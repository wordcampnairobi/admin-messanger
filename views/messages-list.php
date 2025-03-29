
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <a href="<?php echo admin_url('admin.php?page=internal-messages-new'); ?>" class="button button-primary">Compose New Message</a>
        </div>
        <br class="clear">
    </div>
    
    <?php if (empty($messages)) : ?>
        <div class="notice notice-info">
            <p>No messages found.</p>
        </div>
    <?php else : ?>
        <table class="widefat internal-messages-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Sender</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $message) : 
                    $sender = get_userdata($message['sender_id']);
                    $sender_name = $sender ? $sender->display_name : 'Unknown User';
                    $is_unread = !$message['is_read'];
                    $row_class = $is_unread ? 'unread-message' : '';
                ?>
                <tr class="<?php echo esc_attr($row_class); ?>">
                    <td>
                        <?php if ($is_unread) : ?>
                            <span class="dashicons dashicons-email"></span>
                        <?php endif; ?>
                        <?php echo esc_html($message['subject']); ?>
                    </td>
                    <td><?php echo esc_html($sender_name); ?></td>
                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($message['time'])); ?></td>
                    <td>
                        <button type="button" class="button view-message" data-id="<?php echo esc_attr($message['id']); ?>">View</button>
                        <?php if ($is_unread) : ?>
                            <button type="button" class="button mark-read" data-id="<?php echo esc_attr($message['id']); ?>">Mark Read</button>
                        <?php endif; ?>
                        <button type="button" class="button button-link-delete delete-message" data-id="<?php echo esc_attr($message['id']); ?>">Delete</button>
                    </td>
                </tr>
                <tr id="message-content-<?php echo esc_attr($message['id']); ?>" class="message-content-row" style="display: none;">
                    <td colspan="4">
                        <div class="message-content">
                            <?php echo wpautop($message['message']); ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Toggle message content visibility
        $('.view-message').on('click', function() {
            var messageId = $(this).data('id');
            $('#message-content-' + messageId).toggle();
            
            // If the message is unread, mark it as read
            if ($(this).closest('tr').hasClass('unread-message')) {
                $.post(
                    ajaxurl,
                    {
                        action: 'internal_messages_mark_read',
                        nonce: internalMessages.nonce,
                        message_id: messageId
                    }
                );
                $(this).closest('tr').removeClass('unread-message');
                $('.mark-read[data-id="' + messageId + '"]').remove();
            }
        });
    });
</script>
