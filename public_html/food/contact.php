<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone


$display_contact = $font.'
  <br>
  Please first check the "<a href="faq.php">How to Order & FAQ</a>" page to see if your question has already been addressed.<br>Thank you for your involvement in the food '.ORGANIZATION_TYPE.'.
  <br><br>
  <table>
    <tr>
      <td align="left">
        Questions about ordering online?
      </td>
      <td align="left">
        <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a>
      </td>
    </tr>
    <tr>
      <td align="left">
        Questions about ordering in general?
      </td>
      <td align="left">
        <a href="mailto:'.ORDER_EMAIL.'">'.ORDER_EMAIL.'</a>
      </td>
    </tr>
    <tr>
      <td align="left">
        Problems with your order from delivery day?
      </td>
      <td align="left">
        <a href="mailto:'.PROBLEMS_EMAIL.'">'.PROBLEMS_EMAIL.'</a>
      </td>
    </tr>
    <tr>
      <td align="left">
        Questions about your payment?
      </td>
      <td align="left">
        <a href="mailto:'.TREASURER_EMAIL.'">'.TREASURER_EMAIL.'</a>
      </td>
    </tr>
    <tr>
      <td align="left">
        Questions about your Membership?
      </td>
      <td align="left">
        <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a>
      </td>
    </tr>
    <tr>
      <td align="left">
        If you are a producer, send product updates to:
      </td>
      <td align="left">
        <a href="mailto:'.STANDARDS_EMAIL.'">'.STANDARDS_EMAIL.'</a>
      </td>
    </tr>
    <tr>
      <td align="left">
        Questions about the website?
      </td>
      <td align="left">
        <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a>
      </td>
    </tr>
    <tr>
      <td align="left">
        General Information
      </td>
      <td align="left">
        <a href="mailto:'.GENERAL_EMAIL.'">'.GENERAL_EMAIL.'</a>
      </td>
    </tr>
  </table>
</div>';

$page_title_html = '<span class="title">Member Resources</span>';
$page_subtitle_html = '<span class="subtitle">How To Contact Us With Questions</span>';
$page_title = 'Member Resources: How To Contact Us With Questions';
$page_tab = 'member_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_contact.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
