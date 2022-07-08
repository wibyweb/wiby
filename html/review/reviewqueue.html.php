<!DOCTYPE html>  

<html>  

  <head>  

    <title>Awaiting Approval</title>  

    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>  
    <link rel="stylesheet" type="text/css" href="/styles.css">  
    <style> 
		blockquote { width: 100%; }
		input[type='number'] { width: 80px; }
    </style>
  </head>  

  <body>

    <form action="review.php" method="post"> 
        <p>Some pages awaiting review:</p>  

        <?php $i=0; ?> 
        <?php foreach ($url as $pageurl): ?>  

          <blockquote><p>  

	    <a class="tlink" href="<?php echo htmlspecialchars($pageurl, ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 17px;" target="_blank"><?php echo htmlspecialchars($pageurl, ENT_QUOTES, 'UTF-8'); ?></a><br>
	    <?php if($worksafe[$i] == '1'): ?>
		[Worksafe<input type="checkbox" id="worksafe<?php echo $id[$i] ?>" name="worksafe<?php echo $id[$i] ?>" checked="checked">]
	    <?php else: ?>
		[Worksafe<input type="checkbox" id="worksafe<?php echo $id[$i] ?>" name="worksafe<?php echo $id[$i] ?>">]
	    <?php endif; ?>
	    [Surprise<input type="checkbox" id="surprise<?php echo $id[$i] ?>" name="surprise<?php echo $id[$i] ?>">]
            [Skip<input type="checkbox" id="skip<?php echo $id[$i] ?>" name="skip<?php echo $id[$i] ?>" >]
            [Bury<input type="checkbox" id="bury<?php echo $id[$i] ?>" name="bury<?php echo $id[$i] ?>" >]
	    [Deny<input type="checkbox" id="deny<?php echo $id[$i] ?>" name="deny<?php echo $id[$i] ?>" >]
	    [Updatable<select id="updatable<?php echo $id[$i] ?>" name="updatable<?php echo $id[$i] ?>"> 
			<option value=1>1 WEEK</option>
			<option value=2>1 DAY</option> 
			<option value=3>12 HOUR</option>
			<option value=4>6 HOUR</option>
			<option value=5>3 HOUR</option>
			<option value=6>1 HOUR</option>
		      </select>]
		
		[Crawl: Depth <input type="number" id="crawldepth<?php echo $id[$i] ?>" name="crawldepth<?php echo $id[$i] ?>" >
		Pages <input type="number" id="crawlpages<?php echo $id[$i] ?>" name="crawlpages<?php echo $id[$i] ?>" >
		Type <select id="crawltype<?php echo $id[$i] ?>" name="crawltype<?php echo $id[$i] ?>">
			<option value=0>Local</option>
			<option value=1>All</option>
			<option value=2>External</option>
			</select>
		Enforce Rules<input type="checkbox" id="forcerules<?php echo $id[$i] ?>" name="forcerules<?php echo $id[$i] ?>" >
		Repeat<input type="checkbox" id="crawlrepeat<?php echo $id[$i] ?>" name="crawlrepeat<?php echo $id[$i] ?>" >]
          </p></blockquote>  
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
