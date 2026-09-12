<!DOCTYPE html>  

<html>  

	<?php
require_once __DIR__ . '/db_config.php';
if (htmlspecialchars($_COOKIE['hs']) == "1")
		{
			$filterHTTPS = true;
		}else{
			$filterHTTPS = false;
		}
    	   
		$db = getDbConnection();
			
		if (!$db)
		{
			$error = 'Cant connect to database.';
			include 'error.html.php';
			exit();
		}

		

		
	
		//grab a random page
		if($filterHTTPS == false){
			$output = db_query($db, "select url from windex where worksafe = 1 and surprise = 1 order by rand() limit 1");
		}
		else
		{
			$output = db_query($db, "select url from windex where worksafe = 1 and surprise = 1 AND http = 1 order by rand() limit 1");
		}

		if(!$output)
		{
		  $error = 'Error ' . $db->errorInfo()[2];  
		  include 'error.html.php';  
		  exit(); 
		}

		//lets put contents of exact search into an array
		while($row = $output->fetch(PDO::FETCH_NUM))
		{
			$url = $row[0];
		}
	?>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>  
		<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
		<meta http-equiv="Pragma" content="no-cache">
		<meta http-equiv="Expires" content="0">
		<meta http-equiv="refresh" content="0; URL=<?php echo $url;?>"/>
	</head>
	<body>
		You asked for it!
	</body>

</html>
