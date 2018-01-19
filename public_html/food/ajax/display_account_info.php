<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

//$_POST = $_GET; // For debugging queries

$account_key = isset($_POST['account_key']) ? mysqli_real_escape_string ($connection, $_POST['account_key']) : '';
$account_type = isset($_POST['account_type']) ? mysqli_real_escape_string ($connection, $_POST['account_type']) : '';
$data_page = isset($_POST['data_page']) ? mysqli_real_escape_string ($connection, $_POST['data_page']) : 1;
$per_page = isset($_POST['per_page']) ? mysqli_real_escape_string ($connection, $_POST['per_page']) : PER_PAGE;

$limit_begin_row = ($data_page - 1) * $per_page;
$limit_query = mysqli_real_escape_string ($connection, floor ($limit_begin_row).", ".floor ($per_page));

if ($account_key == '' || $account_type == '')
  {
    echo "Invalid request. Invalid account number or type.";
    exit (1);
  }

// Set the accounting datetime limit (for constraining totals over time beginning with the zero-datetime)
$constrain_accounting_datetime = '
              AND effective_datetime < "0"';
if (defined ('ACCOUNTING_ZERO_DATETIME') && strlen (ACCOUNTING_ZERO_DATETIME) > 0)
  {
    // The amount we will subtract from balances in order to cross zero at the "zero datetime"
    $constrain_accounting_datetime = '
              AND effective_datetime < "'.ACCOUNTING_ZERO_DATETIME.'"';
  }

if ($account_type == 'tax') // Handle the tax query differently because we want to group tax-codes rather than tax_ids
  {
    // Query for the running total of the current set of transactions
    $query_balance = '
      SELECT
        SUM(amount) AS running_total
      FROM
        (SELECT
          transaction_id,
          amount
        FROM
          (
            (SELECT
              transaction_id,
              amount * IF(replaced_by IS NULL, -1, 0) AS amount
            FROM
              '.NEW_TABLE_LEDGER.'
            LEFT JOIN
              '.NEW_TABLE_TAX_RATES.' ON (tax_id = source_key)
            WHERE
              source_type = "tax"
              AND region_code="'.$account_key.'"CONSTRAINT)
          UNION ALL
             (SELECT
              transaction_id,
              amount * IF(replaced_by IS NULL, 1, 0) AS amount
            FROM
              '.NEW_TABLE_LEDGER.'
            LEFT JOIN
              '.NEW_TABLE_TAX_RATES.' ON (tax_id = target_key)
            WHERE
              target_type = "tax"
              AND region_code="'.$account_key.'"CONSTRAINT)
          ) foo
        ORDER BY transaction_id DESC
        LIMIT) bar';
    // Query the actual transactions
    $query_data = '
      SELECT
        SQL_CALC_FOUND_ROWS
        *
      FROM
        (
          (SELECT
            transaction_id,
            transaction_group_id,
            source_type,
            region_code AS source_key,
            region_code AS source_name,
            target_type,
            target_key,
            CASE target_type
              WHEN "member" THEN (SELECT preferred_name FROM '.TABLE_MEMBER.' WHERE member_id = target_key)
              WHEN "producer" THEN (SELECT business_name FROM '.TABLE_PRODUCER.' WHERE producer_id = target_key)
              WHEN "internal" THEN (SELECT description FROM '.NEW_TABLE_ACCOUNTS.' WHERE account_id = target_key)
              WHEN "tax" THEN (SELECT region_code FROM '.NEW_TABLE_TAX_RATES.' WHERE tax_id = target_key)
            END AS target_name,
            region_type,
            region_name,
            amount * IF(replaced_by IS NULL, -1, 0) AS amount,
            text_key,
            effective_datetime,
            posted_by,
            replaced_by,
            replaced_datetime,
            timestamp,
            basket_id,
            bpid,
            site_id AS site_id_source,
              IF(site_id, (SELECT site_short FROM '.NEW_TABLE_SITES.' WHERE site_id = site_id_source), "") AS site_name,
            delivery_id AS delivery_id_source,
              IF(delivery_id, (SELECT delivery_date FROM '.TABLE_ORDER_CYCLES.' WHERE delivery_id = delivery_id_source), "") AS delivery_date,
            pvid AS pvid_source,
              IF(pvid, (SELECT product_name FROM '.NEW_TABLE_PRODUCTS.' WHERE pvid = pvid_source), "") AS product_name
          FROM
            '.NEW_TABLE_LEDGER.'
          LEFT JOIN
            '.NEW_TABLE_TAX_RATES.' ON (tax_id = source_key)
          WHERE
            source_type = "tax"
            AND region_code="'.$account_key.'")
        UNION ALL
           (SELECT
            transaction_id,
            transaction_group_id,
            source_type,
            source_key,
            CASE source_type
              WHEN "member" THEN (SELECT preferred_name FROM '.TABLE_MEMBER.' WHERE member_id = source_key)
              WHEN "producer" THEN (SELECT business_name FROM '.TABLE_PRODUCER.' WHERE producer_id = source_key)
              WHEN "internal" THEN (SELECT description FROM '.NEW_TABLE_ACCOUNTS.' WHERE account_id = source_key)
              WHEN "tax" THEN (SELECT region_code FROM '.NEW_TABLE_TAX_RATES.' WHERE tax_id = source_key)
            END AS source_name,
            target_type,
            region_code AS target_key,
            region_code AS target_name,
            region_type,
            region_name,
            amount * IF(replaced_by IS NULL, 1, 0) AS amount,
            text_key,
            effective_datetime,
            posted_by,
            replaced_by,
            replaced_datetime,
            timestamp,
            basket_id,
            bpid,
            site_id AS site_id_source,
              IF(site_id, (SELECT site_short FROM '.NEW_TABLE_SITES.' WHERE site_id = site_id_source), "") AS site_name,
            delivery_id AS delivery_id_source,
              IF(delivery_id, (SELECT delivery_date FROM '.TABLE_ORDER_CYCLES.' WHERE delivery_id = delivery_id_source), "") AS delivery_date,
            pvid AS pvid_source,
              IF(pvid, (SELECT product_name FROM '.NEW_TABLE_PRODUCTS.' WHERE pvid = pvid_source), "") AS product_name
          FROM
            '.NEW_TABLE_LEDGER.'
          LEFT JOIN
            '.NEW_TABLE_TAX_RATES.' ON (tax_id = target_key)
          WHERE
            target_type = "tax"
            AND region_code="'.$account_key.'")
        ) foo
      ORDER BY transaction_id DESC
      LIMIT '.$limit_query;
  }
else
  {
    // Query for the running total of the current set of transactions
    $query_balance = '
      SELECT
        SUM(balance) AS running_total
      FROM
        (
          SELECT
            amount
            * IF(source_key = "'.$account_key.'" AND source_type = "'.$account_type.'", -1, 1)
            * IF(replaced_by IS NULL, 1, 0) AS balance
          FROM
            '.NEW_TABLE_LEDGER.'
          WHERE
            ((source_type = "'.$account_type.'"
              AND source_key = "'.$account_key.'")
            OR
            ( target_type = "'.$account_type.'"
              AND target_key = "'.$account_key.'"))CONSTRAINT
          ORDER BY transaction_id DESC
          LIMIT
        ) AS foo';
    // Query the actual transactions
    $query_data = '
      SELECT
        SQL_CALC_FOUND_ROWS
        transaction_id,
        transaction_group_id,
        source_type,
        source_key,
        CASE source_type
          WHEN "member" THEN (SELECT preferred_name FROM '.TABLE_MEMBER.' WHERE member_id = source_key)
          WHEN "producer" THEN (SELECT business_name FROM '.TABLE_PRODUCER.' WHERE producer_id = source_key)
          WHEN "internal" THEN (SELECT description FROM '.NEW_TABLE_ACCOUNTS.' WHERE account_id = source_key)
          WHEN "tax" THEN (SELECT region_code FROM '.NEW_TABLE_TAX_RATES.' WHERE tax_id = source_key)
        END AS source_name,
        target_type,
        target_key,
        CASE target_type
          WHEN "member" THEN (SELECT preferred_name FROM '.TABLE_MEMBER.' WHERE member_id = target_key)
          WHEN "producer" THEN (SELECT business_name FROM '.TABLE_PRODUCER.' WHERE producer_id = target_key)
          WHEN "internal" THEN (SELECT description FROM '.NEW_TABLE_ACCOUNTS.' WHERE account_id = target_key)
          WHEN "tax" THEN (SELECT region_code FROM '.NEW_TABLE_TAX_RATES.' WHERE tax_id = target_key)
        END AS target_name,
        amount,
        text_key,
        effective_datetime,
        posted_by,
        replaced_by,
        replaced_datetime,
        timestamp,
        basket_id,
        bpid,
        site_id AS site_id_source,
          IF(site_id, (SELECT site_short FROM '.NEW_TABLE_SITES.' WHERE site_id = site_id_source), "") AS site_name,
        delivery_id AS delivery_id_source,
          IF(delivery_id, (SELECT delivery_date FROM '.TABLE_ORDER_CYCLES.' WHERE delivery_id = delivery_id_source), "") AS delivery_date,
        pvid AS pvid_source,
          IF(pvid, (SELECT product_name FROM '.NEW_TABLE_PRODUCTS.' WHERE pvid = pvid_source), "") AS product_name
      FROM
        '.NEW_TABLE_LEDGER.'
      WHERE
        ( source_type = "'.$account_type.'"
          AND source_key = "'.$account_key.'")
        OR
        ( target_type = "'.$account_type.'"
          AND target_key = "'.$account_key.'")
      ORDER BY transaction_id DESC
      LIMIT '.$limit_query;
  }

// SPECIAL NOTE...
//
// There is a necessary order in the following steps.
// First we run the query_data in order to get a value for SQL_CALC_FOUND_ROWS
// Then we run the query_found_rows to get the total number of rows returned
// We need total number of rows in order to properly limit the query_balance
// Then we execute the query balance, which is needed for processing the original query_data


// Get the actual transactions
$result_data = mysqli_query ($connection, $query_data) or die (debug_print ("ERROR: 567289 ", array ($query_data, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));

// Get the total number of rows (for pagination) -- not counting the LIMIT condition
$query_found_rows = '
  SELECT
    FOUND_ROWS() AS found_rows';
$result_found_rows = @mysqli_query ($connection, $query_found_rows) or die (debug_print ("ERROR: 229323 ", array ($query_found_rows, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$row_found_rows = mysqli_fetch_array ($result_found_rows, MYSQLI_ASSOC);
$found_rows = $row_found_rows['found_rows'];
$found_pages = ceil ($found_rows / $per_page);

// Apply the limit to get the running total
$limit_running_total = 'LIMIT '.mysqli_real_escape_string ($connection, floor ($limit_begin_row).', '.floor ($found_rows - $limit_begin_row));
$query_balance_unlimited = str_replace('LIMIT', '', $query_balance);
$query_balance_unlimited = str_replace('CONSTRAINT', $constrain_accounting_datetime, $query_balance_unlimited);
$query_balance_limited = str_replace('LIMIT', $limit_running_total, $query_balance);
$query_balance_limited = str_replace('CONSTRAINT', '', $query_balance_limited);

// Get the running total for all transactions
$result_balance_unlimited = mysqli_query($connection, $query_balance_unlimited) or die (debug_print ("ERROR: 767392 ", array ($query_balance_unlimited, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
if ($row_balance_unlimited = mysqli_fetch_array($connection, $result_balance_unlimited))
  {
    $running_total_unlimited = $row_balance_unlimited['running_total'];
  }

// Get the running total for this set of transactions
$result_balance_limited = mysqli_query ($connection, $query_balance_limited) or die (debug_print ("ERROR: 567592 ", array ($query_balance_limited, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
if ($row_balance_limited = mysqli_fetch_array ($result_balance_limited, MYSQLI_ASSOC))
  {
    $running_total_limited = $row_balance_limited['running_total'];
  }

$running_total = $running_total_limited - $running_total_unlimited;

// Now go back and process through the original data query
while ($row = mysqli_fetch_array ($result_data, MYSQLI_ASSOC))
  {
    // Keep the desired account information on the "source" side...
    $row['delivery_id'] = $row['delivery_id_source'];
    $row['site_id'] = $row['site_id_source'];
    $row['pvid_id'] = $row['pvid_id_source'];

    // If necessary, invert sense of the source and target accounts
    if ($row['target_type'] != $account_type || $row['target_key'] != $account_key)
      {
        $temp = $row['source_type'];
        $row['source_type'] = $row['target_type'];
        $row['target_type'] = $temp;
        $temp = $row['source_key'];
        $row['source_key'] = $row['target_key'];
        $row['target_key'] = $temp;
        $temp = $row['source_name'];
        $row['source_name'] = $row['target_name'];
        $row['target_name'] = $temp;
        // And invert the value of the amount
        $row['amount'] = $row['amount'] * -1;
      }

    // Set up "replaced" transactions for style and linking with their replacements
    if ($row['replaced_by'] > 0)
      {
        $replaced_class = ' replaced';
        $replaced_action = ' onmouseover="highlight_replacement(\''.$row['replaced_by'].'\')" onmouseout="restore_replacement(\''.$row['replaced_by'].'\')"';
        // Running total is not changed for "replaced" transactions
        $running_total_next = $running_total + 0;
      }
    else
      {
        $replaced_class = '';
        $replaced_action = '';
        // Subtract from the running total because we are displaying in reverse order
        $running_total_next = $running_total - $row['amount'];
      }

    // build the ledger output
    $ledger_data['markup'] .= '
      <div id="id-'.$row['transaction_id'].'" class="data_row'.$replaced_class.'"'.$replaced_action.' onclick="popup_src(\'adjust_ledger.php?type=single&target='.$row['transaction_id'].'\', \'adjust_ledger\', \'\');">
        <div class="source_info">
          <div class="source_name">'.$row['source_name'].'</div>
          <div class="source_type">'.$row['source_type'].'</div>
          <div class="source_key">'.$row['source_key'].'</div>
        </div>
        <div class="target_info">
          <div class="target_type">'.$row['target_type'].'</div>
          <div class="target_key">'.$row['target_key'].'</div>
          <div class="target_name">'.$row['target_name'].'</div>
        </div>
        <div class="text_key">'.str_replace (' ', '&nbsp;', $row['text_key']).'</div>
        <div class="effective_datetime">'.$row['effective_datetime'].'</div>
        <div class="posted_by">'.$row['posted_by'].'</div>
        <div class="replaced_by">'.$row['replaced_by'].'</div>
        <div class="replaced_datetime">'.$row['replaced_datetime'].'</div>
        <div class="timestamp">'.$row['timestamp'].'</div>
        <div class="order_info">
          <div class="basket_id">'.$row['basket_id'].'</div>
          <div class="bpid">'.$row['bpid'].'</div>
          <div class="site_info">
            <div class="site_id">'.$row['site_id'].'&nbsp;</div>
            <div class="site_name">'.$row['site_name'].'&nbsp;</div>
          </div>
          <div class="delivery_info">
            <div class="delivery_id">'.$row['delivery_id'].'&nbsp;</div>
            <div class="delivery_date">'.$row['delivery_date'].'&nbsp;</div>
          </div>
          <div class="product_info">
            <div class="pvid">'.$row['pvid'].'&nbsp;</div>
            <div class="product_name">'.$row['product_name'].'&nbsp;</div>
          </div>
        </div>
        <div class="amount">'.number_format($row['amount'], 2).'</div>
        <div class="running_total">'.number_format($running_total, 2).'</div>
      </div>';
    $running_total = $running_total_next;
  }

//debug_print ("INFO: 545636 ", $ledger_data['markup'], basename(__FILE__).' LINE '.__LINE__);

$ledger_data['maximum_data_page'] = $found_pages;
$ledger_data['data_page'] = $data_page;
echo json_encode ($ledger_data);
