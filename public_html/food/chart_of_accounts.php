<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

// Set defaults


// See documentation for the auto-fill menu at http://api.jqueryui.com/autocomplete/
$display = '

<link rel="stylesheet" hrefx="//code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">

<div id="spinner_container"><div id="spinner" style="display:none;"></div></div>
<div id="tab_holder">
  <div class="tab_frame">
    <div id="tab_member" class="tab" onclick="set_tab(\'member\');">
      <a class="member">Member</a>
    </div>
  </div>
  <div class="tab_frame">
    <div id="tab_producer" class="tab" onclick="set_tab(\'producer\');">
      <a class="producer">Producer</a>
    </div>
  </div>
  <div class="tab_frame">
    <div id="tab_internal" class="tab tab_active" onclick="set_tab(\'internal\');">
      <a class="internal">Internal</a>
    </div>
  </div>
  <div class="tab_frame">
    <div id="tab_tax" class="tab" onclick="set_tab(\'tax\');">
      <a class="tax">Tax</a>
    </div>
  </div>
</div>
<div id="main_content">
  <fieldset class="controls">
    <div id="pager" class="internal">
      <label for="data_page">Page:</label>
      <span id="decrement_data_page" class="decrement" onclick="decrement(\'data_page\', $(\'#this_tab\').val());" style="display:none;">&#9664;</span>
      <span id="data_page" onkeyup="constrain(\'data_page\', $(\'#this_tab\').val());debounce_pager(\'data_page\', $(\'#this_tab\').val());" onchange="constrain(\'data_page\', $(\'#this_tab\').val());debounce_pager(\'data_page\', $(\'#this_tab\').val());" onfocus="document.execCommand(\'selectAll\',false,null)" contenteditable>1</span>
      <div id="maximum_data_page">/ 1</div>
      <span id="increment_data_page" class="increment" onclick="increment(\'data_page\', $(\'#this_tab\').val());">&#9654;</span>
    </div>
    <input type="hidden" id="this_tab" name="this_tab" value="internal">
  </fieldset>
  <div id="member_content">
  </div>
  <div id="producer_content">
  </div>
  <div id="internal_content">
  </div>
  <div id="tax_content">
  </div>
</div>
';

$page_specific_javascript = '
  <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
  <script type="text/javascript" src="'.PATH.'ajax/jquery-simplemodal.js"></script>
  <script type="text/javascript" src="'.PATH.'adjust_ledger.js"></script>
  <script type="text/javascript" src="'.PATH.'ajax/jquery-ui.js"></script>

  <script type="text/javascript">
  // Load the first page automatically
  $( document ).ready(function() {
    // Handler for .ready() called.
    debounce_pager(\'data_page\', $(\'#this_tab\').val());
    });
  // Set default values
  var minimum = new Array();
  var maximum = new Array();
  var actual = new Array();
  minimum["member_page"] = 1;   // Page 1 is always the lowest possible page
  minimum["producer_page"] = 1; // Page 1 is always the lowest possible page
  minimum["internal_page"] = 1; // Page 1 is always the lowest possible page
  minimum["tax_page"] = 1;      // Page 1 is always the lowest possible page
  actual["member_page"] = 0;    // Set 0 to force load the first time a page is requested
  actual["producer_page"] = 0;  // Set 0 to force load the first time a page is requested
  actual["internal_page"] = 0;  // Set 0 to force load the first time a page is requested
  actual["tax_page"] = 0;       // Set 0 to force load the first time a page is requested
  maximum["member_page"] = 1;   // Default value until we have additional data
  maximum["producer_page"] = 1; // Default value until we have additional data
  maximum["internal_page"] = 1; // Default value until we have additional data
  maximum["tax_page"] = 1;      // Default value until we have additional data

  function set_tab (this_tab) {
    var old_tab = $(".tab_active").attr("id").substr(4);
    // Now switch the active tabs (class="tab_active")...
    $("#tab_"+old_tab).removeClass("tab_active");
    $("#tab_"+this_tab).addClass("tab_active");
    // Now assign the new tab to the input "this_tab"
    $("#this_tab").val(this_tab);

    // Hide the results for the old tab
    $("#"+old_tab+"_content").hide();
    // Show the results for the new tab
    $("#"+this_tab+"_content").show();

    // Set the page number to the "actual[]" stored value
    $("#data_page").html(actual[this_tab+"_page"]);
    // Convert the pager to correctly display for this page
    constrain ("data_page", $("#this_tab").val());
    // If the page data is not loaded, then do it
    if (actual[this_tab+"_page"] != $("#data_page").html) {
      debounce_pager(\'data_page\', this_tab);
      }
    }

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
  function increment (target, this_tab) {
    $("#"+target).html($("#"+target).html()*1+1);
    constrain (target, this_tab);
    debounce_pager(target, this_tab);
    }
  function decrement (target, this_tab) {
    $("#"+target).html($("#"+target).html()*1-1);
    constrain (target, this_tab);
    debounce_pager(target, this_tab);
    }
  // Constrain value to within min/max limits
  function constrain (target_id, target_type) {
    if ($("#"+target_id).html() > maximum[target_type+"_page"]) {
      $("#"+target_id).html(maximum[target_type+"_page"]);
      $("#increment_"+target_id).hide();
      }
    else {
      $("#increment_"+target_id).show();
      }
    if ($("#"+target_id).html() < minimum[target_type+"_page"]) {
      $("#"+target_id).html(minimum[target_type+"_page"]);
      $("#decrement_"+target_id).hide();
      }
    else {
      $("#decrement_"+target_id).show();
      }
    // Set the maximum_data_page text
    $("#maximum_data_page").html(" / "+maximum[target_type+"_page"]);
    }
  var debounce_pager = debounce(function(target, this_tab) {
    get_account_chart(target, this_tab);
    }, 1000);

  function get_account_chart(target, this_tab) {
    if (document.getElementById(target).value != actual[this_tab+"_page"]) {
      actual[this_tab+"_page"] = document.getElementById(target).innerHTML;
      // Start the spinner (change color of the "data_page" field)
      $("#data_page").addClass("spinning")
      $.ajax({
        type: "POST",
        url: "'.PATH.'ajax/display_account_chart.php",
        cache: false,
        data: {
          account_type: this_tab,
          data_page: $("#"+target).html()
          }
        })
      .done(function(json_chart_info) {
        chart_info = JSON.parse(json_chart_info);
        maximum[this_tab+"_page"] = chart_info.maximum_data_page;
        constrain (\'data_page\');
        constrain (\'data_page\', this_tab);
        // Stop the spinner (restore color of the "data_page" field)
        $("#data_page").removeClass("spinning")
        $("#"+this_tab+"_content").html(chart_info.markup);
        });
      }
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

  #tab_holder {
    width:100%;
    height:35px;
    padding:0;
    background-color:#fff;
    border-bottom:1px solid #444;
    }
  .tab_frame {
    float:left;
    width:25%;
    margin:0;
    }
  .tab {
    position:relative;
    margin:0;
    padding:0;
    height:33px;
    width:90%;
    margin:auto;
    cursor:pointer;
    }
  .tab_active {
    height:35px;
    }
  .tab a {
    display:block;
    width:100%;
    height:100%;
    border-top:1px solid #888;
    border-right:1px solid #888;
    border-bottom:1px solid #888;
    border-left:1px solid #888;
    background-color:#aaa;
    padding:0;
    text-align:center;
    overflow:hidden;
    }
  .tab a:hover {
    border-bottom:1px solid #ddd;
    background-color:#eec;
    text-decoration:none;
    }
  #tab_holder .tab_active a {
    background-color:#ffa;
    border-bottom:1px solid #ddd;
    }
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
  #account_type {
    margin-right:2em;
    }
  #account_name {
    width:25em;
    margin-right:2em;
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

  #member_content,
  #producer_content,
  #internal_content,
  #tax_content {
    }

  .account_row:nth-child(even) {
    background: #ffd;
    }
  .account_row:nth-child(odd) {
    background: #eef;
    }
  .account_row {
    width:99%;
    height:18px;
    font-size:12px;
    cursor:default;
    }
  .account_row:hover {
    background-color:rgba(0, 0, 0, .2);
    }
  .account_number {
    position:relative;
    top:0px; left:0px;
    height:15px;
    width:200px;
    overflow:hidden;
    text-align:left;
    padding-right:1em;
    }
  .account_description {
    position:relative;
    top:-15px; left:200px;
    height:15px;
    width:275px;
    }
  .account_balance {
    position:relative;
    top:-30px; left:475px;
    height:15px;
    width:75px;
    overflow:hidden;
    text-align:right;
    }
  .account_balance::before {
    content: "$";
    } 
  .edit_link {
    position:relative;
    top:-45px; left:650px;
    height:15px;
    width:50px;
    overflow:hidden;
    text-align:center;
    cursor:pointer;
    }
  .add_link {
    position:relative;
    top:-2em;
    float:right;
    cursor:pointer;
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