<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin');

// PROGRAMMING NOTE: This function was originally designed for access by members
// with various auth_types.  Now, with its restriction to auth_type=route_admin,
// it could be some functionality is gone... e.g. "Save changes to this route" - ROYG


$message = "";
if ( $_POST['action'] == "Save changes to this location" )
  {
    // If auth_type is route_admin and not site_admin then do the update
    if (CurrentMember::auth_type('route_admin'))
      {
        $query_values = '
              SET
                site_type = "'.mysql_real_escape_string (implode (',', $_POST['site_type'])).'",
                site_short = "'.mysql_real_escape_string ($_POST['site_short']).'",
                site_long = "'.mysql_real_escape_string ($_POST['site_long']).'",
                delivery_type = "'.mysql_real_escape_string ($_POST['delivery_type']).'",
                site_description = "'.mysql_real_escape_string ($_POST['site_description']).'",
                delivery_charge = "'.mysql_real_escape_string ($_POST['delivery_charge']).'",
                route_id = "'.mysql_real_escape_string ($_POST['route_id']).'",
                hub_id = "'.mysql_real_escape_string ($_POST['hub_id']).'",
                truck_code = "'.mysql_real_escape_string ($_POST['truck_code']).'",
                delivery_postal_code = "'.mysql_real_escape_string ($_POST['delivery_postal_code']).'",
                inactive = "'.mysql_real_escape_string ($_POST['inactive']).'"';
        if ($_POST['site_id'] == 'new')
          {
            $query = '
              INSERT INTO
                '.NEW_TABLE_SITES.
                $query_values;
          }
        else
          {
            $query = '
              UPDATE
                '.NEW_TABLE_SITES.'
                '.$query_values.'
              WHERE
                site_id = "'.mysql_real_escape_string ($_POST['site_id']).'"';
          }
        $result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        $message = ': <font color="#FFFFFF">Delivery Information Updated</font>';
      }
    else
      {
        $message = ': <font color="#FFFFFF">You can only update the route you manage</font>';
      }
  }
elseif ( $_POST['action'] == "Save changes to this route" )
  {
    if ( $_SESSION['member_id'] == $_POST['rtemgr_member_id'] || CurrentMember::auth_type('site_admin') )
      {
        $query_values = '
              SET
                route_name = "'.mysql_real_escape_string ($_POST['route_name']).'",
                rtemgr_member_id = "'.mysql_real_escape_string ($_POST['rtemgr_member_id']).'",
                rtemgr_namecd = "'.mysql_real_escape_string ($_POST['rtemgr_namecd']).'",
                route_desc = "'.mysql_real_escape_string ($_POST['route_desc']).'",
                hub_id = "'.mysql_real_escape_string ($_POST['hub_id']).'"';
        if ($_POST['route_id'] == 'new')
          {
            $query = '
              INSERT INTO
                '.TABLE_ROUTE.
                $query_values;
          }
        else
          {
            $query = '
              UPDATE
                '.TABLE_ROUTE.
                $query_values.'
              WHERE
                route_id = "'.mysql_real_escape_string ($_POST['route_id']).'"';;
          }    
        $result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        $message = ': <font color="#FFFFFF">Route Information Updated</font>';
      }
    else
      {
        $message = ': <font color="#FFFFFF">You can only update the route you manage</font>';
      }
  }

// Set up an array of existing routes
$query = '
  SELECT
    route_id,
    route_name
  FROM
    '.TABLE_ROUTE;
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 768023 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$routes_array = array ();
while (($row = mysql_fetch_array($result)))
  {
    $routes_array[$row['route_id']] = $row['route_name'];
  }
// Set up an array of existing hubs
$query = '
  SELECT
    hub_id,
    hub_short,
    hub_long
  FROM
    '.TABLE_HUBS;
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 683021 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$hubs_array = array ();
while (($row = mysql_fetch_array($result)))
  {
    $hubs_array[$row['hub_id']] = $row['hub_short'].': '.$row['hub_long'];
  }
// Now cycle through the existing routes
$sqlr = '
  SELECT
    '.TABLE_ROUTE.'.route_id,
    '.TABLE_ROUTE.'.route_name,
    '.TABLE_ROUTE.'.rtemgr_member_id,
    '.TABLE_ROUTE.'.rtemgr_namecd,
    '.TABLE_ROUTE.'.route_desc,
    '.TABLE_ROUTE.'.admin,
    '.TABLE_ROUTE.'.hub_id,
    '.TABLE_ROUTE.'.inactive,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name_2,
    '.TABLE_MEMBER.'.last_name_2,
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.email_address_2,
    (SELECT COUNT(site_id) FROM '.NEW_TABLE_SITES.' WHERE '.NEW_TABLE_SITES.'.route_id = '.TABLE_ROUTE.'.route_id) AS number_of_sites
  FROM
    '.TABLE_ROUTE.'
  LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
  LEFT JOIN '.TABLE_MEMBER.' ON rtemgr_member_id = member_id
  GROUP BY
    '.TABLE_ROUTE.'.route_id
  ORDER BY
    '.TABLE_ROUTE.'.route_name ASC';
$rsr = @mysql_query($sqlr, $connection) or die(debug_print ("ERROR: 678230 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// Force one extra iteration with null information for the purpose of displaying a blank form
while (($row = mysql_fetch_array($rsr)) || $count++ < 1)
  {
    $route_id = $row['route_id'];
    $route_name = $row['route_name'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $email_address = $row['email_address'];
    $email_address_2 = $row['email_address_2'];
    $rtemgr_member_id = $row['rtemgr_member_id'];
    $rtemgr_namecd = $row['rtemgr_namecd'];
    $route_desc = $row['route_desc'];
    $number_of_sites = $row['number_of_sites'];
    // If this is the extra iteration, then change some variables
    if ($count == 1)
      {
        $route_id = 'new';
        $route_name = 'New';
        $site_id = 'new';
      }
    // Check if the route manager is the member's first (name 1) or second (name 2) person on the account
    if ( $rtemgr_namecd == 'F' )
      {
        $route_manager = '<b>'.$first_name.' '.$last_name.'</b><br><a href="mailto:'.$email_address.'">'.$email_address.'</a>';
      }
    elseif ( $rtemgr_namecd == 'S' )
      {
        if ( $email_address_2 )
          {
            $route_manager = '<b>'.$first_name_2.' '.$last_name_2.'</b><br><a href="mailto:'.$email_address_2.'">'.$email_address_2.'</a>';
          }
        else
          {
            $route_manager = '<b>'.$first_name_2.' '.$last_name_2.'</b><br><a href="mailto:'.$email_address.'">'.$email_address.'</a>';
          }
      }
    elseif ( $rtemgr_namecd == 'B' )
      {
        if ( $email_address_2 )
          {
            $route_manager = '
              <b>'.$first_name.' '.$last_name.'</b><br><a href="mailto:'.$email_address.'">'.$email_address.'</a><br>
              <b>'.$first_name_2.' '.$last_name_2.'</b><br><a href="mailto:'.$email_address_2.'">'.$email_address_2.'</a>';
          }
        else
          {
            $route_manager = '
              <b>'.$first_name.' '.$last_name.'</b><br>
              <b>'.$first_name_2.' '.$last_name_2.'</b><br>
              <a href="mailto:'.$email_address.'">'.$email_address.'</a><br>';
          }
      }
    $quick_links .= '<a class="quicklink" href="#'.$route_id.'">'.$route_name.'</a>';
    $rtemgr_namecd_select = '
      <select name="rtemgr_namecd">
        <option value="F"'.($rtemgr_namecd == 'F' ? ' selected' : '').'>'.$first_name.' '.$last_name.'</option>
        <option value="S"'.($rtemgr_namecd == 'S' ? ' selected' : '').'>'.$first_name_2.' '.$last_name_2.'</option>
        <option value="B"'.($rtemgr_namecd == 'B' ? ' selected' : '').'>'.$first_name.' and '.$first_name_2.'</option>
      </select>';
    $hub_id_select = '
      <select name="hub_id">';
    foreach ($hubs_array as $id => $name)
      {
        $hub_id_select .= '
        <option'.($hub_id == $id ? ' selected' : '').' value="'.$id.'">'.$name.'</option>';
      }
    $hub_id_select .= '
      </select>';
    $display .= '
      <tr>
        <td>
          <form action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
          <table class="route_info'.($route_id == 'new' ? ' new' : '').'">
            <tr class="route_header" id="'.$route_id.'">
              <td>
                '.($count ? 'Add New Route'
                          : 'Route #'.$route_id.
                            '').'
              </td>
              <td colspan="2">
                '.($count ? ''
                          : '<input type="text" name="route_name" value="'.htmlspecialchars($route_name, ENT_QUOTES).'">').'
              </td>
            </tr>';
    $display .= '
            <tr>
              <td colspan="2">
                <b>Route Manager:</b>
              </td>
              <td rowspan="4">
                <textarea name="route_desc" cols="53" rows="3">'.htmlspecialchars($route_desc, ENT_QUOTES).'</textarea><br>
                <input type="hidden" name="route_id" value="'.$route_id.'">
                <input type="hidden" name="rtemgr_member_id" value="'.$rtemgr_member_id.'">
              </td>
            </tr>
            <tr>
              <td>Member ID:</td>
              <td><input size="4" type="text" name="rtemgr_member_id" value="'.htmlspecialchars($rtemgr_member_id, ENT_QUOTES).'"></td>
            </tr>
            <tr>
              <td>Member Name:</td>
              <td>'.$rtemgr_namecd_select.'</td>
            </tr>
            <tr>
              <td>Hub ID:</td>
              <td>'.$hub_id_select.'</td>
            </tr>
<!-- "inactive" is not currently used for routes
            <tr>
              <td>Inactive:</td>
              <td>'.$rtemgr_namecd_select.'</td>
            </tr>
-->
            <tr>
              <td colspan="3">
                <div style="float:left;padding:0.75em;">[<a class="show_hide_link" onclick="jQuery(\'#sites_on_route_'.$route_id.'\').toggleClass(\'hidden\')">Show/hide '.$number_of_sites.' sites on &ldquo;'.$route_name.'&rdquo; route</a>]</div>
                <input class="update_button" type="submit" name="action" value="Save changes to this route">
              </td>
            </tr>
          </table>
          </form>
        </td>
      </tr>';
    $sqlr2 = '
      SELECT
        '.NEW_TABLE_SITES.'.site_id,
        '.NEW_TABLE_SITES.'.site_type,
        '.NEW_TABLE_SITES.'.site_short,
        '.NEW_TABLE_SITES.'.site_long,
        '.NEW_TABLE_SITES.'.delivery_type,
        '.NEW_TABLE_SITES.'.site_description,
        '.NEW_TABLE_SITES.'.delivery_charge,
        '.NEW_TABLE_SITES.'.route_id,
        '.NEW_TABLE_SITES.'.hub_id,
        '.NEW_TABLE_SITES.'.truck_code,
        '.NEW_TABLE_SITES.'.delivery_postal_code,
        '.NEW_TABLE_SITES.'.inactive,
        '.TABLE_HUBS.'.hub_short
      FROM
        '.NEW_TABLE_SITES.'
      LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
      WHERE
        route_id = "'.mysql_real_escape_string ($route_id).'"
      ORDER BY
        delivery_type ASC,
        site_long ASC';
    $rsr2 = @mysql_query($sqlr2, $connection) or die(debug_print ("ERROR: 896792 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $num_del = mysql_numrows($rsr2);
    $display .= '
      <tr>
        <td colspan="2">
          <table id="sites_on_route_'.$route_id.'" class="hidden site_info'.($route_id == 'new' ? ' new' : '').'">';
    while (($row = mysql_fetch_array($rsr2)) || $route_id == 'new')
      {
        $site_id = $row['site_id'];
        if ($route_id == 'new') $site_id = 'new';
        $site_type = explode (',', $row['site_type']);
        $site_short = $row['site_short'];
        $site_long = $row['site_long'];
        $delivery_type = $row['delivery_type'];
        $site_description = $row['site_description'];
        $delivery_charge = number_format($row['delivery_charge'], 2);
        $route_id = $row['route_id'];
        $hub_id = $row['hub_id'];
        $truck_code = $row['truck_code'];
        $delivery_postal_code = $row['delivery_postal_code'];
        $inactive = $row['inactive'];
        $hub_short = $row['hub_short'];
        $route_id_select = '
          <select name="route_id">';
        foreach ($routes_array as $id => $name)
          {
            $route_id_select .= '
            <option'.($route_id == $id ? ' selected' : '').' value="'.$id.'">'.$name.'</option>';
          }
        $route_id_select .= '
          </select>';
        $site_type_select = '
          <select name="site_type[]" size="3" multiple>
            <option'.(in_array ('producer', $site_type) ? ' selected' : '').' value="producer">Producer</option>
            <option'.(in_array ('institution', $site_type) ? ' selected' : '').' value="institution">Institution</option>
            <option'.(in_array ('customer', $site_type) ? ' selected' : '').' value="customer">Customer</option>
          </select>';
        $delivery_type_select = '
          <select name="delivery_type">
            <option value="P"'.($delivery_type == 'P' ? ' selected' : '').'>P (Pickup)</option>
            <option value="D"'.($delivery_type == 'D' ? ' selected' : '').'>D (Home or Work Delivery)</option>
          </select>';
        $inactive_select = '
          <select name="inactive">
            <option value="0"'.($inactive == '0' ? ' selected' : '').'>Active</option>
            <option value="1"'.($inactive == '1' ? ' selected' : '').'>Suspended</option>
            <option value="2"'.($inactive == '2' ? ' selected' : '').'>Standby</option>
          </select>';
        $display .= '
            <form action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
            <tr class="site_header">
              <td>'.($site_id == 'new' ? 'Add New Site' : 'Site #'.htmlspecialchars($site_id, ENT_QUOTES)).'</td>
              <td colspan="2">
                <input type="hidden" name="site_id" value="'.htmlspecialchars($site_id, ENT_QUOTES).'">
                <input size="10" type="text" name="site_short" value="'.htmlspecialchars($site_short, ENT_QUOTES).'">
                <input type="text" name="site_long" value="'.htmlspecialchars($site_long, ENT_QUOTES).'">
              </td>
            </tr>
            <tr>
              <td>Site Type:</td>
              <td>'.$site_type_select.'</td>
              <td rowspan="7">
                <textarea name="site_description" cols="53" rows="8">'.htmlspecialchars($site_description, ENT_QUOTES).'</textarea>
              </td>
            </tr>
            <tr>
              <td>Postal Code:</td>
              <td><input type="text" name="delivery_postal_code" value="'.htmlspecialchars($delivery_postal_code, ENT_QUOTES).'"></td>
            </tr>
            <tr>
              <td>Site Status:</td>
              <td>'.$inactive_select.'</td>
            </tr>
            <tr>
              <td>Delivery Type:</td>
              <td>'.$delivery_type_select.'</td>
            </tr>
            <tr>
              <td>Route:</td>
              <td>'.$route_id_select.'</td>
            </tr>
            <tr>
              <td>Truck Code:</td>
              <td><input type="text" name="truck_code" value="'.htmlspecialchars($truck_code, ENT_QUOTES).'"></td>
            </tr>
            <tr>
              <td>Delivery Charge:</td>
              <td>$ <input type="text" name="delivery_charge" value="'.htmlspecialchars($delivery_charge, ENT_QUOTES).'"></td>
            </tr>
            <tr>
              <td colspan="3">
                <input type="hidden" name="site_id" value="'.$site_id.'">
                <input type="hidden" name="rtemgr_member_id" value="'.$rtemgr_member_id.'">
                <input class="update_button" type="submit" name="action" value="Save changes to this location">
              </td>
            </tr>
          </form>';
      }
    $display .= '
          </table>
        </td>
      </tr>';
  }

$content_edit = '
<div align="center">
  <table style="width:685px; border-collapse:collapse;">
    <tr class="info">
      <td colspan="2" align="left"><b>Delivery and Pick up Route Information</b> '.$message.'</td>
    </tr>
    <tr>
      <td colspan="2" align="left" bgcolor="#EEEEEE">
        <ul>
          View <a href="'.LOCATIONS_PAGE.'" target="_blank">the full public list of locations</a>.<br>
          View <a href="delivery.php">route information for this delivery cycle</a>.
        </ul>
        <b>Quick Links to Routes:</b><br>
        <div align="center">'.$quick_links.'</div>
      </td>
    </tr>
    <tr>
      <td colspan="2"><hr></td>
    </tr>
    '.$display.'
  </table>
</div>';

$page_specific_css = '
  <style type="text/css">
    .show_hide_link {
      cursor:pointer;
      }
    .update_button {
      float:right;
      padding:0.3em 1em !important;
      margin:0.75em;
      }
    .site_info tr td {
      background-color:#efd;
      }
    .site_info tr.site_header td {
      background-color: #684;
      color:#ffd;
      }
    .route_info tr td {
       background-color:#def;
       }
    .route_info tr.route_header td {
       background-color:#57a;
      color:#ffd;
       }
    .quicklink {
      margin-left:1em;
      }
    .info {
      background-color:#ccc;
      }
    .new {
      color:#840;
      font-style:italic;
      }
    .hidden {
      display:none;
  </style>';

$page_title_html = '<span class="title">Route Information</span>';
$page_subtitle_html = '<span class="subtitle">Edit Route Info.</span>';
$page_title = 'Route Information: Edit Route Info.';
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_edit.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
