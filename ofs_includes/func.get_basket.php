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
          member_id = "'.mysql_real_escape_string ($argument1).'"
          AND delivery_id = "'.mysql_real_escape_string ($argument2).'"';
      }
    // and if only one argument, then it is $basket_id
    elseif
      (is_numeric ($argument1))
      {
        $query_where = 'WHERE
          basket_id = "'.mysql_real_escape_string ($argument1).'"';
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
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 892305 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysql_fetch_array($result))
      {
        return ($row);
      }
    else
      {
        // Error... no basket found
        // die(debug_print('ERROR: 502 ', 'basket does not exist', basename(__FILE__).' LINE '.__LINE__));
      }
  }
?>