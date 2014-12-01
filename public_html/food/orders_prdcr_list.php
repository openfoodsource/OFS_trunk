<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin,producer_admin,site_admin,cashier');

$i=0;
$query = '
  SELECT
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_MEMBER.'.address_line1,
    '.TABLE_MEMBER.'.city,
    '.TABLE_MEMBER.'.state,
    '.TABLE_MEMBER.'.zip,
    '.TABLE_MEMBER.'.county,
    '.TABLE_MEMBER.'.home_phone,
    '.TABLE_MEMBER.'.work_phone,
    '.TABLE_MEMBER.'.mobile_phone,
    '.TABLE_MEMBER.'.email_address
  FROM
    '.NEW_TABLE_BASKET_ITEMS.'
  LEFT JOIN
    '.NEW_TABLE_BASKETS.' USING(basket_id)
  LEFT JOIN
    '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
  LEFT JOIN
    '.TABLE_PRODUCER.' USING(producer_id)
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
  GROUP BY '.TABLE_PRODUCER.'.producer_id
  ORDER BY
    business_name ASC,
    last_name ASC';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 803532 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($result) )
  {
    $business_name = '';
    $phone_number = '';
    $county = '';
    if ( $row['business_name'] )
      {
        $business_name = $row['business_name'];
      }
    else
      {
        $business_name = $row['first_name'].' '.$row['last_name'];
      }
    if ($row['home_phone'])
      {
        $phone_number = $row['home_phone'];
      }
    elseif ($row['work_phone'])
      {
        $phone_number = $row['work_phone'];
      }
    elseif ($row['mobile_phone'])
      {
        $phone_number = $row['mobile_phone'];
      }
    if ($row['county'] != '')
      {
        $county = strtoupper ($row['county'].' Co.');
      }
    $display .= '
        <tr>
          <td style="border-bottom:1px solid gray;" valign="top">
            <b>'.$business_name.'</b>
            ('.$row['preferred_name'].')<br>
            <font style="font-size:75%;">'.$row['address_line1'].'<br>
            '.$row['city'].', '.$row['state'].' '.$row['zip'].'<br>
            '.$county.'<br>
            <strong>'.$phone_number.'</strong> &ndash; <a href="mailto:'.$business_name.' &lt;'.$row['email_address'].'&gt;">'.$row['email_address'].'</a></font>
          </td>
          <td style="border-bottom:1px solid gray;font-size:70%;">
            <a href="product_list.php?delivery_id='.$_REQUEST['delivery_id'].'&producer_id='.$row['producer_id'].'&type=producer_byproduct">Product</a><br/>
            <a href="product_list.php?delivery_id='.$_REQUEST['delivery_id'].'&producer_id='.$row['producer_id'].'&type=producer_bystoragecustomer">Customer Storage</a><br/>
            <a href="product_list.php?delivery_id='.$_REQUEST['delivery_id'].'&producer_id='.$row['producer_id'].'&type=producer_bycustomer">Customer</a><br/>
          </td>
          <td style="border-bottom:1px solid gray;font-size:70%;">';
    $query_weight = '
      SELECT
        COUNT('.NEW_TABLE_BASKET_ITEMS.'.bpid) AS count
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
      LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
      WHERE
        '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
        AND producer_id = "'.mysql_real_escape_string ($row["producer_id"]).'"
        AND out_of_stock != quantity
        AND random_weight = 1
        AND total_weight = 0';
    $result_weight = @mysql_query($query_weight, $connection) or die(debug_print ("ERROR: 860323 ", array ($query_weight,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $unfilled_random_weights = 0;
    if ($row_weight = mysql_fetch_array($result_weight))
      {
        $unfilled_random_weights = $row_weight['count'];
      }
    if ($unfilled_random_weights) $display .= '
          [waiting on '.$unfilled_random_weights.' '.Inflect::pluralize_if ($unfilled_random_weights, 'weight').']';
    $display .= '
          </td>
        </tr>';
    $i++;
  }
$query = '
  SELECT
    delivery_id,
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 906324 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($result) )
  {
    $delivery_id = $row['delivery_id'];
    $delivery_date = $row['delivery_date'];
  }

$content_list = '
      <div align="center">
        <h3>Producer List for '.date ('F j, Y', strtotime ($delivery_date)).'</h3>
        <table width="70%" cellpadding="4" cellspacing="0" border="0" style="border:1px solid gray;">
          <tr bgcolor="#AEDE86">
            <td align="center" valign="bottom" style="border-bottom:1px solid gray;"><b>Business Name</b></td>
            <td align="center" valign="bottom" style="border-bottom:1px solid gray;"><b>View Invoice</b></td>
            <td align="center" valign="bottom" style="border-bottom:1px solid gray;"><b>Status</b></td>
          </tr>
          '.$display.'
      </table>
      </div>';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Producers List</span>';
$page_title = 'Delivery Cycle Functions: Producers List';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

