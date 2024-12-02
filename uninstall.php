<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

delete_option('text_to_article_log');
