<?php
// IMPORTANT: Save the posted data before doing anything else
$received_post_data = file_get_contents('php://input');
include_once 'config_openfood.php';

// Use this file for all basic paypal services
// Be careful when making changes because this must be prepared to process paypal calls at any time.

// Check if there is incoming posted data -- i.e. from paypal
if (strlen ($received_post_data) && $not_from_paypal == false)
  {
    // STEP 1: read POST data

    // Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
    // Instead, read raw POST data from the input stream.
    $received_post_array = explode('&', $received_post_data);
    $prepared_post_array = array();
    foreach ($received_post_array as $keyval)
      {
        $keyval = explode ('=', $keyval);
        if (count($keyval) == 2) $prepared_post_array[$keyval[0]] = urldecode($keyval[1]);
      }
    // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
    $status_request = 'cmd=_notify-validate';
    if(function_exists('get_magic_quotes_gpc')) $get_magic_quotes_exists = true;
    foreach ($prepared_post_array as $key => $value)
      {
        if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) $value = urlencode(stripslashes($value));
        else $value = urlencode($value);
        $status_request .= "&$key=$value";
      }
    // Step 2: POST IPN data back to PayPal to validate
    $curl_handle = curl_init (PAYPAL_IPN_ENDPOINT);
    curl_setopt ($curl_handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt ($curl_handle, CURLOPT_POST, 1);
    curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER,1);
    curl_setopt ($curl_handle, CURLOPT_POSTFIELDS, $status_request);
    curl_setopt ($curl_handle, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt ($curl_handle, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt ($curl_handle, CURLOPT_FORBID_REUSE, 1);
    curl_setopt ($curl_handle, CURLOPT_HTTPHEADER, array('Connection: Close'));

    // In wamp-like environments that do not come bundled with root authority certificates,
    // please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set
    // the directory path of the certificate as shown below:
    // curl_setopt($curl_handle, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
    if( !($status_response = curl_exec($curl_handle)) )
      {
        // error_log("Got " . curl_error($curl_handle) . " when processing IPN data");
        curl_close($curl_handle);
        exit;
      }
    curl_close($curl_handle);

    // Step 3: Receive "VERIFIED" or "INVALID" response and $_POST fields returned

    // inspect IPN validation result and act accordingly
    if (strcmp ($status_response, 'VERIFIED') == 0)
      {
        // The IPN is verified, process it:
        //   Check whether the payment_status is "Completed"
        //   Check that txn_id has not been previously processed
        //   Check that receiver_email is your Primary PayPal email
        //   Check that payment_amount/payment_currency are correct
        //   Process the notification

        // Assign posted variables to local variables
        $item_name = $_POST['item_name'];
        $item_number = $_POST['item_number'];
        $payment_status = $_POST['payment_status'];
        $payment_amount = $_POST['mc_gross'];
        $payment_currency = $_POST['mc_currency'];
        $txn_id = $_POST['txn_id'];
        $receiver_email = $_POST['receiver_email'];
        $payer_email = $_POST['payer_email'];
        // IPN message values depend upon the type of notification sent.
      }
    elseif (strcmp ($status_response, 'INVALID') == 0)
      {
        // IPN invalid, log for manual investigation
        die (debug_print ("ERROR: 571930 ", array(
          'Level' => 'FAIL',
          'Scope' => 'Paypal API',
    //      'File' => str_replace (FILE_PATH, '', __FILE__).' at line '.__LINE__,
          'File ' => __FILE__.' at line '.__LINE__,
          'Message' => 'The response from IPN was: '.$status_response,
          'Details' => array (
            'Message received' => $received_post_data,
            'Message sent    ' => $status_request,
            'Status response ' => $status_response))));
      }
    $okay_to_post_payment = true;
    // Check if the payment_status is 'Completed'
    if ($_POST['payment_status'] != 'Completed') $okay_to_post_payment = false;
    // Check if this transaction_id matches one we have already processed
    if ($_POST['payment_gross'] == ofs_get_status ('paypal_txn_id', $_POST['txn_id'])) $okay_to_post_payment = false;
    // Check if the payment address is okay
    if (stripos (PAYPAL_VALID_EMAILS, $_POST['receiver_email']) === false) $okay_to_post_payment = false;
    // Check for the proper currency
    if ($_POST['mc_currency'] != PAYPAL_CURRENCY) $okay_to_post_payment = false;
    // If we made it through the gauntlet, then we can now post the payment
    if ($okay_to_post_payment == true)
      {
        // We use the "custom" field of paypal submissions to track what sort of payment is being made
        // Options are:
        //    basket#[basket_id]
        //    member#[member_id]
        //    producer#[producer_id]    (Not implemented)
        list ($type, $number) = explode ('#', $_POST['custom']);
        if ($type == 'basket')
          {
            // First update the status table with this posting
            ofs_put_status ('paypal_txn_id', $_POST['txn_id'], $_POST['payment_gross'], PAYPAL_TTL);
            // Get basket information
            $query = '
              SELECT * FROM '.NEW_TABLE_BASKETS.' WHERE basket_id = "'.mysql_real_escape_string ($number).'"';
            $result = @mysql_query($query, $connection) or die (
              debug_print ("ERROR: 780602 ", array(
                'Level' => 'FATAL',
                'Scope' => 'Database',
                'File ' => __FILE__.' at line '.__LINE__,
                'Details' => array (
                  'MySQL Error' => mysql_errno(),
                  'Message' => mysql_error(),
                  'Query' => $query))));
            // See if we got a resulting basket to post into
            if ($row = mysql_fetch_object($result))
              {
                // Prepare to post the accounting
                $payment_message_array = array();
                $payment_message_array['ledger comment'] = $_POST['memo'];
                $paypal_message_array = array();
                $paypal_message_array['ledger paypal comment'] = 'From: '.$_POST['payer_email'];
                $effective_datetime = date('Y-m-d H:i:s', strtotime ($_POST['payment_date']));
                $transaction_group_id = get_new_transaction_group_id ();
                include_once ('func.update_ledger.php');
                // First, post the PayPal fee
                $paypal_transaction_id = add_to_ledger (array (
                  'transaction_group_id' => $transaction_group_id,
                  'source_type' => 'internal',
                  'source_key' => 'paypal charges',
                  'target_type' => 'internal',
                  'target_key' => 'payment sent',
                  'amount' => $_POST['payment_fee'], // PayPal fee
                  'text_key' => 'paypal charges',
                  'effective_datetime' => $effective_datetime,
                  'posted_by' => $row->member_id, // Consider the member self-posted the payment
                  'replaced_by' => '',
                  'timestamp' => '',
                  'basket_id' => $row->basket_id,
                  'bpid' => '',
                  'site_id' => $row->site_id,
                  'delivery_id' => $row->delivery_id,
                  'pvid' => '',
                  'messages' => $paypal_message_array));
                if (! is_numeric ($paypal_transaction_id))
                  debug_print ("ERROR: 860224 ", array(
                    'Level' => 'WARNING',
                    'Scope' => 'PayPal API',
                    'File ' => __FILE__.' at line '.__LINE__,
                    'Details' => array (
                      'Message' => 'Did not receive transaction_id when posting PayPal fee',
                      'IPN Data' => $_POST)));
                // Second, post the Gross payment
                $payment_transaction_id = add_to_ledger (array (
                  'transaction_group_id' => $transaction_group_id,
                  'source_type' => 'internal',
                  'source_key' => 'payment received',
                  'target_type' => 'member',
                  'target_key' => $row->member_id,
                  'amount' => $_POST['payment_gross'],
                  'text_key' => 'payment received',
                  'effective_datetime' => $effective_datetime,
                  'posted_by' => $row->member_id, // Consider the member self-posted the payment
                  'replaced_by' => '',
                  'timestamp' => '',
                  'basket_id' => $row->basket_id,
                  'bpid' => '',
                  'site_id' => $row->site_id,
                  'delivery_id' => $row->delivery_id,
                  'pvid' => '',
                  'messages' => $payment_message_array));
                if (! is_numeric ($payment_transaction_id))
                  debug_print ("ERROR: 785493 ", array(
                    'Level' => 'WARNING',
                    'Scope' => 'PayPal API',
                    'File ' => __FILE__.' at line '.__LINE__,
                    'Details' => array (
                      'Message' => 'Did not receive transaction_id when posting PayPal payment',
                      'IPN Data' => $_POST)));
              }
            else
              {
                // ERROR: Could not find basket
                die (debug_print ("ERROR: 822345 ", array(
                  'Level' => 'FATAL',
                  'Scope' => 'PayPal API',
                  'File ' => __FILE__.' at line '.__LINE__,
                  'Details' => array (
                    'Message' => 'Could not find basket information for basket #'.$number,
                    'IPN Data' => $_POST))));
              }
          }
        elseif ($type == 'member')
          {
            // First update the status table with this posting
            ofs_put_status ('paypal_txn_id', $_POST['txn_id'], $_POST['payment_gross'], PAYPAL_TTL);
            // Prepare to post the accounting
            $payment_message_array = array();
            $payment_message_array['ledger comment'] = $_POST['memo'];
            $paypal_message_array = array();
            $paypal_message_array['ledger paypal comment'] = 'From: '.$_POST['payer_email'];
            $effective_datetime = date('Y-m-d H:i:s', strtotime ($_POST['payment_date']));
            $transaction_group_id = get_new_transaction_group_id ();
            include_once ('func.update_ledger.php');
            // First, post the PayPal fee
            $paypal_transaction_id = add_to_ledger (array (
              'transaction_group_id' => $transaction_group_id,
              'source_type' => 'internal',
              'source_key' => 'paypal charges',
              'target_type' => 'internal',
              'target_key' => 'payment sent',
              'amount' => $_POST['payment_fee'], // PayPal fee
              'text_key' => 'paypal charges',
              'effective_datetime' => $effective_datetime,
              'posted_by' => $number, // Consider the member self-posted the payment
              'replaced_by' => '',
              'timestamp' => '',
              'basket_id' => '',
              'bpid' => '',
              'site_id' => '',
              'delivery_id' => '',
              'pvid' => '',
              'messages' => $paypal_message_array));
            if (! is_numeric ($paypal_transaction_id))
              debug_print ("ERROR: 275378 ", array(
                'Level' => 'WARNING',
                'Scope' => 'PayPal API',
                'File ' => __FILE__.' at line '.__LINE__,
                'Details' => array (
                  'Message' => 'Did not receive transaction_id when posting PayPal fee',
                  'IPN Data' => $_POST)));
            // Second, post the Gross payment
            $payment_transaction_id = add_to_ledger (array (
              'transaction_group_id' => $transaction_group_id,
              'source_type' => 'internal',
              'source_key' => 'payment received',
              'target_type' => 'member',
              'target_key' => $number,
              'amount' => $_POST['payment_gross'],
              'text_key' => 'payment received',
              'effective_datetime' => $effective_datetime,
              'posted_by' => $number, // Consider the member self-posted the payment
              'replaced_by' => '',
              'timestamp' => '',
              'basket_id' => '',
              'bpid' => '',
              'site_id' => '',
              'delivery_id' => '',
              'pvid' => '',
              'messages' => $payment_message_array));
            if (! is_numeric ($payment_transaction_id))
              debug_print ("ERROR: 372755 ", array(
                'Level' => 'WARNING',
                'Scope' => 'PayPal API',
                'File ' => __FILE__.' at line '.__LINE__,
                'Details' => array (
                  'Message' => 'Did not receive transaction_id when posting PayPal payment',
                  'IPN Data' => $_POST)));
          }
        else // ERROR: We have a payment but no account to post it into
          {
            die (debug_print ("ERROR: 879023 ", array(
              'Level' => 'FATAL',
              'Scope' => 'PayPal API',
              'File ' => __FILE__.' at line '.__LINE__,
              'Details' => array (
                'Message' => 'Custom field ('.$_POST['custom'].') insufficient to determine PayPal credit target',
                'IPN Data' => $_POST))));
          }
      }
  }

// Use an array like the following to preload values and call the function with
// paypal_display_form ($paypal_arguments)
//
// $paypal_arguments = array (
//   'form_id' => 'paypal_form1',
//   'span1_content' => 'Pay $',
//   'span2_content' => ' now with PayPal',
//   'form_target' => 'paypal',
//   'allow_editing' => true,
//   'amount' => number_format ($unique['total_order_amount'] + $unique['balance_forward'] + $unique['included_adjustment_total'], 2),
//   'business' => PAYPAL_EMAIL,
//   'item_name' => htmlentities ($unique['member_id'].' '.$unique['preferred_name']),
//   'notify_url' => BASE_URL.PATH.'paypal_utilities.php',
//   'custom' => htmlentities ($unique['site_id']),
//   'no_note' => '0',
//   'cn' => 'Message:',
//   'cpp_cart_border_color' => '#3f7300',
//   'cpp_logo_image' => BASE_URL.DIR_GRAPHICS.'logo1_for_paypal.png',
//   'return' => BASE_URL.PATH.'trash.php',
//   'cancel_return' => BASE_URL.PATH.'foobar.php',
//   'rm' => '2',
//   'cbt' => 'Return to '.SITE_NAME,
//   'paypal_button_src' => 'https://www.paypal.com/en_US/i/btn/btn_buynow_SM.gif'
//   );

function paypal_display_form ($paypal)
  {
    $paypal_form = '
      <form id="'.$paypal['form_id'].'" action="'.PAYPAL_IPN_ENDPOINT.'" method="post" target="'.$paypal['form_target'].'">
        <span class="paypal_span1">'.$paypal['span1_content'].'</span>
        <input type="'.($paypal['allow_editing'] ? 'text' : 'hidden').'" class="paypal_amount_field" name="amount" value="'.$paypal['amount'].'">
        <span class="paypal_span2">'.$paypal['span2_content'].'</span>
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="business" value="'.$paypal['business'].'">
        <input type="hidden" name="item_name" value="'.$paypal['item_name'].'">
        <input type="hidden" name="notify_url" value="'.$paypal['notify_url'].'">
        <input type="hidden" name="custom" value="'.$paypal['custom'].'">
        <input type="hidden" name="no_note" value="'.$paypal['no_note'].'">
        <input type="hidden" name="cn" value="'.$paypal['cn'].'">
        <input type="hidden" name="cpp_cart_border_color" value="'.$paypal['cpp_cart_border_color'].'">
        <input type="hidden" name="cpp_logo_image" value="'.$paypal['cpp_logo_image'].'"><!-- max size: 190x60 -->
        <input type="hidden" name="return" value="'.$paypal['return'].'">
        <input type="hidden" name="cancel_return" value="'.$paypal['cancel_return'].'">
        <input type="hidden" name="rm" value="'.$paypal['rm'].'">
        <input type="hidden" name="cbt" value="'.$paypal['cbt'].'">
        <input type="image" class="paypal_button" src="'.$paypal['paypal_button_src'].'" border="0" name="submit" alt="Make payments with PayPal - fast, free and secure!" style="border:0;"><br>
      </form>';
    return $paypal_form;
  }
