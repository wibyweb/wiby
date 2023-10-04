<!DOCTYPE html>    

<html>    

  <head>    
    <title>Wiby Feedback Form</title>   
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <LINK REL=STYLESHEET HREF="/styles.css" TYPE="text/css">  
  </head>    

  <body>    
    <form method="post" > 

      <div>

        <label for="feedback">Feedback:</label><br>   
	<?php if(isset($_POST['feedback'])) : ?>
            <textarea id="feedback" name="feedback" maxlength="8000" rows="10" cols="60"><?php echo htmlspecialchars($_POST['feedback'], ENT_QUOTES, 'UTF-8') ?></textarea>
	<?php else : ?>
	    <textarea id="feedback" name="feedback" maxlength="8000" rows="10" cols="60"></textarea>
	<?php endif; ?>
	

      </div>    
	<br> 
	<div>
	  <img id="captcha" src="/securimage/securimage_show.php" alt="CAPTCHA Image" />
	</div>
	<div>
	  <input type="text" name="captcha_code" size="10" maxlength="6" />
	  <a href="#" onclick="document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false">Reload Image</a>
          <br><p class="pin">* Cookies must be enabled for the captcha.</p>
        </div><br>
      <div><input type="submit" value="Submit"/></div>    

    </form>    

  </body>    

</html>
