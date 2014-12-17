<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-28 09:52:38
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-07 23:28:26
 */
/**
GETS ALL USERS
 */
function get_users($order = 'last_name, first_name',$where=' WHERE user_type != \'volunteer\''){
	global $mysqli;

	$return = false;
	$stmt = $mysqli->prepare('SELECT `id`, `email`, `first_name`, `last_name`, `user_type`, `created` FROM `users` ' . $where . ' ORDER BY ' . $order );
	$stmt->execute();
	$result = $stmt->get_result();
	
	while($row = $result->fetch_assoc()) {
		$return[] = array( 'id'=>$row['id'], 'email'=>$row['email'], 'first_name'=>$row['first_name'], 'last_name'=>$row['last_name'], 'user_type'=>$row['user_type'], 'created'=>$row['created'] );
    }

    return $return;
}
/**
GETS ALL USERS FOR A GIVEN TYPE
 */
function get_users_by_user_type($user_type, $order = 'last_name, first_name'){
	global $mysqli;

	$return = false;
	$stmt = $mysqli->prepare('SELECT `id`, `email`, `first_name`, `last_name`, `user_type`, `created` FROM `users` WHERE `user_type` = ? ORDER BY ' . $order );
	$stmt->bind_param('s', $user_type);
	$stmt->execute();
	$result = $stmt->get_result();
	
	while($row = $result->fetch_assoc()) {
		$return[] = array( 'id'=>$row['id'], 'email'=>$row['email'], 'first_name'=>$row['first_name'], 'last_name'=>$row['last_name'], 'user_type'=>$row['user_type'], 'created'=>$row['created'] );
    }

    return $return;
}
/**
GET USER INFORMATION BY ID
 */
function get_user( $user_id ){
	global $mysqli;

	//get the key
	$stmt = $mysqli->prepare('SELECT `id`,`email`,`password`,`pwd_salt`,`first_name`,`last_name`,`user_type`,`created` FROM `users` WHERE `id` = ?');
	$stmt->bind_param('i', $user_id);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = false;
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return = array( 
				'id'=>$row['id'], 
				'email'=>$row['email'], 
				'password'=>$row['password'], 
				'pwd_salt'=>$row['pwd_salt'],
				'first_name'=>$row['first_name'],
				'last_name'=>$row['last_name'],
				'user_type'=>$row['user_type'],
				'created'=>$row['created'],
			);
	    }
	} 

    return $return;
}
/**
GET USER INFORMATION BY EMAIL ADDRESS
 */
function get_user_by_email( $email ){
	global $mysqli;

	//get the key
	$stmt = $mysqli->prepare('SELECT `id`,`email`,`password`,`pwd_salt`,`first_name`,`last_name`,`user_type`,`created` FROM `users` WHERE `email` = ?');
	$stmt->bind_param('s', $email);
	$stmt->execute();
	$result = $stmt->get_result();

	$return = false;

	//if there is an option matching the value
	if( !empty( $result ) ) {

		while($row = $result->fetch_assoc()) {
			$return = array( 
				'id'=>$row['id'], 
				'email'=>$row['email'], 
				'password'=>$row['password'], 
				'pwd_salt'=>$row['pwd_salt'],
				'first_name'=>$row['first_name'],
				'last_name'=>$row['last_name'],
				'user_type'=>$row['user_type'],
				'created'=>$row['created'],
			);
	    }
	} else {
		$return = false;
	}

    return $return;
}
/**
GET A SPECIFIC USER META VALUE
 */
function get_user_meta($user_id, $meta_name){
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT `id`,`user_id`,`meta_name`,`meta_value` FROM  `user_meta` WHERE `user_id` = ? AND `meta_name` = ?");
	$stmt->bind_param('is', $user_id, $meta_name);
	$stmt->execute();
	$result = $stmt->get_result();
	
	//default return
	$return = false;
	while($row = $result->fetch_assoc()) {
		$return = array( 'id'=>$row['id'], 'user_id'=>$row['user_id'], 'meta_name'=>$row['meta_name'], 'meta_value'=>$row['meta_value'] );
    }

    return $return;
	
}
/**
UPDATE USER TABLE FIELD
 */
function update_user_field($user_id, $field, $value){
	global $mysqli;	

	//update the password
	$query = "UPDATE `users` SET `" . $field . "` = '" . $value . "' WHERE `id` = " . $user_id;
	$run = $mysqli->query( $query );
}
/**
CHANGE THE USER PASSWORD
 */
function update_user_pass($user_id,$password){
	global $mysqli;

	//update the password
	$query = "UPDATE `users` SET `password` = md5( CONCAT('" . $password . "',`pwd_salt`) ) WHERE `id` = " . $user_id;
	$run = $mysqli->query( $query );

	//remove the force password meta entry
	delete_meta(null, $user_id, 'force_password_reset');

}
/**
REMOVE THE USER META RECORD
 */
function delete_meta($meta_id=null,$user_id=null,$meta_name=null){
	global $mysqli;

	if( isset( $meta_id ) ){
		$query = 'DELETE FROM `user_meta` WHERE `id` = ' . $meta_id;
	} else {
		$query = 'DELETE FROM `user_meta` WHERE `user_id` = ' . $user_id . ' AND `meta_name` = \'' . $meta_name	. '\'';
	}

	if( !empty( $query ) ){
		$run = $mysqli->query( $query );
	}
}
/**
UPDATES A USER META VALUE
 */
function update_user_meta($user_id, $meta_name, $meta_value){
	global $mysqli;

	//check for existing value
	$result = get_user_meta( $user_id, $meta_name);

	//check for result
	if( $result ) {
		$id = $result['id'];
	    $query = "UPDATE `user_meta` SET `meta_value` = '" . $meta_value . "' WHERE `id` = " . $id;
	} else {
		$query = "INSERT INTO `user_meta` (`user_id`,`meta_name`,`meta_value`) VALUES ('" . $user_id . "','" . $meta_name . "','" . $meta_value . "')";
	}

	//run update
	$run = $mysqli->query( $query );
	if( $run ){
		return true;
	} else {
		return false;
	}
}
/**
GETS THE DISTINCT USER META FIELDS
 */
function get_user_meta_fields($include_perms=true){
	global $mysqli;

	//base query
	$query = "SELECT DISTINCT `meta_name` FROM  `user_meta`";
	
	//check for include perms
	if( !$include_perms ){
		$query .= " WHERE `meta_name` NOT LIKE 'perm%' ";
	}
	
	//get records
	$stmt = $mysqli->prepare( $query );	
	$stmt->execute();
	$result = $stmt->get_result();
	
	//default return
	$return = false;
	while($row = $result->fetch_assoc()) {
		$return[] = $row['meta_name'];
    }

    return $return;
}

/**
SET / GET USER META DEFAULT
**/
function user_meta_defaults(){

	//empty json array
	$empty_json = json_encode( array() );

	//defaults
	$defaults = array(
		  'profile_image'=>null,
		  'email_confirm'=>'unconfirmed',
		  'confirm_date'=>null,
		  'gender'=>null,
		  'email_pref'=>'html',
		  'attributes'=>$empty_json,
		);

	return $defaults;
}
/**
UPDATES THE DB USER TYPE
 */
function update_user_type($id, $new_type){
	global $mysqli;
	$query = $mysqli->query("UPDATE `users` SET `user_type` = '" . $new_type . "' WHERE `id` = " . $id);
}
/**
SETS THE USER PASSWORD TO THE RANDOM SALT VALUE
 */
function resetPassword( $user_id ){	
	global $mysqli;
	$stmt = $mysqli->query('UPDATE `users` SET `password` = md5( CONCAT( `pwd_salt`,`pwd_salt` ) ) WHERE `id` = ' . $user_id);	
}
/**
SENDS A RESET PASSWORD EMAIL TO USER
 */
function forgotPasswordEmail($email){
	//get the user info;
	$user = get_user_by_email( $email );

	//reset the password in the database
	resetPassword( $user['id'] );

	//update the meta value to trigger rest password
	update_user_meta( $user['id'], 'force_password_reset', 'true');

	//start mail
	require_once( __INCLUDE_PATH__ . 'class' . _DIRSEP_ . 'mail.class.php' );
	$email = new mail;		

	//set the to
	$email->to = $user['email'];
	$email->subject = _DEFAULT_TITLE_ . ' Password Recovery';

	//get the html for the email
	$raw_message = file_get_contents( __EMAILTEMPLATES__ . 'forgot_email.html');

	//set message vars
	$raw_message = str_replace('{%USER%}', $user['first_name'] . ' ' . $user['last_name'], $raw_message );

	//set system name
	$raw_message = str_replace('{%SYSTEM_NAME%}', $email->subject, $raw_message );

	//get the new password
	$raw_message = str_replace('{%NEWPASS%}', $user['pwd_salt'] , $raw_message );

	//set the link
	$raw_message = str_replace('{%ROOTURL%}', _ROOTURL_ , $raw_message );

	//set email class message
	$email->message = $raw_message;

	//set email headers  to html type
	$email->set_headers();

	//send message
	$success = $email->send_message();
}
/**
SENDS A MESSAGE INFORMING THE USER TEHIR ACCCOUNT HAS BEEN APPROVED
 */
function approveUserEmail( $id ){
	//get the user info;
	$user = get_user( $id );

	//start mail
	require_once( __INCLUDE_PATH__ . 'class' . _DIRSEP_ . 'mail.class.php' );
	$email = new mail;		

	//set the to
	$email->to = $user['email'];
	$email->subject = _DEFAULT_TITLE_ . ' Account Approval';

	//get the html for the email
	$raw_message = file_get_contents( __EMAILTEMPLATES__ . 'user_account_approval.html');

	//set message vars
	$raw_message = str_replace('{%USER%}', $user['first_name'] . ' ' . $user['last_name'], $raw_message );

	//set system name
	$raw_message = str_replace('{%SYSTEM_NAME%}', $email->subject, $raw_message );

	//set the username
	$raw_message = str_replace('{%USERNAME%}', $user['email'] , $raw_message );

	//set the link
	$raw_message = str_replace('{%ROOT%}', _ROOT_ , $raw_message );

	//set email class message
	$email->message = $raw_message;

	//set email headers  to html type
	$email->set_headers();

	//send message
	$success = $email->send_message();
}

/**
RETURNS THE AVAILABLE USER TYPES
 */
function get_user_types(){
	return array(
		'admin' => array('friendly'=>'Administrative Accounts','description'=>'Administrative users have full access to the system. These privileges should be given out with extreme discretion.','user_level'=>10),
		'super_user' => array('friendly'=>'Super User Accounts','description'=>'Super users are similar to Administrative users in that they have access to all of the system with the exception of user accounts. Super users are unable to view / change user account information.','user_level'=>9),
		'basic' => array('friendly'=>'Standard Users / Volunteers','description'=>'Basic users are given access to their account, position and event information. All other functions are disallowed. Likewise they are unable to update anything other than their own account information.','user_level'=>7),
		'guest' => array('friendly'=>'Guest Accounts','description'=>'Guest users are similar to basic users except they are unable to make changes to even their own account information. These accounts are good for shared users.','user_level'=>5),
		'demo' => array('friendly'=>'Demo Users','description'=>'Demo users are essentially the same as a super user with the exception of udpate privileges. These accounts should be given out to display the functionality of your system as they are able to view quite a bit of information but cannot make any changes.','user_level'=>3),
		'pending_approval' => array('friendly'=>'Users Pending Approval','description'=>'This account type is given to all persons who sign up for an account. An admin must change their account status from Pending Approval to another account type before they are granted access to the system. It should be noted that if a user has a Pending Approval account they will still be able to sign up for volunteer opportunities however will not be able to confirm any of their personal information afterwards.','user_level'=>1),
		'inactive' => array('friendly'=>'Inactive Users','description'=>'Inactive accounts should be used sparingly. Inactive accounts will not be able to log into their account to view any of their information.','user_level'=>1),
		'refused' => array('friendly'=>'Refused Accounts','description'=>'Refused users will not be able to sign in to their account or sign up for volunteer opportunities. This account type should be used to prevent access to a given account.','user_level'=>0),
	);
}
/**
RETURNS TRUE IF USER HAS ADEQUATE PERMISSIONS
*/
function check_user_level($requiredLevel=8, $user_id=null){

	//user id is supplied 
	if( !empty( $user_id ) ){
		//get user type for id
		$user = get_user( $user_id );
		$current_user_type = $user['user_type'];
	} else {

		//set the user type = current user
		$current_user_type  = $_COOKIE[ 'usrtyp' ];
	}

	//user levels
	$user_types = get_user_types();

	//check if usertype is at or above required level
	if( $user_types[ $current_user_type ]['user_level'] >= $requiredLevel ){ return true; } else { return false; }
}
/**
RETURNS THE NUMBER ASSOCIATED WITH THE USER ACCESS LEVEL
*/
function get_user_level( $user_id=null ){

	//user id is supplied 
	if( !empty( $user_id ) ){
		//get user type for id
		$user = get_user( $user_id );
		$user_type = $user['user_type'];
	} else {

		//set the user type = current user
		$user_type  = $_COOKIE[ 'usrtyp' ];
	}

	//get the available user types
	$avail_user_types = get_user_types();

	//check if user type exists
	if( !empty( $avail_user_types[ $user_type ] ) ){ 

		//return integer of permission level
		return $avail_user_types[ $user_type ]['user_level'];

	} else {

		//unable to locate position
		return false;
	}
}

/**
ALL USER PERMISSION INFORMATION
*/
function user_perms(){

	//core menu items 
	$perms[] = array('default_value'=>'false','id'=>'manage_menu', 'group'=>'Core Menu');
	$perms[] = array('default_value'=>'false','id'=>'admin_menu', 'group'=>'Core Menu');
	$perms[] = array('default_value'=>'true','id'=>'help', 'group'=>'Core Menu');

	//manage sub menu items
	$perms[] = array('default_value'=>'false','id'=>'manage_events_menu', 'group'=>'Sub Menu');
	$perms[] = array('default_value'=>'false','id'=>'manage_positions_menu', 'group'=>'Sub Menu');
	$perms[] = array('default_value'=>'false','id'=>'manage_volunteers_menu', 'group'=>'Sub Menu');

	//create sub menu items
	$perms[] = array('default_value'=>'false','id'=>'create_event_menu', 'group'=>'Sub Menu');
	$perms[] = array('default_value'=>'false','id'=>'create_position_menu', 'group'=>'Sub Menu');
	$perms[] = array('default_value'=>'false','id'=>'create_volunteer_menu', 'group'=>'Sub Menu');

	//admin sub menu items
	$perms[] = array('default_value'=>'false','id'=>'settings_menu', 'group'=>'Admin Settings');
	$perms[] = array('default_value'=>'false','id'=>'manage_users_menu', 'group'=>'Admin Settings');

	//function permissions
	$perms[] = array('default_value'=>'false','id'=>'reset_user_password', 'group'=>'Application Functions');
	$perms[] = array('default_value'=>'false','id'=>'change_user_type', 'group'=>'Application Functions');
	$perms[] = array('default_value'=>'false','id'=>'update_user_perms', 'group'=>'Application Functions');
	$perms[] = array('default_value'=>'false','id'=>'approve_user', 'group'=>'Application Functions');	
	$perms[] = array('default_value'=>'false','id'=>'system_status', 'group'=>'Application Functions');	
	$perms[] = array('default_value'=>'false','id'=>'system_metrics', 'group'=>'Application Functions');	

	return $perms;

}
/**
VERIFY THAT THE USER HAS ACCESS TO THE PERMISSION
*/
function verify_perm_access( $perm_id, $user_id=null ){

	global $mysqli;

	//default return value
	$return = false;

	//verify user id is set
	if( empty( $user_id ) ){
		$user_id = $_COOKIE['usrID'];
	} 

	//set the permission name value
	$perm_meta_name = 'perm_' . $perm_id;

	//get the user permission from the DB
	$stmt = $mysqli->prepare('SELECT `id`, `user_id`, `meta_name`, `meta_value` FROM `user_meta` WHERE `user_id` = ? AND `meta_name` = ?');
	$stmt->bind_param('is',$user_id,$perm_meta_name);
	$stmt->execute();
	$result = $stmt->get_result();

	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {

			//check if meta value is true
			if( $row['meta_value'] == true || $row['meta_value'] == 'true' || $row['meta_value'] == '1' || $row['meta_value'] == 1 ){
				$return = true;
			}
	    }
	} 

	return $return;
}
/**
GET USER PERMISSIONS FOR GIVEN USER
*/
function get_user_perms( $user_id = null ){
	//blank array for return
	$return = false;

	//set the user ID to current user
	if( empty( $user_id ) ){ $user_id = $_COOKIE['usrID']; }

	//get all user perms
	$all_perms = user_perms();

	foreach( $all_perms as $perm ){

		//set the perm meta name
		$perm_name = 'perm_' . $perm['id'];
		//check db for perm value
		$db_perm = get_user_meta( $user_id, $perm_name );

		//perm is present set return value
		if( $db_perm ){
			$return[] = array( 'id'=>$perm_name, 'group'=>$perm['group'],'value'=>$db_perm['meta_value'] );
		} else {

			//get user information
			$user = get_user( $user_id );

			//if admin / super user reset value
			if( $user['user_type'] == 'admin' || $user['user_type'] == 'super_user' ){

				//set following permissions to default to true
				$super_user_disallow = array('admin_menu','manage_events_menu','create_event_menu','settings_menu','manage_users_menu','reset_user_password','change_user_type','update_user_perms');

				//verify the permssion should be set  by checking disallow array
				if( $user['user_type'] == 'admin' || ( $user['user_type'] == 'super_user' && !in_array( $perm['id'], $super_user_disallow ) ) ){
					$perm['default_value'] = 'true';
				}
			}

			//if perm is not present set the db and the return value
			update_user_meta( $user_id, $perm_name, $perm['default_value'] );
			$return[] = array( 'id'=>$perm_name, 'group'=>$perm['group'],'value'=>$perm['default_value'] );
		}
	}

	return $return;
}

/**
INSERT USER PERMS
*/
function create_user_perms( $user_id ){
	//get user information
	$user = get_user( $user_id );

	//create the user permissions
	$perms = user_perms();
	foreach( $perms as $perm ){

		//array of items that super users should not be allowed to do
		$super_user_disallow = array('reset_user_password','manage_users_menu');

		//if admin / super user reset value
		if( $user['user_type'] == 'admin' || $user['user_type'] == 'super_user' ){

			//verify the permssion should be set  by checking disallow array
			if( $user['user_type'] == 'admin' || ( $user['user_type'] == 'super_user' && !in_array( $perm['id'], $super_user_disallow ) ) ){
				$perm['default_value'] = 'true';
			}
		}

		//add permission meta_value
		update_user_meta( $user_id, 'perm_' . $perm['id'], $perm['default_value'] );
	}
}

/**
CREATES STATRTING ATTRIBUTE SETS
*/
function create_user_attributes( $user_id, $overrides=array() ){
	//get defaults
	$attributes = user_meta_defaults();

	//check for overrides
	if( !empty( $overrides ) ){
		foreach( $overrides as $key=>$val ){
			$attributes[ $key ] = $val;
		}
	}

	//insert user attribute values
	foreach( $attribues as $key=>$val ){
		update_user_meta( $user_id, $key, $val );
	}
}

/**
VERIFIES THAT ALL REQUIRED ATTRIBUTES HAVE BEEN SUPPLIED
*/
function verify_required_attributes(){

	//check for missing requirements and set cookie
	require_once( __FUNCTION_INCLUDE__ . 'db_functions.php');
	$required_atts = get_required_attributes();

	//set var to determine if cookie should be set
	$missing = false;

	//if there are required attributes
	if( $required_atts ){

		//if there are required attributes verify the user has them inserted
		foreach( $required_atts as $attr ){

			//check the db for value
			$verify = get_user_meta( $_COOKIE['usrID'], $attr['dom_id'] );

			//if there is no value set or it is empty
			if( !$verify || empty( $verify['meta_value'] ) ){

				//set the missing variable
				$missing = true;

				//make sure cookie isn't already set
				if( empty( $_COOKIE['disable_signup'] ) ){

					//set cookie to prevent sign ups
					$expire = time()+60*60*24*30;
					setcookie('disable_signup','true',$expire,'/');

					//set page message to alert the user of the missing information
					add_page_message( 'danger', 'Looks like we\'re missing some information from your profile. You won\'t be able to sign up for any positions until this information has been provided. Please check out <a href="' . _PROTOCOL_ . _ROOTURL_ . '/my-profile" target="_blank" title="click to go to your profile page">your profile</a> for more information.','Confirmed');

					//redirect
					submission_redirect( $_SERVER['PHP_SELF'] );
				}
			}
		}
	}

	//check if there are any missing and if not then expire the cookie if it's set
	if( !$missing && !empty( $_COOKIE['disable_signup'] ) ){
		$expire = time() - 10000;
		setcookie('disable_signup','true',$expire,'/');
	}
}
?>