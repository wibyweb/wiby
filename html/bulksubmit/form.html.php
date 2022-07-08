<!DOCTYPE html>    

<html>    

  <head>    
    <title>Bulk submit to the Wiby Web</title>   
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>   
    <LINK REL=STYLESHEET HREF="/styles.css" TYPE="text/css">  
    <style type="text/css">    
      textarea {   
        display: block;   
        width: 100%;   
      }   
    </style>  
  </head>    

  <body>    
    <form action="bulksubmit.php" method="post"> 

      <div>

        <label for="urls">URL List:</label><br>
	<?php if(isset($_POST['urls'])) : ?>
            <textarea id="urls" name="urls" maxlength="8000" rows="10" cols="60" ><?php echo htmlspecialchars($_POST['urls'], ENT_QUOTES, 'UTF-8') ?></textarea>
	<?php else : ?>
	    <textarea id="urls" name="urls" maxlength="8000" rows="10" cols="60" ></textarea>
	<?php endif; ?>
	

      </div>    

      <div>
        <label for="worksafe">worksafe:</label>   
        <input type="checkbox" id="worksafe" name="worksafe" checked="checked">
      </div><br> 
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
