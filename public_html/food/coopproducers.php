<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,producer_admin');

function prdcr_contact_info($start, $half)
  {
    global $connection;
    $sqlp = '
      SELECT
        '.TABLE_PRODUCER.'.producer_id,
        '.TABLE_PRODUCER.'.member_id,
        '.TABLE_PRODUCER.'.business_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.address_line1,
        '.TABLE_MEMBER.'.address_line2,
        '.TABLE_MEMBER.'.city,
        '.TABLE_MEMBER.'.state,
        '.TABLE_MEMBER.'.zip,
        '.TABLE_MEMBER.'.email_address,
        '.TABLE_MEMBER.'.email_address_2,
        '.TABLE_MEMBER.'.home_phone,
        '.TABLE_MEMBER.'.work_phone,
        '.TABLE_MEMBER.'.mobile_phone,
        '.TABLE_MEMBER.'.fax,
        '.TABLE_MEMBER.'.toll_free,
        '.TABLE_MEMBER.'.home_page,
        '.TABLE_MEMBER.'.membership_date,
        '.TABLE_PRODUCER.'.unlisted_producer
      FROM
        '.TABLE_PRODUCER.'
      LEFT JOIN
        '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_PRODUCER.'.member_id
      WHERE
        '.TABLE_PRODUCER.'.unlisted_producer != 2
        AND '.TABLE_MEMBER.'.membership_discontinued != 1
      ORDER BY
        '.TABLE_PRODUCER.'.business_name ASC
      LIMIT '.mysqli_real_escape_string ($connection, $start).', '.mysqli_real_escape_string ($connection, $half).'';
    $resultp = @mysqli_query ($connection, $sqlp) or die (debug_print ("ERROR: 572929 ", array ($sqlp, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysqli_fetch_array ($resultp, MYSQLI_ASSOC) )
      {
        $producer_id = $row['producer_id'];
        $business_name = $row['business_name'];
        $unlisted_producer = $row['unlisted_producer'];
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
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
        $toll_free = $row['toll_free'];
        $home_page = $row['home_page'];
        $membership_date = $row['membership_date'];
        $display .= '<div class="'.($unlisted_producer == 0 ? 'listed' : 'unlisted').'">';
        $display .= $business_name.'<br />';
        $display .= $first_name.' '.$last_name.'<br />';
        $display .= $address_line1.'<br />';
        if( $address_line2 )
          {
            $display .= $address_line2.'<br />';
          }
        $display .= $city.', '.$state.' '.$zip.'<br />';
        if ( $email_address )
          {
            $display .= '<a href="mailto:'.$email_address.'">'.$email_address.'</a><br />';
          }
        if ( $email_address_2 )
          {
            $display .= '<a href="mailto:'.$email_address_2.'">'.$email_address_2.'</a><br />';
          }
        if ( $home_phone )
          {
            $display .= $home_phone.' (home)<br />';
          }
        if ( $work_phone )
          {
            $display .= $work_phone.' (work)<br />';
          }
        if ( $mobile_phone )
          {
            $display .= $mobile_phone.' (cell)<br />';
          }
        if ( $fax )
          {
            $display .= $fax.' (fax)<br />';
          }
        if ( $toll_free )
          {
            $display .= $toll_free.' (toll free)<br />';
          }
        if ( $home_page )
          {
            $display .= $home_page.'<br />';
          }
        $year = substr ($membership_date, 0, 4);
        $month = substr ($membership_date, 5, 2);
        $day = substr ($membership_date, 8);
        $member_since = date('F j, Y',mktime(0, 0, 0, $month, $day, $year));
        $display .= 'Member since '.$member_since.'<br />';
        $display .= '<br /></div>';
      }
    return $display;
  }
$query = '
  SELECT
    COUNT(producer_id) AS count
  FROM
    '.TABLE_PRODUCER.'
  LEFT JOIN
    '.TABLE_MEMBER.' USING(member_id)
  WHERE
    unlisted_producer != 2
    AND membership_discontinued != 1';
$result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 427857 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
$pid_count = $row['count'];
$pid_half = ceil ($pid_count / 2);
$content_list = '
<table class="center">
  <tr>
    <td colspan="2" align="center">
      <h3>'.$pid_count.' Producers</h3>
      This list contains <span class="listed">listed</span> and <span class="unlisted">unlisted</span> (but not suspended) producers.
      <br />Click here for <a href="producer_list.php"><b>Further details about each producer</b></a>
      <br />Contact us at <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> if your contact information needs to be updated.
      <br /><br />
    </td>
  </tr>
  <tr>
    <td valign="top" align="left">
      '.prdcr_contact_info (0, $pid_half).'
    </td>
    <td valign="top" align="left">
      '.prdcr_contact_info ($pid_half, $pid_count).'
    </td>
  </tr>
</table>';
$page_specific_css .= '
  table.center {
    margin:auto;
    }
  .listed,
  .listed a {
    color:#000;
    }
  .unlisted,
  .unlisted a {
    color:#888;
    }';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Producer Contact Info.</span>';
$page_title = 'Reports: Producer Contact Info.';
$page_tab = 'producer_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
