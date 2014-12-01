<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,producer_admin');


$content_applications .= '
<small>NOTE: This page is scrolled horizontally.  The horizontal scroll-bar is at the bottom of the page &darr;</small>
<table style="text-align: left;" border="1">';

$sql = '
  SELECT
    *
  FROM
    '.TABLE_PRODUCER_REG.'
  ORDER BY
    member_id DESC';
$rs = @mysql_query($sql, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$first = 1;
while ($row = mysql_fetch_array($rs))
  {
    if ($first)
      {
        $keys = array_keys($row);
        $first = 0;
      }
    $content_applications .= "<tr>\n";
    for ($i = 1; $i < count($keys); $i+=2)
      {
        $content_applications .= "<th>$keys[$i]</th> ";
      }
    $content_applications .= "</tr>\n";
    $content_applications .= "<tr>\n";
    for ($i = 1; $i < count($keys); $i+=2)
      {
        $content_applications .= "<td style='vertical-align: top;'>".$row[$keys[$i]]."</td>\n";
      }
    $content_applications .= "</tr>\n";
  }

$content_applications .= '
</table>';

$page_specific_css .= '
<style type="text/css">
small {
  font-size:0.9em;
  color:#006;
  font-weight:bold;
  }
</style>';

$page_title_html = '<span class="title">Producer Membership Information</span>';
$page_subtitle_html = '<span class="subtitle">Producer Applications</span>';
$page_title = 'Producer Membership Information: Producer Applications';
$page_tab = 'producer_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_applications.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
 