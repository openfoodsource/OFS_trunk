<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.get_delivery_codes_list.php');

// Set content_top to show basket selector...
$delivery_codes_list = get_delivery_codes_list (array (
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

$page_specific_javascript = '
  <script src="'.PATH.'ajax/jquery.js" type="text/javascript"></script>
  <script src="'.PATH.'ajax/jquery-ui.js" type="text/javascript"></script>';

// Always display this page as a popup...
$display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$delivery_codes_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
