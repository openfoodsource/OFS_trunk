<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone
require_once ('securimage.php');

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
    $human_check = $_POST['human_check'];
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

    if ( strlen ($password1) < 6 )
      {
        array_push ($error_array, 'Passwords must be at least six characters long');
        $clear_password = true;
      }

    if ( $password1 != $password2 )
      {
        array_push ($error_array, 'Passwords do not match');
        $clear_password = true;
      }

    if ($clear_password === true)
      {
        $password1 = '';
        $password2 = '';
      }

    $captcha = new Securimage();
    if ($captcha->check($human_check) != true) array_push ($error_array, 'Enter the human validation text');

    $query = '
      SELECT
        *
      FROM
        '.TABLE_MEMBER.'
      WHERE username = "'.mysql_real_escape_string ($username).'"';
    $sql =  @mysql_query($query, $connection) or die("You found a bug. <b>Error:</b> Check for existing member query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

    if ($row = mysql_fetch_object($sql)) array_push ($error_array, 'The username "'.$username.'" is already in use');

    if (!$username) array_push ($error_array, 'Choose a unique username');

    if ( !$membership_type_id ) array_push ($error_array, 'Choose a membership option');

    if ( !$affirmation ) array_push ($error_array, 'You must accept the affirmation before becoming a member');

    if ( !$how_heard_id ) array_push ($error_array, 'Let us know how you heard about '.SITE_NAME);

    if ( !$heard_other ) array_push ($error_array, 'Tell us more about how you heard about us');
  }

// Assemble any errors encountered so far
if (count ($error_array) > 0) $error_message = '
  <div class="error_message">
    <p class="message">The information was not accepted. Please correct the following problems and resubmit.</p>
    <ul class="error_list">
      <li>'.implode ("</li>\n<li>", $error_array).'</li>
    </ul>
  </div>';


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
        member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
    $sql =  @mysql_query($query, $connection) or die(debug_print ("ERROR: 863430 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_object($sql))
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
$sql =  @mysql_query($query, $connection) or die(debug_print ("ERROR: 683642 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysql_fetch_object($sql))
  {
    $selected = '';
    if ($membership_type_id == $row->membership_type_id)
      {
        $selected = ' selected';
        $membership_type_text = $row->membership_description;
      }
    $membership_types_display .= '
      <dt>'.$row->membership_class.'</dt>
      <dd>'.$row->membership_description.'</dd>';
    $membership_types_options .= '
      <option value="'.$row->membership_type_id.'"'.$selected.'>'.$row->membership_class.'</option>';
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
$sql =  @mysql_query($query, $connection) or die(debug_print ("ERROR: 650242 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysql_fetch_object($sql))
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

$display_form_title .= '
  <h1>'.date('Y').' '.SITE_NAME.' Registration</h1><br>
  <div style="margin:auto;width:90%;padding:1em;">';

$welcome_message = '
  <p><em>Thank you for your interest in becoming a member of '.SITE_NAME.'.
  '.SITE_NAME.' customers and producers are interested in local foods and products
  produced with sustainable practices that demonstrate good stewardship of the environment.';

$display_form_top .= $welcome_message.'
  To become a member, please read the <a href="'.TERMS_OF_SERVICE.'" target="_blank">
  Terms of Service</a>, and then complete the following information and click submit.</em></p>';

if (! $_SESSION['member_id'])
  {
    $display_form_top .= '
      <p>If you are already a member, please <a href="index.php?action=login">sign in here</a>.  Otherwise fill out the form below to become a member. Required fields are shown with an asterisk (*)</p>';
  }
else
  {
    $display_form_top .= '
      <p>As an existing member, you can use the form below to update your membership information.</p>';
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
  <form action="'.$_SERVER['SCRIPT_NAME'].'" name="delivery" method="post">

    <table cellspacing="15" cellpadding="2" width="100%" border="0" align="center">
      <tbody>

      <tr>
        <th class="memberform">Section 1: General Information</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>*&nbsp;First&nbsp;Name:</strong></td>
              <td><input maxlength="20" id="first_name" size="25" name="first_name" value="'.$first_name.'" onKeyUp=set_preferred_name() tabindex="1"></td>
              <td class="form_key"><strong>*&nbsp;Last&nbsp;Name:</strong></td>
              <td><input maxlength="20" id="last_name" size="25" name="last_name" value="'.$last_name.'" onKeyUp=set_preferred_name() tabindex="2"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>First&nbsp;Name&nbsp;2:</strong></td>
              <td><input maxlength="20" id="first_name_2" size="25" name="first_name_2" value="'.$first_name_2.'" onKeyUp=set_preferred_name() tabindex="3"></td>
              <td class="form_key"><strong>Last&nbsp;Name&nbsp;2:</strong></td>
              <td><input maxlength="20" id="last_name_2" size="25" name="last_name_2" value="'.$last_name_2.'" onKeyUp=set_preferred_name() tabindex="4"></td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key" width="10"><strong>*&nbsp;Invoice&nbsp;Name:</strong></td>
              <td width="10"><input maxlength="50" id="preferred_name" size="45" name="preferred_name" value="'.$preferred_name.'" tabindex="5"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td colspan="3" class="footnote">NOTE: Invoice Name can be edited.  It will be used to route your order and should be easily tracable to you.  It could be the common form of your name like &quot;Alf Jones&quot; instead of &quot;Alfred Jones&quot;.  If preferred, your Business Name may be used for the Invoice Name.</td>
            </tr>
            <tr>
              <td class="form_key" width="10"><strong>Business&nbsp;Name:</strong></td>
              <td width="10"><input maxlength="50" id="business_name" size="45" name="business_name" value="'.$business_name.'" onKeyUp=set_preferred_business() tabindex="6"></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td colspan="2" width="20"><input type="checkbox" name="producer" value="yes"'.$producer_check.'> I am interested in becoming a producer member for '.SITE_NAME.'.</td>
              <td>&nbsp;</td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <th class="memberform">Section 2: Contact Information</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>*&nbsp;Address:</strong></td>
              <td colspan="6"><input maxlength="75" size="50" name="address_line1" value="'.$address_line1.'" tabindex="7"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>Address&nbsp;2:</strong></td>
              <td colspan="6"><input maxlength="75" size="50" name="address_line2" value="'.$address_line2.'" tabindex="8"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>*&nbsp;City/State/Zip:</strong></td>
              <td><input maxlength="50" size="25" name="city" value="'.$city.'" tabindex="9"></td>
              <td><input maxlength="2" size="2" name="state" value="'.$state.'" tabindex="10"></td>
              <td><input maxlength="10" size="10" name="zip" value="'.$zip.'" tabindex="11"></td>
              <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
              <td class="form_key"><strong>*&nbsp;County:</strong></td>
              <td colspan="3"><input maxlength="75" size="25" name="county" value="'.$county.'" tabindex="12"></td>
              <td class="form_key" colspan="2"><strong>Do Not Send Postal Mail:</strong></td>
              <td><input type="checkbox" name="no_postal_mail" value="1"'.$no_postal_mail_check.' tabindex="13"></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>Work&nbsp;Address:</strong></td>
              <td colspan="3"><input maxlength="75" size="50" name="work_address_line1" value="'.$work_address_line1.'" tabindex="14"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>Work&nbsp;Address&nbsp;2:</strong></td>
              <td colspan="3"><input maxlength="75" size="50" name="work_address_line2" value="'.$work_address_line2.'" tabindex="15"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>City/State/Zip:</strong></td>
              <td><input maxlength="50" size="25" name="work_city" value="'.$work_city.'" tabindex="16"></td>
              <td><input maxlength="2" size="2" name="work_state" value="'.$work_state.'" tabindex="17"></td>
              <td><input maxlength="10" size="10" name="work_zip" value="'.$work_zip.'" tabindex="18"></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>*&nbsp;Home&nbsp;Phone:</strong></td>
              <td><input maxlength="20" size="25" name="home_phone" value="'.$home_phone.'" tabindex="19"></td>
              <td class="form_key"><strong>Work&nbsp;Phone:</strong></td>
              <td><input maxlength="20" size="25" name="work_phone" value="'.$work_phone.'" tabindex="20"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>Mobile&nbsp;Phone:</strong></td>
              <td><input maxlength="20" size="25" name="mobile_phone" value="'.$mobile_phone.'" tabindex="21"></td>
              <td class="form_key"><strong>FAX:</strong></td>
              <td><input maxlength="20" size="25" name="fax" value="'.$fax.'" tabindex="22"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>Toll&nbsp;Free:</strong></td>
              <td><input maxlength="20" size="25" name="toll_free" value="'.$toll_free.'" tabindex="23"></td>
              <td class="form_key"><strong>&nbsp;</strong></td>
              <td>&nbsp;</td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>*&nbsp;Email&nbsp;Address:</strong></td>
              <td><input maxlength="80" size="45" name="email_address" value="'.$email_address.'" tabindex="24"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>Email&nbsp;Address&nbsp;2:</strong></td>
              <td><input maxlength="80" size="45" name="email_address_2" value="'.$email_address_2.'" tabindex="25"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>Home&nbsp;Page:</strong></td>
              <td>http://<input maxlength="80" size="40" name="home_page" value="'.$home_page.'" tabindex="26"></td>
            </tr>
          </table>
        </td>
      </tr>';

if (! $_SESSION['member_id']) // Do not show the following part to existing members....
  {
$display_form_html .= '
      <tr>
        <th class="memberform">Section 3: Access Credentials</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key"><strong>*&nbsp;Username:</strong></td>
              <td><input maxlength="20" size="25" name="username" value="'.$username.'" tabindex="27"></td>
              <td class="form_key" rowspan="3" valign="top"><strong>*&nbsp;Enter the word</strong><br><input maxlength="10" size="10" name="human_check" value="" tabindex="30"></td>
              <td rowspan="3" align="center"><img src="securimage_show.php?sid='.time().'" alt="Human validation text"><br>Human validation text</td>
            </tr>
            <tr>
              <td class="form_key"><strong>*&nbsp;Password:</strong></td>
              <td><input type="password" maxlength="20" size="25" name="password1" value="'.$password1.'" tabindex="28"></td>
            </tr>
            <tr>
              <td class="form_key"><strong>*&nbsp;Confirm:</strong></td>
              <td><input type="password" maxlength="20" size="25" name="password2" value="'.$password2.'" tabindex="29"></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <th class="memberform">Section 4: Membership Type</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key" rowspan="2" valign="top"><strong>*&nbsp;Membership&nbsp;Type:</strong></td>
              <td><select name="membership_type_id" tabindex="31">'.$membership_types_options.'</select></td>
            </tr>
            <tr>
              <td>
                <dl>
                  '.$membership_types_display.'
                </dl>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <th class="memberform">Section 5: Additional Information</th>
      </tr>

      <tr>
        <td>
          <table>
            <tr>
              <td class="form_key" valign="top"><strong>*&nbsp;How&nbsp;you&nbsp;heard&nbsp;about '.SITE_NAME.':</strong></td>
              <td><select name="how_heard_id" tabindex="32">'.$how_heard_options.'</select></td>
            </tr>
            <tr>
              <td class="form_key" valign="top"><strong>*&nbsp;Please&nbsp;give&nbsp;more&nbsp;detail:</strong></td>
              <td>
                <input maxlength="50" size="25" name="heard_other" value="'.$heard_other.'" tabindex="33"><br>
                What website, from whom, which publication/date, etc.?
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td><input type="checkbox" name="volunteer" value="yes"'.$volunteer_check.' tabindex="34"> YES! I&lsquo;m interested in volunteering to help '.SITE_NAME.'.</td>
      </tr>

      <tr>
        <td><input type="checkbox" name="affirmation" value="yes"'.$affirmation_check.' tabindex="35">
          *&nbsp;I acknowledge that I have read and understand the '.SITE_NAME.' <a href="'.TERMS_OF_SERVICE.'" target="_blank">
          Terms of Service</a>.
        </td>
      </tr>';
  }

$display_form_html .= '


      <tr>
        <td align="center">
          <input type="submit" name="action" value="'.$action.'" tabindex="36">
        </td>
      </tr>
      </tbody>
    </table>
  </form></div>';


////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//         ADD OR CHANGE INFORMATION IN THE DATABASE FOR THIS MEMBER          //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

// If everything validates, then we can post to the database...
if (count ($error_array) == 0 && $_POST['action'] == 'Submit') // For new members
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
        $sql = @mysql_query($query, $connection) or die(debug_print ("ERROR: 543954 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if ($row = mysql_fetch_object($sql))
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
        WHERE membership_type_id = "'.mysql_real_escape_string ($membership_type_id).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 804923 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysql_fetch_object($result))
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
        pending = "'.mysql_real_escape_string ($pending).'",
        auth_type = "'.mysql_real_escape_string ($auth_type).'",
        membership_type_id = "'.mysql_real_escape_string ($membership_type_id).'",
        customer_fee_percent = "'.mysql_real_escape_string ($customer_fee_percent).'",
        membership_date = NOW(),
        last_renewal_date = NOW(),
        username = "'.mysql_real_escape_string ($username).'",
        password = md5("'.mysql_real_escape_string ($password1).'"),
        last_name = "'.mysql_real_escape_string ($last_name).'",
        first_name = "'.mysql_real_escape_string ($first_name).'",
        last_name_2 = "'.mysql_real_escape_string ($last_name_2).'",
        first_name_2 = "'.mysql_real_escape_string ($first_name_2).'",
        preferred_name = "'.mysql_real_escape_string ($preferred_name).'",
        business_name = "'.mysql_real_escape_string ($business_name).'",
        address_line1 = "'.mysql_real_escape_string ($address_line1).'",
        address_line2 = "'.mysql_real_escape_string ($address_line2).'",
        city = "'.mysql_real_escape_string ($city).'",
        state = "'.mysql_real_escape_string ($state).'",
        zip = "'.mysql_real_escape_string ($zip).'",
        county = "'.mysql_real_escape_string ($county).'",
        work_address_line1 = "'.mysql_real_escape_string ($work_address_line1).'",
        work_address_line2 = "'.mysql_real_escape_string ($work_address_line2).'",
        work_city = "'.mysql_real_escape_string ($work_city).'",
        work_state = "'.mysql_real_escape_string ($work_state).'",
        work_zip = "'.mysql_real_escape_string ($work_zip).'",
        home_phone = "'.mysql_real_escape_string ($home_phone).'",
        work_phone = "'.mysql_real_escape_string ($work_phone).'",
        mobile_phone = "'.mysql_real_escape_string ($mobile_phone).'",
        fax = "'.mysql_real_escape_string ($fax).'",
        toll_free = "'.mysql_real_escape_string ($toll_free).'",
        email_address = "'.mysql_real_escape_string ($email_address).'",
        email_address_2 = "'.mysql_real_escape_string ($email_address_2).'",
        home_page = "'.mysql_real_escape_string ($home_page).'",
        how_heard_id = "'.mysql_real_escape_string ($how_heard_id).'",
        no_postal_mail = "'.mysql_real_escape_string ($no_postal_mail).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 793032 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $member_id = mysql_insert_id();
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
          <p>Thanks for becoming a member! Your membership number will be #'.$member_id.'.  Your membership
          application will be reviewed by an administrator and you will be notified when it becomes
          active.  Until then, you will not be able to log in.</p>';
      }
    else // Pending = 0
      {
        $membership_disposition = '
          <p class="welcome_message">Thanks for becoming a member! Your membership number is #'.$member_id.'.  Your membership has
          been automatically activated and you may <a href="'.PATH.'index.php?action=login">
          sign in</a> immediately.</p>';
      }

    // Add taxes for taxable membership fees (based upon the STATE tax rate)
    $membership_taxes = 0;
    if (MEMBERSHIP_IS_TAXED)
      {
        $membership_taxes = round ($initial_cost * STATE_TAX, 2);
      }
    if ($initial_cost > 0) $membership_disposition .= '
      <p>Please send your membership payment of $'.number_format ($initial_cost + $membership_taxes, 2).' to:<br><br>
      '.SITE_MAILING_ADDR.'</p>';
    if ( PAYPAL_EMAIL && $initial_cost > 0 ) $membership_disposition .= '
      <p>Or make a payment online through PayPal (opens in a new window)
      <form target="paypal" method="post" action="https://www.paypal.com/cgi-bin/webscr">
      <input type="hidden" value="_xclick" name="cmd">
      <input type="hidden" value="'.PAYPAL_EMAIL.'" name="business">
      <input type="hidden" name="amount" value="'.number_format ($initial_cost + $membership_taxes, 2).'">
      <input type="hidden" value="Membership payment for #'.$member_id.' '.$preferred_name.' : '.$business_name.'" name="item_name">
      <input type="image" border="0" alt="Make payment with PayPal" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_paynowCC_LG.gif">
      </form></p>';

    $display_form_message .= $membership_disposition;

    // Now send email notification(s)
    $email_to = preg_replace ('/SELF/', $email_address, MEMBER_FORM_EMAIL);
    $email_subject = 'Welcome to '.SITE_NAME.' - '.$preferred_name.' (#'.$member_id.')';
    $boundary = uniqid();
    // Set up the email preamble...
    $email_preamble = '
      <p>Following is a copy of the membership information you submitted to '.SITE_NAME.'.</p>';
    $email_preamble .= $membership_disposition.$welcome_message;

    // Disable all form elements for emailing
    $html_version = $email_preamble.preg_replace ('/<(input|select|textarea)/', '<\1 disabled', $display_form_html);

    $email_headers  = "From: ".MEMBERSHIP_EMAIL."\n";
    $email_headers .= "Reply-To: ".MEMBERSHIP_EMAIL."\n";
    $email_headers .= "Errors-To: web@".DOMAIN_NAME."\n";
    $email_headers .= "MIME-Version: 1.0\n";
    $email_headers .= "Content-type: multipart/alternative; boundary=\"$boundary\"\n";
    $email_headers .= "Message-ID: <".md5(uniqid(time()))."@".DOMAIN_NAME.">\n";
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

elseif (count ($error_array) == 0 && $_POST['action'] == 'Update') // For existing members
  {
    // Everything validates correctly so do the INSERT and send the EMAIL
    $query = '
      UPDATE
        '.TABLE_MEMBER.'
      SET
        last_name = "'.mysql_real_escape_string ($last_name).'",
        first_name = "'.mysql_real_escape_string ($first_name).'",
        last_name_2 = "'.mysql_real_escape_string ($last_name_2).'",
        first_name_2 = "'.mysql_real_escape_string ($first_name_2).'",
        preferred_name = "'.mysql_real_escape_string ($preferred_name).'",
        business_name = "'.mysql_real_escape_string ($business_name).'",
        address_line1 = "'.mysql_real_escape_string ($address_line1).'",
        address_line2 = "'.mysql_real_escape_string ($address_line2).'",
        city = "'.mysql_real_escape_string ($city).'",
        state = "'.mysql_real_escape_string ($state).'",
        zip = "'.mysql_real_escape_string ($zip).'",
        county = "'.mysql_real_escape_string ($county).'",
        work_address_line1 = "'.mysql_real_escape_string ($work_address_line1).'",
        work_address_line2 = "'.mysql_real_escape_string ($work_address_line2).'",
        work_city = "'.mysql_real_escape_string ($work_city).'",
        work_state = "'.mysql_real_escape_string ($work_state).'",
        work_zip = "'.mysql_real_escape_string ($work_zip).'",
        home_phone = "'.mysql_real_escape_string ($home_phone).'",
        work_phone = "'.mysql_real_escape_string ($work_phone).'",
        mobile_phone = "'.mysql_real_escape_string ($mobile_phone).'",
        fax = "'.mysql_real_escape_string ($fax).'",
        toll_free = "'.mysql_real_escape_string ($toll_free).'",
        email_address = "'.mysql_real_escape_string ($email_address).'",
        email_address_2 = "'.mysql_real_escape_string ($email_address_2).'",
        home_page = "'.mysql_real_escape_string ($home_page).'",
        no_postal_mail = "'.mysql_real_escape_string ($no_postal_mail).'"
      WHERE
        member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 673022 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $display_form_message = '
      <p>Your membership information has been successfully updated.<br><br></p>';
  }

if ($producer == 'yes' && count ($error_array) == 0)
  {
    // We already have the member_id from the mysql_insert_id
    $_SESSION['new_member_id'] = $member_id;
    $_SESSION['new_business_name'] = $business_name;
    $_SESSION['new_website'] = $home_page;
    $_SESSION['new_membership_type_id'] = $membership_type_id;
    $display_form_message .= '
      <p>You also expressed interest in becoming a producer member.</p>
      <p>You can access the <a href="producer_form.php">producer
      registration form</a> immediately or you can return later to complete the form.
      It is a lengthy form  and you may wish to print it prior to filling it out online.<br><br></p>';
  }
if ($display_form_message) $display_form_message .= '</div>';

$page_title_html = '<span class="title">Member Resources</span>';
$page_subtitle_html = '<span class="subtitle">'.($_SESSION['show_name'] ? 'Update Membership Info.' : 'New Member Form').'</span>';
$page_title = 'Member Resources: '.($_SESSION['show_name'] ? 'Update Membership Info.' : 'New Member Form');
$page_tab = 'member_panel';

$page_specific_javascript = '
<script type="text/javascript">
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
    // CASE WHERE BOTH LAST NAMES ARE ENTERED
    document.getElementById("preferred_name").value =
      document.getElementById("business_name").value;
    }
</script>';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_form_title.'
  '.$display_form_message.'
  '.(!$email_sent ? $display_form_top.$display_form_html : '').'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
