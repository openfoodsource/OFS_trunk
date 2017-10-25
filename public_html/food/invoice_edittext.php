<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');

$message = "";
if ( $_REQUEST['update'] == 'yes' )
  {
    $sqlu = '
      UPDATE
        '.TABLE_ORDER_CYCLES.'
      SET
        msg_all = "'.mysqli_real_escape_string ($connection, $_REQUEST['msg_all']).'",
        msg_bottom = "'.mysqli_real_escape_string ($connection, $_REQUEST['msg_bottom']).'"
      WHERE
      delivery_id >= '.ActiveCycle::delivery_id();
    $resultu = @mysqli_query ($connection, $sqlu) or die (debug_print ("ERROR: 621919 ", array ($sqlu, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $message = ': <font color="#FFFFFF">Messages have been updated</font>';
  }
$sqlmsg = '
  SELECT msg_all,
    delivery_id,
    msg_bottom
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id = '.ActiveCycle::delivery_id();
$resultmsg = @mysqli_query ($connection, $sqlmsg) or die (debug_print ("ERROR: 429229 ", array ($sqlmsg, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysqli_fetch_array ($resultmsg, MYSQLI_ASSOC) )
  {
    $msg_all = $row['msg_all'];
    $msg_bottom = $row['msg_bottom'];
  }

$content .= $font.'
  <h3>Editing Text on the Invoices</h3>
  <p>This will change the message for the current invoice ('.date (DATE_FORMAT_CLOSED, strtotime (ActiveCycle::delivery_date())).') and all future invoices until changed.</p>
  <table width="685" cellpadding="7" cellspacing="2" border="0">
    <tr bgcolor="#AE58DA">
      <td align="left"><b>Message to all Members '.$message.'</b></td>
    </tr>
    <tr>
      <td align="left" bgcolor="#EEEEEE">
        <form action="'.$_SERVER['SCRIPT_NAME'].'" method="POST">
          <b>Appears at the top of all Customer Invoices</b><br>
          <textarea name="msg_all" cols="75" rows="7">'.htmlspecialchars ($msg_all, ENT_QUOTES).'</textarea><br><br>
          <b>Appears at the bottom of all Customer Invoices</b><br>
          <textarea name="msg_bottom" cols="75" rows="7">'.htmlspecialchars ($msg_bottom, ENT_QUOTES).'</textarea><br>
          <input type="hidden" name="update" value="yes">
          <div align="center"><input type="submit" name="submit" value="Submit"></div>
        </form>
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">Admin Maintenance</span>';
$page_subtitle_html = '<span class="subtitle">Edit Invoice Messages</span>';
$page_title = 'Admin Maintenance: Edit Invoice Messages';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
