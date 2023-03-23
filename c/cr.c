//Wiby Web Crawler

#include </usr/include/mysql/mysql.h>
#include <stdlib.h>
#include <stdio.h>
#include <strings.h>
//#include </usr/include/curl/curl.h> //RHEL/Rocky
//#include </usr/include/curl/easy.h> //RHEL/Rocky
#include </usr/include/x86_64-linux-gnu/curl/curl.h> //ubuntu 20/22
#include </usr/include/x86_64-linux-gnu/curl/easy.h> //ubuntu 20/22
#include "htmlparse.h"
#include "urlparse.h"
#include "checkrobots.h"
#include <unistd.h>

#define url_fromlist_arraylen 102400
#define url_insert_arraylen 1024000

char /**title, *keywords, *description, *page,*/ *windexinsert, *windexupdate, *windexRandUpdate, *titlecheckinsert, /**shardinsert,*/ urlPath_finalURL[1001], folderPath_finalURL[1001], urlPrefix_finalURL[1001], urlNPNP_finalURL[1001], strDepth[101], url_fromlist[url_fromlist_arraylen], url_insert[url_insert_arraylen], previousfail[5][1001];

FILE *shardfile;
char *shardfilestr;

void finish_with_error(MYSQL *con)
{
  fprintf(stderr, "%s\n", mysql_error(con));
  mysql_close(con);
  exit(1);        
}
int isnum(char *source){
	int sourcelength = strlen(source);
	for(int i=0;i < sourcelength; i++){		
		if(source[i] < 48 || source[i] > 57){
			return 0;
		}	
	}
	return 1;
}
size_t write_data(void *ptr, size_t size, size_t nmemb, FILE *stream) {
    size_t written = fwrite(ptr, size, nmemb, stream);
    return written;
}

int main(int argc, char **argv)
{
	int id_assigned=0;
	if(argc == 2 && isnum(argv[1])==1){
		id_assigned=1;
	}else if(argc >= 2){
		printf("\nWiby Web Crawler\n\nUsage: cr Crawler_ID\n\nThe indexqueue may have each page assigned a crawler ID. The ID is assigned when you specify to the Refresh Scheduler the total number of crawlers you are running, and when you update the variable '$num_crawlers' from inside of review.php and graveyard.php (line 73) to the number of crawlers you are using. The scheduler will assign pages in round-robin order a crawler ID within the range of that total.\n\nExample: If you want two crawlers running, then you should specify the first with an ID of 1, and the second with and ID of 2. Run them in separate folders, and provide a symlink to the 'robots' folder in each. Each crawler will crawl pages in the indexqueue with its corresponding ID.\n\nYou can also not assign an ID, and in that case the crawler will ignore the ID assignments. So if you have only one crawler running, assigning an ID is optional. Don't run multiple crawlers without assigning ID's.\n\nSpecify the total number of shard tables you wish to use in the 'shards' file. The crawler will round-robin insert/update rows in these tables (ws0 to wsX) along with the main 'windex' table. The default is 4.\n\n");
		exit(0);	
	}

	long int previousID[5] = {0, 1, 2, 3, 4};
	int sanity = 1;

	for(int i=0;i<5;i++){
		previousfail[i][0]=0;
	}

	//check if there are shards to include
	int nShards=0,fsize=0,shardnum=0;
	char shardc, numshards[20], shardnumstr[20];
	memset(shardnumstr,0,20);
	sprintf(shardnumstr,"0");
	if(shardfile = fopen("shards", "r")){
		fseek(shardfile, 0, SEEK_END);
		fsize = ftell(shardfile);
		fseek(shardfile, 0, SEEK_SET);
		if(fsize > 0 && fsize < 11){
			shardfilestr = malloc(fsize + 1);
			if(fread(shardfilestr, 1, fsize, shardfile)){}
			shardfilestr[fsize] = 0;
			for(int i=0;i<fsize;i++){
				shardc = shardfilestr[i];
				if(shardc != 10 && shardc != 13){
					numshards[i]=shardc;
				}
			}
			//check if its a number
			if(isnum(numshards)==1){
				nShards = atoi(numshards);
			}else{
				printf("\nThe shard file contains gibberish: '%s'. Fix this to continue.",shardfilestr);
				exit(0);
			}
			free(shardfilestr);
		}
		if(fsize>10){
			printf("\nTotal number of shards is too large (10 billion???).");
		}
		fclose(shardfile);
	}else{
                printf("\n'shards' file is missing. Create the file and indicate the number of available shards you are using or set it to 0 if you aren't. The default is 4.\n\n");
                exit(0);
        }
	if(nShards > 0){
		srand(time(NULL));
		shardnum = (rand() % nShards);
		memset(shardnumstr,0,20);
		sprintf(shardnumstr,"%d",shardnum);
	}

	while(1)
	{	
		//printf("MySQL client version: %s\n", mysql_get_client_info());
		int alreadydone = 0, permitted=1;
		 //allocates or initialises a MYSQL object

		MYSQL *con = mysql_init(NULL);

		if (con == NULL) 
		{
			finish_with_error(con);
		}

		//establish a connection to the database.  We provide connection handler, host name, user name and password parameters to the function. The other four parameters are the database name, port number, unix socket and finally the client flag
		if (mysql_real_connect(con, "localhost", "crawler", "seekout", "wiby", 0, NULL, 0) == NULL) 
		{
			finish_with_error(con);
		} 

		if (mysql_query(con, "SET CHARSET utf8;")) 
		{
		    finish_with_error(con);
		}
		
		if(id_assigned == 0){
			if (mysql_query(con, "SELECT id, url, worksafe, approver, surprise, updatable, task, crawl_tree, crawl_family, crawl_depth, crawl_pages, crawl_type, crawl_repeat, force_rules FROM indexqueue limit 1;")) 
			{
			    	finish_with_error(con);
			}
		}else{
			char indexqueuequery[2001];
			memset(indexqueuequery,0,2001);
			strcpy(indexqueuequery,"SELECT id, url, worksafe, approver, surprise, updatable, task, crawl_tree, crawl_family, crawl_depth, crawl_pages, crawl_type, crawl_repeat, force_rules FROM indexqueue WHERE crawler_id = '");
			strcat(indexqueuequery,argv[1]);
			strcat(indexqueuequery,"' LIMIT 1;");
			if (mysql_query(con, indexqueuequery)) 
			{
			    	finish_with_error(con);
			}
		}

		//We get the result set using the mysql_store_result() function. The MYSQL_RES is a structure for holding a result set
		MYSQL_RES *result = mysql_store_result(con);
		
		if(result == NULL)
		{
			finish_with_error(con);
		}

		//get the number of fields (columns) in the table
		//int num_fields = mysql_num_fields(result);

		//We fetch the rows and print them to the screen. 
		/*MYSQL_ROW row;
		while (row = mysql_fetch_row(result))	
		{
			for(int i=0; i<num_fields; i++)
			{
				printf("%s ", row[i] ? row[i] : "NULL");
			}
			printf("\n");
		}*/

		MYSQL_ROW row = mysql_fetch_row(result);
		
		int empty=0;
		if(row == NULL){
			//printf("\nQueue is empty\n");
			empty=1;
		}
		else
		{
			//convert shardnum to string
			if(nShards > 0){
				sprintf(shardnumstr,"%d",shardnum);
				//itoa(shardnum,shardnumstr,10);
			}

			printf("-----------------------------------------------------------------------------------\nFetching:");
			//grab the first entry (fifo)
			/*for(int i=0; i<num_fields; i++)
			{
				printf("%s ", row[i] ? row[i] : "NULL");
			}*/

			//Store data in first row into variables
			char *url = row[1];
			char *id = row[0];
			char *worksafe = row[2];
			char *approver = row[3];
			char *surprise = row[4];
			char *updatable = row[5];
			char *task = row[6];

			char *crawl_tree = row[7];
			char *crawl_family = row[8];
			char *crawl_depth = row[9];
			char *crawl_pages = row[10];
			char *crawl_type = row[11];
			char *crawl_repeat = row[12];
			char *force_rules = row[13];
			
			//convert crawl depth, pages to int
			int n_crawl_depth=0, n_crawl_pages=0;
			if(crawl_depth!=0){
				n_crawl_depth = atoi(crawl_depth);
			}
			if(crawl_pages!=0){
				n_crawl_pages = atoi(crawl_pages);
			}

			printf("\nURL: %s\nID: %s | Worksafe: %s | Surprise: %s | Approver: %s | Updatable: %s | task: %s\n", url, id, worksafe, surprise, approver, updatable, task);
			printf("Tree: %s | Family: %s | Depth: %s | Pages: %s | Type: %s | Repeat: %s | Rules: %s\n",crawl_tree,crawl_family,crawl_depth,crawl_pages,crawl_type,crawl_repeat,force_rules);
			//===================check if url already indexed,  ====================

			//find out if its http or https or http://www. or https://www.
			int httpwww=0, httpswww=0, http=0, https=0;
			char prefix[14];
			memset(prefix,0,14);
			strcpy(prefix,"http");
			int urlsize = strlen(url);

			if(urlsize > 4){
				if(url[4]==':' && (url[3]=='p' || url[3]=='P'))
					http = 7;
			}
			if(urlsize > 5){
				if(url[5]==':' && (url[4]=='s' || url[4]=='S'))
					https = 8;
			}
			if(urlsize > 11){
				if((url[7]=='w' || url[7]=='W') && (url[8]=='w' || url[8]=='W') && ((url[9]=='w' || url[9]=='W') || url[9]=='1' || url[9]=='2' || url[9]=='3') && url[10]=='.' ){
					httpwww = 11;
					http = https = 0;
				}
				if(url[7]=='/' && (url[8]=='w' || url[8]=='W') && (url[9]=='w' || url[9]=='W') && ((url[9]=='w' || url[9]=='W') || url[9]=='1' || url[9]=='2' || url[9]=='3') && url[11]=='.' ){
					httpswww = 12;
					http = https = 0;
				}
			}

			//set the prefix
			
			if(http > 0) strcat(prefix,"://");
			else if(https > 0) strcat(prefix,"s://");
			else if(httpwww > 0) strcat(prefix,"://www.");
			else if(httpswww > 0) strcat(prefix,"s://www.");	

			int prefixsize = httpswww+httpwww+https+http;
			char urlnoprefix[urlsize-prefixsize+1];
			char urlnopathnoprefix[urlsize-prefixsize+1];
			memset(urlnoprefix,0,urlsize-prefixsize+2);
			memset(urlnopathnoprefix,0,urlsize-prefixsize+2);
			int urlcount=0,urlnoprefixcount=0,urlnopathnoprefix_done=0;

			//store the url without prefix to urlnoprefix
			while(urlcount < urlsize+1)
			{
				if(urlcount>prefixsize-1)
				{	
					urlnoprefix[urlnoprefixcount]=url[urlcount];
					//get urlnopath
					if(url[urlcount] != '/' && urlnopathnoprefix_done==0){
						urlnopathnoprefix[urlnoprefixcount]=url[urlcount];
					}else{
						urlnopathnoprefix_done=1;
					}
					urlnoprefixcount++;
				}
				urlcount++;
			}

			//check for '/' at end of url. it may be already indexed without that so we need to account for it.
			//int urlnoprefixlength = strlen(urlnoprefix);
			int slashfound = 0;
			char urlnoprefixnoslash[urlnoprefixcount];
			memset(urlnoprefixnoslash,0,urlnoprefixcount);
			if(urlnoprefix[urlnoprefixcount-1] == '/')
			{
				strncpy(urlnoprefixnoslash,urlnoprefix,urlnoprefixcount-1);
				slashfound = 1;	
			}
			//printf("\nurlnoprefix: %s\n",urlnoprefix);

			printf("Checking if page already exists in index... ");	
			int idexistsalready = 0;
			char *idexistsvalue;
			char checkurl[urlnoprefixcount*24+1000];
			memset(checkurl,0,urlnoprefixcount*24+1000);
			if(task == 0 || task[0] == '2'){//index request did not come from refresh scheduler, or is an autocrawl url
				//strcpy(checkurl,"SELECT id,updatable,title,enable,fault,url FROM windex WHERE url = 'http://"); //replace this with a simple check for url_noprefix column match
				strcpy(checkurl,"SELECT id,updatable,title,enable,fault,url,shard FROM windex WHERE url_noprefix = '"); 
				if(slashfound==0)
				{
					strcat(checkurl,urlnoprefix);
					strcat(checkurl,"' OR url_noprefix = '");
					strcat(checkurl,urlnoprefix);strcat(checkurl,"/");
					strcat(checkurl,"' OR url_noprefix = '");
					strcat(checkurl,urlnoprefix);strcat(checkurl,"/index.html");
					strcat(checkurl,"' OR url_noprefix = '/index.htm");
					strcat(checkurl,"';");
				}
				else
				{
					strcat(checkurl,urlnoprefix);
					strcat(checkurl,"' OR url_noprefix = '");
					strcat(checkurl,urlnoprefixnoslash);
					strcat(checkurl,"' OR url_noprefix = '");
					strcat(checkurl,urlnoprefix);strcat(checkurl,"index.html");
					strcat(checkurl,"' OR url_noprefix = '");
					strcat(checkurl,urlnoprefix);strcat(checkurl,"index.htm");
					strcat(checkurl,"';");
				}
			}else{
				strcpy(checkurl,"SELECT id,updatable,title,enable,fault,url,shard FROM windex WHERE url = '");
				strcat(checkurl,url);
				strcat(checkurl,"';");
			}

			if (mysql_query(con, checkurl)) 
			{
			    finish_with_error(con);
			}

			//We get the result set using the mysql_store_result() function. The MYSQL_RES is a structure for holding a result set
			MYSQL_RES *resulturlcheck = mysql_store_result(con);

			if(resulturlcheck == NULL)
			{
				finish_with_error(con);
			}
	
			//grab the first entry (fifo)
			printf("Found ID ");
			row = mysql_fetch_row(resulturlcheck);
			char updatedefault[] = "1";
			char *updatableOldDBval = updatedefault;
			char *enableOldDBval = updatedefault;
			char *dbtitle;	
			char *fault;
			char *dburl;
			char *shard;
			
			//Catalog the previous crawl attempts (to see if they are all for the same page - which would be a bad sign)
			previousID[4] = previousID[3];
			previousID[3] = previousID[2];
			previousID[2] = previousID[1];
			previousID[1] = previousID[0];

			if(row == NULL)
			{
				printf("null");
				previousID[0] = -1;
			}
			else {
				printf("%s",row[0]);
				idexistsalready = 1;
				idexistsvalue = row[0];
				previousID[0] = atoi(row[0]);
				updatableOldDBval = row[1];
				dbtitle = row[2];
				enableOldDBval = row[3];
				fault = row[4];
				dburl=row[5];
				shard=row[6];
				if(task != 0 && task[0]=='2')
					alreadydone=1;
			}

			//Log duplicate rows (they shouldn't exist)
			int num_rows = mysql_num_rows(resulturlcheck);
			if(num_rows > 1){
				FILE *duplicates = fopen("duplicates.txt", "a");
				fputs (dburl,duplicates);
				fputs ("\r\n",duplicates);
				fclose(duplicates);			
			}

			//check robots.txt file for this domain
			urlparse(url);
			permitted = checkrobots(prefix,rootdomain,urlPath);

			int failedcrawl=0;
			if(task != 0 && task[0]=='2' && alreadydone==0 && permitted==1){
				//see if url failed to crawl last time (when link crawling)
				//as it might come up multiple times during crawl of website, should avoid recrawling it
				for(int i=0;i<5;i++){
					if(strcasecmp(previousfail[i], urlnoprefix)==0){
						sanity=0;
						failedcrawl=1;
						break;
					}
				}
				if(sanity==1)
					sleep(1);//do link crawling slowly
			}

			//Does this crawl attempt, along with the last 4 have the same ID? There is possibly a duplicate db entry, or some other problem.
			if(previousID[0] != -1 && alreadydone==0 && failedcrawl==0){
				if(previousID[0] == previousID[4] && previousID[0] == previousID[3] && previousID[0] == previousID[2] && previousID[0] == previousID[1]){
					sanity = 0;
					printf("\nWARNING: Last 5 crawl attempts are all for the same page. Will not continue crawling in this situation. Is the same page being submitted over and over? Also, duplicate table entries of the same URL in windex can cause this behavior. Check duplicates.txt");
				}else{
					sanity = 1;
				}
				
			}else{
				sanity = 1;
			}

			//printf("\n\n%ld, %ld, %ld, %ld, %ld\n",previousID[0],previousID[1],previousID[2],previousID[3],previousID[4]);
			
			//see if the server will accept http only connections on older browsers, change url to HTTP only:
			char urlHTTP[strlen(url)+100];
			memset(urlHTTP,0,strlen(url)+100);
			strcpy(urlHTTP,"http");				
			if(http > 0 || https > 0){
				strcat(urlHTTP,"://");
			}
			else if(httpwww > 0 || httpswww > 0){
				strcat(urlHTTP,"://www.");
			}			
			strcat(urlHTTP,urlnoprefix);

			if(updatableOldDBval[0] != '0' && enableOldDBval[0] != '0' && sanity == 1 && alreadydone==0 && permitted==1)
			{
				printf("\nAttempt HTTP connection: %s",urlHTTP);
				printf("\nDownloading page... ");
				//===============do the curl (download the webpage)=====================
				//curl_global_init(CURL_GLOBAL_ALL);
				CURL *curl;
				FILE *fp;
				CURLcode res;
				char outfilename[FILENAME_MAX] = "page.out";
				curl = curl_easy_init();
				long size=0;
				char *finalURL = NULL;
				long response_code;	
				int finalURLsize = 0,urltoolong=0;
				if (curl) {
					fp = fopen(outfilename,"wb");
					//Get file size
					//fseek(fp, 0L, SEEK_END);
					//size = ftell(fp);
					//set curl options
					curl_easy_setopt(curl, CURLOPT_URL, urlHTTP);// set URL to get here 
					curl_easy_setopt(curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; WebCrawler; SearchEngine)");
					curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, write_data);// send all data to this function  // 
					curl_easy_setopt(curl, CURLOPT_WRITEDATA, fp);// write the page body to this file handle  
					curl_easy_setopt(curl,CURLOPT_FOLLOWLOCATION,1L);//allow redirects
					curl_easy_setopt(curl, CURLOPT_TIMEOUT, 60L);
					curl_easy_setopt(curl, CURLOPT_CONNECTTIMEOUT, 55L);					
					curl_easy_setopt(curl, CURLOPT_MAXREDIRS, 5L);//max num of redirects
					curl_easy_setopt(curl, CURLOPT_MAXFILESIZE, 5000000L);//don't download if over 5MB
					curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);//0 or 1 to verify ssl
					//curl_easy_setopt(curl, CURLOPT_VERBOSE, 1L);//set verbose
					res = curl_easy_perform(curl);// get it! 
					//if(res == CURLE_OK) {//get final redirect url //-- don't check for this, causes segfault if "transfer closed with outstanding read data remaining"
					curl_easy_getinfo(curl, CURLINFO_EFFECTIVE_URL, &finalURL);
					curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &response_code);

					if(finalURL){
						printf("Effective URL: %s\nResponse: %ld, ", finalURL,response_code);
						finalURLsize = strlen(finalURL);
					}

					//curl_easy_cleanup(curl); //cleanup moved further down because finalURL is needed at insert
					
					//get file size
					fseek(fp, 0L, SEEK_END);
					size = ftell(fp);

					fclose(fp);
				}

				if(finalURLsize>500){
					urltoolong=1;
					printf("\nURL is too long");
				}

				char finalURLnoprefix[finalURLsize-prefixsize+100];
				char httpAllow[] = "0";
				memset(finalURLnoprefix,0,finalURLsize-prefixsize+100);
				int updatereserve=0;
				char idReserve[100];

				if(urltoolong==0){
					//see if server permitted an http connection 
					if(finalURL != NULL){
						if(finalURL[4]==':')
							httpAllow[0] = '1';
					}
					else if(http > 0 || httpwww > 0){
						httpAllow[0] = '1';
					}
					
					//Remove the prefix from the final URL, to store into url_noprefix
					//find out if its http or https or http://www. or https://www.
					httpwww=httpswww=http=https=0;

					if(finalURLsize > 4){
						if(finalURL[4]==':')
							http = 7;
						if(finalURL[4]=='s' || finalURL[4]=='S')
							https = 8;
					}
					if(finalURLsize > 11){
						if((finalURL[7]=='w' || finalURL[7]=='W') && (finalURL[8]=='w' || finalURL[8]=='W') && (finalURL[9]=='w' || finalURL[9]=='W') && finalURL[10]=='.' ){
							httpwww = 11;
							http = https = 0;
						}
						if(finalURL[7]=='/' && (finalURL[8]=='w' || finalURL[8]=='W') && (finalURL[9]=='w' || finalURL[9]=='W') && (finalURL[10]=='w' || finalURL[10]=='W') && finalURL[11]=='.' ){
							httpswww = 12;
							http = https = 0;
						}
					}	

					prefixsize = httpswww+httpwww+https+http;
					urlcount=urlnoprefixcount=0;

					//store the url without prefix to urlnoprefix
					while(finalURL[urlcount] != 0){
						if(urlcount>prefixsize-1)
						{	
							finalURLnoprefix[urlnoprefixcount]=finalURL[urlcount];
							urlnoprefixcount++;
						}
						urlcount++;
					}

					//Double check that the URL is in fact not in the DB, by also searching for the effective URL from libcurl and its url in the table
					int foundindoublecheck=0;
					if(idexistsalready == 0){
						mysql_free_result(resulturlcheck);
						char doublecheckurl[finalURLsize+100];
						memset(doublecheckurl,0,finalURLsize+100);
						strcpy(doublecheckurl,"SELECT id,updatable,title,enable,fault,url,shard FROM windex WHERE url = '");
						strcat(doublecheckurl,finalURL);
						strcat(doublecheckurl,"';");
						if (mysql_query(con, doublecheckurl)) 
						{
						    finish_with_error(con);
						}
						resulturlcheck = mysql_store_result(con);
						if(resulturlcheck == NULL)
						{
							finish_with_error(con);
						}
						row = mysql_fetch_row(resulturlcheck);					
						if(row != NULL)
						{
							printf("\nDoublechecked effective URL in windex, found ID %s",row[0]);
							idexistsalready = 1;
							idexistsvalue = row[0];
							previousID[0] = atoi(row[0]);
							updatableOldDBval = row[1];
							dbtitle = row[2];
							enableOldDBval = row[3];
							fault = row[4];
							dburl=row[5];
							shard=row[6];
							if(task != 0 && task[0]=='2')
								alreadydone=1;
							foundindoublecheck=1;
						}
						//Log duplicate rows (they shouldn't exist)
						num_rows = mysql_num_rows(resulturlcheck);
						if(num_rows > 1){
							FILE *duplicates = fopen("duplicates.txt", "a");
							fputs (dburl,duplicates);
							fputs ("\r\n",duplicates);
							fclose(duplicates);			
						}
						//Does this crawl attempt, along with the last 4 have the same ID? There is possibly a duplicate db entry, or some other problem.
						if(previousID[0] != -1){
							if(previousID[0] == previousID[4] && previousID[0] == previousID[3] && previousID[0] == previousID[2] && previousID[0] == previousID[1]){
								printf("\nWARNING: Last 5 crawl attempts are all for the same page. Will not continue crawling in this situation. Is the same page being submitted over and over? Also, duplicate table entries of the same URL in windex can cause this behavior. Check duplicates.txt");
								exit(0);
							}
						}
					}

					//if doing an update when using multiple crawlers, reserve the id and verify the URL is still associated with it 
					if(alreadydone==0 && id_assigned==1 && idexistsalready==1){
						if (mysql_query(con, "use wibytemp;")) 
						{
						    finish_with_error(con);
						}
						memset(idReserve,0,100);
						strcpy(idReserve,"INSERT into reserve_id (id) VALUES (");
						strcat(idReserve,idexistsvalue);
						strcat(idReserve,");");
						if(mysql_query(con, idReserve)) 
						{
							printf("\nID is already reserved, will try again later. Clearing old reservations...");
							if(mysql_query(con, "DELETE FROM reserve_id WHERE time < NOW() - INTERVAL 10 MINUTE")){
								finish_with_error(con);
							}else{
								printf(" Done.");							
							}
							alreadydone=1;
						}
						//back to wiby database
						if (mysql_query(con, "use wiby;")) 
						{
						    finish_with_error(con);
						}
						updatereserve=1;
						if(alreadydone==0){
							//check that the url being updated is still assigned to that ID 
							memset(checkurl,0,urlnoprefixcount*24+1000);
							if(task != 0 && task[0] == '1'){
								strcpy(checkurl,"SELECT id FROM windex WHERE url = '");
								strcat(checkurl,url);
								strcat(checkurl,"';");
							}else{
								if(foundindoublecheck==0){
									strcpy(checkurl,"SELECT id FROM windex WHERE url_noprefix = '");
									if(slashfound==0)
									{
										strcat(checkurl,urlnoprefix);
										strcat(checkurl,"' OR url_noprefix = '");
										strcat(checkurl,urlnoprefix);strcat(checkurl,"/");
										strcat(checkurl,"' OR url_noprefix = '");
										strcat(checkurl,urlnoprefix);strcat(checkurl,"/index.html");
										strcat(checkurl,"' OR url_noprefix = '/index.htm");
										strcat(checkurl,"';");
									}else{
										strcat(checkurl,urlnoprefix);
										strcat(checkurl,"' OR url_noprefix = '");
										strcat(checkurl,urlnoprefixnoslash);
										strcat(checkurl,"' OR url_noprefix = '");
										strcat(checkurl,urlnoprefix);strcat(checkurl,"index.html");
										strcat(checkurl,"' OR url_noprefix = '");
										strcat(checkurl,urlnoprefix);strcat(checkurl,"index.htm");
										strcat(checkurl,"';");
									}
								}else{
									strcpy(checkurl,"SELECT id FROM windex WHERE url = '");
									strcat(checkurl,finalURL);
									strcat(checkurl,"';");
								}
							}
							//query db
							if (mysql_query(con, checkurl)) 
							{
							    finish_with_error(con);
							}
							MYSQL_RES *resulturlcheck = mysql_store_result(con);
							if(resulturlcheck == NULL)
							{
								finish_with_error(con);
							}
							//grab the first entry (fifo)
							char *URLcheckID;
							MYSQL_ROW rowURLCheck = mysql_fetch_row(resulturlcheck);
							if(rowURLCheck != NULL)
							{						
								URLcheckID = rowURLCheck[0];
							}
							if(URLcheckID != 0 && atoi(URLcheckID) != atoi(idexistsvalue)){
								printf("\nID was already reserved, will try again later.");
								alreadydone=1;
							}
						}
					}
				}
				//=====================Extract text from HTML file=======================
				if(size < 5000000 && urltoolong==0 && alreadydone==0)
				{
					//switch on/off hyperlink collecting (if refresh is from link crawler, or from regular refresh while crawl_repeat is on, or during manual submission when appropriate limits are set)
					if((task != 0 && task[0]=='2' && (n_crawl_depth > 0 || n_crawl_depth < 0) && (n_crawl_pages > 0 || n_crawl_pages < 0)) || (task==0 && (n_crawl_depth > 0 || n_crawl_depth < 0)  && (n_crawl_pages > 0 || n_crawl_pages < 0)) || (task != 0 && task[0]=='1' && crawl_repeat != 0 && crawl_repeat[0]=='1' && (n_crawl_pages > 0 || n_crawl_pages < 0))){
						getURLs=1;
					}else{
						getURLs=0;
					}

					htmlparse();

					//need the finalURL path info also
					urlparse(finalURL);
					memset(urlPath_finalURL,0,1001);
					strcpy(urlPath_finalURL,urlPath);
					memset(folderPath_finalURL,0,1001);
					strcpy(folderPath_finalURL,folderPath);					
					memset(urlPrefix_finalURL,0,1001);
					strcpy(urlPrefix_finalURL,prefix_fromlist);
					memset(urlNPNP_finalURL,0,1001);
					strcpy(urlNPNP_finalURL,urlnopathnoprefix_fromlist);

					if(urlPrefix_finalURL[0]==0 || urlNPNP_finalURL[0]==0 || urlPath_finalURL[0]==0)
						noindex = 1;

				}else{
					noindex = 1;					
				}

				//check if rules are enforced (only for pages that are autocrawled)
				if(force_rules != 0 && force_rules[0]=='1' && task != 0 && task[0]=='2' && noindex == 0){
					if(num_scripts > 2 || num_stylesheets > 1)
						noindex = 1;
					printf("\nFailed rule check");
				}

				int skip = 0, titlechanged = 0, escape = 0, escapetotal = 0, redirected = 0;
				//Check if noindex and size
				//if(((noindex == 0 /*&& bodysize < 1900000*/ && bodysize > 10) || (noindex == 0 /*&& bodysize < 1900000*/ && descriptionsize > 10)) && response_code == 200 && alreadydone==0) 
				if((emptytitle == 0 || descriptionsize > 0 || bodysize > 0) && response_code == 200 && alreadydone==0 && noindex == 0)
				{
					//=================Allocate memory for the parsed text from htmlparse()
					//title = (char*)calloc(titlesize+1,sizeof(char));
					//keywords = (char*)calloc(keywordssize+1,sizeof(char));
					//description = (char*)calloc(descriptionsize+1,sizeof(char));
					//page = (char*)calloc(bodysize+1,sizeof(char));
					windexinsert = (char*)calloc(finalURLsize+urlnoprefixcount+bodysize+descriptionsize+keywordssize+titlesize+1001,sizeof(char));
					//shardinsert = (char*)calloc(finalURLsize+urlnoprefixcount+bodysize+descriptionsize+keywordssize+titlesize+1001,sizeof(char));
					windexupdate = (char*)calloc(finalURLsize+urlnoprefixcount+bodysize+descriptionsize+keywordssize+titlesize+1001,sizeof(char));
					windexRandUpdate = (char*)calloc(finalURLsize+urlnoprefixcount+bodysize+descriptionsize+keywordssize+titlesize+1001,sizeof(char));
					titlecheckinsert = (char*)calloc(finalURLsize+titlesize+1001,sizeof(char));
					
					/*if(title == NULL || keywords == NULL || description == NULL || page == NULL || windexinsert == NULL || windexupdate == NULL)
					{
						printf("\nError allocating memory for webpage");
						//cleanup sql stuff
						mysql_free_result(resulturlcheck);
						mysql_free_result(result);
						mysql_close(con);
						exit(0);
					}*/

				
					//Check if this is a new page: check if the title found in windex is the same as the parsed title. If not, put the page back into review.
					int dbtitlesize = 0,titlecheckTitleSize = 0, dbNoTitle=0,extrapos=0;				
					if(idexistsalready==1)
					{
						//going to insert the crawled title into a "titlecheck" table with the url for reference, then we're going to read back the
						//title and count the number of bytes vs what was read from dbtitlesize to determine if title changed
						//this is because bytes read from db must be the same charset as what is crawled to get a proper count
						//unsupported charsets can end up truncating data, giving incorrect title check, this method avoids that issue

						if (mysql_query(con, "use wibytemp;")) 
						{
						    finish_with_error(con);
						}
						//set charset based on crawled page charset tag
						if (mysql_query(con, mysqlcharset))
						{
						    finish_with_error(con);
						}
						//insert title into wibytemp for comparison
						strcpy(titlecheckinsert,"INSERT INTO titlecheck (url,title) VALUES ('");
						strcat(titlecheckinsert,finalURL);
						strcat(titlecheckinsert,"','");
						strcat(titlecheckinsert,title);
						strcat(titlecheckinsert,"');");
						if (mysql_query(con, titlecheckinsert)) 
						{
						    finish_with_error(con);
						}
						if (mysql_query(con, "SET CHARSET utf8;")) 
						{
						    finish_with_error(con);
						}
						//now read back the title from the database
						char checktitle[finalURLsize+dbtitlesize+1000];
						memset(checktitle,0,finalURLsize+dbtitlesize+1000);
						strcpy(checktitle,"SELECT title FROM titlecheck WHERE url = '");
						strcat(checktitle,finalURL);strcat(checktitle,"' ORDER BY id DESC;");
						//query db
						if (mysql_query(con, checktitle)) 
						{
						    finish_with_error(con);
						}
						MYSQL_RES *resulttitlecheck = mysql_store_result(con);
						if(resulttitlecheck == NULL)
						{
							finish_with_error(con);
						}
	
						//grab the first entry (fifo)
						MYSQL_ROW rowTitleCheck = mysql_fetch_row(resulttitlecheck);
						char *titlecheckTitle;
						int titlecheckTitleSize = 0;
						titlecheckTitle = rowTitleCheck[0];
						//printf("\n %s",rowTitleCheck[0]);

						//delete the entry from the table
						char titlecheckremove[finalURLsize+1000];
						memset(titlecheckremove,0,finalURLsize+1000);
						strcpy(titlecheckremove,"DELETE FROM titlecheck WHERE url ='");
						strcat(titlecheckremove,finalURL);strcat(titlecheckremove,"';");
						if (mysql_query(con, titlecheckremove)) 
						{
						    finish_with_error(con);
						}

						//back to wiby database
						if (mysql_query(con, "use wiby;")) 
						{
						    finish_with_error(con);
						}

						//check if original dburl is now getting redirected from finalurl (should be sent to review)
						int finalUrlsize_noprefix, dburlsize_noprefix = 0, finalURL_prefixsize = 0, dburl_prefixsize = 0,dburlsize=strlen(dburl);
						if(finalURL[4] == ':'){//if its just a switch from http to https, ignore
							finalUrlsize_noprefix = finalURLsize - 7;
							finalURL_prefixsize = 7;
						}else{
							finalUrlsize_noprefix = finalURLsize - 8;
							finalURL_prefixsize = 8;
						}
						if(dburl[4] == ':'){
							dburlsize_noprefix = dburlsize - 7;
							dburl_prefixsize = 7;
						}else{
							dburlsize_noprefix = dburlsize - 8;
							dburl_prefixsize = 8;
						}
						if(finalURLsize-finalURL_prefixsize != dburlsize-dburl_prefixsize){ 
							redirected = 1;
							printf("\nIndexed page is being redirected.");
						}else{
							for(int i=0;i<finalUrlsize_noprefix;i++){
								if(dburl[i+dburl_prefixsize] != finalURL[i+finalURL_prefixsize]){
									redirected = 1;
									printf("\nIndexed page is being redirected.");
									break;
								}
							}									
						}

						while(titlecheckTitle[titlecheckTitleSize]!='\0')//get size of title in titlecheck
						{
							titlecheckTitleSize++;
						}
						//printf("\n%d",titlecheckTitleSize);

						dbtitlesize = 0,dbNoTitle=0,extrapos=0;

						while(dbtitle[dbtitlesize]!='\0')//get size of old title in db
						{
							dbtitlesize++;
						}
						//printf("\n%d",dbtitlesize);

						//check if dbtitle matches url - If no title exists, URL's smaller than 111 chars will be used as titles, otherwise, "Untitled" will be used.
						int URL_is_dbtitle = 1;
						dbNoTitle=1;
						if(dbtitlesize==finalURLsize){
							for(int i=0;i<finalURLsize;i++){
								if(dbtitle[i] != finalURL[i]){
									URL_is_dbtitle = dbNoTitle = 0;
									break;
								}
							}
						}else{
							URL_is_dbtitle = dbNoTitle = 0;	
						}

						if(dbtitlesize == 8 && URL_is_dbtitle == 0)//check if old title in db is "Untitled"
						{	
							if(dbtitle[0]=='U' && dbtitle[1]=='n' && dbtitle[2]=='t' && dbtitle[3]=='i' && dbtitle[4]=='t' && dbtitle[5]=='l' && dbtitle[6]=='e' && dbtitle[7]=='d')							
								dbNoTitle=1;
							if(titlesize == 8 && emptytitle == 0){//off chance the title is actually called "Untitled".
								if(title[0]=='U' && title[1]=='n' && title[2]=='t' && title[3]=='i' && title[4]=='t' && title[5]=='l' && title[6]=='e' && title[7]=='d')							
									dbNoTitle=0;
							}
						}
						
						//if((dbNoTitle == 0 && dbtitlesize != (titlesize-extrapos)) || (dbNoTitle == 1 && titlesize > 0 && emptytitle == 0))  //previous, before db wibytemp titlecheck method
						if((dbNoTitle == 0 && dbtitlesize != titlecheckTitleSize) || (dbNoTitle == 1 && titlesize > 0 && emptytitle == 0) || (URL_is_dbtitle == 1 && dbtitlesize != titlecheckTitleSize && titlesize > 0 && emptytitle == 0))
						{
							titlechanged = 1;
						}
						//printf("\n|%s|\n%d\n%d\n%d\n%d\n%d",dbtitle,titlesize,dbtitlesize,extrapos,dbNoTitle,titlechanged);

						//cleanup some sql stuff
						mysql_free_result(resulttitlecheck);
					}

					if(titlechanged == 0 && redirected == 0)
					{
						//====================Load the parsed text into windex!==================

						if (mysql_query(con, mysqlcharset))//set charset based on page charset tag
						{
						    finish_with_error(con);
						}

						//strcpy(windexinsert,"INSERT INTO windex (url,title,tags,description,body,worksafe,enable,date,approver,surprise,updatable) VALUES ('");
						strcpy(windexinsert,"INSERT INTO windex (url,url_noprefix,title,description,body,worksafe,enable,date,approver,surprise,http,updatable,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,shard) VALUES ('");
						
						strcpy(windexupdate,"UPDATE windex SET url = '");

						int copiedRandom = 0;
						int reserveFail = 0;
						char randomreserve[100];
						char *randID;
						char *randshard;
						char *randURL;
						MYSQL_RES *resultRandID;

						if(idexistsalready == 0){//Insert new entry
							//For search topics to be evenly discovered by all replication servers assigned to a specific shard table, new rows must be scattered randomly across the database insead of sequental:
							//Existing rows will be randomly selected and copied (inserted) into a new row at the bottom, and the new page will take the ID number of the old one through an update.
							//select id from windex where enable = 1 order by rand() limit 1;
							//insert into windex (url,title,tags,description,body,surprise,http,updatable,worksafe,enable,date,updated,approver,fault) select url,title,tags,description,body,surprise,http,updatable,worksafe,enable,date,updated,approver,fault from windex where id = 1338;
							//the corresponding shard table will also be updated with the same ID and contents, which can be offloaded to another replica.


							printf("\nInserting into index... ");

							if (mysql_query(con, "SELECT id, shard, url_noprefix FROM windex WHERE enable = 1 ORDER BY rand() LIMIT 1;")) 
							{
							    finish_with_error(con);
							}						
							resultRandID = mysql_store_result(con);
							if (resultRandID==NULL) 
							{
							    finish_with_error(con);
							}
							MYSQL_ROW row = mysql_fetch_row(resultRandID);
							if(row != NULL){
								randID = row[0];
								idexistsvalue = row[0];
								randshard = row[1];
								randURL = row[2];
							}

							//reserve the randomly selected ID when running more than one crawler
							if(row != NULL && id_assigned==1){
								if (mysql_query(con, "use wibytemp;")) 
								{
								    finish_with_error(con);
								}
								memset(randomreserve,0,100);
								strcpy(randomreserve,"INSERT into reserve_id (id) VALUES (");
								strcat(randomreserve,randID);
								strcat(randomreserve,");");
								if (mysql_query(con, randomreserve)) 
								{
									printf("\nID is already reserved. Clearing old reservations...");
									if(mysql_query(con, "DELETE FROM reserve_id WHERE time < NOW() - INTERVAL 10 MINUTE")){
										finish_with_error(con);
									}else{
										printf(" Done.");							
									}
									reserveFail=1;//if error: more than one crawler attempted to reserve the same randomly selected ID
								}
								//back to wiby database
								if (mysql_query(con, "use wiby;")) 
								{
								    finish_with_error(con);
								}								
							}

							if(row == NULL || reserveFail==1){//if no rows in db yet or fails to reserve an ID 
								strcat(windexinsert,finalURL);strcat(windexinsert,"','");
								strcat(windexinsert,finalURLnoprefix);strcat(windexinsert,"','");
								//strcat(windexinsert,prefix);strcat(windexinsert,"','");
								if(titlesize > 0 && emptytitle == 0) {
									strcat(windexinsert,title);
								} 
								else {
									if(finalURLsize < 111){
										strcat(windexinsert,finalURL);
									}
									else{
										strcat(windexinsert,"Untitled");
									}
								}
								strcat(windexinsert,"','");
								//if(tagsize > 0) {strcat(windexinsert,keywords);}
								//strcat(windexinsert,"','");
								if(descriptionsize > 0)	{strcat(windexinsert,description);}
								strcat(windexinsert,"','");
								if(bodysize > 0) {strcat(windexinsert,body);}
								strcat(windexinsert,"',");
								strcat(windexinsert,worksafe);
								strcat(windexinsert,",1,now(),'");
								strcat(windexinsert,approver);
								strcat(windexinsert,"',");
								strcat(windexinsert,surprise);
								strcat(windexinsert,",");
								strcat(windexinsert,httpAllow);
								strcat(windexinsert,",");
								strcat(windexinsert,updatable);
								if(task != 0 && task[0]=='2'){//came from link crawling
									strcat(windexinsert,",'");
									strcat(windexinsert,crawl_tree);
									strcat(windexinsert,"','");
									strcat(windexinsert,crawl_family);
									strcat(windexinsert,"',");
									strcat(windexinsert,crawl_pages);
									strcat(windexinsert,",");
									strcat(windexinsert,crawl_type);
									strcat(windexinsert,",");
									strcat(windexinsert,"0");
								}else{
									strcat(windexinsert,",");
									strcat(windexinsert,"NULL,");
									strcat(windexinsert,"NULL,");
									strcat(windexinsert,crawl_pages);
									strcat(windexinsert,",");
									strcat(windexinsert,crawl_type);
									strcat(windexinsert,",");
									strcat(windexinsert,crawl_repeat);								
								}
								strcat(windexinsert,",");
								strcat(windexinsert,shardnumstr);	
								strcat(windexinsert,")");
								if (mysql_query(con, windexinsert)) 
								{
								    finish_with_error(con);
								}

								//insert into the shard table for the new row
								if(nShards>0){
									memset(windexinsert,0,strlen(windexinsert));
									strcpy(windexinsert,"INSERT INTO ws");
									strcat(windexinsert,shardnumstr);
									strcat(windexinsert," (id,url,url_noprefix,title,tags,description,body,surprise,http,updatable,worksafe,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,enable,date,updated,approver,fault,shard) SELECT id,url,url_noprefix,title,tags,description,body,surprise,http,updatable,worksafe,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,enable,date,updated,approver,fault,shard FROM windex WHERE id = LAST_INSERT_ID();");
									/*//get the last ID
									MYSQL_RES *resultIDnum;
									char *lastIDnum;

									if (mysql_query(con, "SELECT LAST_INSERT_ID() FROM windex limit 1")) 
									{
									    finish_with_error(con);
									}	
									MYSQL_ROW rowLastID = mysql_fetch_row(resultIDnum);
									if(rowLastID != NULL){
										lastIDnum = rowLastID[0];
									}						

									strcpy(shardinsert,"INSERT INTO ws");
									strcat(shardinsert,shardnumstr);
									strcat(shardinsert," (id,url,url_noprefix,title,tags,description,body,surprise,http,updatable,worksafe,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,enable,date,updated,approver,fault,shard) SELECT id,url,url_noprefix,title,tags,description,body,surprise,http,updatable,worksafe,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,enable,date,updated,approver,fault,shard FROM windex WHERE id = ");
									strcat(shardinsert,lastIDnum);
									if (mysql_query(con, shardinsert)) 
									{
									    finish_with_error(con);
									}
									mysql_free_result(resultIDnum);	*/
									if (mysql_query(con, windexinsert)) 
									{
									    finish_with_error(con);
									}
								}			
							}
							else{
								//copy contents of randomly selected row to a new row in windex.
								strcpy(windexRandUpdate,"INSERT INTO windex (url,url_noprefix,title,tags,description,body,surprise,http,updatable,worksafe,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,enable,date,updated,approver,fault,shard) SELECT url,url_noprefix,title,tags,description,body,surprise,http,updatable,worksafe,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,enable,date,updated,approver,fault,shard FROM windex WHERE id = ");
								strcat(windexRandUpdate,randID);
								if (mysql_query(con, windexRandUpdate))
								{
								    finish_with_error(con);
								}
								if(nShards>0){//Also copy that new row into a new row of the same ID in the round-robin assigned shard table
									//update the shard id in windex
									memset(windexRandUpdate,0,strlen(windexRandUpdate));
									strcpy(windexRandUpdate,"UPDATE windex set shard = ");
									strcat(windexRandUpdate,shardnumstr);
									strcat(windexRandUpdate," WHERE id = LAST_INSERT_ID()");
									if (mysql_query(con, windexRandUpdate))
									{
									    finish_with_error(con);
									}
									//insert that row into the next shard
									memset(windexRandUpdate,0,strlen(windexRandUpdate));
									strcpy(windexRandUpdate,"INSERT INTO ws");
									strcat(windexRandUpdate,shardnumstr);
									strcat(windexRandUpdate," (id,url,url_noprefix,title,tags,description,body,surprise,http,updatable,worksafe,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,enable,date,updated,approver,fault,shard) SELECT id,url,url_noprefix,title,tags,description,body,surprise,http,updatable,worksafe,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,enable,date,updated,approver,fault,shard FROM windex WHERE id = LAST_INSERT_ID()");
									if (mysql_query(con, windexRandUpdate))
									{
									    finish_with_error(con);
									}

									//Overwrite the randomly selected row with the contents of the newly crawled webpage
									memset(windexRandUpdate,0,strlen(windexRandUpdate));
									strcpy(windexRandUpdate,"UPDATE windex SET url = '");
									strcat(windexRandUpdate,finalURL);
									strcat(windexRandUpdate,"', url_noprefix = '");
									strcat(windexRandUpdate,finalURLnoprefix);
									strcat(windexRandUpdate,"', title = '");
									if(titlesize > 0 && emptytitle == 0){
										strcat(windexRandUpdate,title);
									}
									else{
										if(finalURLsize < 111){
											strcat(windexRandUpdate,finalURL);
										}
										else{
											strcat(windexRandUpdate,"Untitled");
										}
									}
									strcat(windexRandUpdate,"', tags = NULL, description = '");
									strcat(windexRandUpdate,description);
									strcat(windexRandUpdate,"', body = '");
									strcat(windexRandUpdate,body);	
									strcat(windexRandUpdate,"', worksafe = ");
									strcat(windexRandUpdate,worksafe);
									strcat(windexRandUpdate,", approver = '");
									strcat(windexRandUpdate,approver);
									strcat(windexRandUpdate,"', surprise = ");
									strcat(windexRandUpdate,surprise);
									strcat(windexRandUpdate,", http = ");
									strcat(windexRandUpdate,httpAllow);
									strcat(windexRandUpdate,", updatable = ");
									strcat(windexRandUpdate,updatable);
									if(task==0){//didn't come from refresh or link crawling 
										strcat(windexRandUpdate,", crawl_pages = ");
										strcat(windexRandUpdate,crawl_pages);
										strcat(windexRandUpdate,", crawl_type = ");
										strcat(windexRandUpdate,crawl_type);
										strcat(windexRandUpdate,", crawl_repeat = ");
										strcat(windexRandUpdate,crawl_repeat);
									}else if(task != 0 && task[0]=='2'){//came from link crawling
										strcat(windexRandUpdate,", crawl_tree = '");
										strcat(windexRandUpdate,crawl_tree);
										strcat(windexRandUpdate,"', crawl_family ='");
										strcat(windexRandUpdate,crawl_family);
										strcat(windexRandUpdate,"', crawl_pages = ");
										strcat(windexRandUpdate,crawl_pages);
										strcat(windexRandUpdate,", crawl_type = ");
										strcat(windexRandUpdate,crawl_type);
										strcat(windexRandUpdate,", crawl_repeat = ");
										strcat(windexRandUpdate,"0");
									}
									strcat(windexRandUpdate,", updated = CURRENT_TIMESTAMP, date = now(), fault = 0 WHERE id = ");
									strcat(windexRandUpdate,randID);
									if (mysql_query(con, windexRandUpdate))
									{
									    finish_with_error(con);
									}
																
									//Finally, update the corresponding shard table row
									if(randshard != 0){
										memset(windexRandUpdate,0,strlen(windexRandUpdate));
										strcpy(windexRandUpdate,"UPDATE ws");
										strcat(windexRandUpdate,randshard);
										strcat(windexRandUpdate," SET url = '");
										strcat(windexRandUpdate,finalURL);
										strcat(windexRandUpdate,"', url_noprefix = '");
										strcat(windexRandUpdate,finalURLnoprefix);
										strcat(windexRandUpdate,"', title = '");
										if(titlesize > 0 && emptytitle == 0){
											strcat(windexRandUpdate,title);
										}
										else{
											if(finalURLsize < 111){
												strcat(windexRandUpdate,finalURL);
											}
											else{
												strcat(windexRandUpdate,"Untitled");
											}
										}
										strcat(windexRandUpdate,"', tags = NULL, description = '");
										strcat(windexRandUpdate,description);
										strcat(windexRandUpdate,"', body = '");
										strcat(windexRandUpdate,body);	
										strcat(windexRandUpdate,"', worksafe = ");
										strcat(windexRandUpdate,worksafe);
										strcat(windexRandUpdate,", approver = '");
										strcat(windexRandUpdate,approver);
										strcat(windexRandUpdate,"', surprise = ");
										strcat(windexRandUpdate,surprise);
										strcat(windexRandUpdate,", http = ");
										strcat(windexRandUpdate,httpAllow);
										strcat(windexRandUpdate,", updatable = ");
										strcat(windexRandUpdate,updatable);
										if(task==0){//didn't come from refresh or link crawling  
											strcat(windexRandUpdate,", crawl_pages = ");
											strcat(windexRandUpdate,crawl_pages);
											strcat(windexRandUpdate,", crawl_type = ");
											strcat(windexRandUpdate,crawl_type);
											strcat(windexRandUpdate,", crawl_repeat = ");
											strcat(windexRandUpdate,crawl_repeat);
										}else if(task != 0 && task[0]=='2'){//came from link crawling
											strcat(windexRandUpdate,", crawl_tree = '");
											strcat(windexRandUpdate,crawl_tree);
											strcat(windexRandUpdate,"', crawl_family ='");
											strcat(windexRandUpdate,crawl_family);
											strcat(windexRandUpdate,"', crawl_pages = ");
											strcat(windexRandUpdate,crawl_pages);
											strcat(windexRandUpdate,", crawl_type = ");
											strcat(windexRandUpdate,crawl_type);
											strcat(windexRandUpdate,", crawl_repeat = ");
											strcat(windexRandUpdate,"0");
										}
										strcat(windexRandUpdate,", updated = CURRENT_TIMESTAMP, date = now(), fault = 0 WHERE id = ");
										strcat(windexRandUpdate,randID);
										if (mysql_query(con, windexRandUpdate))
										{
										    finish_with_error(con);
										}	
									}
								}
								copiedRandom = 1;
							}																					
						}
						if(idexistsalready == 1 || (copiedRandom == 1 && nShards == 0)){ //update an existing entry or a new entry with no shard listed in row

							printf("\nUpdating index... ");
							strcat(windexupdate,finalURL);
							strcat(windexupdate,"', url_noprefix = '");
							strcat(windexupdate,finalURLnoprefix);
							strcat(windexupdate,"', title = '");
							if(titlesize > 0 && emptytitle == 0){
								strcat(windexupdate,title);
							}
							else{
								if(finalURLsize < 111){
									strcat(windexupdate,finalURL);
								}
								else{
									strcat(windexupdate,"Untitled");
								}
							}
							if(copiedRandom == 0)//normal update
								strcat(windexupdate,"', description = '");
							else{
								strcat(windexupdate,"', tags = NULL, description = '");
							}
							strcat(windexupdate,description);
							strcat(windexupdate,"', body = '");
							strcat(windexupdate,body);	
							strcat(windexupdate,"', worksafe = ");
							strcat(windexupdate,worksafe);
							strcat(windexupdate,", approver = '");
							strcat(windexupdate,approver);
							strcat(windexupdate,"', surprise = ");
							strcat(windexupdate,surprise);
							strcat(windexupdate,", http = ");
							strcat(windexupdate,httpAllow);
							strcat(windexupdate,", updatable = ");
							strcat(windexupdate,updatable);
							if(task==0){//didn't come from refresh or link crawling  VERIFY THIS IS RIGHT
								strcat(windexupdate,", crawl_pages = ");
								strcat(windexupdate,crawl_pages);
								strcat(windexupdate,", crawl_type = ");
								strcat(windexupdate,crawl_type);
								strcat(windexupdate,", crawl_repeat = ");
								strcat(windexupdate,crawl_repeat);
							}else if(task != 0 && task[0]=='2' && idexistsalready == 0){//came from link crawling
								strcat(windexupdate,", crawl_tree = '");
								strcat(windexupdate,crawl_tree);
								strcat(windexupdate,"', crawl_family ='");
								strcat(windexupdate,crawl_family);
								strcat(windexupdate,"', crawl_pages = ");
								strcat(windexupdate,crawl_pages);
								strcat(windexupdate,", crawl_type = ");
								strcat(windexupdate,crawl_type);
								strcat(windexupdate,", crawl_repeat = ");
								strcat(windexupdate,"0");
							}
							if(copiedRandom == 0)//normal update
								strcat(windexupdate,", updated = CURRENT_TIMESTAMP, fault = 0 WHERE id = ");
							else
								strcat(windexupdate,", updated = CURRENT_TIMESTAMP, date = now(), fault = 0 WHERE id = ");
							strcat(windexupdate,idexistsvalue);//will be same as randID if a new page is replacing that row
							if (mysql_query(con, windexupdate)) 
							{
							    finish_with_error(con);
							}

							//update shard
							if(nShards>0 && idexistsalready == 1 && shard != 0){
								memset(windexupdate,0,strlen(windexupdate));
								strcpy(windexupdate,"UPDATE ws");
								strcat(windexupdate,shard);
								strcat(windexupdate," SET url = '");
								strcat(windexupdate,finalURL);
								strcat(windexupdate,"', url_noprefix = '");
								strcat(windexupdate,finalURLnoprefix);
								strcat(windexupdate,"', title = '");
								if(titlesize > 0 && emptytitle == 0){
									strcat(windexupdate,title);
								}
								else{
									if(finalURLsize < 111){
										strcat(windexupdate,finalURL);
									}
									else{
										strcat(windexupdate,"Untitled");
									}
								}
								if(copiedRandom == 0)//normal update
									strcat(windexupdate,"', description = '");
								else{
									strcat(windexupdate,"', tags = NULL, description = '");
								}
								strcat(windexupdate,description);
								strcat(windexupdate,"', body = '");
								strcat(windexupdate,body);	
								strcat(windexupdate,"', worksafe = ");
								strcat(windexupdate,worksafe);
								strcat(windexupdate,", approver = '");
								strcat(windexupdate,approver);
								strcat(windexupdate,"', surprise = ");
								strcat(windexupdate,surprise);
								strcat(windexupdate,", http = ");
								strcat(windexupdate,httpAllow);
								strcat(windexupdate,", updatable = ");
								strcat(windexupdate,updatable);
								if(task==0){//didn't come from refresh or link crawling  VERIFY THIS IS RIGHT
									strcat(windexupdate,", crawl_pages = ");
									strcat(windexupdate,crawl_pages);
									strcat(windexupdate,", crawl_type = ");
									strcat(windexupdate,crawl_type);
									strcat(windexupdate,", crawl_repeat = ");
									strcat(windexupdate,crawl_repeat);
								}
								strcat(windexupdate,", updated = CURRENT_TIMESTAMP, fault = 0 WHERE id = ");
								strcat(windexupdate,idexistsvalue);//will be same as randID if a new page is replacing that row
								if (mysql_query(con, windexupdate)) 
								{
								    finish_with_error(con);
								}
							}
						}

						//unreserve randomly selected ID
						if(id_assigned==1 && idexistsalready==0 && reserveFail==0){
							if (mysql_query(con, "use wibytemp;")) 
							{
							    finish_with_error(con);
							}
							memset(randomreserve,0,100);
							strcpy(randomreserve,"DELETE FROM reserve_id where id = ");
							strcat(randomreserve,randID);
							strcat(randomreserve,";");
							if (mysql_query(con, randomreserve)) 
							{
								finish_with_error(con);
							}
							//back to wiby database
							if (mysql_query(con, "use wiby;")) 
							{
							    finish_with_error(con);
							}							
						}
						//unreserve ID if doing an update 
						if(id_assigned==1 && updatereserve==1){
							if (mysql_query(con, "use wibytemp;")) 
							{
							    finish_with_error(con);
							}
							memset(idReserve,0,100);
							strcpy(idReserve,"DELETE FROM reserve_id where id = ");
							strcat(idReserve,idexistsvalue);
							strcat(idReserve,";");
							if(mysql_query(con, idReserve)) 
							{
								finish_with_error(con);
							}
							//back to wiby database
							if (mysql_query(con, "use wiby;")) 
							{
							    finish_with_error(con);
							}
						}
						//free result
						if(idexistsalready == 0){
							mysql_free_result(resultRandID);							
						}
						
						//===================remove the entry from the indexqueue===============
						//printf("\nRemoving from queue...");
						char sqlqueryremove[200];
						memset(sqlqueryremove,0,200);
						strcpy(sqlqueryremove,"DELETE FROM indexqueue WHERE id=");
						strcat(sqlqueryremove,id);strcat(sqlqueryremove,";");
						if (mysql_query(con, sqlqueryremove)) 
						{
						    finish_with_error(con);
						}
	
						printf("\n\nSuccess!");
					}
					//clear page from memory
					free(windexinsert); free(windexupdate); free(titlecheckinsert); free(windexRandUpdate); //free(shardinsert);
				}
				else 
				{
					skip = 1;
				}

				if((skip == 1 || titlechanged == 1 || redirected == 1)){
					//from skip check: if(((noindex == 0 && bodysize < 1900000 && bodysize > 10) || (noindex == 0 && bodysize < 1900000 && descriptionsize > 10)) && response_code == 200 && alreadydone==0) 
					//printf("\nnoindex: %d\nbodysize: %ld\ndescriptionsize %ld\nresponse_code: %d\nalreadydone: %d\nskip: %d\ntitlechanged: %d\nredirected: %d",noindex,bodysize,descriptionsize,response_code,alreadydone,skip,titlechanged,redirected);
					if(skip == 1){				
						printf("\nDoesn't want to be indexed, size too big, 404, already done, failed rules, or security issue.");
						//log previous failed link crawls
						strcpy(previousfail[4],previousfail[3]);
						strcpy(previousfail[3],previousfail[2]);
						strcpy(previousfail[2],previousfail[1]);
						strcpy(previousfail[1],previousfail[0]);
						strcpy(previousfail[0],urlnoprefix);
					}
					printf("\nRemoving from queue...");
					char sqlqueryremove[200];
					memset(sqlqueryremove,0,200);
					strcpy(sqlqueryremove,"DELETE FROM indexqueue WHERE id=");
					strcat(sqlqueryremove,id);strcat(sqlqueryremove,";");
					
					if (mysql_query(con, sqlqueryremove)) 
					{
					    finish_with_error(con);
					}
					if(alreadydone==0){
						if(idexistsalready == 1 && fault[0] == '1')
						{
							if(crawl_family != 0 && crawl_family[0] !='0'){
								printf("\nPage may no longer exist. Originated from link crawling. Removing from the index.");
								FILE *abandoned = fopen("abandoned.txt", "a");
								fputs (url,abandoned);
								fputs ("\r\n",abandoned);
								fclose(abandoned);
							}else{
								printf("\nPage may no longer exist. Moving to review.");
							}
							memset(sqlqueryremove,0,200);
							strcpy(sqlqueryremove,"DELETE FROM windex WHERE id =");
							strcat(sqlqueryremove,idexistsvalue);
							if (mysql_query(con, sqlqueryremove)) 
							{
							    finish_with_error(con);
							}
							if(nShards > 0 && shard != 0){
								memset(sqlqueryremove,0,200);
								strcpy(sqlqueryremove,"DELETE FROM ws");
								strcat(sqlqueryremove,shard);
								strcat(sqlqueryremove," WHERE id = ");
								strcat(sqlqueryremove,idexistsvalue);
								if (mysql_query(con, sqlqueryremove)) 
								{
								    finish_with_error(con);
								}
							}
							if(crawl_family == 0 || (crawl_family != 0 && crawl_family[0] =='0')){
								char sqlqueryreview[1001];
								memset(sqlqueryreview,0,1001);
								strcpy(sqlqueryreview,"INSERT INTO reviewqueue (url,worksafe) VALUES ('");
								strcat(sqlqueryreview,url);strcat(sqlqueryreview,"',");
								strcat(sqlqueryreview,worksafe);strcat(sqlqueryreview,");");	
								if (mysql_query(con, sqlqueryreview)) 
								{
								    finish_with_error(con);
								}
							}
						}
						else if(idexistsalready == 1 && fault[0] != '1')//mark that there is a fault with the page, crawler will throw it back into review if it happens again
						{
							printf("\nFault found. Will try again later.");
							char sqlqueryfault[450];
							memset(sqlqueryfault,0,450);
							strcpy(sqlqueryfault,"UPDATE windex SET updated = CURRENT_TIMESTAMP, fault = 1 WHERE id = ");
							strcat(sqlqueryfault,idexistsvalue);
							if (mysql_query(con, sqlqueryfault)) 
							{
							    finish_with_error(con);
							}
							if(nShards>0 && shard != 0){
								memset(sqlqueryfault,0,450);
								strcat(sqlqueryfault,"UPDATE ws");
								strcat(sqlqueryfault,shard);
								strcat(sqlqueryfault," SET updated = CURRENT_TIMESTAMP, fault = 1 WHERE id = ");
								strcat(sqlqueryfault,idexistsvalue);
								if (mysql_query(con, sqlqueryfault)) 
								{
								    finish_with_error(con);
								}
							}				
						}
						else{
							FILE *abandoned = fopen("abandoned.txt", "a");
							fputs (url,abandoned);
							fputs ("\r\n",abandoned);
							fclose(abandoned);
						}
				}

				//check if link crawling is specified
				//make sure duplicates don't get crawled more than once
				//check db if its already indexed too - do this at beginning instead?

				//crawl links if refresh is from link crawler, or from regular refresh while crawl_repeat is on, or during manual submission when appropriate limits are set
				}else if(nofollow==0 && getURLs==1 && alreadydone==0){
					//cycle through url list, then construct an sql string around it, then insert it to indexqueue;	
					
					//force crawl depth of 1 during a refresh if crawl_repeat is set
					if(crawl_repeat != 0 && crawl_repeat[0]=='1' && task != 0 && task[0]=='1'){
						n_crawl_depth=1;
					}

					if(n_crawl_depth>0)//below 0 = unlimited depth
						n_crawl_depth--;

					memset(strDepth,0,101);
					sprintf(strDepth,"%d",n_crawl_depth);
					//itoa(n_crawl_depth,strDepth,10);

					memset(url_fromlist,0,url_fromlist_arraylen);
					memset(url_insert,0,url_insert_arraylen);
					int loopcount=0,elementnum=0,urls=0;
					if(id_assigned == 1){
						strcpy(url_insert,"INSERT INTO indexqueue (url,worksafe,approver,surprise,task,crawl_tree,crawl_family,crawl_depth,crawl_pages,crawl_type,crawl_repeat,crawler_id) VALUES (");
					}else{
						strcpy(url_insert,"INSERT INTO indexqueue (url,worksafe,approver,surprise,task,crawl_tree,crawl_family,crawl_depth,crawl_pages,crawl_type,crawl_repeat) VALUES (");
					}
					while(urlListShuffled[loopcount]!=0){
						switch(urlListShuffled[loopcount]){
							case '\n' ://see if url can be indexed, if so, add to sql insert statement
	
								urlparse(url_fromlist);

								//check if internal or external url
								int isinternal=1;
								if(rootdomain[0]!=0){
									isinternal=0;
								}else if(url_fromlist[4]==':' || url_fromlist[5]==':'){
									isinternal=0;
								}else if((url_fromlist[0]=='w' || url_fromlist[0]=='W') && (url_fromlist[1]=='w' || url_fromlist[1]=='W') && (url_fromlist[2]=='w' || url_fromlist[2]=='W') && url_fromlist[3]=='.'){
									isinternal=0;
								}
								int urlNPNP_finalURL_len=strlen(urlNPNP_finalURL);
								int isabsolute=0;
								if(isinternal==0 && urlNPNP_finalURL_len==strlen(urlnopathnoprefix_fromlist)){
									isinternal=isabsolute=1;
									for(int q=0;q<urlNPNP_finalURL_len;q++){
										if(urlnopathnoprefix_fromlist[q]!=urlNPNP_finalURL[q]){
											isinternal=isabsolute=0;
											break;
										}
									}
								}

								if(isinternal==1 && ((crawl_type != 0 && crawl_type[0] != '2') || crawl_type == 0)){//is internal link
									urls++;
									if(urls>1){
										strcat(url_insert,", (");
									}
									if(url_fromlist[0]=='/' && url_fromlist[1] != '.'){//can't handle '..' otherwise append to insert
										strcat(url_insert,"'");
										strcat(url_insert,urlPrefix_finalURL);
										strcat(url_insert,urlNPNP_finalURL);
										strcat(url_insert,url_fromlist);
										strcat(url_insert,"',");
										strcat(url_insert,worksafe);
										strcat(url_insert,",'");
										strcat(url_insert,approver);
										strcat(url_insert,"',0,2,'");
										if(task==0){
											strcat(url_insert,url);
										}else{
											strcat(url_insert,crawl_tree);
										}
										strcat(url_insert,"','");
										strcat(url_insert,finalURL);
										strcat(url_insert,"',");
										strcat(url_insert,strDepth);
										strcat(url_insert,",");
										strcat(url_insert,crawl_pages);
										strcat(url_insert,",");
										strcat(url_insert,crawl_type);
										strcat(url_insert,",");
										strcat(url_insert,"0");
										if(id_assigned == 1){
											strcat(url_insert,",");
											strcat(url_insert,argv[1]);
										}
										strcat(url_insert,")");
									}else if(url_fromlist[0] != '/' && url_fromlist[0] != '.'){
										strcat(url_insert,"'");
										if(isabsolute==0){
											strcat(url_insert,urlPrefix_finalURL);
											strcat(url_insert,urlNPNP_finalURL);
											strcat(url_insert,folderPath_finalURL);
											strcat(url_insert,urlcopy);//scrubed index.html
										}else{
											strcat(url_insert,urlcopy);
										}
										strcat(url_insert,"',");
										strcat(url_insert,worksafe);
										strcat(url_insert,",'");
										strcat(url_insert,approver);
										strcat(url_insert,"',0,2,'");
										if(task==0){
											strcat(url_insert,url);
										}else{
											strcat(url_insert,crawl_tree);
										}
										strcat(url_insert,"','");
										strcat(url_insert,finalURL);
										strcat(url_insert,"',");
										strcat(url_insert,strDepth);
										strcat(url_insert,",");
										strcat(url_insert,crawl_pages);
										strcat(url_insert,",");
										strcat(url_insert,crawl_type);
										strcat(url_insert,",");
										strcat(url_insert,"0");
										if(id_assigned == 1){
											strcat(url_insert,",");
											strcat(url_insert,argv[1]);
										}
										strcat(url_insert,")");
									}
								}else if(isinternal==0 && crawl_type != 0 && crawl_type[0] != '0'){//is external link
									urls++;
									if(urls>1){
										strcat(url_insert,", (");
									}
									strcat(url_insert,"'");
									strcat(url_insert,rootdomain);
									strcat(url_insert,urlPath);
									strcat(url_insert,"',");
									strcat(url_insert,worksafe);
									strcat(url_insert,",'");
									strcat(url_insert,approver);
									strcat(url_insert,"',0,2,'");
									if(task==0){
										strcat(url_insert,url);
									}else{
										strcat(url_insert,crawl_tree);
									}
									strcat(url_insert,"','");
									strcat(url_insert,finalURL);
									strcat(url_insert,"',");
									strcat(url_insert,strDepth);
									strcat(url_insert,",");
									strcat(url_insert,crawl_pages);
									strcat(url_insert,",");
									strcat(url_insert,crawl_type);
									strcat(url_insert,",");
									strcat(url_insert,"0");
									if(id_assigned == 1){
										strcat(url_insert,",");
										strcat(url_insert,argv[1]);
									}
									strcat(url_insert,")");										
								}
								
								memset(url_fromlist,0,url_fromlist_arraylen);
								elementnum=0;
								loopcount++;
							default :
								if(loopcount<url_fromlist_arraylen){
									url_fromlist[elementnum]=urlListShuffled[loopcount];
								}
								elementnum++;
								loopcount++;
						}
						if(n_crawl_pages == urls || strlen(url_insert)>(url_insert_arraylen-10000))
							break;
					}
					if(urls>0){
						strcat(url_insert,";");
						//insert into db
						if (mysql_query(con, url_insert)) 
						{
						    finish_with_error(con);
						}
					}
				}
				if (curl)
					curl_easy_cleanup(curl);// cleanup curl (finalURL used at inserts, thats why we cleanup and the end here 
			}else{
				if(alreadydone == 0){
					printf("\nPage was flagged as unable to crawl or banned.");
				}else if(idexistsalready==1){
					printf("\nPage is already indexed.");
				}
				printf("\nRemoving from queue...");
				char sqlqueryremove[200];
				memset(sqlqueryremove,0,200);
				strcpy(sqlqueryremove,"DELETE FROM indexqueue WHERE id=");
				strcat(sqlqueryremove,id);
				if (mysql_query(con, sqlqueryremove)) 
				{
				    finish_with_error(con);
				}
				if(idexistsalready==1 && permitted==0){
					printf(" Removing from index...");
					memset(sqlqueryremove,0,200);
					strcpy(sqlqueryremove,"DELETE FROM windex WHERE id=");
					strcat(sqlqueryremove,idexistsvalue);
					strcat(sqlqueryremove," AND updatable != '0'");
					if (mysql_query(con, sqlqueryremove)) 
					{
					    finish_with_error(con);
					}	
					if(nShards>0 && shard != 0){
						memset(sqlqueryremove,0,200);
						strcpy(sqlqueryremove,"DELETE FROM ws");
						strcat(sqlqueryremove,shard);
						strcat(sqlqueryremove," WHERE id=");
						strcat(sqlqueryremove,idexistsvalue);
						strcat(sqlqueryremove," AND updatable != '0'");
						if (mysql_query(con, sqlqueryremove)) 
						{
						    finish_with_error(con);
						}
					}				
				}
				FILE *abandoned = fopen("abandoned.txt", "a");
				fputs (url,abandoned);
				fputs ("\r\n",abandoned);
				fclose(abandoned);
			}
			//cleanup more sql stuff
			mysql_free_result(resulturlcheck);

			//rotate shard for next insert
			if(nShards > 0){
				shardnum++;
				if(shardnum == nShards)
					shardnum=0;
				sprintf(shardnumstr,"%d",shardnum);
			}

			printf(" Awaiting next page in queue...\n\n");
		}
		//cleanup more sql stuff
		mysql_free_result(result);
		mysql_close(con);

		if(empty==1)
			sleep(5);//sleep 5 seconds
	}
  	exit(0);
}
