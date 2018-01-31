<?php

// Include classes for ActiveCycle, CurrentBasket, and CurrentMember
include_once ('classes_base.php');

// Establish a connection to the OFS database
function connect_to_database ($database_config)
  {
    global $connection;
    $connection = @mysqli_connect ($database_config['db_host'], $database_config['db_user'], $database_config['db_pass'], $database_config['db_name']) or die (debug_print ("ERROR: 720349 ", array ('error'=>'Error while connecting to the database', mysqli_connect_error()), basename(__FILE__).' LINE '.__LINE__));
  }

// Get the OFS configuration data from the database and set constants
function get_configuration ($database_config, $override_config)
  {
    global $connection;
    $query = '
      SELECT
        name,
        constant,
        options,
        value
      FROM
        '.$database_config['db_prefix'].$database_config['openfood_config'].'
      ORDER BY
        section,
        name';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 264302 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_object ($result))
      {
        // For the special case of boolean checkboxes, cast $row->value to boolean
        // NOTE: This extra step should be deprecated once all boolean true/false values are removed from the software
        $option_data = array_map ('trim', explode ("\n", $row->options));
        if ($option_data[0] == 'checkbox=' && $option_data[1] == 'false' && $option_data[2] == 'true') $row->value = $row->value === 'true'? true: false;
        // If we have a constant other than the special case SECTION_HEADING, then define it
        if (strlen ($row->constant) > 0 && $row->options != "section_heading")
          {
            if (substr ($row->constant, 0, 6) == 'TABLE_' || substr ($row->constant, 0, 10) == 'NEW_TABLE_') $prefix_for_table = $database_config['db_prefix'];
            else $prefix_for_table = '';
            if (isset ($override_config[$row->name])) define ($row->constant, $prefix_for_table.$override_config[$row->name]);
            else define ($row->constant, $prefix_for_table.$row->value);
          }
      }
    // Before leaving this function, set the timezone
    date_default_timezone_set(LOCAL_TIME_ZONE);
  }

// Check authorization for members to access certain things
if (! function_exists ('valid_auth'))
  {
    function valid_auth ($auth_type)
      {
        // If the current auth_type is not even "member" then go to the login page
        if (! CurrentMember::auth_type('member'))
          {
            session_start();
            if (count($_GET) > 0) $_SESSION['_GET'] = $_GET;
            if (count($_POST) > 0) $_SESSION['_POST'] = $_POST;
            $_SESSION['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            header( 'Location: index.php?action=login');
            exit(0);
          }
        // Check against all the passed auth_type options to see if any of them is okay
        else
          {
            $auth_fail = true;
            foreach (explode (',', $auth_type) as $test_auth)
              {
                if (CurrentMember::auth_type($test_auth))
                  {
                    $auth_fail = false;
                  }
              }
            if ($auth_fail)
              {
                header( "Location: index.php");
                exit(0);
              }
            else
              {
                // Restore the $_POST and $_GET variables from the last (failed) access
                // But do not unset any *real* GET or POST values
                if (isset ($_SESSION['_POST']))
                  {
                    $_POST = $_SESSION['_POST'];
                    unset ($_SESSION['_POST']);
                  }
                if (isset ($_SESSION['_GET']))
                  {
                    $_GET = $_SESSION['_GET'];
                    unset ($_SESSION['_GET']);
                  }
              }
          }
      }
  }

// Handle debugging information
if (! function_exists ('debug_print'))
  {
    function debug_print ($text, $data, $target = NULL)
      {
        if (DEBUG_LOGGING != 'NONE')
          {
            $message_content = print_r ($data, true);
            // Consolidate output by removing "Array ()" notations -- remove preg_replace() for explicit output
          }
        if (DEBUG_LOGGING == 'HTML' || DEBUG_LOGGING == 'BOTH')
          {
            // For HTML errors
            $debug_type = substr($text, 0, 3);
            if     ($debug_type == 'ERR' /*error*/)            $color = '128,0,0';
            elseif ($debug_type == 'WAR' /*warn|warning*/)     $color = '0,0,128';
            elseif ($debug_type == 'INF' /*info|information*/) $color = '0 96,0';
            elseif ($debug_type == 'NOT' /*note|notice*/)      $color = '64,64,64';
            else                                               $color = '0,0,0';
            $message = '
              <pre style="width:100%;border-top:2px solid rgb('.$color.');background-color:rgba('.$color.',0.1);"><span style="font-weight:bold;color:rgb('.$color.');">'.$text.'</span>'.
                ': '.date('Y-m-d H:i:s',time()).' (member #'.$_SESSION['member_id'].')'."\n".
                '<span style="color:#aaa;">'.$target.'</span>'."\n".
                $message_content.'
              </pre>';
            $destination = FILE_PATH.PATH.'errors.html';
            error_log ($message, 3, $destination);
          }
        if (DEBUG_LOGGING == 'TEXT' || DEBUG_LOGGING == 'BOTH')
          {
            // For text errors
            $message_header = $text.': '.date('Y-m-d H:i:s',time()).' (member #'.$_SESSION['member_id'].')';
            $message = "\n\n".substr($message_header.' ******************************', 0, 75)."\n".$target."\n".
                preg_replace ('/  /', ' ', $message_content); /* preg_replace: cut down on whitespace */
            $destination = FILE_PATH.PATH.'errors.text';
            error_log ($message, 3, $destination);
          }
      }
  }

// request status value
function ofs_get_status ($scope, $key = '')
  {
    global $connection;
    ofs_delete_status (); // Dump garbage before request
    $query = '
      SELECT
        status_value
      FROM
        '.NEW_TABLE_STATUS.'
      WHERE
        status_scope = "'.mysqli_real_escape_string ($connection, $scope).'"
        AND status_key = "'.mysqli_real_escape_string ($connection, $key).'"';
    $result = @mysqli_query ($connection, $query) or die (
      debug_print ("ERROR: 760765 ", array(
        'Level' => 'FATAL',
        'Scope' => 'Database',
        'File ' => __FILE__.' at line '.__LINE__,
        'Details' => array (
          'MySQL Error' => mysqli_errno ($connection),
          'Message' => mysqli_connect_error (),
          'Query' => $query))));
    if ($row = mysqli_fetch_object ($result))
      {
        return $row->status_value;
      }
  }
// assign/update status
function ofs_put_status ($scope, $key = '', $value, $ttl_minutes = STATUS_TTL_MINUTES)
  {
    global $connection;
    $query = '
      INSERT INTO
        '.NEW_TABLE_STATUS.'
      SET
        status_scope = "'.mysqli_real_escape_string ($connection, $scope).'",
        status_key = "'.mysqli_real_escape_string ($connection, $key).'",
        status_value = "'.mysqli_real_escape_string ($connection, $value).'",
        ttl_minutes = "'.mysqli_real_escape_string ($connection, $ttl_minutes).'"
      ON DUPLICATE KEY UPDATE
        status_value = "'.mysqli_real_escape_string ($connection, $value).'"';
    $result = @mysqli_query ($connection, $query) or die (
      debug_print ("ERROR: 222345 ", array(
        'Level' => 'FATAL',
        'Scope' => 'Database',
        'File ' => __FILE__.' at line '.__LINE__,
        'Details' => array (
          'MySQL Error' => mysqli_errno ($connection),
          'Message' => mysqli_connect_error (),
          'Query' => $query))));
  }
// delete status and collect garbage
function ofs_delete_status ($scope = '', $key = '')
  {
    global $connection;
    $query = '
      DELETE FROM
        '.NEW_TABLE_STATUS.'
      WHERE
        ( status_scope = "'.mysqli_real_escape_string ($connection, $scope).'"
          AND status_key = "'.mysqli_real_escape_string ($connection, $key).'")
        OR TIMESTAMPADD(MINUTE, ttl_minutes, timestamp) < NOW()';
    $result = @mysqli_query ($connection, $query) or die (
      debug_print ("ERROR: 876924 ", array(
        'Level' => 'FATAL',
        'Scope' => 'Database',
        'File ' => __FILE__.' at line '.__LINE__,
        'Details' => array (
          'MySQL Error' => mysqli_errno ($connection),
          'Message' => mysqli_connect_error (),
          'Query' => $query))));
  }

// Reserve a transaction group value using the transaction_group_enum table (similar to an auto-increment)
// Among other things, this allows linking the paypal transaction with the payment
function get_new_transaction_group_id ()
  {
    global $connection;
    $query = '
      INSERT INTO
        '.NEW_TABLE_ADJUSTMENT_GROUP_ENUM.'
      VALUES (NULL)';
    $result = mysqli_query ($connection, $query) or die(
      debug_print ("ERROR: 252930 ", array(
        'Level' => 'FATAL',
        'Scope' => 'Database',
        'File ' => __FILE__.' at line '.__LINE__,
        'Details' => array (
          'MySQL Error' => mysqli_errno ($connection),
          'Message' => mysqli_connect_error (),
          'Query' => $query))));
    return mysqli_insert_id ($connection);
  }

// Convert the ROUTE_CODE_TEMPLATE into something usable for this instance
function convert_route_code($route_code_info)
  {
    $template_parts = preg_split('/!/', ROUTE_CODE_TEMPLATE);
    $route_code = '';
    while (is_string ($template_part = array_shift ($template_parts)))
      {
        $template_part_lower = strtolower ($template_part);
        if (is_string ($route_code_info[$template_part_lower]))
          {
            $route_code .= $route_code_info[$template_part_lower];
          }
        // For storage codes, show nothing if it is not defined
        elseif ($template_part_lower == 'storage_code')
          {
            $route_code .= '';
          }
        else
          {
            $route_code .= $template_part;
          }
      }
    // Remove any empty braces or parentheses
    $route_code = str_replace ('[]', '', $route_code);
    $route_code = str_replace ('()', '', $route_code);
    return $route_code;
  }

function get_image_path_by_id ($image_id)
  {
    // Do we want to send images directly from image files?
    if (SERVE_FILE_IMAGES == true)
      {
        $src = PRODUCT_IMAGE_PATH.'img'.PRODUCT_IMAGE_SIZE.'-'.$image_id.'.png';
        // This could be a good place to pre-generate any images that are about to be requested
        // NOT IMPLEMENTED
      }
    // ... or from the database while creating new image files on the fly?
    elseif (CREATE_IMAGE_FILES == true)
      {
        $src = BASE_URL.PATH.'get_image.php?image_id='.$image_id;
      }
    // ... or use the legacy system to access the dabase directly and nothing more.
    else
      {
        $src = BASE_URL.PATH.'get_image.php?type=db&image_id='.$image_id;
      }
    return $src;
  }

// SOURCE:   http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/
// Thanks to http://www.eval.ca/articles/php-pluralize (MIT license)
//           http://dev.rubyonrails.org/browser/trunk/activesupport/lib/active_support/inflections.rb (MIT license)
//           http://www.fortunecity.com/bally/durrus/153/gramch13.html
//           http://www2.gsu.edu/~wwwesl/egw/crump.htm
//
// Changes (12/17/07)
//   Major changes
//   --
//   Fixed irregular noun algorithm to use regular expressions just like the original Ruby source.
//       (this allows for things like fireman -> firemen
//   Fixed the order of the singular array, which was backwards.
//
//   Minor changes
//   --
//   Removed incorrect pluralization rule for /([^aeiouy]|qu)ies$/ => $1y
//   Expanded on the list of exceptions for *o -> *oes, and removed rule for buffalo -> buffaloes
//   Removed dangerous singularization rule for /([^f])ves$/ => $1fe
//   Added more specific rules for singularizing lives, wives, knives, sheaves, loaves, and leaves and thieves
//   Added exception to /(us)es$/ => $1 rule for houses => house and blouses => blouse
//   Added excpetions for feet, geese and teeth
//   Added rule for deer -> deer

// Changes:
//   Removed rule for virus -> viri
//   Added rule for potato -> potatoes
//   Added rule for *us -> *uses
//   Added rule for uncountable "dozen" -ROYG
//   Modified rule to include half -> halves -ROYG

class Inflect
  {
    static $plural = array(
      '/(quiz)$/i'               => '$1zes',
      '/^(ox)$/i'                => '$1en',
      '/([m|l])ouse$/i'          => '$1ice',
      '/(matr|vert|ind)ix|ex$/i' => '$1ices',
      '/(x|ch|ss|sh)$/i'         => '$1es',
      '/([^aeiouy]|qu)y$/i'      => '$1ies',
      '/(hive)$/i'               => '$1s',
      '/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
      '/(shea|lea|loa|thie|hal)f$/i' => '$1ves',
      '/sis$/i'                  => 'ses',
      '/([ti])um$/i'             => '$1a',
      '/(tomat|potat|ech|her|vet)o$/i'=> '$1oes',
      '/(bu)s$/i'                => '$1ses',
      '/(alias)$/i'              => '$1es',
      '/(octop)us$/i'            => '$1i',
      '/(ax|test)is$/i'          => '$1es',
      '/(us)$/i'                 => '$1es',
      '/s$/i'                    => 's',
      '/$/'                      => 's'
      );
    static $singular = array(
      '/(quiz)zes$/i'             => '$1',
      '/(matr)ices$/i'            => '$1ix',
      '/(vert|ind)ices$/i'        => '$1ex',
      '/^(ox)en$/i'               => '$1',
      '/(alias)es$/i'             => '$1',
      '/(octop|vir)i$/i'          => '$1us',
      '/(cris|ax|test)es$/i'      => '$1is',
      '/(shoe)s$/i'               => '$1',
      '/(o)es$/i'                 => '$1',
      '/(bus)es$/i'               => '$1',
      '/([m|l])ice$/i'            => '$1ouse',
      '/(x|ch|ss|sh)es$/i'        => '$1',
      '/(m)ovies$/i'              => '$1ovie',
      '/(s)eries$/i'              => '$1eries',
      '/([^aeiouy]|qu)ies$/i'     => '$1y',
      '/([lr])ves$/i'             => '$1f',
      '/(tive)s$/i'               => '$1',
      '/(hive)s$/i'               => '$1',
      '/(li|wi|kni)ves$/i'        => '$1fe',
      '/(shea|loa|lea|thie|hal)ves$/i'=> '$1f',
      '/(^analy)ses$/i'           => '$1sis',
      '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'  => '$1$2sis',
      '/([ti])a$/i'               => '$1um',
      '/(n)ews$/i'                => '$1ews',
      '/(h|bl)ouses$/i'           => '$1ouse',
      '/(corpse)s$/i'             => '$1',
      '/(us)es$/i'                => '$1',
      '/s$/i'                     => ''
      );
    static $irregular = array(
      'move'   => 'moves',
      'foot'   => 'feet',
      'goose'  => 'geese',
      'sex'    => 'sexes',
      'child'  => 'children',
      'man'    => 'men',
      'tooth'  => 'teeth',
      'person' => 'people'
      );
    static $uncountable = array(
      'sheep',
      'dozen',
      'fish',
      'deer',
      'series',
      'species',
      'money',
      'rice',
      'information',
      'equipment'
      );
    public static function pluralize( $string )
      {
        // save some time in the case that singular and plural are the same
        if ( in_array( strtolower( $string ), self::$uncountable ) )
            return $string;
        // check for irregular singular forms
        foreach ( self::$irregular as $pattern => $result )
          {
            $pattern = '/' . $pattern . '$/i';
            if ( preg_match( $pattern, $string ) )
                return preg_replace( $pattern, $result, $string);
          }
        // check for matches using regular expressions
        foreach ( self::$plural as $pattern => $result )
          {
            if ( preg_match( $pattern, $string ) )
                return preg_replace( $pattern, $result, $string );
          }
        return $string;
      }
    public static function singularize( $string )
      {
        // save some time in the case that singular and plural are the same
        if ( in_array( strtolower( $string ), self::$uncountable ) )
            return $string;
        // check for irregular plural forms
        foreach ( self::$irregular as $result => $pattern )
          {
            $pattern = '/' . $pattern . '$/i';
            if ( preg_match( $pattern, $string ) )
                return preg_replace( $pattern, $result, $string);
          }
        // check for matches using regular expressions
        foreach ( self::$singular as $pattern => $result )
          {
            if ( preg_match( $pattern, $string ) )
                return preg_replace( $pattern, $result, $string );
          }
        return $string;
    }
    public static function pluralize_if($count, $string)
      {
        if ($count == 1)
            return self::singularize($string);
        else
            return self::pluralize($string);
      }
  }

// This function will no longer be necessary after php version 5.3
function nl2br2($text)
  {
    return preg_replace("/\r\n|\n|\r/", "<br>", $text);
  }
// ... but this function still does not exist.
// Replaces <br> <br /> and also rolls in newlines that are adjacent to <br> and <br />
function br2nl($text)
  {
    return preg_replace('/\\n*?<br\\s*?\\n?\/??>/i', "\n", $text);
  }

// For php < 5.4 need to create this function:
if (!function_exists('getimagesizefromstring'))
  {
    function getimagesizefromstring($string_data)
      {
        $uri = 'data://application/octet-stream;base64,'  . base64_encode($string_data);
        return getimagesize($uri);
      }
  }

// GENERATES A MICROSOFT/WINDOWS-SAFE CSV FILE
function mssafe_csv($filepath, $data, $header = array())
  {
    if ( $fp = fopen($filepath, 'w') ) {
        $show_header = true;
        if ( empty($header) ) {
            $show_header = false;
            reset($data);
            $line = current($data);
            if ( !empty($line) ) {
                reset($line);
                $first = current($line);
                if ( substr($first, 0, 2) == 'ID' && !preg_match('/["\\s,]/', $first) ) {
                    array_shift($data);
                    array_shift($line);
                    if ( empty($line) ) {
                        fwrite($fp, "\"{$first}\"\r\n");
                    } else {
                        fwrite($fp, "\"{$first}\",");
                        fputcsv($fp, $line);
                        fseek($fp, -1, SEEK_CUR);
                        fwrite($fp, "\r\n");
                    }
                }
            }
        } else {
            reset($header);
            $first = current($header);
            if ( substr($first, 0, 2) == 'ID' && !preg_match('/["\\s,]/', $first) ) {
                array_shift($header);
                if ( empty($header) ) {
                    $show_header = false;
                    fwrite($fp, "\"{$first}\"\r\n");
                } else {
                    fwrite($fp, "\"{$first}\",");
                }
            }
        }
        if ( $show_header ) {
            fputcsv($fp, $header);
            fseek($fp, -1, SEEK_CUR);
            fwrite($fp, "\r\n");
        }
        foreach ( $data as $line ) {
            fputcsv($fp, $line);
            fseek($fp, -1, SEEK_CUR);
            fwrite($fp, "\r\n");
        }
        fclose($fp);
    } else {
        return false;
    }
    return true;
  }

// The following function is modified from http://www.phpro.org/examples/Password-Strength-Tester.html by Kevin Waterson
function test_password ($password)
  {
    if ( strlen( $password ) == 0 )
      return 1;
    $strength = 0;
    /*** get the length of the password ***/
    $length = strlen($password);
    /*** check if password is not all lower case ***/
    if(strtolower($password) != $password)
      $strength += 1;
    /*** check if password is not all upper case ***/
    if(strtoupper($password) == $password)
      $strength += 1;
    /*** base strength on the logarithm of length) ***/
    /*** 8 char: +1, 21 char: +2, 55: +3 ***/
    $strength += floor (log ($length)) - 1;
    /*** get the numbers in the password ***/
    preg_match_all('/[0-9]/', $password, $numbers);
    $strength += count($numbers[0]);
    /*** check for special chars ***/
    preg_match_all('/[^a-zA-Z0-9]/', $password, $specialchars);
    $strength += sizeof($specialchars[0]);
    /*** get the number of unique chars ***/
    $chars = str_split($password);
    $num_unique_chars = sizeof( array_unique($chars) );
    $strength += $num_unique_chars * 2;
    /*** strength is a number 1-10; ***/
    $strength = $strength > 99 ? 99 : $strength;
    // $strength = floor($strength / 10 + 1);
    return $strength;
  }

// Function to display alert messages
function display_alert ($alert_type = 'error', $alert_message = '', $alert_array)
  {
    if (count ($alert_array) > 0)
      {
        $display_alert = '
    <div class="display_alert '.$alert_type.' expanded" onmouseover="jQuery(this).removeClass(\'expanded\');" onclick="jQuery(this).toggleClass(\'expanded\');">
      <header class="alert_header">'.$alert_type.'</header>'.
      (strlen ($alert_message) > 0 ? '
      <p class="alert_message">'.$alert_message.'</p>'
      : ''
      ).'
      <ul class="alert_list">
        <li>'.implode ("</li>\n<li>", $alert_array).'</li>
      </ul>
    </div>';
      }
    else
      {
        $display_alert = '';
      }
    return $display_alert;
  }



// This function is used to send email
// Input data is an associative array with values:
// * reason                 [Reason for sending this e-mail (for X-AntiAbuse header)] e.g. 'Confirmation of new-member form submission'
// * subject                [Subject to use for the e-mail]
//   from                   [E-mail address for the "From" email header]
//   reply-to               [E-mail address for the "Reply-To" email header]
//   errors-to              [E-mail address for the "Errors-To" email header]
// * to                     [E-mail address(es) for the "To" email header -- separate with commas]
//   cc                     [E-mail address(es) for the "CC" email header -- separate with commas]
//   bcc                    [E-mail address(es) for the "BCC" email header -- separate with commas]
//   self                   [E-mail for the current member -- translated from 'SELF' in addresses]
//   priority               [E-mail priority for sending]
// * html_body              [E-mail body encoded as HTML]
// * text_body              [E-mail body encoded as plain-text]
//
// * = REQUIRED FIELDS (only one of: html_body OR text_body is required)
//
// Sample template for calling function:
//
// $send_email_return = send_email (array (
//   'reason' => '',
//   'subject' => '',
//   'from' => '',
//   'reply-to' => '',
//   'errors-to' => '',
//   'to' => '',
//   'cc' => '',
//   'bcc' => '',
//   'self' => '', // Email for the active member
//   'priority' => '', // 1=highest .. 5=lowest
//   'html_body' => '',
//   'text_body' => '',
//   'foobar' => ''));

function send_email ($email_array)
  {
    // Set defaults
    $email_headers = '';
    $errors = false;
    if (! isset ($email_array['from']) && strlen (EMAIL_SMTP_DEFAULT_FROM) > 0) $email_array['from'] = EMAIL_SMTP_DEFAULT_FROM;
    if (! isset ($email_array['reply-to']) && strlen (EMAIL_SMTP_DEFAULT_REPLY_TO) > 0) $email_array['reply-to'] = EMAIL_SMTP_DEFAULT_REPLY_TO;
    if (! isset ($email_array['errors-to']) && strlen (EMAIL_SMTP_DEFAULT_ERRORS_TO) > 0) $email_array['reply-to'] = EMAIL_SMTP_DEFAULT_ERRORS_TO;
    if (! isset ($email_array['bcc']) && strlen (EMAIL_SMTP_DEFAULT_BCC) > 0) $email_array['bcc'] = EMAIL_SMTP_DEFAULT_BCC;
    if (! isset ($email_array['priority'])) $email_array['priority'] = 3; // Default priority = 3
    // Error if no "To:" recipient
    if (! isset ($email_array['to'])
        || strlen ($email_array['to']) == 0)
      {
        debug_print ("ERROR: 759301 ", array ('No "To:" header set for e-mail', $email_array), basename(__FILE__).' LINE '.__LINE__);
        $errors = true;
      }
    // Error if no "Subject:" was provided
    if (! isset ($email_array['subject'])
        || strlen ($email_array['subject']) == 0)
      {
        debug_print ("ERROR: 890241 ", array ('No e-mail subject provided', $email_array), basename(__FILE__).' LINE '.__LINE__);
        $errors = true;
      }
    // Error if no body message to send
    if (strlen ($email_array['html_body'].$email_array['text_body']) == 0)
      {
        debug_print ("ERROR: 757931 ", array ('No e-mail message to send', $email_array), basename(__FILE__).' LINE '.__LINE__);
        $errors = true;
      }
    // Go send the mail...
    if (EMAIL_SERVER_TYPE == 'Sendmail')
      {
        $endline = "\r\n";
        $boundary = uniqid(time());
        $multipart = false;
        // Assemble e-mail headers
        $email_headers .= 'From: '.$email_array['from'].$endline;
        // $email_headers .= 'To: '.preg_replace ('/SELF/', $email_array['self'], $email_array['to']).$endline;
        if (strlen ($email_array['cc']) > 0) $email_headers .= 'Cc: '.preg_replace ('/SELF/', $email_array['self'], $email_array['cc']).$endline;
        if (strlen ($email_array['bcc']) > 0) $email_headers .= 'Bcc: '.preg_replace ('/SELF/', $email_array['self'], $email_array['bcc']).$endline;
        $email_headers .= 'Reply-To: '.$email_array['reply-to'].$endline;
        $email_headers .= 'Errors-To: '.$email_array['errors-to'].$endline;
        // Send MIME if multi-part or if HTML
        if (strlen ($email_array['html_body']) > 0) $email_headers .= 'MIME-Version: 1.0'.$endline;
        // Send multipart if there are both HTML and text sections to be sent
        if (strlen ($email_array['html_body']) > 0
            && strlen ($email_array['text_body']) > 0)
          {
            $email_headers .= 'Content-type: multipart/alternative; boundary="'.$boundary.'"'.$endline;
            $multipart = true;
          }
        $email_headers .= 'Message-ID: <'.md5($boundary).'@'.trim (explode ("\n", DOMAIN_NAME)[0]).'>'.$endline;
        $email_headers .= 'X-Mailer: PHP '.phpversion().$endline;
        $email_headers .= 'X-Priority: '.$email_array['priority'].$endline;
        $email_headers .= 'X-AntiAbuse: '.$email_array['reason'].' at '.SITE_NAME.$endline;
        $email_headers .= 'Content-Type: text/html; charset=UTF-8'.$endline;
        // Assemble e-mail body
        $email_body = $endline;
        if ($multipart == true) $email_body .= '--'.$boundary.$endline;
        if ($multipart == true
            && strlen ($email_array['text_body']) > 0) $email_body .= 'Content-Type: text/plain; charset=UTF-8'.$endline;
        if (strlen ($email_array['text_body']) > 0)
          {
            $email_body .= $endline; // Extra blank line to start text section
            $email_body .= $email_array['text_body'].$endline;
          }
        if (strlen ($email_array['html_body']) > 0)
          {
            $email_body .= '--'.$boundary.$endline;
            $email_body .= 'Content-Type: text/html; charset=UTF-8'.$endline;
            $email_body .= $endline; // Extra blank line to start HTML section
            $email_body .= $email_array['html_body'].$endline;
            $email_body .= '--'.$boundary.$endline;
          }
        mail (preg_replace ('/SELF/', $email_array['self'], $email_array['to']), $email_array['subject'], $email_body, $email_headers) or
          debug_print ("ERROR: 756803 ", array ('Sending e-mail failed', $email_array), basename(__FILE__).' LINE '.__LINE__);
      }
    elseif (EMAIL_SERVER_TYPE == 'SMTP Mail')
      {
        // Begin using PHPMailer to send the messages
        include_once ('phpmailer.class.phpmailer.php');
        include_once ('phpmailer.class.smtp.php');
        include_once ('phpmailer.class.pop3.php');
        include_once ('phpmailer.class.phpmaileroauth.php');
        include_once ('phpmailer.class.phpmaileroauthgoogle.php');
        $mail = new PHPMailer;
        // Server and connection configurations
        // Check that we have values for the minimum required fields for SMTP mailing
        if (strlen (EMAIL_SMTP_HOST) == 0
            || strlen (EMAIL_SMTP_PORT) == 0
            || strlen (EMAIL_SMTP_SECURE) == 0
            || strlen (EMAIL_SMTP_USERNAME) == 0
            || strlen (EMAIL_SMTP_PASSWORD) == 0)
          {
            debug_print ("ERROR: 503123 ", array ('Some SMTP configuration settings are missing.', $email_array), basename(__FILE__).' LINE '.__LINE__);
          }
        $mail->isSMTP();                                      // Set mailer to use SMTP
// if ($_SESSION['member_id'] == 16) $mail->SMTPDebug = 3;    // For privately debugging as a particular user
        $mail->Host = EMAIL_SMTP_HOST;                        // Specify main and backup SMTP servers
        $mail->Port = EMAIL_SMTP_PORT;                        // TCP port to connect to
        $mail->SMTPAuth = EMAIL_SMTP_AUTH;                    // Enable SMTP authentication
        $mail->SMTPSecure = EMAIL_SMTP_SECURE;                // Enable TLS encryption, `ssl` also accepted
        $mail->Username = EMAIL_SMTP_USERNAME;                // SMTP username
        $mail->Password = EMAIL_SMTP_PASSWORD;                // SMTP password
        // Message-specific parameters
        // From:
        $from = $email_array['from'];
        $from_name = '';
        if (strpos ($from, '<')) list ($from_name, $from) = explode ('<', preg_replace('/[^0-9a-zA-Z~!@#%^&< \$\*\+\-\.]/', '', $from));
        if (strlen ($from) > 0) $mail->addReplyTo($from);
        if (strlen ($from_name) > 0) $mail->FromName = $from_name; // Redundant, but prevents From => "Root User"
        // Reply-to:
        $reply_to = $email_array['reply-to'];
        $reply_name = '';
        if (strpos ($reply_to, '<')) list ($reply_name, $reply_to) = explode ('<', preg_replace('/[^0-9a-zA-Z~!@#%^&< \$\*\+\-\.]/', '', $reply_to));
        if (strlen ($reply_to) > 0) $mail->addReplyTo($reply_to, $reply_name);
        // To:
        foreach (explode (',', $email_array['to']) as $to)
          {
            // Convert "Foo Bar <foo@bar>"
            if ($to == 'SELF') $to = $email_array['self'];
            $to_name = '';
            if (strpos ($to, '<')) list ($to_name, $to) = explode ('<', preg_replace('/[^0-9a-zA-Z~!@#%^&< \$\*\+\-\.]/', '', $to));
            $mail->addAddress($to, $to_name); // Add To recipient
          }
        // Cc:
        foreach (explode (',', $email_array['cc']) as $cc)
          {
            // Convert "Foo Bar <foo@bar>"
            if ($cc == 'SELF') $cc = $email_array['self'];
            $cc_name = '';
            if (strpos ($cc, '<')) list ($cc_name, $cc) = explode ('<', preg_replace('/[^0-9a-zA-Z~!@#%^&< \$\*\+\-\.]/', '', $cc));
            $mail->addCC($cc, $cc_name); // Add CC recipient
          }
        // Bcc:
        foreach (explode (',', $email_array['bcc']) as $bcc)
          {
            // Convert "Foo Bar <foo@bar>"
            if ($bcc == 'SELF') $bcc = $email_array['self'];
            $bcc_name = '';
            if (strpos ($bcc, '<')) list ($bcc_name, $bcc) = explode ('<', preg_replace('/[^0-9a-zA-Z~!@#%^&< \$\*\+\-\.]/', '', $bcc));
            $mail->addBCC($bcc, $bcc_name); // Add BCC recipient
          }
        if (strlen ($email_array['html_body']) > 0)
          {
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = $email_array['subject'];
            $mail->Body = $email_array['html_body'];
            if (strlen ($email_array['text_body']) > 0) $mail->AltBody = $email_array['text_body'];
          }
        else
          {
            $mail->isHTML(false); // Set email format to PLAIN TEXT
            $mail->Subject = $email_array['subject'];
            $mail->Body = $email_array['html_body'];
          }
        if(! $mail->send())
          {
            debug_print ("ERROR: 347823 ", array ('SMTP Mail failure. Message could not be sent.', $mail->ErrorInfo), basename(__FILE__).' LINE '.__LINE__);
            return 'error';
          }
        else
          {
            return 'message sent';
          }
      }
  }
