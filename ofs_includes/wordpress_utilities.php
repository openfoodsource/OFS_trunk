<?php

// Access Open Food Source to get defined constants
include_once ('config_openfood.php');

$content_user_menu = '';
if (SHOW_USER_MENU == true) include ('user_menu.php');

function wordpress_login ($member_id, $auth_types)
  {
    // We have skipped the Wordpress login checks, but since we are logged-in, we must set the wordpress session
    // This file includes everything required to set the session through wordpress
    // Path (absolute or relative) to where your WP core is running
    require(FILE_PATH.WORDPRESS_PATH.'wp-load.php');
    if (is_user_logged_in())
      {
        $user = wp_get_current_user();
      }
    else
      {
        $creds = array ();
        // We have an override on the "authenticate" filter in Wordpress so that it will
        // automatically log in without checking username/password again.
        $creds['user_login'] = ''; // not needed
        $creds['user_password'] = ''; // not needed
        $creds['remember'] = true;
        $user = wp_signon ($creds, false);
        if (is_wp_error ($user))
          {
            echo $user->get_error_message();
          }
        else
          {
            wp_set_auth_cookie ($user->ID, true);
          }
      }
    // Now make sure the wordpress database is correctly configured for this member
    // Open a database wp_connection to the Wordpress database
    $wp_connection = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die(debug_print ("ERROR: 293034 ", array ('error'=>'Error while connecting to the WordPress database', mysqli_connect_error()), basename(__FILE__).' LINE '.__LINE__));
    // SET ROLES FOR WORDPRESS
    // Members get to be "subscribers"
    $auth_array = explode (',', $auth_types);
    if (in_array ('member', $auth_array))
      {
        $wp_capabilities = 'a:1:{s:10:"subscriber";b:1;}';
        $wp_user_level = 10;
      }
    // Site admins get to be "administrators"
    if (in_array ('site_admin', $auth_array))
      {
        $wp_capabilities = 'a:1:{s:13:"administrator";b:1;}';
        $wp_user_level = 0;
      }
    // We will not overwrite any values (which means permissions will never be demoted)
    // but we do need to write *new* members as they log in...
    // First: wp_capabilities (ROLES)
    $query_get_capabilities = '
      SELECT
        umeta_id,
        user_id,
        meta_key,
        meta_value
      FROM
        wp_usermeta
      WHERE
        user_id="'.mysqli_real_escape_string ($wp_connection, $member_id).'"
        AND meta_key="wp_capabilities"';
    $result_get_capabilities = @mysqli_query ($wp_connection, $query_get_capabilities) or die (debug_print ("ERROR: 790223 ", array ($query_get_capabilities, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ( $row = mysqli_fetch_array ($result_get_capabilities, MYSQLI_ASSOC) )
      {
        // This is key to the current wp_capabilities for the member
        $umeta_id = $row['umeta_id'];
        // We will leave it alone
      }
    else
      {
        // Or else we will add a new capabilities value where none existed before
        $query_set_capabilities = '
          INSERT INTO
            wp_usermeta
          SET
            user_id="'.mysqli_real_escape_string ($wp_connection, $member_id).'",
            meta_key="wp_capabilities",
            meta_value="'.mysqli_real_escape_string ($wp_connection, $wp_capabilities).'"';
        $result_set_capabilities = @mysqli_query ($wp_connection, $query_set_capabilities) or die (debug_print ("ERROR: 742943 ", array ($query_set_capabilities, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }

    // Second: wp_user_level (ROLES)
    $query_get_user_level = '
      SELECT
        umeta_id,
        user_id,
        meta_key,
        meta_value
      FROM
        wp_usermeta
      WHERE
        user_id="'.mysqli_real_escape_string ($wp_connection, $member_id).'"
        AND meta_key="wp_user_level"';
    $result_get_user_level = @mysqli_query ($wp_connection, $query_get_user_level) or die (debug_print ("ERROR: 939823 ", array ($query_get_user_level, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ( $row = mysqli_fetch_array ($result_get_user_level, MYSQLI_ASSOC) )
      {
        // This is key to the current wp_capabilities for the member
        $umeta_id = $row['umeta_id'];
        // We will leave it alone
      }
    else
      {
        // Or else we will add a new value user_level value where none existed before
        $query_set_user_level = '
          INSERT INTO
            wp_usermeta
          SET
            user_id="'.mysqli_real_escape_string ($wp_connection, $member_id).'",
            meta_key="wp_user_level",
            meta_value="'.mysqli_real_escape_string ($wp_connection, $wp_user_level).'"';
        $result_set_user_level = @mysqli_query ($wp_connection, $query_set_user_level) or die (debug_print ("ERROR: 437956 ", array ($query_set_user_level, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    // SET GROUPS FOR WORDPRESS
    // First remove all groups for this member -- except "registered"
    // This ensures the member's groups will be correctly updated at each login
    $query_remove_groups = '
      DELETE FROM
        wp_groups_user_group
      WHERE
        user_id="'.mysqli_real_escape_string ($wp_connection, $member_id).'"
        AND group_id != 1';
    $result_remove_groups = @mysqli_query ($wp_connection, $query_remove_groups) or die (debug_print ("ERROR: 572923 ", array ($query_remove_groups, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    // Then set groups based on the member's auth_types
    foreach (explode ("\n", WORDPRESS2OPENFOOD_GROUPS) as $line)
      {
        list($key, $auth_type) = explode ('=', trim ($line));
          // Hard-coded example:
          // 
          // $wp_groups = array (
          // 1=>'registered',
          // 2=>'member',
          // 3=>'producer',
          // 4=>'institution',
          // 5=>'route_admin',
          // 6=>'cashier',
          // 7=>'board',
          // 8=>'member_admin',
          // 9=>'producer_admin',
          // 10=>'site_admin');
        if (in_array ($auth_type, $auth_array))
          {
            $query_add_group = '
              INSERT INTO
                wp_groups_user_group
              SET
                user_id="'.mysqli_real_escape_string ($wp_connection, $member_id).'",
                group_id="'.mysqli_real_escape_string ($wp_connection, $key).'"';
            $result_add_group = @mysqli_query ($wp_connection, $query_add_group) or die (debug_print ("ERROR: 673023 ", array ($query_add_group, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
      }
  }

function wordpress_logout ()
  {
    // This file includes everything required to logout through wordpress
    @include_once (FILE_PATH.WORDPRESS_CONFIG);
    @wp_logout();
  }
