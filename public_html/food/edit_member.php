<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,member_admin');

// echo "<pre>".print_r($_POST,true)."</pre>";

// CREATE A NEW MEMBER?
if (isset ($_REQUEST['member_id']))
  {
    // Set the submit button to "Update"
    $submit_button_text = 'Update';
  }
else
  {
    // Set the submit button to "Add"
    $submit_button_text = 'Add New';
  }

// If we received 'Add' or 'Update' request, then handle the validation and database changes
if (isset ($_POST['action']))
  {
    // Validate data
    $error_array = array ();
    $warn_array = array ();
    // Ensure there is no username conflict
    $query = '
      SELECT member_id
      FROM '.TABLE_MEMBER.'
      WHERE
        username = "'.mysql_real_escape_string ($_POST['username']).'"
        AND member_id != "'.mysql_real_escape_string ($_POST['member_id']).'"';
    $result = mysql_query($query) or die (debug_print ("ERROR: 793402 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $number_of_conflicts = @mysql_num_rows($result);
    if ($number_of_conflicts > 0)
      {
        array_push ($error_array, 'The Username already exists. Please select a different value.');
        $error['username'] = ' error';
      }
    // Ensure there is both a first and last name --OR-- a preferred name
    if ((strlen ($_POST['first_name']) == 0 ||
         strlen ($_POST['last_name']) == 0) &&
        strlen ($_POST['preferred_name']) == 0)
      {
        array_push ($error_array, 'Please provide a first and last name or a preferred name.');
        $error['first_name'] = ' error';
        $error['last_name'] = ' error';
        $error['preferred_name'] = ' error';
      }
    // Ensure there is at least one street address
    if ((strlen ($_POST['address_line1']) == 0 ||
         strlen ($_POST['city']) == 0 ||
         strlen ($_POST['state']) == 0 ||
         strlen ($_POST['zip']) == 0) &&
        (strlen ($_POST['work_address_line1']) == 0 ||
         strlen ($_POST['work_city']) == 0 ||
         strlen ($_POST['work_state']) == 0 ||
         strlen ($_POST['work_zip']) == 0))
      {
        array_push ($error_array, 'Please provide at least one valid address location (home or work).');
        $error['address_line1'] = ' error';
        $error['city'] = ' error';
        $error['state'] = ' error';
        $error['zip'] = ' error';
        $error['work_address_line1'] = ' error';
        $error['work_city'] = ' error';
        $error['work_state'] = ' error';
        $error['work_zip'] = ' error';
      }
    // Ensure there is a county listed
    if (strlen ($_POST['county']) == 0)
      {
        array_push ($error_array, 'Please provide a county (home or work location where deliveries would take place).');
        $error['county'] = ' error';
      }
    // Ensure there is at least one e-mail address
    if (! filter_var ($_POST['email_address'], FILTER_VALIDATE_EMAIL) &&
        ! filter_var ($_POST['email_address_2'], FILTER_VALIDATE_EMAIL))
      {
        array_push ($error_array, 'Please provide at least one valid e-mail address.');
        $error['email_address'] = ' error';
        $error['email_address_2'] = ' error';
      }
    // Check the validity of each email address
    if (strlen ($_POST['email_address']) > 0 &&
        ! filter_var ($_POST['email_address'], FILTER_VALIDATE_EMAIL))
      {
        array_push ($error_array, 'Primary e-mail address is invalid.');
        $error['email_address'] = ' error';
      }
    if (strlen ($_POST['email_address_2']) > 0 &&
        ! filter_var ($_POST['email_address_2'], FILTER_VALIDATE_EMAIL))
      {
        array_push ($error_array, 'Secondary e-mail address is invalid.');
        $error['email_address_2'] = ' error';
      }
    // Validate the home_page, but only if the field has been entered
    // Ensure there is no "http://" and that the url is good by validating it *with* "http://" prepended
    if (strlen ($_POST['home_page']) != 0 &&
        (preg_match ('|^http(s)?://.*$|i', $_POST['home_page']) ||
         ! filter_var ('http://'.$_POST['home_page'], FILTER_VALIDATE_URL)))
      {
        array_push ($error_array, 'The home page URL is not formatted properly. Be sure it does not include the http:// portion.');
        $error['home_page'] = ' error';
      }
    // If we received password change, then check they match and are at least six characters
    if (strlen ($_POST['password1']) != 0 ||
         strlen ($_POST['password2']) != 0)
      {
        if ($_POST['password1'] != $_POST['password2'])
          {
            array_push ($error_array, 'Passwords do not match.');
            $error['password1'] = ' error';
            $error['password2'] = ' error';
          }
        $password_strength = test_password ($_POST['password1']);
        if ($password_strength < MIN_PASSWORD_STRENGTH)
          {
            array_push ($error_array, 'The password (strength '.$password_strength.') must be at least strength '.MIN_PASSWORD_STRENGTH.'.');
            $error['password1'] = ' error';
            $error['password2'] = ' error';
          }
        else
          {
            array_push ($warn_array, 'The password strength is '.$password_strength.'. Minimum required is '.MIN_PASSWORD_STRENGTH.'.');
          }
      }
    // Be sure the membership_date is valid
    $membership_date = date_create_from_format('Y-m-d', $_POST['membership_date']);
    $membership_date_errors = date_get_last_errors ();
    if ($membership_date_errors['warning_count'] != 0 ||
        $membership_date_errors['error_count'] != 0)
      {
        array_push ($error_array, 'The membership date is invalid or improperly formatted as &quot;YYYY-MM-DD&quot;.');
        $error['membership_date'] = ' error';
      }
    else
      {
        $_POST['membership_date'] = date_format ($membership_date, 'Y-m-d');
      }
//     // Be sure the last_renewal_date is valid
    $last_renewal_date = date_create_from_format('Y-m-d', $_POST['last_renewal_date']);
    $last_renewal_date_errors = date_get_last_errors ();
    if ($last_renewal_date_errors['warning_count'] != 0 ||
        $last_renewal_date_errors['error_count'] != 0)
      {
        array_push ($error_array, 'The last_renewal date is invalid or improperly formatted as &quot;YYYY-MM-DD&quot;.');
        $error['last_renewal_date'] = ' error';
      }
    else
      {
        $_POST['last_renewal_date'] = date_format ($last_renewal_date, 'Y-m-d');
      }
    // Be sure the customer fee is a number
    if (! preg_match ('|^(-)?[0-9]*(\.)?[0-9]*$|', $_POST['customer_fee_percent']))
      {
        array_push ($error_array, 'The customer fee must be a postive or negative decimal number.');
        $error['customer_fee_percent'] = ' error';
      }
  }
// If there are no validation errors, then post the new data and get the changed values from the database
if (count ($error_array) == 0 &&
    ($_REQUEST['action'] == 'Update' ||
     $_REQUEST['action'] == 'Add New'))
  {
    // Close the modal dialog and reload the parent when completed.
    $modal_action = 'reload_parent';
    // Get the auth_types from checkboxes
    // Prepare the database insert or update
    $query_values = '
          SET
            first_name = "'.mysql_real_escape_string($_POST['first_name']).'",
            last_name = "'.mysql_real_escape_string($_POST['last_name']).'",
            first_name_2 = "'.mysql_real_escape_string($_POST['first_name_2']).'",
            last_name_2 = "'.mysql_real_escape_string($_POST['last_name_2']).'",
            preferred_name = "'.mysql_real_escape_string($_POST['preferred_name']).'",
            business_name = "'.mysql_real_escape_string($_POST['business_name']).'",
            address_line1 = "'.mysql_real_escape_string($_POST['address_line1']).'",
            address_line2 = "'.mysql_real_escape_string($_POST['address_line2']).'",
            city = "'.mysql_real_escape_string($_POST['city']).'",
            state = "'.mysql_real_escape_string($_POST['state']).'",
            zip = "'.mysql_real_escape_string($_POST['zip']).'",
            county = "'.mysql_real_escape_string($_POST['county']).'",
            work_address_line1 = "'.mysql_real_escape_string($_POST['work_address_line1']).'",
            work_address_line1 = "'.mysql_real_escape_string($_POST['work_address_line1']).'",
            work_city = "'.mysql_real_escape_string($_POST['work_city']).'",
            work_state = "'.mysql_real_escape_string($_POST['work_state']).'",
            work_zip = "'.mysql_real_escape_string($_POST['work_zip']).'",
            email_address = "'.mysql_real_escape_string($_POST['email_address']).'",
            email_address_2 = "'.mysql_real_escape_string($_POST['email_address_2']).'",
            home_phone = "'.mysql_real_escape_string($_POST['home_phone']).'",
            work_phone = "'.mysql_real_escape_string($_POST['work_phone']).'",
            mobile_phone = "'.mysql_real_escape_string($_POST['mobile_phone']).'",
            fax = "'.mysql_real_escape_string($_POST['fax']).'",
            toll_free = "'.mysql_real_escape_string($_POST['toll_free']).'",
            home_page = "'.mysql_real_escape_string($_POST['home_page']).'",
            username = "'.mysql_real_escape_string($_POST['username']).'",'.
            /* Only update the password if there is a new one */
            (strlen ($_POST['password1']) > 0 ? '
            password = MD5("'.mysql_real_escape_string($_POST['password1']).'"),' : '').
            /* The auth_type is combined from a multi-valued array */ '
            auth_type = "'.implode (',', $_POST['auth_type']).'",
            membership_type_id = "'.mysql_real_escape_string($_POST['membership_type_id']).'",
            membership_date = "'.mysql_real_escape_string($_POST['membership_date']).'",
            last_renewal_date = "'.mysql_real_escape_string($_POST['last_renewal_date']).'",
            customer_fee_percent = "'.mysql_real_escape_string($_POST['customer_fee_percent']).'",
            pending = "'.($_POST['pending'] == '1' ? '1' : '0').'",
            no_postal_mail = "'.($_POST['no_postal_mail'] == '1' ? '1' : '0').'",
            mem_delch_discount = "'.($_POST['mem_delch_discount'] == '1' ? '1' : '0').'",
            mem_taxexempt = "'.($_POST['mem_taxexempt'] == '1' ? '1' : '0').'",
            membership_discontinued = "'.($_POST['membership_discontinued'] == '1' ? '1' : '0').'",
            how_heard_id = "'.mysql_real_escape_string($_POST['how_heard_id']).'",
            notes = "'.mysql_real_escape_string($_POST['notes']).'"';
    // Put together the proper query
    if ($_REQUEST['action'] == 'Update')
      {
        $query = '
          UPDATE
            '.TABLE_MEMBER.
          $query_values.'
          WHERE
            member_id = "'.mysql_real_escape_string($_POST['member_id']).'"';
      }
    elseif ($_REQUEST['action'] == 'Add New')
      {
        $query = '
          INSERT INTO
            '.TABLE_MEMBER.
          $query_values;
      }
    $result = mysql_query($query) or die (debug_print ("ERROR: 578932 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    // Get the new or current member_id
    if ($_REQUEST['action'] == 'Add New')
      $_GET['member_id'] = mysql_insert_id ();
    else
      $_GET['member_id'] = $_POST['member_id'];
    // And force the submit button text to "Update"
    $submit_button_text = 'Update';
  }

// Query for member information if a member_id was requested
if (isset ($_GET['member_id']))
  {
    $query = '
      SELECT *
      FROM '.TABLE_MEMBER.'
      WHERE
        member_id="'.mysql_real_escape_string($_REQUEST['member_id']).'"';
    $result = mysql_query($query) or die (debug_print ("ERROR: 567230 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $rows = @mysql_num_rows($result);
    if ($rows == 0)
      {
        // We found no member information, so open the form for adding a new member
        $submit_button_text = 'Add New';
        // Use the error notification to alert that we are not editing the member
        array_push ($warn_array, 'Member #'.$_GET['member_id'].' was not found but the form is open for adding a new member.');
      }
    else
      {
        $member_info = mysql_fetch_array($result);
      }
  }
// If there was a validation error, then display it and use posted data to fill the form
// This exists HERE because we need $member_info next
if (count ($error_array) > 0 ||
    count ($warn_array) > 0)
  {
    $error_message = '
      <div class="error_message">
        <ul class="error_list">
          '.(count ($error_array) ? '<strong>Update cancelled due to errors:</strong><br /><li class="error">'.implode ('</li>'."\n".'<li class="error">', $error_array).'</li>' : '').'
          '.(count ($warn_array) ? '<br /><strong>Warnings:</strong><br /><li class="warn">'.implode ('</li>'."\n".'<li class="warn">', $warn_array).'</li>' : '').'
        </ul>
      </div>';
    // Use the posted data instead of database values
    unset ($_GET['member_id']);
    $member_info = $_POST;
    // The auth_type field needs special attention
    $member_info['auth_type'] = implode (',', $_POST['auth_type']);
  }
// Query for member information
$query = '
  SELECT
    how_heard_id,
    how_heard_name
  FROM '.TABLE_HOW_HEARD;
$result = mysql_query($query) or die (debug_print ("ERROR: 543023 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ($how_heard_row = mysql_fetch_array($result))
  {
    $how_heard_input .= '
    <div class="input_block how_heard_'.$how_heard_row['membership_type_id'].'">
      <label for="how_heard_'.$how_heard_row['membership_type_id'].'">'.$how_heard_row['how_heard_name'].'</label>
      <input id="how_heard_'.$how_heard_row['membership_type_id'].'" name="how_heard_id" type="radio" value="'.$how_heard_row['how_heard_id'].'"'.($member_info['how_heard_id'] == $how_heard_row['how_heard_id'] ? ' checked' : '').'>
    </div>';
  }
// Query for all legitimate auth_types in the members table
$query = '
  SHOW COLUMNS
  FROM '.TABLE_MEMBER.'
  LIKE "auth_type"';
$result = mysql_query($query) or die (debug_print ("ERROR: 672930 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$auth_type_options = mysql_fetch_array($result);
$auth_type_raw = substr ($auth_type_options['Type'],3);
$auth_type_array = explode (',', preg_replace ('/[^a-zA-Z0-9\-_,]/', '', $auth_type_raw)); // \'
foreach ($auth_type_array as $auth_type_option)
  {
    $auth_type_input .= '
    <div class="input_block auth_type_'.$auth_type_option.'">
      <label for="auth_type_'.$auth_type_option.'">'.ucwords (strtr ($auth_type_option, '_', ' ')).'</label>
      <input id="auth_type_'.$auth_type_option.'" name="auth_type[]" type="checkbox" value="'.$auth_type_option.'"'.(strpos (','.$member_info['auth_type'].',', ','.$auth_type_option.',') !== false ? ' checked' : '').'>
    </div>';
  }
// Query for all legitimate membership_types in the membership_types table
$query = '
  SELECT
    membership_type_id,
    membership_class
  FROM '.TABLE_MEMBERSHIP_TYPES;
$result = mysql_query($query) or die (debug_print ("ERROR: 572893 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ($membership_types_row = mysql_fetch_array($result))
  {
    $membership_type_input .= '
    <div class="input_block membership_type_'.$membership_types_row['membership_type_id'].'">
      <label for="membership_type_'.$membership_types_row['membership_type_id'].'">'.$membership_types_row['membership_class'].'</label>
      <input id="membership_type_'.$membership_types_row['membership_type_id'].'" name="membership_type_id" type="radio" value="'.$membership_types_row['membership_type_id'].'"'.($member_info['membership_type_id'] == $membership_types_row['membership_type_id'] ? ' checked' : '').'>
    </div>';
  }

$content_edit_member = 
    $error_message.'
    <form name="edit_member" id="edit_member" method="POST" action="'.$_SERVER['SCRIPT_NAME'].($_GET['display_as'] == 'popup' ? '?display_as=popup' : '').'">
      <div class="form_buttons">
        <button type="submit" name="action" id="action" value="'.$submit_button_text.'">'.$submit_button_text.'</button>
        <button type="reset" name="reset" id="reset" value="Reset">Reset</button>
      </div>
      <fieldset class="personal_info">
        <legend>Personal Information</legend>
        <div class="input_block first_name">
          <label for="first_name" class="'.$error['first_name'].'">Name 1 (first)</label>
          <input type="text" id="first_name" name="first_name" size="20" maxlength="25" value="'.htmlspecialchars($member_info['first_name']).'" class="'.$error['first_name'].'">
        </div>
        <div class="input_block last_name">
          <label for="last_name" class="'.$error['last_name'].'">(last)</label>
          <input type="text" id="last_name" name="last_name" size="20" maxlength="25" value="'.htmlspecialchars($member_info['last_name']).'" class="'.$error['last_name'].'">
        </div>
        <div class="input_block first_name_2">
          <label for="first_name_2">Name 2 (first)</label>
          <input type="text" id="first_name_2" name="first_name_2" size="20" maxlength="25" value="'.htmlspecialchars($member_info['first_name_2']).'">
        </div>
        <div class="input_block last_name_2">
          <label for="last_name_2">(last)</label>
          <input type="text" id="last_name_2" name="last_name_2" size="20" maxlength="25" value="'.htmlspecialchars($member_info['last_name_2']).'">
        </div>
        <div class="input_block preferred_name">
          <label for="preferred_name" class="'.$error['preferred_name'].'">Name (preferred)</label>
          <input type="text" id="preferred_name" name="preferred_name" size="40" maxlength="50" value="'.htmlspecialchars($member_info['preferred_name']).'" class="'.$error['preferred_name'].'">
        </div>
        <div class="input_block business_name">
          <label for="business_name">Business name</label>
          <input type="text" id="business_name" name="business_name" size="40" maxlength="50" value="'.htmlspecialchars($member_info['business_name']).'">
        </div>
      </fieldset>
      <fieldset class="contact_info">
        <legend>Contact Information</legend>
        <div class="input_block address_line1">
          <label for="address_line1" class="'.$error['address_line1'].'">Home address</label>
          <input type="text" id="address_line1" name="address_line1" size="40" maxlength="50" value="'.htmlspecialchars($member_info['address_line1']).'" class="'.$error['address_line1'].'">
        </div>
        <div class="input_block address_line2">
          <input type="text" id="address_line2" name="address_line2" size="40" maxlength="50" value="'.htmlspecialchars($member_info['address_line2']).'">
        </div>
        <div class="input_block city">
          <label for="city" class="'.$error['city'].'">City</label>
          <input type="text" id="city" name="city" size="15" maxlength="15" value="'.htmlspecialchars($member_info['city']).'" class="'.$error['city'].'">
        </div>
        <div class="input_block state">
          <label for="state" class="'.$error['state'].'">State</label>
          <input type="text" id="state" name="state" size="2" maxlength="2" value="'.htmlspecialchars($member_info['state']).'" class="'.$error['state'].'">
        </div>
        <div class="input_block zip">
          <label for="zip" class="'.$error['zip'].'">Postal code</label>
          <input type="text" id="zip" name="zip" size="7" maxlength="10" value="'.htmlspecialchars($member_info['zip']).'" class="'.$error['zip'].'">
        </div>
        <div class="input_block county">
          <label for="county" class="'.$error['county'].'">County</label>
          <input type="text" id="county" name="county" size="7" maxlength="15" value="'.htmlspecialchars($member_info['county']).'" class="'.$error['county'].'">
        </div>
        <div class="input_block work_address_line1" class="'.$error['work_address_line1'].'">
          <label for="work_address_line1">Work address</label>
          <input type="text" id="work_address_line1" name="work_address_line1" size="40" maxlength="50" value="'.htmlspecialchars($member_info['work_address_line1']).'" class="'.$error['work_address_line1'].'">
        </div>
        <div class="input_block work_address_line2">
          <input type="text" id="work_address_line2" name="work_address_line2" size="40" maxlength="50" value="'.htmlspecialchars($member_info['work_address_line2']).'">
        </div>
        <div class="input_block work_city">
          <label for="work_city" class="'.$error['work_city'].'">City</label>
          <input type="text" id="work_city" name="work_city" size="15" maxlength="15" value="'.htmlspecialchars($member_info['work_city']).'" class="'.$error['work_city'].'">
        </div>
        <div class="input_block work_state">
          <label for="work_state" class="'.$error['work_state'].'">State</label>
          <input type="text" id="work_state" name="work_state" size="2" maxlength="2" value="'.htmlspecialchars($member_info['work_state']).'" class="'.$error['work_state'].'">
        </div>
        <div class="input_block work_zip">
          <label for="work_zip" class="'.$error['work_zip'].'">Postal code</label>
          <input type="text" id="work_zip" name="work_zip" size="7" maxlength="10" value="'.htmlspecialchars($member_info['work_zip']).'" class="'.$error['work_zip'].'">
        </div>
        <div class="input_block email_address">
          <label for="email_address" class="'.$error['email_address'].'">E-mail address</label>
          <input type="text" id="email_address" name="email_address" size="25" maxlength="100" value="'.htmlspecialchars($member_info['email_address']).'" class="'.$error['email_address'].'">
        </div>
        <div class="input_block email_address_2">
          <label for="email_address_2" class="'.$error['email_address_2'].'">E-mail address (secondary)</label>
          <input type="text" id="email_address_2" name="email_address_2" size="25" maxlength="100" value="'.htmlspecialchars($member_info['email_address_2']).'" class="'.$error['email_address_2'].'">
        </div>
        <div class="input_block home_phone">
          <label for="home_phone">Home phone</label>
          <input type="text" id="home_phone" name="home_phone" size="15" maxlength="20" value="'.htmlspecialchars($member_info['home_phone']).'">
        </div>
        <div class="input_block work_phone">
          <label for="work_phone">Work phone</label>
          <input type="text" id="work_phone" name="work_phone" size="15" maxlength="20" value="'.htmlspecialchars($member_info['work_phone']).'">
        </div>
        <div class="input_block mobile_phone">
          <label for="mobile_phone">Mobile phone</label>
          <input type="text" id="mobile_phone" name="mobile_phone" size="15" maxlength="20" value="'.htmlspecialchars($member_info['mobile_phone']).'">
        </div>
        <div class="input_block fax">
          <label for="fax">FAX</label>
          <input type="text" id="fax" name="fax" size="15" maxlength="20" value="'.htmlspecialchars($member_info['fax']).'">
        </div>
        <div class="input_block toll_free">
          <label for="toll_free">Toll-free</label>
          <input type="text" id="toll_free" name="toll_free" size="15" maxlength="20" value="'.htmlspecialchars($member_info['toll_free']).'">
        </div>
        <div class="input_block home_page">
          <label for="home_page" class="'.$error['home_page'].'">Home page (do not include http(s)://)</label>
          <input type="text" id="home_page" name="home_page" size="40" maxlength="200" value="'.htmlspecialchars($member_info['home_page']).'" class="'.$error['home_page'].'">
        </div>
      </fieldset>
      <fieldset class="site_info">
        <legend>Site-related Information</legend>
        <input type="hidden" id="member_id" name="member_id" size="20" maxlength="20" value="'.$member_info['member_id'].'">

        <div class="input_block username">
          <label for="username" class="'.$error['username'].'">Username</label>
          <input class="'.$error['username'].'" type="text" id="username" name="username" size="20" maxlength="20" value="'.htmlspecialchars($member_info['username']).'">
        </div>
        <div class="input_block password1">
          <label for="password1" class="'.$error['password1'].'">Password</label>
          <input type="password" id="password1" name="password1" size="20" maxlength="250" value="" autocomplete="off" class="'.$error['password1'].'">
        </div>
        <div class="input_block password2">
          <label for="password2" class="'.$error['password2'].'">Password (twice to change)</label>
          <input type="password" id="password2" name="password2" size="20" maxlength="250" value="" autocomplete="off" class="'.$error['password2'].'">
        </div>
        <div class="option_label">Member authorizations:</div>
        <div class="option_block auth_type">'.
          $auth_type_input.'
          <div style="clear:both"><!-- force option_block div to expand --></div>
        </div>
        <div class="option_label">Membership type (changes do not apply any charges or credits):</div>
        <div class="option_block membership_type">'.
          $membership_type_input.'
          <div style="clear:both"><!-- force option_block div to expand --></div>
        </div>
        <div class="input_block membership_date">
          <label for="membership_date" class="'.$error['membership_date'].'">Membership date</label>
          <input type="text" id="membership_date" name="membership_date" size="15" maxlength="50" value="'.$member_info['membership_date'].'" class="'.$error['membership_date'].'">
        </div>
        <div class="input_block last_renewal_date">
          <label for="last_renewal_date" class="'.$error['last_renewal_date'].'">Last renewal date</label>
          <input type="text" id="last_renewal_date" name="last_renewal_date" size="15" maxlength="50" value="'.$member_info['last_renewal_date'].'" class="'.$error['last_renewal_date'].'">
        </div>
        <div class="input_block customer_fee_percent">
          <label for="customer_fee_percent" class="'.$error['customer_fee_percent'].'">Customer fee (percent)</label>
          <input id="customer_fee_percent" name="customer_fee_percent" type="text" size="5" maxlength="8" value="'.$member_info['customer_fee_percent'].'" class="'.$error['customer_fee_percent'].'">
        </div>
        <div class="option_label other_options">Other options:</div>
        <div class="option_block miscellaneous">
          <div class="input_block pending">
            <label for="pending">Pending</label>
            <input id="pending" name="pending" type="checkbox" value="1"'.($member_info['pending'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block no_postal_mail">
            <label for="no_postal_mail">Send no postal mail</label>
            <input id="no_postal_mail" name="no_postal_mail" type="checkbox" value="1"'.($member_info['no_postal_mail'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block mem_delch_discount">
            <label for="mem_delch_discount">Delivery discount</label>
            <input id="mem_delch_discount" name="mem_delch_discount" type="checkbox" value="1"'.($member_info['mem_delch_discount'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block mem_taxexempt">
            <label for="mem_taxexempt">Tax exempt</label>
            <input id="mem_taxexempt" name="mem_taxexempt" type="checkbox" value="1"'.($member_info['mem_taxexempt'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block membership_discontinued">
            <label for="membership_discontinued">Membership discontinued</label>
            <input id="membership_discontinued" name="membership_discontinued" type="checkbox" value="1"'.($member_info['membership_discontinued'] == 1 ? ' checked' : '').'>
          </div>
          <div style="clear:both"><!-- force option_block div to expand --></div>
        </div>
        <div class="option_label">How heard about the '.ORGANIZATION_TYPE.':</div>
        <div class="option_block how_heard">'.
          $how_heard_input.'
          <div style="clear:both"><!-- force option_block div to expand --></div>
        </div>
        <div class="input_block notes">
          <label for="notes">Notes</label>
          <textarea id="notes" name="notes">'.htmlspecialchars($member_info['notes'], ENT_QUOTES).'</textarea>
        </div>
        <div style="clear:both"><!-- force option_block div to expand --></div>
      </fieldset>
    </form>
  </body>
</html>';

$page_specific_css = '
    <style type="text/css">
    fieldset {
      margin:1em auto;
      border: 1px solid #060;
      border-radius:5px;
      background-color: #fff;
      }
    fieldset.site_info,
    fieldset.personal_info,
    fieldset.contact_info {
      width: 60%;
      min-width:200px;
      }

    legend {
      margin:0 5px;
      padding:2px 5px;
      font-weight:bold;
      color:#040;
      }
    label,
    div.option_label {
      clear: both;
      color: #008;
      display: block;
      font-size: 70%;
      width: 100%;
      margin-left: 4px;
      }
    fieldset .input_block {
      display:inline-block;
      min-width:3em;
      float:left;
      }
    .auth_type_member,
    .auth_type_producer {
      float:left;
      }
    fieldset.personal_info {
      background-color:#efd;
      }
    fieldset.contact_info {
      background-color:#def;
      }
    fieldset.site_info {
      background-color:#edf;
      }

    .form_buttons button,
    fieldset input,
    fieldset button,
    .option_block,
    textarea {
      font-size:12px;
      padding:3px 8px;
      line-height:1.5;
      margin:2px;
      border-width:0;
      border-style:none;
      border-radius:5px;
      border-color:none;
      background:none;
      border:1px solid #686;
      box-shadow:2px 2px 0px 0px #bcb;
      background-color:#eee;
      color:#060;
      }
    .option_block .input_block {
      background:none;
      border-radius:5px;
      width:4.9em;
      height:6em;
      text-align:center;
      margin:0 8px;
      }
    .option_block .input_block label {
      display:table-cell;
      vertical-align:bottom;
      width:7em; /* width of containing input_block / 70% (font size) */
      height:4.5em;
      text-align:center;
      color:#060;
      }
    .input_block label {
      min-width:5em;
      margin-left: 4px;
      }
    .form_buttons button:hover,
    fieldset input:hover,
    fieldset button:hover,
    fieldset textarea:hover,
    .option_block .input_block:hover {
      background-color:#cdc;
      color:#040;
      }
    /* This rule overrides styles from wordpress */
    input:focus,
    textarea:focus {
      border:1px solid #686;
      }

    .password1,
    .auth_type,
    .membership_type,
    .membership_date,
    .miscellaneous,
    .notes,
    .first_name,
    .first_name_2,
    .preferred_name,
    .business_name,
    .address_line1,
    .address_line2,
    .city,
    .work_address_line1,
    .work_address_line2,
    .work_city,
    .email_address,
    .home_phone,
    .home_page {
      clear:left;
      }

    div.username,
    div.password2,
    div.auth_type,
    div.membership_type,
    div.last_renewal_date,
    div.customer_fee_percent,
    div.miscellaneous,
    div.how_heard,
    div.notes,
    div.last_name_2,
    div.preferred_name,
    div.county,
    div.work_zip,
    div.email_address_2,
    div.toll_free {
      margin-bottom:1em;
      }
    div.notes,
    #notes {
      width:97%;
      height:7em;
      }
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
    </style>';

if($_GET['display_as'] == 'popup')
  $display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_edit_member.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
