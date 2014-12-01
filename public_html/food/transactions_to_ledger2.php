<?php
include_once 'config_openfood.php';
session_start();

include_once ('func.update_ledger.php');


// README  README  README  README  README  README  README  README  README  README  README  README 
// 
// This program will cycle through all the OLD non-finalization-related transactions in the
// transactions table and make entries to the ledger table for them. This will include
// membership payments, membership receivables, adjustments, etc.

// CHECK FOR AJAX CALL (for compactness, this script handles its own ajax)
if ($_REQUEST['ajax'] == 'yes')
  {
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Get a list of transactions with the particular ttype_id               //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'post_to_ledger')
      {
//$content .= print_r($_REQUEST,true);
        // Prepare the content for update_ledger()
        $ledger_data['transaction_id'] = $_REQUEST['transaction_id'];
        $ledger_data['transaction_group_id'] = $_REQUEST['transaction_group_id'];
        $ledger_data['source_type'] = $_REQUEST['source_type'];
        $ledger_data['source_key'] = $_REQUEST['source_key'];
        $ledger_data['target_type'] = $_REQUEST['target_type'];
        $ledger_data['target_key'] = $_REQUEST['target_key'];
        $ledger_data['amount'] = $_REQUEST['amount'];
        $ledger_data['delivery_id'] = $_REQUEST['delivery_id'];
        $ledger_data['site_id'] = $_REQUEST['site_id'];
        $ledger_data['basket_id'] = $_REQUEST['basket_id'];
        $ledger_data['bpid'] = $_REQUEST['bpid'];
        $ledger_data['text_key'] = $_REQUEST['text_key'];
        $ledger_data['effective_date'] = $_REQUEST['timestamp'];
        $ledger_data['timestamp'] = $_REQUEST['timestamp'];
        $ledger_data['posted_by'] = $_REQUEST['posted_by'];
        // Populate the messages array
        $messages = array ();
        if ($_REQUEST['batchno'] != '' && $_REQUEST['batchno'] != 0) $messages['ledger batch number'] = $_REQUEST['batchno'];
        if ($_REQUEST['memo'] != '') $messages['ledger memo'] = $_REQUEST['memo'];
        if ($_REQUEST['comments'] != '') $messages['ledger comment'] = $_REQUEST['comments'];
        // Handle "PROCESS-AND-NEXT" button push
        if ($_REQUEST['requested_action'] == 'process_and_next')
          {
            // First, verify that this transaction is not already processed...
            $query = '
              SELECT
                xfer_to_ledger
              FROM
                '.TABLE_TRANSACTIONS.'
              WHERE
                transaction_id = "'.$_REQUEST['transaction_id'].'"';
            $result= mysql_query($query) or die(debug_print ("ERROR: 730542 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysql_fetch_array($result))
              {
                // If not yet xfer_to_ledger then okay to continue
                if ($row['xfer_to_ledger'] == 0)
                  {
                    $ledger_data['match_keys'] = array ('source_type','source_key','target_type','target_key','text_key','delivery_id');
                    // Use add_to_ledger instead of basket_item_to_ledger because there may be multiple entries
                    $new_transaction_id = add_to_ledger($ledger_data);
                    // Update the transactions table to show this one is completed
                    $query = '
                      UPDATE '.TABLE_TRANSACTIONS.'
                      SET xfer_to_ledger = "1"
                      WHERE transaction_id = "'.mysql_real_escape_string($_REQUEST['transaction_id']).'"';
                    $result= mysql_query($query) or die(debug_print ("ERROR: 883892 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
                    $content .= 'posted:'.$_REQUEST['transaction_id'];
                  }
              }
           }
        // Handle "MARK-AS-PROCESSED" button push
        elseif ($_REQUEST['requested_action'] == 'mark_and_next')
          {
            // Update the transactions table to show this one is completed
            // even though no action was taken.
            $query = '
              UPDATE '.TABLE_TRANSACTIONS.'
              SET xfer_to_ledger = "1"
              WHERE transaction_id = "'.mysql_real_escape_string($_REQUEST['transaction_id']).'"';
            $result= mysql_query($query) or die(debug_print ("ERROR: 752093 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            $content .= 'marked:'.$_REQUEST['transaction_id'];
          }
        // Handle "SKIP-TO-NEXT" button push
        elseif ($_REQUEST['requested_action'] == 'skip_and_next')
          {
            $content .= 'skipped:'.$_REQUEST['transaction_id'];
          }
        echo $content;
        exit (0);
      }
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Get a list of transactions with the particular ttype_id               //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'get_transaction_list')
      {
        $query = '
          SELECT
            '.TABLE_MEMBER.'.member_id,
            '.TABLE_MEMBER.'.first_name,
            '.TABLE_MEMBER.'.last_name,
            '.TABLE_TRANS_TYPES.'.ttype_whereshow,
            '.TABLE_TRANSACTIONS.'.*
          FROM
            '.TABLE_TRANSACTIONS.'
          LEFT JOIN
            '.TABLE_MEMBER.' ON '.TABLE_TRANSACTIONS.'.transaction_user = '.TABLE_MEMBER.'.username
          LEFT JOIN
            '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
          WHERE
            transaction_type = "'.$_REQUEST['ttype_id'].'"
            /* AND xfer_to_ledger = "0" */';
        $result= mysql_query($query) or die("Error: 863024" . mysql_error());
        $content .= '
          <ul id="transactions_list">';
        while($row = mysql_fetch_array($result))
          {
            // Use this (strikeout) to show adjustments that were previous deletions.
            // Of course, there was an associated adjustment that was the target of the deletion...
            $strike = '';
            if ($row['transaction_comments'] == 'Adjustment Zeroed Out') $strike = ' strike';
            $content .= '
              <li id="trans_id:'.$row['transaction_id'].'" class="trans_detail trans_'.($row['xfer_to_ledger'] == 1 ? '' : 'in').'complete'.$strike.'" onclick="get_transaction_info('.$row['transaction_id'].')">
                  <span class="trans_id">'.$row['transaction_id'].'</span>
                  <span class="amount'.($row['transaction_amount'] < 0 ? ' neg' : ' pos').'">$ '.$row['transaction_amount'].'</span>
                  <span class="source">'.($row['ttype_whereshow'] == 'producer' ? 'Prod: '.$row['transaction_producer_id'] : 'Mem: '.$row['transaction_member_id']).'</span>
                  <span class="delivery_id">Del. #'.$row['transaction_delivery_id'].'</span>
              </li>';
          }
        $content .= '
          </ul>';
        echo $content;
        exit (0);
      }
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Get information about a specific transaction_id                       //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'get_transaction_info')
      {
        $query = '
          SELECT
            '.TABLE_TRANS_TYPES.'.ttype_whereshow,
            '.TABLE_TRANSACTIONS.'.transaction_id,
            '.TABLE_TRANSACTIONS.'.transaction_type,
            '.TABLE_TRANSACTIONS.'.transaction_amount,
            '.TABLE_TRANSACTIONS.'.transaction_producer_id,
            '.TABLE_TRANSACTIONS.'.transaction_member_id,
            '.TABLE_TRANSACTIONS.'.transaction_delivery_id,
            '.TABLE_TRANSACTIONS.'.transaction_taxed,
            '.TABLE_TRANSACTIONS.'.transaction_timestamp,
            '.TABLE_TRANSACTIONS.'.transaction_batchno,
            '.TABLE_TRANSACTIONS.'.transaction_memo,
            '.TABLE_TRANSACTIONS.'.transaction_comments,
            '.TABLE_ORDER_CYCLES.'.delivery_date,
            COALESCE ((SELECT member_id
                FROM '.TABLE_MEMBER.'
                WHERE username = transaction_user), 0) AS user_member_id,
            '.TABLE_TRANSACTIONS.'.transaction_user AS user_member_text,
            COALESCE (transaction_basket_id, (SELECT basket_id
                FROM '.NEW_TABLE_BASKETS.'
                WHERE delivery_id = transaction_delivery_id
                  AND member_id = transaction_member_id)) AS basket_id,
            (SELECT site_id
                FROM '.NEW_TABLE_BASKETS.'
                WHERE delivery_id = transaction_delivery_id
                  AND member_id = transaction_member_id) AS site_id
          FROM
            '.TABLE_TRANSACTIONS.'
          LEFT JOIN
            '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
          LEFT JOIN
            '.TABLE_ORDER_CYCLES.' ON ('.TABLE_TRANSACTIONS.'.transaction_delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id)
          WHERE
            transaction_id = "'.$_REQUEST['transaction_id'].'"';
        $result= mysql_query($query) or die("Error: 742752" . mysql_error());
        if($row = mysql_fetch_array($result))
          {
            // If bogus timestamp, then use the delivery date
            if (! strtotime ($row['transaction_timestamp']) $row['transaction_timestamp'] = $row['delivery_date'].' 00:00:00';
            $content .= json_encode($row);
            echo $content;
            exit (0);
          }
      }
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Get the conversion list for old transactions to new ledger            //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'get_transaction_types')
      {
        // Get a list of all transactions types and related information
        $query = '
          SELECT
            '.TABLE_TRANSACTIONS.'.transaction_id,
            '.TABLE_TRANS_TYPES.'.ttype_id,
            '.TABLE_TRANS_TYPES.'.ttype_parent,
            '.TABLE_TRANS_TYPES.'.ttype_name,
            '.TABLE_TRANS_TYPES.'.ttype_creditdebit,
            '.TABLE_TRANS_TYPES.'.ttype_taxed,
            '.TABLE_TRANS_TYPES.'.ttype_whereshow,
            SUM(transaction_amount) AS total_amount,
            COUNT(ttype_id) AS quantity
          FROM
            '.TABLE_TRANSACTIONS.'
          RIGHT JOIN '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANSACTIONS.'.transaction_type = '.TABLE_TRANS_TYPES.'.ttype_id
          WHERE
            ttype_parent = "'.mysql_real_escape_string($_REQUEST['ttype_parent']).'"
            AND '.TABLE_TRANSACTIONS.'.transaction_id IS NOT NULL
            AND xfer_to_ledger = "0"
          GROUP BY '.TABLE_TRANS_TYPES.'.ttype_id';
        $result= mysql_query($query) or die("Error: 899032" . mysql_error());
        while($row = mysql_fetch_object($result))
          {

// Probably need to add other table linkage elements here

            // Set up proposed variables:
            if ($row->ttype_whereshow == 'customer')
              {
                $whereshow = 'member'; // customer --> member
                $referenced_table = 'members';
                $referenced_key = '[members_id]';
              }
            elseif ($row->ttype_whereshow == 'producer')
              {
                $whereshow = 'producer';
                $referenced_table = 'producers';
                $referenced_key = '[producer_id]';
              }
            else
              {
                $whereshow = $row->ttype_whereshow;
              }
            if ($row->ttype_debitcredit == 'credit')
              {
                $source_type = 'internal';
                $source_key = ''; // enter a varchar(25) name
                $target_type = $whereshow;
                $target_key = '['.$whereshow.'_id]'; // gives [member_id] or [producer_id]
              }
            elseif ($row->ttype_debitcredit == 'debit')
            {
                $target_type = 'internal';
                $target_key = ''; // enter a varchar(25) name
                $source_type = $whereshow;
                $source_key = '['.$whereshow.'_id]'; // gives [member_id] or [producer_id]
              }
            $amount = '[amount]'; // value to be filled in later
            $text_key = 'adjustment';
//            $reason = 'legacy-transaction';
            $post_by_member = '[transaction_user]'; // Will need to be converted to a member_id
            $message = '';
            $content .= '
          <li id="ttype_id:'.$row->ttype_id.'" class="ttype_incomplete">
            <form>
              <div>
                <div class="ttype" onclick="get_transaction_list('.$row->ttype_id.')">
                  S<br>H<br>O<br>W<br> <br>L<br>I<br>S<br>T
                </div>
                <div class="source_trans">
                  <strong>OLD TRANSACTION TYPE # '.$row->ttype_id.'</strong><br>
                  <p><strong>Parent Transaction Type:</strong>
                    '.$row->ttype_parent.'</p>
                  <p><strong>Description:</strong>
                    '.htmlspecialchars ($row->ttype_name).'</p>
                  <p><strong>Total:</strong>
                    $ '.number_format ($row->total_amount, 2).' 
                  <strong>in</strong>
                     '.$row->quantity.' <strong>records.</strong></p>
                  <p><strong>Credit/Debit:</strong>
                    '.($row->ttype_creditdebit == 'credit' ? 'Credit' : 'Debit').' &nbsp; &nbsp; 
                  <strong>Taxed:</strong>
                    '.($row->ttype_taxed == 1 ? 'Yes' : 'No').' &nbsp; &nbsp; 
                  <strong>Applied to:</strong>
                    '.($row->ttype_whereshow == 'customer' ? 'Customer (Member)' : 'Producer').'</p>
                </div>
                <div class="target_trans">
                  <strong>NEW TRANSACTION FORMAT</strong><br>
                  <p><strong>Source Type:</strong>
                    <input type="radio" id="source_member:'.$row->ttype_id.'" name="source_type" value="member"'.($row->ttype_whereshow == 'customer' ? ' checked' : '').'>Member &nbsp; 
                    <input type="radio" id="source_producer:'.$row->ttype_id.'" name="source_type" value="producer"'.($row->ttype_whereshow == 'producer' ? ' checked' : '').'>Producer &nbsp; 
                    <input type="radio" id="source_internal:'.$row->ttype_id.'" name="source_type" value="internal"'.($row->ttype_whereshow == 'credit' ? ' checked' : '').'>Internal &nbsp; 
                    <input type="radio" id="source_tax:'.$row->ttype_id.'" name="source_type" value="tax"'.($row->ttype_whereshow == 'debit' ? ' checked' : '').'>Tax</p>
                  <p><strong>Source Key:</strong>
                    <input type="text" size="15" id="source_key:'.$row->ttype_id.'" name="source_key:'.$row->ttype_id.'" value="['.($row->ttype_whereshow == 'customer' ? 'member' : $row->ttype_whereshow).'_id]'.'"></p>
                  <p><strong>Target Type:</strong>
                    <input type="radio" id="target_member:'.$row->ttype_id.'" name="target_type" value="member">Member &nbsp; 
                    <input type="radio" id="target_producer:'.$row->ttype_id.'" name="target_type" value="producer">Producer &nbsp; 
                    <input type="radio" id="target_internal:'.$row->ttype_id.'" name="target_type" value="internal" checked>Internal &nbsp; 
                    <input type="radio" id="target_tax:'.$row->ttype_id.'" name="target_type" value="tax">Tax</p>
                  <p><strong>Target Key:</strong>
                    <input type="text" size="15" id="target_key:'.$row->ttype_id.'" name="target_key:'.$row->ttype_id.'" value=""></p>
                  <p><strong>Amount Multiplier:</strong>
                    <input type="text" size="5" id="base_multiplier:'.$row->ttype_id.'" name="base_multiplier:'.$row->ttype_id.'" value="1.00">
                    (1.00 will keep same / -1.00 to invert)</p>
                  <p><strong>Text Key:</strong>
                    <input type="text" size="20" id="text_key:'.$row->ttype_id.'" name="text_key:'.$row->ttype_id.'" value="'.$text_key.'"> &nbsp; &nbsp; 
                  <strong>* Posted By Member ID:</strong>
                    <input type="text" size="5" id="user:'.$row->ttype_id.'" name="user:'.$row->ttype_id.'" value=""></p>
                  <p><strong>Transaction group ID:</strong>
                    <input type="text" size="20" id="transaction_group_id:'.$row->ttype_id.'" name="transaction_group_id:'.$row->ttype_id.'" value=""></p>
                  <p><strong>Messages:</strong>
                    * Batch No: <input type="text" size="10" id="batchno:'.$row->ttype_id.'" name="batchno:'.$row->ttype_id.'"> &nbsp;
                    * Memo: <input type="text" size="20" id="memo:'.$row->ttype_id.'" name="memo:'.$row->ttype_id.'"><br>
                    * Comments: <input type="text" size="60" id="comments:'.$row->ttype_id.'" name="comments:'.$row->ttype_id.'"><br>
                    </p>
                  <p>* Fallback options will be used if actual values are not found.
                </div>
              </div>
            </form>
          </li>';
          }
        echo $content;
        exit (0);
      }
    // Ajax as called, but not used, so exit with error
    exit (1);
  }

// BEGIN GENERATING MAIN PAGE //////////////////////////////////////////////////

// Get a list of all transactions parent types ttype_parents and create buttons for them
$query = '
  SELECT
    ttypes2. * 
  FROM
    '.TABLE_TRANS_TYPES.' ttypes1
  LEFT JOIN '.TABLE_TRANS_TYPES.' ttypes2
    ON ttypes1.ttype_parent = ttypes2.ttype_id
  WHERE
    ttypes2.ttype_id IS NOT NULL 
  GROUP BY
    ttype_id';
$result= mysql_query($query) or die("Error: 603823" . mysql_error());
while($row = mysql_fetch_array($result))
  {
    // Create a button for each
    $button_content .= '
      <input type="button" value="'.$row['ttype_desc'].'" onclick="get_transactions_types('.$row['ttype_id'].')">';
  }
$content .= '
<div id="instructions">
    <p>Select from the buttons below to pick a family (parent) set of transactions to work with.
      General information about those transactions and suggested translation values will be
      displayed with each transaction type.</p>
    <p>After modifying the translations to set new and/or default values, click the &quot;Show
      List&quot; at the left to load the list of transactions from the database in preparation
      for processing.</p>
    <p>Click on transactions that are loaded in the list to see how they will be converted into
      ledger entries. When everything looks good, either process them singly or uncheck the
      &quot;Pause&quot; checkbox to automatically move on through the list. Note that transactions
      will be flagged in the database so they will not be processed more than once.</p>
    <p>Transactions that were used to zero-out other transactions are shown stricken-out. <i>In
      combination with</i> the transaction they were used to zero, they can probably be ignored.
      The recommended action is to find and &quot;Mark as Processed&quot; each of the two before
      processing the list as a batch.</p>
</div>
<div id="reporting">
  <div id="left-column">
    <p>'.$button_content.'</p>
    <div id="ttypes_box">
      <div id="trans_list">
      </div>
    </div>
  </div>
  <div id="right-column">
    <div id="transactions_box">
      <div id="trans_list">
      </div>
    </div>
  </div>
</div>
<div id="process_area" style="clear:both;">
  <div id="process_target">
    <form name="ledger_data" id="ledger_data" action="" method="post">
      <table id="in_process">
        <tr>
          <td class="label">Amount:</td><td>
          <input type="text" size="7" id="amount"></td>
          <td class="label">Delivery ID:</td>
          <td><input type="text" size="8" id="delivery_id"></td>
          <td class="label">Timestamp:</td>
          <td><input type="text" size="20" id="timestamp"></td>
        </tr>
        <tr>
          <td class="label">Source Type:</td>
          <td><input type="text" size="12" id="source_type"></td>
          <td class="label">Delcode ID:</td>
          <td><input type="text" size="8" id="site_id"></td>
          <td class="label">Posted By:</td>
          <td><input type="text" size="5" id="posted_by"><input type="text" size="12" id="posted_by_text"></td>
        </tr>
        <tr>
          <td class="label">Source Key:</td>
          <td><input type="text" size="12" id="source_key"></td>
          <td class="label">Basket ID</td>
          <td><input type="text" size="8" id="basket_id"></td>
          <td class="label">Reason:</td>
          <td><input type="text" size="15" id="transaction_group_id"></td>
        </tr>
        <tr>
          <td class="label">Target Type:</td>
          <td><input type="text" size="12" id="target_type"></td>
          <td class="label">Bask/Prod BPID</td>
          <td><input type="text" size="8" id="bpid"></td>
          <td class="label">Post Zeros?</td>
          <td><input type="checkbox" id="post_even_if_zero" value="YES"></td>
        </tr>
        <tr>
          <td class="label">Target Key:</td>
          <td><input type="text" size="12" id="target_key"></td>
          <td class="label">Text Key:</td>
          <td><input type="text" size="15" id="text_key"></td>
          <td class="label">Transaction ID:</td>
          <td><input type="text" size="10" id="transaction_id"></td>
        </tr>
        <tr>
          <td class="label">Messages:</td>
          <td colspan="5">
            Batch No: <input type="text" size="10" id="batchno" name="batchno"> &nbsp;
            Memo: <input type="text" size="20" id="memo" name="memo">
            Comments: <input type="text" size="50" id="comments" name="comments">
          </td>
        </tr>
        <tr>
          <td colspan="4">
            <input type="button" id="process_next" value="Process and Next" onclick="process_ledger_data(\'process_and_next\')">
            <input type="button" id="mark_processed" value="Mark as Processed" onclick="process_ledger_data(\'mark_and_next\')">
            <input type="button" id="skip_process_next" value="Skip to Next" onclick="process_ledger_data(\'skip_and_next\')">
          </td>
          <td class="label">Pause?</td>
          <td><input type="checkbox" id="pause" name="pause" onClick="process_basket_list()" checked></td>
        </tr>
      </table>
    </form>
  <div id="post_response"></div>
  </div>
</div>';

$page_specific_javascript = '
    <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
    <script type="text/javascript" src="'.PATH.'transactions_to_ledger2.js"></script>';

$page_specific_css = '
    <link href="'.PATH.'transactions_to_ledger2.css" rel="stylesheet" type="text/css">';

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

?>