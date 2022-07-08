<!DOCTYPE html>    

<html>    
  <head>    
    <title>Ban a page</title>   
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>   
    <link rel="stylesheet" type="text/css" href="/styles.css"> 
  </head>    
  <body>    
    <form action="ban.php" method="post"> 
      <div>
        <label for="url">URL as it appears in search results:</label><br> 
        <input type="text" id="url" name="url" size="45"></input>  
      </div>   
      <div>
        <label for="delete">Delete instead:</label>   
        <input type="checkbox" id="delete" name="delete" checked="checked">
      </div>	  
      <div><input type="submit" value="Submit"/></div>    
    </form>    
  </body>    
</html>
