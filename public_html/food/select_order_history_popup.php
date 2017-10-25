<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

$order_history_list = '';

$page_start = (isset ($_GET['page_start']) && $_GET['page_start'] >= 0 ? $_GET['page_start'] : 0);
$show_prior_page_button = false;
$show_next_page_button = false;

// Get a list of the order cycles since the member joined
$delivery_id_array = array();
$delivery_attrib = array ();
$after_current_count = 0;
$current = false;
$query = '
  SELECT 
    MAX(delivery_id) AS max_delivery_id,
    MIN(delivery_id) AS min_delivery_id
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_date > "'.mysqli_real_escape_string ($connection, $_SESSION['renewal_info']['membership_date']).'"
    AND date_open < NOW()';
$result = @mysqli_query($connection, $query) or die (debug_print ("ERROR: 898334 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysqli_fetch_array ($result))
  {
    $min_delivery_id = $row['min_delivery_id'];
    $max_delivery_id = $row['max_delivery_id'];
  }
$query = '
  SELECT 
    delivery_id,
    date_open,
    date_closed,
    order_fill_deadline,
    delivery_date,
    customer_type
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_date > "'.mysqli_real_escape_string ($connection, $_SESSION['renewal_info']['membership_date']).'"
    AND TIME_TO_SEC(TIMEDIFF(date_open, "'.date('Y-m-d H:i:s', time()).'")) < 0
  ORDER BY
    delivery_date DESC
  LIMIT '.$page_start.', '.PER_PAGE;
$result = @mysqli_query($connection, $query) or die (debug_print ("ERROR: 398034 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysqli_fetch_array ($result))
  {
    // Use arbitrary counter so this is only evaluated on the first pass
    if ($counter++ == 0 && $max_delivery_id > $row['delivery_id']) $show_prior_page_button = true;
    array_push ($delivery_id_array, $row['delivery_id']);
    $delivery_attrib[$row['delivery_id']]['date_open'] = $row['date_open'];
    $delivery_attrib[$row['delivery_id']]['time_open'] = strtotime($row['date_open']);
    $delivery_attrib[$row['delivery_id']]['date_closed'] = $row['date_closed'];
    $delivery_attrib[$row['delivery_id']]['time_closed'] = strtotime($row['date_closed']);
    $delivery_attrib[$row['delivery_id']]['order_fill_deadline'] = $row['order_fill_deadline'];
    $delivery_attrib[$row['delivery_id']]['delivery_date'] = $row['delivery_date'];
    $delivery_attrib[$row['delivery_id']]['customer_type'] = $row['customer_type'];
    // Initialize these array elements
    $delivery_attrib[$row['delivery_id']]['basket_id'] = '';
    $delivery_attrib[$row['delivery_id']]['site_id'] = '';
    $delivery_attrib[$row['delivery_id']]['delivery_type'] = '';
    $delivery_attrib[$row['delivery_id']]['checked_out'] = '';
    // Clobber $show_next_page_button each time so we have a valid result on the last pass
    $show_next_page_button = false;
    if ($min_delivery_id < $row['delivery_id']) $show_next_page_button = true;
  }
// Now get this customer's baskets
$query = '
  SELECT 
    *
  FROM
    '.NEW_TABLE_BASKETS.'
  WHERE
    member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"
  ORDER BY
    delivery_id DESC';
$result = @mysqli_query($connection, $query) or die (debug_print ("ERROR: 868034 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysqli_fetch_array ($result))
  {
    $delivery_attrib[$row['delivery_id']]['basket_id'] = $row['basket_id'];
    $delivery_attrib[$row['delivery_id']]['site_id'] = $row['site_id'];
    $delivery_attrib[$row['delivery_id']]['delivery_type'] = $row['delivery_type'];
    $delivery_attrib[$row['delivery_id']]['checked_out'] = $row['checked_out'];
  }
// Display the order cycles and baskets...
$order_history_list .= '
    <div id="basket_dropdown" class="dropdown">
      <h1 class="basket_history">
        Ordering History
      </h1>
      <div id="basket_history">'.
      ($show_prior_page_button == true ? '
        <div class="prior_page">
          <a href="'.$_SERVER['SCRIPT_NAME'].'?page_start='.($page_start - PER_PAGE).'">Show previous page</a>
        </div>' : '').
      ($show_next_page_button == true ? '
      <div class="next_page">
        <a href="'.$_SERVER['SCRIPT_NAME'].'?page_start='.($page_start+PER_PAGE).'">Show_next_page</a>
      </div>' : '').'
        <ul class="basket_history">';
foreach ($delivery_id_array as $delivery_id)
  {
    $full_empty = '';
    $open_closed = '';
    $future_past = '';
    // Check if basket for the delivery had any items...
    if ($delivery_attrib[$delivery_id]['checked_out'] != 0)
      {
        $fe = 'f'; // full
        $full_empty = 'full';
      }
    else
      {
        $fe = 'e'; // empty
        $full_empty = 'empty';
      }
    // Check if this basket is currently open...
    if ($delivery_attrib[$delivery_id]['time_open'] < time() &&
        $delivery_attrib[$delivery_id]['time_closed'] > time() &&
        CurrentMember::auth_type($delivery_attrib[$delivery_id]['customer_type']))
      {
        $cg = 'c'; // colored
        $current = true;
        // Start the after_current counter
        $open_closed = 'open';
        $after_current_count = 1;
      }
    else
      {
        $cg = 'g'; // grey
        $open_closed = 'closed';
      }
    // Check if this is a future delivery...
    if ($delivery_attrib[$delivery_id]['time_open'] > time())
      {
        $is = 'i'; // insubstantial
        $cg = 'c'; // colored
        $current = false;
        $after_current_count ++;
        $future_past = 'future';
      }
    else
      {
        $is = 's'; // substantial
        $future_past = 'past';
      }
    $day_open = date ('j', $delivery_attrib[$delivery_id]['time_open']);
    $month_open = date ('M', $delivery_attrib[$delivery_id]['time_open']);
    $year_open = date ('Y', $delivery_attrib[$delivery_id]['time_open']);
    $day_closed = date ('j', $delivery_attrib[$delivery_id]['time_closed']);
    $month_closed = date ('M', $delivery_attrib[$delivery_id]['time_closed']);
    $year_closed = date ('Y', $delivery_attrib[$delivery_id]['time_closed']);
    if ($day_open == $day_closed) $day_open = '';
    if ($month_open == $month_closed) $month_closed = '';
    if ($year_open == $year_closed) $year_open = '';
    $items_in_basket = abs($delivery_attrib[$delivery_id]['checked_out']);
    $current_link = '';
    // Process basket quantity display
    if ($future_past != 'future')
      {
        if ($items_in_basket) 
          {
            $basket_quantity_text = '['.$items_in_basket.' '.Inflect::pluralize_if($items_in_basket, 'item').']';
            $current_link = '
              <a href="product_list.php?type=basket&delivery_id='.$delivery_id.'" target="_parent">Basket</a>
              <a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id.'" target="_parent">Invoice</a>';
          }
        else
          {
            $basket_quantity_text = '[No order]';
          }
      }
    // Current order... set link for opening or checking basket
    if ($open_closed == 'open')
      {
        // Basket does not exist?
        if (! $delivery_attrib[$delivery_id]['basket_id'])
          {
            $current_link = '
              <a href="'.PATH.'select_delivery_popup.php?after_select=reload_parent()">Start an Order</a>';
          }
        else
          {
            $current_link = '
              <a href="product_list.php?type=basket&delivery_id='.$delivery_id.'" target="_parent">Basket</a>';
          }
        $basket_quantity_text = '';

      }
    if ($after_current_count <= 2) // Including current
      {
// Need some onclick code for class=view (full baskets)
        $order_history_list .= '
          <li class="'.$fe.$cg.$is.($full_empty == 'full' || $current == 'true' ? ' view' : '').'"'.($open_closed == 'open' ? ' id="current"' : '').'>
            <span class="delivery_date">Delivery: '.date('M j, Y', strtotime($delivery_attrib[$delivery_id]['delivery_date'])).'</span>'.
            (CurrentBasket::basket_id() && $current == 'true' ? '
              <span class="basket_link"><a href="product_list.php?type=basket&delivery_id='.$delivery_id.'" target="_parent">Basket</a></span>
               &bull; <!--
               <span class="accounting_link"><a href="member_view_balance.php?account_type=member&delivery_id='.$delivery_id.'" target="_parent">Account</a></span>
               &bull; -->
               <span class="accounting_link"><a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id.'&member_id='.$_SESSION['member_id'].'" target="_parent">Invoice</a></span>'
            : '').'
            <span class="order_dates">'.$month_open.' '.$day_open.' '.$year_open.' &ndash; '.$month_closed.' '.$day_closed.' '.$year_closed.'</span>
            <span class="basket_qty">'.$basket_quantity_text.'</span>
            <span class="basket_action">'.$current_link.'</span>
          </li>';
      }
  }
$order_history_list .= '
        </ul>'.
      ($show_next_page_button == true ? '
      <div class="next_page">
        <a href="'.$_SERVER['SCRIPT_NAME'].'?page_start='.($page_start+PER_PAGE).'">Show_next_page</a>
      </div>' : '').'
      </div>
    </div>';

// Add styles to override delivery location dropdown
$page_specific_css .= '
  <link href="'.PATH.'stylesheet.css" rel="stylesheet" type="text/css">
  <style type="text/css">
  body {
    font-size:87%;
    border-radius:15px;
    }
  /* Styles for the Basket Selector */
  #basket_history .prior_page,
  #basket_history .next_page {
    display:inline-block;
    padding:3px 6px;
    border:1px solid #8a8;
    border-radius:3px;
    background-color:#bc9;
    margin:5px 10px;
    }
  #basket_history .prior_page:hover,
  #basket_history .next_page:hover {
    background-color:#efc;
    }
  #basket_history .prior_page a,
  #basket_history .next_page a {
    text-decoration:none;
    }
  #basket_dropdown {
    border:0;
    width:100%;
    }
  h1.basket_history {
    position:fixed;
    top:0;
    box-sizing:border-box;
    width:100%;
    font-size:16px;
    color:#050;
    background-color:#fff;
    height:26px;
    margin:0;
    padding:3px;
    text-align:center;
    line-height:1;
    }
  #basket_history {
    width:100%;
    background-color:#fff;
    margin-top:26px;
    }
  ul.basket_history {
    list-style-type:none;
    padding-left:0;
    }
  ul.basket_history li {
    padding-top:5px;
    text-align:left;
    height:80px;
    padding-left:70px;
    border-top:1px dotted #eee;
    border-bottom:1px dotted #eee;
    }
  ul.basket_history li.view:hover {
    cursor:default;
    height:80px;
    background-color:#efd;
    border-top:1px solid #ad6;
    border-bottom:1px solid #ad6;
    }
  ul.basket_history li.ecs,
  ul.basket_history li.fcs {
    background-color:#eeb;
    border-top:1px solid #cba;
    border-bottom:1px solid #cba;
    }
  ul.basket_history li span {
    vertical-align: middle;
    }
  .delivery_date {
    display:block;
    font-size:130%;
    font-weight:bold;
    color:#350;
    }
  .order_dates {
    display:inline-block;
    color:#530;
    }
  .basket_qty {
    display:inline-block;
    color:#000;
    }
  .basket_action {
    display:block;
    color:#000;
    }
  .basket_action a {
    display:inline-block;
    font-size:130%;
    padding:3px 6px;
    border:1px solid #aa8;
    background-color:#dda;
    margin:0 0 0 15px;
    border-radius:3px;
    color:#000;
    }
  .basket_action a:hover {
    text-decoration:none;
    border:1px solid #aa8;
    background-color:#ffc;
    color:#000;
    }
  li.fcs {
    background:url(/food/grfx/basket-fcs.png) no-repeat 7px center;
    }
  li.fci {
    background:url(/food/grfx/basket-fci.png) no-repeat 7px center;
    }
  li.fgs {
    background:url(/food/grfx/basket-fgs.png) no-repeat 7px center;
    }
  li.fgi {
    background:url(/food/grfx/basket-fgi.png) no-repeat 7px center;
    }
  li.ecs {
    background:url(/food/grfx/basket-ecs.png) no-repeat 7px center;
    }
  li.eci {
    background:url(/food/grfx/basket-eci.png) no-repeat 7px center;
    }
  li.egs {
    background:url(/food/grfx/basket-egs.png) no-repeat 7px center;
    }
  li.egi {
    background:url(/food/grfx/basket-egi.png) no-repeat 7px center;
    }
  li.eci span,
  li.egs span,
  li.egi span {
    color:#999;
    }
  .basket_link {
    font-size:80%;
    }
  .accounting_link {
    font-size:80%;
    }
  </style>';

// This is ALWAYS a popup
$display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$order_history_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
