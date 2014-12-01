<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,cashier');


$num_cycles = 20; # should be 1 higher than the actual number of cycles you want

$query = '
  SELECT
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_CATEGORY.'.category_name,
    /* (!out_of_stock * if('.NEW_TABLE_PRODUCTS.'.random_weight = 1, '.NEW_TABLE_BASKET_ITEMS.'.item_price * total_weight, '.NEW_TABLE_BASKET_ITEMS.'.item_price * quantity)) AS real_price */
    ((1 - out_of_stock) * (('.NEW_TABLE_PRODUCTS.'.random_weight * '.NEW_TABLE_PRODUCTS.'.unit_price * total_weight) + ((1 - '.NEW_TABLE_PRODUCTS.'.random_weight) * '.NEW_TABLE_PRODUCTS.'.unit_price * quantity))) AS real_price
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
  LEFT JOIN '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.NEW_TABLE_BASKETS.'.delivery_id
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' ON ('.NEW_TABLE_PRODUCTS.'.product_id = '.NEW_TABLE_BASKET_ITEMS.'.product_id AND '.NEW_TABLE_PRODUCTS.'.product_version = '.NEW_TABLE_BASKET_ITEMS.'.product_version)
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id <= "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"
    AND '.NEW_TABLE_BASKETS.'.delivery_id > "'.mysql_real_escape_string (ActiveCycle::delivery_id() - $num_cycles).'"
  GROUP BY
    '.NEW_TABLE_BASKET_ITEMS.'.bpid';
$main_sql = mysql_query($query);

$categories = array ();
$cat_total = array ();
while ($row = mysql_fetch_array($main_sql))
  {
    if ($row["category_name"] && $row["subcategory_name"] && $row["delivery_date"])
      {
        if (isset($categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]]))
          $categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]] += $row["real_price"];
        else
          $categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]] = $row["real_price"];
      }
  }

$query = '
  SELECT
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id <= "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"
    AND delivery_id > "'.mysql_real_escape_string (ActiveCycle::delivery_id() - $num_cycles).'"
  ORDER BY
    delivery_date DESC';
$dates_sql = mysql_query($query);

$delivery_dates = array ();
$spreadsheet = "Subcategory / Date";
$date_headers = "";
while ($row = mysql_fetch_array($dates_sql))
  {
    array_push($delivery_dates, $row["delivery_date"]);
    $date_headers .= '
      <th class="date">'.$row["delivery_date"].'</th>';
    $spreadsheet .= "\t".$row["delivery_date"];
  }

$table = "";
$spreadsheet .= "\n";
ksort($categories);
foreach ($categories as $cat_name => $cat)
  {
    // First iterate through all the subcategories for this category (to get totals)
    ksort($cat);
    $subcat_table_rows = '';
    $subcat_spreadsheet_rows = '';
    unset ($cat_total);
    foreach ($cat as $subcat_name => $subcat)
      {
        $subcat_table_rows .= '
          <tr>
            <th class="subcat col1">'.$subcat_name.'</th>';
        $subcat_spreadsheet_rows .= $subcat_name;
        foreach ($delivery_dates as $date)
          {
//            $value = (isset($subcat[$date]) && $subcat[$date] != 0) ? number_format($subcat[$date], 2) : "-";
            $value = (isset ($subcat[$date]) && $subcat[$date] != 0) ? round ($subcat[$date], 2) : 0.00;
            $subcat_table_rows .= '
              <td class="subcat currency">'.number_format ($value, 2).'</td>';
            $subcat_spreadsheet_rows .= "\t".number_format ($value, 2);
            $cat_total[$date] = (isset ($cat_total[$date]) ? $cat_total[$date] + $value : $value);
          }
        $subcat_table_rows .= '
          </tr>';
        $subcat_spreadsheet_rows .= "\n";
      }
    // Put together the category totals
    $cat_totals_table_row = '';
    $cat_totals_spreadsheet_row = '';
    foreach ($delivery_dates as $date)
      {
        $value = (isset ($cat_total[$date]) && $cat_total[$date] != 0) ? round ($cat_total[$date], 2) : 0.00;
        $cat_totals_table_row .= '
          <td class="total category currency">'.number_format ($value, 2).'</td>';
        $cat_totals_spreadsheet_row .= "\t".number_format ($value, 2);
      }
    $table .= '
      <tr>
        <th class="category col1">'.$cat_name.' (TOTAL)</th>'.$cat_totals_table_row.'
      </tr>'.$subcat_table_rows;
    $spreadsheet .= "\n*** $cat_name ***".$cat_totals_spreadsheet_row."\n$subcat_spreadsheet_rows";
  }

$content = '
  <small>NOTE: This page might scroll horizontally.  Look for the horizontal scroll-bar at the bottom of the page.</small>
  <table id="report_container">
    <tr>
      <td>
        <h2>Sales By Subcategory (last '.$num_cycles.' cycles)</h2>
        <form>
          <label for="spreadsheet">Spreadsheet copyable data (click to select all, then copy):</label><br>
          <textarea style="margin-bottom: 1em;" id="spreadsheet" onclick="this.select();">'.$spreadsheet.'</textarea>
        </form>
        <table id="subcat_sales">
          <tr>
            <th class="date">Delivery Date</th>'.$date_headers.'
          '.$table.'
        </table>
      </td>
    </tr>
  </table>';

$page_specific_css = '
  <style type="text/css">
    th {
      font-weight:bold;
      font-size:70%;
      }
    #subcat_sales {
      table-layout:fixed;
      border-collapse:collapse;
      border:1px solid black;
      border-spacing:0px;
      }
    #subcat_sales tr td,
    #subcat_sales tr th {
      border:1px solid black;
      }
    .total {
      text-align: right;
      font-weight:bold;
      padding-top:10px;
      }
    .category {
      font-size:80%;
      color:#000;
      margin-top:20px;
      }
    .subcat {
      padding-left:15px;
      color:#777;
      }
    .date {
      color:#444;
      text-align:left;
      font-size:90%;
      }
    .col1 {
      text-align:left;
      }
    .currency {
      text-align:right;
      }
    #report_container {
      table-layout:fixed;
      width:100%;
      }
    #report_container tr td {
      }
    #content {
      overflow-x:scroll;
      }
  </style>';
$page_specific_javascript = '';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Subcategory Report</span>';
$page_title = 'Reports: Subcategory Report';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

