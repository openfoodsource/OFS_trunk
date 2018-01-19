/* Commonly used Javascript Functions */

// Display an external page using an iframe
// http://www.ericmmartin.com/projects/simplemodal/
// Popup a simplemodal dialog, such as for selecting membership renewal type

function popup_html (html, modalID, closeTarget, overlayClose) {
  // Default overlayClose = false
  if (overlayClose === undefined) {
    overlayClose = false;
    }
  // In the next line, the class = modalClose is the normal way to close the modal window but we will
  // replace that with an explicit call to closeTarget, if it is specified.
  $.modal('<!-- <a class="modalCloseImg'+(closeTarget.length == 0 ? ' modalClose"' : '" onclick="window.location=\''+closeTarget+'\'"')+'>&nbsp;</a> -->'+html, {
    overlayId:"simplemodal-overlay-"+modalID,
    containerId:"simplemodal-container-"+modalID,
    closeClass:"modalClose",
    focus:false,
    modal:true,
    overlayClose:overlayClose
    });
  // Assign a class to the iframe body element
  jQuery("iframe").on('load', function() {
    jQuery("iframe").contents().find("body").addClass("iframe-"+modalID);
    });
  };

// This is just a special case of popup_html()
function popup_src (src, modalID, closeTarget, overlayClose) {
  // Default overlayClose = false
  if (overlayClose === undefined) {
    overlayClose = false;
    }
  popup_html('<iframe src="'+src+'" id="simplemodal-iframe-'+modalID+'">', modalID = modalID, closeTarget = closeTarget, overlayClose);
  }

function reload_parent (delay) {
  // Default delay = 0
  if (delay === undefined) {
    delay = 0;
    }
  setTimeout(function(){
    location.reload(true);
    }, delay);
  }

function just_close (delay) {
  // Default delay = 0
  if (delay === undefined) {
    delay = 0;
    }
  setTimeout(function(){
    $.modal.close();
    }, delay);
  }


/* Handle events for pagers
Sample code for the pager(s) follows (use a unique descriptive ID for the outer div):
    <form id="product_list_pager" name="product_list_pager" action="'.$_SERVER['SCRIPT_NAME'].'" method="GET">
      <div id="product_list_pager_container" class="pager">
        <span class="button_position">
          <div id="product_list_pager_decrement" class="pager_decrement" onclick="decrement_pager(jQuery(this).closest(\'form\').attr(\'id\'));"><span>&ominus;</span></div>
        </span>
        <input type="hidden" id="product_list_pager_slider_prior" value="'.$_GET['page'].'">
        <span class="pager_center">
          <input type="range" id="product_list_pager_slider" class="pager_slider" name="page" min="1" max="'.$data['last_page'].'" step="1" value="'.$_GET['page'].'" onmousemove="update_pager_display(jQuery(this).closest(\'form\').attr(\'id\'));" onchange="goto_pager_page(jQuery(this).closest(\'form\').attr(\'id\'));">
        </span>
        <span class="button_position">
          <div id="product_list_pager_increment" class="pager_increment" onclick="increment_pager(jQuery(this).closest(\'form\').attr(\'id\'));"><span>&oplus;</span></div>
        </span>
      </div>
      <output id="product_list_pager_display_value" class="pager_display_value">Page '.$_GET['page'].'</output>
    </form>
    <div class="clear"></div>
*/
function update_pager_display (pager_id) {
  var display_element = document.getElementById(pager_id+"_display_value");
  var slider_element = document.getElementById(pager_id+"_slider");
  var slider_element_prior = document.getElementById(pager_id+"_slider_prior");
  if (parseInt(slider_element.value) == parseInt(slider_element_prior.value)) {
    display_element.innerHTML = "Page "+slider_element.value;
    }
  else {
    display_element.innerHTML = "Go to Page "+slider_element.value;
    }
  }
function decrement_pager (pager_id) {
  var slider_element = document.getElementById(pager_id+"_slider");
  var display_element = document.getElementById(pager_id+"_display_value");
  if (parseInt(slider_element.value) > parseInt(slider_element.min)) {
    slider_element.value = -- slider_element.value;
    update_pager_display(pager_id);
    display_element.innerHTML = "Loading Page "+slider_element.value;
    goto_pager_page(pager_id);
    }
  }
function increment_pager (pager_id) {
  var slider_element = document.getElementById(pager_id+"_slider");
  var display_element = document.getElementById(pager_id+"_display_value");
  if (parseInt(slider_element.value) < parseInt(slider_element.max)) {
    slider_element.value = ++ slider_element.value;
    update_pager_display(pager_id);
    display_element.innerHTML = "Loading Page "+slider_element.value;
    goto_pager_page(pager_id);
    }
  }
function goto_pager_page (pager_id) {
  // If there is a pager override function, do that instead
  var display_element = document.getElementById(pager_id+"_display_value");
  var slider_element = document.getElementById(pager_id+"_slider");
  if (typeof goto_pager_page_override == 'function') { 
    goto_pager_page_override(pager_id); 
    display_element.innerHTML = "Page "+slider_element.value;
    }
  else {
    var form_element = document.getElementById(pager_id);
    document.forms[pager_id].submit();
    jQuery("#"+pager_id+"_container").fadeOut("slow");
    display_element.innerHTML = "Loading Page "+slider_element.value;
    }
  }

// Debounce function from: http://davidwalsh.name/javascript-debounce-function
// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce (func, wait, immediate) {
  var timeout;
  return function() {
    var context = this, args = arguments;
    var later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
      };
    var callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
    };
  };
