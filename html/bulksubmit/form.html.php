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

        <label for="urls">List up to 100 URLs:</label><br>
	<?php if(isset($_POST['urls'])) : ?>
            <textarea id="urls" name="urls" maxlength="40000" rows="10" cols="60" ><?php echo htmlspecialchars($_POST['urls'], ENT_QUOTES, 'UTF-8') ?></textarea>
	<?php else : ?>
	    <textarea id="urls" name="urls" maxlength="40000" rows="10" cols="60" ></textarea>
	<?php endif; ?>
	

      </div>    

      <div>
        <label for="worksafe">worksafe:</label>   
        <input type="checkbox" id="worksafe" name="worksafe" checked="checked">
      </div><br> 
      <br>
      <div><input type="submit" value="Submit"/></div>    
    </form>    

  </body>    

</html>
