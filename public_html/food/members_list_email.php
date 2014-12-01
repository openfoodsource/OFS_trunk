<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin');


$sql = '
  SELECT
    '.TABLE_MEMBER.'.* 
  FROM
    '.TABLE_MEMBER.'
  WHERE
    pending != "1"
    AND membership_discontinued != "1"
  ORDER BY
    membership_date DESC,
    last_name ASC,
    first_name ASC';
$rs = @mysql_query($sql, $connection) or die("Couldn't execute category query.");
$num = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
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
    if ( $email_address )
      {
        $display .= '<a href="mailto:'.$email_address.'">'.$email_address.'</a><br>';
      }
    if ( $email_address_2 )
      {
        $display .= '<a href="mailto:'.$email_address_2.'">'.$email_address_2.'</a><br>';
      }
  }

$content_list = '
  <table width="100%">
    <tr>
      <td align="left">
        <div align="center">
          <h3>'.$num.' Non-pending Members</h3>
        </div>
        '.$display.'
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Member Contact Info</span>';
$page_title = 'Reports: Member Contact Info';
$page_tab = 'member_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

