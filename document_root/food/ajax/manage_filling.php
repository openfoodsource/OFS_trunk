<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');
include_once ('func.get_basket_item.php');
include_once ('func.update_basket_item.php');

// $action = [set_weight|set_outs]
$bpid = $_POST['bpid'];
$action = $_POST['action'];
$ship_quantity = $_POST['ship_quantity'];
$weight = $_POST['weight'];

// Information about the actual basket item
$item_info = get_basket_item ($bpid);
$basket_id = $item_info['basket_id'];
$basket_info = get_basket ($basket_id);
$out_of_stock = $item_info['quantity'] - $ship_quantity;

// Update the basket
$result_item_info = update_basket_item (array (
  'action' => $action,
  'basket_id' => $item_info['basket_id'],
  'member_id' => $basket_info['member_id'],
  'delivery_id' => $basket_info['delivery_id'],
  'product_id' => $item_info['product_id'],
  'product_version' => $item_info['product_version'],
  'out_of_stock' => $out_of_stock,
  'weight' => $weight
  ));

// Synch the ledger, but only if the basket is checked out
if ($result_item_info['checked_out'] == 1)
  {
    $result_item_info = update_basket_item (array (
      'action' => 'producer_synch_ledger',
      'basket_id' => $item_info['basket_id'],
      'member_id' => $basket_info['member_id'],
      'delivery_id' => $basket_info['delivery_id'],
      'product_id' => $item_info['product_id'],
      'product_version' => $item_info['product_version']
      ));
  }

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
$extra_charge_total = ($result_item_info['quantity'] - $result_item_info['out_of_stock']) * $result_item_info['extra_charge'];
$shipped = $result_item_info['quantity'] - $result_item_info['out_of_stock'];
// Add extra values to the $result_item_info array
$result_item_info['producer_adjusted_cost'] = number_format ($producer_adjusted_cost, 2);
$result_item_info['extra_charge_total'] = number_format ($extra_charge_total, 2);
$result_item_info['shipped'] = $shipped;

echo json_encode ($result_item_info);
?>