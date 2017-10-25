<?php
$sqlp = '
  SELECT
    '.TABLE_PRODUCER.'.business_name
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    '.TABLE_PRODUCER.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
$resultp = @mysqli_query ($connection, $sqlp) or die (debug_print ("ERROR: 086734 ", array ($sqlp, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysqli_fetch_array ($resultp, MYSQLI_ASSOC) )
  {
    $business_name = $row['business_name'];
  }
