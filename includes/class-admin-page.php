<?php
namespace AdminMessenger;

class AdminPage {
    const PAGE_SLUG = 'admin-messenger';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_page']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function add_admin_page() {
        add_menu_page(
            __('Admin Messenger', 'admin-messenger'),
            __('Admin Messenger', 'admin-messenger'),
            'manage_options',
            self::PAGE_SLUG,
            [__CLASS__, 'render_admin_page'],
            'dashicons-email-alt',
            80
        );
    }

    public static function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_' . self::PAGE_SLUG) {
            return;
        }

        wp_enqueue_style(
            'admin-messenger-css',
            ADMIN_MESSENGER_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            ADMIN_MESSENGER_VERSION
        );

        wp_enqueue_script(
            'admin-messenger-js',
            ADMIN_MESSENGER_PLUGIN_URL . 'assets/js/admin-script.js',
            ['jquery'],
            ADMIN_MESSENGER_VERSION,
            true
        );

        wp_localize_script(
            'admin-messenger-js',
            'adminMessenger',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('admin_messenger_nonce'),
                'currentUserId' => get_current_user_id(),
                'i18n' => [
                    'sendMessage' => __('Send Message', 'admin-messenger'),
                    'sending' => __('Sending...', 'admin-messenger'),
                    'messageSent' => __('Message sent!', 'admin-messenger'),
                    'errorSending' => __('Error sending message.', 'admin-messenger'),
                    'markAsRead' => __('Mark as Read', 'admin-messenger'),
                    'marking' => __('Marking...', 'admin-messenger'),
                ]
            ]
        );
    }

    public static function render_admin_page() {
        $messages = Database::get_messages(null, true);
        $admin_users = Messages::get_admin_users();
        $unread_count = Database::get_unread_count();

        include_once ADMIN_MESSENGER_PLUGIN_DIR . 'includes/views/admin-page.php';
    }

    public static function display_notification_bubble() {
        $unread_count = Database::get_unread_count();

        if ($unread_count > 0) {
            global $menu;
            
            foreach ($menu as $key => $value) {
                if ($value[2] === self::PAGE_SLUG) {
                    $menu[$key][0] .= ' <span class="update-plugins count-' . $unread_count . '"><span class="plugin-count">' . $unread_count . '</span></span>';
                    break;
                }
            }
        }
    }
}
