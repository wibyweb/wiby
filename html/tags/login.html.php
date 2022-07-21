<!DOCTYPE html>    
<?php session_start(); ?>
<html>    

  <head>    

    <title>wiby.me</title>    

    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>  
    <link rel="stylesheet" type="text/css" href="/styles.css">

  </head>    

  <body>    
    <form method="post">  
      Username <input type="text" name="user" id="user"/><br>     
      Password <input type="password" name="pass" id="pass"/><br><br>
      <?php if($_SESSION["authenticated"]!=true): ?> 
	  <div>
	    <img id="captcha" src="/securimage/securimage_show.php" alt="CAPTCHA Image" />
	  </div>
	  <div>
	    <input type="text" name="captcha_code" size="10" maxlength="6" />
	    <a href="#" onclick="document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false">Reload Image</a>
      </div>  
      <?php endif; ?>
      <br><input type="submit" id="login" value="Login"/>
    </form>    
  </body>    

</html>
