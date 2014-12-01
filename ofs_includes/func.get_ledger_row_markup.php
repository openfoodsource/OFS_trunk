<?php

// Keep this function on this page so header and contents can be kept in sync
function get_ledger_header_markup ()
  {
    $response = '
      <tr class="ledger_header">
        <th></th>
        <th>Invoice No.</th>
        <th>Date / Time</th>
        <th>To / From</th>
        <th>For</th>
        <th>Qty</th>
        <th>Description</th>
        <th></th>
        <th>Amt.</th>
        <th>Bal.</th>
      </tr>';
    return ($response);
  }

// A common function to generate the HTML for ledger display rows...
function get_ledger_row_markup ($transaction_data, $running_total, $row_type)
  {
    // Set up the unique row ID
    if ($transaction_data['unique_row_id'])
      {
        $unique_row_id = $transaction_data['unique_row_id'];
      }
    else
      {
        $unique_row_id = 'tid_'.$transaction_data['transaction_id'];
        // For detail rows in unlocked baskets, provide action linkages
        if (! $transaction_data['locked'])
          {
            $row_click_script = '<img class="control" src="'.DIR_GRAPHICS.'edit_icon.png" onclick="row_click('.
              $transaction_data['transaction_id'].','.
              $transaction_data['bpid'].')">';
          }
      }
    // $row_type should be [singleton|summary|detail]
    if ($row_type == 'singleton')
      {
        if (is_numeric ($running_total)) $running_total = number_format ($running_total, 2); // Show running total
        $hide_class = ''; // Not hidden
        $more_symbol = '';
        $more_less_script = '';
      }
    elseif ($row_type == 'summary')
      {
        $running_total = number_format ($running_total, 2); // Show running total
        $hide_class = ''; // Not hidden
        $more_symbol = 'more';
        $more_less_script = '<span class="more_less" onclick="this.innerHTML=show_hide_detail(\''.$transaction_data['detail_group'].'\',this.innerHTML)">'.$more_symbol.'</span>';
      }
    elseif ($row_type == 'detail')
      {
        $running_total = ''; // No running total
        $hide_class = 'hid ';
        $more_symbol = '';
        $more_less_script = '';
      }
    else // this is the 'normal' display
      {
        $running_total = number_format ($running_total, 2); // Show running total
        $hide_class = '';
        $more_symbol = '';
        $more_less_script = '';
      }


    $response = '
      <tr id="'.$unique_row_id.'" class="'.$hide_class.$transaction_data['css_class'].$transaction_data['display_class'].'">
        <td class="control">'.$row_click_script.'</td>
        <td class="scope">'.$transaction_data['display_scope'].'<br>['.$transaction_data['css_class'].':'.$transaction_data['display_class'].']</td>
        <td class="timestamp">'.$transaction_data['timestamp'].'</td>
        <td class="from_to">'.$transaction_data['display_to_from'].'</td>
        <td class="text_key">'.$transaction_data['text_key'].'</td>
        <td class="quantity">'.$transaction_data['display_quantity'].'</td>
        <td class="detail'.$transaction_data['special'].'">'.$transaction_data['detail'].'</td>
        <td class="more_less">'.$more_less_script.'</td>
        <td class="amount">'.number_format ($transaction_data['amount'], 2).'</td>
        <td class="'.$hide_class.'balance">'.$running_total.'&nbsp;</td>
      </tr>';
    return ($response);
  }
?>