<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');

ob_start();
phpinfo();
$phpinfo = ob_get_contents();
ob_end_clean();

// Break the phpinfo() apart into CSS and BODY sections so it can be embedded in an otherwise-styled page
preg_match ('%<style type="text/css">(.*?)</style>.*?(<body>(.*?)</body>)%s', $phpinfo, $phpinfo);
// $phpinfo[0] is the whole phpinfo() output.
// $phpinfo[1] is the styling portion without <style></style> tags
// $phpinfo[2] is the body portion WITH <body></body> tags
// $phpinfo[3] is the body portion WITHOUT <body></body> tags

// We ignore the phpinfo CSS and replace it with our own
$page_specific_css = '
  #ofs_content pre {
    margin: 0; font-family: monospace;
    }
  #ofs_content a:link {
    color: #009;
    text-decoration: none;
    background-color: #fff;
    }
  #ofs_content a:hover {
    text-decoration: underline;
    }
  #ofs_content table {
    border-collapse: collapse;
    border: 0;
    width: 934px;
    box-shadow: 1px 2px 3px #ccc;
    }
  #ofs_content .center {
    text-align: center;
    }
  #ofs_content .center table {
    margin: 1em auto;
    text-align: left;
    }
  #ofs_content .center th {
    text-align: center !important;
    }
  #ofs_content td, th {
    border: 1px solid #666;
    font-size: 75%;
    vertical-align: baseline;
    padding: 4px 5px;
    }
  #ofs_content h1 {
    font-size: 150%;
    }
  #ofs_content h2 {
    font-size: 125%;
    }
  #ofs_content .p {
    text-align: left;
    }
  #ofs_content .e {
    background-color: #ccf;
    width: 300px;
    font-weight: bold;
    }
  #ofs_content .h {
    background-color: #99c;
    font-weight: bold;
    }
  #ofs_content .v {
    background-color: #ddd;
    max-width: 300px;
    overflow-x: auto;
    word-wrap: break-word;
    }
  #ofs_content .v i {
    color: #999;
    }
  #ofs_content img {
    float: right;
    border: 0;
    }
  #ofs_content hr {
    width: 934px;
    background-color: #ccc;
    border: 0;
    height: 1px;
    }';

$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">PHP Information on this Server</span>';
$page_title = 'Site Admin Panel - PHP Information';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$phpinfo[3].'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
