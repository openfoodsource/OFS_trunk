<?php

// This function will get the html markup for a div containing formatted basket history.
// Sample/suggested CSS is given at the end.
function delivery_selector ($current_delivery_id)
  {
    global $connection;
    // Get a list of the order cycles in reverse order
    $delivery_id_array = array();
    $delivery_attrib = array ();
    $query = '
      SELECT 
        delivery_id,
        date_open,
        date_closed,
        order_fill_deadline,
        delivery_date
      FROM
        '.TABLE_ORDER_CYCLES.'
      WHERE
        date_open < NOW()
      ORDER BY
        delivery_date DESC';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 898034 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    WHILE ($row = mysql_fetch_array($result))
      {
        array_push ($delivery_id_array, $row['delivery_id']);
        $delivery_attrib[$row['delivery_id']]['date_open'] = $row['date_open'];
        $delivery_attrib[$row['delivery_id']]['time_open'] = strtotime($row['date_open']);
        $delivery_attrib[$row['delivery_id']]['date_closed'] = $row['date_closed'];
        $delivery_attrib[$row['delivery_id']]['time_closed'] = strtotime($row['date_closed']);
        $delivery_attrib[$row['delivery_id']]['order_fill_deadline'] = $row['order_fill_deadline'];
        $delivery_attrib[$row['delivery_id']]['delivery_date'] = $row['delivery_date'];
      }
    // Now get this customer's baskets
    $list_title = 'Select Delivery Date';
    foreach ($delivery_id_array as $delivery_id)
      {
        // Check if this is the current delivery
        if ($delivery_id == $current_delivery_id)
          {
            $current = true;
            $list_title = 'Selected: '.date('M j, Y', strtotime($delivery_attrib[$delivery_id]['delivery_date']));
          }
        else
          {
            $current = false;
          }
        $day_open = date ('j', $delivery_attrib[$delivery_id]['time_open']);
        $month_open = date ('M', $delivery_attrib[$delivery_id]['time_open']);
        $year_open = date ('Y', $delivery_attrib[$delivery_id]['time_open']);
        $day_closed = date ('j', $delivery_attrib[$delivery_id]['time_closed']);
        $month_closed = date ('M', $delivery_attrib[$delivery_id]['time_closed']);
        $year_closed = date ('Y', $delivery_attrib[$delivery_id]['time_closed']);
        if ($day_open == $day_closed) $day_open = '';
        if ($month_open == $month_closed) $month_closed = '';
        if ($year_open == $year_closed) $year_open = '';
        $items_in_basket = abs($delivery_attrib[$delivery_id]['checked_out']);
// Need some onclick code for class=view (full baskets)
        $list_display .= '
          <li>
            <a class="select_block view'.($current == true ? ' current' : '').' "href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.$delivery_id.'">
              <span class="delivery_date">Delivery: '.date('M j, Y', strtotime($delivery_attrib[$delivery_id]['delivery_date'])).'</span>
              <span class="order_dates">'.$month_open.' '.$day_open.' '.$year_open.' &ndash; '.$month_closed.' '.$day_closed.' '.$year_closed.'</span>
              <span class="basket_qty">'.$basket_quantity_text.'</span>
              <!-- <span class="basket_action">Jump to this delivery</span> -->
            </a>
          </li>';
      }
    // Display the order cycles and baskets...
    $display .= '
        <div id="basket_dropdown" class="dropdown" onclick="jQuery(this).toggleClass(\'clicked\')">
          <h1 class="cycle_history">
            '.$list_title.'
          </h1>
          <div id="cycle_history">
            <ul class="cycle_history">'.
              $list_display.'
            </ul>
          </div>
        </div>';
    return $display;
  }
?>