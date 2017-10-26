<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');


$action = $_POST['action'];
$content .= '<div style="padding:1em;">';


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                        BEGIN PROCESSING SUBMITTED PAGE                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

if ($action == "Update")
  {

    // Update the sites to turn them on/off.
    $query = '
      SELECT
        site_id,
        COALESCE (foo.producer_id, 0) AS checked
      FROM
        '.NEW_TABLE_SITES.'
      LEFT JOIN
        ( SELECT *
        FROM '.TABLE_AVAILABILITY.'
        WHERE producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'") foo USING(site_id)';
      // This query is NOT constrained to site_type="producers" so it will be able to clear
      // any sites that might have been changed to site_type="customer"
    $result = mysqli_query($connection, $query) or die (debug_print ("ERROR: 217784 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_object ($result))
      {
        // Check if we need to clear a row 
        if ($row->checked != 0 AND ! isset($_POST['select_'.$row->site_id]))
          {
            $query2 = '
              DELETE FROM
                '.TABLE_AVAILABILITY.'
              WHERE
                site_id = '.mysqli_real_escape_string ($connection, $row->site_id).'
                AND producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'"';
            $null = mysqli_query ($connection, $query2) or die (debug_print ("ERROR: 829389 ", array ($query2, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }

        // Check if we need to clear a row 
        if ($row->checked == 0 AND isset($_POST['select_'.$row->site_id]))
          {
            $query2 = '
              INSERT INTO
                '.TABLE_AVAILABILITY.'
              SET
                site_id = '.mysqli_real_escape_string ($connection, $row->site_id).',
                producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'"';
            $null = mysqli_query ($connection, $query2) or die (debug_print ("ERROR: 797841 ", array ($query2, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
      }
  }

// Always show the form...
$content .= '
  <div align="center"><h3>Select Collection Point</h3></div>
  <p>Use the following form to select a site where products will be brought for connection into the distribution system.
  More than one collection point may be chosen, in which case you will need to route the proper products to each.
  Be sure to update any changes at the bottom of the form.</p>
  <form action="'.$_SERVER['SCRIPT_NAME'].'" method="POST">';

$query = '
  SELECT
    new_sites.*,
    '.TABLE_PRODUCER.'.business_name,
    COALESCE (foo.producer_id, 0) AS checked
  FROM
    '.NEW_TABLE_SITES.'
  LEFT JOIN
    ( SELECT *
    FROM '.TABLE_AVAILABILITY.'
    WHERE producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'") foo USING(site_id)
  LEFT JOIN
    '.TABLE_PRODUCER.' ON('.TABLE_PRODUCER.'.producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'")
  WHERE
    site_type LIKE "%producer%"
  ORDER BY
    site_short;';
$result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 729478 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$content .= '
    <table id="producer_site_list">
      <tr>
        <th>Select</th>
        <th>Site ID</th>
        <th>Site Name</th>
        <th>Product Types</th>
      </tr>';
while ($row = mysqli_fetch_object ($result))
  {
    $business_name = $row->business_name;
    if ($row->checked != 0)
      {
        $select_checked = ' checked';
      }
    else
      {
        $select_checked = '';
      }
    $content .= '
      <tr id="row_'.$row->site_id.'" class="'.$select_checked.'">
        <td><input type="checkbox" name="select_'.$row->site_id.'"'.$select_checked.' onclick="jQuery(\'#row_'.$row->site_id.'\').toggleClass(\'checked\');"></td>
        <td>'.$row->site_short.'</td>
        <td>'.$row->site_long.'</td>
        <td>'.$row->site_description.'</td>
      </tr>';
  }

$content .= '
  </table>
  <br>
  <table border="0" width="100%">
    <tr>
      <td width="33%" align="center"><input type="submit" name="action" value="Update"></td>
      <td width="33%" align="center"><input type="reset"></td>
    </tr>
  </table>
  </form>
  <hr>';

$content .= '</div>';

$page_specific_css = '
  <style type="text/css">
  #producer_site_list {
    border:1px;
    background-color:#eee;
    border:1px solid black;
    border-collapse:collapse;
    font-size:80%;
    }
  #producer_site_list tr.checked td {
    background-color:#ffb;
    color:#000;
    }
  #producer_site_list tr th {
    color:#ffc;
    background-color:#468;
    padding:2px;
     }
  #producer_site_list tr td {
    border:1px solid #aaa;
    color:#666;
    padding:2px;
    }
  </style>
  ';

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
