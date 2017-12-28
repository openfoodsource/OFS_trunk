<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

if($_GET['display_as'] == 'popup')
  {
    $display_as_popup = true;
  }
else
  {
    // Redirect to the member panel instead of allowing access to this page
    header('Location: '.PATH.'panel_member.php');
  }

// This function is also on the panel_members.php so it will execute before the page loads
// It needs to be in the header (here) to process for every other page it might be called from.
// Do we need to post membership changes?
if (isset ($_POST['update_membership']) && $_POST['update_membership'] == 'true')
  {
    include_once ('func.check_membership.php');
    renew_membership ($_SESSION['member_id'], $_POST['membership_type_id']);
    // Now update our session membership values
    $membership_info = get_membership_info ($_SESSION['member_id']);
    $_SESSION['renewal_info'] = check_membership_renewal ($membership_info);
    // If the renewal was successful, then close the modal dialog
    if (! $_SESSION['renewal_info']['membership_expired'])
      {
        // This flag is also used to determine whether the information was processed/accepted
        $modal_action = 'parent.reload_parent()';
      }
  }
$do_update_membership = false;
// Check if this is a forced update or if it is member-requested
if ($_SESSION['renewal_info']['membership_expired'])
  {
    // Don't allow a member-request to spoof a forced update
    $member_request = false;
    $do_update_membership = true;
  }
// Members requested update must come from panel_member.php (just to limit the scope) and
// must pass update=membership... something like this: panel_member.php?update=membership
else // if (isset ($_GET['update']) && $_GET['update'] == 'membership')
  {
    $member_request = true;
    $do_update_membership = true;
  }
// Display the update membership form
if ($do_update_membership == true)
  {
    include_once ('func.check_membership.php');
    $membership_info = get_membership_info ($_SESSION['member_id']);
    $membership_renewal = check_membership_renewal ($membership_info);
    $membership_renewal_form = membership_renewal_form($membership_info);
    // Block the page with a renewal form (but allow member-requested forms to be closed
    $renew_membership_form = '
      <div id="membership_renewal_content">';
    if ($member_request == true)
      {
        // Instructions for optional membership changes
        $display_form_title = '
          <h1>Change Membership</h1>';
        $renew_message = '
          <p>Depending on the changes you are making, this could adversely affect your renewal date and/or other membership priviliges with the '.ORGANIZATION_TYPE.'. Multiple changes might result in additional membership dues that will need to be manually adjusted. For additional help, please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'?Subject=Changing%20Membership%20(member%20#'.$_SESSION['member_id'].')">'.MEMBERSHIP_EMAIL.'</a>.</p>
          <p>Select from the option(s) below to change your membership type.</p>';
        $submit_button = 'Change';
      }
    else
      {
        // Instructions for mandatory membership changes
        $display_form_title = '
          <h1>Membership Renewal</h1>';
        $renew_message = '
          <p>'.$membership_renewal_form['expire_message'].' Any charges will be added to your next ordering invoice.</p>
          <p>Please select from the option(s) below to continue.</p>';
        $submit_button = 'Renew';
      }
    $renew_membership_form .= $display_form_title.'
        <form action="'.$_SERVER['SCRIPT_NAME'].($_GET['display_as'] == 'popup' ? '?display_as=popup' : '').'" method="post">
          <div class="form_buttons">
            <button type="submit" name="action" id="action" value="'.$submit_button.'">'.$submit_button.'</button>
            <button type="reset" name="reset" id="reset" value="Reset">Reset</button>
          </div>
          <fieldset class="renewal_options grouping_block">
            <legend>Membership Options</legend>
            <div class="note">'.
              $renew_message.'
            </div>'.
            $membership_renewal_form['same_renewal'].
            $membership_renewal_form['changed_renewal'].'
          </fieldset>
        <input type="hidden" name="update_membership" value="true">
        </form>
      </div>';
  }

$page_specific_css = '
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
  fieldset.renewal_options {
    background-color:#f8f4f0;
    width:80%;
    text-align:left;
    }
  fieldset.renewal_options legend {
    background-color:#f8f4f0;
    }
  .grouping_block .input_block {
    float:left;
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
    }
  .renew {
    color: #600;
    display: block;
    text-align: center;
    text-transform: uppercase;
    }
  .renew::before,
  .renew::after {
    content:"-";
    }';

if($_GET['display_as'] == 'popup')
  $display_as_popup = true;

// If everything was accepted, then provide a message rather than showing the form again
$message = '<div class="membership_message">'.($display_as_popup == true ? 'Reloading...' : 'Updating...').'</div>';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.($modal_action != 'parent.reload_parent()' ? $renew_membership_form : $message).'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
