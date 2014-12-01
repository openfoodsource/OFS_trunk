<?php

include_once ('func.get_account_info.php');

// This function is used to add new transactions to the ledger. Transactions will only be added  //
// if the data is new (other than the added_by value). Values for the new transaction are stored //
// in the $data array with field names the same as in the database:                              //
//                                                                                               //
//    transaction_id    target_type    text_key             replaced_by   bpid            pvid   //
//    source_type       target_key     effective_datetime   timestamp     site_id             //
//    source_key        amount         posted_by            basket_id     delivery_id            //
//                                                                                               //
// The data['match_keys'] field contains an array of keys that are used to narrow the            //
// query target. For example, if $data['match_keys'] = array ('target_type', 'target_id')        //
// then the transaction where target_type and target_id match values in $data['target_type']     //
// and $data['target_key'] will be identified for this update. Obviously, in that case,          //
// many matching transactions will be returned, so it is not a good choice.                      //
//                                                                                               //
// The $data['messages'] field can contain an associative arry of messages to post as            //
// linked to this transaction.                                                                   //
//                                                                                               //
// NOTE: The $data['match_keys'] should sufficiently narrow the query to a SINGLE result.        //




function basket_item_to_ledger ($new_data)
  {
// debug_print ("INFO: 1 ", $new_data, basename(__FILE__).' LINE '.__LINE__);
    // Begin by checking the ledger for prior entries like this one
    $old_data = search_ledger($new_data);
    // What data is already in the ledger?
    if ($old_data == 0)
      {
        // Nothing to report, so we can post this data as new
        $new_transaction_id = add_to_ledger ($new_data);
      }
    elseif (is_array ($old_data))
      {
        // One ledger entry found, so compare it...
        $different_fields = compare_ledger_fields ($new_data, $old_data);
        // Need to do an update if there are changed messages
        // Or if there is any changed field besides replaced_by
        if (count($new_data['messages']) ||
          (count($different_fields) >= 1 && ! in_array('replaced_by', $different_fields)) ||
          (count($different_fields) > 2 && in_array('replaced_by', $different_fields)))
          {
            // Add the new transaction
            $new_transaction_id = add_to_ledger ($new_data);
            // And delete the old one
            replace_ledger_transaction ($old_data['transaction_id'], $new_transaction_id);
          }
      }
    else
      {
        // Multiple ledger entries -- should not happen
        die(debug_print ("ERROR: 750820 ", 'Multiple ['.$old_data.'] existing basket_item entries already in ledger', basename(__FILE__).' LINE '.__LINE__));
      }
  }

// This function will insert all defined values (array values in $data) as a row into the ledger and also
// any associated messages from the messages[] array. It will return the transaction_id for the row inserted.
function add_to_ledger($data)
  {
// debug_print ("INFO: 2 ", $data, basename(__FILE__).' LINE '.__LINE__);
    global $connection;
    // Create the insert "SET" clauses
    $ledger_fields = array (
      // 'transaction_id' -- Do not include transaction_id for INSERT queries
      'transaction_group_id',
      'source_type',
      'source_key',
      'target_type',
      'target_key',
      'amount',
      'text_key',
      'effective_datetime',
      'posted_by',
      'replaced_by',
      'timestamp',
      'basket_id',
      'bpid',
      'site_id',
      'delivery_id',
      'pvid');
    $query_set_array = array ();
    // If we do not already have a source_key, then lookup account_number for internal source_type
    if ($data['source_type'] == 'internal' && ! is_numeric($data['source_key']))
      {
        $data['source_key'] = get_internal_account_id ($data['source_key'], $data['text_key']);
      }
    // If we do not already have a target_key, then lookup account_number for internal target_type
    if ($data['target_type'] == 'internal' && ! is_numeric($data['target_key']))
      {
        $data['target_key'] = get_internal_account_id ($data['target_key'], $data['text_key']);
      }
    // If effective_datetime is not set, then use the current datetime
    if (strtotime ($data['effective_datetime']))
      {
        $data['effective_datetime'] = date ('Y-m-d H:i:s', strtotime ($data['effective_datetime']));
      }
    else
      {
        $data['effective_datetime'] = date ('Y-m-d H:i:s', time());
      }
    // Cycle through all the possible fields
    foreach ($ledger_fields as $field)
      {
        // Add "SET" values to any fields that have data to post except the transaction_id,
        // which is auto-increment and may not be specified on an INSERT.
        if ($data[$field])
          {
            $query_set = $field.' = "'.mysql_real_escape_string($data[$field]).'"';
            array_push ($query_set_array, $query_set);
          }
      }
    // Combine the "SET" clauses with commas
    $query_set = implode(",\n        ", $query_set_array);
    $query = '
      INSERT INTO '.NEW_TABLE_LEDGER.'
      SET
        '.$query_set;
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 850302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $new_transaction_id = mysql_insert_id();
    $affected_rows = mysql_affected_rows();
    if ($affected_rows != 1)
      {
        die(debug_print ("ERROR: 860324 ", "Data error. Updates ($affected_rows) not equal to one", basename(__FILE__).' LINE '.__LINE__));
      }
    // Now go post any transaction messages
    add_transaction_messages ($new_transaction_id, $data['messages']);
    return ($new_transaction_id);
  }


// This function will link an old transaction to a new transaction (i.e. replace the 'replaced_by'
// field with the transaction_id for the new transaction.
function replace_ledger_transaction ($old_transaction_id, $new_transaction_id)
  {
// debug_print ("INFO: 3 ", array ($old_transaction_id, $new_transaction_id), basename(__FILE__).' LINE '.__LINE__);
    global $connection;
    $query = '
      UPDATE '.NEW_TABLE_LEDGER.'
      SET replaced_by = "'.mysql_real_escape_string($new_transaction_id).'"
      WHERE transaction_id = "'.mysql_real_escape_string($old_transaction_id).'"';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 834043 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if (mysql_affected_rows() != 1)
      {
        die(debug_print ("ERROR: 270433 ", "Data error. No prior transaction to update", basename(__FILE__).' LINE '.__LINE__));
      }
  }


// This function will search the ledger based on information in the [match_keys] array and return
// either 0 (no results found), a resulting array if only one result found, or a number of results
// returned matching. NOTE: This only searches for NULL replaced_by values.
function search_ledger(array $data)
  {
// debug_print ("INFO: 4 ", $data, basename(__FILE__).' LINE '.__LINE__);
    global $connection;
    $ledger_fields = array (
      'transaction_id',
      'transaction_group_id',
      'source_type',
      'source_key',
      'target_type',
      'target_key',
      'amount',
      'text_key',
      'effective_datetime',
      'posted_by',
      'replaced_by',
      'timestamp',
      'basket_id',
      'bpid',
      'site_id',
      'delivery_id',
      'pvid');
    // If we do not already have a source_key, then lookup account_number for internal source_type
    if ($data['source_type'] == 'internal' && ! is_numeric($data['source_key']))
      {
        $data['source_key'] = get_internal_account_id ($data['source_key'], $data['text_key']);
      }
    // If we do not already have a target_key, then lookup account_number for internal target_type
    if ($data['target_type'] == 'internal' && ! is_numeric($data['target_key']))
      {
        $data['target_key'] = get_internal_account_id ($data['target_key'], $data['text_key']);
      }
    // Is the requested transaction pretty much the same as an existing one?
    // Allow different amount and a change in the source OR target account.
    // IGNORE transaction_id, posted_by, and timestamp. Only look at cases
    // where replaced_by is NULL (i.e. active transactions, not historical).
    if (is_array($data['match_keys']))
      {
        $query_where_array = array ();
        // Look at all the possible keys and see which ones we want to
        // match for existing transactions (to replace)
        foreach ($ledger_fields as $field)
          {
            // And add "WHERE" constraints to the ones indicated by the "match_keys" array
            if (in_array ($field, $data['match_keys']) && $field != 'replaced_by')
              {
                // Build an array of the "WHERE" clauses
                $query_where = $field.' = "'.mysql_real_escape_string($data[$field]).'"'."\n        ";
                array_push ($query_where_array, $query_where);
              }
          }
        // Combine the "WHERE" clauses with "AND" conditions
        $query_where = implode ('AND ', $query_where_array);
      }
    // Better be constraining the query somehow...
    else
      {
        die(debug_print ("ERROR: 753032 ", "Query error. No constraint fields selected", basename(__FILE__).' LINE '.__LINE__));
      }
    $query = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      WHERE
        '.$query_where.'
        AND replaced_by IS NULL';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 752002 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $num_rows = mysql_num_rows($result);
    // no matches: return 0
    if ($num_rows == 0) return (0);
    // exactly one result: return the data
    elseif ($num_rows == 1)
      {
        $new_data = mysql_fetch_array ($result);
        // Since we return data, better get the messages also
        // Maybe this should be simplified to just use the transaction_id
        $query_messages = '
          SELECT 
            '.NEW_TABLE_MESSAGE_TYPES.'.description,
            '.NEW_TABLE_MESSAGES.'.message
          FROM
            '.NEW_TABLE_MESSAGE_TYPES.'
          LEFT JOIN
            '.NEW_TABLE_MESSAGES.' USING(message_type_id)
          WHERE
            key1_target = "ledger.transaction_id"
            AND referenced_key1 = "'.mysql_real_escape_string($new_data['transaction_id']).'"';
        $result_messages = mysql_query($query_messages, $connection) or die(debug_print ("ERROR: 678302 ", array ($query_messages,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $messages = array ();
        while ($row_messages = mysql_fetch_array ($result_messages))
          {
            $messages[$row_messages['description']] = $row_messages['message'];
          }
        $new_data['messages'] = $messages;
        return ($new_data);
      }
    // many results: return how many
    else return ($num_rows);
  }


// Compare two ledger data arrays and return an array of fields that are different between them.
function compare_ledger_fields ($data1, $data2)
  {
// debug_print ("INFO: 5 ", array ($data1, $data2), basename(__FILE__).' LINE '.__LINE__);
    $ledger_fields = array (
      'transaction_id',
      'transaction_group_id',
      'source_type',
      'source_key',
      'target_type',
      'target_key',
      'amount',
      'text_key',
      'effective_datetime',
      'posted_by',
      'replaced_by',
      'timestamp',
      'basket_id',
      'bpid',
      'site_id',
      'delivery_id',
      'pvid');
    // If we do not already have a source_key, then lookup account_number for internal source_type
    if ($data1['source_type'] == 'internal' && ! is_numeric($data1['source_key']))
      {
        $data1['source_key'] = get_internal_account_id ($data1['source_key'], $data1['text_key']);
      }
    if ($data2['source_type'] == 'internal' && ! is_numeric($data2['source_key']))
      {
        $data2['source_key'] = get_internal_account_id ($data2['source_key'], $data2['text_key']);
      }
    // If we do not already have a target_key, then lookup account_number for internal target_type
    if ($data1['target_type'] == 'internal' && ! is_numeric($data1['target_key']))
      {
        $data1['target_key'] = get_internal_account_id ($data1['target_key'], $data1['text_key']);
      }
    if ($data2['target_type'] == 'internal' && ! is_numeric($data2['target_key']))
      {
        $data2['target_key'] = get_internal_account_id ($data['target_key'], $data['text_key']);
      }
    $transaction_changed = false;
    $difference_array = array();
    // Check the ledger fields (excluding "posted_by") to see if this is just the same data
    foreach ($ledger_fields as $field)
      {
        // Do not care if the only change is 'posted_by' -- unless there is a message
        if ($data1[$field] != $data2[$field])
          {
            array_push ($difference_array, $field);
          }
      }
    // Compare messages fields separately
    $messages1 = (array) $data1['messages'];
    $messages2 = (array) $data2['messages'];
    $unique_message_keys = array_unique(array_merge(array_keys ($messages1), array_keys ($messages2)));
    foreach ($unique_message_keys as $message_key)
      {
        if ($message1[$message_key] != $message2[$message_key])
          {
            array_push ($difference_array, 'messages');
          }
      }
    return ($difference_array);
  }


// Post messages to a transaction. The messages are contained in an array, keyed by message_type.
// Function will return the number of messages changed/added.
function add_transaction_messages ($transaction_id, $messages)
  {
// debug_print ("INFO: 6 ", array ($transaction_id, $messages), basename(__FILE__).' LINE '.__LINE__);
    global $connection;
    // Are there any messages to add?
    if (is_array ($messages))
      {
        $count = 0;
        foreach ($messages as $message_type => $message)
          {
            // If there is a message, then add the message or replace an existing one
            if (strlen ($message) > 0)
              {
                // Use [0]:orphaned message in case the description is not found
                $query = '
                  REPLACE INTO '.NEW_TABLE_MESSAGES.'
                  SET
                    message = "'.mysql_real_escape_string($message).'",
                    message_type_id = 
                      COALESCE((
                        SELECT message_type_id
                        FROM '.NEW_TABLE_MESSAGE_TYPES.'
                        WHERE description = "'.mysql_real_escape_string($message_type).'"
                        LIMIT 1
                        ),0),
                    referenced_key1 = "'.mysql_real_escape_string($transaction_id).'"';
              }
            // Otherwise, delete any existing message of this variety
            else
              {
                $query = '
                  DELETE FROM '.NEW_TABLE_MESSAGES.'
                  WHERE
                    message_type_id = 
                      COALESCE((
                        SELECT message_type_id
                        FROM '.NEW_TABLE_MESSAGE_TYPES.'
                        WHERE description = "'.mysql_real_escape_string($message_type).'"
                        LIMIT 1
                        )
                      ,0)
                    AND referenced_key1 = "'.mysql_real_escape_string($transaction_id).'"';
              }
            $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 850234 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            $count ++;
          }
      }
    // Return the number of messages changed
    return ($count);
  }
?>