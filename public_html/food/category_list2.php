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

//Configure dividers for between the category text and the number of items
$classA_divider = ' ';
$classB_divider = '&nbsp;&mdash; ';
$classC_divider = '&nbsp;&mdash; ';

// Set up the "listing_auth_type" field condition based on whether the member is an "institution" or not
// Only institutions are allowed to see listing_auth_type=3 (wholesale products)
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
$where_confirmed .= '
    AND '.NEW_TABLE_PRODUCTS.'.confirmed = "1"';

// Set up an exception for hiding zero-inventory products
$where_zero_inventory = '';
if (EXCLUDE_ZERO_INV == true)
  {
    // Can use TABLE_PRODUCT here because this condition is only used on the public product lists
    $where_zero_inventory = '
    AND IF('.NEW_TABLE_PRODUCTS.'.inventory_id > 0, FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull), 1)';
  }

// Set default ORDER BY clause
$order_by = '
    '.TABLE_CATEGORY.'.sort_order ASC,
    '.TABLE_CATEGORY.'.category_name ASC,
    '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
    '.TABLE_PRODUCER.'.business_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC,
    '.NEW_TABLE_PRODUCTS.'.unit_price ASC';

//Set default depth to a large number
if (! $_GET['depth'])
  {
    $_GET['depth'] = 100;
  }
else
  {
    $_GET['depth'] = floor ($_GET['depth']);
  }

//Make sure offset is numeric
$_GET['offset'] = floor ($_GET['offset']);

//Set up the root for the categories tree
if ($_GET['category_id'])
  {
    $base_category = preg_replace("/[^0-9]/","",$_GET['category_id']);
  }
else
  {
    $base_category = 0;
  }
$list_markup = '';

//If an order-cycle is open, then use the product_list table.  Otherwise use the product_list_prep table.
if (ActiveCycle::date_open_next() == 'open')
  {
    $product_list = 'product_list';
  }
else
  {
    $product_list = 'product_list_prep';
  }

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///    DEFINE listCategories FUNCTION, BUT ONLY IF NOT ALREADY DEFINED       ///
///                                                                          ///
///         THE FUNCTION RECURSIVELY GENERATES THE CATEGORIES LIST           ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

if (! $list_categories_defined)
  {
    function listCategories ($parent_id, $level)
      {
        global
          $connection,
          $list_categories_defined,
          $classB_divider,
          $classC_divider,
          $product_list,
          $where_producer_pending,
          $where_confirmed,
          $where_auth_type,
          $where_unlisted_producer,
          $where_zero_inventory,
          $where_order_by;
        $list_categories_defined = true;
        $query = '
          SELECT *
          FROM
            '.TABLE_CATEGORY.'
          WHERE
            parent_id = '.$parent_id.'
          ORDER BY
            category_name;';
        $sql = @mysql_query($query, $connection) or die(debug_print ("ERROR: 579302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if (mysql_affected_rows($connection) > 0)
          { //There are more categories (or subcategories) to look at
            if ($level <= $_GET['depth'])
              {
                $list_markup .= '<ul class="list-markup'.$level.'">';
              }
            $total = 0;
            $total_new = 0;
            while ($row = mysql_fetch_array($sql))
              {
                $category_id = $row['category_id'];
                $category_name = $row['category_name'];
                $category_desc = $row['category_desc'];
                $parent_id = $row['parent_id'];
                $return_value = listCategories ($category_id, $level + 1);
                $subtotal = $return_value[0];
                $subtotal_new = $return_value[1];
                $sublist_markup = $return_value[2];
                $total = $total + $subtotal;
                $total_new = $total_new + $subtotal_new;
                if ($level <= $_GET['depth'])
                  { //Prepare output formatting
                    if ($total > 1)
                      {
                        $plural = 's';
                      }
                    else
                      {
                        $plural = '';
                      };
                    $item_count_markup = $subtotal.'&nbsp;item'.$plural;
                    if ($subtotal_new > 0)
                      {
                        $item_new_count_markup = '('.$subtotal_new.'&nbsp;new)';
                      }
                    else
                      {
                        $item_new_count_markup = "";
                      }
                    //$list_markup .= '<li class="cat'.$level.'"><a href="'.$_SERVER['SCRIPT_NAME'].'?category_id='.$category_id.'">'.$category_name."</a>$item_count_markup $item_new_count_markup</li>\n";
                    //Only display if there are items in the category
                    if ($subtotal > 0)
                      {
                        $list_markup .= '<li>
                          <a href="category_list2.php?category_id='.$category_id.'&offset='.$level.'">'.$category_name.'</a>'.$classB_divider.'<span class="levelY">
                          <a href="product_list.php?type=full&category_id='.$category_id.'">'.$item_count_markup.'</a>
                          <a href="product_list.php?type=new&category_id='.$category_id.'">'.$item_new_count_markup."</a>
                          </span>\n";
                        $list_markup .= $sublist_markup."</li>\n";
                      }
                  }
              }
            if ($level <= $_GET['depth'])
              {
                $list_markup .= "</ul>\n";
              }
          }
        else
          { //There are no more "categories", so call the subcategories and see how many items are in each
            $query2 ='
              SELECT
                '.TABLE_SUBCATEGORY.'.*,
                '.NEW_TABLE_PRODUCTS.'.subcategory_id,
                '.NEW_TABLE_PRODUCTS.'.listing_auth_type,
                COUNT(DISTINCT('.NEW_TABLE_PRODUCTS.'.product_id)) AS qty_of_items,
                SUM(IF(DATEDIFF(NOW(), '.NEW_TABLE_PRODUCTS.'.created) < '.DAYS_CONSIDERED_NEW.', 1, 0)) AS qty_of_new_items
              FROM
                '.TABLE_SUBCATEGORY.'
              LEFT JOIN
                '.NEW_TABLE_PRODUCTS.' USING(subcategory_id)
              LEFT JOIN
                '.TABLE_PRODUCER.' USING(producer_id)
              LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
              WHERE
                '.TABLE_SUBCATEGORY.'.category_id = "'.$parent_id.'"'.
                $where_producer_pending.
                $where_auth_type.
                $where_unlisted_producer.
                $where_confirmed.
                $where_zero_inventory.'
              GROUP BY
                '.NEW_TABLE_PRODUCTS.'.subcategory_id
              ORDER BY
                subcategory_name';
            $sql2 = @mysql_query($query2, $connection) or die(debug_print ("ERROR: 905656 ", array ($query2,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            if ($level <= $_GET['depth'])
              {
                $list_markup .= '<ul class="list-markup'.$level.'">';
              }
            while ($row2 = mysql_fetch_array($sql2))
              {
                $subcategory_id = $row2['subcategory_id'];
                $subcategory_name = $row2['subcategory_name'];
                $subtotal = $row2['qty_of_items'];
                $subtotal_new = $row2['qty_of_new_items'];
                $total = $total + $subtotal;
                $total_new = $total_new + $subtotal_new;
                if ($level <= $_GET['depth'])
                  { //Prepare output formatting
                    if ($total > 1)
                      {
                        $plural = 's';
                      }
                    else
                      {
                        $plural = '';
                      };
                    $item_count_markup = $subtotal.'&nbsp;item'.$plural;
                    if ($subtotal_new > 0)
                      {
                        $item_new_count_markup = '('.$subtotal_new.'&nbsp;new)';
                      }
                    else
                      {
                        $item_new_count_markup = "";
                      }
                    $list_markup .= '<li>
                      <a href="product_list.php?type=full&subcat_id='.$subcategory_id.'">'.$subcategory_name.'</a>'.$classC_divider.'<span class="levelZ">
                      <a href="product_list.php?type=full&subcat_id='.$subcategory_id.'">'.$item_count_markup.'</a>
                      <a href="product_list.php?type=new&subcat_id='.$subcategory_id.'">'.$item_new_count_markup."</a>
                      </span></li>\n";
                  }
              }
            if ($level <= $_GET['depth'])
              {
                $list_markup .= "</ul>\n";
              }
          }
        return (array ($total, $total_new, $list_markup));
      }
  }

$query = '
  SELECT *
  FROM
    '.TABLE_CATEGORY.'
  WHERE
    category_id = "'.$base_category.'"';
$sql = @mysql_query($query, $connection) or die(debug_print ("ERROR: 578320 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$row = mysql_fetch_array($sql);
if ($base_category == 0)
  {
    $overall_parent_id = '0';
    $overall_category_name = '<h3 class="inline">Browse Products by Category</h3>';
    $overall_category_desc = '';
  }
else
  {
    $overall_parent_id = $row['parent_id']; //Might be useful for climbing back up the tree
    $overall_category_name = '<h3 class="inline"><a href="'.$_SERVER['SCRIPT_NAME'].'?category_id='.$overall_parent_id.'&offset='.($_GET['offset'] - 1).'"><strong>&laquo;'.$row['category_name'].'</strong></a></h3>';
    if ($_GET['show_parts'] == 'list_only') $overall_category_name = $row['category_name'];
    $overall_category_desc = $row['category_desc'];
  }
$return_value = listCategories ($base_category, $_GET['offset'] + 1);
$total = $return_value[0];
$total_new = $return_value[1];
$sublist_markup = $return_value[2];
if ($total > 1)
  {
    $plural = 's';
  }
else
  {
    $plural = '';
  };
$item_count_markup = $total.'&nbsp;item'.$plural;
if ($total_new > 0)
  {
    $item_new_count_markup = '('.$total_new.'&nbsp;new)';
  }
else
  {
    $item_new_count_markup = "";
  }
$list_markup = $overall_category_name.'
  <div class="category_list2">
    <span class="levelX">
      <a href="product_list.php?type=full&category_id='.$base_category.'">'.$item_count_markup.'</a>&nbsp;&nbsp;
      <a href="product_list.php?type=new&category_id='.$base_category.'">'.$item_new_count_markup.'</a>
    </span>
    '.$sublist_markup.'
  </div>';

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///             NOW SEND THE WHOLE PAGE OR PARTIAL-PAGE OUTPUT               ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '<span class="subtitle">Browse Categories</span>';
$page_title = 'Products - Browse Categories';
$page_tab = 'shopping_panel';

$page_specific_css = '
<link rel="stylesheet" type="text/css" href="'.PATH.'product_list.css">';

//Display the header unless only sending style or list
if ($_GET['show_parts'] != 'list_only' && $_GET['show_parts'] != 'style_only') include("template_header.php");

// //Display the content wrapper unless only sending style or list
// if ($_GET['show_parts'] != 'list_only' && $_GET['show_parts'] != 'style_only') echo '
// 
// <!-- CONTENT BEGINS HERE -->
// 
// <h2 class="banner"><span>Product List</span></h2>';

//Display the list markup unless only sending the style
// if ($_GET['show_parts'] != 'style_only')

//Display the content wrapper unless only sending style or list
if ($_GET['show_parts'] != 'list_only' && $_GET['show_parts'] != 'style_only') echo $list_markup;

//Display the footer unless only sending style or list
if ($_GET['show_parts'] != 'list_only' && $_GET['show_parts'] != 'style_only') include("template_footer.php");
?>
