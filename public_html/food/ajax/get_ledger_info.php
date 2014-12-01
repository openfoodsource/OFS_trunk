<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

include_once ('func.get_ledger_row_markup.php');

//echo '<tr><td colspan="9" style="text-align:left;">';

////////////////////////////////////////////////////////////////////////////////////
//                                                                                //
// This is the main ajax call to get ledger information for display of            //
// accounting information.                                                        //
// REQUIRED arguments:                       action=get_ledger_info               //
//                                     account_spec=[account_type]:[account_id]   //
//                                                                                //
// OPTIONAL arguments:      group_customer_fee_with=[product|order]               //
//                          group_producer_fee_with=[product|order]               //
//                           group_weight_cost_with=[product|order]               //
//                             group_quantity_cost_with=[product|order]               //
//                          group_extra_charge_with=[product|order]               //
//                                 group_taxes_with=[product|order]               //
//                                   include_header=[true|false]                  //
//                                      delivery_id=[delivery_id]                 //
//                                                                                //
////////////////////////////////////////////////////////////////////////////////////

// These are text_key values used in transactions.
$text_key_array = array();
$summarize_by = array ();
$css_trans_array = array ();
$query = '
  SELECT
    DISTINCT(text_key) AS text_key
  FROM
    '.NEW_TABLE_LEDGER;
$result = mysql_query($query, $connection) or die(debug_print ("ERROR: 820432 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// Ensure $count is a two-digit (number or more)
$count = 10;
while ($row = mysql_fetch_array($result))
  {
    array_push ($text_key_array, $row['text_key']);
    // While we can, set all the summarize_by settings to default with basket_id
    $summarize_by[$row['text_key']] = 'delivery_id';
    $count ++;
    // Get some "random" letters to use in css abbreviations for various text_keys
    // Gives bb, bc, bd, be, bf, ...
    $css_trans_array[$row['text_key']] = strtr($count, '0123456789', 'abcdefghij');
  }

// We used to do this:
//     $css_trans_array = array (
//       'adjustment'    => 'aj',
//       'customer fee'  => 'cf', 
//       'producer fee'  => 'pf', 
//       'weight cost'   => 'wc', // Same key as "each" to treat them as a single group
//       'quantity cost' => 'qc', // Same key as "weight" to treat them  as a single group
//       'extra charge'  => 'ec', 
//       'delivery cost' => 'dc',
//       'tax'           => 'tx'
//       );

// Now overwrite any of the summary_by settings we want to be OTHER-THAN by delivery_id
// Summarizing these (grouping) by bpid will cause them to sort together at the product level
// These should all be text_key relationships
   $summarize_by['adjustment'] = 'bpid';
   $summarize_by['weight cost'] = 'bpid';
   $summarize_by['quantity cost'] = 'bpid';
   $summarize_by['extra charge'] = 'bpid';
// $summarize_by['customer fee' = 'delivery_id';
// $summarize_by['producer fee' = 'delivery_id';
// $summarize_by['delivery cost' = 'delivery_id';
// $summarize_by['state tax' = 'delivery_id';
// $summarize_by['city tax' = 'delivery_id';
// $summarize_by['county tax' = 'delivery_id';


// The summary_type_array should list from finest to coarsest detail -- opposite of the query ORDER BY
// but it only needs to include the *values* of the summarize_by array
// Do this so they come out in the correct order...
// $summary_type_array = array('bpid', 'pvid', 'basket_id', 'site_id', 'delivery_id');
$summary_type_array = array();
foreach (array('bpid', 'pvid', 'basket_id', 'site_id', 'delivery_id') as $summary_type)
  {
    if (in_array ($summary_type, array_values ($summarize_by))) array_push ($summary_type_array, $summary_type);
  }

// Initialize arrays
$static_balance = array();
$summarize = array();
$order_summary_count = array();
$order_summary = array();
$order_summary_singleton = array();
$order_item_count = array();
$order_product_count = array();
// Initialize some other arrays
$transaction_array['delivery_id'] = array();
$transaction_array['site_id'] = array();
$transaction_array['basket_id'] = array();
$transaction_array['pvid'] = array();
$transaction_array['bpid'] = array();

// This array is used to help make the output more readable for users
$english_trans_array = array (
  'delivery_id' => 'delivery cycle',
  'site_id'  => 'delivery location', 
  'basket_id'   => 'shopping basket', 
  'pvid'        => 'product',
  'bpid'        => 'product'
  );

// If asked for just the header...
if ($_REQUEST['action'] == 'get_ledger_head')
  {
    $response = get_ledger_header_markup ();
    echo $response;
    exit (0);
  }

// If asked for the body content...
if ($_REQUEST['action'] == 'get_ledger_body')
  {
    // Get the ledger data for whatever account this is
    list($account_type, $account_id) = explode (':',$_REQUEST['account_spec']);
    // Narrow the results, if requested
    if ($_REQUEST['delivery_id'])
      {
        $query_where = '
        AND ('.NEW_TABLE_LEDGER.'.delivery_id = "'.mysql_real_escape_string($_REQUEST['delivery_id']).'"
          OR '.NEW_TABLE_LEDGER.'.delivery_id IS NULL)';
        // And get the balance from before the delivery_id...
        $query = '
          SELECT
            SUM(amount * IF(source_type = "'.mysql_real_escape_string($account_type).'"
              AND source_key = "'.mysql_real_escape_string($account_id).'", -1, 1)) AS total_before
          FROM
            '.NEW_TABLE_LEDGER.'
          WHERE
            ((source_type = "'.mysql_real_escape_string($account_type).'"
                AND source_key = "'.mysql_real_escape_string($account_id).'")
              OR (target_type = "'.mysql_real_escape_string($account_type).'"
                AND target_key = "'.mysql_real_escape_string($account_id).'"))
            AND replaced_by IS NULL
            AND delivery_id < "'.mysql_real_escape_string($_REQUEST['delivery_id']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 788322 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysql_fetch_array($result))
          {
            $total_before = $row['total_before'];
          }
        else
          {
            $total_before = 0;
          }
        // ...and the balance from after the delivery_id
        $query = '
          SELECT
            SUM(amount * IF(source_type = "'.mysql_real_escape_string($account_type).'"
              AND source_key = "'.mysql_real_escape_string($account_id).'", -1, 1)) AS total_after
          FROM
            '.NEW_TABLE_LEDGER.'
          WHERE
            ((source_type = "'.mysql_real_escape_string($account_type).'"
                AND source_key = "'.mysql_real_escape_string($account_id).'")
              OR (target_type = "'.mysql_real_escape_string($account_type).'"
                AND target_key = "'.mysql_real_escape_string($account_id).'"))
            AND replaced_by IS NULL
            AND delivery_id > "'.mysql_real_escape_string($_REQUEST['delivery_id']).'"';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 788322 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysql_fetch_array($result))
          {
            $total_after = $row['total_after'];
          }
        else
          {
            $total_after = 0;
          }
        // Get a beginning row for the ledger...
        $running_total = $total_before;
        $extra_transaction['amount'] = $total_before;
        $extra_transaction['detail'] = 'earlier activity';
        $display_output .= get_display_row($extra_transaction, '', $running_total, 'extra_row');
      }
    $query = '
      SELECT
        '.NEW_TABLE_LEDGER.'.*,
        COALESCE(source_producer.business_name,"") AS source_business_name,
        COALESCE(target_producer.business_name,"") AS target_business_name,
        COALESCE(source_member.preferred_name,"") AS source_preferred_name,
        COALESCE(target_member.preferred_name,"") AS target_preferred_name,
        COALESCE(source_coa.account_number,"") AS source_account_number,
        COALESCE(target_coa.account_number,"") AS target_account_number,
        COALESCE(source_coa.description,"") AS source_description,
        COALESCE(target_coa.description,"") AS target_description,
        COALESCE(CONCAT_WS(" ", source_tax_rates.region_code, source_tax_rates.region_name, source_tax_rates.postal_code),"") AS source_tax_code,
        COALESCE(CONCAT_WS(" ", target_tax_rates.region_code, target_tax_rates.region_name, target_tax_rates.postal_code),"") AS target_tax_code,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.basket_id,0) AS basket_id,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.bpid, 0) AS bpid,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.quantity, 0) AS quantity,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.total_weight, "") AS total_weight,
        COALESCE('.NEW_TABLE_PRODUCTS.'.ordering_unit, "") AS ordering_unit,
        COALESCE('.NEW_TABLE_PRODUCTS.'.pricing_unit, "") AS pricing_unit,
        COALESCE('.NEW_TABLE_PRODUCTS.'.pvid, 0) AS pvid,
        COALESCE('.NEW_TABLE_PRODUCTS.'.product_id, 0) AS product_id,
        COALESCE('.NEW_TABLE_PRODUCTS.'.product_name, "") AS product_name,
        '.NEW_TABLE_BASKETS.'.locked,
        '.NEW_TABLE_BASKETS.'.site_id,
        '.NEW_TABLE_BASKETS.'.delivery_id,
        COALESCE('.TABLE_ORDER_CYCLES.'.delivery_date, 0) AS delivery_date
      FROM
        '.NEW_TABLE_LEDGER.'
      /* Get the linked basket or basket_item table entries */
      LEFT JOIN
        '.NEW_TABLE_BASKETS.' ON
          '.NEW_TABLE_LEDGER.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
      LEFT JOIN
        '.NEW_TABLE_BASKET_ITEMS.' ON
          '.NEW_TABLE_LEDGER.'.bpid = '.NEW_TABLE_BASKET_ITEMS.'.bpid
      LEFT JOIN
        '.TABLE_ORDER_CYCLES.' ON
          '.NEW_TABLE_BASKETS.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
      LEFT JOIN
        '.NEW_TABLE_PRODUCTS.' ON
          '.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id
      LEFT JOIN /* Source is a member */
        '.TABLE_MEMBER.' source_member ON
          (source_type = "member" AND source_key = source_member.member_id)
      LEFT JOIN /* Source is a producer */
        '.TABLE_PRODUCER.' source_producer ON
          (source_type = "producer" AND source_key = source_producer.producer_id)
      LEFT JOIN /* Source is internal */
        '.NEW_TABLE_ACCOUNTS.' source_coa ON
          (source_key = source_coa.account_id)
      LEFT JOIN /* Source is tax */
        '.NEW_TABLE_TAX_RATES.' source_tax_rates ON
          (source_type = "tax" AND source_key = source_tax_rates.tax_id)
      LEFT JOIN /* Target is a member */
        '.TABLE_MEMBER.' target_member ON
          (target_type = "member" AND target_key = target_member.member_id)
      LEFT JOIN /* Target is a producer */
        '.TABLE_PRODUCER.' target_producer ON
          (target_type = "producer" AND target_key = target_producer.producer_id)
      LEFT JOIN /* Target is internal */
        '.NEW_TABLE_ACCOUNTS.' target_coa ON
          (target_key = target_coa.account_id)
      LEFT JOIN /* Target is tax */
        '.NEW_TABLE_TAX_RATES.' target_tax_rates ON
          (target_type = "tax" AND target_key = target_tax_rates.tax_id)
      WHERE
        ((source_type = "'.mysql_real_escape_string($account_type).'"
            AND source_key = "'.mysql_real_escape_string($account_id).'")
          OR (target_type = "'.mysql_real_escape_string($account_type).'"
            AND target_key = "'.mysql_real_escape_string($account_id).'"))
        AND replaced_by IS NULL'.
        $query_where.'
      /* Not sure why we need this GROUP BY condition, but we do! */
      GROUP BY transaction_id
      ORDER BY
        '.NEW_TABLE_LEDGER.'.delivery_id,
        '.NEW_TABLE_LEDGER.'.site_id,
        '.NEW_TABLE_LEDGER.'.basket_id,
        '.NEW_TABLE_LEDGER.'.pvid,
        '.NEW_TABLE_LEDGER.'.bpid,
        '.NEW_TABLE_LEDGER.'.text_key';
//$display_output .= '<tr><td colspan="9"><pre>'.$query.'</pre></td></tr>';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 869373 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    // Need to know how many rows were returned because we need to iterate ONE MORE THAN that
    // many times in order to capture summary functions (not pretty). This really should be
    // done with some kind of data object... -ROYG
    $number_of_rows = mysql_num_rows ($result);
    $amount = 0;
    while ($row = mysql_fetch_array($result))
      {
        // Clear the assigned variables
        $other_account_type = '';
        $other_account_key = '';
        $display_to_from = '';
        $display_quantity = '';
        $detail = '';
        // $balance = 0; // this is the running total, so leave it alone!
        $display_balance = '';
          // VARIABLES RETURNED FROM QUERY: $row[...]
          // source_type,             target_preferred_name,    text_key,              product_id,
          // source_key,              source_account_number,    effective_datetime,    product_name,
          // target_type,             target_account_number,    posted_by,             basket_id,
          // target_key,              source_description,       timestamp,             site_id
          // amount,                  target_description,       quantity,              transaction_group_id
          // source_business_name,    source_tax_code,          total_weight,
          // target_business_name,    target_tax_code,          ordering_unit,
          // source_preferred_name,   transaction_id,           pricing_unit,

        // Check if this account is the SOURCE account
        if ($row['source_type'] == $account_type && $row['source_key'] == $account_id)
          {
            $other_account_type = $row['target_type'];
            $other_account_key = $row['target_key'];
            $other_account = 'target';
            $amount = 0 - $row['amount']; // Invert the sense of payments *from* the account
          }
        // Otherwise this account is the TARGET account
        elseif ($row['target_type'] == $account_type && $row['target_key'] == $account_id)
          {
            $other_account_type = $row['source_type'];
            $other_account_key = $row['source_key'];
            $other_account = 'source';
            $amount = $row['amount'];
          }
        else
          {
            // TO and FROM the same account... should not happen
            die(debug_print ("error 203: ", "could not determine disposition of transaction".print_r($row,true), basename(__FILE__).' LINE '.__LINE__));
          }
        $balance += $amount;
        if ($other_account_type == 'producer') $display_to_from = 'Producer #'.$other_account_key.': '.$row[$other_account.'_business_name'];
        elseif ($other_account_type == 'member') $display_to_from = 'Member #'.$other_account_key.': '.$row[$other_account.'_preferred_name'];
        elseif ($other_account_type == 'internal') $display_to_from = ORGANIZATION_ABBR.': '.$row[$other_account.'_account_number'].' ('.$row[$other_account.'_description'].')';
        elseif ($other_account_type == 'tax') $display_to_from = 'Tax: '.$row[$other_account.'_tax_code'];
        else $display_to_from = '['.$other_account_type.'::'.$other_account_key.']';
        if ($row['text_key'] == 'weight cost')
          {
            $display_quantity = $row['quantity'].' '.$row['ordering_unit'].' @ '.($row['total_weight'] + 0).' '.$row['pricing_unit'];
          }
        elseif ($row['text_key'] == 'quantity cost')
          {
            $display_quantity = $row['quantity'].' '.$row['ordering_unit'];
          }
        elseif ($row['text_key'] == 'extra charge')
          {
            $display_quantity = $row['quantity'].' '.$row['ordering_unit'];
          }
        if ($row['product_id'])
          {
            $detail = '(#'.$row['product_id'].') '.$row['product_name'];
          }
        elseif ($row['site_id'])
          {
            $detail = $row['site_id'].' basket'; //<pre>'.print_r($row,true).'</pre>';
          }
        // Set up an array of display_data for display output
        $transaction_current['transaction_id']      = $row['transaction_id'];
        $transaction_current['transaction_group_id']= $row['transaction_group_id'];
        $transaction_current['other_account_type']  = $other_account_type;
        $transaction_current['other_account_key']   = $other_account_key;
        $transaction_current['basket_id']           = $row['basket_id'];
        $transaction_current['timestamp']           = $row['timestamp'];
        $transaction_current['bpid']                = $row['bpid'];
        $transaction_current['pvid']                = $row['pvid'];
        $transaction_current['product_id']          = $row['product_id'];
        $transaction_current['display_to_from']     = $display_to_from;
        $transaction_current['text_key']            = $row['text_key'];
        $transaction_current['effective_datetime']  = $row['effective_datetime'];
        $text_key                                   = $row['text_key'];
        $transaction_current['source_type']         = $row['source_type'];
        $transaction_current['source_key']          = $row['source_key'];
        $transaction_current['target_type']         = $row['target_type'];
        $transaction_current['target_key']          = $row['target_key'];
        $transaction_current['quantity']            = $row['quantity'];
        $transaction_current['locked']              = $row['locked'];
        $transaction_current['display_quantity']    = $display_quantity;
        $transaction_current['detail']              = $detail;
        $transaction_current['amount']              = $amount;
        $transaction_current['balance']             = $balance;
        $transaction_current['delivery_id']         = $row['delivery_id'];
        $transaction_current['site_id']             = $row['site_id'];
        $transaction_current['delivery_date']       = $row['delivery_date'];
        // Get the preferred summarization form for this row (i.e. delivery_id, pvid, etc)
        $summary_type = $summarize_by[$text_key];
        $transaction_current['summary_type'] = $summary_type;
        // This should assign a value something like $change_array_current['delivery_id'] = 14
        // It will only track the summary_types we care about
        $change_array_current[$summary_type] = $transaction_current[$summary_type];
        // See if we have gone to a new summary type
        if ($change_array_current != $change_array_prior)
          {
//echo '<span style="color:#66a;">array_diff failed...<pre>'.print_r($change_array_current, true).print_r($change_array_prior, true).'</pre></span><br>';
            // Then it is time to expose these particular summary items as well as any that
            // are more specific. So start with the most specific and work back to the current level
            $done_summarizing = false;
            // $summary_type_array = array('bpid', 'pvid', 'basket_id', 'site_id', 'delivery_id');
            foreach ($summary_type_array as $this_summary_type)
              {
                if (! $done_summarizing)
                  {
//echo '<span style="color:#aaa;">processing summary_type: '.$this_summary_type.'</span><br>';
                    // Within each summary type, cycle through the text keys to see which ones have
                    // something to summarize
                    // $text_key_array = array('customer fee', 'producer fee', 'weight cost', 'quantity cost', 'extra charge', 'state tax', 'city tax', 'count tax', 'adjustment');
                    foreach ($text_key_array as $this_text_key)
                      {
                        // See if there is anything to summarize
                        $this_summary_count = count($transaction_array[$this_summary_type][$this_text_key]);
//if ($this_summary_count) echo '<span style="color:#aca;">checking... count of ['.$this_summary_type.']['.$this_text_key.']: '.$this_summary_count.'</span><br>';
                        // If one, then display it as a regular line
                        if ($this_summary_count == 1)
                          {
                            $display_transaction = array_pop($transaction_array[$this_summary_type][$this_text_key]);
                            $running_total += $display_transaction['amount'];
                            // "singleton_row" *would be* detail_row, but since it stands alone: display it instead of the summary
                            $display_output .= get_display_row($display_transaction, $this_summary_type, $running_total, 'singleton_row');
//echo '<span style="color:#a22;">recomposing1... ['.$this_summary_type.':'.$display_transaction[$this_summary_type].']['.$this_text_key.']: product-'.$display_transaction['product_id'].'</span><br>';
                          }
                        // If multiple, then display a summary of the contained information
                        elseif ($this_summary_count > 1)
                          {
                            // Initialize variables
                            $running_subtotal = 0;
                            $display_to_from = '';
                            $detail = '';
                            while ($display_transaction = array_pop($transaction_array[$this_summary_type][$this_text_key]))
                              {
//echo '<span style="color:#a22;">recomposingM... ['.$this_summary_type.':'.$display_transaction[$this_summary_type].']['.$this_text_key.']: product-'.$display_transaction['product_id'].'</span><br>';
                                $running_subtotal += $display_transaction['amount'];
                                $running_total += $display_transaction['amount'];
                                $display_output .= get_display_row($display_transaction, $this_summary_type, $running_total, 'detail_row');
                                // Capture some summary row information since it does not have a product of its own
                                $summary_data['text_key'] = $display_transaction['text_key'];
                                $summary_data['summary_type'] = $display_transaction['summary_type'];
                                $summary_data[$summary_data['summary_type']] = $display_transaction[$display_transaction['summary_type']];
                                //$summary_data['detail'] = 'All '.$display_transaction['text_key'].' items for this '.$display_transaction['summary_type'];
                                $summary_data['basket_id'] = $display_transaction['basket_id'];
                                // Either keep the summary data information forward or show it as a mixed group
                                if ($display_to_from && $display_to_from != $display_transaction['display_to_from']) $display_to_from = 'various';
                                else $display_to_from = $display_transaction['display_to_from'];
                                if ($detail && $detail != $display_transaction['detail']) $detail = 'Various '.$display_transaction['text_key'].' items for this '.$english_trans_array[$display_transaction['summary_type']];
                                else $detail = $display_transaction['detail'];
                                $summary_data['amount'] = $running_subtotal;
                              }
                            $summary_data['display_to_from'] = $display_to_from;
                            $summary_data['detail'] = $detail;
                            $display_transaction['amount'] = $running_subtotal;
                            $display_transaction['text_key'] = $this_text_key;
                            $display_transaction['summary_type'] = $summary_type;
                            // Use the data from the most recent $display_output as a shortcut to get some
                            // of the useful values.
                            $display_output .= get_display_row($summary_data, $this_summary_type, $running_total, 'summary_row');
                          }
                        // Otherwise there is nothing to do
                      }
                  }
                else
                  {
                  }
                if ($this_summary_type == $summary_type) $done_summarizing = true;
              } // end foreach
          }
        // We will always push the row onto a summary stack and handle it on the next pass (except the last pass, of course)
        if (!is_array ($transaction_array[$summary_type][$text_key])) $transaction_array[$summary_type][$text_key] = array();
        array_push ($transaction_array[$summary_type][$text_key], $transaction_current);
//echo 'pushing... ['.$summary_type.':'.$transaction_current[$summary_type].']['.$text_key.'] new total:'.count($transaction_array[$summary_type][$text_key]).'<br>';
        $transaction_prior = $transaction_current;
        $change_array_prior = $change_array_current;
      }
    $done_summarizing = false;
    foreach ($summary_type_array as $this_summary_type)
      {
        // Within each summary type, cycle through the text keys to see which ones have
        // something to summarize
        // $text_key_array = array('customer fee', 'producer fee', 'weight cost', 'quantity cost', 'extra charge', 'state tax', 'city tax', 'count tax', 'adjustment');
        foreach ($text_key_array as $this_text_key)
          {
            // See if there is anything to summarize
            $this_summary_count = count($transaction_array[$this_summary_type][$this_text_key]);
            // If one, then display it as a regular line
            if ($this_summary_count == 1)
              {
                $display_transaction = array_pop($transaction_array[$this_summary_type][$this_text_key]);
                $running_total += $display_transaction['amount'];
                // "singleton_row" *would be* detail_row, but since it stands alone: display it instead of the summary
                $display_output .= get_display_row($display_transaction, $this_summary_type, $running_total, 'singleton_row');
              }
            // If multiple, then display a summary of the contained information
            elseif ($this_summary_count > 1)
              {
                // Initialize variables
                $running_subtotal = 0;
                $display_to_from = '';
                $detail = '';
                while ($display_transaction = array_pop($transaction_array[$this_summary_type][$this_text_key]))
                  {
                    $running_subtotal += $display_transaction['amount'];
                    $running_total += $display_transaction['amount'];
                    $display_output .= get_display_row($display_transaction, $this_summary_type, $running_total, 'detail_row');
                    // Capture some summary row information since it does not have a product of its own
                    $summary_data['text_key'] = $display_transaction['text_key'];
                    $summary_data['summary_type'] = $display_transaction['summary_type'];
                    $summary_data[$summary_data['summary_type']] = $display_transaction[$display_transaction['summary_type']];
                    //$summary_data['detail'] = 'All '.$display_transaction['text_key'].' items for this '.$display_transaction['summary_type'];
                    $summary_data['basket_id'] = $display_transaction['basket_id'];
                    $summary_data['amount'] = $running_subtotal;
                    // Either keep the summary data information forward or show it as a mixed group
                    if ($display_to_from && $display_to_from != $display_transaction['display_to_from']) $display_to_from = 'various';
                    else $display_to_from = $display_transaction['display_to_from'];
                    if ($detail && $detail != $display_transaction['detail']) $detail = 'Various '.$display_transaction['text_key'].' items for this '.$english_trans_array[$display_transaction['summary_type']];
                    else $detail = $display_transaction['detail'];
                  }
                $summary_data['display_to_from'] = $display_to_from;
                $summary_data['detail'] = $detail;
                $display_transaction['amount'] = $running_subtotal;
                $display_transaction['text_key'] = $this_text_key;
                $display_transaction['summary_type'] = $summary_type;
                // Use the data from the most recent $display_output as a shortcut to get some
                // of the useful values.
                $display_output .= get_display_row($summary_data, $this_summary_type, $running_total, 'summary_row');
              }
            // Otherwise there is nothing to do
          }
      } // end foreach
    if ($_REQUEST['delivery_id'])
      {
        // Get a beginning row for the ledger...
        $running_total += $total_after;
        $extra_transaction['amount'] = $total_after;
        $extra_transaction['detail'] = 'more recent activity';
        $display_output .= get_display_row($extra_transaction, '', $running_total, 'extra_row');
      }
    if (! $number_of_rows)
      {
        $display_output = 'No information to display';
      };
    echo $display_output;
    exit(0);
  }


function get_display_row($transaction_data, $summary_type, $running_total, $display_type)
  {
    // Set CSS translations (this array is defined near the top of the page
    global $css_trans_array;
    // The next row starts with values like text_key="customer fee" and delivery_id=42 to get soemthing like: cf42
    $css_group_class = $css_trans_array[$transaction_data['text_key']].'-'.$transaction_data['summary_type'].'-'.$transaction_data[$transaction_data['summary_type']];

    $unique_row_id = 'tid_'.$transaction_data['transaction_id'];
    $running_total = number_format ($running_total, 2); // Format running total
    // For detail rows in unlocked baskets, provide action linkages
    if (! $transaction_data['locked'])
      {
        $row_click_script = '<img class="control" src="'.DIR_GRAPHICS.'edit_icon.png" onclick="row_click('.
          $transaction_data['transaction_id'].','.
          $transaction_data['bpid'].')">';
      }
    // $row_type should be [singleton_row|summary_row|detail_row]
    if ($display_type == 'singleton_row')
      {
        $hide_running_total = ''; // Not hidden
        $hide_whole_row = ''; // Not hidden
        $more_symbol = '';
        $more_less_script = '';
        $css_group_class = ' summary_row singleton_'.$transaction_data['summary_type']; // Treat it like a summary row for CSS
      }
    elseif ($display_type == 'summary_row')
      {
        $row_click_script = '';
        $hide_running_total = ''; // Not hidden
        $hide_whole_row = ''; // Not hidden
        $more_symbol = 'show';
        $more_less_script = '<span class="more_less" onclick="this.innerHTML=show_hide_detail(\''.$css_group_class.'\',this.innerHTML)">'.$more_symbol.'</span>';
        // Now that more_less is set, we can do this...
        $css_group_class = ' summary_row summary_'.$transaction_data['summary_type']; // Clobber to prevent hiding itself
      }
    elseif ($display_type == 'detail_row')
      {
        $hide_running_total = 'hid '; // Hidden
        $hide_whole_row = 'hid '; // Hidden
        $more_symbol = '';
        $more_less_script = '';
      }
    elseif ($display_type == 'extra_row')
      {
        $row_click_script = '';
        $hide_running_total = '';
        $hide_whole_row = '';
        $more_symbol = '';
        $more_less_script = '';
        $css_group_class = ' extra_row'; // Treat it like a summary row for CSS
      }
    $type_text_key = $transaction_data['text_key'].
      ($transaction_data['transaction_group_id'] && $transaction_data['text_key'] ? '<br>(' : '').
      $transaction_data['transaction_group_id'].
      ($transaction_data['transaction_group_id'] ? ')' : '');

    $response = '
      <tr id="'.$unique_row_id.'" class="'.$hide_whole_row.$css_group_class.'">
        <td class="control">'.$row_click_script.'</td>
        <td class="scope">'.$transaction_data['basket_id'].'<br>'.$css_group_class.'</td>
        <td class="timestamp">'.$transaction_data['timestamp'].'</td>
        <td class="from_to">'.$transaction_data['display_to_from'].'</td>
        <td class="text_key">'.$type_text_key.'</td>
        <td class="quantity">'.$transaction_data['display_quantity'].'</td>
        <td class="detail">'.$transaction_data['detail'].'</td>
        <td class="more_less">'.$more_less_script.'</td>
        <td class="amount">'.number_format ($transaction_data['amount'], 2).'</td>
        <td class="'.$hide_running_total.'balance">'.$running_total.'&nbsp;</td>
      </tr>';
    return ($response);
  }
?>
