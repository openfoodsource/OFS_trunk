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

function wholesale_text_html(&$product, &$unique)
  { return
    '<br><br><center style="color:#f00;letter-spacing:5px;">** FEATURED WHOLESALE ITEM **</center>';
  };

function no_product_message(&$product, &$unique)
  { return
    '<td align="center" colspan="7">
      <br>
      <br>
      <br>
      <br>
      EMPTY INVOICE
      <br>
      Nothing ordered
      <br>
      <br>
      <br>
    </td>';
  };

// RANDOM_WEIGHT_DISPLAY_CALC
function random_weight_display_calc(&$product, &$unique)
  { 
    $this_row = $product['this_row'];
    return
    ($product[$this_row]['random_weight'] == 1 ?
    'You will be billed for exact '.$row['meat_weight_type'].' weight ('.
    ($product[$this_row]['minimum_weight'] == $product[$this_row]['maximum_weight'] ?
    $product[$this_row]['minimum_weight'].' '.Inflect::pluralize_if ($product[$this_row]['minimum_weight'], $product[$this_row]['pricing_unit'])
    :
    'between '.$product[$this_row]['minimum_weight'].' and '.$product[$this_row]['maximum_weight'].' '.Inflect::pluralize ($product[$this_row]['pricing_unit'])).')'
    :
    '');
  };

// TOTAL_DISPLAY_CALC
function total_display_calc(&$product, &$unique)
  {
    $this_row = $product['this_row'];
    // Random weight w/o weight gets a special note
    if ($product[$this_row]['random_weight'] == 1 && $product[$this_row]['total_weight'] == 0) $total_display = '
      [pending]';
    elseif ($product[$this_row]['unit_price']  != 0) $total_display .= '
      <span id="customer_adjusted_cost'.$product[$this_row]['bpid'].'">$&nbsp;'.number_format($product[$this_row]['customer_display_cost'], 2).'</span>';
    // If there is content so far, then add a newline
    if ($total_display && $product[$this_row]['extra_charge'] != 0) $total_display .= '
      <br>';
    if ($product[$this_row]['extra_charge'] != 0) $total_display .= '
      <span id="extra_charge'.$product[$this_row]['bpid'].'">'.($product[$this_row]['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format($product[$this_row]['basket_quantity'] * abs($product[$this_row]['extra_charge']), 2).'</span>';
    // Now clobber everything if this is out-of-stock
    if ($product[$this_row]['out_of_stock'] == $product[$this_row]['basket_quantity']) $total_display = '
      <span id="customer_adjusted_cost'.$product[$this_row]['bpid'].'">$&nbsp;0.00</span>';
    return $total_display;
  };

// PRICING_DISPLAY_CALC
function pricing_display_calc(&$product, &$unique)
  { 
    $this_row = $product['this_row'];
    $markup = 0;
    if (PAYS_PRODUCER_FEE == 'customer') $markup += $product[$this_row]['producer_fee_percent'];
    if (PAYS_CUSTOMER_FEE == 'customer') $markup += $unique['customer_fee_percent'];
    if (PAYS_SUBCATEGORY_FEE == 'customer') $markup += $product[$this_row]['subcategory_fee_percent'];
    if (PAYS_PRODUCT_FEE == 'customer') $markup += $product[$this_row]['product_fee_percent'];
    if ($unique['invoice_price'] == 1) $unit_price = $product[$this_row]['unit_price'] * (1 + ($markup / 100));
    elseif ($unique['invoice_price'] == 0) $unit_price = $product[$this_row]['unit_price'];

    return
    '<span class="unit_price">$'.number_format($unit_price, 2).'/'.Inflect::singularize ($product[$this_row]['pricing_unit']).'</span><br>'.
    ($product[$this_row]['extra_charge'] != 0 ?
    '<span class="extra">'.($product[$this_row]['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format (abs ($product[$this_row]['extra_charge']), 2).'/'.Inflect::singularize ($product[$this_row]['ordering_unit']).'</span><br>'
    : '');
  };

// INVENTORY_DISPLAY_CALC
function inventory_display_calc(&$product, &$unique)
  { 
    $this_row = $product['this_row'];
    // Use this for the out-of-stock checkmark
    if ($product[$this_row]['out_of_stock'] == 0)
      $out_checkmark = ''; // Fully in stock
    elseif ($product[$this_row]['out_of_stock'] == $product[$this_row]['basket_quantity'])
      $out_checkmark = '<img alt="out of stock" src="'.DIR_GRAPHICS.'out.png" width="30">'; // Fully out of stock
    else
      $out_checkmark = '<img alt="out of stock" src="'.DIR_GRAPHICS.'part.png" width="30">'; // Partly filled
    return $out_checkmark;
  };

// BUSINESS_NAME_DISPLAY_CALC
function business_name_display_calc(&$product, &$unique)
  {
    $this_row = $product['this_row'];
    return
    '<font color="#770000"><b>'.$product[$this_row]['producer_name'].'</b>';
  };

// PAGER_DISPLAY_CALC
function pager_display_calc(&$product, &$unique)
  { return
    '<a href="'.$_SERVER['SCRIPT_NAME'].'?'.
    ($_GET['type'] ? 'type='.$_GET['type'] : '').
    ($_GET['producer_id'] ? '&amp;producer_id='.$_GET['producer_id'] : '').
    ($_GET['category_id'] ? '&amp;category_id='.$_GET['category_id'] : '').
    ($_GET['delivery_id'] ? '&amp;delivery_id='.$_GET['delivery_id'] : '').
    ($_GET['subcat_id'] ? '&amp;subcat_id='.$_GET['subcat_id'] : '').
    ($_GET['query'] ? '&amp;query='.$_GET['query'] : '').
    ($_GET['a'] ? '&amp;a='.$_GET['a'] : '').
    ($data['page'] ? '&amp;page='.$data['page'] : '').
    '" class="'.($data['this_page_true'] ? 'current' : '').($data['page'] == 1 ? ' first' : '').($data['page'] == $data['last_page'] ? ' last' : '').'">&nbsp;'.$data['page'].'&nbsp;</a>';
  };

/************************* PAGER NAVIGATION SECTION ***************************/

function pager_navigation(&$product, &$unique)
  { return
    ($data['last_page'] > 1 ?
    '<div class="pager"><span class="pager_title">Page: </span>'.$data['display'].'</div>
    <div class="clear"></div>'
    : '');
  };

/*********************** OPEN BEGINNING OF PRODUCT LIST *************************/

function open_list_top(&$product, &$unique)
  {
    $display_list_top = ($_GET['output'] == 'pdf' ? '' : '
      <span class="current_view">
        Current view: '.ucfirst ($unique['view']).' invoice<br>
        View as
          '.($unique['view'] != 'adjusted' ? '[<a href="'.$_SERVER['SCRIPT_NAME'].'?'.($_GET['type'] ? 'type='.$_GET['type'] : '').($_GET['delivery_id'] ? '&amp;delivery_id='.$_GET['delivery_id'] : '').($_GET['producer_id'] ? '&amp;producer_id='.$_GET['producer_id'] : '').'&amp;view=adjusted">Adjusted</a>]': '').'
          '.($unique['view'] != 'original' ? '[<a href="'.$_SERVER['SCRIPT_NAME'].'?'.($_GET['type'] ? 'type='.$_GET['type'] : '').($_GET['delivery_id'] ? '&amp;delivery_id='.$_GET['delivery_id'] : '').($_GET['producer_id'] ? '&amp;producer_id='.$_GET['producer_id'] : '').'&amp;view=original">Original</a>]': '').'
          '.(($unique['view'] != 'editable' && CurrentMember::auth_type('cashier') && $_GET['producer_id'] != $_SESSION['producer_id_you']) ? '[<a href="'.$_SERVER['SCRIPT_NAME'].'?'.($_GET['type'] ? 'type='.$_GET['type'] : '').($_GET['delivery_id'] ? '&amp;delivery_id='.$_GET['delivery_id'] : '').($_GET['producer_id'] ? '&amp;producer_id='.$_GET['producer_id'] : '').'&amp;view=editable">Editable</a>]': '').'
        invoice.
      </span>').'
          <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td align="left" valign="top" width="50%"><!-- FOOTER LEFT "'.$unique['business_name'].'" -->
                <font size="+2"><b>'.$unique['business_name'].'</b></font>
              </td>
              <td valign="top" align="right" rowspan="2" style="text-align:right;" width="50%">
                <img src="'.BASE_URL.DIR_GRAPHICS.'invoice_logo.gif" alt="logo" width="250" height="71">
              </td>
            </tr>
            <tr>
              <td align="left">
                <br>
                <table cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td valign="top"><strong>Home:</strong><br>'.$unique['address_line1'].
($unique['address_line2'] != '' ? '
                      <br>'.$unique['address_line2'].''
: '').'
                      <br>'.implode (', ', array_filter (array($unique['city'], $unique['state'], $unique['zip']))).'<br>'.
($unique['home_phone'] != '' ? '
                      <br>'.$unique['home_phone']
: '').'
                    </td>
                    <td width="8" style="width:8px;">
                    </td>
                    <td width="1" bgcolor="#888888" style="width:1px;">
                    </td>
                    <td width="8" style="width:8px;">
                    </td>
                    <td valign="top"><strong>Business:</strong><br>'.$unique['work_address_line1'].
($unique['work_address_line2'] != '' ? '
                      <br>'.$unique['address_line2'].''
: '').'
                      <br>'.implode (', ', array_filter (array($unique['work_city'], $unique['work_state'], $unique['work_zip']))).'<br>'.
($unique['work_phone'] != '' ? '
                      <br>'.$unique['work_phone']
: '').'
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td valign="top">'.
($unique['email_address'] != '' ? '
                <br><a href="mailto:'.$unique['email_address'].'">'.$unique['email_address'].'</a>'
: '').
($unique['email_address_2'] != '' ? '
                <br><a href="mailto:'.$unique['email_address_2'].'">'.$unique['email_address_2'].'</a>'
: '').
($unique['mobile_phone'] != '' ? '
                <br>'.$unique['mobile_phone'] .' (mobile)'
: '').
($unique['fax'] != '' ? '
                <br>'.$unique['fax'] .' (fax)'
: '').'
              </td>
              <td valign="bottom" align="right" style="vertical-align:bottom;text-align:right">
                <font size="+2">'.date ("F j, Y", strtotime ($unique['delivery_date'])).'</font>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                '.
($unique['msg_all'] != '' ? '
                <font color="#990000" size="-1">'.$unique['msg_all'].'  E-mail any problems with your order to <a href="mailto:'.PROBLEMS_EMAIL.'">'.PROBLEMS_EMAIL.'</a><br>'
: '').
($unique['msg_unique'] != '' ? '
                <br><font color="#990000" size="-1">'.$unique['msg_unique'].'<br>'
: '').'
              </td>
            </tr>
            <tr>
              <td colspan="2" height="20" align="center"><img class="wide-line" src="'.BASE_URL.DIR_GRAPHICS.'black_pixel.gif" width="750" height="1" alt="divider"></td>
            </tr>
            <tr>
              <td colspan="2" align="right" padding="0"></td>
            </tr>
          </table>
        <table cellpadding="0" cellspacing="0" border="0" style="width:100%;" width="750">
          <tr>
            <td colspan="7"><br></td>
          </tr>
          <tr>
            <th valign="bottom" bgcolor="#444444" width="40"></th>
            <th valign="bottom" bgcolor="#444444" width="35"><font color="#ffffff" size="-1">#</font></th>
            <th valign="bottom" bgcolor="#444444" align="left"><font color="#ffffff" size="-1">Product Name</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Shipped</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Weight</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Order</font></th>
            <th valign="bottom" bgcolor="#444444" align=right width="8%" style="text-align:right;"><font color="#ffffff" size="-1">Total</font></th>
          </tr>';
    return $display_list_top;
  };



// '<pre style="width:100%;height:100px;overflow:scroll;border:1px solid #000;">'.print_r($unique, true).'</pre>'.



function close_list_bottom(&$product, &$adjustment, &$unique)
  {
    $this_row = $product['this_row'];
    $display_list_bottom = '';
    if ($unique['product_count'] == 0)
      {
        $display_list_bottom = '
          <tr>
            <td colspan="7" align="center"><br><br><br><br>EMPTY INVOICE<br>Nothing ordered<br><br><br></td>
          </tr>
        </table>';
      }
    else
      {
        $display_list_bottom = 
($product[$this_row]['adjustments_exist'] != '' ? '
          <tr align="left">
            <td></td>
            <td>____</td>
            <td colspan="5"><br><font face="arial" color="#770000" size="-1"><b>Adjustments</b></td>
          </tr>
          '.$product[$this_row]['adjustment_display_output']
: '').'
<!-- NEED 7 -->
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><br><b>SUBTOTAL</b></td>
            <td align="right" width="8%" style="text-align:right;"><br><b>$&nbsp;'.number_format($unique['total_order_amount'], 2).'</b></td>
          </tr>'.
($product[$this_row]['delivery_id'] >= DELIVERY_NO_PAYPAL && $unique['invoice_price'] == 0 && $unique['producer_fee_percent'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>- '.number_format($unique['producer_fee_percent'], 0).'% Fee</b></td>
            <td align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($unique['total_order_fee'], 2).'</b></td>
          </tr>'
: '').
($product[$this_row]['exempt_adjustment_cost'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>Non-taxed Adjustments</b></td>
            <td  align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($product[$this_row]['exempt_adjustment_cost'], 2).'</b></td>
          </tr>'
: '').'
          <tr>
            <td colspan="6" height="1"></td>
            <td height="1"><img class="wide-line" src="'.BASE_URL.DIR_GRAPHICS.'black_pixel.gif" width="90" height="1" alt="divider"></td>
          </tr>
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>Invoice&nbsp;Total </b></td>
            <td align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($unique['total_order_amount'] - $unique['total_order_fee'], 2).'</b></td>
          </tr>'.
($unique['balance_forward'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>Previous '.($unique['balance_forward'] > 0 ? 'Credit' : 'Balance Due').'</b></td>
            <td align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($unique['balance_forward'], 2).'</b></td>
          </tr>'
: '').
(round($product[$this_row]['membership_cost'], 2) != 0 || $product[$this_row]['order_cost'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>Membership/Fees</b></td>
            <td  align="right" width="8%" style="text-align:right;"><b>$ '.number_format($product[$this_row]['membership_cost'] + $product[$this_row]['order_cost'], 2).'</b></td>
          </tr>'
: '').
($product[$this_row]['previous_balance'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>(Our records show a previous '.($product[$this_row]['previous_balance'] < 0 ? 'Credit' : 'Balance Due').'</b></td>
            <td align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($product[$this_row]['previous_balance'], 2).')</b></td>
          </tr>'
: '').'
          <tr>
            <td colspan="6" height="1"></td>
            <td height="1"><img class="wide-line" src="'.BASE_URL.DIR_GRAPHICS.'black_pixel.gif" width="90" height="1" alt="divider"></td>
          </tr>
          <tr>
            <td colspan="5" align="right" style="text-align:right;"><font size="+2"> NET:</font></td>
            <td colspan="2" align="right" style="text-align:right;"><font size="+2">$&nbsp;'.number_format ($unique['total_order_amount'] - $unique['total_order_fee'] + $unique['balance_forward'], 2).'</font></td>
          </tr>
        </table>';
      }
    return $display_list_bottom;
  };






// 
// /************************** MEMBERSHIP DISPLAY SECTION ************************/
// 
// $membership_display_section = <<<EOT
// '
//               <tr align="center">
//                 <td align="left" valign="top">'.$data['transaction_timestamp'].'</td>
//                 <td align="left" valign="top"><strong>'.$data['transaction_name'].'</strong><br>'.$data['transaction_comments'].'</td>
//                 <td align="right" valign="top">$'.number_format($data['transaction_amount'], 2).'</td>
//                 <td align="right" valign="top">$'.number_format($data['membership_cost'], 2).'</td>
//               </tr>'
// EOT;





/************************** OPEN MAJOR DIVISION ****************************/

// For invoices, the major division is a change in the producer
function major_product_open(&$product, &$unique)
  {
    $this_row = $product['this_row'];
    switch ($unique['major_product'])
      {
        // Majory division on category
        case 'category_id':
        case 'category_name':
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '';
          break;
        // Majory division on product
        case 'product_id':
          $header = '
          <tr>
            <td colspan="2" width="75"></td>
            <td colspan="5"><font face="arial" color="#770000" size="-1"><b>'.$product[$this_row]['product_name'].'</b> &ndash; '.$product[$this_row]['pricing_display'].'</font></td>
          </tr>';
        case 'producer_name':
          break;
        // Otherwise...
          $header = '
          ';
          break;
      }
    return $header;
  };

function major_product_close (&$product, &$unique)
  {
    $display_line = '
          <tr align="left">
            <td colspan="4" height="10" valign="bottom" style="vertical-align:bottom;"></td>
            <td></td>
            <td align="right" style="text-align:right;">Subtotal</td>
            <td align="right" style="text-align:right;"><b>$'.number_format($unique['total_product_amount'], 2).'</b></td>
          </tr>';
    // Reset the product totals
    $unique['total_product_amount'] = 0;
    $unique['total_product_fee'] = 0;
    return $display_line;
  };

/************************** OPEN MINOR DIVISION ****************************/

function minor_product_open(&$product, &$unique)
  {
    // The main thing to do is reset the product information when the product changes
    return '';
  };

function minor_product_close (&$product, &$unique)
  {
    return '';
  };

/************************* LISTING FOR PRODUCT SORTS **************************/

function show_product_row(&$product, &$unique)
  {
    $this_row = $product['this_row'];
    $display_line = '';

    // Capture producer product costs
    if ($product[$this_row]['text_key'] == 'quantity cost' ||
        $product[$this_row]['text_key'] == 'weight cost' ||
        $product[$this_row]['text_key'] == 'extra charge')
      {
        $unique['total_bpid_amount'] += $product[$this_row]['amount'];
        $unique['total_product_amount'] += $product[$this_row]['amount'];
        $unique['total_order_amount'] += $product[$this_row]['amount'];
      }
    else // Capture all other producer costs (fees)
      {
        $unique['total_bpid_fee'] += $product[$this_row]['amount'];
        $unique['total_product_fee'] += $product[$this_row]['amount'];
        $unique['total_order_fee'] += $product[$this_row]['amount'];
      }




    // Aggregate customer fee over whole order
    if ($product[$this_row]['text_key'] == 'customer fee')
      $unique['total_order_customer_fee'] += $product[$this_row]['amount'];

    // Aggregate tax over whole order
    if (! strpos ($product[$this_row]['text_key'], 'tax') === false)
      {
        $unique['total_order_tax'] += $product[$this_row]['amount'];
        $product['total_product_tax'] += $product[$this_row]['amount'];
      }

    // If the product will be different on the next go-around
    // or if this is the last row, then show product details
    if ($product[$this_row + 1]['bpid'] != $product[$this_row]['bpid'] ||
        $product['this_row'] == $product['number_of_rows'])
      {




        $display_line = '
          <tr align="center">
            <td width="40" align="right" valign="top" style="text-align:right;">'.($unique['view'] == 'editable' ? '<img src="'.DIR_GRAPHICS.'edit_icon.png" onclick="popup_src(\'adjust_ledger.php?type=product&amp;target='.$product[$this_row]['bpid'].'\');">' : '').'</td>
            <td width="50" align="right" valign="top" style="text-align:right;">'.$product[$this_row]['member_id'].'&nbsp;&nbsp;</td>
            <td align="left" valign="top">'.$product[$this_row]['preferred_name'].'</td>

            <td align="center" valign="top">'.
              ($product[$this_row]['out_of_stock'] != 0 ?
                $product[$this_row]['basket_quantity'] - $product[$this_row]['out_of_stock'].' of '
                : '').
              $product[$this_row]['basket_quantity'].' '.Inflect::pluralize_if ($product[$this_row]['basket_quantity'], $product[$this_row]['ordering_unit']).'</td>

            <td align="center" valign="top">'.
              ($product[$this_row]['random_weight'] ?
                ($product[$this_row]['total_weight'] ?
                  $product[$this_row]['total_weight'].' '.Inflect::pluralize_if ($product[$this_row]['total_weight'], $product[$this_row]['pricing_unit'])
                  : '(wt.&nbsp;pending)')
                : '').'</td>

            <td width="13" align="right" valign="top" style="text-align:right;">'.number_format($unique['total_bpid_amount'], 2).'</td>
            <td align="center" valign="top"></td>
          </tr>';
        // Reset totals for this row (bpid)
        $unique['total_bpid_amount'] = 0;
        $unique['total_bpid_fee'] = 0;
        $unique['product_count'] ++;
      }
    return $display_line;
  };

/************************* LISTING FOR PRODUCT SORTS **************************/

function show_adjustment_row(&$adjustment, &$unique)
  {
    $this_row = $adjustment['this_row'];
    $display_line = '';
    $show_adjustment = true;

    // Do not show the delivery cost. It will be included in the invoice total
    if ($adjustment[$this_row]['text_key'] == 'delivery cost')
      {
        $adjustment['total_delivery_cost'] += $adjustment[$this_row]['amount'];
        $show_adjustment = false;
      }

// //adjustment_display_section
//     '
//               <tr align="center">
//                 <td></td>
//                 <td align="right" valign="top"><b> </b>&nbsp;&nbsp;</td>
//                 <td width="275" align="left" valign="top" colspan="'.($data['transaction_taxed'] ? '4' : '3').'"><b>'.$data['transaction_name'].$data['taxable_product'].'</b><br>'.$data['transaction_comments'].'</td>
//                 <td align="right" valign="top">$'.number_format($data['transaction_amount'], 2).'</td>
//                 '.($data['transaction_taxed'] ? '' : '<td>&nbsp;</td>').'
//               </tr>'
// EOT;

    // Show every adjustment row unless specified otherwise
    if ($show_adjustment == true)
      {
        $display_line = '
          <tr align="center">
            <td colspan="4" align="left">'.$adjustment[$this_row]['ledger_message'].'</td>
            <td align="center" valign="top">'.$adjustment[$this_row]['amount'].'</td>
            <td colspan="2">&nbsp;</td>
          </tr>';
        $adjustment['total_listed_adjustments'] += $adjustment[$this_row]['amount'];
      }
    return $display_line;
  };

?>