<?php
include_once('func.update_basket.php');

// This function will open a new basket and return the basket information in an associative array
// Input data is an associative array with values:
// * member_id         member_id for the basket to be opened
// * delivery_id       delivery_id for the basket to be opened
// * site_id           site_id for the basket to be opened (maybe optional)
// * delivery_type     delivery_type for the basket to be opened (maybe optional)
//
// If we can get site_id and delivery_type, that is good. Otherwise, return such values as can be
// inferred from prior orders (or nothing). If the site_id/delivery_type are provided, then set any
// existing basket to those new values.
function open_update_basket (array $data)
  {
    global $connection;
    // Expose additional parameters as they become needed. Call with:
    $basket_fields = array (
      'basket_id',
      'member_id',
      'delivery_id',
      'site_id',
      'delivery_type',
      'delivery_postal_code',
      'delivery_cost',
      'order_cost',
      'order_cost_type',
      'customer_fee_percent',
      'order_date',
      'checked_out',
      'locked'
      );
    // At a minimum, we need to know the member_id and the delivery_id
    if (! $data['member_id'] || ! $data['delivery_id'])
      {
        die(debug_print('ERROR: 504 ', 'call to create basket without all parameters', basename(__FILE__).' LINE '.__LINE__));
      }
    // See if a basket already exists
    $query_basket_info = '
      SELECT
        '.implode (",\n        ", $basket_fields).'
      FROM '.NEW_TABLE_BASKETS.'
      WHERE
        member_id = "'.mysql_real_escape_string ($data['member_id']).'"
        AND delivery_id = "'.mysql_real_escape_string ($data['delivery_id']).'"';
    $result_basket_info = mysql_query($query_basket_info, $connection) or die(debug_print ("ERROR: 892122 ", array ($query_basket_info,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row_basket_info = mysql_fetch_array($result_basket_info))
      {
        // If site_id or delivery_type are set and same as existing values (then we are done)...
        if ((! $data['site_id'] || $data['site_id'] == $row_basket_info['site_id']) &&
            (! $data['delivery_type'] || $data['delivery_type'] == $row_basket_info['delivery_type']))
          {
            // Done with nothing to do. Return the information...
            return ($row_basket_info);
          }
        // Otherwise get the current basket_id -- if there is one
        elseif ($row_basket_info['basket_id'])
          {
            $data['basket_id'] = $row_basket_info['basket_id'];
            // Change in an existing basket means we need to re-checkout to catch
            // things like taxes and changed delivery fees
            $initiate_checkout = true;
          }
      }
    // See if we are missing either site_id or delivery_type. If not, make a best-guess from the person's prior order
    if (! $data['site_id'] ||
        ! $data['delivery_type'])
      {
        // See what site_id this member used prior to the target delivery_id
        $query_site_guess = '
          SELECT
            site_id,
            delivery_type
          FROM '.NEW_TABLE_BASKETS.'
          WHERE
            delivery_id < "'.mysql_real_escape_string($data['delivery_id']).'"
            AND member_id = "'.mysql_real_escape_string($data['member_id']).'"
            AND inactive = "0"
          ORDER BY delivery_id DESC
          LIMIT 1';
        $result_site_guess = mysql_query($query_site_guess, $connection) or die(debug_print ("ERROR: 902524 ", array ($query_site_guess,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // If we get a result back, then we will use it
        if ($row_site_guess = mysql_fetch_array($result_site_guess))
          {
            $data['site_id'] = $row_site_guess['site_id'];
            // If we already have a delivery_type value, then do not clobber it
            if (! $data['delivery_type'])
              {
                $data['delivery_type'] = $row_site_guess['delivery_type'];
              }
          }
        else
          {
            // we will allow creation of a non-homed cart because it will be caught at checkout
            // so set some "blank" default values
            $data['site_id'] = '';
            $data['delivery_type'] =  'P';
            $basket_adrift = true;
          }
      }
    // Get additional basket data for opening this basket
    $query_basket_data = '
      SELECT
        '.NEW_TABLE_SITES.'.delivery_postal_code,
        '.NEW_TABLE_SITES.'.delivery_charge AS delivery_cost,
        '.TABLE_MEMBERSHIP_TYPES.'.order_cost,
        '.TABLE_MEMBERSHIP_TYPES.'.order_cost_type,
        '.TABLE_MEMBER.'.customer_fee_percent,
        '.TABLE_MEMBER.'.zip AS home_zip,
        '.TABLE_MEMBER.'.work_zip
      FROM '.NEW_TABLE_SITES.'
      INNER JOIN '.TABLE_MEMBER.'
      LEFT JOIN '.TABLE_MEMBERSHIP_TYPES.' USING(membership_type_id)
      WHERE
        '.NEW_TABLE_SITES.'.site_id = "'.mysql_real_escape_string($data['site_id']).'"
        AND '.TABLE_MEMBER.'.member_id = "'.mysql_real_escape_string($data['member_id']).'"';
    $result_basket_data = mysql_query($query_basket_data, $connection) or die(debug_print ("ERROR: 983134 ", array ($query_basket_data,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row_basket_data = mysql_fetch_array($result_basket_data))
      {
        $data['delivery_postal_code'] = $row_basket_data['delivery_postal_code'];
        $data['delivery_cost'] = $row_basket_data['delivery_cost'];
        $data['order_cost'] = $row_basket_data['order_cost'];
        $data['order_cost_type'] = $row_basket_data['order_cost_type'];
        $data['customer_fee_percent'] = $row_basket_data['customer_fee_percent'];
        $home_zip = $row_basket_data['home_zip'];
        $work_zip = $row_basket_data['work_zip'];
      }
    else
      {
        // Since we allow creation of carts without assigned delivery_codes, set some "blank" default values
        $data['delivery_postal_code'] = '';
        $data['delivery_cost'] = '';
        $data['order_cost'] = '';
        $data['order_cost_type'] = '';
        $data['customer_fee_percent'] = '';
        $data['order_date'] = date ('Y-m-d H:i:s', time());
        $data['checked_out'] = '0';
        $data['locked'] = '0';
      }
  // If the delivery is not 'P' (pickup) then set the clobber $delivery_postal_code with the correct value
  if ($data['delivery_type'] == 'H')
    {
      $data['delivery_postal_code'] = $home_zip;
    }
  elseif ($data['delivery_type'] == 'W')
    {
      $data['delivery_postal_code'] = $work_zip;
    }
  // Check if there is a delivery_postal code provided This should possibly check against
  // the tax_rates table to see that the postal code is included there...?
  if (! $data['delivery_postal_code'])
    {
      die(debug_print('ERROR: 508 ', 'Requested basket has invalid delivery_postal_code', basename(__FILE__).' LINE '.__LINE__));
    }
  // Now open a basket with the provided (or guessed) information
  $query_open_basket = '
    REPLACE INTO '.NEW_TABLE_BASKETS.'
    SET'.
      ($data['basket_id'] ? '
      basket_id = "'.mysql_real_escape_string($data['basket_id']).'",' :
      ''
      ).' /* basket_id OR auto_increment */
      member_id = "'.mysql_real_escape_string($data['member_id']).'",
      delivery_id = "'.mysql_real_escape_string($data['delivery_id']).'",
      site_id = "'.mysql_real_escape_string($data['site_id']).'",
      delivery_type = "'.mysql_real_escape_string($data['delivery_type']).'",
      delivery_postal_code = "'.mysql_real_escape_string($data['delivery_postal_code']).'",
      delivery_cost = "'.mysql_real_escape_string($data['delivery_cost']).'",
      order_cost = "'.mysql_real_escape_string($data['order_cost']).'",
      order_cost_type = "'.mysql_real_escape_string($data['order_cost_type']).'",
      customer_fee_percent = "'.mysql_real_escape_string($data['customer_fee_percent']).'",
      order_date = "'.mysql_real_escape_string($data['order_date']).'",
      checked_out = "'.mysql_real_escape_string($data['checked_out']).'",
      locked = "'.mysql_real_escape_string($data['locked']).'"';
    $result_open_basket = mysql_query($query_open_basket, $connection) or die(debug_print ("ERROR: 895237 ", array ($query_open_basket,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $data['basket_id'] = mysql_insert_id();
    // Now do a re-checkout if necessary
    if ($initiate_checkout == true)
      {
        $basket_info = update_basket (array(
          'action' => 'synch_ledger_items',
          'basket_id ' => $data['basket_id'],
          'delivery_id' => $data['delivery_id'],
          'member_id' => $data['member_id'],
          'site_id' => $data['site_id'],
          'delivery_type' => $data['delivery_type']
          ));
        // Replace any information in the $data array with info returned from update_basket()
        foreach (array_keys($basket_info) as $key)
          {
            $data[$key] = $basket_info[$key];
          }
      }
    return ($data);
  }
?>