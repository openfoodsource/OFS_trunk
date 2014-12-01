<?php
valid_auth('member');

$view = 'adjusted';
if ($_GET['view'] == 'original')
  $view = 'original';
// Check if "editable" request by Cashier who is NOT the holder of the invoice
elseif ($_GET['view'] == 'editable' &&
  CurrentMember::auth_type('cashier') &&
  $member_id != $_SESSION['member_id'])
  $view = 'editable';

if ($view == 'original')
  $view_original = '
    AND '.NEW_TABLE_LEDGER.'.transaction_group_id = ""
    OR ( '.NEW_TABLE_LEDGER.'.replaced_by IS NOT NULL
      AND '.NEW_TABLE_LEDGER.'.replaced_datetime <= delivery_date )';
else
  $view_original = '';

// Do not paginate invoices under any circumstances (web pages)
$per_page = 1000000;

// Assign page tab and title information
$page_title_html = '<span class="title">Basket</span>';
$page_subtitle_html = '<span class="subtitle">Basket Items</span>';
$page_title = 'Basket: Basket Items';
$page_tab = 'shopping_panel';

// Set display groupings
$major_product = 'producer_id';
$major_product_prior = $major_product.'_prior';
$minor_product = 'product_id';
$minor_product_prior = $minor_product.'_prior';
$show_major_product = true;
$show_minor_product = true;
$row_type = 'product'; // Reflects the detail to show on each row (vs. what gets featured in the header)

// Assign template file
$template_type = 'customer_invoice';

// This single-row content is unique for the entire report (used in the header, footer, etc)
$query_unique = '
  SELECT
    '.TABLE_MEMBER.'.address_line1,
    '.TABLE_MEMBER.'.address_line2,
    '.TABLE_MEMBER.'.auth_type,
    '.TABLE_MEMBER.'.business_name,
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
    '.NEW_TABLE_SITES.'.hub_id,
    '.NEW_TABLE_SITES.'.delivery_type,
    '.NEW_TABLE_SITES.'.truck_code,
    '.NEW_TABLE_SITES.'.site_short,
    '.NEW_TABLE_SITES.'.site_long,
    '.NEW_TABLE_SITES.'.site_description,
    '.NEW_TABLE_BASKETS.'.site_id,
    '.NEW_TABLE_BASKETS.'.basket_id,

    (SELECT SUM(amount) FROM '.NEW_TABLE_LEDGER.' WHERE
      ((target_key = "'.mysql_real_escape_string($member_id).'"
      AND target_type = "member")
      OR (source_key = "'.mysql_real_escape_string($member_id).'"
      AND source_type = "member"))
      AND text_key = "order cost"
      AND delivery_id = "'.mysql_real_escape_string($delivery_id).'"
      AND replaced_by IS NULL) AS order_cost,

    /* '.NEW_TABLE_BASKETS.'.order_cost, */

    '.NEW_TABLE_BASKETS.'.delivery_cost,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.delivery_postal_code,
    '.NEW_TABLE_BASKETS.'.customer_fee_percent,
    '.NEW_TABLE_BASKETS.'.checked_out,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_ORDER_CYCLES.'.invoice_price,
    '.TABLE_ORDER_CYCLES.'.msg_all,
    '.TABLE_ORDER_CYCLES.'.msg_bottom
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  LEFT JOIN '.NEW_TABLE_SITES.' USING (site_id)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING (delivery_id)
  WHERE
    '.TABLE_MEMBER.'.member_id = "'.mysql_real_escape_string($member_id).'"
    AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string($delivery_id).'"';
$result_unique = mysql_query($query_unique, $connection) or die(debug_print ("ERROR: 863023 ", array ($query_unique,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
if ($row_unique = mysql_fetch_array ($result_unique))
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
      WHEN ('.NEW_TABLE_LEDGER.'.source_type = "member" AND '.NEW_TABLE_LEDGER.'.source_key = "'.mysql_real_escape_string($member_id).'") THEN 1
      WHEN ('.NEW_TABLE_LEDGER.'.target_type = "member" AND '.NEW_TABLE_LEDGER.'.target_key = "'.mysql_real_escape_string($member_id).'") THEN -1
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
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name AS producer_name,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_type,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.NEW_TABLE_MESSAGES.'1.message AS customer_message,
    '.NEW_TABLE_MESSAGES.'2.message AS product_message,
    '.NEW_TABLE_MESSAGES.'3.message AS adjustment_group_memo
  FROM
    '.NEW_TABLE_LEDGER.'
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(pvid)
  LEFT JOIN '.TABLE_PRODUCER.' USING(producer_id)
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.'  USING(bpid)
  LEFT JOIN '.TABLE_SUBCATEGORY.' USING(subcategory_id)
  LEFT JOIN '.TABLE_CATEGORY.' USING(category_id)
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' USING(production_type_id)
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' USING(storage_id)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  LEFT JOIN '.NEW_TABLE_MESSAGES.' '.NEW_TABLE_MESSAGES.'1 ON
    ( '.NEW_TABLE_MESSAGES.'1.referenced_key1 = '.NEW_TABLE_BASKET_ITEMS.'.bpid
    AND '.NEW_TABLE_MESSAGES.'1.message_type_id =
      (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "customer notes to producer")
    )
  LEFT JOIN '.NEW_TABLE_MESSAGES.' '.NEW_TABLE_MESSAGES.'2 ON
    ( '.NEW_TABLE_MESSAGES.'2.referenced_key1 = '.NEW_TABLE_LEDGER.'.transaction_id
    AND '.NEW_TABLE_MESSAGES.'2.message_type_id =
      (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "ledger comment")
    )
  LEFT JOIN '.NEW_TABLE_MESSAGES.' '.NEW_TABLE_MESSAGES.'3 ON
    ( '.NEW_TABLE_MESSAGES.'3.referenced_key1 = '.NEW_TABLE_LEDGER.'.transaction_group_id
    AND '.NEW_TABLE_MESSAGES.'3.message_type_id =
      (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "adjustment group memo")
    )
  WHERE
    '.NEW_TABLE_LEDGER.'.basket_id = (SELECT basket_id FROM '.NEW_TABLE_BASKETS.' WHERE member_id="'.mysql_real_escape_string($member_id).'" AND delivery_id="'.mysql_real_escape_string($delivery_id).'")
    AND ( '.NEW_TABLE_LEDGER.'.replaced_by IS NULL'.
    $view_original.'
    )
    AND '.NEW_TABLE_PRODUCTS.'.product_id IS NOT NULL
  ORDER BY
    '.TABLE_PRODUCER.'.producer_id,
    '.NEW_TABLE_PRODUCTS.'.product_id,
    FIND_IN_SET('.NEW_TABLE_LEDGER.'.text_key, "quantity cost,weight cost,extra charge,customer fee,producer fee,delivery cost") DESC';

// echo "<pre>$query_product</pre>";

// Get the closing date for this member's most recent prior order
$query_prior_closing = '
  SELECT
    date_closed,
    delivery_date
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string($member_id).'"
    AND '.TABLE_ORDER_CYCLES.'.date_closed < (SELECT date_closed FROM '.TABLE_ORDER_CYCLES.' WHERE delivery_id = "'.mysql_real_escape_string($delivery_id).'")
  ORDER BY
    '.TABLE_ORDER_CYCLES.'.date_closed DESC
  LIMIT
    0,1';
// echo "<pre>$query_prior_closing </pre>";
$result_prior_closing = mysql_query($query_prior_closing, $connection) or die(debug_print ("ERROR: 754932 ", array ($query_prior_closing,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$and_since_prior_closing_date = '';
$and_since_prior_delivery_date = '';
$and_before_prior_delivery_date = '';
if ($row_prior_closing = mysql_fetch_array ($result_prior_closing))
  {
    $unique['prior_closing'] = $row_prior_closing['date_closed'];
    $unique['prior_delivery'] = $row_prior_closing['delivery_date'];
    $and_since_prior_closing_date = 'AND '.NEW_TABLE_LEDGER.'.effective_datetime > "'.$unique['prior_closing'].'"';
    $and_since_prior_delivery_date = 'AND '.NEW_TABLE_LEDGER.'.effective_datetime > "'.$unique['prior_delivery'].'"';
    $and_before_prior_delivery_date = 'AND '.NEW_TABLE_LEDGER.'.effective_datetime < "'.$unique['prior_delivery'].'"';
  }
else
  {
    // There was no prior delivery date, so set it to "zero"
    $and_before_prior_delivery_date = 'AND '.NEW_TABLE_LEDGER.'.effective_datetime < "0000-00-00 00:00:00"';
  }

// This multi-row content comprises the non-product body of the report
// This should be expanded to include all current charges -- even those from prior orders that were enacted recently.

// We want all non-product transactions that happened since the prior closing date (if there was one)
// and until the closing of the current order... AS WELL AS all non-product transactions directly
// associated with this order cycle

$query_adjustment = '
  SELECT
    SQL_CALC_FOUND_ROWS
    IF('.NEW_TABLE_LEDGER.'.source_type = "member", 1, -1) AS multiplier,
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
  LEFT JOIN '.NEW_TABLE_MESSAGES.' '.NEW_TABLE_MESSAGES.' ON
    ( '.NEW_TABLE_MESSAGES.'.referenced_key1 = '.NEW_TABLE_LEDGER.'.transaction_id
    AND '.NEW_TABLE_MESSAGES.'.message_type_id =
      (SELECT message_type_id FROM '.NEW_TABLE_MESSAGE_TYPES.' WHERE description = "ledger comment")
    )
  WHERE
    (('.NEW_TABLE_LEDGER.'.source_type = "member"
        AND '.NEW_TABLE_LEDGER.'.source_key = "'.mysql_real_escape_string($member_id).'")
      OR ('.NEW_TABLE_LEDGER.'.target_type = "member"
        AND '.NEW_TABLE_LEDGER.'.target_key = "'.mysql_real_escape_string($member_id).'"))
    AND '.NEW_TABLE_LEDGER.'.replaced_by IS NULL
    AND '.NEW_TABLE_LEDGER.'.amount != 0 /* no need to show null adjustments */
    AND '.NEW_TABLE_LEDGER.'.bpid IS NULL /* do not consider basket items */
    AND (('.NEW_TABLE_LEDGER.'.effective_datetime < (SELECT delivery_date FROM '.TABLE_ORDER_CYCLES.' WHERE delivery_id = "'.mysql_real_escape_string($delivery_id).'")
        '.$and_since_prior_delivery_date.')
      OR ('.NEW_TABLE_LEDGER.'.delivery_id = "'.mysql_real_escape_string($delivery_id).'"
        AND ('.NEW_TABLE_LEDGER.'.text_key = "payment received"
          OR '.NEW_TABLE_LEDGER.'.text_key = "payment made")))
  ORDER BY
    '.NEW_TABLE_LEDGER.'.effective_datetime';

// echo "<pre>$query_adjustment </pre>";

// Get the balance-forward amount, if any
$query_balance = '
  SELECT
    SUM(amount * IF('.NEW_TABLE_LEDGER.'.source_type = "member", 1, -1)) AS total
  FROM
    '.NEW_TABLE_LEDGER.'
  WHERE
    (('.NEW_TABLE_LEDGER.'.source_type = "member"
      AND '.NEW_TABLE_LEDGER.'.source_key = "'.mysql_real_escape_string($member_id).'")
    OR ('.NEW_TABLE_LEDGER.'.target_type = "member"
      AND '.NEW_TABLE_LEDGER.'.target_key = "'.mysql_real_escape_string($member_id).'"))
    AND '.NEW_TABLE_LEDGER.'.replaced_by IS NULL
    /* Only consider charges prior to the order closing time */
    /* AND '.NEW_TABLE_LEDGER.'.effective_datetime < (SELECT date_closed FROM '.TABLE_ORDER_CYCLES.' WHERE delivery_id = "'.mysql_real_escape_string($delivery_id).'") */
    '.$and_before_prior_delivery_date;
// echo "<pre>$query_balance</pre>";
$result_balance = mysql_query($query_balance, $connection) or die(debug_print ("ERROR: 675930 ", array ($query_balance,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
if ($row_balance = mysql_fetch_array ($result_balance))
  {
    $unique_data['balance_forward'] = $row_balance['total'];
  }


?>