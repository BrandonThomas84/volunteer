<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-20 23:11:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-08 23:23:42
 */

class manage_event {

	public function __construct(){

		/**
		INCLUDE THE EVENT FUNCTIONS
		*/
		require_once( __FUNCTION_INCLUDE__ . 'event_functions.php');
		/**
		INCLUDE THE USER FUNCTIONS
		*/
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');

		$this->title = 'Manage Events';
		$this->subtitle = 'Manage your events and event positions from here';
		$this->pageMessage = null;

		//page meta and breadcrumb information
		$this->pageMeta[0] = array(
			'name' => 'Manage Events',
			'url' => '/manage-event',
			'meta_title' => 'Manage Volunteer Events',
			'meta_description' => 'View all yur active and inactive events from here. You can adjust the options of each including changing the name and the active status.',
		);

		//check if event slug is supplied and add to meta information if so
		if(isset( $_GET['function'] ) ){

			//get the event info
			$event = get_event_by_slug( $_GET['function'] );

			//change the page title
			$this->title = $event['name'];
			$this->subtitle = 'Manage event settings for ' . $event['name'];

			//page meta and breadcrumb information
			$this->pageMeta[1] = array(
				'name' => $event['name'],
				'url' => $this->pageMeta[0]['url'] . '/' . $event['slug'],
				'meta_title' => $event['name'] . ' - Edit Screen',
				'meta_description' => 'Manage and change settings for the ' . $event['name'] . ' event.',
			);	
		}

		//check if role ID is supplied and add to meta information if so
		if(isset( $_GET['role_id'] ) ){

			//get the event info
			$event = get_event_by_slug( $_GET['function'] );

			//get role info
			$role = get_event_roles( $event['id'] );
			$role = $role[ $_GET['role_id'] ];

			//change the page title
			$this->title = $role['name'];
			$this->subtitle = 'Manage event role ';

			//page meta and breadcrumb information
			$this->pageMeta[2] = array(
				'name' => $role['name'],
				'url' => $this->pageMeta[1]['url'] . '/?role_id=' . $_GET['role_id'],
				'meta_title' => $role['name'] . ' - Role Edit Screen',
				'meta_description' => 'Manage and change settings for the ' . $event['name'] . ' role.',
			);	
		}
	}

	public function checkSubmission(){
		//check for remove
		if( isset( $_GET['del'] ) ){

			//get the ID
			$event = get_event_by_slug( $_GET['function'] );
			$event_id = $event['id'];
			remove_event($event_id);
			submission_redirect( $this->pageMeta[0]['url'] );
		}

		//check for new event creation
		if( isset( $_POST['new_event'] ) ){

			if( $_POST['event_name'] == '' ){
				add_page_message('danger','You must specifiy a name for the event.','Missing Event Name');
				submission_redirect('/manage-event/');
			} else {
				$name = $_POST['event_name'];
				$status = 'created';
				$message = create_new_event($name, $status);

				//get the new event by name
				$event = get_event_by_name( $name );
				$id = $event[0]['id'];

				//set created date
				update_event_meta( $id, 'created', date('U') );

				//set created by
				update_event_meta( $id, 'created_by', $_COOKIE['usrID'] );

				//set the page message
				foreach( $message as $name => $msg ){
					$this->pageMessage[$name] = $msg;
				}
			}
			
		}

		//check for options update
		if( isset( $_POST['event-update'] ) ){

			//check if event status was changed
			if( $_POST['event-status-change'] == 'true' ){

				//check if active or inactive
				if( isset( $_POST['event-status'] ) ){
					$newStatus = 'active';
				} else {
					$newStatus = 'created';
				}

				//check for open enrollment
				if( isset( $_POST['event-open-enrollment'] ) ){
					$open_enrollment_update = 'ENABLED';
					update_event_meta( $_POST['event-id'], 'open_enrollment', 'on' );
				} else {
					$open_enrollment_update = 'DISBALED';
					update_event_meta( $_POST['event-id'], 'open_enrollment', 'off' );
				}

				//check for no email allowance
				if( isset( $_POST['event-allow-no-email'] ) ){
					$open_enrollment_update = 'ENABLED';
					update_event_meta( $_POST['event-id'], 'allow_no_email', 'on' );
				} else {
					$open_enrollment_update = 'DISBALED';
					update_event_meta( $_POST['event-id'], 'allow_no_email', 'off' );
				}

				//check for start date
				if( isset( $_POST['event-start-date'] ) ){
					$start_date_update = strtotime( $_POST['event-start-date'] );
					update_event_meta( $_POST['event-id'], 'start_date', $start_date_update );
				} 

				//check for end date
				if( isset( $_POST['event-end-date'] ) ){
					$end_date_update = strtotime( $_POST['event-end-date'] );
					update_event_meta( $_POST['event-id'], 'end_date', $end_date_update );
				} 

				
				
				//run update on event
				$update = update_event( $newStatus, $_POST['event-name'], $_POST['event-id'] );

				//check if update succeded
				if( $update ){

					//check for meta updates
					$hist = get_event_meta( $_POST['event-id'], 'event_updates');

					//check for existing values
					if( !empty( $hist ) ){

						//convert updates to array
						$meta_val = unserialize( $hist['meta_value'] );
					} 

					//add new meta info
					$meta_val[date('U')] = 'EVENT EDIT: name: "' . $_POST['event-name'] . '" | status: "' . $newStatus . '" | updated by: ' . $_COOKIE['usrID'] . '" | open enrollment: "' . $open_enrollment_update;

					//serialize values
					$meta_val = serialize( $meta_val );

					//update meta value
					$metaUpdate = update_event_meta($_POST['event-id'], 'event_updates', $meta_val);

					//return message
					add_page_message('success','You have successfully updated the options for this event. Please review your changes below.','Successfully Updated Event Options');
				} else {

					//ad page message
					add_page_message('danger','There was a database error while trying to update your event settings.','Error While Updating');
				}

				//redirect page
				submission_redirect( '/manage-event/' . $_GET['function'] );
			}
		}
	}

	public function display(){

		//verify license
		$verify = verifyLicense();
		
		$html = '';

		//check if event slug is supplied
		if(isset( $_GET['function'] ) ){

			//get event information
			$event = get_event_by_slug( $_GET['function'] );

			//check for role 
			if( isset( $_GET['role_id'] ) ){
				$html .= '';
			} else {
				//output basic event controls
				$html .= $this->eventDetails( $event );
			}

		} else {

			//output the select event page if no event slug has been supplied
			$html .= $this->selectEvent();
		}

		

		return $html;
	}

	private function eventDetails( $event ){

		//set base variables
		$id = $event['id'];
		$name = $event['name'];
		$slug = $event['slug'];
		$status = $event['status'];

		//open enrollment meta
		$open_enrollment = get_event_meta( $id, 'open_enrollment');
		$open_enrollment = $open_enrollment['meta_value'];

		//phone only meta
		$allow_no_email = get_event_meta( $id, 'allow_no_email');
		$allow_no_email = $allow_no_email['meta_value'];

		//start date meta
		$start_date = get_event_meta( $id, 'start_date');
		$start_date = date('Y-m-d', $start_date['meta_value'] );

		//end date meta
		$end_date = get_event_meta( $id, 'end_date');
		$end_date = date('Y-m-d', $end_date['meta_value'] );

		//create group end string
		$end_group 	= '</div><!--END GROUP-->';

		//created info variables
		$created = get_event_meta( $id, 'created' );
		$created = date('m/d/Y', $created['meta_value'] );
		$creator = get_event_meta( $id, 'created_by' );
		$createdBy = get_user( $creator['meta_value'] );


		//created information
		$html = '<div id="manage-event-primary" class="">';
		$html .= '	<h2 class="h3 bg-info text-info" style="margin-top: 5px !important;">General Information</h2>';

		$html .= '	<div class="row event-base-info" style="margin: 0 20px;">';
		$html .= '		<div class="col-xs-4 text-center text-info bg-info"><strong>Event ID:</strong> <small>' . $id . '</small></div>';
		$html .= '		<div class="col-xs-4 text-center text-info bg-info"><strong>Created:</strong> <small>' . $created . '</small></div>';
		$html .= '		<div class="col-xs-4 text-center text-info bg-info"><strong>Created By:</strong> <small>' . $createdBy['first_name'] . ' ' . $createdBy['last_name'] . '</small></div>';
		$html .= '	</div><hr>';

		//start form
		$html .= '	<form id="edit-event-form" class="modify-form-controller form-horizontal form-has-nonce" data-controller-id="event-status-change" name="edit-event-form" method="post" enctype="multipart/form-data" role="form">';
		$html .= '		<input type="hidden" name="event-update" value="true">';
		$html .= '		<input type="hidden" name="event-id" value="' . $id . '">';
		$html .= '		<input type="hidden" id="event-status-change" name="event-status-change" value="false">';

		/**
		EVENT NAME
		*/
		$html .= '		<div class="form-group col-sm-12">';
		$html .= '			<label class="col-sm-5 col-md-2 control-label">Event Name:</label>';
		$html .= '			<div class="col-sm-6 col-md-10">';
		$html .= '				<input type="text" name="event-name" placeholder="Event Name" value="' . $name . '" required="required" class="form-control">';
		$html .= '			</div>';
		$html .= '		</div>';

		/**
		EVENT START
		*/
		$html .= '		<div class="form-group col-sm-6">';
		$html .= '			<label class="col-sm-5 col-md-2 control-label">Event Start Date:</label>';
		$html .= '			<div class="col-sm-6 col-md-10">';
		$html .= '				<input type="date" name="event-start-date" value="' . $start_date . '" required="required" class="form-control">';
		$html .= '			</div>';
		$html .= '		</div>';

		/**
		EVENT END
		*/
		$html .= '		<div class="form-group col-sm-6">';
		$html .= '			<label class="col-sm-5 col-md-2 control-label">Event End Date:</label>';
		$html .= '			<div class="col-sm-6 col-md-10">';
		$html .= '				<input type="date" name="event-end-date" value="' . $end_date . '" required="required" class="form-control">';
		$html .= '			</div>';
		$html .= '		</div>';

		/**
		STATUS CHECKBOX
		*/
		$html .= '			<div class="form-group col-sm-12 col-md-4">';
		$html .= '				<label class="col-sm-5 col-md-4 control-label">Event Status:</label>';
		$html .= '				<div class="col-sm-7 col-md-8">';
		$html .= '					<div class="onoffswitch">';
    	$html .= '						<input type="checkbox" name="event-status" class="onoffswitch-checkbox" id="myonoffswitch" ' . returnChecked($status,'active') . '>';
    	$html .= '						<label class="onoffswitch-label" for="myonoffswitch">';
        $html .= '							<span class="onoffswitch-inner"></span>';
        $html .= '							<span class="onoffswitch-switch"></span>';
    	$html .= '						</label>';
    	$html .= '					</div><p class="help">Active events will be shown on the frontend and allow sign ups for open positions.</p>';
		$html .= '				</div>';
		$html .= '			</div>';

		/**
		OPEN ENROLLMENT
		*/
		$html .= '			<div class="form-group col-sm-12 col-md-4">';
		$html .= '				<label class="col-sm-5 col-md-4 control-label">Open Enrollment:</label>';
		$html .= '				<div class="col-sm-7 col-md-8">';
		$html .= '					<div class="onoffswitch">';
    	$html .= '						<input type="checkbox" name="event-open-enrollment" class="onoffswitch-checkbox" id="openEnrollmentSwitch" ' . returnChecked($open_enrollment,'on') . '>';
    	$html .= '						<label class="onoffswitch-label" for="openEnrollmentSwitch">';
        $html .= '							<span class="onoffswitch-inner"></span>';
        $html .= '							<span class="onoffswitch-switch"></span>';
    	$html .= '						</label>';
    	$html .= '					</div><p class="help">This allows users to request to be added to a list of floating volunteers that can be added to individual positions later.</p>';
		$html .= '				</div>';
		$html .= '			</div>';

		/**
		ALLOW REGISTRATION WITH ONLY PHONE NUMBER
		*/
		$html .= '			<div class="form-group col-sm-12 col-md-4">';
		$html .= '				<label class="col-sm-5 col-md-4 control-label">No Email Registration:</label>';
		$html .= '				<div class="col-sm-7 col-md-8">';
		$html .= '					<div class="onoffswitch">';
    	$html .= '						<input type="checkbox" name="event-allow-no-email" class="onoffswitch-checkbox" id="noEmailRegSwitch" ' . returnChecked($allow_no_email,'on') . '>';
    	$html .= '						<label class="onoffswitch-label" for="noEmailRegSwitch">';
        $html .= '							<span class="onoffswitch-inner"></span>';
        $html .= '							<span class="onoffswitch-switch"></span>';
    	$html .= '						</label>';
    	$html .= '					</div><p class="help">This allows users to sign up without an email address listed on their account. They will be required to submit a phone number instead.</p>';
		$html .= '				</div>';
		$html .= '			</div>';

		$html .='			<div class="clearfix"></div>';

		/**
		EVENT ROLE CREATION
		*/

		//role title and help text
		$html .= '		<h2 class="h3 bg-info text-info">Event Roles</h2>';
		$html .= '		<p class="help-block">This is where individual responsibility roles can be created for a given event.</p><p class="help-block">Roles should be used to give permissions to users for the duration of the event (unless otherwise indicated). When the event has ended or the role completion date / time is reached the permissions are revoked.</p>';

		//role container
		$html .= '	<div class="col-sm-12 no-padd" id="event-role-management">';

		//get the roles
		$roles = get_event_roles( $id );

		$html .= $this->get_event_role_table( $roles );

		//column / row shift
		$html .= '	</div><!--CLOSE COLUMN-->';
		$html .= '	<div class="clearfix"></div>';

		/**
		HISTORY
		*/
		//start update history box
		$html .= '		<h2 class="h3 bg-info text-info">Update History</h2>';
		$html .= '<div id="update-hitory" class="panel panel-default">';
		$html .= '	<div class="panel-heading"><a data-toggle="collapse" data-parent="#update-hitory" href="#update-hstory-collapase"><strong>Click to View Update History</strong></a></div>';
  		$html .= '		<div id="update-hstory-collapase" class="panel-body panel-collapse collapse">';
		$html .= '			<ul class="list-group">';

		//get update history
		$hist = get_event_meta( $id, 'event_updates');

		if( $hist ){

			//convert updates to array
			$hist = unserialize( $hist['meta_value'] );

			//show most recent first
			$hist = array_reverse($hist, true);

			//loop through update history
			foreach( $hist as $date => $upd ){
				$html .= '<li class="list-group-item"><span class="badge">' . date('m/d/Y H:i:s', $date ) . '</span>' . $upd . '</li>';
			}
		} else {
			$html .= '<li class="list-group-item">No Update History</li>';
		}
		$html .= '		</ul>';

		//close the panel body
		$html .= '	</div><!-- CLOSE PANEL BODY-->';
		//close primary container
		$html .= '</div><!--CLOSE PRIMARY -->';


		/**
		SUBMIT BUTTON
		*/
		$html .= '		<div class="form-group text-center" style="margin-top:30px;">';
		$html .= '			<input type="submit" class="btn btn-success col-xs-10 col-xs-offset-1" value="Save Changes">';
		$html .= '		</div><div class="clearfix"></div>';

		//close form
		$html .= '	</form><!--END EVENT FORM-->';

		return $html;
	}

	private function selectEvent(){
		//active event listings
		$html = '<h3>Active Events</h3>';
		$events = get_events_by_status('active');
		if( !empty( $events ) ){

			$html .= $this->selectEventTable('Change Active Event Settings',$events);
			
		}else{
			$html .= '<p>There are currently no active events. Try <a href="#" class="duplicateNavItem" data-nav-id="nav-new-event"> creating one here</a>.</p>';
		}

		//created event listings
		$html .= '<h3>Created Events</h3>';
		$events = get_events_by_status('created');
		if( !empty( $events ) ){

			//get html events table
			$html .= $this->selectEventTable('Change Inactive Event Options',$events);
			
		}else{
			$html .= '<p>There are currently no inactive events. Try <a href="#" class="duplicateNavItem" data-nav-id="nav-new-event"> creating one here</a>.</p>';
		}

		return $html;
	}

	private function selectEventTable($title,$events){

		//construct html
		$html = '<div class="panel panel-default">';
		$html .= '<div class="panel-heading">' . $title . '</div>';
		$html .= '<div class="panel-body">';
		$html .= '<table class="table table-striped table-hover table-responsive">';

		//table header
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th><p>#</p></td>';
		$html .= '<th><p>Event Name</p></td>';
		$html .= '<th><p>Event Status</p></td>';
		$html .= '<th><p>Event Controls</p></td>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		//loop through events
		$i = 1;
		foreach( $events as $event ){

			$html .= '<tr>';
			$html .= '<td><p>' . $i	 . '</p></td>';
			$html .= '<td><p>' . $event['name']	 . '</p></td>';
			$html .= '<td><p>' . $event['status']	 . '</p></td>';
			$html .= '<td class="row">';
			$html .= '<a class="col-md-5 col-xs-12 btn btn-primary" href="' . _ROOT_ . '/manage-event/' . $event['slug'] . '">Options</a>';
			$html .= '<a class="col-md-5 col-md-offset-1 col-xs-12 btn btn-danger trigger-confirm" data-confirm-message="Are you sure you want to remove this event? You will be unable to undo this change." href="' . _ROOT_ . '/manage-event/' . $event['slug'] . '?del=true">Remove Event</a>';
			$html .= '</td>';
			$html .= '</tr>';

			$i++;
		}

		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	protected function get_event_role_table( $roles ){
		//start role table
		$html = '<table class="table table-striped">';

		//table headers
		$html .= '<tr><th>ID</th><th>Role Name</th><th>Role End</th><th>Assignee</th><th>Modify Role</th></tr>';

		//if there are roles
		if( !empty( $roles ) ){
			foreach ($roles as $key => $role) {

				//check if assigned to dictate color
				$row_class = 'danger';
				if( !empty( $role['uid'] ) ){
					$row_class = 'success';
				}

				//start role row
				$html .= '<tr class="' . $row_class . '">';

				//role id
				$html .= '<td>' . $role['id'] . '</td>';
				
				//role name
				$html .= '<td>' . $role['name'] . '</td>';
				
				//role end
				$html .= '<td>' . date('m/d/Y', $role['end']) . '</td>';

				//get the user name
				$user = get_user( $role['uid'] );
				$user = $user['first_name'] . ' ' . substr( $user['last_name'], 0,1);

				//role assignee
				$html .= '<td>' . $user . '</td>';

				//role assignee
				$html .= '<td><a class="btn btn-primary form-control" href="' . _ROOT_ . '/manage-event/' . $_GET['function'] . '?role_id=' . $role['id'] . '" onclick="verifySaveOnChange(this,\'event-status-change\')">Manage Role</a></td>';

				//close role row
				$html .= '</tr>';
			}
		} else {
			$html .= '<tr><td colspan="5"><p style="text-align:center;">NO ROLES HAVE BEEN CREATED</p></td></tr>';
		}

		//create new role link
		$html .= '<tr><td colspan="5"><a class="btn btn-warning col-xs-8 col-xs-offset-2" href="' . _ROOT_ . '/manage-event/' . $_GET['function'] . '?role_id=new" onclick="verifySaveOnChange">Create New Role</a></td></tr>';

		//end role table
		$html .= '</table>';

		return $html;
	}
}
?>