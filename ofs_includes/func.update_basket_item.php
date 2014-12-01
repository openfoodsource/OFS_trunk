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

session_start();
// debug_print ('INFO: Session:', $_SESSION);
//valid_auth('cashier,site_admin');


// This function is used to add / change / remove a basket_item
// Input data is an associative array with values:
// * action                 ['set_quantity'|'set_message_to_producer'|'set_outs'|'set_weight'|'checkout'|'set_all_producer'|'set_everything'|'clear_item'|'synch_ledger'|'producer_synch_ledger']
// * delivery_id            delivery_id
// * member_id              member_id
// * product_id             product_id
// * product_version        product_version
// * messages               [optional messages connected to basket_item]
// SPECIAL VALUES: ------------------------------------------------------------
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
          $initiate_change_quantity = true;
          $initiate_set_message_to_producer = true;
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
          $test_basket_item_exists = true;
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
          return ('Unexpected request '.$action);
          break;
      }
    // Check if the product exists, regardless of $admin_override_not_set
    if ($test_for_valid_product)
      {
        $product_info = get_product ($data['product_id'], $data['product_version'], $data['pvid']);
        if (! is_array ($product_info))
          {
            return ('Product not found in database');
          }
      }
    // Get  information about the basket for this member
    // This needs to be done before the availability check
    $basket_info = get_basket ($data['member_id'], $data['delivery_id']);
    // See if we already have this basket_item
    if (is_array ($basket_info))
      {
        $basket_item_info = get_basket_item ($basket_info['basket_id'], $data['product_id']);
      }
    else // For now, we are not going to deal with any case where the basket is not yet opened
      {
        return ('Basket does not exist');
      }
    // Check for basket item
    if ($test_basket_item_exists && ! is_array ($basket_item_info))
      {
        return ('Basket item does not exist');
      }
    // Check if the basket is locked
    if ($basket_info['locked'] == 1)
      {
        return ('Basket is locked');
      }
    // Check if this producer is permitted and enabled to sell
    if ($test_for_producer_privilege && $admin_override_not_set)
      {
        $producer_info = get_producer ($product_info['producer_id']);
        if ($producer_info['unlisted_producer'] > 0 || $producer_info['pending'] == 1)
          {
            return ('Producer is restricted from selling');
          }
      }
    // Check if the customer is allowed to purchase this product
    if ($test_for_customer_privilege && $admin_override_not_set)
      {
        $member_info = get_member ($data['member_id']);
        $member_auth_type_array = explode (',', $member_info['auth_type']);
        // $product_info['listing_auth_type'] contains the *necessary* auth_type to buy this product
        // and $member_auth_type_array contains all the members' allowable auth_types
        // listing_auth_types archived and unlisted are not allowed for members, so can never be ordered
        if (! is_array ($member_auth_type_array) ||
          ! in_array ($product_info['listing_auth_type'], $member_auth_type_array))
          {
            return ('Incorrect privilege to purchase requested product');
          }
      }
    // Check that the member is not pending or discontinued
    if ($test_for_membership_privilege && $admin_override_not_set)
      {
        if ($member_info['pending'] == 1 || $member_info['membership_discontinued'] == 1)
          {
            return ('Incorrect privilege to order');
          }
      }
    // Check if shopping is closed for this order
    if ($test_customer_ordering_window && $admin_override_not_set)
      {
        if (ActiveCycle::ordering_window() == 'closed')
          {
            return ('Customer ordering period is not in effect');
          }
      }
    // Check if the product can be delivered to this site_id
    if ($test_product_availability && $admin_override_not_set)
      {
        if ($producer_info['available_site_ids'] != '' && // Empty means product is available everywhere
          ! in_array ($basket_info['site_id'], explode(',',$producer_info['available_site_ids'])))
          {
            return ('Producer does not sell at this location');
          }
      }
    // Check if producer activity is taking place within the producer update window
    if ($test_producer_update_window && $producer_id_you && $admin_override_not_set)
      {
        if (ActiveCycle::producer_update_window() == 'closed')
          {
            return ('Producer update window is closed');
          }
      }

    // Create an empty basket item if one does not already exist
    if ($initiate_basket_item && ! is_array ($basket_item_info))
      {
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
              taxable,
              out_of_stock,
              future_delivery,
              future_delivery_type,
              date_added
            )
          SELECT
            '.mysql_real_escape_string ($basket_info['basket_id']).' AS basket_id,
            product_id,
            product_version,
            "0" AS quantity,
            "0" AS total_weight,
            product_fee_percent,
            subcategory_fee_percent,
            producer_fee_percent,
            taxable,
            "0" AS out_of_stock,
            future_delivery,
            future_delivery_type,
            NOW() AS date_added
          FROM '.NEW_TABLE_PRODUCTS.'
          LEFT JOIN '.TABLE_SUBCATEGORY.' USING(subcategory_id)
          LEFT JOIN '.TABLE_CATEGORY.' USING(category_id)
          LEFT JOIN '.TABLE_PRODUCER.' USING(producer_id)
          WHERE
            product_id = "'.mysql_real_escape_string ($product_info['product_id']).'"
            AND product_version = "'.mysql_real_escape_string ($product_info['product_version']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 748032 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // Now get the basket information we just posted
        $basket_item_info = get_basket_item ($basket_info['basket_id'], $data['product_id']);
      }

    // Update the quantity for this basket_item and adjust inventory accordingly
    if ($initiate_change_quantity)
      {
        $old_requested_quantity = $basket_item_info['quantity'];
        $old_out_of_stock = $basket_item_info['out_of_stock'];
        // The following code is built around adding/subtracting [quantity], so we will first adjust
        // the input to fit that method. If $data['quantity'] is like "+1" or "-2" then increment/decrement
        if (preg_match('/^([\+\-])(\d+)$/', $data['quantity'], $matches))
          {
            if ($matches[1] == '+') $data['quantity'] = $matches[2]; // positive
            elseif ($matches[1] == '-') $data['quantity'] = 0 - $matches[2]; // negative
            else return ('Unexpected result 789830');
          }
        // Requested to set a specific quantity, so figure out what the *change* to that number would be
        elseif (preg_match('/^(\d+)$/', $data['quantity'], $matches))
          {
            $data['quantity'] = $matches[1] - $old_requested_quantity; // Amount to increase the basket by
          }
        // Invalid quantity
        else
          {
            return ('Unexpected result 785932');
          }
        // If this is an inventory-controlled item
        if ($product_info['inventory_id'])
          {
            $old_actual_quantity = $old_requested_quantity - $old_out_of_stock;
            // Note that available_inventory is the number of inventory_pull units available for this
            // particular product and might actually leave a few inventory items remaining.
            $available_inventory = floor ($product_info['inventory_quantity'] / $product_info['inventory_pull']);
            $inventory_reduction = 0;
            // Just for sanity, make sure the old out_of_stock is not more than the old_requested_quantity
            if ($old_out_of_stock > $old_requested_quantity)
              {
                $old_out_of_stock = $old_requested_quantity;
              }
            // Add the request, no matter what (unless the quantity goes negative)
            $new_requested_quantity = $old_requested_quantity + $data['quantity'];
            // If we brought the requested quantity in the basket down to zero
            if ($new_requested_quantity <= 0)
              {
                // then set the new_requested_quantity to [all of it]
                $data['quantity'] = 0 - $old_requested_quantity;
                $new_requested_quantity = 0;
                // and set out_of_stock to zero (empty the basket completely)
                $new_out_of_stock = 0;
              }
            // Otherwise, calculate the new basket_item amounts (we already have new_requested_quantity)
            else
              {
                // We will begin by considering all of the new quantity as "out"
                $new_out_of_stock = $old_out_of_stock + $data['quantity'];
              }
            // At this point, the new_requested_quantity is correct, but we might
            // have set the new_out_of_stock incorrectly, so we will adjust that
            // according to the inventory available
            // Check if there is enough inventory to cover our entire out_of_stock request
            if ($available_inventory > $new_out_of_stock)
              {
                $inventory_reduction = $new_out_of_stock;
                $new_out_of_stock = 0;
              }
            // Otherwise we need to get as much out_of_stock as the inventory will cover
            else
              {
                $inventory_reduction = $available_inventory; // all of it
                $new_out_of_stock = $new_out_of_stock - $inventory_reduction;
              }
          }
        // Otherwise it is not an inventory-controlled item
        else
          {
            // If this somehow changed, then clear the out_of_stock setting
            $new_out_of_stock = 0;
            $inventory_reduction = 0;
            // Add the new requested quantity
            $new_requested_quantity = $old_requested_quantity + $data['quantity'];
            // And make sure it isn't less than zero
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
            quantity = "'.mysql_real_escape_string ($new_requested_quantity).'",
            out_of_stock = "'.mysql_real_escape_string ($new_out_of_stock).'"
          WHERE bpid = "'.mysql_real_escape_string ($basket_item_info['bpid']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 842075 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // And update the inventory amount
        if ($inventory_reduction != 0)
          {
            // This does work with negative numbers for $new_requested_quantity
            $query = '
              UPDATE '.TABLE_INVENTORY.'
              SET quantity = quantity + "'.mysql_real_escape_string ($new_requested_quantity * $product_info['inventory_pull']).'"
              WHERE inventory_id = "'.mysql_real_escape_string ($product_info['inventory_id']).'"';
            $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 902784 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
          }
      }
    // Not changing quantity, so just set variables we will need
    else
      {
        $new_requested_quantity = $basket_item_info['quantity'];
      }

    // Change the "out" setting on this item
    if ($initiate_change_outs)
      {
        // If $data['out_of_stock'] is like "+1" or "-2" then increment/decrement
        if (preg_match('/^([\+\-])(\d+)$/', $data['out_of_stock'], $matches))
          {
            // Increase out_of_stock :: decreases actual order quantity
            if ($matches[1] == '+') $new_out_of_stock = $basket_item_info['out_of_stock'] + $matches[2];
            // Decrease out_of_stock :: increases actual order quantity
            elseif ($matches[1] == '-') $new_out_of_stock = $basket_item_info['out_of_stock'] - $matches[2];
            else return ('Unexpected result 578932');
          }
        // Otherwise, just set the out_of_stock directly
        elseif (preg_match('/^(\d+)$/', $data['out_of_stock'], $matches))
          {
            $new_out_of_stock = $matches[1];
          }
        else
          {
            return ('Unexpected result 874042');
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
          SET out_of_stock = "'.mysql_real_escape_string ($new_out_of_stock).'"
          WHERE bpid = "'.mysql_real_escape_string ($basket_item_info['bpid']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 784303 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
      }

    // Set a basket_item message for this item
    if ($initiate_set_message_to_producer && $basket_item_info['bpid'] && is_array ($data['messages']))
      {
        foreach ($data['messages'] as $message_type => $message)
          {
            // If there is a message, then add the message or replace an existing one
            if (strlen ($message) > 0)
              {
                // Use [0]:orphaned message in case the description is not found
                $query_post_message = '
                  REPLACE INTO '.NEW_TABLE_MESSAGES.'
                  SET
                    message = "'.mysql_real_escape_string($message).'",
                    message_type_id = 
                      COALESCE((
                        SELECT message_type_id
                        FROM '.NEW_TABLE_MESSAGE_TYPES.'
                        WHERE key1_target = "basket_items.bpid"
                        AND description = "'.mysql_real_escape_string($message_type).'"
                        LIMIT 1
                        )
                      ,0),
                    referenced_key1 = "'.mysql_real_escape_string($basket_item_info['bpid']).'"';
              }
            // Otherwise, delete any existing message of this variety
            else
              {
                $query_post_message = '
                  DELETE FROM '.NEW_TABLE_MESSAGES.'
                  WHERE
                    message_type_id = 
                      COALESCE((
                        SELECT message_type_id
                        FROM '.NEW_TABLE_MESSAGE_TYPES.'
                        WHERE key1_target = "basket_items.bpid"
                        AND description = "'.mysql_real_escape_string($message_type).'"
                        LIMIT 1
                        )
                      ,0)
                    AND referenced_key1 = "'.mysql_real_escape_string($basket_item_info['bpid']).'"';
              }
            $result_post_message = mysql_query($query_post_message, $connection) or die(debug_print ("ERROR: 789021 ", array ($query_post_message,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
          }
      }

    // Clear any weight that might have been set for this item
    if ($initiate_clear_weight && $basket_item_info['random_weight'])
      {
        $total_weight = $data['weight'];
        $query = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET total_weight = "'.mysql_real_escape_string ($total_weight ).'"
          WHERE bpid = "'.mysql_real_escape_string ($basket_item_info['bpid']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 890254 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
      }
    // Update the weight for this item
    if ($initiate_change_weight && $basket_item_info['random_weight'])
      {
        $total_weight = $data['weight'];
        if ($new_requested_quantity - $new_out_of_stock)
          {
            $average_weight =  $data['weight'] / ($new_requested_quantity - $new_out_of_stock);
//             // Check for weight in specified range (admins may override this check)
//             if (($average_weight < $basket_item_info['minimum_weight'] ||
//               $average_weight > $basket_item_info['maximum_weight']) &&
//               $admin_override_not_set)
//               {
//                 return ('Random-weight item outside declared weight range');
//               }
          }
        else // no items... so no weight
          {
            $average_weight = 0;
            $total_weight = 0;
          }
        $query = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET total_weight = "'.mysql_real_escape_string ($total_weight).'"
          WHERE bpid = "'.mysql_real_escape_string ($basket_item_info['bpid']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 520561 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
          'basket_id' => $basket_info['basket_id']
          ));
        // Sync the checked_out field
        $basket_info['checked_out'] = $test_info['checked_out'];
        $query = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET checked_out = "1"
          WHERE bpid = "'.mysql_real_escape_string ($basket_item_info['bpid']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 893020 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
                return ('Unexpected result 579210');
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
                return ('No designated payee for product fee');
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
                return ('No designated payee for subcategory fee');
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
                return ('No designated payee for producer fee');
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
                return ('No designated payee for customer fee');
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
                postal_code = "'.mysql_real_escape_string ($basket_info['delivery_postal_code']).'"
                AND order_id_start <= "'.mysql_real_escape_string ($data['delivery_id']).'"
                AND (
                  order_id_stop >= "'.mysql_real_escape_string ($data['delivery_id']).'"
                  OR order_id_stop = "0"
                  )';
            $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 890236 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            while ($row = mysql_fetch_array($result))
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
?>