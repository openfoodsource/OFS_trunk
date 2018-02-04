<?php
// If a basket_item exists for this order, the subroutine returns useful information.
// This can be accessed by calling with:    get_basket_item ($bpid)
//                                       OR get_basket_item ($basket_id, $product_id, $product_version)
//                                       OR get_basket_item ($member_id, $product_id, $product_version, $delivery_id)
// product_version is not needed: there better be only one version in the basket at a time
function get_basket_item ($argument1, $argument2 = NULL, $argument3 = NULL, $argument4 = NULL)
  {
    global $connection;
    $selected_fields = array (
      // Expose additional parameters as they become needed.
      // COLUMNS FROM BASKET_ITEMS --------------------------------
      'bpid',
      'basket_id',
      'product_id',
      'product_version',
      // SUM: Total of all versions, just in case there are multiple versions in the basket
      'SUM(quantity) AS quantity', 
      'SUM(out_of_stock) AS out_of_stock',
      'SUM(total_weight) AS total_weight',
      NEW_TABLE_BASKET_ITEMS.'.product_fee_percent',
      NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent',
      NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent',
      'taxable',
      // 'future_delivery',
      // 'future_delivery_type',
      'date_added',
      // COLUMNS FROM PRODUCTS ------------------------------------
      // 'xid',
      // 'product_id',                                  // provided by basket_items
      // 'product_version',                             // provided by basket_items
      'producer_id',
      'product_name',
      // 'account_number',
      // 'inventory_pull',
      // 'inventory_id',
      'product_description',
      'subcategory_id',
      // 'future_delivery',
      // 'future_delivery_type',
      // 'production_type_id',
      'unit_price',
      'pricing_unit',
      'ordering_unit',
      'random_weight',
      'meat_weight_type',
      'minimum_weight',
      'maximum_weight',
      'extra_charge',
      'image_id',
      'listing_auth_type',
      // 'confirmed',
      // 'retail_staple',
      // 'staple_type',
      'created',
      'modified',
      'tangible',
      'sticky',
      // 'hide_from_invoice',
      'storage_id',
      'checked_out',
      // COLUMNS FROM MESSAGES ------------------------------------
      'message',
      // ACTIVE PRODUCT VERSION: Just in case we don't have this one in the basket
      '(SELECT product_version
          FROM '.NEW_TABLE_PRODUCTS.'
          WHERE '.NEW_TABLE_PRODUCTS.'.product_id=products.product_id
          AND approved="1"
          AND active="1") AS active_product_version',
      // COUNT() Should always be "1" unless there are multiple versions in the basket
      'COUNT(product_version) AS number_of_versions',
      );

    if (is_numeric ($argument1) && is_numeric ($argument2) && is_numeric ($argument3) && is_numeric ($argument4))
      {
        $query_where = 'RIGHT JOIN '.NEW_TABLE_BASKETS.' USING (basket_id)
        WHERE
          member_id = "'.mysqli_real_escape_string ($connection, $argument1).'"
          AND product_id = "'.mysqli_real_escape_string ($connection, $argument2).'"
          AND product_version = "'.mysqli_real_escape_string ($connection, $argument3).'"
          AND delivery_id = "'.mysqli_real_escape_string ($connection, $argument4).'"';
      }
    elseif (is_numeric ($argument1) && is_numeric ($argument2) && is_numeric ($argument3))
      {
        $query_where = 'WHERE
          basket_id = "'.mysqli_real_escape_string ($connection, $argument1).'"
          AND product_id = "'.mysqli_real_escape_string ($connection, $argument2).'"
          AND product_version = "'.mysqli_real_escape_string ($connection, $argument3).'"';
      }
    elseif (is_numeric ($argument1))
      {
        $query_where = 'WHERE
          bpid = "'.mysqli_real_escape_string ($connection, $argument1).'"';
      }
    else
      {
        die (debug_print('ERROR: 164201 ', 'unexpected request', basename(__FILE__).' LINE '.__LINE__));
      }
    $query = '
      SELECT
        '.implode (",\n        ", $selected_fields).'
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' products USING(product_id,product_version)
      LEFT JOIN '.NEW_TABLE_MESSAGES.' ON
        ('.NEW_TABLE_MESSAGES.'.message_type_id = 1
        AND '.NEW_TABLE_BASKET_ITEMS.'.bpid = '.NEW_TABLE_MESSAGES.'.referenced_key1)
      '.$query_where;
    $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 799031 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        return ($row);
      }
  }
