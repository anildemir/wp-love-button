<?php

/*
 * This shortcode adds a list of all the tags 
 * and their love counts to a page in descending order. 
 */

function wplb_tag_loves_shortcode()
{
    
    // Register shortcode specific script file  
    
    wp_enqueue_script('wp-love-button-shortcode', plugin_dir_url(__FILE__) . "/js/wp-love-button-shortcode.js");
    
    // Get all the tags and their love counts
    
    $tags = wplb_get_tag_loves();
    
    // Prepare html for the short code
    
    $html = '<table id="wplb-tags-loves"><tr><th>Tags</th><th>Loves</th></tr>';
    
    foreach ($tags as $term_id => $love_count) {
        
        // Get the tag by ID
        
        $tag = get_term_by('id', $term_id, 'post_tag');
        
        // Display tag name and love count
        
        $html .= '<tr>';
        $html .= '<td>' . $tag->name . '</td><td><i style="color:red;" class="fa fa-heart"></i> ' . $love_count . '</td></tr>';
        
    }
    
    $html .= '</table>';
    
    return $html;
    
}