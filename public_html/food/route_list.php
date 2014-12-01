<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin,producer,producer_admin');

$type = $_GET['type'];
$delivery_id = $_GET['delivery_id'];
$checkbox = '<img src="'.DIR_GRAPHICS.'checkbox.gif" style="height:1em;vertical-align:text-top;">';

// Check how to restrict the results...
if (isset ($_GET['producer_id']))
  {
    // Producers and Route Admins get the specified list, if requested.
    $and_producer_id = '
      AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysql_real_escape_string ($_GET['producer_id']).'"';
    // Use only ONE checkbox on these listings
    $checkbox = ' <img src="'.DIR_GRAPHICS.'checkbox.gif" style="height:1em;vertical-align:text-top;">  ';
  }
// Other wise, if a Route Admin, then give the full list.
elseif (CurrentMember::auth_type('route_admin'))
  {
    $and_producer_id = '';
  }
// Otherwise, give no list at all.
else
  {
    $and_producer_id = '
      AND '.NEW_TABLE_PRODUCTS.'.producer_id = ""';
  }

if ($type == 'pickup')
  {
    $output .= '
              <h1>Producer Pick-up List</h1>
              <pre>';
    $query = '
      SELECT
        '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
        '.NEW_TABLE_BASKET_ITEMS.'.product_id,
        '.NEW_TABLE_PRODUCTS.'.product_name,
        '.NEW_TABLE_SITES.'.*,
        '.TABLE_HUBS.'.*,
        '.TABLE_MEMBER.'.*,
        '.NEW_TABLE_BASKETS.'.member_id,
        '.TABLE_PRODUCER.'.business_name,
        '.TABLE_PRODUCER.'.producer_id,
        '.NEW_TABLE_BASKET_ITEMS.'.quantity,
        '.NEW_TABLE_PRODUCTS.'.ordering_unit
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN
        '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
      LEFT JOIN
        '.NEW_TABLE_BASKETS.' USING(basket_id)
      LEFT JOIN
        '.NEW_TABLE_SITES.' USING(site_id)
      LEFT JOIN
        '.TABLE_HUBS.' USING(hub_id)
      LEFT JOIN
        '.TABLE_PRODUCER.' USING(producer_id)
      LEFT JOIN
        '.TABLE_PRODUCT_STORAGE_TYPES.' USING(storage_id)
      LEFT JOIN
        '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.NEW_TABLE_BASKETS.'.member_id
      WHERE
        '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string ($delivery_id).'
        AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != '.NEW_TABLE_BASKET_ITEMS.'.quantity
        AND '.NEW_TABLE_PRODUCTS.'.tangible = 1'.
        $and_producer_id.'
      ORDER BY
        '.TABLE_PRODUCER.'.business_name,
        '.NEW_TABLE_SITES.'.site_short,
        '.NEW_TABLE_BASKETS.'.member_id,
        '.NEW_TABLE_BASKET_ITEMS.'.product_id,
        '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 783022 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysql_fetch_object($result) )
      {
        if ($row->producer_id != $producer_id_prior)
          {
            $output .= '
              </pre>'.($_GET['paginate'] == 'true' ? '<!-- NEW SHEET -->' : '').'
<h3>'.$row->business_name.'</h3>
              <pre>
(MEMBR)  ROUTE CODE                - NAME
  PROD_ID CHK  STOR  DESCRIPTION
___________________________________________________________________________________________________';
          }
        $route_code = convert_route_code((array) $row);
        if ($row->member_id != $member_id_prior || $row->producer_id != $producer_id_prior) $output .=
          "\n\n ".str_pad('('.$row->member_id.')', 6, ' ', STR_PAD_LEFT).'  '.$route_code.' - '.$row->first_name.' '.$row->last_name.
          "\n  ".str_pad($row->product_id, 7, ' ', STR_PAD_LEFT).'  '.$checkbox.'  '.str_pad($row->storage_code, 6).str_pad(substr($row->product_name, 0, 40), 40).'     '.'('.$row->quantity.') - '.Inflect::pluralize_if ($row->quantity, $row->ordering_unit);
//         elseif ($row->storage_code != $storage_code_prior) $output .= 
//           "\n".str_pad('('.$row->member_id.')', 6, ' ', STR_PAD_LEFT).str_pad('['.$row->product_id.']', 10, ' ', STR_PAD_LEFT).'  '.$checkbox.'  '.str_pad(substr($row->product_name, 0, 40), 40).'     '.'('.$row->quantity.') - '.Inflect::pluralize_if ($row->quantity, $row->ordering_unit);
        else $output .= 
          "\n  ".str_pad($row->product_id, 7, ' ', STR_PAD_LEFT).'  '.$checkbox.'  '.str_pad($row->storage_code, 6).str_pad(substr($row->product_name, 0, 40), 40).'     '.'('.$row->quantity.') - '.Inflect::pluralize_if ($row->quantity, $row->ordering_unit);
//         $output .= '
//                 <tr>
//                   <td width="7%">'.$row->storage_code.'</td>
//                   <td width="75%">['.$row->product_id.'] '.$row->product_name.'</td>
//                   <td width="18%">('.$row->product_quantity.') - '.Inflect::pluralize_if ($row->product_quantity, $row->ordering_unit).'</td>
//                 </tr>';
        $storage_code_prior = $row->storage_code;
        $producer_id_prior = $row->producer_id;
        $member_id_prior = $row->member_id;
      }
    $output .= '
              </pre>';
  }


elseif ($type == 'dropoff')
  {
    $output .= '
              <h1>Site Drop-off List</h1>
              <pre>';
    $query = '
      SELECT
        '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
        '.NEW_TABLE_BASKETS.'.member_id,
        '.NEW_TABLE_BASKET_ITEMS.'.product_id,
        '.NEW_TABLE_PRODUCTS.'.product_name,
        '.NEW_TABLE_SITES.'.*,
        '.TABLE_HUBS.'.*,
        '.TABLE_MEMBER.'.*,
        '.NEW_TABLE_BASKET_ITEMS.'.quantity,
        '.NEW_TABLE_PRODUCTS.'.ordering_unit
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN
        '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
      LEFT JOIN
        '.NEW_TABLE_BASKETS.' USING(basket_id)
      LEFT JOIN
        '.NEW_TABLE_SITES.' USING(site_id)
      LEFT JOIN
        '.TABLE_HUBS.' USING(hub_id)
      LEFT JOIN
        '.TABLE_PRODUCT_STORAGE_TYPES.' USING(storage_id)
      LEFT JOIN
        '.TABLE_MEMBER.' USING(member_id)
      WHERE
        '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string ($delivery_id).'
        AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != '.NEW_TABLE_BASKET_ITEMS.'.quantity
        AND '.NEW_TABLE_PRODUCTS.'.tangible = 1'.
        $and_producer_id.'
      ORDER BY
        '.NEW_TABLE_SITES.'.site_long,
        '.NEW_TABLE_BASKETS.'.member_id,
        '.NEW_TABLE_BASKET_ITEMS.'.product_id,
        '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 730302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysql_fetch_object($result) )
      {
        if ($row->site_id != $site_id_prior)
          {
            $output .= '
              </pre>'.($_GET['paginate'] == 'true' ? '<!-- NEW SHEET -->' : '').'
<h3>'.$row->site_long.' ['.$row->site_short.']</h3>
              <pre>
(MEMBR)  ROUTE CODE                - NAME
  PROD_ID   CHK    STOR  DESCRIPTION                                  QUANTITY
___________________________________________________________________________________________________';
          }
        $route_code = convert_route_code((array) $row);
        if ($row->member_id != $member_id_prior || $row->site_id != $site_id_prior) $output .=
          "\n\n ".str_pad('('.$row->member_id.')', 6, ' ', STR_PAD_LEFT).'  '.$route_code.' - '.$row->first_name.' '.$row->last_name.
          "\n  ".str_pad($row->product_id, 7, ' ', STR_PAD_LEFT).'   '.$checkbox.'  '.str_pad($row->storage_code, 6).str_pad(substr($row->product_name, 0, 40), 40).'     '.'('.$row->quantity.') - '.Inflect::pluralize_if ($row->quantity, $row->ordering_unit);
//         elseif ($row->storage_code != $storage_code_prior) $output .=
//           "\n      ".str_pad('('.$row->member_id.')', 6, ' ', STR_PAD_LEFT).str_pad('['.$row->product_id.']', 10, ' ', STR_PAD_LEFT).'  $checkbox  '.str_pad(substr($row->product_name, 0, 40), 40).'     '.'('.$row->quantity.') - '.Inflect::pluralize_if ($row->quantity, $row->ordering_unit);
        else $output .=
          "\n  ".str_pad($row->product_id, 7, ' ', STR_PAD_LEFT).'   '.$checkbox.'  '.str_pad($row->storage_code, 6).str_pad(substr($row->product_name, 0, 40), 40).'     '.'('.$row->quantity.') - '.Inflect::pluralize_if ($row->quantity, $row->ordering_unit);
//         $output .= '
//                 
//                 <tr>
//                   <td width="7%">'.$row->storage_code.'</td>
//                   <td width="75%">('.$row->member_id.') ['.$row->product_id.'] '.$row->product_name.'</td>
//                   <td width="18%">('.$row->quantity.') - '.Inflect::pluralize_if ($row->product_quantity, $row->ordering_unit).'</td>
//                 </tr>';
        $site_id_prior = $row->site_id;
        $storage_code_prior = $row->storage_code;
        $member_id_prior = $row->member_id;
      }
    $output .= '
              </pre>';
  }

$page_specific_css = '
  <style type="text/css">
    pre {
      background: none repeat scroll 0 0 #fff;
      font-size:10px;
      color: #000;
      }
  </style>';

// Conditional for generating PDF invoices
if ($_GET['output'] == 'pdf')
  {
    $fp = fopen( FILE_PATH.PATH.'temp/route_list.html', a);
    fwrite($fp, $output);
  }
else
  {
    include("template_header.php");
    echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?type='.$type.'&delivery_id='.$delivery_id.($_GET['producer_id'] ? '&producer_id='.$_GET['producer_id'] : '').'&output=pdf&paginate=false">Download as PDF (continuous)</a><br>';
    echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?type='.$type.'&delivery_id='.$delivery_id.($_GET['producer_id'] ? '&producer_id='.$_GET['producer_id'] : '').'&output=pdf&paginate=true">Download as PDF (paginated)</a>';
    if (! $_GET['producer_id'])
      {
        echo '
          <style type="text/css">
            @media print {
              pre {
                page-break-after:always;
                }
          </style>';
      }
    echo $output;
    include("template_footer.php");
  }

// Conditional for generating PDF invoices
if ($_GET['output'] == 'pdf')
  {
    // Now convert to PDF and send to browser
    putenv("HTMLDOC_NOCGI=1");
    header("Content-Type: application/pdf");
    flush();
    passthru('htmldoc -t pdf --webpage '.FILE_PATH.PATH.'temp/route_list.html');
    unlink(FILE_PATH.PATH.'temp/route_list.html');
  }