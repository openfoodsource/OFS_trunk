<?php

// Include commonly-used functions
include_once ('general_functions.php');

// Access parameters for the database and OFS configuration table
$database_config = array (
  'db_host'         => 'localhost',                          // Enter the db host
  'db_user'         => 'openfood_user',                      // Enter the username for db access
  'db_pass'         => 'OpunFud DayTaBaze PazzWoorD',        // Enter the password for db access
  'db_name'         => 'openfoodsource_ofs',                 // Enter the database name
  'db_prefix'       => 'ofs_',                               // This is probably blank
  'openfood_config' => 'configuration'                       // Points to configuration table in database
  );

// Include override values, but only if the file exists
@include_once ("config_override.php"); 

// Establish database connection
connect_to_database ($database_config);

// Set all additional configurations from the database
get_configuration ($database_config, $override_config);

// Set the time zone
date_default_timezone_set (LOCAL_TIME_ZONE);

// Set error reporting level
ini_set('display_errors', DEBUG); 


// Set error reporting level
// Convert the comma-separated ERROR_FLAGS into boolean constants and bitwise-or them together
if (!is_int (ERROR_FLAGS)) $error_flags = array_reduce (array_map ('constant', explode (',', ERROR_FLAGS)), function($a, $b) {return $a | $b;}, 0);
error_reporting ($error_flags);

?>
