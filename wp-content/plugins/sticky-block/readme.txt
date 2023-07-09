=== Sticky Block for Gutenberg Editor ===
Contributors: senff
Donate link: https://donate.senff.com
Tags: sticky block, sticky, gutenberg
Plugin URI: https://wordpress.org/plugins/sticky-block/
Requires at least: 5.0
Tested up to: 6.2
Stable tag: 1.11
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

The Gutenberg Sticky Block will stick at the top of the page once you scroll down. 

== Description ==

= Summary =

The Gutenberg Sticky Block can be added to any Post or Page and will be sticky as soon as it hits the top of the page after you scroll down. The Sticky Block can contain any other Blocks (Paragraphs, Images, etc.), and can also be used *in* other Blocks (e.g. Column or Table).

The main principle is the same as my [Sticky Menu (Or Anything)](https://wordpress.org/support/plugin/sticky-menu-or-anything-on-scroll) plugin, and the basic code of that plugin has been used in this Sticky Block plugin as well. If you want to make anything sticky outside of the content area (e.g navigation menu, widgets), use the *Sticky Menu (Or Anything)* plugin instead of Sticky Block.

Featured BLOCK OF THE WEEK (Sep 16, 2021).


= Features =

* **Any Block can be added inside the Sticky Block**: the Sticky Block works as a "wrapper" or "grouping" block, that can hold every other type of Block.
* **The Sticky Block can be added inside other Blocks**: any block that works as a "wrapper" (such as the Columns Block or the Table Block) can also hold a Sticky Block.
* **Positioning from top** (optional): you can add any amount of space between the Sticky Block and the top of the page, so that the element is not always stuck at the "ceiling" of the page.
* **Enable for certain screen sizes only** (optional): you can set a minimum and/or maximum screen size where the stickiness should work. This can be handy if you have a responsive site and you don't want your Block to be sticky on smaller screens, for example. 
* **Push-up element** (optional): you can pick any other element lower on the page that will push the Sticky Block up again (for example a footer, or even another Sticky Block).
* **Admin Bar aware**: checks if the current user has an Admin Toolbar at the top of the page. If it has, the Sticky Block will not obscure it (or be obscured by it).
* **Z-index**: in case there are other elements on the page that obscure or peek through your Sticky Block, you can add a Z-index easily. This will only be applied once the Block is sticky.


== Installation ==

1. Install Sticky Block from the WordPress Plugin Directory. Or, if that doesn't work:
2. Upload the "sticky-block" directory to your "wp-content/plugins" directory.
3. In your WordPress admin, go to PLUGINS and activate "Sticky Block"
4. When editing a Post or Page, find the Sticky Block in the Layout section of the available Blocks.
5. Party!


== Frequently Asked Questions ==

= Can I add more than one Sticky Block on a page?
Yes you can. Unlike the [Sticky Menu (Or Anything)](https://wordpress.org/support/plugin/sticky-menu-or-anything-on-scroll) plugin (where you can select only one sticky element), the Sticky Block plugin will let you add as many Sticky Blocks as you want.

= Is it possible to add some styles to the element but only when it's sticky?
To add styles to your Block when it's not sticky, use classname ".block-is-not-sticky".
To add styles to your Block only when it's sticky, use classname ".block-is-sticky".

The following code would give your Block a red background only when it's not sticky, and blue only when it is:

.block-is-not-sticky {
   background: red;
   }

.block-is-sticky {
   background: blue;
   }

You may need to be more specific with your CSS, to override other default styles.

= I'll need more help please! =
Please go to the plugin's [support forum on WordPress.org](https://wordpress.org/support/plugin/sticky-block) and post a message (include a link to your site if possible). 

= I need some functionality that the plugin currently doesn't support. Can you help make a custom version? =
I am currently not available for any custom work.

= How was this plugin made? =
Sticky Block was created using the [Create Guten Block Toolkit](https://ahmadawais.com/create-guten-block-toolkit/) by [Ahmad Awais](https://ahmadawais.com/).


== Screenshots ==

1. Adding a Sticky Block
2. A Sticky Block (containing a Media-Text Block) with all its options


== Changelog ==

= 1.11 =
* Minor bug fix (thanks to @brogr for reporting)
* WP 6.0 compatibility

= 1.1 =
* WP 5.9 compatibility

= 1.0 =
* Initial release of the plugin
