<?php

include_once ('general_functions.php'); // just in case it got missed from the base page
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
$script_id = strtr (basename ($_SERVER['SCRIPT_NAME']), '.', '-');
if (!isset($page_tab)) $page_tab = '';
if (!isset($modal_action)) $modal_action = '';
if (isset ($_GET['display_as']) && $_GET['display_as'] == 'popup') $display_as_popup = true;
elseif (!isset ($display_as_popup)) $display_as_popup = false;
$site_is_down = false;

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

// Add boiler-plate stylesheet to the page-specific styles
$page_specific_stylesheets['ofs_stylesheet'] = array (
  'name'=>'ofs_stylesheet',
  'src'=>BASE_URL.PATH.'stylesheet.css',
  'dependencies'=>array(),
  'version'=>'2.1.1',
  'media'=>'all',
  );
// Add boiler-plate scripts to the page-specific scripts array
$page_specific_scripts['jquery'] = array (
  'name'=>'jquery',
  'src'=>BASE_URL.PATH.'ajax/jquery.js',
  'dependencies'=>array(),
  'version'=>'3.2.1',
  'location'=>false
  );
$page_specific_scripts['jquery-ui'] = array (
  'name'=>'jquery-ui',
  'src'=>BASE_URL.PATH.'ajax/jquery-ui.js',
  'dependencies'=>array('jquery'),
  'version'=>'1.11.1',
  'location'=>false
  );
$page_specific_scripts['jquery-simplemodal'] = array (
  'name'=>'jquery-simplemodal',
  'src'=>BASE_URL.PATH.'ajax/jquery-simplemodal.js',
  'dependencies'=>array('jquery'),
  'version'=>'1.4.5',
  'location'=>false
  );
$page_specific_scripts['rangeslider'] = array (
  'name'=>'rangeslider',
  'src'=>BASE_URL.PATH.'js/rangeslider.js',
  'dependencies'=>array('jquery'),
  'version'=>'2.1.1',
  'location'=>false
  );
$page_specific_scripts['ofs_javascript'] = array (
  'name'=>'ofs_javascript',
  'src'=>BASE_URL.PATH.'javascript.js',
  'dependencies'=>array(),
  'version'=>'1.2.0',
  'location'=>false
  );

// Get basket information
if (isset ($_SESSION['member_id'])
    && ActiveCycle::delivery_id())
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
        // Keep basket info as session variables (these will be set upon login, prior to visiting any
        // WordPress pages, so does not need to be invoked from WordPress)
        $_SESSION['basket_quantity'] = $row->basket_quantity;
        $_SESSION['basket_id'] = $row->basket_id;
      }
  }
// Check if we need to force a membership update or if it is member-requested
// if..elseif... to ensure we only process one popup at a time
if ($_SESSION['renewal_info']['membership_expired'] == true
    && $update_membership_page != true)
  {
    // Force the membership_renewal popup
    $page_specific_javascript .= '
      jQuery(document).ready(function() {
        popup_src("update_membership.php?display_as=popup", "membership_renewal", "index.php?action=logout");
        });';
    // Include membership_renewal styles
    $page_specific_stylesheets['membership_renewal'] = array (
      'name'=>'membership_renewal',
      'src'=>BASE_URL.PATH.'membership_renewal.css',
      'dependencies'=>array('ofs_stylesheet'),
      'version'=>'2.1.1',
      'media'=>'all',
      );
  }
// Handle the MOTD inclusion
elseif (MOTD_REPEAT_TIME >= 0 &&
        strlen (MOTD_CONTENT) > 0 &&
        ofs_get_status ('motd_viewed', $_SESSION['member_id']) == false)
  {
    // Force the MOTD popup
    $page_specific_javascript .= '
      jQuery(document).ready(function() {
        popup_src("motd.php?display_as=popup", "motd", "");
        });';
    // Include MOTD styles
    $page_specific_stylesheets['motd'] = array (
      'name'=>'motd',
      'src'=>BASE_URL.PATH.'motd.css.css',
      'dependencies'=>array('ofs_stylesheet'),
      'version'=>'2.1.1',
      'media'=>'all',
      );
  }
// Handle the customer site selection (applies also to non-logged-in users)
elseif (USE_AVAILABILITY_MATRIX == true
        && $is_customer_product_page == true
        && ! isset ($_COOKIE['ofs_customer']['site_id'])
        && ! isset ($_COOKIE['ofs_customer']['site_id']))
  {
    // Force the customer_select_site popup
    $page_specific_javascript .= '
      jQuery(document).ready(function() {
        popup_src("customer_select_site.php?display_as=popup", "customer_select_site", "");
        });';
  }

////////////////////////////////////////////////////////////////////////////////
//////////////                                              ////////////////////
//////////////     ASSEMBLE FINAL OUTPUT FOR POPUP PAGES    ////////////////////
//////////////                                              ////////////////////
////////////////////////////////////////////////////////////////////////////////

if ($display_as_popup == true) // Do not distinguish Wordpress vs. non-Wordpress
  {
    // Since popups are often modal dialogues, we will sometimes need to close or refresh other windows
    // according to the information returned by the modal pages
    // Functions just_close(delay) and reload_parent() are defined in javascript.js, included from this header
    // Other functions can be used by passing them in as something like: function_foo(parent.variable_bar)
    // ... where the parent page will be responsible for supplying function_foo() and variable_bar
    $inline_styles = get_inline_styles ($page_specific_stylesheets, $page_specific_css);
    $inline_scripts = get_inline_scripts ($page_specific_scripts, $page_specific_javascript);
    $content_header = '<!DOCTYPE html>
<html style="overflow:auto;height:100%;box-sizing:border-box;">
  <head>
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="-1">'.
    $favicon.
    (isset ($inline_styles) ? $inline_styles : '').
    (isset ($inline_scripts) ? $inline_scripts : '').'
  </head>
  <body class="popup" lang="en-us"'.(strlen ($modal_action) > 0 ? ' onload="'.$modal_action.'"' : '').'>'.
  $google_tracking_code.'
    <div class="modal_content">
      <div class="full_modal">';
  echo $content_header;
  }
else // This is not a popup page
  {
    // So fork over to wordpress or openfood headers to complete the header for non-popup pages
    if (WORDPRESS_ENABLED == true)
      {
        include_once ('wordpress_utilities.php');
        include_once (FILE_PATH.PATH.'template_header_wordpress.php');
      }
    else
      {
        include_once (FILE_PATH.PATH.'template_header_openfood.php');
      }
  }

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

// Get markup for styles in the proper order for loading
function get_inline_styles ($page_specific_stylesheets, $page_specific_css)
  {
    // Compile the stylesheets
    $styles_array = array();
    // Avoid infinite recursive loops
    $max_iterations = ceil (0.5 * count ($page_specific_stylesheets) * count ($page_specific_stylesheets));
    while (count ($page_specific_stylesheets) > 0 && $max_iterations-- > 0)
      {
        // Cycle through the page_specific_styles and peel off those with dependencies that have been met
        foreach ($page_specific_stylesheets as $style_name=>$style)
          {
            $okay_to_add = false;
            // If there are no dependencies, then we can add this style
            if (count ($style['dependencies']) == 0) $okay_to_add = true;
            // If there are no unmet dependencies, then we can add this style
            else
              {
                $okay_to_add = true;
                foreach ($style['dependencies'] as $style_dependency)
                  {
                    if (! isset ($styles_array[$style_dependency])) $okay_to_add = false;
                  }
              }
            // If it is still okay to add the style, then do so
            if ($okay_to_add == true)
              {
                $styles_array[$style['name']] = '
                  <link rel="stylesheet" type="text/css" href="'.$style['src'].'?ver='.$style['version'].'">';
                // And remove the element from the page_specific_styles array
                unset ($page_specific_stylesheets[$style['name']]);
              }
          }
      }
    // Combine and add page_specific_css
    $inline_styles =
      implode ('', $styles_array).
      (strlen ($page_specific_css) > 0 ? '
      <style type="text/css">'.
        $page_specific_css.'
      </style>'
      : '');
    return ($inline_styles);
  }

// Get markup for scripts in the proper order for loading
function get_inline_scripts ($page_specific_scripts, $page_specific_javascript)
  {
    // Compile the scripts
    $scripts_array = array();
    // Avoid infinite recursive loops
    $max_iterations = ceil (0.5 * count ($page_specific_scripts) * count ($page_specific_scripts));
    while (count ($page_specific_scripts) > 0 && $max_iterations-- > 0)
      {
        // Cycle through the page_specific_scripts and peel off those with dependencies that have been met
        foreach ($page_specific_scripts as $script_name=>$script)
          {                                          // Okay to add to scripts_array if...
            $okay_to_add = false;
            // If there are no dependencies, then we can add this script
            if (count ($script['dependencies']) == 0) $okay_to_add = true;
            // If there are no unmet dependencies, then we can add this script
            else
              {
                $okay_to_add = true;
                foreach ($script['dependencies'] as $script_dependency)
                  {
                    if (! isset ($scripts_array[$script_dependency])) $okay_to_add = false;
                  }
              }
            // If it is still okay to add the script, then do so
            if ($okay_to_add == true)
              {
                $scripts_array[$script['name']] = '
                  <script type="text/javascript" src="'.$script['src'].'?ver="'.$script['version'].'"></script>';
                // And remove the element from the page_specific_scripts array
                unset ($page_specific_scripts[$script['name']]);
              }
          }
      }
    // Combine and add page_specific_javascript
    $inline_scripts =
      implode ('', $scripts_array).
      (strlen ($page_specific_javascript) > 0 ? '
      <script type="text/javascript">'.
        $page_specific_javascript.'
      </script>'
      : '');
    return ($inline_scripts);
  }
