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
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.NEW_TABLE_SITES.'.site_id,
    '.NEW_TABLE_SITES.'.site_short,
    '.NEW_TABLE_SITES.'.site_long,
    '.NEW_TABLE_SITES.'.route_id,
    '.NEW_TABLE_SITES.'.inactive,
    '.TABLE_HUBS.'.hub_short,
    tangible_count.num_orders
  FROM
    ('.NEW_TABLE_SITES.',
    '.TABLE_ORDER_CYCLES.')
  LEFT JOIN '.TABLE_ROUTE.' USING(route_id)
  LEFT JOIN '.TABLE_HUBS.' ON ('.TABLE_ROUTE.'.hub_id = '.TABLE_HUBS.'.hub_id)
  LEFT JOIN (
    SELECT
      site_id,
      COUNT(DISTINCT(basket_id)) AS num_orders
    FROM '.NEW_TABLE_BASKET_ITEMS.'
    LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
    LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
    WHERE
      '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string ($delivery_id).'
      AND out_of_stock != quantity
      AND '.NEW_TABLE_PRODUCTS.'.tangible = "1"
    GROUP BY '.NEW_TABLE_BASKETS.'.site_id
    ) AS tangible_count USING(site_id)
  WHERE
    '.TABLE_ORDER_CYCLES.'.delivery_id = "'.mysql_real_escape_string($delivery_id).'"
    AND '.NEW_TABLE_SITES.'.site_type = "customer"
  GROUP BY
    '.NEW_TABLE_SITES.'.site_id
  ORDER BY
    '.TABLE_ROUTE.'.route_name ASC,
    '.NEW_TABLE_SITES.'.site_long ASC';
// echo "<pre>$query</pre>";
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 860342 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($result) )
  {
    $route_id = $row['route_id'];
    $route_name = $row['route_name'];
    $delivery_date = $row['delivery_date'];
    $site_id = $row['site_id'];
    $site_short = $row['site_short'];
    $site_long = $row['site_long'];
    $site_inactive = $row['inactive'];
    $hub_short = $row['hub_short'];
    $current_site_id = $row['site_id'];
    $num_orders = floor ($row['num_orders']); // Gives "0" for NULL values
    if ($route_id != $route_id_prior)
      {
        $display .= '
      <div class="route_row">
        <div class="route_name">'.$route_name.'</div>
      </div>';
      }
    if ($site_inactive == '1')
      $site_class = 'suspended';
    elseif ($site_inactive == '2')
      $site_class = 'standby';
    else
      $site_class = 'active';
    $display .='
      <div class="site_row '.$site_class.'">
        <div class="hub">Hub: '.$hub_short.'</div>
        <div class="site_short">'.$site_short.'</span></div>
        <div class="site_orders"><span class="site_long">'.$site_long.'</span><span class="num_orders">'.$num_orders.' '.(Inflect::pluralize_if ($num_orders, 'order')).'</span></div>
        <div class="link"><a href="delivery_list.php?route_id='.$route_id.'&site_id='.$site_id.'&delivery_id='.$delivery_id.'">View by member</a></div>
      </div>';
    $total_orders = $total_orders + $num_orders;
    $route_id_prior = $route_id;
  }

$content_delivery = '
    <div id="delivery_id_nav">
      <a class="prior" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id - 1).'">&larr; PRIOR CYCLE </a>
      <a class="next" href="'.$_SERVER['SCRIPT_NAME'].'?delivery_id='.($delivery_id + 1).'"> NEXT CYCLE &rarr;</a>
    </div>
    <h5>'.$total_orders.' Total Orders for this Cycle</h5>
    <div id="site_list">
      '.$display.'
    </div>';

$page_specific_css = '
  <style type="text/css">
    /* DELIVERY DATE NAVIGATION STYLES */
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
    /* DISPLAY TABLE STYLES */
    #site_list {
      display:table;
      margin:auto;
      width:80%;
      border:1px solid #888;
      padding:0 1em 1em;
      }
    .route_row {
      display:table-row;
      }
    .site_row {
      display:table-row;
      }
    .site_row:hover {
      background-color:#eea;
      }
    .route_name {
      text-align:center;
      padding-top:1em;
      text-align:center;
      font-weight:bold;
      color:#246;
      }
    .link,
    .hub,
    .site_orders,
    .site_short {
      display:table-cell;
      }
    .num_orders::before {
      content:" (";
      }
    .num_orders::after {
      content:")";
      }
    .suspended {
      display:none;
      }
    .standby {
      color:#aaa;
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
