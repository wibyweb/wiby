<?php    
	session_start();
	
	if($_SESSION["authenticated"]!=true)
	{
		include 'index.php';
		exit();
	}

	if (!isset($_POST['url']) && !isset($_POST['tags']))    
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
		$status = "";
		
		if( isset($_POST['tags']))
		{
			$tags = mysqli_real_escape_string($link, $_POST['tags']);
			if($tags==""){
				$result = mysqli_query($link,'UPDATE windex SET tags = NULL WHERE url = "'.$url.'";');
			}
			else{
				$result = mysqli_query($link,'UPDATE windex SET tags = "'.$tags.'" WHERE url = "'.$url.'";');
			}
			if ($result === false)   
			{
			  $error = 'Error fetching index: ' . mysqli_error($link);  
			  include 'error.html.php';  
			  exit(); 
			}
			$status = "Update Completed";
			unset($_POST['tags']);
		}		
	    
		$result = mysqli_query($link,'SELECT tags FROM windex WHERE url = "'.$url.'";');
		if ($result === false)  
		{
		  $error = 'Error fetching index: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}

		while($row = mysqli_fetch_array($result))
		{
			$tagsArray[] = $row['tags'];
		}
		
		$tags = $tagsArray[0];
		include 'tags.html.php';       
	}
?>


