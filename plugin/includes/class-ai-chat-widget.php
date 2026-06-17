<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Chat_Widget {

    private const OPTION_GROUP = 'ai_chat_widget_group';
    private const PAGE_SLUG    = 'ai-chat-widget';

    private array $fields = [
        [
            'id'          => 'ai_chat_widget_backend_url',
            'label'       => 'Backend API URL',
            'type'        => 'url',
            'placeholder' => 'https://your-api.up.railway.app',
            'default'     => '',
            'desc'        => 'Your Railway backend URL (no trailing slash).',
        ],
        [
            'id'          => 'ai_chat_widget_api_key',
            'label'       => 'Anthropic API Key',
            'type'        => 'password',
            'placeholder' => 'sk-ant-...',
            'default'     => '',
            'desc'        => 'Stored securely, never sent to the browser.',
        ],
        [
            'id'          => 'ai_chat_widget_title',
            'label'       => 'Widget Title',
            'type'        => 'text',
            'placeholder' => 'AI Assistant',
            'default'     => 'AI Assistant',
            'desc'        => 'Displayed in the chat header.',
        ],
        [
            'id'          => 'ai_chat_widget_color',
            'label'       => 'Primary Color',
            'type'        => 'color',
            'placeholder' => '#6366f1',
            'default'     => '#6366f1',
            'desc'        => 'Hex color for the chat button and header.',
        ],
        [
            'id'          => 'ai_chat_widget_welcome',
            'label'       => 'Welcome Message',
            'type'        => 'text',
            'placeholder' => 'Hello! How can I help you today?',
            'default'     => 'Hello! How can I help you today?',
            'desc'        => 'First message shown when the chat opens.',
        ],
    ];

    public function __construct() {
        add_action( 'admin_menu',       [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init',       [ $this, 'register_settings' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_shortcode( 'ai_chat_widget', [ $this, 'render_shortcode' ] );
    }

    public function add_admin_menu(): void {
        add_options_page(
            'AI Chat Widget Settings',
            'AI Chat Widget',
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings(): void {
        foreach ( $this->fields as $field ) {
            register_setting( self::OPTION_GROUP, $field['id'], [
                'sanitize_callback' => $field['type'] === 'url'
                    ? 'esc_url_raw'
                    : ( $field['type'] === 'color'
                        ? 'sanitize_hex_color'
                        : 'sanitize_text_field' ),
                'default' => $field['default'],
            ] );

            add_settings_field(
                $field['id'],
                $field['label'],
                [ $this, 'render_field' ],
                self::PAGE_SLUG,
                'ai_chat_widget_main',
                $field
            );
        }

        add_settings_section(
            'ai_chat_widget_main',
            '',
            '__return_null',
            self::PAGE_SLUG
        );
    }

    public function render_field( array $field ): void {
        $id    = esc_attr( $field['id'] );
        $type  = $field['type'];
        $value = get_option( $field['id'], $field['default'] );
        $ph    = esc_attr( $field['placeholder'] );
        $desc  = esc_html( $field['desc'] );

        if ( $type === 'color' ) {
            printf(
                '<input type="color" name="%s" id="%s" value="%s" style="height:36px;cursor:pointer" /> '
                . '<code style="margin-left:8px">%s</code>',
                $id,
                $id,
                esc_attr( $value ?: $field['default'] ),
                esc_html( $value ?: $field['default'] )
            );
        } elseif ( $type === 'password' ) {
            printf(
                '<input type="password" name="%s" id="%s" value="%s" placeholder="%s" class="regular-text" autocomplete="new-password" />',
                $id,
                $id,
                esc_attr( $value ),
                $ph
            );
        } else {
            printf(
                '<input type="%s" name="%s" id="%s" value="%s" placeholder="%s" class="regular-text" />',
                esc_attr( $type ),
                $id,
                $id,
                esc_attr( $value ),
                $ph
            );
        }

        echo "<p class=\"description\">{$desc}</p>";
    }

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p>
                Add <code>[ai_chat_widget]</code> to any page or post to show the chat button.<br>
                The widget automatically appears on pages that include the shortcode, or everywhere if you add it to your theme's <code>footer.php</code>.
            </p>
            <form action="options.php" method="post">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( 'Save Settings' );
                ?>
            </form>
            <hr>
            <h2>How to use</h2>
            <ol>
                <li>Enter your <strong>Backend API URL</strong> (Railway deployment).</li>
                <li>Enter your <strong>Anthropic API Key</strong> — it stays on the server.</li>
                <li>Customize the title, color, and welcome message.</li>
                <li>Add <code>[ai_chat_widget]</code> to any page.</li>
            </ol>
        </div>
        <?php
    }

    public function enqueue_scripts(): void {
        wp_enqueue_script(
            'ai-chat-widget',
            AI_CHAT_WIDGET_URL . 'assets/chat-widget.iife.js',
            [],
            AI_CHAT_WIDGET_VERSION,
            true
        );

        wp_localize_script( 'ai-chat-widget', 'AIChatWidgetSettings', [
            'backendUrl'     => get_option( 'ai_chat_widget_backend_url', '' ),
            'widgetTitle'    => get_option( 'ai_chat_widget_title', 'AI Assistant' ),
            'primaryColor'   => get_option( 'ai_chat_widget_color', '#6366f1' ),
            'welcomeMessage' => get_option( 'ai_chat_widget_welcome', 'Hello! How can I help you today?' ),
            'nonce'          => wp_create_nonce( 'ai_chat_widget_nonce' ),
        ] );
    }

    public function render_shortcode( array $atts ): string {
        $atts = shortcode_atts( [], $atts, 'ai_chat_widget' );
        unset( $atts ); // shortcode activates the widget via JS; no wrapper HTML needed
        return '<div id="ai-chat-widget-root"></div>';
    }
}
