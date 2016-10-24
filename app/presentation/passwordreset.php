<?php

/*
 * 1. User gives email of forgotten password account
 * 2. - Told if email was found, told they have 24 hours to click on it
 *    - Email token created, valid for 24 hours
 *    - Email sent including a token
 * 3. User has followed a link with a token in, if token is valid user prompted to enter a new password (twice, to check), token included in the form
 * 4. Token is correct, password is updated and the user is told to log in, email sent to user saying the password has been reset.
 *
 * 1. /passwordreset
 * 2. /passwordreset $_POST['forgottenemailform']
 * 3. /passwordreset/[code]
 * 4. /passwordreset/[code] $_POST['forgottenemailnewpasswordform']
 *
*/
if (UriPath::part(1)) {
	
	// is the token valid?
	if (!$mydb->is_valid_password_reset_token(UriPath::part(1))) {
		$error_string = "The link you followed is not valid.";
	}
}

// process the range of forms which may be submitted
if (!$error_string && ($_POST['forgottenemailform'] == "yes")) {
	if (is_logged_in()) {
		$error_string = "You need to log out before you can request a password reset.";
	} else {
		
		// step 2
		
		// fetch the forgetful user, will throw error if user not found
		
		// is the thing used an email address?
		if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			try {
				$forgetfulUser = $mydb->get_user_from_email($_POST['email']);
			}
			catch(Exception $e) {
				$error_string = "Sorry, that email address is not associated with any account. Please try again.";
			}
		} else {
			try {
				$forgetfulUser = $mydb->get_user_from_name($_POST['email']);
			}
			catch(Exception $e) {
				$error_string = "Sorry, that name is not associated with any account. Please try again.";
			}
		}
		
		// function to save the reset token in the database & email the user
		if ($forgetfulUser) {
			try {
				$resetPasswordTokenHandler = new ResetPasswordTokenHandler($mydb);
				$resetPasswordTokenHandler->setUp($forgetfulUser);
			}
			catch(Exception $e) {
				$error_string = "Sorry, we were not able to send you an email with your password rest info in. This could be caused by you not registering an email address on sign up (and, to be fair, the email bit is optional). If you think your account might not have an email address associated, send a message to contact@rollerderbytestomatic.fr and we can sort it out.";
			}
		}
	}
}

if (!$error_string && ($_POST['forgottenemailnewpasswordform'] == "yes")) {
	if (is_logged_in()) {
		$error_string = "You need to log out before you can request a password reset.";
	} else {
		try {
			
			// step 4
			// is the password valid?
			if ($_POST['password'] != $_POST['password2']) {
				throw new exception("The two passwords did not match, please try again.");
			}
			
			if (!$mydb->is_valid_password_reset_token($_POST['token'])) {
				throw new exception("The link you followed is not valid.");
			}
			
			// is the password valid
			if (strlen($_POST['password']) < 8) {
				throw new exception("You need to have a password which is 8 or more characters long.");
			}
			
			// ALL VALID!
			$forgetfulUser = $mydb->get_user_from_password_reset_token($_POST['token']);
			
			// remove the token
			$mydb->use_password_reset_token($_POST['token']);
			
			// update the password
			$mydb->set_user_password($forgetfulUser->get_ID() , $_POST['password']);
		}
		catch(Exception $e) {
			$error_string = $e->getMessage();
		}
	}
}

// show the page

set_page_subtitle("Turn left and forget your password.");
include ("header.php");

if (!UriPath::part(1)) {
	
	// step 1 or 2
	if (($_POST['forgottenemailform'] != "yes") || $error_string) {
		
		// step 1
		
		
?>

		<h3>To have your password reset, please enter your email address or user name. You'll be emailed details on what to do next.</h3>
		
		<form method="post" action="<?php
		echo get_site_URL(); ?>passwordreset" name="formforgottenemail">
			<input type="hidden"  name="forgottenemailform" id="forgottenemailform" value="yes">
			<p>
				Email or Name:<br />
				<input class="input_text" type="text" id="email" name = "email">
			</p>
			
			<p>
				<a class="button" onClick="document.formforgottenemail.submit()">Reset Password</a>
			</p>
		</form>
		<?php
	} else {
		
		// step 2
		
		
?>
		<h3>An email has been sent with instructions on how to reset your password.</h3>
		
		<p>The email will be valid for the next 24 hours. After this time if you've not reset your password you'll need to enter your email address again and have a new email sent.</p>
		<?php
	}
} else {
	
	// step 3 or 4
	if (($_POST['forgottenemailnewpasswordform'] != "yes") || $error_string) {
		
		// step 3
		// UriPath::part(1) is the password reset token
		
		
?>
		<h3>Please enter your new password:</h3>
		
		<form method="post" action="<?php
		echo get_site_URL(); ?>passwordreset/<?php
		echo UriPath::part(1); ?>" name="formforgottenemailnewpassword">
			<input type="hidden" name="forgottenemailnewpasswordform" id="forgottenemailnewpasswordform" value="yes">
			<input type="hidden" name="token" id="token" value="<?php
		echo UriPath::part(1); ?>">
			<p>
				Password:<br />
				<input class="input_text" type="password" id="password" name ="password">
			</p>
			<p>
				Re-enter your password:<br />
				<input class="input_text" type="password" id="password2" name ="password2">
			</p>
			
			<p>
				<a class="button" onClick="document.formforgottenemailnewpassword.submit()">Save Password</a>
			</p>
		</form>
		<?php
	} else {
		
		// step 4
		
		
?>
		<h3>Your password has been reset!</h3>

		<p>Hurray! You can <a href="<?php
		echo get_site_URL(); ?>profile">now log in</a> with your new password.</p>
		<?php
	}
}

include ("footer.php");
?>