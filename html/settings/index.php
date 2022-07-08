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
	  setcookie("hs","1",0,"/");
	}
	if ($_POST['filterHTTPS'] != 'on')
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
	  include 'gohome.html';
	}
	else if ($_POST['worksafewason'] == 'off' && $_POST['worksafe'] != 'on')    
	{    
	  include 'gohome.html';
	}
	else if ($_POST['agree'] == 'on')
	{
	  setcookie("ws","0",0,"/");
	  include 'gohome.html';
	}
	else    
	{   
	  setcookie("ws","1",0,"/");
	  include 'gohome.html';
	}
?>


