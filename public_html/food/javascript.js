/* Commonly used Javascript Functions */

// Display an external page using an iframe
// http://www.ericmmartin.com/projects/simplemodal/
// Popup the simplemodal dialog for selecting membership renewal type

function popup_html(html, modalID = "", closeTarget = "") {
  // In the next line, the class = modalClose is the normal way to close the modal window but we will
  // replace that with an explicit call to closeTarget, if it is specified.
  jQuery.modal('<a class="modalCloseImg'+(closeTarget.length == 0 ? ' modalClose"' : '" onclick="window.location=\''+closeTarget+'\'"')+'>&nbsp;</a>'+html, {
    overlayId:"simplemodal-overlay-"+modalID,
    containerId:"simplemodal-container-"+modalID,
    closeClass:"modalClose",
    focus:false,
    modal:true,
    overlayClose:false
    });
  };

// This is just a special case of popup_html()
function popup_src(src, modalID = "", closeTarget = "") {
  popup_html('<iframe src="'+src+'" id="simplemodal-iframe-'+modalID+'">', modalID = "", closeTarget = "");
  }
