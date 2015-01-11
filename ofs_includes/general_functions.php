<?php

// Include classes for ActiveCycle, CurrentBasket, and CurrentMember
include_once ('classes_base.php');

// Establish a connection to the OFS database
function connect_to_database ($database_config)
  {
    global $connection;
    $connection = @mysql_connect($database_config['db_host'], $database_config['db_user'], $database_config['db_pass']) or die("Couldn't connect: \n".mysql_error());
    $db = @mysql_select_db($database_config['db_name'], $connection) or die(debug_print ("ERROR: 720349 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
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
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 864302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_object($result))
      {
        // For the special case of boolean checkboxes, cast $row->value to boolean
        // NOTE: This extra step should be deprecated once all boolean true/false values are removed from the software
        $option_data = array_map ('trim', explode ("\n", $row->options));
        if ($option_data[0] == 'checkbox=' && $option_data[1] == 'false' && $option_data[2] == 'true') $row->value = $row->value === 'true'? true: false;
        // If we have a constant, then define it
        if (strlen ($row->constant) > 0)
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
        if (DEBUG_LOGGING == true)
          {
            if (substr($text, 0, 6) == 'ERROR:') $color = '#900';
            elseif (substr($text, 0, 5) == 'WARN:') $color = '#009';
            elseif (substr($text, 0, 5) == 'INFO:') $color = '#060';
            else $color = '#000';

            $message = '
              <pre style="color:'.$color.';">'.date('Y-m-d H:i:s',time()).' ['.$_SESSION['member_id'].']<br>'.$text.$target.'<br>'.print_r ($data, true).'</pre>';
            $destination = FILE_PATH.PATH.'errors.html';
            error_log ($message, 3, $destination);

    //         $message = '
    //           '.$text.$target.'
    //           '.print_r ($data, true);
    //         error_log ($message, 0);

    //         echo $message;
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
        status_scope = "'.mysql_real_escape_string($scope).'"
        AND status_key = "'.mysql_real_escape_string($key).'"';
    $result = @mysql_query($query, $connection) or die (
      debug_print ("ERROR: 760765 ", array(
        'Level' => 'FATAL',
        'Scope' => 'Database',
        'File ' => __FILE__.' at line '.__LINE__,
        'Details' => array (
          'MySQL Error' => mysql_errno(),
          'Message' => mysql_error(),
          'Query' => $query))));
    if ($row = mysql_fetch_object($result))
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
        status_scope = "'.mysql_real_escape_string($scope).'",
        status_key = "'.mysql_real_escape_string($key).'",
        status_value = "'.mysql_real_escape_string($value).'",
        ttl_minutes = "'.mysql_real_escape_string($ttl_minutes).'"
      ON DUPLICATE KEY UPDATE
        status_value = "'.mysql_real_escape_string($value).'"';
    $result = @mysql_query($query, $connection) or die (
      debug_print ("ERROR: 822345 ", array(
        'Level' => 'FATAL',
        'Scope' => 'Database',
        'File ' => __FILE__.' at line '.__LINE__,
        'Details' => array (
          'MySQL Error' => mysql_errno(),
          'Message' => mysql_error(),
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
        ( status_scope = "'.mysql_real_escape_string($scope).'"
          AND status_key = "'.mysql_real_escape_string($key).'")
        OR TIMESTAMPADD(MINUTE, ttl_minutes, timestamp) < NOW()';
    $result = @mysql_query($query, $connection) or die (
      debug_print ("ERROR: 876924 ", array(
        'Level' => 'FATAL',
        'Scope' => 'Database',
        'File ' => __FILE__.' at line '.__LINE__,
        'Details' => array (
          'MySQL Error' => mysql_errno(),
          'Message' => mysql_error(),
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
    $result = mysql_query($query, $connection) or die(
      debug_print ("ERROR: 752930 ", array(
        'Level' => 'FATAL',
        'Scope' => 'Database',
        'File ' => __FILE__.' at line '.__LINE__,
        'Details' => array (
          'MySQL Error' => mysql_errno(),
          'Message' => mysql_error(),
          'Query' => $query))));
    return mysql_insert_id();
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
        $src = PATH.'get_image.php?image_id='.$image_id;
      }
    // ... or use the legacy system to access the dabase directly and nothing more.
    else
      {
        $src = PATH.'getimage.php?image_id='.$image_id;
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
function br2nl($text)
  {
    return preg_replace('/<br\\s*?\/??>/i', "\n", $text);
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