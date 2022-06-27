# Updating libraries

## update OpenLayers

see https://openlayers.org/download/

```shell
cd ol6
export OL_VERSION=v6.14.1
wget https://github.com/openlayers/openlayers/releases/download/$OL_VERSION/$OL_VERSION-dist.zip
unzip -jo $OL_VERSION-dist.zip
rm $OL_VERSION-dist.zip
```

## update LayerSwitcher

see https://github.com/walkermatt/ol-layerswitcher#js

```shell
cd ol6
export SWITCHER_VERSION=3.8.3
wget "https://unpkg.com/ol-layerswitcher@$SWITCHER_VERSION" -O ol-layerswitcher.js
wget "https://unpkg.com/ol-layerswitcher@$SWITCHER_VERSION/dist/ol-layerswitcher.css" -O ol-layerswitcher.css
```