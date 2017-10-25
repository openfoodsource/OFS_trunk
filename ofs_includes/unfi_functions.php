<?php
// Save and load unfi status information (this is stored in the "status" file and
// used for super-persistent information and communication between program steps


function unfi_get_status ($key)
  {
    global $connection;
    $query = '
      SELECT
        status_value
      FROM
        '.TABLE_UNFI_STATUS.'
      WHERE
        status_key = "'.mysqli_real_escape_string ($connection, $key).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 932170 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysqli_fetch_object ($result) )
      {
        return $row->status_value;
      }
  }
function unfi_put_status ($key, $value)
  {
    global $connection;
    $query = '
      INSERT INTO
        '.TABLE_UNFI_STATUS.'
      SET
        status_key = "'.mysqli_real_escape_string ($connection, $key).'",
        status_value = "'.mysqli_real_escape_string ($connection, $value).'"
      ON DUPLICATE KEY UPDATE
        status_value = "'.mysqli_real_escape_string ($connection, $value).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 489325 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
  }
function unfi_delete_key ($key)
  {
    global $connection;
    $query = '
      DELETE FROM
        '.TABLE_UNFI_STATUS.'
      WHERE
        status_key = "'.mysqli_real_escape_string ($connection, $key).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 784230 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
  }
