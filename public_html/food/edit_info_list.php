<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer_admin');


$display .= '
  <style type="text/css">
  td.body { border: 1px solid #aaa; }
  </style>
  <table cellpadding=4 cellspacing=0 border=0 bgcolor="#ffffff">
    <tr bgcolor="#aede86">
      <td><b>Edit Producer Info.</b></td>
      <td><b>Status</b></td>
      <td><b>Business Name</b></td>
    </tr>';

$sqlp = '
  SELECT
    producer_id,
    member_id,
    unlisted_producer,
    business_name
  FROM
    '.TABLE_PRODUCER.'
  GROUP BY
    producer_id
  ORDER BY
    business_name ASC';
$resultp = @mysql_query($sqlp, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$prdcr_count = mysql_numrows($resultp);
while ( $row = mysql_fetch_array($resultp) )
  {
    $producer_id = $row['producer_id'];
    $business_name = $row['business_name'];
    $donotlist_producer = $row['donotlist_producer'];

    if ( $donotlist_producer == "1" )
      {
        $display .= '
          <tr>
            <td class="body" align="center" id="p_'.$producer_id.'"><a href="edit_producer_info.php?producer_id='.$producer_id.'">Edit</a></td>
            <td class="body" align="left" bgcolor="#ffffdd">Unlisted</td>
            <td class="body" align="left" bgcolor="#ffffdd"><b>'.$business_name.'</b></td>
          </tr>';
      }
    elseif ( $donotlist_producer == "2" )
      {
        $display .= '
          <tr>
            <td class="body" align="center" id="p_'.$producer_id.'"><a href="edit_producer_info.php?producer_id='.$producer_id.'">Edit</a></td>
            <td class="body" align="left" bgcolor="#ffdddd">Suspended</td>
            <td class="body" align="left" bgcolor="#ffdddd"><b>'.$business_name.'</b></td>
          </tr>';
      }
    else // ( $donotlist_producer == 0 )
      {
        if ( ($business_name_prior < 0) && ! $business_name )
          {
            $business_name_prior = $row['business_name'];
          }
        if ( $business_name_prior != $business_name )
          {
            $business_name_prior = $business_name;
            $display .= '
              <tr>
                <td class="body" align="center" id="p_'.$producer_id.'"><a href="edit_producer_info.php?producer_id='.$producer_id.'">Edit</a></td>
                <td class="body" align="left" bgcolor="#ddeedd">Listed</td>
                <td class="body" align="left" bgcolor="#ddeedd"><b>'.$business_name.'</b></td>
              </tr>';
          }
      }
  }
$display .= '</table>';

$content_edit = '
  <div align="center">
    <table width="80%">
      <tr><td align="center">
        <div align="center">
          <h3>'.$prdcr_count.' Producers</h3>
        </div>
        '.$display.'
        </td>
      </tr>
    </table>
  </div>';

$page_title_html = '<span class="title">Manage Producers and Products</span>';
$page_subtitle_html = '<span class="subtitle">Edit Producer Info.</span>';
$page_title = 'Manage Producers and Products: Edit Producer Info.';
$page_tab = 'producer_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_edit.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
