<?php

valid_auth('producer,producer_admin');

// Do not show search on non-shopping pages
$show_search = false;

// No zero-inventory exclusion for producers' own products
$where_zero_inventory = '';

// This is the producer's own listing, so no restriction on producers
$where_producer_pending = '
    1';

// For producer product list, show both the wholesale and retail prices... unless it is a wholesale-only product
$display_wholesale_price_true = 1;  // Force display
$display_retail_price_true = 1;     // Force display

// Listing all versions of a product, which includes the un-confirmed ones
$where_confirmed = '';

// Showing all versions, so no restrict by listing_auth_type_condition
$where_auth_type = '';

// Producer admin is allowed to see the versions for anyone...
if (CurrentMember::auth_type('producer_admin'))
  $where_misc = '
    AND '.NEW_TABLE_PRODUCTS.'.product_id = "'.mysql_real_escape_string ($_GET['product_id']).'"';
else
  $where_misc = '
    AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysql_real_escape_string ($producer_id_you).'"
    AND '.NEW_TABLE_PRODUCTS.'.product_id = "'.mysql_real_escape_string ($_GET['product_id']).'"';

$order_by = '
    '.TABLE_CATEGORY.'.sort_order ASC,
    '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_version DESC';

// Assign page tab and title information
$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '<span class="subtitle">Listed by Category</span>';
$page_title = 'Products: Listed by Category';
$page_tab = 'producer_panel';

// Set display groupings
$major_division = 'product_version';
$major_division_prior = $major_division.'_prior';
$minor_division = '';
$minor_division_prior = $minor_division.'_prior';
$show_major_division = true;
$show_minor_division = false;
$row_type = 'product'; // Reflects the detail to show on each row (vs. what gets featured in the header)

// Assign template file
$template_type = 'producer_list';

// Execute the main product_list query
$query = '
  SELECT
    SQL_CALC_FOUND_ROWS
    '.NEW_TABLE_PRODUCTS.'.*,
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name AS producer_name,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.*,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    (SELECT GROUP_CONCAT(site_id) FROM '.TABLE_AVAILABILITY.' WHERE '.TABLE_AVAILABILITY.'.producer_id='.NEW_TABLE_PRODUCTS.'.producer_id) AS availability_list
    /* GROUP_CONCAT(CONCAT_WS(",", '.TABLE_AVAILABILITY.'.site_id)) AS availability_list */
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN '.TABLE_AVAILABILITY.' ON '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"
  WHERE'.
    $where_producer_pending.
//     $where_unlisted_producer.    Okay to show unlisted/suspended producers their own products
    $where_misc.
    $where_zero_inventory.
    $where_confirmed.
    $where_auth_type.'
  GROUP BY CONCAT('.NEW_TABLE_PRODUCTS.'.product_id, "-", '.NEW_TABLE_PRODUCTS.'.product_version)
  ORDER BY'.
    $order_by;
?>