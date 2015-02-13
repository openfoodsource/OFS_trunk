<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,producer_admin');

// echo "<pre>".print_r($_POST,true)."</pre>";

// CREATE A NEW MEMBER?
if (isset ($_REQUEST['producer_id']))
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
    // Ensure there is no producer_link conflict
    $query = '
      SELECT producer_link
      FROM '.TABLE_PRODUCER.'
      WHERE
        producer_link = "'.mysql_real_escape_string ($_POST['producer_link']).'"
        AND producer_id != "'.mysql_real_escape_string ($_POST['producer_id']).'"';
    $result = mysql_query($query) or die (debug_print ("ERROR: 238014 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $number_of_conflicts = @mysql_num_rows($result);
    if ($number_of_conflicts > 0)
      {
        array_push ($error_array, 'The producer link already exists. Please select a different value.');
        $error['producer_link'] = ' error';
      }
    // Ensure the producer_link contains only the correct characters
    if (! preg_match ('|^[0-9A-Za-z\-\._]*$|', $_POST['producer_link']))
      {
        array_push ($error_array, 'The producer link can only contain alpha-numeric characters, dash(-), dot(.) and underscore(_).');
        $error['producer_link'] = ' error';
      }
    // Ensure there is a business_name provided
    if (strlen ($_POST['business_name']) == 0)
      {
        array_push ($error_array, 'Please provide a business name.');
        $error['business_name'] = ' error';
      }
    // Ensure the list_order is an integer
    if (! preg_match ('|^[0-9]*$|', $_POST['list_order']))
      {
        array_push ($error_array, 'The listing order must be a positive integer.');
        $error['list_order'] = ' error';
      }
    // Be sure the producer fee is a number
    if (! preg_match ('|^(-)?[0-9]*(\.)?[0-9]*$|', $_POST['producer_fee_percent']))
      {
        array_push ($error_array, 'The producer fee must be a postive or negative decimal number.');
        $error['producer_fee_percent'] = ' error';
      }
    // Ensure the member_id is an integer and corresponds to a real member in the database
    if (! preg_match ('|^[0-9]*$|', $_POST['member_id']))
      {
        array_push ($error_array, 'The member manager must be a positive integer.');
        $error['member_id'] = ' error';
      }
    $query = '
      SELECT member_id
      FROM '.TABLE_MEMBER.'
      WHERE
        member_id = "'.mysql_real_escape_string ($_POST['member_id']).'"';
    $result = mysql_query($query) or die (debug_print ("ERROR: 784032 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $number_of_conflicts = @mysql_num_rows($result);
    if ($number_of_conflicts != 1)
      {
        array_push ($error_array, 'The member manager must reference exactly one real member, by their Member ID.');
        $error['member_id'] = ' error';
      }
    // Validate the publicly-displayed information fields (needs information from the member manager)
    // This is done HERE because we have just ensured the member_id is valid
    else
      {
        $query = '
          SELECT
            address_line1,
            address_line2,
            city,
            state,
            zip,
            county,
            email_address,
            email_address_2,
            home_phone,
            work_phone,
            mobile_phone,
            fax,
            toll_free,
            home_page
          FROM '.TABLE_MEMBER.'
          WHERE
            member_id="'.mysql_real_escape_string($_POST['member_id']).'"';
        $result = mysql_query($query) or die (debug_print ("ERROR: 010293 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $row = mysql_fetch_array($result);
          {
            // Home address
            if (PRODUCER_PUB_ADDRESS == 'REQUIRED' &&
                $_POST['pub_address'] == 0)
              {
                array_push ($error_array, 'Producer [home] address is required to be published.');
                $error['pub_address'] = ' error';
              }
            if (PRODUCER_PUB_ADDRESS == 'REQUIRED' &&
                (strlen ($row['address_line1']) == 0 ||
                 strlen ($row['city']) == 0 ||
                 strlen ($row['state']) == 0 ||
                 strlen ($row['zip']) == 0 ||
                 strlen ($row['county']) == 0))
              {
                array_push ($warn_array, 'Producer [home] address is required to be published but some part(s) of the home address or county are missing.');
              }
            if (PRODUCER_PUB_ADDRESS == 'DENIED' &&
                $_POST['pub_address'] == 1)
              {
                array_push ($error_array, 'Producer [home] address may not be published.');
                $error['pub_address'] = ' error';
              }
            // Primary email
            if (PRODUCER_PUB_EMAIL == 'REQUIRED' &&
                $_POST['pub_email'] == 0)
              {
                array_push ($error_array, 'Producer primary e-mail address is required to be published.');
                $error['pub_email'] = ' error';
              }
            if (PRODUCER_PUB_EMAIL == 'REQUIRED' &&
                strlen ($row['email_address']) == 0)
              {
                array_push ($warn_array, 'Producer primary e-mail address is required to be published but no primary e-mail address is available to display.');
              }
            if (PRODUCER_PUB_EMAIL == 'DENIED' &&
                $_POST['pub_email'] == 1)
              {
                array_push ($error_array, 'Producer primary e-mail address may not be published.');
                $error['pub_email'] = ' error';
              }
            // Secondary email
            if (PRODUCER_PUB_EMAIL2 == 'REQUIRED' &&
                $_POST['pub_email2'] == 0)
              {
                array_push ($error_array, 'Producer secondary e-mail address is required to be published.');
                $error['pub_email2'] = ' error';
              }
            if (PRODUCER_PUB_EMAIL2 == 'REQUIRED' &&
                strlen ($row['email_address_2']) == 0)
              {
                array_push ($warn_array, 'Producer secondary e-mail address is required to be published but no secondary e-mail address is available to display.');
              }
            if (PRODUCER_PUB_EMAIL2 == 'DENIED' &&
                $_POST['pub_email2'] == 1)
              {
                array_push ($error_array, 'Producer secondary e-mail address may not be published.');
                $error['pub_email2'] = ' error';
              }
            // Home phone
            if (PRODUCER_PUB_PHONEH == 'REQUIRED' &&
                $_POST['pub_phoneh'] == 0)
              {
                array_push ($error_array, 'Producer home phone is required to be published.');
                $error['pub_phoneh'] = ' error';
              }
            if (PRODUCER_PUB_PHONEH == 'REQUIRED' &&
                strlen ($row['home_phone']) == 0)
              {
                array_push ($warn_array, 'Producer home phone is required to be published but no home phone number is available to display.');
              }
            if (PRODUCER_PUB_PHONEH == 'DENIED' &&
                $_POST['pub_phoneh'] == 1)
              {
                array_push ($error_array, 'Producer home phone may not be published.');
                $error['pub_phoneh'] = ' error';
              }
            // Work phone
            if (PRODUCER_PUB_PHONEW == 'REQUIRED' &&
                $_POST['pub_phonew'] == 0)
              {
                array_push ($error_array, 'Producer work phone is required to be published.');
                $error['pub_phonew'] = ' error';
              }
            if (PRODUCER_PUB_PHONEW == 'REQUIRED' &&
                strlen ($row['work_phone']) == 0)
              {
                array_push ($warn_array, 'Producer work phone is required to be published but no work phone number is available to display.');
              }
            if (PRODUCER_PUB_PHONEW == 'DENIED' &&
                $_POST['pub_phonew'] == 1)
              {
                array_push ($error_array, 'Producer work phone may not be published.');
                $error['pub_phonew'] = ' error';
              }
            // Mobile phone
            if (PRODUCER_PUB_PHONEC == 'REQUIRED' &&
                $_POST['pub_phonec'] == 0)
              {
                array_push ($error_array, 'Producer mobile phone is required to be published.');
                $error['pub_phonec'] = ' error';
              }
            if (PRODUCER_PUB_PHONEC == 'REQUIRED' &&
                strlen ($row['mobile_phone']) == 0)
              {
                array_push ($warn_array, 'Producer mobile phone is required to be published but no work mobile number is available to display.');
              }
            if (PRODUCER_PUB_PHONEC == 'DENIED' &&
                $_POST['pub_phonec'] == 1)
              {
                array_push ($error_array, 'Producer mobile phone may not be published.');
                $error['pub_phonec'] = ' error';
              }
            // Toll-free phone
            if (PRODUCER_PUB_PHONET == 'REQUIRED' &&
                $_POST['pub_phonet'] == 0)
              {
                array_push ($error_array, 'Producer toll-free phone is required to be published.');
                $error['pub_phonet'] = ' error';
              }
            if (PRODUCER_PUB_PHONET == 'REQUIRED' &&
                strlen ($row['toll_free']) == 0)
              {
                array_push ($warn_array, 'Producer toll-free phone is required to be published but no toll-free number is available to display.');
              }
            if (PRODUCER_PUB_PHONET == 'DENIED' &&
                $_POST['pub_phonet'] == 1)
              {
                array_push ($error_array, 'Producer toll-free phone may not be published.');
                $error['pub_phonet'] = ' error';
              }
            // FAX
            if (PRODUCER_PUB_FAX == 'REQUIRED' &&
                $_POST['pub_fax'] == 0)
              {
                array_push ($error_array, 'Producer FAX is required to be published.');
                $error['pub_fax'] = ' error';
              }
            if (PRODUCER_PUB_FAX == 'REQUIRED' &&
                strlen ($row['fax']) == 0)
              {
                array_push ($warn_array, 'Producer FAX is required to be published but no FAX number is available to display.');
              }
            if (PRODUCER_PUB_FAX == 'DENIED' &&
                $_POST['pub_fax'] == 1)
              {
                array_push ($error_array, 'Producer FAX may not be published.');
                $error['pub_fax'] = ' error';
              }
            // Web page
            if (PRODUCER_PUB_WEB == 'REQUIRED' &&
                $_POST['pub_web'] == 0)
              {
                array_push ($error_array, 'Producer home page is required to be published.');
                $error['pub_web'] = ' error';
              }
            if (PRODUCER_PUB_WEB == 'REQUIRED' &&
                strlen ($row['home_page']) == 0)
              {
                array_push ($warn_array, 'Producer home page is required to be published but no home page is available to display.');
              }
            if (PRODUCER_PUB_WEB == 'DENIED' &&
                $_POST['pub_web'] == 1)
              {
                array_push ($error_array, 'Producer home page may not be published.');
                $error['pub_web'] = ' error';
              }
            // Check on requirement to publish at least one email address
            if (PRODUCER_REQ_EMAIL == true &&
                ($_POST['pub_email'] == 0 &&
                 $_POST['pub_email2'] == 0))
              {
                array_push ($error_array, 'Producer is required to publish at least one e-mail address (primary or secondary).');
                $error['pub_email'] = ' error';
                $error['pub_email2'] = ' error';
              }
            if (PRODUCER_REQ_EMAIL == true &&
                (($_POST['pub_email'] == 0 ||
                 strlen ($row['email_address']) == 0) &&
                 ($_POST['pub_email2'] == 0 ||
                 strlen ($row['email_address_2']) == 0)))
              {
                array_push ($warn_array, 'Producer is required to publish at least one e-mail address (primary or secondary) but no referenced e-mail address is available for display.');
              }
            // Check on requirement to publish at least one phone number
            if (PRODUCER_REQ_PHONE == true &&
                ($_POST['pub_phoneh'] == 0 &&
                 $_POST['pub_phonew'] == 0 &&
                 $_POST['pub_phonec'] == 0 &&
                 $_POST['pub_phonet'] == 0))
              {
                array_push ($error_array, 'Producer is required to publish at least one phone number (home, work, mobile, toll-free).');
                $error['pub_phoneh'] = ' error';
                $error['pub_phonew'] = ' error';
                $error['pub_phonec'] = ' error';
                $error['pub_phonet'] = ' error';
              }
            if (PRODUCER_REQ_PHONE == true &&
                (($_POST['pub_phoneh'] == 0 ||
                 strlen ($row['home_phone']) == 0) &&
                 ($_POST['pub_phonew'] == 0 ||
                 strlen ($row['work_phone']) == 0) &&
                 ($_POST['pub_phonec'] == 0 ||
                 strlen ($row['mobile_phone']) == 0) &&
                 ($_POST['pub_phonet'] == 0 ||
                 strlen ($row['toll_free']) == 0)))
              {
                array_push ($warn_array, 'Producer is required to publish at least one phone number (home, work, mobile, toll-free) but no referenced phone number is available for display.');
              }
          }
      }
  }
// If there are no validation errors, then post the new data and get the changed values from the database
if (count ($error_array) == 0 &&
    ($_REQUEST['action'] == 'Update' ||
     $_REQUEST['action'] == 'Add New'))
  {
    // Get the auth_types from checkboxes
    // Prepare the database insert or update
    $query_values = '
          SET
            producer_link = "'.mysql_real_escape_string($_POST['producer_link']).'",
            business_name = "'.mysql_real_escape_string($_POST['business_name']).'",
            payee = "'.mysql_real_escape_string($_POST['payee']).'",
            list_order = "'.mysql_real_escape_string($_POST['list_order']).'",
            member_id = "'.mysql_real_escape_string($_POST['member_id']).'",
            producer_fee_percent = "'.mysql_real_escape_string($_POST['producer_fee_percent']).'",
            pending = "'.mysql_real_escape_string($_POST['pending']).'",
            unlisted_producer = "'.mysql_real_escape_string($_POST['unlisted_producer']).'",
            pub_address = "'.mysql_real_escape_string($_POST['pub_address']).'",
            pub_email = "'.mysql_real_escape_string($_POST['pub_email']).'",
            pub_email2 = "'.mysql_real_escape_string($_POST['pub_email2']).'",
            pub_phoneh = "'.mysql_real_escape_string($_POST['pub_phoneh']).'",
            pub_phonew = "'.mysql_real_escape_string($_POST['pub_phonew']).'",
            pub_phonec = "'.mysql_real_escape_string($_POST['pub_phonec']).'",
            pub_phonet = "'.mysql_real_escape_string($_POST['pub_phonet']).'",
            pub_fax = "'.mysql_real_escape_string($_POST['pub_fax']).'",
            pub_web = "'.mysql_real_escape_string($_POST['pub_web']).'",
            producttypes = "'.mysql_real_escape_string($_POST['producttypes']).'",
            about = "'.mysql_real_escape_string($_POST['about']).'",
            ingredients = "'.mysql_real_escape_string($_POST['ingredients']).'",
            general_practices = "'.mysql_real_escape_string($_POST['general_practices']).'",
            highlights = "'.mysql_real_escape_string($_POST['highlights']).'",
            additional = "'.mysql_real_escape_string($_POST['additional']).'",
            liability_statement = "'.mysql_real_escape_string($_POST['liability_statement']).'"';
    // Put together the proper query
    if ($_REQUEST['action'] == 'Update')
      {
        $query = '
          UPDATE
            '.TABLE_PRODUCER.
          $query_values.'
          WHERE
            producer_id = "'.mysql_real_escape_string($_POST['producer_id']).'"';
        // Also update the producer registration table
        $query2 = '
          UPDATE
            '.TABLE_PRODUCER_REG.'
          SET
            member_id = "'.mysql_real_escape_string($_POST['member_id']).'"
          WHERE
            producer_id = "'.mysql_real_escape_string($_POST['producer_id']).'"';
      }
    elseif ($_REQUEST['action'] == 'Add New')
      {
        $query = '
          INSERT INTO
            '.TABLE_PRODUCER.
          $query_values;
        // Also enter a blank row in the producer registration table
        $query2 = '
          INSERT INTO
            '.TABLE_PRODUCER_REG.'
          SET
            member_id = "'.mysql_real_escape_string($_POST['member_id']).'",
            producer_id = "'.mysql_real_escape_string($_POST['producer_id']).'"';
      }
    $result = mysql_query($query) or die (debug_print ("ERROR: 759843 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $result2 = mysql_query($query2) or die (debug_print ("ERROR: 752893 ", array ($query2,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    // Get the new or current producer_id
    if ($_REQUEST['action'] == 'Add New')
      $_GET['producer_id'] = mysql_insert_id ();
    else
      $_GET['producer_id'] = $_POST['producer_id'];
    // And force the submit button text to "Update"
    $submit_button_text = 'Update';
  }
// Query for producer information if a producer_id was requested
if (isset ($_GET['producer_id']))
  {
    $query = '
      SELECT *
      FROM '.TABLE_PRODUCER.'
      WHERE
        producer_id="'.mysql_real_escape_string($_REQUEST['producer_id']).'"';
    $result = mysql_query($query) or die (debug_print ("ERROR: 167935 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $rows = @mysql_num_rows($result);
    if ($rows == 0)
      {
        // We found no producer information, so open the form for adding a new producer
        $submit_button_text = 'Add New';
        // Use the error notification to alert that we are not editing the producer
        array_push ($warn_array, 'Producer #'.$_GET['producer_id'].' was not found, but the form is open for adding a new producer.');
      }
    else
      {
        $producer_info = mysql_fetch_array($result);
      }
  }
// If there was a validation error, then display it and use posted data to fill the form
// This exists HERE because we need $producer_info next
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
    unset ($_GET['producer_id']);
    $producer_info = $_POST;
  }
$content_edit_producer = 
    $error_message.'
    <form name="edit_producer" id="edit_producer" method="POST" action="'.$_SERVER['SCRIPT_NAME'].($_GET['display_as'] == 'popup' ? '?display_as=popup' : '').'">
      <div class="form_buttons">
        <button type="submit" name="action" id="action" value="'.$submit_button_text.'">'.$submit_button_text.'</button>
        <button type="reset" name="reset" id="reset" value="Reset">Reset</button>
      </div>
      <fieldset class="personal_info">
        <legend>Personal Information</legend>
        <input type="hidden" id="producer_id" name="producer_id" value="'.htmlspecialchars($producer_info['producer_id']).'">
        <div class="input_block producer_link">
          <label for="producer_link" class="'.$error['producer_link'].'">Producer Link</label>
          <input type="text" id="producer_link" name="producer_link" size="40" maxlength="50" value="'.htmlspecialchars($producer_info['producer_link']).'" class="'.$error['producer_link'].'">
        </div>
        <div class="input_block business_name">
          <label for="business_name" class="'.$error['business_name'].'">Business name</label>
          <input type="text" id="business_name" name="business_name" size="40" maxlength="50" value="'.htmlspecialchars($producer_info['business_name']).'">
        </div>
        <div class="input_block payee">
          <label for="payee" class="'.$error['payee'].'">Payee (name to whom payments are made)</label>
          <input type="text" id="payee" name="payee" size="40" maxlength="50" value="'.htmlspecialchars($producer_info['payee']).'" class="'.$error['payee'].'">
        </div>
      </fieldset>
      <fieldset class="setup_info">
        <legend>Setup Information</legend>
        <div class="input_block list_order">
          <label for="list_order" class="'.$error['list_order'].'">Listing order</label>
          <input type="text" id="list_order" name="list_order" size="6" maxlength="6" value="'.htmlspecialchars($producer_info['list_order']).'">
        </div>
        <div class="input_block member_id">
          <label for="member_id" class="'.$error['member_id'].'">Member manager</label>
          <input type="text" id="member_id" name="member_id" size="9" maxlength="9" value="'.htmlspecialchars($producer_info['member_id']).'" class="'.$error['member_id'].'">
        </div>
        <div class="input_block producer_fee_percent">
          <label for="producer_fee_percent" class="'.$error['producer_fee_percent'].'">Producer fee (percent)</label>
          <input id="producer_fee_percent" name="producer_fee_percent" type="text" size="7" maxlength="7" value="'.$producer_info['producer_fee_percent'].'" class="'.$error['producer_fee_percent'].'">
        </div>
        <div class="option_label">Producer status:</div>
        <div class="option_block unlisted_producer">
          <div class="input_block pending">
            <label for="pending">Pending</label>
            <input id="pending" name="pending" type="checkbox" value="1"'.($producer_info['pending'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block unlisted_producer_1">
            <label for="unlisted_producer_1">Suspended</label>
            <input id="unlisted_producer_1" name="unlisted_producer" type="radio" value="2"'.($producer_info['unlisted_producer'] == 2 ? ' checked' : '').'>
          </div>
          <div class="input_block unlisted_producer_2">
            <label for="unlisted_producer_2">Unlisted</label>
            <input id="unlisted_producer_2" name="unlisted_producer" type="radio" value="1"'.($producer_info['unlisted_producer'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block unlisted_producer_3">
            <label for="unlisted_producer_3">Listed</label>
            <input id="unlisted_producer_3" name="unlisted_producer" type="radio" value="0"'.($producer_info['unlisted_producer'] == 0 ? ' checked' : '').'>
          </div>
          <div style="clear:both"><!-- force option_block div to expand --></div>
        </div>
        <div class="option_label">Member information that may be displayed publicly:</div>
        <div class="option_block publish_info">
          <div class="input_block pub_address">
            <label for="pub_address" class="'.$error['pub_address'].'">Street address</label>
            <input id="pub_address" name="pub_address" type="checkbox" value="1"'.($producer_info['pub_address'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block pub_email">
            <label for="pub_email" class="'.$error['pub_email'].'">Primary e-mail</label>
            <input id="pub_email" name="pub_email" type="checkbox" value="1"'.($producer_info['pub_email'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block pub_email2">
            <label for="pub_email2" class="'.$error['pub_email2'].'">Secondary e-mail</label>
            <input id="pub_email2" name="pub_email2" type="checkbox" value="1"'.($producer_info['pub_email2'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block pub_phoneh">
            <label for="pub_phoneh" class="'.$error['pub_phoneh'].'">Home phone</label>
            <input id="pub_phoneh" name="pub_phoneh" type="checkbox" value="1"'.($producer_info['pub_phoneh'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block pub_phonew">
            <label for="pub_phonew" class="'.$error['pub_phonew'].'">Work phone</label>
            <input id="pub_phonew" name="pub_phonew" type="checkbox" value="1"'.($producer_info['pub_phonew'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block pub_phonec">
            <label for="pub_phonec" class="'.$error['pub_phonec'].'">Mobile phone</label>
            <input id="pub_phonec" name="pub_phonec" type="checkbox" value="1"'.($producer_info['pub_phonec'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block pub_phonet">
            <label for="pub_phonet" class="'.$error['pub_phonet'].'">Toll-free phone</label>
            <input id="pub_phonet" name="pub_phonet" type="checkbox" value="1"'.($producer_info['pub_phonet'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block pub_fax">
            <label for="pub_fax" class="'.$error['pub_fax'].'">FAX</label>
            <input id="pub_fax" name="pub_fax" type="checkbox" value="1"'.($producer_info['pub_fax'] == 1 ? ' checked' : '').'>
          </div>
          <div class="input_block pub_web">
            <label for="pub_web" class="'.$error['pub_web'].'">Web page</label>
            <input id="pub_web" name="pub_web" type="checkbox" value="1"'.($producer_info['pub_web'] == 1 ? ' checked' : '').'>
          </div>
          <div style="clear:both"><!-- force option_block div to expand --></div>
        </div>
      </fieldset>
      <fieldset class="expository_info">
        <legend>Expository Information</legend>
        <div class="input_block producttypes textarea">
          <label for="producttypes" class="'.$error['producttypes'].'">Product types</label>
          <textarea id="producttypes" name="producttypes">'.htmlspecialchars($producer_info['producttypes'], ENT_QUOTES).'</textarea>
        </div>
        <div class="input_block about textarea">
          <label for="about" class="'.$error['about'].'">About the producer</label>
          <textarea id="about" name="about">'.htmlspecialchars($producer_info['about'], ENT_QUOTES).'</textarea>
        </div>
        <div class="input_block ingredients textarea">
          <label for="ingredients" class="'.$error['ingredients'].'">Ingredients</label>
          <textarea id="ingredients" name="ingredients">'.htmlspecialchars($producer_info['ingredients'], ENT_QUOTES).'</textarea>
        </div>
        <div class="input_block general_practices textarea">
          <label for="general_practices" class="'.$error['general_practices'].'">General practices</label>
          <textarea id="general_practices" name="general_practices">'.htmlspecialchars($producer_info['general_practices'], ENT_QUOTES).'</textarea>
        </div>
        <div class="input_block highlights textarea">
          <label for="highlights" class="'.$error['highlights'].'">Highlights</label>
          <textarea id="highlights" name="highlights">'.htmlspecialchars($producer_info['highlights'], ENT_QUOTES).'</textarea>
        </div>
        <div class="input_block additional textarea">
          <label for="additional" class="'.$error['additional'].'">Additional information</label>
          <textarea id="additional" name="additional">'.htmlspecialchars($producer_info['additional'], ENT_QUOTES).'</textarea>
        </div>
        <div class="input_block liability_statement textarea">
          <label for="liability_statement" class="'.$error['liability_statement'].'">Liability statement</label>
          <textarea id="liability_statement" name="liability_statement">'.htmlspecialchars($producer_info['liability_statement'], ENT_QUOTES).'</textarea>
        </div>
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
    fieldset.setup_info,
    fieldset.expository_info,
    fieldset.personal_info {
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
      }
    fieldset .input_block {
      display:inline-block;
      min-width:3em;
      float:left;
      }
    fieldset.personal_info {
      background-color:#efd;
      }
    fieldset.setup_info {
      background-color:#def;
      }
    fieldset.expository_info {
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
    /* Fields that need some spacing at the bottom */
    .expository_info .textarea textarea,
    div.username,
    div.password2,
    div.auth_type,
    div.producership_type,
    div.last_renewal_date,
    div.publish_info,
    div.producer_fee_percent,
    div.miscellaneous,
    div.how_heard,
    div.notes,
    div.list_order,
    div.member_id,
    div.county,
    div.work_zip,
    div.email_address_2,
    div.toll_free {
      margin-bottom:1em;
      }
    .expository_info .textarea {
      width:95%;
      }
    .expository_info .textarea textarea {
      width:100%;
      height:100px;
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
  '.$content_edit_producer.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");


