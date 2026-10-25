package com.example.mobile

import android.content.Context
import android.view.View
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import io.flutter.plugin.common.StandardMessageCodec
import io.flutter.plugin.platform.PlatformView
import io.flutter.plugin.platform.PlatformViewFactory

class SosMapViewFactory : PlatformViewFactory(StandardMessageCodec.INSTANCE) {
    override fun create(context: Context, viewId: Int, args: Any?): PlatformView {
        @Suppress("UNCHECKED_CAST")
        val parameters = args as? Map<String, Any?>
        return SosMapPlatformView(context, parameters)
    }
}

class SosMapPlatformView(
    context: Context,
    parameters: Map<String, Any?>?,
) : PlatformView {
    private val webView = WebView(context).apply {
        settings.javaScriptEnabled = true
        settings.domStorageEnabled = false
        settings.mixedContentMode = WebSettings.MIXED_CONTENT_NEVER_ALLOW
        webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(
                view: WebView,
                request: WebResourceRequest,
            ): Boolean = true
        }

        val latitude = (parameters?.get("latitude") as? Number)?.toDouble()
        val longitude = (parameters?.get("longitude") as? Number)?.toDouble()
        val accuracy = (parameters?.get("accuracy") as? Number)?.toDouble() ?: 0.0
        val hasLocation = latitude != null && longitude != null
        val html = MAP_HTML
            .replace("__LATITUDE__", latitude?.toString() ?: "-2")
            .replace("__LONGITUDE__", longitude?.toString() ?: "118")
            .replace("__ZOOM__", if (hasLocation) "15" else "4")
            .replace("__ACCURACY__", accuracy.toString())
            .replace("__MARKER__", if (hasLocation) "true" else "false")

        loadDataWithBaseURL(
            "https://sigap-bencana.local/",
            html,
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
        const val MAP_HTML = """
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <style>html,body,#map{width:100%;height:100%;margin:0;background:#e8e7e5}.leaflet-control-attribution{font-size:9px!important}</style>
</head>
<body>
  <div id="map" aria-label="Peta titik lokasi SOS"></div>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const latitude=__LATITUDE__, longitude=__LONGITUDE__, accuracy=__ACCURACY__, hasMarker=__MARKER__;
    const map=L.map('map',{zoomControl:false,scrollWheelZoom:false}).setView([latitude,longitude],__ZOOM__);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
    if(hasMarker){L.marker([latitude,longitude]).addTo(map);if(accuracy>0){L.circle([latitude,longitude],{radius:accuracy,color:'#d50032',fillColor:'#ff8794',fillOpacity:.24}).addTo(map);}}
  </script>
</body>
</html>
"""
    }
}
