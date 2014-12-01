<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');

$detail_type = $_GET['detail_type'];

include ('func/order_summary_function.php');
if (! preg_match ('/.*compile_producer_invoices.*/' , $_SERVER['HTTP_REFERER']))
  {
    $web_display = true;
  };
////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                       OBTAIN CURRENT DELIVERY ID                         ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////


if ($_GET['delivery_id'])
  { // If we were passed a delivery_id, use  it
  $delivery_id = $_GET['delivery_id'];
  }
else
  { // Otherwise, use the current delivery_id
    $sqlp = '
      SELECT
        delivery_id,
        delivery_date,
        producer_markdown,
        wholesale_markup,
        retail_markup
      FROM
        '.TABLE_ORDER_CYCLES.'
      WHERE
        delivery_id = '.ActiveCycle::delivery_id();
    $resultp = @mysql_query($sqlp, $connection) or die(debug_print ("ERROR: 675932 ", array ($sqlp,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($resultp))
      {
        $delivery_id = $row['delivery_id'];
        $delivery_date = $row['delivery_date'];
        $producer_markdown = $row['producer_markdown'] / 100;
        $retail_markup = $row['retail_markup'] / 100;
        $wholesale_markup = $row['wholesale_markup'] / 100;
      }
  }

$producer_id = $_SESSION['producer_id_you'];
$display_page = generate_producer_summary ($producer_id, $delivery_id, $detail_type, '');

include("func/show_businessname.php");

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Order Summary</span>';
$page_title = $business_name.': Order Summary';
$page_tab = 'producer_panel';

if ($include_header) include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$display_page.'
  <!-- CONTENT ENDS HERE -->';
if ($include_footer) include("template_footer.php");
