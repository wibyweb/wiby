<?php
require_once __DIR__ . '/db_config.php';
session_start();
	
	if($_SESSION["authenticated"]!=true)
	{
		include 'index.php';
		exit();
	}else    
	{    
		$db = getDbConnection();

		if (!$db)
		{
			$error = 'Cant connect to database.';
			include 'error.html.php';
			exit();
		}

		

		

		//get info for admin
		if (((!isset($_POST['name']) && !isset($_POST['password'])) || $_SESSION["loadadmin"]==true) && $_SESSION["level"]=="admin")    
		{  
			$_SESSION["loadadmin"]=false;
			$adminresult = db_query($db,"SELECT name, level, attempts, updated FROM accounts");
			if(!$adminresult)
			{
			  $error = 'Error fetching index: ' . $db->errorInfo()[2]; 
			  include 'error.html.php'; 
			  exit(); 
			}

			//put contents of accounts into an array
			while($row = $adminresult->fetch(PDO::FETCH_NUM))
			{
				$db_name[] = $row['name'];
				$db_level[] = $row['level'];
				$db_attempts[] = $row['attempts'];
				$db_updated[] = $row['updated']; 
			}
		  	include 'admin.html.php';   
			exit();
		//process info submitted by admin 
		}else if ($_SESSION["level"]=="admin" )
		{
			if($_POST["action"]=="0"){
				$_SESSION["loadadmin"]=true;
				echo "No actions taken.";
				include 'accounts.php';
				exit();					
			}
			//create
			if($_POST["action"]=="1"){
				if($_POST['password']=="" || $_POST['name']==""){
					echo "Both a name and password are required";
					$_SESSION["loadadmin"]=true;
					include 'accounts.php';
					exit();
				}

			    $post_name = db_escape_string($db, $_POST['name']);
				$post_password = db_escape_string($db, $_POST['password']);
				$hash = password_hash($post_password, PASSWORD_DEFAULT);
				if($_POST['level']==1){
					$post_level = "guardian";
				}else if($_POST['level']==2){
					$post_level = "admin";
				}

				$adminresult = db_query($db,"INSERT INTO accounts (name,hash,level) VALUES('".$post_name."','".$hash."','".$post_level."')");
				if(!$adminresult)
				{
				  $error = 'Error fetching index: ' . $db->errorInfo()[2]; 
				  include 'error.html.php'; 
				  exit(); 
				}
				echo "Create account submitted for ".$post_name;
				$_SESSION["loadadmin"]=true;
				include 'accounts.php';
				exit();			
			}
			//delete
			if($_POST["action"]=="2"){
				if($_POST['name']==""){
					echo "You did not name an account to delete.";
					$_SESSION["loadadmin"]=true;
					include 'accounts.php';
					exit();
				}

			    $post_name = db_escape_string($db, $_POST['name']);

				$adminresult = db_query($db,"DELETE FROM accounts WHERE name = '".$post_name."'");
				if(!$adminresult)
				{
				  $error = 'Error fetching index: ' . $db->errorInfo()[2]; 
				  include 'error.html.php'; 
				  exit(); 
				}
				echo "Delete account submitted for ".$post_name;
				$_SESSION["loadadmin"]=true;
				include 'accounts.php';
				exit();		
			}
			//update password
			if($_POST["action"]=="3"){
				if($_POST['password']=="" || $_POST['name']==""){
					echo "You must include both a name and password.";
					$_SESSION["loadadmin"]=true;
					include 'accounts.php';
					exit();
				}

			    $post_name = db_escape_string($db, $_POST['name']);
				$post_password = db_escape_string($db, $_POST['password']);
				$hash = password_hash($post_password, PASSWORD_DEFAULT);
				$adminresult = db_query($db,"UPDATE accounts SET hash = '".$hash."' WHERE name = '".$post_name."'");
				if(!$adminresult)
				{
				  $error = 'Error fetching index: ' . $db->errorInfo()[2]; 
				  include 'error.html.php'; 
				  exit(); 
				}
				echo "Update password submitted for ".$post_name;
				$_SESSION["loadadmin"]=true;
				include 'accounts.php';
				exit();					
			}
			//update level
			if($_POST["action"]=="4"){
				if($_POST['name']==""){
					echo "You must include an account name.";
					$_SESSION["loadadmin"]=true;
					include 'accounts.php';
					exit();
				}

			    $post_name = db_escape_string($db, $_POST['name']);
				$post_level = db_escape_string($db, $_POST['action']);
				if($_POST['level']==1){
					$post_level = "guardian";
				}else if($_POST['level']==2){
					$post_level = "admin";
				}

				$adminresult = db_query($db,"UPDATE accounts SET level = '".$post_level."' WHERE name = '".$post_name."'");
				if(!$adminresult)
				{
				  $error = 'Error fetching index: ' . $db->errorInfo()[2]; 
				  include 'error.html.php'; 
				  exit(); 
				}
				echo "Update level submitted for ".$post_name;
				$_SESSION["loadadmin"]=true;
				include 'accounts.php';
				exit();				
			}
			//unlock
			if($_POST["action"]=="5"){
				if($_POST['name']==""){
					echo "You must include an account name.";
					$_SESSION["loadadmin"]=true;
					include 'accounts.php';
					exit();
				}

			    $post_name = db_escape_string($db, $_POST['name']);

				$adminresult = db_query($db,"UPDATE accounts SET attempts = 0 WHERE name = '".$post_name."'");
				if(!$adminresult)
				{
				  $error = 'Error fetching index: ' . $db->errorInfo()[2]; 
				  include 'error.html.php'; 
				  exit(); 
				}
				echo "Unlock account submitted for ".$post_name;
				$_SESSION["loadadmin"]=true;
				include 'accounts.php';
				exit();				
			}
			//lock
			if($_POST["action"]=="6"){
				if($_POST['name']==""){
					echo "You must include an account name.";
					$_SESSION["loadadmin"]=true;
					include 'accounts.php';
					exit();
				}

		    	$post_name = db_escape_string($db, $_POST['name']);

				$adminresult = db_query($db,"UPDATE accounts SET attempts = 5 WHERE name = '".$post_name."'");
				if(!$adminresult)
				{
				  $error = 'Error fetching index: ' . $db->errorInfo()[2]; 
				  include 'error.html.php'; 
				  exit(); 
				}
				echo "Lock account submitted for ".$post_name;
				$_SESSION["loadadmin"]=true;
				include 'accounts.php';
				exit();					
			}
		}

		//get form for guardian 
		if (!isset($_POST['password']) && $_SESSION["level"]=="guardian")    
		{  
			echo "Welcome ". $_POST['user']; 
			include 'guardian.html.php';

		//process info for guardian
		}else if ($_SESSION["level"]=="guardian"){
			if($_POST['password']==""){
				echo "Password field is empty.";
				include 'guardian.html.php';
				exit();
			}

			$post_password = db_escape_string($db, $_POST['password']);
			$hash = password_hash($post_password, PASSWORD_DEFAULT);

			$result = db_query($db,"UPDATE accounts SET hash = '".$hash."' WHERE name = '".$_SESSION["user"]."'");
			if(!$result)
			{
				$error = 'Error fetching index: ' . $db->errorInfo()[2]; 
				include 'error.html.php'; 
				exit(); 
			}
			echo "Password has been updated";
			include 'guardian.html.php';			
		}

	}
?>


