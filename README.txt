=== Post/Page Copying Tool  to Export and Import post/page for Cross site Migration ===
Contributors: wpspin
Donate link: 
Tags: Post, Page, Export, post export, page export
Requires at least: 4.0.0
Tested up to: 6.5.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds Export and Import buttons for posts/pages, enabling JSON exports of post details and simplifying content addition.

== Description ==

This plugin streamlines content management across different versions of a WordPress site by enabling the export and import of post/page content in JSON format.
It eliminates the need for manual copying by allowing you to download post details—including content, title, categories, and featured image—into a JSON file.
This file can then be uploaded to another post, automatically updating its content, custom fields, taxonomies, and featured image. The plugin supports:

1. Post Title
2. Post Content
3. Featured Image
4. Taxonomies: Tag, Category, and Custom Taxonomy
5. PostMeta / Custom Fields
Designed to work with major page builders and ACF fields, the plugin offers flexibility to selectively duplicate posts, avoiding the need for full database migration.

== Installation ==

1. Upload the plugin folder to the /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in the WordPress Dashboard.

== Screenshots ==

1. How to export a JSON file
2. Import button
4. Import popup

== Changelog ==

= 1.0.0 =
* First version of the plugin.

= 1.0.1 =
* Check compatible with WordPress 6.0.1.

= 1.1.0 =
* Fix enqueue of CSS & JS files.
* Test with WordPress version 6.1.1

= 1.2.0 =
* Showing import button when new page screen
* Fix import file path

= 1.2.1 =
* Test with wp 6.2

= 1.3.0 =
* Fix broken templates that have complex ACF Fields.
* Test compatibility with WordPress 6.3.1.

= 2.0.0 =
* Enhance the way the plugin works