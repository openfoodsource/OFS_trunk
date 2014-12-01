<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin');

if (isset ($_GET['delivery_id'])) $delivery_id = $_GET['delivery_id'];
else $delivery_id = ActiveCycle::delivery_id();

$all_emails = array();

$sqlr = '
  SELECT
    '.TABLE_ROUTE.'.route_id,
    '.TABLE_ROUTE.'.route_name,
    '.TABLE_ROUTE.'.route_desc,
    '.TABLE_HUBS.'.hub_short,
    '.TABLE_ORDER_CYCLES.'.delivery_date
  FROM
    ('.TABLE_ROUTE.',
    '.TABLE_ORDER_CYCLES.')
  LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
  WHERE
    '.TABLE_ORDER_CYCLES.'.delivery_id = "'.mysql_real_escape_string($delivery_id).'"
  GROUP BY
    '.TABLE_ROUTE.'.route_id
  ORDER BY
    '.TABLE_HUBS.'.hub_short ASC,
    '.TABLE_ROUTE.'.route_name ASC';
$rsr = @mysql_query($sqlr, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($rsr) )
  {
    $route_id = $row['route_id'];
    $route_name = $row['route_name'];
    $route_desc = $row['route_desc'];
    $delivery_date = $row['delivery_date'];
    $sql_sum6 = '
      SELECT
        '.NEW_TABLE_BASKETS.'.delivery_id,
        '.NEW_TABLE_BASKETS.'.basket_id,
        '.NEW_TABLE_BASKET_ITEMS.'.basket_id,
        '.NEW_TABLE_BASKETS.'.site_id,
        '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
        '.NEW_TABLE_BASKET_ITEMS.'.product_id,
        '.NEW_TABLE_SITES.'.route_id,
        '.NEW_TABLE_SITES.'.site_id
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
      LEFT JOIN '.NEW_TABLE_SITES.' USING(site_id)
      WHERE
        '.NEW_TABLE_BASKETS.'.delivery_id ="'.mysql_real_escape_string ($delivery_id).'"
        AND '.NEW_TABLE_SITES.'.route_id = "'.mysql_real_escape_string ($route_id).'"
        AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != "1"
      GROUP BY
        '.NEW_TABLE_BASKETS.'.delivery_id';
    $result_sum6 = @mysql_query($sql_sum6, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    $num_mem = mysql_numrows($result_sum6);
    if ( $num_mem )
      {
        $display .= '
          <tr><td align="left" bgcolor="#AEDE86">
          <b>Route: '.$route_name.'</b><br>'.$route_desc.'
          </td></tr>';
      }
    else
      {
        $display .= "";
      }
    if ( $num_mem )
      {
        $sqlr2 = '
          SELECT
            '.NEW_TABLE_SITES.'.site_id,
            '.NEW_TABLE_SITES.'.site_long,
            '.NEW_TABLE_SITES.'.site_description,
            '.NEW_TABLE_SITES.'.route_id,
            '.TABLE_HUBS.'.hub_short
          FROM
            '.NEW_TABLE_SITES.'
          LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
          WHERE
            route_id = "'.mysql_real_escape_string ($route_id).'"
            AND '.NEW_TABLE_SITES.'.site_type = "customer"
          GROUP BY
            site_id
          ORDER BY
            site_long ASC';
        $rsr2 = @mysql_query($sqlr2, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        $num_del = mysql_numrows($rsr2);
        while ( $row = mysql_fetch_array($rsr2) )
          {
            $site_id = $row['site_id'];
            $site_long = $row['site_long'];
            $site_description = $row['site_description'];
            $hub_short = $row['hub_short'];
            $display .= '
              <tr><td align="left" bgcolor="#DDDDDD">
              <b>Delivery Specifics: '.$site_long.' (Hub: '.$hub_short.')</b><br>'.$site_description.'
              </td></tr>';
            $display_rt = '';
            $sql = '
              SELECT
                '.NEW_TABLE_BASKETS.'.*,
                '.TABLE_MEMBER.'.*,
                '.NEW_TABLE_BASKET_ITEMS.'.product_id,
                '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
                '.NEW_TABLE_SITES.'.*,
                '.NEW_TABLE_BASKETS.'.delivery_type as ddelivery_type
              FROM
                '.NEW_TABLE_BASKETS.'
              LEFT JOIN
                '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.NEW_TABLE_BASKETS.'.member_id
              LEFT JOIN
                '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
              LEFT JOIN
                '.NEW_TABLE_SITES.' ON '.NEW_TABLE_SITES.'.site_id = '.NEW_TABLE_BASKETS.'.site_id
              WHERE
                '.NEW_TABLE_BASKETS.'.site_id = "'.mysql_real_escape_string ($site_id).'"
                AND '.NEW_TABLE_SITES.'.site_id = "'.mysql_real_escape_string ($site_id).'"
                AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
                AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != "1"
              GROUP BY
                basket_id
              ORDER BY
                last_name ASC';
            $rs = @mysql_query($sql, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
            $num_orders = mysql_numrows($rs);
            unset ($all_route_emails);
            $all_route_emails = array();
            while ( $row = mysql_fetch_array($rs) )
              {
                $row['hub_short'] = $hub_short; // Set this so it will be included in the convert_route_code function.
                $basket_id = $row['basket_id'];
                $basket_checked_out = $row['checked_out'];
                $delivery_id = $row['delivery_id'];
                $member_id = $row['member_id'];
                $last_name = $row['last_name'];
                $first_name = $row['first_name'];
                $business_name = $row['business_name'];
                $preferred_name = $row['preferred_name'];
                $delivery_type = $row['delivery_type'];
                $truck_code = $row['truck_code'];
                $row['storage_code'] = 'ALL'; // Storage code isn't available so set to some value for convert_route_code function.
                $preferred_name = $row['preferred_name'];
                $first_name_2 = $row['first_name_2'];
                $last_name_2 = $row['last_name_2'];
                $ddelivery_type = $row['ddelivery_type'];
                $finalized = $row['finalized'];
                $home_phone = $row['home_phone'];
                $work_phone = $row['work_phone'];
                $mobile_phone = $row['mobile_phone'];
                $fax = $row['fax'];
                $email_address = $row['email_address'];
                $email_address_2 = $row['email_address_2'];
                $product_quantity_of_member = $row['sum_pm'];
                $address_line1 = $row['address_line1'];
                $address_line2 = $row['address_line2'];
                $city = $row['city'];
                $state = $row['state'];
                $zip = $row['zip'];
                $work_address_line1 = $row['work_address_line1'];
                $work_address_line2 = $row['work_address_line2'];
                $work_city = $row['work_city'];
                $work_state = $row['work_state'];
                $work_zip = $row['work_zip'];

                $display_rt .= '
                  <li> <b>'.(convert_route_code((array) $row)).'</b><br>
                  <a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id.'&member_id='.$member_id.'">
                  <b>'.$preferred_name.' (Mem#'.$member_id.')</b></b></a>';

                $display_rt .= '   <ul>';
                if ( $ddelivery_type == 'W' )
                  {
                    $display_rt .= 'Work address: ';
                    if ( $work_address_line1 )
                      {
                        $display_rt .= $work_address_line1;
                      }
                    else
                      {
                        $display_rt .= 'No work address available<br>';
                      }
                    if ( $work_address_line2 )
                      {
                        $display_rt .= ', '.$work_address_line2;
                      }
                    if ( $work_city || $work_state || $work_zip )
                      {
                        $display_rt .= ", $work_city, $work_state, $work_zip<br>";
                      }
                  }
                else
                  {
                    $display_rt .= 'Home address: ';
                    $display_rt .= $address_line1;
                    if ( $address_line2 )
                      {
                        $display_rt .= ', '.$address_line2;
                      }
                      $display_rt .= ", $city, $state, $zip<br>";
                  }
                // Set the intangible flag (basket_checked out < 0 when all items are intangible)
                if ($basket_checked_out < 0)
                  {
                    $intangible_prefix = '<span class="intangible">';
                    $intangible_postfix = '</span>';
                  }
                else
                  {
                    $intangible_prefix = '';
                    $intangible_postfix = '';
                  }
                $display_rt .= 'Email: '.$email_address;
                array_push ($all_route_emails, $intangible_prefix.$email_address.$intangible_postfix);
                array_push ($all_emails, $intangible_prefix.$email_address.$intangible_postfix);
                if ( $email_address_2 )
                  {
                    $display_rt .= ', Email2: '.$email_address_2;
                    array_push ($all_route_emails, $intangible_prefix.$email_address_2.$intangible_postfix);
                    array_push ($all_emails, $intangible_prefix.$email_address_2.$intangible_postfix);
                  }
                if ( $home_phone )
                  {
                    $display_rt .= ', Home: '.$home_phone;
                  }
                if ( $work_phone )
                  {
                    $display_rt .= ', Work: '.$work_phone;
                  }
                if ( $mobile_phone )
                  {
                    $display_rt .= ', Cell: '.$mobile_phone;
                  }
                if ( $fax )
                  {
                    $display_rt .= '", Fax: '.$fax;
                  }
                $display_rt .= "   </ul><br>";
              }
            if ( !$num_orders )
              {
                $display .= '<tr><td align="left">No orders here for this cycle.<br><br></td></tr>';
              }
            else
              {
                $display .= '
                  <tr>
                    <td align="left">
                      <ul>
                        '.$display_rt.'
                        <li>
                          <strong>All emails for this route: </strong>
                          <pre>'.
                          implode ("\n",$all_route_emails).'
                          </pre>
                        </li>
                      </ul>
                    </td>
                  </tr>';
              }
          }
      }
  }
$display .= '
  <tr>
    <td>
      <hr />
      <br />
      <strong>All emails for all routes: </strong>
      <pre>'.
      implode ("\n",$all_emails).'
      </pre>
    </td>
  </tr>';

$site_name = 'Food '.ucfirst (ORGANIZATION_TYPE);
$base_url = '';
$fontface = 'arial';
$fontsize = '-1';
$font = '<font face="arial" size="-1">';

$content_delivery = '
  <div id="delivery_id_nav">
    <a class="prior" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id - 1).'">&larr; PRIOR CYCLE </a>
    <a class="next" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id + 1).'"> NEXT CYCLE &rarr;</a>
  </div>
  <table bgcolor="#FFFFFF" cellspacing="2" cellpadding="2" border="0">
    '.$display.'
  </table>';

$page_specific_css = '
  <style type="text/css">
    #delivery_id_nav {
      width:45%;
      max-width:40rem;
      margin:5px auto 0;
      height:1.5em;
      background-color:#eef;
      }
    #delivery_id_nav .prior,
    #delivery_id_nav .next {
      display:block;
      line-height:1.5;
      padding:0 5px;
      }
    #delivery_id_nav .prior {
      float:left;
      }
    #delivery_id_nav .next {
      float:right;
      }
  </style>';

$page_title_html = '<span class="title">All Members With Orders on Each Route</span>';
$page_subtitle_html = '<span class="subtitle">'.date ('F d, Y', strtotime ($delivery_date)).'</span>';
$page_title = 'All Members With Orders on Each Route: '.date ('F d, Y', strtotime ($delivery_date));
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_delivery.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
