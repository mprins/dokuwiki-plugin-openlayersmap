/* 
 * Copyright (c) 2006-2013 by OpenLayers Contributors
 * Copyright (c) 2014 Mark C. Prins <mprins@users.sf.net>
 *  
 * Published under the 2-clause BSD license.
 * See license.txt in the OpenLayers distribution or repository for the
 * full text of the license. 
 */

/**
 * @class Creates a keyboard accessible popup for feature information.
 * 
 * @requires OpenLayers/Popup/FramedCloud.js
 * @requires OpenLayers/Util.js
 * @requires OpenLayers/BaseTypes/Bounds.js
 * @requires OpenLayers/BaseTypes/Pixel.js
 * @requires OpenLayers/BaseTypes/Size.js
 * 
 * 
 * @extends OpenLayers.Popup.FramedCloud
 */
OpenLayersMap.Popup.FramedCloud = OpenLayers.Class(OpenLayers.Popup.FramedCloud, {
	closeBox : true,

	/**
	 * @constructor OpenLayers.Popup.FramedCloud
	 * 
	 * @param id
	 *            {String}
	 * @param lonlat
	 *            {OpenLayers.LonLat}
	 * @param contentSize
	 *            {OpenLayers.Size}
	 * @param contentHTML
	 *            {String}
	 * @param anchor
	 *            {Object} Object to which we'll anchor the popup. Must expose a
	 *            'size' ({OpenLayers.Size}) and 'offset' ({OpenLayers.Pixel})
	 *            (Note that this is generally an {OpenLayers.Icon}).
	 * @param closeBox
	 *            {Boolean} should be true otherwise the box cannot be closed
	 * @param closeBoxCallback
	 *            {Function} Function to be called on closeBox click.
	 */
	initialize : function(id, lonlat, contentSize, contentHTML, anchor, closeBox, closeBoxCallback) {
		OpenLayers.Popup.FramedCloud.prototype.initialize.apply(this, arguments);
		this.contentDiv.className = this.contentDisplayClass;
	},

	/**
	 * defines the close button.
	 * 
	 * @param callback
	 *            {Function} The callback to be called when the close button is
	 *            clicked.
	 * @override
	 */
	addCloseBox : function(callback) {
		this.closeDiv = document.createElement('button');
		this.closeDiv.id = this.id + '_close';
		this.closeDiv.insertAdjacentHTML('afterbegin', '<span role="tooltip">' + OpenLayers.i18n('dlgClose')
				+ '</span>\u00D7');
		this.closeDiv.setAttribute('type' ,'button');
		this.closeDiv.name = 'close';
		this.closeDiv.style.position = 'absolute';
		this.closeDiv.style.width = this.closeDiv.style.height = '22px';
		// use the content div's css padding to determine if we should
		// padd the close div
		var contentDivPadding = this.getContentDivPadding();
		this.closeDiv.style.right = contentDivPadding.right + 'px';
		this.closeDiv.style.top = contentDivPadding.top + 'px';

		this.groupDiv.appendChild(this.closeDiv);
		OpenLayers.Element.addClass(this.closeDiv, 'olPopupCloseBox olHasTooltip_bttm_l');

		var closePopup = callback || function(e) {
			this.hide();
			OpenLayers.Event.stop(e);
		};
		OpenLayers.Event.observe(this.closeDiv, 'touchend', OpenLayers.Function.bindAsEventListener(closePopup, this));
		OpenLayers.Event.observe(this.closeDiv, 'click', OpenLayers.Function.bindAsEventListener(closePopup, this));
	},

	CLASS_NAME : 'OpenLayersMap.Popup.FramedCloud'
});
