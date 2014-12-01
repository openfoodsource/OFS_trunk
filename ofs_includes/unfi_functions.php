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
        status_key = "'.mysql_real_escape_string($key).'"';
    $result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    while ( $row = mysql_fetch_object($result) )
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
        status_key = "'.mysql_real_escape_string($key).'",
        status_value = "'.mysql_real_escape_string($value).'"
      ON DUPLICATE KEY UPDATE
        status_value = "'.mysql_real_escape_string($value).'"';
    $result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
  }
function unfi_delete_key ($key)
  {
    global $connection;
    $query = '
      DELETE FROM
        '.TABLE_UNFI_STATUS.'
      WHERE
        status_key = "'.mysql_real_escape_string($key).'"';
    $result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
  }

?>