<?php

include_once 'general_functions.php'; // just in case it got missed from the base page
include_once ('wordpress_utilities.php');
$content_header = '';
$google_tracking_code = '';
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
$onload_action = '';
if (!isset($page_tab)) $page_tab = '';
if (!isset($modal_action)) $modal_action = '';
if (!isset ($display_as_popup)) $display_as_popup = false;
$site_is_down = false;


$wordpress_menu = '';
$wordpress_producer_menu = '';
$wordpress_cashier_menu = '';
$wordpress_board_menu = '';

// Prepare google tracking code
if (strlen (GOOGLE_TRACKING_ID) > 0)
  include_once ('google_tracking.php');

// Set $favicon
$favicon = (FAVICON != '' ? '
    <link rel="shortcut icon" href="'.FAVICON.'" type="image/x-icon">' : '');
$header_title =
  (SHOW_HEADER_LOGO ? '
  <img id="header_logo" src="'.DIR_GRAPHICS.'logo.jpg" border="0" alt="'.SITE_NAME.'">' : '').
  (SHOW_HEADER_SITENAME ? '
  <h1 class="site-title">'.SITE_NAME.'</h1>' : '');
$content_login = (WORDPRESS_ENABLED == true ? wordpress_show_usermenu() : '');

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
if (isset ($_SESSION['member_id']))
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
            '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"
            AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysqli_real_escape_string ($connection, ActiveCycle::delivery_id()).'
          GROUP BY
            '.NEW_TABLE_BASKETS.'.member_id';
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 780934 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $basket_quantity = 0;
        if ($row = mysqli_fetch_object ($result))
          {
            $basket_quantity = $row->basket_quantity;
            $basket_id = $row->basket_id;
          }
      }
    // Check if this is a forced update or if it is member-requested
    if ($_SESSION['renewal_info']['membership_expired'] && $update_membership_page != true)
      {
        $popup_renew_membership .= '
          <script type="text/javascript">
            jQuery(document).ready(function() {
              popup_src("update_membership.php?display_as=popup", "membership_renewal", "index.php?action=logout");
              });
          </script>';
        $page_specific_css .= '
          <link rel="stylesheet" id="membership_renewal_styles" href="'.PATH.'membership_renewal.css" type="text/css" media="all" />';
      }
    // Handle the MOTD inclusion
    elseif (MOTD_REPEAT_TIME >= 0 &&
            strlen (MOTD_CONTENT) > 0 &&
            ! ofs_get_status ('motd_viewed', $_SESSION['member_id']))
      {
        $popup_motd .= '
          <script type="text/javascript">
            jQuery(document).ready(function() {
              popup_src("motd.php?display_as=popup", "motd", "");
              });
          </script>';
        $page_specific_css .= '
          <link rel="stylesheet" id="motd_styles"  href="'.PATH.'motd.css" type="text/css" media="all" />';
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
// Put it all together now

////////////////////////////////////////////////////////////////////////////////
//////////////                                              ////////////////////
//////////////     ASSEMBLE FINAL OUTPUT FOR POPUP PAGES    ////////////////////
//////////////                                              ////////////////////
////////////////////////////////////////////////////////////////////////////////
if ($display_as_popup == true)
  {
    // Since popups are often modal dialogues, we will sometimes need to close or refresh other windows
    // according to the information returned by the modal pages
    // Functions just_close() and reload_parent() are defined in javascript.js, included from this header
    // Other functions can be used by passing them in as something like: function_foo(parent.variable_bar)
    // ... where the parent page will be responsible for supplying function_foo() and variable_bar
    $content_header = '<!DOCTYPE html>
<html style="overflow:auto;">
  <head>'.
    $favicon.'
    <link href="'.PATH.'stylesheet.css" rel="stylesheet" type="text/css">'.
    (isset ($page_specific_css) ? $page_specific_css : '').'
    <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
    <script type="text/javascript" src="'.PATH.'ajax/jquery-simplemodal.js"></script>
    <script src="'.PATH.'javascript.js" type="text/javascript"></script>
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
    (isset ($page_specific_javascript) ? $page_specific_javascript : '').'
  </head>
  <body lang="en-us"'.(strlen ($modal_action) > 0 ? ' onload="parent.'.$modal_action.'"' : '').' style="margin:0;">'.
  $google_tracking_code;
  }
////////////////////////////////////////////////////////////////////////////////
//////////////                                              ////////////////////
//////////////   ASSEMBLE FINAL OUTPUT FOR STANDARD PAGES   ////////////////////
//////////////                                              ////////////////////
////////////////////////////////////////////////////////////////////////////////
else
  {
    $content_header = '<!DOCTYPE html>
<html>
  <head>
    <title>'.SITE_NAME.' - '.$page_title.'</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="'.PATH.'ajax/jquery.js" type="text/javascript"></script>
    <script src="'.PATH.'ajax/jquery-ui.js" type="text/javascript"></script>
    <script type="text/javascript" src="'.PATH.'ajax/jquery-simplemodal.js"></script>'.
    $favicon.'
    <link href="'.PATH.'stylesheet.css" rel="stylesheet" type="text/css">'.
    (isset ($page_specific_css) ? $page_specific_css : '').'
    <script src="'.PATH.'javascript.js" type="text/javascript"></script>
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
    (isset ($popup_renew_membership) ? $popup_renew_membership : ''). // not on popup pages to prevent recursion
    (isset ($popup_motd) ? $popup_motd : '').                         // not on popup pages to prevent recursion
    (isset ($page_specific_javascript) ? $page_specific_javascript : '').'
  </head>
  <body>'.
    $google_tracking_code.'
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
    </div><!-- #menu -->
    <div id="content">
      '.$page_title_html.'
      '.$page_subtitle_html.'
      <div class="clear"></div>';
  }

echo $content_header;

if ($site_is_down)
  {
    include ('template_footer.php');
    exit (0);
  }
