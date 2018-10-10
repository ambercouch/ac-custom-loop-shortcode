<?php
/*
  Plugin Name: AC Custom Loop Shortcode
  Plugin URI: https://github.com/ambercouch/ac-wp-custom-loop-shortcode
  Description: Shortcode  ( [ac_custom_loop] ) that allows you to easily list post, pages or custom posts with the WordPress content editor or in any widget that supports short code.
  Version: 1
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

if (!function_exists('ac_wp_custom_loop_short_code'))
{

    function ac_wp_custom_loop_short_code($atts)
    {


        extract(shortcode_atts(array(
            'type' => 'post',
            'show' => 4,
            'template' => 'loop-template.php',
            'css' => 'true'
        ), $atts));

        $args = [
            'public' => true
        ];
        $output = '';
        $post_types = get_post_types($args, 'names');
        $theme_directory = get_stylesheet_directory().'/';
        $theme_template = $theme_directory.$template;

        if($css == 'true'){
            $handle = 'ac_wp_custom_loop_styles';
            $list = 'enqueued';

            if (! wp_script_is( $handle, $list )) {
                wp_register_style( 'ac_wp_custom_loop_styles', plugin_dir_url( __FILE__ ) . 'assets/css/ac_wp_custom_loop_styles.css', array(), '20181007' );
                wp_enqueue_style( 'ac_wp_custom_loop_styles' );
            }
        }

        if( file_exists($theme_template)){
            $template = $theme_template;
        }

        if (!in_array($type, $post_types))
        {
            $output .= '<p>';
            $output .= '<strong>' . $type . '</strong> ';
            $output .= __('in not a public post type on this website. The following post type are available: -', 'ac-wp-custom-loop-shortcode');
            $output .= '</p>';
            $output .= '<ul>';

            foreach ($post_types as $key => $cpt)
            {
                $output .= '<li>' . $cpt . '</li>';
            }
            $output .= '</ul>';
            $output .= '<p>';
            $output .= __('Please edit the short code to use one of the available post types.', 'ac-wp-custom-loop-shortcode');
            $output .= '</p>';
            $output .= '<code>[ ac_custome_loop type="post" show="4"]</code>';

            return $output;
        }

        global $wp_query;
        $temp_q = $wp_query;
        $wp_query = null;
        $wp_query = new WP_Query();
        $wp_query->query(array(
            'post_type' => $type,
            'showposts' => $show,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));


        if (have_posts()) :
            $output .= '<div class="c-accl-post-list" >';
            while (have_posts()):
                the_post();
                ob_start();
                ?>
            <?php require($template) ?>
                <?php //get_template_part('template-parts/post/content'); ?>
                <?php
                $output .= ob_get_contents();
                ob_end_clean();
            endwhile;
            $output .= '</div>';
        endif;

        $wp_query = $temp_q;

        return $output;

    }

    add_shortcode('ac_custom_loop', 'ac_wp_custom_loop_short_code');

}
