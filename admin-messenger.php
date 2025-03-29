<?php
/**
 * Plugin Name: Admin Messenger
 * Description: Enables admin-to-admin communication within WordPress dashboard
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: admin-messenger
 * Domain Path: /languages
 */

defined('ABSPATH') or die('No direct access allowed!');

// Define plugin constants
define('ADMIN_MESSENGER_VERSION', '1.0.0');
define('ADMIN_MESSENGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADMIN_MESSENGER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'AdminMessenger\\';
    $base_dir = ADMIN_MESSENGER_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Load text domain
    load_plugin_textdomain('admin-messenger', false, dirname(plugin_basename(__FILE__)) . '/languages/';
    
    // Initialize components
    AdminMessenger\Database::init();
    AdminMessenger\Messages::init();
    AdminMessenger\AdminPage::init();
    AdminMessenger\Notifications::init();
});

// Activation and deactivation hooks
register_activation_hook(__FILE__, ['AdminMessenger\Database', 'activate']);
register_deactivation_hook(__FILE__, ['AdminMessenger\Database', 'deactivate']);
