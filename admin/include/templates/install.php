<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-09-24 21:37:41
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-09-24 23:11:26
 */

//default page message
$message = 'You must install the database before proceeding.';
$button = array('/?idb=true','Install Database','primary');

//install db
if( isset( $_GET['idb'] ) && $_GET['idb'] == 'true' ){
					
	global $mysqli;

	//test connection
	if( $mysqli && empty( $mysqli->connect_error ) ){

		//get the install script
		$query = file_get_contents( __INCLUDE_PATH__ . 'installation' . _DIRSEP_ . 'db_install.sql');

		//remove all breaks and tabs from install
		$remove = array( "\n", "\r", "\t");
		$query = str_replace( $remove, null, $query );

		//run install
		$mysqli->multi_query( $query );

		//redirect
		header('Location:' . _ROOT_ . '/' );

	} else {

		$message = $mysqli->connect_error;
	}
	
}

?>
<!DOCTYPE html>
<html class="no-js" lang="en-US">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Volunteer Management Software Installtation</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/jquery-ui.min.js"></script>
		<!-- BOOTSTRAP -->
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
		<!-- CORE JS -->
		<script src="/js/jquery.cookie.js"></script>
		<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/themes/smoothness/jquery-ui.css" />
		<link rel="stylesheet" id="core-css" href="/style/install-style.css" type="text/css" media="all">
		<link rel="stylesheet" id="responsive-css" href="/style/install-responsive.css" type="text/css" media="all">
		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
		<link rel="icon" href="/favicon.ico" type="image/x-icon">
	</head>
	<body>
		<div class="row-fluid">
			<div class="col-xs-12 text-center">
				<h1 class="page-title text-center">Install Volunteer Management Software</h1>
				<p class="bg-danger text-danger"><?php echo $message; ?></p>
				<?php if( !empty( $button ) ){ ?>
					<a class="btn btn-<?php echo $button[2]; ?>" href="<?php echo $button[0]; ?>"><?php echo $button[1]; ?></a>
				<?php } ?>
			</div>
		</div>
	</body>
</html>