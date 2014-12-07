<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

// Set defaults

// Include the display (ajax) page so we can go immediately to the last page...
$call_display_as_include = true;
include_once (FILE_PATH.PATH.'ajax/display_order_schedule.php');

$display = '
<div id="spinner_container"><div id="spinner" style="display:none;"></div></div>
<div id="main_content">
  <fieldset class="controls">
    <div id="pager" class="internal">
      <label for="data_page">Page:</label>
      <span id="decrement_data_page" class="decrement" onclick="decrement(\'data_page\', $(\'#this_page\').val());" style="display:none;">&#9664;</span>
      <span id="data_page" onkeyup="constrain(\'data_page\', $(\'#this_page\').val());debounce_pager(\'data_page\', $(\'#this_page\').val());" onchange="constrain(\'data_page\', $(\'#this_page\').val());debounce_pager(\'data_page\', $(\'#this_page\').val());" onfocus="document.execCommand(\'selectAll\',false,null)" contenteditable>'.$found_pages.'</span>
      <div id="maximum_data_page">/ 1</div>
      <span id="increment_data_page" class="increment" onclick="increment(\'data_page\', $(\'#this_page\').val());">&#9654;</span>
    </div>
    <input type="hidden" id="this_page" name="this_page" value="internal">
  </fieldset>
  <div id="order_schedule_content">
  </div>
</div>
';

$page_specific_javascript = '
  <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
  <script type="text/javascript" src="'.PATH.'ajax/jquery-simplemodal.js"></script>
  <script type="text/javascript" src="'.PATH.'ajax/jquery-ui.js"></script>

  <script type="text/javascript">
  // Load the first page automatically
  $( document ).ready(function() {
    // Handler for .ready() called.
    debounce_pager(\'data_page\', $(\'#this_page\').val());
    });
  // Set default values
  var minimum = new Array();
  var maximum = new Array();
  var actual = new Array();
  minimum["order_schedule_page"] = 1; // Page 1 is always the lowest possible page
  actual["order_schedule_page"] = 0;  // Set 0 to force load the first time a page is requested
  maximum["order_schedule_page"] = 1; // Default value until we have additional data


  // Debounce function from: http://davidwalsh.name/javascript-debounce-function
  // Returns a function, that, as long as it continues to be invoked, will not
  // be triggered. The function will be called after it stops being called for
  // N milliseconds. If `immediate` is passed, trigger the function on the
  // leading edge, instead of the trailing.
  function debounce(func, wait, immediate) {
    var timeout;
    return function() {
      var context = this, args = arguments;
      var later = function() {
        timeout = null;
        if (!immediate) func.apply(context, args);
        };
      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(context, args);
      };
    };

  // Pager functions for incrementing and decrementing target pages
  function increment (target, this_page) {
    $("#"+target).html($("#"+target).html()*1+1);
    constrain (target, this_page);
    debounce_pager(target, this_page);
    }
  function decrement (target, this_page) {
    $("#"+target).html($("#"+target).html()*1-1);
    constrain (target, this_page);
    debounce_pager(target, this_page);
    }
  // Constrain value to within min/max limits
  function constrain (target_id, target_type) {
    if ($("#"+target_id).html() > maximum["order_schedule_page"]) {
      $("#"+target_id).html(maximum["order_schedule_page"]);
      $("#increment_"+target_id).hide();
      }
    else {
      $("#increment_"+target_id).show();
      }
    if ($("#"+target_id).html() < minimum["order_schedule_page"]) {
      $("#"+target_id).html(minimum["order_schedule_page"]);
      $("#decrement_"+target_id).hide();
      }
    else {
      $("#decrement_"+target_id).show();
      }
    // Set the maximum_data_page text
    $("#maximum_data_page").html(" / "+maximum["order_schedule_page"]);
    }
  var debounce_pager = debounce(function(target, this_page) {
    get_order_cycles(target, this_page);
    }, 1000);

  function get_order_cycles(target, this_page) {
    if (document.getElementById(target).value != actual["order_schedule_page"]) {
      actual["order_schedule_page"] = document.getElementById(target).innerHTML;
      // Start the spinner (change color of the "order_schedule_page" field)
      $("#data_page").addClass("spinning")
      $.ajax({
        type: "POST",
        url: "'.PATH.'ajax/display_order_schedule.php",
        cache: false,
        data: {
          data_page: $("#"+target).html()
          }
        })
      .done(function(json_schedule_info) {
        schedule_info = JSON.parse(json_schedule_info);
        maximum["order_schedule_page"] = schedule_info.maximum_data_page;
        constrain (\'data_page\');
        constrain (\'data_page\', this_page);
        // Stop the spinner (restore color of the "order_schedule_page" field)
        $("#data_page").removeClass("spinning")
        $("#order_schedule_content").html(schedule_info.markup);
        });
      }
    }

  // Functions to highlight calendar segments
  function highlight_calendar(target) {
    $(".cycle-"+target).addClass("highlight");
    // document.getElementById("id-"+target).style.color="#880000";
    }
  function restore_calendar(target) {
    $(".cycle-"+target).removeClass("highlight");
    // document.getElementById("id-"+target).style.color="#000000";
    }

  // Display an external page using an iframe
  // http://www.ericmmartin.com/projects/simplemodal/
  // Set the simplemodal close button
  $.modal.defaults.closeClass = "modalClose";
  // Popup the simplemodal dialog for selecting a site
  function popup_src(src) {
    $.modal(\'<a class="modalCloseImg modalClose">&nbsp;</a><iframe src="\' + src + \'">\', {
      opacity:70,
      overlayCss: {backgroundColor:"#000"},
      closeHTML:"",
      containerCss:{
        backgroundColor:"#fff",
        borderColor:"#fff",
        height:"80%",
        width:"80%",
        padding:0
        },
      overlayClose:true
      });
    };
  // Close the simplemodal iframe after 500 ms
  function close_delivery_selector() {
    setTimeout(function (){
      $.modal.close();
      }, 1000);
    }
  </script>';

$page_specific_css = '
  <style type="text/css">

/* BEGIN TAB STYLES */

  #main_content {
    width:100%;
    border-right:1px solid #888;
    border-bottom:1px solid #888;
    border-left:1px solid #888;
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

  #order_schedule_content {
    }

  .order_cycle_row:nth-child(even) {
    background: #ffd;
    }
  .order_cycle_row:nth-child(odd) {
    background: #eef;
    }
  #id-header div {
    font-weight:bold;
    }
  .order_cycle_row {
    width:60%;
    height:40px;
    font-size:12px;
    cursor:default;
    }
  .order_cycle_row:hover {
    background-color:rgba(0, 0, 0, .2);
    }
  .delivery_id {
    position:relative;
    top:0px; left:0px;
    height:15px;
    width:50px;
    overflow:hidden;
    text-align:left;
    padding-right:1em;
    }
  .date_open {
    position:relative;
    top:-15px; left:50px;
    height:15px;
    width:150px;
    overflow:hidden;
    }
  .date_closed {
    position:relative;
    top:-30px; left:200px;
    height:15px;
    width:150px;
    overflow:hidden;
    }
  .order_fill_deadline {
    position:relative;
    top:-45px; left:350px;
    height:15px;
    width:150px;
    overflow:hidden;
    } 
  .delivery_date {
    position:relative;
    top:-60px; left:500px;
    height:15px;
    width:150px;
    overflow:hidden;
    } 

  .edit_link {
    position:relative;
    top:-75px; left:650px;
    height:15px;
    width:50px;
    overflow:hidden;
    text-align:center;
    cursor:pointer;
    }

  /* BEGIN STYLES FOR CALENDAR */
  #calendar {
    float:right;
    position:relative;
    width:30%;
    }
  .week_row {
    position:static;
    overflow:hidden;
    height:40px;
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
    height:40px;
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
  .distinct-1 div {
    background-color:#ace;
    }
  .distinct-2 div {
    background-color:#aec;
    }
  .distinct-3 div {
    background-color:#cea;
    }
  .distinct-4 div {
    background-color:#cae;
    }
  .distinct-5 div {
    background-color:#eac;
    }
  .distinct-6 div {
    background-color:#eca;
    }
  .distinct-6 div {
    background-color:#ccc;
    }

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

  /* BEGIN STYLES FOR SIMPLEMODAL OVERLAY */

  #simplemodal-data {
    height:100%;
    background-color:#fff;
    }
  #simplemodal-container {
    box-shadow:10px 10px 10px #000;
    }
  #simplemodal-data iframe {
    border:0;
    height:95%;
    width:100%;
    }
  #simplemodal-container a.modalCloseImg {
    background:url('.DIR_GRAPHICS.'/simplemodal_x.png) no-repeat; /* adjust url as required */
    width:25px;
    height:29px;
    display:inline;
    z-index:3200;
    position:absolute;
    top:0px;
    right:0px;
    cursor:pointer;
  }
  </style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Inspect Accounts</span>';
$page_title = 'Reports: Inspect Accounts';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
?>