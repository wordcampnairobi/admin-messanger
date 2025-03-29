<div class="wrap admin-messenger">
    <h1><?php esc_html_e('Admin Messenger', 'admin-messenger'); ?></h1>

    <div class="admin-messenger-container">
        <div class="admin-messenger-form">
            <h2><?php esc_html_e('Send New Message', 'admin-messenger'); ?></h2>
            
            <form id="admin-messenger-send-form">
                <div class="form-group">
                    <label for="admin-messenger-recipient"><?php esc_html_e('Recipient:', 'admin-messenger'); ?></label>
                    <select id="admin-messenger-recipient" class="regular-text">
                        <option value="0"><?php esc_html_e('All Admins', 'admin-messenger'); ?></option>
                        <?php foreach ($admin_users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>">
                                <?php echo esc_html($user->display_name ?: $user->user_login); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="admin-messenger-subject"><?php esc_html_e('Subject:', 'admin-messenger'); ?></label>
                    <input type="text" id="admin-messenger-subject" class="regular-text" required>
                </div>
                
                <div class="form-group">
                    <label for="admin-messenger-message"><?php esc_html_e('Message:', 'admin-messenger'); ?></label>
                    <textarea id="admin-messenger-message" rows="5" class="large-text" required></textarea>
                </div>
                
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Send Message', 'admin-messenger'); ?>
                </button>
                
                <div id="admin-messenger-response" class="response-message"></div>
            </form>
        </div>
        
        <div class="admin-messenger-messages">
            <h2><?php esc_html_e('Messages', 'admin-messenger'); ?></h2>
            
            <?php if (empty($messages)) : ?>
                <p><?php esc_html_e('No messages yet.', 'admin-messenger'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('From', 'admin-messenger'); ?></th>
                            <th><?php esc_html_e('To', 'admin-messenger'); ?></th>
                            <th><?php esc_html_e('Subject', 'admin-messenger'); ?></th>
                            <th><?php esc_html_e('Message', 'admin-messenger'); ?></th>
                            <th><?php esc_html_e('Date', 'admin-messenger'); ?></th>
                            <th><?php esc_html_e('Actions', 'admin-messenger'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message) : ?>
                            <tr class="<?php echo !$message->is_read && $message->recipient_id == get_current_user_id() ? 'unread-message' : ''; ?>">
                                <td><?php echo esc_html($message->sender_login); ?></td>
                                <td><?php echo $message->recipient_login ? esc_html($message->recipient_login) : esc_html__('All Admins', 'admin-messenger'); ?></td>
                                <td><?php echo esc_html($message->subject); ?></td>
                                <td><?php echo wp_kses_post(wpautop($message->message)); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($message->created_at))); ?></td>
                                <td>
                                    <?php if (!$message->is_read && $message->recipient_id == get_current_user_id()) : ?>
                                        <button class="button mark-as-read" data-message-id="<?php echo esc_attr($message->id); ?>">
                                            <?php esc_html_e('Mark as Read', 'admin-messenger'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
