<?php

valid_auth('producer,producer_admin');

// Following are used to set up various sort/display options for products
$unique['sort_type'] = $_GET['sort_type'];
if ($_GET['sort_type'] == 'product')
  {
    $major_division = 'bpid';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'bpid';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = false;
    $show_minor_division = true;
    $unique['row_type'] = 'product_short'; // Show the product info
    $order_by = '
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.NEW_TABLE_BASKETS.'.basket_id ASC,
    '.NEW_TABLE_PRODUCTS.'.pvid ASC,
    '.NEW_TABLE_SITES.'.site_long ASC';
    $subtitle = 'One Per Product';
  }
elseif ($_GET['sort_type'] == 'product_multiple') // Same as above but an extra label for each of multiple items (e.g 1 of 2, 2 of 2)
  {
    $major_division = 'bpid';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'bpid';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = false;
    $show_minor_division = true;
    $unique['row_type'] = 'product_multiple'; // Show the product info
    $order_by = '
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.NEW_TABLE_BASKETS.'.basket_id ASC,
    '.NEW_TABLE_PRODUCTS.'.pvid ASC,
    '.NEW_TABLE_SITES.'.site_long ASC';
    $subtitle = 'One Per Item';
  }
else // if ($unique['sort_type'] == 'storage_customer')
  {
    $major_division = 'storage_code';
    $major_division_prior = $major_division.'_prior';
    $minor_division = 'member_id';
    $minor_division_prior = $minor_division.'_prior';
    $show_major_division = false;
    $show_minor_division = true;
    $unique['row_type'] = 'product_short'; // Show the product info
    $unique['sort_type'] = 'storage_customer'; // in case default not explicitly set
    $order_by = '
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.NEW_TABLE_BASKETS.'.basket_id ASC,
    '.NEW_TABLE_PRODUCTS.'.pvid ASC,
    '.NEW_TABLE_SITES.'.site_long ASC';
    $subtitle = 'One Per Customer';
  }


// Assign page tab and title information
$page_title_html = '<span class="title">Labels</span>';
// $page_subtitle_html = '<span class="subtitle">'.$subtitle.'</span>';
$page_title = 'Labels: '.$subtitle.'';
$page_tab = 'shopping_panel';

// Assign template file
$template_type = 'labels';

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
    DISTINCT('.NEW_TABLE_BASKET_ITEMS.'.bpid),
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    '.NEW_TABLE_BASKET_ITEMS.'.product_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.checked_out,
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
    '.NEW_TABLE_SITES.'.truck_code,
    '.NEW_TABLE_SITES.'.hub_id,
    '.TABLE_HUBS.'.hub_short,
    (
      SELECT
        COUNT(DISTINCT(CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))))
      FROM
        '.NEW_TABLE_BASKET_ITEMS.' count_basket_items
      LEFT JOIN '.NEW_TABLE_BASKETS.' count_baskets USING(basket_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' count_products USING(product_id,product_version)
      WHERE
        delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
        AND count_products.producer_id = '.mysqli_real_escape_string ($connection, $producer_id_you).'
        AND count_products.storage_id = '.NEW_TABLE_PRODUCTS.'.storage_id
        AND count_products.tangible = "1"
        AND count_basket_items.checked_out > 0
        AND CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))
          <= CONCAT(
            LPAD('.NEW_TABLE_PRODUCTS.'.storage_id, 4, "0"),
            LPAD('.NEW_TABLE_BASKETS.'.basket_id, 10, "0"),
            LPAD('.NEW_TABLE_PRODUCTS.'.producer_id, 8, "0"),
            LPAD('.NEW_TABLE_PRODUCTS.'.pvid, 12, "0"))
      ORDER BY
        storage_id,
        basket_id
    ) AS producer_key,
    (
      SELECT
        COUNT(count_products.pvid) AS producer_bag_count
      FROM
        '.NEW_TABLE_BASKET_ITEMS.' count_basket_items
      LEFT JOIN '.NEW_TABLE_BASKETS.' count_baskets USING(basket_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' count_products USING(product_id,product_version)
      WHERE
        delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
        AND count_products.producer_id = '.mysqli_real_escape_string ($connection, $producer_id_you).'
        AND count_products.storage_id = '.NEW_TABLE_PRODUCTS.'.storage_id
        AND count_baskets.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
        AND count_products.tangible = "1"
        AND count_basket_items.checked_out > 0
    ) AS producer_bag_count,
    (
      SELECT
        COUNT(DISTINCT(CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))))
      FROM
        '.NEW_TABLE_BASKET_ITEMS.' count_basket_items
      LEFT JOIN '.NEW_TABLE_BASKETS.' count_baskets USING(basket_id)
      LEFT JOIN '.NEW_TABLE_SITES.' count_sites USING(site_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' count_products USING(product_id, product_version)
      WHERE
        delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
        AND count_sites.site_id = '.NEW_TABLE_SITES.'.site_id
        AND count_products.storage_id = '.NEW_TABLE_PRODUCTS.'.storage_id
        AND count_products.tangible = "1"
        AND count_basket_items.checked_out > 0
        AND CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))
          <= CONCAT(
            LPAD('.NEW_TABLE_PRODUCTS.'.storage_id, 4, "0"),
            LPAD('.NEW_TABLE_BASKETS.'.basket_id, 10, "0"),
            LPAD('.NEW_TABLE_PRODUCTS.'.producer_id, 8, "0"),
            LPAD('.NEW_TABLE_PRODUCTS.'.pvid, 12, "0"))
      ORDER BY
       basket_id,
       storage_id,
       producer_id
    ) AS site_key,
    (
      SELECT
        COUNT(DISTINCT(CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))))
      FROM
        '.NEW_TABLE_BASKET_ITEMS.' count_basket_items
      LEFT JOIN '.NEW_TABLE_BASKETS.' count_baskets USING(basket_id)
      LEFT JOIN '.NEW_TABLE_SITES.' count_sites USING(site_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' count_products USING(product_id, product_version)
      WHERE
        delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
        AND count_sites.site_id = '.NEW_TABLE_SITES.'.site_id
        AND count_baskets.member_id = '.NEW_TABLE_BASKETS.'.member_id
        AND count_products.tangible = "1"
        AND count_basket_items.checked_out > 0
        AND CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))
          <= CONCAT(
            LPAD('.NEW_TABLE_PRODUCTS.'.storage_id, 4, "0"),
            LPAD('.NEW_TABLE_BASKETS.'.basket_id, 10, "0"),
            LPAD('.NEW_TABLE_PRODUCTS.'.producer_id, 8, "0"),
            LPAD('.NEW_TABLE_PRODUCTS.'.pvid, 12, "0"))
      ORDER BY
       storage_id,
       producer_id,
       product_id
    ) AS customer_key,
    (
      SELECT
        COUNT(DISTINCT(CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))))
      FROM
        '.NEW_TABLE_BASKET_ITEMS.' count_basket_items
      LEFT JOIN '.NEW_TABLE_BASKETS.' count_baskets USING(basket_id)
      LEFT JOIN '.NEW_TABLE_SITES.' count_sites USING(site_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' count_products USING(product_id, product_version)
      WHERE
        delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
        AND count_sites.site_id = '.NEW_TABLE_SITES.'.site_id
        AND count_baskets.member_id = '.NEW_TABLE_BASKETS.'.member_id
        AND count_products.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
        AND count_products.tangible = "1"
        AND count_basket_items.checked_out > 0
        AND CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))
          <= CONCAT(
            LPAD('.NEW_TABLE_PRODUCTS.'.storage_id, 4, "0"),
            LPAD('.NEW_TABLE_BASKETS.'.basket_id, 10, "0"),
            LPAD('.NEW_TABLE_PRODUCTS.'.producer_id, 8, "0"),
            LPAD('.NEW_TABLE_PRODUCTS.'.pvid, 12, "0"))
      ORDER BY
       storage_id,
       producer_id,
       product_id
    ) AS bundle_key,
    (
      SELECT
        COUNT(DISTINCT(CONCAT(
            LPAD(count_products.storage_id, 4, "0"),
            LPAD(count_baskets.basket_id, 10, "0"),
            LPAD(count_products.producer_id, 8, "0"),
            LPAD(count_products.pvid, 12, "0"))))
      FROM
        '.NEW_TABLE_BASKET_ITEMS.' count_basket_items
      LEFT JOIN '.NEW_TABLE_BASKETS.' count_baskets USING(basket_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' count_products USING(product_id, product_version)
      WHERE
        delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
        AND count_baskets.member_id = '.NEW_TABLE_BASKETS.'.member_id
        AND count_products.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
        AND count_products.tangible = "1"
        AND count_basket_items.checked_out > 0
      ORDER BY
       storage_id,
       producer_id,
       product_id
    ) AS bundle_key_max,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_quantity,'.
    $select_availability.'
    '.NEW_TABLE_MESSAGES.'.message
  FROM
    '.NEW_TABLE_BASKET_ITEMS.'
  LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
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
  LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
    AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"
    AND '.NEW_TABLE_PRODUCTS.'.tangible = "1"
    AND '.NEW_TABLE_BASKET_ITEMS.'.checked_out > 0'.
    $where_producer_pending.'
  ORDER BY'.
    $order_by;

// echo "<pre>$query</pre>";
// debug_print ("INFO LABELS_QUERY ", array ('query'=>$query), basename(__FILE__).' LINE '.__LINE__)
