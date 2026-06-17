<?php
/**
 * Plugin Name: AI Chat Widget
 * Plugin URI:  https://github.com/edwardkim-ai/ai-chat-widget
 * Description: Embeds a Claude-powered AI chatbot on any page or post using the [ai_chat_widget] shortcode.
 * Version:     1.0.0
 * Author:      Edward Kim
 * Author URI:  https://github.com/edwardkim-ai
 * License:     GPL-2.0+
 * Text Domain: ai-chat-widget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AI_CHAT_WIDGET_VERSION', '1.0.0' );
define( 'AI_CHAT_WIDGET_DIR',     plugin_dir_path( __FILE__ ) );
define( 'AI_CHAT_WIDGET_URL',     plugin_dir_url( __FILE__ ) );

require_once AI_CHAT_WIDGET_DIR . 'includes/class-ai-chat-widget.php';

add_action( 'plugins_loaded', function () {
    new AI_Chat_Widget();
} );
