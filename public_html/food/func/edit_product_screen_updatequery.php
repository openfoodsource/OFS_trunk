<?php
// Initialize variables
$okay_to_post = false;
$set_created = '/* created, */'; // do not set
$error_array = array ();
$warn = array ();
$products_fields = array (
  'pvid'                 => '',
  'product_id'           => 'trigger_new_version',
  'product_version'      => '',
  'producer_id'          => '',
  'product_name'         => 'trigger_new_version',
  'product_description'  => 'trigger_new_version',
  'subcategory_id'       => '',
  'account_number'       => 'trigger_new_version',
  'inventory_id'         => '',
  'inventory_pull'       => '',
  'unit_price'           => 'trigger_new_version',
  'pricing_unit'         => 'trigger_new_version',
  'ordering_unit'        => 'trigger_new_version',
  'production_type_id'   => 'trigger_new_version',
  'extra_charge'         => 'trigger_new_version',
  'product_fee_percent'  => 'trigger_new_version',
  'random_weight'        => 'trigger_new_version',
  'minimum_weight'       => '',
  'maximum_weight'       => '',
  'meat_weight_type'     => 'trigger_new_version',
  'listing_auth_type'    => '',
  'taxable'              => 'trigger_new_version',
  'sticky'               => 'trigger_new_version',
  'tangible'             => 'trigger_new_version',
  'storage_id'           => 'trigger_new_version',
  'retail_staple'        => 'trigger_new_version',
  'future_delivery'      => 'trigger_new_version',
  'future_delivery_type' => 'trigger_new_version',
  'image_id'             => 'trigger_new_version',
  'confirmed'            => '',
  'staple_type'          => 'trigger_new_version',
  'created'              => '',
  'modified'             => '',
  'hide_from_invoice'    => 'trigger_new_version'
  );

// Validate the form data in all cases except "Cancel"
if ($check_validation == true && $_POST['submit_action'] != 'Cancel')
  {
    // Sanitize the data
    $_POST['pvid']                   = preg_replace("/[^0-9]/",'',$_POST['pvid']);
    $_POST['product_id']             = preg_replace("/[^0-9]/",'',$_POST['product_id']);
    $_POST['producer_id']            = preg_replace("/[^0-9]/",'',$_POST['producer_id']);
    $_POST['product_version']        = preg_replace("/[^0-9]/",'',$_POST['product_version']);
    $_POST['product_name']           = strip_tags ($_POST['product_name']);
    $_POST['account_number']         = preg_replace("/[^0-9]/",'',$_POST['account_number']);
    $_POST['pricing_unit']           = strip_tags ($_POST['pricing_unit']);
    $_POST['ordering_unit']          = strip_tags ($_POST['ordering_unit']);
    $_POST['product_description']    = nl2br2 (strip_tags ($_POST['product_description'], '<b><i><u><strong><em>'));
    $_POST['unit_price']             = preg_replace("/[^0-9\.\-]/",'',$_POST['unit_price']);
    $_POST['extra_charge']           = preg_replace("/[^0-9\.\-]/",'',$_POST['extra_charge']);
    $_POST['product_fee_percent']    = preg_replace("/[^0-9\.\-]/",'',$_POST['product_fee_percent']);
    $_POST['minimum_weight']         = preg_replace("/[^0-9\.\/]/",'',$_POST['minimum_weight'] + 0);
    $_POST['maximum_weight']         = preg_replace("/[^0-9\.\/]/",'',$_POST['maximum_weight'] + 0);
    $_POST['inventory_id']           = preg_replace("/[^0-9\-]/",'',$_POST['inventory_id']);
    $_POST['inventory_pull']         = preg_replace("/[^0-9]/",'',$_POST['inventory_pull']);
    $_POST['subcategory_id']         = preg_replace("/[^0-9]/",'',$_POST['subcategory_id']);
    $_POST['random_weight']          = preg_replace("/[^0-9]/",'',$_POST['random_weight']);
    $_POST['meat_weight_type']       = $_POST['meat_weight_type'];
    $_POST['production_type_id']     = preg_replace("/[^0-9]/",'',$_POST['production_type_id']);
    $_POST['listing_auth_type']      = preg_replace("/[^member|^institution|^unlisted|^archived|^unfi]/",'',$_POST['listing_auth_type']);
    $_POST['tangible']               = ($_POST['tangible'] ? '1' : '0');
    $_POST['sticky']                 = ($_POST['sticky'] == 1 ? '1' : '0');
    $_POST['hide_from_invoice']      = ($_POST['hide_from_invoice'] ? '1' : '0');
    $_POST['confirmed']              = ($_POST['confirmed'] ? '1' : '0');
    $_POST['storage_id']             = preg_replace("/[^0-9]/",'',$_POST['storage_id']);
    $_POST['retail_staple']          = preg_replace("/[^0-9]/",'','0'.$_POST['retail_staple']);
    $_POST['staple_type']            = preg_replace("/[^A-Za-z0-9]/",'',$_POST['staple_type']);   // Validation might not be correct
    $_POST['future_delivery']        = preg_replace("/[^0-9]/",'','0'.$_POST['future_delivery']); // Validation might not be correct
    $_POST['future_delivery_type']   = $_POST['future_delivery_type'];                            // Validation might not be correct
    $_POST['image_id']               = preg_replace("/[^0-9]/",'','0'.$_POST['image_id']);
    $_POST['created']                = date('Y-m-d H:i:s', strtotime ($_POST['created']));
    $_POST['modified']               = date('Y-m-d H:i:s', strtotime ($_POST['modified']));

    // Initialize okay_to_post flag
    $okay_to_post = true;
    // Ensure all items have a selected listing auth type
    if ( $_POST['listing_auth_type'] == '')
      {
        array_push ($error_array, 'Please select a listing type.');
        $warn['listing_auth_type'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure there is a product name
    if ( ! $_POST['product_name'] )
      {
        array_push ($error_array, 'You must enter a product name to continue.');
        $warn['product_name'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure there is a subcategory selected
    if ( !$_POST['subcategory_id'] )
      {
        array_push ($error_array, 'Please choose a subcategory.');
        $warn['subcategory_id'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure inventory -- if used -- is not set to zero-pull
    if ( $_POST['inventory_id'] != 0 && $_POST['inventory_pull'] == 0)
      {
        array_push ($error_array, 'Please set a number of inventory units to use for each purchase. It does not make sense to use inventory that never decreases.');
        $warn['inventory_pull'] = ' warn';
        $warn['inventory_id'] = ' warn';
        $okay_to_post = false;
      }
    // If there is a unit price, then there should also be a pricing unit
    if ( $_POST['unit_price'] && ! $_POST['pricing_unit'])
      {
        array_push ($error_array, 'You have entered a price without a pricing unit.');
        $warn['unit_price'] = ' warn';
        $warn['pricing_unit'] = ' warn';
        $okay_to_post = false;
      }
    // There should always be an ordering unit
    if ( ! $_POST['ordering_unit'])
      {
        array_push ($error_array, 'Please enter an ordering unit, often the same as the pricing unit.');
        $warn['ordering_unit'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure random weight items have a minimum and maximum weight entered
    if ( $_POST['random_weight'] && ( ! $_POST['minimum_weight'] || ! $_POST['maximum_weight']) )
      {
        array_push ($error_array, 'You have selected a random weight product. If this is a random weight product you need to enter an approximate minimum and maximum weight.');
        $warn['random_weight'] = ' warn';
        if (! $_POST['minimum_weight']) $warn['minimum_weight'] = ' warn';
        if (! $_POST['maximum_weight']) $warn['maximum_weight'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure that random (minimum weight) is less than random (maximum weight)
    if ( $_POST['random_weight'] && ( $_POST['minimum_weight'] > $_POST['maximum_weight']) )
      {
        array_push ($error_array, 'You have selected a random weight product but the minimum weight is greater than the maximum weight.');
        $warn['minimum_weight'] = ' warn';
        $warn['maximum_weight'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure items with a meat weight type are also random weight items
    if ( $_POST['meat_weight_type'] && ! $_POST['random_weight'] )
      {
        array_push ($error_array, 'Meat weight type is only valid for random weight items.');
        $warn['meat_weight_type'] = ' warn';
        $warn['random_weight'] = ' warn';
        $okay_to_post = false;
      }
//     // Ensure items with a meat weight type do not have minimum or maximum random weights (WHY???)
//     if ( ! $_POST['meat_weight_type'] && ! $_POST['random_weight'] )
//       {
//         $_POST['minimum_weight'] = '0';
//         $_POST['maximum_weight'] = '0';
//       }
    // Ensure all items have a selected production type
    if ( ! $_POST['production_type_id'] )
      {
        array_push ($error_array, 'Please select a production type.');
        $warn['production_type_id'] = ' warn';
        $okay_to_post = false;
      }
    // There should always be a storage type selected
    if ( ! $_POST['storage_id'] )
      {
        array_push ($error_array, 'Please select as storage type.');
        $warn['storage_type'] = ' warn';
        $okay_to_post = false;
      }
  }

// Do the postings to the database
if ( $okay_to_post == true)
  {
    // Add a new product from scratch
    if ($_POST['submit_action'] == 'Add Product')
      {
        // Reserve a new product in the database, then all we need to do is update it later
        $new_product_row = get_next_product_id ($producer_id);
        $pvid = $new_product_row['pvid'];
        $_POST['product_id'] = $new_product_row['product_id'];
        $_POST['product_version'] = 1; // Alwasy begin versions with 1
        $set_created = 'created = NOW(),'; // Set current creation date
      }
    // Add existing product as new
    elseif ($_POST['save_as_new'] == 'Save as a New Product')
      {
        // Get information for the original product from the database
        $db_product_info = get_product ($_POST['product_id'], $_POST['product_version'], '');
        // Before comparing, make sure the version coming from the database is handled the same as the form data
        $db_product_info['product_description'] = nl2br2 (strip_tags (htmlspecialchars(br2nl ($db_product_info['product_description']), ENT_QUOTES), '<b><i><u><strong><em>'));
        $force_new_version = false;
        $force_confirmation = false;
        foreach (array_keys($products_fields) as $field)
          {
            // Is the field different from the database (i.e. changed)?
            if ($_POST[$field] != $db_product_info[$field])
              {
                if ($products_fields[$field] == 'trigger_new_version') $force_new_version = true;
                if (in_array ($field, explode (',', FIELDS_REQ_CONFIRM))) $force_confirmation = true;
              }
          }
        // Clobber the image_id as a workaround to prevent multiple products linking to the same image
        $_POST['image_id'] = '';
        // Reserve a new product in the database, then all we need to do is update it later
        $new_product_row = get_next_product_id ($producer_id);
        $pvid = $new_product_row['pvid'];
        $_POST['product_id'] = $new_product_row['product_id'];
        $_POST['product_version'] = 1; // Alwasy begin versions with 1
        $set_created = 'created = NOW(),'; // Set current creation date
      }
    // Modify existing product (maybe add new version)
    else
      {
        $modify_product = true;
        // Get information for the original product from the database
        $db_product_info = get_product ($_POST['product_id'], $_POST['product_version'], '');
        // Before comparing, make sure the version coming from the database is handled the same as the form data
        $db_product_info['product_description'] = nl2br2 (strip_tags (br2nl ($db_product_info['product_description']), '<b><i><u><strong><em>'));
        $force_new_version = false;
        $force_confirmation = false;
        foreach (array_keys($products_fields) as $field)
          {
            // Is the field different from the database (i.e. changed)?
            if ($_POST[$field] != $db_product_info[$field])
              {
                if ($products_fields[$field] == 'trigger_new_version') $force_new_version = true;
                if (in_array ($field, explode (',', FIELDS_REQ_CONFIRM))) $force_confirmation = true;
              }
          }
        // If need to force a new product version, get one and put it into the database
        if ($force_new_version == true)
          {
            // Get the new version (maximum old version + 1)
            $query = '
              SELECT
                MAX(product_version) + 1 AS new_product_version
              FROM
                '.NEW_TABLE_PRODUCTS.'
              WHERE
                product_id = "'.mysql_real_escape_string($_POST['product_id']).'"
                AND producer_id = "'.mysql_real_escape_string($producer_id).'"';
            $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 829605 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysql_fetch_array($result))
              {
                $new_product_version = $row['new_product_version'];
              }
            // Insert the new product/version to reserve a place in the products table
            $query = '
              INSERT INTO
                '.NEW_TABLE_PRODUCTS.'
              SET
                product_id = "'.mysql_real_escape_string($_POST['product_id']).'",
                product_version = "'.mysql_real_escape_string($new_product_version).'",
                producer_id = "'.mysql_real_escape_string($producer_id).'"';
            $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 634443 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            $pvid = mysql_insert_id();
            $old_product_version = $db_product_info['product_version'];
            $_POST['product_version'] = $new_product_version;
            $set_created = 'created = "'.mysql_real_escape_string($_POST['created']).'",'; // Apply the original creation date
          }
      }
    // At this point any new product versions have been added to the database, ready to update
    // Set some convenient variables
    $is_admin = false;
    $prev_preferred = false;
    $prev_confirmed = false;
    $now_confirmed = false;
    $add_product = false;
    $save_as_new = false;
    $product_id = $_POST['product_id'];
    $product_version = $_POST['product_version'];
    // And clobber the values if necessary
    if (CurrentMember::auth_type('producer_admin,site_admin')) $is_admin = true;
    if ($db_product_info['confirmed'] == -1) $prev_preferred = true;
    elseif ($db_product_info['confirmed'] == 1) $prev_confirmed = true;
    if($_POST['confirmed'] == 1) $now_confirmed = true;
    if ($_POST['submit_action'] == 'Add Product') $add_product = true;
    if ($_POST['save_as_new'] == 'Save as a New Product') $save_as_new = true;

    // Now handle the various cases...                                                                   //
    //                                                                                                   //
    // WHO       action      (condition)                             RESULT (new)    OLD VER (force new) //
    // -----     --------    ---------------------------------       -------------   ------------------- //
    //                                                                                                   //
    // ADMIN ->  confirm ->                                          CONFIRM                             //
    //           unconfirm (prev confirmed) ->                       AUTO-PREFER                         //
    //           unconfirm (prev unconfirmed) ->                     [NO ACTION]                         //
    //                                                                                                   //
    // PRDUCR -> add_product (force_confirm) ->                      PREFER                              //
    //                                                                                                   //
    //           save_as_new -> (no force) (prev confirmed) ->       CONFIRM                             //
    //                          (force_confirm) ->                   PREFER                              //
    //                          (no force) (prev unconfirmed) ->     PREFER                              //
    //                                                                                                   //
    //                                                                                                   //
    //   following apply (regardless of force-new and not)                                               //
    //           edit_product (force confirm) (prev confirmed) ->    PREFER          AS CONFIGURED       //
    //           edit_product (force confirm) (prev unconfirmed) ->  AUTO-PREFER     [NO ACTION]         //
    //           edit_product (no force) (prev confirmed) ->         CONFIRM         UNCONFIRM           //
    //           edit_product (no force) (prev unconfirmed) ->       AUTO-PREFER     PREFER              //
    //                                                                                                   //



    // Admin functions are always approved
    if ($is_admin == true)
      {
        // Confirm this version no matter what
        if ($now_confirmed == true)
          {
            clear_product_confirm ($product_id);
            set_product_confirm ($product_id, $product_version, 'confirmed');
          }
        // Unconfirm this version and set auto-prefer
        elseif ($now_confirmed == false && $prev_confirmed == true)
          {
            clear_product_confirm ($product_id);
            set_product_confirm ($product_id, $product_version, 'unconfirmed');
            set_product_auto_prefer ($product_id);
          }
        // This version was preferred, so keep it that way
        elseif ($now_confirmed == false && $prev_preferred == true)
          {
            clear_product_confirm ($product_id);
            set_product_confirm ($product_id, $product_version, 'preferred');
          }
        // Else no action necessary
      }
    // Add new product
    elseif ($add_product == true)
      {
        // New product with no old versions to clear. Only one version so it is preferred.
        set_product_confirm ($product_id, $product_version, 'preferred');
      }
    // Add existing product as new
    elseif ($save_as_new == true)
      {
        // Similar enough to existing confirmed product, so go ahead and confirm it
        if ($force_confirmation == false && $prev_confirmed == true)
          {
            set_product_confirm ($product_id, $product_version, 'confirmed');
          }
        // Otherwise it is the only product, so set preferred
        else
          {
            set_product_confirm ($product_id, $product_version, 'preferred');
          }
      }
    // Update existing product (with or without creating a new version)
    else
      {
        // Was confirmed, but needs another confirmation, so mark preferred
        if ($force_confirmation == true && $prev_confirmed == true)
          {
            clear_product_confirm ($product_id);
            set_product_confirm ($product_id, $product_version, 'preferred');
          }
        // Set auto-prefer since we do not know who to prefer
        elseif ($force_confirmation == true && $prev_confirmed == false)
          {
            clear_product_confirm ($product_id);
            set_product_auto_prefer ($product_id);
          }
        // It was confirmed and is not forced to confirm again so set confirmed
        elseif ($prev_confirmed == true)
          {
            clear_product_confirm ($product_id);
            set_product_confirm ($product_id, $product_version, 'confirmed');
          }
        // was not previously confirmed but might have been preferred, so set auto-prefer
        else
          {
            clear_product_confirm ($product_id);
            set_product_auto_prefer ($product_id);
          }
        // And if there is an new version, go handle the version that was replaced
        if ($force_new_version == true)
          {
            // Force new version to confirm... leave old version active or not?
            if ($force_confirmation == true && $prev_confirmed == true)
              {
                if (KEEP_OLD_PROD_CONF == true)
                  {
                    set_product_confirm ($product_id, $old_product_version, 'confirmed');
                    set_product_confirm ($product_id, $new_product_version, 'unconfirmed');
                  }
                else
                  {
                    set_product_confirm ($product_id, $old_product_version, 'unconfirmed');
                  }
              }
            // New product is confirmed, so old one can be unconfirmed
            elseif ($force_confirmation == false && $prev_confirmed == true)
              {
                set_product_confirm ($product_id, $old_product_version, 'unconfirmed');
              }
            // Else we have already auto-preferred, so nothing to do for prior unconfirmed case
          }
      }


//         // Force confirmation regardless of new version or old -- except for admin
//         if ($force_confirmation == true &&
//             ! CurrentMember::auth_type('producer_admin,site_admin'))
//           {
//             if ($db_product_info['confirmed'] == 1) $_POST['confirmed'] = -1; // Unconfirmed but preferred
// //echo"<pre>CONFIRMED 4:".$_POST['confirmed']."</pre>";
//             if ($db_product_info['confirmed'] == 0) $_POST['confirmed'] = 0; // Just plain unconfirmed
// //echo"<pre>CONFIRMED 5:".$_POST['confirmed']."</pre>";
//           }
//       }
//     // Confirmed means we must un-confirm all other versions of this product. We can get away
//     // with unconfirming all of them because the current one will become confirmed when updated.
//     if ($_POST['confirmed'] == 1)
//       {
//         $query = '
//           UPDATE
//             '.NEW_TABLE_PRODUCTS.'
//           SET
//             confirmed = "0"
//           WHERE
//             product_id = "'.mysql_real_escape_string($_POST['product_id']).'"
//             AND producer_id = "'.mysql_real_escape_string($producer_id).'"';
//         $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 673408 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//       }
//     // If UN-confirming a previously confirmed product, then need to set a new preferred alternative
//     if ($_POST['confirmed'] == 0 && $db_product_info['confirmed'] == "1") $_POST['confirmed'] = -1;
//     // Admin may confirm the product
//     if (($_POST['confirmed'] == -1 || $_POST['confirmed'] == 1) && CurrentMember::auth_type('producer_admin,site_admin'))  $_POST['confirmed'] = 1;
//     // If we are setting confirmed to +1 or -1, then make sure it is the only one but do not mess with anything
//     // that is already confirmed
//     if ($_POST['confirmed'] == -1 || $_POST['confirmed'] == 1)
//       {
//         $query = '
//           UPDATE
//             '.NEW_TABLE_PRODUCTS.'
//           SET
//             confirmed = "0"
//           WHERE
//             product_id = "'.mysql_real_escape_string($_POST['product_id']).'"
//             AND producer_id = "'.mysql_real_escape_string($producer_id).'"
//             AND confirmed = "-1"';
//         $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 673408 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//       }



    // Case where we need to create a new inventory unit for this product
    if ($_POST['inventory_id'] == -1)
      {
        $query = '
          INSERT INTO
            '.TABLE_INVENTORY.'
          SET
            producer_id = "'.mysql_real_escape_string($producer_id).'",
            description = "'.mysql_real_escape_string($_POST['product_name']).'",
            quantity = "'.mysql_real_escape_string(0).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 749026 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if ($result) $_POST['inventory_id'] = mysql_insert_id();
      }


    // Now insert/update the product:
    $sqlu = '
      UPDATE
        '.NEW_TABLE_PRODUCTS.'
      SET
        product_name = "'.mysql_real_escape_string ($_POST['product_name']).'",
        product_description = "'.mysql_real_escape_string ($_POST['product_description']).'",
        subcategory_id = "'.mysql_real_escape_string ($_POST['subcategory_id']).'",
        account_number = "'.mysql_real_escape_string ($_POST['account_number']).'",
        inventory_id = "'.mysql_real_escape_string ($_POST['inventory_id']).'",
        inventory_pull = "'.mysql_real_escape_string ($_POST['inventory_pull']).'",
        unit_price = "'.mysql_real_escape_string ($_POST['unit_price']).'",
        pricing_unit = "'.mysql_real_escape_string ($_POST['pricing_unit']).'",
        ordering_unit = "'.mysql_real_escape_string ($_POST['ordering_unit']).'",
        production_type_id = "'.mysql_real_escape_string ($_POST['production_type_id']).'",
        extra_charge = "'.mysql_real_escape_string ($_POST['extra_charge']).'",
        product_fee_percent = "'.mysql_real_escape_string ($_POST['product_fee_percent']).'",
        random_weight = "'.mysql_real_escape_string ($_POST['random_weight']).'",
        minimum_weight = "'.mysql_real_escape_string ($_POST['minimum_weight']).'",
        maximum_weight = "'.mysql_real_escape_string ($_POST['maximum_weight']).'",
        meat_weight_type = "'.mysql_real_escape_string ($_POST['meat_weight_type']).'",
        listing_auth_type = "'.mysql_real_escape_string ($_POST['listing_auth_type']).'",
        taxable = (SELECT taxable FROM '.TABLE_SUBCATEGORY.' WHERE subcategory_id = "'.mysql_real_escape_string ($_POST['subcategory_id']).'"),
        sticky = "'.mysql_real_escape_string ($_POST['sticky']).'",
        tangible = "'.mysql_real_escape_string ($_POST['tangible']).'",
        storage_id = "'.mysql_real_escape_string ($_POST['storage_id']).'",
        retail_staple = "'.mysql_real_escape_string ($_POST['retail_staple']).'",
        /* future_delivery = "", */
        /* future_delivery_type = "", */
        image_id = "'.mysql_real_escape_string ($_POST['image_id']).'",
        confirmed = "'.mysql_real_escape_string ($_POST['confirmed']).'",
        /* staple_type, */
        '.$set_created.'
        modified = NOW(),
        hide_from_invoice = "'.mysql_real_escape_string ($_POST['hide_from_invoice']).'"
      WHERE
        producer_id = "'.mysql_real_escape_string ($producer_id).'"
        AND product_id = "'.mysql_real_escape_string ($_POST['product_id']).'"
        AND product_version = "'.mysql_real_escape_string ($_POST['product_version']).'"';
    $result = @mysql_query($sqlu, $connection) or die(debug_print ("ERROR: 267154 ", array ($sqlu,mysql_error()), basename(__FILE__).' LINE '.__LINE__));

//    $new_product_id = mysql_insert_id ();


//     // Now check again for product confirmations. It is important that at least one product_version
//     // be preferred out of the product_id.
//     $query = '
//       SELECT
//         MAX(confirmed) AS max_confirmed,
//         MIN(confirmed) AS min_confirmed,
//         MAX(product_version) AS max_product_version
//       FROM
//         '.NEW_TABLE_PRODUCTS.'
//       WHERE
//         product_id = "'.mysql_real_escape_string($_POST['product_id']).'"
//         AND producer_id = "'.mysql_real_escape_string($producer_id).'"
//       GROUP BY
//         product_id';
//       $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 901347 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//       if ($row = mysql_fetch_array($result))
//         {
//           // If nothing confirmed nor preferred, then set highest version to preferred
//           if ($row['max_confirmed'] != 1 && $row['min_confirmed'] != -1)
//             {
//               $query = '
//                 UPDATE
//                   '.NEW_TABLE_PRODUCTS.'
//                 SET
//                   confirmed = "-1"
//                 WHERE
//                   product_id = "'.mysql_real_escape_string($_POST['product_id']).'"
//                   AND producer_id = "'.mysql_real_escape_string($producer_id).'"
//                   AND product_version = "'.mysql_real_escape_string($row['max_product_version']).'"';
//                 $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 059372 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//             }
//         }


    // Delete all unconfirmed and unpreferred versions that were never used (in baskets)
    $query = '
      DELETE
        '.NEW_TABLE_PRODUCTS.'
      FROM
        '.NEW_TABLE_PRODUCTS.'
      LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(product_id,product_version)
      WHERE
        confirmed = "0"
        AND product_id = "'.mysql_real_escape_string($_POST['product_id']).'"
        AND product_version != "'.mysql_real_escape_string ($_POST['product_version']).'"
        AND bpid IS NULL';
//    $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 926790 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    // Return to the prior page with a new product notice
    if ($_POST['submit_action'] == 'Add Product' ||
        $_POST['save_as_new'] == 'Save as a New Product')
      {
        $result3 = @mysql_query($sqlu,$connection) or die(debug_print ("ERROR: 287568 ", array ($sqlu,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $return_target = $referrer.'#X'.$_POST['product_id'];
        header('refresh: 2; url="'.$return_target.'"');
        echo '<div style="width:40%;margin-left:auto;margin-right:auto;font-size:3em;padding:2em;text-align:center;color:fff;background-color:#080;">New product #'.$_POST['product_id'].' has been created.</div>';
        exit (0);
      }
    // Return to the prior page with an update notice
    elseif($_POST['submit_action'] == 'Update Product')
      {
        $return_target = $referrer.'#X'.$_POST['product_id'];
        header ('refresh: 2; url="'.$return_target.'"');
        echo '<div style="width:40%;margin-left:auto;margin-right:auto;font-size:3em;padding:2em;text-align:center;color:fff;background-color:#008;">Product #'.$_POST['product_id'].' has been updated.</div>';
        exit (0);
      }
  }





// Return to the prior page with a cancel notice
if($_POST['submit_action'] == 'Cancel')
  {
    $return_target = $referrer.'#X'.$_POST['product_id'];
    header('refresh: 2; url="'.$return_target.'"');
    echo '<div style="width:40%;margin-left:auto;margin-right:auto;font-size:3em;padding:2em;text-align:center;color:fff;background-color:#800;">Editing<br>was<br>CANCELLED.</div>';
    exit (0);
  }
// If we did not exit, then we need to go back to adding or editing
if (count ($error_array))
  {
    // Rather than using get_product() again, keep the $_POST info
    $product_info = $_POST;
    // If "adding" a product, then be sure not to switch to "editing" since it won't have a product_id
    if ($_POST['submit_action'] == 'Add Product' || $_POST['submit_action'] == 'Save as a New Product')
      {
        $action = 'add';
      }
  }

// This function will set the selected confirmation and unconfirm all other
// versions of the product.
function set_product_confirm ($product_id, $product_version, $state)
  {
    // $state is ['preferred'|'confirmed'|'unconfirmed']
    global $connection;
    switch ($state)
      {
        case 'preferred':
          $confirmed = -1;
          break;
        case 'confirmed':
          $confirmed = 1;
          break;
        case 'unconfirmed':
          $confirmed = 0;
          break;
      }
    $query = '
      UPDATE
        '.NEW_TABLE_PRODUCTS.'
      SET
        confirmed = "'.mysql_real_escape_string($confirmed).'"
      WHERE
        product_id = "'.mysql_real_escape_string($product_id).'"
        AND product_version = "'.mysql_real_escape_string($product_version).'"';
    $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 354218 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
  }

function clear_product_confirm ($product_id)
  {
    global $connection;
    $query = '
      UPDATE
        '.NEW_TABLE_PRODUCTS.'
      SET
        confirmed = "0"
      WHERE
        product_id = "'.mysql_real_escape_string($product_id).'"
        AND confirmed != "0"';
    $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 542671 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
  }

function set_product_auto_prefer ($product_id)
  {
    global $connection;
    // Get the most recently modified product version
    $query = '
      SELECT
        product_version
      FROM
        '.NEW_TABLE_PRODUCTS.'
      WHERE
        product_id = "'.mysql_real_escape_string($product_id).'"
      ORDER BY
        modified DESC
      LIMIT 1';
    $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 547348 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysql_fetch_array($result))
      {
        $product_version = $row['product_version'];
      }
    $query = '
      UPDATE
        '.NEW_TABLE_PRODUCTS.'
      SET
        confirmed = "-1"
      WHERE
        product_id = "'.mysql_real_escape_string($product_id).'"
        AND product_version = "'.mysql_real_escape_string($product_version).'"';
    $result = mysql_query($query, $connection) or die (debug_print ("ERROR: 543804 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
  }
?>