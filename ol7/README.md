# Updating libraries

## update OpenLayers

### full build
This will get the full/legacy build of OpenLayers. See https://openlayers.org/download/

```shell
cd ol7
export OL_VERSION=v7.2.2
wget https://github.com/openlayers/openlayers/releases/download/$OL_VERSION/$OL_VERSION-dist.zip
unzip -jo $OL_VERSION-dist.zip  dist/* ol.css ol.css.map
rm $OL_VERSION-dist.zip
```

### custom OpenLayers build

A slightly more complicated build process is required to get an optimized-fot-size build of OpenLayers.

```shell
cd ol7
git clone https://github.com/openlayers/openlayers.git
cd openlayers
git checkout -b v7.2.2-custom v7.2.2
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
cd ol7
export SWITCHER_VERSION=4.1.0
wget "https://unpkg.com/ol-layerswitcher@$SWITCHER_VERSION" -O ol-layerswitcher.js
wget "https://unpkg.com/ol-layerswitcher@$SWITCHER_VERSION/dist/ol-layerswitcher.css" -O ol-layerswitcher.css
```
