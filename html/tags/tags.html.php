<!DOCTYPE html>    

<html>    

  <head>    

    <title>Form Example</title>    

    <meta http-equiv="content-type"    

        content="text/html; charset=utf-8"/>    

  </head>    

  <body>   
	<?php echo $status; ?>
    <form action="tags.php" method="post"> 
      <div>
        <label for="tags">Tags:</label>
        <input type="text" id="tags" name="tags" size="45" value="<?php echo $tags; ?>"></input>  
        <br>
        <label for="url">URL:</label>
        <input type="text" id="url" name="url" size="45" value="<?php echo $url; ?>"></input> 
      </div>     
      <div><input type="submit" value="Submit"/></div>    
    </form>      

  </body>    

</html>
