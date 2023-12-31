=== Author Category ===
Contributors: bainternet, GwynethLlewelyn
Donate link: https://en.bainternet.info/donations
Tags: author category, limit author to category, author posts to category
Requires at least: 3.0
Tested up to: 6.3.2
Stable tag: 0.10.0

A fork of a simple lightweight plugin that limits authors to post just in one category.

== Description ==

This plugin allows you to select a specific category per user and all of that users posts will be posted in that category only.

**Main Features:**

*   Only admin can set categories for users.
*   Only users with a specified category will be limited to that category, other will still have full control.
*   Removes category metabox for selected users.
*   Removed categories from quick edit for selected users.
*   Option to clear selection. (new)
*   Multiple categories per user. (new)

French translation (since 0.8) thanks to @jyd44

Any feedback or suggestions are welcome.

Also check out my <a href=\"http://en.bainternet.info/category/plugins\">other plugins</a>

== Installation ==

1.  Extract the zip file and just drop the contents in the `wp-content/plugins/` directory of your WordPress installation.
2.  Then activate the Plugin from the Plugins page.
3.  Done!
== Frequently Asked Questions ==

= I have Found a Bug, Now what? =

Simply use the <a href=\"http://wordpress.org/tags/author-category?forum_id=10\">Support Forum</a> and thanks a head for doing that.

= How To Use =

Just login as the admin user and under each user »» profile select the category for that user.

== Screenshots ==
1. User category selection under user profile.
2. Author category metabox.

== Changelog ==
 = 0.10.0 =
* Started fixing everything to become compatible with PHP 8.2 and WP 6.3.2.
* Try to adhere to the WordPress Coding Standards as much as possible, using PHP Code Sniffer and
PHP Mess Detector to properly clean up code.
* Bumped license to GPL 3.0 (was 2.0) and added file.

 = 0.8 =
* Added POT file for translations.
* Added french translation.
* Fixed translation loading to an earlest time to allow panel translation.

 = 0.7 =
* Updated simple panel version.
* Added textdomain to plugin and to option panel.
* Wrapped checkboxes with labels.
* Categories are now ordered by name.

 = 0.6 =
Fixed xmlrpc posting issue.
* Added an option panel to allow configuration of multiple categories.
* Added an action hook `in_author_category_metabox`.

 = 0.5 =
* Added post by mail category limitation.

 = 0.4 =
* Added support for multiple categories per user.
* Added option to remove user selected category.

 = 0.3 =
* Added plugin links,
* Added XMLRPC and Quickpress support.
* Changed category save function from `save_post` to default `input_tax` field.
* Added a function to overwrite default category option per user.

 = 0.2 =
* Fixed admin profile update issue.

 = 0.1 =
* Initial release.
