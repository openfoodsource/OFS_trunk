<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

if (isset($_GET['callback'])) $jsonp_callback = $_GET['callback'];
if (isset($_GET['account_type'])) $account_type = $_GET['account_type'];

// Get hints for delivery_id boxen
if ($_GET['action'] == 'get_hint' && isset ($_GET['query']))
  {
    $escaped_search = mysql_real_escape_string($_GET['query']);
    switch ($_GET['account_type'])
      {
        case "member":
          $query = '
            SELECT
              member_id AS value,
              CONCAT(
                "#",
                member_id,
                " ",
                preferred_name,
                IF(business_name != "", CONCAT(" (", business_name, ")"), "")) AS label
            FROM
              '.TABLE_MEMBER.'
            WHERE
              preferred_name LIKE "%'.$escaped_search.'%"
              OR CONCAT_WS(" ", first_name, last_name, first_name_2, last_name_2) LIKE "%'.$escaped_search.'%"
              OR business_name LIKE "%'.$escaped_search.'%"
              OR CONCAT("member:", member_id) = "'.$escaped_search.'" /* Like member:64 */
            ORDER BY member_id, preferred_name, last_name
            LIMIT 20';
        break;

        case "producer":
          $query = '
            SELECT
              producer_id AS value,
              CONCAT(
                "#",
                producer_id,
                " ",
                '.TABLE_PRODUCER.'.business_name) AS label
            FROM
              '.TABLE_PRODUCER.'
            LEFT JOIN '.TABLE_MEMBER.' USING (member_id)
            WHERE
              '.TABLE_PRODUCER.'.business_name LIKE "%'.$escaped_search.'%"
              OR preferred_name LIKE "%'.$escaped_search.'%"
              OR CONCAT_WS(" ", first_name, last_name, first_name_2, last_name_2) LIKE "%'.$escaped_search.'%"
            ORDER BY '.TABLE_PRODUCER.'.business_name
            LIMIT 20';
        break;

        case "internal":
          $query = '
            SELECT
              account_id AS value,
              CONCAT(
                account_number,
                "-",
                sub_account_number,
                ": ",
                description
                ) AS description,
              description AS label
            FROM
              '.NEW_TABLE_ACCOUNTS.'
            WHERE
              CONCAT_WS(" ", account_number, sub_account_number) LIKE "%'.$escaped_search.'%"
              OR description LIKE "%'.$escaped_search.'%"
            ORDER BY account_number
            LIMIT 20';
        break;

        case "tax":
          $query = '
            SELECT
              region_type,
              region_code AS value,
              CONCAT(
                "[",
                region_code,
                "] ",
                region_name,
                ": ",
                postal_code
                ) AS description,
              CONCAT(
                region_code,
                " ",
                region_type,
                ": ",
                region_name
                ) AS label
            FROM
              '.NEW_TABLE_TAX_RATES.'
            WHERE
              region_code LIKE "%'.$escaped_search.'%"
              OR region_name LIKE "%'.$escaped_search.'%"
              OR postal_code LIKE "%'.$escaped_search.'%"
            GROUP BY
              region_code
            ORDER BY
              FIND_IN_SET(region_type, "state,province,county,district,city"),
              region_name
            LIMIT 20';
        break;
      }
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 754932 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $response = array ();
    while ($row = mysql_fetch_array($result))
      {
        // $description = $row['description'];
        $value = $row['value'];
        $label = $row['label'];
        array_push ($response, json_encode(array("type" => $account_type,"value" => $value, "label" => $label)));
      }
    $jsonp = $jsonp_callback.'(['.
      implode (', ', array_values ($response)).
      '])';
    echo $jsonp;
  }
else
  {
    echo $jsonp_callback.'([])';
  }

?>