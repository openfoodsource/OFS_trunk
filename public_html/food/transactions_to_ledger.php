<?php
include_once 'config_openfood.php';
session_start();


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
        // Prepare the content for update_ledger()
        // If there is not good timestamp value, then use the delivery date
        if (! strtotime ($_REQUEST['timestamp'])
          {
            $query = '
              SELECT delivery_date
              FROM '.TABLE_ORDER_CYCLES.'
              WHERE delivery_id = "'.mysql_real_escape_string($_REQUEST['delivery_id']).'"';
            $result= mysql_query($query) or die("Error: 754937" . mysql_error());
            if ($row = mysql_fetch_array($result))
              {
                $ledger_data['timestamp'] = $row['delivery_date'].' 00:00:00';
              }
            else
              {
                $error_code = 'no delivery_id to derive timestamp from';
              }
          }
        else
          {
            $ledger_data['timestamp'] = $_REQUEST['timestamp'];
          }
        $ledger_data['effective_datetime'] = $ledger_data['timestamp']
        $ledger_data['source_type'] = $_REQUEST['source_type'];
        $ledger_data['source_key'] = $_REQUEST['source_key'];
        $ledger_data['target_type'] = $_REQUEST['target_type'];
        $ledger_data['target_key'] = $_REQUEST['target_key'];
        $ledger_data['amount'] = $_REQUEST['amount'];
        $ledger_data['text_key'] = $_REQUEST['text_key'];

// Probably need to add other table linkage elements here

        $ledger_data['posted_by'] = $_REQUEST['posted_by'];
        // $ledger_data['replaced_by_transaction'] = '';
        // put the various messages into an associative array
        $messages = array ();
        if ($_REQUEST['batchno'] != '' && $_REQUEST['batchno'] != 0) $messages['ledger batch number'] = $_REQUEST['batchno'];
        if ($_REQUEST['memo'] != '') $messages['ledger memo'] = $_REQUEST['memo'];
        if ($_REQUEST['comments'] != '') $messages['ledger comment'] = $_REQUEST['comments'];
        // If no error, then go on with posting
        if ($error_code)
          {
            $content = $error_code;
          }
        else
          {
            // Rather than deleting delete_on_zero transactions, we simply will not post them
            if ($_REQUEST['delete_on_zero'] != 'YES' || $_REQUEST['amount'] != 0)
              {
                $content = update_ledger($ledger_data);
                // Should return "target_transaction_id:[transaction_id]"
                if (substr($content, 0, 22) == 'target_transaction_id:')
                  {
                    // Update the transactions table to show this one has been moved
                    $query = '
                      UPDATE '.TABLE_TRANSACTIONS.'
                      SET xfer_to_ledger = "'.mysql_real_escape_string(substr($content, 22)).'"
                      WHERE transaction_id = "'.mysql_real_escape_string($_REQUEST['transaction_id']).'"';
                  }
              }
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
            AND xfer_to_ledger = "0"';
        $result= mysql_query($query) or die("Error: 863024" . mysql_error());
        $content .= '
          <ul>';
        while($row = mysql_fetch_array($result))
          {
            $content .= '
              <li id="trans_id:'.$row['transaction_id'].'" class="trans_detail trans_incomplete" onclick="get_transaction_info('.$row['transaction_id'].')">
                  <span class="trans_id">'.$row['transaction_id'].'</span>
                  <span class="amount'.($row['transaction_amount'] < 0 ? ' neg' : '').'">$ '.$row['transaction_amount'].'</span>
                  <span class="source">'.($row['ttype_whereshow'] == 'producer' ? 'Prod: '.$row['transaction_producer_id'] : 'Memb: '.$row['transaction_member_id']).'</span>
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
            '.TABLE_TRANSACTIONS.'.*,
            COALESCE(
              ( SELECT member_id
                FROM '.TABLE_MEMBER.'
                WHERE username = transaction_user),
              0) AS user_member_id,
            COALESCE(
              ( SELECT basket_id
                FROM '.NEW_TABLE_BASKETS.'
                WHERE delivery_id = transaction_delivery_id
                  AND member_id = transaction_member_id),
              0) AS referenced_basket_key
          FROM
            '.TABLE_TRANSACTIONS.'
          LEFT JOIN
            '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
          WHERE
            transaction_id = "'.$_REQUEST['transaction_id'].'"';
        $result= mysql_query($query) or die("Error: 742752" . mysql_error());
        if($row = mysql_fetch_array($result))
          {
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
                $target_type = $whereshow;
                $target_key = '['.$whereshow.'_id]'; // gives [member_id] or [producer_id]
              }
            elseif ($row->ttype_debitcredit == 'debit')
            {
                $target_type = 'internal';
                $target_key = 'transaction_type'; // enter a varchar(25) name
                $source_type = $whereshow;
                $source_key = '['.$whereshow.'_id]'; // gives [member_id] or [producer_id]
              }
            $amount = '[amount]'; // value to be filled in later
            $text_key = 'legacy transaction';
            $post_by_member = '[transaction_user]'; // Will need to be converted to a member_id
            $message = '';
            $delete_on_zero = '';
            $content .= '
          <li id="ttype_id:'.$row->ttype_id.'" class="ttype_incomplete">
            <form>
              <div>
                <div class="ttype" onclick="get_transaction_list('.$row->ttype_id.')">
                  Show List
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
                    (1.00=keep same / -1.00=invert)</p>

<!-- Probably need to add other table linkage elements here -->

                  <p><strong>Referenced Table:</strong>
                    <input type="radio" id="referenced_members:'.$row->ttype_id.'" name="referenced_table" value="members">members table &nbsp; 
                    <input type="radio" id="referenced_baskets:'.$row->ttype_id.'" name="referenced_table" value="baskets" checked>baskets table &nbsp; 
                    <input type="radio" id="referenced_producers:'.$row->ttype_id.'" name="referenced_table" value="producers">producers &nbsp; 
                  <p><strong>Text Key:</strong>
                    <input type="text" size="20" id="text_key:'.$row->ttype_id.'" name="text_key" value="adjustment"> &nbsp; &nbsp; 
                  <strong>* Posted By Member ID:</strong>
                    <input type="text" size="5" id="user:'.$row->ttype_id.'" name="user" value=""></p>
                  <p><strong>Messages:</strong>
                    * Batch No: <input type="text" size="10" id="batchno:'.$row->ttype_id.'" name="batchno"> &nbsp;
                    * Memo: <input type="text" size="20" id="memo:'.$row->ttype_id.'" name="memo"><br>
                    * Comments: <input type="text" size="60" id="comments:'.$row->ttype_id.'" name="comments"><br>
                    </p>
                  <p><strong>Add Tax?</strong>
                    <input type="checkbox" id="add_taxed:'.$row->ttype_id.'" value="taxed"'.($row->ttype_taxed == 1 ? ' checked' : '').'>
                    (based on delivery_id)</p>
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
  <div id="controls">
    <div id="basket_generate_start">
      <input id="delivery_generate_button" type="submit" onClick="reset_delivery_list(); delivery_generate_start(); generate_basket_list();" value="Begin Processing">
    </div>
    <div id="delivery_progress"><div id="c_progress-left"></div><div id="c_progress-right"></div></div>
    <div id="basket_progress"><div id="p_progress-left"></div><div id="p_progress-right"></div></div>
  </div>
<div id="instructions">
    <p>Select from the buttons below to pick a family (parent) set of transactions to work with.
      General information about those transactions and suggested translation values will be
      displayed with each transaction type.</p>
    <p>After modifying the translations to set new and/or default values, click the &quot;Show
      List&quot; at the left to load the list of transactions from the database in preparation
      for processing.</p>
    <p>Click on transactions that are loaded in the list to see how they will be converted into
      ledger entries. When everything looks good, either process them singly or click the
      &quot;Process All&quot; button.</p>
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
    Pause: <input type="checkbox" id="pause" name="pause" onClick="process_basket_list()">
    Delivery: <input type="text" size="8" id="ttype_id" name="ttype_id">
    Basket: <input type="text" size="8" id="basket_id" name="basket_id">
    <div id="transactions_box">
      <div id="trans_list">
      </div>
    </div>
  </div>
</div>
<div id="process_area" style="clear:both;">
  <div id="process_target">
    <form name="ledger_data" id="ledger_data" action="" method="post">
      <table>
        <tr>
          <td>Delivery ID:</td>
          <td><input type="text" size="5" id="delivery_id"></td>
          <td>Amount:</td><td>
          <input type="text" size="7" id="amount"></td>
          <td>Timestamp:</td>
          <td><input type="text" size="20" id="timestamp"></td>
        </tr>
        <tr>
          <td>Source Type:</td>
          <td><input type="text" size="12" id="source_type"></td>
          <td>Source Key:</td>
          <td><input type="text" size="5" id="source_key"></td>
          <td>Source Subkey:</td>
          <td>xxx</td>
        </tr>
        <tr>
          <td>Target Type:</td>
          <td><input type="text" size="12" id="target_type"></td>
          <td>Target Key:</td>
          <td><input type="text" size="5" id="target_key"></td>
          <td>Target Subkey:</td>
          <td>xxx</td>
        </tr>

<!-- Probably need to add other table linkage elements here -->

        <tr>
          <td>Ref. Table:</td>
          <td><input type="text" size="12" id="referenced_table"></td>
          <td>Ref. Key:</td>
          <td><input type="text" size="5" id="referenced_key"></td>
          <td>Post By:</td>
          <td><input type="text" size="5" id="posted_by"></td>
        </tr>
        <tr>
          <td>Trans. Group:</td>
          <td>xxx</td>
          <td>Text Key:</td>
          <td><input type="text" size="15" id="text_key"></td>
          <td>Delete On Zero?</td>
          <td><input type="checkbox" id="delete_on_zero" value="YES"></td>
        </tr>
        <tr>
          <td>Messages:</td>
          <td colspan="5">
            Batch No: <input type="text" size="10" id="batchno" name="batchno"> &nbsp; Memo: <input type="text" size="20" id="memo" name="memo">
            Comments: <input type="text" size="50" id="comments" name="comments"><br>
            <input type="submit" id="process_ledger_data" value="Process This Entry">
          </td>
        </tr>
      </table>
    </form>
  <div id="post_response"></div>
  </div>
</div>';

$page_specific_javascript = '
    <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
    <script type="text/javascript" src="'.PATH.'members/transactions_to_ledger.js"></script>';

$page_specific_css = '
    <link href="/shop/members/transactions_to_ledger.css" rel="stylesheet" type="text/css">';

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