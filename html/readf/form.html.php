<!DOCTYPE html>  

<html>  

  <head>  

    <title>Feedback</title>  

    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>  
    <link rel="stylesheet" type="text/css" href="/styles.css">  
    <style> 
	pre {
	    white-space: pre-wrap;       
	    white-space: -moz-pre-wrap;  
	    white-space: -pre-wrap;      
	    white-space: -o-pre-wrap;    
	    word-wrap: break-word;       
	}
     </style>
  </head>  

  <body>

    <form action="feedback.php" method="post"> 
        <p>Some feedback awaiting review:</p><br><hr>  

        <?php $i=0; ?> 
        <?php foreach ($message as $submission): ?>  

          <blockquote><p>  

	   <pre><?php echo htmlspecialchars($submission, ENT_QUOTES, 'UTF-8'); ?></pre>
	   Time: <?php echo htmlspecialchars($time[$i], ENT_QUOTES, 'UTF-8'); ?><br>
	    [Drop<input type="checkbox" id="drop<?php echo $id[$i] ?>" name="drop<?php echo $id[$i] ?>" >]

          </p></blockquote><hr>
          <?php $i++; ?>
        <?php endforeach; ?> 
	<br> 
	<?php $r=5; ?>
        <div><input type="submit" id="submit" value="Submit"/></div>    
	<input type="hidden" name="startid" id="startid" value="<?php echo $id[0]; ?>">
	<input type="hidden" name="endid" id="endid" value="<?php echo $id[$i-1]; ?>">
    </form> 
  </body>  

</html>
