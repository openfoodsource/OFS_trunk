<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

// Set the defaults
$page = 1;
$member_count = 0;

// ... but if a targeted delivery is requested then use that.
if (isset ($_GET['page']))
  $page = $_GET['page'];

$query = '
  SELECT
    SQL_CALC_FOUND_ROWS
    member_id,
    preferred_name,
    membership_discontinued
  FROM
    '.TABLE_MEMBER.'
  WHERE membership_discontinued != "1"
  ORDER BY
    member_id
  LIMIT '.(($page - 1) * PER_PAGE).', '.PER_PAGE;
$result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 673032 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));

// Get the total number of rows (for pagination) -- not counting the LIMIT condition
$query_found_rows = '
  SELECT
    FOUND_ROWS() AS found_rows';
$result_found_rows = @mysqli_query ($connection, $query_found_rows) or die (debug_print ("ERROR: 754321 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$row_found_rows = mysqli_fetch_array ($result_found_rows, MYSQLI_ASSOC)['found_rows'];
$last_page = ceil ($row_found_rows / PER_PAGE);
$pager_navigation = pager_navigation($page, $last_page, $pager_id='receive_payments_pager');
$page_data .= '
  <div class="pager">
    '.$pager_navigation.'
  </div>';

while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
  {
    $page_data .= '
      <div id="member_id'.$row['member_id'].'" class="basket_section">
        <span class="member_id">'.$row['member_id'].'</span>
        <span class="site_id">[N/A]</span>
        <span class="member_name">'.$row['preferred_name'].'</span>
        <span class="controls"><input type="button" value="Receive Payment" onclick="show_receive_payment_form('.$row['member_id'].',0)"></span>
        <div id="basket_id'.$basket_id.'" class="ledger_info">'.
          $receive_payments_detail_line.'
        </div>
      </div>';
  }

function pager_navigation($this_page, $last_page, $pager_id)
  {
    if (! isset ($this_page)) $this_page = 1;
    $pager_navigation = '<form id="'.$pager_id.'" name="'.$pager_id.'" action="'.$_SERVER['SCRIPT_NAME'].'" method="GET">
      <div id="'.$pager_id.'_container" class="pager">
        <span class="button_position">
          <div id="'.$pager_id.'_decrement" class="pager_decrement" onclick="decrement_pager(jQuery(this).closest(\'form\').attr(\'id\'));"><span>&ominus;</span></div>
        </span>
        <input type="hidden" id="'.$pager_id.'_slider_prior" value="'.$this_page.'">
        <span class="pager_center">
          <input type="range" id="'.$pager_id.'_slider" class="pager_slider" name="page" min="1" max="'.$last_page.'" step="1" value="'.$this_page.'" onmousemove="update_pager_display(jQuery(this).closest(\'form\').attr(\'id\'));" onchange="goto_pager_page(jQuery(this).closest(\'form\').attr(\'id\'));">
        </span>
        <span class="button_position">
          <div id="'.$pager_id.'_increment" class="pager_increment" onclick="increment_pager(jQuery(this).closest(\'form\').attr(\'id\'));"><span>&oplus;</span></div>
        </span>
      </div>
      <output id="'.$pager_id.'_display_value" class="pager_display_value">Page '.$this_page.'</output>
    </form>
    <div class="clear"></div>';
    return $pager_navigation;
  };

$page_specific_scripts['receive_payments'] = array (
  'name'=>'receive_payments',
  'src'=>BASE_URL.PATH.'receive_payments.js',
  'dependencies'=>array('jquery'),
  'version'=>'2.1.1',
  'location'=>false
  );

$page_specific_stylesheets['receive_payments'] = array (
  'name'=>'receive_payments',
  'src'=>BASE_URL.PATH.'receive_payments.css',
  'dependencies'=>array('ofs_stylesheet'),
  'version'=>'2.1.1',
  'media'=>'all'
  );

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Receive Payments</span>';
$page_title = 'Delivery Cycle Functions: Receive Payments';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->'.
  $pager_display.
  $page_data.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
