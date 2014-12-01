<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');

// Set up default values
$slice_order_by = '1';
$this_slice = '';
$slice_subcategory_where = '1';
$slice_producer_where = '1';

// Before doing anything, do we need to update any values?
if ($_POST['action'] == 'Update Subcategory Adjust Fee')
  {
    $query = '
      UPDATE
        '.TABLE_SUBCATEGORY.'
      SET
        subcategory_fee_percent = '.mysql_real_escape_string ($_POST['subcategory_fee_percent']).'
      WHERE
        subcategory_id = "'.mysql_real_escape_string ($_POST['subcategory_id']).'"';
    $result = @mysql_query($query, $connection) or die(mysql_error());
  }
elseif ($_POST['action'] == 'Update Producer Adjust Fee')
  {
    $query = '
      UPDATE
        '.TABLE_PRODUCER.'
      SET
        producer_fee_percent = '.mysql_real_escape_string ($_POST['producer_fee_percent']).'
      WHERE
        producer_id = "'.mysql_real_escape_string ($_POST['producer_id']).'"';
    $result = @mysql_query($query, $connection) or die(mysql_error());
  }
elseif ($_POST['action'] == 'Update Product Adjust Fee')
  {
    $query = '
      UPDATE
        '.NEW_TABLE_PRODUCTS.'
      SET
        product_fee_percent = '.mysql_real_escape_string ($_POST['product_fee_percent']).'
      WHERE
        product_id = "'.mysql_real_escape_string ($_POST['product_id']).'"';
    $result = @mysql_query($query, $connection) or die(mysql_error());
  }

// Set up the proper query, based upon what request we have received
if ($_GET['slice_subcategory'])
  {
    $slice_subcategory_where = NEW_TABLE_PRODUCTS.'.subcategory_id = '.$_GET['slice_subcategory'];
    $slice_order_by = '
    '.NEW_TABLE_PRODUCTS.'.subcategory_id,
    '.NEW_TABLE_PRODUCTS.'.producer_id,
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type';
    $this_slice = 'slice_subcategory='.$_GET['slice_subcategory'];
  }

if ($_GET['slice_producer'])
  {
    $slice_producer_where = NEW_TABLE_PRODUCTS.'.producer_id = "'.$_GET['slice_producer'].'"';
    $slice_order_by = '
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.producer_id,
    '.NEW_TABLE_PRODUCTS.'.subcategory_id,
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type';
    if ($this_slice) $this_slice .= '&';
    $this_slice .= 'slice_producer='.$_GET['slice_producer'];
  }

// Get the retail and wholesale markup amounts
$query = '
  SELECT
    delivery_date,
    producer_markdown,
    wholesale_markup,
    retail_markup
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id_next()).'"';
$result = @mysql_query($query, $connection) or die(mysql_error());
if ( $row = mysql_fetch_array($result) )
  {
    $delivery_date = date ("F j, Y", strtotime ($row['delivery_date']));
    $producer_markdown = $row['producer_markdown'] / 100;
    $retail_markup = $row['retail_markup'] / 100;
    $wholesale_markup = $row['wholesale_markup'] / 100;
  }

// Get the products meeting the requested criteria
$query = '
  SELECT
    '.NEW_TABLE_PRODUCTS.'.*,
    '.NEW_TABLE_PRODUCTS.'.product_fee_percent / 100 AS product_fee_percent,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_PRODUCER.'.producer_fee_percent / 100 AS producer_fee_percent,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_SUBCATEGORY.'.subcategory_fee_percent / 100 AS subcategory_fee_percent
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  WHERE
    '.$slice_subcategory_where.'
    AND '.$slice_producer_where.'
    AND confirmed = 1
  ORDER BY
    '.$slice_order_by;

$producer_id_prior = '';
$subcategory_id_prior = '';

// Cycle through the products/producers we need to display.
$sql = @mysql_query($query, $connection) or die(debug_print ("ERROR: 572932 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_object($sql) )
  {

// If needed, show a new subcategory subheader
    if ($row->subcategory_id != $subcategory_id_prior)
      {
        $html_output .= '
      <tr class="subcat_head">
        <td colspan="4">
          '.$row->subcategory_name.'<br>
          <span class="next_link">[ <a href="'.$_SERVER['SCRIPT_NAME'].'?slice_subcategory='.$row->subcategory_id.'">View all '.$row->subcategory_name.' products</a> ]
        </td>
        <td colspan="6">
          <form action="'.$_SERVER['SCRIPT_NAME'].'?'.$this_slice.'" method="post">
            <input type="hidden" name="subcategory_id" value="'.$row->subcategory_id.'">
            <input type="submit" name="action" value="Update Subcategory Adjust Fee" class="subcat_form">
            <input id="subcat_af_'.$row->subcategory_id.'" type="text" size="5" maxlength="10" name="subcategory_fee_percent" value="'.($row->subcategory_fee_percent * 100).'" class="input subcat_form" onKeyUp="updatePrices(this)">&nbsp;%&nbsp;
            </form>
        </td>';
      }

// If needed, show a new producer subheader
    if ($row->producer_id != $producer_id_prior)
      {
        $html_output .= '
      <tr class="producer_head">
        <td colspan="4">
          '.$row->business_name.'<br>
          <span class="next_link">[ <a href="'.$_SERVER['SCRIPT_NAME'].'?slice_producer='.$row->producer_id.'">View all '.$row->business_name.' products</a> ]
        </td>
        <td colspan="6">
          <form action="'.$_SERVER['SCRIPT_NAME'].'?'.$this_slice.'" method="post">
            <input type="hidden" name="producer_id" value="'.$row->producer_id.'">
            <input type="submit" name="action" value="Update Producer Adjust Fee" class="producer_form">
            <input id="producer_af_'.$row->producer_id.'" type="text" size="5" maxlength="10" name="producer_fee_percent" value="'.($row->producer_fee_percent * 100).'" class="input producer_form" onKeyUp="updatePrices(this)">&nbsp;%&nbsp;
            </form>
        </td>';
      }

// Translate the "listed" values from their numeric codes
//     if ($row->listing_auth_type == 0) $listed = 'Retail';
//     if ($row->listing_auth_type == 1) $listed = 'Unlisted';
//     if ($row->listing_auth_type == 2) $listed = 'Archived';
//     if ($row->listing_auth_type == 3) $listed = 'Wholesale';
    $listed = $row->listing_auth_type;

// Get the html for this row
    $html_output .= '
      <tr '.($listed != $listed_prior ? ' class="list_section"' : '').'>
        <td class="border_left border_right">#'.$row->product_id.' '.$row->product_name.'</td>
        <td>'.$listed.'</td>
        <td class="border_left center">
          <form action="'.$_SERVER['SCRIPT_NAME'].'?'.$this_slice.'" method="post">
          <input type="hidden" name="product_id" value="'.$row->product_id.'">
          <input type="hidden" name="action" value="Update Product Adjust Fee">
          <input id="product_af_'.$row->product_id.'" type="text" size="5" maxlength="10" name="product_fee_percent" value="'.($row->product_fee_percent * 100).'" class="input product_form" onKeyUp="updatePrices(this)">%</td>
          </form>
        <td class="center">'.($row->subcategory_fee_percent * 100).'%</td>
        <td class="border_right center">'.($row->producer_fee_percent * 100).'%</td>
        <td>$&nbsp;<span id="pid_'.$row->product_id.'">'.number_format ($row->unit_price, 2).'</span> / '.$row->pricing_unit.'</td>

        <td class="border_left center'.($listed == 'Unlisted' || $listed == 'Archived' ? ' strike' : '').'">$&nbsp;'.number_format ($row->unit_price * (1 - $producer_markdown), 2).'</td>
        <td class="center producer_af_'.$row->producer_id.' product_af_'.$row->product_id.' subcat_af_'.$row->subcategory_id.($listed != 'Retail' ? ' strike' : '').'" id="retail-'.$row->producer_id.'-'.$row->subcategory_id.'-'.$row->product_id.'">$&nbsp;'.number_format ($row->unit_price * (1 + $retail_markup) * (1 + $row->product_fee_percent + $row->producer_fee_percent + $row->subcategory_fee_percent), 2).'</td>
        <td class="border_right center producer_af_'.$row->producer_id.' product_af_'.$row->product_id.' subcat_af_'.$row->subcategory_id.($listed == 'Unlisted' || $listed == 'Archived' ? ' strike' : '').'" id="wholesale-'.$row->producer_id.'-'.$row->subcategory_id.'-'.$row->product_id.'">$&nbsp;'.number_format ($row->unit_price * (1 + $wholesale_markup) * (1 + $row->product_fee_percent + $row->producer_fee_percent + $row->subcategory_fee_percent), 2).'</td>
      </tr>';

    // Keep track of prior values so we know when to put up a new subheader
    $producer_id_prior = $row->producer_id;
    $subcategory_id_prior = $row->subcategory_id;
    $listed_prior = $listed;
  }

$page_specific_javascript = '
<script type="text/javascript">
<!--

// Script will auto-fill affected prices -- it does not auto-update the percentages in the "Adjust fees for..." columns.
var c_arrElements;
var p_arrElements;
var i;

function getElementsByClass (needle) {
  var my_array = document.getElementsByTagName("td");
  var retvalue = new Array();
  var i;
  var j;

  for (i = 0, j = 0; i < my_array.length; i++) {
    var c = " " + my_array[i].className + " ";
    if (c.indexOf(" " + needle + " ") != -1)
      retvalue[j++] = my_array[i];
    }
  return retvalue;
  }
function updatePrices (textfield) {
  var textfield_id = textfield.id;
  c_arrElements = getElementsByClass (textfield_id);
  for (i = 0; i < c_arrElements.length; i++) {
    // ID is returned as [retail|wholesale]-producer_id-subcategory_id-product_id
    var id_parts = c_arrElements[i].id.split(\'-\');
    if (id_parts[0] == \'retail\') {
      document.getElementById(c_arrElements[i].id).innerHTML=\'$ \'+ ( (1+Number('.$retail_markup.')) * (1 + Number(document.getElementById(\'producer_af_\'+id_parts[1]).value)/100 + Number(document.getElementById(\'subcat_af_\'+id_parts[2]).value)/100 + Number(document.getElementById(\'product_af_\'+id_parts[3]).value)/100) * Number(document.getElementById(\'pid_\'+id_parts[3]).innerHTML) ).toFixed(2);
      }
    if (id_parts[0] == \'wholesale\') {
      document.getElementById(c_arrElements[i].id).innerHTML=\'$ \'+ ( (1+Number('.$wholesale_markup.')) * (1 + Number(document.getElementById(\'producer_af_\'+id_parts[1]).value)/100 + Number(document.getElementById(\'subcat_af_\'+id_parts[2]).value)/100 + Number(document.getElementById(\'product_af_\'+id_parts[3]).value)/100) * Number(document.getElementById(\'pid_\'+id_parts[3]).innerHTML) ).toFixed(2);
      }
    }
  }

-->
</script>';

$page_specific_css = '
  <style type="text/css">
    table { border-collapse:collapse; border: 1px solid #000; }
    tr { font-size:0.8em; }
    td { padding-left:4px; padding-right:4px; }
    .producer_head { color:#fff; background-color:#876; }
    .producer_form { color:#4c3d2f; background-color:#c3b7ab; text-align:right; }
    .subcat_head { color:#fff; background-color:#687; }
    .subcat_form { color:#2f4c3d; background-color:#abc3b7; text-align:right; }
    .product_form { background-color:#eee; text-align:right; border-bottom:1px solid #aaa; border-right: 1px solid #aaa }
    .input { font-weight:bold; font-family:courier; font-size: 1em;}
    tr.producer_head, tr.subcat_head { height:2em; font-weight:bold; font-size: 1em; border-top: 1px solid #000; border-bottom: 1px solid #bbb; }
    tr.head { font-weight:bold; }
    form { float:right; }
    .border_left { border-left: 1px solid black; }
    .border_right { border-right: 1px solid black; }
    .center { text-align:center; }
    .strike { color:#888; }
    .list_section {border-top: 1px solid #bbb; }
    .next_link { font-size: 0.8em; }
    .next_link a { color:#fed; text-decoration:none; font-weight:normal; }
    .next_link a:hover { text-decoration:underline; }
    table, p { width:95%; margin:auto; padding-bottom:1em; };
  </style>';


$content_edit = '
    <p><strong>Instructions:</strong> In this worksheet, any number of values can be entered to observe how
      the product prices will be affected, however <em>only one</em> value may be updated at any given time;
      all other changes will be reset to their saved values.  In order to update product fee values, press [ENTER]
      while on that input box.</p>
    <table border="0">
      <tr class="head">
        <td class="border_left border_right"></td>
        <td></td>
        <td colspan="3" class="border_left border_right center">Adjust Fees for</td>
        <td></td>

        <td colspan="3" class="border_left border_right center">Pricing Detail</td>
      </tr>
      <tr class="head">
        <td class="border_left border_right">Product Name</td>
        <td>Listed</td>
        <td class="border_left">Prod.</td>
        <td>Subcat.</td>
        <td class="border_right">Prdcr.</td>
        <td>Base Price</td>

        <td class="border_left center">Producer</td>
        <td class="center">Retail</td>
        <td class="border_right center">Wholesale</td>
      </tr>
      '.$html_output.'
    </table>';

$page_title_html = '<span class="title">Admin Maintenance</span>';
$page_subtitle_html = '<span class="subtitle">Edit Product Rates</span>';
$page_title = 'Admin Maintenance: Edit Product Rates';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_edit.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

