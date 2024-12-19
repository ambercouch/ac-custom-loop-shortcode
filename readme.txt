=== AC Custom Loop Shortcode ===
Contributors: ambercouch
Donate link: http://ambercouch.co.uk/
Tags: shortcode, list post, list custom posts, timber, twig, custom post type
Requires at least: 5.2
Tested up to: 6.6
Stable tag: 1.6.1
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple WordPress plugin that creates a shortcode to loop through posts, pages, or custom post types and display them anywhere on your site.

== Description ==

Easily display posts, pages, custom post types or taxonomy terms in content areas using a customizable shortcode. Display your latest posts, group by taxonomies, display your post tags or categories, use custom templates with Timber for Twig support.

== Installation ==

1. Install via WordPress Add New Plugin feature by searching "AC Custom Loop," or upload the plugin files to `/wp-content/plugins/ac-wp-custom-loop`.
2. Activate the plugin via the ‘Plugins’ screen in WordPress.
3. Use `[ac_custom_loop]` shortcode in any page, post, or widget supporting shortcodes.
4. The default setup displays your latest 4 posts. Customize with attributes like `type` and `show`, e.g., `[ac_custom_loop type="page" show="3"]` to display the latest three pages.

== Shortcode Options and Examples ==

**Display a specific post type:**
`[ac_custom_loop type="foo"]`
This displays posts from the `foo` custom post type.

**Display a specific post type and use a timber template:**
`[ac_custom_loop timber='true' type="foo"]`
This displays the same as the previous example but uses a nice timber template.

**Display posts with specific tags:**
`[ac_custom_loop type="post" tax="tag" term="foo"]`
This displays posts tagged with `foo`.

**Group posts by taxonomy (e.g., categories and tags):**
`[ac_custom_loop type="post" subtax="category,tag"]`
Groups posts by categories, then by tags within each category.

**Exclude posts by specific tags:**
`[ac_custom_loop type="post" tax="post_tag" term="foo,bar" exclude="baz"]`
This displays posts tagged with `foo` and `bar`, but excludes those tagged with `baz`.

**Display post tag terms:**
`[ac_custom_loop type="category" tax="post_tag" ]`
This displays all your category terms and link to those post.

**Use a custom template for loop display:**
To use a custom template, copy `loop-template.php` from the plugin folder to the root of your theme folder and modify as desired. You can also create templates for specific post types (e.g., `loop-template-post.php`).

== Frequently Asked Questions ==

= How do I show posts from a specific post type? =
Use `[ac_custom_loop type="your_post_type"]` to show posts from a specific custom post type.

= Can I display posts with a specific taxonomy term? =
Yes! Use `[ac_custom_loop type="post" tax="tag" term="your_term"]` to filter posts by taxonomy term.

= How can I group posts by taxonomies? =
Use `[ac_custom_loop subtax="category,tag"]` to group posts by taxonomies.

= Can I exclude specific terms? =
Yes, add `exclude="term"` to exclude posts tagged with that term.

= Can I use custom templates? =
Absolutely! Copy `loop-template.php` to your theme directory or create post-type-specific templates, such as `loop-template-post.php`.

== Screenshots ==

1. Adding the shortcode to any content area that supports shortcodes.
2. Posts, pages, or custom post types appear styled in your chosen layout on the front end.

== Upgrade Notice ==

= 1.5.2 =
Fix template issue.

= 1.5.1 =
Added support for grouping posts by multiple taxonomies and refactored core code for flexibility and clarity.

= 1.5 =
Improved Timber support.

= 1.4.3 =
Fixed release issues.

== Changelog ==

= 1.6.1 =
* Fixed issue that required Timber
* Fixed issue with using multiple taxonomy terms

= 1.6 =
* Added support for display taxonomy terms.
* Added support for pagination.

= 1.5.2 =
* Fix template issue.

= 1.5.1 (2024-11-01) =
* Added support for grouping posts by multiple taxonomies.
* Refactored core code for improved maintainability.

= 1.5 (2021-04-04) =
* Enhanced Timber compatibility.

= 1.4.3 (2019-12-06) =
* Resolved release issues.

= 1.4.2 (2019-12-24) =
* Fixed shortcode argument issues.

= 1.4.0 (2019-12-05) =
* Added support for specific post type templates and custom Timber integration.

= 1.1.0 (2018-10-16) =
* Added default post order and optional excerpt in the default template.

= 0.1.0 (2018-10-07) =
* Initial release.
