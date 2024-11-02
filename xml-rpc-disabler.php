<?php
/*
Plugin Name: XML-RPC Disabler
Plugin URI: https://redmedia.lk
Description: A plugin to disable XML-RPC functionality for enhanced security, with logging options.
Version: 1.0
Author: RED Media Corporation
Author URI: https://dev.redmedia.lk
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.2
License: GPLv2 or later
*/

defined('ABSPATH') or die('No script kiddies please!');

class XML_RPC_Disabler {
    
    public function __construct() {
        add_action('init', array($this, 'disable_xmlrpc'));
        add_action('admin_menu', array($this, 'create_admin_page'));
        add_action('admin_init', array($this, 'process_settings'));
        add_action('admin_init', array($this, 'clear_access_log'));
        
        // Add settings link to plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    public function disable_xmlrpc() {
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'xmlrpc.php') !== false) {
            // Log the access attempt with IP
            $this->log_access_attempt();

            wp_die('XML-RPC functionality has been disabled on this site.', 'XML-RPC Disabled', array('response' => 403));
        }
    }

    private function log_access_attempt() {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $log_entry = sprintf("[%s] XML-RPC access attempt blocked from IP: %s\n", date("Y-m-d H:i:s"), $ip_address);
        file_put_contents(plugin_dir_path(__FILE__) . 'access_log.txt', $log_entry, FILE_APPEND);
    }

    public function create_admin_page() {
        add_options_page('XML-RPC Disabler Settings', 'XML-RPC Disabler', 'manage_options', 'xml-rpc-disabler', array($this, 'admin_page'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>XML-RPC Disabler Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('save_settings', 'xmlrpc_nonce'); ?>
                <h2>Settings</h2>
                <label>
                    <input type="checkbox" name="xmlrpc_status" value="1" <?php checked(1, get_option('xmlrpc_status')); ?> />
                    Disable XML-RPC
                </label>
                <br>
                <input type="submit" name="save_settings" value="Save Settings" class="button button-primary"/>
            </form>
            
            <h2>Access Logs</h2>
            <pre><?php echo esc_html($this->get_access_logs()); ?></pre>
            <form method="post" action="">
                <?php wp_nonce_field('clear_log', 'clear_log_nonce'); ?>
                <input type="submit" name="clear_log" value="Clear Access Log" class="button button-secondary" onclick="return confirm('Are you sure you want to clear the access log?');"/>
            </form>
            <form method="post" action="">
                <input type="submit" name="refresh_log" value="Refresh Access Log" class="button button-secondary"/>
            </form>
        </div>
        <style>
            /* Basic styles for a professional UI */
            .wrap {
                background-color: #f9f9f9;
                border: 1px solid #ccc;
                border-radius: 5px;
                padding: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1, h2 {
                color: #333;
            }
            label {
                display: block;
                margin-bottom: 10px;
                font-weight: bold;
            }
            .button {
                margin-top: 10px;
                background-color: #0073aa;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 3px;
                cursor: pointer;
            }
            .button:hover {
                background-color: #005177;
            }
        </style>
        <?php
    }

    private function get_access_logs() {
        $log_file = plugin_dir_path(__FILE__) . 'access_log.txt';
        if (file_exists($log_file)) {
            return file_get_contents($log_file);
        } else {
            return "No access attempts logged.";
        }
    }

    public function process_settings() {
        if (isset($_POST['save_settings']) && check_admin_referer('save_settings', 'xmlrpc_nonce')) {
            update_option('xmlrpc_status', isset($_POST['xmlrpc_status']) ? 1 : 0);
            // Log save operation
            error_log("XML-RPC settings saved. Status: " . (isset($_POST['xmlrpc_status']) ? 'Disabled' : 'Enabled'));
        }
    }

    public function clear_access_log() {
        if (isset($_POST['clear_log']) && check_admin_referer('clear_log', 'clear_log_nonce')) {
            $log_file = plugin_dir_path(__FILE__) . 'access_log.txt';
            if (file_exists($log_file)) {
                file_put_contents($log_file, ''); // Clear the log file
            }
        }
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=xml-rpc-disabler') . '">Settings</a>';
        $links[] = $settings_link;
        return $links;
    }
}

new XML_RPC_Disabler();
