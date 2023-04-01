=== Infusionsoft Gravity Forms Add-on ===
Tags: Gravity Forms, Infusionsoft, CRM
Requires at least: 3.3
Tested up to: 4.5
Stable tag: 1.5.12
Contributors: katzwebdesign, katzwebservices
Donate link: https://gravityview.co/?utm_source=wordpress&utm_medium=readme&utm_campaign=infusionsoft&utm_content=Donate

Integrate the remarkable Gravity Forms plugin with Infusionsoft.

== Description ==

### The best Infusionsoft plugin for WordPress.
Easily add contacts into Infusionsoft when users submit a Gravity Forms form.

Map your Gravity Forms form fields to Infusionsoft data, and have contacts automatically updated if the contact already exists.

####Easily view contacts in Infusionsoft
When the Entry is created, a link to the Contact's page in Infusionsoft is shown inside WordPress.

### It's the best form plugin combined with the best CRM service.
Forget manually adding the Infusionsoft Web Forms into your WordPress site. Use Gravity Forms, and you'll be on your way with a beautiful, smart form in minutes.

#### Coming soon...
If this plugin garners much interest, there will be some seriously cool stuff coming, including:

* Invoices & Orders integrated with Gravity Forms payments
* Creation of Opportunities and Companies
* <del>Custom fields for Contacts</del> - Added in 1.2!
* <del>Add Tags to Contacts</del> - Added in 1.3!
* Add Contacts to Campaigns
* And much more

If you're interested in having this functionality, <strong>leave us a note in the support forum &rarr;!</strong>

== Screenshots ==

1. Gravity Forms Infusionsoft Add-on settings page
2. It's easy to integrate Gravity Forms with Infusionsoft: set up a "Feed" and match up the fields you'd like sent to Infusionsoft
3. Then you map the fields in the form to the fields for the Infusionsoft contact.
4. When a form is submitted, a contact will be created or updated in Infusionsoft. Simple!

== Installation ==

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
1. Activate the plugin
1. Go to the plugin settings page (under Forms > Settings > Infusionsoft)
1. Enter the information requested by the plugin.
1. Click Save Settings.
1. If the settings are correct, it will say so.
1. Follow on-screen instructions for integrating with Infusionsoft.

== Frequently Asked Questions ==

= Does this plugin require Infusionsoft? =
Well of course it does.

= What's the license for this plugin? =
This plugin is released under a GPL license.

== Changelog ==

= 1.5.12 on April 16, 2016 =

This is one of the last updates before a major re-write, coming later this year.

* Fixed potential XSS security issue. __Please update.__
* Fixed deprecated jQuery Javascript (convert `live()` to `on()`)
* Fixed link to "Learn how to find your API key" doc
* Fixed not being able to remove Conditional Tags
* Fixed styling of the settings screen
* Sanitized text and URLs
* Improved translation strings

= 1.5.11 on September 12, 2014 =
* Fixed potential security issue. __Please update.__

= 1.5.10 on September 12, 2014 =
* Infusionsoft updated their SSL provider. The plugin has updated a file to match their security settings.
* Added: Translation files. If you want to translate the plugin into your language, [please do so here](https://www.transifex.com/projects/p/gravity-forms-infusionsoft/).

= 1.5.9.6 on August 19, 2014 =
* Added: 'gf_infusionsoft_radio_value' filter to allow radio field value manipulation before sending it to Infusionsoft.

= 1.5.9.5 on June 15, 2014 =
* Fixed: Cast Number field type before sending it to Infusionsoft

= 1.5.9.4 on June 11, 2014 =
* Fixed: blank screen when no email is defined.
* Fixed: Opt In condition value not showing correctly on feed edit screen.

= 1.5.9.3 on May 27, 2014 =
* Fixes custom fields update if Yes/No custom field is present

= 1.5.9.2 on May 22, 2014 =
* Include again the Quiz Add-on Fields (from version 1.5.8)

= 1.5.9 & 1.5.9.1 on May 15, 2014 =
* Fixed: Fetching tags now starts on page 1, not page 2
* Fixed: Path to translations file
* Fixed: PHP notice for calling non-static method statically
* Fixed: SSL issue [as reported here](http://wordpress.org/support/topic/plugin-breaks-on-ssl)

= 1.5.8.1 on May 1, 2014 =
* Added: Support for mapping Quiz Add-On fields for a feeds
* Modified: Changed default number of tags to get from Infusionsoft from 3,000 to 4,000.
* Added: `gf_infusionsoft_max_number_of_tags` filter to allow developers to change how many tags to fetch from Infusionsoft (returns `int`, `4000` default)

= 1.5.8 on April 3, 2014 =
* Added: Enable the Quiz add-on fields on the feeds

= 1.5.7.2 =
* Added: Enable debug logging using Gravity Forms Logging Add-on

= 1.5.7.1 =
* Fixes PHP warning related with unkonw form_id index on line 772
* Fixes "Refresh Fields & Tags" link after saving feed - avoid feed duplication

= 1.5.7 =
* Added: `gravity_forms_infusionsoft_max_opt_in_conditions` filter to allow for a custom number of conditional opt-ins (default: 100).
* Fixed: Potential XSS security issue

= 1.5.6 =
* Fixed: More PHP warnings
* Modified: Increased the tag limit from 1,000 to 3,000

= 1.5.5 =
* Fixed: PHP warnings

= 1.5.4.2 =
* Fixed: Newly added "Conditionally Added Tags" would not save when added until saving the form first.

= 1.5.4.1 =
* Fixed: Removed limit to the number of conditional tag

= 1.5.4 =
* Fixed: Infusionsoft SDK conflicts with other plugins using the `xmlrpc` library.

= 1.5.3 =
* Fixed: Endlessly spinning on "Select the form to tap into."
* Fixed: Compatibility with Gravity Forms 1.7.7
* Improved: Updated to the latest <a href="https://github.com/joeynovak/infusionsoft-php-sdk">Infusionsoft SDK</a>

= 1.5.2 =
* Fixed: Conditional Tagging for checkbox fields (previously only worked with radio and select field types)

= 1.5.1 =
* Fixed: Some of the drop-down Conditional Tagging fields were still mis-behaving.

= 1.5 =
* Added: Notes are now added to entries with a link to the Contact's Infusionsoft URL. Also, find a neat link under "Info" box when viewing the Entry.
* Added: Conditional Tags are now available before saving a form
* Fixed: A few issues with Conditional Tags not respecting conditions
* Fixed: Admin issue caused when duplicating or removing Conditional Tags conditions.

= 1.4.1.2 =
* Fixed: Tooltip issue, which caused switching forms to break

= 1.4.1.1 =
* Added max-height to tag conditions

= 1.4.1 =
* Fixed JavaScript alerts
* Improved tooltip text and Tag descriptions

= 1.4 =
* Added really cool new feature: Conditional Tagging: add tags based on form responses!
* Added Edit Form and Preview Form to Feed list
* Actually implemented debugging form submission for Admins - previously, the checkbox didn't actually do anything!
* Fixed some PHP Warnings

= 1.3.4 =
* Fixed issue with "Invalid byte 1 of 1-byte UTF-8 sequence" bug (<a href="http://wordpress.org/support/topic/copy-and-pasting-into-text-area-results-in-error?">as reported</a>) caused by pasting text into fields
* Fixed formatting for pasting from Microsoft Word
* Fixed conflict with other Infusionsoft WordPress plugin
* Fixed date field not sending properly, <a href="http://wordpress.org/support/topic/custom-date-field-not-updating-for-existing-contacts?replies=1">as reported</a>
* Fixed empty date fields sending Unix Epoch date (1970)
* Made date field compatible with Infusionsoft servers (Eastern time)

= 1.3.3.1 =
* Fixed issue with <a href="http://wordpress.org/support/topic/first-argument-is-expected-to-be-a-valid-callback">`check_update` issue</a>
* May also fix issue where link to settings page is not visible.

= 1.3.3 =
* Fixed issue with field caching introduced in 1.3.2

= 1.3.2 =
* Fixed errors caused by tags with non-alphanumeric characters in it
* Improved tag layout for accounts with lots of tags
* Added caching for fields so if you're setting up multiple forms in a day, it'll load the fields much faster.

= 1.3.1 =
* Fixes date formatting for date fields other than Birthday

= 1.3 =
* Added support for tagging leads on a per-form basis.

= 1.2.1 =
* I forgot to actually update the file! This update actually adds support for Contact custom fields.

= 1.2 =
* Added support for Contact custom fields.

= 1.0 =
* Liftoff!
