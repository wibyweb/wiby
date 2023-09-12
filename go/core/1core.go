package main

import (
	"database/sql"
	_ "github.com/go-sql-driver/mysql"
//	"fmt"
	"html"
	"html/template"
	"log"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"unicode/utf8"
	//	"time"
)

type indexPage struct{}
type errorReport struct{ Error string }
type surpriseURL struct{ Url string }
type settingsPage struct{ Worksafe, FilterHTTPS bool }
type MySQLResults struct{ Id, Url, Title, Description, Body string }
type PageData struct {
	DBResults         []MySQLResults
	Query, Page string
	FindMore bool
}

func main() {
	http.HandleFunc("/", handler)
	http.HandleFunc("/json", handler)
	http.HandleFunc("/json/", handler)
	http.HandleFunc("/surprise", surprise)
	http.HandleFunc("/surprise/", surprise)
	http.HandleFunc("/settings/", settings)
	http.HandleFunc("/settings", settings)
	log.Fatal(http.ListenAndServe("localhost:8080", nil))
}

//https://golang.org/pkg/net/http/#Request
func handler(w http.ResponseWriter, r *http.Request) {
	//fmt.Fprintf(w, "%s %s \n", r.Method, r.URL)
	//fmt.Fprintf(w, "%s \n", r.URL.RawQuery)

	//check if worksafe+https cookie enabled.
	filterHTTPS := false
	worksafe := true
	worksafeHTTPSCookie, err := r.Cookie("ws")
	if err != nil {
		worksafe = true
		filterHTTPS = false
	} else if worksafeHTTPSCookie.Value == "0" {
		worksafe = false
		filterHTTPS = false
	} else if worksafeHTTPSCookie.Value == "1" {
		worksafe = true
		filterHTTPS = false
	} else if worksafeHTTPSCookie.Value == "2" {
		worksafe = false
		filterHTTPS = true
	} else if worksafeHTTPSCookie.Value == "3" {
		worksafe = true
		filterHTTPS = true
	}

	//setup for error report
	error := errorReport{}

	//Get the raw query
	m, _ := url.ParseQuery(r.URL.RawQuery)
	//Get the query parameters (q and o)
	//fmt.Fprintf(w,"%s\n%s\n", m["q"][0], m["o"][0])

	json := false
	if strings.Contains(r.URL.Path, "/json") {
		json = true
		if _, ok := m["nsfw"]; ok { //check if &nsfw added to json url
			worksafe = false
		}
	}

	query := ""
	queryNoQuotes := ""

	offset := "0"
	page := "0"

	//Check if query and page params exist
	if _, ok := m["q"]; ok {
		query = strings.Replace(m["q"][0], "'", "''", -1)
		queryNoQuotes = query
	}
	if _, ok := m["p"]; ok {//gets page num, will convert to offset further down
		page = strings.Replace(m["p"][0], "'", "''", -1)
		offset = page
	}

	lim := "12"

	if query == "" { //what do if no query found?
		//load index if no query detected
		if r.URL.Path == "/" {
			p := indexPage{}
			t, _ := template.ParseFiles("coreassets/form.html.go")
			t.Execute(w, p)
		} else if strings.Contains(r.URL.Path, "/json") { //load json info page if json selected
			p := indexPage{}
			t, _ := template.ParseFiles("coreassets/json/json.html.go")
			t.Execute(w, p)
		} else {
			p := indexPage{}
			t, _ := template.ParseFiles("coreassets/form.html.go")
			t.Execute(w, p)
		}
	} else {

		//Make sure offset is a number
		offsetInt, err := strconv.Atoi(offset)
		if err != nil {
			offset = "0"
			offsetInt = 0
		}

		//Make sure page is a number
		pageInt, err := strconv.Atoi(page)
		if err != nil {
			page = "0"
			pageInt = 0
		}

		//Convert lim to number
		limInt, _ := strconv.Atoi(lim)

		//convert page num to offset
		if offsetInt > 0 {
			offsetInt --;
		}
		offsetInt = offsetInt * limInt
		offset = strconv.Itoa(offsetInt)
		

		//get some details from the raw query
		var additions string
		querylen := len(query)
		
		//see if a search redirect (! or &) is used for a different search engine
		if json == false && (strings.Contains(m["q"][0],"!") || strings.Contains(m["q"][0],"&")){
			searchredirect(w, r, m["q"][0])
		}		
		
		//phone users
		if query[querylen-1] == ' '{
			query = query[:querylen-1]
			queryNoQuotes = queryNoQuotes[:len(queryNoQuotes)-1]
			querylen = len(query)
		}
		if querylen > 1 && query[0] == ' '{
			query = query[1:querylen]
			queryNoQuotes = queryNoQuotes[1:len(queryNoQuotes)]
			querylen = len(query)
		}
					
		//check if user wants to limit search to a specific website
		sitePos := -1
		siteEnd := 0
		siteURL := ""
		if strings.Index(strings.ToLower(query), "site:") > -1 {
			//get url user wants to search and remove it from the query stringre
			sitePos = strings.Index(strings.ToLower(query), "site:")
			siteEnd = strings.Index(query[sitePos:], " ")
			//fmt.Printf("\n%d\n%d\n",sitePos,siteEnd)
			if siteEnd > -1 && sitePos > 1 { //site is not last part of query
				siteURL = query[sitePos+5 : siteEnd+sitePos]
				query = query[:sitePos-1] + query[siteEnd+sitePos:]
				queryNoQuotes = queryNoQuotes[:sitePos-1] + queryNoQuotes[siteEnd+sitePos:]
				additions = additions + "AND url LIKE '%" + siteURL + "%' "
			} else if siteEnd > -1 && sitePos == 0 { //site is at beginning
				siteURL = query[sitePos+5 : siteEnd]
				query = query[siteEnd+1:]
				queryNoQuotes = queryNoQuotes[siteEnd+1:]
				additions = additions + "AND url LIKE '%" + siteURL + "%' "
			} else if siteEnd < 0 && sitePos > 1 { //site is at end
				siteURL = query[sitePos+5:]
				query = query[:sitePos-1]
				queryNoQuotes = queryNoQuotes[:sitePos-1]
				additions = additions + "AND url LIKE '%" + siteURL + "%' "
			}else if querylen > 5{
				query = query[5:]
			}
			querylen = len(query)
		}
		//fmt.Printf("Addition: \n%s\nQuery: '%s'\n",additions,query)

		//see if user uses -https flag (instead of cookie settings option)
		if querylen > 7 && strings.ToLower(query[querylen-7:querylen]) == " -https" {
			filterHTTPS = true
			query = query[0 : querylen-7]
			querylen = len(query)
		}

		//check if user wants to search within a time window (day,week,month)
		option := ""
		//fmt.Printf("\n'%s'\n",query)
		location := strings.Index(query, " !")
		if location == -1 {
			location = strings.Index(query, " &")
		}
		if location > -1 && strings.Index(query[location+1:querylen], " ") == -1 { //option is at end of query
			option = query[location+2 : querylen]
			query = query[:location]
			queryNoQuotes = queryNoQuotes[:location]
			querylen = len(query)
		}else if querylen > 0 && (query[0] == '!' || query[0] == '&') &&  strings.Index(query, " ") > -1{ //option is at start of query
			option = query[1:strings.Index(query, " ")]
			query = query[strings.Index(query, " ")+1:]
			queryNoQuotes = queryNoQuotes[strings.Index(queryNoQuotes, " ")+1:]
			querylen = len(query)
		}
		option = strings.ToLower(option)
		if option != "" {
			if option == "td" { //day
				additions = additions + "AND date > NOW() - INTERVAL 1 DAY "
			} else if option == "tw" { //week
				additions = additions + "AND date > NOW() - INTERVAL 7 DAY "
			} else if option == "tm" { //month
				additions = additions + "AND date > NOW() - INTERVAL 30 DAY "
			} else if option == "ty" { //year
				additions = additions + "AND date > NOW() - INTERVAL 365 DAY "
			}
		}

		//check if worksafe and filterHTTPS flags set
		if worksafe == true {
			additions = additions + "AND worksafe = '1' "
		}
		if filterHTTPS == true {
			additions = additions + "AND http = '1' "
		}

		//search if query has quotes and remove them (so we can find the longest word in the query)
		exactMatch := false
		//queryNoQuotes := query
		if strings.Contains(query, "\"") {
			exactMatch = true
			queryNoQuotes = strings.TrimLeft(queryNoQuotes, "\"")
			getlastquote := strings.Split(queryNoQuotes, "\"")
			queryNoQuotes = getlastquote[0]
			//fmt.Printf("%s \n", queryNoQuotes)
		}

		//remove the '*' if contained anywhere in queryNoQuotes
		if strings.Contains(queryNoQuotes, "*") && exactMatch == false {
			queryNoQuotes = strings.Replace(queryNoQuotes, "*", "", -1)
		}

		//Prepare to find longest word in query
		words := strings.Split(queryNoQuotes, " ")
		longestWordLength := 0
		longestWord := ""
		wordcount := 0
		longestwordelementnum := 0
		queryNoQuotesOrFlags := queryNoQuotes
		requiredword := ""
		flags := ""
		//queryNoFlags := ""
		//first remove any flags inside var queryNoQuotes, also grab any required words (+ prefix)
		if strings.Contains(queryNoQuotes, "-") || strings.Contains(queryNoQuotes, "+") {
			queryNoQuotesOrFlags = ""
			for i, wordNoFlags := range words {
				if i > 0 && strings.HasPrefix(wordNoFlags, "-") == false && strings.HasPrefix(wordNoFlags, "+") == false { //add a space after
					queryNoQuotesOrFlags += " "
				}
				if strings.HasPrefix(wordNoFlags, "-") == false && strings.HasPrefix(wordNoFlags, "+") == false {
					queryNoQuotesOrFlags += wordNoFlags
				}
				if strings.HasPrefix(wordNoFlags, "+") == true && len(wordNoFlags) > 1 && requiredword == "" { //get requiredword
					requiredword = wordNoFlags[1:len(wordNoFlags)]
				}
				if i > 0 && strings.HasPrefix(wordNoFlags, "-") == true || strings.HasPrefix(wordNoFlags, "+") == true {
					flags += " " + wordNoFlags
				}
			}
		}
		//now find longest word
		words = strings.Split(queryNoQuotes, " ")
		if exactMatch == false {
			for _, word := range words {
				if len(word) > longestWordLength {
					longestWordLength = len(word)
					longestWord = word
					longestwordelementnum = wordcount
				}
				wordcount++
			}
		}

		//create another query where all compatible words are marked as keywords
		keywordQuery := ""
		flagssetbyuser := 0
		wordlen := 0
		for i, word := range words{
			wordlen = len(word)
			if (strings.HasPrefix(word, "+") == true || strings.HasPrefix(word, "-") == true) && wordlen > 3{
				flagssetbyuser++
			}
			if i==0 && (strings.HasPrefix(word, "+") == true || strings.HasPrefix(word, "-") == true) && wordlen > 3{
				keywordQuery += word
			}
			if i==0 && (strings.HasPrefix(word, "+") == false && strings.HasPrefix(word, "-") == false) {
				if wordlen > 2 {
					keywordQuery += "+"
				}
				keywordQuery += word
			}
			if i!=0 && (strings.HasPrefix(word, "+") == true || strings.HasPrefix(word, "-") == true) && wordlen > 3{
				keywordQuery += " "
				keywordQuery += word
			}
			if i!=0 && (strings.HasPrefix(word, "+") == false && strings.HasPrefix(word, "-") == false) {
				keywordQuery += " "
				if wordlen > 2 {
					keywordQuery += "+"
				}
				keywordQuery += word
			}
		}

		//fmt.Printf("\n%s",keywordQuery)

		//get copy of original query because we might have to modify it further
		queryOriginal := query

		tRes := MySQLResults{}
		var res = PageData{}

		//init the db and set charset
		db, err := sql.Open("mysql", "guest:qwer@/wiby?charset=utf8mb4")
		if err != nil {
			p := indexPage{}
			t, _ := template.ParseFiles("coreassets/error.html.go")
			t.Execute(w, p)
		}
		defer db.Close()

		// Open doesn't open a connection. Validate DSN data:
		err = db.Ping()
		if err != nil {
			error.Error = err.Error()
			t, _ := template.ParseFiles("coreassets/error.html.go")
			t.Execute(w, error)
		}

		//Check if query is a url.
		urlDetected := false
		isURL := ""
		if strings.Index(query, " ") == -1 && strings.Index(query, "\"") == -1 && strings.Index(query, ".") > -1 { //note this will also flag on file extensions
			if len(query) > 6 && (query[0:7] == "http://" || query[0:7] == "HTTP://") {
				query = query[7:]
			} else if len(query) > 7 && (query[0:8] == "https://" || query[0:8] == "HTTPS://") {
				query = query[8:]
			}
			if len(queryNoQuotes) > 6 && (queryNoQuotes[0:7] == "http://" || queryNoQuotes[0:7] == "HTTP://") {
				queryNoQuotes = queryNoQuotes[7:]
			} else if len(queryNoQuotes) > 7 && (queryNoQuotes[0:8] == "https://" || queryNoQuotes[0:8] == "HTTPS://") {
				queryNoQuotes = queryNoQuotes[8:]
			}
			query = "\"" + query + "\""
			urlDetected = true
			isURL = "WHEN MATCH(url) AGAINST('\"" + queryNoQuotes + "\"' IN BOOLEAN MODE) THEN 25"
		}

		//Check if query contains a hyphenated word. Will wrap quotes around hyphenated words that aren't part of a string which is already wraped in quotes.
		if (strings.Contains(queryNoQuotes, "-") || strings.Contains(queryNoQuotes, "+")) && urlDetected == false {
			hyphenwords := strings.Split(query, " ")
			query = ""
			quotes := 0
			for i, word := range hyphenwords {
				if strings.Contains(word, "\"") {
					quotes++
				}
				if ((strings.Contains(word, "-") && word[0] != '-') || (strings.Contains(word, "+") && word[0] != '+')) && quotes%2 == 0 { //if hyphen or plus exists, not a flag, not wrapped in quotes already
					word = "\"" + word + "\""
				}
				if i > 0 {
					query += " "
				}
				query += word
			}
		}
		//fmt.Printf(">%s<\n", keywordQuery)
		queryWithQuotesAndFlags := "\"" + queryNoQuotesOrFlags + "\"" + flags

		//if query is just 1 or 2 letters, help make it work. 
		if utf8.RuneCountInString(queryOriginal) < 3 {
			queryfix := "" + query + "*"
			query = queryfix 
			queryWithQuotesAndFlags = queryfix
			keywordQuery = queryfix
		}
		if queryOriginal == "c++" || query == "C++" {
			query = "\"c++\" +programming"
			queryWithQuotesAndFlags = query
			keywordQuery = query
		}

		querytouse := query
		if flagssetbyuser > 0{
			querytouse = keywordQuery
		}

		//perform full text search FOR InnoDB STORAGE ENGINE or MyISAM
		var sqlQuery, id, url, title, description, body string
	
		if(exactMatch==false && urlDetected==false && strings.Index(query, " ") != -1 && flagssetbyuser != wordcount){
			sqlQuery = "SELECT id, url, title, description, body FROM windex WHERE MATCH(tags, body, description, title, url) AGAINST('" + querytouse + "' IN BOOLEAN MODE) AND enable = '1' " + additions + "ORDER BY CASE WHEN MATCH(tags) AGAINST('" + queryWithQuotesAndFlags + "' IN BOOLEAN MODE) THEN 30 " + isURL + " WHEN MATCH(title) AGAINST('" + queryWithQuotesAndFlags + "' IN BOOLEAN MODE) THEN 20 WHEN MATCH(body) AGAINST('" + queryWithQuotesAndFlags + "' IN BOOLEAN MODE) OR MATCH(description) AGAINST('" + queryWithQuotesAndFlags + "' IN BOOLEAN MODE) THEN 15 WHEN MATCH(title) AGAINST('" + keywordQuery + "' IN BOOLEAN MODE) THEN 14 WHEN MATCH(title) AGAINST('" + query + "' IN BOOLEAN MODE) THEN 13 END DESC, id DESC LIMIT " + lim + " OFFSET " + offset + ""
		}else{
			sqlQuery = "SELECT id, url, title, description, body FROM windex WHERE MATCH(tags, body, description, title, url) AGAINST('" + queryWithQuotesAndFlags + "' IN BOOLEAN MODE) AND enable = '1' " + additions + "ORDER BY CASE WHEN MATCH(tags) AGAINST('" + queryWithQuotesAndFlags + "' IN BOOLEAN MODE) THEN 30 " + isURL + " WHEN MATCH(title) AGAINST('" + queryWithQuotesAndFlags + "' IN BOOLEAN MODE) THEN 20 END DESC, id DESC LIMIT " + lim + " OFFSET " + offset + ""
		}
		
		rows, err := db.Query(sqlQuery)
		//fmt.Printf("\n%s\n",sqlQuery)
		if err != nil {
			res.Page = strconv.Itoa(0)
			res.Query = m["q"][0] //get original unsafe query
			if json {
				w.Header().Set("Content-Type", "application/json")
				t, _ := template.ParseFiles("coreassets/json/results.json.go")
				t.Execute(w, res)
			} else {
				t, _ := template.ParseFiles("coreassets/results.html.go")
				t.Execute(w, res)
			}
			//p := indexPage{}
			//t, _ := template.ParseFiles("coreassets/form.html.go")
			//t.Execute(w, p)
			return
		}

		if urlDetected == true {
			query = queryOriginal
		}

		count := 0

		for rows.Next() {
			count++
			//this will get set if position of longest word of query is found within body
			pos := -1

			err := rows.Scan(&id, &url, &title, &description, &body)
			if err != nil {
				error.Error = err.Error()
				t, _ := template.ParseFiles("coreassets/error.html.go")
				t.Execute(w, error)
			}

			//find query inside body of page
			if exactMatch == false && (flagssetbyuser == 0 || flagssetbyuser == wordcount){
				/*					//remove the '*' if contained anywhere in query
									if strings.Contains(queryNoQuotes,"*"){
										queryNoQuotes = strings.Replace(queryNoQuotes, "*", "", -1)
									}	*/

				if len(requiredword) > 0 { //search for position of required word if any, else search for position of whole query
					pos = strings.Index(strings.ToLower(body), strings.ToLower(requiredword))
				} else if pos == -1 {
					pos = strings.Index(strings.ToLower(body), strings.ToLower(queryNoQuotesOrFlags))
				}

				if pos == -1 { //prepare to find position of longest query word (or required word) within body
					//remove the '*' at the end of the longest word if present
					if strings.Contains(longestWord, "*") {
						longestWord = strings.Replace(longestWord, "*", "", -1)
					}
					//search within body for position of longest query word.
					pos = strings.Index(strings.ToLower(body), strings.ToLower(longestWord))
					//not found?, set position to a different word, make sure there's no wildcard on it
					if pos == -1 && wordcount > 1 {
						if longestwordelementnum > 0 {
							words[0] = strings.Replace(words[0], "*", "", -1)
							pos = strings.Index(strings.ToLower(body), strings.ToLower(words[0]))
						}
						if longestwordelementnum == 0 {
							words[1] = strings.Replace(words[1], "*", "", -1)
							pos = strings.Index(strings.ToLower(body), strings.ToLower(words[1]))
						}
					}
				}
			} else { //if exact match, find position of query within body
				pos = strings.Index(strings.ToLower(body), strings.ToLower(queryNoQuotesOrFlags))
			}

			//still not found?, set position to 0
			if pos == -1 {
				pos = 0
			}

			//Adjust position for runes within body
			pos = utf8.RuneCountInString(body[:pos])

			starttext := 0
			//ballpark := 0
			ballparktext := ""

			//figure out how much preceding text to use
			if pos < 32 {
				starttext = 0
			} else if pos > 25 {
				starttext = pos - 25
			} else if pos > 20 {
				starttext = pos - 15
			}

			//total length of the ballpark
			textlength := 180

			//populate the ballpark
			if pos >= 0 {
				ballparktext = substr(body, starttext, starttext+textlength)
			} //else{ ballpark = 0}//looks unused

			//find position of nearest Period
			//foundPeriod := true
			posPeriod := strings.Index(ballparktext, ". ") + starttext + 1

			//find position of nearest Space
			//foundSpace := true
			posSpace := strings.Index(ballparktext, " ") + starttext

			//if longest word in query is after a period+space within ballpark, reset starttext to that point
			if (pos - starttext) > posPeriod {
				starttext = posPeriod
				//populate the bodymatch
				if (pos - starttext) >= 0 {
					body = substr(body, starttext, starttext+textlength)
				} else {
					body = ""
				}
			} else if pos > posSpace { //else if longest word in query is after a space within ballpark, reset starttext to that point
				//else if(pos-starttext) > posSpace//else if longest word in query is after a space within ballpark, reset starttext to that point
				starttext = posSpace
				//populate the bodymatch
				if (pos - starttext) >= 0 {
					body = substr(body, starttext, starttext+textlength)
				} else {
					body = ""
				}
			} else //else just set the bodymatch to the ballparktext
			{
				//populate the bodymatch
				if (pos - starttext) >= 0 {
					body = ballparktext
				} else {
					body = ""
				}
			}

			tRes.Id = id
			tRes.Url = url
			tRes.Title = html.UnescapeString(title)
			tRes.Description = html.UnescapeString(description)
			tRes.Body = html.UnescapeString(body)
			if json == true {
				tRes.Title = JSONRealEscapeString(tRes.Title)
				tRes.Description = JSONRealEscapeString(tRes.Description)
				tRes.Body = JSONRealEscapeString(tRes.Body)
			}
			res.DBResults = append(res.DBResults, tRes)
		}
		defer rows.Close()
		rows.Close()
		//================================================================================================================================
		//no results found (count==0), so do a wildcard search (repeat the above process) - this section will probably be removed, no longer useful
		addWildcard := false
		/*if count == 0 && offset == "0" && urlDetected == false && exactMatch == false {
			addWildcard = true
			query = strings.Replace(query, "\"", "", -1) //remove some things innodb gets fussy over
			query = strings.Replace(query, "*", "", -1)
			query = strings.Replace(query, "'", "", -1)
			queryNoQuotes = strings.Replace(queryNoQuotes, "\"", "", -1)
			queryNoQuotes = strings.Replace(queryNoQuotes, "*", "", -1)
			queryNoQuotes = strings.Replace(queryNoQuotes, "'", "", -1)
			query = query + "*"

			sqlQuery = "SELECT id, url, title, description, body FROM windex WHERE Match(tags, body, description, title, url) Against('" + query + "' IN BOOLEAN MODE) AND enable = '1' " + additions + "ORDER BY CASE WHEN MATCH(tags) AGAINST('" + queryWithQuotesAndFlags + "' IN BOOLEAN MODE) THEN 30 END DESC, id DESC LIMIT " + lim + " OFFSET " + offset + ""
			rows2, err := db.Query(sqlQuery)
			if err != nil {
				res.Page = strconv.Itoa(0)
				res.Query = m["q"][0] //get original unsafe query
				if json {
					w.Header().Set("Content-Type", "application/json")
					t, _ := template.ParseFiles("coreassets/json/results.json.go")
					t.Execute(w, res)
				} else {
					t, _ := template.ParseFiles("coreassets/results.html.go")
					t.Execute(w, res)
				}
				//p := indexPage{}
				//t, _ := template.ParseFiles("coreassets/form.html.go")
				//t.Execute(w, p)
				return
			}

			for rows2.Next() {
				count++
				//this will get set if position of longest word of query is found within body
				pos := -1

				err := rows2.Scan(&id, &url, &title, &description, &body)
				if err != nil {
					error.Error = err.Error()
					t, _ := template.ParseFiles("coreassets/error.html.go")
					t.Execute(w, error)
				}

				//find query inside body of page
				if exactMatch == false && (flagssetbyuser == 0 || flagssetbyuser == wordcount){
					//remove the '*' if contained anywhere in query
					//if strings.Contains(queryNoQuotes,"*"){
					//	queryNoQuotes = strings.Replace(queryNoQuotes, "*", "", -1)
					//}
					if len(requiredword) > 0 { //search for position of required word if any, else search for position of whole query
						pos = strings.Index(strings.ToLower(body), strings.ToLower(requiredword))
					} else if pos == -1 {
						pos = strings.Index(strings.ToLower(body), strings.ToLower(queryNoQuotesOrFlags))
					}
					if pos == -1 { //Not found? prepare to find position of longest query word within body
						//remove the '*' at the end of the longest word if present
						if strings.Contains(longestWord, "*") {
							longestWord = strings.Replace(longestWord, "*", "", -1)
						}
						//search within body for position of longest query word.
						pos = strings.Index(strings.ToLower(body), strings.ToLower(longestWord))
						//not found?, set position to a different word, make sure there's no wildcard on it
						if pos == -1 && wordcount > 1 {
							if longestwordelementnum > 0 {
								words[0] = strings.Replace(words[0], "*", "", -1)
								pos = strings.Index(strings.ToLower(body), strings.ToLower(words[0]))
							}
							if longestwordelementnum == 0 {
								words[1] = strings.Replace(words[1], "*", "", -1)
								pos = strings.Index(strings.ToLower(body), strings.ToLower(words[1]))
							}
						}
					}

				} else { //if exact match, find position of query within body
					pos = strings.Index(strings.ToLower(body), strings.ToLower(queryNoQuotesOrFlags))
				}
				//still not found?, set position to 0
				if pos == -1 {
					pos = 0
				}

				//Adjust position for runes within body
				pos = utf8.RuneCountInString(body[:pos])

				starttext := 0
				//ballpark := 0
				ballparktext := ""

				//figure out how much preceding text to use
				if pos < 32 {
					starttext = 0
				} else if pos > 25 {
					starttext = pos - 25
				} else if pos > 20 {
					starttext = pos - 15
				}

				//total length of the ballpark
				textlength := 180

				//populate the ballpark
				if pos >= 0 {
					ballparktext = substr(body, starttext, starttext+textlength)
				} //else{ ballpark = 0}//looks unused

				//find position of nearest Period
				//foundPeriod := true
				posPeriod := strings.Index(ballparktext, ". ") + starttext + 1

				//find position of nearest Space
				//foundSpace := true
				posSpace := strings.Index(ballparktext, " ") + starttext

				//if longest word in query is after a period+space within ballpark, reset starttext to that point
				if (pos - starttext) > posPeriod {
					starttext = posPeriod
					//populate the bodymatch
					if (pos - starttext) >= 0 {
						body = substr(body, starttext, starttext+textlength)
					} else {
						body = ""
					}
				} else if pos > posSpace { //else if longest word in query is after a space within ballpark, reset starttext to that point
					//else if(pos-starttext) > posSpace//else if longest word in query is after a space within ballpark, reset starttext to that point
					starttext = posSpace
					//populate the bodymatch
					if (pos - starttext) >= 0 {
						body = substr(body, starttext, starttext+textlength)
					} else {
						body = ""
					}
				} else //else just set the bodymatch to the ballparktext
				{
					//populate the bodymatch
					if (pos - starttext) >= 0 {
						body = ballparktext
					} else {
						body = ""
					}
				}

				tRes.Id = id
				tRes.Url = url
				tRes.Title = html.UnescapeString(title)
				tRes.Description = html.UnescapeString(description)
				tRes.Body = html.UnescapeString(body)
				if json == true {
					tRes.Title = JSONRealEscapeString(tRes.Title)
					tRes.Description = JSONRealEscapeString(tRes.Description)
					tRes.Body = JSONRealEscapeString(tRes.Body)
				}
				res.DBResults = append(res.DBResults, tRes)
			}
			defer rows2.Close()
			rows2.Close()
		}
		//=======================================================================================================================
		//http://go-database-sql.org/retrieving.html
*/
		//Close DB
		db.Close()

		//If results = lim, allow the find more link
		if count >= limInt && addWildcard == false{
			res.FindMore = true
		} else {
			res.FindMore = false
		}

		if(pageInt == 0){
			pageInt+=2
		}else{
			pageInt++;
		}
		res.Page = strconv.Itoa(pageInt)
		res.Query = m["q"][0] //get original unsafe query

		if json {
			w.Header().Set("Content-Type", "application/json")
			t, _ := template.ParseFiles("coreassets/json/results.json.go")
			t.Execute(w, res)
		} else {
			t, _ := template.ParseFiles("coreassets/results.html.go")
			t.Execute(w, res)
		}
	}
}

func settings(w http.ResponseWriter, r *http.Request) {
	//setup for error report
	error := errorReport{}

	//check if worksafe (adult content) cookie enabled.
	filterHTTPS := false
	worksafe := true
	worksafewasoff := false
	worksafeHTTPSCookie, err := r.Cookie("ws")
	if err != nil {
		worksafe = true
		filterHTTPS = false
	} else if worksafeHTTPSCookie.Value == "0" {
		worksafe = false
		filterHTTPS = false
		worksafewasoff = true
	} else if worksafeHTTPSCookie.Value == "1" {
		worksafe = true
		filterHTTPS = false
	} else if worksafeHTTPSCookie.Value == "2" {
		worksafe = false
		filterHTTPS = true
		worksafewasoff = true
	} else if worksafeHTTPSCookie.Value == "3" {
		worksafe = true
		filterHTTPS = true
	}

	//check if and what is the user posting
	switch r.Method {
	case "POST":
		if err := r.ParseForm(); err != nil {
			error.Error = err.Error()
			t, _ := template.ParseFiles("coreassets/error.html.go")
			t.Execute(w, error)
		}
		worksafebox := r.Form.Get("worksafe")
		agreecheck := r.Form.Get("agree")
		agreesubmit := r.Form.Get("agreesubmit")
		httpsbox := r.Form.Get("filterHTTPS")

		//if user agrees to terms to disable adult content, set cookie and return to index
		if agreecheck == "on" {
			worksafe = false
			//expiration := time.Now().Add(365 * 24 * time.Hour)
			if filterHTTPS == false {
				cookie := http.Cookie{Name: "ws", Value: "0", Path: "/"}
				http.SetCookie(w, &cookie)
			} else {
				cookie := http.Cookie{Name: "ws", Value: "2", Path: "/"}
				http.SetCookie(w, &cookie)
			}
			p := indexPage{}
			t, _ := template.ParseFiles("coreassets/settings/gohome.html")
			t.Execute(w, p)
			//else if worksafebox is checked, return to index with worksafe on
		} else if worksafebox == "on" || agreesubmit == "on" {
			//expiration := time.Now().Add(365 * 24 * time.Hour)
			if httpsbox != "on" {
				cookie := http.Cookie{Name: "ws", Value: "1", Path: "/"}
				http.SetCookie(w, &cookie)
			} else {
				cookie := http.Cookie{Name: "ws", Value: "3", Path: "/"}
				http.SetCookie(w, &cookie)
			}
			p := indexPage{}
			t, _ := template.ParseFiles("coreassets/settings/gohome.html")
			t.Execute(w, p)
			//else if worksafebox unchecked and no cookie, go to content agreement section
		} else if worksafebox != "on" && worksafewasoff == false && agreesubmit != "on" {
			p := indexPage{}
			if httpsbox == "on" {
				cookie := http.Cookie{Name: "ws", Value: "3", Path: "/"}
				http.SetCookie(w, &cookie)
			} else {
				cookie := http.Cookie{Name: "ws", Value: "1", Path: "/"}
				http.SetCookie(w, &cookie)
			}
			t, _ := template.ParseFiles("coreassets/settings/agree.html.go")
			t.Execute(w, p)
			//else if worksafebox unchecked and cookie alredy agreed, go back to index
		} else if worksafebox != "on" && worksafewasoff == true {
			if httpsbox == "on" {
				cookie := http.Cookie{Name: "ws", Value: "2", Path: "/"}
				http.SetCookie(w, &cookie)
			} else {
				cookie := http.Cookie{Name: "ws", Value: "0", Path: "/"}
				http.SetCookie(w, &cookie)
			}
			p := indexPage{}
			t, _ := template.ParseFiles("coreassets/settings/gohome.html")
			t.Execute(w, p)
		}
	default:
		//load the settings page if no post value
		settingspage := settingsPage{}
		settingspage.Worksafe = worksafe
		settingspage.FilterHTTPS = filterHTTPS
		t, _ := template.ParseFiles("coreassets/settings/settings.html.go")
		t.Execute(w, settingspage)
	}
}

func surprise(w http.ResponseWriter, r *http.Request) {
	surprise := surpriseURL{}

	//check if worksafe+HTTPS cookie enabled.
	filterHTTPS := false
	worksafeHTTPSCookie, err := r.Cookie("ws")
	if err != nil {
		filterHTTPS = false
	} else if worksafeHTTPSCookie.Value == "2" {
		filterHTTPS = true
	} else if worksafeHTTPSCookie.Value == "3" {
		filterHTTPS = true
	}

	//setup for error report
	error := errorReport{}

	//init the db and set charset
	db, err := sql.Open("mysql", "guest:qwer@/wiby?charset=utf8mb4")
	if err != nil {
		error.Error = err.Error()
		t, _ := template.ParseFiles("coreassets/error.html.go")
		t.Execute(w, error)
	}
	defer db.Close()
	// Open doesn't open a connection. Validate DSN data:
	err = db.Ping()
	if err != nil {
		error.Error = err.Error()
		t, _ := template.ParseFiles("coreassets/error.html.go")
		t.Execute(w, error)
	}

	//grab a random page
	var sqlQuery string
	if filterHTTPS == false {
		sqlQuery = "select url from windex where worksafe = 1 and surprise = 1 order by rand() limit 1"
	} else {
		sqlQuery = "select url from windex where worksafe = 1 and surprise = 1 and http = 1 order by rand() limit 1"
	}
	rows, err := db.Query(sqlQuery)

	if err != nil {
		error.Error = err.Error()
		t, _ := template.ParseFiles("coreassets/error.html.go")
		t.Execute(w, error)
	}
	var url string
	for rows.Next() {
		err := rows.Scan(&url)
		if err != nil {
			error.Error = err.Error()
			t, _ := template.ParseFiles("coreassets/error.html.go")
			t.Execute(w, error)
		}
		surprise.Url = url
	}
	defer rows.Close()
	rows.Close()
	db.Close()
	t, _ := template.ParseFiles("coreassets/surprise.html.go")
	t.Execute(w, surprise)
}

func MysqlRealEscapeString(value string) string {
	replace := map[string]string{"\\": "\\\\", "'": `\'`, "\\0": "\\\\0", "\n": "\\n", "\r": "\\r", `"`: `\"`, "\x1a": "\\Z"}

	for b, a := range replace {
		value = strings.Replace(value, b, a, -1)
	}

	return value
}
func JSONRealEscapeString(value string) string {
	replace := map[string]string{"\\": "\\\\", "\t": "\\t", "\b": "\\b", "\n": "\\n", "\r": "\\r", "\f": "\\f" /*, `"`:`\"`*/}

	for b, a := range replace {
		value = strings.Replace(value, b, a, -1)
	}

	//remove control characters
	buf := []rune(value)
	for i, v := range buf {
		if v < 32 || v == 127 {
			buf[i]=32
		}
	}

	return string(buf)
}
func substr(s string, start int, end int) string {
	start_str_idx := 0
	i := 0
	for j := range s {
		if i == start {
			start_str_idx = j
		}
		if i == end {
			return s[start_str_idx:j]
		}
		i++
	}
	return s[start_str_idx:]
}

func searchredirect(w http.ResponseWriter, r *http.Request, query string) {
	//separate actual query from search redirect
	actualquery := ""
	redirect := ""
	lenquery := len(query)
	if strings.Index(query," ") > -1{
		location := strings.Index(query, " !")
		if location == -1 {
			location = strings.Index(query, " &")
		}
		if location > -1 && strings.Index(query[location+1:lenquery], " ") == -1 { //redirect is at end of query
			redirect = query[location+2 : lenquery]
			actualquery = query[:location]
		} else if (strings.Index(query, "!") == 0 || strings.Index(query, "&") == 0){ //redirect is at start of query
			redirect = query[1:strings.Index(query, " ")]
			actualquery = query[strings.Index(query, " ")+1:]
			//fmt.Printf("\nRedirect: %s\nquery: %s\n",redirect,actualquery)
		}
		redirect = strings.ToLower(redirect)
	}else if (query[0] == '!' || query[0] == '&') && lenquery > 1{
			redirect = query[1:]
	}
	if redirect != "" {
		//determine which search engine to redirect
		if redirect == "g" { //if google text search
			http.Redirect(w, r, "http://google.com/search?q="+actualquery, http.StatusSeeOther)
		} else if redirect == "b" { //if bing text search
			http.Redirect(w, r, "http://bing.com/search?q="+actualquery, http.StatusSeeOther)
		} else if redirect == "gi" { //if google image search
			http.Redirect(w, r, "http://www.google.com/search?tbm=isch&q="+actualquery, http.StatusSeeOther)
		} else if redirect == "bi" { //if bing image search
			http.Redirect(w, r, "http://www.bing.com/images/search?q="+actualquery, http.StatusSeeOther)
		} else if redirect == "gv" { //if google video search
			http.Redirect(w, r, "http://www.google.com/search?tbm=vid&q="+actualquery, http.StatusSeeOther)
		} else if redirect == "bv" { //if bing video search
			http.Redirect(w, r, "http://www.bing.com/videos/search?q="+actualquery, http.StatusSeeOther)
		} else if redirect == "gm" { //if google maps search
			http.Redirect(w, r, "http://www.google.com/maps/search/"+actualquery, http.StatusSeeOther)
		} else if redirect == "bm" { //if bing maps search
			http.Redirect(w, r, "http://www.bing.com/maps?q="+actualquery, http.StatusSeeOther)
		}/* else {
			http.Redirect(w, r, "/?q="+actualquery, http.StatusSeeOther)
		}*/
	}
}
