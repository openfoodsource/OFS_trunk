<?php
include_once 'config_openfood.php';
session_start ();

// First ensure we have authority to execute member updates
if (! CurrentMember::auth_type('site_admin,member_admin'))
  {
    echo 'Unauthorizied access';
    exit (0);
  }

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///     AJAX BACKEND FOR UPDATING A SINGLE VALUE IN THE MEMBER TABLE         ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////


// Get the arguments passed in the query_data variable
list ($member_id, $field_name, $new_value) = explode (':', $_POST['query_data']);

// Get an array of all member columns
$query = '
  SHOW COLUMNS FROM
    '.TABLE_MEMBER;
$result= mysqli_query ($connection, $query) or die (debug_print ("ERROR: 784281 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$member_columns_array = array ();
while ($row = mysqli_fetch_object ($result))
  {
    array_push ($member_columns_array, $row->Field);
    // Get an array of all available auth_types
    if ($row->Field == 'auth_type')
      {
        // $row->Type will give something like this:
        // set('member','producer','route_admin','cashier','member_admin','site_admin')
        // so substr ($row->Type, 5, 2) removes the first five and last two characters
        // leaving the split to operate on the intermediate ',' strings.
        $auth_types_array = array ();
        $auth_types_array = explode ("','", substr ($row->Type, 5, -2));
      }
  }

// Validate the field_name
// Ideally this would be dynamically built, but that would require another query
// so this is lighter, though less robust.

if (! in_array ($field_name, $member_columns_array))
  {
    echo 'Invalid field';
    exit (0);
  }

// Get the current value for that field
$query = '
  SELECT
    '.mysqli_real_escape_string ($connection, $field_name).'
  FROM
    '.TABLE_MEMBER.'
  WHERE
    member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';

$result= mysqli_query ($connection, $query) or die (debug_print ("ERROR: 378994 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
  {
    $old_value = $row[$field_name];
  }

// Only update if there is a change
if ($old_value != $new_value)
  {
    // Update the field with the new value
    $query = '
      UPDATE
        '.TABLE_MEMBER.'
      SET
        '.mysqli_real_escape_string ($connection, $field_name).' = "'.mysqli_real_escape_string ($connection, $new_value).'"
      WHERE
        member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';

    $result= mysqli_query ($connection, $query) or die (debug_print ("ERROR: 312836 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    echo 'Changed value: '.$old_value;
  }
else
  {
    echo 'Not changed';
  }

// Return an informative message
