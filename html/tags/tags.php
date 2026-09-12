<?php
require_once __DIR__ . '/db_config.php';
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
		$db = getDbConnection();

		if (!$db)
		{
			$error = 'Cant connect to database.';
			include 'error.html.php';
			exit();
		}

		

		
		
		$url = db_escape_string($db, $_POST['url']);
		$status = "";
		
		if( isset($_POST['tags']))
		{			
			$tags = db_escape_string($db, $_POST['tags']);
			
			$result = db_query($db,"SELECT id, shard FROM windex WHERE url = '".$url."'");
			if ($result === false)  
			{
			  $error = 'Error: ' . $db->errorInfo()[2];  
			  include 'error.html.php';  
			  exit(); 
			}	
			while($row = $result->fetch(PDO::FETCH_NUM))
			{
				$idArray[] = $row['id'];
				$shardArray[] = $row['shard'];
			}	
			$id = $idArray[0];
			$shard = $shardArray[0];							
			
			if($tags==""){
				$result = db_query($db,"UPDATE windex SET tags = NULL WHERE id = $id AND url = '".$url."'");
			}
			else{
				$result = db_query($db,"UPDATE windex SET tags = '".$tags."' WHERE id = $id AND url = '".$url."'");
			}
			if ($result === false)   
			{
			  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
			  include 'error.html.php';  
			  exit(); 
			}
			
			if($tags==""){
				$result = db_query($db,"UPDATE ws$shard SET tags = NULL WHERE id = $id AND url = '".$url."'");
			}
			else{
				$result = db_query($db,"UPDATE ws$shard SET tags = '".$tags."' WHERE id = $id AND url = '".$url."'");
			}
			if ($result === false)   
			{
			  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
			  include 'error.html.php';  
			  exit(); 
			}	
					
			$status = "Update Completed";
			unset($_POST['tags']);
		}		
	    
		$result = db_query($db,"SELECT tags FROM windex WHERE url = '".$url."'");
		if ($result === false)  
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}

		while($row = $result->fetch(PDO::FETCH_NUM))
		{
			$tagsArray[] = $row['tags'];
		}
		
		$tags = $tagsArray[0];
		include 'tags.html.php';       
	}
?>


