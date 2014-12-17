<?php
/**
 * @Author: Brandon Thomas
 * @Date:   2014-09-23 22:19:47
 * @Last Modified by:   Brandon Thomas
 * @Last Modified time: 2014-09-24 00:10:52
 */

/**
MANAGES ALL MESSAGING FOR THE VOLUNTEER SYSTEM	
*/
class mail {
	public function __construct(){

		//set mail header var
		$this->headers = '';
		$this->to = 'test' . _EMAIL_REGEX_;
		$this->subject = _DEFAULT_TITLE_ . ' Test Email';
		$this->message = 'This is a test email from the ' . _CO_NAME_ . _DEFAULT_TITLE_;
	}

	/**
	VALIDATES EMAIL ADDRESS
	*/
	public function spamcheck( $field ) {
	  // Sanitize e-mail address
	  $field=filter_var($field, FILTER_SANITIZE_EMAIL);
	  // Validate e-mail address
	  if(filter_var($field, FILTER_VALIDATE_EMAIL)) {
	    return TRUE;
	  } else {
	    return FALSE;
	  }
	}

	/**
	SETS HTML HEADER
	*/
	public function set_headers($from=_CS_EMAIL_,$replyto=_CS_EMAIL_,$cc=array(),$bcc=array(),$type='html'){

		//add recipient
		$this->headers .= "From: " . strip_tags($from) . "\r\n";
		$this->headers .= "Reply-To: ". strip_tags($replyto) . "\r\n";

		//check for html email
		if( $type == 'html'){			
			$this->headers .= "MIME-Version: 1.0\r\n";
			$this->headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		}

		//check for cc
		if( !empty( $cc ) ){
			foreach( $cc as $email ){
				$this->headers .= "CC: " . $email . "\r\n";
			}
		}

		//check for bcc
		if( !empty( $bcc ) ){
			foreach( $bcc as $email ){
				$this->headers .= "BCC: " . $email . "\r\n";
			}
		}

	}

	/**
	SEND MESSAGE
	*/
	public function send_message(){

		//validate email
		$to = $this->spamcheck( $this->to );
		if( $to ){

			//if in dev mode change email address
			if( check_dev_mode() ){
				$this->to = _ADMIN_EMAIL_;
			}

			//send message
			$email = mail($this->to, $this->subject, $this->message, $this->headers);

			return $email;
		} else {
			return false;
		}
	}

}

?>