<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

// EXPLANATION/PURPOSE OF THIS FILE
// This is the basic entry-page for making payments to producers

include ('func.delivery_selector.php');

// Set up the default delivery cycle
$delivery_id = ActiveCycle::delivery_id();
// ... but if a targeted delivery is requested then use that.
if (isset ($_GET['delivery_id']))
  $delivery_id = $_GET['delivery_id'];

// Set the sort order
if (isset ($_GET['order']))
  {
    switch ($_GET['order'])
      {
        case 'producer_id':
          $order_by = 'producer_id';
          break;
        case 'payee':
          $order_by = 'payee';
          break;
        default:
          $order_by = 'business_name';
          break;
      }
  }
else
  {
    $order_by = 'business_name';
  }

// This next line allow us to include the ajax routine and call it as a function
// without it returning anything on stdout. C.f. the ajax function.
$call_ajax_as_function = true;
$page_data = '';
include_once ('ajax/make_payments_detail.php');

$query = '
  SELECT
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_PRODUCER.'.member_id AS producer_member_id,
    '.TABLE_PRODUCER.'.payee,
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_ORDER_CYCLES.'.delivery_date
  FROM
    '.NEW_TABLE_BASKET_ITEMS.'
  LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  LEFT JOIN '.TABLE_PRODUCER.' USING(producer_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY
    '.NEW_TABLE_PRODUCTS.'.producer_id
  ORDER BY
  '.$order_by;
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 579329 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num_orders = mysql_numrows($result);
while ( $row = mysql_fetch_array($result) )
  {
    $business_name = $row['business_name'];
    $producer_member_id = $row['producer_member_id'];
    $payee = $row['payee'];
    $producer_id = $row['producer_id'];
    $make_payments_detail_line = make_payments_detail(array(
      'request' => 'producer_total_and_payments',
      'delivery_id' => $delivery_id,
      'producer_id' => $producer_id));
    $page_data .= '
      <div id="producer_id'.$producer_id.'" class="producer_section">
        <span class="producer_id">'.$producer_id.'</span>
        <span class="payee">'.($payee != $business_name ? '['.$payee.']' : '').'</span>
        <span class="business_name"><a href="show_report.php?type=producer_invoice&delivery_id='.$delivery_id.'&producer_id='.$producer_id.'" target="_blank">'.$business_name.'</a></span>
        <span class="controls"><input type="button" value="Make Payment" onclick="show_make_payment_form('.$producer_id.','.$delivery_id.', \''.urlencode ($business_name).'\')"></span>
        <div id="detail_producer_id'.$producer_id.'" class="ledger_info">'.
          $make_payments_detail_line.'
        </div>
      </div>';
  }

$page_specific_javascript = '
  <script src="'.PATH.'make_payments.js" type="text/javascript"></script>';

$page_specific_css = '
  <link href="'.PATH.'make_payments.css" rel="stylesheet" type="text/css">';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Make Payments</span>';
$page_title = 'Delivery Cycle Functions: Make Payments';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  <div style="float:right;width:300px;height:26px;margin-bottom:10px;">'.delivery_selector($delivery_id).'</div>
  '.$page_data.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

