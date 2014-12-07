<?php
include_once 'config_openfood.php';
include_once 'image_functions.php';

$image_id = mysql_real_escape_string($_GET['image_id']);

if (is_numeric ($image_id))
  {
    // First check to see if the image exists as a file
    $file = PRODUCT_IMAGE_PATH.'img'.PRODUCT_IMAGE_SIZE.'-'.$image_id.'.png';
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
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 785922 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $row = mysql_fetch_array($result);
        // Use SimpleImage (from image_functions.php) to resize the image
        // and save it to the expected file
        $image = new SimpleImage();
        $image->load_data($row['image_content']);
        // If we don't have a width or height for this image in the
        // database, then save it now.
        if ($row['width'] == 0 ||
            $row['height'] == 0)
          {
            $image_info = getimagesizefromstring($row['image_content']);
            $original_width = $image_info[0];
            $original_height = $image_info[1];
            $query = '
              UPDATE
                '.TABLE_PRODUCT_IMAGES.'
              SET
                width = "'.$original_width.'",
                height = "'.$original_height.'"
              WHERE
                image_id = "'.mysql_real_escape_string($_GET['image_id']).'"';
            $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 902742 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));;
          }
        $image->resizeDownToWidthHeight(PRODUCT_IMAGE_SIZE);
        $image->save(FILE_PATH.$file, IMAGETYPE_PNG);
        // This would be a good place to remove any of the same image but of a different size
        // NOT IMPLEMENTED
      }
    // Now redirect the browser to the actual image
    header ('Location: '.$file, TRUE, 301);
  };
?>