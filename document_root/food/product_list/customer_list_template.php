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

function no_product_message(&$unique)
  {
    if (strlen ($unique['no_site_selected']) > 0) return $unique['no_site_selected'];
    else return '<h2>No products to show</h2>';
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
    // Users who are not logged in
    if ($data['display_anonymous_price'] &&
        $data['display_base_anonymous_price'])
      {
        $pricing_display_calc .= '
          <span class="anon_price">'.($_GET['type'] == 'producer_list' ? 'Anon: ' : '').'$'.number_format($data['display_base_anonymous_price'], 2).$per_pricing_unit.Inflect::singularize ($data['pricing_unit']).'</span>';
      }
    // Users who are logged in
    if ($data['display_base_price'] &&
        $data['display_base_customer_price'])
      {
        $pricing_display_calc .= '
          <span class="base_price">'.($_GET['type'] == 'producer_list' ? 'Retail: ' : '').'$'.number_format($data['display_base_customer_price'], 2).$per_pricing_unit.Inflect::singularize ($data['pricing_unit']).'</span>';
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
      ($data['order_open'] ? '
        <div class="manage_ordering control_block">'.
          ($data['checked_out'] == 0 ? /* For products that have not been checked out, allow changing quantities */ '
            <form id="manage_ordering-'.$data['product_id'].'-'.$data['product_version'].'" class="manage_ordering '.($data['basket_quantity'] > 0 ? 'full' : 'empty').'">
              <input id="dec-'.$data['product_id'].'-'.$data['product_version'].'" class="basket_dec" type="image" name="basket_dec" src="'.DIR_GRAPHICS.'basket_dec.png" alt="Decrement Basket" onclick="adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'dec\'); return false;">
              <input id="basket_quantity-'.$data['product_id'].'-'.$data['product_version'].'" class="basket_quantity'.($data['basket_quantity'] > 0 ? ' full' : ' empty').'" type="text" name="basket_quantity" value="'.$data['basket_quantity'].'" '.($data['availability'] == true ? 'onkeyup="debounced_adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'set\'); return false;"' : '').' autocomplete="off">'
              .($data['availability'] == true ? '
              <div class="basket_quantity_zero" onclick="adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'inc\'); return false;">Order This</div>
              <input id="inc-'.$data['product_id'].'-'.$data['product_version'].'" class="basket_inc" type="image" name="basket_inc" src="'.DIR_GRAPHICS.'basket_inc.png" alt="Increment Basket" onclick="adjust_customer_basket('.$data['product_id'].','.$data['product_version'].',\'inc\'); return false;">'
              : '' ).'
            </form>
            <div class="checkout_summary">'.
              inventory_display_calc ($data).'
            </div>
            <div id="ship_reserve-'.$data['product_id'].'-'.$data['product_version'].'" class="ship_reserve '.($data['out_of_stock'] > 0 ? '' : 'hide').'">
              <div class="to_ship"><span id="to_ship-'.$data['product_id'].'-'.$data['product_version'].'">'.($data['basket_quantity'] - $data['out_of_stock']).'</span> will be shipped</div>
              <div class="reserve">plus <span id="reserve-'.$data['product_id'].'-'.$data['product_version'].'">'.$data['out_of_stock'].'</span> more, if available</div>
            </div>
            <div class="message_area'.(strlen($data['customer_message']) > 0 ? ' has_message' : ' no_message').($data['basket_quantity'] > 0 ? '' : ' hidden').'" id="message_area-'.$data['product_id'].'-'.$data['product_version'].'">
              (Message to producer can be added in basket view)
            </div>'
          : /* Already checked out, so show quantity in cart */ '
            <div class="manage_ordering">
              <div id="basket_quantity-'.$data['product_id'].'-'.$data['product_version'].'" class="basket_quantity checkedout">'.$data['basket_quantity'].'</div>'.
              /* For checked-out products, show quantity and weight of product in the basket */ '
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
            </div>'
          ).'
        </div>'
      : // Except when ordering is closed or the user is not logged in
        (isset ($_SESSION['member_id']) ? '
          <div class="manage_ordering control_block">
            <div class="ordering_blocked_message">Ordering is currently closed.<br /><a href="'.BASE_URL.PATH.'panel_shopping.php#schedule">Check Calendar</span></div>
          </div>'
        : /* NOTE: The following reloads the page and triggers a hook on product_list.php to require auth_type=member
             ... this will ultimately force the page to reload after the member logs in*/ '
          <div class="manage_ordering control_block">
            <div class="ordering_blocked_message">You must be a member and logged-in before you can order.<br /><a href="'.$_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'].'&auth_type=member">Login</a><a href="'.BASE_URL.PATH.'member_form.php">Join</a></div>
          </div>'
        )
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
    $sorting_navigation = '
        <form name="sort_option" action="'.$_SERVER['SCRIPT_NAME'].'" method="GET">
          <div id="sort_option" class="hidden">'.
            (strlen ($_GET['category_id']) > 0 ? '<input type="hidden" name="category_id" value="'.$_GET['category_id'].'">' : '').
            (strlen ($_GET['delivery_id']) > 0 ? '<input type="hidden" name="delivery_id" value="'.$_GET['delivery_id'].'">' : '').
            (strlen ($_GET['display_as']) > 0 ? '<input type="hidden" name="display_as" value="'.$_GET['display_as'].'">' : '').'
            <input type="hidden" name="page" value="1">'. // Force page=1 when switching sort option
            (strlen ($_GET['producer_id']) > 0 ? '<input type="hidden" name="producer_id" value="'.$_GET['producer_id'].'">' : '').
            (strlen ($_GET['producer_link']) > 0 ? '<input type="hidden" name="producer_link" value="'.$_GET['producer_link'].'">' : '').
            (strlen ($_GET['product_id']) > 0 ? '<input type="hidden" name="product_id" value="'.$_GET['product_id'].'">' : '').'
            <!-- BUTTONS FOR NARROWING THE SELECTION -->
            <div class="sort_title">Narrow the List of Products</div>
              <input class="select_type_radio" type="radio" id="select_type_all" name="select_type" value="all"'.($unique['select_type'] == 'all' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
              <label for="select_type_all">All Products<span class="detail">&nbsp;</span></label>
              <input class="select_type_radio" type="radio" id="select_type_organic" name="select_type" value="organic"'.($unique['select_type'] == 'organic' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
              <label for="select_type_organic">Organic Products<span class="detail">All, Part, or As Organic</span></label>
              <input class="select_type_radio" type="radio" id="select_type_new_changed" name="select_type" value="new_changed"'.($unique['select_type'] == 'new_changed' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
              <label for="select_type_new_changed">Recent Products<span class="detail">New &amp; Changed</span></label>
              <input class="select_type_radio" type="radio" id="select_type_previously_ordered" name="select_type" value="previously_ordered"'.($unique['select_type'] == 'previously_ordered' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
              <label for="select_type_previously_ordered">Prior Orders<span class="detail">Ordered Previously</span></label>'.
            // Only show the select_type_wholesale option for institutional members
            (CurrentMember::auth_type('institution') ? '
              <input class="select_type_radio" type="radio" id="select_type_wholesale" name="select_type" value="wholesale"'.($unique['select_type'] == 'wholesale' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
              <label for="select_type_wholesale">Wholesale Products<span class="detail">For Institutions Only</span></label>'
            : '').'
            <!-- BUTTONS FOR SORTING THE SELECTION -->
            <div class="sort_title">Select a sorting option</div>
              <input class="sort_type_radio" type="radio" id="sort_type_storage" name="sort_type" value="storage"'.($unique['sort_type'] == 'storage' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
              <label for="sort_type_storage">Storage Type<span class="detail">[NON &gt; REF &gt; FROZ] &gt; product</span></label>
              <input class="sort_type_radio" type="radio" id="sort_type_category" name="sort_type" value="category"'.($unique['sort_type'] == 'category' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
              <label for="sort_type_category">Category<span class="detail">category &gt; subcategory</span></label>
              <input class="sort_type_radio" type="radio" id="sort_type_product_name" name="sort_type" value="product_name"'.($unique['sort_type'] == 'product_name' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
              <label for="sort_type_product_name">Product Name<span class="detail">A &ndash; Z</span></label>'.
            // Only show the sort-by-producer option when not on a producer page
            ($_GET['select_type'] != 'producer_id' && $_GET['select_type'] != 'producer_link' ? '
              <input class="sort_type_radio" type="radio" id="sort_type_producer" name="sort_type" value="producer"'.($unique['sort_type'] == 'producer' ? ' checked' : '').' onclick="document.forms[\'sort_option\'].submit()">
              <label for="sort_type_producer">Producer<span class="detail">A &ndash; Z</span></label>'
            : '').
            // Only show the recently_ordered and frequently_ordered options when selecting previously_ordered products
            ($_GET['select_type'] == 'previously_ordered' ? '
              <input class="sort_type_radio" type="radio" id="sort_type_recently_ordered" name="sort_type" value="recently_ordered"'.($unique['sort_type'] == 'recently_ordered' ? ' checked' : '').' onclick="document.forms[\'sort_option\'].submit()">
              <label for="sort_type_recently_ordered">Most Recent<span class="detail">Currently Available</span></label>
              <input class="sort_type_radio" type="radio" id="sort_type_frequently_ordered" name="sort_type" value="frequently_ordered"'.($unique['sort_type'] == 'frequently_ordered' ? ' checked' : '').' onclick="document.forms[\'sort_option\'].submit()">
              <label for="sort_type_frequently_ordered">Most Frequent<span class="detail">Currently Available</span></label>'
            : '').
            (strlen ($_GET['subcat_id']) > 0 ? '<input type="hidden" name="subcat_id" value="'.$_GET['subcat_id'].'">' : '').
            (strlen ($_GET['type']) > 0 ? '<input type="hidden" name="type" value="'.$_GET['type'].'">' : '').'
          </div>
          <div class="search_container">
            <input class="search_input" type="text" name="query" value="'.$unique['search_query'].'">
            <input class="search_button" type="submit" name="action" value="Search">
          </div>
        </form>';
    return $sorting_navigation;
  }


/*********************** ORDER CYCLE NAVIGATION SECTION *************************/

function order_cycle_navigation($data, &$unique)
  {
    // Order cycle navigation is not needed for product listings
    $order_cycle_navigation = '';
    return $order_cycle_navigation;
  };

/*********************** CUSTOMER LIST DIVISION OPEN/CLOSE *************************/

function open_list_top($data, &$unique)
  {
    $product_list_pager_top = pager_navigation ($data, $unique, 'product_list_pager_top');
    $sorting_navigation = sorting_navigation ($data, $unique);
    $order_cycle_navigation = order_cycle_navigation($data, $unique);
    $list_open = '
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
      <div id="product_list_table" class="customer_list">';
    // Show producer details when asked, but only when page=1
    if ($unique['show_producer_details'] == true && $unique['pager_this_page'] <= 1)
      {
        $list_open .= '
        <div class="subpanel">
          <header class="header_major">
            <span class="business_name">'.$unique['business_name'].'</span>
          </header>
          <div class="producer_details">
            <div class="producer_listing">
              <a href="'.PATH.'product_list.php?type=customer_list&select_type=producer_id&producer_id='.$unique['producer_id'].'" class="business_name">'.
                (strlen ($unique['logo_id']) > 0 ? '<img class="producer_logo" src="'.PATH.'show_logo.php?logo_id='.$unique['logo_id'].'">' : '').'
              </a>'.
              (strlen ($unique['about']) > 0 ? '
              <span class="producer_section about">
                <h4 class="header about_header">About '.$unique['business_name'].':</h4>'.
                $unique['about'].'
              </span>' : '').
              (strlen ($unique['general_practices']) > 0 ? '
              <span class="producer_section general_practices">
                <h4 class="header general_practices_header">General Practices:</h4>'.
                $unique['general_practices'].'
              </span>' : '').
              (strlen ($unique['highlights']) > 0 ? '
              <span class="producer_section highlights">
                <h4 class="header highlights_header">Highlights:</h4>'.
                $unique['highlights'].'
              </span>' : '').
              (strlen ($unique['Additional']) > 0 ? '
              <span class="producer_section Additional">
                <h4 class="header Additional_header">Additional:</h4>'.
                $unique['Additional'].'
              </span>' : '').
              (strlen ($unique['ingredients']) > 0 ? '
              <span class="producer_section ingredients">
                <h4 class="header ingredients_header">Ingredients:</h4>'.
                $unique['ingredients'].'
              </span>' : '').
              (strlen ($unique['producttypes']) > 0 ? '
              <span class="producer_section producttypes">
                <h4 class="header producttypes_header">Product Types:</h4>'.
                $unique['producttypes'].'
              </span>' : '').'
              <span class="original_questionnaire_link">
                <a class="block_link popup_link" onclick="popup_src(\''.PATH.'prdcr_display_quest.php?pid='.$unique['producer_id'].'&display_as=popup\', \'prdcr_display_quest\', \'\', false);">More detailed information about this producer</a>
              </span>
            </div>
          </div>
        </div>
        ';
      }
    /* The following hidden fields are required for ajax to handle basket functions */
    $list_open .= '
        <input type="hidden" name="basket_id" id="basket_id" value="'.$unique['basket_id'].'">
        <input type="hidden" name="site_id" id="site_id" value="'.CurrentBasket::site_id() /* Is only set when basket has been opened */ .'">
        <input type="hidden" name="member_id" id="member_id" value="'.$unique['member_id'].'">
        <input type="hidden" name="delivery_id" id="delivery_id" value="'.$unique['delivery_id'].'">';
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
        case 'major_division_empty_title':
          $header = '
            <div class="subpanel">';
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
        case 'number_of_orders':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="number_of_orders">Products Ordered '.$data['number_of_orders'].' '.Inflect::pluralize_if ($data['number_of_orders'], 'time').'</span>
              </header>';
          break;
        case 'ordered_for_date':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="ordered_for_date">Products Last Ordered: '.$data['ordered_for_date'].'</span>
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
            <h3 class="header_minor">
              <span class="product_name">'.$data['product_name'].'</a>
            </h3>';
          break;
        case 'ordered_for_date':
          $header = '
            <h3 class="header_minor">
              <span class="ordered_for_date">Last Ordered: '.$data['ordered_for_date'].'</span>
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
            <strong>This product is not available at '.$data['site_long_you'].'.</strong> The producer will be unable to fill this order. Please remove this item from your basket or switch back to a site where it is available.
          </div>';
      }
    elseif ($data['all_versions_quantity'] != 0 // No versions in basket
        && $data['basket_quantity'] != $data['all_versions_quantity']) // Or all versions match the current version
      {
        $customer_alert_banner = '
          <div class="banner wrong_version">* See Note</div>';
        $customer_alert_info = '
          <div class="note wrong_version">
            An earlier version of this product is already in your basket. Adding this product will be <span class="em">in addition to</span> quantities already ordered.
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
          <div class="producer_name">
            '.business_name_display_calc($data).'
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
