<?php
include_once 'config_openfood.php';
session_start();

// README  README  README  README  README  README  README  README  README  README  README  README 
// 
// The function of this program is to cycle through all the baskets in all the orders and do a
// "checkout" procedure on them, causing the basket_items to be added to the ledger and adding
// ledger entries for such things as delivery fees and other whole-basket items.

$content = '';
$ajax_content = '';

// CHECK FOR AJAX CALL (for compactness, this script handles its own ajax)
if (isset ($_REQUEST['ajax']) && $_REQUEST['ajax'] == 'yes')
  {
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Get a list of baskets for the particular delivery_id                  //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'get_basket_list')
      {
        $delivery_id = $_REQUEST['delivery_id'];
        $ajax_content = '
          <ul>
            <li><div class="p_list_header p_list_pid">Basket&nbsp;ID</div><div class="p_list_header p_list_name">Member # / [Quantity]</div></a></li>';
        $query = '
          SELECT
            '.NEW_TABLE_BASKETS.'.member_id,
            '.NEW_TABLE_BASKETS.'.basket_id,
            COUNT('.NEW_TABLE_BASKET_ITEMS.'.product_id) AS quantity
          FROM
            '.NEW_TABLE_BASKETS.'
          LEFT JOIN
            '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
          WHERE
            '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
          GROUP BY
            '.NEW_TABLE_BASKET_ITEMS.'.basket_id
          ORDER BY
            '.NEW_TABLE_BASKETS.'.basket_id';
        $result= mysql_query("$query") or die("Error: 678574" . mysql_error());
        while($row = mysql_fetch_object($result))
          {
            $ajax_content .= '
          <li id="producer_id:'.$row->basket_id.'" class="basket_incomplete" onClick="window.open(\'validate_customer_basket_items.php?ajax=yes&process=view_invoice&basket_id='.$row->basket_id.'\',\'external\')"><div class="p_list_pid">'.$row->basket_id.'</div><div class="p_list_name">Member '.$row->member_id.' ['.$row->quantity.' Items]</div></li>';
          }
        $ajax_content .= '
          </ul>';
        echo "$ajax_content";
        exit (0);
      }
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Add basket items and basket aggregate functions to the ledger table   //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'process_basket')
      {
        $basket_id = $_REQUEST['basket_id'];
        // echo 'PAUSE                         ';
        // Get information about this basket
        include_once ('func.update_basket.php');
        $basket_info = get_basket ($basket_id);
        // Now go create the ledger entries...
        $basket_info = update_basket(array(
          'basket_id' => $basket_id,
          'delivery_id' => $basket_info['delivery_id'],
          'member_id' => $basket_info['member_id'],
          'action' => 'checkout'
          ));
        if (is_array ($basket_info))
          {
            echo 'OKAY      ';
            exit (0); // Success
          }
      }
    // Ajax as called, but not used, so exit with error
    exit (1);
  }


// BEGIN GENERATING MAIN PAGE //////////////////////////////////////////////////
$content .= '
  <div id="controls">
    <div id="basket_generate_start">
      <input id="delivery_generate_button" type="submit" onClick="reset_delivery_list(); delivery_generate_start(); generate_basket_list();" value="Begin Processing">
    </div>
    <div id="delivery_progress"><div id="c_progress-left"></div><div id="c_progress-right"></div></div>
    <div id="basket_progress"><div id="p_progress-left"></div><div id="p_progress-right"></div></div>
  </div>
<div id="reporting">
  <div id="left-column">
    <div id="customerBox">
      <div class="customerList" id="customerList">
        <ul>
          <li><div class="c_list_header c_list_cid">Del&nbsp;ID</div><div class="c_list_header c_list_name">Date [Quantity]</div></a></li>';
// Get a list of all delivery_id values
$query = '
  SELECT
    '.TABLE_ORDER_CYCLES.'.delivery_id,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    COUNT('.NEW_TABLE_BASKETS.'.basket_id) AS quantity
  FROM
    '.TABLE_ORDER_CYCLES.'
  RIGHT JOIN '.NEW_TABLE_BASKETS.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.NEW_TABLE_BASKETS.'.delivery_id
  GROUP BY '.NEW_TABLE_BASKETS.'.delivery_id';
$result= mysql_query($query) or die("Error: 899032" . mysql_error());
while($row = mysql_fetch_object($result))
  {
    $content .= '          <li id="delivery_id:'.$row->delivery_id.'" class="del_incomplete"><div class="c_list_cid">'.$row->delivery_id.'</div><div class="c_list_name">'.$row->delivery_date.' ['.$row->quantity.' Orders]</div></li>';
  }
$content .= '
        </ul>
      </div>
    </div>
  </div>
  <div id="right-column">
    Pause: <input type="checkbox" id="pause" name="pause" onClick="process_basket_list()">
    Delivery: <input type="text" size="8" id="delivery_id" name="delivery_id">
    Basket: <input type="text" size="8" id="basket_id" name="basket_id">
    <div id="basketBox">
      <div class="basketList" id="basketList">
  [process information goes here]
      </div>
    </div>
  </div>
</div>
<div id="process_area" style="clear:both;">
  <div id="process_target">[process here]</div>
</div>';

$page_specific_javascript = '
    <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
    <script type="text/javascript" src="'.PATH.'create_ledger.js"></script>';

$page_specific_css = '
    <link href="'.PATH.'create_ledger.css" rel="stylesheet" type="text/css">';

$page_title_html = '<span class="title">Site Admin Functions</span>';
$page_subtitle_html = '<span class="subtitle">Convert Accounting</span>';
$page_title = 'Site Admin Functions: Convert Accounting';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
