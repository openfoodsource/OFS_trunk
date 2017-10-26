<?php
include_once 'config_openfood.php';
// getdata.php3 - by Florian Dittmer <dittmer@gmx.net>
// Example php script to demonstrate the direct passing of binary data
// to the user. More infos at http://www.phpbuilder.com
// Syntax: getdata.php3?id=<id>
// You would call this script as an img src tag, e.g.
// you can do <img src="getdata.php?id=3">
//
if( $_GET['logo_id'] )
  {
    // you may have to modify login information for your database server:
    $query = '
      SELECT
        bin_data,
        filetype
      FROM
        '.TABLE_PRODUCER_LOGOS.'
      WHERE
        logo_id='.$_GET['logo_id'];
    $result = @mysqli_query($connection, $query);
    if ($row = mysqli_fetch_object ($result))
      {
        $data = $row->bin_data;
        $type = $row->filetype;
      }
    Header( "Content-type: $type");
    echo $data;
    echo 'Click here to return to <a href="coopproducers.php">'.ORGANIZATION_TYPE.' producers</a>';
  }
