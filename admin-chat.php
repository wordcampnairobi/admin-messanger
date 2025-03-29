<?php

/**
 * Plugin Name: Internal Messages
 * Plugin URI: https://your-website.com/internal-messages
 * Description: Allows WordPress backend users to leave messages for each other
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * Text Domain: internal-messages
 * License: GPL-2.0+
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

// Define plugin constants
define('INTERNAL_MESSAGES_VERSION', '1.0.0');
define('INTERNAL_MESSAGES_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Class to handle the core plugin functionality
 */
class Internal_Messages
{

  /**
   * Initialize the plugin
   */
  public function __construct()
  {
    // Hook into WordPress
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_styles_scripts'));
    add_action('wp_ajax_internal_messages_save', array($this, 'save_message'));
    add_action('wp_ajax_internal_messages_delete', array($this, 'delete_message'));
    add_action('wp_ajax_internal_messages_mark_read', array($this, 'mark_message_read'));
    add_action('admin_notices', array($this, 'display_new_message_notice'));

    // Register activation hook
    register_activation_hook(__FILE__, array($this, 'activate_plugin'));
  }

  /**
   * Plugin activation - create the database table
   */
  public function activate_plugin()
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'internal_messages';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            subject varchar(255) NOT NULL,
            message text NOT NULL,
            sender_id bigint(20) NOT NULL,
            is_read tinyint(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  /**
   * Add menu items to the WordPress admin
   */
  public function add_admin_menu()
  {
    // Count unread messages
    $unread_count = $this->get_unread_count();
    $menu_title = 'Internal Messages';

    if ($unread_count > 0) {
      $menu_title .= ' <span class="awaiting-mod">' . $unread_count . '</span>';
    }

    add_menu_page(
      'Internal Messages',
      $menu_title,
      'edit_posts',
      'internal-messages',
      array($this, 'display_messages_page'),
      'dashicons-email',
      25
    );

    add_submenu_page(
      'internal-messages',
      'New Message',
      'New Message',
      'edit_posts',
      'internal-messages-new',
      array($this, 'display_new_message_page')
    );
  }

  /**
   * Enqueue styles and scripts
   */
  public function enqueue_styles_scripts($hook)
  {
    // Only load on our plugin pages
    if (strpos($hook, 'internal-messages') === false) {
      return;
    }

    wp_enqueue_style(
      'internal-messages-css',
      plugin_dir_url(__FILE__) . 'css/internal-messages.css',
      array(),
      INTERNAL_MESSAGES_VERSION
    );

    wp_enqueue_script(
      'internal-messages-js',
      plugin_dir_url(__FILE__) . 'js/internal-messages.js',
      array('jquery'),
      INTERNAL_MESSAGES_VERSION,
      true
    );

    wp_localize_script(
      'internal-messages-js',
      'internalMessages',
      array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('internal-messages-nonce')
      )
    );
  }

  /**
   * Display the main messages page
   */
  public function display_messages_page()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'internal_messages';

    // Get messages, ordered by newest first
    $messages = $wpdb->get_results(
      "SELECT * FROM $table_name ORDER BY time DESC",
      ARRAY_A
    );

    // Include the view file
    include INTERNAL_MESSAGES_PLUGIN_DIR . 'views/messages-list.php';
  }

  /**
   * Display the new message form
   */
  public function display_new_message_page()
  {
    // Include the view file
    include INTERNAL_MESSAGES_PLUGIN_DIR . 'views/new-message.php';
  }

  /**
   * Ajax handler to save a new message
   */
  public function save_message()
  {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'internal-messages-nonce')) {
      wp_send_json_error('Security check failed');
      exit;
    }

    // Get current user ID
    $sender_id = get_current_user_id();

    // Sanitize and validate input
    $subject = sanitize_text_field($_POST['subject']);
    $message = wp_kses_post($_POST['message']);

    if (empty($subject) || empty($message)) {
      wp_send_json_error('Subject and message are required');
      exit;
    }

    // Insert into database
    global $wpdb;
    $table_name = $wpdb->prefix . 'internal_messages';

    $result = $wpdb->insert(
      $table_name,
      array(
        'subject' => $subject,
        'message' => $message,
        'sender_id' => $sender_id,
        'time' => current_time('mysql'),
        'is_read' => 0
      ),
      array('%s', '%s', '%d', '%s', '%d')
    );

    if ($result) {
      wp_send_json_success('Message saved successfully');
    } else {
      wp_send_json_error('Error saving message');
    }

    exit;
  }

  /**
   * Ajax handler to delete a message
   */
  public function delete_message()
  {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'internal-messages-nonce')) {
      wp_send_json_error('Security check failed');
      exit;
    }

    $message_id = intval($_POST['message_id']);

    if (!$message_id) {
      wp_send_json_error('Invalid message ID');
      exit;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'internal_messages';

    $result = $wpdb->delete(
      $table_name,
      array('id' => $message_id),
      array('%d')
    );

    if ($result) {
      wp_send_json_success('Message deleted successfully');
    } else {
      wp_send_json_error('Error deleting message');
    }

    exit;
  }

  /**
   * Ajax handler to mark a message as read
   */
  public function mark_message_read()
  {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'internal-messages-nonce')) {
      wp_send_json_error('Security check failed');
      exit;
    }

    $message_id = intval($_POST['message_id']);

    if (!$message_id) {
      wp_send_json_error('Invalid message ID');
      exit;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'internal_messages';

    $result = $wpdb->update(
      $table_name,
      array('is_read' => 1),
      array('id' => $message_id),
      array('%d'),
      array('%d')
    );

    if ($result !== false) {
      wp_send_json_success('Message marked as read');
    } else {
      wp_send_json_error('Error updating message');
    }

    exit;
  }

  /**
   * Get the count of unread messages
   */
  public function get_unread_count()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'internal_messages';

    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_read = 0");

    return intval($count);
  }

  /**
   * Display a notice for new messages
   */
  public function display_new_message_notice()
  {
    $count = $this->get_unread_count();

    if ($count > 0) {
      $message = sprintf(
        _n(
          'You have %d unread internal message.',
          'You have %d unread internal messages.',
          $count,
          'internal-messages'
        ),
        $count
      );

      $url = admin_url('admin.php?page=internal-messages');

      echo '<div class="notice notice-info is-dismissible">';
      echo '<p>' . $message . ' <a href="' . esc_url($url) . '">View messages</a></p>';
      echo '</div>';
    }
  }
}

// Initialize the plugin
$internal_messages = new Internal_Messages();

/**
 * Create CSS directory and file on plugin activation
 */
function internal_messages_create_css_file()
{
  $css_dir = plugin_dir_path(__FILE__) . 'css';

  if (!file_exists($css_dir)) {
    mkdir($css_dir, 0755);
  }

  $css_file = $css_dir . '/internal-messages.css';

  if (!file_exists($css_file)) {
    $css_content = "
/* Internal Messages Plugin Styles */
.internal-messages-table {
    width: 100%;
    border-collapse: collapse;
}

.internal-messages-table th,
.internal-messages-table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.internal-messages-table tr:hover {
    background-color: #f5f5f5;
}

.unread-message {
    font-weight: bold;
    background-color: #f0f8ff;
}

.message-action {
    margin-right: 10px;
}

.message-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.message-form input[type='text'],
.message-form textarea {
    width: 100%;
    margin-bottom: 15px;
}

.message-form textarea {
    min-height: 200px;
}
";
    file_put_contents($css_file, $css_content);
  }
}
register_activation_hook(__FILE__, 'internal_messages_create_css_file');

/**
 * Create JS directory and file on plugin activation
 */
function internal_messages_create_js_file()
{
  $js_dir = plugin_dir_path(__FILE__) . 'js';

  if (!file_exists($js_dir)) {
    mkdir($js_dir, 0755);
  }

  $js_file = $js_dir . '/internal-messages.js';

  if (!file_exists($js_file)) {
    $js_content = "
jQuery(document).ready(function($) {
    // Submit new message
    $('#internal-messages-form').on('submit', function(e) {
        e.preventDefault();
        
        var subject = $('#message-subject').val();
        var message = $('#message-content').val();
        
        if (!subject || !message) {
            alert('Please enter both subject and message');
            return;
        }
        
        $.ajax({
            url: internalMessages.ajaxurl,
            type: 'POST',
            data: {
                action: 'internal_messages_save',
                nonce: internalMessages.nonce,
                subject: subject,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    alert('Message sent successfully!');
                    $('#message-subject').val('');
                    $('#message-content').val('');
                    // Redirect to messages list
                    window.location.href = 'admin.php?page=internal-messages';
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while saving the message');
            }
        });
    });
    
    // Mark message as read
    $('.mark-read').on('click', function(e) {
        e.preventDefault();
        
        var messageId = $(this).data('id');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: internalMessages.ajaxurl,
            type: 'POST',
            data: {
                action: 'internal_messages_mark_read',
                nonce: internalMessages.nonce,
                message_id: messageId
            },
            success: function(response) {
                if (response.success) {
                    row.removeClass('unread-message');
                }
            }
        });
    });
    
    // Delete message
    $('.delete-message').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this message?')) {
            return;
        }
        
        var messageId = $(this).data('id');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: internalMessages.ajaxurl,
            type: 'POST',
            data: {
                action: 'internal_messages_delete',
                nonce: internalMessages.nonce,
                message_id: messageId
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while deleting the message');
            }
        });
    });
});
";
    file_put_contents($js_file, $js_content);
  }
}
register_activation_hook(__FILE__, 'internal_messages_create_js_file');

/**
 * Create views directory and files on plugin activation
 */
function internal_messages_create_view_files()
{
  $views_dir = plugin_dir_path(__FILE__) . 'views';

  if (!file_exists($views_dir)) {
    mkdir($views_dir, 0755);
  }

  // Create messages list view
  $list_file = $views_dir . '/messages-list.php';

  if (!file_exists($list_file)) {
    $list_content = '
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <a href="<?php echo admin_url(\'admin.php?page=internal-messages-new\'); ?>" class="button button-primary">Compose New Message</a>
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
                    $sender = get_userdata($message[\'sender_id\']);
                    $sender_name = $sender ? $sender->display_name : \'Unknown User\';
                    $is_unread = !$message[\'is_read\'];
                    $row_class = $is_unread ? \'unread-message\' : \'\';
                ?>
                <tr class="<?php echo esc_attr($row_class); ?>">
                    <td>
                        <?php if ($is_unread) : ?>
                            <span class="dashicons dashicons-email"></span>
                        <?php endif; ?>
                        <?php echo esc_html($message[\'subject\']); ?>
                    </td>
                    <td><?php echo esc_html($sender_name); ?></td>
                    <td><?php echo date_i18n(get_option(\'date_format\') . \' \' . get_option(\'time_format\'), strtotime($message[\'time\'])); ?></td>
                    <td>
                        <button type="button" class="button view-message" data-id="<?php echo esc_attr($message[\'id\']); ?>">View</button>
                        <?php if ($is_unread) : ?>
                            <button type="button" class="button mark-read" data-id="<?php echo esc_attr($message[\'id\']); ?>">Mark Read</button>
                        <?php endif; ?>
                        <button type="button" class="button button-link-delete delete-message" data-id="<?php echo esc_attr($message[\'id\']); ?>">Delete</button>
                    </td>
                </tr>
                <tr id="message-content-<?php echo esc_attr($message[\'id\']); ?>" class="message-content-row" style="display: none;">
                    <td colspan="4">
                        <div class="message-content">
                            <?php echo wpautop($message[\'message\']); ?>
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
        $(\'.view-message\').on(\'click\', function() {
            var messageId = $(this).data(\'id\');
            $(\'#message-content-\' + messageId).toggle();
            
            // If the message is unread, mark it as read
            if ($(this).closest(\'tr\').hasClass(\'unread-message\')) {
                $.post(
                    ajaxurl,
                    {
                        action: \'internal_messages_mark_read\',
                        nonce: internalMessages.nonce,
                        message_id: messageId
                    }
                );
                $(this).closest(\'tr\').removeClass(\'unread-message\');
                $(\'.mark-read[data-id="\' + messageId + \'"]\').remove();
            }
        });
    });
</script>
';
    file_put_contents($list_file, $list_content);
  }

  // Create new message view
  $new_file = $views_dir . '/new-message.php';

  if (!file_exists($new_file)) {
    $new_content = '
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form id="internal-messages-form" class="message-form">
        <div class="form-field">
            <label for="message-subject">Subject:</label>
            <input type="text" id="message-subject" name="subject" required>
        </div>
        
        <div class="form-field">
            <label for="message-content">Message:</label>
            <?php
                wp_editor(
                    \'\',
                    \'message-content\',
                    array(
                        \'textarea_name\' => \'message\',
                        \'media_buttons\' => false,
                        \'textarea_rows\' => 10,
                        \'teeny\' => true,
                    )
                );
            ?>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Send Message">
        </p>
    </form>
</div>
';
    file_put_contents($new_file, $new_content);
  }
}
register_activation_hook(__FILE__, 'internal_messages_create_view_files');
