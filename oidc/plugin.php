<?php
/*
Plugin Name: oidc
Plugin URI: https://github.com/joshp23/YOURLS-OIDC
Description: Enables OpenID Connect user authentication
Version: 0.1.0
Author: Josh Panter
Author URI: https://unfettered.net
*/
// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

require_once __DIR__.'/vendor/autoload.php';

global $oidc;
$oidc = new Jumbojett\OpenIDConnectClient(
			OIDC_BASE_URL,
			OIDC_CLIENT_NAME,
			OIDC_CLIENT_SECRET
		);

yourls_add_filter( 'is_valid_user', 'oidc_auth' );
function oidc_auth($valid) {
	// check for correct context
	if ( !yourls_is_API()  && !$valid ) {
		global $oidc;
		$oidc->authenticate();
		$user_id = $oidc->requestUserInfo('sub');
		$pref_uname = $oidc->requestUserInfo('preferred_username');
		if ($user_id) {	
			$valid = true;
			$id = $user_id;
			$display_name = $pref_name;
			$valid = true;
			global $yourls_user_passwords;
			global $oidc_profiles;
			foreach( $oidc_profiles as $linked_user => $linked_hash) {
				if( $user_id == $linked_hash ) {
					foreach( $yourls_user_passwords as $yourls_user => $password) {
						if( $linked_user == $yourls_user ) {
							$id = $display_name = $yourls_user;
							break;
						}
					}
				}
			}
			yourls_set_user($id);
			setcookie('yourls_'.yourls_salt('OIDC_DISPLAY_NAME') ,$display_name );
		}
	}
	// return appropriate validation status
	return $valid;
}
yourls_add_filter( 'logout_link', 'oidc_logout_link' );
function oidc_logout_link( $data ) {
	if( isset($_COOKIE['yourls_'.yourls_salt('OIDC_DISPLAY_NAME')]) ) {
		$name = $_COOKIE['yourls_'.yourls_salt('OIDC_DISPLAY_NAME')];
		$data = sprintf( yourls__('Hello <strong>%s</strong>'), $name ) . ' (<a href="' . yourls_admin_url() . '?action=logout" title="' . yourls_esc_attr__( 'Logout' ) . '">' . yourls__( 'Logout' ) . '</a>)' ;
	}
	return $data;
}

yourls_add_action( 'logout', 'oidc_logout' );
function oidc_logout() {
	setcookie('yourls_'.yourls_salt('OIDC_DISPLAY_NAME') ,'', time() - 3600);
	yourls_store_cookie( null );
	global $oidc;
	$oidc->signOut( null, YOURLS_SITE );
}
?>
