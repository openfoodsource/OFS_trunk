<?php
include_once 'config_openfood.php';
session_start();

valid_auth('site_admin,cashier');

// Constrain by Accounting Zero Date-time, if appropriate
if (defined ('ACCOUNTING_ZERO_DATETIME') && strlen (ACCOUNTING_ZERO_DATETIME) > 0)
  {
    $constrain_accounting_datetime = '
        AND effective_datetime > "'.ACCOUNTING_ZERO_DATETIME.'"';
  }
// Set the LIMIT according to PER_PAGE or query values
// Special case: per_page=0 --> show all records
if (filter_var($_GET['start_record'], FILTER_VALIDATE_INT) == true)
  {
    $start_record = filter_var($_GET['start_record'], FILTER_VALIDATE_INT);
  }
else
  {
    $start_record = 0;
  }
if (isset ($_GET['per_page']) && (filter_var($_GET['per_page'], FILTER_VALIDATE_INT) == true || $_GET['per_page'] == 0))
  {
    $per_page = filter_var($_GET['per_page'], FILTER_VALIDATE_INT);
    if ($per_page == 0)
      {
        $query_limit = '';
      }
    else
      {
        $query_limit = '
          LIMIT '.mysqli_real_escape_string ($connection, $start_record).', '.mysqli_real_escape_string ($connection, $per_page);
      }
  }
else
  {
    $per_page = PER_PAGE;
    $query_limit = '
      LIMIT '.mysqli_real_escape_string ($connection, $start_record).', '.mysqli_real_escape_string ($connection, $per_page);
  }
// Set HAVING values for query to constrain which orders are shown
// Special case balance=all --> Show all accounts regardless of balance
if ($_GET['balance'] == 'all')
  {
    $balance = 'all';
    $query_having = '';
  }
// Accounts with a specific balance including those who have never ordered
elseif (isset ($_GET['balance']) AND $_GET['balance'] != '' AND (filter_var($_GET['balance'], FILTER_VALIDATE_FLOAT) == true || $_GET['balance'] == 0))
  {
    $balance = filter_var($_GET['balance'], FILTER_VALIDATE_FLOAT);
    $query_having = '
  HAVING
    account_balance = "'.mysqli_real_escape_string ($connection, number_format ($balance * -1, 2)).'"';
  }
else // Show all non-zero accounts who have ever ordered anything
  {
    $query_having = '
  HAVING
    account_balance != 0
    AND product_count != 0';
  }
// SET WHERE to constrain display to a particular order cycle
// NOTE: Account balances are not necessarily the result of that order cycle
if (filter_var($_GET['delivery_id'], FILTER_VALIDATE_INT) == true)
  {
    $delivery_id = filter_var($_GET['delivery_id'], FILTER_VALIDATE_INT);
    $filter_delivery_id = '
    (SELECT
      member_id
      FROM '.NEW_TABLE_BASKETS.'
      WHERE delivery_id = '.mysqli_real_escape_string ($connection, $delivery_id).'
      AND member_id = foo.member_id) IS NOT NULL';
    $display_specific_cycle = true;
  }
else
  {
    $filter_delivery_id = '
    1';
    $display_specific_cycle = false;
  }
// SET THE ORDER_BY for sorting the list
if ($_GET['sort_by'] == 'account_balance'
    || $_GET['sort_by'] == 'preferred_name'
    || $_GET['sort_by'] == 'last_name'
    || $_GET['sort_by'] == 'site_short'
    || $_GET['sort_by'] == 'business_name'
    || $_GET['sort_by'] == 'member_id')
  {
    $sort_by = $_GET['sort_by'];
    $query_order_by = '
  ORDER BY '.$sort_by;
  }
else // DEFAULT = sort_by --> member_id
  {
    $query_order_by = '
  ORDER BY '.TABLE_MEMBER.'.member_id';
  }
$content_top = '
  <ul>'.
    ($per_page != 0 ? '
    <li>Showing '.$per_page.' Records per page. <a href="'.$_SERVER['SCRIPT_NAME'].'?per_page=0'.($balance ? '&balance='.$balance : '').($delivery_id ? '&delivery_id='.$delivery_id : '').($sort_by ? '&sort_by='.$sort_by : '').'">Display Full List</a>' : '').'</li>
    <li><form action='.$_SERVER['SCRIPT_NAME'].' method="GET">
      <input type="hidden" name="per_page" id="per_page" value="'.$per_page.'">
      <input type="hidden" name="start_record" id="start_record" value="'.$start_record.'">
      Show members with balance of: <input type="text" size="8" name="balance" id="balance" value="'.($balance != 0 ? number_format ($balance, 2) : '').'">
      <input type="hidden" name="delivery_id" id="delivery_id" value="'.$delivery_id.'">
      <input type="hidden" name="sort_by" id="sort_by" value="'.$sort_by.'">
    </form></li>'.
    ($delivery_id != '' ? '
    <li><a href="'.$_SERVER['SCRIPT_NAME'].'?per_page='.$per_page.($start_record ? '&start_record='.$start_record : '').($balance ? '&balance='.$balance : '').($sort_by ? '&sort_by='.$sort_by : '').'">Show Records for All Cycles</a>' : '').'</li>
    
  </ul>'.
  ($display_specific_cycle == true ? '
  <div id="delivery_id_nav">
    <a class="prior" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id - 1).'&per_page='.$per_page.($balance ? '&balance='.$balance : '').($sort_by ? '&sort_by='.$sort_by : '').'">&larr; PRIOR CYCLE </a>
    <a class="next" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id + 1).'&per_page='.$per_page.($balance ? '&balance='.$balance : '').($sort_by ? '&sort_by='.$sort_by : '').'"> NEXT CYCLE &rarr;</a>
  </div>' : '').
  ($delivery_id != 0 ? '<div class="notice">Only showing members with orders for Delivery ID '.$delivery_id.'</div>' : '').
  ($balance != 'all' ? '<div class="notice">Only showing members with non-zero balances and have ever ordered</div>' : '');
$content_heading = '
  <div class="heading">
    <div class="data_head member_id"><a href="'.$_SERVER['SCRIPT_NAME'].'?per_page='.$per_page.($start_record ? '&start_record='.$start_record : '').($balance ? '&balance='.$balance : '').($delivery_id ? '&delivery_id='.$delivery_id : '').'&sort_by=member_id">Member ID</a></div>
    <div class="data_head account_description"><a href="'.$_SERVER['SCRIPT_NAME'].'?per_page='.$per_page.($start_record ? '&start_record='.$start_record : '').($balance ? '&balance='.$balance : '').($delivery_id ? '&delivery_id='.$delivery_id : '').'&sort_by=preferred_name">Preferred Name</a> / <a href="'.$_SERVER['SCRIPT_NAME'].'?per_page='.$per_page.($start_record ? '&start_record='.$start_record : '').($balance ? '&balance='.$balance : '').($delivery_id ? '&delivery_id='.$delivery_id : '').'&sort_by=last_name">Last Name</a> / <a href="'.$_SERVER['SCRIPT_NAME'].'?per_page='.$per_page.($start_record ? '&start_record='.$start_record : '').($balance ? '&balance='.$balance : '').($delivery_id ? '&delivery_id='.$delivery_id : '').'&sort_by=business_name">Business Name</a></div>'.
    ($display_specific_cycle ? '
    <div class="data_head site_short"><a href="'.$_SERVER['SCRIPT_NAME'].'?per_page='.$per_page.($start_record ? '&start_record='.$start_record : '').($balance ? '&balance='.$balance : '').($delivery_id ? '&delivery_id='.$delivery_id : '').'&sort_by=site_short">Site</a></div>
    <div class="data_head due_this_cycle">This Cycle</div>' : '').'
    <div class="data_head account_balance"><a href="'.$_SERVER['SCRIPT_NAME'].'?per_page='.$per_page.($start_record ? '&start_record='.$start_record : '').($balance ? '&balance='.$balance : '').($delivery_id ? '&delivery_id='.$delivery_id : '').'&sort_by=account_balance">Total Due</a></div>'.
    ($display_specific_cycle ? '
    <div class="data_head paid_amount">Paid</div>
    <div class="data_head paid_method">Method</div>' : '').'
  </div>';
$wherestatement = '';
$query = '
  SELECT
    SQL_CALC_FOUND_ROWS
    foo.member_id AS member_id,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.business_name,
    SUM(account_balance) AS account_balance,
    (SELECT
      COUNT(bpid)
      FROM '.NEW_TABLE_BASKETS.'
      LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING (basket_id)
      WHERE '.NEW_TABLE_BASKETS.'.member_id = foo.member_id
      ) AS product_count'.
      ($display_specific_cycle != 0 ? ',
    (SELECT
      SUM(amount
        * IF(source_type="member", -1, 1)
        * IF(replaced_by IS NULL, 1, 0))
      FROM '.NEW_TABLE_LEDGER.'
      WHERE
        delivery_id = '.$delivery_id.'
        AND ((source_key = foo.member_id
            AND source_type = "member")
          OR (target_key = foo.member_id
            AND target_type = "member"))'.
        $constrain_accounting_datetime.'
      ) AS due_this_cycle,
    (SELECT
      site_short
      FROM '.NEW_TABLE_BASKETS.'
      LEFT JOIN '.NEW_TABLE_SITES.' USING(site_id)
      WHERE
        delivery_id = '.$delivery_id.'
        AND member_id = foo.member_id
      ) AS site_short' : '').'
  FROM
    (
      (SELECT
        source_key AS member_id,
        SUM(amount
          * IF(source_type="member", -1, 1)
          * IF(replaced_by IS NULL, 1, 0)
          ) AS account_balance
      FROM
        '.NEW_TABLE_LEDGER.'
      WHERE
        source_type = "member"'.
        $constrain_accounting_datetime.'
      GROUP BY source_key)
    UNION ALL
      (SELECT
        target_key AS member_id,
        SUM(amount
          * IF(target_type="member", 1, -1)
          * IF(replaced_by IS NULL, 1, 0)
          ) AS account_balance
      FROM
        '.NEW_TABLE_LEDGER.'
      WHERE
        target_type = "member"'.
        $constrain_accounting_datetime.'
      GROUP BY target_key)
    ) foo
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  WHERE'.
    $filter_delivery_id.'
  GROUP BY '.TABLE_MEMBER.'.member_id'.
  $query_having.
  $query_order_by.
  $query_limit;

// echo "<pre>$query</pre>";

$result_report = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 683903 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
// How many total rows in this query (not counting LIMIT)?
$query_found_rows = '
  SELECT
    FOUND_ROWS() AS found_rows';
$result_found_rows = @mysqli_query ($connection, $query_found_rows) or die (debug_print ("ERROR: 852302 ", array ($query_found_rows, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$array_found_rows = mysqli_fetch_array ($result_found_rows, MYSQLI_ASSOC);
$found_rows = $array_found_rows['found_rows'];
$number_of_pages = ($per_page > 0 ? ceil ($found_rows / $per_page) : 1);
$this_page = ($per_page > 0 ? ceil ($start_record + 1 / $per_page) : 0);
$page = 0;
if ($number_of_pages > 1)
  {
    $content_pager = '
      <div class="pager">
      <span class="pager_title">Page: </span>';
    while (++ $page <= $number_of_pages)
      {
        $content_pager .= '
        <a class="'.($page == 1 ? 'first' : ($page == $number_of_pages ? 'last' : '')).($page == $this_page ? ' current' : '').'" href="'.$_SERVER['SCRIPT_NAME'].'?per_page='.$per_page.'&start_record='.($page - 1) * $per_page.($balance ? '&balance='.$balance : '').($delivery_id ? '&delivery_id='.$delivery_id : '').($sort_by ? '&sort_by='.$sort_by : '').'"> '.$page.' </a>';
      }
    $content_pager .= '
      </div><br style="clear:both;">';
  }
else
  {
    $content_pager = '';
  }
$content = '';
while ( $row = mysqli_fetch_object ($result_report) )
  {
    $member_id = $row->member_id;
    $preferred_name = $row->preferred_name;
    $last_name = $row->last_name;
    $business_name = $row->business_name;
    $account_balance = $row->account_balance;
    $site_short = $row->site_short;
    $due_this_cycle = $row->due_this_cycle;
    $product_count = $row->product_count;
    $content .= '
      <div id="record_member_'.$member_id.'" class="record">
        <div class="data member_id">'.$member_id.'</div>
        <div class="data account_description"><span class="preferred_name"><a href="'.PATH.'view_account.php?account_type=member&account_key='.$member_id.'&account_name='.$preferred_name.'" target="_blank">'.$preferred_name.'</a></span>'.(strlen($business_name) > 0 ? '<span class="business_name">'.$business_name.'</span>' : '').'</div>'.
        ($display_specific_cycle ? '
        <div class="data site_short">'.$site_short.'</div>
        <div class="data due_this_cycle">'.number_format ($due_this_cycle * -1, 2).'</div>' : '').'
        <div class="data account_balance">'.number_format ($account_balance * -1, 2).'</div>'.
        ($display_specific_cycle ? '
        <div class="data paid_amount">&nbsp;</div>
        <div class="data paid_method">&nbsp;</div>' : '').'
      </div>';
  }
$page_specific_css .= '
#delivery_id_nav {
  background-color: #eef;
  height: 1.5em;
  margin: 5px auto 0;
  max-width: 40rem;
  width: 45%;
  }
#delivery_id_nav .prior {
  float: left;
  }
#delivery_id_nav .next {
  float: right;
  }
.notice {
  display:block;
  text-align:center;
  margin:2em;
  color:#800;
  }
.heading > div {
  border-bottom:3px solid #666;
  }
.heading,
.record {
  clear:both;
  display:table-row;
  }
.data_head,
.data {
  display:inline-block;
  width:10%;
  max-width:10%;
  margin:0.5em 0.5em;
  }
.business_name {
  color:#008;
  }
.business_name::before {
  content:" (";  
  }
.business_name::after {
  content:")";  
  }
.preferred_name a {
  box-shadow:none !important;
  text-decoration:none;
  }
.preferred_name a:hover {
  text-decoration:underline;
  }
.member_id {
  clear:both;
  }
.account_balance,
.due_this_cycle {
  text-align:right;
  }
.account_description {
  width:'.($display_specific_cycle ? '25' : '50').'%;
  max-width:'.($display_specific_cycle ? '25' : '50').'%;
  }
.site_short,
.account_balance,
.due_this_cycle,
.paid_amount,
.paid_method {
  width:8%;
  max-width:8%;
  }
.data.paid_amount,
.data.paid_method {
  border-bottom:1px solid black;
  }
.pager {
  clear: both;
  font-size: 120%;
  margin: 1em 0;
  }
.pager .pager_title {
  background-color: #ffc;
  border-color: #040 -moz-use-text-color #040 #040;
  border-image: none;
  border-style: solid none solid solid;
  border-width: 1px 0 1px 1px;
  display: block;
  float: left;
  font-size: 100%;
  padding: 1px 4px;
  }';

$page_title_html = '<span class="title">Member Balance Report</span>';
$page_subtitle_html = '<span class="subtitle">Balances</span>';
$page_title = 'Member Balance Report: Report';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->'.
  $content_top.
  $content_pager.
  $content_heading.
  $content.
  $content_pager.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
