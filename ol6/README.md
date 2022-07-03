# Updating libraries

## update OpenLayers

This will get the full legacy build of OpenLayers. See https://openlayers.org/download/

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

## custom openlayers build

cd ol6
git clone https://github.com/openlayers/openlayers.git
cd openlayers
npm install
npm run build-index

# edit ./build/index.js

npx webpack --config config/webpack-config-legacy-build.mjs && npx cleancss --source-map src/ol/ol.css -o build/legacy/ol.css
