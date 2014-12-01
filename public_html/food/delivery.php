<?php
include_once 'config_openfood.php';
session_start();
valid_auth('route_admin');

if (isset ($_GET['delivery_id'])) $delivery_id = $_GET['delivery_id'];
else $delivery_id = ActiveCycle::delivery_id();

$query = '
  SELECT
    '.TABLE_ROUTE.'.route_id,
    '.TABLE_ROUTE.'.route_name,
    '.TABLE_ORDER_CYCLES.'.delivery_date
  FROM
    ('.TABLE_ROUTE.',
    '.TABLE_ORDER_CYCLES.')
  WHERE
    '.TABLE_ORDER_CYCLES.'.delivery_id = "'.mysql_real_escape_string($delivery_id).'"
  GROUP BY
    '.TABLE_ROUTE.'.route_id
  ORDER BY
    '.TABLE_ROUTE.'.route_name ASC';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 860342 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($result) )
  {
    $route_id = $row['route_id'];
    $route_name = $row['route_name'];
    $delivery_date = $row['delivery_date'];
    $display .= '<tr><td colspan="4" bgcolor="#AEDE86">'.$font.'<b>'.$route_name.'</b></td></tr>';
    $sql = '
      SELECT
        site_id,
        site_long,
        route_id,
        hub_short
      FROM
        '.NEW_TABLE_SITES.'
      LEFT JOIN '.TABLE_HUBS.' USING(hub_id)
      WHERE
        route_id = "'.mysql_real_escape_string ($route_id).'"
        AND '.NEW_TABLE_SITES.'.site_type = "customer"
      ORDER BY site_long ASC';
    $rs = @mysql_query($sql, $connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($rs) )
      {
        $site_id = $row['site_id'];
        $site_long = $row['site_long'];
        $hub_short = $row['hub_short'];
        if ( $current_site_id < 0 )
          {
            $current_site_id = $row['site_id'];
          }
        while ( $current_site_id != $site_id )
          {
            $current_site_id = $site_id;
            $rte_confirmed_total = "";
            $query_confirm = '
              SELECT
                0 AS rte_confirmed,
                /* '.NEW_TABLE_BASKETS.'.rte_confirmed, */
                '.NEW_TABLE_BASKETS.'.basket_id
              FROM
                '.NEW_TABLE_BASKET_ITEMS.'
              LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
              LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
              WHERE
                '.NEW_TABLE_BASKETS.'.site_id = "'.mysql_real_escape_string ($site_id).'"
                AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string ($delivery_id).'
                AND out_of_stock != quantity
                AND '.NEW_TABLE_PRODUCTS.'.tangible = "1"
              GROUP BY '.NEW_TABLE_BASKETS.'.basket_id';
            $result_confirm = @mysql_query($query_confirm, $connection) or die(debug_print ("ERROR: 690430 ", array ($query_confirm,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            while ( $row_confirm = mysql_fetch_array($result_confirm) )
              {
                $basket_id = $row_confirm['basket_id'];
                $rte_confirmed = $row_confirm['rte_confirmed'];
                $rte_confirmed_total = $rte_confirmed_total + $rte_confirmed + 0;
              }
            $num_orders = mysql_numrows($result_confirm);
            if ( !$num_orders )
              {
                $num_orders = 0;
              }
//             $remaining_to_confirm = $num_orders - $rte_confirmed_total;
//             if ( ($num_orders == $rte_confirmed_total) && $num_orders != 0 )
//               {
//                 $display_confirmed = '<td>All confirmed</td>';
//               }
//             elseif ( $num_orders == 0 )
//               {
//               $display_confirmed = '<td></td>';
//               }
//             else
//               {
//                 $display_confirmed = '<td bgcolor="#ADB6C6">'.$remaining_to_confirm.' awaiting confirmation by route manager</td>';
//               }
          }
        $display .='
          <tr>
            <td>[<a href="delivery_list.php?route_id='.$route_id.'&site_id='.$site_id.'&delivery_id='.$delivery_id.'">By Member</a>]</td>
            <td>[Hub: '.$hub_short.']</td>
            <td><b>'.$site_long.'</b> ('.$num_orders.' orders)</td>
            '.$display_confirmed.'
          </tr>';
        $total_orders = $total_orders + $num_orders;
      }
    $display .= '<tr><td colspan="3"><br></td></tr>';
  }

$content_delivery = '
  <div align="center">
  <div id="delivery_id_nav">
    <a class="prior" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id - 1).'">&larr; PRIOR CYCLE </a>
    <a class="next" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id + 1).'"> NEXT CYCLE &rarr;</a>
  </div>
    <table width="80%">
      <tr>
        <td align="left">
          <br><b>'.$total_orders.' Total Orders for this Cycle</b><br><br>
          <table width="100%" cellpadding="3" cellspacing="0" border="0">
            '.$display.'
          </table>
        </td>
      </tr>
    </table>
  </div>';

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

$page_title_html = '<span class="title">Route Information</span>';
$page_subtitle_html = '<span class="subtitle">Deliveries and Pickups<br>'.date ('F d, Y', strtotime ($delivery_date)).'</span>';
$page_title = 'Route Information: Deliveries and Pickups';
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_delivery.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
