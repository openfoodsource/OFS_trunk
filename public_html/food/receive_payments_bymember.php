<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

// Set the defaults
$page = 1;
$member_count = 0;

// ... but if a targeted delivery is requested then use that.
if (isset ($_GET['page']))
  $page = $_GET['page'];

$query = '
  SELECT
    member_id,
    preferred_name,
    membership_discontinued
  FROM
    '.TABLE_MEMBER.'
  ORDER BY
    member_id';
$result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 673032 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$this_page = 1;
$pager_display .= '
  <div class="pager">
    <span class="pager_title">Member: </span>';
while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
  {
    // If a new page, then get the starting member_id for this page
    if ($member_count == 0)
      {
        $page_starting_member = $row['member_id'];
      }
    // If this member is on the current page then display the member info
    if ($this_page == $page)
      {
        $page_data .= '
          <div id="member_id'.$row['member_id'].'" class="basket_section">
            <span class="member_id">'.$row['member_id'].'</span>
            <span class="site_id">[N/A]</span>
            <span class="member_name">'.$row['preferred_name'].'</span>
            <span class="controls"><input type="button" value="Receive Payment" onclick="show_receive_payment_form('.$row['member_id'].',0)"></span>
            <div id="basket_id'.$basket_id.'" class="ledger_info">'.
              $receive_payments_detail_line.'
            </div>
          </div>';
      }
    $member_count ++;
    if ($member_count == PER_PAGE)
      {
        // Get the ending member_id for this page
        $page_ending_member = $row['member_id'];
        $pager_display .= '
          <a href="'.$_SERVER['SCRIPT_NAME'].'?page='.$this_page.'" class="'.($this_page == $page ? 'current' : '').($this_page == 1 ? ' first' : '').'">&nbsp;'.$page_starting_member.'-'.$page_ending_member.'&nbsp;</a>';
        $this_page ++;
        $member_count = 0;
      }
    // Keep this for closing the pager *after* this loop
    $page_ending_member = $row['member_id'];
  }
// Check for any unclosed pages in the pager
if ($member_count != PER_PAGE)
  {
    // Get the ending member_id for this page
    $pager_display .= '
      <a href="'.$_SERVER['SCRIPT_NAME'].'?page='.$this_page.'" class="'.($this_page == $page ? 'current' : '').' last">&nbsp;'.$page_starting_member.'-'.$page_ending_member.'&nbsp;</a>';
  }
$pager_display .= '
  </div>';


$page_specific_javascript = '
  <script src="'.PATH.'receive_payments.js" type="text/javascript"></script>';

$page_specific_css = '
  <link href="'.PATH.'receive_payments.css" rel="stylesheet" type="text/css">
  <style type="text/css">
    .pager a {
      width:'.number_format(45/$this_page,2).'%;
      }
    .pager a.first,
    .pager a.last,
    .pager a.current,
    .pager a:hover {
      min-width:10%;
      }
    .pager {
      display:inline-block;
      margin-bottom:15px;
      }
  </style>';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Receive Payments</span>';
$page_title = 'Delivery Cycle Functions: Receive Payments';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->'.
  $pager_display.
  $page_data.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
