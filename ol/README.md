# Updating libraries

## update OpenLayers

### full build
This will get the full/legacy build of OpenLayers. See https://openlayers.org/download/

```shell
cd ol
export OL_VERSION=v10.0.0
wget https://github.com/openlayers/openlayers/releases/download/$OL_VERSION/$OL_VERSION-dist.zip
unzip -jo $OL_VERSION-dist.zip  dist/* ol.css ol.css.map
rm $OL_VERSION-dist.zip
```

### custom OpenLayers build

A slightly more complicated build process is required to get an optimized-full-size build of OpenLayers.

```shell
cd ol
rm -rf openlayers
export OL_VERSION=v10.0.0
git clone https://github.com/openlayers/openlayers.git
cd openlayers
git checkout -b $OL_VERSION-custom $OL_VERSION
npm install
# patch package.json to remove puppeteer (not supported on OpenBSD)
npm uninstall puppeteer
# patch generate-info.js to exclude some parts
cp ../generate-info.js.diff .
git apply generate-info.js.diff
rm -rf build/
npm run build-index
npx rollup --config config/rollup-full-build.js
npx cleancss --source-map src/ol/ol.css -o build/full/ol.css
cp build/full/*.* ../
```

## update LayerSwitcher

see https://github.com/walkermatt/ol-layerswitcher#js

```shell
cd ol
export SWITCHER_VERSION=4.1.2
wget "https://unpkg.com/ol-layerswitcher@$SWITCHER_VERSION" -O ol-layerswitcher.js
wget "https://unpkg.com/ol-layerswitcher@$SWITCHER_VERSION/dist/ol-layerswitcher.css" -O ol-layerswitcher.css
```
