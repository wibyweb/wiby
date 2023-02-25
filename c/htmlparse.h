//Wiby HTML Parser
//Separates text from an HTML file
//Remember to also set sql_mode = "NO_BACKSLASH_ESCAPES" in my.cnf 

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

#define window_len 100
#define charset_len 100
#define mysqlcharset_len 100
#define title_len 152
#define keywords_len 1024
#define description_len 182
#define robots_len 100
#define body_len 81920
#define urlList_len 102400
#define strURL_len 102400

FILE *bodyfile,*titlefile, *keywordsfile, *descriptionfile, *noindexfile, *nofollowfile, *charsetfile, *urlfile, *shuffledurlfile;

static char filename[] = "page.out";

char window[window_len],windowWithSpaces[window_len],charset[charset_len+1],mysqlcharset[mysqlcharset_len+1],title[title_len+1],keywords[keywords_len+1],description[description_len+1],robots[robots_len+1],body[body_len+1];
char urlList[urlList_len+1],strURL[strURL_len+1],urlListShuffled[urlList_len+1],urlListHoldShuffled[urlList_len+1];
int titlefound=0,charsetfound=0,descriptionfound=0,keywordsfound=0,robotsfound=0,nofollow=0,noindex=0,scriptfound=0,stylefound=0,urlFound=0,urlTagFound=0,numURL=0,emptytitle=1,spaces=0,seeded=0,num_stylesheets=0,num_scripts=0,getURLs=1;
long charsetsize=0,titlesize=0,keywordssize=0,descriptionsize=0,robotssize=0,bodysize=0;

int matchMySQLcharset(int html_charset_length, char *html_charset, int html_match_length, char *html_lowercase_match, char *html_uppercase_match);
int locateInWindow(char *window, char *birdLower, char *birdUpper, int length);
int locateInURL(char *url, char *birdLower, char *birdUpper, int length, int urlSize);
int canCrawl(int urlSize);
void shuffleURLs(int iterations, long urlListSize);
void sqlsafe();
void charset2mysql();

FILE *f;
char *fileStr;
char c;

void htmlparse(){
	long urlListSize=0;
	numURL=0;
	int intag=0,incomment=0,inscript=0,instyle=0,inlink=0,putspace=0,spacecount=0;
	int urlSize=0,dqcount=0;
	titlefound=charsetfound=descriptionfound=keywordsfound=robotsfound=nofollow=noindex=scriptfound=stylefound=0;
	charsetsize=titlesize=keywordssize=descriptionsize=robotssize=bodysize=0;

	memset(window,'#',window_len);
//	window[window_len]=0;
	memset(windowWithSpaces,'#',window_len);
//	windowWithSpaces[window_len]=0;
	memset(charset,0,charset_len+1);
	memset(mysqlcharset,0,mysqlcharset_len+1);
	memset(title,0,title_len+1);
	memset(keywords,0,keywords_len+1);
	memset(description,0,description_len+1);
	memset(robots,0,robots_len+1);
	memset(body,0,body_len+1);
	memset(urlList,0,urlList_len+1);
	memset(strURL,0,strURL_len+1);
	memset(urlListShuffled,0,urlList_len+1);
	memset(urlListHoldShuffled,0,urlList_len+1);
	printf("Parsing HTML... ");

	//open html file and load into memory
	f = fopen(filename, "rb");
	fseek(f, 0, SEEK_END);
	long fsize = ftell(f);
	fseek(f, 0, SEEK_SET);  /* same as rewind(f); */

	fileStr = malloc(fsize + 1);
	if(fread(fileStr, 1, fsize, f)){};
	fclose(f);

	fileStr[fsize] = 0;
	
	//Locate the charset, title, description, keywords, robots, body
	//must accomodate human error in markup
	//must double all single quotes for mysql safety
	//dont allow extra whitespace, ignore cr/lf/tabs
	//complete it all in one pass	
		
	for(int i=0;i<fsize;i++){
		c = fileStr[i];
		
		//use a rolling window of 100 bytes to detect elements, ignore lf/cr/so/si/space/null/tab
		if(c!= 10 && c != 13 && c != 14 && c != 15 && c != 127 && c != 32 && c != 0 && c != 9){
			for(int j=0;j<window_len-1;j++){
				window[j] = window[j+1];
			}
			window[window_len-1] = c;
		}
		//use a rolling window of 100 bytes to detect elements, but permit space, ignore lf/cr/null/tab
		if(c!= 10 && c != 13 && c != 14 && c != 15 && c != 127 && c != 0 && c != 9){
			for(int j=0;j<window_len-1;j++){
				windowWithSpaces[j] = windowWithSpaces[j+1];
			}
			windowWithSpaces[window_len-1] = c;
		}		
		
		//Get Title
		if(titlefound == 2){
			if(titlesize < (title_len-2) && c!= 10 && c != 13 && c != 14 && c != 15 && c != 127 && c != 0 && c != 9){
				title[titlesize]=c;
				titlesize++;
				if(c == 39){//check for single quotes and double them up for sql safety
					title[titlesize]=c;
					titlesize++;
				}
				if(c != 32 && c != 12 && c != 13 && c != 14 && c != 15 && c != 127 && c != 10 && c != 9){//some titles are just a bunch of spaces or garbage, need to check for that
					emptytitle = 0;
				}				
			}
			if(locateInWindow(window,"</title>","</TITLE>",8)==1){
				titlefound = 3;
				//remove </title> from end of title by inserting null at location of <
				titlesize -= 8;
				title[titlesize] = 0;
				//printf("\n%s",title);
			}
		}
		if(titlefound == 1 && c=='>')//in case of this situation: <title some_nonsense>
			titlefound=2;		
		if(titlefound == 0 && locateInWindow(window,"<title","<TITLE",6)==1){
				titlefound = 1;
		}
		
		//Get Charset
		if(charsetfound == 1){
			if(c == '>' || c == '/'){
				charsetfound = 2;
				//printf("\n%s",charset);
			}			
			if(charsetfound == 1 && charsetsize < charset_len && c != '"' && c != '\''){
				charset[charsetsize]=c;
				charsetsize++;
			}
		}
		if(charsetfound == 0 && locateInWindow(window,"charset=","CHARSET=",8)==1){
			charsetfound = 1;
		}
		
		//Get Description
		if(descriptionfound == 1){
			if(c == '>' || c == '/'){
				descriptionfound = 2;
				//printf("\n%s",description);
			}			
			if(descriptionfound == 1 && descriptionsize < (description_len-2) && c != '"'){
				description[descriptionsize]=c;
				descriptionsize++;
				if(c == 39){//check for single quotes and double them up for sql safety
					description[descriptionsize]=c;
					descriptionsize++;
				}				
			}
		}
		if(descriptionfound == 0 && locateInWindow(window,"description\"content=","DESCRIPTION\"CONTENT=",20)==1){
			descriptionfound = 1;
		}	
		
		//Get Keywords
		if(keywordsfound == 1){
			if(c == '>' || c == '/'){
				keywordsfound = 2;
				//printf("\n%s",keywords);
			}			
			if(keywordsfound == 1 && keywordssize < (keywords_len-2) && c != '"'){
				keywords[keywordssize]=c;
				keywordssize++;
				if(c == 39){//check for single quotes and double them up for sql safety
					keywords[keywordssize]=c;
					keywordssize++;
				}				
			}
		}
		if(keywordsfound == 0 && locateInWindow(window,"keywords\"content=","KEYWORDS\"CONTENT=",17)==1){
			keywordsfound = 1;
		}	
		
		//Get Robots (nofollow, noindex)
		if(robotsfound == 1){
			if(c == '>' || c == '/'){
				robotsfound = 2;
				//printf("\n%s",robots);
				if(locateInWindow(window,"nofollow","NOFOLLOW",8)==1)
					nofollow=1;
				if(locateInWindow(window,"noindex","NOINDEX",7)==1 || locateInWindow(window,"none","NONE",4)==1)
					noindex=nofollow=1;					
			}			
			if(robotsfound == 1 && robotssize < robots_len && c != '"' && c != '\''){
				robots[robotssize]=c;
				robotssize++;				
			}
		}
		if(robotsfound == 0 && locateInWindow(window,"robots\"content=","ROBOTS\"CONTENT=",15)==1){
			robotsfound = 1;
		}						
			
			
		if(titlefound != 2){
			//Ignore between scripts, styles, and remove all tags, repeated spaces, tabs, cr, lf, null, add a space at end of every tag
			if(c=='<'){
				intag = 1;
			}else if(c=='>'){
				intag = 0;
				putspace = 1;
			}
		
			if(locateInWindow(window,"<!--","<!--",4)==1){
				incomment = 1;
			}else if(locateInWindow(window,"-->","-->",3)==1){
				incomment = 0;
			}
							
			if(locateInWindow(window,"<script","<SCRIPT",7)==1){
				inscript = 1;
				num_scripts++;
			}else if(locateInWindow(window,"</script>","</SCRIPT>",9)==1){
				inscript = 0;
			}
			
			if(locateInWindow(window,"<style","<STYLE",6)==1){
				instyle = 1;
			}else if(locateInWindow(window,"</style>","</STYLE>",8)==1){
				instyle = 0;
			}
			
			if(locateInWindow(window,"<link","<LINK",5)==1){
				inlink = 1;
			}else if(inlink==1 && locateInWindow(window,">",">",1)==1){
				inlink = 0;
			}
			if(inlink==1){
 				if(locateInWindow(window,".css",".CSS",4)==1)
 					num_stylesheets++;
			}

			//Get Body
			//exclude remaining tags, comments, scripts, styles, cr, lf, null, tab, add a space after a '>' but only allow one
			if(intag == 0 && incomment == 0 && inscript == 0 && instyle == 0 && inlink == 0 &&  c!= 13 && c != 14 && c != 15 && c != 127 && c != 10 && c != 0 && c != 9 && bodysize < (body_len-2)){
				if(putspace == 1){
					if(spacecount == 0){
						body[bodysize]=32;
						bodysize++;
					}
					spacecount++;
					putspace=0;
				}else{				
					if(c==32)
						spacecount++;
					else spacecount = 0;
					
					if(spacecount < 2){
						body[bodysize]=c;
						bodysize++;
						
						if(c == 39){//check for single quotes and double them up for sql safety
							body[bodysize]=c;
							bodysize++;
						}						
					}						
				}	
			}
		}

		//Get URL's 
		if(getURLs==1){
			if(urlFound == 1 && incomment==0 && instyle==0 && inscript==0 && inlink == 0){
				if(c=='"' || c=='\'')
					dqcount++;
				if((c == '#' && urlSize==0) || (dqcount == 2 && urlSize == 0) || (c == ' ' && urlSize == 0)) 
					urlFound=urlTagFound=dqcount=0;
				if((c == '>' || c == ' ') && urlFound == 1){
					if(canCrawl(urlSize)==0 || (urlSize+urlListSize) >= (urlList_len-1)){
						memset(strURL,0,strURL_len+1);	
					}else{
						strcat(urlList,strURL);
						strcat(urlList,"\n");
						urlListSize+=urlSize+1;
						memset(strURL,0,strURL_len+1);
						numURL++;			
					}
					urlFound = urlTagFound = urlSize = dqcount = 0;
				}			
				if(urlFound == 1 && urlListSize < (urlList_len-2) && c != '"' && c != '\'' && urlSize < (strURL_len-2)){
					strURL[urlSize]=window[window_len-1];
					urlSize++;
				}
				if(urlSize==11){
					if(locateInWindow(window,"javascript:","JAVASCRIPT:",11)==1){
						urlFound=urlTagFound=urlSize=dqcount=0;	
						memset(strURL,0,strURL_len+1);
					}
				}			
			}
			if(urlFound == 0 && urlTagFound == 0 && incomment == 0 && instyle == 0 && inscript == 0 && inlink == 0 && locateInWindow(windowWithSpaces,"<a ","<A ",3)==1){//sometimes there is something between "<a" and "href"
				urlTagFound = 1;
			}			
			if(urlFound == 0 && urlTagFound == 1 && incomment == 0 && instyle == 0 && inscript == 0 && inlink == 0 && locateInWindow(window,"href=","HREF=",5)==1){
				urlFound = 1;
			}
		}		
	}
	
	//Convert charset to mysql equivalent
	charset2mysql();
	
	//print body to file
/*	bodyfile = fopen("body.txt","wb");
	fputs(body,bodyfile);
	fclose(bodyfile);

	//print title to file
	titlefile = fopen("title.txt","wb");
	fputs(title,titlefile);
	fclose(titlefile);
	
	//print keywords to file
	keywordsfile = fopen("keywords.txt","wb");
	fputs(keywords,keywordsfile);
	fclose(keywordsfile);
	
	//print description to file
	descriptionfile = fopen("description.txt","wb");
	fputs(description,descriptionfile);
	fclose(descriptionfile);
	
	//print charset to file
	charsetfile = fopen("charset.txt","wb");
	fputs(mysqlcharset,charsetfile);
	fclose(charsetfile);
	
	//print noindex to file
	noindexfile = fopen("noindex.txt","wb");
	if(noindex==1)
		fputs("noindex",noindexfile);
	fclose(noindexfile);				

	//print nofollow to file
	nofollowfile = fopen("nofollow.txt","wb");
	if(nofollow==1)
		fputs("nofollow",nofollowfile);
	fclose(nofollowfile);*/
	
	if(getURLs==1){
		//shuffle order of collected URLs list
		shuffleURLs(10,urlListSize);	
		//printf("\n%s",urlList);
				
		//print URLs to file
/*		urlfile = fopen("url.txt","wb");
		fputs(urlList,urlfile);
		fclose(urlfile);
		
		//print shuffled URLs to file
		shuffledurlfile = fopen("urlshuffled.txt","wb");
		fputs(urlListShuffled,shuffledurlfile);
		fclose(shuffledurlfile);*/	
	}
	
	free(fileStr);

	printf("\nbody: %ld, title: %ld, charset: %ld, description: %ld, keywords: %ld, noindex: %d, nofollow: %d",bodysize,titlesize,charsetsize,descriptionsize,keywordssize,noindex,nofollow);
}

void shuffleURLs(int iterations, long urlListSize)
{
	if(seeded==0){
		srand(time(NULL));
		seeded=1;
	}
	
	int r1,r2,r1to2; 
	int urlCount,i,j,k,l;

	if(numURL>2){
		strcpy(urlListHoldShuffled,urlList);
		for(int loops=0;loops<iterations;loops++){
			r1 = r1to2 = (rand() % numURL) + 1;
			r2 = (rand() % numURL) + 1;

			if(r1>r2){
				r1=r2;
				r2=r1to2;
			}
			if(r1==r2){
				continue;
			}

			urlCount=i=j=k=l=0;
			
			//skip to url number r1
			while(urlCount < r1 /*&& i<urlList_len*/){
				if(urlListHoldShuffled[i]=='\n')
					urlCount++;
				i++;
			}
			j=i;
			//copy to urlListShuffled starting at j until reaching r2 location
			while(urlCount<r2 /*&& j<urlList_len*/){
				urlListShuffled[k]=urlListHoldShuffled[j];
				if(urlListHoldShuffled[j]=='\n')
					urlCount++;			
				j++;
				k++;
			}
			//concat url's before i 
			while(l<i /*&& k<urlList_len*/){
				urlListShuffled[k]=urlListHoldShuffled[l];
				l++;
				k++;
			}
			//concat url's after k 
			while(k<urlListSize /*&& k<urlList_len*/){
				urlListShuffled[k]=urlListHoldShuffled[k];
				k++;
			}
			strcpy(urlListHoldShuffled,urlListShuffled);
		}
	}else{
		strcpy(urlListShuffled,urlList);
	}
	
}

void charset2mysql()
{
	//if no charset specified, use utf8
	if(charsetsize == 0){
		strcpy(mysqlcharset,"SET CHARSET utf8;");
		printf("No Charset found. %s",mysqlcharset);
	}
	else{ //else, match charset with a proper mysql charset
	
		if(matchMySQLcharset(charsetsize,charset,5,"utf-8","UTF-8")==1){
			strcpy(mysqlcharset,"SET CHARSET utf8mb4;");
			printf("%s",mysqlcharset);
		}
		else if(matchMySQLcharset(charsetsize,charset,6,"latin1","LATIN1")==1){
			strcpy(mysqlcharset,"SET CHARSET latin1;");
			printf("%s",mysqlcharset);
		}		
		else if(matchMySQLcharset(charsetsize,charset,9,"shift-jis","SHIFT-JIS")==1){
			strcpy(mysqlcharset,"SET CHARSET cp932;");
			printf("%s",mysqlcharset);
		}	
		else if(matchMySQLcharset(charsetsize,charset,6,"x-sjis","X-SJIS")==1){
			strcpy(mysqlcharset,"SET CHARSET cp932;");
			printf("%s",mysqlcharset);
		}	
		else if(matchMySQLcharset(charsetsize,charset,10,"iso-8859-1","ISO-8859-1")==1){
			strcpy(mysqlcharset,"SET CHARSET latin1;");
			printf("%s",mysqlcharset);
		}
		else if(matchMySQLcharset(charsetsize,charset,12,"windows-1252","WINDOWS-1252")==1){
			strcpy(mysqlcharset,"SET CHARSET latin1;");
			printf("%s",mysqlcharset);
		}
		else if(matchMySQLcharset(charsetsize,charset,12,"windows-1251","WINDOWS-1251")==1){
			strcpy(mysqlcharset,"SET CHARSET cp1251;");
			printf("%s",mysqlcharset);
		}		
		else if(matchMySQLcharset(charsetsize,charset,6,"koi8-r","KOI8-R")==1){
			strcpy(mysqlcharset,"SET CHARSET cp1251;");
			printf("%s",mysqlcharset);
		}
		else if(matchMySQLcharset(charsetsize,charset,6,"euc-kr","EUC-KR")==1){
			strcpy(mysqlcharset,"SET CHARSET euckr;");
			printf("%s",mysqlcharset);
		}
		else if(matchMySQLcharset(charsetsize,charset,4,"big5","BIG5")==1){
			strcpy(mysqlcharset,"SET CHARSET big5;");
			printf("%s",mysqlcharset);
		}											
		else{
			strcpy(mysqlcharset,"SET CHARSET utf8;");
			printf("Charset mismatch. %s",mysqlcharset);	
		}
	}
}

int matchMySQLcharset(int html_charset_length, char *html_charset, int html_match_length, char *html_lowercase_match, char *html_uppercase_match)
{	
	int match = 0;
	int i=0;
	for(;i<html_match_length;i++){
		if(i > html_charset_length){
			return 0;
		}
		if(html_charset[i] != 95 && html_charset[i] != 45 && html_lowercase_match[i] != 95 && html_lowercase_match[i] != 45){ // _ or -
			if(html_lowercase_match[i] != html_charset[i] && html_uppercase_match[i] != html_charset[i]){
				return 0;
			}
		}
		match = 1;
	}
	return match;
}

int locateInWindow(char *window, char *birdLower, char *birdUpper, int length)
{
	int start = window_len-length;
	for(int i=0;i<length;i++){
		if(window[start] != birdLower[i] && window[start] != birdUpper[i]){
			return 0;
		}
		start++;
	}
	return 1;
}

int locateInURL(char *url, char *birdLower, char *birdUpper, int length, int urlSize)
{
	long start = urlSize-length;
	if(urlSize >= length){
		for(int i=0;i<length;i++){
			if(url[start] != birdLower[i] && window[start] != birdUpper[i]){
				return 0;
			}
			start++;
		}
		return 1;
	}else{
		return 0;
	}
}

//Check if url can be indexed (allow relative links for html and txt files. Removing this check will add to the queue everything listed including external links.		
int canCrawl(int urlSize){
	int numDots=0,numSlash=0;
	int slashpos=0,dotspos=0;
	int extfound=0,extlocation=0,prefixfound=0;

	for(int i=0;i<urlSize;i++){
		if(urlSize>5 && strURL[i]==':' && i>3){
			if((strURL[0]!='h' && strURL[0]!='H') || (strURL[1]!='t' && strURL[1]!='T') || (strURL[2]!='t' && strURL[2]!='T') || (strURL[3]!='p' && strURL[3]!='P') || (strURL[4]!='s' && strURL[4]!='S' && strURL[4]!=':') || (strURL[5]!=':' && strURL[5]!='/'))
				return 0;
			prefixfound=1;
		}
		if(strURL[i]=='?' || strURL[i]=='\\'){
			return 0;
		}
		if(strURL[i]=='.'){
			numDots++;
		}
		if(strURL[i]=='/'){
			numSlash++;
		}		
		if(strURL[i]=='.' ){
			extfound=1;
			extlocation=i;
		}
		if(strURL[i]=='/' && extfound==1 && i>extlocation){
			extfound=0;
		}
		if(prefixfound==1 && numSlash-2<=0){
			extfound=0;
		}		
	}
	if(numDots == 0){
		return 1;
	}

	//restrict file extensions to these
	if(extfound==1 && (locateInURL(strURL,".html",".HTML",5,urlSize)==1 || locateInURL(strURL,".htm",".HTM",4,urlSize)==1 || locateInURL(strURL,".txt",".TXT",4,urlSize)==1 || locateInURL(strURL,".php",".PHP",4,urlSize)==1 || locateInURL(strURL,".asp",".ASP",4,urlSize)==1)){
		return 1;
	}	
	if(extfound==0 )
		return 1;
	return 0;
}
