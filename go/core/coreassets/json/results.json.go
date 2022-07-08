[
{{range $i, $e:=.DBResults}}{{if $i}},
{{end}}  {
    "URL": "{{.Url}}",
    "Title": "{{.Title}}",
    "Snippet": "{{.Body}}",
    "Description": "{{.Description}}"
  }{{end}}{{if .FindMore }},
  {
    "NextOffset": "{{.Totalcount}}"
  }
{{end}}
]
