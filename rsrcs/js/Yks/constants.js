var XHTML = "http://www.w3.org/1999/xhtml",
    amp = '&';

var CRLF = "\r\n";

if(!document.location)
    document.location = window.location; 

var url = Urls.parse(document.location.href); 
  Urls.base = url; //cache
  http_lnk.security_flag = ks_flag;


var site_url    = url.site_url, 
    site_domain = url.domain,
    site_href   = url.args,
    xsl_path    = site_url+'/'+cache_path+'/xsl/'+xsl_engine+"_client.xsl",
    blank_frame = "/?/Yks/Wysiwyg/blank",
    site_base   = site_code.capitalize(),
    error_page  = "/?/"+site_base+"/error",
    login_page  = "/?/"+site_base+"/error//403",
    error_box   = 'error_box';

