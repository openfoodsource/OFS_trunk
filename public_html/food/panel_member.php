<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

// So paypal_utilities knows this is a local request and not a paypal request
$not_from_paypal = true;
include_once ('paypal_utilities.php');
$basket_status = '';

// Do we need to post membership changes?
if (isset ($_POST['update_membership']) && $_POST['update_membership'] == 'true')
  {
    include_once ('func.check_membership.php');
    renew_membership ($_SESSION['member_id'], $_POST['membership_type_id']);
    // Now update our session membership values
    $membership_info = get_membership_info ($_SESSION['member_id']);
    $_SESSION['renewal_info'] = check_membership_renewal ($membership_info);
    // Make sure this function does not run again from the template_header.php
    $_POST['update_membership'] = 'false';
  }

/////////////// FINISH PRE-PROCESSING AND BEGIN PAGE GENERATION /////////////////

// Generate the display output
$display = '
  <div class="subpanel membership_info">
    <header>
      Membership Information
    </header>
    <ul class="grid membership_info">
      <li class="block block_33 membership_class"">
        <a class="popup_link" onclick="popup_src(\''.PATH.'update_membership.php?display_as=popup\', \'membership_renewal\', \'\', false);">
          <span>You are a </span><span class="title">'.$_SESSION['renewal_info']['membership_class'].'</span><span class="detail">(click to change)</span>
        </a>
      </li>
      <li class="block block_33 membership_description">
        <span class="title">Detail</span><span class="detail">'.$_SESSION['renewal_info']['membership_description'].'</span>
      </li>
      <li class="block block_33 membership_message">
        <span class="title">Progress</span><span class="detail">'.$_SESSION['renewal_info']['membership_message'].'</span>
      </li>
      <li class="block block_33 renewal_date">
        <span class="title">Next Renewal</span><span>'.date('F j, Y', strtotime($_SESSION['renewal_info']['standard_renewal_date'])).'</span>
      </li>
      <li class="block block_33">
        <a class="popup_link" onClick="popup_src(\''.PATH.'motd.php?display_as=popup\', \'motd\', \'\', false);">
          <span class="motd">View the Message of the Day</span>
        </a>
      </li>
    </ul>
  </div>
  <div class="subpanel member_resources">
    <header>
      Member Resources
    </header>
    <ul class="grid member_resources">
      <li class="block block_42">
        <a class="popup_link" onClick="popup_src(\''.PATH.'member_form.php?display_as=popup\', \'member_form\', \'\', false);">Update My Contact Information</a>
      </li>
      <li class="block block_42">
        <a class="popup_link" onClick="popup_src(\''.PATH.'reset_password.php?display_as=popup\', \'reset_password\', \'\', false);">Change My Password</a>
      </li>
      <li class="block block_42">
        <a class="block_link" href="locations.php">View Available Pickup/Delivery Locations</a>
      </li>
      <li class="block block_42">
        <a class="block_link" href="contact.php">How to Contact Us with Questions</a>
      </li>
      <li class="block block_42">
        <a class="block_link" href="faq.php">How to Order FAQ</a>
      </li>
      <li class="block block_42">
        <a class="popup_link" onClick="popup_src(\''.PATH.'producer_form.php?action=new_producer&display_as=popup\', \'member_form\', \'\', false);">New Producer Application Form</a>
      </li>
    </ul>
  </div>
  <div class="subpanel payment_options">
    <header>
      Make A Payment
    </header>
    <ul class="grid payment_options">'.
      (PAYPAL_ENABLED && $_SESSION['member_id'] ? 
        paypal_display_form (array (
          'form_id' => 'paypal_form2',
          'span1_content' => '<li class="block block_44"><span class="title">Pay with PayPal</span><span class="detail">Enter the amount at PayPal</span>',
          'span2_content' => '',
          'form_target' => 'paypal',
          'allow_editing' => false,
          'amount' => number_format (0, 2),
          'business' => PAYPAL_EMAIL,
          'item_name' => htmlentities (ORGANIZATION_ABBR.' '.$_SESSION['member_id'].' '.$_SESSION['show_name']),
          'notify_url' => BASE_URL.PATH.'paypal_utilities.php',
          'custom' => htmlentities ('member#'.$_SESSION['member_id']),
          'no_note' => '0',
          'cn' => 'Message:',
          'cpp_cart_border_color' => '#3f7300',
          'cpp_logo_image' => BASE_URL.DIR_GRAPHICS.'logo1_for_paypal.png',
          'return' => BASE_URL.PATH.'panel_member.php',
          'cancel_return' => BASE_URL.PATH.'panel_member.php',
          'rm' => '2',
          'cbt' => 'Return to '.SITE_NAME,
          'paypal_button_src' => 'https://www.paypal.com/en_US/i/btn/btn_buynow_SM.gif'
          )).'</li>'
        : '').'
        <li class="block block_44">
          <span class="title">Mail a check to</span>
          <span>'.SITE_MAILING_ADDR.'</span>
          <span class="detail">Indicate &quot;Member #'.$_SESSION['member_id'].'&quot; on payment</span>
        </li>
    </ul>
  </div>';

$page_specific_css = '
  .paypal_message {
    font-size:70%;
    margin:0.5em 0 1em;
    }
  .member_resources header {
    background-image:url("'.DIR_GRAPHICS.'status.png");
    }
  .membership_info header {
    background-image:url("'.DIR_GRAPHICS.'type.png");
    }
  iframe#simplemodal-iframe-reset_password html {
    min-height:100%;
    }
  iframe#simplemodal-iframe-reset_password html body {
    min-height:100%;
    }
  .fullscreen {
    display:table-cell;
    }';

$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Member Panel</span>';
$page_title = 'Member Panel';
$page_tab = 'member_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
