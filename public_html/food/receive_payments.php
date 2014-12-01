<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

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
        case 'member_id':
          $order_by = 'member_id';
          break;
        case 'site_id':
          $order_by = 'site_short, last_name';
          break;
        default:
          $order_by = 'last_name, member_id';
          break;
      }
  }
else
  {
    $order_by = 'site_short, last_name';
  }

// This next line allow us to include the ajax routine and call it as a function
// without it returning anything on stdout. C.f. the ajax function.
$call_ajax_as_function = true;
$page_data = '';
include_once ('ajax/receive_payments_detail.php');

$query = '
  SELECT
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.site_id,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.preferred_name,
    '.NEW_TABLE_SITES.'.hub_id,
    '.NEW_TABLE_SITES.'.site_short,
    '.TABLE_HUBS.'.hub_short
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  LEFT JOIN '.NEW_TABLE_SITES.' USING(site_id)
  LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id
  ORDER BY
    '.$order_by;
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 672323 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num_orders = mysql_numrows($result);
while ( $row = mysql_fetch_array($result) )
  {
    $basket_id = $row['basket_id'];
    $member_id = $row['member_id'];
    $site_id = $row['site_id'];
    $site_short = $row['site_short'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = $row['business_name'];
    $preferred_name = $row['preferred_name'];
    $hub_id = $row['hub_id'];

    $receive_payments_detail_line = receive_payments_detail(array(
      'request' => 'basket_total_and_payments',
      'basket_id' => $basket_id));

    $page_data .= '
      <div id="member_id'.$member_id.'" class="basket_section">
        <span class="member_id">'.$member_id.'</span>
        <span class="site_short">['.$site_short.']</span>
        <span class="member_name"><a href="'.PATH.'show_report.php?type=customer_invoice&delivery_id='.$delivery_id.'&member_id='.$member_id.'" target="_blank">'.$preferred_name.'</a></span>
        <span class="controls"><input type="button" value="Receive Payment" onclick="show_receive_payment_form('.$member_id.','.$basket_id.',\''.urlencode($preferred_name).'\')"></span>
        <div id="basket_id'.$basket_id.'" class="ledger_info">'.
          $receive_payments_detail_line.'
        </div>
      </div>';
  }

$page_specific_javascript = '
  <script src="'.PATH.'receive_payments.js" type="text/javascript"></script>';

$page_specific_css = '
  <link href="'.PATH.'receive_payments.css" rel="stylesheet" type="text/css">';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Receive Payments</span>';
$page_title = 'Delivery Cycle Functions: Receive Payments';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  <div style="float:right;width:300px;height:26px;margin-bottom:10px;">'.delivery_selector($delivery_id).'</div>
  '.$page_data.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

