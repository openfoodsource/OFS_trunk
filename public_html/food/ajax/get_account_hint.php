<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

// Get hints for delivery_id boxen
if ($_REQUEST['action'] == 'get_delivery_date')
  {
    if ($_REQUEST['query'])
      {
        $escaped_search = mysql_real_escape_string($_REQUEST['query']);
        $combined_accounts = array ();
        $query = '
          SELECT
            delivery_id,
            date_open,
            date_closed,
            delivery_date
          FROM
            '.TABLE_ORDER_CYCLES.'
          WHERE
               DATE_FORMAT(delivery_date,"%c %e") LIKE "%'.$escaped_search.'%" /* "6 15" */
            OR DATE_FORMAT(delivery_date,"%c %d") LIKE "%'.$escaped_search.'%" /* "5 02" */
            OR DATE_FORMAT(delivery_date," %Y") LIKE "%'.$escaped_search.'%" /* " 2011" */
            OR DATE_FORMAT(delivery_date," %y") LIKE "%'.$escaped_search.'%"  /* " 11" */
            OR DATE_FORMAT(delivery_date,"%M") LIKE "%'.$escaped_search.'%" /* "January" */

            OR DATE_FORMAT(date_open,"%c %e") LIKE "%'.$escaped_search.'%" /* "6 15" */
            OR DATE_FORMAT(date_open,"%c %d") LIKE "%'.$escaped_search.'%" /* "5 02" */
            OR DATE_FORMAT(date_open," %Y") LIKE "%'.$escaped_search.'%" /* " 2011" */
            OR DATE_FORMAT(date_open," %y") LIKE "%'.$escaped_search.'%"  /* " 11" */
            OR DATE_FORMAT(date_open,"%M") LIKE "%'.$escaped_search.'%" /* "January" */

            OR DATE_FORMAT(date_closed,"%c %e") LIKE "%'.$escaped_search.'%" /* "6 15" */
            OR DATE_FORMAT(date_closed,"%c %d") LIKE "%'.$escaped_search.'%" /* "5 02" */
            OR DATE_FORMAT(date_closed," %Y") LIKE "%'.$escaped_search.'%" /* " 2011" */
            OR DATE_FORMAT(date_closed," %y") LIKE "%'.$escaped_search.'%"  /* " 11" */
            OR DATE_FORMAT(date_closed,"%M") LIKE "%'.$escaped_search.'%" /* "January" */

            OR CONCAT("d", delivery_id) = "'.$escaped_search.'" /* Like d87 */
            OR CONCAT("delivery:", delivery_id) = "'.$escaped_search.'" /* Like delivery:87 */
          ORDER BY delivery_id DESC
          LIMIT 20';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 574093 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        while ($row = mysql_fetch_array($result))
          {
            // Check a few of the combinations and save the first one that matches
            $delivery_description1a = '#'.$row['delivery_id'].' delivery: '.date ('n j Y', strtotime($row['delivery_date']));
            $delivery_description1b = '#'.$row['delivery_id'].' delivery: '.date ('n d y', strtotime($row['delivery_date']));
            $delivery_description1c = '#'.$row['delivery_id'].' delivery: '.date ('d F Y', strtotime($row['delivery_date']));
            $delivery_description1d = '#'.$row['delivery_id'].' delivery: '.date ('d M y', strtotime($row['delivery_date']));
            $delivery_description2a = '#'.$row['delivery_id'].' open: '.date ('n j Y', strtotime($row['date_open']));
            $delivery_description2b = '#'.$row['delivery_id'].' open: '.date ('n d y', strtotime($row['date_open']));
            $delivery_description2c = '#'.$row['delivery_id'].' open: '.date ('d F Y', strtotime($row['date_open']));
            $delivery_description2d = '#'.$row['delivery_id'].' open: '.date ('d M y', strtotime($row['date_open']));
            $delivery_description3a = '#'.$row['delivery_id'].' close: '.date ('n j Y', strtotime($row['date_closed']));
            $delivery_description3b = '#'.$row['delivery_id'].' close: '.date ('n d y', strtotime($row['date_closed']));
            $delivery_description3c = '#'.$row['delivery_id'].' close: '.date ('d F Y', strtotime($row['date_closed']));
            $delivery_description3d = '#'.$row['delivery_id'].' close: '.date ('d M y', strtotime($row['date_closed']));
            // This first one looks for e.g. "d23" and "delivery:23"
            if (stripos(' d'.$row['delivery_id'].' delivery:'.$row['delivery_id'], $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description1d;
            // The rest of these look for various parts of the delivery date, same as the query did
            elseif (stripos(' '.$delivery_description1a, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description1a;
            elseif (stripos(' '.$delivery_description1b, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description1b;
            elseif (stripos(' '.$delivery_description1c, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description1c;
            elseif (stripos(' '.$delivery_description1d, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description1d;
            // And for opening dates
            elseif (stripos(' '.$delivery_description2a, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description2a;
            elseif (stripos(' '.$delivery_description2b, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description2b;
            elseif (stripos(' '.$delivery_description2c, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description2c;
            elseif (stripos(' '.$delivery_description2d, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description2d;
            // And for closing dates
            elseif (stripos(' '.$delivery_description3a, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description3a;
            elseif (stripos(' '.$delivery_description3b, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description3b;
            elseif (stripos(' '.$delivery_description3c, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description3c;
            elseif (stripos(' '.$delivery_description3d, $_REQUEST['query'])) $matches[$row['delivery_id']] = $delivery_description3d;
          }
      }
    // Now sort and return the final values...
    if (count ($matches) > 0)
      {
//        asort($matches);
        $response = '{
          query:"'.$_REQUEST['query'].'",
          suggestions:["'.implode ('","', array_values ($matches)).'"],
          data:["'.implode ('","', array_keys ($matches)).'"]
          }';
        echo $response;
        exit (0);
      }
    else
      {
        $response = '{
          query:"'.$_REQUEST['query'].'",
          suggestions:["Garbled Query"],
          data:["no result"]
          }';
        echo $response;
        exit (0);
      }
  }

if ($_REQUEST['action'] == 'get_account_hint')
  {
    if ($_REQUEST['query'])
      {
        $escaped_search = mysql_real_escape_string($_REQUEST['query']);
        $combined_accounts = array ();
        // Queue up all the queries and run them in parallel to intermix the results... then sort.
        $query_producer = '
          SELECT
            producer_id,
            preferred_name,
            first_name,
            last_name,
            first_name_2,
            last_name_2,
            '.TABLE_PRODUCER.'.business_name
          FROM
            '.TABLE_PRODUCER.'
          LEFT JOIN '.TABLE_MEMBER.' USING (member_id)
          WHERE
            '.TABLE_PRODUCER.'.business_name LIKE "%'.$escaped_search.'%"
            OR preferred_name LIKE "%'.$escaped_search.'%"
            OR CONCAT_WS(" ", first_name, last_name, first_name_2, last_name_2) LIKE "%'.$escaped_search.'%"
            OR CONCAT("p", producer_id) = "'.$escaped_search.'" /* Like p64 */
            OR CONCAT("producer:", producer_id) = "'.$escaped_search.'" /* Like producer:64 */
          ORDER BY '.TABLE_PRODUCER.'.business_name, last_name
          LIMIT 20';
        $query_member = '
          SELECT
            member_id,
            preferred_name,
            first_name,
            last_name,
            first_name_2,
            last_name_2,
            business_name
          FROM
            '.TABLE_MEMBER.'
          WHERE
            preferred_name LIKE "%'.$escaped_search.'%"
            OR CONCAT_WS(" ", first_name, last_name, first_name_2, last_name_2) LIKE "%'.$escaped_search.'%"
            OR business_name LIKE "%'.$escaped_search.'%"
            OR CONCAT("m", member_id) = "'.$escaped_search.'" /* Like m64 */
            OR CONCAT("member:", member_id) = "'.$escaped_search.'" /* Like member:64 */
          ORDER BY preferred_name, last_name
          LIMIT 20';
        $query_tax = '
          SELECT
            tax_id,
            region_code,
            region_name,
            postal_code
          FROM
            '.NEW_TABLE_TAX_RATES.'
          WHERE
            region_code LIKE "%'.$escaped_search.'%"
            OR region_name LIKE "%'.$escaped_search.'%"
            OR postal_code LIKE "%'.$escaped_search.'%"
          ORDER BY region_name, postal_code
          LIMIT 20';
        $query_internal = '
          SELECT
            account_id,
            account_number,
            sub_account_number,
            description
          FROM
            '.NEW_TABLE_ACCOUNTS.'
          WHERE
            CONCAT_WS(" ", account_number, sub_account_number) LIKE "%'.$escaped_search.'%"
            OR description LIKE "%'.$escaped_search.'%"
            OR CONCAT("internal:", account_id) = "'.$escaped_search.'"
            OR CONCAT("i", account_id) = "'.$escaped_search.'"
          ORDER BY account_number
          LIMIT 20';
        $result_producer = mysql_query($query_producer, $connection) or die(debug_print ("ERROR: 869373 ", array ($query_producer,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//        $how_many_producers = mysql_num_rows($result_producer);
        $result_member = mysql_query($query_member, $connection) or die(debug_print ("ERROR: 027325 ", array ($query_member,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//        $how_many_members = mysql_num_rows($result_member);
        $result_tax = mysql_query($query_tax, $connection) or die(debug_print ("ERROR: 896274 ", array ($query_tax,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//        $how_many_taxes = mysql_num_rows($result_tax);
        $result_internal = mysql_query($query_internal, $connection) or die(debug_print ("ERROR: 893582 ", array ($query_internal,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//        $how_many_internals = mysql_num_rows($result_internal);
        $wait_for_all_four = 0;
        // Query producers, members, taxes, internal accounts one-at-a-time until there are either a total
        // of 20 results or all four come up empty.
        while (count($combined_accounts) < 20 && $wait_for_all_four < 4)
          {
            if ($row_producer = mysql_fetch_array($result_producer))
              {
                // Check a few of the combinations and save the first one that matches
                // The ' ' [space] is prepended to return true without consideration of a '0' [zero] return position
                $producer_description = 'producer:'.$row_producer['producer_id'].' '.$row_producer['business_name'];
                if (stripos(' '.$row_producer['business_name'], $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = $producer_description;
                elseif (stripos(' '.$row_producer['preferred_name'], $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = $producer_description;
                elseif (stripos(' '.$first_last, $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = $producer_description;
                elseif (stripos(' '.$first_last2, $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = $producer_description;
                elseif (stripos(' p'.$row_producer['producer_id'], $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = $producer_description;
                elseif (stripos(' producer:'.$row_producer['producer_id'], $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = $producer_description;
              }
            else
              {
                $wait_for_producer = 1;
              }
            if ($row_member = mysql_fetch_array($result_member))
              {
                // Check a few of the combinations and save the first one that matches
                // The ' ' [space] is prepended to return true without consideration of a '0' [zero] return position
                $member_description = 'member:'.$row_member['member_id'].' '.$row_member['preferred_name'];
                if (stripos(' '.$row_member['preferred_name'], $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = $member_description;
                elseif (stripos(' '.$first_last, $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = $member_description;
                elseif (stripos(' '.$first_last2, $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = $member_description;
                elseif (stripos(' '.$row_member['business_name'], $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = $member_description;
                elseif (stripos(' m'.$row_member['member_id'], $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = $member_description;
                elseif (stripos(' member:'.$row_member['member_id'], $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = $member_description;
              }
            else
              {
                $wait_for_member = 1;
              }
            if ($row_tax = mysql_fetch_array($result_tax))
              {
                // Check a few of the combinations and save the first one that matches
                // The ' ' [space] is prepended to return true without consideration of a '0' [zero] return position
                $tax_description = 'tax:'.$row_tax['tax_id'].' '.$row_tax['region_name'].' ('.$row_tax['postal_code'].') '.$row_tax['region_code'].'';
                if (stripos(' '.$row_tax['region_name'], $_REQUEST['query'])) $combined_accounts['tax:'.$row_tax['tax_id']] = $tax_description;
                elseif (stripos(' '.$row_tax['postal_code'], $_REQUEST['query'])) $combined_accounts['tax:'.$row_tax['tax_id']] = $tax_description;
                elseif (stripos(' '.$row_tax['region_code'], $_REQUEST['query'])) $combined_accounts['tax:'.$row_tax['tax_id']] = $tax_description;
                elseif (stripos(' tax:'.$row_tax['region_code'], $_REQUEST['query'])) $combined_accounts['tax:'.$row_tax['tax_id']] = $tax_description;
              }
            else
              {
                $wait_for_tax = 1;
              }
            if ($row_internal = mysql_fetch_array($result_internal))
              {
                // Check a few of the combinations and save the first one that matches
                // The ' ' [space] is prepended to return true without consideration of a '0' [zero] return position
                $account_description = 'internal:'.$row_internal['account_id'].' '.$row_internal['account_number'].' '.$row_internal['sub_account_number'].' '.$row_internal['description'];
                if (stripos(' '.$row_internal['description'], $_REQUEST['query'])) $combined_accounts['internal:'.$row_internal['account_id']] = $account_description;
                elseif (stripos(' '.$row_internal['account_number'], $_REQUEST['query'])) $combined_accounts['internal:'.$row_internal['account_id']] = $account_description;
                elseif (stripos(' '.$row_internal['sub_account_number'], $_REQUEST['query'])) $combined_accounts['internal:'.$row_internal['account_id']] = $account_description;
                elseif (stripos(' i'.$row_internal['account_id'], $_REQUEST['query'])) $combined_accounts['internal:'.$row_internal['account_id']] = $account_description;
                elseif (stripos(' internal:'.$row_internal['account_id'], $_REQUEST['query'])) $combined_accounts['internal:'.$row_internal['account_id']] = $account_description;
              }
            else
              {
                $wait_for_internal = 1;
              }
            $wait_for_all_four = $wait_for_producer + $wait_for_member + $wait_for_tax + $wait_for_internal;
          }
        // Now sort and return the final values...
        asort($combined_accounts);
        $response = '{
          query:"'.$_REQUEST['query'].'",
          suggestions:["'.implode ('","', array_values ($combined_accounts)).'"],
          data:["'.implode ('","', array_keys ($combined_accounts)).'"]
          }';
        echo $response;
        exit (0);
      }
    else
      {
        $response = '{
          query:"'.$_REQUEST['query'].'",
          suggestions:["Garbled Query"],
          data:["no result"]
          }';
        echo $response;
        exit (0);
      }
  }
?>