<?php
require_once __DIR__ . '/db_config.php';
session_start();
	if($_SESSION["level"]!="admin")
	{
		echo "Access denied.";
		exit();
	}
	if($_SESSION["authenticated"]!=true)
	{
		include 'index.php';
		exit();
	}
	
	$db = getDbConnection();

	if (!$db)
	{
		$error = 'Cant connect to database.';
		include 'error.html.php';
		exit();
	}

	

	
	
	if (isset($_POST['startid']) && $_SESSION["loadfeedback"]==false)    
	{    
		$startID = db_escape_string($db, $_POST['startid']);
		$endID = db_escape_string($db, $_POST['endid']);
	}	

	$lim = 10000000000;
	
	if (isset($_POST['startid']) && $_SESSION["loadfeedback"]==false) //this is incase any new submissions are made during the review process, they will be ignored   
	{  
		$result = db_query($db,"SELECT * FROM feedback WHERE id >= '".$startID."' AND id <= '".$endID."'");
		if(!$result)
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}
	}
	else
	{
		$result = db_query($db,"SELECT * FROM feedback LIMIT $lim");
		if(!$result)
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}	
	}

	//lets put contents of index into an array
	while($row = $result->fetch(PDO::FETCH_NUM))
	{
		$id[] = $row['id'];
		$message[] = $row['message'];
		$time[] = $row['time'];
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['startid']) && $_SESSION["loadfeedback"]==false)    
	{    //remove selected feedback					
		$i=0;
		foreach($id as $pageid)
		{

			if($_POST["drop$pageid"] == 'on')
			{
				$result2 = db_query($db,"DELETE FROM feedback WHERE id = '".$pageid."'");
				if(!$result2)
				{
				  $error = 'Error deleting from feedback: ' . $db->errorInfo()[2];  
				  include 'error.html.php';  
				  exit(); 
				}
			}			
			$i++;			
		}
		$_SESSION["loadfeedback"]=true;
		unset($id);
		unset($message);
		unset($time);
		unset($startID);
		unset($endID);		
		unset($result);
		unset($result2);
		$db -> close();			
		include 'feedback.php';
		exit();
	}
	else
	{
		$_SESSION["loadfeedback"]=false;
		include 'form.html.php';
	}
?>


