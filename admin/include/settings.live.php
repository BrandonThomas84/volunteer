<?php

/** Prevent direct access */
if (basename($_SERVER['PHP_SELF']) == 'settings.inc') { 
	die('You cannot load this page directly.');
}

//default time zone
date_default_timezone_set('America/Los_Angeles');

# Directory Separator
define('_DIRSEP_',DIRECTORY_SEPARATOR);

# Root Directory (no trailing slash)
define('_FRONTEND_URL_','volunteer.perspektivedesigns.com');

# Root Directory (no trailing slash)
define('_ROOTURL_','volunteer.perspektivedesigns.com/admin');

# Template Directory
define('__TEMPLATES__', __INCLUDE_PATH__ . 'templates' . _DIRSEP_ );

# Email Template Directory
define('__EMAILTEMPLATES__', __INCLUDE_PATH__ . 'templates' . _DIRSEP_  . 'email' . _DIRSEP_ );

# Page HTML Template Directory
define('__PAGETEMPLATES__', __INCLUDE_PATH__ . 'templates' . _DIRSEP_  . 'page' . _DIRSEP_ );

//change this value to TRUE to force all pages to render in https only
$REQUIRESECURE = true;

//set the directory value addition
if($REQUIRESECURE){
	
	//redirects to https if secure is forced
	if ($_SERVER["SERVER_PORT"] != 443 || $_SERVER["HTTPS"] != "on" || empty($_SERVER['HTTPS'])) {
    	$redir = "Location: https://" . _ROOTURL_ ;
    	header($redir);
	}

	define('_PROTOCOL_', 'https://');

} else {
	
	define('_PROTOCOL_', 'https://');
}

//settings constants

# Site Root
define('_ROOT_', _PROTOCOL_ . _ROOTURL_ );	

# Secure Site Root
define('_SECURE_ROOT_','https://' . _ROOTURL_ );

# Database host name
define('_DB_SERVER_','localhost');

# Database user name
define('_DB_USER_','vol_user');

# Database password
define('_DB_PASSWD_','8dvNhwvv^NjxnQnwFQE9&Eg');

# Database name
define('_DB_NAME_','volunteer');

# Max Number of Login Attempts before lockout
define('_MAX_LOGINS_ATTEMPTS_','5');

# Lockout duration in seconds set as non constant to allow for updating
$lockout_duration = 1800;

# Default Page Author
define('_AUTHOR_','Brandon Thomas');

# Company Name
define('_CO_NAME_','Narcotics Anonymous');

# Default page title
define('_DEFAULT_TITLE_','Volunteer System');

# System User Information
define('_CS_NAME_','Customer Service');
define('_CS_ADDRESS_1_','557 Eaton Rd');
define('_CS_ADDRESS_2_',"2");
define('_CS_CITY_','Chico');
define('_CS_STATE_','California');
define('_CS_POSTAL_CODE_','95973');
define('_COUNTRY_','USA');
define('_CS_PHONE_','+1-(530)774-7991');
define('_CS_EMAIL_','cs@perspektivedesigns.com');
define('_EMAIL_REGEX_','@perspektivedesigns.com');

$mysqli = new mysqli(_DB_SERVER_,_DB_USER_,_DB_PASSWD_,_DB_NAME_);

?>