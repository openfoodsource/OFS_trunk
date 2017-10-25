<?php

// valid_auth(''); // anyone can view these pages

// Show search box on product shopping pages
$show_search = true;

$where_misc = '
    AND '.TABLE_PRODUCT_TYPES.'.prodtype LIKE "%organic%"';

$order_by = '
    '.TABLE_CATEGORY.'.sort_order ASC,
    '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
    '.TABLE_PRODUCER.'.business_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC,
    '.NEW_TABLE_PRODUCTS.'.unit_price ASC';

// Assign page tab and title information
$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '<span class="subtitle">All or Part Organic</span>';
$page_title = 'Products: All or Part Organic';
$page_tab = 'shopping_panel';

// Assign template file
if ($_GET['output'] == 'csv')
  {
    $per_page = 1000000;
    $template_type = 'customer_list_csv';
  }
elseif ($_GET['output'] == 'pdf')
  {
    $per_page = 1000000;
    $template_type = 'customer_list_pdf';
  }
else
  {
    $per_page = PER_PAGE;
    $template_type = 'customer_list';
  }

// Set display groupings
$major_division = 'category_name';
$major_division_prior = $major_division.'_prior';
$minor_division = 'producer_name';
$minor_division_prior = $minor_division.'_prior';
$show_major_division = true;
$show_minor_division = true;
$row_type = 'product'; // Reflects the detail to show on each row (vs. what gets featured in the header)

// Execute the main product_list query
$query = '
  SELECT
    SQL_CALC_FOUND_ROWS
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
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name AS producer_name,
    '.TABLE_PRODUCER.'.producer_link,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_type,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    COALESCE('.NEW_TABLE_BASKETS.'.customer_fee_percent, (SELECT customer_fee_percent FROM '.TABLE_MEMBER.' WHERE member_id = "'.$_SESSION['member_id'].'")) AS customer_fee_percent,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    '.NEW_TABLE_PRODUCTS.'.product_fee_percent,
    '.TABLE_SUBCATEGORY.'.subcategory_fee_percent,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    (SELECT GROUP_CONCAT(site_id) FROM '.TABLE_AVAILABILITY.' WHERE '.TABLE_AVAILABILITY.'.producer_id='.NEW_TABLE_PRODUCTS.'.producer_id) AS availability_list,
    '.NEW_TABLE_MESSAGES.'.message
  FROM
    ('.NEW_TABLE_PRODUCTS.',
    '.TABLE_MEMBER.')
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN '.TABLE_AVAILABILITY.' ON '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON
    ('.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id
    AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'"
    AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id > 0)
  LEFT JOIN '.NEW_TABLE_MESSAGES.' ON (referenced_key1 = bpid AND message_type_id =
    (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "customer notes to producer"))
  LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
  WHERE'.
    $where_producer_pending.
    $where_unlisted_producer.
    $where_misc.
    $where_zero_inventory.
    $where_confirmed.'
    AND FIND_IN_SET(listing_auth_type, COALESCE((SELECT auth_type FROM '.TABLE_MEMBER.' WHERE member_id = "'.$_SESSION['member_id'].'"), "member")) > 0
  GROUP BY CONCAT('.NEW_TABLE_PRODUCTS.'.product_id, "-", '.NEW_TABLE_PRODUCTS.'.product_version)
  ORDER BY'.
    $order_by;
