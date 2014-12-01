<?php

valid_auth('producer,producer_admin');

// Do not show search on non-shopping pages
$show_search = false;

$where_misc = '
    AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string($delivery_id).'"
    AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysql_real_escape_string($producer_id_you).'"';

$order_by = '
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.NEW_TABLE_SITES.'.site_long ASC,
    '.TABLE_MEMBER.'.member_id ASC';

// Clobber the listing_auth_type for producer baskets
$where_auth_type = '';

// Assign page tab and title information
$page_title_html = '<span class="title">Basket</span>';
$page_subtitle_html = '<span class="subtitle">Basket Items</span>';
$page_title = 'Basket: Basket Items';
$page_tab = 'shopping_panel';

// Assign template file
$template_type = 'labels';

// Set display groupings
$major_division = 'storage_code';
$major_division_prior = $major_division.'_prior';
$minor_division = 'member_id';
$minor_division_prior = $minor_division.'_prior';
$show_major_division = false;
$show_minor_division = true;
$row_type = 'product_short'; // Reflects the detail to show on each row (vs. what gets featured in the header)

// Execute the main product_list query
$query = '
  SELECT
    SQL_CALC_FOUND_ROWS
    DISTINCT('.NEW_TABLE_BASKET_ITEMS.'.bpid),
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    '.NEW_TABLE_BASKET_ITEMS.'.product_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.product_version,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.NEW_TABLE_PRODUCTS.'.inventory_pull,
    '.NEW_TABLE_PRODUCTS.'.inventory_id,
    '.NEW_TABLE_PRODUCTS.'.product_description,
    '.NEW_TABLE_PRODUCTS.'.unit_price,
    '.NEW_TABLE_PRODUCTS.'.pricing_unit,
    '.NEW_TABLE_PRODUCTS.'.ordering_unit,
    '.NEW_TABLE_PRODUCTS.'.random_weight,
    '.NEW_TABLE_PRODUCTS.'.meat_weight_type,
    '.NEW_TABLE_PRODUCTS.'.minimum_weight,
    '.NEW_TABLE_PRODUCTS.'.maximum_weight,
    '.NEW_TABLE_PRODUCTS.'.extra_charge,
    '.NEW_TABLE_PRODUCTS.'.image_id,
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type,
    '.NEW_TABLE_PRODUCTS.'.taxable,
    '.NEW_TABLE_PRODUCTS.'.tangible,
    '.NEW_TABLE_PRODUCTS.'.sticky,
    '.NEW_TABLE_PRODUCTS.'.confirmed,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_CATEGORY.'.sort_order,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.home_phone,
    '.TABLE_MEMBER.'.work_phone,
    '.TABLE_MEMBER.'.mobile_phone,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCER.'.business_name AS producer_name,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_type,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.NEW_TABLE_BASKETS.'.site_id,
    '.NEW_TABLE_SITES.'.site_short,
    '.NEW_TABLE_SITES.'.site_long,
    '.NEW_TABLE_SITES.'.hub_id,
    '.TABLE_HUBS.'.hub_short,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_quantity,
    (SELECT GROUP_CONCAT(site_id) FROM '.TABLE_AVAILABILITY.' WHERE '.TABLE_AVAILABILITY.'.producer_id='.NEW_TABLE_PRODUCTS.'.producer_id) AS availability_list,
    '.NEW_TABLE_MESSAGES.'.message
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(basket_id)
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN '.TABLE_AVAILABILITY.' ON '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.NEW_TABLE_SITES.' ON '.NEW_TABLE_BASKETS.'.site_id = '.NEW_TABLE_SITES.'.site_id
  LEFT JOIN '.NEW_TABLE_MESSAGES.' ON (referenced_key1 = bpid AND message_type_id =
    (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "customer notes to producer"))
  LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
  WHERE'.
    $where_producer_pending.
    $where_misc.
    $where_auth_type.'
  ORDER BY'.
    $order_by;
?>