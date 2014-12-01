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

    // you may have to modify login information for your database server:

    $query = '
      SELECT
        image_content,
        mime_type
      FROM
        '.TABLE_PRODUCT_IMAGES.'
      WHERE
        image_id = '.mysql_real_escape_string($_GET['image_id']);
    $result = @mysql_query($query,$connection);

    $data = @mysql_result($result,0,"image_content");
    $type = @mysql_result($result,0,"mime_type");

    Header( "Content-type: $type");
    echo $data;
  };
?>