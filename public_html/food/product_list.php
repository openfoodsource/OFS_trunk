<?php
include_once 'config_openfood.php';
session_start();
// Except for this hook, validations are done in the product_list files
// This is used to force reloading the same page after login
if (isset ($_GET['auth_type']) && $_GET['auth_type'] == 'member') valid_auth('member');

// Only customer pages need to select select a site
$is_customer_product_page = false;
if (isset ($_GET['type'])) $product_list_type = $_GET['type'];
if ($product_list_type == 'customer_list'
    || $product_list_type == 'customer_basket')
  {
    $is_customer_product_page = true;
  }

// Sanitize variables that are expected to be numeric
if (isset ($_GET['producer_id'])) $_GET['producer_id'] = preg_replace ('/[^0-9]/', '', $_GET['producer_id']);
if (isset ($_GET['category_id'])) $_GET['category_id'] = preg_replace ('/[^0-9]/', '', $_GET['category_id']);
if (isset ($_GET['subcat_id'])) $_GET['subcat_id'] = preg_replace ('/[^0-9]/', '', $_GET['subcat_id']);

// Items dependent upon the location of this header
$unique = array();

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
    AND '.TABLE_PRODUCER.'.pending = "0"';

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
    AND '.NEW_TABLE_PRODUCTS.'.active = "1"
    AND '.NEW_TABLE_PRODUCTS.'.approved = "1"';

//////////////////////////////////////////////////////////////////////////////////////
//                                                                                  //
//                         QUERY AND DISPLAY THE DATA                               //
//                                                                                  //
//////////////////////////////////////////////////////////////////////////////////////

// Make sure we're looking for a valid list_type
if (isset ($_GET['type']) && file_exists ('product_list/'.$_GET['type'].'.php')) $list_type = $_GET['type'];
else $list_type = 'customer_list';
$unique['list_type'] = $list_type;

// Include the appropriate list "module" from the product_list directory
include_once ('product_list/'.$list_type.'.php');
// Now include the template (specified in the include_file)
include_once ('product_list/'.$template_type.'_template.php');

// This setting might be overridden below or in included files
$unique['pager_per_page'] = PER_PAGE;
// Labels do not have pages
if ($template_type == 'labels') $unique['pager_per_page'] = 1000000;
// Set up the pager for the output
$list_start = ($_GET['page'] - 1) * $unique['pager_per_page'];
if ($list_start < 0) $list_start = 0;
$query_limit = $list_start.', '.$unique['pager_per_page'];


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
$unique['pager_found_rows'] = $row_found_rows['found_rows'];
if ($_GET['page']) $unique['pager_this_page'] = $_GET['page'];
else $unique['pager_this_page'] = 1;
$unique['pager_last_page'] = ceil (($unique['pager_found_rows'] / $unique['pager_per_page']) - 0.00001);
$unique['pager_page'] = 0;
$unique['pager_first_product_on_page'] = (($unique['pager_this_page'] - 1) * PER_PAGE) + 1;
if ($row_found_rows['found_rows'] == 0) $unique['pager_first_product_on_page'] = 0; // When there are zero products to show
$unique['pager_last_product_on_page'] = $unique['pager_this_page'] * PER_PAGE;
if ($unique['pager_last_product_on_page'] > $unique['pager_found_rows'])
  {
    $unique['pager_last_product_on_page'] = $unique['pager_found_rows'];
  }

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
    // Set the unique category/subcategory name for use later
    $unique['category_name'] = $row['category_name'];
    $unique['subcategory_name'] = $row['subcategory_name'];
    $unique['site_long_you'] = $row['site_long_you'];
    $row['display_base_price'] = $display_base_price;
    $row['display_anonymous_price'] = $display_anonymous_price;
    $row['is_wholesale_item'] = $is_wholesale_item;
    $row['order_open'] = $order_open;

    // Open the product list
    if ($first_time_through++ == 0) $display .= open_list_top($row, $unique);

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

    // Transition to specific activity sections
    if (function_exists ('manage_ordering_control_calc'))
        $row['manage_ordering_control'] = manage_ordering_control_calc($row, $unique);
    if (function_exists ('manage_filling_control_calc'))
        $row['manage_filling_control'] = manage_filling_control_calc($row, $unique);
    if (function_exists ('manage_inventory_control_calc'))
        $row['manage_inventory_control'] = manage_inventory_control_calc($row, $unique);
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
            $display .= major_division_close($row, $unique);
            $listing_is_open = 0;
          }
        $display .= major_division_open($row, $unique, $major_division);
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
        $display .= minor_division_open($row, $unique, $minor_division);
      }

    $listing_is_open = 1;
    $display .= show_listing_row($row, $unique);

    // Handle prior values to catch changes
    $$major_division_prior = $row[$major_division];
    $$minor_division_prior = $row[$minor_division];
  }
$unique['completed'] = 'true';
// Close minor if there were any products
if ($unique['product_count'] > 0 && $show_minor_division) $display .= minor_division_close($row, $unique);
// Close major if there were any products
if ($unique['product_count'] > 0 && $show_major_division) $display .= major_division_close($row, $unique);
// Close the product list if there were any products listed
if ($unique['pager_found_rows'] && $unique['product_count'] > 0)
  {
    $display .= close_list_bottom($row, $unique);
  }
// Otherwise send the "nothing to show" message
else
  {
    $display = open_list_top($row, $unique).
      major_division_open($row, $unique, 'major_division_empty_title').
      no_product_message().
      major_division_close($row, $unique, $major_division).
      close_list_bottom($row, $unique);
    $unique['pager_found_rows'] = 0;
  }

// Some product_list types need dynamically generated titles and subtitles
// if ($_GET['type'] == 'subcategory')
//   {
//     $page_title_html = '<span class="title">Products</span>';
//     $page_subtitle_html = '<span class="subtitle">'.$subcategory_name.' Subcategory</span>';
//     $page_title = 'Products in '.$subcategory_name.' Subcategory';
//     $page_tab = 'shopping_panel';
//   }
// elseif ($_GET['producer_id'] || strpos($_SERVER['SCRIPT_NAME'],'producers'))
//   {
//     $page_title_html = '<span class="title">Products</span>';
//     $page_subtitle_html = '<span class="subtitle">'.$business_name.'</span>';
//     $page_title = 'Products from '.$business_name;
//     $page_tab = 'shopping_panel';
//   }

// Set subtitle as category [and?] subcategory
if (isset ($_GET['category_id'])) $subtitle = $unique['category_name'];
if (isset ($_GET['subcat_id'])) $subtitle = $unique['category_name'].' &ndash; '.$unique['subcategory_name'];

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
  position:relative;
  }
/* Styles for detailed producer information */
.producer_details {
  margin-bottom:15px;
  }
.producer_details .producer_section {
  display:block;
  margin-bottom:1rem;
  }
.producer_details .producer_section h4 {
  display:inline-block;
  clear:left;
  margin:0 1rem 0 0;
  }
.producer_details {
  background-color:#fff;
  border-radius:10px;
  border:1px solid #888;
  padding:1rem;
  }
.producer_listing .producer_details a img {
  box-shadow: none;
  float: right;
  height: 150px;
  margin: 0.5rem 0.5rem 1rem 1rem;
  max-width: 50%;
  }
.producer_details .original_questionnaire_link > a {
  box-shadow: none;
  }
.producer_details .original_questionnaire_link {
  display:block;
  text-align:right;
  }';

$page_specific_javascript .= '
// FUNCTIONS TO MANAGE ORDERING

var add_to_cart_array = [];
// This function hides the messages button and shows the messages textarea
function show_message_area (product_id, product_version) {
  $("#message_area-"+product_id+"-"+product_version).removeClass("no_message");
  $("#message_area-"+product_id+"-"+product_version).addClass("has_message");
  $("#message-"+product_id+"-"+product_version).removeClass("no_message");
  $("#message-"+product_id+"-"+product_version).addClass("has_message");
  }

// This function is for checking out individual products from a customer basket. It requires two clicks
// before something can be checked out, changing style between clicks.
function checkout (product_id, product_version, action) {
  if (action == "checkout") {
    if (jQuery("#checkout_item-"+product_id+"-"+product_version).hasClass("warn")) {
      // We have already warned the customer, so do the checkout
      adjust_customer_basket (product_id, product_version, action);
      jQuery("#checkout_item-"+product_id+"-"+product_version).removeClass("warn");
      }
    else {
      // Confirm checkout
      jQuery("#checkout_item-"+product_id+"-"+product_version).addClass("warn");
      }
    }
  else { // Reset the elements to pre-confirmation condition
      jQuery("#checkout_item-"+product_id+"-"+product_version).removeClass("warn");
    }
  }

// Create debounced version for adjust_customer_basket
// NOTE: debounce() is located in javascript.js
var debounced_adjust_customer_basket = debounce(function(product_id, product_version, action) {
  adjust_customer_basket(product_id, product_version, action);
  }, 800);

// Function for adding, subtracting, and setting quantities in a customer basket
function adjust_customer_basket (product_id, product_version, action) {
  var elem;
  var set_quantity = "";
  if (elem = document.getElementById("basket_quantity-"+product_id+"-"+product_version)) {
    set_quantity = elem.value;
    }
  var message = "";
  if (elem = document.getElementById("message-"+product_id+"-"+product_version)) {
    message = elem.value;
    }
  var site_id = "";
  if (elem = document.getElementById("site_id")) {
    site_id = elem.value;
    }
  var basket_id = "";
//  basket_id = jQuery("#basket_id").value;
  if (elem = document.getElementById("basket_id")) {
    basket_id = elem.value;
    }
  var member_id = "";
  if (elem = document.getElementById("member_id")) {
    member_id = elem.value;
    }
  var delivery_id = "";
  if (elem = document.getElementById("delivery_id")) {
    delivery_id = elem.value;
    }
  // site_id will not have a value until a basket has been opened..
  // so go confirm or load a site to use before adding to the cart
  if (! site_id > 0) {
    // Set the requested product information so we can re-request it after the basket is handled
    add_to_cart_array = [product_id, product_version, action];
    popup_src(\'customer_select_site.php?confirm_site=true&completion_action=product_list_and_close();\', \'customer_select_site\', \'\');
    return false;
    }
  else {
    // Now all variable should be set, so go do the requested operation
    jQuery.ajax({
      type: "POST",
      url: "'.PATH.'ajax/manage_ordering.php",
      cache: false,
      data: {
        product_id:product_id,
        product_version:product_version,
        action:action,
        message:message,
        basket_id:basket_id,
        member_id:member_id,
        delivery_id:delivery_id,
        set_quantity:set_quantity
        }
      })
    .done(function(json_basket_item_info) {
      basket_item_info = JSON.parse(json_basket_item_info);
      /* Returns all variables from basket_item_info()
         PLUS:
           basket_item_info.error
           basket_item_info.inventory_pull_quantity
      */
      if (basket_item_info.error.length > 1)
        {
        // Uncomment the following line to show alerts
        alert (basket_item_info.error);
        }
      // Set the basket_id on this page if it is not already set
      if (elem = document.getElementById("basket_id")
          && elem.value == "") {
        elem.value = basket_item_info.basket_id;
        }
      // Assign the quantity that ended up in the basket
      if (document.getElementById("basket_quantity-"+product_id+"-"+product_version))
        {
          document.getElementById("basket_quantity-"+product_id+"-"+product_version).value = basket_item_info.quantity;
        }
      // Update the number available in inventory
      if (document.getElementById("available-"+product_id+"-"+product_version))
        {
          document.getElementById("available-"+product_id+"-"+product_version).innerHTML = basket_item_info.inventory_pull_quantity;
        }
      // Update the to_ship number
      var to_ship = basket_item_info.quantity - basket_item_info.out_of_stock;
      if (document.getElementById("to_ship-"+product_id+"-"+product_version))
        {
          document.getElementById("to_ship-"+product_id+"-"+product_version).innerHTML = to_ship;
        }
      // Update the reserve number
      var reserve = basket_item_info.out_of_stock;
      if (document.getElementById("reserve-"+product_id+"-"+product_version))
        {
          document.getElementById("reserve-"+product_id+"-"+product_version).innerHTML = reserve;
        }
      // Show/hide the to_ship/reserve information
      if (reserve > 0) {
        jQuery("#ship_reserve-"+product_id+"-"+product_version).removeClass("hide");
        }
      else {
        jQuery("#ship_reserve-"+product_id+"-"+product_version).addClass("hide");
        }
      // Show/hide the basket controls
      if (basket_item_info.quantity > 0) // The item is in the basket
        {
          // Product is in basket
          jQuery("#manage_ordering-"+product_id+"-"+product_version).removeClass("empty");
          jQuery("#manage_ordering-"+product_id+"-"+product_version).addClass("full");
          document.getElementById("dec-"+product_id+"-"+product_version).style.visibility = "visible";
          jQuery("#message_area-"+product_id+"-"+product_version).removeClass("hidden");
        }
      else // nothing in basket
        {
          // Product is not in basket
          jQuery("#manage_ordering-"+product_id+"-"+product_version).removeClass("full");
          jQuery("#manage_ordering-"+product_id+"-"+product_version).addClass("empty");
          document.getElementById("dec-"+product_id+"-"+product_version).style.visibility = "hidden";
          jQuery("#message_area-"+product_id+"-"+product_version).addClass("hidden");
        }
      if (basket_item_info.checked_out == 1)
        {
          // Prevent any additional adjust_customer_basket() function
          jQuery("#basket_quantity-"+product_id+"-"+product_version).removeAttr("onkeyup");
          jQuery("#dec-"+product_id+"-"+product_version).removeAttr("onclick");
          jQuery("#inc-"+product_id+"-"+product_version).removeAttr("onclick");
          jQuery("#checkout_item-"+product_id+"-"+product_version).removeClass("checkout");
          jQuery("#checkout_item-"+product_id+"-"+product_version).addClass("checkedout");
          // Hide buttons
          document.getElementById("inc-"+product_id+"-"+product_version).style.visibility = "hidden";
          document.getElementById("dec-"+product_id+"-"+product_version).style.visibility = "hidden";
          jQuery("#checkout_item-"+product_id+"-"+product_version).removeAttr("onclick");
        }
      });
    }
  }

// FUNCTIONS TO MANAGE INVENTORY

// Create debounced version for adjust_inventory
// NOTE: debounce() is located in javascript.js
var debounced_adjust_inventory = debounce(function(access_type, access_key, action, callback) {
  // Default callback
  if (callback === undefined) {
    callback = function(){};
    }
  adjust_inventory(access_type, access_key, action);
  }, 800);

// Function for producers to adjust inventory levels
function adjust_inventory (access_type, access_key, action, callback) {
  // Default callback
  if (callback === undefined) {
    callback = function(){};
    }
  // access_type: [inventory|product]
  // access_key: [inventory_id|product_id-product_version]
  var elem;
  var inventory_pull_quantity = "";
  if (elem = document.getElementById("inventory_pull_quantity-"+ access_key)) inventory_pull_quantity = elem.value;
  var inventory_description = "";
  if (elem = document.getElementById("inventory_description-"+ access_key)) inventory_description = elem.value;
  var inventory_quantity = "";
  if (elem = document.getElementById("inventory_quantity-"+ access_key)) inventory_quantity = elem.value;
  var delivery_id = "";
  if (elem = document.getElementById("delivery_id")) delivery_id = elem.value;
  jQuery.post("'.PATH.'ajax/manage_inventory.php", {
    access_type:access_type,
    access_key:access_key,
    inventory_quantity:inventory_quantity,
    inventory_pull_quantity:inventory_pull_quantity,
    inventory_description:inventory_description,
    delivery_id:delivery_id,
    action:action
    },
  function(returned_data) {
    if (returned_data.substring(0,1) != "{") {
      return (1); // FAIL: Not a JSON response
      }
    var data = JSON.parse(returned_data);
    if (action == "confirm"
        || action == "delete") {
      // Nothing to do upon return from these actions
      return (0);
      }
    if (access_type == "product") {
      if (elem = document.getElementById("inventory_pull_quantity-"+access_key)) elem.value = data["inventory_pull_quantity"];
      if (elem = document.getElementById("bucket_quantity-"+access_key)) elem.innerHTML = data["bucket_quantity"];
      if (elem = document.getElementById("ordered_quantity-"+access_key)) elem.innerHTML = data["ordered_quantity"] + 0;
      }
    else if (access_type == "inventory") {
      if (elem = document.getElementById("inventory_quantity-"+access_key)) elem.value = data["inventory_quantity"];
      if (elem = document.getElementById("ordered_quantity-"+access_key)) elem.innerHTML = data["ordered_quantity"] + 0;
      if (data["error"] == 100) { // Duplicate inventory description (ask to combine?)
        var initiate_combine = confirm ("The requested inventory bucket name is already in use. Selecting \"OK\" will combine products into a common inventory bucket and reload this page.");
        if (initiate_combine == true) {
          if (elem = document.getElementById("inventory_description-"+access_key)) elem.value = data["inventory_description"];
          adjust_inventory (access_type, access_key, "combine",
            function () {
              alert ("Time to reload");
              location.reload();
              }
            )
          }
        else {
          if (elem = document.getElementById("inventory_description-"+access_key)) elem.value = data["old_inventory_description"];
          }
        }
      else if (data["error"] == 200) { // Empty inventory description (ask to delete?)
        var initiate_delete = confirm ("Selecting \"OK\" will DELETE the current inventory bucket, release all of its products from using inventory, and reload this page.");
        if (initiate_delete == true) {
          if (elem = document.getElementById("inventory_description-"+access_key)) elem.value = "";
          adjust_inventory (access_type, access_key, "delete",
            function () {
              alert ("Time to reload");
              location.reload();
              }
            )
          }
        else {
          if (elem = document.getElementById("inventory_description-"+access_key)) elem.value = data["old_inventory_description"];
          }
        }
      }
    if (data["alert_text"] && data["alert_text"].length > 1) {
      // Uncomment the following line to show alerts
      // alert (data["alert_text"]);
      }
    });
  callback ();
  }

// FUNCTIONS TO MANAGE FILLING ORDERS

// Create debounced version for set_item
// NOTE: debounce() is located in javascript.js
var debounced_set_item = debounce(function(bpid, action) {
  set_item(bpid, action);
  }, 1800);

/* This function allows producers to adjust shipped quantities and variable weights */
function set_item (bpid, action) {
  var elem;
  if (elem = document.getElementById("ship_quantity-"+bpid)) var ship_quantity = elem.value;
  if (elem = document.getElementById("weight-"+bpid)) var weight = elem.value;
  // Give user indication the function is running
  if (action == "set_outs") {
    $("#ship_quantity-"+bpid).addClass("active");
    }
  if (action == "set_weight") {
    $("#weight-"+bpid).addClass("active");
    }
  jQuery.post("'.PATH.'ajax/manage_filling.php", {
    bpid:bpid,
    ship_quantity:ship_quantity,
    weight:weight,
    action:action
    },
  function(returned_data) {
    var data = JSON.parse(returned_data);
    if (elem = document.getElementById("producer_adjusted_cost-"+bpid)) elem.innerHTML = data["producer_adjusted_cost"];
    if (elem = document.getElementById("extra_charge-"+bpid)) elem.innerHTML = data["extra_charge"];

    if (elem = document.getElementById("ship_quantity-"+bpid)) {
      elem.value = data["shipped"];
      $("#ship_quantity-"+bpid).removeClass("active");
      }
    if (elem = document.getElementById("weight-"+bpid)) {
      elem.style.color = "#000";
      elem.value = data["total_weight"];
      $("#weight-"+bpid).removeClass("active");
      if (data["total_weight"] > 0) {
        $("#estimate_text-"+bpid).css("display", "none");
        }
      }
    });
  return false;
  }

// FUNCTIONS TO MANAGE PRODUCTS

/* This function is for updating product list images from the image upload screen */
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

/* This function is for deleting products. It requires two clicks to execute, changing style between */
function delete_product (product_id, product_version, action, deletion_type) {
  if (action == "delete") {
    if (jQuery("#delete_product-"+product_id+"-"+product_version).hasClass("warn")) {
      jQuery.post("'.PATH.'ajax/delete_product.php", {
        product_id:product_id,
        product_version:product_version,
        deletion_type:deletion_type
        },
      function(data) {
        // Function returns [SUCCESS] OR [ERROR:alert_message]
        var returned_array = data.split(":");
        if (returned_array[0] == "SUCCESS") {
          // If deleted, then hide the product/version
          jQuery("#product_display-"+product_id+"-"+product_version).addClass("delete");
          }
        else if (returned_array[0] == "ERROR") {
          alert (returned_array[1]);
          }
        });
      jQuery("#delete_product-"+product_id+"-"+product_version).removeClass("warn");
      }
    else {
      jQuery("#delete_product-"+product_id+"-"+product_version).addClass("warn");
      }
    }
  }';

$content_list = '
  <div class="product_list">'.
    $display.'
  </div>';

// $page_title_html = [value set dynamically]
$page_subtitle_html = '
  <span class="subtitle">'.$subtitle.
    (strlen ($search_order_by) > 0 ? '
      <span class="subtitle_search">'.$search_query.'</span>'
    : '').
    (strlen ($unique['site_long_you']) > 0 ? '
      <span class="subtitle_site" title="Change this?" onclick="popup_src(\''.BASE_URL.PATH.'customer_select_site.php?display_as=popup\', \'customer_select_site\', \'\');">'.$unique['site_long_you'].'</span>'
    : '').'
  </span>';
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
