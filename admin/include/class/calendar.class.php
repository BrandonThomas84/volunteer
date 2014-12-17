<?php

class calendar {

	/**
	THE CONSTRUCT CREATES ALL THE CORE VARIABLES
	 */
	public function __construct($calType=null){

		require_once( __FUNCTION_INCLUDE__ . 'event_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'volunteer_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'position_functions.php');
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');

		//set object cal type
		if( empty( $calType ) ){
			$this->calType = 'admin';
		} else {
			$this->calType = $calType;
		}

		//year
		if( isset( $_GET['y'] ) ){
			$this->year = $_GET['y'];
		} else {
			$this->year = date('Y');
		}

		//month
		if( isset( $_GET['m'] ) ){
			$this->month = $_GET['m'];
		} else {
			$this->month = date('m');
		}

		//current day if on current month of current year
		if( $this->month == date('m') && $this->year == date('Y') ) {
			$this->day_num = date('d');
		} else {
			$this->day_num = null;
		}

		//set last month
		if( $this->month == 1 ){
			$this->last_month = 12;
			$this->last_month_year = $this->year - 1;
		} else {
			$this->last_month = $this->month - 1;
			$this->last_month_year = $this->year;
		}

		//set next month
		if( $this->month == 12 ){
			$this->next_month = 1;
			$this->next_month_year = $this->year + 1;
		} else {
			$this->next_month = $this->month + 1;
			$this->next_month_year = $this->year;
		}

		//get start weekday
		$this->start_day = date('w', strtotime( $this->year . '-' . $this->month . '-01') );

		//days in the month
		$this->total_days = date('t', strtotime( $this->year . '-' . $this->month . '-01') );

		//get end weekday
		$this->end_day = date('w', strtotime( $this->year . '-' . $this->month . '-' . $this->total_days ) );

		//last month total day count
		$this->last_total_days = date('t', strtotime( $this->last_month_year . '-' . $this->last_month . '-01') );

		//next month start day of week
		$this->next_start_days = date('w', strtotime( $this->next_month_year . '-' . $this->next_month . '-01') );

		//core url
		$this->core_url = explode( '?',$_SERVER['REQUEST_URI'] );
		$this->core_url = $this->core_url[0];

		//set the narrow cookie value
		if( $this->calType == 'admin' ){
			
			$cookie_name = 'admin_position_narrow';

		} elseif( $this->calType == 'frontend' ){

			$cookie_name = 'fe_position_narrow';
		}	

		//check for narrow cookie
		$narrows = array();		
		if( !empty( $_COOKIE[ $cookie_name ] ) ){
			
			//get and decode the narrows
			$narrows = json_decode( $_COOKIE[ $cookie_name ], true );
		}

		//set front end query directive
		if( $this->calType == 'frontend' ){

			$narrows['frontend'] = true;
		}

		//check for user id
		$this->user_id = 0;
		if( !empty( $_COOKIE['usrID'] ) ){
			$this->user_id = $_COOKIE['usrID'];
		}  

		
		
		//set date variations to month and year
		$narrows['search_date_start'] = $this->year . '-' . $this->month . '-01 00:00';
		$narrows['search_date_end'] = $this->year . '-' . $this->month . '-' . $this->total_days  . ' 23:59';

		//create the query from position functions
		$this->posQuery = create_narrowed_query( $narrows );

		//get the position values from the query and set as array
		$this->position_array = get_narrowed_positions( $this->posQuery );
	}

	/**
	COMBINES ALL DAYS INTO A MONTH
	 */
	private function createMonth(){

		//set the day counter to wrap weeks correctly
		$days_processed = 0;

		//start html
		$month_html = '<div class="row col-xs-11">';

		//check if month starts on Sunday to control visible days from previous month
		if( $this->start_day !== 0 ){

			//get the number of days to display
			$display_days = $this->last_total_days - $this->total_days;

			//loop through the alternate months ending days
			for( $i=0; $i < $this->start_day; $i++ ){

				//set the date number variable
				$date = $this->last_total_days - $this->start_day + ($i) + 1;

				//add the day to the calendar
				$month_html .= $this->createDay( $date, $this->last_month, $this->last_month_year, null, true );

				//increase the total days count
				$days_processed++;
			}
		}

		//loop through month
		for( $date=1; $date <= $this->total_days; $date++ ){

			//check if week is over and start a new row if so
			if( is_int( $days_processed / 7 ) && $days_processed > 0 ){
				$month_html .= '</div>';
				$month_html .= '<div class="row col-xs-11">';
			}

			//add the day to the calendar
			$month_html .= $this->createDay( $date, $this->month, $this->year, null );

			//increase the total days count
			$days_processed++;
		}

		//finish out the month with values from next month
		if( $this->end_day !== 6 ){

			//get the number of days to display
			$end_display_days = 6 - $this->end_day;

			//loop through the alternate months ending days
			for( $i=1; $i <= $end_display_days; $i++ ){

				//set the date number variable
				$date = $i;

				//add the day to the calendat
				$month_html .= $this->createDay( $date, $this->next_month, $this->next_month_year, null, true );
			}
		}

		$month_html .= '</div>';		

		return $month_html;		
	}

	/**
	CREATE INDIVIDUAL CALENDAR DAY
	 */
	private function createDay($date, $month, $year, $position_data, $alternate_month=false){

		//put positions into day
		$day_positions = null;

		//date strings
		$date_string = $month . '/' . $date . '/' . $year;
		$friendly_date = date('F jS, Y', strtotime( $date_string ) );
		$short_date = date('Y-m-d', strtotime( $date_string ) );
		$long_date = date('Y-m-d H:i:s A T', strtotime( $date_string ) );

		//verify there are any positions
		if( !empty( $this->position_array ) ){
			
			//loop through positions
			foreach( $this->position_array as $pos ){
				
				//set the position date information
				$pos_year = date('Y', $pos['date_start'] );
				$pos_month = date('m',  $pos['date_start'] );
				$pos_day = date('d',  $pos['date_start'] );

				$avail_roles = get_position_role_status( $pos['id'], 'unfilled');

				//add the positions to the day array if all data matches
				if( $pos_year == $year && $pos_month == $month && $pos_day == $date && $avail_roles > 0){
					$day_positions[] = $pos;
				} 
			}
		}

		//set the count of positions
		$positions = count( $day_positions );

		//check for alternate month to start the day block
		if( $alternate_month ){
			$day_html = '<div class="calendar-day alternate-month">';
			$show_options = false;
		} else {

			//check if day is today and add class
			$today = null;
			if( !empty( $this->day_num ) ){
				if( $this->day_num == $date ){
					$today = ' cal-today ';
				}
			}

			$dayClass = ' day-' . strtolower( date('l', strtotime($year . '-' . $month . '-' . $date ) ) );
			$day_html = '<div class="calendar-day' . $today . $dayClass . '">';
			$show_options = true;
		}

		//if there are more than 0 available positions
		if( $positions > 0 ){
			$label_type = 'success';
		} else {
			$label_type = 'danger';
			$show_options = false;
		}
		
		$day_html .= '  <div class="row">';
		$day_html .= '    <div class="col-xs-12 calendar-day-head"><small class="visible-lg-inline-block">' . date('l', strtotime( $year . '-' . $month . '-' . $date ) ) . '</small><span class="calendar-day-num">' . $date . '</span></div>';
		$day_html .= '  </div><!--close row-->';

		//insures options are only shown for the current month
		if( $show_options ){

			//position number
			$day_html .= '  <span class="label label-' . $label_type . ' avail-positions tooltips" data-toggle="tooltip" data-placement="top" title="' . $positions . ' Available Positions for ' . $friendly_date . '">' . $positions . '</span>';

			//check cal type for trigger
			if( $this->calType == 'admin' ){
				$trigger = ' vol-modal-trigger" ';
			} else {
				$trigger = '" onClick="showPositions(this)" ';
			}
			//modal button trigger
			$day_html .= '  <button class="btn btn-default btn-sm glyphicon glyphicon-plus ' . $trigger . ' data-toggle="modal" title="Click here to view the ' . $positions . ' volunteer oppportunities on this day" data-target="#volunteerModal" data-title="Positions for ' . date('m/d/Y', strtotime( $year . '-' . $month . '-' . $date ) ) . '" data-modal-body="modal-' . date('U', strtotime( $year . '-' . $month . '-' . $date ) ) . '" data-save-button="hide"></button>';

			//create modal body
			$day_html .= '<div class="hidden modal-' . date('U', strtotime( $year . '-' . $month . '-' . $date ) ) . '">' . $this->cal_modal_body( $day_positions ) . '</div>';
		} else {

			//previous month 
			$day_html .= '  <span class="label label-' . $label_type . ' avail-positions tooltips" data-toggle="tooltip" data-placement="top" title="There are no positions for ' . $friendly_date . '">' . $positions . '</span>';
		}
		$day_html .= '</div><!--close cal day-->';

		return $day_html;
	}
	/**
	CREATES THE POSITION DAY MODAL CONTENT 
	 */
	private function cal_modal_body( $positions ){

		//check for month and year selection
		$breadcrumb_add = '?bcsuppy=' . $this->year . '&bcsuppm=' . $this->month;
		
		//start table
		$html = '<div id="cal-day-modal-body" class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>Event</th><th>Name</th>';

		//change column names for front end
		if( $this->calType == 'admin' ){
			$html .= '<th>Status</th>';
		} else {
			$html .= '<th>Details</th>';
		}
		//close head row and start table body
		$html .= '</tr><tbody>';

		//lop through positions
		foreach( $positions as $pos ){
			
			//get event information
			$event = get_event( $pos['event_id'] );
			
			//check cal type
			if( $this->calType == 'admin' ){

				//get the status
				$status = get_position_meta_by_name( $pos['id'], 'status');
				$status = $status['meta_value'];
				
				//set the href link
				$href = _ROOT_ . '/manage-position/' . $pos['id'] . $breadcrumb_add;

				//create row that links to a new page
				$html .= '<tr><td><a href="' . $href . '">' . $event['name'] . '</a></td><td><a href="' . $href . '">' . $pos['name'] . '</a></td><td><a href="' . $href . '">' . $status . '</a></td></tr>';

			} else {

				//check for available positions
				$avail_roles = get_position_role_status( $pos['id'], 'unfilled');
				if( $avail_roles > 0 ){
				
					//create position details modal content
					$sub_modal = create_position_modal( $pos['id'] );
					
					//check for extra get info
					$submit_url = create_return_url( _PROTOCOL_ . _FRONTEND_URL_, array('m','y'), array('signup'=>'true','pos_id'=>$pos['id'], 'user_id'=>$this->user_id ) );

					//create row that triggers new modal content
					$html .= '<tr><td>' . $event['name'] . '</td><td>' . $pos['name'] . '</td><td><span class="btn btn-primary fe-vol-info-button" data-pos-id="' . $pos['id'] . '" data-position-title="' . $pos['name'] . '" onClick="showPositionInfo(this)" data-su-url="' . $submit_url . '">View Details</span><div id="modal-content-home-' . $pos['id'] . '" class="hidden">' . $sub_modal . '</div></td></tr>';
				}

			}
		}

		$html .= '</tbody></table></div>';

		return $html;
	}


	/**
	CREATES THE NEXT / PREV / CURRENT MONTH BUTTONS
	 */
	private function pagination(){
		//last month button
		$page_html = '<li class="previous col-sm-4 no-padd"><a href="' . $this->core_url . '?y=' . $this->last_month_year . '&m=' . $this->last_month . '">&larr;</a></li>';

		//current month button
		$page_html .= '<li class="col-sm-4"><a href="' . $this->core_url . '?y=' . date('Y') . '&m=' . date('m') . '">Current Month</a></li>';

		//next month button
		$page_html .= '<li class="next col-sm-4 no-padd"><a href="' . $this->core_url . '?y=' . $this->next_month_year . '&m=' . $this->next_month . '">&rarr;</a></li>';

		return $page_html;
	}

	/**
	DISPLAY PAGE OUTPUT
	 */
	public function get_calendar(){

		//verify license
		$verify = verifyLicense();

		//title
		$html = '<h2 class="bg-info text-center calendar-title">';
		$html .= ucfirst( date('F', strtotime( $this->year . '-' . $this->month . '-01') ) ) . ' ' . $this->year;
		$html .= '</h2>';

		//date specification
		$html .= '<div class="row">';
		$html .= '<div class="col-sm-3"><p class="h4 text-center">Go to specific date:</p></div>';
		$html .= '<div class="col-sm-6 row marg-b">';

		$html .= '	<div class="col-xs-12 input-group form-inline">';
		$html .= '		<span class="input-group-addon">Month</span>';
		$html .= '		<select id="goToDateInputM" class="form-control">';
		$html .= '			<option></option>';
		$html .= '			<option value="1"' . returnSelected($this->month, '1') . '>January</option>';
		$html .= '			<option value="2"' . returnSelected($this->month, '2') . '>February</option>';
		$html .= '			<option value="3"' . returnSelected($this->month, '3') . '>March</option>';
		$html .= '			<option value="4"' . returnSelected($this->month, '4') . '>April</option>';
		$html .= '			<option value="5"' . returnSelected($this->month, '5') . '>May</option>';
		$html .= '			<option value="6"' . returnSelected($this->month, '6') . '>June</option>';
		$html .= '			<option value="7"' . returnSelected($this->month, '7') . '>July</option>';
		$html .= '			<option value="8"' . returnSelected($this->month, '8') . '>August</option>';
		$html .= '			<option value="9"' . returnSelected($this->month, '9') . '>September</option>';
		$html .= '			<option value="10"' . returnSelected($this->month, '10') . '>October</option>';
		$html .= '			<option value="11"' . returnSelected($this->month, '11') . '>November</option>';
		$html .= '			<option value="12"' . returnSelected($this->month, '12') . '>December</option>';
		$html .= '		</select>';
		$html .= '		<span class="input-group-addon">Year</span>';
		$html .= '		<input id="goToDateInputY" type="year" class="form-control" value="' . $this->year . '">';
		$html .= '	</div>';
		$html .= '</div>';

		$html .= '<div class="col-sm-2"><button id="goToDate" data-url="' . $this->core_url . '" class="btn btn-info form-control">GO</button></div>';
		$html .= '</div>';

		//top pager
		$html .= '<ul class="pager">';
		$html .= $this->pagination();
		$html .= '</ul>';
		
		//add calendar
		$html .= '<div id="volunteer-calendar-container" class="container-fluid">';
		$html .= $this->createMonth();
		$html .= '</div>';

		//bottom page			
		$html .= '<div class="panel-footer">';
		$html .= '<ul class="pager">';
		$html .= $this->pagination();
		$html .= '</ul>';
		$html .= '</div>';		

		return $html;

	}

}

?>