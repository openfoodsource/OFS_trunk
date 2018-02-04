// Converting from receive_payments to make_payments:
//   member_id,basket_id -> producer_id,delivery_id

function show_make_payment_form(producer_id,delivery_id,business_name) {
  // Get the make_payment_form
  // var element_id = p_arrElements[j].attributes["id"].value;
  jQuery.post("ajax/make_payments_process.php", {
    process:"get_make_payment_form",
    producer_id:producer_id,
    delivery_id:delivery_id,
    business_name:business_name
    },
  function(make_payment_form) {
    // First kill off (delete) any existing make_payment form on the page
    close_make_payment_form();
    // Add the new receive payment form
    if (delivery_id != 0)
      jQuery(make_payment_form).appendTo('#detail_producer_id'+producer_id);
//     else
//       jQuery(make_payment_form).appendTo('#detail_producer_id'+producer_id);
    });
  }

// Post the make_payment information
function make_payment(producer_id,delivery_id) {
  jQuery.post("ajax/make_payments_process.php", {
    process:"make_payment",
    producer_id:producer_id,
    delivery_id:delivery_id,
    amount:jQuery("#amount").val(),
    effective_datetime:jQuery("#effective_datetime").val(),
    payment_type:jQuery("#payment_type_cash_check").val(),
    paypal_fee:jQuery("#paypal_fee").length ? jQuery("#paypal_fee").val() : "",
    paypal_comment:jQuery("#paypal_comment").length ? jQuery("#paypal_comment").val() : "",
    square_fee:jQuery("#square_fee").length ? jQuery("#square_fee").val() : "",
    square_comment:jQuery("#square_comment").length ? jQuery("#square_comment").val() : "",
    memo:jQuery("#memo").val(),
    batch_number:jQuery("#batch_number").val(),
    comment:jQuery("#comment").val()
    },
  function(make_payment) {
    // Returned value has first ten fixed characters indicating status
    var make_payment_status = make_payment.substr(0,10)
    var make_payment_result = make_payment.substr(10)
    if (make_payment_status == "ACCEPT    ") {
      // Payment was recorded, so close the make_payment form
      close_make_payment_form();
      // Then reload the member information section
      reload_detail_line(producer_id,delivery_id);
      }
    else if (make_payment_status == "ERROR     ") {
      // Payment failed for some reason so clear the form and show it form again
      close_make_payment_form();
      // Add the new receive payment form
      jQuery(make_payment_result).appendTo('#detail_producer_id'+producer_id);
      }
    else {
      }
    });
  }

function close_make_payment_form() {
  if (jQuery('#make_payment_row').length) {
    jQuery('#make_payment_row').replaceWith("");
    }
  }

function reload_detail_line (producer_id,delivery_id) {
  jQuery.post("ajax/make_payments_detail.php", {
    request:"producer_total_and_payments",
    producer_id:producer_id,
    delivery_id:delivery_id
    },
  function(make_payments_detail_data) {
    jQuery("#detail_producer_id"+producer_id).html(make_payments_detail_data);
    });
  }
