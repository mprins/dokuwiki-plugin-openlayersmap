/*
 * Copyright (c) 2013-2014 Mark C. Prins <mprins@users.sf.net>
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
 * @class OpenLayersMap.Control.Zoom with built-in tooltips and proper semantic
 *        elements.
 * @extends OpenLayers.Control.Zoom
 * @requires OpenLayers/Control/Zoom.js
 * @author Mark C. Prins <mprins@users.sf.net>
 * 
 */
OpenLayersMap.Control.Zoom = OpenLayers.Class(OpenLayers.Control.Zoom, {
	/**
	 * 
	 * @param el
	 *            {Element} parent DOM element, eg a div
	 * @return {Object} containing two DOM elements, zoomIn and zoomOut
	 * @override
	 */
	getOrCreateLinks : function(el) {
		var zoomIn = document.getElementById(this.zoomInId), zoomOut = document.getElementById(this.zoomOutId);
		if (!zoomIn) {
			zoomIn = document.createElement('button');
			zoomIn.name = 'ZoomIn';
			zoomIn.setAttribute('type' ,'button');
			zoomIn.insertAdjacentHTML('afterbegin', '<span role="tooltip">' + OpenLayers.i18n('zoom_in') + '</span>'
					+ this.zoomInText);
			el.appendChild(zoomIn);
		}
		OpenLayers.Element.addClass(zoomIn, 'olControlZoomIn olButton olHasTooltip_bttm_r');
		if (!zoomOut) {
			zoomOut = document.createElement('button');
			zoomOut.name = 'ZoomOut';
			zoomOut.setAttribute('type' ,'button');
			zoomOut.insertAdjacentHTML('afterbegin', '<span role="tooltip">' + OpenLayers.i18n('zoom_out') + '</span>'
					+ this.zoomOutText);
			el.appendChild(zoomOut);
		}
		OpenLayers.Element.addClass(zoomOut, 'olControlZoomOut olButton olHasTooltip_bttm_r');
		return {
			zoomIn : zoomIn,
			zoomOut : zoomOut
		};
	},
	CLASS_NAME : 'OpenLayersMap.Control.Zoom'
});
