<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-26 18:59:19
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-09-30 20:36:28
 */
/**

	OPTIONS

 */
/**
RETRIEVE AN OPTION FROM THE DATABASE
 */
function get_option_by_name($option_name){

	global $mysqli;

	//get the key
	$stmt = $mysqli->prepare('SELECT `id`,`option_name`,`option_value` FROM `options` WHERE `option_name` = ?');
	$stmt->bind_param('s', $option_name);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = false;
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return = array( 'id'=>$row['id'], 'option_name'=>$row['option_name'], 'option_value'=>$row['option_value'] );
	    }
	}

    return $return;
}
/**
RETRIEVE AN OPTION FROM THE DATABASE BY ID
 */
function get_option_by_id($id){

	global $mysqli;

	//get the key
	$stmt = $mysqli->prepare('SELECT `id`,`option_name`,`option_value` FROM `options` WHERE `id` = ?');
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = false;
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return = array( 'id'=>$row['id'], 'option_name'=>$row['option_name'], 'option_value'=>$row['option_value'] );
	    }
	}

    return $return;
}
/**
UPDATE / INSERT OPTION
 */
function updateOption($option_name,$option_value,$id=null){

	global $mysqli;

	//check for existing value option (update vs insert)
	$existing = get_option_by_name($option_name);
	if( $existing || !empty( $id ) ){
		$query = "UPDATE `options` SET `option_value` = '" . $option_value . "' WHERE `option_name` = '" . $option_name . "'";

		//check for ID
		if( !empty( $id ) ){
			$query .= ' AND `id` = ' . $id;
		}
	} else {
		$query = "INSERT INTO `options` (`option_name`,`option_value`) VALUES ('" . $option_name . "','" . $option_value . "')" ;
	}

	//run the query
	$stmt = $mysqli->query( $query );

	//get the new / edited option number

    return $stmt;
}

/**

	ATTRIBUTES

 */
/**
RETRIEVE ATTRIBUTE(S) FROM THE DATABASE
 */
function getAttributes(){

	global $mysqli;

	//fetch all attributes
	$stmt = $mysqli->prepare('SELECT `id`,`name`,`dom_id`,`type`,`default`,`placeholder`,`options_option_id`,`data_option_id`,`created`,`modified`,`required`, `profile_display` FROM `attributes` ORDER BY `name`');
	$stmt->execute();
	$result = $stmt->get_result();

	//empty array for return start
	$return = array();

	//if there are values then add then to return array
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return[] = array( 'id'=>$row['id'], 'name'=>$row['name'], 'dom_id'=>$row['dom_id'], 'type'=>$row['type'], 'default'=>$row['default'], 'placeholder'=>$row['placeholder'], 'options'=>$row['options_option_id'], 'data'=>$row['data_option_id'], 'created'=>$row['created'], 'required'=>$row['required'], 'profile_display'=>$row['profile_display'] );
	    }
	}

    return $return;
}
/**
RETRIEVE ATTRIBUTE FROM THE DATABASE BY ID
 */
function get_attribute_by_id( $id ){

	global $mysqli;

	//fetch all attributes
	$stmt = $mysqli->prepare('SELECT `id`,`name`,`dom_id`,`type`,`default`,`placeholder`,`options_option_id`,`data_option_id`,`created`,`modified`,`required`,`profile_display` FROM `attributes` WHERE `id` = ?');
	$stmt->bind_param('i',$id);
	$stmt->execute();
	$result = $stmt->get_result();

	//empty array for return start
	$return = array();

	//if there are values then add then to return array
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return = array( 'id'=>$row['id'], 'name'=>$row['name'], 'dom_id'=>$row['dom_id'], 'type'=>$row['type'], 'default'=>$row['default'], 'placeholder'=>$row['placeholder'], 'options'=>$row['options_option_id'], 'data'=>$row['data_option_id'], 'created'=>$row['created'], 'modified'=>$row['modified'], 'required'=>$row['required'], 'profile_display'=>$row['profile_display']  );
	    }
	}

    return $return;
}
/**
CREATE / MODIFY ATTRIBUTE
 */
function updateAttribute( $values=array() ){

	global $mysqli;

	$defaults = array(
		'id'=>null,
		'name'=>null,
		'dom_id'=>null,
		'type'=>null,
		'default'=>null,
		'placeholder'=>null,
		'required'=>false,
		'profile_display'=>false,
	);

	//replace spaces with underscores and lowercase the dom ID
	if( !empty( $values['dom_id'] ) ){
		$values['dom_id'] = stringToLowerUnderscores( $values['dom_id'] );
	}

	//set the submitted values
	foreach( $defaults as $field=>$value ){

		//check if the value was submitted
		if( isset( $values[ $field ] ) ){
			$defaults[ $field ] = $values[ $field ];
		} 
	}

	//check if an ID was provided (update)
	if( !empty( $defaults['id'] ) ){
		$stmt = $mysqli->prepare( "UPDATE `attributes` SET `name` = ?,`dom_id` = ?,`type` = ?,`default` = ?,`placeholder` = ?, `modified` = CURRENT_TIMESTAMP, `required` = ?, `profile_display` = ? WHERE `id` = ?" );
		$stmt->bind_param('sssssiid', $defaults['name'], $defaults['dom_id'], $defaults['type'], $defaults['default'], $defaults['placeholder'], $defaults['required'], $defaults['profile_display'], $defaults['id'] );
		$stmt->execute();
	} else {

		//add blank select options to the options table
		$mysqli->query('INSERT INTO `options` (`option_name`,`option_value`) VALUES (\'attr_options\',\'{}\')');
		$options_id = $mysqli->insert_id;

		//add blank data options to the options table
		$mysqli->query('INSERT INTO `options` (`option_name`,`option_value`) VALUES (\'attr_data\',\'{}\')');
		$data_id = $mysqli->insert_id;

		//insert the attribute
		$stmt = $mysqli->prepare( "INSERT INTO `attributes` (`name`, `dom_id`, `type`, `default`, `placeholder`, `options_option_id`, `data_option_id`,`required`, `profile_display`) VALUES (?,?,?,?,?,?,?,?,?)" );
		$stmt->bind_param('sssssiidd', $defaults['name'], $defaults['dom_id'], $defaults['type'], $defaults['default'], $defaults['placeholder'], $options_id, $data_id, $defaults['required'], $defaults['profile_display']);
		$stmt->execute();
	}

	if( $stmt ){
		return $mysqli->insert_id ;	
	} else {
		return false;
	}
    
}
/**
DELETE ATTRIBUTE
 */
function removeAtrribute( $attr_id ){

	global $mysqli;

	//get data and options ID
	$attribute = get_attribute_by_id( $attr_id );
	$option_id = $attribute['options'];
	$data_id = $attribute['data'];

	$mysqli->query( 'DELETE FROM `attributes` WHERE `id` = ' . $attr_id );

	//if there are options
	if( !empty( $option_id ) ){
		$mysqli->query( 'DELETE FROM `options` WHERE `id` = ' . $option_id );
	}

	//if there are data attributes
	if( !empty( $data_id ) ){
		$mysqli->query( 'DELETE FROM `options` WHERE `id` = ' . $data_id );
	}	
}

/**
GET FOR REQUIRED ATTTRIBUTES
*/
function get_required_attributes( $date=null, $profile_display=false ){

	global $mysqli;

	//if no start date set current time as the standard
	if( empty( $date ) ){
		$date = date('U');
	}

	//get a distinct list of ID's for any positions that hasn't occured
	$query = 'SELECT `id`,`name`,`dom_id`,`type`,`default`,`placeholder`,`options_option_id`,`data_option_id`,`created`,`modified`,`required`, `profile_display` FROM `attributes` WHERE `required` = 1';

	//check for profile view only indicator
	if( $profile_display ){
		$query .= ' AND `profile_display` = 1 ';
	}

	$stmt = $mysqli->prepare( $query );
	$stmt->execute();
	$result = $stmt->get_result();

	//required attributes array
	$req_attr = array();

	//if there are required attirbutes create array of them
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$req_attr[]= array( 'id'=>$row['id'], 'name'=>$row['name'], 'dom_id'=>$row['dom_id'], 'type'=>$row['type'], 'default'=>$row['default'], 'placeholder'=>$row['placeholder'], 'options'=>$row['options_option_id'], 'data'=>$row['data_option_id'], 'created'=>$row['created'], 'modified'=>$row['modified'], 'required'=>$row['required'], 'profile_display'=>$row['profile_display'] );
	    }

	    return $req_attr;
	} else {
		return false;
	}

}

/**
GET ATTIRBUTE VALUES FROM POSITIONS FOR NARROW BYS
 */
function getAttributeValuesForNarrow( $attr, $narrows ){

	global $mysqli, $attr_type_settings;

	//set blank return
	$return = array();

	//create the query from narrow selections for IN value
	$narrow_query = create_narrowed_query( $narrows, '`p`.`id`' );

	//start the query
	$query_base = "SELECT `p`.`id`,`p`.`name`,`p`.`event_id`,`p`.`date_start`,`p`.`date_end`,`p`.`created`,`p`.`created_by`,`m`.`id` AS `meta_id`,`m`.`meta_name`,`m`.`meta_value` FROM `positions` AS `p`	INNER JOIN `position_meta` AS `m` ON `m`.`position_id` = `p`.`id` WHERE `m`.`meta_name` = 'attr-" . $attr['id'] . "' AND `p`.`id` IN (" . $narrow_query . ")";

	$stmt = $mysqli->prepare( $query_base );
	$stmt->execute();
	$result = $stmt->get_result();

	//if there are required attirbutes create array of them
	if( !empty( $result ) ) {
		while( $row = $result->fetch_assoc() ) {
			//set the meta value var
			$meta_value = $row['meta_value'];

			//decode the value
			$meta_value = json_decode($meta_value, true);

			//set basic display valye
			$display_value = $meta_value['value'];

			//if multiple choice conver the value to the 
			if( $attr['type'] == 'multiple_choice' ){

				//get the options
				$options = get_option_by_id( $attr['options'] );
				$options = json_decode( $options['option_value'], true );
				if( !empty( $options ) && !empty( $meta_value['value'] ) ){
					$display_value = $options[ $meta_value['value'] ];
				} 
			} 

			//check for value and add helper content if so
			if( !empty( $meta_value['type'] ) ){
				$text_value = $attr_type_settings[ $meta_value['type'] ]['helper'] . ' ' . $display_value . ' ' . $attr_type_settings[ $meta_value['type'] ]['helper_end'];
			} else {
				$text_value = $display_value;
			}

			//add to array if not present
			if( empty( $return[ $meta_value['value'] ] ) && !empty( $meta_value['value'] ) ){
			
				$return[ $meta_value['value'] ] =  $text_value;
			}

	    }

	    return $return;

	   
	} else {
		return false;
	}

}

/**

	NOTIFICATIONS

 */
/**
INSERT / UPDATE NOTIFICATION
 */
function update_notification( $type, $content=null, $header=null, $summary=null, $rec_id='NULL', $noti_id=null){

	global $mysqli;

	//check for id
	if( empty( $noti_id ) ){
		$query = 'INSERT INTO `notifications` (`sender_id`,`recipient_id`,`type`,`content`,`header`,`summary`) VALUES (?,?,?,?,?,?)';
	} else {
		$query = 'UPDATE `notifications` SET `sender_id` = ?,`recipient_id` = ?,`type` = ?,`content` = ?,`header` = ?,`summary` = ? WHERE `id` = ' . $noti_id;
	}

	//get the key
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('iissss', $_COOKIE['usrID'], $rec_id, $type, $content, $header, $summary);
	$test = $stmt->execute();

	if( $test ){
		return true;
	} else {
		return false;
	}
}
/**
RETRIEVE NOTIFICATIONS
 */
function get_notifications_by_type($type){

	global $mysqli;

	//get the key
	$stmt = $mysqli->prepare('SELECT `id`,`sender_id`,`recipient_id`,`type`,`content`,`header`,`summary`,`created` FROM `notifications` WHERE `type` = ?');
	$stmt->bind_param('s', $type);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = array();
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return[] = array( 'id'=>$row['id'], 'sender_id'=>$row['sender_id'], 'recipient_id'=>$row['recipient_id'], 'type'=>$row['type'], 'content'=>$row['content'], 'header'=>$row['header'], 'summary'=>$row['summary'], 'created'=>$row['created'] );
	    }
	} 

    return $return;
}
/**
RETRIEVE NOTIFICATIONS BY RECIPIENT
 */
function get_notifications_by_recipient($r_id){

	global $mysqli;

	//get the key
	$stmt = $mysqli->prepare('SELECT `id`,`sender_id`,`recipient_id`,`type`,`content`,`header`,`summary`,`created` FROM `notifications` WHERE `recipient_id` = ?');
	$stmt->bind_param('i', $r_id);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = array();
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return[] = array( 'id'=>$row['id'], 'sender_id'=>$row['sender_id'], 'recipient_id'=>$row['recipient_id'], 'type'=>$row['type'], 'content'=>$row['content'], 'header'=>$row['header'], 'summary'=>$row['summary'], 'created'=>$row['created'] );
	    }
	} 

    return $return;
}
/**
CHECK FOR COOKIE REMOVAL COMMAND
 */
function remove_notifications(){

	//check for cookie
	if( !empty( $_COOKIE['notif-remv'] ) && $_COOKIE['notif-remv'] !== 0  && $_COOKIE['notif-remv'] !== '0' ){

		global $mysqli;

		//set variable
		$remove = $_COOKIE['notif-remv'];

		//explode ut the ids tha were clicked
		$remove = explode(';', $remove);

		//add them to an array
		foreach ($remove as $id) {
			if( $id !== 'undefined' && $id !== '0'){
				$not_ids[] = $id;
			}			
		}

		//get unique values
		$not_ids = array_unique($not_ids);

		//recombine them into SQL friendly
		$not_ids = implode(', ', $not_ids);

		//kill cookie
		setcookie('notif-remv',false,-1000000,'/');

		//update database
		$mysqli->query('DELETE FROM `notifications` WHERE `id` IN (' . $not_ids . ')');
	}
}

/**

	MESSAGES

 */
/**
ADD NEW MESSAGE TO BE SENT
*/
function add_new_message($notify_map,$from,$subject,$body,$send_after='CURRENT_TIMESTAMP',$type='message',$status='unsent',$delivery_method='both'){

	global $mysqli;

	//set query
	$stmt = $mysqli->prepare("INSERT INTO `messaging` (`notify_map`, `uid_from`, `subject`, `body`, `type`, `status`, `delivery_method`, `send_after`) VALUES (?,?,?,?,?,?,?,?)");
	$stmt->bind_param('iisssssi', $notify_map, $from, $subject, $body, $type, $status, $delivery_method, $send_after);
	$stmt->execute();

	//set the last id
	$id = $mysqli->insert_id;

	//if successful return id, else return false
	if( $id ){ return $id; } else {	var_dump( $mysqli->error );	die(); }
}

/**
GET THE EVENT NOTIFICATIONS
*/
function set_position_messages(){

}



?>