<?php
// If the producer exists, the subroutine returns producer information.
// Call with: get_producer ($producer_id)
function get_producer ($producer_id)
  {
    // Expose additional parameters as they become needed.
    global $connection;
    $producer_fields = array (
      'producer_id',
      'producer_link',
      'pending',
      'member_id',
      'business_name',
      'producer_fee_percent',
      'unlisted_producer',
      // 'producttypes',
      // 'about',
      // 'ingredients',
      // 'general_practices',
      // 'highlights',
      // 'additional',
      // 'liability_statement',
      'pub_address',
      'pub_email',
      'pub_email2',
      'pub_phoneh',
      'pub_phonew',
      'pub_phonec',
      'pub_phonet',
      'pub_fax',
      'pub_web',
      // available_site_ids <----- also included with this information: e.g. 'GRNDI,LNC,OMA'
      );
    $query = '
      SELECT
        '.implode (",\n        ", $producer_fields).',
        (SELECT GROUP_CONCAT(site_id)
         FROM '.TABLE_AVAILABILITY.'
         WHERE producer_id = '.TABLE_PRODUCER.'.producer_id) AS available_site_ids
      FROM '.TABLE_PRODUCER.'
      WHERE producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
    $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 895053 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        return ($row);
      }
  }
