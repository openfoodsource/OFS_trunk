// Get prior transactions and display it
function get_replaced_transaction (current,target) {
  $.post("adjust_ledger.php", {
    target:target,
    type:"single",
    method:"ajax"
    },
  function(row_content) {
    // First remove any prior instance of the new transaction
    if ($('#'+target).length) {
      $('tbody').remove('#'+target);
      }
    // New row content is ruturned and inserted above the current transaction row
    $(row_content).insertBefore('#'+current);
// alert ("LENGTH: "+row_content.length);
    });
  }

// Get replacement transactions and display it
function get_replacing_transaction (current,target) {
  $.post("adjust_ledger.php", {
    target:target,
    type:"single",
    method:"ajax"
    },
  function(row_content) {
    // First remove any prior instance of the new transaction
    if ($('#'+target).length) {
      $('tbody').remove('#'+target);
      }
    // New row content is ruturned and inserted above the current transaction row
    $(row_content).insertAfter('#'+current);
// alert ("LENGTH: "+row_content.length);
    });
  }

$(document).ready(function(){
  $(".row1").mouseover(function(){
    // $(".row1").css("backgroundColor", "yellow");
    $("#row1_header").addClass("highlight");
    });
  $(".row1").mouseout(function(){
    // $(".row1").css("backgroundColor", "white");
    $("#row1_header").removeClass("highlight");
    });
  $(".row2").mouseover(function(){
    // $(".row2").css("backgroundColor", "yellow");
    $("#row2_header").addClass("highlight");
    });
  $(".row2").mouseout(function(){
    // $(".row1").css("backgroundColor", "white");
    $("#row2_header").removeClass("highlight");
    });
  });

///////////////////////////////////////////////////////////////////////// THIS IS FOR EDITING TRANSACTIONS

// Get edit dialogue and display it
function get_edit_dialog (current,target) {
  $.post("adjust_ledger.php", {
    target:target,
    type:"edit",
    method:"ajax"
    },
  function(row_content) {
    // First remove any prior instance of the new transaction
    if ($('#edit_dialog_'+target).length) {
      $('tr').remove('#edit_dialog_'+target);
      }
    // New row content is ruturned and inserted above the current transaction row
    $(row_content).insertBefore('#edit_control_'+current);
// alert ("LENGTH: "+row_content.length);
    });
  }

function cancel_edit_dialog (target) {
  // Remove the editing dialog
  if ($('#edit_dialog_'+target).length) {
    $('tr').remove('#edit_dialog_'+target);
    }
  }

function update_transaction (target) {
  if ($("#zero_split_"+target).is(':checked')) {
    var zero_split = $("#zero_split_"+target).val()
    } else var zero_split = "";
  $.post("adjust_ledger.php", {
    target:target,
    type:"update",
    method:"ajax",
  source_type:$("#source_type_"+target).children("option").filter(":selected").text(),
    source_key:$("#source_key_"+target).val(),
  target_type:$("#target_type_"+target).children("option").filter(":selected").text(),
    target_key:$("#target_key_"+target).val(),
    amount:$("#amount_"+target).val(),
  text_key:$("#text_key_"+target).val(),
    effective_datetime:$("#effective_datetime_"+target).val(),
    basket_id:$("#basket_id_"+target).val(),
    bpid:$("#bpid_"+target).val(),
    site_id:$("#site_id_"+target).val(),
    delivery_id:$("#delivery_id_"+target).val(),
    pvid:$("#pvid_"+target).val(),
    transaction_group_id:$("#transaction_group_id").val(),
    adjustment_message:$("#adjustment_message").val(),
    zero_split:zero_split
    },
  function(row_content) {
    // First create a temporary placeholder immediately after this tbody
    $('<hr id="placeholder">').insertAfter('#'+target);
    // Then remove the current instance of the edited transaction
    if ($('#'+target).length) {
      $('tbody').remove('#'+target);
      }
    // Then add the original transaction and new transaction back (before the placeholder)
    $(row_content).insertBefore('#placeholder');
    // Then remove the placeholder
    if ($('#placeholder').length) {
      $('hr').remove('#placeholder');
      }
    });
  }

///////////////////////////////////////////////////////////////////////// THIS IS FOR ADDING NEW TRANSACTIONS

// Get new dialogue and display it
function get_new_dialog (current,target) {
  $.post("adjust_ledger.php", {
    target:target,
    type:"new",
    method:"ajax"
    },
  function(row_content) {
    // First remove any prior instance of the new transaction
    if ($('#new_dialog').length) {
      $('tr').remove('#new_dialog');
      }
    // New row content is ruturned and inserted above the current transaction row
// alert ("LENGTH: "+row_content.length);
    $(row_content).insertBefore('#edit_control_'+current);
    });
  }

function cancel_new_dialog () {
  // Remove the editing dialog
  if ($('#new_dialog').length) {
    $('tr').remove('#new_dialog');
    }
  }

function new_transaction (target) {
  $.post("adjust_ledger.php", {
    target:target,
    type:"add",
    method:"ajax",
  source_type:$("#source_type").children("option").filter(":selected").text(),
    source_key:$("#source_key").val(),
  target_type:$("#target_type").children("option").filter(":selected").text(),
    target_key:$("#target_key").val(),
    amount:$("#amount").val(),
  text_key:$("#text_key").val(),
    effective_datetime:$("#effective_datetime").val(),
    basket_id:$("#basket_id").val(),
    bpid:$("#bpid").val(),
    site_id:$("#site_id").val(),
    delivery_id:$("#delivery_id").val(),
    pvid:$("#pvid").val(),
    transaction_group_id:"", // Do not group this transaction
    adjustment_message:$("#adjustment_message").val()
    },
  function(row_content) {
    // First create a temporary placeholder immediately after this tbody
    $('<hr id="placeholder">').insertAfter('#'+target);
    // Then remove the current instance of the edited transaction
    if ($('#'+target).length) {
      $('tbody').remove('#'+target);
      }
    // Then add the original transaction and new transaction back (before the placeholder)
    $(row_content).insertBefore('#placeholder');
    // Then remove the placeholder
    if ($('#placeholder').length) {
      $('hr').remove('#placeholder');
      }
    });
  }

///////////////////////////////////////////////////////////////////////// THESE ARE FOR EDITED OR NEW TRANSACTIONS

function close_transaction_row (target) {
  // Remove a transaction row
  if ($('#'+target).length) {
    $('tbody').remove('#'+target);
    }
  }

function reserve_transaction_group_id () {
  // Only reserve a group_id once
  if ($("#transaction_group_id").val() == 0) {
    $.post("adjust_ledger.php", {
      type:"reserve_transaction_group_id",
      method:"ajax"
      },
    function(transaction_group_id) {
      if (transaction_group_id.length > 0) {
        $("#transaction_group_id").val(transaction_group_id);
        }
      });
    }
  }
