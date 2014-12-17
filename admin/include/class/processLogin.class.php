<?php /* FILEVERSION: v1.0.1b */ ?>
<?php

class processLogin {

	//basic properties
	public $mysqli;
	public $pageMessage;
	public $returnMessage;

	//credential properties
	protected $username;
	protected $password;
	protected $dbPassword;
	protected $salt;
	protected $userType;
	protected $firstName;
	protected $lastName;
	protected $id;
	protected $email;
	

	public function __construct(){

		//starting db connection for querying
		global $mysqli;
		$this->mysqli = $mysqli;

		//include user functions
		require_once( __FUNCTION_INCLUDE__ . 'user_functions.php');

		//checking to make sure we have a secure connection
		$pageMessage = $this->checkForSubmission();

		return $pageMessage;

	}

	public function checkForSubmission(){

		//check for placeholder for login / forgot pass / logout
		if( isset( $_POST['processLogin'] ) ){

			//check for forgot password 
			if(isset($_POST['forgotPass'])){

				//send user email reminder and reset password
				forgotPasswordEmail( $_POST['username'] );

				//redirect back to the index page once login is complete
				header( 'Location: ' . _ROOT_ . '/' );

			} else {

				//checking for a login submission
				if(isset($_POST['username'])){

					$success = $this->verifyCredentials();

					//set the location
					$location = _ROOT_ . '/home';

					//check for return after login page
					if( isset( $_GET['ret'] ) ){
						if( $_GET['ret'] == 'home' ){
							$location = _PROTOCOL_ . _FRONTEND_URL_;
						} 
					}

					$location = create_return_url( $location );

					if( $success ){

						//sending back to the home page after complete with message
						header( 'Location: ' . $location );
					} 
				}
			}
		}

		//logout script
		if( !empty( $_GET['page'] ) ){

			if( $_GET['page'] == 'logout' ){

				//kill session
				$this->logout();

				//default return location
				$location = _ROOT_ . '/';

				//check for return URL
				if( isset( $_GET['ret'] ) ){
					if( $_GET['ret'] == 'home' ){
						$location = _PROTOCOL_ . _FRONTEND_URL_ . '/';
					}
				}

				$location = create_return_url( $location );

				add_page_message('success','You have successfully been logged out. See you soon!','Logged Out');
				
				//sending back to the home page
				header( 'Location: ' . $location );
			}
		}

		//check for create user placeholder
		if( isset( $_POST['newUser'] ) ){

			//check for user_type
			if( isset( $_POST['user-type'] ) ){
				$user_type = $_POST['user-type'];
				$location = '/manage-users#' . str_replace('_',null, $user_type) . '-user-cfg';
			} else {
				$user_type = 'pending_approval';
				$location = _ROOT_;
			}

			//checking for registration request
			$this->insertNewUser($_POST['username'], $_POST['password'], $_POST['password2'], $_POST['first_name'], $_POST['last_name'], $user_type);

			//add notification 
			$content = '<p><strong>First: </strong>' . $_POST['first_name'] . '</p><p><strong>Last: </strong>' . $_POST['last_name'] . '</p><p><strong>Email: </strong>' . $_POST['username'] . '</p>';
			$summary = $_POST['first_name'] . ' ' . $_POST['last_name'] . ' has requested access';
			update_notification('user_requests', $content, $_POST['username'], $summary );
					
			//redirect back to the index page once login is complete
			//header( 'Location: ' . $location );
		}

		//if nothing updates
		return false;

	} 

	public function verifyCredentials(){

		global $lockout_duration;

		//form submitted username value
		$username = $_POST['username'];

		//form submitted password value
		$password = $_POST['password'];	

		//preparing ststement prevents sql injection
		$stmt = $this->mysqli->prepare('SELECT `id`, `email`, `password`, `pwd_salt`, `first_name`, `last_name`, `user_type` FROM `' . _DB_NAME_ . '`.`users` WHERE email = ? AND `user_type` NOT IN (\'refused\',\'pending_approval\',\'inactive\')');

		//binding submitted username to prepared query
		$stmt->bind_param('s',$username);

		//executing prepared statement
		$stmt->execute();

		//store result
    	$stmt->store_result();

		//checking if user exists
		if($stmt->num_rows == 0){

			//setting the return message to cannot locate username
			add_page_message( 'warning', 'The email / username you provided was not found in our system. Please check your email and try again.','Invalid Email / Username');
			return false;

		} else {

			//binding results
			$stmt->bind_result($this->id, $this->email, $this->dbPassword, $this->salt, $this->firstName, $this->lastName, $this->userType);

			//fecthing bound data
			$stmt->fetch();

			//checking for successive failed attempts to prevent a brute force attack
			if($this->checkBruteForce($this->id) == true){

				$message_mins = $lockout_duration / 60; 

				//setting the return message as a brute force lockout
				add_page_message( 'danger', 'Due to too many failed attempts you have been locked out of your account for ' . $message_mins . ' minutes.','Account Lockout');
				return false;

			} else {

				//verifying the submitted password matches the salted DB passsword
				if($this->dbPassword !== md5($password . $this->salt)){

					//adding a failed attempt record to the login attempts table 
					$this->addAttemptValue($this->id);

					//setting the return message
					add_page_message( 'danger', 'The password you supplied does not match our records.','Incorrect Password');
					return false;

				} else {

					//starting session
					sessionInit();

					//check for remember me
					if( isset( $_POST['login-remember-me'] ) ){

						//check for option of remember time
						$r_time = get_option_by_name('remember_duration');

						if( !$r_time ){
							//default duration 30 days
							$r_time = time()+60*60*24*30;
						} else {
							$r_time = $r_time['option_value'];
						}
					} else {
						$r_time = 0;
					}

					//setting session cookies
					$_SESSION['usr'] = $this->email;
					$_SESSION['frstnm'] = $this->firstName;
					$_SESSION['lstnm'] = $this->lastName;
					$_SESSION['usrtyp'] = $this->userType;
					$_SESSION['usrID'] = $this->id;
					$_SESSION['admin_session'] = 'started';

					setcookie('usr', $this->email,$r_time,'/');
					setcookie('frstnm', $this->firstName,$r_time,'/');
					setcookie('lstnm', $this->lastName,$r_time,'/');
					setcookie('usrtyp', $this->userType,$r_time,'/');
					setcookie('usrID', $this->id,$r_time,'/');
					setcookie('admin_session',true,$r_time,'/');

					//check for password reset action
					if( $this->checkForReset() ){
						$_SESSION['requireResetPass'] = 'true';
					}				

					//successful login message
					add_page_message('success','Welcome Back!','Logged in');
					
					return true;
				}
			} 
		}
	}

	public function checkBruteForce($user_id){

		global $lockout_duration;

		//preparing ststement prevents sql injection
		$stmt = $this->mysqli->prepare('SELECT DISTINCT `id` FROM `' . _DB_NAME_ . '`.`login_attempts` WHERE `user_id` = ? AND `datetime` > (CURRENT_TIMESTAMP-' . $lockout_duration . ')');

		//binding submitted userid to prepared query
		$stmt->bind_param('i',$user_id);

		//executing prepared statement
		$stmt->execute();
		$stmt->store_result();

		//counting number of failed attempts
		if($stmt->num_rows > 5){

			//return true value
			return true;

		} else {

			//return false value
			return false;

		}
	}

	public function addAttemptValue($user_id){

		//get user IP address
		$user_ip = $_SERVER['REMOTE_ADDR'];

		//preparing ststement prevents sql injection
		$stmt = $this->mysqli->prepare('INSERT INTO `' . _DB_NAME_ . '`.`login_attempts` (`user_id`,`user_ip`) VALUES (?,?)'); 

		$stmt->bind_param('is',$user_id,$user_ip);
		$stmt->execute();
		$stmt->close();


	}

	public function passwordRecovery(){

		$email = $_POST['username'];

		if($this->checkExistingUser($email) <= 0){

			//if user doesnt exist in the database return an error message
			add_page_message('warning','The username / email that you supplied does not match any of our records.','Invalid Username / Email');
		} else {

			//assign object scope properties
			if($this->assignObjectProperties($email)){

				//updating database values
				$this->resetPasswordDB();

				$subject = _CO_NAME_ . " Password Recovery";
				$headers = "From: noreply" . _EMAIL_REGEX_;
				$message = "Your temporary password: \r\n" . $this->salt;
				$recovEmail = new mail($email,$subject,$headers,$message);
				$recovEmail->sendMessage();

				add_page_message('success','An email has been sent to you with instructions on how to change your password.','Password Recovery Email Sent');

			} else {

				add_page_message('danger','We\'re sorry an error occured while trying to update your profile. Please try back later.','Error');
			}			
		}
	}

	public function insertNewUser($email, $password, $password2, $first_name, $last_name, $user_type='pending_approval'){
		global $mysqli;

		//if there is a user in the database return the error message
		if($this->checkExistingUser($email) > 0){
			
			//return duplicate user error message
			add_page_message('warning','A user with that email address has already been created. Please have a user with administrator rights edit the user if you would like to make changes to their account.','Duplicate User');

		} else {

			//check if submitted passwords match
			if($password !== $password2){

				//return password error
				add_page_message('danger','The passwords that you entered did not match. Please carefully retry your request.','Password Mismatch');

			} else {

				$stmt = $this->mysqli->prepare('INSERT INTO `' . _DB_NAME_ . '`.`users` (`email`, `password`, `pwd_salt`, `first_name`, `last_name`, `user_type`) VALUES (?,?,?,?,?,?)');

				//setting salt value
				$salt =  generateRandomString();

				//preparing the password for insertion into the database by adding the salt and hashing with MD5
				$password = md5($password . $salt);

				//binding submitted information to prepared query
				$stmt->bind_param('ssssss',$email, $password, $salt, $first_name, $last_name, $user_type);

				//executing prepared statement
				$stmt->execute();

				//setting return message
				add_page_message('success','Your request for access has been received. Please watch your email for further instructions.','Access Requested');

				//get the created user ID
				$new_user_id = $this->mysqli->insert_id ;

				//create user perm records
				create_user_perms( $new_user_id );

				//set the user attributes
				create_user_attributes( $new_user_id );

			}
		}
	}

	public function logout(){

		setcookie('admin_session',false,-10000,'/');
		setcookie('usr',false,-10000,'/');
		setcookie('frstnm',false,-10000,'/');
		setcookie('lstnm',false,-10000,'/');
		setcookie('usrtyp',false,-10000,'/');
		setcookie('usrID',false,-10000,'/');
		setcookie('admin_position_view_type',false,-10000,'/');
		setcookie('breadcrumb_show',false,-10000,'/');

		//expiring session cookies
		if (ini_get("session.use_cookies")) {
		    $params = session_get_cookie_params();
		    setcookie(session_name(), '', time() - 42000,
		        $params["path"], $params["domain"],
		        $params["secure"], $params["httponly"]);
		}

		//kill session variables
		session_unset();
		
		//ending session
		@session_destroy();
	
	}

	public function checkExistingUser($email){
		//check if user is already registered
		$stmt = $this->mysqli->prepare('SELECT * FROM `' . _DB_NAME_ . '`.`users` WHERE `email` = ?');

		//binding submitted information to prepared query
		$stmt->bind_param('s',$email);

		//executing prepared statement
		$stmt->execute();
		$stmt->store_result();

		return $stmt->num_rows;
	}

	public function assignObjectProperties($username){
		//preparing ststement prevents sql injection
		$stmt = $this->mysqli->prepare('SELECT `id`, `email`, `password`, `pwd_salt`, `first_name`, `last_name`, `user_type` FROM `' . _DB_NAME_ . '`.`users` WHERE email = ? AND `user_type` NOT IN (\'refused\',\'pending_approval\',\'inactive\')');

		//binding submitted username to prepared query
		$stmt->bind_param('s',$username);

		//executing prepared statement
		$stmt->execute();

		//store result
    	$stmt->store_result();

    	//checking if user exists
		if($stmt->num_rows == 0){

			//setting the return message to cannot locate username
			$this->pageMessage = 'invalidUser';

		} else {

			//binding results
			$stmt->bind_result($this->id, $this->email, $this->dbPassword, $this->salt, $this->firstName, $this->lastName, $this->userType);

			//fecthing bound data
			$stmt->fetch();

			//binding results
			$stmt->bind_result($this->id, $this->email, $this->dbPassword, $this->salt, $this->firstName, $this->lastName, $this->userType);

			//fecthing bound data
			$stmt->fetch();
		}
	}

	public function resetPasswordDB(){

		$newPassword = md5($this->salt . $this->salt);

		$stmt = $this->mysqli->query('UPDATE `' . _DB_NAME_ . '`.`users` SET `password` = \'' . $newPassword . '\' WHERE `id` = ' . $this->id);

	}

	public function checkForReset(){

		$passwordVerify = md5($this->salt . $this->salt);

		$stmt = $this->mysqli->query('SELECT `id` FROM `' . _DB_NAME_ . '`.`users` WHERE `id` = ' . $this->id . ' AND `pwd_salt` = \'' . $passwordVerify . '\'');

		if( $stmt && $stmt->num_rows == 1 ){
			return true;
		} else {
			return false;
		}
	}
}
?>