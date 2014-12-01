<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,producer_admin');

// Figure out what to display
$per_page = PER_PAGE;
// Check that the page is integer
if (isset ($_GET['page']) && ($_GET['page'] == floor ($_GET['page'])))
  {
    $page = $_GET['page'];
    $query_limit = ($page - 1).','.PER_PAGE;
  }
// Otherwise use the first page
else
  {
    $page = 1;
    $query_limit = '0,'.PER_PAGE;
  }

// Get the producer list
$query_producer = '
  SELECT
    SQL_CALC_FOUND_ROWS
    producer_id,
    list_order,
    pending,
    business_name,
    unlisted_producer
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    1
  ORDER BY
    list_order
  LIMIT
    '.$query_limit;
$result_producer = mysql_query($query_producer, $connection) or die(debug_print ("ERROR: 576219 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// How many total producers in this query (not counting LIMIT)?
$query_found_rows = '
  SELECT
    FOUND_ROWS() AS found_rows';
$result_found_rows = @mysql_query($query_found_rows, $connection) or die(debug_print ("ERROR: 756890 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$number_of_pages = floor (mysql_fetch_array($result_found_rows) - 1 / PER_PAGE);
// Now process the list of producers
while ($row_producer = mysql_fetch_object($result_producer))
  {
    $producer_array[$row_producer->list_order]['producer_id'] = $row_producer->producer_id;
    $producer_array[$row_producer->list_order]['pending'] = $row_producer->pending;
    $producer_array[$row_producer->list_order]['business_name'] = $row_producer->business_name;
    $producer_array[$row_producer->list_order]['unlisted_producer'] = $row_producer->unlisted_producer;
  }







// Get the delivery_code list
$query_producer = '
  SELECT
    SQL_CALC_FOUND_ROWS
    site_id,
    list_order,
    delcode,
    delivery_type,
    route_id
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    1
  ORDER BY
    list_order
  LIMIT
    '.$query_limit;
$result_producer = mysql_query($query_producer, $connection) or die(debug_print ("ERROR: 576219 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// How many total producers in this query (not counting LIMIT)?
$query_found_rows = '
  SELECT
    FOUND_ROWS() AS found_rows';
$result_found_rows = @mysql_query($query_found_rows, $connection) or die(debug_print ("ERROR: 756890 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$number_of_pages = floor (mysql_fetch_array($result_found_rows) - 1 / PER_PAGE);
// Now process the list of producers
while ($row_producer = mysql_fetch_object($result_producer))
  {
    $producer_array[$row_producer->list_order]['producer_id'] = $row_producer->producer_id;
    $producer_array[$row_producer->list_order]['pending'] = $row_producer->pending;
    $producer_array[$row_producer->list_order]['business_name'] = $row_producer->business_name;
    $producer_array[$row_producer->list_order]['unlisted_producer'] = $row_producer->unlisted_producer;
  }

































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
 