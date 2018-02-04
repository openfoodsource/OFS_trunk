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
function total_display_calc($data, &$unique)
  {
    // Random weight w/o weight gets a special note
    if ($data['weight_needed'] == true)
      {
        $estimate_text = '<span id="estimate_text-'.$data['bpid'].'" class="estimate">estimated '.RANDOM_CALC.'</span>';
        $unique['weight_needed'] = true;
      }

    if ($data['unit_price']  != 0) $total_display = $estimate_text.'$&nbsp;<span id="producer_adjusted_cost-'.$data['bpid'].'">'.number_format($data['display_adjusted_producer_price'], 2).'</span>';
    if ($data['unit_price'] != 0 && $data['extra_charge'] != 0) $total_display .= '<br />';
    if ($data['extra_charge'] != 0) $total_display .= '<span id="extra_charge-'.$data['bpid'].'">'.($data['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format($data['basket_quantity'] * abs($data['extra_charge']), 2).'</span>';
    $unique['running_total'] = $unique['running_total'] + $data['customer_adjusted_cost'] + ($data['basket_quantity'] * $data['extra_charge']);
    return $total_display;
  };

// PRICING_DISPLAY_CALC
function pricing_display_calc($data)
  {
    $pricing_display_calc = '';
    $per_pricing_unit = ' / ';
    if (preg_match ('/[0-9]+.*/', $data['pricing_unit'])) $per_pricing_unit = ' per ';
    $per_ordering_unit = ' / ';
    if (preg_match ('/[0-9]+.*/', $data['ordering_unit'])) $per_ordering_unit = ' per ';
    if ($data['display_base_customer_price'])
      {
        $pricing_display_calc .= '
          <span class="basket_price">$'.number_format($data['display_base_customer_price'], 2).$per_pricing_unit.Inflect::singularize ($data['pricing_unit']).'</span>';
      }
    if (strlen ($pricing_display_calc) > 0 &&
        $data['extra_charge'] != 0)
      {
        $pricing_display_calc .= ($data['extra_charge'] > 0 ? '
        <span class="combine_price">plus</span>' : '
        <span class="combine_price">minus</span>').'
        <span class="extra_price">&nbsp;$'.number_format (abs ($data['extra_charge']), 2).$per_ordering_unit.Inflect::singularize ($data['ordering_unit']).'</span>';
      }
    elseif ($data['extra_charge'] != 0)
    {
      $pricing_display_calc .= '
        <span class="extra_price">'.($data['extra_charge'] > 0 ? '' : '- ').'$'.number_format (abs ($data['extra_charge']), 2).$per_ordering_unit.Inflect::singularize ($data['ordering_unit']).'</span>';
    }
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
  {
    $image_display = '
              <figure class="product_image">'.
                ($data['image_id'] ? '
                <figcaption class="edit_product_image" onclick="popup_src(\''.get_image_path_by_id ($data['image_id']).'\', \'product_image\', \'\', false);">Click for Larger Image</figcaption>
                <img id="image-'.$data['product_id'].'-'.$data['product_version'].'" src="'.get_image_path_by_id ($data['image_id']).'" class="product_image" onclick="popup_src(\'set_product_image.php?display_as=popup&action=select_image&product_id='.$data['product_id'].'&product_version='.$data['product_version'].'\', \'edit_product_image\', \'\', false);">'
                : ''
                ).'
              </figure>';
    return $image_display;
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
    $manage_inventory_control = '
          <div class="manage_inventory control_block">
            <form class="manage_inventory">
              <span class="current_orders">
                <span class="inventory_label">Inventory</span>
                Currently ordered: <span id="ordered_quantity-'.$data['product_id'].'-'.$data['product_version'].'" class="ordered_quantity">'.$data['ordered_quantity'].'</span>
              </span>'.
            ($data['inventory_id'] > 0 ? '
              <input id="dec-'.$data['product_id'].'-'.$data['product_version'].'" class="inventory_dec" type="image" name="basket_dec" src="'.DIR_GRAPHICS.'basket_dec.png" alt="Decrement Inventory" onclick="adjust_inventory(\'product\',\''.$data['product_id'].'-'.$data['product_version'].'\',\'dec\'); return false;">
              <input id="inventory_pull_quantity-'.$data['product_id'].'-'.$data['product_version'].'" class="inventory_pull_quantity" type="text" name="inventory_pull_quantity" value="'.$data['inventory_pull_quantity'].'" alt="Submit" onkeyup="debounced_adjust_inventory(\'product\',\''.$data['product_id'].'-'.$data['product_version'].'\',\'set\'); return false;" autocomplete="off">
              <input id="inc-'.$data['product_id'].'-'.$data['product_version'].'" class="inventory_inc" type="image" name="basket_inc" src="'.DIR_GRAPHICS.'basket_inc.png" alt="Increment Inventory" onclick="adjust_inventory(\'product\',\''.$data['product_id'].'-'.$data['product_version'].'\',\'inc\'); return false;">
              <span id="bucket_quantity-'.$data['product_id'].'-'.$data['product_version'].'" class="bucket_quantity">'.$data['bucket_quantity'].'</span>
              <span class="inventory_description">'.$data['inventory_description'].'</span>'
              :
              '
              <span class="inventory_unlimited">Unlimited</span>'
              // NOTE: Not showing additional product controls beyond inventory management
            ).'
            </form>
          </div>';
    return $manage_inventory_control;
  };

// PRODUCT_FILLING_CALC
function manage_filling_control_calc($data, &$unique)
  {
    // This is for filling order quantities and weights
    $input = '
                <div class="manage_filling control_block">
                  <form class="manage_filling">
                    <input type="hidden" name="order_amount-'.$data['bpid'].'" value="'.$data['basket_quantity'].'">
                    <input type="hidden" name="bpid" value="'.$data['bpid'].'">
                    <input type="hidden" name="process_type" value="producer_basket">
                    <input type="submit" style="display:none;">
                    <div class="manage_order">
                      <div class="ordered">
                        <span class="order_text">Ordered:</span>
                        <span class="order_amount">'.$data['basket_quantity'].'</span>
                      </div>
                      <div class="shipped">
                        <span class="order_text">Shipped:</span>
                        <span class="order_fill"><input class="ship_quantity" type="text" id="ship_quantity-'.$data['bpid'].'" name="ship_quantity-'.$data['bpid'].'" value="'.($data['basket_quantity'] - $data['out_of_stock']).'" onkeyup="debounced_set_item ('.$data['bpid'].',\'set_outs\'); return false;" autocomplete="off"></span>
                      </div>
                      <div class="weight">'.
                      ($data['random_weight'] ? '
                        <span class="order_text">Weight:</span>
                        <span class="order_weight"><input class="weight" type="text" id="weight-'.$data['bpid'].'" name="weight-'.$data['bpid'].'" value="'.number_format($data['total_weight'], 2).'" onkeyup="debounced_set_item ('.$data['bpid'].',\'set_weight\'); return false;" autocomplete="off"></span>'
                        : '').'
                      </div>
                    </div>
                  </form>
                  <div class="message_area '.(strlen($data['customer_message']) > 0 ? 'has_message"' : 'no_message').'" id="message_area-'.$data['product_id'].'-'.$data['product_version'].'">
                    <header class="message_label">Message</header>
                    <div class="message" id="message-'.$data['product_id'].'-'.$data['product_version'].'" name="message" >'.$data['customer_message'].'</div>
                  </div>
                </div>';
    $unique['order_amount_total_prior'] = $unique['order_amount_total'];
    $unique['ship_quantity_total_prior'] = $unique['ship_quantity_total'];
    $unique['order_amount_total'] += $data['basket_quantity'];
    $unique['ship_quantity_total'] += $data['basket_quantity'] - $data['out_of_stock'];
    return $input;
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
            <input class="sort_type_radio" type="radio" id="sort_type_customer" name="sort_type" value="customer"'.($unique['sort_type'] == 'customer' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_customer">Customer<span class="detail">(ascending order)</span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_site_customer" name="sort_type" value="site_customer"'.($unique['sort_type'] == 'site_customer' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_site_customer">Site<span class="detail">site &gt; customer</span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_storage_customer" name="sort_type" value="storage_customer"'.($unique['sort_type'] == 'storage_customer' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_storage_customer">Storage<span class="detail">[NON &gt; REF &gt; FROZ] &gt; product</span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_category" name="sort_type" value="category"'.($unique['sort_type'] == 'category' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_category">Category<span class="detail">category &gt; subcategory</span></label>'.
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

/*********************** PRODUCER BASKET DIVISION OPEN/CLOSE *************************/

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
        $product_list_pager_top.
        (isset ($unique['pager_found_rows']) ? '
          <div class="search_status">
            Showing '.($unique['pager_found_rows'] > PER_PAGE ? $unique['pager_first_product_on_page'].'-'.$unique['pager_last_product_on_page'].' / ' : '').
            $unique['pager_found_rows'].' '.Inflect::pluralize_if ($unique['pager_found_rows'], 'product').'
          </div>'
        : ''
        ).'
      </div>
      <div id="product_list_table" class="producer_basket">';
    return $list_open;
  };


function close_list_bottom($data, &$unique)
  {
    $product_list_pager_bottom = pager_navigation ($data, $unique, 'product_list_pager_bottom');
    $list_close = '
    </div>'.
    $product_list_pager_bottom;
    return $list_close;
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
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="category">'.$data['category_name'].'</span>
                <span class="subcategory">'.$data['subcategory_name'].'</span>
              </header>';
          break;
        // Major division on producer_id (Using this to kick off a single major heading
        // since all products are inherently from the same producer.
        case 'producer_id':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="producer_id">All Products Sorted by Member (ascending)</span>
              </header>';
          break;
        // Major division on producer_name
        case 'producer_name':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="producer_name">'.$data['producer_name'].'</span>
              </header>';
          break;
        // Major division on site
        case 'site_short':
        case 'site_long':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="site_short">'.$data['site_short'].'</span>
                <span class="site_long">'.$data['site_long'].'</span>
              </header>';
          break;
        // Major division on storage
        case 'storage_code':
        case 'storage_type':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="storage_code">'.$data['storage_code'].'</span>
                <span class="storage_type">'.$data['storage_type'].'</span>
              </header>';
          break;
        // Major division on customer
        case 'member_id':
        case 'preferred_name':
          $header = '
            <div class="subpanel">
              <header class="header_minor">
                <span class="member_id">'.$data['member_id'].'</span><span class="preferred_name">'.$data['preferred_name'].'</span>
                <div class="member_contact">'.
                  ($data['email_address'] ? '<a class="email_address" href="mailto:'.$data['email_address'].'">'.$data['email_address'].'</a> ' : '').
                  ($data['home_phone'] ? '<span class="home_phone">'.$data['home_phone'].'</span>' : '').
                  ($data['work_phone'] ? '<span class="work_phone">'.$data['work_phone'].'</span>' : '').
                  ($data['mobile_phone'] ? '<span class="mobile_phone">'.$data['mobile_phone'].'</span>' : '').'
                </div>
              </header>';
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
          $header = '
            <h3 class="header_minor">
              '.$data['category_name'].' &mdash; '.$data['subcategory_name'].'
            </h3>';
          break;
        // Minor division on producer
        case 'producer_id':
        case 'producer_name':
          $header = '
            <h3 class="header_minor">
              <a href="'.PATH.'producers/'.$data['producer_link'].'">'.$data['producer_name'].'</a>
            </h3>';
          break;
        // Minor division on product (producer_byproduct)
        case 'pvid':
        case 'product_id':
        case 'product_name':
          $header = '
      <div id="product_display-'.$data['product_id'].'-'.$data['product_version'].'" class="product_display">
        <div class="product">'.
          $data['manage_inventory_control'].'
          <div class="product_title">
            <span class="product_id">#'.$data['product_id'].'<span class="product_version">'.$data['product_version'].'</span></span>
            <span class="product_name">'.$data['product_name'].'</span>
          </div>
          <div class="product_description" id="product_description-'.$data['product_id'].'-'.$data['product_version'].'">
            <div class="pricing">
              '.$data['image_display'].'
              '.$data['pricing_display'].'
              <span class="prodtype">'.$data['prodtype'].'</span>
              <span class="storage_type">'.$data['storage_type'].'</span>
            </div>
            '.$data['product_description'].'
            '.($data['is_wholesale_item'] ? wholesale_text_html() : '').'
            <div class="ordering_details">'.
              $data['ordering_unit_display'].
              $data['random_weight_display'].'
            </div>
          </div>
        </div><!-- .product -->';
          break;
        // Minor division on member (producer_bystoragecustomer)
        case 'member_id':
        case 'preferred_name':
          $header = '
      <div id="member_display-'.$data['member_id'].'">
        <div class="member_info">
          <h3>
            <span class="member_id">'.$data['member_id'].'</span>
            <span class="preferred_name">'.$data['preferred_name'].'</span>
          </h3>
          <div class="member_contact">'.
            ($data['email_address'] ? '<a class="email_address" href="mailto:'.$data['email_address'].'">'.$data['email_address'].'</a> ' : '').
            ($data['home_phone'] ? '<span class="home_phone">'.$data['home_phone'].'</span>' : '').
            ($data['work_phone'] ? '<span class="work_phone">'.$data['work_phone'].'</span>' : '').
            ($data['mobile_phone'] ? '<span class="mobile_phone">'.$data['mobile_phone'].'</span>' : '').'
            <div class="site_long">
              '.$data['site_long'].' ('.$data['site_short'].')
            </div>
          </div>
        </div>';
          break;
        // Otherwise...
          $header = '
          ';
          break;
      }
    return $header;
  };

function minor_division_close ($data, &$unique)
  { 
    if ($unique['completed'] != 'true')
      {
        $unique['order_amount_total'] -= $unique['order_amount_total_prior'];
        $unique['ship_quantity_total'] -= $unique['ship_quantity_total_prior'];
      }
    else
      {
        $unique['order_amount_total_prior'] = $unique['order_amount_total'];
        $unique['ship_quantity_total_prior'] = $unique['ship_quantity_total'];
      }
    $minor_close = '
                <div class="minor_section_close">
                  <div class="summary_data">
                    Ordered <strong>'.$unique['order_amount_total_prior'].'</strong> &nbsp; Shipped <strong>'.$unique['ship_quantity_total_prior'].'</strong>
                  </div>
                  <div class="minor_summary_text">
                    Quantities are for this page only. There may be more on prior or later pages. Reload to update shipped quantity.
                  </div>
                </div>
              </div>';
    return $minor_close;
  };

/************************* DISPLAY INDIVIDUAL ROWS CONTENT **************************/

function show_listing_row($data, &$unique)
  {
    // Set banner
    if ($data['availability'] == false)
      {
        $customer_alert_banner = '
          <div class="banner not_available">* See Note</div>';
        $customer_alert_info = '
          <div class="note not_available">
            <strong>This product is not available for '.$data['site_long'].'.</strong> You are not expected to fill this order unless you want to arrange delivery. Otherwise you will need to change to &ldquo;Shipped=0&rdquo; so the customer will not be charged.
          </div>';
      }
    else
      {
        $customer_alert_banner = '';
        $customer_alert_info = '';
      }
    if ($data['checked_out'] != 1)
      {
        $checkout_status = 'PENDING';
        $checkout_status_class = ' checkout_pending';
      }
    elseif ($data['random_weight'] == 1
            && $data['total_weight'] == 0)
      {
        $checkout_status = 'NEEDS WEIGHT';
        $checkout_status_class = ' checkout_ready';
      }
    else
      {
        $checkout_status = 'COMPLETE';
        $checkout_status_class = ' checkout_filled';
      }
    switch ($unique['row_type'])
      {
        // Row type
        case 'product_short':
          $row_content = '
                <div id="product_member-'.$data['product_id'].'-'.$data['member_id'].'" class="product_short '.($data['is_wholesale_item'] ? 'wholesale' : '').'">
                  <div class="checkout_status'.$checkout_status_class.'">'.
                    $checkout_status.'
                  </div>
                  <div class="product_info'.$checkout_status_class.'">'.
                    $data['manage_filling_control'].'
                    <div class="product_short">
                      <strong>#'.$data['product_id'].' &ndash; '.$data['product_name'].'</strong>
                      <span class="storage_code">'.$data['storage_code'].'</span>
                      <br />'.$data['inventory_display'].$data['ordering_unit_display'].$data['random_weight_display'].'
                    </div>
                    <div class="total">
                      '.$data['total_display'].'
                    </div>'.
          $customer_alert_banner.'
                  </div>
                </div>';
          break;
        // Row type (producer_byproduct)
        case 'member_short':
          $row_content = '
            <div id="product_member-'.$data['product_id'].'-'.$data['member_id'].'" class="member_short '.($data['is_wholesale_item'] ? 'wholesale' : '').'">
              <div class="checkout_status'.$checkout_status_class.'">'.
                $checkout_status.'
              </div>
              <div class="customer_info'.$checkout_status_class.'">'.
                $data['manage_filling_control'].'
                <div class="member_short">
                  <strong>#'.$data['member_id'].' &ndash; '.$data['preferred_name'].'</strong>
                  <div>'.
                    ($data['email_address'] ? '<a class="email_address" href="mailto:'.$data['email_address'].'">'.$data['email_address'].'</a> ' : '').
                    ($data['home_phone'] ? '<span class="home_phone">'.$data['home_phone'].'</span>' : '').
                    ($data['work_phone'] ? '<span class="work_phone">'.$data['work_phone'].'</span>' : '').
                    ($data['mobile_phone'] ? '<span class="mobile_phone">'.$data['mobile_phone'].'</span>' : '').
                  '</div>
                </div>'.
                $customer_alert_info.'
                <div class="site_long">
                  '.$data['site_long'].' ('.$data['site_short'].')
                </div>
                <div class="total">
                  '.$data['total_display'].'
                </div>'.
                $customer_alert_banner.'
                <br class="clear">
              </div>
            </div>';
          break;
        // Row type (producer_bystoragecustomer)
        case 'product_short_site':
          $row_content = '
                <div id="product_member-'.$data['product_id'].'-'.$data['member_id'].'" class="product_short_site '.($data['is_wholesale_item'] ? 'wholesale' : '').'">
                  <div class="checkout_status'.$checkout_status_class.'">'.
                    $checkout_status.'
                  </div>yyy
                  <div class="product_info'.$checkout_status_class.'">'.
                    $data['manage_filling_control'].'
                    <div class="product_short">
                      <strong>#'.$data['product_id'].' &ndash; '.$data['product_name'].'</strong>
                      <br />'.$data['inventory_display'].$data['ordering_unit_display'].$data['random_weight_display'].
            $customer_alert_info.'
                    </div>
                    <div class="total">
                      '.$data['total_display'].'
                    </div>'.
          $customer_alert_banner.'
                  </div>
                </div>';
          break;
        // Row type
        case 'product':
          $row_content = '
            <div>
              <div class="product">
                NOTHING YET
              </div>
            </div>';
          break;
        // Otherwise...
          $row_content = '
          ';
          break;
      }
    return $row_content;
  };
