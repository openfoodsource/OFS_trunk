<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member_admin,site_admin,cashier');

// $_POST = $_GET; // FOR DEBUGGING

$data_page = isset($_POST['data_page']) ? mysql_real_escape_string ($_POST['data_page']) : 1;
$per_page = isset($_POST['per_page']) ? mysql_real_escape_string ($_POST['per_page']) : PER_PAGE;
$per_page = 10;
$limit_clause = mysql_real_escape_string (floor (($data_page - 1) * $per_page).", ".floor ($per_page));

// // Set colors that will be used for consecutive calendar months
// $month_color_array = array ('ace', 'aec', 'cae', 'cea', 'eac', 'eca');
// // Set colors that will be used for consecutive order cycles
// $cycle_color_array = array ('06c', '0c6', '60c', '6c0', 'c06', 'c60');
$distinct_cycles = 6;
$days_array = array (0=>'Sun', 1=>'Mon', 2=>'Tue', 3=>'Wed', 4=>'Thu', 5=>'Fri', 6=>'Sat');
$day_length = 24 * 3600; // Length of day in seconds
$week_length = $day_length * 7; // Length of week in seconds
// Query for the order cycles
$query = '
  SELECT
    SQL_CALC_FOUND_ROWS
    delivery_id,
    date_open,
    delivery_date,
    date_closed,
    order_fill_deadline,
    msg_all,
    msg_bottom,
    coopfee,
    invoice_price    /* 0=show coop price; 1=show retail price */
    producer_markdown,
    retail_markup,
    wholesale_markup
  FROM
    '.TABLE_ORDER_CYCLES.'
  ORDER BY delivery_date
  LIMIT '.$limit_clause;

$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 756930 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// Get the total number of rows (for pagination) -- not counting the LIMIT condition
$query_found_cycles = '
  SELECT
    FOUND_ROWS() AS found_cycles';
$result_found_cycles = @mysql_query($query_found_cycles, $connection) or die(debug_print ("ERROR: 759323 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$row_found_cycles = mysql_fetch_array($result_found_cycles);
$found_cycles = $row_found_cycles['found_cycles'];
$found_pages = ceil ($found_cycles / $per_page);

$order_cycle_array = array ();
while ($row = mysql_fetch_array($result))
  {
    array_push ($order_cycle_array, $row);
  }
// Get the lowest and highest dates from the current span of order cycles
$ordering_span = array();
$filling_span = array();
$cycle_span = array();
$delivery_span = array();
$week_div = '';
foreach ($order_cycle_array as $row=>$order_cycle_data)
  {
    if ($first_pass++ == 0)
      {
        $minimum_time = strtotime ($order_cycle_data['date_open']);
        $maximum_time = $minimum_time;
      }
    // Convert all the times to times
    $date_open_time = strtotime ($order_cycle_data['date_open']);
    $date_closed_time = strtotime ($order_cycle_data['date_closed']);
    $fill_deadline_time = strtotime ($order_cycle_data['order_fill_deadline']);
    $delivery_date_start_time = strtotime ($order_cycle_data['delivery_date']);
    $delivery_date_finish_time = $delivery_date_start_time + (3600 * 24) - 1; // One second less than a day
    // Create the span arrays
    $cycle_span[$order_cycle_data['delivery_id']] = $date_open_time.'-'.$delivery_date_finish_time;
    $ordering_span[$order_cycle_data['delivery_id']] = $date_open_time.'-'.$date_closed_time;
    $filling_span[$order_cycle_data['delivery_id']] = $date_closed_time.'-'.$fill_deadline_time;
    $delivery_span[$order_cycle_data['delivery_id']] = $delivery_date_start_time.'-'.$delivery_date_finish_time;
    // Get the overall maximum and minimum times for this data
    foreach (array (
        $date_open_time,
        $date_closed_time,
        $fill_deadline_time,
        $delivery_date_start_time,
        $delivery_date_finish_time
        ) as $time)
      {
        if ($time < $minimum_time) $minimum_time = $time;
        if ($time > $maximum_time) $maximum_time = $time;
      }
  }
// extend our coverage to a week before and a week after the displayed order cycles
$minimum_time = $minimum_time - $week_length;
$maximum_time = $maximum_time + $week_length;
// Make sure the calendar won't span too many weeks -- 2500 weeks is almost five years
$calendar_week_span = ($maximum_time - $minimum_time) / $week_length;
if ($calendar_week_span > 250)
  {
    $ledger_calendar = '
      <div class="error">Not showing calendar, which would span too many ('.$calendar_week_span.') weeks!</div>';
  }
// Otherwise, build a calendar of the ordering time elements
else
  {
    // Get the start time (time of first day of week before/including minimum_time)
    // date ('w') is day of week (Sun=0 .. Sat=6)
    $calendar_start_time = $minimum_time - (date ('w', $minimum_time) * $day_length);
    $calendar_end_time = $maximum_time + ((6 - date ('w', $maximum_time)) * $day_length);
    // Now step through every day from calendar start time to calendar end time
    $this_week_start_time = strtotime (date ('Y-m-d', $calendar_start_time));
    $week_counter = 0;
    // We want to take the cycles in order by their earliest time
    natsort($cycle_span);
    while ($this_week_start_time < $calendar_end_time)
      {
        // Increment the week number
        $week_counter++;
        // Step through all ordering cycles
        reset ($cycle_span);
        $cycle_div = '';
        $this_week_finish_time = $this_week_start_time + $week_length;
        foreach ($cycle_span as $cycle_key=>$cycle_start_stop)
          {
            // Look for cycles with activity within the current week window
            list ($this_cycle_start_time, $this_cycle_finish_time) = explode ('-', $cycle_start_stop);
            if ($this_cycle_start_time < $this_week_finish_time &&
                $this_cycle_finish_time > $this_week_start_time)
              {
                // Create the cycle div
                if ($this_cycle_start_time < $this_week_start_time) $this_cycle_start_time = $this_week_start_time;
                if ($this_cycle_finish_time > $this_week_finish_time) $this_cycle_finish_time = $this_week_finish_time;
                $width_fraction = ($this_cycle_finish_time - $this_cycle_start_time) / $week_length;
                $left_fraction = ($this_cycle_start_time - $this_week_start_time) / $week_length;
                $cycle_class = fmod ($cycle_key, $distinct_cycles) + 1; // This is for coloring styles
                $cycle_div .= '
                  <div id="cycle-'.$week_counter.'-'.$cycle_key.'" style="width:'.number_format ($width_fraction * 100, 2).'%;left:'.number_format ($left_fraction * 100, 2).'%" class="cycle distinct-'.$cycle_class.' cycle-'.$cycle_key.'">';
                // Create the other divs within the cycle div as needed
                //    ORDERING SPAN
                list ($this_date_open_time, $this_date_closed_time) = explode ('-', $ordering_span[$cycle_key]);
                if ($this_date_open_time < $this_cycle_start_time) $this_date_open_time = $this_cycle_start_time;
                if ($this_date_closed_time > $this_cycle_finish_time) $this_date_closed_time = $this_cycle_finish_time;
                $width_fraction = ($this_date_closed_time - $this_date_open_time) / ($this_cycle_finish_time - $this_cycle_start_time);
                $left_fraction = ($this_date_open_time - $this_cycle_start_time) / ($this_cycle_finish_time - $this_cycle_start_time);
                if ($width_fraction > 0) $ordering_div = '
                  <div id="ordering-'.$cycle_key.'" style="width:'.number_format ($width_fraction * 100, 2).'%;left:'.number_format ($left_fraction * 100, 2).'%" class="ordering cycle-'.$cycle_key.'"></div>';
                else $ordering_div = '';
                //    FILLING SPAN
                list ($this_fill_start_time, $this_fill_finish_time) = explode ('-', $filling_span[$cycle_key]);
                if ($this_fill_start_time < $this_cycle_start_time) $this_fill_start_time = $this_cycle_start_time;
                if ($this_fill_finish_time > $this_cycle_finish_time) $this_fill_finish_time = $this_cycle_finish_time;
                $width_fraction = ($this_fill_finish_time - $this_fill_start_time) / ($this_cycle_finish_time - $this_cycle_start_time);
                $left_fraction = ($this_fill_start_time - $this_cycle_start_time) / ($this_cycle_finish_time - $this_cycle_start_time);
                if ($width_fraction > 0) $filling_div = '
                  <div id="filling-'.$cycle_key.'" style="width:'.number_format ($width_fraction * 100, 2).'%;left:'.number_format ($left_fraction * 100, 2).'%" class="filling cycle-'.$cycle_key.'"></div>';
                else $filling_div = '';
                //    DELIVERY SPAN
                list ($this_delivery_start_time, $this_delivery_finish_time) = explode ('-', $delivery_span[$cycle_key]);
                if ($this_delivery_start_time < $this_cycle_start_time) $this_delivery_start_time = $this_cycle_start_time;
                if ($this_delivery_finish_time > $this_cycle_finish_time) $this_delivery_finish_time = $this_cycle_finish_time;
                $width_fraction = ($this_delivery_finish_time - $this_delivery_start_time) / ($this_cycle_finish_time - $this_cycle_start_time);
                $left_fraction = ($this_delivery_start_time - $this_cycle_start_time) / ($this_cycle_finish_time - $this_cycle_start_time);
                if ($width_fraction > 0) $delivery_div = '
                  <div id="delivery-'.$cycle_key.'" style="width:'.number_format ($width_fraction * 100, 2).'%;left:'.number_format ($left_fraction * 100, 2).'%" class="delivery cycle-'.$cycle_key.'"></div>';
                else $delivery_div = '';
                $cycle_div .= '
                  '.$ordering_div.'
                  '.$filling_div.'
                  '.$delivery_div.'
                  </div>';
              }
          }
        // Create the week days to display
        $days_div = '';
        foreach (array_values ($days_array) as $days_key=>$day_of_week)
          {
            list ($day_of_month,
                  $short_weekday,
                  $long_weekday,
                  $short_month,
                  $long_month,
                  $day_number,
                  $week_number,
                  $month_number,
                  $year
                  ) = explode ('-', date ('j-D-l-M-F-N-W-n-Y', $this_week_start_time + ($days_key * $day_length) + 3600)); // Add one hour to get past Daylight Savings Time in all cases
            $days_div .= '
              <div id="day-'.$week_number.'-'.$day_number.'-'.$days_key.'" class="day_frame day_no-'.number_format($day_number, 0).' date_no-'.$day_of_month.' month_no-'.$month_number.'">
                <div class="day">
                  <span class="cal_date">'.$day_of_month.'</span>
                </div>
              </div>';
          }
        // Put it all together inside the week div
        if (($day_of_month >= 7 &&
            $day_of_month < 14) ||
            ($first_month++ == 0 &&
            $day_of_month <= 27))
          $display_month = $long_month.' '.$year;
        $week_div .= '
          <div id="week-'.$week_number.'" class="week_row">
            <span class="month_name">'.($display_month != $display_month_prior ? $display_month : '').'</span>'.
            $days_div.
            $cycle_div.'
          </div>';
        $display_month_prior = $display_month;
        // Increment the day_time counter
        $this_week_start_time = $this_week_finish_time;
      }
  }

// Set up the calendar information
$ledger_data['markup'] = '
  <div id="calendar">
  '.$week_div.'
  </div>';
// Set up the header information for the data table
$ledger_data['markup'] .= '
      <div id="id-header" class="order_cycle_row">
        <div class="delivery_id">Del #</div>
        <div class="date_open">Date Open</div>
        <div class="date_closed">Date Closed</div>
        <div class="order_fill_deadline">Fill Deadline</div>
        <div class="delivery_date">Delivery</div>
      </div>';
$top_special_markup = '';
// Set up the information for the data table
foreach ($order_cycle_array as $row=>$order_cycle_data)
  {
    // build the order schedule output
    $ledger_data['markup'] .= '
      <div id="id-'.$order_cycle_data['delivery_id'].'" class="order_cycle_row" onclick="popup_src(\'edit_order_schedule.php?delivery_id='.$order_cycle_data['delivery_id'].'\', \'edit_order\');" onmouseout="restore_calendar(\''.$order_cycle_data['delivery_id'].'\')" onmouseover="highlight_calendar(\''.$order_cycle_data['delivery_id'].'\')">
        <div class="delivery_id">'.$order_cycle_data['delivery_id'].'</div>
        <div class="date_open">'.$order_cycle_data['date_open'].'</div>
        <div class="date_closed">'.$order_cycle_data['date_closed'].'</div>
        <div class="order_fill_deadline">'.$order_cycle_data['order_fill_deadline'].'</div>
        <div class="delivery_date">'.$order_cycle_data['delivery_date'].'</div>
        <!-- <div class="edit_link" onclick="popup_src(\'edit_account.php?action=edit&account_key='.$order_cycle_data['account_key'].'\');">Edit</a></div> -->
      </div>';
    $top_special_markup = '';
  }

$ledger_data['query'] = $query;
$ledger_data['maximum_data_page'] = $found_pages;
$ledger_data['data_page'] = $data_page;
// Send back the json data only when not called as an include file.
if (! $call_display_as_include) echo json_encode ($ledger_data);

?>