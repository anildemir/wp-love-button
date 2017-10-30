<?php

/*
 * This widget adds a list of the top most loved ten tags in the website 
 */

class WP_Love_Button_Widget extends WP_Widget
{

    public function __construct()
    {
        $widget_options = array(
            'classname' => 'wp_love_button_widget',
            'description' => 'A widget for the love button that displays all the tags and their love counts.'
        );
        parent::__construct('wp_love_button_widget', 'WP Love Button Widget', $widget_options);
    }
    
    
    public function widget($args, $instance)
    {

        $tags = wplb_get_tag_loves();

        $tags = array_slice($tags, 0, 10, true);
        
        $html = '<div class="wplb-widget-area"><h2>Tag Loves</h2><ul>';
        foreach ($tags as $term_id => $love_count) {
            
            $tag = get_term_by('id', $term_id, 'post_tag');
            
            $tag_link = get_tag_link($term_id);
            
            $html .= "<li><a href='{$tag_link}'>";
            $html .= "{$tag->name} - <i style='color:red;' class='fa fa-heart'></i> {$love_count}</a></li>";
        }
        $html .= '</ul></div>';
        echo $html;
        
    }
} 