<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');


$producer_id = $_GET['producer_id'];
include("func/show_businessname.php");

$content_body = '';

// Figure out where we came from and save it so we can go back
$referrer = $_SERVER['HTTP_REFERER'];

// If a product_version is specified, then only show orders for that version
if (isset ($_GET['product_version']))
  {
    $where_version = '
    AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.mysql_real_escape_string ($_GET['product_version']).'"';
  }

$query = '
  SELECT
    '.NEW_TABLE_BASKET_ITEMS.'.product_id,
    '.NEW_TABLE_BASKET_ITEMS.'.product_version,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_MESSAGES.'.message AS notes_to_producer,
    '.NEW_TABLE_PRODUCTS.'.product_description,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.home_phone,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_PRODUCER.'.producer_id
  FROM
    '.NEW_TABLE_BASKET_ITEMS.'
  LEFT JOIN
    '.NEW_TABLE_BASKETS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.NEW_TABLE_BASKETS.'.member_id
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.NEW_TABLE_BASKETS.'.delivery_id
  LEFT JOIN
    '.NEW_TABLE_PRODUCTS.' ON
      ('.NEW_TABLE_PRODUCTS.'.product_id = '.NEW_TABLE_BASKET_ITEMS.'.product_id
      AND '.NEW_TABLE_PRODUCTS.'.product_version = '.NEW_TABLE_BASKET_ITEMS.'.product_version)
  LEFT JOIN
    '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id='.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN
    '.NEW_TABLE_MESSAGE_TYPES.' ON ('.NEW_TABLE_MESSAGE_TYPES.'.description = "customer notes to producer")
  LEFT JOIN
    '.NEW_TABLE_MESSAGES.' ON
      ('.NEW_TABLE_MESSAGES.'.message_type_id = '.NEW_TABLE_MESSAGE_TYPES.'.message_type_id
      AND referenced_key1 = '.NEW_TABLE_BASKET_ITEMS.'.bpid)
  WHERE
    '.NEW_TABLE_BASKET_ITEMS.'.product_id = "'.mysql_real_escape_string ($_GET['product_id']).'"
    AND '.TABLE_PRODUCER.'.producer_id = "'.mysql_real_escape_string ($_GET['producer_id']).'"'.
    $where_version.'
  ORDER BY
    '.NEW_TABLE_BASKETS.'.delivery_id DESC';
$sql = @mysql_query($query,$connection) or die(debug_print ("ERROR: 654219 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysql_fetch_array($sql))
  {
    $delivery_id = $row['delivery_id'];
    $member_id = $row['member_id'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $email_address = $row['email_address'];
    $home_phone = $row['home_phone'];
    $quantity = $row['quantity'];
    $notes_to_producer = $row['notes_to_producer'];
    $product_name = $row['product_name'];
    $product_version = $row['product_version'];
    $detailed_notes = $row['detailed_notes'];
    $delivery_date = $row['delivery_date'];

    if ($delivery_date && $delivery_date != $delivery_date_prior)
      {
        $new_section = ' class="new_section"';
      }
    else
      {
        $new_section = '';
      }

    $content_body .= '
      <tr class="proddata">
      <td '.$new_section.'>'.$product_version.'</td>
      <td '.$new_section.'>'.$delivery_date.'</td>
      <td '.$new_section.'>'.$first_name.' '.$last_name.'</td>
      <td '.$new_section.'>'.$email_address.'</td>
      <td '.$new_section.'>'.$home_phone.'</td>
      <td '.$new_section.'>'.$quantity.'</td>
      <td '.$new_section.'>'.$notes_to_producer.'</td>
      </tr>';

    $delivery_date_prior = $delivery_date;
  }

$content_head = '
  <table id="product_history" align="center" border="0" cellspacing="0" cellpadding="2" width="90%">
  <tr class="prodhead">
  <td colspan="7">
    <div class="right"><a href="'.$referrer.'">Return to producer product list</a></div>
    <h3>'.$product_name.'</h3>'.$detailed_notes.'<br><br>
  </td>
  </tr>
  <tr class="prodhead">
  <th>Product<br>Version</th>
  <th>Delivery<br>Date</th>
  <th>Customer Name</th>
  <th>Email Address</th>
  <th>Home Phone</th>
  <th>Qty</th>
  <th>Notes from Customer</th>
  </tr>';

$content_history .= $content_head.$content_body.'
  </table>';

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Order History for '.$product_name.'</span>';
$page_title = $business_name.': Order History for '.$product_name;
$page_tab = 'producer_panel';

$page_specific_css = '
  <style>
  #product_history td, #product_history th{padding-left:1em;}
  tr.prodhead {background:#efd}
  tr.prodhead th {text-align:left;font-size:1.1em;font-weight:bolder;border-bottom:1px solid #000;}
  tr.proddata {background:#ffe;color:#000;}
  tr.proddata:hover {background:#ffffcc;color:#008;}
  tr.proddata td.new_section {border-top:1px solid #ccc;}
  </style>';


include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$content_history.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
