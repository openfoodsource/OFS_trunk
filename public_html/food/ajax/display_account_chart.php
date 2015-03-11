<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

// $_POST = $_GET; // FOR DEBUGGING

// $account_key = isset($_POST['account_key']) ? mysql_real_escape_string ($_POST['account_key']) : '';
$account_type = isset($_POST['account_type']) ? mysql_real_escape_string ($_POST['account_type']) : '';
$data_page = isset($_POST['data_page']) ? mysql_real_escape_string ($_POST['data_page']) : 1;
$per_page = isset($_POST['per_page']) ? mysql_real_escape_string ($_POST['per_page']) : PER_PAGE;
$per_page = 25;
$top_special_markup = '';
$limit_clause = mysql_real_escape_string (floor (($data_page - 1) * $per_page).", ".floor ($per_page));

// echo "<pre>".print_r($_POST,true)."</pre>";

if ($account_type == '')
  {
    echo "Invalid request. Invalid account number or type.";
    exit (1);
  }

// Set up the appropriate queries for the chart of accounts, depending on the account type

switch ($account_type)
  {
    case "member":
      $query = '
        SELECT
          SQL_CALC_FOUND_ROWS
          member_id AS account_key,
          member_id AS account_number,
          CONCAT(
            preferred_name,
            IF(business_name != "", CONCAT(" (", business_name, ")"), "")) AS account_description,
          SUM(account_balance) AS account_balance
        FROM
          (
            (SELECT
              member_id,
              preferred_name,
              business_name,
              SUM(amount
                * IF(source_key = member_id AND source_type="member", -1, 1)
                * IF(replaced_by IS NULL, 1, 0)
                ) AS account_balance
            FROM
              '.NEW_TABLE_LEDGER.'
            LEFT JOIN
              '.TABLE_MEMBER.' ON (source_key = member_id)
            WHERE
              source_type = "member"
              AND source_key = member_id
            GROUP BY member_id)
          UNION ALL
            (SELECT
              member_id,
              preferred_name,
              business_name,
              SUM(amount
                * IF(target_key = member_id AND target_type="member", 1, -1)
                * IF(replaced_by IS NULL, 1, 0)
                ) AS account_balance
            FROM
              '.NEW_TABLE_LEDGER.'
            LEFT JOIN
              '.TABLE_MEMBER.' ON (target_key = member_id)
            WHERE
              target_type = "member"
              AND target_key = member_id
            GROUP BY member_id)
          ) foo
        GROUP BY member_id
        ORDER BY member_id
        LIMIT '.$limit_clause;
    break;

    case "producer":
      $query = '
        SELECT
          SQL_CALC_FOUND_ROWS
          producer_id AS account_key,
          producer_id AS account_number,
          business_name AS account_description,
          ( SELECT SUM(amount
              * IF(source_key = producer_id AND source_type="producer", -1, 1)
              * IF(replaced_by IS NULL, 1, 0))
            FROM
              '.NEW_TABLE_LEDGER.'
            WHERE
              ( source_type = "producer"
                AND source_key = producer_id)
              OR
              ( target_type = "producer"
                AND target_key = producer_id)
          ) AS account_balance
        FROM
          '.TABLE_PRODUCER.'
        ORDER BY producer_id
        LIMIT '.$limit_clause;
    break;

    case "internal":
      $query = '
        SELECT
          SQL_CALC_FOUND_ROWS
          /* account_id AS account_number */
          account_id AS account_key,
          CONCAT(
            account_number, " / ", sub_account_number) AS account_number,
            description AS account_description,
          ( SELECT SUM(amount
              * IF(source_key = account_id AND source_type="internal", -1, 1)
              * IF(replaced_by IS NULL, 1, 0))
            FROM
              '.NEW_TABLE_LEDGER.'
            WHERE
              ( source_type = "internal"
                AND source_key = account_id)
              OR
              ( target_type = "internal"
                AND target_key = account_id)
          ) AS account_balance
        FROM
          '.NEW_TABLE_ACCOUNTS.'
        ORDER BY
          account_number,
          sub_account_number,
          account_id
        LIMIT '.$limit_clause;
      $top_special_markup = '
        <div class="add_link"><a onclick="popup_src(\'edit_account.php?action=add&account_key=1\');">Add new account</a></div>';
    break;

    case "tax":
      $query = '
        SELECT
          SQL_CALC_FOUND_ROWS
          region_code AS account_key,
          region_code AS account_number,
          CONCAT(
            region_type, ": ", region_name) AS account_description,
          SUM(account_balance) AS account_balance
        FROM
          (
            (SELECT
              region_code,
              region_type,
              region_name,
              SUM(amount
                * IF(source_key = tax_id AND source_type = "tax", -1, 1)
                * IF(replaced_by IS NULL, 1, 0)
                ) AS account_balance
            FROM
              '.NEW_TABLE_LEDGER.'
            LEFT JOIN
              '.NEW_TABLE_TAX_RATES.' ON (tax_id = source_key)
            WHERE
              source_type = "tax"
            GROUP BY region_code)
          UNION ALL
            (SELECT
              region_code,
              region_type,
              region_name,
              SUM(amount
                * IF(target_key = tax_id AND target_type = "tax", 1, -1)
                * IF(replaced_by IS NULL, 1, 0)
                ) AS account_balance
            FROM
              '.NEW_TABLE_LEDGER.'
            LEFT JOIN
              '.NEW_TABLE_TAX_RATES.' ON (tax_id = target_key)
            WHERE
              target_type = "tax"
            GROUP BY region_code)
          ) foo
        GROUP BY region_code
        ORDER BY FIND_IN_SET(region_type, "state,county,city"), region_name
        LIMIT '.$limit_clause;
    break;
  }

// $ledger_data .= "<pre>$query</pre>";
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 756930 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// Get the total number of rows (for pagination) -- not counting the LIMIT condition
$query_found_accounts = '
  SELECT
    FOUND_ROWS() AS found_accounts';
$result_found_accounts = @mysql_query($query_found_accounts, $connection) or die(debug_print ("ERROR: 759323 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$row_found_accounts = mysql_fetch_array($result_found_accounts);
$found_accounts = $row_found_accounts['found_accounts'];

$found_pages = ceil ($found_accounts / $per_page);

while ($row = mysql_fetch_array($result))
  {
    // Set up the edit links (easiest just to set up all possibilities)
      $edit_link['member'] = '
        <div class="edit_link" onclick="popup_src(\'edit_member.php?action=edit&member_id='.$row['account_key'].'&display_as=popup\', \'edit_member\');">Edit</a></div>';
      $edit_link['producer'] = '
        <div class="edit_link" onclick="popup_src(\'edit_producer.php?action=edit&producer_id='.$row['account_key'].'&display_as=popup\', \'edit_producer\');">Edit</a></div>';
      $edit_link['internal'] = '
        <div class="edit_link" onclick="popup_src(\'edit_account.php?action=edit&account_key='.$row['account_key'].'&display_as=popup\', \'edit_member\');">Edit</a></div>';
      $edit_link['tax'] = '';
    // build the ledger output
    $ledger_data['markup'] .= '
      <div id="id-'.$row['account_number'].'" class="account_row '.$account_type.'">
        '.$top_special_markup.'
        <div class="account_number">'.$row['account_number'].'</div>
        <div class="account_description"><a href="'.PATH.'view_account.php?account_type='.$account_type.'&account_key='.$row['account_key'].'&account_name='.$row['account_description'].'" target="_blank">'.$row['account_description'].'</a></div>
        <div class="account_balance">'.number_format ($row['account_balance'], 2).'</div>'.
        $edit_link[$account_type].'
      </div>';
    $top_special_markup = '';
  }

$ledger_data['query'] = $query;
$ledger_data['maximum_data_page'] = $found_pages;
$ledger_data['data_page'] = $data_page;
echo json_encode ($ledger_data);

?>