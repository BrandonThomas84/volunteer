<?php

if( isset( $pageContent ) ){
	global $pageContent;

	//get page meta
	$elems = count( $pageContent->pageMeta );
	$meta = $pageContent->pageMeta[ $elems - 1 ];
} else {
	$meta['meta_title'] = _DEFAULT_TITLE_;
	$meta['meta_description'] = _DEFAULT_TITLE_;
}

?>

<!DOCTYPE html>
<html class="no-js" lang="en-US">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>
			<?php echo $meta['meta_title'] . ' | ' . _CO_NAME_ . ' ' . _DEFAULT_TITLE_; ?>
		</title>
		<meta name="description" content="<?php echo $meta['meta_description'] ?>">
		<link rel="stylesheet" id="font_awesome-css" href="//<?php echo _FRONTEND_URL_; ?>/style/font-awesome.css" type="text/css" media="all">		
		<script src="//<?php echo _FRONTEND_URL_; ?>/js/jquery.min.js"></script>
		<script src="//<?php echo _FRONTEND_URL_; ?>/js/jquery-ui.min.js"></script>
		<script async="" src="//<?php echo _FRONTEND_URL_; ?>/js/analytics.js" type="text/javascript"></script>		

		<!-- BOOTSTRAP -->
		<link rel="stylesheet" href="//<?php echo _FRONTEND_URL_; ?>/style/bootstrap.min.css">
		<script src="//<?php echo _FRONTEND_URL_; ?>/js/bootstrap.min.js"></script>

		<!-- CORE JS -->
		<script id="jquery-cookie-js" src="//<?php echo _FRONTEND_URL_ ; ?>/js/jquery.cookie.js"></script>
		<script id="volunteer-js" src="//<?php echo _ROOTURL_ ; ?>/js/vol_js.js"></script>
		<!--<script src="//<?php echo _FRONTEND_URL_; ?>/js/jquery-ui.js"></script>-->
		<script id="scroll-to-js" src="//<?php echo _ROOTURL_ ; ?>/js/jquery.scrollTo.min.js"></script>
		
		<!-- Optional theme -->
		<link rel="stylesheet" id="bootstrap-theme-css" href="//<?php echo _FRONTEND_URL_; ?>/style/bootstrap-theme.min.css">
		<link rel="stylesheet" id="jquery-ui-css" href="//<?php echo _FRONTEND_URL_; ?>/style/jquery-ui.css" />
		<link rel="stylesheet" id="core-css" href="//<?php echo _ROOTURL_ ; ?>/style/style.css" type="text/css" media="all">
		<link rel="stylesheet" id="responsive-css" href="//<?php echo _ROOTURL_ ; ?>/style/responsive.css" type="text/css" media="all">
		<link rel="stylesheet" id="circle-css" href="//<?php echo _ROOTURL_ ; ?>/style/circle.css" type="text/css" media="all">

		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
		<link rel="icon" href="/favicon.ico" type="image/x-icon">
	</head>
	<body id="page-body">
	<div id="scroll-to-top" style="display: block;"> 
      <a href="#" class="page-scroll dropup" data-element="#page-body">
      	<span class="scrolltop">
          <span>Top</span><span class="caret"></span>
      	</span>
      </a>
    </div>