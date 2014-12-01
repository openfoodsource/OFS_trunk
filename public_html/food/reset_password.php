<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone


$message = '';

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
              AND (first_name="'.mysql_real_escape_string ($first_name).'"
                OR first_name_2="'.mysql_real_escape_string ($first_name).'")';
            $and_last_name = '
              AND (last_name="'.mysql_real_escape_string ($last_name).'"
                OR last_name_2="'.mysql_real_escape_string ($last_name).'")';
            $matches ++;
          }
        if (strlen ($email_address) > 2)
          {
            $and_email_address = '
              AND (email_address="'.mysql_real_escape_string ($email_address).'"
                OR email_address_2="'.mysql_real_escape_string ($email_address).'")';
            $matches ++;
          }
        if (strlen ($username) > 2)
          {
            $and_username = '
              AND username="'.mysql_real_escape_string ($username).'"';
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
        $result = @mysql_query($query_check, $connection) or die(mysql_error());
        $num_rows = mysql_num_rows($result);
        if ($num_rows == 1 && $matches >= 2)
          {
            $email_array = array ();
            $row = mysql_fetch_array($result);
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
                  password = MD5("'.mysql_real_escape_string ($password).'")
                WHERE
                  username = "'.mysql_real_escape_string ($valid_username).'"';
            $result = mysql_query ($query_update, $connection) or die(mysql_errno());
            $message =
              'Account security notice:

                The password for an account registered with this email address
                has been reset from the website at '.DOMAIN_NAME.'
                Username: '.$valid_username.'
                The new password is: '.$password;
            mail ( $valid_email, 'Updated account info for '.DOMAIN_NAME, $message, "from: ".MEMBERSHIP_EMAIL);
            header( 'refresh: 7; url='.PATH );
            $display_password .= '
              <table width="50%" align="center" cellspacing="5">
                <tr>
                  <td><p style="font-size:1.2em">An email has been sent to the validated address.
                    If you do not receive it, contact '.MEMBERSHIP_EMAIL.'</p>
                  <p style="font-size:1.2em">In a few seconds, you will be redirected to the main page.</p></td>
                </tr>
              </table>';
            $page_title_html = '<span class="title">Member Resources</span>';
            $page_subtitle_html = '<span class="subtitle">Password Successfully Reset</span>';
            $page_title = 'Member Resources: Password Successfully Reset';
            $page_tab = 'member_panel';
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
          $message = '<p style="font-size:1.2em;color:#700;">Sorry... the information you submitted did not validate.</p>';
          }
      }
    if ( $_POST['form_data'] != 'true' )
      // Form data was not posted or was invalid, so show the form for input
      {
        $display_password .= '
          <form method="post" action="'.$_SERVER['SCRIPT_NAME'].'" name="change_password">
          <table width="50%" align="center" cellspacing="5">
            <tr>
              <td colspan="3">'.$message.'<p style="color:#462">In order to reset your password, you must correctly
                enter two of the three pieces of information below.  Then a new password will be
                e-mailed to you.</p><p style="color:#462">For security purposes, you will not be told which information
                is incorrect.</p>
                <p style="color:#462">Because of possible email delays, please allow up to an hour to receive your new password.</td>
            </tr>
            <tr>
              <td align="right" style="padding-bottom:1em;"><b>Username</b>:</td>
              <td align="left" colspan="2" style="padding-bottom:1em;"><input type="input" name="username" size="25" placeholder="username" maxlength="20"></td>
            </tr>
            <tr>
              <td align="right" style="padding-bottom:1em;"><b>Email Address</b>:</td>
              <td align="left" colspan="2" style="padding-bottom:1em;"><input type="text" name="email_address" size="25" placeholder="email@site.com" maxlength="50"></td>
            </tr>
            <tr>
              <td align="right" rowspan="2" style="padding-bottom:1em;"><b>Full Name</b>:</td>
              <td align="left" width="10"><input type="input" name="first_name" size="25" maxlength="25" placeholder="first name" onClick="javascript:this.focus();this.select();"></td>
              <td valign="middle" rowspan="2" align="left" style="padding-bottom:1em;"> Both required</td>
            </tr>
            <tr>
              <td align="left" width="10" style="padding-bottom:1em;"><input type="input" name="last_name" size="25" maxlength="25" placeholder="last name" onClick="javascript:this.focus();this.select();"></td>
           </tr>
            <tr>
              <td colspan="3" align="center"><input type="hidden" name="form_data" value="true">
                <input type="submit" name="submit" value="Send New Password"></td>
            </tr>
          </table>
          </form>';
        $page_title_html = '<span class="title">Member Resources</span>';
        $page_subtitle_html = '<span class="subtitle">Identity Validation</span>';
        $page_title = 'Member Resources: Identity Validation';
        $page_tab = 'member_panel';
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
        if($_SESSION['member_id'] && $old_password && $new_password1 && $new_password2)
          {
            // Check that the new passwords match
            if ( $new_password1 != $new_password2 )
              {
                $message .= '<p style="font-size:1.2em;color:#700;">New passwords do not match.</p>';
              }
            // Check that the old password is correct
            $query_pw = '
              SELECT
                "true" AS valid_password
              FROM
                '.TABLE_MEMBER.'
              WHERE
                member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
                AND password = MD5("'.mysql_real_escape_string ($old_password).'")';
            $result = @mysql_query($query_pw, $connection) or die(mysql_error());
            $row = mysql_fetch_array($result);
            if ( $row['valid_password'] != 'true' && md5($old_password) != MD5_MASTER_PASSWORD)
              {
                $message .= '<p style="font-size:1.2em;color:#700;">Incorrect old password was provided.</p>';
              }
            if ($message == '')
              // Everything looks good, so go ahead and update the password
              {
                $query_update = '
                  UPDATE
                    '.TABLE_MEMBER.'
                  SET
                    password = MD5("'.mysql_real_escape_string ($new_password1).'")
                  WHERE
                    member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
                $result = mysql_query ($query_update, $connection) or die(mysql_errno());

                header( 'refresh: 7; url=panel_member.php' );
                $display_password .= '
                <table width="50%" align="center" cellspacing="5">
                  <tr>
                    <td><p style="font-size:1.2em">Your password has been updated. </p>
                    <p style="font-size:1.2em">In a few seconds, you will be redirected to the login page.</p></td>
                  </tr>
                </table>';
                $page_title_html = '<span class="title">Member Resources</span>';
                $page_subtitle_html = '<span class="subtitle">Successfully Changed Password</span>';
                $page_title = 'Member Resources: Password Successfully Changed';
                $page_tab = 'member_panel';
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
        $display_password .= '<form method="post" action="'.$_SERVER['SCRIPT_NAME'].'" name="change_password">';
        $display_password .= '
          <table width="50%" align="center" cellspacing="5">
            <tr>
              <td colspan="2">';
        if ($message)
          {
            $display_password .= $message.'<p style="font-size:1.2em;color:#700;">Please re-enter your information.</p>';
          }
        else
          {
            $display_password .= '<p style="font-size:1.2em">In order to change your password, please enter your old password and
              enter your new password twice for confirmation.</p>';
          }
        $display_password .= '
              </td>
            </tr>
            <tr>
              <td align="right"><b>Old Password</b>:</td>
              <td align="left"><input type="password" name="old_password" size="17" maxlength="20"></td>
            </tr>
            <tr>
              <td align="right"><b>New Password</b>:</td>
              <td align="left"><input type="password" name="new_password1" size="17" maxlength="25"></td>
            </tr>
            <tr>
              <td align="right"><b>New Password (confirm)</b>:</td>
              <td align="left"><input type="password" name="new_password2" size="17" maxlength="25"></td>
            </tr>
            <tr>
              <td colspan="2" align="right"><input type="hidden" name="form_data" value="true">
                <input type="submit" name="submit" value="Update"></td>
            </tr>
          </table>
          </form>';
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
?>