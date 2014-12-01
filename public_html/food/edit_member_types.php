<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,member_admin');


if (!$_GET['content']) $_GET['content'] = 'members';

// Get the membership_types array and prepare the membership_types table update form (in case needed)
$query = '
  SELECT
    *
  FROM
    '.TABLE_MEMBERSHIP_TYPES;
$sql = @mysql_query($query, $connection) or die("Couldn't execute query 6.");
$membership_types_array = array ();
$membership_type_ids = array ();

while ( $row = mysql_fetch_object($sql) )
  {
    $membership_types_array[$row->membership_type_id]['initial_cost'] = $row->initial_cost;
    $membership_types_array[$row->membership_type_id]['order_cost'] = $row->order_cost;
    $membership_types_array[$row->membership_type_id]['order_cost_type'] = $row->order_cost_type;
    $membership_types_array[$row->membership_type_id]['revert_cost'] = $row->revert_cost;
    $membership_types_array[$row->membership_type_id]['revert_to_type_id'] = $row->revert_to_type_id;
    $membership_types_array[$row->membership_type_id]['membership_class'] = $row->membership_class;
    $membership_types_array[$row->membership_type_id]['membership_description'] = $row->membership_description;
    $membership_types_array[$row->membership_type_id]['pending'] = $row->pending;

    array_push ($membership_type_ids, $row->membership_type_id);

    if ($row->order_cost_type == 'percent')
      {
        $order_cost_display = number_format ($row->initial_cost, 2).'&nbsp;%';
        $form_order_cost_unit = '';
        $cost_type_fixed = '';
        $cost_type_percent = ' selected';
      }
    elseif ($row->order_cost_type == 'fixed')
      {
        $order_cost_display = '$&nbsp;'.number_format ($row->initial_cost, 2);
        $form_order_cost_unit = '$&nbsp;';
        $cost_type_fixed = ' selected';
        $cost_type_percent = '';
      }

    $form_cost_type_display = '
      <select name="order_cost_type['.$row->membership_type_id.']">
        <option'.$cost_type_fixed.' value="fixed">fixed</option>
        <option'.$cost_type_percent.' value="percent">percent</option>
      </select>';

    // Display a form for changing the membership_types values
    $membership_types_display .= '
      <div>
        <fieldset>
          <legend><span id="legendspan'.$row->membership_type_id.'" class="edit" onClick=\'toggle_view('.$row->membership_type_id.')\'>[EDIT]</span></legend>
          <div id="text'.$row->membership_type_id.'">
            <span class="membership_class">'.$row->membership_class.'</span>
            <span class="membership_description">'.$row->membership_description.'</span><br>
            <span class="initial_cost">Initial Cost: $&nbsp;'.number_format ($row->initial_cost, 2).'</span><br>
            <span class="order_cost">Cost for each order: '.$order_cost_display.'</span><br>
            <span class="revert_cost">Cost each year: $&nbsp;'.number_format ($row->revert_cost, 2).'</span><br>
            <span class="revert_to_type_id">When expired, revert to: '.$row->revert_to_type_id.'</span><br>
            <span class="pending">Begin as pending? '.$row->pending.'</span><br>
          </div>
          <div id="form'.$row->membership_type_id.'" style="display:none;">
            Short Description: <input type="text" name="membership_class['.$row->membership_type_id.']" size="20" maxlen="30" value="'.$row->membership_class.'"><br>
            Detailed Description: <input type="text" name="membership_description['.$row->membership_type_id.']" size="50" maxlen="255" value="'.$row->membership_description.'"><br>
            Initial Cost: $&nbsp;<input type="text" name="initial_cost['.$row->membership_type_id.']" size="5" value="'.number_format($row->initial_cost, 2).'"><br>
            Order Cost: '.$form_order_cost_unit.'<input type="text" name="order_cost['.$row->membership_type_id.']" size="5" value="'.number_format($row->order_cost, 2).'"> '.$form_cost_type_display.'<br>
            Annual Cost: $&nbsp;<input type="text" name="revert_cost['.$row->membership_type_id.']" size="5" value="'.number_format($row->revert_cost, 2).'"><br>
            On Expiration, Revert to Type: <input type="text" name="revert_to_type_id['.$row->membership_type_id.']" size="5" value="'.$row->revert_to_type_id.'"><br>
            Pending? <input type="text" name="pending['.$row->membership_type_id.']" size="5" value="'.$row->pending.'"><br>
          </div>
        </fieldset>
      </div>
      ';
  }

if ($_GET['content'] == 'membership_types')
  {
    // Send the membership_types display and form
    $page_specific_javascript .= '
      <script type="text/javascript">
        var edit_count = 0;
        function toggle_view(type_id)
          {
            if (document.getElementById("text"+type_id).style.display == "none")
              {
                document.getElementById("text"+type_id).style.display = "";
                document.getElementById("form"+type_id).style.display = "none";
                document.getElementById("legendspan"+type_id).innerHTML = "[EDIT -- values might have been changed]";
                edit_count --;
              }
            else
              {
                document.getElementById("text"+type_id).style.display = "none";
                document.getElementById("form"+type_id).style.display = "";
                document.getElementById("legendspan"+type_id).innerHTML = "[HIDE FORM]";
                edit_count ++;
              }
            if (edit_count > 0)
              {
                document.getElementById("form_submit").style.display = "";
              }
            else
              {
                document.getElementById("form_submit").style.display = "none";
              }
          }
      </script>';
    $page_specific_css .= '
      <style type="text/css">
        .edit {font-size:80%; color:#048;}
        .membership_class {text-decoration:underline;font-size:120%;}
        .membership_description {color:#aaa;}
        #form_submit {margin-left:auto;margin-right:auto;}
      </style>';
    $content_types .= '
      <form method="post" action="'.$_SERVER['SCRIPT_NAME'].'">
        '.$membership_types_display.'
        <input id="form_submit" type="submit" name="action" value="Update ALL Membership Type Changes" style="display:none;">
      </form>';
  }






// If not using jquery and doing an update, then get the posted info and do it...
if ($_GET['content'] == 'members' && $_POST['update'] == 'Make changes')
  {
    foreach (array_keys ($_POST['membership_type_id']) as $member_id)
      {
        if ($_POST['membership_type_id'][$member_id] != $_POST['old_membership_type_id'][$member_id])
          {
            $query = '
              UPDATE
                '.TABLE_MEMBER.'
              SET
                membership_type_id = "'.mysql_real_escape_string ($_POST['membership_type_id'][$member_id]).'"
              WHERE
                member_id = "'.$member_id.'"';
            $result= mysql_query($query) or die("Error: " . mysql_error());
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
    if (strpos ('|member_id|business_name|last_name|username|membership_type_id|', "|$sort|") === false)
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
      <th><a href="'.$_SERVER['SCRIPT_NAME'].'?content=members&page='.$page.'&sort=membership_type_id">Membership Type'.$sort_by['membership_type_id'].'</a></th>';
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
        auth_type,
        membership_type_id
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
              <select name="membership_type_id['.$row->member_id.']" onchange="update_member_info(\''.$row->member_id.'\',\'membership_type_id\',this.options[this.selectedIndex].value);">';
            foreach ($membership_type_ids as $membership_type_id)
              {
                $type_check = '';
                if ($row->membership_type_id == $membership_type_id)
                  {
                    $type_check = ' selected';
                  }
                $member_form .= '
            <option'.$type_check.' value="'.$membership_type_id.'">'.$membership_types_array[$membership_type_id]['membership_class'].'</option>';
              }
            $member_form .= '
              </select>
              <input type="hidden" name="old_membership_type_id['.$row->member_id.']" value="'.$row->membership_type_id.'">';
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
        .this_page {display:block;background-color:#fed;padding:2px 15px;float:left;margin-bottom:5px;border:1px solid #999;border-left:0;}
        .page_link_text {display:block;padding:2px 15px;float:left;margin-bottom:5px;border-top:1px solid #999;border:1px solid #999;}
        table {border:1px solid #999;}
        tr.data:hover {background-color:#ddd;}
        td.changed {background-color:#444;}
        #submit_button {text-align:center;margin-top:1em;}
      </style>';

    $page_specific_javascript = '
      <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
      <script type="text/javascript">
        function update_member_info(member_id, field_name, new_value)
          {
            $.post("'.PATH.'ajax/update_member_info.php", { query_data: ""+member_id+":"+field_name+":"+new_value }, function(data) {
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
        $(document).ready(function() {
          $("#submit_button").css("visibility","hidden");
          });
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
$page_subtitle_html = '<span class="subtitle">Modify Membership Types</span>';
$page_title = 'Membership Information: Modify Membership Types';
$page_tab = 'member_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_types.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

