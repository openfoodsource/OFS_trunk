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
    (
      email_address is null
    OR email_address = ""
    )
    AND no_postal_mail != "1"
    AND membership_discontinued != "1"
  ORDER BY
    last_name ASC,
    first_name ASC';
$rs = @mysqli_query ($connection, $sql) or die (debug_print ("ERROR: 203115 ", array ($sql, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
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
    $home_phone = $row['home_phone'];
    $work_phone = $row['work_phone'];
    $mobile_phone = $row['mobile_phone'];
    $fax = $row['fax'];
    $membership_date = $row['membership_date'];
    $preferred_name = $row['preferred_name'];
    $display .= '<strong>'.$preferred_name.'</strong><br>';
    $display .= $address_line1.'<br>';
    if($address_line2)
      {
        $display .= $address_line2.'<br>';
      }
    $display .= $city.', '.$state.' '.$zip.'<br>';
    $display .= '<br>';
  }

$content_list = '
  <table width="100%">
    <tr>
      <td align="left">
        <div align="center">
          <h3>'.$num.' Members</h3>
        </div>
        '.$display.'
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Members Without Email</span>';
$page_title = 'Reports: Members Without Email';
$page_tab = 'member_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
