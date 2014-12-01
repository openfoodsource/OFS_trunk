<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.get_delivery_codes_list.php');

// If not the first_call (i.e. after being clicked), tell javascript to close the window.
if ($_GET['first_call'] != 'true')
  {
    $javascript_close = ' onload="self.close();"';
  }
// Set content_top to show basket selector...
$delivery_codes_list .= get_delivery_codes_list (array (
  'action' => $_GET['action'],
  'member_id' => $_SESSION['member_id'],
  'delivery_id' => ActiveCycle::delivery_id(),
  'site_id' => $_GET['site_id'],
  'delivery_type' => $_GET['delivery_type']
  ));

// Add styles to override delivery location dropdown
$page_specific_css .= '
  <style type="text/css">
  body {
    font-size:87%;
    }
  /* OVERRIDE THE DROPDOWN CLOSURE FOR MOBILE DEVICES */
  #delivery_dropdown {
    position:static;
    height:auto;
    width:100%;
    overflow:hidden;
    }
  #delivery_dropdown:hover {
    width:100%;
    }
  #delivery_select {
    width:100%;
    height:auto;
    }
  #delivery_dropdown:hover {
    height:auto;
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
    '.$delivery_codes_list.'
  </body>
</html>';

echo $content;