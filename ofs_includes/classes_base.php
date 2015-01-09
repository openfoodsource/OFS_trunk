<?php

/////////////////////////////////////////////////////////////////////////////////////////////////
///                                                                                           ///
///         Classes to set common "global" values (previously contained in the session)       ///
///                                                                                           ///
/////////////////////////////////////////////////////////////////////////////////////////////////

// ActiveCycle returns informatoin for the delivery that began most recently in the past,
// regardless of when it ends or ended.
class ActiveCycle
  {
    private static $active_cycle_query_complete = false;
    private static $next_query_complete = false;

    private static $delivery_id = false;
    private static $delivery_date = false;
    private static $date_open = false;
    private static $date_closed = false;
    private static $order_fill_deadline = false;
    private static $producer_markdown = false;
    private static $retail_markup = false;
    private static $wholesale_markup = false;
    private static $date_open_next = false;
    private static $date_closed_next = false;
    private static $delivery_date_next = false;
    private static $delivery_id_next = false;
    private static $producer_markdown_next = false;
    private static $retail_markup_next = false;
    private static $wholesale_markup_next = false;
    private static $ordering_window = false;
    private static $producer_update_window = false;
    private static $using_next = false;
    private static function get_active_delivery_info ($new_delivery_id)
      {
        if (self::$active_cycle_query_complete === false)
          {
            global $connection;
            // Get information about any shopping period that is currently open
            $query = '
              SELECT
                delivery_id,
                delivery_date,
                date_open,
                date_closed,
                order_fill_deadline,
                producer_markdown / 100 AS producer_markdown,
                retail_markup / 100 AS retail_markup,
                wholesale_markup / 100 AS wholesale_markup
              FROM
                '.TABLE_ORDER_CYCLES.'
              WHERE
                date_open < "'.date ('Y-m-d H:i:s', time()).'"
                /* AND order_fill_deadline > "'.date ('Y-m-d H:i:s', time()).'" */
              ORDER BY
                delivery_id DESC
              LIMIT
                1';
            $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 730099 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            // Set default values in case we returned nothing
            self::$delivery_id = 1;
            if ($row = mysql_fetch_object ($result))
              {
                self::$delivery_id = $row->delivery_id;
                self::$delivery_date = $row->delivery_date;
                self::$date_open = $row->date_open;
                self::$date_closed = $row->date_closed;
                self::$order_fill_deadline = $row->order_fill_deadline;
                self::$producer_markdown = $row->producer_markdown;
                self::$retail_markup = $row->retail_markup;
                self::$wholesale_markup = $row->wholesale_markup;
                if (time() > strtotime ($row->date_open) && time() < strtotime ($row->date_closed))
                  self::$ordering_window = 'open';
                else
                  self::$ordering_window = 'closed';
                if (time() > strtotime ($row->date_closed) && time() < strtotime ($row->order_fill_deadline))
                  self::$producer_update_window = 'open';
                else
                  self::$producer_update_window = 'closed';
                self::$active_cycle_query_complete = true;
              }
          }
      }
    private static function get_next_delivery_info ()
      {
        if (self::$next_query_complete === false)
          {
            global $connection;
            // Set the default "where condition" to be the cycle that opened most recently
            // Do not use MySQL NOW() because it does not know about the php timezone directive
            $now = date ('Y-m-d H:i:s', time());
            $query = '
                (SELECT
                  date_open,
                  date_closed,
                  delivery_date,
                  delivery_id,
                  producer_markdown / 100 AS producer_markdown,
                  retail_markup / 100 AS retail_markup,
                  wholesale_markup / 100 AS wholesale_markup,
                  1 AS using_next
                FROM
                  '.TABLE_ORDER_CYCLES.'
                WHERE
                  date_closed > "'.$now.'"
                ORDER BY
                  date_closed ASC
                LIMIT 0,1)
              UNION
                (SELECT
                  date_open,
                  date_closed,
                  delivery_date,
                  delivery_id,
                  producer_markdown / 100 AS producer_markdown,
                  retail_markup / 100 AS retail_markup,
                  wholesale_markup / 100 AS wholesale_markup,
                  0 AS using_next
                FROM
                  '.TABLE_ORDER_CYCLES.'
                WHERE
                  date_open < "'.$now.'"
                ORDER BY
                  date_open DESC
                LIMIT 0,1)';
            $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 863024 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysql_fetch_object ($result))
              {
                self::$date_open_next = $row->date_open;
                self::$date_closed_next = $row->date_closed;
                self::$delivery_date_next = $row->delivery_date;
                self::$delivery_id_next = $row->delivery_id;
                self::$producer_markdown_next = $row->producer_markdown;
                self::$retail_markup_next = $row->retail_markup;
                self::$wholesale_markup_next = $row->wholesale_markup;
                self::$using_next = $row->using_next;
                self::$next_query_complete = true;
              }
          }
      }
    // Use this function if it is necessary to change/set the active delivery_id within a script.
    public function set_active_delivery_id ($new_delivery_id)
      {
        $active_cycle_query_complete = false;
        self::get_active_delivery_info ($new_delivery_id);
        return self::$delivery_id;
      }
    // Following functions return information for the (current or set value) delivery_id
    public static function delivery_id ()
      {
        self::get_active_delivery_info (0);
        return self::$delivery_id;
      }
    public static function delivery_date ()
      {
        self::get_active_delivery_info (0);
        return self::$delivery_date;
      }
    public static function date_open ()
      {
        self::get_active_delivery_info (0);
        return self::$date_open;
      }
    public static function date_closed ()
      {
        self::get_active_delivery_info (0);
        return self::$date_closed;
      }
    public static function order_fill_deadline ()
      {
        self::get_active_delivery_info (0);
        return self::$order_fill_deadline;
      }
    public static function producer_markdown ()
      {
        self::get_active_delivery_info (0);
        return self::$producer_markdown;
      }
    public static function retail_markup ()
      {
        self::get_active_delivery_info (0);
        return self::$retail_markup;
      }
    public static function ordering_window ()
      {
        self::get_active_delivery_info (0);
        return self::$ordering_window;
      }
    public static function producer_update_window ()
      {
        self::get_active_delivery_info (0);
        return self::$producer_update_window;
      }
    public static function wholesale_markup ()
      {
        self::get_active_delivery_info (0);
        return self::$wholesale_markup;
      }
    // NextDelivery returns delivery information for either the next delivery that
    // will close (in the future) or -- if that does not exist -- then the most recent
    // delivery that opened (in the past) just as with the ActiveCycle class.
    //
    // So, NextDelivery is the same as ActiveCycle EXCEPT if a delivery cycle has
    // already closed and the next one exists in the database, then the next one will
    // be used instead.
    public static function date_open_next ()
      {
        self::get_next_delivery_info (0);
        return self::$date_open_next;
      }
    public static function date_closed_next ()
      {
        self::get_next_delivery_info (0);
        return self::$date_closed_next;
      }
    public static function delivery_date_next ()
      {
        self::get_next_delivery_info (0);
        return self::$delivery_date_next;
      }
    public static function delivery_id_next ()
      {
        self::get_next_delivery_info (0);
        return self::$delivery_id_next;
      }
    public static function producer_markdown_next ()
      {
        self::get_next_delivery_info (0);
        return self::$producer_markdown_next;
      }
    public static function retail_markup_next ()
      {
        self::get_next_delivery_info (0);
        return self::$retail_markup_next;
      }
    public static function wholesale_markup_next ()
      {
        self::get_next_delivery_info (0);
        return self::$wholesale_markup_next;
      }
    public static function using_next ()
      {
        self::get_next_delivery_info (0);
        return self::$using_next;
      }
  }

class CurrentBasket
  {
    private static $query_complete = false;
    private static $basket_id = false;
    private static $basket_checked_out = false;
    private static $site_id = false;
    private static $site_short = false;
    private static $site_long = false;
    private static function get_basket_info ()
      {
        if (self::$query_complete === false)
          {
            global $connection;
            $query = '
              SELECT
                '.NEW_TABLE_BASKETS.'.basket_id,
                '.NEW_TABLE_SITES.'.site_id,
                '.NEW_TABLE_SITES.'.site_short,
                '.NEW_TABLE_SITES.'.site_long,
                '.NEW_TABLE_BASKETS.'.checked_out
              FROM
                '.NEW_TABLE_BASKETS.'
              LEFT JOIN '.NEW_TABLE_SITES.' USING(site_id)
              WHERE
                '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id ()).'"
                AND '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
            $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 783032 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysql_fetch_object ($result))
              {
                self::$basket_id = $row->basket_id;
                self::$site_id = $row->site_id;
                self::$site_short = $row->site_short;
                self::$site_long = $row->site_long;
                self::$basket_checked_out = $row->checked_out;
                self::$query_complete = true;
              }
          }
      }
    public static function basket_id ()
      {
        self::get_basket_info ();
        return self::$basket_id;
      }
    public static function site_id ()
      {
        self::get_basket_info ();
        return self::$site_id;
      }
    public static function site_short ()
      {
        self::get_basket_info ();
        return self::$site_short;
      }
    public static function site_long ()
      {
        self::get_basket_info ();
        return self::$site_long;
      }
    public static function basket_checked_out ()
      {
        self::get_basket_info ();
        return self::$basket_checked_out;
      }
  }

class CurrentMember
  {
    private static $query_complete = false;
    private static $pending = false;
    private static $username = false;
    private static $auth_type = false;
    private static $business_name = false;
    private static $first_name = false;
    private static $last_name = false;
    private static $first_name_2 = false;
    private static $last_name_2 = false;
    private static function get_member_info ()
      {
        if (self::$query_complete === false)
          {
            global $connection;
            $query = '
              SELECT
                pending,
                username,
                auth_type,
                business_name,
                first_name,
                last_name,
                first_name_2,
                last_name_2
              FROM
                '.TABLE_MEMBER.'
              WHERE
                member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
            $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 683243 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            if ($row = mysql_fetch_object ($result))
              {
                self::$pending = $row->pending;
                self::$username = $row->username;
                self::$auth_type = array ();
                self::$auth_type = explode (',', $row->auth_type);
                self::$business_name = $row->business_name;
                self::$first_name = $row->first_name;
                self::$last_name = $row->last_name;
                self::$first_name_2 = $row->first_name_2;
                self::$last_name_2 = $row->last_name_2;
                self::$query_complete = true;
              }
          }
      }
    public static function pending ()
      {
        self::get_member_info ();
        return self::$pending;
      }
    public static function username ()
      {
        self::get_member_info ();
        return self::$username;
      }
    public static function auth_type ($test_auth)
      {
        self::get_member_info ();
        foreach (explode (',', $test_auth) as $needle)
          {
            if (is_array (self::$auth_type) && in_array ($needle, self::$auth_type))
              return true;
          }
          return false;
      }
    public static function business_name ()
      {
        self::get_member_info ();
        return self::$business_name;
      }
    public static function first_name ()
      {
        self::get_member_info ();
        return self::$first_name;
      }
    public static function last_name ()
      {
        self::get_member_info ();
        return self::$last_name;
      }
    public static function first_name_2 ()
      {
        self::get_member_info ();
        return self::$first_name_2;
      }
    public static function last_name_2 ()
      {
        self::get_member_info ();
        return self::$last_name_2;
      }
    public static function clear_member_info ()
      {
        self::get_member_info ();
        self::$pending = false;
        self::$username = false;
        self::$auth_type = false;
        self::$business_name = false;
        self::$first_name = false;
        self::$last_name = false;
        self::$first_name_2 = false;
        self::$last_name_2 = false;
        self::$query_complete = false;
      }
  }
