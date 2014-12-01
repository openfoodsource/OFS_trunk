<?php
include_once 'config_openfood.php';
session_start();

// Items dependent upon the location of this header
$pager = array();

// Set up some variables that might be needed
if (isset ($_SESSION['member_id'])) $member_id = $_SESSION['member_id'];
if (isset ($_SESSION['producer_id_you'])) $producer_id_you = $_SESSION['producer_id_you'];
$delivery_id = mysql_real_escape_string (ActiveCycle::delivery_id());

// Allow cashier to override member_id
if (isset ($_GET['member_id']) && CurrentMember::auth_type('cashier'))
  $member_id = $_GET['member_id'];
// Allow producer_admin or cashier to override producer_id_you
if (isset ($_GET['producer_id']) && CurrentMember::auth_type('cashier,producer_admin'))
  $producer_id_you = $_GET['producer_id'];
// Allow anyone to override the delivery_id
if ($_GET['delivery_id'])
  $delivery_id = mysql_real_escape_string ($_GET['delivery_id']);

// Initialize display of wholesale and retail to false
$wholesale_member = false;
$retail_member = false;

//////////////////////////////////////////////////////////////////////////////////////
//                                                                                  //
//                         QUERY AND DISPLAY THE DATA                               //
//                                                                                  //
//////////////////////////////////////////////////////////////////////////////////////

// Include the appropriate list "module" from the show_report directory
$report_type = $_GET['type'];
if (! isset ($report_type)) $report_type = 'customer_invoice';
include_once ('show_report/'.$report_type.'.php');
// Now include the template (specified in the include_file)
include_once ('show_report/'.$report_type.'_template.php');

// // This setting might be overridden below or in included files
// $pager['per_page'] = PER_PAGE;
// // Labels do not have pages
// if ($template_type == 'labels') $pager['per_page'] = 1000000;
// // Set up the pager for the output
// $list_start = ($_GET['page'] - 1) * $pager['per_page'];
// if ($list_start < 0) $list_start = 0;
// $query_limit = $list_start.', '.$pager['per_page'];
// 
// // Add limits to the query
// $query .= '
//   LIMIT '.$query_limit;

// $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 785033 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// // Get the total number of rows (for pagination) -- not counting the LIMIT condition
// $query_found_rows = '
//   SELECT
//     FOUND_ROWS() AS found_rows';
// $result_found_rows = @mysql_query($query_found_rows, $connection) or die(debug_print ("ERROR: 860342 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// // Handle pagination for multi-page results
// $row_found_rows = mysql_fetch_array($result_found_rows);
// $pager['found_rows'] = $row_found_rows['found_rows'];
// if ($_GET['page']) $pager['this_page'] = $_GET['page'];
// else $pager['this_page'] = 1;
// $pager['last_page'] = ceil (($pager['found_rows'] / $pager['per_page']) - 0.00001);
// $pager['page'] = 0;
// while (++$pager['page'] <= $pager['last_page'])
//   {
//     if ($pager['page'] == $pager['this_page']) $pager['this_page_true'] = true;
//     else $pager['this_page_true'] = false;
//     $pager['display'] .= pager_display_calc($pager);
//   }
// $pager_navigation_display = pager_navigation($pager);





// Assign some additional unique_data values
$unique_data['major_product'] = $major_product;
$unique_data['major_product_prior'] = $major_product_prior;
$unique_data['minor_product'] = $minor_product;
$unique_data['minor_product_prior'] = $minor_product_prior;
$unique_data['show_major_product'] = $show_major_product;
$unique_data['show_minor_product'] = $show_minor_product;

// Begin with the product results
$result_product = mysql_query($query_product, $connection) or die(debug_print ("ERROR: 752932 ", array ($query_product,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$number_of_rows = mysql_num_rows ($result_product);
$this_row = 0;
// Load the data structure with all the values
while ($row_product = mysql_fetch_array ($result_product))
  {
    $product_data[++ $this_row] = (array) $row_product;
  }
// Some generalized product_data values
$product_data['row_type'] = $row_type;
$product_data['number_of_rows'] = $number_of_rows;

// Send the page start
$display = open_list_top($product_data, $unique_data);

// Start over and cycle through the data
$this_row = 0;
while ($this_row ++ < $number_of_rows)
  {
    $product_data['this_row'] = $this_row;

    // Grab the *unique* producer_fee_percent from the product data (otherwise not available)
    if (isset ($product_data[$this_row]['producer_fee_percent']))
      {
        // NOTE this will give bogus values if the producer_fee_percent is not the same for
        // every product during the ordering cycle
        $unique_data['producer_fee_percent'] = $product_data[$this_row]['producer_fee_percent'];
      }

    $product_data[$this_row]['random_weight_display'] = random_weight_display_calc($product_data, $unique_data);
    $product_data[$this_row]['business_name_display'] = business_name_display_calc($product_data, $unique_data);
    $product_data[$this_row]['pricing_display'] = pricing_display_calc($product_data, $unique_data);
    // $product_data['total_display'] = total_display_calc($product_data);
    $product_data[$this_row]['total_display'] = $product_data['amount'];
    $product_data[$this_row]['inventory_display'] = inventory_display_calc($product_data, $unique_data);

    // New major division
    if ($product_data[$this_row][$major_product] != $product_data[$this_row - 1][$major_product] && $show_major_product == true)
      {
        if ($listing_is_open)
          {
            if ($show_minor_product) $display .= minor_product_close($product_data, $unique_data);
            $display .= major_product_close($product_data, $unique_data);
            $listing_is_open = 0;
          }
        $display .= major_product_open($product_data, $unique_data);
        // New major division will force a new minor division
        $$minor_product_prior = -1;
      }

    // New minor division
    // We will aggregate everything in a minor division into a single row for display
    if ($product_data[$this_row][$minor_product] != $product_data[$this_row - 1][$minor_product] && $show_minor_product == true)
      {
        if ($listing_is_open)
          {
            $display .= minor_product_close($product_data, $unique_data);
            $listing_is_open = 0;
          }
        $display .= minor_product_open($product_data, $unique_data);
      }
    $listing_is_open = 1;
    $display .= show_product_row($product_data, $unique_data);
  }
// Close minor
if ($show_minor_product) $display .= minor_product_close($product_data, $unique_data);
// Close major
if ($show_major_product) $display .= major_product_close($product_data, $unique_data);



//////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////




// Load the data structure with all the values
$result_adjustment = mysql_query($query_adjustment, $connection) or die(debug_print ("ERROR: 567292 ", array ($query_adjustment,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$number_of_rows = mysql_num_rows ($result_adjustment);
$this_row = 0;
while ($row_adjustment = mysql_fetch_array ($result_adjustment))
  {
    $adjustment_data[++ $this_row] = (array) $row_adjustment;
  }

// Start over and cycle through the data
$this_row = 0;
while ($this_row ++ < $number_of_rows)
  {
    $adjustment_data['this_row'] = $this_row;
    show_adjustment_row($adjustment_data, $unique_data);
    // $unique_data['show_adjustment'] = show_adjustment_row($adjustment_data, $unique_data);
    // $display .= show_adjustment_row($adjustment_data, $unique_data);
  }
// Close minor
// if ($show_minor_adjustment) $display .= minor_adjustment_close($adjustment_data, $unique_data);
// Close major
// if ($show_major_adjustment) $display .= major_adjustment_close($adjustment_data, $unique_data);


// Close the page
$display .= close_list_bottom($product_data, $adjustment_data, $unique_data);

$page_specific_css .= '
<link rel="stylesheet" type="text/css" href="'.PATH.'show_report.css">
<link rel="stylesheet" type="text/css" href="basket_dropdown.css">
<style type="text/css">
#basket_dropdown {
  right:3%;
  }
#content_top {
  margin-bottom:25px;
  }
.pager a {
  width:'.($pager['last_page'] == 0 ? 0 : number_format(72/$pager['last_page'],2)).'%;
  }
.adjustment {
  font-size:80%;
  color:#666;
  }
  #simplemodal-data {
    height:100%;
    background-color:#fff;
    }
  #simplemodal-container {
    box-shadow:10px 10px 10px #000;
    }
  #simplemodal-data iframe {
    border:0;
    height:95%;
    width:100%;
    }
  #simplemodal-container a.modalCloseImg {
    background:url('.DIR_GRAPHICS.'/simplemodal_x.png) no-repeat; /* adjust url as required */
    width:25px;
    height:29px;
    display:inline;
    z-index:3200;
    position:absolute;
    top:0px;
    right:0px;
    cursor:pointer;
  }
</style>';

$page_specific_javascript .= '
<script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
<script type="text/javascript" src="'.PATH.'ajax/jquery-simplemodal.js"></script>
<script type="text/javascript" src="'.PATH.'adjust_ledger.js"></script>

<script type="text/javascript">
  // Display an external page using an iframe
  // http://www.ericmmartin.com/projects/simplemodal/
  // Set the simplemodal close button
  $.modal.defaults.closeClass = "modalClose";
  // Popup the simplemodal dialog for selecting a site
  function popup_src(src) {
    $.modal(\'<a class="modalCloseImg modalClose">&nbsp;</a><iframe src="\' + src + \'">\', {
      opacity:70,
      overlayCss: {backgroundColor:"#000"},
      closeHTML:"",
      containerCss:{
        backgroundColor:"#fff",
        borderColor:"#fff",
        height:"80%",
        width:"80%",
        padding:0
        },
      overlayClose:true
      });
    };
  // Close the simplemodal iframe after 500 ms
  function close_delivery_selector() {
    setTimeout(function (){
      $.modal.close();
      }, 1000);
    }
</script>';

$content_list = 
  ($content_top ? '
    <div id="content_top">
    '.$content_top.'
    </div>' : '').'
  <div class="show_report">'.
    // $producer_display.
    $display.'
  </div>
';

// $page_title_html = [value set dynamically]
// $page_subtitle_html = [value set dynamically]
// $page_title = [value set dynamically]
// $page_tab = [value set dynamically]

if ($_GET['output'] == 'csv')
  {
    header('Content-Type: text/csv');
    header('Content-disposition: attachment;filename=Product_List.csv');
    echo $display;
  }
elseif ($_GET['output'] == 'pdf')
  {
    // DISPLAY NOTHING
  }
else
  {
    include("template_header.php");
    echo '
      <!-- CONTENT BEGINS HERE -->
      '.$content_list.'
      <!-- CONTENT ENDS HERE -->';
    include("template_footer.php");
  }