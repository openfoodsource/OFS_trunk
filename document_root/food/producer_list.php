<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member'); // anyone can see this list

// Display page as nested list items (like a list) -- otherwise un-nested (like a tag cloud)
$display_as = (isset ($_GET['display_as']) ? $_GET['display_as'] : 'list');
if ($display_as != 'list' && $display_as != 'grid') $display_as = 'list';

// Get information about showing all producers (or not)
$show_all = (isset ($_GET['show']) && $_GET['show'] == 'all' ? true : false);

if ($show_all == true)
  {
    $show_unlisted_query = '';
  }
else
  {
    // We ALWAYS do not show suspended producers.
    // But on this condition, also do not show "unlisted" producers.
    $show_unlisted_query = '
    AND '.TABLE_PRODUCER.'.unlisted_producer != 1 /* NOT UNLISTED */
    AND IF('.NEW_TABLE_PRODUCTS.'.inventory_id > 0, IF(FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) > 0, 1, 0), 1) = 1';
  }

// Configure to use the availability matrix -- or not
if (USE_AVAILABILITY_MATRIX == true)
  {
    // Default to use the current basket site_id, if it exists
    $ofs_customer_site_id = CurrentBasket::site_id();
    // If not, then use the session site_id, if it exists
    if (! $ofs_customer_site_id) $ofs_customer_site_id = $_SESSION['ofs_customer']['site_id'];
    // If not, then use the cookie site_id, if it exists
    if (! $ofs_customer_site_id) $ofs_customer_site_id = $_COOKIE['ofs_customer']['site_id'];
    // And if there is no cookie, then set the cookie...
    if (! $ofs_customer_site_id)
      {
        $no_site_selected = '
        <div class="no_site_message"><h3>No site was selected</h3><p>Not all producers have products available at every location.<br />Please <a onclick="popup_src(\''.BASE_URL.PATH.'customer_select_site.php?display_as=popup\', \'customer_select_site\', \'\', false);">select a site</a> in order to view products available at that location.</p></div>';
      }
    // Now set query values...
    $select_availability = '
    IF ('.TABLE_AVAILABILITY.'.site_id = "'.$ofs_customer_site_id.'", 1, 0) AS availability,
    '.NEW_TABLE_SITES.'.site_long AS site_long_you,';
    $join_availability = '
  LEFT JOIN '.TABLE_AVAILABILITY.' ON (
    '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_AVAILABILITY.'.site_id = "'.$ofs_customer_site_id.'")
  LEFT JOIN '.NEW_TABLE_SITES.' ON '.NEW_TABLE_SITES.'.site_id = "'.$ofs_customer_site_id.'"';
  }
else
  {
    $select_availability = '
    1 AS availability,';
    $join_availability = '';
    $no_site_selected = '';
  }

$query = '
  SELECT
    '.TABLE_PRODUCER_LOGOS.'.logo_id,
    '.TABLE_SUBCATEGORY.'.subcategory_id,
    COUNT('.TABLE_SUBCATEGORY.'.subcategory_id) AS subcat_count,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_PRODUCER.'.producttypes,
    '.TABLE_PRODUCER.'.about,
    '.TABLE_PRODUCER.'.highlights,'.
    $select_availability.'
    IF('.TABLE_PRODUCER.'.unlisted_producer < 1, COUNT('.NEW_TABLE_PRODUCTS.'.product_id), 0) AS product_count,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_quantity
  FROM
    '.TABLE_PRODUCER.'
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' USING(subcategory_id)
  LEFT JOIN '.TABLE_INVENTORY.' USING(inventory_id)
  LEFT JOIN '.TABLE_PRODUCER_LOGOS.' ON '.TABLE_PRODUCER.'.producer_id = '.TABLE_PRODUCER_LOGOS.'.producer_id'.
  $join_availability.'
  WHERE
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"
    AND '.NEW_TABLE_PRODUCTS.'.active = 1
    AND '.NEW_TABLE_PRODUCTS.'.approved = 1
    AND '.TABLE_PRODUCER.'.unlisted_producer != 2 /* NOT SUSPENDED */'.
    $show_unlisted_query.'
  GROUP BY
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_SUBCATEGORY.'.subcategory_id
  HAVING
    availability = 1
  ORDER BY
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_SUBCATEGORY.'.subcategory_name';
$result = @mysqli_query ($connection, $query) or die(debug_print ("ERROR: 897650 ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
$display = '';
// debug_print ("INFO: ", array ($query,mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__);
while ( $row = mysqli_fetch_array ($result, MYSQLI_ASSOC) )
  {
    $producer_id = $row['producer_id'];
    $business_name = $row['business_name'];
    $producer_about = $row['about'];
    $producer_highlights = $row['highlights'];
    $producttypes = $row['producttypes'];
    $product_count = $row['product_count'];
    $subcat_count = $row['subcat_count'];
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = $row['subcategory_name'];
    $logo_id = $row['logo_id'];
    if (strlen ($row['site_long_you']) > 0) $site_long_you = $row['site_long_you']; // Because it has a null value for some rows
    if ($product_count > 0 || $show_all)
      {
        if ($producer_id != $producer_id_prior && $producer_id_prior != '') // Show accumulated data for the prior producer
          {
            $display .= '
            <div class="producer_listing">
              <div class="producer_details">
                <a href="'.PATH.'product_list.php?type=customer_list&select_type=producer_id&producer_id='.$producer_id_prior.'" class="business_name">'.
                  (strlen ($logo_id_prior) > 0 ? '<img class="producer_logo" src="'.PATH.'show_logo.php?logo_id='.$logo_id_prior.'">' : '').
                  $business_name_prior.'
                </a>'.
                (strlen ($producer_about) > 0 ? '
                <span class="producer_section about">'.
                  substr($producer_about_prior, 0, 255).(strlen ($producer_about_prior) > 255 ? ' ...<a href="'.PATH.'product_list.php?type=customer_list&select_type=producer_id&producer_id='.$producer_id_prior.'">[read more]</a>' : '').'
                </span>' : '').
                (strlen ($producer_highlights) > 0 ? '
                <span class="producer_section highlights">'.
                  $producer_highlights_prior.'
                </span>' : '').'
                <span class="producer_section producttype_text">'.
                  $producttypes_markup.'
                </span>
              </div>
              <div class="subcategory_list">'.
                $subcategory_markup.'
              </div>
            </div>';
            $subcategory_markup = '
                  <div class="subcategory subcat-'.$subcategory_id.'">
                    <a class="subcat" href="'.PATH.'product_list.php?type=customer_list&select_type=producer_id&producer_id='.$producer_id.'&subcat_id='.$subcategory_id.'">'.$subcategory_name.'<span class="count"> &ndash; '.$subcat_count.' '.Inflect::pluralize_if ($subcat_count, 'item').'</span></a>
                  </div>';
          }
        else // Just show next product type
          {
            $subcategory_markup .= '
                  <div class="subcategory subcat-'.$subcategory_id.'">
                    <a class="subcat" href="'.PATH.'product_list.php?type=customer_list&select_type=producer_id&producer_id='.$producer_id.'&subcat_id='.$subcategory_id.'">'.$subcategory_name.'<span class="count"> &ndash; '.$subcat_count.' '.Inflect::pluralize_if ($subcat_count, 'item').'</span></a>
                  </div>';
          }
        $producer_id_prior = $producer_id;
        $producer_about_prior = $producer_about;
        $producer_highlights_prior = $producer_highlights;
        $business_name_prior = $business_name;
        $logo_id_prior = $logo_id;
        $producttypes_markup = $producttypes;
      }
  }
// Add the last set of information that was not already handled by the while-loop
$display .= '
            <div class="producer_listing">
              <div class="producer_details">
                <a href="'.PATH.'product_list.php?type=customer_list&select_type=producer_id&producer_id='.$producer_id_prior.'" class="business_name">'.
                  (strlen ($logo_id_prior) > 0 ? '<img class="producer_logo" src="'.PATH.'show_logo.php?logo_id='.$logo_id_prior.'">' : '').
                  $business_name_prior.'
                </a>'.
                (strlen ($producer_about) > 0 ? '
                <span class="producer_section about">'.
                  substr($producer_about_prior, 0, 255).(strlen ($producer_about_prior) > 255 ? ' ...<a href="'.PATH.'product_list.php?type=customer_list&select_type=producer_id&producer_id='.$producer_id_prior.'">[read more]</a>' : '').'
                </span>' : '').
                (strlen ($producer_highlights) > 0 ? '
                <span class="producer_section highlights">'.
                  $producer_highlights_prior.'
                </span>' : '').'
                <span class="producer_section producttype_text">'.
                  $producttypes_markup.'
                </span>
              </div>
              <div class="subcategory_list">'.
                $subcategory_markup.'
              </div>'.
              // Display a no-site-selected message if appropriate
              $no_site_selected.'
            </div>';

if ($show_all)
  {
    $content_producer_list .= '
    <p>All producers listed below have been approved for selling by '.SITE_NAME.', although some
    may not currently have products for sale.  Also available is a list of only those
    <a href="'.$_SERVER['SCRIPT_NAME'].'?display_as='.$display_as.'">producers with products for sale at this time</a>.</p>';
  }
else
  {
    $content_producer_list .= '
    <p>Only coop producer members with products available for sale are listed on this page.
    <a href="'.$_SERVER['SCRIPT_NAME'].'?show=all&display_as='.$display_as.'">Click here for a complete listing</a>
    of the producer members, irrespective of the current status of their product offerings.</p>';
  }
$content_producer_list .= '
    <p>Not from this region? Don&rsquo;t despair. Many of these producers are ready and able
    to ship their products to you, including frozen meats! Please contact the producers
    directly about the shipping policies. </p>
          <div id="producer_list" class="subpanel '.$display_as.'"">
            <header>Producer List &mdash; <span class="list">List View</span><span class="grid">Grid View</span><span class="toggle" onclick="toggle_view();">Switch View</span></header>
            <div class="producer_list_inner">
              <span class="levelX">
              </span>'.
              $display.'
            </div>
          </div>';

$page_specific_stylesheets['producer_list'] = array (
  'name'=>'producer_list',
  'src'=>BASE_URL.PATH.'css/openfood-producer_list.css',
  'dependencies'=>array('openfood'),
  'version'=>'2.1.1',
  'media'=>'all'
  );

$page_specific_css .= '
  #producer_list.list header {
    background-image:url("'.DIR_GRAPHICS.'icon-list.png");
    }
  #producer_list.grid header {
    background-image:url("'.DIR_GRAPHICS.'icon-grid.png");
    }';

$page_specific_javascript .= '
  function set_view (view_type) {
    jQuery.each(["list", "grid"], function(index, view_option) {
      if (view_type == view_option) {
        /* jQuery("#view_option_"+view_type).addClass("selected"); */
        jQuery("#producer_list").addClass(view_type);
        }
      else {
        /* jQuery("#view_option_"+view_option).removeClass("selected"); */
        jQuery("#producer_list").removeClass(view_option);
        }
      });
    }
  /* Toggle between the views */
  function toggle_view() {
    var classes = ["list", "grid"];
    if (jQuery("#producer_list").hasClass("grid")) { set_view ("list"); }
    else if (jQuery("#producer_list").hasClass("list")) { set_view ("grid"); }
    };';

$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '
  <span class="subtitle">'.$subtitle.
    (strlen ($site_long_you) > 0 ? '
      <span class="subtitle_site" title="Change this?" onclick="popup_src(\''.BASE_URL.PATH.'customer_select_site.php?display_as=popup\', \'customer_select_site\', \'\', false);">'.$site_long_you.'</span>'
    : '').'
  </span>';
$page_title = 'Products: '.($show_all ? 'Full Producer List' : 'Active Producers');
$page_tab = 'shopping_panel';

// Let the header know to handle this as a product_list page (i.e. ask for customer_site if needed)
$is_customer_product_page = true;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_producer_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
