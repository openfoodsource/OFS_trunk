<?php
session_start();
include_once 'config_openfood.php';
valid_auth('member_admin,site_admin,cashier');

// Set defaults

// Include the display (ajax) page so we can go immediately to the last page...
$call_display_as_include = true;
$_POST['per_page'] = 10; // Force per_page setting
include_once (FILE_PATH.PATH.'ajax/display_order_schedule.php');

$display = '
<div id="main_content">'.
    ($calendar_data['maximum_data_page'] > 1 ? // Show the pager if there is more than one page to show
    '<form id="calendar_pager" name="calendar_pager" onsubmit="get_order_cycles(\'data_page\', jQuery(\'#this_page\').val())">'.
    ($calendar_data['data_page'] ? '
      <div id="calendar_pager_container" class="pager">
        <span class="button_position">
          <div id="calendar_pager_decrement" class="pager_decrement" onclick="decrement_pager(\'calendar_pager\', jQuery(\'#this_page\').val());"><span>&ominus;</span></div>
        </span>
        <input type="hidden" id="calendar_pager_slider_prior" value="'.$calendar_data['data_page'].'">
        <span class="pager_center">
          <input type="range" id="calendar_pager_slider" class="pager_slider" name="page" min="1" max="'.$calendar_data['maximum_data_page'].'" step="1" value="'.$calendar_data['data_page'].'" onmousemove="update_pager_display(jQuery(this).closest(\'form\').attr(\'id\'));" onchange="goto_pager_page(\'calendar_pager\');">
        </span>
        <span class="button_position">
          <div id="calendar_pager_increment" class="pager_increment" onclick="increment_pager(\'calendar_pager\', jQuery(\'#this_page\').val());"><span>&oplus;</span></div>
        </span>
      </div>
      <output id="calendar_pager_display_value" class="pager_display_value">Page '.$calendar_data['data_page'].'</output>
    </form>' : '').'
    <div class="clear"></div>'
    : '').'
  <div id="order_schedule_content">'.
  $calendar_data['markup'].'
  </div>
</div>
';

$page_specific_javascript = '
  function goto_pager_page_override(target) {
    var this_page = jQuery("#"+target+"_slider_prior").val();
    jQuery.ajax({
      type: "POST",
      url: "'.PATH.'ajax/display_order_schedule.php",
      cache: false,
      data: {
        data_page: jQuery("#"+target+"_slider").val(),
        per_page: '.$_POST['per_page'].'
        }
      })
    .done(function(json_schedule_info) {
      schedule_info = JSON.parse(json_schedule_info);
      jQuery("#order_schedule_content").html(schedule_info.markup);
      jQuery("#calendar_pager_slider_prior").val(schedule_info.data_page);
      });
    return false;
    }

  // Functions to highlight calendar segments
  function highlight_calendar(target) {
    jQuery(".cycle-"+target).addClass("highlight");
    // document.getElementById("id-"+target).style.color="#880000";
    }
  function restore_calendar(target) {
    jQuery(".cycle-"+target).removeClass("highlight");
    // document.getElementById("id-"+target).style.color="#000000";
    }';

$page_specific_css = '
/* BEGIN TAB STYLES */

  #main_content {
    margin:20px;
    overflow:auto;
    }

  /* BEGIN BASIC FORM STYLES */

  fieldset.controls {
    text-align:center;
    }
  #data_page {
    font-weight:bold;
    padding: 0 3px;
    }
  #data_page:focus {
    outline: none;
    }
  #data_page.spinning {
    animation:pulse_spinner 1s infinite;
    }
  #maximum_data_page {
    display:inline;
    }
  #pager {
  margin-top:0.3em;
    }
  .increment,
  .decrement {
    width:1em;
    cursor:pointer;
    color:#888;
    -webkit-user-select: none; /* Chrome/Safari prevent double-click highlighting */        
    -moz-user-select: none; /* Firefox prevent double-click highlighting */
    -ms-user-select: none; /* IE10+ prevent double-click highlighting */
    }
  .increment:hover,
  .decrement:hover {
    color:#008;
    }

  /* BEGIN SPINNER STYLES */

  @keyframes pulse_spinner {
    0%   {color:#aaa;}
    50%  {color:#00a;}
    100%   {color:#aaa;}
    }

  /* BEGIN CHART DATA STYLES */

  #id-header div {
    font-weight:bold;
    }
  .order_cycle_row {
    font-size:10px;
    cursor:default;
    position: absolute;
    left:105%; /* Just more than calendar width */
    margin-top:-45px; /* Move it up a tad over one calendar row */
    overflow:auto;
    padding: 8px;
    border: 1px solid #888;
    border-radius: 10px;
    width:150%
    }
  .order_cycle_row:hover {
    background-color:rgba(0, 0, 0, .2);
    }
  .order_cycle_row .key +.value:before {
    content:": ";
    }
  .order_cycle_row .key {
    font-weight:bold;
    }
  .delivery_id,
  .date_open,
  .date_closed,
  .order_fill_deadline,
  .customer_type,
  .delivery_date {
    white-space:nowrap;
    display:inline-block;
    margin-right:1em;
    }

  /* BEGIN STYLES FOR CALENDAR */
  #calendar {
    float:left;
    position:relative;
    width:38%;
    }
  .month_name {
    display:block;
    position:absolute;
    line-height:150%;
    left:0;
    font-size:25px;
    font-weight:bold;
    color:rgba(128,128,64,.5);
    }
  .week_row {
    position:static;
    overflow:hidden;
    height:50px;
    width:99.5%;
    border-left:1px solid #ccc;
    }
  .day {
    width:100%;
    height:100%;
    border:1px solid #ccc;
    border-left:0;
    border-bottom:0;
    }
  .day_frame {
    height:50px;
    width:14.25%;
    float:left;
    z-index:10;
    }
  .day_no-7 {
    clear:left;
    }
  .day .cal_date {
    display:block;
    height:33%;
    font-size:10px;
    }
  .cycle {
    position:relative;
    height:10px;
    z-index:20;
    }
  .cycle .ordering {
    position:absolute;
    opacity:0.3;
    height:10px;
    border:1px solid #888;
    border-top:3px solid #800;
    }
  .cycle .filling {
    position:absolute;
    opacity:0.3;
    height:10px;
    border:1px solid #888;
    border-top:3px solid #008;
    }
  .cycle .delivery {
    position:absolute;
    opacity:0.3;
    height:10px;
    border:1px solid #888;
    border-top:3px solid #060;
    }
  .cycle .highlight {
    opacity:0.8;
    border-bottom:1px solid #888;
    }
  /* Color code for the various order cycles */
  .order_cycle_row.distinct-1,
  .distinct-1 div {
    background-color:#ace;
    }
  .order_cycle_row.distinct-2,
  .distinct-2 div {
    background-color:#aec;
    }
  .order_cycle_row.distinct-3,
  .distinct-3 div {
    background-color:#cea;
    }
  .order_cycle_row.distinct-4,
  .distinct-4 div {
    background-color:#cae;
    }
  .order_cycle_row.distinct-5,
  .distinct-5 div {
    background-color:#eac;
    }
  .order_cycle_row.distinct-6,
  .distinct-6 div {
    background-color:#eca;
    }
  /* Give months distinctive colors */
  .month_no-1,
  .month_no-3,
  .month_no-5,
  .month_no-7,
  .month_no-9,
  .month_no-11 {
    background-color:rgba(255,255,255,.1);
    }
  .month_no-2,
  .month_no-4,
  .month_no-6,
  .month_no-8,
  .month_no-10,
  .month_no-12 {
    background-color:rgba(0,0,0,.1);
    }';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Ordering Calendar</span>';
$page_title = 'Reports: Inspect Accounts';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
