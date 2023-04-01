<?php

namespace WPIDE\App\Classes;

use WPIDE\App\App;
use const WPIDE\Constants\AUTHOR;
use const WPIDE\Constants\VERSION;

class RecommendedPlugins
{

    public static function init()
    {

        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets'], 99);
        add_filter('plugins_api_result', [__CLASS__, 'plugin_results'], 1, 3);

    }

    public static function is_plugin_install_page(): bool
    {

        $screen = get_current_screen();

        return (!empty($screen) && ($screen->id === 'plugin-install' || $screen->id === 'plugin-install-network'));

    }

    public static function is_plugin_install_page_xt_tabs($_tab = false): bool
    {

        $isPluginInstallPage = self::is_plugin_install_page();

        $tab = !empty(filter_input(INPUT_POST, 'tab')) ? filter_input(INPUT_POST, 'tab') : filter_input(INPUT_GET, 'tab');

        if ($_tab) {

            return $isPluginInstallPage && ($tab === $_tab);
        }

        return $isPluginInstallPage && $tab == 'search';
    }

    public static function enqueue_assets()
    {

        if (!App::instance()->isPluginScreen() && !self::is_plugin_install_page_xt_tabs()) {
            return;
        }

        $handle = App::instance()->prefix('plugins');

        wp_register_script( $handle, false, array('jquery'), VERSION );

        wp_add_inline_script($handle, '
            (function( $ ) {
               
                function alter_frame(frame) {
                
                    var doc = $(frame).contents().get(0);
                    var filter = 5;
                
                    $(".counter-container", doc).each(function() {
                
                        var has_reviews = parseInt($(this, doc).find(".counter-count").text().trim()) > 0;
                        var link = $(this, doc).find(".counter-label a");
                        var plugin_link = $("#plugin-information-content > .fyi > ul:first-child", doc).find("li").last().find("a");
                
                        if(!has_reviews) {
                
                            link.removeAttr("href");
                
                        }else if(plugin_link.length){
                
                            link.attr("href", plugin_link.attr("href")+"?filter="+filter+"#ratings");
                        }
                
                        filter--;
                    });
                }
                
                var observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        [].filter.call(mutation.addedNodes, function (node) {
                            return node.nodeName == "IFRAME" && node.id === "TB_iframeContent";
                        }).forEach(function (frame) {
                            frame.addEventListener("load", function (e) {
                                alter_frame(frame);
                            });
                        });
                    });
                });
                $(document).ready(function() {
                    observer.observe(window.document.body, { childList: true, subtree: true });
                });
            })( jQuery );
        ');

        wp_enqueue_script( $handle );
    }

    /**
     * Gets the current plugin page number.
     *
     * @return int
     */
    public static function get_pagenum(): int
    {

        $pagenum = isset($_REQUEST['paged']) ? absint($_REQUEST['paged']) : 0;

        return max(1, $pagenum);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public static function get_plugins()
    {

        return App::instance()->cache()->result('get_plugins', function () {

            $result = plugins_api('query_plugins', array(
                'page' => self::get_pagenum(),
                'per_page' => 36,
                'author' => AUTHOR,
                'xt_plugins_query' => true
            ));

            $result->plugins = self::alter_plugin_results($result->plugins);

            return $result;

        });
    }

    public static function plugin_results($res, $action, $args)
    {

        if (is_wp_error($res)) {
            return $res;
        }

        if ($action === 'query_plugins') {

            $new_result = self::alter_query_plugins($res, $action, $args);

            if (is_wp_error($new_result)) {
                return $res;
            }

            if (self::is_plugin_install_page_xt_tabs()) {

                $position = 4;
                $position_middle = 6;
                $position_middle = max($position, $position_middle);

                $top_plugins = array_splice($res->plugins, 0, $position);
                $middle_plugins = array_merge($new_result->plugins, array_splice($res->plugins, $position, $position_middle));
                $below_plugins = array_splice($res->plugins, $position_middle);
                shuffle($middle_plugins);

                $res->plugins = array_merge($top_plugins, $middle_plugins, $below_plugins);

            }

        } else if ($action === 'plugin_information') {

            if (!empty($res->author) && strpos($res->author, AUTHOR)) {

                $res = json_decode(json_encode($res), true);

                return (object)self::alter_plugin_info($res);
            }
        }

        return $res;
    }

    public static function search_contains($needles, $haystack): int
    {
        return count(array_intersect($needles, explode(" ", preg_replace("/[^A-Za-z0-9' -]/", "", $haystack))));
    }

    public static function search_terms(): array
    {
        return array('woo', 'woocommerce', 'cart', 'quick view', 'quickview', 'points', 'rewards', 'swatches', 'attributes', 'variation', 'variations');
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public static function alter_query_plugins($res, $action, $args)
    {

        $args = (array)$args;

        if (
            !empty($args['xt_plugins_query']) ||
            !empty($args['author']) ||
            (!empty($args['search']) && !self::search_contains(self::search_terms(), $args['search'])) ||
            (!empty($args['tag']) && !self::search_contains(self::search_terms(), $args['tag']))
        ) {
            $res->plugins = self::alter_plugin_results($res->plugins);
            return $res;
        }

        return self::get_plugins();
    }

    public static function is_plugin_active($slug): bool
    {

        $slug = str_replace('-lite', '', $slug);

        $active_plugins = wp_cache_get('active_plugins');
        if ($active_plugins === false) {
            $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
            wp_cache_set('active_plugins', $active_plugins);
        }

        foreach ($active_plugins as $plugin) {

            if (strpos($plugin, $slug) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function alter_plugin_results($plugins): array
    {

        $slugs = array();

        $plugins = array_map(function ($plugin) use (&$slugs) {

            if (is_object($plugin)) {
                return $plugin;
            }

            if (strpos($plugin['author'], AUTHOR)) {

                if (in_array($plugin['slug'], $slugs)) {
                    $plugin['duplicated'] = true;
                }

                $slugs[] = $plugin['slug'];

                return self::alter_plugin_info($plugin);
            }

            return $plugin;

        }, $plugins);

        $plugins = array_filter($plugins, function ($plugin) {

            if (is_object($plugin)) {
                return $plugin;
            }

            if (!empty($plugin['duplicated'])) {
                return false;
            }

            if (self::is_plugin_install_page_xt_tabs() && self::is_plugin_active($plugin['slug'])) {
                return false;
            }

            return true;
        });

        return $plugins;
    }

    public static function alter_plugin_info($plugin)
    {

        if (!empty($plugin['ratings'])) {

            $total_rating_value = 0;
            $ratings = array();

            foreach ($plugin['ratings'] as $rating => $total) {

                $total = absint($total);

                if (absint($rating) < 4 && $total > 0) {
                    $total = 0;
                }

                $ratings[$rating] = $total;
                $total_rating_value += ($rating * $total);
            }

            $plugin['ratings'] = $ratings;
            if (!empty($plugin['num_ratings'])) {
                $plugin['num_ratings'] = $plugin['ratings']['5'] + $plugin['ratings']['4'];
                $plugin['rating'] = ((($total_rating_value / $plugin['num_ratings']) * 100) / 5);
            }
        }

        if (!empty($plugin['sections']) && !empty($plugin['sections']['reviews'])) {

            $reviews = str_replace('<div class="review">', '|<div class="review">', $plugin['sections']['reviews']);
            $reviews = substr($reviews, 1);
            $reviews = explode('|', $reviews);

            $reviews = array_filter($reviews, function ($review) {

                preg_match('/data-rating\=\"(4|5)\"/', $review, $match);
                return !empty($match[1]);
            });

            $plugin['sections']['reviews'] = implode('', $reviews);
        }

        $plugin['external'] = true;
        $plugin['homepage'] = App::instance()->getExternalUrl('plugin-info', $plugin['homepage']);
        $plugin['author_profile'] = App::instance()->getExternalUrl('plugin-info');
        $plugin['author'] = sprintf('<a href="%s" target="_blank">%s</a>', $plugin['author_profile'], AUTHOR);

        if (!empty($plugin['contributors'])) {
            foreach ($plugin['contributors'] as $key => $contributor) {

                if ($key === strtolower(AUTHOR)) {

                    $contributor['profile'] = $plugin['author_profile'];
                    $contributor['display_name'] = AUTHOR;
                    $plugin['contributors'][$key] = $contributor;
                }
            }
        }

        return $plugin;
    }

}
