function show_receive_payment_form(member_id,basket_id,preferred_name) {
  // Get the receive_payment_form
  // var element_id = p_arrElements[j].attributes["id"].value;
  jQuery.post("ajax/receive_payments_process.php", {
    process:"get_receive_payment_form",
    basket_id:basket_id,
    member_id:member_id,
    preferred_name:preferred_name
    },
  function(receive_payment_form) {
    // First kill off (delete) any existing receive_payment form on the page
    close_receive_payment_form();
    // Add the new receive payment form
    if (basket_id != 0)
      jQuery(receive_payment_form).appendTo('#basket_id'+basket_id);
    else
      jQuery(receive_payment_form).appendTo('#member_id'+member_id);
    });
  }

// Post the receive_payment information
function receive_payment(member_id,basket_id) {
  jQuery.post("ajax/receive_payments_process.php", {
    // member_id:
    // posted_by:
    // site_id:
    // delivery_id:
    process:"receive_payment",
    member_id:member_id,
    basket_id:basket_id,
    amount:jQuery("#amount").val(),
    effective_datetime:jQuery("#effective_datetime").val(),
    payment_type:jQuery("input[type='radio'][name='payment_type']:checked").val(),
    paypal_fee:jQuery("#paypal_fee").val(),
    paypal_comment:jQuery("#paypal_comment").val(),
    memo:jQuery("#memo").val(),
    batch_number:jQuery("#batch_number").val(),
    comment:jQuery("#comment").val()
    },
  function(receive_payment) {
    // Returned value has first ten fixed characters indicating status
    var receive_payment_status = receive_payment.substr(0,10)
    var receive_payment_result = receive_payment.substr(10)
    if (receive_payment_status == "ACCEPT    ") {
      // Payment was recorded, so close the receive_payment form
      close_receive_payment_form();
      // Then reload the member information section
      reload_detail_line(basket_id);
      }
    else if (receive_payment_status == "ERROR     ") {
      // Payment failed for some reason so clear the form and show it form again
      close_receive_payment_form();
      // Add the new receive payment form
      jQuery(receive_payment_result).appendTo('#basket_id'+basket_id);
      }
    else {
      }
    });
  }


function close_receive_payment_form() {
  if (jQuery('#receive_payment_row').length) {
    jQuery('#receive_payment_row').replaceWith("");
    }
  }




function reload_detail_line (basket_id) {
  jQuery.post("ajax/receive_payments_detail.php", {
    request:"basket_total_and_payments",
    basket_id:basket_id
    },
  function(receive_payments_detail_data) {
    jQuery("#basket_id"+basket_id).html(receive_payments_detail_data);
    });
  }

// 
//       var receive_payments_detail_line = "";
//       var receive_payments_detail_line = receive_payments_detail (basket_id);
//       // Set the new innerHTML for the member section...
//       document.getElementById("basket_id"+basket_id).innerHTML = receive_payments_detail_line;
//       // jQuery("#basket_id"+basket_id).html(receive_payments_detail_line.substr(0));
// // need stuff here

