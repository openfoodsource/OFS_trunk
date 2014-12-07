<?php

function wordpress_show_usermenu ()
  {
    // Check if the member is logged in
    if (isset ($_SESSION['member_id']))
      {
        $content_login = '
          <form method="post" action="/food/member_form.php" id="edit_profile"></form>
          <form method="post" action="/food/index.php" id="logout"></form>
          <div id="user_menu">
            <img id="user_image" alt="user image" src="//www.gravatar.com/avatar/'.$_SESSION['gravatar_hash'].'?s=64&amp;d=mm&amp;r=PG" class="avatar avatar-64 photo" height="64" width="64" />
            <ul id="user_actions">
              <li id="user_menu_identity"><div class="display-name">'.$_SESSION['show_name'].'</div></li>
              <li id="user_menu_logout">
                <input type="submit" form="edit_profile" value="Edit profile">
                <input type="hidden" name="action" value="logout" form="logout">
                <input type="submit" form="logout" value="Log out">
              </li>
            </ul>
          </div>';
      }
    else
      {
        $content_login = '
          <form method="post" action="/food/index.php" id="login"></form>
          <form method="get" action="/food/member_form.php" id="sign_up"></form>
          <div id="user_menu">
            <img id="user_image" alt="utility image" src="'.DIR_GRAPHICS.'gear.png" class="avatar avatar-64 photo" height="64" width="64" />
            <input type="hidden" name="action" value="login" form="login">
            <ul id="user_actions">
              <li id="login_username"><input type="text" placeholder="Username" name="username" form="login"></li>
              <li id="login_password"><input type="password" placeholder="Password" name="password" form="login"></li>
              <li id="login_new_account">
                <input type="submit" form="login" value="Login">
                <input type="submit" value="Sign up now!" form="sign_up">
              </li>
            </ul>
          </div>';
      }
    return $content_login;
  }

function wordpress_login ($member_id, $auth_types)
  {
    // We have skipped the Wordpress login checks, but since we are logged-in, we must set the wordpress session
    // This file includes everything required to set the session through wordpress
    @include_once (FILE_PATH.WORDPRESS_CONFIG);
    @wp_set_auth_cookie($_SESSION['member_id'], '', '');
    @do_action('wp_login', $_SESSION['member_id'], $_SESSION['username']);
    // Now make sure the wordpress database is correctly configured for this member
    // Open a database connection to the Wordpress database
    $wp_connection = @mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Couldn't connect: \n".mysql_error());
    $wp_db = @mysql_select_db(DB_NAME, $wp_connection) or die(mysql_error());
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
        user_id="'.mysql_real_escape_string($member_id).'"
        AND meta_key="wp_capabilities"';
    $result_get_capabilities = @mysql_query($query_get_capabilities, $wp_connection) or die(debug_print ("ERROR: 790223 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ( $row = mysql_fetch_array($result_get_capabilities) )
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
            user_id="'.mysql_real_escape_string($member_id).'",
            meta_key="wp_capabilities",
            meta_value="'.mysql_real_escape_string($wp_capabilities).'"';
        $result_set_capabilities = @mysql_query($query_set_capabilities, $wp_connection) or die(debug_print ("ERROR: 742943 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
        user_id="'.mysql_real_escape_string($member_id).'"
        AND meta_key="wp_user_level"';
    $result_get_user_level = @mysql_query($query_get_user_level, $wp_connection) or die(debug_print ("ERROR: 939823 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ( $row = mysql_fetch_array($result_get_user_level) )
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
            user_id="'.mysql_real_escape_string($member_id).'",
            meta_key="wp_user_level",
            meta_value="'.mysql_real_escape_string($wp_user_level).'"';
        $result_set_user_level = @mysql_query($query_set_user_level, $wp_connection) or die(debug_print ("ERROR: 437956 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
      }
    // SET GROUPS FOR WORDPRESS
    // First remove all groups for this member -- except "registered"
    // This ensures the member's groups will be correctly updated at each login
    $query_remove_groups = '
      DELETE FROM
        wp_groups_user_group
      WHERE
        user_id="'.mysql_real_escape_string($member_id).'"
        AND group_id != 1';
    $result_remove_groups = @mysql_query($query_remove_groups, $wp_connection) or die(debug_print ("ERROR: 572923 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
                user_id="'.mysql_real_escape_string($member_id).'",
                group_id="'.mysql_real_escape_string($key).'"';
            $result_add_group = @mysql_query($query_add_group, $wp_connection) or die(debug_print ("ERROR: 673023 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
          }
      }
  }

function wordpress_logout ()
  {
    // This file includes everything required to logout through wordpress
    @include_once (FILE_PATH.WORDPRESS_CONFIG);
    @wp_logout();
  }
?>
