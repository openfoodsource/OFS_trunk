<?php

// If this is being loaded by edit_product_info.php for sending by email, then do not check auth
// In this case, it is called by the server itself, so REMOTE_ADDR == SERVER_ADDR.
if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) $unique['local_auth'] = true or $unique['local_auth'] = false;
if (! $unique['local_auth']) valid_auth('producer,producer_admin');

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

// Default is to show either version where "active=1" or maximum version otherwise
$narrow_over_versions_where = '
      '.NEW_TABLE_PRODUCTS.'.product_version = COALESCE (
        (SELECT product_version FROM '.NEW_TABLE_PRODUCTS.' bar2 WHERE bar2.product_id = '.NEW_TABLE_PRODUCTS.'.product_id AND bar2.active = 1 LIMIT 1),
        (SELECT MAX(product_version) FROM '.NEW_TABLE_PRODUCTS.' bar2 WHERE bar2.product_id = '.NEW_TABLE_PRODUCTS.'.product_id))';

$narrow_over_producer = '
      AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"';
$group_by = '
      '.NEW_TABLE_PRODUCTS.'.product_id';

$unique['select_type'] = $_GET['select_type'];


// Following are used to set up various sort/display options for products
$unique['sort_type'] = $_GET['sort_type'];
if ($_GET['sort_type'] == 'listing_type')
  {
    $major_division = 'listing_auth_type'; // This option will not change across the list, giving only one major division
    $major_division_prior = $major_division.'_prior';
    $minor_division = '';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = false;
    $unique['row_type'] = 'product'; // Show the product info
    $order_by = '
      FIELD('.NEW_TABLE_PRODUCTS.'.listing_auth_type, "archived", "unlisted", "institution", "member") DESC,
      '.NEW_TABLE_PRODUCTS.'.product_name ASC';
  }
elseif ($unique['sort_type'] == 'storage')
  {
    $major_division = 'storage_code';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'subcategory_id';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $unique['row_type'] = 'product'; // Show the product info
    $order_by = '
      FIELD('.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code, "FROZ", "REF", "NON") DESC,
      '.TABLE_CATEGORY.'.category_name ASC,
      '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
      '.NEW_TABLE_PRODUCTS.'.product_name ASC';
  }
elseif ($unique['sort_type'] == 'category')
  {
    $major_division = 'category_name';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'subcategory_name';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $unique['row_type'] = 'product'; // Show the product info
    $order_by = '
      '.TABLE_CATEGORY.'.category_name ASC,
      '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
      '.NEW_TABLE_PRODUCTS.'.product_name ASC';
  }
elseif ($unique['sort_type'] == 'product_id')
  {
    $major_division = 'static_producer_name';
    $major_division_prior = $major_division.'_prior';
    $minor_division = '';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = false;
    $unique['row_type'] = 'product'; // Show the product info
    $order_by = '
      '.NEW_TABLE_PRODUCTS.'.product_id DESC';
    $select_special = '
      "Products by Number (descending)" AS static_producer_name,';
  }
elseif ($unique['sort_type'] == 'email_product')
  {
    $major_division = '';
    $major_division_prior = $major_division.'_prior';
    $minor_division = '';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = false;
    $show_minor_division = false;
    $unique['row_type'] = 'product'; // Show the product info
    $order_by = '
      '.NEW_TABLE_PRODUCTS.'.product_id';
    $display_as_popup = true;
    $narrow_over_producer = ''; // No producer_id restriction
    $narrow_over_versions_where = ' 1'; // No version restriction
    $unique['row_type'] = 'email_product';
  }
else // if ($unique['sort_type'] == 'product_name') // DEFAULT
  {
    $major_division = 'static_producer_id'; // This option will not change across the list, giving only one major division
    $major_division_prior = $major_division.'_prior';
    $minor_division = '';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = false;
    $unique['row_type'] = 'product'; // Show the product info
    $order_by = '
      '.NEW_TABLE_PRODUCTS.'.product_name ASC,
      '.NEW_TABLE_PRODUCTS.'.unit_price ASC';
    $unique['sort_type'] = 'product_name'; // Set explicitly since this is the default
    $select_special = '
      "Products by Name" AS static_producer_id,';
  }

// What part of the product list are we selecting over?
if ($unique['select_type'] == 'admin_unconfirmed'
    && CurrentMember::auth_type('producer_admin'))
  {
    // Need to be able to look at all producers and all products
    $where_auth_type = '';
    // Invert the normal sense of confirmation checking
    // and restrict to non-suspended producers
    $where_confirmed = '
    AND '.NEW_TABLE_PRODUCTS.'.approved = 0
    AND '.TABLE_PRODUCER.'.unlisted_producer != 2';
    // Default clause to get number of products currently ordered for this product; all versions
    $total_ordered_clause = '
    COALESCE((SELECT
      SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE product_id = '.NEW_TABLE_PRODUCTS.'.product_id),
      0) AS total_ordered_this_product,';
    // For unconfirmed products, order by modified date DESC
    $order_by = '
      '.NEW_TABLE_PRODUCTS.'.modified DESC';
    $group_by = '
        pvid'; // No grouping so we show all unconfirmed versions
    // Unconfirmed products are over ALL producers
    $narrow_over_versions = '';
    $narrow_over_versions_where = '
      1';
    $narrow_over_producer = '';
    $title = 'All Producers';
    $subtitle = 'Unconfirmed Products';
    // Override some of the sort_type settings since this is a special case section
    // We will make the major sort (from sort_type) into the secondary division headers
    $minor_division = $major_division; // From sort_type settings above
    $minor_division_prior = $minor_division.'_prior';
    $major_division = 'static_confirmation_message'; // This option will not change across the list, giving only one major division
    $major_division_prior = $major_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $select_special = '
      "Unconfirmed Products from All Producers" AS static_confirmation_message,';
  }
elseif ($unique['select_type'] == 'versions')
  {
    // Technically, versions changes the listing domain rather than narrowing, but the condition works well here
    $major_division = 'static_product_id';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'product_version';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = true;
    $show_minor_division = true;
    $unique['row_type'] = 'product'; // Reflects the detail to show on each row (vs. what gets featured in the header)
    // Showing all versions, so do not restrict on listing_auth_type
    $where_auth_type = '';
    // This is where we switch to showing versions of a single product instead of all products
    $where_misc = '
      AND '.NEW_TABLE_PRODUCTS.'.product_id = "'.mysqli_real_escape_string ($connection, $_GET['product_id']).'"';
    $order_by = '
      '.NEW_TABLE_PRODUCTS.'.product_version DESC';
    $total_ordered_clause = '
    COALESCE((SELECT
      SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE product_id = '.NEW_TABLE_PRODUCTS.'.product_id
      AND product_version = '.NEW_TABLE_PRODUCTS.'.product_version),
      0) AS total_ordered_this_version,';
    $narrow_over_versions = '';
    $narrow_over_versions_where = '
      1';
    $group_by = '
      CONCAT('.NEW_TABLE_PRODUCTS.'.product_id, "-", '.NEW_TABLE_PRODUCTS.'.product_version)';
    $subtitle = 'All Versions of Product #'.mysqli_real_escape_string ($connection, $_GET['product_id']);
    $select_special = '
      "Versions of Product #" AS static_product_id,';
  }
elseif ($unique['select_type'] == 'for_sale')
  {
    // Default clause to get number of products currently ordered for this product; all versions
    $total_ordered_clause = '
    COALESCE((SELECT
      SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE product_id = '.NEW_TABLE_PRODUCTS.'.product_id),
      0) AS total_ordered_this_product,';

    $where_misc = '
      AND '.NEW_TABLE_PRODUCTS.'.active = "1"';

    $where_auth_type = '
      AND ('.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"
        OR '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "institution")';
    $subtitle = 'Retail &amp; Wholesale Products';
  }
elseif ($unique['select_type'] == 'not_archived')
  {
    // Default clause to get number of products currently ordered for this product; all versions
    $total_ordered_clause = '
    COALESCE((SELECT
      SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE product_id = '.NEW_TABLE_PRODUCTS.'.product_id),
      0) AS total_ordered_this_product,';
    $where_auth_type = '
      AND '.NEW_TABLE_PRODUCTS.'.listing_auth_type != "archived"';
    $subtitle = 'Non-Archived Products';
  }
elseif ($unique['select_type'] == 'archived')
  {
    // Default clause to get number of products currently ordered for this product; all versions
    $total_ordered_clause = '
    COALESCE((SELECT
      SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE product_id = '.NEW_TABLE_PRODUCTS.'.product_id),
      0) AS total_ordered_this_product,';
    $where_auth_type = '
      AND '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "archived"';
    $subtitle = 'Archived Products';
  }
elseif ($unique['select_type'] == 'email_product')
  {
    // Default clause to get number of products currently ordered for this product; all versions
    $total_ordered_clause = '';
    $where_auth_type = '
      AND '.NEW_TABLE_PRODUCTS.'.product_id = "'.mysqli_real_escape_string ($connection, $_GET['product_id']).'"
      AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.mysqli_real_escape_string ($connection, $_GET['product_version']).'"';
    $subtitle = 'Product Information';
  }
else // if ($unique['select_type'] == 'all') // DEFAULT
  {
    $unique['select_type'] = 'all';
    // Default clause to get number of products currently ordered for this product; all versions
    $total_ordered_clause = '
    COALESCE((SELECT
      SUM(quantity)
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      WHERE product_id = '.NEW_TABLE_PRODUCTS.'.product_id),
      0) AS total_ordered_this_product,';
    $subtitle = 'All Products';
  }

// Assign page tab and title information
$page_title_html = '<span class="title">'.(strlen($title) > 0 ? $title : $_SESSION['producer_business_name']).'</span>';
// $page_subtitle_html = '<span class="subtitle">'.$subtitle.'</span>';
$page_title = $_SESSION['producer_business_name'].' Products';
$page_tab = 'producer_panel';

// Assign template file
$template_type = 'producer_list';

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
    '.NEW_TABLE_PRODUCTS.'.approved,
    '.NEW_TABLE_PRODUCTS.'.active,
    '.NEW_TABLE_PRODUCTS.'.confirmed,
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name AS producer_name,
    (SELECT business_name FROM '.TABLE_PRODUCER.' WHERE producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'") AS producer_name_you,
    '.TABLE_PRODUCER.'.producer_link,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.*,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_pull_quantity,
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
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'"'.
  $narrow_over_versions.'
  WHERE'.
    $narrow_over_versions_where.
    $narrow_over_producer.
    $where_producer_pending.
    $where_misc.
    $where_zero_inventory.
    $where_confirmed.
    $where_auth_type.'
  GROUP BY'.
    $group_by.'
  ORDER BY'.
    $order_by;
// debug_print ("INFO ", array ('QUERY'=>$query), basename(__FILE__).' LINE '.__LINE__);
