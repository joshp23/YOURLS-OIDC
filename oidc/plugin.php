<?php
/*
Plugin Name: oidc
Plugin URI: https://github.com/joshp23/YOURLS-OIDC
Description: Enables OpenID Connect user authentication
Version: 0.3.1
Author: Josh Panter, Francesco Servida
Author URI: https://unfettered.net, https://francescoservida.ch
*/
// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();
if( !defined( 'OIDC_BYPASS_YOURLS_AUTH' ) ) 
	define( 'OIDC_BYPASS_YOURLS_AUTH', false );
	
require_once __DIR__.'/vendor/autoload.php';
global $oidc;
$oidc = new Jumbojett\OpenIDConnectClient(
			OIDC_BASE_URL,
			OIDC_CLIENT_NAME,
			OIDC_CLIENT_SECRET
		);

if( defined( 'OIDC_REDIRECT_URL' ) ) $oidc->setRedirectUrl(OIDC_REDIRECT_URL);

yourls_add_filter( 'is_valid_user', 'oidc_auth' );
function oidc_auth( $valid ) {
	// check for correct context
	if ( !yourls_is_API() && isset($_COOKIE[yourls_cookie_name()]) ) {
                if ( isset($_COOKIE['OIDC_username']) ) {
                        if ( yourls_cookie_value($_COOKIE['OIDC_username']) === $_COOKIE[yourls_cookie_name()] ){
                                yourls_set_user($_COOKIE['OIDC_username']);
                                $valid = true;
                        }
                }
        }
	if ( !yourls_is_API() && !$valid ) {
		global $oidc;
		$oidc->authenticate();
		if ( OIDC_BYPASS_YOURLS_AUTH ) {
			$user = $oidc->requestUserInfo('preferred_username');
			if ( $user ) {
				yourls_set_user($user);
				oidc_cookie_login($user);
				$valid = true;
			}
		} else {
			$id = $oidc->requestUserInfo('sub');
			if ( $id ) {
				global $oidc_profiles;
				foreach( $oidc_profiles as $user => $hash) {
					if( $id == $hash ) {
						yourls_set_user($user);
						$valid = true;
					}
				}
			}
		}
	}
	return $valid;
}

function oidc_cookie_login($user){
        $time = time() + yourls_get_cookie_life();
        $path     = yourls_apply_filter( 'setcookie_path',     '/' );
        $domain   = yourls_apply_filter( 'setcookie_domain',   parse_url( yourls_get_yourls_site(), PHP_URL_HOST ) );
        $secure   = yourls_apply_filter( 'setcookie_secure',   yourls_is_ssl() );
        $httponly = yourls_apply_filter( 'setcookie_httponly', true );
        setcookie('OIDC_username', $user, $time, $path, $domain, $secure, $httponly);
}

yourls_add_action( 'logout', 'oidc_logout' );
function oidc_logout() {
	yourls_store_cookie( null );
	oidc_cookie_logout();
	global $oidc;
	$oidc->signOut( null, YOURLS_SITE );
}

function oidc_cookie_logout(){
        $path     = yourls_apply_filter( 'setcookie_path',     '/' );
        $domain   = yourls_apply_filter( 'setcookie_domain',   parse_url( yourls_get_yourls_site(), PHP_URL_HOST ) );
        $secure   = yourls_apply_filter( 'setcookie_secure',   yourls_is_ssl() );
        $httponly = yourls_apply_filter( 'setcookie_httponly', true );
        setcookie("OIDC_username", "", time() - 3600, $path, $domain, $secure, $httponly);
}

// Largely unchanged: only checking auth against w/ cookies.
yourls_add_filter( 'shunt_check_IP_flood', 'oidc_check_ip_flood' );
function oidc_check_ip_flood ( $ip ) {
	// don't touch API logic
	if ( yourls_is_API() ) return false;
	
	yourls_do_action( 'pre_check_ip_flood', $ip ); // at this point $ip can be '', check it if your plugin hooks in here

	// Raise white flag if installing or if no flood delay defined
	if(
		( defined('YOURLS_FLOOD_DELAY_SECONDS') && YOURLS_FLOOD_DELAY_SECONDS === 0 ) ||
		!defined('YOURLS_FLOOD_DELAY_SECONDS') ||
		yourls_is_installing()
	)
		return true;

	// Don't throttle logged in users XXX and don't trigger OIDC login!
	if( yourls_is_private() && isset( $_COOKIE[ yourls_cookie_name() ] ) && yourls_check_auth_cookie() ) {
		yourls_store_cookie( YOURLS_USER );
		return true;
	}

	// Don't throttle whitelist IPs
	if( defined( 'YOURLS_FLOOD_IP_WHITELIST' ) && YOURLS_FLOOD_IP_WHITELIST ) {
		$whitelist_ips = explode( ',', YOURLS_FLOOD_IP_WHITELIST );
		foreach( (array)$whitelist_ips as $whitelist_ip ) {
			$whitelist_ip = trim( $whitelist_ip );
			if ( $whitelist_ip == $ip )
				return true;
		}
	}

	$ip = ( $ip ? yourls_sanitize_ip( $ip ) : yourls_get_IP() );

	yourls_do_action( 'check_ip_flood', $ip );

	global $ydb;
	$table = YOURLS_DB_TABLE_URL;

	$lasttime = $ydb->fetchValue( "SELECT `timestamp` FROM $table WHERE `ip` = :ip ORDER BY `timestamp` DESC LIMIT 1", array('ip' => $ip) );
	if( $lasttime ) {
		$now = date( 'U' );
		$then = date( 'U', strtotime( $lasttime ) );
		if( ( $now - $then ) <= YOURLS_FLOOD_DELAY_SECONDS ) {
			// Flood!
			yourls_do_action( 'ip_flood', $ip, $now - $then );
			yourls_die( yourls__( 'Too many URLs added too fast. Slow down please.' ), yourls__( 'Too Many Requests' ), 429 );
		}
	}

	return true;
}

?>
