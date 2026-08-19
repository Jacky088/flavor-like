<?php
/**
 * Front-end AJAX Hooks
 * // @echo HEADER
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/*******************************************************
  Start AJAX From Here
*******************************************************/

/**
 * AJAX function for all votings process
 *
 * @return void
 */
function flavor_like_process(){
	new flavor_like_cta_listener;
}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_flavor_like_process'			, 'flavor_like_process' );
add_action( 'wp_ajax_nopriv_flavor_like_process'	, 'flavor_like_process' );

/**
 * AJAX function for voters process
 */
function flavor_like_get_likers(){
	new flavor_like_voters_listener;
}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_flavor_like_get_likers'		 , 'flavor_like_get_likers' );
add_action( 'wp_ajax_nopriv_flavor_like_get_likers' , 'flavor_like_get_likers' );