/*
 * Copyright (c) 2014-2015 Mark C. Prins <mprins@users.sf.net>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * Button control to switch the map to fullscreen and back. Note that fullscreen
 * support is still experimental in most browsers.
 *
 * @author Mark C. Prins <mprins@users.sf.net>
 * @extends {OpenLayers.Control}
 * @requires OpenLayers/Control.js
 */
OpenLayersMap.Control.Fullscreen = OpenLayers.Class(OpenLayers.Control, {
	fullscreenClass : 'fullscreen',
	/**
	 * maximize screen button.
	 *
	 * @private
	 */
	maxBtn : null,

	/**
	 * minimize screen button.
	 *
	 * @private
	 */
	minBtn : null,

	/**
	 * @constructor OpenLayersMap.Control.Fullscreen Create a control to toggle
	 *              fullscreen display of the map.
	 * @param options
	 *            {Object} Properties of this object will be set on the control
	 */
	initialize : function(options) {
		OpenLayers.Control.prototype.initialize.apply(this, [ options ]);
		this.displayClass = 'olControlFullscreen';

		// listen for fullscreenchange event on document eg. escape or F11 key
		OpenLayers.Event.observe(document, 'fullscreenchange', OpenLayers.Function.bind(function() {
			if (document.fullscreenElement == null) {
				this.restore();
			}
		}, this));
		OpenLayers.Event.observe(document, 'webkitfullscreenchange', OpenLayers.Function.bind(function() {
			if (document.webkitCurrentFullScreenElement == null) {
				this.restore();
			}
		}, this));
		OpenLayers.Event.observe(document, 'mozfullscreenchange', OpenLayers.Function.bind(function() {
			if (document.mozFullScreenElement == null) {
				this.restore();
			}
		}, this));
		OpenLayers.Event.observe(document, 'msfullscreenchange', OpenLayers.Function.bind(function() {
			if (document.fullscreenElement == null) {
				this.restore();
			}
		}, this));
	},

	/**
	 * restore document mode.
	 */
	restore : function() {
		if (document.exitFullscreen) {
			document.exitFullscreen();
		} else if (document.msRequestFullscreen) {
			document.msExitFullscreen();
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		} else if (document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		}
		OpenLayers.Element.removeClass(this.map.div, this.fullscreenClass);
		this.map.updateSize();
		this.maxBtn.style.display = '';
		this.minBtn.style.display = 'none';
		this.map.div.focus();
	},

	/**
	 * go full screen.
	 */
	fullscreen : function() {
		if (this.map.div.requestFullscreen) {
			this.map.div.requestFullscreen();
		} else if (this.map.div.msRequestFullscreen) {
			this.map.div.msRequestFullscreen();
		} else if (this.map.div.mozRequestFullScreen) {
			this.map.div.mozRequestFullScreen();
		} else if (this.map.div.webkitRequestFullscreen) {
			this.map.div.webkitRequestFullscreen();
		}
		OpenLayers.Element.addClass(this.map.div, this.fullscreenClass);
		this.map.updateSize();
		this.maxBtn.style.display = 'none';
		this.minBtn.style.display = '';
		this.map.div.focus();
	},

	/**
	 * Toggle fullscreen mode depending on the button clicked.
	 *
	 * @param evt
	 *            {Event}
	 * @return {Boolean} true when event was handled
	 */
	onButtonClick : function(evt) {
		if (evt.buttonElement === this.maxBtn) {
			this.fullscreen();
			return true;
		} else if (evt.buttonElement === this.minBtn) {
			this.restore();
			return true;
		} else {
			return false;
		}
	},
	/**
	 * Render the control in the browser.
	 *
	 * @return {Element} DOM element, a div with the buttons
	 * @override
	 */
	draw : function() {
		OpenLayers.Control.prototype.draw.apply(this, arguments);

		// fullscreen button
		this.maxBtn = document.createElement('button');
		this.maxBtn.insertAdjacentHTML('afterbegin', '<span role="tooltip">' + OpenLayers.i18n('toggle_fullscreen')
				+ '</span>&#x2708;');
		this.maxBtn.name = 'fullscreen';
		this.maxBtn.setAttribute('type', 'button');
		this.maxBtn.className = this.displayClass + 'Max olButton olHasTooltip_bttm_r';
		this.div.appendChild(this.maxBtn);

		// restore button
		this.minBtn = document.createElement('button');
		this.minBtn.insertAdjacentHTML('afterbegin', '<span role="tooltip">' + OpenLayers.i18n('toggle_fullscreen')
				+ '</span>&#x2715;');
		this.minBtn.name = 'restorefullscreen';
		this.minBtn.setAttribute('type', 'button');
		this.minBtn.style.display = 'none';
		this.minBtn.className = this.displayClass + 'Min olButton olHasTooltip_bttm_r';
		this.div.appendChild(this.minBtn);

		this.map.events.on({
			buttonclick : this.onButtonClick,
			scope : this
		});
		return this.div;
	},

	CLASS_NAME : "OpenLayersMap.Control.Fullscreen"
});
