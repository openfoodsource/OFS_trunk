<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');

if (! $_GET['content']) $_GET['content'] = 'members';

// Get an array of all member columns
$query = '
  SHOW COLUMNS FROM
    '.TABLE_MEMBER.'
  LIKE "auth_type"';
$result= mysql_query($query) or die("Error: " . mysql_error());
while ($row = mysql_fetch_object($result))
  // Get an array of all available auth_types
  {
    // $row->Type will give something like this:
    // set('member','producer','route_admin','cashier','member_admin','site_admin')
    // so substr ($row->Type, 5, 2) removes the first five and last two characters
    // leaving the split to operate on the intermediate ',' strings.
    $auth_types_array = array ();
    $auth_types_array = explode ("','", substr ($row->Type, 5, -2));
  }



// If not using jquery and doing an update, then get the posted info and do it...
if ($_GET['content'] == 'members' && $_POST['update'] == 'Make changes')
  {
    foreach (array_keys ($_POST['auth_type']) as $member_id)
      {
        if ($_POST['auth_type'][$member_id] != $_POST['old_auth_type'][$member_id])
          {
            $query = '
              UPDATE
                '.TABLE_MEMBER.'
              SET
                auth_type = "'.mysql_real_escape_string ($_POST['auth_type'][$member_id]).'"
              WHERE
                member_id = "'.$member_id.'"';
// echo "<pre>$query</pre>";
//             $result= mysql_query($query) or die("Error: " . mysql_error());
          }
      }
  }

// Display the member table and current values with forms for jquery (ajax) or manual (post) update...
if ($_GET['content'] == 'members')
  {
    if ($_GET['page'] > 1)
      {
        $page = floor($_GET['page']);
      }
    else
      {
        $page = 1;
      }
    $sort = $_GET['sort'];
    // If the sort is not a valid value, default it to "member_id"
    if (strpos ('|member_id|business_name|last_name|username|auth_type|', "|$sort|") === false)
      {
        $sort = 'member_id';
      }
    $sort_by[$sort] = '&nbsp;(&#8593;)';
    $query = '
      SELECT
        COUNT(member_id) AS number_of_members
      FROM
        '.TABLE_MEMBER;
    $sql = @mysql_query($query, $connection) or die("Couldn't execute query 6.");
    $row = mysql_fetch_object($sql);
    $number_of_pages = ceil ($row->number_of_members / PER_PAGE);

    for ( $page_number = 1; $page_number <= $number_of_pages; $page_number ++ )
      {
        if ($page_number == $page)
          {
            $page_links .= '
              <span class="this_page">'.($page_number).'</span> ';
          }
        else
          {
            $page_links .= '
              <a class="page_link" href="'.$_SERVER['SCRIPT_NAME'].'?content=members&page='.$page_number.'&sort='.$sort.'">'.$page_number.'</a> ';
            }
      }

    $member_form .= '
      <th><a href="'.$_SERVER['SCRIPT_NAME'].'?content=members&page='.$page.'&sort=member_id">ID'.$sort_by['member_id'].'</a></th>
      <th><a href="'.$_SERVER['SCRIPT_NAME'].'?content=members&page='.$page.'&sort=last_name">Name'.$sort_by['last_name'].'</a> / <a href="'.$_SERVER['SCRIPT_NAME'].'?content=members&page='.$page.'&sort=business_name">Business'.$sort_by['business_name'].'</a></th>
      <th><a href="'.$_SERVER['SCRIPT_NAME'].'?content=members&page='.$page.'&sort=username">Username'.$sort_by['username'].'</a></th>
      <th><a href="'.$_SERVER['SCRIPT_NAME'].'?content=members&page='.$page.'&sort=auth_type">Membership Type'.$sort_by['auth_type'].'</a></th>';
    $query = '
      SELECT
        member_id,
        pending,
        username,
        first_name,
        last_name,
        first_name_2,
        last_name_2,
        business_name,
        preferred_name,
        auth_type
      FROM
        '.TABLE_MEMBER.'
      ORDER BY
        '.mysql_real_escape_string ($sort).'
      LIMIT
        '.(($page - 1) * PER_PAGE).', '.PER_PAGE;
    $sql = @mysql_query($query, $connection) or die("Couldn't execute query 6.");

    while ( $row = mysql_fetch_object($sql) )
      {
        $class_pending = '';
        $pending_statement = '';
        if ($row->pending == '1')
          {
            $class_pending = ' pending';
            $pending_statement = '<br>[PENDING]';
          }
        $class_producer = '';
        $producer_statement = '';
        if (in_array ('producer', explode (',', $row->auth_type)))
          {
            $class_producer = ' producer';
            $producer_statement = '[PRODUCER] ';
          }
        $first_name = $row->first_name;
        $last_name = $row->last_name;
        $first_name_2 = $row->first_name_2;
        $first_name_2 = $row->first_name_2;
        $business_name = $row->business_name;
        $preferred_name = $row->preferred_name;
        $member_form .= '
          <tr class="data">
            <td class="member_id'.$class_pending.$class_producer.'">'.$row->member_id.'</td>
            <td class="name'.$class_pending.$class_producer.'">'.$producer_statement.$preferred_name.'</td>
            <td class="username'.$class_pending.$class_producer.'">'.$row->username.$pending_statement.'</td>
            <td id="row['.$row->member_id.']" class="radio'.$class_pending.$class_producer.'">';
        if ($_SESSION['member_id'] == $row->member_id)
          {
            $member_form .= '
              SELF: Modification is not permitted';
          }
        else
          {
            $member_form .= '
              <select multiple="multiple" size="5" id="auth_type['.$row->member_id.']" onClick="update_data(\''.$row->member_id.'\')";">';
            $element_count = 0;
            foreach ($auth_types_array as $auth_type)
              {
                $type_check = '';
                if (in_array ($auth_type, explode (',', $row->auth_type)))
                  {
                    $type_check = ' selected';
                  }
                $member_form .= '
                <option'.$type_check.' value="'.$auth_type.'">'.$auth_type.'</option>';
              }
            $element_count = 0;
            $member_form .= '
              </select>
              <input type="hidden" name="old_auth_type['.$row->member_id.']" value="'.$row->auth_type.'">';
          }
        $member_form .= '
            </td>
          </tr>';
      }

    $page_specific_css = '
      <style type="text/css">
        body {font-family:verdana,arial,sans-serif;}
        td.radio {text-align:center;border-left:1px solid #999;border-top:1px solid #999;}
        th {text-align:center;border-left:1px solid #999;font-size:90%;padding:3px;color:#ffc;}
        th a {color:#ffc;text-decoration:none;}
        th a:hover {text-decoration:underline;}
        td.member_id {border-top:1px solid #999;padding:0 5px;font-size:80%;vertical-align:top;width:10%;}
        td.name {border-top:1px solid #999;border-left:1px solid #999;padding:0 5px;font-size:80%;vertical-align:top;width:40%;}
        td.username {border-top:1px solid #999;border-left:1px solid #999;padding:0 5px;font-size:80%;vertical-align:top;width:20%;}
        td.radio {padding:5px;width:30%;}
        .pending {background-color:#eee;}
        .producer {color:#444;}
        .radio select {font-size:80%;}
        .page_link {display:block;background-color:#ffe;padding:2px 15px;float:left;margin-bottom:5px;border:1px solid #999;border-left:0;}
        .page_link:hover {background-color:#dcb;}
        caption .this_page {display:block;background-color:#fed;padding:2px 15px;float:left;margin-bottom:5px;border:1px solid #999;border-left:0;}
        .page_link_text {display:block;padding:2px 15px;float:left;margin-bottom:5px;border-top:1px solid #999;border:1px solid #999;}
        table {border:1px solid #999;}
        tr.data:hover {background-color:#ddd;}
        td.changed {background-color:#444;}
        #submit_button {text-align:center;margin-top:1em;}
      </style>';

    $element_count = 0;
    foreach ($auth_types_array as $auth_type)
      {
        // Set up the javascript submit function
        $javascript_update_function .= '
          if (document.getElementById("auth_type["+member_id+"]").options['.$element_count.'].selected) {
            new_data = new_data+"'.$auth_type.',"
            }';
        $element_count++;
      }

    $page_specific_javascript = '
      <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
      <script type="text/javascript">
        function update_member_info(member_id, field_name, new_value)
          {
            jQuery.post("'.PATH.'ajax/update_member_info.php", { query_data: ""+member_id+":"+field_name+":"+new_value }, function(data) {
                if (data == "Unauthorizied access") {
                  alert ("Your session has timed out or you are not authorized to perform this operation");
                  }
                else if (data == "Invalid field") {
                  alert ("["+field_name+"] is not a valid field in the members table");
                  }
                else if (data.substring(0,13) == "Changed value") {
                  document.getElementById("row["+member_id+"]").className = "radio changed";
                  //alert ("Changed from "+data.substring(14));
                  }
                else if (data == "Not changed") {
                  };
              });
          }
        // Hide the regular "submit" button after loading the page iff JQuery is up and running
        jQuery(document).ready(function() {
          jQuery("#submit_button").css("visibility","hidden");
          });
        function update_data(member_id) {
          var new_data = "";
          '.$javascript_update_function.'
          update_member_info(member_id, "auth_type", new_data);
          // alert (new_data.substring(0, -1));
          }
      </script>';

    $content_types .= '
      <form name="member_list" method="post" action="'.$_SERVER['SCRIPT_NAME'].'?content=members&page='.$page.'&sort='.$sort.'">
      <table border="0" cellpadding="0" cellspacing="0" align="center" width="90%">
      <caption><span class="page_link_text">Go to page... </span>'.$page_links.'</caption>
        '.$member_form.'
      </table>
      <div id="submit_button"><input type="submit" name= "update" value="Make changes">
      <input type="reset" name= "reset" value="Reset values"></div>
      </form>';
  }

$page_title_html = '<span class="title">Membership Information</span>';
$page_subtitle_html = '<span class="subtitle">Modify Auth Types</span>';
$page_title = 'Membership Information: Modify Auth Types';
$page_tab = 'member_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_types.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

