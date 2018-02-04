<?php

include_once 'config_openfood.php';
session_start();
valid_auth('site_admin');

// Initialize some values
$save_button_text = 'Save Changes';
$new_button_text = 'Save New';
$post_changes_to_database = false;
$status_message = '';
$update_queries = array();
if (isset ($_POST['action']) && strlen ($_POST['action']) > 0) $action = $_POST['action'];

// Set the query for updating the database
function set_update_query($update_queries, $subsection, $value)
  {
    global $database_config, $connection;
    // Create an array of update queries, to be run later
    $query = '
      UPDATE '.$database_config['db_prefix'].$database_config['openfood_config'].'
      SET value = "'.mysqli_real_escape_string ($connection, trim ($value)).'"
      WHERE subsection = "'.mysqli_real_escape_string ($connection, $subsection).'"';
    array_push ($update_queries, $query);
    return $update_queries;
  }

// Check if we are configuring the configuration table itself
if (isset ($_GET['config']))
  {
    if (isset ($_GET['subsection'])) $config_subsection = $_GET['subsection'] or $config_subsection = '';
    // Add or update config?
    if ($action == $save_button_text)
      {
        // Do the update query
        $query = '
          UPDATE '.$database_config['db_prefix'].$database_config['openfood_config'].'
          SET
            section = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_section_number'])).'",
            name = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_name'])).'",
            constant = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_constant'])).'",
            options = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_option_type']."\n".$_POST['edit_config_options'])).'",
            value = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_value'])).'",
            description = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_description'])).'"
          WHERE
            subsection = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_subsection_number'])).'"';
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 828431 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        // Prepare to show the value again to confirm the changes
        $config_subsection = trim ($_POST['edit_config_subsection']);
      }
    if ($action == $new_button_text)
      {
        // Do the update query
        $query = '
          INSERT INTO '.$database_config['db_prefix'].$database_config['openfood_config'].'
          SET
            section = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_section_number'])).'",
            /* subsection = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_subsection_number'])).'", SUBSECTION IS THE TABLE KEY */
            name = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_name'])).'",
            constant = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_constant'])).'",
            options = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_option_type']."\n".$_POST['edit_config_options'])).'",
            value = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_value'])).'",
            description = "'.mysqli_real_escape_string ($connection, trim ($_POST['edit_config_description'])).'"';
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 322452 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        // Prepare to show the value again to confirm the changes
        $config_subsection = trim ($_POST['edit_config_subsection']);
      }
    // Default action will be 'add_config'
    $config_action = 'add_config';
    if ($config_subsection != '')
      {
        $query = '
          SELECT
            section,
            subsection,
            name,
            constant,
            options,
            value,
            description
          FROM
            '.$database_config['db_prefix'].$database_config['openfood_config'].'
          WHERE
            subsection="'.mysqli_real_escape_string ($connection, $config_subsection).'";';
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 292021 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        if (! $row = mysqli_fetch_object ($result))
          {
            debug_print ("ERROR: 923782 ", array ('message'=>'No results found for configuration item named '.$_GET['value'], $query), basename(__FILE__).' LINE '.__LINE__);
          }
        $config_action = 'update_config';
      }
    // Display the config-configuration form
    $option_lines = explode ("\n", $row->options);
    $option_type = array_shift ($option_lines);
    $content = '
      <p class="status_message">When finished, press "'.$save_button_text.'" or "'.$new_button_text.'" to update the
      configuration table in the database. Be aware that changing constants can severely break your installation
      of Open Food Source. This interface has minimal error-checking and you are expected to know what you are doing
      when using this form. When adding section headings, be sure to use &ldquo;section_heading&rdquo; for the options.
      The name and constant should preferably be lower- and upper-case versions of the same thing. Return to the
      standard configuration interface <a href="'.$_SERVER['SCRIPT_NAME'].'">here</a>.</p>
      <p class="status_message">'.$status_message.'</p>
      <form name="ofs_config" action="'.$_SERVER['SCRIPT_NAME'].'?config=edit_config&subsection='.$config_subsection.'" method="post">
      <div class="form_buttons">
        <button type="submit" id="action" name="action" value="'.$new_button_text.'">'.$new_button_text.'</button>
        <button type="submit" id="action" name="action" value="'.$save_button_text.'">'.$save_button_text.'</button>
        <button id="reset" value="Reset" name="reset" type="reset">Reset</button>
      </div>
        <fieldset class="'.$changed_class.'">
          <div class="description" onclick="show_hide(\'add_config\');">
            <div id="info_button-add_config" class="info_button">&#9432;</div>
            <div id="info_content-add_config" class="info_content">
              Enter information for the configuration setting that will be made available in the Open Food Source software
              <dl>
                <dt>Section number</dt>
                <dd>Select which section this configuration will be assigned into.</dd>
            <!--
                <dt>Subsection number</dt>
                <dd>Subsection is a table key and will be assigned the next in sequence.</dd>
            -->
                <dt>Name</dt>
                <dd>Name is either the name of a new configuration section, or it is the name of the variable that can be set in the override file. As a variable, it should always be formatted as lower_snake_case.</dd>
                <dt>Constant</dt>
                <dd>Constant is the constant that will be defined for use by Open Food Source. It should always be formatted as CAPITAL_SNAKE_CASE. All constants must be unique, so section headings might use something like SECTION_ONE, SECTION_TWO, etc.</dd>
                <dt>Value</dt>
                <dd>For single line values, they can be assigned here for convenience. With the exception of &ldquo;read_only&rdquo; and &ldquo;section_heading&rdquo; options, they really should be assigned in the main configuration program later.</dd>
                <dt>Option Type and Options</dt>
                <dd>
                  <strong>checkboxes</strong> Enter two lines: Line-1 should be the &ldquo;checked&rdquo; value and Line-2 should be the &ldquo;un-checked&rdquo; value.<br />
                  <strong>input_options</strong> Enter any number of lines that will be used as hints for filling in the text input field. Howerver the field will also allow other values to be entered.<br />
                  <strong>input_pattern</strong> Enter a regular expression that will be used to restrict input values for the text input area.<br />
                  <strong>multi_options</strong> Enter any number of lines, any combination of which may be selected as data values in a select/multi field.<br />
                  <strong>read_only</strong> This is the only place where a value must also be entered. The normal configuration interface will not allow editing this field.<br />
                  <strong>select</strong> Enter any number of lines, any one of which may be selected as data values in a select field.<br />
                  <strong>textarea</strong> There are no options for this field. It will create a textarea for entering configuration information.
                </dd>
                <dt>Description</dt>
                <dd>Use the description field for providing guidance in using the configuration setting. It will be available under the &ldquo;information&rdquo; icon in the normal configuration interface.</dd>
              </dl>
            </div>
          </div>
          <div class="input_block config_section_number">
            <label for="edit_config_section_number">Section</label>
            <input id="edit_config_section_number" name="edit_config_section_number" type="text" value="'.htmlspecialchars (trim ($row->section), ENT_QUOTES).'">
          </div>
      <!--
          <div class="input_block config_subsection_number">
            <label for="edit_config_subsection_number">Subsection</label>
      -->
            <input id="edit_config_subsection_number" name="edit_config_subsection_number" type="hidden" value="'.htmlspecialchars (trim ($row->subsection), ENT_QUOTES).'">
      <!--
          </div>
      -->
          <div class="input_block config_name">
            <label for="edit_config_name">Name</label>
            <input id="edit_config_name" name="edit_config_name" type="text" value="'.htmlspecialchars (trim ($row->name), ENT_QUOTES).'">
          </div>
          <div class="input_block config_constant">
            <label for="edit_config_constant">Constant</label>
            <input id="edit_config_constant" name="edit_config_constant" type="text" value="'.htmlspecialchars (trim ($row->constant), ENT_QUOTES).'">
          </div>
          <div class="input_block config_value">
            <label for="edit_config_value">Value</label>
            <input id="edit_config_value" name="edit_config_value" type="text" value="'.htmlspecialchars (trim ($row->value), ENT_QUOTES).'">
          </div>
          <div class="input_block config_option_group">
            <div class="input_block config_option_type">
              <label for="edit_config_option_type">Option Type</label>
              <select id="edit_config_option_type" name="edit_config_option_type">
                <option value="checkbox="'.($option_type == 'checkbox=' ? ' selected' : '').'>checkbox=</option>
                <option value="input_options="'.($option_type == 'input_options=' ? ' selected' : '').'>input_options=</option>
                <option value="input_pattern="'.($option_type == 'input_pattern=' ? ' selected' : '').'>input_pattern=</option>
                <option value="multi_options="'.($option_type == 'multi_options=' ? ' selected' : '').'>multi_options=</option>
                <option value="read_only"'.($option_type == 'read_only' ? ' selected' : '').'>read_only</option>
                <option value="select="'.($option_type == 'select=' ? ' selected' : '').'>select=</option>
                <option value="text_area"'.($option_type == 'text_area' ? ' selected' : '').'>text_area</option>
                <option value="section_heading"'.($option_type == 'section_heading' ? ' selected' : '').'>section_heading</option>
             </select>
            </div>
            <div class="input_block config_options">
              <label for="edit_config_options">Options</label>
              <textarea id="edit_config_options" name="edit_config_options" type="text">'.htmlspecialchars (trim (implode ("\n", $option_lines)), ENT_QUOTES).'</textarea>
            </div>
          </div>
          <div class="input_block config_description">
            <label for="edit_config_description">Description</label>
            <textarea id="edit_config_description" name="edit_config_description">'.htmlspecialchars (trim ($row->description), ENT_QUOTES).'</textarea>
          </div>
        </fieldset>
      </form>';

    $page_specific_css = '
      .config_section_number {
        display:inline-block;
        width:10%;
        }
      .config_subsection_number {
        display:inline-block;
        width:7%;
        }
      .config_name {
        display:inline-block;
        clear:left;
        width:29%;
        }
      .config_constant {
        display:inline-block;
        clear:left;
        width:29%;
        }
      .config_value {
        display:inline-block;
        clear:left;
        width:29%;
        }
      .config_option_group {
        display:inline-block;
        width:29%;
        height:20rem;
        }
      .config_option_type {
        display:inline-block;
        width:100%;
        text-align:center;
        }
      #edit_config_option_type {
        padding:0.65rem 0;
        margin:2px;
        width:100%;
        background-color:#fff;
        box-shadow:2px 2px 0 0 #bcb;
        }
      .config_options {
        display:block;
        clear:left;
        width:100%;
        }
      .config_description {
        display:inline-block;
        width:69%;
        height:20rem;
        vertical-align: top;
        }
      #edit_config_options {
        height:14rem;
        }
      #edit_config_description {
        height:18.6rem;
        }';
  }
// Otherwise handle the website configuration process
else
  {
    if ($action == $save_button_text)
      {
        $post_changes_to_database = true;
        $status_message .= '
        Changes have been saved to the Database. Names for changed values are shown in <span class="changed">
        THIS</span> color.';
      }
    // Begin content generation
    $content = '
                <p class="status_message">Make changes and press "'.$save_button_text.'." Be aware that some changes
                (particularly the database table names) can severely break your installation of Open Food Source.
                Hover over (or click on) a constant name to expose the configuration settings.</p>
                <p class="status_message">NOTE: Items marked with <span class="star">&#9733;</span> are set from
                the override file and may not represent the normal configuration. Override values are shown
                in <span class="override_value">THIS</span> color.</p>
                <p class="status_message">'.$status_message.'</p>
                <form name="ofs_config" action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
                <div class="form_buttons">
                  <button type="submit" id="action" name="action" value="'.$save_button_text.'">'.$save_button_text.'</button>
                  <button id="reset" value="Reset" name="reset" type="reset">Reset</button>
                </div>
                <div id="ofs_config" class="ofs_config">';
    $stored_value = '';
    $this_value = '';
    $query = '
      SELECT
        section,
        subsection,
        name,
        constant,
        options,
        value,
        description
      FROM
        '.$database_config['db_prefix'].$database_config['openfood_config'].'
      ORDER BY
        section,
        IF (options = "section_heading", "", constant)'; // Always force section headings to the top of the subsection list)
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 868302 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $section_prior = '';
    $first_element = 0;
    while ($row = mysqli_fetch_object ($result))
      {
        // Sanitize values to remove quotes and other html-troublesome characters
        $section_header = false;
        // Start a new section, if section has changed
        if ($row->section != $section_prior)
          {
            // Show section heading information if options='section_heading'
            if ($row->options == 'section_heading')
              {
                $content .= ($first_element++ == 0 ? /* Show Opening accordion (Null configuration) on start of loop */ '
                  <h3 id="accordion_null" class="section_head">
                    <span class="section">Configuration Settings</span>
                    <span class="description">Overview</span>
                  </h3>
                  <div class="accordion_section">
                    <p>Select from the configuration options below to edit those settings. There is no need to save changes before switching to a different section; but be sure to save all changes when finished. If &ldquo;'.$save_button_text.'&rdquo; does not seem to work, there may be a validation failing in one of the sections.</p>'.
                    (IS_DEVELOPER == true ? '
                    <p>Add a <a href="'.$_SERVER['SCRIPT_NAME'].'?config=new_config">new</a> configuration option (this will lose unsaved changes).</p>'
                    : '' ).'
                  </div>'
                  : /* For all other elements: close the prior div before opening the next */ '
                  </div>').'
                  <h3 id="'.strtr (strtolower ($row->name), ' ', '_').'" class="section_head">
                    <span class="section">'.$row->section.'. '.$row->value.'</span>
                    <span class="description">'.$row->description.(IS_DEVELOPER == true ? ' <a class="edit_this" href="'.$_SERVER['SCRIPT_NAME'].'?config=edit_config&subsection='.$row->subsection.'">&#9998;</a>' : '').'</span>
                  </h3>
                  <div class="accordion_section">';
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
              if ($post_changes_to_database && isset ($_POST[$row->name.'_has_checkbox']))
                {
                  if ($_POST[$row->name] == $option_data[1]) $posted_value = $option_data[1];
                  else $posted_value = $option_data[0];
                  $this_value = $posted_value;
                }
              else
                {
                  $this_value = $stored_value;
                }
              $input_field = '
                      <input type="checkbox" class="value" name="'.$row->name.'" value="'.htmlentities ($option_data[1]).'"'.($this_value != $option_data[1] && $this_value != false ? '' : ' checked="checked"').'> '.htmlentities ($option_data[1]).'?
                      <input type="hidden" class="value" name="'.$row->name.'_has_checkbox" value="true">';
              break;
            // Display input as a text area
            case 'text_area':
              $stored_value = $row->value;
              if ($post_changes_to_database && isset ($_POST[$row->name]) && $_POST[$row->name] != $row->value)
                {
                  $posted_value = $_POST[$row->name];
                  $this_value = $posted_value;
                }
              else
                {
                  $this_value = $stored_value;
                }
              // Size the textarea appropriately
              $lines = count (explode ("\n", $this_value)) + 3; // Make textarea approx three lines longer than the content
              if ($lines > 20) $lines = 20 + (($lines - 20) / 5); // Above ~20 lines, give one additional line for every five
              $input_field = '
                      <textarea class="value" name="'.$row->name.'" rows="'.$lines.'">'.htmlentities ($this_value).'</textarea>';
              break;
            // Display input as a text field with a validation pattern
            case 'input_pattern=':
              $stored_value = $row->value;
              if ($post_changes_to_database && isset ($_POST[$row->name]) && $_POST[$row->name] != $row->value)
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
              if ($post_changes_to_database && isset ($_POST[$row->name]) && $_POST[$row->name] != $row->value)
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
              if ($post_changes_to_database && isset ($_POST[$row->name]) && $_POST[$row->name] != $row->value)
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
              $select_height = floor (count ($option_data) / 2) + 2;
              if ($select_height > 20) $select_height = 20; // Keep it from being crazy-large
              $input_field = '
                      <select name="'.$row->name.'[]" multiple style="height:'.$select_height.'em;">';
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
            case 'read_only':
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
            $update_queries = set_update_query($update_queries, $row->subsection, $posted_value);
            $changed_class = ' changed';
          }
        else
          {
            $changed_class = '';
          }
        // If an override value has been set, then flag and display the override values
        if (isset ($override_config[$row->name]))
          {
            $overrride_is_set = true;
            $override_flag = '<span class="star">&#9733; </span>';
          }
        else
          {
            $override_config[$row->name] = '';
            $overrride_is_set = false;
            $override_flag = '';
          }
        // Create this configuration element and add it to the page content
        if ($section_header != true)
          {
            $content .= '
                    <div id="'.$row->name.'" class="config_row">
                      <fieldset class="'.$changed_class.'">
                        <div class="description" onclick="show_hide(\''.$row->name.'\');">
                          <div id="info_button-'.$row->name.'" class="info_button">&#9432;</div>
                          <div id="info_content-'.$row->name.'" class="info_content">'.nl2br ($row->description).(IS_DEVELOPER == true ? ' <a class="edit_this" href="'.$_SERVER['SCRIPT_NAME'].'?config=edit_config&subsection='.$row->subsection.'">&#9998;</a>' : '').'</div>
                        </div>
                        <label class="name" onclick="jQuery(this).closest(\'fieldset\').toggleClass(\'hover\');">
                          <span class="constant">'.$override_flag.$row->constant.
                          ($row->constant != strtoupper ($row->name) ? '
                          <span class="override_with">(override with &apos;'.$row->name.'&apos;)</span>'
                          : '').' </span>
                        </label>
                        <span class="config_setting">'.$input_field.'</span>'.
                        (strlen ($override_config[$row->name]) > 0 ? '<span class="override_text">Overridden as: <span class="override_value">'.$override_config[$row->name].'</span></span>'
                        : '').'
                      </fieldset>
                    </div>';
          }
      // Keep track of prior sections for page formatting
      $input_field = '';
      $section_prior = $row->section;
      }
    $content .= '
                </div>
              </form>';
    // Now that we're almost done, go back and post all new data values
    if (count ($update_queries) > 0)
      {    
        while ($query = array_shift ($update_queries))
          {
            $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 758932 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
          }
      }
    $page_specific_css = '
      .status_message {
        color:#000;
        background-color:#fff;
        margin:0.5em 5em;
        }
      .config_row {
        display:block;
        overflow:hidden;
        padding: 4px;
        }
      .config_row fieldset {
        margin:0.25rem auto;
        }
      .config_row fieldset .override_text,
      .config_row fieldset .config_setting {
        display:none;
        }
      .config_row fieldset.hover .override_text,
      .config_row fieldset:hover .override_text,
      .config_row fieldset.hover .config_setting,
      .config_row fieldset:hover .config_setting {
        display:block;
        }
      .changed {
        background-color:#eee;
        }
      .changed label {
        color:#800;
        }
      .override_text {
        display:block;
        clear:left;
        font-size:75%;
        font-family:monospace;
        padding:0.3em 0 0;
        }
      .override_value {
        color:#863;
        }
      fieldset {
        position:relative;
        margin:0;
        padding:0;
        width:100%;
        border:0;
        }
      .config_row .name {
        display:block;
        width:100%;
        font-size:80%;
        font-weight:bold;
        padding-right:5px;
        }
      .star {
        font-size:120%;
        color:#ca0;
        }
      fieldset .value {
        float:left;
        width:95%;
        }
      fieldset textarea.value {
        font-family:monospace;
        white-space: pre;
        wrap:hard;
        width:100%;
        }
      h3 .section {
        color:#000;
        }
      h3 .section::after {
        content:": ";
        }
      h3 .description {
        }
      .config_row .description {
        font-size:200%;
        color:#369;
        }
      input[type=checkbox] {
        margin:5px 5px 0;
        width:2em;
        }
      input[type=text] {
        border:1px solid #000;
        background-color:#f5f5f5;
        }
    .info_content {
      font-size:50%;
      }
    .edit_this {
      dislay:block;
      width:20px;
      height:20px;
      position:absolute;
      right:0.5rem;
      color:#fed;
      background-color:#444;
      border-radius:3px;
      line-height:20px;
      font-size:20px;
      overflow:hidden;
      z-index:100;
      }';
  }

$page_specific_javascript = '
  jQuery(function() {
    jQuery("#ofs_config").accordion({
      heightStyle: "content",
      collapsible: true
      });
    });
  function show_hide(content_id) {
    jQuery("#info_button-"+content_id).fadeToggle();
    jQuery("#info_content-"+content_id).fadeToggle();
    };';

$page_specific_stylesheets['jquery-ui'] = array (
  'name'=>'jquery-ui',
  'src'=>BASE_URL.PATH.'css/jquery-ui.css',
  'dependencies'=>array(),
  'version'=>'2.1.1',
  'media'=>'all',
  );

$page_specific_css .= '
     #ofs_content a,
    .status_message a {
      background-color:#444;
      color:#fed;
      }
    .info_button {
      position:absolute;
      right:0.5rem;
      top:0;
      font-size:2rem !important;
      background-color:transparent;
      color:#048;
      padding:0 !important;
      cursor:help;
      }
    .info_button:hover {
      background-color:transparent;
      color:#000;
      }
    .info_content {
      display: block;
      position:relative;
      z-index:100;
      width: 100%;
      background-color:#fff;
      color:#048;
      padding: 0.5rem;
      border: 1px solid;
      border-radius: 0.5rem;
      display:none;
      padding-right: 2rem;
      }';

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
