<?php
include_once 'config_openfood.php';
session_start();
valid_auth('cashier');

// How was this script called?
if (isset ($_REQUEST['type'])) $type = $_REQUEST['type'];
if (isset ($_REQUEST['target'])) $target = $_REQUEST['target'];
if (isset ($_REQUEST['method'])) $method = $_REQUEST['method'];

if ($type == 'reserve_transaction_group_id')
  {
    // Reserve a group adjustment value using the transaction_group_enum table (similar to an auto-increment)
    $query = '
      INSERT INTO
        '.NEW_TABLE_ADJUSTMENT_GROUP_ENUM.'
      VALUES (NULL)';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 752930 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $inserted_row = mysql_insert_id();
    // Return the row to the ajax query
    echo $inserted_row;
  }

if ($type == 'single')
  {
    // Get the target transaction
    $transaction_data = get_transaction($target);
    // Check if this transaction replaced another
    $replaced_data = get_replaced($transaction_data['transaction_id']);
    if ($replaced_data['replaced_by'] == $transaction_data['transaction_id'])
      $transaction_data['replaces_id'] = $replaced_data['transaction_id'];
    // Get the row to display
    $row_markup = get_transaction_row ($transaction_data);
    // Also get transactions grouped with this one (same group_id but not this transaction)
    $query = '
      SELECT
        '.NEW_TABLE_LEDGER.'.transaction_id
      FROM
        '.NEW_TABLE_LEDGER.'
      WHERE
        '.NEW_TABLE_LEDGER.'.transaction_group_id = "'.mysql_real_escape_string($transaction_data['transaction_group_id']).'"
        AND '.NEW_TABLE_LEDGER.'.transaction_group_id != ""
        AND '.NEW_TABLE_LEDGER.'.transaction_id != "'.mysql_real_escape_string($target).'"';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 752930 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array ($result))
      {
        $transaction_id = $row['transaction_id'];
        // Get the target transaction
        $transaction_data = get_transaction($transaction_id);
        // Check if this transaction replaced another
        $replaced_data = get_replaced($transaction_data['transaction_id']);
        if ($replaced_data['replaced_by'] == $transaction_data['transaction_id'])
          $transaction_data['replaces_id'] = $replaced_data['transaction_id'];
        // Get the row to display
        $row_markup .= get_transaction_row ($transaction_data);
      }
    $heading_markup = get_heading_row ();
    if ($method != 'ajax')
      {
        display_page_header ();
        echo '
          <table id="adjust">'.
            $heading_markup.'
            <tr class="data_entry adjustment_memo">
              <td colspan="10">
                <input type="hidden" name="transaction_group_id" id="transaction_group_id" value="" form="edit_dialog">
                <label for="adjustment_message">Group adjustment memo:</label>
                <input type="text" name="adjustment_message" id="adjustment_message" value="" form="edit_dialog" onblur="reserve_transaction_group_id()">
              </td>
            </tr>'.
            $row_markup.'
          </table>';
        display_page_footer ();
      }
    elseif ($method == 'ajax')
      {
        echo $row_markup;
      }
  }

if ($type == 'product')
  {
    // Get all the transactions for this product (bpid)
    $query = '
      SELECT
        '.NEW_TABLE_LEDGER.'.transaction_id
      FROM
        '.NEW_TABLE_LEDGER.'
      WHERE
        '.NEW_TABLE_LEDGER.'.bpid = "'.mysql_real_escape_string($target).'"
        AND '.NEW_TABLE_LEDGER.'.replaced_by IS NULL';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 752930 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $row_markup = '';
    while ($row = mysql_fetch_array ($result))
      {
        $transaction_id = $row['transaction_id'];
        // Get the target transaction
        $transaction_data = get_transaction($transaction_id);
        // Check if this transaction replaced another
        $replaced_data = get_replaced($transaction_data['transaction_id']);
        if ($replaced_data['replaced_by'] == $transaction_data['transaction_id'])
          $transaction_data['replaces_id'] = $replaced_data['transaction_id'];
        // Get the row to display
        $row_markup .= get_transaction_row ($transaction_data);
      }
    $heading_markup = get_heading_row ();
    if ($method != 'ajax')
      {
        display_page_header ();
        echo '
          <table id="adjust">'.
            $heading_markup.'
            <tr class="data_entry adjustment_memo">
              <td colspan="10">
                <input type="hidden" name="transaction_group_id" id="transaction_group_id" value="" form="edit_dialog">
                <label for="adjustment_message">Group adjustment memo:</label>
                <input type="text" name="adjustment_message" id="adjustment_message" value="" form="edit_dialog" onblur="reserve_transaction_group_id()">
              </td>
            </tr>'.
            $row_markup.'
          </table>';
        display_page_footer ();
      }
    elseif ($method == 'ajax')
      {
        echo $row_markup;
      }
  }

// Send the edit dialog for this transaction
if ($type == 'edit')
  {
    // Get current information about the transaction
    $transaction_data = get_transaction($target);
    // If the transaction is replaced, then abort and do not allow editing
    if ($transaction_data['replaced_by'] != '') exit (1);
    // Display the edit dialogue
    $display = '
        <tr id="edit_dialog_'.$transaction_data['transaction_id'].'" class="data_entry">
          <td colspan="10">
            <form class="edit_dialog" name="edit_dialog">
              <fieldset class="left">
                <legend>Required Accounting Info.</legend>
                <label for="source_key_'.$transaction_data['transaction_id'].'">From:</label>
                <select name="source_type" id="source_type_'.$transaction_data['transaction_id'].'">
                  <option value="member"'.($transaction_data['source_type'] == 'member' ? ' selected' : '').'>member</option>
                  <option value="producer"'.($transaction_data['source_type'] == 'producer' ? ' selected' : '').'>producer</option>
                  <option value="internal"'.($transaction_data['source_type'] == 'internal' ? ' selected' : '').'>internal</option>
                  <option value="tax"'.($transaction_data['source_type'] == 'tax' ? ' selected' : '').'>tax</option>
                </select>
                <input type="text" class="regular_input" name="source_key" id="source_key_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['source_key'].'" placeholder="'.$transaction_data['source_key'].'">
                <br>
                <label for="target_key_'.$transaction_data['transaction_id'].'">To:</label>
                <select name="target_type" id="target_type_'.$transaction_data['transaction_id'].'">
                  <option value="member"'.($transaction_data['target_type'] == 'member' ? ' selected' : '').'>member</option>
                  <option value="producer"'.($transaction_data['target_type'] == 'producer' ? ' selected' : '').'>producer</option>
                  <option value="internal"'.($transaction_data['target_type'] == 'internal' ? ' selected' : '').'>internal</option>
                  <option value="tax"'.($transaction_data['target_type'] == 'tax' ? ' selected' : '').'>tax</option>
                </select>
                <input type="text" class="regular_input" name="target_key" id="target_key_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['target_key'].'" placeholder="'.$transaction_data['target_key'].'">
                <br>
                <label for="amount_'.$transaction_data['transaction_id'].'">Amount:</label>
                <input type="text" class="regular_input" name="amount" id="amount_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['amount'].'" placeholder="'.$transaction_data['amount'].'">
                <br>
                <label for="text_key">Text Key:</label>
                <input type="text" class="regular_input" name="text_key" id="text_key_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['text_key'].'" placeholder="'.$transaction_data['text_key'].'" list="text_key_list" required>
                <datalist id="text_key_list">
                  '.get_text_key_list().'
                </datalist>
                <br>
                <label for="effective_datetime_'.$transaction_data['transaction_id'].'">Effective Date/Time:</label>
                <input type="text" class="regular_input" name="effective_datetime" id="effective_datetime_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['effective_datetime'].'">
              </fieldset>
              <fieldset class="right">
                <legend>Optional Shopping/Basket Info.</legend>
                <label for="basket_id_'.$transaction_data['transaction_id'].'">Basket ID:</label>
                <input type="text" class="regular_input" name="basket_id" id="basket_id_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['basket_id'].'" placeholder="'.$transaction_data['basket_id'].'">
                <br>
                <label for="bpid_'.$transaction_data['transaction_id'].'">Basket/Product ID:</label>
                <input type="text" class="regular_input" name="bpid" id="bpid_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['bpid'].'" placeholder="'.$transaction_data['bpid'].'">
                <br>
                <label for="site_id_'.$transaction_data['transaction_id'].'">Site ID:</label>
                <input type="text" class="regular_input" name="site_id" id="site_id_'.$transaction_data['transaction_id'].'" list="site_list" value="'.$transaction_data['site_id'].'" placeholder="'.$transaction_data['site_id'].'">
                <datalist id="site_list">
                  '.get_site_list().'
                </datalist>
                <br>
                <label for="delivery_id_'.$transaction_data['transaction_id'].'">Delivery ID:</label>
                <input type="text" class="regular_input" name="delivery_id" id="delivery_id_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['delivery_id'].'" placeholder="'.$transaction_data['delivery_id'].'">
                <br>
                <label for="pvid_'.$transaction_data['transaction_id'].'">Product/Version ID:</label>
                <input type="text" class="regular_input" name="pvid" id="pvid_'.$transaction_data['transaction_id'].'" value="'.$transaction_data['pvid'].'" placeholder="'.$transaction_data['pvid'].'">
              </fieldset>
              <fieldset class="right clearboth">
                <label for="zero_split'.$transaction_data['transaction_id'].'">Zero and Split?</label>
                <input type="checkbox" name="zero_split" value="true" id="zero_split_'.$transaction_data['transaction_id'].'">
              </fieldset>
              <div><input class="edit_button" type="button" onclick="update_transaction('.$transaction_data['transaction_id'].')" value="UPDATE"></div>
              <div><input class="edit_button" type="button" onclick="cancel_edit_dialog('.$transaction_data['transaction_id'].')" value="Cancel"></div>
              <div><input class="edit_button" type="reset" value="Reset"></div>
            </form>
          </td>
         </tr>';
    echo $display;
  }

// Update the transaction
if ($type == 'update')
  {
// debug_print ("WARN: X-1-X ", $_REQUEST, basename(__FILE__).' LINE '.__LINE__);
    // First get the existing ledger information
    $original_transaction_data = get_transaction($target);
    // Get the posted (update) information from the form submission
    $original_transaction_id = $target;
    $new_source_type = $_REQUEST['source_type'];
    $new_source_key = $_REQUEST['source_key'];
    $new_target_type = $_REQUEST['target_type'];
    $new_target_key = $_REQUEST['target_key'];
    $new_amount = $_REQUEST['amount'];
    $new_text_key = $_REQUEST['text_key'];
    $new_effective_datetime = $_REQUEST['effective_datetime'];
    $new_basket_id = $_REQUEST['basket_id'];
    $new_bpid = $_REQUEST['bpid'];
    $new_site_id = $_REQUEST['site_id'];
    $new_delivery_id = $_REQUEST['delivery_id'];
    $new_pvid = $_REQUEST['pvid'];
    $new_transaction_group_id = $_REQUEST['transaction_group_id'];
    $new_adjustment_message = $_REQUEST['adjustment_message'];
    $zero_split = $_REQUEST['zero_split'];
    if (strlen ($new_adjustment_message) > 0)
      {
        // Do not use the regular transaction messaging process that goes with add_to_ledger
        $original_transaction_data['transaction_group_id'] = $transaction_group_id;
        // $adjustment_message_array['adjustment group memo'] = $new_adjustment_message;
        $query = '
          REPLACE INTO '.NEW_TABLE_MESSAGES.'
          SET
            message = "'.mysql_real_escape_string($new_adjustment_message).'",
            message_type_id = 
              COALESCE((
                SELECT message_type_id
                FROM '.NEW_TABLE_MESSAGE_TYPES.'
                WHERE description = "'.mysql_real_escape_string('adjustment group memo').'"
                LIMIT 1
                ),0),
            referenced_key1 = "'.mysql_real_escape_string($new_transaction_group_id).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 759302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $original_transaction_data['transaction_group_id'] = $new_transaction_group_id;
      }
    include_once ('func.update_ledger.php');
    // If zero_split is true, then we will first post a zeroing transaction before
    // Updating (splitting) the remaining one.
    if ($zero_split == 'true')
      {
// debug_print ("WARN: X-2-X ZERO SPLIT:", $zero_split, basename(__FILE__).' LINE '.__LINE__);
        $payment_transaction_id = add_to_ledger (array (
          'transaction_group_id' => $new_transaction_group_id,
          'source_type' => $original_transaction_data['source_type'],
          'source_key' => $original_transaction_data['source_key'],
          'target_type' => $original_transaction_data['target_type'],
          'target_key' => $original_transaction_data['target_key'],
          'amount' => 0,
          'text_key' => $original_transaction_data['text_key'],
          'effective_datetime' => $original_transaction_data['effective_datetime'],
          'posted_by' => $original_transaction_data['posted_by'],
          'replaced_by' => $original_transaction_data['replaced_by'],
          // 'timestamp' => $original_transaction_data['timestamp'],
          'basket_id' => $original_transaction_data['basket_id'],
          'bpid' => $original_transaction_data['bpid'],
          'site_id' => $original_transaction_data['site_id'],
          'delivery_id' => $original_transaction_data['delivery_id'],
          'pvid' => $original_transaction_data['pvid'],
          'messages' => ''));
        if (! is_numeric ($payment_transaction_id))
          array_push ($error_array, 'DATABASE ERROR: Problem posting the adjustment.');
      }

    // Update the ledger using the basket method (automatically adds new item and links with prior)
// debug_print ("WARN: X-3-X ", $_REQUEST, basename(__FILE__).' LINE '.__LINE__);
    $ledger_status = basket_item_to_ledger(array (
      'transaction_id' => $original_transaction_id,
      'transaction_group_id' => $original_transaction_data['transaction_group_id'],
      'source_type' => $new_source_type,
      'source_key' => $new_source_key,
      'target_type' => $new_target_type,
      'target_key' => $new_target_key,
      'amount' => $new_amount,
      'text_key' => $new_text_key,
      'posted_by' => $_SESSION['member_id'],
      'effective_datetime' => $new_effective_datetime,
      'basket_id' => $new_basket_id,
      'bpid' => $new_bpid,
      'site_id' => $new_site_id,
      'delivery_id' => $new_delivery_id,
      'pvid' => $new_pvid,
      'match_keys' => array ('transaction_id') // This forces changing of the specific transaction
      ));
    // Having completed the update, we need to do two things:
    //    1. Replace the transaction row we just modified
    //    2. Get the new transaction
    $transaction_data = get_transaction($target);
    if (count ($transaction_data)) $row_markup = get_transaction_row ($transaction_data);
    // Now take the replaced_by to get the new transaction information
    $new_transaction_data = get_transaction($transaction_data['replaced_by']);
    if (count ($new_transaction_data)) $row_markup .= get_transaction_row ($new_transaction_data);
    // By returning BOTH rows (actually tbody elements, we insert them together
    echo $row_markup;
  }

// Send the add-new dialog
if ($type == 'new')
  {
    // Get current information about the transaction
    $transaction_data = get_transaction($target);
    $where = '
          0';
    if ($transaction_data['member_id'] > 0)
      {
        $where .= '
          OR member_id = "'.$transaction_data['member_id'].'"';
      }
    if ($transaction_data['producer_id'] > 0)
      {
        $where .= '
          OR producer_id = "'.$transaction_data['producer_id'].'"';
      }
    if ($transaction_data['delivery_id'] > 0)
      {
        $where .= '
          OR delivery_id = "'.$transaction_data['delivery_id'].'"';
      }
    if ($transaction_data['site_id'] > 0)
      {
        $where .= '
          OR site_id = "'.$transaction_data['site_id'].'"';
      }
    // If the transaction is replaced, then abort and do not allow editing

    // Display the add-new dialogue
    $display = '
        <tr id="new_dialog">
          <td colspan="10">
            <form class="edit_dialog" name="edit_dialog">
              <fieldset class="left">
                <legend>Required Accounting Info.</legend>
                <label for="source_key">From:</label>
                <select name="source_type" id="source_type">
                  <option value="member"'.($transaction_data['source_type'] == 'member' ? ' selected' : '').'>member</option>
                  <option value="producer"'.($transaction_data['source_type'] == 'producer' ? ' selected' : '').'>producer</option>
                  <option value="internal"'.($transaction_data['source_type'] == 'internal' ? ' selected' : '').'>internal</option>
                  <option value="tax"'.($transaction_data['source_type'] == 'tax' ? ' selected' : '').'>tax</option>
                </select>
                <input type="text" name="source_key" id="source_key" placeholder="'.$transaction_data['source_key'].'">
                <br>
                <label for="target_key">To:</label>
                <select name="target_type" id="target_type">
                  <option value="member"'.($transaction_data['target_type'] == 'member' ? ' selected' : '').'>member</option>
                  <option value="producer"'.($transaction_data['target_type'] == 'producer' ? ' selected' : '').'>producer</option>
                  <option value="internal"'.($transaction_data['target_type'] == 'internal' ? ' selected' : '').'>internal</option>
                  <option value="tax"'.($transaction_data['target_type'] == 'tax' ? ' selected' : '').'>tax</option>
                </select>
                <input type="text" name="target_key" id="target_key" placeholder="'.$transaction_data['target_key'].'">
                <br>
                <label for="amount">Amount:</label>
                <input type="text" class="regular_input" name="amount" id="amount" value="" placeholder="'.$transaction_data['amount'].'">
                <br>
                <label for="text_key">Text Key:</label>
                <input type="text" class="regular_input" name="text_key" id="text_key" value="" placeholder="'.$transaction_data['text_key'].'" list="text_key_list" required>
                <datalist id="text_key_list">
                  '.get_text_key_list().'
                </datalist>
                <br>
                <label for="effective_datetime">Effective Date/Time:</label>
                <input type="text" name="effective_datetime" id="effective_datetime" value="'.date ('Y-m-d H:i:s', time()).'">
              </fieldset>
              <fieldset class="right">
                <legend>Optional Shopping/Basket Info.</legend>
                <label for="basket_id">Basket ID:</label>
                <input type="text" class="regular_input" name="basket_id" id="basket_id" value="" placeholder="'.$transaction_data['basket_id'].'">
                <br>
                <label for="bpid">Basket/Product ID:</label>
                <input type="text" class="regular_input" name="bpid" id="bpid" value="" placeholder="'.$transaction_data['bpid'].'">
                <br>
                <label for="site_id">Site ID:</label>
                <input type="text" class="regular_input" name="site_id" id="site_id" list="site_list" value="" placeholder="'.$transaction_data['site_id'].'">
                <datalist id="site_list">
                  '.get_site_list().'
                </datalist>
                <br>
                <label for="delivery_id">Delivery ID:</label>
                <input type="text" class="regular_input" name="delivery_id" id="delivery_id" value="" placeholder="'.$transaction_data['delivery_id'].'">
                <br>
                <label for="pvid">Product/Version ID:</label>
                <input type="text" class="regular_input" name="pvid" id="pvid" value="" placeholder="'.$transaction_data['pvid'].'">
              </fieldset>
              <div><input class="edit_button" type="button" onclick="new_transaction('.$transaction_data['transaction_id'].')" value="ADD"></div>
              <div><input class="edit_button" type="button" onclick="cancel_new_dialog()" value="Cancel"></div>
              <div><input class="edit_button" type="reset" value="Reset"></div>
            </form>
          </td>
         </tr>';
    echo $display;
  }

// Add the transaction
if ($type == 'add')
  {
    // Get the posted (update) information from the form submission
    $original_transaction_id = '';
    // Source
    $new_source_type = $_REQUEST['source_type'];
    $new_source_key = $_REQUEST['source_key'];
    // Target
    $new_target_type = $_REQUEST['target_type'];
    $new_target_key = $_REQUEST['target_key'];
    // Text key
    $new_text_key = $_REQUEST['text_key'];
    // Amount
    $new_amount = $_REQUEST['amount'];
    // Effective Date/time
    $new_effective_datetime = $_REQUEST['effective_datetime'];
    // Basket ID
    $new_basket_id = $_REQUEST['basket_id'];
    // Basket/Product ID
    $new_bpid = $_REQUEST['bpid'];
    // Site ID
    $new_site_id = $_REQUEST['site_id'];
    // Delivery ID
    $new_delivery_id = $_REQUEST['delivery_id'];
    // Product/Version ID
    $new_pvid = $_REQUEST['pvid'];
    // Message
    $new_messages = $_REQUEST['adjustment_message'];

    include_once ('func.update_ledger.php');
    $messages = array ();
    if ($_REQUEST['adjustment_message'] != '') $messages['ledger comment'] = $_REQUEST['adjustment_message'];
    // Add the transaction into the ledger
    $ledger_status = add_to_ledger(array (
      // 'transaction_group_id' => $original_transaction_data['transaction_group_id'],
      'source_type' => $new_source_type,
      'source_key' => $new_source_key,
      'target_type' => $new_target_type,
      'target_key' => $new_target_key,
      'amount' => $new_amount,
      'text_key' => $new_text_key,
      'posted_by' => $_SESSION['member_id'],
      'effective_datetime' => $new_effective_datetime,
      'basket_id' => $new_basket_id,
      'bpid' => $new_bpid,
      'site_id' => $new_site_id,
      'delivery_id' => $new_delivery_id,
      'pvid' => $new_pvid,
      'messages' => $messages
      ));
    // Having completed the update, we need to do two things:
    //    1. Replace the transaction row we just modified
    //    2. Get the new transaction
//    $transaction_data = get_transaction($target);
//    if (count ($transaction_data)) $row_markup = get_transaction_row ($transaction_data);
//    // Now take the replaced_by to get the new transaction information
//    $new_transaction_data = get_transaction($transaction_data['replaced_by']);
//    if (count ($new_transaction_data)) $row_markup .= get_transaction_row ($new_transaction_data);
    // By returning BOTH rows (actually tbody elements, we insert them together
    echo $row_markup;
  }

function get_heading_row ()
  {
    // Set some classes
    if ($transaction_data['replaced_by'] != '') $replaced_class = 'replaced ';
    $display = '
      <thead>
        <tr id="row1_header" class="header_row1">
          <th>transaction id</th>
          <th>group</th>
          <th>from</th>
          <th>to</th>
          <th>amount</th>
          <th>text key</th>
          <th>effective datetime</th>
          <th>posted by</th>
          <th>replaces id</th>
          <th>replaced by</th>
        </tr>
        <tr id="row2_header" class="header_row2">
          <th>replaced datetime</th>
          <th>timestamp</th>
          <th>basket id</th>
          <th>bpid</th>
          <th>site id</th>
          <th>delivery id</th>
          <th>pvid</th>
          <th colspan="3">comment</th>
        </tr>
      </thead>';
    return $display;
  }

function get_transaction_row ($transaction_data)
  {
    // Set some classes
    $replaced_class = 'standard';
    if ($transaction_data['replaced_by'] != '') $replaced_class = 'replaced ';
    $display = '
      <tbody id="'.$transaction_data['transaction_id'].'" class="transaction_row">
        <tr class="'.$replaced_class.' row1">
          <td>'.($_REQUEST['method'] == 'ajax' ?'<div class="close_row js_link" onclick="close_transaction_row('.$transaction_data['transaction_id'].')">X</div> ' : '').$transaction_data['transaction_id'].'</td>
          <td>'.$transaction_data['transaction_group_id'].'</td>
          <td>'.$transaction_data['source_type'].':
              '.$transaction_data['source_key'].'</td>
          <td>'.$transaction_data['target_type'].':
              '.$transaction_data['target_key'].'</td>
          <td>'.$transaction_data['amount'].'</td>
          <td>'.$transaction_data['text_key'].'</td>
          <td>'.$transaction_data['effective_datetime'].'</td>
          <td>'.$transaction_data['posted_by'].': '.$transaction_data['posted_by_name'].'</td>
          <td>'.($transaction_data['replaces_id'] != '' ? '<span class="js_link" onclick="get_replaced_transaction('.$transaction_data['transaction_id'].','.$transaction_data['replaces_id'].')">'.$transaction_data['replaces_id'].'</span>' : '').'</td>
          <td>'.($transaction_data['replaced_by'] != '' ? '<span class="js_link" onclick="get_replacing_transaction('.$transaction_data['transaction_id'].','.$transaction_data['replaced_by'].')">'.$transaction_data['replaced_by'].'</span>' : '').'</td>
        </tr>
        <tr class="'.$replaced_class.' row2">
          <td>'.$transaction_data['replaced_datetime'].'</td>
          <td>'.$transaction_data['timestamp'].'</td>
          <td>'.$transaction_data['basket_id'].'</td>
          <td>'.$transaction_data['bpid'].'</td>
          <td>'.$transaction_data['site_id'].'</td>
          <td>'.$transaction_data['delivery_id'].'</td>
          <td>'.$transaction_data['pvid'].'</td>
          <td colspan="3">'.$transaction_data['comment'].'</td>
        </tr>';
    // Check if we need to display editing controls
    if ($transaction_data['replaced_by'] == '')
      {
        $display .= '
        <tr id="edit_control_'.$transaction_data['transaction_id'].'">
          <td colspan="10" class="edit_control">
            <div>Operations:</div>
            <div><span class="js_link" onclick="get_edit_dialog('.$transaction_data['transaction_id'].','.$transaction_data['transaction_id'].')">EDIT</span></div>
            <div><span class="js_link" onclick="get_new_dialog('.$transaction_data['transaction_id'].','.$transaction_data['transaction_id'].')">NEW</span></div>
            <!-- <div><span>DELETE (zero)</span></div> -->
          </td>
        </tr>';
      }
    $display .= '
      </tbody>';
    return $display;
  }

// Get transaction information from any prior transaction
function get_replaced ($transaction_id)
  {
    global $connection;
    $query = '
      SELECT
        '.NEW_TABLE_LEDGER.'.*,
        '.TABLE_MEMBER.'.preferred_name AS posted_by_name
      FROM
        '.NEW_TABLE_LEDGER.'
      LEFT JOIN '.TABLE_MEMBER.' ON ('.TABLE_MEMBER.'.member_id = '.NEW_TABLE_LEDGER.'.posted_by)
      WHERE
        replaced_by = "'.mysql_real_escape_string($transaction_id).'"';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 675302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $num_rows = mysql_num_rows ($result);
    if ($num_rows)
      {
        $row = mysql_fetch_array ($result);
        // Now get messages
        $query_message = '
          SELECT
            '.NEW_TABLE_MESSAGES.'.message,
            '.NEW_TABLE_MESSAGE_TYPES.'.description
          FROM
            '.NEW_TABLE_MESSAGES.'
          LEFT JOIN '.NEW_TABLE_MESSAGE_TYPES.' USING(message_type_id)
          WHERE
              ('.NEW_TABLE_MESSAGES.'.referenced_key1 = "'.mysql_real_escape_string($row['transaction_id']).'"
              AND '.NEW_TABLE_MESSAGE_TYPES.'.key1_target = "ledger.transaction_id")
            OR
              ('.NEW_TABLE_MESSAGES.'.referenced_key1 = "'.mysql_real_escape_string($row['transaction_group_id']).'"
              AND '.NEW_TABLE_MESSAGE_TYPES.'.key1_target = "ledger.transaction_group_id")';
        $result_message = mysql_query($query_message, $connection) or die(debug_print ("ERROR: 572021 ", array ($query_message,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $message_array = array ();
        while ($row_message = mysql_fetch_array ($result_message))
          {
            array_push ($message_array, /* $row_message['description'].': '. */ $row_message['message']);
          }
        if (count ($message_array)) $row['comment'] = '['.implode ("][", $message_array).']';
        return $row;
      }
    else return array ('0');
  }

// Get transaction information
function get_transaction ($transaction_id)
  {
    global $connection;
    $query = '
      SELECT
        '.NEW_TABLE_LEDGER.'.*,
        '.TABLE_MEMBER.'.preferred_name AS posted_by_name
      FROM
        '.NEW_TABLE_LEDGER.'
      LEFT JOIN '.TABLE_MEMBER.' ON ('.TABLE_MEMBER.'.member_id = '.NEW_TABLE_LEDGER.'.posted_by)
      WHERE
        transaction_id = "'.mysql_real_escape_string($transaction_id).'"';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 293032 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysql_fetch_array ($result))
      {
        // Now get messages
        $query_message = '
          SELECT
            '.NEW_TABLE_MESSAGES.'.message,
            '.NEW_TABLE_MESSAGE_TYPES.'.description
          FROM
            '.NEW_TABLE_MESSAGES.'
          LEFT JOIN '.NEW_TABLE_MESSAGE_TYPES.' USING(message_type_id)
          WHERE
              ('.NEW_TABLE_MESSAGES.'.referenced_key1 = "'.mysql_real_escape_string($row['transaction_id']).'"
              AND '.NEW_TABLE_MESSAGE_TYPES.'.key1_target = "ledger.transaction_id")
            OR
              ('.NEW_TABLE_MESSAGES.'.referenced_key1 = "'.mysql_real_escape_string($row['transaction_group_id']).'"
              AND '.NEW_TABLE_MESSAGE_TYPES.'.key1_target = "ledger.transaction_group_id")';
        $result_message = mysql_query($query_message, $connection) or die(debug_print ("ERROR: 572021 ", array ($query_message,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $message_array = array ();
        while ($row_message = mysql_fetch_array ($result_message))
          {
            array_push ($message_array, /* $row_message['description'].': '. */ $row_message['message']);
          }
        if (count ($message_array)) $row['comment'] = '['.implode ("][", $message_array).']';
        return $row;
      }
    else return 'error';
  }

function get_text_key_list ()
  {
    global $connection;
    // Build text_key list
    $query = '
      SELECT
        DISTINCT(text_key) AS text_key
      FROM '.NEW_TABLE_LEDGER.'
      WHERE
        1
      ORDER BY text_key';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 675393 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array ($result))
      {
        $text_key_list .= '
          <option value="'.$row['text_key'].'">'.$row['text_key'].'</option>';
      }
    return $text_key_list;
  }

function get_site_list ()
  {
    global $connection;
    // Build customer site list
    $query = '
      SELECT
        site_id,
        site_short,
        site_long
      FROM '.NEW_TABLE_SITES.'
      WHERE
        site_type = "customer"
      ORDER BY site_short';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 759320 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array ($result))
      {
        $site_list .= '
          <option value="'.$row['site_id'].'" label="'.str_pad ($row['site_id'], 3, '0', STR_PAD_LEFT).' '.$row['site_short'].'">'.str_pad ($row['site_id'], 3, '0', STR_PAD_LEFT).' '.$row['site_long'].'</option>';
      }
    return $site_list;
  }

function display_page_header ()
  {
    echo '<!DOCTYPE html>
<html>
  <head>
    <title>Adjust Ledger</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link href="'.PATH.'stylesheet.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="'.PATH.'adjust_ledger.css">
    <script src="'.PATH.'ajax/jquery.js" type="text/javascript"></script>
    <script src="'.PATH.'ajax/jquery-ui.js" type="text/javascript"></script>
    <script src="'.PATH.'adjust_ledger.js" type="text/javascript"></script>
  </head>
  <body lang="en-us">
    <h3>Edit transaction information</h3>';
  }

function display_page_footer ()
  {
    echo '
  </body>
</html>';
  }
