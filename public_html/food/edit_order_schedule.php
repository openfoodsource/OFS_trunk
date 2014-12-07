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
    // If we had some errors, go back and ask for a revision
    if (count ($error_array) > 0)
      {
        $errors_found = true;
        $error_message = '
          <div class="error_message">
            <p class="message">Information was not accepted. Please correct the following problems and resubmit.</p>
            <ul class="error_list">
              <li>'.implode ("</li>\n<li>", $error_array).'</li>
            </ul>
          </div>';
      }
  }
if ($action == 'post_edit' && $errors_found == false)
  {
    $query = '
      UPDATE
        '.TABLE_ORDER_CYCLES.'
      SET
        date_open = "'.date ('Y-m-d H:i:s', strtotime (mysql_real_escape_string($_POST['date_open']))).'",
        date_closed = "'.date ('Y-m-d H:i:s', strtotime (mysql_real_escape_string($_POST['date_closed']))).'",
        order_fill_deadline = "'.date ('Y-m-d H:i:s', strtotime (mysql_real_escape_string($_POST['order_fill_deadline']))).'",
        delivery_date = "'.date ('Y-m-d', strtotime (mysql_real_escape_string($_POST['delivery_date']))).'",
        msg_all = "'.nl2br (mysql_real_escape_string($_POST['msg_all'])).'",
        msg_bottom = "'.nl2br (mysql_real_escape_string($_POST['msg_bottom'])).'",
        coopfee = "'.mysql_real_escape_string($_POST['coopfee']).'",
        invoice_price = "'.mysql_real_escape_string($_POST['invoice_price']).'",
        producer_markdown = "'.mysql_real_escape_string($_POST['producer_markdown']).'",
        retail_markup = "'.mysql_real_escape_string($_POST['retail_markup']).'",
        wholesale_markup = "'.mysql_real_escape_string($_POST['wholesale_markup']).'"
      WHERE
        delivery_id = "'.mysql_real_escape_string($_POST['delivery_id']).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 759821 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $message = '<p class="message">Order cycle has been updated.</p>';
  }
elseif ($action == 'post_new' && $errors_found == false)
  {
    $query = '
      INSERT INTO
        '.TABLE_ORDER_CYCLES.'
      SET
        date_open = "'.date ('Y-m-d H:i:s', strtotime (mysql_real_escape_string($_POST['date_open']))).'",
        date_closed = "'.date ('Y-m-d H:i:s', strtotime (mysql_real_escape_string($_POST['date_closed']))).'",
        order_fill_deadline = "'.date ('Y-m-d H:i:s', strtotime (mysql_real_escape_string($_POST['order_fill_deadline']))).'",
        delivery_date = "'.date ('Y-m-d', strtotime (mysql_real_escape_string($_POST['delivery_date']))).'",
        msg_all = "'.nl2br (mysql_real_escape_string($_POST['msg_all'])).'",
        msg_bottom = "'.nl2br (mysql_real_escape_string($_POST['msg_bottom'])).'",
        coopfee = "'.mysql_real_escape_string($_POST['coopfee']).'",
        invoice_price = "'.mysql_real_escape_string($_POST['invoice_price']).'",
        producer_markdown = "'.mysql_real_escape_string($_POST['producer_markdown']).'",
        retail_markup = "'.mysql_real_escape_string($_POST['retail_markup']).'",
        wholesale_markup = "'.mysql_real_escape_string($_POST['wholesale_markup']).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 759821 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    // Get the insert_id to use for the "delivery_id" field
    $query = '
      SELECT LAST_INSERT_ID() AS delivery_id';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 883782 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $delivery_id = mysql_insert_id();
    $_POST['delivery_id'] = $delivery_id;
    $message = 'New order cycle has been added.';
  }
elseif ($action == 'delete')
  {
    $query = '
      DELETE FROM
        '.TABLE_ORDER_CYCLES.'
      WHERE
        delivery_id = "'.mysql_real_escape_string($_POST['delivery_id']).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 759821 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $message = 'Order cycle has been deleted.';
  }
// Query for information about this order cycle
if ($errors_found == true)
  {
    $order_cycle_info = $_POST;
  }
else
  {
    $query = '
      SELECT
        *
      FROM
        '.TABLE_ORDER_CYCLES.'
      WHERE
        delivery_id="'.mysql_real_escape_string ($delivery_id).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 759821 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $order_cycle_info = mysql_fetch_array($result);
  }
// Or take the information from the submitted data
if (1)
  {
    $delivery_id = $order_cycle_info['delivery_id'];
    $date_open = $order_cycle_info['date_open'];
    $date_closed = $order_cycle_info['date_closed'];
    $delivery_date = $order_cycle_info['delivery_date'];
    $order_fill_deadline = $order_cycle_info['order_fill_deadline'];
    $msg_all = $order_cycle_info['msg_all'];
    $msg_bottom = $order_cycle_info['msg_bottom'];
    $coopfee = $order_cycle_info['coopfee'];
    $invoice_price = $order_cycle_info['invoice_price'];    /* 0=show coop price; 1=show retail price */
    $producer_markdown = $order_cycle_info['producer_markdown'];
    $retail_markup = $order_cycle_info['retail_markup'];
    $wholesale_markup = $order_cycle_info['wholesale_markup'];
  }
// See documentation for the auto-fill menu at http://api.jqueryui.com/autocomplete/
$display = '
<head>
  <style type="text/css">
    fieldset {
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
    input[type=radio] {
      float:none;
      }
    .radio_block {
      width:45%;
      float:left;
      text-align:center;
      background-color:#fff;
      border:1px solid #9e9e9e;
      }
    .radio_block:first-of-type {
      border-right:0;
      }
    .radio_block:last-of-type {
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
      width:45%;
      background-color:#ddf;
      }
    .deprecated {
      float:right;
      width:45%;
      background-color:#fdd;
      border:1px solid #800;
      }
    .optional {
      clear:both;
      margin-top:5px;
      width:97%;
      background-color:#dfd;
      border:1px solid #060;
      }
    .controls {
      float:right;
      width:45%;
      border:0;
      }
    .controls input[type=submit],
     .controls input[type=reset] {
     width:40%;
      margin:2% 5%;
      float:right;
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
      }
  </style>
  <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
  <script type="text/javascript">
    // Execute after the DOM is loaded
    $(function() {
      var no_submit = 0;
      $("#action_delete").click(function(event) {
        no_submit++;
        $("#action_delete").val("Confirm Delete"); // Change the button label
        $("#action_delete").addClass("warn"); // Change the button style
        $("#action_delete").attr("form", "edit_schedule"); // Connect to form for submission
        if (no_submit < 2) return false;
        });
      $("#action_delete").blur(function(event) {
        no_submit = 0;
        $("#action_delete").val("Delete"); // Change the button label
        $("#action_delete").removeClass("warn"); // Change the button style
        $("#action_delete").attr("form", "null"); // Disconnect to form for submission
        });
      });
  </script>
</head>
<body>
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
        <label>Invoice &dash; Show Price</label>
        <div class="radio_block">
          <label for="show_customer">Customer</label>
          <input type="radio" id="show_customer" name="invoice_price" value="1"'.($invoice_price == 1 ? ' checked="checked"' : '').'>
        </div>
        <div class="radio_block">
          <label for="show_coop">Co-op</label>
          <input type="radio" id="show_coop" name="invoice_price" value="0"'.($invoice_price == 0 ? ' checked="checked"' : '').'>
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
  </div>
</body>';
echo $display;
?>