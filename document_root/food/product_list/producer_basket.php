<?php

valid_auth('producer,producer_admin');

$where_misc = '
    AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
    AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"';

$unique['sort_type'] = $_GET['sort_type'];
$unique['delivery_id'] = $delivery_id;
if ($unique['sort_type'] == 'customer')
  {
    $major_division = 'producer_id';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'member_id';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $unique['row_type'] = 'product_short'; // Reflects the detail to show on each row (vs. what gets featured in the header)
    $order_by = '
    '.TABLE_MEMBER.'.preferred_name ASC,
      FIELD('.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code, "FROZ", "REF", "NON") DESC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC';
  }
elseif ($unique['sort_type'] == 'site_customer')
  {
    $major_division = 'site_short';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'member_id';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $unique['row_type'] = 'product_short'; // Reflects the detail to show on each row (vs. what gets featured in the header)
    $order_by = '
    '.NEW_TABLE_SITES.'.site_long ASC,
    '.TABLE_MEMBER.'.preferred_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC';
  }
elseif ($unique['sort_type'] == 'storage_customer')
  {
    $major_division = 'storage_code';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'pvid';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $unique['row_type'] = 'member_short'; // Reflects the detail to show on each row (vs. what gets featured in the header)
    $order_by = '
      FIELD('.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code, "FROZ", "REF", "NON") DESC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_id ASC,
    '.TABLE_MEMBER.'.preferred_name ASC';
  }
else // if ($unique['sort_type'] == 'category') // DEFAULT OPTION
  {
    $major_division = 'subcategory_id';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'pvid';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $unique['row_type'] = 'member_short'; // Reflects the detail to show on each row (vs. what gets featured in the header)
    $order_by = '
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_id ASC,
    '.TABLE_MEMBER.'.preferred_name ASC';
    $unique['sort_type'] = 'category'; // Set explicitly since this is the default
  }

// Clobber the listing_auth_type for producer baskets
$where_auth_type = '';

// Assign page tab and title information
$page_title_html = '<span class="title">Producer Basket</span>';
$subtitle = $_SESSION['producer_business_name'];
// $page_subtitle_html = '<span class="subtitle">'.$_SESSION['producer_business_name'].'</span>';
$page_title = 'Producer Basket Items';
$page_tab = 'shopping_panel';

// Assign template file
$template_type = 'producer_basket';

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
    SQL_CALC_FOUND_ROWS
    DISTINCT('.NEW_TABLE_BASKET_ITEMS.'.bpid) AS bpid,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    '.NEW_TABLE_BASKET_ITEMS.'.product_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.checked_out,
    '.NEW_TABLE_PRODUCTS.'.pvid,
    '.NEW_TABLE_PRODUCTS.'.producer_id,
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
    '.NEW_TABLE_PRODUCTS.'.active,
    '.NEW_TABLE_PRODUCTS.'.approved,
    '.NEW_TABLE_PRODUCTS.'.confirmed,
    '.TABLE_CATEGORY.'.category_id,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_CATEGORY.'.sort_order,
    '.TABLE_SUBCATEGORY.'.subcategory_id,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.home_phone,
    '.TABLE_MEMBER.'.work_phone,
    '.TABLE_MEMBER.'.mobile_phone,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_type,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.NEW_TABLE_BASKETS.'.site_id,
    '.NEW_TABLE_SITES.'.site_short,
    '.NEW_TABLE_SITES.'.site_long,
    '.NEW_TABLE_SITES.'.hub_id,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_pull_quantity,
    '.NEW_TABLE_MESSAGES.'.message AS customer_message,
    '.TABLE_ORDER_CYCLES.'.delivery_date,'.
    $select_availability.'
    '.TABLE_INVENTORY.'.quantity AS bucket_quantity,
    '.TABLE_INVENTORY.'.description AS inventory_description,
    (SELECT
      SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
      WHERE delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
      AND product_id = '.NEW_TABLE_PRODUCTS.'.product_id
      AND product_version = '.NEW_TABLE_PRODUCTS.'.product_version
      ) AS ordered_quantity
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(basket_id)
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id'.
  $join_availability.'
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.NEW_TABLE_SITES.' ON '.NEW_TABLE_BASKETS.'.site_id = '.NEW_TABLE_SITES.'.site_id
  LEFT JOIN '.NEW_TABLE_MESSAGES.' ON (referenced_key1 = bpid AND message_type_id =
    (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "customer notes to producer"))
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    1'.
    $where_producer_pending.
    $where_misc.
    $where_auth_type.'
  ORDER BY'.
    $order_by;
// echo "<pre>$query</pre>";
