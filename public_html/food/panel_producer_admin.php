<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer_admin');


$producer_admin_true = 0;
if (CurrentMember::auth_type('producer_admin')) $producer_admin_true = 1;
else header("Location: index.php");

// Check if we need to change the unlisted_producer status
if (isset ($_REQUEST['list_producer']) && $_SESSION['producer_id_you'] != '' )
  {
    if ( $_REQUEST['list_producer'] == 'relist' )
      {
        $unlisted_producer = 0;
      }
    elseif($_REQUEST['list_producer'] == "unlist")
      {
        $unlisted_producer = 1;
      }
    elseif($_REQUEST['list_producer'] == "suspend")
      {
        $unlisted_producer = 2;
      }
    $sqlr = '
      UPDATE
        '.TABLE_PRODUCER.'
      SET
        unlisted_producer = "'.mysqli_real_escape_string ($connection, $unlisted_producer).'"
      WHERE
        producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'"';
    $resultr = @mysqli_query ($connection, $sqlr) or die (debug_print ("ERROR: 904933 ", array ($sqlr, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $message = 'Producer # '.$producer_id.' has been updated.<br>';
  }

if ($_GET['producer_id_you'])
  {
    // Make sure we are authorized to "become" this producer
    // Either we are the member who is the producer or we are a producer admin
    $query = '
      SELECT
        business_name
      FROM
        '.TABLE_PRODUCER.'
      WHERE
        producer_id = "'.mysqli_real_escape_string ($connection, $_GET['producer_id_you']).'"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 830943 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_object($result))
      {
            $_SESSION['producer_id_you'] = $_GET['producer_id_you'];
            $active_business_name = $row->business_name;
          }
      }

// // If we have reached this point without a producer_id_you, then we need to get a default one or abort...
// // Make sure we are authorized to "become" this producer
// // Either we are the member who is the producer or we are a producer admin
// if (! $_SESSION['producer_id_you'])
//   {
//     $query = '
//       SELECT
//         producer_id,
//         business_name
//       FROM
//         '.TABLE_PRODUCER.'
//       WHERE
//         member_id = '.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'
//         OR producer_id = "'.mysqli_real_escape_string ($connection, $_SESSION['producer_id_you']).'"
//       ORDER BY
//         business_name
//       LIMIT 0,1';
//     $result = @mysqli_query($connection, $query) or die (debug_print ("ERROR: 537537 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
//     while ($row = mysqli_fetch_object ($result))
//       {
//         if ($row->producer_id)
//           {
//             $_SESSION['producer_id_you'] = $row->producer_id;
//             $active_business_name = $row->business_name;
//           }
//       }
//   }

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
$result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 259326 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysqli_fetch_object ($result) )
  {
    $pending_producer = $row->pending_producer;
    // Preset/clear variables
    $active_class = '';
    $active_display = '';
    $pending_class = '';
    $pending_display = '';

    if ($row->pending_producer == 1)
      {
        $pending_class = ' pending';
        $pending_display = '[PENDING] ';
      }
    if ($row->producer_id == $_SESSION['producer_id_you'])
      {
        $active_class = ' current';
        $active_display = '';
        $active_business_name = $row->business_name;
      }

    if ($row->unlisted_producer == 0)
      {
        $producer_list = '
      <li class="listed'.$active_class.$pending_class.'"><a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id_you='.$row->producer_id.'">'.$active_display.$pending_display.htmlspecialchars($row->business_name, ENT_QUOTES).'</a> (Listed)</li>';
      if ($row->producer_id == $_SESSION['producer_id_you'])
        $list_status_html = '
          <ul class="fancyList1">
            '.$producer_list.'<br>
            <li class="unlisted"><a href="'.$_SERVER['SCRIPT_NAME'].'?list_producer=unlist">Unlist '.$active_business_name.'</a><br>(Temporarily remove all '.$active_business_name.' products from the shopping lists)</li><br>
            <li class="suspended last_of_group"><a href="'.$_SERVER['SCRIPT_NAME'].'?list_producer=suspend">Suspend '.$active_business_name.'</a><br>(Temporarily remove '.$active_business_name.' from all producer lists and all '.$active_business_name.' products from the shopping lists)</li>
          </ul>';
      }
    elseif ($row->unlisted_producer == 1)
      {
        $producer_list = '
      <li class="unlisted'.$active_class.$pending_class.'"><a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id_you='.$row->producer_id.'">'.$active_display.$pending_display.htmlspecialchars($row->business_name, ENT_QUOTES).'</a> (Unlisted)</li>';
      if ($row->producer_id == $_SESSION['producer_id_you'])
        $list_status_html = '
          <ul class="fancyList1">
            '.$producer_list.'<br>
            <li class="listed"><a href="'.$_SERVER['SCRIPT_NAME'].'?list_producer=relist">Relist '.$active_business_name.'</a><br>(Make retail and wholesale products available.  This does not change the status of products that are unlisted or archived)</li><br>
            <li class="suspended last_of_group"><a href="'.$_SERVER['SCRIPT_NAME'].'?list_producer=suspend">Suspend '.$active_business_name.'</a><br>(Temporarily remove '.$active_business_name.' from all producer lists and all '.$active_business_name.' products from the shopping lists)</li>
          </ul>';
      }
    elseif ($row->unlisted_producer == 2)
      {
        $producer_list = '
      <li class="suspended'.$active_class.$pending_class.'"><a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id_you='.$row->producer_id.'">'.$active_display.$pending_display.''.htmlspecialchars($row->business_name, ENT_QUOTES).'</a> (Suspended)</li>';
      if ($row->producer_id == $_SESSION['producer_id_you'])
        $list_status_html = '
          <ul class="fancyList1">
            '.$producer_list.'<br>
            <li class="unlisted"><a href="'.$_SERVER['SCRIPT_NAME'].'?list_producer=unlist">Unsuspend '.$active_business_name.'</a><br>(Temporarily remove all '.$active_business_name.' products from the shopping lists)</li><br>
            <li class="listed last_of_group"><a href="'.$_SERVER['SCRIPT_NAME'].'?list_producer=relist">Relist '.$active_business_name.'</a><br>(Make retail and wholesale products available.  This does not change the status of products that are unlisted or archived)</li>
          </ul>';
      }

    if ($producer_admin_true)
      $admin_list .= $producer_list;

    $producer_count ++;
  }





/////////////// FINISH PRE-PROCESSING AND BEGIN PAGE GENERATION /////////////////




// Generate the display output
$display .= '
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">';

// If there is a current producer_id_you, then show the current status
if ($list_status_html)
  {
    $display .= '
        <img src="'.DIR_GRAPHICS.'status.png" width="32" height="32" align="left" hspace="2" alt="Status">
        <b>Selected Producer Status:</b> '.$list_status_html.'
        <img src="'.DIR_GRAPHICS.'producer.png" width="32" height="32" align="left" hspace="2" alt="Producer info.">
        <b>'.$active_business_name.' Producer Info.</b>
        <ul class="fancyList1">
          <li><a href="edit_producer_info.php">Edit Basic Producer Information</a></li>
          <li class="last_of_group"><a href="producer_form.php">Edit All Producer Information</a>'.((NEW_PRODUCER_PENDING || NEW_PRODUCER_STATUS != 0) ? ' (requires re-approval)' : '').'</li>
        </ul>';
  }

// Generate the administrative display list of producers
$display .= '
    <img src="'.DIR_GRAPHICS.'producer3.png" width="32" height="32" align="left" hspace="2" alt="Select producer identity">
    <b>ADMIN: Select Producer Identity</b>
    <p>Producer administrators may choose an identity below to edit on behalf of the producer.</p>
    <div class="height_400">
    <ul class="fancyList1">'.$admin_list.'
    </ul>
    </div>';

$display .= '
      </td>
      <td align="left" width="50%">';
if ($_SESSION['producer_id_you'])
  {
    $display .= '
      <div id="this_producer">
        <div class="title">Links for '.$active_business_name.'</div>
        <img src="'.DIR_GRAPHICS.'labels.png" width="32" height="32" align="left" hspace="2" alt="Delivery Day Functions">
        <b>'.$active_business_name.' Delivery Day Functions</b>
        <ul class="fancyList1">
          <!-- <li class="last_of_group"><a href="producer_select_site.php">Select Collection Point</a></li> -->
          <li><a href="product_list.php?&type=labels_bystoragecustomer">Labels &ndash; One per Customer/Storage</a></li>
          <li class="last_of_group"><a href="product_list.php?&type=labels_byproduct">Labels &ndash; One per Item</a></li>
        </ul>
          <img src="'.DIR_GRAPHICS.'invoices.png" width="32" height="32" align="left" hspace="2" alt="Producer invoices">
          <b>'.$active_business_name.' Producer Orders</b>
          <ul class="fancyList1">
            <li><a href="product_list.php?&type=producer_byproduct">Producer Basket (by product)</a></li>
            <li><a href="product_list.php?&type=producer_bystoragecustomer">Producer Basket List (by storage/customer)</a></li>
            <li class="last_of_group"><a href="product_list.php?&type=producer_bycustomer">Producer Basket List (by customer)</a></li>
            <li><a href="order_summary.php">Order Summary</a></li>
            <li><a href="show_report.php?type=producer_invoice">Producer Invoice</a></li>
            <li class="last_of_group"><a href="past_producer_invoices.php?producer_id='.$_SESSION['producer_id_you'].'">Past Producer Invoices</a></li>
            <li><a href="route_list.php?delivery_id='.ActiveCycle::delivery_id().'&type=pickup&producer_id='.$_SESSION['producer_id_you'].'">Routing Checklist (by customer)</a></li>
            <li class="last_of_group"><a href="route_list.php?delivery_id='.ActiveCycle::delivery_id().'&type=dropoff&producer_id='.$_SESSION['producer_id_you'].'">Routing Checklist (by destination)</a></li>
          </ul>
          <img src="'.DIR_GRAPHICS.'product.png" width="32" height="32" align="left" hspace="2" alt="Edit your products">
          <b>Edit '.$active_business_name.' Products</b>
          <ul class="fancyList1">
            <li><a href="product_list.php?a=retail&type=producer_list">Listed&nbsp;Retail</a></li>
            <li><a href="product_list.php?a=wholesale&type=producer_list">Listed&nbsp;Wholesale</a></li>
            <li><a href="product_list.php?a=unfi&type=producer_list">Listed&nbsp;UNFI</a></li>
            <li><a href="product_list.php?a=unlisted&type=producer_list">Unlisted</a></li>
            <li class="last_of_group"><a href="product_list.php?a=archived&type=producer_list">Archived</a></li>
            <li class="last_of_group"><a href="edit_products.php?producer_id='.$_SESSION['producer_id_you'].'">Add A New Product</a></li>
            <li class="last_of_group"><a href="edit_inventory.php">Manage Inventory</a></li>
          </ul>
        </div>';
  }
$display .= '
      <div id="all_producers">
        <div class="title">General producer links</div>
          <img src="'.DIR_GRAPHICS.'producers.png" width="32" height="32" align="left" hspace="2" alt="Food Stamps">
          <b>Manage Producers and Products</b>
          <ul class="fancyList1">
            <!-- <li><a href="edit_prdcr_list.php">Producer/Product List</a></li> -->
            <li><a href="product_list.php?type=confirm">Confirm Changed Products</a></li>
            <li><a href="edit_info_list.php">Edit Producer Info</a></li>
          </ul>
          <img src="'.DIR_GRAPHICS.'kcron.png" width="32" height="32" align="left" hspace="2" alt="Delivery Cycle Functions">
          <b>Delivery Cycle Functions</b>
          <ul class="fancyList1">
            <li class="last_of_group"><a href="orders_prdcr_list.php?delivery_id='.ActiveCycle::delivery_id().'">Producers with Customers this Cycle</a></li>
          </ul>
          <img src="'.DIR_GRAPHICS.'bottom.png" width="32" height="32" align="left" hspace="2" alt="Producer Membership Information">
          <b>Producer Membership Information</b>
          <ul class="fancyList1">
            <li><a href="producers_pending.php">Pending Producers</a></li>
            <li class="last_of_group"><a href="producer_applications.php">Producer Applications</a></li>
          </ul>
          <img src="'.DIR_GRAPHICS.'report.png" width="32" height="32" align="left" hspace="2" alt="Manage products">
          <b>Reports</b>
          <ul class="fancyList1">
            <li> <a href="producers_labels.php">All Producers</a></li>
            <li><a href="prdcr_list_email.php">Producer Email Addresses</a></li>
            <li><a href="coopproducers.php">Producer Contact Info</a></li>
            <li class="last_of_group"> <a href="report_members.php?p=1">Download a Spreadsheet of All Members/Producers</a></li>
          </ul>
        </div>
        </td>
      </tr>
    </table>';


$page_specific_css .= '
<style type="text/css">
#this_producer {
  border:3px solid #ddd;
  padding:0.5em;
  }
#all_producers {
  margin-top:1em;
  border:3px solid #ddd;
  padding:0.5em;
  }
li.listed a {
  }
li.unlisted a {
  color:#888;
  }
li.suspended a {
  color:#900;
  }
.height_400 {
  height:400px;
  width:90%;
  overflow:scroll;
  border:1px solid black;
  }
</style>';


$page_title_html = '<span class="title">'.$active_business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Producer Admin Panel</span>';
$page_title = $active_business_name.': Producer Admin Panel';
$page_tab = 'producer_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
