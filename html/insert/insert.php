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
	}else{    
		
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
	    $url = str_replace("''", "%27", $url); 
	    $url_noprefix = str_ireplace("http://","", $url);
	    $url_noprefix = str_ireplace("https://","", $url);
	    $url_noprefix = str_ireplace("http://www.","", $url);
	    $url_noprefix = str_ireplace("https://www.","", $url);
		$title = mysqli_real_escape_string($link, $_POST['title']);
		$tags = mysqli_real_escape_string($link, $_POST['tags']);
		$description = mysqli_real_escape_string($link, $_POST['description']);
		$body = mysqli_real_escape_string($link, $_POST['body']);
		$http = mysqli_real_escape_string($link, $_POST['http']);
		$surprise = mysqli_real_escape_string($link, $_POST['surprise']);
		$worksafe = mysqli_real_escape_string($link, $_POST['worksafe']);
    	$enable = mysqli_real_escape_string($link, $_POST['enable']);
		$updatable = mysqli_real_escape_string($link, $_POST['updatable']);
		$shard = mysqli_real_escape_string($link, $_POST['shard']);

		if($shard == ""){
			$sql = "INSERT INTO windex (url,url_noprefix,title,tags,description,body,http,surprise,worksafe,enable,updatable,date,approver) 
	VALUES ('".$url."','".$url_noprefix."','".$title."','".$tags."','".$description."','".$body."','".$http."','".$surprise."','".$worksafe."','".$enable."','".$updatable."',now(),'".$_SESSION["user"]."')";
			if (!mysqli_query($link, $sql))   
			{
			  $error = 'Error fetching index: ' . mysqli_error($link);  
			  include 'error.html.php';  
			  exit(); 
			}	
		}else{
			$sql = "INSERT INTO windex (url,url_noprefix,title,tags,description,body,http,surprise,worksafe,enable,updatable,date,shard,approver) 
	VALUES ('".$url."','".$url_noprefix."','".$title."','".$tags."','".$description."','".$body."','".$http."','".$surprise."','".$worksafe."','".$enable."','".$updatable."',now(),'".$shard."','".$_SESSION["user"]."')";
			if (!mysqli_query($link, $sql))   
			{
			  $error = 'Error fetching index: ' . mysqli_error($link);  
			  include 'error.html.php';  
			  exit(); 
			}
			
			$result = mysqli_query($link,"SELECT id FROM windex WHERE url = '".$url."'");
			if ($result === false)  
			{
			  $error = 'Error: ' . mysqli_error($link);  
			  include 'error.html.php';  
			  exit(); 
			}	
			while($row = mysqli_fetch_array($result))
			{
				$idArray[] = $row['id'];
			}	
			$id = $idArray[0];
						
			$sql = "INSERT INTO ws$shard (id,url,url_noprefix,title,tags,description,body,http,surprise,worksafe,enable,updatable,date,shard,approver) 
	VALUES ('".$id."','".$url."','".$url_noprefix."','".$title."','".$tags."','".$description."','".$body."','".$http."','".$surprise."','".$worksafe."','".$enable."','".$updatable."',now(),'".$shard."','".$_SESSION["user"]."')";
			if (!mysqli_query($link, $sql))   
			{
			  $error = 'Error fetching index: ' . mysqli_error($link);  
			  include 'error.html.php';  
			  exit(); 
			}			
					
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
		$updatable . ' ' .
		$shard;  

		include 'insert.html.php';       
	}
?>


