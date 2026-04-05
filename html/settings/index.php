<?php    
	session_start();

	if (!isset($_COOKIE['ws']))
	{
		setcookie("ws","1",0,"/");
	}
	if (!isset($_COOKIE['hs']))
	{
		setcookie("hs","0",0,"/");
	}
	if ($_POST['filterHTTPS'] == 'on')
	{
		if(strstr($_SERVER['HTTP_USER_AGENT'],"Mozilla/2.") != FALSE || strstr($_SERVER['HTTP_USER_AGENT'],"Mozilla/3.") != FALSE){ 
			setcookie("hs","1",0,"/"); //format and/or Y2K issues with setting expiration date on Netscape 2 and 3
			//header("Set-Cookie: hs=1; expires=Thu, 01-Apr-2027 05:36:46 GMT; path=/"); //only works if computer date is before 2000
		}else{
			setcookie("hs","1",time()+60*60*24*365,"/");
		}
	}
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['filterHTTPS'] != 'on' && !isset($_POST['agree']))
	{
		setcookie("hs","0",0,"/");
	}
	if (!isset($_POST['worksafe']) && !isset($_POST['worksafewason']) && !isset($_POST['agree']))    
	{    
		include 'form.html.php';    
	}
	else if ($_POST['worksafewason'] == 'on' && $_POST['worksafe'] != 'on')    
	{    
		include 'agree.html.php';    
	}
	else if ($_POST['worksafewason'] == 'on' && $_POST['worksafe'] == 'on')    
	{    
		//include 'gohome.html';
		header("Location: /");
	}
	else if ($_POST['worksafewason'] == 'off' && $_POST['worksafe'] != 'on')    
	{    
		//include 'gohome.html';
		header("Location: /");
	}
	else if ($_POST['agree'] == 'on')
	{
		if(strstr($_SERVER['HTTP_USER_AGENT'],"Mozilla/2.") != FALSE || strstr($_SERVER['HTTP_USER_AGENT'],"Mozilla/3.") != FALSE){ 
			setcookie("ws","0",0,"/"); //format and/or Y2K issues with setting expiration date on Netscape 2 and 3
			//header("Set-Cookie: ws=0; expires=Thu, 01-Apr-2027 05:36:46 GMT; path=/"); //only works if computer date is before 2000
		}else{
			setcookie("ws","0",time()+60*60*24*365,"/");
		}
		//include 'gohome.html';
		header("Location: /");
	}
	else    
	{   
		setcookie("ws","1",0,"/");
		//include 'gohome.html';
		header("Location: /");
	}
?>


