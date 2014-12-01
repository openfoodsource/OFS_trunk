<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin');


$display_route = '
  <table width="100%" class="compact">
   <tr valign="top">
      <td align="left" width="50%">
        <img src="'.DIR_GRAPHICS.'gnome2.png" width="32" height="32" align="left" hspace="2" alt="Route Information"><br>
        <b>Route Information</b>
        <ul class="fancyList1">
          <li><a href="delivery.php">Deliveries and Pickups</a></li>
          <li> <a href="delivery_list_all.php?delivery_id='.ActiveCycle::delivery_id().'">All members with orders on each route</a>
          <li><a href="route_list.php?delivery_id='.ActiveCycle::delivery_id().'&type=pickup">Producer Pick-up List</a></li>
          <li class="last_of_group"><a href="route_list.php?delivery_id='.ActiveCycle::delivery_id().'&type=dropoff">Site Drop-off List</a></li>
          <li><a href="delivery_editroute.php">Edit Route Info</a></li>
          <li class="last_of_group"><a href="site_activation.php">Activate Delivery Locations</a></li>
        </ul>
      </td>
      <td align="left" width="50%">
        <img src="'.DIR_GRAPHICS.'launch.png" width="32" height="32" align="left" hspace="2" alt="Delivery Cycle Functions"><br>
        <b>Delivery Cycle Functions</b>
        <ul class="fancyList1">
          <li><a href="orders_list_withtotals.php?delivery_id='.ActiveCycle::delivery_id().'">Customer orders with totals</a></li>
          <li><a href="producer_list_withtotals.php?delivery_id='.ActiveCycle::delivery_id().'">Producer orders with totals</a></li>
          <li><a href="members_list_emailorders.php?delivery_id='.ActiveCycle::delivery_id().'">Customer Email Addresses this cycle</a></li>
          <li><a href="orders_prdcr_list.php?delivery_id='.ActiveCycle::delivery_id().'">Producers with Customers this Cycle</a></li>
        </ul>
      </td>
    </tr>
  </table>';
  $sqla = '
    SELECT
      rtemgr_member_id,
      admin
    FROM
      '.TABLE_ROUTE.'
    WHERE
      rtemgr_member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
      AND admin != "1"';
$resulta = @mysql_query($sqla, $connection) or die("Couldn't execute query -a.");
$rt_num = mysql_numrows($resulta);

$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Route Manager Panel</span>';
$page_title = 'Route Manager Panel';
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_route.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");?>
