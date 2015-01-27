<?php 
	

/**
PREVENT DIRECT ACCESS TO THIS FILE
*/
if (basename($_SERVER['PHP_SELF']) == 'functions.php') { 
	die('You cannot load this page directly.');
}

require_once( __INCLUDE_PATH__ . 'settings.php');
require_once( __FUNCTION_INCLUDE__ . 'db_functions.php');

//attribute type text helper
$attr_type_settings = array(
	'greater_than_hrs'	=>array('helper'=>'Minimum','helper_end'=>'Hours'),
	'greater_than_dys'	=>array('helper'=>'Minimum','helper_end'=>'Days'),
	'greater_than_mns'	=>array('helper'=>'Minimum','helper_end'=>'Minuts'),
	'greater_than_wks'	=>array('helper'=>'Minimum','helper_end'=>'Weeks'),
	'greater_than_mnths'=>array('helper'=>'Minimum','helper_end'=>'Months'),
	'greater_than_yrs'	=>array('helper'=>'Minimum','helper_end'=>'Years'),
	'less_than_mns'		=>array('helper'=>'Maximum','helper_end'=>'Minuts'),
	'less_than_hrs'		=>array('helper'=>'Maximum','helper_end'=>'Hours'),
	'less_than_dys'		=>array('helper'=>'Maximum','helper_end'=>'Days'),
	'less_than_wks'		=>array('helper'=>'Maximum','helper_end'=>'Weeks'),
	'less_than_mnths'	=>array('helper'=>'Maximum','helper_end'=>'Months'),
	'less_than_yrs'		=>array('helper'=>'Maximum','helper_end'=>'Years'),
	'match'				=>array('helper'=>'Must be','helper_end'=>''),
	'no_match'			=>array('helper'=>'Must not be','helper_end'=>''),
);


/**
SET PHP CONSTANTS FROM THE DATABASE
*/
function set_database_constants(){
	
	$options = array('software_key','site_url','lockout_duration');
	foreach( $options as $option ){
		$value = get_option_by_name( $option );
		if( !empty( $value ) ){
			$value = $value['option_value'];
			define( '__' . strtoupper( $option ) . '__', $value );
		} else {
			define( '__' . strtoupper( $option ) . '__', false );
		}
	}
}

/**
VERIFY EXISTANCE OF DATABASE
*/
function verify_install(){
	global $mysqli;

	//get the table information
	$query = "SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . _DB_NAME_ . "'";
	$stmt = $mysqli->query( $query );

	if( !$stmt || mysqli_num_rows( $stmt ) < 12 ){		
		return false;
	} else {
		return true;
	}
}


/**
RETURNS TRUE IF DEVELOPERMODE IS ENABLED
*/
function check_dev_mode(){

	//makes sure it's only set once
	if( !defined( '__DEVELOPMENT_MODE__' ) ){
		//set dev mode
		$dev_mode = get_option_by_name('development_mode');
		if( $dev_mode['option_value'] == 'on' ){
			define('__DEVELOPMENT_MODE__', true );
			error_reporting(E_ALL);
			ini_set('error_reporting', E_ALL);
			ini_set("display_errors", 1);
		} else {
			define('__DEVELOPMENT_MODE__', false );
		}
	}
		
	if( __DEVELOPMENT_MODE__ ){
		return true;
	} else {
		return false;
	}	
}

/**
CHECK AND SET THE PAGE VARIABLE TO CONTROLL THE CALLED CLASSES
*/
function setPageVars(){
	if( isset( $_GET['page'] ) ){ 
		$page = $_GET['page'];  
	} else { 
		$page = 'home'; 
	}

	return $page;
}
	
/**
CREATE THE ADMIN AUTO LOADER
*/
function __autoload($classname){

	//if there is an admin session active
	if( in_array( $classname, array('login','processLogin','calendar') ) || !checkAdminSession() ){
		include( __INCLUDE_PATH__ . 'class' . _DIRSEP_ . $classname . '.class.php' );
	} else {
		include( __INCLUDE_PATH__ . 'admin_class' . _DIRSEP_ . $classname . '.class.php' );
	} 
}
/**
RETURNS TRUE IF USER IS IN ADMIN AREA
*/
function checkAdminSession(){
	if( isset( $_COOKIE['admin_session'] ) ){
		return true;
	} else {
		return false;
	}
}
/**
RETURNS TRUE IF USER HAS STARTED AN ACTIVE SESSION
*/
function checkForSession(){

	if( isset( $_COOKIE[ 'PHPSESSID' ] ) ){
		return true;
	} else {
		return false;
	}
}
/**
START A SESSION
*/
function sessionInit(){
	//start session
	session_start();
}
/**
USED FOR SELECT INPUT FIELDS THIS CHECKS VALUES TO RETURN THE SELECTED ONE
*/
function returnSelected($check,$success){
	if($check == $success){
		return ' selected ';
	}
}
/**
USED FOR CHECKBOX INPUTS RETURNS A CHECKED VALUE
*/
function returnChecked($check,$success){
	if($check == $success){
		return ' checked ';
	}
}
/**
USED FOR BUTTONS AND MENU LINKS
*/
function returnActive($check,$success){
	if($check == $success){
		return ' active ';
	}
}
/**
USED TO CONVERT STRING TO SLUG FORMAT
*/
function stringToSlug($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens
   $string = str_replace('_', '-', $string); // Replaces all underscores with hyphens

   return preg_replace('/[^A-Za-z0-9\-]/', '', strtolower( $string ) ); // Removes special chars.
}
/**
USED TO CONVERT STRING TO SLUG FORMAT
*/
function stringToLowerUnderscores($string) {
   $string = str_replace(' ', '_', $string); // Replaces all spaces with underscores.
   $string = str_replace('-', '_', $string); // Replaces all hyphens with underscores
   $string = str_replace('&', '_', $string); // Replaces all ampersands with underscores

   return preg_replace('/[^A-Za-z0-9\_]/', '', strtolower( $string ) ); // Removes special chars.
}
/**
USED TO CHECK IF SOFTWARE IS VALIDATED
 */
function verifyLicense(){

	//get the key from the database
	$key = get_option_by_name('software_key');
	$site_url = get_option_by_name('site_url');
	$reg_first = get_option_by_name('reg_first_name');
	$reg_last = get_option_by_name('reg_last_name');
	$reg_email = get_option_by_name('reg_email');

	//if site_url is missing or not equal to actual URL
	$test_url = $_SERVER['HTTP_HOST'] . '/';
	if( !$site_url || $site_url !== $test_url ){
		updateOption( 'site_url', $_SERVER['HTTP_HOST'] . '/');
		$site_url = get_option_by_name('site_url');
	}

	if( !empty( $key ) ){

		//set the key
		$key = $key['option_value'];

		//curl url
		$url = 'http://license.perspektivedesigns.com/';

		//post params
		$params = array('url' => $site_url['option_value'],'key' => $key, 'software' => 'pd_vms');

		//set curl options
		$defaults = array( 
	        CURLOPT_POST => 1, 
	        CURLOPT_HEADER => 0, 
	        CURLOPT_URL => $url, 
	        CURLOPT_FRESH_CONNECT => 1, 
	        CURLOPT_RETURNTRANSFER => 1, 
	        CURLOPT_FORBID_REUSE => 1, 
	        CURLOPT_TIMEOUT => 4, 
	        CURLOPT_POSTFIELDS => http_build_query($params) 
	    ); 

	    $ch = curl_init(); 
	    curl_setopt_array( $ch, $defaults );

	    if( !$result = curl_exec($ch) ){ 

	        trigger_error(curl_error($ch)); 
	    } 
	    curl_close($ch); 

	    //check status
	    if( $result == base64_decode('YWN0aXZl') ){	    	

	    	//if all is good return and stop processing
	    	return true;
	    } 

	}  else {

		//no key
		$result = 'Please enter your software key';
	}

	//get the html doc head
	require_once( __TEMPLATES__ . 'admin_head.php' );	

	//get the navigation
	include_once( __TEMPLATES__ . 'admin_header.php');

	//correction html
	$html = '<div id="page-messages-container" class="container">';
	$html .= '<div class="row">'; 
	$html .= '<div class="col-sm-12 bg-danger">';
	$html .= '<p class="h3">Software Verification Error</p>';
	$html .= '<hr>';
	$html .= '<p>' . $result . '</p>';
	$html .= '</div>';
	$html .= '</div>';
	$html .= '</div><br></br>';

	//verify
	$html .= '<h3>Please Verify Registration</h3><p>Please verify that you have entered your Product Registration Key correctly. If you are confident that the key is correct you may have registered the URL incorrectly. Please contact us so that we can update your registration records.</p>';


	//start options form
	$html .= '<div class="container row-fluid">';
	$html .= '<form class="form-horizontal" role="form" action="' . _ROOT_ . '/settings" method="post">';			

		//create site url field
	$options = array('label'=>'Registered URL:','label_class'=>'col-xs-4 col-md-2 control-label','disabled'=>true, 'class'=>'col-xs-8','field_wrap'=>array('<div class="col-sm-10">','</div>') );
	$html .= '<div class="input-group col-xs-8 col-xs-offset-2">' . createFormInput('site_url', $options ) . '</div>';

	//registration key
	$options = array('label'=>'Registration Key:','label_class'=>'col-xs-4 col-md-2 control-label','required'=>true, 'class'=>'col-xs-8','field_wrap'=>array('<div class="col-sm-10">','</div>'));
	$html .= '<div class="input-group col-xs-8 col-xs-offset-2">' . createFormInput('software_key', $options) . '</div>';

	//submit form
	$options = array('input_type'=>'submit','input_value'=>'Update Settings','class'=>'btn btn-success');
	$html .= '<div class="input-group col-xs-8 col-xs-offset-2">' . createFormInput('settings_submit_button', $options) . '</div>';			

	//close form and container
	$html .= '</form></div><!-- END CONTAINER -->';

	//return form output if there is a validation error
	echo $html;
	exit;

}

/**
CREATE RANDOM STRING
*/
function createRandomString($length,$numbers=true,$special_chars=true){

	//create string of chars
	$charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	//enable number and special character usage
	$charset .= ( ($numbers) ? '0123456789' : null);
	$charset .= ( ($special_chars) ? '!@#$%^&*()<>?~' : null);
	

	//get the number of available characters
    $count = strlen($charset);

    //create str var
    $str = '';
    
    //loop for duration of length
    while($length--) {
    	//get random character from charset
        $str .= $charset[mt_rand(0, $count-1)];
    }

    return $str;
}
/**
ADD COOKIE PAGE MESSAGES
*/
function add_page_message( $type='default', $message, $title='Message' ){

	//get the existing messages or set new array
	if( !empty( $_COOKIE['page_message'] ) ){
		$messages = json_decode( $_COOKIE['page_message'], true );
	} 

	//check for existing message key
	if( !empty( $message[ $title ] ) ){
		$key = $title . '-' . date('h:i:s');
	} else {
		$key = $title;
	}
	//add new message
	$messages[ $key ] = array('type'=>$type,'msg'=>$message);	

	//re encode messages
	$new_message = json_encode( $messages );

	setcookie('page_message',$new_message, 0,'/');

}
/**
RETRIEVE MESSAGES
*/
function checkPageMessage($class=null){

	//blank value for return
	$html = '';

	//empty array for page messages
	$pageMessages = array();

	//add class page messages
	if( !empty( $class ) ){
		if( !empty( $class->pageMessage ) ){
			
			foreach( $class->pageMessage as $key=>$msg ){
				$pageMessages[ $key ] = $msg;
			}
		}
	}
	
	//add cookie page messages
	if( !empty( $_COOKIE['page_message'] ) ){
		$messages = json_decode( $_COOKIE['page_message'], true );
		if( !empty( $messages ) ){
			foreach( $messages as $key=>$msg ){
				$pageMessages[ $key ] = $msg;
			}
		}
	}
	
	//check for any page messages
	if( !empty( $pageMessages ) ){

		//check for message removals
		if( !empty( $_COOKIE['msg_remove'] ) ){
			$removals = explode(',', $_COOKIE['msg_remove'] );
		} else { 
			$removals = array(); 
		}

		//loop through messages
		foreach( $pageMessages as $key => $msg ){

			//if in removals
			if( in_array( $key, $removals ) ){
				unset( $pageMessages[ $key ] );
			} else {

				$html .= '<div class="alert alert-' . $msg['type'] . ' alert-dismissible" role="alert">';
				$html .= '<button type="button" class="close" data-dismiss="alert"><span onClick="unsetPageMessage(\'' . $key . '\')" aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>';
				$html .= '<strong>' . $key . ':</strong> ';
				$html .= $msg['msg'];
				$html .= '</div>';
			}
		}
	}

	return $html;
}
/**
GET BREADCRUMBS
*/
function breadcrumbs( $class ){

	//check if cooie is set
	if( isset( $_COOKIE['breadcrumb_show'] ) && $_COOKIE['breadcrumb_show'] == 1 ){
		$open = ' open';
		$style = null;
	} else {
		$open = 'closed';
		$style = ' style="display: none;"';
	}

	//breadcrumbs container
	$html = '<div id="breadcrumbs-container" ' . $style . '>';

	$html .= '<p style="margin: 0;font-size: 12px;margin-left: 10px;color: #999;"><small><em>You Are:</em></small></p><ol class="breadcrumb">';

	//check if on the home page and add to the breadcrumb output
	if( $class->pageMeta[0]['name'] == 'Home' ){

		//active home link
		$html .= '	<li class="active"><a title="Return Home" href="' . _ROOT_ . '/home">Home</a></li>';

	} else {
		
		//add inactive home link
		$html .= '	<li><a title="Return Home" href="' . _ROOT_ . '/home">Home</a></li>';

		//last array key to indicate the active page
		$last_index = count($class->pageMeta ) - 1;
		$i = 0;

		//get the existing page meta information
		foreach( $class->pageMeta as $info ){	

			//check if last element
			if( $i == $last_index ){

				//active element without link
				$html .= '	<li class="active">' . $info['name'] . '</li>';
			} else {

				//inactive element as link
				$html .= '	<li><a title="Go to ' . $info['name'] . '" href="' . _ROOT_ . '' . $info['url'] . '">' . $info['name'] . '</a></li>';
			}

			//increase iteration counter
			$i++;
		}
	}

	//close breadcrumbs
	$html .= '</ol>';
	$html .= '</div>';

	$html .= '<div id="breadcrumb-toggle" class="' . $open . ' tooltips" data-toggle="tooltip" data-placement="bottom" title="Click here to show / hide your path helper for easy navigation."><span class="caret"></span></div>';

	return $html;
}
/**
BREADCRUMP SUPPLMENTS
*/
function breadcrumb_supplements($key, $first=true){

	//start return 
	$bcrumb_supp = '';
	//check for gets
	if( isset( $_GET['bcsupp' . $key ] ) ){

		//check if first iteration
		if( $first ){ $delim = '?'; } else { $delim = '&'; }

		//create supplemental string
		$bcrumb_supp .= $delim . $key . '=' . $_GET['bcsupp' . $key ];
	}

	return $bcrumb_supp;
}
/**
CREATE FORM INPUT FIELDS
*
* Input field default settings. Some of these values MUST be overwritten
*
* @var string 	option_name 				REQUIRED 			| calls the database option_name value
* @var string 	input_value					DEFAULT null 		| the display value for the field
* @var string 	input_type 					DEFAULT "text" 		| standard html input types
* @var string 	label 						DEFAULT null 		| adds label above the field
* @var string 	label_class					DEFAULT null 		| adds class to the label
* @var string 	placeholder 				DEFAULT null 		| acts as placeholder text
* @var string 	class 						DEFAULT null 		| list classes as they wil appear
* @var boolean 	disable 					DEFAULT false 		| whether or not the field is enabled
* @var boolean	required 					DEFAULT false 		| whether or not the field is required
* @var array 	option 						DEFAULT array() 	| options array for select types {value=>display,...}
* @var string 	check_value 				DEFAULT null 		| checks for selected value (checkboxes,radios,selects)
* @var string 	input_addon_start 			DEFAULT false 		| add bootstrap input addo to beginning
* @var string 	input_addon_start_type 		DEFAULT "default" 	| start addon color 
* @var string 	input_addon_end 			DEFAULT false 		| add bootstrap input addon to end
* @var string 	input_addon_end_type 		DEFAULT "default" 	| end addon color
* @var string 	input_button_start 			DEFAULT false 		| add bootstrap input button addon to beginning
* @var string 	input_button_start_type 	DEFAULT "default" 	| start button addon color
* @var string 	input_button_end 			DEFAULT false 		| add bootstrap input button addon to end
* @var string 	input_button_end_type 		DEFAULT "default" 	| end button addon color
* @var string 	help_title	 				DEFAULT null 		| if supplied renders as bubble title
* @var string 	help_text 					DEFAULT null 		| required to display help bubble
* @var array 	field_wrap 					DEFAULT (null,null)	| array([0]=>START,[1]=>END)
**/
function createFormInput( $option_name, $field_options ){

	//check for submitted vlues and add to the new input array in not set values are set as default values
	$newInput['label'] = ( isset( $field_options['label'] ) ? $field_options['label'] : false );
	$newInput['label_class'] = ( isset( $field_options['label_class'] ) ? $field_options['label_class'] : null );
	$newInput['input_type'] = ( isset( $field_options['input_type'] ) ? $field_options['input_type'] : 'text' );
	$newInput['placeholder'] = ( isset( $field_options['placeholder'] ) ? $field_options['placeholder'] : null );
	$newInput['class'] = ( isset( $field_options['class'] ) ? $field_options['class'] : null );
	$newInput['disabled'] = ( isset( $field_options['disabled'] ) && $field_options['disabled'] ? 'disabled' : false );
	$newInput['required'] = ( isset( $field_options['required'] ) && $field_options['required'] ? 'required' : false );
	$newInput['options'] = ( isset( $field_options['options'] ) ? $field_options['options'] : array() );
	$newInput['check_value'] = ( isset( $field_options['check_value'] ) ? $field_options['check_value'] : null );
	$newInput['input_addon_start'] = ( isset( $field_options['input_addon_start'] ) ? $field_options['input_addon_start'] : false );
	$newInput['input_addon_start_type'] = ( isset( $field_options['input_addon_start_type'] ) ? $field_options['input_addon_start_type'] : 'default' );
	$newInput['input_addon_end'] = ( isset( $field_options['input_addon_end'] ) ? $field_options['input_addon_end'] : false );
	$newInput['input_addon_end_type'] = ( isset( $field_options['input_addon_end_type'] ) ? $field_options['input_addon_end_type'] : 'default' );
	$newInput['input_button_start'] = ( isset( $field_options['input_button_start'] ) ? $field_options['input_button_start'] : false );
	$newInput['input_button_start_type'] = ( isset( $field_options['input_button_start_type'] ) ? $field_options['input_button_start_type'] : 'default' );
	$newInput['input_button_end'] = ( isset( $field_options['input_button_end'] ) ? $field_options['input_button_end'] : false );
	$newInput['input_button_end_type'] = ( isset( $field_options['input_button_end_type'] ) ? $field_options['input_button_end_type'] : 'default' );
	$newInput['help_title'] = ( isset( $field_options['help_title'] ) ? $field_options['help_title'] : 'What is this?' );
	$newInput['help_text'] = ( isset( $field_options['help_text'] ) ? $field_options['help_text'] : null );
	$newInput['min_value'] = ( isset( $field_options['min_value'] ) ? $field_options['min_value'] : null );
	$newInput['max_value'] = ( isset( $field_options['max_value'] ) ? $field_options['max_value'] : null );
	$newInput['pattern'] = ( isset( $field_options['pattern'] ) ? ' pattern="' . $field_options['pattern'] . '" ' : null );
	$newInput['allow_blank'] = ( isset( $field_options['allow_blank'] ) ? $field_options['allow_blank'] : false );
	$newInput['on_change'] = ( isset( $field_options['on_change'] ) ? ' onchange="' . $field_options['on_change'] . '" ' : null );
	$newInput['display_value'] = ( isset( $field_options['display_value'] ) ? $field_options['display_value'] : null );

	$field_wrapS = ( isset( $field_options['field_wrap'] ) ? $field_options['field_wrap'][0] : null );
	$field_wrapE = ( isset( $field_options['field_wrap'] ) ? $field_options['field_wrap'][1] : null );


	//check for existing database option and set value and ID
	$db_option = get_option_by_name( $option_name );
	$newInput['id'] = ( $db_option ? $db_option['id'] : null );

	//check for supplied field_value
	if( !empty( $field_options['input_value'] ) ){
		$newInput['option_value'] = $field_options['input_value'];
	} else {
		$newInput['option_value'] = ( $db_option ? $db_option['option_value'] : null );
	}	

	//check for help text
	$fieldHelp = null;
	if( isset( $newInput['help_text' ] ) ){

		//set the popover data
		$fieldHelp = '<span class="popover-toggle-help" data-toggle="popover" data-placement="auto" data-content="' . $newInput['help_text'] . '" data-trigger="focus click" title="' . $newInput['help_title'] . '">?</span>';

	} 

	//blank html retrun value
	$html ='';

	//check for label
	if( $newInput['label'] ){
		$html .= '<label for="settings-' . $option_name . '" class="control-label ' . $newInput['label_class'] . '">' . $newInput['label'];

		//check for wrap label option
		if( empty( $field_options['wrap_label'] ) || $field_options['wrap_label'] == false ){
			$html .= '</label>';
		}
	}

	//add field wrapping
	$html .=  $field_wrapS;

	//check for form addon
	if( $newInput['input_addon_start'] ){	
		$html .= '<span class="input-group-addon">' . $newInput['input_addon_start'] . '</span>';
	}

	//check for form buton addon
		if( $newInput['input_button_start'] ){	
			$html .= '<span class="input-group-btn">';
			$html .= '	<button class="btn btn-' . $newInput['input_button_start_type'] . '" type="button">' . $newInput['input_button_start'] . '</button>';
			$html .= '</span>';
		}

	//compile basic field
	if( in_array( $newInput['input_type'], array('text','checkbox','date','year','month','datetime','email','password','time','tel','color','number','time','submit','hidden','reset','button') ) ){ 		

		//add form control class
		if(empty( $field_options['no_form_control'] ) ){
			$newInput['class'] .= ' form-control';
		}

		//check for field min and max
		$max = null;
		$min = null;
		if( !empty( $newInput['max_value'] ) || $newInput['max_value'] == '0' ){
			$max = ' max="' . $newInput['max_value'] . '" ';
		}
		if( !empty( $newInput['min_value'] ) || $newInput['min_value'] == '0'  ){
			$min = ' min="' . $newInput['min_value'] . '" ';
		}

		$html .= '<input type="' . $newInput['input_type'] . '" id="settings-' . $option_name . '" name="settings-' . $option_name . '" class="' . $newInput['class'] . '" placeholder="' . $newInput['placeholder'] . '"' . $min . $max . $newInput['disabled'] . $newInput['required'] . $newInput['pattern'];

		//if checkbox check for checked attribute
		if( $newInput['input_type'] == 'checkbox' && $newInput['option_value'] !== null ){

			$html .= returnChecked( $newInput['option_value'], $newInput['check_value'] );
		} 

		//add value
		$html .= ' value="' . $newInput['option_value']  . '" ';

		$html .= $newInput['on_change'] . '>';
		if( $newInput['input_type'] == 'checkbox' ){

			//check for display value
			if( !empty( $newInput['display_value'] ) ){
				$html .= $newInput['display_value'] . '<br>';
			} else {
				$html .= $newInput['option_value']  . '<br>';
			}
		}

		$html .= '<!--END INPUT-->';

	} elseif( $newInput['input_type'] == 'select' ){

			//cehck if blanks are allowed
			if( !$newInput['allow_blank'] ){ $disabled_option = ' disabled'; } else { $disabled_option = null; }

			//create first option
			$html .= '<select id="settings-' . $option_name . '" name="settings-' . $option_name . '" class="form-control ' . $newInput['class'] . '"' . $newInput['disabled'] . $newInput['required'] . $newInput['on_change'] . '><option' . $disabled_option . '>' . $newInput['placeholder']  . '</option>';

			foreach($newInput['options'] as $value=>$display){
				$html .= '<option value="' . $value . '"' . returnSelected($newInput['check_value'],$value) . '>' . $display . '</option>';
			}
			$html .= '</select><!--END SELECT-->';

	} elseif( $newInput['input_type'] == 'textarea' ){


			//create first option
			$html .= '<textarea id="settings-' . $option_name . '" name="settings-' . $option_name . '" class="form-control ' . $newInput['class'] . '"' . $newInput['disabled'] . $newInput['required'] . $newInput['on_change'] . '>' . $newInput['check_value']  . '</textarea>';

			$html .= '<!--END TEXTAREA-->';
	}

	//check for form addon
	if( $newInput['input_addon_end'] ){	
		$html .= '<span class="input-group-addon">' . $newInput['input_addon_end'] . '</span>';
	}

	//check for form button addon	
	if( $newInput['input_button_end'] ){	
		$html .= '<span class="input-group-btn">';
		$html .= '	<button class="btn btn-' . $newInput['input_button_end_type'] . '" type="button">' . $newInput['input_button_end'] . '</button>';
		$html .= '</span>';
	}

	//check for wrap label option
	if( !empty( $field_options['wrap_label'] ) && $field_options['wrap_label'] == true ){
		$html .= '</label>';
	}

	//add field wrapping
	$html .= $field_wrapE;

	return $html;
}

/**
OUTPUT PROGRESS METER
 */
function progressMeter($progress=0,$type='%',$color=null,$dark=null,$title=null,$size='big',$tooltip=null){

	//start circular progress container
	$html = '<div class="progress-container">';
	$html .= '	<p data-placement="top" class="progress-title tooltips" title="' . $tooltip . '">' . $title . '</p>';
	$html .= '	<div data-placement="top" class="tooltips c100 p' . round($progress,0) . ' ' . $size . ' ' . $color . ' ' . $size . '" title="' . $tooltip . '">';
    $html .= '  	<span>' . round( $progress,1) . $type . '</span>';
    $html .= '		<div class="slice">';
	$html .= '			<div class="bar"></div>';
    $html .= '			<div class="fill"></div>';
	$html .= '		</div>';
	$html .= '	</div>';
	$html .= '</div>';

	return $html;
}

/**
GENERATE RANDOM STRING
*/
function generateRandomString($length=25){

	//return array
	$string = array();

	//aray of available characters that salt will be generated from
	$characterArray = array('1','2','3','4','5','6','7','8','9','10','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','!','@','#','$','%','^','&','(',')','_');

	//adding a single random character up to the specified length to the return array
	for($i = 0; $i < $length; $i++){
		
		//generating random number equal to a character in the array
		$character = rand(0,71);

		//adding that random character to the array
		array_push($string,$characterArray[$character]);
	}

	//setting string var to a string from array
	$string = implode('',$string);

	//returning the randomized salt
	return $string;
}

/**
CHECK THE STATUS OF A POSTED NONCE
*/
function checkNonce($name,$value='true'){
	if( !empty( $_REQUEST[ $name ] ) && $_REQUEST[ $name ] == $value ){
		return true;
	} else {
		return false;
	}
}

/**
RETURNS AN ARRAY OF STATES AND ABBREVS
*/
function get_states_array(){
	$states = array('AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California','CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','DC'=>'District of Columbia','FL'=>'Florida','GA'=>'Georgia','HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas','KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland','MA'=>'Massachusetts','MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri','MT'=>'Montana','NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico','NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma','OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina','SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont','VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming');

	return $states;
}
/**
CONVERT SECONDS TO READABLE STRING
 */
function seconds_to_string($secs){
    $units = array(
        'week'   => 7*24*3600,
        'day'    =>   24*3600,
        'hour'   =>      3600,
        'minute' =>        60,
        'second' =>         1,
    );

	// specifically handle zero
    if( $secs == 0 ) return "0 seconds";

    $s = "";
    foreach( $units as $name => $divisor ){
        if ( $quot = intval($secs / $divisor) ) {
                $s .= "$quot $name";
                $s .= (abs($quot) > 1 ? "s" : "") . ", ";
                $secs -= $quot * $divisor;
        }
    }

    return substr($s, 0, -2);
}

/**
CONTROLS WHETHER REDIRECTS WORK IN DEVELOPMENT MODE
 */
function submission_redirect($location, $type='admin'){

	//if in dev mode then output the submission
	if( check_dev_mode() ){
		var_dump( $_REQUEST );
	} else {

		//check type
		if( $type == 'admin' ){
			$base = _ROOT_;
		} else {
			$base = _PROTOCOL_ . _FRONTEND_URL_;
		}
		
		$location = str_replace($base, null, $location);
		header('Location:' . $base . $location );
	}
}

/**
CREATE A RETURN URL BASED ON URL GET VALUES
 */
function create_return_url( $location, $vars=array('y','m','s'), $include=array() ){
	//place holder to help with the ? vs &
	$first = true;

	//loop through page vars
	foreach( $vars as $var ){

		if( isset( $_GET[ $var ] ) ){
			//check if first
			if( $first ){ 
				$first = false;
				$location .= '?'; 				
			} else { 
				$location .= '&'; 
			}

			//set the new location URL
			$location .= $var . '=' . $_GET[ $var ];
		}
	}

	//check for force includes
	if( !empty( $include ) ){
		foreach ($include as $key => $value) {
			
			//check for first
			if( $first ){ 
				$first = false;
				$location .= '?'; 
			} else {
		 		$location .= '&';  
		 	}

			$location .= $key . '=' . $value;
		}
	}

	return $location;
}
/**
CREATES THE CLASSES FOR ADMIN AND FRONTEND FORM ELEMENTS
 */
function form_input_class_control( $type, $admin, $frontend ){
	if( $type == 'admin' ){
		return $admin;
	} else {
		return $frontend;
	}
}
/**
CHECKS FOR email_response IN URL
*/
function check_email_response(){
	if( isset( $_GET['email_response'] ) ){

		require_once( __FUNCTION_INCLUDE__ . 'volunteer_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');

		$response = base64_decode( $_GET['email_response'] );
		$response = json_decode( $response, true );

		//epty array for updates
		$updates = array();

		//check for volunteer updates
		if( isset( $response['vol_id'] ) ){

			//check for status update
			if( isset( $response['status'] ) ){
				$updates['status'] = $response['status'];
				add_page_message( 'success', 'You have successfully confirmed this position.','Confirmed');
			}

			//if there are updates run them
			if( !empty( $updates ) ){
				update_volunteer( $response['vol_id'], $updates  );
				$location = create_return_url( _PROTOCOL_ . _FRONTEND_URL_ );
				submission_redirect( $location );
			}			
		}		
	}
}

?>