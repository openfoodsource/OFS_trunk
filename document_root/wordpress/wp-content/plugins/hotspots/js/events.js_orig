/**
 * Events
 */
var events = new function() {
	
	/**
	 * Sets up save page views
	 */
	this.setupSavePageViews = function() {
		this.saveEvent(config_data.user_id, config_data.user_environment_id, 'page_view', 'Page viewed at URL ' + window.location.href + '.', null, '', '', '');
	};
	
	/**
	 * Sets up save ajax actions
	 */
	this.setupSaveAjaxActions = function() {
		// Intercept all global AJAX responses and send a ping with ajax action to the server
		// except for url_ping and ajax_ping
		jQuery(document).ajaxSuccess(function(event, xhr, settings) {
			var ajaxAction = '';
			if (settings.data) {
				var dataParts = settings.data.split("&");
				ajaxAction = dataParts[0].split("=")[1];
			} else if (settings.url) {
				ajaxAction = utils.getUrlParamByName(settings.url, "action");
			} else {
				// cannot get ajax action
				return;
			}
			var temp = config_data.ignore_ajax_actions[ajaxAction];
			if (jQuery.inArray(ajaxAction, config_data.ignore_ajax_actions) != -1)
				return; // ignore this ajax call
			
			var statusText = xhr.statusText;
			var description = 'Ajax action ' + ajaxAction + ' was made.';
			
			events.saveEvent(config_data.user_id, config_data.user_environment_id, 'ajax_action', description , null, null, '', statusText);
		});
	};
	
	/**
	 * Sets up saving custom events
	 */
	this.setupSaveCustomEvents = function() {
		for (var index in config_data.custom_events) {
			var customEvent = config_data.custom_events[index]['custom_event'];
			var description = config_data.custom_events[index]['description'];
			var eventType = config_data.custom_events[index]['event_type'];
			var isFormSubmit = config_data.custom_events[index]['is_form_submit'] == "1" ? true : false;
			var isMouseClick = config_data.custom_events[index]['is_mouse_click'] == "1" ? true : false;
			var isTouchscreenTap = config_data.custom_events[index]['is_touchscreen_tap'] == "1" ? true : false;
			this.bindCustomEvent(customEvent, description, eventType, isFormSubmit, isMouseClick, isTouchscreenTap);
		}
	};
	
	/**
	 * Binds custom events
	 */
	this.bindCustomEvent = function(selector, description, eventType, isFormSubmit, isMouseClick, isTouchscreenTap) {
		if (isTouchscreenTap) {
			this.setupSaveTouchscreenTapEvent(selector, eventType, description);
		}
		if (isMouseClick) {
			this.setupSaveMouseClickEvent(selector, eventType, description);
		}
		if (isFormSubmit) {
			jQuery(selector).submit(function() {				
				events.saveEvent(config_data.user_id, config_data.user_environment_id, eventType, description , null, null, false, selector);
			});
		}
	};
	
	/**
	 * Sets up saving mouse clicks and touchscreen tap events
	 */
	this.setupSaveMouseClickAndTouchScreenTapEvents = function() {
		this.setupSaveTouchscreenTapEvent();
		this.setupSaveMouseClickEvent(document);
	};
	
	/**
	 * Trigger setup for saving touchscreen tap events
	 */
	this.setupSaveTouchscreenTapEvent = function(selector, eventType, description, data) {
		
		var touchData = {
			started : null, // detect if a touch event is sarted
			currrentX : 0,
			yCoord : 0,
			previousXCoord : 0,
			previousYCoord : 0,
			touch : null
		};

		jQuery(selector).on("touchstart", function(e) {
			touchData.started = new Date().getTime();
			var touch = e.originalEvent.touches[0];
			touchData.previousXCoord = touch.pageX;
			touchData.previousYCoord = touch.pageY;
			touchData.touch = touch;
		});
		
		jQuery(selector).on(
				"touchend touchcancel",
				function(e) {
					var now = new Date().getTime();
					// Detecting if after 200ms if in the same position.
					// FIXME taps are not always recorded if the browser 
					// takes over before the AJAX call is made. So this 
					// means we will get some, and lose some.
					if ((touchData.started !== null)
							&& ((now - touchData.started) < 200)
							&& (touchData.touch !== null)) {
						var touch = touchData.touch;
						var xCoord = touch.pageX;
						var yCoord = touch.pageY;
						if ((touchData.previousXCoord === xCoord)
								&& (touchData.previousYCoord === yCoord)) {
							
							if (jQuery('#wpadminbar').length > 0) {
								yCoord -= jQuery('#wpadminbar').height();
							}
							
							if (description == null || description == '') {
								description = 'A touchscreen tap was made at x = ' + xCoord + ' and y = ' + yCoord + '.';
							}
							if (eventType == null || eventType == '') {
								eventType = 'touchscreen_tap';
							}
							if (data == null) {
								if (selector !== document) {
									data = selector;
								} else {
									data = '';
								}
							}
							events.saveEvent(config_data.user_id, config_data.user_environment_id, eventType, description, xCoord, yCoord, true, data);
						}
					}
					touchData.started = null;
					touchData.touch = null;
				});
	};
	
	/**
	 * Trigger and setup for savig mouse click events
	 */
	this.setupSaveMouseClickEvent = function(selector, eventType, description, data) {
		// support mouse clicks always
		jQuery(document).on('click', selector, function(e) {
			var event = e ? e : window.event;
			var coords = utils.getEventXYCoords(event);
			var xCoord = coords.xCoord;
			var yCoord = coords.yCoord;

			if (description == null || description == '') {
				description = 'A mouse click was made at x = ' + xCoord + ' and y = ' + yCoord + '.';
			}
			if (eventType == null || eventType == '') {
				eventType = 'mouse_click';
			}
			if (data == null) {
				if (selector !== document) {
					data = selector;
				} else {
					data = '';
				}
			}
			events.saveEvent(config_data.user_id, config_data.user_environment_id, eventType, description, xCoord, yCoord, false, data);
		});
	};
	
	/**
	 * Saves an event
	 * 
	 * @param userId
	 * @param userEnvironmentId
	 * @param eventType
	 * @param url
	 * @param description
	 * @param xCoord
	 * @param yCoord
	 * @param pageWidth
	 */
	this.saveEvent = function(userId, userEnvironmentId, eventType, description, xCoord, yCoord, isTap, data) {
		
		// remove hash tags from URL
		var url = window.location.href;
		var hashIndex = url.indexOf('#');
		if (hashIndex > 0) {
			url = url.substring(0, hashIndex);
		}
		
		userId = config_data.user_id;
		userEnvironmentId = config_data.user_environment_id;
		
		var data = {
				action : "save_user_event",
				nonce : config_data.ajax_nonce,
				pluginVersion : config_data.plugin_version,
				
				userId : userId,
				userEnvironmentId : userEnvironmentId,
				eventType : eventType,
				url : url,
				description : description,
				xCoord : xCoord,
				yCoord : yCoord,
				pageWidth : utils.getPageWidth(),
				data : '',
				ipAddress : config_data.ip_address,
				sessionId : config_data.session_id,
				debug : debug,
				drawHeatMapEnabled : drawHeatmapEnabled,
				spotRadius : spotRadius,
				widthAllowance : config_data.width_allowance
		};

		
		jQuery.post(config_data.ajax_url, data, function(response) {
			var eventData = jQuery.parseJSON(response);
			
			if ((data.eventType == "mouse_click" || data.eventType == "touchscreen_tap") && debug == true && drawHeatmapEnabled === true) {
				if (isHeatmap) {
					// if heatmap.js
					heatmap.store.addDataPoint(xCoord, yCoord, 1);
				} else {
					var heatValue = eventData.heat_value;
					drawing.drawArc(xCoord, yCoord, heatValue);
				}
			}
		});
	};
};
