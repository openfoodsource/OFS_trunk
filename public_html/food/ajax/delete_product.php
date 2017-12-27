<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');

//////////////////////////////////////////////////////////////////////////////
//                                                                          //
// When called with product_id, product_version, and deletion_type, this    //
// program will delete a product version (or all versions if deleting the   //
// whole product). User must be logged in as the producer who owns the      //
// product -- i.e. the producer_id_you must be owner of the product to be   //
// deleted. Normally it is called by ajax from product_list.php             //
//                                                                          //
// NOTE: The product images and inventory buckets will NOT be deleted       //
// since they are permitted to exist without attachment to a product.       //
//                                                                          //
//////////////////////////////////////////////////////////////////////////////

// Get values for this operation
$producer_id_you = $_SESSION['producer_id_you'];
$product_id = $_POST['product_id'];
$product_version = $_POST['product_version'];
$deletion_type = $_POST['deletion_type']; // [product|version]

// First pass check that we own this product/version (producer_id_you) and that it can be deleted
if ($deletion_type == 'version')
  {
    // Restrict our query string to the specific product_id and product_version
    $narrow_by_version = '
    AND product_version = "'.mysqli_real_escape_string ($connection, $product_version).'"';
  }
elseif ($deletion_type == 'product')
  {
    // Otherwise we just query on the product_id
    $narrow_by_version = '';
    $narrow_by_version = '';
  }
else
  {
    // ERROR: Did not choose either product or version deletion_type
    echo 'ERROR:"'.$deletion_type.'" is not a valid deletion_type.';
    exit (1);
  }
$query = '
  SELECT
    COUNT(bpid) AS quantity_ordered,
    producer_id
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(product_id, product_version)
  WHERE
    '.NEW_TABLE_PRODUCTS.'.product_id = "'.mysqli_real_escape_string ($connection, $product_id).'"'.
    $narrow_by_version;
$result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 765830 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
if ($row = mysqli_fetch_object ($result) )
  {
    $quantity_ordered = $row->quantity_ordered;
    $producer_id = $row->producer_id;
    if ($producer_id != $producer_id_you)
      {
        // Only the owning producer (or admin currently acting as that producer) can delete a product
        echo 'ERROR:Deletion attempt by an unauthorized producer.';
        exit (1);
      }
    // We can proceed if the quantity_ordered is zero
    // i.e. if the product has never been ordered
    if ($quantity_ordered == 0)
      {
        $query = '
          DELETE FROM
            '.NEW_TABLE_PRODUCTS.'
          WHERE
            producer_id = "'.mysqli_real_escape_string ($connection, $producer_id_you).'"
            AND (
              SELECT COUNT(bpid)
              FROM '.NEW_TABLE_BASKET_ITEMS.'
              WHERE product_id = "'.mysqli_real_escape_string ($connection, $product_id).'"'.
              $narrow_by_version.') = 0
            AND product_id = "'.mysqli_real_escape_string ($connection, $product_id).'"'.
            $narrow_by_version;
        $result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 768230 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        // If we did not die with a warning, then assume success
        echo 'SUCCESS';
        exit (0);
      }
    else
      {
        echo 'ERROR:This '.$deletion_type.' has been ordered and can not be deleted.';
        exit (1);
      }
  }
// Otherwise something else went wrong
echo 'ERROR:Deletion failed.';
?>