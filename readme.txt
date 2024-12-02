=== Simple Post Publish & Rewrite using API ===
Contributors: techavenuelabs
Tags: post publish, OpenAI, GPT, API, rewrite
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

A WordPress plugin to publish and rewrite text content into articles automatically using OpenAI's GPT model.

== Description ==

Simple Post Publish & Rewrite using API is a WordPress plugin that allows users to publish and rewrite text content into articles automatically. The plugin leverages OpenAI's GPT model for content generation and rewriting, making it easier to create articles with minimal effort. Additionally, any associated images (thumbnails) are automatically saved to the WordPress media library. Developers can use this API to integrate into their projects and handle auto rewrite & publishing.

=== Key Features ===
- **Publish & Rewrite Content**: Using OpenAI's GPT model, the plugin can publish new posts and rewrite existing content into well-structured news articles.
- **API Integration**: You can access the plugin’s functionality via a RESTful API, making it easy to integrate with external scripts and applications (like PHP, Python, Curl, etc.) using HTTP POST requests.
- **Image Handling**: The plugin saves any associated image URL as a thumbnail image in the WordPress media library.
- **Flexible Content Generation**: The plugin allows you to customize the content generation, including generating a title, rewriting content, and adding categories and tags.
- **Automatic Post Creation**: Generate content automatically from external sources, scripts, or applications, saving time and streamlining content management.

=== Requirements ===
- **JWT Authentication for WP REST API**: This plugin requires the **JWT Authentication for WP REST API** plugin for secure API authentication using a JWT token.

== Installation ==

1. Download and install the plugin from the WordPress plugin directory.
2. Activate the plugin from the WordPress admin dashboard.
3. Install and configure the **JWT Authentication for WP REST API** plugin.
4. Configure the settings in the WordPress admin panel to connect with OpenAI API and set other necessary options.
5. Access the API from external scripts using the endpoint provided below.

== API Endpoint ==

- **API Endpoint URL**:  
  `https://yoursite.com/wp-json/api-publisher/v1/generate`

=== Authentication ===
To use the API, authentication is required using a **JWT token** (Bearer). Ensure that the JWT Authentication for WP REST API plugin is installed and configured properly.

== External Services ==

This plugin connects to OpenAI's GPT API to generate and rewrite article content. It sends user-provided text and settings to OpenAI's servers for processing and retrieves the generated content. The use of this service requires an active OpenAI API key.

Service provider: [OpenAI](https://openai.com/)  
Terms of use: [OpenAI Terms of Use](https://openai.com/terms)  
Privacy policy: [OpenAI Privacy Policy](https://openai.com/privacy)

== Screenshots ==

1-Plugin settings page for configuring OpenAI API key and other options.

== Changelog ==

= 1.1 =
* Improvements.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.1 =
* Improvements.

= 1.0 =
Initial release of the plugin, offering API-based post-publishing and rewriting functionality.

== Frequently Asked Questions ==

= How do I use this plugin? =
Just configure your OpenAI API key in the plugin settings and start publishing or rewriting articles.

== Donate ==

If you find this plugin helpful, consider supporting us: [Donate here](buymeacoffee.com/techavenuelabs).
