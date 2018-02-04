<?php
include_once 'config_openfood.php';

// Routine used to check if a producer_id is already in use

// Call with: $_POST['producer_id']
// Return: 'used' | 'avail'


$count = 1; // Default to show that the producer_id is in use

// Is there a posted query string?
if(isset($_POST['producer_link']) && isset($_POST['producer_id']))
  {
    // Check if there is a producer link with this name that is not this producer_id.
    $query = '
      SELECT
        COUNT(producer_link) AS count
      FROM
        '.TABLE_PRODUCER.'
      WHERE
        producer_link = "'.mysqli_real_escape_string ($connection, $_POST['producer_link']).'"
        AND producer_id != "'.mysqli_real_escape_string ($connection, $_POST['producer_id']).'"';
    $sql = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 278530 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($sql && $row = mysqli_fetch_array ($sql, MYSQLI_ASSOC))
      {
        $count = $row['count'];
      }
  }

if ($count == 0)
  echo 'avail';
else
  echo 'used';
