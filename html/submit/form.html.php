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
      <?php endif; ?>
      <br>
      <div><input type="submit" value="Submit"/></div>    
      <br><br>
      <h3>What kind of pages get indexed?</h3>
      <p>
        Pages must be simple in design. Simple HTML, <b>non-commerical</b> sites are preferred.<br> 
        <b>Pages should not use much scripts/css for cosmetic effect.</b> Some might squeak through.<br>
	    Don't use ads that are intrusive (such as ads that appear overtop of content).<br>
	    Don't submit a page which serves primarily as a portal to other bloated websites.<br>
	    If you submit a blog, submit a few of your articles, not your main feed.<br>
	    If your page does not contain any text or uses frames, ensure a meta description tag is added.<br>
	    Only the page you submit will be crawled.<br>
      </p>
      <p class="pin">
        <br><br>Note:<br>
	<br>The WibyBot (172.93.49.59) is occasionally rejected by some web servers. 
	<br>Barring technical issues, if you are puzzled why a site wasn't indexed, reread the above guide.
	<br>Angelfire and Tripod pages are no longer grandfathered (ads are too intrusive).
      </p>
    </form>    

  </body>    

</html>
