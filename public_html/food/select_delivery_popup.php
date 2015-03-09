<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.get_delivery_codes_list.php');

// If not the first_call (i.e. after being clicked), tell javascript to close the window.
if ($_GET['first_call'] != 'true')
  {
    $modal_action = 'just_close';
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
  <link href="'.PATH.'stylesheet.css" rel="stylesheet" type="text/css">
  <link href="'.PATH.'delivery_dropdown.css" rel="stylesheet" type="text/css">
  <style type="text/css">
  body {
    font-size:87%;
    }
  /* OVERRIDE THE DROPDOWN CLOSURE FOR MOBILE DEVICES */
  #delivery_dropdown {
    position:static;
    height:auto;
    width:100% !important;
    overflow:hidden;
    margin:0px;
    border:0;
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
  #delivery_select ul {
    margin-left:0;
    }
  </style>';

// This is ALWAYS a popup
$display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$delivery_codes_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
