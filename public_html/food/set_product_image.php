<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');

// Use only the producer_id_you
if ($_SESSION['producer_id_you']) $producer_id = $_SESSION['producer_id_you'];
// Capture the image_id if it is set
if (isset ($_GET['image_id'])) $image_id = mysqli_real_escape_string ($connection, $_GET['image_id']);
// Figure out where we came from and save it so we can go back
if (isset ($_REQUEST['referrer'])) $referrer = mysqli_real_escape_string ($connection, $_REQUEST['referrer']);
else $referrer = $_SERVER['HTTP_REFERER'];

if($_GET['display_as'] == 'popup')
  {
    $display_as_popup = true;
  }
// Section for setting an image for a particular product
if ($_GET['action'] == 'select_image')
  {
    if (isset ($_GET['product_id'])) $product_id_target = mysqli_real_escape_string ($connection, $_GET['product_id']);
    if (isset ($_GET['product_version'])) $product_version_target = mysqli_real_escape_string ($connection, $_GET['product_version']);
    // Find the image being used by this product
    if (isset ($product_id_target) && isset ($product_version_target))
      {
        $query = '
          SELECT
            '.NEW_TABLE_PRODUCTS.'.image_id,
            '.NEW_TABLE_PRODUCTS.'.product_name,
            '.NEW_TABLE_PRODUCTS.'.product_id
          FROM
            '.NEW_TABLE_PRODUCTS.'
          WHERE
            '.NEW_TABLE_PRODUCTS.'.product_id = "'.$product_id_target.'"
            AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.$product_version_target.'"';
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 784390 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
        $image_id_target = $row['image_id'];
        $product_id_target = $row['product_id'];
        $product_name_target = $row['product_name'];
      }
    $page_content = '
        <fieldset id="options">
          <input type="checkbox" name="select_all_versions" id="select_all_versions">
          <label for="select_all_versions" id="version_option">Check here if you want to set the image for ALL versions of this product at once.</label>
          <!-- <div id="return_button"><a href="'.$referrer.'">Return to previous page</a></div> -->
          <span id="instructions">INSTRUCTIONS:<p>To switch images for this product, click on the &#10004; check
            of the selected product. You can also
            (1) upload new images using the &#8686; arrow,
            (2) view larger versions and/or add titles or captions to images using the &#9998; icon,
            (3) delete images that are not being used by any product by clicking (twice) on the &#215; symbol, or
            (4) hover over images to see which products/versions are using them.</p>
            <p>Text below the images shows the title (if available), or the products (if there are any), or the original file name.</p></span>
        </fieldset>';
    // Display the upload link
    $page_content .= '
          <div class="gallery_block">
            <div id="image-upload" class="gallery_image" onclick="popup_src(\'upload_images.php\', \'upload_image\', \'\', false)">
              <div class="upload_icon" title="Upload new image">&#8686;</div>
            </div>
          <figcaption>upload new image</figcaption>
          </div>
          <div class="gallery_block">
            <div id="image-remove" class="gallery_image" onclick="remove_image()">
              <div class="remove_icon" title="Remove image">&#9988;</div>
            </div>
          <figcaption>remove image from the product</figcaption>
          </div>';
    // Select all images from this producer
    $query = '
      SELECT
        image_id,
        producer_id,
        title,
        caption,
        file_name,
        ( SELECT GROUP_CONCAT(DISTINCT(CONCAT("#",product_id,"-",product_version,":",product_name)) SEPARATOR "\n")
          FROM '.NEW_TABLE_PRODUCTS.'
          WHERE '.NEW_TABLE_PRODUCTS.'.image_id = '.TABLE_PRODUCT_IMAGES.'.image_id
        ) AS product_list
      FROM
        '.TABLE_PRODUCT_IMAGES.'
      WHERE
        '.TABLE_PRODUCT_IMAGES.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"
      ORDER BY COALESCE(title, caption, file_name, 0)';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 522967 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
      {
        $image_id = $row['image_id'];
        $product_id = $row['product_id'];
        $title = $row['title'];
        $caption = $row['caption'];
        $product_list = $row['product_list'];
        $file_name = $row['file_name'];
        // Use the product list if there is no title
        if (strlen ($title) == 0) $title = $product_list;
        // Use the file name if there is still no title
        if (strlen ($title) == 0) $title = $file_name;
        $page_content .= '
          <div class="gallery_block">
            <div id="image-'.$image_id.'" class="gallery_image'.($image_id == $image_id_target ? ' selected' : '').'" title="'.$product_list.'" style="background-image:url(\''.get_image_path_by_id ($image_id).'\');" onclick="jQuery(this).toggleClass(\'hover\');">
              <input id="edit-'.$image_id.'" class="image_edit" type="button" title="Edit this image" value="&#9998;" onclick="popup_src(\''.$_SERVER['SCRIPT_NAME'].'?action=edit_image&image_id='.$image_id.'\', \'upload_image\', \'\', false)"></input>
              '.(strlen ($product_list) == 0 ? '<input id="delete-'.$image_id.'" class="image_delete" type="button" title="Delete this image" value="&#215;" onclick="delete_image(this,\'set\')" onblur="delete_image(this,\'clear\')"></input>' : '').'
              <input id="select-'.$image_id.'" class="image_select" type="button" title="Select this image" value="&#10004;" onclick="set_image('.$image_id.')"></input>
              <!-- <img src="'.get_image_path_by_id ($image_id).'"> -->
            </div>
          <figcaption>'.$title.'</figcaption>
          </div>';
      }
  }
elseif ($_POST['action'] == 'remove_image')
  {
    // Variables used in remove_image
    if (isset ($_POST['product_id'])) $product_id_target = mysqli_real_escape_string ($connection, $_POST['product_id']);
    if (isset ($_POST['product_version'])) $product_version_target = mysqli_real_escape_string ($connection, $_POST['product_version']);
    if ($_POST['select_all_versions'] != "true")
      $query_where = 'AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.$product_version_target.'"';
    // Connect a new image to this product/version
    if ($product_id_target != 0 &&
        $product_version_target != 0)
      {
        $query = '
          UPDATE
            '.NEW_TABLE_PRODUCTS.'
          SET
            '.NEW_TABLE_PRODUCTS.'.image_id = ""
          WHERE
            '.NEW_TABLE_PRODUCTS.'.product_id = "'.$product_id_target.'"
            '.$query_where;
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 231831 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $json['result'] = "success";
      }
    else
      {
        $json['result'] = "failure";
      }
    echo json_encode ($json);
    exit (0);
  }
elseif ($_POST['action'] == 'rotate_image')
  {
    // Variables used in remove_image
    if (isset ($_POST['rotation'])) $rotation = mysqli_real_escape_string ($connection, $_POST['rotation']);
    if (isset ($_POST['image_id'])) $image_id = mysqli_real_escape_string ($connection, $_POST['image_id']);
    // Get the image from the database
    $query = '
      SELECT
        *
      FROM
        '.TABLE_PRODUCT_IMAGES.'
      WHERE
        image_id = "'.$image_id.'"';
    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 785922 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
    // Use Imagick to rotate the image
    $image = new Imagick ();
    // $image->setResolution (100, 100) or die(debug_print ("ERROR: 892023 ", 'Failed setResolution', basename(__FILE__).' LINE '.__LINE__)); // Need this for PDF images
    $image->readImageBlob ($row['image_content']) or die(debug_print ("ERROR: 748923 ", 'Failed readImageBlob', basename(__FILE__).' LINE '.__LINE__));
    $db_image_size = $image->getImageGeometry () or die(debug_print ("ERROR: 794210 ", 'Failed getImageGeometry', basename(__FILE__).' LINE '.__LINE__));
    // First increase the canvas size to allow full rotation by increments of 90 deg
    $max_dimension = max (array ($db_image_size['width'], $db_image_size['height']));
    $image->extentImage ($max_dimension, $max_dimension, ($db_image_size['width'] - $max_dimension) / 2, ($db_image_size['height'] - $max_dimension) / 2) or die(debug_print ("ERROR: 753202 ", 'Failed extentImage', basename(__FILE__).' LINE '.__LINE__));
    // Now do the rotation
    $image->rotateImage (new ImagickPixel('#00000000'), $rotation) or die(debug_print ("ERROR: 793434 ", 'Failed rotateImage', basename(__FILE__).' LINE '.__LINE__));
    // And adjust the width/height
    if ($rotation == 90 || $rotation == 270)
      {
        // Swap original width/height
        list ($db_image_size['width'], $db_image_size['height']) = array ($db_image_size['height'], $db_image_size['width']);
      }
    $image->cropImage ($db_image_size['width'], $db_image_size['height'], ($max_dimension - $db_image_size['width']) / 2, ($max_dimension - $db_image_size['height']) / 2) or die(debug_print ("ERROR: 103743 ", 'Failed cropImage', basename(__FILE__).' LINE '.__LINE__));
    // Since we are modifying the image, we will keep it in the database as jpeg format for conservation of size
    // It will be converted to png if/when an image file is created.
    $image->setImageCompression(Imagick::COMPRESSION_JPEG) or die(debug_print ("ERROR: 742905 ", 'Failed setImageCompression', basename(__FILE__).' LINE '.__LINE__));
    $image->setImageCompressionQuality(80) or die(debug_print ("ERROR: 750248 ", 'Failed setImageCompressionQuality', basename(__FILE__).' LINE '.__LINE__));
    $image->stripImage() or die(debug_print ("ERROR: 720894 ", 'Failed stripImage', basename(__FILE__).' LINE '.__LINE__));
    $image->setImageFormat("jpeg") or die(debug_print ("ERROR: 734002 ", 'Failed setImageFormat', basename(__FILE__).' LINE '.__LINE__));
    $rotated_image_data = $image->getImageBlob() or die(debug_print ("ERROR: 854362 ", 'Failed getImageBlob', basename(__FILE__).' LINE '.__LINE__));
    // Now put this into the database
    $query = '
      UPDATE
        '.TABLE_PRODUCT_IMAGES.'
      SET
        image_content = "'.mysqli_real_escape_string ($connection, $rotated_image_data).'",
        content_size = "'.strlen ($rotated_image_data).'",
        mime_type = "image/jpeg",
        width = "'.$db_image_size['width'].'",
        height = "'.$db_image_size['height'].'"
      WHERE
        image_id = "'.mysqli_real_escape_string ($connection, $image_id).'"';
    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 752893 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
// 
// 
// // echo "<pre>SIZE: ".strlen($image->getImageBlob ())."</pre>";
// // header( "Content-type: image/png");
// echo "<pre>$query</pre>";
// exit (0);
// 
// 
// 
// 
    // Now unlink the image file, if it exists (might fail if file does not exist)
    unlink (FILE_PATH.PRODUCT_IMAGE_PATH.'img'.PRODUCT_IMAGE_SIZE.'-'.$image_id.'.png');
    // $json['result'] = "failure";
    $json['uniqid'] = uniqid();
    $json['result'] = "success";
    echo json_encode ($json);
    exit (0);
  }
elseif ($_POST['action'] == 'set_image')
  {
    // Variables used in set_image
    if (isset ($_POST['product_id'])) $product_id_target = mysqli_real_escape_string ($connection, $_POST['product_id']);
    if (isset ($_POST['product_version'])) $product_version_target = mysqli_real_escape_string ($connection, $_POST['product_version']);
    if (isset ($_POST['new_image_id'])) $image_id_target = mysqli_real_escape_string ($connection, $_POST['new_image_id']);
    if ($_POST['select_all_versions'] != "true")
      $query_where = 'AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.$product_version_target.'"';
    // Connect a new image to this product/version
    if ($product_id_target != 0 &&
        $product_version_target != 0 &&
        $image_id_target != 0)
      {
        $query = '
          UPDATE
            '.NEW_TABLE_PRODUCTS.'
          SET
            '.NEW_TABLE_PRODUCTS.'.image_id = "'.$image_id_target.'"
          WHERE
            '.NEW_TABLE_PRODUCTS.'.product_id = "'.$product_id_target.'"
            '.$query_where;
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 931831 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        $json['result'] = "success";
        $json['new_image_id'] = $image_id_target;
      }
    else
      {
        $json['result'] = "failure";
      }
    echo json_encode ($json);
    exit (0);
  }
// Section for editing images (sometimes called with GET, sometimes with POST
elseif ($_REQUEST['action'] == 'edit_image')
  {
    // Variables used in edit_image
    if (isset ($_REQUEST['image_id'])) $image_id = mysqli_real_escape_string ($connection, $_REQUEST['image_id']);
    if (isset ($_SESSION['producer_id'])) $producer_id = mysqli_real_escape_string ($connection, $_SESSION['producer_id']);
    // Select all products for this image
    if ($image_id != 0 && $producer_id != 0)
      {
        // Is there information to POST before proceeding?
        if ($_POST['post_action'] == 'Save Title/Caption')
          {
            $query = '
              UPDATE
                '.TABLE_PRODUCT_IMAGES.'
              SET
                title = "'.mysqli_real_escape_string ($connection, $_POST['title']).'",
                '.(isset($_POST['width']) ? 'width = "'.mysqli_real_escape_string ($connection, $_POST['width']).'",' : '').'
                '.(isset($_POST['height']) ? 'height = "'.mysqli_real_escape_string ($connection, $_POST['height']).'",' : '').'
                caption = "'.mysqli_real_escape_string ($connection, $_POST['caption']).'"
              WHERE
                image_id = "'.mysqli_real_escape_string ($connection, $_POST['image_id']).'"';
            $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 289541 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
        $query = '
          SELECT
            '.NEW_TABLE_PRODUCTS.'.pvid,
            '.NEW_TABLE_PRODUCTS.'.product_id,
            '.NEW_TABLE_PRODUCTS.'.product_version,
            '.NEW_TABLE_PRODUCTS.'.product_name,
            '.TABLE_PRODUCT_IMAGES.'.image_id,
            '.TABLE_PRODUCT_IMAGES.'.producer_id,
            '.TABLE_PRODUCT_IMAGES.'.title,
            '.TABLE_PRODUCT_IMAGES.'.caption,
            '.TABLE_PRODUCT_IMAGES.'.image_content,
            '.TABLE_PRODUCT_IMAGES.'.file_name,
            '.TABLE_PRODUCT_IMAGES.'.content_size,
            '.TABLE_PRODUCT_IMAGES.'.mime_type,
            '.TABLE_PRODUCT_IMAGES.'.width,
            '.TABLE_PRODUCT_IMAGES.'.height
          FROM
            '.TABLE_PRODUCT_IMAGES.'
          LEFT JOIN
            '.NEW_TABLE_PRODUCTS.' USING(image_id)
          WHERE
            '.TABLE_PRODUCT_IMAGES.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"
            AND '.TABLE_PRODUCT_IMAGES.'.image_id = "'.mysqli_real_escape_string ($connection, $image_id).'"
          ORDER BY
            product_id,
            product_version';
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 022967 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
            // Display the upload link
        $add_this_first = 0;
        while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
          {
            $image_id = $row['image_id'];
            $pvid = $row['pvid'];
            $product_id = $row['product_id'];
            $product_version = $row['product_version'];
            $product_name = $row['product_name'];
            $caption = $row['caption'];
            $image = new Imagick();
            $image->setResolution(100, 100); // This is low resolution for a PDF image, but should be high enough for images.
            $image->readimageblob($row['image_content']);
            $image_info = $image->getImageGeometry();
            $width_to_height = $image_info['width'] / $image_info['height'];
            if ($image_info['width'] <= PRODUCT_IMAGE_SIZE && $image_info['height'] <= PRODUCT_IMAGE_SIZE)
              $stretch_text = '<br>SMALL<br>IMAGE<br>IS<br>STRETCHED<br>TO<br>FIT';
            else
              $stretch_text = '';
            if ($width_to_height < 1)
              $size_style = 'height:'.PRODUCT_IMAGE_SIZE.'px;';
            else $size_style = 'width:'.PRODUCT_IMAGE_SIZE.'px;';
            if ($add_this_first++ < 1)
              {
                $page_content .= '
              <h3>Information for image #'.$image_id.'</h3>
              <div id="gallery_image_large-'.$image_id.'" class="gallery_image_large" style="background-image:url(\''.get_image_path_by_id ($image_id).'\');">
                <div id="small_size_message">'.$stretch_text.'</div>
              </div>
              <div class="gallery_image_rotation">
                <div class="instruction">Rotate</div>
                <div class="orientation">&bull;<div id="rotate_image_none-'.$image_id.'" class="up" style="background-image:url(\''.get_image_path_by_id ($image_id).'\');" onclick="rotate_image(this, 0);"></div></div>
                <div class="orientation">&#x21b7;<div id="rotate_image_right-'.$image_id.'" class="right" style="background-image:url(\''.get_image_path_by_id ($image_id).'\');" onclick="rotate_image(this, 90);"></div></div>
                <div class="orientation">&#x21bb;<div id="rotate_image_down-'.$image_id.'" class="down" style="background-image:url(\''.get_image_path_by_id ($image_id).'\');" onclick="rotate_image(this, 180);"></div></div>
                <div class="orientation">&#x21b6;<div id="rotate_image_left-'.$image_id.'" class="left" style="background-image:url(\''.get_image_path_by_id ($image_id).'\');" onclick="rotate_image(this, 270);"></div></div>
              </div>
              <div class="image_info">
                <fieldset class="image_fields">
                  <form id="image_data" action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
                    <input id="save_button" name="post_action" type="submit" value="Save Title/Caption">
                    <input type="hidden" name="action" value="edit_image">
                    <input type="hidden" name="image_id" value="'.$row['image_id'].'">
                    '.($row['width'] != $image_info['width'] ? '<input type="hidden" name="width" value="'.$image_info['width'].'">' : '').'
                    '.($row['height'] != $image_info['height'] ? '<input type="hidden" name="height" value="'.$image_info['height'].'">' : '').'
                    <label for="title">Title (For organizing images)</label>
                    <input type="text" id="title" name="title" value="'.$row['title'].'">
                    <label for="caption">Caption</label>
                    <input type="text" id="caption" name="caption" value="'.$row['caption'].'">
                  </form>
                </fieldset>
                <span id="file_name_label" class="label">Original file name</span>
                <span id="file_name_info" class="info">'.$row['file_name'].'</span>
                <span id="content_size_label" class="label">Original size (bytes)</span>
                <span id="content_size_info" class="info">'.number_format($row['content_size'], 0).'</span>
                <span id="mime_type_label" class="label">Original type<br>Note: all images are served as PNG)</span>
                <span id="mime_type_info" class="info">'.$row['mime_type'].'</span>
                <span id="width_label" class="label">Original width (pixels)</span>
                <span id="width_info" class="info">'.$image_info['width'].'</span>
                <span id="height_label" class="label">Original height (pixels)</span>
                <span id="height_info" class="info">'.$image_info['height'].'</span>
              </div>
              <div class="product_list">';
              }
            // If we're into a new product_id, then start a new product_row
            // Also, if we skipped a version, then start a new product row
            if ($product_id != $product_id_prior ||
                $product_version != $product_version_prior + 1)
              {
                $page_content .= $page_content_prior;
                // Set the new minimum version
                $product_version_min = $product_version;
              }
            else
              {
              }
            $product_id_prior = $product_id;
            $product_version_prior = $product_version;
            $page_content_prior = '
              <div class="product_row'.$pvid.'">
                <span class="product_id">Prod. #'.$product_id.'</span>
                <span class="product_version">Ver. '.$product_version_min.($product_version != $product_version_min ? '-'.$product_version : '').'</span>
                <span class="product_name">'.$product_name.'</span>
              </div>';
          }
        $page_content .= '
            '.$page_content_prior.'
              </div>';
      }
    $page_specific_css .= '
      /* STYLES FOR DISPLAYING THE EDIT_IMAGE PAGE */
        h3 {
          margin:5px 20px;
          }
        .image_fields {
          border:0;
          padding:0;
          margin:0;
          }
        #save_button {
          background-color:#ddd;
          margin:0.5rem;
          padding:0.25rem 0.5rem;
          border-style:outset;
          border-width:2px;
          border-color:#ccc;
          min-width:10rem;
          color:#000;
          line-height:1rem;
          font-weight:normal;
          font-family:inherit;
          }
        #save_button:hover {
          font-weight:bold;
          }
        label,
        .label {
          display:block;
          text-align:left;
          font-size:80%;
          margin:5px 10px 1px 10px;
          }
        input[type=text],
        .info {
          display:block;
          border:1px solid #aaa;
          margin: 0 10px 5px 10px;
          font-family: Helvetica,sans-serif;
          font-size:14px;
          padding:3px;
          width:90%;
          }
        .info {
          background-color:#ddd;
          }
        .gallery_image_large {
          background-repeat:no-repeat;
          background-position:center;
          background-size:contain;
          width:'.PRODUCT_IMAGE_SIZE.'px;
          height:'.PRODUCT_IMAGE_SIZE.'px;
          position:relative;
          float:left;
          background-color:#ddd;
          border:1px solid #888;
          z-index:-20;
          margin:10px 20px;
          transition:color 2s;
          }
        #small_size_message {
          position:absolute;
          top:0;
          width:100%;
          height:100%;
          font-size:30px;
          text-align:center;
          color:rgba(128,128,128,0);
          transition:color 2s;
          z-index:5;
          }
        #small_size_message:hover {
          color:rgba(128,128,128,1);
          }
        .gallery_image_large img {
          '.$size_style.'
          margin:auto;
          }
        .image_info {
          float:left;
          min-width:'.PRODUCT_IMAGE_SIZE.'px;
          min-height:'.PRODUCT_IMAGE_SIZE.'px;
          border:1px solid #888;
          max-width:50%;
          margin:10px 20px;
          }
        .product_list {
          float:left;
          clear:both;
          min-width:'.PRODUCT_IMAGE_SIZE.'px;
          max-width:'.(PRODUCT_IMAGE_SIZE*2.1).'px;
          min-height:50px;
          border:1px solid #888;
          margin:10px 20px;
          padding:10px;
          }
        /* Styles for rotation options */
        .instruction {
          text-align:center;
          font-size:80%;
          }
        .gallery_image_rotation {
          margin: 10px 20px;
          float:left;
          border:1px solid #888;
          padding:0;
          }
        .gallery_image_rotation div.orientation {
          font-size:200%;
          text-align:right;
          margin:2px 10px;
          }
        .gallery_image_rotation div.orientation div {
          display:inline-block;
          background-color:#ddd;
          background-repeat:no-repeat;
          background-position:center;
          background-size:contain;
          height:'.floor ((PRODUCT_IMAGE_SIZE / 4) - 19).'px;
          width:'.floor ((PRODUCT_IMAGE_SIZE / 4) - 19).'px;
          min-height:20px;
          min-width:20px;
          border:1px solid #888;
          margin:2px;
          cursor:pointer;
          }
        .orientation .right {
          transform: rotate(90deg);
          }
        .orientation .down {
          transform: rotate(180deg);
          }
        .orientation .left {
          transform: rotate(270deg);
          }';

    $page_specific_javascript = '
      // This function rotates an image by a specified number of degrees
      function rotate_image (obj, rotation) {
        var image_id = obj.id.split("-")[1];
        if (rotation != 0) {
          jQuery.ajax({
            type: "POST",
            url: "'.PATH.'set_product_image.php",
            cache: false,
            data: {
              rotation: rotation,
              image_id: image_id,
              action: "rotate_image"
              }
            })
          .done(function(return_values) {
            returned = JSON.parse(return_values);
            if (returned["result"] == "success") {
              // Force the image to reload
              var image_url = "'.BASE_URL.PATH.'get_image.php?image_id="+image_id+"&"+returned["uniqid"];
              // Udate all the images
              jQuery ("#gallery_image_large-"+image_id).css("background-image", "url(" + image_url + ")");
              jQuery ("#rotate_image_none-"+image_id).css("background-image", "url(" + image_url + ")");
              jQuery ("#rotate_image_right-"+image_id).css("background-image", "url(" + image_url + ")");
              jQuery ("#rotate_image_down-"+image_id).css("background-image", "url(" + image_url + ")");
              jQuery ("#rotate_image_left-"+image_id).css("background-image", "url(" + image_url + ")");
              // Now have the parent page reload the image as well
              // parent.update_image (product_id, product_version, new_image_id);
              parent.reload_image (image_id, image_url);
              }
            else {
              alert ("Sorry, the image was not rotated");
              }
            });
          }
        }';

    $display_as_popup = true;
    $page_title_html = '<span class="title">'.$business_name.'</span>';
    $page_subtitle_html = '<span class="subtitle">Set an Image for #'.$product_id_target.' '.$product_name_target.'</span>';
    $page_title = $business_name.': Set an Image for #'.$product_id_target.' '.$product_name_target;
    $page_tab = 'producer_panel';

    include("template_header.php");
    echo '
      <!-- CONTENT ENDS HERE -->
      '.$page_content.'
      <!-- CONTENT ENDS HERE -->';
    include("template_footer.php");
    exit (0);
  }

$page_specific_css = '
  /* STYLES FOR DISPLAYING THE SELECT_IMAGE PAGE */
    /* Instructions style */
    #version_option {
      color:#800;
      }
    #instructions {
      display:block;
      margin-top:1em;
      clear:both;
      }
    #instructions p {
      margin:10px 20px 2px;
      font-size:80%;
      color:#008;
      }
    /* Button for returning to the prior page */
    #return_button {
      float:right;
      }
    #return_button a {
      display:block;
      border:1px solid #aaa;
      background-color:#ddd;
      padding:5px 10px;
      }
    #return_button a:hover {
      text-decoration:none;
      border:1px solid #888;
      background-color:#aaa;
      }
    /* Gallery block contains the gallery image (container) and the figcaption */
    .gallery_block {
      position:relative;
      float:left;
      height:150px;
      }
    /* Style for the figcaptions */
    .gallery_block figcaption {
      width:112px;
      height:38px;
      overflow:hidden;
      text-align:center;
      font-size:10px;
      line-height:10px;
      }
    /* Default gallery image blocks (not images, but hold the gallery images) */
    .gallery_image {
      background-repeat:no-repeat;
      background-position:center;
      background-size:contain;
      width:112px;
      height:112px;
      border:6px solid rgba(128,128,128,0.2);
      border-left:3px solid rgba(128,128,128,0.2);
      border-right:3px solid rgba(128,128,128,0.2);
      padding:3px;
      color:#aaa;
      cursor:pointer;
      }
    /* Border color of the images during mouseover */
    .gallery_image.hover,
    .gallery_image:hover {
      border:6px solid rgba(128,128,128,0.6);
      height:118px;
      padding:0;
      color:#000;
      }
    /* Border color of the currently-selected image */
    .gallery_image.selected {
      border:6px solid rgba(192,0,0,0.8);
      height:118px;
      padding:0;
      }
    /* Border color of the currently-selected image during mouseover */
    .gallery_image.selected.hover,
    .gallery_image.selected:hover {
      border:6px solid rgba(128,64,64,0.8);
      }
    /* Appearance of the upload and remove icons */
    .gallery_image .upload_icon,
    .gallery_image .remove_icon {
      position:absolute;
      width:100%;
      height:100%;
      top:0;
      left:0;
      text-align:center;
      font-size:100px;
      line-height:100px;
      margin:0;
      border-color:#fff;
      }
    /* Basic style for the image action areas */
    .gallery_image input.image_edit,
    .gallery_image input.image_delete,
    .gallery_image input.image_select {
      z-index:10;
      display:none;
      background:#fff;
      color:#888;
      opacity:0.5;
      border:1px solid #888;
      padding:0;
      }
    /* More specific styles for action areas */
    .gallery_image input.image_edit,
    .gallery_image input.image_delete {
      line-height:16px;
      font-size:20px;
      height:20px;
      width:20px;
      border-radius:10px;
      }
    /* Specific styles for the "select" action area */
    .gallery_image input.image_select {
      line-height:55px;
      font-size:60px;
      height:60px;
      width:60px;
      border-radius:30px;
      }
    /* Exposes action areas which are otherwise invisible */
    .gallery_image.hover input,
    .gallery_image:hover input {
      display:block;
      }
    /* Style the specific action areas when they are hovered */
    .gallery_image.hover input,
    .gallery_image input:hover {
      opacity:0.7;
      color:#000;
      }
    /* Change style when action area switches to "warn" class */
    .gallery_image input.warn {
      color:#fff;
      background-color:#800;
      font-weight:bold;
      border:1px solid #800;
      }
    /* Position and the image action areas */
    .gallery_image input.image_edit {
      position:absolute;
      left:3px;
      top:3px;
      }
    .gallery_image input.image_delete {
      position:absolute;
      right:3px;
      top:3px;
      }
    .gallery_image input.image_select {
      position:absolute;
      left:20px;
      top:20px;
      }

  /* SIMPLEMODAL STYLES */
    #simplemodal-data {
      height:100%;
      }
    #simplemodal-data iframe {
      width:100%;
      height:100%;
      border:0;
      }
    .modalCloseImg.modalClose {
      position: absolute;
      right: 0;
      }    
    #simplemodal-container a.modalCloseImg {
      background: url("'.DIR_GRAPHICS.'/simplemodal_x.png") no-repeat scroll 0 0 rgba(0, 0, 0, 0);
      cursor: pointer;
      display: inline;
      height: 29px;
      position: absolute;
      right: 0;
      top: 0;
      width: 25px;
      z-index: 3200;
      }
  /* STYLES FOR DISPLAYING THE EDIT_IMAGE PAGE */
    .gallery_image_large {
      border:1px solid #888;
      float:left;
      }
    .image_fields {
      border:1px solid #888;
      float:right;
      }';

$page_specific_javascript = '
    // This function requires two clicks to delete, changing style between.
    function delete_image (obj, action) {
      var image_id = obj.id.split("-")[1];
      if (action == "set") {
        if (jQuery(obj).hasClass("warn")) {
          jQuery.get("'.PATH.'receive_image_uploads.php?action=delete&image_id="+image_id, function(data) {
            // If the return value is deleted
            if (data == "deleted") {
              // Remove the image from our list
              jQuery("#image-"+image_id).parent().remove();
              }
            })
          }
        jQuery(obj).addClass("warn");
        }
      if (action == "clear") {
        jQuery(obj).removeClass("warn");
        }
      }
    // Remove the image from a product
    function remove_image(image_id) {
      var select_all_versions = jQuery("#select_all_versions").prop("checked"); // gives true/false
      var product_id = "'.preg_replace("/[^0-9]/",'',$_GET['product_id']).'";
      var product_version = "'.preg_replace("/[^0-9]/",'',$_GET['product_version']).'";
      jQuery.ajax({
        type: "POST",
        url: "'.PATH.'set_product_image.php",
        cache: false,
        data: {
          select_all_versions: select_all_versions,
          product_id: product_id,
          product_version: product_version,
          action: "remove_image"
          }
        })
      .done(function(return_values) {
        returned = JSON.parse(return_values);
        if (returned["result"] == "success") {
          var new_image_id = returned["new_image_id"];
          jQuery("#image-"+old_image_id).removeClass("selected");
          parent.update_image (product_id, product_version, 0);
          }
        else {
          alert ("Sorry, the image was not removed");
          }
        });
      }
    // Change the image of a product
    var old_image_id = '.$image_id_target.';
    function set_image(image_id) {
      var select_all_versions = jQuery("#select_all_versions").prop("checked"); // gives true/false
      var product_id = "'.preg_replace("/[^0-9]/",'',$_GET['product_id']).'";
      var product_version = "'.preg_replace("/[^0-9]/",'',$_GET['product_version']).'";
      jQuery.ajax({
        type: "POST",
        url: "'.PATH.'set_product_image.php",
        cache: false,
        data: {
          select_all_versions: select_all_versions,
          product_id: product_id,
          product_version: product_version,
          new_image_id: image_id,
          old_image_id: old_image_id,
          action: "set_image"
          }
        })
      .done(function(return_values) {
        returned = JSON.parse(return_values);
        if (returned["result"] == "success") {
          var new_image_id = returned["new_image_id"];
          jQuery("#image-"+old_image_id).removeClass("selected");
          jQuery("#image-"+new_image_id).addClass("selected");
          old_image_id = new_image_id;
          parent.update_image (product_id, product_version, new_image_id);
          }
        else {
          alert ("Sorry, the image was not updated");
          }
        });
      }
    // This function reloads an image that might have changed (i.e. from rotation)
    function reload_image (image_id, new_target) {
      jQuery ("#image-"+image_id).css("background-image", "url(" + new_target + ")");
      }';

include("show_businessname.php");

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Set an Image for #'.$product_id_target.' '.$product_name_target.'</span>';
$page_title = $business_name.': Set an Image for #'.$product_id_target.' '.$product_name_target;
$page_tab = 'producer_panel';


include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$page_content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
