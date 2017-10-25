<?php

// getdata.php3 - by Florian Dittmer <dittmer@gmx.net>
// Example php script to demonstrate the direct passing of binary data
// to the user. More infos at http://www.phpbuilder.com
// Syntax: getdata.php3?id=<id>
// You would call this script as an img src tag, e.g.
// you can do <img src="getdata.php?id=3">
//
include_once 'config_openfood.php';

if ( $_GET['image_id'] )
  {
    $query = '
      SELECT
        image_content,
        mime_type
      FROM
        '.TABLE_PRODUCT_IMAGES.'
      WHERE
        image_id = '.mysqli_real_escape_string ($connection, $_GET['image_id']);
    $result = @mysqli_query($connection, $query);
    if ($row = mysqli_fetch_object ($result))
      {
        $data = $row->image_content;
        $type = $row->mime_type;
      }
    Header( "Content-type: $type");
    echo $data;
  }
