<?php

include_once 'general_functions.php'; // just in case it got missed from the base page
include_once ('wordpress_utilities.php');
$content_header = '';
$google_analytics = '';
$panel_member_menu = '';
$panel_shopping_menu = '';
$panel_producer_menu = '';
$panel_route_admin_menu = '';
$panel_producer_admin_menu = '';
$panel_member_admin_menu = '';
$panel_cashier_menu = '';
$panel_admin_menu = '';
$logout_menu = '';
$basket_menu = '';
$login_menu = '';
$header_title = '';

// Set $google_analytics
if (strlen (GOOGLE_ANALYTICS_TRACKING_ID) > 0)
  include_once (FILE_PATH.PATH.'template_analyticstracking.php');

// Set $favicon
if (FAVICON != '')
  $favicon = '
    <link rel="shortcut icon" href="'.FAVICON.'" type="image/x-icon">';
if (SHOW_HEADER_LOGO)
  $header_title .= '<img id="header_logo" src="'.DIR_GRAPHICS.'logo.jpg" border="0" alt="'.SITE_NAME.'">';
if (SHOW_HEADER_SITENAME)
  $header_title .= '<h1 id="header_site_name">'.SITE_NAME.'</h1>';


// // Site down processing -- not yet implemented
// $site_is_down = false;
// $warn_now = false;
// $site_down_at_time = '2012-01-18 16:30:00';
// $down_time_duration = 40 * 3600; // hours * sec/hr
// $down_time_warning = 6 * 3600; // hours * sec/hr
// $site_down_message = '
//   <div style="border:2px solid #800;width:50%;text-align:center;color:#800;float:right;background-color:#fff;padding:1em;position:absolute;right:10px;top:10px;opacity:0.7;filter:alpha(opacity=70);">
//   <h2>NOTE<br>The site will be going down for maintenance '.date('l, F j \a\t g:i a', strtotime($site_down_at_time)).' and may be down until the next order cycle opens.</h2>
//   </div>';
// if (time() > strtotime($site_down_at_time) && time() < strtotime($site_down_at_time) + $down_time_duration) $site_is_down = true;
// if (time() > strtotime($site_down_at_time) - $down_time_warning && time() < strtotime($site_down_at_time) + $down_time_duration) $warn_now = true;

// Check if the member is logged in
if ($_SESSION['member_id'])
  {
    // Get basket information, but don't re-query if we already have it
    if (! isset ($basket_quantity) && ActiveCycle::delivery_id())
      {
        $query = '
          SELECT
            COUNT(product_id) AS basket_quantity,
            '.NEW_TABLE_BASKET_ITEMS.'.basket_id
          FROM
            '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN '.NEW_TABLE_BASKETS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
          WHERE
            '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
            AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string (ActiveCycle::delivery_id()).'
          GROUP BY
            '.NEW_TABLE_BASKETS.'.member_id';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 780934 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $basket_quantity = 0;
        if ($row = mysql_fetch_object($result))
          {
            $basket_quantity = $row->basket_quantity;
            $basket_id = $row->basket_id;
          }
      }
    // Prepare for membership renewals
    // Do we need to post membership changes?
    // This function is also on the panel_members.php so it will execute before the page loads
    // It needs to be in the header (here) to process for every other page it might be called from.
    if (isset ($_POST['update_membership']) && $_POST['update_membership'] == 'true')
      {
        include_once ('func.check_membership.php');
        renew_membership ($_SESSION['member_id'], $_POST['membership_type_id']);
        // Now update our session membership values
        $membership_info = get_membership_info ($_SESSION['member_id']);
        $_SESSION['renewal_info'] = check_membership_renewal ($membership_info);
      }
    $do_update_membership = false;
    // Check if this is a forced update or if it is member-requested
    if ($_SESSION['renewal_info']['membership_expired'])
      {
        // Don't allow a member-request to spoof a forced update
        $member_request = false;
        $do_update_membership = true;
      }
    // Members requested update must come from panel_member.php (just to limit the scope) and
    // must pass update=membership... something like this: panel_member.php?update=membership
    elseif (isset ($_GET['update']) && $_GET['update'] == 'membership' && basename ($_SERVER['SCRIPT_NAME']) == 'panel_member.php')
      {
        $member_request = true;
        $do_update_membership = true;
      }
    // Display the update membership form
    if ($do_update_membership == true)
      {
        include_once ('func.check_membership.php');
        $membership_info = get_membership_info ($_SESSION['member_id']);
        $membership_renewal = check_membership_renewal ($membership_info);
        $membership_renewal_form = membership_renewal_form($membership_info['membership_type_id']);
        // Block the page with a renewal form (but allow member-requested forms to be closed
        $renew_membership_form = '
          <div id="membership_renewal">';
        if ($member_request == true)
          {
            $renew_membership_form .= '
              <div id="close_membership_renewal" title="close this window" onclick="document.getElementById(\'membership_renewal\').style.display = \'none\'">&times;</div>
              <h3>Change Membership Type</h3>
              <p>This form is best used for membership upgrades. Depending on the changes you are making, this could adversely affect your renewal date and/or other membership priviliges with the '.ORGANIZATION_TYPE.'. Repeated changes could also compound membership dues. For additional help, please contact <a href="mailto:membership@'.DOMAIN_NAME.'?Subject=Changing%20Membership%20(member%20#'.$_SESSION['member_id'].')">membership@'.DOMAIN_NAME.'</a>.</p>
              <p>Select from the option(s) below to continue.</p>';
          }
        else
          {
            $renew_membership_form .= '
              <div id="close_membership_renewal" title="logout" onclick="location.href=\'index.php?action=logout\'">&times;</div>
              <h3>Membership Renewal</h3>
              <p>It&rsquo;s time to update your membership. Membership renewal charges will be added to your next ordering invoice.</p>
              <p>Please select from the option(s) below to continue.</p>';
          }
        $renew_membership_form .= '
            <form action="'.$_SERVER['SCRIPT_NAME'].'" method="post">'.
            $membership_renewal_form['expire_message'].
            $membership_renewal_form['same_renewal_intro'].
            $membership_renewal_form['same_renewal'].
            $membership_renewal_form['changed_renewal_intro'].
            $membership_renewal_form['changed_renewal'].
            '<input type="hidden" name="update_membership" value="true">
            <input id="renew_membership" type="submit" name="submit" value="Renew now!">
            </form>
          </div>';
        // Add a style (later) to prevent scrolling the page
        // so it will stay shaded
        $renew_membership_form_css = '
          <link rel="stylesheet" id="motd_styles"  href="'.PATH.'membership_renewal.css" type="text/css" media="all" />';
      }
    // Set up the page tabs
    if (CurrentMember::auth_type('member'))
      $panel_member_menu = '
        <div class="tab_frame">
          <a href="'.PATH.'panel_member.php" class="'.($page_tab == 'member_panel' ? ' current_tab' : '').'">Member Panel</a>
        </div>';
    if (CurrentMember::auth_type('member'))
      $panel_shopping_menu = '
        <div class="tab_frame">
          <a href="'.PATH.'panel_shopping.php" class="'.($page_tab == 'shopping_panel' ? ' current_tab' : '').'">Shopping</a>
        </div>';
    if (CurrentMember::auth_type('producer'))
      $panel_producer_menu = '
        <div class="tab_frame">
          <a href="'.PATH.'panel_producer.php" class="'.($page_tab == 'producer_panel' ? ' current_tab' : '').'">Producer Panel</a>
        </div>';
    if (CurrentMember::auth_type('route_admin'))
      $panel_route_admin_menu = '
        <div class="tab_frame">
          <a href="'.PATH.'panel_route_admin.php" class="'.($page_tab == 'route_admin_panel' ? ' current_tab' : '').'">Route Admin</a>
        </div>';
    if (CurrentMember::auth_type('producer_admin'))
      $panel_producer_admin_menu = '
        <div class="tab_frame">
          <a href="'.PATH.'panel_producer_admin.php" class="'.($page_tab == 'producer_admin_panel' ? ' current_tab' : '').'">Producer Admin</a>
        </div>';
    if (CurrentMember::auth_type('member_admin'))
      $panel_member_admin_menu = '
        <div class="tab_frame">
          <a href="'.PATH.'panel_member_admin.php" class="'.($page_tab == 'member_admin_panel' ? ' current_tab' : '').'">Member Admin</a>
        </div>';
    if (CurrentMember::auth_type('cashier'))
      $panel_cashier_menu = '
        <div class="tab_frame">
          <a href="'.PATH.'panel_cashier.php" class="'.($page_tab == 'cashier_panel' ? ' current_tab' : '').'">Cashiers</a>
        </div>';
    if (CurrentMember::auth_type('site_admin'))
      $panel_admin_menu = '
        <div class="tab_frame">
          <a href="'.PATH.'panel_admin.php" class="'.($page_tab == 'admin_panel' ? ' current_tab' : '').'">Site Admin</a>
        </div>';
    $logout_menu = '
        <div class="tab_frame right">
          <a href="'.PATH.'index.php?action=logout" class="'.($page_tab == 'login' ? ' current_tab' : '').'">Logout</a>
        </div>';
    if (isset ($basket_id) && $basket_id != 0)
      {
        if (CurrentMember::auth_type('orderex') || ( ActiveCycle::ordering_window() == 'open'))
          {
            $basket_menu = '
        <div class="tab_frame right">
          <a href="'.PATH.'product_list.php?type=basket" class="">View Basket ['.$basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').']</a>
        </div>';
          }
      }
  }
// If they're not logged in, then they will have a login link
else
  {
    $login_menu = '
        <div class="tab_frame right">
          <a href="'.PATH.'index.php?action=login" class="'.($page_tab == 'login' ? ' current_tab' : '').'">Login</a>
        </div>';
  }
// Handle the MOTD inclusion
$motd_file_name = FILE_PATH.PATH.'motd.html';
// Don't do anything unless there's a MOTD file and we're logged in, AND membership update isn't already happening...
if (is_readable ($motd_file_name) && $_SESSION['member_id'] && !$do_update_membership)
  {
    $motd = ''; // Initialize
    // Check if MOTD has been seen since it was last changed
    $motd_last_changed = filemtime ($motd_file_name);
    if (isset ($_COOKIE['motd_last_seen']))
      $motd_last_seen = $_COOKIE['motd_last_seen'];
    else
      $motd_last_seen = 0;
    // Show the MOTD only if it has changed since it was last seen
    // OR if we are accessing the MOTD directly via motd.php
    $currenty_motd_page = false;
    if (basename ($_SERVER['SCRIPT_NAME']) == 'motd.php') $currenty_motd_page = true;
    if ($motd_last_seen < $motd_last_changed || $currenty_motd_page)
      {
        // Get the contents of the MOTD file and wrap it in an appropriate div
        $motd = '
          <div id="motd'.($currenty_motd_page ? '_page' : '' ).'">
            '.($currenty_motd_page ? '' : '<div id="close_motd" title="close this message" onclick="close_motd()">&times;</div>').
          file_get_contents ($motd_file_name).'
          </div>';
        // Identify (on this browser, at least) that the current MOTD has been seen
        // setcookie ('motd_last_seen', $value, $expire = 0);
        // But not if this is on the MOTD page (motd.php)
        if (! $currenty_motd_page)
          {
            $timestamp = time ();
            $expire_time = $timestamp + (3600*24*90); // Ninety days from now
            $motd_javascript = '
              <script type="text/javascript">
                function close_motd() {
                  document.cookie = "motd_last_seen='.$timestamp.';expires='.date('D, j M Y H:i:s e', $expire_time).';path=/";
                  document.getElementById("motd").style.display = "none";
                  }
              </script>';
          }
        $motd_css .= '
          <link rel="stylesheet" id="motd_styles"  href="'.PATH.'motd.css" type="text/css" media="all" />';
      }
  }
// Put it all together now
$content_header = '<!DOCTYPE html>
<html>
  <head>
    <title>'.SITE_NAME.' - '.$page_title.'</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="'.PATH.'ajax/jquery.js" type="text/javascript"></script>
    <script src="'.PATH.'ajax/jquery-ui.js" type="text/javascript"></script>'.
    $favicon.'
    <link href="'.PATH.'stylesheet.css" rel="stylesheet" type="text/css">'.
    $motd_css.
    $renew_membership_form_css.
    (isset ($page_specific_css) ? $page_specific_css : '').'
    <script type="text/javascript">
      function init() {
        // Do not throw an error if page does not have a load_target element...
        if (document.getElementById ("load_target")) {
          var text_input = document.getElementById ("load_target");
          text_input.focus ();
          text_input.select ();
          }
        }
      window.onload = init;
    </script>'.
    $motd_javascript.
    (isset ($page_specific_javascript) ? $page_specific_javascript : '').'
  </head>
  <body lang="en-us">'.
    $google_analytics.'
    <div id="header">
      <a href="'.PATH.'">'.$header_title.'</a>
      <div class="header_mission">'.
        ($warn_now ? $site_down_message : '').'
        '.MISSION_VISION_VALUES.'
      </div>
      <div class="tagline">
        '.TAGLINE.'
      </div>
    </div><!-- #header -->
    <!-- BEGIN MENU SECTION -->
    <div id="menu">'.
      $panel_member_menu.
      $panel_shopping_menu.
      $panel_producer_menu.
      $panel_route_admin_menu.
      $panel_producer_admin_menu.
      $panel_member_admin_menu.
      $panel_cashier_menu.
      $panel_admin_menu.
      $logout_menu.
      $basket_menu.
      // Menus above will be shown to members with approved auth_types
      // Menu below will be shown when no member is logged in
      $login_menu.'
    </div><!-- #menu -->'.
    $motd.
    $renew_membership_form.'
    <div id="content">
      '.$page_title_html.'
      '.$page_subtitle_html.'
      <div class="clear"></div>';

echo $content_header;

if ($site_is_down)
  {
    include ('template_footer.php');
    exit (0);
  }
