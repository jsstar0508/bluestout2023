<?php

namespace WPIDE\App\Classes;

use WPIDE\App\App;
use const WPIDE\Constants\NAME;
use const WPIDE\Constants\SLUG;
use const WPIDE\Constants\VERSION;
use const WPIDE\Constants\ASSETS_URL;

/**
 * Class that takes care of adding plugin review notice after X days
 */

class ReviewNotice {

    /**
     * Interval in days between reminder notice
     */
    public const days_interval = 7;

    /**
     * Max days before stopping the reminder notice
     */
    public const max_days = 120;

    /**
     * Check if a notice is currently active
     */
    public static $active_notice = false;

    /**
     * Review url
     */
    public static $review_url = '';

    /**
     * @throws \Exception
     */
    public static function init() {

        self::$review_url = 'https://wordpress.org/support/plugin/'. SLUG .'/reviews/?rate=5&filter=5/#new-post';

        // Add Ajax Events
        add_action("wp_ajax_".SLUG."_plugin_rate_action", [__CLASS__, 'ajaxPluginRateAction']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets'], 1);

        if(!self::enabled()) {
            return;
        }

        self::$active_notice = true;

        // Init Review Notice
        add_action( 'admin_notices', [ __CLASS__, 'add_review_notice' ] );
    }

    /**
     * @throws \Exception
     */
    public static function enabled(): bool
    {

        $min_days_trigger = self::getMinDaysTrigger();
        $days_passed = self::getDaysPassed();

        return $min_days_trigger !== -1 && $days_passed >= $min_days_trigger && !self::$active_notice;
    }

    public static function getMinDaysTrigger(): int
    {

        $option_key = App::instance()->prefix('review_notice_min_days');
        return intval(get_option($option_key, self::days_interval));
    }

    public static function setMinDaysTrigger($value): bool
    {

        $option_key = App::instance()->prefix('review_notice_min_days');
        return update_option($option_key, $value);
    }

    public static function getDaysPassed(): int
    {

        $install_timestamp = Migrations::$installed_time;
        $current_time = time();

        $date_diff = $current_time - $install_timestamp;

        return intval(round($date_diff / (60 * 60 * 24)));
    }

    public static function getReadableDaysPassed(): string
    {

        $install_timestamp = Migrations::$installed_time;
        $current_time = time();

        return human_time_diff($install_timestamp, $current_time);
    }

    public static function ajaxPluginRateAction() {

        // Continue only if the nonce is correct
        $nonce = sanitize_text_field($_REQUEST['_nonce']);

        if ( ! wp_verify_nonce( $nonce, App::instance()->prefix('wp_notice_action_nonce') ) ) {
            wp_send_json_error();
        }

        $notice_action = sanitize_text_field($_POST['notice_action']);

        self::ratePlugin($notice_action);

        wp_send_json_success();
    }

    public static function ratePlugin($notice_action, $days_interval = null) {

        $min_days_trigger = self::getMinDaysTrigger();
        $days_passed = self::getDaysPassed();
        $days_interval = !empty($days_interval) ? $days_interval : self::days_interval;

        if ( -1 === $min_days_trigger ) {
            return;
        }

        if ('done-rating' === $notice_action) {

            $min_days_trigger = -1;

        } else {

            if($min_days_trigger > self::max_days) {
                $min_days_trigger = -1;
            }else {
                $min_days_trigger = $days_passed + $days_interval;
            }
        }

        self::setMinDaysTrigger($min_days_trigger);
    }

    public static function add_review_notice() {

        $current_user = wp_get_current_user();
        $first_name = ucfirst(strtolower(!empty($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name));

        $time_passed = self::getReadableDaysPassed();
        $action = SLUG."_plugin_rate_action";
        ?>
        <div class="notice notice-info wpide-notice" data-slug="<?php echo esc_attr(SLUG); ?>">
            <p>
                <?php
                echo sprintf(esc_html__("Hey %s, I noticed you've been using %s for the past %s – that’s awesome!", "wpide"), $first_name, '<strong>' . NAME . '</strong>', '<strong>'.$time_passed.'</strong>');
                echo '&nbsp;';
                echo sprintf(esc_html__('Could you please do me a %1$sBIG favor%2$s and give it a %1$s5-star rating%2$s on WordPress? Just to %1$s help us%2$s spread the word and boost our motivation. Many thanks!', "wpide"), "<strong>", "</strong>");
                ?>
                <br><br>
                <em>~ Georges H</em>
            </p>
            <ul class="wpide-notice-action" data-action="<?php echo esc_attr($action);?>" data-nonce="<?php echo wp_create_nonce( App::instance()->prefix('wp_notice_action_nonce') ) ?>">
                <li><span class="dashicons dashicons-thumbs-up"></span> <a target="_blank" href="<?php echo esc_url(self::$review_url);?>"><?php echo esc_html__( 'Ok, you deserve it', 'wpide' ) ?></a></li>
                <li><span class="dashicons dashicons-thumbs-down"></span> <a data-action="not-enough" href="#"><?php echo esc_html__( 'Nope, maybe later', 'wpide' ) ?></a></li>
                <li><span class="dashicons dashicons-yes"></span> <a data-action="done-rating" href="#"><?php echo esc_html__( 'I already did', 'wpide' ) ?></a></li>
                <li><span class="dashicons dashicons-sos"></span> <a target="_blank" href="https://xplodedthemes.com/support/"><?php echo esc_html__( 'Help me first', 'wpide' ) ?></a></li>
            </ul>
        </div>
        <?php
    }

    public static function enqueue_admin_assets()
    {

        wp_enqueue_script(SLUG.'-notice', ASSETS_URL.'global/js/notice-min.js', [], VERSION);
        wp_enqueue_style(SLUG.'-notice', ASSETS_URL.'global/css/notice.css', [], VERSION);
    }

}
