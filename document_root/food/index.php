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
                // Allow long query results for order_cycle_list and member_order_cycle_list
                $query = '
                  SET SESSION group_concat_max_len = 1000000;';
                $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 758902 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
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
                    '.TABLE_PRODUCER.'.producer_id,
                    '.TABLE_PRODUCER.'.business_name AS producer_business_name,
                    (SELECT GROUP_CONCAT(CONCAT(delivery_id))
                      FROM '.TABLE_ORDER_CYCLES.'
                      WHERE 1) AS delivery_id_list,
                    (SELECT GROUP_CONCAT(CONCAT(delivery_date))
                      FROM '.TABLE_ORDER_CYCLES.'
                      WHERE 1) AS delivery_date_list,
                    (SELECT GROUP_CONCAT(CONCAT(delivery_id))
                      FROM '.TABLE_ORDER_CYCLES.'
                      LEFT JOIN '.NEW_TABLE_BASKETS.' USING (delivery_id)
                      WHERE member_id = '.mysqli_real_escape_string ($connection, $member_id).') AS member_delivery_id_list
                  FROM
                    ('.TABLE_MEMBER.',
                    '.TABLE_ORDER_CYCLES.')
                  LEFT JOIN '.TABLE_PRODUCER.' USING(member_id)
                  WHERE
                    member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';
                $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 789089 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
                while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
                  {
                    $_SESSION['member_id'] = $row['member_id'];
                    $_SESSION['producer_id_you'] = $row['producer_id'];
                    $_SESSION['producer_business_name'] = $row['producer_business_name'];
                    $_SESSION['show_name'] = $row['preferred_name'];
                    // Create session arrays of delivery_ids <==> delivery_dates for all cycles
                    $_SESSION['delivery_id_array'] = array_combine (explode(',', $row['delivery_id_list']), explode(',', $row['delivery_date_list']));
                    // Create session arrays of delivery_ids <==> delivery_dates for this member
                    $_SESSION['customer_delivery_id_array'] = array ();
                    foreach (explode(',', $row['member_delivery_id_list']) as $key)
                      {
                        $_SESSION['customer_delivery_id_array'][$key] = $_SESSION['delivery_id_array'][$key];
                      }
                    $username = $row['username'];
                    $_SESSION['username'] = $username;
                    $member_id = $row['member_id'];
                    // Following is needed for gravatar (c.f. https://en.gravatar.com/site/implement/hash/)
                    $gravatar_hash = md5( strtolower( trim( $row['email_address'] ) ) );
                    $_SESSION['email_address'] = $row['email_address'];
                    $_SESSION['gravatar_hash'] = $gravatar_hash;
                    // Following values are used for the wordpress interface
                    $password_hash = $row['password'];
                    $auth_types = $row['auth_type'];
                    $_SESSION['auth_types'] = $auth_types;
                    // Convert an existing $_COOKIE['ofs_customer']['site_id'] into a $_SESSION['ofs_customer']['site_id'];
                    if (isset ($_COOKIE['ofs_customer']['site_id'])) $_SESSION['ofs_customer']['site_id'] = $_COOKIE['ofs_customer']['site_id'];
                    if (isset ($_COOKIE['ofs_customer']['delivery_type'])) $_SESSION['ofs_customer']['delivery_type'] = $_COOKIE['ofs_customer']['delivery_type'];
                  }
                // If this is a producer, then fill the producer_delivery_id_array
                $query = '
                  SELECT
                    DISTINCT(delivery_id) AS delivery_id,
                    delivery_date
                  FROM '.NEW_TABLE_BASKETS.'
                  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING (basket_id)
                  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING (product_id, product_version)
                  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING (delivery_id)
                  WHERE producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'"';
                $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 742902 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
                while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
                  {
                    $_SESSION['producer_delivery_id_array'][$row['delivery_id']] = $row['delivery_date'];
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
                    $_SESSION['wp_auth_okay'] = true; // Tell Wordpress it's okay to authenticate this user
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
          <form class="login" id="page_login" method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=login" name="login">
            <fieldset>
              <label>Username</label>
              <input type="text" class="text_field username" name="username" placeholder="Username" tabindex="1" autofocus>
              <label>Password</label>
              <input type="password" class="text_field password" name="password" placeholder="Password" tabindex="2">
              <div class="link">
                <a href="reset_password.php" tabindex="4">Forgot your password?</a>
              </div>
              <div class="link">
                <a href="member_form.php" tabindex="5">Register as a new member...</a>
              </div>
              <button type="submit" class="submit" name="submit" tabindex="3">Login</button>
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
      <div id="info_container">
        <h3>Information Links</h3>
        <ul class="info_links">
          <li><a href="'.PATH.'locations.php">Food Pickup/Delivery Locations</a></li>
          <li><a href="'.PATH.'producer_list.php">Active Producers</a></li>
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
  #info_container {
    background-image: url('.DIR_GRAPHICS.'info_background.png);
    background-repeat: no-repeat;
    width:100%;
    background-position:top right;
    min-height:401px;
    background-size:870px 410px;
    }
  #info_container > h3 {
    clear:both;
    padding-top:220px;
    }
  #page_login fieldset {
    font-size:100%;
    position:relative;
    padding:0;
    max-width:50rem;
    height:18rem;
    border-top-right-radius:9rem;
    border-bottom-right-radius:9rem;
    }
  #page_login .submit {
    font-size:100%;
    position:absolute;
    right:2rem;
    top:2rem;
    height:14rem;
    width:14rem;
    border-radius:8rem;
    background-color:#84b03e;
    box-shadow:-25px -25px 35px #5c6e40 inset;
    border: 1px solid rgba(0,64,0,0.5);
    }
  #page_login .submit:hover {
    background-color:#a4d04e;
    box-shadow:-25px -25px 35px #6c7e50 inset;
    }
  #page_login .submit:active {
    background-color:#74902e;
    box-shadow:-25px -25px 35px #3c4e20 inset;
    }
  #page_login .text_field {
    width:40%;
    float:left;
    clear:left;
    }
  #page_login label {
    display:block;
    float:left;
    clear:both;
    height:2rem;
    margin:0 0 0 1rem;
    padding:1rem 0 0 0;
    line-height:1.5rem;
    font-size:1.25rem;
    }
  #page_login input {
    height:2.5rem;
    padding:0 0.75rem;
    margin:0.25em 0 0 1rem;
    border-radius:1.25rem;
    border: 1px solid rgba(0,64,0,0.5);
    line-height:2.5rem;
    font-size:1.5rem;
    }
  #page_login input.password {
    margin-bottom:3rem;
    }
  #page_login .link {
    display:block;
    float:left;
    clear:left;
    height:2rem;
    line-height:1.5rem;
    font-size:1rem;
    margin:0.5rem 0 0 1rem;
    }
  #page_login .link a {
    border-bottom:0;
    }
  /* Full-size first -- then small-screen following next */
  @media screen and (max-width: 40em) {
    #page_login fieldset {
      border-top-right-radius:5px;
      border-bottom-left-radius:9rem;
      min-width:18rem;
      width:18rem;
      height:34rem;
      }
    #page_login .submit {
      top:18rem;
      }
    #page_login .text_field {
      width:80%;
      }
    #page_login input.password {
      margin-bottom:2rem;
      }
    #page_login .link {
      height:1.5rem;
      }
    }';

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
