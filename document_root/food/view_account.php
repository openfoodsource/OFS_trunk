<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

$account_type = isset($_GET['account_type']) ? $_GET['account_type'] : '';
$account_key = isset($_GET['account_key']) ? $_GET['account_key'] : '';
$account_name = isset($_GET['account_name']) ? $_GET['account_name'] : '';
$sort_option = isset($_GET['sort_option']) ? $_GET['sort_option'] : 'effective_datetime';

// See documentation for the auto-fill menu at http://api.jqueryui.com/autocomplete/
$display = '
<fieldset class="controls">
  <div class="grouping_block select_account">
    <div class="info_block account_type">
      <label for="account_type">Type: </label>
      <select id="account_type" name="account_type" onchange="clear_form();">
        <option value="member"'.($account_type == "member" ? ' selected' : '').'>member</option>
        <option value="producer"'.($account_type == "producer" ? ' selected' : '').'>producer</option>
        <option value="internal"'.($account_type == "internal" || $account_type == '' ? ' selected' : '').'>internal</option>
        <option value="tax"'.($account_type == "tax" ? ' selected' : '').'>tax</option>
      </select>
    </div>
    <div class="info_block account_name">
      <label for="account_name">Account: </label>
      <input type="text" id="account_name" onFocus="this.select();" value="'.$account_name.'" placeholder="start typing to find account...">
    </div>
    <div class="info_block account_name">
      <label for="sort_option">Sort by</label>
      <select id="sort_option" name="sort_option" onchange="get_account_info(\'data_page\', true);">
        <option value="ledger_order"'.($sort_option == "ledger_order" ? ' selected' : '').'>Ledger Order</option>
        <option value="ledger_order_voids"'.($sort_option == "ledger_order_voids" ? ' selected' : '').'>Ledger Order (with voided)</option>
        <option value="effective_datetime"'.($sort_option == "effective_datetime" ? ' selected' : '').'>Effective Date/Time</option>
      </select>
    </div>
    <input type="hidden" id="account_key" value="'.$account_key.'">
  </div>
  <div id="pager">
    <label for="data_page"></label>
    <span id="decrement_data_page" class="decrement" onclick="decrement(\'data_page\');" style="display:none;">&#9664;</span>
    <span id="data_page" onkeyup="constrain(\'data_page\');debounce_pager(\'data_page\');" onchange="constrain(\'data_page\');debounce_pager(\'data_page\');" onfocus="document.execCommand(\'selectAll\',false,null)" contenteditable>1</span>
    <div id="maximum_data_page">/ 1</div>
    <span id="increment_data_page" class="increment" onclick="increment(\'data_page\');" style="display:none;">&#9654;</span>
  </div>
</fieldset>

<div id="main_content" class="clear">
  <div id="content_area">
    <div id="working_area">
    </div>
    <div id="ledger_container">
    </div>
  </div>
  <br />
</div>';

// If we have been called with an account to display, then kick off the request
// as soon as the page loads
if ($account_type != '' && $account_key != '')
  $auto_load_request = '
    jQuery( document ).ready(function() {
      // Handler for .ready() called.
      actual["data_page"] = 0; // needed to force loading
      get_account_info(\'data_page\', false);
      });';

$page_specific_scripts['adjust_ledger'] = array (
  'name'=>'adjust_ledger',
  'src'=>BASE_URL.PATH.'adjust_ledger.js',
  'dependencies'=>array('jquery'),
  'version'=>'2.1.1',
  'location'=>false
  );
$page_specific_javascript = '
  '.$auto_load_request.'
  // Set default values
  var minimum = [];
  var maximum = [];
  var actual = [];
  minimum["data_page"] = 1; // Page 1 is always the lowest possible page
  actual["data_page"] = 1; // Always begin with page 1
  maximum["data_page"] = 1; // Default value until we have additional data

  function clear_form() {
    document.getElementById("account_name").value = "";
    document.getElementById("data_page").innerHTML = 1;
    actual["data_page"] = 1;
    maximum["data_page"] = 1;
    document.getElementById("ledger_container").innerHTML = "";
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

  // Pager functions for advancing and going back on pages
  function increment (target) {
    jQuery("#"+target).html(jQuery("#"+target).html()*1+1);
    constrain (target);
    debounce_pager(target);
    }
  function decrement (target) {
    jQuery("#"+target).html(jQuery("#"+target).html()*1-1);
    constrain (target);
    debounce_pager(target);
    }
  // Constrain value to within min/max limits
  function constrain (target) {
    if (jQuery("#"+target).html() > maximum[target]) {
      jQuery("#"+target).html(maximum[target]);
      jQuery("#increment_"+target).hide();
      }
    else {
      jQuery("#increment_"+target).show();
      }
    if (jQuery("#"+target).html() < minimum[target]) {
      jQuery("#"+target).html(minimum[target]);
      jQuery("#decrement_"+target).hide();
      }
    else {
      jQuery("#decrement_"+target).show();
      }
    // Set the maximum_data_page text
    jQuery("#maximum_data_page").html(" / "+maximum["data_page"]);
    }
  var debounce_pager = debounce(function(target) {
    get_account_info(target, false);
    }, 1000);
  function get_account_info(target, force_update) {
    // Clear the current #ledger_container content
    // jQuery("#ledger_container").html("");
    if (document.getElementById(target).innerHTML != actual[target] || force_update == true) {
      actual[target] = document.getElementById(target).innerHTML;
      // Start the spinner (change color of the "data_page" field)
      jQuery("#data_page").addClass("spinning")
      jQuery.ajax({
        type: "POST",
        url: "'.PATH.'ajax/display_account_info.php",
        cache: false,
        data: {
          account_key: document.getElementById("account_key").value,
          sort_option: document.getElementById("sort_option").value,
          account_type: document.getElementById("account_type").value,
          data_page: document.getElementById("data_page").innerHTML
          }
        })
      .done(function(json_account_info) {
        account_info = JSON.parse(json_account_info);
        maximum["data_page"] = account_info.maximum_data_page;
        constrain (\'data_page\');
        // Stop the spinner (restore color of the "data_page" field)
        jQuery("#data_page").removeClass("spinning")
        jQuery("#ledger_container").html(account_info.markup);
        });
      }
    }

  // Functions to highlight links between transactions and their replacements
  function highlight_replacement(target) {
    jQuery("#id-"+target).addClass("replacement");
    // document.getElementById("id-"+target).style.color="#880000";
    }
  function restore_replacement(target) {
    jQuery("#id-"+target).removeClass("replacement");
    // document.getElementById("id-"+target).style.color="#000000";
    }

  // Set the width of the autocomplete dropdown to match the input area
  jQuery.ui.autocomplete.prototype._resizeMenu = function () {
    var ul = this.menu.element;
    ul.outerWidth(this.element.outerWidth());
    }

  // Set up autocomplete for finding accounts
  jQuery(function() {
    function lookup(account_key) {
      jQuery.ajax({
        type: "POST",
        url: "'.PATH.'ajax/display_account_info.php",
        cache: false,
        data: {
          account_key: account_key,
          account_type: document.getElementById("account_type").value,
          data_page: document.getElementById("data_page").innerHTML
          }
        })
      .done(function(json_account_info) {
        account_info = JSON.parse(json_account_info);
        maximum["data_page"] = account_info.maximum_data_page;
        constrain (\'data_page\');
        jQuery("#ledger_container").html(account_info.markup);
        });
      }
    jQuery("#account_name").autocomplete({
      source: function( request, response ) {
        jQuery.ajax({
          url: "'.PATH.'ajax/autocomplete_account_list.php",
          dataType: "jsonp",
          data: {
            query: request.term,
            account_type: document.getElementById("account_type").value,
            action:"get_hint"
            },
          success: function( data ) {
            response( jQuery.map(data, function(item) {
              return {
                // Must return data for either "value" or "label"
                // These are switched so the "display" version is put into the select field
                key: item.value,
                label: item.label
                }
              }));
            }
          });
        },
      minLength: 3,
      select: function( event, ui ) {
        // Clear the current #ledger_container content
        jQuery("#ledger_container").html("");
        // Restore to data_page=1
        jQuery("#data_page").html("1");
        document.getElementById("account_key").value = ui.item.key;
        lookup( ui.item.key);
        },
      open: function() {
        jQuery( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
        },
      close: function() {
        jQuery( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
        }
      })
    });';

$page_specific_css = '
  /* BEGIN AUTOCOMPLETION STYLES */

  .ui-state-focus {
    font-weight:normal;
    }
  .ui-helper-hidden-accessible {
    display:none;
    }
  ul.ui-autocomplete {
    border:1px solid #aaa;
    border-top:0;
    box-shadow:4px 4px 6px #bbb;
    }
  li.ui-menu-item {
    list-style:none;
    margin-left:-40px;
    padding:2px 5px;
    background-color:#fff;
    font-size:80%;
    }
  li.ui-menu-item:hover {
    color:#024;
    background-color:#def;
    }
  fieldset.controls {
    text-align:center;
    }
  .ui-autocomplete-loading {
    background: white url("'.DIR_GRAPHICS.'ui-anim_basic_16x16.gif") right center no-repeat;
    }

  /* BEGIN BASIC FORM STYLES */

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

  /* BEGIN LEDGER DATA STYLES */

  .data_row:nth-child(even) {
    background: #ffd;
    }
  .data_row:nth-child(odd) {
    background: #eef;
    }
  .replaced {
    color:#888;
    text-decoration:line-through;
    }
  .data_row {
    width:99%;
    height:18px;
    font-size:12px;
    cursor:default;
    }
  .data_row:hover {
    background-color:rgba(0, 0, 0, .2);
    }
  .replacement {
    color:#800;
    background-color:#fdd !important;
    }
  .source_info {
    position:relative;
    top:0; left:135px;
    height:15px;
    width:280px;
    overflow:hidden;
    text-decoration:inherit;
    }
  .source_type {
    display:inline-block;
    position:relative;
    top:-4px;
    text-decoration:inherit;
    }
  .source_type::before {
    content: " (";
    } 
  .source_key::after {
    content: ")";
    } 
  .source_key {
    display:inline-block;
    position:relative;
    top:-4px;
    text-decoration:inherit;
    }
  .source_name {
    display:inline-block;
    max-width:200px;
    overflow:hidden;
    white-space:nowrap;
    text-decoration:inherit;
    }
  .target_info {
    position:relative;
    top:0px; left:135px;
    height:15px;
    width:280px;
    overflow:hidden;
    display:none;
    text-decoration:inherit;
    }
  .target_type {
    display:inline-block;
    position:relative;
    top:-4px;
    text-decoration:inherit;
    }
  .target_key {
    display:inline-block;
    display:none;
    position:relative;
    top:-4px;
    text-decoration:inherit;
    }
  .target_name {
    display:inline-block;
    max-width:200px;
    overflow:hidden;
    white-space:nowrap;
    text-decoration:inherit;
    }
  .amount {
    position:relative;
    top:-60px; left:575px;
    height:15px;
    width:75px;
    overflow:hidden;
    text-align:right;
    }
  .amount::before {
    content: "$";
    } 
  .running_total {
    position:relative;
    top:-75px; left:650px;
    height:15px;
    width:75px;
    overflow:hidden;
    text-align:right;
    }
  .running_total::before {
    content: "$";
    } 
  .text_key {
    position:relative;
    top:-15px; left:425px;
    height:15px;
    width:100px;
    }
  .text_key::before {
    content: "[";
    } 
  .text_key::after {
    content: "]";
    } 
  .effective_datetime {
    position:relative;
    top:-30px; right:0;
    height:15px;
    width:135px;
    }
  .posted_by {
    display:none;
    }
  .replaced_by {
    display:none;
    }
  .replaced_datetime {
    display:none;
    }
  .timestamp {
    display:none;
    }
  .order_info {
    position:relative;
    top:-45px; left:525px;
    height:16px;
    text-align:center;
    overflow:hidden;
    width:50px;
    }
  .order_info:hover {
    overflow:visible;
    color:#fff;
    background-color:#444;
    z-index:100;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    }
  .order_info::before {
    content: "(info)";
    } 
  .basket_id {
    display:none;
    }
  .bpid {
    display:none;
    }
  .site_info {
    position:relative;
    /* top:-45px; left:425px; */
    top:-2px; left:-150px;
    height:20px;
    width:50px;
    padding:2px;
    color:#fff;
    background-color:#444;
    border-top-left-radius: 0.5rem;
    }
  .site_id {
    display:none;
    }
  .site_name {
    display:inline;
    }
  .delivery_info {
    position:relative;
    /* top:-60px; left:475px; */
    top:-22px; left:-100px;
    height:20px;
    width:150px;
    padding:2px;
    color:#fff;
    background-color:#444;
    }
  .delivery_id {
    display:none;
    }
  .delivery_date {
    display:inline;
    }
  .delivery_date::before {
    content: "Cycle: ";
    } 
  .product_info {
    position:relative;
    /* top:-75px; left:575px; */
    top:-22px; left:-150px;
    height:20px;
    width:200px;
    padding:2px;
    color:#fff;
    background-color:#444;
    border-bottom-right-radius: 0.5rem;
    border-bottom-left-radius: 0.5rem;
    }
  .pvid {
    display:inline;
    }
  .product_name {
    display:inline;
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
    }';

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
