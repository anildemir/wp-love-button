<?php
/*
Plugin Name:  WP Love Button
Plugin URI:   https://github.com/anildemir/wp-love-button
Description:  Adds a simple love button to each post.  
Version:      1.0.0
Author:       Anıl DEMİR
License:      GPLv3
License URI:  http://www.gnu.org/licenses/gpl-3.0.html

WP Love Button is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

WP Love Button is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with WP Love Button. If not, see http://www.gnu.org/licenses/gpl-3.0.html.
*/

/*
 * Including files for the widget and the shortcode
 */

include(plugin_dir_path(__FILE__) . 'wp-love-button-widget.php');
include(plugin_dir_path(__FILE__) . 'wp-love-button-shortcode.php');

/* 
 * Register and include the stylesheets and scripts
 */

function wplb_styles()
{
    wp_register_style("wp-love-button-style-file", plugin_dir_url(__FILE__) . "/css/wp-love-button.css");
    wp_enqueue_style("wp-love-button-style-file");
    
    wp_register_style("love-font-awesome", "//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css");
    wp_enqueue_style("love-font-awesome");
}

function wplb_scripts()
{
    wp_register_script("wp-love-button-script-file", plugin_dir_url(__FILE__) . "/js/wp-love-button.js", array(
        'jquery'
    ));
    wp_enqueue_script("wp-love-button-script-file");
    wp_localize_script('wp-love-button-script-file', 'ajax_var', array(
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax-nonce')
    ));
}

add_action("wp_enqueue_scripts", "wplb_styles");
add_action("wp_enqueue_scripts", "wplb_scripts");

/*
 * Setting up a PHP function to handle Ajax
 * Currently, the plugin does not support loves from anonymous users
 */

// add_action('wp_ajax_nopriv_love_post', 'love_post'); // For non-logged-in users
add_action('wp_ajax_wplb_love_post', 'wplb_love_post'); // For logged in users

/*
 * Love/Unlove a post
 * Hooked into wp_ajax_ above to save post IDs when button clicked.
 */

function wplb_love_post()
{
    
    // Security measures for the ajax call
    
    $nonce = $_POST['_wpnonce'];
    if (!wp_verify_nonce($nonce, 'ajax-nonce'))
        die("Security check has not passed.");
    
    if (isset($_POST['wplb_love_post'])) {
        
        // Get the post ID of the clicked button's post and the current user's ID
        
        $post_id = $_POST['post_id'];
        $user_id = get_current_user_id();
        
        // Get the tags associated to the post and their love counts of the post associa

        $tag_love_counts = wplb_get_tag_loves($post_id);
        
        // Get the count of loves for the particular post
        
        $post_love_count = get_post_meta($post_id, "_love_count", true);
        
        // Get the users who loved the post
        
        $postmetadata_userIDs = get_post_meta($post_id, "_user_loved");
        
        $users_loved = array();
        
        if (count($postmetadata_userIDs) != 0) {
            $users_loved = $postmetadata_userIDs[0];
        }
        
        if (!is_array($users_loved))
            $users_loved = array();
        
        $users_loved['User_ID-' . $user_id] = $user_id;
        
        if (!wplb_already_loved($post_id)) {
            
            // Love
            
            update_post_meta($post_id, "_user_loved", $users_loved);
            update_post_meta($post_id, "_love_count", ++$post_love_count);
            
            foreach ($tag_love_counts as $key => $value) {
                update_term_meta($key, "_love_count", ++$value);
            }
            $response['count']   = $post_love_count;
            $response['message'] = "You loved this! " . '<i class="fa fa-heart"></i>';
        }
        
        else {
            
            // Unlove
            
            $uid_key = array_search($user_id, $loved_users); // find the key
            unset($loved_users[$uid_key]); // remove from array
            
            update_post_meta($post_id, "_user_loved", $loved_users); // Remove user ID from post meta
            update_post_meta($post_id, "_love_count", --$post_love_count); // -1 count post meta
            
            foreach ($tag_love_counts as $key => $value) {
                
                update_term_meta($key, "_love_count", --$value);
                
            }
            $response['count']   = $post_love_count;
            $response['message'] = "Love this! " . '<i class="fa fa-heart"></i>';
      
        }
        
        wp_send_json($response);
    }
}

/*
/ Function to display the love button on the front-end below every post
*/

function wplb_display_love_button($post_id)
{
    // Total counts for the post
    
    $love_count = get_post_meta($post_id, "_love_count", true);
    $count      = (empty($love_count) || $love_count == "0") ? ' Nobody has loved this yet.' : $love_count;
    
    // Prepare button html

    if (!wplb_already_loved($post_id)) {
        $html = '<div class="wplb-wrapper"><a href="#" data-post_id="' . $post_id . '">Love this! <i class="fa fa-heart"></i></a><span>' . $count . ' </span></div>';
    } else {
        $html = '<div class="wplb-wrapper wplb-loved"><a href="#" data-post_id="' . $post_id . '">You loved this! <i class="fa fa-heart"></i></a><span> ' . $count . '</span></div>';
    }
    
    return $html;
}

/* 
 *   Adding the button to each post if the user is logged in
 *   and the post type is "post"
 */

function wplb_add_love_button($content)
{
    return (is_user_logged_in() && get_post_type() == post) ? $content . wplb_display_love_button(get_the_ID()) : $content;
}

add_filter("the_content", "wplb_add_love_button");

/* 
 * Function to check whether the user who clicks the love button already loved the post
 */

function wplb_already_loved($post_id)
{

    $user_id              = get_current_user_id();
    $postmetadata_userIDs = get_post_meta($post_id, "_user_loved");
    $users_loved          = array();
    
    if (count($postmetadata_userIDs) != 0) {
        $users_loved = $postmetadata_userIDs[0];
    }
    if (!is_array($users_loved))
        $users_loved = array();
    
    if (in_array($user_id, $users_loved)) {
        return true;
    } else {
        return false;
    }
}


/*
* When a tag is added to or removed from a post
* These functions update the tag loves according to their current post's loves
*/

function wplb_update_after_tag_add($object_id, $tt_id, $taxonomy)
{
    if ($taxonomy == 'post_tag') {
        $post_love_count = get_post_meta($object_id, "_love_count", true);
        $tag_love_count = get_term_meta($tt_id, "_love_count", true);
        $tag_love_count += $post_love_count;
        update_term_meta($tt_id, "_love_count", $tag_love_count);
    }
}

function wplb_update_after_tag_remove($object_id, $tt_id, $taxonomy)
{
    if ($taxonomy == 'post_tag') {
        $post_love_count = get_post_meta($object_id, "_love_count", true);
        $tag_love_count = get_term_meta($tt_id, "_love_count", true);
        $tag_love_count -= $post_love_count;
        update_term_meta($tt_id, "_love_count", $tag_love_count);
    }
}

add_action('added_term_relationship', 'wplb_update_after_tag_add', 10, 3);
add_action('delete_term_relationships', 'wplb_update_after_tag_remove', 10, 3);

/*
 * Registering the shortcode
 */ 

add_shortcode('wplb-tag-loves', 'wplb_tag_loves_shortcode');

/* 
 * Registering the widget
 */

function wplb_register_widgets()
{
    register_widget('WP_Love_Button_Widget');
}

add_action('widgets_init', 'wplb_register_widgets');

/* 
 * The function that returns an array of tags and their love counts
 * When the function is called without parameters
 * It returns all the tags with their love counts
 * Else it gets all the tags associated to the given post 
 */

function wplb_get_tag_loves($post_id = -1)
{
    
    $tags = ($post_id == -1) ? get_tags() : wp_get_post_tags($post_id);
    
    $tag_love_counts = array();
    
    foreach ($tags as $tag) {
        
        $love_count = get_term_meta($tag->term_id, "_love_count", true);
        if (!$love_count)
            $love_count = 0;
        $tag_love_counts[$tag->term_id] = $love_count;
        
    }
    arsort($tag_love_counts); // Descending order, most loved at the top
    return $tag_love_counts;
    
}