<?php
include_once 'config_openfood.php';
include_once ('func.get_product.php');
session_start();
valid_auth('producer,producer_admin');


// Figure out where we came from and save it so we can go back
if (isset ($_REQUEST['referrer']))
  {
    $referrer = $_REQUEST['referrer'];
  }
else
  {
    $referrer = $_SERVER['HTTP_REFERER'];
  }
// If we don't have a producer_id then admin may get one from the arguments
if ($_GET['producer_id'] && CurrentMember::auth_type('producer_admin,site_admin'))
  {
    $producer_id = $_GET['producer_id'];
  }
else
  {
    $producer_id = $_SESSION['producer_id_you'];
  }
// Set product_id, product_version
// If this is a reposting of prior data (maybe when there was an error)
if (isset ($_POST['product_id']) &&
    isset ($_POST['product_version']) &&
    isset ($_GET['producer_id']))
  {
    $product_id = $_POST['product_id'];
    $product_version = $_POST['product_version'];
    $producer_id = $_GET['producer_id']; // Always _GET ... not _POST
    $action = 'edit';
    $check_validation = true;
  }
// Or for the first time loading of this page, use $_GET info
elseif (isset ($_GET['product_id']) &&
        isset ($_GET['product_version']) &&
        isset ($_GET['producer_id']))
  {
    $product_id = $_GET['product_id'];
    $product_version = $_GET['product_version'];
    $producer_id = $_GET['producer_id'];
    $action = 'edit';
    $check_validation = false;
    // Get product_info from the database to display in form
    $product_info = get_product ($_GET['product_id'], $_GET['product_version'], '');
    // Abort if the producer does not match the selected producer
    if ($product_info['producer_id'] != $producer_id && ! CurrentMember::auth_type('producer_admin'))
      {
        die(debug_print ("ERROR: 367634 ", 'Product requested is not associated with this producer.', basename(__FILE__).' LINE '.__LINE__));
      }
  }
// And if no product_id or product_version then assume we are adding a product
elseif (isset ($_GET['producer_id']))
  {
    $action = 'add';
    $producer_id = $_GET['producer_id'];
    $check_validation = false;
    // Set some new-product defaults
    $product_info['tangible'] = 1;
    $product_info['listing_auth_type'] = 'member';
  }
else
  {
    die(debug_print ("ERROR: 543612 ", 'Attempt to edit a product without providing required arguments.', basename(__FILE__).' LINE '.__LINE__));
  }
// Process any information posted previously
include('func/edit_product_screen_updatequery.php');
// Now go get the main part of the screen
include("func/edit_product_screen.php");
// Display any errors followed by the main edit page

$content_edit = '
  <div align="center">
  <table width="80%">
    <tr>
      <td align="left">
        '.$help.'
        '.(count($error_array) > 0 ? '
        <div class="error_message">
          <p class="message">The information was not accepted. Please correct the following problems and
          resubmit.<ul class="error_list"><li>'.implode ("</li>\n<li>", $error_array).'</li></ul></p>
        </div>'
          : '').'
          '.$display.'
      </td>
    </tr>
  </table>
  </div>';


$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Edit Product'.($product_id ? ' #'.$product_id : '').''.($product_version ? '-'.$product_version : '').'</span>';
$page_title = $business_name.': Edit Product';
$page_tab = 'producer_panel';

$page_specific_javascript = '
  <script type="text/javascript" src="javascript_popup.js"></script>
  <script type="text/javascript">
  function updatePrices()
    {
    document.getElementById("unit_price_prdcr").value=(document.getElementById("unit_price_coop").value*'.(1 - ActiveCycle::producer_markdown_next ()).').toFixed(2);
    document.getElementById("unit_price_cust").value=(document.getElementById("unit_price_coop").value*'.(1 + (SHOW_ACTUAL_PRICE ? ActiveCycle::retail_markup_next () : 0)).'*(1+(document.getElementById("product_fee_percent").value/100)+'.($subcat_adjust_fee + $producer_adjust_fee).')).toFixed(2);
    document.getElementById("unit_price_institution").value=(document.getElementById("unit_price_coop").value*'.(1 + (SHOW_ACTUAL_PRICE ? ActiveCycle::wholesale_markup_next () : 0)).'*(1+(document.getElementById("product_fee_percent").value/100)+'.($subcat_adjust_fee + $producer_adjust_fee).')).toFixed(2);
    }
  </script>';
$page_specific_css = '
<style type="text/css">
.normal_row {
  background-color:#ddd;
  }
.random_wt_row {
  background-color:#bbd;
  }
.control_row {
  background-color:#ddd;
  }
.admin_row {
  background-color:#fdb;
  }
</style>';


include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$content_edit.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
