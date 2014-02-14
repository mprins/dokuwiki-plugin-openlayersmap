/*
 * Copyright (c) 2014 Mark C. Prins <mprins@users.sf.net>
 * Copyright (c) 2006-2013 by OpenLayers Contributors
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
 * @class OpenLayersMap.Control.LayerSwitcher The LayerSwitcher control displays
 *        a table of contents for the map. This allows the user interface to
 *        switch between BaseLasyers and to show or hide Overlays. By default
 *        the switcher is shown minimized on the right edge of the map, the user
 *        may expand it by clicking on the handle. To create the LayerSwitcher
 *        outside of the map, pass the Id of a html div as the first argument to
 *        the constructor.
 * 
 * @extends {OpenLayers.Control.LayerSwitcher}
 * @requires OpenLayers/Control/LayerSwitcher.js
 */
OpenLayersMap.Control.LayerSwitcher = OpenLayers.Class(OpenLayers.Control.LayerSwitcher,
		{
			/**
			 * @constructor OpenLayersMap.Control.LayerSwitcher
			 * 
			 * @param options
			 *            {Object}
			 */
			initialize : function(options) {
				OpenLayers.Control.LayerSwitcher.prototype.initialize.apply(this, arguments);
				this.displayClass = 'olControlLayerSwitcher';
			},

			/**
			 * Set focus to the first base layer selector.
			 * 
			 * @param e
			 *            {Event}
			 * @extend OpenLayers.Control.LayerSwitcher#maximizeControl
			 */
			maximizeControl : function(e) {
				OpenLayers.Control.LayerSwitcher.prototype.maximizeControl.apply(this, arguments);
				document.getElementById("baseLayersDiv").firstChild.focus();
			},

			/**
			 * make the selection/choice sticky by remembering focus.
			 * 
			 * @extend OpenLayers.Control.LayerSwitcher#onButtonClick
			 * @param evt
			 *            {Event}
			 */
			onButtonClick : function(evt) {
				// get a hold of the value of this evt
				var selValue = evt.buttonElement.value;

				OpenLayers.Control.LayerSwitcher.prototype.onButtonClick.apply(this, arguments);

				if (selValue) {
					// find the clicked item and restore focus
					var inputs = document.getElementById(this.id).getElementsByTagName("input");
					for (var i = 0; i < inputs.length; i++) {
						if (inputs[i].value === selValue) {
							inputs[i].focus();
							break;
						}
					}
				}
			},

			/**
			 * Return focus to the map.
			 * 
			 * @extend OpenLayers.Control.LayerSwitcher#minimizeControl
			 * 
			 * @param e
			 *            {Event}
			 * 
			 */
			minimizeControl : function(e) {
				OpenLayers.Control.LayerSwitcher.prototype.minimizeControl.apply(this, arguments);
				this.map.div.focus();
			},

			/**
			 * Set up the labels and divs for the control.
			 * 
			 * @override
			 */
			loadContents : function() {
				// layers list div
				this.layersDiv = document.createElement("div");
				this.layersDiv.id = this.id + "_layersDiv";
				OpenLayers.Element.addClass(this.layersDiv, "layersDiv");

				this.baseLbl = document.createElement("div");
				this.baseLbl.innerHTML = OpenLayers.i18n("Base Layer");
				OpenLayers.Element.addClass(this.baseLbl, "baseLbl");

				this.baseLayersDiv = document.createElement("div");
				this.baseLayersDiv.id = "baseLayersDiv";
				OpenLayers.Element.addClass(this.baseLayersDiv, "baseLayersDiv");

				this.dataLbl = document.createElement("div");
				this.dataLbl.innerHTML = OpenLayers.i18n("Overlays");
				OpenLayers.Element.addClass(this.dataLbl, "dataLbl");

				this.dataLayersDiv = document.createElement("div");
				OpenLayers.Element.addClass(this.dataLayersDiv, "dataLayersDiv");

				if (this.ascending) {
					this.layersDiv.appendChild(this.baseLbl);
					this.layersDiv.appendChild(this.baseLayersDiv);
					this.layersDiv.appendChild(this.dataLbl);
					this.layersDiv.appendChild(this.dataLayersDiv);
				} else {
					this.layersDiv.appendChild(this.dataLbl);
					this.layersDiv.appendChild(this.dataLayersDiv);
					this.layersDiv.appendChild(this.baseLbl);
					this.layersDiv.appendChild(this.baseLayersDiv);
				}

				// maximize button
				this.maximizeDiv = document.createElement("button");
				this.maximizeDiv.insertAdjacentHTML('afterbegin', '<span role="tooltip">'
						+ OpenLayers.i18n('lyrsMaximize') + '</span>&#x2261;');
				this.maximizeDiv.name = 'show';
				OpenLayers.Element.addClass(this.maximizeDiv,
						"maximizeDiv olButton olHasTooltip_bttm_l olLayerSwitcherButton");
				this.maximizeDiv.style.display = "none";

				// minimize button
				this.minimizeDiv = document.createElement("button");
				// \u2212 is minus, \u00D7 is multiply symbol
				this.minimizeDiv.insertAdjacentHTML('afterbegin', '<span role="tooltip">'
						+ OpenLayers.i18n("lyrsMinimize") + '</span>\u00D7');
				this.minimizeDiv.name = 'hide';
				OpenLayers.Element.addClass(this.minimizeDiv,
						"minimizeDiv olButton olHasTooltip_bttm_l olLayerSwitcherButton");
				this.minimizeDiv.style.display = "none";

				this.div.appendChild(this.maximizeDiv);
				this.div.appendChild(this.minimizeDiv);
				this.div.appendChild(this.layersDiv);
			},

			CLASS_NAME : "OpenLayersMap.Control.LayerSwitcher"
		});
