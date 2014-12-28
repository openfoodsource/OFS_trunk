<?php
include_once 'config_openfood.php';

function prdcr_info ($producer_id, $producer_link)
  {
    global $connection;
    // Figure out what identity to query against
    if ($producer_id > 0)
      {
        $where_producer = TABLE_PRODUCER.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"';
      }
    elseif (strlen ($producer_link) > 0)
      {
        $where_producer = TABLE_PRODUCER.'.producer_link = "'.mysql_real_escape_string ($producer_link).'"';
      }
    else
      {
        return '';
      }
    $query = '
      SELECT
        '.TABLE_PRODUCER.'.*,
        '.TABLE_MEMBER.'.*
      FROM
        '.TABLE_PRODUCER.'
      LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
      WHERE
        '.$where_producer.'
        AND '.TABLE_PRODUCER.'.pending = 0
        AND '.TABLE_PRODUCER.'.unlisted_producer != 2
      ORDER BY
        '.TABLE_MEMBER.'.business_name ASC';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 828135 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysql_fetch_array($result) )
      {
        $producer_id = $row['producer_id'];
        $business_name =  $row['business_name'];
        $producttypes =  $row['producttypes'];
        $about =  $row['about'];
        $general_practices =  $row['general_practices'];
        $ingredients =  $row['ingredients'];
        $highlights =  $row['highlights'];
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
        $county = $row['county'];
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
        $query2 = '
          SELECT
            '.TABLE_PRODUCER_LOGOS.'.*
          FROM
            '.TABLE_PRODUCER_LOGOS.'
          WHERE
            '.TABLE_PRODUCER_LOGOS.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"';
        $result2 = @mysql_query($query2, $connection) or die(debug_print ("ERROR: 759323 ", array ($query2,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        while ( $row = mysql_fetch_array($result2) )
          {
            $logo_id = $row['logo_id'];
            $logo_desc = $row['logo_desc'];
            if ( $logo_id )
              {
                $display_logo = '<td width="150" align="left">
                  <img src="'.PATH.'/func/getlogos.php?logo_id='.$logo_id.'" width="150" hspace="5" alt="'.$logo_desc.'"></td>';
              }
          }
        $display .= '
          <div align="right"><a href="prdcr_list.php">Back to Producers List</a></div>
          <table align="center" width="95%" cellpadding="10" cellspacing="2" border="1" bordercolor="#000000" bgcolor="#ffffff">
            <tr><td bgcolor="#DDDDDD" align="left">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#DDDDDD">
              <tr>
                '.$display_logo.'
              <td align="left">
            <font face="arial" size="4">
            '.$business_name.'<br></font>';
        if (PRDCR_INFO_PUBLIC || ($_SESSION['member_id']))
          {
            if ( $address_line1 &&
                 $pub_address &&
                 PRODUCER_PUB_ADDRESS != 'DENIED' &&
                 strpos (' '.PRODUCER_DISPLAY_ADDRESS, 'STREET') )
              {
                $display .= $address_line1.'<br>';
              }
            if ( $address_line2 &&
                 $pub_address &&
                 PRODUCER_PUB_ADDRESS != 'DENIED' &&
                 strpos (' '.PRODUCER_DISPLAY_ADDRESS, 'STREET') )
              {
                $display .= $address_line2.'<br>';
              }
            if ( $city &&
                 $pub_address &&
                 PRODUCER_PUB_ADDRESS != 'DENIED' &&
                 strpos (' '.PRODUCER_DISPLAY_ADDRESS, 'CITY') )
              {
                $display .= $city.', '.$state;
                if ( $city &&
                     $pub_address &&
                     strpos (' '.PRODUCER_DISPLAY_ADDRESS, 'ZIP') )
                  {
                    $display .= ' '.$zip;
                  }
                $display .= '<br>';
              }
            if ( $county &&
                 $pub_address &&
                 strpos (' '.PRODUCER_DISPLAY_ADDRESS, 'COUNTY') )
              {
                $display .= $county.' County<br>';
              }
          }
        $display .= '
            </font><br>
              </td><td align="right">'.$_GLOBALS['font'];
        if (PRDCR_INFO_PUBLIC || ($_SESSION['member_id']))
          {
            if ( $email_address &&
                 $pub_email &&
                 PRODUCER_PUB_EMAIL != 'DENIED' )
              {
                $display .= '<a href="mailto:'.$email_address.'">'.$email_address.'</a><br>';
              }
            if ($email_address_2 &&
                $pub_email2 &&
                PRODUCER_PUB_EMAIL2 != 'DENIED')
              {
                $display .= '<a href="mailto:'.$email_address_2.'">'.$email_address_2.'</a><br>';
              }
            if ( $home_phone &&
                 $pub_phoneh &&
                PRODUCER_PUB_PHONEH != 'DENIED' )
              {
                $display .= $home_phone .' (home)<br>';
              }
            if ( $work_phone &&
                 $pub_phonew &&
                PRODUCER_PUB_PHONEW != 'DENIED' )
              {
                $display .= $work_phone .' (work)<br>';
              }
            if ( $mobile_phone &&
                 $pub_phonec &&
                PRODUCER_PUB_PHONEC != 'DENIED' )
              {
                $display .= $mobile_phone .' (cell)<br>';
              }
            if ( $fax &&
                 $pub_fax &&
                PRODUCER_PUB_FAX != 'DENIED' )
              {
                $display .= $fax .'(fax)<br>';
              }
            if ( $toll_free &&
                 $pub_phonet &&
                PRODUCER_PUB_PHONET != 'DENIED' )
              {
                $display .= $toll_free .' (toll free)<br>';
              }
            if ( $home_page &&
                 $pub_web &&
                PRODUCER_PUB_WEB != 'DENIED' )
              {
                $display .= '<a href="http://'.$home_page.'" target="_blank">'.$home_page.'</a><br>';
              }
          }
        $display .= '
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td align="left">';
        if ( $producttypes )
          {
            $display .= '
            <font face="arial" size="3"><b>Product Types: </b></font>
            <font face="arial" size="-1">'.$producttypes.'</font><br><br>';
          }
        if ( $about )
          {
            $display .= '
            <font face="arial" size="3"><b>About Us</b></font><br>
            <font face="arial" size="-1">'.$about.'</font><br><br>';
          }
        if ( $ingredients )
          {
            $display .= '
            <font face="arial" size="3"><b>Ingredients</b></font><br>
            <font face="arial" size="-1">'.$ingredients.'</font><br><br>';
           }
        if ( $general_practices )
          {
            $display .= '
            <font face="arial" size="3"><b>Practices (our standards for raising or making our products)</b></font><br>
              <font face="arial" size="-1">'.$general_practices.'</font><br><br>';
          }
        if ( $additional )
          {
            $display .= '
            <font face="arial" size="3"><b>Additional Information</b></font><br>
            <font face="arial" size="-1">'.$additional.'</font><br><br>';
          }
        if ( $highlights )
          {
            $display .= '
            <font face="arial" size="3"><b>Highlights this Month</b></font><br>
            <font face="arial" size="-1">'.$highlights.'</font>';
          }
        $display .= '<br><div align="right"><a href="'.PATH.'prdcr_display_quest.php?pid='.$producer_id.'" target="_blank">View answers to original producer questionnaire</a></div>';
        $display .= '</td></tr></table>';
      }
    return $display;
  }
?>
