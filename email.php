<?php

	# This will accept formdata in the form of the following:
# 	# POST = ['app_name' => 'nameOfYourApplication', 'secret' => 'hashedSecretRegisteredToYourApplication'
# 	, 'to', 'subject', 'body']
	# FIRST: remember to make sure php is sending mail with postfix properly!
#	ini_set( 'display_errors', 1);
#	error_reporting( E_ALL);
#	$from		=	"poe@powersofeight.com";
#	$to		=	"jdjohnson8883@gmail.com";
#	$subject	=	"PHP Mail Test Script";
#	$message	=	"This is just a test for PHP mail functionality";
#	$headers	=	"From:" . $from;
#	mail($to,$subject,$message, $headers);
#	echo "Test email sent to $to";
	# Now we need to come up with a secret way of authenticating this server
	# In order to prevent misuse from bad actors.  
	# Because this is for our project, for now, we can simply specify a file location
# for a secret and hardcode a secret to our liking
	define('FROM_CONFIG_PATH', '../API/email/config/from_address');

	$status = [
		'status'	 => 'ok',
		'errors' 	 => []

	];
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$app_name = isset($_POST['app_name']) ? trim($_POST['app_name']) : NULL;
		$secret   = isset($_POST['secret'])   ? trim($_POST['secret'])   : NULL;
		$to	  = isset($_POST['to'])	      ?	trim($_POST['to'])	 : NULL;
		$subject  = isset($_POST['subject'])  ? trim($_POST['subject'])  : NULL;
		$body     = isset($_POST['body'])     ? trim($_POST['body'])     : NULL;
			
		if ($app_name && $secret && $to && $subject && $body) {
			# Authenticate app name and secret
			$app_name = trim($app_name);
			$secret_file_location = "../API/email/api_clients/$app_name";
			if(file_exists($secret_file_location)) {
				$secret_hash = password_hash(trim(file_get_contents($secret_file_location)),PASSWORD_DEFAULT);
					if (password_verify($secret, $secret_hash)) {
						# This is where you actually send the email
						$from = file_get_contents(trim(FROM_CONFIG_PATH));
						$headers = "From:" . $from;
						# Sanitize the subject of any newline characters
						$subject = str_replace("\n","", $subject);
						# Wordwrap the body at no more than 70 characters
						$body = wordwrap($body, 70);
						$result = mail($to, $subject, $body, $headers);
						$status['status'] = 'email_sent';
					} else {
						$status['status'] = 'permission_denied';
						array_push($status['errors'], "App name and secret could not be verified"); 
					}
					
			} else {//	END OF if secret file exists
				$status['status'] = 'failed';
				array_push($status['errors'], "Could not locate secret file.
				       	Either the app name is incorrect or no secret has been registered");
			}//	END OF else secret was bad
		} else {//	END OF if all required fields are not null
			$status['status'] = 'incomplete_request';
			array_push($status['errors'], "One or more required fields were missing from the request");
		}//	END OF else didn't have all required fields
	} else {
		$status['status'] = 'bad_request_method';
		array_push($status['errors'], "The request method was wrong");
	}//	END OF else method is not post
	echo json_encode($status);
	exit();

?>
