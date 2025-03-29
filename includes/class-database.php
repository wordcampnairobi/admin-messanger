<?php
namespace AdminMessenger;

class Database {
    const TABLE_NAME = 'admin_messages';
    const DB_VERSION = '1.0';

    public static function init() {
        // Initialization if needed
    }

    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_id mediumint(9) NOT NULL,
            recipient_id mediumint(9) DEFAULT NULL COMMENT 'NULL means all admins',
            subject varchar(255) NOT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('admin_messenger_db_version', self::DB_VERSION);
    }

    public static function deactivate() {
        // For now, we won't remove the table on deactivation
        // You might want to add an option to clean up data in plugin settings
    }

    public static function get_messages($recipient_id = null, $all = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        if ($all) {
            // Get all messages (for admin page)
            return $wpdb->get_results(
                "SELECT m.*, u1.user_login as sender_login, u2.user_login as recipient_login 
                FROM $table_name m
                LEFT JOIN {$wpdb->users} u1 ON m.sender_id = u1.ID
                LEFT JOIN {$wpdb->users} u2 ON m.recipient_id = u2.ID
                ORDER BY m.created_at DESC"
            );
        }

        if ($recipient_id === null) {
            // Get messages for current user
            $recipient_id = get_current_user_id();
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, u.user_login as sender_login 
                FROM $table_name m
                LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
                WHERE (m.recipient_id = %d OR m.recipient_id IS NULL) AND m.sender_id != %d
                ORDER BY m.created_at DESC",
                $recipient_id,
                $recipient_id
            )
        );
    }

    public static function create_message($sender_id, $recipient_id, $subject, $message) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->insert(
            $table_name,
            [
                'sender_id' => $sender_id,
                'recipient_id' => $recipient_id,
                'subject' => $subject,
                'message' => $message
            ],
            ['%d', '%d', '%s', '%s']
        );
    }

    public static function mark_as_read($message_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->update(
            $table_name,
            ['is_read' => 1],
            ['id' => $message_id],
            ['%d'],
            ['%d']
        );
    }

    public static function get_unread_count($user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                FROM $table_name 
                WHERE (recipient_id = %d OR recipient_id IS NULL) 
                AND sender_id != %d 
                AND is_read = 0",
                $user_id,
                $user_id
            )
        );
    }
}
