<?php
/*
Plugin Name: Gravity Forms Infusionsoft Add-On
Plugin URI: http://katz.co
Description: Integrates Gravity Forms with Infusionsoft allowing form submissions to be automatically sent to your Infusionsoft account
Version: 1.5.12
Author: Katz Web Services, Inc.
Author URI: https://www.katzwebservices.com
Text Domain: gravity-forms-infusionsoft
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2016 Katz Web Services, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

add_action('init',  array('GFInfusionsoft', 'init'));
register_activation_hook( __FILE__, array("GFInfusionsoft", "add_permissions"));

class GFInfusionsoft {

    private static $name = "Gravity Forms Infusionsoft Add-On";
    private static $path = "gravity-forms-infusionsoft/infusionsoft.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravity-forms-infusionsoft";
    private static $version = "1.5.12";
    private static $min_gravityforms_version = "1.3.9";
    private static $is_debug = NULL;
    private static $debug_js = false;
    private static $classLoader;
    private static $settings = array(
                "key" => '',
                "appname" => '',
                "debug" => false,
            );

    //Plugin starting point. Will load appropriate files
    public static function init(){
        global $pagenow;

        load_plugin_textdomain( 'gravity-forms-infusionsoft', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

        if($pagenow === 'plugins.php') {
            add_action("admin_notices", array('GFInfusionsoft', 'is_gravity_forms_installed'), 10);
        }

        if(self::is_gravity_forms_installed(false, false) === 0){
            add_action('after_plugin_row_' . self::$path, array('GFInfusionsoft', 'plugin_row') );
           return;
        }

        add_filter('plugin_action_links', array('GFInfusionsoft', 'settings_link'), 10, 2 );

        if(!self::is_gravityforms_supported()){
           return;
        }

        if(is_admin()){

            //creates a new Settings page on Gravity Forms' settings screen
            if(self::has_access("gravityforms_infusionsoft")){
                RGForms::add_settings_page("Infusionsoft", array("GFInfusionsoft", "settings_page"), self::get_base_url() . "/images/infusionsoft_wordpress_icon_32.png");
            }

            // Enable debug with Gravity Forms Logging Add-on
            add_filter( 'gform_logging_supported', array( 'GFInfusionsoft', 'add_debug_settings' ) );
        }

        //integrating with Members plugin
        if(function_exists('members_get_capabilities'))
            add_filter('members_get_capabilities', array("GFInfusionsoft", "members_get_capabilities"));

        //creates the subnav left menu
        add_filter("gform_addon_navigation", array('GFInfusionsoft', 'create_menu'));

        if(self::is_infusionsoft_page()){

            //enqueueing sack for AJAX requests
            wp_enqueue_script(array("sack"));

            wp_enqueue_script("gforms_gravityforms", GFCommon::get_base_url() . "/js/gravityforms.js", null, GFCommon::$version);

            add_action('admin_head', array('GFInfusionsoft', 'admin_head'));

            wp_enqueue_style("gforms_css", GFCommon::get_base_url() . "/css/forms.css", null, GFCommon::$version);

            //loading data lib
            require_once(self::get_base_path() . "/data.php");

            self::setup_tooltips();

            //runs the setup when version changes
            self::setup();

         }
         else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){

            //loading data class
            require_once(self::get_base_path() . "/data.php");

            add_action('wp_ajax_rg_update_feed_active', array('GFInfusionsoft', 'update_feed_active'));
            add_action('wp_ajax_gf_select_infusionsoft_form', array('GFInfusionsoft', 'select_infusionsoft_form'));

        }
        else{
             //handling post submission. (gform_post_submission deprecated)
            add_action("gform_after_submission", array('GFInfusionsoft', 'export'), 10, 2);
        }

        add_action('gform_entry_info', array('GFInfusionsoft', 'entry_info_link_to_infusionsoft'), 10, 2);
    }

    public static function admin_head() {
        ?>
        <script>

            /**
             * Clone of gformAddListItem, with clone value clearing fixed.
             * @param {[type]} element [description]
             * @param {[type]} max     [description]
             */
            function KWSFormAddListItem(element, max){
                if(jQuery(element).hasClass("gfield_icon_disabled"))
                    return;

                var tr = jQuery(element).parent().parent();
                var clone = tr.clone();
                clone.find("input[type=text],select").val("").attr("tabindex", clone.find('input:last').attr("tabindex"));
                tr.after(clone);
                gformToggleIcons(tr.parent(), max);
                gformAdjustClasses(tr.parent());
            }

            function DeleteSetting(id){
                jQuery("#action_argument").val(id);
                jQuery("#action").val("delete");
                jQuery("#feed_form")[0].submit();
            }

            function ToggleActive(img, feed_id){
                var is_active = img.src.indexOf("active1.png") >=0
                if(is_active){
                    img.src = img.src.replace("active1.png", "active0.png");
                    jQuery(img).attr('title','<?php echo esc_js( __("Inactive", "gravity-forms-infusionsoft") ); ?>').attr('alt', '<?php echo esc_js( __("Inactive", "gravity-forms-infusionsoft") ); ?>');
                }
                else{
                    img.src = img.src.replace("active0.png", "active1.png");
                    jQuery(img).attr('title','<?php echo esc_js( __("Active", "gravity-forms-infusionsoft") ); ?>').attr('alt', '<?php echo esc_js( __("Active", "gravity-forms-infusionsoft") ); ?>');
                }

                var mysack = new sack("<?php echo esc_js( admin_url("admin-ajax.php") ); ?>" );
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "rg_update_feed_active" );
                mysack.setVar( "rg_update_feed_active", "<?php echo wp_create_nonce("rg_update_feed_active") ?>" );
                mysack.setVar( "feed_id", feed_id );
                mysack.setVar( "is_active", is_active ? 0 : 1 );
                mysack.onError = function() { alert('<?php echo esc_js( __("Ajax error while updating feed", "gravity-forms-infusionsoft" ) ); ?>' )};
                mysack.runAJAX();

                return true;
            }
        </script>
    <?php
    }

    public static function is_gravity_forms_installed($asd = '', $echo = true) {
        global $pagenow, $page; $message = '';

        $installed = 0;
        $name = self::$name;
        if(!class_exists('RGForms')) {
            if(file_exists(WP_PLUGIN_DIR.'/gravityforms/gravityforms.php')) {
                $installed = 1;
                $message .= sprintf( esc_attr__('%sGravity Forms is installed but not active. %sActivate Gravity Forms%s to use the %s plugin.%s', 'gravity-forms-infusionsoft' ), '<p>', '<strong><a href="'.wp_nonce_url(admin_url('plugins.php?action=activate&plugin=gravityforms/gravityforms.php'), 'activate-plugin_gravityforms/gravityforms.php').'">', '</a></strong>', esc_html( $name ),'</p>');
            } else {
                $message .= <<<EOD
<p><a href="https://katz.si/gravityforms?con=banner" title="Gravity Forms Contact Form Plugin for WordPress"><img src="http://gravityforms.s3.amazonaws.com/banners/728x90.gif" alt="Gravity Forms Plugin for WordPress" width="728" height="90" style="border:none;" /></a></p>
        <h3><a href="https://katz.si/gravityforms" target="_blank">Gravity Forms</a> is required for the $name</h3>
        <p>You do not have the Gravity Forms plugin installed. <a href="https://katz.si/gravityforms">Get Gravity Forms</a> today.</p>
EOD;
            }

            if(!empty($message) && $echo) {
                echo '<div id="message" class="updated">'.$message.'</div>';
            }
        } else {
            return true;
        }
        return $installed;
    }

    public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(esc_html__("%sGravity Forms%s is required. %sPurchase it today!%s", 'gravity-forms-infusionsoft'), "<a href='https://katz.si/gravityforms'>", "</a>", "<a href='https://katz.si/gravityforms'>", "</a>");
            self::display_plugin_message($message, true);
        }
    }

    public static function display_plugin_message($message, $is_error = false){
        $style = '';
        if($is_error)
            $style = 'style="background-color: #ffebe8;"';

        echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
    }

    public static function update_feed_active(){
        check_ajax_referer('rg_update_feed_active','rg_update_feed_active');
        $id = $_POST["feed_id"];
        if(!self::$debug_js) { error_reporting(0); }
        $feed = GFInfusionsoftData::get_feed($id);
        GFInfusionsoftData::update_feed($id, $feed["form_id"], $_POST["is_active"], $feed["meta"]);
    }

    //--------------   Automatic upgrade ---------------------------------------------------

    static function settings_link( $links, $file ) {
        static $this_plugin;
        if( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
        if ( $file == $this_plugin ) {
            $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=gf_infusionsoft' ) ) . '" title="' . esc_attr__('Select the Gravity Form you would like to integrate with Infusionsoft. Contacts generated by this form will be automatically added to your Infusionsoft account.', 'gravity-forms-infusionsoft') . '">' . esc_attr__('Feeds', 'gravity-forms-infusionsoft') . '</a>';
            array_unshift( $links, $settings_link ); // before other links
            $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=gf_settings&addon=Infusionsoft' ) ) . '" title="' . esc_attr__('Configure your Infusionsoft settings.', 'gravity-forms-infusionsoft') . '">' . esc_attr__('Settings', 'gravity-forms-infusionsoft') . '</a>';
            array_unshift( $links, $settings_link ); // before other links
        }
        return $links;
    }


    //Returns true if the current page is an Feed pages. Returns false if not
    private static function is_infusionsoft_page(){
        global $plugin_page; $current_page = '';
        $infusionsoft_pages = array("gf_infusionsoft");

        if(isset($_GET['page'])) {
            $current_page = trim(strtolower($_GET["page"]));
        }

        return (in_array($plugin_page, $infusionsoft_pages) || in_array($current_page, $infusionsoft_pages));
    }


    //Creates or updates database tables. Will only run when version changes
    private static function setup(){

        if(get_option("gf_infusionsoft_version") != self::$version)
            GFInfusionsoftData::update_table();

        update_option("gf_infusionsoft_version", self::$version);
    }

    static function setup_tooltips() {
        //loading Gravity Forms tooltips
        require_once(GFCommon::get_base_path() . "/tooltips.php");
        add_filter('gform_tooltips', array('GFInfusionsoft', 'tooltips'));
    }

    //Adds feed tooltips to the list of tooltips
    public static function tooltips($tooltips){
        $infusionsoft_tooltips = array(
            "infusionsoft_contact_list" => "<h6>" . esc_attr__("Infusionsoft List", "gravity-forms-infusionsoft") . "</h6>" . __("Select the Infusionsoft list you would like to add your contacts to.", "gravity-forms-infusionsoft"),
            "infusionsoft_gravity_form" => "<h6>" . esc_attr__("Gravity Form", "gravity-forms-infusionsoft") . "</h6>" . esc_attr__("Select the Gravity Form you would like to integrate with Infusionsoft. Contacts generated by this form will be automatically added to your Infusionsoft account.", "gravity-forms-infusionsoft"),
            "infusionsoft_map_fields" => "<h6>" . esc_attr__("Map Fields", "gravity-forms-infusionsoft") . "</h6>" . esc_attr__("Associate your Infusionsoft attributes to the appropriate Gravity Form fields by selecting.", "gravity-forms-infusionsoft"),
            "infusionsoft_optin_condition" => "<h6>" . esc_attr__("Opt-In Condition", "gravity-forms-infusionsoft") . "</h6>" . esc_attr__("When the opt-in condition is enabled, form submissions will only be exported to Infusionsoft when the condition is met. When disabled all form submissions will be exported.", "gravity-forms-infusionsoft"),
            "infusionsoft_tag" => "<h6>" . esc_attr__("Entry Tags", "gravity-forms-infusionsoft") . "</h6>" . esc_attr__("Add these tags to every entry (in addition to any conditionally added tags below).", "gravity-forms-infusionsoft"),
            "infusionsoft_tag_optin_condition" => "<h6>" . esc_attr__("Conditionally Added Tags", "gravity-forms-infusionsoft") . "</h6>" . esc_attr__("Tags will be added to the entry when the conditions specified are met. Does not override the 'Entry Tags' setting above (which are applied to all entries).", "gravity-forms-infusionsoft"),

        );
        return array_merge($tooltips, $infusionsoft_tooltips);
    }

    //Creates Infusionsoft left nav menu under Forms
    public static function create_menu($menus){

        // Adding submenu if user has access
        $permission = self::has_access("gravityforms_infusionsoft");
        if(!empty($permission))
            $menus[] = array("name" => "gf_infusionsoft", "label" => esc_attr__("Infusionsoft", "gravity-forms-infusionsoft"), "callback" =>  array("GFInfusionsoft", "infusionsoft_page"), "permission" => $permission);

        return $menus;
    }

    public static function is_debug() {
        if(is_null(self::$is_debug)) {
            self::$is_debug = self::get_setting('debug') && current_user_can('manage_options');
        }
        return self::$is_debug;
    }

    static public function get_setting($key) {
        $settings = self::get_settings();
        return isset($settings[$key]) ? (empty($settings[$key]) ? false : $settings[$key]) : false;
    }

    static public function get_settings() {
        $settings = get_option("gf_infusionsoft_settings");
        if(!empty($settings)) {
            self::$settings = $settings;
        } else {
            $settings = self::$settings;
        }
        return $settings;
    }

    public static function settings_page(){

        if(isset($_POST["uninstall"])){
            check_admin_referer("uninstall", "gf_infusionsoft_uninstall");
            self::uninstall();

            ?>
            <div class="updated fade" style="padding:20px;"><?php printf( esc_html__("Gravity Forms Infusionsoft Add-On has been successfully uninstalled. It can be re-activated from the %splugins page%s.", "gravity-forms-infusionsoft"), "<a href='" . esc_url( admin_url( 'plugins.php' ) ) . "'>","</a>"); ?></div>
            <?php
            return;
        }
        else if(isset($_POST["gf_infusionsoft_submit"])){
            check_admin_referer("update", "gf_infusionsoft_update");
            $settings = array(
                "key" => stripslashes($_POST["gf_infusionsoft_key"]),
                "appname" => stripslashes($_POST["gf_infusionsoft_appname"]),
                "debug" => isset($_POST["gf_infusionsoft_debug"]),
            );
            update_option("gf_infusionsoft_settings", $settings);
        }
        else{
            $settings = self::get_settings();
        }

        $valid = self::test_api(true);

?>
        <form method="post" action="<?php echo esc_url( remove_query_arg(array('refresh', 'retrieveListNames', '_wpnonce')) ); ?>" id="gform-settings">
            <?php wp_nonce_field("update", "gf_infusionsoft_update") ?>

            <h3><span style="line-height: 38px"><img src="<?php echo esc_attr( plugins_url( 'images/icon.png', __FILE__ ) ); ?>" width="38" height="38" alt="" style="float:left;" /><?php esc_html_e("Infusionsoft Settings", "gravity-forms-infusionsoft") ?></span></h3>

		<div class="gaddon-section gaddon-first-section">
			<h4 class="gaddon-section-title gf_settings_subgroup_title"><?php esc_html_e('Infusionsoft Account Information', 'gravity-forms-infusionsoft'); ?></h4>

            <table class="form-table gforms_form_settings">
	            <tbody>
	                <tr>
	                    <th scope="row"><label for="gf_infusionsoft_key"><?php esc_html_e("API Key", "gravity-forms-infusionsoft"); ?></label><span class="howto"><a href="http://help.infusionsoft.com/userguides/get-started/tips-and-tricks/api-key"><?php esc_html_e("Learn how to find your API key", 'gravity-forms-infusionsoft'); ?></a></th>
	                    <td><input type="text" id="gf_infusionsoft_key" style="padding:5px 5px 3px;" class="code" placeholder="<?php printf( esc_attr('example: %s', "gravity-forms-infusionsoft" ), 'otj4nlqbbkfttj81wx91119mr1j5g1ga7ttatzo71am3z9g8gkv24dn9ugaiphjb' ); ?>" name="gf_infusionsoft_key" size="68" value="<?php echo empty($settings["key"]) ? '' : esc_attr($settings["key"]); ?>"/></td>
	                </tr>
	                <tr>
	                    <th scope="row"><label for="gf_infusionsoft_appname"><?php esc_html_e("Account Subdomain", "gravity-forms-infusionsoft"); ?></label> </th>
	                    <td><input type="text" class="code" id="gf_infusionsoft_appname" name="gf_infusionsoft_appname" size="10" placeholder="<?php printf( esc_attr('example: %s', "gravity-forms-infusionsoft" ), 'ab123' ); ?>" value="<?php echo empty($settings["appname"]) ? '' : esc_attr($settings["appname"]); ?>"/></td>
	                </tr>
				</tbody>
            </table>
        </div>
        <div class="gaddon-section">
            <h4 class="gaddon-section-title gf_settings_subgroup_title"><?php esc_html_e('Debugging', 'gravity-forms-infusionsoft'); ?></h4>
	        <table class="form-table gforms_form_settings">
		        <tbody>
		            <tr>
	                    <th scope="row"><label for="gf_infusionsoft_debug"><?php esc_html_e("Debug Form Submissions", "gravity-forms-infusionsoft"); ?></label></th>
	                    <td><span class="checkbox"><input type="checkbox" class="checkbox" id="gf_infusionsoft_debug" name="gf_infusionsoft_debug" value="1" <?php checked($settings["debug"], true); ?>/>  <?php esc_html_e('Dubugging messages will be shown only to Administrators', 'gravity-forms-infusionsoft'); ?></span></td>
	                </tr>
	                <tr>
	                    <td colspan="2" ><input type="submit" name="gf_infusionsoft_submit" class="button button-large button-primary" value="<?php esc_html_e("Save Settings", "gravity-forms-infusionsoft") ?>" /></td>
	                </tr>
		       </tbody>
	       </table>
       </div>
        <form action="" method="post">
            <?php wp_nonce_field("uninstall", "gf_infusionsoft_uninstall") ?>
            <?php if(GFCommon::current_user_can_any("gravityforms_infusionsoft_uninstall")){ ?>
                <div class="hr-divider"></div>

                <h3><?php esc_html_e("Uninstall Infusionsoft Add-On", "gravity-forms-infusionsoft") ?></h3>
                <div class="delete-alert"><?php esc_html_e("Warning! This operation deletes ALL Infusionsoft Feeds.", "gravity-forms-infusionsoft") ?>
                    <?php
                    $uninstall_button = '<input type="submit" name="uninstall" value="' . esc_attr__("Uninstall Infusionsoft Add-On", "gravity-forms-infusionsoft") . '" class="button" onclick="return confirm(\'' . esc_js( __("Warning! ALL Infusionsoft Feeds will be deleted. This cannot be undone. 'OK' to delete, 'Cancel' to stop", "gravity-forms-infusionsoft") ) . '\');"/>';
                    echo apply_filters("gform_infusionsoft_uninstall_button", $uninstall_button);
                    ?>
                </div>
            <?php } ?>
        </form>
        <?php
    }

    public static function infusionsoft_page(){
        $view = isset($_GET["view"]) ? $_GET["view"] : '';
        if($view == "edit")
            self::edit_page($_GET["id"]);
        else
            self::list_page();
    }

    //Displays the Infusionsoft feeds list page
    private static function list_page(){
        if(!self::is_gravityforms_supported()){
            die( sprintf( __("The Infusionsoft Add-On requires Gravity Forms %s. Upgrade automatically on the %sPlugin page%s.", "gravity-forms-infusionsoft"), self::$min_gravityforms_version, "<a href='plugins.php'>", "</a>") );
        }

        if(isset($_POST["action"]) && $_POST["action"] == "delete"){
            check_admin_referer("list_action", "gf_infusionsoft_list");

            $id = absint($_POST["action_argument"]);
            GFInfusionsoftData::delete_feed($id);
            ?>
            <div class="updated fade" style="padding:6px"><?php esc_html_e("Feed deleted.", "gravity-forms-infusionsoft") ?></div>
            <?php
        }
        else if (!empty($_POST["bulk_action"])){
            check_admin_referer("list_action", "gf_infusionsoft_list");
            $selected_feeds = $_POST["feed"];
            if(is_array($selected_feeds)){
                foreach($selected_feeds as $feed_id)
                    GFInfusionsoftData::delete_feed($feed_id);
            }
            ?>
            <div class="updated fade" style="padding:6px"><?php esc_html_e("Feeds deleted.", "gravity-forms-infusionsoft") ?></div>
            <?php
        }

        ?>
        <div class="wrap">
            <a href="https://katz.si/inhome"><img alt="<?php esc_attr_e("Infusionsoft Feeds", "gravity-forms-infusionsoft") ?>" src="<?php echo self::get_base_url()?>/images/infusion-logo.png" style="margin:15px 7px 0 0; display:block;" width="200" height="33" /></a>
            <h2><?php esc_html_e("Infusionsoft Feeds", "gravity-forms-infusionsoft"); ?>
            <a class="button add-new-h2" href="admin.php?page=gf_infusionsoft&view=edit&id=0"><?php esc_html_e("Add New", "gravity-forms-infusionsoft") ?></a>
            </h2>

            <div class="updated" id="message" style="margin-top:20px;">
                <p><?php _e('Do you like this free plugin? <a href="https://katz.si/gfratein">Please review it on WordPress.org</a>! <small class="description alignright">Note: You must be logged in to WordPress.org to leave a review!</small>', 'gravity-forms-infusionsoft'); ?></p>
            </div>

            <div class="clear"></div>

            <ul class="subsubsub" style="margin-top:0;">
                <li><a href="<?php echo esc_url( admin_url('admin.php?page=gf_settings&addon=Infusionsoft') ); ?>"><?php esc_html_e('Infusionsoft Settings', 'gravity-forms-infusionsoft'); ?></a> |</li>
                <li><a href="<?php echo esc_url( admin_url('admin.php?page=gf_infusionsoft') ); ?>" class="current"><?php esc_html_e('Infusionsoft Feeds', 'gravity-forms-infusionsoft'); ?></a></li>
            </ul>

            <form id="feed_form" method="post">
                <?php wp_nonce_field('list_action', 'gf_infusionsoft_list') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>

                <div class="tablenav">
                    <div class="alignleft actions" style="padding:8px 0 7px; 0">
                        <label class="hidden" for="bulk_action"><?php esc_html_e("Bulk action", "gravity-forms-infusionsoft") ?></label>
                        <select name="bulk_action" id="bulk_action">
                            <option value=''> <?php esc_html_e("Bulk action", "gravity-forms-infusionsoft") ?> </option>
                            <option value='delete'><?php esc_html_e("Delete", "gravity-forms-infusionsoft") ?></option>
                        </select>
                        <?php
                        echo '<input type="submit" class="button" value="' . esc_attr__("Apply", "gravity-forms-infusionsoft") . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . esc_attr__("Delete selected feeds? ", "gravity-forms-infusionsoft") . esc_js( __("'Cancel' to stop, 'OK' to delete.", "gravity-forms-infusionsoft") ) .'\')) { return false; } return true;"/>';
                        ?>
                    </div>
                </div>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php esc_html_e("Form", "gravity-forms-infusionsoft") ?></th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php esc_html_e("Form", "gravity-forms-infusionsoft") ?></th>
                        </tr>
                    </tfoot>

                    <tbody class="list:user user-list">
                        <?php

                        $settings = GFInfusionsoftData::get_feeds();
                        if(is_array($settings) && !empty($settings)){
                            foreach($settings as $setting){
                                ?>
                                <tr class='author-self status-inherit' valign="top">
                                    <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo $setting["id"] ?>"/></th>
                                    <td><img src="<?php echo self::get_base_url() ?>/images/active<?php echo intval($setting["is_active"]) ?>.png" alt="<?php echo $setting["is_active"] ? esc_attr__("Active", "gravity-forms-infusionsoft") : esc_attr__("Inactive", "gravity-forms-infusionsoft");?>" title="<?php echo $setting["is_active"] ? esc_attr__("Active", "gravity-forms-infusionsoft") : esc_attr__("Inactive", "gravity-forms-infusionsoft");?>" onclick="ToggleActive(this, <?php echo $setting['id'] ?>); " /></td>
                                    <td class="column-title">
                                        <a href="admin.php?page=gf_infusionsoft&amp;view=edit&amp;id=<?php echo $setting["id"] ?>" title="<?php esc_attr_e("Edit", "gravity-forms-infusionsoft") ?>"><?php echo $setting["form_title"] ?></a>
                                        <div class="row-actions">
                                            <span class="edit">
                                            <a title="Edit this setting" href="admin.php?page=gf_infusionsoft&amp;view=edit&amp;id=<?php echo $setting["id"] ?>" title="<?php esc_attr_e("Edit", "gravity-forms-infusionsoft") ?>"><?php esc_html_e("Edit", "gravity-forms-infusionsoft") ?></a>
                                            |
                                            </span>

                                            <span class="edit">
                                            <a title="<?php esc_attr_e("Delete", "gravity-forms-infusionsoft") ?>" href="javascript: if(confirm('<?php echo esc_js( __( "Delete this feed? 'Cancel' to stop, 'OK' to delete.", "gravity-forms-infusionsoft") ); ?>')){ DeleteSetting(<?php echo $setting["id"] ?>);}"><?php esc_html_e("Delete", "gravity-forms-infusionsoft")?></a>
                                            |
                                            </span>

                                            <span class="edit">
                                            <a title="<?php esc_attr_e("Edit Form", "gravity-forms-infusionsoft") ?>" href="<?php echo esc_url( add_query_arg(array('page' => 'gf_edit_forms', 'id' => $setting['form_id']), admin_url('admin.php')) ); ?>"><?php esc_html_e("Edit Form", "gravity-forms-infusionsoft")?></a>
                                            |
                                            </span>

                                            <span class="edit">
                                            <a title="<?php esc_attr_e("Preview Form", "gravity-forms-infusionsoft") ?>" href="<?php echo esc_url( add_query_arg(array('gf_page' => 'preview', 'id' => $setting['form_id']), site_url()) ); ?>"><?php esc_html_e("Preview Form", "gravity-forms-infusionsoft")?></a>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        else {
                            $valid = self::test_api();
                            if(!empty($valid)){
                                ?>
                                <tr>
                                    <td colspan="3" style="padding:20px;">
                                        <?php printf( esc_html__("You don't have any Infusionsoft feeds configured. Let's go %screate one%s!", "gravity-forms-infusionsoft"), '<a href="'.esc_url( admin_url('admin.php?page=gf_infusionsoft&view=edit&id=0') ).'">', "</a>"); ?>
                                    </td>
                                </tr>
                                <?php
                            } else{
                                ?>
                                <tr>
                                    <td colspan="3" style="padding:20px;">
                                        <?php printf( esc_html__("To get started, please configure your %sInfusionsoft Settings%s.", "gravity-forms-infusionsoft"), '<a href="'.esc_url( admin_url('admin.php?page=gf_settings&addon=Infusionsoft') ).'">', "</a>"); ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    public static function get_api(){

        if(!class_exists("Infusionsoft_Classloader"))
            require_once("Infusionsoft/infusionsoft.php");

        self::$classLoader = new Infusionsoft_Classloader();

        $infusionsoft_host = sprintf('%s.infusionsoft.com', self::get_setting('appname'));
        $infusionsoft_api_key = self::get_setting('key');

        //Below is just some magic...  Unless you are going to be communicating with more than one APP at the SAME TIME.  You can ignore it.
        Infusionsoft_AppPool::addApp(new Infusionsoft_App($infusionsoft_host, $infusionsoft_api_key, 443));

    }

    private static function test_api($echo = false) {
        $works = true; $message = ''; $class = '';
        $key = self::get_setting('key');
        $appname = self::get_setting('appname');

        if(empty($appname) && empty($key)) {

            $message = sprintf( '%s<h3>%s</h3><p>%s</p><p><a href="https://katz.si/indemo" class="button button-primary">%s</a> <a href="https://katz.si/inhome" class="button button-secondary">%s</a></p>',
		        '<a href="https://katz.si/inhome"><img alt="Infusionsoft Logo" src="' . esc_attr( self::get_base_url().'/images/infusion-logo.png' ) .'" style="display:block; margin:15px 7px 0 0;" width="200" height="33"/></a>',
		        sprintf( esc_html__('Don\'t have an %sInfusionsoft%s account?', 'gravity-forms-infusionsoft'), '<a href="https://katz.si/inhome">', '</a>' ),
		        esc_html__('This plugin requires an Infusionsoft account. If you have an Infusionsoft account, fill out the settings form below. Otherwise, you should sign up for an Infusionsoft account and start taking advantage of the world\'s best CRM.', 'gravity-forms-infusionsoft'),
		        esc_html__('Sign up for Infusionsoft Today!', 'gravity-forms-infusionsoft'),
		        esc_html__('Visit Infusionsoft.com', 'gravity-forms-infusionsoft')
            );

            $works = false;
            $class = 'updated';
        } else if(empty($appname)) {

            $message = sprintf( esc_html__("Your Account Subdomain (also called \"Application Name\") is required. %sEnter it below%s.", 'gravity-forms-infusionsoft'), "<label for='gf_infusionsoft_appname'><a>", "</a></label>" );
            $message .= "<span class='howto'>";
            $message .= sprintf( esc_attr__("If you access your Infusionsoft account from %sexample123%s.infusionsoft.com%s, your Account Subdomain is %sexample123%s", 'gravity-forms-infusionsoft'), "<span class='code' style='font-style:normal'><strong>", "</strong>", "</span>", "<strong class='code' style='font-style:normal;'>", "</strong>" );
            $message .= "</span>";

            $works = false;
        } elseif(empty($key)) {
            $message = wpautop( sprintf( esc_attr__('Your API Key is required, please %senter your API key below%s.', 'gravity-forms-infusionsoft'), '<label for="gf_infusionsoft_key"><a>', '</a></label>' ) );
            $works = false;
        } else {
            self::get_api();

            $app = Infusionsoft_AppPool::getApp();

            if(Infusionsoft_DataService::ping('ProductService')){

                try {
                    Infusionsoft_WebFormService::getMap($app);
                    $message .= wpautop(sprintf(esc_attr__("It works: everything is communicating properly and your settings are correct. Now go %sconfigure form integration with Infusionsoft%s!", "gravity-forms-infusionsoft"), '<a href="'.esc_url( admin_url('admin.php?page=gf_infusionsoft') ).'">', '</a>'));
                }
                catch(Exception $e){
                    $works = false;
                    if(strpos($e->getMessage(), "[InvalidKey]") !== FALSE){
                        $message .= wpautop(sprintf(esc_attr__('Your API Key is not correct, please double check your %sAPI key setting%s.', 'gravity-forms-infusionsoft'), '<label for="gf_infusionsoft_key"><a>', '</a></label>'));
                    }
                    else{
                        $message .= wpautop(sprintf(esc_attr__('Failure to connect: %s', 'gravity-forms-infusionsoft'), $e->error));
                    }
                }
            }
            else{
                $works = false;
                $message .= wpautop(esc_attr__('Something is wrong. See below for details, check your settings and try again.', 'gravity-forms-infusionsoft'));
            }

            $exceptions = Infusionsoft_AppPool::getApp()->getExceptions();

            if(!empty($exceptions)) {
                $message .= '<ul class="ul-square">';
                foreach($exceptions as $exception){
                    $messagetext = str_replace('[', esc_attr__('Error key: [', 'gravity-forms-infusionsoft'), str_replace(']', ']<br />Error message: ', $exception->getMessage()));
                    $message .= '<li style="list-style:square;">'.$messagetext.'</li>';
                }
                $message .= '</ul>';
            }
        }

        $class = empty($class) ? ($works ? "updated" : "error") : $class;

        if($message && $echo) {
            echo sprintf('<div id="message" class="%s">%s</div>', $class, wpautop($message));
        }

        return $works;
    }

    static function r($content, $die = false) {
        echo '<pre>'.print_r($content, true).'</pre>';
        if($die) { die(); }
    }

    private static function edit_page(){
        if(isset($_REQUEST['cache'])) {
            delete_site_transient('gf_infusionsoft_default_fields');
            delete_site_transient('gf_infusionsoft_custom_fields');
            delete_site_transient( 'gf_infusionsoft_tag_list'); //since 1.5.5
        }
        ?>
        <style type="text/css">
            label span.howto { cursor: default; }
            .infusionsoft_col_heading, .infusionsoft_tag_optin_condition_fields { padding-bottom: .5em; border-bottom: 1px solid #ccc; }
            .infusionsoft_col_heading { font-weight:bold; width:50%; }
            .infusionsoft_tag_optin_condition_fields { margin-bottom: .5em; }
            #infusionsoft_field_list table, #infusionsoft_tag_optin table { width: 500px; border-collapse: collapse; margin-top: 1em; }
            .infusionsoft_field_cell {padding: 6px 17px 0 0; margin-right:15px; vertical-align: text-top; font-weight: normal;}
            ul.infusionsoft_checkboxes { max-height: 120px; overflow-y: auto;}
            ul.infusionsoft_map_field_groupId_checkboxes { max-height: 300px; }
            .gfield_required{color:red;}
            .feeds_validation_error{ background-color:#FFDFDF;}
            .feeds_validation_error td{ margin-top:4px; margin-bottom:6px; padding-top:6px; padding-bottom:6px; border-top:1px dotted #C89797; border-bottom:1px dotted #C89797}

            .left_header{float:left; width:200px; padding-right: 20px;}
            #infusionsoft_field_list .left_header { margin-top: 1em; }
            .margin_vertical_10{margin: 20px 0;}
            #gf_infusionsoft_list { margin-left:220px; padding-top: 1px }
            #infusionsoft_doubleoptin_warning{padding-left: 5px; padding-bottom:4px; font-size: 10px;}
        </style>
        <script>
            var form = [];
        </script>
        <div class="wrap">
            <a href="https://katz.si/inhome"><img alt="<?php esc_attr_e("Infusionsoft Feeds", "gravity-forms-infusionsoft") ?>" src="<?php echo self::get_base_url()?>/images/infusion-logo.png" style="display:block; margin:15px 7px 0 0;" width="200" height="33"/></a>
            <h2><?php esc_html_e("Infusionsoft Feeds", "gravity-forms-infusionsoft"); ?></h2>
            <ul class="subsubsub">
                <li><a href="<?php echo esc_url( admin_url('admin.php?page=gf_settings&addon=Infusionsoft') ); ?>"><?php esc_html_e('Infusionsoft Settings', 'gravity-forms-infusionsoft'); ?></a> |</li>
                <li><a href="<?php echo esc_url( admin_url('admin.php?page=gf_infusionsoft') ); ?>"><?php esc_html_e('Infusionsoft Feeds', 'gravity-forms-infusionsoft'); ?></a></li>
            </ul>
        <div class="clear"></div>
        <?php
        //getting Infusionsoft API

        $api = self::get_api();

        //ensures valid credentials were entered in the settings page
        if(($api === false) || is_string($api)) {
            ?>
            <div class="error" id="message" style="margin-top:20px;"><?php echo wpautop(sprintf(esc_attr__("We are unable to login to Infusionsoft with the provided username and API key. Please make sure they are valid in the %sSettings Page%s", "gravity-forms-infusionsoft"), "<a href='?page=gf_settings&addon=Infusionsoft'>", "</a>")); ?></div>
            <?php
            return;
        }

        //getting setting id (0 when creating a new one)
        $id = !empty($_POST["infusionsoft_setting_id"]) ? $_POST["infusionsoft_setting_id"] : absint($_GET["id"]);
        $config = empty($id) ? array("meta" => array(), "is_active" => true) : GFInfusionsoftData::get_feed($id);


        //getting merge vars
        $merge_vars = array();

        //updating meta information
        if(isset($_POST["gf_infusionsoft_submit"])){
            $objectType = $list_names = array();
            $list = stripslashes(@$_POST["gf_infusionsoft_list"]);
            $config["meta"]["contact_object_name"] = $list;
            $config["form_id"] = absint($_POST["gf_infusionsoft_form"]);

            $is_valid = true;

            $merge_vars = self::get_fields();

            $field_map = array();
            foreach($merge_vars as $key => $var){
                $field_name = "infusionsoft_map_field_" . $var['tag'];
                if(isset($_POST[$field_name])) {
                    if(is_array($_POST[$field_name])) {
                        foreach($_POST[$field_name] as $k => $v) {
                            $_POST[$field_name][$k] = stripslashes($v);
                        }
                        $mapped_field = $_POST[$field_name];
                    } else {
                        $mapped_field = stripslashes($_POST[$field_name]);
                    }
                }
                if(!empty($mapped_field)){
                    $field_map[$var['tag']] = $mapped_field;
                }
                else{
                    unset($field_map[$var['tag']]);
                    if(!empty($var['req'])) {
                        $is_valid = false;
                    }
                }
                unset($_POST["{$field_name}"]);
            }

            $config["meta"]["field_map"] = $field_map;
            $config["meta"]["optin_enabled"] = !empty($_POST["infusionsoft_optin_enable"]) ? true : false;
            if( $config["meta"]["optin_enabled"] ) {
                $config["meta"]["optin_field_id"] = isset( $_POST["infusionsoft_optin_field_id"] ) ? $_POST["infusionsoft_optin_field_id"] : '';
                $config["meta"]["optin_operator"] = isset( $_POST["infusionsoft_optin_operator"] ) ? $_POST["infusionsoft_optin_operator"] : '';
                $config["meta"]["optin_value"] = isset( $_POST["infusionsoft_optin_value"] ) ? $_POST["infusionsoft_optin_value"] : '';
            } else {
                $config["meta"]["optin_field_id"] =  $config["meta"]["optin_operator"] = $config["meta"]["optin_value"] = '';
            }

            $config["meta"]["tag_optin_enabled"] = !empty($_POST["infusionsoft_tag_optin_enable"]) ? true : false;
            $config["meta"]["tag_optin_field_id"] = !empty($config["meta"]["tag_optin_enabled"]) ? isset($_POST["infusionsoft_tag_optin_field_id"]) ? @$_POST["infusionsoft_tag_optin_field_id"] : '' : "";
            $config["meta"]["tag_optin_operator"] = !empty($config["meta"]["tag_optin_enabled"]) ? isset($_POST["infusionsoft_tag_optin_operator"]) ? @$_POST["infusionsoft_tag_optin_operator"] : '' : "";
            $config["meta"]["tag_optin_tags"] = !empty($config["meta"]["tag_optin_enabled"]) ? @$_POST["tag_optin_tags"] : "";
            $config["meta"]["tag_optin_value"] = !empty($config["meta"]["tag_optin_enabled"]) ? @$_POST["infusionsoft_tag_optin_value"] : "";

            if($is_valid){
                $id = GFInfusionsoftData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
                ?>
                <div id="message" class="updated fade" style="margin-top:10px;"><p><?php echo sprintf(esc_html__("Feed Updated. %sback to list%s", "gravity-forms-infusionsoft"), "<a href='?page=gf_infusionsoft'>", "</a>") ?></p>
                    <input type="hidden" name="infusionsoft_setting_id" value="<?php echo $id ?>"/>
                </div>
                <?php
            }
            else{
                ?>
                <div class="error" style="padding:6px"><?php echo esc_html__("Feed could not be updated. Please enter all required information below.", "gravity-forms-infusionsoft") ?></div>
                <?php
            }

        }

        self::setup_tooltips();

?>
        <form method="post" action="<?php echo remove_query_arg('refresh'); ?>">
            <input type="hidden" name="infusionsoft_setting_id" value="<?php echo $id ?>"/>

            <div id="infusionsoft_form_container" valign="top" class="margin_vertical_10">

                <h2><?php esc_html_e('1. Select the form to tap into.', "gravity-forms-infusionsoft"); ?></h2>
                <?php
                $forms = RGFormsModel::get_forms();

                if(isset($config["form_id"])) {
                    foreach($forms as $form) {
                        if($form->id == $config["form_id"]) {
                            echo '<h3 style="margin:0; padding:0 0 1em 1.75em; font-weight:normal;">'.sprintf(esc_html__('(Currently linked with %s)', "gravity-forms-infusionsoft"), $form->title).'</h3>';
                        }
                    }
                }

                ?>
                <label for="gf_infusionsoft_form" class="left_header"><?php esc_html_e("Gravity Form", "gravity-forms-infusionsoft"); ?> <?php gform_tooltip("infusionsoft_gravity_form") ?></label>

                <select id="gf_infusionsoft_form" name="gf_infusionsoft_form">
                <option value=""><?php esc_html_e("Select a form", "gravity-forms-infusionsoft"); ?> </option>
                <?php

                foreach($forms as $form){
                    $current_form = !empty( $config["form_id"] ) ? $config["form_id"] : '';
                    ?>
                    <option value="<?php echo absint($form->id) ?>"  <?php selected( absint( $form->id ), absint( $current_form ), true); ?>><?php echo esc_html($form->title) ?></option>
                    <?php
                }
                ?>
                </select>
                &nbsp;&nbsp;
                <img src="<?php echo esc_url( GFInfusionsoft::get_base_url() ); ?>/images/loading.gif" id="infusionsoft_wait" style="display: none;"/>
            </div>

            <div class="clear"></div>
            <div id="infusionsoft_field_group" valign="top" <?php echo empty($config["form_id"]) ? "style='display:none;'" : "" ?>>
                <div id="infusionsoft_field_container" valign="top" class="margin_vertical_10" >
                    <h2><?php esc_html_e('2. Map form fields to Infusionsoft fields.', "gravity-forms-infusionsoft"); ?></h2>
                    <h3 class="description"><?php esc_html_e('About field mapping:', "gravity-forms-infusionsoft"); ?></h2>
                    <label for="infusionsoft_fields" class="left_header"><?php esc_html_e("Standard Fields", "gravity-forms-infusionsoft"); ?> <?php gform_tooltip("infusionsoft_map_fields") ?> <span class="howto"><a href="<?php echo add_query_arg(array( 'id'=> $id, 'cache' => 0)); ?>"><?php esc_html_e('Refresh Fields &amp; Tags', "gravity-forms-infusionsoft"); ?></a></span></label>
                    <div id="infusionsoft_field_list">
                    <?php

                    if(!empty($config["form_id"])){

                        //getting list of all Infusionsoft merge variables for the selected contact list
                        if(empty($merge_vars))
                            $merge_vars = self::get_fields($config['meta']['contact_object_name']);

                        //getting field map UI
                        echo self::get_field_mapping($config, $config["form_id"], $merge_vars);

                        //getting list of selection fields to be used by the optin
                        $form_meta = RGFormsModel::get_form_meta($config["form_id"]);
                        //$selection_fields = GFCommon::get_selection_fields($form_meta, $config["meta"]["optin_field_id"]);
                        $selection_fields = self::get_selection_fields($form_meta, $config["meta"]["optin_field_id"] );

                       $tag_selection_fields = true;
                    } else {
                        $selection_fields = $tag_selection_fields = false;
                    }

                    ?>
                    </div>
                    <div class="clear"></div>
                </div>


            <div id="infusionsoft_tags_optin_container" valign="top" class="margin_vertical_10 ginput_container ginput_container_list ginput_list">
                    <label for="infusionsoft_tag_optin" class="left_header"><?php esc_html_e("Conditionally Added Tags", "gravity-forms-infusionsoft"); ?> <?php gform_tooltip("infusionsoft_tag_optin_condition") ?></label>
                    <div id="infusionsoft_tag_optin">
                        <table class="gfield_list gfield_list_container" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <input type="checkbox" id="infusionsoft_tag_optin_enable" name="infusionsoft_tag_optin_enable" value="1" onclick="if(this.checked){jQuery('#infusionsoft_tag_optin_condition_field_container').show('slow'); SetOptin('','', 0); } else{jQuery('#infusionsoft_tag_optin_condition_field_container').hide('slow');}" <?php echo !empty($config["meta"]["tag_optin_enabled"]) ? "checked='checked'" : ""?>/>
                                    <label for="infusionsoft_tag_optin_enable"><?php esc_html_e("Enable", "gravity-forms-infusionsoft"); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div id="infusionsoft_tag_optin_condition_field_container" <?php echo empty($config["meta"]["tag_optin_enabled"]) ? "style='display:none'" : ""?> class="ginput_container ginput_list">
                                        <div class='ginput_container ginput_list'>
                                            <table class='gfield_list'>
                                            <?php
                                            $rownum = 1;
                                            $tabindex = GFCommon::get_tabindex();
                                            $maxRow = apply_filters('gravity_forms_infusionsoft_max_opt_in_conditions', 100);
                                            $colnum = 1;
                                            $fields = !empty($config["meta"]["tag_optin_field_id"]) ? $config["meta"]["tag_optin_field_id"] : array(0 => '');
                                            $disabled_icon_class = !empty($maxRow) && count($fields) >= $maxRow ? "gfield_icon_disabled" : "";
                                            $delete_display = count($fields) === 1 ? "visibility:hidden;" : "";
                                            $disabled_text = "disabled='disabled'";
                                            $tags = self::get_tag_list();
                                            $list = '';
                                            $add_icon = esc_url( GFCommon::get_base_url() . "/images/add.png" );
                                            $delete_icon = esc_url( GFCommon::get_base_url() . "/images/remove.png" );

                                            foreach($fields as $key => $item) {
                                                    $odd_even = ($rownum % 2) == 0 ? "even" : "odd";

                                                    if(!empty($form_meta) && isset($config["meta"]["tag_optin_field_id"][$key])) {
                                                        $tag_selection_fields = self::get_selection_fields($form_meta, $config["meta"]["tag_optin_field_id"][$key]);
                                                    }

                                                    $list .= "
                                            <tr class='gfield_list_row gfield_list_row_{$odd_even} gfield_list_group' id='gfield_list_row_{$key}' data-fieldid='{$key}'>
                                                <td class='gfield_list_cell'>

                                                    <div class='infusionsoft_tag_optin_condition_fields infusionsoft_optin_condition_fields'";
                                                        $list .= empty($tag_selection_fields) ? "style='display:none'" : "";
                                                        $list .= '>';

                                                        #if(!empty($tag_selection_fields)) {
                                                            $list .= '<div>' . esc_html__("If these conditions are met:", "gravity-forms-infusionsoft") . '</div>';

                                                            $list .= '
                                                                <select id="infusionsoft_tag_optin_field_id_'.$key.'" name="infusionsoft_tag_optin_field_id[]" class="optin_select optin_tag_field_id">'.$tag_selection_fields.'</select>

                                                               <select id="infusionsoft_tag_optin_operator_'.$key.'" name="infusionsoft_tag_optin_operator[]" />
                                                                    <option value="is"'.selected(isset($config["meta"]["tag_optin_operator"][$key]) && $config["meta"]["tag_optin_operator"][$key] == "is", true, false).'>'.esc_html__("is", "gravity-forms-infusionsoft") .'</option>
                                                                    <option value="isnot"'. selected(isset($config["meta"]["tag_optin_operator"][$key]) && $config["meta"]["tag_optin_operator"][$key] == "isnot", true, false).'>'.esc_html__("is not", "gravity-forms-infusionsoft") .'</option>
                                                                </select>

                                                                <select id="infusionsoft_tag_optin_value_'.$key.'" name="infusionsoft_tag_optin_value[]" class="optin_select optin_value"></select>
                                                            ';
                                                            $list .= '<p>'.esc_html__("Assign Entry the following tags: ", "gravity-forms-infusionsoft").'</p>';
                                                            $list .= self::get_mapped_field_checkbox("[$key]", (!empty($config['meta']['tag_optin_tags'][$key]) ? $config['meta']['tag_optin_tags'][$key] : array()), $tags, 'tag_optin_tags');
                                                      #  }
                                                            $list .= '
                                                       </div>
                                                        <div class="infusionsoft_optin_condition_message"';
                                                        $list .= !empty($tag_selection_fields) ? "style='display:none'" : "";
                                                        $list .= '>';
                                                        if(empty($id)) {
                                                            $list .= esc_html__("Please save the Feed to configure conditional tagging. ", "gravity-forms-infusionsoft");
                                                        }
                                                        $list .= esc_html__("To create an Opt-In condition, your form must have a drop down, checkbox or multiple choice field.", "gravity-forms-infusionsoft");
                                                        $list .= '
                                                        </div>
                                                    </div>
                                                </td>';

                                                $list .= "
                                                <td class='gfield_list_icons'>
                                                    <img src='{$add_icon}' class='add_list_item {$disabled_icon_class}' {$disabled_text} title='" . esc_attr__("Add a condition", "gravity-forms-infusionsoft") . "' alt='" . esc_attr__("Add a condition", "gravity-forms-infusionsoft") . "' onclick='KWSFormAddListItem(this, {$maxRow}); GFInfusionsoftUpdateListIDs(this)' style='cursor:pointer; margin:0 3px;' />
                                                     <img src='{$delete_icon}' {$disabled_text} title='" . esc_attr__("Remove this condition", "gravity-forms-infusionsoft") . "' alt='" . esc_attr__("Remove this condition", "gravity-forms-infusionsoft") . "' class='delete_list_item' style='cursor:pointer; {$delete_display}' onclick='gformDeleteListItem(this, {$maxRow});  GFInfusionsoftUpdateListIDs(this)' />
                                                </td>";


                                                $list .= "</tr>";

                                                if(!empty($maxRow) && $rownum >= $maxRow)
                                                    break;

                                                $rownum++;
                                            }

                $list .="</tbody></table></div>";

                echo $list;
                ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div id="infusionsoft_optin_container" valign="top" class="margin_vertical_10">
                    <label for="infusionsoft_optin" class="left_header"><?php esc_html_e("Opt-In Condition", "gravity-forms-infusionsoft"); ?> <?php gform_tooltip("infusionsoft_optin_condition") ?></label>
                    <div id="infusionsoft_optin">
                        <table>
                            <tr>
                                <td>
                                    <input type="checkbox" id="infusionsoft_optin_enable" name="infusionsoft_optin_enable" value="1" onclick="if(this.checked){jQuery('#infusionsoft_optin_condition_field_container').show('slow'); SetOptinCondition();} else{jQuery('#infusionsoft_optin_condition_field_container').hide('slow');}" <?php echo !empty($config["meta"]["optin_enabled"]) ? "checked='checked'" : ""?>/>
                                    <label for="infusionsoft_optin_enable"><?php esc_html_e("Enable", "gravity-forms-infusionsoft"); ?></label>
                                </td>
                            </tr>
                            <tr class="gfield_list_row" data-fieldid="optin">
                                <td>
                                    <div id="infusionsoft_optin_condition_field_container" <?php echo empty($config["meta"]["optin_enabled"]) ? "style='display:none'" : ""?>>
                                        <div class="infusionsoft_optin_condition_fields" <?php echo empty($selection_fields) ? "style='display:none'" : ""?>>
                                            <?php esc_html_e("Export to Infusionsoft if ", "gravity-forms-infusionsoft") ?>

                                            <select id="infusionsoft_optin_field_id" name="infusionsoft_optin_field_id" class='optin_select'><?php echo $selection_fields ?></select>
                                            <select id="infusionsoft_optin_operator" name="infusionsoft_optin_operator">
                                                <option value="is" <?php echo (isset($config["meta"]["optin_operator"]) && $config["meta"]["optin_operator"] == "is") ? "selected='selected'" : "" ?>><?php esc_html_e("is", "gravity-forms-infusionsoft") ?></option>
                                                <option value="isnot" <?php echo (isset($config["meta"]["optin_operator"]) && $config["meta"]["optin_operator"] == "isnot") ? "selected='selected'" : "" ?>><?php esc_html_e("is not", "gravity-forms-infusionsoft") ?></option>
                                            </select>
                                            <select id="infusionsoft_optin_value" name="infusionsoft_optin_value" class='optin_select optin_value'>
                                            </select>

                                        </div>
                                        <div class="infusionsoft_optin_condition_message" <?php echo !empty($selection_fields) ? "style='display:none'" : ""?>>
                                            <?php esc_html_e("To create an Opt-In condition, your form must have a drop down, checkbox or multiple choice field.", "gravityform", 'gravity-forms-infusionsoft') ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <script>
                        <?php
                        if(!empty($config["form_id"])){
                            // this form meta contains quiz results' fields
                            $form_extended = self::get_form_meta_extended( $config["form_id"] );
                            ?>
                            //creating Javascript form object
                            form = <?php echo GFCommon::json_encode( $form_extended )?> ;

                            function SetOptinCondition() {
                                var selectedField = "<?php echo str_replace('"', '\"', $config["meta"]["optin_field_id"])?>";
                                var selectedValue = "<?php echo str_replace('"', '\"', $config["meta"]["optin_value"])?>";
                                if( selectedField !== jQuery('#infusionsoft_optin_field_id').val() ) {
                                    selectedValue = '';
                                }

                                jQuery("#infusionsoft_optin_value").html(GetFieldValues(jQuery('#infusionsoft_optin_field_id').val(), selectedValue, 50));
                            }
                            //initializing drop downs
                            jQuery(document).ready(function(){

                                SetOptinCondition();
                            <?php

                                $fields = !empty($config["meta"]["tag_optin_field_id"]) ? $config["meta"]["tag_optin_field_id"] : array('');

                                foreach($fields as $key => $field) {
                                    $value = isset($config["meta"]["tag_optin_value"][$key]) ? $config["meta"]["tag_optin_value"][$key] : '';
                                    echo 'tagSelectedField = "'.str_replace('"', '\"', $field).'";'."\n";
                                    echo 'tagSelectedValue = "'.str_replace('"', '\"', $value).'";'."\n";
                                    echo 'SetOptin(tagSelectedField, tagSelectedValue, '.$key.');'."\n";
                                }
                            ?>
                            });
                        <?php
                        } else {
                        ?>
                        function SetOptinCondition() {
                            SetOptin('','', 'optin');
                        }
                        <?php
                        }
                        ?>
                    </script>
                </div>

                <div id="infusionsoft_submit_container" class="margin_vertical_10">
                    <input type="submit" name="gf_infusionsoft_submit" value="<?php echo empty($id) ? esc_attr__("Save Feed", "gravity-forms-infusionsoft") : esc_attr__("Update Feed", "gravity-forms-infusionsoft"); ?>" class="button-primary"/>
                </div>
            </div>
        </form>
        </div>

<script>

    jQuery(document).ready(function($) {

    <?php if(isset($_REQUEST['id'])) { ?>
        $('#infusionsoft_field_list').on('load', function() {
            $('.infusionsoft_field_cell select').each(function() {
                var $select = $(this);
                var label = $.trim($('label[for='+$(this).prop('name')+']').text()).replace(' *', '');

                if($select.val() === '') {
                    $('option', $select).each(function() {

                        if($(this).text() === label) {
                            if($().prop) {
                                $(this).prop('selected', true);
                            } else {
                                $(this).attr('selected', true);
                            }
                        }
                    });
                }
            });
        });
    <?php } ?>

        <?php if(empty($config["form_id"])){ ?>
        SelectForm($('#gf_infusionsoft_form').val());
        <?php } ?>

        $('body').on('change', '.optin_tag_field_id', function() {
            var $parent = $(this).parents('tr');
            var key = $parent.data('fieldid');
            var value = $(this).val();
            $("#infusionsoft_tag_optin_value_"+key, $parent).html(GetFieldValues(value, "", 70));
        }).on('change', '#gf_infusionsoft_form', function() {
            SelectForm( $(this).val() );
        }).on('change', '#infusionsoft_optin_field_id', SetOptinCondition );
    });

            function SelectForm(formId){

                // If no form is selected, just hide everything.
                if(!formId){
                    jQuery("#infusionsoft_field_group").slideUp();
                    return;
                }

                jQuery("#infusionsoft_wait").show();
                jQuery("#infusionsoft_field_group").slideUp();

                var mysack = new sack("<?php echo esc_js( admin_url('admin-ajax.php') ); ?>" );
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "gf_select_infusionsoft_form" );
                mysack.setVar( "gf_select_infusionsoft_form", "<?php echo wp_create_nonce("gf_select_infusionsoft_form") ?>" );
                mysack.setVar( "form_id", formId);
                mysack.onError = function() {jQuery("#infusionsoft_wait").hide(); alert('<?php echo esc_js( __("Ajax error while selecting a form", "gravity-forms-infusionsoft") ); ?>' )};
                mysack.runAJAX();
                return true;
            }

            function SetOptin(selectedField, selectedValue, tag){

                //load form fields
                jQuery(".optin_select[id*=field_id]").each(function() {

                    var optinConditionField = jQuery(this).val();
                    var $table = jQuery(this).parents('tr.gfield_list_row');
                    var fieldID = $table.data('fieldid');
                    var values = '';
                    jQuery(this).addClass('processed');

                    // If the conditional is set up
                    if(optinConditionField){
                        jQuery(".infusionsoft_optin_condition_message", $table).hide();
                        jQuery(".infusionsoft_optin_condition_fields", $table).show();

                        if(tag == fieldID) {
                            // Gather the form fields that qualify for conditional
                            jQuery(this).html(GetSelectableFields(selectedField, 50));
                            values = GetFieldValues(optinConditionField, selectedValue, 50);
                            jQuery(".optin_value", $table).html(values);
                        }
                    } else{
                        jQuery(this).html(GetSelectableFields(selectedField, 50));
                        jQuery(".infusionsoft_optin_condition_message", $table).show();
                        jQuery(".infusionsoft_optin_condition_fields", $table).hide();
                    }

                });
            }

            function EndSelectForm(fieldList, form_meta){
                //setting global form object
                form = form_meta;

                if(fieldList){

                    SetOptin("","", false);

                    jQuery("#infusionsoft_field_list").html(fieldList);
                    jQuery("#infusionsoft_field_group").slideDown();
                    jQuery('#infusionsoft_field_list').trigger('load');
                }
                else{
                    jQuery("#infusionsoft_field_group").slideUp();
                    jQuery("#infusionsoft_field_list").html("");
                }
                jQuery("#infusionsoft_wait").hide();
            }

            function GetFieldValues(fieldId, selectedValue, labelMaxCharacters){
                if(!fieldId)
                    return "";

                var str = "";
                var field = GetFieldById(fieldId);
                if(!field || !field.choices)
                    return "";

                var isAnySelected = false;

                for(var i=0; i<field.choices.length; i++){
                    var fieldValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
                    var isSelected = fieldValue == selectedValue;
                    var selected = isSelected ? "selected='selected'" : "";
                    if(isSelected)
                        isAnySelected = true;

                    str += "<option value='" + fieldValue.replace("'", "&#039;") + "' " + selected + ">" + TruncateMiddle(field.choices[i].text, labelMaxCharacters) + "</option>";
                }

                if(!isAnySelected && selectedValue){
                    str += "<option value='" + selectedValue.replace("'", "&#039;") + "' selected='selected'>" + TruncateMiddle(selectedValue, labelMaxCharacters) + "</option>";
                }

                return str;
            }

            function GetFieldById(fieldId){
                for(var i=0; i<form.fields.length; i++){
                    if(form.fields[i].id == fieldId)
                        return form.fields[i];
                }
                return null;
            }

            function TruncateMiddle(text, maxCharacters){
                if(text.length <= maxCharacters)
                    return text;
                var middle = parseInt(maxCharacters / 2);
                return text.substr(0, middle) + "..." + text.substr(text.length - middle, middle);
            }

            function GetSelectableFields(selectedFieldId, labelMaxCharacters){
                var str = "";
                var inputType;
                for(var i=0; i<form.fields.length; i++){
                    fieldLabel = form.fields[i].adminLabel ? form.fields[i].adminLabel : form.fields[i].label;
                    inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
                    if(inputType == "checkbox" || inputType == "radio" || inputType == "select"){
                        var selected = form.fields[i].id == selectedFieldId ? "selected='selected'" : "";
                        str += "<option value='" + form.fields[i].id + "' " + selected + ">" + TruncateMiddle(fieldLabel, labelMaxCharacters) + "</option>";
                    }
                }
                return str;
            }

            /**
             * We duplicate existing fields and we have to reset the values then bring them in dynamically.
             */
            function GFInfusionsoftUpdateListIDs(element) {
                var $tr = jQuery(element).parent().parent();
                var $table = $tr.parent();
                var rows = $table.children();

                for(var i=0; i<rows.length; i++){

                    // Set the <tr> IDs and the fieldid
                    jQuery(rows[i]).attr("id", 'gfield_list_row_'+i).data('fieldid', i);


                    $optin = jQuery('select[id*=infusionsoft_tag_optin_value]', rows[i]);
                    $id = jQuery('select[id*=infusionsoft_tag_optin_field_id]', rows[i]);

                    // Store previous settings
                    idval = $id.val();
                    optinval = $optin.val();

                    // Reset "is"
                    jQuery('select[id*=infusionsoft_tag_optin_operator]', rows[i]).attr('id', 'infusionsoft_tag_optin_operator_'+i).val('is');

                    // Optin
                    $optin.attr('id', 'infusionsoft_tag_optin_value_'+i);

                    // This will reset everything
                    $id.attr('id', 'infusionsoft_tag_optin_field_id_'+i).trigger('change');

                    $optin.val(optinval);
                    $id.val(idval);

                    jQuery('.infusionsoft_checkboxes input', rows[i])
                        .attr('name', 'tag_optin_tags['+i+'][]')
                        .attr('id', function() {
                            return jQuery(this).attr('id').replace(/\[([0-9]+)\]/, '_row'+i+'_');
                        });
                }
            }

        </script>

        <?php

    }

    public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gravityforms_infusionsoft");
        $wp_roles->add_cap("administrator", "gravityforms_infusionsoft_uninstall");
    }

    //Target of Member plugin filter. Provides the plugin with Gravity Forms lists of capabilities
    public static function members_get_capabilities( $caps ) {
        return array_merge($caps, array("gravityforms_infusionsoft", "gravityforms_infusionsoft_uninstall"));
    }

    public static function disable_infusionsoft(){
        delete_option("gf_infusionsoft_settings");
    }

    public static function select_infusionsoft_form(){
        check_ajax_referer("gf_select_infusionsoft_form", "gf_select_infusionsoft_form");
        if(!self::$debug_js) { error_reporting(0); }
        $form_id =  intval($_POST["form_id"]);
        $setting_id =  0;

        // Not only test API, but include necessary files.
        $valid = self::test_api();
        if(empty($valid)) {
            die("EndSelectForm();");
        }

        //getting list of all Infusionsoft merge variables for the selected contact list
        $merge_vars = self::get_fields();

        //getting configuration
        $config = GFInfusionsoftData::get_feed($setting_id);

        //getting field map UI
        $str = self::get_field_mapping($config, $form_id, $merge_vars);

        //fields meta
        $form = self::get_form_meta_extended( $form_id );

        //$fields = $form["fields"];
        die("EndSelectForm('" . str_replace("'", "\'", str_replace(")", "\)", $str)) . "', " . GFCommon::json_encode($form) . ");");
    }

    /**
     * Extends the RGFormsModel::get_form_meta to add custom fields (like quiz results' fields)
     * @param  string $form_id  form ID
     * @return array            form meta object (custom)
     */
    private static function get_form_meta_extended( $form_id ) {

        if( empty( $form_id ) || !class_exists('RGFormsModel') ) {
            return array();
        }

        // get default form meta
        $form = RGFormsModel::get_form_meta( $form_id );

        //Add quiz results' fields to form to enable the opt-in conditions
        if( !empty( $form['gravityformsquiz'] ) ) {
            // add pass/fail field
            $form['fields'][] = array(
                'id' => 'gquiz_is_pass',
                'label'=> 'Quiz Pass/Fail',
                'inputType' => 'radio',
                'choices' => array( array( 'text' => 'Pass', 'value' => '1' ), array( 'text' => 'Fail', 'value' => '0' ) )
                );
            // add grade field
            if( !empty( $form['gravityformsquiz']['grades'] ) ) {
                $grades = array();
                foreach( $form['gravityformsquiz']['grades'] as $grade ) {
                    $grades[] = array( 'text' => $grade['text'], 'value' => $grade['text'] );
                }
                $form['fields'][] = array(
                    'id' => 'gquiz_grade',
                    'label'=> 'Quiz Grade',
                    'inputType' => 'radio',
                    'choices' => $grades
                );
            }
        }

        return $form;
    }



    private static function get_fields() {

        $lists = array();

        $fields = get_site_transient('gf_infusionsoft_default_fields');
        if(!empty($fields) && !isset($_REQUEST['cache'])) {
            $fields = maybe_unserialize($fields);
        } else {
            self::$classLoader->loadClass('Contact');
            $Contact = new Infusionsoft_Contact();
            $fields = $Contact->getFields();

            // Cache the results for two months; Infusionsoft says that their defaults won't change often.
            set_site_transient('gf_infusionsoft_default_fields', maybe_serialize($fields), 60 * 60 * 24 * 60);
        }

        foreach($fields as $key => $field) {

            $lists[] = array(
                'name' => esc_js($field),
                'req' => false,
                'tag' => esc_js($field),
            );
        }

        $custom_fields = get_site_transient('gf_infusionsoft_custom_fields');
        if(!empty($custom_fields) && !isset($_REQUEST['cache'])) {
            $custom_fields = maybe_unserialize($custom_fields);
        } else {
            $custom_fields = Infusionsoft_DataService::getCustomFields(new Infusionsoft_Contact());

            // Cache the results for one day; will change more often than not often...
            set_site_transient('gf_infusionsoft_custom_fields', maybe_serialize($custom_fields), 60 * 60 * 24);
        }

        if(!empty($custom_fields)) {
            foreach($custom_fields as $key => $field) {

                if(!is_array($field)) { continue; }

                foreach($field as $k => $v) {

                    if(!is_a($v, 'Infusionsoft_DataFormField')) { continue; }

                    $lists[] = array(
                        'name' => esc_js($v->__get('Label')),
                        'req' => false,
                        'tag' => esc_js($v->__get('Name')),
                    );
                }
            }
        }

        $lists[] = array(
            'name' => esc_js(__('Tags',  "gravity-forms-infusionsoft")),
            'req' => false,
            'tag' => 'groupId',
        );

        return $lists;
    }



	/**
	 * Get a list of available tags.
     *
     * Tags are cached using `gf_infusionsoft_tag_list` transient.
     *
	 * @version 1.5.5
	 * @access public
	 * @static
	 * @return array
	 */
	static function get_tag_list() {

		if( !isset($_GET['cache']) || false === ( $lists = get_site_transient( 'gf_infusionsoft_tag_list' ) ) ) {

            $lists = array(); $page = 0;

            // How many tags do you have?
            $max_number_of_tags = apply_filters( 'gf_infusionsoft_max_number_of_tags', 4000 );

            // We're fetching 1000 tags per page.
            $tag_pages = ceil((int)$max_number_of_tags/1000);

			for( $page = 0; $page < $tag_pages; $page++ ) {

				$contactGroups = Infusionsoft_DataService::query( new Infusionsoft_ContactGroup(), array('Id' => '%'), 1000, $page );

				if( !empty( $contactGroups ) ) {
					foreach( $contactGroups as $contactGroup ) {

						if(!is_a($contactGroup, 'Infusionsoft_ContactGroup')) { continue; }

						$lists[] = array(
							'name' => esc_js($contactGroup->__get('GroupName')),
							'GroupCategoryId' => $contactGroup->__get('GroupCategoryId'),
							'req' => false,
							'tag' => esc_js($contactGroup->__get('Id')),
						);
					}
				} else {
					break;
				}
			}

			if( !empty( $lists ) ) {
				// Cache the results for one day;
				set_site_transient( 'gf_infusionsoft_tag_list', maybe_serialize( $lists ), DAY_IN_SECONDS );
			}

		} else {

			$lists = maybe_unserialize( $lists );

		}

		return $lists;
	}



    private static function get_field_mapping($config = array(), $form_id, $merge_vars){

        $str = $custom = $standard = '';


        //getting list of all fields for the selected form
        $form_fields = self::get_form_fields($form_id);

        $str = "<table cellpadding='0' cellspacing='0'><thead><tr><th scope='col' class='infusionsoft_col_heading'>" . esc_html__("List Fields", "gravity-forms-infusionsoft") . "</th><th scope='col' class='infusionsoft_col_heading'>" . esc_html__("Form Fields", "gravity-forms-infusionsoft") . "</th></tr></thead><tbody>";


        foreach($merge_vars as $var){

            $selected_field = (isset($config["meta"]) && isset($config["meta"]["field_map"]) && isset($config["meta"]["field_map"][$var["tag"]])) ? $config["meta"]["field_map"][$var["tag"]] : '';

            if($var['tag'] === 'groupId') {

                $lists = self::get_tag_list();

                $field_list = self::get_mapped_field_checkbox($var["tag"], $selected_field, $lists);

                $name = __("Entry Tags", 'gravity-forms-infusionsoft');

                self::setup_tooltips();

                ob_start();
                    gform_tooltip("infusionsoft_tag");
                $tooltip = ob_get_clean();

                $name .= ' '.$tooltip;

            } else {
                $field_list = self::get_mapped_field_list($var["tag"], $selected_field, $form_fields);
                $name = stripslashes( $var["name"] );
            }

            $required = $var["req"] === true ? "<span class='gfield_required' title='This field is required.'>*</span>" : "";
            $error_class = $var["req"] === true && empty($selected_field) && !empty($_POST["gf_infusionsoft_submit"]) ? " feeds_validation_error" : "";
            $field_desc = '';
            $row = "<tr class='$error_class'><th scope='row' class='infusionsoft_field_cell' id='infusionsoft_map_field_{$var['tag']}_th'><label for='infusionsoft_map_field_{$var['tag']}'>" . $name ." $required</label><small class='description' style='display:block'>{$field_desc}</small></th><td class='infusionsoft_field_cell'>" . $field_list . "</td></tr>";

            $str .= $row;

        } // End foreach merge var.

        $str .= "</tbody></table>";

        return $str;
    }

    private function getNewTag($tag, $used = array()) {
        if(isset($used[$tag])) {
            $i = 1;
            while($i < 1000) {
                if(!isset($used[$tag.'_'.$i])) {
                    return $tag.'_'.$i;
                }
                $i++;
            }
        }
        return $tag;
    }

    /**
     * Getting an array of all fields for the selected form
     *
     * @filter `gravity_forms_infusionsoft_form_fields` Modify the fields available
     *
     * @param  int $form_id The ID of the form we're getting
     * @return array          Array of fields with [0] as the field ID, [1] as the field label
     */
    public static function get_form_fields($form_id){
        $form = RGFormsModel::get_form_meta($form_id);
        $fields = array();

        //Adding default fields
        array_push($form["fields"],array("id" => "date_created" , "label" => __("Entry Date", "gravity-forms-infusionsoft")));
        array_push($form["fields"],array("id" => "ip" , "label" => __("User IP", "gravity-forms-infusionsoft")));
        array_push($form["fields"],array("id" => "source_url" , "label" => __("Source Url", "gravity-forms-infusionsoft")));

        if(is_array($form["fields"])){
            foreach($form["fields"] as $field){
                if(isset($field["inputs"]) && is_array($field["inputs"]) && $field['type'] !== 'checkbox' && $field['type'] !== 'select'){

                    //If this is an address field, add full name to the list
                    if(RGFormsModel::get_input_type($field) == "address")
                        $fields[] =  array($field["id"], GFCommon::get_label($field) . " (" . __("Full" , "gravity-forms-infusionsoft") . ")");

                    foreach($field["inputs"] as $input)
                        $fields[] =  array($input["id"], GFCommon::get_label($field, $input["id"]));
                }
                else if(empty($field["displayOnly"])){
                    $fields[] =  array($field["id"], GFCommon::get_label($field));
                }
            }
        }

        //Adding other fields (since v 1.5.8) - for example the results' quiz fields
        if( class_exists('GFFormsModel') ) {
            $extra_fields = GFFormsModel::get_entry_meta($form_id);
            foreach( $extra_fields as $key => $extra_field ) {
                $fields[] =  array( $key , $extra_field['label'] );
            }
        }

        // manage available fields
        $fields = apply_filters( 'gravity_forms_infusionsoft_form_fields', $fields, $form );

        return $fields;
    }

    private static function get_address($entry, $field_id){
        $street_value = str_replace("  ", " ", trim($entry[$field_id . ".1"]));
        $street2_value = str_replace("  ", " ", trim($entry[$field_id . ".2"]));
        $city_value = str_replace("  ", " ", trim($entry[$field_id . ".3"]));
        $state_value = str_replace("  ", " ", trim($entry[$field_id . ".4"]));
        $zip_value = trim($entry[$field_id . ".5"]);
        $country_value = GFCommon::get_country_code(trim($entry[$field_id . ".6"]));

        $address = $street_value;
        $address .= !empty($address) && !empty($street2_value) ? "  $street2_value" : $street2_value;
        $address .= !empty($address) && (!empty($city_value) || !empty($state_value)) ? "  $city_value" : $city_value;
        $address .= !empty($address) && !empty($city_value) && !empty($state_value) ? "  $state_value" : $state_value;
        $address .= !empty($address) && !empty($zip_value) ? "  $zip_value" : $zip_value;
        $address .= !empty($address) && !empty($country_value) ? "  $country_value" : $country_value;

        return $address;
    }

    public static function get_mapped_field_list($variable_name, $selected_field, $fields){
        $field_name = "infusionsoft_map_field_" . $variable_name;
        $str = "<select name='$field_name' id='$field_name'><option value=''></option>";
        foreach($fields as $field){
            $field_id = $field[0];
            $field_label = $field[1];
            $str .= "<option value='" . $field_id . "' ". selected(($field_id == $selected_field), true, false) . ">" . $field_label . "</option>";
        }
        $str .= "</select>";
        return $str;
    }

    /**
     * Generate a HTML unordered list of tags with checkboxes.
     * @param  string $variable_name  Name of field
     * @param  array  $selected_field Array of values that are checked
     * @param  array $fields         Array of all possible values, with keys `tag` (value of field) and `name` (label for field) defined.
     * @param  string $base_name      String for `name` and `id` prefix
     * @return string                 HTML output of `<ul>`
     */
    public static function get_mapped_field_checkbox($variable_name, $selected_field = array(), $fields, $base_name = 'infusionsoft_map_field_'){
        $field_name_base = $base_name . $variable_name;
        $str = '<ul class="'.sanitize_html_class( $field_name_base ).'_checkboxes infusionsoft_checkboxes">';
        foreach($fields as $field){
            $field_name = $field_name_base.$field["tag"];
            $str .= "<li><label>";
            $str .=  "<input name='{$field_name_base}[]' id='{$field_name}' type='checkbox' value='".$field['tag']."'";
            $str .= checked(is_array($selected_field) && in_array($field['tag'], $selected_field), true, false);
            $str .= " /> ".esc_html($field['name'])."</label></li>";
        }
        $str .= '</ul>';
        return $str;
    }

    public static function export($entry, $form){

        self::log_debug( 'init export. Entry ID: ' . $entry['id'] );

        //Login to Infusionsoft
        $api = self::get_api();
        if(!empty($api->lastError)) {
            self::log_debug( 'Infusionsoft API Error: ' . print_r( $api->lastError, true ) );
            return;
        }

        //loading data class
        require_once(self::get_base_path() . "/data.php");

        //getting all active feeds
        $feeds = GFInfusionsoftData::get_feed_by_form($form["id"], true);
        foreach($feeds as $feed){
            //Always export the user
            self::export_feed($entry, $form, $feed, $api);
        }
    }

    public static function export_feed($entry, $form, $feed, $api){

        self::log_debug( '[Entry ID: '. $entry['id'] . '] Init Export Feed: ' . print_r( $feed, true ) );

        $email_field_id = $feed["meta"]["field_map"]["Email"];
        $email = $entry[$email_field_id];

        if( empty( $email ) ) {
              self::log_debug( '[Entry ID: '. $entry['id'] . '] No email defined - leaving...' );
              return;
        }
        self::log_debug( '[Entry ID: '. $entry['id'] . '] Email: ' . print_r( $email, true ) );

        $merge_vars = array();

        foreach($feed["meta"]["field_map"] as $var_tag => $field_id){
            if($var_tag === 'groupId') {
                $merge_vars[$var_tag] = $field_id;
            } else {
                $field = RGFormsModel::get_field($form, $field_id);
                $input_type = RGFormsModel::get_input_type($field);

                if( $field_id == intval($field_id) && RGFormsModel::get_input_type($field) == "address") {
                    //handling full address
                    $merge_vars[$var_tag] = self::get_address($entry, $field_id);
                    $merge_vars[$var_tag] = self::clean_utf8( $merge_vars[$var_tag] );

                } elseif ( $input_type === 'date' && !empty( $entry[$field_id] ) ) {
                    $original_timezone = date_default_timezone_get();
                    date_default_timezone_set('America/New_York');
                    $date = strtotime($entry[$field_id]);
                    $date = date('Ymd\TH:i:s', $date);
                    date_default_timezone_set($original_timezone);

                    $merge_vars[$var_tag] = $date;
                } elseif ( $input_type === 'radio' && isset( $entry[ $field_id ] ) ) {

                    // Radio buttons are sent to infusionsoft as strings by default.
                    $merge_vars[$var_tag] = apply_filters( 'gf_infusionsoft_radio_value', $entry[ $field_id ], $field_id );

                    // Yes/No fields in infusionsoft only work with integer
                    if( in_array( $merge_vars[$var_tag], array( '0', '1') ) ) {
                        $merge_vars[$var_tag] = (int)$merge_vars[$var_tag];
                    }

                } elseif ( $input_type === 'number' && isset( $entry[ $field_id ] ) ) {
                    $merge_vars[$var_tag] = (float)$entry[ $field_id ];

                } else if( $var_tag != "EMAIL" ) { //ignoring email field as it will be handled separatelly
                    $merge_vars[$var_tag] = $entry[$field_id];
                    $merge_vars[$var_tag] = self::clean_utf8( $merge_vars[$var_tag] );
                }

            }
        }

        self::log_debug( '[Entry ID: '. $entry['id'] . '] Infusionsoft Merge Data: ' . print_r( $merge_vars, true ) );

        $valid = self::test_api();

        if($valid) {
            $contact_id = self::add_contact($email, $merge_vars);

            self::log_debug( '[Entry ID: '. $entry['id'] . '] Contact ID: ' . print_r( $contact_id, true ) );

            if($contact_id) {
                $tags_added = self::add_tags_to_contact($contact_id, $merge_vars, $feed, $entry, $form);
                self::log_debug( '[Entry ID: '. $entry['id'] . '] Adding Tags: ' . print_r( $tags_added, true ) );
            }
            // Only set them as marketable if they opt-in
            // http://help.infusionsoft.com/developers/services-methods/email/optIn
            //if(self::is_optin($form, $feed)) {
            if( self::is_optin_ok( $entry, $feed ) ) {
                $opt_in = self::opt_in($email, $entry);
                self::log_debug( '[Entry ID: '. $entry['id'] . '] Opt In: ' . print_r( $opt_in, true ) );
            }

            if(self::is_debug()) {
                echo '<h3>'.esc_html__('Admin-only Form Debugging', 'gravity-forms-infusionsoft').'</h3>';
                self::r(array(
                        'Form Entry Data' => $entry,
                        #'Form Meta Data' => $form,
                        'Infusionsoft Feed Meta Data' => $feed,
                        'Infusionsoft Posted Merge Data' => $merge_vars,
                        'Posted Data ($_POST)' => $_POST,
                        'Contact ID' => $contact_id,
                        'Adding Tags' => $tags_added,
                ));
            }
            self::log_debug( '[Entry ID: '. $entry['id'] . '] Form Entry Data: ' . print_r( $entry, true ) );
            self::log_debug( '[Entry ID: '. $entry['id'] . '] Posted Data ($_POST): ' . print_r( $_POST, true ) );
            self::add_note($entry, $contact_id);

       } elseif(current_user_can('administrator')) {
            echo '<div class="error" id="message">'.wpautop(sprintf(esc_html__("The form didn't create a contact because the Infusionsoft Gravity Forms Add-on plugin isn't properly configured. %sCheck the configuration%s and try again.", 'gravity-forms-infusionsoft'), '<a href="'.esc_url( admin_url('admin.php?page=gf_settings&amp;addon=Infusionsoft') ).'">', '</a>')).'</div>';

            self::log_debug( '[Entry ID: '. $entry['id'] . '] '. "API Error: The form didn't create a contact because the Infusionsoft Gravity Forms Add-on plugin isn't properly configured. " );
        }
    }

    static function add_note($entry, $contact_id) {
        global $current_user;

        // Old version
        if(!function_exists('gform_update_meta')) { return; }

        @RGFormsModel::add_note($entry['id'], $current_user->ID, $current_user->display_name, stripslashes(sprintf(__('Added or Updated on Infusionsoft. Contact ID: #%d. View entry at %s', 'gravity-forms-addons', 'gravity-forms-infusionsoft'), $contact_id, self::get_contact_url($contact_id))));

        @gform_update_meta($entry['id'], 'infusionsoft_id', $contact_id);

    }

    static function get_contact_url($contact_id) {
        return add_query_arg(array('view' => 'edit', 'ID' => $contact_id), 'https://'.self::get_setting('appname').'.infusionsoft.com/Contact/manageContact.jsp');
    }

    static function entry_info_link_to_infusionsoft($form_id, $lead) {
        $contact_id = gform_get_meta($lead['id'], 'infusionsoft_id');
        if(!empty($contact_id)) {
            echo sprintf(__('<p>Infusionsoft ID: <a href="%s">Contact #%s</a></p>', 'gravity-forms-infusionsoft'), self::get_contact_url($contact_id), $contact_id);
        }
    }

    static private function clean_utf8($string) {

        if(function_exists('mb_convert_encoding') && !seems_utf8($string)) {
            $string = mb_convert_encoding($string, "UTF-8", 'auto');
        }

        // First, replace UTF-8 characters.
        $string = str_replace(
            array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
            array("'", "'", '"', '"', '-', '--', '...'),
        $string);

        // Next, replace their Windows-1252 equivalents.
        $string = str_replace(
            array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
            array("'", "'", '"', '"', '-', '--', '...'),
        $string);

        return $string;
    }

    /**
     * Extends GFCommon::get_selection_fields function to add other custom selection fields
     * @param  array $form                  form object
     * @param  string $selected_field_id    the selected field id to mark is as 'selected'
     * @return string                       select options html tags
     */
    public static function get_selection_fields( $form, $selected_field_id ) {
        // get the default selection fields
        $output = GFCommon::get_selection_fields( $form, $selected_field_id );

        // Add custom selection fields (for example, quiz pass and quiz grade )
        $extra_fields = GFFormsModel::get_entry_meta( $form['id'] );
        foreach( $extra_fields as $key => $extra_field ) {
            if( in_array( $key, array( 'gquiz_is_pass', 'gquiz_grade' ) ) ) {
                $output .= '<option value="' . $key . '" ' . selected( $key, $selected_field_id, false ) . '>' . $extra_field['label'] . '</option>';
            }
        }

        return $output;
    }


    static private function opt_in($email, $entry) {

        self::$classLoader->loadClass('EmailService');

        $EmailService = new Infusionsoft_EmailService();

        return $EmailService->optIn($email, apply_filters('gravity_forms_infusionsoft_optinsource', sprintf( __("Gravity Forms Entry #%s (Source: %s)", 'gravity-forms-infusionsoft'), $entry['id'], $entry['source_url']), $entry));
    }

    public static function is_optin($form, $settings){
        $config = $settings["meta"];
        $operator = $config["optin_operator"];

        $field = RGFormsModel::get_field($form, $config["optin_field_id"]);
        $field_value = RGFormsModel::get_field_value($field, array());
        $is_value_match = is_array($field_value) ? in_array($config["optin_value"], $field_value) : $field_value == $config["optin_value"];

        return  !$config["optin_enabled"] || empty($field) || ($operator == "is" && $is_value_match) || ($operator == "isnot" && !$is_value_match);
    }

    /**
     * Alternative is_optin function that uses $entry instead of $form
     * returns true if entry is OK to be exported
     *
     * @access public
     * @static
     * @param array $entry
     * @param array $settings
     * @return boolean
     */
    public static function is_optin_ok( $entry, $settings ){

        if( empty( $settings['meta']['optin_enabled'] ) ) {
            return true;
        }

        $operator = $settings['meta']['optin_operator'];

        foreach( $entry as $key => $value ) {

            if( floor( $key ) == $settings['meta']['optin_field_id']
                || ( !is_numeric( $key ) && $key == $settings['meta']['optin_field_id'] )
            ) {
                $field_value[] = empty( $value ) ? '' : $value;
            }
        }

        $is_value_match = is_array( $field_value ) ? in_array( $settings['meta']['optin_value'], $field_value) : false;

        return ( $operator == "is" && $is_value_match ) || ( $operator == "isnot" && !$is_value_match );
    }



    static private function add_contact($email_address, $merge_vars) {

        self::$classLoader->loadClass('ContactService');

        $ContactService = new Infusionsoft_ContactService();

        unset($merge_vars['groupId']);

        // Options: 'Email', 'EmailAndName', 'EmailAndNameAndCompany'
        // http://help.infusionsoft.com/developers/services-methods/contacts/addWithDupCheck
        return $ContactService->addWithDupCheck($merge_vars, apply_filters('gravity_forms_infusionsoft_dupchecktype', 'Email'));
    }

    /**
     * Process tags for the entry, and add conditional tags.
     * @param int $contact_id Number of the Contact ID
     * @param array $merge_vars Form posted merge data
     * @param array $entry      Graviy Forms entry array
     */
    static private function add_tags_to_contact($contact_id, $merge_vars, $feed, $entry, $form) {

        $groups = array();
        $debug = array();

        // Add conditional tags
        if(!empty($feed['meta']['tag_optin_tags']) && is_array($feed['meta']['tag_optin_tags'])) {

            // For each opt-in conditional that is set up
            foreach($feed['meta']['tag_optin_tags'] as $key => $tags) {

                // We get the ID of the field that the conditional is based on
                $conditional_field_id = $feed['meta']['tag_optin_field_id'][$key];

                // We get the value of the conditional opt-in the field
                $conditional_field_compare = $feed['meta']['tag_optin_value'][$key];
                $is = ($feed['meta']['tag_optin_operator'][$key] === 'is');
                $entry_field = isset($entry["{$conditional_field_id}"]) ? $entry["{$conditional_field_id}"] : null;

                foreach( $entry as $key => $value ) {

                    if( floor( $key ) == $conditional_field_id
                        || ( !is_numeric( $key ) && $key == $conditional_field_id )
                    ) {
                        $field_value[] = empty( $value ) ? '' : $value;
                    }
                }

                $is_value_match = is_array( $field_value ) ? in_array( $conditional_field_compare, $field_value) : false;


                // $field = RGFormsModel::get_field($form, $conditional_field_id);
                // $entry_field = RGFormsModel::get_lead_field_value($entry, $field);
                // $entry_label = GFCommon::get_label($field);
                $added = __('No: the comparison did not match', 'gravity-forms-infusionsoft');

                if( ( $is && $is_value_match ) || ( !$is && !$is_value_match ) ) {



               /* if(
                    // Is a match
                   ($is && $entry_field === $conditional_field_compare) ||
                   ($is && is_array($entry_field) && in_array($conditional_field_compare, $entry_field)) ||

                   // Or is not a match
                   (!$is && $entry_field !== $conditional_field_compare) ||
                   (!$is && is_array($entry_field) && !in_array($conditional_field_compare, $entry_field))
                ) { */
                    // Add tag if not already added.
                    foreach($tags as $tagID) {
                       if(!in_array($tagID, $groups)) { $groups[] = $tagID; $added = __('Yes: added', 'gravity-forms-infusionsoft'); } else { $added = 'already exists'; }
                    }
                }

                $debug[] = array(
                        'Conditional Field ID' => $conditional_field_id,
                        'Entry Field Value' => $entry_field,
                        'Comparison Type' => $is ? 'is' : 'is not',
                        'Conditional Field Comparison' => $conditional_field_compare,
                        'Added' => $added,
                        'Tags' => ($added === 'no match' ? null : $tags)
                );
            }
        }

        // Add non-conditional tags
        if(isset($merge_vars['groupId'])) {
            $debug[] = array('Non-Conditional Tags' => (array)$merge_vars['groupId']);
            foreach((array)$merge_vars['groupId'] as $tagID) {
                if(!in_array($tagID, $groups)) { $groups[] = $tagID; }
            }
        }

        // If there are no tags, get outta here!
        if(!empty($groups)) {
            // Otherwise, we add the groups
            self::$classLoader->loadClass('ContactService');

            $ContactService = new Infusionsoft_ContactService();

            foreach($groups as $groupId) {
                $ContactService->addToGroup($contact_id, $groupId);
            }
        }

        return self::is_debug() ? $debug : $groups;
    }

    public static function uninstall(){

        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        if(!GFInfusionsoft::has_access("gravityforms_infusionsoft_uninstall"))
            die(__("You don't have adequate permission to uninstall Infusionsoft Add-On.", "gravity-forms-infusionsoft"));

        //droping all tables
        GFInfusionsoftData::drop_tables();

        //removing options
        delete_option("gf_infusionsoft_settings");
        delete_option("gf_infusionsoft_version");

        //Deactivating plugin
        $plugin = "gravity-forms-infusionsoft/infusionsoft.php";
        deactivate_plugins($plugin);
        update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
    }

    private static function is_gravityforms_installed(){
        return class_exists("RGForms");
    }

    private static function is_gravityforms_supported(){
        if(class_exists("GFCommon")) {
            $is_correct_version = version_compare(GFCommon::$version, self::$min_gravityforms_version, ">=");
            return $is_correct_version;
        }
        else{
            return false;
        }
    }

    static private function simpleXMLToArray($xml,
                    $flattenValues=true,
                    $flattenAttributes = true,
                    $flattenChildren=true,
                    $valueKey='@value',
                    $attributesKey='@attributes',
                    $childrenKey='@children'){

        $return = array();
        if(!($xml instanceof SimpleXMLElement)){return $return;}
        $name = $xml->getName();
        $_value = trim((string)$xml);
        if(strlen($_value)==0){$_value = null;};

        if($_value!==null){
            if(!$flattenValues){$return[$valueKey] = $_value;}
            else{$return = $_value;}
        }

        $children = array();
        $first = true;
        foreach($xml->children() as $elementName => $child){
            $value = self::simpleXMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
            if(isset($children[$elementName])){
                if($first){
                    $temp = $children[$elementName];
                    unset($children[$elementName]);
                    $children[$elementName][] = $temp;
                    $first=false;
                }
                $children[$elementName][] = $value;
            }
            else{
                $children[$elementName] = $value;
            }
        }
        if(count($children)>0){
            if(!$flattenChildren){$return[$childrenKey] = $children;}
            else{$return = array_merge($return,$children);}
        }

        $attributes = array();
        foreach($xml->attributes() as $name=>$value){
            $attributes[$name] = trim($value);
        }
        if(count($attributes)>0){
            if(!$flattenAttributes){$return[$attributesKey] = $attributes;}
            else{$return = array_merge($return, $attributes);}
        }

        return $return;
    }

    static private function convert_xml_to_object($response) {
        $response = @simplexml_load_string($response);  // Added @ 1.2.2
        if(is_object($response)) {
            return $response;
        } else {
            return false;
        }
    }

    static private function convert_xml_to_array($response) {
        $response = self::convert_xml_to_object($response);
        $response = self::simpleXMLToArray($response);
        if(is_array($response)) {
            return $response;
        } else {
            return false;
        }
    }

    protected static function has_access($required_permission){
        $has_members_plugin = function_exists('members_get_capabilities');
        $has_access = $has_members_plugin ? current_user_can($required_permission) : current_user_can("level_7");
        if($has_access)
            return $has_members_plugin ? $required_permission : "level_7";
        else
            return false;
    }

    //Returns the url of the plugin's root folder
    static protected function get_base_url(){
        return plugins_url(null, __FILE__);
    }

    //Returns the physical path of the plugin's root folder
    static protected function get_base_path(){
        $folder = basename(dirname(__FILE__));
        return WP_PLUGIN_DIR . "/" . $folder;
    }

    /**
     * Enables debug with Gravity Forms logging add-on
     * @param array $supported_plugins List of plugins
     */
    public static function add_debug_settings( $supported_plugins ) {
        $supported_plugins['infusionsoft'] = 'Gravity Forms Infusionsoft Add-on';
        return $supported_plugins;
    }

    /**
     * Logs messages using Gravity Forms logging add-on
     * @param  string $message log message
     * @return void
     */
    public static function log_debug( $message ){
        if ( class_exists("GFLogging") ) {
            GFLogging::include_logger();
            GFLogging::log_message('infusionsoft', $message, KLogger::DEBUG);
        }
    }


}
