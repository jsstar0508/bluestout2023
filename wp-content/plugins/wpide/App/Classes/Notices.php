<?php

namespace WPIDE\App\Classes;

use DOMDocument;

class Notices
{
    protected static $notices = [];

    public static function init()
    {

        libxml_use_internal_errors(true);

        self::removeNotices('admin_notices');
        self::removeNotices('network_admin_notices');
    }
    
    public static function removeNotices($hook_name) {
        
        global $wp_filter;

        if (isset($wp_filter[$hook_name])) {

            foreach ($wp_filter[$hook_name]->callbacks as $hooks) {

                foreach ($hooks as $hook) {

                    ob_start();
                    call_user_func_array($hook["function"], []);
                    $notice = ob_get_clean();
                    $notice = trim($notice);
                    if (!empty($notice)) {
                        self::setNotice($notice);
                    }
                }
            }

            if(in_array($hook_name, ['admin_notices', 'network_admin_notices'])) {
                remove_all_actions($hook_name);
            }

            add_action('wpide_inline_scripts', [__CLASS__, 'appendInlineScript'], 1);
        }
    }
    
    public static function appendInlineScript($scripts): string
    {

        $ajaxurl = is_network_admin() ? 'ajaxurl + "?_fs_network_admin=true"' : 'ajaxurl + "?_fs_blog_admin=true"';

        $scripts .= '
            (function( $ ) {
                $(function() {
                
                    $( document ).on("click", ".fs-notice.fs-sticky .fs-close", function() {
                        var
                            notice           = $( this ).parents( ".fs-notice" ),
                            container        = $( this ).parents( ".nk-notification-item" ),
                            index            = container.index(),
                            id               = notice.attr( "data-id" ),
                            ajaxActionSuffix = notice.attr( "data-manager-id" ).replace( ":", "-" );
            
                        notice.fadeOut( "fast", function() {
                            var data = {
                                action   : "fs_dismiss_notice_action_" + ajaxActionSuffix,
                                // As such we don"t need to use `wp_json_encode` method but using it to follow wp.org guideline.
                                _wpnonce : '.wp_json_encode( wp_create_nonce( "fs_dismiss_notice_action" ) ).',
                                message_id: id
                            };
            
                            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                            $.post('.$ajaxurl.', data);
            
                            var event = new CustomEvent("wpide_notice_removed", { detail: index });
                            document.body.dispatchEvent(event);

                        });
                    });
                });
            })( jQuery );
        ';

        return $scripts;
    }

    public static function isValid($notice): bool
    {

        if(!str_contains($notice, 'data-slug="wpide"')) {
            return false;
        }

        return true;
    }
    public static function setNotice($notice)
    {
        if(!self::isValid($notice)) {
          return;
        }

        // check if notice has multiple notices, split them into separate notices.
        preg_match_all('/data-slug\="wpide"/ms', $notice, $matches);
        if(!empty($matches[0]) && count($matches[0]) > 1) {

            $dom = new DOMDocument();

            $dom->LoadHTML($notice);
            $body = $dom->getElementsByTagName( 'body' )->item( 0 );
            foreach( $body->childNodes as $node )
            {
                $notice = $dom->saveHTML($node);
                $notice = trim($notice);
                if(!empty($notice)) {
                    $notice = self::format($notice);
                    self::$notices[] = $notice;
                }
            }

        }else {
            $notice = self::format($notice);
            self::$notices[] = $notice;
        }
    }

    protected static function format($notice): array
    {
        $type = 'info';
        if (str_contains($notice, 'error') || str_contains($notice, 'danger')) {
            $type = 'error';
        } else if (str_contains($notice, 'warning')) {
            $type = 'warning';
        } else if (str_contains($notice, 'success') || str_contains($notice, 'updated')) {
            $type = 'success';
        }

        $findTagStart = [
            '<span',
            '<b>'
        ];
        $findTagEnd = [
            '</span>',
            '</b>'
        ];
        $replaceTagStart = [
            '<strong',
            '<strong>'
        ];
        $replaceTagEnd = '</strong>';

        $notice = strip_tags($notice, ['a', 'p', 'div', 'span', 'strong', 'br', 'b', 'label', 'ul', 'li']);

        $notice = str_replace($findTagStart, $replaceTagStart, $notice);
        $notice = str_replace($findTagEnd, $replaceTagEnd, $notice);

        $notice = self::removeEmptyTags($notice);

        return [
            'type' => $type,
            'content' => $notice
        ];
    }

    /*
    * @param    string    $str    String to remove tags.
    * @param    string    $replace    Replace empty string with.
    * @return   string    Cleaned string.
    */
    public static function removeEmptyTags($str, $replace = NULL)
    {
        //** Return if string not given or empty.
        if (!is_string($str)
            || trim($str) == '')
            return $str;

        //** Recursive empty HTML tags.
        return preg_replace(

            '/<([^<\/>]*)>([\s]*?|(?R))<\/\1>/imsU',

            //** Replace with nothing if string empty.
            !is_string($replace) ? '' : $replace,

            //** Source string
            $str
        );
    }

    public static function all(): array
    {
        return self::$notices;
    }

    public static function count(): int
    {
        return count(self::$notices);
    }
}
