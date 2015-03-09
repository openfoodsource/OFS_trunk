<?php
include_once 'config_openfood.php';
session_start();


if (! CurrentMember::auth_type('cashier'))
  {
    echo '
        <div id="make_payment_row" class="data_row">
          <span class="error_message">Only cashiers are permitted to execute this function.</span>
        </div>';
    exit (1); // Not permitted to access this page
  }


switch ($_POST['process'])
  {
// MAKE PAYMENTS FORM ******************************************************
    case 'get_make_payment_form':
      echo get_make_payment_form ($_POST['delivery_id'],$_POST['producer_id'],$_POST['business_name'], '');
      break;

// POST MAKE PAYMENTS ******************************************************
    case 'make_payment':
      $error_array = array ();
      // Validate the data: amount
      if (preg_match('/^[-]{0,1}[0-9]*(\.[0-9]{2}){0,1}$/', $_POST['amount']) != 1)
        array_push ($error_array, 'Payment must be numeric with decimal cents<br>(e.g. 45.67 or .89).');
      // Validate the data: payment_type
      if ($_POST['payment_type'] != 'cash' &&
          $_POST['payment_type'] != 'check' &&
          $_POST['payment_type'] != 'paypal')
        array_push ($error_array, 'Please select a payment type.');
      // Validate the data: memo
      if (preg_match('/^[0-9]*$/', $_POST['memo']) != 1)
        array_push ($error_array, 'Memo must be numeric.');
      if (strlen($_POST['memo']) > 20)
        array_push ($error_array, 'Memo must not exceed 20 characters.');
      // Validate the data: batch_number
      if (strlen($_POST['batch_number']) > 20)
        array_push ($error_array, 'Batch number can not exceed 20 characters.');
      // Validate the data: comment
      if (strlen($_POST['comment']) > 200)
        array_push ($error_array, 'Comment can not exceed 200 characters.');
      $payment_message_array = array();
      // Assemble the payment_message array
      if (strlen($_POST['memo']) > 0)
        $payment_message_array['ledger memo'] = $_POST['memo'];
      if (strlen($_POST['batch_number']) > 0)
        $payment_message_array['ledger batch number'] = $_POST['batch_number'];
      if (strlen($_POST['comment']) > 0)
        $payment_message_array['ledger comment'] = $_POST['comment'];


//       // If there is a basket, then get basket information from the database
//       if ($_POST['basket_id'] != 0)
//         {
//           $query = '
//             SELECT
//               member_id,
//               basket_id,
//               site_id,
//               delivery_id
//             FROM
//               '.NEW_TABLE_BASKETS.'
//             WHERE
//               basket_id = "'.mysql_real_escape_string($_POST['basket_id']).'"';
//           $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 672930 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//           if (! $row = mysql_fetch_array($result))
//             array_push ($error_array, 'DATABASE ERROR: No information found for this basket.');
//         }
//       // Otherwise set basket information to NULL
//       else
//         {
//           $row['basket_id'] = '';
//           $row['site_id'] = '';
//           $row['delivery_id'] = '';
//         }


//       // Post the paypal fee, if any...
//       if (count($error_array) == 0 &&
//           $_POST['payment_type'] == 'paypal' &&
//           $_POST['paypal_fee'] != 0)
//         {
//           // Reserve a transaction group value using the transaction_group_enum table (similar to an auto-increment)
//           // this allows linking the paypal transaction with the payment
//           $query = '
//             INSERT INTO
//               '.NEW_TABLE_ADJUSTMENT_GROUP_ENUM.'
//             VALUES (NULL)';
//           $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 752930 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//           $transaction_group_id = mysql_insert_id();
//           include_once ('func.update_ledger.php');
//           $paypal_transaction_id = add_to_ledger (array (
//             'transaction_group_id' => $transaction_group_id,
//             'source_type' => 'internal',
//             'source_key' => 'paypal charges',
//             'target_type' => 'internal',
//             'target_key' => 'payment sent',
//             'amount' => $_POST['paypal_fee'],
//             'text_key' => 'paypal charges',
//             'effective_datetime' => $_POST['effective_datetime'],
//             'posted_by' => $_SESSION['member_id'],
//             'replaced_by' => '',
//             'timestamp' => '',
//             'basket_id' => $row['basket_id'],
//             'bpid' => '',
//             'site_id' => $row['site_id'],
//             'delivery_id' => $row['delivery_id'],
//             'pvid' => '',
//             'messages' => $paypal_message_array));
//           if (! is_numeric ($paypal_transaction_id))
//             array_push ($error_array, 'DATABASE ERROR: Problem posting the paypal payment.');
//         }


      // if no errors, then post the payment, if any
      if (count($error_array) == 0 && 
          $_POST['amount'] != 0)
        {
          // For negative payments received (payments MADE TO members), we will change the source_key to match
          $text_key = 'payment made';
          if ($_POST['amount'] < 0) $text_key = 'payment received';
          include_once ('func.update_ledger.php');
          $payment_transaction_id = add_to_ledger (array (
            'transaction_group_id' => $transaction_group_id,
            'source_type' => 'internal',
            'source_key' => $text_key,
            'target_type' => 'producer',
            'target_key' => $_POST['producer_id'],
            'amount' => $_POST['amount'],
            'text_key' => $text_key,
            'effective_datetime' => $_POST['effective_datetime'],
            'posted_by' => $_SESSION['member_id'],
            'replaced_by' => '',
            'timestamp' => '',
            'basket_id' => '',
            'bpid' => '',
            'site_id' => '',
            'delivery_id' => $_POST['delivery_id'],
            'pvid' => '',
            'messages' => $payment_message_array));
          if (! is_numeric ($payment_transaction_id))
            array_push ($error_array, 'DATABASE ERROR: Problem posting the producer payment.');

        }
      // All done, so return what we know...
      if (count($error_array) == 0)
        {
          $return_content = 'ACCEPT    ';
        }
      else
        {
          $error_message = '
            <div class="error_message">
              <p class="message">The information was not accepted. Please correct the following problems and resubmit.
                <ul class="error_list">
                  <li>'.implode ("</li>\n<li>", $error_array).'</li>
                </ul>
              </p>
            </div>';
          $return_content = 'ERROR     '.get_make_payment_form ($_POST['delivery_id'],$_POST['producer_id'],$_POST['business_name'], $error_message);
        }
      echo $return_content;
      break;

// BASKET TOTAL ONLY ******************************************************
    case 'basket_summary';
      break;
  }

/**************** FUNCTIONS USED ABOVE ******************/

function get_make_payment_form ($delivery_id, $producer_id, $business_name, $error_message)
  {
    // Set up payment_type selections and show-paypal option
    if ($_POST['payment_type'] == 'cash')
      {
        $payment_type_cash = ' checked="checked"';
        $paypal_display_style = 'display:none;';
      }
    elseif ($_POST['payment_type'] == 'check')
      {
        $payment_type_check = ' checked="checked"';
        $paypal_display_style = 'display:none;';
      }
    elseif ($_POST['payment_type'] == 'paypal')
      {
        $payment_type_paypal = ' checked="checked"';
        $paypal_display_style = 'display:inline;';
      }
    else // Default
      {
        $payment_type_check = ' checked="checked"';
        $paypal_display_style = 'display:none;';
      }

    // Problem with paypal not showing, so override the display:none directive
    $paypal_display_style = 'display:inline;';
    // Construct the make payment form
    $form_content = '
      <div id="make_payment_row" class="data_row">'.
        $error_message.'
        <form id="make_payment_form">
          <fieldset>
            <div class="close_button" onclick="close_make_payment_form()"></div>
            <legend>
              Make payment to producer: '.urldecode ($business_name).'
            </legend>
            <span class="nobr">
              <label id="effective_datetime_label" for="effective_datetime">Effective Date/Time</label>
              <input id="effective_datetime" name="effective_datetime" required pattern="[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}" placeholder="'.date('Y-m-d H:i:s', time()).'" autocomplete="off" value="'.($_POST['effective_datetime'] ? $_POST['effective_datetime'] : date('Y-m-d H:i:s', time())).'">
            </span>

            <br>

            <span class="nobr">
              <label for=amount>Payment</label>
              <input id=amount name=amount type=text required autofocus pattern="[-]{0,1}[0-9]*(\.[0-9]{2}){0,1}" autocomplete="off" value="'.$_POST['amount'].'">
            </span>

            <span class="nobr">
              <label id="comment_label" for="comment">Comment</label>
              <input id="comment" name="comment" placeholder="Ex. Maresey dotes and dosey dotes..." autocomplete="off" value="'.$_POST['comment'].'">
            </span>
<!--
            <br>

            <span class="nobr paypal_section" style="'.$paypal_display_style.'">
              <label id="paypal_fee_label" for="paypal_fee">Paypal Fee</label>
              <input id="paypal_fee" name="paypal_fee" pattern="[0-9]*(\.[0-9]{2}){0,1}" autocomplete="off" value="'.$_POST['paypal_fee'].'">
            </span>

            <span class="nobr paypal_section" style="'.$paypal_display_style.'">
              <label id="paypal_comment_label" for="paypal_comment">Paypal&nbsp;Comment</label>
              <input id="paypal_comment" name="paypal_comment" placeholder="Ex. Little lamsey divey. A kidley divey too." autocomplete="off" value="'.$_POST['paypal_comment'].'">
            </span>
-->
            <br>
<!--
            <span class="nobr">
              <label for="payment_type_cash">Cash/Check</label>
              <input type="radio" id="payment_type_cash_check" value="cash" name="payment_type" required'.$payment_type_cash.' onclick="jQuery(\'#paypal_fee\').val(\'0.00\');">

              <label for="payment_type_paypal">Paypal</label>
              <input type="radio" id="payment_type_paypal" value="paypal" name="payment_type" required'.$payment_type_paypal.' onclick="jQuery(\'#paypal_fee\').val((Math.round((jQuery(\'#amount\').val()*0.029+0.30) * 100)/100).toFixed(2));">
            </span>
-->
            <input type="hidden" id="payment_type_cash_check" value="cash" name="payment_type">

            <span class="nobr">
              <label id="memo_label" for="memo">Memo</label>
              <input id="memo" name="memo" placeholder="Ex. 12345" pattern="[0-9]*" autocomplete="off" value="'.$_POST['memo'].'">
            </span>

            <span class="nobr">
              <label for="batch_number">Batch No</label>
              <input id="batch_number" name="batch_number" placeholder="Ex. AB-2468" autocomplete="off" value="'.$_POST['batch_number'].'">
            </span>

            <div class="button" onclick="make_payment('.$producer_id.','.$delivery_id.')">Make<br>Payment</div>
          </fieldset>
        </form>
      </div>';
    return $form_content;
  }
