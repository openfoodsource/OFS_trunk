<?php
include_once 'config_openfood.php';
// The purpose of this page is to capture 404 errors when image files are not found
// and create them so, at least next time, they will be found

// Just to stop bogus hits on this process, only handle requests from this server
$parsed_referer = parse_url($_SERVER['HTTP_REFERER']);
if (BASE_URL != $parsed_referer['scheme'].'://'.$parsed_referer['host']) exit;

// Get the image file that was requested
$file = basename ($_SERVER['REQUEST_URI'],'.png');

// Take it apart to figure out the image_id
// Image files are of the form: 'img'.PRODUCT_IMAGE_SIZE.'-'.$image_number.'.png
list ($null, $image_id) = explode ('-', $file);

// Redirect to the image (either from the database, or whatever)
header ('Location: '.get_image_path_by_id ($image_id), TRUE, 301);
