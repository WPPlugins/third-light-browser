=== Plugin Name ===
Contributors: thirdlight
Tags: Third Light Browser, image library, thirdlight, Third Light, IMS, digital asset management, DAM
Requires at least: 3.4.0
Tested up to: 3.8.1
Stable tag: 0.0.6
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description == 

The Third Light Plugin for WordPress integrates your existing Third Light server with WordPress, allowing you to browse, crop and use images from your Third Light library in WordPress posts.

This plugin requires that you have a Third Light IMS Premium or Enterprise instance (version 6.0.18 or greater). See [the Third Light website](www.thirdlight.com "Third Light: the Digital Asset Management Specialists") for information.

== Installation ==

Steps to install and run:

1. Copy the plugin folder into your WordPress installation's `/wp-content/plugins` directory.
2. In your WordPress administration menu, navigate to the `Plugins` menu and activate the `Third Light Browser` plugin that will now be listed.
3. Having activated the plugin, a new submenu under the Settings menu will appear, named `Third Light Browser`. Click on this, and enter a valid URL to your Third Light instance. Other settings are optional.

Now, when you create or edit a post, a new button will be available next to the _WordPress Media_ button, which opens the Third Light browser. This browser provides a window into your Third Light instance, and allows you to browse, select, and crop images before inserting them into your WordPress post.

== Additional Configuration ==

Once installed, additional configuration is possible in the _Third Light Browser_ settings menu.

Output formats denote the sizes and formats of image that you can export images into WordPress from your Third Light library. Some example formats are preset, but new formats can easily be added or removed. This provides both convenient resizing to web resolutions and improved consistency when images are used to WordPress.

If you have an API Key (obtained from your Third Light Configuration > Site Options > API Keys menu), automatic login can be enabled. Provided with the username or email address of a user in your Third Light instance, the Third Light Browser will then automatically log in as that user, bypassing the need to manually do so. This requires that you have administration privileges in Third Light.

== Changelog ==

= 0.0.6 =
* Minor tweaks to bring inline with WordPress submission guidelines.

= 0.0.5 =
* Reverted to using old array syntax to support versions of PHP prior to 5.4.

= 0.0.4 =
* Fixed broken "Save Changes" button in IE9.
* Fixed removal of output formats on hitting enter.

= 0.0.3 =
* Updated readme.
* Updated plugin description.

= 0.0.2 =
* Updated readme.

= 0.0.1 =
* Initial release.

== Upgrade Notice ==

= 0.0.5 =
If using PHP < 5.4, you must upgrade to this version to avoid breakage.