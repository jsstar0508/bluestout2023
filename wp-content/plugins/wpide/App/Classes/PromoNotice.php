<?php
namespace WPIDE\App\Classes;

use Exception;
use const WPIDE\Constants\AUTHOR_URL;
use const WPIDE\Constants\NAME;
use const WPIDE\Constants\SLUG;
use const WPIDE\Constants\VERSION;
use const WPIDE\Constants\ASSETS_URL;

use DateTime;
use DateInterval;
use WPIDE\App\App;

/**
 * Class that takes care of adding promo notice if available based on current date
 */

class PromoNotice {

    /**
     * Dismissed option key
     */
    public static $dismissed_option = '';

    /**
     * Today date
     */
    public static $today;

    /**
     * @throws Exception
     */
    public static function init() {

        self::$dismissed_option = App::instance()->prefix('promos_dismissed');
        self::$today = new DateTime();

        // Add Ajax Events
        add_action("wp_ajax_".SLUG."_promo_dismiss_action", [__CLASS__, 'ajaxPromoDismissAction']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueueAdminAssets'], 1);

        if(!self::enabled()) {
            return;
        }

        // Init Promo Notice
        add_action( 'admin_notices', [ __CLASS__, 'addPromoNotice' ] );
    }

    /**
     * @throws Exception
     */
    public static function enabled(): bool
    {

        if(!Freemius::showSubmenus()) {
            return false;
        }

        try {
            $promo = self::getActivePromo();
            return $promo && !self::isPromoDismissed($promo->id);
        }catch (Exception $error) {
            return false;
        }
    }

    /**
     * @throws Exception
     */
    public static function getPromos(): array
    {

        $year = self::$today->format('Y');
        $nextYear = (new DateTime(self::$today->format('d-m-Y')))->add((new DateInterval('P1Y')))->format('Y');

        return array_map(function($promo) {

            $promo['id'] = md5($promo['name'].$promo['start'].$promo['end']);
            $promo['start'] = new DateTime($promo['start']);
            $promo['end'] = new DateTime($promo['end']);

            return (object) $promo;
        }, [
            [
                "name" => "Valentine's Day Sale",
                "discount" => "15%",
                "coupon" => "VALENTINE".$year,
                "start" => "14-02-".$year,
                "end" => "14-02-".$year
            ],
            [
                "name" => "Mother’s Day Sale",
                "discount" => "15%",
                "coupon" => "MOTHERS".$year,
                "start" => "second Sunday of May ".$year,
                "end" => "second Sunday of May ".$year
            ],
            [
                "name" => "Father’s Day Sale",
                "discount" => "15%",
                "coupon" => "MOTHERS".$year,
                "start" => "third Sunday of June ".$year,
                "end" => "third Sunday of June ".$year
            ],
            [
                "name" => "Halloween Sale",
                "discount" => "15%",
                "coupon" => "HALLOWEEN".$year,
                "start" => "28-10-".$year,
                "end" => "31-10-".$year
            ],
            [
                "name" => "Black Friday + Cyber Monday Sale",
                "discount" => "40%",
                "coupon" => "BFCM".$year,
                "start" => "20-11-".$year,
                "end" => "30-11-".$year
            ],
            [
                "name" => "Christmas + New Year Sale",
                "discount" => "25%",
                "coupon" => "XMAS".$year,
                "start" => "24-12-".$year,
                "end" => "01-01-".$nextYear
            ]
        ]);
    }

    /**
     * @throws Exception
     */
    public static function getPromoById($id): ?object
    {

        $promos = self::getPromos();

        $results = array_filter($promos, function($promo) use($id) {
            return $promo->id === $id;
        });

        return !empty($results) ? array_shift($results) : null;
    }

    /**
     * @throws Exception
     */
    public static function promoExists($id): bool
    {
        return !empty(self::getPromoById($id));
    }

    /**
     * @throws Exception
     */
    public static function getActivePromo(): ?object
    {

        $promos = self::getPromos();

        foreach($promos as $promo) {

            if(self::$today >= $promo->start && self::$today <= $promo->end) {

                return $promo;
            }
        }

        return null;
    }

    public static function getDismissedPromos(): array
    {

        $dismissed = get_option(self::$dismissed_option);

        return is_array($dismissed) ? $dismissed : [];
    }

    /**
     * @throws Exception
     */
    public static function cleanDismissedPromos($dismissed)
    {
        foreach($dismissed as $key => $id) {
            if(!self::promoExists($id)) {
                unset($dismissed[$key]);
            }
        }

        return $dismissed;
    }

    /**
     * @throws Exception
     */
    public static function dismissPromo($id): bool
    {

        $dismissed = self::getDismissedPromos();

        if(!in_array($id, $dismissed)) {
            $dismissed = self::cleanDismissedPromos($dismissed);
            $dismissed[] = $id;
            return update_option(self::$dismissed_option, $dismissed);
        }

        return true;
    }

    public static function isPromoDismissed($id): bool
    {
        $dismissed = self::getDismissedPromos();

        return in_array($id, $dismissed);
    }

    public static function ajaxPromoDismissAction() {

        // Continue only if the nonce is correct
        $nonce = sanitize_text_field($_REQUEST['_nonce']);

        if ( ! wp_verify_nonce( $nonce, App::instance()->prefix('promo_dismiss_nonce') ) ) {
            wp_send_json_error();
        }

        $id = sanitize_text_field($_POST['params']['id']);

        $dismissed = self::dismissPromo($id);

        if($dismissed) {
            wp_send_json_success();
        }else {
            wp_send_json_error();
        }
    }

    /**
     * @throws Exception
     */
    public static function addPromoNotice() {

        $current_user = wp_get_current_user();
        $first_name = ucfirst(strtolower(!empty($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name));

        $promo = self::getActivePromo();
        $startYear = $promo->start->format('Y');
        $endYear = $promo->end->format('Y');

        $promoDays = $promo->end->diff($promo->start)->days;

        if($promoDays === 0) {

            $endDate = esc_html__('the end of the day', 'wpide');

        }else if($promoDays === 1) {

            $endDate = esc_html__('tomorrow', 'wpide');

        }else{

            $endDateFormat = 'M jS';

            if($startYear !== $endYear) {
                $endDateFormat .= ', Y';
            }
            $endDate = $promo->end->format($endDateFormat);
        }

        $action = SLUG."_promo_dismiss_action";

        $params = [
            'id' => $promo->id
        ];

        $dataParams = htmlspecialchars(json_encode($params), ENT_QUOTES, 'UTF-8');

        ?>
        <div class="notice notice-info wpide-notice" data-slug="<?php echo esc_attr(SLUG); ?>">
            <p><?php echo sprintf(esc_html__("Hey %s, %s is LIVE until %s!", "wpide"), $first_name, '<strong>' . $promo->name . '</strong>', '<strong>' . $endDate . '</strong>'); ?>
            <p><?php echo sprintf(esc_html__('Get %s OFF on a %s license using this coupon: %s', "wpide"), "<strong>$promo->discount</strong>", "<strong>".NAME."</strong>", "<strong>$promo->coupon</strong>"); ?></p>
            <ul class="wpide-notice-action" data-action="<?php echo esc_attr($action);?>" data-nonce="<?php echo wp_create_nonce( App::instance()->prefix('promo_dismiss_nonce') ) ?>">
                <li><span class="dashicons dashicons-thumbs-up"></span> <a href="<?php echo esc_url(Freemius::sdk()->get_upgrade_url());?>"><?php echo esc_html__( 'Upgrade', 'wpide' ) ?></a></li>
                <li><span class="dashicons dashicons-products"></span> <a target="_blank" href="<?php echo esc_url(App::instance()->getExternalUrl('promo-notice', AUTHOR_URL.'/plugins'));?>"><?php echo esc_html__( 'More Products', 'wpide' ) ?></a></li>
                <li><span class="dashicons dashicons-dismiss"></span> <a data-action="dismiss" data-params="<?php echo $dataParams;?>" href="#"><?php echo esc_html__( 'Dismiss', 'wpide' ) ?></a></li>
            </ul>
        </div>
        <?php
    }

    public static function enqueueAdminAssets()
    {

        wp_enqueue_script(SLUG.'-notice', ASSETS_URL.'global/js/notice-min.js', [], VERSION);
        wp_enqueue_style(SLUG.'-notice', ASSETS_URL.'global/css/notice.css', [], VERSION);
    }

}
