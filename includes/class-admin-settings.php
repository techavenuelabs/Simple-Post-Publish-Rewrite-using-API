<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
class text_to_AdminSettings
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'initialize_jwt_secret_key']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    public function initialize_jwt_secret_key()
    {
        if (defined('JWT_AUTH_SECRET_KEY') && !get_option('JWT_AUTH_SECRET_KEY')) {
            update_option('JWT_AUTH_SECRET_KEY', JWT_AUTH_SECRET_KEY);
        }
    }

    public function enqueue_admin_styles($hook)
    {
        // Load styles only on your plugin's admin page
        if ($hook !== 'settings_page_text-to-article') {
            return;
        }

        wp_enqueue_style(
            'text-to-article-admin-style',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            [],
            '1.0.0'
        );
    }

    public function render_settings_page()
    {
        // Check for nonce before processing form data
        if (
            isset($_POST['save_text_to_article_settings']) &&
            isset($_POST['_wpnonce']) && 
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'text_to_article_settings_nonce')
        ) {
            if (isset($_POST['openai_key'])) {
                update_option('text_to_article_openai_key', sanitize_text_field(wp_unslash($_POST['openai_key'])));
            }
            if (isset($_POST['prompt'])) {
                update_option('text_to_article_prompt', sanitize_textarea_field(wp_unslash($_POST['prompt'])));
            }
            if (isset($_POST['prompt_title'])) {
                update_option('text_to_article_prompt_title', sanitize_textarea_field(wp_unslash($_POST['prompt_title'])));
            }
            if (isset($_POST['post_status'])) {
                update_option('text_to_article_post_status', sanitize_text_field(wp_unslash($_POST['post_status'])));
            }
            if (isset($_POST['gpt_model'])) {
                update_option('text_to_article_gpt_model', sanitize_text_field(wp_unslash($_POST['gpt_model'])));
            }
            if (isset($_POST['max_tokens'])) {
                update_option('text_to_article_max_tokens', intval(wp_unslash($_POST['max_tokens'])));
            }
            echo "<div class='updated'><p>Settings saved!</p></div>";
        }

        $openai_key = get_option('text_to_article_openai_key');
        $prompt = get_option('text_to_article_prompt');
        $prompt_title = get_option('text_to_article_prompt_title');
        $post_status = get_option('text_to_article_post_status', 'pending');
        $gpt_model = get_option('text_to_article_gpt_model', 'gpt-3.5-turbo');
        $max_tokens = get_option('text_to_article_max_tokens', 200);
        $jwt_auth_defined = get_option('JWT_AUTH_SECRET_KEY') ? 'Yes' : 'No';
        $categories = get_categories(['hide_empty' => false]);
        ?>
<form method="POST" class="text-to-article-settings">
   <h2>Text to Article Settings</h2>
   
   <!-- Add nonce field to secure form -->
   <?php wp_nonce_field('text_to_article_settings_nonce'); ?>

   <p><strong>JWT Auth Secret Key Defined:</strong> <?php echo esc_html($jwt_auth_defined); ?></p><br><br>
   <div>
      <label for="openai_key">OpenAI API Key</label>
      <input type="text" name="openai_key" id="openai_key" value="<?php echo esc_attr($openai_key); ?>" required>
      <a href="https://platform.openai.com/signup" target="_blank" style="margin-left: 10px;">Get your API key</a><br><br>
   </div>
   <div>
      <label for="prompt">Content GPT Prompt</label>
      <textarea name="prompt" id="prompt" rows="4" required><?php echo esc_textarea($prompt); ?></textarea><br><br>
   </div>
   <div>
      <label for="prompt_title">Title GPT Prompt</label>
      <textarea name="prompt_title" id="prompt_title" rows="4" required><?php echo esc_textarea($prompt_title); ?></textarea><br><br>
   </div>
   <div>
      <label for="post_status">Default Post Status</label>
      <select name="post_status" id="post_status">
         <option value="publish" <?php selected($post_status, 'publish'); ?>>Published</option><br><br>
         <option value="pending" <?php selected($post_status, 'pending'); ?>>Pending Review</option><br><br>
      </select><br><br>
   </div>
   <div>
      <label for="gpt_model">GPT Model</label>
      <select name="gpt_model" id="gpt_model">
         <option value="gpt-3.5-turbo" <?php selected($gpt_model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
         <option value="gpt-4" <?php selected($gpt_model, 'gpt-4'); ?>>GPT-4</option>
      </select><br><br>
   </div>
   <div>
      <label for="max_tokens">Max Tokens</label>
      <input type="number" name="max_tokens" id="max_tokens" value="<?php echo esc_attr($max_tokens); ?>" min="1" max="4096" required><br><br>
   </div>
   <input type="submit" name="save_text_to_article_settings" value="Save Settings">
</form>


<h3>API Endpoint</h3>
<p><strong>API Endpoint:</strong> <code><?php echo esc_url(home_url('/wp-json/api-publisher/v1/generate')); ?></code></p>

<h3>Accepted Values</h3>
<ul>
   <li><strong>Authentication:</strong> JWT token (Bearer)</li>
   <li><strong>text:</strong> Post content (required)</li>
   <li><strong>image_url:</strong> Thumbnail image URL (optional)</li>
   <li><strong>tags:</strong> Tags (optional)</li>
   <li><strong>category:</strong> Category ID (If multiple, seperated by commas, required)</li>
   <li><strong>rewrite:</strong> Rewrite content (boolean value: true/false, Default:false)</li>
   <li><strong>generate_title:</strong> Generate title (boolean value: true/false, Default:false)</li>
</ul>

<p style="margin-top: 20px; text-align: center;">
   <a href="https://buymeacoffee.com/techavenuelabs" target="_blank" style="text-decoration: none; color: #555;">
      ❤️ Liked our plugin? <strong>Donate us</strong>
   </a>
</p>
<?php
    }
}
// Initialize the settings page
new text_to_AdminSettings();
