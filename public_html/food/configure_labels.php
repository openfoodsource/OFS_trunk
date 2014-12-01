<?php

require_once ("../func/label_config.class.php");
session_start();
$show_list = true;
$defined_labels = array();
if (! $_SESSION['return_target'])
  {
    $_SESSION['return_target'] = $_SERVER['HTTP_REFERER'];
  }

if ($_GET['label_name'])
  {
    $_GET['label_name'] = strtr (base64_encode ($_GET['label_name']), '=', '-');
  }
if ($_POST['label_name'])
  {
    $_POST['label_name'] = strtr (base64_encode ($_POST['label_name']), '=', '-');
  }

// Get any already-stored printer configuration data
if ($_COOKIE['defined_labels'])
  {
    $defined_labels = explode ('*',$_COOKIE['defined_labels']);
  }

// Check whether we have selected a label and record that fact in the session
if ($_GET['action'] == "select")
  {
    $_SESSION['label_select'] = $_GET['label_name'];
    $show_list = false;
    header( "Location:".$_SESSION['return_target']);
    $_SESSION['return_target'] = '';
  }

// Check... what?
if ($_POST['action'] == "YES")
  {
    $label_name = $_POST['label_name'];
    $defined_labels = array_diff($defined_labels, array ($label_name));
    $cookie_defined_labels = implode ('*', array_keys (array_flip ($defined_labels)));
    setcookie ('defined_labels', $cookie_defined_labels, time() + 3600 * 24 * 300);
    setcookie ($label_name, "", 0);
  }

// Check whether we need to delete this label
if ($_GET['action'] == "delete_query")
  {
    $label_name = $_GET['label_name'];
    echo '
      <table width="100%" height="100%"><tr><td valign="center" align="center">
      Do you really want to delete the label:<br><br><font size="+2">'.base64_decode (strtr ($label_name, '-', '=')).'</font><br><br>
      <form action="'.$_SERVER['SCRIPT_NAME'].'" method="post">
      <input type="hidden" name="label_name" value="'.base64_decode (strtr ($label_name, '-', '=')).'">
      <input type="submit" name="action" value="YES">
      <input type="submit" name="action" value="NO">
      </form>
      </td></tr></table>
      ';
    $show_list = false;
  }

// Check whether we just received a printer/label configuration
//$label_name = preg_replace ("/[^0-9A-Za-z\-\+]/","", $_GET['label_name']);
$label_name = $_GET['label_name'];
if ($_GET['action'] == "set_config" && $label_name != '')
  {
    $number_of_columns = round (preg_replace ("/[^0-9\.\-]/","", $_GET['number_of_columns']), 3);
    $label_width = round (preg_replace ("/[^0-9\.\-]/","", $_GET['label_width']), 3);
    $horiz_spacing = round (preg_replace ("/[^0-9\.\-]/","", $_GET['horiz_spacing']), 3);
    $number_of_rows = round (preg_replace ("/[^0-9\.\-]/","", $_GET['number_of_rows']), 3);
    $label_height = round (preg_replace ("/[^0-9\.\-]/","", $_GET['label_height']), 3);
    $vert_spacing = round (preg_replace ("/[^0-9\.\-]/","", $_GET['vert_spacing']), 3);
    $page_top_margin = round (preg_replace ("/[^0-9\.\-]/","", $_GET['page_top_margin']), 3);
    $page_left_margin = round (preg_replace ("/[^0-9\.\-]/","", $_GET['page_left_margin']), 3);
    $label_margin = round (preg_replace ("/[^0-9\.\-]/","", $_GET['label_margin']), 3);
    $font_scaling = round (preg_replace ("/[^0-9\.]/","", $_GET['font_scaling']), 3);

    array_push ($defined_labels, $label_name);
    $cookie_defined_labels = implode ('*', array_keys (array_flip ($defined_labels)));
    $cookie_defined_label_config = implode ('~', array ($number_of_columns, $label_width, $horiz_spacing, $number_of_rows, $label_height, $vert_spacing, $page_top_margin, $page_left_margin, $label_margin, $font_scaling));
    setcookie ($label_name, $cookie_defined_label_config, time() + 3600 * 24 * 300);
    setcookie ('defined_labels', $cookie_defined_labels, time() + 3600 * 24 * 300);
  }

// Display the currently configured printers
if ($show_list == true)
  {
    echo '
      <h3>Label Selection and Configuration</h3>
      <table style="border:1px solid #aaa;">';
    foreach (array_keys (array_flip ($defined_labels)) as $defined_label)
      {
        echo '
          <tr style="background-color:#ddc;">
            <td style="font-size:1.7em;text-align:center;padding:0.1em 1em;">'.base64_decode (strtr ($defined_label, '-', '=')).'</td>
            <td style="text-align:center;padding:0.5em 3em;"><a href="'.$_SERVER['SCRIPT_NAME'].'?action=configure&label_name='.base64_decode (strtr ($defined_label, '-', '=')).'">Configure<br>label</a></td>
            <td style="text-align:center;padding:0.5em 3em;"><a href="'.$_SERVER['SCRIPT_NAME'].'?action=select&label_name='.base64_decode (strtr ($defined_label, '-', '=')).'">Select<br>label</a></td>
            <td style="text-align:center;padding:0.5em 3em;"><a href="'.$_SERVER['SCRIPT_NAME'].'?action=delete_query&label_name='.base64_decode (strtr ($defined_label, '-', '=')).'">Delete<br>label</a></td>
          </tr>';
      }
    echo '
      </table><br>';
    if ($_GET['action'] != 'configure')
      {
        echo '
          Add a <a href="'.$_SERVER['SCRIPT_NAME'].'?action=configure">new label</a> type.
          <br><br>';
      }
    echo '


    <div id="instr_head">
    <a href="#" onClick=\'{document.getElementById("instructions").style.display="";document.getElementById("instr_head").style.display="none";}\'>VIEW INSTRUCTIONS</a><br><br>
    </div>

    <div id="instructions" style="display:none";>
    <a href="#" onClick=\'{document.getElementById("instr_head").style.display="";document.getElementById("instructions").style.display="none";}\'>HIDE INSTRUCTIONS</a><br><br>
    This tool is used to configure labels for printing.  Begin by
    choosing the &quot;new label&quot; link above.  You will then be given a list
    of configuration options for the label sheet you will be using.  The printer/label
    name should match the printing setup you will be using (e.g. Lexmark-Avery8160).
    This is just a name for your convenience and does not matter to the system.  For
    multiple printers, it may be a good idea to include the printer name in the label
    because the configuration for the same labels may vary from one printer to another.
    <br>
    <br>
    During label configuration, please enter all units in &quot;inches&quot;.  To save
    wasting label sheets, you might want to print &quot;page 2&quot; of the configuration
    screen on regular paper and hold it up to the light against your label sheet to see
    how well it matches.  For greatest flexibility, it is advised that you set the margins
    on your browser print dialogue as small as they will go and make all necessary adjustments
    from this form. NOTE:  Most measurements should be correct as measured on the label sheet
    but, because of the printer minimimum margin sizes, the top and left margins probably
    <em>will not</em> be correct.  Once you print a test sheet, adjust the top and left
    margins accordingly to move the labels to the correct relative position.
    <br>
    <br>
    Once you have the labels configured the way you want, choose the &quot;Select label&quot;
    link to proceed.  Label configurations are stored in a cookie on your computer and
    will remain for a year from their last update or until deleted.  To create a new label,
    you may begin with an existing label and change the name before choosing the &quot;Set
    values&quot; button.
    <br><br></div>';
  }



if (! $_COOKIE['defined_labels'] || $_GET['action'] == 'configure' || $_GET['action'] == 'set_config')
  {
    if ($_GET['action'] == 'configure')
      {
        list ($number_of_columns,
              $label_width,
              $horiz_spacing,
              $number_of_rows,
              $label_height,
              $vert_spacing,
              $page_top_margin,
              $page_left_margin,
              $label_margin,
              $font_scaling) = explode ('~', $_COOKIE[$_GET['label_name']]);
      }
    if (! $font_scaling)
      {
        $font_scaling = '1.0';
      };
    echo '
      <form action="'.$_SERVER['SCRIPT_NAME'].'" method="get">
      <input type="hidden" name="action" value="set_config">
      <table style="background-color:#ddc;border:1px solid #888;">
        <tr>
          <td>Printer/Label Name</td>
          <td colspan="2"><input type="text" name="label_name" size="45" value="'.base64_decode (strtr ($label_name, '-', '=')).'"></td>
        </tr>
        <tr>
          <td><strong>N</strong>umber of <strong>Col</strong>umn<strong>s</strong></td>
          <td><input type="text" name="number_of_columns" size="5" value="'.$number_of_columns.'"></td>
          <td rowspan="10"><img src="'.DIR_GRAPHICS.'label_calibration.gif"</td>
        </tr>
        <tr>
          <td>Label <strong>Width</strong></td>
          <td><input type="text" name="label_width" size="5" value="'.$label_width.'"> in.</td>
        </tr>
        <tr>
          <td><strong>H</strong>orizontal <strong>Spacing</strong></td>
          <td><input type="text" name="horiz_spacing" size="5" value="'.$horiz_spacing.'"> in.</td>
        </tr>
        <tr>
          <td><strong>Num</strong>ber of <strong>Rows</strong></td>
          <td><input type="text" name="number_of_rows" size="5" value="'.$number_of_rows.'"></td>
        </tr>
        <tr>
          <td>Label <strong>Height</strong></td>
          <td><input type="text" name="label_height" size="5" value="'.$label_height.'"> in.</td>
        </tr>
        <tr>
          <td><strong>V</strong>ertical <strong>Spacing</strong></td>
          <td><input type="text" name="vert_spacing" size="5" value="'.$vert_spacing.'"> in.</td>
        </tr>
        <tr>
          <td>* Page <strong>Top Margin</strong></td>
          <td><input type="text" name="page_top_margin" size="5" value="'.$page_top_margin.'"> in.</td>
        </tr>
        <tr>
          <td>* Page <strong>Left Margin</strong></td>
          <td><input type="text" name="page_left_margin" size="5" value="'.$page_left_margin.'"> in.</td>
        </tr>
        <tr>
          <td><strong>Label Margin</strong></td>
          <td><input type="text" name="label_margin" size="5" value="'.$label_margin.'"> in.</td>
        </tr>
        <tr>
          <td>** Font Scaling</td>
          <td><input type="text" name="font_scaling" size="5" value="'.$font_scaling.'"> x</td>
        </tr>
        <tr>
          <td colspan="2"><p style="font-size:0.8em;padding-left:2em;">* Top and left margins may differ from measured values.</p></td>
        </tr>
        <tr>
          <td colspan="2"><p style="font-size:0.8em;padding-left:2em;">** Font scaling can be used to make the font smaller (less than 1.0) or larger (greater than 1.0) than default size.</p></td>
        </tr>
      </table>
      <br>
      <input type="submit" name="null" value="Set Values"> &nbsp;
      <input type="reset" name="null" value="Reset">
      </form>';
    echo '
      To test this label alignment, print the page below (probably page #2 or #3, depending on your browser).  You may need to set your margins to zero and turn off header and footer fields (find these in print settings for the browser you are using).<br>';
    if ($_GET['action'] == 'set_config' || $label_name != '')
      {
        $label_test = new output_Label ($number_of_columns, $label_width, $horiz_spacing, $number_of_rows, $label_height, $vert_spacing, $page_top_margin, $page_left_margin, $label_margin, $font_scaling);
        echo '<hr>';
        echo $label_test->printAlignmentPage();
      }
  }
