#include <stdlib.h>
#include <stdio.h>
#include <string.h>
//#include </usr/include/curl/curl.h> //RHEL/Rocky
//#include </usr/include/curl/easy.h> //RHEL/Rocky
//#include </usr/include/x86_64-linux-gnu/curl/curl.h> //ubuntu 20/22
//#include </usr/include/x86_64-linux-gnu/curl/easy.h> //ubuntu 20/22

//gcc checkrobots.c -o checkrobots -lcurl

#define rwindow_len 100
FILE *robotsfile;
char *robotsfilestr,robotsurl[1011],rwindow[rwindow_len];
//char rURLpath[] = "/dumpop/";

size_t write_data_checkrobots(void *ptr, size_t size, size_t nmemb, FILE *stream) {
    size_t written = fwrite(ptr, size, nmemb, stream);
    return written;
}
int locateInRWindow(char *window, char *birdLower, char *birdUpper, int length);

//int main(int argc, char **argv)
int checkrobots(char *rURLprefix, char *rDomain, char *rURLpath)
{
	if(rURLprefix[0]==0 || rDomain[0]==0 || rURLpath[0]==0)
		return 1;
	if(strlen(rDomain)>253)
		return 0;
	if(strlen(rURLpath)>500)
		return 0;

	memset(rwindow,'?',rwindow_len);
//	rwindow[rwindow_len]=0;
	
	curl_global_init(CURL_GLOBAL_DEFAULT);
	CURL *curl;
	FILE *fp;
	CURLcode res;
	curl = curl_easy_init();
	memset(robotsurl,0,1011);
	strcpy(robotsurl,rURLprefix);
	strcat(robotsurl,rDomain);
	strcat(robotsurl,"/robots.txt");
	char outfilename[300];
	memset(outfilename,0,300);
	strcpy(outfilename,"robots/");
	strcat(outfilename,rDomain);
	strcat(outfilename,".txt");
	long fsize=0,response_code_checkrobots=0;	
	char *finalURL_checkrobots = NULL;
	int foundfile=0,alloced=0;
	char rb,rwb;
	printf("\nChecking robots.txt: ");

	//open robots.txt file and load into memory, or download it if it doesn't exist
	if(robotsfile = fopen(outfilename, "rb")){
		fseek(robotsfile, 0, SEEK_END);
		fsize = ftell(robotsfile);
		fseek(robotsfile, 0, SEEK_SET);  /* same as rewind(f); */

		robotsfilestr = malloc(fsize + 1);
		alloced=1;
		if(fread(robotsfilestr, 1, fsize, robotsfile)){}
		fclose(robotsfile);

		robotsfilestr[fsize] = 0;
		//printf("%ld",fsize);

		foundfile=1;
	}else if (curl) {
		printf("Downloading... ");
		if(fp = fopen(outfilename,"wb")){
			//set curl options
			curl_easy_setopt(curl, CURLOPT_URL, robotsurl);// set URL to get here 
			curl_easy_setopt(curl, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Wibybot; https://wiby.me/)"); 
			curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, write_data_checkrobots);// send all data to this function  // 
			curl_easy_setopt(curl, CURLOPT_WRITEDATA, fp);// write the page body to this file handle  
			curl_easy_setopt(curl,CURLOPT_FOLLOWLOCATION,1L);//allow redirects
			curl_easy_setopt(curl, CURLOPT_TIMEOUT, 60L);
			curl_easy_setopt(curl, CURLOPT_CONNECTTIMEOUT, 55L);
			curl_easy_setopt(curl, CURLOPT_MAXREDIRS, 5L);//max num of redirects
			curl_easy_setopt(curl, CURLOPT_MAXFILESIZE, 1000000L);//don't download if over 1MB
			curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);//0 or 1 to verify ssl
			res = curl_easy_perform(curl);// get it! 
			curl_easy_getinfo(curl, CURLINFO_EFFECTIVE_URL, &finalURL_checkrobots);
			curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &response_code_checkrobots);
			//curl_easy_cleanup(curl);// always cleanup (done further down)
			fclose(fp);
			if(response_code_checkrobots!=200){
				fp = fopen(outfilename,"wb");
				fclose(fp);
			}
		}else{
			curl_easy_cleanup(curl);
			curl_global_cleanup();
			printf("\nFailed to create file: %s - proceeding anyway.",outfilename);
			return 1;
		}
	}
	if(response_code_checkrobots==200 && foundfile==0){
		robotsfile = fopen(outfilename, "rb");
		fseek(robotsfile, 0, SEEK_END);
		fsize = ftell(robotsfile);
		fseek(robotsfile, 0, SEEK_SET);  // same as rewind(f); 

		robotsfilestr = malloc(fsize + 1);
		alloced=1;
		if(fread(robotsfilestr, 1, fsize, robotsfile)){}
		fclose(robotsfile);

		robotsfilestr[fsize] = 0;
		//printf("%ld",fsize);
	}
	//parse the robots.txt file
	if(response_code_checkrobots==200 || foundfile==1 && fsize > 11){
		int foundUserAgent=0,foundDisallow=0,foundAllow=0,comment=0,match=0;
		int k=0,lenurlpath=strlen(rURLpath),rwupdated=0,result=1;
		for(int i=0;i<fsize;i++){
			rb = robotsfilestr[i];
		
			//use a rolling window of 100 bytes to detect elements, ignore space/null/tab
			if(rb != 32 && rb != 0 && rb != 9){
				for(int j=0;j<rwindow_len-1;j++){
					rwindow[j] = rwindow[j+1];
				}
				rwindow[rwindow_len-1] = rwb = rb;
				rwupdated=1;
			}
			
			if(rwb==35){
				comment=1;
			}
			if(rwb==10 || rwb==13){
				comment=0;				
			}

			if(comment==0){
				//get my specific user-agent
				//change this to something else if you want
				//robots.txt file would need to call this ahead of the '*' user-agent or else will get ignored.
				if(foundUserAgent==0 && locateInRWindow(rwindow,"user-agent:wibybot","USER-AGENT:WIBYBOT",18)==1){
					foundUserAgent=1;
					//printf("\nfound user agent!");
				}
				//get universal user-agent //change this to something else if you want
				if(foundUserAgent==0 && locateInRWindow(rwindow,"user-agent:*","USER-AGENT:*",12)==1){
					foundUserAgent=1;
					//printf("\nfound user agent!");
				}
				//if another user-agent detected after, end loop
				if(foundUserAgent==1 && locateInRWindow(rwindow,"user-agent:","USER-AGENT:",11)==1){
					break;
				}
				//end if 'Disallow: /'
				if(foundUserAgent==1 && locateInRWindow(rwindow,"disallow:/\n","DISALLOW:/\n",11)==1){
					result=0;
				}
				if(foundUserAgent==1 && locateInRWindow(rwindow,"disallow:/\r","DISALLOW:/\r",11)==1){
					result=0;
				}
				if(i==fsize-1 && foundUserAgent==1 && locateInRWindow(rwindow,"disallow:/","DISALLOW:/",10)==1){
					result=0;
				}
				//check if path is disallowed in url
				if(rwupdated==1 && foundDisallow==1){
					if(rwb!=10 && rwb!=13){
						//get path
						if(k<lenurlpath && rwb==rURLpath[k])
							match=1;
						if(k<lenurlpath && rwb!=rURLpath[k])
							match=0;
						if(k>=lenurlpath)
							match=0;
						k++;
					}
					if((i==fsize-1 && match==1) || ((rwb==10 || rwb==13) && match==1)){
						result=0;
						foundDisallow=0;
					}
					if(match==0)
						foundDisallow=k=0;
				}
				//check if path is allowed in url
				if(rwupdated==1 && foundAllow==1){
					if(rwb!=10 && rwb!=13){
						//get path
						if(k<lenurlpath && rwb==rURLpath[k])
							match=1;
						if(k<lenurlpath && rwb!=rURLpath[k])
							match=0;
						if(k>=lenurlpath)
							match=0;
						k++;
					}
					if((i==fsize-1 && match==1) || ((rwb==10 || rwb==13) && match==1)){
						printf("Permitted.");
						curl_easy_cleanup(curl);
						curl_global_cleanup();
						if(alloced==1)
							free(robotsfilestr);
						return 1;
					}
					if(match==0)
						foundAllow=k=0;
				}

				if(foundUserAgent==1 && rwupdated && locateInRWindow(rwindow,"disallow:","DISALLOW:",9)==1){
					foundDisallow=1;
					foundAllow=0;
					k=0;
					//printf("\nfound disallow");
				}
				if(foundUserAgent==1 && rwupdated && locateInRWindow(rwindow,"\nallow:","\nALLOW:",7)==1){
					foundDisallow=0;
					foundAllow=1;
					k=0;
					//printf("\nfound allow");
				}
			}
			rwupdated=0;
		}
		
		if(result==0){
			printf("Denied.");
			curl_easy_cleanup(curl);
			curl_global_cleanup();
			if(alloced==1)
				free(robotsfilestr);
			return 0;
		}else{
			printf("Permitted.");
			curl_easy_cleanup(curl);
			curl_global_cleanup();
			if(alloced==1)
				free(robotsfilestr);
			return 1;
		}
	}
	printf("Permitted.");
	curl_easy_cleanup(curl);
	if(alloced==1)
		free(robotsfilestr);
	return 1;
}


int locateInRWindow(char *window, char *birdLower, char *birdUpper, int length)
{
	int start = rwindow_len-length;
	for(int i=0;i<length;i++){
		if(window[start] != birdLower[i] && window[start] != birdUpper[i]){
			return 0;
		}
		start++;
	}
	return 1;
}
