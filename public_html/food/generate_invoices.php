<?php
include_once 'config_openfood.php';
session_start();
valid_auth('cashier,site_admin');

// Use any valid delivery_id that was passed or else use current value
if (round ($_GET['delivery_id']) && $_GET['delivery_id'] <= ActiveCycle::delivery_id() && $_GET['delivery_id'] > 0)
  {
    $delivery_id = $_GET['delivery_id'];
  }
else
  {
    $delivery_id = ActiveCycle::delivery_id();
  }

// Get the target delivery date
$query = '
  SELECT
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($delivery_id).'"';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 893032 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
if ( $row = mysql_fetch_array($result) )
  {
    $delivery_date = date ("F j, Y", strtotime ($row['delivery_date']));
  }

$page_specific_javascript = '
<script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
<script type="text/javascript">


var c_arrElements;
var p_arrElements;
var i;

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

// CUSTOMER FUNCTIONS

function reset_cust_list() {
  c_arrElements = getElementsByClass("c_complete");
  for (i = 0; i < c_arrElements.length; i++) {
    if (c_arrElements[i].attributes["class"].value == \'c_complete\') {
      // Change the class from "complete" to "c_incomplete"
      c_arrElements[i].attributes["class"].value = "c_incomplete";
      }
    }
  }

function cust_generate_start() {
  // Delete the old html file before continuing
  $.post("'.PATH.'ajax/compile_customer_invoices.php", { query_data: "delete_html:"+"'.$delivery_id.'" }, function(data) {
    if (data != "DELETED_HTML") {
      alert ("ERROR C1: "+data+" \r\nPlease try again");
      }
    })
  //get list of all span elements:
  c_arrElements = getElementsByClass("c_incomplete");
  // Set display elements
  document.getElementById("cust_generate_start").style.display = "none"; /* Make the button disappear */
  document.getElementById("load_customer_html").style.display = "none"; /* Hide the html link until regenerated */
  document.getElementById("cust_progress").style.display = "block"; /* Show the progress bar */
  document.getElementById("prod_generate_button").disabled = "true"; /* Disable the other button */
  i = 0;
  }

function compile_customer_invoices() {
  //iterate over the <li> array elements:
  if (i < c_arrElements.length) {
    //check that this is the proper class
    if (c_arrElements[i].attributes["class"].value == \'c_incomplete\') {
      // Get the id of the element (that is the basket number, formatted like: basket_id2147
      var element_id = c_arrElements[i].attributes["id"].value;
      $.post("'.PATH.'ajax/compile_customer_invoices.php", { query_data: ""+element_id+":'.$delivery_id.'" }, function(data) {
        if(data == "GENERATED_INVOICE") {
          var oldHTML = document.getElementById(\'customerList\').innerHTML;
          var c_progress_left = Math.floor (300 * i / c_arrElements.length);
          var c_progress_right = 300 - c_progress_left;
          document.getElementById("c_progress-left").style.width = c_progress_left+"px";
          document.getElementById("c_progress-left").innerHTML = Math.floor (c_progress_left / 3)+"%&nbsp;";
          document.getElementById("c_progress-right").style.width = c_progress_right+"px";
          document.getElementById(element_id).className = "c_complete";
          // If we\'re done with the list, then show the PDF button
          if (i == c_arrElements.length) {
            // And go generate the pdf
            document.getElementById("cust_progress").style.display = "none"; /* Hide the progress bar */
            document.getElementById("load_customer_html").style.display = ""; /* Make html link visible */
            '.(USE_HTMLDOC ? 'cust_generate_pdf()' : '
            document.getElementById("prod_generate_button").disabled = ""; /* Re-enable the other button */
            document.getElementById("cust_generate_start").style.display = ""; /* Bring back the button */
            document.getElementById("cust_html2pdf_message").style.display = ""; /* Hide the html2pdf conversion message */
            ').'
            };
          // Continue cycling through this loop
          compile_customer_invoices ();
          }
        else {
          alert ("ERROR C2: "+data+" \r\nPlease try again");
          }
        });
      }
    i++;
    }
  }

function cust_generate_pdf() {
  document.getElementById("load_customer_pdf").style.display = "none"; /* Hide the pdf link until regenerated */
  document.getElementById("cust_html2pdf_message").style.display = "block"; /* Show the html2pdf conversion message */
  // Delete the old pdf file before continuing
  $.post("'.PATH.'ajax/compile_customer_invoices.php", { query_data: "delete_pdf:"+"'.$delivery_id.'" }, function(data) {
    if (data != "DELETED_PDF") {
      alert ("ERROR C3: "+data+" \r\nPlease try again");
      }
    })
  $.post("'.PATH.'ajax/compile_customer_invoices.php", { query_data: "html2pdf:"+"'.$delivery_id.'" }, function(data) {
    if(data != "HTML2PDF") {
      alert ("ERROR C4: "+data+" \r\nPlease try again");
      }
    document.getElementById("load_customer_pdf").style.display = ""; /* Make pdf link visible */
    document.getElementById("prod_generate_button").disabled = ""; /* Re-enable the other button */
    document.getElementById("cust_generate_start").style.display = ""; /* Bring back the button */
    document.getElementById("cust_html2pdf_message").style.display = ""; /* Hide the html2pdf conversion message */
    })
  }


// PRODUCER FUNCTIONS

function reset_prod_list() {
  p_arrElements = getElementsByClass("p_complete");
  for (i = 0; i < p_arrElements.length; i++) {
    if (p_arrElements[i].attributes["class"].value == \'p_complete\') {
      // Change the class from "complete" to "p_incomplete"
      p_arrElements[i].attributes["class"].value = "p_incomplete";
      }
    }
  }

function prod_generate_start() {
  // Delete the old html file before continuing
  $.post("'.PATH.'ajax/compile_producer_invoices.php", { query_data: "delete_html:"+"'.$delivery_id.'" }, function(data) {
    if (data != "DELETED_HTML") {
      alert ("ERROR P1: "+data+" \r\nPlease try again");
      }
    })
  //get list of all span elements:
  p_arrElements = getElementsByClass("p_incomplete");
  // Set display elements
  document.getElementById("prod_generate_start").style.display = "none"; /* Make the button disappear */
  document.getElementById("load_producer_html").style.display = "none"; /* Hide the html link until regenerated */
  document.getElementById("prod_progress").style.display = "block"; /* Show the progress bar */
  document.getElementById("cust_generate_button").disabled = "true"; /* Disable the other button */
  i = 0;
  }

function compile_producer_invoices() {
  //iterate over the <li> array elements:
  if (i < p_arrElements.length) {
    //check that this is the proper class
    if (p_arrElements[i].attributes["class"].value == \'p_incomplete\') {
      // Get the id of the element (that is the basket number, formatted like: basket_id2147
//      alert ("DATA: "+data);
      var element_id = p_arrElements[i].attributes["id"].value;
      $.post("'.PATH.'ajax/compile_producer_invoices.php", { query_data: ""+element_id+":'.$delivery_id.'" }, function(data) {
        if(data == "GENERATED_INVOICE") {
          var oldHTML = document.getElementById(\'producerList\').innerHTML;
          var p_progress_left = Math.floor (300 * i / p_arrElements.length);
          var p_progress_right = 300 - p_progress_left;
          document.getElementById("p_progress-left").style.width = p_progress_left+"px";
          document.getElementById("p_progress-left").innerHTML = Math.floor (p_progress_left / 3)+"%&nbsp;";
          document.getElementById("p_progress-right").style.width = p_progress_right+"px";
          document.getElementById(element_id).className = "p_complete";
          // If we\'re done with the list, then show the PDF button
          if (i == p_arrElements.length) {
            // And go generate the pdf
            document.getElementById("prod_progress").style.display = "none"; /* Hide the progress bar */
            document.getElementById("load_producer_html").style.display = ""; /* Make html link visible */
            '. (USE_HTMLDOC ? 'prod_generate_pdf()' : '
            document.getElementById("cust_generate_button").disabled = ""; /* Re-enable the other button */
            document.getElementById("prod_generate_start").style.display = ""; /* Bring back the button */
            document.getElementById("prod_html2pdf_message").style.display = ""; /* Hide the html2pdf conversion message */
            ').'
            };
          // Continue cycling through this loop
          compile_producer_invoices ();
          }
        else {
          alert ("ERROR P2: "+data+" \r\nPlease try again");
          }
        });
      }
    i++;
    }
  }

function prod_generate_pdf() {
  document.getElementById("load_producer_pdf").style.display = "none"; /* Hide the pdf link until regenerated */
  document.getElementById("prod_html2pdf_message").style.display = "block"; /* Show the html2pdf conversion message */
  // Delete the old pdf file before continuing
  $.post("'.PATH.'ajax/compile_producer_invoices.php", { query_data: "delete_pdf:"+"'.$delivery_id.'" }, function(data) {
    if (data != "DELETED_PDF") {
      alert ("ERROR P3: "+data+" \r\nPlease try again");
      }
    })
  $.post("'.PATH.'ajax/compile_producer_invoices.php", { query_data: "html2pdf:"+"'.$delivery_id.'" }, function(data) {
    if(data != "HTML2PDF") {
      alert ("ERROR P4: "+data+" \r\nPlease try again");
      }
    document.getElementById("load_producer_pdf").style.display = ""; /* Make pdf link visible */
    document.getElementById("cust_generate_button").disabled = ""; /* Re-enable the other button */
    document.getElementById("prod_generate_start").style.display = ""; /* Bring back the button */
    document.getElementById("prod_html2pdf_message").style.display = ""; /* Hide the html2pdf conversion message */
    })
  }

</script>';
// ' // Just an extra single-quote to make the editor parser happy :-)

$page_specific_css = '
<style type="text/css">
h3 {
  margin: 0px;
  padding: 0px;
  }

blink {
  animation-name: blinker;
  animation-duration: 2s;
  animation-timing-function: linear;
  animation-delay: 0s;
  animation-iteration-count: infinite;
  animation-direction: alternate;
  animation-play-state: running;
  /* Safari and Chrome: */
  -webkit-animation-name: blinker;
  -webkit-animation-duration: 2s;
  -webkit-animation-timing-function: linear;
  -webkit-animation-delay: 0s;
  -webkit-animation-iteration-count: infinite;
  -webkit-animation-direction: alternate;
  -webkit-animation-play-state: running;
  }
@-webkit-keyframes blinker {
  0% {opacity: 1}
  50% {opacity: 0}
}
@keyframes blinker {
  0% {opacity: 1}
  50% {opacity: 0}
}

li.c_complete a, li.p_complete a {
  color: #000;
  margin: 0;
  padding: 0;
  border: 0;
  font-weight:normal;
  text-align:left;
  }

li.c_incomplete a, li.p_incomplete a {
  color: #ddd;
/*  height: 0; */
  margin: 0;
  padding: 0;
  border: 0;
  font-weight:normal;
  text-align:left;
  }

#left-column {
  float:left;
  margin: 0px;
  width: 49%;
  }

#right-column {
  float:right;
  margin: 0px;
  width: 49%;
  }

#customerBox {
  clear:both;
/*  position: relative; */
  margin: auto;
  margin-top:10px;
  width: 80%;
  height:500px;
  overflow:auto;
  background-color: #fff;
  -moz-border-radius: 7px;
/*  -webkit-border-radius: 7px; */
  border: 2px solid #000;
  }

#producerBox {
  clear:both;
  position: relative;
  margin: auto;
  margin-top:10px;
  width: 80%;
  height:500px;
  overflow:auto;
  background-color: #fff;
  -moz-border-radius: 7px;
/*  -webkit-border-radius: 7px; */
  border: 2px solid #000;
  }

input {
  /*display:block;*/
  margin: auto;
  margin-top:10px;
  }

.customerList {
  margin: 0px;
  padding: 0px;
  }

ul {
  list-style-type:none;
  padding-left:5px;
  }

/*
.customerList li {
  margin: 0px 0px 3px 0px;
  cursor: pointer;
  } */

.customerList li:hover {
  background-color: #ccc;
  color:#000;
  }

.producerList {
  margin: 0px;
  padding: 0px;
  }

/*
.producerList li {
  margin: 0px 0px 3px 0px;
  cursor: pointer;
  } */

.producerList li:hover {
  background-color: #ccc;
  color:#000;
  }

#cust_progress, #prod_progress {
  display:none;
  position: relative;
  margin: auto;
  margin-top:10px;
  margin-bottom:36px;
  height:20px;
  border: 2px solid #000;
  width: 301px;
  }

#p_progress-left, #c_progress-left {
  float:left;
  border-right: 1px solid #000;
  width: 0px;
  height:20px;
  background: #ff0;
  text-align:left;
  }

#p_progress-right #c_progress-right {
  float:right;
  border: 0;
  width: 300px;
  height:20px;
  background: #aaa;
  text-align:left;
  }

a:link, a:visited {
  text-decoration:none;
  color:#228;
  }

a:hover {
  text-decoration:underline;
  color:#161;
  }

h1 {
  text-align:center;
  }

.order_nav {
  text-align:center;
  background-color:#edc;
  color:#000;
  width: 25em;
  margin:auto;
  padding: 5px;
  margin-bottom:3em;
  border:1px solid #420;
  }

#cust_generate_start,
#prod_generate_start {
  height:60px;
  text-align:center;
  }

#cust_html_link,
#cust_pdf_link,
#cust_html2pdf_message,
#prod_html_link,
#prod_pdf_link,
#prod_html2pdf_message {
  clear:both;
  width:80%;
  height:30px;
  margin:auto;
  font-size:1.3em;
  text-align:center;
  }

.order_nav order_nav,
#cust_html_link a:hover,
#cust_pdf_link a:hover,
#cust_generate_start a:hover,
#cust_html2pdf_message a:hover,
#prod_html_link a:hover,
#prod_pdf_link a:hover,
#prod_generate_start a:hover,
#prod_html2pdf_message a:hover {
  color:#5d872b;
  text-decoration:underline;
  }

#prod_html2pdf_message,
#cust_html2pdf_message {
  display:none;
  color: #a00;
  font-size:1.3em;
  }

#cust_pdf_link,
#prod_pdf_link {
  display:'.(USE_HTMLDOC ? 'block' : 'none').';
  }

.p_list_pid {
  width:5em;
  float:left;
  text-align:center;
  padding-right:1em;
  font-family:verdana;
  }

.c_list_cid strong,
.p_list_pid strong {
  color:#a22;
  }

.p_list_name {
  padding-left:6em;
  font-family:verdana;
  }

.p_list_header {
  position:relative;
  font-weight:bold;
  text-decoration:underline;
  color:#008;
  }

.c_list_cid {
  width:3em;
  float:left;
  text-align:right;
  padding-right:1em;
  font-family:verdana;
  }

.c_list_name {
  padding-left:4em;
  font-family:verdana;
  }

.c_list_header {
  position:relative;
  font-weight:bold;
  text-decoration:underline;
  color:#008;
  }

</style>';


$prior_delivery_link = ' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';
$next_delivery_link = ' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';
if ($delivery_id > 1)
  {
    $prior_delivery_link = '<a href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.number_format($delivery_id - 1, 0).'">&larr; PRIOR &#151;</a>';
  }
if ($delivery_id < ActiveCycle::delivery_id())
  {
    $next_delivery_link = '<a href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.number_format($delivery_id + 1, 0).'">&#151; NEXT &rarr;</a>';
  }


$content .= '
<h1>Generate Invoices for Delivery #'.$delivery_id.'<br>'.$delivery_date.'</h1>
<p class="order_nav">'.$prior_delivery_link.' &nbsp; &nbsp; OTHER ORDERS &nbsp; &nbsp; '.$next_delivery_link.'</p>';


$customer_output_html = INVOICE_WEB_PATH.'invoices_customers-'.$delivery_id.'.html';
$customer_output_pdf = INVOICE_WEB_PATH.'invoices_customers-'.$delivery_id.'.pdf';


if (! file_exists(INVOICE_FILE_PATH.'invoices_customers-'.$delivery_id.'.html'))
  {
    $cust_view_html = ' style="display:none;"';
  }
if (! file_exists(INVOICE_FILE_PATH.'invoices_customers-'.$delivery_id.'.pdf'))
  {
    $cust_view_pdf = ' style="display:none;"';
  }

$content .= '
<div id="left-column">
  <div id="cust_control">
    <div id="cust_html_link"><a id="load_customer_html" href="'.$customer_output_html.'" target="_blank"'.$cust_view_html.'>View Customer Invoices (HTML)</a></div>
    <div id="cust_pdf_link"><a id="load_customer_pdf" href="'.$customer_output_pdf.'" target="_blank"'.$cust_view_pdf.'>View Customer Invoices (PDF)</a></div>
    <div id="cust_generate_start"><input id="cust_generate_button" type="submit" onClick="reset_cust_list(); cust_generate_start(); compile_customer_invoices();" value="Generate Customer Invoices"></div>
    <div id="cust_html2pdf_message">Converting HTML to PDF... <blink>please wait</blink></div>
    <div id="cust_progress"><div id="c_progress-left"></div><div id="c_progress-right"></div></div>
  </div>
  <div id="customerBox">
    <div class="customerList" id="customerList">
      <ul>
        <li><div class="c_list_header c_list_cid">ID</div><div class="c_list_header c_list_name">Destination Hub: Delcode [Name]</div></a></li>';

$query = '
  SELECT
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.site_id,
    '.NEW_TABLE_SITES.'.site_short,
    '.TABLE_HUBS.'.hub_short
  FROM
    (
      '.NEW_TABLE_BASKETS.',
      '.NEW_TABLE_SITES.'
    )
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.NEW_TABLE_BASKETS.'.member_id = '.TABLE_MEMBER.'.member_id
  LEFT JOIN
    '.TABLE_HUBS.' USING(hub_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id IS NOT NULL
    AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
    AND '.NEW_TABLE_BASKETS.'.site_id = '.NEW_TABLE_SITES.'.site_id
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id
  ORDER BY
    '.TABLE_HUBS.'.hub_short ASC,
    '.NEW_TABLE_SITES.'.site_short ASC,
    '.TABLE_MEMBER.'.last_name ASC,
    '.TABLE_MEMBER.'.first_name ASC';

$result= mysql_query("$query") or die(debug_print ("ERROR: 684039 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while($row = mysql_fetch_array($result))
  {
    $hub_short = $row['hub_short'];
    $basket_id = $row['basket_id'];
    $site_short = $row['site_short'];
    $member_id = $row['member_id'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $sql = '
      SELECT '.NEW_TABLE_BASKETS.'.basket_id
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
      WHERE
        '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
        AND '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($member_id).'"
        AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != quantity
        AND '.NEW_TABLE_PRODUCTS.'.random_weight = "1"
        AND '.NEW_TABLE_BASKET_ITEMS.'.total_weight <= "0"';
    $rs = @mysql_query($sql,$connection) or die(debug_print ("ERROR: 780303 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $qty_need_weight = mysql_num_rows ($rs);
    if ($qty_need_weight == 0)
      {
        $ready_begin = '';
        $ready_end = '';
      }
    else
      {
        $ready_begin = '<strong>';
        $ready_end = '</strong>';
      }
    $content .= '
          <li id="basket_id:'.$basket_id.'" class="c_complete"><a href="show_report.php?type=customer_invoice&amp;delivery_id='.$delivery_id.'&amp;member_id='.$member_id.'" target="_blank"><div class="c_list_cid">'.$ready_begin.$row['member_id'].$ready_end.'</div><div class="c_list_name">'.$hub_short.': '.$site_short.' ['.$last_name.', '.$first_name.']</div></a></li>';
  }

$content .= '
      </ul>
    </div>
  </div>
</div>';

////////////////////////////////////  END CUSTOMER SECTION AND BEGIN PRODUCER SECTION ////////////////////////////////

$producer_output_html = INVOICE_WEB_PATH.'invoices_producers-'.$delivery_id.'.html';
$producer_output_pdf = INVOICE_WEB_PATH.'invoices_producers-'.$delivery_id.'.pdf';

if (! file_exists(INVOICE_FILE_PATH.'invoices_producers-'.$delivery_id.'.html'))
  {
    $prod_view_html = ' style="display:none;"';
  }
if (! file_exists(INVOICE_FILE_PATH.'invoices_producers-'.$delivery_id.'.pdf'))
  {
    $prod_view_pdf = ' style="display:none;"';
  }

$content .= '
<div id="right-column">
  <div id="cust_control">
    <div id="prod_html_link"><a id="load_producer_html" href="'.$producer_output_html.'" target="_blank"'.$prod_view_html.'>View Producer Invoices (HTML)</a></div>
    <div id="prod_pdf_link"><a id="load_producer_pdf" href="'.$producer_output_pdf.'" target="_blank"'.$prod_view_pdf.'>View Producer Invoices (PDF)</a></div>
    <div id="prod_generate_start"><input id="prod_generate_button" type="submit" onClick="reset_prod_list(); prod_generate_start(); compile_producer_invoices();" value="Generate Producer Invoices"></div>
    <div id="prod_html2pdf_message">Converting HTML to PDF... <blink>please wait</blink></div>
    <div id="prod_progress"><div id="p_progress-left"></div><div id="p_progress-right"></div></div>
  </div>
  <div id="producerBox">
    <div class="producerList" id="producerList">
      <ul>
        <li><div class="p_list_header p_list_pid">ID</div><div class="p_list_header p_list_name">Business Name</div></a></li>';

  $sqlp2 = '
    SELECT
      '.TABLE_PRODUCER.'.producer_id,
      '.TABLE_PRODUCER.'.member_id,
      '.TABLE_PRODUCER.'.business_name,
      '.NEW_TABLE_BASKET_ITEMS.'.product_id,
      '.NEW_TABLE_PRODUCTS.'.producer_id,
      '.NEW_TABLE_BASKET_ITEMS.'.product_id,
      '.NEW_TABLE_BASKETS.'.delivery_id,
      '.NEW_TABLE_BASKETS.'.basket_id,
      '.NEW_TABLE_BASKET_ITEMS.'.basket_id
    FROM
      '.NEW_TABLE_BASKET_ITEMS.'
    LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
    LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
    LEFT JOIN '.TABLE_PRODUCER.' USING(producer_id)
    WHERE
      '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
    GROUP BY
      '.TABLE_PRODUCER.'.producer_id
    ORDER BY
      business_name ASC';

$resultp= mysql_query("$sqlp2") or die(debug_print ("ERROR: 897967 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while($row = mysql_fetch_array($resultp))
  {
    $producer_id = $row['producer_id'];
    $business_name = $row['business_name'];
    $sql = '
      SELECT '.NEW_TABLE_BASKETS.'.basket_id
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_BASKETS.' ON '.NEW_TABLE_BASKET_ITEMS.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
      WHERE
        '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
        AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
        AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != quantity
        AND '.NEW_TABLE_PRODUCTS.'.random_weight = "1"
        AND '.NEW_TABLE_BASKET_ITEMS.'.total_weight <= "0"';
    $rs = @mysql_query($sql,$connection) or die(debug_print ("ERROR: 765303 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $qty_need_weight = mysql_num_rows ($rs);
    if ($qty_need_weight == 0)
      {
        $ready_begin = '';
        $ready_end = '';
      }
    else
      {
        $ready_begin = '<strong>';
        $ready_end = '</strong>';
      }
    $content .= '
          <li id="producer_id:'.$producer_id.'" class="p_complete"><a href="show_report.php?type=producer_invoice&amp;producer_id='.$producer_id.'&amp;delivery_id='.$delivery_id.'" target="_blank"><div class="p_list_pid">'.$ready_begin.$producer_id.$ready_end.'</div><div class="p_list_name">'.$business_name.'</div></a></li>';
    //  $invoicep .= prdcrinvoice($producer_id, $delivery_id);
    //  $invoicep .= "\n<HR BREAK>\n";
  }

$content .= '
      </ul>
    </div>
  </div>
</div>
<div style="clear:both;">&nbsp;</div>';


$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Generate Invoices</span>';
$page_title = 'Delivery Cycle Functions: Generate Invoices';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
