<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer');


$producer_admin_true = 0;
if (CurrentMember::auth_type('producer_admin')) $producer_admin_true = 1;

// Check if we need to change the unlisted_producer status
if (isset ($_REQUEST['list_producer']) && $_SESSION['producer_id_you'] != '' && $_REQUEST['list_producer'] != "suspend")
  {
    if ( $_REQUEST['list_producer'] == 'relist' )
      {
        $unlisted_producer = 0;
      }
    elseif($_REQUEST['list_producer'] == "unlist")
      {
        $unlisted_producer = 1;
      }
    // Update the unlisted value but not if *suspended* (nonotlist_producer = 2)
    $sqlr = '
      UPDATE
        '.TABLE_PRODUCER.'
      SET
        unlisted_producer = "'.mysql_real_escape_string ($unlisted_producer).'"
      WHERE
        producer_id = "'.mysql_real_escape_string ($_SESSION['producer_id_you']).'"
        AND unlisted_producer != "2"';
    $resultr = @mysql_query($sqlr,$connection) or die(debug_print ("ERROR: 906897 ", array ($sqlr,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $message = "Producer # $producer_id has been updated.<br>";
  }

if ($_GET['producer_id_you'])
  {
    // Make sure we are authorized to "become" this producer
    // Either we are the member who is the producer or we are a producer admin
    $query = '
      SELECT
        1 AS authorized,
        business_name
      FROM
        '.TABLE_PRODUCER.'
      WHERE
        producer_id = "'.mysql_real_escape_string ($_GET['producer_id_you']).'"
        AND
          (
            member_id = '.mysql_real_escape_string ($_SESSION['member_id']).'
            OR '.$producer_admin_true.'
          )';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 860943 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_object($result))
      {
        if ($row->authorized == 1)
          {
            $_SESSION['producer_id_you'] = $_GET['producer_id_you'];
            $active_business_name = $row->business_name;
          }
      }
  }

// If we have reached this point without a producer_id_you, then we need to get a default one or abort...
// Make sure we are authorized to "become" this producer
// Either we are the member who is the producer or we are a producer admin
if (! $_SESSION['producer_id_you'])
  {
    $query = '
      SELECT
        producer_id,
        business_name
      FROM
        '.TABLE_PRODUCER.'
      WHERE
        member_id = '.mysql_real_escape_string ($_SESSION['member_id']).'
        OR producer_id = "'.mysql_real_escape_string ($_SESSION['producer_id_you']).'"
      ORDER BY
        business_name
      LIMIT 0,1';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 537557 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_object($result))
      {
        if ($row->producer_id)
          {
            $_SESSION['producer_id_you'] = $row->producer_id;
            $active_business_name = $row->business_name;
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
    member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
    OR '.$producer_admin_true.'
  ORDER BY
    business_name';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 897618 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_object($result) )
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
          </ul>';
      }
    elseif ($row->unlisted_producer == 2)
      {
        $producer_list = '
      <li class="suspended'.$active_class.$pending_class.'"><a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id_you='.$row->producer_id.'">'.$active_display.$pending_display.''.htmlspecialchars($row->business_name, ENT_QUOTES).'</a> (Suspended)<br>(Only a producer admin can unsuspend the producer account)</li>';
      if ($row->producer_id == $_SESSION['producer_id_you'])
        $list_status_html = '
          <ul class="fancyList1">
            '.$producer_list.'<br>
          </ul>';
      }

    // Generate two lists (one for administrators)
    if ($row->member_id == $_SESSION['member_id'])
      $owner_list .= $producer_list;

    $producer_count ++;
  }

if (! $producer_count)
  {
    // There weren't any producers listed (neither owner nor admin), so we better abort
    header("Location: index.php");
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
        <img src="'.DIR_GRAPHICS.'status.png" width="32" height="32" align="left" hspace="2" alt="Status"><br>
        <b>Status:</b> '.$list_status_html.'
        <img src="'.DIR_GRAPHICS.'producer.png" width="32" height="32" align="left" hspace="2" alt="Producer info."><br>
        <b>'.$active_business_name.' Producer Info.</b>
        <ul class="fancyList1">
          <li><a href="edit_producer_info.php">Edit Basic Producer Information</a></li>
          <li class="last_of_group"><a href="producer_form.php">Edit All Producer Information</a>'.((NEW_PRODUCER_PENDING || NEW_PRODUCER_STATUS != 0) ? ' (requires re-approval)' : '').'</li>
          <li class="last_of_group"><a href="producer_form.php?action=new_producer">New Producer Applicaton Form</a></li>
        </ul>';
  }

// If this member "owns" any producer identities, show them
$display .= '
    <img src="'.DIR_GRAPHICS.'producer3.png" width="32" height="32" align="left" hspace="2" alt="Producer identity"><br>
    <b>Producer Identity</b>
    <p>For members with multiple producer identities (multiple businesses), select below to choose which one to work with.  Then all links will use that business, identified below with an arrow .</p>
    <ul class="fancyList1">'.$owner_list.'
    </ul>';

$display .= '
      </td>
      <td align="left" width="50%">';
if ($_SESSION['producer_id_you'])
  {
    $display .= '
        <img src="'.DIR_GRAPHICS.'labels.png" width="32" height="32" align="left" hspace="2" alt="Delivery Day Functions"><br>
        <b>'.$active_business_name.' Delivery Day Functions</b>
        <ul class="fancyList1">
          <!-- <li class="last_of_group"><a href="producer_select_site.php">Select Collection Point</a></li> -->
          <li><a href="product_list.php?&type=labels_bystoragecustomer">Labels &ndash; One per Customer/Storage</a></li>
          <li class="last_of_group"><a href="product_list.php?&type=labels_byproduct">Labels &ndash; One per Item</a></li>
        </ul>
          <img src="'.DIR_GRAPHICS.'invoices.png" width="32" height="32" align="left" hspace="2" alt="Producer invoices"><br>
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
          <img src="'.DIR_GRAPHICS.'product.png" width="32" height="32" align="left" hspace="2" alt="Edit your products"><br>
          <b>Edit '.$active_business_name.' Products</b>
          <ul class="fancyList1">
            <li><a href="product_list.php?a=retail&type=producer_list">Listed&nbsp;Retail</a></li>
            <li><a href="product_list.php?a=wholesale&type=producer_list">Listed&nbsp;Wholesale</a></li>
            <li><a href="product_list.php?a=unlisted&type=producer_list">Unlisted</a></li>
            <li class="last_of_group"><a href="product_list.php?a=archived&type=producer_list">Archived</a></li>
            <li class="last_of_group"><a href="edit_products.php?producer_id='.$_SESSION['producer_id_you'].'">Add A New Product</a></li>
            <li class="last_of_group"><a href="edit_inventory.php">Manage Your Inventory</a></li>
          </ul>
          ';
  }
$display .= '
        </td>
      </tr>
    </table>';

$page_title_html = '<span class="title">'.$active_business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Producer Panel</span>';
$page_title = $active_business_name.': Producer Panel';
$page_tab = 'producer_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");