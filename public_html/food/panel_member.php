<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

// So paypal_utilities knows this is a local request and not a paypal request
$not_from_paypal = true;
include_once ('paypal_utilities.php');

// Do we need to post membership changes?
if ($_POST['update_membership'] == 'true')
  {
    include_once ('func.check_membership.php');
    renew_membership ($_SESSION['member_id'], $_POST['membership_type_id']);
    // Now update our session membership values
    $membership_info = get_membership_info ($_SESSION['member_id']);
    $_SESSION['renewal_info'] = check_membership_renewal ($membership_info);
    // Make sure this function does not run again from the template_header.php
    $_POST['update_membership'] = 'false';
  }

// Set up English grammar for ordering dates

$relative_text = '';
$close_suffix = '';
$open_suffix = '';

if ( strtotime (ActiveCycle::date_open_next()) < time ()  && strtotime (ActiveCycle::date_closed_next()) > time ())
  {
    $relative_text = 'Current&nbsp;';
  }
elseif ( strtotime (ActiveCycle::date_closed_next()) > time () )
  {
    $relative_text = 'Next&nbsp;';
  }
else // strtotime (ActiveCycle::delivery_date_next()) < time ()
  {
    $relative_text = 'Prior&nbsp;';
  }

if ( strtotime (ActiveCycle::date_open_next()) < time () )
  {
    $open_suffix = 'ed'; // Open[ed]
  }
else
  {
    $open_suffix = 's'; // Open[s]
  }

if ( strtotime (ActiveCycle::date_closed_next()) < time () )
  {
    $close_suffix = 'd'; // Close[d]
  }
else
  {
    $close_suffix = 's'; // Close[s]
  }

// echo "<pre>".print_r($_SESSION,true)."</pre>";

// Get basket status information
$query = '
  SELECT
    COUNT(product_id) AS basket_quantity,
    '.NEW_TABLE_BASKETS.'.basket_id
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
    AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string (ActiveCycle::delivery_id()).'
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 670342 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$basket_quantity = 0;
if ($row = mysql_fetch_object($result))
  {
    $basket_quantity = $row->basket_quantity;
    $basket_id = $row->basket_id;
  }

/////////////// FINISH PRE-PROCESSING AND BEGIN PAGE GENERATION /////////////////



// Generate the display output
$display .= '
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">
    <img src="'.DIR_GRAPHICS.'current.png" width="32" height="32" align="left" hspace="2" alt="Order"><br>
    <strong>'.$relative_text.'Order</strong>
        <ul class="fancyList1">
          <li><strong>Open'.$open_suffix.':</strong>&nbsp;'.date ('M&\n\b\s\p;j,&\n\b\s\p;g:i&\n\b\s\p;A&\n\b\s\p;(T)', strtotime (ActiveCycle::date_open_next())).'</li>
          <li><strong>Close'.$close_suffix.':</strong>&nbsp;'.date ('M&\n\b\s\p;j,&\n\b\s\p;g:i&\n\b\s\p;A&\n\b\s\p;(T)', strtotime (ActiveCycle::date_closed_next())).'</li>
          <li class="last_of_group"><strong>Delivery:</strong>&nbsp;'.date ('F&\n\b\s\p;j', strtotime (ActiveCycle::delivery_date_next())).'</li>
        </ul>
<!--
    <img src="'.DIR_GRAPHICS.'shopping.png" width="32" height="32" align="left" hspace="2" alt="Basket Status"><br>
    <strong>Basket Status</strong>
        <ul class="fancyList1">
          <li class="last_of_group">'.$basket_status.'</li>
        </ul>
-->
    <img src="'.DIR_GRAPHICS.'type.png" width="32" height="32" align="left" hspace="2" alt="Membership Type"><br>
    <strong>Membership Type</strong> [<a href="panel_member.php?update=membership">Change</a>]
        <ul class="fancyList1">
          <li><strong>'.$_SESSION['renewal_info']['membership_class'].':</strong> '.$_SESSION['renewal_info']['membership_description'].'<br><br></li>
          <li class="last_of_group">'.$_SESSION['renewal_info']['membership_message'].'</li>
        </ul>
    <img src="'.DIR_GRAPHICS.'time.png" width="32" height="32" align="left" hspace="2" alt="Information"><br>
    <strong>Next Renewal Date</strong>
        <ul class="fancyList1">
          <li class="last_of_group">'.date('F j, Y', strtotime($_SESSION['renewal_info']['standard_renewal_date'])).'</li>
        </ul>
      </td>
      <td align="left" width="50%">
        <img src="'.DIR_GRAPHICS.'status.png" width="32" height="32" align="left" hspace="2" alt="Member Resources"><br>
        <b>Member Resources</b>
        <ul class="fancyList1">
          <li><a href="locations.php">Food Pickup/Delivery Locations</a></li>
          <li><a href="contact.php">How to Contact Us with Questions</a></li>
          <li><a href="member_form.php">Update Membership Info.</a></li>
          <li><a href="reset_password.php">Change Password</a></li>
          <li><a href="faq.php">How to Order FAQ</a></li>
          <li class="last_of_group"><a href="producer_form.php?action=new_producer">New Producer Application Form</a></li>
        </ul>
        <img src="'.DIR_GRAPHICS.'money.png" width="32" height="32" align="left" hspace="2" alt="Payment Options"><br>
        <b>Payment Options</b>
        <ul class="fancyList1">'.
        // Only show PayPal if PayPal is enabled and if there is a real member_id
        (PAYPAL_ENABLED && $_SESSION['member_id'] ? 
          paypal_display_form (array (
            'form_id' => 'paypal_form2',
            'span1_content' => '<li class="last_of_group"><strong>Pay with PayPal &nbsp; &nbsp;</strong><div class="paypal_message">(enter amount at PayPal)</div>',
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
          <li class="last_of_group">
            <strong>Mail a check to :</strong><br><br>
            '.SITE_MAILING_ADDR.'<br><br>
            (Indicate &quot;Member #'.$_SESSION['member_id'].'&quot; on payment)
          </li>
        </ul>
        </td>
      </tr>
    </table>';

$page_specific_css = '
  <style type="text/css">
    .paypal_message {
      font-size:70%;
      margin:0.5em 0 1em;
      }
  </style>';

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