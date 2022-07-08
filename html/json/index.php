<?php    
session_start();

if (htmlspecialchars($_COOKIE['ws']) == "0")
{
	$worksafe = false;
}else{
	$worksafe = true;
}
if (htmlspecialchars($_COOKIE['hs']) == "1")
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

	if (!isset($_REQUEST['o'])) 
	{
		$offset=0;
	}
	else
	{
		$offset = mysqli_real_escape_string($link, $_GET['o']);
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

	//perform exact search
/*	if($_SESSION["worksafe"]==true)
	{
		$output = mysqli_query($link,"SELECT id, url, title, description, body, FROM windex WHERE body LIKE '%$query%' AND enable = '1' AND worksafe = '1' AND id > $lastID OR description LIKE '%$query%' AND enable = '1' AND worksafe = '1' AND id > $lastID OR title LIKE '%$query%' AND enable = '1' AND worksafe = '1' AND id > $lastID OR url LIKE '%$query%' AND enable = '1' AND worksafe = '1' AND id > $lastID DESC LIMIT $lim");
	}
	else
	{
		$output = mysqli_query($link,"SELECT id, url, title, description, body, FROM windex WHERE body LIKE '%$query%' AND enable = '1' AND id > $lastID OR description LIKE '%$query%' AND enable = '1' AND id > $lastID OR title LIKE '%$query%' AND enable = '1' AND id > $lastID OR url LIKE '%$query%' AND enable = '1' AND id > $lastID DESC LIMIT $lim");
	}		
	if(!$output)
	{
	  $error = 'Error ' . mysqli_error($link);  
	  include 'error.html.php';  
	  exit(); 
	}*/
	
	//Check if query is a url (contains http:// or https:// and no spaces). If so, put quotations around to to get an exact match
	$urlDetected = 0;
	if(strpos($query, ' ') == false && strpos($query,'.') == true && strpos($query,'"') == false && preg_match('/http/',$query) == true)
	{
		$queryOriginal = $query;
		$query = '"' . $query . '"';
		$urlDetected = 1;
	}

	//it was made safe for sql, now put it back to the way it was and use htmlspecialchars on results page
	$query = $_GET['q'];
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
		$queryNoQuotes = $query;
	}
	
	//alright then lets remove the quotes
	if($exactMatch == true)
	{
		while(strpos($queryNoQuotes,'"') !== false)
		{
			$queryNoQuotes = str_replace('"', "",$queryNoQuotes);
		}
	}

	//first remove any flags inside queryNoQuotes, also grab any required words (+ prefix) 
	$queryNoQuotesOrFlags = '';
	$requiredword = '';	
	if(strpos($queryNoQuotes,'+') !== false || strpos($queryNoQuotes,'-') !== false){
		$words = explode(' ', $queryNoQuotes);	
		$i = 0;
		foreach ($words as $word) {
			if($i != 0 && $word[0] != '-' && $word[0] != '+'){
				$queryNoQuotesOrFlags .= ' ';
			}			
			if ($word[0] != '-' && $word[0] != '+'){
				$queryNoQuotesOrFlags .= $word;
			}
			if ($word[0] == '+' && strlen($word) > 1){
				$requiredword = substr($word,1);
			}			
			$i++;
		}
	}

	//remove the '*' if contained anywhere in queryNoQuotes
	if(strpos($queryNoQuotes,'*') !== false && $exactMatch == false){
		$queryNoQuotes = str_replace('*', "",$queryNoQuotes);
	}

	$queryNoQuotes_SQLsafe = mysqli_real_escape_string($link, $queryNoQuotes);

	if($exactMatch == false)
	{
		//find longest word in query 	
		$words  = explode(' ', $queryNoQuotes);
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

	//Check if query contains a hyphenated word. MySQL doesn't handle them smartly. We will wrap quotes around hyphenated words that aren't part of a string which is already wraped in quotes.
	if((strpos($queryNoQuotes,'-') !== false || strpos($queryNoQuotes,'+') !== false) && $urlDetected == false){
		if($query == "c++" || $query == "C++"){//shitty but works
			$query = "c++ programming";
		}
		$hyphenwords = explode(' ',$query);
		$query = '';
		$quotes = 0;
		$i = 0;
		foreach ($hyphenwords as $word) {
			if(strpos($queryNoQuotes,'"') !== false){
				$quotes++;
			}
			if(((strpos($queryNoQuotes,'-') !== false && $word[0] != '-') || (strpos($queryNoQuotes,'+') !== false && $word[0] != '+')) && $quotes%2 == 0){//if hyphen exists, not a flag, not wrapped in quotes already
				$word = '"' . $word . '"';
			}
			if($i > 0){
				$query .= ' ';
			}
			$query .= $word;
			$i++;
		}	
	}

	
	//make query safe for sql again
	$query = mysqli_real_escape_string($link, $query);

	//perform full text search FOR InnoDB STORAGE ENGINE ONLY! DO NOT USE FOR MyISAM
	if($filterHTTPS == false){
		if($worksafe == true)
		{
			$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$query' IN BOOLEAN MODE) AND enable = '1' AND worksafe = '1' ORDER BY CASE WHEN LOCATE('$queryNoQuotes_SQLsafe', tags)>0 THEN 30 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 AND Match(title) AGAINST('$query' IN BOOLEAN MODE) THEN 20 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 THEN 15 WHEN Match(title) AGAINST('$query' IN BOOLEAN MODE) THEN Match(title) AGAINST('$query' IN BOOLEAN MODE) WHEN LOCATE('$queryNoQuotes_SQLsafe', body)>0 THEN 14 END DESC LIMIT $lim OFFSET $offset");
		}
		else
		{
			$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$query' IN BOOLEAN MODE) AND enable = '1' ORDER BY CASE WHEN LOCATE('$queryNoQuotes_SQLsafe', tags)>0 THEN 30 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 AND Match(title) AGAINST('$query' IN BOOLEAN MODE) THEN 20 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 THEN 15 WHEN Match(title) AGAINST('$query' IN BOOLEAN MODE) THEN Match(title) AGAINST('$query' IN BOOLEAN MODE) WHEN LOCATE('$queryNoQuotes_SQLsafe', body)>0 THEN 14 END DESC LIMIT $lim OFFSET $offset");
		}
	}
	else
	{
		if($worksafe == true)
		{
			$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$query' IN BOOLEAN MODE) AND enable = '1' AND worksafe = '1' AND http = '1' ORDER BY CASE WHEN LOCATE('$queryNoQuotes_SQLsafe', tags)>0 THEN 30 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 AND Match(title) AGAINST('$query' IN BOOLEAN MODE) THEN 20 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 THEN 15 WHEN Match(title) AGAINST('$query' IN BOOLEAN MODE) THEN Match(title) AGAINST('$query' IN BOOLEAN MODE) WHEN LOCATE('$queryNoQuotes_SQLsafe', body)>0 THEN 14 END DESC LIMIT $lim OFFSET $offset");
		}
		else
		{
			$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$query' IN BOOLEAN MODE) AND enable = '1' AND http = '1' ORDER BY CASE WHEN LOCATE('$queryNoQuotes_SQLsafe', tags)>0 THEN 30 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 AND Match(title) AGAINST('$query' IN BOOLEAN MODE) THEN 20 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 THEN 15 WHEN Match(title) AGAINST('$query' IN BOOLEAN MODE) THEN Match(title) AGAINST('$query' IN BOOLEAN MODE) WHEN LOCATE('$queryNoQuotes_SQLsafe', body)>0 THEN 14 END DESC LIMIT $lim OFFSET $offset");
		}
	}	
	/*if(!$outputFTS)//dont error out yet, will give another try below
	{
	  $error = 'Error ' . mysqli_error($link);  
	  include 'error.html.php';  
	  exit(); 
	}*/

	if($urlDetected == 1)
	{
		$query = $queryOriginal;
	}

	//perform full text search with * appended
	if(mysqli_num_rows($outputFTS) == 0 && $offset == 0 && $urlDetected == 0 && $exactMatch == false)
	{
		$starappend = 1;
		$querystar = $query;
		$querystar = str_replace('*', "",$querystar);//innodb will get fussy over some things if put in like '''' or ****
		$querystar = str_replace('"', "",$querystar);
		$querystar = str_replace('"', "",$querystar);
		$querystar = str_replace('\'', "",$querystar);
		$querystar = $querystar . '*';

		//perform full text search FOR InnoDB STORAGE ENGINE ONLY! DO NOT USE FOR MyISAM
		if($filterHTTPS == false){
			if($worksafe == true)
			{
				$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$querystar' IN BOOLEAN MODE) AND enable = '1' AND worksafe = '1' ORDER BY CASE WHEN LOCATE('$queryNoQuotes_SQLsafe', tags)>0 THEN 30 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 AND Match(title) AGAINST('$querystar' IN BOOLEAN MODE) THEN 20 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 THEN 15 WHEN Match(title) AGAINST('$querystar' IN BOOLEAN MODE) THEN Match(title) AGAINST('$querystar' IN BOOLEAN MODE) WHEN LOCATE('$queryNoQuotes_SQLsafe', body)>0 THEN 14 END DESC LIMIT $lim OFFSET $offset");
			}
			else
			{
				$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$querystar' IN BOOLEAN MODE) AND enable = '1' ORDER BY CASE WHEN LOCATE('$queryNoQuotes_SQLsafe', tags)>0 THEN 30 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 AND Match(title) AGAINST('$querystar' IN BOOLEAN MODE) THEN 20 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 THEN 15 WHEN Match(title) AGAINST('$querystar' IN BOOLEAN MODE) THEN Match(title) AGAINST('$querystar' IN BOOLEAN MODE) WHEN LOCATE('$queryNoQuotes_SQLsafe', body)>0 THEN 14 END DESC LIMIT $lim OFFSET $offset");
			}
		}
		else
		{
			if($worksafe == true)
			{
				$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$querystar' IN BOOLEAN MODE) AND enable = '1' AND worksafe = '1' AND http = '1' ORDER BY CASE WHEN LOCATE('$queryNoQuotes_SQLsafe', tags)>0 THEN 30 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 AND Match(title) AGAINST('$querystar' IN BOOLEAN MODE) THEN 20 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 THEN 15 WHEN Match(title) AGAINST('$querystar' IN BOOLEAN MODE) THEN Match(title) AGAINST('$querystar' IN BOOLEAN MODE) WHEN LOCATE('$queryNoQuotes_SQLsafe', body)>0 THEN 14 END DESC LIMIT $lim OFFSET $offset");
			}
			else
			{
				$outputFTS = mysqli_query($link, "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('$querystar' IN BOOLEAN MODE) AND enable = '1' AND http = '1' ORDER BY CASE WHEN LOCATE('$queryNoQuotes_SQLsafe', tags)>0 THEN 30 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 AND Match(title) AGAINST('$querystar' IN BOOLEAN MODE) THEN 20 WHEN LOCATE('$queryNoQuotes_SQLsafe', title)>0 THEN 15 WHEN Match(title) AGAINST('$querystar' IN BOOLEAN MODE) THEN Match(title) AGAINST('$querystar' IN BOOLEAN MODE) WHEN LOCATE('$queryNoQuotes_SQLsafe', body)>0 THEN 14 END DESC LIMIT $lim OFFSET $offset");
			}
		}
		if(!$outputFTS)
		{
		  $error = 'Error ' . mysqli_error($link);  
		  include 'error.html.php';  
		  exit(); 
		}		
	}


	$count = 0;

	//it was made safe for sql, now put it back to the way it was and use htmlspecialchars on results page
	$query = $_GET['q'];

	//Are there quotes in the query?
	$exactMatch = false;
	if(preg_match('/"/',$query) == true)
	{
		$exactMatch = true;
		$queryNoQuotes = $query;
	}
	
	//alright then lets remove the quotes
	if($exactMatch == true)
	{
		while(preg_match('/"/',$queryNoQuotes) == true)
		{
			$queryNoQuotes = str_replace('"', "",$queryNoQuotes);
		}
	}

	if($exactMatch == false)
	{
		//find longest word in query 	
		$words  = explode(' ', $query);
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

	//this will get set if position of longest word of query is found within body
	$pos = -1;

	//lets put contents of the full text search into the array
	while($row = mysqli_fetch_array($outputFTS))
	{
		//put the contents of the URL column within the DB into an array
		$id[] = $row[0];
		$url[] = $row[1];
		$title[] = JSONRealEscapeString(substr($row[2],0,150));
		$description[] = JSONRealEscapeString(substr($row[3],0,180));
		$body = JSONRealEscapeString($row[4]);
		$count++;
		$lastID = $row[0];
		
		if($exactMatch == false)
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
				$pos = stripos($body, $queryNoQuotes);
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
			$pos = stripos($body, $queryNoQuotes);
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

	$row = null;
	$totalcount = $count + $offset;

	//make safe for json
//   replace := map[string]string{"\\":"\\\\", "\t":"\\t", "\b":"\\b", "\n":"\\n", "\r":"\\r", "\f":"\\f"/*, `"`:`\"`*/}


        include 'results.json.php';    
}    

function JSONRealEscapeString($var){
	$var = str_replace("\\","\\\\",$var);
	$var = str_replace("\t","\\t",$var);
	$var = str_replace("\b","\\b",$var);
	$var = str_replace("\n","\\n",$var);
	$var = str_replace("\r","\\r",$var);
	$var = str_replace("\f","\\f",$var);
	return $var;
}

?>
