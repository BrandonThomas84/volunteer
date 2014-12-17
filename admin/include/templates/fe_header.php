<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-10-08 12:29:22
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-09 14:41:13
 */

//check for URL vars
$ret_sup = '';
if( isset( $_GET['y']) ){
	$ret_sup .= '&y=' . $_GET['y'];
}
if( isset( $_GET['m']) ){
	$ret_sup .= '&m=' . $_GET['m'];
}
if( isset( $_GET['s']) ){
	$ret_sup .= '&s=' . $_GET['s'];
}

?>

<div class="header home light">
	<div class="logo"></div>
	<nav class="navbar navbar-default navbar-static-top" role="navigation">
		<div class="container-fluid">
			<!-- Brand and toggle get grouped for better mobile display -->
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"><span class="sr-only">Toggle navigation</span></button> <a class="navbar-brand" href="#">VMS</a>
			</div><!-- Collect the nav links, forms, and other content for toggling -->
			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				<ul class="nav navbar-nav pull-right">
				<?php 

					//if user is signed in
					if( checkForSession() ){
						echo '<li class="dropdown">';
						echo '	<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . $_COOKIE['frstnm'] . ' ' . $_COOKIE['lstnm'];
						echo '		<span class="caret"></span>';
						echo '	</a>';
						echo '	<ul class="dropdown-menu" role="menu" style="padding-top: 0;">';
						echo '		<li class=""><a href="' . _ROOT_ . '/my-profile" target="_self">My Profile</a></li>';
						echo '		<li class=""><a href="' . _ROOT_ . '/home" target="_self">My Positions</a></li>';
						echo '		<li class=""><a href="' . _ROOT_ . '/logout" target="_self">Logout</a></li>';
						echo '	</ul>';
						echo '</li>';

					} else {
						echo '<li class=""><a href="http://' .  _ROOTURL_ . '?ret=home' . $ret_sup . '">Sign In</a></li>';
						echo '<li class=""><a href="http://' .  _ROOTURL_ . '?user_type=vol&ret=home' . $ret_sup . '">Sign Up</a></li>';
					}

				?>
				</ul>
				<form class="navbar-form navbar-right" role="search" method="get">
					<div class="form-group">
						<!--<input type="text" name="s" class="form-control" placeholder="Search">-->
					</div><!--<button id="searchButton" type="submit" class="btn btn-default glyphicon glyphicon-search"></button>-->
				</form>
			</div><!-- /.navbar-collapse -->
		</div><!-- /.container-fluid -->
	</nav>
</div>