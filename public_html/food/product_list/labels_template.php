<?php

/*******************************************************************************

NOTES ON USING THIS TEMPLATE FILE...

The heredoc convention is used to simplify quoting.
The noteworthy point to remember is to escape the '$' in
variable names.  But functions pass through as expected.

The short php if-else format is also useful in this context
for inline display (or not) of content elements:
([condition] ? [true] : [false])

All variables in this file are loaded at include-time and interpreted later
so there is no required ordering of the assignments.

All system constants from the configuration file are available to this template




********************************************************************************
Model for the overall product list display might look something like this:

 -- OVERALL PRODUCT LIST ----------------
|                                        |
|     ----- NAVIGATION SECTION -----     |
|    |                              |    |
|     ------------------------------     |
|     -- PRODUCT HEADING SECTION ---     |
|    |                              |    |
|     - PRODUCT SUBHEADING SECTION -     |
|    |                              |    |
|     -- PRODUCT LISTING SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     - PRODUCT SUBHEADING SECTION -     |
|    |                              |    |
|     -- PRODUCT LISTING SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     ----- NAVIGATION SECTION -----     |
|    |                              |    |
|     ------------------------------     |
|                                        |
 ----------------------------------------

*/

/********************** MISC MARKUP AND CALCULATIONS *************************/

function wholesale_text_html()
  { return
    '<span class="wholesale_notice">** FEATURED WHOLESALE ITEM **</span>';
  };

function no_product_message()
  { return
    '<h2>No products sold</h2>';
  };

// RANDOM_WEIGHT_DISPLAY_CALC
function random_weight_display_calc($data)
  { return
    ($data['random_weight'] == 1 ?
    '<span class="random_weight_display">You will be billed for exact '.$row['meat_weight_type'].' weight ('.
    ($data['minimum_weight'] == $data['maximum_weight'] ?
    $data['minimum_weight'].' '.Inflect::pluralize_if ($data['minimum_weight'], $data['pricing_unit'])
    :
    'between '.$data['minimum_weight'].' and '.$data['maximum_weight'].' '.Inflect::pluralize ($data['pricing_unit'])).')</span>'
    :
    '');
  };

// TOTAL_DISPLAY_CALC
function total_display_calc($data)
  { return
    ($data['unit_price']  != 0 ? '<span id="producer_adjusted_cost'.$data['bpid'].'">$&nbsp;'.number_format($data['producer_adjusted_cost'], 2).'</span>' : '').
    ($data['unit_price'] != 0 && $data['extra_charge'] != 0 ? '<br>' : '').
    ($data['extra_charge'] != 0 ? '<span id="extra_charge'.$data['bpid'].'">'.($data['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format($data['basket_quantity'] * abs($data['extra_charge']), 2).'</span>' : '');
  };

// PRICING_DISPLAY_CALC
function pricing_display_calc($data)
  {
    $pricing_display_calc = '';
    return $pricing_display_calc;
  };

// ORDERING_UNIT_DISPLAY_CALC
function ordering_unit_display_calc($data)
  {
    $ordering_unit_display = '
              <span class="ordering_unit_display">Order number of '.Inflect::pluralize ($data['ordering_unit']).'.</span>';
    return $ordering_unit_display;
  };

// INVENTORY_DISPLAY_CALC
function inventory_display_calc($data)
  {
    $inventory_display =
    ($data['inventory_id'] ?
    '<span class="inventory_display"><span id="available-'.$data['product_id'].'-'.$data['product_version'].'">'.($data['inventory_quantity'] == 0 ? 'No' : $data['inventory_quantity']).'</span> more <!--'.Inflect::pluralize_if ($data['inventory_quantity'], $data['ordering_unit']).' -->available.</span>'
    : '');
    return $inventory_display;
  };

// IMAGE_DISPLAY_CALC
function image_display_calc($data)
  { return
    ($data['image_id'] ?
    '<img src="'.get_image_path_by_id ($data['image_id']).'" class="product_image">'
    : '');
  };

// PRODTYPE_DISPLAY_CALC
function prodtype_display_calc($data)
  { return
    ($data['prodtype_id'] == 5 ? '' : $data['prodtype']).
    (strtolower ($_SESSION['producer_id_you']) == $data['producer_id'] ? '<br />['.$data['storage_code'].']' : '');
  };

// BUSINESS_NAME_DISPLAY_CALC
function business_name_display_calc($data)
  {
    $business_name_display = '
    <a href="'.PATH.'producers/'.$data['producer_link'].'">'.$data['business_name'].'</a>';
    return $business_name_display;
  };

// PRODUCT_MANAGING_CALC
function manage_inventory_control_calc($data, &$unique)
  {
    $product_filling_control = '';
    return $product_filling_control;
  };

// PRODUCT_FILLING_CALC
function manage_filling_control_calc($data, &$unique)
  {
    $product_filling_control = '';
    return $product_filling_control;
  };

/************************* PAGER NAVIGATION SECTION ***************************/

function pager_navigation($data, &$unique, $pager_id='product_list_pager')
  {
    if (! isset ($unique['pager_this_page'])) $unique['pager_this_page'] = 1;
    $pager_navigation =
    ($unique['pager_last_page'] > 1 ? // Show the pager if there is more than one page to show
    '<form id="'.$pager_id.'" name="'.$pager_id.'" action="'.$_SERVER['SCRIPT_NAME'].'" method="GET">'.
    (strlen ($_GET['category_id']) > 0 ? '<input type="hidden" name="category_id" value="'.$_GET['category_id'].'">' : '').
    (strlen ($_GET['delivery_id']) > 0 ? '<input type="hidden" name="delivery_id" value="'.$_GET['delivery_id'].'">' : '').
    (strlen ($_GET['display_as']) > 0 ? '<input type="hidden" name="display_as" value="'.$_GET['display_as'].'">' : '').
    // page is handled below
    (strlen ($_GET['producer_id']) > 0 ? '<input type="hidden" name="producer_id" value="'.$_GET['producer_id'].'">' : '').
    (strlen ($_GET['producer_link']) > 0 ? '<input type="hidden" name="producer_link" value="'.$_GET['producer_link'].'">' : '').
    (strlen ($_GET['product_id']) > 0 ? '<input type="hidden" name="product_id" value="'.$_GET['product_id'].'">' : '').
    (strlen ($_GET['query']) > 0 ? '<input type="hidden" name="query" value="'.$_GET['query'].'">' : '').
    (strlen ($_GET['select_type']) > 0 ? '<input type="hidden" name="select_type" value="'.$_GET['select_type'].'">' : '').
    (strlen ($_GET['sort_type']) > 0 ? '<input type="hidden" name="sort_type" value="'.$_GET['sort_type'].'">' : '').
    (strlen ($_GET['subcat_id']) > 0 ? '<input type="hidden" name="subcat_id" value="'.$_GET['subcat_id'].'">' : '').
    (strlen ($_GET['type']) > 0 ? '<input type="hidden" name="type" value="'.$_GET['type'].'">' : '').
    ($unique['pager_this_page'] ? '
      <div id="'.$pager_id.'_container" class="pager">
        <span class="button_position">
          <div id="'.$pager_id.'_decrement" class="pager_decrement" onclick="decrement_pager(jQuery(this).closest(\'form\').attr(\'id\'));"><span>&ominus;</span></div>
        </span>
        <input type="hidden" id="'.$pager_id.'_slider_prior" value="'.$unique['pager_this_page'].'">
        <span class="pager_center">
          <input type="range" id="'.$pager_id.'_slider" class="pager_slider" name="page" min="1" max="'.$unique['pager_last_page'].'" step="1" value="'.$unique['pager_this_page'].'" onmousemove="update_pager_display(jQuery(this).closest(\'form\').attr(\'id\'));" onchange="goto_pager_page(jQuery(this).closest(\'form\').attr(\'id\'));">
        </span>
        <span class="button_position">
          <div id="'.$pager_id.'_increment" class="pager_increment" onclick="increment_pager(jQuery(this).closest(\'form\').attr(\'id\'));"><span>&oplus;</span></div>
        </span>
      </div>
      <output id="'.$pager_id.'_display_value" class="pager_display_value">Page '.$unique['pager_this_page'].'</output>
    </form>' : '').'
    <div class="clear"></div>'
    : '');
    return $pager_navigation;
  };

/************************* SORTING NAVIGATION SECTION ***************************/

function sorting_navigation($data, &$unique)
  {
    $sorting_navigation = '
        <form id="sort_option" class="hidden" name="sort_option" action="'.$_SERVER['SCRIPT_NAME'].'" method="GET">'.
          (strlen ($_GET['category_id']) > 0 ? '<input type="hidden" name="category_id" value="'.$_GET['category_id'].'">' : '').
          (strlen ($_GET['delivery_id']) > 0 ? '<input type="hidden" name="delivery_id" value="'.$_GET['delivery_id'].'">' : '').
          (strlen ($_GET['display_as']) > 0 ? '<input type="hidden" name="display_as" value="'.$_GET['display_as'].'">' : '').
          (strlen ($_GET['page']) > 0 ? '<input type="hidden" name="page" value="1">' : ''). // Always start on page 1
          (strlen ($_GET['producer_id']) > 0 ? '<input type="hidden" name="producer_id" value="'.$_GET['producer_id'].'">' : '').
          (strlen ($_GET['producer_link']) > 0 ? '<input type="hidden" name="producer_link" value="'.$_GET['producer_link'].'">' : '').
          (strlen ($_GET['product_id']) > 0 ? '<input type="hidden" name="product_id" value="'.$_GET['product_id'].'">' : '').
          (strlen ($_GET['query']) > 0 ? '<input type="hidden" name="query" value="'.$_GET['query'].'">' : '').
          (strlen ($_GET['select_type']) > 0 ? '<input type="hidden" name="select_type" value="'.$_GET['select_type'].'">' : '').'
          <!-- BUTTONS FOR SORTING THE SELECTION -->
          <div class="sort_title">Select a sorting option</div>
            <input class="sort_type_radio" type="radio" id="sort_type_storage_customer" name="sort_type" value="storage_customer"'.($unique['sort_type'] == 'storage_customer' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_storage_customer">Label per Customer<span class="detail">Multiple Products per Label</span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_product" name="sort_type" value="product"'.($unique['sort_type'] == 'product' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_product">Label per Product<span class="detail">One Label per Product</span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_product_multiple" name="sort_type" value="product_multiple"'.($unique['sort_type'] == 'product_multiple' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_product_multiple">Label per Item<span class="detail">Multiple Labels for Quantities</span></label>'.
          (strlen ($_GET['subcat_id']) > 0 ? '<input type="hidden" name="subcat_id" value="'.$_GET['subcat_id'].'">' : '').
          (strlen ($_GET['type']) > 0 ? '<input type="hidden" name="type" value="'.$_GET['type'].'">' : '').'
        </form>';
    return $sorting_navigation;
  }

/*********************** ORDER CYCLE NAVIGATION SECTION *************************/

function order_cycle_navigation($data, &$unique)
  {
    // Set up the previous/next order cycle (delivery_id) navigation
    $current_delivery_id = (isset ($_GET['delivery_id']) ? $_GET['delivery_id'] : ActiveCycle::delivery_id());
    $producer_current_delivery_id_key = array_search ($current_delivery_id, array_keys ($_SESSION['producer_delivery_id_array']))
      or $producer_current_delivery_id_key = count ($_SESSION['producer_delivery_id_array']); // Ordinal for the last array entry
    // Check if the producer had a prior basket
    if ($current_delivery_id > array_keys ($_SESSION['delivery_id_array'])[0]
        && $current_delivery_id > array_keys ($_SESSION['producer_delivery_id_array'])[0])
      {
        $producer_prior_delivery_id = array_keys ($_SESSION['producer_delivery_id_array'])[$producer_current_delivery_id_key - 1];
        $producer_prior_label = '&larr; PRIOR ORDER';
      }
    // Check if the producer has a next basket
    if ($current_delivery_id < array_reverse (array_keys ($_SESSION['delivery_id_array']))[0]
        && $current_delivery_id < array_reverse (array_keys ($_SESSION['producer_delivery_id_array']))[0])
      {
        $producer_next_delivery_id = array_keys ($_SESSION['producer_delivery_id_array'])[$producer_current_delivery_id_key + 1];
        $producer_next_label = 'NEXT ORDER &rarr;';
      }
    elseif ($current_delivery_id < ActiveCycle::delivery_id())
      {
        // Always allow going to the current cycle, even when there are no products to show
        $producer_next_delivery_id = ActiveCycle::delivery_id();
        $producer_next_label = 'CURRENT ORDER &rarr;';
      }
    $next_delivery_id = '';
    $order_cycle_navigation .= '
      <form id="delivery_id_nav" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">'.
        (isset ($producer_prior_delivery_id) ? '
          <button class="prior" name="delivery_id" value="'.$producer_prior_delivery_id.'">'.$producer_prior_label.'</button>'
        : ''
        ).'
        <span class="delivery_id">'.date (DATE_FORMAT_CLOSED, strtotime($_SESSION['delivery_id_array'][$current_delivery_id])).'</span>'.
        (isset ($producer_next_delivery_id) ? '
          <button class="next" name="delivery_id" value="'.$producer_next_delivery_id.'">'.$producer_next_label.'</button>'
        : ''
        ).
        (strlen ($_GET['category_id']) > 0 ? '<input type="hidden" name="category_id" value="'.$_GET['category_id'].'">' : '').
        // delivery_id handled above
        (strlen ($_GET['display_as']) > 0 ? '<input type="hidden" name="display_as" value="'.$_GET['display_as'].'">' : '').'
        <input type="hidden" name="page" value="1">'. // Force page=1 when switching cycles
        (strlen ($_GET['producer_id']) > 0 ? '<input type="hidden" name="producer_id" value="'.$_GET['producer_id'].'">' : '').
        (strlen ($_GET['producer_link']) > 0 ? '<input type="hidden" name="producer_link" value="'.$_GET['producer_link'].'">' : '').
        (strlen ($_GET['product_id']) > 0 ? '<input type="hidden" name="product_id" value="'.$_GET['product_id'].'">' : '').
        (strlen ($_GET['query']) > 0 ? '<input type="hidden" name="query" value="'.$_GET['query'].'">' : '').
        (strlen ($_GET['select_type']) > 0 ? '<input type="hidden" name="select_type" value="'.$_GET['select_type'].'">' : '').
        (strlen ($_GET['sort_type']) > 0 ? '<input type="hidden" name="sort_type" value="'.$_GET['sort_type'].'">' : '').
        (strlen ($_GET['subcat_id']) > 0 ? '<input type="hidden" name="subcat_id" value="'.$_GET['subcat_id'].'">' : '').
        (strlen ($_GET['type']) > 0 ? '<input type="hidden" name="type" value="'.$_GET['type'].'">' : '').'
      </form>';
    return $order_cycle_navigation;
  };

/*********************** LABEL DIVISION OPEN/CLOSE *************************/

function open_list_top($data, &$unique)
  {
    $product_list_pager_top = pager_navigation ($data, $unique, 'product_list_pager_top');
    $sorting_navigation = sorting_navigation ($data, $unique);
    $order_cycle_navigation = order_cycle_navigation($data, $unique);
    $list_open = '
      <input type="hidden" id="delivery_id" value="'.$unique['delivery_id'].'">
      <div id="sort_option_reveal" onclick="$(\'#sort_option\').toggleClass(\'hidden\');">Show/Hide Search Options</div>
      <div class="list_navigation">'.
        $order_cycle_navigation.
        $sorting_navigation.
        $product_list_pager_top.'
      </div>
      <div id="product_list_table" class="producer_basket">';
    return $list_open;
  };

function close_list_bottom()
  {
    $product_list_pager_bottom = '
      <div class="no_print">
        * NOTE: Flagged products have a message from the customer.
      </div>';
    return $product_list_pager_bottom;
  };

/************************** MAJOR DIVISION OPEN/CLOSE ****************************/

function major_division_open($data, &$unique, $major_division = NULL)
  {
    switch ($major_division)
      {
        // Major division on category
        case 'category_id':
        case 'category_name':
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '';
          break;
        // Major division on producer
        case 'producer_id':
        case 'producer_name':
          $header = '';
          break;
        // Major division on producer
        case 'site_short':
        case 'site_long':
          $header = '';
          break;
        // Major division on product
        case 'product_id':
        case 'product_name':
          $header = '
          <div class="label">
            '.$data['hub_short'].'-<b>'.$data['site_short'].'</b> #'.$data['member_id'].' ('.$data['site_long'].') ['.$data['storage_code'].']<br>
            '.$data['preferred_name'].'<br>';
          break;
        // Major division on storage
        case 'storage_code':
        case 'storage_type':
          $header = '
          <div class="label">
            '.$data['hub_short'].'-<b>'.$data['site_short'].'</b> #'.$data['member_id'].' ('.$data['site_long'].') ['.$data['storage_code'].']<br>
            '.$data['preferred_name'].'<br>';
          break;
        // Major division on producer
        case 'member_id':
        case 'preferred_name':
          $header = '
          <div class="label">
            '.$data['hub_short'].'-<b>'.$data['site_short'].'</b> #'.$data['member_id'].' ('.$data['site_long'].') ['.$data['storage_code'].']<br>
            '.$data['preferred_name'].'<br>';
          break;
        // Major division on bpid
        case 'bpid':
          $header = '<div class="label">
            '.$data['hub_short'].'-<b>'.$data['site_short'].'</b> #'.$data['member_id'].' ('.$data['site_long'].') ['.$data['storage_code'].']<br>
            '.$data['preferred_name'].'<br>';
          break;
        // Otherwise...
          $header = '
          ';
          break;
      }
    return $header;
  };

function major_division_close ($data, $unique, $major_division = NULL)
  { return '
            </div>';
  };

/************************** MINOR DIVISION OPEN/CLOSE ****************************/

function minor_division_open($data, &$unique, $minor_division = NULL)
  {
    switch ($minor_division)
      {
        // Minor division on category
        case 'category_id':
        case 'category_name':
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '';
          break;
        // Minor division on producer
        case 'producer_id':
        case 'producer_name':
          $header = '';
          break;
        // Minor division on storage
        case 'storage_code':
        case 'storage_type':
          $header = '
          <div class="label">
            '.$data['hub_short'].'-<b>'.$data['site_short'].'</b> #'.$data['member_id'].' ('.$data['site_long'].') ['.$data['storage_code'].']<br>
            '.$data['preferred_name'].'<br>'.'
            '.$data['producer_name'].'<br>
          </div>';
          break;
        // Minor division on product
        case 'product_id':
        case 'product_name':
          $header = '
          <div class="label">
            '.$data['hub_short'].'-<b>'.$data['site_short'].'</b> #'.$data['member_id'].' ('.$data['site_long'].') ['.$data['storage_code'].']<br>
            '.$data['preferred_name'].'<br>'.'
            '.$data['producer_name'].'<br>
          </div>';
          break;
        // Minor division on member // USED FOR ONE LABEL FOR EACH CUSTOMER/STORAGE COMBINATION (MULTI-ITEMS PER LABEL)
        case 'member_id':
        case 'preferred_name':
          $header = '
          <div class="label">'.
            ($data['availability'] != true ? '<span class="availability_error">'.$data['site_long'].'</span>' : '').'
            <div class="label_header">
              <div class="sorting_info">
                <span class="truck_key">'.$data['truck_code'].'</span>
                <span class="site_key">'.$data['site_key'].($data['producer_bag_count'] > 1 ? '-'.($data['site_key'] + $data['producer_bag_count'] - 1) : '').'</span>
                <span class="customer_key">'.$data['customer_key'].($data['producer_bag_count'] > 1 ? '-'.($data['customer_key'] + $data['producer_bag_count'] - 1) : '').'</span>
                <span class="producer_key">'.$data['producer_key'].($data['producer_bag_count'] > 1 ? '-'.($data['producer_key'] + $data['producer_bag_count'] - 1) : '').'</span>
              </div>
              <div class="site_info">
                <span class="site_short">'.$data['site_short'].'</span>
                <span class="member_id">'.$data['member_id'].'</span>
                <span class="site_long">'.$data['site_long'].'</span>
                <span class="storage_code">'.$data['storage_code'].'</span>
              </div>
              <div class="customer_info">
                <span class="preferred_name">'.$data['preferred_name'].'</span>
                <span class="member_id">'.$data['member_id'].'</span>
              </div>
              <div class="producer_info">
                <span class="producer_name">'.$data['producer_name'].'</span>
              </div>
            </div>
            <div class="label_product">';
          break;
        // Minor division on bpid // USED FOR ONE LABEL FOR EACH CUSTOMER ITEM (ONE PRODUCT PER LABEL)
        case 'bpid':
          $header = '
          <div class="label">'.
            ($data['availability'] != true ? '<span class="availability_error">'.$data['site_long'].'</span>' : '').'
            <div class="label_header">
              <div class="sorting_info">
                <span class="truck_key">'.$data['truck_code'].'</span>
                <span class="site_key">'.$data['site_key'].'</span>
                <span class="customer_key">'.$data['customer_key'].'</span>
                <span class="producer_key">'.$data['producer_key'].'</span>
                <!-- POSSIBLE_MULTI_LABEL_DATA -->
              </div>
              <div class="site_info">
                <span class="site_short">'.$data['site_short'].'</span>
                <span class="member_id">'.$data['member_id'].'</span>
                <span class="site_long">'.$data['site_long'].'</span>
                <span class="storage_code">'.$data['storage_code'].'</span>
              </div>
              <div class="customer_info">
                <span class="preferred_name">'.$data['preferred_name'].'</span>
                <span class="member_id">'.$data['member_id'].'</span>
              </div>
              <div class="producer_info">
                <span class="producer_name">'.$data['producer_name'].'</span>
              </div>
            </div>
            <div class="label_product">';
          // Keep this header content so we can repeat it n-times for product_multiple listings
          if ($unique['sort_type'] == 'product_multiple'
              && $data['basket_quantity'] > 1)
            {
              $unique['header_content'] = $header; // Save the header display
              $header = ''; // and do not display anything right now
            }
          break;
        // Otherwise...
          $header = '
          ';
          break;
      }
    return $header;
  };

function minor_division_close ()
  {
    $minor_close = '
          </div>
        </div>';
    return $minor_close;
  };

/************************* DISPLAY INDIVIDUAL ROWS CONTENT **************************/

function show_listing_row($data, &$unique)
  {
    switch ($unique['row_type'])
      {
        // Row type
        case 'product_short':
          $row_content = '
            <div class="product_info'.(strlen ($data['message']) > 0 ? ' has_message' : '').($data['out_of_stock'] > 0 ? ' out_of_stock' : '').($data['out_of_stock'] == $data['basket_quantity'] ? ' void' : '').'">
              <span class="product_id">'.$data['product_id'].'</span>
              <span class="product_name">'.$data['product_name'].'</span>
              <div class="fulfillment">
                <span class="basket_quantity">'.$data['basket_quantity'].'</span>
                <span class="ordering_unit">'.Inflect::pluralize_if ($data['basket_quantity'], $data['ordering_unit']).'</span>'.
              ($data['out_of_stock'] > 0 ? '
                <span class="out_of_stock">'.($data['basket_quantity'] - $data['out_of_stock']).'</span>'
              : ''
              ).'
              </div>'.
              (SHOW_CUSTOMER_NOTE_ON_LABEL == true ? '
              <div class="customer_message">'.$data['message'].'</div>'
              : '').'
            </div>';
          break;
        case 'product_multiple':
          // HOW THIS WORKS: in the minor_division display, we trap basket quantities > 1 and don't display anything.
          // Then we combine those minor_division_open data with each show_listing_row and replace the X of Y value
          // before displaying.
          $label_count = 0;
          $row_content = '';
          $shipped_quantity = $data['basket_quantity'] - $data['out_of_stock'];
          // First we handle the case where we just need to print a single regular label
          if ($data['basket_quantity'] == 1)
            {
              $row_content .= '
              <div class="product_info'.(strlen ($data['message']) > 0 ? ' has_message' : '').($data['out_of_stock'] > 0 ? ' out_of_stock' : '').($data['out_of_stock'] == $data['basket_quantity'] ? ' void' : '').'">
                <span class="product_id">'.$data['product_id'].'</span>
                <span class="product_name">'.$data['product_name'].'</span>
                <div class="fulfillment">
                  <span class="basket_quantity">'.$data['basket_quantity'].'</span>
                  <span class="ordering_unit">'.Inflect::pluralize_if ($data['basket_quantity'], $data['ordering_unit']).'</span>'.
                ($data['out_of_stock'] > 0 ? '
                  <span class="out_of_stock">'.($data['basket_quantity'] - $data['out_of_stock']).'</span>'
                : ''
                ).'
                </div>'.
              (SHOW_CUSTOMER_NOTE_ON_LABEL == true ? '
              <div class="customer_message">'.$data['message'].'</div>'
              : '').'
              </div>';
            }
          // And now the case where we print multiple labels, one for each of the quantity
          else
            {
              while ($label_count ++ < $shipped_quantity)
                {
                  $row_content .= str_replace ('<!-- POSSIBLE_MULTI_LABEL_DATA -->', '<span class="bundle_key">'.$label_count.'<span class="bundle_key_max">'.$shipped_quantity.'</span></span>', $unique['header_content']).
                    '
              <div class="product_info'.(strlen ($data['message']) > 0 ? ' has_message' : '').($data['out_of_stock'] > 0 ? ' out_of_stock' : '').($data['out_of_stock'] == $data['basket_quantity'] ? ' void' : '').'">
                <span class="product_id">'.$data['product_id'].'</span>
                <span class="product_name">'.$data['product_name'].'</span>
                <div class="fulfillment">
                  <span class="basket_quantity">'.$data['basket_quantity'].'</span>
                  <span class="ordering_unit">'.Inflect::pluralize_if ($data['basket_quantity'], $data['ordering_unit']).'</span>'.
                ($data['out_of_stock'] > 0 ? '
                  <span class="out_of_stock">'.$shipped_quantity.'</span>'
                : ''
                ).'
                </div>'.
              (SHOW_CUSTOMER_NOTE_ON_LABEL == true ? '
              <div class="customer_message">'.$data['message'].'</div>'
              : '').'
              </div>';
                  if ($label_count != $data['basket_quantity'])
                    {
                      $row_content .= minor_division_close ();
                    }
                }
            }
          break;
        // Row type
        case 'member_short':
          $row_content = '';
          break;
        // Row type
        case 'product_short_site':
          $row_content = '';
          break;
        // Row type
        case 'product':
          $row_content = '';
          break;
        // Otherwise...
          $row_content = '
          ';
          break;
      }
    return $row_content;
  }
