<?php

// valid_auth(''); // anyone can view these pages

// Set up for searching to refine selections:
$search_select = '';
$search_order_by = ''; // Bogus ORDER BY term for queries lacking a search term
$having_search = '';
if (strlen ($_GET['query']) > 0)
  {
    // The following is probably not the most correct query but it provides for
    // textual search matching like [ melon -water ] to get melons that are not
    // watermelons.  It also allows for searching on product numbers, but not as
    // part of the above boolean logic... so [ melon -1000] will find all melons
    // as well as product #1000.  NOTE:  If the product_id field is combined in
    // the first MATCH, results are cast as binary and become case-sensitive...
    // so Melon, melon, and MELON find different results.
    // Sanitize any search query
    $search_query = mysqli_real_escape_string ($connection, strtr (preg_replace ('/[^0-9A-Za-z\-\+\~\<\>\"\*\(\)\@ ]/','', $_GET['query']), '"', ' '));
    // Weight the search according to the column where found
    $search_select = '
      (2.5 * MATCH ('.NEW_TABLE_PRODUCTS.'.product_name)
        AGAINST (\''.$search_query.'\' IN BOOLEAN MODE) +
      1.0 * MATCH ('.NEW_TABLE_PRODUCTS.'.product_description)
        AGAINST (\''.$search_query.'\' IN BOOLEAN MODE) +
      1.5 * MATCH ('.TABLE_SUBCATEGORY.'.subcategory_name)
        AGAINST (\''.$search_query.'\' IN BOOLEAN MODE) +
      1.5 * MATCH ('.TABLE_CATEGORY.'.category_name)
        AGAINST (\''.$search_query.'\' IN BOOLEAN MODE)) AS search_score,';
    $search_order_by = '
      search_score DESC'; // Sometimes comes first in ordering; sometimes last, so can't pre/postpend a comma)
    $having_search = '
      AND search_score > 0';
    $unique['search_query'] = $search_query;
  }


if (isset ($_SESSION['member_id'])) // If the member is logged in, then use their information
  {
    $where_member = '
    AND '.TABLE_MEMBER.'.member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"';
  }

// The where_catsubcat constraints are only used in the type='new' option (called from category_list.php)
// NOTE: The $_REQUEST['subcat_id'] is sometimes passed with extra garbage like this: 129\'A=0 (From Windows?)
// So we need to sanitize it
// Changed from $_REQUEST to $_GET ... see if that helps!

if ($_GET['subcat_id']) $where_catsubcat = '
    AND '.NEW_TABLE_PRODUCTS.'.subcategory_id = '.mysqli_real_escape_string ($connection, $_GET['subcat_id']);
if ($_GET['category_id']) $where_catsubcat = '
    AND (
      '.TABLE_CATEGORY.'.category_id = '.mysqli_real_escape_string ($connection, $_GET['category_id']).'
      OR
      '.TABLE_CATEGORY.'.parent_id = '.mysqli_real_escape_string ($connection, $_GET['category_id']).'
        )';
$unique['delivery_id'] = $delivery_id;
$unique['member_id'] = $member_id;
$unique['basket_id'] = $basket_id;
// Set display grouping defaults
$template_type = 'customer_list';
$unique['row_type'] = 'product';
$order_by = (strlen ($search_order_by) > 0 ? $search_order_by.',' : '').'
    '.TABLE_CATEGORY.'.sort_order ASC,
    '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
    '.TABLE_PRODUCER.'.business_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC,
    '.NEW_TABLE_PRODUCTS.'.unit_price ASC';
// Be sure to show products that customer has in basket even if zero inventory available
$having_real_inventory = '
  HAVING (
    (
      (inventory_pull_quantity > 0
        AND inventory_id > 0)
      OR inventory_id = 0
      OR '.(EXCLUDE_ZERO_INV == true ? '0' : '1').'
    )
    AND (
      COALESCE(basket_quantity, 0) > 0
      OR availability = 1)'.
    $having_search.')';

// Set up sorting options for these pages (how the selection is sorted)
$unique['sort_type'] = $_GET['sort_type'];
// Set narrowing options for these pages (reduces the selection set)
$unique['select_type'] = $_GET['select_type'];

// Set most-common values so we only need to deal with exceptions
// All queries (except previous_orders) will use this constraint
$basket_join_constraint = '
  LEFT JOIN '.NEW_TABLE_BASKETS.' ON(basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'")
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON
    ('.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id
    AND '.NEW_TABLE_BASKET_ITEMS.'.product_version = '.NEW_TABLE_PRODUCTS.'.product_version
    AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
    AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id > 0)';
$special_select = '
    COALESCE('.NEW_TABLE_BASKET_ITEMS.'.quantity, 0) AS basket_quantity,';

if ($unique['select_type'] == 'previously_ordered') // Check these first to short-circuit other options
  {
    if ($unique['sort_type'] == 'recently_ordered')
      {
        $major_division = 'ordered_for_date';
        $major_division_prior = $major_division.'_prior';
        $minor_division = 'product_name';
        $minor_division_prior = $minor_division.'_prior';
        $show_major_division = true;
        $show_minor_division = true;
        $order_by = '
          MAX('.TABLE_ORDER_CYCLES.'.delivery_date) DESC,
          '.NEW_TABLE_PRODUCTS.'.product_name ASC'.
          (strlen ($search_order_by) > 0 ? ','.$search_order_by : '');
      }
    else // if ($unique['sort_type'] == 'frequently_ordered') // Defalt for previously_ordered sorting
      {
        $major_division = 'number_of_orders';
        $major_division_prior = $major_division.'_prior';
        $minor_division = 'ordered_for_date';
        $minor_division_prior = $minor_division.'_prior';
        $show_major_division = true;
        $show_minor_division = true;
        $order_by = '
          COUNT('.NEW_TABLE_BASKET_ITEMS.'.bpid) DESC,
          MAX('.TABLE_ORDER_CYCLES.'.delivery_date) DESC'.
          (strlen ($search_order_by) > 0 ? ','.$search_order_by : '');
        $unique['sort_type'] = 'frequently_ordered';
      }
  }
elseif ($unique['sort_type'] == 'storage')
  {
    $major_division = 'storage_code';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'category_id';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $order_by = (strlen ($search_order_by) > 0 ? $search_order_by.',' : '').'
      FIELD('.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code, "FROZ", "REF", "NON") DESC,
      '.TABLE_CATEGORY.'.category_name ASC,
      '.NEW_TABLE_PRODUCTS.'.product_name ASC';
  }
elseif ($unique['sort_type'] == 'product_name')
  {
    $major_division = 'major_division_special_title';
    $major_division_prior = $major_division.'_prior';
    $minor_division = '';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = false;
    $special_select .= '
      "Products Listed Alphabetically" AS major_division_special_title,';
    $order_by = (strlen ($search_order_by) > 0 ? $search_order_by.',' : '').'
      '.NEW_TABLE_PRODUCTS.'.product_name ASC,
      '.NEW_TABLE_PRODUCTS.'.product_id ASC';
  }
elseif ($unique['sort_type'] == 'producer')
  {
    $major_division = 'producer_id';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'subcategory_id';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $order_by = (strlen ($search_order_by) > 0 ? $search_order_by.',' : '').'
      '.TABLE_PRODUCER.'.business_name ASC,
      '.TABLE_CATEGORY.'.category_name ASC,
      '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
      '.NEW_TABLE_PRODUCTS.'.product_name ASC';
  }
else // if ($unique['sort_type'] == 'category') // DEFAULT OPTION
  {
    $major_division = 'subcategory_id';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'producer_id';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $order_by = (strlen ($search_order_by) > 0 ? $search_order_by.',' : '').'
      '.TABLE_CATEGORY.'.category_name ASC,
      '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
      '.TABLE_PRODUCER.'.business_name ASC,
      '.NEW_TABLE_PRODUCTS.'.product_name ASC,
      '.NEW_TABLE_PRODUCTS.'.product_id ASC';
    $unique['sort_type'] = 'category'; // Set explicitly since this is the default
  }

// What part of the product list are we selecting over?
if ($unique['select_type'] == 'producer_id')
  {
    $unique['show_producer_details'] = true;
    $unique['row_type'] = 'product';
    // Select only producer_id = $_GET['producer_id']
    $narrow_over_products = '
      AND '.TABLE_PRODUCER.'.producer_id = "'.mysqli_real_escape_string ($connection, $_GET['producer_id']).'"';
    // Use this to load unique data about the producer for display
    $query_unique = '
      SELECT
        producer_id,
        business_name,
        producer_link,
        producttypes,
        about,
        ingredients,
        general_practices,
        highlights,
        additional,
        logo_id
      FROM
        '.TABLE_PRODUCER.'
      LEFT JOIN '.TABLE_PRODUCER_LOGOS.' USING(producer_id)
      WHERE
        '.TABLE_PRODUCER.'.producer_id = "'.mysqli_real_escape_string ($connection, $_GET['producer_id']).'"';
    $result_unique = mysqli_query ($connection, $query_unique) or die(debug_print ("ERROR: 574032 ", array ($query_unique,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row_unique = mysqli_fetch_array ($result_unique, MYSQLI_ASSOC))
      {
        // Add these values to the $unique array
        $unique = array_merge ($unique, (array) $row_unique);
      }
    $subtitle = 'Products from '.$unique['business_name'];
  }
elseif ($unique['select_type'] == 'producer_link')
  {
    $unique['show_producer_details'] = true;
    $unique['row_type'] = 'product';
    // Select only producer_id = $_GET['producer_id']
    $narrow_over_products = '
      AND '.TABLE_PRODUCER.'.producer_link = "'.mysqli_real_escape_string ($connection, $_GET['producer_link']).'"';
    // Use this to load unique data about the producer for display
    $query_unique = '
      SELECT
        producer_id,
        business_name,
        producer_link,
        producttypes,
        about,
        ingredients,
        general_practices,
        highlights,
        additional,
        logo_id
      FROM
        '.TABLE_PRODUCER.'
      LEFT JOIN '.TABLE_PRODUCER_LOGOS.' USING(producer_id)
      WHERE
        '.TABLE_PRODUCER.'.producer_link = "'.mysqli_real_escape_string ($connection, $_GET['producer_link']).'"';
    $result_unique = mysqli_query ($connection, $query_unique) or die(debug_print ("ERROR: 574032 ", array ($query_unique,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row_unique = mysqli_fetch_array ($result_unique, MYSQLI_ASSOC))
      {
        // Add these values to the $unique array
        $unique = array_merge ($unique, (array) $row_unique);
      }
    $subtitle = 'Products from '.$unique['business_name'];
  }
elseif ($unique['select_type'] == 'organic')
  {
    $unique['show_producer_details'] = false;
    $unique['row_type'] = 'product';
    // Select only producer_id = $_GET['producer_id']
    $narrow_over_products = '
      AND '.TABLE_PRODUCT_TYPES.'.prodtype LIKE "%organic%"';
    $subtitle = 'All, Part, or As Organic';
  }
elseif ($unique['select_type'] == 'wholesale' && CurrentMember::auth_type('institution'))
  {
    $unique['show_producer_details'] = false;
    $unique['row_type'] = 'product';
    $narrow_over_products = '
    AND '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "institution"';
    $subtitle = 'Wholesale Products';
  }
elseif ($unique['select_type'] == 'new_changed')
  {
    $unique['show_producer_details'] = false;
    $unique['row_type'] = 'product';
    $narrow_over_products = '
    AND (DATEDIFF(NOW(), '.NEW_TABLE_PRODUCTS.'.created) <= '.DAYS_CONSIDERED_NEW.'
     OR DATEDIFF(NOW(), '.NEW_TABLE_PRODUCTS.'.modified) <= '.DAYS_CONSIDERED_CHANGED.')';
    $subtitle = 'New &amp; Changed Products';
  }
elseif ($unique['select_type'] == 'previously_ordered')
  {
    $unique['show_producer_details'] = false;
    $unique['row_type'] = 'product';
    $special_select .= '
    COUNT('.NEW_TABLE_BASKET_ITEMS.'.product_id) AS number_of_orders,
    MAX('.TABLE_ORDER_CYCLES.'.delivery_date) AS ordered_for_date,
    (SELECT checked_out
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'"
      AND product_id='.NEW_TABLE_PRODUCTS.'.product_id) AS checked_out,
    COALESCE((SELECT quantity
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'"
      AND product_id='.NEW_TABLE_PRODUCTS.'.product_id), 0) AS basket_quantity,';
    // We want all (prior) baskets but only those connected to baskets for this member
    $basket_join_constraint = '
  LEFT JOIN '.NEW_TABLE_BASKETS.' ON('.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $member_id).'")
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON
    ('.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id
    AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id)';
    $narrow_over_products = '
    AND '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"
    AND '.NEW_TABLE_BASKET_ITEMS.'.quantity IS NOT NULL'; // Weeds out the empty baskets;
    $subtitle = 'Previously Ordered and Available Now';
  }
elseif ($unique['select_type'] != 'previously_ordered'
        || $unique['select_type'] == 'all')
  {
    $unique['show_producer_details'] = false;
    $unique['row_type'] = 'product';
    $narrow_over_products = '';
    $unique['select_type'] = 'all';
    // Set default subtitle. Might be clobbered in the product_list.php
    $subtitle = 'Everything Currently Available';
  }

// debug_print ("INFO", array ('sort_type'=>$unique['sort_type'], 'select_type'=> $unique['select_type'], $_GET), basename(__FILE__).' LINE '.__LINE__);

// Default page tab and title information
$page_title_html = '<span class="title">Product List</span>';
// $page_subtitle_html = '<span class="subtitle">'.$subtitle.'</span>';
$page_title = 'Product List';
$page_tab = 'shopping_panel';

// For logged-in users, we will associate database tables on their basket.site_id
// For others, we will associate on either $_SESSION['ofs_customer']['site_id'] OR $_COOKIE['ofs_customer[site_id]']
// Configure to use the availability matrix -- or not
if (USE_AVAILABILITY_MATRIX == true)
  {
    // Default to use the current basket site_id, if it exists
    $ofs_customer_site_id = CurrentBasket::site_id();
    // If not, then use the session site_id, if it exists
    if (! $ofs_customer_site_id) $ofs_customer_site_id = $_SESSION['ofs_customer']['site_id'];
    // If not, then use the cookie site_id, if it exists
    if (! $ofs_customer_site_id) $ofs_customer_site_id = $_COOKIE['ofs_customer']['site_id'];
    // Now set query values...
    $select_availability = '
    IF ('.TABLE_AVAILABILITY.'.site_id = "'.$ofs_customer_site_id.'", 1, 0) AS availability,
    '.NEW_TABLE_SITES.'.site_long AS site_long_you,';
    $join_availability = '
  LEFT JOIN '.TABLE_AVAILABILITY.' ON (
    '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_AVAILABILITY.'.site_id = "'.$ofs_customer_site_id.'")
  LEFT JOIN '.NEW_TABLE_SITES.' ON '.NEW_TABLE_SITES.'.site_id = "'.$ofs_customer_site_id.'"';
  }
else
  {
    $select_availability = '
    1 AS availability,';
    $join_availability = '';
  }

// Execute the main product_list query
$query = '
  SELECT
    SQL_CALC_FOUND_ROWS'.
    $special_select.
    $search_select.'
    COALESCE('.NEW_TABLE_BASKETS.'.customer_fee_percent, (SELECT customer_fee_percent FROM '.TABLE_MEMBER.' WHERE member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'")) AS customer_fee_percent,
    COUNT('.TABLE_SUBCATEGORY.'.subcategory_id) AS subcat_count,
    DATEDIFF(NOW(), '.NEW_TABLE_PRODUCTS.'.created) AS days_new,
    DATEDIFF(NOW(), '.NEW_TABLE_PRODUCTS.'.modified) AS days_changed,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_pull_quantity,
    IF('.TABLE_PRODUCER.'.unlisted_producer < 1, COUNT('.NEW_TABLE_PRODUCTS.'.product_id), 0) AS product_count,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    (SELECT SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE product_id = '.NEW_TABLE_PRODUCTS.'.product_id
        AND basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'") AS all_versions_quantity,
    '.NEW_TABLE_PRODUCTS.'.approved,
    '.NEW_TABLE_PRODUCTS.'.active,
    '.NEW_TABLE_PRODUCTS.'.confirmed,
    '.NEW_TABLE_PRODUCTS.'.extra_charge,
    '.NEW_TABLE_PRODUCTS.'.image_id,
    '.NEW_TABLE_PRODUCTS.'.inventory_id,
    '.NEW_TABLE_PRODUCTS.'.inventory_pull,
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type,
    '.NEW_TABLE_PRODUCTS.'.maximum_weight,
    '.NEW_TABLE_PRODUCTS.'.meat_weight_type,
    '.NEW_TABLE_PRODUCTS.'.minimum_weight,
    '.NEW_TABLE_PRODUCTS.'.ordering_unit,
    '.NEW_TABLE_PRODUCTS.'.pricing_unit,
    '.NEW_TABLE_PRODUCTS.'.product_description,
    '.NEW_TABLE_PRODUCTS.'.product_fee_percent,
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.NEW_TABLE_PRODUCTS.'.product_version,
    '.NEW_TABLE_PRODUCTS.'.random_weight,
    '.NEW_TABLE_PRODUCTS.'.sticky,
    '.NEW_TABLE_PRODUCTS.'.tangible,
    '.NEW_TABLE_PRODUCTS.'.taxable,
    '.NEW_TABLE_PRODUCTS.'.unit_price,'.
    $select_availability.'
    '.TABLE_CATEGORY.'.category_id,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_CATEGORY.'.sort_order,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_PRODUCER_LOGOS.'.logo_id,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.producer_link,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_type,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_SUBCATEGORY.'.subcategory_fee_percent,
    '.TABLE_SUBCATEGORY.'.subcategory_id,
    '.TABLE_SUBCATEGORY.'.subcategory_name
  FROM
    ('.NEW_TABLE_PRODUCTS.',
    '.TABLE_MEMBER.')
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.TABLE_PRODUCER_LOGOS.' ON '.TABLE_PRODUCER.'.producer_id = '.TABLE_PRODUCER_LOGOS.'.producer_id'.
  $basket_join_constraint.
  $join_availability.'
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    active = "1"'.
    $where_member.
    $where_producer_pending.
    $where_unlisted_producer.
    $narrow_over_products.
    $where_catsubcat.
    $where_zero_inventory.
    $where_confirmed.'
    AND FIND_IN_SET('.NEW_TABLE_PRODUCTS.'.listing_auth_type, '.TABLE_MEMBER.'.auth_type) > 0
  GROUP BY CONCAT('.NEW_TABLE_PRODUCTS.'.product_id, "-", '.NEW_TABLE_PRODUCTS.'.product_version)'.
    $having_real_inventory.'
  ORDER BY'.
    $order_by;
// echo "<pre>$query</pre>";
// debug_print ("INFO CUSTOMER_LIST_QUERY ", array ('query'=>$query), basename(__FILE__).' LINE '.__LINE__);
