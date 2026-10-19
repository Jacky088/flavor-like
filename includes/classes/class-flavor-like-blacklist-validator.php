<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class flavor_like_blacklist_validator {
    /**
     * @return bool
     */
    public static function isValid( $args = array() )
    {
        $target = implode("\n", array_filter($args));
        $isValid = self::validateBlacklist($target);
        return apply_filters('flavor_like_validate_blacklist', $isValid, $target, $args );
    }

    /**
     * @return string
     */
    protected static function blacklist()
    {
        $blacklist_option = 'comments' === flavor_like_get_option('blacklist_integration')
            ? get_option('disallowed_keys')
            : flavor_like_get_option('blacklist_entries');

        // Ensure $blacklist_option is a string before passing to trim()
        return trim((string) $blacklist_option);
    }

    /**
     * @param string $target
     * @return bool
     */
    protected static function validateBlacklist($target)
    {
        if (empty($blacklist = self::blacklist())) {
            return true;
        }
        $target = trim( (string) $target );
        $is_target_ip = class_exists( 'Flavor_Like_Ip_Detector' ) ? Flavor_Like_Ip_Detector::validate_ip( $target ) : filter_var( $target, FILTER_VALIDATE_IP );

        $lines = explode("\n", $blacklist);
        foreach ((array) $lines as $line) {
            $line = trim($line);
            if (empty($line) || 256 < strlen($line)) {
                continue;
            }

            // Handle IP & CIDR matching if target is an IP
            if ( $is_target_ip ) {
                if ( strpos( $line, '/' ) !== false && class_exists( 'Flavor_Like_Ip_Detector' ) ) {
                    if ( Flavor_Like_Ip_Detector::ip_in_range( $target, $line ) ) {
                        return false;
                    }
                    continue;
                } elseif ( strcasecmp( $line, $target ) === 0 ) {
                    return false;
                }
            }

            // Word-boundary match for words/keys to prevent substring false positives
            $pattern = sprintf( '#(?<![0-9a-zA-Z._-])%s(?![0-9a-zA-Z._-])#i', preg_quote( $line, '#' ) );
            if ( preg_match( $pattern, $target ) ) {
                return false;
            }
        }
        return true;
    }
}