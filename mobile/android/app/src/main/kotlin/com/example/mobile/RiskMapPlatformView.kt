package com.example.mobile

import android.content.Context
import android.view.View
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import io.flutter.plugin.common.StandardMessageCodec
import io.flutter.plugin.platform.PlatformView
import io.flutter.plugin.platform.PlatformViewFactory

class RiskMapViewFactory : PlatformViewFactory(StandardMessageCodec.INSTANCE) {
    override fun create(context: Context, viewId: Int, args: Any?): PlatformView {
        return RiskMapPlatformView(context)
    }
}

class RiskMapPlatformView(context: Context) : PlatformView {
    private val webView = WebView(context).apply {
        settings.javaScriptEnabled = true
        settings.domStorageEnabled = false
        settings.mixedContentMode = WebSettings.MIXED_CONTENT_NEVER_ALLOW
        webChromeClient = WebChromeClient()
        webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(
                view: WebView,
                request: WebResourceRequest,
            ): Boolean = true
        }
        loadDataWithBaseURL(
            "https://sigap-bencana.local/",
            RISK_MAP_HTML,
            "text/html",
            "UTF-8",
            null,
        )
    }

    override fun getView(): View = webView

    override fun dispose() {
        webView.stopLoading()
        webView.destroy()
    }

    private companion object {
        const val RISK_MAP_HTML = """
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <style>
    html, body, #map { width: 100%; height: 100%; margin: 0; background: #e8e7e5; font-family: Arial, sans-serif; }
    .leaflet-control-attribution { font-size: 9px !important; }
    .panel { position: absolute; z-index: 1100; left: 12px; right: 12px; padding: 12px 14px; border-radius: 14px; background: rgba(255,255,255,.98); box-shadow: 0 2px 10px #0003; color: #222; font: 12px/1.4 Arial, sans-serif; }
    #filter-toggle { position: absolute; z-index: 1200; top: 132px; right: 20px; display: grid; width: 50px; height: 50px; place-items: center; border: 1px solid #ddd; border-radius: 50%; background: rgba(255,255,255,.98); box-shadow: 0 2px 8px #0003; color: #222; }
    #filter-toggle svg { width: 25px; height: 25px; }
    #filter-panel { display: none; top: 188px; left: auto; right: 16px; width: min(280px, calc(100% - 32px)); box-sizing: border-box; padding: 12px; }
    #risk-type-filter { display: block; box-sizing: border-box; width: 100%; margin-top: 4px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 8px; background: white; font-size: 13px; }
    #risk-title { margin-top: 6px; font-weight: bold; }
    #risk-date { margin-top: 2px; color: #555; font-size: 10px; }
    #source-status { margin-top: 4px; color: #555; font-size: 10px; }
    #source-status.error { color: #b00020; }
    #legend-panel { bottom: 10px; left: 16px; right: 16px; max-height: 27%; overflow-y: auto; padding: 15px 16px; border: 1px solid #f7e4e6; border-radius: 14px; background: rgba(255,248,248,.98); }
    #legend-title { font-size: 16px; font-weight: bold; }
    #legend-content { display: flex; flex-wrap: wrap; gap: 8px 16px; margin-top: 10px; font-size: 12px; }
    .legend-item { display: flex; align-items: center; gap: 7px; min-width: 0; }
    .legend-item img { max-width: 26px; max-height: 26px; flex: 0 0 auto; object-fit: contain; }
    #legend-note { margin-top: 8px; color: #666; font-size: 9px; line-height: 1.35; }
    #map { position: absolute; inset: 0; }
  </style>
</head>
<body>
  <button id="filter-toggle" type="button" aria-label="Filter jenis bencana" aria-expanded="false" aria-controls="filter-panel">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 5h18v2H3zm3 6h12v2H6zm3 6h6v2H9z"/></svg>
  </button>
  <div id="filter-panel" class="panel">
    <label for="risk-type-filter"><strong>Pilih jenis bencana</strong></label>
    <select id="risk-type-filter"></select>
    <div id="risk-title">Peta Risiko Banjir — InaRISK</div>
    <div id="risk-date">Tanggal data tidak dicantumkan pada metadata REST yang diverifikasi.</div>
    <div id="source-status" role="status" aria-live="polite">Memuat metadata BNPB…</div>
  </div>
  <div id="map" aria-label="Peta risiko SIGAP Bencana"></div>
  <div id="legend-panel" class="panel">
    <div id="legend-title">Legenda Peta Risiko InaRISK - Banjir</div>
    <div id="legend-content" aria-live="polite">Meminta legenda resmi dari layanan…</div>
    <div id="legend-note">Sumber: InaRISK/BNPB. Keterangan mengikuti legenda resmi layer yang dipilih.</div>
  </div>  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script src="https://unpkg.com/esri-leaflet@3.0.19/dist/esri-leaflet.js"></script>
  <script>
    const map = L.map('map', { scrollWheelZoom: false, zoomControl: false }).setView([-2, 118], 4);
    const osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    const status = document.getElementById('source-status');
    const filterToggle = document.getElementById('filter-toggle');
    const filterPanel = document.getElementById('filter-panel');
    const legendContent = document.getElementById('legend-content');
    const legendTitle = document.getElementById('legend-title');
    const title = document.getElementById('risk-title');
    const filter = document.getElementById('risk-type-filter');
    const catalog = 'https://gis.bnpb.go.id/server/rest/services/inarisk';
    const riskLayers = [
      { key: 'banjir', label: 'Banjir', service: 'layer_risiko_banjir' },
      { key: 'banjir_bandang', label: 'Banjir Bandang', service: 'layer_risiko_banjir_bandang' },
      { key: 'gempa_bumi', label: 'Gempa Bumi', service: 'layer_risiko_gempabumi' },
      { key: 'tsunami', label: 'Tsunami', service: 'layer_risiko_tsunami' },
      { key: 'tanah_longsor', label: 'Tanah Longsor', service: 'layer_risiko_tanah_longsor' },
      { key: 'letusan_gunung_api', label: 'Letusan Gunung Api', service: 'layer_risiko_letusan_gunungapi' },
      { key: 'kekeringan', label: 'Kekeringan', service: 'layer_risiko_kekeringan' },
      { key: 'cuaca_ekstrem', label: 'Cuaca Ekstrem', service: 'layer_risiko_cuaca_ekstrim' },
      { key: 'kebakaran_hutan_lahan', label: 'Kebakaran Hutan dan Lahan', service: 'layer_risiko_kebakaran_hutan_dan_lahan' },
      { key: 'likuefaksi', label: 'Likuefaksi', service: 'layer_risiko_likuefaksi' },
      { key: 'gelombang_abrasi', label: 'Gelombang Ekstrem dan Abrasi', service: 'layer_risiko_gelombang_ekstrim_dan_abrasi' },
      { key: 'multi_bahaya', label: 'Multi-Bahaya', service: 'layer_risiko_multi' }
    ];
    let activeLayer = null;
    let activeMetadata = null;
    let requestVersion = 0;

    riskLayers.forEach(function (layer) {
      const option = document.createElement('option');
      option.value = layer.key;
      option.textContent = layer.label;
      filter.appendChild(option);
    });

    function serviceJson(url) {
      return fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      }).then(function (data) {
        if (data.error) throw new Error(data.error.message || 'ArcGIS REST error');
        return data;
      });
    }

    function outsideExtent(metadata) {
      const extent = metadata.fullExtent;
      if (!extent || !extent.spatialReference || extent.spatialReference.wkid !== 3395) return null;
      const bounds = map.getBounds();
      const southWest = L.CRS.EPSG3395.project(bounds.getSouthWest());
      const northEast = L.CRS.EPSG3395.project(bounds.getNorthEast());
      return northEast.x < extent.xmin || southWest.x > extent.xmax || northEast.y < extent.ymin || southWest.y > extent.ymax;
    }

    function updateCoverageStatus() {
      if (!activeMetadata) return;
      const selected = riskLayers.find(function (item) { return item.key === filter.value; });
      if (outsideExtent(activeMetadata) === true) {
        status.textContent = 'Layer ' + selected.label + ' tidak mencakup area peta saat ini. Geser peta ke area layanan.';
        status.className = 'error';
      } else if (outsideExtent(activeMetadata) === false) {
        status.textContent = 'Area peta berada dalam cakupan layer ' + selected.label + '. Area tanpa data raster dapat tampil kosong.';
        status.className = '';
      }
    }

    function loadLegend(layer, version) {
      legendTitle.textContent = 'Legenda Peta Risiko InaRISK - ' + layer.label;
      legendContent.textContent = 'Meminta legenda resmi dari layanan…';
      serviceJson(layer.url + '/legend?f=json').then(function (data) {
        if (version !== requestVersion) return;
        const entries = (data.layers || []).reduce(function (all, item) {
          return all.concat(item.legend || []);
        }, []);
        if (!entries.length) {
          legendContent.textContent = 'Layanan BNPB tidak mengembalikan legenda kelas untuk layer ini.';
          return;
        }
        legendContent.replaceChildren();
        entries.forEach(function (entry) {
          const item = document.createElement('div');
          item.className = 'legend-item';
          if (entry.imageData && entry.contentType) {
            const image = document.createElement('img');
            image.src = 'data:' + entry.contentType + ';base64,' + entry.imageData;
            image.alt = '';
            item.appendChild(image);
          }
          const label = document.createElement('span');
          label.textContent = entry.label || 'Kelas tanpa label pada layanan';
          item.appendChild(label);
          legendContent.appendChild(item);
        });
      }).catch(function () {
        if (version === requestVersion) legendContent.textContent = 'Legenda resmi tidak dapat dimuat saat ini.';
      });
    }

    function selectRiskLayer(layerKey) {
      const layer = riskLayers.find(function (item) { return item.key === layerKey; });
      const version = ++requestVersion;
      if (!layer) return;
      if (activeLayer) {
        map.removeLayer(activeLayer);
        activeLayer = null;
      }
      activeMetadata = null;
      layer.url = catalog + '/' + layer.service + '/ImageServer';
      title.textContent = 'Peta Risiko ' + layer.label + ' — InaRISK';
      status.textContent = 'Memeriksa metadata dan cakupan ' + layer.label + '…';
      status.className = '';

      serviceJson(layer.url + '?f=json').then(function (metadata) {
        if (version !== requestVersion) return;
        activeMetadata = metadata;
        if (!L.esri || typeof L.esri.imageMapLayer !== 'function') throw new Error('Esri Leaflet ImageServer adapter tidak tersedia');
        activeLayer = L.esri.imageMapLayer({ url: layer.url, opacity: 0.7, useCors: true });
        activeLayer.on('loading', function () {
          status.textContent = 'Memuat peta risiko ' + layer.label + '…';
          status.className = '';
        });
        activeLayer.on('load', function () {
          if (version !== requestVersion) return;
          const outside = outsideExtent(metadata);
          status.textContent = outside === null
            ? 'Layer dimuat; cakupan area tidak dapat dipastikan dari metadata layanan.'
            : outside
              ? 'Layer ' + layer.label + ' tidak mencakup area peta saat ini. Geser peta ke area layanan.'
              : 'Area peta berada dalam cakupan layer. Area tanpa data raster dapat tampil kosong.';
          status.className = '';
        });
        activeLayer.on('error', function () {
          if (version !== requestVersion) return;
          status.textContent = 'Layer ' + layer.label + ' gagal dimuat dari BNPB. OpenStreetMap tetap tersedia.';
          status.className = 'error';
        });
        activeLayer.addTo(map);
        loadLegend(layer, version);
      }).catch(function () {
        if (version !== requestVersion) return;
        status.textContent = 'Metadata atau layanan ' + layer.label + ' tidak dapat diakses. Periksa jaringan, lalu pilih ulang jenis peta.';
        status.className = 'error';
        legendTitle.textContent = 'Legenda Peta Risiko InaRISK - ' + layer.label;
        legendContent.textContent = 'Legenda BNPB tidak dapat dimuat saat ini.';
      });
    }

    filterToggle.addEventListener('click', function () {
      const isExpanded = filterToggle.getAttribute('aria-expanded') === 'true';
      filterToggle.setAttribute('aria-expanded', String(!isExpanded));
      filterPanel.style.display = isExpanded ? 'none' : 'block';
    });
    filter.addEventListener('change', function () {
      selectRiskLayer(filter.value);
      filterPanel.style.display = 'none';
      filterToggle.setAttribute('aria-expanded', 'false');
    });
    map.on('moveend', updateCoverageStatus);
    selectRiskLayer('banjir');
  </script>
</body>
</html>
"""
    }
}
