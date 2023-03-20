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
		$delete = mysqli_real_escape_string($link, $_POST['delete']);
		
		$result = mysqli_query($link,"SELECT id, shard FROM windex WHERE url = '".$url."'");
		if ($result === false)  
		{
		  $error = 'Error: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}
		while($row = mysqli_fetch_array($result))
		{
			$idArray[] = $row['id'];
			$shardArray[] = $row['shard'];
		}
		
		$id = $idArray[0];
		$shard = $shardArray[0];	
		
		if($delete == 'on')
		{
			$sql = "DELETE FROM ws$shard WHERE id = $id AND url = '".$url."'";
		}
		else
		{
			$sql = "UPDATE ws$shard SET enable = 0 WHERE id = $id AND url = '".$url."'";
		}

		if (!mysqli_query($link, $sql))   
		{
		  $error = 'Error: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}		
		
		if($delete == 'on')
		{
			$sql = "DELETE FROM windex WHERE id = $id AND url = '".$url."'";
		}
		else
		{
			$sql = "UPDATE windex SET enable = 0 WHERE id = $id AND url = '".$url."'";
		}
		if (!mysqli_query($link, $sql))   
		{
		  $error = 'Error: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}		

	    $output = 'No errors '.  $url;

		include 'ban.html.php';       
	}
?>


