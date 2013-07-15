/*
 * Copyright (c) 2013 Mark C. Prins <mprins@users.sf.net>
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
 * ZoomControl with built-in tooltips.
 * 
 * @extends OpenLayers.Control.Zoom()
 * @requires OpenLayers/Control/Zoom.js
 * @author Mark C. Prins <mprins@users.sf.net>
 * @class
 */
OpenLayersMap.Control.Zoom = OpenLayers.Class(OpenLayers.Control.Zoom, {
	/**
	 * @override
	 */
	getOrCreateLinks : function(el) {
		var zoomIn = document.getElementById(this.zoomInId), zoomOut = document.getElementById(this.zoomOutId);
		if (!zoomIn) {
			//zoomIn = document.createElement("a");
			zoomIn = document.createElement("button");
			//zoomIn.href = "#zoomIn";
			var tooltip = document.createElement("span");
			tooltip.appendChild(document.createTextNode(OpenLayers.i18n('zoom_in')));
			//OpenLayers.Element.addClass(tooltip, "below");
			zoomIn.appendChild(tooltip);
			zoomIn.appendChild(document.createTextNode(this.zoomInText));
			zoomIn.className = "olControlZoomIn";
			el.appendChild(zoomIn);
		}
		OpenLayers.Element.addClass(zoomIn, "olButton");
		OpenLayers.Element.addClass(zoomIn, "olHasTooltip_bttm_r");
		if (!zoomOut) {
			//zoomOut = document.createElement("a");
			zoomOut = document.createElement("button");
			//zoomOut.href = "#zoomOut";
			var tooltip = document.createElement("span");
			tooltip.appendChild(document.createTextNode(OpenLayers.i18n('zoom_out')));
			//OpenLayers.Element.addClass(tooltip, "below");
			zoomOut.appendChild(tooltip);
			zoomOut.appendChild(document.createTextNode(this.zoomOutText));
			zoomOut.className = "olControlZoomOut";
			el.appendChild(zoomOut);
		}
		OpenLayers.Element.addClass(zoomOut, "olButton");
		OpenLayers.Element.addClass(zoomOut, "olHasTooltip_bttm_r");
		return {
			zoomIn : zoomIn,
			zoomOut : zoomOut
		};
	},
	CLASS_NAME : "OpenLayersMap.Control.Zoom"
});
