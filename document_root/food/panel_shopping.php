<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

// Did this member have a prior order?
$query = '
  SELECT
    basket_id,
    delivery_id,
    delivery_date
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"
    AND delivery_date < "'.date('Y-m-d', time()).'"
  ORDER BY delivery_date DESC
  LIMIT 1';
$result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 850224 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
if ($row = mysqli_fetch_object ($result))
  {
    $prior_basket_id = $row->basket_id;
    $prior_delivery_id = $row->delivery_id;
    $prior_delivery_date = $row->delivery_date;
    $prior_basket_markup = '
        <li class="block block_42">
          <a href="'.PATH.'show_report.php?type=customer_invoice&delivery_id='.$prior_delivery_id.'">View Prior Invoice<span class="detail">* Delivered '.date (DATE_FORMAT_SHORT, strtotime ($prior_delivery_date)).'</span></a>
        </li>';
  }

// Get basket status information
$query = '
  SELECT
    COUNT(product_id) AS basket_quantity,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.delivery_type,
    '.TABLE_ORDER_CYCLES.'.delivery_date
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"
    AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysqli_real_escape_string ($connection, ActiveCycle::delivery_id()).'
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id';
$result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 756321 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$current_basket_quantity = 0;
if ($row = mysqli_fetch_object ($result))
  {
    $current_basket_quantity = $row->basket_quantity;
    $current_basket_id = $row->basket_id;
    $current_delivery_id = $row->delivery_id;
    $current_delivery_type = $row->delivery_type;
    $current_delivery_date = $row->delivery_date;
  }
// Check for current cycle orders...
if ( ActiveCycle::ordering_window() == 'open')
  {
    // Is there a basket open?
    if (isset ($current_basket_id) && $current_basket_id != 0)
      {
        $current_basket_markup = '
        <li class="block block_42">
          <a href="'.PATH.'product_list.php?type=customer_basket&delivery_id='.$current_delivery_id.'">View Current Basket<span class="detail">'.$current_basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').' in Basket</span></a>
        </li>
        <li class="block block_42">
          <a href="'.PATH.'show_report.php?type=customer_invoice&delivery_id='.$current_delivery_id.'">View Current Invoice<span class="detail">* Delivers '.date (DATE_FORMAT_SHORT, strtotime ($current_delivery_date)).'</span></a>
        </li>
        <li class="block block_42">
          <!-- Site: <strong>'.CurrentBasket::site_long().'</strong> -->
          <a class="popup_link" onClick="popup_src(\''.PATH.'customer_select_site.php?after_select=parent.reload_parent()\', \'select_delivery\', \'\', false);">Change Delivery Location<span class="detail">('.CurrentBasket::site_long().')</span></a>
        </li>';
      }
    else
      {
        $current_basket_markup = '
        <li class="block block_42">
          <a class="popup_link" onClick="popup_src(\''.PATH.'customer_select_site.php?after_select=parent.reload_parent()\', \'select_delivery\', \'\', false);">Select a location to begin shopping for<span class="detail">'.date (DATE_FORMAT_LONG, strtotime (ActiveCycle::delivery_date_next())).'</span></a>
        </li>';
      }
  }

// These are for displaying the ordering calendar
$call_display_as_include = true;
$_POST['non_admin'] = true; // Do not allow admin access from this page
$_POST['per_page'] = 5; // Force per_page setting
include_once (FILE_PATH.PATH.'ajax/display_order_schedule.php');
$display = '
  <div class="subpanel shopping_options">
    <header>
      '.ucfirst(ActiveCycle::ordering_window()).' for Shopping
    </header>
    <ul class="grid shopping_options">
      <li class="block block_63">
        <form class="search" method="get" action="'.PATH.'product_list.php">
          <input type="hidden" name="type" value="customer_list">
          <input type="hidden" name="select_type" value="all">
          <input type="text" value="" name="query">
          <input type="submit" value="search" name="action">
        </form>
        <span class="search_note">Words must be four or more characters</span>
      </li>
      <li class="block block_33"><a class="block_link" href="category_list.php?display_as=grid">                                  Browse by Category</a></li>
      <li class="block block_33"><a class="block_link" href="producer_list.php">                                                  Browse by Producer</a></li>
      <li class="block block_33"><a class="block_link" href="product_list.php?type=prior_baskets&select_type=previously_ordered"> Previously Ordered Products</a></li>
      <li class="block block_33"><a class="block_link" href="product_list.php?type=customer_list&select_type=all">                All Products</a></li>
      <li class="block block_33"><a class="block_link" href="product_list.php?type=customer_list&select_type=organic">            Organic Products</a></li>
      <li class="block block_33"><a class="block_link" href="product_list.php?type=customer_list&select_type=new_changed">        New &amp; Changed Products</a></li>'.
(CurrentMember::auth_type('institution') ? '
      <li class="block block_33"><a class="block_link" href="product_list.php?type=customer_list&select_type=wholesale">          Wholesale products</a></li>'
: '').'
    </ul>
  </div>
  <div class="subpanel ordering_info">
    <header>
      Common Links
    </header>
      <ul class="grid ordering_info">'.
        $current_basket_markup.
        $prior_basket_markup.'
        <li class="block block_42">
          <a class="popup_link" onClick="popup_src(\''.PATH.'order_history_popup.php?history_type=customer\', \'select_order_history\', \'\', false);">Other Baskets/Invoices</a>
        </li>
     </ul>
     <span class="alert_block warn">* Delivery date may vary by site. Please check invoice for details. Invoice will be blank until basket items are checked-out'.(CHECKOUT_MEMBER_ID == 'ALL' ? '' : ' by an admin. All items in your basket will be checked out after the order closes').'.</span>
  </div>
  <div class="subpanel next_order"><a id="schedule"></a>
    <header>
      Shopping Schedule
    </header>
    <div id="order_schedule_content">'.
    $calendar_data['markup'].'
    </div>
  </div>';

$page_specific_javascript = '
  // Functions to highlight calendar segments
  function highlight_calendar(target) {
    jQuery(".cycle-"+target).addClass("highlight");
    // document.getElementById("id-"+target).style.color="#880000";
    }
  function restore_calendar(target) {
    jQuery(".cycle-"+target).removeClass("highlight");
    // document.getElementById("id-"+target).style.color="#000000";
    }';

$page_specific_css = '
  /* Styles for the subpanels */
  .ordering_info header {
    background-image:url("'.DIR_GRAPHICS.'current.png");
    }
  .shopping_options header {
    background-image:url("'.DIR_GRAPHICS.'product.png");
    }
  .next_order header {
    background-image:url("'.DIR_GRAPHICS.'shopping.png");
    }
  .shopping_options {
    clear:both;
    }
  .shopping_options::after { /* force div to contain elements because overflow:auto does not work with the header element */
    content:".";
    display:block;
    clear:both;
    visibility:hidden;
    line-height:0;
    height:0;
    }

  /* BEGIN CHART DATA STYLES */
  #order_schedule_content {
    overflow:hidden;
    }
  #id-header div {
    font-weight:bold;
    }
  .order_cycle_row {
    font-size:10px;
    cursor:default;
    position: absolute;
    left:105%; /* Just more than calendar width */
    margin-top:-45px; /* Move it up a tad over one calendar row */
    overflow:auto;
    padding: 8px;
    border: 1px solid #888;
    border-radius: 10px;
    width:150%
    }
  .order_cycle_row:hover {
    background-color:rgba(0, 0, 0, .2);
    }
  .order_cycle_row .key +.value:before {
    content:": ";
    }
  .order_cycle_row .key {
    font-weight:bold;
    }
  .delivery_id,
  .date_open,
  .date_closed,
  .order_fill_deadline,
  .customer_type,
  .delivery_date {
    white-space:nowrap;
    display:inline-block;
    margin-right:1em;
    }
  .search_note {
    font-size:70%;
    }

  /* BEGIN STYLES FOR CALENDAR */
  #calendar {
    float:left;
    position:relative;
    width:38%;
    }
  .month_name {
    display:block;
    position:absolute;
    line-height:150%;
    left:0;
    font-size:25px;
    font-weight:bold;
    color:rgba(128,128,64,.5);
    }
  .week_row {
    position:static;
    overflow:hidden;
    height:50px;
    width:99.5%;
    border-left:1px solid #ccc;
    }
  .day {
    width:100%;
    height:100%;
    border:1px solid #ccc;
    border-left:0;
    border-bottom:0;
    }
  .day_frame {
    height:50px;
    width:14.25%;
    float:left;
    z-index:10;
    }
  .day_no-7 {
    clear:left;
    }
  .day .cal_date {
    display:block;
    height:33%;
    font-size:10px;
    }
  .cycle {
    position:relative;
    height:10px;
    z-index:20;
    }
  .cycle .ordering {
    position:absolute;
    opacity:0.3;
    height:10px;
    border:1px solid #888;
    border-top:3px solid #800;
    }
  .cycle .filling {
    position:absolute;
    opacity:0.3;
    height:10px;
    border:1px solid #888;
    border-top:3px solid #008;
    }
  .cycle .delivery {
    position:absolute;
    opacity:0.3;
    height:10px;
    border:1px solid #888;
    border-top:3px solid #060;
    }
  .cycle .highlight {
    opacity:0.8;
    border-bottom:1px solid #888;
    }
  /* Color code for the various order cycles */
  .order_cycle_row.distinct-1,
  .distinct-1 div {
    background-color:#ace;
    }
  .order_cycle_row.distinct-2,
  .distinct-2 div {
    background-color:#aec;
    }
  .order_cycle_row.distinct-3,
  .distinct-3 div {
    background-color:#cea;
    }
  .order_cycle_row.distinct-4,
  .distinct-4 div {
    background-color:#cae;
    }
  .order_cycle_row.distinct-5,
  .distinct-5 div {
    background-color:#eac;
    }
  .order_cycle_row.distinct-6,
  .distinct-6 div {
    background-color:#eca;
    }
  /* Give months distinctive colors */
  .month_no-1,
  .month_no-3,
  .month_no-5,
  .month_no-7,
  .month_no-9,
  .month_no-11 {
    background-color:rgba(255,255,255,.1);
    }
  .month_no-2,
  .month_no-4,
  .month_no-6,
  .month_no-8,
  .month_no-10,
  .month_no-12 {
    background-color:rgba(0,0,0,.1);
    }';

$page_title_html = '<span class="title">Shopping Panel</span>';
$page_subtitle_html = '<span class="subtitle">'.$_SESSION['show_name'].'</span>';
$page_title = 'Shopping Panel: '.$_SESSION['show_name'];
$page_tab = 'shopping_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
