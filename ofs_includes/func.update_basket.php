<?php
include_once 'config_openfood.php';

// include_once ('func.get_basket_item.php');
include_once ('func.get_basket.php');
// include_once ('func.get_member.php');
// include_once ('func.get_producer.php');
// include_once ('func.get_product.php');
include_once ('func.update_ledger.php');
include_once ('func.update_basket_item.php');
// include_once ('func.open_basket.php');

session_start();
//valid_auth('cashier,site_admin');

// This function is used to update basket information
// Input data is an associative array with values:
// * action                ['checkout'|set_checkout'|'un_checkout'|'set_site']
// * basket_id             basket_id 
// * delivery_id           delivery_id 
// * member_id             member_id
// * site_id               [site_id]
// * delivery_type         [delivery_type]

function update_basket (array $data)
  {
//    debug_print ('INFO: Update Basket', $data);
    global $connection;
//    $member_id_you = $_SESSION['member_id'];
    $producer_id_you = $_SESSION['producer_id_you'];
    // Allow admins to override certain checks if the requested action is not for themselves
    $admin_override = true;
    if ($member_id_you == $data['member_id'] || ! CurrentMember::auth_type('cashier'))
      {
        $admin_override = false;
      }
// Set this value manually when converting from transactions to ledger accounting
$admin_override = true;
    // Set flags for needed validations and operations
    switch ($data['action'])
      {
        // checkout will checkout all the items in the basket
        case 'checkout':
          $test_for_membership_privilege = true;
          $test_customer_ordering_window = true;
          $initiate_set_checkout = true;
          $initiate_checkout_items = true;
          break;
        // same as "checkout" but only synchs items that were already checked out
        case 'synch_ledger_items':
          $test_for_membership_privilege = true;
          $test_customer_ordering_window = true;
          $initiate_set_checkout = true;
          $synch_ledger_items = true;
          break;
          // set_uncheckout is currently disabled
        case 'set_checkout':
          $test_for_membership_privilege = true;
          $test_customer_ordering_window = true;
          $initiate_set_checkout = true;
          break;
          // un_checkout is currently disabled
        case 'un_checkout':
          $test_customer_ordering_window = true;
          $initiate_un_checkout = true;
          break;
          // update the site (Pickup|Home|Work)
        case 'set_site':
          $update_site = true;
          break;
        default:
          die(debug_print('ERROR: 679217 ', 'unexpected request', basename(__FILE__).' LINE '.__LINE__));
          break;
      }
    // Get  information about the basket for this member
    // Prefer to access basket by basket_id
    if ($data['basket_id'] != 0)
      {
        $basket_info = get_basket ($data['basket_id']);
      }
    // Then try with member_id/delivery_id combination
    elseif ($data['member_id'] != 0 && $data['delivery_id'] != 0)
      {
        $basket_info = get_basket ($data['member_id'], $data['delivery_id']);
      }
    // Otherwise we don't know enough to get the basket
    else
      {
        die(debug_print('ERROR: 970893 ', 'incomplete information to locate basket', basename(__FILE__).' LINE '.__LINE__));
      }
    // Check that we actually got some basket information
    if (! is_array ($basket_info))
      {
        die(debug_print('ERROR: 701854 ', 'basket does not exist', basename(__FILE__).' LINE '.__LINE__));
      }
    // Check that the member is not pending or discontinued
    if ($test_for_membership_privilege && ! $admin_override)
      {
        if ($member_info['pending'] == 1 || $member_info['membership_discontinued'] == 1)
          {
            die(debug_print('ERROR: 974383 ', 'incorrect privilege to order', basename(__FILE__).' LINE '.__LINE__));
          }
      }
    // Check if shopping is closed for this order
    if ($test_customer_ordering_window && ! $admin_override)
      {
        if (ActiveCycle::ordering_window() == 'closed')
          {
            die(debug_print('ERROR: 823186 ', 'customer ordering period is not in effect', basename(__FILE__).' LINE '.__LINE__));
          }
      }
    // Update the basket with a new site and information related to the new site
    if ($update_site)
      {

debug_print ("ERROR: 892573 ", "UPDATE DELCODE", basename(__FILE__).' LINE '.__LINE__);

        if ($data['delivery_type'] == 'H' || $data['delivery_type'] == 'W') $query_delivery_type = 'D'; // H[ome] and W[ork] --> D[elivery]
        else $query_delivery_type = $data['delivery_type']; // P[ickup]
        // Could check for changes and abort otherwise, but this will force updating
        // delivery_postal_code just in case it might have changed.
        $query_site = '
          SELECT
            delivery_charge,
            delivery_postal_code
          FROM '.NEW_TABLE_SITES.'
          WHERE
            site_id = "'.mysql_real_escape_string($data['site_id']).'"
            AND delivery_type = "'.$query_delivery_type.'"
            AND inactive = "0"
            AND site_type = "customer"';
        $result_site = mysql_query($query_site, $connection) or die(debug_print ("ERROR: 892573 ", array ($query_site,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // Got we some information, then post the new information
        if ($row_site = mysql_fetch_array($result_site))
          {

            $query_update_basket = '
              UPDATE '.NEW_TABLE_BASKETS.'
              SET
                delivery_cost = "'.mysql_real_escape_string($row_site['delivery_charge']).'",
                delivery_postal_code = "'.mysql_real_escape_string($row['delivery_postal_code']).'",
                site_id = "'.mysql_real_escape_string($data['site_id']).'",
                delivery_type = "'.mysql_real_escape_string($data['delivery_type']).'"
              WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
            $result_update_basket = mysql_query($query_update_basket, $connection) or die(debug_print ("ERROR: 892764 ", array ($query_update_basket,mysql_error()), basename(__FILE__).' LINE '.__LINE__));

debug_print ("INFO: 892573 ", $query_update_basket, basename(__FILE__).' LINE '.__LINE__);

            // Update the $basket_info with changes
            $basket_info['delivery_cost'] = $row_site['delivery_charge'];
            $initiate_delivery_charge = true;
          }
        // Otherwise error
        else
          {
            die(debug_print('ERROR: 898952 ', 'requested site does not exist or is not available', basename(__FILE__).' LINE '.__LINE__));
          }
      }
    // Change the checked_out setting on the basket
    // Do this early so the update_basket_item will process the ledger items (only if they are in a checked-out state)
    if ($initiate_set_checkout)
      {
        // Get the number of items in the basket that are checked out
        $query = '
          SELECT
            '.NEW_TABLE_PRODUCTS.'.tangible,
            COUNT('.NEW_TABLE_BASKET_ITEMS.'.bpid) AS count
          FROM
            '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN
            '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
          WHERE
            '.NEW_TABLE_BASKET_ITEMS.'.basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"
          GROUP BY
            '.NEW_TABLE_PRODUCTS.'.tangible';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 758023 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        while ($row = mysql_fetch_array($result))
          {
            if ($row['tangible'] == '0') $intangible_count = $row['count'];
            if ($row['tangible'] == '1') $tangible_count = $row['count'];
          }
        // Preference is to set basket count to the number of *tangible* items in the basket
        if ($tangible_count > 0) $checked_out = $tangible_count;
        // Otherwise if there are no tangible items, we set to [negative] the number of *intangible* items in the basket
        elseif ($intangible_count > 0) $checked_out = 0 - $intangible_count;
        // Otherwise the basket is empty.
        else $checked_out = 0;
        $query = '
          UPDATE '.NEW_TABLE_BASKETS.'
          SET checked_out = "'.mysql_real_escape_string($checked_out).'"
          WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 892764 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // Sync the variable we just changed
        $basket_info['checked_out'] = $checked_out ;





        // If there is an order cost (fixed), then post it (or clear it if wrongly set).
        if ($basket_info['order_cost'] != 0 &&
            $basket_info['order_cost_type'] == 'fixed' &&
            $basket_info['checked_out'] != 0)
          {
            // Add the order cost to the ledger for this basket
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => 'member',
              'source_key' => $data['member_id'],
              'target_type' => 'internal',
              'target_key' => 'order_cost',
              'amount' => $basket_info['order_cost'],
              'text_key' => 'order cost',
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'match_keys' => array ('source_type','source_key','target_type','target_key','text_key','basket_id')
              ));
          }
        // If there is an order cost (percent), then post it (or clear it if wrongly set).
        elseif ($basket_info['order_cost'] != 0 &&
            $basket_info['order_cost_type'] == 'percent' &&
            $basket_info['checked_out'] != 0)
          {
            // First need to know the basket total to calculate the percent cost
            $query = '
              SELECT
                SUM(amount) AS order_total
              FROM
                '.NEW_TABLE_LEDGER.'
              WHERE
                basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"
                AND (text_key = "quantity cost"
                  OR text_key = "weight cost")';
            $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 678304 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysql_fetch_array($result))
              {
                $order_total = $row['order_total'];
                $order_cost_total = round ($row['order_total'] * $basket_info['order_cost'] / 100, 2);
              }
            // Add the order cost to the ledger for this basket
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => 'member',
              'source_key' => $data['member_id'],
              'target_type' => 'internal',
              'target_key' => 'order_cost',
              'amount' => $basket_info['order_cost'],
              'text_key' => 'order cost',
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'match_keys' => array ('source_type','source_key','target_type','target_key','text_key','basket_id')
              ));
          }





      }
    // For checkout, synchronize ledger entries to all basket_items
    if ($initiate_checkout_items || $synch_ledger_items)
      {
        // $initiate_checkout_items: check out all items and synch ledger
        // $synch_ledger_items:      repost existing checked_out items to the ledger
        if ($synch_ledger_items)
          {
            // Restrict to just the checked_out items
            $query_where = '
              AND checked_out != "0"';
          }
        // Get the items currently in the basket
        $query_basket_items = '
          SELECT
            bpid,
            product_id,
            product_version
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"'.
          $query_where;
        $result_basket_items = mysql_query($query_basket_items, $connection) or die(debug_print ("ERROR: 892785 ", array ($query_basket_items,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // Go through all the basket items (or all the checked_out items)
        while ($row_basket_items = mysql_fetch_array($result_basket_items))
          {
            $basket_item_info = update_basket_item (array(
              'action' => 'synch_ledger',
              'delivery_id' => $data['delivery_id'],
              'member_id' => $data['member_id'],
              'product_id' => $row_basket_items['product_id'],
              'product_version' => $row_basket_items['product_version']
              ));
            if (! is_array($basket_item_info))
              {
                die(debug_print ("ERROR: 902784 ", 'update_basket_item() did not return array.', basename(__FILE__).' LINE '.__LINE__));
              }
          }
      }
    // This is done for any/all changes, so not conditional except for baskets that are not checked-out.
    if ($basket_info['checked_out'] != 0)
      {
        // If there is a delivery charge, then post it (or clear it if wrongly set).
        if ($basket_info['delivery_cost'] != 0 || $initiate_delivery_charge)
          {
            // Add the delivery cost to the ledger for this basket
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => 'member',
              'source_key' => $data['member_id'],
              'target_type' => 'internal',
              'target_key' => 'delivery_cost',
              'amount' => $basket_info['delivery_cost'],
              'text_key' => 'delivery cost',
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'match_keys' => array ('source_type','source_key','target_type','target_key','text_key','basket_id')
              ));
          }
      }

//     // For un_checkout, clear all ledger entries related to the basket and basket_items
//     // This will remove or clear the cost of ledger entries for all products in the basket
//     if ($initiate_un_checkout)
//       {
//         // Get the items currently in the basket
//         $query_basket_items = '
//           SELECT
//             bpid,
//             product_id,
//             product_version
//           FROM '.NEW_TABLE_BASKET_ITEMS.'
//           WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
//         $result_basket_items = mysql_query($query_basket_items, $connection) or die(debug_print ("ERROR: 892785 ", array ($query_basket_items,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//         // Go through all the basket items
//         while ($row_basket_items = mysql_fetch_array($result_basket_items))
//           {
//             // Problem: clear_item removes all quantity from the basket. We would like to leave the basket unchanged.
//             // ... but if we define that as the desired behavior, then we have something, at least...
//             $basket_item_info = update_basket_item (array(
//               'action' => 'un_checkout',
//               'delivery_id' => $data['delivery_id'],
//               'member_id' => $data['member_id'],
//               'product_id' => $row_basket_items['product_id'],
//               'product_version' => $row_basket_items['product_version'],
//               'post_even_if_zero' => 'YES'
//               ));
//             if ($basket_item_info != 'clear_item:'.$row_basket_items['bpid'])
//               {
//                 return('error 100: expected "clear_item:'.$row_basket_items['bpid'].'" but got "'.$basket_item_info.'"');
//               }
//           }
//         // And un-checkout the basket as well
//         // Remove the delivery cost from the ledger for this basket
//         $ledger_status = basket_item_to_ledger(array (
//           'source_type' => 'member',
//           'source_key' => $data['member_id'],
//           'target_type' => 'internal',
//           'target_key' => 'delivery_cost',
//           'amount' => 0,
//           'text_key' => 'delivery cost',
//           'posted_by' => $_SESSION['member_id'],
//           'basket_id' => $basket_info['basket_id'],
//           'site_id' => $basket_info['site_id'],
//           'delivery_id' => $basket_info['delivery_id'],
//           'match_keys' => array ('source_type','source_key','target_type','target_key','text_key','basket_id')
//           ));
//       }
//     // Change the checked_out setting on the basket
//     // Do this last so the update_basket_item will clear ledger items (only if they are in a checked-out state)
//     if ($initiate_un_checkout)
//       {
//         $query = '
//           UPDATE '.NEW_TABLE_BASKETS.'
//           SET checked_out = "0"
//           WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
//         $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 892764 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//         $basket_info['checked_out'] = 0;
//       }

    // At this point, all basket information has been updated, so we need to consider any changes to the ledger.
// * messages                    link a message to this transaction
// * post_even_if_zero              'YES' will delete the transaction is zero and a singleton


// NEED TO ADD PAYPAL SURCHARGE CALCULATION

    // Return the new (possibly changed) basket_info array
    return ($basket_info);
  }
?>