<?php

valid_auth('producer_admin');

// Show search box on product shopping pages
$show_search = true;

// Need to be able to look at all producers and all products
$where_unlisted_producer = '';
$where_zero_inventory = '';
$where_auth_type = '';
$where_producer_pending = '';

// Look for cases of products needing confirmation
// SEE the "RIGHT JOIN" in query for "confirmed" portion of the query
$where_confirmed = '';

$order_by = '
    '.TABLE_CATEGORY.'.sort_order ASC,
    '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
    '.TABLE_PRODUCER.'.business_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC,
    '.NEW_TABLE_PRODUCTS.'.unit_price ASC';

// Assign page tab and title information
$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '<span class="subtitle">Full List by Category</span>';
$page_title = 'Products: Full List by Category';
$page_tab = 'shopping_panel';

// Assign template file
if ($_GET['output'] == 'csv')
  {
    $per_page = 1000000;
    $template_type = 'producer_list_csv';
  }
elseif ($_GET['output'] == 'pdf')
  {
    $per_page = 1000000;
    $template_type = 'producer_list_pdf';
  }
else
  {
    $per_page = PER_PAGE;
    $template_type = 'producer_list';
  }

// Set display groupings
$major_division = 'category_name';
$major_division_prior = $major_division.'_prior';
$minor_division = 'producer_name';
$minor_division_prior = $minor_division.'_prior';
$show_major_division = true;
$show_minor_division = true;
$row_type = 'product'; // Reflects the detail to show on each row (vs. what gets featured in the header)

// Explanation of the following query:
// 
// The LEFT OUTER JOIN on itself, along with the WHERE new_products2.product_id IS NULL
// is used to limit results to the confirmed row (first), then the preferred row
// (second) and finally to the maximal product_version (third) if there is not confirmed
// or preferred row. This is accomplished by sorting on the IF(FIELD...) clauses that set
// confirmed=1,000,001, preferred=1,000,000 and everything else to the version number. Of
// course, this means versions above 999,999 will break the functionality.

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
    '.NEW_TABLE_PRODUCTS.'.modified,
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
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    '.NEW_TABLE_BASKET_ITEMS.'.product_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    (SELECT GROUP_CONCAT(site_id) FROM '.TABLE_AVAILABILITY.' WHERE '.TABLE_AVAILABILITY.'.producer_id='.NEW_TABLE_PRODUCTS.'.producer_id) AS availability_list
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN '.TABLE_AVAILABILITY.' ON '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON
    '.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id
    AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"
  LEFT OUTER JOIN '.NEW_TABLE_PRODUCTS.' '.NEW_TABLE_PRODUCTS.'2
    ON ('.NEW_TABLE_PRODUCTS.'.product_id = '.NEW_TABLE_PRODUCTS.'2.product_id
      AND IF(FIELD('.NEW_TABLE_PRODUCTS.'.confirmed, -1, 1) = 0, '.NEW_TABLE_PRODUCTS.'.product_version, FIELD('.NEW_TABLE_PRODUCTS.'.confirmed, -1, 1) + 999999)
      <
      IF(FIELD('.NEW_TABLE_PRODUCTS.'2.confirmed, -1, 1) = 0, '.NEW_TABLE_PRODUCTS.'2.product_version, FIELD('.NEW_TABLE_PRODUCTS.'2.confirmed, -1, 1) + 999999))
  WHERE
    '.NEW_TABLE_PRODUCTS.'2.product_id IS NULL
    AND '.NEW_TABLE_PRODUCTS.'.confirmed != 1'.
    $where_producer_pending.
    $where_unlisted_producer.
    $where_misc.
    $where_zero_inventory.
    $where_confirmed.
    $where_member.'
  GROUP BY CONCAT('.NEW_TABLE_PRODUCTS.'.product_id, "-", '.NEW_TABLE_PRODUCTS.'.product_version)
  ORDER BY'.
    $order_by;


//   RIGHT JOIN (
//       SELECT product_id
//       FROM '.NEW_TABLE_PRODUCTS.'
//       GROUP BY product_id
//       HAVING MAX(confirmed) < 1) nonconfirmed ON nonconfirmed.product_id = '.NEW_TABLE_PRODUCTS.'.product_id
//   WHERE'.

?>