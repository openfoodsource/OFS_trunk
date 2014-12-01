<?php
require_once("formvalidator.class.php");
class memberInterface
  {
    var $member;
    var $producer;

    function memberInterface()
      {
        $this->member = TABLE_MEMBER;
        $this->producer = TABLE_PRODUCER;
        return true;
      }

    function mainMenu()
      {
        global $content_members;
        include "forms/findmembers.form.php";
        return true;  
      }

    function buildAddMember()
      {
        include "forms/addmember.form.php";
        return true;  
      }

    function checkMemberForm() //check the form. $add_edit is whether we're checking an add or edit form
      {
        $fields = array("ok");
        $cf = new formValidator;
        $fields["first_name"] = $cf->checkText ($_POST['first_name'], "first name");
        $fields["preferred_name"] = $cf->checkText ($_POST['preferred_name'], "preferred name");
        $fields["last_name"] = $cf->checkText ($_POST['last_name'], "last name");
        if($_POST['first_name_2'])
          {
            $fields["first_name_2"] = $cf->checkText($_POST['first_name_2'], "first name 2");
          }
        else
          {
            $fields["first_name_2"] = true;
          }
        if($_POST['last_name_2'])
          {
            $fields["last_name_2"] = $cf->checkText($_POST['last_name_2'], "first name 2");
          }
        else
          {
            $fields["last_name_2"] = true;
          }
        if ($_POST['email_address'])
          {
            $fields["email_address"] = $cf->validateEmail ($_POST['email_address']);
          }
        else
          {
            $fields["email_address"] = true;
          }
        if ($_POST['email_address_2'])
          {
            $fields["email_address_2"] = $cf->validateEmail ($_POST['email_address_2']);
          }
        else
          {
            $fields["email_address_2"] = true;
          }
        // $fields["home_phone"] = $cf->checkText ($_POST['home_phone'], "home phone");
        $fields["address_line1"] = $cf->checkText ($_POST['address_line1'], "address");
        $fields["city"] = $cf->checkText ($_POST['city'], "city");
        $fields["state"] = $cf->checkText ($_POST['state'], "state");
        $fields["zip"] = $cf->checkText ($_POST['zip'], "zip");    
  /*  if($_POST[business_name])
    {
      $fields["work_phone"]=$cf->checkText($_POST[work_phone], "work phone");
      $fields["work_address_line1"]=$cf->checkText($_POST[work_address_line1], "work address");
      $fields["work_city"]=$cf->checkText($_POST[work_city], "work city");
      $fields["work_state"]=$cf->checkText($_POST[work_state], "work state");
      $fields["work_zip"]=$cf->checkText($_POST[work_zip], "work zip");
    }else{
      $fields["work_phone"]=true;
      $fields["work_address_line1"]=true;
      $fields["work_city"]=true;
      $fields["work_state"]=true;
      $fields["zip"]=true;
    }*/
        $fields["username"] = $cf->checkText ($_POST['username'], "user name");
        if ($fields["username"])
          {
            if (!$_GET['ID'])
              {
                $query_string = '
                  SELECT
                    *
                  FROM
                    '.mysql_real_escape_string ($this->member).'
                  WHERE
                    username = "'.mysql_real_escape_string ($_POST['username']).'"';
                $query = mysql_query($query_string) or die(debug_print ("ERROR: 672303 ", array ($query_string,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
                $rows = @mysql_num_rows($query);
                if ($rows > 0)
                  {
                    $cf->_errors[$cf->_counter] = "There is already a user with this username. Please choose a new one.";
                    $cf->_counter++;
                  }
              }
          }
        if (!$_GET['ID'] || $_POST['password_r'])
          {
            if ($fields["password"] = $cf->checkText ($_POST['password'], "password", "min", 6, 25, "alphanumeric") && $fields["password_r"] = $cf->checkText($_POST['password_r'], "password again", "min", 6, 25, "alphanumeric"))
              {
                if($_POST['password'] != $_POST['password_r'])
                  {
                    $cf->_errors[$cf->_counter] = "The second password does not match the first.";
                    $cf->_counter++;
                  }
              }
          }
        if(strlen ($cf->showErrors()) == 0)
          {
            $result['username'] = $_POST['username'];
            $result['business_name'] = $_POST['business_name'];
            $result['preferred_name'] = $_POST['preferred_name'];
            $result['last_name'] = $_POST['last_name'];
            $result['first_name'] = $_POST['first_name'];
            $result['last_name_2'] = $_POST['last_name_2'];
            $result['first_name_2'] = $_POST['first_name_2'];
            $result['no_postal_mail'] = $_POST['no_postal_mail'];
            $result['address_line1'] = $_POST['address_line1'];
            $result['address_line2'] = $_POST['address_line2'];
            $result['city'] = $_POST['city'];
            $result['state'] = $_POST['state'];
            $result['zip'] = $_POST['zip'];
            $result['county'] = $_POST['county'];
            $result['work_address_line1'] = $_POST['work_address_line1'];
            $result['work_address_line2'] = $_POST['work_address_line2'];
            $result['work_city'] = $_POST['work_city'];
            $result['work_state'] = $_POST['work_state'];
            $result['work_zip'] = $_POST['work_zip'];
            $result['email_address'] = $_POST['email_address'];
            $result['email_address2'] = $_POST['email_address2'];
            $result['home_phone'] = $_POST['home_phone'];
            $result['work_phone'] = $_POST['work_phone'];
            $result['mobile_phone'] = $_POST['mobile_phone'];
            $result['fax'] = $_POST['fax'];
            $result['toll_free'] = $_POST['toll_free'];
            $result['home_page'] = $_POST['home_page'];
            $result['membership_type_id'] = $_POST['membership_type_id'];
            $result['membership_date'] = $_POST['membership_date'];
            $result['membership_discontinued'] = $_POST['membership_discontinued'];
            $result['pending'] = $_POST['pending'];
            $result['notes'] = $_POST['notes'];
            if($_POST['producer_id'])
              {
                $p_result['producer_id'] = $_POST['producer_id'];
                $p_result['producer_link'] = $_POST['producer_link'];
                $p_result['producer_payee'] = $_POST['producer_payee'];
                $p_result['unlisted_producer'] = $_POST['unlisted_producer'];  
              }
            include "forms/addmember.form.php";
            $this->insertData();
            return '';
          }
        else
          {
            // Show the errors
            return $cf->showErrors();
          }
      }

    function insertData()
      {
        global $content_members;
        $query = mysql_query('SELECT MD5("'.mysql_real_escape_string ($_POST['password']).'")');
        $pass = mysql_fetch_row($query);
        $password = $pass[0];
        $member_id = preg_replace("/[^0-9]/","",$_POST['member_id']);
        if($_POST['producer_id'])
          {
            $query_string = '
              UPDATE 
                '.mysql_real_escape_string ($this->producer).'
              SET
                unlisted_producer = "'.mysql_real_escape_string ($_POST['unlisted_producer']).'",
                producer_link = "'.mysql_real_escape_string ($_POST['producer_link']).'",
                business_name = "'.mysql_real_escape_string ($_POST['producer_business_name']).'",
                payee = "'.mysql_real_escape_string ($_POST['producer_payee']).'"
              WHERE
                member_id = "'.mysql_real_escape_string ($member_id).'"';
            $query = mysql_query($query_string) or die (debug_print ("ERROR: 730352 ", array ($query_string,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
          }
        if(!$_POST['password_r'])
          {
            $password = $_POST['password'];
          }
        if($_POST['no_postal_mail'] != 1)
          {
            $_POST['no_postal_mail'] = 0;
          }
        if($_POST['membership_discontinued'] != 1)
          {
            $_POST['membership_discontinued'] = 0;
          }
        if($member_id > 0)
          {
            $query_type = "UPDATE";
          }
        else
          {
            $query_type = " INSERT INTO";
          }
        $query_string = '
          '.$query_type.' '.mysql_real_escape_string ($this->member).'
          SET
            username = "'.mysql_real_escape_string($_POST['username']).'",
            password = "'.mysql_real_escape_string($password).'",
            business_name = "'.mysql_real_escape_string($_POST['business_name']).'",
            preferred_name = "'.mysql_real_escape_string($_POST['preferred_name']).'",
            last_name = "'.mysql_real_escape_string($_POST['last_name']).'",
            first_name = "'.mysql_real_escape_string($_POST['first_name']).'",
            last_name_2 = "'.mysql_real_escape_string($_POST['last_name_2']).'", 
            first_name_2 = "'.mysql_real_escape_string($_POST['first_name_2']).'", 
            no_postal_mail = "'.mysql_real_escape_string ($_POST['no_postal_mail']).'",
            address_line1 = "'.mysql_real_escape_string($_POST['address_line1']).'",
            address_line2 = "'.mysql_real_escape_string($_POST['address_line2']).'",
            city = "'.mysql_real_escape_string($_POST['city']).'",
            state = "'.mysql_real_escape_string($_POST['state']).'",
            zip = "'.mysql_real_escape_string($_POST['zip']).'",
            county = "'.mysql_real_escape_string($_POST['county']).'",
            work_address_line1 = "'.mysql_real_escape_string($_POST['work_address_line1']).'",
            work_address_line2 = "'.mysql_real_escape_string($_POST['work_address_line2']).'",
            work_city = "'.mysql_real_escape_string($_POST['work_city']).'",
            work_state = "'.mysql_real_escape_string($_POST['work_state']).'",
            work_zip = "'.mysql_real_escape_string($_POST['work_zip']).'",
            email_address = "'.mysql_real_escape_string($_POST['email_address']).'",
            email_address_2 = "'.mysql_real_escape_string($_POST['email_address_2']).'",
            home_phone = "'.mysql_real_escape_string($_POST['home_phone']).'",
            work_phone = "'.mysql_real_escape_string($_POST['work_phone']).'",
            mobile_phone = "'.mysql_real_escape_string($_POST['mobile_phone']).'",
            fax = "'.mysql_real_escape_string($_POST['fax']).'",
            toll_free = "'.mysql_real_escape_string($_POST['toll_free']).'", 
            home_page = "'.mysql_real_escape_string($_POST['home_page']).'",
            membership_date = "'.mysql_real_escape_string (date ('Y-m-d', strtotime ($_POST['membership_date']))).'",
            last_renewal_date = "'.mysql_real_escape_string (date ('Y-m-d', strtotime($_POST['last_renewal_date']))).'",
            membership_type_id = "'.mysql_real_escape_string ($_POST['membership_type_id']).'",
            membership_discontinued = "'.mysql_real_escape_string ($_POST['membership_discontinued']).'",
            pending = "'.mysql_real_escape_string ($_POST['pending']).'",
            notes = "'.mysql_real_escape_string ($_POST['notes']).'"';
        if ($member_id > 0)
          {
            $query_string .= '
          WHERE
            member_id = "'.mysql_real_escape_string ($member_id).'"';
          }
        $query = mysql_query($query_string) or die (debug_print ("ERROR: 874052 ", array ($query_string,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if ($_POST['new_producer_id'])
          {
            $query_string = '
              SELECT
                member_id
              FROM
                '.mysql_real_escape_string ($this->member).'
              WHERE
                username = "'.mysql_real_escape_string ($_POST['username']).'"';
            $query = mysql_query($query_string);
            $result = mysql_fetch_row($query);
            $member_id = $result[0];
            $query_string = '
              INSERT INTO '.mysql_real_escape_string ($this->producer).'
                (
                  producer_id,
                  producer_link,
                  business_name,
                  payee,
                  member_id,
                  unlisted_producer
                )
              VALUES
                (
                  '.mysql_real_escape_string ($_POST['new_producer_id']).',
                  '.mysql_real_escape_string ($_POST['producer_link']).',
                  '.mysql_real_escape_string ($_POST['business_name']).',
                  '.mysql_real_escape_string ($_POST['producer_payee']).',
                  '.mysql_real_escape_string ($member_id).',
                  '.mysql_real_escape_string ($_POST['unlisted_producer']).'
                )';
            $query = mysql_query($query_string) or die (debug_print ("ERROR: 657632 ", array ($query_string,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            $query_string = '
              INSERT INTO '.TABLE_PRODUCER_REG.'
                (
                  producer_id,
                  member_id,
                  business_name
                )
              VALUES
                (
                  '.mysql_real_escape_string ($_POST['new_producer_id']).',
                  '.mysql_real_escape_string ($member_id).',
                  '.mysql_real_escape_string ($_POST['business_name']).'
                )';
            $query = mysql_query($query_string) or die (debug_print ("ERROR: 574932 ", array ($query_string,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
          }
        if($query && $member_id)
          {
            $content_members .= 'Member updated!<br>';
          }
        else
          {
            $content_members .= 'Member added!<br>';
          }
        $this->mainMenu();
        return true;
      }

    function findForm()
      {
        global $content_members;
        include "forms/findmembers.form.php";
        return;
      }

    function findUsers()
      {
        global $content_members;
        $search = $_POST['query'];
        $query_string = '
          SELECT
            *
          FROM
            '.$this->member.'
          WHERE
            first_name LIKE "%'.mysql_real_escape_string ($search).'%"
            OR last_name LIKE "%'.mysql_real_escape_string ($search).'%"
            OR first_name_2 LIKE "%'.mysql_real_escape_string ($search).'%"
            OR last_name_2 LIKE "%'.mysql_real_escape_string ($search).'%"
            OR preferred_name LIKE "%'.mysql_real_escape_string ($search).'%"
            OR username LIKE "%'.mysql_real_escape_string ($search).'%"
            OR business_name LIKE "%'.mysql_real_escape_string ($search).'%"
            OR email_address LIKE "%'.mysql_real_escape_string ($search).'%"
            OR email_address_2 LIKE "%'.mysql_real_escape_string ($search).'%"
            OR member_id = "'.mysql_real_escape_string ($search).'"
          ORDER BY
            member_id';
        $query = mysql_query($query_string)  or die (debug_print ("ERROR: 762930 ", array ($query_string,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $rows = @mysql_num_rows($query);
        if ($rows > 0)
          {
            $this->displayUsers($query, $rows);
            return true;
          }
        else
          {
            $content_members .= 'No users found.  Please search again.';
            $this->findForm();
            return false;
          }
      }  

    function displayUsers($query, $rows)//entirely a subset of the findUsers()
      {
        global $content_members;
        $content_members .= '
          <table style="border:.5px black solid;width:80%;" cellspacing="0">
            <tr bgcolor="#BB0000">
              <td>Member #</td>
              <td>Names (first, second, preferred, business)</td>
              <td>Email</td>
              <td>Username</td>
              <td>Action</td>
            </tr>';
        while ($result = mysql_fetch_array($query))
          {
            if ($result['membership_discontinued'] > 0)
              $discontinued_class = 'discontinued';
            else
              $discontinued_class = '';
            $content_members .= '
            <tr>
              <td class="member_id">'.$result['member_id'].'</td>
              <td class="'.$discontinued_class.'">
                <span style="color:#888;font-size:70%;">'.$result['first_name'].' '.$result['last_name'].' &nbsp; '.$result['first_name_2'].' '.$result['last_name_2'].'</span><br />
                '.$result['preferred_name'].'<br />
                <span class="business_name '.$discontinued_class.'">'.$result['business_name'].'</td>
              <td class="email_address '.$discontinued_class.'">'.$result['email_address'].(strlen ($result['email_address_2']) > 0 ? '<br>'.$result['email_address_2'] : '').'</td>
              <td class="username '.$discontinued_class.'">'.$result['username'].'</td>
              <td>
                <a href="member_interface.php?action=edit&ID='.$result['member_id'].'">Edit</a>&nbsp;/&nbsp;<a href="member_information.php?action=edit&member_id='.$result['member_id'].'">View</a>
              </td>
            </tr>'; 
          }
        $content_members .= '
          </table>
          <a href="member_interface.php"><button>New search</button></a>';
        return true;
      }

    function editUser($error_html="")
      {
        global $content_members;
        $query_string = '
          SELECT
            *
          FROM
            '.$this->member.'
          WHERE
            member_id = "'.mysql_real_escape_string ($_GET['ID']).'"';
        $query = mysql_query($query_string);  
        $result = mysql_fetch_array($query);
        $query_string = '
          SELECT
            *
          FROM
            '.mysql_real_escape_string ($this->producer).'
          WHERE
            member_id = "'.mysql_real_escape_string ($_GET['ID']).'"';
        $query = mysql_query($query_string);
        $p_result = @mysql_fetch_array($query);
        include "forms/addmember.form.php";
        return true;
      }
  }
?>
