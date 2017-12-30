<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');

include_once ('func.open_update_basket.php');
// include_once ('func.get_basket.php');

// Functional process for selecting customer pickup sites
//
// Data propagates from...
// customer_select_site.php -> $_COOKIE['ofs_customer']['site_id'] -> $_SESSION['ofs_customer']['site_id'] -> baskets['site_id']
//
// If the user does not have $_COOKIE['ofs_customer']['site_id'] set, it is asked.
// Then if the user is also logged in, the value is assigned to $_SESSION['ofs_customer']['site_id']
//
// If the user has $_COOKIE['ofs_customer']['site_id'] set, it generates an alert popup
//   with option to change to another site. The value then propagates to $_SESSION['ofs_customer']['site_id']
//
// When any product is ordered, then the $_SESSION['ofs_customer']['site_id'] is propagated to baskets['site_id']

$delivery_codes_list = '';

// If not the first_call (i.e. after being clicked), tell javascript to close the window.
if (isset ($_GET['completion_action'])) $completion_action = $_GET['completion_action'];
else $completion_action = 'parent.reload_parent()';
// We will call this page with $_GET['after_select'] which will contain the $_GET['completion_action'] for disposition after submission

// If we requested to select a site, then update the $_COOKIE, (possibly) the $_SESSION,
// and possibly the basket, then reload the parent page
if ($_GET['action'] == 'select_site')
  {
    if (isset ($_GET['site_id'])
        && isset ($_GET['delivery_type']))
      {
        // Logged in? Then set the $_SESSION['ofs_customer']['site']
        if (isset ($_SESSION['member_id']))
          {
            $_SESSION['ofs_customer']['site_id'] = $_GET['site_id'];
            $_SESSION['ofs_customer']['delivery_type'] = $_GET['delivery_type'];
          }
        // If a basket is already open, then change the site
        if (CurrentBasket::basket_id() > 0 && isset ($_SESSION['member_id']))
          {
            include_once ('func.open_update_basket.php');
            // Update the basket
            $basket_info = open_update_basket(array(
              'member_id' => $_SESSION['member_id'],
              'delivery_id' => ActiveCycle::delivery_id(),
              'site_id' => $_GET['site_id'],
              'delivery_type' => $_GET['delivery_type']
              ));
          }
        // Regardless: Set the $_COOKIE['ofs_customer']['site_id']
        $domain_name = trim (explode ("\n", DOMAIN_NAME)[0]);
        setcookie('ofs_customer[site_id]', $_GET['site_id'], strtotime ('+ 1 day'), '/', '.'.$domain_name);
        setcookie('ofs_customer[delivery_type]', $_GET['delivery_type'], strtotime ('+ 1 day'), '/', '.'.$domain_name);
        // Now we have completed the purpose of this popup, so
        $modal_action = $completion_action;
      }
  }

// Constrain this shopper's baskets to the site_type they are enabled to use
$site_type_constraint = '
  AND (
    site_type LIKE "%customer%"'.
    (CurrentMember::auth_type('institution') ? '
      OR site_type LIKE "%institution%"'
    : '').'
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
    '.NEW_TABLE_SITES.'.inactive
  FROM '.NEW_TABLE_SITES.'
  WHERE
    '.NEW_TABLE_SITES.'.inactive != "1"'.
    $site_type_constraint.'
  ORDER BY
    site_long';
$result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 671934 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$site_id_array = array ();
$delivery_type_array = array ();

// If we already have a $_COOKIE or $_SESSION value and are about to create a new basket,
// then just confirm we have the correct site
$just_confirm = false;
if (isset ($_GET['confirm_site']) && $_GET['confirm_site'] == 'true')
  {
    $just_confirm = true;
    $confirm_message_top = '
        <div class="confirm_site_message">
          Please select the site below to confirm that your order should be delivered to that location.
        </div>';
    $confirm_message_bottom = '
        <div class="confirm_site_message">
          Or you may <a href="'.$_SERVER['SCRIPT_NAME'].(strlen ($completion_action) > 0 ? '?completion_action='.$completion_action : '').'">switch to another site</a>.
        </div>';
    $confirm_site = $basket_info['site_id'];
  }

// Set up the customer_select_site header message
$list_header = '';
if ($basket_info['site_id'] > 0) $list_header = 'Selected: '.$basket_info['site_long'];
elseif ($just_confirm) $list_header = 'Confirm site for delivery';
else /*if (USE_AVAILABILITY_MATRIX && ! isset ($_SESSION['member_id'])  )*/ $list_header = 'Select a site to view available products';
/* else $list_header = 'Select a site to receive your order'; */

$delivery_codes_list .= '
    <div id="delivery_dropdown" class="dropdown">
      <h1 class="delivery_select">'.
        $list_header.'
      </h1>'.
      $confirm_message_top.'
      <div id="delivery_select">
        <ul class="delivery_select">';

// Default to use the current basket site_id, if it exists
$ofs_customer_site_id = CurrentBasket::site_id()
  // If not, then use the session site_id, if it exists
  or $ofs_customer_site_id = $_SESSION['ofs_customer']['site_id']
  // If not, then use the cookie site_id, if it exists
  or $ofs_customer_site_id = $_COOKIE['ofs_customer']['site_id']
  // Or set site_id = 0;
  or $ofs_customer_site_id = 0;

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
    // Only show the selected site if we are just confirming
    if (! $just_confirm || $site_id == $ofs_customer_site_id)
      {
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
            $select_link_href   = $_SERVER['SCRIPT_NAME'].'?action=select_site&amp;site_id='.$site_id.'&amp;delivery_type=P'.(strlen ($completion_action) > 0 ? '&amp;completion_action='.$completion_action : '');
            $select_link_h_href = $_SERVER['SCRIPT_NAME'].'?action=select_site&amp;site_id='.$site_id.'&amp;delivery_type=H'.(strlen ($completion_action) > 0 ? '&amp;completion_action='.$completion_action : '');
            $select_link_w_href = $_SERVER['SCRIPT_NAME'].'?action=select_site&amp;site_id='.$site_id.'&amp;delivery_type=W'.(strlen ($completion_action) > 0 ? '&amp;completion_action='.$completion_action : '');
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
        if ($site_id == $ofs_customer_site_id)
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
  }
$delivery_codes_list .= '
        </ul>
      </div>'.
      $confirm_message_bottom.'
    </div>';

// Add styles to override delivery location dropdown
$page_specific_css .= '
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
    background:url('.PATH.'grfx/deltype-dag.png) no-repeat 7px center;
    }
  li.delivery_type-dac {
    background:url('.PATH.'grfx/deltype-dac.png) no-repeat 7px center;
    }
  li.delivery_type-dig {
    background:url('.PATH.'grfx/deltype-dig.png) no-repeat 7px center;
    }
  li.delivery_type-pag {
    background:url('.PATH.'grfx/deltype-pag.png) no-repeat 7px center;
    }
  li.delivery_type-pac {
    background:url('.PATH.'grfx/deltype-pac.png) no-repeat 7px center;
    }
  li.delivery_type-pig {
    background:url('.PATH.'grfx/deltype-pig.png) no-repeat 7px center;
    }';

$page_specific_javascript = '
  function product_list_and_close () {
   if (elem = parent.document.getElementById("site_id")) {
     elem.value = "'.$_GET['site_id'].'";
     }
    // parent.add_to_cart_array are pre-set when the customer clicks on a product to order
    // ... so we proceed to do the "add to cart"
    parent.adjust_customer_basket (parent.add_to_cart_array[0], parent.add_to_cart_array[1], parent.add_to_cart_array[2]);
    // Then close the modal without reloading
    parent.jQuery.modal.close();
    };';

// This is ALWAYS a popup
$display_as_popup = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$delivery_codes_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
