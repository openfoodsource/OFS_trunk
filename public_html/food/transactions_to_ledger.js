var c_arrElements;
var p_arrElements;
var i;
var j;

function getElementsByClass (needle) {
  var my_array = document.getElementsByTagName("li");
  var retvalue = new Array();
  var i;
  var j;
  for (i = 0, j = 0; i < my_array.length; i++) {
    var c = " " + my_array[i].className + " ";
    if (c.indexOf(" " + needle + " ") != -1)
      retvalue[j++] = my_array[i];
    }
  return retvalue;
  }

// Get the transactions types list for the left column
function get_transactions_types (ttype_parent) {
  var li_begin = '<ul>';
  var li_end = '</ul>';
  // Send the ajax request
  $.post("transactions_to_ledger.php", {
    ajax:"yes",
    process:"get_transaction_types",
    ttype_parent:ttype_parent
    },
  function(transaction_types) {
    document.getElementById("trans_list").innerHTML = li_begin + transaction_types + li_end;
    });
  }

// Make the list of transactions and display it
function get_transaction_list (ttype_id) {
  // Send the ajax request
  $.post("transactions_to_ledger.php", {
    ajax:"yes",
    process:"get_transaction_list",
    ttype_id:ttype_id
    },
  function(transaction_list) {
    document.getElementById("transactions_box").innerHTML = transaction_list;
    });
  }

// A transaction has been selected from the tranactions_box, so get the transaction information
// and prepare to process it according to configuration specified in the ttypes_box.
function get_transaction_info(transaction_id) {
  var translation_config;
  var transaction_info
  // Send the ajax request
  $.post("transactions_to_ledger.php", {
    ajax:"yes",
    process:"get_transaction_info",
    transaction_id:transaction_id
    },
  function(transaction_info) {
    this_transaction = JSON.parse(transaction_info);
    // Clear any values that will not be automatically overwritten below
    $("input#source_type").val("");
    $("input#source_key").val("");
    $("input#target_type").val("");
    $("input#target_key").val("");
    $("input#amount").val("");

// Probably need to add other table linkage elements here

    $("input#posted_by").val("");
    $("input#timestamp").val("");
    $("input#batchno").val("");
    $("input#memo").val("");
    $("input#comments").val("");
    // We do not actually use this value, but it might be useful to see
    $("input#delivery_id").val(this_transaction.transaction_delivery_id);
    // Assign values for soruce_type and source_key
    if (document.getElementById("source_key:"+this_transaction.transaction_type).value == "[member_id]") {
      $("input#source_type").val("member");
      $("input#source_key").val(this_transaction.transaction_member_id);
      }
    else if(document.getElementById("source_key:"+this_transaction.transaction_type).value == "[producer_id]") {
      $("input#source_type").val("producer");
      $("input#source_key").val(this_transaction.transaction_producer_id);
      }
    else if(document.getElementById("source_internal:"+this_transaction.transaction_type).checked == true) {
      $("input#source_type").val("internal");
      $("input#source_key").val(document.getElementById("source_key:"+this_transaction.transaction_type).value);
      }
    else if(document.getElementById("source_tax:"+this_transaction.transaction_type).checked == true) {
      $("input#source_type").val("tax");
      $("input#source_key").val(document.getElementById("source_key:"+this_transaction.transaction_type).value);
      }
    else {
      stop_for_inquiry = "trouble with source parameters";
      }
    // Assign values for target_type and target_key
    if (document.getElementById("target_key:"+this_transaction.transaction_type).value == "[member_id]") {
      $("input#target_type").val("member");
      $("input#target_key").val(this_transaction.transaction_member_id);
      }
    else if(document.getElementById("target_key:"+this_transaction.transaction_type).value == "[producer_id]") {
      $("input#target_type").val("producer");
      $("input#target_key").val(this_transaction.transaction_producer_id);
      }
    else if(document.getElementById("target_internal:"+this_transaction.transaction_type).checked == true) {
      $("input#target_type").val("internal");
      $("input#target_key").val(document.getElementById("target_key:"+this_transaction.transaction_type).value);
      }
    else if(document.getElementById("target_tax:"+this_transaction.transaction_type).checked == true) {
      $("input#target_type").val("tax");
      $("input#target_key").val(document.getElementById("target_key:"+this_transaction.transaction_type).value);
      }
    else {
      stop_for_inquiry = "trouble with target parameters";
      }
    // Set the amount based on the old amount and the base_multiplier
    if (this_transaction.amount != 0) {
//      alert (document.getElementById("base_multiplier:"+this_transaction.transaction_type).value);
      $("input#amount").val(Math.round(document.getElementById("base_multiplier:"+this_transaction.transaction_type).value*this_transaction.transaction_amount * 100)/100);
      }
    else {
      stop_for_inquiry = "amount is zero";
      }


// Need to remove referenced_* fields and probalby need to add various *other* columns for other table linkages....


    // Set the referenced_table and referenced_key
    if (document.getElementById("referenced_members:"+this_transaction.transaction_type).checked == true &&
      this_transaction.transaction_member_id > 0) {
      $("input#referenced_table").val("members");
      $("input#referenced_key").val(this_transaction.transaction_member_id);
      }
    else if (document.getElementById("referenced_producers:"+this_transaction.transaction_type).checked == true &&
      this_transaction.transaction_producer_id > 0) {
      $("input#referenced_table").val("producers");
      $("input#referenced_key").val(this_transaction.transaction_producer_id);
      }
    else if (document.getElementById("referenced_baskets:"+this_transaction.transaction_type).checked == true &&
      this_transaction.transaction_basket_id > 0) {
      $("input#referenced_table").val("baskets");
      $("input#referenced_key").val(this_transaction.transaction_basket_id);
      }
    else {
      stop_for_inquiry = "could not identify a referenced_table and/or referenced_key";
      }



    // See if we have a valid transaction_user from the database
    if (this_transaction.user_member_id > 0) {
      $("input#posted_by").val(this_transaction.user_member_id);
      }
    // ... or possibly from the configuration (as a fallback option)
    else if (document.getElementById("user:"+this_transaction.transaction_type).value != "") {
      $("input#posted_by").val(document.getElementById("user:"+this_transaction.transaction_type).value );
      }
    else {
      stop_for_inquiry = "no useful value for posted_by";
      }
    // Set the text_key (first try for a configured value, otherwise use "adjustment"
    if (document.getElementById("text_key:"+this_transaction.transaction_type).value != "") {
      $("input#text_key").val(document.getElementById("text_key:"+this_transaction.transaction_type).value);
      }
    else {
      $("input#text_key").val("adjustment");
      }
    if (this_transaction.transaction_timestamp != "") {
      $("input#timestamp").val(this_transaction.transaction_timestamp);
      }
    else {
      stop_for_inquiry = "no timestamp";
      }
    // Assign either the actual batchno, memo, and comment text, or anything in the configuration setup
    if (this_transaction.transaction_batchno != 0) {
      $("input#batchno").val(this_transaction.transaction_batchno);
      }
    else if (document.getElementById("batchno:"+this_transaction.transaction_type).value != 0) {
      $("input#batchno").val(document.getElementById("batchno:"+this_transaction.transaction_type).value);
      }
    if (this_transaction.transaction_memo != "") {
      $("input#memo").val(this_transaction.transaction_memo);
      }
    else if (document.getElementById("memo:"+this_transaction.transaction_type).value != 0) {
      $("input#memo").val(document.getElementById("memo:"+this_transaction.transaction_type).value);
      }
    if (this_transaction.transaction_comments != "") {
      $("input#comments").val(this_transaction.transaction_comments);
      }
    else if (document.getElementById("comments:"+this_transaction.transaction_type).value != 0) {
      $("input#comments").val(document.getElementById("comments:"+this_transaction.transaction_type).value);
      }
    // All the data is loaded. If no problems, then go ahead and post to ledger
    if(stop_for_inquiry.length == 0) {
        stop_for_inquiry = process_ledger_data(transaction_id);
      }
    // Check if there were errors from 
    if(stop_for_inquiry.length > 0) {
      alert ("STOP FOR INQUIRY: "+stop_for_inquiry);
      }
    });
  }

// Take data from the ledger_data form and post it. Some of this data will be
// massaged/interpreted by php at the other end
function process_ledger_data (transaction_id) {
alert ("Beginning process_ledger_data routine");
  var delivery_id = document.getElementById("delivery_id").value;
  var source_type = document.getElementById("source_type").value;
  var source_key = document.getElementById("source_key").value;
  var target_type = document.getElementById("target_type").value;
  var target_key = document.getElementById("target_key").value;
  var amount = document.getElementById("amount").value;
  var text_key = document.getElementById("text_key").value;
  var posted_by = document.getElementById("posted_by").value;

// Probably need to add other table linkage elements here

  var timestamp = document.getElementById("timestamp").value;
  var batchno = document.getElementById("batchno").value;
  var memo = document.getElementById("memo").value;
  var comments = document.getElementById("comments").value;
  // Send the ajax request
  $.post("transactions_to_ledger.php", {
    ajax:"yes",
    process:"post_to_ledger",
    transaction_id:transaction_id,
    // Following are the data
    delivery_id:delivery_id,
    source_type:source_type,
    source_key:source_key,
    target_type:target_type,
    target_key:target_key,
    amount:amount,
    text_key:text_key,

// Probably need to add other table linkage elements here

    posted_by:posted_by,
    timestamp:timestamp,
    batchno:batchno,
    memo:memo,
    comments:comments
    },
  function(post_response) {
alert ("Finishing process_ledger_data routine");
    // The returned value should look like "target_transaction_id:[transaction_id]"
    if (post_response.substr(0,22) != "target_transaction_id:") {
      return (post_response);
      }
    });
  }
