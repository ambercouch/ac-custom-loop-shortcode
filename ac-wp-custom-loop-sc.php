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

// Function to build WP_Query arguments with support for multiple terms and exclusion terms
function accls_build_query_args($type, $show, $orderby, $order, $ignore_sticky_posts, $tax, $term, $exclude, $ids) {
    $args = array(
        'post_type' => $type,
        'posts_per_page' => $show,
        'orderby' => $orderby,
        'order' => $order,
        'ignore_sticky_posts' => $ignore_sticky_posts
    );

    // Initialize the tax_query array
    $args['tax_query'] = array('relation' => 'AND');

    // Add included terms if `tax` and `term` are provided
    if (!empty($tax) && !empty($term)) {
        $terms = explode(',', $term); // Split terms by comma
        $args['tax_query'][] = array(
            'taxonomy' => $tax,
            'field' => 'slug',
            'terms' => $terms,
            'operator' => 'AND' // Ensures posts match all terms in the array
        );
    }

    // Add excluded terms if `exclude` is provided
    if (!empty($tax) && !empty($exclude)) {
        $exclude_terms = explode(',', $exclude); // Split exclude terms by comma
        $args['tax_query'][] = array(
            'taxonomy' => $tax,
            'field' => 'slug',
            'terms' => $exclude_terms,
            'operator' => 'NOT IN' // Excludes posts with any of these terms
        );
    }

    // Include specific post IDs if provided
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
    wp_reset_postdata();
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


// Function to handle queries with one or more subtax terms and group by term combinations
function accls_handle_subtax_query($query_args, $subtaxes, $timber, $template, $wrapper, $class) {
    $output = '';
    $subtaxonomies = explode(',', $subtaxes); // Split subtaxonomies by comma
    $grouped_posts = []; // Initialize grouped posts array

    // Get terms for each subtaxonomy
    $terms_by_taxonomy = [];
    foreach ($subtaxonomies as $subtax) {
        $terms = get_terms(array(
            'taxonomy' => $subtax,
            'hide_empty' => true
        ));
        if (!empty($terms) && !is_wp_error($terms)) {
            $terms_by_taxonomy[$subtax] = $terms;
        }
    }

    // Handle single subtax case
    if (count($subtaxonomies) == 1) {
        foreach ($terms_by_taxonomy[$subtaxonomies[0]] as $term) {
            $subtax_query_args = $query_args;
            $subtax_query_args['tax_query'][] = array(
                'taxonomy' => $subtaxonomies[0],
                'field' => 'slug',
                'terms' => $term->slug
            );

            $query = new WP_Query($subtax_query_args);

            if ($query->have_posts()) {
                $grouped_posts[$term->name] = [];
                while ($query->have_posts()) {
                    $query->the_post();
                    $grouped_posts[$term->name][] = get_post(get_the_ID());
                }
            }
            wp_reset_postdata();
        }

    } else {
        // Multiple subtaxonomies case with nested grouping
        foreach ($terms_by_taxonomy[$subtaxonomies[0]] as $term_1) {
            foreach ($terms_by_taxonomy[$subtaxonomies[1]] as $term_2) {
                $subtax_query_args = $query_args;
                $subtax_query_args['tax_query'] = array('relation' => 'AND',
                                                        array(
                                                            'taxonomy' => $subtaxonomies[0],
                                                            'field' => 'slug',
                                                            'terms' => $term_1->slug
                                                        ),
                                                        array(
                                                            'taxonomy' => $subtaxonomies[1],
                                                            'field' => 'slug',
                                                            'terms' => $term_2->slug
                                                        ),
                                                        array(
                                                            'taxonomy' => $query_args['tax_query'][0]['taxonomy'],
                                                            'field' => 'slug',
                                                            'terms' => $query_args['tax_query'][0]['terms']
                                                        )
                );

                $query = new WP_Query($subtax_query_args);

                if ($query->have_posts()) {
                    // Nest posts under [fooTerm][barTerm] structure
                    if (!isset($grouped_posts[$term_1->name])) {
                        $grouped_posts[$term_1->name] = [];
                    }
                    $grouped_posts[$term_1->name][$term_2->name] = [];

                    while ($query->have_posts()) {
                        $query->the_post();
                        $grouped_posts[$term_1->name][$term_2->name][] = get_post(get_the_ID());
                    }
                }
                wp_reset_postdata();
            }
        }
    }

    // Render grouped posts using either Timber or PHP templates
    if ($wrapper == 'true') {
        $output .= '<div class="' . esc_attr($class) . '">';
    }

    if ($timber && class_exists('Timber')) {
        $output .= accls_render_grouped_timber_template($grouped_posts, $template);
    } else {
        $output .= accls_render_grouped_php_template($grouped_posts, $template);
    }

    if ($wrapper == 'true') {
        $output .= '</div>';
    }

    return $output;
}

// Function to render grouped posts using PHP template
function accls_render_grouped_php_template($grouped_posts, $template) {

    error_log(print_r('accls_render_grouped_php_template', true));
    error_log(print_r('grouped_post', true));
    error_log(print_r($grouped_posts, true));
    $output = '';
    ob_start();
    include($template);
    $output .= ob_get_clean();
    wp_reset_postdata();
    return $output;
}

// Function to render grouped posts using Timber template
function accls_render_grouped_timber_template($grouped_posts, $template) {
    $context = Timber::get_context();
    $context['grouped_posts'] = $grouped_posts;
    ob_start();
    Timber::render($template, $context);
    return ob_get_clean();
}

if (!function_exists('ac_wp_custom_loop_short_code')) {

    function ac_wp_custom_loop_short_code($atts) {
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
            'subtax' => '', // New subtax parameter
            'timber' => false,
            'exclude' => '',
            'ids' => ''
        ), $atts));

        error_log("ac_wp_custom_loop_short_code");
        // Validate post type
        if (!accls_valid_post_type($type)) {
            return accls_invalid_post_type_message($type);
        }

        $output = '';
        $template_type = $type;

        // Handle IDs
        if ($ids != '') {
            $ids = explode(',', $ids);
            $type = 'any';
        }

        // Get the template path
        $template = accls_get_template($timber, $template_path, $template_type, $template);

        // Check if the template exists
        if (!file_exists($template)) {
            return '<p>Template not found: ' . $template . '</p>';
        }

        // Get the correct orderby
        $orderby = accls_get_orderby($ids, $type);

        // Enqueue CSS if required
        if ($css == 'true') {
            accls_enqueue_styles();
        }

        // Main Query Arguments
        $query_args = accls_build_query_args($type, $show, $orderby, $order, $ignore_sticky_posts, $tax, $term, $exclude, $ids);

        // If no subtax is provided, use the default query and rendering behavior
        if (empty($subtax)) {
            // Execute the query
            $query = new WP_Query($query_args);

            // Check if there are posts and render accordingly
            if ($query->have_posts()) {
                if ($wrapper == 'true') {
                    $output .= '<div class="' . esc_attr($class) . '">';
                }

                // Use Timber or PHP template rendering
                if ($timber && class_exists('Timber')) {
                    $output .= accls_render_timber_template($query, $template);
                } else {
                    $output .= accls_render_php_template($query, $template);
                }

                if ($wrapper == 'true') {
                    $output .= '</div>';
                }
            }

        } else {
            // If subtax is provided, query the terms and group the results by subtax term
            $output .= accls_handle_subtax_query($query_args, $subtax, $timber, $template, $wrapper, $class);
        }

        return $output;
    }

    add_shortcode('ac_custom_loop', 'ac_wp_custom_loop_short_code');
}
