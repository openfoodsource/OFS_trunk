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
            <br>
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
