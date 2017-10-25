<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');
include_once ('func.check_membership.php');

// This is the new member landing page
// It will allow login/logout, handle membership renewals, and site messages.

// if (! [logged_in])
//   {
//     [provide login window]
//   }
// elseif ([membership expired])
//   {
//     [provide membership renewal window]
//   }
// elseif (redirect)
//   {
//     [provide redirect]
//   }
// else
//   {
//     [provide site message(s)]
//     [provide order cycle information]
//   }

// If being asked to logout, then do that first
if ($_REQUEST['action'] == 'logout')
  {
    session_destroy();
    unset ($_SESSION);
    if (WORDPRESS_ENABLED == true)
      {
        require ('wordpress_utilities.php');
        wordpress_logout ();
      }
    CurrentMember::clear_member_info();
    $page_title_html = '<span class="title">'.SITE_NAME.'</span>';
    $page_subtitle_html = '<span class="subtitle">Logout</span>';
    $page_title = 'Logout';
    $page_tab = 'login';
  }
// Check if the member is not already logged in
if ($_REQUEST['action'] == 'login' && ! $_SESSION['member_id'])
  {
    // Check if we already have a posted username/password combination
    if ($_POST['username'] && $_POST['password'])
      {
        $query_login = '
          SELECT
            member_id,
            username,
            pending,
            membership_discontinued
          FROM
            '.TABLE_MEMBER.'
          WHERE
            username = "'.mysqli_real_escape_string ($connection, $_POST['username']).'"
            AND
              (password = MD5("'.mysqli_real_escape_string ($connection, $_POST['password']).'")
              OR "'.MD5_MASTER_PASSWORD.'" = MD5("'.mysqli_real_escape_string ($connection, $_POST['password']).'"))
          LIMIT 1';
        $result_login = mysqli_query ($connection, $query_login) or die (debug_print ("ERROR: 703410 ", array ($query_login, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        if ($row_login = mysqli_fetch_array ($result_login, MYSQLI_ASSOC))
          {
            $member_id = $row_login['member_id'];
            // Check for a valid login
            if ($row_login['pending'] == 0 && $row_login['membership_discontinued'] == 0)
              {
                // We are good to login
                // Capture any information we are holding in the SESSION
                // These will be the only elements retained into the new session
                $request_uri = isset ($_SESSION['REQUEST_URI']) ? $_SESSION['REQUEST_URI'] : PATH.'panel_shopping.php';
                $_POST = $_SESSION['_POST'];
                $_GET = $_SESSION['_GET'];
                session_destroy();
                session_start ();
                if (count($_GET) > 0) $_SESSION['_GET'] = $_GET;
                if (count($_POST) > 0) $_SESSION['_POST'] = $_POST;
                // Then start a session and set the basic SESSION veraiables.. things that can prevent any
                // unnecessary database access later
                $query = '
                  SELECT
                    '.TABLE_MEMBER.'.member_id,
                    '.TABLE_MEMBER.'.username,
                    '.TABLE_MEMBER.'.password,
                    '.TABLE_MEMBER.'.auth_type,
                    '.TABLE_MEMBER.'.preferred_name,
                    '.TABLE_MEMBER.'.email_address,
                    '.TABLE_MEMBER.'.pending,
                    '.TABLE_PRODUCER.'.producer_id
                  FROM
                    '.TABLE_MEMBER.'
                  LEFT JOIN '.TABLE_PRODUCER.' USING(member_id)
                  WHERE
                    member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';
                $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 789089 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
                while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
                  {
                    $_SESSION['member_id'] = $row['member_id'];
                    $_SESSION['producer_id_you'] = $row['producer_id'];
                    $_SESSION['show_name'] = $row['preferred_name'];
                    $username = $row['username'];
                    $_SESSION['username'] = $username;
                    $member_id = $row['member_id'];
                    // Following is needed for gravatar (c.f. http://en.gravatar.com/site/implement/hash/)
                    $gravatar_hash = md5( strtolower( trim( $row['email_address'] ) ) );
                    $_SESSION['gravatar_hash'] = $gravatar_hash;
                    // Following values are used for the wordpress interface
                    $password_hash = $row['password'];
                    $auth_types = $row['auth_type'];
                  }
                // Save the membership/renewal information into the SESSION to avoid gathering it again
                $membership_info = get_membership_info ($member_id);
                $_SESSION['renewal_info'] = check_membership_renewal ($membership_info);
                // Enable sumultaneous logging in to wordpress
                if (WORDPRESS_ENABLED == true)
                  {
                    // Wordpress needs these to be arrays
                    $_GET = array ();
                    $_POST = array ();
                    require ('wordpress_utilities.php');
                    wordpress_login ($member_id, $auth_types);
                  }
                // If transferring to another page, then go do that...
                if ($request_uri)
                  {
                    header('Location: '.$request_uri);
                    exit(0);
                  }
              }
            elseif ($row_login['membership_discontinued'] == 1)
              {
                $error_message = 'Your membership has been suspended. Please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> if you have any questions.';
              }
            elseif ($row_login['pending'] == 1)
              {
                $error_message = 'Your membership is pending. You will be unable to log in until it has been approved. Please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> if you have any questions.';
              }
          }
        else
          {
            $error_message = 'Invalid username or password. Please re-enter your information to try again.';
            // Wait a few seconds to help thwart brute-force password attacks
            sleep (3);
          }
      }
    if (! $_SESSION['member_id'])
      {
        $form_block .= '
          <form class="login" method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=login" name="login">
            <fieldset>
              <button type="submit" name="submit" tabindex="3">go</button>
              <label>Username</label>
              <input id="load_target" type="text" name="username" placeholder="Username" tabindex="1">
              <label>Password</label>
              <input type="password" name="password" placeholder="Password" tabindex="2">
              <label>
                <a href="reset_password.php" tabindex="4">Forgot your password?</a>
              <label>
              </label>
                <a href="member_form.php" tabindex="5">Register as a new member...</a>
              </label>
            </fieldset>
          </form>';
        $content .= 
      ($error_message ? '
        <div class="error_message">
          <p class="message">'.$error_message.'</p>
        </div>' : '').'
      '.$form_block;
      }
    $page_title_html = '<span class="title">'.SITE_NAME.'</span>';
    $page_subtitle_html = '<span class="subtitle">Login</span>';
    $page_title = 'Login';
    $page_tab = 'login';
  }
else
  {
    // Not login and not logged in, so show basic "info" screen
    $content .= 
      ($error_message ? '<div class="error_message">'.$error_message.'</div>' : '').'
      <div id="info_container" style="background-image: url('.DIR_GRAPHICS.'info_background.png); background-repeat: no-repeat; width:100%;background-position:top right;min-height:401px;background-size:870px 410px;">
        <h3 style="clear; padding-top:220px;">Information Links</h3>
        <ul class="info_links">
          <li><a href="'.PATH.'locations.php">Food Pickup/Delivery Locations</a></li>
          <li><a href="'.PATH.'prdcr_list.php">Active Producers</a></li>
          <li><a href="'.PATH.'contact.php">Contacts</a></li>
          <li><a href="'.PATH.'category_list.php?display_as=grid">Current Product Listings</a></li>
          <li><a href="'.PATH.'member_form.php">Membership Application Form</a></li>
          <li><a href="'.PATH.'index.php?action=login">Login to Order</a></li>
        </ul>
      </div>
      <div style="clear:both;"></div>';
    $page_title_html = '<span class="title">'.SITE_NAME.'</span>';
    $page_subtitle_html = '<span class="subtitle">Information</span>';
    $page_title = 'Information';
    $page_tab = 'login';
  }

$page_specific_javascript = '';
$page_specific_css .= '
<style type="text/css">
.full_screen {
  width:100%;
  height:100%;
  position:absolute;
  left:0;
  top:0;
  background-color:#000;
  opacity:0.7;
  filter:alpha(opacity=70)
  }
.inner_window {
  width:70%;
  height:70%;
  margin:15%;
  position:absolute;
  left:0;
  top:0;
  padding:10px;
  background-color:#fff;
  overflow-y:auto;
  box-shadow: 3px 3px 8px 5px #000;
  background-color:#eee;
  }
fieldset {
  width:425px;
  height:175px;
  margin:auto;
  padding:0px;
  padding-left:10px;
  border:1px solid #b7a777;
  border-bottom-right-radius: 100px;
  border-top-right-radius: 100px;
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
  }
label {
  display:block;
  float:left;
  font-size:20px;
  width:200px;
  font-style:italic;
  color:#87753e;
  }
input {
  display:block;
  float:left;
  height:35px;
  width:200px;
  color:#58673f;
  font-size:20px;
  border:1px solid #87753e;
  }
/* Style the big round button */
button::-moz-focus-inner {  
  border:0;
  }
button {
  border: 1px solid #b7a777;
  border-radius: 65px;
  box-shadow: -25px -25px 35px #97a97b inset;
  color: #758954;
  display: block;
  float: right;
  font-size: 40px;
  font-weight: bold;
  height: 130px;
  margin: 20px 25px;
  outline: 0 none;
  width: 130px;
  }
button:focus,
button:hover {
  box-shadow: -25px -25px 35px #758954 inset;
  color:#556a35;
  border:1px solid #87753e;
  }
fieldset a {
  display:block;
  clear:both;
  width:300px;
  }
label a {
  line-height:1;
  font-size: 50%;
  padding-top:10px;
  }
/* This is for wordpress 2013 */
input.search-field {
  float:right;
  }

</style>';

// $page_title_html = '<span class="title">'.SITE_NAME.'</span>';
// $page_subtitle_html = '<span class="subtitle">Login</span>';
// $page_title = 'Login';
// $page_tab = 'login';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
