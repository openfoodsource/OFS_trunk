<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');

// Use only the producer_id_you
if ($_SESSION['producer_id_you']) $producer_id = $_SESSION['producer_id_you'];
// Capture the image_id if it is set
if (isset ($_GET['image_id'])) $image_id = mysql_real_escape_string($_GET['image_id']);
// Figure out where we came from and save it so we can go back
if (isset ($_REQUEST['referrer'])) $referrer = mysql_real_escape_string($_REQUEST['referrer']);
else $referrer = $_SERVER['HTTP_REFERER'];

// Section for setting an image for a particular product
if ($_GET['action'] == 'select_image')
  {
    if (isset ($_GET['product_id'])) $product_id_target = mysql_real_escape_string($_GET['product_id']);
    if (isset ($_GET['product_version'])) $product_version_target = mysql_real_escape_string($_GET['product_version']);
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
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 784390 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $row = mysql_fetch_array($result);
        $image_id_target = $row['image_id'];
        $product_id_target = $row['product_id'];
        $product_name_target = $row['product_name'];
      }
    $page_content = '
        <fieldset id="options">
          <input type="checkbox" name="select_all_versions" id="select_all_versions">
          <label for="select_all_versions" id="version_option">Check here if you want to set the image for ALL versions of this product at once.</label>
          <div id="return_button"><a href="'.$referrer.'">Return to previous page</a></div>
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
            <div id="image-upload" class="gallery_image" onclick="popup_src(\'upload_images.php\', \'upload_image\')">
              <div class="upload_icon" title="Upload new image">&#8686;</div>
            </div>
          <figcaption>upload new image</figcaption>
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
        '.TABLE_PRODUCT_IMAGES.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
      ORDER BY image_id';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 022967 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysql_fetch_array($result) )
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
            <div id="image-'.$image_id.'" class="gallery_image'.($image_id == $image_id_target ? ' selected' : '').'" title="'.$product_list.'">
              <input id="edit-'.$image_id.'" class="image_edit" type="button" title="Edit this image" value="&#9998;" onclick="popup_src(\''.$_SERVER['SCRIPT_NAME'].'?action=edit_image&image_id='.$image_id.'\', \'upload_image\')"></input>
              '.(strlen ($product_list) == 0 ? '<input id="delete-'.$image_id.'" class="image_delete" type="button" title="Delete this image" value="&#215;" onclick="delete_image(this,\'set\')" onblur="delete_image(this,\'clear\')"></input>' : '').'
              <input id="select-'.$image_id.'" class="image_select" type="button" title="Select this image" value="&#10004;" onclick="set_image('.$image_id.')"></input>
              <img src="'.get_image_path_by_id ($image_id).'">
            </div>
          <figcaption>'.$title.'</figcaption>
          </div>';
      }
  }
elseif ($_POST['action'] == 'set_image')
  {
    // Variables used in set_image
    if (isset ($_POST['product_id'])) $product_id_target = mysql_real_escape_string($_POST['product_id']);
    if (isset ($_POST['product_version'])) $product_version_target = mysql_real_escape_string($_POST['product_version']);
    if (isset ($_POST['new_image_id'])) $image_id_target = mysql_real_escape_string($_POST['new_image_id']);
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
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 231831 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
    if (isset ($_REQUEST['image_id'])) $image_id = mysql_real_escape_string($_REQUEST['image_id']);
    if (isset ($_SESSION['producer_id'])) $producer_id = mysql_real_escape_string($_SESSION['producer_id']);
    // Select all products for this image
    if ($image_id != 0 && $producer_id != 0)
      {
        // Is there information to POST before proceeding?
        if ($_POST['post_action'] == 'Save')
          {
            $query = '
              UPDATE
                '.TABLE_PRODUCT_IMAGES.'
              SET
                title = "'.mysql_real_escape_string($_POST['title']).'",
                '.(isset($_POST['width']) ? 'width = "'.mysql_real_escape_string($_POST['width']).'",' : '').'
                '.(isset($_POST['height']) ? 'height = "'.mysql_real_escape_string($_POST['height']).'",' : '').'
                caption = "'.mysql_real_escape_string($_POST['caption']).'"
              WHERE
                image_id = "'.mysql_real_escape_string($_POST['image_id']).'"';
            $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 289541 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
            '.TABLE_PRODUCT_IMAGES.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
            AND '.TABLE_PRODUCT_IMAGES.'.image_id = "'.mysql_real_escape_string ($image_id).'"
          ORDER BY
            product_id,
            product_version';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 022967 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            // Display the upload link
        $add_this_first = 0;
        while ( $row = mysql_fetch_array($result) )
          {
            $image_id = $row['image_id'];
            $pvid = $row['pvid'];
            $product_id = $row['product_id'];
            $product_version = $row['product_version'];
            $product_name = $row['product_name'];
            $caption = $row['caption'];

            $image_info = getimagesizefromstring($row['image_content']);
            $actual_width = $image_info[0];
            $actual_height = $image_info[1];
            $width_to_height = $actual_width / $actual_height;
            if ($actual_width <= PRODUCT_IMAGE_SIZE && $actual_height <= PRODUCT_IMAGE_SIZE)
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
              <div class="gallery_image_large">
                <img src="'.get_image_path_by_id ($image_id).'">
                <div id="small_size_message">'.$stretch_text.'</div>
              </div>
              <div class="image_info">
                <fieldset class="image_fields">
                  <form id="image_data" action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
                    <input id="save_button" name="post_action" type="submit" value="Save">
                    <input type="hidden" name="action" value="edit_image">
                    <input type="hidden" name="image_id" value="'.$row['image_id'].'">
                    '.($row['width'] != $actual_width ? '<input type="hidden" name="width" value="'.$actual_width.'">' : '').'
                    '.($row['height'] != $actual_height ? '<input type="hidden" name="height" value="'.$actual_height.'">' : '').'
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
                <span id="width_info" class="info">'.$actual_width.'</span>
                <span id="height_label" class="label">Original height (pixels)</span>
                <span id="height_info" class="info">'.$actual_height.'</span>
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
    // We're not going to send header and footer for this page
    $page_specific_css .= '
      <style type="text/css">
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
            height:5em;
            width:5em;
            float:right;
            margin:20px 25px 10px 10px;
            }
          #save_button:hover {
            background-color:#ddd;
            color:#008;
            }
          label,
          .label {
            display:block;
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
            }
          .info {
            background-color:#ddd;
            }
          .gallery_image_large {
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
      </style>';
    echo $page_specific_css.$page_content;
    exit (0);
  }
 

$page_specific_css = '
  <style type="text/css">
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
      position:relative;
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
    .gallery_image.selected:hover {
      border:6px solid rgba(128,64,64,0.8);
      }
    /* Position of images within the gallery blocks */
    .gallery_image img {
      position:absolute;
      bottom:0;
      max-width:100px;
      max-height:100px;
      }
    /* Appearance of the upload arrow icon */
    .gallery_image .upload_icon {
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
    .gallery_image:hover input {
      display:block;
      }
    /* Style the specific action areas when they are hovered */
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
      }
  </style>';

$page_specific_javascript = '
  <script type="text/javascript">
    // This function requires two clicks to delete, changing style between.
    function delete_image (obj, action) {
      var image_id = obj.id.split("-")[1];
      if (action == "set") {
        if (jQuery(obj).hasClass("warn")) {
          jQuery.get("'.BASE_URL.PATH.'receive_image_uploads.php?action=delete&image_id="+image_id, function(data) {
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

    var old_image_id = '.$image_id_target.';
    function set_image(image_id) {
      var select_all_versions = jQuery("#select_all_versions").prop("checked"); // gives true/false
      var product_id = "'.preg_replace("/[^0-9]/",'',$_GET['product_id']).'";
      var product_version = "'.preg_replace("/[^0-9]/",'',$_GET['product_version']).'";
      jQuery.ajax({
        type: "POST",
        url: "'.BASE_URL.PATH.'set_product_image.php",
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
          }
        else {
          alert ("Sorry, the image was not updated");
          }
        });
      }
  </script>';

include("func/show_businessname.php");

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
