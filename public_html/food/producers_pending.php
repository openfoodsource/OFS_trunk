<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer_admin,site_admin');

if ( $_POST['pending'] )
  {
    foreach( $_POST['pending'] as $producer_id=>$value )
      {
        $query = '
          SELECT
            '.TABLE_PRODUCER.'.business_name,
            '.TABLE_MEMBER.'.first_name,
            '.TABLE_MEMBER.'.last_name,
            '.TABLE_MEMBER.'.email_address,
            '.TABLE_MEMBER.'.member_id,
            '.TABLE_MEMBER.'.auth_type
          FROM
            '.TABLE_PRODUCER.'
          LEFT JOIN '.TABLE_MEMBER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
          WHERE
            producer_id = "'.mysql_real_escape_string($producer_id).'"';
        $sql = mysql_query($query, $connection) or die("Couldn't execute query 4.");
        $producer_info = mysql_fetch_object($sql);
        if ( $value == 'approve' )
          {
            $query = '
              UPDATE
                '.TABLE_PRODUCER.'
              SET
                pending="0"
              WHERE
                producer_id="'.mysql_real_escape_string($producer_id).'"';
            $sql = mysql_query($query);

            // Now send the confirmation email...

            if ( $producer_info )
              {
                // Now send the "Newly Activated" email notice
                $subject  = 'Account status: '.SITE_NAME;
                $email_to = preg_replace ('/SELF/', $producer_info->email_address, PRODUCER_FORM_EMAIL);
                $headers  = "From: ".MEMBERSHIP_EMAIL."\nReply-To: ".MEMBERSHIP_EMAIL."\n";
                $headers .= "Errors-To: ".GENERAL_EMAIL."\n";
                $headers .= "MIME-Version: 1.0\n";
                $headers .= "Content-type: text/plain; charset=us-ascii\n";
                $headers .= "Message-ID: <".md5(uniqid(time()))."@".DOMAIN_NAME.">\n";
                $headers .= "X-Mailer: PHP ".phpversion()."\n";
                $headers .= "X-Priority: 3\n";
                $headers .= 'X-AntiAbuse: This is a user-submitted email through the '.SITE_NAME." producer approval page.\n\n";
                $msg  = "Dear ".$producer_info->first_name." ".$producer_info->last_name.",\n\n";
                $msg .= "Your producer account with ".SITE_NAME." has been activated. \n\n";
                $msg .= "When you log in to your regular member account, you will have a new section relating to ";
                $msg .= "producer functions. You may immediately begin adding new products to the system but they ";
                $msg .= "will not be available for ordering until an order is open.  If, for some reason, you need ";
                $msg .= "to change a product listing during an order cycle, you will need to contact one of the site ";
                $msg .= "administrators at the Producer help address below to make your changes \"live\".  Until that ";
                $msg .= "step is completed, your products and any changes you make will not show up on the public ";
                $msg .= "shopping pages (except changes to your inventory).\n\n";
                $msg .= "Producer help is available at: ".PRODUCER_CARE_EMAIL."\n";
                $msg .= "Other help is always available at: ".HELP_EMAIL."\n";
                $msg .= "Join in the fun, volunteer! ".VOLUNTEER_EMAIL."\n\n";
                $msg .= "If I can be of any help to you or you have any questions, please contact me. \n\n";
//                $msg .= AUTHORIZED_PERSON."\n";
                $msg .= 'Standards Committee'."\n";
                $msg .= STANDARDS_EMAIL;
                mail($email_to, $subject, $msg, $headers);
              }
            $content_pending .= '&nbsp;<b>'.$producer_info->business_name.'</b> (#'.$producer_id.') was updated.<br>';
          }
        else if ( $value == "remove" )
          {
            $query = '
              DELETE FROM
                '.TABLE_PRODUCER.'
              WHERE
                producer_id="'.mysql_real_escape_string($producer_id).'"';
            mysql_query($query);
            
            $query = '
              DELETE FROM
                '.TABLE_PRODUCER_REG.'
              WHERE
                producer_id="'.mysql_real_escape_string($producer_id).'"';
            mysql_query($query);
            
            if ( $producer_info )
              {
                //remove "producer" from auth_type
                $auth_type = explode(",", $producer_info->auth_type);
                foreach(array_keys($auth_type, 'producer') as $key) unset($auth_type[$key]);
                $auth_type = implode(",", $auth_type);
                $query = '
                  UPDATE
                    '.TABLE_MEMBER.'
                  SET
                    auth_type="'.mysql_real_escape_string($auth_type).'"
                  WHERE
                    member_id="'.mysql_real_escape_string($producer_info->member_id).'"';
                  mysql_query($query);
              }
            $content_pending .= '&nbsp;<b>'.$producer_info->business_name.'</b> (#'.$producer_id.') was removed.<br>';
          }
      }
  }
$display = '';
$query = '
  SELECT
    producer_id,
    '.TABLE_PRODUCER.'.business_name,
    first_name,
    last_name,
    '.TABLE_PRODUCER.'.member_id,
    home_phone,
    email_address
  FROM
    '.TABLE_PRODUCER.',
    '.TABLE_MEMBER.'
  WHERE
    '.TABLE_PRODUCER.'.pending != "0"
    AND '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id';
$sql = mysql_query($query);
while ( $row = mysql_fetch_array($sql) )
  {
    $display .= '
      <tr>
        <td style="white-space: nowrap">
          <input type="radio" name="pending['.$row['producer_id'].']" value="" checked>Pending<br>
          <input type="radio" name="pending['.$row['producer_id'].']" value="approve">Approve<br>
          <input type="radio" name="pending['.$row['producer_id'].']" value="remove">Remove
        </td>
        <td><b>'.$row['producer_id'].'</b></td>
        <td><a href="'.PATH.'prdcr_display_quest.php?pid='.$row['producer_id'].'" target="_blank">'.$row['business_name'].'</a></td>
        <td>'.$row['first_name'].'</td>
        <td>'.$row['last_name'].'</td>
        <td>'.$row['home_phone'].'</td>
        <td><a href="mailto:'.$row['email_address'].'">'.$row['email_address'].'</a></td>
        <td>'.$row['member_id'].'</td>
      </tr>
    ';
  }
if ( !$display )
  {
    $display = '
      <tr>
        <td colspan="8" align="right">There are no pending producers.</td>
      </tr>';
  }
else
  {
    $display .= '
      <tr>
        <td colspan="8" align="center"><input type="submit" name="submit" value="Submit"></td>
      </tr>';
  }
$content_pending .= '
  <div align="center">
  <form name="pendingproducers" method="POST">
  <table>
    <tr>
      <th>Status</th>
      <th>Producer ID</th>
      <th>Business Name<br>
        <span style="font-size:60%;font-weight:normal">(click to view questionnaire)</span></th>
      <th>First Name</th>
      <th>Last Name</th>
      <th>Phone</th>
      <th>Email</th>
      <th>Member ID</th>
    </tr>
    '.$display.'
  </table>
  </form>
  </div>
  <br><br>
  ';


$page_specific_css .= '
<style type="text/css">
table, td, th {
  border: 1px solid #CCCCCC;
  }
</style>';

$page_title_html = '<span class="title">Producer Membership Information</span>';
$page_subtitle_html = '<span class="subtitle">Pending Producers</span>';
$page_title = 'Producer Membership Information: Pending Producers';
$page_tab = 'producer_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_pending.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
