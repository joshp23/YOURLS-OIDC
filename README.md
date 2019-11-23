# YOURLS-OIDC
OpenID Connect Authentication for YOURLS

This plugin enables authentication against a generic OpenID Connect server in YOURLS. 

### Features
- Respects YOURLS auth flow
- Respects YOURLS hard-coded logins
- Can link OpenID Connect accounts to existing YOURLS accounts
- Sets user to `sub`, sets display name to `preferred_username`
- Single Sign Out: signing out of YOURLS signs off OIDC server.

### Requirements
- YOURLS 7.4.0
- The [jumbojett/OpenID-Connect-PHP](https://github.com/jumbojett/OpenID-Connect-PHP) library
- `composer`, `php-curl` and `php-json`
- A working OpenID Connect servier (Tested against Keycloak)
- If installed, remove [dgw/yourls-dont-track-admins](https://github.com/dgw/yourls-dont-track-admins), or replace it with [joshp23/YOURLS-No-Tracking-Admins](https://github.com/joshp23/YOURLS-No-Tracking-Admins) for compatability.

### Installation
1. Download this repo and extract the `oidc` folder into `YOURLS/user/plugins/`
2. `cd` to the directory you just created
3. Run `composer install` in that directory to fetch the OIDC library
4. Configure the plugin (see below)
5. Enable in Admin

Configuration
-------------
Config: `user/config.php` file.
```
// oidc server
define( 'OIDC_BASE_URL', 'https://keycloak.example.com/auth/realms/master/' );
define( 'OIDC_CLIENT_NAME', 'YOURLS' );
define( 'OIDC_CLIENT_SECRET', 'YOUR-SUPER-SECRET-HASH' );
// identity mapping (optional)
$oidc_profiles = array( 
	'YOURLS_UNAME' => 'sub attribute from OIDC provider',
);
```
### In Development
- Tight integration with AuthMgrPlus
	- Group and attribute assignment
- User panel in admin for linking accounts with the push of a button

### Support Dev
All of my published code is developed and maintained in spare time, if you would like to support development of this, or any of my published code, I have set up a Liberpay account for just this purpose. Thank you.

<noscript><a href="https://liberapay.com/joshu42/donate"><img alt="Donate using Liberapay" src="https://liberapay.com/assets/widgets/donate.svg"></a></noscript>

License
-------
Copyright 2019 Joshua Panter  
