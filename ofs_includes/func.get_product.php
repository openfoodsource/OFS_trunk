<?php
// If the product exists, the subroutine returns the product information.
// Call with: get_product ($product_id, $product_version, $pvid)
// Must supply (product_id, product_version) AND/OR (pvid)
function get_product ($product_id, $product_version, $pvid)
  {
    // Expose additional parameters as they become needed.
    global $connection;
    $products_fields = array (
      'pvid',
      'product_id',
      'product_version',
      NEW_TABLE_PRODUCTS.'.producer_id',
      'product_name',
      'product_description',
      'account_number',
      'inventory_pull',
      'inventory_id',
      'quantity AS inventory_quantity', // <------------- information from inventory table
      'subcategory_id',
      'future_delivery',
      'future_delivery_type',
      'production_type_id',
      'unit_price',
      'pricing_unit',
      'ordering_unit',
      'random_weight',
      'meat_weight_type',
      'minimum_weight',
      'maximum_weight',
      'extra_charge',
      'product_fee_percent',
      'image_id',
      'listing_auth_type',
      'confirmed',
      'retail_staple',
      'staple_type',
      'created',
      'modified',
      'tangible',
      'sticky',
      'hide_from_invoice',
      'storage_id',
      TABLE_CATEGORY.'.category_id',
      TABLE_CATEGORY.'.category_name',
      TABLE_SUBCATEGORY.'.subcategory_name',
      TABLE_PRODUCT_TYPES.'.prodtype'
      );
    $query = '
      SELECT
        '.implode (",\n        ", $products_fields).'
      FROM '.NEW_TABLE_PRODUCTS.'
      LEFT JOIN
        '.TABLE_INVENTORY.' USING(inventory_id)
      LEFT JOIN
        '.TABLE_PRODUCT_TYPES.' USING(production_type_id)
      LEFT JOIN
        '.TABLE_SUBCATEGORY.' USING(subcategory_id)
      LEFT JOIN
        '.TABLE_CATEGORY.' USING(category_id)
      LEFT JOIN
        '.TABLE_PRODUCER.' ON '.NEW_TABLE_PRODUCTS.'.producer_id = '.TABLE_PRODUCER.'.producer_id
      WHERE
          ('.NEW_TABLE_PRODUCTS.'.product_id = "'.mysql_real_escape_string ($product_id).'"
          AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.mysql_real_escape_string ($product_version).'")
        OR 
          ('.NEW_TABLE_PRODUCTS.'.pvid = "'.mysql_real_escape_string ($pvid).'"
          AND '.NEW_TABLE_PRODUCTS.'.pvid != "0")';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 754004 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysql_fetch_array($result))
      {
        return ($row);
      }
  }

// This function is a little convoluted, but there is no easy way to get the next product_id when 
// product_id is not an auto-increment field. So here is the process...
//    1. ask for the maximum product_id and add +1
//    2. insert our new row and hope no other process has added a product ahead of us
//    3. check if our product_id is unique (i.e. we got the only one) -- a good chance
//    4. if *not* unique, then increment it until it is
// The function will reserve the row by setting pvid, product_id, and product_version=1 and
// will return an associative array with pvid and product_id
// For this to work, the products table index (product_id-product_version) must not be unique
function get_next_product_id ($producer_id)
  {
    global $connection;
    // Get the next product_id as it stands currently
    $query = '
      SELECT
        MAX(product_id) + 1 AS product_id
      FROM
        '.NEW_TABLE_PRODUCTS;
    $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 856249 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysql_fetch_array($result))
      {
        $product_id = $row['product_id'];
      }
    // Now insert that product_id to reserve our place
    // Set product_version = 1 since it is a new product
    $query = '
      INSERT INTO
        '.NEW_TABLE_PRODUCTS.'
      SET
        product_id = "'.mysql_real_escape_string($product_id).'",
        product_version = "1",
        producer_id = "'.mysql_real_escape_string($producer_id).'"';
    $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 460569 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $pvid = mysql_insert_id();
    // Now increment the product_id until it is unique in the database
    $need_to_test = true;
    while ($need_to_test == true)
      {
        $query = '
          SELECT
            COUNT(product_id) AS count
          FROM
            '.NEW_TABLE_PRODUCTS.'
          WHERE
            product_id = "'.mysql_real_escape_string($product_id).'"';
        $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 311047 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysql_fetch_array($result))
          {
            // Check if we are done
            if ($row['count'] == 1)
              {
                $need_to_test = false;
              }
            // Otherwise increment product_id for our row in the database
            else
              {
                $query = '
                  UPDATE
                    '.NEW_TABLE_PRODUCTS.'
                  SET
                    product_id = product_id + 1
                  WHERE
                    pvid = "'.mysql_real_escape_string($pvid).'"';
                $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 939523 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
                // And sync our external product_id variable
                $product_id ++;
              }
          }
        // if no row or overflow, then prevent an infinite loop by bailing
        else
          {
            die (debug_print ("ERROR: 784914 ", 'No product rows found', basename(__FILE__).' LINE '.__LINE__));
          }
        if ($overflow_count ++ > 8) die (debug_print ("ERROR: 489063 ", 'Overflow while finding next product', basename(__FILE__).' LINE '.__LINE__));
      }
    return array ('pvid' => $pvid, 'product_id' => $product_id);
  }






?>