<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,cashier,member');

if (isset ($_GET['member_id']) && is_numeric ($_GET['member_id']))
  {
    // If not authorized then force to member's own member_id
    if (! CurrentMember::auth_type('cashier') && ! CurrentMember::auth_type('site_admin'))
      {
        $member_id = $_SESSION['member_id'];
      }
    else
      {
        $member_id = $_GET['member_id'];
      }
    $query_where = '
      WHERE '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';
    $query_member_name = '
      SELECT
        preferred_name
      FROM
        '.TABLE_MEMBER.'
      WHERE
        member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';
    $result_member_name = @mysqli_query ($connection, $query_member_name) or die (debug_print ("ERROR: 782130 ", array ($query_member_name, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ( $row = mysqli_fetch_array ($result_member_name, MYSQLI_ASSOC) )
      {
        $preferred_name = $row['preferred_name'];
      }
  }
else
  {
    $member_id = 0;
  }

$content = '
<table width="80%">
  <tr>
    <td align="left">
      <h3>Previous and Current Invoices For '.($member_id > 0 ? $preferred_name : 'Members').'</h3>
      <ul>';
$query_delieries = '
  SELECT
    DISTINCT(delivery_id),
    delivery_date
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)'.
  $query_where.'
  ORDER BY
    delivery_id DESC';
$result_deliveries = @mysqli_query ($connection, $query_delieries) or die (debug_print ("ERROR: 432743 ", array ($query_delieries, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysqli_fetch_array ($result_deliveries, MYSQLI_ASSOC) )
  {
    if ($member_id > 0)
      {
        $content .= '<li><a href="show_report.php?type=customer_invoice&delivery_id='.$row['delivery_id'].'&member_id='.$member_id.'">'.date('F j, Y', strtotime ($row['delivery_date'])).'</a>';
      }
    else
      {
        $content .= '<li><a href="orders_list_withtotals.php?delivery_id='.$row['delivery_id'].'">'.date('F j, Y', strtotime ($row['delivery_date'])).'</a>';
      }
  }
$content .= '
      </ul>
    </td>
  </tr>
</table>';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Past Customer Invoices</span>';
$page_title = 'Delivery Cycle Functions: Past Customer Invoices';
$page_tab = 'cashier_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
