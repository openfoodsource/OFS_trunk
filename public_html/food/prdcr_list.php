<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member'); // anyone can see this list


// Items dependent upon the location of this header
$color1 = "#DDDDDD";
$color2 = "#CCCCCC";
$row_count = 0;

if ($_GET['show'] == 'all')
  {
    $show_all = true;
    $show_unlisted_query = '';
  }
else
  {
    // We ALWAYS do not show suspended producers.
    // But on this condition, also do not show "unlisted" producers.
    $show_unlisted_query = '
    AND '.TABLE_PRODUCER.'.unlisted_producer != 1
    AND IF('.NEW_TABLE_PRODUCTS.'.inventory_id > 0, FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull), 1)';
  }

$query = '
  SELECT
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_PRODUCER.'.producttypes,
    (
      SELECT COUNT(product_id)
      FROM '.NEW_TABLE_PRODUCTS.'
      WHERE '.NEW_TABLE_PRODUCTS.'.producer_id = '.TABLE_PRODUCER.'.producer_id
        AND confirmed = 1
    ) AS product_count
  FROM
    '.TABLE_PRODUCER.'
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_INVENTORY.' USING(inventory_id)
  WHERE
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"
    AND '.TABLE_PRODUCER.'.pending = 0
    AND '.TABLE_PRODUCER.'.unlisted_producer != 2'.
    $show_unlisted_query.'
  GROUP BY
    '.TABLE_PRODUCER.'.producer_id
  ORDER BY
    '.TABLE_PRODUCER.'.business_name';
$result = @mysql_query($query,$connection) or die(debug_print ("ERROR: 897650 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($result) )
  {
    $producer_id = $row['producer_id'];
    $business_name = $row['business_name'];
    $producttypes = $row['producttypes'];
    $product_count = $row['product_count'];
    if ($product_count > 0 || $show_all)
      {
        $show_name = "";
        $row_color = ($row_count % 2) ? $color1 : $color2;
        $display_top .= '
          <tr bgcolor="'.$row_color.'">
            <td width="25%"><font face="arial" size="3"><b><a href="product_list.php?type=producer_id&producer_id='.$producer_id.'">'.$business_name.'</a></b></td>
            <td width="75%">'.strip_tags ($producttypes).' ('.number_format ($product_count, 0).' '.Inflect::pluralize_if ($product_count, 'product').')</font></td>
          </tr>';
        $row_count++;
      }
  }

if ($show_all)
  {
    $content_list .= '
  <font face="arial">
    All producers listed below have been approved for selling by '.SITE_NAME.', although some
    may not currently have products for sale.  Also available is a list of only those
    <a href="'.$_SERVER['SCRIPT_NAME'].'">producers with products for sale</a>.<br><br>
    Not from this region? Don&rsquo;t despair. Many of these producers are ready and able
    to ship their products to you, including frozen meats! Please contact the producers
    directly about the shipping policies. <br><br>';
  }
else
  {
    $content_list .= '
  <font face="arial">
    Only coop producer members with products to sell this month are listed on this page.
    For a complete listing of the producer members, irrespective of the current status
    of their product offerings, click here for a <a href="'.$_SERVER['SCRIPT_NAME'].'?show=all">
    complete listing of producer members</a>.<br><br>
    Not from this region? Don&rsquo;t despair. Many of these producers are ready and able
    to ship their products to you, including frozen meats! Please contact the producers
    directly about the shipping policies. <br><br>';
  }

$content_list .= '
    <table cellpadding="2" cellspacing="2" border="0">
      <tr bgcolor="#AEDE86">
        <td><b>Producer Name</b> (Click on name)</td>
        <td><b>Types of Products Available for Sale</b></td>
      </tr>
      '.$display_top.'
    </table>';

$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '<span class="subtitle">'.($show_all ? 'Full Producer List' : 'Active Producers').'</span>';
$page_title = 'Products: '.($show_all ? 'Full Producer List' : 'Active Producers');
$page_tab = 'shopping_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
