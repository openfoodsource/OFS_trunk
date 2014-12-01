<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,cashier,producer_admin,producer');

if (isset ($_GET['producer_id']) && is_numeric ($_GET['producer_id']))
  {
    // If not authorized then force to member's own member_id
    if (! CurrentMember::auth_type('cashier') && ! CurrentMember::auth_type('site_admin') && ! CurrentMember::auth_type('producer_admin'))
      {
        $producer_id = $_SESSION['producer_id_you'];
      }
    else
      {
        $producer_id = $_GET['producer_id'];
      }
    $query_where = '
      WHERE '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysql_real_escape_string($producer_id).'"';
    $query_business_name = '
      SELECT
        business_name
      FROM
        '.TABLE_PRODUCER.'
      WHERE
        producer_id = "'.mysql_real_escape_string($producer_id).'"';
    $result_business_name = @mysql_query($query_business_name, $connection) or die("Couldn't execute query.");
    if ( $row = mysql_fetch_array($result_business_name) )
      {
        $business_name = $row['business_name'];
      }
  }
else
  {
    $producer_id = 0;
  }

$content = '
<table width="80%">
  <tr>
    <td align="left">
      <h3>Previous and Current Invoices For '.($producer_id > 0 ? $business_name : 'Producers').'</h3>
      <ul>';
$query_delieries = '
  SELECT
    DISTINCT(delivery_id),
    delivery_date
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(basket_id)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)'.
  $query_where.'
  ORDER BY
    delivery_id DESC';
$result_deliveries = @mysql_query($query_delieries, $connection) or die("Couldn't execute query.");
while ( $row = mysql_fetch_array($result_deliveries) )
  {
    if ($producer_id > 0)
      {
        $content .= '<li><a href="show_report.php?type=producer_invoice&delivery_id='.$row['delivery_id'].'&producer_id='.$producer_id.'">'.date('F j, Y', strtotime ($row['delivery_date'])).'</a>';
      }
    else
      {
        $content .= '<li><a href="producer_list_withtotals.php?delivery_id='.$row['delivery_id'].'">'.date('F j, Y', strtotime ($row['delivery_date'])).'</a>';
      }
  }
$content .= '
      </ul>
    </td>
  </tr>
</table>';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Past Producer Invoices</span>';
$page_title = 'Delivery Cycle Functions: Past Producer Invoices';
$page_tab = 'cashier_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
