<?php
// If a basket_item exists for this order, the subroutine returns useful information.
// This can be accessed by calling with:    get_basket_item ($bpid)
//                                       OR get_basket_item ($basket_id, $product_id)
//                                       OR get_basket_item ($member_id, $product_id, $delivery_id)
// product_version is not needed: there better be only one version in the basket at a time
function get_basket_item ($argument1, $product_id = NULL, $delivery_id = NULL)
  {
    global $connection;
    $selected_fields = array (
      // Expose additional parameters as they become needed.
      // COLUMNS FROM BASKET_ITEMS --------------------------------
      'bpid',
      'basket_id',
      'product_id',
      'product_version',
      'quantity',
      'total_weight',
      NEW_TABLE_BASKET_ITEMS.'.product_fee_percent',
      NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent',
      NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent',
      'taxable',
      'out_of_stock',
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
      );

    if (is_numeric ($argument1) && is_numeric ($product_id) && is_numeric ($delivery_id))
      {
        $query_where = 'RIGHT JOIN '.NEW_TABLE_BASKETS.' USING (basket_id)
        WHERE
          member_id = "'.mysql_real_escape_string ($argument1).'"
          AND product_id = "'.mysql_real_escape_string ($product_id).'"
          AND delivery_id = "'.mysql_real_escape_string ($delivery_id).'"';
      }
    elseif (is_numeric ($argument1) && is_numeric ($product_id))
      {
        $query_where = 'WHERE
          basket_id = "'.mysql_real_escape_string ($argument1).'"
          AND product_id = "'.mysql_real_escape_string ($product_id).'"';
      }
    elseif (is_numeric ($argument1))
      {
        $query_where = 'WHERE
          bpid = "'.mysql_real_escape_string ($argument1).'"';
      }
    else
      {
        die(debug_print('ERROR: 101 ', 'unexpected request', basename(__FILE__).' LINE '.__LINE__));
      }
    $query = '
      SELECT
        '.implode (",\n        ", $selected_fields).'
      FROM '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
      LEFT JOIN '.NEW_TABLE_MESSAGES.' ON
        ('.NEW_TABLE_MESSAGES.'.message_type_id = 1
        AND '.NEW_TABLE_BASKET_ITEMS.'.bpid = '.NEW_TABLE_MESSAGES.'.referenced_key1)
      '.$query_where;
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 799031 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysql_fetch_array($result))
      {
        return ($row);
      }
  }
?>
