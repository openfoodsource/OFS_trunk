<?php
include_once 'config_openfood.php';

if ( $_GET['logo_id'] )
  {
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
  }
