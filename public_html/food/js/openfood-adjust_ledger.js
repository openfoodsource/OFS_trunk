// Get prior transactions and display it
function get_replaced_transaction (current,target) {
  jQuery.post("adjust_ledger.php", {
    target:target,
    type:"single",
    method:"ajax"
    },
  function(row_content) {
    // First remove any prior instance of the new transaction
    if (jQuery('#'+target).length) {
      jQuery('tbody').remove('#'+target);
      }
    // New row content is ruturned and inserted above the current transaction row
    jQuery(row_content).insertBefore('#'+current);
// alert ("LENGTH: "+row_content.length);
    });
  }

// Get replacement transactions and display it
function get_replacing_transaction (current,target) {
  jQuery.post("adjust_ledger.php", {
    target:target,
    type:"single",
    method:"ajax"
    },
  function(row_content) {
    // First remove any prior instance of the new transaction
    if (jQuery('#'+target).length) {
      jQuery('tbody').remove('#'+target);
      }
    // New row content is ruturned and inserted above the current transaction row
    jQuery(row_content).insertAfter('#'+current);
// alert ("LENGTH: "+row_content.length);
    });
  }

jQuery(document).ready(function(){
  jQuery(".row1").mouseover(function(){
    // jQuery(".row1").css("backgroundColor", "yellow");
    jQuery("#row1_header").addClass("highlight");
    });
  jQuery(".row1").mouseout(function(){
    // jQuery(".row1").css("backgroundColor", "white");
    jQuery("#row1_header").removeClass("highlight");
    });
  jQuery(".row2").mouseover(function(){
    // jQuery(".row2").css("backgroundColor", "yellow");
    jQuery("#row2_header").addClass("highlight");
    });
  jQuery(".row2").mouseout(function(){
    // jQuery(".row1").css("backgroundColor", "white");
    jQuery("#row2_header").removeClass("highlight");
    });
  });

///////////////////////////////////////////////////////////////////////// THIS IS FOR EDITING TRANSACTIONS

// Get edit dialogue and display it
function get_edit_dialog (current,target) {
  jQuery.post("adjust_ledger.php", {
    target:target,
    type:"edit",
    method:"ajax"
    },
  function(row_content) {
    // First remove any prior instance of the new transaction
    if (jQuery('#edit_dialog_'+target).length) {
      jQuery('tr').remove('#edit_dialog_'+target);
      }
    // New row content is ruturned and inserted above the current transaction row
    jQuery(row_content).insertBefore('#edit_control_'+current);
// alert ("LENGTH: "+row_content.length);
    });
  }

function cancel_edit_dialog (target) {
  // Remove the editing dialog
  if (jQuery('#edit_dialog_'+target).length) {
    jQuery('tr').remove('#edit_dialog_'+target);
    }
  }

function update_transaction (target) {
  if (jQuery("#zero_split_"+target).is(':checked')) {
    var zero_split = jQuery("#zero_split_"+target).val()
    } else var zero_split = "";
  jQuery.post("adjust_ledger.php", {
    target:target,
    type:"update",
    method:"ajax",
  source_type:jQuery("#source_type_"+target).children("option").filter(":selected").text(),
    source_key:jQuery("#source_key_"+target).val(),
  target_type:jQuery("#target_type_"+target).children("option").filter(":selected").text(),
    target_key:jQuery("#target_key_"+target).val(),
    amount:jQuery("#amount_"+target).val(),
  text_key:jQuery("#text_key_"+target).val(),
    effective_datetime:jQuery("#effective_datetime_"+target).val(),
    basket_id:jQuery("#basket_id_"+target).val(),
    bpid:jQuery("#bpid_"+target).val(),
    site_id:jQuery("#site_id_"+target).val(),
    delivery_id:jQuery("#delivery_id_"+target).val(),
    pvid:jQuery("#pvid_"+target).val(),
    transaction_group_id:jQuery("#transaction_group_id").val(),
    adjustment_message:jQuery("#adjustment_message").val(),
    zero_split:zero_split
    },
  function(row_content) {
    // First create a temporary placeholder immediately after this tbody
    jQuery('<hr id="placeholder">').insertAfter('#'+target);
    // Then remove the current instance of the edited transaction
    if (jQuery('#'+target).length) {
      jQuery('tbody').remove('#'+target);
      }
    // Then add the original transaction and new transaction back (before the placeholder)
    jQuery(row_content).insertBefore('#placeholder');
    // Then remove the placeholder
    if (jQuery('#placeholder').length) {
      jQuery('hr').remove('#placeholder');
      }
    });
  }

///////////////////////////////////////////////////////////////////////// THIS IS FOR ADDING NEW TRANSACTIONS

// Get new dialogue and display it
function get_new_dialog (current,target) {
  jQuery.post("adjust_ledger.php", {
    target:target,
    type:"new",
    method:"ajax"
    },
  function(row_content) {
    // First remove any prior instance of the new transaction
    if (jQuery('#new_dialog').length) {
      jQuery('tr').remove('#new_dialog');
      }
    // New row content is ruturned and inserted above the current transaction row
// alert ("LENGTH: "+row_content.length);
    jQuery(row_content).insertBefore('#edit_control_'+current);
    });
  }

function cancel_new_dialog () {
  // Remove the editing dialog
  if (jQuery('#new_dialog').length) {
    jQuery('tr').remove('#new_dialog');
    }
  }

function new_transaction (target) {
  jQuery.post("adjust_ledger.php", {
    target:target,
    type:"add",
    method:"ajax",
  source_type:jQuery("#source_type").children("option").filter(":selected").text(),
    source_key:jQuery("#source_key").val(),
  target_type:jQuery("#target_type").children("option").filter(":selected").text(),
    target_key:jQuery("#target_key").val(),
    amount:jQuery("#amount").val(),
  text_key:jQuery("#text_key").val(),
    effective_datetime:jQuery("#effective_datetime").val(),
    basket_id:jQuery("#basket_id").val(),
    bpid:jQuery("#bpid").val(),
    site_id:jQuery("#site_id").val(),
    delivery_id:jQuery("#delivery_id").val(),
    pvid:jQuery("#pvid").val(),
    transaction_group_id:"", // Do not group this transaction
    adjustment_message:jQuery("#adjustment_message").val()
    },
  function(row_content) {
    // First create a temporary placeholder immediately after this tbody
    jQuery('<hr id="placeholder">').insertAfter('#'+target);
    // Then remove the current instance of the edited transaction
    if (jQuery('#'+target).length) {
      jQuery('tbody').remove('#'+target);
      }
    // Then add the original transaction and new transaction back (before the placeholder)
    jQuery(row_content).insertBefore('#placeholder');
    // Then remove the placeholder
    if (jQuery('#placeholder').length) {
      jQuery('hr').remove('#placeholder');
      }
    });
  }

///////////////////////////////////////////////////////////////////////// THESE ARE FOR EDITED OR NEW TRANSACTIONS

function close_transaction_row (target) {
  // Remove a transaction row
  if (jQuery('#'+target).length) {
    jQuery('tbody').remove('#'+target);
    }
  }

function reserve_transaction_group_id () {
  // Only reserve a group_id once
  if (jQuery("#transaction_group_id").val() == 0) {
    jQuery.post("adjust_ledger.php", {
      type:"reserve_transaction_group_id",
      method:"ajax"
      },
    function(transaction_group_id) {
      if (transaction_group_id.length > 0) {
        jQuery("#transaction_group_id").val(transaction_group_id);
        }
      });
    }
  }
