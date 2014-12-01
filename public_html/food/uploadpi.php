<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');


// store.php3 - by Florian Dittmer <dittmer@gmx.net>
// Example php script to demonstrate the storing of binary files into
// an sql database. More information can be found at http://www.phpbuilder.com/

// Check if auth_type = producer_admin and there is a producer_id provided
if (CurrentMember::auth_type('producer_admin') && $_GET['producer_id'])
  {
    // Keep the same producer_id value
    $producer_id = $_GET['producer_id'];
  }
elseif ($_SESSION['producer_id_you'])
  {
    $producer_id = $_SESSION['producer_id_you'];
  }

// Figure out where we came from and save it so we can go back
if (isset ($_REQUEST['referrer']))
  {
    $referrer = $_REQUEST['referrer'];
  }
else
  {
    $referrer = $_SERVER['HTTP_REFERER'];
  }

$sqll = '
  SELECT
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.image_id,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.TABLE_PRODUCT_IMAGES.'.descrition
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN
    '.TABLE_PRODUCT_IMAGES.'
      ON '.NEW_TABLE_PRODUCTS.'.image_id = '.TABLE_PRODUCT_IMAGES.'.image_id
  WHERE
    '.NEW_TABLE_PRODUCTS.'.product_id = "'.mysql_real_escape_string ($_REQUEST['product_id']).'"';
$rsrl = @mysql_query($sqll, $connection) or die(debug_print ("ERROR: 022967 ", array ($sqll,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num = mysql_numrows($rsrl);
while ( $row = mysql_fetch_array($rsrl) )
  {
    $image_id = $row['image_id'];
    $product_name = $row['product_name'];
    $image_description = $row['descrition'];
  }
if ( $image_id )
  {
    $display_image = '
      <td align="center" bgcolor="#FFFFFF">
        <img src="'.get_image_path_by_id ($image_id).'" width="100" hspace="5" border="1">
      </td>';
  }
else
  {
    $display_image = '<td align="center" bgcolor="#DDDDDD" style="border:1px solid black;">No image</td>';
  }

// code that will be executed if the form has been submitted:

if ( $_REQUEST['submit'] == 'Upload' )
  {
    if ( ! $_FILES['form_data'] )
      {
        $content_upload .= '
          <div align=center>
            <font color=#3333FF><b>To add an image, you"ll need to select an image to upload from your computer<br>
            by clicking the "Browse" button.<br></font></b>
            Return to your <a href="product_list.php?producer_id='.$producer_id.'&a='.$_REQUEST['a'].'">product list</a><br>
            Questions?: <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a><br><br>
          </div>';
      }
    else
      {
        if ($_FILES['form_data']['name'] != '')
          {
            $data = fread(fopen($_FILES['form_data']['tmp_name'], "r"), $_FILES['form_data']['size']);
          }
        if ( $_FILES['form_data']['size'] > 200000 )
          {
            $content_upload .= '
              <div align=center><font color=#3333FF><b>
                Your image is too large. The file size must be less than 200K to <br>
                ensure that webpages load at a reasonable speed for all users.<br></font></b>
                Return to your <a href="product_list.php?producer_id='.$producer_id.'&a='.$_REQUEST['a'].'">product list</a><br>
                Questions?: <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a><br><br>
              </div>';
          }
        else
          {
            if ( $image_id )
              {
                if ($data)
                  {
                    $sql = '
                      UPDATE
                        '.TABLE_PRODUCT_IMAGES.'
                      SET
                        descrition = "'.mysql_real_escape_string ($_REQUEST['image_description']).'",
                        bin_data = "'.mysql_real_escape_string($data).'",
                        file_name = "'.mysql_real_escape_string ($_FILES['form_data']['name']).'",
                        file_size = "'.mysql_real_escape_string ($_FILES['form_data']['size']).'",
                        file_type = "'.mysql_real_escape_string ($_FILES['form_data']['type']).'"
                       WHERE
                        image_id = "'.$image_id.'"';
                  }
                else
                  {
                    $sql = '
                      UPDATE
                        '.TABLE_PRODUCT_IMAGES.'
                      SET
                        descrition = "'.mysql_real_escape_string ($_REQUEST['image_description']).'"
                       WHERE
                        image_id = "'.mysql_real_escape_string ($image_id).'"';
                  }
                $result = mysql_query($sql, $connection) or die(debug_print ("ERROR: 897859 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
                $display_results .= '
                  <div align="center">
                    <font color="#3333FF"><b>Your image has been updated</b></font><br><br>
                    '.$display_image.'
                    <br>Click here to <a href="'.$referrer.'">return to your product list</a>.
                  </div>';
                $content_upload .= $display_results;
              }
            else
              {
                $query = '
                  INSERT INTO
                    '.TABLE_PRODUCT_IMAGES.'
                  (
                    descrition,
                    bin_data,
                    file_name,
                    file_size,
                    file_type
                  )
                  VALUES
                    (
                      "'.mysql_real_escape_string ($_REQUEST['image_description']).'",
                      "'.mysql_real_escape_string ($data).'",
                      "'.mysql_real_escape_string ($_FILES['form_data']['name']).'",
                      "'.mysql_real_escape_string ($_FILES['form_data']['size']).'",
                      "'.mysql_real_escape_string ($_FILES['form_data']['type']).'"
                    )';
                $result=mysql_query($query,$connection) or die(debug_print ("ERROR: 279026 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));

                $image_id= mysql_insert_id();

                $sqlu = '
                  UPDATE
                    '.NEW_TABLE_PRODUCTS.'
                  SET
                    image_id = "'.$image_id.'"
                  WHERE
                    product_id = "'.$_REQUEST['product_id'].'"';
                $resultu = mysql_query($sqlu, $connection) or die(debug_print ("ERROR: 688936 ", array ($sqlu,mysql_error()), basename(__FILE__).' LINE '.__LINE__));

                $display_results .= '
                  <div align="center">
                    <font color="#3333FF"><b>Your image has been uploaded.</b></font><br><br>
                    <img src="'.get_image_path_by_id ($image_id).'" width="100" hspace="5" border="1">
                    <br>Click here to <a href="'.$referrer.'">return to your product list</a>.
                  </div>';
                $content_upload .= $display_results;
              }
          }
      }
  }
elseif( $_REQUEST['submit'] == 'Delete' )
  {
    if ($_REQUEST['confirm'] == 'yes')
      {
        $content_upload .= '
        <div style="text-align:center;">
        <form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="'.$_REQUEST['product_id'].'">
        <input type="hidden" name="producer_id" value="'.$producer_id.'">
        <input type="hidden" name="a" value="'.$_REQUEST['a'].'">
        <input type="hidden" name="referrer" value="'.$referrer.'">
        <img src="'.get_image_path_by_id ($image_id).'" width="100" hspace="5" border="1"><br><br>
        Really delete this image?<br>
        <input type="hidden" name="confirm" value="no">
        <p><input type="submit" name="submit" value="Delete"> &nbsp; <input type="submit" name="submit" value="Keep">
        </form>
        </div>';
      }
    elseif ($_REQUEST['confirm'] == 'no' && $image_id != 0)
      {
        $query = '
          DELETE FROM
            '.TABLE_PRODUCT_IMAGES.'
          WHERE
            image_id = "'.mysql_real_escape_string ($image_id).'"';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 408593 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));

        $query = '
          UPDATE
            '.NEW_TABLE_PRODUCTS.'
          SET
            image_id = "0"
          WHERE
            product_id = "'.mysql_real_escape_string ($_REQUEST['product_id']).'"';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 820596 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $content_upload .= '
          <div align="center">
            <font color="#3333FF"><b>Your image has been deleted.</b></font><br><br>
            <br>Click here to <a href="'.$referrer.'">return to your product list</a>.
          </div>';
      }
  }
else
  {
    // else show the form to submit new data:
    $content_upload .= '
      <div align="center">
      <table width="70%">
        <tr><td align="left">

          <table cellpadding="3" border="0">
            <tr><td valign="top">
            <b>Current Product Image:</b><br>
            To replace or upload an image,<br>use the form below.<br><br>
            Must be .jpg, .gif, or .png format and<br>no larger than 200K.
            </td>
              '.$display_image.'
              </tr>
          </table>

          <form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'" enctype="multipart/form-data">

          <input type="hidden" name="product_id" value="'.$_REQUEST['product_id'].'">
          <input type="hidden" name="producer_id" value="'.$producer_id.'">
          <input type="hidden" name="confirm" value="yes">
          <input type="hidden" name="a" value="'.$_REQUEST['a'].'">
          <input type="hidden" name="referrer" value="'.$referrer.'">
          Short description:<br>
          <input type="text" name="image_description" size="40" value="'.$image_description.'"><br>
          File to upload/store in database:<br>
          <input type="file" name="form_data"  size="40">
          <p><input type="submit" name="submit" value="Upload"> &nbsp; <input type="submit" name="submit" value="Delete">
          </form>
          Questions?: <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a>
        </td></tr>
      </table>
      </div>';
  }


include("func/show_businessname.php");

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Upload an Image for '.$product_name.'</span>';
$page_title = $business_name.': Upload an Image for '.$product_name;
$page_tab = 'producer_panel';


include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$content_upload.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
