<?php

include_once ('func.open_update_basket.php');
include_once ('func.get_basket.php');

// This function will get the html markup for a div containing formatted basket history.
// Sample/suggested CSS is given at the end.
function get_delivery_codes_list ($request_data)
  {
    global $connection;
    // See if it is okay to open a basket...
    if (ActiveCycle::delivery_id() &&
        ActiveCycle::ordering_window() == 'open')
//        && ! CurrentBasket::basket_id())
      {
        // If requested to open-basket...
        if ($request_data['action'] == 'open_basket')
          {
            if ($request_data['site_id'] &&
                $request_data['delivery_type'])
              {
                $site_id = $request_data['site_id'];
                $delivery_type = $request_data['delivery_type'];
                // First try an assigned delivery_id... then use the current active one
                $delivery_id = $request_data['delivery_id'];
                if (! $delivery_id) $delivery_id = ActiveCycle::delivery_id();
                // First try an assigned member_id... then use the current session one
                $member_id = $request_data['member_id'];
                if (! $member_id) $member_id = $_SESSION['member_id'];
                // Update the basket
                $basket_info = open_update_basket(array(
                  'member_id' => $member_id,
                  'delivery_id' => $delivery_id,
                  'site_id' => $site_id,
                  'delivery_type' => $delivery_type
                  ));
              }
          }
        // Get current basket information
        else
          {
            $basket_info = get_basket($request_data['member_id'], $request_data['delivery_id']);
          }

//         // Ordering is open and there is no basket open yet
//         // Get this member's most recent delivery location
//         $query = '
//           SELECT
//             '.NEW_TABLE_SITES.'.site_id,
//             '.NEW_TABLE_SITES.'.deltype
//           FROM
//             '.NEW_TABLE_BASKETS.'
//           LEFT JOIN
//             '.NEW_TABLE_SITES.' USING(site_id)
//           WHERE
//             '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string($_SESSION['member_id']).'"
//             AND '.NEW_TABLE_SITES.'.inactive = "0"
//           ORDER BY
//             delivery_id DESC
//           LIMIT
//             1';
//           $result = mysql_query ($query, $connection) or die(debug_print ("ERROR: 548167 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//           if ($row = mysql_fetch_array ($result))
//             {
//               $site_id_prior = $row['site_id'];
//               $deltype_prior = $row['deltype'];
//             }
        // Constrain this shopper's baskets to the site_type they are enabled to use
        $site_type_constraint = '';
        if (CurrentMember::auth_type('member'))
          {
            $site_type_constraint .= '
              '.(strlen ($site_type_constraint) > 0 ? 'OR ' : '').'site_type LIKE "%customer%"';
          }
        if (CurrentMember::auth_type('institution'))
          {
            $site_type_constraint .= '
              '.(strlen ($site_type_constraint) > 0 ? 'OR ' : '').'site_type LIKE "%institution%"';
          }
        $site_type_constraint = '
            AND ('.$site_type_constraint.'
              )';

        // Now get the list of all available delivery codes and flag the one
        // that corresponds to this member's prior order
        $query = '
          SELECT
            '.NEW_TABLE_SITES.'.site_id,
            '.NEW_TABLE_SITES.'.site_short,
            '.NEW_TABLE_SITES.'.site_long,
            '.NEW_TABLE_SITES.'.delivery_type,
            '.NEW_TABLE_SITES.'.site_description,
            '.NEW_TABLE_SITES.'.delivery_charge,
            '.NEW_TABLE_SITES.'.inactive,
            '.TABLE_MEMBER.'.address_line1,
            '.TABLE_MEMBER.'.work_address_line1
          FROM
            ('.NEW_TABLE_SITES.',
            '.TABLE_MEMBER.')
          WHERE
            '.NEW_TABLE_SITES.'.inactive != "1"
            AND '.TABLE_MEMBER.'.member_id = "'.mysql_real_escape_string($_SESSION['member_id']).'"'.
            $site_type_constraint.'
          ORDER BY
            site_long';
        $result = mysql_query ($query, $connection) or die(debug_print ("ERROR: 671934 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $site_id_array = array ();
        $delivery_type_array = array ();
        $display .= '
            <div id="delivery_dropdown" class="dropdown">
              <a href="'.$_SERVER['SCRIPT_NAME'].'?action=delivery_list_only"><h1 class="delivery_select">'.
                ($basket_info['site_id'] ? 'Selected: '.$basket_info['site_long'] : 'Select Location').'
              </h1></a>
              <div id="delivery_select">
                <ul class="delivery_select">';
        while ($row = mysql_fetch_array ($result))
          {
            // Simplify variables
            $site_id = $row['site_id'];
            $site_long = $row['site_long'];
            $delivery_type = $row['delivery_type'];
            $site_description = $row['site_description'];
            $delivery_charge = $row['delivery_charge'];
            $inactive = $row['inactive'];
            $address = $row['address_line1'];
            $work_address = $row['work_address_line1'];
            // Set up some text for the $delivery type (delivery or pickup)
            if ($delivery_type == 'P')
              {
                $delivery_type_text = 'Pick up your order here';
                $delivery_type_class = 'delivery_type-p';
              }
            elseif ($delivery_type == 'D')
              {
                $delivery_type_text_h = 'HOME delivery';
                $delivery_type_text_w = 'WORK delivery';
                if ($delivery_charge)
                  {
                    $delivery_type_text_h .= ' ($'.number_format($delivery_charge, 2).' charge)';
                    $delivery_type_text_w .= ' ($'.number_format($delivery_charge, 2).' charge)';
                  }
                $delivery_type_class = 'delivery_type-d';
              }
            else
              {
                $delivery_type_text = '';
                $delivery_type_class = '';
              }
            // Process the inactive options
            if ($inactive == 0)
              {
                $show_site = true;
                $active_class = ' active';
                $select_link_href   = $_SERVER['SCRIPT_NAME'].'?action=open_basket&amp;site_id='.$site_id.'&amp;delivery_type=P';
                $select_link_h_href = $_SERVER['SCRIPT_NAME'].'?action=open_basket&amp;site_id='.$site_id.'&amp;delivery_type=H';
                $select_link_w_href = $_SERVER['SCRIPT_NAME'].'?action=open_basket&amp;site_id='.$site_id.'&amp;delivery_type=W';
                $delivery_type_class .= 'a'; // color
              }
            elseif ($inactive == 2)
              {
                $show_site = true;
                $active_class = ' inactive';
                $select_link_href   = '';
                $select_link_h_href = '';
                $select_link_w_href = '';
                $delivery_type_class .= 'i'; // color
                $delivery_type_text = '(Not available for pick up this cycle)'; // clobber the delivery type text
                $delivery_type_text_h = '(Not available for home delivery this cycle)'; // clobber the delivery type text
                $delivery_type_text_w = '(Not available for work delivery this cycle)'; // clobber the delivery type text
              }
            else // ($inactive == 1)
              {
                $show_site = false;
                $active_class = ' suspended';
                $select_link_href   = '';
                $select_link_h_href = '';
                $select_link_w_href = '';
                $delivery_type_class .= 'i'; // color
                $delivery_type_text = '(Not available for pick up this cycle)'; // clobber the delivery type text
                $delivery_type_text_h = '(Not available for home delivery this cycle)'; // clobber the delivery type text
                $delivery_type_text_w = '(Not available for work delivery this cycle)'; // clobber the delivery type text
              }
            // Process current selection
            if ($site_id == CurrentBasket::site_id())
              {
                $selected = true;
                $select_class = ' select';
                $delivery_type_class .= 'c'; // color
              }
            else
              {
                $selected = 'false';
                $select_class = '';
                $delivery_type_class .= 'g'; // greyscale
              }
            if ($show_site == true)
              {
                if ($delivery_type == 'P')
                  {
                    $display .= '
                  <li class="'.$delivery_type_class.$active_class.$select_class.'" '.($select_link_href != '' ? 'onclick="javascript:location.href=\''.$select_link_href : '').'\';parent.close_delivery_selector();">
                      <span class="site_long">'.$site_long.'</span>
                      <span class="site_action">'.$delivery_type_text.'</span>
                      <span class="site_description">'.br2nl($site_description).'</span>
                  </li>';
                  }
                // For delivery_type = delivery, we will give an option for "home"
                if ($delivery_type == 'D' && $address)
                  {
                    if ($basket_info['delivery_type'] != 'H') $select_class = '';
                    $display .= '
                  <li class="'.$delivery_type_class.$active_class.$select_class.'" '.($select_link_h_href != '' ? 'onclick="javascript:location.href=\''.$select_link_h_href : '').'\';parent.close_delivery_selector();">
                      <span class="site_long">'.$site_long.'</span>
                      <span class="site_action">'.$delivery_type_text_h.'</span>
                      <span class="site_description"><strong>To home address:</strong> '.$address.'<br>'.br2nl($site_description).'</span>
                  </li>';
                  }
                // For delivery_type = delivery, we will also give an option for "work"
                if ($delivery_type == 'D' && $work_address)
                  {
                    if ($basket_info['delivery_type'] != 'W') $select_class = '';
                    $display .= '
                  <li class="'.$delivery_type_class.$active_class.$select_class.'" '.($select_link_w_href != '' ? 'onclick="javascript:location.href=\''.$select_link_w_href : '').'\';parent.close_delivery_selector();">
                      <span class="site_long">'.$site_long.'</span>
                      <span class="site_action">'.$delivery_type_text_w.'</span>
                      <span class="site_description"><strong>To work address:</strong> '.$work_address.'<br>'.br2nl($site_description).'</span>
                  </li>';
                  }
              }
          }
        $display .= '
                </ul>
              </div>
            </div>';
      }
    return $display;
  }

?>