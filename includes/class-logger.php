<?php

class text_to_Logger {
    public static function log($request, $response, $post_id) {
        $log = get_option('text_to_article_log', []);
        $log[] = [
            'timestamp' => current_time('mysql'),
            'request' => $request,
            'response' => $response,
            'post_id' => $post_id,
        ];
        update_option('text_to_article_log', $log);
    }

    public function render_log_page() {
        // Check if the clear log button was pressed and clear the log if true
        if (isset($_POST['clear_log']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'clear_log_nonce')) {
            update_option('text_to_article_log', []);
            echo "<div class='updated'><p>Log cleared successfully!</p></div>";
        }

        // Retrieve and display the log
        $log = get_option('text_to_article_log', []);
        
        echo "<h2>Text to Article Log</h2>";
        
        // Add form with the Clear Log button and nonce field
        echo '<form method="POST">';
        wp_nonce_field('clear_log_nonce'); // Add nonce field
        echo '<input type="submit" name="clear_log" value="Clear Log" style="background-color: #d9534f; color: #fff; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer;">';
        echo '</form>';
        
        echo "<pre>";
        echo esc_html(var_export($log, true)); // Use esc_html and var_export for safer output
        echo "</pre>";
    }
}
