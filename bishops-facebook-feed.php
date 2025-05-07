<?php
/**
 * Plugin Name: Bishops Facebook Feed
 * Description: Custom Facebook feed for Malawi Conference of Catholic Bishops
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Bishops_Facebook_Feed {
    
    // Plugin settings
    private $page_id;
    private $access_token;
    
    public function __construct() {
        // Initialize plugin
        add_action('init', array($this, 'init'));
        
        // Register shortcode
        add_shortcode('bishops_facebook_feed', array($this, 'render_facebook_feed'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function init() {
        // Get settings
        $this->page_id = get_option('bishops_fb_page_id', '');
        $this->access_token = get_option('bishops_fb_access_token', '');
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
    }
    
    public function register_scripts() {
        // Register custom CSS
        wp_register_style(
            'bishops-facebook-feed-css', 
            plugin_dir_url(__FILE__) . 'css/facebook-feed.css',
            array(),
            '1.0'
        );
        
        // Register custom JS
        wp_register_script(
            'bishops-facebook-feed-js',
            plugin_dir_url(__FILE__) . 'js/facebook-feed.js',
            array('jquery'),
            '1.0',
            true
        );
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Bishops Facebook Feed Settings',
            'Bishops FB Feed',
            'manage_options',
            'bishops-facebook-feed',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('bishops_fb_settings', 'bishops_fb_page_id');
        register_setting('bishops_fb_settings', 'bishops_fb_access_token');
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Bishops Facebook Feed Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('bishops_fb_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Facebook Page ID</th>
                        <td>
                            <input type="text" name="bishops_fb_page_id" value="<?php echo esc_attr(get_option('bishops_fb_page_id')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Access Token</th>
                        <td>
                            <input type="text" name="bishops_fb_access_token" value="<?php echo esc_attr(get_option('bishops_fb_access_token')); ?>" class="regular-text" />
                            <p class="description">This should be a long-lived access token. See documentation for instructions.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_facebook_feed($atts) {
        // Enqueue required styles and scripts
        wp_enqueue_style('bishops-facebook-feed-css');
        wp_enqueue_script('bishops-facebook-feed-js');
        
        // Pass variables to JavaScript
        wp_localize_script('bishops-facebook-feed-js', 'bishopsFBSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bishops_fb_nonce')
        ));
        
        // Start output buffering
        ob_start();
        
        // Display feed container
        ?>
        <div class="bishops-feed-container">
            <h2 class="bishops-feed-title">Latest Updates</h2>
            <div class="bishops-feed-row" id="bishops-facebook-posts">
                <div class="bishops-feed-loading">
                    <div class="bishops-feed-spinner"></div>
                    <p>Loading posts...</p>
                </div>
            </div>
        </div>
        <?php
        
        // Return the output
        return ob_get_clean();
    }
    
    public function ajax_get_facebook_posts() {
        // Check nonce for security
        check_ajax_referer('bishops_fb_nonce', 'nonce');
        
        $page_id = $this->page_id;
        $access_token = $this->access_token;
        
        // Build the API endpoint URL
        $endpoint = "https://graph.facebook.com/v19.0/{$page_id}/posts";
        $fields = "id,message,created_time,attachments{media_type,media,title,description,url},likes.summary(true),comments.summary(true)";
        $url = "{$endpoint}?fields={$fields}&access_token={$access_token}&limit=6";
        
        // Make the API request
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Error fetching posts'));
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            wp_send_json_error(array('message' => $data['error']['message']));
            return;
        }
        
        wp_send_json_success($data);
    }
}

// Initialize the plugin
$bishops_facebook_feed = new Bishops_Facebook_Feed();

// Add AJAX action
add_action('wp_ajax_get_facebook_posts', array($bishops_facebook_feed, 'ajax_get_facebook_posts'));
add_action('wp_ajax_nopriv_get_facebook_posts', array($bishops_facebook_feed, 'ajax_get_facebook_posts'));
