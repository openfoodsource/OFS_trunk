<?php
include_once 'config_openfood.php';
session_start();

// We need to access the OLD product_list_prep table -- without the database
// prefix, so get the name for it here by stripping it off the front of the
// product_list_prep table name:

define('TABLE_PRODUCT_PREP_ORIG', substr(TABLE_PRODUCT_PREP, strlen(DB_PREFIX)));

// README  README  README  README  README  README  README  README  README  README  README  README 
// 
// After the customer_basket_items have been validated against the old invoices, this program can
// be used to update the new tables. It will cycle through all customer_basket_items referenced by
// basket_items entries and create entries into the products table with relevant versions. 
// It will also create customer messages to producers in the messages table, as needed and update
// values in the basket_items table.
// 
// The program should be extended to also update the baskets table so that it can leave the existing
// customer_basket_ovarall table alone.


// CHECK FOR AJAX CALL (for compactness, this script handles its own ajax)
if ($_REQUEST['ajax'] == 'yes')
  {
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Get a list of products for the particular delivery_id                 //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'get_product_list')
      {
        $delivery_id = $_REQUEST['delivery_id'];
        $ajax_content = '
          <ul>
            <li><div class="p_list_header p_list_pid">Prod.&nbsp;ID</div><div class="p_list_header p_list_pid">BPID</div><div class="p_list_header p_list_name">Product&nbsp;Name</div></a></li>';
        $query = '
          SELECT
            '.NEW_TABLE_BASKET_ITEMS.'.*
          FROM '.NEW_TABLE_BASKETS.'
          INNER JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(basket_id)
          WHERE delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
          ORDER BY product_id';
        $result= mysql_query($query) or die("Error: 702042 " . mysql_error());
        while($row = mysql_fetch_object($result))
          {
            $ajax_content .= '
          <li id="bpid:'.$row->bpid.'" class="bpid_incomplete" onClick="window.open(\'create_basket_items_products_tables.php?ajax=yes&process=view_invoice&basket_id='.$row->basket_id.'\',\'external\')"><div class="p_list_pid">'.$row->product_id.'</div><div class="p_list_pid">'.$row->bpid.'</div><div class="p_list_name">'.$row->product_name.'</div></li>';
          }
        $ajax_content .= '
          </ul>';
        echo "$ajax_content";
        exit (0);
      }
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Compare products from this order cycle                                //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'process_product')
      {
        // Check to see if this item is already in the basket_items table
        // If it is, then we have already completed processing it
        $query_basket_items = '
          SELECT *
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
          WHERE bpid = "'.mysql_real_escape_string($_REQUEST['bpid']).'"';
        $result_basket_items = mysql_query($query_basket_items) or die("Error: 193752 " . mysql_error());
        if ($row_basket_items = mysql_fetch_array($result_basket_items) && $row_basket_items['product_name'])
          {
            // Everything fine so far (assuming if we have a bpid that points to a valid product_name
            // then it is already handled in the database
            // So short-circuit: Exit and get started processing the next bpid...
            exit (0);
          }
        // At this point, we need to do a few things:
        // 1. See if the product is already added in the new tables.
        // 2. If not: A) add the product to the new baskets table
        // 3.            check against any prior products versions
        // 4.         B) add the product to the new products table
        // Get the current customer_basket_items and relevant product_list_prep data for this basket item.
        // From product_list_prep, only the inventory_*, image_id, modified, and confirmed should contain data.
        $query_customer_basket_items = '
          SELECT
            '.NEW_TABLE_BASKET_ITEMS.'.*,
            0 AS account_number,
            '.TABLE_PRODUCT_PREP_ORIG.'.inventory_pull,
            '.TABLE_PRODUCT_PREP_ORIG.'.inventory_id,
            '.TABLE_PRODUCT_PREP_ORIG.'.future_delivery,
            '.TABLE_PRODUCT_PREP_ORIG.'.future_delivery_id,
            '.TABLE_PRODUCT_PREP_ORIG.'.image_id,
            '.TABLE_PRODUCT_PREP_ORIG.'.modified,
            '.TABLE_PRODUCT_PREP_ORIG.'.confirmed
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN '.TABLE_PRODUCT_PREP_ORIG.' USING(product_id)
          WHERE bpid = "'.mysql_real_escape_string($_REQUEST['bpid']).'"';
        $result_customer_basket_items = mysql_query($query_customer_basket_items) or die("Error: 790962 " . mysql_error());
        if (! $row_customer_basket_items = mysql_fetch_array($result_customer_basket_items))
          {
            // Failure
            echo "PAUSE                         No result found for BPID=".$_REQUEST['bpid'];
          }
        // The following array associates the new baskets table columns with the old
        // customer_basket_items (and product_list_prep) columns they will come from
        $baskets_2_customer_basket_items = array (
          // 'bpid'=>'bpid',                              // Does not have a corresponding field
          'product_id'=>'product_id',
          // 'product_version',                           // We must not compare this value since... WHY?
          'producer_id'=>'producer_id',
          'product_name'=>'product_name',
          'account_number'=>'account_number',             // Bring from product_list_prep table
          'inventory_pull'=>'inventory_pull',             // Bring from product_list_prep table
          'inventory_id'=>'inventory_id',                 // Bring from product_list_prep table
          'product_description'=>'detailed_notes',        // Convert from detailed_notes column
          'subcategory_id'=>'subcategory_id',
          'future_delivery'=>'future_delivery',
          'future_delivery_type'=>'future_delivery_type', // From customer_basket_items
          'production_type_id'=>'prodtype_id',            // Convert from prodtype_id column
          'unit_price'=>'item_price',
          'pricing_unit'=>'pricing_unit',
          'ordering_unit'=>'ordering_unit',
          'random_weight'=>'random_weight',
          'meat_weight_type'=>'meat_weight_type',
          'minimum_weight'=>'minimum_weight',
          'maximum_weight'=>'maximum_weight',
          'extra_charge'=>'extra_charge',
          'product_fee_percent'=>'product_adjust_fee',
          'image_id'=>'image_id',                         // Bring from product_list_prep table
          'listing_auth_type'=>'donotlist',               // Convert from product_list_prep.donotlist column
          'confirmed'=>'confirmed',                       // Bring from product_list_prep table
          'retail_staple'=>'retail_staple',
          'staple_type'=>'staple_type',
          // 'created'=>'created',                        // Does not have a corresponding field
          'modified'=>'modified',                         // Bring from product_list_prep table (timestamp)
          'tangible'=>'tangible',
          // 'sticky'=>'sticky',                          // Does not have a corresponding field
          'hide_from_invoice'=>'hidefrominvoice',
          'storage_id'=>'storage_id'
          );
        // See if there is a product in the new products table that matches
        $query_products = '
          SELECT *
          FROM '.NEW_TABLE_PRODUCTS.'
          WHERE product_id = "'.$row_customer_basket_items['product_id'].'"';
        $result_products = mysql_query($query_products) or die("Error: 709269 " . mysql_error());
        $version_match = 0;
        $version_max = 0;
        while ($row_products = mysql_fetch_array($result_products))
          {
            // Set the default value, to be clobbered if false
            $this_row_matches = true;
            foreach ($baskets_2_customer_basket_items as $key=>$value)
              {
                // Need to fix a few items before comparison
                if ($key == 'listing_auth_type')
                  {
                    switch ($row_customer_basket_items['donotlist'])
                      {
                        case 0:
                          $row_customer_basket_items['donotlist'] = 'member';
                          break;
                        case 1:
                          $row_customer_basket_items['donotlist'] = 'unlisted';
                          break;
                        case 2:
                          $row_customer_basket_items['donotlist'] = 'archived';
                          break;
                        case 3:
                          $row_customer_basket_items['donotlist'] = 'institution';
                          break;
                        case 4:
                          $row_customer_basket_items['donotlist'] = 'unfi'; // This might not be the correct code
                          break;
                        default:
                          $row_customer_basket_items['donotlist'] = 'unlisted'; // Set unlisted if unknown donotlist condition
                          break;
                      }
                  }
                $row_customer_basket_items['detailed_notes'] = stripslashes ($row_customer_basket_items['detailed_notes']);
                $row_products['product_description'] = stripslashes ($row_products['product_description']);

                $row_customer_basket_items['product_name'] = stripslashes ($row_customer_basket_items['product_name']);
                $row_products['product_name'] = stripslashes ($row_products['product_name']);

                $row_customer_basket_items['extra_charge'] = number_format ($row_customer_basket_items['extra_charge'], 2);
                $row_products['extra_charge'] = number_format ($row_products['extra_charge'], 2);

                $row_customer_basket_items['item_price'] = number_format ($row_customer_basket_items['item_price'], 3);
                $row_products['unit_price'] = number_format ($row_products['unit_price'], 3);
                // If columns do not match, then no sense in looking farther
                if ($row_products[$key] != $row_customer_basket_items[$value])
                  {
                    // $status_text .= '<li>Match failure on <em>'.$key.'</em>: '.$row_products[$key].'::'.$row_customer_basket_items[$value].'</li>';
                    $this_row_matches = false;
                    break; // ... out of the foreach loop
                  }
              }
            // Keep track of the highest version we have seen for this product
            if ($row_products['product_version'] > $version_max)
              {
                $version_max = $row_products['product_version'];
              }
            // If the row matched, the we have a match and are done comparing
            if ($this_row_matches == true)
              {
                $version_match = $row_products['product_version'];
                $status_text .= '<li>Existing product-version found: '.$row_products['product_id'].'-'.$row_products['product_version'].'</li>';
              }
          }
        // If there was no version match, then create the new row in the products table
        if ($version_match == 0)
          {
            // Begin by setting the new version (now we have a $version_match value for later).
            $version_match = $version_max + 1;
            // Translate the listing_auth_type for use in the new products table
            switch ($row_customer_basket_items['donotlist'])
              {
                case 0:
                  $listing_auth_type = 'member';
                  break;
                case 1:
                  $listing_auth_type = 'unlisted';
                  break;
                case 2:
                  $listing_auth_type = 'archived';
                  break;
                case 3:
                  $listing_auth_type = 'institution';
                  break;
                case 4:
                  $listing_auth_type = 'unfi'; // This might not be the correct code
                  break;
                default:
                  $listing_auth_type = 'unlisted'; // Set unlisted if unknown donotlist condition
                  break;
              }
            $status_text .= '<li>Creating new product-version: '.$row_customer_basket_items['product_id'].'-'.$version_match.'</li>';
            $query_update_products = '
              INSERT INTO '.NEW_TABLE_PRODUCTS.'
                (
                  /* pvid is an auto-increment field */
                  product_id,
                  product_version,
                  producer_id,
                  product_name,
                  account_number,
                  inventory_pull,
                  inventory_id,
                  product_description,
                  subcategory_id,
                  future_delivery,
                  future_delivery_type,
                  production_type_id,
                  unit_price,
                  pricing_unit,
                  ordering_unit,
                  random_weight,
                  meat_weight_type,
                  minimum_weight,
                  maximum_weight,
                  extra_charge,
                  product_fee_percent,
                  image_id,
                  listing_auth_type,
                  taxable,
                  confirmed,
                  retail_staple,
                  staple_type,
                  created,
                  modified,
                  tangible,
                  sticky,
                  hide_from_invoice,
                  storage_id
                )
              VALUES
                (
                  "'.mysql_real_escape_string($row_customer_basket_items['product_id']).'",
                  "'.mysql_real_escape_string($version_match).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['producer_id']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['product_name']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['account_number']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['inventory_pull']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['inventory_id']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['detailed_notes']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['subcategory_id']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['future_delivery']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['future_delivery_type']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['prodtype_id']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['item_price']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['pricing_unit']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['ordering_unit']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['random_weight']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['meat_weight_type']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['minimum_weight']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['maximum_weight']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['extra_charge']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['product_adjust_fee']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['image_id']).'",
                  "'.mysql_real_escape_string($listing_auth_type).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['taxable']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['confirmed']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['retail_staple']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['staple_type']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['created']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['modified']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['tangible']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['sticky']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['hide_from_invoice']).'",
                  "'.mysql_real_escape_string($row_customer_basket_items['storage_id']).'"
                )';
            $result_update_products = mysql_query($query_update_products) or die("Error: 620523 " . mysql_error());
          }
        // Up until now, out_of_stock has been ALL or NOTHING. Now the out_of_stock is either zero (for
        // items that are not out) or some number up to the full quantity that has been outed.
        if ($row_customer_basket_items['out_of_stock'] == 1)
          {
            // This is the "ALL" case
            $out_of_stock = $row_customer_basket_items['quantity'];
          }
        else
          {
            // This is the "NOTHING" case
            $out_of_stock = '0';
          }
        // Given the new version (now contained in $version_match), update the basket_items table
        $status_text .= '<li>Updating basket_items for bpid: '.$row_customer_basket_items['bpid'].'</li>';
        $query_update_basket_items = '
          UPDATE '.NEW_TABLE_BASKET_ITEMS.'
          SET
            basket_id = "'.mysql_real_escape_string($row_customer_basket_items['basket_id']).'",
            product_id = "'.mysql_real_escape_string($row_customer_basket_items['product_id']).'",
            product_version = "'.mysql_real_escape_string($version_match).'",
            quantity = "'.mysql_real_escape_string($row_customer_basket_items['quantity']).'",
            total_weight = "'.mysql_real_escape_string($row_customer_basket_items['total_weight']).'",
            product_fee_percent = "'.mysql_real_escape_string($row_customer_basket_items['product_adjust_fee']).'",
            subcategory_fee_percent = "'.mysql_real_escape_string($row_customer_basket_items['subcat_adjust_fee']).'",
            producer_fee_percent = "'.mysql_real_escape_string($row_customer_basket_items['producer_adjust_fee']).'",
            out_of_stock = "'.mysql_real_escape_string($out_of_stock).'",
            future_delivery = "'.mysql_real_escape_string($row_customer_basket_items['future_delivery']).'",
            future_delivery_type = "'.mysql_real_escape_string($row_customer_basket_items['future_delivery_type']).'",
            date_added = "'.mysql_real_escape_string($row_customer_basket_items['date_added']).'"
          WHERE bpid = "'.mysql_real_escape_string($row_customer_basket_items['bpid']).'"';
        $result_update_basket_items = mysql_query($query_update_basket_items) or die("Error: 208548 " . mysql_error());
        // One last thing to do:
        // If there was a customer_notes_to_producer, we need to add them to the messages table
        if (strlen ($row_customer_basket_items['customer_notes_to_producer']) > 0)
          {
            // NOTE: it is assumed message_type_id = 1 will be "customer notes to producer"
            // Set the message_types table accordingly
            $status_text .= '<li>Adding message to producer for bpid: '.$row_customer_basket_items['bpid'].'</li>';
            // Use str_replace to remove the old back-slashes. Unlikely there are any that should be there.
            $query_update_messages = '
              REPLACE INTO '.NEW_TABLE_MESSAGES.'
                (
                  /* pvid is an auto-increment field */
                  message_type_id,
                  referenced_key1,
                  referenced_key2,
                  referenced_key3,
                  message
                )
              VALUES
                (
                  (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "customer message to producer"),
                  "'.mysql_real_escape_string($row_customer_basket_items['bpid']).'",
                  "",
                  "",
                  "'.mysql_real_escape_string(str_replace ('\\', '', $row_customer_basket_items['customer_notes_to_producer'])).'"
                )';
            $result_update_messages = mysql_query($query_update_messages) or die("Error: 072343 " . mysql_error());
          }
        // If we don't have a product_id, then pause operations
        if (! $row_customer_basket_items['product_id'])
          {
            echo 'PAUSE                         <pre>'.print_r($_REQUEST,true).'</pre>';
          }
        else
          {
            echo '                              '.$status_text;
          }
        exit (0);
      }
    // Ajax as called, but not used, so exit with error
    exit (1);
  }
// BEGIN GENERATING MAIN PAGE //////////////////////////////////////////////////
$content .= '
  <div id="controls">
    <div id="basket_generate_start">
      <input id="delivery_generate_button" type="submit" onClick="reset_delivery_list(); delivery_generate_start(); generate_basket_list();" value="Begin Processing">
    </div>
    <div id="delivery_progress"><div id="c_progress-left"></div><div id="c_progress-right"></div></div>
    <div id="basket_progress"><div id="p_progress-left"></div><div id="p_progress-right"></div></div>
  </div>
<div id="reporting">
  <div id="left-column">
    <div id="customerBox">
      <div class="customerList" id="customerList">
        <ul>
          <li><div class="c_list_header c_list_cid">Del&nbsp;ID</div><div class="c_list_header c_list_name">Date [Quantity]</div></a></li>';
// Get a list of all delivery_id values
$query = '
  SELECT
    '.TABLE_ORDER_CYCLES.'.delivery_id,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    COUNT('.NEW_TABLE_BASKETS.'.basket_id) AS quantity
  FROM
    '.TABLE_ORDER_CYCLES.'
  RIGHT JOIN '.NEW_TABLE_BASKETS.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.NEW_TABLE_BASKETS.'.delivery_id
  GROUP BY '.NEW_TABLE_BASKETS.'.delivery_id';
$result= mysql_query($query) or die("Error: 899032" . mysql_error());
while($row = mysql_fetch_object($result))
  {
    $content .= '          <li id="delivery_id:'.$row->delivery_id.'" class="del_complete"><div class="c_list_cid">'.$row->delivery_id.'</div><div class="c_list_name">'.$row->delivery_date.' ['.$row->quantity.' Orders]</div></li>';
  }
$content .= '
        </ul>
      </div>
    </div>
  </div>
  <div id="right-column">
    Pause: <input type="checkbox" id="pause" name="pause" onClick="process_product_list()">
    Delivery: <input type="text" size="8" id="delivery_id" name="delivery_id">
    <div id="basketBox">
      <div class="basketList" id="basketList">
  [process information goes here]
      </div>
    </div>
  </div>
</div>
<div id="process_area" style="clear:both;">
  <div id="process_target">[process here]</div>
</div>';

$page_specific_javascript = '
    <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
    <script type="text/javascript" src="'.PATH.'create_basket_items_products_tables.js"></script>';

$page_specific_css = '
    <link href="'.PATH.'create_basket_items_products_tables.css" rel="stylesheet" type="text/css">';

$page_title_html = '<span class="title">Site Admin Functions</span>';
$page_subtitle_html = '<span class="subtitle">Convert Accounting</span>';
$page_title = 'Site Admin Functions: Convert Accounting';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
