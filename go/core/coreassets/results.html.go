<!DOCTYPE html>  
<html>  
  <head>
    <meta charset="UTF-8"/>
    <title>{{.Query}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/styles.css" type="text/css"> 
    <link rel="search" type="application/opensearchdescription+xml" title="Your title" href="/opensearch.xml">	
  </head>  
  <body>
    <form method="get">    
      <div style="float: left">   
        <a class="title" href="../">name</a>&nbsp;&nbsp;
        <input type="text" size="35" name="q" id="q" value="{{.Query}}" role="form" aria-label="Main search form"/>    
        <input type="submit" value="Search"/>
      </div>    
      <div  style="float: right"><a class="tiny" href="/settings/">Settings</a></div><br><br>
    </form>
    <p class="pin"><br></p>

    {{range .DBResults}}
    <blockquote> 
      <a class="tlink" href="{{.Url}}">{{ printf "%.150s" .Title}}</a><br><p class="url">{{.Url}}</p><p>{{printf "%.180s" .Body}}<br>{{printf "%.180s" .Description}}</p>
    </blockquote>
    {{end}}
    
    {{if .FindMore }}
        <p class="pin"><blockquote></p><br><a class="more" href="/?q={{.Query}}&p={{.Page}}">Find more...</a></blockquote>
    {{else}}
        <blockquote><p class="pin"> <br>That's everything I could find.<br>Help make me smarter by <a class="pin1" href="/submit">submitting a page</a>.</p></blockquote>
    {{end}}
  </body> 
</html>
