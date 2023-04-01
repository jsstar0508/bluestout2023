<?php

namespace WPIDE\App\Classes;

use  Freemius_Api ;
use  WPIDE\App\App ;
use const  WPIDE\Constants\ASSETS_DIR ;
use const  WPIDE\Constants\ASSETS_URL ;
use const  WPIDE\Constants\AUTHOR ;
use const  WPIDE\Constants\DIR ;
use const  WPIDE\Constants\FS_ID ;
use const  WPIDE\Constants\FS_KEY ;
use const  WPIDE\Constants\GTM_ID ;
use const  WPIDE\Constants\NAME ;
use const  WPIDE\Constants\SLUG ;
use const  WPIDE\Constants\VERSION ;
class Freemius
{
    public static  $fs ;
    public static  $api ;
    public static  $loaded = false ;
    /**
     * @throws \Freemius_Exception
     */
    public static function init()
    {
        
        if ( !isset( self::$fs ) ) {
            if ( !defined( 'WP_FS__PRODUCT_' . FS_ID . '_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_' . FS_ID . '_MULTISITE', true );
            }
            // Init Freemius SDK.
            require_once DIR . 'freemius/start.php';
            self::$fs = fs_dynamic_init( array(
                'id'             => FS_ID,
                'slug'           => SLUG,
                'premium_slug'   => SLUG . '-pro',
                'type'           => 'plugin',
                'public_key'     => FS_KEY,
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                'days'               => 14,
                'is_require_payment' => true,
            ),
                'menu'           => array(
                'slug'        => SLUG,
                'support'     => false,
                'affiliation' => false,
                'network'     => true,
            ),
                'is_live'        => true,
            ) );
            $GLOBALS['wpide_fs'] = self::$fs;
            self::$fs->add_action( 'connect/before', [ __CLASS__, 'beforeConnectBox' ] );
            self::$fs->add_action( 'connect/after', [ __CLASS__, 'afterConnectBox' ] );
            self::$fs->add_filter( 'checkout/purchaseCompleted', [ __CLASS__, 'afterPurchaseJs' ] );
            self::$fs->add_filter( 'templates/checkout.php', [ __CLASS__, 'checkoutGtmScript' ] );
            self::$fs->add_filter( 'freemius_pricing_js_path', [ __CLASS__, 'pricingJsPath' ] );
            self::$fs->add_filter( 'plugin_icon', [ __CLASS__, 'pluginIcon' ] );
            self::$fs->add_filter( 'hide_account_tabs', '__return_true' );
            self::$fs->add_filter( 'hide_freemius_powered_by', '__return_true' );
            self::$fs->add_filter( 'hide_billing_and_payments_info', '__return_true' );
            self::$fs->override_i18n( array(
                'contact-us' => __( 'Support', 'wpide' ),
            ) );
            add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ] );
            self::$loaded = true;
            // Signal that SDK was initiated.
            do_action( 'wpide_fs_loaded' );
        }
    
    }
    
    public static function admin_enqueue_scripts()
    {
        if ( App::instance()->isFreemiusScreen() ) {
            self::freemius_custom_styles();
        }
    }
    
    public static function freemius_custom_styles()
    {
        wp_enqueue_style(
            SLUG . '-fs-connect',
            ASSETS_URL . 'css/freemius/freemius.css',
            [],
            VERSION
        );
    }
    
    public static function beforeConnectBox()
    {
        wp_enqueue_style(
            SLUG . '-fs-connect',
            ASSETS_URL . 'css/freemius/freemius.css',
            [],
            VERSION
        );
        echo  '<div id="fs-connect-wrap">' ;
    }
    
    public static function afterConnectBox()
    {
        echo  '</div>' ;
    }
    
    public static function afterPurchaseJs() : string
    {
        return 'function ( data ) {
                    
             /**
             * Since the user just entered their personal & billing information, agreed to the TOS & privacy,
             * know they are running within a secure iframe from an external domain, they implicitly permit tracking
             * this purchase. So initializing GTM here (after the purchase), is legitimate.
             */
             
            var is_subscription = typeof(data.purchase.initial_amount) !== "undefined";
            var is_trial = typeof(data.purchase.trial_ends) !== "undefined" && data.purchase.trial_ends !== null;
            var total = is_subscription ? data.purchase.initial_amount : data.purchase.gross;
            total = is_trial ? 0 : total;
    
            var coupon = data && data.coupon ? data.coupon.code : "";
            var currency = data && data.currency ? data.currency.toUpperCase() : "USD";
    
            var item = {
                item_name: "' . NAME . '",
                item_id: ' . FS_ID . ',
                item_brand: "' . AUTHOR . '",
                affiliation: "' . AUTHOR . '",
                price: data.total,
                discount: 0,
                currency: currency,
                coupon: coupon,
                index: 1,
                quantity: 1
            };
    
            // Purchase Event
            dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
            dataLayer.push({
                event: "purchase",
                ecommerce: {
                    transaction_id: data.purchase.id,
                    affiliation: item.affiliation,
                    tax: 0,
                    shipping: 0,
                    items: [item],
                    currency: item.currency,
                    coupon: item.coupon,
                    value: total
                }
            });
        }';
    }
    
    public static function checkoutGtmScript( $html ) : string
    {
        return "\n        <script>window.dataLayer = window.dataLayer || [];</script>\n        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n        })(window,document,'script','dataLayer', '" . GTM_ID . "');</script>\n        " . $html;
    }
    
    public static function pricingJsPath() : string
    {
        return ASSETS_DIR . 'pricing/freemius-pricing.js';
    }
    
    public static function pluginIcon() : string
    {
        return ASSETS_DIR . 'img/icon.png';
    }
    
    /**
     * Add license dialog boxes
     *
     * @since     1.0.0
     */
    public static function addLicenseActivationDialogBox()
    {
        
        if ( !did_action( SLUG . '_license_activation_dialog_added' ) && !App::instance()->isFreemiusScreen( 'account' ) ) {
            self::sdk()->_add_license_activation_dialog_box();
            fs_enqueue_local_style( 'fs_dialog_boxes', '/admin/dialog-boxes.css' );
            do_action( SLUG . '_license_activation_dialog_added' );
        }
    
    }
    
    public static function showSubmenus() : bool
    {
        $is_activation_mode = self::sdk()->is_activation_mode();
        
        if ( $is_activation_mode ) {
            $show_submenus = false;
        } else {
            
            if ( fs_is_network_admin() ) {
                /**
                 * Add submenu items or action links to network level when plugin was network activated and the super
                 * admin did NOT delegate the connection of all sites to site admins.
                 */
                $show_submenus = App::instance()->isNetworkActive() && (WP_FS__SHOW_NETWORK_EVEN_WHEN_DELEGATED || !self::sdk()->is_network_delegated_connection());
            } else {
                $show_submenus = !App::instance()->isNetworkActive() || self::sdk()->is_delegated_connection();
            }
        
        }
        
        return $show_submenus;
    }
    
    public static function showUpgradeLink() : bool
    {
        $show_submenus = self::showSubmenus();
        $is_activation_mode = self::sdk()->is_activation_mode();
        $add_upgrade_link = ($show_submenus || $is_activation_mode && self::sdk()->is_only_premium()) && !self::sdk()->is_whitelabeled();
        return $add_upgrade_link && self::sdk()->is_pricing_page_visible() && self::sdk()->is_submenu_item_visible( 'pricing' );
    }
    
    public static function showTrialLink() : bool
    {
        return self::sdk()->apply_filters( 'show_trial', true ) && self::sdk()->has_trial_plan() && !self::sdk()->is_trial_utilized();
    }
    
    public static function sdk() : \Freemius
    {
        return self::$fs;
    }
    
    public static function loaded() : bool
    {
        return self::$loaded;
    }

}