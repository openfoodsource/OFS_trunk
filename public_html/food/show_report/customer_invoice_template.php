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

// Make sure the paypal_utilities know this is not paypal calling and include the file
$not_from_paypal = true;
include_once (FILE_PATH.PATH.'paypal_utilities.php');

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
    '<font face="arial" color="#770000" size="-1"><b>'.$product[$this_row]['producer_name'].'</b></font>';
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
    $list_top = ($_GET['output'] == 'pdf' ? '' : '
      <span class="current_view">
        Current view: '.ucfirst ($unique['view']).' invoice<br>
        View as
          '.($unique['view'] != 'adjusted' ? '[<a href="'.$_SERVER['SCRIPT_NAME'].'?'.($_GET['type'] ? 'type='.$_GET['type'] : '').($_GET['delivery_id'] ? '&amp;delivery_id='.$_GET['delivery_id'] : '').($_GET['member_id'] ? '&amp;member_id='.$_GET['member_id'] : '').'&amp;view=adjusted">Adjusted</a>]': '').'
          '.($unique['view'] != 'original' ? '[<a href="'.$_SERVER['SCRIPT_NAME'].'?'.($_GET['type'] ? 'type='.$_GET['type'] : '').($_GET['delivery_id'] ? '&amp;delivery_id='.$_GET['delivery_id'] : '').($_GET['member_id'] ? '&amp;member_id='.$_GET['member_id'] : '').'&amp;view=original">Original</a>]': '').'
          '.(($unique['view'] != 'editable' && CurrentMember::auth_type('cashier') && $_GET['member_id'] != $member_id) ? '[<a href="'.$_SERVER['SCRIPT_NAME'].'?'.($_GET['type'] ? 'type='.$_GET['type'] : '').($_GET['delivery_id'] ? '&amp;delivery_id='.$_GET['delivery_id'] : '').($_GET['member_id'] ? '&amp;member_id='.$_GET['member_id'] : '').'&amp;view=editable">Editable</a>]': '').'
        invoice.
      </span>').'
          <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td align="left" valign="top"><!-- FOOTER LEFT "'.(strpos ($unique['auth_type'], 'institution') !== false ? $unique['business_name'] : '').$unique['last_name'].', '.$unique['first_name'].'" -->
                <font size="+2"><b>'.$unique['preferred_name'].' '.(strpos ($unique['auth_type'], 'institution') !== false ? $unique['business_name'].'<br>(attn: '.$unique['first_name'].' '.$unique['last_name'].')' : '').'</b></font>
              </td>
              <td valign="top" align="right">
                <table border="0" style="width:300px;float:right">
                  <tr>
                    <td align="center" style="text-align:center;">
                      <img src="'.BASE_URL.DIR_GRAPHICS.'invoice_logo.gif" alt="logo" width="250" height="72">
                    </td>
                  </tr>
                  <tr>
                    <td align="center" style="text-align:center;">
                      <font size="-2">'.SITE_CONTACT_INFO.'</font>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td align="left">
                      <font size="+2">'.$unique['member_id'].'-'.$unique['site_short'].' ('.$unique['site_long'].')</font>
                    </td>
                    <td align="right" style="text-align:right;">
                      <font size="+2">'.date ("F j, Y", strtotime ($unique['delivery_date'])).'</font>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td colspan="2" height="20"><img class="wide-line" src="'.BASE_URL.DIR_GRAPHICS.'black_pixel.gif" width="100%" height="1" alt="divider"></td>
            </tr>
            <tr>
              <td valign="top"><strong>Customer info</strong>'.
($unique['delivery_type'] == 'H' || $unique['delivery_type'] == 'P' ? '
                (home):<br><br>'.$unique['address_line1'].''.
($unique['address_line2'] != '' ? '
                <br>'.$unique['address_line2'].''
: '').'
                <br>'.$unique['city'].', '.$unique['state'].', '.$unique['zip'].'<br>' :
'').
($unique['delivery_type'] == 'W' ? '
                (work):<br><br>'.$unique['work_address_line1'].''.
($unique['work_address_line2'] != '' ? '
                <br>'.$unique['work_address_line2'].''
: '').'
                <br>'.$unique['work_city'].', '.$unique['work_state'].', '.$unique['work_zip'].'<br>'
: '').
($unique['email_address'] != '' ? '
                <br><a href="mailto:'.$unique['email_address'].'">'.$unique['email_address'].'</a>'
: '').
($unique['email_address_2'] != '' ? '
                <br><a href="mailto:'.$unique['email_address_2'].'">'.$unique['email_address_2'].'</a>'
: '').
($unique['home_phone'] != '' ? '
                <br>'.$unique['home_phone'] .' (home)'
: '').
($unique['work_phone'] != '' ? '
                <br>'.$unique['work_phone'] .' (work)'
: '').
($unique['mobile_phone'] != '' ? '
                <br>'.$unique['mobile_phone'] .' (mobile)'
: '').
($unique['fax'] != '' ? '
                <br>'.$unique['fax'] .' (fax)'
: '').'<br><br>
              </td>
              <td valign="top"><strong>Delivery/pickup details:</strong>
                <dl>
                  <dt><font face="Times New Roman">'.$unique['site_long'].'</font></dt>
                  <dd><pre><font face="Times New Roman">'.$unique['site_description'].'</font></pre></dd>
                </dl>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                '.
($unique['msg_all'] != '' ? '
                <font color="#990000" size="-1">'.$unique['msg_all'].'</font>'
: '').
($unique['msg_unique'] != '' ? '
                <br><font color="#990000" size="-1">'.$unique['msg_unique'].'<br></font>'
: '').'
              </td>
            </tr>
          </table>
        <font face="arial">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">'.
($unique['checked_out'] != 0 ? '
          <tr>
            <td colspan="7"><br></td>
          </tr>
          <tr>
            <th valign="bottom" bgcolor="#444444" width="40"></th>
            <th valign="bottom" bgcolor="#444444" width="35"><font color="#ffffff" size="-1">#</font></th>
            <th valign="bottom" bgcolor="#444444" align="left"><font color="#ffffff" size="-1">Product Name</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Price</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Quantity</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Weight</font></th>
            <th valign="bottom" bgcolor="#444444" align=right width="8%"><font color="#ffffff" size="-1">Amount</font></th>
          </tr>'
: '
          <tr>
            <td colspan="7" align="center"><br><br><br><br>EMPTY INVOICE<br>Nothing ordered<br><br><br></td>
          </tr>');
    return $list_top;
  };




// '<pre style="width:100%;height:100px;overflow:scroll;border:1px solid #000;">'.print_r($unique, true).'</pre>'.



function close_list_bottom(&$product, &$adjustment, &$unique)
  {
    $this_row = $product['this_row'];
    return
($product[$this_row]['adjustments_exist'] != '' ? '
          <tr align="left">
            <td></td>
            <td>____</td>
            <td colspan="5"><br><font face="arial" color="#770000" size="-1"><b>Adjustments</b></font></td>
          </tr>
          '.$product[$this_row]['adjustment_display_output']
: '').'
<!-- NEED 7 -->
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><br><b>SUBTOTAL</b></td>
            <td align="right" width="8%" style="text-align:right;"><br><b>$&nbsp;'.number_format($unique['total_order_amount'] - ($unique['invoice_price'] == 1 ? 0 : $unique['total_order_customer_fee']) - $unique['total_order_tax'], 2).'</b></td>
          </tr>'.
($product[$this_row]['delivery_id'] >= DELIVERY_NO_PAYPAL && $unique['invoice_price'] == 0 && $unique['customer_fee_percent'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>+ '.number_format($unique['customer_fee_percent'], 0).'% Fee</b></td>
            <td align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($unique['total_order_customer_fee'], 2).'</b></td>
          </tr>'
: '').
($unique['total_order_tax'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>* Sales tax</b></td>
            <td align="right" width="8%" style="text-align:right;"><b>$ '.number_format($unique['total_order_tax'], 2).'</b></td>
          </tr>'
: '').
($product[$this_row]['exempt_adjustment_cost'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>Non-taxed Adjustments</b></td>
            <td  align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($product[$this_row]['exempt_adjustment_cost'], 2).'</b></font></td>
          </tr>'
: '').
($adjustment['total_delivery_cost'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>Extra Charge for Delivery </b></font></td>
            <td  align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($adjustment['total_delivery_cost'], 2).'</b></td>
          </tr>'
: '').'
          <tr>
            <td height="1" colspan="6"></td>
            <td height="1"><img class="wide-line" width="90" height="1" src="'.BASE_URL.DIR_GRAPHICS.'black_pixel.gif"></td>
          </tr>
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>Invoice&nbsp;Total </b></td>
            <td align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($unique['total_order_amount'], 2).'</b></td>
          </tr>'.
($unique['balance_forward'] != 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><b>Previous '.($unique['balance_forward'] < 0 ? 'Credit' : 'Balance Due').'</b></td>
            <td align="right" width="8%" style="text-align:right;"><b>$&nbsp;'.number_format($unique['balance_forward'], 2).'</b></td>
          </tr>'
: '').
$unique['included_adjustments'].'
          <tr>
            <td colspan="5" height="1"></td>
            <td colspan="2" height="1" align="right"><img class="wide-line" width="90" height="1" src="'.BASE_URL.DIR_GRAPHICS.'black_pixel.gif"></td>
          </tr>
          <tr>
            <td colspan="6" align="right" style="text-align:right;"><font size="+2">PLEASE PAY:&nbsp;</font></td>
            <td align="right" style="text-align:right;"><font size="+2">'.($product[$this_row]['unfilled_random_weight'] ? '<font size="-1">'.$product[$this_row]['display_weight_pending_text'].'</font>' : '$&nbsp;'.number_format ($unique['total_order_amount'] + $unique['balance_forward'] + $unique['included_adjustment_total'], 2)).'</font></td>
          </tr>'.
// ADJUSTMENT DISPLAY
$unique['excluded_adjustments'].
(round ($product[$this_row]['most_recent_payment_amount'], 2) > 0 ? '
          <tr>
            <td colspan="6" align="right" style="text-align:right;">Thank you for your most recent payment of $&nbsp;'.number_format ($product[$this_row]['most_recent_payment_amount'], 2).'.</td>
            <td></td>
          </tr>'
: '').
($_GET['output'] != 'pdf' ? '
          <tr id="payment_options">
            <td colspan="7" align="right">
              <table width="60%" border="0" cellspacing="10" align="center" style="margin:2em auto; width:60%;">
                <tr>
                  <td colspan="2" valign="top" align="left" style="padding:5px;">
                    <font color="#880000">Please do not make payments until producers have had a chance to fill the orders, mark products out-of-stock if needed, and all weights are no longer zero.</font>
                  </td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" align="center" style="padding:5px;">
                    <b>P A Y M E N T &nbsp; &nbsp; O P T I O N S</b>
                  </td>
                </tr>
                <tr>
                  <td colspan="2" valign="top" style="padding:5px;height:21px;font-size:16px;">
                    Pay $&nbsp;'.number_format ($unique['total_order_amount'] + $unique['balance_forward'] + $unique['included_adjustment_total'], 2).' by cash or check at order pickup
                  </td>
                </tr>'.
// Only show PayPal if PayPal is enabled and if there is a real basket_id for this order
(PAYPAL_EMAIL && $unique['basket_id'] ? '
                <tr>
                  <td colspan="2" style="padding:15px 20px;">OR</td>
                </tr>
                <tr>
                  <td colspan="2" style="padding:5px;">'.
                  paypal_display_form (array (
                    'form_id' => 'paypal_form1',
                    'span1_content' => 'Pay now with PayPal &nbsp; &nbsp; $',
                    'span2_content' => '',
                    'form_target' => 'paypal',
                    'allow_editing' => true,
                    'amount' => number_format ($unique['total_order_amount'] + $unique['balance_forward'] + $unique['included_adjustment_total'], 2),
                    'business' => PAYPAL_EMAIL,
                    'item_name' => htmlentities (ORGANIZATION_ABBR.' '.$unique['member_id'].' '.$unique['preferred_name']),
                    'notify_url' => BASE_URL.PATH.'paypal_utilities.php',
                    'custom' => htmlentities ('basket#'.$unique['basket_id']),
                    'no_note' => '0',
                    'cn' => 'Message:',
                    'cpp_cart_border_color' => '#3f7300',
                    'cpp_logo_image' => BASE_URL.DIR_GRAPHICS.'logo1_for_paypal.png',
                    'return' => BASE_URL.PATH.'panel_member.php',
                    'cancel_return' => BASE_URL.PATH.'panel_member.php',
                    'rm' => '2',
                    'cbt' => 'Return to '.SITE_NAME,
                    'paypal_button_src' => 'https://www.paypal.com/en_US/i/btn/btn_buynow_SM.gif'
                    )).'
                    <div style="clear:both;font-size:80%;margin-top:1em;">If paying with PayPal, be sure to print/bring your PayPal receipt with you to order pickup as proof of payment.</div>
                  </td>
                </tr>'
: '&nbsp;')
: '').'
              </table>
        </font>';
  };

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
        // Majory division on producer
        case 'producer_id':
          $header = '
          <tr>
            <td colspan="2" width="75"><!-- <img class="wide-line" src="'.BASE_URL.DIR_GRAPHICS.'black_pixel.gif" width="70" height="1" alt="divider"> --></td>
            <td colspan="5">'.$product[$this_row]['business_name_display'].'</td>
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
  { return
    '
          <tr align="left">
            <td colspan="7" height="10"></td>
          </tr>';
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
    // Check if this is an adjusted quantity
    if ($product[$this_row]['adjustment_group_memo'] != "")
      {
        $adjustment_class = ' adjusted';
        // Use an associative key...[$product[$this_row]['adjustment_group_memo']]...to prevent repeating memos
        $unique['adjustment_markup'][$product[$this_row]['adjustment_group_memo']] = '
          <tr align="center" class="'.$adjustment_class.'">
            <td colspan="2"></td>
            <td colspan="5" align="left" class="adjustment">Adjustment: '.$product[$this_row]['adjustment_group_memo'].'</td>
          </tr>';
        }
    // Aggregate the total order cost over the whole order
    $unique['total_order_amount'] += $product[$this_row]['amount'];
    if ($unique['invoice_price'] == 1)
      $product['total_product_amount'] += $product[$this_row]['amount'];
    // Only aggregate direct product costs
    elseif ($product[$this_row]['text_key'] != 'customer fee')
      $product['total_product_amount'] += $product[$this_row]['amount'];

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
    if ($product[$this_row + 1]['product_id'] != $product[$this_row]['product_id'] ||
        $product['this_row'] == $product['number_of_rows'])
      {
        $tax_display = ($product[$this_row]['taxable'] == 1 ? '* ' : '');
        $display_line = '
          <tr align="center" class="'.$adjustment_class.'">
            <td width="40" align="right" valign="top">'.($unique['view'] == 'editable' ? '<img src="'.DIR_GRAPHICS.'edit_icon.png" onclick="popup_src(\'adjust_ledger.php?type=product&amp;target='.$product[$this_row]['bpid'].'\', \'edit_transaction\');">' : '').'</td>
            <td width="50" align="right" valign="top">'.$product[$this_row]['product_id'].'&nbsp;&nbsp;</td>

            <td align="left" valign="top">'.$product[$this_row]['product_name'].
              ($product[$this_row]['customer_message'] != '' ? '<br />
                <font color="#6666aa" face="arial" size="-1"><b>Customer Note: </b>'.$product[$this_row]['customer_message'].'</font>'
                : '').'</td>

            <td align="center" valign="top">'.$product[$this_row]['pricing_display'].'</td>

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

            <td width="13" align="right" valign="top" style="text-align:right;"><b>'.$tax_display.'$'.number_format($product['total_product_amount'] - $product['total_product_tax'], 2).'</b></td>
          </tr>'.
          // Show adjustment comment (kept in an associative array keyed by the adjustment text -- to prevent duplication of message)
          (count ($unique['adjustment_markup']) > 0 ? implode ('', $unique['adjustment_markup']) : '');
        // Set product aggregations to zero
        unset ($unique['adjustment_markup']);
        $product['total_product_amount'] = 0;
        $product['total_product_tax'] = 0;
      }
    return $display_line;
  };

// /************************* LISTING FOR PRODUCT SORTS **************************/
// 
// function show_adjustment_row(&$adjustment, &$unique)
//   {
//     $this_row = $adjustment['this_row'];
//     $display_line = '';
//     $show_adjustment = true;
// 
//     // Do not show the delivery cost. It will be included in the invoice total
//     if ($adjustment[$this_row]['text_key'] == 'delivery cost')
//       {
//         $adjustment['total_delivery_cost'] += $adjustment[$this_row]['amount'];
//         $show_adjustment = false;
//       }
// 
// // //adjustment_display_section
// //     '
// //               <tr align="center">
// //                 <td></td>
// //                 <td align="right" valign="top"><b> </b>&nbsp;&nbsp;</td>
// //                 <td width="275" align="left" valign="top" colspan="'.($data['transaction_taxed'] ? '4' : '3').'"><b>'.$data['transaction_name'].$data['taxable_product'].'</b><br>'.$data['transaction_comments'].'</td>
// //                 <td align="right" valign="top">$'.number_format($data['transaction_amount'], 2).'</td>
// //                 '.($data['transaction_taxed'] ? '' : '<td>&nbsp;</td>').'
// //               </tr>'
// // EOT;
// 
//     // Show every adjustment row unless specified otherwise
//     if ($show_adjustment == true)
//       {
//         $unique['adjustment_total'] += $adjustment[$this_row]['amount'];
//         $display_line = '
//           <tr align="center">
//             <td colspan="2" align="left">'.$adjustment[$this_row]['ledger_message'].'</td>
//             <td colspan="2" align="left">'.$adjustment[$this_row]['text_key'].'</td>
//             <td colspan="2">&nbsp;</td>
//             <td align="center" valign="top">'.$adjustment[$this_row]['amount'].'</td>
//           </tr>';
//         $adjustment['total_listed_adjustments'] += $adjustment[$this_row]['amount'];
//       }
//     return $display_line;
//   };
// 
/************************* LISTING FOR PRODUCT SORTS **************************/

function show_adjustment_row(&$adjustment, &$unique)
  {
    $this_row = $adjustment['this_row'];
    $display_line = '';

    // If the adjustment is associated with this order, then it goes *BELOW* everything else and is not counted
    // in the totals.
    if (($adjustment[$this_row]['text_key'] == 'payment received' || $adjustment[$this_row]['text_key'] == 'payment made') && $adjustment[$this_row]['delivery_id'] == $unique['delivery_id'])
      {
        // Do not show for "original" invoices
        if ($unique['view'] != 'original')
          {
            $unique['excluded_adjustment_total'] += $adjustment[$this_row]['amount'] * $adjustment[$this_row]['multiplier'];
            $unique['excluded_adjustments'] .= '
              <tr>
                <td colspan="1">&nbsp;</td>
                <td colspan="5" align="right" style="text-align:right;">'.
                  ($unique['view'] == 'editable' ? '
                  <img src="'.DIR_GRAPHICS.'edit_icon.png" onclick="popup_src(\'adjust_ledger.php?type=single&target='.$adjustment[$this_row]['transaction_id'].'\', \'edit_transaction\');">' : '').'
                  '.ucfirst ($adjustment[$this_row]['text_key']).'
                  ('.date ('M d, Y', strtotime ($adjustment[$this_row]['effective_datetime'])).')'.
                  (strlen ($adjustment[$this_row]['ledger_message']) > 0 ? '<span class="adjustment"><br>'.$adjustment[$this_row]['ledger_message'].'</span>' : '').'
                </td>
                <td align="right" valign="top" style="text-align:right;">
                  $&nbsp;'.number_format ($adjustment[$this_row]['amount'] * $adjustment[$this_row]['multiplier'], 2).'
                </td>
              </tr>';
          }
      }
    // Otherwise it is counted into the total line.
    else
      {
        $unique['included_adjustment_total'] += $adjustment[$this_row]['amount'] * $adjustment[$this_row]['multiplier'];
        $unique['included_adjustments'] .= '
          <tr>
            <td colspan="1">&nbsp;</td>
            <td colspan="5" align="right" style="text-align:right;">'.
              ($unique['view'] == 'editable' ? '
              <img src="'.DIR_GRAPHICS.'edit_icon.png" onclick="popup_src(\'adjust_ledger.php?type=single&amp;target='.$adjustment[$this_row]['transaction_id'].'\', \'edit_transaction\');">' : '').'
              '.ucfirst ($adjustment[$this_row]['text_key']).'
              ('.date ('M d, Y', strtotime ($adjustment[$this_row]['effective_datetime'])).')'.
              (strlen ($adjustment[$this_row]['ledger_message']) > 0 ? '<span class="adjustment"><br>'.$adjustment[$this_row]['ledger_message'].'</span>' : '').'
            </td>
            <td align="right" valign="top" style="text-align:right;">
              $&nbsp;'.number_format ($adjustment[$this_row]['amount'] * $adjustment[$this_row]['multiplier'], 2).'
            </td>
          </tr>';
        
      }


  };

?>