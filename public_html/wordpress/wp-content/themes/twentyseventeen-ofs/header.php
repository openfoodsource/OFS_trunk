<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

// Include OpenFood configuration settings for use by WordPress
include_once 'config_openfood.php';

// Add OpenFood stylesheets
wp_enqueue_style ('ofs_stylesheet', BASE_URL.PATH.'stylesheet.css', array(), '2.1.1', 'all');
wp_enqueue_style ('user_menu', BASE_URL.PATH.'user_menu.css', array(), '2.1.1', 'all');

// Get userdata (i.e. user_role and username), which are used for the Hotspots Analytics WordPress package
if (isset ($_SESSION['member_id']) && $_SESSION['member_id'] > 0) get_userdata( $_SESSION['member_id'] );

// The WORDPRESS_MENU constant is formatted to create OpenFood menu items in WordPress:
//    each line represents an individual menu item
//    use pipes to separate elements in order: parent_slug | title | url | order | parent | id | auth_type
//    for convenience, any white space around elements will be trimmed away
//    auth_type may include comma-separated (no white space) auth_type values permitted to see this menu item
//    auth_type = ALL may be used to show the menu item to everyone
//    parent is the CSS ID of the parent menu item -- or zero (0) to make it a top-level menu item
//
//    EXAMPLE:
//  [menu-slug] [Menu Title]       [Link URL]                        [Menu Order]  [Item Parent ID]   [Item ID]                    [Permitted auth_type]
//  top-menu  | Open Food Source | OPENFOOD                         |     10     |      0           | openfood-menu              | ALL
//  top-menu  | Shopping Panel   | OPENFOODpanel_shopping.php       |     10     | openfood-menu    | menu_panel_shopping        | member
//  top-menu  | Member Panel     | OPENFOODpanel_member.php         |     20     | openfood-menu    | menu_panel_member          | member
//  top-menu  | Producer Panel   | OPENFOODpanel_producer.php       |     30     | openfood-menu    | menu_panel_member          | producer
//  top-menu  | Route Admin      | OPENFOODpanel_route_admin.php    |     40     | openfood-menu    | menu_panel_route_admin     | route_admin
//  top-menu  | Producer Admin   | OPENFOODpanel_producer_admin.php |     50     | openfood-menu    | menu_panel_producer_admin  | producer_admin
//  top-menu  | Member Admin     | OPENFOODpanel_member_admin.php   |     60     | openfood-menu    | menu_panel_member_admin    | member_admin
//  top-menu  | Financial Admin  | OPENFOODpanel_cashier.php        |     70     | openfood-menu    | menu_panel_cashier         | cashier
//  top-menu  | OpenFood Admin   | OPENFOODpanel_admin.php          |     80     | openfood-menu    | menu_panel_openfood_admin  | site_admin
//  top-menu  | WordPress Admin  | OPENFOODpanel_admin.php          |     90     | openfood-menu    | menu_panel_wordpress_admin | site_admin

// Modify WordPress menus to add OpenFood menu items


// if (CurrentMember::auth_type ('member')) debug_print ("INFO: 000100 ", array ('SESSION' => $_SESSION), basename(__FILE__).' LINE '.__LINE__);
include_once ('custom_menu_items.php');
foreach (explode ("\n", WORDPRESS_MENU) as $menu_item)
  {
    list ($parent_slug, $title, $url, $order, $parent, $id, $auth_type) = explode ("|", trim($menu_item));
    // Make a menu item if it is NOT a comment AND the user has auth_type permission
    if (substr (trim ($parent_slug), 0, 1) != '#'
        && (trim($auth_type) == 'ALL'
            || CurrentMember::auth_type ("$auth_type")))
      {
        // Convert occurrence of "OPENFOOD" in $url to BASE_URL.PATH
        $url = str_replace ('OPENFOOD', BASE_URL.PATH, $url);
        custom_menu_items::add_item(trim($parent_slug), trim($title), trim($url), trim($order), trim($parent), trim($id));
      }
  }

// We need these values from OpenFood
global $onload, $content_user_menu;

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>
<body <?php body_class(); echo (strlen ($onload) > 0 ? ' onload="'.$onload.'"' : '') ?>>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'twentyseventeen' ); ?></a>

	<header id="masthead" class="site-header" role="banner">

		<?php get_template_part( 'template-parts/header/header', 'image' ); ?>
      <?php
        include_once ('wordpress_utilities.php');
        if (SHOW_USER_MENU == true) echo $content_user_menu;
      ?>

		<?php if ( has_nav_menu( 'top' ) ) : ?>
			<div class="navigation-top">
				<div class="wrap">
					<?php get_template_part( 'template-parts/navigation/navigation', 'top' ); ?>
				</div><!-- .wrap -->
			</div><!-- .navigation-top -->
		<?php endif; ?>

	</header><!-- #masthead -->

	<?php
	// If a regular post or page, and not the front page, show the featured image.
	if ( has_post_thumbnail() && ( is_single() || ( is_page() && ! twentyseventeen_is_frontpage() ) ) ) :
		echo '<div class="single-featured-image-header">';
		the_post_thumbnail( 'twentyseventeen-featured-image' );
		echo '</div><!-- .single-featured-image-header -->';
	endif;
	?>

	<div class="site-content-contain">
		<div id="content" class="site-content">
