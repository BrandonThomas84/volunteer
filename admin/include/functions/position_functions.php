<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-09-14 22:22:40
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-11-11 10:53:26
 */
/**

	POSITION FUNCTIONS

 */
/**
GET POSITION BY ID
 */
function get_position($id){

	global $mysqli;

	//get the key
	$stmt = $mysqli->prepare('SELECT `id`,`name`,`event_id`,`date_start`,`date_end`,`created`,`created_by` FROM `positions` WHERE `id` = ?');
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = false;
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return = array( 'id'=>$row['id'], 'name'=>$row['name'], 'event_id'=>$row['event_id'], 'date_start'=>$row['date_start'], 'date_end'=>$row['date_end'], 'created'=>$row['created'], 'created_by'=>$row['created_by'] );
	    }
	}

    return $return;
}

/**
GET POSITIONS
 */
function get_positions(){

	global $mysqli;

	//get the key
	$stmt = $mysqli->prepare('SELECT `id`,`name`,`event_id`,`date_start`,`date_end`,`created`,`created_by` FROM `positions`');
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = false;
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return[ $row['id'] ] = array( 'id'=>$row['id'], 'name'=>$row['name'], 'event_id'=>$row['event_id'], 'date_start'=>$row['date_start'], 'date_end'=>$row['date_end'], 'created'=>$row['created'], 'created_by'=>$row['created_by'] );
	    }
	}

    return $return;
}
/**
REMOVE POSITION 
 */
function remove_position($position_id){

	global $mysqli;

	//get notify map ID
	$notify_map_id = get_position_meta_by_name( $position_id, 'notify_users');
	$notify_map_id = $notify_map_id['id'];

	//set queries for removal
	$msg_query = 'DELETE FROM `messaging` WHERE `notify_map` = ' . $notify_map_id;
	$mta_query = 'DELETE FROM `position_meta` WHERE `position_id` = ' . $position_id;
	$pos_query = 'DELETE FROM `positionS` WHERE `id` = ' . $position_id;

	//run queries
	$mysqli->query( $msg_query );
	$messages = $mysqli->affected_rows;
	$mysqli->query( $mta_query );
	$meta = $mysqli->affected_rows;
	$mysqli->query( $pos_query );
	$pos = $mysqli->affected_rows;

	return array('msgs'=>$messages,'meta'=>$meta,'pos'=>$pos);
}
/**
GET COUNT OF ALL ROLES 
 */
function get_all_role_count(){
	//get positions
	$positions = get_positions();
	
	//start counter as number of positions
	$roles = 0;

	if( $positions ){
		foreach( $positions as $position ){

			//get number of roles for each positions
			$pos_role = get_position_meta_by_name( $position['id'], 'roles');
			if( $pos_role ){
				//add roles to total
				$roles += (int) $pos_role['meta_value'];
			} 
		}
	}

	return $roles;
}

/**
GET NUMBER OF ROLES FOR A GIVEN POSITION
 */
function get_position_roles( $position_id ){

	//get the role meta
	$pos_role = get_position_meta_by_name( $position_id, 'roles');
	if( $pos_role ){ $roles = $pos_role['meta_value']; } else { $roles = 0; }
	return $roles;
}

/**
GET ROLE INFO FOR GIVEN POSITION
 */
function get_position_role_info( $position_id ){

	//get the role meta
	$pos_role = get_position_meta_by_name( $position_id, 'role_info');
	if( $pos_role ){ $role_info = $pos_role['meta_value']; } else { $role_info = false; }
	return $role_info;
}

/**
GET THE NUMBER OF ROLES FOR A GIVEN STATUS
*/
function get_position_role_status( $position_id, $status=null){
	global $mysqli;

	$query = 'SELECT DISTINCT `id` FROM `volunteers` WHERE `position_id` = ' . $position_id;

	if( !empty( $status ) ){
		$query .= ' AND `status` = \'' . $status . '\'';
	}

	$result = $mysqli->query( $query );
	$count = $result->num_rows;	

	return $count;
}

/**
SET POSITION META DEFAULTS
 */
function set_position_meta_defaults(){
	//set default meta values
	$meta_defaults = array(
		'lead' => $_COOKIE['usrID'],
		'status' => 'inactive',
		'location_address1' => '',
		'location_address2' => '',
		'location_city' => '',
		'location_state' => '',
		'location_zip' => '',
		'require_check_in' => 'false',
		'notifications' => json_encode( default_notifications() ),
		'notify_users' => json_encode( array( $_COOKIE['usrID'] ) ),
		'notify' => 'true',
		'custom_notifications'=> json_encode( array() ),
		'attribute_settings' => json_encode( array() ),
		'description' => '',
		'roles' => '1',
		'role_info'=>json_encode( array('1'=>array('status'=>'unfilled','volunteer_id'=>'','created_date'=>date('U'), 'modified_date'=>date('U') ) ) ),
		'require_approval'=>'false',
	);

	return $meta_defaults;
}

/**
SET DEFAULT NOTIFICATIONS ARRAY
*/
function default_notifications(){

	//set the signup
	$notifications['sign_up'] = array(
		'active'=>'true',
		'date'=>date('U'),
		'msg_type'=>'template',
		'template_name'=>'position_signup',
	);

	//set the onupdate
	$notifications['on_change'] = array(
		'active'=>'true',
		'date'=>date('U'),
		'msg_type'=>'template',
		'template_name'=>'position_update',
	);

	//set the onupdate
	$notifications['on_confirm'] = array(
		'active'=>'false',
		'date'=>date('U'),
		'msg_type'=>'template',
		'template_name'=>'volunteer_confirm',
	);

	//set the onupdate
	$notifications['on_start'] = array(
		'active'=>'false',
		'date'=>date('U'),
		'msg_type'=>'template',
		'template_name'=>'position_start',
	);

	return $notifications;
}

/**
CREATE NEW POSITION
 */
function create_position( $name, $event_id, $start=null, $end=null, $meta_override=array() ){

	global $mysqli;

	//create the new position
	$stmt = $mysqli->prepare('INSERT INTO `positions` ( `name`,`event_id`,`date_start`,`date_end`,`created_by` ) VALUES (?,?,?,?,?)');
	$stmt->bind_param('sissi', $name, $event_id, $start, $end, $_COOKIE['usrID'] );
	$stmt->execute();
	$result = $stmt->get_result();

	//set the position ID
	$position_id = $mysqli->insert_id;

	//set the default meta fields
	$meta_defaults = set_position_meta_defaults();

	//check for meta value updates
	foreach( $meta_override as $key=>$value ){
		$meta_defaults[ $key ] = $value;
	}

	//create meta values in db
	foreach( $meta_defaults as $name=>$value ){
		update_position_meta($position_id,$name,$value);
	}

	//get the id for the notifications map for messaging
	$notify_map = get_position_meta_by_name($position_id, 'notify_users');
	$notify_map = $notify_map['id'];

	//create position start message (0 from system)
	$content = file_get_contents( __EMAILTEMPLATES__ . 'position_start.html');
	add_new_message( $notify_map, '0', 'Volunteer Position ' . $name . ' Sign Up', $content, $start );
	

	return $position_id;
}
/**
	GET POSITION META FOR GIVEN POSITION ID
 */
function get_position_meta_for_id($position_id){
	global $mysqli;

	//create the new position
	$stmt = $mysqli->prepare('SELECT `id`,`position_id`,`meta_name`,`meta_value` FROM `position_meta` WHERE `position_id` = ?');
	$stmt->bind_param('i', $position_id);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = false;
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return[ $row['meta_name'] ] = array( 'id'=>$row['id'], 'position_id'=>$row['position_id'], 'meta_name'=>$row['meta_name'], 'meta_value'=>$row['meta_value'] );
	    }
	}

    return $return;

}
/**
	GET POSITION META BY META NAME AND POSTION
 */
function get_position_meta_by_name($position_id, $meta_name){
	global $mysqli;

	//create the new position
	$stmt = $mysqli->prepare('SELECT `id`,`position_id`,`meta_name`,`meta_value` FROM `position_meta` WHERE `position_id` = ? AND `meta_name` = ?');
	$stmt->bind_param('is', $position_id, $meta_name);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = false;
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return = array( 'id'=>$row['id'], 'position_id'=>$row['position_id'], 'meta_name'=>$row['meta_name'], 'meta_value'=>$row['meta_value'] );
	    }
	}

    return $return;

}
/**
	GET POSITION META BY META ID
 */
function get_position_meta_by_id($id){
	global $mysqli;

	//create the new position
	$stmt = $mysqli->prepare('SELECT `id`,`position_id`,`meta_name`,`meta_value` FROM `position_meta` WHERE `id` = ?');
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$result = $stmt->get_result();

	//if there is an option matching the value
	$return = false;
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return = array( 'id'=>$row['id'], 'position_id'=>$row['position_id'], 'meta_name'=>$row['meta_name'], 'meta_value'=>$row['meta_value'] );
	    }
	}

    return $return;
}
/**
	UPDATE POSITION
 */
function update_position($position_id, $name=null, $event_id=null, $date_start=null, $date_end=null ){

	//check if row already exists
	$existing = get_position( $position_id );
	if( $existing ){
		//blank array for the set statement
		$set = array();

		//set the vars
		if( !empty( $name ) ){ $set[] =' `name` = \'' . $name . '\''; }
		if( !empty( $event_id ) ){ $set[] =' `event_id` = \'' . $event_id . '\''; }
		if( !empty( $date_start ) ){ $set[] =' `date_start` = \'' . $date_start . '\''; }
		if( !empty( $date_end ) ){ $set[] =' `date_end` = \'' . $date_end . '\''; }

		if( !empty( $set ) ){
			global $mysqli;
			$set = implode(', ', $set);
			$query = "UPDATE `positions` SET " . $set . " WHERE `id` = " . $existing['id'];	
			$mysqli->query( $query );
		}
	} 	
}
/**
	UPDATE POSITION META
 */
function update_position_meta($position_id,$meta_name,$meta_value ){
	global $mysqli;

	//check if row already exists
	$existing = get_position_meta_by_name( $position_id, $meta_name );
	if( $existing ){
		$query = "UPDATE `position_meta` SET `meta_value` = '" . $meta_value . "'	WHERE `id` = " . $existing['id'];
	} else {
		$query = "INSERT INTO `position_meta` (`position_id`,`meta_name`,`meta_value`) VALUES (" . $position_id . ",'" . $meta_name . "','" . $meta_value . "')";
	}

	$mysqli->query( $query );
	
}
/**
	GET POSITION BY EVENT (FOR EVENT LISTING)
 */
function get_position_by_event($where=null,$orderBy='event_name',$orderDir='asc'){
	global $mysqli;

	//create the new position
	$result = $mysqli->query('SELECT `p`.`id`, `p`.`name`, `p`.`date_start`, `p`.`date_end`, `p`.`created`, `p`.`created_by`, `p`.`event_id`, `e`.`name` as `event_name`, `e`.`status` as `event_status`, `e`.`slug` as `event_slug` FROM `positions` as `p` INNER JOIN `events` AS `e` ON `e`.`id` = `p`.`event_id` ' . $where . ' ORDER BY `' . $orderBy . '` ' . $orderDir);

	//default return value
	$return = false;

	//if there are values
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			$return[] = array( 'id'=>$row['id'], 'name'=>$row['name'], 'date_start'=>$row['date_start'], 'date_end'=>$row['date_end'], 'created'=>$row['created'], 'created_by'=>$row['created_by'], 'event_id'=>$row['event_id'], 'event_name'=>$row['event_name'], 'event_status'=>$row['event_status'], 'event_slug'=>$row['event_slug'] );
	    }
	}

    return $return;
}
/**
	GET POSITIONS FROM NARROW VALUES
 */
function get_narrowed_positions( $query ){

	global $mysqli;

	$result = $mysqli->query( $query );
	//default return value
	$return = false;

	//if there are values
	if( !empty( $result ) ) {
		while($row = $result->fetch_assoc()) {
			foreach( $row as $key=>$value ){
				$record_row[ $key ] = $value;
			}
			$return[] = $record_row;
	    }
	}

    return $return;
}

/**
SET DEFAULT NARROW BY OPTIONS
 */
function get_position_narrow_defaults(){
	return array('status'=>null,'search_date_start'=>null,'search_time_start'=>null,'search_date_end'=>null,'search_time_end'=>null,'created_date_start'=>null,'created_time_start'=>null,'created_date_end'=>null,'created_time_end'=>null,'event_id'=>null,'name'=>null,'lead'=>null,'location'=>null,'description'=>null,'show_inactive'=>'1','show_inactive_events'=>'1','show_events_no_position'=>'1');
}

/**
CREATE QUERY BASED ON NARROWS
 */
function create_narrowed_query( $narrows ){

	//set blank narows if there are any
	$narrow_defaults = get_position_narrow_defaults();
	foreach( $narrow_defaults as $key=>$val ){
		if( empty( $narrows[ $key ] ) ){
			$narrows[ $key ] = $val;
		}
	}

	//start the select
	$select_base = "SELECT DISTINCT `p`.`id`, `p`.`name`, `p`.`event_id`, `p`.`date_start`, `p`.`date_end`, `p`.`created`, `p`.`created_by`";

	//start the from
	$from_base = " FROM `positions` AS `p` ";

	//start where
	$where_base = null;

	//array of query items
	$query_supps = array(
			'frontend' => array('select'=>'`v`.`user_id`,`v`.`position_id`,`v`.`status`,`v`.`signup`,`v`.`last_contact`','from'=>'INNER JOIN  `volunteers` AS `v` ON `v`.`position_id` = `p`.`id` AND `v`.`user_id` = 0','where'=>''),
			'status' => array('select'=>'`status`.`meta_value` AS `status`','from'=>'LEFT OUTER JOIN  `position_meta` AS `status` ON `status`.`meta_name` = \'status\' AND `status`.`position_id` = `p`.`id`','where'=>'`status`.`meta_value` = \'' . $narrows['status'] . '\''),
			'search_date_start' => array('select'=>'','from'=>'','where'=>'`p`.`date_start` > \'' . date('U', strtotime($narrows['search_date_start'] . $narrows['search_time_start'] ) ) . '\''),
			'search_date_end' => array('select'=>'','from'=>'','where'=>'`p`.`date_end` < \'' . date('U', strtotime($narrows['search_date_end'] . $narrows['search_time_end']  ) ) . '\''),
			'created_low' => array('select'=>'','from'=>'','where'=>'`p`.`created` > \'' . date('Y-m-d H:i:s', strtotime( $narrows['created_date_start'] . $narrows['created_time_start'] ) ) . '\''),
			'created_hi' => array('select'=>'','from'=>'','where'=>'`p`.`created` < \'' . date('Y-m-d H:i:s', strtotime( $narrows['created_date_end'] . $narrows['created_time_end'] ) ) . '\''),
			'event_id' => array('select'=>'','from'=>'','where'=>'`p`.`event_id` = \'' . $narrows['event_id'] . '\''),
			'name' => array('select'=>'','from'=>'','where'=>'`p`.`name` LIKE \'%' . $narrows['name'] . '%\''),
			'lead' => array('select'=>'`lead`.`meta_value` AS `lead`','from'=>'LEFT OUTER JOIN  `position_meta` AS `lead` ON `lead`.`meta_name` = \'lead\' AND `lead`.`position_id` = `p`.`id`','where'=>'`lead`.`meta_value` = \'' . $narrows['lead'] . '\''),
			'description' => array('select'=>'`desc`.`meta_value` AS `desc`','from'=>'','where'=>'`desc`.`meta_value	` LIKE \'%' . $narrows['description'] . '%\''),
			'location'=> array('select'=>'`addr1`.`meta_value` AS `addr1`,`addr2`.`meta_value` AS `addr2`,`city`.`meta_value` AS `city`,`state`.`meta_value` AS `state`,`zip`.`meta_value` AS `zip`','from'=>'LEFT OUTER JOIN  `position_meta` AS `lead` ON `lead`.`meta_name` = \'lead\' AND `lead`.`position_id` = `p`.`id` LEFT OUTER JOIN  `position_meta` AS `addr1` ON `addr1`.`meta_name` = \'location_address1\' AND `addr1`.`position_id` = `p`.`id` LEFT OUTER JOIN  `position_meta` AS `addr2` ON `addr2`.`meta_name` = \'location_address2\' AND `addr2`.`position_id` = `p`.`id` LEFT OUTER JOIN  `position_meta` AS `city` ON `city`.`meta_name` = \'location_city\' AND `city`.`position_id` = `p`.`id` LEFT OUTER JOIN  `position_meta` AS `state` ON `state`.`meta_name` = \'location_state\' AND `state`.`position_id` = `p`.`id` LEFT OUTER JOIN  `position_meta` AS `zip` ON `zip`.`meta_name` = \'location_zip\' AND `zip`.`position_id` = `p`.`id` ','where'=>'`addr1`.`meta_value` LIKE \'%' . $narrows['location'] . '%\' OR `addr2`.`meta_value` LIKE \'%' . $narrows['location'] . '%\' OR `city`.`meta_value` LIKE \'%' . $narrows['location'] . '%\' OR `state`.`meta_value` LIKE \'%' . $narrows['location'] . '%\' OR `zip`.`meta_value` LIKE \'%' . $narrows['location'] . '%\' '),
		);

	//construct query
	foreach( $narrows as $key=>$value ){
		if( !empty( $value ) ){

			//add select fields
			if( !empty( $query_supps[ $key ]['select'] ) ){
				$select_base .= ', ' . $query_supps[ $key ]['select'];
			}

			//add from tables
			if( !empty( $query_supps[ $key ]['from'] ) ){
				$from_base	.= ' ' . $query_supps[ $key ]['from'];
			}

			//add where statements
			if( !empty( $query_supps[ $key ]['where'] ) ){
				if( empty( $where_base ) ){
					$where_base = ' WHERE ';
				} else {
					$where_base .= ' AND ';
				}
				$where_base	.= $query_supps[ $key ]['where'];
			}
		}
	}

	$query = $select_base . $from_base . $where_base;

	return $query;
}

/**
CREATES THE CONTENT FOR THE MODAL FOR EACH POSITION
 */
function create_position_modal( $position_id ){

	//get the position information
	$position = get_position( $position_id );
	$pos_meta = get_position_meta_for_id( $position_id );
	

	//set base vars
	$status = $pos_meta['status']['meta_value'];
	$address['addr1'] = $pos_meta['location_address1']['meta_value'];
	$address['addr2'] = $pos_meta['location_address2']['meta_value'];
	$address['city'] = $pos_meta['location_city']['meta_value'];
	$address['state'] = $pos_meta['location_state']['meta_value'];
	$address['zip'] = $pos_meta['location_zip']['meta_value'];

	//get description
	$description = $pos_meta['description']['meta_value'];
	if( empty( $description ) ){
		$description = '<p class="help-block">Sorry, a description for this position has not been written.</p>';
	}

	//set the lead name
	$lead['name'] = get_user( $pos_meta['lead']['meta_value'] );
	$lead['name'] = $lead['name']['first_name'] . ' ' . substr( $lead['name']['last_name'], 0, 1 );

	//set lead image
	$lead['image'] = get_user_meta( $pos_meta['lead']['meta_value'], 'profile_image');
	if( $lead['image'] ){
		$lead['image'] = $lead['image']['meta_value'];	
	} else {
		$lead['image'] = 'blank_unknown.jpg';
	}
	


	//start the conatainer
	$sub_modal = '<div id="position-detail-' . $position_id . '" data-position-id="' . $position_id . '" class="fe-position-detail">';

	//add lead image
	$sub_modal .= '<div class="col-xs-12 col-md-4 text-center pos-lead-outer">';
	$sub_modal .= '	<img class="position-lead-profile-img" alt="You will be checking in with ' . $lead['name'] . '" src="' . _PROTOCOL_ . _ROOTURL_ . '/profile-images/' . $lead['image'] . '">';
	$sub_modal .= '	<p class="h5">Position Lead: <strong>' . $lead['name'] . '</strong></p>';
	$sub_modal .= '	<p class="help-block"><small>Any questions or comments should be directed to the Position Lead. This will also be who you check in with when you arrive to fulfill your role.</small></p>';
	$sub_modal .= '</div>';

	//position info
	$sub_modal .= '<div class="col-xs-12 col-md-8 pos-info-outer">';

	//basic info
	$sub_modal .= '<div class="col-xs-12 basic-position-info">';
	$sub_modal .= '	<h2 class="h4 position-modal-name">' . $position['name'] . '</h2>';
	$sub_modal .= '	<p class="h4">Basic Information</p>';
	$sub_modal .= '	<div class="col-xs-12">';
	$sub_modal .= '		<p><strong>Start:</strong> <br>' . date('l F jS, Y h:i a', $position['date_start'] ) . '</p>';
	$sub_modal .= '		<p><strong>End:</strong> <br>' . date('l F jS, Y h:i a', $position['date_end'] ) . '</p>';

	//check if there is an address line 2 value
	$address = $pos_meta['location_address1']['meta_value'];
	if( !empty( $pos_meta['location_address2']['meta_value'] ) ){
		$address .= ', ' . $pos_meta['location_address2']['meta_value'];
	}
	$sub_modal .= '		<p><strong>Location</strong></p>';
	$sub_modal .= '		<address>' . $address . '<br>' . $pos_meta['location_city']['meta_value'] . ', ' . $pos_meta['location_state']['meta_value'] . ' ' . $pos_meta['location_zip']['meta_value'];
	$sub_modal .= '		</address>';

	//available roles
	$total = get_position_role_status( $position_id );
	$available = get_position_role_status( $position_id, 'unfilled');
	if( $available > 0 ){
		$average = round( $available / $total, 0 );	
	} else {
		$average = 0;
	}
	
	if( $average < .3 ){
		if( $available < 1 ){
			$flag = 'danger';
		} else {
			$flag = 'warning';
		}
	
	} else {
		$flag = 'success';
	}
	


	$sub_modal .= '		<p><strong>Available Positions:</strong> <span class="h3 label label-' . $flag . '">' . $available . ' / ' . $total . '</span></p>';
	$sub_modal .= '	</div>';
	$sub_modal .= '</div>';


	//position description
	$description = str_replace( "\n\r" , '</p><p>', $description );	
	$sub_modal .= '	<div class="col-xs-12 description-container">';
	$sub_modal .= '		<p class="h4">Position Description</p>';
	$sub_modal .= '		<div class="pos-description-inner"><p>' . $description . '</p></div>';
	$sub_modal .= '	</div>';

	//close info container
	$sub_modal .= '</div>';


	//close modal container
	$sub_modal .= '<div class="clearfix"></div>';
	$sub_modal .= '</div>';

	return $sub_modal;
}
/**
CREATES THE OPTIONS FOR THE POSITION NARROWS
*/
function positionControls($init_type,$view_type,$position_narrows,$enabled_narrows=0,$class=null,$style=null){

	//disable on calendar view
	$disabled_class = array(null,null);

	//active elements 
	$active_narrow = '<span class="label label-default admin-pos-enabled-narrows ' . $disabled_class[0] . '">Enabled Options: ' . $enabled_narrows . '</span>';

	//start html output
	$html = '';

	if( $init_type == 'admin' ){

		//start the panel html
		$html .= '<div class="panel panel-info" id="position-narrow-slider-panel">
					<div class="panel-heading">
						<a data-toggle="collapse" class="' . $disabled_class[0] . '" data-parent="#position-narrow-slider-panel" href="#position-narrow-group-collapse">Advanced Search</a>';

		//insert narrow counter
		$html .= $active_narrow;

		//display type button
		$html .= '		<span class="panel-addon pull-right">';
		$html .= '			<div class="btn-group ' . $class . '" style="' . $style . '">';
		$html .= '				<button type="button" class="javascript-button btn btn-default' . returnActive($view_type, 'calendar') . '" title="Calendar View" data-href="' . _ROOT_ . '/manage-position?view-type=calendar"><span class="glyphicon glyphicon-calendar"></span></button>';
		$html .= '				<button type="button" class="javascript-button btn btn-default' . returnActive($view_type, 'event') . '" title="Group by Event" data-href="' . _ROOT_ . '/manage-position?view-type=event"><span class="glyphicon glyphicon-list"></span></button>';
		$html .= '				<button type="button" class="javascript-button btn btn-default' . returnActive($view_type, 'list') . '" title="Position List - A to Z" data-href="' . _ROOT_ . '/manage-position?view-type=list"><span class="glyphicon glyphicon-sort-by-alphabet"></span></button>';
		$html .= '			</div>';
		$html .= '		</span>';
		$html .= '		<div class="clearfix"></div>';
		$html .= '	</div><!--CLOSE PANEL HEADING-->';

		//start panel body
		$html .= '	<div class="panel-body panel-collapse collapse" id="position-narrow-group-collapse">';

	} else {

		$html .= '<div class="panel panel-info" id="fe-position-narrow-container">';
		$html .= '	<h3 class="h4 text-center">Narrow Your Results</h3>';
		$html .= '	<div class="panel-body" id="fe-position-narrow-inner">';
	}

	//start options		
	$html .= '		<form id="admin-position-narrow" name="admin-position-narrow" method="post" role="form" data-controller-id="position-narrow-change-nonce">';
	$html .= '			<input type="hidden" id="position-narrow-change-nonce" name="update_narrow" value="false">';
	
	//start position narow functions

	//name
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'col-xs-12' );
	$html .= '			<div class="' . $class . '"><label>Name</label><div class="input-group col-xs-12">';
	$options = array(
		'input_type' => 'text',
		'input_value'=>$position_narrows['name'],
		);
	$html .= createFormInput('position-name-narrow', $options);
	$html .= '			</div></div>';

	//location
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'col-xs-12' );
	$html .= '			<div class="' . $class . '"><label>Location</label><div class="input-group col-xs-12">';
	$options = array(
		'input_type' => 'text',
		'input_value'=>$position_narrows['location'],
		);
	$html .= createFormInput('position-location-narrow', $options);
	$html .= '			</div></div>';

	//description
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'col-xs-12' );
	$html .= '			<div class="' . $class . '"><label>Description</label><div class="input-group col-xs-12">';
	$options = array(
		'input_type' => 'text',
		'input_value'=>$position_narrows['description'],
		);
	$html .= createFormInput('position-description-narrow', $options);
	$html .= '			</div></div>';

	//status
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'col-xs-12' );
	$html .= '			<div class="' . $class . '"><label>Status</label><div class="input-group">';
	$options = array(
		'input_type' => 'select',
		'options' => array('active'=>'Active','inactive'=>'Inactive'),
		'check_value'=>$position_narrows['status'],
		'allow_blank'=>true,
		);
	$html .= createFormInput('position-status-narrow', $options);
	$html .= '			</div></div>';

	//position schedule after date / time
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'col-xs-12' );
	$html .= '			<div class="' . $class . '"><label>Scheduled Position Time</label><div class="input-group">';
	$options = array(
		'input_type' => 'date',
		'input_addon_start'=>'After:'
		);
	if( !empty( $position_narrows['search_date_start'] ) ){
		$options['input_value'] = date('Y-m-d', strtotime( $position_narrows['search_date_start'] ) );
	} else {
		$options['placeholder'] = 'XX:XX';
	}
	$html .= createFormInput('position-date-start-narrow', $options);
	$options = array(
		'input_type' => 'time',
		);
	if( !empty( $position_narrows['search_time_start'] ) ){
		$options['input_value'] = date('H:i', strtotime( $position_narrows['search_time_start'] ) );
	} else {
		$options['placeholder'] = 'XX:XX';
	}
	$html .= createFormInput('position-time-start-narrow', $options);
	$html .= '			</div></div>';

	//before date / time
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'col-xs-12' );
	$html .= '			<div class="' . $class . '"><label>&nbsp;</label><div class="input-group">';
	$options = array(
		'input_type' => 'date',
		'input_addon_start'=>'Before:'
		);
	if( !empty( $position_narrows['search_date_end'] ) ){
		$options['input_value'] = date('Y-m-d', strtotime( $position_narrows['search_date_end'] ) );
	} else {
		$options['placeholder'] = 'XX:XX';
	}
	$html .= createFormInput('position-date-end-narrow', $options);
	$options = array(
		'input_type' => 'time',
		);
	if( !empty( $position_narrows['search_time_end'] ) ){
		$options['input_value'] = date('H:i', strtotime( $position_narrows['search_time_end'] ) );
	} else {
		$options['placeholder'] = 'XX:XX';
	}
	$html .= createFormInput('position-time-end-narrow', $options);
	$html .= '			</div></div>';

	//lead
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'hidden' );
	$html .= '			<div class="' . $class . '"><label>Position Lead</label><div class="input-group">';
	$users = get_users('first_name, last_name, email'," WHERE `user_type` NOT IN ('demo','volunteer','inactive','refused','guest')");
	$user_options = array();
	foreach( $users as $user ){
		$user_options[ $user['id'] ] = $user['first_name'] . ' ' . $user['last_name'] . ' (e:' . $user['email'] . ')';
	}
	$options = array(
		'input_type' => 'select',
		'options' => $user_options,
		'check_value'=>$position_narrows['lead'],
		'allow_blank'=>true,
		);
	$html .= createFormInput('position-lead-narrow', $options);
	$html .= '			</div></div>';

	//created after date / time
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'hidden' );
	$html .= '			<div class="' . $class . '"><label>Position Creation</label><div class="input-group">';
	
	$options = array(
		'input_type' => 'date',
		'input_addon_start'=>'After:'
		);
	if( !empty( $position_narrows['created_date_start'] ) ){
		$options['input_value'] = date('Y-m-d' , strtotime( $position_narrows['created_date_start'] ) );
	} else {
		$options['placeholder'] = 'XXXX/XX/XX';
	}
	$html .= createFormInput('position-created-start-date-narrow', $options);

	$options = array(
		'input_type' => 'time',
		);
	if( !empty( $position_narrows['created_time_start'] ) ){
		$options['input_value'] = date('H:i', strtotime( $position_narrows['created_time_start'] ) );
	} else {
		$options['placeholder'] = 'XX:XX';
	}
	$html .= createFormInput('position-created-start-time-narrow', $options);
	$html .= '			</div></div>';

	//created after date / time
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'hidden' );
	$html .= '			<div class="' . $class . '"><label>&nbsp;</label><div class="input-group">';
	$options = array(
		'input_type' => 'date',
		'input_addon_start'=>'Before:'
		);
	if( !empty( $position_narrows['created_date_end'] ) ){
		$options['input_value'] = date('Y-m-d', strtotime( $position_narrows['created_date_end'] ) );
	} else {
		$options['placeholder'] = 'XXXX/XX/XX';
	}
	$html .= createFormInput('position-created-end-date-narrow', $options);
	$options = array(
		'input_type' => 'time',
		);
	if( !empty( $position_narrows['created_time_end'] ) ){
		$options['input_value'] = date('H:i', strtotime( $position_narrows['created_time_end'] ) );
	} else {
		$options['placeholder'] = 'XX:XX';
	}
	$html .= createFormInput('position-created-end-time-narrow', $options);
	$html .= '			</div></div>';

	//event_id
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-4', 'col-xs-12' );
	$html .= '			<div class="' . $class . '">';
	$html .= '			<label>Event</label><div class="input-group">';
	$events = get_events();
	if( $events ){
		foreach ($events as $id => $value) {

			//check if allows for open enrollment
			$open_enrollment = get_event_meta( $value['id'], 'open_enrollment' );
			$open_enrollment = $open_enrollment['meta_value'];
			$oe_text = null;
			if( $open_enrollment == 'on' ){
				$oe_text = ' (Open Enrollement Available)';
			}
			$events_for_options[ $value['id'] ] = $value['name'] . $oe_text;
		}
	} else {
		$events_for_options = array();
	}
	$options = array(
		'input_type' =>'select',
		'class'=>'',
		'options'=>$events_for_options,
		'check_value'=>$position_narrows['event_id'],
		'allow_blank'=>true,
		);
	$html .= createFormInput('position-events-narrow', $options);
	$html .= '			</div>';

	//show checkboxwes if in admin view
	if( $init_type == 'admin' ){

		//inactive positions
		$options = array(
			'label'=>'Show Inactive Positions',
			'input_type' =>'checkbox',
			'no_form_control'=>true,
			'class'=>'marg-l',
			'input_value'=>$position_narrows['show_inactive'],
			'check_value'=>'1'
			);
		$html .= createFormInput('position-show-inactive-narrow', $options) . '<br>';

		//inactive events
		$options = array(
			'label'=>'Show Inactive Events',
			'input_type' =>'checkbox',
			'no_form_control'=>true,
			'class'=>'marg-l',
			'input_value'=>$position_narrows['show_inactive_events'],
			'check_value'=>'1'
			);
		$html .= createFormInput('position-show-inactive-events-narrow', $options) . '<br>';

		//Events with No Positions
		$options = array(
			'label'=>'Events w/ No Positions',
			'input_type' =>'checkbox',
			'no_form_control'=>true,
			'class'=>'marg-l',
			'input_value'=>$position_narrows['show_events_no_position'],
			'check_value'=>'1'
			);
		$html .= createFormInput('position-show-events-no-position-narrow', $options) . '<br>';
	} else {
		$html .= '<div class="clearfix"></div><br>';
	}

	$html .= '			</div><!-- CLOSE CHECKBOXES -->';
	
	//clear narrows
	$html .= '			<div class="clearfix"></div>';
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-5', 'col-xs-12' );
	$html .= '			<div class="' . $class . '">';
	$options = array(
		'input_type' =>'submit',
		'input_value'=>'Get Positions',
		'class'=>'btn btn-success',
		);
	$html .= createFormInput('position-submit-narrow', $options);
	$html .= '			</div>';

	//submit
	$class = form_input_class_control( $init_type, 'col-xs-12 col-md-5 col-md-offset-2', 'col-xs-12' );
	$html .= '			<div class="' . $class . '">';
	$html .= ' 			<button style="margin-top: 16px;" class="form-control button btn btn-danger" id="clear-pos-narrows" onclick="clearAdminPosNarrows()">Clear All</button>';
	$html .= '			</div>';

	//end form
	$html .= '			<div class="clearfix"></div>';
	$html .= '		</form><br><!--CLOSE NARROW FORM-->';
	$html .= '		<div class=" well well-sm"><p class="help-text">Your search settings are saved and so even when you navigate away from this page your settings will be maintained. This means that if you are unable to find the position you are looking for you may need to alter these settings</p></div>';
	$html .= '	</div><!-- CLOSE PANEL BODY -->';

	//close everything
	$html .= '</div><br><!--CLOSE PANEL-->';

	return $html;
}

/**
DUPLICATE THE POSITION
*/
function duplicate_position( $position_id, $num_dups=1, $event_id=null, $options=array() ){
	global $mysqli;

	//get current position info
	$position = get_position( $position_id );

	//check for event id
	if( empty( $event_id ) ){
		$event_id = $position['event_id'];
	}

	//get the count of current positions
	$count = $mysqli->query("SELECT `id` FROM `positions` WHERE `name` LIKE '" . $position['name'] . "%' and `event_id` = " . $event_id );
	$count = $count->num_rows;
	$count = $count-1;

	//build the insert query
	$query = 'INSERT INTO `positions` (';
	$fields = array();
	$values = array();

	//check options
	$fields .= ( !isset( $options['omit']['event_id'] ) ? '`event_id`' : null );
	$values .= ( !isset( $options['omit']['event_id'] ) ? $event_id : null );

	$fields .= ( !isset( $options['omit']['date_start'] ) ? '`date_start`' : null );
	$values .= ( !isset( $options['omit']['date_start'] ) ? "'" . $position['date_start'] . "'" : null );

	$fields .= ( !isset( $options['omit']['date_end'] ) ? '`date_end`' : null );
	$values .= ( !isset( $options['omit']['date_end'] ) ? "'" . $position['date_end'] . "'" : null );

	$fields .= ( !isset( $options['omit']['created'] ) ? '`created`' : null );
	$values .= ( !isset( $options['omit']['created'] ) ? "'" . $position['created'] . "'" : null );

	$fields .= ( !isset( $options['omit']['created_by'] ) ? '`created_by`' : null );
	$values .= ( !isset( $options['omit']['created_by'] ) ? "'" . $_COOKIE['usrID'] . "'" : null );

	//loop through number of duplicates
	for( $i = $count; $count<=$num_dups; $i++ ){

		//make name
		$fields .= ( !isset( $options['omit']['name'] ) ? '`name`' : null );
		$values .= ( !isset( $options['omit']['name'] ) ? "'" . $position['name'] . " - " . $i . "'" : null );

		//combine query
		$query .= implode( ',', $fields ) . ') VALUES (' . implode( ',', $values ) . ')';
		
		//run query
		$mysqli->query( $query );
	}
}

?>