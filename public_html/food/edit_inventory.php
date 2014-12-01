<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');


$date_today = date("F j, Y");
$display_type = "edit";

// Check if auth_type = producer_admin and there is a producer_id provided
if (CurrentMember::auth_type('producer_admin') && $_GET['producer_id'])
  {
    // Keep the same producer_id value
    $producer_id = $_GET['producer_id'];
  }
elseif ($_SESSION['producer_id_you'])
  {
    $producer_id = $_SESSION['producer_id_you'];
  }

// Figure out where we came from and save it so we can go back
if (isset ($_REQUEST['referrer']))
  {
    $referrer = $_REQUEST['referrer'];
  }
else
  {
    $referrer = $_SERVER['HTTP_REFERER'];
  }

if (! $sort_by = $_GET['sort_by']) $sort_by = 'description';

// If we only need to handle one inventory item (for convenience) then restrict the query
$and_where = '';
if ($_REQUEST['target_inventory_id']) $and_where = '
    AND '.TABLE_INVENTORY.'.inventory_id = '.$_REQUEST['target_inventory_id'];
// Get this producer's list of inventory options as a baseline reference (no sense in changing
// any database values if they weren't actually changed, eh?
$query = '
  SELECT
    '.TABLE_INVENTORY.'.*,
    product_id,
    product_version,
    product_name,
    inventory_pull,
    confirmed
  FROM
    '.TABLE_INVENTORY.'
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(inventory_id)
  WHERE
    '.TABLE_INVENTORY.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"'.$and_where.'
  ORDER BY
    description,
    product_id,
    product_version';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 286875 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$inventory_array = array();
$update_query = array();
while ( $row = mysql_fetch_object($result) )
  {
    $inventory_id = $row->inventory_id;
    // If we received new post data, then first do any updates
    if ($_POST['action'] == 'Update/Delete/Add')
      {
        // array_push ($update_query, 'COMPARE: '.$row->description.' WITH '.$_POST['description-'.$inventory_id]);
        if ($row->description != $_POST['description-'.$inventory_id])
          {
            array_push ($update_query, '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                description = "'.mysql_real_escape_string($_POST['description-'.$inventory_id]).'"
              WHERE
                inventory_id = '.$inventory_id);
            $row->description = $_POST['description-'.$inventory_id];
            $message .= 'Changed description for &quot;'.$row->description.'&quot;<br>';
          }
        // array_push ($update_query, 'COMPARE: '.$row->quantity.' WITH '.$_POST['quantity-'.$inventory_id]);
        if ($row->quantity != $_POST['quantity-'.$inventory_id])
          {
            array_push ($update_query, '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                quantity = "'.mysql_real_escape_string(floor ($_POST['quantity-'.$inventory_id])).'"
              WHERE
                inventory_id = '.$inventory_id);
            $row->quantity = $_POST['quantity-'.$inventory_id];
            $message .= 'Quantity changed for &quot;'.$row->description.'&quot;<br>';
          }
      }
    // If we need to delete the inventory_id, then set that up and DON'T update the $inventory_array
    if ($_POST['delete-'.$inventory_id] == 'true')
      {
        array_push ($update_query, '
          DELETE FROM
            '.TABLE_INVENTORY.'
          WHERE
            inventory_id = '.$inventory_id.'
            AND
              (
                SELECT
                  COUNT(product_id)
                FROM
                  '.NEW_TABLE_PRODUCTS.'
                WHERE
                  inventory_id = '.$inventory_id.'
              ) = 0');
            $message .= 'Deleted inventory &quot;'.$row->description.'&quot;<br>';
      }
    else // Set the old/new values in the $inventory_array
      {
        // New inventory_id so initialize the save_this_product_id flag
        if ($inventory_id != $inventory_id_prior || $row->product_id != $product_id_prior) $save_this_product_id = true;
        // If the row is confirmed, we use it!
        if ($row->confirmed == 1 || $save_this_product_id)
          {
            $inventory_array[$inventory_id]['description'] = $row->description;
            $inventory_array[$inventory_id]['quantity'] = $row->quantity;
            if ($row->confirmed) $confirmed_mark = '<span class="conf_check">&#10004;</span>';
            else $confirmed_mark = '<span class="conf_check">&nbsp;</span>';
            // Save the product link and name
            if ($row->product_id) $inventory_array[$inventory_id]['product_name'][$row->product_id] = $confirmed_mark.' [<a href="edit_products.php?product_id='.$row->product_id.'&amp;product_version='.$row->product_version.'&amp;producer_id='.$producer_id.'&amp;a=inventory">Edit '.$row->product_id.']</a> '.$row->product_name.' (x'.$row->inventory_pull.')';
            // Does not matter if this is incremented for each version... we do not want to
            // allow deleting the inventory_id until ALL versions are no longer linked.
            $inventory_array[$inventory_id]['number_of_products'] += 1;
            // Save the current product_id to watch for changes
            $product_id_prior = $row->product_id;
            // Only clobber values until we find one with confirmed=1 for the product_id
            if ($row->confirmed == 1) $save_this_product_id = false;
          }
      }
    $inventory_id_prior = $inventory_id;
  }

// If we came from the product list, then return to it...
if ($_REQUEST['target_inventory_id'] && $_POST['action'] == 'Update/Delete/Add')
  {
    header('Location: '.$referrer.'#X'.$product_id);
  }


// Handle the special cases of the three optionally-added new inventory lines
foreach (array('A', 'B', 'C') as $new_row)
  {
    if ($_POST['description-'.$new_row] != '')
      {
        // These are not done as part of the update_query because we need to get the 
        // insert_id back for updating the $inventory_array correctly.
        $query = '
          INSERT INTO
            '.TABLE_INVENTORY.'
          SET
            producer_id = "'.mysql_real_escape_string($producer_id).'",
            description = "'.mysql_real_escape_string($_POST['description-'.$new_row]).'",
            quantity = "'.mysql_real_escape_string($_POST['quantity-'.$new_row]).'"';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 586385 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $inventory_id = mysql_insert_id ();
        // Now that we have any new values, set the arrays we will use later to populate the form
        $inventory_array[$inventory_id]['description'] = $_POST['description-'.$new_row];
        $inventory_array[$inventory_id]['quantity'] = $_POST['quantity-'.$new_row];
        if ($row->product_id) {} // There will never be a product_id for a new inventory item
      }
  }

function multisort($array, $sort_by, $key1, $key2=NULL, $key3=NULL, $key4=NULL, $key5=NULL, $key6=NULL)
  {
    if (count ($array) > 0)
      {
        // sort by ?
        foreach ($array as $pos =>  $val)
            $tmp_array[$pos] = $val[$sort_by];
        asort($tmp_array);

        // display however you want
        foreach ($tmp_array as $pos =>  $val){
            $return_array[$pos][$sort_by] = $array[$pos][$sort_by];
            $return_array[$pos][$key1] = $array[$pos][$key1];
            if (isset($key2)){
                $return_array[$pos][$key2] = $array[$pos][$key2];
                }
            if (isset($key3)){
                $return_array[$pos][$key3] = $array[$pos][$key3];
                }
            if (isset($key4)){
                $return_array[$pos][$key4] = $array[$pos][$key4];
                }
            if (isset($key5)){
                $return_array[$pos][$key5] = $array[$pos][$key5];
                }
            if (isset($key6)){
                $return_array[$pos][$key6] = $array[$pos][$key6];
                }
            }
        return $return_array;
      }
  }

//usage (only enter the keys you want sorted):

$inventory_array = multisort ($inventory_array, 'description', 'description', 'quantity', 'product_name', 'number_of_products');

// Post the update queries to the database
foreach (array_values ($update_query) as $query)
  {
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 155863 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
  }

// Show the inventory-control form
$display = '
<div style="width:95%;margin:auto;">
  <table id="inventory_help">
    <tr>
      <td>
        '.($message ? '<p style="color:#800;">'.$message.'</p>' : '').'
        <p>Use this page to update your inventory quantities and add/delete inventory types.</p>
        <p><strong>UPDATE:</strong> Multiple products may draw from the same inventory &quot;buckets&quot; and may draw
        at different rates.  Assign the inventory &quot;bucket&quot; to your products on the regular product editing
        screen.</p>
        <p><strong>DELETE:</strong> To delete existing inventory &quot;buckets&quot;, select the delete checkbox before
        updating the screen (only inventory &quot;buckets&quot; without products may be deleted).  Deletion will not be
        verified and can not be undone.</p>
        <p><strong>ADD:</strong> To add new inventory &quot;buckets&quot;, enter up to three at a time in the empty
        fields at the bottom.</p>
        <p><strong>TIP:</strong> Customers do not see the names in this list.  In order to group or sort the values more
        conveniently, you can prefix the names with a number or letter.</p>
      </td>
    </tr>
  </table>
  <br>
  <form action="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'" method="post">
  <table id="inventory">
    <tr>
      <th class="left">Description<div style="float:right; background-color:#eef;padding:1px 3px;font-size:80%;font-weight:normal;"><a href="'.$_SERVER['SCRIPT_NAME'].'?'.($_REQUEST['target_inventory_id']? 'target_inventory_id='.$_REQUEST['target_inventory_id'].'&' : '').($_REQUEST['a']? 'a='.$_REQUEST['a'].'&' : '').'enable_edit=true">[Edit descriptions]</a></div></th>
      <th class="center">Qty in stock</th>
      <th class="left">Used by products... (checked if confirmed)</th>
      <th class="center">Delete?</th>
    </tr>';
if ( count ($inventory_array) > 0)
  {
    foreach (array_keys ($inventory_array) as $inventory_id)
      {
        $display .= '
        <tr>
          <td class="left">'.($_REQUEST['enable_edit'] == 'true' ? '<input type="text" size="30" name="description-'.$inventory_id.'" value="'.htmlspecialchars ($inventory_array[$inventory_id]['description'], ENT_QUOTES).'">' : '<input type="hidden" name="description-'.$inventory_id.'" value="'.htmlspecialchars ($inventory_array[$inventory_id]['description'], ENT_QUOTES).'">'.htmlspecialchars ($inventory_array[$inventory_id]['description'], ENT_QUOTES)).'</td>
          <td class="center"><input type="text" size="3" name="quantity-'.$inventory_id.'" value="'.htmlspecialchars ($inventory_array[$inventory_id]['quantity'], ENT_QUOTES).'"></td>
          <td class="left">'.nl2br (@implode ("\n", $inventory_array[$inventory_id]['product_name'])).'</td>
          <td class="center">'.($inventory_array[$inventory_id]['number_of_products'] == 0 ? '<input type="checkbox" name="delete-'.$inventory_id.'" value="true">' : '' ).'</td>
        </tr>';
      }
  }

// Add three rows for new inventory items
if (! $_REQUEST['target_inventory_id'])
  {
    $display .= '
    <tr>
      <th colspan="4">Add new inventory &quot;buckets&quot; below:</th>
    </tr>
    <tr>
      <td class="left"><input type="text" size="30" name="description-A" value=""></td>
      <td class="center"><input type="text" size="3" name="quantity-A" value=""></td>
      <td class="left">&nbsp;</td>
      <td class="center">&nbsp;</td>
    </tr>
    <tr>
      <td class="left"><input type="text" size="30" name="description-B" value=""></td>
      <td class="center"><input type="text" size="3" name="quantity-B" value=""></td>
      <td class="left">&nbsp;</td>
      <td class="center">&nbsp;</td>
    </tr>
    <tr>
      <td class="left"><input type="text" size="30" name="description-C" value=""></td>
      <td class="center"><input type="text" size="3" name="quantity-C" value=""></td>
      <td class="left">&nbsp;</td>
      <td class="center">&nbsp;</td>
    </tr>';
  }

// form value for "action" is "hidden" instead of "submit" to allow for IE when <return> is pressed without using submit button
$display .= '
    <tr>
      <td colspan="4" class="center">
        <input type="hidden" name="target_inventory_id" value="'.$_REQUEST['target_inventory_id'].'">
        <input type="hidden" name="a" value="'.$_REQUEST['a'].'">
        <input type="hidden" name="referrer" value="'.$referrer.'">
        <input type="hidden" name="enable_edit" value="'.$_REQUEST['enable_edit'].'">
        <input type="hidden" name="action" value="Update/Delete/Add">
        <input class="button" type="submit" name="null" value="Update/Delete/Add">
      </td>
    </tr>
  </table>
  </form>';

$display .= '
</div>';

include("func/show_businessname.php");

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">'.($_REQUEST['target_inventory_id'] ? 'Set Inventory for '.$inventory_array[$inventory_id]['description'] : 'Manage Inventory').'</span>';
$page_title = $business_name.': '.($_REQUEST['target_inventory_id'] ? 'Set Inventory for '.$inventory_array[$inventory_id]['description'] : 'Manage Inventory');
$page_tab = 'producer_panel';

$page_specific_css = '
  <style type="text/css">
    #inventory_help {
      border:2px solid #000;
      background-color:#eee;
      }
    #inventory {
       border:1px solid #000;
       width:100%;
       }
    #inventory th {background-color:#006;color:#ffe;padding:3px;}
    #inventory td {background-color:#ffe;padding:3px;}
    .button {margin:1em;padding:0.5em;}
    .conf_check {display:inline-block;width:1em;}
  </style>';

include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
