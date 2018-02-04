// Globals
var drawHeatmapEnabled = false;
var spotRadius = 6;
var hot = 20; // default is 20
var warm = hot / 2; // default is 10
var opacity = 0.2; // default is 0.2
var isHeatmap = false; // for heatmap.js
var debug = false;

jQuery(window).load(function() {	
	
	initOptions();
	
	setupSaveEvents();

	setupDrawing();
	
});


/**
 * Initialises plugin options
 */
function initOptions() {
	drawHeatmapEnabled = (config_data.draw_heat_map_enabled) == "1" ? true : false;
	
	// Check for drawHeatMap query param
	var drawHeatmapQueryParam = utils.getUrlParamByName(window.location.href, 'drawHeatmap') === "true" ? true : false;
	if (drawHeatmapQueryParam == false) {
		// cannot enable drawing heat map without the query param set to true
		drawHeatmapEnabled = false;
	}
	
	debug = (config_data.debug) == "1" ? true : false;
	isHeatmap = (config_data.use_heatmapjs) == "1" ? true : false;
	hot = parseInt(config_data.hot_value);
	warm = hot / 2;
	opacity = config_data.spot_opacity;
	spotRadius = parseInt(config_data.spot_radius);	
}

/**
 * Sets up save events
 */
function setupSaveEvents() {
	// Page views
	var savePageViews = (config_data.save_page_views) == "1" ? true : false;
	if (savePageViews) {
		events.setupSavePageViews();
	}
	
	// Ajax actions
	var saveAjaxActions = (config_data.save_ajax_actions) == "1" ? true : false;
	if (saveAjaxActions) {
		events.setupSaveAjaxActions();
	}
	
	// Mouse clicks and touchscreen taps
	var saveMouseClickAndTouchscreenTaps = (config_data.save_click_or_tap_enabled) == "1" ? true : false;
	var urlDBLimitReached = (config_data.url_db_limit_reached) == "1" ? true : false;
	var urlExcluded = (config_data.url_excluded) == "1" ? true : false;
	var scheduleCheck = (config_data.schedule_check) == "1" ? true : false;
	if (urlDBLimitReached == true || scheduleCheck == false || urlExcluded == true) {
		saveMouseClickAndTouchscreenTaps = false;
	}
	if (saveMouseClickAndTouchscreenTaps) {
		events.setupSaveMouseClickAndTouchScreenTapEvents();
	}
	
	// Custom events
	var saveCustomEvents = (config_data.save_custom_events) == "1" ? true : false;
	if (saveCustomEvents) {
		events.setupSaveCustomEvents();
	}
}

/**
 * Sets up drawing heatmap
 */
function setupDrawing() {
	if (drawHeatmapEnabled) {	
		
		utils.showLoadingDialog();
		
		drawing.init();
		
		// redraw heat map if window is resized
		jQuery(window).resize(function() {
			// TODO: don't do anything until a small delay
			
			if (drawHeatmapEnabled) {	
	
				drawing.destroy();
				
				drawing.init();
		
				// redraw the heat map
				drawing.drawHeatmap();
				
				jQuery("#infoPanel").remove();
				drawing.setupInfoPanel(false);
				drawing.refreshInfoPanel();
			}
		});
		
		// Now draw the hot spots
		drawing.drawHeatmap();
		
		// at then end, add info panel over the top
		drawing.setupInfoPanel(true);
	}
}

/**
 * This object is intended to be used to manually code custom events
 */
var hotspots = new function() {
	/**
	 * This function abstracts the user data for saving an event
	 */
	this.saveEvent = function(eventType, description, data) {
		userId = config_data.user_id;
		userEnvironmentId = config_data.user_environment_id;
		
		return events.saveEvent(userId, userEnvironmentId, eventType, description, '', '', '', data);
	}
};