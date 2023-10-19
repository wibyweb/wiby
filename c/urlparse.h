#include <stdio.h>
#include <stdlib.h>
#include <string.h>

//char url[] = "index.htm\0";
char urlcopy[1000];
char domain[1000];
char tldlist[] = "co.uk,org.uk,co.jp\0";
char buffer[1000];
char rootdomain[1000];
char urlPath[1000];
char folderPath[1000];
char urlnopathnoprefix_fromlist[1000];
char urlnoprefix_fromlist[10000];
char prefix_fromlist[14];
int prefixsize_fromlist=0;
int checkDomain(char *domain, char *substrLower, char *substrUpper, int domainLen, int substrLen);

void urlparse(char* url){
//int main(int argc, char *argv[]) {
	int foundDot=0,foundDotInPath=0,foundSlash=0,foundColon=0,slashPos=0,lastSlashPos=0,folderPathLength=0,isFile=0,pathlen=0;
	int rootdomaincount=0;
	int isIPv4=1,isIPv6=1;
	memset(buffer,0,1000);
	memset(urlcopy,0,1000);
	memset(domain,0,1000);
	memset(rootdomain,0,1000);
	memset(urlPath,0,1000);
	memset(folderPath,0,1000);
	memset(urlnoprefix_fromlist,0,1000);
	memset(urlnopathnoprefix_fromlist,0,1000);
	
	//find out if its http or https or http://www. or https://www.
	int httpwww=0, httpswww=0, http=0, https=0;
	//char prefix[12];
	memset(prefix_fromlist,0,14);
	strcpy(prefix_fromlist,"http");
	int urlsize = strlen(url);

	if(urlsize<998){
	
		//copy url (variable from crawler)
		strcpy(urlcopy,url);
		
		//truncate any "index.html" files and just use the directory path
		if(urlsize == 10){
			if(checkDomain(urlcopy,"index.html","INDEX.HTML",urlsize,10)==1){
				urlcopy[0]=0;
				urlsize=0;
			}	
		}else if(urlsize == 9){
			if(checkDomain(urlcopy,"index.htm","INDEX.HTM",urlsize,9)==1){
				urlcopy[0]=0;
				urlsize=0;
			}
		}
		if(urlsize > 10){
			if(checkDomain(urlcopy,"/index.html","/INDEX.HTML",urlsize,11)==1){
				urlcopy[urlsize-10]=0;
				urlsize-=10;
			}
		}
		if(urlsize > 9){
			if(checkDomain(urlcopy,"/index.htm","/INDEX.HTM",urlsize,10)==1){
				urlcopy[urlsize-9]=0;
				urlsize-=9;
			}
		}		

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
		if(http > 0) strcat(prefix_fromlist,"://");
		else if(https > 0) strcat(prefix_fromlist,"s://");
		else if(httpwww > 0) strcat(prefix_fromlist,"://www.");
		else if(httpswww > 0) strcat(prefix_fromlist,"s://www.");	
	
		int prefixsize_fromlist = httpswww+httpwww+https+http;
		//char urlnoprefix[urlsize-prefixsize+1];
		//memset(urlnoprefix,0,urlsize-prefixsize+1);
				
		int urlcount=0,urlnoprefixcount=0,urlnopathnoprefix_done=0,urlnopathnoprefix_len=0;
			
		//if no prefix, see if it might be a domain
		int noprebutisdomain=0;
		if(prefixsize_fromlist==0){
			memset(prefix_fromlist,0,14);
			while(urlcount < urlsize+1)
			{
				if(urlcopy[urlcount]=='.' && urlcount>0)
				{		
					noprebutisdomain=1;
					break;
				}
				if(urlcopy[urlcount]=='/')
				{		
					noprebutisdomain=0;
					break;
				}				
				urlcount++;
			}	
		}			
	
		//store the url without prefix to urlnoprefix
		urlcount=0;
		if(prefixsize_fromlist!=0 || noprebutisdomain==1){
			while(urlcount < urlsize)
			{
				if(urlcount>prefixsize_fromlist-1)
				{		
					urlnoprefix_fromlist[urlnoprefixcount]=urlcopy[urlcount];
					
					//get urlnopath
					if(urlcopy[urlcount] != '/' && urlnopathnoprefix_done==0){
						urlnopathnoprefix_fromlist[urlnoprefixcount]=urlcopy[urlcount];
						urlnopathnoprefix_len++;
					}else{
						urlnopathnoprefix_done=1;
					}
					urlnoprefixcount++;
				}
				urlcount++;
			}
		}
	
		//check for file extension like html/htm/txt if no prefix in url
		if(noprebutisdomain==1 && strlen(urlnopathnoprefix_fromlist)>4){
			if(checkDomain(urlnopathnoprefix_fromlist,".html",".HTML",urlnopathnoprefix_len,5)==1 || checkDomain(urlnopathnoprefix_fromlist,".htm",".HTM",urlnopathnoprefix_len,4)==1 || checkDomain(urlnopathnoprefix_fromlist,".txt",".txt",urlnopathnoprefix_len,4)==1 || checkDomain(urlnopathnoprefix_fromlist,".php",".PHP",urlnopathnoprefix_len,4)==1 || checkDomain(urlnopathnoprefix_fromlist,".shtml",".SHTML",urlnopathnoprefix_len,6)==1 || checkDomain(urlnopathnoprefix_fromlist,".xhtml",".XHTML",urlnopathnoprefix_len,6)==1 || checkDomain(urlnopathnoprefix_fromlist,".cgi",".CGI",urlnopathnoprefix_len,4)==1){
				memset(domain,0,1000);
				memset(urlnoprefix_fromlist,0,1000);
				memset(urlnopathnoprefix_fromlist,0,1000);	
				urlnoprefixcount=0;				
			}
		}	
		
		//get domain name
		int lenurl=strlen(urlnoprefix_fromlist);
		int numDots=0;
		int i=0;
		for(i;i<lenurl;i++){
				
			//to get folder path, locate final slash position
			if(urlnoprefix_fromlist[i]=='/')
				lastSlashPos=i;
			
			//Null terminate hostname at first slash
			if(urlnoprefix_fromlist[i]!='/')
				domain[i]=urlnoprefix_fromlist[i];
			if(urlnoprefix_fromlist[i]=='.' && foundSlash==0)
				numDots++;
			
			//get path after hostname	
			if(urlnoprefix_fromlist[i]=='/' && foundSlash==0){
				foundSlash=1;
				slashPos=i-1;
				pathlen++;
			}
			if(foundSlash==1){
				urlPath[i-slashPos-1]=urlnoprefix_fromlist[i];
				pathlen++;		
				if(urlnoprefix_fromlist[i]=='.')	
					foundDotInPath=1;
			}
			
			if(urlnoprefix_fromlist[i]==':')
				foundColon=1;
			
			//Check if hostname is an IPv4 address
			if(((urlnoprefix_fromlist[i]<48 && urlnoprefix_fromlist[i] != '.') || (urlnoprefix_fromlist[i]>57)) && foundSlash==0)
				isIPv4=0;
			//Check if hostname is an IPv6 address
			if(((urlnoprefix_fromlist[i]<48 && urlnoprefix_fromlist[i] > 57) || (urlnoprefix_fromlist[i]<65 && urlnoprefix_fromlist[i]>70) || (urlnoprefix_fromlist[i]<97 && urlnoprefix_fromlist[i]>102)) && foundSlash==0)
				isIPv6=0;	
		}
		
		if(foundColon==0)
			isIPv6=0;	
		
		if(isIPv6==1)//if ipv6, force it into working
			numDots=1;
			
		if(foundDotInPath==0 && pathlen>1){
			//urlPath[pathlen-1]='/';
			//pathlen++;
			//urlnoprefix[lenurl]='/';
			//lenurl++;
			lastSlashPos=lenurl;	
		}
		
			
		//get folder path				
		folderPathLength=lastSlashPos-slashPos;
		for(i=0;i<folderPathLength;i++){
			folderPath[i]=urlnoprefix_fromlist[i+slashPos+1];
		}
		if(numDots==0 && isIPv6==0){		
			memset(urlPath,0,1000);
			memset(folderPath,0,1000);
			strcpy(urlPath,urlnoprefix_fromlist);
			strcpy(folderPath,urlnoprefix_fromlist);
		}		
		
		if(folderPathLength>2 && folderPath[i-2] != 0 && folderPath[i-2] != '/')
			folderPath[i-1]='/';

		if(urlPath[0]==0)
			urlPath[0]='/';
		if(folderPath[0]==0)
			folderPath[0]='/';
		
		int lendomain=strlen(domain);
		//get tld
		int lentldlist=strlen(tldlist);
		int foundDoubleDotTLD=0, k=0, dotcount=0, firstSlash=0;
		for(i=0;i<=lentldlist;i++){
			if(tldlist[i] != ',' && tldlist[i] != 0){
				buffer[k]=tldlist[i];
				k++;
			}else if(foundDoubleDotTLD==0 && (tldlist[i] == ',' || tldlist[i] == 0)){
				if(strstr(urlnoprefix_fromlist,buffer)!=NULL)
					foundDoubleDotTLD=1;
				if(numDots <=2 && foundDoubleDotTLD==1)
					strcpy(rootdomain,domain);
				if(numDots > 2 && foundDoubleDotTLD==1){
					int j=0;
					for(j;j<lenurl;j++){
							if(foundDot==1){
								if(urlnoprefix_fromlist[j]=='/')
									firstSlash=1;
								if(firstSlash==0){
									rootdomain[rootdomaincount]=urlnoprefix_fromlist[j];
									rootdomaincount++;
								}	
							}
							if(urlnoprefix_fromlist[j]=='.')
								foundDot=1;						
					}
				}
				if (tldlist[i] == ','){
					memset(buffer,0,1000);
					k=0;
				}			
			}else if(foundDoubleDotTLD==1){
				break;	
			}
		}
	
		if(foundDoubleDotTLD==0){
			foundDot=rootdomaincount=0;
			if(numDots==1){
				strcpy(rootdomain,domain);
			}else if(numDots>1){
				//skip text before first dot
				for(i=0;i<lendomain;i++){
						if(foundDot==1 || isIPv4==1){
							rootdomain[rootdomaincount]=domain[i];
							rootdomaincount++;
						}
						if(domain[i]=='.')
							foundDot=1;			
				}
			}
		}
		
//		printf("\nURL: %s\nHostname: %s\nPath: %s\nURL nopathnopre: %s\nFolder Path: %s\nURL_noprefix: %s\nPrefix: %s\nPrefix Size: %d",url,rootdomain,urlPath,urlnopathnoprefix_fromlist,folderPath,urlnoprefix_fromlist,prefix_fromlist,prefixsize_fromlist);
	}
//	return 0;
}

int checkDomain(char *domain, char *substrLower, char *substrUpper, int domainLen, int substrLen){
	int j=0;
	if(domainLen>=substrLen){
		for(int i=domainLen-substrLen;i<domainLen;i++){
			if(domain[i]!=substrLower[j] && domain[i]!=substrUpper[j]){
				return 0;	
			}
			j++;
		}
		return 1;
	}else{
		return 0;
	}	
}
