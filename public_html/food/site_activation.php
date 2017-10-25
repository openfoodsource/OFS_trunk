<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin');


$action = $_POST['action'];
$content .= '<div style="padding:1em;">';


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                        BEGIN PROCESSING SUBMITTED PAGE                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

if ($action == "Update")
  {

    // Update the sites to turn them on/off.
    $query = '
      SELECT
        site_id,
        inactive
      FROM
        '.NEW_TABLE_SITES.'
      WHERE
        site_type LIKE "%customer%"';
    $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 943290 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_object ($result))
      {
        if ($row->inactive != $_POST[$row->site_id.'_inactive'])
          {
            $query2 = '
              UPDATE
                '.NEW_TABLE_SITES.'
              SET
                inactive = '.mysqli_real_escape_string ($connection, $_POST[$row->site_id.'_inactive']).'
              WHERE
                site_id = "'.mysqli_real_escape_string ($connection, $row->site_id).'"';
            $null = mysqli_query ($connection, $query2) or die (debug_print ("ERROR: 389340 ", array ($query2, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
      }
  }

// Always show the form...
$content .= '
  <div align="center"><h3>Activate Delivery Locations</h3></div>
  <p>Use the following form to change which sites are available.  &quot;Standby&quot; is used for sites that are not available for this order cycle,
  but remain available on the locations page to indicate they are normally in the service area.</p>
  <form action="'.$_SERVER['SCRIPT_NAME'].'" method="POST">';

$query = '
  SELECT
    *
  FROM
    '.NEW_TABLE_SITES.'
  LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
  WHERE
    site_type LIKE "%customer%"
  ORDER BY
    site_short;';
$result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 927489 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$content .= '
    <table id="customer_site_list">
      <tr>
        <th>Delcode ID</th>
        <th>Del Code</th>
        <th>Del Type</th>
        <th>Del Desc.</th>
        <th>Inactive</th>
      </tr>';
while ($row = mysqli_fetch_object ($result))
  {
    if ($row->inactive == 0) // Active site
      {
        $inactive_select = '
          <option value="0" selected>Active</option>
          <option value="1">Suspended</option>
          <option value="2">Standby</option>
          ';
          $inactive_color = '#cfc';
          $select_inactive = 'active';
      }
    elseif ($row->inactive == 1) // Suspended site
      {
        $inactive_select = '
          <option value="0">Active</option>
          <option value="1" selected>Suspended</option>
          <option value="2">Standby</option>
          ';
          $inactive_color = '#fcc';
          $select_inactive = 'suspended';
      }
    elseif ($row->inactive == 2) // Standby -- but okay for signups
      {
        $inactive_select = '
          <option value="0">Active</option>
          <option value="1">Suspended</option>
          <option value="2" selected>Standby</option>
          ';
          $inactive_color = '#ffc';
          $select_inactive = 'standby';
      }
    if ($row->delivery_type == "P") // Order pickup site
      {
        $delivery_type_display = "Pickup";
      }
    elseif ($row->delivery_type == "D") // Delivery choice
      {
        $delivery_type_display = "Delivery";
      }
    $content .= '
      <tr class="'.$select_inactive.'">
        <td>'.$row->site_short.'</td>
        <td>'.$row->site_long.'</td>
        <td>'.$delivery_type_display.'</td>
        <td>'.nl2br ($row->site_description).'</td>
        <td><select name="'.$row->site_id.'_inactive">'.$inactive_select.'</select></td>
      </tr>';
  }

$content .= '
  </table>
  <br>
  <table border="0" width="100%">
    <tr>
      <td width="33%" align="center"><input type="submit" name="action" value="Update"></td>
      <td width="33%" align="center"><input type="reset"></td>
    </tr>
  </table>
  </form>
  <hr>';

$content .= '</div>';

$page_specific_css = '
  <style type="text/css">
  #customer_site_list {
    border:1px;
    background-color:#eee;
    border:1px solid black;
    border-collapse:collapse;
    font-size:80%;
    }
  #customer_site_list tr.active td {
    background-color:#dfd;
    color:#000;
    }
  #customer_site_list tr.suspended td {
    background-color:#ddd;
    color:#a44;
    }
  #customer_site_list tr.standby td {
    background-color:#ffc;
    color:#640;
    }
  #customer_site_list tr th {
    color:#ffc;
    background-color:#468;
    padding:2px;
     }
  #customer_site_list tr td {
    border:1px solid #aaa;
    padding:2px;
    }
  </style>
  ';

$page_title_html = '<span class="title">Route Information</span>';
$page_subtitle_html = '<span class="subtitle">Activate Delivery Locations</span>';
$page_title = 'Route Information: Activate Delivery Locations';
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");?>
