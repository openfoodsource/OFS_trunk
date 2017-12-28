<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

$order_history_list = '';
if ($_GET['history_type'] == 'producer')
  {
    // Check if auth_type = producer_admin and there is a producer_id provided
    if (CurrentMember::auth_type('producer_admin') && $_GET['producer_id'])
      {
        // Keep the same producer_id value
        $producer_id = $_GET['producer_id'];
      }
    elseif ($_SESSION['producer_id_you'])
      {
        $producer_id = $_SESSION['producer_id_you'];
      }
    else
      {
        die(debug_print ("ERROR: 758932 ", array ('message'=>'No producer_id to use for finding past orders.'), basename(__FILE__).' LINE '.__LINE__));
      }
    $query_select = '
      SUM(quantity - out_of_stock) AS number_of_sales,
      COUNT(DISTINCT(basket_id)) AS number_of_customers,
      COUNT(DISTINCT(product_id)) AS number_of_products';
    $query_from = '
      '.NEW_TABLE_BASKET_ITEMS.'
    LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
    LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
    LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)';
    $query_where = '
      producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'"
    GROUP BY
      delivery_id';
    $history_type = 'producer';
  }
elseif ($_GET['history_type'] == 'customer')
  {
    $query_select = '
      basket_id,
      site_id,
      delivery_type,
      checked_out';
    $query_from = '
      '.NEW_TABLE_BASKETS.'
    LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)';
    $query_where = '
      member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"';
    $history_type = 'customer';
  }
else
  {
    debug_print ("ERROR: 758931 ", array ('message'=>'Call without target: '.$_GET['history_type'],$_GET), basename(__FILE__).' LINE '.__LINE__);
  }
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
      MAX(delivery_date) AS max_delivery_date,
      MIN(delivery_date) AS min_delivery_date
    FROM'.
    $query_from.'
    WHERE'.
      $query_where;
$result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 898034 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
  {
    $min_delivery_date = $row['min_delivery_date'];
    $max_delivery_date = $row['max_delivery_date'];
  }
// Now get this customer's baskets
$query = '
    SELECT 
      date_open,
      date_closed,
      order_fill_deadline,
      delivery_id,
      delivery_date,
      customer_type,'.
      $query_select.'
    FROM'.
      $query_from.'
    WHERE'.
      $query_where.'
    ORDER BY
      delivery_date DESC
    LIMIT '.$page_start.', '.PER_PAGE;
$result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 898034 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
  {
    // Initialize these array elements
    array_push ($delivery_id_array, $row['delivery_id']);
    $delivery_attrib[$row['delivery_id']]['date_open'] = $row['date_open'];
    $delivery_attrib[$row['delivery_id']]['time_open'] = strtotime($row['date_open']);
    $delivery_attrib[$row['delivery_id']]['date_closed'] = $row['date_closed'];
    $delivery_attrib[$row['delivery_id']]['time_closed'] = strtotime($row['date_closed']);
    $delivery_attrib[$row['delivery_id']]['order_fill_deadline'] = $row['order_fill_deadline'];
    $delivery_attrib[$row['delivery_id']]['delivery_date'] = $row['delivery_date'];
    $delivery_attrib[$row['delivery_id']]['customer_type'] = $row['customer_type'];
    // Values for customer order history
    $delivery_attrib[$row['delivery_id']]['basket_id'] = $row['basket_id'];
    $delivery_attrib[$row['delivery_id']]['site_id'] = $row['site_id'];
    $delivery_attrib[$row['delivery_id']]['delivery_type'] = $row['delivery_type'];
    $delivery_attrib[$row['delivery_id']]['checked_out'] = $row['checked_out'];
    // Values for producer order history
    $delivery_attrib[$row['delivery_id']]['number_of_sales'] = $row['number_of_sales'];
    $delivery_attrib[$row['delivery_id']]['number_of_customers'] = $row['number_of_customers'];
    $delivery_attrib[$row['delivery_id']]['number_of_products'] = $row['number_of_products'];
    // Use arbitrary counter so this is only evaluated on the first pass
    if ($counter++ == 0 && $max_delivery_date > $delivery_attrib[$row['delivery_id']]['delivery_date']) $show_prior_page_button = true;
    // Clobber $show_next_page_button each time so we have a valid result on the last pass
    $show_next_page_button = true;
    if ($min_delivery_date >= $delivery_attrib[$row['delivery_id']]['delivery_date']) $show_next_page_button = false;
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
          <a href="'.$_SERVER['SCRIPT_NAME'].'?history_type='.$history_type.'&page_start='.($page_start - PER_PAGE).'">Show previous page</a>
        </div>' : '').
      ($show_next_page_button == true ? '
      <div class="next_page">
        <a href="'.$_SERVER['SCRIPT_NAME'].'?history_type='.$history_type.'&page_start='.($page_start+PER_PAGE).'">Show_next_page</a>
      </div>' : '').'
      <ul class="basket_history">';
foreach ($delivery_id_array as $delivery_id)
  {
    $full_empty = '';
    $open_closed = '';
    $future_past = '';
    // Check if basket for the delivery had any items...
    if ($delivery_attrib[$delivery_id]['number_of_sales'] != 0 // For producers
        || $delivery_attrib[$delivery_id]['checked_out'] != 0) // For customers
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
    if ($after_current_count <= 2) // Including current order
      {
        if ($history_type == 'producer')
          {
            $order_history_list .= '
              <li class="'.$fe.$cg.$is.($full_empty == 'full' || $current == 'true' ? ' view' : '').'"'.($open_closed == 'open' ? ' id="current"' : '').'>
                <span class="delivery_date">Delivery: '.date('M j, Y', strtotime($delivery_attrib[$delivery_id]['delivery_date'])).'</span>
                <span class="number_of_sales">'.$delivery_attrib[$delivery_id]['number_of_sales'].' '.Inflect::pluralize_if($delivery_attrib[$delivery_id]['number_of_sales'], 'Item').'</span>
                <span class="number_of_products">('.$delivery_attrib[$delivery_id]['number_of_products'].' '.Inflect::pluralize_if($delivery_attrib[$delivery_id]['number_of_products'], 'product').'</span>
                <span class="number_of_customers">&rarr; '.$delivery_attrib[$delivery_id]['number_of_customers'].' '.Inflect::pluralize_if($delivery_attrib[$delivery_id]['number_of_customers'], 'customer').')</span>
                <span class="basket_action">
                  <a href="product_list.php?type=producer_basket&delivery_id='.$delivery_id.'" target="_parent">Basket</a>
                  <a href="show_report.php?type=producer_invoice&delivery_id='.$delivery_id.'" target="_parent">Invoice</a>
                </span>
              </li>';
          }
        else // if ($history_type == 'customer')
          {
            // Process basket quantity display
            if ($future_past != 'future')
              {
                if ($items_in_basket) 
                  {
                    $basket_quantity_text = '['.$items_in_basket.' '.Inflect::pluralize_if($items_in_basket, 'item').']';
                    $current_link = '
                      <a href="product_list.php?type=customer_basket&delivery_id='.$delivery_id.'" target="_parent">Basket</a>
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
                      <a>No Order</a>';
                  }
                else
                  {
                    $current_link = '
                      <a href="product_list.php?type=customer_basket&delivery_id='.$delivery_id.'" target="_parent">Basket</a>';
                  }
                $basket_quantity_text = '';
              }
            $order_history_list .= '
              <li class="'.$fe.$cg.$is.($full_empty == 'full' || $current == 'true' ? ' view' : '').'"'.($open_closed == 'open' ? ' id="current"' : '').'>
                <span class="delivery_date">Delivery: '.date('M j, Y', strtotime($delivery_attrib[$delivery_id]['delivery_date'])).'</span>
                <span class="order_dates">'.$month_open.' '.$day_open.' '.$year_open.' &ndash; '.$month_closed.' '.$day_closed.' '.$year_closed.'</span>
                <span class="basket_qty">'.$basket_quantity_text.'</span>
                <span class="basket_action">'.$current_link.'</span>
              </li>';
          }
      }
  }
$order_history_list .= '
        </ul>'.
      ($show_next_page_button == true ? '
      <div class="next_page">
        <a href="'.$_SERVER['SCRIPT_NAME'].'?history_type='.$history_type.'&page_start='.($page_start+PER_PAGE).'">Show_next_page</a>
      </div>' : '').'
      </div>
    </div>';

// Add styles to override delivery location dropdown
$page_specific_css .= '
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
    }';

// This is ALWAYS a popup
$display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$order_history_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
