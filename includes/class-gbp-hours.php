<?php
/**
 *
 * @link              https://plugins.knowsync.dev
 * @since             1.0.0
 * @package           gbp_Hours
 *
 * @wordpress-plugin
 * Plugin Name:       Business Profile Hours Sync
 * Plugin URI:        https://plugins.knowsync.dev/gbp-hours
 * Description:       Plugin to retrieve business hours using Google Places API and display them on your website. 
 * Version:           1.0.0
 * Author:            KnowSync Plugins
 * Author URI:        https://plugins.knowsync.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       business-profile-hours-sync
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GPBH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GPBH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GPBH_CACHE_TIME', 12 * HOUR_IN_SECONDS); // Cache for 12 hours
define('GPBH_LOG_FILE', WP_CONTENT_DIR . '/gpbh-errors.log');

// Business Hours Widget class
class Business_Hours_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'business_hours_widget',
            'Business Hours',
            array('description' => 'Display your business hours from Google Business Profile')
        );
    }
    
    public function widget($args, $instance) {
        $title = ! empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        
        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        // Display the business hours without title to avoid duplication
        $business_hours = new BusinessProfileHours();
        echo $business_hours->display_business_hours(array('show_title' => false));
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = ! empty($instance['title']) ? $instance['title'] : 'Business Hours';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <small>This title will appear above the business hours in your widget area. Business hours are pulled from your settings in the <a href="<?php echo admin_url('options-general.php?page=business-profile-hours'); ?>">Business Hours</a> options page.</small>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

// Create settings page
class BusinessProfileHours {
    private $api_key;
    private $place_id;
    private $cache_key = 'gpbh_business_hours';
    private $error_log_enabled = true;
    private $display_title;

    public function __construct() {
        // Add actions
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('google_business_hours', array($this, 'display_business_hours'));
        
        // Register widget
        add_action('widgets_init', function() {
            register_widget('Business_Hours_Widget');
        });
        
        // Get API key, Place ID, and Display Title from settings
        $this->api_key = get_option('gpbh_api_key', '');
        $this->place_id = get_option('gpbh_place_id', '');
        $this->display_title = get_option('gpbh_display_title', 'Business Hours');

        // Create error log file if it doesn't exist
        //$this->initialize_error_log();

        // Enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    // Enqueue styles
    public function enqueue_styles() {
        wp_enqueue_style(
            'gpbh-styles',
            GPBH_PLUGIN_URL . 'css/gpbh-styles.css',
            array(),
            '1.3'
        );
    }
/*
    // Initialize error log file
    private function initialize_error_log() {
        if (!file_exists(GPBH_LOG_FILE)) {
            @file_put_contents(GPBH_LOG_FILE, '');
            @chmod(GPBH_LOG_FILE, 0644);
        }
    }

    // Log errors
    private function log_error($message) {
        if (!$this->error_log_enabled) {
            return;
        }

        $timestamp = current_time('mysql');
        $log_entry = "[$timestamp] $message\n";
        
        error_log($log_entry, 3, GPBH_LOG_FILE);
    }
*/
    // Add admin menu
    public function add_admin_menu() {
        add_options_page(
            'Business ProfileHours Settings',
            'Business Hours',
            'manage_options',
            'business-profile-hours',
            array($this, 'create_admin_page') // Fixed method name
        );
    }

    // Register settings
    public function register_settings() {
        register_setting('gpbh_settings_group', 'gpbh_api_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gpbh_settings_group', 'gpbh_place_id', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gpbh_settings_group', 'gpbh_display_title', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Business Hours'
        ));

        add_settings_section(
            'gpbh_settings_section',
            'Google Places API Settings',
            array($this, 'settings_section_callback'),
            'google-places-business-hours'
        );

        add_settings_field(
            'gpbh_api_key',
            'Google Places API Key',
            array($this, 'api_key_callback'),
            'google-places-business-hours',
            'gpbh_settings_section'
        );

        add_settings_field(
            'gpbh_place_id',
            'Place ID',
            array($this, 'place_id_callback'),
            'google-places-business-hours',
            'gpbh_settings_section'
        );

        add_settings_field(
            'gpbh_display_title',
            'Display Title',
            array($this, 'display_title_callback'),
            'google-places-business-hours',
            'gpbh_settings_section'
        );
    }

    // Settings section callback with collapsible instructions
    public function settings_section_callback() {
        ?>
        <p>Enter your Google Places API key and Place ID below. Business hours are cached for 12 hours to reduce API calls.</p>
        
        <details style="margin: 15px 0;">
            <summary style="cursor: pointer; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                <strong>How to Get Your Google Places API Key</strong>
            </summary>
            <div style="padding: 15px; border: 1px solid #ddd; border-radius: 0 0 4px 4px; margin-top: -1px;">
                <ol>
                    <li>Go to the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                    <li>Create a new project or select an existing one</li>
                    <li>Click on "APIs & Services" > "Credentials" in the left sidebar</li>
                    <li>Click "Create Credentials" > "API Key"</li>
                    <li>Copy the generated API key</li>
                    <li>Enable the Places API:
                        <ul>
                            <li>Go to "APIs & Services" > "Library"</li>
                            <li>Search for "Places API"</li>
                            <li>Click "Enable"</li>
                        </ul>
                    </li>
                    <li>Restrict your API key (recommended for security):
                        <ul>
                            <li>Go back to "Credentials"</li>
                            <li>Click on your API key</li>
                            <li>Under "API restrictions", select "Restrict key"</li>
                            <li>Choose "Places API" from the dropdown</li>
                            <li>Under "Application restrictions", consider restricting to your website's domain</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </details>

        <details style="margin: 15px 0;">
            <summary style="cursor: pointer; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                <strong>How to Get Your Place ID</strong>
            </summary>
            <div style="padding: 15px; border: 1px solid #ddd; border-radius: 0 0 4px 4px; margin-top: -1px;">
                <ol>
                    <li>Go to the <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank">Google Place ID Finder</a></li>
                    <li>Search for your business location</li>
                    <li>Click on the map pin for your business</li>
                    <li>Copy the Place ID that appears (it will look like: ChIJN1t_tDeuEmsRUsoyG83frY4)</li>
                    <li>Alternatively, you can:
                        <ul>
                            <li>Go to Google Maps</li>
                            <li>Search for your business</li>
                            <li>Click on the business listing</li>
                            <li>Look for the Place ID in the URL or use browser developer tools</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </details>

        <details style="margin: 15px 0;">
            <summary style="cursor: pointer; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                <strong>Important Notes</strong>
            </summary>
            <div style="padding: 15px; border: 1px solid #ddd; border-radius: 0 0 4px 4px; margin-top: -1px;">
                <ul>
                    <li>Google Places API usage may incur costs. Check Google's pricing for details.</li>
                    <li>Keep your API key secure and don't share it publicly.</li>
                </ul>
            </div>
        </details>
        <?php
    }

    // API Key field callback
    public function api_key_callback() {
        $api_key = get_option('gpbh_api_key', '');
        echo '<input type="text" name="gpbh_api_key" value="' . esc_attr($api_key) . '" size="40">';
        echo '<p class="description">Enter your Google Places API key here.</p>';
    }

    // Place ID field callback
    public function place_id_callback() {
        $place_id = get_option('gpbh_place_id', '');
        echo '<input type="text" name="gpbh_place_id" value="' . esc_attr($place_id) . '" size="40">';
        echo '<p class="description">Enter your business Place ID here.</p>';
    }

    // Display Title field callback
    public function display_title_callback() {
        $display_title = get_option('gpbh_display_title', 'Business Hours');
        echo '<input type="text" name="gpbh_display_title" value="' . esc_attr($display_title) . '" size="40">';
        echo '<p class="description">Enter the title to display above the business hours table (default: "Business Hours").</p>';
    }

    // Create admin page
    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Business Profile Hours Sync Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('gpbh_settings_group');
                do_settings_sections('google-places-business-hours');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use the shortcode <code>[google_business_hours]</code> to display the business hours on any page or post.</p>
            <p>You can also add business hours to any widget area by using the <strong>Business Hours</strong> widget in the Widgets section of your WordPress dashboard.</p>
            <h2>Cache Management</h2>
            <p>Current cache status: 
                <?php
                $cached = get_transient($this->cache_key);
                echo $cached ? 'Active (expires in ' . esc_html(human_time_diff(time(), get_option("_transient_timeout_{$this->cache_key}"))) . ')' : 'Not active';
                ?>
            </p>
            <form method="post" action="">
                <input type="hidden" name="gpbh_clear_cache" value="1">
                <?php wp_nonce_field('gpbh_clear_cache_nonce', 'gpbh_nonce'); ?>
                <input type="submit" class="button" value="Clear Cache">
            </form>
            <?php
            // Handle cache clearing
            if (isset($_POST['gpbh_clear_cache']) && check_admin_referer('gpbh_clear_cache_nonce', 'gpbh_nonce')) {
                delete_transient($this->cache_key);
                echo '<div class="updated"><p>Cache cleared successfully.</p></div>';
            }
            ?>
        </div>
        <?php
    }

    // Get business hours from Google Places API
    private function get_business_hours() {
        // Check cache first
        $cached_hours = get_transient($this->cache_key);
        if ($cached_hours !== false) {
            return $cached_hours;
        }

        if (empty($this->api_key) || empty($this->place_id)) {
            $error = 'API Key or Place ID is missing';
            $this->log_error($error);
            return array('error' => $error);
        }

        $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$this->place_id}&fields=opening_hours&key={$this->api_key}";

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            $error = 'Failed to connect to Google Places API: ' . $response->get_error_message();
            //$this->log_error($error);
            return array('error' => $error);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error = "API request failed with status code: $response_code";
            //$this->log_error($error);
            return array('error' => $error);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Failed to parse API response: ' . json_last_error_msg();
            //$this->log_error($error);
            return array('error' => $error);
        }

        if (isset($data['error_message'])) {
            $error = 'Google Places API error: ' . $data['error_message'];
            //$this->log_error($error);
            return array('error' => $error);
        }

        $hours = isset($data['result']['opening_hours']) ? $data['result']['opening_hours'] : array('error' => 'No opening hours found');
        
        if (!isset($hours['error'])) {
            // Cache the successful response
            set_transient($this->cache_key, $hours, GPBH_CACHE_TIME);
        } else {
            //$this->log_error($hours['error']);
        }

        return $hours;
    }

    // Display business hours using shortcode
    public function display_business_hours($atts = array()) {
        $defaults = array(
            'show_title' => true
        );
        
        $atts = wp_parse_args($atts, $defaults);
        
        $hours = $this->get_business_hours();

        if (isset($hours['error'])) {
            if (current_user_can('manage_options')) {
                return '<p>Error: ' . esc_html($hours['error']) . ' (Visible to admins only)</p>';
            }
            return '<p>Business hours are currently unavailable.</p>';
        }

        if (!isset($hours['weekday_text'])) {
            if (current_user_can('manage_options')) {
                return '<p>No business hours available (Visible to admins only)</p>';
            }
            return '<p>Business hours are currently unavailable.</p>';
        }

        $output = '<div class="gpbh-business-hours">';
        
        // Only show the title if show_title is true
        if ($atts['show_title']) {
            $output .= '<h3>' . esc_html($this->display_title) . '</h3>';
        }
        
        $output .= '<table class="gpbh-hours-table">';
        $output .= '<tbody>';

        foreach ($hours['weekday_text'] as $day) {
            // Split the day and hours (format: "Day: Hours")
            $parts = explode(': ', $day, 2);
            $day_name = isset($parts[0]) ? $parts[0] : '';
            $day_hours = isset($parts[1]) ? $parts[1] : 'Closed';
            
            $output .= '<tr>';
            $output .= '<td>' . esc_html($day_name) . '</td>';
            $output .= '<td>' . esc_html($day_hours) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';

        // Add special hours notice if available
        if (isset($hours['periods']) && !empty($hours['periods'])) {
            $output .= '<p class="gpbh-special-hours"><em>Special hours may apply on holidays.</em></p>';
        }

        $output .= '</div>';

        return $output;
    }
}