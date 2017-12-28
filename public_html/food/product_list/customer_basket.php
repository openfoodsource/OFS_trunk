<?php
valid_auth('member');

// $where_misc = '
//     AND (
//       '.NEW_TABLE_BASKET_ITEMS.'.basket_id = "'.mysqli_real_escape_string ($connection, $basket_id).'"
//       OR (
//         '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"
//         AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"))';
$where_misc = '
    (
      '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"
      AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'")';

$order_by = '
    '.TABLE_CATEGORY.'.sort_order ASC,
    '.TABLE_PRODUCER.'.business_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC,
    '.NEW_TABLE_PRODUCTS.'.unit_price ASC';

// Assign page tab and title information
$page_title_html = '<span class="title">Shopping Basket</span>';
$subtitle = $_SESSION['show_name'];
// $page_subtitle_html = '<span class="subtitle">'.$_SESSION['show_name'].'</span>';
$page_title = 'Shopping Basket Items';
$page_tab = 'shopping_panel';

// Set display groupings
$major_division = 'major_division_empty_title'; // Pick a row that is not zero and will match every product
$special_select = '
  "1" AS major_division_empty_title,';
$major_division_prior = $major_division.'_prior';
$minor_division = 'business_name';
$minor_division_prior = $minor_division.'_prior';
$show_major_division = true;
$show_minor_division = true;

// This single-row content is unique for the entire report (used in the header, footer, etc)
$query_unique = '
  SELECT
    '.TABLE_MEMBER.'.address_line1,
    '.TABLE_MEMBER.'.address_line2,
    '.TABLE_MEMBER.'.auth_type,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.city,
    '.TABLE_MEMBER.'.state,
    '.TABLE_MEMBER.'.zip,
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.email_address_2,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_MEMBER.'.home_phone,
    '.TABLE_MEMBER.'.mobile_phone,
    '.TABLE_MEMBER.'.work_phone,
    '.TABLE_MEMBER.'.fax,
    '.TABLE_MEMBER.'.work_address_line1,
    '.TABLE_MEMBER.'.work_address_line2,
    '.TABLE_MEMBER.'.work_city,
    '.TABLE_MEMBER.'.work_state,
    '.TABLE_MEMBER.'.work_zip,
    '.NEW_TABLE_SITES.'.hub_id,
    '.NEW_TABLE_SITES.'.delivery_type,
    '.NEW_TABLE_SITES.'.truck_code,
    '.NEW_TABLE_SITES.'.site_short,
    '.NEW_TABLE_SITES.'.site_long,
    '.NEW_TABLE_SITES.'.site_description,
    '.NEW_TABLE_BASKETS.'.site_id,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.delivery_cost,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.delivery_postal_code,
    '.NEW_TABLE_BASKETS.'.customer_fee_percent,
    '.NEW_TABLE_BASKETS.'.checked_out,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_ORDER_CYCLES.'.invoice_price,
    '.TABLE_ORDER_CYCLES.'.msg_all,
    '.TABLE_ORDER_CYCLES.'.msg_bottom
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  LEFT JOIN '.NEW_TABLE_SITES.' USING (site_id)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING (delivery_id)
  WHERE
    '.TABLE_MEMBER.'.member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"
    AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"';
$result_unique = mysqli_query ($connection, $query_unique) or die(debug_print ("ERROR: 863023 ", array ($query_unique,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
if ($row_unique = mysqli_fetch_array ($result_unique, MYSQLI_ASSOC))
  {
    $unique = (array) $row_unique;
  }
$unique['row_type'] = 'product';

// Assign template file
$template_type = 'customer_basket';

// Configure to use the availability matrix -- or not
if (USE_AVAILABILITY_MATRIX == true)
  {
    $select_availability = '
    IF ('.TABLE_AVAILABILITY.'.site_id = '.NEW_TABLE_BASKETS.'.site_id, 1, 0) AS availability,';
    $join_availability = '
  LEFT JOIN '.TABLE_AVAILABILITY.' ON (
    '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_AVAILABILITY.'.site_id = '.NEW_TABLE_BASKETS.'.site_id)';
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
    $special_select.'
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_pull_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.checked_out,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.product_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.checked_out AS basket_checked_out,
    '.NEW_TABLE_BASKETS.'.customer_fee_percent,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.delivery_postal_code,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_MESSAGES.'.message AS customer_message,
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
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.NEW_TABLE_PRODUCTS.'.product_version,
    '.NEW_TABLE_PRODUCTS.'.random_weight,
    '.NEW_TABLE_PRODUCTS.'.sticky,
    '.NEW_TABLE_PRODUCTS.'.tangible,
    '.NEW_TABLE_PRODUCTS.'.taxable,
    '.NEW_TABLE_PRODUCTS.'.unit_price,
    '.NEW_TABLE_SITES.'.site_id,
    '.NEW_TABLE_SITES.'.site_long,
    '.NEW_TABLE_SITES.'.site_short AS site_short_you,
    '.NEW_TABLE_SITES.'.site_long AS site_long_you,'.
    $select_availability.'
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_CATEGORY.'.sort_order,
    '.TABLE_MEMBER.'.auth_type,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.producer_link,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_type,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    active_product_version.product_version AS active_product_version
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(basket_id)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' active_product_version ON (
    '.NEW_TABLE_PRODUCTS.'.product_id = active_product_version.product_id
    AND active_product_version.active = 1)
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id'.
  $join_availability.'
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  LEFT JOIN '.TABLE_MEMBER.' ON FIND_IN_SET('.NEW_TABLE_PRODUCTS.'.listing_auth_type, auth_type) > 0
  LEFT JOIN '.NEW_TABLE_SITES.' ON '.NEW_TABLE_BASKETS.'.site_id = '.NEW_TABLE_SITES.'.site_id
  LEFT JOIN '.NEW_TABLE_MESSAGES.' ON (referenced_key1 = bpid AND message_type_id =
    (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "customer notes to producer"))
  WHERE'.
    // $where_producer_pending.
    // $where_unlisted_producer.
    $where_misc.
    // $where_zero_inventory.
    // $where_confirmed.
    // $where_auth_type.
    '
  GROUP BY CONCAT('.NEW_TABLE_PRODUCTS.'.product_id, "-", '.NEW_TABLE_PRODUCTS.'.product_version)
  ORDER BY'.
    $order_by;
