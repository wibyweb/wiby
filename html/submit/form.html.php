<!DOCTYPE html>    

<html>    

  <head>    
    <title>Submit to the Wiby Web</title>   
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>   
    <LINK REL=STYLESHEET HREF="../styles.css" TYPE="text/css">  
    <style type="text/css">    
      textarea {   
        display: block;   
        width: 100%;   
      }   
    </style>  
  </head>    

  <body>    
    <form method="post"> 

      <div>

        <label for="url">url:</label>   
	<?php if(isset($_POST['url'])) : ?>
            <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($_POST['url'], ENT_QUOTES, 'UTF-8') ?>" maxlength="400" >
	<?php else : ?>
	    <input type="text" id="url" name="url" maxlength="400" >
	<?php endif; ?>
	

      </div>    

      <div>
        <label for="worksafe">worksafe:</label>   
        <input type="checkbox" id="worksafe" name="worksafe" checked="checked">
      </div><br> 
      <?php if($_SESSION["authenticated"]!=true): ?> 
	  <div>
	    <img id="captcha" src="/securimage/securimage_show.php" alt="CAPTCHA Image" />
	  </div>
	  <div>
	    <input type="text" name="captcha_code" size="10" maxlength="6" />
	    <a href="#" onclick="document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false">Reload Image</a>
      </div>
      <p class="pin">* Cookies must be enabled for the captcha.</p>	    
      <?php endif; ?>
      <br>
      <div><input type="submit" value="Submit"/></div>    
      <br><br>
      <h3>What kind of pages get indexed?</h3>
      <p>
	      Enter your submission rules here.
      </p>
    </form>    

  </body>    

</html>
