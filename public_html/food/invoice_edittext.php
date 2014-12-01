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
        msg_all = "'.mysql_real_escape_string ($_REQUEST['msg_all']).'",
        msg_bottom = "'.mysql_real_escape_string ($_REQUEST['msg_bottom']).'"
      WHERE
      delivery_id >= '.ActiveCycle::delivery_id();
    $resultu = @mysql_query($sqlu, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Updating ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
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
$resultmsg = @mysql_query($sqlmsg, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
while ( $row = mysql_fetch_array($resultmsg) )
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
