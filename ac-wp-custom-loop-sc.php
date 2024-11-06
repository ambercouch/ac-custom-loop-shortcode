<?php
/*
  Plugin Name: AC Custom Loop Shortcode
  Plugin URI: https://ambercouch.co.uk
  Description: Shortcode  ( [ac_custom_loop] ) that allows you to easily list post, pages or custom posts with the WordPress content editor or in any widget that supports short code. A typical use would be to show your latest post on your homepage.
  Version: 1.5.2
  Author: AmberCouch
  Author URI: http://ambercouch.co.uk
  Author Email: richard@ambercouch.co.uk
  Text Domain: ac-custom-loop-shortcode
  License: GPL-2.0+
  License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined('ABSPATH') or die('You do not have the required permissions');

function acclsc_get_template($timber, $template_path, $template_type , $template){
    $theme_directory = $template_path;

    $twig_template_folder = false;
    if ($timber !== false) {
        // Get the Timber template directory, defaulting to 'views' or any custom folder set by the user
        $timber_template_dir = is_array(Timber::$dirname) ? Timber::$dirname[0] : Timber::$dirname;
        $twig_template_folder = $theme_directory  . $timber_template_dir . '/';

        // Construct template paths
        $template = (substr($template, -5) === '.twig') ? substr_replace($template, "", -5) : $template;
        $theme_template = $twig_template_folder . $template . '.twig';
        $theme_template_type = $twig_template_folder . $template . '-' . $template_type . '.twig';
        if (file_exists($theme_template_type)){
            $template = $theme_template_type;
        }elseif (file_exists($theme_template ))
        {
            $template = $theme_template;

        }else{
            $template = plugin_dir_path(__FILE__)."views/loop-template.twig";
        }

    } else {
        // For PHP templates
        $template = (substr($template, -4) === '.php') ? substr_replace($template, "", -4) : $template;
        $theme_template = $theme_directory . $template . '.php';
        $theme_template_type = $theme_directory . $template . '-' . $template_type . '.php';
        $plugin_template_type = plugin_dir_path(__FILE__) . $template . '-' . $template_type . '.php';

        if (file_exists($theme_template_type))
        {
            $template = $theme_template_type;

        }elseif (file_exists( $theme_template ))
        {
            $template = $theme_template;
        }elseif (file_exists( $plugin_template_type ))
        {
            $template = $plugin_template_type;
        }else{
            $template = plugin_dir_path(__FILE__)."loop-template.php";
        }
    }
    return $template;
}

function acclsc_get_orderby($ids, $type){

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
function acclsc_valid_post_type($type) {
    $post_types = get_post_types(array('public' => true), 'names');
    return in_array($type, $post_types) || $type == 'any' || $type="tax_terms";
}

// Function to return an error message for invalid post types
function acclsc_invalid_post_type_message($type) {
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
function acclsc_enqueue_styles() {
    $handle = 'ac_wp_custom_loop_styles';
    if (!wp_script_is($handle, 'enqueued')) {
        wp_register_style('ac_wp_custom_loop_styles', plugin_dir_url(__FILE__) . 'assets/css/ac_wp_custom_loop_styles.css', array(), '20181016');
        wp_enqueue_style('ac_wp_custom_loop_styles');
    }
}

// Function to build WP_Query arguments with support for multiple terms and exclusion terms
function acclsc_build_query_args($type, $show, $orderby, $order, $ignore_sticky_posts, $tax, $term, $exclude, $ids) {
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
        $args['post__in'] = $ids;
    }

    return $args;
}

// Function to render PHP template
function acclsc_render_php_template($query, $template) {
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
function acclsc_render_timber_template($query, $template) {
    $context = Timber::get_context();
    $context['posts'] = new Timber\PostQuery($query);
    ob_start();
    Timber::render($template, $context);
    return ob_get_clean();
}


// Function to handle queries with one or more subtax terms and group by term combinations
function acclsc_handle_subtax_query($query_args, $subtaxes, $timber, $template, $wrapper, $class) {
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
        $output .= acclsc_render_grouped_timber_template($grouped_posts, $template);
    } else {
        $output .= acclsc_render_grouped_php_template($grouped_posts, $template);
    }

    if ($wrapper == 'true') {
        $output .= '</div>';
    }

    return $output;
}

// Function to render grouped posts using PHP template
function acclsc_render_grouped_php_template($grouped_posts, $template) {

    $output = '';
    ob_start();
    include($template);
    $output .= ob_get_clean();
    wp_reset_postdata();
    return $output;
}

function acclsc_handle_tax_query($tax, $show, $orderby, $order, $template){
    $args = array(
        'taxonomy' => $tax,
        'number' => intval($show),
        'orderby' => $orderby ?: 'name',
        'order' => $order,
        'hide_empty' => false
    );

    $terms = get_terms($args);

    if (is_wp_error($terms) || empty($terms)) {
        return '<p><strong>No terms found in taxonomy:</strong> ' . esc_html($tax) . '</p>';
    }

    return $terms;


}

// Function to render grouped posts using Timber template
function acclsc_render_grouped_timber_template($grouped_posts, $template) {
    $context = Timber::get_context();
    $context['grouped_posts'] = $grouped_posts;
    ob_start();
    Timber::render($template, $context);
    return ob_get_clean();
}

function acclsc_render_terms_timber_template( $term, $template){
    $context = Timber::get_context();
    $context['terms'][] = $term;
    ob_start();
    Timber::render($template, $context);
    return ob_get_clean();
}

function acclsc_render_terms_php_template( $term, $template){
    $output = '';
    ob_start();
    include($template);
    $output .= ob_get_clean();
    wp_reset_postdata();
    return $output;
}

if (!function_exists('acclsc_sc')) {

    function acclsc_sc($atts) {
        extract(shortcode_atts(array(
            'type' => 'post',
            'collections' => false,
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

        // Validate post type
        if (!acclsc_valid_post_type($type)) {
            return acclsc_invalid_post_type_message($type);
        }

        $output = '';
        $template_type = $type;

        if ($collections != false || $type == 'tax_terms' ) {
            if (empty($tax)) {
                return '<p><strong>Error:</strong> You must specify a taxonomy using the "tax" attribute when using "type=\'tax_terms\'".</p>';
            }
            $template_type = 'tax-'.$tax;
            //if we want all terms we need to use 0 instead of -1
            $show = ($show == '-1') ? 0 : $show;
        }

        // Handle IDs
        if ($ids != '') {
            $ids = explode(',', $ids);
            $type = 'any';
        }

        // Get the template path
        $template = acclsc_get_template($timber, $template_path, $template_type, $template);

        // Check if the template exists
        if (!file_exists($template)) {
            return '<p>Template not found: ' . $template . '</p>';
        }

        // Get the correct orderby
        $orderby = acclsc_get_orderby($ids, $type);

        // Enqueue CSS if required
        if ($css == 'true') {
            acclsc_enqueue_styles();
        }




        // Main Query Arguments
        $query_args = acclsc_build_query_args($type, $show, $orderby, $order, $ignore_sticky_posts, $tax, $term, $exclude, $ids);

        // If no subtax is provided, use the default query and rendering behavior
        if ($collections != false || $type == 'tax_terms' )
        {
            $terms = acclsc_handle_tax_query($tax, $show, $orderby, $order, $template);

            if (!empty($terms))
            {
                if ($wrapper == 'true')
                {
                    $output .= '<div class="' . esc_attr($class) . '">';
                }

                if ($timber && class_exists('Timber'))
                {
                    foreach ($terms as $term){
                        $output .= acclsc_render_terms_timber_template($term, $template);
                    }
                } else
                {
                    foreach ($terms as $term){
                        $output .= acclsc_render_terms_php_template($term, $template);                    }

                }

                if ($wrapper == 'true')
                {
                    $output .= '</div>';
                }
            }


        } elseif (empty($subtax)) {
            // Execute the query
            $query = new WP_Query($query_args);

            // Check if there are posts and render accordingly
            if ($query->have_posts()) {
                if ($wrapper == 'true') {
                    $output .= '<div class="' . esc_attr($class) . '">';
                }

                // Use Timber or PHP template rendering
                if ($timber && class_exists('Timber')) {
                    $output .= acclsc_render_timber_template($query, $template);
                } else {
                    $output .= acclsc_render_php_template($query, $template);
                }

                if ($wrapper == 'true') {
                    $output .= '</div>';
                }
            }

        } else {
            // If subtax is provided, query the terms and group the results by subtax term
            $output .= acclsc_handle_subtax_query($query_args, $subtax, $timber, $template, $wrapper, $class);
        }

        return $output;
    }

    add_shortcode('ac_custom_loop', 'acclsc_sc');
}
