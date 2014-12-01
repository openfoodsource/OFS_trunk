<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.check_membership.php');

$membership_info = get_membership_info ($_SESSION['member_id']);
$membership_renewal = check_membership_renewal ($membership_info);
$membership_renewal_form = membership_renewal_form($membership_info['membership_type_id']);

// If not the first_call (i.e. after being clicked), tell javascript to close the window.
if ($_GET['first_call'] != 'true')
  {
    // First update the database
    renew_membership ($_SESSION['member_id'], $_POST['membership_type_id']);
    // Then close the window
    $javascript_close = ' onload="self.close();"';
  }

$membership_form = '
  <form action="'.$_SERVER['SCRIPT_NAME'].'" method="post">'.
  $membership_renewal_form['expire_message'].
  $membership_renewal_form['same_renewal_intro'].
  $membership_renewal_form['same_renewal'].
  $membership_renewal_form['changed_renewal_intro'].
  $membership_renewal_form['changed_renewal'].
  '<input type="hidden" name="update_membership" value="true">
  <input type="submit" name="submit" value="submit">
  </form>';

$page_specific_css .= '
<style type="text/css">
  .expire_message {
    color:#607045;
    margin:15px;
    }
  .same_renewal_intro,
  .changed_renewal_intro {
    color:#607045;
    font-weight:bold;
    margin:15px;
    }
  .same_renewal,
  .changed_renewal {
    font-weight:bold;
    padding-left:50px;
    margin:15px 5px 5px 5px;
    }
  .same_renewal_desc,
  .changed_renewal_desc {
    font-style:italic;
    padding-left:100px;
    margin:5px 15px 15px 15px;
    }
  input[type=submit] {
    margin:20px 50px;
    }
</style>';





// This is a stand-alone page -- not including the template_header -- so build it manually
$content = '<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link href="'.PATH.'stylesheet.css" rel="stylesheet" type="text/css">
    <link href="'.PATH.'delivery_dropdown.css" rel="stylesheet" type="text/css">';

// Include any page-specific CSS directives
if ($page_specific_css)
  {
    $content .= '
    '.$page_specific_css;
  }

$content .= '
    <script src="'.PATH.'ajax/jquery.js" type="text/javascript"></script>
    <script src="'.PATH.'ajax/jquery-ui.js" type="text/javascript"></script>
  </head>
  <body'.$javascript_close.' lang="en-us">
    '.$membership_form.'
  </body>
</html>';

// echo $content;