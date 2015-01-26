<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

$hub_id = '';
$random_min = 0;
$random_max = 0;
$zero_qty = 0;
$need_checkout = 0;
$all_checked_out = 0;

// Configure the delivery_id
if (isset ($_GET['delivery_id'])) $delivery_id = $_GET['delivery_id'] / 1;
else $delivery_id = ActiveCycle::delivery_id();

// Sanitize and get directive for sort direction
$switch = '';
if (isset ($_GET['order']) && $_GET['order'] == 'asc')
  {
    $switch = '&amp;order=desc';
    $order = ' ASC';
    $order_arrow = '&uarr;';
  }
elseif (isset ($_GET['order']) && $_GET['order'] == 'desc')
  {
    $switch = '&amp;order=asc';
    $order = ' DESC';
    $order_arrow = '&darr;';
  }
else
  {
    $switch = '&amp;order=asc';
    $order = ' ASC';
    $order_arrow = '&uarr;';
  }

// Sanitize and get directive for sort parameter
if (isset ($_GET['sort']) && $_GET['sort'] == 'member_id') $sort = 'member_id';
elseif (isset ($_GET['sort']) && $_GET['sort'] == 'member_name') $sort = 'member_name';
elseif (isset ($_GET['sort']) && $_GET['sort'] == 'site_short') $sort = 'site_short';
else
  {
    $sort = 'member_name';
    $switch = '&amp;order=desc';
  }

// Set up the sort "ORDER BY" query
if ($sort == 'member_id') $order_by = 'ORDER BY '.TABLE_MEMBER.'.member_id'.$order;
elseif ($sort == 'member_name') $order_by = 'ORDER BY '.TABLE_MEMBER.'.last_name'.$order;
elseif ($sort == 'site_short') $order_by = 'ORDER BY '.NEW_TABLE_SITES.'.site_short'.$order;
else $order_by = 'ORDER BY '.TABLE_MEMBER.'.last_name'.$order;

$query = '
  SELECT
    '.NEW_TABLE_BASKETS.'.basket_id AS big_basket_id,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.delivery_id AS basket_delivery_id,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.auth_type,
    0 AS finalized,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.preferred_name,
    1 AS rte_confirmed,
    '.NEW_TABLE_SITES.'.site_short,
    '.NEW_TABLE_BASKETS.'.site_id,
    '.NEW_TABLE_SITES.'.hub_id,
    '.TABLE_HUBS.'.hub_short,
    '.TABLE_HUBS.'.hub_long,
    '.TABLE_ORDER_CYCLES.'.delivery_date
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  LEFT JOIN '.NEW_TABLE_SITES.' USING(site_id)
  LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id
  '.$order_by;

$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 785033 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num_orders = mysql_numrows($result);

while ( $row = mysql_fetch_array($result) )
  {
    $hub_short = $row['hub_short'];
    $site_id = $row['site_id'];
    $site_short = $row['site_short'];
    $basket_id = $row['big_basket_id'];
    $basket_delivery_id = $row['basket_delivery_id'];
    $member_id = $row['member_id'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = $row['business_name'];
    $rte_confirmed = $row['rte_confirmed'];
    $finalized = $row['finalized'];
    $preferred_name = $row['preferred_name'];
    $delivery_date = $row['delivery_date'];
    // Get the invoice subtotal from the ledger
    $query_total = '
      SELECT
        SUM(amount * IF(source_type = "member", 1, -1)) AS invoice_total
      FROM '.NEW_TABLE_LEDGER.'
      WHERE
        delivery_id = "'.mysql_real_escape_string($delivery_id).'"
        AND (
            (source_key = "'.mysql_real_escape_string($member_id).'"
            AND source_type = "member")
          OR
            (target_key = "'.mysql_real_escape_string($member_id).'"
            AND target_type = "member"))
        AND text_key != "payment received"
        AND text_key != "payment made"
        AND replaced_by IS NULL';
    $result_total = @mysql_query($query_total,$connection) or die(debug_print ("ERROR: 785033 ", array ($query_total,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ( $row_total = mysql_fetch_array($result_total) )
      {
        $invoice_total = $row_total['invoice_total'];
      }
    $invoice_running_total = $invoice_total + $invoice_running_total;

    // Get the basket estimate from the basket_items
    $query_estimate = '
      SELECT
        SUM((
          IF(random_weight = 1,
            IF(pricing_unit = ordering_unit,'.
              /* pricing_unit = ordering_unit --> will NOT have multiples (i.e. ordering
              6lb  of a random weight (5lb - 7 lb) treat as individual items and multiply by the qty=6 ordered */
              '
              (unit_price * quantity),'.
              /* pricing_unit != ordering_unit --> WILL be ordering multiples (i.e. ordering
              2 chickens of a random weight 6lb - 7lb) item WILL be multiplied by qty = 2 */
              '
              (unit_price * ((maximum_weight + minimum_weight) / 2) * quantity)),'.
            /* Not a random weight item */
            '
            (unit_price * quantity))
          * ((customer_fee_percent + 100) / 100)) + extra_charge) AS estimate_total,
          SUM('.NEW_TABLE_BASKET_ITEMS.'.checked_out) AS checked_out_count,
          COUNT(bpid) AS product_count
      FROM '.NEW_TABLE_BASKETS.'
      LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(basket_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
      WHERE
        basket_id = "'.mysql_real_escape_string($basket_id).'"
        AND member_id="'.mysql_real_escape_string($member_id).'"';
    $result_estimate = @mysql_query($query_estimate,$connection) or die(debug_print ("ERROR: 785033 ", array ($query_total,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ( $row_estimate = mysql_fetch_array($result_estimate) )
      {
        $estimate_total = $row_estimate['estimate_total'];
        $checked_out_count = $row_estimate['checked_out_count'];
        $product_count = $row_estimate['product_count'];
      }
    $estimate_running_total = $estimate_total + $estimate_running_total;

    if ( $product_count == 0)
      {
        $notice_style = 'zero_qty';
        $zero_qty ++;
      }
    elseif ($product_count != $checked_out_count)
      {
        $notice_style = 'need_checkout';
        $need_checkout ++;
      }
    else
      {
        $notice_style = 'all_checked_out';
        $all_checked_out ++;
      }
    $display .= '
      <tr id="'.$basket_id.'">
      <td class="basket_estimate '.$notice_style.'">$&nbsp;'.number_format($estimate_total,2).'</td>
      <td class="invoice_subtotal '.$notice_style.'">$&nbsp;'.number_format($invoice_total,2).'</td>
      <td class="site_short '.$notice_style.'">'.$site_short.'</td>
      <td class="member_id '.$notice_style.'"># '.$member_id.'</td>
      <td class="name '.$notice_style.'"><strong>'.$last_name.':</strong> '.$preferred_name.'</td>
      <td>';
    $sqlp = '
      SELECT
        '.NEW_TABLE_BASKET_ITEMS.'.product_id,
        '.NEW_TABLE_PRODUCTS.'.producer_id
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
      WHERE
        '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($member_id).'"
        AND '.NEW_TABLE_BASKETS.'.basket_id = "'.mysql_real_escape_string ($basket_id).'"
        AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != '.NEW_TABLE_BASKET_ITEMS.'.quantity
        AND '.NEW_TABLE_PRODUCTS.'.random_weight != "0"
        AND '.NEW_TABLE_BASKET_ITEMS.'.total_weight = "0"
      ORDER BY producer_id ASC';
    $resultprp = @mysql_query($sqlp, $connection) or die(debug_print ("ERROR: 785033 ", array ($sqlp,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $num = mysql_numrows($resultprp);
    while ( $row = mysql_fetch_array($resultprp) )
      {
        $display .= '<a href="product_list.php?&amp;type=producer_byproduct&amp;producer_id='.$row['producer_id'].'&amp;delivery_id='.$delivery_id.'">Weight needed: #'.$row['product_id'].'</a><br>';
      }
    $display .= '</td>
      <td class="member_links">'.
        (CurrentMember::auth_type('member_admin') == true ? '<a class="popup" onclick="popup_src(\'edit_member.php?action=edit&member_id='.$member_id.'&display_as=popup\')">Edit</a>' : '').
        (CurrentMember::auth_type('member_admin') == true && CurrentMember::auth_type('cashier') == true ? ' | ' : '').
        (CurrentMember::auth_type('cashier') == true ? '<a class="popup" onclick="popup_src(\'member_information.php?member_id='.$member_id.'&display_as=popup\')">View</a>' : '').'
      </td>
      <td class="order_links" valign="top"><a href="product_list.php?type=basket&amp;delivery_id='.$delivery_id.'&amp;member_id='.$member_id.'&amp;basket_id='.$basket_id.'">Basket</a>&nbsp;|&nbsp;<a href="show_report.php?type=customer_invoice&amp;delivery_id='.$delivery_id.'&amp;member_id='.$member_id.'">Invoice</a></td>
    </tr>';
    $member_id_list .= '#'.$member_id;
  }

$content_list = '
  <div align="center">
  <div id="delivery_id_nav">
    <a class="prior" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id - 1).'">&larr; PRIOR CYCLE </a>
    <a class="next" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id + 1).'"> NEXT CYCLE &rarr;</a>
  </div>
<table width="100%">
  <tr>
    <td align="left">
      <h3>Customer Orders: '.date ('F j, Y', strtotime ($delivery_date)).' ('.$num_orders.' Orders)</h3>
      <p>
        Combined Subtotal (all checked-out items): <strong>$'.number_format($invoice_running_total,2).'</strong><br/>
        Combined Estimate (using average for random-weight items): <strong>$'.number_format($estimate_running_total,2).'</strong><br/>
        '.($num_orders).' total baskets (<span class="all_checked_out">'.$all_checked_out.' complete</span>, <span class="zero_qty">'.$zero_qty.' empty</span>, <span class="need_checkout">'.$need_checkout.' need checkout</span>)
      </p>

<table id="customer_baskets">
  <tr>
    <th class="basket_estimate">Basket<br />Estimate</th>
    <th class="invoice_subtotal">Invoice<br />Subtotal</th>
    <th class="site_short"><a href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.$delivery_id.'&sort=site_short'.($sort == 'site_short' ? $switch : '&amp;order=asc').'">Site Code '.($sort == 'site_short' ? $order_arrow : '').'</a></th>
    <th class="member_id"><a href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.$delivery_id.'&sort=member_id'.($sort == 'member_id' ? $switch : '&amp;order=asc').'">Mem. ID '.($sort == 'member_id' ? $order_arrow : '').'</a></th>
    <th class="name"><a href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.$delivery_id.'&sort=member_name'.($sort == 'member_name' ? $switch : '&amp;order=asc').'">Name '.($sort == 'member_name' ? $order_arrow : '').'</a></th>
    <th class="weights_needed">Weights<br />needed</th>
    <th class="member_links">Member<br />Links</th>
    <th class="order_links">Order<br />Links</th>
  </tr>
  '.$display.'
  <tr>
    <th>$&nbsp;'.number_format($estimate_running_total, 2).'</th>
    <th>$&nbsp;'.number_format($invoice_running_total, 2).'</th>
    <td colspan="3"></td>
  </tr>
</table>
</td></tr>
</table>';

$page_specific_css = '
  <style type="text/css">
    #delivery_id_nav {
      width:45%;
      max-width:40rem;
      margin:5px auto 0;
      height:1.5em;
      background-color:#eef;
      }
    #delivery_id_nav .prior,
    #delivery_id_nav .next {
      display:block;
      line-height:1.5;
      padding:0 5px;
      }
    #delivery_id_nav .prior {
      float:left;
      }
    #delivery_id_nav .next {
      float:right;
      }
    table#customer_baskets {
      border:1px solid #666;
      border-collapse:separate;
      }
    #customer_baskets th {
      text-align:center;
      background-color:#040;
      color:#fff;
      }
    #customer_baskets th a {
      color:#ffa;
      }
    #customer_baskets td {
      border-bottom:1px solid #ddd;
      }
    #customer_baskets td:nth-child(1) /* est basket total */
      {
        text-align:right;
        border-right:1px solid #ddd;
      }
    #customer_baskets td:nth-child(2) /* invoice total */
      {
        text-align:right;
        font-weight:bold;
        border-right:1px solid #ddd;
      }
    #customer_baskets td:nth-child(3) /* site_id */
      {
        text-align:center;
      }
    #customer_baskets td:nth-child(4) /* member_id */
      {
        text-align:right;
      }
    .zero_qty {
      color:#aaa;
      vertical-align:top;
      }
    .need_checkout {
      color:#800;
      vertical-align:top;
      }
    .all_checked_out {
      color:#000;
      vertical-align:top;
      }
    td.weights_needed {
      font-size:75%;
      color:#800;
      }
    td.member_links,
    td.order_links {
      font-size:75%;
      text-align:center;
      }
    /* BEGIN STYLES FOR SIMPLEMODAL OVERLAY */
    a.popup {
      cursor:pointer;
      }
    #simplemodal-data {
      height:100%;
      background-color:#fff;
      }
    #simplemodal-container {
      box-shadow:10px 10px 10px #000;
      }
    #simplemodal-data iframe {
      border:0;
      height:95%;
      width:100%;
      }
    #simplemodal-container a.modalCloseImg:hover {
      background:url('.DIR_GRAPHICS.'/simplemodal_x_hover.png) no-repeat;
      }
    #simplemodal-container a.modalCloseImg {
      background:url('.DIR_GRAPHICS.'/simplemodal_x.png) no-repeat;
      width:40px;
      height:46px;
      display:inline;
      z-index:3200;
      position:absolute;
      top:-20px;
      right:-20px;
      cursor:pointer;
      }
    ul.producer_list {
      list-style-type:none;
      margin:0;
      padding-left:10px;
      }
    ul.producer_list li.producer {
      font-size:80%;
      color:#008;
      cursor:pointer;
      }
  </style>';

$page_specific_javascript .= '
<script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
<script type="text/javascript" src="'.PATH.'ajax/jquery-simplemodal.js"></script>
<script type="text/javascript">
  // Display an external page using an iframe
  // http://www.ericmmartin.com/projects/simplemodal/
  // Set the simplemodal close button
  $.modal.defaults.closeClass = "modalClose";
  // Popup the simplemodal dialog for selecting a site
  function popup_src(src) {
    $.modal(\'<a class="modalCloseImg modalClose">&nbsp;</a><iframe src="\' + src + \'">\', {
      opacity:70,
      overlayCss: {backgroundColor:"#000"},
      closeHTML:"",
      containerCss:{
        backgroundColor:"#fff",
        borderColor:"#fff",
        height:"80%",
        width:"80%",
        padding:0
        },
      overlayClose:true
      });
    };
  // Close the simplemodal iframe after 500 ms
  function close_modal_window() {
    $.modal.close();
    }
</script>';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Customer Orders with Totals</span>';
$page_title = 'Delivery Cycle Functions: Customer Orders with Totals';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

