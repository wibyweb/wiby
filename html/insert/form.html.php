<!DOCTYPE html>    

<html>    

  <head>    

    <title>Add to Index</title>   

    <meta http-equiv="content-type"   

        content="text/html; charset=utf-8"/>   
    <link rel="stylesheet" type="text/css" href="/styles.css"> 
    <style type="text/css">   

    textarea {   

      display: block;   

      width: 100%;   

    }   

    </style>  

  </head>    

  <body>    

    <form action="insert.php" method="post"> 
 
      <div>

        <label for="url">url:</label>   

        <textarea id="url" name="url" ></textarea>  

      </div>    

      <div>
        <label for="title">title:</label>   

        <textarea id="title" name="title"></textarea> 
      </div>    

      <div>
        <label for="tags">tags:</label>   

        <textarea id="tags" name="tags"></textarea> 
      </div>    
      <div>
        <label for="description">description :</label>   

        <textarea id="description" name="description"></textarea> 
      </div>  
      <div>
        <label for="body">body:</label>   

        <textarea id="body" name="body"></textarea> 
      </div>  
      <div>
        <label for="http">http (1 or 0):</label>   

        <textarea id="http" name="http"></textarea> 
      </div>  
      <div>
        <label for="surprise">surprise (1 or 0):</label>   

        <textarea id="surprise" name="surprise"></textarea> 
      </div>  
      <div>
        <label for="worksafe">worksafe (1 or 0): </label>   

        <textarea id="worksafe" name="worksafe"></textarea> 
      </div>  
      <div>
        <label for="enable">enable (1 or 0):</label>   

        <textarea id="enable" name="enable"></textarea> 
      </div> 
      <div>
        <label for="updatable">updatable (0 for no, or integer value 1-6, 1 is default):</label>   

        <textarea id="updatable" name="updatable"></textarea> 
      </div>   
      <div><input type="submit" value="Submit"/></div>    

    </form>    

  </body>    

</html>
