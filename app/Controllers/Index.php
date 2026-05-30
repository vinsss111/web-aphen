<?php

namespace App\Controllers;

use App\Models\SparepartModel;

class Index extends BaseController
{
    protected $sparepartModel;

    public function __construct()
    {
        // Memuat model di CI4 via properti class
        $this->sparepartModel = new SparepartModel();
    }

    public function index()
    {
        $data['spareparts'] = $this->sparepartModel->get_all_spareparts();
        return view('Index', $data); // Di CI4 menggunakan return view()
    }

    public function checkout()
    {
        // CI4 Response Service untuk JSON Header
        $response = service('response');
        $response->setContentType('application/json');

        // Mengambil raw input stream (JSON payload) di CI4
        $rawInput = $this->request->getBody();
        $data     = json_decode($rawInput, true);

        if (!empty($data['keranjang'])) {
            $insert = [
                'nama_customer'      => $data['nama_customer'],
                'metode_pengambilan' => $data['metode_pengambilan'],
                'item_barang'        => json_encode($data['keranjang']),
                'total_bayar'        => $data['total'],
                'status'             => 'pending',
                'tanggal_pesan'      => date('Y-m-d H:i:s')
            ];

            // Menggunakan Query Builder CI4 via instance DB global
            $db = \Config\Database::connect();
            if ($db->table('pesanan')->insert($insert)) {
                return $response->setJSON(['status' => 'success', 'message' => 'Pesanan berhasil dicatat, menunggu persetujuan admin']);
            } else {
                return $response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan pesanan']);
            }
        } else {
            return $response->setJSON(['status' => 'error', 'message' => 'Keranjang kosong']);
        }
    }

    public function chatbot_proxy()
    {
        $response = service('response');
        $response->setContentType('application/json');

        // Mengambil URL Python dari Environment Variable (Aman, tidak bocor ke GitHub)
        $python_server_url = env('PYTHON_CHATBOT_URL') ?? 'http://localhost:5000/chat'; 

        $pesan_user = $this->request->getPost('pesan');

        // Mengirimkan request dari server CI4 ke server Python via cURL
        $ch = curl_init($python_server_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pesan' => $pesan_user]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $result = curl_exec($ch);
        curl_close($ch);

        return $response->setBody($result);
    }
}