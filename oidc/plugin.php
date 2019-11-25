<?php
/*
Plugin Name: oidc
Plugin URI: https://github.com/joshp23/YOURLS-OIDC
Description: Enables OpenID Connect user authentication
Version: 0.2.0
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
			global $yourls_user_passwords;
			global $oidc_profiles;
			foreach( $oidc_profiles as $local_user => $local_hash) {
				if( $user_id == $local_hash )
					$id = $local_user;
			}
			$valid = true;
			yourls_set_user($id);
		}
	}
	// return appropriate validation status
	return $valid;
}

yourls_add_action( 'logout', 'oidc_logout' );
function oidc_logout() {
	yourls_store_cookie( null );
	global $oidc;
	$oidc->signOut( null, YOURLS_SITE );
}
?>
