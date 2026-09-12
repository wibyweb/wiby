<?php
require_once __DIR__ . '/db_config.php';
session_start();
	if (!isset($_POST['feedback']))    
	{    
	  include 'form.html.php';    
	}
	else if($_POST['feedback'] == '')
	{
	  echo "It doesn't look like you submitted anything.";
	  include 'form.html.php';
	} 

	else    

	{   
		include_once $_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php';
		$securimage = new Securimage();
		if ($securimage->check($_POST['captcha_code']) == false) 
		{
		  echo "The security code entered was incorrect.";
		  include 'form.html.php';
		  exit();

		}


		$db = getDbConnection();

		if (!$db)
		{
			$error = 'Cant connect to database.';
			include 'error.html.php';
			exit();
		}

		

		
 
		//$feedback = str_replace("\'", "\'\'", $_POST['feedback']); //single quotes must be handled correctly
		//$feedback = str_replace("\"", "\"\"", $feedback);//double quotes must be handled correctly
	    $feedback = db_escape_string($db, $_POST['feedback']);

		$feedback = substr($feedback,0,8000); //don't allow user to post a longer string than 8k (also limited in form)


		$sql = "INSERT INTO feedback (message) VALUES ('".$feedback."')";


		if (!db_query($db, $sql))   
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}
		//Send thank you message which includes feedback
	    	$output = htmlspecialchars($_POST['feedback'], ENT_QUOTES, 'UTF-8');

		include 'submit.html.php';       
	}
?>


