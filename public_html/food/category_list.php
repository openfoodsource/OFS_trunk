<?php
include_once 'config_openfood.php';
session_start();

// valid_auth(''); // anyone can view these pages

//The function of this script is to create an html-formatted output of the categories and subcategories
//for available products with product counts and new-product counts.
//Changes to this file should probably be reflected in category_list_products.php as well.

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                     ARGUMENTS USED IN THIS SCRIPT                        ///
///                                                                          ///
/// &category_id=#           Will select the "root" category from which to   ///
///                          begin listing subcategories                     ///
///                                                                          ///
/// &show_parts=list_only    Will only output the html list for the selected ///
///                          category structure                              ///
///                                                                          ///
/// [show_parts: null]       Default condition will output the selected      ///
///                          category list with headers and footers          ///
///                                                                          ///
/// &depth=#                 Will truncate display to this many levels       ///
///                                                                          ///
/// &offset=#                Subtract this number to get the proper level    ///
///                          category for css markup                         ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

// Display page as nested list items (like a list) -- otherwise un-nested (like a tag cloud)
$display_as = (isset ($_GET['display_as']) ? $_GET['display_as'] : 'list');
if ($display_as != 'list' && $display_as != 'grid' && $display_as != 'cloud') $display_as = 'list';
$power = 0.5;

$where_auth_type = '
    AND FIND_IN_SET('.NEW_TABLE_PRODUCTS.'.listing_auth_type, COALESCE((SELECT auth_type FROM '.TABLE_MEMBER.' WHERE member_id = "'.mysqli_real_escape_string ($connection, $_SESSION['member_id']).'"), "member")) > 0';

// Normally, do not show producers that are pending (1) or suspended (2)
$where_producer_pending = '
    AND '.TABLE_PRODUCER.'.pending = 0';

// Only show for listed producers -- not unlisted (1) or suspended (2)
$where_unlisted_producer = '
    AND unlisted_producer = "0"';

// Set the default subquery_confirmed to look only at confirmed products
$where_confirmed = '
    AND '.NEW_TABLE_PRODUCTS.'.approved = "1"
    AND '.NEW_TABLE_PRODUCTS.'.active = "1"';

// Set up an exception for hiding zero-inventory products
$where_zero_inventory = '';
if (EXCLUDE_ZERO_INV == true)
  {
    // Can use TABLE_PRODUCT here because this condition is only used on the public product lists
    $where_zero_inventory = '
    AND IF('.NEW_TABLE_PRODUCTS.'.inventory_id > 0, FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull), 1)';
  }

//Set default depth to a large number
$_GET['depth'] = (isset ($_GET['depth']) ? floor ($_GET['depth']) : 100);

//Make sure offset is numeric
$_GET['offset'] = (isset ($_GET['offset']) ? floor ($_GET['offset']) : 0);

//Set the show_parts or default
$_GET['show_parts'] = (isset ($_GET['show_parts']) ? $_GET['show_parts'] : '');

//Set up the root for the categories tree
$base_category = (isset ($_GET['category_id']) ? preg_replace("/[^0-9]/","",$_GET['category_id']) : 0);

// Get the maximum number of products in any returned grouping
// (this is for scaling the sizes of the tag-cloud fonts)
$query = '
  SELECT
    MAX(qty_of_items) AS max_quantity
  FROM
  (
  SELECT
    COUNT(DISTINCT('.NEW_TABLE_PRODUCTS.'.product_id)) AS qty_of_items
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN
    '.TABLE_SUBCATEGORY.' USING(subcategory_id)
  LEFT JOIN
    '.TABLE_CATEGORY.' USING(category_id)
  LEFT JOIN
    '.TABLE_PRODUCER.' USING(producer_id)
  LEFT JOIN
    '.TABLE_INVENTORY.' USING(inventory_id)
  WHERE 1'.
    $where_producer_pending.
    $where_auth_type.
    $where_unlisted_producer.
    $where_confirmed.
    $where_zero_inventory.'
  GROUP BY
    '.TABLE_SUBCATEGORY.'.category_id
  ) foo';
$result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 905656 ", array ($query2,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
$max_quantity = $row['max_quantity'];
// Now set up the scaling factor...
// Font sizes range from .root-0 to .root-24 so we will scale our max_quantity to be size .root-24
$powered_max_quantity = ceil (pow ($max_quantity, $power));
// So set the scale accordingly...
$scale = 24 / $powered_max_quantity;

// Configure to use the availability matrix -- or not
if (USE_AVAILABILITY_MATRIX == true)
  {
    // Default to use the current basket site_id, if it exists
    $ofs_customer_site_id = CurrentBasket::site_id();
    // If not, then use the session site_id, if it exists
    if (! $ofs_customer_site_id) $ofs_customer_site_id = $_SESSION['ofs_customer']['site_id'];
    // If not, then use the cookie site_id, if it exists
    if (! $ofs_customer_site_id) $ofs_customer_site_id = $_COOKIE['ofs_customer']['site_id'];
    // Now set query values...
    $select_availability = '
    IF ('.TABLE_AVAILABILITY.'.site_id = "'.$ofs_customer_site_id.'", 1, 0) AS availability,
    '.NEW_TABLE_SITES.'.site_long AS site_long_you,';
    $join_availability = '
  LEFT JOIN '.TABLE_AVAILABILITY.' ON (
    '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_AVAILABILITY.'.site_id = "'.$ofs_customer_site_id.'")
  LEFT JOIN '.NEW_TABLE_SITES.' ON '.NEW_TABLE_SITES.'.site_id = "'.$ofs_customer_site_id.'"';
  }
else
  {
    $select_availability = '
    1 AS availability,';
    $join_availability = '';
  }

// Get the cat/subcat/product information for each category/subcategory with products to list
$query ='
  SELECT
    '.TABLE_CATEGORY.'.category_id,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_SUBCATEGORY.'.subcategory_id,
    '.TABLE_SUBCATEGORY.'.subcategory_name,'.
    $select_availability.'
    SUM(IF(
      FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) >= 0, 1, 0)
      ) AS qty_of_items,
    0 /* SUM(IF(DATEDIFF(NOW(), '.NEW_TABLE_PRODUCTS.'.created) < '.DAYS_CONSIDERED_NEW.' && FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) >= 0, 1, 0)) *** Disabled because of inconsistency problems with MySQL ***/ AS qty_of_new_items,
    IF('.NEW_TABLE_PRODUCTS.'.inventory_id > 0, FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull), 1) AS inventory
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN
    '.TABLE_SUBCATEGORY.' USING(subcategory_id)
  LEFT JOIN
    '.TABLE_CATEGORY.' USING(category_id)
  LEFT JOIN
    '.TABLE_PRODUCER.' USING(producer_id)
  LEFT JOIN
    '.TABLE_INVENTORY.' USING(inventory_id)
  LEFT JOIN
    '.NEW_TABLE_BASKETS.' ON ('.NEW_TABLE_BASKETS.'.basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'")'.
  $join_availability.'
  WHERE 1'.
    $where_producer_pending.
    $where_auth_type.
    $where_unlisted_producer.
    $where_confirmed.
    $where_zero_inventory.'
  GROUP BY
    '.TABLE_SUBCATEGORY.'.category_id,
    '.TABLE_SUBCATEGORY.'.subcategory_id
  HAVING availability = 1
  ORDER BY
    '.TABLE_CATEGORY.'.sort_order,
    '.TABLE_SUBCATEGORY.'.subcategory_name';
// Cycle through the results and build HTML
$result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 903656 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
// Get the total number of rows returned
$query_found_rows = '
  SELECT
    FOUND_ROWS() AS found_rows';
$result_found_rows = @mysqli_query ($connection, $query_found_rows) or die (debug_print ("ERROR: 759373 ", array ($query_found_rows, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$row_found_rows = mysqli_fetch_array ($result_found_rows, MYSQLI_ASSOC);
$found_rows = $row_found_rows['found_rows'];
// Initialize variables
$category_id_prior = 0;
$count = 0;
$category_qty = 0;
$category_new_qty = 0;
$markup_primary = '';
$markup_secondary = '';
$list_markup = '';
// Cycle through [one more than] the number of rows because the extra cycle triggers the last changed category_id
while ($count++ <= $found_rows)
  {
    $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
    $category_id = $row['category_id'];
    $category_name = $row['category_name'];
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = $row['subcategory_name'];
    $subcategory_qty = $row['qty_of_items'];
    $subcategory_new_qty = $row['qty_of_new_items'];
    if (strlen ($row['site_long_you']) > 0) $site_long_you = $row['site_long_you']; // Because it has a null value for some rows
    // Generate the category section markup (if we're in a new category)
    if ($category_id != $category_id_prior &&
        $category_id_prior != 0) // Don't flag this on the first pass when processing the first row
      {
        // New category: specifically, we want to do the LAST for each group of subcategories
        $category_qty_root = number_format (ceil (pow ($category_qty_prior, $power) * $scale), 0);
        $markup_primary .= '
           <span class="level_group-1 cat-'.$category_id_prior.'">
             <div class="category list_level-1 cat-'.$category_id_prior.' root-'.$category_qty_root.'">
               <a href="product_list.php?type=customer_list&select_type=all&category_id='.$category_id_prior.'" class="cat">'.$category_name_prior.'</a>
               <span class="levelY">
                 <a href="product_list.php?type=customer_list&select_type=all&category_id='.$category_id_prior.'" class="count'.($category_qty_prior > 1 ? ' plural' : '').'">'.$category_qty_prior.'</a>
                 <a href="product_list.php?type=customer_list&select_type=new_changed&category_id='.$category_id_prior.'" class="count_new'.($category_new_qty_prior == 0 ? ' zero' : '').($category_new_qty_prior > 1 ? ' plural' : '').'">'.$category_new_qty_prior.'</a>
               </span>
             </div>'.$markup_secondary.'
            </span>';
        // Reset the category total
        $category_qty = 0;
        $category_new_qty = 0;
        $markup_secondary = '';
      }
    // Calculated values
    $category_qty = $category_qty + $subcategory_qty;
    $category_new_qty = $category_new_qty + $subcategory_new_qty;
    // Generate the subcategory section markup (always)
    $subcategory_qty_root = number_format (ceil (pow ($subcategory_qty, $power) * $scale), 0);
    $markup_secondary .= '
             <div class="subcategory list_level-2 subcat-'.$subcategory_id.' root-'.$subcategory_qty_root.'">
               <a href="product_list.php?type=customer_list&select_type=all&subcat_id='.$subcategory_id.'" class="subcat">'.$subcategory_name.'</a>
               <span class="levelZ">
                 <a href="product_list.php?type=customer_list&select_type=all&subcat_id='.$subcategory_id.'" class="count '.($subcategory_qty > 1 ? ' plural' : '').'">'.$subcategory_qty.'</a>
                 <a href="product_list.php?type=customer_list&select_type=new_changed&subcat_id='.$subcategory_id.'" class="count_new '.($subcategory_new_qty == 0 ? ' zero' : '').($subcategory_new_qty > 1 ? ' plural' : '').'">'.$subcategory_new_qty.'</a>
               </span>
             </div>';
    $category_id_prior = $category_id;
    $category_name_prior = $category_name;
    $category_qty_prior = $category_qty;
    $category_new_qty_prior = $category_new_qty;
  }
$list_markup = '
  <div id="view_option">View categories as <br />
    <div id="view_option_list" class="view_option list'.($display_as == 'list' ? ' selected' : '').'" onclick="set_view(\'list\');">List</div>
    <div id="view_option_cloud" class="view_option cloud'.($display_as == 'cloud' ? ' selected' : '').'" onclick="set_view(\'cloud\');">Cloud</div>
    <div id="view_option_grid" class="view_option grid'.($display_as == 'grid' ? ' selected' : '').'" onclick="set_view(\'grid\');">Grid</div>
  </div>
  <div id="category2" class="subpanel category_list '.$display_as.'">
    <header>Category List &mdash; <span class="list">List View</span><span class="grid">Grid View</span><span class="cloud">Cloud View</span><span class="toggle" onclick="toggle_view();">Switch View</span></header>
    <div class="category2_inner">
      <span class="levelX">
      </span>
      '.$markup_primary.'
    </div>
    <div class="clear"></div>
  </div>';

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///             NOW SEND THE WHOLE PAGE OR PARTIAL-PAGE OUTPUT               ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

$page_specific_javascript = '
  function set_view (view_type) {
    jQuery.each(["list", "cloud", "grid"], function(index, view_option) {
      if (view_type == view_option) {
        jQuery("#view_option_"+view_type).addClass("selected");
        jQuery("#category2").addClass(view_type);
        }
      else {
        jQuery("#view_option_"+view_option).removeClass("selected");
        jQuery("#category2").removeClass(view_option);
        }
      });
    }
  /* Toggle between the three views */
  function toggle_view() {
    var classes = ["list", "cloud", "grid"];
    if (jQuery("#category2").hasClass("grid")) { set_view ("cloud"); }
    else if (jQuery("#category2").hasClass("cloud")) { set_view ("list"); }
    else if (jQuery("#category2").hasClass("list")) { set_view ("grid"); }
    };';

$page_specific_stylesheets['category_list'] = array (
  'name'=>'category_list',
  'src'=>BASE_URL.PATH.'category_list.css',
  'dependencies'=>array(),
  'version'=>'2.1.1',
  'media'=>'all'
  );

$page_specific_css = '
  #category2.list header {
    background-image:url("'.DIR_GRAPHICS.'icon-list.png");
    }
  #category2.cloud header {
    background-image:url("'.DIR_GRAPHICS.'icon-cloud.png");
    }
  #category2.grid header {
    background-image:url("'.DIR_GRAPHICS.'icon-grid.png");
    }';

$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '
  <span class="subtitle">'.$subtitle.
    (strlen ($site_long_you) > 0 ? '
      <span class="subtitle_site" title="Change this?" onclick="popup_src(\''.BASE_URL.PATH.'customer_select_site.php?display_as=popup\', \'customer_select_site\', \'\');">'.$site_long_you.'</span>'
    : '').'
  </span>';
$page_title = 'Products - Browse Categories';
$page_tab = 'shopping_panel';

// Let the header know to handle this as a product_list page (i.e. ask for customer_site if needed)
$is_customer_product_page = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$list_markup.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
