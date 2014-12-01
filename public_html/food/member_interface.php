<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,member_admin');

require_once("classes/mi.class.php");
$content_members .= '<div align="center">';
$mi = new memberInterface;
switch ( $_GET[action] )
  {
    case 'add':
      $mi->buildAddMember();
      break;
    case 'checkMemberForm':
      $error_html = $mi->checkMemberForm();
      if (strlen ($error_html) > 0)
        {
          $mi->editUser($error_html);
        }
      break;
    case 'edit':
      $mi->editUser();
      break;
    case 'find':
      $mi->findForm();
      break;
    case 'displayUsers':
      $mi->findUsers();
      break;
  }
if ( !$_GET[action] )
  {
    $content_members .=  '
      <ul>';
    switch ( $_GET[action] )
      {
        default:
          $mi->mainMenu();
          break;
      }
    $content_members .=  '</ul>
      ';
  }
$content_members .=  '</div>';

$page_specific_css = '
  <style type="text/css">
    .member_id {
      font-size:180%;
      text-align:center;
      }
    .business_name {
      color: #007;
      }
    .email_address {
      }
    .username {
      color:#640;
      font-size:80%;
      font-family:"Lucida Console", monospace;
      }
    .business_name {
      }
  </style>';

$page_title_html = '<span class="title">Membership Information</span>';
$page_subtitle_html = '<span class="subtitle">Find/Edit Members</span>';
$page_title = 'Membership Information: Find/Edit Members';
$page_tab = 'member_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_members.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");


