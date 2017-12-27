<?php
include_once 'config_openfood.php';
session_start();
// Validations are done in the product_list/* files

// See if this is a producer-related page (if so, exempt from needing to select a site)
$producer_page = false;
if (isset ($_GET['type'])) $product_list_type = $_GET['type'];
if ($product_list_type == 'labels_byproduct'
    || $product_list_type == 'labels_bystoragecustomer'
    || $product_list_type == 'producer_byproduct'
    || $product_list_type == 'producer_bystoragecustomer'
    || $product_list_type == 'producer_bycustomer'
    || $product_list_type == 'producer_list')
  {
    $producer_page = true;
  }
// Do not show product lists unless the member has already opened a basket, so go do that...
unset ($_SESSION['redirect_after_basket_select']);
if (! CurrentBasket::basket_id()
    && ! $producer_page)
  {
    // Put the originally requested URI into the $_SESSION for later retrieval
    $_SESSION['redirect_after_basket_select'] = $_SERVER['REQUEST_URI'];
    header ('Location: '.BASE_URL.PATH.'select_delivery_page.php?first_call=true');
    exit (0);
  }

// Sanitize variables that are expected to be numeric
if (isset ($_GET['producer_id'])) $_GET['producer_id'] = preg_replace ('/[^0-9]/', '', $_GET['producer_id']);
if (isset ($_GET['category_id'])) $_GET['category_id'] = preg_replace ('/[^0-9]/', '', $_GET['category_id']);
if (isset ($_GET['subcat_id'])) $_GET['subcat_id'] = preg_replace ('/[^0-9]/', '', $_GET['subcat_id']);

// Items dependent upon the location of this header
$unique = array();
$pager = array();
if (isset ($_POST['action']))
  {
    if (( isset ($_POST['basket_x']) && isset ($_POST['basket_y'])) ||
      ( isset ($_POST['basket_add_x']) && isset ($_POST['basket_add_y'])))
      {
        $_POST['action'] = 'add';
      }
    elseif ( isset ($_POST['basket_sub_x']) && isset ($_POST['basket_sub_y']))
      {
        $_POST['action'] = 'sub';
      }
    $process_type = $_POST['process_type'];
    $non_ajax_query = true;
    // Different back-end for customer_list|basket_list|producer_basket
    include(FILE_PATH.PATH.'ajax/'.$process_type.'.php');
  }

// Determine whether a basket is open or not
$basket_open_true = 0;
if (CurrentBasket::basket_id()) $basket_open_true = 1;

// Set up some variables that might be needed
if ($_SESSION['member_id']) $member_id = $_SESSION['member_id'];
// Allow cashier to override member_id
if ($_GET['member_id'] && CurrentMember::auth_type('cashier')) $member_id = $_GET['member_id'];

if ($_GET['subcat_id']) $subcat_id = $_GET['subcat_id'];
if ($_GET['producer_id']) $producer_id = $_GET['producer_id'];
if ($_GET['producer_link']) $producer_link = $_GET['producer_link'];
if ($_SESSION['producer_id_you']) $producer_id_you = $_SESSION['producer_id_you'];
// Allow GET to trump SESSION for producer_id -- but only for admin
if (CurrentMember::auth_type('producer_admin') && isset($_GET['producer_id']))
  $producer_id_you = $_GET['producer_id'];

// Get a delivery_id for pulling current producer "invoices"
if ($_GET['delivery_id']) $delivery_id = mysqli_real_escape_string ($connection, $_GET['delivery_id']);
else $delivery_id = mysqli_real_escape_string ($connection, ActiveCycle::delivery_id());
// Get a basket_id in cases where we are looking at baskets or invoices...
if ($_GET['basket_id']) $basket_id = mysqli_real_escape_string ($connection, $_GET['basket_id']);
else $basket_id = mysqli_real_escape_string ($connection, CurrentBasket::basket_id());

// Determine whether the order is open or not
$order_open = false;
if ((ActiveCycle::ordering_window() == 'open' && ActiveCycle::delivery_id() == $delivery_id ) ||
  CurrentMember::auth_type('orderex')) $order_open = true;

// Initialize display of wholesale and retail to false
$display_base_price = false;
$display_anonymous_price = false;
$is_wholesale_item = false;

// SET UP QUERY PARAMETERS THAT APPLY TO MOST LISTS



// Only show for listed producers -- not unlisted (1) or suspended (2)
$where_unlisted_producer = '
    AND unlisted_producer = "0"';

// Normally, do not show producers that are pending (1)
$where_producer_pending = '
    '.TABLE_PRODUCER.'.pending = 0';

// Set up an exception for hiding zero-inventory products
$where_zero_inventory = '';
if (EXCLUDE_ZERO_INV == true)
  {
    // Can use TABLE_PRODUCT here because this condition is only used on the public product lists
    $where_zero_inventory = '
    AND (
      IF('.NEW_TABLE_PRODUCTS.'.inventory_id > 0, FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull), 1)
      OR '.NEW_TABLE_BASKET_ITEMS.'.quantity > 0)';
  }

// Set the default subquery_confirmed to look only at confirmed products
$where_confirmed .= '
    AND '.NEW_TABLE_PRODUCTS.'.confirmed = "1"';

//////////////////////////////////////////////////////////////////////////////////////
//                                                                                  //
//                         QUERY AND DISPLAY THE DATA                               //
//                                                                                  //
//////////////////////////////////////////////////////////////////////////////////////

// Make sure we're looking for a valid list_type
if (isset ($_GET['type']) && file_exists ('product_list/'.$_GET['type'].'.php')) $list_type = $_GET['type'];
else $list_type = 'by_id';

// Include the appropriate list "module" from the product_list directory
include_once ('product_list/'.$list_type.'.php');
// Now include the template (specified in the include_file)
include_once ('product_list/'.$template_type.'_template.php');

// This setting might be overridden below or in included files
$pager['per_page'] = PER_PAGE;
// Labels do not have pages
if ($template_type == 'labels') $pager['per_page'] = 1000000;
// Set up the pager for the output
$list_start = ($_GET['page'] - 1) * $pager['per_page'];
if ($list_start < 0) $list_start = 0;
$query_limit = $list_start.', '.$pager['per_page'];


// Add limits to the query
$query .= '
  LIMIT '.$query_limit;

$result = @mysqli_query($connection, $query) or die (debug_print ("ERROR: 755237 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
// Get the total number of rows (for pagination) -- not counting the LIMIT condition
$query_found_rows = '
  SELECT
    FOUND_ROWS() AS found_rows';
$result_found_rows = @mysqli_query ($connection, $query_found_rows) or die (debug_print ("ERROR: 860842 ", array ($query_found_rows, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
// Handle pagination for multi-page results
$row_found_rows = mysqli_fetch_array ($result_found_rows, MYSQLI_ASSOC);
$pager['found_rows'] = $row_found_rows['found_rows'];
if ($_GET['page']) $pager['this_page'] = $_GET['page'];
else $pager['this_page'] = 1;
$pager['last_page'] = ceil (($pager['found_rows'] / $pager['per_page']) - 0.00001);
$pager['page'] = 0;
while (++$pager['page'] <= $pager['last_page'])
  {
    if ($pager['page'] == $pager['this_page']) $pager['this_page_true'] = true;
    else $pager['this_page_true'] = false;
    $pager['display'] .= pager_display_calc($pager);
  }
$pager_navigation_display = pager_navigation($pager);
$order_cycle_navigation_display = order_cycle_navigation($pager);

// Iterate through the returned results and display products
while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
  {
    $unique['product_count'] ++;
    // If this row does not contain any product information, then break out of the "while" loop
    if ($row['product_id'] == '')
      {
        $unique['product_count'] = 0;
        break;
      }
    // Add non-database variables to the $row array so they are available in function calls
    if ($row_counter++ < 1) // only do this once
      {
        if (isset ($_SESSION['member_id']))
          {
            $display_base_price = true;
            $display_anonymous_price = false;
          }
        else
          {
            $display_base_price = false;
            $display_anonymous_price = true;
          }
      }
    $row['display_base_price'] = $display_base_price;
    $row['display_anonymous_price'] = $display_anonymous_price;
    $row['is_wholesale_item'] = $is_wholesale_item;
// $row['availability_array'] = explode (',', $row['availability_list']);
    // $row['site_id_you'] = CurrentBasket::site_id();
    // $row['site_short_you'] = CurrentBasket::site_short();
    // $row['site_long_you'] = CurrentBasket::site_long();
    $row['order_open'] = $order_open;

    // Open the product list
    if ($first_time_through++ == 0) $display .= open_list_top($row);

    // Set the various fees:
    $row['customer_customer_adjust_fee'] = 0;
    $row['producer_customer_adjust_fee'] = 0;
    if (PAYS_CUSTOMER_FEE == 'customer') $row['customer_customer_adjust_fee'] = $row['customer_fee_percent'] / 100;
    elseif (PAYS_CUSTOMER_FEE == 'producer') $row['producer_customer_adjust_fee'] = $row['customer_fee_percent'] / 100;
    $row['customer_product_adjust_fee'] = 0;
    $row['producer_product_adjust_fee'] = 0;
    if (PAYS_PRODUCT_FEE == 'customer') $row['customer_product_adjust_fee'] = $row['product_fee_percent'] / 100;
    elseif (PAYS_PRODUCT_FEE == 'producer') $row['producer_product_adjust_fee'] = $row['product_fee_percent'] / 100;
    $row['customer_subcat_adjust_fee'] = 0;
    $row['producer_subcat_adjust_fee'] = 0;
    if (PAYS_SUBCATEGORY_FEE == 'customer') $row['customer_subcat_adjust_fee'] = $row['subcategory_fee_percent'] / 100;
    elseif (PAYS_SUBCATEGORY_FEE == 'producer') $row['producer_subcat_adjust_fee'] = $row['subcategory_fee_percent'] / 100;
    $row['customer_producer_adjust_fee'] = 0;
    $row['producer_producer_adjust_fee'] = 0;
    if (PAYS_PRODUCER_FEE == 'customer') $row['customer_producer_adjust_fee'] = $row['producer_fee_percent'] / 100;
    elseif (PAYS_PRODUCER_FEE == 'producer') $row['producer_producer_adjust_fee'] = $row['producer_fee_percent'] / 100;

    // All this parsing and rounding is to match the line-item breakout in the ledger to prevent roundoff mismatch

    //$row['cost_multiplier'] = ($row['random_weight'] == 1 ? $row['total_weight'] : ($row['basket_quantity'] - $row['out_of_stock'])) * $row['unit_price'];
    // The line above is replaced by the following cost_multiplier calculations
    if ($row['random_weight'] == 1 &&                           // Random weight item
        $row['total_weight'] == 0 &&                            // AND no weight entered
        ($row['basket_quantity'] - $row['out_of_stock']) != 0)  // AND not out of stock
      {
        switch (RANDOM_CALC)
          {
            case 'ZERO':
              $row['cost_multiplier'] = 0;
              $row['weight_needed'] = true;
              break;
            case 'AVG':
              $row['cost_multiplier'] = ($row['minimum_weight'] + $row['maximum_weight']) / 2 * $row['unit_price'] * ($row['basket_quantity'] - $row['out_of_stock']);
              $row['weight_needed'] = true;
              break;
            case 'MIN':
              $row['cost_multiplier'] = $row['minimum_weight'] * $row['unit_price'] * ($row['basket_quantity'] - $row['out_of_stock']);
              break;
            case 'MAX':
              $row['cost_multiplier'] = $row['maximum_weight'] * $row['unit_price'] * ($row['basket_quantity'] - $row['out_of_stock']);
              break;
          }
        $row['weight_needed'] = true;
      }
    elseif ($row['random_weight'] == 1) // Random weight item with weight entered (or out of stock)
      {
        $row['cost_multiplier'] = $row['total_weight'] * $row['unit_price'];
      }
    else // Not a random weight item
      {
        $row['cost_multiplier'] = ($row['basket_quantity'] - $row['out_of_stock']) * $row['unit_price'];
      }
    // Following are for products that are in the customer's basket
    $row['producer_adjusted_cost'] = round($row['cost_multiplier'], 2)
                                     - round($row['producer_customer_adjust_fee'] * $row['cost_multiplier'], 2)
                                     - round($row['producer_product_adjust_fee'] * $row['cost_multiplier'], 2)
                                     - round($row['producer_subcat_adjust_fee'] * $row['cost_multiplier'], 2)
                                     - round($row['producer_producer_adjust_fee'] * $row['cost_multiplier'], 2);
    $row['customer_adjusted_cost'] = round($row['cost_multiplier'], 2)
                                     + round($row['customer_customer_adjust_fee'] * $row['cost_multiplier'], 2)
                                     + round($row['customer_product_adjust_fee'] * $row['cost_multiplier'], 2)
                                     + round($row['customer_subcat_adjust_fee'] * $row['cost_multiplier'], 2)
                                     + round($row['customer_producer_adjust_fee'] * $row['cost_multiplier'], 2);
    // Following are for products that are NOT in a customer's basket
    $row['base_producer_cost'] = round($row['unit_price'], 2)
                                 - round($row['producer_customer_adjust_fee'] * $row['unit_price'], 2)
                                 - round($row['producer_product_adjust_fee'] * $row['unit_price'], 2)
                                 - round($row['producer_subcat_adjust_fee'] * $row['unit_price'], 2)
                                 - round($row['producer_producer_adjust_fee'] * $row['unit_price'], 2);
    $row['base_customer_cost'] = round($row['unit_price'], 2)
                                 + round($row['customer_customer_adjust_fee'] * $row['unit_price'], 2)
                                 + round($row['customer_product_adjust_fee'] * $row['unit_price'], 2)
                                 + round($row['customer_subcat_adjust_fee'] * $row['unit_price'], 2)
                                 + round($row['customer_producer_adjust_fee'] * $row['unit_price'], 2);
    // Following are for products where the customer is NOT logged in
    $row['base_anonymous_cost'] = round($row['unit_price'], 2)
                                      + round(ANONYMOUS_MARKUP_PERCENT * $row['unit_price'] / 100, 2)
                                      + round($row['customer_product_adjust_fee'] * $row['unit_price'], 2)
                                      + round($row['customer_subcat_adjust_fee'] * $row['unit_price'], 2)
                                      + round($row['customer_producer_adjust_fee'] * $row['unit_price'], 2);
    // These are per-item values based on the SHOW_ACTUAL_PRICE setting in baskets
    if (SHOW_ACTUAL_PRICE) $row['display_adjusted_producer_price'] = $row['producer_adjusted_cost'];
    else $row['display_adjusted_producer_price'] = $row['unit_price'];
    if (SHOW_ACTUAL_PRICE) $row['display_adjusted_customer_price'] = $row['customer_adjusted_cost'];
    else $row['display_adjusted_customer_price'] = $row['unit_price'];
    // These are per-item values based on the SHOW_ACTUAL_PRICE setting NOT in baskets
    if (SHOW_ACTUAL_PRICE) $row['display_base_producer_price'] = $row['base_producer_cost'];
    else $row['display_base_producer_price'] = $row['unit_price'];
    if (SHOW_ACTUAL_PRICE) $row['display_base_customer_price'] = $row['base_customer_cost'];
    else $row['display_base_customer_price'] = $row['unit_price'];
    if (SHOW_ACTUAL_PRICE) $row['display_base_anonymous_price'] = $row['base_anonymous_cost'];
    else $row['display_base_anonymous_price'] = $row['unit_price'];

    // Set up wholesale flag
    if ($row['listing_auth_type'] == "institution") $row['is_wholesale_item'] = true;
    else $row['is_wholesale_item'] = false;

    // Get the availability for this product at this member's chosen site_id
    // Two conditions will allow products to be purchased (availability = true):
    //   1. No availibility set for the producer means the product is available everywhere
    //   2. Customer's site is in the set of availabile locations for the producer
// if ($row['availability_list'] == '' || in_array ($row['site_id_you'], $row['availability_array'])) $row['availability'] = true;
    // Otherwise the product is not available for this customer to purchase
//else $row['availability'] = false;
    $row['row_activity_link'] = row_activity_link_calc($row, $pager, $unique);
    $row['random_weight_display'] = random_weight_display_calc($row);
    $row['business_name_display'] = business_name_display_calc($row);
    $row['pricing_display'] = pricing_display_calc($row);
    $row['total_display'] = total_display_calc($row, $unique);
    $row['ordering_unit_display'] = ordering_unit_display_calc($row);
    $row['image_display'] = image_display_calc($row);
    $row['prodtype_display'] = prodtype_display_calc($row);
    $row['inventory_display'] = inventory_display_calc($row);
    // New major division
    if ($row[$major_division] != $$major_division_prior && $show_major_division == true)
      {
        if ($listing_is_open)
          {
            if ($show_minor_division) $display .= minor_division_close($row, $unique);
            $display .= major_division_close($row);
            $listing_is_open = 0;
          }
        $display .= major_division_open($row, $major_division);
        // New major division will force a new minor division
        $$minor_division_prior = -1;
      }

    // New minor division
    if ($row[$minor_division] != $$minor_division_prior && $show_minor_division == true)
      {
        if ($listing_is_open)
          {
            $display .= minor_division_close($row, $unique);
            $listing_is_open = 0;
          }
        $display .= minor_division_open($row, $minor_division);
      }

    $listing_is_open = 1;
    $display .= show_listing_row($row, $row_type);

    // Handle prior values to catch changes
    $$major_division_prior = $row[$major_division];
    $$minor_division_prior = $row[$minor_division];
  }
$unique['completed'] = 'true';
// Close minor if there were any products
if ($unique['product_count'] > 0 && $show_minor_division) $display .= minor_division_close($row, $unique);
// Close major if there were any products
if ($unique['product_count'] > 0 && $show_major_division) $display .= major_division_close($row);
// Close the product list if there were any products listed
if ($pager['found_rows'] && $unique['product_count'] > 0)
  {
    $display .= close_list_bottom($row, $unique);
  }
// Otherwise send the "nothing to show" message
else
  {
    $display .= no_product_message();
    $pager['found_rows'] = 0;
  }

// Some product_list types need dynamically generated titles and subtitles
if ($_GET['type'] == 'subcategory')
  {
    $page_title_html = '<span class="title">Products</span>';
    $page_subtitle_html = '<span class="subtitle">'.$subcategory_name.' Subcategory</span>';
    $page_title = 'Products: '.$subcategory_name.' Subcategory';
    $page_tab = 'shopping_panel';
  }
elseif ($_GET['producer_id'] || strpos($_SERVER['SCRIPT_NAME'],'producers'))
  {
    $page_title_html = '<span class="title">Products</span>';
    $page_subtitle_html = '<span class="subtitle">'.$business_name.'</span>';
    $page_title = 'Products: '.$business_name;
    $page_tab = 'shopping_panel';
  }

$content_list = '
<div id="listing_auth_type">
  <h3>';
foreach (array ("retail"=>"Listed Retail", "wholesale"=>"Listed Wholesale", "unlisted"=>"Unlisted", "archived"=>"Archived") as $key=>$value)
  {
    if ($_REQUEST['a'] == $key)
      {
        $content_list .= $value.' ';
        $this_edit = $value;
      }
    else
      {
        $content_list .= '[<a href="producer_product_list.php?a='.$key.'">'.$value.'</a>] ';
      }
  }
$content_list .= '</h3>';

if ($show_search) $search_display = '
  <form action="'.$_SERVER['SCRIPT_NAME'].'" method="get">'.
    ($_REQUEST['a'] ? '<input type="hidden" name="a" value="'.$_REQUEST['a'].'">' : '').
    '<input type="text" name="query" value="'.$search_query.'">
    <input type="submit" name="type" value="search">
  </form>';


if (isset ($pager['found_rows']))
  {
    $search_display .= '
      <span class="found_rows">Found '.$pager['found_rows'].' '.Inflect::pluralize_if ($pager['found_rows'], 'item').'</span>';
  }
$page_specific_stylesheets['product_list'] = array (
  'name'=>'product_list',
  'src'=>BASE_URL.PATH.'product_list.css',
  'dependencies'=>array('ofs_stylesheet'),
  'version'=>'2.1.1',
  'media'=>'all'
  );
$page_specific_stylesheets['basket_dropdown'] = array (
  'name'=>'basket_dropdown',
  'src'=>BASE_URL.PATH.'basket_dropdown.css',
  'dependencies'=>array('ofs_stylesheet'),
  'version'=>'2.1.1',
  'media'=>'all'
  );

$page_specific_css .= '
#basket_dropdown {
  right:3%;
  }
#content_top {
  margin-bottom:25px;
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
  #simplemodal-container a.modalCloseImg {
    background:url('.DIR_GRAPHICS.'/simplemodal_x.png) no-repeat; /* adjust url as required */
    width:25px;
    height:29px;
    display:inline;
    z-index:3200;
    position:absolute;
    top:0px;
    right:0px;
    cursor:pointer;
  }
.pager a {
  width:'.($pager['last_page'] == 0 ? 0 : number_format(72/$pager['last_page'],2)).'%;
  }
.estimate {
  color:#a00;
  font-style:italic;
  }
.basket_total {
  text-align:right;
  }
.total_label {
  font-weight:bold;
  }
.total_message {
  display:block;
  font-size:70%;
  }
.estimate_message {
  display:block;
  color:#a00;
  font-size:70%;
  }
.prior_order {
  display:block;
  width:100%;
  margin-top:1em;
  text-align:center;
  font-size:70%;
  color:#000;
  }
.product_list {
  clear:both;
  }
#delivery_id_nav {
  margin: 5px auto 0;
  max-width: 40rem;
  text-align:center;
  }
#delivery_id_nav .delivery_id {
  display:block;
  font-weight:bold;
  background-color:#464;
  color:#fff;
  border-radius:10px;
  }
#delivery_id_nav .prior,
#delivery_id_nav .next,
#delivery_id_nav .delivery_id {
  display: inline-block;
  line-height: 1.5;
  padding: 0 15px;
  }';

$page_specific_javascript .= '
var add_to_cart_array = [];
function AddToCart (product_id, product_version, action) {
  var elem;
  var message = "";
  if (elem = document.getElementById("message"+product_id)) message = elem.value;
  var basket_id = "";
  if (elem = document.getElementById("basket_id")) basket_id = elem.value;
  var member_id = "";
  if (elem = document.getElementById("member_id")) member_id = elem.value;
  var delivery_id = "";
  if (elem = document.getElementById("delivery_id")) delivery_id = elem.value;
  jQuery.post("'.PATH.'ajax/'.$template_type.'.php", {
    product_id:product_id,
    product_version:product_version,
    action:action,
    message:message,
    basket_id:basket_id,
    member_id:member_id,
    delivery_id:delivery_id
    },
  function(data) {
    // If site is being inferred from a prior order, then notify of the assumption
    if (data.substr(0,16) == "site_id reverted") {
      popup_src(\''.PATH.'select_delivery_popup.php?after_select=close_and_add(parent.add_to_cart_array)#target_site\', \'select_delivery\', \'\');
      // Set the requested product information so we can re-request it after the basket is handled
      add_to_cart_array = [product_id, product_version, action];
      return false;
      }
    // If no site can be determined, then popup a window to set it.
    if (data == "site_id not set") {
      popup_src(\'select_delivery_popup.php?after_select=close_and_add(parent.add_to_cart_array)#target_site\', \'select_delivery\', \'\');
      // Set the requested product information so we can re-request it after the basket is handled
      add_to_cart_array = [product_id, product_version, action];
      return false;
      }
    var returned_array = data.split(":");
    var new_quantity = returned_array[0];
    var new_inventory = returned_array[1];
    var checked_out = returned_array[2];
    var alert_text = returned_array[3];
    if (document.getElementById("basket_qty" + product_id))
      {
        document.getElementById("basket_qty" + product_id).innerHTML = new_quantity;
      }
    // Update the number available
    if (document.getElementById("available" + product_id))
      {
        document.getElementById("available" + product_id).innerHTML = new_inventory;
      }
    // Show/hide the basket controls
    if (new_quantity > 0 && document.getElementById("add" + product_id)) // The item is in the basket
      {
        if (document.getElementById("available" + product_id) && new_inventory == 0)
          {
            document.getElementById("add" + product_id).style.display = "none";
          }
        else
          {
            document.getElementById("add" + product_id).style.display = "";
          }
        document.getElementById("sub"+product_id).style.display = "";
        document.getElementById("basket_empty"+product_id).style.display = "none";
        document.getElementById("basket_full"+product_id).style.display = "";
        document.getElementById("in_basket"+product_id).style.display = "";
        if (elem = document.getElementById("message_area"+product_id)) elem.style.display = "";
      }
    else if (document.getElementById("add"+product_id) || document.getElementById("sub"+product_id)) // The item is not in the basket
      {
        document.getElementById("add"+product_id).style.display = "none";
        document.getElementById("sub"+product_id).style.display = "none";
        document.getElementById("basket_empty"+product_id).style.display = "";
        document.getElementById("basket_full"+product_id).style.display = "none";
        document.getElementById("in_basket"+product_id).style.display = "none";
        document.getElementById("message_area"+product_id).style.display = "none";
      }
    if (checked_out == 1) {
      document.getElementById("checkout"+product_id).innerHTML = "<input type=\"image\"class=\"checkout_check\" src=\"'.DIR_GRAPHICS.'checkout-ccs.png\" onclick=\"AddToCart("+product_id+","+product_version+",\'no_checkout\'); return false;\"><span class=\"checkout_text\">Ordered!</span>";
      document.getElementById("message_button"+product_id).innerHTML = "";
      document.getElementById("activity"+product_id).innerHTML = "";
      }
    else {
      }

    if (alert_text && alert_text.length > 1) {
      // Uncomment the following line to show alerts
      alert (alert_text);
      }
    });
  }

// This function will close the select_delivery_popup and add the requested item to the cart
function close_and_add (add_to_cart_array) {
  jQuery.modal.close();
  AddToCart (add_to_cart_array[0], add_to_cart_array[1], add_to_cart_array[2]);
  }


function SetItem (bpid, action) {
  var elem;
  if (elem = document.getElementById("ship_quantity"+bpid)) var ship_quantity = elem.value;
  if (elem = document.getElementById("weight"+bpid)) var weight = elem.value;
  // Give user indication the function is running
  if (action == "set_quantity") {
    document.getElementById("ship_quantity"+bpid).style.color = "#f80";
    }
  if (action == "set_weight") {
    document.getElementById("weight"+bpid).style.color = "#f80";
    }
  jQuery.post("'.PATH.'ajax/producer_basket.php", {
    bpid:bpid,
    ship_quantity:ship_quantity,
    weight:weight,
    action:action
    },
  function(data) {
    // Function returns [producer_adjusted_cost]:[extra_charge] OR [ERROR:alert_message]
    var returned_array = data.split(":");
    if (returned_array[0] == "ERROR") {
      alert (returned_array[1]);
      }
    else {
      var producer_adjusted_cost = returned_array[0];
      var extra_charge = returned_array[1];
      var shipped = returned_array[2];
      var total_weight = returned_array[3];
      if (elem = document.getElementById("producer_adjusted_cost"+bpid)) elem.innerHTML = producer_adjusted_cost;
      if (elem = document.getElementById("extra_charge"+bpid)) elem.innerHTML = extra_charge;
      }
    if (action == "set_quantity" && (elem = document.getElementById("ship_quantity"+bpid))) {
      elem.style.color = "#000";
      elem.value = shipped;
      // now also set the weight...
      action = "set_weight";
      }
    if (action == "set_weight" && (elem = document.getElementById("weight"+bpid))) {
      elem.style.color = "#000";
      elem.value = total_weight;
      }
    });
  return false;
  }
// This function is for updating product list images from the image upload screen
function update_image (product_id, product_version, new_image_id) {
  var get_image_src = "'.get_image_path_by_id('XXX').'";
  if (new_image_id == 0) { // Remove the image
    jQuery("#image-"+product_id+"-"+product_version).attr("src", "'.DIR_GRAPHICS.'no_image_set.png");
    jQuery("#image-"+product_id+"-"+product_version).attr("class", "no_product_image");
    }
  else { // Switch the image
    jQuery("#image-"+product_id+"-"+product_version).attr("src", get_image_src.replace("XXX", new_image_id));
    jQuery("#image-"+product_id+"-"+product_version).attr("class", "product_image");
    }
  }
';

$csv_link = '
  <!-- <br><a href="'.$_SERVER['REQUEST_URI'].'&csv=true">Download full list as a CSV file</a> -->
  ';

$content_list = 
  ($content_top ? '
    <div id="content_top">
    '.$content_top.'
    </div>' : '').'
  <div class="product_list">
    '.($message ? '<b><font color="#770000">'.$message.'</font></b>' : '').
    $search_display.
    $producer_display.         // Only set for pages needing producer info
    $order_cycle_navigation_display.
    $pager_navigation_display.
    $display.
    $pager_navigation_display.
    $csv_link.'
  </div>
';

// $page_title_html = [value set dynamically]
// $page_subtitle_html = [value set dynamically]
// $page_title = [value set dynamically]
// $page_tab = [value set dynamically]

if ($_GET['output'] == 'csv')
  {
    header('Content-Type: text/csv');
    header('Content-disposition: attachment;filename=Product_List.csv');
    echo $display;
  }
elseif ($_GET['output'] == 'pdf')
  {
    // DISPLAY NOTHING
  }
else
  {
    include("template_header.php");
    echo '
      <!-- CONTENT BEGINS HERE -->'.
      $content_list.'
      <!-- CONTENT ENDS HERE -->';
    include("template_footer.php");
  }
