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
// Register settings for library hours
add_action('admin_init', 'library_hours_register_settings');
function library_hours_register_settings () {
    register_setting('library_hours_options_group', 'default_opening_time');
    register_setting('library_hours_options_group', 'default_closing_time');
}
// Add Library Hours menu page.
add_action('admin_menu','library_hours_menu');
function library_hours_menu () {
    add_options_page('Library Hours Options','Library Hours',
            'manage_options','library_hours_options','library_hours_options_page');
}
function library_hours_options_page () {
    if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
	echo '<h2>Library Hours Options</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields('library_hours_options_group');
        do_settings_sections('library_hours_options_group');
        echo '<p>Default Opening time</p><p><input type="time" name="default_opening_time" value="'. 
                esc_attr(get_option('default_opening_time')) .'" />';
        echo '<p>Default Closing time</p><p><input type="time" name="default_closing_time" value="'. 
                esc_attr(get_option('default_closing_time')) .'" />';
        submit_button();
        echo '</form>';
	echo '</div>';
}

// Add the library hours entry post type.
add_action('init','library_hours_entry');
function library_hours_entry() {
    register_post_type('lib_hours_entry', array(
        'labels' => array(
            'name' => __('Library Hours Entry'),
            'singular' => __('Library Hours Entry'),
        ),
        'public' => false,
        'has_archive' => true,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => array('title'),
        'show_ui' => true,
    ));
}

// Add the library hours exception post type.
add_action('init','library_hours_exception');
function library_hours_exception() {
    register_post_type('lib_hours_exception', array(
        'labels' => array(
            'name' => __('Library Hours Exception'),
            'singular' => __('Library Hours Exception'),
        ),
        'public' => false,
        'has_archive' => true,
        'menu_icon' => 'dashicons-palmtree',
        'supports' => array('title'),
        'show_ui' => true,
    ));
}

// Add a taxonomy for the tabs
add_action('init','library_hours_taxonomy');
function library_hours_taxonomy () {
    register_taxonomy('schedule_tab',array(''), array(
        'hierarchical' => true,
        'label' => 'Tab Name',
        'query_var' => true,
        'rewrite' => true,
        'sort' => true,
    ));
    $default_terms = array('Fall','Spring','Summer');
    foreach($default_terms as $term){
        wp_insert_term($term,'schedule_tab');
    }
}

// Add metabox to select the taxonomy
add_action('add_meta_boxes','schedule_tab_meta_box_init');
function schedule_tab_meta_box_init () {
    add_meta_box('schedule-tab-meta', 'Tab Name','schedule_tab_meta_box',
            'lib_hours_entry','normal','default');
    add_meta_box('schedule-tab-meta', 'Tab Name','schedule_tab_meta_box',
            'lib_hours_exception','normal','default');
}
function schedule_tab_meta_box ($post, $box) {
    $tab_terms = get_terms('schedule_tab','hide_empty=0');
    
    if (!empty($tab_terms) && !is_wp_error($tab_terms)) {
    $post_data = get_post_custom($post->ID);
 
    if(isset($post_data['_schedule_tab_term'][0])) {
      $selected_term = $post_data['_schedule_tab_term'][0];
    }
    echo '<select name="schedule_tab_term" id="tab-term-select">';
    foreach ($tab_terms as $tab) {
        $tab_id = $tab->term_id;
        $tab_name = $tab->name;
    if($tab_name == $selected_term){
    echo '<option value="' . $tab_name . '" selected>' . $tab_name . '</option>';   
    } else {    
    echo '<option value="' . $tab_name . '">' . $tab_name . '</option>';
    }
    
    }
    
    echo '</select>';
    }
//    wp_nonce_field(plugin_basename(__FILE__), 'schedule_tab_save_meta_box');
}

// Saves the metabox contents on form save.
add_action('save_post', 'schedule_tab_save_meta_box');
function schedule_tab_save_meta_box ($post_id) {
    // Check the nonce for security.
//        check_admin_referer(plugin_basename(__FILE__), 'schedule_tab_save_meta_box');

    // Update only if there are values
    if(isset($_POST['schedule_tab_term'])) {
        // Skip save if an autosave is in progress.
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save the meta box
        update_post_meta($post_id, '_schedule_tab_term', 
                sanitize_text_field($_POST['schedule_tab_term']));
    }
   
}

// Add the date range meta box to the library hours post type.
add_action('add_meta_boxes','date_range_meta_box_init');
function date_range_meta_box_init () {
    add_meta_box('date-range-meta', 'Date Range','date_range_meta_box',
            'lib_hours_entry','normal','default');
    add_meta_box('date-range-meta', 'Date Range','date_range_meta_box',
            'lib_hours_exception','normal','default');
}
function date_range_meta_box ($post, $box) {
    $start_date = get_post_meta($post->ID, '_start_date', true);
    $end_date = get_post_meta($post->ID, '_end_date', true);
//    wp_nonce_field(plugin_basename(__FILE__), 'date_range_save_meta_box');
    
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
//        check_admin_referer(plugin_basename(__FILE__), 'date_range_save_meta_box');
        
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
    
    $default_start_time = get_option('default_opening_time');
    $default_end_time = get_option('default_closing_time');
    
    if (isset($default_start_time) && $start_time == "") {
        $start_time = $default_start_time;
    }
    if (isset($default_end_time) && $end_time == "") {
        $end_time = $default_end_time;
    }
    echo '<div class="week-day"><p>' . $day . ' </p> <p> <input type="time" name="weekly_schedule_start_time_' . strtolower($day) . '" value="' . esc_attr($start_time) . '" />' . 
            ' to <input type="time" name="weekly_schedule_end_time_' . strtolower($day) . '" value="' . esc_attr($end_time) . '" />' . 
            '</p></div>';
    }
//    wp_nonce_field(plugin_basename(__FILE__), 'weekly_schedule_save_meta_box');
}

// Saves the metabox contents on form save.
add_action('save_post', 'weekly_schedule_save_meta_box');
function weekly_schedule_save_meta_box ($post_id) {
    $week_days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
    // Check the nonce for security.
//        check_admin_referer(plugin_basename(__FILE__), 'weekly_schedule_save_meta_box');
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

// Add a short code to display the schedule
add_shortcode('weekly_schedule','weekly_schedule');
function weekly_schedule ($atts, $content = null) {
    
    $html = '<div id="tabs">
  <ul>
    <li><a href="#tabs-1">Nunc tincidunt</a></li>
    <li><a href="#tabs-2">Proin dolor</a></li>
    <li><a href="#tabs-3">Aenean lacinia</a></li>
  </ul>
  <div id="tabs-1">' .
  'Test' .
  '</div>
  <div id="tabs-2">
    <p>Morbi tincidunt, dui sit amet facilisis feugiat, odio metus gravida ante, ut pharetra massa metus id nunc. Duis scelerisque molestie turpis. Sed fringilla, massa eget luctus malesuada, metus eros molestie lectus, ut tempus eros massa ut dolor. Aenean aliquet fringilla sem. Suspendisse sed ligula in ligula suscipit aliquam. Praesent in eros vestibulum mi adipiscing adipiscing. Morbi facilisis. Curabitur ornare consequat nunc. Aenean vel metus. Ut posuere viverra nulla. Aliquam erat volutpat. Pellentesque convallis. Maecenas feugiat, tellus pellentesque pretium posuere, felis lorem euismod felis, eu ornare leo nisi vel felis. Mauris consectetur tortor et purus.</p>
  </div>
  <div id="tabs-3">
    <p>Mauris eleifend est et turpis. Duis id erat. Suspendisse potenti. Aliquam vulputate, pede vel vehicula accumsan, mi neque rutrum erat, eu congue orci lorem eget lorem. Vestibulum non ante. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Fusce sodales. Quisque eu urna vel enim commodo pellentesque. Praesent eu risus hendrerit ligula tempus pretium. Curabitur lorem enim, pretium nec, feugiat nec, luctus a, lacus.</p>
    <p>Duis cursus. Maecenas ligula eros, blandit nec, pharetra at, semper at, magna. Nullam ac lacus. Nulla facilisi. Praesent viverra justo vitae neque. Praesent blandit adipiscing velit. Suspendisse potenti. Donec mattis, pede vel pharetra blandit, magna ligula faucibus eros, id euismod lacus dolor eget odio. Nam scelerisque. Donec non libero sed nulla mattis commodo. Ut sagittis. Donec nisi lectus, feugiat porttitor, tempor ac, tempor vitae, pede. Aenean vehicula velit eu tellus interdum rutrum. Maecenas commodo. Pellentesque nec elit. Fusce in lacus. Vivamus a libero vitae lectus hendrerit hendrerit.</p>
  </div>
</div>';
    return $html;
}