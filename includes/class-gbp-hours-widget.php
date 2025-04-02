<?php
/**
 * Business Hours Widget
 *
 * Displays business hours fetched via Google Places API.
 */
class Gbp_Hours_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'gbp_hours_widget', // Base ID
            __('Business Hours', 'business-profile-hours-sync'), // Name
            array('description' => __('Displays business hours from Google Places API', 'business-profile-hours-sync'))
        );
    }

    // Front-end display
    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        // Fetch hours 
        $plugin = new BusinessProfileHours();
        $hours = $plugin->display_business_hours(); 

        if ($hours) {
            echo $hours;
        } else {
            echo '<p>' . __('No hours available.', 'business-profile-hours-sync') . '</p>';
        }

        echo $args['after_widget'];
    }

    // Back-end form
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Business Hours', 'business-profile-hours-sync');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'business-profile-hours-sync'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    // Update widget settings
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

// Register the widget
function register_gbp_hours_widget() {
    register_widget('Gbp_Hours_Widget');
}
add_action('widgets_init', 'register_gbp_hours_widget'); 