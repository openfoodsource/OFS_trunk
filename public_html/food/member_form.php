<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone
require_once ('securimage.php');

if($_GET['display_as'] == 'popup')
  {
    $display_as_popup = true;
  }
// Initialize variables
$show_form = true;

// SPECIAL NOTES ABOUT THIS PAGE: //////////////////////////////////////////////
//                                                                            //
// This page MAY be accessed by visitors without logging in.  If not          //
// logged-in, Information will need to be added to the form.  If properly     //
// logged in already then the form will be prefilled with the appropriate     //
// information and can be used to update that information.                    //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

$username = $_SESSION['username'];
// Set up the default action for this form (for the submit button)
$action = $_POST['action'];
if (! $_POST['action']) $action = 'Submit';

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                           PROCESS POSTED DATA                              //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Get data from the $_POST variable that pertain to BOTH Submit (new members) and Update (existing members)
if ($_POST['action'] == 'Submit' || $_POST['action'] == 'Update')
  {
    $business_name = $_POST['business_name'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $last_name_2 = $_POST['last_name_2'];
    $first_name_2 = $_POST['first_name_2'];
    $preferred_name = $_POST['preferred_name'];
    $no_postal_mail = $_POST['no_postal_mail'];
    $address_line1 = $_POST['address_line1'];
    $address_line2 = $_POST['address_line2'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip = $_POST['zip'];
    $county = $_POST['county'];
    $work_address_line1 = $_POST['work_address_line1'];
    $work_address_line2 = $_POST['work_address_line2'];
    $work_city = $_POST['work_city'];
    $work_state = $_POST['work_state'];
    $work_zip = $_POST['work_zip'];
    $email_address = $_POST['email_address'];
    $email_address_2 = $_POST['email_address_2'];
    $home_phone = $_POST['home_phone'];
    $work_phone = $_POST['work_phone'];
    $mobile_phone = $_POST['mobile_phone'];
    $fax = $_POST['fax'];
    $toll_free = $_POST['toll_free'];
    $home_page = $_POST['home_page'];
    $how_heard_id = $_POST['how_heard_id'];
    $heard_other = $_POST['heard_other'];
    $captcha_code = $_POST['captcha_code'];
    $affirmation = $_POST['affirmation'];
    $producer = $_POST['producer'];
    $volunteer = $_POST['volunteer'];

    // VALIDATE THE DATA
    $error_array = array ();

    if ( !$first_name || !$last_name )
      array_push ($error_array, 'First and last name are required');

    if ( !$preferred_name )
      array_push ($error_array, 'Preferred name is required (may be a business name)');

    if ( !$address_line1 || !$city || ! $state || !$zip )
      array_push ($error_array, 'A full home address is required');

    if ( !$county )
      array_push ($error_array, 'County of residence is required');

    if ( !$home_phone && !$mobile_phone )
      array_push ($error_array, 'Either home or mobile phone number is required');

    if ((!$email_address && ! $email_address_2) ||
        (!filter_var($email_address, FILTER_VALIDATE_EMAIL) && !filter_var($email_address_2, FILTER_VALIDATE_EMAIL)))
      array_push ($error_array, 'Please enter at least one valid email address');
  }

// Get data from the $_POST variable that pertain ONLY to Submit (new members)
if ($_POST['action'] == 'Submit')
  {
    $password1 = $_POST['password1'];
    $password2 = $_POST['password2'];
    $username = $_POST['username'];
    $how_heard = $_POST['how_heard'];
    $membership_type_id = $_POST['membership_type_id'];
    $password_strength = test_password ($password1);
    if ( $password_strength < MIN_PASSWORD_STRENGTH)
      {
        array_push ($error_array, 'Please select a password with a strength of at least '.MIN_PASSWORD_STRENGTH.'. Yours was '.$password_strength.'.');
        $clear_password = true;
      }

    if ( $password1 != $password2 )
      {
        array_push ($error_array, 'Password and confirmation do not match.');
        $clear_password = true;
      }

    if ($clear_password === true)
      {
        $password1 = '';
        $password2 = '';
      }

    $captcha = new Securimage();
    if ($captcha->check($captcha_code) != true) array_push ($error_array, 'Please enter the validation words to prove you are not a robot.');
    $query = '
      SELECT
        *
      FROM
        '.TABLE_MEMBER.'
      WHERE username = "'.mysqli_real_escape_string ($connection, $username).'"';
    $sql =  @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 863430 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_object ($sql))
        array_push ($error_array, 'The username "'.$username.'" is already in use.');
    if (!$username)
        array_push ($error_array, 'Choose a unique username.');
    if ( !$membership_type_id )
        array_push ($error_array, 'Choose a membership option.');
    if ( !$affirmation )
        array_push ($error_array, 'You must accept the affirmation before becoming a member.');
    if ( !$how_heard_id )
        array_push ($error_array, 'Let us know how you heard about '.SITE_NAME.'.');
    if ( !$heard_other )
        array_push ($error_array, 'Please tell more about how you heard about '.SITE_NAME.'.');
  }

// Assemble any errors encountered so far
$error_message = display_alert('error', 'Please correct the following problems and resubmit.', $error_array);


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                    GET MEMBER'S INFO FROM THE DATABASE                     //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Get member information from the database to pre-fill the form (only if first time through -- $_POST is unset)


if (!$_POST['action'] && $_SESSION['member_id'])
  {
    $query = '
      SELECT
        *
      FROM
        '.TABLE_MEMBER.'
      WHERE
        member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"';
    $sql =  @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 742813 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_object ($sql))
      {
        $last_name = $row->last_name;
        $first_name = $row->first_name;
        $last_name_2 = $row->last_name_2;
        $first_name_2 = $row->first_name_2;
        $preferred_name = $row->preferred_name;
        $business_name = $row->business_name;
        $address_line1 = $row->address_line1;
        $address_line2 = $row->address_line2;
        $city = $row->city;
        $state = $row->state;
        $zip = $row->zip;
        $county = $row->county;
        $work_address_line1 = $row->work_address_line1;
        $work_address_line2 = $row->work_address_line2;
        $work_city = $row->work_city;
        $work_state = $row->work_state;
        $work_zip = $row->work_zip;
        $home_phone = $row->home_phone;
        $work_phone = $row->work_phone;
        $mobile_phone = $row->mobile_phone;
        $fax = $row->fax;
        $toll_free = $row->toll_free;
        $username = $row->username;
        $no_postal_mail = $row->no_postal_mail;
        $email_address = $row->email_address;
        $email_address_2 = $row->email_address_2;
        $home_page = $row->home_page;
        $how_heard_id = $row->how_heard_id;
        $action = 'Update';
      }
  }


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//  SET UP THE SELECT AND CHECKBOX FORMS FOR DISPLAY BASED UPON PRIOR VALUES  //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////


// Generate the membership_types_display and membership_types_options
$membership_types_options = '
      <option value="">Choose One</option>';
$query = '
  SELECT
    *
  FROM
    '.TABLE_MEMBERSHIP_TYPES.'
  WHERE
    enabled_type = 1
    OR enabled_type = 3';
$sql =  @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 683642 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysqli_fetch_object ($sql))
  {
    $membership_types_radio .= '
          <div class="membership_type_group">
            <div class="membership_class">
              <input type="radio" class="membership_type_id" id="membership_type_id" name="membership_type_id" value="'.$row->membership_type_id.'"'.($row->membership_type_id == $membership_type_id ? ' checked' : '').' required>'.$row->membership_class.'
            </div>
            <div class="membership_description">'.$row->membership_description.'</div>
          </div>';
  }

// Build how-heard select options
$how_heard_options = '
      <option value="">Choose One</option>';
$query = '
  SELECT
    *
  FROM
    '.TABLE_HOW_HEARD.'
  WHERE 1';
$sql =  @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 650242 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysqli_fetch_object ($sql))
  {
    $selected = '';
    if ($how_heard_id == $row->how_heard_id)
      {
        $selected = ' selected';
        $how_heard_text = $row->how_heard_name;
      }
    $how_heard_options .= '
      <option value="'.$row->how_heard_id.'"'.$selected.'>'.$row->how_heard_name.'</option>';
  }

if ($affirmation == 'yes') $affirmation_check = ' checked';
if ($volunteer == 'yes') $volunteer_check = ' checked';
if ($producer == 'yes') $producer_check = ' checked';
if ($no_postal_mail == '1') $no_postal_mail_check = ' checked';


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                          DISPLAY THE INPUT FORM                            //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

$welcome_message = '
  <p>Thank you for your interest in becoming a member of '.SITE_NAME.'.
  '.SITE_NAME.' customers and producers are interested in local foods and products
  produced with sustainable practices that demonstrate good stewardship of the environment.</p>';
$display_form_top .= $welcome_message.'
  <p>To become a member, please read the <a href="'.TERMS_OF_SERVICE.'" target="_blank">
  '.TERMS_OF_SERVICE_TEXT.'</a>, and then complete the following information and click submit.</p>';
if (! $_SESSION['member_id'])
  {
    $display_form_title .= '
      <h1>'.date('Y').' '.SITE_NAME.' Registration</h1>
      <div class="submission_form member_form">';
    $display_form_top .= '
      <p>If you are already a member, please <a href="index.php?action=login">sign in here</a>.  Otherwise fill out the form below to become a member.</p>
      <p><span class="required">Required fields.</span>';
  }
else
  {
    $display_form_title .= '
      <h1>'.SITE_NAME.' Member Information</h1>
      <div class="submission_form member_form">';
    $display_form_top = '
      <p>Use this form to update your membership information.</p>
      <p><span class="required">Required fields.</span>';
  }

$display_form_text .= '
  First name:     '.$first_name.'
  Last name:      '.$last_name.'
  First name 2:   '.$first_name_2.'
  Last name 2:    '.$last_name_2.'
  Preferred name: '.$preferred_name.'
  Business name:  '.$business_name.'

  Address:
      '.$address_line1.'
      '.$address_line2.'
      '.$city.', '.$state.' '.$zip.'
      '.$county.' County

  Do not send postal mail? '.$no_postal_mail_check.'

  Work Address:
      '.$work_address_line1.'
      '.$work_address_line2.'
      '.$work_city.', '.$work_state.' '.$work_zip.'

  Home phone: '.$home_phone.'
  Work phone: '.$work_phone.'
  Cell phone: '.$mobile_phone.'
  FAX:        '.$fax.'
  Toll-free: '.$toll_free.'

  E-mail address:   '.$email_address.'
  E-mail address 2: '.$email_address_2.'
  Home Page:        '.$home_page.'

  Username: '.$username.'
  Password: '.$password1.'

  Membership type: '.$membership_type_text.'
  How you heard about '.SITE_NAME.': '.$how_heard_text.'
      Specifically: '.$heard_other.'

  Interested in volunteering? '.$volunteer_check.'

  Read the membership documents? '.$affirmation_check.'
  ';

$display_form_html .= $error_message.'
  <form action="'.$_SERVER['SCRIPT_NAME'].($display_as_popup == true ? '?display_as=popup' : '').'" name="member_form" id="member_form" method="post">
    <div class="form_buttons">
      <button type="submit" name="action" id="action" value="'.$action.'">'.$action.'</button>
      <button type="reset" name="reset" id="reset" value="Reset">Reset</button>
    </div>
    <fieldset class="identity_info grouping_block">
      <legend>Section 1: Identifying Information</legend>
      <div class="input_block_group">
        <div class="input_block first_name">
          <label class="first_name required" for="first_name">First&nbsp;Name:</label>
          <input id="first_name" name="first_name" size="25" maxlength="25" value="'.htmlspecialchars ($first_name, ENT_QUOTES).'" type="text" onKeyUp=set_preferred_name() tabindex="1" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block last_name">
          <label class="last_name required" for="last_name">Last&nbsp;Name:</label>
          <input id="last_name" name="last_name" size="25" maxlength="25" value="'.htmlspecialchars ($last_name, ENT_QUOTES).'" type="text" onKeyUp=set_preferred_name() tabindex="2" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block first_name_2">
          <label class="first_name_2" for="first_name_2">First&nbsp;Name 2:</label>
          <input id="first_name_2" name="first_name_2" size="25" maxlength="25" value="'.htmlspecialchars ($first_name_2, ENT_QUOTES).'" type="text" onKeyUp=set_preferred_name() tabindex="3">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block last_name_2">
          <label class="last_name_2" for="last_name_2">Last&nbsp;Name 2:</label>
          <input id="last_name_2" name="last_name_2" size="25" maxlength="25" value="'.htmlspecialchars ($last_name_2, ENT_QUOTES).'" type="text" onKeyUp=set_preferred_name() tabindex="4">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block business_name">
          <label class="business_name" for="business_name">Business&nbsp;Name:</label>
          <input id="business_name" name="business_name" size="50" maxlength="50" value="'.htmlspecialchars ($business_name, ENT_QUOTES).'" type="text" onKeyUp=set_preferred_business() tabindex="5">
        </div>
      </div>
      <div class="note">
        NOTE: The Preferred Invoice Name can be edited.  It will be used to route your order and should be easily tracable to you (i.e. it should not be something like &ldquo;Captain Cosmic&rdquo;). It could be the common form of your name like &ldquo;Alf Jones&rdquo; instead of &ldquo;Alfred Jones&rdquo;. In some cases, you may want to use your Business Name for the Preferred Invoice Name.
      </div>
      <div class="input_block_group">
        <div class="input_block preferred_name">
          <label class="preferred_name required" for="preferred_name">Preferred&nbsp;Invoice&nbsp;Name:</label>
          <input id="preferred_name" name="preferred_name" size="50" maxlength="50" value="'.htmlspecialchars ($preferred_name, ENT_QUOTES).'" type="text" tabindex="6" required>
        </div>
      </div>
    </fieldset>
    <fieldset class="contact_info grouping_block">
      <legend>Section 2: Contact Information</legend>
      <div class="input_block_group">
        <div class="input_block address_line1">
          <label class="address_line1 required" for="address_line1">Address:</label>
          <input id="address_line1" name="address_line1" size="50" maxlength="50" value="'.htmlspecialchars ($address_line1, ENT_QUOTES).'" type="text" tabindex="7" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block address_line2">
          <label class="address_line2" for="address_line2">Address&nbsp;2:</label>
          <input id="address_line2" name="address_line2" size="50" maxlength="50" value="'.htmlspecialchars ($address_line2, ENT_QUOTES).'" type="text" tabindex="8">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block city">
          <label class="city required" for="city">City:</label>
          <input id="city" name="city" size="15" maxlength="15" value="'.htmlspecialchars ($city, ENT_QUOTES).'" type="text" tabindex="9" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block state">
          <label class="state required" for="state">State:</label>
          <input id="state" name="state" size="2" maxlength="2" value="'.htmlspecialchars ($state, ENT_QUOTES).'" type="text" tabindex="10" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block zip">
          <label class="zip required" for="zip">Postal&nbsp;Code:</label>
          <input id="zip" name="zip" size="10" maxlength="10" value="'.htmlspecialchars ($zip, ENT_QUOTES).'" type="text" tabindex="11" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block county">
          <label class="county required" for="county">County</label>
          <input id="county" name="county" size="15" maxlength="15" value="'.htmlspecialchars ($county, ENT_QUOTES).'" type="text" tabindex="12" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block work_address_line1">
          <label class="work_address_line1" for="work_address_line1">Work&nbsp;Address:</label>
          <input id="work_address_line1" name="work_address_line1" size="50" maxlength="50" value="'.htmlspecialchars ($work_address_line1, ENT_QUOTES).'" type="text" tabindex="13">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block work_address_line2">
          <label class="work_address_line2" for="work_address_line2">Work&nbsp;Address&nbsp;2:</label>
          <input id="work_address_line2" name="work_address_line2" size="50" maxlength="50" value="'.htmlspecialchars ($work_address_line2, ENT_QUOTES).'" type="text" tabindex="14">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block work_city">
          <label class="work_city" for="work_city">Work&nbsp;City:</label>
          <input id="work_city" name="work_city" size="15" maxlength="15" value="'.htmlspecialchars ($work_city, ENT_QUOTES).'" type="text" tabindex="15">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block work_state">
          <label class="work_state" for="work_state">Work&nbsp;State:</label>
          <input id="work_state" name="work_state" size="2" maxlength="2" value="'.htmlspecialchars ($work_state, ENT_QUOTES).'" type="text" tabindex="16">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block work_zip">
          <label class="work_zip" for="work_zip">Work&nbsp;Postal&nbsp;Code:</label>
          <input id="work_zip" name="work_zip" size="10" maxlength="10" value="'.htmlspecialchars ($work_zip, ENT_QUOTES).'" type="text" tabindex="17">
        </div>
      </div>
      <div class="note">
        NOTE: Either a Home Phone or Mobile Phone number is required &ndash; not necessarily both.
      </div>
      <div class="input_block_group">
        <div class="input_block home_phone">
          <label class="home_phone required" for="home_phone">Home&nbsp;Phone:</label>
          <input id="home_phone" name="home_phone" size="20" maxlength="20" value="'.$home_phone.'" type="text" tabindex="18">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block mobile_phone">
          <label class="mobile_phone required" for="mobile_phone">Mobile&nbsp;Phone:</label>
          <input id="mobile_phone" name="mobile_phone" size="20" maxlength="20" value="'.$mobile_phone.'" type="text" tabindex="20">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block work_phone">
          <label class="work_phone" for="work_phone">Work&nbsp;Phone:</label>
          <input id="work_phone" name="work_phone" size="20" maxlength="20" value="'.$work_phone.'" type="text" tabindex="19">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block toll_free">
          <label class="toll_free" for="toll_free">Toll&nbsp;Free:</label>
          <input id="toll_free" name="toll_free" size="20" maxlength="20" value="'.$toll_free.'" type="text" tabindex="21">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block fax">
          <label class="fax" for="fax">FAX:</label>
          <input id="fax" name="fax" size="20" maxlength="20" value="'.$fax.'" type="text" tabindex="22">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block email_address">
          <label class="email_address required" for="email_address">Email&nbsp;Address:</label>
          <input id="email_address" name="email_address" size="50" maxlength="100" value="'.htmlspecialchars ($email_address, ENT_QUOTES).'" type="email" tabindex="22" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block email_address_2">
          <label class="email_address_2" for="email_address_2">Email&nbsp;Address&nbsp;2:</label>
          <input id="email_address_2" name="email_address_2" size="50" maxlength="100" value="'.htmlspecialchars ($email_address_2, ENT_QUOTES).'" type="email" tabindex="22">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block home_page">
          <label class="home_page" for="home_page">Home&nbsp;Page:</label>
          <input id="home_page" name="home_page" size="50" maxlength="200" value="'.htmlspecialchars ($home_page, ENT_QUOTES).'" type="text" tabindex="22">
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block no_postal_mail">
          <input id="no_postal_mail" type="checkbox" name="no_postal_mail" value="1"'.$no_postal_mail_check.' tabindex="23"> <span class="no_postal_mail">Do Not Send Postal Mail.</span>
        </div>
      </div>
    </fieldset>'.
    // Do not show the following part to existing members....
    (isset ($_SESSION['member_id']) ? '' : '
    <fieldset class="credential_info grouping_block">
      <legend>Section 3: Access Credentials</legend>
      <div class="input_block_group">
        <div class="input_block username">
          <label class="username required" for="username">Username:</label>
          <input id="username" name="username" size="20" maxlength="50" value="'.htmlspecialchars ($username, ENT_QUOTES).'" type="text" tabindex="24" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block password1">
          <label class="password1 required" for="password1">Password:</label>
          <input id="password1" name="password1" size="20" maxlength="50" value="'.htmlspecialchars ($password1, ENT_QUOTES).'" type="password" tabindex="25" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block password2">
          <label class="password2 required" for="password2">Confirm&nbsp;Password:</label>
          <input id="password2" name="password2" size="20" maxlength="50" value="'.htmlspecialchars ($password2, ENT_QUOTES).'" type="password" tabindex="26" required>
        </div>
      </div>
      <div class="note">
        Please enter the two food-related words to prove you are not a robot.
        <img class="securimage" id="captcha" src="'.PATH.'securimage_show.php" alt="CAPTCHA Image" />
      </div>
      <div class="input_block_group">
        <div class="input_block captcha_code">
          <label class="captcha_code required" for="captcha_code">Enter the word above:</label>
          <input id="captcha_code" name="captcha_code" size="20" maxlength="20" value="" type="text" autocomplete="off" tabindex="27" required />
        </div>
      </div>
    </fieldset>
    <fieldset class="membership_info grouping_block">
      <legend><span class="required">Section 4: Select A Membership Type</span></legend>
      <div class="input_block_group">
        <div class="input_block membership_type_id">
          <div class="membership_type_list">'.
          $membership_types_radio.'
          </div>
        </div>
      </div>
    </fieldset>
    <fieldset class="general_info grouping_block">
      <legend>Section 5: Additional Information</legend>
      <div class="input_block_group">
        <div class="input_block how_heard_id">
          <label class="how_heard_id required" for="pahow_heard_idssword1">How&nbsp;did&nbsp;you&nbsp;hear&nbsp;about '.ORGANIZATION_ABBR.'?</label>
          <select name="how_heard_id" tabindex="28" required>'.$how_heard_options.'</select>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block heard_other">
          <label class="heard_other" for="heard_other">What website, from whom, which publication/date, etc.?</label>
          <input id="heard_other" name="heard_other" size="50" maxlength="50" value="'.htmlspecialchars ($heard_other, ENT_QUOTES).'" type="text" tabindex="29" required>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block affirmation">
          <input type="checkbox" name="affirmation" value="yes"'.$affirmation_check.' tabindex="30" required> <span class="affirmation required">I acknowledge that I have read and
            understand the '.SITE_NAME.' <a href="'.TERMS_OF_SERVICE.'" target="_blank">'.TERMS_OF_SERVICE_TEXT.'</a></span>.
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block producer">
          <input type="checkbox" name="producer" value="yes"'.$producer_check.' tabindex="31"> <span class="producer">I am interested in becoming a producer member
            for '.SITE_NAME.'. Selecting this option will provide the opportunity to immediately fill out a producer application
            after submitting the membership form. Otherwise you can access the producer form from your member panel after you sign in.</span>
        </div>
      </div>
      <div class="input_block_group">
        <div class="input_block volunteer">
          <input type="checkbox" name="volunteer" value="yes"'.$volunteer_check.' tabindex="32"> <span class="volunteer">I am interested in becoming a volunteer member
            for '.SITE_NAME.'.</span>
        </div>
      </div>
    </fieldset>'
    ).'
  </form>';


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//         ADD OR CHANGE INFORMATION IN THE DATABASE FOR THIS MEMBER          //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// If everything validates, then we can post to the database...
if (count ($error_array) == 0)
  {
    if ($_POST['action'] == 'Submit') // For new members
      {
        $set_member_id = '';
        if (FILL_IN_MEMBER_ID)
          {
            // This query will find blanks in the members table and fill in the gaps with the new member_id
            $query = '
              SELECT l.member_id + 1 AS empty_member_id
              FROM '.TABLE_MEMBER.' AS l
              LEFT OUTER JOIN '.TABLE_MEMBER.' AS r ON l.member_id + 1 = r.member_id
              WHERE r.member_id IS NULL';
            $sql = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 543954 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysqli_fetch_object ($sql))
              {
                $set_member_id = '
                  member_id = "'.$row->empty_member_id.'",';
              }
          }
        // Everything validates correctly so do the INSERT and send the EMAIL
        // Begin by getting this member's pending status based upon the membership_type_id
        $query = '
          SELECT
              pending,
              initial_cost,
              set_auth_type,
              customer_fee_percent
            FROM
              '.TABLE_MEMBERSHIP_TYPES.'
            WHERE membership_type_id = "'.mysqli_real_escape_string ($connection, $membership_type_id).'"';
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 804923 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysqli_fetch_object ($result))
          {
            $pending = $row->pending;
            $initial_cost = $row->initial_cost;
            $customer_fee_percent = $row->customer_fee_percent;
            $member_auth_type_array = array ();
            $auth_type_array = explode (',', $row->set_auth_type);
            for ($auth_type_element = 0; $auth_type_element <= count($auth_type_array); $auth_type_element++)
              {
                // Check if this auth type is to be included for this membership type (will begin with "+")
                if (substr ($auth_type_array[$auth_type_element], 0, 1) == '+')
                  {
                    // Add the auth_type
                    array_push ($member_auth_type_array, substr ($auth_type_array[$auth_type_element], 1));
                  }
                // Check if this auth type is to be excluded for this membership type (will begin with "-")
                // Of course, for a new member, why would we really be *removing* an auth_type?
                if (substr ($auth_type_array[$auth_type_element], 0, 1) == '-')
                  {
                    // Remove the auth_type
                    $member_auth_type_array = array_diff($member_auth_type_array, array(substr ($auth_type_array[$auth_type_element], 1)));
                  }
              }
            // Glue the array together with commas
            $auth_type = implode (',', $member_auth_type_array);
          }
        // Then do the database insert with the relevant membership data
        $query = '
          INSERT INTO
            '.TABLE_MEMBER.'
          SET'.
            $set_member_id.'
            pending = "'.mysqli_real_escape_string ($connection, $pending).'",
            auth_type = "'.mysqli_real_escape_string ($connection, $auth_type).'",
            membership_type_id = "'.mysqli_real_escape_string ($connection, $membership_type_id).'",
            customer_fee_percent = "'.mysqli_real_escape_string ($connection, $customer_fee_percent).'",
            membership_date = NOW(),
            last_renewal_date = NOW(),
            username = "'.mysqli_real_escape_string ($connection, $username).'",
            password = md5("'.mysqli_real_escape_string ($connection, $password1).'"),
            last_name = "'.mysqli_real_escape_string ($connection, $last_name).'",
            first_name = "'.mysqli_real_escape_string ($connection, $first_name).'",
            last_name_2 = "'.mysqli_real_escape_string ($connection, $last_name_2).'",
            first_name_2 = "'.mysqli_real_escape_string ($connection, $first_name_2).'",
            preferred_name = "'.mysqli_real_escape_string ($connection, $preferred_name).'",
            business_name = "'.mysqli_real_escape_string ($connection, $business_name).'",
            address_line1 = "'.mysqli_real_escape_string ($connection, $address_line1).'",
            address_line2 = "'.mysqli_real_escape_string ($connection, $address_line2).'",
            city = "'.mysqli_real_escape_string ($connection, $city).'",
            state = "'.mysqli_real_escape_string ($connection, $state).'",
            zip = "'.mysqli_real_escape_string ($connection, $zip).'",
            county = "'.mysqli_real_escape_string ($connection, $county).'",
            work_address_line1 = "'.mysqli_real_escape_string ($connection, $work_address_line1).'",
            work_address_line2 = "'.mysqli_real_escape_string ($connection, $work_address_line2).'",
            work_city = "'.mysqli_real_escape_string ($connection, $work_city).'",
            work_state = "'.mysqli_real_escape_string ($connection, $work_state).'",
            work_zip = "'.mysqli_real_escape_string ($connection, $work_zip).'",
            home_phone = "'.mysqli_real_escape_string ($connection, $home_phone).'",
            work_phone = "'.mysqli_real_escape_string ($connection, $work_phone).'",
            mobile_phone = "'.mysqli_real_escape_string ($connection, $mobile_phone).'",
            fax = "'.mysqli_real_escape_string ($connection, $fax).'",
            toll_free = "'.mysqli_real_escape_string ($connection, $toll_free).'",
            email_address = "'.mysqli_real_escape_string ($connection, $email_address).'",
            email_address_2 = "'.mysqli_real_escape_string ($connection, $email_address_2).'",
            home_page = "'.mysqli_real_escape_string ($connection, $home_page).'",
            how_heard_id = "'.mysqli_real_escape_string ($connection, $how_heard_id).'",
            no_postal_mail = "'.mysqli_real_escape_string ($connection, $no_postal_mail).'"';
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 793032 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $member_id = mysqli_insert_id ($connection);
        include_once ('func.update_ledger.php');
        // Post the membership receivable
        $transaction_row = add_to_ledger (array (
          'transaction_group_id' => '',
          'source_type' => 'member',
          'source_key' => $member_id,
          'target_type' => 'internal',
          'target_key' => 'membership_dues',
          'amount' => $initial_cost,
          'text_key' => 'membership dues',
          'posted_by' => $member_id));
        // Figure out what sort of "welcome" to give the new member...
        if ($pending == 1)
          {
            $membership_disposition = '
            <div class="submission_form member_form">
              <p>Thanks for becoming a member! Your membership number will be #'.$member_id.'.  Your membership
              application will be reviewed by an administrator and you will be notified when it becomes
              active.  Until then, you will not be able to log in.</p>
            </div>';
        $show_form = false;
          }
        else // Pending = 0
          {
            $membership_disposition = '
            <div class="submission_form member_form">
              <p class="welcome_message">Thanks for becoming a member! Your membership number is #'.$member_id.'.  Your membership has
              been automatically activated and you may <a href="'.PATH.'index.php?action=login">
              sign in</a> immediately.</p>
            </div>';
        $show_form = false;
          }
        // Add taxes for taxable membership fees (based upon the STATE tax rate)
        $membership_taxes = 0;
        if (MEMBERSHIP_IS_TAXED)
          {
            $membership_taxes = round ($initial_cost * STATE_TAX, 2);
          }
        if ($initial_cost > 0) $membership_disposition .= '
          <p>The cost of your membership ($'.number_format ($initial_cost + $membership_taxes, 2).') will appear on your first order.';
        $display_form_message .= $membership_disposition;
        // Now send email notification(s)
        $email_to = preg_replace ('/SELF/', $email_address, MEMBER_FORM_EMAIL);
        $email_subject = 'Welcome to '.SITE_NAME.' - '.$preferred_name.' (#'.$member_id.')';
        $boundary = uniqid();
        // Set up the email preamble...
        $email_preamble = '
          <p>Following is a copy of the membership information you submitted to '.SITE_NAME.'.</p>';
        $email_preamble .= $membership_disposition.$welcome_message;
        // Need to break DOMAIN_NAME into an array of separate names so we can use the first element
        $domain_names = preg_split("/[\n\r]+/", DOMAIN_NAME);
        // Disable all form elements for emailing
        $html_version = $email_preamble.preg_replace ('/<(input|select|textarea)/', '<\1 disabled', $display_form_html);
        $email_headers  = "From: ".MEMBERSHIP_EMAIL."\n";
        $email_headers .= "Reply-To: ".MEMBERSHIP_EMAIL."\n";
        $email_headers .= "Errors-To: web@".($domain_names[0])."\n";
        $email_headers .= "MIME-Version: 1.0\n";
        $email_headers .= "Content-type: multipart/alternative; boundary=\"$boundary\"\n";
        $email_headers .= "Message-ID: <".md5(uniqid(time()))."@".($domain_names[0]).">\n";
        $email_headers .= "X-Mailer: PHP ".phpversion()."\n";
        $email_headers .= "X-Priority: 3\n";
        $email_headers .= "X-AntiAbuse: This is a machine-generated response to a user-submitted form at ".SITE_NAME.".\n\n";
        $email_body .= "--".$boundary."\n";
        $email_body .= "Content-Type: text/plain; charset=us-ascii\n\n";
        $email_body .= strip_tags ($email_preamble).$display_form_text."\n";
        $email_body .= "--".$boundary."\n";
        $email_body .= "Content-Type: text/html; charset=us-ascii\n\n";
        $email_body .= $html_version."\n";
        $email_body .= "--".$boundary."--\n";
        mail ($email_to, $email_subject, $email_body, $email_headers);
        $email_sent = true;
      }
    elseif ($_POST['action'] == 'Update') // For existing members
      {
        // Everything validates correctly so do the INSERT and send the EMAIL
        $query = '
          UPDATE
            '.TABLE_MEMBER.'
          SET
            last_name = "'.mysqli_real_escape_string ($connection, $last_name).'",
            first_name = "'.mysqli_real_escape_string ($connection, $first_name).'",
            last_name_2 = "'.mysqli_real_escape_string ($connection, $last_name_2).'",
            first_name_2 = "'.mysqli_real_escape_string ($connection, $first_name_2).'",
            preferred_name = "'.mysqli_real_escape_string ($connection, $preferred_name).'",
            business_name = "'.mysqli_real_escape_string ($connection, $business_name).'",
            address_line1 = "'.mysqli_real_escape_string ($connection, $address_line1).'",
            address_line2 = "'.mysqli_real_escape_string ($connection, $address_line2).'",
            city = "'.mysqli_real_escape_string ($connection, $city).'",
            state = "'.mysqli_real_escape_string ($connection, $state).'",
            zip = "'.mysqli_real_escape_string ($connection, $zip).'",
            county = "'.mysqli_real_escape_string ($connection, $county).'",
            work_address_line1 = "'.mysqli_real_escape_string ($connection, $work_address_line1).'",
            work_address_line2 = "'.mysqli_real_escape_string ($connection, $work_address_line2).'",
            work_city = "'.mysqli_real_escape_string ($connection, $work_city).'",
            work_state = "'.mysqli_real_escape_string ($connection, $work_state).'",
            work_zip = "'.mysqli_real_escape_string ($connection, $work_zip).'",
            home_phone = "'.mysqli_real_escape_string ($connection, $home_phone).'",
            work_phone = "'.mysqli_real_escape_string ($connection, $work_phone).'",
            mobile_phone = "'.mysqli_real_escape_string ($connection, $mobile_phone).'",
            fax = "'.mysqli_real_escape_string ($connection, $fax).'",
            toll_free = "'.mysqli_real_escape_string ($connection, $toll_free).'",
            email_address = "'.mysqli_real_escape_string ($connection, $email_address).'",
            email_address_2 = "'.mysqli_real_escape_string ($connection, $email_address_2).'",
            home_page = "'.mysqli_real_escape_string ($connection, $home_page).'",
            no_postal_mail = "'.mysqli_real_escape_string ($connection, $no_postal_mail).'"
          WHERE
            member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"';
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 673022 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $display_form_message = '
          <div class="submission_form member_form">
            <p>Your membership information has been successfully updated.</p>
          </div>';
        $show_form = false;
        $modal_action = 'just_close(3000)';
      };
    if ($producer == 'yes')
      {
        // We already have the member_id from the mysql_insert_id
        $_SESSION['new_member_id'] = $member_id;
        $_SESSION['new_business_name'] = $business_name;
        $_SESSION['new_website'] = $home_page;
        $_SESSION['new_membership_type_id'] = $membership_type_id;
        $display_form_message .= '
          <div class="submission_form member_form">
            <p>You expressed interest in becoming a producer member.</p>
            <p>You can access the <a href="producer_form.php">producer
            registration form</a> immediately or you can return later to complete it.
            The form is lengthy and you may wish to print it prior to filling it out online.</p>
          </div>';
        $show_form = false;
        $modal_action = ''; // Do not automatically close the window
      };
  }

// if (strlen ($display_form_message) > 0) $display_form_message .= '</div>';

$page_title_html = '<span class="title">Member Resources</span>';
$page_subtitle_html = '<span class="subtitle">'.($_SESSION['show_name'] ? 'Update Membership Info.' : 'New Member Form').'</span>';
$page_title = 'Member Resources: '.($_SESSION['show_name'] ? 'Update Membership Info.' : 'New Member Form');
$page_tab = 'member_panel';

$page_specific_css = '
  /* NOTE: You might want to change the $line_color and $image_bg_color
      in /openfood_includes/securimage.php to match the fieldset color that
      is used for the fieldset.credential_info background. */
  /* Set fieldset colors */
  fieldset.general_info,
  fieldset.contact_info,
  fieldset.identity_info,
  fieldset.credential_info,
  fieldset.membership_info {
    background-color:#f8f4f0;
    width:80%;
    text-align:left;
    }
  fieldset.general_info legend,
  fieldset.contact_info legend,
  fieldset.identity_info legend,
  fieldset.credential_info legend,
  fieldset.membership_info legend {
    background-color:#f8f4f0;
    }
  .grouping_block .input_block {
    float:left;
    }
  /* Force some fields to begin new lines at the left margin */
  .input_block.first_name,
  .input_block.first_name_2,
  .input_block.preferred_name,
  .input_block.business_name,
  .input_block.address_line1,
  .input_block.address_line2,
  .input_block.password1,
  .input_block.city,
  .input_block.work_address_line1,
  .input_block.work_address_line2,
  .input_block.work_city,
  .input_block.email_address,
  .input_block.home_phone,
  .input_block.fax,
  .input_block.home_page,
  .input_block.no_postal_mail,
  .note,
  .captcha_code {
    clear:left;
    }
  /* Provide a little extra space to the right */
  .input_block.how_heard_id {
    margin-right:2em;
    }
  /* Set fields that want a little extra spacing below */
  .input_block.last_name_2,
  .input_block.preferred_name,
  .input_block.county,
  .input_block.work_zip,
  .input_block.fax,
  .input_block.password2,
  .input_block.email_address_2,
  .input_block.toll_free,
  .input_block.heard_other,
  .input_block.producer,
  .input_block.affirmation {
    margin-bottom:1em;
    }
  .producer,
  .no_postal_mail,
  .affirmation {
    margin-top:0.5rem;
    }
  .note {
    float:left;
    font-size:0.9em;
    color:#433;
    text-align:left;
    padding:1rem;
    }
  img.securimage {
    display:block;
    margin:1rem 0 0;
    }
  /* Special styles for Membership Types block */
  .membership_type_list {
    width: 100%;
    }
  .membership_type_group {
    border-top: 1px solid #ccc;
    display: table;
    height: auto;
    margin: 0 2rem 0 0.5rem;
    overflow: auto;
    padding: 0.5rem 0 1rem;
    width: 98%;
    }
  .membership_class {
    display: table-cell;
    font-weight: bold;
    padding-right: 2rem;
    white-space: nowrap;
    width: 1px;
    }
  input.membership_type_id {
    margin: 0 1rem;
    }
  .membership_description {
    display: table-cell;
    }';

$page_specific_javascript = '
  function set_preferred_name() {
    // CASE WHERE BOTH LAST NAMES ARE ENTERED
    if (document.getElementById("last_name").value && document.getElementById("last_name_2").value) {
      // CASE WHERE BOTH LAST NAMES ARE THE SAME
      if (document.getElementById("last_name").value == document.getElementById("last_name_2").value) {
        document.getElementById("preferred_name").value =
          document.getElementById("first_name").value + " and " + document.getElementById("first_name_2").value + " " + document.getElementById("last_name").value; }
      // CASE WHERE LAST NAMES ARE DIFFERENT
      else {
        document.getElementById("preferred_name").value =
          document.getElementById("first_name").value + " " + document.getElementById("last_name").value + " and " + document.getElementById("first_name_2").value + " " + document.getElementById("last_name_2").value; } }
    // CASE WHERE ONLY ONE LAST NAME IS ENTERED
    else if ((document.getElementById("last_name").value && !document.getElementById("last_name_2").value) || (!document.getElementById("last_name").value && document.getElementById("last_name_2").value)) {
      // CASE WHERE ONLY ONE FIRST NAME IS ENTERED
      if ((document.getElementById("first_name").value && !document.getElementById("first_name_2").value) || (!document.getElementById("first_name").value && document.getElementById("first_name_2").value)) {
        document.getElementById("preferred_name").value =
          document.getElementById("first_name").value + document.getElementById("first_name_2").value + " " + document.getElementById("last_name").value + document.getElementById("last_name_2").value; }
      // CASE WHERE BOTH FIRST NAMES ARE ENTERED
      else if (document.getElementById("first_name").value && document.getElementById("first_name_2").value) {
        document.getElementById("preferred_name").value =
          document.getElementById("first_name").value + " and " + document.getElementById("first_name_2").value + " " + document.getElementById("last_name").value + document.getElementById("last_name_2").value; }
      // CASE WHERE NO FIRST NAME IS GIVEN
      else {
        document.getElementById("preferred_name").value =
          document.getElementById("last_name").value + document.getElementById("last_name_2").value; } }
    // CASE WHERE NO LAST NAMES ARE ENTERED
    else {
      // CASE WHERE THERE IS ONLY ONE FIRST NAME
      if ((document.getElementById("first_name").value && !document.getElementById("first_name_2").value) || (!document.getElementById("first_name").value && document.getElementById("first_name_2").value)) {
        document.getElementById("preferred_name").value =
          document.getElementById("first_name").value + document.getElementById("first_name_2").value; }
      // CASE WHERE THERE ARE TWO FIRST NAMES
      else if (document.getElementById("first_name").value && document.getElementById("first_name_2").value) {
        document.getElementById("preferred_name").value =
          document.getElementById("first_name").value + " and " + document.getElementById("first_name_2").value; }
      // CASE WHERE THERE ARE NO FIRST NAMES GIVEN
      else {
        document.getElementById("preferred_name").value =
          "" } }
    }
  function set_preferred_business() {
    // CASE WHERE A BUSINESS NAME WAS ENTERED
    if (document.getElementById("business_name").value.length > 0) {
      document.getElementById("preferred_name").value =
        document.getElementById("business_name").value;
      }
    }';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->'.
  $display_form_title.
  $display_form_message.
  (!$email_sent && $show_form ? $display_form_top.$display_form_html : '').'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
