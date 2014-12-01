<?php
include_once 'config_openfood.php';
include_once ('func.get_product.php');

// Initialize variables
$ranwt_bg = ' class="random_wt_row"'; // Random-weight fields
$norm_bg  = ' class="normal_row"'; // Normal product fields
$admin_bg = ' class="admin_row"'; // Admin fields
$cntrl_bg = ' class="control_row"'; // Admin fields

// This function is used to highlight validation problems and link to the help page
function format_help_link ($target)
  {
    global $warn;
    $requires_confirm = false;
    if (in_array ($target, explode (',', FIELDS_REQ_CONFIRM)))
      {
        $req_confirmation = true;
      }
    return ($req_confirmation ? '*&nbsp;' : '').
      '<a class="help_link'.$warn[$target].'" href="help.php#'.$target.'" onclick="popup=window.open(\'help.php#'.$target.'\', \'popupPage\', \'height=300,width=400,left=400,scrollbars=yes,resizeable=no\'); return false" target="help_window">';
  }

// Get the category/subcategory list and create a drop-down list
$sqlsc = '
  SELECT
    *
  FROM
    '.TABLE_SUBCATEGORY.',
    '.TABLE_CATEGORY.'
  WHERE
    '.TABLE_SUBCATEGORY.'.category_id = '.TABLE_CATEGORY.'.category_id
  ORDER BY
    category_name ASC,
    subcategory_name ASC';
$rs = @mysql_query($sqlsc, $connection) or die(debug_print ("ERROR: 906537 ", array ($sqlsc,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$display_subcat = '
  <option value="">Select Subcategory</option>';
while ( $row = mysql_fetch_array($rs) )
  {
    $option_select = '';
    // Set the option that currently-set option
    if ( $row['subcategory_id'] == $product_info['subcategory_id'] )
      {
        $option_select = ' selected';
      }
    // Check for category changes and do a new optgroup
    if ( $row['category_name'] != $prior_category_name )
      {
        // For all but the first group, close the prior optgroup
        if ( $prior_category_name != '' )
          {
            $display_subcat .= '
              </optgroup>';
          }
        $display_subcat .= '
          <optgroup label="'.$row['category_name'].'">';
      }
    $display_subcat .= '
      <option value="'.$row['subcategory_id'].'"'.$option_select.'>'.$row['subcategory_name'].'</option>';
    $prior_category_name = $row['category_name'];
  }
$display_subcat .= '
  </optgroup>';
// Get this producer's list of inventory options and create a drop-down list
$query = '
  SELECT
    *
  FROM
    '.TABLE_INVENTORY.'
  WHERE
    producer_id = "'.mysql_real_escape_string ($producer_id).'"
  ORDER BY
    description';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 649509 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$inventory_array = array();
$inventory_array[0]['description'] = 'No inventory selected';
$inventory_array[0]['quantity'] = 0;

$inventory_select = '
    <select name="inventory_id" onChange="if (this.value==0) document.getElementById(\'inventory_pull\').style.display=\'none\'; else document.getElementById(\'inventory_pull\').style.display=\'\';">
      <optgroup label="SELECT A FUNCTION:">
        <option value="0"'.($_POST['inventory_id'] == 0 ? ' selected' : '').'>DO NOT USE ANY INVENTORY</option>
        <option value="-1"'.($_POST['inventory_id'] == -1 ? ' selected' : '').'>CREATE A NEW INVENTORY UNIT</option>
      </optgroup>
      <optgroup label="OR USE EXISTING INVENTORY UNIT:">';

while ( $row = mysql_fetch_object($result) )
  {
    $selected = ($product_info['inventory_id'] == $row->inventory_id ? ' selected' : '');
    $inventory_select .= '
        <option value="'.$row->inventory_id.'"'.$selected.'>'.$row->description.'</option>';
  }
$inventory_select .= '
      </optgroup>
    </select>';
// Get the list of local accounts from the chart of accounts and create a drop-down list
if (CurrentMember::auth_type('producer_admin,site_admin,cashier'))
  {
    $query = '
      SELECT
        account_id,
        account_number,
        description
      FROM
        '.NEW_TABLE_ACCOUNTS.'
      WHERE
        1
      ORDER BY
        description';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 099564 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $account_number_select = '
      <select name="account_number">
        <option value=""'.($product_info['account_number'] == "" ? ' selected' : '').'>NONE &ndash; treat as a regular sale</option>';
    while ( $row = mysql_fetch_object($result) )
      {
        $account_number_select .= '
        <option value="'.$row->account_id.'"'.($product_info['account_number'] == $row->account_id ? ' selected' : '').'>('.$row->account_number.') '.$row->description.'</option>';
      }
    $account_number_select .= '
      </select>';
  }
// Generate the product_types_options and create a drop-down list
$product_types_options = '
      <option value="">Choose One</option>';
$query = '
  SELECT
    *
  FROM
    '.TABLE_PRODUCT_TYPES.'
  ORDER BY
    prodtype';

$sql =  @mysql_query($query, $connection) or die(debug_print ("ERROR: 947534 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysql_fetch_object($sql))
  {
    $selected = '';
    if ($product_info['production_type_id'] == $row->production_type_id)
      {
        $selected = ' selected';
        $proddesc = $row->proddesc;
      }
    $prod_types_display .= '
      <dt>'.$row->prodtype.'</dt>
      <dd>'.$row->proddesc.'</dd>';
    $product_types_options .= '
      <option value="'.$row->production_type_id.'"'.$selected.'>'.$row->prodtype.'</option>';
  }
// Generate the storage_types_options and create a drop-down list
$storage_types_options = '<option value="">Choose One</option>';
$query = '
  SELECT
    storage_id,
    storage_type
  FROM
    '.TABLE_PRODUCT_STORAGE_TYPES.'
    ORDER BY
        storage_type';
$sql =  @mysql_query($query, $connection) or die(debug_print ("ERROR: 616609 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
                            while ($row = mysql_fetch_object($sql))
  {
      $selected = '';
    if ($product_info['storage_id'] == $row->storage_id)
      {
        $selected = ' selected';
      }
        $store_types_display .= '
            <dt>'.$row->storage_type.'</dt>';
        $storage_types_options .= '
            <option value="'.$row->storage_id.'"'.$selected.'>'.$row->storage_type.'</option>';
  }
// Generate the meat_weight_type and create a drop-down list
if ( $product_info['meat_weight_type'] )
  {
    $meat_first  = '
      <option value="'.$product_info['meat_weight_type'].'">'.$product_info['meat_weight_type'].'</option>';
    $display_meat .= '
      <option value="'.$product_info['meat_weight_type'].'">---------</option>';
  }
else
  {
    $meat_first  = '
      <option value="">Meat Weight Type</option>';
    $display_meat .= '
      <option value="">---------</option>';
  }
$display_meat .= '
  <option value="LIVE">LIVE</option>
  <option value="PROCESSED">PROCESSED</option>
  <option value="DRESSED/HANGING">DRESSED/HANGING</option>
  <option value="">NONE</option>';
// Set up all checkboxes and radio buttons to display current values
if ($product_info['sticky'] == 1)            $chks = ' checked';
if ($product_info['hide_from_invoice'] == 1) $chkh = ' checked';
if ($product_info['confirmed'] == 1)         $chkc = ' checked';
if ($product_info['tangible'] == 1)          $chkt = ' checked';
// Set radio buttons for retail_staple
$chkf1 = '';
$chkf2 = '';
$chkf3 = '';
if     ($product_info['retail_staple'] == 1) $chkf1 = ' checked';
elseif ($product_info['retail_staple'] == 2) $chkf2 = ' checked';
elseif ($product_info['retail_staple'] == 3) $chkf3 = ' checked';



// Can this be deleted???
if (!$product_info['unit_price'] )
  {
    $show_unit_price = '0.00';
  }
else
  {
  $show_unit_price = number_format($product_info['unit_price'], 3);
  }

// Set checkboxes for listing_auth_type
$listing_auth_type_chk1 = '';
$listing_auth_type_chk2 = '';
$listing_auth_type_chk3 = '';
$listing_auth_type_chk4 = '';
if     ($product_info['listing_auth_type'] == 'archived')    $listing_auth_type_chk3 = ' checked';
elseif ($product_info['listing_auth_type'] == 'unlisted')    $listing_auth_type_chk2 = ' checked';
elseif ($product_info['listing_auth_type'] == 'institution') $listing_auth_type_chk4 = ' checked';
elseif ($product_info['listing_auth_type'] == 'member')      $listing_auth_type_chk1 = ' checked';
// Set radio button for random_weight
if (! $product_info['random_weight'])
  {
    $chk3 = ' checked';
    $chk4 = '';
    $chk3d = ' style="display:none;"'; // hide this section if not needed
  }
elseif ($product_info['random_weight'] == 1)
  {
    $chk3 = '';
    $chk4 = ' checked';
  }
// Begin main display for edit-product screen (start table/form)
if( $action == 'edit' )
  {
    $display = '<form action="'.$_SERVER['SCRIPT_NAME'].'?product_id='.$product_id.'&product_version='.$product_version.'&producer_id='.$producer_id.'&a='.$_GET['a'].'" method="post">';
  }
elseif ( $action == 'add' )
  {
    $display = '<form action="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&a='.$_GET['a'].'" method="post">';
  }
$display .= '
  <table bgcolor="#CCCCCC" border="0" cellpadding="2" cellspacing="2">';
// Admin-related fields
if(CurrentMember::auth_type('producer_admin,site_admin,cashier'))
  {
    $display .= '
    <tr bgcolor="#770000">
      <th colspan="2" align="center"><font color="#FFFFFF">Administrative Options</font></th>
    </tr>
    <tr '.$admin_bg.'>
      <td>'.format_help_link ('account_number').'Account</a></td>
      <td><b>Attach an account for sales of this product</b><br>
        <font size="-2">Probably should not use this field for regular member-producers</font>
        '.$account_number_select.'<br>
        <font size="-2">Rather than being applied to the respective producer account, proceeds from this
        transaction will be sent to some other &quot;internal&quot; account. This might be useful for
        things like membership &quot;products&quot;.</font>
      </td>
    </tr>
    <tr '.$admin_bg.'>
      <td>
        '.format_help_link ('retail_staple').'Food&nbsp;Stamps</a></td>
        <td><b><input type="radio" name="retail_staple" value="2"'.$chkf2.'> Retail Food item but not a Staple
        <br><input type="radio" name="retail_staple" value="3"'.$chkf3.'> Retail Food Item and a Staple
        <br><input type="radio" name="retail_staple" value="1"'.$chkf1.'> Non-food item</b>
      </td>
    </tr>
    <tr '.$admin_bg.'>
      <td>'.format_help_link ('sticky').'Sticky</a></td>
      <td><b><input type="checkbox" name="sticky" value="1"'.$chks.'>
        Check if only admins can alter this product after it is in a customer basket.</b><br>
        <font size="-2">Useful for placing things like membership dues into customer baskets.</font>
      </td>
    </tr>
    <tr '.$admin_bg.'>
      <td>'.format_help_link ('hide_from_invoice').'Hide&nbsp;From&nbsp;Invoice</a></td>
      <td><b><input type="checkbox" name="hide_from_invoice" value="1"'.$chkh.'>
        Check if this product should be hidden on invoice printouts</b>
      </td>
    </tr>
    <tr '.$admin_bg.'>
      <td>'.format_help_link ('product_fee_percent').'Adjust&nbsp;Fee</a></td><td>
        <b><input type="text" name="product_fee_percent" id="product_fee_percent" size="5" style="text-align:right;" value="'.$product_info['product_fee_percent'].'" onKeyUp="updatePrices()"> % Product markup</b><br>
        <b><input type="text" name="producer_adjust_fee" size="5" style="text-align:right;" value="'.$product_info['producer_fee_percent'].'" disabled> % Producer markup</b> (can not be changed here)<br>
        <b><input type="text" name="subcat_adjust_fee" size="5" style="text-align:right;" value="'.$product_info['subcategory_fee_percent'].'" disabled> % Subcategory markup</b> (can not be changed here)<br>
        <font size="-2">NOTE: subcategory markup may not be valid if subcategory &quot;'.$product_info['subcategory_name'].'&quot; is changed.</font>
      </td>
    </tr>
    <tr '.$admin_bg.'>
      <td>'.format_help_link ('confirmed').'Confirm</a></td>
      <td><b><input type="checkbox" name="confirmed" value="1"'.$chkc.'>
        Confirm this product for listing</b><br>
        <font size="-2">Checking this box will un-confirm all other versions of this product.</font>
      </td>
    </tr>';
  }
else
  {
    // When not displaying admin-related fields, we still need to have information about those product attributes
    // e.g. product_fee_percent is needed for calculating markup values
    $display .= '
    <input type="hidden" name="account_number" id="account_number" value="'.$product_info['account_number'].'">
    <input type="hidden" name="retail_staple" id="retail_staple" value="'.$product_info['retail_staple'].'">
    <input type="hidden" name="sticky" id="sticky" value="'.$product_info['sticky'].'">
    <input type="hidden" name="hide_from_invoice" id="hide_from_invoice" value="'.$product_info['hide_from_invoice'].'">
    <input type="hidden" name="product_fee_percent" id="product_fee_percent" value="'.$product_info['product_fee_percent'].'">
    <input type="hidden" name="confirmed" id="confirmed" value="'.$product_info['confirmed'].'">';
  }
// Producer product fields
$display .= '
    <input type="hidden" name="pvid" id="pvid" value="'.$product_info['pvid'].'">
    <input type="hidden" name="product_id" id="product_id" value="'.$product_info['product_id'].'">
    <input type="hidden" name="product_version" id="product_version" value="'.$product_info['product_version'].'">
    <input type="hidden" name="producer_id" id="producer_id" value="'.$producer_id.'">
    <input type="hidden" name="image_id" id="image_id" value="'.$product_info['image_id'].'">
    <input type="hidden" name="created" id="created" value="'.$product_info['created'].'">
    <input type="hidden" name="modified" id="modified" value="'.$product_info['modified'].'">
    <tr bgcolor="#770000">
      <th colspan="2" align="center"><font color="#FFFFFF">Configure This Product</font></th>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('listing_auth_type').'Listing&nbsp;Type</a></td>
      <td><b>
        <input type="radio" name="listing_auth_type" value="member"'.$listing_auth_type_chk1.'> Retail to Members<br>
        <input type="radio" name="listing_auth_type" value="institution"'.$listing_auth_type_chk4.'> Wholesale to Institutions<br>
        <input type="radio" name="listing_auth_type" value="unlisted"'.$listing_auth_type_chk2.'> Unlisted<br>
        <input type="radio" name="listing_auth_type" value="archived"'.$listing_auth_type_chk3.'> Archived<br>
        </b>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('tangible').'Tangible</a></td>
      <td><b>
        <input type="checkbox" name="tangible" value="1"'.$chkt.'>
        Keep checked if this product needs physical pickup/delivery.</b><br>
        <font size=-2>Some products (like reservations) do not require any handling.</font>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('product_name').'Product&nbsp;Name</a></td>
      <td>
        <input name="product_name" size="60" maxlength="75" value="'.htmlspecialchars($product_info['product_name'], ENT_QUOTES).'"><br>
        <font size="-2">(max. length 75 characters)</font>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('product_description').'Description</a></td>
      <td><textarea name="product_description" cols="60" rows="7">'.htmlspecialchars(br2nl ($product_info['product_description']), ENT_QUOTES).'</textarea><br>(not required)</td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('subcategory_id').'Subcategory</a></td>
      <td>
        <select id="subcategory_id" name="subcategory_id" onChange="lookup(document.getElementById("subcategory_id").value);">
          '.$subcat_first.'
          '.$display_subcat.'
        </select>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('inventory_pull').'Inventory&nbsp;Rate</a><br>'.format_help_link ('inventory_id').'Inventory&nbsp;Name</a></td>
      <td>Each item purchased will pull <input type="text" name="inventory_pull" value="'.$product_info['inventory_pull'].'" size=3 maxlength="6"> unit(s) of...<br>
      '.$inventory_select.'<span id="inventory_pull"> from inventory.</span>
        </div>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('unit_price').'Price</a><br>'.format_help_link ('pricing_unit').'Pricing&nbsp;Unit</a></td>
      <td>
        <table>
          <tr>
            <td style="padding:0 1em;" align="right">'.$alert5.'<b>Producer&nbsp;Price:</b></td>
            <td><nobr>$ </b><input class="disabled" type="text" id="unit_price_prdcr" name="unit_price" value="'.number_format($show_unit_price * (1 - ActiveCycle::producer_markdown_next ()), 2).'" size="8" maxlength="8" disabled></nobr></td>
            <td style="padding:0 1em;" rowspan="3"><b>per '.$alert5a.'<input name="pricing_unit" size="12" maxlength="12" value="'.$product_info['pricing_unit'].'"></b><br>
            <font size="-2">(Use singular, not plural; e.g. pound instead of pounds, loaf instead of loaves, ox instead of oxen, etc.)</font></td>
          </tr>
          <tr>
            <td style="padding:0 1em;" align="right">'.$alert5.'<b>Coop&nbsp;Price:</td>
            <td><nobr>$</b> <input type="text" id="unit_price_coop" name="unit_price" value="'.number_format($show_unit_price, 3).'" size=8 maxlength="8" onKeyUp="updatePrices()" onChange="document.getElementById("unit_price_coop").value=(document.getElementById("unit_price_coop").value*1).toFixed(2)"></nobr></td>
          </tr>
          <tr>
            <td style="padding:0 1em;" align="right">'.$alert5.'<b>Retail&nbsp;Price:</b></td>
            <td><nobr>$</b> <input class="disabled" type="text" id="unit_price_cust" name="unit_price" value="'.number_format($show_unit_price * (1 + (SHOW_ACTUAL_PRICE ? ActiveCycle::retail_markup_next () : 0)) * (1 + ($product_info['product_fee_percent'] / 100) + ($product_info['subcategory_fee_percent'] / 100) + ($product_info['producer_fee_percent'] / 100)), 2).'" size="8" maxlength="8" disabled></nobr></td>
          </tr>';
if (INSTITUTION_WINDOW > 0) // Only show wholesale values if there is a wholesale opportunity
  {
    $display .= '
          <tr>
            <td style="padding:0 1em;" align="right">'.$alert5.'<b>Wholesale&nbsp;Price:</b></td>
            <td><nobr>$</b> <input type="text" id="unit_price_institution" name="unit_price" value="'.number_format($show_unit_price * (1 + (SHOW_ACTUAL_PRICE ? ActiveCycle::wholesale_markup_next () : 0)) * (1 + ($product_info['product_fee_percent'] / 100) + ($product_info['subcategory_fee_percent'] / 100) + ($product_info['producer_fee_percent'] / 100)), 2).'" size="8" maxlength="8" disabled></nobr></td>
          </tr>';
  }
$display .= '
        </table>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('ordering_unit').'Ordering&nbsp;Unit</a></td>
      <td>
        Order by number of
        <input name="ordering_unit" size="20" maxlength="20" value="'.htmlspecialchars($product_info['ordering_unit'], ENT_QUOTES).'">(s)<br>
        <font size=-2>(Use singular, not plural; e.g. package, steak, bag, jar, pound, ounce, item, dozen, etc.)</font>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('extra_charge').'Extra&nbsp;Charge</a></td>
      <td>
       $&nbsp;<input type="text" name="extra_charge" value="'.number_format((double)$product_info['extra_charge'], 2).'" size="9" maxlength="9"><br>
        <font size=-2>(Not subject to coop-fee or taxes. Authorization is required before using this charge.)</font>
      </td>
    </tr>
    <tr '.$ranwt_bg.'>
      <td>'.format_help_link ('random_weight').'Random&nbsp;Weight</a></td>
      <td>
        Will producer need to enter a weight on the invoice to determine price?
        <font size=-2>(Please see instructions.)</font><br>
        <input type="radio" name="random_weight" value="1"'.$chk4.' onClick=\'{document.getElementById("max_min").style.display="";document.getElementById("weight_type").style.display="";}\'> Yes
        <input type="radio" name="random_weight" value="0"'.$chk3.' onClick=\'{document.getElementById("max_min").style.display="none";document.getElementById("weight_type").style.display="none";}\'> No
      </td>
    </tr>
    <tr '.$ranwt_bg.' id="max_min"'.$chk3d.'>
      <td>'.format_help_link ('minimum_weight').'Minimum&nbsp;Weight</a><br>'.format_help_link ('maximum_weight').'Maximum&nbsp;Weight</a></td>
      <td>
        If Random Weight is Yes: <br>
        <b>Approx. Minimum weight</b>:
        <input type="text" name="minimum_weight" value="'.$product_info['minimum_weight'].'" size="8" maxlength="8">
        &nbsp;&nbsp;&nbsp;&nbsp;<b>Approx. Maximum weight</b>:
        <input type="text" name="maximum_weight" value="'.$product_info['maximum_weight'].'" size="8" maxlength="8"><br>
        <font size=-2>(For example, if pricing unit is pounds, min. weight could be 1 pound, max. weight could be 2 pounds. Use up to 2 decimal places.)</font>
      </td>
    </tr>
    <tr '.$ranwt_bg.' id="weight_type"'.$chk3d.'>
      <td>'.format_help_link ('meat_weight_type').'Meat&nbsp;Weight&nbsp;Type</a></td>
      <td>
        Meat weight type is only valid for random weight items:
        <select name="meat_weight_type">
          '.$meat_first.'
          '.$display_meat.'
        </select>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('production_type_id').'Production&nbsp;Type</a></td>
      <td>
        <select name="production_type_id">
          '.$prod_types_display.'
          '.$product_types_options.'
        </select>
      </td>
    </tr>
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('storage_type').'Storage&nbsp;Type</a></td>
      <td>Indicate the type of storage:
        <select name="storage_id">
          '.$store_types_display.'
          '.$storage_types_options.'
        </select>
      </td>
    </tr>
<!-- Future deliver options are not currently (and never have been) implemented
    <tr'.$norm_bg.'>
      <td>'.format_help_link ('future_delivery').'Future Delivery</a></td>
      <td>
        If this product needs to be ordered one or more order cycles in advance of the order cycle in
        which it will be delivered, contact <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a> for assistance.
      </td>
    </tr>
-->
    <input type="hidden" name="referrer" value="'.$referrer.'">';
// When editing a product, need to know the old product info. Also need to have a save-as-new option
if( $action == 'edit' )
  {
    $display .= '
      <tr '.$cntrl_bg.'>
        <td>'.format_help_link ('operation').'Save as New</a></td>
        <td align="left">
          <input type="hidden" name="action" value="edit">
          <input type="checkbox" name="save_as_new" value="Save as a New Product"'.($_POST['save_as_new'] ? ' checked' : '').'> Check here to keep the original product as it was and save these changes as a new product. The new product will have the same image, so it may need to be changed.
        </td>
      </tr>
      <tr '.$cntrl_bg.'>
        <td>'.format_help_link ('operation').'Operation</a>
        <td align="left">
          <font size=-2>* NOTE: Any changes made to items marked with an asterisk will require confirmation by an administrator.</font><br>
          <table width="100%" border="0" '.$cntrl_bg.'>
            <tr>
              <td align="center" width="50%">
                <input name="submit_action" type="submit" value="Update Product">
              </td>
              <td align="center" width="50%">
                <input name="submit_action" type="submit" value="Cancel">
                </form>
              </td>
            </tr>
          </table>
        </td>
      </tr>';
  }
// When just adding a product, we do not need to know as much
elseif( $action == 'add' )
  {
    $display .= '
      <tr'.$cntrl_bg.'>
        <td>'.format_help_link ('operation').'Operation</a>
        <td align="center">
          <table width="100%" border="0" '.$cntrl_bg.'>
            <tr>
              <td align="center" width="50%">
                <input type="hidden" name="producer_id" value="'.$producer_id.'">
                <input name="submit_action" type="submit" value="Add Product">
              </td>
              <td align="center" width="50%">
                <input name="submit_action" type="submit" value="Cancel">
                </form>
              </td>
            </tr>
          </table>
        </td>
      </tr>';
  }
// Finally.. finish off the page
$display .= '
  </table>';
$display .= $font.' <br><br>For questions not covered in the <a href="help.php">(?)</a>links,<br>contact <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a>';
include('func/show_businessname.php');
$help = '
  <table>
    <tr>
      <td valign="top">
        <font face="arial" size="2">If you have any questions about what a particular section means, please click on the question mark (?) to the left of that section.  If you are still not sure, then please e-mail <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a>.<br><br>
      </td>
      <td>
        <form action="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&a='.$_REQUEST['a'].'" method="post">
          <input type="hidden" name="referrer" value="'.$referrer.'">
          <input type="hidden" name="product_id" value="'.$_REQUEST['product_id'].'">
          <input type="hidden" name="producer_id" value="'.$producer_id.'">
          <input name="submit_action" type="submit" value="Cancel">
        </form>
      </td>
    </tr>
  </table>';
?>