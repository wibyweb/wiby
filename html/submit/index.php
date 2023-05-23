<?php    
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
 
		$url = mysqli_real_escape_string($link, $_POST['url']);
		$url = str_replace("''", "%27", $url);
		$url = str_replace(":443", "", $url);
		$url = trim($url);

		//$url = str_replace("\"", "\"\"", $url); //not needed if using single quotes for query
		$url = substr($url,0,400); //don't allow user to post a longer url than 400b (also limited in form)
		$worksafe = mysqli_real_escape_string($link, $_POST['worksafe']);
		//$worksafe = str_replace("\"", "\"\"", $worksafe); 
		
		if($worksafe == 'on')
		{
			$worksafe = 1;
		}
		else
		{
			$worksafe = 0;
		}

		if(strpos("x".$url,'http://') == false and strpos("x".$url,'HTTP://') == false and strpos("x".$url,'https://') == false and strpos("x".$url,'HTTPS://') == false)
		{
			$url = "http://" . $url;
		}
		$url = str_replace("/index.html", "/", $url);
		$url = str_replace("/index.htm", "/", $url);

		$sql = "INSERT INTO reviewqueue (url,worksafe) VALUES ('".$url."','".$worksafe."')";


		if (!mysqli_query($link, $sql))   
		{
		  $error = 'Error fetching index: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}
		
	    	$output = 'Thank you for submitting '. $url;  

		include 'submit.html.php';       
	}
?>


