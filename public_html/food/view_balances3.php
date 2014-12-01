<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

// See documentation for the auto-fill menu at http://api.jqueryui.com/autocomplete/
$display = '
  <div class="ledger_input">
    <fieldset>
      <label class="text_label" for="load_spec">Account Spec.</label>
      <input type="text" size="9" class="secondary" name="load_spec" id="load_spec" autocomplete="off" />
    </fieldset>
    <fieldset>
      <label class="text_label" for="load_target">Type to select an account...</label>
      <input type="text" size="50" name="load_target" id="load_target" autocomplete="off" />
    </fieldset>
    <fieldset>
      <label class="text_label" for="delivery_id">Delivery No.</label>
      <input type="text" size="5" name="delivery_id" id="delivery_id" autocomplete="off" />
    </fieldset>
    <fieldset>
      <label class="text_label" for="delivery_date">Select a delivery cycle</label>
      <input type="text" size="50" name="delivery_date" id="delivery_date" autocomplete="off" />
    </fieldset>
    <input type="button" class="button" name="Refresh" id="refresh_button" value="Refresh" onclick="get_ledger_head(); get_ledger_body ()">
  </div>
    <table class="clear hid">
      <tr>
        <th></td>
        <th colspan="2">Group&nbsp;with<br>product&nbsp;&nbsp;/&nbsp;&nbsp;order </th>
        <th></td>
        <th colspan="2">Group&nbsp;with<br>product&nbsp;&nbsp;/&nbsp;&nbsp;order </th>
        <th></td>
        <th colspan="2">Group&nbsp;with<br>product&nbsp;&nbsp;/&nbsp;&nbsp;order </th>
        <td rowspan="3">
        </td>
      </tr>
      <tr>
        <td class="for">customer fees</td>
        <td><input type="radio" name="group_customer_fee_with" value="pvid"></td>
        <td><input type="radio" name="group_customer_fee_with" value="delivery_id" checked></td>
        <td class="for">random weight products</td>
        <td><input type="radio" name="group_weight_cost_with" value="pvid" checked></td>
        <td><input type="radio" name="group_weight_cost_with" value="delivery_id"></td>
        <td class="for">extra charges</td>
        <td><input type="radio" name="group_extra_charge_with" value="pvid" checked></td>
        <td><input type="radio" name="group_extra_charge_with" value="delivery_id"></td>
      </tr>
      <tr>
        <td class="for">producer fees</td>
        <td><input type="radio" name="group_producer_fee_with" value="pvid"></td>
        <td><input type="radio" name="group_producer_fee_with" value="delivery_id" checked></td>
        <td class="for">regular products</td>
        <td><input type="radio" name="group_quantity_cost_with" value="pvid" checked></td>
        <td><input type="radio" name="group_quantity_cost_with" value="delivery_id"></td>
        <td class="for">taxes</td>
        <td><input type="radio" name="group_taxes_with" value="pvid"></td>
        <td><input type="radio" name="group_taxes_with" value="delivery_id" checked></td>
      </tr>
    </table>


    <div id="main_content" class="clear">
      <div id="content_area">
        <div id="working_area">
        </div>
        <div id="ledger_container">
          <table id="ledger" class="ledger">
            <thead id="ledger_head">
            </thead>
            <tbody id="ledger_body">
            </tbody>
          </table>
        </div>
      </div>
      <br />
    </div>';

//     <tr id="pre_insertion_point">
//       <td colspan="9"></td>
//     </tr>
//     <tr id="post_insertion_point">
//       <td colspan="9"></td>
//     </tr>

$page_specific_javascript = '
<script type="text/javascript" src="'.PATH.'ajax/jquery.autocomplete.js"></script>
<script type="text/javascript">

// Set some initial variables for autocompletion
var lt_options, lt, ahs, aht, dd;
lt_options = {
  serviceUrl:"'.PATH.'ajax/get_account_hint.php'.'",
  minChars:2,
  maxHeight:400,
  width:400,
  zIndex: 9999,
  deferRequestBy: 300,
  params: { action:"get_account_hint"},
  onSelect: function(value, data){
    var parts = value.split(" ");
    document.getElementById("load_spec").value=parts.shift(); // could just use "data" but needs to be unshifted anyway
    document.getElementById("load_target").value=parts.join(" ");
    $("#working_area").removeClass("open"); 
    get_ledger_head();
    get_ledger_body ();
    } // callback function
  };
dd_options = {
  serviceUrl:"'.PATH.'ajax/get_account_hint.php'.'",
  minChars:2,
  maxHeight:400,
  width:400,
  zIndex: 9999,
  deferRequestBy: 300,
  params: { action:"get_delivery_date"},
  onSelect: function(value, data){
    var parts = value.split(": "); // split apart from e.g. "#82 delivery: 15 January 2012"
    document.getElementById("delivery_id").value=data; // the delivery_id is already in the data variable
    // document.getElementById("delivery_date").value=parts[1]; // This is the date-part from above
    document.getElementById("delivery_date").value=value; // This is whole date-part
    $("#working_area").removeClass("open"); 
    get_ledger_head();
    get_ledger_body ();
    } // callback function
  };
ahs_options = {
  serviceUrl:"'.PATH.'ajax/get_account_hint.php'.'",
  minChars:2,
  maxHeight:400,
  width:400,
  zIndex: 9999,
  deferRequestBy: 300,
  params: { action:"get_account_hint"},
  onSelect: function(value, data){
    var parts = value.split(" ");
    document.getElementById("edit_source_spec").value=parts.shift(); // could just use "data" but needs to be unshifted anyway
    document.getElementById("ad_hoc_source").value=parts.join(" ");
    } // callback function
  };
aht_options = {
  serviceUrl:"'.PATH.'ajax/get_account_hint.php'.'",
  minChars:2,
  maxHeight:400,
  width:400,
  zIndex: 9999,
  deferRequestBy: 300,
  params: { action:"get_account_hint"},
  onSelect: function(value, data){
    var parts = value.split(" ");
    document.getElementById("edit_target_spec").value=parts.shift(); // could just use "data" but needs to be unshifted anyway
    document.getElementById("ad_hoc_target").value=parts.join(" ");
    } // callback function
  };

// JQuery function to handle the autocompletion interface. Information on this autocomplete script
// can be found at: http://www.devbridge.com/projects/autocomplete/jquery/
function load_autocompletion () {
  jQuery(function(){
    lt_options;
    ahs_options;
    aht_options;
    lt = $("#load_target").autocomplete
      (lt_options);
    dd = $("#delivery_date").autocomplete
      (dd_options);
    ahs = $("#ad_hoc_source").autocomplete
      (ahs_options);
    aht = $("#ad_hoc_target").autocomplete
      (aht_options);
    });
  }

// Get ledger header. Does not really need to be an ajax call, but using this will allow
// handling header and table information in the same get_ledger_info.php file
function get_ledger_head () {
  var account_spec = document.getElementById("load_target").value;
  $.post("'.PATH.'ajax/get_ledger_info.php'.'", {
    action:"get_ledger_head",
    account_spec:account_spec,
    },
  function(ledger_head) {
    document.getElementById("ledger_head").innerHTML = ledger_head;
    });
  }

// Get ledger information for the requested account (based on the form values)
function get_ledger_body () {
  // first set the food throbber to run...
  document.getElementById("ledger_body").innerHTML = "<img src=\"'.DIR_GRAPHICS.'food_throbber.gif\" class=\"throbber\">";
  var account_spec = document.getElementById("load_spec").value;
  var delivery_id = document.getElementById("delivery_id").value;
//   if (! delivery_id || ! account_spec) {
//     alert ("Please select both an accound and a delivery cycle");
//     document.getElementById("ledger_body").innerHTML = "";
//     return 0;
//     }
  var group_customer_fee = "";
  var group_producer_fee = "";
  var group_customer_fee_with = $("input[name=\'group_customer_fee_with\']:checked").val();
  var group_producer_fee_with = $("input[name=\'group_producer_fee_with\']:checked").val();
  var group_weight_cost_with = $("input[name=\'group_weight_cost_with\']:checked").val();
  var group_quantity_cost_with = $("input[name=\'group_quantity_cost_with\']:checked").val();
  var group_extra_charge_with = $("input[name=\'group_extra_charge_with\']:checked").val();
  var group_taxes_with = $("input[name=\'group_taxes_with\']:checked").val();
  // This is used for the "Reload" button
//  if (account_spec == "null") { account_spec = document.getElementById("load_spec").value; }
  $.post("'.PATH.'ajax/get_ledger_info.php'.'", {
    action:"get_ledger_body",
    account_spec:account_spec,
    group_customer_fee_with:group_customer_fee_with,
    group_producer_fee_with:group_producer_fee_with,
    group_weight_cost_with:group_weight_cost_with,
    group_quantity_cost_with:group_quantity_cost_with,
    group_extra_charge_with:group_extra_charge_with,
    group_taxes_with:group_taxes_with,
    delivery_id:delivery_id
    },
  function(ledger_body) {
    document.getElementById("ledger_body").innerHTML = ledger_body;
    });
  }

// Expand or contract the detail listings by adding or removing the "hidden" class
function show_hide_detail (target, operation) {
  var target;
  var operation;
  if (operation == "show") {
    $("."+target).removeClass("hid");
    $("."+target).addClass("detail");
    return("hide");
    }
  else {
    $("."+target).addClass("hid");
    return("show");
    }
  }

// Get information from a clicked transaction row and call ajax to process it for editing.
function row_click(transaction_id,bpid) {
  $.post("'.PATH.'ajax/adjustment_interface.php'.'", {
    action:"get_adjustment_dialog",
    transaction_id:transaction_id,
    bpid:bpid
    },
  function(adjustment_dialog) {
    document.getElementById("working_area").innerHTML = adjustment_dialog;
    $("#working_area").addClass("open"); 
    // Reload jQuery functions to re-enable the lookup hints
    load_autocompletion ();
    });
  }

// Update the basket information
function update_basket_info(bpid) {
  var quantity = document.getElementById("edit_quantity").value;
  var out_of_stock = document.getElementById("edit_out_of_stock").value;
  var weight = document.getElementById("edit_total_weight").value;
  var message = document.getElementById("edit_basket_message").value;
  var product_id = document.getElementById("edit_product_id").value;
  var product_version = document.getElementById("edit_product_version").value;
  var delivery_id = document.getElementById("edit_delivery_id").value;
  var member_id = document.getElementById("edit_member_id").value;
  $.post("'.PATH.'ajax/adjustment_interface.php'.'", {
    action:"update_basket_info",
    quantity:quantity,
    weight:weight,
    out_of_stock:out_of_stock,
    message:message,
    product_id:product_id,
    product_version:product_version,
    delivery_id:delivery_id,
    member_id:member_id
    },
  function(update_response) {
    document.getElementById("working_area").innerHTML = update_response;
    });
  }

// Update the ledger information
function add_modify_ledger_info(transaction_id,add_modify) {
  var transaction_group_id = document.getElementById("edit_transaction_group_id").value;
  var amount = document.getElementById("edit_amount").value;
  var source_spec = document.getElementById("edit_source_spec").value.split(":");
  var source_type = source_spec[0];
  var source_key = source_spec[1];
  var target_spec = document.getElementById("edit_target_spec").value.split(":");
  var target_type = target_spec[0];
  var target_key = target_spec[1];
  var text_key = document.getElementById("edit_text_key").value;
  var message = document.getElementById("edit_ledger_message").value;
  $.post("'.PATH.'ajax/adjustment_interface.php'.'", {
    action:"update_ledger_info",
    transaction_id:transaction_id,
    transaction_group_id:transaction_group_id,
    add_modify:add_modify,
    amount:amount,
    source_type:source_type,
    source_key:source_key,
    target_type:target_type,
    target_key:target_key,
    text_key:text_key,
    message:message
    },
  function(update_response) {
    document.getElementById("working_area").innerHTML = update_response;
    });
  }

// Close (hide) the editor
function close_editor () {
  $("#working_area").removeClass("open"); 
  }

load_autocompletion();

</script>';

$page_specific_css = '
  <style type="text/css">
  /* #load_target {
    width:40%;
    } */
  .autocomplete-w1 {
     background:url('.DIR_GRAPHICS.'shadow.png) no-repeat bottom right;
     position:absolute;
     top:0px;
     left:0px;
     margin:6px 0 0 6px;
     /* IE6 fix: */ _background:none;
     _margin:1px 0 0 0;
     }
  .autocomplete {
     border:1px solid #999;
     background:#fff;
     cursor:default;
     text-align:left;
     max-height:350px;
     overflow:auto;
     margin:-6px 6px 6px -6px;
     /* IE6 specific: */ _height:350px;
      _margin:0;
     _overflow-x:hidden;
     }
  .autocomplete .selected {
     background:#f0f0f0;
     }
  .autocomplete div {
     padding:2px 5px;
     white-space:nowrap;
     overflow:hidden;
     }
  .autocomplete strong {
     font-weight:normal;
     color:#007;
    text-decoration:underline;
     }
  table.ledger {
    width:100%;
    border-spacing:0;
    border-collapse:collapse;
    }
  .ledger tr td {
    background-color:#eed;
    margin:0;
    font-size:80%;
    padding:1px 5px;
    }
  .ledger tr.detail td {
    border-bottom:1px solid #ccb
    }
  .ledger tr.detail td.amount {
    border-right:1px solid #ccb
    }
  .ledger tr.summary_row td {
    border-bottom:1px solid #997
    }
  .ledger tr.extra_row td {
    background-color:#bb9;
    border-bottom:1px solid #997
    }
  .ledger tr.summary_delivery_id td,
  .ledger tr.singleton_delivery_id td {
    background-color:#ddb;
    border-bottom:1px solid #997
    }
  .ledger_header {
    background-color:#540;
    color:#ffe;
    text-align:left;
    }
  .hid {
    display:none
    }
  .more_less {
    font-size:90%;
    color:#630;
    cursor:pointer;
    padding:0;
    margin:0;
    }
  .scope, .timestamp, .text_key, .more_less {
    text-align:center;
    }
  .amount, .balance {
    text-align:right;
    }
  tr.row_sep td {
    background-color:#a70;
    max-height:1px;
    border:0;
    }
  tr.order_summary td {
    background-color:#ddb;
    }
  td.for {
    text-align:right;
    padding-left:2em;
    }
  #editor {
    border: 3px solid #360;
    width:100%;
    z-index:200;
    }
  #main_content {
    margin:0px auto;
    }
  #ledger_container {
    margin:0 auto;
    max-height:450px;
    overflow-y:auto;
    }
  #content_area {
    width:95%;
    margin:0 auto;
    } 
  #working_area {
    background-color:#fff;
    width:89.7%;
    max-height:0;
    overflow:hidden;
    position:absolute;
    -moz-transition: max-height 1s ease;
    -ms-transition: max-height 1s ease;  
    -o-transition: max-height 1s ease;  
    transition: max-height 1s ease;  
    -webkit-transition: max-height 1s ease;  
    }
  #working_area.open {
    width:89.7%;
    margin:0 auto;
    max-height:400px;
    overflow:auto;
    }
  fieldset {
    width:0;
    float:left;
    border:0;
    margin:0;
    padding:0 3px;
    }
  input[type=text] {
    }
  label.text_label {
    display:block;
    float:left;
    font-size:0.8em;
    }
  label {
    white-space:nowrap;
    }
  div.label_holder {
    width:0;
    position:relative;
    bottom:1em;
    font-size:0.8em;
    margin-left:1em;
    float:left;
    margin-top:1em;
    }
  .clear {
    clear:left;
    }
  .throbber {
    width:50px;
    height:50px;
    margin: 10px 200px;
    }
  .secondary {
    text-align:right;
    background-color:transparent;
    padding-right:0.5em;
    font-family:verdana;
    }
  .editor {
    width:100%;
    background-color:#ded;
    border:1px solid #750;
    }
  .editor td {
    padding:0;
    margin:0;
    }
  .control {
    cursor:pointer;
    }
  input[type=button] {
    width:120px;
    margin:5px 20px 0;
    text-align:center;
    color:#ffe;
    background-color:#786;
    padding:0.3em 1em 0.1em;
    cursor:pointer;
    border-top:2px solid #ddd;
    border-left:2px solid #ddd;
    border-bottom:2px solid #666;
    border-right:2px solid #666;
    font-family:verdana;
    vertical-align:middle;
    }
  input[type=button]:hover {
    background-color:#675;
    color:#fff;
    border-right:2px solid #444;
    border-bottom:2px solid #444;
    }
  input[type=button]:active {
    background-color:#786;
    border-right:2px solid #ddd;
    border-bottom:2px solid #ddd;
    }
  #customer_message {
    width:300px;
    height:40px;
    }

  #ad_hoc_source,
  #ad_hoc_target,
  #load_target,
  #delivery_date {
    width:200px;
    }
  #edit_source_spec,
  #edit_target_spec,
  #load_spec {
    width:100px;
    }
  #edit_ledger_message {
    width:300px;
    }
  .pad_left {
    padding-left:2em;
    }
  .close_icon {
    display:block;
    float:right;
    margin:0 2px;
    padding:0 2px;
    color:#fff;
    border:1px solid #fff;
    cursor:pointer;
    }
  textarea {
    width:300px;
    height:50px;
  </style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Member Balances Lookup</span>';
$page_title = 'Reports: Member Balances Lookup';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
?>