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
    member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';

$result_member_info = @mysqli_query ($connection, $query_member_info) or die (debug_print ("ERROR: 265033 ", array ($query_member_info, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));

$member_data_found = false;
if ( $row_member_info = mysqli_fetch_array ($result_member_info, MYSQLI_ASSOC) )
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
$result_account = @mysqli_query ($connection, $query_account) or die (debug_print ("ERROR: 759335 ", array ($query_account, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row_account = mysqli_fetch_array ($result_account) )
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
      <th>Delivery<span style="font-weight:normal;font-size:80%;margin-left:1em;">(click for invoice)</span></th>
      <th>Notation</th>
      <th class="money">Costs</th>
      <th class="money">Payments</th>
      <th class="money">Total</th>
    </tr>';

$running_total = 0;

// Get the ledger history for this member
$query_ledger = '
SELECT *
FROM
  (
  SELECT
    '.NEW_TABLE_LEDGER.'.basket_id AS basket_id,
    '.NEW_TABLE_LEDGER.'.delivery_id AS delivery_id,
    IF('.NEW_TABLE_LEDGER.'.text_key LIKE "%tax", "tax", '.NEW_TABLE_LEDGER.'.text_key) AS text_key,
    SUM(amount * IF(source_type="member", -1, 1)) AS amount,
    COALESCE('.TABLE_ORDER_CYCLES.'.delivery_date, DATE(effective_datetime)) AS date
  FROM '.NEW_TABLE_LEDGER.'
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    (
      (source_type = "member"
      AND source_key = "'.mysqli_real_escape_string ($connection, $member_id).'")
    OR
      (target_type = "member"
      AND target_key = "'.mysqli_real_escape_string ($connection, $member_id).'")
    )
    AND replaced_by IS NULL
    AND (IF('.TABLE_ORDER_CYCLES.'.delivery_date IS NOT NULL, '.TABLE_ORDER_CYCLES.'.delivery_date, DATE(effective_datetime)) > "'.ACCOUNTING_ZERO_DATETIME.'")
    AND basket_id IS NOT NULL
  GROUP BY
    basket_id,
    text_key
UNION /* BECAUSE THESE GROUP DIFFERENTLY */
  SELECT
    "N/A" AS basket_id,
    "N/A" AS delivery_id,
    IF('.NEW_TABLE_LEDGER.'.text_key LIKE "%tax", "tax", '.NEW_TABLE_LEDGER.'.text_key) AS text_key,
    SUM(amount * IF(source_type="member", -1, 1)) AS amount,
    DATE(effective_datetime) AS date
  FROM '.NEW_TABLE_LEDGER.'
  WHERE
    (
      (source_type = "member"
      AND source_key = "'.mysqli_real_escape_string ($connection, $member_id).'")
    OR
      (target_type = "member"
      AND target_key = "'.mysqli_real_escape_string ($connection, $member_id).'")
    )
    AND replaced_by IS NULL
    AND (DATE(effective_datetime) > "'.ACCOUNTING_ZERO_DATETIME.'")
    AND basket_id IS NULL
  GROUP BY
    DATE(effective_datetime),
    text_key
  ) bar
WHERE 1
ORDER BY
  date,
  FIND_IN_SET(text_key, "payment received,membership dues,customer fee,quantity cost,weight cost,extra charge,delivery cost,tax") DESC';
$result_ledger = @mysqli_query ($connection, $query_ledger) or die(debug_print ("ERROR: 784032 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
// Cycle through all the rows... then one last (bogus) row for processing)
while (($row_ledger = mysqli_fetch_array ($result_ledger, MYSQLI_ASSOC)) || $last_row++ < 1)
  {
    $basket_id = $row_ledger['basket_id'];
    $delivery_id = $row_ledger['delivery_id'];
    $text_key = $row_ledger['text_key'];
    $amount = $row_ledger['amount'];
    $date = $row_ledger['date'];
    if ($text_key == 'customer fee'
        || $text_key == 'quantity cost'
        || $text_key == 'weight cost'
        || $text_key == 'extra charge'
        || $text_key == 'delivery cost'
        || substr($text_key, -3, 3) == 'tax')
      {
        $purchases_total += $amount;
        $notation = 'purchases';
      }
    elseif ($text_key == 'payment received')
      {
        $payments_total += $amount;
        $notation = 'payment received';
      }
    elseif ($text_key == 'membership dues')
      {
        $other_total += $amount;
        $notation = 'membership dues';
      }
    elseif ($amount < 0)
      {
        $payments_total += $amount;
        $notation = $text_key;
      }
    elseif ($amount > 0)
      {
        $other_total += $amount;
        $notation = $text_key;
      }
    // Post information for the prior row; otherwise continue aggregating information
    if ($notation_prior != ''
        && ($date != $date_prior
          || $notation != $notation_prior)
        && ($purchases_total_prior != 0
          || $payments_total_prior != 0
          || $other_total_prior != 0))
      {
          {
            $costs_total_prior = $other_total_prior + $purchases_total_prior;
            $running_total += $payments_total_prior + $costs_total_prior;
            $member_content .= '
              <tr>
                <td>'.$date_prior.'</td>
                <td class="purchase">'.($delivery_id_prior != 'N/A' ? ($delivery_id_prior ? '<a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id_prior.'&member_id='.$member_id.'" target="_blank">Delivery '.$delivery_id_prior.'</a>' : '').($basket_id_prior ? ' &mdash; Invoice #'.$basket_id_prior : '') : '').'</td>
                <td class="notation">'.$notation_prior.'</td>
                <td class="money '.($costs_total_prior < -0.005 ? 'red' : 'black').'">'.(number_format ($costs_total_prior, 2) != '0.00' ? number_format ($costs_total_prior, 2) : '').'</td>
                <td class="money '.($payments_total_prior < -0.005 ? 'red' : 'black').'">'.(number_format ($payments_total_prior, 2) != '0.00' ? number_format ($payments_total_prior, 2) : '').'</td>
                <td class="money '.($running_total < -0.005 ? 'red' : 'black').'">'.number_format ($running_total, 2).'</td>
              </tr>';
            $costs_total_prior = 0;
            $purchases_total_prior = 0;
            $payments_total_prior = 0;
            $other_total_prior = 0;
          }
      }
    $date_prior = $date;
    $basket_id_prior = $basket_id;
    $delivery_id_prior = $delivery_id;
    $notation_prior = $notation;
    $purchases_total_prior += $purchases_total;
    $payments_total_prior += $payments_total;
    $other_total_prior += $other_total;
    $purchases_total = 0;
    $payments_total = 0;
    $other_total = 0;
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


// // $num_members = mysqli_num_rows ($result);

// Prepare page for display
$page_specific_css = '
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

    .note {
      clear:both;
      margin:0 2em;
      color:#800;
      }';

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
