=== WPIDE - File Manager & Code Editor ===
Contributors: XplodedThemes
Tags: theme editor, plugin editor, code editor, file editor, file manager, wpide, ide
Requires at least: 5.2
Tested up to: 6.1.1
Stable tag: 3.4.6
Requires PHP: 7.4.0
Requires at least: 5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WPIDE is an advanced File Manager & Code Editor for WordPress. You can edit any files in your wp-content folder, not just plugins and themes. Code completion will help you remember your WordPress/PHP commands providing function reference along the way. Edit multiple files with the tabbed editor.

== Description ==

**WPIDE** is an Advanced **File Manager** and **Code Editor** plugin for WordPress that you can use completely for free.

The Code Editor lets you edit any file within your wp-content folder, not just plugins and themes.

The included **code completion** will help you remember your WordPress/PHP commands providing function reference along the way. Edit multiple files with the tabbed editor.

The File Manager lets you copy, move, duplicate, create an archives, download, upload, edit, delete, preview files & directories **without FTP or cPanel access**.

WPIDE uses a **very modern, clean and easy to use interface** to make managing and editing your files a breeze! It comes with 6 different themes and a dark mode to reduce blue light exposure.

**Did you know?**
More than [700,000 WordPress websites](https://www.wordfence.com/blog/2020/09/millions-of-sites-targeted-in-file-manager-vulnerability-attacks/) were attacked during September 2020.
Malicious bots are looking to exploit vulnerable versions of WP file manager plugins.

Fortunately, WPIDE is built with **security in mind** and comes with this vulnerability **fixed**! So rest assured! WPIDE poses no risk to you!

###▶️ VIDEO OVERVIEW

[youtube https://www.youtube.com/watch?v=wF0PUz8wfRM]

<a target="_blank" href="https://www.youtube.com/watch?v=wF0PUz8wfRM">https://www.youtube.com/watch?v=wF0PUz8wfRM</a>

###⚡️ FEATURES

*   Advanced File Manager
*   File Tree Browser
*   Smart context menu
*   Customizable Root Path
*   Create new files and directories
*   Download files / folders (Batch support)
*   Upload files / folders using drag n drop (Batch support)
*   Zip / Unzip files and folders (Batch support)
*   Deep search for files / folders by keyword
*   Calculate folder size
*   Advanced File Editor
*   Editor Line numbers
*   Editor Find + Replace
*   Editor Syntax highlighting
*   Editor Highlight Matching Parentheses
*   Editor Automatic Indentation + Code Folding
*   Editor keyboard commands / shortcuts
*   Tabbed interface for editing multiple files
*   WordPress and PHP code auto-completion
*   PHP code parsing and validation
*   PHP file backup before saving
*   File Recovery Wizard
*   Using WordPress filesystem API
*   Beautiful Image Gallery
*   Video / Audio Media Player


###<a target="_blank" href="https://wpide.com">👉 **WPIDE PRO**</a>

###⭐️ PRO FEATURES

= File Editor =

* Auto Save Feature: While editing, files will be auto saved every X seconds to a draft file. Original files are not affected. If you ever close the page by mistake without saving a file, you will be able to restore from the auto saved file.
* Auto Saved Quick Diff: You can view and compare differences between the auto saved and the current file using the Quick Diff Viewer, then decide if you wish to restore from the auto saved or keep the current file.
* Toggle Full Screen: This will allow you to toggle full screen the editor area by itself, giving you more space while editing on smaller screens.


= File Manager =

* Advanced Mode Option: When enabled, all files and folders will be available for editing including core WordPress files and the wp-config.php file.


= Config Manager =

* View all defined constants within wp-config.php
* Add / Update / Remove constants
* Prevent Duplicated Constants


= Advanced Image Editor =

* Resize / Crop Images
* Apply filters
* Add Frames / Corners
* Add Text / Stickers
* Add Patterns / Gradients


= Database Manager =

* View Tables
* Create New Tables
* Add / Update / Delete Rows
* Update Table Structure
* Update Column Indexes
* Safe Editing Enabled


== Installation ==

1. Upload the WPIDE folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Access WPIDE by clicking the WPIDE menu item in your main administration menu

== Frequently Asked Questions ==

= What is in place to stop me from breaking my website - "The white screen of death" =

When you edit a PHP file, before that file is saved to the filesystem it is syntax checked to make sure there isn't something obvious that will break your site.

Every file that you edit is backed up before your first save to the filesystem and then on subsequent saves WPIDE will try and make a backup. It will save a maximum of files for up to 5 days to the server.

As you edit or more specifically save PHP files, in case of a fatal error, the File recovery Wizard will appear and a restore button will display which will allow you to restore the most recent backup.

If your WordPress install is fully functional then you can use the file manager to browse all of your backed up files (/upload/wpide/backups/), if your WordPress install isn't responding then restoring the file directly via FTP/SSH is the only way.

The backed up PHP files cannot be accessed/restored from the web directly without the 40 digit nonce/key so should not pose a security concern.

= Can I override the default file permissions when creating files/directories on the local filesystem =

Yes you can use the below WordPress settings in wp-config.php which will affect files created with WPIDE and files added during the WordPress upgrade process.

define('FS_CHMOD_DIR', (0755 & ~ umask()));
define('FS_CHMOD_FILE', (0644 & ~ umask()));

= I get an error notification on the bottom right saying 'something went wrong, unknown error'. How can I fix this ? =

Some hosts might disable direct access to PHP files which will block the plugin from accessing them.

Some users reported this issue on **Cloudways**, and they were able to fix it by enabling **Direct PHP File Access** under application settings within Cloudways dashboard.

This option might be available on other hosting platforms as well.

== Screenshots ==

1. File Manager
2. Code editor + Auto Complete Reference
3. Image Gallery Viewer
4. Video Player Viewer
5. Files & Folders Deep Search
6. File Recovery Wizard

== Changelog ==

#### 3.4.6 - 10.12.2022
- **fix**: Instead of ABSPATH, use the correct root path, relative to the plugin in case WordPress Files are located outside the root directory, which is the case if the site is hosted on WordPress.com.
- **fix**: Minor fixes

#### 3.4.5 - 07.12.2022
- **new**: **Pro** Config Manager | Added new visual config manager to easily Add, Update or Delete constants within the wp-config.php file
- **new**: **Pro** File Manager | Added new **Advanced Mode** option. When enabled, all files and folders will be available for editing including core wordpress files and the wp-config.php unless they are filtered out manually within the settings.
- **new**: **Pro** File Editor | Added new **Auto Save** option. While editing, files will automatically be saved every X seconds to a temporary draft file. Original files are not affected. If you every close or refresh the page by mistake without manually saving a file, the next time you open it, a File Recovery modal will display allowing you to restore from the auto saved file. You can also view and compare both files differences using the Quick Diff Viewer. https://d.pr/v/l6SQjD
- **new**: File Editor | Added option to increase / decrease font size within the toolbar
- **enhance**: File Editor | In dark mode, highlighted text bg color will now inherit the theme accent color
- **support**: Added multisite support
- **fix**: Settings | Fixed issue with the repeater field.
- **fix**: Fixed scrolling issue on Firefox
- **fix**: When opening a file, show popup error if for some reason the file does not exist, (deleted from another window) or has no read / write permissions.
- **fix**: Minor fixes
- **update**: Update Freemius SDK

#### 3.4.4 - 19.10.2022
- **new**: **Pro** File editor | Added new **Toggle Full Screen** option within the tool bar
- **fix**: Fixed Class 'finfo' not found error appearing on servers without fileinfo extension by adding a fallback function.
- **fix**: Minor fixes

#### 3.4.3 - 27.09.2022
- **new**: Added .svg and .yaml file extensions to the editable file extensions option.
- **new**: Added new filter **wpide_editable_ext_options** to allow adding new extensions to the list by 3rd party.
- **support**: Support SVG syntax check / highlighting
- **fix**: Fixed bug with single file upload
- **fix**: Fixed issue with soft tabs
- **enhance**: Remove uppercase CSS from breadcrumbs. Replaced "Home" with the name of the root folder. Added a nice drive icon.
- **enhance**: Faster file load and switching between display types
- **enhance**: Keep current active file display type after reloading the page
- **enhance**: Keep current active settings tab after reloading the page
- **fix**: Fixed WP admin menu scroll glitch
- **new**: Added right click context menu on folders & files within the file manager and the editor
- **new**: Added sort dropdown above file manager
- **update**: File Tree within the File Editor is now sorted by name (asc)
- **fix**: **Pro** Image Editor | Fixed glitch with revert image function
- **fix**: Minor fixes

#### 3.4.2 - 05.09.2022
- **support**: Support windows server / xampp on windows
- **fix**: Minor CSS fixes

#### 3.4.1 - 17.08.2022
- **new**: Added append content hash when loading js / css chunks to force cache busting making sure always the latest version is used

#### 3.4 - 16.08.2022
- **new**: File Editor | Added new Settings tab
- **new**: File Editor | Added Scroll Speed option
- **new**: File Editor | Added an option to disable Behaviours, such as auto closing tags and brackets
- **new**: File Editor | Added option to enable / disable Soft Tabs
- **new**: File Editor | Using the enter key within the search input will now take you to the next occurrence
- **new**: File Editor | After selecting text then pressing the Ctrl-F key, the search input will focused and populated with the selected text
- **new**: File Editor | File Tree | Added file manager action menu for each item.
- **new**: File Manager | Added new item actions: Create folder, Create file, Duplicate
- **new**: File Manager | The default zip name, which can be changed within the settings, will only be applied when batch zipping or downloading. For a single item, the item name will be used by default instead.
- **fix**: Minor fixes

#### 3.3 - 02.08.2022
- **support**: PHP 8+ Support
- **support**: To use WPIDE on a lower PHP version, use v2.6 or below.
- **update**: Updated PHP required version to 7.4.0
- **update**: Better installation / update handling for the fatal-error-handler.php drop-in to make sure the latest version is always used.

#### 3.2 - 30.07.2022
- **fix**: Fix corrupt file
- **fix**: Replace symfony session with transients to avoid session issues with other plugins

#### 3.0 - 30.07.2022
- **new**: A brand new and completely revamped version of WPIDE.

#### 2.6 - 26.04.2022
- **update**: Plugin acquired by **XplodedThemes**
- **update**: A brand new and completely revamped version of WPIDE is coming soon, Stay tuned!

#### 2.5 - 2020.04.13
- **update**: getting the plugin back to life
- **update**: started big code cleanup
- **update**: completely removed experimental Git features
- **support**: fully compatible with latest WordPress

#### 2.4.0
- **new**: Context menu option added to file browser to rename, delete, download, zip, unzip!! thanks to shanept https://github.com/shanept for the code behind this.
- **new**: find+replace
- **update**: Stopped using the full functionality of sanitize_file_name() and using a cut down version instead that allows period, dash and underscore at the beginning and end of filename.
- **fix**: corrected invalid regex for determining image type
- **update**: Changed order of PHP4 compatible constructor in an attempt to stop a PHP Strict Standard error
- **update**: Update to Ace Editor 1.2.0
- **update**: Editor settings, so that it’s possible to change the theme and other editor features/behaviours
- **new**: New application icon in menu bar

#### 2.3.2
- **update**: Update the Ace component to 1.1.1 which includes some bug fixes, a PHP worker (showing PHP errors as you work) and a greatly improved search box.
- **fix**: Fixed issue with file save showing javascript alert as if there was a failure when there wasn't
- **update**: Order folders and files alphabetically

#### 2.3.1
- **fix**: As a quick fix I have commented out the git functionality as the namespacing used is causing issues with old versions of PHP

#### 2.3
- **new**: Added initial git functions using the following library: PHP-Stream-Wrapper-for-Git from https://github.com/teqneers/PHP-Stream-Wrapper-for-Git
- **new**: Initial Git functionality added - it's very experimental!

#### 2.2
- **new**: Add restore recent backup facility - It's a primitive implementation at this point but it does the job. See FAQ note.
- **update**: Turned on the LESS mode when a .LESS file is edited
- **update**: Made the autocomplete functionality only be enabled for PHP files otherwise it can be a pain to write txt files like this one!

#### 2.1
- **update**: Ramped up the version number because the last one was just getting silly
- **update**: Interface changes to make the editor take up more screen space. Including hiding the WP admin menu and footer.

#### 2.0.16
- **fix**: Fixed problem saving PHP documents - PHP-Parser library wasn't included in the codebase correctly

#### 2.0.15
- **update**: PHP syntax checking before saving to disk (Using: https://github.com/nikic/PHP-Parser)

#### 2.0.14
- **fix**: Fixed error Warning: Creating default object from empty value in WPIDE.php
- **update**: Updated the ace editor to current build

#### 2.0.13
- **new**: Added colour assist - a colour picker that displays when you double click a hex colour code in the editor (see other notes for info).
- **new**: Added a confirm box to stop you exiting the editor by mistake and losing unsaved changes.
- **new**: Added 'wpide_filesystem_root' filter (see other notes for info).
- **update**: A number of bug fixes.

#### 2.0.12
- **new**: Added links to the WordPress codex and the PHP manual from within the function reference for further info

#### 2.0.11
- **update**: Newly created files use to contain a space, instead it now defaults to a blank file.

#### 2.0.10
- **fix**: Fixed a problem with file loading (ajax) indicator not showing.

#### 2.0.9
- **update**: Upload snapshot of current ajaxorg editor (master/build/src) at 00:30 on the 22 May 2012. Which fixes some issues with selecting big blocks of text, code folding seems better with gutter interface hidden when not in use

#### 2.0.8
- **support**: Fix browser compatibility issues

#### 2.0.7
- **update**: Fixing issue with closing tabs not focusing onto next tab once closed.
- **fix**: Fixed issue with detecting ajax url correctly which was causing all WPIDE ajax requests to fail if WordPress was installed in a subdirectory.
- **update**: Stopped autocomplete from trying to work when a js/css file is being edited.

#### 2.0.6
- **update**: Cleaned up the WPIDE class and modified the way the class is passed to WordPress actions/filters.

#### 2.0.5
- **update**: On startup the editor page now shows extra debugging information for the filesystem API initialization.

#### 2.0.4
- **update**: On startup the initial editor page now shows some startup info regarding file permissions to help with debugging.

#### 2.0.3
- **update**: If WPIDE cannot access your files due to permissions then when it starts up it will give you an alert to say this.

#### 2.0.2
- **new**: Image editing is now available using the SumoPaint image editor and drawing application http://www.sumopaint.com/

#### 2.0.1
- **new**: You can now create new files/folders

#### 2.0
- **update**: Recreated this plugin as a dedicated WPIDE section/app rather than extending the built in plugin/theme editor (just incase WP remove it)
- **update**: Now using the WP filesystem API (although currently restricted to local access)
- **update**: More security checks on file opening and editing
- **new**: Added new file tree for exploring the file system and opening files (any file in wp-content)
- **update**: Massive overhaul to code autocomplete functionality with the addition of function information right in the app
- **update**: Update the ajaxorg Ace Editor to the current branch
- **new**: Tabbed editing

#### 1.0.6
- **new**: Added link to meta section of plugin list for easy install of V2 Dev version if you have dismissed the alert.

#### 1.0.5
- **new**: Added the facility to download and run the cutting edge development version of WPIDE from the Github repository

#### 1.0.4
- **new**: Implemented JavaScript and CSS mode for better syntax highlighting and checking
- **enhance**: Organize and format source code

#### 1.0.2
- **update**: Tidy and comment code
- **new**: Added message when backup file is generated
- **fix**: Adjust code complete dropdown position
- **enhance**: Improved editor responsiveness when using delete or enter keys

#### 1.0.1
- **fix**: Fixed "Folder name case" issue.

#### 1.0
- **initial**: Initial release.

