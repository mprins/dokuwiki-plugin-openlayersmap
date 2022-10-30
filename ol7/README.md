# Updating libraries

## update OpenLayers

This will get the full legacy build of OpenLayers. See https://openlayers.org/download/

```shell
cd ol7
export OL_VERSION=v7.1.0
wget https://github.com/openlayers/openlayers/releases/download/$OL_VERSION/$OL_VERSION-dist.zip
unzip -jo $OL_VERSION-dist.zip  dist/* ol.css ol.css.map
rm $OL_VERSION-dist.zip
```

## update LayerSwitcher

see https://github.com/walkermatt/ol-layerswitcher#js

```shell
cd ol7
export SWITCHER_VERSION=4.1.0
wget "https://unpkg.com/ol-layerswitcher@$SWITCHER_VERSION" -O ol-layerswitcher.js
wget "https://unpkg.com/ol-layerswitcher@$SWITCHER_VERSION/dist/ol-layerswitcher.css" -O ol-layerswitcher.css
```
