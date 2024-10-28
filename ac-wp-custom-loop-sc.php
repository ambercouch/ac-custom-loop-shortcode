<?php
/*
  Plugin Name: AC Custom Loop Shortcode
  Plugin URI: https://github.com/ambercouch/ac-wp-custom-loop-shortcode
  Description: Shortcode  ( [ac_custom_loop] ) that allows you to easily list post, pages or custom posts with the WordPress content editor or in any widget that supports short code. A typical use would be to show your latest post on your homepage.
  Version: 1.5
  Author: AmberCouch
  Author URI: http://ambercouch.co.uk
  Author Email: richard@ambercouch.co.uk
  Text Domain: ac-wp-custom-loop-shortcode
  Domain Path: /lang/
  License:
  Copyright 2018 AmberCouch
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

use Timber\PostQuery;

defined('ABSPATH') or die('You do not have the required permissions');

function accls_get_template($timber, $template_path, $template_type , $template){
    $theme_directory = $template_path;

    $twig_template_folder = false;
    if ($timber != false){
        $twig_template_folder = $theme_directory . 'templates/';
        $template = (substr($template, -5) === '.twig') ? substr_replace($template ,"",-5) :  $template;
        $theme_template = $template . '.twig';
        $theme_template_type = $template . '-' . $template_type . '.twig';
    }else{

        //$theme_extention = (substr($template, -4) === '.php' || substr($template, -5) === '.twig' ) ? '' : '.php';
        $template = (substr($template, -4) === '.php') ? substr_replace($template ,"",-4) :  $template;
        $theme_template = $theme_directory . $template . '.php';
        $theme_template_type = $theme_directory . $template . '-' . $template_type . '.php';
    }
    if($timber != false){

        if (file_exists($twig_template_folder.$theme_template_type))
        {
            $template = $theme_template_type;

        }elseif (file_exists($twig_template_folder.$theme_template ))
        {
            $template = $theme_template;
        }else{
            $template = "loop-template.twig";
        }
    }else{

        if (file_exists($theme_template_type))
        {
            $template = $theme_template_type;

        }elseif (file_exists( $theme_template ))
        {
            $template = $theme_template;
        }else{
            $template = "loop-template.php";
        }
    }
    return $template;
}

function accls_get_orderby($ids, $type){

    if($ids){
        $orderby = 'post__in';
    }
    elseif ($type == 'post')
    {
        $orderby = 'date';
    }
    else
    {
        $orderby = 'menu_order';
    }

    return $orderby;

}

// Function to validate the post type
function accls_valid_post_type($type) {
    $post_types = get_post_types(array('public' => true), 'names');
    return in_array($type, $post_types) || $type == 'any';
}

// Function to return an error message for invalid post types
function accls_invalid_post_type_message($type) {
    $post_types = get_post_types(array('public' => true), 'names');
    $output = '<p><strong>' . $type . '</strong> ' . __('is not a public post type on this website.') . '</p>';
    $output .= '<ul>';
    foreach ($post_types as $cpt) {
        $output .= '<li>' . $cpt . '</li>';
    }
    $output .= '</ul>';
    $output .= '<p>';
    $output .= __('Please edit the short code to use one of the available post types.', 'ac-wp-custom-loop-shortcode');
    $output .= '</p>';
    $output .= '<code>[ ac_custom_loop type="post" show="4"]</code>';
    return $output;
}

// Function to enqueue CSS
function accls_enqueue_styles() {
    $handle = 'ac_wp_custom_loop_styles';
    if (!wp_script_is($handle, 'enqueued')) {
        wp_register_style('ac_wp_custom_loop_styles', plugin_dir_url(__FILE__) . 'assets/css/ac_wp_custom_loop_styles.css', array(), '20181016');
        wp_enqueue_style('ac_wp_custom_loop_styles');
    }
}

// Function to build WP_Query arguments
function accls_build_query_args($type, $show, $orderby, $order, $ignore_sticky_posts, $tax, $term, $ids) {
    $args = array(
        'post_type' => $type,
        'posts_per_page' => $show,
        'orderby' => $orderby,
        'order' => $order,
        'ignore_sticky_posts' => $ignore_sticky_posts
    );

    if (!empty($tax) && !empty($term)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => $tax,
                'field' => 'slug',
                'terms' => $term
            )
        );
    }

    if (!empty($ids)) {
        $args['post__in'] = explode(',', $ids);
    }

    return $args;
}

// Function to render PHP template
function accls_render_php_template($query, $template) {
    $output = '';
    while ($query->have_posts()) {
        $query->the_post();
        ob_start();
        include($template);
        $output .= ob_get_clean();
    }
    return $output;
}

// Function to render Timber template
function accls_render_timber_template($query, $template) {
    $context = Timber::get_context();
    $context['posts'] = new Timber\PostQuery($query);
    ob_start();
    Timber::render($template, $context);
    return ob_get_clean();
}
if (!function_exists('ac_wp_custom_loop_short_code'))
{

    function ac_wp_custom_loop_short_code($atts)
    {
        extract(shortcode_atts(array(
            'type' => 'post',
            'show' => '-1',
            'template_path' => get_stylesheet_directory() . '/',
            'template' => 'loop-template',
            'css' => 'true',
            'wrapper' => 'true',
            'ignore_sticky_posts' => 1,
            'orderby' => '',
            'order' => 'DESC',
            'class' => 'c-accl-post-list',
            'tax' => '',
            'term' => '',
            'timber' => false,
            'ids' => ''

        ), $atts));

        if (!accls_valid_post_type($type)) {
            return accls_invalid_post_type_message($type);
        }

        $output = '';

        $template_type = $type;

        if($ids != ''){
            $ids = explode(',', $ids);
            $type = 'any';
        }

        $template = accls_get_template($timber, $template_path, $template_type , $template);

        // Debug: Check if the template exists
        if (!file_exists($template)) {
            return '<p>Template not found: ' . $template . '</p>';
        }

        $orderby = accls_get_orderby($ids, $type);

        // Enqueue CSS if needed
        if ($css == 'true') {
            accls_enqueue_styles();
        }

        // Build WP_Query arguments
        $query_args = accls_build_query_args($type, $show, $orderby, $order, $ignore_sticky_posts, $tax, $term, $ids);

        // Execute the query
        $query = new WP_Query($query_args);


        if ($query->have_posts()) :
            $output .= ($wrapper == 'true') ? '<div class="'.$class.'" >' : '';

            if ($timber && class_exists('Timber')) {
                // Use Timber for rendering if it's enabled and available
                $output .= accls_render_timber_template($query, $template);
            } else {
                // Use PHP template rendering
                $output .= accls_render_php_template($query, $template);
            }

            $output .= ($wrapper == 'true') ? '</div>' : '';
        endif;

        return $output;

    }

    add_shortcode('ac_custom_loop', 'ac_wp_custom_loop_short_code');

}
