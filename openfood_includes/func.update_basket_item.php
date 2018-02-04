<?php
include_once 'config_openfood.php';

include_once ('func.get_basket_item.php');
include_once ('func.get_basket.php');
include_once ('func.get_member.php');
include_once ('func.get_producer.php');
include_once ('func.get_product.php');
include_once ('func.update_ledger.php');
include_once ('func.update_basket.php');
// include_once ('func.open_basket.php');

if (!isset ($_SESSION)) session_start();
// debug_print ('INFO: Session:', $_SESSION);
//valid_auth('cashier,site_admin');


// This function is used to add / change / remove a basket_item
// Input data is an associative array with values:
// * action                 [set_quantity|delete_item|convert_to_active_version|set_message_to_producer|set_outs|set_weight|checkout|set_all_producer|set_everything|clear_item|synch_ledger|producer_synch_ledger]
// * delivery_id            delivery_id
// * member_id              member_id
// * product_id             product_id
// * product_version        product_version
// SPECIAL VALUES: ------------------------------------------------------------
// * messages               [optional messages connected to basket_item]
// * quantity               [arguments (quantity: e.g. '6', '+1' '-3')]
// * out_of_stock           [arguments (number of ordering units (of quantity) that will NOT be delivered)]
// * weight                 [arguments (weight: e.g. '4.32')]
// * transaction_group_id   [optional value to pass through to transaction_type field]

function update_basket_item (array $data)
  {
//    debug_print ('INFO: Update Basket', $data);
    global $connection;
//    $member_id_you = $_SESSION['member_id'];
    $producer_id_you = $_SESSION['producer_id_you'];
    // Allow admins to override certain checks if the requested action is not for themselves
    $admin_override_not_set = false;
    if ($member_id_you == $data['member_id'] || ! CurrentMember::auth_type('cashier'))
      {
        $admin_override_not_set = true;
      }
    // Set flags for needed validations and operations
    switch ($data['action'])
      {
        case 'set_quantity':
          $test_for_valid_product = true;
          $test_for_customer_privilege = true;
          $test_for_membership_privilege = true;
          $test_customer_ordering_window = true;
          $test_product_availability = true;
          $test_for_producer_privilege = true;
          $initiate_basket_item = true;
          $test_basket_item_exists = true;
          $initiate_change_quantity = true;
          $initiate_set_message_to_producer = true;
          break;
        case 'delete_item':
          $test_customer_ordering_window = true;
          $delete_basket_item = true;
          break;
        case 'convert_to_active_version':
          // This is mainly for error-correction so is always allowed.
          $convert_to_active_version = true;
          break;
        case 'set_message_to_producer':
          $test_for_valid_product = true;
          $test_customer_ordering_window = true;
          $initiate_set_message_to_producer = true;
          break;
        case 'set_outs':
          $test_for_valid_product = true;
          $test_for_producer_privilege = true;
          $test_basket_item_exists = true;
          $test_producer_update_window = true;
          $initiate_change_outs = true;
          break;
        case 'set_weight':
          $test_for_valid_product = true;
          $test_for_producer_privilege = true;
          $test_basket_item_exists = true;
          $test_producer_update_window = true;
          $initiate_change_weight = true;
          break;
        case 'checkout':
          $test_for_valid_product = true;
          $test_for_membership_privilege = true;
          $test_customer_ordering_window = true;
          $test_basket_item_exists = true;
          $initiate_set_message_to_producer = true;
          $initiate_synch_ledger = true;
          break;
        case 'set_all_producer':
          $test_for_valid_product = true;
          $test_basket_item_exists = true;
          $test_for_producer_privilege = true;
          $test_producer_update_window = true;
          $initiate_change_outs = true;
          $initiate_change_weight = true;
          break;
        case 'set_everything':
          $test_for_valid_product = true;
          $test_basket_item_exists = true;
          $test_customer_ordering_window = true;
          $initiate_change_quantity = true;
          $initiate_change_outs = true;
          $initiate_clear_weight = true;
          $initiate_clear_item = true;
          $initiate_set_message_to_producer = true;
          break;
        case 'clear_item': // Used when un_checking_out
          $test_for_valid_product = true;
          $test_basket_item_exists = true;
          $test_customer_ordering_window = true;
          $data['quantity'] = '0';
          $data['out_of_stock'] = '0';
          $data['weight'] = '0';
          $initiate_change_quantity = true;
          $initiate_change_outs = true;
          $initiate_clear_weight = true;
          $initiate_clear_item = true;
          break;
        case 'synch_ledger': // Used when checking_out
          $test_for_valid_product = true;
          $test_for_membership_privilege = true;
          $test_customer_ordering_window = true;
          $test_basket_item_exists = true;
          $initiate_synch_ledger = true;
          break;
        case 'producer_synch_ledger': // Used when checking_out
          $test_for_valid_product = true;
          $test_basket_item_exists = true;
          $test_for_producer_privilege = true;
          $test_producer_update_window = true;
          $initiate_synch_ledger = true;
          break;
        default:
          $error = 'Unexpected request: '.$action;
          debug_print ("WARNING 780321", array ('message'=>$error, 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
          return $error;
          break;
      }
    // Check if the product exists, regardless of $admin_override_not_set
    if ($test_for_valid_product)
      {
        $product_info = get_product ($data['product_id'], $data['product_version'], $data['pvid']);
        if (! is_array ($product_info))
          {
            $error = 'Product not found in database';
            debug_print ("WARNING 786021", array ('message'=>$error, 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $error;
          }
      }
    // Get  information about the basket for this member
    // This needs to be done before the availability check
    $basket_info = get_basket ($data['member_id'], $data['delivery_id']);
    // See if we already have this basket_item
    if (is_array ($basket_info))
      {
        $basket_item_info = get_basket_item ($basket_info['basket_id'], $data['product_id'], $data['product_version']);
      }
    else // For now, we are not going to deal with any case where the basket is not yet opened
      {
        $basket_item_info['error'] = 'Basket does not exist';
        debug_print ("WARNING 892456", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
        return $basket_item_info;
      }
    // Check if the basket is locked
    if ($basket_info['locked'] == 1)
      {
        $basket_item_info['error'] = 'Basket is locked';
        debug_print ("WARNING 893249", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
        return $basket_item_info;
      }
    // Check if this producer is permitted and enabled to sell
    if ($test_for_producer_privilege
        && $admin_override_not_set)
      {
        $producer_info = get_producer ($product_info['producer_id']);
        if ($producer_info['unlisted_producer'] > 0 || $producer_info['pending'] == 1)
          {
            $basket_item_info['error'] = 'Producer is restricted from selling';
            debug_print ("WARNING 728478", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }
      }
    // Check if the customer is allowed to purchase this product
    if ($test_for_customer_privilege
        && $admin_override_not_set)
      {
        $member_info = get_member ($data['member_id']);
        $member_auth_type_array = explode (',', $member_info['auth_type']);
        // $product_info['listing_auth_type'] contains the *necessary* auth_type to buy this product
        // and $member_auth_type_array contains all the members' allowable auth_types
        // listing_auth_types archived and unlisted are not allowed for members, so can never be ordered
        if (! is_array ($member_auth_type_array) ||
          ! in_array ($product_info['listing_auth_type'], $member_auth_type_array))
          {
            $basket_item_info['error'] = 'Incorrect privilege to purchase requested product';
            debug_print ("WARNING 289314", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }
      }
    // Check that the member is not pending or discontinued
    if ($test_for_membership_privilege
        && $admin_override_not_set)
      {
        if ($member_info['pending'] == 1 || $member_info['membership_discontinued'] == 1)
          {
            $basket_item_info['error'] = 'Incorrect privilege to order';
            debug_print ("WARNING 899021", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }
      }
    // Check if shopping is closed for this order
    if ($test_customer_ordering_window
        && $admin_override_not_set)
      {
        if (ActiveCycle::ordering_window() == 'closed'
            && ! CurrentMember::auth_type('orderex'))
          {
            $basket_item_info['error'] = 'Customer ordering period is not in effect';
            debug_print ("WARNING 934229", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }
      }
    // Check if the product can be delivered to this site_id
    if ($test_product_availability
        && $admin_override_not_set)
      {
        if ($producer_info['available_site_ids'] != '' && // Empty means product is available everywhere
          ! in_array ($basket_info['site_id'], explode(',',$producer_info['available_site_ids'])))
          {
            $basket_item_info['error'] = 'Producer does not sell at this location';
            debug_print ("WARNING 894211", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }
      }
    // Check if producer activity is taking place within the producer update window
    if ($test_producer_update_window
        // && $producer_id_you
        && $admin_override_not_set)
      {
        if (ActiveCycle::producer_update_window() == 'closed')
          {
            $basket_item_info['error'] = 'Producer update window is closed';
            debug_print ("WARNING 752932", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }
      }
    // Check if this product bpid already has a basket item
    if ($initiate_basket_item
        && ! $basket_item_info['bpid'])
      {
        // Create an empty basket item if one does not already exist...
        $query = '
          INSERT INTO '.NEW_TABLE_BASKET_ITEMS.'
            (
              /* bpid, */
              basket_id,
              product_id,
              product_version,
              quantity,
              total_weight,
              product_fee_percent,
              subcategory_fee_percent,
              producer_fee_percent,
              out_of_stock,
              future_delivery,
              future_delivery_type,
              date_added
            )
          SELECT
            '.mysqli_real_escape_string ($connection, $basket_info['basket_id']).' AS basket_id,
            product_id,
            product_version,
            "0" AS quantity,
            "0" AS total_weight,
            product_fee_percent,
            subcategory_fee_percent,
            producer_fee_percent,
            "0" AS out_of_stock,
            future_delivery,
            future_delivery_type,
            NOW() AS date_added
          FROM '.NEW_TABLE_PRODUCTS.'
          LEFT JOIN '.TABLE_SUBCATEGORY.' USING(subcategory_id)
          LEFT JOIN '.TABLE_CATEGORY.' USING(category_id)
          LEFT JOIN '.TABLE_PRODUCER.' USING(producer_id)
          WHERE
            product_id = "'.mysqli_real_escape_string ($connection, $product_info['product_id']).'"
            AND product_version = "'.mysqli_real_escape_string ($connection, $product_info['product_version']).'"';
        $result = mysqli_query ($connection, $query)
          or debug_print ("ERROR: 748032 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__);
        // Now get the basket information we just posted
        $basket_item_info = get_basket_item ($basket_info['basket_id'], $data['product_id'], $data['product_version']);
      }

//     // Transform/combine versions of this product in the basket       CURRENTLY DISABLED!
//     if ($convert_to_active_version
//         || $basket_item_info['number_of_versions'] > 0)
//       {
//         // If there is already another version of this product in the basket then modify to match
//         // the current version while retaining stocking levels (quantity, total_weight, out_of_stock)
//         // This is to prevent multiple product versions from occupying the same basket
//         if ($convert_to_active_version == true) // This is done with customer's approval
//           {
//             // Convert to active version
//             $assign_product_version = $basket_item_info['active_product_version'];
//             debug_print ("INFO: 756932 ", array('Converting product version ('.$basket_item_info['product_version'].') to active ('.$basket_item_info['active_product_version'].'):', $data), basename(__FILE__).' LINE '.__LINE__);
//           }
//         else // This is done without customer's approval
//           {
//             // Convert to current version
//             $assign_product_version = $data['product_version'];
//             debug_print ("INFO: 756932 ", array('Converting product version ('.$basket_item_info['product_version'].') to current ('.$data['product_version'].'):', $data), basename(__FILE__).' LINE '.__LINE__);
//           }
//         $query = '
//           UPDATE '.NEW_TABLE_BASKET_ITEMS.'
//           LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
//           LEFT JOIN '.TABLE_SUBCATEGORY.' USING(subcategory_id)
//           LEFT JOIN '.TABLE_CATEGORY.' USING(category_id)
//           LEFT JOIN '.TABLE_PRODUCER.' USING(producer_id)
//           SET
//               /* '.NEW_TABLE_BASKET_ITEMS.'.bpid, */
//               /* '.NEW_TABLE_BASKET_ITEMS.'.basket_id, */
//               /* '.NEW_TABLE_BASKET_ITEMS.'.product_id, */
//               '.NEW_TABLE_BASKET_ITEMS.'.product_version = "'.mysqli_real_escape_string ($connection, $assign_product_version).'",
//               /* '.NEW_TABLE_BASKET_ITEMS.'.quantity, */
//               /* '.NEW_TABLE_BASKET_ITEMS.'.total_weight, */
//               '.NEW_TABLE_BASKET_ITEMS.'.product_fee_percent = '.NEW_TABLE_PRODUCTS.'.product_fee_percent,
//               '.NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent = '.TABLE_SUBCATEGORY.'.subcategory_fee_percent,
//               '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent = '.TABLE_PRODUCER.'.producer_fee_percent,
//               /* '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock, */
//               '.NEW_TABLE_BASKET_ITEMS.'.future_delivery = '.NEW_TABLE_PRODUCTS.'.future_delivery,
//               '.NEW_TABLE_BASKET_ITEMS.'.future_delivery_type = '.NEW_TABLE_PRODUCTS.'.future_delivery_type
//               /* '.NEW_TABLE_BASKET_ITEMS.'.date_added */
//           WHERE
//             bpid = "'.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'"
//             AND basket_id = "'.mysqli_real_escape_string ($connection, $basket_info['basket_id']).'"'; // This last WHERE condition should not be needed
//         $result = mysqli_query ($connection, $query)
//           or debug_print ("ERROR: 748032 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__);
//         // Now get the basket information we just posted
//         $basket_item_info = get_basket_item ($basket_info['basket_id'], $data['product_id'], $data['product_version']);
//       }

    // Check for basket item
    if ($test_basket_item_exists
        && (! is_array ($basket_item_info)
            || ! $basket_item_info['bpid']))
      {
        $basket_item_info['error'] = 'Basket item does not exist';
        debug_print ("WARNING 834021", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
        return $basket_item_info;
      }
    // Update the quantity for this basket_item and adjust inventory accordingly
    if ($initiate_change_quantity
        && ! $basket_item_info['checked_out']) // No changes after checkout
      {
        // Initialize
        $old_out_of_stock = $basket_item_info['out_of_stock'];
        $old_requested_quantity = $basket_item_info['quantity'];
        // The following code is built around adding/subtracting [quantity], so we will first adjust
        // the input to fit that method. If $data['quantity'] is like "+1" or "-2" then increment/decrement
        if (preg_match('/^([\+\-])(\d+)$/', $data['quantity'], $matches))
          {
            if ($matches[1] == '+')
              {
                $delta_quantity = $matches[2]; // positive
              }
            elseif ($matches[1] == '-')
              {
                $delta_quantity = 0 - $matches[2]; // negative
              }
            else
              {
                $basket_item_info['error'] = 'Unexpected value for $data[quantity]:'.$data['quantity'];
                debug_print ("WARNING 789830", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
                return $basket_item_info;
              }
          }
        // Otherwise we are requesting a specific quantity, so figure out what the *change* to that number would be
        elseif (preg_match('/^(\d+)$/', $data['quantity'], $matches))
          {
            $delta_quantity = $matches[1] - $old_requested_quantity; // Amount to increase the basket by
          }
        // Invalid quantity
        else
          {
            $basket_item_info['error'] = 'Unexpected value for $data[quantity]:'.$data['quantity'];
            debug_print ("WARNING 785932", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }

        // If the new_requested_quantity is more than the old_requested_quantity
        // and this is not the current active version, then do not proceed
        if ($delta_quantity > 0 && $basket_item_info['active_product_version'] != $basket_item_info['product_version'])
          {
            $basket_item_info['error'] = 'Attempt to increase quantity for non-active product';
            debug_print ("WARNING 678530", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }
        // Add everything we want into the new_requested_quantity
        $new_requested_quantity = $old_requested_quantity + $delta_quantity;

        // If this is an inventory-controlled item
        if ($product_info['inventory_id'] != 0)
          {
            $old_inventory_quantity = $product_info['inventory_quantity'];
            // Note that available_inventory is the number of inventory_pull units available for this
            // particular product and might actually leave a few inventory items remaining.
            $old_quantity_available = floor ($old_inventory_quantity / $product_info['inventory_pull']);
            // quantity_available is the number of inventory_pull units available
            // Also subtract any out_of_stock and set that to zero
            $new_quantity_available = $old_quantity_available - $delta_quantity - $old_out_of_stock;
            $new_out_of_stock = 0;
            // Fix quantity, if necessary, by converting into out_of_stock
            if ($new_quantity_available < 0)
              {
                $new_out_of_stock = 0 - $new_quantity_available;
                $new_quantity_available = 0;
              }
            if (ALLOW_RESERVE_ORDERING == false)
              {
                // Fix out_of_stock, if necessary, by setting it to zero
                $new_requested_quantity = $new_requested_quantity - $new_out_of_stock;
                $new_out_of_stock = 0;
              }
            // Did we go negative on new_requested_quantity?
            if ($new_requested_quantity < 0)
              {
                // Put it back into inventory and zero-out the request
                $new_quantity_available = $new_quantity_available + $new_requested_quantity;
                $new_requested_quantity = 0;
              }
            // Prepare to post new inventory value into the database
            $new_inventory_quantity = $old_inventory_quantity - (($old_quantity_available - $new_quantity_available) * $product_info['inventory_pull']);
            // Now post the inventory changesmysqli_affected_rows ($connection)
            // Since the inventory could have changed since we started calculations, we need
            // to check for that.
            if ($new_inventory_quantity != $old_inventory_quantity)
              {
                $query = '
                  UPDATE '.TABLE_INVENTORY.'
                  SET quantity = "'.mysqli_real_escape_string ($connection, $new_inventory_quantity).'"
                  WHERE
                    inventory_id = "'.mysqli_real_escape_string ($connection, $product_info['inventory_id']).'"
                    AND quantity = "'.mysqli_real_escape_string ($connection, $old_inventory_quantity).'"'; // Only if inventory is same as when we started
                $result = mysqli_query ($connection, $query) or die(debug_print ("ERROR: 902784 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
                if (mysqli_affected_rows ($connection) == 0)
                  {
                    // Inventory was not changed, so quantity must no longer match the original product_info[inventory_quantity]
                    // We could go through some kind of recursion to calculate a new inventory value, but expect this to be
                    // a rare occurrence, so will just DO NOTHING and abort further updates. The customer will probably just push
                    // the button again.
                    $basket_item_info['error'] = 'Inventory mismatch during query';
                    debug_print ("WARNING 676289", array ('message'=>$basket_item_info['error'], 'query'=>$query), basename(__FILE__).' LINE '.__LINE__);
                    return $basket_item_info;
                  }
              }
          }
        // Otherwise it is not an inventory-controlled item
        else
          {
            // If this somehow changed, then clear the out_of_stock setting
            $new_out_of_stock = 0;
            // Make sure the new quantity isn't less than zero
            if ($new_requested_quantity < 0)
              {
                $new_requested_quantity = 0;
              }
          }
        // Set these variables for use later
        $basket_item_info['quantity'] = $new_requested_quantity;
        $basket_item_info['out_of_stock'] = $new_out_of_stock;
        // Update the basket quantity and out_of_stock
        $query = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET
            quantity = "'.mysqli_real_escape_string ($connection, $new_requested_quantity).'",
            out_of_stock = "'.mysqli_real_escape_string ($connection, $new_out_of_stock).'"
          WHERE bpid = "'.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'"';
        $result = mysqli_query ($connection, $query) or die(debug_print ("ERROR: 842075 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        // If we hit zero in basket, then delete the basket_item altogether
        if ($new_requested_quantity <= 0)
          {
            $delete_basket_item = true; // Operation performed further down in this script
          }
      }
    // Not changing quantity, so just set variables we will need
    else
      {
        $new_requested_quantity = $basket_item_info['quantity'];
      }
    // Change the "out" setting on this item
    if ($initiate_change_outs
        && $basket_item_info['checked_out']) // Only allow after checkout
      {
        // If $data['out_of_stock'] is like "+1" or "-2" then increment/decrement
        if (preg_match('/^([\+\-])(\d+)$/', $data['out_of_stock'], $matches))
          {
            // Increase out_of_stock :: decreases actual order quantity
            if ($matches[1] == '+') $new_out_of_stock = $basket_item_info['out_of_stock'] + $matches[2];
            // Decrease out_of_stock :: increases actual order quantity
            elseif ($matches[1] == '-') $new_out_of_stock = $basket_item_info['out_of_stock'] - $matches[2];
          }
        // Otherwise, just set the out_of_stock directly
        elseif (preg_match('/^(\d+)$/', $data['out_of_stock'], $matches))
          {
            $new_out_of_stock = $matches[1];
          }
        else
          {
            $basket_item_info['error'] = 'Unexpected value for out_of_stock change: '.$data['out_of_stock'];
            debug_print ("WARNING 874042", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
            return $basket_item_info;
          }
        // Make sure we have not outed more than the total quantity in the basket
        if ($new_out_of_stock > $basket_item_info['quantity'])
          {
            $new_out_of_stock = $basket_item_info['quantity'];
          }
        // Make sure we have not outed a negative number
        if ($new_out_of_stock < 0)
          {
            $new_out_of_stock = 0;
          }
        // Update the basket_item with the new quantities
        $query = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET out_of_stock = "'.mysqli_real_escape_string ($connection, $new_out_of_stock).'"
          WHERE bpid = "'.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'"';
        $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 784303 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    // Set a basket_item message for this item
    if ($initiate_set_message_to_producer
        && $basket_item_info['bpid']
        && is_array ($data['messages']) // No bother if there is no message
        && !$basket_item_info['checked_out']) // No changes after checkout
      {
        foreach ($data['messages'] as $message_type => $message)
          {
            // First delete any message(s) with this message_type_id and referenced_key1
            $query_post_message = '
              DELETE FROM '.NEW_TABLE_MESSAGES.'
              WHERE
                message_type_id = 
                  COALESCE((
                    SELECT message_type_id
                    FROM '.NEW_TABLE_MESSAGE_TYPES.'
                    WHERE key1_target = "basket_items.bpid"
                    AND description = "'.mysqli_real_escape_string ($connection, $message_type).'"
                    LIMIT 1
                    )
                  ,0)
                AND referenced_key1 = "'.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'"';
            $result_post_message = mysqli_query ($connection, $query_post_message) or die(debug_print ("ERROR: 789021 ", array ($query_post_message,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            // Then, if there is a message, then add it back in
            if (strlen ($message) > 0)
              {
                // Use [0]:orphaned message in case the description is not found
                $query_post_message = '
                  INSERT INTO '.NEW_TABLE_MESSAGES.'
                  SET
                    message = "'.mysqli_real_escape_string ($connection, $message).'",
                    message_type_id = 
                      COALESCE((
                        SELECT message_type_id
                        FROM '.NEW_TABLE_MESSAGE_TYPES.'
                        WHERE key1_target = "basket_items.bpid"
                        AND description = "'.mysqli_real_escape_string ($connection, $message_type).'"
                        LIMIT 1
                        )
                      ,0),
                    referenced_key1 = "'.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'"';
                $result_post_message = mysqli_query ($connection, $query_post_message) or die(debug_print ("ERROR: 789021 ", array ($query_post_message,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
              }
          }
      }
    // Clear any weight that might have been set for this item
    if ($initiate_clear_weight
        && $basket_item_info['random_weight'])
      {
        $total_weight = $data['weight'];
        $query = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET total_weight = "'.mysqli_real_escape_string ($connection, $total_weight).'"
          WHERE bpid = "'.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'"';
        $result = mysqli_query ($connection, $query) or die(debug_print ("ERROR: 890254 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    // Update the weight for this item
    if ($initiate_change_weight
        && $basket_item_info['random_weight']
        && $basket_item_info['checked_out']) // Only allow after checkout)
      {
        $total_weight = $data['weight'];
        if ($new_requested_quantity - $new_out_of_stock)
          {
            $average_weight =  $data['weight'] / ($new_requested_quantity - $new_out_of_stock);
            if (ENFORCE_WEIGHT_LIMITS == true)
              {
                // Check for weight in specified range (admins may override this check)
                if ($average_weight < $basket_item_info['minimum_weight'] && $admin_override_not_set)
                  {
                    $basket_item_info['total_weight'] = $basket_item_info['minimum_weight'] * ($new_requested_quantity - $new_out_of_stock);
                  }
                elseif ($average_weight > $basket_item_info['maximum_weight'] && $admin_override_not_set)
                  {
                    $basket_item_info['total_weight'] = $basket_item_info['maximum_weight'] * ($new_requested_quantity - $new_out_of_stock);
                  }
              }
          }
        else // no items... so no weight
          {
            $average_weight = 0;
            $total_weight = 0;
          }
        $query = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET total_weight = "'.mysqli_real_escape_string ($connection, $total_weight).'"
          WHERE bpid = "'.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'"';
        $result = mysqli_query ($connection, $query) or die(debug_print ("ERROR: 520561 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    // Delete a basket item (primarily used when its quantity has been set to zero)
    if ($delete_basket_item == true
        && is_array($basket_item_info)
        && is_array($product_info))
      {
        // Restore inventory that remains in the basket_item
        $excess_in_basket = $basket_item_info['quantity'] - $basket_item_info['out_of_stock'];
        if ($excess_in_basket > 0)
          {
            // This does work with negative numbers for $new_requested_quantity
            $query = '
              UPDATE '.TABLE_INVENTORY.'
              SET quantity = quantity + "'.mysqli_real_escape_string ($connection, $excess_in_basket * $product_info['inventory_pull']).'"
              WHERE inventory_id = "'.mysqli_real_escape_string ($connection, $product_info['inventory_id']).'"';
            $result = mysqli_query ($connection, $query) or die(debug_print ("ERROR: 750324 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
        // Remove messages attached to the basket_item
        if ($basket_item_info['bpid'] != 0)
          {
            $query = '
              DELETE FROM
                '.NEW_TABLE_MESSAGES.'
              WHERE
                referenced_key1 = '.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'
                AND message_type_id =
                  (
                    SELECT message_type_id
                    FROM '.NEW_TABLE_MESSAGE_TYPES.'
                    WHERE description = "customer notes to producer"
                  )';
            $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 678230 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
        // Remove the basket item itself (all versions!)
        $query = '
          DELETE FROM
            '.NEW_TABLE_BASKET_ITEMS.'
          WHERE
            basket_id = "'.mysqli_real_escape_string ($connection, $basket_item_info['basket_id']).'"
            AND product_id = "'.mysqli_real_escape_string ($connection, $basket_item_info['product_id']).'"
            AND product_version = "'.mysqli_real_escape_string ($connection, $basket_item_info['product_version']).'"';
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 278934 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    if ($initiate_synch_ledger == true)
      {
        // If the requested action is just to synch the ledger, then we need to preset these values:
        $new_out_of_stock = $basket_item_info['out_of_stock'];
        $new_requested_quantity = $basket_item_info['quantity'];
        $product_tax_basis = 0;
        $fee_tax_basis = 0;
        // And make sure the basket is also checked out
        $test_info = update_basket (array (
          'action' => 'set_checkout',
          'basket_id' => $basket_info['basket_id'],
          'member_id' => $data['member_id']
          ));
        // Sync the checked_out field
        $basket_info['checked_out'] = $test_info['checked_out'];
        $query = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET checked_out = "1"
          WHERE bpid = "'.mysqli_real_escape_string ($connection, $basket_item_info['bpid']).'"';
        $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 893020 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        // Sync the checked_out field
        $basket_item_info['checked_out'] = "1";
      }

    // If the quantity has become zero (i.e. out_of_stock == requested_quantity) then we don't want to
    // charge people for random weight items -- even if a weight is entered -- so clobber the weight
    if ($new_out_of_stock == $new_requested_quantity)
      {
        // i.e. There are no items in stock... then multiply certain costs by zero
        $basket_item_info['total_weight'] = 0;
      }

    // At this point, all basket_item information has been set, so we need to consider
    // changes in the ledger. This is done for any/all changes, so not conditional
    // except for baskets that are not checked-out.
    if ($basket_info['checked_out'] != 0)
      {
        // If this product is configured with an extra_charge, then post it
        if ($product_info['extra_charge'] != 0)
          {
            $extra_charge = ($new_requested_quantity - $new_out_of_stock) * $product_info['extra_charge'];
            // Assumption is that extra_charges are passed through to the producer
            // Should they/could they be held by the co-op???
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => 'member',
              'source_key' => $data['member_id'],
              'target_type' => 'producer',
              'target_key' => $product_info['producer_id'],
              'amount' => $extra_charge,
              'text_key' => 'extra charge',
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'bpid' => $basket_item_info['bpid'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'pvid' => $product_info['pvid'],
              'match_keys' => array ('text_key','bpid')
              ));
          }
        // If this product is configured with a regular cost, then post it
        if ($product_info['unit_price'] != 0)
          {
            if ($product_info['random_weight'] == 1)
              {
                $total_price = $product_info['unit_price'] * $basket_item_info['total_weight'];
                $text_key = 'weight cost';
              }
            elseif ($product_info['random_weight'] == 0)
              {
                $total_price = $product_info['unit_price'] * ($new_requested_quantity - $new_out_of_stock);
                $text_key = 'quantity cost';
              }
            else
              {
                $basket_item_info['error'] = 'Value out of range for random_weight';
                debug_print ("WARNING 579210", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
                return $basket_item_info;
              }
            // Start accumulating basis for taxation
            $product_tax_basis += $total_price;
            // Write the transaction to the ledger
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => 'member',
              'source_key' => $data['member_id'],
              'target_type' => 'producer',
              'target_key' => $product_info['producer_id'],
              'amount' => $total_price,
              'text_key' => $text_key,
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'bpid' => $basket_item_info['bpid'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'pvid' => $product_info['pvid'],
              'match_keys' => array ('text_key','bpid')
              ));
          }
        // If there is a product fee, post it
        if ($basket_item_info['product_fee_percent'] != 0 && PAYS_PRODUCT_FEE != 'nobody')
          {
            $product_adjust_amount = $basket_item_info['product_fee_percent'] * $total_price / 100;
            if (PAYS_PRODUCT_FEE == 'customer')
              {
                $source_type = 'member';
                $source_key = $data['member_id'];
                // Accumulate basis for taxation
                $fee_tax_basis += $product_adjust_amount;
              }
            elseif (PAYS_PRODUCT_FEE == 'producer')
              {
                $source_type = 'producer';
                $source_key = $product_info['producer_id'];
              }
            else
              {
                $basket_item_info['error'] = 'No designated payee for product fee';
                debug_print ("WARNING 865029", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
                return $basket_item_info;
              }
            // Post product fee to the ledger for each product
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => $source_type,
              'source_key' => $source_key,
              'target_type' => 'internal',
              'target_key' => 'product_fee',
              'amount' => $product_adjust_amount,
              'text_key' => 'product fee',
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'bpid' => $basket_item_info['bpid'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'pvid' => $product_info['pvid'],
              'match_keys' => array ('text_key','bpid')
              ));
          }
        // If there is a subcategory fee, post it
        if ($basket_item_info['subcategory_fee_percent'] != 0 && PAYS_SUBCATEGORY_FEE != 'nobody')
          {
            $subcategory_adjust_amount = $basket_item_info['subcategory_fee_percent'] * $total_price / 100;
            if (PAYS_SUBCATEGORY_FEE == 'customer')
              {
                $source_type = 'member';
                $source_key = $data['member_id'];
                // Accumulate basis for taxation
                $fee_tax_basis += $subcategory_adjust_amount;
              }
            elseif (PAYS_SUBCATEGORY_FEE == 'producer')
              {
                $source_type = 'producer';
                $source_key = $product_info['producer_id'];
              }
            else
              {
                $basket_item_info['error'] = 'No designated payee for subcategory fee';
                debug_print ("WARNING 802421", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
                return $basket_item_info;
              }
            // Post subcategory fee to the ledger for each product
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => $source_type,
              'source_key' => $source_key,
              'target_type' => 'internal',
              'target_key' => 'subcategory_fee',
              'amount' => $subcategory_adjust_amount,
              'text_key' => 'subcategory fee',
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'bpid' => $basket_item_info['bpid'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'pvid' => $product_info['pvid'],
              'match_keys' => array ('text_key','bpid')
              ));
          }
        // If there is a producer fee, post it
        if ($basket_item_info['producer_fee_percent'] != 0 && PAYS_PRODUCER_FEE != 'nobody')
          {
            $producer_adjust_amount = $basket_item_info['producer_fee_percent'] * $total_price / 100;
            if (PAYS_PRODUCER_FEE == 'customer')
              {
                $source_type = 'member';
                $source_key = $data['member_id'];
                // Accumulate basis for taxation
                $fee_tax_basis += $producer_adjust_amount;
              }
            elseif (PAYS_PRODUCER_FEE == 'producer')
              {
                $source_type = 'producer';
                $source_key = $product_info['producer_id'];
              }
            else
              {
                $basket_item_info['error'] = 'No designated payee for producer fee';
                debug_print ("WARNING 825489", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
                return $basket_item_info;
              }
            // Post producer fee to the ledger for each product
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => $source_type,
              'source_key' => $source_key,
              'target_type' => 'internal',
              'target_key' => 'producer_fee',
              'amount' => $producer_adjust_amount,
              'text_key' => 'producer fee',
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'bpid' => $basket_item_info['bpid'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'pvid' => $product_info['pvid'],
              'match_keys' => array ('text_key','bpid')
              ));
          }
        // If there is a customer fee, post it
        if ($basket_info['customer_fee_percent'] != 0 && PAYS_CUSTOMER_FEE != 'nobody')
          {
            $customer_adjust_amount = $basket_info['customer_fee_percent'] * $total_price / 100;
            if (PAYS_CUSTOMER_FEE == 'customer')
              {
                $source_type = 'member';
                $source_key = $data['member_id'];
                // Accumulate basis for taxation
                $fee_tax_basis += $customer_adjust_amount;
              }
            elseif (PAYS_CUSTOMER_FEE == 'producer')
              {
                $source_type = 'producer';
                $source_key = $product_info['producer_id'];
              }
            else
              {
                $basket_item_info['error'] = 'No designated payee for customer fee';
                debug_print ("WARNING 502924", array ('message'=>$basket_item_info['error'], 'data'=>$data), basename(__FILE__).' LINE '.__LINE__);
                return $basket_item_info;
              }
            // Post customer fee to the ledger for each product
            $ledger_status = basket_item_to_ledger(array (
              'transaction_group_id' => $data['transaction_group_id'],
              'source_type' => $source_type,
              'source_key' => $source_key,
              'target_type' => 'internal',
              'target_key' => 'customer_fee',
              'amount' => $customer_adjust_amount,
              'text_key' => 'customer fee',
              'posted_by' => $_SESSION['member_id'],
              'basket_id' => $basket_info['basket_id'],
              'bpid' => $basket_item_info['bpid'],
              'site_id' => $basket_info['site_id'],
              'delivery_id' => $basket_info['delivery_id'],
              'pvid' => $product_info['pvid'],
              'match_keys' => array ('text_key','bpid')
              ));
          }
        // If this is a taxable item, then collect all the requisite taxes
        if ($basket_item_info['taxable'] == 1 || COOP_FEE_IS_TAXED == 'always')
          {
            // Get the tax information...
            $query = '
              SELECT
                tax_id,
                region_code,
                region_type,
                tax_percent
              FROM '.NEW_TABLE_TAX_RATES.'
              WHERE
                "'.mysqli_real_escape_string ($connection, $basket_info['delivery_postal_code']).'" LIKE postal_code
                AND order_id_start <= "'.mysqli_real_escape_string ($connection, $data['delivery_id']).'"
                AND (
                  order_id_stop >= "'.mysqli_real_escape_string ($connection, $data['delivery_id']).'"
                  OR order_id_stop = "0"
                  )';
            $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 890236 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
              {
                $text_key = $row['region_type'].' tax'; // e.g. 'county tax'
                // Just tax the item and not the fees
                if (COOP_FEE_IS_TAXED == 'never')
                  {
                    $tax_amount = $row['tax_percent'] * $product_tax_basis / 100;
                  }
                // Tax the item and the fees
                elseif (COOP_FEE_IS_TAXED == 'on taxable items' ||
                  (COOP_FEE_IS_TAXED == 'always' && $basket_item_info['taxable'] == 1))
                  {
                    $tax_amount = $row['tax_percent'] * ($product_tax_basis + $fee_tax_basis) / 100;
                  }
                // Tax only the fees (does this ever really happen?)
                elseif (COOP_FEE_IS_TAXED == 'always' && $basket_item_info['taxable'] == 0)
                  {
                    $tax_amount = $row['tax_percent'] * $fee_tax_basis / 100;
                  }
                $ledger_status = basket_item_to_ledger(array (
                  'transaction_group_id' => $data['transaction_group_id'],
                  'source_type' => 'member',
                  'source_key' => $data['member_id'],
                  'target_type' => 'tax',
                  'target_key' => $row['tax_id'],
                  'amount' => $tax_amount,
                  'text_key' => $text_key,
                  'posted_by' => $_SESSION['member_id'],
                  'basket_id' => $basket_info['basket_id'],
                  'bpid' => $basket_item_info['bpid'],
                  'site_id' => $basket_info['site_id'],
                  'delivery_id' => $basket_info['delivery_id'],
                  'pvid' => $product_info['pvid'],
                  'match_keys' => array ('text_key','bpid')
                  ));
              }
          }
      }
    return ($basket_item_info);
  }
