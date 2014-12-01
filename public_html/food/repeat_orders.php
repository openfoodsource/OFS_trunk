<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');

/*

The purpose of this script is to allow certain products to be posted to multiple subsequent orders.
It uses a special table "repeat_orders" to configure which products are configured for use on
repeating orders.  Then a button allows those products to open customer baskets and add the product
to members who will receive the multiple order items.  All orders after the initial order (which will
be put into the basket manually) will have a zero-cost associated with their customer_basket_item.
After the designated number of orders, the product will no longer be added to baskets until it is
again manually added by the member (for a non-zero cost).

This script was generated for use by CSAs where a multiple-week subscription will be ordered and paid
for in advance (on the first order) but the CSA item will still need to be added to each order for the
duration of the subscription period.  Near the end of the subscription period, a notice will be added
to the "customer_notes_to_producer" field, indicating that it is time to re-order.

This script does not require any concrete changes to the core LFC software, but it DOES require a
change to the customer_basket_overall schema to create a joint key on member_id and delivery_id as a
unique key.  This is actually an improvement over prior versions anyway.  For pre-v1.6.0 databases,
the following SQL should update the customer_basket_overall schema correctly:

  ALTER TABLE customer_basket_overall
    DROP INDEX delivery_codescustomer_basket,
    DROP INDEX delivery_datescustomer_basket,
    DROP INDEX memberscustomer_basket_overall,
    DROP INDEX order_id,
    DROP INDEX payment_methodcustomer_basket_overall,
    ADD INDEX site_id (site_id),
    ADD INDEX payment_method (payment_method),
    ADD UNIQUE delivery_member_id (delivery_id, member_id)

The schema for the repeat_orders is:

  CREATE TABLE repeat_orders
    (
      repeat_id int(11) NOT NULL AUTO_INCREMENT,
      product_id int(11) NOT NULL,
      repeat_cycles tinyint(4) NOT NULL,
      warn_cycles tinyint(4) NOT NULL,
      order_last_added smallint(6) NOT NULL,
      PRIMARY KEY (`repeat_id`)
    )
  COMMENT='Scheduling data for repeating orders'
  AUTO_INCREMENT=1

NOTE: At the current stage of development, a row for each potential repeat product will need to be
manually added to the database before it can be configured.

*/

// Configuration data
define('TABLE_REPEAT_ORDERS', $db_prefix.'repeat_orders');

// Process any current requests
if ($_POST['action'] == 'Post orders')
  {
    // First get information about the product being added
    $query = '
      SELECT
        *
      FROM
        '.TABLE_PRODUCT.'
      WHERE
        product_id = "'.mysql_real_escape_string ($_POST['product_id']).'"';
    $result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
    while ( $row = mysql_fetch_object($result) )
      {
        $producer_id = $row->producer_id;
        $product_id = $row->product_id;
        $product_name = $row->product_name;
        $detailed_notes = $row->detailed_notes;
        $subcategory_id = $row->subcategory_id;
        $unit_price = $row->unit_price;
        $pricing_unit = $row->pricing_unit;
        $ordering_unit = $row->ordering_unit;
        $random_weight = $row->random_weight;
        $extra_charge = $row->extra_charge;
        $meat_weight_type = $row->meat_weight_type;
        $minimum_weight = $row->minimum_weight;
        $maximum_weight = $row->maximum_weight;
        $future_delivery = $row->future_delivery;
        $prodtype_id = $row->prodtype_id;
        $retail_staple = $row->retail_staple;
        $staple_type = $row->staple_type;
        $hidefrominvoice = $row->hidefrominvoice;
        $storage_id = $row->storage_id;
        $future_delivery_id = $row->future_delivery_id;
        $tangible = $row->tangible;
      }
    // This query will create new baskets, but only where needed (no duplicate baskets)
    $query = '
      INSERT INTO
        '.TABLE_BASKET_ALL.'
          (
            member_id,
            delivery_id,
            coopfee,
            site_id,
            deltype,
            delivery_cost,
            transcharge,
            payment_method
          )
      SELECT
        '.TABLE_BASKET_ALL.'.member_id,
        '.mysql_real_escape_string ($_POST['new_order_last_added']).',
        '.TABLE_ORDER_CYCLES.'.coopfee,
        '.TABLE_BASKET_ALL.'.site_id,
        '.TABLE_BASKET_ALL.'.deltype,
        '.TABLE_BASKET_ALL.'.delivery_cost,
        '.NEW_TABLE_SITES.'.transcharge,
        '.TABLE_BASKET_ALL.'.payment_method
      FROM
        '.TABLE_REPEAT_ORDERS.'
      LEFT JOIN
        '.TABLE_PRODUCT.' ON '.TABLE_REPEAT_ORDERS.'.product_id = '.TABLE_PRODUCT.'.product_id
      LEFT JOIN
        '.TABLE_BASKET.' ON '.TABLE_REPEAT_ORDERS.'.product_id = '.TABLE_BASKET.'.product_id
      LEFT JOIN
        '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
      LEFT JOIN '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.mysql_real_escape_string ($_POST['new_order_last_added']).'
      LEFT JOIN '.NEW_TABLE_SITES.' ON '.TABLE_BASKET_ALL.'.site_id = '.NEW_TABLE_SITES.'.site_id
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id >= '.mysql_real_escape_string ($_POST['new_order_last_added']).' - repeat_cycles
        AND '.TABLE_BASKET.'.item_price != 0
        AND '.TABLE_BASKET.'.product_id = '.mysql_real_escape_string ($_POST['product_id']).'
      ON DUPLICATE KEY UPDATE
        '.TABLE_BASKET_ALL.'.finalized = 0';
    $result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());

    // This query will return all members who need the product in their (now open) baskets
    $query_mem = '
      SELECT
        '.TABLE_REPEAT_ORDERS.'.repeat_cycles,
        '.TABLE_REPEAT_ORDERS.'.repeat_id,
        '.TABLE_REPEAT_ORDERS.'.warn_cycles,
        '.TABLE_PRODUCT.'.product_name,
        '.TABLE_PRODUCT.'.detailed_notes,
        '.TABLE_BASKET.'.quantity,
        '.TABLE_BASKET_ALL.'.member_id,
        MAX('.TABLE_BASKET_ALL.'.delivery_id),
        (MAX('.TABLE_BASKET_ALL.'.delivery_id) + repeat_cycles - '.mysql_real_escape_string ($_POST['new_order_last_added']).') AS qty_remaining
      FROM
        '.TABLE_REPEAT_ORDERS.'
      LEFT JOIN
        '.TABLE_PRODUCT.' ON '.TABLE_REPEAT_ORDERS.'.product_id = '.TABLE_PRODUCT.'.product_id
      LEFT JOIN
        '.TABLE_BASKET.' ON '.TABLE_REPEAT_ORDERS.'.product_id = '.TABLE_BASKET.'.product_id
      LEFT JOIN
        '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
      WHERE
        ('.TABLE_BASKET_ALL.'.delivery_id + repeat_cycles) >= '.mysql_real_escape_string ($_POST['new_order_last_added']).'
        AND '.TABLE_BASKET.'.item_price != 0
        AND '.TABLE_BASKET.'.product_id = '.mysql_real_escape_string ($_POST['product_id']).'
        AND '.TABLE_BASKET_ALL.'.delivery_id < '.mysql_real_escape_string ($_POST['new_order_last_added']).'
      GROUP BY member_id';
    $result_mem = @mysql_query($query_mem, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());

    // Initialize the completion_array that will be used to show what members were acted upon.
    $completion_array = array ();
    while ( $row = mysql_fetch_object($result_mem) )
      {
        // We need this value later, after the while-statement
        $repeat_id = $row->repeat_id;

        // First, delete the product from the basket (just in case it was added incorrectly -- i.e. with a price)
        $query = '
          DELETE FROM
            '.TABLE_BASKET.'
          WHERE
            '.TABLE_BASKET.'.product_id = '.mysql_real_escape_string ($_POST['product_id']).'
            AND '.TABLE_BASKET.'.basket_id = 
              (
                SELECT
                  basket_id
                FROM
                  '.TABLE_BASKET_ALL.'
                WHERE
                  '.TABLE_BASKET_ALL.'.delivery_id = '.mysql_real_escape_string ($_POST['new_order_last_added']).'
                  AND '.TABLE_BASKET_ALL.'.member_id = '.mysql_real_escape_string ($row->member_id).'
              )';
        $result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());

        // Then, insert the item into the basket (again, if necessary)
        $query = '
          INSERT INTO
            '.TABLE_BASKET.'
              (
                basket_id,
                product_id,
                producer_id,
                product_name,
                detailed_notes,
                subcategory_id,
                item_price,
                pricing_unit,
                ordering_unit,
                quantity,
                random_weight,
                extra_charge,
                meat_weight_type,
                minimum_weight,
                maximum_weight,
                future_delivery,
                customer_notes_to_producer,
                prodtype_id,
                retail_staple,
                staple_type,
                hidefrominvoice,
                storage_id,
                future_delivery_id,
                tangible,
                item_date
              )
          VALUES
            (
              (
                SELECT
                  basket_id
                FROM
                  '.TABLE_BASKET_ALL.'
                WHERE
                  '.TABLE_BASKET_ALL.'.delivery_id = '.mysql_real_escape_string ($_POST['new_order_last_added']).'
                  AND '.TABLE_BASKET_ALL.'.member_id = '.mysql_real_escape_string ($row->member_id).'
              ),
              "'.mysql_real_escape_string ($_POST['product_id']).'",
              "'.mysql_real_escape_string ($producer_id).'",
              "'.mysql_real_escape_string ($product_name).'",
              "'.mysql_real_escape_string ($detailed_notes).'",
              "'.mysql_real_escape_string ($subcategory_id).'",
              "0",
              "'.mysql_real_escape_string ($pricing_unit).'",
              "'.mysql_real_escape_string ($ordering_unit).'",
              "'.mysql_real_escape_string ($row->quantity).'",
              "'.mysql_real_escape_string ($random_weight).'",
              "0",
              "'.mysql_real_escape_string ($meat_weight_type).'",
              "'.mysql_real_escape_string ($minimum_weight).'",
              "'.mysql_real_escape_string ($maximum_weight).'",
              "'.mysql_real_escape_string ($future_delivery).'",
              "'.mysql_real_escape_string ($row->qty_remaining).' remaining '.Inflect::pluralize_if ($row->qty_remaining, 'order').'.'.($row->qty_remaining <= $row->warn_cycles ? ' Time to reorder!' : '').'",
              "'.mysql_real_escape_string ($prodtype_id).'",
              "'.mysql_real_escape_string ($retail_staple).'",
              "'.mysql_real_escape_string ($staple_type).'",
              "'.mysql_real_escape_string ($hidefrominvoice).'",
              "'.mysql_real_escape_string ($storage_id).'",
              "'.mysql_real_escape_string ($future_delivery_id).'",
              "'.mysql_real_escape_string ($tangible).'",
              now()
            )';
        $result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
        array_push ($completion_array, 'Product added for member #'.$row->member_id.' ('.$row->qty_remaining.' future orders remain).');
      }
    // Finally, update the repeat_orders table with the order that we just posted.
    $query = '
      UPDATE
        '.TABLE_REPEAT_ORDERS.'
      SET
        order_last_added = "'.mysql_real_escape_string ($_POST['new_order_last_added']).'"
      WHERE
        repeat_id = "'.mysql_real_escape_string ($repeat_id).'"
      ';
    $result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
  }

elseif ($_POST['action'] == 'Update settings')
  {
    $query = '
      UPDATE
        '.TABLE_REPEAT_ORDERS.'
      SET
        product_id = "'.$_POST['product_id'].'",
        repeat_cycles = "'.$_POST['repeat_cycles'].'",
        warn_cycles = "'.$_POST['warn_cycles'].'"
      WHERE
        repeat_id = "'.$_POST['repeat_id'].'"';
    $result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
  }
elseif ($_POST['action'] == 'Add new item')
  {
    $query = '
      INSERT INTO
        '.TABLE_REPEAT_ORDERS.'
      SET
        product_id = "'.$_POST['product_id'].'",
        repeat_cycles = "'.$_POST['repeat_cycles'].'",
        warn_cycles = "'.$_POST['warn_cycles'].'"';
    $result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
  }

// Override the mysql error:
// 
//   Error: Selecting message The SELECT would examine more than MAX_JOIN_SIZE rows; check your WHERE and use SET SQL_BIG_SELECTS=1 or SET SQL_MAX_JOIN_SIZE=# if the SELECT is okay
//   Error No: 1104
// This query should probably be reviewed and revised -ROYG
$query = '
  SET SQL_BIG_SELECTS=1';
$result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());

// Display current repeat-scheduled products
$query = '
  SELECT
    '.TABLE_REPEAT_ORDERS.'.*,
    '.TABLE_PRODUCT.'.product_name,
    '.TABLE_PRODUCT.'.detailed_notes,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    COUNT(member_id) AS quantity
  FROM
    '.TABLE_REPEAT_ORDERS.'
  LEFT JOIN
    '.TABLE_PRODUCT.' ON '.TABLE_REPEAT_ORDERS.'.product_id = '.TABLE_PRODUCT.'.product_id
  LEFT JOIN
    '.TABLE_BASKET.' ON '.TABLE_REPEAT_ORDERS.'.product_id = '.TABLE_BASKET.'.product_id
  LEFT JOIN
    '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.TABLE_REPEAT_ORDERS.'.order_last_added
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id >= '.mysql_real_escape_string (ActiveCycle::delivery_id_next()).' - repeat_cycles
    AND '.TABLE_BASKET.'.item_price != 0
  GROUP BY
    '.TABLE_REPEAT_ORDERS.'.product_id';
$result = @mysql_query($query, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Selecting message ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
$display .= '
  <table class="control" border="0" cellspacing="0" cellpadding="3" width="95%" align="center">
    <tr>
      <th style="border-bottom:1px solid #000;">Product</th>
      <th colspan="2" style="border-bottom:1px solid #000;">Parameters</th>
    </tr>';
while ( $row = mysql_fetch_object($result) )
  {
    $display .= '
    <form action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
      <tr>
        <td rowspan="5" width="50%" valign="top"><strong>'.$row->product_name.'</strong><br>'.$row->detailed_notes.'</td>
        <td class="prod_desc">Product ID: </td>
        <td class="prod_data"><input type="text" name="product_id" size="3" value="'.$row->product_id.'"><br>
      </tr>
      <tr>
        <td class="prod_desc">Repeat after first order: </td>
        <td class="prod_data"><input type="text" name="repeat_cycles" size="3" value="'.$row->repeat_cycles.'"> '.Inflect::pluralize_if ($row->repeat_cycles, 'time').'<br>
      </tr>
      <tr>
        <td class="prod_desc">Warn on remaining: </td>
        <td class="prod_data"><input type="text" name="warn_cycles" size="3" value="'.$row->warn_cycles.'"> '.Inflect::pluralize_if ($row->warn_cycles, 'cycle').'<br>
      </tr>
      <tr>
        <td class="prod_desc">Orders to process: </td>
        <td class="prod_data">'.$row->quantity.' '.Inflect::pluralize_if ($row->quantity, 'member').'<br>
      </tr>
      <tr>
        <td class="prod_desc">Last added for: </td>
        <td class="prod_data">'.($row->delivery_date? date ("M d, Y", strtotime($row->delivery_date)) : '[NEVER]').'</td>
      </tr>';
    if ($repeat_id == $row->repeat_id && count ($completion_array) > 0)
      {
        sort($completion_array, SORT_NUMERIC);
        $display .= '
      <tr>
        <td colspan="3">
          <div class="completion_list">
          '.(implode ('<br>', $completion_array)).'
          </div>
        </td>
      </tr>';
      }
    $display .= '
      <tr>
        <td align="left" style="border-bottom:1px solid #000;">
          <input type="hidden" name="repeat_id" value="'.$row->repeat_id.'">
          <input type="hidden" name="new_order_last_added" value="'.ActiveCycle::delivery_id_next().'">
          '.($row->order_last_added < ActiveCycle::delivery_id_next() ? '<input class="button" type="submit" name="action" value="Post orders"><br><br>' : '').'
        </td>
        <td align="right" colspan="2" style="border-bottom:1px solid #000;">
          <input class="button" type="submit" name="action" value="Update settings"><br><br>
        </td>
      </tr>
    </form>';
  }

// Get the next repeat_id for adding a new repeating item

$display .= '
    <form action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
      <tr>
        <td rowspan="3" width="50%" valign="top" class="add"><strong>Add new item</strong><br>Use this section to add a new
        repeating item.  The cost of the item will be charged on the invoice only the first time the item is ordered.  Each
        subsequent invoice for the number of &quot;Repeat after first order&quot; times, will show the item at zero cost.  This
        is how the system can correctly count repeats.</td>
        <td class="prod_desc add">Product ID: </td>
        <td class="prod_data add"><input type="text" name="product_id" size="3" value=""><br>
      </tr>
      <tr>
        <td class="prod_desc add">Repeat after first order: </td>
        <td class="prod_data add"><input type="text" name="repeat_cycles" size="3" value=""> times<br>
      </tr>
      <tr>
        <td class="prod_desc add">Warn on remaining: </td>
        <td class="prod_data add"><input type="text" name="warn_cycles" size="3" value=""> cycles<br>
      </tr>
      <tr>
        <td align="left" style="border-bottom:1px solid #000;" class="add"></td>
        <td align="right" colspan="2" style="border-bottom:1px solid #000;" class="add">
          <input class="button" type="submit" name="action" value="Add new item"><br><br>
        </td>
      </tr>
    </form>
  </table>';

$page_specific_css = '
  <style type="text/css">
    .prod_desc {width:30%; text-align:right; vertical-align:middle;font-weight:bold;}
    .prod_data {width:20%; text-align:left; vertical-align:middle;}
    .button {padding:4px; margin:10px;}
    .control {border:3px solid #aaf;}
    .completion_list {font-size: 80%; color:#008;background-color:#eef;border:1px solid #aaf;padding:0.5em;}
    .add {background-color:#eee;color:#a00;;}
  </style>';

$page_title_html = '<span class="title">Admin Maintenance</span>';
$page_subtitle_html = '<span class="subtitle">Repeating Orders</span>';
$page_title = 'Admin Maintenance: Repeating Orders';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
