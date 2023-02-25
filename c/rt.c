//Wiby replication server tracker
//Admin creates file 'servers.csv' containing only IP and database name, one per line
//When executing, include the expected number of search results per page (eg: ./rt 12) so that a
//divisible list of available servers is allocated to the core application by the tracker.
//Tracker will check status of replica databases by attempting to connect to all listed every few seconds
//Tracker will create a copy of this file called 'res.csv' and display only the confirmed online servers
//as well as ID ranges divided across all servers so each has the same number of rows.

#include </usr/include/mysql/mysql.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <sys/time.h>

FILE *servers;
FILE *error;
FILE *res;
int c,d;
char ip[1000][100];
char db[1000][100];
char ipOK[1000][100];
char dbOK[1000][100];
char startID[1000][100];
char endID[1000][100];
char firstOnlineServerIP[100];
char firstOnlineServerDB[100];
char *resfiletext;
char totalRows[50];
char lastID[50];
char strSQL[200];

struct timeval stop, start;

void handle_error(MYSQL *con)
{
	error = fopen("rtlog", "a");
	printf("%s\n", mysql_error(con));
	fprintf(error, "%s\n", mysql_error(con));
	fclose(error);
	mysql_close(con);
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
	int timetest=0,reportinit=0,running=0,pagelim=12;
	if(argc == 2 && isnum(argv[1])==1){	
		pagelim=atoi(argv[1]);
		printf("\nStarting Replication Tracker:\n--------------------------------");
	}else{
		printf("\nStarting Replication Tracker:\n--------------------------------");
		printf("\n\nNo page limit was set, will use a default of 12.\n\nUsage: rt page_limit\n\nWhere page_limit is the expected number of search results per page.");
		printf("\nMake sure you have setup servers.csv, see the install guide for more info.");
	}

	printf("\n\nConnection Latency\n--------------------------------\n");

	while(1)
	{
		long bytecount=0;
		int serverCount=0, onlineServers=0, i=0, ipcnt=0, dbcnt=0, errcount=0, foundfirst=0,timeout=5,ignore = 0;
		int ipORdb = 0; //0 = ip, 1 = space
		servers = fopen("servers.csv", "rb");
		if (servers==NULL)
		{
			printf("Error opening 'servers.csv' file.\n");
			exit(0);
		}
		//parse server list
		while((c = fgetc(servers)) != EOF)
		{
			if(c == 35)//check if line is commented out (#)
				ignore = 1;
			if(c != 10 && c != 13 && c != 32 && c != 44 && ipORdb == 0 && ignore == 0){//if no cr/lf, commas, spaces, or comments, gather ip
				ip[serverCount][i] = c;
				ipcnt++;
			}
			if(c==44  && ignore == 0){//if comma detected, switch to gather db name
				ipORdb = 1;
				i = -1;
			}
			if(c != 10 && c != 13 && c != 32 && c != 44 && ipORdb == 1 && ignore == 0){//if no cr/lf, commas, spaces, or comments, gather db
				db[serverCount][i] = c;
				dbcnt++;
			}		
			if(c == 10){//count replication slaves
				ipORdb = 0;
				ip[serverCount][ipcnt] = 0;//null terminate string
				db[serverCount][dbcnt] = 0;
				if(ipcnt && dbcnt > 0)
					serverCount++;
				ipcnt = dbcnt = 0;
				i = -1;
				ignore = 0;
			}
			if(c != 13){
				i++;
				bytecount++;
			}
			d=c;
		}
		if(i>0 && d != 10)
			serverCount++;
		fclose(servers);

		//Allocate bytes for the res file text
//		resfiletext = (char*)calloc(bytecount+1000+(i*50),sizeof(char));
		char resfiletext[10000];
		memset(resfiletext,0,10000);

		//conect to each listed server and verify it works
		int last=0;
		for (i=0;i<serverCount;i++){
			int err = 0;
			MYSQL *con = mysql_init(NULL);
			if (con == NULL) 
			{
				handle_error(con);
				exit(0);
			}
			mysql_options(con,MYSQL_OPT_CONNECT_TIMEOUT,&timeout);
			if(timetest==0){
				gettimeofday(&start, NULL);
			}
			if (mysql_real_connect(con, ip[i], "remote_guest", "d0gemuchw0w", db[i], 0, NULL, 0) == NULL) 
			{
				handle_error(con);
				err=1;
			}
			if(timetest==0){
				gettimeofday(&stop, NULL);
				printf("%s %s | %lums", ip[i], db[i], ((stop.tv_sec - start.tv_sec) * 1000000 + stop.tv_usec - start.tv_usec)/1000);
				if(err==1)
					printf(" (Fail)");
				printf("\n");
			}		
			if(err==0){//append successful connection info to res string
				strcpy(ipOK[onlineServers],ip[i]);
				strcpy(dbOK[onlineServers],db[i]);
				last=i;
				onlineServers++;
				mysql_close(con);
			}
		}
		timetest=1;

		//get more database info needed for distributed queries
		//--------------------------------------------------------------------------------------------------------------------

		//calculate how many servers can be used, which must be divisible to or by the search results per page limit.
		int coreAssigned=onlineServers;
		if(pagelim == onlineServers || onlineServers==0){
			//do nothing
		}else if(pagelim>onlineServers){
			//compute number of servers to harness
			while(pagelim % coreAssigned != 0){
				coreAssigned--;
			}
		}else if(pagelim<onlineServers){
			//compute number of servers to harness
			while(coreAssigned % pagelim != 0){
				coreAssigned--;
			}
		}

		// connect to last available slave server and get info needed for all available (coreAssigned) slaves to handle a distributed query
		int initialinfo = 0, nRows=0;
		for (i=0;i<coreAssigned;i++){
			int err = 0, startIDint=0;
			long long int numrows=0;
			MYSQL *con = mysql_init(NULL);
			if (con == NULL) 
			{
				handle_error(con);
				exit(0);
			}
			mysql_options(con,MYSQL_OPT_CONNECT_TIMEOUT,&timeout);
			if (mysql_real_connect(con, ipOK[last], "remote_guest", "d0gemuchw0w", dbOK[last], 0, NULL, 0) == NULL) //connect to the last online server each iteration
			{
				handle_error(con);
				err=1;
			}
			if(err==0){
				if(i==0){//get initial info

					//Get total number of rows
					if (mysql_query(con, "SELECT COUNT(id) FROM windex;"))  
					{
					    handle_error(con);
					}
					MYSQL_RES *result = mysql_store_result(con);
					if(result == NULL)
					{
						handle_error(con);
						exit(0);
					}	
					MYSQL_ROW row = mysql_fetch_row(result);
					nRows = atoi(row[0]);

					//free old result data or else you'll get a memory leak
					mysql_free_result(result);

					//Get the last row id number
					if (mysql_query(con, "SELECT id FROM windex ORDER BY id DESC LIMIT 1;"))  
					{
						handle_error(con);
					}
					result = mysql_store_result(con);
					if(result == NULL)
					{
						handle_error(con);
						exit(0);
					}	
					row = mysql_fetch_row(result);
					memset(lastID, 0, 50);
					strcpy(lastID,row[0]);

					//free old result data or else you'll get a memory leak
					mysql_free_result(result);
					
					if(reportinit==0)
						printf("\nCurrent ID Ranges (Rows: %d)\n--------------------------------",nRows);
				}

				//Get id of last row of the % of the db you want to search (depending on # of slaves)
				numrows = (nRows / coreAssigned * i) + (nRows / coreAssigned) - 1;
				//printf("\n%lld",numrows);fflush(stdout); 
				sprintf(totalRows, "%lld", numrows);//convert int to string
				strcpy(strSQL,"SELECT id FROM windex ORDER BY id LIMIT ");
				strcat(strSQL,totalRows);
				strcat(strSQL,",1;");
				//SELECT id FROM windex ORDER BY id LIMIT n-1,1;
				if (mysql_query(con, strSQL))  
				{
				    handle_error(con);
				}
				MYSQL_RES *result2 = mysql_store_result(con);
				if(result2 == NULL)
				{
					handle_error(con);
					exit(0);
				}	
				MYSQL_ROW row = mysql_fetch_row(result2);

				//store endID and startID
				if(i+1 != coreAssigned)
					strcpy(endID[i],row[0]);
				else 
					strcpy(endID[i],lastID);
				//strcpy(endID[i],row[0]);

				if(i==0){
					strcpy(startID[i],"0");
				}else{
					startIDint = atoi(endID[i-1])+1;
					sprintf(startID[i], "%d", startIDint);
				}
				if(reportinit==0){
					printf("\n%s %s | %s %s",ipOK[i],dbOK[i],startID[i],endID[i]);
					if(i+1 == coreAssigned)
						printf("\n\n");
					fflush(stdout);
				}

				//free old result data or else you'll get a memory leak
				mysql_free_result(result2);
				mysql_close(con);

				//update res file
				if(i>0)
					strcat(resfiletext,"\n");
				strcat(resfiletext,ipOK[i]);
				strcat(resfiletext,",");
				strcat(resfiletext,dbOK[i]);
				strcat(resfiletext,",");
				strcat(resfiletext,startID[i]);
				strcat(resfiletext,",");
				strcat(resfiletext,endID[i]);			
			}
		}
		//--------------------------------------------------------------------------------------------------------------------
		
		//get resfiletext length
		long resfiletextlen = strlen(resfiletext);
		res = fopen("res.csv","rb");
		if (res==NULL)
		{
			printf("Error opening 'res.csv' file. Will create a new one.\n");
			res = fopen("res","w+");
			if (res==NULL)
			{
				printf("Error creating 'res.csv' file.\n");
				exit(0);
			}
		}
		//Get file size
		fseek(res, 0L, SEEK_END);
		bytecount = ftell(res);
		rewind(res);

		//check if res file is different from resfiletext string.
		i=0;
		int changed=0;
		if(bytecount == resfiletextlen){
			while((c = fgetc(res)) != EOF)
			{
				if(c != resfiletext[i]){
					changed = 1;
				}
				i++;
			}		
			fclose(res);
		}else{
			changed = 1;
		}

		reportinit = 1;
		//store available servers in res file
		if(changed == 1){
			res = fopen("res.csv", "w");
			fprintf(res, "%s", resfiletext);
			fclose(res);
			reportinit = 0;
		}
		if(running == 0){
			printf("Running\n");
			fflush(stdout);
			running = 1;
		}

		//fflush(stdout);
		//free(resfiletext);
		sleep(5);
	}
}

