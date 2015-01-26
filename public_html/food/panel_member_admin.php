<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin');


$display_admin .= '
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">
        <img src="'.DIR_GRAPHICS.'report.png" width="32" height="32" align="left" hspace="2" alt="Manage products"><br>
        <b>Reports</b>
        <ul class="fancyList1">
          <li class="last_of_group"><a href="view_balances3.php">View Ledger</a></li>
          <li> <a href="report_members.php?p=0">Download a Spreadsheet of All Members</a></li>
          <li><a href="members_list.php">Membership List (Full Info)</a></li>
          <li><a href="members_list_institutions.php">Producer, Business & Other Orgs. Membership List (Full Info)</a></li>
          <li><a href="members_list_email.php">Member Email Addresses</a></li>
          <li> <a href="members_list_withemail.php">Members who have email</a></li>
          <li class="last_of_group"> <a href="members_list_noemail.php">Members without email</a></li>
        </ul>
      </td>
      <td align="left" width="50%">
        <img src="'.DIR_GRAPHICS.'bottom.png" width="32" height="32" align="left" hspace="2" alt="Membership Information"><br>
        <b>Membership Information</b>
        <ul class="fancyList1">
          <li><a href="member_lookup.php?action=find">Find/Edit Members</a></li>
          <li><a href="edit_member_types.php">Mass Edit Membership Types</a></li>
          <li><a href="edit_auth_types.php">Mass Edit Auth Types</a></li>
        </ul>
        <img src="'.DIR_GRAPHICS.'kcron.png" width="32" height="32" align="left" hspace="2" alt="Delivery Cycle Functions"><br>
        <b>Delivery Cycle Functions</b>
        <ul class="fancyList1">
          <li class="last_of_group"><a href="orders_list_withtotals.php?delivery_id='.ActiveCycle::delivery_id().'">Members with orders this cycle (with totals)</a></li>
          <li><a href="members_list_emailorders.php?delivery_id='.ActiveCycle::delivery_id().'">Customer Email Addresses this cycle</a></li>
        </ul>
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Member Admin Panel</span>';
$page_title = 'Member Admin Panel';
$page_tab = 'member_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_admin.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
