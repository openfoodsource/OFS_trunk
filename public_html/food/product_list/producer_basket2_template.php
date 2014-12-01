<?php

/*******************************************************************************

NOTES ON USING THIS TEMPLATE FILE...

The heredoc convention is used to simplify quoting.
The noteworthy point to remember is to escape the '$' in
variable names.  But functions pass through as expected.

The short php if-else format is also useful in this context
for inline display (or not) of content elements:
([condition] ? [true] : [false])

All variables in this file are loaded at include-time and interpreted later
so there is no required ordering of the assignments.

All system constants from the configuration file are available to this template




********************************************************************************
Model for the overall product list display might look something like this:

 -- OVERALL PRODUCT LIST ----------------
|                                        |
|     ----- NAVIGATION SECTION -----     |
|    |                              |    |
|     ------------------------------     |
|     -- PRODUCT HEADING SECTION ---     |
|    |                              |    |
|     - PRODUCT SUBHEADING SECTION -     |
|    |                              |    |
|     -- PRODUCT LISTING SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     - PRODUCT SUBHEADING SECTION -     |
|    |                              |    |
|     -- PRODUCT LISTING SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     ----- NAVIGATION SECTION -----     |
|    |                              |    |
|     ------------------------------     |
|                                        |
 ----------------------------------------

*/

/********************** MISC MARKUP AND CALCULATIONS *************************/

function wholesale_text_html()
  { return
    '<br /><br /><center style="color:#f00;letter-spacing:5px;">** FEATURED WHOLESALE ITEM **</center>';
  };

function no_product_message()
  { return
    '<h2>No products currently available</h2>';
  };

// RANDOM_WEIGHT_DISPLAY_CALC
function random_weight_display_calc($data)
  { return
    ($data['random_weight'] == 1 ?
    'You will be billed for exact '.$row['meat_weight_type'].' weight ('.
    ($data['minimum_weight'] == $data['maximum_weight'] ?
    $data['minimum_weight'].' '.Inflect::pluralize_if ($data['minimum_weight'], $data['pricing_unit'])
      :
    'between '.$data['minimum_weight'].' and '.$data['maximum_weight'].' '.Inflect::pluralize ($data['pricing_unit'])).')'
  :
    '');
  };

// TOTAL_DISPLAY_CALC
function total_display_calc(&$data)
  {
    $data['total_basket_amount'] = '';
    return
    ($data['unit_price']  != 0 ? '<span id="producer_adjusted_cost'.$data['bpid'].'">$&nbsp;'.number_format($data['producer_adjusted_cost'], 2).'</span>' : '').
    ($data['unit_price'] != 0 && $data['extra_charge'] != 0 ? '<br />' : '').
    ($data['extra_charge'] != 0 ? '<span id="extra_charge'.$data['bpid'].'">'.($data['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format($data['basket_quantity'] * abs($data['extra_charge']), 2).'</span>' : '');
  };


// PRICING_DISPLAY_CALC
function pricing_display_calc($data)
  { return
    ($data['unit_price'] != 0 ?
    '$&nbsp;'.number_format($data['unit_price'], 2).'/'.($data['random_weight'] ? $data['pricing_unit'] : $data['ordering_unit'])
    : '').
    ($data['unit_price'] != 0 && $data['extra_charge'] != 0 ?
    '<br />'
    : '').
    ($data['extra_charge'] != 0 ?
    '<span class="extra">'.($data['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format (abs ($data['extra_charge']), 2).'/'.Inflect::singularize ($data['ordering_unit']).'</span><br />'
    : '');
  };

// ORDERING_UNIT_DISPLAY_CALC
function ordering_unit_display_calc($data)
  { return
    ($data['inventory_quantity'] > 0 || !$data['inventory_id'] ?
    'Order number of '.Inflect::pluralize ($data['ordering_unit']).'. '
    : '');
  };


// INVENTORY_DISPLAY_CALC
function inventory_display_calc($data)
  { return
    ($data['inventory_id'] ?
    '<span id="available'.$data['product_id'].'">'.($data['inventory_quantity'] == 0 ? '[OUT OF STOCK] 0 ' : $data['inventory_quantity']).'</span> '.Inflect::pluralize_if ($data['inventory_quantity'], $data['ordering_unit']).' available. '
    : '');
  };


// IMAGE_DISPLAY_CALC
function image_display_calc($data)
  { return
    ($data['image_id'] ?
    '<img src="'.get_image_path_by_id ($data['image_id']).'" class="product_image">'
    : '');
  };


// PRODTYPE_DISPLAY_CALC
function prodtype_display_calc($data)
  { return
    ($data['prodtype_id'] == 5 ? '' : $data['prodtype']).
    (strtolower ($_SESSION['producer_id_you']) == $data['producer_id'] ? '<br />['.$data['storage_code'].']' : '');
  };


// BUSINESS_NAME_DISPLAY_CALC
function business_name_display_calc($data)
  { return
    '<a href="product_list.php?producer_id='.$data['producer_id'].'">'.$data['producer_name'].'</a>';
  };

// ORDERING_LINK_CALC
function row_activity_link_calc($data, $pager)
  {
    $input = '
      <form action="'.$_SERVER['SCRIPT_NAME'].'?type='.$_GET['type'].
        ($data['delivery_id'] ? '&delivery_id='.$data['delivery_id'] : '').
        ($pager['this_page'] ? '&page='.$pager['this_page'] : '').
        ($data['bpid'] ? '#X'.$data['bpid'] : ''). // Anchor
        '" method="post">
          <input type="hidden" name="order_amount'.$data['bpid'].'" value="'.$data['basket_quantity'].'">
          <input type="hidden" name="action" value="set_weight_quantity">
          <input type="hidden" name="bpid" value="'.$data['bpid'].'">
          <input type="hidden" name="process_type" value="producer_basket">
          <input type="submit" style="display:none;">
      <td class="activity">
        <div class="ordered">
          <!-- <span class="order_text">Ordered:<br /></span> -->
          <span class="order_amount">'.$data['basket_quantity'].'<br /></span>
        </div>
        <div class="shipped">
          <span class="order_text">Shipped:<br /></span>
          <span class="order_fill"><input type="text" id="ship_quantity'.$data['bpid'].'" name="ship_quantity'.$data['bpid'].'" value="'.($data['basket_quantity'] - $data['out_of_stock']).'" onchange="SetItem ('.$data['bpid'].',\'set_quantity\'); return false;"><br /></span>
        </div>
        <div class="weight">'.
        ($data['random_weight'] ? '
          <span class="order_text">Weight:<br /></span>
          <span class="order_weight"><input type="text" id="weight'.$data['bpid'].'" name="weight'.$data['bpid'].'" value="'.number_format($data['total_weight'], 2).'" onchange="SetItem ('.$data['bpid'].',\'set_weight\'); return false;"></span>'
          : '').'
        </div>
      </td>';
    return $input;
  };


// PAGER_DISPLAY_CALC
function pager_display_calc($data)
  { return
    '<a href="'.$_SERVER['SCRIPT_NAME'].'?'.
    ($_GET['type'] ? 'type='.$_GET['type'] : '').
    ($_GET['producer_id'] ? '&producer_id='.$_GET['producer_id'] : '').
    ($_GET['category_id'] ? '&category_id='.$_GET['category_id'] : '').
    ($_GET['delivery_id'] ? '&delivery_id='.$_GET['delivery_id'] : '').
    ($_GET['subcat_id'] ? '&subcat_id='.$_GET['subcat_id'] : '').
    ($_GET['query'] ? '&query='.$_GET['query'] : '').
    ($_GET['a'] ? '&a='.$_GET['a'] : '').
    ($data['page'] ? '&page='.$data['page'] : '').
    '" class="'.($data['this_page_true'] ? 'current' : '').($data['page'] == 1 ? ' first' : '').($data['page'] == $data['last_page'] ? ' last' : '').'">&nbsp;'.$data['page'].'&nbsp;</a>';
  };


/************************* PAGER NAVIGATION SECTION ***************************/

function pager_navigation($data)
  { return
    ($data['last_page'] > 1 ?
    '<div class="pager"><span class="pager_title">Page: </span>'.$data['display'].'</div>
  <div class="clear"></div>'
    : '');
  };

/*********************** OPEN BEGINNING OF PRODUCT LIST *************************/

function open_list_top()
  { return
    '<table id="product_list_table">';
  };

function close_list_bottom()
  { return
    '</table>';
  };

/************************** OPEN MAJOR DIVISION ****************************/

function major_division_open($data, $major_division = NULL)
  {
    switch ($major_division)
      {
        // Major division on category
        case 'category_id':
        case 'category_name':
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '
            <tr>
              <td colspan="6" class="header_major">
                '.$data['category_name'].' &mdash; '.$data['subcategory_name'].'
              </td>
            </tr>';
          break;
        // Major division on producer
        case 'producer_id':
        case 'producer_name':
          $header = '
            <tr>
              <td colspan="6" class="header_major">
                '.$data['producer_name'].'
              </td>
            </tr>';
          break;
        // Major division on producer
        case 'site_short':
        case 'site_long':
          $header = '
            <tr>
              <td colspan="6" class="header_major">
                '.$data['site_long'].' ('.$data['site_short'].')
              </td>
            </tr>';
          break;
        // Major division on storage
        case 'storage_code':
        case 'storage_type':
          $header = '
            <tr>
              <td colspan="6" class="header_major">
                '.$data['storage_code'].' ('.$data['storage_type'].')
              </td>
            </tr>';
          break;
        // Major division on producer
        case 'member_id':
        case 'preferred_name':
          $header = '
            <tr>
              <td colspan="7" class="header_minor">
              <font size="-1" face="arial"><b>(#'.$data['member_id'].') '.$data['preferred_name'].'</b></font>
              <div style="float:right;">'.
              ($data['email_address'] ? '<a href="mailto:'.$data['email_address'].'">'.$data['email_address'].'</a> ' : '').
              ($data['home_phone'] ? 'H:'.$data['home_phone'].' ' : '').
              ($data['work_phone'] ? 'W:'.$data['work_phone'].' ' : '').
              ($data['mobile_phone'] ? 'M:'.$data['mobile_phone'].' ' : '').
              '</div>
              </td>
            </tr>';
          break;
        // Otherwise...
          $header = '
          ';
          break;
      }
    return $header;
  };

function major_division_close ()
  { return
    '';
  };

/************************** OPEN MINOR DIVISION ****************************/

function minor_division_open($data, $minor_division = NULL)
  {
    switch ($minor_division)
      {
        // Minor division on category
        case 'category_id':
        case 'category_name':
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '
            <tr>
              <td colspan="6" class="header_minor">
                '.$data['category_name'].' &mdash; '.$data['subcategory_name'].'
              </td>
            </tr>';
          break;
        // Minor division on producer
        case 'producer_id':
        case 'producer_name':
          $header = '
            <tr>
              <td colspan="6" class="header_minor">
                <a href="'.PATH.'producers/'.$data['producer_link'].'">'.$data['producer_name'].'</a>
              </td>
            </tr>';
          break;
        // Minor division on product
        case 'product_id':
        case 'product_name':
          $header = '
            <tr id="Y'.$data['product_id'].'">
              <td colspan="5" class="header_minor">
                '.$data['image_display'].'
              <strong>(#'.$data['product_id'].') '.$data['product_name'].'</strong>
              <br />'.$data['inventory_display'].$data['ordering_unit_display'].$data['random_weight_display'].'
              '.$data['product_description'].'
              </td>
            </tr>';
          break;
        // Minor division on member
        case 'member_id':
        case 'preferred_name':
          $header = '
            <tr>
              <td colspan="7" class="header_minor">
                <font size="-1" face="arial"><b>(#'.$data['member_id'].') '.$data['preferred_name'].'</b></font>
              <div style="float:right;">'.
                ($data['email_address'] ? '<a href="mailto:'.$data['email_address'].'">'.$data['email_address'].'</a> ' : '').
                ($data['home_phone'] ? 'H:'.$data['home_phone'].' ' : '').
                ($data['work_phone'] ? 'W:'.$data['work_phone'].' ' : '').
                ($data['mobile_phone'] ? 'M:'.$data['mobile_phone'].' ' : '').
              '</div>
              </td>
            </tr>';
          break;
        // Otherwise...
          $header = '
          ';
          break;
      }
    return $header;
  };

function minor_division_close ()
  { return
    '';
  };

/************************* LISTING FOR PRODUCT SORTS **************************/

function show_listing_row($data, $row_type)
  {
    switch ($row_type)
      {
        // Row type
        case 'product_short':
          $row_content = '
            <tr id="Y'.$data['product_id'].'"'.($data['is_wholesale_item'] ? ' class="wholesale"' : '').'>
              '.$data['row_activity_link'].'
              <td class="product_short">
                <strong>#'.$data['product_id'].' &ndash; '.$data['product_name'].'</strong>
                <br />'.$data['inventory_display'].$data['ordering_unit_display'].$data['random_weight_display'].
                ($data['message'] ? '<br /><br /><span class="message_to_producer">&bull; '.$data['message'].'</span>' : '').'
              </td>
              <td class="storage">
                <span class="storage_code">'.$data['storage_code'].'</span><br /><span class="storage_type">('.$data['storage_type'].')</span>
              </td>
              <td class="pricing">
                '.$data['pricing_display'].'
              </td>
              <td class="total">
                '.$data['total_display'].'
              </td>';
          break;
        // Row type
        case 'member_short':
          $row_content = '
            <tr id="Y'.$data['product_id'].'"'.($data['is_wholesale_item'] ? ' class="wholesale"' : '').'>
              '.$data['row_activity_link'].'
              <td class="member_short">
                <strong>#'.$data['member_id'].' &ndash; '.$data['preferred_name'].'</strong>
                <div>'.
                  ($data['email_address'] ? '<a href="mailto:'.$data['email_address'].'">'.$data['email_address'].'</a> ' : '').
                  ($data['home_phone'] ? 'H:'.$data['home_phone'].' ' : '').
                  ($data['work_phone'] ? 'W:'.$data['work_phone'].' ' : '').
                  ($data['mobile_phone'] ? 'M:'.$data['mobile_phone'].' ' : '').
                '</div>'.
                ($data['message'] ? '<br /><span class="message_to_producer">&bull; '.$data['message'].'</span>' : '').'
              </td>
              <td class="delcode">
                '.$data['delcode'].' ('.$data['site_short'].')
              </td>
              <td class="pricing">
                '.$data['pricing_display'].'
              </td>
              <td class="total">
                '.$data['total_display'].'
              </td>';
          break;
        // Row type
        case 'product_short_delcode':
          $row_content = '
            <tr id="Y'.$data['product_id'].'"'.($data['is_wholesale_item'] ? ' class="wholesale"' : '').'>
              '.$data['row_activity_link'].'
              <td class="product_short">
                <strong>#'.$data['product_id'].' &ndash; '.$data['product_name'].'</strong>
                <br />'.$data['inventory_display'].$data['ordering_unit_display'].$data['random_weight_display'].
                ($data['message'] ? '<br /><br /><span class="message_to_producer">&bull; '.$data['message'].'</span>' : '').'
              </td>
              <td class="delcode">
                '.$data['delcode'].' ('.$data['site_short'].')
              </td>
              <td class="pricing">
                '.$data['pricing_display'].'
              </td>
              <td class="total">
                '.$data['total_display'].'
              </td>';
          break;
        // Row type
        case 'product':
          $row_content = '
            <tr>
              <td colspan="6">
                NOTHING YET 752190
              </td>
            </tr>';
          break;
        // Otherwise...
          $row_content = '
          ';
          break;
      }
    return $row_content;
  };

// Need:
//   X display customer_message
//     edit/add customer message
//     checked_out
//     random weight
//     total charge(s)
//     taxable
//     checkout button
//     out_of_stock
//     ...

/******************************************************************************/

//             <tr>
//               <th align="center" bgcolor="#DDDDDD" width="10%">Order</th>
//               <th align="center" bgcolor="#DDDDDD" width="10%">ID</th>
//               <th align="center" bgcolor="#DDDDDD" width="45%">Product Name</th>
//               <th align="center" bgcolor="#DDDDDD" width="10%">Producer</th>
//               <th align="center" bgcolor="#DDDDDD" width="10%">Type</th>
//               <th align="center" bgcolor="#DDDDDD" width="15%">Price</th>
//             </tr>



?>