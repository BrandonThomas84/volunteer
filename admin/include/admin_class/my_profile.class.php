<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-20 23:11:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-08 00:55:56
 */

class my_profile {

	public function __construct(){

		$this->title = $_COOKIE['frstnm'] . ' ' . $_COOKIE['lstnm'] . '\'s Profile';
		$this->subtitle = 'Profile settings';
		$this->pageMessage = null;

		//page meta and breadcrumb information
		$this->pageMeta[0] = array(
			'name' => 'My Profile',
			'url' => '/my-profile',
			'meta_title' => 'Manage Your Profile',
			'meta_description' => 'Easily manage your profile preferences',
		);

		//check for user attributes and create blank set if there are none
		$user_attributes = user_meta_defaults();
		foreach( $user_attributes as $key=>$val ){
			//check for an existing attribute
			$test = get_user_meta( $_COOKIE['usrID'], $key );
			if( !$test ){
				//if no attribute add a blank one
				update_user_meta( $_COOKIE['usrID'], $key, $val );
			}
		}
	}

	public function checkSubmission(){
		//check for password reset
		if( isset( $_POST['settings-new_password1'] ) ){

			//verify passwords matched
			if( $_POST['settings-new_password1'] == $_POST['settings-new_password2'] ){
				update_user_pass( $_COOKIE['usrID'], $_POST['settings-new_password1'] );
			} else {
				$this->pageMessage['Password Mismatch'] = 'Whoops! It looks like your passwords do not match. Please check your entry and try again.';
			}
		}

		//check for profile image upload
		if( !empty( $_FILES ) ){
			
			//verify no error
			if ($_FILES["new-profile-image"]["error"] > 0) {
				echo $_FILES["new-profile-image"]["error"];
			} else {
				$allowedExts = array("gif", "jpeg", "jpg", "png");
				$temp = explode(".", $_FILES["new-profile-image"]["name"]);
				$extension = end($temp);

				if ( ( ( $_FILES["new-profile-image"]["type"] == "image/gif" )
				|| ( $_FILES["new-profile-image"]["type"] == "image/jpeg" )
				|| ( $_FILES["new-profile-image"]["type"] == "image/jpg" )
				|| ( $_FILES["new-profile-image"]["type"] == "image/pjpeg" )
				|| ( $_FILES["new-profile-image"]["type"] == "image/x-png" )
				|| ( $_FILES["new-profile-image"]["type"] == "image/png" ) )
				&& ( $_FILES["new-profile-image"]["size"] < 2048000 )
				&& in_array($extension, $allowedExts)) {

					//get user info
					$user_id = $_COOKIE['usrID'];
					$user = get_user( $user_id );

					//update the image meta entry
					$newFileName = 'profile_image_' . $user['id'] . '.' . $extension;
					update_user_meta($user['id'], 'profile_image', $newFileName);

					//set upload directives
					$uploaddir = __ROOTPATH__ . 'profile-images' . _DIRSEP_;
					$uploadfile = $uploaddir . $newFileName;
					move_uploaded_file($_FILES['new-profile-image']['tmp_name'], $uploadfile);
				}
			}

			//check if settings were updated as well
			if( $_POST['profile-settings-updated'] == 'false' ){

				submission_redirect( _PROTOCOL_ . _ROOTURL_ . '/my-profile/' );
			}
		}

		//check for profile updates
		if( !empty( $_POST['profile-settings-updated'] ) && $_POST['profile-settings-updated'] == 'true' ){

			//update first and last name
			update_user_field( $_COOKIE['usrID'],'first_name', $_POST['settings-profile-first-name'] );
			update_user_field( $_COOKIE['usrID'],'last_name', $_POST['settings-profile-last-name'] );

			//verify the email is unique and update if so
			if( !get_user_by_email( $_POST['settings-profile-email'] ) ){
				update_user_field( $_COOKIE['usrID'],'email', $_POST['settings-profile-email'] );
			}	

			//update password if password  duplication matches
			if( !empty( $_POST['settings-profile-password1'] ) && !empty( $_POST['settings-profile-password1'] ) && ( $_POST['settings-profile-password1'] == $_POST['settings-profile-password2'] ) ){
				update_user_pass( $_COOKIE['usrID'], $_POST['settings-profile-password1'] );
			}

			//update meta fields
			update_user_meta( $_COOKIE['usrID'], 'email_pref', $_POST['settings-profile-email-pref'] );

			//check for user attribute update
			$user_attributes = getAttributes();
			foreach ($user_attributes as $attr) {

				//verify there is a name for the attribute
				if( !empty( $attr['name'] ) && !empty( $attr['dom_id'] ) ){

					//check for a posted update
					if( isset( $_POST['settings-' . $attr['dom_id'] ] ) ){
						update_user_meta( $_COOKIE['usrID'], $attr['dom_id'], $_POST['settings-' . $attr['dom_id'] ] );
					}
				}
			}

			//redirect
			submission_redirect( _PROTOCOL_ . _ROOTURL_ . '/my-profile/' );
		}


	}

	public function display(){
		$html = '';

		//verify license
		$verify = verifyLicense();

		//check for force password change
		$passForce = get_user_meta( $_COOKIE['usrID'], 'force_password_reset' );
		if( $passForce && $passForce['meta_value'] == 'true' ){

			//output force password reset
			$html .= self::forcePasswordReset();

		} else {

			$html .= $this->userSettingsPage();
		}
		
		return $html;
	}

	public static function forcePasswordReset(){
		$html ='';
		$html .= '<div class="col-xs-12 col-md-8 col-md-offset-2 text-center well">';
		$html .= '<h2>Password Reset</h2>';
		$html .='<form role="form" method="post">';
		$html .= '<p>You must reset your password before continuing</p>';

		//password field 1
		$options = array(
				'label'=>'Password:',
				'input_type'=>'password',
				'placeholder'=>'Password',
				'required'=>true,
				'field_wrap'=>array('<div class="input-group col-xs-12">','</div>'),
			);
		$html .= createFormInput('new_password1', $options);

		//password field 2
		$options = array(
				'label'=>'Repeat Password:',
				'input_type'=>'password',
				'placeholder'=>'Repeat Password',
				'required'=>true,
				'field_wrap'=>array('<div class="input-group col-xs-12">','</div>'),
			);
		$html .= createFormInput('new_password2', $options);

		//save button
		$html .= '<br>';
		$options = array(
				'id'=>'new-pass-submit',
				'input_type'=>'submit',
				'input_value'=>'Update Password',
				'class'=>'btn btn-success',
				'field_wrap'=>array('<div class="input-group col-xs-12">','</div>'),
			);
		$html .= createFormInput('update_pass', $options);
		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	public function userSettingsPage(){
		//get user data
		$user_id = $_COOKIE['usrID'];
		$user = get_user( $user_id );
		
		//set the profile image
		$user_image = get_user_meta( $user_id, 'profile_image' );
		if( !$user_image['meta_value'] || $user_image['meta_value'] == ''){
			$user_image = _ROOT_ . '/profile-images/blank_male.jpg';
		} else {
			$user_image = _ROOT_ . '/profile-images/' . $user_image['meta_value'] ;
		}

		//get the user meta info excluding permissions
		$user_info = user_meta_defaults();

		//loop through defaults to set them to the users values
		foreach( $user_info as  $field=>$default ){

			//check for user meta value
			$value = get_user_meta( $user_id, $field );
			if( $value ){
				$user_info[ $field ] = $value['meta_value'];
			} 
		}

		$html = '<div class="row-fluid">';
		$html .= '	<form id="profile-settings" name="my-profile" class="form-horizontal form-has-nonce" role="form" enctype="multipart/form-data" method="post" data-controller-id="profile-settings-updated">';
		$html .= '		<input id="profile-settings-updated" type="hidden" name="profile-settings-updated" value="false" />';
		$html .= '		<div id="profile-image" class="col-xs-12 col-md-4">';
		$html .= '			<div class="col-xs-10 col-xs-offset-1 text-center">';
		$html .= '				<img src="' . $user_image . '">';
		$html .= '				<div id="new-profile-img-upload" style="padding-bottom: 12px;display:none;" class="text-center marg-t">';
		$html .= '					<input type="hidden" name="MAX_FILE_SIZE" value="2048000" />';
		$html .= '					<input type="file" name="new-profile-image" id="new-profile-image" class="form-control" >';
		$html .= '				</div>';
		$html .= '				<button id="new-profile-img-trigger" class="marg-t col-xs-6 col-xs-offset-3 btn btn-primary">Upload New Photo</button>';
		$html .= '			</div>';
		$html .= '		</div>';
		$html .= '		<div id="profile-body" class="col-xs-12 col-md-8">';
		$html .= '			<div class="form-section">';
		$html .= '				<h3>General Information</h3>';
		$html .= '				<div class="form-group">';
		$html .= '				    <label for="settings-profile-first-name" class="col-sm-3 control-label">First Name</label>';
		$html .= '				    <div class="col-sm-9">';
		$html .= '				    	<input type="text" name="settings-profile-first-name" class="form-control" id="settings-profile-first-name" placeholder="Your First Name" value="' . $user['first_name'] . '">';
		$html .= '					</div>';
		$html .= '				</div>';
		$html .= '				<div class="form-group">';
		$html .= '				    <label for="settings-profile-last-name" class="col-sm-3 control-label">Last Name</label>';
		$html .= '				    <div class="col-sm-9">';
		$html .= '				    	<input type="text" name="settings-profile-last-name" class="form-control" id="settings-profile-last-name" placeholder="Your Last Name" value="' . $user['last_name'] . '">';
		$html .= '					</div>';
		$html .= '				</div>';
		$html .= '				<div class="form-group">';
		$html .= '				    <label for="settings-profile-email" class="col-sm-3 control-label">Email / Username</label>';
		$html .= '				    <div class="col-sm-9">';
		$html .= '				    	<input type="email" name="settings-profile-email" class="form-control" id="settings-profile-email" placeholder="Email" value="' . $user['email'] . '">';
		$html .= '					</div>';
		$html .= '				</div>';
		$html .= '				<div class="form-group">';
		$html .= '				    <label for="settings-profile-email-pref" class="col-sm-3 control-label">Email Style</label>';
		$html .= '				    <div class="col-sm-6 col-md-4">';
		$html .= '				    	<select name="settings-profile-email-pref" class="form-control" id="settings-profile-email-pref">';
		$html .= '				    		<option value="html" ' . returnSelected( $user_info['email_pref'],'html' ) . '>HTML</option>';
		$html .= '				    		<option value="plain_text" ' . returnSelected( $user_info['email_pref'],'plain_text' ) . '>Plain Text</option>';
		$html .= '				    	</select>';
		$html .= '					</div>';
		$html .= '				</div>';
		$html .= '			</div>';

		$html .= '			<div class="form-section">';
		$html .= '				<h3>User Attributes</h3>';
		$html .= '				<p>The following are attributes that allow us to help find the most appropriate positions or you. Not all of them are required but it is suggested that you supply as much information as possible.</p>';


		//get the user attributes
		$user_attributes = getAttributes();
		foreach ($user_attributes as $attr) {
			//verify there is a name for the attribute
			if( !empty( $attr['name'] ) && !empty( $attr['dom_id'] ) && $attr['profile_display'] ){

				//empty the field options
				$field_options = array();

				//start output
				$html .= '				<div class="form-group">';
				$html .= '				    <label for="settings-' . $attr['dom_id'] . '" class="col-sm-3 control-label">' . $attr['name'] . '</label>';
				$html .= '				    <div class="col-sm-6 col-md-4">';

				//check if type is multiple choice and add option values
				if( $attr['type'] == 'multiple_choice' ){
					$field_options['input_type'] = 'select';

					//empty select options array
					$options = array();

					//get and convert options
					$option_raw = get_option_by_id( $attr['options'] );
					$option_raw = $option_raw['option_value'];
					$option_raw = json_decode( $option_raw, TRUE );

					//loop through options
					foreach( $option_raw as $key=>$val ){
						$options[ $key ] = $val;

						//check if option is selected
						$exst_value = get_user_meta( $user_id, $attr['dom_id'] );
						if( $exst_value['meta_value'] == $key ){
							$field_options['check_value'] = $key;
						}
					}

					//add options to the field options array
					$field_options['options'] = $options;

					//allow blank input
					$field_options['allow_blank'] = true;


				} else {
					//set type
					$field_options['input_type'] = $attr['type'];

					//check for existing value
					$exst_value = get_user_meta( $user_id, $attr['dom_id'] );
					if( $exst_value ){
						if( !empty( $exst_value['meta_value'] ) ){
							$field_options['input_value'] = $exst_value['meta_value'];
						}
					}
				}

				//check for placeholder
				if( !empty( $attr['placeholder'] ) ){
					$field_options['placeholder'] = $attr['placeholder'];
				}

				//check for required
				if( !empty( $attr['required'] ) && $attr['required'] ){
					$field_options['required'] = true;
				}


				//create the input form
				$html .= createFormInput( $attr['dom_id'], $field_options );

				//close row
				$html .= '					</div>';
				$html .= '				</div>';

			}
		}

		//close attributes form section
		$html .= '			</div>';

		//password and other security stuff
		$html .= '			<div class="form-section">';
		$html .= '				<h3>User Security</h3>';
		$html .= '				<div class="form-group">';
		$html .= '				    <label for="settings-profile-password1" class="col-sm-3 control-label">Password</label>';
		$html .= '				    <div class="col-sm-9">';
		$html .= '				    	<input type="password" name="settings-profile-password1" class="form-control" id="settings-profile-password1" placeholder="Should include upper and lower case letters and at least one special character and/or number">';
		$html .= '					</div>';
		$html .= '				</div>';
		$html .= '				<div class="form-group  has-feedback">';
		$html .= '				    <label for="settings-profile-password2" class="col-sm-3 control-label">Password</label>';
		$html .= '				    <div class="col-sm-9">';
		$html .= '				    	<input type="password" name="settings-profile-password2" class="form-control" id="settings-profile-password2" placeholder="Repeat your password">';
		$html .= '					</div>';
		$html .= '				</div>';
		$html .= '			</div>';
		$html .= '			<div class="form-section">';
		$html .= '				<h3>Account Information</h3>';
		$html .= '				<div class="form-group">';
		$html .= '				    <label for="settings-profile-account-type" class="col-sm-3 control-label">Account Type</label>';
		$html .= '				    <div class="col-sm-9">';

		//get the user type
		$user_type = get_user_types();
		$user_type = $user_type[ $user['user_type'] ];

		$html .= '				    	<input type="email" name="settings-profile-account-type" class="form-control" id="settings-profile-account-type" value="' . $user_type['friendly'] . '" readonly>';
		$html .= '					</div>';
		$html .= '				</div>';
		$html .= '			</div>';

		//submit button
		$html .= '			<div class="form-section">';
		$html .= '				<div class="form-group">';
		$html .= '				    <div class="col-sm-8 col-sm-offset-2">';
		$html .= '				    	<input type="submit" name="settings-profile-submit" class="form-control btn btn-success" id="settings-profile-submit" value="Save Changes">';
		$html .= '					</div>';
		$html .= '				</div>';
		$html .= '			</div>';

		//end container and form
		$html .= '		</div>';
		$html .= '	</form><!--END FORM-->';
		$html .= '</div>';

		return $html;
	}
}
?>