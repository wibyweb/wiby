<?php    
session_start();

if (isset($_COOKIE['ws']) && htmlspecialchars($_COOKIE['ws']) == "0")
{
	$worksafe = false;
}else{
	$worksafe = true;
}
if (isset($_COOKIE['hs']) && htmlspecialchars($_COOKIE['hs']) == "1")
{
	$filterHTTPS = true;
}else{
	$filterHTTPS = false;
}

if (!isset($_REQUEST['q']))    
{    
  include 'form.html.php';    
}  

else    

{    
	$link = mysqli_connect('localhost', 'guest', 'qwer');
			
	$query = mysqli_real_escape_string($link, $_GET['q']);

	$lim = 12;
	
	$starappend = 0;
	$startID = 0;	
	$additions = "";

	//Check if no query found
	if($query == "")
	{
		include 'form.html.php';
		exit();
	} 
	
	//phone users
	if(strlen($query) > 1 && $query[strlen($query)-1]==" "){
		$query = substr($query,0,strlen($query)-1);
	}
	if(strlen($query) > 1 && $query[0]==" "){
		$query = substr($query,1,strlen($query));
	}

	//check if user wants to search a different search engine (!) or time window
	if(($query[0] == "!" || $query[0] == "&") && strlen($query) > 3)
	{
		//separate actual query from search redirect	
		$actualquery = "";
		$redirect = "";
		if($query[2] == " "){
			$redirect = substr($query, 1, 1);
			for($i=3; $i<strlen($query);$i++){
				$actualquery .= $query[$i];
			}
			
		}
		if($query[3] == " "){
			$redirect = substr($query, 1, 2);
			for($i=4; $i<strlen($query);$i++){
				$actualquery .= $query[$i];
			}
		}	
		//determine which search engine to redirect or which time window to use
		if ($redirect == "g"){//if google
			header('Location: '."http://google.com/search?q=$actualquery");
			exit();
		}else if ($redirect == "b"){//if bing
			header('Location: '."http://bing.com/search?q=$actualquery");
			exit();
		}else if ($redirect == "gi"){//if google image search
			header('Location: '."http://www.google.com/search?tbm=isch&q=$actualquery");
			exit();
		}else if ($redirect == "bi"){//if bing image search
			header('Location: '."http://www.bing.com/images/search?q=$actualquery");
			exit();
		}else if ($redirect == "gv"){//if google video search
			header('Location: '."http://www.google.com/search?tbm=vid&q=$actualquery");
			exit();
		}else if ($redirect == "bv"){//if bing video search
			header('Location: '."http://www.bing.com/videos/search?q=$actualquery");
			exit();
		}else if ($redirect == "gm"){//if google maps search
			header('Location: '."http://www.google.com/maps/search/$actualquery");
			exit();
		}else if ($redirect == "bm"){//if bing maps search
			header('Location: '."http://www.bing.com/maps?q=$actualquery");
		}else if ($redirect == "td"){
			$additions = $additions."AND date > NOW() - INTERVAL 1 DAY ";
			$query = $actualquery;
		}else if ($redirect == "tw"){
			$additions = $additions."AND date > NOW() - INTERVAL 7 DAY ";
			$query = $actualquery;
		}else if ($redirect == "tm"){
			$additions = $additions."AND date > NOW() - INTERVAL 30 DAY ";
			$query = $actualquery;
		}else if ($redirect == "ty"){
			$additions = $additions."AND date > NOW() - INTERVAL 365 DAY ";
			$query = $actualquery;
		}else{
			header('Location: '."/?q=$actualquery");
			exit();
		}		
	}
	
	//check if user wants to limit search to a specific website
	if(strlen($query) > 5 && strcasecmp(substr($query, 0, 5),"site:")==0){
		//remove 'site:'
		$query = substr($query, 5, strlen($query)-5);
		//get site:
		$site = strstr($query, ' ', true);
		//now just get the search query
		$query = strstr($query, ' ', false);
		$query = substr($query, 1, strlen($query)-1);
		//add to additions 
		$additions = $additions."AND url LIKE '%".$site."%' ";
	}
	
	$page=0;
	if (!isset($_REQUEST['p'])) 
	{
		$offset=0;
	}
	else
	{
		$page = mysqli_real_escape_string($link, $_GET['p']);
		$offset = $page;
		if($offset > 0)
		{
			$offset--;
		}
		$offset = $offset * $lim;
	}
	
	if (!$link)
	{
		$error = 'Cant connect to database.';
		include 'error.html.php';
		exit();
	}

	if (!mysqli_set_charset($link, 'utf8mb4'))
	{ 
	  $error = 'Unable to set database connection encoding.'; 
	  include 'error.html.php'; 
	  exit(); 
	}
	
	if(!mysqli_select_db($link, 'wiby'))
	{
	  $error = 'Unable to locate the database.'; 
	  include 'error.html.php'; 
	  exit(); 
	}

	$queryOriginal = $query;

	//Check if query is a url (contains http:// or https:// and no spaces). If so, put quotations around to to get an exact match
	$urlDetected = 0;
	//if(strpos($query, ' ') == false && strpos($query,'.') == true && strpos($query,'"') == false && preg_match('/http/',$query) == true)
	if(strpos($query, ' ') == false && strpos($query,'.') == true && strpos($query,'"') == false)//note this will flag on file extensions also
	{
		$query = '"' . $query . '"';
		$urlDetected = 1;
	}

	//did user manually set -https instead of settings cookie?
	if(substr($query,-7) == " -https"){
		$filterHTTPS = true;
		$query = substr($query, 0,-7);	
	}

	$queryNoQuotes = $query;

	//Are there quotes in the query?
	$exactMatch = false;
	if(strpos($queryNoQuotes,'"') !== false)
	{
		$exactMatch = true;
	}
	
	//alright then lets remove the quotes
	if($exactMatch == true)
	{
		while(strpos($queryNoQuotes,'"') !== false)
		{
			$queryNoQuotes = str_replace('"', "",$queryNoQuotes);
		}
	}

	//remove the '*' if contained anywhere in queryNoQuotes
	if(strpos($queryNoQuotes,'*') !== false && $exactMatch == false){
		$queryNoQuotes = str_replace('*', "",$queryNoQuotes);
	}

	//first remove any flags inside queryNoQuotes, also grab any required words (+ prefix) 
	$queryNoQuotesOrFlags = $queryNoQuotes;
	$requiredword = '';
	$flags = '';
	if(strpos($queryNoQuotes,'+') !== false || strpos($queryNoQuotes,'-') !== false){
		$words = explode(' ', $queryNoQuotes);	
		$i = 0;
		$queryNoQuotesOrFlags = '';
		foreach ($words as $word) {
			if($i != 0 && $word[0] != '-' && $word[0] != '+'){
				$queryNoQuotesOrFlags .= ' ';
			}			
			if ($word[0] != '-' && $word[0] != '+'){
				$queryNoQuotesOrFlags .= $word;
			}
			if ($word[0] == '+' && strlen($word) > 1 && $requiredword == ''){
				$requiredword = substr($word,1);
			}
			if ($word[0] == '-' || $word[0] == '+'){
				$flags .= " $word";
			}			
			$i++;
		}
	}

	//$queryNoQuotes_SQLsafe = mysqli_real_escape_string($link, $queryNoQuotes);
	//$flags = mysqli_real_escape_string($link, $flags);
	
	$words  = explode(' ', $queryNoQuotesOrFlags);
	if($exactMatch == false)
	{
		//find longest word in query	
		$longestWordLength = 0;
		$longestWord = '';
		$wordcount = 0;
		$longestwordelementnum = 0;
		foreach ($words as $word) {
			if (strlen($word) > $longestWordLength) {
				$longestWordLength = strlen($word);
				$longestWord = $word;
				$longestwordelementnum = $wordcount;
			}
		   $wordcount++;
		}
	}

	//create another query where all compatible words are marked as keywords
	$reqwordQuery = '';
	$i=0;
	$flagssetbyuser=0;
	$numRequiredWords=0;
	$wordlen=0;
	foreach ($words as $word) {
		$wordlen = strlen($word);
		if(($word[0] == '+' || $word[0] == '-') && $wordlen > 3){
			$flagssetbyuser++;
			if($word[0] == '+'){
				$numRequiredWords++;
			}
		}
		if($i==0 && ($word[0] == '+' || $word[0] == '-') && $wordlen > 3){
			$reqwordQuery .= "$word";
		}
		if($i==0 && $word[0] != '+' && $word[0] != '-'){
			if($wordlen > 2){
				$reqwordQuery .= "+$word";
			}else{
				$reqwordQuery .= "$word";
			}
		}
		if($i!=0 && ($word[0] == '+' || $word[0] == '-') && $wordlen > 3){
			$reqwordQuery .= " $word";
		}		
		if($i!=0 && $word[0] != '+' && $word[0] != '-' ){
			if($wordlen > 2){			
				$reqwordQuery .= " +$word";
			}else{
				$reqwordQuery .= " $word";
			}
		}
		$i++;	
	}

	//Check if query contains a hyphenated word. MySQL is finicky about them. We will wrap quotes around hyphenated words that aren't part of a string which is already wraped in quotes.
	if((strpos($queryNoQuotes,'-') !== false || strpos($queryNoQuotes,'+') !== false) && $urlDetected == false){
		$hyphenwords = explode(' ',$query);
		$query = '';
		$quotes = 0;
		$i = 0;
		foreach ($hyphenwords as $word) {
			if(strpos($queryNoQuotes,'"') !== false){
				$quotes++;
			}
			if(((strpos($word,'-') !== false && $word[0] != '-') || (strpos($word,'+') !== false && $word[0] != '+')) && $quotes%2 == 0){//if hyphen exists, not a flag, not wrapped in quotes already
				$word = '"' . $word . '"';
			}
			if($i > 0){
				$query .= ' ';
			}
			$query .= $word;
			$i++;
		}
	}

	//if no required words set, make the longest word in the query required.
	$querywithrequiredword = "";
	if($numRequiredWords == 0 && $wordcount > 1 && $longestWordLength > 2){
		$querywithrequiredword = $query .= " +$longestWord";
	}
	
	if($filterHTTPS == true){
		$additions = $additions."AND http = '1' ";
	}
	if($worksafe == true){
		$additions = $additions."AND worksafe = '1' ";
	}
	
	$count = 0;
	
	$queryWithQuotesAndFlags = '"'. $queryNoQuotesOrFlags.'"'.$flags.'';
	$queryWithQuotes = '"'. $queryNoQuotesOrFlags.'"';

	//if query is just 1 or 2 letters, help make it work.
	if(iconv_strlen($queryOriginal) < 3){
		$query = "".$query."*";
		$queryWithQuotesAndFlags = $query;
		$reqwordQuery = $query;
	}
	if($queryOriginal == "c++" || $queryOriginal == "C++"){
		$query = "\"c++\" +programming";
		$queryWithQuotesAndFlags = $query;
		$reqwordQuery = $query;
	}

	if($querywithrequiredword != ""){
		$querytouse = $querywithrequiredword;
	}else if($numRequiredWords > 0){
		$querytouse = $reqwordQuery;
	}else{
		$querytouse = $query;
	}

	//perform full text search FOR InnoDB or MyISAM STORAGE ENGINE
	if($exactMatch == false && $urlDetected==0 && strpos($query, ' ') == true && $flagssetbyuser != $wordcount){
		$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE MATCH(tags, body, description, title, url) AGAINST('$querytouse' IN BOOLEAN MODE) AND enable = '1' $additions ORDER BY CASE WHEN MATCH(tags) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 30 WHEN MATCH(title) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 20 WHEN MATCH(body) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) OR MATCH(description) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 15 WHEN MATCH(title) AGAINST('$reqwordQuery' IN BOOLEAN MODE) THEN 14 WHEN MATCH(title) AGAINST('$querytouse' IN BOOLEAN MODE) THEN 13 END DESC, id DESC LIMIT $lim OFFSET $offset");
	}else{
		$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE MATCH(tags, body, description, title, url) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) AND enable = '1' $additions ORDER BY CASE WHEN MATCH(tags) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 30 WHEN MATCH(title) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 20 END DESC, id DESC LIMIT $lim OFFSET $offset");
	}

/*	if($exactMatch == false && $urlDetected==0 && strpos($query, ' ') == true && $flagssetbyuser != $wordcount){
		$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE MATCH(tags, body, description, title, url) AGAINST('$reqwordQuery' IN BOOLEAN MODE) AND enable = '1' $additions ORDER BY CASE WHEN MATCH(tags) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 30 WHEN MATCH(title) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 20 WHEN MATCH(body) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) OR MATCH(description) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 15 WHEN MATCH(title) AGAINST('$reqwordQuery' IN BOOLEAN MODE) THEN 14 WHEN MATCH(title) AGAINST('$query' IN BOOLEAN MODE) THEN 13 END DESC, id DESC LIMIT $lim OFFSET $offset");*/

	if($urlDetected == 1)
	{
		$query = $queryOriginal;
	}

	/*if(mysqli_num_rows($outputFTS) <= 3 && $offset == 0 && $urlDetected == 0 && $exactMatch == false)
	{
		$starappend = 1;
		$querystar = $query;
		//innodb will get fussy over some things if put in like '''' or ****, uncomment below lines if using innoDB
		$querystar = str_replace('*', "",$querystar);
		$querystar = str_replace('"', "",$querystar);
		$querystar = str_replace('"', "",$querystar);
		$querystar = str_replace('\'', "",$querystar);
		//-----------------------------------------------
	
		$querystar = $querystar . '*';
		//perform full text search FOR InnoDB or MyISAM STORAGE ENGINE
		$outputFTSgeneral = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$querystar' IN BOOLEAN MODE) AND enable = '1' $additions ORDER BY CASE WHEN MATCH(tags) AGAINST('$queryWithQuotesAndFlags' IN BOOLEAN MODE) THEN 30 END DESC, id DESC LIMIT $lim OFFSET $offset");

		if(!$outputFTSgeneral)
		{
		  $error = 'Error ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}		
	}*/
	//this will get set if position of longest word of query is found within body
	$pos = -1;

	//lets put contents of the full text search into the array
	while($row = mysqli_fetch_array($outputFTS))
	{
		//put the contents of the URL column within the DB into an array
		$id[] = $row[0];
		$url[] = $row[1];
		$title[] = substr($row[2],0,150);
		$description[] = substr($row[3],0,180);
		$body = $row[4];
		$count++;
		$lastID = $row[0];
		
		if($exactMatch == false && ($flagssetbyuser == 0 || $flagssetbyuser == $wordcount))
		{
			//remove the '*' at the end of the longest word if present
			if(strpos($longestWord,'*') == true)
			{
				$longestWord = str_replace('*', "",$longestWord);
			}

			//first find an exact
			if(strlen($requiredword) > 0){
				$pos = stripos($body, $requiredword);			
			}else{
				$pos = stripos($body, $queryNoQuotesOrFlags);
			}
		
			//search within body for position of longest query word. If not found, try another word	
			if($pos == false){	
				$pos = stripos($body, $longestWord);
				if($pos == false && $wordcount > 1)
				{
					if($longestwordelementnum > 0)
					{
						if(strpos($words[0],'*') == true)//remove the '*' at the end of the query if present
							$words[0] = str_replace('*', "",$words[0]);					
						$pos = stripos($body, $words[0]);
					}
					else if($longestwordelementnum == 0)
					{
						if(strpos($words[1],'*') == true)//remove the '*' at the end of the query if present
							$words[1] = str_replace('*', "",$words[1]);				
						$pos = stripos($body, $words[1]);
					}		
				}
			}
		}
		else
		{
			$pos = stripos($body, $queryNoQuotesOrFlags);
		}
		//still not found?, set position to 0
		if($pos == false){
			$pos = 0;
		}

		//get all positions of all keywords in body
	/*	$lastPos = 0;
		$positions = array();
		foreach($words as $word)
		{
			while (($lastPos = mb_strpos($body, $word, $lastPos))!== false) {
				$positions[$word][] = $lastPos;
				$lastPos = $lastPos + strlen($word);
			}		
		}*/
		
		//figure out how much preceding text to use	
		if($pos < 32)		
			$starttext = 0;
		else if($pos > 25)		
			$starttext = $pos - 25;
		else if($pos > 20)
			$starttext = $pos - 15;
		//else $starttext = 0;

		//total length of the ballpark
		$textlength = 180;

		//populate the ballpark
		if($pos >= 0)
		{
			$ballparktext = substr($body,$starttext,$textlength);
		}
		else $ballpark = '0';
			
		//find position of nearest Period
		$foundPeriod = true;
		$posPeriod = stripos($ballparktext, '. ') + $starttext +1;

		//find position of nearest Space
		$foundSpace = true;
		$posSpace = stripos($ballparktext, ' ') + $starttext;
		
		//if longest word in query is after a period+space within ballpark, reset $starttext to that point 
		if($pos-$starttext > $posPeriod)
		{
			$starttext = $posPeriod;
			//populate the bodymatch
			if($pos-$starttext >= 0)
			{
				$bodymatch[] = substr($body,$starttext,$textlength);
			}
			else $bodymatch[] = '';
		}
		//else if($pos-starttext > $posSpace)//else if longest word in query is after a space within ballpark, reset $starttext to that point
		else if($pos > $posSpace)//else if longest word in query is after a space within ballpark, reset $starttext to that point
		{
			$starttext = $posSpace;
			//populate the bodymatch
			if($pos-$starttext >= 0)
			{
				$bodymatch[] = substr($body,$starttext,$textlength);
			}
			else $bodymatch[] = '';
		}
		else //else just set the bodymatch to the ballparktext
		{
			//populate the bodymatch
			if($pos-$starttext >= 0)
			{
				$bodymatch[] = $ballparktext;
			}
			else $bodymatch[] = '';	
		}
		
	}

	$query = $_GET['q'];
	$row = null;

	if($page == 0){
		$page+=2;
	}else{
		$page++;
	}

   	include 'results.html.php';    
}    

?>
