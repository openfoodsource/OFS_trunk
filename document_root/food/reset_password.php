<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone

if($_GET['display_as'] == 'popup')
  {
    $display_as_popup = true;
  }

$message = '';
$error_array = array();
$notice_array = array();

// Rather than use the check_valid_user function, we need to trap the result
if ( ! $_SESSION['member_id'] )
  // The user is not valid, so provide a form to reset and send a new password by email
  {
    if ( $_POST['form_data'] == 'true' )
      // Validate the information and take appropriate action
      {
        $username = $_POST['username'];
        $email_address = $_POST['email_address'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $full_name = $_POST['first_name'].' '.$_POST['last_name'];
        // Preset variables
        $and_first_name = '';
        $and_last_name = '';
        $and_email_address = '';
        $and_username = '';
        $matches = 0;
        if (strlen ($first_name) > 0 && strlen ($last_name) > 0)
          {
            $and_first_name = '
              AND (first_name="'.mysqli_real_escape_string ($connection, $first_name).'"
                OR first_name_2="'.mysqli_real_escape_string ($connection, $first_name).'")';
            $and_last_name = '
              AND (last_name="'.mysqli_real_escape_string ($connection, $last_name).'"
                OR last_name_2="'.mysqli_real_escape_string ($connection, $last_name).'")';
            $matches ++;
          }
        if (strlen ($email_address) > 2)
          {
            $and_email_address = '
              AND (email_address="'.mysqli_real_escape_string ($connection, $email_address).'"
                OR email_address_2="'.mysqli_real_escape_string ($connection, $email_address).'")';
            $matches ++;
          }
        if (strlen ($username) > 2)
          {
            $and_username = '
              AND username="'.mysqli_real_escape_string ($connection, $username).'"';
            $matches ++;
          }
        // Check consistency between username and email_address
        $query_check = '
          SELECT
            username,
            email_address,
            email_address_2
          FROM
            '.TABLE_MEMBER.'
          WHERE
            1 /* base value to AND against */
            '.$and_first_name.'
            '.$and_last_name.'
            '.$and_email_address.'
            '.$and_username;
        $result = @mysqli_query ($connection, $query_check) or die (debug_print ("ERROR: 421432 ", array ($query_check, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $num_rows = mysqli_num_rows ($result);
        if ($num_rows == 1 && $matches >= 2)
          {
            $email_array = array ();
            $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
            $valid_username = $row['username'];
            if ($row['email_address']) array_push ($email_array, $row['email_address']);
            if ($row['email_address_2']) array_push ($email_array, $row['email_address_2']);
            $valid_email = implode (',', $email_array);
          }
        if ( strlen ($valid_username) > 0 )
        // Everything looks good, send the new password to the validated email address.
          {
            // Generate new password
            $chars = "ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";
            $password = '' ;
            while (strlen ($password) <= rand(5,8))
              {
                $password .= substr($chars, rand(0,57), 1);
              }
            $query_update = '
              UPDATE
                '.TABLE_MEMBER.'
                SET
                  password = MD5("'.mysqli_real_escape_string ($connection, $password).'")
                WHERE
                  username = "'.mysqli_real_escape_string ($connection, $valid_username).'"';
            $result = mysqli_query ($connection, $query_update) or die (debug_print ("ERROR: 262562 ", array ($query_update, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            // Need to break DOMAIN_NAME into an array of separate names so we can use the first element
            $domain_names = preg_split("/[\n\r]+/", DOMAIN_NAME);
            $email_message =
              'Account security notice:

                The password for an account registered with this email address
                has been reset from the website at '.($domain_names[0]).'
                Username: '.$valid_username.'
                The new password is: '.$password;
            mail ( $valid_email, 'Updated account info for '.($domain_names[0]), $email_message, "from: ".MEMBERSHIP_EMAIL);
            $display_password .= '
                  <div class="modal_message">An email has been sent to the validated address. If you do
                  not receive it, contact '.MEMBERSHIP_EMAIL.'.</div>';
            $page_title_html = '<span class="title">Member Resources</span>';
            $page_subtitle_html = '<span class="subtitle">Password Successfully Reset</span>';
            $page_title = 'Member Resources: Password Successfully Reset';
            $page_tab = 'member_panel';
            $page_specific_css = '
              .modal_message {
                font-size:2rem;
                color:#888;
                }';
            if ($display_as_popup == true)
              {
                $modal_action = 'just_close(6000)';
              }
            include("template_header.php");
            echo '
              <!-- CONTENT BEGINS HERE -->
              '.$display_password.'
              <!-- CONTENT ENDS HERE -->';
            include("template_footer.php");
            exit;
          }
        else
          // Information did not validate, so return to the form
          {
          $_POST['form_data'] = 'false';
          array_push ($error_array, 'The information provided does not match any known account.');;
          }
      }
    if ( $_POST['form_data'] != 'true' )
      // Form data was not posted or was invalid, so show the form for input
      {
        $display_errors = display_alert('notice', 'You may try another combination and resubmit.', $error_array);
        $display_password .= '
          <form action="'.$_SERVER['SCRIPT_NAME'].($display_as_popup == true ? '?display_as=popup' : '').'" name="change_password" id="change_password" method="post">'.
            $display_errors.'
            <div class="form_buttons">
              <button type="submit" name="action" id="action" value="Send">Send</button>
            </div>
            <fieldset class="reset_password grouping_block">
              <legend>Reset Password</legend>
              <div class="note">
                <p>In order to reset your password, you must correctly enter two of the three pieces of information below
                (Username and E-Mail, Username and Full Name, or E-Mail and Full Name). Then a new password will be e-mailed to you.</p>
                <p>For security reasons, you will not be told if any information is incorrect.</p>
                <p>Please allow up to an hour to receive your new password.</p>
              </div>
              <div class="input_block_group">
                <div class="input_block username">
                  <label for="username">Username</label>
                  <input type="text" id="username" name="username">
                </div>
              </div>
              <div class="input_block_group">
                <div class="input_block email_address">
                  <label for="email_address">E-Mail Address</label>
                  <input type="text" id="email_address" name="email_address">
                </div>
              </div>
              <div class="input_block_group full_name">
                <div class="input_block first_name">
                  <label for="first_name">First Name</label>
                  <input type="text" id="first_name" name="first_name">
                </div>
                <div class="input_block last_name">
                  <label for="last_name">Last Name</label>
                  <input type="text" id="last_name" name="last_name">
                  <input type="hidden" name="form_data" value="true">
                </div>
              </div>
            </fieldset>
          </form>';
        $page_title_html = '<span class="title">Member Resources</span>';
        $page_subtitle_html = '<span class="subtitle">Identity Validation</span>';
        $page_title = 'Member Resources: Identity Validation';
        $page_tab = 'member_panel';
        $page_specific_css = '
          /* Styles for submit/reset/clear/etc. form buttons */
          .form_buttons {
            position:fixed;
            left:10px;
            bottom:10px;
            }
          .form_buttons button {
            display:block;
            clear:both;
            width:5em;
            margin-bottom:2em;
            }
          /* Set fieldset colors */
          fieldset.reset_password {
            background-color:#f8f4f0;
            width:80%;
            text-align:center;
            }
          fieldset.reset_password legend {
            background-color:#f8f4f0;
            }
          .grouping_block .input_block {
            }
          .input_block_group {
            display:block;
            margin:0.75rem 0;
            text-align:center;
            }
          .input_block.username,
          .input_block.email_address,
          .input_block.first_name,
          .input_block.last_name {
            display:inline-block;
            }
          .first_name::after {
            content:" + ";
            }';
        include("template_header.php");
        echo '
          <!-- CONTENT BEGINS HERE -->
          '.$display_password.'
          <!-- CONTENT ENDS HERE -->';
        include("template_footer.php");
      }
  }
else
  // The user is already logged in, so provide a form to change the password
  {
    if ( $_POST['form_data'] == 'true' )
      // Validate the password information and take appropriate action
      {
        $old_password = $_POST['old_password'];
        $new_password1 = $_POST['new_password1'];
        $new_password2 = $_POST['new_password2'];
        // Make sure everything is filled in
        if($_SESSION['member_id'] && ($old_password || $new_password1 || $new_password2))
          {
            // Check that the new passwords match
            if ( $new_password1 != $new_password2 )
              {
                array_push ($error_array, 'New passwords do not match.');
              }
            else
              {
                $password_strength = test_password ($new_password1);
                array_push ($notice_array, 'New password strength: '.$password_strength);
                if ($password_strength < MIN_PASSWORD_STRENGTH)
                  {
                    array_push ($error_array, 'Password strength ('.$password_strength.') is too weak.');
                    array_push ($error_array, 'Minimum password strength is: '.MIN_PASSWORD_STRENGTH);
                  }
              }
            // Check that the old password is correct
            $query_pw = '
              SELECT
                "true" AS valid_password
              FROM
                '.TABLE_MEMBER.'
              WHERE
                member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"
                AND password = MD5("'.mysqli_real_escape_string ($connection, $old_password).'")';
            $result = @mysqli_query ($connection, $query_pw) or die (debug_print ("ERROR: 562354 ", array ($query_pw, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
            if ( $row['valid_password'] != 'true' && md5($old_password) != MD5_MASTER_PASSWORD)
              {
                array_push ($error_array, 'Incorrect value for old password.');
              }
            if (count ($error_array) == 0)
              // Everything looks good, so go ahead and update the password
              {
                $query_update = '
                  UPDATE
                    '.TABLE_MEMBER.'
                  SET
                    password = MD5("'.mysqli_real_escape_string ($connection, $new_password1).'")
                  WHERE
                    member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"';
                $result = mysqli_query ($connection, $query_update) or die (debug_print ("ERROR: 137864 ", array ($query_update, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
                $display_notices = display_alert('notice', '', $notice_array);
                $display_password .= $display_notices.'
                    <div class="modal_message">Your password has been updated.</div>';
                $page_title_html = '<span class="title">Member Resources</span>';
                $page_subtitle_html = '<span class="subtitle">Successfully Changed Password</span>';
                $page_title = 'Member Resources: Password Successfully Changed';
                $page_tab = 'member_panel';
                $page_specific_css = '
                  <style type="text/css">
                  .modal_message {
                    font-size:2rem;
                    color:#888;
                    }
                  </style>';
                if ($display_as_popup == true)
                  {
                    $modal_action = 'just_close(6000)';
                  }
                include("template_header.php");
                echo '
                  <!-- CONTENT BEGINS HERE -->
                  '.$display_password.'
                  <!-- CONTENT ENDS HERE -->';
                include("template_footer.php");
                exit;
              }
            else
              // There was an error, so return to the form
              {
                $_POST['form_data'] = 'false';
              }
          }
        else
          {
            $_POST['form_data'] = 'false';
          }
      }
    if ( $_POST['form_data'] != 'true' )
      // Form data was not posted or was invalid, so show the form for input
      {
        $display_errors = display_alert('error', 'Please correct the following problems and resubmit.', $error_array);
        $display_password .= '
          <form action="'.$_SERVER['SCRIPT_NAME'].($display_as_popup == true ? '?display_as=popup' : '').'" name="change_password" id="change_password" method="post">'.
            $display_errors.'
            <div class="form_buttons">
              <button type="submit" name="action" id="action" value="Change">Change</button>
            </div>
            <fieldset class="change_password grouping_block">
              <legend>Change Password</legend>
              <div class="note">
                <p>In order to change your password, please enter your old password and
                enter your new password twice for confirmation.</p>
              </div>
              <div class="input_block old_password">
                <label for="old_password">Old Password</label>
                <input type="password" id="old_password" name="old_password">
              </div>
              <div class="input_block new_password1">
                <label for="new_password1">New Password</label>
                <input type="password" id="new_password1" name="new_password1">
              </div>
              <div class="input_block new_password2">
                <label for="new_password2">New Password (confirm)</label>
                <input type="password" id="new_password2" name="new_password2">
                <input type="hidden" name="form_data" value="true">
              </div>
            </fieldset>
          </form>';
        $page_specific_css = '
          <style type="text/css">
          /* Styles for submit/reset/clear/etc. form buttons */
          .form_buttons {
            position:fixed;
            left:10px;
            bottom:10px;
            }
          .form_buttons button {
            display:block;
            clear:both;
            width:5em;
            margin-bottom:2em;
            }
          /* Set fieldset colors */
          fieldset.change_password {
            background-color:#f8f4f0;
            width:80%;
            text-align:center;
            }
          fieldset.change_password legend {
            background-color:#f8f4f0;
            }
          .grouping_block .input_block {
            }
          .input_block.old_password,
          .input_block.new_password1,
          .input_block.new_password2 {
            display:block;
            margin:0.5rem auto;
            width:10rem;
            }
          </style>';
        $page_title_html = '<span class="title">Member Resources</span>';
        $page_subtitle_html = '<span class="subtitle">Change Password</span>';
        $page_title = 'Member Resources: Change Password';
        $page_tab = 'member_panel';
        include("template_header.php");
        echo '
          <!-- CONTENT BEGINS HERE -->
          '.$display_password.'
          <!-- CONTENT ENDS HERE -->';
        include("template_footer.php");
      }
  }
