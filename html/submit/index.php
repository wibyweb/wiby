<?php
require_once __DIR__ . '/db_config.php';
session_start();
	if (!isset($_POST['url']))    
	{    
	  include 'form.html.php';    
	}
	else if($_POST['url'] == '' || strpos("x".$_POST['url'],'.') == false || strpos(trim("x".$_POST['url']),' ') == true)
	{
	  echo "It doesn't look like you submitted a valid URL.";
	  include 'form.html.php';
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
			  include 'form.html.php';
			  exit();

			}
		}


		$db = getDbConnection();

		if (!$db)
		{
			$error = 'Cant connect to database.';
			include 'error.html.php';
			exit();
		}

		

		
 
		$url = db_escape_string($db, $_POST['url']);
		$url = str_replace("''", "%27", $url);
		$url = str_replace(":443", "", $url);
		$url = trim($url);

		//$url = str_replace("\"", "\"\"", $url); //not needed if using single quotes for query
		$url = substr($url,0,400); //don't allow user to post a longer url than 400b (also limited in form)
		$worksafe = db_escape_string($db, $_POST['worksafe']);
		//$worksafe = str_replace("\"", "\"\"", $worksafe); 
		
		if($worksafe == 'on')
		{
			$worksafe = 1;
		}
		else
		{
			$worksafe = 0;
		}

		if(stripos("x".$url,'http://') == false and stripos("x".$url,'https://') == false)
		{
			$url = "http://" . $url;
		}
		$url = str_replace("/index.html", "/", $url);
		//$url = str_replace("/index.htm", "/", $url);//turns out cant reliably do the same for .htm

		$sql = "INSERT INTO reviewqueue (url,worksafe) VALUES ('".$url."','".$worksafe."')";


		if (!db_query($db, $sql))   
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}
		
	    	$output = 'Thank you for submitting '. $url;  

		include 'submit.html.php';       
	}
?>


