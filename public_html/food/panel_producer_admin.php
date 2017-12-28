<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer_admin');

$producer_admin_true = 0;
if (CurrentMember::auth_type('producer_admin')) $producer_admin_true = 1;
else header("Location: index.php");

// Check if we need to change the unlisted_producer status
if (isset ($_POST['select_status']) && $_SESSION['producer_id_you'] != '' )
  {
    if ( $_POST['select_status'] == 'listed' )
      {
        $unlisted_producer = 0;
      }
    elseif($_POST['select_status'] == "unlisted")
      {
        $unlisted_producer = 1;
      }
    elseif($_POST['select_status'] == "suspended")
      {
        $unlisted_producer = 2;
      }
    if ($_POST['select_pending'] == "pending")
      {
        $pending_producer = 1;
      }
    else
      {
        $pending_producer = 0;
      }
    $query = '
      UPDATE
        '.TABLE_PRODUCER.'
      SET
        unlisted_producer = "'.mysqli_real_escape_string ($connection, $unlisted_producer).'",
        pending = "'.mysqli_real_escape_string ($connection, $pending_producer).'"
      WHERE
        producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'"';
    $resultr = @mysqli_query ($connection, $sqlr) or die (debug_print ("ERROR: 904933 ", array ($sqlr, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $message = 'Producer # '.$producer_id.' has been updated.<br>';
  }

if ($_POST['select_producer'])
  {
    // Make sure we are authorized to "become" this producer
    // Either we are the member who is the producer or we are a producer admin
    $query = '
      SELECT
        business_name,
        producer_id
      FROM
        '.TABLE_PRODUCER.'
      WHERE
        producer_id = "'.mysqli_real_escape_string ($connection, $_POST['select_producer']).'"';
    $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 830943 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_object ($result))
      {
        $_SESSION['producer_id_you'] = $row->producer_id;
        $_SESSION['producer_business_name'] = $row->business_name;
        // If switching to a new producer, then refresh the producer_delivery_id_array
        $query_order_cycles = '
          SELECT
            DISTINCT(delivery_id) AS delivery_id,
            delivery_date
          FROM '.NEW_TABLE_BASKETS.'
          LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING (basket_id)
          LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING (product_id, product_version)
          LEFT JOIN '.TABLE_ORDER_CYCLES.' USING (delivery_id)
          WHERE producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'"';
        $result = @mysqli_query ($connection, $query_order_cycles) or die(debug_print ("ERROR: 742902 ", array ($query_order_cycles,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
          {
            $_SESSION['producer_delivery_id_array'][$row['delivery_id']] = $row['delivery_date'];
          }
      }
  }

// Get a list of all the producer_id values for this member
$query = '
  SELECT
    member_id,
    producer_id,
    business_name,
    pending AS pending_producer,
    unlisted_producer
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    1
  ORDER BY
    unlisted_producer,
    business_name';
$result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 759326 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysqli_fetch_object ($result) )
  {
$producer_count = 0;
    $pending_class = '';
    $listed_class = '';
    if ($row->pending_producer == 1) $pending_class = 'pending';
    if ($row->unlisted_producer == 0) $listed_class = 'listed';
    if ($row->unlisted_producer == 1) $listed_class = 'unlisted';
    if ($row->unlisted_producer == 2) $listed_class = 'suspended';
    // Add the producer to the producer selections
    $producer_select_list .= '
      <input class="select_type_radio" id="select_producer-'.$row->producer_id.'" name="select_producer" value="'.$row->producer_id.'" onchange="document.forms[\'select_producer\'].submit();" type="radio"'.($row->producer_id == $_SESSION['producer_id_you'] ? ' checked' : '').'>
      <label class="block_31 '.$listed_class.'" for="select_producer-'.$row->producer_id.'">'.$row->business_name.'</label>';
    // Build the status options for this (producer_id_you) producer
    if ($row->producer_id == $_SESSION['producer_id_you'])
      {
        $producer_select_status = '
          <form id="select_status" class="" name="select_status" action="'.$_SERVER['SCRIPT_NAME'].'" method="POST">
            <!-- BUTTON FOR PENDING/UNPENDING A PRODUCER -->
            <input id="select_status_pending_target" class="select_type_checkbox" name="select_pending" value="pending" onchange="document.forms[\'select_status\'].submit();" type="checkbox"'.($pending_class == 'pending' ? ' checked' : '').'>
            <label id="select_status_pending" class="block_22 pending disabled" for="select_status_pending_target-DISABLED" onclick="interrupt_radio_check_confirm(this.id,\'ask_confirm\',6)">
              <span class="default">'.($pending_class == 'pending' ? 'PENDING<span class="detail">Make active' : 'ACTIVE<span class="detail">Make Pending').'</span></span>
              <span class="confirm">Confirm<span class="count"id="select_status_pending_count">&nbsp;</span></span>
            </label>
            <!-- BUTTONS FOR LISTING/UNLISTING/SUSPENDING A PRODUCER -->
            <span class="block block_42">'.$row->business_name.'</span>
            <input id="select_status_listed_target" class="select_type_radio" name="select_status" value="listed" onchange="document.forms[\'select_status\'].submit();" type="radio"'.($listed_class == 'listed' ? ' checked' : '').'>
            <label id="select_status_listed" class="block_22 listed disabled" for="select_status_listed_target-DISABLED" onclick="interrupt_radio_check_confirm(this.id,\'ask_confirm\',6)">
              <span class="default">'.($listed_class == 'listed' ? 'Producer is LISTED' : 'Relist Producer').'<span class="detail"></span></span>
              <span class="confirm">Confirm<span class="count"id="select_status_listed_count">&nbsp;</span></span>
            </label>
            <input id="select_status_unlisted_target" class="select_type_radio" name="select_status" value="unlisted" onchange="document.forms[\'select_status\'].submit();" type="radio"'.($listed_class == 'unlisted' ? ' checked' : '').'>
            <label id="select_status_unlisted" class="block_22 unlisted disabled" for="select_status_unlisted_target-DISABLED" onclick="interrupt_radio_check_confirm(this.id,\'ask_confirm\',6)">
              <span class="default">'.($listed_class == 'unlisted' ? 'Producer and Products are UNLISTED' : 'Unlist Producer').'<span class="detail"></span></span>
              <span class="confirm">Confirm<span class="count"id="select_status_unlisted_count">&nbsp;</span></span>
            </label>
            <input id="select_status_suspended_target" class="select_type_radio" name="select_status" value="suspended" onchange="document.forms[\'select_status\'].submit();" type="radio"'.($listed_class == 'suspended' ? ' checked' : '').'>
            <label id="select_status_suspended" class="block_22 suspended disabled" for="select_status_suspended_target-DISABLED" onclick="interrupt_radio_check_confirm(this.id,\'ask_confirm\',6)">
              <span class="default">'.($listed_class == 'suspended' ? 'Producer is SUSPENDED' : 'Suspend Producer').'<span class="detail"></span></span>
              <span class="confirm">Confirm<span class="count"id="select_status_suspended_count">&nbsp;</span></span>
            </label>
          </form>';
      }
    $producer_count ++;
  }

/////////////// FINISH PRE-PROCESSING AND BEGIN PAGE GENERATION /////////////////

// Generate the display output
$display .= '
  <div class="subpanel producer_status">
    <header>
      Producer Status
    </header>'.
    $producer_select_status.'
    <ul id="edit_producer_info" class="grid edit_producer_info">
      <li class="block block_33"><a href="edit_producer_info.php">Edit Basic Producer Information</a></li>
      <li class="block block_33"><a href="producer_form.php">Edit All Producer Information<span class="detail">'.((NEW_PRODUCER_PENDING || NEW_PRODUCER_STATUS != 0) ? ' (will require re-approval)' : '').'</span></a></li>'.
      (USE_AVAILABILITY_MATRIX == true ? '
      <li class="block block_33"><a class="popup_link" onClick="popup_src(\'producer_select_site.php?producer_id='.$_SESSION['producer_id_you'].'&display_as=popup\', \'producer_select_site\', \'\');">Select Collection Point(s)</a></li>'
      : '' ).'
    </ul>
  </div>
  <div class="subpanel marketplace_functions">
    <header>
      Marketplace Functions
    </header>
    <ul id="marketplace_functions" class="grid marketplace_functions">
      <li class="block block_33"><a href="product_list.php?&type=labels">Labels<span class="detail">(print for packaging)</span></a></li>
      <li class="block block_33"><a href="product_list.php?&type=producer_basket">Current Basket<span class="detail">(fill orders)</span></a></li>
      <li class="block block_33"><a href="order_summary.php">Order Summary</a></li>
      <li class="block block_33"><a href="show_report.php?type=producer_invoice">Current Invoice</a></li>
      <li class="block block_33"><a class="block_link popup_link" onClick="popup_src(\''.PATH.'order_history_popup.php?history_type=producer\', \'select_order_history\', \'\');">Past Baskets &amp; Invoices</a></li>
      <li class="block block_33"><a href="route_list.php?delivery_id='.ActiveCycle::delivery_id().'&type=pickup&producer_id='.$_SESSION['producer_id_you'].'">Routing Checklist<span class="detail">(by customer)</span></a></li>
      <li class="block block_33"><a href="route_list.php?delivery_id='.ActiveCycle::delivery_id().'&type=dropoff&producer_id='.$_SESSION['producer_id_you'].'">Routing Checklist<span class="detail">(by destination)</span></a></li>
      <li class="block block_33"><a href="product_list.php?type=producer_list&select_type=for_sale">My Products</a></li>
      <li class="block block_33"><a class="popup_link" onClick="popup_src(\''.PATH.'edit_product_info.php?action=add&producer_id='.$_SESSION['producer_id_you'].'&display_as=popup\', \'edit_product_info\', \'\');">Create New Product</a></li>
      <li class="block block_33"><a href="product_list.php?&type=inventory_list">Manage Inventory</a></li>
    </ul>
  </div>
  <div class="subpanel producer_admin_functions">
    <header>
      Administrative Functions
    </header>
    <ul id="admin_functions" class="grid admin_functions">
      <li class="block block_33"><a href="orders_prdcr_list.php?delivery_id='.ActiveCycle::delivery_id().'">Producers with Customers this Cycle</a></li>
      <li class="block block_33"><a href="producer_select_site.php">Collection Points for ALL producers</a></li>
      <li class="block block_33"><a href="product_list.php?type=producer_list&select_type=admin_unconfirmed">Confirm Changed Products</a></li>
      <li class="block block_33"><a href="'.PATH.'producer_list_withtotals.php?delivery_id='.ActiveCycle::delivery_id().'">Producer Orders with Totals</a></li>
      <li class="block block_33"><a href="producers_pending.php">Pending Producers</a></li>
      <li class="block block_33"><a href="producer_applications.php">Producer Applications</a></li>
      <li class="block block_33"><a href="producers_labels.php">All Producers</a></li>
      <li class="block block_33"><a href="prdcr_list_email.php">Producer Email Addresses</a></li>
      <li class="block block_33"><a href="coopproducers.php">Producer Contact Info</a></li>
      <li class="block block_33"><a href="report_members.php?p=1">Download a Spreadsheet of All Producers</a></li>
    </ul>
  </div>
  <div class="subpanel producer_select_list">
    <header>
      Select Other Producer
    </header>
    <div id="producer_select_list">
      <form id="select_producer" class="" name="select_producer" action="'.$_SERVER['SCRIPT_NAME'].'" method="POST">
          <!-- BUTTONS FOR SELECTING A PRODUCER -->'.
            $producer_select_list.'
      </form>
    </div>
  </div>';

$page_specific_javascript = '
  // Create two debounced versions for radio_check_confirm
  // interrupt... is quick, just to prevent clicks from passing through to the form element
  // countdown... is one-second to re-disable the form element after a short period of time
  // NOTE: debounce() is located in javascript.js
  var interrupt_radio_check_confirm = debounce(function(id, action, count) {
    radio_check_confirm(id, action, count);
    }, 50);
  var countdown_radio_check_confirm = debounce(function(id, action, count) {
    radio_check_confirm(id, action, count);
    }, 1000);
  function radio_check_confirm(id, action, count) {
    if (action == "ask_confirm") {
      $("#"+id).removeClass("disabled");
      $("#"+id).attr("for", id+"_target");
      countdown_radio_check_confirm(id, "countdown", count - 1);
      }
    else if (action == "countdown") {
      $("#"+id+"_count").html(count);
      if (count > 0 && count <= 10) {
        countdown_radio_check_confirm(id, "countdown", count - 1);
        }
      else {
        radio_check_confirm(id, "reset_confirm", 0);
        }
      }
    else if (action == "reset_confirm") {
      $("#"+id+"_count").html("");
      $("#"+id).addClass("disabled");
      $("#"+id).attr("for", id+"_target-DISABLED");
      }
    }';


$page_specific_css .= '
  .producer_status header {
    background-image:url("'.DIR_GRAPHICS.'status.png");
    }
  .marketplace_functions header {
    background-image:url("'.DIR_GRAPHICS.'product.png");
    }
  .producer_admin_functions header {
    background-image:url("'.DIR_GRAPHICS.'invoices.png");
    }
  .producer_select_list header {
    background-image:url("'.DIR_GRAPHICS.'producers.png");
    }
  .select_type_checkbox,
  .select_type_radio {
    display:none;
    }
  #edit_producer_info,
  #select_status,
  #select_producer {
    list-style-type: none;
    display:flex;
    flex-wrap:wrap;
    text-align:center;
    justify-content:center;
    margin:-0.5rem 0 0 -0.5rem;
    }
  #edit_producer_info {
    margin-top:0;
    }
  .select_type_checkbox + label,
  .select_type_radio + label {
    display:flex;
    text-align:center;
    vertical-align:middle;
    border:1px solid #888;
    margin:0.5rem 0 0 0.5rem;
    border-radius:0.5rem;
    flex-direction:column;
    justify-content:center;
    font-size:60%;
    color:#000;
    padding:0.25rem;
    cursor:pointer;
    }
  .select_type_checkbox + label.listed {
    background-color:#ddd;
    }
  .select_type_radio + label.listed {
    background-color:#afa;
    }
  .select_type_radio + label.unlisted {
    background-color:#ffa;
    }
  .select_type_radio + label.suspended {
    background-color:#faa;
    }
  #select_status_pending_target.select_type_checkbox:checked + label {
    background-color:#444;
    color:#fff;
    border-width:2px;
    border-color:#000;
    }
  .select_type_radio:checked + label {
    background-color:#fff;
    border-width:2px;
    border-color:#000;
    cursor:auto;
    }
  #select_status .select_type_checkbox + label,
  #select_status .select_type_radio + label {
    font-size:80%;
    }
  /* Styles for radio/checkbox confirmations */
  label .default {
    display:none;
    }
  label .confirm {
    display:inline;
    }
  label .confirm .count {
    display:block;
    font-size:150%;
    }
  label.disabled .confirm {
    display:none;
    }
  label.disabled .default {
    display:inline;
    }
  label .detail:before { /* Create a line-break before .detail */
    content:"\A";
    white-space:pre;
    }';

$page_title_html = '<span class="title">'.$_SESSION['producer_business_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Producer Admin Panel</span>';
$page_title = $_SESSION['producer_business_name'].': Producer Admin Panel';
$page_tab = 'producer_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
