<?php
include_once 'config_openfood.php';
include_once ('func.get_product.php');
session_start();
valid_auth('producer,producer_admin');

// Initialize variables
$action = $_REQUEST['action']; // Sometimes POSTed and sometimes GETted first call: [add|edit] return calls: [update|save|save_new]
$error_array = array ();
$warn_array = array ();
$help_array = array ();
$confirm_array = array ();
// Set some defaults
$alert_type = 'error';
$alert_message = 'Please correct the following problems and resubmit.';
$product_is_rejected = false;

// Figure out where we came from and save it so we can go back
// if (isset ($_REQUEST['referrer']))
//     $referrer = $_REQUEST['referrer']
//     or $referrer = $_SERVER['HTTP_REFERER'];

// If we don't have a producer_id then admin may get one from the arguments
if ($_GET['producer_id'] != 0
    && CurrentMember::auth_type('producer_admin,site_admin'))
  {
    $producer_id = $_GET['producer_id'];
  }
else
  {
    $producer_id = $_SESSION['producer_id_you'];
  }

// Does this user have administrative rights
if (CurrentMember::auth_type('producer_admin'))
    $is_admin = true
    or $is_admin = false;

// These are all fields from the product table (alphabetical by column for convenience)
$product_fields_array = array (
    'account_number',         'image_id',               'pricing_unit',             'random_weight',
    'active',                 'inventory_id',           'producer_id',              'retail_staple',
    'approved',               'inventory_pull',        'product_description',       'staple_type',
    'confirmed',              'listing_auth_type',      'product_fee_percent',      'sticky',
    'created',                'maximum_weight',         'product_id',               'storage_id',
    'extra_charge',           'meat_weight_type',       'production_type_id',       'subcategory_id',
    'future_delivery',        'minimum_weight',         'product_name',             'tangible',
    'future_delivery_type',   'modified',               'product_version',          'taxable',
    'hide_from_invoice',      'ordering_unit',          'pvid',                     'unit_price'
  );
// These are the fields that, when changed, will force a new product version
// Generally, these are things that might affect product presentation for invoicing
$trigger_new_version_array = array ( // We can disable these by putting an (*) in the value
    'account_number',       '* image_id',               'pricing_unit',             'random_weight',
  '* active',               '* inventory_id',           'producer_id',              'retail_staple',
  '* approved',             '* inventory_pull',         'product_description',      'staple_type',
  '* confirmed',            '* listing_auth_type',      'product_fee_percent',    '* sticky',
  '* created',                'maximum_weight',         'product_id',               'storage_id',
    'extra_charge',           'meat_weight_type',       'production_type_id',     '* subcategory_id',
    'future_delivery',        'minimum_weight',         'product_name',             'tangible',
    'future_delivery_type', '* modified',               'product_version',          'taxable',
    'hide_from_invoice',      'ordering_unit',        '* pvid',                     'unit_price'
  );
// These are the fields that, when changed, will flag a product as such (MODIFIED)
// Generally, these are things that only affect product presentation for lists and baskets (what customer sees when shopping)
$trigger_change_array = array ( // We can disable these by putting an (*) in the value
  '* account_number',         'image_id',             'pricing_unit',               'random_weight',
  '* active',               '* inventory_id',         'producer_id',              '* retail_staple',
  '* approved',             '* inventory_pull',         'product_description',    '* staple_type',
  '* confirmed',            '* listing_auth_type',    '* product_fee_percent',    '* sticky',
  '* created',                'maximum_weight',         'product_id',               'storage_id',
    'extra_charge',           'meat_weight_type',       'production_type_id',       'subcategory_id',
  '* future_delivery',        'minimum_weight',         'product_name',           '* tangible',
  '* future_delivery_type', '* modified',             '* product_version',        '* taxable',
  '* hide_from_invoice',      'ordering_unit',        '* pvid',                     'unit_price'
  );
// Fields requiring confirmation are stored in the site configuration
// Preload these with the class that will be applied
foreach (explode (',', FIELDS_REQ_CONFIRM) as $confirmation_field)
  {
    $confirm_array[$confirmation_field] = ' trigger_confirm';
  }

// In cases when the customer pays product fees, then a change here will elicit a changed product
if (PAYS_PRODUCT_FEE == 'customer')
  {
    $trigger_change_array[] = 'product_fee_percent';
  }
// Validate the form data for all submissions except "Cancel"
if ($action == 'update'
    || $action == 'save'
    || $action == 'save_new')
  {
    $okay_to_post = true;
    // Convert fields so they contain only the proper data types
    $updated_product_data = prepare_user_input_for_database ($_POST);
    // Ensure all items have a selected listing auth type
    if ( $updated_product_data['listing_auth_type'] == '')
      {
        array_push ($error_array, 'Please select a listing type.');
        $warn_array['listing_auth_type'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure there is a product name
    if ( ! $updated_product_data['product_name'] )
      {
        array_push ($error_array, 'You must enter a product name to continue.');
        $warn_array['product_name'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure there is a subcategory selected
    if ( ! $updated_product_data['subcategory_id'] )
      {
        array_push ($error_array, 'Please choose a subcategory.');
        $warn_array['subcategory_id'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure inventory -- if used -- is not set to zero-pull
    if ( $updated_product_data['inventory_id'] != 0 && $updated_product_data['inventory_pull'] == 0)
      {
        array_push ($error_array, 'Please set a number of units that will be pulled from inventory for each purchase.');
        $warn_array['inventory_pull'] = ' warn';
        $warn_array['inventory_id'] = ' warn';
        $okay_to_post = false;
      }
    // If there is a unit price, then there should also be a pricing unit
    if ( $updated_product_data['unit_price'] && ! $updated_product_data['pricing_unit'])
      {
        array_push ($error_array, 'You have entered a price without a pricing unit.');
        $warn_array['unit_price'] = ' warn';
        $warn_array['pricing_unit'] = ' warn';
        $okay_to_post = false;
      }
    // There should always be an ordering unit
    if ( ! $updated_product_data['ordering_unit'])
      {
        array_push ($error_array, 'Please enter an ordering unit, sometimes the same as the pricing unit.');
        $warn_array['ordering_unit'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure random weight items have a minimum and maximum weight entered
    if ( $updated_product_data['random_weight'] && ( ! $updated_product_data['minimum_weight'] || ! $updated_product_data['maximum_weight']) )
      {
        array_push ($error_array, 'You have selected a random weight product. That means you will need to enter minimum and maximum weight.');
        $warn_array['random_weight'] = ' warn';
        if (! $updated_product_data['minimum_weight']) $warn_array['minimum_weight'] = ' warn';
        if (! $updated_product_data['maximum_weight']) $warn_array['maximum_weight'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure that random (minimum weight) is less than random (maximum weight)
    if ( $updated_product_data['random_weight'] && ( $updated_product_data['minimum_weight'] > $updated_product_data['maximum_weight']) )
      {
        array_push ($error_array, 'You have selected a random weight product but the minimum weight more than the maximum weight.');
        $warn_array['minimum_weight'] = ' warn';
        $warn_array['maximum_weight'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure items with a meat weight type are also random weight items
    if ( $updated_product_data['meat_weight_type'] && ! $updated_product_data['random_weight'] )
      {
        array_push ($error_array, 'Meat weight type is only valid for random weight items.');
        $warn_array['meat_weight_type'] = ' warn';
        $warn_array['random_weight'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure all items have a selected production type
    if ( ! $updated_product_data['production_type_id'] )
      {
        array_push ($error_array, 'Please select a production type.');
        $warn_array['production_type_id'] = ' warn';
        $okay_to_post = false;
      }
    // Ensure all items have a storage type selected
    if ( ! $updated_product_data['storage_id'] )
      {
        array_push ($error_array, 'Please select as storage type.');
        $warn_array['storage_type'] = ' warn';
        $okay_to_post = false;
      }
    // In the case where the product-version is being rejected, then we don't stop on errors
    if ( $updated_product_data['approved'] == 2 )
      {
        $product_is_rejected = true;
        $okay_to_post = true;
        $flag_changed_product = false;
        // Set all fields back the way they were
        $updated_product_data = get_product ($updated_product_data['product_id'], $updated_product_data['product_version'], '');
        // Except for the approval value, which we set to rejected (2)
        $updated_product_data['approved'] = 2;
      }
  }
// If no errors in submitted data, then prepare and POST RESULTS TO DATABASE
$data_posted = false;
$force_approval_fields = array();

if ($okay_to_post == true)
  {
    // For product conversions, see if we need to trigger confirmation and/or new version
    if ($action == 'save_new'
        || $action == 'update')
      {
        if ($updated_product_data['product_id'] == 0
            || $updated_product_data['product_version'] == 0)
          {
            debug_print ("ERROR: 759382 ", array('message'=>'Updating a product without a product_id', 'product_info'=>$updated_product_data), basename(__FILE__).' LINE '.__LINE__);
            exit (1);
          }
        // Get information for the original product from the database
        $original_product_data = get_product ($updated_product_data['product_id'], $updated_product_data['product_version'], '');
        // Before comparing, make sure the version coming from the database is handled the same as the form data
        $original_product_data = prepare_user_input_for_database (prepare_database_for_user_input ($original_product_data));
        $force_new_version = false;
        $force_approval = false;
        $flag_changed_product = false;
        foreach (array_values($product_fields_array) as $field)
          {
            // Is the field different from the database (i.e. changed)?
//          if ($updated_product_data[$field] != $original_product_data[$field])
            // Updated to allow formatting, whitespace, and case changes without forcing anything
            if (mb_strtolower (preg_replace ('/\s+/', ' ', trim (strip_tags (br2nl ($updated_product_data[$field]))))) != mb_strtolower (preg_replace ('/\s+/', ' ', trim (strip_tags (br2nl ($original_product_data[$field]))))))
              {
                if (in_array ($field, $trigger_new_version_array)
                    && $original_product_data['total_ordered_this_version'] > 0) // Only force new version if there the current version has been purchased at some time
                  {
                    // debug_print ("INFO ", array('new_version'=>$field, 'ORIGINAL: '=>$original_product_data[$field], 'UPDATED:  '=>$updated_product_data[$field]), basename(__FILE__).' LINE '.__LINE__);
                    // debug_print ("INFO ", array('new_version'=>$field, 'ORIGINAL: '=>mb_strtolower (preg_replace ('/\s+/', ' ', trim (strip_tags ($original_product_data[$field])))), 'UPDATED:  '=>mb_strtolower (preg_replace ('/\s+/', ' ', trim (strip_tags ($updated_product_data[$field]))))), basename(__FILE__).' LINE '.__LINE__);
                    $force_new_version = true;
                  }
                if (in_array ($field, explode (',', FIELDS_REQ_CONFIRM))
                    && $is_admin == false) // Only force confirmation if this is not an admin
                  {
                    // debug_print ("INFO ", array('force_confirm'=>$field, 'ORIGINAL: '=>$original_product_data[$field], 'UPDATED:  '=>$updated_product_data[$field]), basename(__FILE__).' LINE '.__LINE__);
                    array_push ($force_approval_fields, $field.' was changed from: <p style="padding-left:2em">'.$original_product_data[$field].'</p> to: <p style="padding-left:2em">'.$updated_product_data[$field].'</p>');
                    $force_approval = true;
                  }
                if (in_array ($field, $trigger_change_array))
                  {
                    // debug_print ("INFO ", array('trigger_change'=>$field, 'ORIGINAL: '=>$original_product_data[$field], 'UPDATED:  '=>$updated_product_data[$field]), basename(__FILE__).' LINE '.__LINE__);
                    $flag_changed_product = true;
                  }
              }
          }
      }
    $query_where = array();
    $query_set = array();
    if ($action == 'save'
        || $action == 'save_new')
      {
        $query_set['product_id']                    = ' product_id = (SELECT MAX(product_id) + 1 FROM '.NEW_TABLE_PRODUCTS.' product_foo)'; // Next higher product_id
        $query_set['product_version']               = ' product_version = "1"'; // New products are always version 1
        $query_set['modified']                      = ' modified = NOW()'; // Set current modification date
        $query_set['created']                       = ' created = NOW()'; // Set current creation date
        $query_set['producer']                      = ' producer_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['producer_id']).'"'; // ORIGINAL data to prevent forgery
        $query_type = 'INSERT INTO';
      }
    elseif ($action == 'update')
      {
        $query_where['product_id']                  = ' product_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['product_id']).'"';
        unset ($query_set['product_id']);
        if ($force_new_version                    // Create a new version of this product
            && $product_is_rejected == false)     // Do not insert if the product is being rejected
          {
            $query_set['product_id']                = ' product_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['product_id']).'"';
            $query_set['product_version']           = ' product_version = (SELECT MAX(product_version) + 1 FROM '.NEW_TABLE_PRODUCTS.' foo2 WHERE product_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['product_id']).'")'; // Next higher product_version
            $query_set['modified']                  = ' modified = NOW()'; // Set current creation date
            $query_set['created']                   = ' created = "'.mysqli_real_escape_string ($connection, $original_product_data['created']).'"'; // ORIGINAL data to prevent forgery
            $query_set['producer']                  = ' producer_id = "'.mysqli_real_escape_string ($connection, $original_product_data['producer_id']).'"'; // ORIGINAL data to prevent forgery
            unset ($query_where['product_id']);
            $query_type = 'INSERT INTO';
          }
        else // Update the existing version
          {
            $query_where['product_version']         = ' product_version = "'.mysqli_real_escape_string ($connection, $original_product_data['product_version']).'"'; // ORIGINAL data to prevent forgery
            unset ($query_set['product_version']);
            if ($flag_changed_product)
              {
                $query_set['modified']              = ' modified = NOW()'; // Set current creation date
              }
            else
              {
                $query_set['modified']              = ' modified = "'.mysqli_real_escape_string ($connection, $original_product_data['modified']).'"'; // ORIGINAL data to prevent forgery
              }
            $query_set['created']                   = ' created = "'.mysqli_real_escape_string ($connection, $original_product_data['created']).'"'; // ORIGINAL data to prevent forgery
            $query_where['producer']                = ' producer_id = "'.mysqli_real_escape_string ($connection, $original_product_data['producer_id']).'"'; // ORIGINAL data to prevent forgery
            $query_type = 'UPDATE';
          }
      }
    if ($force_approval == true)
      {
        $query_set['confirmed']                     = ' confirmed = "-1"';
        $query_set['approved']                      = ' approved = "0"';
        $confirmed = -1;
        $approved = 0;
      }
    else
      {
        $query_set['confirmed']                     = ' confirmed = "'.mysqli_real_escape_string ($connection, $updated_product_data['confirmed']).'"';
        $query_set['approved']                      = ' approved = "'.mysqli_real_escape_string ($connection, $updated_product_data['approved']).'"';
        $confirmed = $updated_product_data['confirmed'];
        $approved = $updated_product_data['approved'];
      }
    $query_set['active']                            = ' active = "'.mysqli_real_escape_string ($connection, $updated_product_data['active']).'"';
    $active = $updated_product_data['active'];
    // The following list could be simplified with a loop
    $query_set['product_name']                      = ' product_name = "'.mysqli_real_escape_string ($connection, $updated_product_data['product_name']).'"';
    $query_set['account_number']                    = ' account_number = "'.mysqli_real_escape_string ($connection, $updated_product_data['account_number']).'"';
    $query_set['inventory_pull']                    = ' inventory_pull = "'.mysqli_real_escape_string ($connection, $updated_product_data['inventory_pull']).'"';
    $query_set['inventory_id']                      = ' inventory_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['inventory_id']).'"';
    $query_set['product_description']               = ' product_description = "'.mysqli_real_escape_string ($connection, $updated_product_data['product_description']).'"';
    $query_set['subcategory_id']                    = ' subcategory_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['subcategory_id']).'"';
    $query_set['future_delivery']                   = ' future_delivery = "'.mysqli_real_escape_string ($connection, $updated_product_data['future_delivery']).'"';
    $query_set['future_delivery_type']              = ' future_delivery_type = "'.mysqli_real_escape_string ($connection, $updated_product_data['future_delivery_type']).'"';
    $query_set['production_type_id']                = ' production_type_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['production_type_id']).'"';
    $query_set['unit_price']                        = ' unit_price = "'.mysqli_real_escape_string ($connection, $updated_product_data['unit_price']).'"';
    $query_set['pricing_unit']                      = ' pricing_unit = "'.mysqli_real_escape_string ($connection, $updated_product_data['pricing_unit']).'"';
    $query_set['ordering_unit']                     = ' ordering_unit = "'.mysqli_real_escape_string ($connection, $updated_product_data['ordering_unit']).'"';
    $query_set['random_weight']                     = ' random_weight = "'.mysqli_real_escape_string ($connection, $updated_product_data['random_weight']).'"';
    $query_set['meat_weight_type']                  = ' meat_weight_type = "'.mysqli_real_escape_string ($connection, $updated_product_data['meat_weight_type']).'"';
    $query_set['minimum_weight']                    = ' minimum_weight = "'.mysqli_real_escape_string ($connection, $updated_product_data['minimum_weight']).'"';
    $query_set['maximum_weight']                    = ' maximum_weight = "'.mysqli_real_escape_string ($connection, $updated_product_data['maximum_weight']).'"';
    $query_set['extra_charge']                      = ' extra_charge = "'.mysqli_real_escape_string ($connection, $updated_product_data['extra_charge']).'"';
    $query_set['product_fee_percent']               = ' product_fee_percent = "'.mysqli_real_escape_string ($connection, $updated_product_data['product_fee_percent']).'"';
    $query_set['image_id']                          = ' image_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['image_id']).'"';
    $query_set['listing_auth_type']                 = ' listing_auth_type = "'.mysqli_real_escape_string ($connection, $updated_product_data['listing_auth_type']).'"';
    $query_set['taxable']                           = ' taxable = "'.mysqli_real_escape_string ($connection, $updated_product_data['taxable']).'"';
    $query_set['retail_staple']                     = ' retail_staple = "'.mysqli_real_escape_string ($connection, $updated_product_data['retail_staple']).'"';
    $query_set['staple_type']                       = ' staple_type = "'.mysqli_real_escape_string ($connection, $updated_product_data['staple_type']).'"';
    $query_set['tangible']                          = ' tangible = "'.mysqli_real_escape_string ($connection, $updated_product_data['tangible']).'"';
    $query_set['sticky']                            = ' sticky = "'.mysqli_real_escape_string ($connection, $updated_product_data['sticky']).'"';
    $query_set['hide_from_invoice']                 = ' hide_from_invoice = "'.mysqli_real_escape_string ($connection, $updated_product_data['hide_from_invoice']).'"';
    $query_set['storage_id']                        = ' storage_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['storage_id']).'"';
    if ($query_type == 'UPDATE')
      {
        $query_where['special']                     = ' LAST_INSERT_ID(pvid)';
      }
    // Now post the updated_product_data to the database
    $query = '
      '.$query_type.'
      '.NEW_TABLE_PRODUCTS.'
      SET '.
      implode (",\n", $query_set).
      (count($query_where) > 0 ? "\nWHERE\n".implode ("\n  AND ", $query_where) : '');
      $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 729223 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    // Get the new/changed pvid
    if ($query_type == 'INSERT INTO')
      {
        $new_pvid = mysqli_insert_id ($connection);
      }
    else // if ($query_type == 'UPDATE')
      {
        $query = '
          SELECT LAST_INSERT_ID() AS new_pvid';
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 875634 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
          {
            $new_pvid = $row['new_pvid'];
          }
      }
    // Get the new current product_id and product_version (regardless of whether it was an update or new product/version)
    $query = '
      SELECT
        product_id,
        product_version,
        COALESCE(email_address, email_address_2) AS email_address
      FROM '.NEW_TABLE_PRODUCTS.'
      LEFT JOIN '.TABLE_PRODUCER.' USING (producer_id)
      LEFT JOIN '.TABLE_MEMBER.' USING (member_id)
      WHERE
        pvid = "'.mysqli_real_escape_string ($connection, $new_pvid).'"';
        $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 235289 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
        {
          $updated_product_data['product_id'] = $row['product_id'];
          $updated_product_data['product_version'] = $row['product_version'];
          $producer_email = $row['email_address'];
        }
    // Following is the case where we have set the new/updated product/version to active, so we will ensure
    // that all other versions are inactive
    if ($active == 1)
      {
        $query = '
          UPDATE '.NEW_TABLE_PRODUCTS.'
          SET active = 0
          WHERE
            product_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['product_id']).'"
            AND product_version != "'.mysqli_real_escape_string ($connection, $updated_product_data['product_version']).'"
            AND producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
        $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 730032 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    // See if there are any active versions of this product (just for information purposes)
    $query = '
      SELECT COUNT(product_version) AS number_active
      FROM '.NEW_TABLE_PRODUCTS.'
      WHERE
        product_id = "'.$updated_product_data['product_id'].'"
        AND active = "1"';
        $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 730032 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
        {
          $number_active = $row['number_active'];
        }
    // See if we might need to do inventory maintenance
    if ($updated_product_data['inventory_id'] != $original_product_data['inventory_id'])
      {
        // General Maintenance: Remove all orphaned inventory buckets from the database
        // since changing inventory_id might have left a bucket without a product
        $query = '
          DELETE FROM '.TABLE_INVENTORY.'
          WHERE
            (
              SELECT COUNT(pvid)
              FROM '.NEW_TABLE_PRODUCTS.'
              WHERE '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
            ) = 0
            AND producer_id = "'.mysqli_real_escape_string ($connection, $updated_product_data['producer_id']).'"';
        $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 802741 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    // Display a completion box while the screen closes
    $edit_product_info = '
      <div class="completion_message">
        <h3>Product has been updated</h3>'.
        ($new_pvid != $original_product_data['pvid'] ? '
          <div>Added as a new product/version</div>' : '').
        ((int)$approved == 0 ? '
          <div>Product will not be available until approved. A notification email will be sent</div>' : '').
        ($number_active == 0 ? '
          <div>NOTE: There are currently no active versions of product #'.$updated_product_data['product_id'].'</div>' : '').
        ($product_is_rejected == true ? '
          <div>Product is Rejected</div>' : '').'
      </div>';
    // First get the product display (just in case we need it)
    // context_options_array is needed to make SSL connection (even with invalid certificate) to load product for HTML email display
    $context_options_array = array (
      'ssl'=>array (
        'verify_peer'=>false,
        'verify_peer_name'=>false,
        ),
      );  
    $html_product_display = file_get_contents (BASE_URL.PATH.'product_list.php?type=producer_list&sort_type=email_product&select_type=email_product&product_id='.$updated_product_data['product_id'].'&product_version='.$updated_product_data['product_version'], false, stream_context_create ($context_options_array));
    // Now figure out whether to send an email
    $send_email = false;
    // Case where product is unapproved (notify admin)
    if ((int)$approved == 0)
      {
        $send_email = true;
        // What happened? NEW or CHANGED ???
        if ($action == 'save' || $action == 'save_new') $context = 'New' or $context = 'Changed';
        $send_email_subject = '['.ORGANIZATION_ABBR.'] '.$context.' product '.$updated_product_data['product_id'].'-'.$updated_product_data['product_version'].' needs approval';
        $send_email_to = EMAIL_PRODUCT_APPROVAL;
        $send_email_bcc = '';
        // URL for editing this product...
        $edit_product_page = BASE_URL.PATH.'edit_product_info.php?product_id='.$updated_product_data['product_id'].'&product_version='.$updated_product_data['product_version'].'&producer_id='.$updated_product_data['producer_id'].'&action=edit';
        $html_body = '
          <h3><a href="'.$edit_product_page.'">Edit this product for approval</a></h3>'.
          $html_product_display.(count ($force_approval_fields) > 0 ? '
          <h3>Approval was triggered because:</h3><ul><li>'.implode ('</li><li>', $force_approval_fields).'</li><ul>'
          : $edit_product_info);
        $text_body = "Edit this product for approval at: $edit_product_page\n\n".
          print_r($updated_product_data, true).(count ($force_approval_fields) > 0 ?
          "\n\nApproval was triggered because:\n".strip_tags (implode ("\n\n", $force_approval_fields))
          : $edit_product_info);
      }
    // Case where product went from unapproved (or rejected) to approved (notify producer)
    if ($approved == 1 && $original_product_data['approved'] != 1)
      {
        $send_email = true;
        // What was the change? FROM UNAPPROVED or FROM REJECTED ???
        if ($original_product_data['approved'] == 0) $context = 'unapproved' or $context = 'rejected';
        $send_email_subject = '['.ORGANIZATION_ABBR.'] Previously '.$context.' product '.$updated_product_data['product_id'].'-'.$updated_product_data['product_version'].' has been approved';
        $send_email_to = $producer_email;
        $send_email_bcc = EMAIL_PRODUCT_APPROVAL;
        $html_body = '
          <h3>The following product has been approved for listing</h3>'.
          $html_product_display;
        $text_body = "The following product has been approved for listing\n\n".
          print_r($updated_product_data, true);
      }
    // Case where product becomes rejected (notify producer)
    if ($approved == 2 && $original_product_data['approved'] != 2)
      {
        $send_email = true;
        $send_email_subject = '['.ORGANIZATION_ABBR.'] Product '.$updated_product_data['product_id'].'-'.$updated_product_data['product_version'].' was rejected for approval';
        $send_email_to = $producer_email;
        $send_email_bcc = EMAIL_PRODUCT_APPROVAL;
        // URL for editing this product...
        $edit_product_page = BASE_URL.PATH.'edit_product_info.php?product_id='.$updated_product_data['product_id'].'&product_version='.$updated_product_data['product_version'].'&producer_id='.$updated_product_data['producer_id'].'&action=edit';
        $html_body = '
          <h3>The product '.$updated_product_data['product_id'].'-'.$updated_product_data['product_version'].' was rejected for listing</h3>
          <p><strong>REASON:</strong>'.(strlen ($_POST['rejection_note']) > 0 ? $_POST['rejection_note'] : 'None provided').'</p>
          <p>You might want to <a href="'.$edit_product_page.'">edit the product and try again</a>.</p>'.
          $html_product_display;
        $text_body = 'The product '.$updated_product_data['product_id'].'-'.$updated_product_data['product_version']." was been rejected for listing\n\n".
          "REASON: ".$_POST['rejection_note']."\n\n".
          "If you would like to edit the product and try again, follow this link: ".$edit_product_page."\n\n".
          print_r($updated_product_data, true);
      }
    // Send an email to admin that there is a product needing approval
    if ($send_email == true)
      {
        $send_email_return = send_email (array (
          'reason' => 'Notification of changed product',
          'subject' => $send_email_subject,
          'to' => $send_email_to,
          'bcc' => $send_email_bcc,
          'self' => '', // Email for the active member
          'priority' => '2', // 1=highest .. 5=lowest
          'html_body' => $html_body,
          'text_body' => $text_body,
          ));
      }
    $modal_action = 'parent.reload_parent(5000)';
    $data_posted = true;
  } // END: POST RESULTS TO DATABASE

// If the data was not posted, then we need to give the producer a chance to edit it again
if (($action == 'update'            // For posting edited products
    || $action == 'save'            // For posting new products
    || $action == 'save_new')  // For posting edited products as new
    && $data_posted == false)
  {
    // Send back the sanitized version of data
    $product_data = prepare_database_for_user_input ($updated_product_data);
  }
elseif ($action == 'add')           // For opening a new product to add
  {
    $product_data = array(); // Empty array
    // Initial default settings
    $product_data['confirmed'] = -1;
    $product_data['active'] = 0;
    $product_data['approved'] = 0;
    $product_data['listing_auth_type'] = 'unlisted';
    $product_data['tangible'] = '1';
    $product_data['inventory_name'] = '';
    $product_data['inventory_pull'] = '1';
    $product_data['extra_charge'] = '0.00';
    $product_data['random_weight'] = '0';
    $product_data['minimum_weight'] = '';
    $product_data['maximum_weight'] = '';
    $product_data['meat_weight_type'] = '';
    $product_data['production_type_id'] = '';
    $product_data['storage_id'] = '';
    $product_data['producer_id'] = $producer_id;
  }
elseif ($action == 'cancel')        // Cancel editing this product
  {
    $modal_action = 'parent.reload_parent(0)';
  }
elseif ($action == 'edit')          // For opening an existing product to edit
  {
    // Get the product information if we have product_id/product_version
    if (isset ($_REQUEST['product_id']) &&
        isset ($_REQUEST['product_version']))
      {
        $product_data = prepare_database_for_user_input (get_product ($_GET['product_id'], $_GET['product_version'], ''));
      }
    else
      {
        debug_print ("ERROR: 758921 ", array('message'=>'Edit requested, but no product_id/product_version provided.', 'GET'=>$_GET), basename(__FILE__).' LINE '.__LINE__);
        array_push ($error_array, 'Product was not found. However you may add a new product.');
        // Provide a blank form (Add Product) instead
        $product_data = array(); // Empty array
        $action = 'add';
      }
    // If we got product_info and it isn't for producer_id_you, then UNAUTHORIZED
    if ($product_data['producer_id'] != $producer_id)
      {
        die(debug_print ("ERROR: 367634 ", array('message'=>'Product requested is not associated with this producer.', 'GET'=>$_GET, 'SESSION[producer_id_you]'=>$_SESSION['producer_id_you'], 'product_info'=>$product_data), basename(__FILE__).' LINE '.__LINE__));
      }
  }
else
  {
    array_push ($error_array, 'Unknown action was requested. However you may add a new product.');
    // Provide a blank form (Add Product) instead
    $product_data = array(); // Empty array
    $action = 'add';
    $alert_type = 'notice';
    $alert_message = '';
  }

// At this point, we have either posted the product to the database or we
// Have product_data to edit in the product form
if ($data_posted != true)
  {
    // Assemble any errors for display
    $error_message = display_alert('error', 'Please correct the following problems and resubmit.', $error_array);
    // Begin product editing form

    // Get the category/subcategory list and create a drop-down list
    $query = '
      SELECT
        subcategory_id,
        subcategory_name,
        category_id,
        category_name,
        subcategory_fee_percent
      FROM '.TABLE_SUBCATEGORY.'
      LEFT JOIN '.TABLE_CATEGORY.' USING(category_id)
      WHERE
        '.TABLE_SUBCATEGORY.'.category_id = '.TABLE_CATEGORY.'.category_id
      ORDER BY
        category_name ASC,
        subcategory_name ASC';
    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 906537 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $subcategory_options = '
      <option class="category-0 subcategory-0" value="">Select Subcategory</option>';
    while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        $option_select = '';
        // Set the option that currently-set option
        if ($row['subcategory_id'] == $product_data['subcategory_id'])
          {
            $option_select = ' selected';
            $product_data['subcat_adjust_fee'] = $row['subcategory_fee_percent'];
          }
        // Check for category changes and do a new optgroup
        if ($row['category_id'] != $prior_category_id)
          {
            // For all but the first group, close the prior optgroup
            if ($prior_category_id != '')
              {
                $subcategory_options .= '
                  </optgroup>';
              }
            $subcategory_options .= '
              <optgroup class="category-'.$row['category_id'].'" label="'.$row['category_name'].'">';
          }
        $subcategory_options .= '
          <option class="category-'.$row['category_id'].' subcategory-'.$row['subcategory_id'].'" value="'.$row['subcategory_id'].'"'.($row['subcategory_id'] == $product_data['subcategory_id'] ? ' selected' : '').'>'.$row['subcategory_name'].'</option>';
        $prior_category_id = $row['category_id'];
      }
    $subcategory_options .= '
      </optgroup>';

    // Get the producer markup
    $query = '
      SELECT
        producer_fee_percent
      FROM '.TABLE_PRODUCER.'
      WHERE
        producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 029213 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        $product_data['producer_adjust_fee'] = $row['producer_fee_percent'];
      }

    // Get this producer's list of inventory options and create a drop-down list
    $query = '
      SELECT
        inventory_id,
        description AS inventory_name
      FROM
        '.TABLE_INVENTORY.'
      WHERE
        producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"
      ORDER BY
        description';
    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 649509 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        $inventory_select_list .= '
          <option class="inventory_option" value="'.$row['inventory_name'].'"'.($product_data['inventory_id'] == $row['inventory_id'] ? ' selected' : '').'>';
        if ($product_data['inventory_id'] == $row['inventory_id'])
          {
            // Add inventory_name as a special-case value to product_data array
            $product_data['inventory_name'] = $row['inventory_name'];
          }
      }

    // Get the list of local accounts from the chart of accounts and create a drop-down list
    if (CurrentMember::auth_type('producer_admin,site_admin,cashier'))
      {
        $query = '
          SELECT
            account_id,
            account_number,
            description
          FROM '.NEW_TABLE_ACCOUNTS.'
          WHERE 1
          ORDER BY description';
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 099564 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $account_number_options = '
            <option value=""'.($product_data['account_number'] == "" ? ' selected' : '').'>NONE &ndash; treat as a regular sale</option>';
        while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
          {
            $account_number_options .= '
            <option value="'.$row['account_id'].'"'.($product_data['account_number'] == $row['account_id'] ? ' selected' : '').'>'.$row['description'].'</option>';
          }
      }

    // Generate the product_types_options and create a drop-down list
    $production_types_options = '
          <option value="">Choose One</option>';
    $help_array['production_type_id'] .= '
        <dl>';
    $query = '
      SELECT *
      FROM '.TABLE_PRODUCT_TYPES.'
      ORDER BY prodtype';
    $result =  @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 947534 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        $production_types_options .= '
          <option value="'.$row['production_type_id'].'"'.($product_data['production_type_id'] == $row['production_type_id'] ? ' selected' : '').'>'.$row['prodtype'].'</option>';
        $help_array['production_type_id'] .= '
          <dt>'.$row['prodtype'].'</dt>
          <dd>'.$row['proddesc'].'</dd>';
      }
    $help_array['production_type_id'] .= '
        </dl>';

    // Generate the storage_types_options and create a drop-down list
    $storage_types_options = '
      <option value="">Choose One</option>';
    $query = '
      SELECT
        storage_id,
        storage_type
      FROM '.TABLE_PRODUCT_STORAGE_TYPES.'
      ORDER BY storage_type';
    $result =  @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 616609 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        $storage_types_options .= '
          <option value="'.$row['storage_id'].'"'.($product_data['storage_id'] == $row['storage_id'] ? ' selected' : '').'>'.$row['storage_type'].'</option>';
      }

    // Generate the meat_weight_type and create a drop-down list
    $meat_weight_types_options = '
      <option value="">NONE</option>';
    foreach (array('LIVE', 'DRESSED/HANGING', 'PROCESSED') as $meat_weight_type)
      {
        $meat_weight_types_options .= '
          <option value="'.$meat_weight_type.'"'.($product_data['meat_weight_type'] == $meat_weight_type ? ' selected' : '').'>'.$meat_weight_type.'</option>';
      }

    // Begin main display for edit-product screen
    $edit_product_info = display_alert($alert_type, $alert_message, $error_array).'
      <form name="edit_product_info" id="edit_product_info" method="post" action="'.$_SERVER['SCRIPT_NAME'].'?display_as=popup">
        <div class="form_buttons">'.
          // Save is for new products only
          ($action == 'add'
            || $action == 'save' ?
          '
          <button type="submit" name="action" id="action_save" value="save">Save</button>'
          // Save new and update are useful only when editing an existing product
          : '
          <button type="submit" name="action" id="action_save_new" value="save_new">Save as New</button>
          <button type="submit" name="action" id="action_update" value="update">Update</button>'
          ).'
          <button type="submit" name="action" id="action_cancel" value="cancel">Cancel</button>
          <button type="reset" name="reset" id="reset" value="reset">Reset</button>
        </div>
        <fieldset class="product_info grouping_block">
          <legend>Current Product '.($product_data['product_id'] > 0 ? $product_data['product_id'].'-'.$product_data['product_version'] : '[NEW]').'</legend>
          <input type="hidden" id="pvid" name="pvid" value="'.$product_data['pvid'].'">
          <input type="hidden" id="product_id" name="product_id" value="'.$product_data['product_id'].'">
          <input type="hidden" id="product_version" name="product_version" value="'.$product_data['product_version'].'">
          <input type="hidden" id="producer_id" name="producer_id" value="'.$product_data['producer_id'].'">
          <input type="hidden" id="image_id" name="image_id" value="'.$product_data['image_id'].'">
          <input type="hidden" id="created" name="created" value="'.$product_data['created'].'">
          <input type="hidden" id="modified" name="modified" value="'.$product_data['modified'].'">
          <input type="hidden" name="referrer" value="'.$referrer.'">

          <div class="explanation">
            NOTE: Changes to any fields in <span class="trigger_confirm">this color</span> will require the new product/version/change to be re-approved.
          </div>'.
          ($original_product_data['total_ordered_this_version'] == 0 ? '
          <div class="explanation">
            This product-version has never been purchased, so all changes will be applied directly <strong>without</strong> forcing a new version.
          </div>'
          : '').'
          <div class="help_link">'.format_help_link ('confirmed').'Activate &amp; Approve</a></div>
          <div class="input_group confirmed">
            <div class="input_block active">
              <input type="radio" id="active-1" class="radio_pushbutton'.$confirm_array['active'].'" name="active" value="1"'.($product_data['active'] == 1 ? ' checked' : '').' autocomplete="off"'.($product_data['active'] == -1 && $is_admin == false ? ' disabled' : '').'>
              <label for="active-1" class="active'.$warn_array['active'].'">Active<span class="detail">'.($product_data['approved'] == 0 ? 'Not ' : '').'Approved</span></label>
            </div>
            <div class="input_block active">
              <input type="radio" id="active-2" class="radio_pushbutton'.$confirm_array['active'].'" name="active" value="0"'.($product_data['active'] == 0 ? ' checked' : '').' autocomplete="off"'.($product_data['active'] == -1 && $is_admin == false ? ' disabled' : '').'>
              <label for="active-2" class="active'.$warn_array['active'].'">Inactive<span class="detail">'.($product_data['approved'] == 0 ? 'Not ' : '').'Approved</span></label>
            </div><br class="clear"/>'.
            ($is_admin == true ? '
            <div class="input_block approved">
              <input type="radio" id="approved-1" class="radio_pushbutton'.$confirm_array['approved'].'" name="approved" value="1"'.($product_data['approved'] == 1 ? ' checked' : '').' autocomplete="off" onchange="check_rejection();">
              <label for="approved-1" class="approved'.$warn_array['approved'].'">Approved<span class="detail">Okay to Sell</span></label>
            </div>
            <div class="input_block approved">
              <input type="radio" id="approved-2" class="radio_pushbutton'.$confirm_array['approved'].'" name="approved" value="0"'.($product_data['approved'] == 0 ? ' checked' : '').' autocomplete="off" onchange="check_rejection();">
              <label for="approved-2" class="approved'.$warn_array['approved'].'">Not Approved<span class="detail">Waiting Admin</span></label>
            </div>
            <div class="input_block approved">
              <input type="radio" id="approved-3" class="radio_pushbutton'.$confirm_array['approved'].'" name="approved" value="2"'.($product_data['approved'] == 2 ? ' checked' : '').' autocomplete="off" onchange="check_rejection();">
              <label for="approved-3" class="approved'.$warn_array['approved'].'">Rejected<span class="detail">Unacceptable</span></label>
            </div>
            <div class="input_block rejection_note" id="rejection_note_block">
              <input type="text" id="rejection_note" name="rejection_note" value="'.$_POST['rejection_note'].'" maxlength="150" autocomplete="off">
              <label for="rejection_note" class="rejection_note">Rejection Note</label>
              <div class="explanation">
                Rejection Note will be sent with a notification email to the producer. It will not be stored. Maximum length is 150 characters.
              </div>
            </div>'
            : ''
            ).'
            <div class="explanation">
              New products and products with significant changes will require approval by a producer administrator
              before they will be available for customers to purchase. Use the "Active" button to select this particular
              product version as the one that will be displayed to customers for purchase (if/when it is approved).
            </div>
          </div>

          <div class="help_link">'.format_help_link ('listing_auth_type').'Listing</a></div>
          <div class="input_group listing_auth_type">
            <div class="input_block listing_auth_type">
              <input type="radio" id="listing_auth_type-member" class="radio_pushbutton'.$confirm_array['listing_auth_type'].'" name="listing_auth_type" value="member"'.($product_data['listing_auth_type'] == 'member' ? ' checked' : '').' autocomplete="off">
              <label for="listing_auth_type-member" class="listing_auth_type'.$warn_array['listing_auth_type'].'">Retail to Members</label>
            </div>
            <div class="input_block listing_auth_type">
              <input type="radio" id="listing_auth_type-institution" class="radio_pushbutton'.$confirm_array['listing_auth_type'].'" name="listing_auth_type" value="institution"'.($product_data['listing_auth_type'] == 'institution' ? ' checked' : '').' autocomplete="off">
              <label for="listing_auth_type-institution" class="listing_auth_type'.$warn_array['listing_auth_type'].'">Wholesale to Institutions</label>
            </div>
            <div class="input_block listing_auth_type">
              <input type="radio" id="listing_auth_type-unlisted" class="radio_pushbutton'.$confirm_array['listing_auth_type'].'" name="listing_auth_type" value="unlisted"'.($product_data['listing_auth_type'] == 'unlisted' ? ' checked' : '').' autocomplete="off">
              <label for="listing_auth_type-unlisted" class="listing_auth_type'.$warn_array['listing_auth_type'].'">Unlisted</label>
            </div>
            <div class="input_block listing_auth_type">
              <input type="radio" id="listing_auth_type-archived" class="radio_pushbutton'.$confirm_array['listing_auth_type'].'" name="listing_auth_type" value="archived"'.($product_data['listing_auth_type'] == 'archived' ? ' checked' : '').' autocomplete="off">
              <label for="listing_auth_type-archived" class="listing_auth_type'.$warn_array['listing_auth_type'].'">Archived</label>
            </div>
            <div class="explanation">
              Select to whom this product will be sold. In most cases, institutions will also have access
              to retail sales. Unlisted and Archived products will not be available for sale.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('tangible').'Tangible</a></div>
          <div class="input_group tangible">
            <div class="input_block tangible">
              <input type="checkbox" id="tangible" class="'.$confirm_array['tangible'].'" name="tangible" value="1"'.($product_data['tangible'] == 1 ? ' checked' : '').' autocomplete="off">
              <label for="tangible" class="tangible'.$warn_array['tangible'].'">This is a tangible product</label>
            </div>
            <div class="explanation">
              Keep checked if this product needs physical pickup/delivery. Some products (like reservations) do not
              have any handling requirement and should not be considered <em>tangible</em>.  Only tangible items are
              included on labels.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('product_name').'Name</a></div>
          <div class="input_group product_name">
            <div class="input_block product_name">
              <input type="text" id="product_name" class="'.$confirm_array['product_name'].'" name="product_name" value="'.$product_data['product_name'].'" maxlength="75" autocomplete="off">
              <label for="product_name" class="product_name'.$warn_array['product_name'].'">Product Name</label>
            </div>
            <div class="explanation">
              Maximum length is 75 characters.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('product_description').'Description</a></div>
          <div class="input_group product_description">
            <div class="input_block product_description">
              <textarea id="product_description" class="'.$confirm_array['product_description'].'" name="product_description" cols="60" rows="7">'.$product_data['product_description'].'</textarea>
              <label for="product_description" class="product_description'.$warn_array['product_description'].'">Product Description</label>
            </div>
            <div class="explanation">
              This is an optional field, but recommended.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('subcategory_id').'Subcategory</a></div>
          <div class="input_group subcategory_id">
            <div class="input_block subcategory_id">
              <select id="subcategory_id" class="'.$confirm_array['subcategory_id'].'" name="subcategory_id">'.
                $subcategory_options.'
              </select>
              <label for="subcategory_options" class="subcategory_id'.$warn_array['subcategory_id'].'">Subcategory</label>
            </div>
            <div class="explanation">
              Choose the most appropriate subcategory for this product.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('inventory_pull').'Inventory</a></div>
          <div class="input_group inventory">
            <div class="input_block inventory_name">
              <input type="text" id="inventory_name" class="'.$confirm_array['inventory_id'].'" name="inventory_name" list="inventory_select_list" value="'.$product_data['inventory_name'].'" autocomplete="off">
              <label for="inventory_name" class="inventory_name'.$warn_array['inventory_id'].'">Inventory name</label>
              <datalist id="inventory_select_list">'.
                $inventory_select_list.'
              </datalist>
            </div>
            <div class="input_block inventory_pull">
              <input type="text" id="inventory_pull" class="'.$confirm_array['inventory_pull'].'" name="inventory_pull" value="'.$product_data['inventory_pull'].'" autocomplete="off">
              <label for="inventory_pull" class="inventory_pull'.$warn_array['inventory_pull'].'">Inventory pull (quantity)</label>
            </div>
            <div class="explanation">
              <em>Inventory</em> is a name to use for inventory units. Customers will never see this name.
              Leave both these fields blank if you prefer not to use inventory for this product. A product
              might be called <em>&ldquo;Happy Chicken Jumbo Brown Eggs&rdquo;</em> but the inventory name
              could just be <em>&ldquo;Brown Eggs&rdquo;</em> and used for multiple products that pull from
              the same <em> egg</em> supply. Use the same inventory name for multiple products, as needed.
            </div>
            <div class="explanation">
              <em>Inventory Pull</em> is the number of inventory units that will be <em>pulled</em>
              every time a customer orders <em>one</em> item. If, for example, an egg inventory is measured in
              the number of eggs available, then a <em>one dozen egg product</em> would have an inventory
              pull of 12. For many products, this value will usually be 1. This field is <em>not</em> for
              setting the number of items you currently have in inventory.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('unit_price').'Price</a></div>
          <div class="input_group pricing">
            <div class="input_block unit_price">
              <input type="text" id="unit_price" class="'.$confirm_array['unit_price'].'" name="unit_price" value="'.$product_data['unit_price'].'" autocomplete="off">
              <label for="unit_price" class="unit_price'.$warn_array['unit_price'].'">Unit price (e.g. 5.00)</label>
            </div>
            <div class="input_block pricing_unit">
              <input type="text" id="pricing_unit" class="'.$confirm_array['pricing_unit'].'" name="pricing_unit" value="'.$product_data['pricing_unit'].'" autocomplete="off">
              <label for="pricing_unit" class="pricing_unit'.$warn_array['pricing_unit'].'">Pricing unit (e.g. pound)</label>
            </div>
            <div class="input_block ordering_unit">
              <input type="text" id="ordering_unit" class="'.$confirm_array['ordering_unit'].'" name="ordering_unit" value="'.$product_data['ordering_unit'].'" autocomplete="off">
              <label for="ordering_unit" class="ordering_unit'.$warn_array['ordering_unit'].'">Ordering unit (e.g. steak)</label>
            </div>
            <div class="explanation">
              Enter the base price that will be used when calculating cost. Depending on how
              '.ORGANIZATION_ABBR.' is configured, some fees might be charged against the producer while
              others are charged to the customer. In all cases, please use singular forms for units: pound
              (not pounds), gallon (not gallons), loaf (not loaves), jar (not jars), ox (not oxen), etc. The
              terms will be pluralized when appropriate.
            </div>
            <div class="explanation">
              In the example (shown above the fields), a customer would order some number of <em>steaks</em>
              at <em>$5.00/pound</em>. For clarity of display, pricing units may no longer contain numbers
              (e.g. 1/2 pound), however you can still use words (e.g. half-pound).
            </div>
          </div>

          <div class="help_link">'.format_help_link ('extra_charge').'Extra Charge</a></div>
          <div class="input_group extra_charge">
            <div class="input_block extra_charge">
              <input type="text" id="extra_charge" class="'.$confirm_array['extra_charge'].'" name="extra_charge" value="'.$product_data['extra_charge'].'" autocomplete="off">
              <label for="extra_charge" class="extra_charge'.$warn_array['extra_charge'].'">Extra Charge</label>
            </div>
            <div class="explanation">
              The <em>Extra Charge</em> amount is not subject to fees or taxes, so it should only be used
              in cases where those are not appropriate &ndash; such as for charges and refunds for reservations.
            </div>
          </div>

          <div class="input_group random_weight">
            <div class="help_link">'.format_help_link ('random_weight').'Random Weight</a></div>
            <div class="input_block random_weight">
              <input type="radio" id="random_weight-1" class="radio_pushbutton'.$confirm_array['random_weight'].'" name="random_weight" value="1"'.($product_data['random_weight'] == 1 ? ' checked' : '').' onClick=\'$("#weight").removeClass("hidden");\' autocomplete="off">
              <label for="random_weight-1" class="random_weight'.$warn_array['random_weight'].'">Yes</label>
            </div>
            <div class="input_block random_weight">
              <input type="radio" id="random_weight-0" class="radio_pushbutton'.$confirm_array['random_weight'].'" name="random_weight" value="0"'.($product_data['random_weight'] == 0 ? ' checked' : '').' onClick=\'$("#weight").addClass("hidden");\' autocomplete="off">
              <label for="random_weight-0" class="random_weight'.$warn_array['random_weight'].'">No</label>
            </div>
            <div class="explanation">
              Choosing &ldquo;Yes&rdquo; for this option means that the producer will need to enter a weight on the
              invoice to determine price. This is often the case for foods like meat and cheese. In this case, the
              <em>Pricing Unit</em> above should be a unit of weight, although the<em>Ordering Unit</em> might still
              be something different.
            </div>
          </div>

          <div id="weight" class="input_group weight'.($product_data['random_weight'] == 0 ? ' hidden' : '').'">
            <div class="help_link">'.format_help_link ('minimum_weight').'Weight Range</a></div>
            <div class="input_block minimum_weight">
              <input type="text" id="minimum_weight" class="'.$confirm_array['minimum_weight'].'" name="minimum_weight" value="'.$product_data['minimum_weight'].'" autocomplete="off">
              <label for="minimum_weight" class="minimum_weight'.$warn_array['minimum_weight'].'">Minimum weight</label>
            </div>
            <div class="input_block maximum_weight">
              <input type="text" id="maximum_weight" class="'.$confirm_array['maximum_weight'].'" name="maximum_weight" value="'.$product_data['maximum_weight'].'" autocomplete="off">
              <label for="maximum_weight" class="maximum_weight'.$warn_array['maximum_weight'].'">Maximum Weight</label>
            </div>
            <div class="input_block meat_weight_type">
              <select id="meat_weight_type" class="'.$confirm_array['meat_weight_type'].'" name="meat_weight_type">'.
                $meat_weight_types_options.'
              </select>
              <label for="subcategory_options" class="meat_weight_type'.$warn_array['meat_weight_type'].'">Meat weight type</label>
            </div>
            <div class="explanation">
              Products selected as <em>Random Weight</em> items must have a minimum and maximum weight assigned.
              The final product weight, when entered for the customer invoice, <em>must</em> lie within this range.
            </div>
            <div class="explanation">
              Meat weight type is only valid for random weight items.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('production_type_id').'Production Type</a></div>
          <div class="input_group production_type_id">
            <div class="input_block production_type_id">
              <select id="production_type_id" class="'.$confirm_array['production_type_id'].'" name="production_type_id">'.
                $production_types_options.'
              </select>
              <label for="subcategory_options" class="production_type_id'.$warn_array['production_type_id'].'">Production type</label>
            </div>
            <div class="explanation">
              <em>Production Type</em> refers to the manner in which food is raised &ndash; such as
              organic, natural, etc. If the correct production type is not available in the list, please
              contact '.ORGANIZATION_ABBR.' to see about having it added. Definitions for selecting from
              these options follows:'.
              $help_array['production_type_id'].'
            </div>
          </div>

          <div class="help_link">'.format_help_link ('storage_id').'Storage</a></div>
          <div class="input_group storage_id">
            <div class="input_block storage_id">
              <select id="storage_id" class="'.$confirm_array['storage_id'].'" name="storage_id">'.
                $storage_types_options.'
              </select>
              <label for="subcategory_options" class="storage_id'.$warn_array['storage_id'].'">Storage type</label>
            </div>
            <div class="explanation">
              Indicate how this product will be shipped and how it should be stored.
            </div>
          </div>
        </fieldset>'.
      // Begin the admin portion of the form
      ($is_admin == true ? // Show form for admin users
        '
        <fieldset class="admin_info grouping_block">
          <legend>Administrative Options</legend>

          <div class="help_link">'.format_help_link ('account_number').'Account</a></div>
          <div class="input_group account_number">
            <div class="input_block account_number">
              <select id="account_number" class="'.$confirm_array['account_number'].'" name="account_number">'.
                $account_number_options.'
              </select>
              <label for="account_number_select" class="account_number'.$warn_array['account_number'].'">Account number</label>
            </div>
            <div class="explanation">
              (UNTESTED) This would not normally be used for regular member-producers. Rather than being applied
              to the respective producer account, proceeds from this transaction will be sent to some other
              &quot;internal&quot; account. This might be useful for things like membership &quot;products&quot;.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('retail_staple').'Food Stamps</a></div>
          <div class="input_group retail_staple">
            <div class="input_block retail_staple">
              <input type="radio" id="retail_staple-1" class="radio_pushbutton'.$confirm_array['retail_staple'].'" name="retail_staple" value="2"'.($product_data['retail_staple'] == 2 ? ' checked' : '').' autocomplete="off">
              <label for="retail_staple-1" class="retail_staple'.$warn_array['retail_staple'].'">Retail food item<span class="detail">Not a staple</span></label>
            </div>
            <div class="input_block retail_staple">
              <input type="radio" id="retail_staple-2" class="radio_pushbutton'.$confirm_array['retail_staple'].'" name="retail_staple" value="3"'.($product_data['retail_staple'] == 3 ? ' checked' : '').' autocomplete="off">
              <label for="retail_staple-2" class="retail_staple'.$warn_array['retail_staple'].'">Retail food item<span class="detail">Staple item</span></label>
            </div>
            <div class="input_block retail_staple">
              <input type="radio" id="retail_staple-3" class="radio_pushbutton'.$confirm_array['retail_staple'].'" name="retail_staple" value="1"'.($product_data['retail_staple'] == 1 ? ' checked' : '').' autocomplete="off">
              <label for="retail_staple-3" class="retail_staple'.$warn_array['retail_staple'].'">Non-food item</label>
            </div>
            <div class="explanation">
              (UNTESTED) This is an unimplemented feature to allow assigning products to food-stamp categories.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('sticky').'Sticky</a></div>
          <div class="input_group sticky">
            <div class="input_block sticky">
              <input type="checkbox" id="sticky" class="'.$confirm_array['sticky'].'" name="sticky" value="1"'.($product_data['sticky'] == 1 ? ' checked' : '').' autocomplete="off">
              <label for="sticky" class="sticky'.$warn_array['sticky'].'">Sticky in customer baskets</label>
            </div>
            <div class="explanation">
              (UNTESTED) If checked, only admins can alter this product after it is in a customer basket. Possibly
              useful for placing things like membership dues into customer baskets.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('hide_from_invoice').'Hide</a></div>
          <div class="input_group hide_from_invoice">
            <div class="input_block hide_from_invoice">
              <input type="checkbox" id="hide_from_invoice" class="'.$confirm_array['hide_from_invoice'].'" name="hide_from_invoice" value="1"'.($product_data['hide_from_invoice'] == 1 ? ' checked' : '').' autocomplete="off">
              <label for="hide_from_invoice" class="hide_from_invoice'.$warn_array['hide_from_invoice'].'">Hide item from display on customer invoices</label>
            </div>
            <div class="explanation">
              (UNTESTED) If checked, this product will be hidden on invoice printouts for customers. It can be
              used for intangible <em>non-product items</em> like voting. This option may be deprecated in
              future versions of the software.
            </div>
          </div>

          <div class="help_link">'.format_help_link ('product_fee_percent').'Markup</a></div>
          <div class="input_group product_fee_percent">
            <div class="input_block product_fee_percent">
              <input type="text" id="product_fee_percent" class="'.$confirm_array['product_fee_percent'].'" name="product_fee_percent" value="'.$product_data['product_fee_percent'].'" autocomplete="off">
              <label for="product_fee_percent" class="product_fee_percent'.$warn_array['product_fee_percent'].'">% Product markup</label>
            </div>
            <div class="input_block producer_adjust_fee">
              <input type="text" id="producer_adjust_fee" name="producer_adjust_fee" value="'.$product_data['producer_adjust_fee'].'" autocomplete="off" disabled>
              <label for="producer_adjust_fee" class="producer_adjust_fee'.$warn_array['producer_adjust_fee'].'" disabled>% Producer markup</label>
            </div>
            <div class="input_block subcat_adjust_fee">
              <input type="text" id="subcat_adjust_fee" name="subcat_adjust_fee" value="'.$product_data['subcat_adjust_fee'].'" autocomplete="off" disabled>
              <label for="subcat_adjust_fee" class="subcat_adjust_fee'.$warn_array['subcat_adjust_fee'].'" disabled>% Subcategory markup</label>
            </div>
            <div class="explanation">
              These indicate the markup amounts applied to products. Only Product Markup can be changed in this
              form. If a product is switched to a different subcategory, then the Subcategory Markup might also
              change. Producer Markup can be changed on the Edit Producer page from Member Admin. Subcategory
              Markup can be changed from a link from the Edit Categories &amp; Subcategories page.
            </div>
          </div>
        </fieldset>'
      : // Form to retain values for for non-admin users
        '
        <input type="hidden" id="approved" name="approved" value="'.$product_data['approved'].'">
        <input type="hidden" id="account_number" name="account_number" value="'.$product_data['account_number'].'">
        <input type="hidden" id="retail_staple" name="retail_staple" value="'.$product_data['retail_staple'].'">
        <input type="hidden" id="sticky" name="sticky" value="'.$product_data['sticky'].'">
        <input type="hidden" id="hide_from_invoice" name="hide_from_invoice" value="'.$product_data['hide_from_invoice'].'">
        <input type="hidden" id="product_fee_percent" name="product_fee_percent" value="'.$product_data['product_fee_percent'].'">'
      ). // This completes the admin portion of the form
        '
      </form>';
    // Fields not provided for:
    //    future_delivery         not implemented
    //    future_delivery_type    not implemented
    //    taxable                 handled with sub/category taxsettings
  }

function prepare_database_for_user_input ($product_data)
  {
    $product_data['product_name'] = htmlspecialchars ($product_data['product_name'], ENT_QUOTES);
    $product_data['product_description'] = htmlspecialchars (trim (br2nl ($product_data['product_description'])), ENT_QUOTES);
    $product_data['ordering_unit'] = htmlspecialchars ($product_data['ordering_unit'], ENT_QUOTES);
    $product_data['pricing_unit'] = htmlspecialchars ($product_data['pricing_unit'], ENT_QUOTES);
    $product_data['unit_price'] = number_format ((double)$product_data['unit_price'], 3);
    $product_data['extra_charge'] = number_format ((double)$product_data['extra_charge'], 2);
    // We could query the database and set inventory_name now, but will wait and do that at the same time
    // we prepare the form information.
    return ($product_data);
  }

function prepare_user_input_for_database ($product_data)
  {
    global $connection;
    // Sanitize the data
    $product_data['pvid']                   = preg_replace("/[^0-9]/",'',$product_data['pvid']);
    $product_data['product_id']             = preg_replace("/[^0-9]/",'',$product_data['product_id']);
    $product_data['producer_id']            = preg_replace("/[^0-9]/",'',$product_data['producer_id']);
    $product_data['product_version']        = preg_replace("/[^0-9]/",'',$product_data['product_version']);
    $product_data['product_name']           = strip_tags (htmlspecialchars_decode($product_data['product_name'], ENT_QUOTES));
    $product_data['account_number']         = preg_replace("/[^0-9]/",'',$product_data['account_number']);
    $product_data['pricing_unit']           = strip_tags (htmlspecialchars_decode($product_data['pricing_unit'], ENT_QUOTES));
    $product_data['ordering_unit']          = strip_tags (htmlspecialchars_decode($product_data['ordering_unit'], ENT_QUOTES));
    $product_data['product_description']    = nl2br2 (strip_tags (ltrim(rtrim (htmlspecialchars_decode($product_data['product_description'], ENT_QUOTES))), '<b><i><u><strong><em>'));
    $product_data['unit_price']             = preg_replace("/[^0-9\.\-]/",'',$product_data['unit_price']);
    $product_data['extra_charge']           = preg_replace("/[^0-9\.\-]/",'',$product_data['extra_charge']);
    $product_data['product_fee_percent']    = preg_replace("/[^0-9\.\-]/",'',$product_data['product_fee_percent']);
    $product_data['random_weight']          = preg_replace("/[^0-9]/",'',$product_data['random_weight']);
    $product_data['minimum_weight']         = preg_replace("/[^0-9\.\/]/",'',$product_data['minimum_weight'] + 0);
    $product_data['maximum_weight']         = preg_replace("/[^0-9\.\/]/",'',$product_data['maximum_weight'] + 0);
    $product_data['meat_weight_type']       = $product_data['meat_weight_type'];
    if ($product_data['random_weight'] == 0)
      {
        $product_data['minimum_weight'] = '';   // Enforce no value for non-random weight items
        $product_data['maximum_weight'] = '';   // Enforce no value for non-random weight items
        $product_data['meat_weight_type'] = ''; // Enforce no value for non-random weight items
      }
    $product_data['inventory_name']         = strip_tags ($product_data['inventory_name']);
    // Since we only receive inventory_name from the form, we will use that to get the inventory_id
    // Of course, this requires that the inventory_name (description) be unique for the producer
    // which was not previously required (2017-03-25 ROYG).
    if (strlen ($product_data['inventory_name']) > 0)
      {
        $query = '
          SELECT inventory_id
          FROM '.TABLE_INVENTORY.'
          WHERE
            description = "'.mysqli_real_escape_string ($connection, $product_data['inventory_name']).'"
            AND producer_id = "'.mysqli_real_escape_string ($connection, $product_data['producer_id']).'"
          LIMIT 1';
        $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 784023 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
          {
            // This inventory bucket is aleady in the database
            $product_data['inventory_id'] = $row['inventory_id'];
          }
        else
          {
            // 1. Add new inventory bucket to the database
            $query = '
              INSERT INTO
                '.TABLE_INVENTORY.'
              SET
                description = "'.mysqli_real_escape_string ($connection, $product_data['inventory_name']).'",
                producer_id = "'.mysqli_real_escape_string ($connection, $product_data['producer_id']).'"';
              // ... and use the new inventory_id that was added
              $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 752131 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
              $product_data['inventory_id'] = mysqli_insert_id ($connection);
          }
      }
    $product_data['inventory_id']           = preg_replace("/[^0-9\-]/",'',$product_data['inventory_id']);
    $product_data['inventory_pull']         = preg_replace("/[^0-9]/",'',$product_data['inventory_pull']);
    $product_data['subcategory_id']         = preg_replace("/[^0-9]/",'',$product_data['subcategory_id']);
    $product_data['production_type_id']     = preg_replace("/[^0-9]/",'',$product_data['production_type_id']);
    $product_data['listing_auth_type']      = preg_replace("/[^member|^institution|^unlisted|^archived|^unfi]/",'archived',$product_data['listing_auth_type']);
    $product_data['tangible']               = ($product_data['tangible'] ? '1' : '0');
    $product_data['sticky']                 = ($product_data['sticky'] == 1 ? '1' : '0');
    $product_data['hide_from_invoice']      = ($product_data['hide_from_invoice'] ? '1' : '0');
    $product_data['confirmed']              = ($product_data['confirmed'] ? '1' : '0');
    $product_data['storage_id']             = preg_replace("/[^0-9]/",'',$product_data['storage_id']);
    $product_data['retail_staple']          = preg_replace("/[^0-9]/",'','0'.$product_data['retail_staple']);
    $product_data['staple_type']            = preg_replace("/[^A-Za-z0-9]/",'',$product_data['staple_type']);   // Validation might not be correct
    $product_data['future_delivery']        = preg_replace("/[^0-9]/",'','0'.$product_data['future_delivery']); // Validation might not be correct
    $product_data['future_delivery_type']   = $product_data['future_delivery_type'];                            // Validation might not be correct
    $product_data['image_id']               = preg_replace("/[^0-9]/",'','0'.$product_data['image_id']);
    $product_data['created']                = date('Y-m-d H:i:s', strtotime ($product_data['created']));
    $product_data['modified']               = date('Y-m-d H:i:s', strtotime ($product_data['modified']));
    return ($product_data);
  }

// This function is used to highlight validation problems and link to the help page
function format_help_link ($target)
  {
    $help_link = '
    <a class="help_link" href="help.php#'.$target.'" onclick="popup=window.open(\'help.php#'.$target.'\', \'popupPage\', \'height=300,width=400,left=400,scrollbars=yes,resizeable=no\'); return false" target="help_window">';
    return $help_link;
  }

$page_specific_javascript = '
  // Show the rejection_note block when the reject radio button is selected
  function check_rejection() {
    if (jQuery("#approved-3").is(":checked")) {
      jQuery("#rejection_note_block").slideDown();
      }
    else {
      jQuery("#rejection_note_block").slideUp();
      }
    };';

$page_specific_css = '
  #edit_product_info {
    text-align:left;
    box-sizing:border-box;
    }
  #edit_product_info fieldset {
    margin:2rem 1rem 1rem 7rem;
    }
  #edit_product_info div.help_link {
    display:block;
    clear:both;
    height:2.5rem;
    border-top:1px solid #888;
    margin-bottom:1rem;
    cursor:default;
    }
  #edit_product_info div.help_link a {
    display:inline-block;
    padding:0.25rem 0.75rem;
    border-radius:5rem;
    background-color:#897;
    margin:0.5rem;
    color:#fff;
    cursor:help;
    }

  #edit_product_info span.trigger_confirm,
  #edit_product_info textarea.trigger_confirm,
  #edit_product_info input[type="radio"].trigger_confirm + label,
  #edit_product_info input[type="checkbox"].trigger_confirm + label,
  #edit_product_info select.trigger_confirm,
  #edit_product_info input.trigger_confirm {
    color:#008;
    }
  #rejection_note_block {
    display:'.($product_data['approved'] == '2' ? '' : 'none').';
    }
  #edit_product_info .explanation {
    display:block;
    clear:both;
    font-size:80%;
    margin:0.5rem 0 1rem;
    }
  #edit_product_info .admin_info legend,
  #edit_product_info .admin_info {
    background-color:#fff8f0;
    }
  #edit_product_info .product_info legend,
  #edit_product_info .product_info {
    background-color:#f0f8ff;
    }
  #edit_product_info .input_group {
    }
  #edit_product_info .input_block {
    float:left;
    margin:1rem 0.5rem;
    }
  #edit_product_info .input_block.rejection_note,
  #edit_product_info .input_block.account_number,
  #edit_product_info .input_block.product_name,
  #edit_product_info .input_block.production_type_id,
  #edit_product_info .input_block.inventory_name,
  #edit_product_info .input_block.subcategory_id {
    width:30rem;
    max-width:100%;
    }
  #edit_product_info dl {
    margin:-1rem 1rem 0;
    padding:0;
    }
  #edit_product_info dt {
    display:inline;
    text-decoration:none;
    font-style:normal;
    font-weight:bold;
    font-size:100%;
    }
  #edit_product_info dt:before {
    content:"\A\2022\2008";
    white-space:pre;
    }
  #edit_product_info dt:after {
    content:": ";
    }
  #edit_product_info dd {
    display:inline;
    text-decoration:none;
    font-style:normal;
    font-weight:normal;
    font-size:100%;
    margin-left:1em;
    }

  #edit_product_info #weight *{
    opacity:1;
    transition:all 1s;
    }
  #edit_product_info #weight.hidden * {
    opacity:0;
  /*font-size:0;*/
    height:0;
    margin-top:0;
    margin-bottom:0;
    padding-top:0;
    padding-bottom:0;
    border-top:0;
    border-bottom:0;
    box-shadow:0;
    transition:all ease 1s;
    }
  #edit_product_info #weight.hidden {
    border-top:0;
    margin:0;
    padding:0;
    transition:all ease 1s;
    }
  #edit_product_info textarea,
  #edit_product_info textarea + label,
  #edit_product_info select,
  #edit_product_info select + label,
  #edit_product_info input[type=text],
  #edit_product_info input[type=text] + label {
    display:block;
    position:relative;
    }
  #edit_product_info textarea {
    top:1.5rem;
    height:10rem;
    }
  #edit_product_info textarea + label {
    bottom:9.5rem;
    }
  #edit_product_info select,
  #edit_product_info input[type=text] {
    top:1.5rem;
    height:2.5rem;
    }
  #edit_product_info select + label,
  #edit_product_info input[type=text] + label {
    bottom:2.3rem;
    }
  #edit_product_info input[type=checkbox] + label {
    display:inline;
    font-size:100%;
    margin:0 2rem 0 0.5rem;
    }
  .explanation em {
    color:#000;
    }
  #edit_product_info label.warn {
    color:#800;
    }';

include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$edit_product_info.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
