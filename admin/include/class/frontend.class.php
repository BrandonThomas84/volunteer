<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-09-23 22:19:47
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-11-11 10:52:08
 */

/**
CONTROLS FUNCTIONS FOR THE FRONT END USER
*/
class frontend {
	public function __construct(){

		//get functions
		require_once( _FE_INCLUDE_PATH_ . 'functions' . _DIRSEP_ . 'volunteer_functions.php' );
		require_once( _FE_INCLUDE_PATH_ . 'functions' . _DIRSEP_ . 'user_functions.php' );
		require_once( _FE_INCLUDE_PATH_ . 'functions' . _DIRSEP_ . 'position_functions.php' );
		require_once( _FE_INCLUDE_PATH_ . 'functions' . _DIRSEP_ . 'event_functions.php');

		//set constants
		set_database_constants();

		//set object scope position narrows default
		$this->position_narrows = get_position_narrow_defaults();

		//get the positions
		$this->positions = get_volunteers(null,' WHERE `user_id` = 0');
	}

	public function check_signup(){

		//verify that user is signed in
		if( isset( $_COOKIE['usrID'] ) ){
			
			//check for volunteer submission
			if( isset( $_GET['signup'] ) && $_GET['signup'] == 'true' ){

				//set base vars
				$position_id = $_GET['pos_id'];
				$user_id = $_GET['user_id'];

				$this->set_volunteer( $position_id, $user_id );

				//set return location
				$location = create_return_url( _FRONTEND_URL_ );
				submission_redirect( _PROTOCOL_ . $location, 'frontend' );
			}

			//check for floating addition
			if( isset( $_GET['floating_volunteer'] ) && $_GET['floating_volunteer'] ='true'){

				//set variables
				$event_id = $_GET['event_id'];
				$user_id = $_COOKIE['usrID'];

				//check for existing floating
				$floating = get_user_meta( $user_id, 'event_open_enrollment' );
				if( $floating ){
					$floating = $floating['meta_value'];
					$floating = json_decode( $floating, TRUE );
				} else {
					$floating = array();
				}

				//check if user is already a floating volunteer and issue a warning
				if( in_array( $event_id, $floating ) ){
					add_page_message( 'warning', 'It looks like you\'ve already been assigned as a floating volunteer for this event.' , 'Whoops' );
				} else {
					//add new event to the floating array annd remove duplicates
					$floating[] = $event_id;
					$floating = array_unique( $floating, SORT_STRING );

					//reencode array
					$floating = json_encode( $floating );

					//update the user meta value
					update_user_meta( $user_id, 'event_open_enrollment', $floating );

					add_page_message( 'success', 'You have been added as a floating volunteer for this event. We will notify you once you have been assigned to a position.' , 'Success' );
				}

				//set return location
				$location = create_return_url( _FRONTEND_URL_ );
				submission_redirect( _PROTOCOL_ . $location, 'frontend' );
			}
		}
	}

	private function set_volunteer( $position_id, $user_id ){

		//get the first available role for the position
		$role = get_volunteers(null,' WHERE `user_id` = 0 AND `position_id` = ' . $position_id );
		
		//verify there is an available spot
		if( !empty( $role ) ){
			//assign the volunteer
			assign_user_to_volunteer( $role[0]['volunteer_id'], $user_id);
		}
	}
/**
SET POSITION NARROW BYS 
*/
	public function set_position_narrows(){

		//update th narrow cookie
		$this->updateNarrowCookie();

		//vars fpr the position controls
		$view_type='calendar';
		$enabled_narrows=0;
		$class=null;
		$style=null;

		//return the position controls
		return positionControls( 'frontend', $view_type, $this->position_narrows, $enabled_narrows, $class, $style );
	}
/**
UPDATE THE JSON VALUE HELD BY THE NARROWING
*/
	private function updateNarrowCookie(){	
	
		//change the object scope properties
		if( isset( $_POST['update_narrow'] ) && $_POST['update_narrow'] == 'true' ){

			$this->position_narrows['status'] = ( isset( $_POST['settings-position-status-narrow'] ) ? $_POST['settings-position-status-narrow'] : null );
			$this->position_narrows['search_date_start'] = ( isset( $_POST['settings-position-date-start-narrow'] ) ? $_POST['settings-position-date-start-narrow'] : null );
			$this->position_narrows['search_time_start'] = ( isset( $_POST['settings-position-time-start-narrow'] ) ? $_POST['settings-position-time-start-narrow'] : null );
			$this->position_narrows['search_date_end'] = ( isset( $_POST['settings-position-date-end-narrow'] ) ? $_POST['settings-position-date-end-narrow'] : null );
			$this->position_narrows['search_time_end'] = ( isset( $_POST['settings-position-time-end-narrow'] ) ? $_POST['settings-position-time-end-narrow'] : null );
			$this->position_narrows['event_id'] = ( isset( $_POST['settings-position-events-narrow'] ) ? $_POST['settings-position-events-narrow'] : null );
			$this->position_narrows['name'] = ( isset( $_POST['settings-position-name-narrow'] ) ? $_POST['settings-position-name-narrow'] : null );
			$this->position_narrows['location'] = ( isset( $_POST['settings-position-location-narrow'] ) ? $_POST['settings-position-location-narrow'] : null );
			$this->position_narrows['description'] = ( isset( $_POST['settings-position-description-narrow'] ) ? $_POST['settings-position-description-narrow'] : null );

			//cookie value as all the updated posts (should include previous cookie values that have not been overwritten)
			$cookieVal = json_encode( $this->position_narrows );

			//indicator for determining whether to use question mark or ampersand
			$first_get = true;

			//array of gets to check for
			$gets = array('y','m');

			//start of destination url
			$location = _PROTOCOL_ . _FRONTEND_URL_ . '/';
			$location = create_return_url( $location );
			
			//set redirect directive			
			submission_redirect( $location, 'frontend' );

		} else {

			//check for previous existence
			if( isset( $_COOKIE['fe_position_narrow'] ) ){

				//set cookie value to existing cookie value
				$cookieVal = $_COOKIE['fe_position_narrow'];

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
		setcookie( 'fe_position_narrow',$cookieVal,0,'/');

		//count of enabled narrow bys
		$this->enabled_narrows = 0;
		foreach( $this->position_narrows as $key=>$value ){
			//check if a value should be counted
			if( !empty( $value ) ){
				$this->enabled_narrows++;
			}				
		}

	}

	
}

?>