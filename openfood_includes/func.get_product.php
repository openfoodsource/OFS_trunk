<?php
// If the product exists, the subroutine returns the product information.
// Call with: get_product ($product_id, $product_version, $pvid)
// Must supply (product_id, product_version) AND/OR (pvid)
function get_product ($product_id, $product_version, $pvid)
  {
    // Expose additional parameters as they become needed.
    global $connection;
    $products_fields = array (
      'pvid',
      'product_id',
      'product_version',
      NEW_TABLE_PRODUCTS.'.producer_id',
      'product_name',
      'product_description',
      'account_number',
      'inventory_pull',
      'inventory_id',
      TABLE_INVENTORY.'.quantity AS inventory_quantity',
      'subcategory_id',
      NEW_TABLE_PRODUCTS.'.future_delivery',
      NEW_TABLE_PRODUCTS.'.future_delivery_type',
      'production_type_id',
      'unit_price',
      'pricing_unit',
      'ordering_unit',
      'random_weight',
      'meat_weight_type',
      'minimum_weight',
      'maximum_weight',
      'extra_charge',
      NEW_TABLE_PRODUCTS.'.product_fee_percent',
      'image_id',
      'listing_auth_type',
      'confirmed', // Depricate field after conversion to OFSv1.2.0
      'approved', // New field OFSv1.2.0
      'active', // New field OFSv1.2.0
      'retail_staple',
      'staple_type',
      'created',
      'modified',
      'tangible',
      'sticky',
      'hide_from_invoice',
      'storage_id',
      TABLE_CATEGORY.'.category_id',
      TABLE_CATEGORY.'.category_name',
      TABLE_SUBCATEGORY.'.subcategory_name',
      TABLE_PRODUCT_TYPES.'.prodtype',
      // The active version is now explicitly set
      '(SELECT product_version
          FROM '.NEW_TABLE_PRODUCTS.'
          WHERE product_id="'.mysqli_real_escape_string ($connection, $product_id).'"
          AND active="1"
          LIMIT 1) AS active_version',
      'COUNT(bpid) AS total_ordered_this_version',
      );
    $query = '
      SELECT
        '.implode (",\n        ", $products_fields).'
      FROM '.NEW_TABLE_PRODUCTS.'
      LEFT JOIN
        '.TABLE_INVENTORY.' USING(inventory_id)
      LEFT JOIN
        '.TABLE_PRODUCT_TYPES.' USING(production_type_id)
      LEFT JOIN
        '.TABLE_SUBCATEGORY.' USING(subcategory_id)
      LEFT JOIN
        '.TABLE_CATEGORY.' USING(category_id)
      LEFT JOIN
        '.TABLE_PRODUCER.' ON '.NEW_TABLE_PRODUCTS.'.producer_id = '.TABLE_PRODUCER.'.producer_id
      LEFT JOIN
        '.NEW_TABLE_BASKET_ITEMS.' USING(product_id,product_version)
      WHERE
          ('.NEW_TABLE_PRODUCTS.'.product_id = "'.mysqli_real_escape_string ($connection, $product_id).'"
          AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.mysqli_real_escape_string ($connection, $product_version).'")
        OR 
          ('.NEW_TABLE_PRODUCTS.'.pvid = "'.mysqli_real_escape_string ($connection, $pvid).'"
          AND '.NEW_TABLE_PRODUCTS.'.pvid != "0")';
          $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 754004 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        return ($row);
      }
  }
