<?php
require_once __DIR__ . '/db_config.php';
session_set_cookie_params(['samesite' => 'Strict']);
	session_start();

	if ( !isset($_POST['pass']) || !isset($_POST['user']))    
	{    
		include 'login.html.php';   
	}
	else if( $_POST['user'] == '' || $_POST['pass'] == '')
	{
		echo "It doesn't look like you submitted a valid username or password.";
		include 'login.html.php';
	}
	else
	{
		if(!isset($_SESSION["authenticated"]))
		{
			include_once $_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php';
			$securimage = new Securimage();
			if ($securimage->check($_POST['captcha_code']) == false) 
			{
			  echo "The security code entered was incorrect.";
			  include 'login.html.php';
			  exit();

			}
		}
	
		$db = getDbConnection();
		$user = db_escape_string($db, $_POST['user']);
		$pass = db_escape_string($db, $_POST['pass']);

		if (!$db)
		{
			$error = 'Cant connect to database.';
			include 'error.html.php';
			exit();
		}
		
		
		$loginresult = db_query($db,"SELECT hash, attempts FROM accounts WHERE name = '$user';");
		if(!$loginresult)
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2]; 
		  include 'error.html.php'; 
		  exit(); 
		}

		//lets put contents of accounts into an array
		while($rowaccounts = $loginresult->fetch(PDO::FETCH_NUM))
		{
			$hash[] = $rowaccounts['hash'];
			$attempts[] = $rowaccounts['attempts'];
		}
		if(password_verify($pass,$hash[0]) && $attempts[0] < 5)
		{
			if($attempts[0]>0)
			{
				if (!db_query($db, "UPDATE accounts SET attempts = '0' WHERE name = '$user';"))
				{
				  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
				  include 'error.html.php';  
				  exit(); 
				}				
			}
			
			$_SESSION["authenticated"] = true;
			$_SESSION["user"] = $user;
			include 'ban.php';
			exit();
		}
		else{
			$attempt = $attempts[0] + 1;
			if (!db_query($db, "UPDATE accounts SET attempts = '$attempt' WHERE name = '$user';"))
			{
			  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
			  include 'error.html.php';  
			  exit(); 
			}
			echo "It doesn't look like you submitted a valid username or password.";
			include 'login.html.php';
		}
	}
?>

