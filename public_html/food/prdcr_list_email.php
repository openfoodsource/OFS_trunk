<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,producer_admin');


$sqlp = '
  SELECT
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.member_id,
    '.TABLE_PRODUCER.'.unlisted_producer,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_MEMBER.'.email_address
  FROM
    '.TABLE_PRODUCER.'
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_PRODUCER.'.member_id
  WHERE
    '.TABLE_PRODUCER.'.unlisted_producer = "0"
    AND '.TABLE_MEMBER.'.membership_discontinued != "1"';
$resultp = @mysql_query($sqlp, $connection) or die(debug_print ("ERROR: 214321 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($resultp) )
  {
    $producer_id = $row['producer_id'];
    $business_name = $row['business_name'];
    $email_address = $row['email_address'];
    $email_address_2 = $row['email_address_2'];
    if ( ($business_name_prior < 0) &&! $business_name )
      {
        $business_name_prior = $row['business_name'];
      }
    else
      {
        $business_name_prior = $row['last_name'];
      }
    if ( $business_name_prior != $business_name )
      {
        $current_business_name = $business_name;
        if ( $email_address )
          {
            $display .= '<a href="mailto:'.$email_address.'">'.$email_address.'</a><br>';
          }
        if ( $email_address_2 )
          {
            $display .= '<a href="mailto:'.$email_address_2.'">'.$email_address_2.'</a><br>';
          }
      }
  }

$content_list .= '
<table class="center">
  <tr>
    <td align="left">
      <div align="center">
        <h3>Producer Email List</h3>
      </div>
      '.$display.'
    </td>
  </tr>
</table>';

$page_specific_css .= '
<style type="text/css">
table.center {
  margin:auto;
  }
</style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Producer Email List</span>';
$page_title = 'Reports: Producer Email List';
$page_tab = 'producer_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
