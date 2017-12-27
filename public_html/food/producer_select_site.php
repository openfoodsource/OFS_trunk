<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');


$action = $_POST['action'];
$error_array = array ();
$show_group_buttons = false;


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                        BEGIN PROCESSING SUBMITTED PAGE                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

// If this is a producer but NOT an admin, then constrain to that producer's businesses
if (! CurrentMember::auth_type('producer_admin'))
  {
    $where_producer = '
  WHERE member_id = "'.mysqli_real_escape_string($connection, $_SESSION['member_id']).'"';
    $consistency = 'member_id-'.mysqli_real_escape_string($connection, $_SESSION['member_id']);
  }
// Otherwise, this is an admin, so narrow the field to specific producer_id if desired
elseif (isset ($_GET['producer_id']))
  {
    $where_producer = '
  WHERE producer_id = "'.mysqli_real_escape_string($connection, $_GET['producer_id']).'"';
    $consistency = 'producer_id-'.mysqli_real_escape_string($connection, $_GET['producer_id']);
  }
else // This is the full list, so show group buttons
  {
    $show_group_buttons = true;
  }

// Get the list of sites
$query = '
  SELECT
    site_id,
    site_short,
    site_long,
    site_type
  FROM '.NEW_TABLE_SITES.'
  ORDER BY site_short';
$result = mysqli_query ($connection, $query) or die(debug_print ("ERROR: 292342 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$site_short_array = array ();
$site_long_array = array ();
$site_type_array = array ();
while ($row = mysqli_fetch_object ($result))
  {
    $site_short_array[$row->site_id] = $row->site_short;
    $site_long_array[$row->site_id] = $row->site_long;
    $site_type_array[$row->site_id] = $row->site_type;
  }
// Get the list of producers
$query = '
  SELECT
    producer_id,
    business_name
  FROM '.TABLE_PRODUCER.
  $where_producer.'
  ORDER BY business_name';
$result = mysqli_query ($connection, $query) or die(debug_print ("ERROR: 292342 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$producer_name_array = array ();
while ($row = mysqli_fetch_object ($result))
  {
    $producer_name_array[$row->producer_id] = $row->business_name;
  }
// Get the sites/producers that are current associated
$query = '
  SELECT
    *
  FROM '.TABLE_AVAILABILITY;
$result = mysqli_query ($connection, $query) or die(debug_print ("ERROR: 292342 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$availability_array = array ();
while ($row = mysqli_fetch_object ($result))
  {
    $availability_array[$row->producer_id][$row->site_id] = 1;
  }
if ($action == "Update")
  {
    // Check if we are accessing this page with different parameters than previously
    // and abort if there is a discrepancy (otherwise we might inadvertently clear a
    // range of producer values that should remain available)
    if (isset ($_POST['consistency'])
        && $_POST['consistency'] != $consistency)
      {
        array_push ($error_array, 'The producers listed on this page have changed from when your selections were made. Updates have been ignored to preserve the existing data. Terribly sorry, but you will need to re-enter the changes.');
      }
    else
      {
        // Cycle through all producer/site combinations ...
        foreach (array_keys ($producer_name_array) as $producer_id)
          {
            foreach (array_keys ($site_short_array) as $site_id)
              {
                // Test if we have been instructed to turn on this producer/site avaiability
                if (isset ($_POST['select-'.$producer_id.'-'.$site_id])
                    && $_POST['select-'.$producer_id.'-'.$site_id] == 'on')
                  {
                    // If currently 'off', then set it to 'on' ...
                    if (! isset ($availability_array[$producer_id][$site_id]))
                      {
                        $query2 = '
                          INSERT INTO
                            '.TABLE_AVAILABILITY.'
                          SET
                            site_id = "'.mysqli_real_escape_string ($connection, $site_id).'",
                            producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
                        $null = mysqli_query ($connection, $query2) or die(debug_print ("ERROR: 924489 ", array ('row-checked: '=>$row->checked, $query2, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
                        // And correct the availability array
                        $availability_array[$producer_id][$site_id] = 1;
                      }
                  }
                // Otherwise, if it is not already 'off', make it so ...
                elseif (isset ($availability_array[$producer_id][$site_id])
                        && $availability_array[$producer_id][$site_id] == 1)
                  {
                    $query2 = '
                      DELETE FROM
                        '.TABLE_AVAILABILITY.'
                      WHERE
                        site_id = '.mysqli_real_escape_string ($connection, $site_id).'
                        AND producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
                    $null = mysqli_query ($connection, $query2) or die(debug_print ("ERROR: 233561 ", array ($query2, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
                    // And correct the availability array
                    unset ($availability_array[$producer_id][$site_id]);
                  }
              }
          }
      }
  }

// Assemble any errors encountered so far
if (count ($error_array) > 0) $error_message = '
  <div class="error_message open" onmouseover="jQuery(this).removeClass(\'open\')">
    <p class="message">The information was not accepted. Please correct the following problems and resubmit.</p>
    <ul class="error_list">
      <li>'.implode ("</li>\n<li>", $error_array).'</li>
    </ul>
  </div>';
// Always show the form...
$content = $error_message.'
  <form id="producer_select_site" action="'.$_SERVER['SCRIPT_NAME'].(isset ($_GET['producer_id']) ? '?producer_id='.$_GET['producer_id'] : '').(isset ($_GET['display_as']) ? '?display_as='.$_GET['display_as'] : '').'" method="POST">
    <h3>Select Collection Point</h3>
    <p>Use the following form to select a site where products will be brought for connection into the distribution system.
      More than one collection point may be chosen, in which case you will need to route the proper products to each.
      Be sure to update any changes at the bottom of the form.</p>
    <input type="hidden" name="consistency" value="'.$consistency.'">';

$content .= '
    <div id="table_container">
      <table id="producer_site_list">
        <thead>';
foreach (array_keys ($site_short_array) as $site_id)
  {
    $content1 .= '
          <th class="site_name" id="col-'.$site_id.'">
            <div>
              <span class="site_short">'.$site_short_array[$site_id].'</span>
              <span class="site_long">'.$site_long_array[$site_id].'</span>
            </div>
          </th>';
    $content2 .= '
          <th class="site_check">
            <input class="site_check" name="site-'.$site_id.'" id="site-'.$site_id.'" type="checkbox">
          </th>';
  }
$content .= '
          <tr>
            <th class="producer" colspan="2" rowspan="2">Producer</th>'.
            $content1.'
          </tr>'.
          ($show_group_buttons == true ? '
          <tr>'.
            $content2.'
          </tr>'
          : '').'
        </thead>
        <tbody>';

// Cycle through all producer/site combinations ...
//      <td class="site">'.$site_short_array[$site_id].'<span class="detail">'.$site_long_array[$site_id].'</span></td>
foreach (array_keys ($producer_name_array) as $producer_id)
  {
    $business_name = $row->business_name;
    $content .= '
          <tr>
            <td class="producer"><span class="producer_id">'.$producer_id.'</span>'.$producer_name_array[$producer_id].'
            </td>
            <td class="producer_check">'.
              ($show_group_buttons == true ? '
              <input class="producer_check" name="producer-'.$producer_id.'" id="producer-'.$producer_id.'" type="checkbox">'
              : '').'
            </td>
';
    foreach (array_keys ($site_short_array) as $site_id)
      {
        if ($availability_array[$producer_id][$site_id] == 1)
          {
            $select_checked = ' checked';
          }
        else
          {
            $select_checked = '';
          }
        $content .= '
            <td class="producer_site_check"><input type="checkbox" id="select-'.$producer_id.'-'.$site_id.'" name="select-'.$producer_id.'-'.$site_id.'"'.$select_checked.'></td>';
      }
        $content .= '
          </tr>';
  }

$content .= '
        </tbody>
      </table>
    </div>
    <br>
    <div>
      <input type="submit" name="action" value="Update">
      <input type="reset">'.
      ($show_group_buttons == true ? '
      <input value="Set Group" type="button" onclick="adjust_producer_site_checks (true);">
      <input value="Clear Group" type="button" onclick="adjust_producer_site_checks (false);">'
      : '').'
    </div>
  </form>';

$page_specific_css = '
  <style type="text/css">
  #producer_select_site {
    text-align:center;
    display:inline-block;
    }
  #producer_site_list thead {
    display:block;
    }
  #producer_site_list tbody {
    display:block;
    max-height:40rem;
    overflow:scroll;
    }
  #producer_site_list {
    border-right:1px solid #888;
    border-bottom:1px solid #888;
    border-spacing:0;
    border-collapse:separate;
    font-size:80%;
    box-sizing: border-box;
    }
  #producer_site_list th.producer {
    vertical-align:bottom;
    height:16rem;
    max-height:16rem;
    width:33rem;
    max-width:33rem;
    padding:5px;
    }
  #producer_site_list th.site_name {
    height:14rem;
    white-space:nowrap;
    border-left:1px solid;
    width:3rem;
    max-width:3rem;
    }
  #producer_site_list th.site_check {
    border-top:1px solid;
    border-left:1px solid;
    text-align:center;
    width:3rem;
    max-width:3rem;
    }
  #producer_site_list th.site_name > div {
    transform: translate(0, 5.5rem) rotate(-90deg);
    width:3rem;
    height:3rem;
    }
  #producer_site_list th.site_name > div > span {
    padding:2px;
    display:block;
    text-align:left;
    width:12rem;
    max-width:12rem;
    overflow:hidden;
    }
  #producer_site_list th.site_name > div > span.site_short {
    display:inline-block;
    font-size:1rem;
    }
  #producer_site_list th.site_name > div > span.site_long {
    font-size:0.6rem;
    }
  #producer_site_list td.producer {
    width:30rem;
    max-width:30rem;
    }
  #producer_site_list tr th {
    color:#ffc;
    background-color:rgba(40, 80, 60, 0.5);
    padding:2px;
     }
  #producer_site_list tr td {
    background-color:rgba(200, 200, 200, 0.5);
    border-top:1px solid #888;
    border-left:1px solid #888;
    color:#444;
    padding:2px;
    }
  #producer_site_list td.producer .producer_id {
    display:inline-block;
    width:4rem;
    text-align:right;
    padding-right:1rem;
    }
  #producer_site_list td.producer .producer_id:before {
    content:"#";
    }
  #producer_site_list td.producer,
  #producer_site_list td.site {
    text-align:left;
    }
  #producer_site_list td.producer_check,
  #producer_site_list td.producer_site_check {
    text-align:center;
    width:3rem;
    max-width:3rem;
    }
  </style>
  ';

$page_specific_javascript .= '
<script type="text/javascript">
function adjust_producer_site_checks (true_false) {
  $(".producer_check:checkbox:checked").each(function () {
    var producer_checked = (this.checked ? $(this).prop("name").substr(9) : ""); // Get the numeric part of... e.g. "producer-23"
    // alert (producer_checked+" is checked.");
    $(".site_check:checkbox:checked").each(function () {
      var site_checked = (this.checked ? $(this).prop("name").substr(5) : ""); // Get the numeric part of... e.g. "site-6"
      // alert ("site:"+site_checked+" and producer:"+producer_checked+" is checked.");
      // Now adjust the producer_site checkboxes
      $("#select-"+producer_checked+"-"+site_checked).prop("checked", true_false);
      });
    });
  }
</script>';

if ($_GET['display_as'] == 'popup') $display_as_popup = true;

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Select Collection Point</span>';
$page_title = ''.$business_name.': Select Collection Point';
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");?>
