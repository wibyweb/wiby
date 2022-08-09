<?php

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
		
		$link = mysqli_connect('localhost', 'approver', 'foobar');
		$user = mysqli_real_escape_string($link, $_POST['user']);
		$pass = mysqli_real_escape_string($link, $_POST['pass']);

		if (!$link)
		{
			$error = 'Cant connect to database.';
			include 'error.html.php';
			exit();
		}
		if (!mysqli_set_charset($link, 'utf8')) 
		{ 
		  $error = 'Unable to set database connection encoding.'; 
		  include 'error.html.php'; 
		  exit(); 
		}
		if(!mysqli_select_db($link, 'wiby'))
		{
		  $error = 'Unable to locate the database.'; 
		  include 'error.html.php'; 
		  exit(); 
		}
		$loginresult = mysqli_query($link,"SELECT hash, attempts, level FROM accounts WHERE name = '$user';");
		if(!$loginresult)
		{
		  $error = 'Error fetching index: ' . mysqli_error($link); 
		  include 'error.html.php'; 
		  exit(); 
		}

		//lets put contents of accounts into an array
		while($rowaccounts = mysqli_fetch_array($loginresult))
		{
			$hash[] = $rowaccounts['hash'];
			$attempts[] = $rowaccounts['attempts'];
			$level[] = $rowaccounts['level'];
		}
		if(password_verify($pass,$hash[0]) && $attempts[0] < 5 && $level[0] == "admin")
		{
			if($attempts[0]>0)
			{
				if (!mysqli_query($link, "UPDATE accounts SET attempts = '0' WHERE name = '$user';"))
				{
				  $error = 'Error fetching index: ' . mysqli_error($link);  
				  include 'error.html.php';  
				  exit(); 
				}				
			}
			
			$_SESSION["authenticated"] = true;
			$_SESSION["user"] = $user;
			$_SESSION["level"] = $level[0];
			include 'feedback.php';
			exit();
		}
		else{
			$attempt = $attempts[0] + 1;
			if (!mysqli_query($link, "UPDATE accounts SET attempts = '$attempt' WHERE name = '$user';"))
			{
			  $error = 'Error fetching index: ' . mysqli_error($link);  
			  include 'error.html.php';  
			  exit(); 
			}
			echo "It doesn't look like you submitted a valid username or password.";
			include 'login.html.php';
		}
	}
?>

