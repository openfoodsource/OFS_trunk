<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,member_admin'); // Disable this line to allow member access to their own information
include_once ('func.check_membership.php');

// Restrict view to member_admin and cashier except for a person's own information
if (CurrentMember::auth_type('member_admin,cashier') &&
     isset ($_GET['member_id']))
  {
    $member_id = $_GET['member_id'];
  }
else
  {
    $member_id = $_SESSION['member_id'];
  }

// Process any updates
// --- NONE ---

// Do queries and create content
$query_member_info = '
  SELECT
    *
  FROM '.TABLE_MEMBER.'
  LEFT JOIN '.TABLE_MEMBERSHIP_TYPES.' USING (membership_type_id)
  WHERE
    member_id = "'.mysql_real_escape_string ($member_id).'"';

$result_member_info = @mysql_query($query_member_info, $connection) or die(debug_print ("ERROR: 785033 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));

$member_data_found = false;
if ( $row_member_info = mysql_fetch_array($result_member_info) )
  {
    $member_data_found = true;
  }

$renewal_info = check_membership_renewal (get_membership_info ($member_id));

$member_content = '
  <div id="member_info_main">
    <div id="member_status">
      <span class="member_id">'.$row_member_info['member_id'].'</span>
      <span class="pending">'.($row_member_info['pending'] == 1 ? 'PENDING' : '').'</span>
      <span class="discontinued">'.($row_member_info['membership_discontinued'] == 1 ? 'DISCONTINUED' : '').'</span>
    </div>
    <div id="shopping_status">
      <span class="customer_fee">'.number_format ($row_member_info['customer_fee_percent'], 3).'%</span>
      <span class="tax_exempt">'.($row_member_info['mem_taxexempt'] == 1 ? '(TAX EXEMPT)' : '').'</span>
      <span class="auth_type">'.implode (', ', explode (',', strtr ($row_member_info['auth_type'], '_', ' '))).'</span>
    </div>
    <div id="member_name">
      <span class="username">'.$row_member_info['username'].'</span>
      <span class="preferred_name">'.$row_member_info['preferred_name'].'</span>
    </div>
  </div>
  <div id="membership_info">
    <div id="membership">
      <span class="membership_type">'.$row_member_info['membership_class'].'</span>
      <span class="membership_date">'.$row_member_info['membership_date'].'</span>
      &nbsp; &mdash; &nbsp;
      <span class="standard_renewal_date'.(strtotime ($renewal_info['standard_renewal_date']) < strtotime ("now") ? ' expired' : '').'">'.$renewal_info['standard_renewal_date'].'</span>
    </div>
  </div>';

// Preload internal account names
$query_account = '
  SELECT
    *
  FROM '.NEW_TABLE_ACCOUNTS.'
  WHERE
    1';
$result_account = @mysql_query($query_account, $connection) or die(debug_print ("ERROR: 759335 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ($row_account = mysql_fetch_array($result_account) )
  {
    $account[$row_account['account_id']]['internal_subkey'] = $row_account['internal_subkey'];
    $account[$row_account['account_id']]['account_number'] = $row_account['account_number'];
    $account[$row_account['account_id']]['sub_account_number'] = $row_account['sub_account_number'];
    $account[$row_account['account_id']]['description'] = $row_account['description'];
  }

$member_content .= '
  <table id="ledger">
    <tr>
      <th>Date</th>
      <th>Reason<span style="font-weight:normal;font-size:80%;margin-left:1em;">(click for invoice)</span></th>
      <th class="money">Charges</th>
      <th class="money">Payments</th>
      <th class="money">Total</th>
    </tr>';

$running_total = 0;
$extra_lap = true;
if ($_GET['restrict'] == 'true')
  {
    $new_accounting_restriction = 'AND (IF('.TABLE_ORDER_CYCLES.'.delivery_date IS NOT NULL, '.TABLE_ORDER_CYCLES.'.delivery_date, DATE(effective_datetime)) > "2013-03-01")';
  }

// Get the ledger history for this member
$query_ledger = '
  SELECT
    '.NEW_TABLE_LEDGER.'.basket_id AS basket_id,
    '.NEW_TABLE_LEDGER.'.delivery_id AS delivery_id,
    '.NEW_TABLE_LEDGER.'.text_key AS text_key,
    SUM(amount * IF(source_type="member", -1, 1)) AS amount,
    IF('.TABLE_ORDER_CYCLES.'.delivery_date IS NOT NULL, '.TABLE_ORDER_CYCLES.'.delivery_date, DATE(effective_datetime)) AS date
  FROM '.NEW_TABLE_LEDGER.'
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    (
      (source_type = "member"
      AND source_key = "'.mysql_real_escape_string ($member_id).'")
    OR
      (target_type = "member"
      AND target_key = "'.mysql_real_escape_string ($member_id).'")
    )
    AND replaced_by IS NULL
    '.$new_accounting_restriction.'
  GROUP BY
    date,
    basket_id,
    text_key
  ORDER BY
    date';
// echo "<pre>$query_ledger</pre>";
$result_ledger = @mysql_query($query_ledger, $connection) or die(debug_print ("ERROR: 784032 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// Cycle through all the rows... then one last (bogus) row for processing)
while (($row_ledger = mysql_fetch_array($result_ledger)) || $last_row++ < 1)
  {
    $basket_id = $row_ledger['basket_id'];
    $delivery_id = $row_ledger['delivery_id'];
    $text_key = $row_ledger['text_key'];
    $amount = $row_ledger['amount'];
    $date = $row_ledger['date'];
    // Stop AFTER the last row (rather than WITH the last row (so we have a chance to display the last row of data).
    if ($row_ledger['date'] == '') $extra_lap = false;
    // Start a new row if it is a new date and post information for the prior row
    if ($date != $date_prior)
      {
        if ($purchases_total != 0 || $payments_total != 0)
          {
            $running_total += $purchases_total + $payments_total;
            $member_content .= '
              <tr>
                <td>'.$date_prior.'</td>
                <td class="purchase">'.($delivery_id_prior ? '<a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id_prior.'&member_id='.$member_id.'" target="_blank">Delivery #'.$delivery_id_prior : '').($basket_id_prior ? ' (basket #'.$basket_id_prior.')</a>' : '').'</td>
                <td class="money '.($purchases_total < -0.005 ? 'red' : 'black').'">'.number_format ($purchases_total, 2).'</td>
                <td class="money '.($payments_total < -0.005 ? 'red' : 'black').'">'.number_format ($payments_total, 2).'</td>
                <td class="money '.($running_total < -0.005 ? 'red' : 'black').'">'.number_format ($running_total, 2).'</td>
              </tr>';
            $purchases_total = 0;
            $payments_total = 0;
          }
//         if ($payments_total != 0)
//           {
//             $running_total ;
//             $member_content .= '
//               <tr>
//                 <td>'.$date_prior.'</td>
//                 <td class="payment">Payments (delivery #'.$delivery_id_prior.' <a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id_prior.'&member_id='.$member_id.'" target="_blank">basket #'.$basket_id_prior.'</a>)</td>
//                 <td class="money '.($payments_total < -0.005 ? 'red' : 'black').'">'.number_format ($payments_total, 2).'</td>
//                 <td class="money '.($running_total < -0.005 ? 'red' : 'black').'">'.number_format ($running_total, 2).'</td>
//               </tr>';
//             $payments_total = 0;
//           }
        if ($other_total != 0)
          {
            $running_total += $other_total;
            $member_content .= '
              <tr>
                <td>'.$date_prior.'</td>
                <td class="other">Other ('.$other_reason.')</td>
                <td class="money '.($other_total < -0.005 ? 'red' : 'black').'">'.number_format ($other_total, 2).'</td>
                <td></td>
                <td class="money '.($running_total < -0.005 ? 'red' : 'black').'">'.number_format ($running_total, 2).'</td>
              </tr>';
            $other_total = 0;
            $other_reason = '';
          }
      }
    switch ($text_key)
      {
        case 'customer fee':
          $purchases_total += $amount;
          break;
        case 'quantity cost':
          $purchases_total += $amount;
          break;
        case 'weight cost':
          $purchases_total += $amount;
          break;
        case 'extra charge':
          $purchases_total += $amount;
          break;
        case 'delivery cost':
          $purchases_total += $amount;
          break;
        case 'city tax':
          $purchases_total += $amount;
          break;
        case 'state tax':
          $purchases_total += $amount;
          break;
        case 'county tax':
          $purchases_total += $amount;
          break;
        case 'payment received':
          $payments_total += $amount;
          break;
        default:
          $other_reason .= $text_key.' ';
          $other_total += $amount;
          break;
      }
    $date_prior = $date;
    $basket_id_prior = $basket_id;
    $delivery_id_prior = $delivery_id;
  }

$member_content .= '
  </table>';


// // Values from the members table                                      // // Values from the membership_types table
// member_id                        // work_city                         // set_auth_type
// pending                          // work_state                        // initial_cost
// username                         // work_zip                          // order_cost
// password                         // email_address                     // order_cost_type
// auth_type                        // email_address_2                   // customer_fee_percent
// business_name                    // home_phone                        // producer_fee_percent
// preferred_name                   // work_phone                        // membership_class
// last_name                        // mobile_phone                      // membership_description
// first_name                       // fax                               // pending
// last_name_2                      // toll_free                         // enabled_type
// first_name_2                     // home_page                         // may_convert_to
// no_postal_mail                   // membership_type_id                // renew_cost
// address_line1                    // customer_fee_percent              // expire_after
// address_line2                    // membership_date                   // expire_type
// city                             // last_renewal_date                 // expire_message
// state                            // membership_discontinued
// zip                              // mem_taxexempt
// county                           // mem_delch_discount
// work_address_line1               // how_heard_id
// work_address_line2               // notes


// // $num_members = mysql_numrows($result_member_info);

// Prepare page for display
$page_specific_css = '
  <style type="text/css">
    #member_info_main {
      position:relative;
      width:90%;
      height:100px;
      }
    #member_status {
      float:left;
      height:100px;
      width:20%;
      padding:5px;
      }
    .member_id {
      font-size:65px;
      display:block;
      width:100%;
      text-align:center;
      color:#669;
      }
    .pending,
    .discontinued {
      font-size:75%;
      }
    #shopping_status {
      float:left;
      height:100px;
      width:20%;
      padding:5px;
      }
    .customer_fee {
      font-size:150%;
      display:block;
      width:100%;
      text-align:center;
      color:#800;
      }
    .tax_exempt {
      font-size:75%;
      }
    .auth_type {
      display:block;
      max-height:60px;
      font-size:70%;
      overflow-y:auto;
      color:#006;
      }
    #member_name {
      float:left;
      height:100px;
      width:60%;
      padding:5px;
      }
    .preferred_name {
      display:block;
      width:100%;
      height:80%;
      font-size:200%;
      color:#333;
      text-align:center;
      }
    .username {
      display:block;
      width:100%;
      height:20%;
      color:#640;
      font-size:120%;
      font-family:Courier;
      font-weight:bold;
      text-align:center;
      }
    #membership_info {
      position:relative;
      width:90%;
      height:25px;
      }
    .membership_type {
      display:block;
      float:left;
      text-align:center;
      width:20%;
      font-size:130%;
      }
    .membership_date {
      font-size:130%;
      }
    .standard_renewal_date {
      font-size:130%;
      }
    .expired {
      color:#800;
      }
    #ledger {
      width:90%;
      margin:2em auto;
      border:1px solid #000;
      border-collapse:collapse;
      box-shadow:6px 6px 15px #888888;
      background-color:#eee;
      }
    #ledger tr th,
    #ledger tr:hover {
      background-color:#ffa;
      }

    #ledger tr td,
    #ledger tr th {
      padding:1px 5px;
      border:1px dotted #aaa;
      }
    .purchase {
      color:#000;
      }
    .red {
      color:#800;
      }
    .black {
      color:#000;
      }
    .payment {
      color:#060;
      }
    .other {
      color:#007;
      }
    .money {
      text-align:right;
      }
  </style>';

$page_title_html = '<span class="title">Member Information</span>';
$page_subtitle_html = '<span class="subtitle">Details for '.$row_member_info['preferred_name'].'</span>';
$page_title = 'Member Information: Details for ';
$page_tab = 'member_admin_panel';

if($_GET['display_as'] == 'popup')
  $display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$member_content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

