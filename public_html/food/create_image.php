<?php
include_once 'config_openfood.php';
// The purpose of this page is to capture 404 errors when image files are not found
// and create them so, at least next time, they will be found

// Just to stop bogus hits on this process, ensure the request came from an "approved" server
// PSEUDOCODE: if (! in_array ("www.openfoodsource.org", array("www.openfoodsource.org", "openfoodsource.org")))
$url_array = parse_url($_SERVER['HTTP_REFERER']);
if (! in_array ($url_array['host'], preg_split("/[\n\r]+/", DOMAIN_NAME))) exit;

// Get the image file that was requested
$file = basename ($_SERVER['REQUEST_URI'],'.png');

// Take it apart to figure out the image_id
// Image files are of the form: 'img'.PRODUCT_IMAGE_SIZE.'-'.$image_number.'.png
list ($null, $image_id) = explode ('-', $file);

// Redirect to the image (either from the database, or whatever)
header ('Location: '.PATH.'get_image.php?image_id='.$image_id, TRUE, 301);
