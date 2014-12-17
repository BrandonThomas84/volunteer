<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-20 23:11:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-08 00:10:11
 */

class manage_volunteer {

	public function __construct(){

		require_once( __FUNCTION_INCLUDE__ . 'volunteer_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');

		$this->title = 'Manage Volunteers';
		$this->subtitle = '';
		$this->pageMessage = null;

		//page meta and breadcrumb information
		$this->pageMeta[0] = array(
			'name' => 'Manage Volunteers',
			'url' => '/manage-volunteer',
			'meta_title' => 'VMS Manage Volunteers',
			'meta_description' => '',
		);		

		//overwrite class vars if volunteer id is set
		if( isset( $_GET['function'] ) ) {

			//set page attributes
			if( $_GET['function'] == 'new' ){
				$name = 'Create New Volunteer';
				$url = '/manage-volunteer/new';
				$title = 'Create a new VMS volunteer';
				$description = '';

			} else {

				//get user info
				$user = get_user( $_GET['function'] );
				$name = 'Manage Volunteer: ' . $user['first_name'] . ' ' . $user['last_name'];
				$url = '/manage-volunteer/' . $user['id'];
				$title = 'Volunteer Options: ' . $user['first_name'] . ' ' . $user['last_name'];
				$description = 'Manage volunteer options for ' . $user['first_name'] . ' ' . $user['last_name'];
			}

			//set the page title
			$this->title = $name;

			//set page attributes for breadcrumbs and meta
			$this->pageMeta[1] = array(
				'name' => $name,
				'url' => $url,
				'meta_title' => $title,
				'meta_description' => $description,
			);
		}
	}

	public function checkSubmission(){

		//check for update nonce
		if( isset( $_POST['volunteer_update'] ) && $_POST['volunteer_update'] == 'true' ){

			//if the passwords match
			if( $_POST['settings-password'] == $_POST['settings-password_repeat'] ){

				//set the gender
				if( !empty( $_POST['settings-gender'] ) ){
					$gender = $_POST['settings-gender'];
				} else {
					$gender = 'unknown';
				}

				//check if new
				if( $_POST['volunteer_id'] == 'new' ){

					$user_id = createNewVolunteerUser( $_POST['settings-email'], $_POST['settings-first_name'], $_POST['settings-last_name'], $_POST['settings-password'], $gender );

				} else {

					$meta_values = array('gender'=>$gender);
					$user_id = updateVolunteerUser( $_POST['volunteer_id'], $_POST['settings-email'], $_POST['settings-first_name'], $_POST['settings-last_name'], $_POST['settings-password'], $meta_values );

					var_dump( $user_id );
				}
			}

			submission_redirect( '/manage-volunteer/' . $user_id);
		}

	}

	public function display(){

		//verify license
		$verify = verifyLicense();
		$html = '';

		//if volunteer id is set
		if( isset( $_GET['function'] ) ){
			$html .= $this->volunteer_configuration( $_GET['function'] );
		} else {
			$html .= $this->display_volunteer_table();
		}

		return $html;
	}

	private function display_volunteer_table(){
		$html = '<div id="manage-volunteers-primary">';
		//statement about users
		$html .= '<p class="help-block">This area displays users that are currently signed up for volunteer positions. If you are unable to locate the person you are looking for they may not be associated with a position yet in which case you must use the manage users section.</p>';

		//array of volunteer types
		$volunteer_id = get_volunteers(null,' WHERE `user_id` > 0');
		$volunteers = array();
		foreach( $volunteer_id as $vol ){
			$volunteers[] = get_volunteer_info( $vol['user_id'] );
		}

		//count volunteer types for badge
		$usr_count = count( $volunteer_id );
		if( $volunteers && $usr_count > 0 ){
			$label_type = 'success';
		} else {
			$usr_count = 0;
			$label_type = 'default';
		}


		$html .= '	<table class="table table-striped table-hover table-responsive volunteers-display-table">';
		$html .= '		<thead>';
		$html .= '			<tr>';
		$html .= '				<th class="volunteers-table-head-id"><p>#</p></th>';
		$html .= '				<th class="volunteers-table-head-fst"><p>First</p></th>';
		$html .= '				<th class="volunteers-table-head-lst"><p>Last</p></th>';
		$html .= '				<th class="volunteers-table-head-eml"><p>Email</p></th>';
		$html .= '				<th class="volunteers-table-head-dt"><p>Created</p></th>';
		$html .= '				<th class="volunteers-table-head-cont"><p>Controls</p></th>';
		$html .= '			</tr>';
		$html .= '		</thead>';
		$html .= '		<tbody>';

			

		//check for results
		if( !empty( $volunteers ) && $volunteers !== false ){
			
			//counter 
			$i = 1;
			
			//loop through volunteers
			foreach( $volunteers as $volunteer ){

				$html .= '	<tr>';
				$html .= '		<td><p>' . $i . '</p></td>';
				$html .= '		<td><p>' . $volunteer['first_name'] . '</p></td>';
				$html .= '		<td><p>' . $volunteer['last_name'] . '</p></td>';
				$html .= '		<td><p>' . $volunteer['email'] . '</p></td>';
				$html .= '		<td><p>' . date('m/d/Y H:i:s', strtotime( $volunteer['created'] ) ) . '</p></td>';
				$html .= '		<td class="row"><a class="btn btn-primary" href="' . _ROOT_ . '/manage-volunteer/' . $volunteer['u_id'] . '">Configure</a></td>';
				$html .= '	</tr>';

				$i++;
			} 
		} else {
			$html .= '	<tr>';
			$html .= '		<td colspan="6"><p>No volunteers</p></td>';
			$html .= '	</tr>';
		}

		//close table
		$html .= '	</tbody>';
		$html .= '</table>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="col-xs-10 col-xs-offset-1 col-md-6 col-md-offset-3"><a class="duplicateNavItem tooltips col-xs-12 btn btn-primary" href="javascript:void(0)" title="Create a New Volunteer Account" data-nav-id="nav-new-volunteer">Create New Volunteer</a></div><div class="clearfix"></div>';

		$html .= '</div><!-- CLOSE PRIMARY-->';

		//new volunteer form
		$html .= '<div id="new-volunteer-form" class="hidden">';		
		//add create new volunteer form with required volunteertype field
		//$html .= login::createNewvolunteerForm(true);
		$html .= '</div>';

		return $html;
	}

	private function volunteer_configuration( $volunteer_id ){

		//get the urser information
		if( $volunteer_id == 'new' ) {
			$volunteer['email'] = $_POST['settings-email'];
			$volunteer['first_name'] = $_POST['settings-first_name'];
			$volunteer['last_name'] = $_POST['settings-last_name'];
			$volunteer['gender'] = 'unknown';
		} else {
			$volunteer = get_user( $volunteer_id );

			//get gender
			$gender = get_user_meta( $volunteer_id, 'gender');
			$volunteer['gender'] = $gender['meta_value'];
		}
		

		//start ouput
		$html = '<form id="volunteer-settings" class="form-inline" role="form" data-controller-id="volunteer_update" method="post">';

		//form nonce
		$html .= '<input type="hidden" name="volunteer_id" value="' . $volunteer_id . '">';
		$html .= '<input type="hidden" id="volunteer_update" name="volunteer_update" value="false">';

		//section header
		$html .= '<h2 class="h3 bg-info text-info">Volunteer Information</h2>';

		//email field
		$options = array(
				'input_value'=>$volunteer['email'],
				'input_type'=>'text',
				'required'=>true,
				'input_addon_start'=>'Email:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('email',$options) . '</div></div>';

		//first_name field
		$options = array(
				'input_value'=>$volunteer['first_name'],
				'input_type'=>'text',
				'required'=>true,
				'input_addon_start'=>'First:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('first_name',$options) . '</div></div>';

		//last_name field
		$options = array(
				'input_value'=>$volunteer['last_name'],
				'input_type'=>'text',
				'required'=>false,
				'input_addon_start'=>'Last:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('last_name',$options) . '</div></div>';

		$html .= '<div class="clearfix"></div><br>';

		
		//gender field
		$options = array(
				'check_value'=>$volunteer['gender'],
				'input_type'=>'select',
				'options'=>array('female'=>'Female','male'=>'Male','transgender'=>'Transgender','other'=>'Other'),
				'required'=>false,
				'input_addon_start'=>'Gender:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('gender',$options) . '</div></div>';
		
		//break line
		$html .= '<div class="clearfix"></div>';
		$html .= '<h2 class="h3 bg-info text-info">Update Password</h2>';

		//password field
		$options = array(
				'input_type'=>'password',
				'input_addon_start'=>'Password:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('password',$options) . '</div></div>';

		//password field
		$options = array(
				'input_type'=>'password',
				'input_addon_start'=>'Pass Repeat:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('password_repeat',$options) . '</div></div>';

		$html .= '<div class="form-group col-xs-12 col-md-4"><a id="generate-random-pass" class="btn btn-primary col-xs-12 tooltips" data-toggle="tooltip" data-placement="top" href="javascript:void(0)" title="Click here to auto create password">Auto Create</a></div>';

		$html .= '<div class="clearfix"></div><br><div class="form-group col-xs-10 col-xs-offset-1 col-md-6 col-md-offset-3"><input class="col-xs-12 btn btn-success" type="submit" value="Update Volunteer"></div>';

		$html .= '</form>';
		return $html;
	}
}
?>