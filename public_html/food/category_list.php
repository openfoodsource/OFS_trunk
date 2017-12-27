<?php
include_once 'config_openfood.php';
session_start();

// Do not show product lists unless the member has already opened a basket, so go do that...
unset ($_SESSION['redirect_after_basket_select']);
if (! CurrentBasket::basket_id())
  {
    // Put the originally requested URI into the $_SESSION for later retrieval
    $_SESSION['redirect_after_basket_select'] = $_SERVER['REQUEST_URI'];
    header ('Location: '.BASE_URL.PATH.'select_delivery_page.php?first_call=true');
  }

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

// Set up the "listing_auth_type" field condition based on whether the member is an "institution" or not
// Only institutions are allowed to see listing_auth_type=3 (wholesale products)
$seconds_until_close = strtotime(ActiveCycle::date_closed()) - time();
if (CurrentMember::auth_type('institution') && $seconds_until_close < INSTITUTION_WINDOW)
  {
    $where_auth_type = '
    AND (
      '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"
      OR '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "institution")';
  }
else
  {
    $where_auth_type = '
    AND '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"';
  }

// Normally, do not show producers that are pending (1) or suspended (2)
$where_producer_pending = '
    AND '.TABLE_PRODUCER.'.pending = 0';

// Only show for listed producers -- not unlisted (1) or suspended (2)
$where_unlisted_producer = '
    AND unlisted_producer = "0"';

// Set the default subquery_confirmed to look only at confirmed products
$where_confirmed = '
    AND '.NEW_TABLE_PRODUCTS.'.confirmed = "1"';

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
$result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 205656 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
$max_quantity = $row['max_quantity'];
// Now set up the scaling factor...
// Font sizes range from .root-0 to .root-24 so we will scale our max_quantity to be size .root-24
$powered_max_quantity = ceil (pow ($max_quantity, $power));
// So set the scale accordingly...
$scale = 24 / $powered_max_quantity;
// Get the cat/subcat/product information for each category/subcategory with products to list
$query ='
  SELECT
    '.TABLE_CATEGORY.'.category_id,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_SUBCATEGORY.'.subcategory_id,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    COUNT(DISTINCT('.NEW_TABLE_PRODUCTS.'.product_id)) AS qty_of_items,
    SUM(IF(DATEDIFF(NOW(), '.NEW_TABLE_PRODUCTS.'.created) < '.DAYS_CONSIDERED_NEW.', 1, 0)) AS qty_of_new_items,
    IF('.NEW_TABLE_PRODUCTS.'.inventory_id > 0, FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull), 1) AS inventory,
    0 AS qty_of_new_items, /* Disabled because of inconsistency problems with MySQL */
    (SELECT COUNT(site_id) FROM '.TABLE_AVAILABILITY.' WHERE '.TABLE_AVAILABILITY.'.site_id = '.NEW_TABLE_BASKETS.'.site_id AND '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id) AS availability
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
    '.NEW_TABLE_BASKETS.' ON ('.NEW_TABLE_BASKETS.'.basket_id = "'.mysqli_real_escape_string ($connection, CurrentBasket::basket_id()).'")
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
    // Generate the category section markup (if we're in a new category)
    if ($category_id != $category_id_prior &&
        $category_id_prior != 0) // Don't flag this on the first pass when processing the first row
      {
        // New category: specifically, we want to do the LAST for each group of subcategories
        $category_qty_root = number_format (ceil (pow ($category_qty_prior, $power) * $scale), 0);
        $markup_primary .= '
           <span class="level_group-1 cat-'.$category_id_prior.'">
             <div class="category list_level-1 cat-'.$category_id_prior.' root-'.$category_qty_root.'">
               <a href="product_list.php?type=full&category_id='.$category_id_prior.'" class="cat">'.$category_name_prior.'</a>
               <span class="levelY">
                 <a href="product_list.php?type=full&category_id='.$category_id_prior.'" class="count'.($category_qty_prior > 1 ? ' plural' : '').'">'.$category_qty_prior.'</a>
                 <a href="product_list.php?type=new&category_id='.$category_id_prior.'" class="count_new'.($category_new_qty_prior == 0 ? ' zero' : '').($category_new_qty_prior > 1 ? ' plural' : '').'">'.$category_new_qty_prior.'</a>
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
               <a href="product_list.php?type=full&subcat_id='.$subcategory_id.'" class="subcat">'.$subcategory_name.'</a>
               <span class="levelZ">
                 <a href="product_list.php?type=full&subcat_id='.$subcategory_id.'" class="count '.($subcategory_qty > 1 ? ' plural' : '').'">'.$subcategory_qty.'</a>
                 <a href="product_list.php?type=new&subcat_id='.$subcategory_id.'" class="count_new '.($subcategory_new_qty == 0 ? ' zero' : '').($subcategory_new_qty > 1 ? ' plural' : '').'">'.$subcategory_new_qty.'</a>
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
  <div id="category2" class="'.$display_as.'">
    <span class="levelX">
    </span>
    '.$markup_primary.'
  </div>';

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///             NOW SEND THE WHOLE PAGE OR PARTIAL-PAGE OUTPUT               ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

$page_specific_javascript = '
<script type="text/javascript">
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
</script>';

$page_specific_css = '
<style type="text/css">
  .levelX {
    color:#58673f;
    font-size: 150%;
    font-family: Verdana, Arial, sans-serif;
    }
  /* STYLES FOR THE VIEW OPTION SELECTORS */
  #view_option {
    /*
    border:1px solid #888;
    border-radius:5px;
    background-color:#bca;
    padding:5px 10px;
    */
    text-align:center;
    font-size:0;
    display:inline-block;
    }
  #view_option .view_option {
    display:inline-block;
    float:left;
    background-color:#6a8;
    cursor:pointer;
    font-size:12px;
    color:#fff;
    width:64px;
    height:75px;
    border:1px solid #264;
    border-radius:4px;
    margin:3px;
    transition:all 0.25s;
    text-align:center;
    vertical-align:bottom;
    }
  #view_option .view_option:hover,
  #view_option .view_option.selected:hover {
    background-color:#486;
    transition:all 0.25s;
    }
  #view_option .view_option.selected {
    background-color:#264;
    transition:all 0.25s;
    }
  #view_option .list,
  #view_option .cloud,
  #view_option .grid {
    background-position:center top 10px;
    background-repeat: no-repeat;
    }
  #view_option .list {
    background-image:url("/food/grfx/list-icon.png");
    }
  #view_option .cloud {
    background-image:url("/food/grfx/cloud-icon.png");
    }
  #view_option .grid {
    background-image:url("/food/grfx/grid-icon.png");
    }

  /* STYLES FOR LIST VIEW */
  #category2.list {
    -moz-column-width:20em;
    -moz-column-gap:2em;
    -webkit-column-width:20em;
    -webkit-column-gap:2em;
    column-width:20em;
    column-gap:2em;
    margin:auto;
    }
  #category2.list .levelY::before,
  #category2.list .levelZ::before {
    content:" \2014";
    }
  #category2.list .count_new::before {
    content:" (";
    }
  #category2.list .count_new::after {
    content:" new)";
    }
  #category2.list .count_new.zero {
    display:none;
    }
  #category2.list .category {
    font-weight:bold;
    }
  #category2.list .count::after {
    content:" item";
    }
  #category2.list .count.plural::after {
    content:" items";
    }
  #category2.list .list_level-1 {
    margin-left:1em;
    }
  #category2.list .list_level-2 {
    margin-left:3em;
    }
  #category2.list .list_level-3 {
    margin-left:5em;
    }
  #category2.list .level_group-1 {
    display:inline;
    }
  #category2.list .category a {
    color:#000;
    transition:all 0.25s;
    }
  #category2.list .subcategory a {
    color:#58673f;
    transition:all 0.25s;
    }
  #category2.list .category a:hover,
  #category2.list .subcategory a:hover {
    text-decoration:underline;
    color:#264;
    transition:all 0.25s;
    }

  /* STYLES FOR CLOUD VIEW */
  #category2.cloud  {
    width:98%;
    text-align:center;
    }
  #category2.cloud .levelX {
    display:none;
    }
  #category2.cloud span {
    display:inline;
    }
  #category2.cloud .category,
  #category2.cloud .subcategory {
    display:inline;
    line-height:25px;
    padding:2px 0.5em;
    overflow:visible;
    }
  #category2.cloud .category a,
  #category2.cloud .subcategory a {
    color:#58673f;
    background-color:rgba(255,255,255,0);
    line-height:1;
    padding:2px 5px;
    transition:all 0.25s;
    }
  #category2.cloud .category a:hover,
  #category2.cloud .subcategory a:hover {
    text-decoration:none;
    background-color:#dfc;
    transition:all 0.25s;
    }
  #category2.cloud .count,
  #category2.cloud .count_new {
    display:none;
    }
  #category2.cloud .root-0  {display:none;  }
  #category2.cloud .root-1  {font-size:7px;}
  #category2.cloud .root-2  {font-size:8px;}
  #category2.cloud .root-3  {font-size:9px;}
  #category2.cloud .root-4  {font-size:10px;}
  #category2.cloud .root-5  {font-size:11px;}
  #category2.cloud .root-6  {font-size:12px;}
  #category2.cloud .root-7  {font-size:13px;}
  #category2.cloud .root-8  {font-size:14px;}
  #category2.cloud .root-9  {font-size:15px;}
  #category2.cloud .root-10 {font-size:16px;}
  #category2.cloud .root-11 {font-size:17px;}
  #category2.cloud .root-12 {font-size:19px;}
  #category2.cloud .root-13 {font-size:21px;}
  #category2.cloud .root-14 {font-size:23px;}
  #category2.cloud .root-15 {font-size:25px;}
  #category2.cloud .root-16 {font-size:27px;}
  #category2.cloud .root-17 {font-size:29px;}
  #category2.cloud .root-18 {font-size:30px;}
  #category2.cloud .root-19 {font-size:31px;}
  #category2.cloud .root-20 {font-size:33px;}
  #category2.cloud .root-21 {font-size:35px;}
  #category2.cloud .root-22 {font-size:37px;}
  #category2.cloud .root-23 {font-size:39px;}
  #category2.cloud .root-24 {font-size:41px;}

  /* STYLES FOR GRID VIEW */
  /* HIDE OVERALL PRODUCT TOTAL (SUPER CATEGORY) */
  #category2.grid .levelX {
    display:none;
    }
  /* GRID BLOCK */
  #category2.grid span.level_group-1 {
    display:block;
    float:left;
    border:1px solid #888;
    width:200px;
    height:200px;
    padding:5px;
    margin:7px;
    border-radius:13px;
    box-shadow:4px 4px 4px #444;
    }
  /* GRID BLOCK TITLE */
  #category2.grid span.level_group-1 .list_level-1 a.cat {
    display:block;
    color:#fff;
    font-weight:bold;
    text-align:center;
    background-color:#264;
    opacity:0.7;
    border-radius:10px;
    padding:5px 5px 20px;
    font-size:120%;
    }
  /* HIDE SUBCAT QUANTITIES AND NEW QUANTITIES */
  #category2.grid .count,
  #category2.grid .count_new {
    display:none;
    }
  /* STYLE CATEGORY QUANTITY */
  #category2.grid .levelY {
    display:block;
    position:relative;
    top:-22px;
    width:100%;
    height:0;
    text-align:center;
    font-weight:bold;
    }
  #category2.grid .levelY a {
    color:#ffc;
    }
  #category2.grid .levelY > .count {
    display:block;
    }
  #category2.grid .levelY > .count::after {
    content:" products";
    }
  #category2.grid .levelY > .count:hover {
    cursor:default;
    text-decoration:none;
    }
  /* BLOCK SUBCATEGORY CONTENTS */


  #category2.grid a.subcat {
    font-size:12px;
    color:#000;
    text-shadow:0 0 2px #fff;
    transition:all 0.25s;
    }
  #category2.grid .subcategory a:hover {
    text-decoration:none;
    background-color:rgba(255,255,255,0.5);
    transition:all 0.25s;
    }
  #category2.grid .list_level-2 {
    display:inline;
    padding:2px 0.5em 2px 0;
    height:20px;
    }
  /* BLOCK BACKGROUNDS */
  #category2.grid span {
    background-position:left top;
    background-repeat: no-repeat;
    }
  #category2.grid span.cat-1 {
    background-image:url("/food/grfx/category_vegetable.png");
    }
  #category2.grid span.cat-2 {
    background-image:url("/food/grfx/category_meat.png");
    }
  #category2.grid span.cat-3 {
    background-image:url("/food/grfx/category_pasta.png");
    }
  #category2.grid span.cat-4 {
    background-image:url("/food/grfx/category_dairy.png");
    }
  #category2.grid span.cat-5 {
    background-image:url("/food/grfx/category_sauces.png");
    }
  #category2.grid span.cat-6 {
    background-image:url("/food/grfx/category_bread.png");
    }
  #category2.grid span.cat-7 {
    background-image:url("/food/grfx/category_beans.png");
    }
  #category2.grid span.cat-8 {
    background-image:url("/food/grfx/category_keychain.png");
    }
  #category2.grid span.cat-9 {
    background-image:url("/food/grfx/category_beverages.png");
    }
  #category2.grid span.cat-11 {
    background-image:url("/food/grfx/category_jam.png");
    }
  #category2.grid span.cat-12 {
    background-image:url("/food/grfx/category_feed.png");
    }
  #category2.grid span.cat-13 {
    background-image:url("/food/grfx/category_fruit.png");
    }
  #category2.grid span.cat-14 {
    background-image:url("/food/grfx/category_toiletries.png");
    }
  #category2.grid span.cat-15 {
    background-image:url("/food/grfx/category_box.png");
    }
  #category2.grid span.cat-19 {
    background-image:url("/food/grfx/category_herbs.png");
    }
  #category2.grid span.cat-20 {
    background-image:url("/food/grfx/category_candy.png");
    }
  #category2.grid span.cat-21 {
    background-image:url("/food/grfx/category_pantry.png");
    }
  #category2.grid span.cat-23 {
    background-image:url("/food/grfx/category_household.png");
    }
  #category2.grid span.cat-24 {
    background-image:url("/food/grfx/category_pets.png");
    }
  #category2.grid span.cat-25 {
    background-image:url("/food/grfx/category_donate.png");
    }
  #category2.grid span.cat-28 {
    background-image:url("/food/grfx/category_butcher.png");
    }
  #category2.grid span.cat-31 {
    background-image:url("/food/grfx/category_nfc.png");
    }
  #category2.grid span.cat-32 {
    background-image:url("/food/grfx/category_services.png");
    }
  #category2.grid span.cat-33 {
    background-image:url("/food/grfx/category_refund.png");
    }
  #category2.grid span.cat-36 {
    background-image:url("/food/grfx/category_plants.png");
    }
  #category2.grid span.cat-37 {
    background-image:url("/food/grfx/category_misc.png");
    }
  #category2.grid span.cat-38 {
    background-image:url("/food/grfx/category_camping.png");
    }
  #category2.grid span.cat-39 {
    background-image:url("/food/grfx/category_vegplants.png");
    }
</style>
';

$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '<span class="subtitle">Browse Categories</span>';
$page_title = 'Products - Browse Categories';
$page_tab = 'shopping_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$list_markup.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
