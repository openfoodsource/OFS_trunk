<?php
include_once 'config_openfood.php';


// Get the arguments passed in the query_data variable
// Format is "basket_id[basket_id]:[delivery_id]  e.g.  basket_id45322:45
$argument_array = explode (':', $_POST['query_data']);

if ($argument_array[0] == 'basket_id')
  {
    $basket_id = $argument_array[1]; // Query is received as "basket_id:xxx:yy:true|false"
    $delivery_id = $argument_array[2];
  }
elseif ($argument_array[0] == 'html2pdf')
  {
    // Input like: html2pdf:14
    $delivery_id = $argument_array[1];
    $customer_output_html = INVOICE_FILE_PATH.'invoices_customers-'.$delivery_id.'.html';
    $customer_output_pdf = INVOICE_FILE_PATH.'invoices_customers-'.$delivery_id.'.pdf';
    exec("htmldoc --webpage --browserwidth 800 --left 36 --right 36 -t pdf $customer_output_html -f $customer_output_pdf");
    echo 'HTML2PDF';
    exit (1);
  }
elseif ($argument_array[0] == 'delete_html')
  {
    // Input like: delete_html:35
    $delivery_id = $argument_array[1];
    $customer_output_html = INVOICE_FILE_PATH.'invoices_customers-'.$delivery_id.'.html';
    if (file_exists($customer_output_html))
      {
        unlink($customer_output_html);
      }
    echo 'DELETED_HTML';
    exit (1);
  }
elseif ($argument_array[0] == 'delete_pdf')
  {
    // Input like: delete_pdf:27
    $delivery_id = $argument_array[1];
    $customer_output_pdf = INVOICE_FILE_PATH.'invoices_customers-'.$delivery_id.'.pdf';
    if (file_exists($customer_output_pdf))
      {
        unlink($customer_output_pdf);
      }
    echo 'DELETED_PDF';
    exit (1);
  }
else
  {
    exit (0); // Wrong query string, so abort.
  }

$query = '
  SELECT
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKETS.'.delivery_id
  FROM
    '.NEW_TABLE_BASKETS.'
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id IS NOT NULL
    AND '.NEW_TABLE_BASKETS.'.checked_out != 0
    AND '.NEW_TABLE_BASKETS.'.basket_id = "'.mysql_real_escape_string ($basket_id).'"';

$result= mysql_query($query) or die("Error: " . mysql_error());
$customer_output_html = INVOICE_FILE_PATH.'invoices_customers-'.$delivery_id.'.html';
$fp = fopen($customer_output_html,a);

if ($row = mysql_fetch_array($result))
  {
    $_GET = array (
      'delivery_id' => $delivery_id,
      'member_id' => $row['member_id'],
      'type' => 'customer_invoice',
      'output' => 'pdf');
    include ('../show_report.php');
    $customer_invoice = '<div class="invoice-container">'.$display.'</div>'.HTMLDOC_PAGING;
    if ( strpos($customer_invoice, 'EMPTY INVOICE') === false )
      {
        fwrite($fp, $customer_invoice);
        $length = strlen ($customer_invoice);
      }
  }

echo 'GENERATED_INVOICE';

?>