<?php

namespace Plugins\Laboratorium\Src;

use Systems\Lib\QueryWrapper;

class Keuangan
{

    protected function db($table = null)
    {
        return new QueryWrapper($table);
    }

    public function getIndex()
    {

      // $totalRecords = $this->db('databarang')
      //   ->select('kode_brng')
      //   ->toArray();
      // $offset         = 30;
      // $return['halaman']    = 1;
      // $return['jml_halaman']    = ceil(count($totalRecords) / $offset);
      // $return['jumlah_data']    = count($totalRecords);

      // $return['list'] = $this->db('databarang')
      //   ->select([
      //     'kode_brng' => 'kode_brng',
      //     'nama_brng' => 'nama_brng',
      //     'kode_satbesar' => 'kodesatuan.satuan',
      //     'kode_sat' => 'kodesatuan.satuan',
      //     'letak_barang' => 'letak_barang',
      //     'dasar' => 'dasar',
      //     'h_beli' => 'h_beli',
      //     'ralan' => 'ralan',
      //     'kelas1' => 'kelas1',
      //     'kelas2' => 'kelas2',
      //     'kelas3' => 'kelas3',
      //     'utama' => 'utama',
      //     'vip' => 'vip',
      //     'vvip' => 'vvip',
      //     'beliluar' => 'beliluar',
      //     'jualbebas' => 'jualbebas',
      //     'karyawan' => 'karyawan',
      //     'stokminimal' => 'stokminimal',
      //     'kdjns' => 'jenis.nama',
      //     'isi' => 'isi',
      //     'kapasitas' => 'kapasitas',
      //     'expire' => 'expire',
      //     'status' => 'status',
      //     'kode_industri' => 'industrifarmasi.nama_industri',
      //     'kode_kategori' => 'kategori_barang.nama',
      //     'kode_golongan' => 'golongan_barang.nama'
      //   ])
      //   ->join('jenis', 'jenis.kdjns=databarang.kdjns')
      //   ->join('kodesatuan', 'kodesatuan.kode_sat=databarang.kode_sat')
      //   ->join('industrifarmasi', 'industrifarmasi.kode_industri=databarang.kode_industri')
      //   ->join('kategori_barang', 'kategori_barang.kode=databarang.kode_kategori')
      //   ->join('golongan_barang', 'golongan_barang.kode=databarang.kode_golongan')
      //   ->desc('kode_brng')
      //   ->toArray();

      // return $return;

    }

    public function search($tgl_awal, $tgl_akhir, $status, $tipe_rawat)
    {
        $str_query = "SELECT (@cnt := @cnt + 1) AS nourut, t.*
                      FROM
                      ( SELECT p.no_rawat, ps.nm_pasien, r.no_rkm_medis, p.tgl_periksa, GROUP_CONCAT(j.nm_perawatan) AS nm_perawatan, SUM(p.biaya) AS biaya, po.nm_poli, p.`status`
                        FROM periksa_lab p 
                        INNER JOIN jns_perawatan_lab j ON j.kd_jenis_prw = p.kd_jenis_prw
                        INNER JOIN reg_periksa r ON p.no_rawat = r.no_rawat
                        INNER JOIN pasien ps ON ps.no_rkm_medis = r.no_rkm_medis
                        INNER JOIN poliklinik po ON po.kd_poli = r.kd_poli
                        WHERE p.kategori = 'PK' 
                        AND p.`status` = '$tipe_rawat'
                        AND p.tgl_periksa BETWEEN '$tgl_awal' AND '$tgl_akhir'
                        GROUP BY p.no_rawat
                        ORDER BY p.tgl_periksa ASC 
                      ) t
                      CROSS JOIN (SELECT @cnt := 0) AS dummy";
        $sql = $this->db()->pdo()->prepare($str_query);
        $sql->execute();
        $data = $sql->fetchAll(\PDO::FETCH_ASSOC);
        $return['list'] = $data;
        $return['count'] = count($data);
        // $return['total'] = $data;
        return $return;
    }

    public function postSave()
    {
      if (!$this->db('databarang')->where('kode_brng', $_POST['kode_brng'])->oneArray()) {
        $query = $this->db('databarang')->save($_POST);
      } else {
        $query = $this->db('databarang')->where('kode_brng', $_POST['kode_brng'])->save($_POST);
      }
      return $query;
    }

    public function postHapus()
    {
      return $this->db('databarang')->where('kode_brng', $_POST['kode_brng'])->update(['status', '0']);
    }

}
