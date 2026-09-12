<?php
require_once __DIR__ . '/db_config.php';
session_start();
	
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

	

	
	
	if (isset($_POST['startid']) && $_SESSION["loadgraveyard"]==false)    
	{    
		$startID = db_escape_string($db, $_POST['startid']);
		$endID = db_escape_string($db, $_POST['endid']);
	}	

	$lim = 100; //note, setting this too high will cause PHP Warning:  Unknown: Input variables exceeded 1000
	
	if (isset($_POST['startid']) && $_SESSION["loadgraveyard"]==false) //this is incase any new submissions are made during the review process, they will be ignored   
	{  
		$result = db_query($db,"SELECT * FROM graveyard WHERE id >= '".$startID."' AND id <= '".$endID."'");
		if(!$result)
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}
	}
	else
	{
		//check graveyard for rows that are reserverd within reservetime. Do not select reserved rows. If reserved rows exceed 30mins, they can be reserved by different approver.  
		$result = db_query($db,"SELECT * FROM graveyard WHERE reserved IS NULL OR reserved = '".$_SESSION["user"]."' OR reservetime < NOW() - INTERVAL 30 MINUTE LIMIT $lim");
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
		$url[] = str_replace("'", "%27", $row['url']); 
		$worksafe[] = $row['worksafe'];
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['startid']) && $_SESSION["loadgraveyard"]==false)    
	{    //store approved url list into indexqueue					
		$i=0;
		$num_crawlers=1;//modify this variable to the number of crawlers you are using in parallel.
		$crawler_id=1;
		foreach($id as $pageid)
		{
			if($_POST["deny$pageid"] != 'on' && $_POST["skip$pageid"] != 'on')
			{

				$worksafe = db_escape_string($db, $_POST["worksafe$pageid"]);
				if($worksafe == 'on')
				{
					$worksafe = 1;
				}
				else
				{
					$worksafe = 0;
				}


				if($_POST["surprise$pageid"] == 'on')
				{
					$surprise = 1;
				}
				else
				{
					$surprise = 0;
				}
				
				if($_POST["forcerules$pageid"] == 'on')
				{
					$forcerules = 1;
				}
				else
				{
					$forcerules = 0;
				}					
				
				if($_POST["crawlrepeat$pageid"] == 'on')
				{
					$crawlrepeat = 1;
				}
				else
				{
					$crawlrepeat = 0;
				}				
		
				$updatable = $_POST["updatable$pageid"];
				$crawldepth = $_POST["crawldepth$pageid"];
				$crawlpages = $_POST["crawlpages$pageid"];
				$crawltype = $_POST["crawltype$pageid"];

				$sql = "INSERT INTO indexqueue (url,worksafe,approver,surprise,updatable,crawl_depth,crawl_pages,crawl_type,force_rules,crawl_repeat,crawler_id) VALUES ('".$url[$i]."','".$worksafe."','".$_SESSION["user"]."','".$surprise."','".$updatable."','".$crawldepth."','".$crawlpages."','".$crawltype."','".$forcerules."','".$crawlrepeat."','".$crawler_id."')";
				if (!db_query($db, $sql))   
				{
					$error = 'Error inserting into indexqueue: ' . $db->errorInfo()[2];  
					include 'error.html.php';  
					exit(); 
				}
			}
			if($_POST["skip$pageid"] != 'on' || ($_POST["skip$pageid"] == 'on' && $_POST["deny$pageid"] == 'on'))
			{
				$result2 = db_query($db,"DELETE FROM graveyard WHERE id = $pageid");
				if(!$result2)
				{
				  $error = 'Error deleting from graveyard: ' . $db->errorInfo()[2];  
				  include 'error.html.php';  
				  exit(); 
				}
			}			
			$i++;
			if($crawler_id == $num_crawlers){
				$crawler_id=1;
			}else{
				$crawler_id++;
			}
		}
		
		$_SESSION["loadgraveyard"]=true;
		unset($id);
		unset($url);
		unset($worksafe);
		unset($startID);
		unset($endID);		
		unset($result);
		$db -> close();		
		include 'graveyard.php';		
		//include 'refresh.html';
		exit();
	}
	else
	{
		$_SESSION["loadgraveyard"]=false;
		//insert approver into reserved, reservetime will autoupdate, so that they cannot be taken by a different approver for 30 mins.
		foreach($id as $pageid)
		{
			$result = db_query($db,"UPDATE graveyard SET reserved = '".$_SESSION["user"]."' WHERE id = $pageid");
			if(!$result)
			{
			  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
			  include 'error.html.php';  
			  exit(); 
			}			
		}

		//get total number of rows remaining in queue
		$totalrows = db_query($db,"select count(id) from graveyard");
		if(!$totalrows)
		{
		  $error = 'Error fetching index: ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}	
		//get result of total rows remaining in queue
		while($row = $totalrows->fetch(PDO::FETCH_NUM))
		{
			$queuesize = $row['count(id)'];
			echo $queuesize . " pages queued in total.";
		}

		include 'graveyardqueue.html.php';
	}
?>
