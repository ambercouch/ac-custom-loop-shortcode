=== AC Custom Loop Shortcode ===
Contributors: ambercouch
Donate link: http://ambercouch.co.uk/
Tags: shortcode, list post, list custom posts,
Requires at least: 4.6
Tested up to: 4.9.8
Stable tag: 1.1.0
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shortcode to display posts in content areas.

== Description ==

A simple Wordpress plugin that creates Wordpress shortcode that will loop through posts, pages, or custom post types and display them on your website or blog. A typical use would be to show your latest post on your homepage.

== Installation ==

Use WordPress' Add New Plugin feature, searching "AC custom loop", or download the archive and:

1. Upload the plugin files to the `/wp-content/plugins/ac-wp-custom-loop` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Add the shortcode [ac_custom_loop] to the content area of any page, post or custom post type or to any widget that supports shortcode.
4. By default the shortcode will display your latest 4 post, you can use the 'type' and 'show' arguments to customise the type of post that are shown and how many eg. [ac_custom_loop type="page" show="3"] would show the last three published pages.


== Frequently Asked Questions ==

= Can I use my own template to display the looped posts? =

Yes you can! Simply copy loop-template.php from the plugin folder to the root of your theme folder and edit as needed.

== Screenshots ==

1. Add the code to any content area that accepts shortcode.
2. Posts, Pages or Custom post types are shown on the front end of your website.

== Upgrade Notice ==

= 1.1.0 =
Updates to default template and post order.

= 0.1.1 =
Added user template function

= 0.0.1 =
Initial version

== Changelog ==

= 1.1.0 (2018-10-16) =
* Added optional excerpt to the default template.
* Added default post order (post = date, everything else = menu_order).

= 0.1.1 (2018-10-13) =
* Added function to override template with the users own template
* Added GPLv2 licence
* Fixed some typos

= 0.1.0 (2018-10-07) =
* Initial version on WP repository