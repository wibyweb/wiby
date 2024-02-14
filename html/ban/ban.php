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
	
	$urls = $_POST['urls'];
	$urls = mysqli_real_escape_string($link, $_POST['urls']);
	$urls = str_replace("''", "%27", $urls);
	$urls = str_replace(":443", "", $urls);				 
	$delete = mysqli_real_escape_string($link, $_POST['delete']);
	if($delete == 'on')
	{
		$delete = 1;
	}
	else
	{
		$delete = 0;
	}	

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['urls']))    
	{
		$i=0;
		$url="";
		$urls=str_replace("\r","",$urls);
		$urls = $urls . "\n";
		$lenURLs=strlen($urls);
		
		while ($i < $lenURLs){
			if($urls[$i]!="\n"){
				$url = $url . $urls[$i];		
			}else if($url != ''){
				
				$url = substr($url,0,400); //don't allow user to post a longer url than 400b 					
				if(strpos($url,' ') == true){
					echo "It doesn't look like you submitted a valid URL: '". $url ."'";
					include 'form.html.php';
					exit();					
				}
				
				if(!mysqli_select_db($link, 'wiby'))
				{
				  $error = 'Unable to locate the database.'; 
				  include 'error.html.php'; 
				  exit(); 
				}
				
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
				unset($idArray);
				unset($shardArray);				
				
				if($delete == 1)
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
				
				if($delete == 1)
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
				
				//log deletions
				if(!mysqli_select_db($link, 'wibytemp'))
				{
				  $error = 'Unable to locate the wibytemp database.'; 
				  include 'error.html.php'; 
				  exit(); 
				}
				$sql = "INSERT INTO rejected (url,user,type,date) VALUES ('".$url."','".$_SESSION["user"]."','".$delete."',now())";
				if (!mysqli_query($link, $sql))   
				{
				  $error = 'Error fetching index: ' . mysqli_error($link);  
				  include 'error.html.php';  
				  exit(); 
				}				
				$url='';								
			}	
			$i++;
		}			

	    $output = $urls;

		include 'ban.html.php';       
	}
?>
