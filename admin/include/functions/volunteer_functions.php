<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-09-24 14:57:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-10 02:17:01
 */

function get_volunteers($order = 'user_id, position_id',$where=null, $limit=null){
	global $mysqli;

	$query = 'SELECT `id`, `user_id`, `position_id`, `status`, `signup`, `last_contact`,`created`,`modified` FROM `volunteers` ' . $where;
	if( !empty( $order ) ) {
		$query .= ' ORDER BY ' . $order;
	}

	if( !empty( $limit ) ){
		$query .= ' LIMIT ';
		if(is_array( $limit) ){
			$query .= $limit[0] . ',' . $limit[1];
		} else {
			$query .= '0,' . $limit;
		}
	}

	$return = array();
	$stmt = $mysqli->prepare( $query ); 
	$stmt->execute();
	$result = $stmt->get_result();
	
	while($row = $result->fetch_assoc()) {
		$return[] = array( 'volunteer_id'=>$row['id'], 'user_id'=>$row['user_id'], 'position_id'=>$row['position_id'], 'status'=>$row['status'], 'signup'=>$row['signup'], 'last_contact'=>$row['last_contact'] );
    }

    return $return;
}

function get_volunteer( $id ){
	global $mysqli;

	$return = array();
	$stmt = $mysqli->prepare('SELECT `id`, `user_id`, `position_id`, `status`, `signup`, `last_contact`,`created`,`modified` FROM `volunteers` WHERE `id` = ' . $id ); 
	$stmt->execute();
	$result = $stmt->get_result();
	
	while($row = $result->fetch_assoc()) {
		$return = array( 'volunteer_id'=>$row['id'], 'user_id'=>$row['user_id'], 'position_id'=>$row['position_id'], 'status'=>$row['status'], 'signup'=>$row['signup'], 'last_contact'=>$row['last_contact'] );
    }

    return $return;
}

function update_volunteer( $volunteer_id, $fields = array() ){
	global $mysqli;

	if( !empty( $fields ) ){

		//set the fields
		$set = array();
		foreach( $fields as $field=>$value ){

			//check for null values
			if( $value == 'NULL' || $value == null ){
				if( $field == 'user_id' ){
					$upd_val = '0'; 
				} else {
					$upd_val = 'NULL';
				}
			} else {
				$upd_val = '\'' . $value . '\'';
			}

			//add the set record
			$set[] = ' `' . $field . '` = ' . $upd_val;
		}

		//create set statement by imploding array
		$set = ' SET ' . implode(', ', $set);

		//create query
		$query = 'UPDATE `volunteers` ' . $set . ' WHERE `id` = ' . $volunteer_id;
		
		//run query
		$mysqli->query( $query );
		if( $mysqli->insert_id ){
			return $mysqli->insert_id; 
		} else {
			return false;
		}
	}

}

function add_blank_volunteer( $position_id , $overrides=array()){
	global $mysqli;

	//check for value overrides
	$user_id = ( isset( $overrides['user_id'] ) ? $overrides['user_id'] : '0' );
	$status = ( isset( $overrides['status'] ) ? $overrides['status'] : 'unfilled' );
	$signup = ( isset( $overrides['signup'] ) ? $overrides['signup'] : 'NULL' );
	$last_contact = ( isset( $overrides['last_contact'] ) ? $overrides['last_contact'] : 'NULL' );

	$mysqli->query( 'INSERT INTO `volunteers` (`user_id`,`position_id`,`status`,`signup`,`last_contact`) VALUES (' . $user_id . ',' . $position_id . ',\'' . $status . '\',' . $signup . ',' . $last_contact . ')' );
	
	$newID = $mysqli->insert_id;

    return $newID;
}

function deactivate_role( $volunteer_id ){
	global $mysqli;

	//check for volunteer
	$volunteer = get_volunteer_info_by_vol_id( $volunteer_id );
	
	if( !empty( $volunteer['u_id'] ) ){

		//send message notifying volunteer of deactivation
		require_once( __INCLUDE_PATH__ . 'class' . _DIRSEP_ . 'mail.class.php' );
		require_once( __INCLUDE_PATH__ . 'functions' . _DIRSEP_ . 'position_functions.php');
		$mail = new mail;
		$mail->set_headers();
		$mail->message = file_get_contents( __EMAILTEMPLATES__  . 'deactivated_position.html' );

		//get position name
		$position = get_position( $volunteer['position_id'] );

		//insert user values
		$replace = array(
			'USER'=>$volunteer['first_name'] . $volunteer['last_name'],
			'CONTACT_EMAIL'=>_CS_EMAIL_,
			'POS_INFO'=> $position['name'],
			);
		foreach ($replace as $key => $value) {

			//check if array
			if( is_array( $value ) ){
				$value = implode(' ',$value);
			}
			$mail->message = str_replace('{%' . $key . '%}', $value, $mail->message );
		}
		$mail->send_message();
		
	}

	$query = 'UPDATE `volunteers` SET `user_id` = 0, `signup` = NULL, last_contact = NULL, `status` = \'deactivated\', `modified` = CURRENT_TIMESTAMP WHERE `id` = ' . $volunteer_id;
	$mysqli->query( $query );
}

function activate_role( $volunteer_id ){
	global $mysqli;

	$query = 'UPDATE `volunteers` SET `status` = \'unfilled\', `modified` = CURRENT_TIMESTAMP WHERE `id` = ' . $volunteer_id;
	$mysqli->query( $query );
}

function get_volunteer_info($user_id){
	global $mysqli;

	$return = false;
	$stmt = $mysqli->prepare('SELECT `v`.`id` AS `v_id`,`u`.`id` AS `u_id`,`u`.`email`,`u`.`password`,`u`.`pwd_salt`,`u`.`first_name`,`u`.`last_name`,`u`.`user_type`,`u`.`created` FROM `volunteers` AS `v` RIGHT JOIN `users` AS `u` ON `u`.`id` = `v`.`user_id` WHERE `u`.`id` = ?');
	$stmt->bind_param('i', $user_id);
	$stmt->execute();
	$result = $stmt->get_result();
	
	while($row = $result->fetch_assoc()) {
		$return = array( 'v_id'=>$row['v_id'], 'u_id'=>$row['u_id'], 'email'=>$row['email'], 'password'=>$row['password'], 'pwd_salt'=>$row['pwd_salt'], 'first_name'=>$row['first_name'], 'last_name'=>$row['last_name'], 'user_type'=>$row['user_type'], 'created'=>$row['created'] );
    }

    return $return;
}


function get_volunteer_info_by_vol_id($vol_id){
	global $mysqli;

	$return = false;
	$stmt = $mysqli->prepare('SELECT `v`.`id` AS `v_id`,`u`.`id` AS `u_id`,`u`.`email`,`u`.`password`,`u`.`pwd_salt`,`u`.`first_name`,`u`.`last_name`,`u`.`user_type`,`u`.`created`, `v`.`position_id`, `v`.`status`, `v`.`last_contact`, `v`.`created` AS `volunteer_created`, `v`.`modified` FROM `volunteers` AS `v` LEFT JOIN `users` AS `u` ON `u`.`id` = `v`.`user_id` WHERE `v`.`id` = ?');
	$stmt->bind_param('i', $vol_id);
	$stmt->execute();
	$result = $stmt->get_result();
	
	while($row = $result->fetch_assoc()) {
		$return = array( 'volunteer_id'=>$row['v_id'], 'u_id'=>$row['u_id'], 'email'=>$row['email'], 'password'=>$row['password'], 'pwd_salt'=>$row['pwd_salt'], 'first_name'=>$row['first_name'], 'last_name'=>$row['last_name'], 'user_type'=>$row['user_type'], 'created'=>$row['created'], 'position_id'=>$row['position_id'], 'status'=>$row['status'], 'last_contact'=>$row['last_contact'], 'volunteer_created'=>$row['volunteer_created'], 'modified'=>$row['modified'] );
    }

    return $return;
}

function createNewVolunteerUser( $email, $first, $last, $password, $gender='unknown' ){

	global $mysqli;

	//check to see if user exists
	$existing = get_user_by_email( $email );
	if( !$existing ){

		//prepare password and salt
		$pwd_salt = generateRandomString();
		$password = md5( $password . $pwd_salt );

		//insert new user
		$stmt = $mysqli->query( "INSERT INTO `users` (`email`, `password`, `pwd_salt`, `first_name`, `last_name`, `user_type`) VALUES ('" . $email . "','" . $password . "','" . $pwd_salt . "','" . $first . "','" . $last . "','basic')" );

		$user_id = $mysqli->insert_id;

		//set volunteer status
		update_user_meta( $user_id, 'volunteer_status','unconfirmed');
		update_user_meta( $user_id, 'email_confirm','unconfirmed');
		update_user_meta( $user_id, 'confirm_date','');
		update_user_meta( $user_id, 'gender',$gender);
		update_user_meta( $user_id, 'profile_image','blank_' . $gender . '.jpg');
		update_user_meta( $user_id, 'email_pref','html');

	} else {

		$user_id = $existing['id'];

		//check for missing meta vals and add them if not present
		if( !get_user_meta( $existing['id'], 'volunteer_status' ) ){
			update_user_meta( $user_id, 'volunteer_status','unconfirmed');
		}
		if( !get_user_meta( $existing['id'], 'email_confirm' ) ){
			update_user_meta( $user_id, 'email_confirm','unconfirmed');
		}
		if( !get_user_meta( $existing['id'], 'confirm_date' ) ){
			update_user_meta( $user_id, 'confirm_date','');
		}
		if( !get_user_meta( $existing['id'], 'gender' ) ){
			update_user_meta( $user_id, 'gender', $gender);
		}
		if( !get_user_meta( $existing['id'], 'profile_image' ) ){
			update_user_meta( $user_id, 'profile_image','blank_' . $gender . '.jpg');
		}
		if( !get_user_meta( $existing['id'], 'email_pref' ) ){
			update_user_meta( $user_id, 'email_pref','html');
		}
	}

	//get the email status
	$email_status = get_user_meta( $existing['id'], 'email_confirm');
	if( !empty( $email_status ) ){ 

		//set the status
		$email_status = $email_status['meta_value'];

		//check if email confirmation has been completed and send confirmation email if not
		if( $email_status == 'unconfirmed' ){

			//send volunteer email confirm message
			require_once( __INCLUDE_PATH__ . 'class' . _DIRSEP_ . 'mail.class.php' );
			$email = new mail;	

			//set the to
			$email->to = $email;
			$email->subject = _DEFAULT_TITLE_ . ' - Email Confirmation';

			//get the html for the email
			$raw_message = file_get_contents( __EMAILTEMPLATES__ . 'volunteer_account_confirm.html');

			//set message vars
			$raw_message = str_replace('{%USER%}', $first . ' ' . $last, $raw_message );

			//set system name
			$raw_message = str_replace('{%SYSTEM_NAME%}', $email->subject, $raw_message );

			//set spam avoid email address
			$raw_message = str_replace('{%SENDEREMAIL%}', _CS_EMAIL_, $raw_message );

			//get the new password
			$raw_message = str_replace('{%USERNAME%}', $email, $raw_message );

			//set email class message
			$email->message = $raw_message;

			//set email headers  to html type
			$email->set_headers();

			//send message
			$success = $email->send_message();
		}
	}

	return $user_id;

}

//update a volunteer user db entry
function updateVolunteerUser( $id, $email, $first, $last, $password, $meta_array=array() ){

	global $mysqli;

	//check for password for special update settings
	$passUpdate = null;
	if( !empty( $password ) ){
		$passUpdate = ", `password` = MD5( CONCAT( '" . $password . "',`pwd_salt` ) )";
	}
	
	//update the DB
	$stmt = $mysqli->query( "UPDATE `users` SET `email` = '" . $email . "', `first_name` = '" . $first . "', `last_name` = '" . $last . "'" . $passUpdate . " WHERE `id` = " . $id );

	//update user meta values
	if( !empty( $meta_array ) ){
		foreach( $meta_array as $key=>$value ){
			update_user_meta( $id, $key, $value );
		}
	}	

	return $id;
}

function assign_user_to_volunteer($volunteer_id, $user_id){

	//get the position_id
	$position_id = get_volunteer( $volunteer_id );
	$position_id = $position_id['position_id'];

	//verify the user isn't already assigned a position
	$current_volunteers = get_volunteers('`user_id`',' WHERE `position_id` = ' . $position_id);
	$continue = true;
	foreach( $current_volunteers as $key=>$val ){
		if( $val['user_id'] == $user_id ){
			$continue = false;
		}
	}

	//get position meta early for lead information
	$position_meta = get_position_meta_for_id( $position_id );

	//set the lead information
	$lead = get_user( $position_meta['lead']['meta_value'] );
	$lead_string = '<a href="mailto:' . $lead['email'] . '" title="Send and email to the position lead: ' . $lead['first_name'] . ' ' . substr( $lead['last_name'], -1) . '">' . $lead['first_name'] . ' ' . substr( $lead['last_name'],0,1) .'</a>';


	//manual stop function for older server versions
	if( $continue ){

		//update volunteer table before sending message
		$fields = array(
			'user_id'=>$user_id,
			'modified'=>date('Y-m-d H:i:s'),
			'signup'=>date('Y-m-d H:i:s'),
			);
		update_volunteer( $volunteer_id, $fields );

		//send the confirmation message to the user
		require_once( __INCLUDE_PATH__ . 'class' . _DIRSEP_ . 'mail.class.php' );
		$email = new mail;

		//get the user information
		$user = get_user( $user_id );

		//get the position information
		$position = get_position( $position_id );

		//create the arrays of data for the code
		$deny_code = array('user_id'=>$user_id,'position_id'=>$position_id, 'status'=>'unfilled','vol_id'=>$volunteer_id);
		$confirm_code = array('user_id'=>$user_id,'position_id'=>$position_id, 'status'=>'confirmed','vol_id'=>$volunteer_id);

		//convert data array
		$deny_code = json_encode( $deny_code );
		$confirm_code = json_encode( $confirm_code );

		//convert to base 64
		$deny_code = base64_encode( $deny_code );
		$confirm_code = base64_encode( $confirm_code );

		//start info container
		$position_info ='<div style="Width:100%;padding:20px;">';

		//create the confirm button
		$position_info .= '<div><a style="background-color:#44c767;-moz-border-radius:8px;-webkit-border-radius:8px;border-radius:8px;border:2px solid #18ab29;display:inline-block;cursor:pointer;color:#ffffff;font-family:arial;font-size:23px;padding:11px 24px;text-decoration:none;text-shadow:1px 2px 0px #2f6627;" href="' . _ROOT_ . '?email_response=' . $confirm_code . '" title="Confirm this position">Confirm Position</a></div>';

		//create the deny button
		$position_info .='<div><a style="background-color:#e4685d;-moz-border-radius:8px;-webkit-border-radius:8px;border-radius:8px;border:2px solid #ffffff;display:inline-block;cursor:pointer;color:#ffffff;font-family:arial;font-size:23px;padding:11px 24px;text-decoration:none;text-shadow:1px 2px 0px #b23e35;" href="' . _ROOT_ . '?email_response=' . $deny_code . '" title="Deny this position">Deny Position</a></div>';

		//input the description
		if( empty( $position_meta['description']['meta_value'] ) ){
			$description = '<span style="color:#999;font-style:italic;font-size:11px;">There has not been a description written for this position.</span>';
		} else {
			$description = $position_meta['description']['meta_value'];
		}

		$position_info .= '<div style="border:1px solid #d9d9d9;padding: 15px 15px 15px 15px;"><h3>' . $position['name'] . ' Description</h3><p>' . str_replace( "\n\r" , '</p><p>', $description ) . '</p></div>';


		//set the to
		$email->to = $user['email'];
		$email->subject = 'Confirmation Email for ' . $position['name'] . ' (' . date('m/d/Y', $position['date_start']) . ')';

		//get the html for the email
		$raw_message = file_get_contents( __EMAILTEMPLATES__ . 'position_signup.html');

		//set message vars
		$raw_message = str_replace('{%USER%}', $user['first_name'] . ' ' . $user['last_name'], $raw_message );

		//set lead email contact name
		$raw_message = str_replace('{%CONTACT_EMAIL%}',$lead_string, $raw_message );

		//get the position information
		$raw_message = str_replace('{%POS_INFO%}', $position_info , $raw_message );

		//set email class message
		$email->message = $raw_message;

		//set email headers to html type
		$email->set_headers(_CS_EMAIL_, _CS_EMAIL_,null,null,'html');

		//send message
		$success = $email->send_message();

		//check if mail was sent successfully
		if( $success ){
			//update voluneteer table to reflect the sent message
			$fields = array(
				'modified'=>date('Y-m-d H:i:s'),
				'last_contact'=>date('Y-m-d H:i:s'),
				'status'=>'unconfirmed',
				);
			update_volunteer( $volunteer_id, $fields );

			add_page_message('success','You have been signed up for this position. Be sure to watch your email for the confirmation. If you do not receive the message check your spam / bulk mail folder and be sure to add ' . _CS_EMAIL_ . ' to your contact list.','Success!' );
		} else {
			//update voluneteer table to reflect the sent message
			$fields = array(
				'modified'=>date('Y-m-d H:i:s'),
				'last_contact'=>date('Y-m-d H:i:s'),
				'status'=>'email_error',
				);
			update_volunteer( $volunteer_id, $fields );

			add_page_message('warning','Sorry, it looks like there wasn an error whil trying to send your confirmation email. However you may have been added to the scheduled volunteers list for this position please contact the position lead for more information ' . $lead_string  . '.','Whoops!' );
		}

	} else {

		add_page_message('warning','Sorry, you have already been assigned a position for this opportunity. If you feel this may be in erro please contact the lead directly.' . $lead_string  . '.','Whoops!' );
	}
}

?>