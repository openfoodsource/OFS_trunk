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
    '<h2>No products in basket</h2>';
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
        $estimate_class = 'estimate';
        $estimate_text = '(estimated)<br>';
        $unique['weight_needed'] = true;
      }
    // Only show zero amounts when there is no extra charge
    if ($data['display_adjusted_customer_price'] != 0 || $data['extra_charge'] == 0)
      {
        $total_display = '
          <span id="customer_adjusted_cost'.$data['bpid'].'" class="'.$estimate_class.'">'.$estimate_text.'$'.number_format($data['display_adjusted_customer_price'], 2).'</span>';
      }
    // If there is any content so far, then add a newline
    if ($total_display)
      {
        $total_display .= '
          <br>';
      }
    if ($data['extra_charge'] != 0)
      {
        $total_display .= '
          <span id="extra_charge'.$data['bpid'].'">'.($data['extra_charge'] > 0 ? ($data['unit_price']  != 0 ? '+' : '') : '-').'&nbsp;$'.number_format($data['basket_quantity'] * abs($data['extra_charge']), 2).'</span>';
      }
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
    '<span class="inventory_display"><span id="available-'.$data['product_id'].'-'.$data['product_version'].'">'.($data['inventory_pull_quantity'] == 0 ? 'No' : $data['inventory_pull_quantity']).'</span> more <!--'.Inflect::pluralize_if ($data['inventory_pull_quantity'], $data['ordering_unit']).' -->available.</span>'
    : '');
    return $inventory_display;
  };

// IMAGE_DISPLAY_CALC
function image_display_calc($data)
  {
    $image_display =
    ($data['image_id'] ? '
      <img src="'.get_image_path_by_id ($data['image_id']).'" class="product_image" onclick="popup_src(\''.get_image_path_by_id ($data['image_id']).'\', \'product_image\', \'\', true);">'
    : '');
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

// PRODUCT_ORDERING_CALC
function manage_ordering_control_calc($data, &$unique)
  {
    $manage_ordering_control =
        ($data['site_id'] && $data['delivery_postal_code'] ? '
          <div class="manage_ordering control_block">'.
            ($data['checked_out'] == 0 ? /* For products that have not been checked out, allow changing quantities */
              '
              <form id="manage_ordering-'.$data['product_id'].'-'.$data['product_version'].'" class="manage_ordering '.($data['basket_quantity'] > 0 ? 'full' : 'empty').'">
                <input id="dec-'.$data['product_id'].'-'.$data['product_version'].'" class="basket_dec" type="image" name="basket_dec" src="'.DIR_GRAPHICS.'basket_dec.png" alt="Decrement Basket" onclick="adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'dec\'); return false;">
                <input id="basket_quantity-'.$data['product_id'].'-'.$data['product_version'].'" class="basket_quantity'.($data['basket_quantity'] > 0 ? ' full' : ' empty').'" type="text" name="basket_quantity" value="'.$data['basket_quantity'].'" '.($data['availability'] == true ? 'onkeyup="debounced_adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'set\'); return false;"' : '').' autocomplete="off">'
                .($data['availability'] == true ? '
                <div class="basket_quantity_zero" onclick="adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'inc\'); return false;">Order This</div>
                <input id="inc-'.$data['product_id'].'-'.$data['product_version'].'" class="basket_inc" type="image" name="basket_inc" src="'.DIR_GRAPHICS.'basket_inc.png" alt="Increment Basket" onclick="adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'inc\'); return false;">'
                : '' ).'
              </form>
              <div class="checkout_summary">
                <div class="ordering_unit">'.Inflect::pluralize_if ($data['basket_quantity'], $data['ordering_unit']).'</div>'.
                inventory_display_calc ($data).'
              </div>
              <div id="ship_reserve-'.$data['product_id'].'-'.$data['product_version'].'" class="ship_reserve '.($data['out_of_stock'] > 0 ? '' : 'hide').'">
                <div class="to_ship"><span id="to_ship-'.$data['product_id'].'-'.$data['product_version'].'">'.($data['basket_quantity'] - $data['out_of_stock']).'</span> will be shipped</div>
                <div class="reserve">plus <span id="reserve-'.$data['product_id'].'-'.$data['product_version'].'">'.$data['out_of_stock'].'</span> more, if available</div>
              </div>
              <div class="message_area '.(strlen($data['customer_message']) > 0 ? 'has_message"' : 'no_message').'" id="message_area-'.$data['product_id'].'-'.$data['product_version'].'">
                <button class="message_button" id="message_button-'.$data['product_id'].'-'.$data['product_version'].'" onclick="show_message_area('.$data['product_id'].','.$data['product_version'].'); return false;">
                  <span class="message_text">Add a Message...</span>
                </button>
                  <header class="message_label">Message</header>
                  <textarea class="message '.(strlen($data['customer_message']) > 0 ? 'has_message"' : 'no_message').'" id="message-'.$data['product_id'].'-'.$data['product_version'].'" name="message" placeholder="Optional message to producer..." onkeyup="debounced_adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'message\'); return false;">'.$data['customer_message'].'</textarea>
              </div>'
            : /* Already checked out, so show quantity in cart */
              '
              <div class="manage_ordering">
                <div id="basket_quantity-'.$data['product_id'].'-'.$data['product_version'].'" class="basket_quantity checkedout">'.$data['basket_quantity'].'</div>'.
                /* For checked-out products, show quantity and weight of product in the basket */
                '
                <div class="checkout_summary">
                  <div class="ordering_unit">'.Inflect::pluralize_if ($data['basket_quantity'], $data['ordering_unit']).'</div>'.
                  ($data['random_weight'] ?
                    ($data['total_weight'] > 0 ? '
                      <span class="total_weight">'.$data['total_weight'].'</span>
                      <span class="pricing_unit">'.Inflect::pluralize_if ($data['total_weight'], $data['pricing_unit']).'</span>'
                    : '
                      (weight pending)'
                    )
                  : ''
                  ).'
                </div>
                <div id="ship_reserve-'.$data['product_id'].'-'.$data['product_version'].'" class="ship_reserve '.($data['out_of_stock'] > 0 ? '' : 'hide').'">
                  <div class="to_ship"><span id="to_ship-'.$data['product_id'].'-'.$data['product_version'].'">'.($data['basket_quantity'] - $data['out_of_stock']).'</span> will be shipped</div>
                  <div class="reserve">plus <span id="reserve-'.$data['product_id'].'-'.$data['product_version'].'">'.$data['out_of_stock'].'</span> more, if available</div>
                </div>
              </div>
              <div class="message_area '.(strlen($data['customer_message']) > 0 ? 'has_message"' : 'no_message').'" id="message_area-'.$data['product_id'].'-'.$data['product_version'].'">
                <header class="message_label">Message</header>
                <div class="message" id="message-'.$data['product_id'].'-'.$data['product_version'].'" name="message" >'.$data['customer_message'].'</div>
              </div>'
            ).
            /* Provide line-item checkout for this item */
            ((CHECKOUT_MEMBER_ID == 'ALL' || in_array ($_SESSION['member_id'], explode (',', CHECKOUT_MEMBER_ID))) && $data['availability'] == true ? '
              <div class="product_checkout">'.
                ($data['checked_out'] ? '
                  <button class="checkout_button checkedout" id="checkout_item-'.$data['product_id'].'-'.$data['product_version'].'">
                    <span class="checked">CHECKED OUT</span>
                  </button>'
                : '
                  <button class="checkout_button checkout" id="checkout_item-'.$data['product_id'].'-'.$data['product_version'].'" onclick="checkout('.$data['product_id'].','.$data['product_version'].',\'checkout\'); return false;" onblur="checkout('.$data['product_id'].','.$data['product_version'].',\'cancel\'); return false;">
                    <span class="default">Checkout this item</span>
                    <span class="confirm">Again to confirm...</span>
                    <span class="checked">CHECKED OUT</span>
                  </button>'
                ).'
              </div>'
            : ''
            ).'
          </div>'
        : /* Except when product is not available for this site */ '
          <div class="manage_ordering control_block">
            <div class="unavailable">
              Not available at '.$data['site_long'].'
            </div>
          </div>'
        );
    return $manage_ordering_control;
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
    $sorting_navigation = '';
    return $sorting_navigation;
  }


/*********************** ORDER CYCLE NAVIGATION SECTION *************************/

function order_cycle_navigation($data, &$unique)
  {
    // Set up the previous/next order cycle (delivery_id) navigation
    $current_delivery_id = (isset ($_GET['delivery_id']) ? $_GET['delivery_id'] : ActiveCycle::delivery_id());
    $customer_current_delivery_id_key = array_search ($current_delivery_id, array_keys ($_SESSION['customer_delivery_id_array']))
      or $customer_current_delivery_id_key = count ($_SESSION['customer_delivery_id_array']); // Ordinal for the last array entry
    // Check if the member had a prior basket
    if ($current_delivery_id > array_keys ($_SESSION['delivery_id_array'])[0]
        && $current_delivery_id > array_keys ($_SESSION['customer_delivery_id_array'])[0])
      {
        $customer_prior_delivery_id = array_keys ($_SESSION['customer_delivery_id_array'])[$customer_current_delivery_id_key - 1];
        $customer_prior_label = '&larr; PRIOR ORDER';
      }
    // Check if the member has a next basket
    if ($current_delivery_id < array_reverse (array_keys ($_SESSION['delivery_id_array']))[0]
        && $current_delivery_id < array_reverse (array_keys ($_SESSION['customer_delivery_id_array']))[0])
      {
        $customer_next_delivery_id = array_keys ($_SESSION['customer_delivery_id_array'])[$customer_current_delivery_id_key + 1];
        $customer_next_label = 'NEXT ORDER &rarr;';
      }
    elseif ($current_delivery_id < ActiveCycle::delivery_id())
      {
        // Always allow going to the current cycle, even when there are no products to show
        $customer_next_delivery_id = ActiveCycle::delivery_id();
        $customer_next_label = 'CURRENT ORDER &rarr;';
      }
    $next_delivery_id = '';
    $order_cycle_navigation = '
      <form id="delivery_id_nav" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">'.
        (isset ($customer_prior_delivery_id) ? '
          <button class="prior" name="delivery_id" value="'.$customer_prior_delivery_id.'">'.$customer_prior_label.'</button>'
        : ''
        ).'
        <span class="delivery_id">'.date (DATE_FORMAT_CLOSED, strtotime($_SESSION['delivery_id_array'][$current_delivery_id])).'</span>'.
        (isset ($customer_next_delivery_id) ? '
          <button class="next" name="delivery_id" value="'.$customer_next_delivery_id.'">'.$customer_next_label.'</button>'
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

/*********************** CUSTOMER BASKET DIVISION OPEN/CLOSE *************************/

function open_list_top($data, &$unique)
  {
    $product_list_pager_top = pager_navigation ($data, $unique, 'product_list_pager_top');
    $sorting_navigation = sorting_navigation ($data, $unique);
    $order_cycle_navigation = order_cycle_navigation($data, $unique);
    $list_open = 
      // Setup for checking out entire basket
      (in_array ($_SESSION['member_id'], explode (',', CHECKOUT_MEMBER_ID)) ? '
        <div class="basket_checkout_block">
          <div class="checkout_button" id="checkout_basket'.$data['basket_id'].'">'.
          ($data['site_id'] && $data['delivery_postal_code'] ?
            ($data['basket_checked_out'] != 0 ? '
              <img class="checkout_check" src="'.DIR_GRAPHICS.'checkout-ccs.png">
              <span class="checkout_text">Checked</span>'
            : '
              <input type="image" class="checkout_check" src="'.DIR_GRAPHICS.'checkout-g.png" onclick="adjust_customer_basket(0,0,\'checkout_basket\'); return false;">
              <span class="checkout_text">Checkout basket</span>'
            )
          : 'No site selected for checkout'
          ).'
          </div>
        </div>'
      : ''
      ).
      /* The following hidden fields are required for ajax to handle basket functions */ '
      <input type="hidden" name="basket_id" id="basket_id" value="'.$data['basket_id'].'">
      <input type="hidden" name="site_id" id="site_id" value="'.CurrentBasket::site_id() /* Is only set when basket has been opened */ .'">
      <input type="hidden" name="member_id" id="member_id" value="'.$data['member_id'].'">
      <input type="hidden" name="delivery_id" id="delivery_id" value="'.$data['delivery_id'].'">
      <div class="list_navigation">'.
        $sorting_navigation.
        $order_cycle_navigation.
        $product_list_pager_top.
        (isset ($unique['pager_found_rows']) ? '
          <div class="search_status">
            Showing '.($unique['pager_found_rows'] > PER_PAGE ? $unique['pager_first_product_on_page'].'-'.$unique['pager_last_product_on_page'].' / ' : '').
            $unique['pager_found_rows'].' '.Inflect::pluralize_if ($unique['pager_found_rows'], 'product').'
          </div>'
        : ''
        ).'
      </div>
      <div id="product_list_table" class="customer_basket">';
    return $list_open;
  };


function close_list_bottom($data, &$unique)
  {
    $product_list_pager_bottom = pager_navigation ($data, $unique, 'product_list_pager_bottom');
    $total_text = 'Total';
    $total_message = '
      <span class="total_message">Reload to view changes if quantities have been modified.</span>';
    if ($unique['pager_last_page'] > 1)
      {
        $this_page_only_message = '
          <span class="this_page_only_message">for this page</span>';
      }
    if ($unique['weight_needed'] == true)
      {
        if (RANDOM_CALC == 'ZERO') $estimate_type = 'zero ';
        elseif (RANDOM_CALC == 'AVG') $estimate_type = 'average';
        elseif (RANDOM_CALC == 'MIN') $estimate_type = 'minimum';
        elseif (RANDOM_CALC == 'MAX') $estimate_type = 'maximum';
        $total_text = 'Estimated Total';
        $estimate_message .= '
          <span class="estimate_message">Estimated total (pricing based on '.$estimate_type.' estimated weight)</span>';
        $estimate_class = 'basket_estimate';
      }
    $list_close = '
      <div>
        <div class="basket_total">
          <span class="total_label '.$estimate_class.'">'.$total_text.$this_page_only_message.': </span>
          <span id="total_cost" class="'.$estimate_class.'">$&nbsp;'.number_format($unique['running_total'], 2).'</span>'.
          
          $total_message.
          $estimate_message.'
        </div>
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
        case 'major_division_empty_title':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="major_division_special_title">Customer Basket: '.date('F j, Y', strtotime(isset ($data['delivery_date']) ? $data['delivery_date'] : $unique['delivery_date'])).'</span>
              </header>';
          break;
        case 'major_division_special_title':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="major_division_special_title">'.$data['major_division_special_title'].'</span>
              </header>';
          break;
        case 'category_id':
        case 'category_name':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="category">'.$data['category_name'].'</span>
              </header>';
          break;
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="category">'.$data['category_name'].'</span>
                <span class="subcategory">'.$data['subcategory_name'].'</span>
              </header>';
          break;
        // Major division on producer_id (shows producer details)
        case 'producer_id':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="business_name">'.$data['business_name'].'</span>
              </header>';
          break;
        // Major division on business_name
        case 'business_name':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="business_name">'.$data['business_name'].'</span>
              </header>';
          break;
        case 'storage_code':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="storage_code">'.$data['storage_code'].'</span>
                <span class="storage_type">'.$data['storage_type'].'</span>
              </header>';
          break;
        case 'delivery_date':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="delivery_date">Products Ordered: '.$data['delivery_date'].'</span>
              </header>';
          break;
        case 'number_of_orders':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="number_of_orders">Ordered '.$data['number_of_orders'].' '.Inflect::pluralize_if ($data['number_of_orders'], 'time').'</span>
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
          $header = '
            <h3 class="header_minor">
              '.$data['category_name'].'
            </h3>';
          break;
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '
            <h3 class="header_minor">
              <span class="subcategory_name">'.$data['subcategory_name'].'</span>
            </h3>';
          break;
        // Minor division on producer
        case 'producer_id':
        case 'business_name':
          $header = '
            <h3 class="header_minor">
              <a href="'.PATH.'producers/'.$data['producer_link'].'">'.$data['business_name'].'</a>
            </h3>';
          break;
        // Minor division on product
        case 'product_id':
        case 'product_name':
          $header = '
            <div id="product_id-'.$row['product_id'].'">
              <h3 class="header_minor">
                '.$row['image_display'].'
              </h3>
              <div class="header_minor">
              (#'.$row['product_id'].') '.$row['product_name'].'
              <br>'.$row['inventory_display'].$row['ordering_unit_display'].$row['random_weight_display'].'
              '.$row['product_description'].'
              '.($row['is_wholesale_item'] == true ? wholesale_text_html() : '').'
              </div>
            </div>';
          break;
        case 'ordered_for_date':
          $header = '
              <h3 class="header_minor">
                <span class="ordered_for_date">Products Ordered for: '.$data['ordered_for_date'].'</span>
              </h3>';
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
    $minor_close = '';
    return $minor_close;
  };

/************************* DISPLAY INDIVIDUAL ROWS CONTENT **************************/

function show_listing_row($data, &$unique)
  {
    // $unique['row_type'] == 'product';
    if ($data['availability'] == false)
      {
        $customer_alert_banner = '
          <div class="banner not_available">* See Note</div>';
        $customer_alert_info = '
          <div class="note not_available">
            <strong>This product is not available at '.$data['site_long_you'].'.</strong> The producer will be unable to fill this order. If you have not already checked out, please remove this item from your basket or switch back to a site where it is available.
          </div>';
      }
    elseif ($data['product_version'] != $data['active_product_version'] // Outdated version in the basket
            && $data['checked_out'] != true) // but no need to flag if the basket is already checked out
      {
        $customer_alert_banner = '
          <div class="banner wrong_version">* See Note</div>';
        $customer_alert_info = '
          <div class="note wrong_version">
            The producer has modified this product since you selected it. You can decrease the quantity here, but you will only be able to add more from the latest version.
          </div>';
      }
    else
      {
        $customer_alert_banner = '';
        $customer_alert_info = '';
      }
    $row_content = '
      <div id="product_id-'.$data['product_id'].'" class="'.($data['is_wholesale_item'] ? ' wholesale' : '').'">
        <div class="product_description product'.($data['availability'] == false || ($data['inventory_pull_quantity'] == 0 && $data['inventory_id']) ? ' inactive' : '').'">'.
            $data['manage_ordering_control'].
          (isset ($data['days_new']) && $data['days_new'] <= DAYS_CONSIDERED_NEW ? '
            <div class="flag new_product">NEW<span class="days_new">'.$data['days_new'].'</span></div>'
            :
            (isset ($data['days_changed']) && $data['days_changed'] <= DAYS_CONSIDERED_CHANGED ?
              '<div class="flag changed_product">CHANGED<span class="days_changed">'.$data['days_changed'].'</span></div>'
              : ''
            )
          ).'
          <div class="product_title">
            <span class="product_id">'.$data['product_id'].'
              <span class="product_version">'.$data['product_version'].'</span>
            </span>
            <span class="product_name">'.$data['product_name'].'</span>
          </div>
          <div class="product_description" id="product_description-'.$data['product_id'].'">
            <div class="pricing">
              '.$data['image_display'].'
              '.$data['pricing_display'].'
              <span class="prodtype">'.$data['prodtype'].'</span>
              <span class="storage_type">'.$data['storage_type'].'</span>
            </div>'.
            $data['product_description'].
            ($data['is_wholesale_item'] ? wholesale_text_html() : '').
            $customer_alert_info.'
          </div>
          <div class="ordering_details">
            '.$data['ordering_unit_display'].'
            '.$data['random_weight_display'].'
          </div>'.
          $customer_alert_banner.'
        </div>
      </div>';
    return $row_content;
  }
