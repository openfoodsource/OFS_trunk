<?php
include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');


// The function of this script is to create an html-formatted output of the categories and subcategories
// for available products with product counts and new-product counts.
// Changes to this file should probably be reflected in category_list_products.php as well.

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
/// CALL THIS SCRIPT WITH GET VALUES:                                        ///
///                                                                          ///
/// [null]                    Display the category list                      ///
///                                                                          ///
/// &action=Edit              Provide fields for editing [Sub]Categories     ///
///                                                                          ///
/// &action=AddCategory       Provide field for adding a new Category        ///
///                                                                          ///
/// &action=AddSubcategory    Provide field for adding a new Subategory      ///
///                                                                          ///
/// &action=Add               Add a [Sub]Category to the database            ///
///                                                                          ///
/// &action=List              Output the Category/Subcategory list           ///
///                                                                          ///
/// &action=Update            Update the database with [Sub]Category info.   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

// Set up the root for the categories tree
$base_category = 0;
$overall_parent_id = '0';
$overall_category_name = 'All Categories';
$overall_category_desc = '';


$page_html = '';

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                   DEFINE listCategoriesEdit FUNCTION                     ///
///                                                                          ///
///         THE FUNCTION RECURSIVELY GENERATES THE CATEGORIES LIST           ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

function listCategoriesEdit ($parent_id, $level, $parent_category_name)
  {
    global $connection, $old_parent_id;
    $prior_category_id = ''; // Contains the category_id from the prior cycle through (for sort-order exchanges)
    $categories_exist = '';
    $query = '
      SELECT
        *
      FROM
        '.TABLE_CATEGORY.'
      WHERE
        parent_id='.mysql_real_escape_string ($parent_id).'
      ORDER BY
        sort_order, category_name;';
    $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 1... $_query<br>\n");
    if (mysql_affected_rows($connection) > 0)
      { // There are more categories (or subcategories) to look at
        $list_html .= '<ul class="cat'.$level.'">';
        $total = 0;
        $total_new = 0;
        while ($row = mysql_fetch_array($sql))
          {
            $category_id = $row['category_id'];
            $category_name = $row['category_name'];
            $category_desc = $row['category_desc'];
            $taxable_category = $row['taxable'];
            $parent_id = $row['parent_id'];
            $sort_order = $row['sort_order'];
            // Display regardless of whether there are items within the category

            // Fix markup from prior iteration (replace CrYpTiCsTrInG with what is *now* the category_id
            $list_html = str_replace ('CrYpTiCsTrInG', $category_id, $list_html);

            // Get markup for moving categories up or down in the list
            $move_up_html = '';
            if ($prior_category_id)
              {
                $move_up_html = '<a href="category_list_edit.php?action=Exchange&category_id1='.$category_id.'&category_id2='.$prior_category_id.'">&uarr;</a>'; }
            else
              {
                $move_up_html = '&nbsp;&nbsp;';
              };
            // Here's the rub... we don't yet know the category_id for the NEXT category in the list
            // ... so we set it as 'CrYpTiCsTrInG' and rewrite it later
            $move_down_html = '<a href="category_list_edit.php?action=Exchange&category_id1='.$category_id.'&category_id2=CrYpTiCsTrInG">&darr;</a>';

            if ($taxable_category == 1) $taxable_category_flag = '<span class="taxable">*</span>';
            if ($taxable_category == 0) $taxable_category_flag = '';

            $list_html .= '<li class="cat'.$level.'">'.$move_up_html.'&nbsp;'.$move_down_html.'&nbsp;&nbsp;&nbsp;<a href="category_list_edit.php?action=Edit&category_id='.$category_id.'">'.$category_name.' '.$taxable_category_flag.'</a>'."\n";
            $return_value = listCategoriesEdit ($category_id, $level + 1, $category_name);
            $sublist_html = $return_value[0];
            $list_html .= $sublist_html.'</li>';
            // Show the option as SELECTED if it should be
            if ($old_parent_id == $category_id)
              {
                $selected = ' selected';
              }
            else
              {
                $selected = '';
              }
            // Indent the option for easy reading
            $option_padding = '';
            $padding_count = 0;
            while ($padding_count++ < (($level - 1) * 3))
              {
                $option_padding .= '&nbsp;';
              }
            $option_html .= '<option class="cat'.$level.'" value= "'.$category_id.'"'.$selected.'>'.$option_padding.$category_name.'</option>'."\n";
            $option_html .= $return_value[1];
            $prior_category_id = $category_id;
          }
        // All existing categories at this level have been done, now show the "Add Category" listing.

        // Fix markup from last iteration (remove the a-tag surrounding CrYpTiCsTrInG because there is no "next" category_id
        $list_html = preg_replace ('/<a[^>]*CrYpTiCsTrInG[^>]*>[^>]*a>/', '&nbsp;&nbsp;', $list_html);

        $list_html .= '<li class="cat'.$level.'">[<a href="category_list_edit.php?action=AddCategory&parent_id='.$parent_id.'">Add new category under <u>'.$parent_category_name.'</u></a>]</li>'."\n";
        $list_html .= "</ul>\n";
        $categories_exist = '<font color="red" size="-1"> NOTE: This subcategory is registered at a category level.  It should be moved to a non-category level</font>';
      }
    if (1)
      { // Call the subcategories regardless of whether we already showed categories or not
        $query2 = '
          SELECT
            *
          FROM
            '.TABLE_SUBCATEGORY.'
          WHERE
            category_id = "'.mysql_real_escape_string ($parent_id).'"
          ORDER BY
            subcategory_name';
        $sql2 = @mysql_query($query2, $connection) or die("Couldn't execute QUERY 2... $_query2<br>\n");
        $list_html .= '<ul class="cat'.$level.'">';
        while ($row2 = mysql_fetch_array($sql2))
          {
            $subcategory_id = $row2['subcategory_id'];
            $subcategory_name = $row2['subcategory_name'];
            $taxable_subcategory = $row2['taxable'];
            // Prepare output formatting

            if ($taxable_subcategory == 1) $taxable_subcategory_flag = '<span class="taxable">*</span>';
            if ($taxable_subcategory == 0) $taxable_subcategory_flag = '';

            $list_html .= '<li class="cat'.$level.'">&raquo; <a href="category_list_edit.php?action=Edit&category_id='.$parent_id.'&subcategory_id='.$subcategory_id.'">'.$subcategory_name.' '.$taxable_subcategory_flag.'</a>'.$categories_exist.'</li>'."\n";
          }
        $list_html .= '<li class="cat'.$level.'">&raquo; [<a href="category_list_edit.php?action=AddSubcategory&parent_id='.$parent_id.'">Add new subcategory under <u>'.$parent_category_name.'</u></a>]</li>'."\n";
        $list_html .= "</ul>\n";
      }
    return (array ($list_html, $option_html));
  }


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                          BEGIN COMMAND SECTION                           ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

if ($_GET['action'] == 'Edit')
  {
    // Get information for this subcategory
    if ($_GET['subcategory_id'] != '')
      {
        // Get subcategory information
        $query = '
          SELECT
            *
          FROM
            '.TABLE_SUBCATEGORY.'
          WHERE
            subcategory_id = "'.mysql_real_escape_string ($_GET['subcategory_id']).'"';
        $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 3... $_query<br>\n");
        $row = mysql_fetch_array($sql);
        $subcategory_id = $row['subcategory_id'];
        $subcategory_name = $row['subcategory_name'];
        $taxable_subcategory = $row['taxable'];
        $old_parent_id = $row['category_id'];
        $option_html = '';
        $return_value = listCategoriesEdit (0, 1, "All Categories");
        $option_html = $return_value[1];
        if ($taxable_subcategory == 1) $taxable_subcategory_yes = ' checked';
        if ($taxable_subcategory == 0) $taxable_subcategory_no = ' checked';
        // Find out how many products are using this subcategory (must not delete if there are any -- prod or prep)
        $query = '
          SELECT
            COUNT(product_id) AS count_prod
          FROM
            '.NEW_TABLE_PRODUCTS.'
          WHERE
            subcategory_id = "'.mysql_real_escape_string ($_GET['subcategory_id']).'"';
        $sql = @mysql_query($query, $connection) or die(debug_print ("ERROR: 793702 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $row = mysql_fetch_array($sql);
        $number_of_products = $row['count_prod'];
        $page_html .= '<form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=Update"><table>'."<br>\n";
        $page_html .= '<tr><td>Subcategory ID: </td><td><input type="hidden" name="subcategory_id" value="'.$subcategory_id.'">'.$subcategory_id."<td></tr>\n";
        $page_html .= '<tr><td>Subcategory Name: </td><td><input type="text" name="new_subcategory_name" size="35" maxlength="35"value="'.htmlspecialchars ($subcategory_name, ENT_QUOTES).'"><td></tr>'."\n";
        $page_html .= '<tr><td>Subcategory is: </td><td><input type="radio" name="new_taxable_subcategory" value="1"'.$taxable_subcategory_yes.'>Taxed
                                         &nbsp; &nbsp; <input type="radio" name="new_taxable_subcategory" value="0"'.$taxable_subcategory_no.'>NON-taxed<td></tr>'."\n";
        $page_html .= '<tr><td>Parent Category: </td><td>'."\n";
        $page_html .= '<select name="new_parent_id">'."\n";
        // Show the option as SELECTED if it should be
        if ($old_parent_id == 0)
          {
            $selected = ' selected';
          }
        else
          {
            $selected = '';
          }
        $page_html .= '<option class="cat0" value="0"'.$selected.'>All Products</option>'."\n";
        $page_html .= "$option_html\n";
        $page_html .= '</select>'."<td></tr>\n";
        $page_html .= '</tr></table><br><br>';
        $page_html .= '<input type="submit" name="submit" value="Update"><br><br>';
        $page_html .= '</form><form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=List"><input type="submit" name="submit" value="CANCEL"></form>'."<br><br>\n";
        if ($number_of_products > 0)
          {
            $page_html .= ' <div style="color:red;"><strong>CAN NOT DELETE:</strong><br>'.$number_of_products.' products depend upon this category. [<a href="edit_product_slice.php?slice_subcategory='.$subcategory_id.'">View them</a>]</div><br><br>';
          }
        else
          {
            $page_html .= ' <form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=Update"><input type="hidden" name="subcategory_id" value="'.$subcategory_id.'"><input type="submit" name="submit" value="Delete"></form>';
          }
      }
    elseif ($_GET['category_id'] != '')
      {
        $query = '
          SELECT
            *
          FROM
            '.TABLE_CATEGORY.'
          WHERE
            category_id = "'.mysql_real_escape_string ($_GET['category_id']).'"';
        $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 3... $_query<br>\n");
        $row = mysql_fetch_array($sql);
        $category_id = $row['category_id'];
        $category_name = $row['category_name'];
        $category_desc = $row['category_desc'];
        $taxable_category = $row['taxable'];
        $old_parent_id = $row['parent_id'];
        $option_html = '';
        $return_value = listCategoriesEdit (0, 1, "All Categories");
        $option_html = $return_value[1];
        if ($taxable_category == 1) $taxable_category_yes = ' checked';
        if ($taxable_category == 0) $taxable_category_no = ' checked';
        // Find out how many categories or subcategories are using this category (must not delete if there are any)
        $query = '
          SELECT
            COUNT(subcategory_id) AS count
          FROM
            '.TABLE_SUBCATEGORY.'
          WHERE
            category_id = "'.mysql_real_escape_string ($_GET['category_id']).'"';
        $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 8... $_query<br>\n");
        $row = mysql_fetch_array($sql);
        $number_of_cats = $row['count'];
        $query = '
          SELECT
            COUNT(category_id) AS count
          FROM
            '.TABLE_CATEGORY.'
          WHERE
            parent_id = "'.mysql_real_escape_string ($_GET['category_id']).'"';
        $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 8... $_query<br>\n");
        $row = mysql_fetch_array($sql);
        $number_of_cats = $number_of_cats + $row['count'];
        $page_html .= '<form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=Update"><table>'."<br>\n";
        $page_html .= '<tr><td>Category ID: </td><td><input type="hidden" name="category_id" value="'.$category_id.'">'.$category_id."<td></tr>\n";
        $page_html .= '<tr><td>Category Name: </td><td><input type="text" name="new_category_name" size="37" maxlength="37" value="'.htmlspecialchars ($category_name, ENT_QUOTES).'"><td></tr>'."\n";
        $page_html .= '<tr><td>Category Desc: </td><td><input type="text" name="new_category_desc" size="37" maxlength="255" value="'.htmlspecialchars ($category_desc, ENT_QUOTES).'"><td></tr>'."\n";
        $page_html .= '<tr><td>Category is: </td><td><input type="radio" name="new_taxable_category" value="1"'.$taxable_category_yes.'>Taxed
                                         &nbsp; &nbsp; <input type="radio" name="new_taxable_category" value="0"'.$taxable_category_no.'>NON-taxed<td></tr>'."\n";
        $page_html .= '<tr><td>Parent Category: </td><td>'."\n";
        $page_html .= '<select name="new_parent_id">'."\n";
        // Show the option as SELECTED if it should be
        if ($old_parent_id == 0)
          {
            $selected = ' selected';
          }
        else
          {
            $selected = '';
          }
        $page_html .= '<option class="cat0" value="0"'.$selected.'>All Products</option>'."\n";
        $page_html .= "$option_html\n";
        $page_html .= '</select>'."<td></tr>\n";
        $page_html .= '</tr></table><br><br>';
        $page_html .= '<input type="submit" name="submit" value="Update"><br><br>';
        $page_html .= '</form><form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=List"><input type="submit" name="submit" value="CANCEL"></form>'."<br><br>\n";
        if ($number_of_cats > 0)
          {
            $page_html .= ' <div style="color:red;">CAN NOT DELETE:<br>'.$number_of_cats.' [sub]categories depend<br>upon this category.</div><br><br>';
          }
        else
          {
            $page_html .= ' <form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=Update"><input type="hidden" name="category_id" value="'.$category_id.'"><input type="submit" name="submit" value="Delete"></form>';
          }
      }
  }

if ($_GET['action'] == 'Exchange')
  {
    $category_id1 = $_GET['category_id1'];
    $category_id2 = $_GET['category_id2'];
    $query = '
      SELECT
        (@sort1:=sort_order)
      FROM
        '.TABLE_CATEGORY.'
      WHERE
        category_id='.mysql_real_escape_string ($category_id2);
    $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY A1... $_query<br>\n");
    $query = '
      SELECT
        (@sort2:=sort_order)
      FROM
        '.TABLE_CATEGORY.'
      WHERE
        category_id='.mysql_real_escape_string ($category_id1);
    $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY A2... $_query<br>\n");
    $query = '
      UPDATE
        '.TABLE_CATEGORY.'
      SET
        sort_order = @sort2
      WHERE
        category_id='.mysql_real_escape_string ($category_id2);
    $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY A3... $_query<br>\n");
    $query = '
      UPDATE
        '.TABLE_CATEGORY.'
      SET
        sort_order = @sort1
      WHERE
        category_id='.mysql_real_escape_string ($category_id1);
    $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY A4... $_query<br>\n");
    $_GET['action'] = 'List';
  }

if ($_GET['action'] == 'AddCategory')
  {
    $option_html = '';
    $old_parent_id = $_GET['parent_id'];
    $return_value = listCategoriesEdit (0, 1, "All Categories");
    $option_html = $return_value[1];
    $page_html .= '<form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=Add"><table>'."<br>\n";
    $page_html .= "<tr><td>Category ID: </td><td>To be assigned<td></tr>\n";
    $page_html .= '<tr><td>Category Name: </td><td><input type="text" name="new_category_name" size="37" maxlength="37"><td></tr>'."\n";
    $page_html .= '<tr><td>Category Desc: </td><td><input type="text" name="new_category_desc" size="37" maxlength="255"><td></tr>'."\n";
    $page_html .= '<tr><td>Parent Category: </td><td>'."\n";
    $page_html .= '<select name="new_parent_id">'."\n";
    // Show the option as SELECTED if it should be
    if ($old_parent_id == 0)
      {
        $selected = ' selected';
      }
    else
      {
        $selected = '';
      }
    $page_html .= '<option class="cat0" value="0"'.$selected.'>All Products</option>'."\n";
    $page_html .= "$option_html\n";
    $page_html .= '</select>'."<td></tr>\n";
    $page_html .= '</tr></table><br><br>'."\n";
    $page_html .= '<input type="submit" name="type" value="Add Category"></form><form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=List"><input type="submit" name="submit" value="CANCEL"></form>'."<br>\n";
  }

if ($_GET['action'] == 'AddSubcategory')
  {
    $option_html = '';
    $old_parent_id = $_GET['parent_id'];
    $return_value = listCategoriesEdit (0, 1, "All Categories");
    $option_html = $return_value[1];
    $page_html .= '<form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=Add"><table>'."<br>\n";
    $page_html .= "<tr><td>Subcategory ID: </td><td>To be assigned<td></tr>\n";
    $page_html .= '<tr><td>Subcategory Name: </td><td><input type="text" name="new_subcategory_name" size="37" maxlength="37"><td></tr>'."\n";
    $page_html .= '<tr><td>Parent Category: </td><td>'."\n";
    $page_html .= '<select name="new_parent_id">'."\n";
    // Show the option as SELECTED if it should be
    if ($old_parent_id == 0)
      {
        $selected = ' selected';
      }
    else
      {
        $selected = '';
      }
    $page_html .= '<option class="cat0" value="0"'.$selected.'>All Products</option>'."\n";
    $page_html .= "$option_html\n";
    $page_html .= '</select>'."<td></tr>\n";
    $page_html .= '</tr></table><br><br>'."\n";
    $page_html .= '<input type="submit" name="type" value="Add Subcategory"></form><form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?action=List"><input type="submit" name="submit" value="CANCEL"></form>'."<br>\n";
  }

if ($_GET['action'] == 'Add')
  {
    if ($_POST['type'] == 'Add Category')
      {
        $query = '
          SELECT
            MAX(sort_order) AS sort_order
          FROM
            '.TABLE_CATEGORY;
        $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 5A... $_query<br>\n");
        $row = mysql_fetch_array($sql);
        $sort_order = $row['sort_order'];
        // Need to get unique sort_order value, so use one higher than current maximum value.
        $query = '
          INSERT INTO
            '.TABLE_CATEGORY.'
              (
                category_name,
                category_desc,
                parent_id,
                sort_order
              )
            VALUES
              (
                "'.mysql_real_escape_string ($_POST['new_category_name']).'",
                "'.mysql_real_escape_string ($_POST['new_category_desc']).'",
                "'.mysql_real_escape_string ($_POST['new_parent_id']).'",
                "'.mysql_real_escape_string (($sort_order + 1)).'"
              )';
      }
    if ($_POST['type'] == 'Add Subcategory')
      {
        $query = '
          INSERT INTO
            '.TABLE_SUBCATEGORY.'
              (
                subcategory_name,
                category_id
              )
            VALUES
              (
                "'.mysql_real_escape_string ($_POST['new_subcategory_name']).'",
                "'.mysql_real_escape_string ($_POST['new_parent_id']).'"
              )';
      }
    $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 5B... $_query<br>\n");
    // After doing the update, show the list again
    $_GET['action'] = 'List';
  }
if ($_GET['action'] == 'Update')
  {
    if ($_POST['submit'] == 'Update')
      { // UPDATE actions
        if ($_POST['subcategory_id'])
          {
            $query = '
              UPDATE
                '.TABLE_SUBCATEGORY.'
              SET
                subcategory_name = "'.$_POST['new_subcategory_name'].'",
                taxable = "'.$_POST['new_taxable_subcategory'].'",
                category_id = "'.$_POST['new_parent_id'].'"
              WHERE
                subcategory_id = "'.$_POST['subcategory_id'].'"';
            }
          elseif ($_POST['category_id']) {
            $query = '
              UPDATE '.TABLE_CATEGORY.'
              SET category_name = "'.$_POST['new_category_name'].'",
                category_desc = "'.$_POST['new_category_desc'].'",
                taxable = "'.$_POST['new_taxable_category'].'",
                parent_id = "'.$_POST['new_parent_id'].'"
              WHERE category_id = "'.$_POST['category_id'].'"';
          }
      }
    elseif ($_POST['submit'] == 'Delete')
      { // DELETE actions
        if ($_POST['subcategory_id'])
          {
            // First gather "UNDO" information
            $query = '
              SELECT
                *
              FROM
                '.TABLE_SUBCATEGORY.'
              WHERE
                subcategory_id = "'.$_POST['subcategory_id'].'"';
            $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 3... $_query<br>\n");
            $row = mysql_fetch_array($sql);
            $subcategory_id = $row['subcategory_id'];
            $subcategory_name = $row['subcategory_name'];
            $taxable_subcategory = $row['taxable'];
            $parent_id = $row['category_id'];
            // Then proceed with the deletion
            $query = '
              DELETE FROM
                '.TABLE_SUBCATEGORY.'
              WHERE
                subcategory_id = "'.$_POST['subcategory_id'].'"';
            $page_html .= 'Subcategory '.$_POST['subcategory_id'].' has been deleted. '."\n";
            $page_html .= '<form action="'.$_SERVER['SCRIPT_NAME'].'?action=Add" method="post">'."\n";
            $page_html .= '<input type="hidden" name="new_subcategory_name" value="'.$subcategory_name.'">'."\n";
            $page_html .= '<input type="hidden" name="new_subcategory_id" value="'.$subcategory_id.'">'."\n";
            $page_html .= '<input type="hidden" name="new_taxable_subcategory value="'.$taxable_category.'">'."\n";
            $page_html .= '<input type="hidden" name="new_parent_id" value="'.$parent_id.'">'."\n";
            $page_html .= '<input type="hidden" name="type" value="Add Subcategory">'."\n";
            $page_html .= '<input type="submit" name="submit" value="UNDO DELETION">'."\n";
            $page_html .= '</form><br><br>'."\n";
          }
        elseif ($_POST['category_id'])
          {
            // First gather "UNDO" information
            $query = '
              SELECT
                *
              FROM
                '.TABLE_CATEGORY.'
              WHERE
                category_id = "'.mysql_real_escape_string ($_POST['category_id']).'"';
            $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 3... $_query<br>\n");
            $row = mysql_fetch_array($sql);
            $category_id = $row['category_id'];
            $category_name = $row['category_name'];
            $category_desc = $row['category_name'];
            $taxable_category = $row['taxable'];
            $parent_id = $row['parent_id'];
            // Then proceed with the deletion
            $query = '
              DELETE FROM
                '.TABLE_CATEGORY.'
              WHERE
                category_id = "'.mysql_real_escape_string ($_POST['category_id']).'"';
            $page_html .= 'Category '.$_POST['category_id'].' has been deleted. '."\n";
            $page_html .= '<form action="'.$_SERVER['SCRIPT_NAME'].'?action=Add" method="post">'."\n";
            $page_html .= '<input type="hidden" name="new_category_name" value="'.$category_name.'">'."\n";
            $page_html .= '<input type="hidden" name="new_category_id" value="'.$category_id.'">'."\n";
            $page_html .= '<input type="hidden" name="new_category_desc" value="'.$category_desc.'">'."\n";
            $page_html .= '<input type="hidden" name="new_taxable_category value="'.$taxable_category.'">'."\n";
            $page_html .= '<input type="hidden" name="new_parent_id" value="'.$parent_id.'">'."\n";
            $page_html .= '<input type="hidden" name="type" value="Add Category">'."\n";
            $page_html .= '<input type="submit" name="submit" value="UNDO">'."\n";
            $page_html .= '</form><br><br>'."\n";
          }
      }
    $sql = @mysql_query($query, $connection) or die("Couldn't execute QUERY 5... $_query<br>\n");
    // After doing the update, show the list again
    $_GET['action'] = 'List';
  }

if ($_GET['action'] == '' || $_GET['action'] == 'List')
  { // Show Hierarchical display of categories and subcategories
    $return_value = listCategoriesEdit (0, 1, "All Categories");
    $sublist_html = $return_value[0];
    $page_html .= '<ul class="cat0"><li class="cat0">'.$overall_category_name."\n".$sublist_html.'</li></ul>';
  };

$page_specific_css = '
    <link href="product_list.css" rel="stylesheet" type="text/css">';

$content_edit = '
<div align="center">
<table width="80%">
  <tr><td align="left">

<div align="left">
<h3>Select a Category or Subcategory to add or edit</h3>
Only subcategories (identified by &raquo;) can be used for product designations.<br>
Taxable categories and subcategories are identified with <span class="taxable">*</span>.
If a category is taxable, then all subcategories within it will also be taxed
even if they are not so designated.<br>
Use arrows to change the sorting order for categories.
</div>
'.$page_html.'
  </td></tr>
</table>
</div>';

$page_title_html = '<span class="title">Admin Maintenance</span>';
$page_subtitle_html = '<span class="subtitle">Edit Sub/categories</span>';
$page_title = 'Admin Maintenance: Edit Sub/categories';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_edit.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

