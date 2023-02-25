//wiby refresh scheduler

#include </usr/include/mysql/mysql.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <ctype.h>

void finish_with_error(MYSQL *con)
{
	fprintf(stderr, "%s\n", mysql_error(con));
	mysql_close(con);
	exit(1);        
}
void help(){
	printf("\nWiby Refresh Scheduler\n\nUsage: re Batch_Limit Total_Crawlers\n\nThe refresh scheduler finds pages that need to be refreshed and adds them to the indexqueue to be crawled. It will wait for the batch to complete before adding more.\n\nThere are two arguments you can set, the max number of pages to grab for each batch, and the total number of crawlers running.\n\nIf you set no arguments, it assumes you have one crawler running with an unassigned ID and will set a limit of one page per batch, rechecking if it finishes every 5 seconds. This slow paced default is fine for an index of 100k pages or so and will not use much CPU.\n\nIf you have two crawlers running and a batch limit of 100 pages, this is how you would run the scheduler:\n\n./re 100 2\n\nIn that example, each crawler will be assigned 50 pages. Once all 100 have been crawled, another batch will be assigned.\n\nYou can also specify only a batch limit and omit the total number of crawlers, it will then assume one crawler with an unassigned ID by default.\n\nIf you do not specify the number of crawlers, do not assign a number (ID) to the crawler that you have running and do not run more than one crawler.\n\nThe program will sleep for 60 seconds if there are no stale pages found.\n\n");
	exit(0);	
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

int main(int argc, char **argv)
{
	int wait_batch = 0,n_lim=1,num_cr=0,cr_count=1;
	char lim[100] = "1";

	if(argc == 3 && isnum(argv[2])==1 && isnum(argv[1])==1){
		num_cr = atoi(argv[2]);
		n_lim = atoi(argv[1]);
	}else if(argc == 2 && isnum(argv[1])==1){
		n_lim = atoi(argv[1]);
	}else if(argc > 1){
		help();
	}
	if(n_lim > 0 && argc > 1){
		strcpy(lim,argv[1]);
	}

	while(1)
	{
		//allocates or initialises a MYSQL object
		MYSQL *con = mysql_init(NULL);

		if (con == NULL) 
		{
			finish_with_error(con);
		}

		//establish a connection to the database.  We provide connection handler, host name, user name and password parameters to the function. The other four parameters are the database name, port number, unix socket and finally the client flag
		if (mysql_real_connect(con, "localhost", "crawler", "seekout", NULL, 0, NULL, 0) == NULL) 
		{
			finish_with_error(con);
		} 

		if (mysql_query(con, "use wiby")) 
		{
			finish_with_error(con);
		}

		//check if indexqueue has rows from a previous batch sent by the scheduler (should not insert more until it's empty)
		if (mysql_query(con, "SELECT id FROM indexqueue WHERE task = 1"))
		{
			finish_with_error(con);
		} 

		//We get the result set using the mysql_store_result() function. The MYSQL_RES is a structure for holding a result set	
		MYSQL_RES *result = mysql_store_result(con);

		if(result == NULL)
		{
			finish_with_error(con);
		}

		int num_rows = 0;
		int re_rows = mysql_num_rows(result);
		mysql_free_result(result);

		if(re_rows > 0){
			mysql_close(con);
			if(wait_batch == 0){
				printf("\nWaiting for batch to complete...\n\n");
			}
			wait_batch = 1;
		}else{	
			wait_batch = 0;
			char querywindex[1000];
			memset(querywindex,0,1000);
			strcpy(querywindex,"SELECT id,url,worksafe,approver,surprise,updatable,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules FROM windex WHERE (CASE WHEN updatable = 1 THEN updated < NOW() - INTERVAL 1 WEEK WHEN updatable = 2 THEN updated < NOW() - INTERVAL 1 DAY WHEN updatable = 3 THEN updated < NOW() - INTERVAL 12 HOUR WHEN updatable = 4 THEN updated < NOW() - INTERVAL 6 HOUR WHEN updatable = 5 THEN updated < NOW() - INTERVAL 3 HOUR WHEN updatable = 6 THEN updated < NOW() - INTERVAL 1 HOUR END) AND updatable != 0 AND enable = 1 LIMIT ");
			strcat(querywindex,lim);
			strcat(querywindex,";");
			//printf("\n%s",querywindex);

			//Get aging windex entries
			if (mysql_query(con,querywindex))  
			{
				finish_with_error(con);
			}

			result = mysql_store_result(con);		

			if(result == NULL)
			{
				finish_with_error(con);
			}

			//get the number of fields (columns) in the table
			//int num_fields = mysql_num_fields(result);
			num_rows = mysql_num_rows(result);

			MYSQL_ROW row;

			while(row = mysql_fetch_row(result)){	
				printf("----------------------------------------------------------\nRefresh:");

				//Store data in first row into variables
				char *id = row[0];
				char *url = row[1];
				char *worksafe = row[2];
				char *approver = row[3];
				char *surprise = row[4];
				char *updatable = row[5];
				char *crawl_tree = row[6];
				char *crawl_family = row[7];
				char *crawl_pages = row[8];
				char *crawl_type = row[9];
				char *crawl_repeat = row[10];
				char *force_rules = row[11];
			
				char str_cr_count[100];
				memset(str_cr_count,0,100);
				sprintf(str_cr_count,"%d",cr_count);
				
				printf("\nURL: %s\nID: %s\nWorksafe: %s\nSurprise: %s\nApprover: %s\nUpdatable: %s", url, id, worksafe, surprise, approver, updatable);
				if(num_cr > 0){
					printf("\nCrawler ID: %d",cr_count);
				}else{
					printf("\nCrawler ID: (null)");
				}

				char sqlqueryinsertindexqueue[2000];
				memset(sqlqueryinsertindexqueue,0,2000);
				if(num_cr == 0){
					strcpy(sqlqueryinsertindexqueue,"INSERT INTO indexqueue (url,worksafe,approver,surprise,updatable,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,task) VALUES ('");
				}else{
					strcpy(sqlqueryinsertindexqueue,"INSERT INTO indexqueue (url,worksafe,approver,surprise,updatable,crawl_tree,crawl_family,crawl_pages,crawl_type,crawl_repeat,force_rules,task,crawler_id) VALUES ('");
				}
				strcat(sqlqueryinsertindexqueue,url);strcat(sqlqueryinsertindexqueue,"','");
				strcat(sqlqueryinsertindexqueue,worksafe);strcat(sqlqueryinsertindexqueue,"','");
				strcat(sqlqueryinsertindexqueue,approver);strcat(sqlqueryinsertindexqueue,"','");
				strcat(sqlqueryinsertindexqueue,surprise);strcat(sqlqueryinsertindexqueue,"','");
				strcat(sqlqueryinsertindexqueue,updatable);strcat(sqlqueryinsertindexqueue,"',");
				if(crawl_tree != NULL){
					strcat(sqlqueryinsertindexqueue,"'");strcat(sqlqueryinsertindexqueue,crawl_tree);strcat(sqlqueryinsertindexqueue,"',");
				}else{
					strcat(sqlqueryinsertindexqueue,"NULL");strcat(sqlqueryinsertindexqueue,",");
				}
				if(crawl_family != NULL){
					strcat(sqlqueryinsertindexqueue,"'");strcat(sqlqueryinsertindexqueue,crawl_family);strcat(sqlqueryinsertindexqueue,"','");
				}else{
					strcat(sqlqueryinsertindexqueue,"NULL");strcat(sqlqueryinsertindexqueue,",'");
				}
				if(crawl_pages != NULL){
					strcat(sqlqueryinsertindexqueue,crawl_pages);strcat(sqlqueryinsertindexqueue,"','");
				}else{
					strcat(sqlqueryinsertindexqueue,"0");strcat(sqlqueryinsertindexqueue,"','");
				}
				if(crawl_type != NULL){
					strcat(sqlqueryinsertindexqueue,crawl_type);strcat(sqlqueryinsertindexqueue,"','");
				}else{
					strcat(sqlqueryinsertindexqueue,"0");strcat(sqlqueryinsertindexqueue,"','");
				}
				if(crawl_repeat != NULL){
					strcat(sqlqueryinsertindexqueue,crawl_repeat);strcat(sqlqueryinsertindexqueue,"','");
				}else{
					strcat(sqlqueryinsertindexqueue,"0");strcat(sqlqueryinsertindexqueue,"','");
				}
				if(force_rules != NULL){
					strcat(sqlqueryinsertindexqueue,force_rules);strcat(sqlqueryinsertindexqueue,"','1");
				}else{
					strcat(sqlqueryinsertindexqueue,"0");strcat(sqlqueryinsertindexqueue,"','1");
				}
				if(num_cr > 0){
					strcat(sqlqueryinsertindexqueue,"','");strcat(sqlqueryinsertindexqueue,str_cr_count);
				}
				strcat(sqlqueryinsertindexqueue,"');");

				printf("\nInserting into indexqueue...\n");
				if(mysql_query(con,sqlqueryinsertindexqueue))
				{
					finish_with_error(con);
				}

				//Assign to crawlers in round robin fashion if user indicated more than one crawler. 
				if(cr_count < num_cr && num_cr > 0){
					cr_count++;
				}else if(num_cr > 0){
					cr_count=1;
				}
			}

			//cleanup sql stuff
			mysql_free_result(result);
			mysql_close(con);

			if(num_rows > 0){
				printf("\nAwaiting next set of pages...\n\n");
			}
		}

		sleep(5);//sleep 5 seconds

		if(num_rows==0 && re_rows == 0)//sleep if no rows were found
			sleep(60);//sleep 60 seconds
	}

	exit(0);
}

