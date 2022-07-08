<?php
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
	
	$urls = $_POST['urls'];
	$urls = mysqli_real_escape_string($link, $_POST['urls']);
	$urls = str_replace("\"", "\"\"", $urls);	
	$worksafe = mysqli_real_escape_string($link, $_POST['worksafe']);
	$worksafe = str_replace("\"", "\"\"", $worksafe);	
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
		$sql = 'INSERT INTO reviewqueue (url,worksafe) VALUES ';//("'.$url.'","'.$worksafe.'")';
		$gotURL=false;
		$urls=str_replace("\r","",$urls);
		$lenURLs=strlen($urls);
		while ($i < $lenURLs){
			if($urls[$i]!="\n"){
				$url = $url . $urls[$i];
			}else if($url != ''){
				$url = substr($url,0,400); //don't allow user to post a longer url than 400b (also limited in form)			
				$url = str_replace("/index.html", "/", $url);
				$url = str_replace("/index.htm", "/", $url);
			if(strpos($url,'.') == false || strpos($url,' ') == true){
				echo "It doesn't look like you submitted a valid URL: '". $url ."'";
				include 'form.html.php';
				exit();					
			}				
				//add to SQL statement
				if($gotfirsturl==false){
					$sql= $sql . '("'.$url.'","'.$worksafe.'")';
					$gotfirsturl=true;
				}else{
					$sql= $sql . ',("'.$url.'","'.$worksafe.'")';
				}
				$url='';
			}
			$i++;
		}
		if($url!=''){
			$url = substr($url,0,400); //don't allow user to post a longer url than 400b (also limited in form)			
			$url = str_replace("/index.html", "/", $url);
			$url = str_replace("/index.htm", "/", $url);
			if(strpos($url,'.') == false || strpos($url,' ') == true){
				echo "It doesn't look like you submitted a valid URL: '". $url ."'";
				include 'form.html.php';
				exit();					
			}			
			//add to SQL statement
			if($gotfirsturl==false){
				$sql = $sql . '("'.$url.'","'.$worksafe.'")';
				$gotfirsturl=true;
			}else{
				$sql = $sql . ',("'.$url.'","'.$worksafe.'")';
			}			
		}
		if (!mysqli_query($link, $sql))   
		{
		  $error = 'Error fetching index: ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}
		
	    $output = 'Submission successful.';  

		include 'submit.html.php';    		
	}
?>
