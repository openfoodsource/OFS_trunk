<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin,member_admin');

// Did we receive a request for member search?
if ($_GET['action'] == 'Search' &&
    strlen ($_GET['member_query']) > 0)
  {
    $query = '
      SELECT
        '.TABLE_MEMBER.'.member_id,
        first_name,
        last_name,
        first_name_2,
        last_name_2,
        preferred_name,
        username,
        '.TABLE_MEMBER.'.business_name AS member_business_name,
        email_address,
        email_address_2,
        membership_discontinued,
        '.TABLE_PRODUCER.'.business_name AS producer_business_name,
        producer_id
      FROM
        '.TABLE_MEMBER.'
      LEFT JOIN '.TABLE_PRODUCER.' USING(member_id)
      WHERE
        first_name LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR last_name LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR first_name_2 LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR last_name_2 LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR preferred_name LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR username LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR '.TABLE_MEMBER.'.business_name LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR email_address LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR email_address_2 LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
        OR '.TABLE_MEMBER.'.member_id = "'.mysqli_real_escape_string ($connection, $_GET['member_query']).'"
        OR '.TABLE_PRODUCER.'.business_name LIKE "%'.mysqli_real_escape_string ($connection, $_GET['member_query']).'%"
      ORDER BY
        member_id,
        producer_id';
    $result = mysqli_query ($connection, $query)  or die (debug_print ("ERROR: 754930 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $rows = @mysqli_num_rows ($result);
    if ($rows > 0)
      {
        $content_member_list .= '
          <fieldset class="lookup_result">
            <legend>Members Matching &ldquo;'.$_GET['member_query'].'&rdquo;</legend>
            <div class="result_data">
              <div class="result_header">
                <div class="header result member_id">ID</div>
                <div class="header result member_name">Assoc. Names</div>
                <div class="header result email">Email</div>
                <div class="header result username">Username</div>
                <div class="header result links">Action</div>
              </div>';
        while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
          {
            if ($row['membership_discontinued'] > 0)
              $discontinued_class = ' discontinued';
            else
              $discontinued_class = '';

            // Assemble a producer list and add to it until we move to a new member_id
            if ($row['member_id'] != $member_id_prior)
              {
                // Not the same member as before, so append the member_list_temporary
                $content_member_list .= $member_list_temporary;
                // Clear the old producer_info_line and start a new one
                $producer_info_temporary = '';
                if (strlen ($row['producer_id']) > 0)
                  {
                    $producer_info_temporary = '
                      <li class="producer" onclick="popup_src(\'edit_producer.php?action=edit&producer_id='.$row['producer_id'].'&display_as=popup\', \'edit_producer\', \'\')">('.$row['producer_id'].') '.$row['producer_business_name'].'</li>';
                  }
              }
            // Otherwise this is the same member as before so continue with the additional producer_info_line
            else
              {
                if (strlen ($row['producer_id']) > 0)
                  {
                    // Add to the producer_info_temporary and recreate the member_list_temporary
                    $producer_info_temporary .= '
                      <li class="producer" onclick="popup_src(\'edit_producer.php?action=edit&producer_id='.$row['producer_id'].'&display_as=popup\', \'edit_producer\', \'\')">('.$row['producer_id'].') '.$row['producer_business_name'].'</li>';
                  }
              }
            $member_list_temporary = '
              <div class="result_row'.$discontinued_class.'">
                <div class="result member_id">'.$row['member_id'].'</div>
                <div class="result member_name">
                  <span style="color:#888;font-size:70%;">'.$row['first_name'].' '.$row['last_name'].' &nbsp; '.$row['first_name_2'].' '.$row['last_name_2'].'</span><br />
                  '.$row['preferred_name'].'<br />
                  <span class="member_business_name">'.$row['member_business_name'].
                  (strlen ($producer_info_temporary) > 0 ? '<ul class="producer_list">'.$producer_info_temporary.'</ul>' : '').'
                  </div>
                <div class="result email"><a href="mailto:'.$row['email_address'].'">'.$row['email_address'].'</a>'.(strlen ($row['email_address_2']) > 0 ? '<br><a href="mailto:'.$row['email_address_2'].'">'.$row['email_address_2'].'</a>' : '').'</div>
                <div class="result username">'.$row['username'].'</div>
                <div class="result links">
                  <a class="popup" onclick="popup_src(\'edit_member.php?action=edit&member_id='.$row['member_id'].'&display_as=popup\', \'edit_member\', \'\')">Edit</a>
                  &nbsp;/&nbsp;
                  <a class="popup" onclick="popup_src(\'member_information.php?member_id='.$row['member_id'].'&display_as=popup\', \'member_information\', \'\')">View</a>
                </div>
              </div>';
            $member_id_prior = $row['member_id'];
          }
        // Now we're done, so append the last member_list_temporary segment
        $content_member_list .= $member_list_temporary;
        $content_member_list .= '
            </div>
          </fieldset>';
      }
  }

$content_members .= '
<form name="member_lookup" id="member_lookup" class="lookup_form" method="GET" action="'.$_SERVER['SCRIPT_NAME'].'">
  <fieldset class="lookup">
    <legend>Lookup Members</legend>
    <div class="instructions">
      <p>Use any part of the following values to help lookup members:</p>
      <ul class="lookup_options">
        <li>First name</li>
        <li>Last name</li>
        <li>Preferred name</li>
        <li>Business name</li>
        <li>Username</li>
        <li>Member number</li>
        <li>Email address</li>
        <li>Producer name</li>
    </ul>
    </div>
    <input id="load_target" name="member_query" type="text" value="'.$_GET['member_query'].'">
    <input type="submit" name="action" value="Search">
  </fieldset>
</form>
';

$page_title_html = '<span class="title">Membership Information</span>';
$page_subtitle_html = '<span class="subtitle">Find/Edit Members</span>';
$page_title = 'Membership Information: Find/Edit Members';
$page_tab = 'member_admin_panel';

$page_specific_css = '
  legend {
    margin:0 5px;
    padding:2px 5px;
    font-weight:bold;
    color:#040;
    }
  fieldset {
    margin:1em auto;
    border: 1px solid #060;
    border-radius:5px;
    background-color: #fff;
    }
  fieldset.lookup input {
    font-size:12px;
    padding:6px 8px;
    line-height:1.2;
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
  fieldset.lookup input:hover {
    border:1px solid #040;
    background:none;
    background-color:#cdc;
    color:#040;
    }
  fieldset.lookup input:active {
    font-size:12px;
    padding:6px 8px;
    line-height:1;
    margin:2px;
    border-width:0;
    border:1px solid #600;
    background:none;
    background-color:#dda;
    color:#600;
    }
  fieldset.lookup {
    width: 40%;
    min-width:200px;
    }
  div.header,
  div.result {
    display:table-cell;
    padding:0 3px;
    border-bottom:1px solid #aaa;
    }
  .result_data {
    display: table;
    width:97%;
    margin:0 auto;
    }
  div.result_header,
  div.result_row {
    display:table-row;
    }
  div.result_row:hover {
    background-color:#dec;
    }
  div.header {
    font-weight:bold;
    text-align:center;
    background-color:#eeb;
    border-top:1px solid #888;
    border-bottom:1px solid #888;
    }
  div.member_id {
    border-left:1px solid #aaa;
    border-right:1px solid #aaa;
    }
  div.member_name {
    border-right:1px solid #aaa;
    }
  div.email {
    font-style:italic;
    border-right:1px solid #aaa;
    }
  div.username {
    border-right:1px solid #aaa;
    }
  div.links {
    text-align:center;
    border-right:1px solid #aaa;
    }
  .instructions p {
    margin:3px;
    }
  .lookup_options {
    font-size:80%;
    text-align:center;
    padding:0 2em;
    }
  .lookup_options li {
    display:inline;
    }
  .lookup_options li + li:before {
    content:"\20\2022\20";
    }
  .discontinued,
  .discontinued a {
    text-decoration: line-through;
    color:#ccc;
    }

$page_specific_javascript = '';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_members.'
  '.$content_member_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
