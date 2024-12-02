<?php
/*
Plugin Name: Simple Post Publish & Rewrite using API
Description: Publishes and rewrites text content into articles using OpenAI's GPT model, saving images in the WordPress media library. Requires the JWT Authentication for WP REST API plugin.
Requires Plugins: jwt-authentication-for-wp-rest-api
Version: 1.1
Author: Tech Avenue Labs
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

*/
if (!defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}
// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';
// Include the Firebase JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
class text_to_TextToArticle
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('rest_api_init', [$this, 'register_routes'], 15);
    }
    public function register_admin_menu()
    {
        add_menu_page('API Publisher Settings', 'API Publisher', 'manage_options', 'api-publisher', [$this, 'settings_page']);
        add_submenu_page('api-publisher', 'Request Log', 'Request Log', 'manage_options', 'api-publisher-log', [$this, 'log_page']);
    }
    public function settings_page()
    {
        $settings = new text_to_AdminSettings();
        $settings->render_settings_page();
    }
    public function log_page()
    {
        $logger = new text_to_Logger();
        $logger->render_log_page();
    }
    public function register_routes()
    {
        register_rest_route('api-publisher/v1', '/generate', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_article'],
            'permission_callback' => [$this, 'validate_author'],
        ]);
    }
	

	
	public function validate_author($request)
	{
    $jwt_token = $request->get_header('Authorization'); // Get the JWT token from the header
    if (!$jwt_token) {
        return new WP_Error('jwt_auth_no_token', 'Authorization token not provided', ['status' => 401]);
    }
    // Remove "Bearer " from the token if present
    $jwt_token = str_replace('Bearer ', '', $jwt_token);
    // Decode the token
    $user_data = $this->decode_jwt_token($jwt_token);
    if (is_wp_error($user_data)) {
        return $user_data; // Return error if token decoding fails
    }
    // Retrieve user by ID
    $user = get_user_by('id', $user_data['data']->user->id);
    if (!$user || !$user->has_cap('publish_posts')) {
        return new WP_Error('jwt_auth_insufficient_capability', 'You do not have sufficient permissions to publish articles', ['status' => 403]);
    }
    return true; // User is authenticated and has the correct capability
	}


    private function decode_jwt_token($jwt)
    {
        $secret_key = get_option('JWT_AUTH_SECRET_KEY');
        if (!$secret_key) {
            return new WP_Error('jwt_auth_missing_secret', 'JWT secret key is missing', ['status' => 500]);
        }
        try {
            // Decode the JWT using the Key class
            $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
            return (array) $decoded; // Convert to array for easier access
        } catch (ExpiredException $e) {
            return new WP_Error('jwt_auth_expired', 'Token has expired', ['status' => 401]);
        } catch (Exception $e) {
            return new WP_Error('jwt_auth_invalid', 'Invalid token', ['status' => 401]);
        }
    }
    public function generate_article($request)
    {
       // Retrieve the text content, image URL, and rewrite parameters
	$text_content = $request->get_param('text');
	$image_url = $request->get_param('image_url');
	$rewrite = filter_var($request->get_param('rewrite'), FILTER_VALIDATE_BOOLEAN); // Convert to boolean
	$category = $request->get_param('category');
	$generate_title = filter_var($request->get_param('generate_title'), FILTER_VALIDATE_BOOLEAN); // Convert to boolean
	$title = $request->get_param('title');
	$tags = $request->get_param('tags');

	// Default values if not set
	if ($rewrite === null) {
		$rewrite = false;
	}
	if ($generate_title === null) {
		$generate_title = false;
	}

	// Check if all required parameters are present
	if (empty($text_content)) {
		return new WP_Error('missing_parameter', 'Missing parameter: text', ['status' => 400]);
	}

	if ($generate_title === false && empty($title)) {
		return new WP_Error('missing_parameter', 'Title is required when generate_title is false', ['status' => 400]);
	}

	if ($category === null) {
		return new WP_Error('missing_parameter', 'Missing parameter: category', ['status' => 400]);
	}

        // Fetch settings
        $api_key = get_option('text_to_article_openai_key');
        $prompt = get_option('text_to_article_prompt');
        $prompt_title = get_option('text_to_article_prompt_title');
        $post_status = get_option('text_to_article_post_status', 'pending');
        $gpt_model = get_option('text_to_article_gpt_model', 'gpt-3.5-turbo'); // Default to GPT-3.5 Turbo if not set
        $max_tokens = (int) get_option('text_to_article_max_tokens', 200);
        // Generate title if required
        if ($generate_title) {
            $title_response = $this->call_openai_api_for_title($api_key, $prompt_title, $text_content, $gpt_model, $max_tokens);
            if (is_wp_error($title_response)) {
                return $title_response; // Return error if title generation fails
            }
            $title = wp_strip_all_tags($title_response['title']); // Use the generated title
        } else {
            $title = wp_strip_all_tags($title); // Use provided title
        }
        // If rewrite is true, generate the article content using OpenAI API
        if ($rewrite) {
            $content_response = $this->call_openai_api_for_content($api_key, $prompt, $text_content, $title, $gpt_model, $max_tokens);
            if (is_wp_error($content_response)) {
                return $content_response; // Return error if content generation fails
            }
            $content = $content_response['content']; // Use the generated content
        } else {
            // If rewrite is false, use the original content as-is
            $content = wp_strip_all_tags($text_content); // Sanitize the original content
        }
        // Create WordPress post
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $post_status,
            'post_category' => [$category],
        ]);
        // Add tags to the post if they were provided
        if (!empty($tags)) {
            wp_set_post_tags($post_id, $tags);
        }
        // Handle image upload if image URL is provided
        if ($image_url) {
            $image_id = $this->upload_image($image_url, $post_id);
            if (is_wp_error($image_id)) {
                return $image_id; // Return error if image upload fails
            }
            // Set the featured image for the post
            set_post_thumbnail($post_id, $image_id);
        }
        // Log request and response
        text_to_Logger::log($request->get_params(), ['title' => $title, 'content' => $content], $post_id);
        return new WP_REST_Response(
            [
                'status' => "success",
                'message' => 'Post created successfully!',
                'post_id' => $post_id,
            ],
            201
        );
    }
    // Function to upload the image and return the attachment ID
    private function upload_image($image_url, $post_id)
    {
        // Include the required WordPress file for media handling
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        // Get the image content
        $response = wp_remote_get($image_url);
        if (is_wp_error($response)) {
            return new WP_Error('image_fetch_failed', 'Failed to fetch image.', ['status' => 500]);
        }
        // Check if the request was successful
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error('image_invalid_url', 'Image URL is invalid.', ['status' => 400]);
        }
        $image_data = wp_remote_retrieve_body($response);
        $filename = basename($image_url);
        // Create a temporary file in the system's temp directory
        $tmp_file = tempnam(sys_get_temp_dir(), 'img_');
        // Save the image data to the temporary file
        if (false === ($creds = request_filesystem_credentials('', '', false, false, null))) {
    // Unable to get filesystem credentials; return an error.
    return new WP_Error('file_system_error', 'Could not access the filesystem.', ['status' => 500]);
		}

		// Initialize the filesystem API
		if (!WP_Filesystem($creds)) {
			return new WP_Error('file_system_error', 'Could not initialize the filesystem.', ['status' => 500]);
		}

		// Get the global filesystem object
		global $wp_filesystem;

		// Check if the filesystem object was initialized correctly
		if (!$wp_filesystem->put_contents($tmp_file, $image_data, FS_CHMOD_FILE)) {
			return new WP_Error('file_write_failed', 'Failed to write image data.', ['status' => 500]);
		}

        // Set up the file array
        $file_array = [
            'name' => $filename,
            'tmp_name' => $tmp_file,
        ];
        // Upload the image to the media library
        $attachment_id = media_handle_sideload($file_array, $post_id);
        // Check for errors during upload
        if (is_wp_error($attachment_id)) {
            return $attachment_id; // Return the error
        }
        return $attachment_id; // Return the attachment ID
    }
    private function call_openai_api_for_title($api_key, $prompt_title, $content, $gpt_model, $max_tokens)
    {
        $api_url = 'https://api.openai.com/v1/chat/completions';
        // Construct the prompt for title generation
        $full_prompt = $prompt_title . "Content:\n" . $content;
        $data = [
            'model' => $gpt_model,
            'messages' => [['role' => 'user', 'content' => $full_prompt]],
            'max_tokens' => $max_tokens,
        ];
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($data),
            'timeout' => 15,
        ];
        $response = wp_remote_post($api_url, $args);
        if (is_wp_error($response)) {
            return new WP_Error('openai_error', 'Failed to connect to OpenAI: ' . $response->get_error_message(), ['status' => 500]);
        }
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            return new WP_Error('openai_error', 'OpenAI response error: ' . $body, ['status' => $status_code]);
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices'][0]['message']['content'])) {
            return [
                'title' => trim($body['choices'][0]['message']['content']), // Process title without prefix
            ];
        }
        return new WP_Error('openai_error', 'Invalid response structure', ['status' => 500]);
    }
    private function call_openai_api_for_content($api_key, $prompt, $content, $title, $gpt_model, $max_tokens)
    {
        $api_url = 'https://api.openai.com/v1/chat/completions';
        // Construct the prompt for content generation
        $full_prompt = $prompt . "\n\n" . "Using the title: \"$title\" and the following content, please generate a complete news article:\n\n" . "Content:\n" . $content;
        $data = [
            'model' => $gpt_model,
            'messages' => [['role' => 'user', 'content' => $full_prompt]],
            'max_tokens' => $max_tokens,
        ];
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($data),
            'timeout' => 15,
        ];
        $response = wp_remote_post($api_url, $args);
        if (is_wp_error($response)) {
            return new WP_Error('openai_error', 'Failed to connect to OpenAI: ' . $response->get_error_message(), ['status' => 500]);
        }
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            return new WP_Error('openai_error', 'OpenAI response error: ' . $body, ['status' => $status_code]);
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices'][0]['message']['content'])) {
            return [
                'content' => trim($body['choices'][0]['message']['content']), // Process generated content
            ];
        }
        return new WP_Error('openai_error', 'Invalid response structure', ['status' => 500]);
    }
}
new text_to_TextToArticle();
