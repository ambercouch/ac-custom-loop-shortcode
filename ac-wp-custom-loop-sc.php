<?php
/*
  Plugin Name: AC Custom Loop Shortcode
  Plugin URI: https://ambercouch.co.uk
  Description: Shortcode  ( [ac_custom_loop] ) that allows you to easily list post, pages or custom posts with the WordPress content editor or in any widget that supports short code. A typical use would be to show your latest post on your homepage.
  Version: 1.6.1
  Author: AmberCouch
  Author URI: http://ambercouch.co.uk
  Author Email: richard@ambercouch.co.uk
  Text Domain: ac-custom-loop-shortcode
  License: GPL-2.0+
  License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined('ABSPATH') or die('You do not have the required permissions');

/*
 * Loop setup function
 */

function acclsc_get_template($timber, $template_path, $template_type , $template){
    $timber_template_dir = '';
    if ($timber !== false)
    {
        $timber_template_dir = is_array(Timber::$dirname) ? Timber::$dirname[0] : Timber::$dirname;
    }
    $theme_directory = ($timber !== false) ? $template_path . $timber_template_dir . '/' : $template_path;

    $plugin_directory = ($timber !== false) ? plugin_dir_path(__FILE__). $timber_template_dir . '/' : plugin_dir_path(__FILE__);
    $file_ext = ($timber !== false) ? '.twig' : '.php';
    $file_ext_len = strlen($file_ext);

    $template = (substr($template, -$file_ext_len ) === $file_ext ) ? substr_replace($template, "", -$file_ext_len) : $template;
    $theme_template = $theme_directory  . $template . $file_ext;
    if (is_array($template_type)){
        $theme_template_type = $theme_directory  . $template . '-' . $template_type[0]. '-' . $template_type[1] . $file_ext;
        $plugin_template_type = $plugin_directory . $template . '-' . $template_type[0]. '-' . $template_type[1] . $file_ext;
        $theme_template_tax = $theme_directory  . $template . '-tax' . $file_ext;
        $plugin_template_tax = $plugin_directory . $template . '-tax' . $file_ext;
    }else{
        $theme_template_type = $theme_directory  . $template . '-' . $template_type . $file_ext;
        $plugin_template_type = $plugin_directory . $template . '-' . $template_type . $file_ext;
        $theme_template_tax = '';
        $plugin_template_tax = '';
    }


    if (file_exists($theme_template_type))
    {
        $template = $theme_template_type;
    }elseif (file_exists( $theme_template_tax ))
    {
        $template = $theme_template_tax;
    }elseif (file_exists( $theme_template ))
    {
        $template = $theme_template;
    }elseif (file_exists( $plugin_template_type ))
    {
        $template = $plugin_template_type;
    }elseif (file_exists( $plugin_template_tax ))
    {
        $template = $plugin_template_tax;
    }else{
        $template = $plugin_directory."loop-template" . $file_ext;
    }

    return $template;
}

function acclsc_get_orderby($ids, $type) {

    if ($ids) {
        $orderby = 'post__in';
    } elseif ($type == 'post') {
        $orderby = 'date';
    } elseif (strpos($type, ',') !== false) { // Check for multiple post types (comma-separated)
        $orderby = 'date';
    } else {
        $orderby = 'menu_order'; // Use menu_order for a single custom post type
    }

    return $orderby;
}

// Function to validate the post type
function acclsc_valid_post_type($type) {
    $post_types = get_post_types(array('public' => true), 'names');
    return in_array($type, $post_types) || $type == 'any' || $type="tax_term";
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

        $style_path = plugin_dir_path(__FILE__) . 'assets/css/ac_wp_custom_loop_styles.css';
        $style_version = file_exists($style_path) ? filemtime($style_path) : '1.0';

        wp_register_style(
            'ac_wp_custom_loop_styles',
            plugin_dir_url(__FILE__) . 'assets/css/ac_wp_custom_loop_styles.css',
            array(),
            $style_version
        );

        wp_enqueue_style('ac_wp_custom_loop_styles');
    }
}

/*
 * Loop query functions
 */

// Function to build WP_Query arguments with support for multiple terms and exclusion terms
function acclsc_build_query_args($type, $show, $orderby, $order, $ignore_sticky_posts, $tax, $term, $exclude, $ids, $show_pagination) {

    $paged = ($show_pagination) ? (get_query_var('paged')) ? get_query_var('paged') : 1 : 1;

    // Convert `$type` to an array if it contains multiple post types
    $post_types = explode(',', $type);

    $args = array(
        'post_type' => (count($post_types) > 1) ? $post_types : $post_types[0], // Use array if multiple types
        'posts_per_page' => $show,
        'orderby' => $orderby,
        'order' => $order,
        'ignore_sticky_posts' => $ignore_sticky_posts,
        'paged' => $paged
    );

    // Initialize the tax_query array
    $args['tax_query'] = array('relation' => 'AND');

    // Add included terms if `tax` and `term` are provided
    if (!empty($tax) && !empty($term)) {
        $terms = explode(',', $term); // Split terms by comma
        $args['tax_query'][] = array(
            'taxonomy' => $tax,
            'field' => 'slug', // Use slug to match categories
            'terms' => $terms,
            'operator' => 'IN' // Ensures posts match any term in the array
        );
    }

    // Add excluded terms if `exclude` is provided
    if (!empty($tax) && !empty($exclude)) {
        $exclude_terms = explode(',', $exclude); // Split exclude terms by comma
        $args['tax_query'][] = array(
            'taxonomy' => $tax,
            'field' => 'slug', // Use slug to match categories
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

    return $grouped_posts;

}

// Function to handle the taxonomy query
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

/*
 * Loop template functions
 */

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

// Function to render grouped posts using PHP template
function acclsc_render_grouped_php_template($grouped_posts, $template) {

    $output = '';
    ob_start();
    include($template);
    $output .= ob_get_clean();
    wp_reset_postdata();
    return $output;
}

// Function to render grouped posts using Timber template
function acclsc_render_grouped_timber_template($grouped_posts, $template) {
    $context = Timber::get_context();
    $context['grouped_posts'] = $grouped_posts;
    ob_start();
    Timber::render($template, $context);
    return ob_get_clean();
}

// Function to render the taxonomy terms timber template
function acclsc_render_terms_timber_template( $term, $template){
    $context = Timber::get_context();
    $context['terms'][] = new \Timber\Term($term->term_id);
    ob_start();
    Timber::render($template, $context);
    return ob_get_clean();
}

// Function to render the taxonomy terms php template
function acclsc_render_terms_php_template( $term, $template){
    $output = '';
    ob_start();
    include($template);
    $output .= ob_get_clean();
    wp_reset_postdata();
    return $output;
}


/*
 * Custom post loop shortcode function
 */

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
            'ids' => '',
            'paged' => '',
            'show_pagination' => false
        ), $atts));

        // Determine if pagination should be shown
        // If the it is paged show the pagination unless $show_pagination is set to the string false
        $show_pagination = ($show_pagination === 'false') ? false : $paged;

        // Validate post type
        if (!acclsc_valid_post_type($type)) {
            return acclsc_invalid_post_type_message($type);
        }

        // Check if Timber is requested and available
        if ($timber !== false) {
            if (!class_exists('Timber')) {
                return '<p><strong>Timber plugin is not active.</strong> Please install and activate the Timber plugin to use Twig templates with this shortcode.</p>';
            } else {
                $timber = true;
            }
        }

        // init the output var
        $output = '';

        //set the template type
        $template_type = $type;

        // Determine if we are taxonomy terms instead of posts
        if ($collections != false || $type == 'tax_term' ) {
            if (empty($tax)) {
                return '<p><strong>Error:</strong> You must specify a taxonomy using the "tax" attribute when using "type=\'tax_term\'".</p>';
            }

            //update the template type
            $template_type = ['tax',$tax];

            //update the $show var when querying taxonomy terms
            //if we want all terms we need to use 0 instead of -1
            $show = ($show == '-1') ? 0 : $show;
        }

        // Determine if we are getting specific IDs
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
        $orderby = ($orderby) ? $orderby : acclsc_get_orderby($ids, $type);

        // Enqueue CSS if required
        if ($css == 'true') {
            acclsc_enqueue_styles();
        }

        if ($wrapper == 'true')
        {
            $output .= '<div class="' . esc_attr($class) . '">';
        }

        // Determine if we are getting taxonomy terms, a simple list of post, or post grouped by taxonomy terms.
        if ($collections != false || $type == 'tax_term' )
        {
            $terms = acclsc_handle_tax_query($tax, $show, $orderby, $order, $template);

            if (!empty($terms))
            {
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
            }

        } elseif (empty($subtax)) {

            // Main Query Arguments
            $query_args = acclsc_build_query_args($type, $show, $orderby, $order, $ignore_sticky_posts, $tax, $term, $exclude, $ids, $show_pagination);

            // Execute the query
            $query = new WP_Query($query_args);

            // Check if there are posts and render accordingly
            if ($query->have_posts()) {


                // Use Timber or PHP template rendering
                if ($timber && class_exists('Timber')) {
                    $output .= acclsc_render_timber_template($query, $template);
                } else {
                    $output .= acclsc_render_php_template($query, $template);
                }

            }

        } else {
            // If subtax is provided, query the terms and group the results by subtax term
            $grouped_posts = acclsc_handle_subtax_query($query_args, $subtax, $timber, $template, $wrapper, $class);

            if ($timber && class_exists('Timber')) {
                $output .= acclsc_render_grouped_timber_template($grouped_posts, $template);
            } else {
                $output .= acclsc_render_grouped_php_template($grouped_posts, $template);
            }

        }

        if ($wrapper == 'true')
        {
            $output .= '</div>';
        }

        if ($show_pagination && $query->max_num_pages > 1) {
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $output .= '<div class="pagination">';
            $output .= paginate_links(array(
                'total' => $query->max_num_pages,
                'current' => max(1, $paged), // Ensure a valid current page
                'format' => '?paged=%#%',
                'mid_size' => 1,
                'prev_text' => __('« Prev'),
                'next_text' => __('Next »')
            ));
            $output .= '</div>';
        }

        return $output;
    }

    add_shortcode('ac_custom_loop', 'acclsc_sc');
}
