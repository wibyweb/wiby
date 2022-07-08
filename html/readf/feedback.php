<?php
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

	if (isset($_POST['startid']) && $_SESSION["loadfeedback"]==false)    
	{    
		$startID = $_POST['startid'];
		$endID = $_POST['endid'];
	}	
	$link = mysqli_connect('localhost', 'approver', 'foobar');

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

	$lim = 10000000000;
	
	if (isset($_POST['startid']) && $_SESSION["loadfeedback"]==false) //this is incase any new submissions are made during the review process, they will be ignored   
	{  
		$result = mysqli_query($link,"SELECT * FROM feedback WHERE id >= $startID AND id <= $endID");
		if(!$result)
		{
		  $error = 'Error fetching index: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}
	}
	else
	{
		$result = mysqli_query($link,"SELECT * FROM feedback LIMIT $lim");
		if(!$result)
		{
		  $error = 'Error fetching index: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}	
	}

	//lets put contents of index into an array
	while($row = mysqli_fetch_array($result))
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
				$result2 = mysqli_query($link,"DELETE FROM feedback WHERE id = $pageid");
				if(!$result2)
				{
				  $error = 'Error deleting from feedback: ' . mysqli_error($link);  
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
		$link -> close();			
		include 'feedback.php';
		exit();
	}
	else
	{
		$_SESSION["loadfeedback"]=false;
		include 'form.html.php';
	}
?>


