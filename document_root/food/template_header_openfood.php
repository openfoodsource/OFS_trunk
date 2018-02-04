<?php

$content_user_menu = '';
if (SHOW_USER_MENU == true)
  {
    // Include the user_menu and page_specific_styles['user_menu']
    // This will assign an appropriate value to $content_user_menu
    include ('user_menu.php');
  }

////////////////////////////////////////////////////////////////////////////////
//////////////                                              ////////////////////
//////////////       ASSEMBLE MENU BUTTONS AND TABS         ////////////////////
//////////////                                              ////////////////////
////////////////////////////////////////////////////////////////////////////////

// Check if the member is logged in and show page menu/tabs accordingly
if (isset ($_SESSION['member_id']))
  {
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
    if (isset ($_SESSION['basket_id']) && $_SESSION['basket_id'] != 0)
      {
        if (CurrentMember::auth_type('orderex') || ( ActiveCycle::ordering_window() == 'open'))
          {
            $basket_menu = '
        <div class="tab_frame right">
          <a href="'.PATH.'product_list.php?type=basket" class="">View Basket ['.$_SESSION['basket_quantity'].' '.Inflect::pluralize_if($_SESSION['basket_quantity'], 'item').']</a>
        </div>';
          }
      }
  }
// If they're not logged in, then they will need a login link
else
  {
    $login_menu = '
        <div class="tab_frame right">
          <a href="'.PATH.'index.php?action=login" class="'.($page_tab == 'login' ? ' current_tab' : '').'">Login</a>
        </div>';
  }

////////////////////////////////////////////////////////////////////////////////
//////////////                                              ////////////////////
//////////////   ASSEMBLE FINAL OUTPUT FOR STANDARD PAGES   ////////////////////
//////////////                                              ////////////////////
////////////////////////////////////////////////////////////////////////////////

// These functions are in template_header.php, where they are also used
$inline_styles = get_inline_styles ($page_specific_stylesheets, $page_specific_css);
$inline_scripts = get_inline_scripts ($page_specific_scripts, $page_specific_javascript);

$content_header = '<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="-1">
    <title>'.SITE_NAME.' - '.$page_title.'</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'.
    $favicon.
    (isset ($inline_styles) ? $inline_styles : '').
    (isset ($inline_scripts) ? $inline_scripts : '').'
  </head>
  <body'.(isset ($onload) && strlen ($onload) > 0 ? ' onload="'.$onload.'"' : '').'>'.
    $google_tracking_code.
      $content_user_menu.'
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
    <div id="ofs_content">
      '.$page_title_html.'
      '.$page_subtitle_html.'
      <div class="clear"></div>';

echo $content_header;

// If the site is down, skip all page content and go straight to the footer
if ($site_is_down)
  {
    include ('template_footer.php');
    exit (0);
  }
