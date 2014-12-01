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
    '<br><br><center style="color:#f00;letter-spacing:5px;">** FEATURED WHOLESALE ITEM **</center>';
  };

function no_product_message()
  { return
    '<h2>No products in basket</h2>';
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
function total_display_calc($data, &$unique)
  {
    // Random weight w/o weight gets a special note
    if ($data['weight_needed'] == true)
      {
        $estimate_text = '(estimated)<br>';
        $estimate_class = 'estimate';
        $unique['weight_needed'] = true;
      }
    // Only show zero amounts when there is no extra charge
    if ($data['customer_adjusted_cost'] != 0 || $data['extra_charge'] == 0)
      {
        $total_display = '
          <span id="customer_adjusted_cost'.$data['bpid'].'" class="'.$estimate_class.'">'.$estimate_text.'$'.number_format($data['customer_adjusted_cost'], 2).'</span>';
      }
    // If there is any content so far, then add a newline
    if ($total_display)
      {
        $total_display .= '
          <br>';
      }
    if ($data['extra_charge'] != 0)
      {
        $total_display .= '
          <span id="extra_charge'.$data['bpid'].'">'.($data['extra_charge'] > 0 ? ($data['unit_price']  != 0 ? '+' : '') : '-').'&nbsp;$'.number_format($data['basket_quantity'] * abs($data['extra_charge']), 2).'</span>';
      }
    $unique['running_total'] = $unique['running_total'] + $data['customer_adjusted_cost'] + ($data['basket_quantity'] * $data['extra_charge']);
    return $total_display;
  };

// PRICING_DISPLAY_CALC
// function pricing_display_calc($data)
//   { return
//     ($data['display_retail_price'] && $data['display_unit_retail_price'] ?
//     '<span class="retail">'.($_GET['type'] == 'producer_list' ? 'Retail: ' : '').'$'.number_format(round($data['display_unit_retail_price'], 2), 2).'/'.Inflect::singularize ($data['pricing_unit']).'</span><br>'
//     : '' ).
//     ($data['display_wholesale_price'] && $data['display_unit_wholesale_price'] ?
//     '<span class="whsle">'.($_GET['type'] == 'producer_list' ? 'Whsle: ' : '').'$'.number_format($data['display_unit_wholesale_price'], 2).'/'.Inflect::singularize ($data['pricing_unit']).'</span><br>'
//     : '').
//     ((($data['display_wholesale_price'] && $data['display_unit_wholesale_price']) || ($data['display_retail_price'] && $data['display_unit_retail_price'])) && ($data['extra_charge'] != 0) ?
//     'plus<br>'
//     : '').
//     ($data['extra_charge'] != 0 ?
//     '<span class="extra">'.($data['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format (abs (round($data['extra_charge'], 2)), 2).'/'.Inflect::singularize ($data['ordering_unit']).'</span><br>'
//     : '');
//   };
function pricing_display_calc($data)
  {
    if ($data['display_retail_price'] &&
        $data['display_unit_retail_price'])
      {
        $pricing_display_calc .= '
          <span class="retail">'.($_GET['type'] == 'producer_list' ? 'Retail: ' : '').'$'.number_format(round($data['display_unit_retail_price'], 2), 2).'/'.Inflect::singularize ($data['pricing_unit']).'</span>';
      }
    if ($data['display_wholesale_price'] &&
        $data['display_unit_wholesale_price'])
      {
        $pricing_display_calc .= '
          <span class="whsle">'.($_GET['type'] == 'producer_list' ? 'Whsle: ' : '').'$'.number_format($data['display_unit_wholesale_price'], 2).'/'.Inflect::singularize ($data['pricing_unit']).'</span>';
      }
    if ((($data['display_wholesale_price'] &&
          $data['display_unit_wholesale_price']) ||
         ($data['display_retail_price'] &&
          $data['display_unit_retail_price'])) &&
        ($data['extra_charge'] != 0))
      {
//         $pricing_display_calc .= '
//           <br>and<br>';
      }
    if ($data['extra_charge'] != 0)
      {
        $pricing_display_calc .= '
          <span class="extra">'.($data['extra_charge'] > 0 ? '' : '-&nbsp;').'$'.number_format (abs (round($data['extra_charge'], 2)), 2).'/'.Inflect::singularize ($data['ordering_unit']).'</span>';
      }
    return ($pricing_display_calc);
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
    '<span id="available'.$data['product_id'].'">'.($data['inventory_quantity'] == 0 ? '[OUT OF STOCK] No' : $data['inventory_quantity']).'</span> more '.Inflect::pluralize_if ($data['inventory_quantity'], $data['ordering_unit']).' available. '
    : '');
  };

// IMAGE_DISPLAY_CALC
function image_display_calc($data)
  { return
    ($data['image_id'] ?
    '<img src="'.get_image_path_by_id ($data['image_id']).'" width="100" name="img'.$data['image_id'].'" onclick="javascript:img'.$data['image_id'].'.width=300" onMouseOut="javascript:img'.$data['image_id'].'.width=100" hspace="5" border="1" align="left" alt="Click to enlarge '.htmlentities ($data['product_name'], ENT_QUOTES).'">'
    : '');
  };

// PRODTYPE_DISPLAY_CALC
function prodtype_display_calc($data)
  { return
    ($data['prodtype_id'] == 5 ? '' : $data['prodtype']).
    (strtolower ($_SESSION['producer_id_you']) == $data['producer_id'] ? '<br>['.$data['storage_code'].']' : '');
  };

// BUSINESS_NAME_DISPLAY_CALC
function business_name_display_calc($data)
  { return
    '<a href="'.PATH.'producers/'.$data['producer_link'].'">'.$data['producer_name'].'</a>';
  };

// ORDERING_LINK_CALC
function row_activity_link_calc($data, $pager)
  { return
    '<td class="basket_control" id="activity'.$data['product_id'].'">'.
    (! $data['checked_out'] ?
      ($data['availability'] == true ?
        ($data['basket_quantity'] > 0 || !$data['inventory_id'] || $data['inventory_quantity'] > 0 ?
          ($data['order_open'] ?
            // ADD PRODUCT TO BASKET
            '<form action="'.$_SERVER['SCRIPT_NAME'].'?type='.$_GET['type'].'#X'.$data['product_id'].'" method="post">
               <input id="add'.$data['product_id'].'" class="basket_add" type="image" name="basket_add" src="'.DIR_GRAPHICS.'basket_add.png" width="24" height="24" border="0" alt="Submit" onclick="AddToCart('.$data['product_id'].','.$data['product_version'].',\'add\'); return false;" '.($data['basket_quantity'] > 0 ? ($data['inventory_id'] && $data['inventory_quantity'] == 0 ? 'style="display:none;"' : '') : 'style="display:none;"').'>
               <input id="sub'.$data['product_id'].'" class="basket_sub" type="image" name="basket_sub" src="'.DIR_GRAPHICS.'basket_sub.png" width="24" height="24" border="0" alt="Submit" onclick="AddToCart('.$data['product_id'].','.$data['product_version'].',\'sub\'); return false;" '.($data['basket_quantity'] > 0 ? '' : 'style="display:none;"').'>
               <input type="hidden" name="product_id" value="'.$data['product_id'].'">
               <input type="hidden" name="product_version" value="'.$data['product_version'].'">
               <input type="hidden" name="producer_id" value="'.$data['producer_id'].'">
               <input type="hidden" name="product_id_printed" value="'.$data['product_id'].'">
               <input type="hidden" name="product_name" value="'.$data['product_name'].'">
               <input type="hidden" name="subcategory_id" value="'.$data['subcategory_id'].'">
               <input type="hidden" name="process_type" value="customer_basket">
               <div class="basket_button">
               <input id="basket_empty'.$data['product_id'].'" class="basket" type="image" name="basket" src="'.DIR_GRAPHICS.'basket-egi_add.png" width="48" height="48" border="0" alt="Submit" onClick="AddToCart('.$data['product_id'].','.$data['product_version'].',\'add\'); return false;" '.($data['basket_quantity'] > 0 ? 'style="display:none;"' : '').'>
               <img id="basket_full'.$data['product_id'].'" class="basket" src="'.DIR_GRAPHICS.'basket-fcs.png" width="48" height="48" border="0" '.($data['basket_quantity'] > 0 ? '' : 'style="display:none;"').'>
               </div>
             </form>
             <span id="in_basket'.$data['product_id'].'" class="in_basket" '.($data['basket_quantity'] > 0 ? '' : 'style="display:none;"').'><span id="basket_qty'.$data['product_id'].'" class="basket_qty">'.$data['basket_quantity'].'</span> in basket</span>'
      :
          '
           <span id="in_basket'.$data['product_id'].'" class="in_basket"><span id="basket_qty'.$data['product_id'].'" class="basket_qty_closed">'.$data['basket_quantity'].'</span> in basket</span>'
          )
          :
          // NOT ABLE TO ADD ANYTHING
          ''
        )
      :
      'Unavailable for '.$data['site_long']
      )
    :
    ($data['checked_out'] ? $data['basket_quantity'].' '.Inflect::pluralize_if ($data['basket_quantity'], $data['ordering_unit']).
      ($data['random_weight'] ? '<br>'.
        ($data['total_weight'] ? $data['total_weight'].' '.Inflect::pluralize_if ($data['total_weight'], $data['pricing_unit'])
        :
        '(wt.&nbsp;pending)')
      : '')
    :
    '')).
    '</td>';
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
    ($_GET['member_id'] ? '&member_id='.$_GET['member_id'] : '').
    ($_GET['basket_id'] ? '&basket_id='.$_GET['basket_id'] : '').
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

function open_list_top($data)
  { return
     '<input type="hidden" name="basket_id" id="basket_id" value="'.$data['basket_id'].'">
     <input type="hidden" name="member_id" id="member_id" value="'.$data['member_id'].'">
     <input type="hidden" name="delivery_id" id="delivery_id" value="'.$data['delivery_id'].'">
    <table id="product_list_table">
       <tr>
         <td colspan="6" class="header_major">
           Customer Basket for '.date('F j, Y', strtotime($data['delivery_date'])).'
         </td>
       </tr>
       <tr>
         <td class="basket_header_minor">
           Adjust Quantity
         </td>'.
($_SESSION['member_id'] == CHECKOUT_MEMBER_ID ? '
         <td class="basket_header_minor">



        <div class="checkout" id="checkout'.$data['product_id'].'">'.
        ($data['site_id'] && $data['delivery_postal_code'] ?
          ($data['basket_checked_out'] != 0 ? '
            <img class="checkout_check" src="'.DIR_GRAPHICS.'checkout-ccs.png">
            <span class="checkout_text">Checked</span>'
            : '
            <input type="image" class="checkout_check" src="'.DIR_GRAPHICS.'checkout-g.png" onclick="AddToCart(0,0,\'checkout_basket\'); return false;">
            <span class="checkout_text">Checkout&nbsp;basket</span>')
          :
          'Please select a delivery site').'
        </div>



         </td>'
: '').'
         <td class="basket_header_minor">
           Product Description
         </td>
         <td class="basket_header_minor">
           Producer and<br>
           Product Info.
         </td>
         <td class="basket_header_minor">
           Pricing
         </td>
         <td class="basket_header_minor">
           Total Cost
         </td>
       </tr>';
  };


function close_list_bottom($data, &$unique)
  {
    $total_text = 'Total';
    $total_message = '
      <span class="total_message">Reload to view changes if quantities have been modified.</span>';
    if ($unique['weight_needed'] == true)
      {
        if (RANDOM_CALC == 'ZERO') $estimate_type = 'zero ';
        elseif (RANDOM_CALC == 'AVG') $estimate_type = 'average';
        elseif (RANDOM_CALC == 'MIN') $estimate_type = 'minimum';
        elseif (RANDOM_CALC == 'MAX') $estimate_type = 'maximum';
        $total_text = 'Estimated Total';
        $estimate_message .= '
          <span class="estimate_message">Estimated total (pricing based on '.$estimate_type.' estimated weight)</span>';
        $estimate_class = 'estimate';
      }
    $list_close = '
      <tr>
        <td colspan="4" class="basket_total">
          <span class="total_label '.$estimate_class.'">'.$total_text.'</span>
          '.$total_message.
          $estimate_message.
          '
        </td>
        <td class="total">
          <span id="total_cost" class="'.$estimate_class.'">'.$estimate_text.'$'.number_format($unique['running_total'], 2).'</span>
        </td>
      </tr>
    </table>';
    return $list_close;
  };

/************************** OPEN MAJOR DIVISION ****************************/

function major_division_open($data, $major_division = NULL)
  {
    switch ($major_division)
      {
        // Majory division on category
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
        // Majory division on producer
        case 'producer_id':
        case 'producer_name':
          $header = '
            <tr>
              <td colspan="6" class="header_major">
                '.$data['producer_name'].'
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
        // Majory division on category
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
        // Majory division on producer
        case 'producer_id':
        case 'producer_name':
          $header = '
            <tr>
              <td colspan="6" class="header_minor">
                <a href="'.PATH.'producers/'.$data['producer_link'].'">'.$data['producer_name'].'</a>
              </td>
            </tr>';
          break;
        // Majory division on producer
        case 'product_id':
        case 'product_name':
          $header = '
            <tr id="Y'.$row['product_id'].'">
              <td colspan="2" class="header_minor">
                '.$row['image_display'].'
              </td>
              <td colspan="4" class="header_minor">
              (#'.$row['product_id'].') '.$row['product_name'].'
              <br>'.$row['inventory_display'].$row['ordering_unit_display'].$row['random_weight_display'].'
              '.$row['product_description'].'
              '.($row['is_wholesale_item'] == true ? wholesale_text_html() : '').'
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
    return
    '
    <tr id="X'.$data['product_id'].'"'.($data['is_wholesale_item'] ? ' class="wholesale"' : '').'>
      '.$data['row_activity_link'].
($_SESSION['member_id'] == CHECKOUT_MEMBER_ID ? '
      <td>
        <div class="checkout" id="checkout'.$data['product_id'].'">'.
        ($data['site_id'] && $data['delivery_postal_code'] ?
          ($data['checked_out'] ? '
            <img class="checkout_check" src="'.DIR_GRAPHICS.'checkout-ccs.png">
            <span class="checkout_text">Ordered!</span>'
            : '
            <input type="image" class="checkout_check" src="'.DIR_GRAPHICS.'checkout-g.png" onclick="AddToCart('.$data['product_id'].','.$data['product_version'].',\'checkout\'); return false;">
            <span class="checkout_text">Checkout&nbsp;Item</span>')
          :
          'Please select a delivery site').'
        </div>
      </td>'
: '').'
      <td width="50%" align="left" valign="top" class="'.($data['availability'] == false || ($data['inventory_quantity'] == 0 && $data['inventory_id']) ? 'inactive' : '').'">
        '.$data['image_display'].'
        <strong>#'.$data['product_id'].' &ndash; '.$data['product_name'].'</strong><br>
        '.$data['inventory_display'].$data['ordering_unit_display'].$data['random_weight_display'].'
        <div id="Y'.$data['product_id'].'">
          '.$data['product_description'].'
          '.($data['is_wholesale_item'] ? wholesale_text_html() : '').'
        </div>
      <div id="message_area'.$data['product_id'].'"'.($data['basket_quantity'] == 0 ? ' style="display:none;"' : '').'>
        <textarea class="message" id="message'.$data['product_id'].'" name="message" placeholder="Optional message to producer...">'.
          $data['customer_message'].
        '</textarea>
        <div class="message_button" id="message_button'.$data['product_id'].'" onclick="AddToCart('.$data['product_id'].','.$data['product_version'].',\'message\'); return false;">'.
          ($data['checked_out'] ? '' : '
          <img alt="save message" src="'.DIR_GRAPHICS.'message.png">
          <div class="thumb_descr">Update<br>Message</div>' ). '
        </div>
      </td>
      <td class="business">
        '.$data['business_name_display'].'
        <hr>
        '.$data['prodtype_display'].'<br>
        <em>('.$data['storage_type'].')</em>
      </td>
      <td class="pricing">
        '.$data['pricing_display'].'
      </td>
      <td class="total">
        '.$data['total_display'].'
      </td>
    </tr>';
  };

?>