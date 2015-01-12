#Paged Textarea Field for Symphony CMS 2.3+
This field extension allows long articles to be split into pages
##Installation
The folder should be named "paged_textarea_field". Install in the usual way for a Symphony extension.

##Usage
To control the page which appears in the XML output using a URL parameter, place the parameter name in the "Page to output" field. The parameter name should begin with a `$` and be enclosed in braces, e.g. `{$url-page}`. If the field is left blank then all pages will be output.