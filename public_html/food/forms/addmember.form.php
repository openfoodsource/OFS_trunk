<?php
$content_members .= '
<div style="margin:auto;width:90%;padding:1em;">
'.$error_html.'
<table width="70%">
  <tr><td align="left">';

if($_GET[action]=="edit")
  {
    $action="member_interface.php?action=checkMemberForm&ID=$_GET[ID]";
    $title="Edit Membership Information";
  }
else
  {
    $action="member_interface.php?action=checkMemberForm&ID=$_GET[ID]";
    $title="Add A New Member";
  }
$content_members .= '
  <form action="'.$action.'" method="post" name="addMember">
  <h3>'.$title.'</h3>

<table id="add_member_table" width="550" border="1" cellpadding="2" cellspacing="2" bordercolor="#333333">
  <caption><font color=#3333FF><b>*</b></font> means it is a required field.</caption>
  <tr bgcolor="#BB0000">
    <td colspan="3"><font face="arial"><b>Personal Info</b></font></td>
  </tr>
  <tr>
    <td align="center">&nbsp;'.($fields["first_name"] == false ? '<font color="#3333FF"><b>*</b></font>' : '').'</td>
    <td width="150" bgcolor="#CCCCCC">First name</td>
    <td width="350" bgcolor="#CCCCCC"> <input name="first_name" type="text" id="first_name4" size="20" maxlength="25" value="'.htmlspecialchars ($result['first_name'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td align="center">&nbsp;'.($fields["last_name"] == false ? '<font color="#3333FF"><b>*</b></font>' : '').'</td>
    <td bgcolor="#CCCCCC">Last name </td>
    <td bgcolor="#CCCCCC"> <input name="last_name" type="text" id="last_name4" size="30" maxlength="25" value="'.htmlspecialchars ($result['last_name'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">First name 2</td>
    <td bgcolor="#CCCCCC"> <input name="first_name_2" type="text" id="first_name_23" size="20" maxlength="25" value="'.htmlspecialchars ($result['first_name_2'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Last Name 2 </td>
    <td bgcolor="#CCCCCC"> <input name="last_name_2" type="text" id="last_name_23" size="30" maxlength="25" value="'.htmlspecialchars ($result['last_name_2'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Preferred Name </td>
    <td bgcolor="#CCCCCC"> <input name="preferred_name" type="text" id="preferred_name" size="30" maxlength="25" value="'.htmlspecialchars ($result['preferred_name'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Phone number (h) </td>
    <td bgcolor="#CCCCCC"> <input name="home_phone" type="text" id="home_phone3" size="15" maxlength="20" value="'.htmlspecialchars ($result['home_phone'], ENT_QUOTES).'"></td>
  </tr>
   <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Phone number (w) </td>
    <td bgcolor="#CCCCCC">
      <input name="work_phone" type="text" id="work_phone3" size="15" maxlength="20" value="'.htmlspecialchars ($result['work_phone'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Mobile phone </td>
    <td bgcolor="#CCCCCC"> <input name="mobile_phone" type="text" id="mobile_phone3" size="15" maxlength="20" value="'.htmlspecialchars ($result['mobile_phone'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Fax </td>
    <td bgcolor="#CCCCCC"> <input name="fax" type="text" id="fax4" size="15" maxlength="20" value="'.htmlspecialchars ($result['fax'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">E-mail address </td>
    <td bgcolor="#CCCCCC"> <input name="email_address" type="text" id="email_address3" size="30" maxlength="100" value="'.htmlspecialchars ($result['email_address'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">E-address (2) </td>
    <td bgcolor="#CCCCCC"> <input name="email_address_2" type="text" id="email_address_24" size="30" maxlength="100" value="'.htmlspecialchars ($result['email_address_2'], ENT_QUOTES).'"></td>
  </tr>
  <tr bgcolor="#BB0000">
    <td colspan="3"><font face="arial"><b>Home Address</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Address (line 1) </td>
    <td bgcolor="#CCCCCC"> <input name="address_line1" type="text" id="address_line13" size="25" maxlength="25" value="'.htmlspecialchars ($result['address_line1'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Address (line 2) </td>
    <td bgcolor="#CCCCCC">
      <input name="address_line2" type="text" id="address_line24" size="25" maxlength="25" value="'.htmlspecialchars ($result['address_line2'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td align="center">&nbsp;'.($fields["city"] == false ? '<font color="#3333FF"><b>*</b></font>' : '').'</td>
    <td colspan="2" bgcolor="#CCCCCC"> <p>City
        <input name="city" type="text" id="city3" size="15" maxlength="15" value="'.htmlspecialchars ($result['city'], ENT_QUOTES).'">State

        <input name="state" type="text" id="state3" size="4" maxlength="2" value="'.htmlspecialchars ($result['state'], ENT_QUOTES).'">Zip

        <input name="zip" type="text" id="zip3" size="12" maxlength="10" value="'.htmlspecialchars ($result['zip'], ENT_QUOTES).'">
        <br>County (optional)
        <input name="county" type="text" id="county" size="8" maxlength="20" value="'.htmlspecialchars ($result['county'], ENT_QUOTES).'">
      </p></td>
  </tr>
  <tr bgcolor="#BB0000">
    <td colspan="3"><font face="arial"><b>Work Address (optional)</b></font></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC" >Address (line 1) </td>
    <td bgcolor="#CCCCCC"> <input name="work_address_line1" type="text" id="address_line13" size="25" maxlength="25" value="'.htmlspecialchars ($result['work_address_line1'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td bgcolor="#CCCCCC">Address (line 2) </td>
    <td bgcolor="#CCCCCC">
      <input name="work_address_line2" type="text" id="address_line24" size="25" maxlength="25" value="'.htmlspecialchars ($result['work_address_line2'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td colspan="2" bgcolor="#CCCCCC"> <p>City
        <input name="work_city" type="text" id="city3" size="15" maxlength="15" value="'.htmlspecialchars ($result['work_city'], ENT_QUOTES).'">
        State
        <input name="work_state" type="text" id="state3" size="4" maxlength="2" value="'.htmlspecialchars ($result['work_state'], ENT_QUOTES).'">
        Zip
        <input name="work_zip" type="text" id="zip3" size="12" maxlength="10" value="'.htmlspecialchars ($result['work_zip'], ENT_QUOTES).'">
      </p></td>
  </tr>

  <tr bgcolor="#BB0000">
    <td colspan="3"><font face="arial"><b>User info</b></font></td>
  </tr>
  <tr>
    <td align="center">&nbsp;'.($fields["username"] == false ? '<font color="#3333FF"><b>*</b></font>' : '').'</td>
    <td bgcolor="#CCCCCC">User name </td>
    <td bgcolor="#CCCCCC"> <input name="username" type="text" id="username" size="20" maxlength="20" value="'.htmlspecialchars ($result['username'], ENT_QUOTES).'"></td>
  </tr>
  <tr>
    <td align="center">&nbsp;'.($fields["password"]==false ? '<font color="#3333FF"><b>*</b></font>' : '').'</td>';

if($_GET[ID] && !$_GET[password])
  {
    $content_members .= '
    <td bgcolor="#CCCCCC" colspan="2">Password stored. <a href="member_interface.php?action=edit&ID='.$_GET[ID].'&password=edit">Click here</a> to edit. <input type="hidden" name="password" value="'.$result['password'].'"></td>';
  }
else
  {
    $content_members .= '
    <td bgcolor="#CCCCCC"';
    if ($fields["password"] == false)
      {
        $content_members .= '
          >Password</td>';
      }
    $content_members .= '
      <td bgcolor="#CCCCCC"> <input name="password" type="password" id="password3" size="15" maxlength="25"> (min 6 characters, no spaces)</td>';
  }
$content_members .= '
  </tr>';
if (!$_GET[ID] || $_GET[password])
  {
    $content_members .= '<tr><td align="center">&nbsp';
    if($fields["password_r"] == false) { $content_members .= '<font color="#3333FF"><b>*</b></font>'; }
    $content_members .= '</td><td bgcolor="#CCCCCC"';
    if($fields["password_r"] == false);
    $content_members .= '>Repeat password </td>
      <td bgcolor="#CCCCCC"><input name="password_r" type="password" id="password_r4" size="15" maxlength="25"></td></tr>';
  }
$content_members .= '
  <tr bgcolor="#BB0000">
    <td colspan="3"><font face="arial"><b>Account Info</b></font></td>
  </tr>

  <!--  ====== DISPLAY MEMBERSHIP TYPE ======== -->
    <tr>
    <td>&nbsp;</td>
    <td colspan="1" bgcolor="#CCCCCC">Membership Type</td>
    <td colspan="1" bgcolor="#CCCCCC">
      <select name="membership_type_id" id="membership_type_id">';
//what is the current membership type?
$current_mem_type = $result['membership_type_id'];
//find all available membership types
$query_mem_types = '
  SELECT
    *
  FROM
    '.TABLE_MEMBERSHIP_TYPES.'
  ORDER BY
    membership_type_id';
$result_query_mem_types = mysql_query($query_mem_types) or die(debug_print ("ERROR: 769303 ", array ($result_query_mem_types,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$total_mem_types = mysql_numrows($result_query_mem_types);
//loop through and display each membership type
$index_mem_types = 0;
while ($index_mem_types < $total_mem_types)
  {
    //determine if this is their current membership type and default the selected option to that one
    $selected = '';
    $current_row = mysql_fetch_assoc($result_query_mem_types);
    if ($current_mem_type == $current_row['membership_type_id'])
      {
        $selected = 'selected="yes"';
      }
    //get the membership type description
    $query_mem_type_descr = '
      SELECT
        membership_class
      FROM
        '.TABLE_MEMBERSHIP_TYPES.'
      WHERE
        membership_type_id = "'.$current_row['membership_type_id'].'"';
    $result_query_mem_type_descr = mysql_query($query_mem_type_descr) or die (debug_print ("ERROR: 675340 ", array ($query_mem_type_descr,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $mem_type_descr_row = mysql_fetch_assoc($result_query_mem_type_descr);
    $mem_type_descr = $mem_type_descr_row['membership_class'];
    //display the option
    $content_members .= '<option value="'.$current_row['membership_type_id'].'" '.$selected.'>'.$mem_type_descr.'</option><br/>';
    $index_mem_types ++;
  }
$content_members .= '
      </select>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td colspan="2" bgcolor="#CCCCCC"> <input name="no_postal_mail" type="checkbox" id="no_postal_mail2" value="1"'.($result['no_postal_mail']==1 ? ' checked' : '').'>
        Don&#146;t send postal mail</td>
    </tr>';

if ($_GET[ID])
  {
    $content_members .= '<tr><td>&nbsp;</td><td colspan="2" bgcolor="#CCCCCC"><input name="membership_discontinued" type="checkbox" id="membership_discontinued" value="1"';
    if($result['membership_discontinued'] == 1)
      {
        $content_members .= ' checked';
      }
    $content_members .= '> Membership discontinued </td></tr>';

    $content_members .= '<tr><td>&nbsp;</td><td colspan="2" bgcolor="#CCCCCC"><input name="pending" type="checkbox" id="pending" value="1"';
    if($result['pending'] == 1)
      {
        $content_members .= ' checked';
      }
    $content_members .= '> Pending </td></tr>';

    // Added line for "membership_date" and "last_renewal_date" fields
    $content_members .= '
    <tr>
      <td align="center"><font color="#3333FF"><b>*</b></font></td><td colspan="2" bgcolor="#CCCCCC">
        <input name="membership_date" type="text" id="membership_date" value="'.$result['membership_date'].'">Membership date (yyyy-mm-dd)
      </td>
    </tr>
    <tr>
      <td align="center"><font color="#3333FF"><b>*</b></font></td><td colspan="2" bgcolor="#CCCCCC">
        <input name="last_renewal_date" type="text" id="last_renewal_date" value="'.$result['last_renewal_date'].'">Last renewal date (yyyy-mm-dd)
      </td>
    </tr>';
    // Added line for "Notes" field
    $content_members .= '<tr><td>&nbsp;</td><td colspan="2" bgcolor="#CCCCCC">Notes:<br><textarea name="notes" cols="50" rows="5">';
    $content_members .= htmlspecialchars ($result['notes'], ENT_QUOTES);
    $content_members .= '</textarea></td></tr>';
  }
$content_members .= '
    <tr bgcolor="#BB0000">
      <td colspan="3"><font face="arial"><b>Producer Information (optional)</b></font></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td bgcolor="#CCCCCC">Producer ID</td>
      <td bgcolor="#CCCCCC">';

$content_members .= $p_result['producer_id'].'<input name="producer_id" type="hidden" value="'.htmlspecialchars ($p_result['producer_id'], ENT_QUOTES).'">';

// if(!$p_result['producer_id'])
//   {
//     $content_members .= '<input name="new_producer_id" type="text" size="8" maxlength="5">';
//   }
// else
//   {
//     $content_members .= $p_result['producer_id'].'<input name="producer_id" type="hidden" value="'.htmlspecialchars ($p_result['producer_id'], ENT_QUOTES).'">';
//   }

$content_members .= '
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td bgcolor="#CCCCCC">Web Address </td>
      <td bgcolor="#CCCCCC"> <input name="producer_link" type="text" id="producer_link" size="30" maxlength="50" value="'.htmlspecialchars ($p_result['producer_link'], ENT_QUOTES).'"></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td bgcolor="#CCCCCC">Business name </td>
      <td bgcolor="#CCCCCC"> <input name="producer_business_name" type="text" id="producer_business_name" size="30" maxlength="50" value="'.htmlspecialchars ($p_result['business_name'], ENT_QUOTES).'"></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td bgcolor="#CCCCCC">Payee name </td>
      <td bgcolor="#CCCCCC"> <input name="producer_payee" type="text" id="producer_payee" size="30" maxlength="50" value="'.htmlspecialchars ($p_result['payee'], ENT_QUOTES).'"></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td bgcolor="#CCCCCC">Active Status</td>
      <td bgcolor="#CCCCCC">
        <input type="radio" name="unlisted_producer" value="0"'.($p_result['unlisted_producer'] == 0 ? ' checked' : '').'>
        List &nbsp; &nbsp;
        <input type="radio" name="unlisted_producer" value="1"'.($p_result['unlisted_producer'] == 1 ? ' checked' : '').'>
        Unlist &nbsp; &nbsp;
        <input type="radio" name="unlisted_producer" value="2"'.($p_result['unlisted_producer'] == 2 ? ' checked' : '').'>
        Suspend
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td bgcolor="#CCCCCC">Homepage </td>
      <td bgcolor="#CCCCCC">http://
  <input name="home_page" type="text" id="home_page3" size="30" value="'.htmlspecialchars ($result['home_page'], ENT_QUOTES).'"></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td bgcolor="#CCCCCC">Toll free number </td>
      <td bgcolor="#CCCCCC"> <input name="toll_free" type="text" id="toll_free3" size="15" maxlength="20" value="'.htmlspecialchars ($result['toll_free'], ENT_QUOTES).'"></td>
    </tr>
  </table>
<div align="center">
  <input type="hidden" name="member_id" value="'.htmlspecialchars ($_GET[ID], ENT_QUOTES).'">
  <input name="reset" type="reset" id="reset" value="Clear form">
  <input type="submit" name="Submit"';
if ($_GET[ID])
  {
    $content_members .= 'value="Update Entry"';
  }
else
  {
    $content_members .= 'value="Add Member"';
  }

$content_members .= '>
</div>
</form>
  </td></tr>
</table>
<br>
</div>';
