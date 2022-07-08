<!DOCTYPE html>    

<html>    
  <head>    
    <title>Account Management</title>   
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>  
    <link rel="stylesheet" type="text/css" href="/styles.css"> 
    <style type="text/css">   
        textarea { display: block; width: 100%; }   
    </style>  
  </head>    
  <body>    
    <form action="accounts.php" method="post"> 
      <div>
      <br>
      Username <input type="text" name="name" id="name"/><br>     
      Password&nbsp; <input type="password" name="password" id="password"/><br>
      Level&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <select id="level" name="level">
             <option value=1>guardian</option>
             <option value=2>admin</option>
            </select><br>
      Action&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <select id="action" name="action">
              <option value=0>none</option>
              <option value=1>create</option>
              <option value=2>delete</option>
              <option value=3>update password</option>
              <option value=4>update level</option>
              <option value=5>unlock</option>
              <option value=6>lock</option>
             </select><br><br>
      </div>     
      <div><input type="submit" value="Submit"/></div> 
      <br>Accounts:<br>
      <?php $i=0; ?> 
      <?php foreach ($db_name as $username): ?>
        <blockquote><p><?php echo $username ." (  level: ". $db_level[$i] ." |  attempts: ". $db_attempts[$i] ." |  updated: ". $db_updated[$i] ." )"; ?></p></blockquote>
        <?php $i++; ?>
      <?php endforeach; ?> 

    </form>    
  </body>    
</html>
