<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

include_once ('func.update_basket_item.php');
include_once ('func.get_account_info.php');

////////////////////////////////////////////////////////////////////////////////////
//                                                                                //
// This part of the program will handle updates to basket items.                  //
//                                                                                //
// REQUIRED arguments:               transaction_id=[transaction_id]              //
// OPTIONAL arguments:                         bpid=[basket product id]           //
//                                                                                //
////////////////////////////////////////////////////////////////////////////////////

// Update basket information
if ($_REQUEST['action'] == 'update_basket_info')
  {
    // $new_basket_info['reason'] = 'admin-basket-updated'; // pass this through to the transactions
    $new_basket_info['transaction_group_id'] = 'admin-basket-updated'; // pass this through to the transactions
    $new_basket_info['quantity'] = $_REQUEST['quantity'];
    $new_basket_info['weight'] = $_REQUEST['weight'];
    $new_basket_info['out_of_stock'] = $_REQUEST['out_of_stock'];
    $new_basket_info['product_id'] = $_REQUEST['product_id'];
    $new_basket_info['product_version'] = $_REQUEST['product_version'];
    $new_basket_info['delivery_id'] = $_REQUEST['delivery_id'];
    $new_basket_info['member_id'] = $_REQUEST['member_id'];
    // Set the customer message to producer
    $new_basket_info['messages'] = array('customer notes to producer' => $_REQUEST['message']);
    $new_basket_info['action'] = 'set_everything';
    $response .= update_basket_item ($new_basket_info);
//     // Set the weight
//     $new_basket_info['action'] = 'set_weight';
//     $response .= update_basket_item ($new_basket_info);
//     // Set the basket quantity
//     $new_basket_info['action'] = 'set_quantity';
//     $response .= update_basket_item ($new_basket_info);
//     // Set the basket outs
//     $new_basket_info['action'] = 'set_outs';
//     $response .= update_basket_item ($new_basket_info);
    echo $response;
    exit (0);
  }


////////////////////////////////////////////////////////////////////////////////////
//                                                                                //
// This part of the program will handle updates to ledger entries.                //
//                                                                                //
// REQUIRED arguments:               transaction_id=[transaction_id]              //
// OPTIONAL arguments:                         bpid=[basket product id]           //
//                                                                                //
////////////////////////////////////////////////////////////////////////////////////

// Update basket information
if ($_REQUEST['action'] == 'update_ledger_info')
  {
    // Begin by getting information on the current ledger entry
    // since we will keep some of that information forward
    $query_old_ledger_info = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      WHERE transaction_id = "'.$_REQUEST['transaction_id'].'"';
    $result_old_ledger_info = mysql_query($query_old_ledger_info, $connection) or die(debug_print ("ERROR: 754892 ", array ($query_old_ledger_info,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if (! $row_old_ledger_info = mysql_fetch_array($result_old_ledger_info))
      {
        die(debug_print ("ERROR: 730893 ", 'No ledger entry was found.', basename(__FILE__).' LINE '.__LINE__));
      }
    if ($_REQUEST['add_modify'] == "modify")
      {
        // Now put together the changed array for the update_ledger function
        $new_ledger_info['transaction_id'] = $_REQUEST['transaction_id'];
        // $new_ledger_info['reason'] = 'admin-adjusted';
        $new_ledger_info['transaction_group_id'] = 'admin-adjusted';
        $new_ledger_info['source_type'] = $_REQUEST['source_type'];
        $new_ledger_info['source_key'] = $_REQUEST['source_key'];
        $new_ledger_info['target_type'] = $_REQUEST['target_type'];
        $new_ledger_info['target_key'] = $_REQUEST['target_key'];
        $new_ledger_info['amount'] = $_REQUEST['amount'];
        $new_ledger_info['text_key'] = $_REQUEST['text_key'];
        // ID for the logged-in member
        $new_ledger_info['posted_by'] = $_SESSION['member_id'];
        // Keep all this stuff the same as before
        $new_ledger_info['basket_id'] = $row_old_ledger_info['basket_id'];
        $new_ledger_info['bpid'] = $row_old_ledger_info['bpid'];
        $new_ledger_info['site_id'] = $row_old_ledger_info['site_id'];
        $new_ledger_info['delivery_id'] = $row_old_ledger_info['delivery_id'];
        $new_ledger_info['pvid'] = $row_old_ledger_info['pvid'];
        // We are targeting a specific transaction_id
        $new_ledger_info['match_keys'] = array ('transaction_id');
        // Create the messages array
        $messages['ledger comment'] = $_REQUEST['message'];
        $new_ledger_info['messages'] = $messages;
        // Post the updated transaction
        $affected_transaction_id = basket_item_to_ledger ($new_ledger_info);
      }
    else
      {
        // Now put together the changed array for the update_ledger function
        $new_ledger_info['transaction_id'] = $_REQUEST['transaction_id'];
        // $new_ledger_info['reason'] = 'admin-added-adjustment';
        $new_ledger_info['transaction_group_id'] = 'admin-added-adjustment';
        $new_ledger_info['source_type'] = $_REQUEST['source_type'];
        $new_ledger_info['source_key'] = $_REQUEST['source_key'];
        $new_ledger_info['target_type'] = $_REQUEST['target_type'];
        $new_ledger_info['target_key'] = $_REQUEST['target_key'];
        $new_ledger_info['amount'] = $_REQUEST['amount'];
        $new_ledger_info['text_key'] = $_REQUEST['text_key'];
        // ID for the logged-in member
        $new_ledger_info['posted_by'] = $_SESSION['member_id'];
        // Keep all this stuff the same as before
        $new_ledger_info['basket_id'] = $row_old_ledger_info['basket_id'];
        $new_ledger_info['bpid'] = $row_old_ledger_info['bpid'];
        $new_ledger_info['site_id'] = $row_old_ledger_info['site_id'];
        $new_ledger_info['delivery_id'] = $row_old_ledger_info['delivery_id'];
        $new_ledger_info['pvid'] = $row_old_ledger_info['pvid'];
        // We are targeting a specific transaction_id
//        $new_ledger_info['match_keys'] = array ('transaction_id');
        // Create the messages array
        $messages['ledger comment'] = $_REQUEST['message'];
        $new_ledger_info['messages'] = $messages;
        // Post the updated transaction
        $affected_transaction_id = add_transaction ($new_ledger_info);
        $response .= add_transaction_messages ($affected_transaction_id, $new_ledger_info['messages']);
      }
    echo $response;
    exit (0);
  }


////////////////////////////////////////////////////////////////////////////////////
//                                                                                //
// This is the main ajax call to get ledger information for display of            //
// the adjustment form controls. It will also get display basket information,     //
// if there is one and handle updates from the form.                              //
//                                                                                //
// REQUIRED arguments:               transaction_id=[transaction_id]              //
// OPTIONAL arguments:                         bpid=[basket product id]           //
//                                                                                //
////////////////////////////////////////////////////////////////////////////////////

// Get the adjustment dialogue for changing, deleting, or adding adjustments
if ($_REQUEST['action'] == 'get_adjustment_dialog')
  {
    $show_basket_form = false;
    // Get information from the ledger
    $query_ledger = '
      SELECT *
      FROM
        '.NEW_TABLE_LEDGER.'
        LEFT JOIN '.NEW_TABLE_MESSAGES.' ON (referenced_key1 = transaction_id)
        LEFT JOIN '.NEW_TABLE_MESSAGE_TYPES.' USING(message_type_id)
      WHERE
        transaction_id = "'.mysql_real_escape_string($_REQUEST['transaction_id']).'"
        AND (
          key1_target = "ledger.transaction_id"
          OR key1_target IS NULL)
      LIMIT 1';
    $result_ledger = mysql_query($query_ledger, $connection) or die(debug_print ("ERROR: 893021 ", array ($query_ledger,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if (! $row_ledger = mysql_fetch_array($result_ledger))
      {
        return ("error 102: unexpected result");
      }
    // Get the name for each of the accounts
    $row_ledger['source_name'] = get_account_name ($row_ledger['source_type'], $row_ledger['source_key']);
    $row_ledger['target_name'] = get_account_name ($row_ledger['target_type'], $row_ledger['target_key']);
    // Get information from the basket (if there is one)
    if ($_REQUEST['bpid'] > 0)
      {
        $show_basket_form = true;
        $query_basket_items = '
          SELECT
            '.NEW_TABLE_BASKET_ITEMS.'.*,
            '.NEW_TABLE_BASKETS.'.delivery_id,
            '.NEW_TABLE_BASKETS.'.member_id,
            '.NEW_TABLE_PRODUCTS.'.product_name,
            '.NEW_TABLE_PRODUCTS.'.random_weight,
            '.NEW_TABLE_MESSAGES.'.message
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
          LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
          LEFT JOIN '.NEW_TABLE_MESSAGES.' ON (referenced_key1 = bpid)
          LEFT JOIN '.NEW_TABLE_MESSAGE_TYPES.' USING(message_type_id)
          WHERE
            bpid = "'.mysql_real_escape_string($_REQUEST['bpid']).'"
            AND (
              key1_target = "basket_items.bpid"
              OR key1_target IS NULL)
          LIMIT 1';
        $result_basket_items = mysql_query($query_basket_items, $connection) or die(debug_print ("ERROR: 763074 ", array ($query_basket_items,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if (! $row_basket_items = mysql_fetch_array($result_basket_items))
          {
            return ("error 102: unexpected result");
          }
      }
    $response .= '
    <table id="editor" class="editor">
      <tr class="ledger_header">
        <th colspan="4">MODIFY OR ADD A TRANSACTION<div class="close_icon" onclick="close_editor()">X</div></th>
      </tr>
      <tr class="editor">
        <td width="70" class="scope">
          TID<br>'.$row_ledger['transaction_id'].'<br>
        </td>
        <td width="70">
          <fieldset>
            <label class="text_label" for="edit_amount">Amount</label>
            <input type="text" class="amount" size="9" name="edit_amount" id="edit_amount" value="'.number_format($row_ledger['amount'], 2).'">
          </fieldset>
        </td>
        <td width="350">
          <fieldset>
            <label class="text_label" for="edit_source_spec">Source spec.</label>
            <input class="secondary" type="text" name="edit_source_spec" id="edit_source_spec" value="'.$row_ledger['source_type'].':'.$row_ledger['source_key'].'">
          </fieldset>
          <fieldset>
            <label class="text_label" for="ad_hoc_source">Source acct (start typing to change...)</label>
            <input type="text" name="ad_hoc_source" id="ad_hoc_source" autocomplete="off" value="'.$row_ledger['source_name'].'" />
          </fieldset>
        </td>
        <td width="350">
          <fieldset>
            <label class="text_label" for="edit_target_spec">Target spec.</label>
            <input class="secondary" type="text"name="edit_target_spec" id="edit_target_spec" value="'.$row_ledger['target_type'].':'.$row_ledger['target_key'].'">
          </fieldset>
          <fieldset>
            <label class="text_label" for="ad_hoc_target">Target acct (start typing to change...)</label>
            <input type="text" name="ad_hoc_target" id="ad_hoc_target" autocomplete="off" value="'.$row_ledger['target_name'].'" />
          </fieldset>
        </td>
      </tr>
      <tr>
        <td>
          '.$row_ledger['transaction_group_id'].'
          <input type="hidden" name="transaction_group_id" id="edit_transaction_group_id" value="adjustment">
        </td>
        <td colspan="2">
          <input type="button" name="" value="Modify entry" onclick="add_modify_ledger_info('.$row_ledger['transaction_id'].',\'modify\')">
          <input type="button" name="" value="Add as new" onclick="add_modify_ledger_info('.$row_ledger['transaction_id'].',\'add\')">
        </td>
        <td>
          <input type="hidden" name="text_key" id="edit_text_key" value="'.$row_ledger['text_key'].'">
          <input type="hidden" name="transaction_group_id" id="transaction_group_id" value="'.$row_ledger['transaction_group_id'].'">
            <fieldset>
              <label class="text_label" for="edit_ledger_message">Transaction comment</label>
              <input type="text" name="edit_ledger_message" id="edit_ledger_message" value="'.htmlspecialchars($row_ledger['message'], ENT_QUOTES).'">
            </fieldset>
        </td>
      </tr>';
    if ($show_basket_form)
      {
        $response .= '
          <tr class="ledger_header">
            <th colspan="4">MODIFY THE BASKET ITEM</th>
          </tr>
          <tr class="editor">
            <td class="scope">BPID<br>'.$row_basket_items['bpid'].'</td>
            <td colspan="2">'.substr($row_basket_items['product_name'], 0, 50).(strlen($row_basket_items['product_name']) > 50 ? '...' : '').'</td>
            <td rowspan="2">
              <fieldset>
                <label class="text_label" for="edit_basket_message">Customer message to producer</label>
                <textarea name="edit_basket_message" id="edit_basket_message">'.htmlspecialchars($row_basket_items['message'], ENT_QUOTES).'</textarea>
              </fieldset>
              <input type="hidden" name="edit_product_id" id="edit_product_id" value="'.$row_basket_items['product_id'].'">
              <input type="hidden" name="edit_product_version" id="edit_product_version" value="'.$row_basket_items['product_version'].'">
              <input type="hidden" name="edit_delivery_id" id="edit_delivery_id" value="'.$row_basket_items['delivery_id'].'">
              <input type="hidden" name="edit_member_id" id="edit_member_id" value="'.$row_basket_items['member_id'].'">
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <input type="button" name="" value="Modify basket" onclick="update_basket_info('.$row_basket_items['basket_id'].')">
            </td>
            <td>
              <fieldset>
                <label class="text_label" for="edit_quantity">Quantity</label>
                <input type="text" size="8" name="edit_quantity" id="edit_quantity" value="'.$row_basket_items['quantity'].'" />
                '.$row_basket_items['ordering_unit'].'
              </fieldset>
              <fieldset class="pad_left">
                <label class="text_label" for="edit_out_of_stock">Out of stock</label>
                <input type="text" size="8" name="edit_out_of_stock" id="edit_out_of_stock" value="'.$row_basket_items['out_of_stock'].'" />
                '.$row_basket_items['ordering_unit'].'
              </fieldset>'.
            ($row_basket_items['random_weight'] ? '
              <fieldset class="pad_left">
                <label class="text_label" for="edit_total_weight">Total weight</label>
              <input type="text" size="8" name="edit_total_weight" id="edit_total_weight" value="'.$row_basket_items['total_weight'].'" />
              '.$row_basket_items['pricing_unit'].'
              </fieldset>'
              : '<input type="hidden" name="edit_total_weight" id="edit_total_weight" value="'.$row_basket_items['total_weight'].'">').'
            </td>
          </tr>';
      }
    $response .= '
        </table>';
    echo $response;
    exit (0);
  }
?>