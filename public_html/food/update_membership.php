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
    // Don't allow direct access to this page
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
        $modal_action = 'reload_parent';
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
        $renew_membership_form .= '
          <h3>Change Membership Type</h3>
          <p>Depending on the changes you are making, this could adversely affect your renewal date and/or other membership priviliges with the '.ORGANIZATION_TYPE.'. Multiple changes might result in additional membership dues that will need to be manually adjusted. For additional help, please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'?Subject=Changing%20Membership%20(member%20#'.$_SESSION['member_id'].')">'.MEMBERSHIP_EMAIL.'</a>.</p>
          <p>Select from the option(s) below to change your membership type:</p>';
      }
    else
      {
        // Instructions for mandatory membership changes
        $renew_membership_form .= '
          <h3>Membership Renewal</h3>
          <p>'.$membership_renewal_form['expire_message'].' Any charges will be added to your next ordering invoice.</p>
          <p>Please select from the option(s) below to continue.</p>';
      }
    $renew_membership_form .= '
        <form action="'.$_SERVER['SCRIPT_NAME'].($_GET['display_as'] == 'popup' ? '?display_as=popup' : '').'" method="post">'.
        $membership_renewal_form['same_renewal'].
        $membership_renewal_form['changed_renewal'].
        '<input type="hidden" name="update_membership" value="true">
        <input id="renew_membership" type="submit" name="submit" value="Renew now!">
        </form>
      </div>';
  }

if($_GET['display_as'] == 'popup')
  $display_as_popup = true;

// If everything was accepted, then provide a message rather than showing the form again
$message = '<div class="membership_message">'.($display_as_popup == true ? 'Reloading...' : 'Updating...').'</div>';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.($modal_action != 'reload_parent' ? $renew_membership_form : $message).'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
