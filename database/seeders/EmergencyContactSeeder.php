<?php

namespace Database\Seeders;

use App\Models\EmergencyContact;
use Illuminate\Database\Seeder;

class EmergencyContactSeeder extends Seeder
{
    public function run(): void
    {
        $contacts = [
            [
                'name' => 'Padang Sigap',
                'category' => 'Pusat Panggilan Darurat',
                'phone' => '112',
                'description' => 'Pusat panggilan darurat Kota Padang.',
                'region' => 'Kota Padang',
                'website_url' => 'https://sigap.padang.go.id/',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'BPBD Kota Padang',
                'category' => 'Kebencanaan',
                'phone' => '(0751) 778775',
                'description' => 'Kontak pelaporan kebencanaan untuk wilayah Kota Padang.',
                'region' => 'Kota Padang',
                'website_url' => 'https://bpbd.padang.go.id/',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Pemadam Kebakaran Kota Padang',
                'category' => 'Pemadam Kebakaran',
                'phone' => '113',
                'description' => 'Layanan pemadam kebakaran di Kota Padang.',
                'region' => 'Kota Padang',
                'website_url' => 'https://padang.go.id/index.php/berita/kebakaran-mengancam-damkar-padang-imbau-warga-waspada-dan-laporkan-segera',
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'Polri',
                'category' => 'Kepolisian',
                'phone' => '110',
                'description' => 'Layanan pengaduan dan bantuan kepolisian. Ketersediaan dapat bergantung pada wilayah dan jaringan.',
                'region' => 'Nasional; konfirmasi ketersediaan setempat',
                'website_url' => 'https://polri.go.id/kontak/call-center-110',
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'name' => 'Basarnas',
                'category' => 'Pencarian dan Pertolongan',
                'phone' => '115',
                'description' => 'Kontak pencarian dan pertolongan (SAR). Cakupan dan ketersediaan layanan perlu dikonfirmasi sesuai wilayah.',
                'region' => 'Nasional; konfirmasi cakupan setempat',
                'website_url' => 'https://basarnas.go.id/hubungi-kami',
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'name' => 'PSC',
                'category' => 'Kesehatan',
                'phone' => '119',
                'description' => 'Layanan darurat kesehatan melalui PSC 119. Ketersediaan layanan dapat berbeda menurut wilayah.',
                'region' => 'Nasional; konfirmasi ketersediaan setempat',
                'website_url' => 'https://kemkes.go.id/id/layanan/psc-119',
                'is_active' => true,
                'sort_order' => 60,
            ],
        ];

        foreach ($contacts as $contact) {
            EmergencyContact::query()->updateOrCreate(
                [
                    'name' => $contact['name'],
                    'phone' => $contact['phone'],
                ],
                $contact,
            );
        }
    }
}
