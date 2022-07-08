<?php    
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


		$link = mysqli_connect('localhost', 'guest', 'qwer');

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
 
		$feedback = str_replace("\'", "\'\'", $_POST['feedback']); //single quotes must be handled correctly
		$feedback = str_replace("\"", "\"\"", $feedback);//double quotes must be handled correctly
	    	//$feedback = mysqli_real_escape_string($link, $_POST['feedback']);//doesn't read back properly

		$feedback = substr($feedback,0,8000); //don't allow user to post a longer string than 8k (also limited in form)


		$sql = 'INSERT INTO feedback (message) VALUES ("'.$feedback.'")';


		if (!mysqli_query($link, $sql))   
		{
		  $error = 'Error fetching index: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}
		//Send thank you message which includes feedback
	    	$output = htmlspecialchars($_POST['feedback'], ENT_QUOTES, 'UTF-8');

		include 'submit.html.php';       
	}
?>


