<?php
include_once (__DIR__ . "/../email/Email.class.php");
include_once (__DIR__ . "/../misc/RandomStringGenerator.class.php");

class ResetPasswordTokenHandler
{
	private $email;
	private $mydb;
	private $randomStringGenerator;
	
	function __construct($mydb, $siteURL = false, $email = false, $randomStringGenerator = false) {
		
		if (!$siteURL) {
			$siteURL = get_site_URL();
		}
		
		if (!$email) {
			$email = new Email();
		}

		if (!$randomStringGenerator) {
			$randomStringGenerator = new RandomStringGenerator();
		}
		
		$this->email = $email;
		$this->mydb = $mydb;
		$this->siteURL = $siteURL;
		$this->randomStringGenerator = $randomStringGenerator;
	}
	
	public function setUp($forgetfulUser) {

		$token = $this->randomStringGenerator->generate(50);

		$this->updateDatabase($forgetfulUser, $token);
		$this->sendEmail($forgetfulUser, $token);
	}
	
	private function updateDatabase($forgetfulUser, $token) {
		$this->mydb->set_password_reset_token($token, $forgetfulUser->get_ID() , $forgetfulUser->get_Email());
	}
	
	private function sendEmail($forgetfulUser, $token) {
		
		$reset_link = $this->siteURL . "passwordreset/" . $token;
		
		$email_subject = "Roller Derby Test O'Matic password reset";
		
		$email_body = "Hello, <br />
	<br />
	To reset your Roller Derby Test O'Matic account (your log-in name is " . $forgetfulUser->get_Name() . ") password, go to the following URL:<br />
	<br />
	<a href='" . $reset_link . "'>" . $reset_link . "</a> <br />
	<br />
	You can either click the link, or copy the URL into your browser's address bar.<br />
	<br />
	If you didn't request to have your password reset then you can ignore this email. If you get this email a bunch of times then something is probably not right. If you're concerned about your account's security, please get in touch via contact@rollerderbytestomatic.fr.";
		
		$this->email->send($forgetfulUser->get_Email() , $email_subject, $email_body);
	}
}
