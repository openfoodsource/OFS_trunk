<?php
$sqlp = '
  SELECT
    '.TABLE_PRODUCER.'.business_name
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    '.TABLE_PRODUCER.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"';
$resultp = @mysql_query($sqlp, $connection) or die(debug_print ("ERROR: 086734 ", array ($sqlp,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($resultp) )
  {
    $business_name = $row['business_name'];
  }
?>