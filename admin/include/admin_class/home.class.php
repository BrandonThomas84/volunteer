<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-08-20 23:11:27
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-10-08 00:07:03
 */

class home {

	public function __construct(){

		require_once( __FUNCTION_INCLUDE__ . 'event_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'volunteer_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'position_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');

		$this->title = 'Home';
		$this->subtitle = 'Volunteer Management System';
		$this->pageMessage = null;

		//user info
		$this->user_info = get_user( $_COOKIE['usrID'] );

		//page meta and breadcrumb information
		$this->pageMeta[0] = array(
			'name' => 'Home',
			'url' => '/home',
			'meta_title' => 'VMS Home',
			'meta_description' => 'Easily manage your events, positions and volunteers from here',
		);
	}

	public function display(){

		//verify license
		$verify = verifyLicense();
		$html = '';

		//if user is at least super user
		if( check_user_level( 9 ) ){

			$html .= $this->admin_user_home();
			
		} 

		//add basic user info
		$html .= $this->basic_user_home();
		
		return $html;
	}

	public function admin_user_home(){

		$system_status = get_user_meta( $_COOKIE['usrID'],'perm_system_status');
		$system_status = ( ( $system_status ) ? $system_status['meta_value'] : 'false' );

		$system_metrics = get_user_meta( $_COOKIE['usrID'],'perm_system_metrics');
		$system_metrics = ( ( $system_metrics ) ? $system_metrics['meta_value'] : 'false' );

		$html = '<div id="home-primary" class="col-xs-12">';

		//if user has access to syatem status
		if( $this->user_info['user_type'] == 'admin' || $system_status == 'true' ){

			//system status section
			$html .= '<div class="row-fluid">';
			$html .= '<h2 class="page-header">System Status</h2>';
			
			$html .= '<p class="help-block">Below you will find the total counts of each of the different system elements. To view more information about a given element click on the name.</p>';

			$events = get_events();
			if( $events ){ $event_count = count( $events ); } else { $event_count = 0; }
			$html .= '<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
			$html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/manage-event/">Events</a></p>';
			$html .= '<div class="well info-tile">' . $event_count . '</div>';
			$html .= '</div>';	

			$positions = get_positions();
			if( $positions ){ $pos_count = count( $positions ); } else { $pos_count = 0; }
			$html .= '<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
			$html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/manage-position/">Positions</a></p>';
			$html .= '<div class="well info-tile">' . $pos_count . '</div>';
			$html .= '</div>';	

			$roles = get_all_role_count();
			$html .= '<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
			$html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/manage-position/">Roles</a></p>';
			$html .= '<div class="well info-tile">' . $roles . '</div>';
			$html .= '</div>';	

			$volunteers = get_volunteers(null,' WHERE  `user_id` != 0 ');
			if( $volunteers ){ $vol_count = count( $volunteers ); } else { $vol_count = 0; }
			$html .= '<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
			$html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/manage-volunteer/">Volunteers</a></p>';
			$html .= '<div class="well info-tile">' . $vol_count . '</div>';
			$html .= '</div>';

			$users = get_users();
			if( $users ){ $usr_count = count( $users ); } else { $usr_count = 0; }
			$html .= '<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
			$html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/manage-users/">Users</a></p>';
			$html .= '<div class="well info-tile">' . $usr_count . '</div>';
			$html .= '</div>';		

			//close row
			$html .= '</div><div class="clearfix"></div>';
		}

		//if user has access to syatem metrics
		if( $this->user_info['user_type'] == 'admin' || $system_metrics == 'true' ){

			//system metrics
			$html .= '<div class="row-fluid">';
			$html .= '<h2 class="page-header">System Metrics</h2>';

			//positions filled
			$filled = get_volunteers('user_id',' WHERE `status` = \'confirmed\'');
			if( $filled ){
				$filled_pct = count( $filled ) / $roles;
				$filled_pct = $filled_pct * 100;
				$tooltip = count($filled) . ' / ' . $roles . ' Positions have been filled';
			} else {
				$filled_pct = 0;
				$tooltip = null;
			}
			
			//assign color to fill
			if( $filled_pct < 30 ){
				$color = 'red';
			}elseif( $filled_pct < 60 ){
				$color = 'orange';
			} else {
				$color = 'green';
			}
			$html .= '<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
			$html .= progressMeter($filled_pct,'%',$color,'dark','Confirmed Positions<br>' . count($filled) . ' / ' . $roles,'big', $tooltip);
			$html .= '</div>';

			//unconfirmed positions
			$unconfirmed = get_volunteers('user_id',' WHERE `status` IN (\'unconfirmed\')');
			if( $unconfirmed ){
				$unconfirmed_pct = count($unconfirmed) / $roles;
				$unconfirmed_pct = $unconfirmed_pct * 100;
				$tooltip = count($unconfirmed) . ' / ' . $roles . ' Positions are unconfirmed';
			} else {
				$unconfirmed_pct = 0;
				$tooltip = null;
			}
			
			//assign color to fill
			if( $unconfirmed_pct > 90 ){
				$color = 'red';
			}elseif( $unconfirmed_pct > 40 ){
				$color = 'orange';
			} else {
				$color = 'green';
			}
			$html .= '<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
			$html .= progressMeter($unconfirmed_pct,'%',$color,'dark','Unconfirmed Positions<br>' . count($unconfirmed) . ' / ' . $roles,'big', $tooltip);
			$html .= '</div>';

			//unfilled positions
			$unfilled = get_volunteers('user_id',' WHERE `status` IN (\'unfilled\')');
			if( $unfilled ){
				$unfilled_pct = count($unfilled) / $roles;
				$unfilled_pct = $unfilled_pct * 100;
				$tooltip = count($unfilled) . ' / ' . $roles . ' Positions are unfilled';
			} else {
				$unfilled_pct = 0;
				$tooltip = null;
			}
			
			//assign color to fill
			if( $unfilled_pct > 80 ){
				$color = 'red';
			}elseif( $unfilled_pct > 40 ){
				$color = 'orange';
			} else {
				$color = 'green';
			}
			$html .= '<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
			$html .= progressMeter($unfilled_pct,'%',$color,'dark','Unfilled Positions<br>' . count($unfilled) . ' / ' . $roles,'big', $tooltip);
			$html .= '</div>';


			//close row
			$html .= '</div>';
			$html .= '<div class="clearfix"></div>';
		}
		
		//end primary container
		$html .= '</div><!-- CLOSE HOME PRIMARY-->';

		return $html;
	}

	public function basic_user_home(){

		$html = '<div id="home-primary" class="col-xs-12">';
		$html .= '	<h2>My Upcoming Positions</h2>';
		$html .= '	<p class="help-block">You can view any of your upcoming positions by clicking on their respective titles.</p>';

		//get position info
		$unfilled = get_volunteers(null, ' WHERE `user_id` = 0 ');
		$my_positions = get_volunteers(null, ' WHERE `user_id` = ' . $_COOKIE['usrID'] );
		$less60 = array();
		$less30 = array();
		$less14 = array();
		$less7 = array();
		$confirmed = array();
		$unconfirmed = array();

		//get upcoming positions
		if( $my_positions ){
			foreach( $my_positions as $pos ){

				//day in seconds
				$day = 86400;

				//get position info
				$position_info = get_position( $pos['position_id'] );

				//check dates
				if( $position_info['date_start'] < date('U', time() + ( $day * 7 ) ) ){ //if within 1 week
					$less7[] = $position_info;
				}elseif( $position_info['date_start'] < date('U', time() + ( $day * 14 )) ){ //if within 2 weeks
					$less14[] = $position_info;
				}elseif( $position_info['date_start'] < date('U', time() + ( $day * 30 ) ) ){ //if within 30 days
					$less30[] = $position_info;
				}elseif( $position_info['date_start'] < date('U', time() + ( $day * 60 ) ) ){ //if within 30 days
					$less60[] = $position_info;
				}

				//check status
				if( $pos['status'] == 'unconfirmed' ){
					$unconfirmed[] = $position_info;
				}
			}
		}

		//this week
		$html .= '	<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
		if( empty( $less7 ) ){ $html .= '<p class="h4 text-center tooltips" title="You do not have any volunteer positions within the next 7 days">This Week</p>'; } else { $html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/my-positions/?t=7" class="tooltips" title="Click to view positions you have signed up for">This Week</a></p>'; }
		$html .='		<div class="well info-tile">' . count($less7) . '</div>';
		$html .= '	</div>';

		//within 2 weeks
		$html .= '	<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
		if( empty( $less14 ) ){ $html .= '<p class="h4 text-center tooltips" title="You do not have any volunteer positions within the next 2 weeks">Next Week</p>'; } else { $html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/my-positions/?t=14" class="tooltips" title="Click to view positions you have signed up for">Next Week</a></p>'; }
		$html .='		<div class="well info-tile">' . count($less14) . '</div>';
		$html .= '	</div>';

		//within 30 days
		$html .= '	<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
		if( empty( $less30 ) ){ $html .= '<p class="h4 text-center tooltips" title="You do not have any volunteer positions within the next 30 days">Within 30 Days</p>'; } else { $html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/my-positions/?t=30" class="tooltips" title="Click to view positions you have signed up for">Within 30 Days</a></p>'; }
		$html .='		<div class="well info-tile">' . count($less30) . '</div>';
		$html .= '	</div>';

		//within 60 days
		$html .= '	<div class="col-xs-10 col-xs-offset-1 col-sm-4 col-sm-offset-0 col-md-3 col-md-offset-0 col-lg-2 col-lg-offset-0">';
		if( empty( $less60 ) ){ $html .= '<p class="h4 text-center tooltips" title="You do not have any volunteer positions within the next 60 days">Within 60 Days</p>'; } else { $html .= '<p class="h4 text-center"><a href="' . _ROOT_ . '/my-positions/?t=60" class="tooltips" title="Click to view positions you have signed up for">Within 60 Days</a></p>'; }
		$html .='		<div class="well info-tile">' . count($less60) . '</div>';
		$html .= '	</div>';
		$html .= '<div class="clearfix"></div>';

		//requires confirmation
		$html .= '	<h2>My Positions Awaiting Confirmation</h2>';
		$html .= '	<p class="help-block">The following are positions that you have signed up for and have yet to confirm. Please be sure that you confirm these positions before the position start date.</p>';

		//start unconfirmed table
		$html .= '<div class="table-responsive table-fluid">';
		$html .= '	<table class="table table-striped table-hover">';
		$html .= '		<thead>';
		$html .= '			<tr>';
		$html .= '				<th>#</th>';
		$html .= '				<th>Position Name</th>';
		$html .= '				<th>Start / End</th>';
		$html .= '				<th>Lead</th>';
		$html .= '				<th>Confirm</th>';
		$html .= '			</tr>';
		$html .= '		</thead>';
		$html .= '		<tbody>';

		//create rows
		$i=1;
		if( !empty( $unconfirmed ) ){
			foreach( $unconfirmed as $pos ){

				//set the friendly date
				$pos_date = date('l jS \of F Y h:i A', $pos['date_start'] ) . ' - ' . date('h:i A', $pos['date_end'] );

				//set the role lead
				$lead = get_position_meta_by_name( $pos['id'], 'lead');
				$lead = get_user( $lead['meta_value'] );
				$lead = $lead['first_name'] . ' ' . substr($lead['last_name'], 0, 1);

				$html .= '<tr class="danger">';
				$html .= '	<td>' . $i . '</td>';
				$html .= '	<td>' . $pos['name'] . '</td>';
				$html .= '	<td>' . $pos_date . '</td>';
				$html .= '	<td>' . $lead . '</td>';
				$html .= '	<td><a class="btn btn-success" href="' . _ROOT_ . '/my-positions/' . $pos['id'] . '/?confirm=true">Confirm</a></td>';
				$html .= '</tr>';

				//increase counter
				$i++;
			}
		} else {
			$html .= '<tr class="success"><td colspan="5">No Positions are awaiting confirmation.</td></tr>';
		}

		//end table and container
		$html .= '</tbody></table></div>';


		//close primary container
		$html .= '</div><!-- CLOSE PRIMARY -->';

		return $html;
	}
}
?>