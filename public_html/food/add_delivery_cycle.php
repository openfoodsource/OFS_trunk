<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');

/******************************************
 * 
 * Insert new deliery cycle dates to 'order_cycles'
 * date_open Saturdays at 2pm (YYYY-MM-DD 14:00:00)
 * delivery_date following Tuesday (YYYY-MM-DD)
 * date_closed Monday at 10am (YYYY-MM-DD 10:00:00)
 * order_fill_deadline 3pm delivery day (YYYY-MM-DD 15:00:00)
 * invoice_price = 1 (show retail price)
 * producer_markdown = 5
 * retail_markup = 10
 * wholesale_markup = 5
 * 
 ******************************************/
if ($_POST["addCycle"] == "doIT") {
	$dateOpen = $_POST["dateOpen"];
	$delDate = $_POST["delDate"];
	$dateClosed = $_POST["dateClosed"];
	$fillDeadline = $_POST["fillDeadline"];
	
	$prodQuery = "INSERT INTO order_cycles (date_open,delivery_date,date_closed,order_fill_deadline,invoice_price,producer_markdown,retail_markup,wholesale_markup) VALUES ('$dateOpen','$delDate','$dateClosed','$fillDeadline','0','5','10','5')";
	$prodSql = mysql_query($prodQuery,$connection) or die(mysql_error());
	if (mysql_affected_rows() > 0) {
		$display_admin .= '<h1>New order cycle added.</h1>';
	} else {
		$display_admin .= '<h2>ERROR: order cycle was NOT added.</h2>';
	}
} else {
	// next open date is next Saturday, unless it IS Saturday, then it's in 7 days ... same for other dates
	$nextOpenDate = (date('N', time()) == '6' ? strtotime("+7 days 2pm") : strtotime("next Saturday 2pm"));
	$nextCloseDate = (date('N', time()) == '1' ? strtotime("next Monday 10am") : strtotime("Monday 10am"));
	$nextFillDeadline = (date('N', time()) == '2' ? strtotime("next Tuesday 3pm") : strtotime("Tuesday 3pm"));
	$nextDelDay = (date('N', time()) == '2' ? strtotime("next Tuesday") : strtotime("Tuesday"));
	
	$nextOpen = date('Y-m-d H:i:s', $nextOpenDate);
	$nextClose = date('Y-m-d H:i:s', $nextCloseDate);
	$nextFill = date('Y-m-d H:i:s', $nextFillDeadline);
	$nextDelivery = date('Y-m-d', $nextDelDay);
	
	$display_admin .= "<br />";
	$display_admin .= "Next cycle opens: $nextOpen<br />";
	$display_admin .= "Next cycle closes: $nextClose<br />";
	$display_admin .= "Next fill deadline: $nextFill<br />";
	$display_admin .= "Next delivery date: $nextDelivery<br />";
	$display_admin .= "<form method='post' action='add_delivery_cycle.php'><br />";
	$display_admin .= "
	<input type='hidden' name='dateOpen' value='$nextOpen' />
	<input type='hidden' name='delDate' value='$nextDelivery' />
	<input type='hidden' name='dateClosed' value='$nextClose' />
	<input type='hidden' name='fillDeadline' value='$nextFill' />
	<input type='hidden' name='addCycle' value='doIT' />
	<input type='submit' name='submit' value='If these dates are correct, click to continue.' />
	</form>";
}

$page_title_html = '<span class="center bold">'.$_SESSION['show_name'].' --> </span>';
$page_subtitle_html = '<span class="subtitle">Add a Delivery Cycle</span>';
$page_title = 'Add Delivery Cycle';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_admin.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
