<?php
require_once __DIR__ . '/db_config.php';
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
		
		$db = getDbConnection();

		if (!$db)
		{
			$error = 'Cant connect to database.';
			include 'error.html.php';
			exit();
		}

		

		
 
	    $url = db_escape_string($db, $_POST['url']);
	    $url = str_replace("''", "%27", $url); 
	    $url_noprefix = str_ireplace("http://","", $url);
	    $url_noprefix = str_ireplace("https://","", $url);
	    $url_noprefix = str_ireplace("http://www.","", $url);
	    $url_noprefix = str_ireplace("https://www.","", $url);
		$title = db_escape_string($db, $_POST['title']);
		$tags = db_escape_string($db, $_POST['tags']);
		$description = db_escape_string($db, $_POST['description']);
		$body = db_escape_string($db, $_POST['body']);
		$http = db_escape_string($db, $_POST['http']);
		$surprise = db_escape_string($db, $_POST['surprise']);
		$worksafe = db_escape_string($db, $_POST['worksafe']);
    	$enable = db_escape_string($db, $_POST['enable']);
		$updatable = db_escape_string($db, $_POST['updatable']);
		$shard = db_escape_string($db, $_POST['shard']);

		if($shard == ""){
			$sql = "INSERT INTO windex (url,url_noprefix,title,tags,description,body,http,surprise,worksafe,enable,updatable,date,approver) 
	VALUES ('".$url."','".$url_noprefix."','".$title."','".$tags."','".$description."','".$body."','".$http."','".$surprise."','".$worksafe."','".$enable."','".$updatable."',now(),'".$_SESSION["user"]."')";
			if (!db_query($db, $sql))   
			{
			  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
			  include 'error.html.php';  
			  exit(); 
			}	
		}else{
			$sql = "INSERT INTO windex (url,url_noprefix,title,tags,description,body,http,surprise,worksafe,enable,updatable,date,shard,approver) 
	VALUES ('".$url."','".$url_noprefix."','".$title."','".$tags."','".$description."','".$body."','".$http."','".$surprise."','".$worksafe."','".$enable."','".$updatable."',now(),'".$shard."','".$_SESSION["user"]."')";
			if (!db_query($db, $sql))   
			{
			  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
			  include 'error.html.php';  
			  exit(); 
			}
			
			$result = db_query($db,"SELECT id FROM windex WHERE url = '".$url."'");
			if ($result === false)  
			{
			  $error = 'Error: ' . $db->errorInfo()[2];  
			  include 'error.html.php';  
			  exit(); 
			}	
			while($row = $result->fetch(PDO::FETCH_NUM))
			{
				$idArray[] = $row['id'];
			}	
			$id = $idArray[0];
						
			$sql = "INSERT INTO ws$shard (id,url,url_noprefix,title,tags,description,body,http,surprise,worksafe,enable,updatable,date,shard,approver) 
	VALUES ('".$id."','".$url."','".$url_noprefix."','".$title."','".$tags."','".$description."','".$body."','".$http."','".$surprise."','".$worksafe."','".$enable."','".$updatable."',now(),'".$shard."','".$_SESSION["user"]."')";
			if (!db_query($db, $sql))   
			{
			  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
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


