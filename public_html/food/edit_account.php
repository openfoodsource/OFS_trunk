<?php
include_once 'config_openfood.php';
session_start();
valid_auth('cashier');

// How was this script called?
$account_id = isset($_GET['account_key']) ? mysqli_real_escape_string ($connection, $_GET['account_key']) : '';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$need_query = true;
$modal_action = '';
$display = '';

if ($action == 'Update')
  {
    $account_id         = isset($_POST['account_id'])         ? mysqli_real_escape_string ($connection, $_POST['account_id'])         : '';
    $internal_key       = isset($_POST['internal_key'])       ? mysqli_real_escape_string ($connection, $_POST['internal_key'])       : '';
    $internal_subkey    = isset($_POST['internal_subkey'])    ? mysqli_real_escape_string ($connection, $_POST['internal_subkey'])    : '';
    $account_number     = isset($_POST['account_number'])     ? mysqli_real_escape_string ($connection, $_POST['account_number'])     : '';
    $sub_account_number = isset($_POST['sub_account_number']) ? mysqli_real_escape_string ($connection, $_POST['sub_account_number']) : '';
    $description        = isset($_POST['description'])        ? mysqli_real_escape_string ($connection, $_POST['description'])        : '';
    // Validate incoming data
    $error_array = array ();

    if (strlen ($internal_key) < 3 )
      {
        array_push ($error_array, 'Internal key must be at least three characters');
      }
    if (! preg_match ('/^[a-zA-Z0-9 ]*$/', $internal_key))
      {
        array_push ($error_array, 'Internal key can only contain alpha-numeric and space characters');
      }
    if (! preg_match ('/^[a-zA-Z0-9\-\_\+#\*@%\. ]*$/', $internal_subkey))
      {
        array_push ($error_array, 'Internal subkey can only contain alpha-numeric, space, and -_+#*@%. characters');
      }
    if (! preg_match ('/^[a-zA-Z0-9\-\_\+#\*@%\. ]*$/', $account_number))
      {
        array_push ($error_array, 'Account number can only contain alpha-numeric, space, and -_+#*@%. characters');
      }
    if (! preg_match ('/^[a-zA-Z0-9\-\_\+#\*@%\. ]*$/', $sub_account_number))
      {
        array_push ($error_array, 'Sub-account number can only contain alpha-numeric, space, and -_+#*@%. characters');
      }
    if (strlen ($description) < 1 )
      {
        array_push ($error_array, 'Description can not be blank');
      }
    if (! preg_match ('/^[a-zA-Z0-9!@#\$\^&\*\(\)\-_\[\]\,\.\?\/=\+:; ]+$/', $description))
      {
        array_push ($error_array, 'Description can contain most characters except quotes and angle brackets');
      }
    // We had some errors, so go back and ask for a revision
    if (count ($error_array) > 0)
      {
        $error_message = '
          <div class="error_message">
            <p class="message">Information was not accepted. Please correct the following problems and resubmit.</p>
            <ul class="error_list">
              <li>'.implode ("</li>\n<li>", $error_array).'</li>
            </ul>
          </div>';
        // Use POST data in lieu of the query data
        $row = $_POST;
        $need_query = false;
        // If we were passed an account_key != 0, then this is an edit request
        if ($account_id > 0)
          {
            $action = 'edit';
          }
        else
          {
            $action = 'add';
          }
      }
    // Otherwise, everything is good, so go ahead and post the new information
    else
      {
        if ($account_id > 0)
          {
            $query = '
              UPDATE
                '.NEW_TABLE_ACCOUNTS.'
              SET
                internal_key = "'.$internal_key.'",
                internal_subkey = "'.$internal_subkey.'",
                account_number = "'.$account_number.'",
                sub_account_number = "'.$sub_account_number.'",
                description = "'.$description.'"
              WHERE
                account_id = "'.$account_id.'"';
          }
        else
          {
            $query = '
              INSERT INTO
                '.NEW_TABLE_ACCOUNTS.'
              SET
                account_id = "'.$account_id.'",
                internal_key = "'.$internal_key.'",
                internal_subkey = "'.$internal_subkey.'",
                account_number = "'.$account_number.'",
                sub_account_number = "'.$sub_account_number.'",
                description = "'.$description.'"';
          }
//        $display .= "Under normal conditions, we would now do this:<br><pre>$query</pre>";
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 759321 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));

        // Do something here to close the modal
        $modal_action = 'parent.reload_parent()';
      }
  }
if ($action == 'add')
  {
    $display .= $error_message.'
      <div id="edit_account">
        <h3>Edit account information</h3>
        <form id="edit_account_form" name="edit_account_form" method="post" action="'.$_SERVER['SCRIPT_NAME'].'">
          <fieldset>
            <legend>Enter or change values</legend>
            <label for="internal_key">Short Name (3-25 chars) <span class="alert">Required field. Can not be changed</span></label>
            <input type="text" name="internal_key" id="internal_key" maxlength="25" value="'.$row['internal_key'].'" pattern="[a-zA-Z0-9 ]+" required>
            <!--
            <label for="internal_subkey">Internal Subkey</label>
            <input type="text" name="internal_subkey" id="internal_subkey" maxlength="25" value="'.$row['internal_subkey'].'" pattern="[a-zA-Z0-9\-\_\+#\*@%\. ]*">
            -->
            <label for="account_number">Account Number (max 50 chars)</label>
            <input type="text" name="account_number" id="account_number" maxlength="50" value="'.$row['account_number'].'" pattern="[a-zA-Z0-9\-\_\+#\*@%\. ]*">
            <label for="sub_account_number">Sub-Account Number (max 50 chars)</label>
            <input type="text" name="sub_account_number" id="sub_account_number" maxlength="50" value="'.$row['sub_account_number'].'" pattern="[a-zA-Z0-9\-\_\+#\*@%\. ]*">
            <label for="description">Description (max 255 chars)</label>
            <input type="text" name="description" id="description" maxlength="255" value="'.$row['description'].'" pattern="[a-zA-Z0-9!@#\$\^&\*\(\)\-_\[\]\,\.\?\/=\+:; ]+">
            <label>&nbsp;</label>
            <input type="submit" name="action" id="action" value="Update">
            <input type="button" name="cancel" id="cancel" value="Cancel">
            <input type="reset" name="reset" id="reset" value="Reset">
          </fieldset>
        </form>
      </div>';
  }
elseif ($action == 'edit' && $account_id > 0)
  {
    if ($need_query == true)
      {
        $query = '
          SELECT
            *
          FROM
            '.NEW_TABLE_ACCOUNTS.'
          WHERE
            account_id = '.$account_id;
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 675293 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
      }
    $display .= $error_message.'
      <div id="edit_account">
        <h3>Edit account information</h3>
        <form id="edit_account_form" name="edit_account_form" method="post" action="'.$_SERVER['SCRIPT_NAME'].'">
          <fieldset>
            <legend>Enter or change values</legend>
            <label for="internal_key">Short Name (max 25 chars) <span class="alert">Can not be changed</span></label>
            <input type="hidden" name="internal_key" id="internal_key" value="'.$row['internal_key'].'">
            <input type="text" name="internal_key_display" id="internal_key_display" value="'.$row['internal_key'].'" disabled>
            <!--
            <label for="internal_subkey">Internal Subkey</label>
            <input type="text" name="internal_subkey" id="internal_subkey" maxlength="25" value="'.$row['internal_subkey'].'" pattern="[a-zA-Z0-9\-\_\+#\*@%\. ]*">
            -->
            <label for="account_number">Account Number (max 50 chars)</label>
            <input type="text" name="account_number" id="account_number" maxlength="50" value="'.$row['account_number'].'" pattern="[a-zA-Z0-9\-\_\+#\*@%\. ]*">
            <label for="sub_account_number">Sub-Account Number (max 50 chars)</label>
            <input type="text" name="sub_account_number" id="sub_account_number" maxlength="50" value="'.$row['sub_account_number'].'" pattern="[a-zA-Z0-9\-\_\+#\*@%\. ]*">
            <label for="description">Description (max 255 chars)</label>
            <input type="text" name="description" id="description" maxlength="255" value="'.$row['description'].'" pattern="[a-zA-Z0-9!@#\$\^&\*\(\)\-_\[\]\,\.\?\/=\+:; ]+">
            <input type="hidden" name="account_id" id="account_id" value="'.$row['account_id'].'">
            <label for="account_id_display">Account ID</label>
            <input type="text" name="account_id_display" id="account_id_display" maxlength="5" value="'.$row['account_id'].'" disabled>
            <input type="submit" name="action" id="action" value="Update">
            <input type="button" name="cancel" id="cancel" value="Cancel">
            <input type="reset" name="reset" id="reset" value="Reset">
          </fieldset>
        </form>
      </div>';
  }

$page_specific_javascript = '';
$page_specific_stylesheets['edit_account'] = array (
  'name'=>'edit_account',
  'src'=>BASE_URL.PATH.'edit_account.css',
  'dependencies'=>array('ofs_stylesheet'),
  'version'=>'2.1.1',
  'media'=>'all'
  );
$display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
