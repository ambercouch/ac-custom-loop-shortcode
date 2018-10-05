<?php
/*
  Plugin Name: Custom Loop Shortcode by AmberCouch
  Plugin URI: https://github.com/ambercouch/ac-wp-custom-loop-shortcode
  Description: Shortcode for adding custom loops to content.
  Version: 1
  Author: Richie Arnold
  Author URI: http://ambercouch.co.uk
  Author Email: richard@ambercouch.co.uk
  Text Domain: ac-wp-custom-loop-shortcode
  Domain Path: /lang/
  License:
  Copyright 2018 AmberCouch
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

defined( 'ABSPATH' ) or die( 'You do not have the required permissions' );

if ( !function_exists( 'ac_wp_custom_loop_short_code' ) ) {

    function ac_wp_custom_loop_short_code ($atts) {

        extract(shortcode_atts(array(
            'type' => 'post',
            'show' => 4
        ), $atts));

        $args = [
          'public' => true
        ];
        $output = '';
        $post_types = get_post_types($args, 'names');

        if( ! in_array($type, $post_types)){
            $output .= '<p>';
            $output .= '<strong>'.$type.'</strong> ';
            $output .= __('in not a public post type on this website. The following post type are available: -', 'ac-wp-custom-loop-shortcode');
            $output .= '</p>';
            $output .= '<ul>';

            foreach($post_types  as $key => $cpt){
                $output .= '<li>'.$cpt.'</li>';
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

        ob_start();
        if (have_posts()) :
            while (have_posts()):
                the_post();

                ?>
                <?php get_template_part( 'partials/blog-grid/content', get_post_format() ); ?>
            <?php

         endwhile;
         endif;

        $wp_query = $temp_q;
        $var = ob_get_contents();
        ob_end_clean();
        return $var;

    }

    add_shortcode('ac_custom_loop', 'ac_wp_custom_loop_short_code');

}
