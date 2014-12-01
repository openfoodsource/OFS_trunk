<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin');

$delivery_id = ActiveCycle::delivery_id();
$sql = '
  SELECT
    '.TABLE_MEMBER.'.*,
    '.TABLE_MEMBERSHIP_TYPES.'.membership_class
  FROM
    '.TABLE_MEMBER.'
  LEFT JOIN '.TABLE_MEMBERSHIP_TYPES.' on '.TABLE_MEMBER.'.membership_type_id = '.TABLE_MEMBERSHIP_TYPES.'.membership_type_id
  WHERE
    '.TABLE_MEMBER.'.pending = "0"
    AND '.TABLE_MEMBER.'.membership_discontinued != "1"
    AND '.TABLE_MEMBER.'.auth_type LIKE "%institution%"
  ORDER BY
    member_id DESC,
    last_name ASC,
    first_name ASC';

$rs = @mysql_query($sql, $connection) or die(debug_print ("ERROR: 785033 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $member_id = $row['member_id'];
    $username = $row['username'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $business_name = $row['business_name'];
    $address_line1 = $row['address_line1'];
    $address_line2 = $row['address_line2'];
    $city = $row['city'];
    $state = $row['state'];
    $zip = $row['zip'];
    $email_address = $row['email_address'];
    $email_address_2 = $row['email_address_2'];
    $home_phone = $row['home_phone'];
    $work_phone = $row['work_phone'];
    $mobile_phone = $row['mobile_phone'];
    $fax = $row['fax'];
    $membership_date = $row['membership_date'];
    $member_type = $row['membership_class'];
    $tax_exempt = $row['mem_taxexempt'];
    $preferred_name = $row['preferred_name'];
    $basket_id = '';
    $sql2 = '
      SELECT
        '.NEW_TABLE_BASKETS.'.member_id,
        '.NEW_TABLE_BASKETS.'.delivery_id,
        '.NEW_TABLE_BASKETS.'.basket_id
      FROM
        '.NEW_TABLE_BASKETS.'
      WHERE
        '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($member_id).'"
        AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"';
    $rs2 = @mysql_query($sql2, $connection) or die(debug_print ("ERROR: 785033 ", array ($sql2,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $num2 = mysql_numrows($rs2);
    while ( $row = mysql_fetch_array($rs2) )
      {
        $basket_id = $row['basket_id'];
      }
    if($tax_exempt) { $display .= '<div style="border: 2px black solid;background-color: beige; padding: 5px; width: 500px;">
      <div style="float: right; padding-right: 10px;"><h3>Wholesale Member</h3></div>';
    }
    $display .= 'Member ID: '.$member_id.' ('.$member_type.')<br>';
    $display .= 'User ID: ' .$username. '<br>';
    $display .= 'Membership Date: '.$membership_date.'<br>';
    $display .= '<b>'.$preferred_name.'</b><br>';
    $display .= $address_line1.'<br>';
    if($address_line2)
      {
        $display .= $address_line2.'<br>';
      }
    $display .= "$city, $state $zip<br>";
    if ( $email_address )
      {
        $display .= '<a href="mailto:'.$email_address.'">'.$email_address.'</a><br>';
      }
    if ( $email_address_2 )
      {
        $display .= '<a href="mailto:'.$email_address_2.'">'.$email_address_2.'</a><br>';
      }
    if ( $home_phone )
      {
        $display .= $home_phone.' (home)<br>';
      }
    if ( $work_phone )
      {
        $display .= $work_phone.' (work)<br>';
      }
    if ( $mobile_phone )
      {
        $display .= $mobile_phone.' (cell)<br>';
      }
    if ( $fax )
      {
        $display .= $fax.' (fax)<br>';
      }
    if ( $basket_id )
      {
        $display .= '
          <b><a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id.'&member_id='.$member_id.'">
          View their current invoice</a></b><br>';
      }
    $display .= '
      <a href="past_customer_invoices.php?member_id='.$member_id.'">View all previous invoices</a><br><br>';

    if($tax_exempt) { $display .= '</div>'; }
  }

$content_list = '
  <table width="100%">
    <tr>
      <td align="left">
        <div align="center">
          <h3>'.$num.' Members (listed newest first)</h3>
        </div>
        '.$display.'
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Producers, Businesses and Other Organizations Contact Info</span>';
$page_title = 'Reports: Producers, Businesses and Other Organizations Contact Info';
$page_tab = 'member_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
