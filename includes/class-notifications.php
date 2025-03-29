<?php
namespace AdminMessenger;

class Notifications {
    public static function init() {
        add_action('admin_bar_menu', [__CLASS__, 'add_admin_bar_notification'], 999);
        add_action('admin_head', [__CLASS__, 'add_notification_styles']);
        add_action('admin_menu', [__CLASS__, 'display_notification_bubble']);
    }

    public static function add_admin_bar_notification($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $unread_count = Database::get_unread_count();

        if ($unread_count > 0) {
            $wp_admin_bar->add_node([
                'id'    => 'admin-messenger-notification',
                'title' => sprintf(
                    '<span class="ab-icon"></span><span class="ab-label">%d</span>',
                    $unread_count
                ),
                'href'  => admin_url('admin.php?page=' . AdminPage::PAGE_SLUG),
                'meta'  => [
                    'title' => __('Unread admin messages', 'admin-messenger'),
                ],
            ]);
        }
    }

    public static function add_notification_styles() {
        ?>
        <style>
            #wpadminbar #wp-admin-bar-admin-messenger-notification .ab-icon:before {
                content: '\f466';
                top: 2px;
            }
            #wpadminbar #wp-admin-bar-admin-messenger-notification .ab-label {
                margin-left: 4px;
            }
        </style>
        <?php
    }
}
