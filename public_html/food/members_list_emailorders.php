<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin,site_admin,member_admin');

$delivery_id = $_GET['delivery_id'];
if (!$delivery_id) $delivery_id = ActiveCycle::delivery_id();

// Initialize with column headers
$home_phone_display = '<span><strong>Home Phone</strong></span><br>';
$mobile_phone_display = '<span><strong>Mobile Phone</strong></span><br>';
$work_phone_display = '<span><strong>Work Phone</strong></span><br>';
$fancy_display = '<strong>Name &lt;E-mail Address&gt;</strong><br>';
$plain_display = '<span><strong>E-mail Address</strong></span><br>';

$sql = '
  SELECT
    '.TABLE_MEMBER.'.*,
    COUNT('.NEW_TABLE_BASKET_ITEMS.'.product_id) AS prod_qty
  FROM
    '.TABLE_MEMBER.'
  LEFT JOIN
    '.NEW_TABLE_BASKETS.' ON '.TABLE_MEMBER.'.member_id = '.NEW_TABLE_BASKETS.'.member_id
  LEFT JOIN
    '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id = '.TABLE_MEMBER.'.member_id
    AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id
  ORDER BY
    last_name ASC,
    first_name ASC';

$rs = @mysql_query($sql, $connection) or die("Couldn't execute query.");
$num = mysql_numrows($rs);
$mail_count = 0;
while ($row = mysql_fetch_array($rs))
  {
    $member_id = $row['member_id'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $business_name = $row['business_name'];
    $email_address = $row['email_address'];
    $email_address_2 = $row['email_address_2'];
    $home_phone = $row['home_phone'];
    $prod_qty = $row['prod_qty'];
    $preferred_name = $row['preferred_name'];

    // Only show if there is an email address and the qty of items ordered is more than zero
    if ($email_address && $prod_qty)
      {
        $home_phone_display .= '<span>'.$home_phone.'</span><br>';
        $mobile_phone_display .= '<span>'.$mobile_phone.'</span><br>';
        $work_phone_display .= '<span>'.$work_phone.'</span><br>';
        $fancy_display .= '<a href="mailto:'.$email_address.'">'.$preferred_name.' &lt;'.$email_address.'&gt;</a><br>';
        $plain_display .= '<span>'.$email_address.'</span><br>';
        $mail_count ++;
      }
  }

$content_list = '
<table width="80%">
  <tr>
    <td align="left">
      <div align="center">
        <h3>Member Ordering for Delivery #'.$delivery_id.': <?echo $mail_count;?> Members</h3>
      </div>
      '.($delivery_id > 1 ? '
      <div style="float:left;border:1px solid #440; background-color:#ffd;padding:3px 20px;">
        <a href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id - 1).'">Get list for prior order</a>
      </div>'
      : '').'
      '.($delivery_id < ActiveCycle::delivery_id() ? '
      <div style="float:right;border:1px solid #440; background-color:#ffd;padding:3px 20px;">
        <a href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id + 1).'">Get list for next order</a>
      </div>'
      : '').'
      <br><br>
      <input type="radio" name="display_type" onClick=\'{document.getElementById("fancy").style.display="none";document.getElementById("plain").style.display="";}\'>Show plain addresses
      <br>
      <input type="radio" name="display_type" onClick=\'{document.getElementById("plain").style.display="none";document.getElementById("fancy").style.display="";}\'>Show fancy addresses
      <br><br>
    <div id="fancy"><div style="float:left";>'.$home_phone_display.'</div><div style="float:left;margin-left:1em;">'.$fancy_display.'</div></div>
    <div id="plain" style="display:none">'.$plain_display.'</div>
  </td></tr>
</table>';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Email List of Members with Orders</span>';
$page_title = 'Delivery Cycle Functions: Email List of Members with Orders';
$page_tab = 'member_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

