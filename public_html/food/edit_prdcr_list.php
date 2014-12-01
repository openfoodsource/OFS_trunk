<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer_admin');


if( $_REQUEST['prep'] == 'live' )
  {
    // Make sure only confirmed products are copied to the product_list table
    if (REQ_PRDCR_CONFIRM)
      {
        $where_confirmed = 'WHERE '.TABLE_PRODUCT_PREP.'.confirmed = "1"';
      }
    else
      {
        $where_confirmed = '';
      }
    $sqlprep = '
      CREATE TABLE '.TABLE_PRODUCT_TEMP.'
      SELECT '.TABLE_PRODUCT_PREP.'.*
      FROM '.TABLE_PRODUCT_PREP.'
      '.$where_confirmed;
    $resultprep = @mysql_query($sqlprep, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if($resultprep)
      {
        $message .= "New product list has been copied.<br>";
      }
    else
      {
        $message .= "New product list not copied. Notify the administrator of this error.";
      }
    $sqldrop = '
      DROP TABLE '.TABLE_PRODUCT;
    $resultdrop = @mysql_query($sqldrop, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if($resultdrop)
      {
        $message .= "Old product list has been dropped.<br>";
      }
    else
      {
        $message .= "Old product list was not dropped. Notify the administrator of this error.<br>";
      }
    $sqlrename = '
      ALTER TABLE '.TABLE_PRODUCT_TEMP.'
      RENAME TO '.TABLE_PRODUCT;
    $resultrename = @mysql_query($sqlrename, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if($resultrename)
      {
        $message .= "New Product list has been renamed and the CHANGES ARE LIVE.<br>";
      }
    else
      {
        $message .= "Product list was not renamed, product list NOT UPDATED. Notify the administrator of this error.<br>";
      }
    $sqlindex = '
      ALTER TABLE 
        '.TABLE_PRODUCT.'
      ADD PRIMARY KEY ( product_id )';
    $resultindex = @mysql_query($sqlindex, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if($resultindex)
      {
        $message .= "New Product list has been indexed.<br>";
      }
    else
      {
        $message .= "Product list is not indexed. Notify the administrator of this error.<br>";
      }
  }
if( $_REQUEST['confirm'] == 'yes' )
  {
    $sqlu = '
      UPDATE
        '.TABLE_PRODUCT_PREP.'
      SET
        confirmed = "1"
      WHERE producer_id = "'.mysql_real_escape_string ($_REQUEST['producer_id']).'"';
    $result = @mysql_query($sqlu,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    $message = 'The Product List for '.$_REQUEST['producer_id'].' has been confirmed.<br>';
  }
if (isset ($_REQUEST['list_producer']))
  {
    if ( $_REQUEST['list_producer'] == 'relist' )
      {
        $donotlist_prdcr = 0;
      }
    elseif($_REQUEST['list_producer'] == "unlist")
      {
        $donotlist_prdcr = 1;
      }
    elseif ( $_REQUEST['list_producer'] == 'suspend' )
      {
        $donotlist_prdcr = 2;
      }
    $sqlr = '
      UPDATE
        '.TABLE_PRODUCER.'
      SET
        donotlist_producer = "'.mysql_real_escape_string ($donotlist_prdcr).'"
      WHERE producer_id = "'.mysql_real_escape_string ($_REQUEST['producer_id']).'"';
    $resultr = @mysql_query($sqlr,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    $message = "$producer_id has been updated.<br>";
  }
$display .= '
  <table cellpadding="4" cellspacing="2" border="1" bgcolor="#DDDDDD">
    <tr bgcolor="#AEDE86">
      <td><b>New</b></td>
      <td><b>Edit</b></td>
      <td><b>Business Name</b></td>';
if ( REQ_PRDCR_CONFIRM )
  {
    $display .= '
      <td><b>Confirm Product Listing</b></td>
      <td><b>Producer Approved</b></td>';
  }
$display .= '
      <td><b>List or Unlist</b><br>Suspended can not relist themselves</td>
    </tr>';

$sqlp = '
  SELECT
    '.TABLE_PRODUCER.'.*
  FROM
    '.TABLE_PRODUCER.'
  ORDER BY
    donotlist_producer,
    business_name ASC';
$resultp = @mysql_query($sqlp, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
$prdcr_count = mysql_numrows($resultp);
while ( $row = mysql_fetch_array($resultp) )
  {
    $producer_id = $row['producer_id'];
    $business_name = $row['business_name'];
    $donotlist_producer = $row['donotlist_producer'];
    $sql_count = '
      SELECT
        producer_id,
        product_id,
        COUNT(product_id) AS count_prod,
        SUM(confirmed) AS count_confirmed
      FROM
        '.TABLE_PRODUCT_PREP.'
      WHERE
        producer_id = "'.mysql_real_escape_string ($producer_id).'"
      GROUP BY
        producer_id';
    $result_count = @mysql_query($sql_count, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    while ( $row = mysql_fetch_array($result_count) )
      {
        $count_prod = $row['count_prod'];
        $count_confirmed = $row['count_confirmed'];
        if($count_prod == $count_confirmed)
          {
            $confirmed = "1";
          }
        else
          {
            $confirmed = "";
          }
      }
    if ( $donotlist_producer == "1" )
      {
        $display .= '
          <tr bgcolor="#FFFFFF">
            <td></td>
            <td></td>
            <td align="left"><b>'.$business_name.'</b></td>';
        if ( REQ_PRDCR_CONFIRM ) $display .= '
            <td>Currently Unlisted</td>
            <td valign="top" align="center" bgcolor="#dddddd">&mdash;</td>';
        $display .= '
            <td bgcolor="#ffffdd" align="center">
              Unlisted<br>
              [<a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&list_producer=relist"><strong>Relist</strong></a>]
              [<a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&list_producer=suspend">Suspend</a>]
            </td>
          </tr>';
      }
    elseif ( $donotlist_producer == "2" )
      {
        $display .= '
          <tr bgcolor="#FFFFFF">
            <td></td>
            <td></td>
            <td align="left"><b>'.$business_name.'</b></td>';
        if ( REQ_PRDCR_CONFIRM ) $display .= '
            <td>Currently Unlisted</td>
            <td valign="top" align="center" bgcolor="#dddddd">&mdash;</td>';
        $display .= '
            <td bgcolor="#ffdddd" align="center">
              <strong>SUSPENDED!</strong><br>
              [<a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&list_producer=relist"><strong>Relist</strong></a>]
              [<a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&list_producer=unlist">Unsuspend</a>]
            </td>
          </tr>';
      }
    else
      {
        if ( $business_name_prior != $business_name )
          {
            $business_name_prior = $business_name;
            $display_confirmed = "";
            if( $confirmed == "" )
              {
                $display_confirmed = '
                <td><a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&confirm=yes#p_'.$producer_id.'">Confirm Listing</a></td>
                <td valign="top" align="center" bgcolor="#ffdddd"><strong>No</strong></td>';
              }
            else
              {
                $display_confirmed .= '
                <td>Confirmed</td>
                <td valign="top" align="center" bgcolor="#ddeedd">Yes</td>';
              }
            $display .= '
              <tr bgcolor="#FFFFFF">
                <td align="center" id="p_'.$producer_id.'"> <a href="edit_products.php?producer_id='.$producer_id.'">Add</a></td>
                <td align="center"> [<a href="product_list.php?a=retail&producer_id='.$producer_id.'">Listed</a>]<br>
                  [<a href="product_list.php?a=wholesale&producer_id='.$producer_id.'">Wholesale</a>]<br>
                  [<a href="product_list.php?a=unlisted&producer_id='.$producer_id.'">Unlisted</a>]<br></td>
                <td align="left"><b>'.$business_name.'</b></td>';
            if ( REQ_PRDCR_CONFIRM ) $display .= $display_confirmed;
            $display .= '
                <td bgcolor="#ddeedd" align="center">
                  Listed<br>
                  [<a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&list_producer=unlist">Unlist</a>]
                  [<a href="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'&list_producer=suspend">Suspend</a>]
                </td>
              </tr>';
          }
      }
  }
      $display .= "</table>";

$content_edit = '
  <div align="center">
    <table width="80%">
      <tr>
        <td align="center">
          <div align="center">
            <h3>'.$prdcr_count.' Producers</h3>
            <font color=#3333ff><b>'.$message.'</b></font>
          </div>
          Click here to make the product list changes <a href="'.$_SERVER['SCRIPT_NAME'].'?prep=live">live</a>.
          <br>
          '.$display.'
        </td>
      </tr>
    </table>
  </div>';

$page_title_html = '<span class="title">Manage Producers and Products</span>';
$page_subtitle_html = '<span class="subtitle">Producer/Product List</span>';
$page_title = 'Manage Producers and Products: Producer/Product List';
$page_tab = 'producer_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_edit.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
