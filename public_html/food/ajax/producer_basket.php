<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.get_basket_item.php');
include_once ('func.update_basket_item.php');

// $action = [set_weight|set_quantity|set_weight_quantity]
$bpid = $_POST['bpid'];
$action = $_POST['action'];
if ($non_ajax_query == false)
  {
    $ship_quantity = $_POST['ship_quantity'];
    $weight = $_POST['weight'];
  }
else // For non-ajax submissions, we must disentangle bpid from the field name
  {
    $ship_quantity = $_POST['ship_quantity'.$bpid];
    $weight = $_POST['weight'.$bpid];
  }

// Information about the actual basket item
$item_info = get_basket_item ($bpid);
$basket_id = $item_info['basket_id'];
$basket_info = get_basket ($basket_id);
$out_of_stock = $item_info['quantity'] - $ship_quantity;

// Update the basket
$result_item_info = update_basket_item (array (
  'action' => 'set_all_producer',
  'basket_id' => $item_info['basket_id'],
  'member_id' => $basket_info['member_id'],
  'delivery_id' => $basket_info['delivery_id'],
  'product_id' => $item_info['product_id'],
  'product_version' => $item_info['product_version'],
  'out_of_stock' => $out_of_stock,
  'weight' => $weight
  ));

// Synch the ledger
$result_item_info = update_basket_item (array (
  'action' => 'producer_synch_ledger',
  'basket_id' => $item_info['basket_id'],
  'member_id' => $basket_info['member_id'],
  'delivery_id' => $basket_info['delivery_id'],
  'product_id' => $item_info['product_id'],
  'product_version' => $item_info['product_version']
  ));

// Should be able to use $result_item_info from above but it is not working
$result_item_info = get_basket_item ($bpid);

// Now set return values
if (is_array ($result_item_info))
  {
    // Set the various fees:
    $customer_product_adjust_fee = 0;
    $producer_product_adjust_fee = 0;
    if (PAYS_PRODUCT_FEE == 'customer') $customer_product_adjust_fee = $result_item_info['product_fee_percent'] / 100;
    elseif (PAYS_PRODUCT_FEE == 'producer') $producer_product_adjust_fee = $result_item_info['product_fee_percent'] / 100;
    $customer_subcat_adjust_fee = 0;
    $producer_subcat_adjust_fee = 0;
    if (PAYS_SUBCATEGORY_FEE == 'customer') $customer_subcat_adjust_fee = $result_item_info['subcategory_fee_percent'] / 100;
    elseif (PAYS_SUBCATEGORY_FEE == 'producer') $producer_subcat_adjust_fee = $result_item_info['subcategory_fee_percent'] / 100;
    $customer_producer_adjust_fee = 0;
    $producer_producer_adjust_fee = 0;
    if (PAYS_PRODUCER_FEE == 'customer') $customer_producer_adjust_fee = $result_item_info['producer_fee_percent'] / 100;
    elseif (PAYS_PRODUCER_FEE == 'producer') $producer_producer_adjust_fee = $result_item_info['producer_fee_percent'] / 100;

    // All this parsing and rounding is to match the line-item breakout in the ledger to prevent roundoff mismatch
    $cost_multiplier = ($result_item_info['random_weight'] == 1 ? $result_item_info['total_weight'] : ($result_item_info['quantity'] - $result_item_info['out_of_stock'])) * $result_item_info['unit_price'];
    $producer_adjusted_cost = round($cost_multiplier, 2) - round($producer_product_adjust_fee * $cost_multiplier, 2) - round($producer_subcat_adjust_fee * $cost_multiplier, 2) - round($producer_producer_adjust_fee * $cost_multiplier, 2);
    // $customer_adjusted_cost = round($cost_multiplier, 2) + round($customer_product_adjust_fee * $cost_multiplier, 2) + round($customer_subcat_adjust_fee * $cost_multiplier, 2) + round($customer_producer_adjust_fee * $cost_multiplier, 2);
    $extra_charge_total = ($result_item_info['quantity'] - $result_item_info['out_of_stock']) * $result_item_info['extra_charge'];
    // Format for output
    $producer_adjusted_cost_display = '$&nbsp;'.number_format($producer_adjusted_cost, 2);
    $extra_charge_display = ($extra_charge_total > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format(abs($extra_charge_total), 2);
    $shipped = $result_item_info['quantity'] - $result_item_info['out_of_stock'];
    $return_data = $producer_adjusted_cost_display.':'.$extra_charge_display.':'.$shipped.':'.$result_item_info['total_weight'];
    if ($non_ajax_query == false) echo $return_data;
// debug_print ("INFO: Returned from updating weights (BPID=$bpid): ", array('return_data'=>$return_data, 'result_item_info'=>$result_item_info), basename(__FILE__).' LINE '.__LINE__);
  }
else
  {
    // Must have gotten an error back from update_basket_item ();
    // So return an alert message
    $alert_message = 'ERROR:"'.$result_item_info;
    if ($non_ajax_query == false) echo $alert_message;
    debug_print ("INFO: Returned from updating weights (BPID=$bpid): ", array('alert_message'=>$alert_message, 'result_item_info'=>$result_item_info), basename(__FILE__).' LINE '.__LINE__);
  }
?>