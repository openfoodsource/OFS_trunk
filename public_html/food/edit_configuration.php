<?php
// Initialize some values
$import_button_text = 'SHOW old configuration data';
$save_button_text = 'SAVE changes';
$preload_from_old_config = false;
$post_changes_to_database = false;
$status_message = '';
$update_queries = array();

// Do we need to preload values from the [old] configuration file?
if (isset ($_POST['action']) && $_POST['action'] == $import_button_text)
  {
    $preload_from_old_config = true;
    $status_message .= '
      Now showing values from the original configuration file. Configuration names with values
      matching current settings are shown in <span class="name_style">this</span> color. Names
      and values where the original configuration is <em>different</em> are shown in
      <span class="from_old_config">this</span> color.';
  }
if (isset ($_POST['action']) && $_POST['action'] == $save_button_text)
  {
    $post_changes_to_database = true;
    $status_message .= '
    Saving changes to the Database. By the time you see this message, the database should already
    be updated. However, it would be a good idea to <a href="'.$_SERVER['SCRIPT_NAME'].'">reload
    this page</a> just to be sure. Names for changed values are shown in <span class="changed">
    this</span> color.';
  }

 // If we need the old configuration values, then include old configuration instead of new one
if ($preload_from_old_config)
  {
    @include_once 'config_openfood.php';
    $database_config['db_prefix'] = DB_PREFIX;
    $database_config['openfood_config'] = 'openfood_config';
  }
include_once 'config_openfood.php';

session_start();
valid_auth('site_admin');

// Set the query for updating the database
function set_update_query($update_queries, $name, $value)
  {
    global $database_config;
    // Create an array of update queries, to be run later
    $query = '
      UPDATE '.$database_config['db_prefix'].$database_config['openfood_config'].'
      SET value = "'.mysql_real_escape_string ($value).'"
      WHERE name = "'.mysql_real_escape_string ($name).'"';
    array_push ($update_queries, $query);
    return $update_queries;
  }

// Begin content generation
$content = '
            <p class="status_message">Make changes and press "'.$save_button_text.'" at the bottom
            of the page. Be aware that some changes (particularly the database table names) can
            severely break your installation. If upgrading from a prior version, with hard-coded
            configuration settings, there is a button at the bottom "'.$import_button_text.'"
            that will display settings from the old configuration file, to aid with the conversion.</p>
            <p class="status_message">NOTE: Items marked with <span class="star">&#9733;</span> are set from
            the override file and may not represent the normal configuration. Override values are shown
            in <span class="from_override">this</span> color.</p>
            <p class="status_message">'.$status_message.'</p>
            <form name="ofs_config" action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
            <div class="ofs_config">';

$query = '
  SELECT
    *
  FROM
    '.$database_config['db_prefix'].$database_config['openfood_config'].'
  ORDER BY
    section,
    name';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 864302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$section_prior = '';
while ($row = mysql_fetch_object($result))
  {
    // If we are preloading from the configuration file, then clobber the $row->value
    if ($preload_from_old_config
        && $row->constant != ''
        && defined ($row->constant))
      {
        // If this is a table, then we need to remove the prefix
        if (substr ($row->constant, 0, 6) == 'TABLE_' || substr ($row->constant, 0, 10) == 'NEW_TABLE_')
          {
            $prefix_length = strlen ($db_prefix);
            if (substr (constant ($row->constant), 0, $prefix_length) == $db_prefix) $constant_value = substr (constant ($row->constant), $prefix_length);
            else $constant_value = constant ($row->constant);
          }
        else $constant_value = constant ($row->constant);
      }





    // Sanitize values to remove quotes and other html-troublesome characters
    $section_header = false;
    // Start a new section, if section has changed
    if ($row->section != $section_prior)
      {
        // Need to close a prior tbody?
        if ($section_prior != '')
          {
            $content .= '
              </div>
              <div class="ofs_config">';
          }
        // Show section heading information if there is an empty name with a description
        if ($row->options == '')
          {
            $content .= '
              <div id="'.strtr (strtolower ($row->section), ' ', '_').'" class="section_head">
                <span class="section">'.$row->section.'</span>
                <span class="description">'.$row->description.'</span>
              </div>';
            $section_header = true;
          }
      }
    // Find out what sort of data type this is and get any options
    $option_data = array_map ('trim', explode ("\n", $row->options));
    $option_type = array_shift ($option_data);
    switch ($option_type)
      {
        // Display input as a checkbox with checked/unchecked options on the next two lines
        case 'checkbox=':
          $stored_value = $row->value;
          // Set up value for overridden data
          if (isset ($override_config[$row->name]))
            {
              if ($override_config[$row->name] != $option_data[1] && $override_config[$row->name] != true) $override_config[$row->name] = $option_data[0];
              else $override_config[$row->name] = $option_data[1];
            }
          // Set up value for old configuration data
          if ($preload_from_old_config)
            {
              if ($constant_value != $option_data[1] && $constant_value != true) $constant_value = $option_data[0];
              else $constant_value = $option_data[1];
            }
          if ($post_changes_to_database)
            {
              if ($_POST[$row->name] == $option_data[1]) $posted_value = $option_data[1];
              else $posted_value = $option_data[0];
              $this_value = $posted_value;
            }
          else
            {
              $this_value = $stored_value;
            }
          $input_field = '<input type="checkbox" class="value" name="'.$row->name.'" value="'.htmlentities ($option_data[1]).'"'.($this_value != $option_data[1] && $this_value != false ? '' : ' checked="checked"').'> '.htmlentities ($option_data[1]).'?';
          break;

        // Display input as a text area
        case 'text_area':
          $stored_value = $row->value;
          if ($post_changes_to_database && $_POST[$row->name] != $row->value)
            {
              $posted_value = $_POST[$row->name];
              $this_value = $posted_value;
            }
          else
            {
              $this_value = $stored_value;
            }
          $input_field = '
                  <textarea class="value" name="'.$row->name.'">'.htmlentities ($this_value).'</textarea>';
          break;

        // Display input as a text field with a validation pattern
        case 'input_pattern=':
          $stored_value = $row->value;
          if ($post_changes_to_database && $_POST[$row->name] != $row->value)
            {
              $posted_value = $_POST[$row->name];
              $this_value = $posted_value;
            }
          else
            {
              $this_value = $stored_value;
            }
          $pattern = implode ('', $option_data); // first line after "input_pattern="
          $input_field .= '
                  <input type="text" class="value" name="'.$row->name.'" value="'.htmlentities ($this_value).'" pattern="'.$pattern.'">';
          break;

        // Display input as a text-input with hint-options on the following lines
        case 'input_options=':
          $stored_value = $row->value;
          if ($post_changes_to_database && $_POST[$row->name] != $row->value)
            {
              $posted_value = $_POST[$row->name];
              $this_value = $posted_value;
            }
          else
            {
              $this_value = $stored_value;
            }
          $input_field = '
                <datalist id="'.$row->name.'_options">
                  <select name="'.$row->name.'">';
          foreach ($option_data as $option)
            {
              if ($option != '')
                $input_field .= '
                    <option value="'.$option.'">'.$option.'</option>';
            }
          $input_field .= '
                  </select>
                </datalist>
                  <input type="text" class="value" name="'.$row->name.'" value="'.htmlentities ($this_value).'" list="'.$row->name.'_options">';
          break;

        // Display input as a select list with options on the following lines
        case 'select=':
          $stored_value = $row->value;
          if ($post_changes_to_database && $_POST[$row->name] != $row->value)
            {
              $posted_value = $_POST[$row->name];
              $this_value = $posted_value;
            }
          else
            {
              $this_value = $stored_value;
            }
          $input_field = '
                  <select name="'.$row->name.'">';
          foreach ($option_data as $option)
            {
              if ($option != '')
                $input_field .= '
                    <option value="'.htmlentities ($option).'"'.($option == $this_value ? ' selected="selected"' : '').'>'.htmlentities ($option).'</option>';
            }
          $input_field .= '
                  </select>';
          break;

        // Display input as a multi-select list options on the following lines; result will be comma-separated values
        case 'multi_options=':
          $stored_value = $row->value;
          if ($post_changes_to_database
              && ((is_array ($_POST[$row->name]) && implode (',', $_POST[$row->name]) != $row->value)
              || (is_string ($_POST[$row->name]) && $_POST[$row->name] != $row->value)))
            {
              if (is_array ($_POST[$row->name])) $posted_value = implode (',', $_POST[$row->name]);
              else $posted_value = $_POST[$row->name];
              $this_value = $posted_value;
            }
          else
            {
              $this_value = $stored_value;
            }
          $values = explode (',', $this_value);
          $input_field = '
                  <select name="'.$row->name.'[]" multiple size="5">';
          foreach ($option_data as $option)
            {
              if ($option != '')
                $input_field .= '
                    <option value="'.htmlentities ($option).'"'.(in_array ($option, $values) ? ' selected="selected"' : '').'>'.htmlentities ($option).'</option>';
            }
          $input_field .= '
                  </select>';
          break;

        // Display input as a hidden field with value displayed as text
        case 'read_only=':
          $stored_value = $row->value;
          $posted_value = $stored_value;
          $this_value = $stored_value;
          // This value is never changed, so we won't even look for a change.
          $input_field .= '
                  <input type="hidden" class="value" name="'.$row->name.'" value="'.htmlentities ($this_value).'">
                  '.$row->value;
          break;

        // If no option_type was chosen, then just clear the values
        default:
          $pattern = '';
          $input_field = '';
          $form_validation = '';
//           $stored_value = '';
//           $posted_value = '';
//           $this_value = '';
          break;
      }

    // If there was a change for this item, set the update query
    if ($this_value != $stored_value)
      {
        $update_queries = set_update_query($update_queries, $row->name, $posted_value);
        $changed_class = ' changed';
      }
    else
      {
        $changed_class = '';
      }

    // Handle data that is pre-loaded from the old configuration file
    if ($preload_from_old_config
        && $row->constant != ''
        && defined ($row->constant))
      {
        if ($constant_value != $row->value)
          {
            $old_config_content = '<div class="alt_info">Old configuration:<div class="alt_value from_old_config">'.$constant_value.'</div></div>';
            // $row->value = $constant_value; // Uncomment this to prefill form elements with old values
            $from_old_config_style = ' from_old_config';
          }
        else
          {
            $old_config_class = '';
            $old_config_content = '';
            $from_old_config_style = '';
          }
      }

    // If an override value has been set, then flag and display the override values
    if (isset ($override_config[$row->name]))
      {
        $overrride_is_set = true;
        $override_flag = '<span class="star">&#9733; </span>';
        $override_content = '<div class="alt_info">Overridden as:<div class="alt_value from_override">'.$override_config[$row->name].'</div></div>';
      }
    else
      {
        $overrride_is_set = false;
        $override_flag = '';
        $override_content = '';
      }

    // Create the display and add it to the page content
    if ($section_header != true)
      {
        $content .= '
              <div id="'.$row->name.'" class="config_row">
                <div class="section">'.$row->section.'</div>
                <div class="constant">'.$row->constant.'</div>
                <fieldset><label class="name name_style'.$changed_class.$from_old_config_style.'">'.
                  $override_flag.$row->name.'</label>'.
                  $input_field.'<br />'.
                  $override_content.
                  $old_config_content.'
                </fieldset>
                <div class="description">'.$row->description.'</div>
              </div>';
      }
  // Keep track of prior sections for page formatting
  $input_field = '';
  $section_prior = $row->section;
  }

// Now close the the last data section if there were any sections to begin with
if ($section_prior != '')
  {
    $content .= '
            </div>';
  }
// Only show the button for including the old configuration if the file exists
if (stream_resolve_include_path ('config_foodcoop.php'))
  {
    $content .= '
            <input type="submit" name="action" value="'.$import_button_text.'">';
  }
// and close the page form
$content .= '
            <input type="submit" name="action" value="'.$save_button_text.'">
          </form>';

// Now that we're almost done, go back and post all new data values
if (count ($update_queries) > 0)
  {    
    while ($query = array_shift ($update_queries))
      {
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 758932 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
      }
  }

$page_specific_css = '
  <style type="text/css">
    .status_message {
      color:#000;
      background-color:#fff;
      margin:0.5em 5em;
      }
    .ofs_config {
      clear:both;
      display:table;
      width:100%;
      border:1px solid #000;
      margin-bottom: 10px;
      }
    .name_style {
      color:#462;
      }
    .section_head {
       display:table-header-group;
       width:100%;
       background-color:#fed;
       }
    .section_head .section {
       font-weight:bold;
       padding:4px;
       }
    .section_head .description {
       font-weight:normal;
       margin-left:5px;
       }
    .config_row {
      display:block;
      overflow:hidden;
      border-top:1px solid #aaa;
      padding: 4px;
      }
    .config_row .constant,
    .config_row .section {
      display:none;
      }
    .config_row:hover .constant {
      display:initial;
      position:absolute;
      right:2em;
      margin-top:-2em;
      padding:5px;
      border-top:1px solid #aaa;
      border-left:1px solid #aaa;
      box-shadow:6px 6px 6px #888;
      background-color:#ffa;
      color:#462;
      }
    .changed {
      color:#800;
      }
    .alt_info {
      clear:both;
      text-align:right;
      font-size:75%;
      font-family:monospace;
      padding:0.3em 0 0;
      }
    .alt_value {
      width:60%;
      float:right;
      text-align:left;
      font-family:monospace;
      }
    .from_old_config {
      color:#368;
      }
    .from_override {
      color:#863;
      }
    fieldset {
      float:left;
      margin:0;
      padding:0;
      clear:both;
      width:50%;
      border:0;
      }
    .name {
      float:left;
      display:block;
      width:40%;
      font-size:80%;
      text-align:right;
      font-weight:bold;
      padding-right:5px;
      }
    .star {
      font-size:120%;
      color:#ca0;
      }
    fieldset .value {
      float:left;
      width:55%;
      }
    fieldset textarea.value {
      height:6em;
      }
    .config_row .description {
      float:left;
      width:50%;
      font-size:80%;
      color:#420;
      }
    input[type=checkbox] {
      margin:5px 5px 0;
      width:2em;
      }
    input[type=text] {
      border:1px solid #000;
      background-color:#f5f5f5;
      }
  </style>';

$page_title_html = '<span class="title">Site Admin</span>';
$page_subtitle_html = '<span class="subtitle">Configure Open Food Source</span>';
$page_title = 'Configure Open Food Source';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
?>