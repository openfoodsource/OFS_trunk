<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,cashier');


$display_cashier = '
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">
        <img src="'.DIR_GRAPHICS.'report.png" width="32" height="32" align="left" hspace="2" alt="Manage products"><br>
        <b>Reports</b>
        <ul class="fancyList1">
          <li class="last_of_group"><a href="view_balances3.php">View Ledger</a></li>
          <li class="last_of_group"><a href="report_per_subcat.php">Sales Per Subcategory</a></li>
        </ul>
<!--    <img src="'.DIR_GRAPHICS.'ksirc.png" width="32" height="32" align="left" hspace="2" alt="Helpful PDF Forms for Download"><br>
        <b>Helpful PDF Forms for Download</b>
        <ul class="fancyList1">
          <li><a href="pdf/payments_received.pdf" target="_blank">Payments Received Form</a></li>
          <li class="last_of_group"><a href="pdf/invoice_adjustments.pdf" target="_blank">Invoice Adjustments Chart</a></li>
        </ul>
-->   </td>
      <td align="left" width="50%">
        <img src="'.DIR_GRAPHICS.'kspread.png" width="32" height="32" align="left" hspace="2" alt="Treasurer Functions"><br>
        <b>Treasurer Functions</b>
        <ul class="fancyList1">
          <li><a href="chart_of_accounts.php">Chart of Accounts</a>
          <li class="last_of_group"><a href="view_account.php">Inspect Individual Accounts</a>
          <li><a href="receive_payments.php">Receive Payments (for orders)</a>
          <li class="last_of_group"><a href="receive_payments_bymember.php">Receive Payments (by member)</a>
          <li class="last_of_group"><a href="make_payments.php">Make Payments (to producers)</a>
        </ul>
        <img src="'.DIR_GRAPHICS.'kcron.png" width="32" height="32" align="left" hspace="2" alt="Delivery Cycle Functions"><br>
        <b>Delivery Cycle Functions</b>
        <ul class="fancyList1">
          <li class="last_of_group"><a href="past_customer_invoices.php">Past Customer Invoices</a></li>
          <li class="last_of_group"><a href="past_producer_invoices.php">Past Producer Invoices</a></li>
          <li class="last_of_group"><a href="generate_invoices.php">Generate Invoices</a></li>
        </ul>
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Cashier Panel</span>';
$page_title = 'Cashier Panel';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_cashier.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
