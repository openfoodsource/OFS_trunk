<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

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
        $basket_status = '
        <li>
          Site: <strong>'.CurrentBasket::site_long().'</strong>
          [<a onClick="popup_src(\''.PATH.'select_delivery_popup.php?after_select=reload_parent()#target_site\', \'select_delivery\', \'\');">Change</a>]
        </li>
        <li>
          '.$basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').' in basket.
        </li>
        <li>
          <a href="'.PATH.'product_list.php?type=basket">View current basket</a>.
        </li>
        <li class="last_of_group">
          <a href="show_report.php?type=customer_invoice">View current invoice</a>
        </li>
        <li>
          <em class="warn">NOTE: Invoice will be blank until checked-out by an admin. All items in your basket will be checked out when the order closes.</em>
        </li>';
      }
    else
      {
        $basket_status = '
        <li class="last_of_group">
          [<a onClick="popup_src(\''.PATH.'select_delivery_popup.php?after_select=reload_parent()#target_site\', \'select_delivery\', \'\');">Select your site</a>] to begin shopping.
        </li>';
      }
  }
else
  {
    $basket_status = '
      Ordering has closed.<br />
      Basket has '.$basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').'.<br />
      Ordering will open again on '.(date ('l, F jS', strtotime (ActiveCycle::date_open_next ())));
  }
// Set up English grammar for ordering dates
$relative_text = '';
$close_suffix = '';
$open_suffix = '';
// Which order are we looking at?
if ( strtotime (ActiveCycle::date_open_next()) < time ()  && strtotime (ActiveCycle::date_closed_next()) > time ())
  $relative_text = 'Current&nbsp;';
elseif ( strtotime (ActiveCycle::date_closed_next()) > time () )
  $relative_text = 'Next&nbsp;';
else // strtotime (ActiveCycle::delivery_date_next()) < time ()
  $relative_text = 'Prior&nbsp;';
if ( strtotime (ActiveCycle::date_open_next()) < time () )
  $open_suffix = 'ed'; // Open[ed]
else
  $open_suffix = 's'; // Open[s]
// Order closing suffix
if ( strtotime (ActiveCycle::date_closed_next()) < time () )
  $close_suffix = 'd'; // Close[d]
else
  $close_suffix = 's'; // Close[s]


// Generate the display output
$display = '
  <table width="100%" class="compact">
    <tr>
      <td>
        <img src="'.DIR_GRAPHICS.'current.png" width="32" height="32" align="left" hspace="2" alt="Order"><br>
        <strong>'.$relative_text.'Order</strong>
        <ul class="fancyList1">
          <li><strong>
            Open'.$open_suffix.':</strong>&nbsp;'.date ('M&\n\b\s\p;j,&\n\b\s\p;g:i&\n\b\s\p;A&\n\b\s\p;(T)', strtotime (ActiveCycle::date_open_next())).'
          </li>
          <li>
            <strong>Close'.$close_suffix.':</strong>&nbsp;'.date ('M&\n\b\s\p;j,&\n\b\s\p;g:i&\n\b\s\p;A&\n\b\s\p;(T)', strtotime (ActiveCycle::date_closed_next())).'
          </li>
          <li class="last_of_group">
            <strong>Delivery:</strong>&nbsp;'.date ('F&\n\b\s\p;j', strtotime (ActiveCycle::delivery_date_next())).'
          </li>
        </ul>
        <img src="'.DIR_GRAPHICS.'shopping.png" width="32" height="32" align="left" hspace="2" alt="Basket Status"><br>
        <strong>Current Order Status</strong>
        <ul class="fancyList1">
          '.$basket_status.'
        </ul>
        <img src="'.DIR_GRAPHICS.'product.png" width="32" height="32" align="left" hspace="2" alt="Order Info"><br>
        <b>Past Orders</b>
        <ul class="fancyList1">
          <li class="last_of_group">
            [<a onClick="popup_src(\''.PATH.'select_order_history_popup.php\', \'select_order_history\', \'\');">View all baskets and invoices</a>]
          </li>
        </ul>
      </td>
      <td align="left" valign="top" width="50%">
        <img src="'.DIR_GRAPHICS.'invoices.png" width="32" height="32" align="left" hspace="2" alt="Available Products"><br>
        <b>'.(ActiveCycle::ordering_window() == 'open' ? 'Available Products' : 'Products (Shopping is closed)').'</b>
        <ul class="fancyList1">';

$search_display = '
  <form action="product_list.php" method="get">
    <input type="hidden" name="type" value="search">
    <input type="text" name="query" value="'.(isset ($_GET['query']) ? $_GET['query'] : '').'">
    <input type="submit" name="action" value="Search">
  </form>';

$display .= '
          <li class="last_of_group">'.$search_display.'</li>';

if (CurrentMember::auth_type('unfi')) $display .= '
          <!-- <li><a href="product_list.php?type=unfi">All products (UNFI)</a></li> -->';
$display .= '
          <li>                        <a href="category_list.php?display_as=grid">    Browse by category</a></li>
          <li>                        <a href="prdcr_list.php">                       Browse by producer</a></li>
          <li class="last_of_group">  <a href="product_list.php?type=prior_baskets">  Previously ordered products</a></li>
<!--      <li>                        <a href="product_list.php?type=by_id">          All products by number</a></li> -->
<!--      <li class="last_of_group">  <a href="product_list.php?type=full">           All products by category</a></li> -->
          <li>                        <a href="product_list.php?type=organic">        Organic products</a></li>
          <li>                        <a href="product_list.php?type=new">            New products</a></li>
          <li>                        <a href="product_list.php?type=changed">        Changed products</a></li>'.
(CurrentMember::auth_type('institution') ? '
          <li>                        <a href="product_list.php?type=wholesale">      Wholesale products</a></li>' : '').'
        </ul>
      </td>
    </tr>
  </table>';

$page_specific_javascript = '';

$page_specific_css = '
<style type="text/css">
.warn {
  color:#844;
  }
input[type="submit"] {
  padding:5px 10px;
  }
</style>';

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