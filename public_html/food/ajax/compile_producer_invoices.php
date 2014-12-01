<?php
include_once 'config_openfood.php';

// Get the arguments passed in the query_data variable
$argument_array = explode (':', $_POST['query_data']);

if ($argument_array[0] == 'producer_id')
  {
    $producer_id = $argument_array[1]; // Query is received as "basket_id:xxx:yy"
    $delivery_id = $argument_array[2];
    // if ($argument_array[3] == 'true') $use = 'adminfinalize'; // finalize the invoice, if requested
  }
elseif ($argument_array[0] == 'html2pdf')
  {
    // Input like: html2pdf:14
    $delivery_id = $argument_array[1];
    $producer_output_html = INVOICE_FILE_PATH.'invoices_producers-'.$delivery_id.'.html';
    $producer_output_pdf = INVOICE_FILE_PATH.'invoices_producers-'.$delivery_id.'.pdf';
    exec("htmldoc --webpage --browserwidth 800 --left 36 --right 36 -t pdf $producer_output_html -f $producer_output_pdf");
    echo 'HTML2PDF';
    exit (1);
  }
elseif ($argument_array[0] == 'delete_html')
  {
    // Input like: delete_html:35
    $delivery_id = $argument_array[1];
    $producer_output_html = INVOICE_FILE_PATH.'invoices_producers-'.$delivery_id.'.html';
    if (file_exists($producer_output_html))
      {
        unlink($producer_output_html);
      }
    echo 'DELETED_HTML';
    exit (1);
  }
elseif ($argument_array[0] == 'delete_pdf')
  {
    // Input like: delete_pdf:27
    $delivery_id = $argument_array[1];
    $producer_output_pdf = INVOICE_FILE_PATH.'invoices_producers-'.$delivery_id.'.pdf';
    if (file_exists($producer_output_pdf))
      {
        unlink($producer_output_pdf);
      }
    echo 'DELETED_PDF';
    exit (1);
  }
else
  {
    exit (0); // Wrong query string, so abort.
  }

$producer_output_html = INVOICE_FILE_PATH.'invoices_producers-'.$delivery_id.'.html';
$fp = fopen($producer_output_html,a);

if ($delivery_id && $producer_id)
  {
    $_GET = array (
      'delivery_id' => $delivery_id,
      'producer_id' => $producer_id,
      'type' => 'producer_invoice',
      'output' => 'pdf');
    include ('../show_report.php');
    $producer_invoice = '<div class="invoice-container">'.$display.'</div>'.HTMLDOC_PAGING;
    if ( strpos($producer_invoice, 'EMPTY INVOICE') === false )
      {
        fwrite($fp, $producer_invoice);
        $length = strlen ($producer_invoice);
      }
  }

echo 'GENERATED_INVOICE';

?>