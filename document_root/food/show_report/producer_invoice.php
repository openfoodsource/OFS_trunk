<?php
valid_auth('member');

// Do not paginate invoices under any circumstances (web pages)
$per_page = 1000000;

// Assign page tab and title information
$page_title_html = '<span class="title">Basket</span>';
$page_subtitle_html = '<span class="subtitle">Basket Items</span>';
$page_title = 'Basket: Basket Items';
$page_tab = 'shopping_panel';

// Set display groupings
$major_product = 'product_id';
$major_product_prior = $major_product.'_prior';
$minor_product = 'member_id';
$minor_product_prior = $minor_product.'_prior';
$show_major_product = true;
$show_minor_product = true;
$row_type = 'product'; // Reflects the detail to show on each row (vs. what gets featured in the header)

// Assign template file
$template_type = 'customer_invoice';

// This single-row content is unique for the entire report (used in the header, footer, etc)
$query_unique = '
  SELECT
    '.TABLE_PRODUCER.'.producer_link,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_MEMBER.'.address_line1,
    '.TABLE_MEMBER.'.address_line2,
    '.TABLE_MEMBER.'.auth_type,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_MEMBER.'.city,
    '.TABLE_MEMBER.'.state,
    '.TABLE_MEMBER.'.zip,
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.email_address_2,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_MEMBER.'.home_phone,
    '.TABLE_MEMBER.'.mobile_phone,
    '.TABLE_MEMBER.'.work_phone,
    '.TABLE_MEMBER.'.fax,
    '.TABLE_MEMBER.'.work_address_line1,
    '.TABLE_MEMBER.'.work_address_line2,
    '.TABLE_MEMBER.'.work_city,
    '.TABLE_MEMBER.'.work_state,
    '.TABLE_MEMBER.'.work_zip,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_ORDER_CYCLES.'.msg_all,
    '.TABLE_ORDER_CYCLES.'.msg_bottom
  FROM
    ('.TABLE_PRODUCER.',
    '.TABLE_ORDER_CYCLES.')
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  WHERE
    '.TABLE_PRODUCER.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"
    AND '.TABLE_ORDER_CYCLES.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"';
$result_unique = mysqli_query ($connection, $query_unique) or die (debug_print ("ERROR: 368023 ", array ($query_unique, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
if ($row_unique = mysqli_fetch_array ($result_unique, MYSQLI_ASSOC))
  {
    $unique_data = (array) $row_unique;
  }
// Add the current view to the unique data set
$unique_data['view'] = $view;

// This multi-row content comprises the product body of the report
$query_product = '
  SELECT
    SQL_CALC_FOUND_ROWS
    DISTINCT('.NEW_TABLE_LEDGER.'.transaction_id),
    (CASE
      WHEN ('.NEW_TABLE_LEDGER.'.source_type = "producer" AND '.NEW_TABLE_LEDGER.'.source_key = "'.mysqli_real_escape_string ($connection, $producer_id_you).'") THEN -1
      WHEN ('.NEW_TABLE_LEDGER.'.target_type = "producer" AND '.NEW_TABLE_LEDGER.'.target_key = "'.mysqli_real_escape_string ($connection, $producer_id_you).'") THEN 1
      ELSE 0
    END) * amount AS amount,
    '.NEW_TABLE_LEDGER.'.text_key,
    '.NEW_TABLE_LEDGER.'.effective_datetime,
    '.NEW_TABLE_LEDGER.'.replaced_by,
    '.NEW_TABLE_LEDGER.'.timestamp,
    '.NEW_TABLE_LEDGER.'.basket_id,
    '.NEW_TABLE_LEDGER.'.bpid,
    '.NEW_TABLE_LEDGER.'.site_id,
    '.NEW_TABLE_LEDGER.'.delivery_id,
    '.NEW_TABLE_LEDGER.'.pvid,
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.product_version,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.NEW_TABLE_PRODUCTS.'.inventory_pull,
    '.NEW_TABLE_PRODUCTS.'.inventory_id,
    '.NEW_TABLE_PRODUCTS.'.product_description,
    '.NEW_TABLE_PRODUCTS.'.unit_price,
    '.NEW_TABLE_PRODUCTS.'.pricing_unit,
    '.NEW_TABLE_PRODUCTS.'.ordering_unit,
    '.NEW_TABLE_PRODUCTS.'.random_weight,
    '.NEW_TABLE_PRODUCTS.'.meat_weight_type,
    '.NEW_TABLE_PRODUCTS.'.minimum_weight,
    '.NEW_TABLE_PRODUCTS.'.maximum_weight,
    '.NEW_TABLE_PRODUCTS.'.extra_charge,
    '.NEW_TABLE_PRODUCTS.'.image_id,
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type,
    '.NEW_TABLE_PRODUCTS.'.confirmed,
    '.NEW_TABLE_PRODUCTS.'.taxable,
    '.NEW_TABLE_PRODUCTS.'.tangible,
    '.NEW_TABLE_PRODUCTS.'.sticky,
    '.NEW_TABLE_PRODUCTS.'.hide_from_invoice,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    '.NEW_TABLE_BASKET_ITEMS.'.product_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.checked_out,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_type,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.TABLE_MEMBER.'.member_id,
    '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent,
    '.TABLE_MEMBER.'.preferred_name,
    '.NEW_TABLE_BASKETS.'.site_id,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.delivery_postal_code,
    '.NEW_TABLE_BASKETS.'.customer_fee_percent,
    '.NEW_TABLE_BASKETS.'.checked_out,
    '.NEW_TABLE_SITES.'.hub_id,
    '.NEW_TABLE_SITES.'.delivery_type,
    '.NEW_TABLE_SITES.'.truck_code,
    '.NEW_TABLE_SITES.'.site_short,
    '.NEW_TABLE_SITES.'.site_long,
    '.NEW_TABLE_SITES.'.site_description,
    '.NEW_TABLE_MESSAGES.'1.message AS customer_message,
    '.NEW_TABLE_MESSAGES.'2.message AS product_message
  FROM
    '.NEW_TABLE_LEDGER.'
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(pvid)
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.'  USING(bpid)
  LEFT JOIN '.NEW_TABLE_BASKETS.'  ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
  LEFT JOIN '.TABLE_MEMBER.'  ON '.TABLE_MEMBER.'.member_id = '.NEW_TABLE_BASKETS.'.member_id
  LEFT JOIN '.NEW_TABLE_SITES.'  ON '.NEW_TABLE_SITES.'.site_id = '.NEW_TABLE_BASKETS.'.site_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' USING(subcategory_id)
  LEFT JOIN '.TABLE_CATEGORY.' USING(category_id)
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' USING(production_type_id)
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' USING(storage_id)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' ON ('.NEW_TABLE_BASKETS.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id)
  LEFT JOIN '.NEW_TABLE_MESSAGES.' '.NEW_TABLE_MESSAGES.'1 ON
    ( '.NEW_TABLE_MESSAGES.'1.referenced_key1 = '.NEW_TABLE_BASKET_ITEMS.'.bpid
    AND '.NEW_TABLE_MESSAGES.'1.message_type_id =
      (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "customer notes to producer")
    )
  LEFT JOIN '.NEW_TABLE_MESSAGES.' '.NEW_TABLE_MESSAGES.'2 ON
    ( '.NEW_TABLE_MESSAGES.'2.referenced_key1 = '.NEW_TABLE_LEDGER.'.transaction_id
    AND '.NEW_TABLE_MESSAGES.'2.message_type_id =
      (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "ledger_comment")
    )
  WHERE
    (('.NEW_TABLE_LEDGER.'.source_type = "producer"
        AND '.NEW_TABLE_LEDGER.'.source_key = "'.mysqli_real_escape_string ($connection, $producer_id_you).'")
      OR ('.NEW_TABLE_LEDGER.'.target_type = "producer"
        AND '.NEW_TABLE_LEDGER.'.target_key = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"))
    AND '.NEW_TABLE_LEDGER.'.replaced_by IS NULL
    AND '.NEW_TABLE_LEDGER.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
    AND '.NEW_TABLE_PRODUCTS.'.product_id IS NOT NULL
  ORDER BY
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.TABLE_MEMBER.'.member_id,
    FIND_IN_SET('.NEW_TABLE_LEDGER.'.text_key, "quantity cost,weight cost,extra charge,customer fee,producer fee,delivery cost") DESC';

// Set the accounting datetime limit (for constraining totals over time following the zero-date)
$constrain_accounting_datetime = '';
$constrain_effective_datetime = '';
if (defined ('ACCOUNTING_ZERO_DATETIME') && strlen (ACCOUNTING_ZERO_DATETIME) > 0)
  {
    $constrain_accounting_datetime = '
    AND '.TABLE_ORDER_CYCLES.'.date_closed > "'.ACCOUNTING_ZERO_DATETIME.'"';
    $constrain_effective_datetime = '
    AND IF('.NEW_TABLE_LEDGER.'.delivery_id IS NULL, '.NEW_TABLE_LEDGER.'.effective_datetime, '.TABLE_ORDER_CYCLES.'.delivery_date) > "'.ACCOUNTING_ZERO_DATETIME.'"';
  }

// Get the closing date for the last time this producer sold an item
$query_prior_closing = '
  SELECT
    date_closed,
    delivery_id,
    delivery_date
  FROM
    '.NEW_TABLE_BASKET_ITEMS.'
  LEFT JOIN
    '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
  LEFT JOIN
    '.NEW_TABLE_BASKETS.' USING(basket_id)
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"
    AND '.TABLE_ORDER_CYCLES.'.date_closed < (SELECT date_closed FROM '.TABLE_ORDER_CYCLES.' WHERE delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'")'.
    $constrain_accounting_datetime.'
  ORDER BY
    '.TABLE_ORDER_CYCLES.'.date_closed DESC
  LIMIT
    0,1';
$result_prior_closing = mysqli_query ($connection, $query_prior_closing) or die (debug_print ("ERROR: 754232 ", array ($query_prior_closing, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$and_since_prior_delivery_date = '';
$and_before_prior_delivery_date = '';
if ($row_prior_closing = mysqli_fetch_array ($result_prior_closing, MYSQLI_ASSOC))
  {
    $unique_data['prior_closing'] = $row_prior_closing['date_closed'];
    $unique_data['prior_delivery'] = $row_prior_closing['delivery_date'];
    $unique_data['prior_delivery_id'] = $row_prior_closing['delivery_id'];

    $and_since_prior_delivery_date = '
        (
          IF /* BEFORE THE CURRENT DELIVERY DATE */
            ('.NEW_TABLE_LEDGER.'.delivery_id IS NULL,
             '.NEW_TABLE_LEDGER.'.effective_datetime,
             '.TABLE_ORDER_CYCLES.'.delivery_date
            ) < "'.mysqli_real_escape_string ($connection, $unique_data['delivery_date']).'"
        AND
          IF /* AND SINCE (INCLUSIVE) THE PRIOR DELIVERY DATE FOR THIS CUSTOMER */
            ('.NEW_TABLE_LEDGER.'.delivery_id IS NULL,
             '.NEW_TABLE_LEDGER.'.effective_datetime,
             '.TABLE_ORDER_CYCLES.'.delivery_date
            ) >= "'.mysqli_real_escape_string ($connection, $row_prior_closing['delivery_date']).'"
        )';
    $and_before_prior_delivery_date = '
    /* ALL NON-ITEM CHARGES PRIOR TO PREVIOUS INVOICE DATE (PREFER USING DELIVERY DATE FOR EFFECTIVE_DATETIME) */
    AND IF('.NEW_TABLE_LEDGER.'.delivery_id IS NULL,
           '.NEW_TABLE_LEDGER.'.effective_datetime,
           '.TABLE_ORDER_CYCLES.'.delivery_date) <= "'.$row_prior_closing['delivery_date'].'"
    /* IS THE FOLLOWING "AND" NEEDED, OR REDUNDANT OF THE PRIOR "AND"
    AND
      (
        '.NEW_TABLE_LEDGER.'.effective_datetime < "'.mysqli_real_escape_string ($connection, $row_prior_closing['delivery_date']).'"
      OR
        '.NEW_TABLE_LEDGER.'.delivery_id <= "'.mysqli_real_escape_string ($connection, $row_prior_closing['delivery_id']).'"
      )
    /* DO NOT INCLUDE ANY PAYMENTS OR RECEIPTS FOR THE PRIOR CYCLE */
    AND NOT (
      '.NEW_TABLE_LEDGER.'.text_key = "payment made"
      AND IF('.NEW_TABLE_LEDGER.'.delivery_id IS NULL,
              '.NEW_TABLE_LEDGER.'.effective_datetime,
              '.TABLE_ORDER_CYCLES.'.delivery_date) >= "'.mysqli_real_escape_string ($connection, $row_prior_closing['delivery_date']).'"
      )';
  }
else
  {
    // There was no prior delivery date, so use the accounting datetime limit (for constraining totals over time following the zero-date)
    $and_since_prior_delivery_date = '
        '.NEW_TABLE_LEDGER.'.effective_datetime > "'.ACCOUNTING_ZERO_DATETIME.'"';
    $and_before_prior_delivery_date = '
      /* THERE WAS NOTHING BEFORE THE PRIOR DELIVERY DATE */
      AND 0';
  }

// This multi-row content comprises the non-product body of the report
// This should be expanded to include all current charges -- even those from prior orders that were enacted recently.

// We want all non-product transactions that happened since the prior closing date (if there was one)
// and until the closing of the current order... AS WELL AS all non-product transactions directly
// associated with this order cycle

$query_adjustment = '
  SELECT
    SQL_CALC_FOUND_ROWS
    IF('.NEW_TABLE_LEDGER.'.source_type = "producer", -1, 1) AS multiplier,
    '.NEW_TABLE_LEDGER.'.transaction_id,
    '.NEW_TABLE_LEDGER.'.amount,
    '.NEW_TABLE_LEDGER.'.text_key,
    '.NEW_TABLE_LEDGER.'.effective_datetime,
    '.NEW_TABLE_LEDGER.'.replaced_by,
    '.NEW_TABLE_LEDGER.'.timestamp,
    '.NEW_TABLE_LEDGER.'.basket_id,
    '.NEW_TABLE_LEDGER.'.bpid,
    '.NEW_TABLE_LEDGER.'.site_id,
    '.NEW_TABLE_LEDGER.'.delivery_id,
    '.NEW_TABLE_LEDGER.'.pvid,
    '.NEW_TABLE_MESSAGES.'.message AS ledger_message
  FROM
    '.NEW_TABLE_LEDGER.'
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  LEFT JOIN '.NEW_TABLE_MESSAGES.' '.NEW_TABLE_MESSAGES.' ON
    ( '.NEW_TABLE_MESSAGES.'.referenced_key1 = '.NEW_TABLE_LEDGER.'.transaction_id
    AND '.NEW_TABLE_MESSAGES.'.message_type_id =
      (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "ledger comment")
    )
  WHERE
    (
      (
        '.NEW_TABLE_LEDGER.'.source_type = "member"
      AND '.NEW_TABLE_LEDGER.'.source_key = "'.mysqli_real_escape_string ($connection, $member_id).'"
    )
    OR
      (
        '.NEW_TABLE_LEDGER.'.target_type = "member"
      AND
        '.NEW_TABLE_LEDGER.'.target_key = "'.mysqli_real_escape_string ($connection, $member_id).'"
      )
    )
    AND '.NEW_TABLE_LEDGER.'.replaced_by IS NULL
    AND '.NEW_TABLE_LEDGER.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'"
    AND '.NEW_TABLE_LEDGER.'.pvid IS NULL


    AND
      ('.
        $and_since_prior_delivery_date.'
    /* ALSO CATCH INFORMATION THAT AFFECTS THE CURRENT INVOICE SPECIFICALLY */
      OR
        (
          '.NEW_TABLE_LEDGER.'.delivery_id = "'.mysqli_real_escape_string ($connection, $delivery_id).'" /* THE TAREGET ORDER CYCLE */
        AND
          '.NEW_TABLE_LEDGER.'.text_key = "payment made"
        )
      )
  ORDER BY
    '.NEW_TABLE_LEDGER.'.effective_datetime';

// Get the balance-forward amount, if any, which should be everything that happened prior to this order except payments for/since the last order
$query_balance = '
  SELECT
    SUM(amount * IF('.NEW_TABLE_LEDGER.'.source_type = "producer", -1, 1)) AS total
  FROM
    '.NEW_TABLE_LEDGER.'
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    (
      (
        '.NEW_TABLE_LEDGER.'.source_type = "producer"
      AND
        '.NEW_TABLE_LEDGER.'.source_key = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"
      )
    OR
      (
        '.NEW_TABLE_LEDGER.'.target_type = "producer"
      AND
        '.NEW_TABLE_LEDGER.'.target_key = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"
      )
    )
    AND '.NEW_TABLE_LEDGER.'.replaced_by IS NULL'.
    $and_before_prior_delivery_date.
    $constrain_effective_datetime;

$result_balance = mysqli_query ($connection, $query_balance) or die (debug_print ("ERROR: 673930 ", array ($query_balance, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
if ($row_balance = mysqli_fetch_array ($result_balance, MYSQLI_ASSOC))
  {
    $unique_data['balance_forward'] = $row_balance['total'];
  }
