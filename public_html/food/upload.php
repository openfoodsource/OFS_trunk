<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin,site_admin');


// store.php3 - by Florian Dittmer <dittmer@gmx.net>
// Example php script to demonstrate the storing of binary files into
// an sql database. More information can be found at http://www.phpbuilder.com/

// producer_admin and site_admin are allowed to pass $_GET directive
if ($_GET['producer_id'] && CurrentMember::auth_type('site_admin,cashier'))
  {
    // Keep the same producer_id value
    $producer_id = $_GET['producer_id'];
  }
elseif ($_SESSION['producer_id_you'])
  {
    $producer_id = $_SESSION['producer_id_you'];
  }
$sqll = '
  SELECT
    '.TABLE_PRODUCER_LOGOS.'.logo_id,
    '.TABLE_PRODUCER.'.business_name
  FROM
    '.TABLE_PRODUCER.'
  LEFT JOIN
    '.TABLE_PRODUCER_LOGOS.' USING(producer_id)
  WHERE
    '.TABLE_PRODUCER.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"';
$rsrl = @mysql_query($sqll, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$num = mysql_numrows($rsrl);
while ($row = mysql_fetch_array($rsrl))
  {
    $logo_id = $row['logo_id'];
    $business_name = $row['business_name'];
  }
if ( $logo_id )
  {
    $display_logo = '
      <img src="getlogos.php?logo_id='.$logo_id.'" width="150" hspace="5" alt="'.$logo_desc.'">';
  }
else
  {
    $display_logo = 'No logo uploaded';
  }
// code that will be executed if the form has been submitted:
if ( $_POST['submit'] )
  {
    // connect to the database
    // (you may have to adjust the hostname,username or password)
    $data = addslashes (fread(fopen($_FILES['form_data']['tmp_name'], "r"), $_FILES['form_data']['size']));
//echo "<pre>"; print_r ($data); echo "</pre>";
    if ( $logo_id )
      {
        $sql = '
          UPDATE
            '.TABLE_PRODUCER_LOGOS.'
          SET
            logo_desc = "'.mysql_real_escape_string ($_POST['form_description']).'",
            bin_data = "'.$data.'",
            filename = "'.mysql_real_escape_string ($_FILES['form_data']['name']).'",
            filesize = "'.mysql_real_escape_string ($_FILES['form_data']['size']).'",
            filetype = "'.mysql_real_escape_string ($_FILES['form_data']['type']).'"
         WHERE
          producer_id = "'.$producer_id.'"';
        $result = mysql_query($sql, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        $display_results .= '
          <div align="center">
            <font color=#3333FF><b>Your logo has been updated</b></font><br><br>
            '.$display_logo.'
            <br>Click here to <a href="edit_producer_info.php?producer_id='.$producer_id.'">return to your editing page</a>.
          </div>';
      }
    else
      {
        $query = '
          INSERT INTO
            '.TABLE_PRODUCER_LOGOS.'
            (
              logo_desc,
              producer_id,
              bin_data,
              filename,
              filesize,
              filetype
            )
          VALUES
            (
              "'.mysql_real_escape_string ($_POST['form_description']).'",
              "'.mysql_real_escape_string ($_POST['producer_id']).'",
              "'.$data.'",
              "'.mysql_real_escape_string ($_FILES['form_data']['name']).'",
              "'.mysql_real_escape_string ($_FILES['form_data']['size']).'",
              "'.mysql_real_escape_string ($_FILES['form_data']['type']).'"
            )';
        $result=mysql_query($query, $connection);
        $logo_id= mysql_insert_id();
        $display_results .= '
          <div align="center">
            <font color=#3333FF><b>Your logo has been uploaded.</b></font><br><br>
            <br>Click here to <a href="edit_producer_info.php?producer_id='.$producer_id.'">return to your editing page to view the logo</a>.
            </div>';
      }
  }
else
  {
    // else show the form to submit new data:
    $display_results .= '
      <div align="center">
        <table width="70%">
          <tr>
            <td align="left">
              <table cellpadding="3" border="0">
                <tr>
                  <td valign="top">
                    <b>Current Logo:</b><br>
                    Use the form below to upload a new logo or replace an old logo.<br><br>
                    <font size="-2">(All logos are displayed at a width of 150 pixels.  For best results, you should scale the image before uploading it.)
                    Images must be no larger than 100Kb and may be .jpg, .gif, .png, or .swf format.</font>
                  </td>
                  <td align="center" bgcolor="#DDDDDD">
                    '.$display_logo.'
                  </td>
                </tr>
              </table>
              <form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'" enctype="multipart/form-data">
              File Description:<br>
              <input type="text" name="form_description"  size="40"> (For example, Bob&#146;s Logo)<br><small>Alternative text that will be shown if the logo is not dislpayed</small>
              <input type="hidden" name="MAX_FILE_SIZE" value="'.(UPLOAD_MAX_FILE_KB * 1024).'">
              <input type="hidden" name="producer_id" value="'.$producer_id.'">
              <br><br>
              File to upload/store in database:<br>
              <input type="file" name="form_data"  size="40">
              <p><input type="submit" name="submit" value="Upload">
              </form>
            </td>
          </tr>
        </table>
      </div>';
  }

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Upload Logo</span>';
$page_title = $business_name.': Upload Logo';
$page_tab = 'producer_panel';


include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$display_results.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
