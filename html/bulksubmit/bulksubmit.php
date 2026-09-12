<?php
require_once __DIR__ . '/db_config.php';
session_start();
	
	if($_SESSION["authenticated"]!=true)
	{
		include 'index.php';
		exit();
	}
	
	if (!isset($_POST['urls']))    
	{    
	  include 'form.html.php'; 
	  exit();   
	}
	else if($_POST['urls'] == '')
	{
		echo "It doesn't look like you submitted anything.";
		include 'form.html.php';
		exit();
	} 	
	
	$db = getDbConnection();
	if (!$db)
	{
		$error = 'Cant connect to database.';
		include 'error.html.php';
		exit();
	}

	

	
	
	$urls = $_POST['urls'];
	$urls = db_escape_string($db, $_POST['urls']);
	$urls = str_replace("''", "%27", $urls);
	$urls = str_replace(":443", "", $urls);			
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

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['urls']))    
	{
		$i=0;
		$url="";
		$gotfirsturl=false;
		$sql = "INSERT INTO reviewqueue (url,worksafe) VALUES ";
		$urls=str_replace("\r","",$urls);
		$urls= $urls . "\n";
		$lenURLs=strlen($urls);
		while ($i < $lenURLs){
			if($urls[$i]!="\n"){
				$url = $url . $urls[$i];
			}else if($url != ''){
				$url = substr($url,0,400); //don't allow user to post a longer url than 400b 	
				$url = str_replace("/index.html", "/", $url);
				$url = str_replace("/index.htm", "/", $url);			
				if(strpos($url,'.') == false || strpos($url,' ') == true){
					echo "It doesn't look like you submitted a valid URL: '". $url ."'";
					include 'form.html.php';
					exit();					
				}				
				//add to SQL statement
				if($gotfirsturl==false){
					$sql = $sql . "('".$url."','".$worksafe."')";
					$gotfirsturl=true;
				}else{
					$sql = $sql . ",('".$url."','".$worksafe."')";
				}
				$url='';
			}
			$i++;
		}
		if (!db_query($db, $sql))   
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}
		
	    $output = 'Submission successful.';  

		include 'submit.html.php';    		
	}
?>
