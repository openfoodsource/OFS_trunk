<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.open_basket.php');

// Get values for this operation
// ... from the environment
$member_id = $_SESSION['member_id'];
$delivery_id = ActiveCycle::delivery_id();
$basket_id = CurrentBasket::basket_id();
// ... from add/subtract from basket
$product_id = $_POST['product_id'];
$product_version = $_POST['product_version'];
$action = $_POST['action'];
// ... from update message
$message = $_POST['message'];

// If a basket is not already open, then open one...
if (! $basket_id)
  {
    $basket_info = open_basket (array (
      'member_id' => $member_id,
      'delivery_id' => $delivery_id,
      ));
    if ($basket_info['site_selection'] == 'unset')
      {
        echo 'site_id not set';
        exit (1);
      }
    elseif ($basket_info['site_selection'] == 'revert')
      {
        echo 'site_id reverted: '.$basket_info['site_long'];
        exit (1);
      }
    $basket_id = $basket_info['basket_id'];
  }
// Make sure the quantity we think is in the basket is the quantity that really is in the basket
$query = '
  SELECT
    (
      SELECT
        CONCAT(bpid,":",quantity)
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      WHERE
        basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"
        AND product_id = "'.mysql_real_escape_string ($product_id).'"
        AND product_version = "'.mysql_real_escape_string ($product_version).'"
    ) AS bpid_quantity,
    '.NEW_TABLE_PRODUCTS.'.inventory_id,
    '.NEW_TABLE_PRODUCTS.'.inventory_pull,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_quantity
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN '.TABLE_INVENTORY.' ON '.TABLE_INVENTORY.'.inventory_id = '.NEW_TABLE_PRODUCTS.'.inventory_id
  WHERE
    '.NEW_TABLE_PRODUCTS.'.product_id = "'.mysql_real_escape_string ($product_id).'"
    AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.mysql_real_escape_string ($product_version).'"';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 738102 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
if ( $row = mysql_fetch_object($result) )
  {
    list ($bpid,$basket_quantity) = explode(':', $row->bpid_quantity);
//    $basket_quantity = $row->quantity;
    $inventory_quantity = $row->inventory_quantity;
    $inventory_id = $row->inventory_id;
    $inventory_pull = $row->inventory_pull;
  }
// Abort the operation if we do not have important information

if (! $delivery_id ||
    ! $member_id ||
    ! $basket_id ||
    ! $product_id ||
    ! $product_version ||
    ! $action)
  {
    die(debug_print ("ERROR: 545721 ", 'Call without necessary information.', basename(__FILE__).' LINE '.__LINE__));
  }

if ($action == "add")
  {
    // Create new basket item
    if ($basket_quantity == 0 &&
        (($inventory_id && $inventory_quantity > 0) ||
        ! $inventory_id))
      {
        $add_basket_item = true;
        // Alert that a new item has been added to the basket
        // $alert = 'Product has been added to the basket';
        $basket_quantity = 1;
        $inventory_quantity = $inventory_quantity - 1; // inventory_quantity is adjusted for THIS product
        $update_basket_item = false;
      }
    // No available inventory... do nothing
    elseif ($inventory_id && $inventory_quantity <= 0)
      {
        $add_basket_item = false;
        // Alert that there are not enough in inventory
        // $alert = 'Insufficient inventory is available';
        // $basket_quantity = $basket_quantity; // no change
        $update_basket_item = false;
      }
    // Add to an existing basket item
    else
      {
        $add_basket_item = false;
        // $alert = 'Product quantity has been updated';
        $basket_quantity = $basket_quantity + 1;
        $inventory_quantity = $inventory_quantity - 1;
        $update_basket_item = true;
      }
  }
elseif ($action == "sub")
  {
    // Only one basket item, so remove it
    if ($basket_quantity <= 1)
      {
        // Alert that the basket has been emptied
        // $alert = 'Product has been removed from the basket';
        $basket_quantity = 0;
        $inventory_quantity = $inventory_quantity + 1;
        $remove_basket_item = true;
        $update_basket_item = false; // no need for update since the item will be removed
      }
    elseif ($basket_quantity > 1)
      {
        // Alert that the basket is already empty
        // $alert = 'The item was not in your basket';
        $basket_quantity = $basket_quantity - 1;
        $inventory_quantity = $inventory_quantity + 1;
        $remove_basket_item = false;
        $update_basket_item = true;
      }
  }
// First add the basket item, if needed
if ($add_basket_item == true)
  {
    $query = '
      INSERT INTO
        '.NEW_TABLE_BASKET_ITEMS.' (
        basket_id,
        product_id,
        product_version,
        quantity,
        product_fee_percent,
        subcategory_fee_percent,
        producer_fee_percent,
        out_of_stock,
        date_added )
      SELECT
        "'.mysql_real_escape_string ($basket_id).'",
        '.NEW_TABLE_PRODUCTS.'.product_id,
        '.NEW_TABLE_PRODUCTS.'.product_version,
        "1",
        '.NEW_TABLE_PRODUCTS.'.product_fee_percent,
        '.TABLE_SUBCATEGORY.'.subcategory_fee_percent,
        '.TABLE_PRODUCER.'.producer_fee_percent,
        "0",
        "'.date('Y-m-d H:i:s',time()).'"
      FROM
      '.NEW_TABLE_PRODUCTS.'
      LEFT JOIN
        '.TABLE_SUBCATEGORY.' USING(subcategory_id)
      LEFT JOIN
        '.TABLE_PRODUCER.' USING(producer_id)
      WHERE
        product_id = "'.mysql_real_escape_string ($product_id).'"
        AND product_version = "'.mysql_real_escape_string ($product_version).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 155816 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $bpid= mysql_insert_id();
  }
// Then update the quantity, if needed
if ($update_basket_item == true)
  {
    $query = '
      UPDATE
        '.NEW_TABLE_BASKET_ITEMS.'
      SET
        quantity = "'.mysql_real_escape_string ($basket_quantity).'"
      WHERE
        basket_id = "'.mysql_real_escape_string ($basket_id).'"
        AND product_id = "'.mysql_real_escape_string ($product_id).'"
        AND product_version = "'.mysql_real_escape_string ($product_version).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 731034 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
  }
if ($inventory_id && ($action == 'add' || $action == 'sub'))
  {
    if ($action == 'add')
      {
        $inventory_function = '-';
      }
    elseif ($action == 'sub')
      {
        $inventory_function = '+';
      }
    $query = '
      UPDATE
        '.TABLE_INVENTORY.'
      SET
        quantity = quantity '.$inventory_function.' '.mysql_real_escape_string ($inventory_pull).'
      WHERE
        inventory_id = '.mysql_real_escape_string ($inventory_id);
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 066934 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
  }
if ($remove_basket_item == true)
  {
    
    $query = '
      DELETE FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      WHERE
        basket_id = "'.mysql_real_escape_string ($basket_id).'"
        AND product_id = "'.mysql_real_escape_string ($product_id).'"
        AND product_version = "'.mysql_real_escape_string ($product_version).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 267490 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
  }
// Handle messages
// First remove all messages, no matter what. Without this process, additional messages
// keep getting added.
if (isset ($bpid))
  { // Delete if necessary
    $query = '
      DELETE FROM
        '.NEW_TABLE_MESSAGES.'
      WHERE
        referenced_key1 = "'.mysql_real_escape_string($bpid).'"
        AND message_type_id =
          (
            SELECT message_type_id
            FROM '.NEW_TABLE_MESSAGE_TYPES.'
            WHERE description = "customer notes to producer"
          )';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 285097 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    // Now post the message back, as needed
    if ($message != '' && $remove_basket_item != true)
      { // Update message
        $query = '
          INSERT INTO '.NEW_TABLE_MESSAGES.'
          SET
            message = "'.mysql_real_escape_string($message).'",
            message_type_id = 
              (
                SELECT message_type_id
                FROM '.NEW_TABLE_MESSAGE_TYPES.'
                WHERE description = "customer notes to producer"
              ),
            referenced_key1 = "'.mysql_real_escape_string($bpid).'"';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 925223 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
      }
  }

// The following is necessary because this is also called when javascript/ajax is turned off and
// we don't want to send extraneous data back to the output page.
if ($non_ajax_query == false)
  {
    echo number_format($basket_quantity, 0).':'.number_format($inventory_quantity, 0).':'.$alert;
  }
?>