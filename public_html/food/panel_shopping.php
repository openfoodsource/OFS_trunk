<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.open_update_basket.php');
include_once ('func.get_baskets_list.php');
include_once ('func.get_delivery_codes_list.php');

/////////////// FINISH PRE-PROCESSING AND BEGIN PAGE GENERATION /////////////////

// Get basket status information
$query = '
  SELECT
    COUNT(product_id) AS basket_quantity,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.delivery_type
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
    AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string (ActiveCycle::delivery_id()).'
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 657922 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$basket_quantity = 0;
if ($row = mysql_fetch_object($result))
  {
    $basket_quantity = $row->basket_quantity;
    $basket_id = $row->basket_id;
    $delivery_type = $row->delivery_type;
  }
if ( ActiveCycle::ordering_window() == 'open')
  {
    if ($basket_id)
      {
        $basket_status = 'Ready for shopping<br>'.$basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').' in basket';
      }
    else
      {
        $basket_status = '
          <em>Use Select Location (above) to open a shopping basket</em>';
      }
  }
else
  {
    $basket_status = 'Ordering is currently closed<br>'.$basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').' in basket';
  }

// Set content_top to show basket selector...
$delivery_codes_list .= get_delivery_codes_list (array (
  'action' => $_GET['action'],
  'member_id' => $_SESSION['member_id'],
  'delivery_id' => ActiveCycle::delivery_id(),
  'site_id' => $_GET['site_id'],
  'delivery_type' => $_GET['delivery_type']
  ));
$baskets_list .= get_baskets_list ();

// Generate the display output
$display .= '
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">'.
        ($delivery_codes_list ? '<div class="content_top">'.
        $delivery_codes_list.'
        </div>' : '').'
      </td>
      <td align="right" width="50%">'.
        ($baskets_list ? '<div class="content_top" style="float:right;">'.
        $baskets_list.'
        </div>' : '').'
      </td>
    </tr>
    <tr>
      <td>';

$display .= '
    <img src="'.DIR_GRAPHICS.'shopping.png" width="32" height="32" align="left" hspace="2" alt="Basket Status"><br>
    <strong>Basket Status</strong>
        <ul class="fancyList1">
          <li class="last_of_group">'.$basket_status.'</li>
        </ul>
        <img src="'.DIR_GRAPHICS.'product.png" width="32" height="32" align="left" hspace="2" alt="Order Info"><br>
        <b>Order Info</b>
        <ul class="fancyList1">
          <li><a href="product_list.php?type=basket">View items in basket</a></li>
          <li><a href="show_report.php?type=customer_invoice">View invoice</a><br />
          <em>(Invoice is blank until after the order closes)</em></li>
          <li class="last_of_group"><a href="past_customer_invoices.php?member_id='.$_SESSION['member_id'].'">Past Customer Invoices</a></li>
        </ul>
      </td>
      <td align="left" width="50%">
        <img src="'.DIR_GRAPHICS.'invoices.png" width="32" height="32" align="left" hspace="2" alt="Available Products"><br>
        <b>'.(ActiveCycle::ordering_window() == 'open' ? 'Available Products' : 'Products (Shopping is closed)').'</b>
        <ul class="fancyList1">';

$search_display = '
  <form action="product_list.php" method="get">
    <input type="hidden" name="type" value="search">
    <input type="text" name="query" value="'.$_GET['query'].'">
    <input type="submit" name="action" value="Search">
  </form>';

$display .= '
          <li class="last_of_group">'.$search_display.'</li>';

if (CurrentMember::auth_type('unfi')) $display .= '
          <!-- <li><a href="product_list.php?type=unfi">All products (UNFI)</a></li> -->';
$display .= '
          <li>                        <a href=category_list2.php>                     Browse by category</a></li>
          <li>                        <a href="prdcr_list.php">                       Browse by producer</a></li>
          <li class="last_of_group">  <a href="product_list.php?type=prior_baskets">  Previously ordered products</a></li>
          <li>                        <a href="product_list.php?type=by_id">          All products by number</a></li>
          <li class="last_of_group">  <a href="product_list.php?type=full">           All products by category</a></li>
          <li>                        <a href="product_list.php?type=organic">        Organic products</a></li>
          <li>                        <a href="product_list.php?type=new">            New products</a></li>
          <li>                        <a href="product_list.php?type=changed">        Changed products</a></li>'.
(CurrentMember::auth_type('institution') ? '
          <li>                        <a href="product_list.php?type=wholesale">      Wholesale products</a></li>' : '').'
        </ul>
      </td>
    </tr>
  </table>';

$page_specific_javascript .= '';

$page_specific_css .= '
<link rel="stylesheet" type="text/css" href="delivery_dropdown.css">
<link rel="stylesheet" type="text/css" href="basket_dropdown.css">
<style type="text/css">
.content_top {
  margin-bottom:45px;
  width:300px;
  }
#basket_dropdown {
  float:right;
  }
</style>';

// Show the delivery-location chooser ONLY...
if ($_GET['action'] == 'delivery_list_only' && $delivery_codes_list)
  {
    // Clobber the display and only show the delivery location list
    $display = $delivery_codes_list;
    // Add styles to override delivery location dropdown
    $page_specific_css .= '
      <style type="text/css">
      /* OVERRIDE THE DROPDOWN CLOSURE FOR MOBILE DEVICES */
      #delivery_dropdown {
        position:static;
        height:auto;
        width:100%;
        overflow:hidden;
        }
      #delivery_dropdown:hover {
        width:100%;
        }
      #delivery_select {
        width:100%;
        height:auto;
        }
      #delivery_dropdown:hover {
        height:auto;
        }
      </style>';
  }

if ($_GET['action'] == 'basket_list_only' && $baskets_list)
  {
    // Clobber the display and only show the delivery location list
    $display = $baskets_list;
    // Add styles to override delivery location dropdown
    $page_specific_css .= '
      <style type="text/css">
      /* OVERRIDE THE DROPDOWN CLOSURE FOR MOBILE DEVICES */
      #basket_dropdown {
        position:static;
        height:auto;
        width:100%;
        overflow:hidden;
        }
      #basket_dropdown:hover {
        width:100%;
        }
      #basket_history {
        width:100%;
        height:auto;
        }
      #basket_dropdown:hover {
        height:auto;
        }
      </style>';
  }

$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Shopping Panel</span>';
$page_title = 'Shopping Panel';
$page_tab = 'shopping_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");