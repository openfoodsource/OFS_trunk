<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');

if (CurrentMember::auth_type('institution'))
  {
    $select_institution_sites = '
      OR site_type = "institution"';
  }
else
  {
    $select_institution_sites = '';
  }
$sqlr = '
  SELECT
    route_id,
    route_name,
    route_desc
  FROM
    '.TABLE_ROUTE.'
  ORDER BY
    route_name ASC';
$rsr = @mysql_query($sqlr,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($rsr) )
  {
    $route_id = $row['route_id'];
    $route_name = $row['route_name'];
    $route_desc = $row['route_desc'];
    $sqlr2 = '
      SELECT
        site_id,
        site_short,
        site_long,
        site_type,
        site_description,
        delivery_charge,
        '.NEW_TABLE_SITES.'.hub_id,
        hub_short
      FROM
        '.NEW_TABLE_SITES.'
      LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
      WHERE
        route_id = '.mysql_real_escape_string ($route_id).'
        AND inactive != 1
        AND (site_type = "customer"'.$select_institution_sites.')
      GROUP BY
        site_id
      ORDER BY
        site_long ASC';
    $rsr2 = @mysql_query($sqlr2,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    $num_del = mysql_numrows($rsr2);
    while ( $row = mysql_fetch_array($rsr2) )
      {
        $site_id = $row['site_id'];
        $site_long = $row['site_long'];
        $site_type = $row['site_type'];
        $site_description = $row['site_description'];
        $delivery_charge = $row['delivery_charge'];
        $transcharge = $row['transcharge'];
        $hub_id = $row['hub_id'];
        $hub_short = $row['hub_short'];
        $quicklinks .= '
          <li> '.$route_name.': <a href="#'.$site_id.'">'.$site_long.'</a></li>';
        if ($route_id != $route_id_prev )
          {
            $display .= '
              <tr>
                <td align="left" colspan="3" bgcolor="#edf3fc" id="'.$site_id.'">
                  <font size="5"><b>Route: '.$route_name.'</b></font><br>(Hub: '.$hub_short.') '.$route_desc.'
                </td>
              </tr>';
          }
        //$display_charge .= "Transportation Charge: \$".number_format($transcharge, 2)."";
        if ( $delivery_charge )
          {
            $display_charge .= "Delivery Charge: \$".number_format($delivery_charge, 2)."";
          }
        $display .= '
              <tr>
                <td>'.(strpos (' '.$site_type, 'institution') > 0 ? '&lowast;' : '').(strpos (' '.$site_type, 'customer') > 0 ? '&bull;' : '').'</td>
                <td align="left" valign=top><a name="'.$site_id.'"></a><font size=4><b>'.$site_long.'</b></font></td>
                <td>'.$display_charge.' </td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td align="left" valign=top colspan=2>'.nl2br ($site_description).'<br><br></td>
              </tr>';
        $display_charge = '';
        $route_id_prev = $route_id;
      }
    $display .= '
              <tr>
                <td><br></td>
              </tr>';
  }
$display_block = '
<table bgcolor="#ffffff" cellspacing="0" cellpadding="2" border="0" width="90%">
  <tr>
    <td colspan="3">
      Note: If you don&rsquo;t see your town listed here, please contact <a href="mailto:'.GENERAL_EMAIL.'">'.GENERAL_EMAIL.'</a>.
      We are adding new routes all the time and if there is interest in a particular location, we may be able to add it.'.
      (CurrentMember::auth_type('institution') ? ' Sites are marked &lowast; for institution/wholesale deliveries and &bull; for retail deliveries.' : '').'
    </td>
  </tr>
  <tr>
    <td colspan="3">
      Quick Links:
      <ul>
        '.$quicklinks.'
      </ul>
    </td>
  </tr>
    '.$display.'
</table>
';

$page_title_html = '<span class="title">'.SITE_NAME.'</span>';
$page_subtitle_html = '<span class="subtitle">Pickup and Delivery Locations</span>';
$page_title = 'Pickup and Delivery Locations';
$page_tab = '';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_block.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");?>
