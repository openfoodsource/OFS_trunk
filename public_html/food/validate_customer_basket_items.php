<?php
include_once 'config_openfood.php';
session_start();

// CHECK FOR AJAX CALL (for compactness, this script handles its own ajax)
if ($_REQUEST['ajax'] == 'yes')
  {
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Update the database with new information                              //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'update_db')
      {
        $basket_id = $_REQUEST['basket_id'];
        $product_id = $_REQUEST['product_id'];
        $delivery_id = $_REQUEST['delivery_id'];
        $update_content = $_REQUEST['update_content'];
        $update_field = $_REQUEST['update_field'];
        // The $where_condition will depend upon what field is being updated.
        // For global fields (all products for everyone for the delivery cycle):
        if ($update_field == 'product_name' ||
            $update_field == 'item_price' ||
            $update_field == 'random_weight' ||
            $update_field == 'taxable' ||
            $update_field == 'extra_charge' ||
            $update_field == 'pricing_unit' ||
            $update_field == 'ordering_unit')
          {
            $where_condition = '
          WHERE delivery_id = "'.mysql_real_escape_string($delivery_id).'"
            AND product_id = "'.mysql_real_escape_string($product_id).'"';
          }
        // For order data (specific to this customer's order/invoice)
        elseif ($update_field == 'quantity' ||
                $update_field == 'total_weight' ||
                $update_field == 'out_of_stock')
          {
            $where_condition = '
          WHERE basket_id = "'.mysql_real_escape_string($basket_id).'"
            AND product_id = "'.mysql_real_escape_string($product_id).'"';
          }
        $query = '
          UPDATE
            '.NEW_TABLE_BASKET_ITEMS.'
          RIGHT JOIN '.NEW_TABLE_BASKETS.' USING (basket_id)
          SET
            '.mysql_real_escape_string($update_field).' = "'.mysql_real_escape_string($update_content).'"'.
          $where_condition;
        $result= mysql_query("$query") or die("Error: 853040" . mysql_error());
        if (($affected_rows = mysql_affected_rows()) > 0)
          {
            // $affected_rows = mysql_affected_rows($result);
            $ajax_content .= "SUCCESS   Updated $affected_rows ".Inflect::pluralize_if ($affected_rows, 'row').'.<pre>'.$query.'</pre>';
          }
        else
          {
            $ajax_content .= $query;
          }
        echo "$ajax_content";
        exit (0);
      }
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Display the old invoice for this order                                //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'view_invoice')
      {
        $basket_id = $_REQUEST['basket_id'];
        $ajax_content = '
          <body>';
        $query = '
          SELECT
            invoice_content
          FROM
            customer_invoices
          WHERE
            basket_id = "'.mysql_real_escape_string ($basket_id).'"';
        $result= mysql_query("$query") or die("Error: 753021" . mysql_error());
        if ($row = mysql_fetch_object($result))
          {
            $ajax_content .= '<html><head><title>Invoice:'.$basket_id.'</title></head><body>'.$row->invoice_content.'</body>';
          }
        else
          {
            $ajax_content .= '<html><head><title>Invoice:NULL</title></head><body><h1>NO DATA TO REPORT</h1></body>';
          }
        echo "$ajax_content";
        exit (0);
      }
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Get a list of baskets for the particular delivery_id                  //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    if ($_REQUEST['process'] == 'get_basket_list')
      {
        $delivery_id = $_REQUEST['delivery_id'];
        $ajax_content = '
          <ul>
            <li><div class="p_list_header p_list_pid">Basket&nbsp;ID</div><div class="p_list_header p_list_name">Member # / [Quantity]</div></a></li>';
        $query = '
          SELECT
            '.NEW_TABLE_BASKETS.'.member_id,
            '.NEW_TABLE_BASKETS.'.basket_id,
            COUNT('.NEW_TABLE_BASKET_ITEMS.'.product_id) AS quantity
          FROM
            '.NEW_TABLE_BASKETS.'
          LEFT JOIN
            '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
          WHERE
            '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
          GROUP BY
            '.NEW_TABLE_BASKET_ITEMS.'.basket_id
          ORDER BY
            '.NEW_TABLE_BASKETS.'.basket_id';
        $result= mysql_query("$query") or die("Error: 678574" . mysql_error());
        while($row = mysql_fetch_object($result))
          {
            $ajax_content .= '
          <li id="producer_id:'.$row->basket_id.'" class="basket_incomplete" onClick="window.open(\'validate_customer_basket_items.php?ajax=yes&process=view_invoice&basket_id='.$row->basket_id.'\',\'external\')"><div class="p_list_pid">'.$row->basket_id.'</div><div class="p_list_name">Member '.$row->member_id.' ['.$row->quantity.' Items]</div></li>';
          }
        $ajax_content .= '
          </ul>';
        echo "$ajax_content";
        exit (0);
      }
    ////////////////////////////////////////////////////////////////////////////
    //                                                                        //
    //  MAJOR SECTION FOR AJAX                                                //
    //  Compare old invoice with customer_basket_items                        //
    //                                                                        //
    ////////////////////////////////////////////////////////////////////////////
    elseif ($_REQUEST['process'] == 'process_basket')
      {
        // Get the historic invoice for this order
        $query = '
          SELECT
            *
          FROM
            customer_invoices
          WHERE
            basket_id = "'.mysql_real_escape_string ($_REQUEST['basket_id']).'"';
        $result= mysql_query("$query") or die("Error: 789204" . mysql_error());
        if($row = mysql_fetch_object($result))
          {
            $invoice_content0 = $row->invoice_content;
          }
        // Allow different filters for different basket ranges
        if ($_REQUEST['basket_id'] <= 2458)
          {
            // First remove any new-lines
            $invoice_content1 = preg_replace ('/[\n\r]+/', ' ', $invoice_content0);
            // Change all <tr strings to newlines: gives each table row its own line.
            $this_pattern = array ("/\<tr/", "/\<\/tr[^\>]*\>/", "/\n+/");
            $that_result = array ("\n<tr", "\\0\n", "\n");
            $invoice_content2 = preg_replace ($this_pattern, $that_result, $invoice_content1);
            // Strip all but the <b> tags: they SEEM to be immediately around all relevant content
            $invoice_content3 = strip_tags ($invoice_content2, '<b><img>');
            // Remove any excess content between <b> tags
            $invoice_content4 = preg_replace ('/<\/b>[^\n\*<]*<b>/', "\t", $invoice_content3);
            // Replace <img > tags with "OUT" (a generalization, but seems okay)
            $invoice_content5 = preg_replace ('/<(img|IMG)[^>]+>[\n]*\s*/', "OUT\t", $invoice_content4);
            // Remove any leading whitespace
            $invoice_content6 = preg_replace ('/\n[\t ]*/', "\n", $invoice_content5);
            // Remove the leftover ...<b> tags
            $invoice_content7 = preg_replace ('/<b>(.*?)<\/b>/', "\$1", $invoice_content6);
            // And remove the leftover </b>... tags
            $invoice_content8 = preg_replace ('/[\s\t]*<\/b>[\s\t]*\n/', "\n", $invoice_content7);
            // Consolidate any spaces adjacent to tab characters
            $invoice_content = preg_replace ('/ *\t */', "\t", $invoice_content8);
            // Set the patern for retrieving this data
            //                          OUT?   PID    PName       UnitPrice    PriceUnit Qty      OrderUnit  Weight          PriceUnit  ExtraCharge      Tax?    LineTotal
            $product_grep_pattern = '/^(OUT\t|)(\d+)\t([^\t]+)\t\$([\d\.\-]+)\/([^\t]+)\t([^ \t]+) ([^\t]+)\t(?:([\d\.\-]*) *([^\t]+))*\t(?:\$([\d\-\.]+))?.*?(\*|) *\$(.*)$/';
            // Create output arrays
            $invoice_lines = explode ("\n", $invoice_content);
            $product_lines = preg_grep ($product_grep_pattern, $invoice_lines);
            foreach ($product_lines as $product_line)
              {
                if (preg_match ($product_grep_pattern, $product_line, &$product_matches))
                  {
                    $product_id = $product_matches[2];
                    $old_invoice[$product_id] = $product_matches;
                    // Associate the array values
                    $old_invoice[$product_id]['out_of_stock'] = ($product_matches[1] == 'OUT' ? 1 : 0);
                    $old_invoice[$product_id]['product_id'] = $product_matches[2];
                    $old_invoice[$product_id]['product_name'] = $product_matches[3];
                    $old_invoice[$product_id]['item_price'] = $product_matches[4];
                    $old_invoice[$product_id]['pricing_unit'] = $product_matches[5];
                    $old_invoice[$product_id]['quantity'] = $product_matches[6];
                    $old_invoice[$product_id]['ordering_unit'] = $product_matches[7];
                    $old_invoice[$product_id]['total_weight'] = ($product_matches[8] ? $product_matches[8] : 0);
                    $old_invoice[$product_id]['random_weight'] = ($product_matches[8] ? 1 : 0);
                    // $old_invoice[$product_id]['weight_unit'] = $product_matches[9]; // same as pricing unit
                    $old_invoice[$product_id]['extra_charge'] = ($product_matches[10] ? $product_matches[10] / $product_matches[6] : 0);
                    $old_invoice[$product_id]['taxable'] = ($product_matches[11] == '*' ? 1 : 0);
                    $old_invoice[$product_id]['line_total'] = $product_matches[12]; // calculated value
                  }
              }
          }
        else
          {
            // First remove any new-lines
            $invoice_content1 = preg_replace ('/[\n\r]+/', ' ', $invoice_content0);
            // Change all <tr strings to newlines: gives each table row its own line.
            $this_pattern = array ("/\<tr/", "/\<\/tr[^\>]*\>/", "/\n+/");
            $that_result = array ("\n<tr", "\\0\n", "\n");
            $invoice_content2 = preg_replace ($this_pattern, $that_result, $invoice_content1);
            // Replace all </td>...<td with tabs
            $invoice_content3 = preg_replace ('/<\/td>[^\n<]+<td[^>]*>/', "\t", $invoice_content2);
            // Replace <> with " + " because it is used before extra_charge items in some invoices
            // Trouble: This creates a double " + + " situation but that is solved separately.
            // Hopefully this pattern doesn't appear anywhere else.
            $invoice_content4 = preg_replace ('/<span style="color:#006;">/', ' + ', $invoice_content3);
            // Strip all tags
            $invoice_content5 = strip_tags ($invoice_content4,'<img>');
            // Replace <img > tags with "OUT" (a generalization, but seems okay)
            $invoice_content6 = preg_replace ('/<(img|IMG)[^>]+>/', 'OUT', $invoice_content5);
            // Strip all nbsp; sequences
            $invoice_content7 = preg_replace ('/&nbsp;/', "", $invoice_content6);
            // Strip all leading space
            $invoice_content8 = preg_replace ('/\n[ \t]+/', "\n", $invoice_content7);
            // And all trailing space
            $invoice_content9 = preg_replace ('/[ \t]+\n/', "\n", $invoice_content8);
            // Consolidate any spaces adjacent to tab characters
            $invoice_content = preg_replace ('/ *\t */', "\t", $invoice_content9);
            // Set the patern for retrieving this data
            //                          Out?   PID    PName       Tax?   UnitPrice         PriceUnit                      ExtraCharge         OrderUnit     Qty       OrderUnit    Weight       WeightUnit           LineTotal
            $product_grep_pattern = '/^(OUT\t*|)(\d+)\t([^\t]+)\t *(\*|) *(?:\$([\d\.\-]+)\/([^\t\+]+?))? *(?:(?:[\+ ]*|and)* *(?: *\$([\d\-\.]+)\/([^\t]+))?)?\t([^ \t]+) ([^\t]+)\t(?:([\d\.\-]+) *([^\t]+))* *\t{0,2}\$(.*)$/';
            // SPECIAL NOTE: For certain orders, extra_charge is indistinguishable from regular price.
            // Create output arrays
            $invoice_lines = explode ("\n", $invoice_content);
            $product_lines = preg_grep ($product_grep_pattern, $invoice_lines);
            foreach ($product_lines as $product_line)
              {
                if (preg_match ($product_grep_pattern, $product_line, &$product_matches))
                  {
                    $product_id = $product_matches[2];
                    // Associate the array values
                    $old_invoice[$product_id]['out_of_stock'] = ($product_matches[1] == 'OUT' ? 1 : 0);
                    $old_invoice[$product_id]['product_id'] = $product_matches[2];
                    $old_invoice[$product_id]['product_name'] = $product_matches[3];
                    $old_invoice[$product_id]['taxable'] = ($product_matches[4] == '*' ? 1 : 0);
                    $old_invoice[$product_id]['item_price'] = $product_matches[5];
                    $old_invoice[$product_id]['pricing_unit'] = $product_matches[6];
                    $old_invoice[$product_id]['extra_charge'] = ($product_matches[7] ? $product_matches[7] / $product_matches[9] : 0);
                    // $old_invoice[$product_id]['ordering_unit'] = $product_matches[8];
                    $old_invoice[$product_id]['quantity'] = $product_matches[9];
                    $old_invoice[$product_id]['ordering_unit'] = $product_matches[10]; // repeat ordering unit
                    $old_invoice[$product_id]['total_weight'] = ($product_matches[11] ? $product_matches[11] : 0);
                    // $old_invoice[$product_id]['weight_unit'] = $product_matches[12]; // same as pricing unit
                    $old_invoice[$product_id]['random_weight'] = ($product_matches[11] ? 1 : 0);

                    $old_invoice[$product_id]['line_total'] = $product_matches[13]; // calculated value
                  }
              }
          }
        // Get a list of the basket items
        $basket_id = $_REQUEST['basket_id'];
        $query = '
          SELECT
            '.NEW_TABLE_BASKET_ITEMS.'.*
          FROM
            '.NEW_TABLE_BASKET_ITEMS.'
          WHERE
            basket_id = "'.mysql_real_escape_string ($basket_id).'"
          ORDER BY
            product_id';
        $result= mysql_query("$query") or die("Error: 432673" . mysql_error());
        $errors = 0;
        $error_flag = '*';
        // Configure the string used for clicking to transfer
        $transfer_this = '&rarr;';
        while($row = mysql_fetch_object($result))
          {
            // Skip until we get past the product_id that [might have been] just handled
            // No requested product_id means to process everything...
            if ($_REQUEST['product_id'] == 0)
              {
                // Set the customer_basket_items values
                $product_id = $row->product_id;
                $product_name = trim($row->product_name);
                $item_price = $row->item_price;
                $pricing_unit = trim($row->pricing_unit);
                $ordering_unit = trim($row->ordering_unit);
                $quantity = $row->quantity;
                $random_weight = ($row->random_weight ? 1 : 0);
                $total_weight = ($row->total_weight != 0 ? $row->total_weight : 0);
                $taxable = ($row->taxable ? 1 : 0);
                $extra_charge = ($row->extra_charge ? $row->extra_charge : 0);
                $out_of_stock = ($row->out_of_stock ? 1 : 0);
                // Sanity check to see if product ID was found on the invoice page
                if ($product_id != $old_invoice[$product_id]['product_id'])
                  {
                    $error['product_id'] = $error_flag;
                    $errors ++;
                  }
                // $old_invoice[$product_id][$field_name] is data interpreted from the stored finalized invoice
                // Catch some generalized assumptions to limit number of false negatives:
                // If customer_basket_items are marked "out" but invoice is not, then we will assume the customer_basket_itmes are correct.
                if ($out_of_stock == 1)
                  {
                    $old_invoice[$product_id]['out_of_stock'] = 1;
                    $error['out_of_stock'] = $error_flag;
                    // $errors++;
                  }
                // If customer_basket_items has total_weight entry, then we assume this IS a random weight item
                if ($total_weight != 0)
                  {
                    $old_invoice[$product_id]['total_weight'] = $total_weight;
                    $old_invoice[$product_id]['random_weight'] = 1;
                    $error['total_weight'] = $error_flag;
                    $error['random_weight'] = $error_flag;
                    // $errors++;
                  }
                // These fields must match exactly
                foreach (array ('product_name') as $field_name)
                  {
                    if ($$field_name != trim($old_invoice[$product_id][$field_name]))
                      {
                        $error[$field_name] = $error_flag;
                        $invoice[$field_name] = '<span id="invoice_'.$field_name.'" class="invoice_source">'.htmlspecialchars($old_invoice[$product_id][$field_name], ENT_QUOTES).'</span>';
                        $move_link[$field_name] = '<span id="transfer_'.$field_name.'" class="transfer" onClick="transfer_to_field(\'invoice_'.$field_name.'\',\'update_'.$field_name.'\');update_db(\''.$field_name.'\');document.getElementById(\'skip\').innerHTML=\' &nbsp; &nbsp; <br>Continue<br> &nbsp; &nbsp; \'">'.$transfer_this.'</span>';
                        $update[$field_name] = '<input size="30" id="update_'.$field_name.'" class="update_destination" value="'.htmlentities($$field_name).'" onChange="update_db(\''.$field_name.'\')">';
                        $errors ++;
                      }
                    else
                      {
                        $invoice[$field_name] = htmlspecialchars($old_invoice[$product_id][$field_name], ENT_QUOTES);
                        $move_link[$field_name] = '';
                        $update[$field_name] = htmlspecialchars($$field_name, ENT_QUOTES);
                      }
                  }
                // These fields must match numerically
                foreach (array ('product_id', 'item_price', 'quantity', 'random_weight', 'total_weight', 'taxable', 'extra_charge', 'out_of_stock') as $field_name)
                  {
                    if ($$field_name != $old_invoice[$product_id][$field_name])
                      {
                        $error[$field_name] = $error_flag;
                        $invoice[$field_name] = '<span id="invoice_'.$field_name.'" class="invoice_source">'.htmlspecialchars($old_invoice[$product_id][$field_name], ENT_QUOTES).'</span>';
                        $move_link[$field_name] = '<span id="transfer_'.$field_name.'" class="transfer" onClick="transfer_to_field(\'invoice_'.$field_name.'\',\'update_'.$field_name.'\');update_db(\''.$field_name.'\');document.getElementById(\'skip\').innerHTML=\' &nbsp; &nbsp; <br>Continue<br> &nbsp; &nbsp; \'">'.$transfer_this.'</span>';
                        $update[$field_name] = '<input size="5" id="update_'.$field_name.'" class="update_destination" value="'.htmlentities($$field_name).'" onChange="update_db(\''.$field_name.'\')">';
                        $errors ++;
                      }
                    else
                      {
                        $invoice[$field_name] = htmlspecialchars($old_invoice[$product_id][$field_name], ENT_QUOTES);
                        $move_link[$field_name] = '';
                        $update[$field_name] = htmlspecialchars($$field_name, ENT_QUOTES);
                      }
                  }
                // These fields must match with consideration for pluralization
                foreach (array ('pricing_unit', 'ordering_unit') as $field_name)
                  {
                    if ($$field_name != trim($old_invoice[$product_id][$field_name]) &&
                        Inflect::singularize ($$field_name) != trim($old_invoice[$product_id][$field_name]) &&
                        Inflect::pluralize ($$field_name) != trim($old_invoice[$product_id][$field_name]) &&
                        $$field_name.'s' != trim($old_invoice[$product_id][$field_name]))
                      {
                        $error[$field_name] = $error_flag;
                        $invoice[$field_name] = '<span id="invoice_'.$field_name.'" class="invoice_source">'.htmlspecialchars($old_invoice[$product_id][$field_name], ENT_QUOTES).'</span>';
                        $move_link[$field_name] = '<span id="transfer_'.$field_name.'" class="transfer" onClick="transfer_to_field(\'invoice_'.$field_name.'\',\'update_'.$field_name.'\');update_db(\''.$field_name.'\');document.getElementById(\'skip\').innerHTML=\' &nbsp; &nbsp; <br>Continue<br> &nbsp; &nbsp; \'">'.$transfer_this.'</span>';
                        $update[$field_name] = '<input size="15" id="update_'.$field_name.'" class="update_destination" value="'.htmlentities($$field_name).'" onChange="update_db(\''.$field_name.'\')">';
                        $errors ++;
                      }
                    else
                      {
                        $invoice[$field_name] = htmlspecialchars($old_invoice[$product_id][$field_name], ENT_QUOTES);
                        $move_link[$field_name] = '';
                        $update[$field_name] = htmlspecialchars($$field_name, ENT_QUOTES);
                      }
                  }
                if ($errors > 0)
                  {
                    // Only display this stuff if there were errors
                    // How many products in this basket? This is used to create a basket-progress bar.
                    $query_progress = '
                      SELECT
                        product_id
                      FROM
                        '.NEW_TABLE_BASKET_ITEMS.'
                      WHERE
                        basket_id = "'.mysql_real_escape_string ($basket_id).'"
                      ORDER BY
                        product_id';
                    $result_progress= mysql_query("$query_progress") or die("Error: 758394" . mysql_error());
                    $product_complete_class = ' complete';
                    $basket_progress_bar .= '
                      <ul id="product_progress">';
                    while ($row_progress = mysql_fetch_object($result_progress))
                      {
                        $basket_progress_bar .= '
                          <li class="progress_hash'.$product_complete_class.($row_progress->product_id == $product_id ? ' current' : '').'">'.$row_progress->product_id.'</li>';
                        if ($row_progress->product_id == $product_id) $product_complete_class = '';
                      }
                    $basket_progress_bar .= '
                      </ul>';
                    // Display the update table to make database corrections
                    $ajax_content .= '
                      <h2>Basket: '.$_REQUEST['basket_id'].' '.($_REQUEST['product_id'] ? $_REQUEST['product_id'] : '').'</h2>
                      <table>
                        <tbody>
                          <tr>
                            <th class="error_flag"> </th>
                            <th class="description">Field</th>
                            <th class="invoice_source">Invoice</th>
                            <th class="transfer"> </th>
                            <th class="update_destination">Database</th>
                            <td class="nav" rowspan="12">
                              <div id="skip" class="button" onClick="document.getElementById(\'pause\').checked = false;process_basket_list()">Skip<br>and<br>Continue</div>
                            </td>
                          </tr>';
                    foreach (array ('product_id', 'product_name', 'item_price', 'quantity', 'random_weight', 'total_weight', 'taxable', 'extra_charge', 'out_of_stock', 'pricing_unit', 'ordering_unit') as $field_name)
                      {
                        $ajax_content .= '
                          <tr>
                            <td class="error_flag '.$field_name.'">'.$error[$field_name].'</td>
                            <td class="description '.$field_name.'">'.ucwords (strtr ($field_name, '_', ' ')).'</td>
                            <td class="invoice_source '.$field_name.'">'.$invoice[$field_name].'</td>
                            <td class="transfer '.$field_name.'">'.$move_link[$field_name].'</td>
                            <td class="update_destination '.$field_name.'">'.$update[$field_name].'</td>
                          </tr>';
                      }
                    $ajax_content .= '
                        <tbody>
                      <table>
                    <div id="status">Status here</div>';
                    // Now send the error-correction information
//                    $ajax_output = sprintf ('[%-8s][%-8s][%-8s]%s%s', 'PAUSE', $product_id, $basket_id, $basket_progress_bar, $ajax_content);
//                    echo $ajax_output;
//                  echo 'PAUSE    '.$product_id.' '.$basket_progress_bar.$ajax_content;
                  echo str_pad('PAUSE', 10).str_pad($basket_id, 10).str_pad($product_id, 10).$basket_progress_bar.$ajax_content;
                    exit (0);
                  }
              }
            // If we finally got to the requested product_id, then reset to continue processing from here
            // But do this AFTER the product_id has been processed so we can skip without changes.
            if ($_REQUEST['product_id'] == $row->product_id)
              {
                $_REQUEST['product_id'] = 0;
              }
          } // END: basket product_id while..loop
      } // END: process_basket
    // Ajax as called, but not used, so exit with error
    exit (1);
  }




// BEGIN GENERATING MAIN PAGE //////////////////////////////////////////////////

$content .= '
  <div id="controls">
    <div id="basket_generate_start">
      <input id="delivery_generate_button" type="submit" onClick="reset_delivery_list(0); delivery_generate_start(0); generate_basket_list();" value="Begin Processing">
    </div>
    <div id="delivery_progress"><div id="c_progress-left"></div><div id="c_progress-right"></div></div>
    <div id="basket_progress"><div id="p_progress-left"></div><div id="p_progress-right"></div></div>
  </div>
<div id="reporting">
  <div id="left-column">
    <div id="customerBox">
      <div class="customerList" id="customerList">
        <ul>
          <li><div class="c_list_header c_list_cid">Del&nbsp;ID</div><div class="c_list_header c_list_name">Date [Quantity]</div></a></li>';

// Get a list of all delivery_id values
$query = '
  SELECT
    '.TABLE_ORDER_CYCLES.'.delivery_id,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    COUNT('.NEW_TABLE_BASKETS.'.basket_id) AS quantity
  FROM
    '.TABLE_ORDER_CYCLES.'
  RIGHT JOIN '.NEW_TABLE_BASKETS.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.NEW_TABLE_BASKETS.'.delivery_id
  GROUP BY '.NEW_TABLE_BASKETS.'.delivery_id';
$result= mysql_query("$query") or die("Error: 899032" . mysql_error());
$js_array_index = 0;
while($row = mysql_fetch_object($result))
  {
    $content .= '          <li id="delivery_id:'.$row->delivery_id.'" class="del_complete del_row" onClick="reset_delivery_list('.$js_array_index.'); delivery_generate_start('.$js_array_index.'); generate_basket_list();"><div class="c_list_cid">'.$row->delivery_id.'</div><div class="c_list_name">'.$row->delivery_date.' ['.$row->quantity.' Orders]</div></li>';
    $js_array_index ++; // Increment the counter -- used as the javascript index
  }

$content .= '
        </ul>
      </div>
    </div>
  </div>
  <div id="right-column">
    Pause: <input type="checkbox" id="pause" name="pause" onClick="process_basket_list()">
    Delivery: <input type="text" size="8" id="delivery_id" name="delivery_id">
    Basket: <input type="text" size="8" id="basket_id" name="basket_id">
    Product: <input type="text" size="8" id="product_id" name="product_id">
    <div id="basketBox">
      <div class="basketList" id="basketList">

  [basket information goes here]

      </div>
    </div>
  </div>
</div>
<div id="process_area" style="clear:both;">
  <div id="process_target">[process here]</div>
</div>';

$page_specific_javascript = '
    <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
    <script type="text/javascript" src="'.PATH.'validate_customer_basket_items.js"></script>';

$page_specific_css = '
    <link href="'.PATH.'validate_customer_basket_items.css" rel="stylesheet" type="text/css">';

$page_title_html = '<span class="title">Site Admin Functions</span>';
$page_subtitle_html = '<span class="subtitle">Correct Old Basket Items</span>';
$page_title = 'Site Admin Functions: Correct Old Basket Items';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
