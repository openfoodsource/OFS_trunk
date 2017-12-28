<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');

// In the case of site-admin auth_types, allow resetting the MOTD views in the database
if (CurrentMember::auth_type('site_admin'))
  {
    if ($_GET['action'] == 'reset_motd')
      {
        $query = '
          DELETE FROM '.NEW_TABLE_STATUS.'
          WHERE
            status_scope = "motd_viewed"
            AND status_value = "popup"';
        $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 786340 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
        // We would send the return value and exit here, but we still need to get
        // the current number of views to send back, so do the next query first...
      }
    $query = '
      SELECT
        COUNT(status_key) AS total_views,
        MIN(timestamp) AS oldest_view
      FROM '.NEW_TABLE_STATUS.'
      WHERE
        status_scope = "motd_viewed"
        AND status_value = "popup"';
    $result = @mysqli_query ($connection, $query) or die (debug_print ("ERROR: 578230 ", array ($query, mysqli_error ($connection)), basename(__FILE__).' LINE '.__LINE__));
    if ($row = mysqli_fetch_object ($result))
      {
        $total_views = $row->total_views;
        $oldest_view = $row->oldest_view;
      }
    $views_text = 'Viewed by '.$total_views.' '.Inflect::pluralize_if($total_views, 'member').(isset ($oldest_view) ? ' since<br />'.$oldest_view : '').'.';
    if ($_GET['action'] == 'reset_motd')
      {
        echo $views_text;
        exit (0);
      }
    $motd_reset = '
      <fieldset id="motd_admin" onclick="jQuery(this).toggleClass(\'show\');">
        <legend>Admin Function</legend>
        <div class="admin_contents">
          <div class="instructions">
            Administrators may edit the MOTD message (in HTML) under Site Admin / Edit Site Configuration. By default, members will be forced to view the MOTD every '.(MOTD_REPEAT_TIME).' days.
            Pressing the reset button below (twice to confirm) will force all members to view the MOTD the next time they access this site.
          </div>
          <span id="total_views" class="total_views">'.$views_text.'</span>
          <input id="reset_motd" class="reset_motd" type="button" onblur="reset_motd(this,\'clear\')" onclick="reset_motd(this,\'set\')" value="RESET ALL MOTD VIEWS" title="RESET ALL MOTD VIEWS">
        </div>
      </div>';
  }
if($_GET['display_as'] == 'popup')
  {
    $display_as_popup = true;
    // Make note that this member saw the MOTD
    ofs_put_status ('motd_viewed', $_SESSION['member_id'], 'popup', MOTD_REPEAT_TIME * 24 * 60);
  }
else
  {
    // Don't allow direct access to this page
    header('Location: '.PATH.'panel_member.php');
  }
$page_specific_javascript = '
  // This function requires two clicks to execute, changing style between.
  function reset_motd (obj, action) {
    if (action == "set") {
      if (jQuery(obj).hasClass("warn")) {
        jQuery.get("'.BASE_URL.PATH.'motd.php?action=reset_motd", function(data) {
          // The returned value is the "total_views" text
          jQuery("#total_views").html(data);
          jQuery(obj).removeClass("warn");
          })
        }
      jQuery(obj).addClass("warn");
      }
    if (action == "clear") {
      jQuery(obj).removeClass("warn");
      }
    }';
$page_specific_css = '
  .alert_box {
    width:35%;
    padding:10px;
    margin:0 0 35px 15px;
    background-color:#800;
    float:right;
    border:2px solid #ffe;
    box-shadow:0 0 0 5px #800, 5px 5px 10px 5px #222;
    font-size:18px;
    text-align:center;
    color:#ffe;
    transform:rotate(7deg);
    }
  #motd_admin {
    box-sizing:border-box;
    font-size:0.4rem;
    border-radius:1em;
    border:0;
    min-width:12rem;
    max-width:12rem;
    min-height:4rem;
    background-color:#eea;
    position:absolute;
    bottom:1rem; right:1rem;
    opacity:0.5;
    transition:all 0.5s;
    }
  #motd_admin.show,
  #motd_admin:hover {
    font-size:1.6rem;
    border:1px solid #800;
    border-radius:0.5em;
    opacity:1;
    max-width:350px;
    transition:all 0.5s;
    }
  #motd_admin legend {
    box-sizing:border-box;
    font-size:4em;
    margin:0;
    padding:0 1em;
    height:2.55em;
    width:100%;
    color:#000;
    border:1px solid #800;
    border-radius:0.25em;
    background-color:#eea;
    position:absolute;
    top:0em;
    left:-0;
    transition:all 0.5s;
    }
  #motd_admin.show legend,
  #motd_admin:hover legend {
    font-size:0.65em;
    margin:0 1em;
    height:1em;
    width:50%;
    border-bottom:0;
    border-top-left-radius:0.67em;
    border-top-right-radius:0.67em;
    border-bottom-left-radius:0;
    border-bottom-right-radius:0;
    top:-1.3em;
    transition:all 0.5s;
    line-height:1.25em
    }
  #motd_admin .instructions {
    font-size:0.6em;
    padding:1em;
    line-height:1;
    color:#000;
    transition:all 0.5s;
    }
  #motd_admin.show .instructions,
  #motd_admin:hover .instructions {
    transition:all 0.5s;
    }
  #motd_admin #reset_motd,
  #motd_admin #reset_motd {
    display:block;
    font-size:0em;
    margin:0.2em auto;
    transition:all 0.5s;
    }
  #motd_admin.show #reset_motd,
  #motd_admin:hover #reset_motd {
    display:block;
    font-size:0.6em;
    transition:all 0.5s;
    }
  #motd_admin #total_views {
    display:block;
    font-size:0.8em;
    margin:1em auto;
    text-align:center;
    }
  #motd_admin #reset_motd.warn {
    color:#fff;
    background-color:#a00;
    }
  '.MOTD_CSS;

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.MOTD_CONTENT.$motd_reset.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
