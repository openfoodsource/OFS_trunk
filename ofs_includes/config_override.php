<?php

// Override local database connection values. Only need to change values
// that are different from the "official" installation
$database_config ['db_host'] = '127.0.0.1';                // Test database server
$database_config ['db_name'] = 'openfoodsource';           // Test database name
$database_config ['db_user'] = 'openfood_user';            // Test database user
$database_config ['db_pass'] = 'openfood_password';        // Test database password

// Set values that should override database values for this server/installation
// This might be used to configure slight differences between a testing server
// and the production server
//
// Additional override keys (on the left) can be found on the configuration page 
// under site admin on the website.
$override_config = array (
  'site_url'              => 'http://ww2.openfoodsource.org',                      // Testing URL
  'file_path'             => '/home/openfoodsource/public_html',                   // Local file path
  'domainname'            => 'openfoodsource.org',                                 // Domain name
  'invoice_file_path'     => '/var/www/openfoodsource/public_html/food/invoices/', // Local file path
  'email_member_form'     => 'bogus1@openfoodsource.org',                          // Testing e-mail address
  'email_producer_form'   => 'bogus1@openfoodsource.org',                          // Testing e-mail address
  'md5_master_password'   => '   *** ENTER PASSWORD HASH ***  ',                   // Master password can be gotten from MySQL: SELECT MD5("your_master_password")
  'debug'                 => true,                                                 // Debug mode for testing
//  'error_flags'           => 'E_ERROR,E_WARNING,E_PARSE',                        // Error codes for testing
  'bogus'                 => ''                                                    // Catch trailing commas when commenting lines
  );

?>