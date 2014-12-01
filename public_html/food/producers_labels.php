<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,producer_admin');


function prdcr_contact_info($start, $half)
  {
    global $connection;
    $query = '
      SELECT
        '.TABLE_PRODUCER.'.producer_id,
        '.TABLE_PRODUCER.'.business_name,
        '.TABLE_PRODUCER.'.member_id,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.address_line1,
        '.TABLE_MEMBER.'.address_line2,
        '.TABLE_MEMBER.'.city,
        '.TABLE_MEMBER.'.state,
        '.TABLE_MEMBER.'.zip,
        '.TABLE_PRODUCER.'.unlisted_producer
      FROM
        '.TABLE_PRODUCER.',
        '.TABLE_MEMBER.'
      WHERE
        '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
        AND '.TABLE_PRODUCER.'.unlisted_producer = "0"
        AND '.TABLE_MEMBER.'.membership_discontinued != "1"
      ORDER BY
        '.TABLE_PRODUCER.'.business_name ASC
      LIMIT '.mysql_real_escape_string ($start).', '.mysql_real_escape_string ($half);
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 869302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysql_fetch_array($result) )
      {
        $producer_id = $row['producer_id'];
        $business_name = $row['business_name'];
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $address_line1 = $row['address_line1'];
        $address_line2 = $row['address_line2'];
        $city = $row['city'];
        $state = $row['state'];
        $zip = $row['zip'];
        $display .= $business_name.'</b><br>';
        $display .= $first_name.' '.$last_name.'</b><br>';
        $display .= $address_line1.'<br>';
        if($address_line2)
          {
            $display .= $address_line2.'<br>';
          }
        $display .= $city.', '.$state.' '.$zip.'<br>';
        $display .= '<br>';
      }
    return $display;
  }

$sql = '
  SELECT
    COUNT(producer_id) AS count
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    unlisted_producer = "0"';
$result = mysql_query($sql) or die("Couldn't execute query.");
$row = mysql_fetch_array($result);
$pid_count = $row['count'];
$pid_half = ceil($pid_count/2);

$content_label .= '
<table width="100%" cellspacing="15" cellpadding="1">
  <tr>
    <td colspan="3" align="center">
      <h3>Producer Contact Info for Mailing Labels: '.$pid_count.' Producers</h3>
    </td>
  </tr>
  <tr>
    <td valign="top" align="left" width="50%">'.prdcr_contact_info(0, $pid_half).'</td>
    <td bgcolor="#000000" width="2"></td>
    <td valign="top" align="left" width="50%">'.prdcr_contact_info($pid_half, $pid_count).'</td>
  </tr>
</table>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Producer Labels</span>';
$page_title = 'Reports: Producer Labels';
$page_tab = 'producer_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_label.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
