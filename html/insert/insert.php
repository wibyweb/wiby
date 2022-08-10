<?php    
	session_start();
	
	if($_SESSION["authenticated"]!=true)
	{
		include 'index.php';
		exit();
	}

	if (!isset($_POST['url']))    

	{    

	  include 'form.html.php';    

	}    

	else    

	{    
		$link = mysqli_connect('localhost', 'crawler', 'seekout');

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
//		$url = str_replace("\'", "\'\'", $_POST['url']);
//		$url = str_replace("\"", "\"\"", $url);
		$title = mysqli_real_escape_string($link, $_POST['title']);
//		$title = str_replace("\'", "\'\'", $_POST['title']);
//		$title = str_replace("\"", "\"\"", $title);
		$tags = mysqli_real_escape_string($link, $_POST['tags']);
//		$tags = str_replace("\'", "\'\'", $_POST['tags']);
//		$tags = str_replace("\"", "\"\"", $tags);
		$description = mysqli_real_escape_string($link, $_POST['description']);
//		$description = str_replace("\'", "\'\'", $_POST['description']);
//		$description = str_replace("\"", "\"\"", $description);
		$body = mysqli_real_escape_string($link, $_POST['body']);
//		$body = str_replace("\'", "\'\'", $_POST['body']);
//		$body = str_replace("\"", "\"\"", $body);
		$http = mysqli_real_escape_string($link, $_POST['http']);
//		$http = str_replace("\'", "\'\'", $_POST['http']);
//		$http = str_replace("\"", "\"\"", $http);
		$surprise = mysqli_real_escape_string($link, $_POST['surprise']);
//		$surprise = str_replace("\'", "\'\'", $_POST['surprise']);
//		$surprise = str_replace("\"", "\"\"", $surprise);
		$worksafe = mysqli_real_escape_string($link, $_POST['worksafe']);
//		$worksafe = str_replace("\'", "\'\'", $_POST['worksafe']);
//		$worksafe = str_replace("\"", "\"\"", $worksafe);
    	$enable = mysqli_real_escape_string($link, $_POST['enable']);
//		$enable = str_replace("\'", "\'\'", $_POST['enable']);
//		$enable = str_replace("\"", "\"\"", $enable);
		$updatable = mysqli_real_escape_string($link, $_POST['updatable']);
//		$updatable = str_replace("\'", "\'\'", $_POST['updatable']);
//		$updatable = str_replace("\"", "\"\"", $updatable);

		$sql = "INSERT INTO windex (url,title,tags,description,body,http,surprise,worksafe,enable,updatable,approver) 
	VALUES ('".$url."','".$title."','".$tags."','".$description."','".$body."','".$http."','".$surprise."','".$worksafe."','".$enable."','".$updatable."','".$_SESSION["user"]."')";


		if (!mysqli_query($link, $sql))   
		{
		  $error = 'Error fetching index: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}

	    	$output = 'No errors '.      
	    	$url . ' ' .    
		$title . ' ' . 
		$tags . ' ' . 
		$description . ' ' . 
		$body . ' ' . 
		$http . ' ' . 
		$surprise . ' ' . 
		$worksafe . ' ' . 
	    	$enable . ' ' .
		$updatable;  

		include 'insert.html.php';       
	}
?>


