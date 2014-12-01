<?php

include_once('func.update_ledger.php');

// This function will return some membership parameters for a particular member_id
// Returns an array of associative values for: 
// FROM MEMBERSHIP_TYPES TABLE:
//   membership_type_id
//   set_auth_type
//   initial_cost
//   order_cost
//   order_cost_type
//   membership_class
//   membership_description
//   pending
//   enabled_type
//   revert_to
//   renew_cost
//   expire_after
//   expire_type
//   expire_message
// FROM MEMBERS TABLE:
//   membership_date
//   last_renewal_date
//   membership_discontinued
//   pending

function get_membership_info ($member_id)
  {
    global $connection;
    $query = '
      SELECT
        '.TABLE_MEMBER.'.member_id,
        '.TABLE_MEMBER.'.membership_date,
        '.TABLE_MEMBER.'.last_renewal_date,
        '.TABLE_MEMBER.'.membership_discontinued,
        '.TABLE_MEMBER.'.pending,
        '.TABLE_MEMBER.'.membership_type_id,
        '.TABLE_MEMBERSHIP_TYPES.'.*
      FROM '.TABLE_MEMBER.'
      LEFT JOIN '.TABLE_MEMBERSHIP_TYPES.'
        USING(membership_type_id)
      WHERE
        '.TABLE_MEMBER.'.member_id = "'.mysql_real_escape_string($member_id).'"';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 892915 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $num_rows = mysql_num_rows($result);
    if ($num_rows == 1)
      {
        $row = mysql_fetch_array($result);
        return ($row);
      }
    else
      {
        die(debug_print ("ERROR: 856302 ", array ($query, 'Multiple matches for member_id (#'.$member_id.').', basename(__FILE__).' LINE '.__LINE__)));
      }
  }

// This function will return information about membership renewal for a member. It needs
// information about the member, which can be gotten from the get_membership_info() function
// or can be passed explicitly in an associative array with values for:
//    expire_after
//    expire_type
//    last_renewal_date
//    member_id
//    membership_class
//    expire_message
// It returns values in an associative array for:
//    used_expiration_range         number of units of expire_after that are already used
//    total_expiration_range        same as expire_after
//    membership_percent_complete   percent of the way through the membership until expiration
//    membership_expired            [true|false]
//    membership_message            status message about expired or in-process
//    standard_renewal_date         date for contiguous renewal
//    suggested_renewal_date        date for renewing, accounting for the grace period (not set if not expired)
function check_membership_renewal ($membership_info)
  {
    global $connection;
    $renewal_info = array();
    $renewal_info['used_expiration_range'] = 0;
    // Check for the simple case where the membership does NOT expire
    if ($membership_info['expire_type'] == '')
      {
        $expiration_units = '';
        $renewal_info['used_expiration_range'] = 0;
        $renewal_info['standard_renewal_date'] = ''; // Or should this be 'N/A'?
        $renewal_info['suggested_renewal_date'] = date('Y-m-d', time()); // Or should this be 'N/A'?
        $renewal_info['total_expiration_range'] = $membership_info['expire_after'];
        $renewal_info['membership_percent_complete'] = 0;
        $renewal_info['membership_message'] = 'Your &quot;'.$membership_info['membership_class'].'&quot; membership does not expire.';
      }
    // Check for cycle-based membership expiration
    elseif ($membership_info['expire_type'] == 'cycle')
      {
        // Get a count of the number of cycles since the last_renewal_date and the current date.
        // NOTE: overlapping cycles might cause this to be a little screwy: e.g. if an order opens
        // and I start shopping, then another delivery date happens... my cycle count will increment
        // and I may need to renew before I can continue shopping.
        $query = '
          SELECT
            delivery_id,
            delivery_date
          FROM
            '.TABLE_ORDER_CYCLES.'
          WHERE
            delivery_date >= "'.mysql_real_escape_string ($membership_info['last_renewal_date']).'"
            AND delivery_date < CURDATE()';
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 780322 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $grace_count = 0;
        $renewal_info['used_expiration_range'] = 0;
        $renewal_info['standard_renewal_date'] = 'Not established';
        $renewal_info['suggested_renewal_date'] = '';
        // Presume not expired, but clobber this value later if expiration is discovered
        $renewal_info['membership_expired'] = false;
        while ($row = mysql_fetch_array($result))
          {
            // Count how many deliveries transpired
            $renewal_info['used_expiration_range'] ++;
            // Check if this is the order when the expiration would have occurred
            if ($renewal_info['used_expiration_range'] == $membership_info['expire_after'])
              {
                // The standard renewal date would be the day after the last regularly allowed delivery date
                $renewal_info['membership_expired'] = true;
                $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ($row['delivery_date']) + (24 * 3600));
              }
            // Keep track of whether we are in the grace_period
            if ($renewal_info['used_expiration_range'] >= $membership_info['expire_after'])
              {
                $grace_count ++;
                $renewal_info['membership_expired'] = true;
                if ($grace_count <= $membership_info['grace_period'])
                  {
                    // Keep the standard renewal date if within the grace period
                    $renewal_info['suggested_renewal_date'] = $renewal_info['standard_renewal_date'];
                  }
                else
                  {
                    // Set to the current date if the grace period is expired
                    $renewal_info['suggested_renewal_date'] = date ('Y-m-d', time());
                  }
              }
          }
        $renewal_info['total_expiration_range'] = $membership_info['expire_after'];
        $renewal_info['membership_percent_complete'] = round(($renewal_info['used_expiration_range'] / $renewal_info['total_expiration_range']) * 100, 0);
        // Set message if expired
        if ($renewal_info['membership_expired'] == true)
          {
            $renewal_info['membership_message'] = 'Time to renew. Your &quot;'.$membership_info['membership_class'].'&quot; membership expired after '.$membership_info['expire_after'].' '.Inflect::pluralize_if($membership_info['expire_after'], $membership_info['expire_type']).'.';
          }
        // and if not expired
        else
          {
            $renewal_info['membership_message'] = 'You are '.$renewal_info['membership_percent_complete'].'% through your &quot;'.$membership_info['membership_class'].'&quot; membership period.';
          }
      }
    // Check for order-based membership expiration
    elseif ($membership_info['expire_type'] == 'order')
      {
        // Get a count of the number of cycles since the last_renewal_date and the current date.
        // NOTE: overlapping cycles might cause this to be a little screwy: e.g. if an order opens
        // and I start shopping, then another delivery date happens... my cycle count will increment
        // and I may need to renew before I can continue shopping.
        $query = '
          SELECT
            delivery_id,
            delivery_date
          FROM
            '.NEW_TABLE_BASKETS.'
          LEFT JOIN
            '.TABLE_ORDER_CYCLES.' USING(delivery_id)
          WHERE
            '.NEW_TABLE_BASKETS.'.member_id = '.mysql_real_escape_string ($membership_info['member_id']).'
            AND '.TABLE_ORDER_CYCLES.'.delivery_date >= "'.mysql_real_escape_string ($membership_info['last_renewal_date']).'"
            AND '.TABLE_ORDER_CYCLES.'.delivery_date < CURDATE()
            AND checked_out != 0'; // Does not include NULL (i.e not checked_out items)
        $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 780322 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $grace_count = 0;
        $renewal_info['used_expiration_range'] = 0;
        $renewal_info['standard_renewal_date'] = 'Not established';
        $renewal_info['suggested_renewal_date'] = '';
        // Presume not expired, but clobber this value later if expiration is discovered
        $renewal_info['membership_expired'] = false;
        while ($row = mysql_fetch_array($result))
          {
            // Count how many deliveries transpired
            $renewal_info['used_expiration_range'] ++;
            // Check if this is the order when the expiration would have occurred
            if ($renewal_info['used_expiration_range'] == $membership_info['expire_after'])
              {
                // The standard renewal date would be the day after the last regularly allowed delivery date
                $renewal_info['membership_expired'] = true;
                $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ($row['delivery_date']) + (24 * 3600));
              }
            // Keep track of whether we are in the grace_period
            if ($renewal_info['used_expiration_range'] >= $membership_info['expire_after'])
              {
                $grace_count ++;
                $renewal_info['membership_expired'] = true;
                if ($grace_count <= $membership_info['grace_period'])
                  {
                    // Keep the standard renewal date if within the grace period
                    $renewal_info['suggested_renewal_date'] = $renewal_info['standard_renewal_date'];
                  }
                else
                  {
                    // Set to the current date if the grace period is expired
                    $renewal_info['suggested_renewal_date'] = date ('Y-m-d', time());
                  }
              }
          }
        $renewal_info['total_expiration_range'] = $membership_info['expire_after'];
        $renewal_info['membership_percent_complete'] = round(($renewal_info['used_expiration_range'] / $renewal_info['total_expiration_range']) * 100, 0);
        // Set message if expired
        if ($renewal_info['membership_expired'] == true)
          {
            $renewal_info['membership_message'] = 'Time to renew. Your &quot;'.$membership_info['membership_class'].'&quot; membership expired after '.$membership_info['expire_after'].' '.Inflect::pluralize_if($membership_info['expire_after'], $membership_info['expire_type']).'.';
          }
        // and if not expired
        else
          {
            $renewal_info['membership_message'] = 'You are '.$renewal_info['membership_percent_complete'].'% through your &quot;'.$membership_info['membership_class'].'&quot; membership period.';
          }
      }
    // Check for membership expiration based on number of days
    elseif ($membership_info['expire_type'] == 'day')
      {
        $renewal_info['used_expiration_range'] = 0;
        $renewal_info['standard_renewal_date'] = '';
        $renewal_info['suggested_renewal_date'] = '';
        $renewal_info['total_expiration_range'] = $membership_info['expire_after'];
        $renewal_info['used_expiration_range'] = (time() - strtotime($membership_info['last_renewal_date'])) / (24 * 3600);
        $renewal_info['membership_percent_complete'] = round(($renewal_info['used_expiration_range'] / $renewal_info['total_expiration_range']) * 100, 0);
        $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ($membership_info['last_renewal_date']) + (24 * 3600 * $membership_info['expire_after']));
        // If the account is expired
        if ($renewal_info['used_expiration_range'] >= $membership_info['expire_after'])
          {
            $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ($membership_info['last_renewal_date']) + (24 * 3600 * $membership_info['expire_after']));
            // If the grace period is still in effect, then use the standard_renewal_date
            if (strtotime($renewal_info['standard_renewal_date']) + (24 * 3600 * $membership_info['grace_period']) > time())
              {
                $renewal_info['suggested_renewal_date'] = $renewal_info['standard_renewal_date'];
              }
            // Otherwise use the current time for expired grace period
            else
              {
                $renewal_info['suggested_renewal_date'] = date('Y-m-d', time());
              }
            $renewal_info['membership_expired'] = true;
            $renewal_info['membership_message'] = 'Time to renew. Your &quot;'.$membership_info['membership_class'].'&quot; membership expired after '.$membership_info['expire_after'].' '.Inflect::pluralize_if($membership_info['expire_after'], $membership_info['expire_type']).'.';
          }
        // Not expired
        else
          {
            $renewal_info['membership_expired'] = false;
            $renewal_info['membership_message'] = 'You are '.$renewal_info['membership_percent_complete'].'% through your &quot;'.$membership_info['membership_class'].'&quot; membership period.';
          }
      }
    // Check for membership expiration based on number of weeks
    elseif ($membership_info['expire_type'] == 'week')
      {
        $renewal_info['used_expiration_range'] = 0;
        $renewal_info['standard_renewal_date'] = '';
        $renewal_info['suggested_renewal_date'] = '';
        $renewal_info['total_expiration_range'] = $membership_info['expire_after'];
        $renewal_info['used_expiration_range'] = (time() - strtotime($membership_info['last_renewal_date'])) / (7 * 24 * 3600);
        $renewal_info['membership_percent_complete'] = round(($renewal_info['used_expiration_range'] / $renewal_info['total_expiration_range']) * 100, 0);
        $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ($membership_info['last_renewal_date']) + (7 * 24 * 3600 * $membership_info['expire_after']));
        // If the account is expired
        if ($renewal_info['used_expiration_range'] >= $membership_info['expire_after'])
          {
            $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ($membership_info['last_renewal_date']) + (7 * 24 * 3600 * $membership_info['expire_after']));
            // If the grace period is still in effect, then use the standard_renewal_date
            if (strtotime($renewal_info['standard_renewal_date']) + (7 * 24 * 3600 * $membership_info['grace_period']) > time())
              {
                $renewal_info['suggested_renewal_date'] = $renewal_info['standard_renewal_date'];
              }
            // Otherwise use the current time for expired grace period
            else
              {
                $renewal_info['suggested_renewal_date'] = date('Y-m-d', time());
              }
            $renewal_info['membership_expired'] = true;
            $renewal_info['membership_message'] = 'Time to renew. Your &quot;'.$membership_info['membership_class'].'&quot; membership expired after '.$membership_info['expire_after'].' '.Inflect::pluralize_if($membership_info['expire_after'], $membership_info['expire_type']).'.';
          }
        // Not expired
        else
          {
            $renewal_info['membership_expired'] = false;
            $renewal_info['membership_message'] = 'You are '.$renewal_info['membership_percent_complete'].'% through your &quot;'.$membership_info['membership_class'].'&quot; membership period.';
          }
      }
    // Check for membership expiration based on number of months
    elseif ($membership_info['expire_type'] == 'month')
      {
        $renewal_info['used_expiration_range'] = 0;
        $renewal_info['standard_renewal_date'] = '';
        $renewal_info['suggested_renewal_date'] = '';
        $standard_renewal_date = array();
        // Months are counted from the same day of each month
        $last_renewal_date = explode('-', date('Y-n-j', strtotime($membership_info['last_renewal_date'])));
        $current_date = explode('-', date('Y-n-j', time()));
        if (is_array($last_renewal_date) && is_array($current_date))
          {
            $year_difference = $current_date[0] - $last_renewal_date[0];
            $month_difference = $current_date[1] - $last_renewal_date[1];
            $day_difference = $current_date[2] - $last_renewal_date[2];
            $renewal_info['used_expiration_range'] = ($day_difference > 0) ? $month_difference : $month_difference - 1;
            $renewal_info['used_expiration_range'] += $year_difference * 12;
          }
        $renewal_info['total_expiration_range'] = $membership_info['expire_after'];
        $renewal_info['membership_percent_complete'] = round(($renewal_info['used_expiration_range'] / $renewal_info['total_expiration_range']) * 100, 0);
        // Go get the standard_renewal_date
        $standard_year_difference = floor($membership_info['expire_after'] / 12);
        $standard_month_difference = $membership_info['expire_after'] - ($standard_year_difference * 12);
        $standard_renewal_day = $last_renewal_date[2];
        $standard_renewal_month = $last_renewal_date[1] + $standard_month_difference;
        $standard_renewal_year = $last_renewal_date[0] + $standard_year_difference;
        if ($standard_renewal_month > 12)
          {
            $standard_renewal_month = $standard_renewal_month - 12;
            $standard_renewal_year = $standard_renewal_year + 1;
          }
        if (! checkdate ($standard_renewal_month, $standard_renewal_day, $standard_renewal_year))
          {
            $standard_renewal_day --; // Take 31 to 30
            if (! checkdate ($standard_renewal_month, $standard_renewal_day, $standard_renewal_year))
              {
                $standard_renewal_day --; // Take 30 to 29
                if (! checkdate ($standard_renewal_month, $standard_renewal_day, $standard_renewal_year))
                  {
                    $standard_renewal_day --; // Take 29 to 28
                    // Should not need any further checks since this will cover longest to shortest month
                  }
              }
          }
        $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ("$standard_renewal_year-$standard_renewal_month-$standard_renewal_day"));
        // Go get the suggested_renewal_date (assuming we are still inside the grace_period
        $suggested_year_difference = floor(($membership_info['expire_after'] + $membership_info['grace_period']) / 12);
        $suggested_month_difference = $membership_info['expire_after'] + $membership_info['grace_period'] - ($suggested_year_difference * 12);
        $suggested_renewal_day = $last_renewal_date[2];
        $suggested_renewal_month = $last_renewal_date[1] + $suggested_month_difference;
        $suggested_renewal_year = $last_renewal_date[0] + $suggested_year_difference;
        if ($suggested_renewal_month > 12)
          {
            $suggested_renewal_month = $suggested_renewal_month - 12;
            $suggested_renewal_year = $suggested_renewal_year + 1;
          }
        if (! checkdate ($suggested_renewal_month, $suggested_renewal_day, $suggested_renewal_year))
          {
            $suggested_renewal_day --; // Take 31 to 30
            if (! checkdate ($suggested_renewal_month, $suggested_renewal_day, $suggested_renewal_year))
              {
                $suggested_renewal_day --; // Take 30 to 29
                if (! checkdate ($suggested_renewal_month, $suggested_renewal_day, $suggested_renewal_year))
                  {
                    $suggested_renewal_day --; // Take 29 to 28
                    // Should not need any further checks since this will cover longest to shortest month
                  }
              }
          }
        // Check if the grace_period has passed
        if (strtotime ("$suggested_renewal_year-$suggested_renewal_month-$suggested_renewal_day") < time())
          {
            // So use the current time
            $renewal_info['suggested_renewal_date'] = date('Y-m-d', time());
            $renewal_info['membership_expired'] = true;
            $renewal_info['membership_message'] = 'Time to renew. Your &quot;'.$membership_info['membership_class'].'&quot; membership expired after '.$membership_info['expire_after'].' '.Inflect::pluralize_if($membership_info['expire_after'], $membership_info['expire_type']).'.';
          }
        // Check if the membership is just normally expired
        elseif (strtotime ($renewal_info['standard_renewal_date']) < time())
          {
            $renewal_info['suggested_renewal_date'] = $renewal_info['standard_renewal_date'];
            $renewal_info['membership_expired'] = true;
            $renewal_info['membership_message'] = 'Time to renew. Your &quot;'.$membership_info['membership_class'].'&quot; membership expired after '.$membership_info['expire_after'].' '.Inflect::pluralize_if($membership_info['expire_after'], $membership_info['expire_type']).'.';
          }
        // Not expired
        else
          {
            $renewal_info['membership_expired'] = false;
            $renewal_info['membership_message'] = 'You are '.$renewal_info['membership_percent_complete'].'% through your &quot;'.$membership_info['membership_class'].'&quot; membership period.';
          }
      }
    // Check for membership expiration based on number of years
    elseif ($membership_info['expire_type'] == 'year')
      {
        $renewal_info['used_expiration_range'] = 0;
        $renewal_info['standard_renewal_date'] = '';
        $renewal_info['suggested_renewal_date'] = '';
        $renewal_info['total_expiration_range'] = $membership_info['expire_after'];
        $renewal_info['used_expiration_range'] = (time() - strtotime($membership_info['last_renewal_date'])) / (365.24 * 24 * 3600);
        $renewal_info['membership_percent_complete'] = round(($renewal_info['used_expiration_range'] / $renewal_info['total_expiration_range']) * 100, 0);
        $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ($membership_info['last_renewal_date']) + (365.24 * 24 * 3600 * $membership_info['expire_after']));
        // If the account is expired, then set the standard_renewal_date to the same day
        if ($renewal_info['used_expiration_range'] >= $membership_info['expire_after'])
          {
            $renewal_info['membership_expired'] = true;
            $renewal_info['standard_renewal_date'] = date ('Y-m-d', strtotime ($membership_info['last_renewal_date']) + (365.24 * 24 * 3600 * $membership_info['expire_after']));
            // If the grace period is still in effect, then use the standard_renewal_date
            if (strtotime($renewal_info['standard_renewal_date']) + (365.24 * 24 * 3600 * $membership_info['grace_period']) > time())
              {
                $renewal_info['suggested_renewal_date'] = $renewal_info['standard_renewal_date'];
              }
            // Otherwise use the current time for expired grace period
            else
              {
                $renewal_info['suggested_renewal_date'] = date('Y-m-d', time());
              }
            $renewal_info['membership_message'] = 'Time to renew. Your &quot;'.$membership_info['membership_class'].'&quot; membership expired after '.$membership_info['expire_after'].' '.Inflect::pluralize_if($membership_info['expire_after'], $membership_info['expire_type']).'.';
          }
        // Otherwise not expired
        else
          {
            $renewal_info['membership_expired'] = false;
            $renewal_info['membership_message'] = 'You are '.$renewal_info['membership_percent_complete'].'% through your &quot;'.$membership_info['membership_class'].'&quot; membership period.';
          }
      }
    // Check for membership expiration based on a date in the calendar year
    elseif ($membership_info['expire_type'] == 'calendar year')
      {
        // Calendar year is based upon the day-number in the year
        $renewal_info['used_expiration_range'] = 0;
        $renewal_info['standard_renewal_date'] = '';
        $renewal_info['suggested_renewal_date'] = '';
        // This gets the year (YYYY+1) after the last renewal date
        $standard_renewal_time = strtotime ((date('Y', strtotime($membership_info['last_renewal_date'])) + 1).'-01-01');
        // And this adds the days until the annual renewal date
        $standard_renewal_time += (24 * 3600 * $membership_info['expire_after']);
        // Adjust standard_renewal time...
        // If it is longer than a year, but still within the grace_period, it is okay.
        while ($standard_renewal_time > (strtotime($membership_info['last_renewal_date']) + (3600 * 24 * 365.24) + ($membership_info['grace_period'])))
          {
            $standard_renewal_time -= (3600 * 24 * 365.24);
          }
        // Now convert standard_renewal_time to date format
        $renewal_info['standard_renewal_date'] = date ('Y-m-d', $standard_renewal_time);
        // So the number of days between those two (normally would be 365, but the annual date may have changed)
        $renewal_info['total_expiration_range'] = (strtotime ($renewal_info['standard_renewal_date']) - strtotime ($membership_info['last_renewal_date'])) / (24 * 3600);
        $renewal_info['used_expiration_range'] = (time() - strtotime ($membership_info['last_renewal_date'])) / (24 * 3600);
        $renewal_info['membership_percent_complete'] = round(($renewal_info['used_expiration_range'] / 365.24) * 100, 0);
        // Get the most recent annual renewal date
        $this_year = date('Y', time());
        $previous_renewal_date = date ('Y-m-d', strtotime($this_year.'-01-01') + (24 * 3600 * $membership_info['expire_after']));
        // Check if our most_recent_renewal_date is in the future. If so, then use the one from last year
        if (time() < strtotime ($previous_renewal_date))
          {
            // Save this just to avoid recalculation
            $next_renewal_date = $previous_renewal_date;
            // ... and get the renewal date from last year.
            $previous_renewal_date = date ('Y-m-d', strtotime(($this_year - 1).'-01-01') + (24 * 3600 * $membership_info['expire_after']));
          }
        else
          {
            $next_renewal_date = date ('Y-m-d', strtotime(($previous_renewal_date + 1).'-01-01') + (24 * 3600 * $membership_info['expire_after']));
          }
        // If still within the year then no need to renew yet
        if ($renewal_info['total_expiration_range'] > $renewal_info['used_expiration_range'])
          {
            $renewal_info['membership_expired'] = false;
            // $renewal_info['suggested_renewal_date'] = $renewal_info['standard_renewal_date'];
            $renewal_info['membership_message'] = 'You are '.$renewal_info['membership_percent_complete'].'% through your &quot;'.$membership_info['membership_class'].'&quot; membership period which began '.$previous_renewal_date.'.';
          }
        // If still within the year plus grace_period, then suggested renewal date will be the same as the standard renewal date
        elseif (($renewal_info['total_expiration_range'] + $membership_info['grace_period']) > $renewal_info['used_expiration_range'])
          {
            $renewal_info['membership_expired'] = true;
            $renewal_info['suggested_renewal_date'] = $renewal_info['standard_renewal_date'];
            $renewal_info['membership_message'] = 'Time to renew your &quot;'.$membership_info['membership_class'].'&quot; membership for the calendar year beginning '.$renewal_info['suggested_renewal_date'].'.';
          }
        // Otherwise, are we within the grace_period of the most recent annual renewal date (for 1+ year expirations)?
        elseif (time() - ($membership_info['grace_period'] * 24 * 3600) < strtotime ($previous_renewal_date))
          {
            $renewal_info['membership_expired'] = true;
            $renewal_info['suggested_renewal_date'] = $previous_renewal_date;
            $renewal_info['membership_message'] = 'Renew your &quot;'.$membership_info['membership_class'].'&quot; membership for the calendar year beginning '.$renewal_info['suggested_renewal_date'].'.';
          }
        // Otherwise, set the suggested_renewal_date to the next annual renewal date
        else
          {
            $renewal_info['membership_expired'] = true;
            $renewal_info['suggested_renewal_date'] = $next_renewal_date;
            $renewal_info['membership_message'] = 'Renew your &quot;'.$membership_info['membership_class'].'&quot; membership for the calendar year beginning '.$renewal_info['suggested_renewal_date'].'.';
          }
      }
    $renewal_info['expire_message'] = $membership_info['expire_message'];
    $renewal_info['expire_type'] = $membership_info['expire_type'];
    $renewal_info['membership_class'] = $membership_info['membership_class'];
    $renewal_info['membership_type_id'] = $membership_info['membership_type_id'];
    $renewal_info['membership_description'] = $membership_info['membership_description'];
    $renewal_info['membership_date'] = $membership_info['membership_date'];
    return ($renewal_info);
  }

// This function will change the member to a new membership_type_id
function renew_membership ($member_id, $membership_type_id)
  {
    global $connection;
    // First see if the member can sign up for this particular membership_type
    $membership_info = get_membership_info ($member_id);
    $renewal_info = check_membership_renewal ($membership_info);
    // Compare the member's current membership_type to what they have requested
    if (! in_array ($membership_type_id, explode (',', $membership_info['revert_to'])))
      {
        // Requested membership_type is not allowed for this membership_type
        return ('Requested membership_type is not allowed.');
      }
    // Check if this member can renew at the requested membership_type
    $query_membership_type = '
      SELECT
        *
      FROM
        '.TABLE_MEMBERSHIP_TYPES.'
      WHERE (
        enabled_type = "2"
          OR enabled_type = "3")
        AND membership_type_id = "'.mysql_real_escape_string($membership_type_id).'"';
    $result_membership_type = mysql_query($query_membership_type, $connection) or die(debug_print ("ERROR: 683080 ", array ($query_membership_type,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if (! $row_membership_type = mysql_fetch_array($result_membership_type))
      {
        // Requested membership_type is not allowed
        return ('Requested membership_type is not allowed.');
      }
    // Everything is good to here... so prepare to post the membership.
    //    When switching membership types, we will use the suggested_renewal_date for the
    //    new membership_renewal_date. When keeping the same membership type, we will
    //    use the standard_renewal_date.
    if ($renewal_info['membership_expired'] == false &&
      $renewal_info['membership_type_id'] == $row_membership_type['membership_type_id'])
      {
        $renewal_date = $renewal_info['standard_renewal_date'];
      }
    elseif ($renewal_info['suggested_renewal_date'] != '')
      {
        $renewal_date = $renewal_info['suggested_renewal_date'];
      }
    else
      {
        $renewal_date = date ('Y-m-d', time());
      }
    // If this is a renewal, then we use the renewal costs but if it
    // is a switch to a different type, then we use the initial cost
    if ($membership_info['membership_type_id'] == $membership_type_id)
      {
        // Renewal
        $target_field = 'renew_cost';
      }
    else
      {
        // Switch type
        $target_field = 'initial_cost';
      }
    // Post the membership receivable
    $transaction_row = add_to_ledger (array (
      'transaction_group_id' => '',
      'source_type' => 'member',
      'source_key' => $member_id,
      'target_type' => 'internal',
      'target_key' => 'membership_dues',
      'amount' => $row_membership_type[$target_field],
      'text_key' => 'membership dues',
      'posted_by' => $_SESSION['member_id']));
    // Now update the members table
    $query_members = '
      UPDATE
        '.TABLE_MEMBER.'
      SET
        last_renewal_date = "'.mysql_real_escape_string($renewal_date).'",
        membership_type_id = "'.mysql_real_escape_string($membership_type_id).'"
      WHERE
        member_id = "'.mysql_real_escape_string($member_id).'"';
    $result_members = mysql_query($query_members, $connection) or die(debug_print ("ERROR: 683080 ", array ($query_members,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if (mysql_affected_rows())
      {
        return ('Successfully updated membership.');
      }
  }

// Select all membership types that a current member may convert into (enabled_type = 2 or 3)
function membership_renewal_form ($membership_type_id) {
    global $connection;
    $query = '
      SELECT
        *
      FROM
        '.TABLE_MEMBERSHIP_TYPES.'
      WHERE
        enabled_type = 2
        OR enabled_type = 3';
    $sql = mysql_query ($query);
    while ( $row = mysql_fetch_object($sql) )
      {
        //  ------- AVAILABLE FIELDS -------
        //  $row->membership_type_id
        //  $row->initial_cost
        //  $row->order_cost
        //  $row->order_cost_type
        //  $row->membership_class
        //  $row->membership_description
        //  $row->pending
        //  $row->enabled_type
        //  $row->revert_to
        //  $row->renew_cost
        //  $row->expire_after
        //  $row->expire_type
        //  $row->expire_message
        $revert_to = explode (',', $row->revert_to);
        $checked = '';
        // In most cases, the conversion cost will be initial cost for the new membership type
        $conversion_cost = $row->initial_cost;
        if ($row->membership_type_id == $membership_type_id)
          {
            // This is a reversion to the same membership type, so select it and set the conversion_cost to the renew cost
            $checked = ' checked';
            $conversion_cost = $row->renew_cost;
            $expire_message = '<div class="expire_message">'.$row->expire_message.'</div>';
          }
        if ($row->membership_type_id == $membership_type_id && in_array($row->membership_type_id, $revert_to))
          {
            // This is the member's current type (if it is okay to renew as the same type)
            $same_renewal_intro .= '<div class="same_renewal_intro">You may renew with the same membership type:</div>';
            $same_renewal .= '
              <div class="same_renewal"><input type="radio" name="membership_type_id" value="'.$row->membership_type_id.'"'.$checked.'> <span class="cost">$'.number_format ($conversion_cost, 2).'</span> '.$row->membership_class.' (RENEWAL)</div>
              <div class="same_renewal_desc">'.$row->membership_description.'</div>';
          }
        elseif (in_array($row->membership_type_id, $revert_to))
          {
            // If it is okay to convert to this type from the current type
            $changed_renewal_intro = '
              <div class="changed_renewal_intro">You may switch to another membership type:</div>';
            $changed_renewal .= '
              <div class="changed_renewal"><input type="radio" name="membership_type_id" value="'.$row->membership_type_id.'"'.$checked.'> <span class="cost">$'.number_format ($conversion_cost, 2).'</span> '.$row->membership_class.'</div>
              <div class="changed_renewal_desc">'.$row->membership_description.'</div>';
          }
      }
    return array (
      'expire_message' => $expire_message,
      'same_renewal_intro' => $same_renewal_intro,
      'same_renewal' => $same_renewal,
      'changed_renewal_intro' => $changed_renewal_intro,
      'changed_renewal' => $changed_renewal);
  }

?>