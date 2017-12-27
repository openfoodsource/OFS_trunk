<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');

// Set defaults
$errors_found = false;
$show_new_button = false;
$show_update_button = false;
$show_delete_button = false; 
$order_cycle_info = array ();
$error_array = array ();
$error_message = '';
$message = '';

if (isset($_GET['delivery_id']))
  {
    $delivery_id = $_GET['delivery_id'];
    $action = 'start_edit';
    $show_new_button = true;
    $show_update_button = true;
    $show_delete_button = true;
  }
elseif ($_POST['action'] == 'Update' && isset($_POST['delivery_id']))
  {
    $delivery_id = $_POST['delivery_id'];
    $action = 'post_edit';
    $show_new_button = true;
    $show_update_button = true;
    $show_delete_button = true;
  }
elseif ($_POST['action'] == 'Add As New')
  {
    $action = 'post_new';
    $show_new_button = true;
    $show_update_button = true;
    $show_delete_button = true;
  }
elseif ($_POST['action'] == 'Confirm Delete')
  {
    $action = 'delete';
    $show_new_button = true;
    $show_update_button = false;
    $show_delete_button = false;
  }
else
  {
    $action = 'start_new';
    $show_new_button = true;
    $show_update_button = false;
    $show_delete_button = false;
  }
// Put together the customer_type value
$customer_type_array = array ();
if (isset($_POST['customer_type_institution']) && $_POST['customer_type_institution'] == 'true')
  array_push ($customer_type_array, 'institution');
if (isset($_POST['customer_type_member']) && $_POST['customer_type_member'] == 'true')
  array_push ($customer_type_array, 'member');
$customer_type = implode (',', $customer_type_array);

// Validate input
if ($action == 'post_edit' || $action == 'post_new')
  {
    if (strtotime ($_POST['date_open']) === false)
      array_push ($error_array, 'Please enter a valid Date Open.');
    if (strtotime ($_POST['date_closed']) === false)
      array_push ($error_array, 'Please enter a valid Date Closed.');
    if (strtotime ($_POST['order_fill_deadline']) === false)
      array_push ($error_array, 'Please enter a valid Order Fill Deadline.');
    if (strtotime ($_POST['delivery_date']) === false)
      array_push ($error_array, 'Please enter a valid Delivery Date.');
    if ($_POST['invoice_price'] != 0 && $_POST['invoice_price'] != 1)
      array_push ($error_array, 'Please choose a value for Invoice - Show Price.');
    if (! is_numeric ($_POST['coopfee']))
      array_push ($error_array, 'Please enter the Co-op Fee as a number.');
    if (! is_numeric ($_POST['producer_markdown']))
      array_push ($error_array, 'Please enter Producer Markdown as a number.');
    if (! is_numeric ($_POST['retail_markup']))
      array_push ($error_array, 'Please enter Retail Markup as a number.');
    if (! is_numeric ($_POST['wholesale_markup']))
      array_push ($error_array, 'Please enter Wholesale Markup as a number.');
    if (! is_numeric ($_POST['transport_id']))
      array_push ($error_array, 'Please select a transport identity.');
    // If we had some errors, go back and ask for a revision
  }
$error_message = display_alert('error', 'Please correct the following problems and resubmit.', $error_array);
if (strlen ($error_message) > 0) $errors_found = true;
if ($action == 'post_edit' && $errors_found == false)
  {
    $query = '
      UPDATE
        '.TABLE_ORDER_CYCLES.'
      SET
        date_open = "'.date ('Y-m-d H:i:s', strtotime (mysqli_real_escape_string ($connection, $_POST['date_open']))).'",
        date_closed = "'.date ('Y-m-d H:i:s', strtotime (mysqli_real_escape_string ($connection, $_POST['date_closed']))).'",
        order_fill_deadline = "'.date ('Y-m-d H:i:s', strtotime (mysqli_real_escape_string ($connection, $_POST['order_fill_deadline']))).'",
        delivery_date = "'.date ('Y-m-d', strtotime (mysqli_real_escape_string ($connection, $_POST['delivery_date']))).'",
        customer_type = "'.$customer_type.'",
        msg_all = "'.nl2br (mysqli_real_escape_string ($connection, $_POST['msg_all'])).'",
        msg_bottom = "'.nl2br (mysqli_real_escape_string ($connection, $_POST['msg_bottom'])).'",
        coopfee = "'.mysqli_real_escape_string ($connection, $_POST['coopfee']).'",
        invoice_price = "'.mysqli_real_escape_string ($connection, $_POST['invoice_price']).'",
        producer_markdown = "'.mysqli_real_escape_string ($connection, $_POST['producer_markdown']).'",
        retail_markup = "'.mysqli_real_escape_string ($connection, $_POST['retail_markup']).'",
        wholesale_markup = "'.mysqli_real_escape_string ($connection, $_POST['wholesale_markup']).'",
        transport_id = "'.mysqli_real_escape_string($connection, $_POST['transport_id']).'"
      WHERE
        delivery_id = "'.mysqli_real_escape_string ($connection, $_POST['delivery_id']).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 459821 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $message = '<p class="message">Order cycle has been updated.</p>';
    // Close the modal and reload the cycle list
    $modal_action = 'reload_parent()';
  }
elseif ($action == 'post_new' && $errors_found == false)
  {
    $query = '
      INSERT INTO
        '.TABLE_ORDER_CYCLES.'
      SET
        date_open = "'.date ('Y-m-d H:i:s', strtotime (mysqli_real_escape_string ($connection, $_POST['date_open']))).'",
        date_closed = "'.date ('Y-m-d H:i:s', strtotime (mysqli_real_escape_string ($connection, $_POST['date_closed']))).'",
        order_fill_deadline = "'.date ('Y-m-d H:i:s', strtotime (mysqli_real_escape_string ($connection, $_POST['order_fill_deadline']))).'",
        delivery_date = "'.date ('Y-m-d', strtotime (mysqli_real_escape_string ($connection, $_POST['delivery_date']))).'",
        customer_type = "'.$customer_type.'",
        msg_all = "'.nl2br (mysqli_real_escape_string ($connection, $_POST['msg_all'])).'",
        msg_bottom = "'.nl2br (mysqli_real_escape_string ($connection, $_POST['msg_bottom'])).'",
        coopfee = "'.mysqli_real_escape_string ($connection, $_POST['coopfee']).'",
        invoice_price = "'.mysqli_real_escape_string ($connection, $_POST['invoice_price']).'",
        producer_markdown = "'.mysqli_real_escape_string ($connection, $_POST['producer_markdown']).'",
        retail_markup = "'.mysqli_real_escape_string ($connection, $_POST['retail_markup']).'",
        wholesale_markup = "'.mysqli_real_escape_string ($connection, $_POST['wholesale_markup']).'",
        transport_id = "'.mysqli_real_escape_string($connection, $_POST['transport_id']).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 752821 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    // Get the insert_id to use for the "delivery_id" field
    $query = '
      SELECT LAST_INSERT_ID() AS delivery_id';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 883782 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $delivery_id = mysqli_insert_id ($connection);
    $_POST['delivery_id'] = $delivery_id;
    $message = 'New order cycle has been added.';
    // Close the modal and reload the cycle list
    $modal_action = 'reload_parent()';
  }
elseif ($action == 'delete')
  {
    $query = '
      DELETE FROM
        '.TABLE_ORDER_CYCLES.'
      WHERE
        delivery_id = "'.mysqli_real_escape_string ($connection, $_POST['delivery_id']).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 759121 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $message = 'Order cycle has been deleted.';
    // Close the modal and reload the cycle list
    $modal_action = 'reload_parent()';
  }
// Query for information about this order cycle
if ($errors_found == true)
  {
    $order_cycle_info = $_POST;
  }
else
  {
    // Get information about this delivery_id
    $query = '
      SELECT
        *
      FROM
        '.TABLE_ORDER_CYCLES.'
      WHERE
        delivery_id="'.mysqli_real_escape_string ($connection, $delivery_id).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 759828 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $order_cycle_info = mysqli_fetch_array ($result, MYSQLI_ASSOC);
  }
// Assign variables for display in the form
$delivery_id = $order_cycle_info['delivery_id'];
$date_open = $order_cycle_info['date_open'];
$date_closed = $order_cycle_info['date_closed'];
$delivery_date = $order_cycle_info['delivery_date'];
$order_fill_deadline = $order_cycle_info['order_fill_deadline'];
$customer_type = $order_cycle_info['customer_type'];
$msg_all = $order_cycle_info['msg_all'];
$msg_bottom = $order_cycle_info['msg_bottom'];
$coopfee = $order_cycle_info['coopfee'];
$invoice_price = $order_cycle_info['invoice_price'];    /* 0=show coop price; 1=show retail price */
$producer_markdown = $order_cycle_info['producer_markdown'];
$retail_markup = $order_cycle_info['retail_markup'];
$wholesale_markup = $order_cycle_info['wholesale_markup'];
// Get set of transport_identities
$query = '
  SELECT
    DISTINCT('.NEW_TABLE_TRANSPORT_IDENTITIES.'.transport_id) AS transport_id,
    '.NEW_TABLE_TRANSPORT_IDENTITIES.'.transport_identity_name,
    MAX('.TABLE_ORDER_CYCLES.'.delivery_id) AS delivery_id
  FROM
    '.NEW_TABLE_TRANSPORT_IDENTITIES.'
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(transport_id)
  WHERE 1
  ORDER BY delivery_id DESC';
$result = @mysqli_query($connection, $query) or die(debug_print ("ERROR: 750321 ", array ($query,mysqli_error($connection)), basename(__FILE__).' LINE '.__LINE__));
if ($order_cycle_info['transport_id'] > 0)
  {
    // Pre-existing value exists, so do not preload a NULL default
    $select_transport_id = '';
  }
else
  {
    // No pre-existing value, so set first option to NULL
    $select_transport_id = '<option value="0">None Selected</option>';
  }
// Cycle through the transport_identities and prepare the option list
while ($transport_id_info = mysqli_fetch_array($result, MYSQLI_ASSOC))
  {
    $select_transport_id .= '
      <option value="'.$transport_id_info['transport_id'].'"'.($transport_id_info['transport_id'] == $order_cycle_info['transport_id'] ? ' selected' : '').'>'.$transport_id_info['transport_identity_name'].'</option>';
  }

// See documentation for the auto-fill menu at http://api.jqueryui.com/autocomplete/

$display = '
  <h3>Configure Order Cycle '.$delivery_id.'</h3>
  '.$error_message.'
  '.$message.'
  <div id="main_content">
    <form name="edit_schedule" id="edit_schedule" action="'.$_SERVER['SCRIPT_NAME'].'" method="POST">
      <fieldset class="required">
        <input type="hidden" id="delivery_id" name="delivery_id" value="'.$delivery_id.'">
        <legend>Required Fields</legend>
        <label for="date_open">Date Open (date/time)</label>
        <input type="datetime" id="date_open" name="date_open" required placeholder="YYYY-MM-DD HH:MM:SS" value="'.$date_open.'">
        <label for="date_closed">Date Closed (date/time)</label>
        <input type="text" id="date_closed" name="date_closed" required placeholder="YYYY-MM-DD HH:MM:SS" value="'.$date_closed.'">
        <label for="order_fill_deadline">Order Fill Deadline (date/time)</label>
        <input type="text" id="order_fill_deadline" name="order_fill_deadline" required placeholder="YYYY-MM-DD HH:MM:SS" value="'.$order_fill_deadline.'">
        <label for="delivery_date">Delivery Date (date)</label>
        <input type="text" id="delivery_date" name="delivery_date" required placeholder="YYYY-MM-DD" value="'.$delivery_date.'">
        <label>Shopping enabled for:</label>
        <div class="option_block">
          <label for="customer_type_institution">Retail Members</label>
          <input type="checkbox" id="customer_type_institution" name="customer_type_member" value="true"'.(strpos($customer_type,'member') !== false ? ' checked' : '').'>
        </div>
        <div class="option_block">
          <label for="customer_type_institution">Institutions</label>
          <input type="checkbox" id="customer_type_institution" name="customer_type_institution" value="true"'.(strpos($customer_type,'institution') !== false ? ' checked' : '').'>
        </div>
        <label>How to show fees on invoice:</label>
        <div class="option_block">
          <label for="show_customer">Fees included in prices</label>
          <input type="radio" id="show_customer" name="invoice_price" value="1"'.($invoice_price == 1 ? ' checked="checked"' : '').'>
        </div>
        <div class="option_block">
          <label for="show_coop">Separate line-item for fees</label>
          <input type="radio" id="show_coop" name="invoice_price" value="0"'.($invoice_price == 0 ? ' checked="checked"' : '').'>
        </div>
        <div class="select_block">
          <label for="transport_identity">Select a Transport Configuration</label>
          <select name="transport_id">'.$select_transport_id.'
          </select>
        </div>
      </fieldset>
      <fieldset class="deprecated">
        <legend>Unused Fields (will be removed)</legend>
        <label for="coopfee">Co-op Fee (dollars)</label>
        <input type="text" id="coopfee" name="coopfee" pattern="\d*(\.\d{2}){0,1}" value="'.number_format($coopfee, 2).'">
        <label for="producer_markdown">Producer Markdown (percent)</label>
        <input type="text" id="producer_markdown" name="producer_markdown" pattern="\d*(\.\d{0,3}){0,1}" value="'.number_format($producer_markdown, 3).'">
        <label for="retail_markup">Retail Markup (percent)</label>
        <input type="text" id="retail_markup" name="retail_markup" pattern="\d*(\.\d{0,3}){0,1}" value="'.number_format($retail_markup, 3).'">
        <label for="wholesale_markup">Wholesale Markup (percent)</label>
        <input type="text" id="wholesale_markup" name="wholesale_markup" pattern="\d*(\.\d{0,3}){0,1}" value="'.number_format($wholesale_markup, 3).'">
      </fieldset>
      <fieldset class="controls">
        '.($show_update_button == true ? '<input type="submit" id="action_update" name="action" value="Update">' : '').'
        '.($show_new_button == true ? '<input type="submit" id="action_add_new" name="action" value="Add As New">' : '').'
        '.($show_delete_button == true ? '<input type="submit" id="action_delete" name="action" form="null" value="Delete">' : '').'
        <input type="reset" value="Reset">
      </fieldset>
      <fieldset class="optional">
        <legend>Invoice Messages</legend>
        <label for="msg_all">Message for all invoices</label>
        <textarea id="msg_all" name="msg_all">'.br2nl (htmlspecialchars($msg_all, ENT_QUOTES)).'</textarea>
        <label for="msg_bottom">Message at bottom of invoices</label>
        <textarea id="msg_bottom" name="msg_bottom">'.br2nl (htmlspecialchars($msg_bottom, ENT_QUOTES)).'</textarea>
      </fieldset>
    </form>
  </div>';

$page_specific_javascript = '
  // Execute after the DOM is loaded
  jQuery(function() {
    var no_submit = 0;
    jQuery("#action_delete").click(function(event) {
      no_submit++;
      jQuery("#action_delete").val("Confirm Delete"); // Change the button label
      jQuery("#action_delete").addClass("warn"); // Change the button style
      jQuery("#action_delete").attr("form", "edit_schedule"); // Connect to form for submission
      if (no_submit < 2) return false;
      });
    jQuery("#action_delete").blur(function(event) {
      no_submit = 0;
      jQuery("#action_delete").val("Delete"); // Change the button label
      jQuery("#action_delete").removeClass("warn"); // Change the button style
      jQuery("#action_delete").attr("form", "null"); // Disconnect to form for submission
      });
    });';

$page_specific_css = '
  body {
    margin:10px;
    }
  fieldset {
    padding:10px;
    }
  legend {
    font-size:80%;
    }
  .required legend {
    color:#008;
    }
  .deprecated legend {
    color:#800;
    }
  .optional legend {
    color:#060;
    }
  input {
    float:left;
    clear:left;
    margin-bottom:0.5em;
    }
  input:invalid {
    color:#800;
    }
  input[type=text],
  input[type=datetime] {
    width:24em;
    }
  input[type=radio] {
    float:none;
    }
  input[type=checkbox] {
    float:none;
    }
  .option_block {
    width:45%;
    max-width:10em;
    float:left;
    text-align:center;
    background-color:#fff;
    border:1px solid #9e9e9e;
    margin-bottom:0.5em;
    }
  .option_block:first-of-type {
    border-right:0;
    clear:left;
    }
  .option_block:last-of-type {
    border-left:0;
    }
  label {
    display:block;
    width:100%;
    clear:both;
    font-size:70%;
    color:#008;
    }
  textarea {
    width:100%;
    height:80px;
    }
  .required {
    float:left;
    border:1px solid #008;
    width:100%;
    background-color:#ddf;
    }
  .deprecated {
    display:none;
    float:right;
    width:100%;
    background-color:#fdd;
    border:1px solid #800;
    }
  .optional {
    clear:both;
    margin-top:5px;
    width:100%;
    background-color:#dfd;
    border:1px solid #060;
    }
  .controls {
    float:left;
    width:100%;
    border:0;
    }
  .controls input[type=submit],
  .controls input[type=reset] {
    clear:none;
    width:15%;
    min-width:8em;
    margin:1em;
    float:left;
    }
  .controls input:hover {
    font-weight:bold;
    }
  input.warn {
    color:#800;
    background-color:#ff8;
    }
  .error_message {
    background-image: url("'.DIR_GRAPHICS.'error.png");
    background-repeat:no-repeat;
    background-position:left top;
    position:absolute;
    z-index:5;
    width:0px;
    height:70px;
    color:#fff;
    opacity:0.7;
    padding:10px 10px 10px 40px;
    background-color:#800;
    font-weight:normal;
    font-style:italic;
    font-size: 1.1em;
    overflow:hidden;
    border-radius: 25px;
    border-radius: 25px;
    box-shadow: 5px 5px 5px #000;
    }
  .error_message:hover {
    width:50%;
    height:auto;
    opacity:0.9;
    }
  p.message {
    width:95%;
    margin:10px 20px;
    font-size: 1.1em;
    color:#008;
    }
  .error_list {
    font-weight:normal;
    font-style:italic;
    font-size: 1.1em;
    color:#ff8;
    clear:left;
    }
  .error_message .message {
    color:#fff;
    }';

// This is always a popup dialog
$display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
