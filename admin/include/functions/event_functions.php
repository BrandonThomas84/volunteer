<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-20 23:11:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-09-29 23:59:45
 */
function get_events($order='name'){
	global $mysqli;

	$stmt = $mysqli->prepare('SELECT `id`,`name`,`status`,`slug` FROM `events` ORDER BY `' . $order . '`');
	$stmt->execute();
	$result = $stmt->get_result();
	
	$return = array();
	while($row = $result->fetch_assoc()) {
		$return[ $row['id'] ] = array( 'id'=>$row['id'], 'name'=>$row['name'], 'status'=>$row['status'], 'slug'=>$row['slug'] );
    }

    return $return;
}

function get_events_by_status($status, $order='name'){
	global $mysqli;

	$stmt = $mysqli->prepare('SELECT `id`,`name`,`status`,`slug` FROM `events` WHERE `status` = ? ORDER BY `' . $order . '`');
	$stmt->bind_param('s', $status);
	$stmt->execute();
	$result = $stmt->get_result();
	
	$return = array();
	while($row = $result->fetch_assoc()) {
		$return[ $row['id'] ] = array( 'name'=>$row['name'], 'status'=>$row['status'], 'slug'=>$row['slug'] );
    }

    return $return;
}

function get_event($id){
	global $mysqli;

	$stmt = $mysqli->prepare('SELECT `id`,`name`,`status`,`slug` FROM `events` WHERE `id` = ?');
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$result = $stmt->get_result();
	
	$return = false;
	while($row = $result->fetch_assoc()) {
		$return = array( 'name'=>$row['name'], 'status'=>$row['status'], 'slug'=>$row['slug'], 'id'=>$row['id'] );
    }

    return $return;
}
function get_event_by_name($name){
	global $mysqli;

	$stmt = $mysqli->prepare('SELECT `id`,`name`,`status`,`slug` FROM `events` WHERE `name` = ?');
	$stmt->bind_param('s', $name);
	$stmt->execute();
	$result = $stmt->get_result();
	
	$return = array();
	while($row = $result->fetch_assoc()) {
		$return[] = array( 'name'=>$row['name'], 'status'=>$row['status'], 'slug'=>$row['slug'], 'id'=>$row['id'] );
    }

    return $return;
}
function get_event_by_slug($slug){
	global $mysqli;

	$stmt = $mysqli->prepare('SELECT `id`,`name`,`status`,`slug` FROM `events` WHERE `slug` = ?');
	$stmt->bind_param('s', $slug);
	$stmt->execute();
	$result = $stmt->get_result();
	
	while($row = $result->fetch_assoc()) {
		$return = array( 'id'=>$row['id'], 'name'=>$row['name'], 'status'=>$row['status'], 'slug'=>$row['slug'] );
    }

    return $return;
}
function create_new_event($name,$status){
	global $mysqli;

	$message = array();

	//test to see if name exists
	$test = get_event_by_name( $name );

	//check if an event already exists with the submitted name exists
	if( empty( $test ) ){

		$insert = $mysqli->query( "INSERT INTO `events` (`name`,`status`,`slug`) VALUES ('" . $name . "','" . $status . "','" . stringToSlug( $name ) . "')" );
		
		if(!$insert){

			//insert fails
			$message['Database Error'] = array(
				'type'=>'danger',
				'msg'=>'An error occured while trying to create the new event<br>' . $mysqli->error(),
			);

		} else {

			//successfully inserted
			$message['Success!'] = array(
				'type'=>'success',
				'msg'=>'New event successfully created', 
			);
		}

	} else {

		//if name already exists
		$message['Duplicate Event Name'] = array(
			'type'=>'danger',
			'msg'=>'An event with that name already exists', 
		);
	}

	return $message;
}

function update_event($newStatus, $newName, $id){
	global $mysqli;

	$query = $mysqli->query("UPDATE `events` SET `status` = '" . $newStatus . "', `name` = '" . $newName . "' WHERE `id` = " . $id);
	if( $query ){
		return true;
	} else {
		return false;
	}
}

function get_event_meta($event_id, $meta_name){
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT `id`,`event_id`,`meta_name`,`meta_value` FROM  `event_meta` WHERE `event_id` = ? AND `meta_name` = ?");
	$stmt->bind_param('is', $event_id, $meta_name);
	$stmt->execute();
	$result = $stmt->get_result();
	
	//default return
	$return = false;
	while($row = $result->fetch_assoc()) {
		$return = array( 'id'=>$row['id'], 'event_id'=>$row['event_id'], 'meta_name'=>$row['meta_name'], 'meta_value'=>$row['meta_value'] );
    }

    return $return;
}

function update_event_meta($event_id, $meta_name, $meta_value){
	global $mysqli;

	//check for existing value
	$result = get_event_meta( $event_id, $meta_name);

	//check for result
	if( $result ) {
		$id = $result['id'];
	    $query = "UPDATE `event_meta` SET `meta_value` = '" . $meta_value . "' WHERE `id` = " . $id;
	} else {
		$query = "INSERT INTO `event_meta` (`event_id`,`meta_name`,`meta_value`) VALUES ('" . $event_id . "','" . $meta_name . "','" . $meta_value . "')";
	}

	//run update
	$run = $mysqli->query( $query );
	if( $run ){
		return true;
	} else {
		return false;
	}
}

function remove_event($event_id){
	global $mysqli;

	//queries to run
	$metaQuery = "DELETE FROM `event_meta` WHERE `event_id` = " . $event_id;
	$query = "DELETE FROM `events` WHERE `id` = " . $event_id;

	//run update
	$mysqli->query( $metaQuery );
	$mysqli->query( $query );
}

function get_event_roles($event_id){
	global $mysqli;

	$stmt = $mysqli->prepare("SELECT `meta_value` FROM `event_meta` WHERE `event_id` = ? AND `meta_name` = 'event_roles'");
	$stmt->bind_param('i', $event_id);
	$stmt->execute();
	$result = $stmt->get_result();
	
	//default return
	$return = false;

	//get values
	while($row = $result->fetch_assoc()) {
		$return = $row['meta_value'];
    }

    //convert roles from json
    $return = json_decode( $return, true );

    return $return;
}
?>