<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-20 23:11:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-08 00:26:29
 */

class manage_users {

	public function __construct(){

		/**
		INCLUDE THE USER FUNCTIONS
		*/
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');

		$this->title = 'Manage Users';
		$this->subtitle = 'Approve / Deny User Access and control function rights';
		$this->pageMessage = null;
		$this->user_perm = user_perms();

		//page meta and breadcrumb information
		$this->pageMeta[0] = array(
			'name' => 'Manage Users',
			'url' => '/manage-users',
			'meta_title' => 'VMS Manage Users',
			'meta_description' => '',
		);		

		//overwrite class vars if user id is set
		if( isset( $_GET['function'] ) ) {

			//set user id var
			$user_id = $_GET['function'];

			//set user var
			$user = get_user( $user_id );

			//get gender
			$gender = get_user_meta( $user_id, 'gender');

			//check for gender
			if( $gender ){
				$gender = $gender['meta_value'];
			} else {
				//add if missing
				update_user_meta( $user_id,'gender','unknown');
				$gender = 'unknown';
			}
			
			//get user image
			if( file_exists( __ROOTPATH__ . 'profile-images' . _DIRSEP_ . 'profile_image_' . $user_id . '.jpg' ) ){
				$image = _ROOT_ . '/profile-images/profile_image_' . $user_id . '.jpg';
			} else {
				$image = _ROOT_ . '/profile-images/blank_' . $gender . '.jpg';
			}

			//change page title
			$this->title = $user['first_name'] . ' ' .  $user['last_name'];
			$this->subtitle = 'Edit settings for ' . $user['first_name'] . ' ' .  $user['last_name'] . '<span id="manage-user-profile-image"><img src="' . $image . '"></span>';


			//page meta and breadcrumb information
			$this->pageMeta[1] = array(
				'name' => $user['first_name'] . ' ' . $user['last_name'] . ' User Settings',
				'url' => '/manage-users/' . $user_id,
				'meta_title' => $user['first_name'] . ' ' . $user['last_name'],
				'meta_description' => '',
			);

			//get the assigned user permissions and update if neccessary
			$this->user_perm = get_user_perms( $_GET['function'] );
		}
	}

	public function checkSubmission(){

		//check for approve user
		if( isset( $_GET['approve'] ) ){

			//approve user with new user type
			update_user_type( $_GET['approve'], $_GET['utype'] );

			//send approval email
			approveUserEmail( $_GET['approve'] );

			//get user info
			$user_info = get_user( $_GET['approve'] );

			add_page_message('success',$user_info['first_name'] . ' ' . $user_info['last_name'] . ' has been approved','Approval Notice');

			//redirect
			submission_redirect('/manage-users');
		}

		//check for password recovery email
		if( isset( $_GET['pass-recovery'] ) && $_GET['pass-recovery'] == 'true' ){

			//get the user info
			$user_id = $_GET['function'];
			$user = get_user( $user_id );

			//send the password recovery email
			forgotPasswordEmail( $user['email'] );

			add_page_message('warning',$user['first_name'] . ' ' . $user['last_name'] . ' has been sent a password recovery email','Recovery Message Sent');

			//log user out if it is their account that they have requested password recvery be sent to
			if( $user_id == $_COOKIE['usrID'] ){
				header('Location: /logout');
			} else {
				submission_redirect('/manage-users/' . $_GET['function'] );
			}
		}

		//check for posted changes to basic information
		if( checkNonce('user-settings-nonce') ){

			//get existing values
			$user = get_user( $_GET['function'] );

			//fields posted=>db
			$fields = array('email','first_name','last_name','user_type');

			//check for differences in field values and update as needed
			foreach( $fields as $post ){
				if( $_POST[ 'settings-' . $post ] !== $user[ $post ] ){
					update_user_field( $_GET['function'], $post, $_POST[ 'settings-' . $post ] );
				}
			}

			//check for password change
			if( !empty( $_POST['settings-password'] ) ){

				//check that passwor match
				if( $_POST['settings-password'] == $_POST['settings-password_repeat'] ){
					update_user_pass( $_GET['function'], $_POST['settings-password'] );
				} else {
					add_page_message('danger','The password did not match','Password Mismatch');
				}
			}

			//check that user is not admin to prevent accidental lockout
			if( get_user_level( $_GET['function'] ) < 10 ){
				
				//loop rhough perms to check for changes
				foreach( $this->user_perm as $perm ){
					
					//check if value is set
					if( isset( $_POST[ $perm['id'] . '-onoff'] ) ){

						//if value requires updating
						if( $perm['value'] == 'false' ){
							update_user_meta( $_GET['function'], $perm['id'], 'true' );
						}
						
					} else {

						//if value requires updating
						if( $perm['value'] == 'true' ){
							update_user_meta( $_GET['function'], $perm['id'], 'false' );
						}
					}
				}
			} 

			//get updatedvalues
			$user = get_user( $_GET['function'] );

			add_page_message('success',$user['first_name'] . ' ' . $user['last_name'] . ' user settings have been updated','Settings Updated');
			submission_redirect('/manage-users/' . $_GET['function'] );
		}
	}

	public function display(){

		//verify license
		$verify = verifyLicense();

		//if user id is set
		if( isset( $_GET['function'] ) ){

			//set user id var
			$user_id = $_GET['function'];

			//if user id selected  display individual options
			$html = $this->user_configuration( $user_id );

		} else {

			//output the select user table
			$html = $this->display_user_table();
		}

		return $html;
	}

	private function display_user_table(){
		$html = '<div id="manage-users-primary">';

		//array of user types
		$user_types = get_user_types();

		//loop through user types
		foreach( $user_types as $user_type =>$user_info ){

			//get users by user type
			$users = get_users_by_user_type($user_type);
			
			//count user types for badge
			$usr_count = count( $users );
			if( $users && $usr_count > 0 ){
				$label_type = 'success';
			} else {
				$usr_count = 0;
				$label_type = 'default';
			}

			//set the label
			$anchor_label = '<span class="label label-' . $label_type . ' marg-l">' . $usr_count . '</span>';

			//start container and table
			$html .= '<div class="panel panel-default" id="' . str_replace('_',null, $user_type) . '-user-cfg">';
			$html .= '	<div class="panel-heading">';
			$html .= '		<a data-toggle="collapse" data-parent="#' . str_replace('_',null, $user_type) . '-user-cfg" href="#' . str_replace('_',null, $user_type) . '-user-collapase">' . $user_info['friendly'] . $anchor_label . '</a>';

			//check if user is admin or hass access to create users
			if( check_user_level( 10 ) ){
				$html .= '		<span class="panel-button modal-operator new-user-panel-button tooltips" data-user-type="' . $user_type . '" data-toggle="modal" data-target="#navModal" data-title="Create a New ' . $user_info['friendly'] . ' User" data-modal-body="new-user-form" data-modal-form-action="' . _ROOT_ . '/manage-users" data-save-button-text="Create ' . $user_info['friendly'] . ' User" data-toggle="tooltip" data-placement="left" title="Create a New ' . $user_info['friendly'] . ' User">'; 
				$html .= '			<span>+</span>';
				$html .= '		</span>';
			}
				
			$html .= '	</div>';
			$html .= '	<div class="panel-body panel-collapse collapse" id="' . str_replace('_',null, $user_type) . '-user-collapase">';
			$html .= '		<p class="help-block bg-info text-info sm-padd">' . $user_info['description'] . '</p>';
			$html .= '		<table class="table table-striped table-hover table-responsive users-display-table">';
			$html .= '			<thead>';
			$html .= '				<tr>';
			$html .= '					<th class="users-table-head-id"><p>#</p></th>';
			$html .= '					<th class="users-table-head-fst"><p>First</p></th>';
			$html .= '					<th class="users-table-head-lst"><p>Last</p></th>';
			$html .= '					<th class="users-table-head-eml"><p>Email</p></th>';
			$html .= '					<th class="users-table-head-dt"><p>Created</p></th>';

			//check for if pending users
			if( $user_type == 'pending_approval' ){
				$html .= '					<th class="users-table-head-pdcont"><p>Controls / Change User Type</p></th>';
			} else {
				$html .= '					<th class="users-table-head-cont"><p>Controls</p></th>';
			}
			$html .= '				</tr>';
			$html .= '			</thead>';
			$html .= '			<tbody>';

			

			//check for results
			if( !empty( $users ) && $users !== false ){
				
				//counter 
				$i = 1;
				
				//loop through users
				foreach( $users as $user ){

					$html .= '				<tr>';
					$html .= '					<td><p>' . $i . '</p></td>';
					$html .= '					<td><p>' . $user['first_name'] . '</p></td>';
					$html .= '					<td><p>' . $user['last_name'] . '</p></td>';
					$html .= '					<td><p>' . $user['email'] . '</p></td>';
					$html .= '					<td><p>' . date('m/d/Y H:i:s', strtotime( $user['created'] ) ) . '</p></td>';
					$html .= '					<td class="row">';

					//check for if pending users
					if( $user_type == 'pending_approval' ){

						//class for primary button
						$class = 'col-md-4 col-xs-12 col-lg-offset-1 ';

						//new user type select box for JS conversion 
						$button = '<div class="col-md-3 col-xs-12 uu-sib">';
						$button .= '	<select class="update-user-control form-control">';
						$button .= '		<option></option>';

						//user types options
						foreach( $user_types as $uoption => $info ){
							$button .= '	<option value="' . $uoption . '">' . $info['friendly'] . '</option>';
						}
						$button .= '	</select>';
						$button .= '</div>';

						//update button
						$button .= '<a class="col-md-3 col-xs-12 btn btn-success disabled update-user" data-user-id="' . $user['id'] . '" data-user-type="" href="javascript:void(0)">Approve User</a>';
					} else {
						$class = 'col-xs-12 ';
						$button = null;
					} 

					$html .= $button;
					$html .= '						<a class="' . $class . ' btn btn-primary" href="' . _ROOT_ . '/manage-users/' . $user['id'] . '">Configure</a>';
					
					$html .= '					</td>';
					$html .= '				</tr>';

					$i++;
				}
			} else {
				$html .= '				<tr>';
				$html .= '					<td colspan="6"><p>No Users</p></td>';
				$html .= '				</tr>';
			}

			//close table
			$html .= '			</tbody>';
			$html .= '		</table>';
			$html .= '	</div>';
			$html .= '</div>';
		}

		$html .= '</div><!-- CLOSE PRIMARY-->';

		//new user form
		if( check_user_level( 10 ) ){
			$html .= '<div id="new-user-form" class="hidden">';		
			//add create new user form with required usertype field
			$html .= login::createNewUserForm(true);
			$html .= '</div>';
		}

		return $html;
	}

	private function user_configuration( $user_id ){

		//get the urser information
		$user = get_user( $user_id );

		//start ouput
		$html = '<form id="user-settings" class="form-inline form-has-nonce" role="form" method="post" data-controller-id="user-settings-nonce">';

		//nonce
		$html .= '<input type="hidden" id="user-settings-nonce" name="user-settings-nonce" value="false">';


		//section header
		$html .= '<h2 class="h3 bg-info text-info">User Information</h2>';

		//email field
		$options = array(
				'input_value'=>$user['email'],
				'input_type'=>'text',
				'required'=>true,
				'input_addon_start'=>'Email:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('email',$options) . '</div></div>';

		//first_name field
		$options = array(
				'input_value'=>$user['first_name'],
				'input_type'=>'text',
				'required'=>true,
				'input_addon_start'=>'First:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('first_name',$options) . '</div></div>';

		//last_name field
		$options = array(
				'input_value'=>$user['last_name'],
				'input_type'=>'text',
				'required'=>false,
				'input_addon_start'=>'Last:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('last_name',$options) . '</div></div>';
		
		//break line
		$html .= '<div class="clearfix"></div>';
		$html .= '<h2 class="h3 bg-info text-info">Update Password</h2>';

		//password field
		$options = array(
				'input_type'=>'password',
				'required'=>false,
				'input_addon_start'=>'Password:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('password',$options) . '</div></div>';

		//password field
		$options = array(
				'input_type'=>'password',
				'required'=>false,
				'input_addon_start'=>'Pass Repeat:',
			);
		$html .= '<div class="form-group col-xs-12 col-md-4"><div class="input-group">' . createFormInput('password_repeat',$options) . '</div></div>';


		//check for access to the user type
		if( verify_perm_access('change_user_type') || check_user_level(10) ){

			//send password recovery email
			$html .= '<div class="form-group col-xs-8 col-xs-offset-2 col-md-4 col-md-offset-0"><a href="' . _ROOT_ . '/manage-users/' . $user_id . '?pass-recovery=true" class="trigger-confirm btn btn-danger btn-full" data-confirm-message="This will send an email notification to the user with a temporary password and instructions on how to update their password. Do you want to continue?">Send Password Recovery</a></div>';
		}

		//check for reset password access
		if( verify_perm_access('reset_user_password') || check_user_level(10) ){

			//break line
			$html .= '<div class="clearfix"></div>';
			$html .= '<h2 class="h3 bg-info text-info">User Accessibility</h2>';

			//user type
			$utypes = get_user_types();
			foreach( $utypes as $key=>$info ){
				$remove = array(' Accounts',' Users', 'Users ');
				$avail_utypes[ $key ] = str_replace($remove, null, $info['friendly']);
			}
			$options = array(
					'input_type'=>'select',
					'required'=>true,
					'input_addon_start'=>'User Type',
					'options'=>$avail_utypes,
					'check_value'=>$user['user_type'],
				);
			$html .= '<div class="form-group col-xs-12 col-md-6"><div class="input-group">' . createFormInput('user_type',$options) . '</div></div>';

			$html .= '<div class="clearfix" style="height: 20px;"></div>';
		}

		//check for access to change user permissions
		if( verify_perm_access('update_user_perms') || check_user_level(10) ){
			//break line
			$html .= '<div class="clearfix"></div>';
			$html .= '<h2 class="h3 bg-info text-info">User Permissions</h2>';

			//create the groups
			foreach( $this->user_perm as $perm ){
				$permissions_groups[ $perm['group'] ] = array();
			}
			//add permissions to their respective groups
			foreach( $this->user_perm as $perm ){
				array_push( $permissions_groups[ $perm['group'] ], $perm);
			}

			foreach( $permissions_groups as $group=>$perm ){
				//section title
				$html .= '<div class="col-xs-12 col-md-4 user-perm-group">';
				$html .= '<h3 class="h5 bg-info md-padd">' . $group . '</h2>';

				//loop through permissions
				foreach( $perm as $p ){

					//check value
					$checked = null;
					if( $p['value'] == 'true' ){
						$checked = 'checked';
					} 
					$html .= '<div class="col-xs-12 user-perm-form">';
					$html .= '	<span class="col-xs-6 form-label user-perm-name">' . ucwords( str_replace(array('perm_','_'), array( null, ' '),  $p['id'] ) ) . '</span>';
					$html .= '	<div class="col-xs-6 user-perm-switch">';
					$html .= '		<div class="onoffswitch">';
					$html .= '			 <input type="checkbox" id="' . $p['id'] . '-onoff" name="' . $p['id'] . '-onoff" class="onoffswitch-checkbox"  '. $checked . '>';
					$html .= '			 <label class="onoffswitch-label" for="' . $p['id'] . '-onoff">';
					$html .= '			 	<span class="onoffswitch-inner"></span>';
					$html .= '		 		<span class="onoffswitch-switch"></span>';
					$html .= '			</label>';
					$html .= '		</div>';
					$html .= '	</div>';
					$html .= '</div>';
				}

				//close group
				$html .= '</div>';
			}
		}

		$html .= '<div class="clearfix"></div><br>';

		//submit button
		$options = array(
				'input_type'=>'submit',
				'input_value'=>'Update User',
				'class'=>'btn btn-success btn-full'
			);
		$html .= '<div class="form-group col-xs-12 col-md-6 col-md-offset-3">' . createFormInput('user_submit',$options) . '</div>';

		$html .= '</form>';
		return $html;
	}
}
?>