<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

include_once ('func.open_update_basket.php');
include_once ('func.get_basket.php');

$delivery_codes_list = '';

// If not the first_call (i.e. after being clicked), tell javascript to close the window.
if (isset ($_GET['modal_action'])) $modal_action = $_GET['modal_action'];
else $modal_action = '';
// We will call this page with $_GET['after_select'] which will contain the $_GET['modal_action'] for disposition after submission
// Values can be "just_close" or "reload_parent".


// See if it is okay to open a basket...
if (ActiveCycle::delivery_id() &&
    ( ActiveCycle::ordering_window() == 'open' ||
      CurrentMember::auth_type('orderex')))
//        && ! CurrentBasket::basket_id())
  {
    // If requested to open-basket...
    if ($_GET['action'] == 'open_basket')
      {
        if ($_GET['site_id'] &&
            $_GET['delivery_type'])
          {
            $site_id = $_GET['site_id'];
            $delivery_type = $_GET['delivery_type'];
            // First try an assigned delivery_id... then use the current active one
            $delivery_id = ActiveCycle::delivery_id();
            if (! $delivery_id) $delivery_id = ActiveCycle::delivery_id();
            // First try an assigned member_id... then use the current session one
            $member_id = $_SESSION['member_id'];
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
        $basket_info = get_basket($_SESSION['member_id'], ActiveCycle::delivery_id());
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
//             '.NEW_TABLE_BASKETS.'.member_id = "'.mysqli_real_escape_string($connection, $_SESSION['member_id']).'"
//             AND '.NEW_TABLE_SITES.'.inactive = "0"
//           ORDER BY
//             delivery_id DESC
//           LIMIT
//             1';
//           $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 548167 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
//           if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
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
        AND '.TABLE_MEMBER.'.member_id = "'.mysqli_real_escape_string($connection, $_SESSION['member_id']).'"'.
        $site_type_constraint.'
      ORDER BY
        site_long';
    $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 671934 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $site_id_array = array ();
    $delivery_type_array = array ();
    $delivery_codes_list .= '
        <div id="delivery_dropdown" class="dropdown">
          <h1 class="delivery_select">'.
              ($basket_info['site_id'] ? 'Selected: '.$basket_info['site_long'] : 'Select Location').'
          </h1>
          <div id="delivery_select">
            <ul class="delivery_select">';
    while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        // Simplify variables
        $site_id = $row['site_id'];
        $site_short = $row['site_short'];
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
            $delivery_type_text = 'Receive your order here:';
            $delivery_type_class = 'delivery_type-p';
          }
        elseif ($delivery_type == 'D')
          {
            $delivery_type_text_h = 'Delivery';
            $delivery_type_text_w = 'Delivery';
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
            $select_link_href   = $_SERVER['SCRIPT_NAME'].'?action=open_basket&amp;site_id='.$site_id.'&amp;delivery_type=P&modal_action='.$_GET['after_select'];
            $select_link_h_href = $_SERVER['SCRIPT_NAME'].'?action=open_basket&amp;site_id='.$site_id.'&amp;delivery_type=H&modal_action='.$_GET['after_select'];
            $select_link_w_href = $_SERVER['SCRIPT_NAME'].'?action=open_basket&amp;site_id='.$site_id.'&amp;delivery_type=W&modal_action='.$_GET['after_select'];
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
            $delivery_codes_list .= '
              <div class="anchor" id="'.($basket_info['site_id'] == $site_id ? 'target_site' : 'site-'.$site_short).'"></div>';
            if ($delivery_type == 'P')
              {
                $delivery_codes_list .= '
              <li class="'.$delivery_type_class.$active_class.$select_class.'" '.($select_link_href != '' ? 'onclick="javascript:location.href=\''.$select_link_href : '').'\';">
                  <span class="site_long">'.$site_long.'</span>
                  <span class="site_action">'.$delivery_type_text.'</span>
                  <span class="site_description">'.br2nl($site_description).'</span>
              </li>';
              }
            // For delivery_type = delivery, we will give an option for "home"
            if ($delivery_type == 'D' && $address)
              {
                if ($basket_info['delivery_type'] != 'H') $select_class = '';
                $delivery_codes_list .= '
              <li class="'.$delivery_type_class.$active_class.$select_class.'" '.($select_link_h_href != '' ? 'onclick="javascript:location.href=\''.$select_link_h_href : '').'\';">
                  <span class="site_long">'.$site_long.'</span>
                  <span class="site_action">'.$delivery_type_text_h.'</span>
                  <span class="site_description"><strong>To home address:</strong> '.$address.'<br>'.br2nl($site_description).'</span>
              </li>';
              }
            // For delivery_type = delivery, we will also give an option for "work"
            if ($delivery_type == 'D' && $work_address)
              {
                if ($basket_info['delivery_type'] != 'W') $select_class = '';
                $delivery_codes_list .= '
              <li class="'.$delivery_type_class.$active_class.$select_class.'" '.($select_link_w_href != '' ? 'onclick="javascript:location.href=\''.$select_link_w_href : '').'\';">
                  <span class="site_long">'.$site_long.'</span>
                  <span class="site_action">'.$delivery_type_text_w.'</span>
                  <span class="site_description"><strong>To business address:</strong> '.$work_address.'<br>'.br2nl($site_description).'</span>
              </li>';
              }
          }
      }
    $delivery_codes_list .= '
            </ul>
          </div>
        </div>';
  }
else
  {
    $delivery_codes_list .= '
      Ordering is currently closed. Site locations can not be altered outside the ordering period.';
  }

// Add styles to override delivery location dropdown
$page_specific_css .= '
  <style type="text/css">
  body {
    font-size:87%;
    border-radius:15px;
    }
  .dropdown {
    transition:height 0.7s cubic-bezier(1,0,0.5,1);
    }
  /* Styles for the delivery dropdown */
  a:hover {
    text-decoration:none;
    }
  #delivery_dropdown {
    border:0;
    width:100%;
    }
  h1.delivery_select {
    position:fixed;
    top:0;
    box-sizing:border-box;
    width:100%;
    font-size:16px;
    color:#050;
    background-color:#fff;
    height:26px;
    margin:0;
    padding:3px;
    text-align:center;
    }
  /* Push anchor down below the "position:fixed" header */
  .anchor {
    height:0;
    visibility:hidden;
    position:relative;
    top:-100px;
    }
  #delivery_select {
    width:100%;
    background-color:#fff;
    margin-top:26px;
    }
  ul.delivery_select {
    list-style-type:none;
    padding-left:0;
    margin:30px 0 100px 0;
    }
  ul.delivery_select li {
    padding-top:10px;
    text-align:left;
    padding-bottom:20px;
    padding-left:70px;
    border-top:1px solid transparent;
    border-bottom:1px solid transparent;
    }
  ul.delivery_select li.active:hover {
    cursor:pointer;
    background-color:#efd;
    border-top:1px solid #ad6;
    border-bottom:1px solid #ad6;
    }
  ul.delivery_select li span {
    vertical-align: middle;
    }
  ul.delivery_select li.select {
    background-color:#eeb;
    border-top:1px solid #cba;
    border-bottom:1px solid #cba;
    }
  .site_long {
    display:block;
    font-size:130%;
    font-weight:bold;
    color:#350;
    }
  .site_action {
    font-weight:bold;
    color:#066;
    }
  .inactive .site_action {
    font-weight:bold;
    color:#d50;
    }
  .suspended .site_action {
    font-weight:bold;
    color:#a00;
    }
  .site_description {
    display:block;
    font-size:80%;
    color:#530;
    }
  li.delivery_type-dag {
    background:url(/food/grfx/deltype-dag.png) no-repeat 7px center;
    }
  li.delivery_type-dac {
    background:url(/food/grfx/deltype-dac.png) no-repeat 7px center;
    }
  li.delivery_type-dig {
    background:url(/food/grfx/deltype-dig.png) no-repeat 7px center;
    }
  li.delivery_type-pag {
    background:url(/food/grfx/deltype-pag.png) no-repeat 7px center;
    }
  li.delivery_type-pac {
    background:url(/food/grfx/deltype-pac.png) no-repeat 7px center;
    }
  li.delivery_type-pig {
    background:url(/food/grfx/deltype-pig.png) no-repeat 7px center;
    }
  </style>';

// This is ALWAYS a popup
$display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$delivery_codes_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
