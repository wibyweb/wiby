<!DOCTYPE html>    
<html> 
   
<?php session_start(); ?>

  <head>    
    <title>Settings</title>   
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>   
    <LINK REL=STYLESHEET HREF="../styles.css" TYPE="text/css">  
    <style type="text/css">  
    textarea {   
      display: block;   
      width: 100%;   
    } 
    </style>  
  </head>    

  <body>    
  <?php $worksafewason = false; ?>
    <form action="" method="post">   
      <div>
        <label for="worksafe">Filter adult content</label>   
	 <?php if($_COOKIE['ws'] == "1" || !isset($_COOKIE['ws'])):?>
		<input type="checkbox" id="worksafe" name="worksafe" checked="checked">
		<input type="hidden" name="worksafewason" id="worksafewason" value="on">
	    <?php else: ?>
		<input type="checkbox" id="worksafe" name="worksafe">
		<input type="hidden" name="worksafewason" id="worksafewason" value="off">
	 <?php endif; ?><br><br>
	<label for="filterHTTPS">Filter HTTPS</label>
	 <?php if($_COOKIE['hs'] == "0" || !isset($_COOKIE['hs'])):?>
		<input type="checkbox" id="filterHTTPS" name="filterHTTPS">
	    <?php else: ?>
		<input type="checkbox" id="filterHTTPS" name="filterHTTPS" checked="checked">
	 <?php endif; ?>
      </div><p class="pin">*for old browsers</p> <br>
      <div><input type="submit" value="Submit"/></div>    
      <div><br><br>
	<a href="/submit/">Submit a URL</a>
      </div>
     <p class="pin"><br><br><br><b>Search Options:</b><br><br>

      cats +tabby (results must contain the word tabby)<br>
      cats -tabby (results must not contain the word tabby)<br>
      "I love you" (use quotes to find an exact match)<br>
      <br>
      !td tornado (find within the frame of one day)<br>
      !tw tornado (find within the frame of one week)<br>
      !tm tornado (find within the frame of one month)<br>
      !ty tornado (find within the frame of one year)<br>
      <br>
      site:URL Lorem ipsum (limit search within a domain or URL)<br>
      <br>
      <br>
      <p class="pin"><b>Redirect Options:</b><br>
      <br>
      !g Paris (Google Text Search)<br>      
      !gi Paris (Google Images)<br>
      !gv Paris (Google Videos)<br>
      !gm Paris (Google Maps)<br>
      <br>
      !b Paris (Bing Text Search)<br>
      !bi Paris (Bing Images)<br>
      !bv Paris (Bing Videos)<br>
      !bm Paris (Bing Maps)<br>
      <br>
      You may also use '&' in place of '!'.
      </p>      
    </form>    

  </body>    

</html>


    
