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
  ORDER BY
    member_id DESC,
    last_name ASC,
    first_name ASC';

$rs = @mysqli_query ($connection, $sql) or die (debug_print ("ERROR: 715733 ", array ($sql, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$num = mysqli_num_rows ($rs);
while ( $row = mysqli_fetch_array ($rs, MYSQLI_ASSOC) )
  {
    $member_id = $row['member_id'];
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
        '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"
        AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"';
    $rs2 = @mysqli_query ($connection, $sql2) or die (debug_print ("ERROR: 795273 ", array ($sql2, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $num2 = mysqli_num_rows ($rs2);
    while ( $row = mysqli_fetch_array ($rs2, MYSQLI_ASSOC) )
      {
        $basket_id = $row['basket_id'];
      }
    $display .= 'Member ID: '.$member_id.' ('.$member_type.')<br>';
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
          <a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id.'&member_id='.$member_id.'">
          View their current invoice</a><br>';
      }
    $display .= '
      <a href="past_customer_invoices.php?member_id='.$member_id.'">View all previous invoices</a><br><br>';
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
$page_subtitle_html = '<span class="subtitle">Members List (Full Info.)</span>';
$page_title = 'Reports: Member List (Full Info.)';
$page_tab = 'member_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
