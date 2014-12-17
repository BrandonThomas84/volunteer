<?php 

	//set the root folder
	define( '_FE_PATH_', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

	//set the admin folder path
	define( '_FE_ADMIN_PATH_', _FE_PATH_ . 'admin' . DIRECTORY_SEPARATOR );

	//set the admin include folder path
	define( '_FE_INCLUDE_PATH_', _FE_ADMIN_PATH_ . 'include' . DIRECTORY_SEPARATOR );

	//set the root folder
	define( '__ROOTPATH__', _FE_ADMIN_PATH_ );

	//set the include folder path
	define( '__INCLUDE_PATH__', _FE_INCLUDE_PATH_ );

	//set the include folder path
	define( '__FUNCTION_INCLUDE__', __INCLUDE_PATH__ . 'functions' . DIRECTORY_SEPARATOR );

	//get functions file
	require_once( _FE_INCLUDE_PATH_ . 'functions' . DIRECTORY_SEPARATOR . 'functions.php' );
	//verify the installation of the database
	if( !verify_install() ){

		//start install
		require_once( __TEMPLATES__ . 'install.php' );
		exit;

	} else {

		check_dev_mode();

		//include the necessary classes
		require_once( _FE_INCLUDE_PATH_ . 'class' . _DIRSEP_ . 'calendar.class.php' );
		require_once( _FE_INCLUDE_PATH_ . 'class' . _DIRSEP_ . 'frontend.class.php' );

		//start the logn class
		$login = new login;

		//start the frontend class
		$frontend = new frontend;
		$frontend->check_signup();
	}

			

?>	
	
<?php 

	//get the page head and header
	require_once( _FE_INCLUDE_PATH_ . 'templates' . _DIRSEP_ . 'fe_head.php'); 
	require_once( _FE_INCLUDE_PATH_ . 'templates' . _DIRSEP_ . 'fe_header.php'); 

	//get page messages 
	echo '<div class="col-xs-10 col-xs-offset-1 col-md-6 col-md-offset-3">';
	echo checkPageMessage();
	echo '</div>';
	echo '<div class="clearfix"></div>';
?>

		
		<div id="fe_primary">
			<div class="row-fluid">
				<div id="volunteer-signup-primary">
					<h1 class="text-center">Available Volunteer Positions</h1>
					<div id="volunteer-signup-narrows" class="col-sm-12 col-md-3">
						<?php
							echo $frontend->set_position_narrows();
						?>
					</div>
					<div id="volunteer-signup-body" class="col-sm-12 col-md-9">
					<?php

						//check if event is set and allows for open enrollment
						//var_dump( $frontend->position_narrows );
						if( isset( $frontend->position_narrows['event_id'] ) ){

							require_once( _FE_INCLUDE_PATH_ . 'functions' . DIRECTORY_SEPARATOR . 'event_functions.php' );

							//check for open enrollment
							$open_enrollment = get_event_meta( $frontend->position_narrows['event_id'], 'open_enrollment' );
							if( $open_enrollment && $open_enrollment['meta_value'] == 'on' ){

								//output open enrollment area
					?>	
						<div id="volunteer-open-volunteer" class="col-xs-12 well">
							<p class="h3">Open Enrollment</p>
							<p class="note">Want to help but don't know where or don't care? Sign up for open enrollment and let us find you a position where we need you.</p>
							<div id="open-volunteer-signup-trigger" class="btn btn-primary vol-modal-trigger" data-event-id="<?php echo $frontend->position_narrows['event_id']; ?>" data-title="Floating Volunteer Sign Up" data-close-button-text="Nevermind" data-close-button-color="danger" data-modal-body="floating-volunteer-sign-up-body">Sign Up
							</div>
							<div class="hidden floating-volunteer-sign-up-body">
								<?php

								//check if signed in
								$floating_url = create_return_url( _PROTOCOL_ . _FRONTEND_URL_, array('y','m','s'), array('ret'=>'home','event_id'=>$frontend->position_narrows['event_id'],'floating_volunteer'=>'true') );
								$sign_up_url = create_return_url( _PROTOCOL_ . _FRONTEND_URL_, array('y','m','s'), array('user_type'=>'new_vol') );
		      					$sign_in_url = create_return_url( _PROTOCOL_ . _FRONTEND_URL_, array('y','m','s'), array('ret'=>'home') );

				      			if( checkForSession() ){
				      				echo '<p class="h4">' . $_COOKIE['frstnm'] . ' ' . $_COOKIE['lstnm'] . ',</p><p>Would you like to be listed as a floating volunteer?</p><p>If you click "Float Me!" below we will add you to our list of volunteers and assign a position for you. You will be notified via your preferred contact method when we find the perfect spot for you.</p>';
				      			?>
				      				<span class="col-xs-6 col-md-4 col-xs-offset-3 col-md-offset-4 text-center">
					    				<a href="<?php echo $floating_url;?>" id="position-sign-up" type="button" class=" col-xs-12 btn btn-success">Float Me!</a>
					    			</span>
					      		<?php } else { 
					      			echo '<span class="col-xs-4 text-center">';
					      			echo '	<a href="' . _PROTOCOL_ . $sign_in_url . '" id="position-sign-in" class="btn btn-primary">Sign In</a><br>';
					      			echo '	<a href="' . _PROTOCOL_ . $sign_up_url . '" title="Don\'t have an account? Signing up is simple!">Create Account</a>';
					      			echo '</span>';
					      			echo '<span class="col-xs-4 text-center">';
					    			echo '	<a id="position-sign-up" type="button" class="disabled btn btn-success">Float Me</a>';
					    			echo '</span>';
					      		}
					      		?>
					      		<div class="clearfix"></div>
							</div>
						</div> 
						<div class="clearfix"></div>

					<?php
							}
						}
					?>
					<?php 
						$calendar = new calendar('frontend'); 
						echo $calendar->get_calendar();
					?>
					</div>
				</div>
			</div>
		</div>
<?php
	//get footer
	require_once( _FE_INCLUDE_PATH_ . 'templates' . _DIRSEP_ . 'fe_footer.php'); 
?>