<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,cashier');

$num_cycles = 20; # should be 1 higher than the actual number of cycles you want
$start = 1;
$stop = mysqli_real_escape_string ($connection, ActiveCycle::delivery_id());
if (isset($_GET['start']) && isset($_GET['stop']))
  {
    $start = $_GET['start'];
    $stop = $_GET['stop'];
    $display = 'range';
  }
else
  {
    if (isset ($_GET['num_cycles'])) $num_cycles = $_GET['num_cycles'];
    $start = $stop - $num_cycles + 1;
    $display = 'history';
  }

$query = '
  SELECT
    '.NEW_TABLE_LEDGER.'.delivery_id,
    '.NEW_TABLE_LEDGER.'.source_key,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    SUM('.NEW_TABLE_LEDGER.'.amount) AS amount
  FROM
    '.NEW_TABLE_LEDGER.'
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(pvid)
  LEFT JOIN '.TABLE_SUBCATEGORY.' USING(subcategory_id)
  LEFT JOIN '.TABLE_CATEGORY.' USING(category_id)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    '.NEW_TABLE_LEDGER.'.delivery_id <= "'.mysqli_real_escape_string($connection, $stop).'"
    AND '.NEW_TABLE_LEDGER.'.delivery_id >= "'.mysqli_real_escape_string($connection, $start).'"
    AND replaced_by IS NULL
    AND (
      text_key = "quantity cost"
      OR text_key= "weight cost"
      )
  GROUP BY
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_SUBCATEGORY.'.subcategory_name';
$main_sql = mysqli_query ($connection, $query);
$categories = array ();
$cat_total = array ();
while ($row = mysqli_fetch_array ($main_sql, MYSQLI_ASSOC))
  {
    if ($row['category_name'] && $row['subcategory_name'] && $row['delivery_date'])
      {
        if (isset($categories[$row['category_name']][$row['subcategory_name']][$row['delivery_date']]))
          $categories[$row['category_name']][$row['subcategory_name']][$row['delivery_date']] += $row['amount'];
        else
          $categories[$row['category_name']][$row['subcategory_name']][$row['delivery_date']] = $row['amount'];
      }
  }

$query = '
  SELECT
    delivery_id,
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id <= "'.mysqli_real_escape_string($connection, $stop).'"
    AND delivery_id >= "'.mysqli_real_escape_string($connection, $start).'"
  ORDER BY
    delivery_date DESC';
$dates_sql = mysqli_query ($connection, $query);

$delivery_id = array ();
$spreadsheet = "Subcategory / Date";
$date_headers = "";
while ($row = mysqli_fetch_array ($dates_sql, MYSQLI_ASSOC))
  {
    array_push($delivery_id, $row["delivery_date"]);
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
        foreach ($delivery_id as $date)
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
    foreach ($delivery_id as $date)
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
        <form id="report_range" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">
          Show <input class="text" type="text" name="number_of_cycles" value="'.$num_cycles.'"> cycles history from present
          <input class="submit_button" type="submit" value="Show History">
        </form>
          OR
        <form id="report_range" action="'.$_SERVER['SCRIPT_NAME'].'" method="get">
          Show from cycle <input class="text" type="text" name="start" value="'.$start.'"> to <input class="text" type="text" name="stop" value="'.$stop.'">
          <input class="submit_button" type="submit" value="Show Range">
        </form>
        <h2>Sales By Subcategory ('.($display == 'history' ?'last '.$num_cycles.' cycles' : 'cycles '.$start.' &ndash; '.$stop).')</h2>
        <p>Totals include all transactions associated with baskets products, i.e. damaged/written-off items as well as actual purchases.</p>
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
      position:relative;
      }
    th.date {
      transform: rotate(-90deg);
      height:150px;
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
    #report_range .text {
      width:50px;
      }
    #report_range .submit_button {
      padding:3px 20px;
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
