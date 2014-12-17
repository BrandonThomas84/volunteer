<?php

class navigation {

	public function __construct(){
		
		global $page, $page_class;

		//set the object scope page
		$this->page = $page;

		/**
		INCLUDE THE USER FUNCTIONS
		*/
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');
	}

	/**
	CREATES THE MENU ITEMS TO BE USED
	 */
	public function createMenu(){

		//get user info
		$userInfo = get_user( $_COOKIE['usrID'] );

		//menu item permissions
		$admin_menu = get_user_meta( $_COOKIE['usrID'],'perm_admin_menus');
		$admin_menu = ( ( $admin_menu ) ? $admin_menu['meta_value'] : 'false' );

		$settings_menu = get_user_meta( $_COOKIE['usrID'],'perm_settings_menu');
		$settings_menu = ( ( $settings_menu ) ? $settings_menu['meta_value'] : 'false' );
		
		$manage_menu = get_user_meta( $_COOKIE['usrID'],'perm_manage_menu');
		$manage_menu = ( ( $manage_menu ) ? $manage_menu['meta_value'] : 'false' );

		$manage_users_menu = get_user_meta( $_COOKIE['usrID'],'perm_manage_users_menu');
		$manage_users_menu = ( ( $manage_users_menu ) ? $manage_users_menu['meta_value'] : 'false' );
		
		$manage_events_menu = get_user_meta( $_COOKIE['usrID'],'perm_manage_events_menu');
		$manage_events_menu = ( ( $manage_events_menu ) ? $manage_events_menu['meta_value'] : 'false' );
		
		$create_event_menu = get_user_meta( $_COOKIE['usrID'],'perm_create_event_menu');
		$create_event_menu = ( ( $create_event_menu ) ? $create_event_menu['meta_value'] : 'false' );
		
		$manage_positions_menu = get_user_meta( $_COOKIE['usrID'],'perm_manage_positions_menu');
		$manage_positions_menu = ( ( $manage_positions_menu ) ? $manage_positions_menu['meta_value'] : 'false' );
		
		$create_position_menu = get_user_meta( $_COOKIE['usrID'],'perm_create_position_menu');
		$create_position_menu = ( ( $create_position_menu ) ? $create_position_menu['meta_value'] : 'false' );
		
		$manage_volunteers_menu = get_user_meta( $_COOKIE['usrID'],'perm_manage_volunteers_menu');
		$manage_volunteers_menu = ( ( $manage_volunteers_menu ) ? $manage_volunteers_menu['meta_value'] : 'false' );

		$create_volunteer_menu = get_user_meta( $_COOKIE['usrID'],'perm_create_volunteer_menu');
		$create_volunteer_menu = ( ( $create_volunteer_menu ) ? $create_volunteer_menu['meta_value'] : 'false' );

		
		//navigation array
		$nav_links = array();

		//add home
		$nav_links[0] = array(
			'href'=>'home',
			'name'=>'Home',
		);

		//check if user has admin menu access
		if( $userInfo['user_type'] == 'admin' || $manage_menu == 'true' ){
			
			//core manage menu item
			$nav_links[1] = array(
				'href'=>'#',
				'name'=>'Manage',
				'children'=>array(),
			);

			//overall events access
			if( $userInfo['user_type'] == 'admin' || $manage_events_menu == 'true' || $create_event_menu == 'true' ){

				//add the events label
				$nav_links[1]['children'][] = array('name'=>'Events','href'=>'#','divider_after'=>false,'is_label'=>true);

				//create event
				if( $userInfo['user_type'] == 'admin' || $create_event_menu == 'true' ){

					//add the new event link
					$nav_links[1]['children'][] = array(
							'name'=>'New Event',
							'href'=>'#',
							'divider_after'=>false,
							'is_label'=>false,
							'a_id' =>'nav-new-event',
							'modal' => true,
							'modal-body-id' => 'new-event-modal',
							'modal-action' => '/manage-event',
							'modal-body' => array(
								'event_name'=>array('type'=>'text','placeholder'=>'Event Name','value'=>null,'label'=>'Event Name','required'=>true),
								'new_event' => array('type'=>'hidden','placeholder'=>'','value'=>'true')
								),
							);
				}

				//manage events
				if( $userInfo['user_type'] == 'admin' || $manage_events_menu == 'true' ){

					//add the manage events link
					$nav_links[1]['children'][] = array('name'=>'Manage Events','href'=>'manage-event','divider_after'=>true,'is_label'=>false);
				}

			} // END EVENTS

			//manage positions
			if( $userInfo['user_type'] == 'admin' || $manage_positions_menu == 'true' || $create_position_menu == 'true' ){

				//add the label
				$nav_links[1]['children'][] = array('name'=>'Positions','href'=>'#','divider_after'=>false,'is_label'=>true);

				//create position
				if( $userInfo['user_type'] == 'admin' || $create_position_menu == 'true' ){
					
					//new position link
					$nav_links[1]['children'][] = array(
						'name'=>'New Position',
						'href'=>'#',
						'divider_after'=>false,
						'is_label'=>false,
						'a_id' =>'nav-new-position',
						'modal' => true,
						'modal-body-id' => 'new-position-modal',
						'modal-action' => '/manage-position/new',
						'modal-body' => array(
										'position_name' => array('type'=>'text','placeholder'=>'Postion Name','value'=>null,'label'=>'Position Name'),
										'position-submission' => array('type' => 'hidden','placeholder' => 'new','value'=>'true'),
										),
						);
				}

				//manage positions
				if( $userInfo['user_type'] == 'admin' || $manage_positions_menu == 'true' ){
					$nav_links[1]['children'][] = array('name'=>'Manage Positions','href'=>'manage-position','divider_after'=>true,'is_label'=>false);
				}
			} // END POSITIONS

			//manage volunteers
			if( $userInfo['user_type'] == 'admin' || $manage_volunteers_menu == 'true' || $create_volunteer_menu == 'true' ){
					
				//add the label
				$nav_links[1]['children'][] = array('name'=>'Volunteers','href'=>'#','divider_after'=>false,'is_label'=>true);

				//new volunteer
				if( $userInfo['user_type'] == 'admin' || $create_volunteer_menu == 'true' ){

					//create volunteer
					$nav_links[1]['children'][] = array(
						'name'=>'New Volunteer',
						'href'=>'#',
						'divider_after'=>false,
						'is_label'=>false,
						'a_id' =>'nav-new-volunteer',
						'modal' => true,
						'modal-body-id' => 'new-volunteer-modal',
						'modal-action' => '/manage-volunteer/new',
						'modal-body' => array(
										'settings-first_name' => array('type' => 'text','placeholder' => 'Enter first name','value'=>null,'label'=>'First Name'),
										'settings-last_name' => array('type' => 'text','placeholder' => 'Enter last name','value'=>null,'label'=>'Last Name'),
										'settings-email' => array('type' => 'email','placeholder' => 'your_email@you.com','value'=>null,'label'=>'Email'),
										)
						);
				}

				//manage volunteers
				if( $userInfo['user_type'] == 'admin' || $create_volunteer_menu == 'true' ){

					//manage volunteers
					$nav_links[1]['children'][] = array('name'=>'Manage Volunteers','href'=>'manage-volunteer','divider_after'=>false,'is_label'=>false);
				}
			} // END VOLUNTEERS
		} // END MANAGE MENU

		//empty array for all notifications
		$notis = array();

		//empty array for modal
		$noti_modal = array();

		//get notifications for user
		$r_notis = get_notifications_by_recipient( $_COOKIE['usrID'] );
		foreach( $r_notis as $msg ){
			$notis[] = $msg;
		}

		//empty array for notifications
		$n_types = array();
		$notis = array();

		//check to see if user can approve users
		$approve_user = get_user_meta( $_COOKIE['usrID'], 'approve_user');
		$approve_user = ( ( $approve_user ) ? $approve_user['meta_value'] : 'false' );

		//check if admin and add notification types
		if( $userInfo['user_type'] == 'admin' || $approve_user == 'true' ){
			$n_types[] = 'user_requests';
		}

		//if there are notifications to check for
		if( !empty( $n_types ) ){

			//get notifications for the n_types array
			foreach( $n_types as $n ){

				//get notifications by type
				$noti_content = get_notifications_by_type($n);
				foreach( $noti_content as $msg ){
					$notis[] = $msg;

					//convert notifications to modal body content
					$noti_modal[ $msg['id'] ] = array( 
						'type'=>'text', 
						'disabled'=>true, 
						'placeholder'=>'View More Info', 
						'value'=>$msg['summary'], 
						'button_addon'=>'X',
						'button_addon_type'=>'danger',
						'button_addon_class'=>'remove-notification', 
						'button_addon_data'=>array( 'notifi-id'=>$msg['id'] ),
					);
				}
			}
		}

		/*
		//notification count
		$noti_badge = count( $notis );
		
		//add messages
		$nav_links[] = array(
			'href'=>'#',
			'name'=>'Messages',
			'children'=>array(
				array(
					'name'=>'New Message',
					'href'=>'#',
					'divider_after'=>true,
					'is_label'=>false,
					'a_id' =>'nav-new-message',
					'modal' => true,
					'modal-body-id' => 'new-message-modal',
					'modal-action' => '/',
					'modal-body' => array(
									'message_type' => array(
										'type' => 'text',
										'placeholder' => 'Correspondence Type',
										'value'=>null,
										'label'=>'Correspondence Type',
									),
									'message_to' => array(
										'type' => 'text',
										'placeholder' => 'User Name',
										'value'=>null,
										'label'=>'Send To:',
									),
									'message_subject' => array(
										'type' => 'text',
										'placeholder' => 'Message Subject',
										'value'=>null,
										'label'=>'Message Subject',
									),
									'message_body' => array(
										'type' => 'text',
										'placeholder' => 'Message Body',
										'value'=>null,
										'label'=>'Message Body',
									),
								),
					),
				array(
					'name'=>'My Messages',
					'href'=>'my-messages',
					'divider_after'=>false,
					'is_label'=>false,
					'badge'=>10
					),
				array(
					'name'=>'Notifications',
					'href'=>'#',
					'divider_after'=>false,
					'is_label'=>false,
					'badge'=>$noti_badge,
					'modal'=>true,
					'modal-body-id' => 'notifications-modal',
					'modal-action' => $_SERVER['REQUEST_URI'],
					'modal-body' => $noti_modal,
					'save-button'=>'hide',
					'save-button-text'=>'',
					)
			)
		);
		*/

		//check if user has admin menu access
		if( $userInfo['user_type'] == 'admin' || $admin_menu == 'true' ){

			//add admin menu
			$nav_links[] = array(
				'href'=>'admin',
				'name'=>'Admin',
				'children'=>array(
					array(
						'name'=>'Settings',
						'href'=>'settings',
						'divider_after'=>false,
						'is_label'=>false
					),
					array(
						'name'=>'Manage Users',
						'href'=>'manage-users',
						'divider_after'=>false,
						'is_label'=>false
					),
				)
			);
		}

		//add profile
		$nav_links[] = array(
			'href'=>'#',
			'name'=>$_COOKIE['frstnm'] . ' ' . $_COOKIE['lstnm'],
			'children'=>array(
				array(
					'name'=>'My Profile',
					'href'=>'my-profile',
					'divider_after'=>false,
					'is_label'=>false,
					),
				/*array(
					'name'=>'Reporting',
					'href'=>'reporting',
					'divider_after'=>true,
					'is_label'=>false,
					),*/
				array(
					'name'=>'Logout',
					'href'=>'logout',
					'divider_after'=>false,
					'is_label'=>false,
					),
				array(
					'name'=>'View Sign Up Page',
					'href'=>_FRONTEND_URL_,
					'divider_after'=>false,
					'is_label'=>false,
					'include_root'=>false,
					),
			)
		);

		/*
		//add help
		$nav_links[] = array(
			'href'=>'help',
			'name'=>'Help',
		);
		*/


		$menu_html = '<ul class="nav navbar-nav">';
		foreach( $nav_links as $link ){
			//default active state
			$active = false;

			//check if is current_page
			if( $this->page == $link['href'] ){
				$active = true;
			}

			//check for children
			if( !empty( $link['children'] ) ){

				//ctart drop down menu
				$menu_html .= '<li class="dropdown' . $this->childActive( $link['children'] ) . '">';
				$menu_html .= '	<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . $link['name'] .' <span class="caret"></span></a>';
		        $menu_html .= '		<ul class="dropdown-menu" role="menu" style="padding-top: 0;">';

		        //loop through children links
		        foreach ($link['children'] as $child) {

		        	//check for id
		        	$a_id = null;
		        	if( !empty( $child['a_id'] ) ){
		        		$a_id = ' id="' . $child['a_id'] . '" ';
		        	} 
		        	$li_id = null;
		        	if( !empty( $child['li_id'] ) ){
		        		$li_id = ' id="' . $child['li_id'] . '" ';
		        	} 
		        	
		        	//check for label
		        	if( !empty( $child['is_label'] ) && $child['is_label'] ){
		        		$menu_html .= '			<li ' . $li_id . 'class="label">' . $child['name'] . '</li>';
		        	} else {

		        		//check for badge value
		        		if( !empty( $child['badge'] ) ){
		        			$badge = '<span class="badge">' . $child['badge'] . '</span>';
		        		} else {
		        			$badge = null;
		        		}

		        		//check for data modal toggle
		        		$modal = null;
		        		if( !empty( $child['modal'] ) ){

		        			//default button functions
		        			$hideSave = ' data-save-button="" ';
		        			$hideClose = ' data-close-button="" ';
		        			$saveText = ' data-save-button-text="" ';

		        			//check for button functions
		        			if( !empty( $child['save-button'] ) ){
		        				$hideSave = ' data-save-button="' . $child['save-button'] . '" ';
		        			}
		        			if( !empty( $child['close-button'] ) ){
		        				$hideClose = ' data-close-button="' . $child['close-button'] . '" ';
		        			}
		        			if( !empty( $child['save-button-text'] ) ){
		        				$saveText = ' data-save-button-text="' . $child['save-button-text'] . '" ';
		        			}

		        			$modal = ' data-toggle="modal" data-target="#navModal" data-title="' . $child['name'] . '" data-modal-body="' . $child['modal-body-id'] . '" data-modal-form-action="' . _ROOT_ . '' . $child['modal-action'] . '" ' . $hideSave . $hideClose . $saveText . ' class="modal-operator"';
		        		}

		        		//create link
		        		if( isset( $child['include_root'] ) && $child['include_root'] == false ){
		        			$href = _PROTOCOL_ . $child['href'];
		        			$target = "_blank";
		        		} else {
		        			$href = _ROOT_ . '/' . $child['href'];
		        			$target = "_self";
		        		}
		        		
		        		$menu_html .= '			<li ' . $li_id . 'class="' . $this->parentActive( $child['href'] ) . '"><a ' . $a_id . 'href="' . $href . '"' . $modal . ' target="' . $target . '">' . $child['name'] . $badge . '</a></li>';

		        		//check for modal content
		        		if( !empty( $child['modal'] ) ){
			        		$menu_html .= '<div class="hidden" id="' . $child['modal-body-id'] . '">';
		        			foreach( $child['modal-body'] as $id => $input ){

		        				$menu_html .= '<div class="input-group modal-input-group">';

		        				//get label
		        				if( !empty( $input['label'] ) ){
		        					$menu_html .= '<label class="form-label">' . $input['label'] . '</label>';
		        				}

		        				//get input addon
		        				if( !empty( $input['start_addon'] ) ){
		        					$menu_html .= '<span class="input-group-addon">' . $input['start_addon'] . '</span>';
		        				}

		        				//get disabled
		        				if( !empty( $input['disabled'] ) && $input['disabled'] == true ){
		        					$disabled = ' disabled ';
		        				} else {
		        					$disabled = null;
		        				}

		        				//get required
		        				if( !empty( $input['required'] ) && $input['required'] == true ){
		        					$required = ' required ';
		        				} else {
		        					$required = null;
		        				}

		        				//get input classes
		        				if( !empty( $input['input_class'] ) ){
		        					$inputClass = $input['input_class'];
		        				} else {
		        					$inputClass = 'form-control';
		        				}

		        				//form input
		        				$menu_html .= '<input id="' . $id . '" name="' . $id . '" type="' . $input['type'] . '" placeholder="' . $input['placeholder'] . '" value="' . $input['value'] . '" class="' . $inputClass . '"' . $disabled . $required . '>';

		        				//get input addon
		        				if( !empty( $input['button_addon'] ) ){

		        					//check for button type
		        					$buttonType = 'default';
		        					if( !empty( $input['button_addon_type'] ) ){
		        						$buttonType = $input['button_addon_type'];
		        					}

		        					//check for button class
		        					$buttonClass = null;
		        					if( !empty( $input['button_addon_class'] ) ){
		        						$buttonClass = $input['button_addon_class'];
		        					}

		        					//check for data
		        					$buttonData = '';
		        					if( !empty( $input['button_addon_data'] ) ){
		        						foreach( $input['button_addon_data'] as $key => $value ){
		        							//$buttonData .= ' data-' . $key . '="' . $value . '" ';
		        							$buttonData = $value;
		        						}
		        					}

		        					//add button to input
		        					$menu_html .= '<span class="input-group-btn">';
        							$menu_html .= '<button id="noti-rmv-' . $id . '" type="button" onclick="removeNotifications(' . $buttonData . ',\'noti-rmv-' . $id . '\')" class="btn btn-' . $buttonType . ' ' . $buttonClass . '" ' . $buttonData . '>' . $input['button_addon'] . '</button>';
      								$menu_html .= '</span>';
		        				}

		        				$menu_html  .= '</div><!-- END INPUT GROUP -->';
		        			}
		        			$menu_html .= '</div>';
		        		}
		        	}

		        	//check for divider
		        	if( !empty( $child['divider_after'] ) && $child['divider_after'] ){
		        		$menu_html .= '		    <li class="divider"></li>';
		        	}

		        }

		        //close dropdown
		        $menu_html .= '		</ul>';
				$menu_html .= '</li>';
			} else {
				$menu_html .= '<li class="' . $this->parentActive( $link['href'] ) . '"><a href="' . _ROOT_ . '/' . $link['href'] . '">' . $link['name'] . '</a></li>';
			}
		}
		$menu_html .= '</ul>';

		return $menu_html;
	}

	/**
	CHECKS A GROUPING OF CHILD LINKS TO SEE IF ANY OF THEM ARE THE CURRENT PAGE
	SETTING THE PARENT VARIABLE TO TRUE WILL CAUSE THE "ACTIVE" CLASS TO BE RETURNED, IF NOT THE
	 */
	public function childActive( $children=array() ){
		
		//default active state
		$active = false;

		//check array for matching current page
		if( !empty( $children ) ){
			foreach( $children as $child ){
				//if the href is equal to current page
				if( $child['href'] == $this->page ){
					$active = true;
				}
			}
		}

		if( $active ){
			return ' active ';
		} else {
			return null;
		}
	}

	/**
	CHECKS THE CURRENT LINK TO SEE IF IT IS THE ACTIVE PAGE
	 */
	public function parentActive( $href ){
		
		//default active state
		$active = false;

		if( $href == $this->page ){
			$active = true;
		}

		if( $active ){
			return ' active ';
		} else {
			return null;
		}
	}
}

?>

<header>
	<nav class="navbar navbar-default navbar-static-top" role="navigation">
		<div class="container-fluid">
		    <!-- Brand and toggle get grouped for better mobile display -->
		    <div class="navbar-header">
		      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
		        <span class="sr-only">Toggle navigation</span>
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		        <span class="icon-bar"></span>
		      </button>
		      <a class="navbar-brand" href="<?php echo _ROOT_; ?>">Volunteer Management</a>
		    </div>

		    <!-- Collect the nav links, forms, and other content for toggling -->
		    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		      <?php 
		      	  //outputs the menu items	
			      $nav = new navigation;
			      echo $nav->createMenu();

		      ?>
		      <form class="navbar-form navbar-right" role="search" method="GET">
		        <div class="form-group">
		          <!--<input type="text" name="s" class="form-control" placeholder="Search">-->
		        </div>
		        <!--<button id="searchButton" type="submit" class="btn btn-default glyphicon glyphicon-search"></button>-->
		      </form>
		    </div><!-- /.navbar-collapse -->
		  </div><!-- /.container-fluid -->
	</nav>
</header>
<?php
	//check if cooie is set
	if( isset( $_COOKIE['breadcrumb_show'] ) && $_COOKIE['breadcrumb_show'] == 1 ){
		$borderTop = null;
	} else {
		$borderTop = ' border-top';
	}
?>
<div id="main-body" class="container-fluid<?php echo $borderTop; ?>">
	<div id="main-body-inner" class="row-fluid">