package com.example.mobile

import android.Manifest
import android.content.Intent
import android.net.Uri
import android.content.pm.PackageManager
import android.location.Geocoder
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Handler
import android.os.Looper
import android.os.SystemClock
import java.util.Locale
import java.util.concurrent.Executors
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.embedding.android.FlutterActivity
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    private val locationPermissionRequestCode = 4821
    private val mainHandler = Handler(Looper.getMainLooper())
    private val geocoderExecutor = Executors.newSingleThreadExecutor()
    private var pendingLocationResult: MethodChannel.Result? = null
    private var locationManager: LocationManager? = null
    private var locationListener: LocationListener? = null
    private var geocodingLocation: Location? = null
    private val locationTimeout = Runnable {
        val location = geocodingLocation
        if (location != null) {
            finishLocation(location, null)
        } else {
            finishLocationError(
                "locationUnavailable",
                "Lokasi belum didapat. Pastikan GPS aktif lalu coba lagi.",
            )
        }
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        flutterEngine.platformViewsController.registry.registerViewFactory(
            "sigap/risk-map",
            RiskMapViewFactory(),
        )
        flutterEngine.platformViewsController.registry.registerViewFactory(
            "sigap/sos-map",
            SosMapViewFactory(),
        )
        flutterEngine.platformViewsController.registry.registerViewFactory(
            "sigap/evacuation-map",
            EvacuationMapViewFactory(),
        )
        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "sigap/location",
        ).setMethodCallHandler { call, result ->
            if (call.method == "getCurrentLocation") {
                requestCurrentLocation(result)
            } else {
                result.notImplemented()
            }
        }
        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "sigap/emergency-contacts",
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "dial" -> {
                    val phone = call.argument<String>("phone")
                    if (phone.isNullOrBlank() || !phone.matches(Regex("\\+?[0-9]{3,30}"))) {
                        result.error("invalidPhone", "Nomor telepon tidak valid.", null)
                    } else {
                        openExternalIntent(Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone")), result)
                    }
                }
                "openWebsite" -> {
                    val url = call.argument<String>("url")
                    val uri = url?.let { Uri.parse(it) }
                    if (uri == null || uri.scheme !in listOf("http", "https") || uri.host.isNullOrBlank()) {
                        result.error("invalidUrl", "Alamat situs tidak valid.", null)
                    } else {
                        openExternalIntent(Intent(Intent.ACTION_VIEW, uri), result)
                    }
                }
                else -> result.notImplemented()
            }
        }
    }

    private fun openExternalIntent(intent: Intent, result: MethodChannel.Result) {
        try {
            startActivity(intent)
            result.success(null)
        } catch (_: Exception) {
            result.error("activityUnavailable", "Aplikasi terkait tidak dapat dibuka.", null)
        }
    }

    private fun requestCurrentLocation(result: MethodChannel.Result) {
        if (pendingLocationResult != null) {
            result.error("locationBusy", "Permintaan lokasi sedang berjalan.", null)
            return
        }

        pendingLocationResult = result
        if (!hasLocationPermission()) {
            requestPermissions(
                arrayOf(
                    Manifest.permission.ACCESS_FINE_LOCATION,
                    Manifest.permission.ACCESS_COARSE_LOCATION,
                ),
                locationPermissionRequestCode,
            )
            return
        }

        startLocationUpdates()
    }

    private fun hasLocationPermission(): Boolean {
        return checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) ==
            PackageManager.PERMISSION_GRANTED ||
            checkSelfPermission(Manifest.permission.ACCESS_COARSE_LOCATION) ==
            PackageManager.PERMISSION_GRANTED
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray,
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode != locationPermissionRequestCode) return

        if (hasLocationPermission()) {
            startLocationUpdates()
        } else {
            finishLocationError(
                "permissionDenied",
                "Izin lokasi tidak diberikan. Aktifkan izin lokasi untuk memakai fitur ini.",
            )
        }
    }

    private fun startLocationUpdates() {
        val manager = getSystemService(LOCATION_SERVICE) as LocationManager
        val providers = manager.getProviders(true).filter {
            it == LocationManager.GPS_PROVIDER ||
                it == LocationManager.NETWORK_PROVIDER
        }

        if (providers.isEmpty()) {
            finishLocationError(
                "locationDisabled",
                "Layanan lokasi perangkat sedang nonaktif.",
            )
            return
        }

        val listener = object : LocationListener {
            override fun onLocationChanged(location: Location) {
                val ageNanos = SystemClock.elapsedRealtimeNanos() -
                    location.elapsedRealtimeNanos
                if (ageNanos > 120_000_000_000L) return
                beginReverseGeocoding(location)
            }
        }
        locationManager = manager
        locationListener = listener

        var hasProvider = false
        for (provider in providers) {
            try {
                manager.requestLocationUpdates(
                    provider,
                    0L,
                    0f,
                    listener,
                    Looper.getMainLooper(),
                )
                hasProvider = true
            } catch (_: SecurityException) {
                // Approximate-only permission can make the GPS provider unavailable.
            }
        }

        if (!hasProvider) {
            finishLocationError(
                "permissionDenied",
                "Izin lokasi perangkat tidak mencukupi untuk mengambil posisi.",
            )
            return
        }

        mainHandler.postDelayed(locationTimeout, 20000L)
    }

    private fun beginReverseGeocoding(location: Location) {
        if (geocodingLocation != null) return

        stopLocationUpdates(removeTimeout = false)
        geocodingLocation = location
        mainHandler.removeCallbacks(locationTimeout)
        mainHandler.postDelayed(locationTimeout, 8000L)

        geocoderExecutor.execute {
            val address = resolveAddress(location)
            mainHandler.post {
                if (geocodingLocation === location) {
                    finishLocation(location, address)
                }
            }
        }
    }

    private fun resolveAddress(location: Location): String? {
        return try {
            val geocoder = Geocoder(this, Locale.getDefault())
            val result = geocoder.getFromLocation(
                location.latitude,
                location.longitude,
                1,
            )?.firstOrNull() ?: return null
            val addressLine = result.getAddressLine(0)?.trim()
            if (!addressLine.isNullOrEmpty()) {
                addressLine
            } else {
                listOfNotNull(
                    result.subLocality,
                    result.locality,
                    result.subAdminArea,
                    result.adminArea,
                ).map(String::trim)
                    .filter(String::isNotEmpty)
                    .distinct()
                    .joinToString(", ")
                    .ifEmpty { null }
            }
        } catch (_: Exception) {
            null
        }
    }

    private fun finishLocation(location: Location, address: String?) {
        val result = pendingLocationResult ?: return
        stopLocationUpdates()
        geocodingLocation = null
        pendingLocationResult = null
        result.success(
            mapOf(
                "latitude" to location.latitude,
                "longitude" to location.longitude,
                "accuracy" to location.accuracy.toDouble(),
                "captured_at" to location.time,
                "address" to address,
            ),
        )
    }

    private fun finishLocationError(code: String, message: String) {
        val result = pendingLocationResult ?: return
        stopLocationUpdates()
        geocodingLocation = null
        pendingLocationResult = null
        result.error(code, message, null)
    }

    private fun stopLocationUpdates(removeTimeout: Boolean = true) {
        if (removeTimeout) {
            mainHandler.removeCallbacks(locationTimeout)
        }
        val manager = locationManager
        val listener = locationListener
        if (manager != null && listener != null) {
            manager.removeUpdates(listener)
        }
        locationManager = null
        locationListener = null
    }

    override fun onDestroy() {
        stopLocationUpdates()
        geocoderExecutor.shutdownNow()
        pendingLocationResult = null
        super.onDestroy()
    }
}
