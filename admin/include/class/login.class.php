<?php

class login {

	//content properties
	public $loginForm;

	public function __construct(){

		//page meta and breadcrumb information
		$this->pageMeta[0] = array(
			'name' => 'VMS Login',
			'url' => '/',
			'meta_title' => 'Welcome to your VMS System',
			'meta_description' => 'Sign in or request access to the ' . _CO_NAME_ . ' Volunteer Management System',
		);

		//checks for login / logout submissions
		$pLogin = new processLogin;

		//carry over page messages
		$this->pageMessage = $pLogin->pageMessage;

		//check for return after login page
		if( isset( $_GET['ret'] ) ){
			if( $_GET['ret'] == 'home' ){
				$this->returnURL = _PROTOCOL_ . _FRONTEND_URL_;
			} else {
				$this->returnURL = _ROOT_;
			}
		} else {
			$this->returnURL = null;
		}

		//check for position to determine the form layout
		if( isset( $_GET['pos_id'] ) ){
			
		}

		//check for user type to determine the form layout
		if( isset( $_GET['user_type'] ) ){
			$this->formType = $_GET['user_type'];
		} else {
			$this->formType = 'standard';
		}

		//check for missing attributes if there is a session started
		if( checkForSession() ){
			require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');
			verify_required_attributes();
		}

	}

	public function loginForm(){

		$form_html = '<div class="container">';
		$form_html .= '  <form class="form-signin" role="form" method="post">';

		//hidden field for return url after login or sign up
		$form_html .= '		<input type="hidden" name="login-return-url" value="' . $this->returnURL . '">';

		//check for form type
		if( $this->formType == 'new_vol' ){
			//form title
			$form_html .= '    <h2 class="form-signin-heading text-center">Account Creation</h2>';

			//login button
			$form_html .= '	   <div class="row">';		

			//forgot password button
			$form_html .= '      <div class="col-sm-12">'; 
			$form_html .= '		 	<button class="btn btn-link btn-block modal-operator" id="sign_up" data-toggle="modal" data-target="#navModal" data-title="Request Access to Volunteer Admin System" data-modal-body="request-access-container" data-modal-form-action="/" data-close-button="hide" data-save-button-text="Request Access">Request Access</button>';
			$form_html .= '    	  </div>';
			$form_html .= '    </div>';
			$form_html .= '</form>';


		} else {
			//form title
			$form_html .= '    <h2 class="form-signin-heading text-center">Please Sign In</h2>';

			//hidden field works as a place holder to search for username pass combo
			$form_html .= '		<input type="hidden" name="processLogin" value="true">';

			//username input
			$form_html .= '    <input type="email" name="username" class="form-control" placeholder="Email address" required="required" autofocus="" autocomplete="off" >';

			//password input
			$form_html .= '    <input type="password" name="password" class="form-control" placeholder="Password" autocomplete="off" >';

			//remember me option
			$form_html .= '    <div class="checkbox">';
			$form_html .= '      <label>';
			$form_html .= '        <input type="checkbox" name="login-remember-me" value="remember-me"> Remember me';
			$form_html .= '      </label>';
			$form_html .= '    </div>';

			//login button
			$form_html .= '	   <div class="row">';		
			$form_html .= '      <div class="col-sm-12">';
			$form_html .= '      	<button class="btn btn-lg btn-primary btn-block" id="sign_in">Sign in</button>';
			$form_html .= '      </div>';

			//forgot password button
			$form_html .= '      <div class="col-sm-12">'; 
			$form_html .= '		 	<input type="submit" name="forgotPass" value="Forgot Password" class="btn btn-link btn-block" id="forgot_pass">';
			$form_html .= '  		</form>';
			$form_html .= '    	  </div>';
			$form_html .= '      <div class="col-sm-12">'; 
			$form_html .= '		 	<button class="btn btn-link btn-block modal-operator" id="sign_up" data-toggle="modal" data-target="#navModal" data-title="Request Access to Volunteer Admin System" data-modal-body="request-access-container" data-modal-form-action="/" data-close-button="hide" data-save-button-text="Request Access">Request Access</button>';
			$form_html .= '    	  </div>';
			$form_html .= '      <div class="col-sm-12">'; 

			//create a return to frontend link that includes get vars
			$frontend_link = create_return_url( _PROTOCOL_ . _FRONTEND_URL_ );

			//insert return to frontend link
			$form_html .= '		 	<a class="btn btn-link btn-block" id="frontend-link" href="' . $frontend_link . '">Return to Volunteer Sign Up</a>';
			$form_html .= '    	  </div>';
			$form_html .= '    </div>';

		}
			
		$form_html .= '</div>';

		return $form_html;
	}

	public static function createNewUserForm($user_type = false){

		//check for events that allow for phone only registration
		require_once( __FUNCTION_INCLUDE__ . 'event_functions.php');
		$allow_phone_only = false;
		$events = get_events();
		if( !empty( $events ) ){
			foreach( $events as $id=>$e){
				$test = get_event_meta( $id, 'allow_no_email' );
				if( $test && $test['meta_value'] == 'on' ){
					$allow_phone_only = true;
				}
			}
		}

		//add script to amend the required fields
		$form_html = '<script>jQuery(document).ready(function(){ var attr = $("#new-vol-email").attr("required");if (typeof attr == typeof undefined || attr == false){$("#new-vol-phone").prop("required",true);} else {console.log( attr );} });</script>';

		//start container
		$form_html .= '<div class="container-fluid">';

		//hidden field works as a place holder to search for username pass combo
		$form_html .= '		<input type="hidden" name="newUser" value="true">';

		//first name input
		$form_html .= '    <label>First Name</label>';
		$form_html .= '    <input type="text" name="first_name" class="form-control" placeholder="First Name" required="required">';

		//last name input
		$form_html .= '    <label>Last Name</label>';
		$form_html .= '    <input type="text" name="last_name" class="form-control" placeholder="Last Name" required="required">';

		//username input
		if( $allow_phone_only ){ $email_req = null; } else { $email_req = ' required="required '; }
		$form_html .= '    <label>Email Address</label>';
		$form_html .= '    <input id="new-vol-email" type="email" name="username" class="form-control" placeholder="Email address" ' . $email_req . 'autofocus="" autocomplete="off" >';

		//phone number repeat input
		$form_html .= '    <label>Phone Number</label>';
		$form_html .= '    <input id="new-vol-phone" type="tel" name="phone" class="form-control" placeholder="555.555.5555" autocomplete="off" ><br>';

		//password input
		$form_html .= '    <label>Password</label>';
		$form_html .= '    <input type="password" name="password" class="form-control" placeholder="Enter a Password" required="required" autocomplete="off" >';

		//password repeat input
		$form_html .= '    <label>Repeat Your Password</label>';
		$form_html .= '    <input type="password" name="password2" class="form-control" placeholder="Repeat Your Password" required="required" autocomplete="off" ><br>';

		//check for usertype
		if( $user_type ){
			$form_html .= '<input type="hidden" name="user-type" required="required">';
		}

	
		$form_html .= '</div>';

		return $form_html;
	}
}
?>