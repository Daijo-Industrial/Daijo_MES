<?php

namespace App\Http\Controllers;

class DaijoMesHomeController extends Controller
{
    public function index()
    {
        $categories = [
            [
                'name' => 'Daily Production',
                'desc' => 'Laporan produksi harian secara real-time',
                'route' => route('djoni.dashboard'),
                'icon' => 'fa-solid fa-industry',
                'active' => true,
            ],
            [
                'name' => 'Packaging',
                'desc' => 'Jumlah Packaging dan transaksi keluar masuk',
                'route' => '#',
                'icon' => 'fa-solid fa-box',
                'active' => false,
            ],
            [
                'name' => 'Delivery Schedule',
                'desc' => 'Jadwal pengiriman ke customer',
                'route' => '#',
                'icon' => 'fa-solid fa-truck',
                'active' => false,
            ],
            [
                'name' => 'Production Plan',
                'desc' => 'Rencana produksi ke depan',
                'route' => '#',
                'icon' => 'fa-solid fa-clipboard-list',
                'active' => false,
            ],
            [
                'name' => 'PO Reminder',
                'desc' => 'Kebutuhan material berdasarkan PPS',
                'route' => '#',
                'icon' => 'fa-solid fa-bell',
                'active' => false,
            ],
            [
                'name' => 'Material Handle',
                'desc' => 'Kebutuhan material untuk produksi',
                'route' => '#',
                'icon' => 'fa-solid fa-boxes-stacked',
                'active' => false,
            ],
            [
                'name' => 'Reject',
                'desc' => 'Data produksi yang tidak baik',
                'route' => '#',
                'icon' => 'fa-solid fa-ban',
                'active' => false,
            ],
            [
                'name' => 'Line and Machine',
                'desc' => 'Daftar dan histori line dan mesin',
                'route' => '#',
                'icon' => 'fa-solid fa-cogs',
                'active' => false,
            ],
            [
                'name' => 'Forecast',
                'desc' => 'Perkiraan kebutuhan barang penjualan',
                'route' => '#',
                'icon' => 'fa-solid fa-chart-line',
                'active' => false,
            ],
        ];

        return view('daijo_mes_home_index', compact('categories'));
    }
}
