<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone

if ( $_SESSION['member_id'] )
  {
    $member_id = $_SESSION['member_id'];
    $show_notice = true;
  }
elseif ( $_SESSION['new_member_id'] )
  {
    // $_SESSION['new_member_id'] was assigned on the member_form
    $member_id = $_SESSION['new_member_id'];
    $show_notice = false;
  }
else
  {
    header( "Location: index.php");
    exit;
  }

// Set the $producer_id_you
$producer_id_you = $_SESSION['producer_id_you'];
$producer_id = $producer_id_you;
// Then clobber it if this is for a new producer.
// We can't clobber the session variable without causing other troubles.
if ($_GET['action'] == 'new_producer')
  $producer_id = '';

if ($_SESSION['member_id'])
  {
    $membership_type_id = $_SESSION['renewal_info']['membership_type_id'];
  }
elseif ($_SESSION['new_member_id'])
  {
//     $error_message = 'Membership information has been accepted. In order to be considered as a producer
//     for the '.ORGANIZATION_TYPE.', please fill out the form below.  If you do not have time to fill out
//     the form now, you may want to print it and return to the form at a later time.';
    $website = $_SESSION['new_website'];
    $business_name = $_SESSION['new_business_name'];
    $membership_type_id = $_SESSION['new_membership_type_id'];
  }

$error_array = array ();

// SPECIAL NOTES ABOUT THIS PAGE: //////////////////////////////////////////////
//                                                                            //
// This page MAY be accessed by visitors without logging in.  However, it     //
// will only be accessed by visitors who either are already accepted members  //
// or who have already supplied an application for membership.                //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////


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
        if ($_POST['action'] == 'Update')
          $changed_information = true; // Means this is changes to existing data -- instead of new data
        // Clobber $producer_id because we are processing the submission and we don't need it at this point
        // but keeps our logic straight -- this is so we don't display the NOTICE message
        $show_notice = false;
//         $producer_id = '';

/* Section I - Credentials and Privacy */
        $member_id = $_POST['member_id'];
        if ($member_id == $_SESSION['member_id'])
          $changed_by_self = true; // Keep the 'Update' option if we were already updating
        // Producers are now permitted to change their [new] producer_link reference
        $producer_link = preg_replace ('/[^0-9A-Za-z\-\._]/','', $_POST['producer_link']); // Only: A-Z a-z 0-9 - . _
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

/* Section II - General Producer Information (Public Profile) */
        $producttypes = $_POST['producttypes'];
        $about = $_POST['about'];
        $ingredients = $_POST['ingredients'];
        $general_practices = $_POST['general_practices'];
        $additional = $_POST['additional'];
        $highlights = $_POST['highlights'];

/* Section III - Production Specifics - Producer Questionaire (Registration for Review by PCC) */
        $products = $_POST['products'];
        $practices = $_POST['practices'];
        $pest_management = $_POST['pest_management'];
        $productivity_management = $_POST['productivity_management'];
        $feeding_practices = $_POST['feeding_practices'];
        $soil_management = $_POST['soil_management'];
        $water_management = $_POST['water_management'];
        $land_practices = $_POST['land_practices'];
        $additional_information = $_POST['additional_information'];

/* Section IV - Certifications (Registration for Review by PCC) */
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
                producer_link = "'.mysql_real_escape_string ($producer_link).'"
                AND producer_id != "'.mysql_real_escape_string ($producer_id).'"';
            $sql =  @mysql_query($query, $connection) or die(debug_print ("ERROR: 857403 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysql_fetch_object($sql))
              {
                if ($row->count > 0)
                  {
                    array_push ($error_array, 'The Producer link you have chosen is already in use, please select a different link');
                    $producer_link = $_POST['producer_link_prior'];
                  }
              }
          }
        if ( !$business_name )
          array_push ($error_array, 'A business name is required in order to register as a producer');
        if ( $pub_email + $pub_email2 + $pub_phoneh + $pub_phonew + $pub_phonec + $pub_phonet == 0 )
          array_push ($error_array, 'Please list either an email or phone number. Customers need some way to contact you.');
        if ( strlen ($products) == 0 )
          array_push ($error_array, 'Please list some of the products you intend to sell.');
        if ( $liability_statement != 1 )
          array_push ($error_array, 'In order to be accepted as a producer, you must agree with the stated terms');
        if (PRODUCER_PUB_WEB == 'REQUIRED' &&
            strlen ($website) < 1)
            array_push ($error_array, 'Producer Website is '.PRODUCER_PUB_WEB);

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
        if (PRODUCER_REQ_EMAIL && (! $pub_email && ! $pub_email2))
          {
            array_push ($error_array, 'At least one e-mail address is required.');
          }
        if (PRODUCER_REQ_PHONE && (! $pub_phoneh && ! $pub_phonew && !$pub_phonec && ! $pub_phonet))
          {
            array_push ($error_array, 'At least one telephone number is required.');
          }
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
            '.TABLE_PRODUCER.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"';
        $sql =  @mysql_query($query, $connection) or die(debug_print ("ERROR: 762905 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysql_fetch_object($sql))
          {
/* Section I - Credentials and Privacy */
            $member_id = $row->member_id;
            $changed_information = true; // Means this is changes to existing data -- instead of new data
            if ($member_id == $_SESSION['member_id'])
              $changed_by_self = true; // Means the producer pending and unlisted_producer will be reset to new-producer values
//             $producer_id = $producer_id_you;
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

/* Section II - General Producer Information (Public Profile) */
            $producttypes = $row->producttypes;
            $about = $row->about;
            $ingredients = $row->ingredients;
            $general_practices = $row->general_practices;
            $additional = $row->additional;
            $highlights = $row->highlights;

/* Section III - Production Specifics - Producer Questionaire (Registration for Review by PCC) */
            $products = $row->products;
            $practices = $row->practices;
            $pest_management = $row->pest_management;
            $productivity_management = $row->productivity_management;
            $feeding_practices = $row->feeding_practices;
            $soil_management = $row->soil_management;
            $water_management = $row->water_management;
            $land_practices = $row->land_practices;
            $additional_information = $row->additional_information;

/* Section IV - Certifications (Registration for Review by PCC) */
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
$error_message = '';
if (count ($error_array) > 0) $error_message = '
  <div class="error_message open" onmouseover="$(this).removeClass(\'open\')">
    <p class="message">The information was not accepted. Please correct the following problems and resubmit.
      <ul class="error_list">
        <li>'.implode ("</li>\n<li>", $error_array).'</li>
      </ul>
    </p>
  </div>';

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//  SET UP THE SELECT AND CHECKBOX FORMS FOR DISPLAY BASED UPON PRIOR VALUES  //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

/* Section I - Credentials and Privacy */
if (($pub_address == '1' || PRODUCER_PUB_ADDRESS == 'REQUIRED') && PRODUCER_PUB_ADDRESS != 'DENIED') $pub_address_check = ' checked';
else $pub_address = '0';

if (($pub_email == '1' || PRODUCER_PUB_EMAIL == 'REQUIRED') && PRODUCER_PUB_EMAIL != 'DENIED') $pub_email_check = ' checked';
else $pub_email = '0';

if (($pub_email2 == '1' || PRODUCER_PUB_EMAIL2 == 'REQUIRED') && PRODUCER_PUB_EMAIL2 != 'DENIED') $pub_email2_check = ' checked';
else $pub_email2 = '0';

if (($pub_phoneh == '1' || PRODUCER_PUB_PHONEH == 'REQUIRED') && PRODUCER_PUB_PHONEH != 'DENIED') $pub_phoneh_check = ' checked';
else $pub_phoneh = '0';

if (($pub_phonew == '1' || PRODUCER_PUB_PHONEW == 'REQUIRED') && PRODUCER_PUB_PHONEW != 'DENIED') $pub_phonew_check = ' checked';
else $pub_phonew = '0';

if (($pub_phonec == '1' || PRODUCER_PUB_PHONEC == 'REQUIRED') && PRODUCER_PUB_PHONEC != 'DENIED') $pub_phonec_check = ' checked';
else $pub_phonec = '0';

if (($pub_phonet == '1' || PRODUCER_PUB_PHONET == 'REQUIRED') && PRODUCER_PUB_PHONET != 'DENIED') $pub_phonet_check = ' checked';
else $pub_phonet = '0';

if (($pub_fax == '1' || PRODUCER_PUB_FAX == 'REQUIRED') && PRODUCER_PUB_FAX != 'DENIED') $pub_fax_check = ' checked';
else $pub_fax = '0';

if (($pub_web == '1' || PRODUCER_PUB_WEB == 'REQUIRED') && PRODUCER_PUB_WEB != 'DENIED') $pub_web_check = ' checked';
else $pub_web = '0';


/* Section IV - Certifications (Registration for Review by PCC) */
if ($organic_cert == '1') $organic_cert_yes = ' checked';
else $organic_cert = '0';

if ($organic_cert == '0') $organic_cert_no = ' checked';

if ($liability_statement == 1) $liability_statement_check = ' checked';
else $liability_statement = '0';

// /* Section I - Credentials and Privacy */
// if ($pub_address != '1') $pub_address = '0';
// if ($pub_email != '1') $pub_email = '0';
// if ($pub_email2 != '1') $pub_email2 = '0';
// if ($pub_phoneh != '1') $pub_phoneh = '0';
// if ($pub_phonew != '1') $pub_phonew = '0';
// if ($pub_phonec != '1') $pub_phonec = '0';
// if ($pub_phonet != '1') $pub_phonet = '0';
// if ($pub_fax != '1') $pub_fax = '0';
// if ($pub_web != '1') $pub_web = '0';

// /* Section IV - Certifications (Registration for Review by PCC) */
// if ($organic_cert != '1') $organic_cert = '0';
// 
// if ($liability_statement != 1) $liability_statement = '0';

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                          DISPLAY THE INPUT FORM                            //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

$display_form_top .= '
  <div style="margin:auto;width:90%;padding:1em;">';

/* $display_form_text used for e-mail text-only */

$display_form_text .= '
'./* Section I */'
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
'./* Section II */'
  About Us:
    '.str_replace ("\n", "\n    ", wordwrap($about, 71, "\n", true)).'

  Product Types:
    '.str_replace ("\n", "\n    ", wordwrap($producttypes, 71, "\n", true)).'

  Ingredients:
    '.str_replace ("\n", "\n    ", wordwrap($ingredients, 71, "\n", true)).'

  Practices:
    '.str_replace ("\n", "\n    ", wordwrap($general_practices, 71, "\n", true)).'

  Additional Information:
    '.str_replace ("\n", "\n    ", wordwrap($additional, 71, "\n", true)).'

  Highlights This Cycle:
    '.str_replace ("\n", "\n    ", wordwrap($highlights, 71, "\n", true)).'
'./* Section III */'
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
'./* Section IV */'
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

  ['.strtr ($organic_cert, " 01", "  X").'] I have available for inspection a copy of your current organic
      certificate.
'./* Section V */'
  ['.strtr ($liability_statement, " 01", "  X").'] '.str_replace ("\n", "\n      ", wordwrap ('I affirm that all statements made about my farm and products in this application are true, correct and complete and I have given a truthful representation of my operation, practices, and origin of products. I understand that if questions arise about my operation I may be inspected (unannounced) by '.SITE_NAME.'. If I stated my operation is organic, then I am complying with the National Organic Program and will provide upon request a copy of my certification.  I have read all of '.SITE_NAME."'".'s Subscription Agreement and fully understand and am willing to comply with them.', 75, "\n", true));

if ($_POST['action'] == 'Update')
  $welcome_message .= '<p><em>Changes have been made to your producer information at '.SITE_NAME.'.  Following is a record of those changes.</em></p>';
else
  $welcome_message .= '<p><em>Thank you for your interest in becoming a producer member of '.SITE_NAME.'. '.SITE_NAME.' customers and producers are interested in local foods and products produced with sustainable practices that demonstrate good stewardship of the environment. Upon approval this form will register you to sell products within '.SITE_NAME.'.  Please read the <a href="'.TERMS_OF_SERVICE.'" target="_blank">Terms of Service</a>, and then complete the following information and click submit.</em></p>';
/* HTML Page and E-mail */
$display_form_html .= $error_message.'
  <form action="'.$_SERVER['SCRIPT_NAME'].'" name="producer_form" id="producer_form" method="post">

    <table cellspacing="15" cellpadding="2" width="100%" border="1" align="center">
      <tbody>
'./* Section I */'
      <tr>
        <th class="memberform">Section 1: Credentials and Privacy</th>
      </tr>

      <tr>
        <td>
          <table id="section1a">
            <tr>
              <td class="form_key"><strong>Member&nbsp;ID:</strong></td>
              <td><input maxlength="6" size="10" name="disabled" value="'.$member_id.'" disabled><input type="hidden" name="member_id" value="'.$member_id.'"></td>
              <td class="form_key"><strong>Web&nbsp;Address:</strong></td>
              <td>
                <div id="producer_link">
                  <input id="producer_link_field" maxlength="50" size="40" name="producer_link" placeholder="my-organic-farm" value="'.$producer_link.'" onKeyUp="check_producer_link()"><input type="hidden" name="producer_link_prior" value="'.$producer_link.'">
                  <div id="producer_link_message"></div>
                </div>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                Member ID<br>can not be changed<br>
              </td>
              <td colspan="2">
                Choose a unique web address for your operation on the site.  It may contain letters,
                numbers, dash, dot, and/or underline and must be between five and fifty characters.
                It will help list your information with search engines and allow web users to directly
                access your information with an address like:
                <nobr><em>'.BASE_URL.PATH.'producers/my-organic-farm</em></nobr>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td id="section1b">
          <div class="business_info required">
            <span class="bus_desc">Business&nbsp;Name</span>
            <span class="bus_input"><input maxlength="50" size="45" name="business_name" value="'.htmlspecialchars ($business_name, ENT_QUOTES).'"></span>
            <span class="bus_req">(required)</span>
          </div>
          <div class="business_info '.strtolower(PRODUCER_PUB_WEB).'">
            <span class="bus_desc">Website</span>
            <span class="bus_input"><input maxlength="50" size="45" name="website" value="'.htmlspecialchars ($website, ENT_QUOTES).'"></span>
            <span class="bus_req">('.strtolower (PRODUCER_PUB_WEB).')</span>
          </div>
        </td>
      </tr>

      <tr>
        <td id="section1c">
          <div class="privacy '.strtolower(PRODUCER_PUB_ADDRESS).'">
            <span class="pub_check"><input type="checkbox" name="pub_address" value="1" '.$pub_address_check.'></span>
            <span class="pub_desc">Publish Home Address</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_ADDRESS).')</span>
          </div>
          <div class="privacy '.strtolower(PRODUCER_PUB_EMAIL).'">
            <span class="pub_check"><input type="checkbox" name="pub_email" value="1" '.$pub_email_check.'></span>
            <span class="pub_desc">Publish Primary Email Address</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_EMAIL).')</span>
          </div>
          <div class="privacy '.strtolower(PRODUCER_PUB_EMAIL2).'">
            <span class="pub_check"><input type="checkbox" name="pub_email2" value="1" '.$pub_email2_check.'></span>
            <span class="pub_desc">Publish Secondary Email Address</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_EMAIL2).')</span>
          </div>
          <div class="privacy '.strtolower(PRODUCER_PUB_PHONEH).'">
            <span class="pub_check"><input type="checkbox" name="pub_phoneh" value="1" '.$pub_phoneh_check.'></span>
            <span class="pub_desc">Publish Home Phone Number</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_PHONEH).')</span>
          </div>
          <div class="privacy '.strtolower(PRODUCER_PUB_PHONEW).'">
            <span class="pub_check"><input type="checkbox" name="pub_phonew" value="1" '.$pub_phonew_check.'></span>
            <span class="pub_desc">Publish Work Phone Number</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_PHONEW).')</span>
          </div>
          <div class="privacy '.strtolower(PRODUCER_PUB_PHONEC).'">
            <span class="pub_check"><input type="checkbox" name="pub_phonec" value="1" '.$pub_phonec_check.'></span>
            <span class="pub_desc">Publish Mobile Phone Number</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_PHONEC).')</span>
          </div>
          <div class="privacy '.strtolower(PRODUCER_PUB_PHONET).'">
            <span class="pub_check"><input type="checkbox" name="pub_phonet" value="1" '.$pub_phonet_check.'></span>
            <span class="pub_desc">Publish Toll-Free Phone Number</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_PHONET).')</span>
          </div>
          <div class="privacy '.strtolower(PRODUCER_PUB_FAX).'">
            <span class="pub_check"><input type="checkbox" name="pub_fax" value="1" '.$pub_fax_check.'></span>
            <span class="pub_desc">Publish FAX Number</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_FAX).')</span>
          </div>
          <div class="privacy '.strtolower(PRODUCER_PUB_WEB).'">
            <span class="pub_check"><input type="checkbox" name="pub_web" value="1" '.$pub_web_check.'></span>
            <span class="pub_desc">Publish Web Page</span>
            <span class="pub_req">('.strtolower (PRODUCER_PUB_WEB).')</span>
          </div>
        </td>
      </tr>
'./* Section II */'
      <tr>
        <th class="memberform">Section 2: General Producer Information<p style="font-weight:normal;">Answers to these questions will appear on your producer information page and may be updated by you at any time.</p></th>
      </tr>

      <tr>
        <td>
          <table id="section2a" width="100%">
            <tr>
              <td class="form_key"><strong>Product Types:</strong><br>
                List keywords like lettuce, berries, buffalo, soap, etc.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="producttypes">'.htmlspecialchars ($producttypes, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section2b" width="100%">
            <tr>
              <td class="form_key"><strong>About Us:</strong><br>
                Use this space to describe your business, you, how you got started, etc.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="about">'.htmlspecialchars ($about, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section2c" width="100%">
            <tr>
              <td class="form_key"><strong>Ingredients:</strong><br>
                Use this space to outline ingredients if relevant.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="ingredients">'.htmlspecialchars ($ingredients, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section2d" width="100%">
            <tr>
              <td class="form_key"><strong>Practices:</strong><br>
                Use this space to describe your standards and practices. For example, if you use all
                natural products, etc.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="general_practices">'.htmlspecialchars ($general_practices, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section2e" width="100%">
            <tr>
              <td class="form_key"><strong>Additional Information:</strong><br>
              Use this space for anything that is not covered in these other sections.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="additional">'.htmlspecialchars ($additional, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section2f" width="100%">
            <tr>
              <td class="form_key"><strong>Highlights This Month:</strong><br>
                Use this section for notes that are relevant to the current month.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="highlights">'.htmlspecialchars ($highlights, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>
'./* Section III */'
      <tr>
        <th class="memberform">Section 3: Production Specifics &ndash; Producer Questionnaire<p style="font-weight:normal;">This information is a part of your original producer questionnaire and will available for review on the website as a link from your producer information page.</p></th>
      </tr>

      <tr>
        <td>
          <table id="section3a" width="100%">
            <tr>
              <td class="form_key"><strong>Products:</strong><br>
                List the types of products you intend to sell through '.SITE_NAME.'
                (e.g. meats, grains, jellies, crafts; also note if you have any heritage breeds).</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="products">'.htmlspecialchars ($products, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section3b" width="100%">
            <tr>
              <td class="form_key"><strong>Practices:</strong><br>
                Describe your farming, processing and/or crafting practices.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="practices">'.htmlspecialchars ($practices, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section3c" width="100%">
            <tr>
              <td class="form_key"><strong>Pest Management:</strong><br>
                Describe your pest and disease management system.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="pest_management">'.htmlspecialchars ($pest_management, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section3d" width="100%">
            <tr>
              <td class="form_key"><strong>Productivity Management:</strong><br>
                Describe your herd health and productivity management (i.e. do you use any hormones,
                antibiotics, and/or steroids).</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="productivity_management">'.htmlspecialchars ($productivity_management, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section3e" width="100%">
            <tr>
              <td class="form_key"><strong>Feeding Practices:</strong><br>
                Describe your feeding practices &ndash; grass-fed only, free-range, feed-lot, etc.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="feeding_practices">'.htmlspecialchars ($feeding_practices, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section3f" width="100%">
            <tr>
              <td class="form_key"><strong>Soil Management:</strong><br>
                Describe your soil and nutrient management. Do you compost, use fertilizers, green
                manures or animal manures?</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="soil_management">'.htmlspecialchars ($soil_management, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section3g" width="100%">
            <tr>
              <td class="form_key"><strong>Water Management:</strong><br>
                Describe your water usage practices. If you irrigate, describe how (e.g. deep well,
                surface water, etc.), and explain how you conserve water or use best management practices.
                Describe how you are protecting your water source from contamination/erosion.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="water_management">'.htmlspecialchars ($water_management, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section3h" width="100%">
            <tr>
              <td class="form_key"><strong>Land Practices:</strong><br>
                Describe your conservation/land stewardship practices.  E.g. do you plant
                windbreaks, maintain grass waterways, riparian buffers, use green manures
                for wind erosion, plant habitats for birds, improve soil quality, etc.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="land_practices">'.htmlspecialchars ($land_practices, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section3i" width="100%">
            <tr>
              <td class="form_key"><strong>Additional Information:</strong><br>
                Describe any additional information and/or sustainable practices about your operation
                that would be helpful to a potential customer in understanding your farm or operation better
                (e.g. if you are raising any heritage animals you might list breeds or list varieties of
                heirloom seeds. List the percentage of local ingredients in your processed items).</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="additional_information">'.htmlspecialchars ($additional_information, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>
'./* Section IV */'
      <tr>
        <th class="memberform">Section 4: Certifications<p style="font-weight:normal;">This information is a part of your original producer questionnaire and will available for review on the website as a link from your producer information page.</p></th>
      </tr>

      <tr>
        <td>
          <table id="section4a" width="100%">
            <tr>
              <td class="form_key"><strong>Insurance, Licenses, and Tests:</strong><br>
                List your food liability insurance coverage, both general and product-related, as well as
                any licenses and tests that you have available. (if applicable).  As this is required to
                market products through the '.ORGANIZATION_TYPE.', you will be required to provide copies of the above
                when you receive confirmation of approval by '.SITE_NAME.'.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="licenses_insurance">'.htmlspecialchars ($licenses_insurance, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section4b" width="100%">
            <tr>
              <td class="form_key"><strong>Organic Products:</strong><br>
              List which products you are selling as organic.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="organic_products">'.htmlspecialchars ($organic_products, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section4c" width="100%">
            <tr>
              <td class="form_key"><strong>Organic Certifying Agency:</strong><br>
              List orgainic certifying agency&#146;s name and address.</td>
            </tr>
            <tr>
              <td><textarea cols="80" rows="5" name="certifying_agency">'.htmlspecialchars ($certifying_agency, ENT_QUOTES).'</textarea></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section4e" width="100%">
            <tr>
              <td class="form_key" align="right"><strong>Certifying&nbsp;Agency&#146;s&nbsp;Phone:</strong></td>
              <td align="left"><input maxlength="20" size="15" name="agency_phone" value="'.htmlspecialchars ($agency_phone, ENT_QUOTES).'"></td>
              <td class="form_key" align="right"><strong>Certifying&nbsp;Agency&#146;s&nbsp;FAX:</strong></td>
              <td align="left"><input maxlength="20" size="15" name="agency_fax" value="'.htmlspecialchars ($agency_fax, ENT_QUOTES).'"></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table id="section4f" width="100%">
            <tr>
              <td class="form_key"><strong>Do you have available for inspection a copy of your current organic certificate?</strong></td>
              <td><input type="radio" name="organic_cert" value="1"'.$organic_cert_yes.'> Yes
              &nbsp; &nbsp; &nbsp; <input type="radio" name="organic_cert" value="0"'.$organic_cert_no.'> No</td>
            </tr>
          </table>
        </td>
      </tr>
'./* Section V */'
      <tr>
        <th class="memberform">Section 5: Terms and Agreement<p style="font-weight:normal;">Affirmation of this agreement is a part of your original producer questionnaire and will available for review on the website as a link from your producer information page.</p></th>
      </tr>

      <tr>
        <td>
          <table id="section5a">
            <tr>
              <td>
                I affirm that all statements made about my farm and products in this application are true,
                correct and complete and I have given a truthful representation of my operation, practices,
                and origin of products. I understand that if questions arise about my operation I may be
                inspected (unannounced) by '.SITE_NAME.'. If I stated my operation is organic, then I am
                complying with the National Organic Program and will provide upon request a copy of my
                certification.  I have read all of '.SITE_NAME.'&#146;s <a href="'.TERMS_OF_SERVICE.'" target="_blank">
                Terms of Service</a> and fully understand and am willing to comply with them.
              </td>
            </tr>
            <tr>
              <td align="center"><input type="checkbox" name="liability_statement" value="1"'.$liability_statement_check.'> I agree</td>
            </tr>
          </table>
        </td>
      </tr>
      ';

$display_form_html .= '
      <tr>
        <td align="center">
          <input type="submit" name="action" value="'.($changed_information ? 'Update' : 'Submit').'">
        </td>
      </tr>
      </tbody>
    </table>
  </form></div>';

    if ($producer_id && (NEW_PRODUCER_PENDING || NEW_PRODUCER_STATUS != 0) && $show_notice)
      {
        $display_form_message .= '
          <div class="error_message">
            <p class="message"><strong>IMPORTANT NOTICE!</strong><br /><br />
              Any update to this page will cause your producer status to become &quot;pending&quot; again.
              If you only need to change the basic information on your producer page (Section 2 below),
              please use <a href="'.PATH.'edit_producer_info.php">this link</a> instead.</p>
          </div>';
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
        member_id = "'.mysql_real_escape_string ($member_id).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 760232 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
              WHERE membership_type_id ="'.mysql_real_escape_string ($membership_type_id).'"),'.
         /* Section I */'
            member_id = '.mysql_real_escape_string ($member_id).',
            business_name = "'.mysql_real_escape_string ($business_name).'",
            producer_link = "'.mysql_real_escape_string ($producer_link).'",
            pub_address = "'.mysql_real_escape_string ($pub_address).'",
            pub_email = "'.mysql_real_escape_string ($pub_email).'",
            pub_email2 = "'.mysql_real_escape_string ($pub_email2).'",
            pub_phoneh = "'.mysql_real_escape_string ($pub_phoneh).'",
            pub_phonew = "'.mysql_real_escape_string ($pub_phonew).'",
            pub_phonec = "'.mysql_real_escape_string ($pub_phonec).'",
            pub_phonet = "'.mysql_real_escape_string ($pub_phonet).'",
            pub_fax = "'.mysql_real_escape_string ($pub_fax).'",
            pub_web = "'.mysql_real_escape_string ($pub_web).'",'.
         /* Section II */'
            producttypes = "'.mysql_real_escape_string ($producttypes).'",
            about = "'.mysql_real_escape_string ($about).'",
            ingredients = "'.mysql_real_escape_string ($ingredients).'",
            general_practices = "'.mysql_real_escape_string ($general_practices).'",
            additional = "'.mysql_real_escape_string ($additional).'",
            highlights = "'.mysql_real_escape_string ($highlights).'",'.
         /* Section V */'
            liability_statement = "'.mysql_real_escape_string ($liability_statement).'"';
        // Run the producers table insert query and get back the producer_id
        $result = @mysql_query($query1, $connection) or die(debug_print ("ERROR: 574303 ", array ($query1,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $producer_id = mysql_insert_id();
        // Set the producers_registration table query
        $query2 = '
          INSERT INTO
            '.TABLE_PRODUCER_REG.'
          SET'.
        /* Section I */'
            member_id = '.mysql_real_escape_string ($member_id).',
            producer_id = "'.mysql_real_escape_string ($producer_id).'",
            business_name = "'.mysql_real_escape_string ($business_name).'",
            website = "'.mysql_real_escape_string ($website).'",
            date_added = now(),'.
        /* Section III */'
            products = "'.mysql_real_escape_string ($products).'",
            practices = "'.mysql_real_escape_string ($practices).'",
            pest_management = "'.mysql_real_escape_string ($pest_management).'",
            productivity_management = "'.mysql_real_escape_string ($productivity_management).'",
            feeding_practices = "'.mysql_real_escape_string ($feeding_practices).'",
            soil_management = "'.mysql_real_escape_string ($soil_management).'",
            water_management = "'.mysql_real_escape_string ($water_management).'",
            land_practices = "'.mysql_real_escape_string ($land_practices).'",
            additional_information = "'.mysql_real_escape_string ($additional_information).'",'.
        /* Section IV */'
            licenses_insurance = "'.mysql_real_escape_string ($licenses_insurance).'",
            organic_products = "'.mysql_real_escape_string ($organic_products).'",
            certifying_agency = "'.mysql_real_escape_string ($certifying_agency).'",
            agency_phone = "'.mysql_real_escape_string ($agency_phone).'",
            agency_fax = "'.mysql_real_escape_string ($agency_fax).'",
            organic_cert = "'.mysql_real_escape_string ($organic_cert).'"';
        // Run the producers_registration table insert query
        $result = @mysql_query($query2,$connection) or die(debug_print ("ERROR: 752243 ", array ($query2,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
      }
    elseif ($_POST['action'] == 'Update')
      {
        // Set the producers table query
        $query1 = '
          UPDATE
            '.TABLE_PRODUCER.'
          SET'.
         /* Section I */'
            business_name = "'.mysql_real_escape_string ($business_name).'",
            producer_link = "'.mysql_real_escape_string ($producer_link).'",
            '.($changed_by_self ? 'pending = "'.NEW_PRODUCER_PENDING.'",' : '').'
            '.($changed_by_self ? 'unlisted_producer = "'.NEW_PRODUCER_STATUS.'",' : '').'
            pub_address = "'.mysql_real_escape_string ($pub_address).'",
            pub_email = "'.mysql_real_escape_string ($pub_email).'",
            pub_email2 = "'.mysql_real_escape_string ($pub_email2).'",
            pub_phoneh = "'.mysql_real_escape_string ($pub_phoneh).'",
            pub_phonew = "'.mysql_real_escape_string ($pub_phonew).'",
            pub_phonec = "'.mysql_real_escape_string ($pub_phonec).'",
            pub_phonet = "'.mysql_real_escape_string ($pub_phonet).'",
            pub_fax = "'.mysql_real_escape_string ($pub_fax).'",
            pub_web = "'.mysql_real_escape_string ($pub_web).'",'.
         /* Section II */'
            producttypes = "'.mysql_real_escape_string ($producttypes).'",
            about = "'.mysql_real_escape_string ($about).'",
            ingredients = "'.mysql_real_escape_string ($ingredients).'",
            general_practices = "'.mysql_real_escape_string ($general_practices).'",
            additional = "'.mysql_real_escape_string ($additional).'",
            highlights = "'.mysql_real_escape_string ($highlights).'",'.
         /* Section V */'
            liability_statement = "'.mysql_real_escape_string ($liability_statement).'"
          WHERE
            producer_id = "'.mysql_real_escape_string ($producer_id).'"';
        // Run the producers table insert query
        $result = @mysql_query($query1, $connection) or die(debug_print ("ERROR: 762930 ", array ($query1,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // Set the producers_registration table query
        $query2 = '
          UPDATE
            '.TABLE_PRODUCER_REG.'
          SET'.
        /* Section I */'
            business_name = "'.mysql_real_escape_string ($business_name).'",
            website = "'.mysql_real_escape_string ($website).'",
            date_added = now(),'.
        /* Section III */'
            products = "'.mysql_real_escape_string ($products).'",
            practices = "'.mysql_real_escape_string ($practices).'",
            pest_management = "'.mysql_real_escape_string ($pest_management).'",
            productivity_management = "'.mysql_real_escape_string ($productivity_management).'",
            feeding_practices = "'.mysql_real_escape_string ($feeding_practices).'",
            soil_management = "'.mysql_real_escape_string ($soil_management).'",
            water_management = "'.mysql_real_escape_string ($water_management).'",
            land_practices = "'.mysql_real_escape_string ($land_practices).'",
            additional_information = "'.mysql_real_escape_string ($additional_information).'",'.
        /* Section IV */'
            licenses_insurance = "'.mysql_real_escape_string ($licenses_insurance).'",
            organic_products = "'.mysql_real_escape_string ($organic_products).'",
            certifying_agency = "'.mysql_real_escape_string ($certifying_agency).'",
            agency_phone = "'.mysql_real_escape_string ($agency_phone).'",
            agency_fax = "'.mysql_real_escape_string ($agency_fax).'",
            organic_cert = "'.mysql_real_escape_string ($organic_cert).'"
          WHERE
            producer_id = "'.mysql_real_escape_string ($producer_id).'"';
        // Run the producers_registration table insert query
        $result = @mysql_query($query2,$connection) or die(debug_print ("ERROR: 760534 ", array ($query2,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
      WHERE member_id = "'.mysql_real_escape_string ($member_id).'"';
    $sql =  @mysql_query($query, $connection) or die(debug_print ("ERROR: 785403 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysql_fetch_object($sql))
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

    // Disable all form elements for emailing
    $html_version = preg_replace ('/<(input|select|textarea)/', '<\1 disabled', $welcome_message.$display_form_html);

    $email_headers  = "From: ".STANDARDS_EMAIL."\r\n";
    $email_headers .= "Reply-To: ".STANDARDS_EMAIL."\r\n";
    $email_headers .= "Errors-To: web@".DOMAIN_NAME."\r\n";
    $email_headers .= "MIME-Version: 1.0\r\n";
    $email_headers .= "Content-type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $email_headers .= "Message-ID: <".md5(uniqid(time()))."@".DOMAIN_NAME.">\r\n";
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
          <p>Your producer application will be reviewed by an administrator and you will be notified when
          it becomes active.  Until then, you will not have producer access or be able to enter products into the system.</p>';
      }
    else
      {
        $display_form_message .= '
          <p>Your producer application will be reviewed by an administrator.  You have access
          to enter products into the system through your member account.</p>';
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
<style type="text/css">
  #section1a tr td {
    padding-left:1em;
    }
  #producer_link_field {
    display:block;
    margin-top:0.5em;
    float:left;
    }
  #producer_link_message {
    height:1.5em;
    width:15em;
    color:#800;
    float:right;
    text-align:right;
    }
</style>';


$page_specific_javascript = '
<script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
<script type="text/javascript">

function check_producer_link () {
  var allowed_chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-.";
  var producer_link;
  var producer_link_temp;
  producer_link = document.getElementById("producer_link_field").value;
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
      document.getElementById("producer_link_field").value = producer_link_temp;
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
      $.post
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
  }
</script>';

$page_title_html = '<span class="title">Producer Info.</span>';
$page_subtitle_html = '<span class="subtitle">'.(! $producer_id ? 'New Producer Application Form' : 'Edit All Producer Information').'</span>';
$page_title = 'Producer Info: '.(! $producer_id ? 'New Producer Application Form' : 'Edit All Producer Information');
$page_tab = 'producer_panel';

include ("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_form_top.'
  '.$display_form_message.'
  '.$display_form_html.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");