<?php
include_once 'config_openfood.php';
session_start();
// This file is used both for member data and producer data so we constrain
// the permissions by what is being requested
if ($_REQUEST['p'] == 1) valid_auth('site_admin,producer_admin');
if ($_REQUEST['p'] == 0) valid_auth('site_admin,member_admin');


$wherestatement = '';
if ( $_REQUEST['p'] == 1 )
  {
    $query = '
      SELECT
        '.TABLE_PRODUCER.'.business_name,
        '.TABLE_MEMBER.'.*,
        '.TABLE_PRODUCER.'.producer_id,
        '.TABLE_MEMBERSHIP_TYPES.'.membership_class
      FROM
        '.TABLE_PRODUCER.'
      LEFT JOIN
        '.TABLE_MEMBER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
      LEFT JOIN
        '.TABLE_MEMBERSHIP_TYPES.' ON '.TABLE_MEMBERSHIP_TYPES.'.membership_type_id = '.TABLE_MEMBER.'.membership_type_id
      WHERE
        auth_type LIKE "%producer%"
      ORDER BY
        member_id';
  }
else
  {
    $query = '
      SELECT
        '.TABLE_PRODUCER.'.business_name,
        '.TABLE_MEMBER.'.*,
        '.TABLE_PRODUCER.'.producer_id,
        '.TABLE_MEMBERSHIP_TYPES.'.membership_class
      FROM
        '.TABLE_MEMBER.'
      LEFT JOIN
        '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
      LEFT JOIN
        '.TABLE_MEMBERSHIP_TYPES.' ON '.TABLE_MEMBERSHIP_TYPES.'.membership_type_id = '.TABLE_MEMBER.'.membership_type_id
      '.$wherestatement.'
      ORDER BY
        member_id';
  }
//echo "<pre>$query</pre>";
$sql = mysql_query($query);
while ( $row = mysql_fetch_array($sql) )
  {
    $member_data[] = $row;
  }

// List all the member table fields that can be accessed and give them human-readable names
$field_array = array (
  'member_id' => 'Member ID',
  'pending' => 'Pending?',
  'username' => 'Username',
  'auth_type' => 'Authorization',
  'business_name' => 'Business Name',
  'producer_id' => 'Producer ID',
  'first_name' => 'First Name',
  'last_name' => 'Last Name',
  'first_name_2' => 'First Name 2',
  'last_name_2' => 'Last Name 2',
  'no_postal_mail' => 'Postal Mail?',
  'address_line1' => 'Address',
  'address_line2' => 'Address 2',
  'city' => 'City',
  'state' => 'State',
  'zip' => 'Zip',
  'county' => 'County',
  'work_address_line1' => 'Work Address',
  'work_address_line2' => 'Work Address 2',
  'work_city' => 'Work City',
  'work_state' => 'Work State',
  'work_zip' => 'Work Zip',
  'email_address' => 'E-mail',
  'email_address_2' => 'E-mail 2',
  'home_phone' => 'Home Phone',
  'work_phone' => 'Work Phone',
  'mobile_phone' => 'Cell Phone',
  'toll_free' => 'Toll-Free',
  'fax' => 'FAX',
  'home_page' => 'Home Page',
  'membership_type_id' => 'Membership Type ID',
  'membership_date' => 'Membership Date',
  'last_renewal_date' => 'Last Renewal Date',
  'membership_discontinued' => 'Member Discontinued?',
  'mem_taxexempt' => 'Tax Exempt?',
  'mem_delch_discount' => 'Delivery Discount?',
  'how_heard_id' => 'How Heard ID',
  'notes' => 'Notes'
  );


// Set the selected fields
if (isset ($_POST['action']))
  {
    // Get selections from the posted data
    foreach (array_keys ($field_array) as $key)
      {
        if ($_POST['select_'.$key] == 1)
          {
            $selected_array[$key] = 1;
          }
      }
  }
else
  {
    // Set the default selections
    $selected_array = array (
      'member_id' => 1,
      'username' => 1,
      'business_name' => 1,
      'producer_id' => 1,
      'last_name' => 1,
      'first_name' => 1,
      'last_name_2' => 1,
      'first_name_2' => 1,
      'address_line1' => 1,
      'address_line2' => 1,
      'city' => 1,
      'state' => 1,
      'zip' => 1,
      'email_address' => 1,
      'home_phone' => 1,
      'mobile_phone' => 1,
      'membership_type_id' => 1,
      'membership_date' => 1,
      'last_renewal_date' => 1
      );
  }


// Create the form to select what fields will be shown
$content_list .= '
  <div id="data_select">
    <form action="'.$_SERVER['SCRIPT_NAME'].'" method="post">';
foreach ($field_array as $key => $field)
  {
    $content_list .= '
      <span class="field_select"><input type="checkbox" name="select_'.$key.'" value="1"'.($selected_array[$key] == 1 ? ' checked' : '').'> '.$field.'</span>';
  }
$content_list .= '
    <input class="button" type="submit" name="action" value="Update">
    <input class="button" type="submit" name="action" value="Download as CSV">
    <input type="checkbox" name="p" value="1"'.($_REQUEST['p'] == 1 ? ' checked' : '').'> Select Only Producers</span>
    </form>
  </div>';

if ($_POST['action'] == "Download as CSV") // Send output to spreadsheet
  {
    // Generate the header row
    foreach ($field_array as $key => $field)
      {
        // Check whether to include this column
        if ($selected_array[$key] == 1)
          {
            // Put the names in the first row (there will be an extra trailing comma)
            $first_row .= $field.',';
          }
      }
    $first_row = substr ($first_row, 0, strlen ($first_row) - 1);
    // Generate the content rows
    $search = array('/\n/', '/\r/', '/"/', '/((.*),(.*))/');
    $replace = array(' ', ' ', '"""', '"\1"');
    foreach( $member_data as $data_key => $data_row )
      {
        $row_data = '';
        foreach ($field_array as $key => $field)
          {
            // Check whether to include this column
            if ($selected_array[$key] == 1)
              {
                // Add the data for this column
                $row_data .= preg_replace ($search, $replace, $data_row[$key]).',';
              }
          }
        $spreadsheet_data .= substr ($row_data, 0, strlen ($row_data) - 1)."\n";
      }
    header("Content-type: application/octet-stream"); 
    header("Content-Disposition: attachment; filename=openfood-".date('Y-m-d').".csv"); 
    header("Pragma: no-cache"); 
    header("Expires: 0"); 
    echo $first_row."\n".$spreadsheet_data;
    exit (1);
  }
else // Send output to web page
  {
    if ( $_REQUEST['p'] == 1 )
      {
        $content_list .=  '<h2 align="center">Producer Members</h2>';
      }
    else
      {
        $content_list .=  '<h2 align="center">Members</h2>';
      }
    // Generate the header row
    $content_list .= '
      <table class="member_list">
        <tr>';
    foreach ($field_array as $key => $field)
      {
        // Check whether to include this column
        if ($selected_array[$key] == 1)
          {
            // Put the names in the first row (there will be an extra trailing comma)
            $content_list .= '
              <th class="'.$key.'">'.$field.'</th>';
          }
      }
    $content_list .= '
      </tr>';


    // Generate the content rows
    foreach( $member_data as $data_key => $data_row )
      {
        $content_list .= '
          <tr>';
        foreach ($field_array as $key => $field)
          {
            // Check whether to include this column
            if ($selected_array[$key] == 1)
              {
                // Do some conditional data manipulation
                if ($key == 'auth_type') $data_row[$key] = strtr ($data_row[$key], ',', ' ');
                // Add the data for this column
                $content_list .= '
                  <td class="'.$key.'">'.$data_row[$key].'</td>';
              }
          }
        $content_list .= '
          </tr>';
      }
    $content_list .=  '
      </table>';
  }

$page_specific_css .= '
<style type="text/css">
small {
  font-size:0.9em;
  color:#006;
  font-weight:bold;
  }
#data_select {
  width:100%;
  }
.field_select {
  width:25%;
  text-align:left;
  float:left;
  }
.button {
  margin:auto;
  display:block;
  clear:both;
  }
table.member_list {
  border:1px solid #aaa;
  border-spacing:0;
  border-collapse:collapse;
  }
table.member_list tr th, table.member_list tr td {
  text-align:left;
  border:1px solid #aaa;
  }
table.member_list tr td {
  font-size:80%;
  vertical-align:top;
  }
</style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Spreadsheet of All '.($_REQUEST['p'] == 1 ? 'Producers' : 'Members').'</span>';
$page_title = 'Reports: Spreadsheet of All '.($_REQUEST['p'] == 1 ? 'Producers' : 'Members');
$page_tab = ($_REQUEST['p'] == 1 ? 'producer_admin_panel' : 'member_admin_panel');


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
