<?php

valid_auth('producer,producer_admin');

// No zero-inventory exclusion
$where_zero_inventory = '';

// This is the producer's own listing (or admin access), so no restriction on producers
$where_producer_pending = '';

// Listing all versions of a product, which includes the un-confirmed versions
$where_confirmed = '';

// Default to allow listing everything
$where_auth_type = '';

// Set options for narrowing the list from ALL of the producers' products
// Set up defaults for narrowing the query

// $narrow_over_versions = '
//       LEFT OUTER JOIN '.NEW_TABLE_PRODUCTS.' '.NEW_TABLE_PRODUCTS.'2
//         ON ('.NEW_TABLE_PRODUCTS.'.product_id = '.NEW_TABLE_PRODUCTS.'2.product_id
//           AND IF(FIELD('.NEW_TABLE_PRODUCTS.'.confirmed, -1, 1) = 0, '.NEW_TABLE_PRODUCTS.'.product_version, FIELD('.NEW_TABLE_PRODUCTS.'.confirmed, -1, 1) + 999999)
//           <
//           IF(FIELD('.NEW_TABLE_PRODUCTS.'2.confirmed, -1, 1) = 0, '.NEW_TABLE_PRODUCTS.'2.product_version, FIELD('.NEW_TABLE_PRODUCTS.'2.confirmed, -1, 1) + 999999))';
$narrow_over_producer = '
      '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"';

$unique['select_type'] = $_GET['select_type'];

$unique['sort_type'] = 'inventory';
$major_division = 'static_inventory_title';
$major_division_prior = $major_division.'_prior';
$minor_division = 'inventory_description';
$minor_division_prior = $minor_division.'_prior';
$show_major_division = true;
$show_minor_division = true;
$order_by = '
  '.TABLE_INVENTORY.'.description ASC,
  '.NEW_TABLE_PRODUCTS.'.product_id ASC,
  '.NEW_TABLE_PRODUCTS.'.product_version DESC';
$select_special = '
  "Products Organized by Inventory Name" AS static_inventory_title,';


// What part of the product list are we selecting over?
if ($unique['select_type'] == 'full_list')
  {
    $narrow_over_versions_where = '';
    $subtitle = 'Full List';
    $unique['row_type'] = 'product_short'; // Show the product info
    $group_by = '
      '.NEW_TABLE_PRODUCTS.'.product_id,
      '.NEW_TABLE_PRODUCTS.'.product_version';
  }
elseif ($unique['select_type'] == 'full_active')
  {
//     $narrow_over_versions_where = '
//       AND '.NEW_TABLE_PRODUCTS.'2.product_id IS NULL';
    $subtitle = 'Active Versions Only';
    $unique['row_type'] = 'product_short'; // Show the product info
    $group_by = '
      '.NEW_TABLE_PRODUCTS.'.product_id';
    $where_confirmed = '
      AND '.NEW_TABLE_PRODUCTS.'.approved = "1"
      AND '.NEW_TABLE_PRODUCTS.'.active = "1"';
  }
elseif ($unique['select_type'] == 'full_for_sale')
  {
//     $narrow_over_versions_where = '
//       AND '.NEW_TABLE_PRODUCTS.'2.product_id IS NULL';
    $subtitle = 'Active / For Sale';
    $unique['row_type'] = 'product_short'; // Show the product info
    $group_by = '
      '.NEW_TABLE_PRODUCTS.'.product_id';
    $where_confirmed = '
      AND '.NEW_TABLE_PRODUCTS.'.approved = "1"
      AND '.NEW_TABLE_PRODUCTS.'.active = "1"
      AND ('.NEW_TABLE_PRODUCTS.'.listing_auth_type = "institution"
        OR '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member")';
  }
elseif ($unique['select_type'] == 'simple_list') // Default
  {
    $narrow_over_versions_where = '';
    $subtitle = 'Simple List';
    $unique['row_type'] = 'product_mini'; // Show the product info
    $group_by = '
      '.NEW_TABLE_PRODUCTS.'.product_id,
      '.NEW_TABLE_PRODUCTS.'.product_version';
  }
else // if ($unique['select_type'] == 'simple_active') // Default
  {
    $unique['select_type'] = 'simple_active'; // Make sure it is set since this is the default
    $narrow_over_versions_where = '';
    $subtitle = 'Simple List';
    $unique['row_type'] = 'product_mini'; // Show the product info
    $group_by = '
      '.NEW_TABLE_PRODUCTS.'.product_id,
      '.NEW_TABLE_PRODUCTS.'.product_version';
    $where_confirmed = '
      AND '.NEW_TABLE_PRODUCTS.'.approved = "1"
      AND '.NEW_TABLE_PRODUCTS.'.active = "1"
      AND (listing_auth_type = "institution" OR listing_auth_type = "member")';
  }

// Assign page tab and title information
$page_title_html = '<span class="title">'.(strlen($title) > 0 ? $title : $_SESSION['producer_business_name']).'</span>';
// $page_subtitle_html = '<span class="subtitle">'.$subtitle.'</span>';
$page_title = $_SESSION['producer_business_name'].' Products';
$page_tab = 'producer_panel';

// Assign template file
$template_type = 'inventory_list';

// Execute the main product_list query
$query = '
  SELECT
    SQL_CALC_FOUND_ROWS'.
    $select_special.'
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.pvid,
    '.NEW_TABLE_PRODUCTS.'.product_version,
    '.NEW_TABLE_PRODUCTS.'.producer_id,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.NEW_TABLE_PRODUCTS.'.account_number,
    '.NEW_TABLE_PRODUCTS.'.inventory_pull,
    '.NEW_TABLE_PRODUCTS.'.inventory_id,
    '.TABLE_INVENTORY.'.description AS inventory_description,
    '.NEW_TABLE_PRODUCTS.'.product_description,
    '.NEW_TABLE_PRODUCTS.'.subcategory_id,
    '.NEW_TABLE_PRODUCTS.'.future_delivery,
    '.NEW_TABLE_PRODUCTS.'.future_delivery_type,
    '.NEW_TABLE_PRODUCTS.'.production_type_id,
    '.NEW_TABLE_PRODUCTS.'.unit_price,
    '.NEW_TABLE_PRODUCTS.'.pricing_unit,
    '.NEW_TABLE_PRODUCTS.'.ordering_unit,
    '.NEW_TABLE_PRODUCTS.'.random_weight,
    '.NEW_TABLE_PRODUCTS.'.meat_weight_type,
    '.NEW_TABLE_PRODUCTS.'.minimum_weight,
    '.NEW_TABLE_PRODUCTS.'.maximum_weight,
    '.NEW_TABLE_PRODUCTS.'.extra_charge,
    '.NEW_TABLE_PRODUCTS.'.product_fee_percent,
    '.NEW_TABLE_PRODUCTS.'.image_id,
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type,
    '.NEW_TABLE_PRODUCTS.'.taxable,
    '.NEW_TABLE_PRODUCTS.'.approved,
    '.NEW_TABLE_PRODUCTS.'.active,
    '.NEW_TABLE_PRODUCTS.'.confirmed,
    '.NEW_TABLE_PRODUCTS.'.retail_staple,
    '.NEW_TABLE_PRODUCTS.'.staple_type,
    '.NEW_TABLE_PRODUCTS.'.created,
    '.NEW_TABLE_PRODUCTS.'.modified,
    '.NEW_TABLE_PRODUCTS.'.tangible,
    '.NEW_TABLE_PRODUCTS.'.sticky,
    '.NEW_TABLE_PRODUCTS.'.hide_from_invoice,
    '.NEW_TABLE_PRODUCTS.'.storage_id,
    '.NEW_TABLE_PRODUCTS.'.modified,
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name AS producer_name,
    (SELECT business_name FROM '.TABLE_PRODUCER.' WHERE producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'") AS producer_name_you,
    '.TABLE_PRODUCER.'.producer_link,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.*,
    '.TABLE_INVENTORY.'.quantity AS inventory_quantity,
    '.TABLE_INVENTORY.'.quantity AS bucket_quantity,
    (SELECT
      SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
      WHERE delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
      AND product_id = '.NEW_TABLE_PRODUCTS.'.product_id
      AND product_version = '.NEW_TABLE_PRODUCTS.'.product_version
      ) AS ordered_quantity,'.
    $total_ordered_clause.'
    "'.mysqli_real_escape_string ($connection, $delivery_id).'" AS delivery_id
  FROM
    '.TABLE_INVENTORY.'
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'"'.
  $narrow_over_versions.'
  WHERE'.
    $narrow_over_producer.
    $narrow_over_versions_where.
    $where_producer_pending.
    $where_misc.
    $where_zero_inventory.
    $where_confirmed.
    $where_auth_type.'
  GROUP BY'.
    $group_by.'
  ORDER BY'.
    $order_by;
// debug_print ("INFO ", array ('QUERY'=>$query), basename(__FILE__).' LINE '.__LINE__)
// echo "<pre>$query</pre>";
