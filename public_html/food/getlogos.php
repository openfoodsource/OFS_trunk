<?php

// getdata.php3 - by Florian Dittmer <dittmer@gmx.net>
// Example php script to demonstrate the direct passing of binary data
// to the user. More infos at http://www.phpbuilder.com
// Syntax: getdata.php3?id=<id>
// You would call this script as an img src tag, e.g.
// you can do <img src="getdata.php?id=3">
//

include_once 'config_openfood.php';

if ( $_GET['logo_id'] )
  {
    // you may have to modify login information for your database server:
    $query = '
      SELECT
        bin_data,
        filetype
      FROM
        '.TABLE_PRODUCER_LOGOS.'
      WHERE
        logo_id = '.$_GET['logo_id'];
    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 782071 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
    header ('Content-type: '.$row['filetype']);
    echo $row['bin_data'];

    echo 'Click here to return to <a href="coopproducers.php">coop producers</a>';
  }
