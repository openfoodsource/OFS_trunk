<?php
/**
 * Twenty Seventeen functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 */

// Be sure Wordpress has session data from OFS (e.g. for the usermenu)
if(! session_id()) session_start();

/**
  * Include styles from the parent twentyseventeen theme
    AND the styles from OpenFood
  */


function my_theme_enqueue_styles ($page_specific_stylesheets)
  {
    global $page_specific_stylesheets;
    $parent_style = 'twentyseventeen-ofs'; // This is 'twentyseventeen-style' for the Twenty Seventeen theme.
    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array($parent_style), wp_get_theme()->get('Version'));
    // Now cycle through the OpenFood styles and enqueue them
    if (is_array ($page_specific_stylesheets))
      {
        foreach ($page_specific_stylesheets as $stylesheet)
          {
            wp_enqueue_style ($stylesheet['name'], $stylesheet['src'], $stylesheet['dependencies'], $stylesheet['version'], $stylesheet['media']);
          }
      }
  }
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

/**
  * Add sidebar to pages as well as posts
  */
function twentyseventeen_body_classes_child( $classes )
  {
    if ( is_active_sidebar( 'sidebar-1' ) &&  is_page() )
      {
        $classes[] = 'has-sidebar';
      }
    return $classes;
  }
add_filter( 'body_class', 'twentyseventeen_body_classes_child' );

// Remove display of the admin bar from the top of Wordpress pages
add_filter('show_admin_bar', '__return_false');

// This will default the wiki posting screen to single-column
$post_type = 'yada_wiki'; // Change this to a post type you'd want
function my_screen_layout_post( $selected )
  {
    if( false === $selected )
      { 
        return 1; // Use 1 column if user hasn't selected anything in Screen Options
      }
    return 1; // Use what the user wants
  }
add_filter( "get_user_option_screen_layout_{$post_type}", 'my_screen_layout_post' );

// Following will force Wordpress to follow OFS user authentications
add_filter('authenticate', 'ofs_auth', 10, 3);
function ofs_auth ($user, $username, $password)
  {
    // We will only reach this function if OFS has already authorized the user
    // but we will confirm by checking $_SESSION['wp_auth_okay'] and rubber-stamp
    // all cases where that is true
    if (isset ($_SESSION['wp_auth_okay'])
        && $_SESSION['wp_auth_okay'] == true
        && isset ($_SESSION['member_id']))
      {
        $userobj = new WP_User();
        $user = $userobj->get_data_by('ID', $_SESSION['member_id']); // Does not return a WP_User object \U0001f641
        $user = new WP_User($user->ID); // Attempt to load up the user with that ID
      }
    // Comment this line if you wish to fall back on WordPress authentication
    // Useful for times when the external service is offline
    remove_action('authenticate', 'wp_authenticate_username_password', 20);
    return $user;
  }
