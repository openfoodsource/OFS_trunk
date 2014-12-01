<?php
include_once 'config_openfood.php';
session_start();

if (CurrentMember::auth_type('cashier') &&
  $call_ajax_as_function != true)
  {
    echo receive_payments_detail($_POST);
  }

function receive_payments_detail($argument)
  {
    global $connection;




    switch ($argument['request'])
      {
// BASKET TOTAL AND PAYMENTS ******************************************************
        case 'basket_total_and_payments':
          $non_payment_total = 0;
          $non_payment_count = 0;
          $payment_total = 0;
          $payment_count = 0;
          $query = '
            SELECT
              text_key,
              SUM(amount) AS total,
              COUNT(amount) AS count
            FROM '.NEW_TABLE_LEDGER.'
            WHERE
              basket_id = "'.mysql_real_escape_string($argument['basket_id']).'"
              AND (source_type = "member"
                OR target_type = "member")
              AND replaced_by IS NULL
            GROUP BY
              text_key';
          $result = @mysql_query($query,$connection) or die(debug_print ("ERROR: 768933 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
          while ( $row = mysql_fetch_array($result) )
            {
              // Is this a payment -- something we want to show explicitly?
              if ($row['text_key'] == 'payment received')
                {
                  $payment_total = $row['total'];
                  $payment_count = $row['count'];
                }
              // Total up all other charges and credits
              else
                {
                  $non_payment_total += $row['total'];
                  $non_payment_count += $row['count'];
                  $non_payment_text_key = $row['text_key'];
                }
            }
          // We return (possibly) two rows...
          // The "everything else" row
          if ($non_payment_count == 0)
            {}// *** NO ROW ***
          elseif ($non_payment_count == 1)
            $return_data = '
            <div id="non_payment'.$argument['basket_id'].'" class="data_row">
              <span id="non_payment'.$argument['basket_id'].'" class="description">'.$non_payment_text_key.'</span>
              <span class="total">'.number_format ($non_payment_total, 2).'</span>
            </div>';
          else // non_payment_count > 1
            $return_data = '
            <div id="non_payment'.$argument['basket_id'].'" class="data_row">
              <span class="description">summary total</span>
              <span class="total">'.number_format ($non_payment_total, 2).'</span>
            </div>';
          // Now the "payments" row
          if ($payment_count == 0)
            {}// *** NO ROW ***
          elseif ($payment_count == 1)
            $return_data .= '
            <div id="payment'.$argument['basket_id'].'" class="data_row">
              <span id="payment'.$argument['basket_id'].'" class="description">payment</span>
              <span class="total">'.number_format ($payment_total, 2).'</span>
            </div>';
          else // payment_count > 1
            $return_data .= '
            <div id="payment'.$argument['basket_id'].'" class="data_row">
              <span class="description">payment total</span>
              <span class="total">'.number_format ($payment_total, 2).'</span>
            </div>';
          return $return_data;
          break;
// BASKET TOTAL ONLY ******************************************************
        case 'basket_total_only':
          break;
// BASKET TOTAL ONLY ******************************************************
        case 'basket_summary';
          break;
      }
  }



