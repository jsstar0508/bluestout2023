<?php
if ( ! function_exists( 'str_contains' ) ) {
    /**
     * Polyfill for `str_contains()` function added in PHP 8.0. since WP 5.9.0
     *
     * Performs a case-sensitive check indicating if needle is
     * contained in haystack.
     *
     * @param string $haystack The string to search in.
     * @param string $needle   The substring to search for in the haystack.
     * @return bool True if `$needle` is in `$haystack`, otherwise false.
     *@since 1.0.0
     *
     */
    function str_contains(string $haystack, string $needle ): bool
    {
        return ( '' === $needle || false !== strpos( $haystack, $needle ) );
    }
}