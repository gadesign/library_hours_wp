<?php
/*
Plugin Name: Library Hours
Plugin URI: http://www.goodanswerdesign.net
Description: Displays the library schedule, and today's hours.
Author: Jeremy Smith
Author URI: http://www.goodanswerdesign.net
License: GPL3
*/

/*
    Copyright (C) 2015 Jeremy Smith (email: goodanswerdesign@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Add the library hours post type.
add_action('init','library_hours_entry');
function library_hours_entry() {
    register_post_type('lib_hours_entry', array(
        'labels' => array(
            'name' => __('Library Hours'),
            'singular' => __('Library Hours Entry'),
        ),
        'public' => false,
        'has_archive' => true,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => array('title'),
        'show_ui' => true,
    ));
}



// Add the date range meta box to the library hours post type.
add_action('add_meta_boxes','date_range_meta_box_init');
function date_range_meta_box_init () {
    add_meta_box('date-range-meta', 'Date Range','date_range_meta_box',
            'lib_hours_entry','normal','default');
}
function date_range_meta_box ($post, $box) {
    $start_date = get_post_meta($post->ID, '_start_date', true);
    $end_date = get_post_meta($post->ID, '_end_date', true);
    wp_nonce_field(plugin_basename(__FILE__), 'date_range_save_meta_box');
    
    echo '<p><input type="date" name="date_range_start_date" value="' . esc_attr($start_date) . '" />' . 
            ' to <input type="date" name="date_range_end_date" value="' . esc_attr($end_date) . '" />' . 
            '</p>';
}

// Saves the metabox contents on form save.
add_action('save_post', 'date_range_save_meta_box');
function date_range_save_meta_box ($post_id) {
    
    // Update only if there are values
    if(isset($_POST['date_range_start_date'])) {
        // Skip save if an autosave is in progress.
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the nonce for security.
        check_admin_referer(plugin_basename(__FILE__), 'date_range_save_meta_box');
        
        // Save the meta box
        update_post_meta($post_id, '_start_date', 
                sanitize_text_field($_POST['date_range_start_date']));
        update_post_meta($post_id, '_end_date', 
                sanitize_text_field($_POST['date_range_end_date']));
    }
}


// Add the date range meta box to the library hours post type.
add_action('add_meta_boxes','weekly_schedule_meta_box_init');
function weekly_schedule_meta_box_init () {
    add_meta_box('weekly-schedule-meta', 'Weekly Schedule','weekly_schedule_meta_box',
            'lib_hours_entry','normal','default');
}
function weekly_schedule_meta_box ($post, $box) {
    $week_days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
    foreach ($week_days as $day) {
    $start_time = get_post_meta($post->ID, '_weekly_schedule_start_time_'. strtolower($day), true);
    $end_time = get_post_meta($post->ID, '_weekly_schedule_end_time_'.strtolower($day), true);
    
    
    echo '<div class="week-day"><p>' . $day . ' </p> <p> <input type="time" name="weekly_schedule_start_time_' . strtolower($day) . '" value="' . esc_attr($start_time) . '" />' . 
            ' to <input type="time" name="weekly_schedule_end_time_' . strtolower($day) . '" value="' . esc_attr($end_time) . '" />' . 
            '</p></div>';
    }
    wp_nonce_field(plugin_basename(__FILE__), 'weekly_schedule_save_meta_box');
}

// Saves the metabox contents on form save.
add_action('save_post', 'weekly_schedule_save_meta_box');
function weekly_schedule_save_meta_box ($post_id) {
    $week_days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
    // Check the nonce for security.
        check_admin_referer(plugin_basename(__FILE__), 'weekly_schedule_save_meta_box');
    foreach ($week_days as $day) {  
    // Update only if there are values
    if(isset($_POST['weekly_schedule_start_time_'. strtolower($day)]) && isset($_POST['weekly_schedule_end_time_'. strtolower($day)])) {
        // Skip save if an autosave is in progress.
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save the meta box
        update_post_meta($post_id, '_weekly_schedule_start_time_'. strtolower($day), 
                sanitize_text_field($_POST['weekly_schedule_start_time_'. strtolower($day)]));
        update_post_meta($post_id, '_weekly_schedule_end_time_'. strtolower($day), 
                sanitize_text_field($_POST['weekly_schedule_end_time_'. strtolower($day)]));
    }
    }
}