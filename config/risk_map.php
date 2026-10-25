<?php

$catalogUrl = 'https://gis.bnpb.go.id/server/rest/services/inarisk';
$serviceBaseUrl = $catalogUrl;

$definitions = [
    'banjir' => ['label' => 'Banjir', 'service' => 'layer_risiko_banjir'],
    'banjir_bandang' => ['label' => 'Banjir Bandang', 'service' => 'layer_risiko_banjir_bandang'],
    'gempa_bumi' => ['label' => 'Gempa Bumi', 'service' => 'layer_risiko_gempabumi'],
    'tsunami' => ['label' => 'Tsunami', 'service' => 'layer_risiko_tsunami'],
    'tanah_longsor' => ['label' => 'Tanah Longsor', 'service' => 'layer_risiko_tanah_longsor'],
    'letusan_gunung_api' => ['label' => 'Letusan Gunung Api', 'service' => 'layer_risiko_letusan_gunungapi'],
    'kekeringan' => ['label' => 'Kekeringan', 'service' => 'layer_risiko_kekeringan'],
    'cuaca_ekstrem' => ['label' => 'Cuaca Ekstrem', 'service' => 'layer_risiko_cuaca_ekstrim'],
    'kebakaran_hutan_lahan' => ['label' => 'Kebakaran Hutan dan Lahan', 'service' => 'layer_risiko_kebakaran_hutan_dan_lahan'],
    'likuefaksi' => ['label' => 'Likuefaksi', 'service' => 'layer_risiko_likuefaksi'],
    'gelombang_abrasi' => ['label' => 'Gelombang Ekstrem dan Abrasi', 'service' => 'layer_risiko_gelombang_ekstrim_dan_abrasi'],
    'multi_bahaya' => ['label' => 'Multi-Bahaya', 'service' => 'layer_risiko_multi'],
];

$layers = [];

foreach ($definitions as $key => $definition) {
    $serviceUrl = $serviceBaseUrl.'/'.$definition['service'].'/ImageServer';

    $layers[$key] = [
        'key' => $key,
        'label' => $definition['label'],
        'classification' => 'risk',
        'classification_label' => 'Peta risiko',
        'service_type' => 'ImageServer',
        'service_name' => $definition['service'],
        'service_url' => $serviceUrl,
        'metadata_url' => $serviceUrl.'?f=json',
        'legend_url' => $serviceUrl.'/legend?f=json',
        'legend' => [
            'source' => 'ArcGIS ImageServer legend operation',
            'fallback' => 'Layanan BNPB tidak mengembalikan legenda kelas untuk layer ini.',
        ],
        'legend_fallback' => 'Layanan BNPB tidak mengembalikan legenda kelas untuk layer ini.',
        'source' => [
            'name' => 'InaRISK / BNPB',
            'catalog_url' => $catalogUrl,
            'service_url' => $serviceUrl,
        ],
        'data_date' => null,
    ];
}

return [
    'default_layer' => 'banjir',
    'catalog_url' => $catalogUrl,
    'layers' => $layers,
];
