<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.open_basket.php');
include_once ('func.get_basket.php');
include_once ('func.get_basket_item.php');
include_once ('func.update_basket_item.php');
include_once ('func.get_product.php');
// $_POST = $_GET;

// Get values for this operation
$action = $_POST['action'];
$delivery_id = $_POST['delivery_id'];
$member_id = $_POST['member_id']; // Should this use the $_SESSION['member_id'] value for security ???
$basket_id = $_POST['basket_id'];
$product_id = $_POST['product_id'];
$product_version = $_POST['product_version'];
$set_quantity = $_POST['set_quantity'];
$message = $_POST['message'];

// If there is not already an open basket, then open one
if (CurrentBasket::basket_id() == 0)
  {
    if (isset ($_SESSION['ofs_customer']['site_id'])
        && isset ($_SESSION['ofs_customer']['delivery_type']))
      {
        $basket_info = open_basket(array(
          'member_id' => $member_id,
          'delivery_id' => $delivery_id,
          'site_id' => $_SESSION['ofs_customer']['site_id'],
          'delivery_type' => $_SESSION['ofs_customer']['delivery_type']));
      }
    else debug_print ("ERROR 021782 ", array('message'=>'Could not open basket because site_id and/or delivery_type not set.','SESSION'=>$_SESSION), basename(__FILE__).' LINE '.__LINE__);
    // Now set the basket_id if we don't have it already
    if ($basket_id == 0) $basket_id = $basket_info['basket_id'];
  }

$basket_item_info = get_basket_item ($basket_id, $product_id, $product_version);

// // If this basket has multiple versions of the same product, or if the current version is not the active version,
// // then combine them notify the customer and provide the oportunity to combine the product into the active version.
// if ($action == "convert_to_active_version")
//   {
//     $basket_item_info = update_basket_item(array(
//       'action' => 'convert_to_active_version',
//       'delivery_id' => $delivery_id,
//       'member_id' => $member_id,
//       'product_id' => $product_id,
//       'product_version' => $product_version)); // "combine_to_current_version" OR "convert_to_active_version"
//   }
// elseif ($basket_item_info['active_product_version'] != $basket_item_info['product_version'])
//   {
//     // Send an error back to the user and ask to combine versions
//     $error = '100 Inactive product version ('.$basket_item_info['product_version'].') should be ('.$basket_item_info['active_product_version'].')';
//     // Prevent any further actions on this page
//     $action = '';
//     $basket_item_info['bpid'] = '';
//   }

if ($action == "inc")
  {
    $basket_item_info = update_basket_item(array(
      'action' => 'set_quantity',
      'delivery_id' => $delivery_id,
      'member_id' => $member_id,
      'product_id' => $product_id,
      'product_version' => $product_version,
      'quantity' => '+1'));
// debug_print ("INFO: 0002 ", array('basket_item_info'=>$basket_item_info), basename(__FILE__).' LINE '.__LINE__);
  }
elseif ($action == "dec")
  {
    $basket_item_info = update_basket_item(array(
      'action' => 'set_quantity',
      'delivery_id' => $delivery_id,
      'member_id' => $member_id,
      'product_id' => $product_id,
      'product_version' => $product_version,
      'quantity' => '-1'));
  }
elseif ($action == "set" && $set_quantity != '')
  {
    $basket_item_info = update_basket_item(array(
      'action' => 'set_quantity',
      'delivery_id' => $delivery_id,
      'member_id' => $member_id,
      'product_id' => $product_id,
      'product_version' => $product_version,
      'quantity' => $set_quantity));
  }
// If the basket_item still exists and there is a message to post, then do that
if ($basket_item_info['bpid'] && $action=='message')
  {
    $basket_item_info = update_basket_item(array(
      'action' => 'set_message_to_producer',
      'delivery_id' => $delivery_id,
      'member_id' => $member_id,
      'product_id' => $product_id,
      'product_version' => $product_version,
      'messages' => array ('customer notes to producer'=>$message)
      ));
  }
if ($action == 'checkout')
  {
    // Make sure there is a good basket for this order
    $basket_item_info = update_basket_item(array (
      'action' => 'checkout',
      'delivery_id' => $delivery_id,
      'member_id' => $member_id,
      'product_id' => $product_id,
      'product_version' => $product_version,
      'messages' => array ('customer notes to producer'=>$message)
      ));
  }
if ($action == 'checkout_basket')
  {
    // Check out the whole basket
    $basket_info = update_basket(array(
      'basket_id' => $basket_id,
      'delivery_id' => $delivery_id,
      'member_id' => $member_id,
      'action' => 'checkout'
      ));
  }
// Need to get inventory level
$product_info = get_product ($product_id, $product_version, 0);
// Send information back
if ($product_info['inventory_pull'] != 0 && $product_info['inventory_id'])
  {
    $basket_item_info['inventory_pull_quantity'] = floor ($product_info['inventory_quantity'] / $product_info['inventory_pull']);
  }
$basket_item_info['error'] = ''.$error;

// debug_print ("INFO: 573982 ", array('basket_item_info'=>$basket_item_info, 'json_encoded'=>json_encode ($basket_item_info)), basename(__FILE__).' LINE '.__LINE__);

echo json_encode ($basket_item_info);
?>