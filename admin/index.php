<?php 

	//set the root folder
	define( '__ROOTPATH__', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

	//set the include folder path
	define( '__INCLUDE_PATH__', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR );

	//set the include folder path
	define( '__FUNCTION_INCLUDE__', __INCLUDE_PATH__ . 'functions' . DIRECTORY_SEPARATOR );

	//get functions file
	require_once( __FUNCTION_INCLUDE__ . 'functions.php' );

	//verify the installation of the database
	if( verify_install() ){

		check_dev_mode();

		set_database_constants();
		
		//start admin page
		require_once( __TEMPLATES__ . 'admin_page.php' );

	} else {

		//start install
		require_once( __TEMPLATES__ . 'install.php' );

	}

?>	