<?php

// Load wordpress environment so that functions, et. al. will work properly
require_once (FILE_PATH.WORDPRESS_PATH.'wp-load.php');

////////////////////////////////////////////////////////////////////////////////
//////////////                                              ////////////////////
//////////////   ASSEMBLE FINAL OUTPUT FOR STANDARD PAGES   ////////////////////
//////////////                                              ////////////////////
////////////////////////////////////////////////////////////////////////////////

// Set the HTML header title tag
add_filter('pre_get_document_title', 'assignPageTitle');
function assignPageTitle()
  {
    global $page_title;
    return $page_title;
  }

// Add 'page' as a <body> class
function my_class_names($classes)
  {
    // add 'page' class to the $classes array
    $classes[] = 'page';
    if (WORDPRESS_SHOW_SIDEBAR != true)
      {
        // Remove 'has-sidebar' class from the $classes array
        unset($classes[array_search ('has-sidebar', $classes)]);
      }
    // return the $classes array
    return $classes;
  }
//Now update the body classes in wordpress
add_filter('body_class','my_class_names');

// TO DO !!!
// Modify the "current_menu_item" for OFS and generate the new menu
// add_filter('wp_nav_menu', 'ofs_nav_menu');
// function ofs_nav_menu () {
//   return "FOO BAR";
//   }

// Add OFS styles
foreach ($page_specific_stylesheets as $stylesheet)
  {
    wp_enqueue_style ($stylesheet['name'], $stylesheet['src'], $stylesheet['dependencies'], $stylesheet['version'], $stylesheet['media']);
  }
// Add in-line style content
if (isset ($page_specific_css) && strlen ($page_specific_css) > 0)
  {
    wp_register_style('page_specific_styles', false, array('ofs_stylesheet'), 'all');
    wp_enqueue_style('page_specific_styles');
    wp_add_inline_style('page_specific_styles', $page_specific_css);
  }

// Add OFS scripts
wp_deregister_script('jquery'); // jquery.js is automatically loaded by WordPress but we want to override it with a known version
foreach ($page_specific_scripts as $script)
  {
    wp_enqueue_script ($script['name'], $script['src'], $script['dependencies'], $script['version'], $script['location']);
    // Need to use this script to connect the wp_inline_script (is this a bug???)
    $last_linked_script = $script['name'];
  }
// Add all inlined scripts are placed *after* the last linked script from above
if (isset ($popup_motd) && strlen ($popup_motd) > 0) wp_add_inline_script ($last_linked_script, $popup_motd, 'after');
if (isset ($popup_renew_membership) && strlen ($popup_renew_membership) > 0) wp_add_inline_script ($last_linked_script, $popup_renew_membership, 'after');
if (isset ($page_specific_javascript) && strlen ($page_specific_javascript) > 0) wp_add_inline_script ($last_linked_script, $page_specific_javascript, 'after');

// Display the Wordpress header (top of page)
get_header();

// Begin the non-header page content
echo '
    <div class="wrap">
      <header class="page-header">
        <h1 class="entry-title">'.$page_title_html.$page_subtitle_html.'</h1>
      </header><!-- .entry-header -->
      <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
          <article id="post_ofs" class="ofs-'.$script_id.' page type-page status-publish hentry">
            <div id="ofs_content" class="entry-content">';

// If the site is down, skip all page content and go straight to the footer
if ($site_is_down)
  {
    include ('template_footer.php');
    exit (0);
  }
