<!DOCTYPE html>  

<html>  

	<?php
		if (htmlspecialchars($_COOKIE['hs']) == "1")
		{
			$filterHTTPS = true;
		}else{
			$filterHTTPS = false;
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
	
		//grab a random page
		if($filterHTTPS == false){
			$output = mysqli_query($link, "select url from windex where worksafe = 1 and surprise = 1 order by rand() limit 1");
		}
		else
		{
			$output = mysqli_query($link, "select url from windex where worksafe = 1 and surprise = 1 AND http = 1 order by rand() limit 1");
		}

		if(!$output)
		{
		  $error = 'Error ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}

		//lets put contents of exact search into an array
		while($row = mysqli_fetch_array($output))
		{
			$url = $row[0];
		}
	?>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>  
		<meta http-equiv="refresh" content="0; URL='<?php echo $url;?>'"/>
	</head>
	<body>
		You asked for it!
	</body>

</html>
