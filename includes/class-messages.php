<?php
namespace AdminMessenger;

class Messages {
    public static function init() {
        add_action('wp_ajax_admin_messenger_send_message', [__CLASS__, 'handle_send_message']);
        add_action('wp_ajax_admin_messenger_mark_read', [__CLASS__, 'handle_mark_read']);
    }

    public static function handle_send_message() {
        check_ajax_referer('admin_messenger_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'admin-messenger'));
        }

        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = wp_kses_post($_POST['message'] ?? '');
        $recipient_id = isset($_POST['recipient_id']) ? intval($_POST['recipient_id']) : null;

        if (empty($subject) || empty($message)) {
            wp_send_json_error(__('Subject and message are required.', 'admin-messenger'));
        }

        $result = Database::create_message(
            get_current_user_id(),
            $recipient_id === 0 ? null : $recipient_id,
            $subject,
            $message
        );

        if ($result === false) {
            wp_send_json_error(__('Failed to send message.', 'admin-messenger'));
        }

        wp_send_json_success(__('Message sent successfully.', 'admin-messenger'));
    }

    public static function handle_mark_read() {
        check_ajax_referer('admin_messenger_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'admin-messenger'));
        }

        $message_id = intval($_POST['message_id'] ?? 0);

        if ($message_id <= 0) {
            wp_send_json_error(__('Invalid message ID.', 'admin-messenger'));
        }

        $result = Database::mark_as_read($message_id);

        if ($result === false) {
            wp_send_json_error(__('Failed to mark message as read.', 'admin-messenger'));
        }

        wp_send_json_success(__('Message marked as read.', 'admin-messenger'));
    }

    public static function get_admin_users() {
        return get_users([
            'role__in' => ['administrator'],
            'fields' => ['ID', 'user_login', 'display_name'],
            'exclude' => [get_current_user_id()]
        ]);
    }
}
