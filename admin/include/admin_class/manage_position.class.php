<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-20 23:11:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-11-05 13:03:37
 * @NOTES: Need to create a javascript functionality that triggers the update field to change to true on any field change and to enable the submit button. Also all the POST values are not being caught by anything other than a var_dump
 */

class manage_position {

	public function __construct(){

		/**
		INCLUDE THE USER FUNCTIONS
		*/
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'event_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'position_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'volunteer_functions.php');

		$this->title = 'Manage Positions';
		$this->subtitle = 'Manage your event positions from here';
		$this->pageMessage = null;

		//page meta and breadcrumb information
		$this->pageMeta[0] = array(
			'name' => 'Manage Postions',
			'url' => '/manage-position' . breadcrumb_supplements('m') . breadcrumb_supplements('y', false),
			'meta_title' => 'Manage Event Positions',
			'meta_description' => '',
		);

		//set the page meta values
		if(isset( $_GET['function'] ) ){

			//check for create new
			if( $_GET['function'] == 'new' ){
				$position['name'] = 'New Position';
				$position['slug'] = '/manage-position/new';
			} else {
				//get the positions info
				$position = get_position( $_GET['function'] );
				$position['slug'] = '/manage-position/' . $_GET['function'];
			}

			//change the page title
			$this->title = $position['name'];
			$this->subtitle = 'Manage position settings for ' . $position['name'];

			//page meta and breadcrumb information
			$this->pageMeta[1] = array(
				'name' => $position['name'],
				'url' => $position['slug'],
				'meta_title' => $position['name'] . ' - Edit Screen',
				'meta_description' => 'Manage and change settings for the ' . $position['name'] . ' position.',
			);

		} else {

			//set the position view type
			if( isset( $_COOKIE['admin_position_view_type'] ) && !isset( $_GET['view-type'] ) ){
				$this->view_type = $_COOKIE['admin_position_view_type'];
			} else {
				if( isset( $_GET['view-type'] ) ){
					$this->view_type = $_GET['view-type'];
				} else {
					$this->view_type = 'event';
				}

				//set the cookie for next call
				setcookie('admin_position_view_type',$this->view_type,0,'/');

				$removals = array('$view-type=' . $this->view_type, '?view-type=' . $this->view_type, _ROOT_, '/admin' );
				$location = str_replace($removals, null, $_SERVER['REQUEST_URI'] );
				//var_dump( $location );
				submission_redirect( $location );
			}

			//set object scope position narrows default
			$this->position_narrows = get_position_narrow_defaults();

			//update the cookie value
			$this->updateNarrowCookie();

			//get and decode the narrows
			$narrows = json_decode( $_COOKIE['admin_position_narrow'], true );

			//create the query from narrow selections
			$this->posQuery = create_narrowed_query( $narrows );
		}
	}

	public function checkSubmission(){

		//verify license
		$verify = verifyLicense();

		//check for remove
		if( isset( $_GET['rmv'] ) && $_GET['rmv'] == 'true' ){
			remove_position( $_GET['function'] );
			submission_redirect('/manage-position/');
		}

		//check for status click from manage page
		if( isset( $_GET['status'] ) && !empty( $_GET['function'] ) ){	

			//set new status
			$newStatus = $_GET['status'];

			//acceptable status
			$stati = array('active','inactive','filled','unconfirmed');

			//if new status is in acceptable statusesi array
			if( in_array( $newStatus, $stati ) ){
				update_position_meta( $_GET['function'], 'status', $newStatus );
				submission_redirect('/manage-position/' . $_GET['function'] );
			}
			add_page_message('success','Status Updated','Status');
		}

		//check to see if create new position is submitted
		if( isset( $_POST['position-submission'] ) ){
			if( $_POST['position-submission'] == 'new' ){

				$start_date = date('U', strtotime( $_POST['settings-position-start-date'] . ' ' . $_POST['settings-position-start-time'] ) );
				$end_date = date('U', strtotime( $_POST['settings-position-end-date'] . ' ' . $_POST['settings-position-end-time'] ) );
				$newID = create_position( $_POST['settings-position-name'],$_POST['settings-position-event'], $start_date, $end_date );

				//update position information
				$this->updatePosition( $newID );

				add_page_message('success','New Position Created: ' . $_POST['settings-position-name'] .', You can further change settings below.' ,'Created');

				submission_redirect('/manage-position/' . $newID  );

			}elseif( $_POST['position-submission'] == 'edit' ){ //editing a current position
				
				//check for update postion settings
				if( !empty( $_POST['position-updated'] ) && $_POST['position-updated'] == 'true' ){

					//update position information
					$this->updatePosition( $_GET['function'] );

					//check for submission redirect change
					if( !empty( $this->submission_redirect_change ) ){
						submission_redirect( $this->submission_redirect_change );
					} else {
						submission_redirect('/manage-position/' . $_GET['function'] );
					}
				}
			}
		}

		//verify all positions are created on edit page
		if( isset( $_GET['function'] ) && $_GET['function'] !== 'new' ){ $this->verify_db_roles( $_GET['function'] ); }
	}

	public function verify_db_roles( $position_id ){

		//get the databae number of roles
		$roles = get_position_meta_by_name( $position_id, 'roles' );
		$role_count = (int) $roles['meta_value'];
		
		//verify that all positions are created
		$active = get_volunteers('`id`',' WHERE `position_id` = ' . $position_id . ' AND `status` != \'deactivated\'');
		$active_count = count( $active );

		//get the deactivated positions
		$deactivated = get_volunteers('`id`',' WHERE `position_id` = ' . $position_id . ' AND `status` = \'deactivated\'' );
		$deac_count = count( $deactivated );

		//if roles is not equal to active
		if( $role_count !== $active_count ){

			//are there deactivated roles
			if( $deac_count > 0 ){

				//activate deactived roles first
				if( ( $active_count + $deac_count ) <= $role_count ){

					//activate all deactiveade roles
					foreach( $deactivated as $key=>$val ){
						activate_role( $val['volunteer_id'] );
					}
					
				} else {

					//find out how many need to be activated
					$short = $role_count - $active_count;

					//activate missing roles
					for($i=0;$i<=$short;$i++){
						activate_role($deactivated[ $i ]['volunteer_id']);
					}
				}

			} else {

				//check if deactivate or create new
				if( $role_count - $active_count < 0 ){

					//deactive the extra
					$surplus = $active_count - $role_count;
					for( $i=0;$i<$surplus;$i++){
						$reversed = array_reverse( $active );
						deactivate_role( $reversed[ $i ]['volunteer_id'] );
					}
				} else {

					//add missing
					$short = $role_count - $active_count;
					for( $i=0;$i<$short;$i++){
						$id = add_blank_volunteer( $position_id );
					}
				}
			}
		}	
	}

	public function updatePosition( $position_id ){

		//compile the date and time into a unix timestamp
		$start_date = date('U', strtotime( $_POST['settings-position-start-date'] . ' ' . $_POST['settings-position-start-time'] ) );
		$end_date = date('U', strtotime( $_POST['settings-position-end-date'] . ' ' . $_POST['settings-position-end-time'] ) );

		//update core information
		update_position( $position_id, $_POST['settings-position-name'], $_POST['settings-position-event'], $start_date, $end_date );

		//update meta information
		update_position_meta($position_id, 'lead', $_POST['settings-position-lead'] );
		update_position_meta($position_id, 'roles', $_POST['settings-position-roles'] );
		update_position_meta($position_id, 'status', $_POST['settings-position-status'] );
		update_position_meta($position_id, 'require_approval', $_POST['settings-position-require_approval'] );
		update_position_meta($position_id, 'notify', $_POST['settings-position-notify'] );
		update_position_meta($position_id, 'location_address1', $_POST['settings-position-location_address1'] );
		update_position_meta($position_id, 'location_address2', $_POST['settings-position-location_address2'] );
		update_position_meta($position_id, 'location_city', $_POST['settings-position-location_city'] );
		update_position_meta($position_id, 'location_state', $_POST['settings-position-location_state'] );
		update_position_meta($position_id, 'location_zip', $_POST['settings-position-location_zip'] );
		update_position_meta($position_id, 'description', $_POST['settings-position-description'] );
		update_position_meta($position_id, 'is_lead', $_POST['settings-position-is-lead'] );

		//update standard notifications
		$core_notifications = get_position_meta_by_name( $position_id,'notifications');
		$core_notifications = $core_notifications['meta_value'];
		$core_notifications = json_decode( $core_notifications, true );

		$noti_check = array('sign_up','on_change','on_confirm','on_start');
		foreach( $noti_check as $field ){
			if( isset( $_POST['settings-notification-' . $field ] ) ){
				$core_notifications[ $field ]['active'] = 'true';
			} else {
				$core_notifications[ $field ]['active'] = 'false';
			}
		}

		//re-encode array of core notifications
		$new_notifications = json_encode( $core_notifications );
		update_position_meta($position_id, 'notifications', $new_notifications );

		//update custom notifications
		if( isset( $_POST['custom-notifications-json'] ) ){
			$noti_string = str_replace(array("'",",]","[,"), array('"',"]","["), $_POST['custom-notifications-json'] );
			update_position_meta($position_id, 'custom_notifications', $noti_string );
		}

		//update the volunteer roles
		$num_roles = (int) $_POST['settings-position-roles'];
		$volunteers = get_volunteers( '`id`', ' WHERE `position_id` = ' . $position_id );
		for( $i=0;$i<$num_roles;$i++){
			//check if a person was assigned
			if( !empty( $_POST[ 'settings-roles-assign-update-' . $i ] ) ){

				//set the update
				$fields = array(
					'user_id'=>$_POST[ 'settings-roles-assign-update-' . $i ],
					'status'=>'unconfirmed',
					'signup'=>date('Y-m-d H:i:s'),
					'last_contact'=>date('Y-m-d H:i:s'),
					'modified'=>date('Y-m-d H:i:s'),
					);

				//update the record
				$updated_vol = update_volunteer( $volunteers[ $i ]['volunteer_id'], $fields );

				//send a message if the record was updated
				if( $updated_vol ){

					//send the confirmation message
					require_once( __INCLUDE_PATH__ . 'class' . _DIRSEP_ . 'mail.class.php' );
					$mail = new mail;
					$mail->set_headers();
					$mail->message = file_get_contents( __EMAILTEMPLATES__  . 'position_signup.html' );

					//get position name
					$position = get_position( $position_id );
					$user = get_volunteer_info( $_POST[ 'settings-roles-assign-update-' . $i ] );

					//insert user values
					$replace = array(
						'USER'=>$user['first_name'] . $user['last_name'],
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
			}

			//check fo unassign
			if( !empty( $_POST[ 'settings-roles-unassign-update-' . $i ] ) ){

				//set the update
				$fields = array(
					'user_id'=>'0',
					'status'=>'unfilled',
					'signup'=>'NULL',
					'last_contact'=>'NULL',
					'modified'=>date('Y-m-d H:i:s'),
					);

				//update the record
				$updated_vol = update_volunteer( $volunteers[ $i ]['volunteer_id'], $fields );
				
				//send a message if the record was updated
				if( $updated_vol ){

					//send the confirmation message
					require_once( __INCLUDE_PATH__ . 'class' . _DIRSEP_ . 'mail.class.php' );
					$mail = new mail;
					$mail->set_headers();
					$mail->message = file_get_contents( __EMAILTEMPLATES__  . 'position_removal.html' );

					//get position name
					$position = get_position( $position_id );
					$user = get_volunteer_info( $_POST[ 'settings-roles-unassign-update-' . $i ] );

					//insert user values
					$replace = array(
						'USER'=>$user['first_name'] . $user['last_name'],
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
			}
		}

		//check attributes
		$attributes = getAttributes();
		foreach( $attributes as $attr ){

			//set the default value array
			$meta_val = array( 'value'=>'','type'=>'','required'=>'false' );

			//update value
			if( !empty( $_POST['settings-attribute-' . $attr['id'] ] ) ){
				$meta_val['value'] = $_POST['settings-attribute-' . $attr['id'] ];

				//set the type
				if( !empty( $_POST['settings-attribute-type-' . $attr['id'] ] ) ){
					$meta_val['type'] = $_POST['settings-attribute-type-' . $attr['id'] ];
				}
			}

			//encode the array
			$meta_val = json_encode( $meta_val );
			update_position_meta($position_id, 'attr-' . $attr['id'], $meta_val );

			add_page_message('success','Position Updated: ' . $_POST['settings-position-name'],'Updated');

		}

		//check for create duplicate trigger and create new positions
		if( !empty( $_POST['duplicate-position-trigger'] ) && $_POST['duplicate-position-trigger'] == 'true' ){
			
			//number of duplicates
			$iterations = $_POST['settings-number-of-duplicates'];

			//loop through iterations of duplicates
			for($i=1;$i<=$iterations;$i++){
				$this->duplicate_position( $position_id, $_POST['settings-duplicate-to-event'], $i );
			}

			//add a message to the cookie tree
			add_page_message('success','The position has been duplicated ' . $iterations . ' times.','Duplicated');

			//change the redirect location
			$this->submission_redirect_change = '/manage-position';
		}
		
	}

	public function duplicate_position( $position_id, $target_event_id, $iteration ){

		//get position information
		$position = get_position( $position_id );

		//get position meta_values
		$meta_defaults = set_position_meta_defaults();
		$pos_meta = get_position_meta_for_id( $position_id );
		foreach( $pos_meta as $pm ){
			$meta_values[ $pm['meta_name'] ] = $pm['meta_value'];
		}

		//set notifications
		if( isset( $_POST['settings-omit-duplicate-notifications'] ) ){

			//array of notification meta names
			$notifications = array('notifications','notify_users','notify','custom_notifications');

			//loop through meta names to set to default
			foreach( $notifications as $n ){
				$meta_values[ $n ] = $meta_defaults[ $n ];
			}
		}

		//set lead
		if( isset( $_POST['settings-omit-duplicate-lead'] ) ){
			$meta_defaults['lead'] = $meta_defaults[ 'lead' ];
		}

		//set start
		$start = $position['date_start'];
		if( isset( $_POST['settings-omit-duplicate-start-time'] ) ){

			//strip the time from the position
			$start = strtotime( $start );
			$start = date('Y-m-d', $start );
			$start = (string) $start . ' 00:00:00';
			$start = date('U', strtotime( $start ) );
		}

		//set end 
		$end = $position['date_end'];
		if( isset( $_POST['settings-omit-duplicate-end-time'] ) ){

			//strip the time from the position
			$end = strtotime( $end );
			$end = date('Y-m-d', $end );
			$end = (string) $end . ' 00:00:00';
			$end = date('U', strtotime( $end ) );
		}
		
		//set roles
		if( isset( $_POST['settings-omit-duplicate-roles'] ) ){

			//array of notification meta names
			$role_keys = array('role_info','roles');

			//loop through meta names to set to default
			foreach( $role_keys as $r ){
				$meta_values[ $r ] = $meta_defaults[ $r ];
			}
		}

		//set status
		if( isset( $_POST['settings-new-position-status-inactive'] ) ){
			$meta_values['status'] = $meta_defaults['status'];
		}

		//set positon name
		$name = $position['name'] . ' - ' . $iteration;

		//create the position
		create_position( $name, $target_event_id, $start, $end, $meta_values );
	}

	public function display(){

		//verify license
		$verify = verifyLicense();

		$html = '';
		
		//check for event
		if( isset( $_GET['function'] ) ){
			$html .= $this->positionOptions( $_GET['function'] );
		} else {

			//add position controls to the top of the page
			$html .= positionControls( 'admin', $this->view_type, $this->position_narrows, $this->enabled_narrows );			

			if( !empty( $_COOKIE['admin_position_view_type'] ) ){
				$view_type = $_COOKIE['admin_position_view_type'];
			} else {
				$view_type = 'list';
			}

			//get output based on view type
			if( $view_type == 'list' ){
				$html .= $this->selectPosition_ListView();	
			} elseif( $view_type == 'calendar' ){
				$html .= $this->selectPosition_CalView();	
			} elseif( $view_type == 'event' ){
				$html .= $this->selectPosition_EventView();	
			}
			
			//create position button
			$html .= '<button class="duplicateNavItem button btn btn-primary col-xs-10 col-xs-offset-1 col-md-6 col-md-offset-3" data-nav-id="nav-new-position">Create New Position</button>';
			$html .= '<dic class="clearfix"></div>';
		}

		return $html;
	}

	private function updateNarrowCookie(){	
	
		//change the object scope properties
		if( isset( $_POST['update_narrow'] ) && $_POST['update_narrow'] == 'true' ){

			$this->position_narrows['status'] = $_POST['settings-position-status-narrow'];
			$this->position_narrows['search_date_start'] = $_POST['settings-position-date-start-narrow'];
			$this->position_narrows['search_time_start'] = $_POST['settings-position-time-start-narrow'];
			$this->position_narrows['search_date_end'] = $_POST['settings-position-date-end-narrow'];
			$this->position_narrows['search_time_end'] = $_POST['settings-position-time-end-narrow'];
			$this->position_narrows['created_date_start'] = $_POST['settings-position-created-start-date-narrow'];
			$this->position_narrows['created_time_start'] = $_POST['settings-position-created-start-time-narrow'];
			$this->position_narrows['created_date_end'] = $_POST['settings-position-created-end-date-narrow'];
			$this->position_narrows['created_time_end'] = $_POST['settings-position-created-end-time-narrow'];
			$this->position_narrows['event_id'] = $_POST['settings-position-events-narrow'];
			$this->position_narrows['name'] = $_POST['settings-position-name-narrow'];
			$this->position_narrows['lead'] = $_POST['settings-position-lead-narrow'];
			$this->position_narrows['location'] = $_POST['settings-position-location-narrow'];
			$this->position_narrows['description'] = $_POST['settings-position-description-narrow'];

			//checkboxes
			if( isset( $_POST['settings-position-show-inactive-narrow'] ) ){
				$this->position_narrows['show_inactive'] = '1';
			} else {
				$this->position_narrows['show_inactive'] = null;
			}
			if( isset( $_POST['settings-position-show-inactive-events-narrow'] ) ){
				$this->position_narrows['show_inactive_events'] = '1';	
			} else {
				$this->position_narrows['show_inactive_events'] = null;
			}
			if( isset( $_POST['settings-position-show-events-no-position-narrow'] ) ){
				$this->position_narrows['show_events_no_position'] = '1';	
			} else {
				$this->position_narrows['show_events_no_position'] = null;
			}

			//cookie value as all the updated posts (should include previous cookie values that have not been overwritten)
			$cookieVal = json_encode( $this->position_narrows );

			//indicator for determining whether to use question mark or ampersand
			$first_get = true;

			//array of gets to check for
			$gets = array('y','m');

			//start of destination url
			$location = '/manage-position';

			//check gets
			foreach ($gets as $var) {

				//if get is set
				if( isset( $_GET[ $var ] ) ){

					//if first get
					if( $first_get ){
						//add ?
						$location .= '?';

						//unset first
						$first_get = false;
					} else {
						//add &
						$location .= '&';
					}

					//add the var itself
					$location .= $var . '=' . $_GET[ $var ];
				}
			}
			//set redirect directive			
			submission_redirect( $location );

		} else {

			//check for previous existence
			if( isset( $_COOKIE['admin_position_narrow'] ) ){

				//set cookie value to existing cookie value
				$cookieVal = $_COOKIE['admin_position_narrow'];

				//set the viewable values
				$cookie_pos_narrow = json_decode( $cookieVal );
				foreach( $cookie_pos_narrow as $key=>$value ){
					$this->position_narrows[ $key ] = $value;
				}

			} else {

				//if no cookie set value to the position_narrow array
				$cookieVal = json_encode( $this->position_narrows );
			}
		}

		//set the cookie		
		setcookie( 'admin_position_narrow',$cookieVal,0,'/');

		//count of enabled narrow bys
		$this->enabled_narrows = 0;
		foreach( $this->position_narrows as $key=>$value ){
			//check if a value should be counted
			if( !empty( $value ) ){
				$this->enabled_narrows++;
			}				
		}

	}

	private function positionOptions( $position_id ){

		//start html
		$html = '';

		//if new
		if( $position_id == 'new' ){

			$formType = '<input type="hidden" name="position-submission" value="new">';

			//check for submitted name
			if( isset( $_POST['position_name'] ) ){
				$position['name'] = $_POST['position_name'];
			} else {
				$position['name'] = '';
			}

			//set remaining values
			$position['event_id'] = '';
			$position['date_start'] = '';
			$position['date_end'] = '';
			$position['created'] = date('U');
			$position['created_by'] = $_COOKIE['usrID'];

			//set meta values to defaults
			$position_meta = set_position_meta_defaults();

			$volunteers = array();
			$active_volunteers = array();
			
		} else {

			$formType = '<input type="hidden" name="position-submission" value="edit">';

			//get the position attributes
			$position = get_position( $_GET['function'] );

			//get postion meta
			$position_meta_raw = get_position_meta_for_id( $_GET['function'] );

			//set all to meta_value oppsed to array
			foreach( $position_meta_raw as $key=>$val ){
				$position_meta[ $key ] = $position_meta_raw[ $key ]['meta_value'];
			}

			//set any missing to default
			$meta_defaults = set_position_meta_defaults();
			foreach( $meta_defaults as $key=>$val ){
				if( empty( $position_meta[ $key ] ) ){
					$position_meta[ $key ] = $meta_defaults[ $key ];
				}
			}

			$volunteers = get_volunteers( '`id`', ' WHERE `position_id` = ' . $position_id );
			$active_volunteers = get_volunteers( '`id`', ' WHERE `position_id` = ' . $position_id . ' AND `status` IN (\'confirmed\',\'unconfirmed\')');
		}

		//page scroll buttons
		$html .= '<div id="page-scroll-container">';
		$html .= '<p class="h5 text-center bg-default">Quick Navigation</p>';
		$html .= '<ul>';
		$html .= '<li class="btn btn-lrg btn-info page-scroll" data-element="#position-basic-container">Basic</li>';
		$html .= '<li class="btn btn-lrg btn-info page-scroll" data-element="#position-location-container">Location</li>';
		$html .= '<li class="btn btn-lrg btn-info page-scroll" data-element="#position-attributes-container">Attributes</li>';
		$html .= '<li class="btn btn-lrg btn-info page-scroll" data-element="#position-role-container">Roles</li>';
		$html .= '<li class="btn btn-lrg btn-info page-scroll" data-element="#position-notification-container">Notifications</li>';
		$html .= '</ul>';
		$html .= '</div>';

		//start form		
		$html .= '<form id="position-settings" class="form-inline form-has-nonce" role="form" method="post" data-controller-id="position-form-nonce" >';

		//basic info
		$html .= '<div id="position-basic-container" class="row-fluid">';
		$html .= '<h2 class="h3 bg-info text-info">Basic Information</h2>';
		$html .= '<p class="help-block">The following options are basic controls for the position.</p>';

		//form nonce
		$formNonce = '<input type="hidden" id="position-form-nonce" name="position-updated" value="false">';
		$html .= $formType . $formNonce;

		//position name
		$options = array(
				'input_value'=>$position['name'],
				'option_name'=>'position-name',
				'input_type'=>'text',
				'placeholder'=>'Position Name',
				'required'=>true,
				'input_addon_start'=>'Name:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-6"><div class="input-group">' . createFormInput( 'position-name', $options ) . '</div></div>';

		//position event
		$events = get_events();
		$event_options = array();
		foreach( $events as $event ){
			$event_options[ $event['id'] ] = $event['name'];
		}
		$options = array(
				'input_value'=>$position['event_id'],
				'option_name'=>'position-event',
				'input_type'=>'select',
				'required'=>true,
				'input_addon_start'=>'Event:',
				'options'=>$event_options,
				'check_value'=>$position['event_id'],
			);
		$html .= '<div class="form-group col-xs-12 col-md-6"><div class="input-group">' . createFormInput( 'position-event', $options ) . '</div></div>';

		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

		//position start and end dates
		if( !empty( $position['date_start'] ) ){
			$start_date = date('Y-m-d', $position['date_start'] );
			$start_time = date('H:i', $position['date_start'] );
			$end_date = date('Y-m-d', $position['date_end'] );
			$end_time = date('H:i', $position['date_end'] );
		} else {
			$start_date = date('Y-m-d');
			$start_time = date('H:i');
			$end_date = date('Y-m-d');
			$end_time = date('H:i');
		}

		$options = array(
				'input_value'=>$start_date,
				'option_name'=>'position-start-date',
				'input_type'=>'date',
				'required'=>true,
				'input_addon_start'=>'Start Date:',
			);
		$options2 = array(
				'input_value'=>$start_time,
				'option_name'=>'position-start-time',
				'input_type'=>'time',
				'required'=>true,
				'input_addon_start'=>'Time:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-6"><div class="input-group">' . createFormInput( 'position-start-date', $options ) . createFormInput( 'position-start-time', $options2 ) . '</div></div>';

		$options = array(
				'input_value'=>$end_date,
				'option_name'=>'position-end-date',
				'input_type'=>'date',
				'required'=>true,
				'input_addon_start'=>'End Date:',
			);
		$options2 = array(
				'input_value'=>$end_time,
				'option_name'=>'position-end-time',
				'input_type'=>'time',
				'required'=>true,
				'input_addon_start'=>'Time:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-6"><div class="input-group">' . createFormInput( 'position-end-date', $options ) . createFormInput( 'position-end-time', $options2 ) . '</div></div>';

		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

		//get users that are not solely volunteers
		$lead_options = get_users();
		if( $lead_options ){
			foreach($lead_options as $user) {
				$leads[ $user['id'] ] = $user['first_name'] . ' ' . $user['last_name'] . ' (ADMIN USER e:' . $user['email'] . ')';
			}
		} else {
			$leads = array();
		}		

		$options = array(
				'check_value'=>$position_meta['lead'],
				'option_name'=>'position-lead',
				'input_type'=>'select',
				'required'=>true,
				'input_addon_start'=>'Lead:',
				'options'=>$leads,
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'position-lead', $options ) . '</div></div>';

		//number of roles
		$roles = 1;
		if( !empty( $position_meta['roles'] ) ){
			$roles = (int) $position_meta['roles'];
		} 

		//set the default minimum number of roles, determined by the number that are filled
		$role_min = count( $active_volunteers );
		if( $role_min == 0 ){ $role_min = 1; }
		
		
		//number of roles
		$options = array(
				'input_value'=>$roles,
				'option_name'=>'position-roles',
				'input_type'=>'number',
				'required'=>true,
				'input_addon_start'=>'# Roles:',
				'min_value'=>$role_min,
			);		
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'position-roles', $options ) . '</div></div>';

		//position status
		if( $_GET['function'] == 'new' ){
			$pos_status = 'inactive';
		} else {
			$pos_status = $position_meta['status'];
		}
		$options = array(
				'option_name'=>'position-status',
				'input_type'=>'select',
				'required'=>true,
				'check_value'=>$pos_status,
				'input_addon_start'=>'Status:',
				'options'=> array('inactive'=>'Inactive','active'=>'Active'),
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'position-status', $options ) . '</div></div>';

		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';		

		//require approval
		$options = array(
				'input_addon_start'=>'Require Approval',
				'option_name'=>'position-require_approval',
				'required'=>true,
				'input_type'=>'select',
				'options'=>array('true'=>'Yes','false'=>'No'),
				'check_value'=>$position_meta['require_approval'],
			);
		$html .= '<div class="form-group col-xs-12 col-md-3"><div class="input-group">' . createFormInput( 'position-require_approval', $options ) . '</div></div>';

		//notifications on
		$options = array(
				'input_addon_start'=>'Notifications',
				'option_name'=>'position-notify',
				'required'=>true,
				'input_type'=>'select',
				'options'=>array('true'=>'ON','false'=>'OFF'),
				'check_value'=>$position_meta['notify'],
			);
		$html .= '<div class="form-group col-xs-12 col-md-3"><div class="input-group">' . createFormInput( 'position-notify', $options ) . '</div></div>';	

		//is lead position
		$options = array(
				'input_addon_start'=>'Is Lead',
				'option_name'=>'position-is-lead',
				'required'=>true,
				'input_type'=>'select',
				'options'=>array('true'=>'YES','false'=>'NO'),
				'check_value'=>$position_meta['is_lead'],
				'allow_blank'=>false,
			);
		$html .= '<div class="form-group col-xs-12 col-md-3"><div class="input-group">' . createFormInput( 'position-is-lead', $options ) . '</div></div>';	

		//insert a break before the de"scription
				$html .= '<div class="clearfix"></div><br>';

		//position description
		$options = array(
				'label'=>'Position Description',
				'option_name'=>'position-description',
				'required'=>false,
				'input_type'=>'textarea',
				'check_value'=>$position_meta['description'],
				'class'=>'col-xs-12',
			);
		$html .= '<div class="form-group col-xs-12"><div class="input-group col-xs-12">' . createFormInput( 'position-description', $options ) . '</div></div>';

		//end basic
		$html .= '</div><!--END BASIC INFO-->';
		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

		//location settings
		$html .= '<div id="position-location-container" class="row-fluid table-fluid">';
		$html .= '<h2 class="h3 bg-info text-info">Location Information</h2>';
		$html .= '<p class="help-block">Use the following options to set the position location. This information is useful for the map functions in the front end.</p>';

		//address 1
		$options = array(
				'option_name'=>'location_address1',
				'input_type'=>'text',
				'required'=>false,
				'input_value'=>$position_meta['location_address1'],
				'input_addon_start'=>'Address 1:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'position-location_address1', $options ) . '</div></div>';

		//address 2
		$options = array(
				'option_name'=>'location_address2',
				'input_type'=>'text',
				'required'=>false,
				'input_value'=>$position_meta['location_address2'],
				'input_addon_start'=>'Address 2:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'position-location_address2', $options ) . '</div></div>';

		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

		//city
		$options = array(
				'option_name'=>'location_city',
				'input_type'=>'text',
				'required'=>false,
				'input_value'=>$position_meta['location_city'],
				'input_addon_start'=>'City:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'position-location_city', $options ) . '</div></div>';

		//state
		$options = array(
				'option_name'=>'location_state',
				'input_type'=>'select',
				'options'=>get_states_array(),
				'required'=>false,
				'check_value'=>$position_meta['location_state'],
				'input_addon_start'=>'State:',
				'allow_blank'=>true,
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'position-location_state', $options ) . '</div></div>';

		//zip code
		$options = array(
				'option_name'=>'location_zip',
				'input_type'=>'text',
				'required'=>false,
				'input_value'=>$position_meta['location_zip'],
				'input_addon_start'=>'Postal Code:',
				'placeholder'=>'00000',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'position-location_zip', $options ) . '</div></div>';

		//end location
		$html .= '</div><!--END LOCATION-->';
		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

		//attribute requirements
		$html .= '<div id="position-attributes-container" class="row-fluid table-fluid">';
		$html .= '<h2 class="h3 bg-info text-info">Position Attributes</h2>';
		$html .= '<p class="help-block">Use the following fields to limit who can sign up for volunteer positions.</p>';
		$html .= '<p class="help-block">Select or input the value and the match type to enable limitations. Checking the box will require the user to input their information at the beginning of their session.</p>';

		//get attributes
		$attributes = getAttributes();

		//loop through attributes
		foreach( $attributes as $attr ){

			//start input options
			$options = array(
					'option_name'=>'attribute-' . $attr['id'],
					'input_addon_start'=>$attr['name'],
					'placeholder'=>$attr['name'] . ' Value',
				);

			//check for placeholder to overwrite
			if( !empty( $attr['placeholder'] ) ){
				$options['placeholder'] = $attr['placeholder'];
			}

			//create type field options
			$match_options = array(
					'input_type'=>'select',
					'option_name'=>'attribute-type-' . $attr['id'],
					'placeholder'=>'Match Type',
					'options'=>array(''=>'','match'=>'MATCH','no_match'=>'NO MATCH'),
				);

			//get data tags
			$attr_data = get_option_by_id( $attr['data'] );
			$attr_data = json_decode( $attr_data['option_value'], true );
			$data_tags = '';

			if( !empty( $attr_data ) ){

				foreach($attr_data as $key => $value) {
					$data_tags .= ' data-' . str_replace(array(' ','_'), '-', strtolower( $key ) ) . '="' . $value . '"';
				}
			}
			
			//inlcude options if mutltiple choice
			if( $attr['type'] == 'multiple_choice' ){

				//set the field type 
				$options[ 'input_type'] = 'select';

				//get the options
				$attr_options = get_option_by_id( $attr['options'] );
				$attr_options = json_decode( $attr_options['option_value'], true );
				
				//blank option for the beginning of select
				$attr_options = array_merge( array(''=>'Any Value'), $attr_options );

				//set the input options
				$options[ 'options' ] = $attr_options;

				//set the check value if value is set
				if( !empty( $position_meta[ 'attr-' . $attr['id'] ] ) ){
					$options['check_value'] = $position_meta[ 'attr-' . $attr['id'] ];				
				}

			} elseif( $attr['type'] ==  'date' ){

				//set the field type 
				$options[ 'input_type'] = 'number';
				$match_options['options']['greater_than_mns'] = 'MORE THAN XX MINUTES';
				$match_options['options']['greater_than_hrs'] = 'MORE THAN XX HOURS';
				$match_options['options']['greater_than_dys'] = 'MORE THAN XX DAYS';
				$match_options['options']['greater_than_wks'] = 'MORE THAN XX WEEKS';
				$match_options['options']['greater_than_mnths'] = 'MORE THAN XX MONTHS';
				$match_options['options']['greater_than_yrs'] = 'MORE THAN XX YEARS';

				$match_options['options']['less_than_mns'] = 'LESS THAN XX MINUTES';
				$match_options['options']['less_than_hrs'] = 'LESS THAN XX HOURS';
				$match_options['options']['less_than_dys'] = 'LESS THAN XX DAYS';
				$match_options['options']['less_than_wks'] = 'LESS THAN XX WEEKS';
				$match_options['options']['less_than_mnths'] = 'LESS THAN XX MONTHS';
				$match_options['options']['less_than_yrs'] = 'LESS THAN XX YEARS';

				//unset the match no match
				unset( $match_options['options']['match'] );
				unset( $match_options['options']['no_match'] );

			} else {

				//set the field type 
				$options[ 'input_type'] = $attr['type'];

				//expand match options
				$match_options['options']['greater_than'] = 'GREATER THAN';
				$match_options['options']['less_than'] = 'LESS THAN';
			}

			//check and set for default value
			if( !empty( $attr['default'] ) ){
				$options['input_value'] = $attr['default'];
			}

			//set value if present (overwrites default)
			if( !empty( $position_meta[ 'attr-' . $attr['id'] ] ) ){
				$decoded = json_decode( $position_meta[ 'attr-' . $attr['id'] ], true );
				$options['input_value'] = $decoded['value'];
				$options['check_value'] = $decoded['value'];

				$match_options['input_value'] = $decoded['type'];
				$match_options['check_value'] = $decoded['type'];
			}

			//set the required checkbox
			$attr_req_checked = null;
			if( !empty( $decoded['required'] ) && $decoded['required'] == 'true' ){ 
				$attr_req_checked = ' checked '; 
			}


			$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput( 'attribute-' . $attr['id'], $options ) . createFormInput( 'attribute-type-' . $attr['id'], $match_options ) . '</div></div>';
		}		

		//end requirements
		$html .= '</tbody></table><div class="clearfix"></div></div><!--END REQUIREMENTS-->';
		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

		//verify not on new position creation
		if( $position_id !== 'new' ){

			//roles tables
			$html .= '<div id="position-role-container" class="row-fluid table-fluid">';
			$html .= '<h2 class="h3 bg-info text-info">Role Information</h2>';
			$html .= '<p class="help-block">The number of roles is determined by the "# Roles" setting in the "<a href="#" data-element="#position-basic-container" class="tooltips page-scroll" data-toggle="tooltip" title="Go to Basic Information">Basic Information</a>" area. If you would like to add / remove roles please adjust the "# Roles" option. You may not remove roles that already have volunteer applicants.</p>';

			//hidden field that contains the role json
			$html .= '<input type="hidden" id="position-roles-json" name="position-roles-json" value="' . str_replace('"','\'', json_encode( $volunteers ) ) . '">';

			//start roles table
			$html .= '<table class="table table-striped table-hover">';
			$html .= '<thead><th>#</th><th>Volunteer Information</th><th>Role Creation</th><th>Role Modified</th><th>Assign Volunteer</th></thead><tbody>';

			//get users select list
			$users_list = get_users('first_name, last_name, email',' WHERE user_type != \'refused\'');
			$user_option_array = array();
			foreach( $users_list as $user ){
				$user_option_array[ $user['id'] ] = $user['first_name'] . ' ' . $user['last_name'] . ' (e:' . $user['email'] . ')';
			}

			//loop through available roles
			for( $i=0; $i<$roles; $i++ ){

				//get the position information
				$pos_vols = get_volunteer_info_by_vol_id( $volunteers[ $i ]['volunteer_id'] );
				$vol_id = $pos_vols['volunteer_id'];

				//check for status
				if( $pos_vols['status'] == 'unfilled' ){
					$style_class = 'danger';
				} elseif( $pos_vols['status'] == 'confirmed' ){
					$style_class = 'success';
				} elseif( $pos_vols['status'] == 'unconfirmed' ){
					$style_class = 'warning';
				} else {
					$style_class = 'default';
				}

				//set row info
				if( !empty( $pos_vols['first_name'] ) ){
					$name = $pos_vols['last_name'] . ', ' . $pos_vols['first_name'] . ' <span class="label label-' . $style_class . '">' . $pos_vols['status']  . '</span>';					
				} else {
					$name = 'No Volunteer <span class="label label-' . $style_class . '">' . $pos_vols['status']  . '</span>';
				}

				$html .= '<tr class="' . $style_class . ' ' . $pos_vols['status'] . '" date-vol-id="' . $vol_id . '">';
				$html .= '<td>' . ($i+1) . '</td>';				

				//set dates
				$created = ( isset( $pos_vols['created_date'] ) ? $pos_vols['created_date'] : date('U') );
				$created = date('m/d/Y', $created );
				$modified = ( isset( $pos_vols['modified_date'] ) ? $pos_vols['modified_date'] : date('U') );
				$modified = date('m/d/Y', $modified );

				$html .= '<td class="role-name">' . $name . '</td>';
				$html .= '<td class="role-created">' . $created . '</td>';
				$html .= '<td class="role-modified">' . $modified . '</td>';

				//assign volunteer drop down if no volunteer
				if( empty( $pos_vols['u_id'] ) ){
					$options = array(
						'option_name'=>'roles-assign-update-' . $i,
						'input_type'=>'select',
						'options'=>$user_option_array,
						'input_value'=>$pos_vols['u_id'],
						'check_value'=>$pos_vols['u_id'],
						'allow_blank'=>true,
					);
					$assign_type = 'assign';
					$remv_btn = null;
				} else {
					$options = array(
						'option_name'=>'roles-unassign-update' . $i,
						'input_type'=>'hidden',
						'input_value'=>'',
						'class'=>'remove-volunteer-id-trigger',
					);
					$assign_type = 'unassign';
					$remv_btn = '<span class="remove-volunteer-from-role btn btn-danger" data-confirm-message="Are you sure you would like to unassign this person?" data-role-id="' . $pos_vols['u_id'] . '">Unassign</span>';
				}

				
				$html .= '<td class="role-control">' . createFormInput( 'roles-' . $assign_type . '-update-' . $i, $options ) . $remv_btn . '</td>';
				$html .= '<tr>';
			}

			$html .= '</tbody></table></div><!--END ROLES-->';
			$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

			//notification settings
			$html .= '<div id="position-notification-container" class="row-fluid table-fluid">';
			$html .= '<h2 class="h3 bg-info text-info">Notification Settings</h2>';
			$html .= '<p class="help-block">The following options determine when notification emails are sent.</p>';

			//decode notifications
			$notifications = json_decode( $position_meta['notifications'], true );

			//on sign up
			$options = array(
					'option_name'=>'notification-sign_up',
					'input_type'=>'checkbox',
					'input_value'=>$notifications['sign_up']['active'],
					'check_value'=>'true',
					'label'=>'New Sign Up:',
				);
			$html .= '<div class="form-group col-xs-6 col-md-3 text-center"><div class="input-group">' . createFormInput( 'notification-sign_up', $options ) . '</div></div>';

			//on update
			$options = array(
					'option_name'=>'notification-on_change',
					'input_type'=>'checkbox',
					'input_value'=>$notifications['on_change']['active'],
					'check_value'=>'true',
					'label'=>'Position Change:',
				);
			$html .= '<div class="form-group col-xs-6 col-md-3 text-center"><div class="input-group">' . createFormInput( 'notification-on_change', $options ) . '</div></div>';

			//on confirm
			$options = array(
					'option_name'=>'notification-on_confirm',
					'input_type'=>'checkbox',
					'input_value'=>$notifications['on_confirm']['active'],
					'check_value'=>'true',
					'label'=>'Volunteer Confirm:',
				);
			$html .= '<div class="form-group col-xs-6 col-md-3 text-center"><div class="input-group">' . createFormInput( 'notification-on_confirm', $options ) . '</div></div>';

			//on position start
			$options = array(
					'option_name'=>'notification-on_start',
					'input_type'=>'checkbox',
					'input_value'=>$notifications['on_start']['active'],
					'check_value'=>'true',
					'label'=>'Position Start:',
				);
			$html .= '<div class="form-group col-xs-6 col-md-3 text-center"><div class="input-group">' . createFormInput( 'notification-on_start', $options ) . '</div></div>';

			$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

			//show table for notifications
			$html .= '<div class="row-fluid table-fluid">';
			$html .= '<h4>Schedule Custom Notifications</h4>';
			$html .= '<table id="position-custom-notifications" class="table table-striped table-hover table-bordered col-xs-12 col-sm-10 col-sm-offset-1 col-md-offset-0">';
			$html .= '<thead><th class="text-center">#</th><th class="text-center">Schedule</th><th class="text-center">Message</th><th class="text-center">Recipients</th><th>Re-Confirm</th><th></th></thead><tbody>';
			$html .= '<tr><td>Add New</td>';

			//new date
			$options = array(
					'option_name'=>'notification-new-date',
					'input_type'=>'select',
					'options'=>array( '300'=>'5 Minutes', '600'=>'10 Minutes', '900'=>'15 Minutes', '1800'=>'30 Minutes', '2700'=>'45 Minutes', '3600'=>'1 Hour', '7200'=>'2 Hours', '10800'=>'3 Hours', '14400'=>'4 Hours', '21600'=>'6 Hours', '43200'=>'12 Hours', '64800'=>'18 Hours', '86400'=>'1 Day', '172800'=>'2 Days', '259200'=>'3 Days', '345600'=>'4 Days', '432000'=>'5 Days', '518400'=>'6 Days', '604800'=>'7 Days', '1209600'=>'2 Weeks', '1814400'=>'3 Weeks', '2419200'=>'4 Weeks', '2592000'=>'30 Days', '5184000'=>'60 Days', '7776000'=>'90 Days', '15552000'=>'180 Days' ),
					'class'=>'nonce-ignore',
					'input_addon_end'=>'Before',
					'allow_blank'=>true,
				);
			$html .= '<td id="new-notification-date"><div class="form-group col-xs-12"><div class="input-group">' . createFormInput( 'notification-new-date', $options ) . '</div></div></td>';

			//new message
			$msg_options = array( 'reminder'=>'Reminder', 'verify'=>'Verification' );
			$options = array(
					'option_name'=>'notification-new-message',
					'input_type'=>'select',
					'options'=>$msg_options,
					'class'=>'nonce-ignore',
					'input_addon_start'=>'Message:',
					'allow_blank'=>true,
				);
			$html .= '<td id="new-notification-msg"><div class="form-group col-xs-12"><div class="input-group">' . createFormInput( 'notification-new-message', $options ) . '</div></div></td>';

			//new recipients
			$recipient_options = array( 'all'=>'Volunteer / Watchers / Creator', 'volunteers'=>'Volunteers Only', 'creator'=>'Creator Only', 'watchers'=>'Watchers Only' );
			$options = array(
					'option_name'=>'notification-new-recipients',
					'input_type'=>'select',
					'options'=>$recipient_options,
					'class'=>'nonce-ignore',
					'input_addon_start'=>'Recipients:',
					'allow_blank'=>true,
				);
			$html .= '<td id="new-notification-recip"><div class="form-group col-xs-12"><div class="input-group">' . createFormInput( 'notification-new-message', $options ) . '</div></div></td>';

			//get custom notifications
			$custom_notif = json_decode( $position_meta['custom_notifications'], true );
			
			//require reconfirm
			if( empty( $custom_notif['require_confirm'] ) || $custom_notif['require_confirm'] !== 'true' ){ $req_conf = 'false'; } else { $req_conf = 'true'; }
			$options = array(
					'input_type'=>'checkbox',
					'input_value'=>$req_conf,
					'check_value'=>'true',
					'class'=>'nonce-ignore',
				);
			$html .= '<td id="new-require-confirm"><div class="form-group col-xs-12"><div class="input-group col-xs-12">' . createFormInput( 'notification-require_confirm', $options ) . '</div></div></td>';

			//add button
			$next_num = count( $custom_notif );
			$html .= '<td><span id="add-custom-notification" class="btn btn-primary" data-next-id="' . $next_num . '">Add Notification</span></td></tr>';


			//output the rows
			$i=1;
			if( !empty( $custom_notif ) ){
				foreach( $custom_notif as $notif ){

					//check for require confirm
					if( empty( $notif['req_conf'] ) || $notif['req_conf'] == 'false' ){
						$req_conf = 'false';
						$req_conf_f = 'NOT REQUIRED';
						$req_conf_class = 'success';
					} else {
						$req_conf = 'true';
						$req_conf_f = 'REQUIRED';
						$req_conf_class = 'danger';
					}

					//create the row
					$html .= '<tr><td class="text-center">' . $i . '</td>';
					$html .= '<td class="text-center">' . seconds_to_string( $notif['time'] ). ' Before</td>';
					$html .= '<td class="text-center">' . $msg_options[ $notif['msg']  ]. ' Message</td>';
					$html .= '<td class="text-center">Sent to ' . $recipient_options[ $notif['rec'] ] . '</td>';
					$html .= '<td class="text-center ' . $req_conf_class . '">' . $req_conf_f . '</td>';
					$html .= '<td><span class="btn btn-danger rmv-parent-notif" data-string="{\'time\':\'' . $notif['time'] . '\',\'msg\':\'' . $notif['msg'] . '\',\'rec\':\'' . $notif['rec'] . '\',\'req_conf\':\'' . $req_conf . '\'}">Remove</span></td></tr>';
					$i++;
				}
			}

			$html .= '</tbody></table><!--END NOTIFICATIONS TABLE-->';

			//hidden input fild that contains notifications json
			$html .= '<input type="hidden" id="custom-notifications-json" name="custom-notifications-json" value="' . str_replace('"',"'", $position_meta['custom_notifications'] ) . '">';
			$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';
		

			//end notification
			$html .= '</div><!--END NOTIFICATION-->';
			$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';
		}

		//submit button 
		$html .= '<input type="submit" name="submit-position" value="Save Position" class="button btn btn-success col-xs-10 col-xs-offset-1 col-md-4 col-md-offset-0">';

		//remove button
		if( $role_min > 1 ){ $rmv_status = 'disabled'; $title = "You may not remove a position once volunteers have already confirmed roles. You must have the position cancelled to insure that all volunteers are notified."; } else { $rmv_status = null; $title = 'Click to remove this position'; }
		$html .= '<div class="tooltips col-xs-10 col-xs-offset-1 col-md-4 col-md-offset-0" data-toggle="tooltip" data-placement="top" title="' . $title . '"><a href="' . _ROOT_ . '/manage-position/' . $_GET['function'] . '?rmv=true" id="remove-position" class="button btn btn-danger col-xs-12 trigger-confirm ' . $rmv_status . '" data-confirm-message="Are you sure you would like to remove this position? This will also remove all future messages / notifications." title="' . $title . '" >Remove Position</a></div>';

		//duplicate position button
		$html .= '<div class="col-xs-10 col-xs-offset-1 col-md-4 col-md-offset-0">';
		$html .= '	<span id="duplicate_position" class="btn btn-warning full-width vol-modal-trigger" data-title="Duplicate Position Options" data-save-button-text="Duplicate Position" data-toggle="modal" data-target="#volunteerModal" data-modal-body="duplicate-position-options-' . $position_id . '" data-save-button-color="btn-success" data-close-button-color="btn-danger no-text-shadow" data-close-button-text="Cancel Duplication">Save & Duplicate Position</span>';
		$html .= '	<input type="hidden" id="duplicate-position-trigger" name="duplicate-position-trigger" value="false">';
		

		//hidden duplcate position options for modal
		$html .= '	<div class="duplicate-position-options-' . $position_id . ' hidden">';
		$html .= '		<p class="bg-info md-padd">Please specify which options you would like to maintain in the dupliction process.</p>';
		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';

		//number of duplicates
		$options = array(
				'input_value'=>1,
				'option_name'=>'number-of-duplicates',
				'input_type'=>'number',
				'min_value'=>1,
				'max_value'=>100,
				'input_addon_start'=>'Number of Duplicates:',
				'on_change'=>'duplicatePositionFormField(\'number-of-duplicates\')',
			);
		$html .= '		<div class="form-group col-xs-12"><div class="input-group col-xs-12">' . createFormInput( 'number-of-duplicates', $options ) . '</div></div><p class="help-block">Select the number of duplicates you would like to create.</p><hr>';

		//check box options
		$options = array(
			'input_type'=>'checkbox',
			'input_value'=>'true',
			'check_value'=>'true',
			'label'=>'Duplicate Name',
			'no_form_control'=>true,
			'wrap_label'=>true,
			'label_class'=>'label-left',
			'on_change'=>'duplicatePositionFormField(\'omit-duplicate-notifications\')',
			);
		$html .= '		<div class="col-xs-12 col-md-6"><div class="checkbox">' . createFormInput( 'omit-duplicate-notifications', $options ) . '</div><p class="help-block">Uncheck this if you would like to exclude the notifications from the new event(s).</p><hr class="visible-xs-block visible-sm-block"></div>';

		$options = array(
			'input_type'=>'checkbox',
			'input_value'=>'true',
			'check_value'=>'true',
			'label'=>'Duplicate Lead',
			'no_form_control'=>true,
			'wrap_label'=>true,
			'label_class'=>'label-left',
			'on_change'=>'duplicatePositionFormField(\'omit-duplicate-lead\')',
			);
		$html .= '		<div class="col-xs-12 col-md-6"><div class="checkbox">' . createFormInput( 'omit-duplicate-lead', $options ) . '</div><p class="help-block">Unchecking this will result in you, the current user, being set as lead. To change this you will need to navigate to each of the newly created positions.</p><hr class="visible-xs-block visible-sm-block"></div><div class="clearfix"><hr class="visible-md-block visible-lg-block visible-xl-block"></div>';

		$options = array(
			'input_type'=>'checkbox',
			'input_value'=>'true',
			'check_value'=>'true',
			'label'=>'Duplicate Start Time',
			'no_form_control'=>true,
			'wrap_label'=>true,
			'label_class'=>'label-left',
			'on_change'=>'duplicatePositionFormField(\'omit-duplicate-start-time\')',
			);
		$html .= '		<div class="col-xs-12 col-md-6"><div class="checkbox">' . createFormInput( 'omit-duplicate-start-time', $options ) . '</div><p class="help-block">Unchecking this will cause the duplicated positions start time to be set the same date at 00:00 hours.</p><hr class="visible-xs-block visible-sm-block"></div>';

		$options = array(
			'input_type'=>'checkbox',
			'input_value'=>'true',
			'check_value'=>'true',
			'label'=>'Duplicate End Time',
			'no_form_control'=>true,
			'wrap_label'=>true,
			'label_class'=>'label-left',
			'on_change'=>'duplicatePositionFormField(\'omit-duplicate-end-time\')',
			);
		$html .= '		<div class="col-xs-12 col-md-6"><div class="checkbox">' . createFormInput( 'omit-duplicate-end-time', $options ) . '</div><p class="help-block">Unchecking this will cause the duplicated positions end time to be set the same date at 23:59 hours.</p><hr class="visible-xs-block visible-sm-block"></div><div class="clearfix"></div><hr class="visible-md-block visible-lg-block visible-xl-block">';

		$options = array(
			'input_type'=>'checkbox',
			'input_value'=>'true',
			'check_value'=>'true',
			'label'=>'Duplicate Number of Roles',
			'no_form_control'=>true,
			'wrap_label'=>true,
			'label_class'=>'label-left',
			'on_change'=>'duplicatePositionFormField(\'omit-duplicate-roles\')',
			);
		$html .= '		<div class="col-xs-12 col-md-6"><div class="checkbox">' . createFormInput( 'omit-duplicate-roles', $options ) . '</div><p class="help-block">Unchecking this will cause the duplicated position(s) number of roles to be set to 1</p><hr class="visible-xs-block visible-sm-block"></div>';

		$options = array(
			'input_type'=>'checkbox',
			'input_value'=>'true',
			'check_value'=>'true',
			'label'=>'Set to Inactive',
			'no_form_control'=>true,
			'wrap_label'=>true,
			'label_class'=>'label-left',
			'on_change'=>'duplicatePositionFormField(\'new-position-status-inactive\')',
			);
		$html .= '		<div class="col-xs-12 col-md-6"><div class="checkbox">' . createFormInput( 'new-position-status-inactive', $options ) . '</div><p class="help-block">Unchecking this will result in the position(s) being created with an "<code>INACTIVE</code>" status.</p><hr class="visible-xs-block visible-sm-block"></div>';

		//add final rule before option box
		$html .='<div class="clearfix"><hr class="visible-md-block visible-lg-block visible-xl-block"></div>';

		//select field options
		$options = array(
				'input_value'=>$position['event_id'],
				'option_name'=>'duplicate-to-event',
				'input_type'=>'select',
				'input_addon_start'=>'Duplicate Position to Event:',
				'options'=>$event_options,
				'check_value'=>$position['event_id'],
				'on_change'=>'duplicatePositionFormField(\'duplicate-to-event\')',
			);
		$html .= '		<div class="form-group col-xs-12"><div class="input-group col-xs-12">' . createFormInput( 'duplicate-to-event', $options ) . '</div></div><p class="help-block">You may select another event to send this position to if desired.</p></div><hr>';

		//close modal container
		$html .= '	</div>';

		//close input container
		$html .= '</div>';

		//clear and end form
		$html .= '<div class="clearfix"></div><br class="visible-md-block visible-lg-block">';
		$html .= '</form>';

		return $html;
	}

	private function selectPosition_ListView(){

		$html = '<div id="positions-list-primary">';

		//create an array of events
		$positions = get_narrowed_positions( $this->posQuery );

		if( !empty( $positions ) ){
			$html .= '<div class="table-responsive">';
			$html .= '<table class="table table-striped table-hover">';

			//start table heas
			$html .= '<tr>';
			$html .= '<th>#</th>';
			$html .= '<th>ID</th>';
			$html .= '<th>Name</th>';
			$html .= '<th>Event</th>';
			$html .= '<th>Role Info</th>';
			$html .= '<th>Date / Time</th>';
			$html .= '<th>Created By</th>';
			$html .= '<th>Controls</th>';
			$html .= '</tr>';
			$html .= '<tbody>';

			//loop through positions
			$i=1;
			
			foreach( $positions as $pos ){

				//set method defaults
				$status_class = 'default';

				//get the position meta
				$meta = get_position_meta_for_id( $pos['id'] );

				//check status
				if( $meta['status']['meta_value'] == 'inactive' ){
					$status_class = 'danger';
				} elseif( $meta['status']['meta_value'] == 'active' ){
					$status_class = 'success';
				} 

				//check for inactive position setting 
				if( $meta['status']['meta_value'] == 'active' || $this->position_narrows['show_inactive'] == '1' ){

					//created info
					$user = get_user( $pos['created_by'] );
					$created_html = $user['last_name'] . ', ' . $user['first_name'] . '<span>(' . date( 'm-d-Y H:i T', strtotime( $pos['created'] ) ) . ')</span>';

					//get the event
					$event = get_event( $pos['event_id'] );
					$event_name = $event['name'] . ' <span class="label label-default label-small">id:' . $event['id'] . '</span>';

					//set the friendly date values
					$date_friendly = date('l F jS, Y', $pos['date_start'] ) . '<br>' . date('h:i A', $pos['date_start'] ) . ' - ' . date('h:i A', $pos['date_end'] );

					//set the friendly role info
					$position_roles = get_position_roles( $pos['id'] );
					$total_roles = $position_roles;
					$confirmed_roles = get_position_role_status( $pos['id'], 'confirmed' );
					$unconfirmed_roles = get_position_role_status( $pos['id'], 'unconfirmed' );
					$unfilled_roles = $total_roles - $confirmed_roles;
					
					$role_friendly = '<span class="label label-info"><span class="text-info">Total: ' . $total_roles . '</span></span><br><span class="label label-success"><span class="text-success">Confirmed: ' . $confirmed_roles . '</span></span><br><span class="label label-warning"><span class="text-warning">Unconfirmed: ' . $unconfirmed_roles . '</span></span><br><span class="label label-danger"><span class="">Unfilled: ' . $unfilled_roles . '</span></span>';

					$html .= '<tr class="' . $status_class . '" id="position-list-' . $pos['id'] . '">';
					$html .= '<td class="position-list-num text-' . $status_class . '"><p>' . $i . '</p></td>';
					$html .= '<td class="position-list-id text-' . $status_class . '"><p>' . $pos['id'] . '</p></td>';
					$html .= '<td class="position-list-name text-' . $status_class . '"><p>' . $pos['name'] . ' - <span class="label label-' . $status_class . '">' . $meta['status']['meta_value'] . '</span></p></td>';
					$html .= '<td class="position-list-event text-' . $status_class . '"><p>' . $event_name . '</p></td>';
					$html .= '<td class="position-list-roles text-' . $status_class . '"><p>' . $date_friendly . '</p></td>';
					$html .= '<td class="position-list-date text-' . $status_class . '"><p>' . $role_friendly . '</p></td>';
					$html .= '<td class="position-list-create text-' . $status_class . '"><p>' . $created_html . '</p></td>';
					$html .= '<td class="position-list-control">
								<a class="col-xs-10 col-xs-offset-1 btn-nosmall btn btn-primary table-button" href="' . _ROOT_ . '/manage-position/' . $pos['id'] . '">Edit</a>';
					
					//check status
					if( $meta['status']['meta_value'] == 'active' ){
						$html .= '	<a class="col-xs-10 col-xs-offset-1 btn-nosmall btn btn-warning table-button" href="' . _ROOT_ . '/manage-position/' . $pos['id'] . '?status=inactive">Deactivate</a>';
					} else {
						$html .= '	<a class="col-xs-10 col-xs-offset-1 btn-nosmall btn btn-success table-button" href="' . _ROOT_ . '/manage-position/' . $pos['id'] . '?status=active">Activate</a>';
					}

					//close cell and row
					$html .= '</td></tr>';

					$i++;
				}
			}

			$html .= '</tbody>';
			$html .= '</table>';
			$html .= '</div><!-- CLOSE TABLE RESPONSIVE -->';
		} else {
			$html .= '<p class="h3">Your search returned no results</p>';
		}
		
		//close container
		$html .= '</div><!-- CLOSE PRIMARY -->';

		return $html;
	}
	private function selectPosition_CalView(){

		//instantiate calendar class
		$calendar = new calendar();
		$calendar->calType = 'admin';

		//get the calendar content with narrows
		$html = $calendar->get_calendar();

		return $html;

	}
	private function selectPosition_EventView(){

		$html = '<div id="positions-by-events-primary">';

		//create an array of events
		$events = get_events();

		foreach( $events as $event ){

			//check if event should be omitted
			if( $this->position_narrows['show_inactive_events'] == '1' ||  $event['status'] == 'active' ){

				//get and decode the narrows
				$narrows = json_decode( $_COOKIE['admin_position_narrow'], true );
				$narrows['event_id'] = $event['id'];

				//create the query from narrow selections
				$eventQuery = create_narrowed_query( $narrows );

				//get the positions with event information
				$positions = get_narrowed_positions( $eventQuery );

				//count positions, set label class for color coding, and the slide activator to prevent sliding on smaller screens when there are no positions
				if( $positions ){
					$position_count = count( $positions );
					$position_count_class = 'success';
					$panel_slide_activator = null;
				} else {
					$position_count = 0;
					$position_count_class = 'default';
					$panel_slide_activator = ' no-slide';
				}

				//check for position count setting (display events with no positions)
				if( $position_count > 0 || $this->position_narrows['show_events_no_position'] == '1' ){

					$html .= '
						<div class="panel panel-default' . $panel_slide_activator . '" id="' . $event['slug'] . '-position-group">
							<div class="panel-heading">
								<a data-toggle="collapse" data-parent="#' . $event['slug'] . '-position-group" href="#' . $event['slug'] . '-position-table-collapse">' . ucwords( $event['name'] ) . '</a><span class="label label-' . $position_count_class . ' marg-l">Event Positions: ' . $position_count . '</span>
							</div>
							<div class="panel-body panel-collapse collapse" id="' . $event['slug'] . '-position-table-collapse">';

					//if there are positions
					if( $positions ){

						$html .='
							<table id="pbe-table" class="table table-hover" style="border-collapse:collapse">
								<thead>
									<tr>
										<th id="pbe-table-head-count">
											<p>#</p>
										</th>
										<th id="pbe-table-head-id">
											<p>ID</p>
										</th>
										<th id="pbe-table-head-name">
											<p>Position Name</p>
										</th>
										<th id="pbe-table-head-roles">
											<p>Role Info</p>
										</th>
										<th id="pbe-table-head-date">
											<p>Position Date / Time</p>
										</th>
										<th id="pbe-table-head-crtd">
											<p>Created By</p>
										</th>
										<th id="pbe-table-head-control">
											<p>Action</p>
										</th>
									</tr>
								</thead>
								<tbody>';

						$i = 0;				
						foreach( $positions as $position ){

							//position status
							$position_status = get_position_meta_by_name( $position['id'], 'status');

							//check for inactive position setting 
							if( $position_status['meta_value'] == 'active' || $this->position_narrows['show_inactive'] == '1' ){

								$i++;								

								if( $position_status['meta_value'] == 'active' ){
									$text_color = 'success';
								} elseif( $position_status['meta_value'] == 'inactive' ){
									$text_color = 'danger';
								} else {
									$text_color = 'warning';
								}

								//created info
								$user = get_user( $position['created_by'] );
								$created_html = $user['last_name'] . ', ' . $user['first_name'] . '<span>(' . date( 'm-d-Y H:i T', strtotime( $position['created'] ) ) . ')</span>';

								//get the event
								$event = get_event( $position['event_id'] );
								$event_name = $event['name'] . ' <span class="label label-default label-small">id:' . $event['id'] . '</span>';

								//set the friendly date values
								$date_friendly = date('l F jS, Y', $position['date_start'] ) . '<br>' . date('h:i A', $position['date_start'] ) . ' - ' . date('h:i A', $position['date_end'] );
								$created_friendly = date('m/d/Y H:i', strtotime( $position['created'] ) );

								//set the friendly role info
								$position_roles = get_position_roles( $position['id'] );
								$total_roles = $position_roles;
								$confirmed_roles = get_position_role_status( $position['id'], 'confirmed' );
								$unconfirmed_roles = get_position_role_status( $position['id'], 'unconfirmed' );
								$unfilled_roles = $total_roles - $confirmed_roles - $unconfirmed_roles;
								
								$role_friendly = '<span class="label label-info"><span class="text-info">Total: ' . $total_roles . '</span></span><br><span class="label label-success"><span class="text-success">Confirmed: ' . $confirmed_roles . '</span></span><br><span class="label label-warning"><span class="text-warning">Unconfirmed: ' . $unconfirmed_roles . '</span></span><br><span class="label label-danger"><span class="">Unfilled: ' . $unfilled_roles . '</span></span>';

								$html .= '
										<tr class="' . $text_color . '">
											<td class="pbe-table-count"><p class="text-' . $text_color . '">' . $i . '</p></td>
											<td class="pbe-table-id"><p class="text-' . $text_color . '">' . $position['id'] . '</p></td>
											<td class="pbe-table-name"><p class="text-' . $text_color . '">' . $position['name'] . ' - <span class="label label-' . $text_color . '">' . $position_status['meta_value'] . '</span></p></td>
											<td class="pbe-table-roles">' . $role_friendly . '</td>
											<td class="pbe-table-date"><p class="text-' . $text_color . '">' . $date_friendly . '</p></td>
											<td class="pbe-table-crtd"><p class="text-' . $text_color . '">' . $created_html . '</p></td>
											<td class="pbe-table-control">
													<a class="col-xs-10 col-xs-offset-1 btn-nosmall btn btn-primary table-button" href="' . _ROOT_ . '/manage-position/' . $position['id'] . '">Edit</a>';

								//check status
								if( $position_status['meta_value'] == 'active' ){
									$html .= '		<a class="col-xs-10 col-xs-offset-1 btn-nosmall btn btn-warning table-button" href="' . _ROOT_ . '/manage-position/' . $position['id'] . '?status=inactive">Deactivate</a>';
								} else {
									$html .= '		<a class="col-xs-10 col-xs-offset-1 btn-nosmall btn btn-success table-button" href="' . _ROOT_ . '/manage-position/' . $position['id'] . '?status=active">Activate</a>';
								}
								
								//close cell and row
								$html .='	</td>
										</tr>';
							}
						}

						//close the table
						$html .= '</tbody>
							</table>';

					} else {
						$html .= '<p>No Current Positions for this event</p>';
					}

					//close the table and container
					$html .= '
						</div>
					</div><!--CLOSE position-group -->';
				}
			}
		}

		$html .= '</div><!--CLOSE PRIMARY-->';

		return $html;

	}

}
?>