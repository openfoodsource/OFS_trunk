<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');

// Set defaults
$alert_text = ''; // Set this to return errors for alert() display at product_list.php:adjust_inventory()
// Get values for this operation
$access_type = $_POST['access_type'];
$access_key = $_POST['access_key'];
if ($access_type == 'inventory')
  {
    // access_key => [inventory_id]
    $inventory_id = $access_key;
    $inventory_quantity = $_POST['inventory_quantity'];
    $inventory_description = $_POST['inventory_description'];
  }
elseif ($access_type == 'product')
  {
    // access_key => [product_id]-[product_version]
    list($product_id, $product_version) = explode ('-', $access_key);
    $inventory_pull_quantity = $_POST['inventory_pull_quantity'];
  }
$producer_id = $_SESSION['producer_id_you'];
$delivery_id = $_POST['delivery_id'];
$action = $_POST['action'];

if (! $delivery_id)
  {
    $delivery_id = ActiveCycle::delivery_id();
  }

if ($access_type == 'product')
  {
    // Get the current inventory for this product
    $query = '
      SELECT
        '.NEW_TABLE_PRODUCTS.'.pvid,
        '.NEW_TABLE_PRODUCTS.'.product_id,
        '.NEW_TABLE_PRODUCTS.'.product_version,
        '.NEW_TABLE_PRODUCTS.'.producer_id,
        '.NEW_TABLE_PRODUCTS.'.inventory_pull,
        '.NEW_TABLE_PRODUCTS.'.inventory_id,
        '.TABLE_INVENTORY.'.description AS inventory_description,
        '.TABLE_INVENTORY.'.quantity AS bucket_quantity,
        FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_pull_quantity,
        (SELECT
          SUM(quantity)
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
          WHERE delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
          AND product_id = '.NEW_TABLE_PRODUCTS.'.product_id
          AND product_version = '.NEW_TABLE_PRODUCTS.'.product_version
          ) AS ordered_quantity
      FROM
        '.NEW_TABLE_PRODUCTS.'
      LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
      WHERE
        '.NEW_TABLE_PRODUCTS.'.product_id = "'.mysqli_real_escape_string ($connection, $product_id).'"
        AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.mysqli_real_escape_string ($connection, $product_version).'"
        AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';

// echo "<pre>$query</pre>";
// debug_print ("WARN: 754032 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__);

    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 754032 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_object ($result) )
      {
        $pvid = $row->pvid;
        // $product_id = $row->product_id;
        // $product_version = $row->product_version;
        // $producer_id = $row->producer_id;
        $inventory_pull = $row->inventory_pull;
        $inventory_id = $row->inventory_id;
        $inventory_description = $row->inventory_description;
        $bucket_quantity = $row->bucket_quantity;
        $old_inventory_pull_quantity = $row->inventory_pull_quantity;
        $ordered_quantity = $row->ordered_quantity;
      }
    if ($inventory_id > 0)
      {
        if ($action == 'set')
          {
            $increment = $inventory_pull_quantity - $old_inventory_pull_quantity;
          }
        if ($action == 'inc' || $action == 'dec')
          {
            if ($action == 'inc')
              {
                $increment = 1;
              }
            elseif ($action == 'dec')
              {
                $increment = -1;
              }
          }
        $inventory_pull_quantity = $old_inventory_pull_quantity + $increment;
        $increment = $inventory_pull_quantity - $old_inventory_pull_quantity;
        $new_bucket_quantity = $bucket_quantity + ($increment * $inventory_pull);
        if ($new_bucket_quantity < 0)
          {
            $new_bucket_quantity = 0;
            $inventory_pull_quantity = 0;
          }
        $query = '
          UPDATE
            '.TABLE_INVENTORY.'
          SET
            quantity = "'.mysqli_real_escape_string ($connection, $new_bucket_quantity).'"
          WHERE
            inventory_id = "'.mysqli_real_escape_string ($connection, $inventory_id).'"';
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 849342 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    echo json_encode (array (
      'product_id' => $product_id,
      'product_version' => $product_version,
      'bucket_quantity' => $new_bucket_quantity,
      'inventory_pull_quantity' => $inventory_pull_quantity,
      'ordered_quantity' => $ordered_quantity,
      'alert_text' => $alert_text
      ));
  }
elseif ($access_type == 'inventory')
  {
    // Get the current inventory for this product
    $query = '
      SELECT
        '.TABLE_INVENTORY.'.inventory_id,
        '.TABLE_INVENTORY.'.description AS inventory_description,
        '.TABLE_INVENTORY.'.quantity AS inventory_quantity,
        (SELECT
          SUM(quantity * inventory_pull)
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
          LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
          WHERE
            delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
            AND inventory_id = '.TABLE_INVENTORY.'.inventory_id
          ) AS ordered_quantity,
        (SELECT
          inventory_id
          FROM '.TABLE_INVENTORY.'
          WHERE
            description = "'.mysqli_real_escape_string ($connection, $inventory_description).'"
            AND producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"
            AND inventory_id != "'.mysqli_real_escape_string ($connection, $inventory_id).'"
          ) AS test_inventory_id
      FROM
        '.TABLE_INVENTORY.'
      WHERE
        '.TABLE_INVENTORY.'.inventory_id = "'.mysqli_real_escape_string ($connection, $inventory_id).'"
        AND '.TABLE_INVENTORY.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
// debug_print ("INFO 001", array ('QUERY'=>$query), basename(__FILE__).' LINE '.__LINE__);
    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 754032 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_object ($result) )
      {
        $inventory_id = $row->inventory_id;
        $old_inventory_description = $row->inventory_description;
        $old_inventory_quantity = $row->inventory_quantity;
        $ordered_quantity = $row->ordered_quantity;
        $test_inventory_id = $row->test_inventory_id;
      }
    // We are only working with single-units when accessing inventory directly
    $inventory_pull = 1;
    if ($inventory_id > 0)
      {
        if ($action == 'set')
          {
            $increment = $inventory_quantity - $old_inventory_quantity;
          }
        if ($action == 'inc' || $action == 'dec')
          {
            if ($action == 'inc')
              {
                $increment = 1;
              }
            elseif ($action == 'dec')
              {
                $increment = -1;
              }
          }
        $inventory_quantity = $old_inventory_quantity + $increment;
        if ($inventory_quantity < 0)
          {
            $inventory_quantity = 0;
          }
        if (rtrim ($inventory_description) != rtrim ($old_inventory_description))
          {
            // Proceed unless there is an inventory_id already using this inventory_description
            if ($test_inventory_id != 0)
              {
                $query_set_description = '';
                $error = '100';
// debug_print ("INFO 010", array ('ERROR'=>'100'), basename(__FILE__).' LINE '.__LINE__);
              }
            elseif (strlen (rtrim($inventory_description)) == 0)
              {
                $query_set_description = '';
                $error = '200';
// debug_print ("INFO 011", array ('ERROR'=>'200'), basename(__FILE__).' LINE '.__LINE__);
              }
            else
              {
                $query_set_description = ',
                  description = "'.mysqli_real_escape_string ($connection, $inventory_description).'"';
// debug_print ("INFO 012", array ('DESCRIPTION'=>$query_set_description), basename(__FILE__).' LINE '.__LINE__);
              }
          }
        // Regardless of these errors... check if we are overriding said error
        if ($error == '100' && $action == 'combine')
          {
            // 1. Convert products from "this" inventory_id to use the "other" inventory_id
            $query = '
              UPDATE
                '.NEW_TABLE_PRODUCTS.'
              SET inventory_id = "'.mysqli_real_escape_string ($connection, $test_inventory_id).'"
              WHERE
                inventory_id = "'.mysqli_real_escape_string ($connection, $inventory_id).'"
                AND producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
// debug_print ("INFO 100", array ('QUERY'=>$query), basename(__FILE__).' LINE '.__LINE__);
                $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 203472 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            // 2. Put existing inventory balance into the new bucket
            $query = '
              UPDATE
                '.TABLE_INVENTORY.'
              SET quantity = quantity + '.mysqli_real_escape_string ($connection, $inventory_quantity).'
              WHERE
                inventory_id = "'.mysqli_real_escape_string ($connection, $test_inventory_id).'"
                AND producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
// debug_print ("INFO 101", array ('QUERY'=>$query), basename(__FILE__).' LINE '.__LINE__);
                $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 203472 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            // 3. And delete the current inventory_id
            $error = '200';
            $action = 'delete';
            $query_set_description = '';
          }
        if ($error == '200' && $action == 'delete')
          {
            // 1. Remove existing product linkages to this inventory bucket
            $query = '
              UPDATE
                '.NEW_TABLE_PRODUCTS.'
              SET
                inventory_id = "0",
                inventory_pull = "0"
              WHERE
                inventory_id = "'.mysqli_real_escape_string ($connection, $inventory_id).'"
                AND producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
// debug_print ("INFO 201", array ('QUERY'=>$query), basename(__FILE__).' LINE '.__LINE__);
                $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 203472 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            // 2. Remove the inventory bucket
            $query = '
              DELETE FROM
                '.TABLE_INVENTORY.'
              WHERE
                inventory_id = "'.mysqli_real_escape_string ($connection, $inventory_id).'"
                AND producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
// debug_print ("INFO 202", array ('QUERY'=>$query), basename(__FILE__).' LINE '.__LINE__);
                $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 203472 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            $query_set_description = '';
            $error = '';
          }
        else // No need to run the update if we just deleted the inventory bucket
          {
            $query = '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                quantity = "'.mysqli_real_escape_string ($connection, $inventory_quantity).'"'.
                $query_set_description.'
              WHERE
                inventory_id = "'.mysqli_real_escape_string ($connection, $inventory_id).'"
                AND producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
// debug_print ("INFO 203", array ('QUERY'=>$query, 'POST'=>$_POST), basename(__FILE__).' LINE '.__LINE__);
            $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 849342 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
      }
    echo json_encode (array (
      'inventory_id' => $inventory_id,
      'ordered_quantity' => $ordered_quantity,
      'inventory_quantity' => $inventory_quantity,
      'old_inventory_quantity' => $old_inventory_quantity,
      'inventory_description' => $inventory_description,
      'old_inventory_description' => $old_inventory_description,
      'error' => $error,
      'alert_text' => $alert_text
      ));
  }
?>