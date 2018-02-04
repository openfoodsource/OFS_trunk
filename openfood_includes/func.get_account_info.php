<?php

// Subroutines for getting information from accounts [member|producer|tax|internal]

// This function will return an [internal] account_id based upon the requested source or target key
// and if nothing is found, it will use the value of text_key
// Input data is [source_key|target_key][text_key]. If the key does not exist, a new account number is generated
// for the text_key and returned. Multiple matches generate a fatal error.
function get_internal_account_id ($requested_key, $text_key)
  {
    global $connection;
    $query = '
      SELECT
        (SELECT account_id
        FROM '.NEW_TABLE_ACCOUNTS.'
        WHERE internal_key = "'.mysqli_real_escape_string ($connection, $requested_key).'") AS first_choice,
        (SELECT account_id
        FROM '.NEW_TABLE_ACCOUNTS.'
        WHERE internal_key = "'.mysqli_real_escape_string ($connection, $text_key).'") AS second_choice';
    $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 752907 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $num_rows = mysqli_num_rows ($result);
    // Ideally, we get one result and return it
    if ($num_rows == 1)
      {
        $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
        if (is_numeric ($row['first_choice']))
          return ($row['first_choice']);
        elseif (is_numeric ($row['second_choice']))
          return ($row['second_choice']);
      }
    // But if there is no value, then we add a blank entry to the table
    elseif ($num_rows == 0 && strlen ($text_key) > 0)
      {
        $query_insert = '
          INSERT INTO '.NEW_TABLE_ACCOUNTS.'
          SET
            internal_key = "'.mysqli_real_escape_string ($connection, $text_key).'",
            description = "auto-generated account for: '.mysqli_real_escape_string ($connection, $text_key).'"';
        $result_insert = mysqli_query ($connection, $query_insert) or die (debug_print ("ERROR: 702912 ", array ($query_insert, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        return (mysqli_insert_id ($connection));
      }
    // And if there are multiple rows, then that is a problem
    else
      {
        die (debug_print ("ERROR: 803283 ", "Multiple instances for $text_key in '.NEW_TABLE_ACCOUNTS.' table", basename(__FILE__).' LINE '.__LINE__));
      }
  }

// This function takes an account_type and key and returns the user-friendly
// name for the transaction from the appropriate table. The returned value is:
//    ACCOUNT TYPE     RETURNS                                       EXAMPLE
//   --------------   -----------------------------------------     ----------------------
//   'member'         [preferred_name]                              'Goofy Goose'
//   'producer'       [business_name]                               'Quack Duckworks'
//   'tax'            [postal_code] [region_name] [region_type]     '97999 Pacifica state'
//   'internal'       [description]                                 'Membership account'
function get_account_name ($account_type, $account_key)
  {
    global $connection;
    switch ($account_type)
      {
        case 'member':
        $query = '
          SELECT preferred_name AS name
          FROM '.TABLE_MEMBER.'
          WHERE member_id = "'.mysqli_real_escape_string ($connection, $account_key).'"';
          break;
        case 'producer':
        $query = '
          SELECT business_name AS name
          FROM '.TABLE_PRODUCER.'
          WHERE producer_id = "'.mysqli_real_escape_string ($connection, $account_key).'"';
          break;
        case 'tax':
        $query = '
          SELECT CONCAT_WS(" ", postal_code, region_name, region_type) AS name
          FROM '.NEW_TABLE_TAX_RATES.'
          WHERE tax_id = "'.mysqli_real_escape_string ($connection, $account_key).'"';
          break;
        case 'internal':
        $query = '
          SELECT description AS name
          FROM '.NEW_TABLE_ACCOUNTS.'
          WHERE account_id = "'.mysqli_real_escape_string ($connection, $account_key).'"';
          break;
      }
    $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 753093 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        return ($row['name']);
      }
    else
      {
        die (debug_print ("ERROR: 786304 ", "Account $account_type::$account_key was not found", basename(__FILE__).' LINE '.__LINE__));
      }
  }
