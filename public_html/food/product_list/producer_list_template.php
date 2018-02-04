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
    '<h2>No products to show</h2>';
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
    $total_display =
    ($data['unit_price']  != 0 ? '<span id="producer_adjusted_cost'.$data['bpid'].'">$&nbsp;'.number_format($data['producer_adjusted_cost'], 2).'</span>' : '').
    ($data['unit_price'] != 0 && $data['extra_charge'] != 0 ? '<br>' : '').
    ($data['extra_charge'] != 0 ? '<span id="extra_charge'.$data['bpid'].'">'.($data['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format($data['basket_quantity'] * abs($data['extra_charge']), 2).'</span>' : '');
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
    if ($data['base_producer_cost'])
      {
        $pricing_display_calc .= '
          <span class="basket_price">$'.number_format($data['base_producer_cost'], 2).$per_pricing_unit.Inflect::singularize ($data['pricing_unit']).'</span>';
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
              <figure class="product_image">
                <figcaption class="edit_product_image" onclick="popup_src(\'set_product_image.php?display_as=popup&action=select_image&product_id='.$data['product_id'].'&product_version='.$data['product_version'].'\', \'edit_product_image\', \'\', false);">Click to Select Image</figcaption>'.
                ($data['image_id'] ? '
                <img id="image-'.$data['product_id'].'-'.$data['product_version'].'" src="'.get_image_path_by_id ($data['image_id']).'" class="product_image" onclick="popup_src(\'set_product_image.php?display_as=popup&action=select_image&product_id='.$data['product_id'].'&product_version='.$data['product_version'].'\', \'edit_product_image\', \'\', false);">'
                : '
                <img id="image-'.$data['product_id'].'-'.$data['product_version'].'" src="'.DIR_GRAPHICS.'no_image_set.png" class="no_product_image" onclick="popup_src(\'set_product_image.php?display_as=popup&action=select_image&product_id='.$data['product_id'].'&product_version='.$data['product_version'].'&a='.$_REQUEST['a'].'\', \'edit_product_image\', \'\', false);">'
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
            <form class="inventory_control" id="inventory_control-'.$data['product_id'].'-'.$data['product_version'].'" action="'.$_SERVER['SCRIPT_NAME'].'?type='.$_GET['type'].'" method="post">
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
              <span class="inventory_unlimited">Unlimited</span>').
            ($unique['select_type'] == 'versions' || $unique['select_type'] == 'admin_unconfirmed' ? // DISPLAYING ONLY VERSIONS OF A PARTICULAR PRODUCT (OR CONFIRMATION PAGE)
              ($data['total_ordered_this_version'] == 0 ? '
              <input class="delete_product" id="delete_product-'.$data['product_id'].'-'.$data['product_version'].'" onblur="jQuery(this).removeClass(\'warn\');" onclick="delete_product(\''.$data['product_id'].'\',\''.$data['product_version'].'\',\'delete\', \'version\')" value="DELETE VERSION" type="button">'
              : '
              <a class="product_history_link" onclick="popup_src(\''.PATH.'product_order_history.php?product_id='.$data['product_id'].'&product_version='.$data['product_version'].'&producer_id='.$_SESSION['producer_id_you'].'&a='.$_REQUEST['a'].'&display_as=popup\', \'product_sales_history\', \'\', false)">Version&nbsp;Sales&nbsp;History</a><br>'
              )
            :                                  // DISPLAYING ONLY CONFIRMED VERSIONS OF ALL PRODUCTS
              ($data['total_ordered_this_product'] == 0 ? '
              <a class="product_versions_link" href="product_list.php?type=producer_list&select_type=versions&product_id='.$data['product_id'].'&a='.$_REQUEST['a'].'">Show&nbsp;Versions</a>
              <input class="delete_product" id="delete_product-'.$data['product_id'].'-'.$data['product_version'].'" onblur="jQuery(this).removeClass(\'warn\');" onclick="delete_product(\''.$data['product_id'].'\',\''.$data['product_version'].'\',\'delete\', \'product\')" value="DELETE PRODUCT" type="button">'
              : '
              <a class="product_versions_link" href="product_list.php?type=producer_list&select_type=versions&product_id='.$data['product_id'].'&a='.$_REQUEST['a'].'">Show&nbsp;Versions</a>
              <a class="product_history_link" onclick="popup_src(\''.PATH.'product_order_history.php?product_id='.$data['product_id'].'&producer_id='.$_SESSION['producer_id_you'].'&a='.$_REQUEST['a'].'&display_as=popup\', \'product_sales_history\', \'\', false)">Sales&nbsp;History</a>'
              )
            ).'
            </form>
          </div>';
    return $manage_inventory_control;
  };

// PRODUCT_FILLING_CALC
function manage_filling_control_calc($data, &$unique)
  {
    // No order filling from the producer list
    return "";
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
        <output id="'.$pager_id.'_display_value" class="pager_display_value">Page '.$unique['pager_this_page'].'</output>'
      : ''
      ).'
      </form>'
    : ''
    );
    return $pager_navigation;
  };

/************************* SORTING NAVIGATION SECTION ***************************/

function sorting_navigation($data, &$unique)
  {
    $sorting_navigation = '
      <div class="sorting_selection">
        <form id="sort_option" class="hidden" name="sort_option" action="'.$_SERVER['SCRIPT_NAME'].'" method="GET">'.
          (strlen ($_GET['category_id']) > 0 ? '<input type="hidden" name="category_id" value="'.$_GET['category_id'].'">' : '').
          (strlen ($_GET['delivery_id']) > 0 ? '<input type="hidden" name="delivery_id" value="'.$_GET['delivery_id'].'">' : '').
          (strlen ($_GET['display_as']) > 0 ? '<input type="hidden" name="display_as" value="'.$_GET['display_as'].'">' : '').
          (strlen ($_GET['page']) > 0 ? '<input type="hidden" name="page" value="1">' : ''). // Always start on page 1
          (strlen ($_GET['producer_id']) > 0 ? '<input type="hidden" name="producer_id" value="'.$_GET['producer_id'].'">' : '').
          (strlen ($_GET['producer_link']) > 0 ? '<input type="hidden" name="producer_link" value="'.$_GET['producer_link'].'">' : '').
          (strlen ($_GET['product_id']) > 0 ? '<input type="hidden" name="product_id" value="'.$_GET['product_id'].'">' : '').
          (strlen ($_GET['query']) > 0 ? '<input type="hidden" name="query" value="'.$_GET['query'].'">' : '').'
          <!-- BUTTONS FOR NARROWING THE SELECTION -->
          <div class="sort_title">Narrow the List of Products</div>
            <input class="select_type_radio" type="radio" id="select_type_all" name="select_type" value="all"'.($unique['select_type'] == 'all' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="select_type_all">All Products<span class="detail">&nbsp;</span></label>
            <input class="select_type_radio" type="radio" id="select_type_for_sale" name="select_type" value="for_sale"'.($unique['select_type'] == 'for_sale' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="select_type_for_sale">Only for Sale<span class="detail">(Retail &amp; Wholesale)</span></label>
            <input class="select_type_radio" type="radio" id="select_type_not_archived" name="select_type" value="not_archived"'.($unique['select_type'] == 'not_archived' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="select_type_not_archived">Not Archived<span class="detail">Listed &amp; Unlisted</span></label>
            <input class="select_type_radio" type="radio" id="select_type_archived" name="select_type" value="archived"'.($unique['select_type'] == 'archived' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="select_type_archived">Archived<span class="detail">&nbsp;</span></label>'.
          (CurrentMember::auth_type('producer_admin') ? '
            <input class="select_type_radio" type="radio" id="select_type_admin_unconfirmed" name="select_type" value="admin_unconfirmed"'.($unique['select_type'] == 'admin_unconfirmed' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="select_type_admin_unconfirmed">Admin: Unconfirmed<span class="detail">(All Producers)</span></label>'
          : ''
          ).
          ($unique['select_type'] == 'versions' ? '
            <input class="select_type_radio" type="radio" id="select_type_versions" name="select_type" value="versions"'.($unique['select_type'] == 'versions' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="select_type_versions">Showing Versions<span class="detail">(Product #'.$data['product_id'].')</span></label>'
            : ''
            ).'
          <!-- BUTTONS FOR SORTING THE SELECTION -->
          <div class="sort_title">Select a sorting option</div>
            <input class="sort_type_radio" type="radio" id="sort_type_product_name" name="sort_type" value="product_name"'.($unique['sort_type'] == 'product_name' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_product_name">Product Name<span class="detail">name &gt; price</span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_product_id" name="sort_type" value="product_id"'.($unique['sort_type'] == 'product_id' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_product_id">Product ID<span class="detail">(descending order)</span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_category" name="sort_type" value="category"'.($unique['sort_type'] == 'category' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_category">Category<span class="detail">category &gt; subcategory</span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_storage" name="sort_type" value="storage"'.($unique['sort_type'] == 'storage' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_storage">Storage<span class="detail">[NON-REF-FROZ] &gt; category</span></span></label>
            <input class="sort_type_radio" type="radio" id="sort_type_listing_type" name="sort_type" value="listing_type"'.($unique['sort_type'] == 'listing_type' ? ' checked' : '').' onchange="document.forms[\'sort_option\'].submit()">
            <label for="sort_type_listing_type">Listing Type<span class="detail">[Ret-Whsl-Unl-Arch]</span></label>'.
          (strlen ($_GET['subcat_id']) > 0 ? '<input type="hidden" name="subcat_id" value="'.$_GET['subcat_id'].'">' : '').
          (strlen ($_GET['type']) > 0 ? '<input type="hidden" name="type" value="'.$_GET['type'].'">' : '').'
        </form>
      </div>';
    return $sorting_navigation;
  }


/*********************** ORDER CYCLE NAVIGATION SECTION *************************/

function order_cycle_navigation($data, &$unique)
  {
    // Order cycle navigation is not needed for producer listings
    $order_cycle_navigation = '';
    return $order_cycle_navigation;
  };

/*********************** PRODUCER LIST DIVISION OPEN/CLOSE *************************/

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
      <div id="product_list_table" class="producer_list select_type-'.(strlen($_GET['select_type']) > 0 ? $_GET['select_type'] : '').'">';
    // Do not open_list_top if this is for a single product display
    if ($unique['sort_type'] == 'email_product') $list_open = '';
    return $list_open;
  };


function close_list_bottom($data, &$unique)
  {
    $product_list_pager_bottom = pager_navigation ($data, $unique, 'product_list_pager_bottom');
    $list_close = '
    </div>'.
    $product_list_pager_bottom;
    // Do not close_list_bottom if this is for a single product display
    if ($unique['sort_type'] == 'email_product') $list_close = '';
    return $list_close;
  };

/************************** MAJOR DIVISION OPEN/CLOSE ****************************/

function major_division_open($data, &$unique, $major_division = NULL)
  {
    switch ($major_division)
      {
        // Major division on category or subcategory
        case 'category_id':
        case 'category_name':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="category">'.$data['category_name'].'</span>
              </header>';
          break;
        // Use for major division showing both category and subcategory
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="category">'.$data['category_name'].'</span>
                <span class="subcategory">'.$data['subcategory_name'].'</span>
              </header>';
          break;
        // Major division on producer
        case 'static_producer_id':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="static_title">'.$data['producer_name'].' '.$data['static_producer_id'].'</span>
              </header>';
          break;
        // Major division on producer_name
        case 'producer_name':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="static_title">'.$data['producer_name'].' Products by Number (descending)</span>
              </header>';
          break;
        // Major division on product_id -- used for listing versions
        case 'static_product_id':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="static_title">'.$data['static_product_id'].''.$data['product_id'].'</span>
              </header>';
          break;
        // Major division on product_id -- used for listing versions
        case 'static_confirmation_message':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="static_title">'.$data['static_confirmation_message'].'</span>
              </header>';
          break;
        // Major division on listing_auth_type
        case 'listing_auth_type':
          $header = '
            <div class="subpanel">
              <header class="header_major">
                <span class="static_title">'.
                  ($data['listing_auth_type'] == 'member' ? 'Products Retail to Members' : '').
                  ($data['listing_auth_type'] == 'institution' ? 'Products Wholesale to Institutions' : '').
                  ($data['listing_auth_type'] == 'unlisted' ? 'Unlisted Products' : '').
                  ($data['listing_auth_type'] == 'archived' ? 'Archived Products' : '').'
                  </span>
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
        // Minor division on subcategory -- showing both category and subcategory
        case 'subcategory_id':
          $header = '
        <h3 class="header_minor">'.
          $data['category_name'].' &mdash; '.$data['subcategory_name'].'
        </h3>';
          break;
        // Minor division on subcategory -- showing only subcategory
        case 'subcategory_name':
          $header = '
        <h3 class="header_minor">'.
          $data['subcategory_name'].'
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
        case 'listing_auth_type':
          $header = '
        <h3 class="header_minor">'.
          ($data['listing_auth_type'] == 'member' ? 'Products Retail to Members' : '').
          ($data['listing_auth_type'] == 'institution' ? 'Products Wholesale to Institutions' : '').
          ($data['listing_auth_type'] == 'unlisted' ? 'Unlisted Products' : '').
          ($data['listing_auth_type'] == 'archived' ? 'Archived Products' : '').'
        </h3>';
          break;
        // Minor division on product
        case 'product_id':
        case 'product_name':
          $header = '
        <div id="product_header-'.$data['product_id'].'-'.$data['product_version'].'">
          <div class="product">
            '.$data['image_display'].'
            <span class="product_id>'.$data['product_id'].'</span>
            <span class="product_name>'.$data['product_name'].'</span>'.
            $data['inventory_display'].$data['ordering_unit_display'].$data['random_weight_display'].'
            <span class="product_description">'.$data['product_description'].'</span>
          </div>
        </div>';
          break;
        // Major division on storage
        case 'storage_code':
        case 'storage_type':
          $header = '
        <h3 class="header_minor">'.
          $data['storage_code'].' &mdash; '.$data['storage_type'].'
        </h3>';
          break;
        // Minor division on member
        case 'member_id':
        case 'preferred_name':
          $header = '
        <div id="member_header-'.$data['member_id'].'">
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
    $minor_close = '';
    return $minor_close;
  };

/************************* DISPLAY INDIVIDUAL ROWS CONTENT **************************/

function show_listing_row($data, &$unique)
  {
    switch ($unique['row_type'])
      {
        // Row type
        case 'product':
          $row_content = '
      <div id="product_display-'.$data['product_id'].'-'.$data['product_version'].'" class="product_display '.$data['listing_auth_type'].($data['is_wholesale_item'] ? ' wholesale' : '').'">
        <div class="product'.($data['active'] == '1' ? ' active' : '').($data['approved'] == '0' ? ' unconfirmed' : '').'">'.
          $data['manage_inventory_control'].'
          <div class="product_title">
            <span class="product_id">#'.$data['product_id'].'
              <span class="product_version">'.$data['product_version'].'</span>
            </span>
            <span class="product_name">
            <a class="edit_product_link" onclick="popup_src(\''.PATH.'edit_product_info.php?display_as=popup&product_id='.$data['product_id'].'&product_version='.$data['product_version'].'&producer_id='.$data['producer_id'].'&action=edit\', \'edit_product\', \'\', false)">[EDIT]</a> '.
            $data['product_name'].'
            </span>
          </div>
          <span class="producer_name">
            <a href="'.PATH.'producers/'.$data['producer_link'].'">'.$data['producer_name'].'</a>
          </span>
          <span class="conf_status'.$data['approved'].'"> [ '.
            ($data['approved'] == 0 ? 'not approved ' : '').
            ($data['approved'] == 1 ? 'approved ' : '').
            ($data['approved'] == 2 ? 'rejected ' : '').
            ($data['active'] == 1 ? 'active ' : '').']
          </span>
          <div class="product_description" id="product_description-'.$data['product_id'].'-'.$data['product_version'].'">
            <div class="pricing">'.
              $data['image_display'].'
              <span class="conf_date">'.date ('Y-M-d H:i:s', strtotime ($data['modified'])).'</span>'.
              $data['pricing_display'].'
              <span class="prodtype">'.$data['prodtype'].'</span>
              <span class="storage_type">'.$data['storage_type'].'</span>
            </div>'.
              $data['product_description'].
            ($data['is_wholesale_item'] ? wholesale_text_html() : '').'
            <div class="ordering_details">'.
              $data['ordering_unit_display'].
              $data['random_weight_display'].'
            </div>
            <div class="listing_auth_type">
              For sale to:
              <span class="match_text institution"><span class="match">'.(strpos ($data['listing_auth_type'], 'institution') === false ? '&#9723;' : '&#9724;').'</span>Institutions</span>
              <span class="match_text member"><span class="match">'.(strpos ($data['listing_auth_type'], 'member') === false ? '&#9723;' : '&#9724;').'</span>Members</span>
              <span class="match_text unlisted"><span class="match">'.(strpos ($data['listing_auth_type'], 'unlisted') === false ? '&#9723;' : '&#9724;').'</span>Nobody (Unlisted)</span>
              <span class="match_text archive"><span class="match">'.(strpos ($data['listing_auth_type'], 'archive') === false ? '&#9723;' : '&#9724;').'</span>Nobody (Archived)</span>
            </div>'.
            (strpos ($data['listing_auth_type'], 'unlisted') === false ? '' : '<div class="banner unlisted">UNLISTED</div>').
            (strpos ($data['listing_auth_type'], 'archived') === false ? '' : '<div class="banner archive">ARCHIVED</div>').
            ($data['approved'] == 0 ? '<div class="banner unconfirmed">UNAPPROVED</div>' : '').
            ($data['active'] == 1 && $unique['select_type'] == 'versions' ? '<div class="banner active">ACTIVE</div>' : '').'
          </div>
        </div><!-- .product -->
      </div><!-- .product_display -->';
          break;
        // Row type
        case 'product_short':
          $row_content = '
      <div id="product_display-'.$data['product_id'].'-'.$data['product_version'].'"'.($data['is_wholesale_item'] ? ' class="wholesale"' : '').'>
        <div class="product_info">
          '.$data['manage_inventory_control'].'
          <div class="product_short">
            <strong>#'.$data['product_id'].' &ndash; '.$data['product_name'].'</strong>
            <br>'.$data['inventory_display'].$data['ordering_unit_display'].$data['random_weight_display'].'
          </div>
          <div class="storage">
            <span class="storage_code">'.$data['storage_code'].'</span><br><span class="storage_type">('.$data['storage_type'].')</span>
          </div>
          <div class="pricing">
            '.$data['pricing_display'].'
          </div>
          <div class="total">
            '.$data['total_display'].'
          </div>
        </div>';
          break;
        // Row type
        case 'email_product':
          $status_array = array();
          if ($data['approved'] == 0) array_push ($status_array, 'not approved');
          if ($data['approved'] == 1) array_push ($status_array, 'approved');
          if ($data['approved'] == 2) array_push ($status_array, 'rejected');
          if ($data['active'] == 1) array_push ($status_array, 'active');
          if ($data['active'] == 0) array_push ($status_array, 'inactive');
          $row_content = '
      <table border="1" cellpadding="5" width="80%">
        <tr>
          <td align="left" colspan="2">
            <h3>#'.$data['product_id'].'-'.$data['product_version'].' '.$data['product_name'].'</h3>
            <h4><a href="'.BASE_URL.PATH.'producers/'.$data['producer_link'].'">'.$data['producer_name'].'</a></h4>
            <p>[ '.(implode (' | ', $status_array)).']
            </p>

          </td>
        </tr>
        <tr>
          <td>'.
                ($data['image_id'] ? '
                <img width="150" src="'.str_replace('https', 'http', BASE_URL).get_image_path_by_id ($data['image_id']).'">'
                : '
                <img width="150" src="'.str_replace('https', 'http', BASE_URL).DIR_GRAPHICS.'no_image_set.png">' // (no https for email images)
                ).'
              <p>'.date ('Y-M-d H:i:s', strtotime ($data['modified'])).'</p>'.
              $data['pricing_display'].'
              <p>'.$data['prodtype'].'</p>
              <p>'.$data['storage_type'].'</p>
          </td>
          <td align="left" valign="top">'.
              $data['product_description'].
            ($data['is_wholesale_item'] ? '<p>** FEATURED WHOLESALE ITEM **</p>' : '').'
            <p>'.
              $data['ordering_unit_display'].
              $data['random_weight_display'].'
            </p>
            <p>
              For sale to:
              <ul style="list-style:none;">
                <li>'.(strpos ($data['listing_auth_type'], 'institution') === false ? '&#9723;' : '&#9724;').' Institutions</li>
                <li>'.(strpos ($data['listing_auth_type'], 'member') === false ? '&#9723;' : '&#9724;').' Members</li>
                <li>'.(strpos ($data['listing_auth_type'], 'unlisted') === false ? '&#9723;' : '&#9724;').' Nobody (Unlisted)</li>
                <li>'.(strpos ($data['listing_auth_type'], 'archive') === false ? '&#9723;' : '&#9724;').' Nobody (Archived)</li>
              </ul>
            </p>
            <ul style="list-style:none;">'.
              (strpos ($data['listing_auth_type'], 'unlisted') === false ? '' : '<li>UNLISTED</li>').
              (strpos ($data['listing_auth_type'], 'archived') === false ? '' : '<li>ARCHIVED</li>').
              ($data['approved'] == 0 ? '<li>UNAPPROVED</li>' : '').
              ($data['active'] == 1 && $unique['select_type'] == 'versions' ? '<li>ACTIVE</li>' : '').'
            </ul>
          </td>
        </tr>
      </table>';
          break;
        // Otherwise...
          $row_content = '
          ';
          break;
      }
    return $row_content;
  };
