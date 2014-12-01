
<?php
class formValidator
  {
    //internal variables
    var $_counter;
    var $_errors;
    //constructor  

    function formValidator()
      {
        $this->_counter = 0;
        $this->_errors = array();
      }

    function checkText()
      {
        $arguments = func_get_args();
        $value = $arguments[0];
        $field_name = $arguments[1];
        $range = $arguments[2];
        $minlength = $arguments[3];
        $maxlength = $arguments[4];
        $asciirange = $arguments[5];
        if($value == NULL || $value == " ")
          {
            if($field_name!=NULL)
              {
                $this->_errors[$this->_counter] = 'Please fill in '.$field_name.'!';
                $this->_counter++;
              }
            return false;
          }
        elseif ($range!=NULL)
          {
            $this->_checkNum($value, $field_name, $range, $minlength, $maxlength);
          }
        if ($asciirange != NULL)
          {
            $len=strlen($value);
            for($i = 0; $i < $len; $i++) //cycle through it all and look for the @ and . signs.
              {
                switch ($asciirange)
                  {
                    case "numeric":
                    if (ord ($value[$i]) < 48 || ord($value[$i]) > 57)
                      {
                        $this->_errors[$this->_counter] = $field_name.' can only contain numbers.';
                        $this->_counter++;
                        return false;
                      }
                    break;
                    case "alphanumeric":
                    if(!((ord ($value[$i]) >= 48 && ord($value[$i]) <= 57) || (ord($value[$i]) >= 64 && ord($value[$i]) <=90 ) || (ord($value[$i]) >= 97 && ord($value[$i]) <= 122) || ord($value[$i]) == 95 || ord($value[$i]) == 46))
                      {
                        $this->_errors[$this->_counter] = $field_name.' can only contain alphnumeric character (and @ for e-mail addresses.';
                        $this->_counter++;
                        return false;
                      }
                    break;
                  } //close switch  
              } //close for  
          } //close if
        return true;
      } //close function
    //checks to make sure that an e-mail address is a] there and b] follows the name@domain.suffix

    function validateEmail()
      {
        $args = func_get_args();
        $value = $args[0];
        $duplicate = $args[1];
        if (! filter_var($value, FILTER_VALIDATE_EMAIL))
          {
            $this->_errors[$this->_counter] = 'You must enter a VALID e-mail address';
            $this->_counter++;
            return false;
          }
        else
          {
            return true;
          }
      }

    function validateZip($value, $return_info)
      {
        if(!$this->checkText($value))
          {
            $this->_errors[$this->_counter] = 'Please enter a zip code.';
            $this->_counter++;
            return false;
          }
        $conn = new connection;
        $conn->makeConnection(/*local zip dbase*/);
        $query = '
          SELECT
            *
          FROM
            geo_refs
          WHERE
            zip = "'.mysql_real_escape_string ($value).'"';
        $match = mysql_query($query, $conn->connection);
        $result = mysql_fetch_row($match);
        mysql_close();
        if ($result==NULL)
          {
            $this->_errors[$this->_counter] = 'You must enter a VALID zip code.';
            $this->_counter++;
            return false;
          }
        else
          {
            if ($return_info == true)
              {
                return $result;
              }
            else
              {
                return true;
              }
          }
      }

    //Takes the string $actual, checks its length and then depending on the value in $minmax, checks to see whether it is too low, high or out of a range.  Works.
    function _checkNum($actual, $fieldname, $minmax, $min, $max)
      {
        $len=strlen($actual);
        switch($minmax)
          {
            case "min":
            if($len<$min)
              {
                $this->_errors[$this->_counter] = $fieldname.' is too short.';
                $this->_counter++;
                return false;
              }
            else
              {
                return true;
              }
            break;
            case "max":
            if($len>$max)
              {
                $this->_errors[$this->_counter] = $fieldname.' is too long.';
                $this->_counter++;
                return false;
              }
            else
              {
                return true;
              }
            break;
            case "inside":
            if($len<$min || $len>$max)
              {
                $this->_errors[$this->_counter] = $fieldname.' is outside of value range.';
                $this->_counter++;
                return false;
              }
            break;
          }
      }

    //Checks to see if an entry is already in the table. Does not add to the _error list.
    function checkDuplicate($entry, $table, $field)
      {
        //echo $entry;
        $query = '
          SELECT
            *
          FROM
            Users
          WHERE
            email_address = "'.mysql_real_escape_string ($entry).'"
            OR email_address_2 = "'.mysql_real_escape_string ($entry).'"';
        $conn = new connection;
        $conn->makeConnection(/*local info*/);
        $match = mysql_query($query, $conn->connection) or die(mysql_error()); //execute
        //echo $match;
        $results = mysql_fetch_row($match);
        if($results[8] == $entry)
          {
            $this->_errors[$this->_counter] = 'There is already an entry for this e-mail address.';
            $this->_counter++;
          }
        else
          {
            return false;
          }
      }    

    function showErrors()
      {
        if($this->_counter>0)
          {
            $display_errors .= '
              <div class="error_message">
                <p class="message">Please correct form errors</p>
                <ul class="error_list">';
            foreach($this->_errors as $error)
              {
                $display_errors .= '
                  <li>'.$error.'</li>';
              }
            $display_errors .= '
                </ul>
              </div>';
            return $display_errors;
          }
        return '';
      }
  }
?>