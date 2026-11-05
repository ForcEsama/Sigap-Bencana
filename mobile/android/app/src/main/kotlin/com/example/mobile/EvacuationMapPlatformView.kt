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
import org.json.JSONArray
import org.json.JSONObject

class EvacuationMapViewFactory : PlatformViewFactory(StandardMessageCodec.INSTANCE) {
    override fun create(context: Context, viewId: Int, args: Any?): PlatformView {
        @Suppress("UNCHECKED_CAST")
        val parameters = args as? Map<String, Any?>
        return EvacuationMapPlatformView(context, parameters)
    }
}

class EvacuationMapPlatformView(
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

        val places = JSONArray()
        (parameters?.get("places") as? List<*>)?.forEach { item ->
            val place = item as? Map<*, *> ?: return@forEach
            val latitude = (place["latitude"] as? Number)?.toDouble() ?: return@forEach
            val longitude = (place["longitude"] as? Number)?.toDouble() ?: return@forEach
            if (latitude !in -90.0..90.0 || longitude !in -180.0..180.0) return@forEach

            places.put(
                JSONObject()
                    .put("latitude", latitude)
                    .put("longitude", longitude)
                    .put("name", place["name"]?.toString().orEmpty())
                    .put("candidate", place["is_planning_candidate"] == true)
                    .put("code", place["location_code"]?.toString().orEmpty())
                    .put("image_data", place["image_data"]?.toString().orEmpty())
                    .put("recommended_capacity", place["recommended_capacity"]?.toString().orEmpty())
                    .put("address", place["address"]?.toString().orEmpty()),
            )
        }
        val serializedPlaces = places.toString().replace("</", "<\\/")
        val latitude = (parameters?.get("latitude") as? Number)?.toDouble()
        val longitude = (parameters?.get("longitude") as? Number)?.toDouble()
        val hasLocation = latitude != null && longitude != null
        val html = MAP_HTML
            .replace("__PLACES__", serializedPlaces)
            .replace("__LATITUDE__", latitude?.toString() ?: "-2")
            .replace("__LONGITUDE__", longitude?.toString() ?: "118")
            .replace("__HAS_LOCATION__", if (hasLocation) "true" else "false")
            .replace("__ZOOM__", if (hasLocation) "14" else "4")

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
  <style>
    html,body,#map{width:100%;height:100%;margin:0;background:#e8e7e5}
    .leaflet-control-attribution{font-size:9px!important}
    .current-marker{width:20px;height:20px;border:3px solid white;border-radius:50%;background:#1686e8;box-shadow:0 0 0 7px #1686e844,0 1px 5px #0008}
  </style>
</head>
<body>
  <div id="map" aria-label="Peta tempat evakuasi SIGAP Bencana"></div>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const places=__PLACES__, latitude=__LATITUDE__, longitude=__LONGITUDE__, hasLocation=__HAS_LOCATION__;
    const map=L.map('map',{zoomControl:false,scrollWheelZoom:false}).setView([latitude,longitude],__ZOOM__);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
    const bounds=[];
    places.forEach(function(place){
      const point=[place.latitude,place.longitude];
      const marker=L.marker(point).addTo(map);
      const popup=document.createElement('div');
      popup.style.width='230px';
      popup.style.maxWidth='68vw';
      if(place.image_data){
        const image=document.createElement('img');
        image.src='data:image/jpeg;base64,'+place.image_data;
        image.alt='Foto kawasan untuk referensi visual';
        image.width=56;image.height=56;
        image.style.width='56px';image.style.height='56px';image.style.objectFit='cover';image.style.borderRadius='6px';image.style.float='right';image.style.margin='0 0 8px 10px';
        image.onerror=function(){image.style.display='none';};
        popup.appendChild(image);
      }
      const code=document.createElement('p');
      code.textContent=place.candidate?place.code+' / TEA':place.name;
      code.style.fontWeight='600';
      popup.appendChild(code);
      const name=document.createElement('p');
      name.textContent=place.name;
      popup.appendChild(name);
      if(place.candidate&&place.recommended_capacity){
        const capacity=document.createElement('p');
        capacity.textContent='Daya tampung rekomendasi dokumen: '+Number(place.recommended_capacity).toLocaleString('id-ID')+' jiwa';
        popup.appendChild(capacity);
      }else if(place.address){
        const address=document.createElement('p');
        address.textContent=place.address;
        popup.appendChild(address);
      }
      const caution=document.createElement('p');
      caution.textContent=place.candidate
        ? 'Hanya untuk pemeriksaan visual; bukan panduan evakuasi.'
        : 'Informasi mengikuti data yang tercatat di server.';
      caution.style.color=place.candidate?'#b91c1c':'#555';
      popup.appendChild(caution);
      popup.style.overflow='auto';
      marker.bindPopup(popup);
      bounds.push(point);
    });
    if(hasLocation){
      L.marker([latitude,longitude],{icon:L.divIcon({className:'',html:'<div class="current-marker"></div>',iconSize:[20,20],iconAnchor:[10,10]})}).addTo(map).bindPopup('Lokasi saya');
      bounds.push([latitude,longitude]);
    }
    if(bounds.length>1){map.fitBounds(bounds,{padding:[40,40],maxZoom:14});}
    else if(bounds.length===1){map.setView(bounds[0],13);}
  </script>
</body>
</html>
"""
    }
}
