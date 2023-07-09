=== My Marginalia ===
Contributors: davidfcarr
Tags: gutenberg, block, formatting, show notes, annotation, float, timestamp
Donate link: http://rsvpmaker.com/
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Requires PHP: 5.3
Tested up to: 5.5
Stable tag: 1.0.6

Creates a Gutenberg block for marginal notes displayed float right or float left relative to other content. Useful for podcast or webcast show notes or annotated transcripts.

== Description ==

The My Marginalia plugin for WordPress lets you format content with notes or comments aligned float:left or float:right to the main body of your content.

*marginalia (n)*: notes in the margin of a book, manuscript, or letter

https://youtu.be/AxwsArnwYgQ

The plugin's original use case was to present the show notes for a webcast with timestamps linked to the specific portion of the video being discussed. Similar uses might include providing commentary to accompany a transcript (noting all the lies in a politician's speech, perhaps?).

This plugin takes advantage of the Gutenberg editor that became the new standard with the release of WordPress 5.0. The "notes" field shows in the editor as a RichText component with standard formatting controls, including the ability to add links. The main content (the subject of the marginalia commentary) can include multiple blocks such as paragraphs, headings, images, and other media.

This project was bootstrapped with [Create Guten Block](https://github.com/ahmadawais/create-guten-block).

Header image: [Rafel Bosch, public domain, via Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Martirologi.jpg)

== Installation ==

1. Upload the entire `marginalia` folder to the `/wp-content/plugins/` directory (or fetch using the WordPress dashboard tools).
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Screenshots ==

1. A show notes example.
2. Formatting a note to be displayed next to an associated set of content blocks within the Marginalia wrapper.
3. The Marginalia block appears on the Layouts tab in Gutenberg.

== Credits ==

    My Marginalia
    Copyright (C) 2019 David F. Carr

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    See the GNU General Public License at <http://www.gnu.org/licenses/gpl-2.0.html>.

== Changelog ==

= 1.0.6 =

* Updated NPM

= 1.0.5 =

* Updated NPM modules

= 1.0.2 =

* Added settings screen where you can set the default sidebar width and position.
* Updated NPM modules to address security bugs in Javascript libraries

= 1.0.0 =

* Initial release.