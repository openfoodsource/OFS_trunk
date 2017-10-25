<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');

// debug_print ("INFO: 555555 ", array('TIME'=>date('H:i:s', time ()), 'SERVER'=>$_SERVER, 'POST'=>$_POST, 'GET'=>$_GET, 'FILES'=>$_FILES));
// sleep (3);

$image_index = 0;

// Compile data for receiving the image
// action
if (isset ($_GET['action'])) $action = mysqli_real_escape_string ($connection, $_GET['action']);
// image_id
if (isset ($_GET['image_id'])) $image_id = mysqli_real_escape_string ($connection, $_GET['image_id']);
// producer_id
if (isset ($_SESSION['producer_id_you'])) $producer_id_you = mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']);
else die(debug_print ("ERROR: 729284 ", 'Value for producer_id_you not set!', basename(__FILE__).' LINE '.__LINE__));
// file_name2 -- Not needed unless (possibly) when uploading multiple images at a time
// if (isset ($_POST['file_name'][$image_index])) $file_name2 = mysqli_real_escape_string ($connection, $_POST['file_name'][$image_index]);
// title
if (isset ($_POST['title'][$image_index])) $title = mysqli_real_escape_string ($connection, $_POST['title'][$image_index]);
// caption
if (isset ($_POST['caption'][$image_index])) $caption = mysqli_real_escape_string ($connection, $_POST['caption'][$image_index]);
// file_name2
if (isset ($_FILES['files']['name'][$image_index])) $file_name = mysqli_real_escape_string ($connection, $_FILES['files']['name'][$image_index]);
// mime_type
if (isset ($_FILES['files']['type'][$image_index])) $mime_type = mysqli_real_escape_string ($connection, $_FILES['files']['type'][$image_index]);
// tmp_name
if (isset ($_FILES['files']['tmp_name'][$image_index])) $tmp_name = mysqli_real_escape_string ($connection, $_FILES['files']['tmp_name'][$image_index]);
// error
if (isset ($_FILES['files']['error'][$image_index])) $error = mysqli_real_escape_string ($connection, $_FILES['files']['error'][$image_index]);
// size
if (isset ($_FILES['files']['size'][$image_index])) $content_size = mysqli_real_escape_string ($connection, $_FILES['files']['size'][$image_index]);


if (isset ($_GET['action']) && $_GET['action'] == 'delete')
  {
    // Cycle through all files matching the image number and delete them
    $deleted = true;
    foreach (glob(FILE_PATH.PRODUCT_IMAGE_PATH.'img*-'.$image_id.'.png') as $filename)
      {
        // If deletion fails for some reason, then we won't send back the json to
        // remove the file and we won't remove it from the database
        unlink ($filename) or die (debug_print ("ERROR: 676530 ", array ('Failed while deleting file:'. $filename, basename(__FILE__).' LINE '.__LINE__)));
        // if (! unlink ($filename)) $deleted = false;
      }
    if ($deleted == true)
      {
        // Delete the image from the database
        $query = '
          DELETE FROM
            '.TABLE_PRODUCT_IMAGES.'
          WHERE
            producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"
            AND image_id = "'.mysqli_real_escape_string ($connection, $image_id).'"
            AND ( SELECT COUNT(product_id)
                  FROM '.NEW_TABLE_PRODUCTS.'
                  WHERE image_id = "'.mysqli_real_escape_string ($connection, $image_id).'"
                ) < 1';
        $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 758921 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        echo 'deleted';
      }
  }
// Iterate through the files and save them in the database
elseif (!$error && $content_size)
  {
    // Don't accept large files (should probably throw an error...?)
    if ($content_size > UPLOAD_MAX_FILE_KB * 1024) exit (1);
    // Need to get the image width and height
    $image_data = file_get_contents ($tmp_name);
    $image_info = getimagesizefromstring($image_data);
    $width = $image_info[0];
    $height = $image_info[1];
    // Insert the image into the database
    $query = '
      INSERT INTO
        '.TABLE_PRODUCT_IMAGES.'
      SET
        producer_id = "'.$producer_id_you.'",
        title = "'.$title.'",
        caption = "'.$caption.'",
        image_content = "'.mysqli_real_escape_string ($connection, $image_data).'",
        file_name = "'.$file_name.'",
        content_size = "'.$content_size.'",
        mime_type = "'.$mime_type.'",
        width = "'.$width.'",
        height = "'.$height.'"';
    $result=mysqli_query ($connection, $query) or die (debug_print ("ERROR: 890583 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    // Grab the image_id while we can
    $image_id = mysqli_insert_id ($connection);
    // Build JSON return value(s)
    $return_info[$image_index]['name'] = $file_name;
    $return_info[$image_index]['size'] = $content_size;
    $return_info[$image_index]['type'] = $mime_type;
    $return_info[$image_index]['url'] = PATH.'product_images/img'.PRODUCT_IMAGE_SIZE.'-'.$image_id.'.png';
    $return_info[$image_index]['thumbnailUrl'] = PATH.'product_images/img'.PRODUCT_IMAGE_SIZE.'-'.$image_id.'.png';
    $return_info[$image_index]['deleteUrl'] = $_SERVER['SCRIPT_NAME'].'?action=delete&image_id='.$image_id;
    $return_info[$image_index]['deleteType'] = 'DELETE';

    $image_index++;
    // We are NOT taking the following steps to save the image in a file
    //   $targetPath = dirname( __FILE__ ).$ds.$storeFolder.$ds;
    //   $targetFile = $targetPath. $_FILES['file']['name'];
    //   move_uploaded_file($temp_file,$targetFile);

    // However, maybe we SHOULD create the resized file and put it in place
    // ..... TO-DO .....

    // RETURN JSON:
    $json_return = array ('files' => $return_info);
    echo json_encode ($json_return);
  }
