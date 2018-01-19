<?php
include_once 'config_openfood.php';
session_start();

// Items dependent upon the location of this header
$pager = array();

// Set up some variables that might be needed
if (isset ($_SESSION['member_id'])) $member_id = $_SESSION['member_id'];
if (isset ($_SESSION['producer_id_you'])) $producer_id_you = $_SESSION['producer_id_you'];
$delivery_id = mysqli_real_escape_string ($connection, ActiveCycle::delivery_id());

// Allow cashier to override member_id
if (isset ($_GET['member_id']) && CurrentMember::auth_type('cashier'))
  $member_id = $_GET['member_id'];
// Allow producer_admin or cashier to override producer_id_you
if (isset ($_GET['producer_id']) && CurrentMember::auth_type('cashier,producer_admin'))
  $producer_id_you = $_GET['producer_id'];
// Allow anyone to override the delivery_id
if ($_GET['delivery_id'])
  $delivery_id = mysqli_real_escape_string ($connection, $_GET['delivery_id']);

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

// $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 782533 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
// // Get the total number of rows (for pagination) -- not counting the LIMIT condition
// $query_found_rows = '
//   SELECT
//     FOUND_ROWS() AS found_rows';
// $result_found_rows = @mysqli_query ($connection, $query_found_rows) or die (debug_print ("ERROR: 820342 ", array ($query_found_rows, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
// // Handle pagination for multi-page results
// $row_found_rows = mysqli_fetch_array ($result_found_rows, MYSQLI_ASSOC);
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
$result_product = mysqli_query ($connection, $query_product) or die (debug_print ("ERROR: 752932 ", array ($query_product, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$number_of_rows = mysqli_num_rows ($result_product);
$this_row = 0;
// Load the data structure with all the values
while ($row_product = mysqli_fetch_array ($result_product, MYSQLI_ASSOC))
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

// Load the data structure with all the values
$result_adjustment = mysqli_query ($connection, $query_adjustment) or die (debug_print ("ERROR: 567292 ", array ($query_adjustment, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$number_of_rows = mysqli_num_rows ($result_adjustment);
$this_row = 0;
while ($row_adjustment = mysqli_fetch_array ($result_adjustment, MYSQLI_ASSOC))
  {
    // First array key=1
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

// Close the page
$display .= close_list_bottom($product_data, $adjustment_data, $unique_data);

$page_specific_stylesheets['show_report'] = array (
  'name'=>'show_report',
  'src'=>BASE_URL.PATH.'show_report.css',
  'dependencies'=>array('ofs_stylesheet'),
  'version'=>'2.1.1',
  'media'=>'all'
  );
$page_specific_stylesheets['basket_dropdown'] = array (
  'name'=>'basket_dropdown',
  'src'=>BASE_URL.PATH.'basket_dropdown.css',
  'dependencies'=>array('ofs_stylesheet'),
  'version'=>'2.1.1',
  'media'=>'all'
  );
$page_specific_css .= '
#basket_dropdown {
  right:3%;
  }
#content_top {
  margin-bottom:25px;
  }
.pager a {
  width:'.($pager['last_page'] == 0 ? 0 : number_format(72/$pager['last_page'],2)).'%;
  }
.price {
  white-space:nowrap;
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
  }';

$page_specific_scripts['adjust_ledger'] = array (
  'name'=>'adjust_ledger',
  'src'=>BASE_URL.PATH.'adjust_ledger.js',
  'dependencies'=>array('jquery'),
  'version'=>'2.1.1',
  'location'=>false
  );

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
