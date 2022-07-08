<!DOCTYPE html>  

<html>  
  <head>  
    <title>TITLE</title>  
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>  
	<link rel=stylesheet href="/styles.css" type="text/css"> 
	<link rel="search" type="application/opensearchdescription+xml" title="YOUR_TITLE" href="/opensearch.xml">	
  </head>  
  <body>
    <form method="get">    
      <div style="float: left">
        <a class="title" href="../">name</a>&nbsp;&nbsp;
        <input type="text" size="35" name="q" id="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8');?>" role="form" aria-label="Main search form"/>
        <input type="submit" value="Search"/>
      </div>    
      <div  style="float: right"><a class="tiny" href="/settings/">Settings</a></div><br><br>
    </form>
    <?php $i=0; ?> 
    <p class="pin"><br></p>
    <?php foreach ($url as $storedresult): ?> 
		<?php $title[$i] = html_entity_decode($title[$i], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>
		<?php $bodymatch[$i] = html_entity_decode($bodymatch[$i], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>
		<?php $description[$i] = html_entity_decode($description[$i], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>
		<?php $title[$i] = str_replace("<","&lt;",$title[$i]); $title[$i] = str_replace(">","&gt;",$title[$i]); ?> 
		<?php $bodymatch[$i] = str_replace("<","&lt;",$bodymatch[$i]); $bodymatch[$i] = str_replace(">","&gt;",$bodymatch[$i]); ?>
		<?php $description[$i] = str_replace("<","&lt;",$description[$i]); $description[$i] = str_replace(">","&gt;",$description[$i]); ?>
		<blockquote><p>  
		  <a class="tlink" href="<?php echo htmlspecialchars($storedresult, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $title[$i]; ?></a> <br><p class="url"><?php echo htmlspecialchars($storedresult, ENT_QUOTES, 'UTF-8'); ?></p> 
		  <?php echo $bodymatch[$i]; ?>
		  <br> 
		  <?php echo $description[$i]; $i++; ?>
		</blockquote>  
    <?php endforeach; ?>  
    <?php if($i >= $lim && $starappend == 0): ?> 
        <p class="pin"><blockquote></p><br><a class="tlink" href="/?q=<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8');?>&o=<?php echo $totalcount;?>">Find more...</a></blockquote>
    <?php else: ?>
        <blockquote><p class="pin"> <br>That's everything I could find.<br>Help make me smarter by <a class="pin1" href="/submit">submitting a page</a>.</p></blockquote>
    <?php endif; ?>
  </body> 
</html>
