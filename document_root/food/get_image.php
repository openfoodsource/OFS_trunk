<?php
include_once ('config_openfood.php');

// If this came about as a redirect from a missing image
if ($_SERVER['REDIRECT_STATUS'] == '404')
  {
    // Then get the image information
    $file = basename ($_SERVER['REQUEST_URI'],'.png');
    list ($null, $image_id) = explode ('-', $file);
  }
else
  {
    // Otherwise image info is in the $_GET query
    $image_id = mysqli_real_escape_string ($connection, $_GET['image_id']);
  }
// Get the image and related information from the database
if (is_numeric ($image_id))
  {
    // First check to see if the image exists as a file
    $file = FILE_PATH.PRODUCT_IMAGE_PATH.'img'.PRODUCT_IMAGE_SIZE.'-'.$image_id.'.png';
    if (!file_exists (FILE_PATH.$file))
      {
        // If the image does not exist, then get it from the database
        $query = '
          SELECT
            *
          FROM
            '.TABLE_PRODUCT_IMAGES.'
          WHERE
            image_id = "'.$image_id.'"';
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 785922 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
        // If this is just to send an image from the database, then do that and quit
        if ($_GET['type'] == 'db')
          {
            header( "Content-type: ".$row['mime_type']);
            echo $row['image_content'];
            exit (0);
          }
        // Otherwise, use Imagick to resize the image and save it to the expected file
        $image = new Imagick();
        $image->setResolution(100, 100) or die(debug_print ("ERROR: 753022 ", 'Failed setResolution', basename(__FILE__).' LINE '.__LINE__)); // Need this for PDF images
        if (strlen ($row['image_content']) > 0)
          {
            $image->readImageBlob($row['image_content']) or die(debug_print ("ERROR: 750223 ", 'Failed readimageblob', basename(__FILE__).' LINE '.__LINE__));
            $db_size = $image->getImageGeometry() or die(debug_print ("ERROR: 869402 ", 'Failed getImageGeometry', basename(__FILE__).' LINE '.__LINE__));
          }
        else
          {
            die(debug_print ("ERROR: 702123 ", 'Image with zero size: #'.$image_id, basename(__FILE__).' LINE '.__LINE__));
          }
        // Update the database if it was wrong
        if ($row['width'] != $db_size['width'] ||
            $row['height'] != $db_size['height'])
          {
            $query = '
              UPDATE
                '.TABLE_PRODUCT_IMAGES.'
              SET
                width = "'.$db_size['width'].'",
                height = "'.$db_size['height'].'"
              WHERE
                image_id = "'.mysqli_real_escape_string ($connection, $_GET['image_id']).'"';
            $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 902742 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
        $image->scaleImage (PRODUCT_IMAGE_SIZE, PRODUCT_IMAGE_SIZE, true) or die(debug_print ("ERROR: 869302 ", 'Failed scaleImage', basename(__FILE__).' LINE '.__LINE__));
        $image->writeImage ($file) or die(debug_print ("ERROR: 863964 ", 'Failed writeImage', basename(__FILE__).' LINE '.__LINE__));
        // This would be a good place to remove any of the same image but of a different size
        // NOT IMPLEMENTED
      }
    // Now redirect the browser to the actual image
    header ('Location: '.PRODUCT_IMAGE_PATH.'img'.PRODUCT_IMAGE_SIZE.'-'.$image_id.'.png', TRUE, 301);
  }
