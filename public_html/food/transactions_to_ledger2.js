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
  $.post("transactions_to_ledger2.php", {
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
  $.post("transactions_to_ledger2.php", {
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
  var transaction_info;
  var stop_for_inquiry = "";
  // Send the ajax request
  $.post("transactions_to_ledger2.php", {
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
    $("input#amount").val(Math.round(document.getElementById("base_multiplier:"+this_transaction.transaction_type).value * this_transaction.transaction_amount * 100) / 100);
    // Set the transaction_id
    $("input#transaction_id").val(this_transaction.transaction_id);
    if (document.getElementById("transaction_id").value == "") {
      stop_for_inquiry = "no value for transaction_id";
      }
    // Set the basket_id
    $("input#basket_id").val(this_transaction.basket_id);
    if (document.getElementById("basket_id").value == "") {
      stop_for_inquiry = "no value for basket_id";
      }
    // Set the site_id
    $("input#site_id").val(this_transaction.site_id);
    if (document.getElementById("site_id").value == "") {
      stop_for_inquiry = "no value for site_id";
      }
    // See if we have a valid transaction_user from the database
    if (this_transaction.user_member_id >= 0) {
      $("input#posted_by").val(this_transaction.user_member_id);
      $("input#posted_by_text").val(this_transaction.user_member_text);
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

    // Set the text_key (first try for a configured value, otherwise use "adjustment"
    if (document.getElementById("transaction_group_id:"+this_transaction.transaction_type).value != "") {
      $("input#transaction_group_id").val(document.getElementById("transaction_group_id:"+this_transaction.transaction_type).value);
      }
    else {
      $("input#transaction_group_id").val("legacy-transaction");
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
    // If target_type = "internal" and target_key is not set, then use text_key as a directive to the ledger
    // to create a new internal account type based on that text key
    if (document.getElementById("target_type").value == "internal" &&
      document.getElementById("target_key").value == "") {
      document.getElementById("target_key").value = document.getElementById("text_key").value; };
    // Same with the source_type/key
    if (document.getElementById("source_type").value == "internal" &&
      document.getElementById("source_key").value == "") {
      document.getElementById("source_key").value = document.getElementById("text_key").value; };

    // Check if there were errors
    if(stop_for_inquiry) {
      alert ("STOPPING: "+stop_for_inquiry);
      }
    });
  }

// Take data from the ledger_data form and post it. Some of this data will be
// massaged/interpreted by php at the other end
function process_ledger_data (requested_action) {
  var transaction_id = document.getElementById("transaction_id").value;
  var transaction_group_id = document.getElementById("transaction_group_id").value;
  var source_type = document.getElementById("source_type").value;
  var source_key = document.getElementById("source_key").value;
  var target_type = document.getElementById("target_type").value;
  var target_key = document.getElementById("target_key").value;
  var amount = document.getElementById("amount").value;
  var delivery_id = document.getElementById("delivery_id").value;
  var site_id = document.getElementById("site_id").value;
  var basket_id = document.getElementById("basket_id").value;
  var bpid = document.getElementById("bpid").value;
  var text_key = document.getElementById("text_key").value;
  var timestamp = document.getElementById("timestamp").value;
  var posted_by = document.getElementById("posted_by").value;
  var batchno = document.getElementById("batchno").value;
  var memo = document.getElementById("memo").value;
  var comments = document.getElementById("comments").value;
  // Send the ajax request
  $.post("transactions_to_ledger2.php", {
    ajax:"yes",
    process:"post_to_ledger",
    requested_action:requested_action,
    transaction_id:transaction_id,
    transaction_group_id:transaction_group_id,
    source_type:source_type,
    source_key:source_key,
    target_type:target_type,
    target_key:target_key,
    amount:amount,
    delivery_id:delivery_id,
    site_id:site_id,
    basket_id:basket_id,
    bpid:bpid,
    text_key:text_key,
    timestamp:timestamp,
    posted_by:posted_by,
    batchno:batchno,
    memo:memo,
    comments:comments
    },
  function(post_response) {
    // Received marked:[transaction_id]
    if (post_response.substr(0,7) == "marked:") {
      var marked_transaction_id = post_response.substr(7)
      // Get the next transaction for this class while the current row is still active
      var next_id = getNextIdByClassName("trans_incomplete", marked_transaction_id)
      // Unmark the current row locally
      // STRANGE... jquery addClass/removeClass does not work, so do it with javascript:
      var current_class = " "+document.getElementById("trans_id:"+marked_transaction_id).className+" ";
      if (current_class.indexOf(" trans_incomplete ") != -1) { 
        document.getElementById("trans_id:"+marked_transaction_id).className = 'trans_detail trans_complete'; 
        } 
      }
    // Received skipped:[transaction_id]
    else if (post_response.substr(0,8) == "skipped:") {
      var skipped_transaction_id = post_response.substr(8)
      // Get the next transaction for this class while the current row is still active
      var next_id = getNextIdByClassName("trans_incomplete", skipped_transaction_id)
      }
    // Received posted:[transaction_id]
    else if (post_response.substr(0,7) == "posted:") {
      var posted_transaction_id = post_response.substr(7)
      // Get the next transaction for this class while the current row is still active
      var next_id = getNextIdByClassName("trans_incomplete", posted_transaction_id)
      // Unmark the current row locally
      // STRANGE... jquery addClass/removeClass does not work, so do it with javascript:
      var current_class = " "+document.getElementById("trans_id:"+posted_transaction_id).className+" ";
      if (current_class.indexOf(" trans_incomplete ") != -1) { 
        document.getElementById("trans_id:"+posted_transaction_id).className = 'trans_detail trans_complete'; 
        } 
      }
    // Now queue-up the next id
    get_transaction_info(next_id);
    // And if not paused, continue with the processing
    if (document.getElementById("pause").checked != true) {
      process_ledger_data ("process_and_next");
      }
    });
  }


// Function to get the next list-item element with a particular class
function getNextIdByClassName(target_class, current_id) {
  var li_elements = document.getElementsByTagName("li");
  var found_current = 0;
  for (var i = 0; i < li_elements.length; i++) {
    var current_class = " "+li_elements[i].className+" ";
    if (current_class.indexOf(" "+target_class+" ") != -1) {
      // Split it off from e.g. "trans_id:2543"
      this_id = li_elements[i].id.split(":");
      if (found_current == 1) {
        return this_id[1];
        }
      else if (this_id[1] == current_id) {
        found_current = 1;
        }
      }
    }
  }

