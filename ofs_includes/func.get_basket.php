<?php
// If a basket exists for this order, the subroutine returns useful basket information.
// Call with:    get_basket ($member_id, $delivery_id)
//            OR get_basket ($basket_id)
function get_basket ($argument1, $argument2 = NULL)
  {
    global $connection;

    // If we received two arguments, they are $member_id and $delivery_id
    if (is_numeric ($argument1) && is_numeric ($argument2))
      {
        $query_where = 'WHERE
          member_id = "'.mysqli_real_escape_string ($connection, $argument1).'"
          AND delivery_id = "'.mysqli_real_escape_string ($connection, $argument2).'"';
      }
    // and if only one argument, then it is $basket_id
    elseif
      (is_numeric ($argument1))
      {
        $query_where = 'WHERE
          basket_id = "'.mysqli_real_escape_string ($connection, $argument1).'"';
      }
    $basket_fields = array (
      'basket_id',
      'member_id',
      'delivery_id',
      NEW_TABLE_BASKETS.'.site_id',
      'site_short',
      'site_long',
      NEW_TABLE_BASKETS.'.delivery_postal_code',
      NEW_TABLE_BASKETS.'.delivery_type',
      'delivery_cost',
      'order_cost',
      'order_cost_type',
      'customer_fee_percent',
      'order_date',
      'checked_out',
      'locked',
      );
    // Get the basket information...
    $query = '
      SELECT
        '.implode (",\n        ", $basket_fields).'
      FROM '.NEW_TABLE_BASKETS.'
      LEFT JOIN '.NEW_TABLE_SITES.' USING(site_id)
      '.$query_where;
    $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 892305 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        return ($row);
      }
    else
      {
        // Error... no basket found
        // die (debug_print('ERROR: 502322 ', 'basket does not exist', basename(__FILE__).' LINE '.__LINE__));
      }
  }
