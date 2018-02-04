<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');


$date_today = date("F j, Y");
// Check if auth_type = producer_admin and there is a producer_id provided
if(CurrentMember::auth_type('producer_admin') && $_GET['producer_id'])
  {
    // Keep the same producer_id value
    $producer_id = $_GET['producer_id'];
  }
elseif ($_SESSION['producer_id_you'])
  {
    $producer_id = $_SESSION['producer_id_you'];
  }

if ( $_REQUEST['producer_submit'] )
  {
    $sql = '
      UPDATE
        '.TABLE_PRODUCER.'
      SET
        producttypes = "'.mysqli_real_escape_string ($connection, $_REQUEST['producttypes']).'",
        about = "'.mysqli_real_escape_string ($connection, nl2br2 ($_REQUEST['about'])).'",
        general_practices = "'.mysqli_real_escape_string ($connection, nl2br2 ($_REQUEST['practices'])).'",
        ingredients = "'.mysqli_real_escape_string ($connection, nl2br2 ($_REQUEST['ingredients'])).'",
        additional = "'.mysqli_real_escape_string ($connection, nl2br2 ($_REQUEST['additional'])).'",
        highlights = "'.mysqli_real_escape_string ($connection, nl2br2 ($_REQUEST['highlights'])).'"
      WHERE
        producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
    $result = mysqli_query ($connection, $sql) or die (debug_print ("ERROR: 798402 ", array ($sql, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    $message = "<font color=#3333FF><b>Your information has been updated</b></font><br>";
  }

$sqlr = '
  SELECT
    '.TABLE_PRODUCER.'.*,
    '.TABLE_PRODUCER.'.business_name AS prod_business_name,
    '.TABLE_MEMBER.'.*
  FROM
    '.TABLE_PRODUCER.',
    '.TABLE_MEMBER.'
  WHERE
    '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
    AND '.TABLE_PRODUCER.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"
  ORDER BY
    '.TABLE_PRODUCER.'.business_name ASC';
$rsr = @mysqli_query ($connection, $sqlr) or die (debug_print ("ERROR: 542321 ", array ($sqlr, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysqli_fetch_array ($rsr, MYSQLI_ASSOC) )
  {
    $business_name = $row['prod_business_name'];
    $producer_link = $row['producer_link'];
    $producttypes = $row['producttypes'];
    $about = $row['about'];
    $general_practices = $row['general_practices'];
    $ingredients = $row['ingredients'];
    $highlights = $row['highlights'];
    $additional = $row['additional'];

    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $address_line1 = $row['address_line1'];
    $address_line2 = $row['address_line2'];
    $city = $row['city'];
    $state = $row['state'];
    $zip = $row['zip'];
    $email_address = $row['email_address'];
    $email_address_2 = $row['email_address_2'];
    $home_phone = $row['home_phone'];
    $work_phone = $row['work_phone'];
    $mobile_phone = $row['mobile_phone'];
    $fax = $row['fax'];
    $toll_free = $row['toll_free'];
    $home_page = $row['home_page'];
    $membership_date = $row['membership_date'];
    $pub_address = $row['pub_address'];
    $pub_email = $row['pub_email'];
    $pub_email2 = $row['pub_email2'];
    $pub_phoneh = $row['pub_phoneh'];
    $pub_phonew = $row['pub_phonew'];
    $pub_phonec = $row['pub_phonec'];
    $pub_phonet = $row['pub_phonet'];
    $pub_fax = $row['pub_fax'];
    $pub_web = $row['pub_web'];

    $display_logo = '';

    $sqll = '
      SELECT
        *
      FROM
        '.TABLE_PRODUCER_LOGOS.'
      WHERE
        '.TABLE_PRODUCER_LOGOS.'.producer_id = "'.mysqli_real_escape_string ($connection, $producer_id).'"';
    $rsrl = @mysqli_query ($connection, $sqll) or die (debug_print ("ERROR: 239881 ", array ($sqll, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysqli_fetch_array ($rsrl, MYSQLI_ASSOC))
      {
        $logo_id = $row['logo_id'];
        $logo_desc = $row['logo_desc'];

        if ( $logo_id )
          {
            $display_logo = '<td width="150">
              <img src="show_logo.php?logo_id='.$logo_id.'" width="150" hspace="5" alt="'.$logo_desc.'"></td>';
          }
      }

    $display .= '
      <table width="100%" cellpadding="10" cellspacing="2" border="1" bordercolor="#000000">
        <tr><td bgcolor="#DDDDDD">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#DDDDDD">
          <tr>
            '.$display_logo.'
          <td>
        <font face="arial" size="4">
        '.$business_name.'<br><br>'.$city.', '.$state.'
        </font><br>
          </td><td>
        <font face="arial" size="-1">
        Click here to <a href="upload.php?producer_id='.$producer_id.'">upload a logo</a>.
        </font><br>
          </td><td align="right"><font face="arial" size="-1">';

    if ($address_line1 && !$pub_address) { $display .= "$address_line1<br>"; }
    if ($address_line2 && !$pub_address) { $display .= "$address_line2<br>"; }
    if ($address_line1 && !$pub_address) { $display .= "$city, $state $zip<br>"; }

    if ($email_address && !$pub_email)
      {
        $display .= '<a href="mailto:'.$email_address.'">'.$email_address.'</a><br>';
      }
    if ($email_address_2 && !$pub_email2)
      {
        $display .= '<a href="mailto:'.$email_address_2.'">'.$email_address_2.'</a><br>';
       }
    if ($home_phone && !$pub_phoneh) { $display .= "$home_phone (home)<br>"; }
    if ($work_phone && !$pub_phonew) { $display .= "$work_phone (work)<br>"; }
    if ($mobile_phone && !$pub_phonec) { $display .= "$mobile_phone (cell)<br>"; }
    if ($fax && !$pub_fax) { $display .= "$fax (fax)<br>"; }
    if ($toll_free && !$pub_phonet) { $display .= "$toll_free (toll free)<br>"; }
    if ($home_page && !$pub_web) { $display .= '<a href="http://'.$home_page.'" target="_blank">'.$home_page.'</a><br>'; }

    $display .= '</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>';
  }

$content_edit = '
  '.$message.'
  '.$display.'
  <div align="right">
    <b><a href="'.PATH.'producers/'.$producer_link.'" target="_blank">Click here to see the live webpage</a></b>
    '.$fulllist_link.'
  </div>

  <blockquote>
    <form method="post" action="'.$_SERVER['SCRIPT_NAME'].'?producer_id='.$producer_id.'">

      <p><font size="3"><b>Product Types:</b></font> (max. 150 characters, use keywords like lettuce, berries, buffalo, soap, etc.)<br>
      <input type="text" name="producttypes" value="'.htmlspecialchars ($producttypes, ENT_QUOTES).'" size="75" maxlength="150">
      </p>

      <p><font size="3"><b>About Us:</b></font> (Use this space to describe your business, you, how you got started, etc.)<br>
      <textarea name="about" cols="75" rows="17">'.htmlspecialchars (br2nl ($about), ENT_QUOTES).'</textarea>
      </p>

      <p><font size="3"><b>Ingredients:</b></font> (Use this space to outline ingredients if relevant.)<br>
      <textarea name="ingredients" cols="75" rows="17">'.htmlspecialchars (br2nl ($ingredients), ENT_QUOTES).'</textarea>
      </p>

      <p><font size="3"><b>Practices:</b></font> (Use this space to describe your standards and practices. For example, if you use all natural products, whether or not the processing of your products is done in a health-inspected kitchen/facility, etc.)<br>
      <textarea name="practices" cols="75" rows="17">'.htmlspecialchars (br2nl ($general_practices), ENT_QUOTES).'</textarea>
      </p>

      <p><font size="3"><b>Additional Information:</b></font> (Use this space for anything that isn&rsquo;t covered in these other sections.)<br>
      <textarea name="additional" cols="75" rows="17">'.htmlspecialchars (br2nl ($additional), ENT_QUOTES).'</textarea>
      </p>

      <p><font size="3"><b>Highlights this Month:</b></font><br>
      <textarea name="highlights" cols="75" rows="17">'.htmlspecialchars (br2nl ($highlights), ENT_QUOTES).'</textarea>
      </p>
  </blockquote>

  <div align="center">
    <input type="hidden" name="producer_id" value="'.$producer_id.'">
    <input class="button" type="submit" name="producer_submit" value="Click here to save your info">
  </div>';

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Edit Producer Info.</span>';
$page_title = $business_name.': Edit Producer Info.';
$page_tab = 'producer_panel';

include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$content_edit.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
