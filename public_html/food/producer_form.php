<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone

if($_GET['display_as'] == 'popup')
  {
    $display_as_popup = true;
  }
// Initialize variables
$error_array = array ();
$notice_array = array ();
$alert_message = '';
$organic_cert_check = '';
// SPECIAL NOTES ABOUT THIS PAGE: //////////////////////////////////////////////
//                                                                            //
// This page MAY be accessed by visitors without logging in.  However, it     //
// will only be accessed by visitors who either are already accepted members  //
// or who have already supplied an application for membership.                //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Set the $producer_id_you
$producer_id_you = $_SESSION['producer_id_you'];
$producer_id = $producer_id_you;
// Then clobber it if this is for a new producer who just came from the member_form.
// NOTE: We can't clobber the session variable without causing other troubles.
if ($_GET['action'] == 'new_producer')
  $producer_id = '';

// For existing members who are logged in
if ( $_SESSION['member_id'] )
  {
    $member_id = $_SESSION['member_id'];
    $membership_type_id = $_SESSION['renewal_info']['membership_type_id'];
    $show_notice = true;
  }
// For new members who just submitted the member_form
elseif ( $_SESSION['new_member_id'] )
  {
    // Following values are set in the member_form
    $member_id = $_SESSION['new_member_id'];
    $membership_type_id = $_SESSION['new_membership_type_id'];
    $website = $_SESSION['new_website'];
    $business_name = $_SESSION['new_business_name'];
    $show_notice = false;
  }
else
  {
    header( "Location: index.php");
    exit;
  }
// New producers get this introduction
if (! $producer_id)
  {
    $display_form_title .= '
      <h1>'.date('Y').' '.SITE_NAME.' Producer Registration</h1>
      <p>Fill out the form below to apply as a producer for '.SITE_NAME.'.</p>';
  }
// Existing producers are prompted to change their existing information
else
  {
    $display_form_title .= '
      <h1>'.SITE_NAME.' Producer Information</h1>
      <p>Use this form to update your full producer information. Because this can affect '.ORGANIZATION_ABBR.' standards criteria, your status will return to &ldquo;pending&rdquo; until the information has been reviewed.</p>';
  }

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                           PROCESS POSTED DATA                              //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Get data from the $_POST variable that pertain to BOTH Submit (new members) and Update (existing members)
if ($_POST['action'] == 'Submit' || $_POST['action'] == 'Update' || ($producer_id && $_SESSION['member_id']))
  {
    if ($_POST['action'] == 'Submit' || $_POST['action'] == 'Update')
      {
        if ($_POST['action'] == 'Update') $changed_information = true; // This is changing existing data instead of adding new data
        // Clobber $producer_id because we are processing the submission and we don't need it at this point
        // but keeps our logic straight -- this is so we don't display the NOTICE message
        $show_notice = false;
        // Section I - Credentials and Privacy
        $member_id = $_POST['member_id'];
        if ($member_id == $_SESSION['member_id']) $changed_by_self = true; // Keep the 'Update' option if we were already updating
        // Producers are now permitted to change their [new] producer_link reference
        $producer_link = preg_replace ('/[^0-9A-Za-z\-\._]/','', $_POST['producer_link']); // Only allow: A-Z a-z 0-9 - . _
        $business_name = $_POST['business_name'];
        $website = $_POST['website'];
        $pub_address = $_POST['pub_address'];
        $pub_email = $_POST['pub_email'];
        $pub_email2 = $_POST['pub_email2'];
        $pub_phoneh = $_POST['pub_phoneh'];
        $pub_phonew = $_POST['pub_phonew'];
        $pub_phonec = $_POST['pub_phonec'];
        $pub_phonet = $_POST['pub_phonet'];
        $pub_fax = $_POST['pub_fax'];
        $pub_web = $_POST['pub_web'];
        // Section II - General Producer Information (Public Profile)
        $producttypes = $_POST['producttypes'];
        $about = $_POST['about'];
        $ingredients = $_POST['ingredients'];
        $general_practices = $_POST['general_practices'];
        $additional = $_POST['additional'];
        $highlights = $_POST['highlights'];
        // Section III - Production Specifics - Producer Questionaire (Registration for Review by PCC)
        $products = $_POST['products'];
        $practices = $_POST['practices'];
        $pest_management = $_POST['pest_management'];
        $productivity_management = $_POST['productivity_management'];
        $feeding_practices = $_POST['feeding_practices'];
        $soil_management = $_POST['soil_management'];
        $water_management = $_POST['water_management'];
        $land_practices = $_POST['land_practices'];
        $additional_information = $_POST['additional_information'];
        // Section IV - Certifications (Registration for Review by PCC)
        $licenses_insurance = $_POST['licenses_insurance'];
        $organic_products = $_POST['organic_products'];
        $certifying_agency = $_POST['certifying_agency'];
        $agency_phone = $_POST['agency_phone'];
        $agency_fax = $_POST['agency_fax'];
        $organic_cert = $_POST['organic_cert'];
        $liability_statement = $_POST['liability_statement'];
        // VALIDATE THE DATA
        if ( strlen ($producer_link) < 5 )
          {
            array_push ($error_array, 'You must enter a unique producer link of at least five characters.');
            $producer_link = $_POST['producer_link_prior'];
          }
        else
          {
            $query = '
              SELECT
                COUNT(producer_link) AS count
              FROM
                '.TABLE_PRODUCER.'
              WHERE
                producer_link = "'.mysqli_real_escape_string ($connection, $producer_link).'"
                AND producer_id != "'.mysqli_real_escape_string ($connection, $producer_id).'"';
            $sql =  @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 857403 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysqli_fetch_object ($sql))
              {
                if ($row->count > 0)
                  {
                    array_push ($error_array, 'The Producer link you have chosen is already in use, please select a different link.');
                    $producer_link = $_POST['producer_link_prior'];
                  }
              }
          }
        if (strlen ($business_name) == 0)
            array_push ($error_array, 'A business name is required in order to register as a producer.');
        if ($pub_email + $pub_email2 + $pub_phoneh + $pub_phonew + $pub_phonec + $pub_phonet == 0 )
            array_push ($error_array, 'Please list either an email or phone number. Customers need some way to contact you.');
        if ( strlen ($products) == 0)
            array_push ($error_array, 'Please list some of the products you intend to sell.');
        if ($liability_statement != 1)
            array_push ($error_array, 'In order to be accepted as a producer, you must agree with the stated terms.');
        if (PRODUCER_PUB_WEB == 'REQUIRED' && strlen ($website) == 0)
            array_push ($error_array, 'Producer Website field is '.PRODUCER_PUB_WEB.'.');
        // Ensure all required/denied publish fields are correctly set
        if (PRODUCER_PUB_ADDRESS != 'OPTIONAL' &&
            ($pub_address == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_ADDRESS)
            array_push ($error_array, 'Permission to publish Home Address is '.PRODUCER_PUB_ADDRESS);
        if (PRODUCER_PUB_EMAIL != 'OPTIONAL' &&
            ($pub_email == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_EMAIL)
            array_push ($error_array, 'Permission to publish Primary Email Address is '.PRODUCER_PUB_EMAIL);
        if (PRODUCER_PUB_EMAIL2 != 'OPTIONAL' &&
            ($pub_email2 == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_EMAIL2)
            array_push ($error_array, 'Permission to publish Secondary Email Address is '.PRODUCER_PUB_EMAIL2);
        if (PRODUCER_PUB_PHONEH != 'OPTIONAL' &&
            ($pub_phoneh == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_PHONEH)
            array_push ($error_array, 'Permission to publish Home Phone No. is '.PRODUCER_PUB_PHONEH);
        if (PRODUCER_PUB_PHONEW != 'OPTIONAL' &&
            ($pub_phonew == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_PHONEW)
            array_push ($error_array, 'Permission to publish Work Phone No. is '.PRODUCER_PUB_PHONEW);
        if (PRODUCER_PUB_PHONEC != 'OPTIONAL' &&
            ($pub_phonec == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_PHONEC)
            array_push ($error_array, 'Permission to publish Mobile Phone No. is '.PRODUCER_PUB_PHONEC);
        if (PRODUCER_PUB_PHONET != 'OPTIONAL' &&
            ($pub_phonet == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_PHONET)
            array_push ($error_array, 'Permission to publish Toll-free Phone No. is '.PRODUCER_PUB_PHONET);
        if (PRODUCER_PUB_FAX != 'OPTIONAL' &&
            ($pub_fax == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_FAX)
            array_push ($error_array, 'Permission to publish FAX No. is '.PRODUCER_PUB_FAX);
        if (PRODUCER_PUB_WEB != 'OPTIONAL' &&
            ($pub_web == 1 ? 'REQUIRED' : 'DENIED') != PRODUCER_PUB_WEB)
            array_push ($error_array, 'Permission to publish Web Page is '.PRODUCER_PUB_WEB);
        // Check for the "at least one" condition for email and phone numbers
        // NOTE: This does not check that the producer has actually provided the specified information on their member form
        if (PRODUCER_REQ_EMAIL && ($pub_email + $pub_email2 == 0))
            array_push ($error_array, 'At least one e-mail address is required.');
        if (PRODUCER_REQ_PHONE && ($pub_phoneh + $pub_phonew + $pub_phonec + $pub_phonet == 0))
            array_push ($error_array, 'At least one telephone number is required.');
      }
    else // Not an Update or Submission, so get database information because we are editing existing producer information
      {
        $query = '
          SELECT
            '.TABLE_PRODUCER.'.*,
            '.TABLE_PRODUCER_REG.'.*
          FROM
            '.TABLE_PRODUCER.'
          JOIN '.TABLE_PRODUCER_REG.' ON '.TABLE_PRODUCER_REG.'.producer_id = '.TABLE_PRODUCER.'.producer_id
          WHERE
            '.TABLE_PRODUCER.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
        $sql =  @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 762905 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysqli_fetch_object ($sql))
          {
            // Section I - Credentials and Privacy
            $member_id = $row->member_id;
            $changed_information = true; // Means this is changes to existing data -- instead of new data
            if ($member_id == $_SESSION['member_id'])
                $changed_by_self = true; // Means the producer pending and unlisted_producer will be reset to new-producer values
            $producer_link = $row->producer_link;
            $business_name = $row->business_name;
            $website = $row->website;
            $pub_address = $row->pub_address;
            $pub_email = $row->pub_email;
            $pub_email2 = $row->pub_email2;
            $pub_phoneh = $row->pub_phoneh;
            $pub_phonew = $row->pub_phonew;
            $pub_phonec = $row->pub_phonec;
            $pub_phonet = $row->pub_phonet;
            $pub_fax = $row->pub_fax;
            $pub_web = $row->pub_web;
            // Section II - General Producer Information (Public Profile)
            $producttypes = $row->producttypes;
            $about = $row->about;
            $ingredients = $row->ingredients;
            $general_practices = $row->general_practices;
            $additional = $row->additional;
            $highlights = $row->highlights;
            // Section III - Production Specifics - Producer Questionaire (Registration for Review by PCC)
            $products = $row->products;
            $practices = $row->practices;
            $pest_management = $row->pest_management;
            $productivity_management = $row->productivity_management;
            $feeding_practices = $row->feeding_practices;
            $soil_management = $row->soil_management;
            $water_management = $row->water_management;
            $land_practices = $row->land_practices;
            $additional_information = $row->additional_information;
            // Section IV - Certifications (Registration for Review by PCC)
            $licenses_insurance = $row->licenses_insurance;
            $organic_products = $row->organic_products;
            $certifying_agency = $row->certifying_agency;
            $agency_phone = $row->agency_phone;
            $agency_fax = $row->agency_fax;
            $organic_cert = $row->organic_cert;
            $liability_statement = $row->liability_statement;
            // Need values for pending and unlisted_producer to put it back the way it was (unless changed_by_self)
            $pending = $row->pending;
            $unlisted_producer = $row->unlisted_producer;
          }
      }
  }
// VALIDATE THE DATA
if ( !$member_id ) array_push ($error_array, 'Member ID is unknown.  You must access this form after logging in and/or submitting a <a href="member_form.php">membership form</a>');
// Assemble any errors encountered so far
if ($producer_id && (NEW_PRODUCER_PENDING || NEW_PRODUCER_STATUS != 0) && $show_notice)
  {
    array_push ($notice_array, 'Any update to this page will require re-approval to ensure compatibility with '.ORGANIZATION_ABBR.' standards.
      If you only need to change the the General Producer Information (Section 2 below), please use the
      <a href="'.PATH.'edit_producer_info.php">Edit Producer Information Link</a> instead.');
  }
$alert_message = display_alert('error', 'Please correct the following problems and resubmit.', $error_array);
// If there are errors, then clobber the NOTICE message and display the errors
if (strlen ($alert_message) == 0)
    $alert_message = display_alert('notice', 'This action will deactivate your producer listing!', $notice_array);

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//  SET UP THE SELECT AND CHECKBOX FORMS FOR DISPLAY BASED UPON PRIOR VALUES  //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// Section I - Credentials and Privacy
// Publish member address
if (($pub_address == '1' || PRODUCER_PUB_ADDRESS == 'REQUIRED') && PRODUCER_PUB_ADDRESS != 'DENIED')
    $pub_address_check = ' checked="checked"';
else
    $pub_address = '0';
// Publish member email_address
if (($pub_email == '1' || PRODUCER_PUB_EMAIL == 'REQUIRED') && PRODUCER_PUB_EMAIL != 'DENIED')
    $pub_email_check = ' checked="checked"';
else
    $pub_email = '0';
// Publish member email_address_2
if (($pub_email2 == '1' || PRODUCER_PUB_EMAIL2 == 'REQUIRED') && PRODUCER_PUB_EMAIL2 != 'DENIED')
    $pub_email2_check = ' checked="checked"';
else
    $pub_email2 = '0';
// Publish member home phone number
if (($pub_phoneh == '1' || PRODUCER_PUB_PHONEH == 'REQUIRED') && PRODUCER_PUB_PHONEH != 'DENIED')
    $pub_phoneh_check = ' checked="checked"';
else
    $pub_phoneh = '0';
// Publish member work phone number
if (($pub_phonew == '1' || PRODUCER_PUB_PHONEW == 'REQUIRED') && PRODUCER_PUB_PHONEW != 'DENIED')
    $pub_phonew_check = ' checked="checked"';
else
    $pub_phonew = '0';
// Publish member mobile phone number
if (($pub_phonec == '1' || PRODUCER_PUB_PHONEC == 'REQUIRED') && PRODUCER_PUB_PHONEC != 'DENIED')
    $pub_phonec_check = ' checked="checked"';
else
    $pub_phonec = '0';
// Publish member toll-free phone number
if (($pub_phonet == '1' || PRODUCER_PUB_PHONET == 'REQUIRED') && PRODUCER_PUB_PHONET != 'DENIED')
    $pub_phonet_check = ' checked="checked"';
else
    $pub_phonet = '0';
// Publish member fax number
if (($pub_fax == '1' || PRODUCER_PUB_FAX == 'REQUIRED') && PRODUCER_PUB_FAX != 'DENIED')
    $pub_fax_check = ' checked="checked"';
else
    $pub_fax = '0';
// Publish producer web page
if (($pub_web == '1' || PRODUCER_PUB_WEB == 'REQUIRED') && PRODUCER_PUB_WEB != 'DENIED')
    $pub_web_check = ' checked="checked"';
else
    $pub_web = '0';
// Section IV - Certifications (Registration for Review by PCC)
// Organic certification checkbox
if ($organic_cert == '1')
    $organic_cert_check = ' checked="checked"';
else
    $organic_cert = '0';
// Liability Statement checkbox
if ($liability_statement == 1)
    $liability_statement_check = ' checked="checked"';
else
    $liability_statement = '0';

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                          DISPLAY THE INPUT FORM                            //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// $display_form_text is used for e-mail text-only

$display_form_text .= '

  SECTION 1: CREDENTIALS AND PRIVACY  --------------------------------

  Member ID:     '.$member_id.'
  Producer ID:   '.$producer_id.'
  Producer Link: '.$producer_link.'
  Business Name: '.$business_name.'
  Website:       '.$website.'

  The following checked items will be displayed on the site
    ['.strtr ($pub_address, " 01", "  X").'] Publish Home Address
    ['.strtr ($pub_email, " 01", "  X").'] Publish Primary Email Address
    ['.strtr ($pub_email2, " 01", "  X").'] Publish Secondary Email Address
    ['.strtr ($pub_phoneh, " 01", "  X").'] Publish Home Phone No.
    ['.strtr ($pub_phonew, " 01", "  X").'] Publish Work Phone No.
    ['.strtr ($pub_phonec, " 01", "  X").'] Publish Mobile Phone No.
    ['.strtr ($pub_phonet, " 01", "  X").'] Publish Toll-free Phone No.
    ['.strtr ($pub_fax, " 01", "  X").'] Publish FAX No.
    ['.strtr ($pub_web, " 01", "  X").'] Publish Web Page

  SECTION 2: GENERAL PRODUCER INFORMATION  ---------------------------

  Product Types:
    '.str_replace ("\n", "\n    ", wordwrap($producttypes, 71, "\n", true)).'

  About Us:
    '.str_replace ("\n", "\n    ", wordwrap($about, 71, "\n", true)).'

  Ingredients:
    '.str_replace ("\n", "\n    ", wordwrap($ingredients, 71, "\n", true)).'

  Practices:
    '.str_replace ("\n", "\n    ", wordwrap($general_practices, 71, "\n", true)).'

  Additional Information:
    '.str_replace ("\n", "\n    ", wordwrap($additional, 71, "\n", true)).'

  Highlights This Cycle:
    '.str_replace ("\n", "\n    ", wordwrap($highlights, 71, "\n", true)).'

  SECTION 3: PRODUCT SPECIFICS ---------------------------------------

  Products:
    '.str_replace ("\n", "\n    ", wordwrap($products, 71, "\n", true)).'

  Practices:
    '.str_replace ("\n", "\n    ", wordwrap($practices, 71, "\n", true)).'

  Pest Management:
    '.str_replace ("\n", "\n    ", wordwrap($pest_management, 71, "\n", true)).'

  Productivity Management:
    '.str_replace ("\n", "\n    ", wordwrap($productivity_management, 71, "\n", true)).'

  Feeding Practices:
    '.str_replace ("\n", "\n    ", wordwrap($feeding_practices, 71, "\n", true)).'

  Soil Management:
    '.str_replace ("\n", "\n    ", wordwrap($soil_management, 71, "\n", true)).'

  Water Management:
    '.str_replace ("\n", "\n    ", wordwrap($water_management, 71, "\n", true)).'

  Land Practices:
    '.str_replace ("\n", "\n    ", wordwrap($land_practices, 71, "\n", true)).'

  Additional Information:
    '.str_replace ("\n", "\n    ", wordwrap($additional_information, 71, "\n", true)).'

  SECTION 4: CERTIFICATIONS ------------------------------------------

  Insurance, Licenses, and Tests:
    '.str_replace ("\n", "\n    ", wordwrap($licenses_insurance, 71, "\n", true)).'

  Organic Products:
    '.str_replace ("\n", "\n    ", wordwrap($organic_products, 71, "\n", true)).'

  Organic Certifying Agency:
    '.str_replace ("\n", "\n    ", wordwrap($certifying_agency, 71, "\n", true)).'

  Certifying Agency Phone:
    '.str_replace ("\n", "\n    ", wordwrap($agency_phone, 71, "\n", true)).'

  Certifying Agency FAX:
    '.str_replace ("\n", "\n    ", wordwrap($agency_fax, 71, "\n", true)).'

  ['.strtr ($organic_cert, " 01", "  X").'] I have available for inspection a copy of your current organic certificate.

  SECTION 5: TERMS AND AGREEMENT -------------------------------------

  ['.strtr ($liability_statement, " 01", "  X").'] '.str_replace ("\n", "\n      ", wordwrap ('I affirm that all statements made about my farm and products in this application are true, correct and complete and I have given a truthful representation of my operation, practices, and origin of products. I understand that if questions arise about my operation I may be inspected (unannounced) by '.SITE_NAME.'. If I stated my operation is organic, then I am complying with the National Organic Program and will provide upon request a copy of my certification.  I have read all of '.SITE_NAME."'".'s Subscription Agreement and fully understand and am willing to comply with them.', 75, "\n", true));

if ($_POST['action'] == 'Update')
  $welcome_message .= '<p><em>Changes have been made to your producer information at '.SITE_NAME.'.  Following is a record of those changes.</em></p>';
else
  $welcome_message .= '<p><em>Thank you for your interest in becoming a producer member of '.SITE_NAME.'. '.SITE_NAME.' customers and producers are interested in local foods and products produced with sustainable practices that demonstrate good stewardship of the environment. Upon approval this form will register you to sell products within '.SITE_NAME.'.  Please read the <a href="'.TERMS_OF_SERVICE.'" target="_blank">'.TERMS_OF_SERVICE_TEXT.'</a>, and then complete the following information and click submit.</em></p>';
/* HTML Page and E-mail */
$display_form_html .= $alert_message.
  $display_form_title.'
  <div class="submission_form producer_form">
    <p><span class="required">Required fields</span></p>
    <form action="'.$_SERVER['SCRIPT_NAME'].($display_as_popup == true ? '?display_as=popup' : '').'" name="producer_form" id="producer_form" method="post">
      <div class="form_buttons">
        <button type="submit" name="action" id="action" value="'.($changed_information ? 'Update' : 'Submit').'">'.($changed_information ? 'Update' : 'Submit').'</button>
        <button type="reset" name="reset" id="reset" value="Reset">Reset</button>
      </div>
      <fieldset class="privacy_info grouping_block">
        <legend>Section 1: Credentials and Privacy</legend>
        <div class="input_block_group member_id">
          <div class="input_block member_id">
            <label class="member_id" for="member_id">Member&nbsp;ID:</label>
            <input id="member_id_display" name="member_id_display" size="10" maxlength="10" value="'.$member_id.'" type="text" disabled>
            <input id="member_id" name="member_id" size="10" maxlength="10" value="'.$member_id.'" type="hidden">
          </div>
        </div>
        <div class="input_block_group producer_link">
          <div class="input_block producer_link">
            <label class="producer_link required" for="producer_link">'.ORGANIZATION_ABBR.'&nbsp;Producer&nbsp;Link:<div id="producer_link_message"></div></label>
            <input id="producer_link" name="producer_link" size="40" maxlength="50" value="'.htmlspecialchars ($producer_link, ENT_QUOTES).'" type="text" onKeyUp="check_producer_link()" tabindex="1">
            <input type="hidden" id="producer_link_prior" name="producer_link_prior" value="'.htmlspecialchars ($producer_link, ENT_QUOTES).'">
          </div>
        </div>
        <div class="note">
          NOTE: Choose a unique Producer Link (above) for direct access to your products on the '.SITE_NAME.' site.  It may contain letters, numbers, dash, dot, and/or underline and must be between five and fifty characters. It will help list your information with search engines and allow web users to directly access your information with an address like: <span class="producer_link_example">'.BASE_URL.PATH.'producers/<span class"target">'.(strlen (htmlspecialchars ($producer_link, ENT_QUOTES)) == 0 ? 'my-organic-farm' : htmlspecialchars ($producer_link, ENT_QUOTES)).'</span></span>.
        </div>
        <div class="input_block_group business_name">
          <div class="input_block business_name">
            <label class="business_name" for="business_name">Business&nbsp;Name:</label>
            <input id="business_name" name="business_name" size="45" maxlength="50" value="'.htmlspecialchars ($business_name, ENT_QUOTES).'" type="text" onKeyUp=set_preferred_name() tabindex="2">
          </div>
        </div>
        <div class="input_block_group website">
          <div class="input_block website '.strtolower (PRODUCER_PUB_WEB).'">
            <label class="website '.strtolower (PRODUCER_PUB_WEB).'" for="website">Website:</label>
            <input id="website" name="website" size="45" maxlength="50" value="'.htmlspecialchars ($website, ENT_QUOTES).'" type="text" onKeyUp=set_preferred_name() tabindex="3">
          </div>
        </div>
        <div class="note">
          NOTE: The selecting the following options will make data from your Membership Information available for customers to see when looking at your products. You must select any <span class="required">required</span> options.
        </div>
        <div class="input_block_group pub_address">
          <div class="input_block privacy_check pub_address '.strtolower(PRODUCER_PUB_ADDRESS).'">
            <input id="pub_address" name="pub_address" type="checkbox" value="1" '.$pub_address_check.'" tabindex="4"> <span class="pub_address '.strtolower(PRODUCER_PUB_ADDRESS).'">Publish Home Address</span>
          </div>
        </div>
        <div class="input_block_group pub_email">
          <div class="input_block privacy_check pub_email '.strtolower(PRODUCER_PUB_EMAIL).'">
            <input id="pub_email" name="pub_email" type="checkbox" value="1" '.$pub_email_check.'" tabindex="5"> <span class="pub_email '.strtolower(PRODUCER_PUB_EMAIL).'">Publish Email Address</span>
          </div>
          <div class="input_block privacy_check pub_email2 '.strtolower(PRODUCER_PUB_EMAIL2).'">
            <input id="pub_email2" name="pub_email2" type="checkbox" value="1" '.$pub_email2_check.'" tabindex="6"> <span class="pub_email2 '.strtolower(PRODUCER_PUB_EMAIL2).'">Publish Email Address 2</span>
          </div>
        </div>
        <div class="input_block_group pub_phone">
          <div class="input_block privacy_check pub_phoneh '.strtolower(PRODUCER_PUB_PHONEH).'">
            <input id="pub_phoneh" name="pub_phoneh" type="checkbox" value="1" '.$pub_phoneh_check.'" tabindex="7"> <span class="pub_phoneh '.strtolower(PRODUCER_PUB_PHONEH).'">Publish Home Phone</span>
          </div>
          <div class="input_block privacy_check pub_phonew '.strtolower(PRODUCER_PUB_PHONEW).'">
            <input id="pub_phonew" name="pub_phonew" type="checkbox" value="1" '.$pub_phonew_check.'" tabindex="8"> <span class="pub_phonew '.strtolower(PRODUCER_PUB_PHONEW).'">Publish Work Phone</span>
          </div>
          <div class="input_block privacy_check pub_phonec '.strtolower(PRODUCER_PUB_PHONEC).'">
            <input id="pub_phonec" name="pub_phonec" type="checkbox" value="1" '.$pub_phonec_check.'" tabindex="9"> <span class="pub_phonec '.strtolower(PRODUCER_PUB_PHONEC).'">Publish Mobile Phone</span>
          </div>
          <div class="input_block privacy_check pub_phonet '.strtolower(PRODUCER_PUB_PHONET).'">
            <input id="pub_phonet" name="pub_phonet" type="checkbox" value="1" '.$pub_phonet_check.'" tabindex="10"> <span class="pub_phonet '.strtolower(PRODUCER_PUB_PHONET).'">Publish Toll-Free Phone</span>
          </div>
          <div class="input_block privacy_check pub_fax '.strtolower(PRODUCER_PUB_FAX).'">
            <input id="pub_fax" name="pub_fax" type="checkbox" value="1" '.$pub_fax_check.'" tabindex="11"> <span class="pub_fax '.strtolower(PRODUCER_PUB_FAX).'">Publish FAX Number</span>
          </div>
        </div>
        <div class="input_block_group pub_web">
          <div class="input_block privacy_check pub_web '.strtolower(PRODUCER_PUB_WEB).'">
            <input id="pub_web" name="pub_web" type="checkbox" value="1" '.$pub_web_check.'" tabindex="12"> <span class="pub_web '.strtolower(PRODUCER_PUB_WEB).'">Publish Website (above)</span>
          </div>
        </div>
      </fieldset>
      <fieldset class="producer_info grouping_block">
        <legend>Section 2: General Producer Information</legend>
        <div class="note">
          Answers to these questions will appear on your producer information page and may be updated by you at any time.
        </div>
        <div class="input_block_group producttypes">
          <div class="input_block text_block producttypes">
            <label class="producttypes" for="producttypes">Product Types (List keywords like lettuce, berries, buffalo, soap, etc.):</label>
            <textarea name="producttypes" tabindex="13">'.htmlspecialchars (br2nl($producttypes, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group about">
          <div class="input_block text_block about">
            <label class="about" for="about">About Us (Describe your business, you, how you got started, etc.):</label>
            <textarea name="about" tabindex="14">'.htmlspecialchars (br2nl($about, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group ingredients">
          <div class="input_block text_block ingredients">
            <label class="ingredients" for="ingredients">Ingredients (List all relevant ingredients):</label>
            <textarea name="ingredients" tabindex="15">'.htmlspecialchars (br2nl($ingredients, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group general_practices">
          <div class="input_block text_block general_practices">
            <label class="general_practices" for="general_practices">Practices (Describe your standards and practices, such as using all natural products, etc.):</label>
            <textarea name="general_practices" tabindex="16">'.htmlspecialchars (br2nl($general_practices, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group additional">
          <div class="input_block text_block additional">
            <label class="additional" for="additional">Additional Information (Anything that is not covered in the other sections):</label>
            <textarea name="additional" tabindex="17">'.htmlspecialchars (br2nl($additional, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group highlights">
          <div class="input_block text_block highlights">
            <label class="highlights" for="highlights">Highlights This Cycle (Notes that are relevant to the current cycle):</label>
            <textarea name="highlights" tabindex="18">'.htmlspecialchars (br2nl($highlights, ENT_QUOTES)).'</textarea>
          </div>
        </div>
      </fieldset>
      <fieldset class="production_info grouping_block">
        <legend>Section 3: Production Specifics</legend>
        <div class="note">
          This information is a part of your &ldquo;Original Producer Questionnaire&rdquo; and will available for review by customers as a link from your producer information page. The answers can be changed at a later date, but will require re-approval to ensure compatibility with '.ORGANIZATION_ABBR.' standards.
        </div>
        <div class="input_block_group products">
          <div class="input_block text_block products">
            <label class="products" for="products">Products (List the types of products you intend to sell through '.ORGANIZATION_ABBR.' such as meats, grains, jellies, crafts. Also note if you have any heritage breeds):</label>
            <textarea name="products" tabindex="19">'.htmlspecialchars (br2nl($products, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group practices">
          <div class="input_block text_block practices">
            <label class="practices" for="practices">Practices (Describe your farming, processing and/or crafting practices):</label>
            <textarea name="practices" tabindex="20">'.htmlspecialchars (br2nl($practices, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group pest_management">
          <div class="input_block text_block pest_management">
            <label class="pest_management" for="pest_management">Pest Management (Describe your pest and disease management system):</label>
            <textarea name="pest_management" tabindex="21">'.htmlspecialchars (br2nl($pest_management, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group productivity_management">
          <div class="input_block text_block productivity_management">
            <label class="productivity_management" for="productivity_management">Productivity Management (Describe your herd health and productivity management, such as use of hormones, antibiotics, and/or steroids):</label>
            <textarea name="productivity_management" tabindex="22">'.htmlspecialchars (br2nl($productivity_management, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group feeding_practices">
          <div class="input_block text_block feeding_practices">
            <label class="feeding_practices" for="feeding_practices">Feeding Practices (Describe your feeding practices, such as grass-fed only, free-range, feed-lot, etc.):</label>
            <textarea name="feeding_practices" tabindex="23">'.htmlspecialchars (br2nl($feeding_practices, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group soil_management">
          <div class="input_block text_block soil_management">
            <label class="soil_management" for="soil_management">Soil Management (Describe your soil and nutrient management, such as the use of compost, fertilizers, green manures, and animal manures):</label>
            <textarea name="soil_management" tabindex="24">'.htmlspecialchars (br2nl($soil_management, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group water_management">
          <div class="input_block text_block water_management">
            <label class="water_management" for="water_management">Water Management (Describe your water usage practices. If you irrigate, is it from deep well, surface water, etc. Explain how you conserve water or use best management practices. Describe how you are protecting your water source from contamination/erosion):</label>
            <textarea name="water_management" tabindex="25">'.htmlspecialchars (br2nl($water_management, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group land_practices">
          <div class="input_block text_block land_practices">
            <label class="land_practices" for="land_practices">Land Practices (Describe your conservation/land stewardship practices, such as windbreaks, grass waterways, riparian buffers, green manures for wind erosion, habitats for birds, soil quality improvements, etc):</label>
            <textarea name="land_practices" tabindex="26">'.htmlspecialchars (br2nl($land_practices, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group additional_information">
          <div class="input_block text_block additional_information">
            <label class="additional_information" for="additional_information">Additional Information (Describe any additional information and/or sustainable practices that would be helpful to a potential customer in understanding your farm or operation better, such as listing heritage breeds or varieties of heirloom seeds. Identify the percentage of local ingredients used in your processed items):</label>
            <textarea name="additional_information" tabindex="27">'.htmlspecialchars (br2nl($additional_information, ENT_QUOTES)).'</textarea>
          </div>
        </div>
      </fieldset>
      <fieldset class="certifications grouping_block">
        <legend>Section 4: Certifications</legend>
        <div class="note">
          This information is a part of your &ldquo;Original Producer Questionnaire&rdquo; and will available for review by customers as a link from your producer information page. The answers can be changed at a later date, but will require re-approval to ensure compatibility with '.ORGANIZATION_ABBR.' standards.
        </div>
        <div class="input_block_group licenses_insurance">
          <div class="input_block text_block licenses_insurance">
            <label class="licenses_insurance" for="licenses_insurance">Insurance, Licenses, and Tests (List your food liability insurance coverage, both general and product-related, as well as any licenses and tests that you have available &ndash; if applicable. As this is required to market products through the '.ORGANIZATION_TYPE.', you will be required to provide copies of the above when you receive confirmation of approval by '.SITE_NAME.'):</label>
            <textarea name="licenses_insurance" tabindex="28">'.htmlspecialchars (br2nl($licenses_insurance, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group organic_products">
          <div class="input_block text_block organic_products">
            <label class="organic_products" for="organic_products">Organic Products (List which products you are selling as organic):</label>
            <textarea name="organic_products" tabindex="29">'.htmlspecialchars (br2nl($organic_products, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group certifying_agency">
          <div class="input_block text_block certifying_agency">
            <label class="certifying_agency" for="certifying_agency">Organic Certifying Agency (List orgainic certifying agency&rsquo;s name and address):</label>
            <textarea name="certifying_agency" tabindex="30">'.htmlspecialchars (br2nl($certifying_agency, ENT_QUOTES)).'</textarea>
          </div>
        </div>
        <div class="input_block_group agency_phone">
          <div class="input_block agency_phone">
            <label class="agency_phone required" for="agency_phone">Certifying&nbsp;Agency&rsquo;s&nbsp;Phone:</label>
            <input id="agency_phone" name="agency_phone" size="15" maxlength="20" value="'.htmlspecialchars ($agency_phone, ENT_QUOTES).'" type="text" tabindex="31">
          </div>
          <div class="input_block agency_fax">
            <label class="agency_fax required" for="agency_fax">Certifying&nbsp;Agency&rsquo;s&nbsp;Phone:</label>
            <input id="agency_fax" name="agency_fax" size="15" maxlength="20" value="'.htmlspecialchars ($agency_fax, ENT_QUOTES).'" type="text" tabindex="32">
          </div>
        </div>
        <div class="input_block_group organic_cert">
          <div class="input_block organic_cert">
            <input type="checkbox" name="organic_cert" value="1"'.$organic_cert_check.' tabindex="33"> <span class="organic_cert">I have available for inspection a copy of my current organic certificate.</span>
          </div>
        </div>
      </fieldset>
      <fieldset class="agreement grouping_block">
        <legend>Section 5: Terms and Agreement</legend>
        <div class="note">
          Affirmation of this agreement is a part of your &ldquo;Original Producer Questionnaire&rdquo; and will available for review by customers as a link from your producer information page.
        </div>
        <div class="input_block_group liability_statement">
          <div class="input_block liability_statement">
            <input type="checkbox" name="liability_statement" value="1"'.$liability_statement_check.' tabindex="34"> <span class="liability_statement">By checking this box, I affirm that all statements made about my farm and products in this application are true, correct and complete and I have given a truthful representation of my operation, practices, and origin of products. I understand that if questions arise about my operation I may be inspected (unannounced) by '.SITE_NAME.'. If I stated my operation is organic, then I am complying with the National Organic Program and will provide upon request a copy of my certification.  I have read all of '.SITE_NAME.'&#146;s <a href="'.TERMS_OF_SERVICE.'" target="_blank">'.TERMS_OF_SERVICE_TEXT.'</a> and fully understand and am willing to comply with them.</span>
          </div>
        </div>
      </fieldset>
    </div>';

    if ($producer_id && (NEW_PRODUCER_PENDING || NEW_PRODUCER_STATUS != 0) && $show_notice)
      {
        array_push ($notice_array, 'Any update to this page will require re-approval to ensure compatibility with '.ORGANIZATION_ABBR.' standards.
          If you only need to change the the General Producer Information (Section 2 below), please use the
          <a href="'.PATH.'edit_producer_info.php">Edit Producer Information Link</a> instead.');
      }

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//         ADD OR CHANGE INFORMATION IN THE DATABASE FOR THIS MEMBER          //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////


// If everything validates, then we can post to the database...
if (count ($error_array) == 0 && ($_POST['action'] == 'Submit' || $_POST['action'] == 'Update'))
  {
    // Everything validates correctly so do the INSERT and send the EMAIL
    // Do the database insert with the relevant data (producers table)
    $query = '
      UPDATE
        '.TABLE_MEMBER.'
      SET
        auth_type = CONCAT_WS(",", auth_type, "producer")
      WHERE
        member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 760232 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    // Do the database insert with the relevant data (producers table)
    if ($_POST['action'] == 'Submit')
      {
        // Set the producers table query
        $query1 = '
          INSERT INTO
            '.TABLE_PRODUCER.'
          SET
            pending = "'.NEW_PRODUCER_PENDING.'",
            unlisted_producer = '.NEW_PRODUCER_STATUS.',
            producer_fee_percent = (
              SELECT producer_fee_percent
              FROM '.TABLE_MEMBERSHIP_TYPES.'
              WHERE membership_type_id ="'.mysqli_real_escape_string ($connection, $membership_type_id).'"),'.
         /* Section I */'
            member_id = '.mysqli_real_escape_string ($connection, $member_id).',
            business_name = "'.mysqli_real_escape_string ($connection, $business_name).'",
            producer_link = "'.mysqli_real_escape_string ($connection, $producer_link).'",
            pub_address = "'.mysqli_real_escape_string ($connection, $pub_address).'",
            pub_email = "'.mysqli_real_escape_string ($connection, $pub_email).'",
            pub_email2 = "'.mysqli_real_escape_string ($connection, $pub_email2).'",
            pub_phoneh = "'.mysqli_real_escape_string ($connection, $pub_phoneh).'",
            pub_phonew = "'.mysqli_real_escape_string ($connection, $pub_phonew).'",
            pub_phonec = "'.mysqli_real_escape_string ($connection, $pub_phonec).'",
            pub_phonet = "'.mysqli_real_escape_string ($connection, $pub_phonet).'",
            pub_fax = "'.mysqli_real_escape_string ($connection, $pub_fax).'",
            pub_web = "'.mysqli_real_escape_string ($connection, $pub_web).'",'.
         /* Section II */'
            producttypes = "'.mysqli_real_escape_string ($connection, $producttypes).'",
            about = "'.mysqli_real_escape_string ($connection, $about).'",
            ingredients = "'.mysqli_real_escape_string ($connection, $ingredients).'",
            general_practices = "'.mysqli_real_escape_string ($connection, $general_practices).'",
            additional = "'.mysqli_real_escape_string ($connection, $additional).'",
            highlights = "'.mysqli_real_escape_string ($connection, $highlights).'",'.
         /* Section V */'
            liability_statement = "'.mysqli_real_escape_string ($connection, $liability_statement).'"';
        // Run the producers table insert query and get back the producer_id
        $result = @mysqli_query ($connection, $query1) or die (debug_print ("ERROR: 574303 ", array ($query1, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $producer_id = mysqli_insert_id ($connection);
        // Set the producers_registration table query
        $query2 = '
          INSERT INTO
            '.TABLE_PRODUCER_REG.'
          SET'.
        /* Section I */'
            member_id = '.mysqli_real_escape_string ($connection, $member_id).',
            producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'",
            business_name = "'.mysqli_real_escape_string ($connection, $business_name).'",
            website = "'.mysqli_real_escape_string ($connection, $website).'",
            date_added = now(),'.
        /* Section III */'
            products = "'.mysqli_real_escape_string ($connection, $products).'",
            practices = "'.mysqli_real_escape_string ($connection, $practices).'",
            pest_management = "'.mysqli_real_escape_string ($connection, $pest_management).'",
            productivity_management = "'.mysqli_real_escape_string ($connection, $productivity_management).'",
            feeding_practices = "'.mysqli_real_escape_string ($connection, $feeding_practices).'",
            soil_management = "'.mysqli_real_escape_string ($connection, $soil_management).'",
            water_management = "'.mysqli_real_escape_string ($connection, $water_management).'",
            land_practices = "'.mysqli_real_escape_string ($connection, $land_practices).'",
            additional_information = "'.mysqli_real_escape_string ($connection, $additional_information).'",'.
        /* Section IV */'
            licenses_insurance = "'.mysqli_real_escape_string ($connection, $licenses_insurance).'",
            organic_products = "'.mysqli_real_escape_string ($connection, $organic_products).'",
            certifying_agency = "'.mysqli_real_escape_string ($connection, $certifying_agency).'",
            agency_phone = "'.mysqli_real_escape_string ($connection, $agency_phone).'",
            agency_fax = "'.mysqli_real_escape_string ($connection, $agency_fax).'",
            organic_cert = "'.mysqli_real_escape_string ($connection, $organic_cert).'"';
        // Run the producers_registration table insert query
        $result = @mysqli_query ($connection, $query2) or die (debug_print ("ERROR: 752243 ", array ($query2, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    elseif ($_POST['action'] == 'Update')
      {
        // Set the producers table query
        $query1 = '
          UPDATE
            '.TABLE_PRODUCER.'
          SET'.
         /* Section I */'
            business_name = "'.mysqli_real_escape_string ($connection, $business_name).'",
            producer_link = "'.mysqli_real_escape_string ($connection, $producer_link).'",
            '.($changed_by_self ? 'pending = "'.NEW_PRODUCER_PENDING.'",' : '').'
            '.($changed_by_self ? 'unlisted_producer = "'.NEW_PRODUCER_STATUS.'",' : '').'
            pub_address = "'.mysqli_real_escape_string ($connection, $pub_address).'",
            pub_email = "'.mysqli_real_escape_string ($connection, $pub_email).'",
            pub_email2 = "'.mysqli_real_escape_string ($connection, $pub_email2).'",
            pub_phoneh = "'.mysqli_real_escape_string ($connection, $pub_phoneh).'",
            pub_phonew = "'.mysqli_real_escape_string ($connection, $pub_phonew).'",
            pub_phonec = "'.mysqli_real_escape_string ($connection, $pub_phonec).'",
            pub_phonet = "'.mysqli_real_escape_string ($connection, $pub_phonet).'",
            pub_fax = "'.mysqli_real_escape_string ($connection, $pub_fax).'",
            pub_web = "'.mysqli_real_escape_string ($connection, $pub_web).'",'.
         /* Section II */'
            producttypes = "'.mysqli_real_escape_string ($connection, $producttypes).'",
            about = "'.mysqli_real_escape_string ($connection, $about).'",
            ingredients = "'.mysqli_real_escape_string ($connection, $ingredients).'",
            general_practices = "'.mysqli_real_escape_string ($connection, $general_practices).'",
            additional = "'.mysqli_real_escape_string ($connection, $additional).'",
            highlights = "'.mysqli_real_escape_string ($connection, $highlights).'",'.
         /* Section V */'
            liability_statement = "'.mysqli_real_escape_string ($connection, $liability_statement).'"
          WHERE
            producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
        // Run the producers table insert query
        $result = @mysqli_query ($connection, $query1) or die (debug_print ("ERROR: 762430 ", array ($query1, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        // Set the producers_registration table query
        $query2 = '
          UPDATE
            '.TABLE_PRODUCER_REG.'
          SET'.
        /* Section I */'
            business_name = "'.mysqli_real_escape_string ($connection, $business_name).'",
            website = "'.mysqli_real_escape_string ($connection, $website).'",
            date_added = now(),'.
        /* Section III */'
            products = "'.mysqli_real_escape_string ($connection, $products).'",
            practices = "'.mysqli_real_escape_string ($connection, $practices).'",
            pest_management = "'.mysqli_real_escape_string ($connection, $pest_management).'",
            productivity_management = "'.mysqli_real_escape_string ($connection, $productivity_management).'",
            feeding_practices = "'.mysqli_real_escape_string ($connection, $feeding_practices).'",
            soil_management = "'.mysqli_real_escape_string ($connection, $soil_management).'",
            water_management = "'.mysqli_real_escape_string ($connection, $water_management).'",
            land_practices = "'.mysqli_real_escape_string ($connection, $land_practices).'",
            additional_information = "'.mysqli_real_escape_string ($connection, $additional_information).'",'.
        /* Section IV */'
            licenses_insurance = "'.mysqli_real_escape_string ($connection, $licenses_insurance).'",
            organic_products = "'.mysqli_real_escape_string ($connection, $organic_products).'",
            certifying_agency = "'.mysqli_real_escape_string ($connection, $certifying_agency).'",
            agency_phone = "'.mysqli_real_escape_string ($connection, $agency_phone).'",
            agency_fax = "'.mysqli_real_escape_string ($connection, $agency_fax).'",
            organic_cert = "'.mysqli_real_escape_string ($connection, $organic_cert).'"
          WHERE
            producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
        // Run the producers_registration table insert query
        $result = @mysqli_query ($connection, $query2) or die (debug_print ("ERROR: 760534 ", array ($query2, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
      }
    // Make sure we let the member know the information was accepted
    $display_form_message .= '<p>Information has been accepted.</p>';
    // Set the session variable so the member has immediate access to producer functions
    $_SESSION['producer_id_you'] = $producer_id;
    // Get the producer's email address so we can send them a notification
    $query = '
      SELECT
        email_address
      FROM
        '.TABLE_MEMBER.'
      WHERE member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';
    $sql =  @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 785403 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_object ($sql))
      {
        $email_address = $row->email_address;
      }
    // Now send email notification(s)
    $email_to = preg_replace ('/SELF/', $email_address, PRODUCER_FORM_EMAIL);
    if ($_POST['action'] == 'Update')
      $email_subject = 'Updated producer info. for '.SITE_NAME.'';
    else
      $email_subject = 'New producer: Welcome to '.SITE_NAME;
    $boundary = uniqid();
    // Set up the email preamble...
    $email_preamble = '<p>Following is a copy of the producer information you submitted to '.SITE_NAME.'.</p>';
    $email_preamble .= $welcome_message;
    // Need to break DOMAIN_NAME into an array of separate names so we can use the first element
    $domain_names = preg_split("/[\n\r]+/", DOMAIN_NAME);
    // Disable all form elements for emailing
    $html_version = preg_replace ('/<(input|select|textarea)/', '<\1 disabled', $welcome_message.$display_form_html);
    $email_headers  = "From: ".STANDARDS_EMAIL."\r\n";
    $email_headers .= "Reply-To: ".STANDARDS_EMAIL."\r\n";
    $email_headers .= "Errors-To: web@".($domain_names[0])."\r\n";
    $email_headers .= "MIME-Version: 1.0\r\n";
    $email_headers .= "Content-type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $email_headers .= "Message-ID: <".md5(uniqid(time()))."@".($domain_names[0]).">\r\n";
    $email_headers .= "X-Mailer: PHP ".phpversion()."\r\n";
    $email_headers .= "X-Priority: 3\r\n";
    $email_headers .= "X-AntiAbuse: This is a machine-generated response to a user-submitted form at ".SITE_NAME.".\r\n";
    $email_body .= "\r\n--".$boundary;
    $email_body .= "\r\nContent-Type: text/plain; charset=us-ascii";
    $email_body .= "\r\n\r\n".wordwrap (strip_tags ($welcome_message), 75, "\n", true)."\n".$display_form_text;
    $email_body .= "\r\n--".$boundary;
    $email_body .= "\r\nContent-Type: text/html; charset=us-ascii";
    $email_body .= "\r\n\r\n--".$html_version;
    $email_body .= "\r\n--".$boundary.'--';
    mail ($email_to, $email_subject, $email_body, $email_headers);
    if ($changed_by_self && (NEW_PRODUCER_PENDING || NEW_PRODUCER_STATUS != 0))
      {
        $display_form_message .= '
          <div class="submission_form producer_form">
            <p>Your producer application will be reviewed by an administrator and you will be notified when
            it becomes active.  Until then, you will not have producer access or be able to enter products into the system.</p>
          </div>';
      }
    else
      {
        $display_form_message .= '
          <div class="submission_form producer_form">
            <p>Your producer application will be reviewed by an administrator.  You have access
            to enter products into the system through your member account.</p>
          </div>';
      }
    // Clear the session variables to prevent any further action on this form
    unset ($_SESSION['new_member_id']);
    unset ($_SESSION['new_business_name']);
    unset ($_SESSION['new_website']);
    unset ($_SESSION['new_membership_type_id']);
    // Clobber the form display so it is not shown. This also prevents re-submission.
    $display_form_html = '
      </div>';
  }

$page_specific_css = '
  /* Styles for submit/reset/clear/etc. form buttons */
  .form_buttons {
    position:fixed;
    left:10px;
    bottom:10px;
    }
  .form_buttons button {
    display:block;
    clear:both;
    width:5em;
    margin-bottom:2em;
    }
  fieldset.privacy_info,
  fieldset.producer_info,
  fieldset.production_info,
  fieldset.certifications,
  fieldset.agreement {
    background-color:#f8f4f0;
    width:80%;
    text-align:left;
    }
  fieldset.privacy_info legend,
  fieldset.producer_info legend,
  fieldset.production_info legend,
  fieldset.certifications legend,
  fieldset.agreement legend {
    background-color:#f8f4f0;
    }
  .grouping_block .input_block {
    float:left;
    }
  /* Force some fields to begin new lines at the left margin */
  .organic_cert,
  .note,
  .business_name {
    clear:left;
    }
  .input_block.liability_statement {
    text-align:left;
    padding:1rem;
    }
  .input_block.privacy_check {
    margin-right:2rem;
    }
  .note {
    float:left;
    font-size:0.9em;
    color:#433;
    text-align:left;
    padding:1rem;
    }
  .input_block.denied {
    opacity:0.2;
    }
  .input_block.required {
    opacity:0.3;
    }
  .input_block.text_block {
    margin: 0;
    padding: 0;
    width: 100%;
    margin-top:1rem;
    }
  .input_block textarea {
    box-sizing: border-box;
    height: 10rem;
    margin: 0;
    width: 100%;
    }
  #producer_link_message {
    display:inline;
    margin-left:3rem;
    }
  .producer_link_example {
    font-family: courier;
    font-size:70%;
    }
  .producer_link_example > span {
    font-weight:bold;
    }';

$page_specific_javascript = '
  function check_producer_link () {
    var allowed_chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-.";
    var producer_link;
    var producer_link_temp;
    producer_link = document.getElementById("producer_link").value;
    producer_id = "'.$producer_id.'";

    // Strip out any "bad" characters
    producer_link_temp = "";
    for (i = 0; i < producer_link.length; i++)
      {
        if (allowed_chars.indexOf(producer_link.charAt(i)) != -1)
          producer_link_temp += producer_link.charAt(i);
      }
    if (producer_link != producer_link_temp)
      {
        producer_link = producer_link_temp;
        document.getElementById("producer_link").value = producer_link_temp;
      }
    // Validate the producer_link field
    if (producer_link.length < 5)
      {
        document.getElementById("producer_link_message").style.color = "#800000";
        document.getElementById("producer_link_message").innerHTML = "Too short";
      }
    else if (producer_link.length > 50)
      {
        document.getElementById("producer_link_message").style.color = "#800000";
        document.getElementById("producer_link_message").innerHTML = "Too long";
      }
    else
      {
        document.getElementById("producer_link_message").innerHTML = "";
        // Use Ajax to check if the producer_link is available
        jQuery.post
          (
            "'.PATH.'ajax/check_producer_link.php",
              {
                producer_link: producer_link,
                producer_id: producer_id,
              },
            function(data)
              {
                if (data == "avail")
                  {
                    document.getElementById("producer_link_message").style.color = "#000080";
                    document.getElementById("producer_link_message").innerHTML = "Link is available";
                  }
                else if (data == "used")
                  {
                    document.getElementById("producer_link_message").style.color = "#800000";
                    document.getElementById("producer_link_message").innerHTML = "Link is NOT available";
                  }
              }
          )
      }
    }';

$page_title_html = '<span class="title">Producer Info.</span>';
$page_subtitle_html = '<span class="subtitle">'.(! $producer_id ? 'New Producer Application Form' : 'Edit All Producer Information').'</span>';
$page_title = 'Producer Info: '.(! $producer_id ? 'New Producer Application Form' : 'Edit All Producer Information');
$page_tab = 'producer_panel';

include ("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->'.
  $display_form_message.
  $display_form_html.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
