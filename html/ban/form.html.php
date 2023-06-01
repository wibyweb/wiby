<!DOCTYPE html>    

<html>    
  <head>    
    <title>Ban a page</title>   
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>   
    <link rel="stylesheet" type="text/css" href="/styles.css"> 
    <style type="text/css">    
      textarea {   
        display: block;   
        width: 100%;   
      }   
    </style>  
  </head>    
  <body>    
    <form action="ban.php" method="post"> 
      <div>
        <label for="url">URLs as they appear in search results:</label><br> 
		<?php if(isset($_POST['urls'])) : ?>
				<textarea id="urls" name="urls" maxlength="40000" rows="10" cols="60" ><?php echo htmlspecialchars($_POST['urls'], ENT_QUOTES, 'UTF-8') ?></textarea>
		<?php else : ?>
			<textarea id="urls" name="urls" maxlength="40000" rows="10" cols="60" ></textarea>
		<?php endif; ?>
      </div>   
      <div>
        <label for="delete">Delete from index:</label>   
        <input type="checkbox" id="delete" name="delete" checked="checked">
        <p class="pin">*Unchecking this will ban them from the index instead.</p><br>
      </div>	  
      <div><input type="submit" value="Submit"/></div>    
    </form>    
  </body>    
</html>
