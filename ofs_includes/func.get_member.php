<?php
// This subroutine returns member information.
// Call with: get_member ($member_id)
function get_member ($member_id)
  {
    global $connection;
    // Expose additional parameters as they become needed.
    $selected_fields = array (
      // 'member_id', <------ don't use this (you already know it)
      TABLE_MEMBER.'.pending',
      'username',
      'password',
      'auth_type',
      'business_name',
      'preferred_name',
      // 'last_name',
      // 'first_name',
      // 'last_name_2',
      // 'first_name_2',
      // 'no_postal_mail',
      // 'address_line1',
      // 'address_line2',
      // 'city',
      // 'state',
      // 'zip',
      // 'county',
      // 'work_address_line1',
      // 'work_address_line2',
      // 'work_city',
      // 'work_state',
      // 'work_zip',
      // 'email_address',
      // 'email_address_2',
      // 'home_phone',
      // 'work_phone',
      // 'mobile_phone',
      // 'fax',
      // 'toll_free',
      // 'home_page',
      'membership_type_id',
      'membership_date',
      'last_renewal_date',
      'membership_discontinued',
      'mem_taxexempt',
      'mem_delch_discount',
      // 'how_heard_id',
      'notes',
      // ------------------------- THE FOLLOWING ARE FROM THE membership_types TABLE
      // 'membership_type_id',
      // 'set_auth_type',
      'order_cost',
      'order_cost_type',
      // 'membership_class',
      // 'membership_description',
      // 'pending',
      // 'enabled_type',
      // 'may_convert_to',
      // 'renew_cost',
      // 'expire_after',
      // 'expire_type',
      // 'expire_message
      );
    $query = '
      SELECT
        '.implode (",\n        ", $selected_fields).'
      FROM '.TABLE_MEMBER.'
      LEFT JOIN '.TABLE_MEMBERSHIP_TYPES.' USING(membership_type_id)
      WHERE
        member_id = "'.mysqli_real_escape_string ($connection, $member_id).'"';
    $result = mysqli_query ($connection, $query) or die (debug_print ("ERROR: 740293 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
      {
        return ($row);
      }
  }
