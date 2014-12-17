<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-20 23:04:42
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-08 12:33:02
 */
	
	//check for email submissions before doing anything
	check_email_response();
	
	//reset lockout duration if the value is set
	$lockout = get_option_by_name('lockout_duration');
	if( $lockout ){
		$duration = (string) $lockout['option_value']*60;
		$lockout_duration = $duration;
	} else {
		global $lockout_duration;
	}

	//start the logn class
	$login = new login;

	//if sesion is active output the page content
	if( checkAdminSession() ){

		//set page vars
		$page = setPageVars();

		//set the page class name
		$page_class = str_replace('-', '_', $page );

		//check for require password reset
		require_once( __INCLUDE_PATH__ . 'functions' . _DIRSEP_ . 'user_functions.php' );
		$passwordReset = get_user_meta( $_COOKIE['usrID'], 'force_password_reset' );
		
		if( !empty( $passwordReset ) ){
			$passwordReset = $passwordReset['meta_value'];
			if( $passwordReset == 'true' && $page !== 'my-profile' ){
				header('Location: /my-profile');
			}
		}

		//page content
		$pageContent = new $page_class;

		//veirfy before submssion check
		$verify = verifyLicense();

		//check for page submissions
		if( method_exists( $pageContent, 'checkSubmission' ) ){

			//check not guest or demo user
			if( !in_array( $_COOKIE['usrtyp'], array( 'demo','guest' ) ) ){
				$pageContent->checkSubmission();
			}
		}

		//remove notifications
		remove_notifications();		

		//START CONTENT
		//get the html doc head
		require_once( __TEMPLATES__ . 'admin_head.php' );

		//get the navigation
		include_once( __TEMPLATES__ . 'admin_header.php');

		//check for development mode and ouput any submissions 
		if( check_dev_mode() ){
			if( !empty( $_REQUEST ) ){
				var_dump( $_SERVER['REQUEST_METHOD'] );
				var_dump( $_REQUEST );
			}
		}

		//get page messages 
		echo '<div class="col-xs-10 col-xs-offset-1 col-md-6 col-md-offset-3">';
		echo checkPageMessage( $pageContent );
		echo '</div>';
		echo '<div class="clearfix"></div>';

		//get bread crumbs
		echo breadcrumbs( $pageContent );

		//page header
		echo '<div class="page-header">';

		//page title / subtitle
		echo '	<h1>';
		echo $pageContent->title;
		if( !empty( $pageContent->subtitle ) ) {
			echo '<small id="page-subtitle">' . $pageContent->subtitle . '</small>';
		}
		echo '	</h1>';
		echo '</div>';

		//page content
		echo $pageContent->display();

		//get footer
		include_once( __TEMPLATES__ . 'admin_footer.php' );

	} else {	

		$pageContent = $login;

		//get the html doc head
		require_once( __TEMPLATES__ . 'admin_head.php' );

		//check for login messages
		echo '<div class="col-xs-10 col-xs-offset-1 col-md-6 col-md-offset-3" style="padding-top:10px;">';
		echo checkPageMessage( $pageContent );
		echo '</div>';
		echo '<div class="clearfix"></div>';

		//output the login form
		echo $pageContent->loginForm();

		//test the create user form
		echo '<div id="request-access-container" class="hidden">';
		echo $pageContent->createNewUserForm();
		echo '</div>';

		//get footer
		include_once( __TEMPLATES__ . 'admin_footer.php' );
	}

	


?>