<?php

namespace Plugins\Rawat_Jalan;

use Systems\AdminModule;
use Plugins\Icd\DB_ICD;
use Systems\Lib\Fpdf\PDF_MC_Table;

require_once __DIR__ . '/../../vendor/autoload.php';
class Admin extends AdminModule
{
  private $_uploads = WEBAPPS_PATH . '/berkasrawat/pages/upload';

  public function navigation()
  {
    return [
      'Kelola'              => 'index',
      'Rawat Jalan'         => 'manage',
      'Booking Registrasi'  => 'booking',
      'Booking Periksa'     => 'bookingperiksa',
      'Jadwal Dokter'       => 'jadwal'
    ];
  }

  public function getIndex()
  {
    $sub_modules = [
      ['name' => 'Rawat Jalan', 'url' => url([ADMIN, 'rawat_jalan', 'manage']), 'icon' => 'wheelchair', 'desc' => 'Pendaftaran pasien rawat jalan'],
      ['name' => 'Booking Registrasi', 'url' => url([ADMIN, 'rawat_jalan', 'booking']), 'icon' => 'file-o', 'desc' => 'Pendaftaran pasien booking rawat jalan'],
      ['name' => 'Booking Periksa', 'url' => url([ADMIN, 'rawat_jalan', 'bookingperiksa']), 'icon' => 'file-o', 'desc' => 'Booking periksa pasien rawat jalan via Online'],
      ['name' => 'Jadwal Dokter', 'url' => url([ADMIN, 'rawat_jalan', 'jadwal']), 'icon' => 'user-md', 'desc' => 'Jadwal dokter rawat jalan'],
    ];
    return $this->draw('index.html', ['sub_modules' => $sub_modules]);
  }

  public function anyManage()
  {
    $tgl_kunjungan = date('Y-m-d');
    $tgl_kunjungan_akhir = date('Y-m-d');
    $status_periksa = '';
    $status_bayar = '';

    if (isset($_POST['periode_rawat_jalan'])) {
      $tgl_kunjungan = $_POST['periode_rawat_jalan'];
    }
    if (isset($_POST['periode_rawat_jalan_akhir'])) {
      $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
    }
    if (isset($_POST['status_periksa'])) {
      $status_periksa = $_POST['status_periksa'];
    }
    if (isset($_POST['status_bayar'])) {
      $status_bayar = $_POST['status_bayar'];
    }
    $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
    $master_berkas_digital = $this->db('master_berkas_digital')->toArray();
    $responsivevoice =  $this->settings->get('settings.responsivevoice');
    $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa, $status_bayar);
    return $this->draw(
      'manage.html', 
        [
          'rawat_jalan' => $this->assign, 
          'cek_vclaim' => $cek_vclaim, 
          'master_berkas_digital' => $master_berkas_digital, 
          'responsivevoice' => $responsivevoice, 
          'admin_mode' => $this->settings->get('settings.admin_mode')
        ]
      );
  }

  public function anyDisplay()
  {
    $tgl_kunjungan = date('Y-m-d');
    $tgl_kunjungan_akhir = date('Y-m-d');
    $status_periksa = '';
    $status_bayar = '';

    if (isset($_POST['periode_rawat_jalan'])) {
      $tgl_kunjungan = $_POST['periode_rawat_jalan'];
    }
    if (isset($_POST['periode_rawat_jalan_akhir'])) {
      $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
    }
    if (isset($_POST['status_periksa'])) {
      $status_periksa = $_POST['status_periksa'];
    }
    if (isset($_POST['status_bayar'])) {
      $status_bayar = $_POST['status_bayar'];
    }
    $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
    $responsivevoice =  $this->settings->get('settings.responsivevoice');
    $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa, $status_bayar);
    echo $this->draw('display.html', ['rawat_jalan' => $this->assign, 'cek_vclaim' => $cek_vclaim, 'responsivevoice' => $responsivevoice, 'admin_mode' => $this->settings->get('settings.admin_mode')]);
    exit();
  }

  public function _Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa = '', $status_bayar = '')
  {

    if ($this->settings->get('settings.responsivevoice') == 'true') {
      $this->core->addJS(url('assets/jscripts/responsivevoice.js'));
    }
    $this->_addHeaderFiles();
    $url = 'vclaim-rest/SEP/FingerPrint/List/Peserta/TglPelayanan/' .  date('Y-m-d');
    $datajson = $this->getDataWSBPJS($url);
    // var_dump($datajson);
    $dataFinger = [];
    if ($datajson['metaData']['code'] == '200') {
      $dataFinger = json_decode($datajson['response'], true);
    }

    $stmt_timestamp = $this->db()->pdo()->prepare("SELECT temp2, temp3, temp4 FROM temporary2 WHERE temp2 IN (SELECT no_rawat FROM reg_periksa WHERE tgl_registrasi  BETWEEN '$tgl_kunjungan' AND '$tgl_kunjungan_akhir' )");
    $stmt_timestamp->execute();
    $rowTimestamp = $stmt_timestamp->fetchAll();
    $arr_timestamp = [];    
    
    foreach($rowTimestamp as $row_ts){
      $arr_timestamp[$row_ts['temp2']] = [
                                          "mulai" => $row_ts['temp3'],
                                          "selesai" => $row_ts['temp4']
                                        ];
    }
    
    $stmt_sep = $this->db()->pdo()->prepare("SELECT no_sep, no_rawat, catatan,katarak,peserta,klsrawat FROM bridging_sep WHERE tglsep  BETWEEN '$tgl_kunjungan' AND '$tgl_kunjungan_akhir' ");
    $stmt_sep->execute();
    $rowSEP = $stmt_sep->fetchAll();
    $arr_sep = [];    
    
    foreach($rowSEP as $row_sep){
      $arr_sep[$row_sep['no_rawat']] = [
                                          "no_sep" => $row_sep['no_sep'],
                                          "catatan" => $row_sep['catatan'],
                                          "katarak" => $row_sep['katarak'],
                                          "peserta" => $row_sep['peserta'],
                                          "klsrawat" => $row_sep['klsrawat']
                                        ];
    }

    $this->assign['poliklinik']     = $this->db('poliklinik')->where('status', '1')->where('kd_poli', '<>', $this->settings->get('settings.igd'))->toArray();
    $this->assign['dokter']         = $this->db('dokter')->where('status', '1')->toArray();
    $this->assign['penjab']       = $this->db('penjab')->toArray();
    $this->assign['no_rawat'] = '';
    $this->assign['no_reg']     = '';
    $this->assign['tgl_registrasi'] = date('Y-m-d');
    $this->assign['jam_reg'] = date('H:i:s');

    $poliklinik = str_replace(",", "','", $this->core->getUserInfo('cap', null, true));
    $igd = $this->settings('settings', 'igd');
    $sql = "SELECT r.no_rkm_medis, p.no_peserta, p.no_ktp, r.no_rawat, p.nm_pasien, r.tgl_registrasi, r.no_reg, po.nm_poli, d.nm_dokter, r.stts, r.jam_reg
            FROM reg_periksa r
            INNER JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis
            INNER JOIN poliklinik po ON po.kd_poli = r.kd_poli
            INNER JOIN dokter d ON d.kd_dokter = r.kd_dokter
            WHERE r.tgl_registrasi BETWEEN '$tgl_kunjungan' AND '$tgl_kunjungan_akhir' ";
    // $sql = "SELECT a.no_rkm_medis, a.no_peserta, a.no_ktp, a.no_rawat,
    //         a.nm_pasien, a.tgl_registrasi, a.no_reg, a.nm_poli, a.nm_dokter,
    //         a.stts, a.jam_reg, IFNULL(t.temp3, '-') AS mulai, IFNULL(t.temp4, '-') AS selesai
    //         FROM (
    //             SELECT reg_periksa.*,
    //                 pasien.no_ktp,
    //                 pasien.nm_pasien,
    //                 pasien.no_peserta,
    //                 dokter.nm_dokter,
    //                 poliklinik.nm_poli,
    //                 penjab.png_jawab
    //             FROM reg_periksa, pasien, dokter, poliklinik, penjab
    //             WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    //             AND reg_periksa.tgl_registrasi BETWEEN '$tgl_kunjungan' AND '$tgl_kunjungan_akhir'
    //             AND reg_periksa.kd_dokter = dokter.kd_dokter
    //             AND reg_periksa.kd_poli = poliklinik.kd_poli
    //             AND reg_periksa.kd_pj = penjab.kd_pj ";
    // $sql = "SELECT reg_periksa.*,
    //         pasien.*,
    //         dokter.*,
    //         poliklinik.*,
    //         penjab.*
    //       FROM reg_periksa, pasien, dokter, poliklinik, penjab
    //       WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    //       AND reg_periksa.kd_poli != '$igd'
    //       AND reg_periksa.tgl_registrasi BETWEEN '$tgl_kunjungan' AND '$tgl_kunjungan_akhir'
    //       AND reg_periksa.kd_dokter = dokter.kd_dokter
    //       AND reg_periksa.kd_poli = poliklinik.kd_poli
    //       AND reg_periksa.kd_pj = penjab.kd_pj";

    if ($this->core->getUserInfo('role') != 'admin') {
      $sql .= " AND r.kd_poli IN ('$poliklinik')";
    }
    if ($status_periksa == 'belum') {
      $sql .= " AND r.stts = 'Belum'";
    }
    if ($status_periksa == 'selesai') {
      $sql .= " AND r.stts = 'Sudah'";
    }
    if ($status_periksa == 'lunas') {
      $sql .= " AND r.status_bayar = 'Sudah Bayar'";
    }

    // $sql .= " ) AS a LEFT JOIN temporary2 t ON a.no_rawat = t.temp2";

    $stmt = $this->db()->pdo()->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $this->assign['list'] = [];
    foreach ($rows as $row) {

      $row['finger'] = 'false';
      if (count($dataFinger) > 0) {
        foreach ($dataFinger['list'] as $finger) {
          if ($finger['noKartu'] == $row['no_peserta'])   $row['finger'] = 'true';
        }
      }

      if(isset( $arr_timestamp[$row['no_rawat']]['mulai'])){
        $row['mulai'] =$arr_timestamp[$row['no_rawat']]['mulai'];
        $row['selesai'] = $arr_timestamp[$row['no_rawat']]['selesai'];
      }else{
        $row['mulai'] ='-';
        $row['selesai'] = '-';
      }
      if(isset($arr_sep[$row['no_rawat']])){
        $row['no_sep'] =  $arr_sep[$row['no_rawat']]['no_sep'];
        $row['catatan'] =  $arr_sep[$row['no_rawat']]['catatan'];
        $row['katarak'] =  ($arr_sep[$row['no_rawat']]['katarak'] == '1.Ya') ? 'Katarak' : 'Tidak Katarak';
        $row['peserta'] =  $arr_sep[$row['no_rawat']]['peserta'];
        $row['klsrawat'] =  $arr_sep[$row['no_rawat']]['klsrawat'];
      }else{
        $row['no_sep'] =  '-';
        $row['catatan'] =  '-';
        $row['katarak'] =  '-';
        $row['peserta'] =  '-';
        $row['klsrawat'] =  '-';
      }
      
      
      $this->assign['list'][] = $row;
    }

    if (isset($_POST['no_rawat'])) {
      $this->assign['reg_periksa'] = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('no_rawat', $_POST['no_rawat'])
        ->oneArray();
    } else {
      $this->assign['reg_periksa'] = [
        'no_rkm_medis' => '',
        'nm_pasien' => '',
        'no_reg' => '',
        'no_rawat' => '',
        'tgl_registrasi' => '',
        'jam_reg' => '',
        'kd_dokter' => '',
        'no_rm' => '',
        'kd_poli' => '',
        'p_jawab' => '',
        'almt_pj' => '',
        'hubunganpj' => '',
        'biaya_reg' => '',
        'stts' => '',
        'stts_daftar' => '',
        'status_lanjut' => '',
        'kd_pj' => '',
        'umurdaftar' => '',
        'sttsumur' => '',
        'status_bayar' => '',
        'status_poli' => '',
        'nm_pasien' => '',
        'tgl_lahir' => '',
        'jk' => '',
        'alamat' => '',
        'no_tlp' => '',
        'pekerjaan' => ''
      ];
    }
  }

  public function anyForm()
  {

    $this->assign['poliklinik'] = $this->db('poliklinik')->where('status', '1')->toArray();
    $this->assign['dokter'] = $this->db('dokter')->where('status', '1')->toArray();
    $this->assign['penjab'] = $this->db('penjab')->toArray();
    $this->assign['no_rawat'] = '';
    $this->assign['no_reg']     = '';
    $this->assign['tgl_registrasi'] = date('Y-m-d');
    $this->assign['jam_reg'] = date('H:i:s');
    if (isset($_POST['no_rawat'])) {
      $this->assign['reg_periksa'] = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('no_rawat', $_POST['no_rawat'])
        ->oneArray();
      echo $this->draw('form.html', [
        'rawat_jalan' => $this->assign
      ]);
    } else {
      $this->assign['reg_periksa'] = [
        'no_rkm_medis' => '',
        'nm_pasien' => '',
        'no_reg' => '',
        'no_rawat' => '',
        'tgl_registrasi' => '',
        'jam_reg' => '',
        'kd_dokter' => '',
        'no_rm' => '',
        'kd_poli' => '',
        'p_jawab' => '',
        'almt_pj' => '',
        'hubunganpj' => '',
        'biaya_reg' => '',
        'stts' => '',
        'stts_daftar' => '',
        'status_lanjut' => '',
        'kd_pj' => '',
        'umurdaftar' => '',
        'sttsumur' => '',
        'status_bayar' => '',
        'status_poli' => '',
        'nm_pasien' => '',
        'tgl_lahir' => '',
        'jk' => '',
        'alamat' => '',
        'no_tlp' => '',
        'pekerjaan' => ''
      ];
      echo $this->draw('form.html', [
        'rawat_jalan' => $this->assign
      ]);
    }
    exit();
  }

  public function anyStatusDaftar()
  {
    if (isset($_POST['no_rkm_medis'])) {
      $rawat = $this->db('reg_periksa')
        ->where('no_rkm_medis', $_POST['no_rkm_medis'])
        ->where('status_bayar', 'Belum Bayar')
        ->limit(1)
        ->oneArray();
      if ($rawat) {
        $stts_daftar = "Transaki tanggal " . date('Y-m-d', strtotime($rawat['tgl_registrasi'])) . " belum diselesaikan";
        $stts_daftar_hidden = $stts_daftar;
        if ($this->settings->get('settings.cekstatusbayar') == 'false') {
          $stts_daftar_hidden = 'Lama';
        }
        $bg_status = 'has-error';
      } else {
        $result = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();
        if ($result >= 1) {
          $stts_daftar = 'Lama';
          $bg_status = 'has-info';
          $stts_daftar_hidden = $stts_daftar;
        } else {
          $stts_daftar = 'Baru';
          $bg_status = 'has-success';
          $stts_daftar_hidden = $stts_daftar;
        }
      }
      echo $this->draw('stts.daftar.html', ['stts_daftar' => $stts_daftar, 'stts_daftar_hidden' => $stts_daftar_hidden, 'bg_status' => $bg_status]);
    } else {
      $rawat = $this->db('reg_periksa')
        ->where('no_rawat', $_POST['no_rawat'])
        ->oneArray();
      echo $this->draw('stts.daftar.html', ['stts_daftar' => $rawat['stts_daftar']]);
    }
    exit();
  }

  public function postSave()
  {
    if ($_POST['tgl_registrasi'] > date('Y-m-d')) {
      $this->db('booking_registrasi')->save([
        'tanggal_booking' => date('Y-m-d'),
        'jam_booking' => date('H:i:s'),
        'no_rkm_medis' => $_POST['no_rkm_medis'],
        'tanggal_periksa' => $_POST['tgl_registrasi'],
        'kd_dokter' => $_POST['kd_dokter'],
        'kd_poli' => $_POST['kd_poli'],
        'no_reg' => $this->core->setNoReg($_POST['kd_dokter'], $_POST['kd_poli']),
        'kd_pj' => $_POST['kd_pj'],
        'limit_reg' => '0',
        'waktu_kunjungan' => $_POST['jam_reg'],
        'status' => 'Belum'
      ]);
    } else if (!$this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->oneArray()) {

      $_POST['status_lanjut'] = 'Ralan';
      $_POST['stts'] = 'Belum';
      $_POST['status_bayar'] = 'Belum Bayar';
      $_POST['p_jawab'] = '-';
      $_POST['almt_pj'] = '-';
      $_POST['hubunganpj'] = '-';

      $poliklinik = $this->db('poliklinik')->where('kd_poli', $_POST['kd_poli'])->oneArray();

      $_POST['biaya_reg'] = $poliklinik['registrasi'];

      $pasien = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();

      $birthDate = new \DateTime($pasien['tgl_lahir']);
      $today = new \DateTime("today");
      $umur_daftar = "0";
      $status_umur = 'Hr';
      if ($birthDate < $today) {
        $y = $today->diff($birthDate)->y;
        $m = $today->diff($birthDate)->m;
        $d = $today->diff($birthDate)->d;
        $umur_daftar = $d;
        $status_umur = "Hr";
        if ($y != '0') {
          $umur_daftar = $y;
          $status_umur = "Th";
        }
        if ($y == '0' && $m != '0') {
          $umur_daftar = $m;
          $status_umur = "Bl";
        }
      }

      $_POST['umurdaftar'] = $umur_daftar;
      $_POST['sttsumur'] = $status_umur;
      $_POST['status_poli'] = 'Lama';

      $query = $this->db('reg_periksa')->save($_POST);
    } else {
      $query = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save([
        'kd_poli' => $_POST['kd_poli'],
        'kd_dokter' => $_POST['kd_dokter'],
        'kd_pj' => $_POST['kd_pj']
      ]);
    }

    if ($query) {
      $data['status'] = 'success';
      echo json_encode($data);
    } else {
      $data['status'] = 'error';
      echo json_encode($data);
    }

    exit();
  }

  public function anyBooking($page = 1)
  {

    $this->core->addCSS(url('assets/css/jquery-ui.css'));
    $this->core->addCSS(url('assets/css/jquery.timepicker.css'));

    // JS
    $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');
    $this->core->addJS(url('assets/jscripts/jquery.timepicker.js'), 'footer');

    $waapitoken =  $this->settings->get('settings.waapitoken');
    $waapiphonenumber =  $this->settings->get('settings.waapiphonenumber');
    $nama_instansi =  $this->settings->get('settings.nama_instansi');

    if (isset($_POST['valid'])) {
      if (isset($_POST['no_rkm_medis']) && !empty($_POST['no_rkm_medis'])) {
        foreach ($_POST['no_rkm_medis'] as $item) {

          $row = $this->db('booking_registrasi')->where('no_rkm_medis', $item)->where('tanggal_periksa', date('Y-m-d'))->oneArray();

          $cek_stts_daftar = $this->db('reg_periksa')->where('no_rkm_medis', $item)->count();
          $_POST['stts_daftar'] = 'Baru';
          if ($cek_stts_daftar > 0) {
            $_POST['stts_daftar'] = 'Lama';
          }

          $biaya_reg = $this->db('poliklinik')->where('kd_poli', $row['kd_poli'])->oneArray();
          $_POST['biaya_reg'] = $biaya_reg['registrasi'];
          if ($_POST['stts_daftar'] == 'Lama') {
            $_POST['biaya_reg'] = $biaya_reg['registrasilama'];
          }

          $cek_status_poli = $this->db('reg_periksa')->where('no_rkm_medis', $item)->where('kd_poli', $row['kd_poli'])->count();
          $_POST['status_poli'] = 'Baru';
          if ($cek_status_poli > 0) {
            $_POST['status_poli'] = 'Lama';
          }

          // set umur
          $tanggal = new \DateTime($this->getPasienInfo('tgl_lahir', $item));
          $today = new \DateTime(date('Y-m-d'));
          $y = $today->diff($tanggal)->y;
          $m = $today->diff($tanggal)->m;
          $d = $today->diff($tanggal)->d;

          $umur = "0";
          $sttsumur = "Th";
          if ($y > 0) {
            $umur = $y;
            $sttsumur = "Th";
          } else if ($y == 0) {
            if ($m > 0) {
              $umur = $m;
              $sttsumur = "Bl";
            } else if ($m == 0) {
              $umur = $d;
              $sttsumur = "Hr";
            }
          }

          if ($row['status'] == 'Belum') {
            $insert = $this->db('reg_periksa')
              ->save([
                'no_reg' => $row['no_reg'],
                'no_rawat' => $this->setNoRawat(),
                'tgl_registrasi' => date('Y-m-d'),
                'jam_reg' => date('H:i:s'),
                'kd_dokter' => $row['kd_dokter'],
                'no_rkm_medis' => $item,
                'kd_poli' => $row['kd_poli'],
                'p_jawab' => $this->getPasienInfo('namakeluarga', $item),
                'almt_pj' => $this->getPasienInfo('alamatpj', $item),
                'hubunganpj' => $this->getPasienInfo('keluarga', $item),
                'biaya_reg' => $_POST['biaya_reg'],
                'stts' => 'Belum',
                'stts_daftar' => $_POST['stts_daftar'],
                'status_lanjut' => 'Ralan',
                'kd_pj' => $row['kd_pj'],
                'umurdaftar' => $umur,
                'sttsumur' => $sttsumur,
                'status_bayar' => 'Belum Bayar',
                'status_poli' => $_POST['status_poli']
              ]);

            if ($insert) {
              $this->db('booking_registrasi')->where('no_rkm_medis', $item)->where('tanggal_periksa', date('Y-m-d'))->update('status', 'Terdaftar');
              $this->notify('success', 'Validasi sukses');
            } else {
              $this->notify('failure', 'Validasi gagal');
            }
          }
        }

        redirect(url([ADMIN, 'rawat_jalan', 'booking']));
      }
    }

    $this->_addHeaderFiles();
    $start_date = date('Y-m-d');
    if (isset($_GET['start_date']) && $_GET['start_date'] != '')
      $start_date = $_GET['start_date'];
    $end_date = date('Y-m-d');
    if (isset($_GET['end_date']) && $_GET['end_date'] != '')
      $end_date = $_GET['end_date'];
    $perpage = '10';
    $phrase = '';
    if (isset($_GET['s']))
      $phrase = $_GET['s'];

    // pagination
    $totalRecords = $this->db()->pdo()->prepare("SELECT booking_registrasi.no_rkm_medis FROM booking_registrasi, pasien WHERE booking_registrasi.no_rkm_medis = pasien.no_rkm_medis AND (booking_registrasi.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ?) AND booking_registrasi.tanggal_periksa BETWEEN '$start_date' AND '$end_date'");
    $totalRecords->execute(['%' . $phrase . '%', '%' . $phrase . '%']);
    $totalRecords = $totalRecords->fetchAll();

    $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), $perpage, url([ADMIN, 'rawat_jalan', 'booking', '%d?s=' . $phrase . '&start_date=' . $start_date . '&end_date=' . $end_date]));
    $this->assign['pagination'] = $pagination->nav('pagination', '5');
    $this->assign['totalRecords'] = $totalRecords;

    $offset = $pagination->offset();
    $query = $this->db()->pdo()->prepare("SELECT booking_registrasi.*, pasien.nm_pasien, pasien.alamat, pasien.no_tlp, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab, pasien.no_peserta FROM booking_registrasi, pasien, dokter, poliklinik, penjab WHERE booking_registrasi.no_rkm_medis = pasien.no_rkm_medis AND booking_registrasi.kd_dokter = dokter.kd_dokter AND booking_registrasi.kd_poli = poliklinik.kd_poli AND booking_registrasi.kd_pj = penjab.kd_pj AND (booking_registrasi.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ?) AND booking_registrasi.tanggal_periksa BETWEEN '$start_date' AND '$end_date' LIMIT $perpage OFFSET $offset");
    $query->execute(['%' . $phrase . '%', '%' . $phrase . '%']);
    $rows = $query->fetchAll();

    $this->assign['list'] = [];
    if (count($rows)) {
      foreach ($rows as $row) {
        $row = htmlspecialchars_array($row);
        $this->assign['list'][] = $row;
      }
    }

    $this->assign['searchUrl'] =  url([ADMIN, 'rawat_jalan', 'booking', $page . '?s=' . $phrase . '&start_date=' . $start_date . '&end_date=' . $end_date]);
    return $this->draw('booking.html', ['booking' => $this->assign, 'waapitoken' => $waapitoken, 'waapiphonenumber' => $waapiphonenumber, 'nama_instansi' => $nama_instansi]);
  }

  public function getBookingPeriksa()
  {
    $date = date('Y-m-d');
    $text = 'Booking Periksa';

    // CSS
    $this->core->addCSS(url('assets/css/jquery-ui.css'));
    $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
    // JS
    $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');
    $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'), 'footer');
    $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'), 'footer');

    return $this->draw(
      'booking.periksa.html',
      [
        'text' => $text,
        'waapitoken' => $this->settings->get('settings.waapitoken'),
        'waapiphonenumber' => $this->settings->get('settings.waapiphonenumber'),
        'nama_instansi' => $this->settings->get('settings.nama_instansi'),
        'booking' => $this->db('booking_periksa')
          ->select([
            'no_booking' => 'booking_periksa.no_booking',
            'tanggal' => 'booking_periksa.tanggal',
            'nama' => 'booking_periksa.nama',
            'no_telp' => 'booking_periksa.no_telp',
            'alamat' => 'booking_periksa.alamat',
            'email' => 'booking_periksa.email',
            'nm_poli' => 'poliklinik.nm_poli',
            'status' => 'booking_periksa.status',
            'tanggal_booking' => 'booking_periksa.tanggal_booking'
          ])
          ->join('poliklinik', 'poliklinik.kd_poli = booking_periksa.kd_poli')
          //->where('tambahan_pesan', 'jkn_mobile_v2')
          ->toArray()
      ]
    );
  }

  public function postSaveBookingPeriksa()
  {
    $this->db('booking_periksa')->where('no_booking', $_POST['no_booking'])->save(['status' => $_POST['status']]);
    $this->db('booking_periksa_balasan')
      ->save([
        'no_booking' => $_POST['no_booking'],
        'balasan' => $_POST['message']
      ]);
    exit();
  }

  public function anyKontrol()
  {
    $rows = $this->db('booking_registrasi')
      ->join('poliklinik', 'poliklinik.kd_poli=booking_registrasi.kd_poli')
      ->join('dokter', 'dokter.kd_dokter=booking_registrasi.kd_dokter')
      ->join('penjab', 'penjab.kd_pj=booking_registrasi.kd_pj')
      ->where('no_rkm_medis', $_POST['no_rkm_medis'])
      ->toArray();
    $i = 1;
    $result = [];
    foreach ($rows as $row) {
      $row['nomor'] = $i++;
      $result[] = $row;
    }
    echo $this->draw('kontrol.html', ['booking_registrasi' => $result]);
    exit();
  }

  public function postSaveKontrol()
  {

    $query = $this->db('skdp_bpjs')->save([
      'tahun' => date('Y'),
      'no_rkm_medis' => $_POST['no_rkm_medis'],
      'diagnosa' => $_POST['diagnosa'],
      'terapi' => $_POST['terapi'],
      'alasan1' => $_POST['alasan1'],
      'alasan2' => '',
      'rtl1' => $_POST['rtl1'],
      'rtl2' => '',
      'tanggal_datang' => $_POST['tanggal_datang'],
      'tanggal_rujukan' => $_POST['tanggal_rujukan'],
      'no_antrian' => $this->core->setNoSKDP(),
      'kd_dokter' => $this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']),
      'status' => 'Menunggu'
    ]);

    if ($query) {
      $this->db('booking_registrasi')
        ->save([
          'tanggal_booking' => date('Y-m-d'),
          'jam_booking' => date('H:i:s'),
          'no_rkm_medis' => $_POST['no_rkm_medis'],
          'tanggal_periksa' => $_POST['tanggal_datang'],
          'kd_dokter' => $this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']),
          'kd_poli' => $this->core->getRegPeriksaInfo('kd_poli', $_POST['no_rawat']),
          'no_reg' => $this->core->setNoBooking($this->core->getUserInfo('username', null, true), $_POST['tanggal_rujukan']),
          'kd_pj' => $this->core->getRegPeriksaInfo('kd_pj', $_POST['no_rawat']),
          'limit_reg' => 0,
          'waktu_kunjungan' => $_POST['tanggal_datang'] . ' ' . date('H:i:s'),
          'status' => 'Belum'
        ]);
    }

    exit();
  }

  public function postHapusKontrol()
  {
    $this->db('booking_registrasi')->where('kd_dokter', $_POST['kd_dokter'])->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tanggal_periksa', $_POST['tanggal_periksa'])->where('status', 'Belum')->delete();
    $this->db('skdp_bpjs')->where('kd_dokter', $_POST['kd_dokter'])->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tanggal_datang', $_POST['tanggal_periksa'])->where('status', 'Menunggu')->delete();
    exit();
  }

  public function getJadwal()
  {
    // JS
    $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');
    $this->core->addJS(url('assets/jscripts/jquery.timepicker.js'), 'footer');
    $this->_addHeaderFiles();
    $rows = $this->db('jadwal')->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli')->toArray();
    $this->assign['jadwal'] = [];
    foreach ($rows as $row) {
      $row['delURL'] = url([ADMIN, 'rawat_jalan', 'jadwaldel', $row['kd_dokter'], $row['hari_kerja']]);
      $row['editURL'] = url([ADMIN, 'rawat_jalan', 'jadwaledit', $row['kd_dokter'], $row['hari_kerja']]);
      $this->assign['jadwal'][] = $row;
    }

    return $this->draw('jadwal.html', ['pendaftaran' => $this->assign]);
  }

  public function getJadwalDel($kd_dokter, $hari_kerja)
  {
    if ($pendaftaran = $this->db('jadwal')->where('kd_dokter', $kd_dokter)->where('hari_kerja', $hari_kerja)->oneArray()) {
      if ($this->db('jadwal')->where('kd_dokter', $kd_dokter)->where('hari_kerja', $hari_kerja)->delete()) {
        $this->notify('success', 'Hapus sukses');
      } else {
        $this->notify('failure', 'Hapus gagal');
      }
    }
    redirect(url([ADMIN, 'rawat_jalan', 'jadwal']));
  }

  public function getJadwalAdd()
  {
    $this->core->addCSS(url('assets/css/jquery-ui.css'));
    $this->core->addCSS(url('assets/css/jquery.timepicker.css'));

    // JS
    $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');
    $this->core->addJS(url('assets/jscripts/jquery.timepicker.js'), 'footer');
    $this->_addHeaderFiles();
    if (!empty($redirectData = getRedirectData())) {
      $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
    } else {
      $this->assign['form'] = [
        'kd_dokter' => '',
        'hari_kerja' => '',
        'jam_mulai' => '',
        'jam_selesai' => '',
        'kd_poli' => '',
        'kuota' => ''
      ];
    }
    $this->assign['title'] = 'Tambah Jadwal Dokter';
    $this->assign['dokter'] = $this->db('dokter')->toArray();
    $this->assign['poliklinik'] = $this->db('poliklinik')->toArray();
    $this->assign['hari_kerja'] = $this->getEnum('jadwal', 'hari_kerja');
    $this->assign['postUrl'] = url([ADMIN, 'rawat_jalan', 'jadwalsave', $this->assign['form']['kd_dokter'], $this->assign['form']['hari_kerja']]);
    return $this->draw('jadwal.form.html', ['pendaftaran' => $this->assign]);
  }

  public function getJadwalEdit($id, $hari_kerja)
  {
    $this->core->addCSS(url('assets/css/jquery-ui.css'));
    $this->core->addCSS(url('assets/css/jquery.timepicker.css'));

    // JS
    $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');
    $this->core->addJS(url('assets/jscripts/jquery.timepicker.js'), 'footer');
    $this->_addHeaderFiles();
    $row = $this->db('jadwal')->where('kd_dokter', $id)->where('hari_kerja', $hari_kerja)->oneArray();
    if (!empty($row)) {
      $this->assign['form'] = $row;
      $this->assign['title'] = 'Edit Jadwal';
      $this->assign['hari_kerja'] = $this->getEnum('jadwal', 'hari_kerja');
      $this->assign['dokter'] = $this->db('dokter')->toArray();
      $this->assign['poliklinik'] = $this->db('poliklinik')->toArray();

      $this->assign['postUrl'] = url([ADMIN, 'rawat_jalan', 'jadwalsave', $this->assign['form']['kd_dokter'], $this->assign['form']['hari_kerja']]);
      return $this->draw('jadwal.form.html', ['pendaftaran' => $this->assign]);
    } else {
      redirect(url([ADMIN, 'rawat_jalan', 'jadwal']));
    }
  }

  public function postJadwalSave($id = null, $hari_kerja = null)
  {
    $errors = 0;

    if (!$id) {
      $location = url([ADMIN, 'rawat_jalan', 'jadwal']);
    } else {
      $location = url([ADMIN, 'rawat_jalan', 'jadwaledit', $_POST['kd_dokter'], $_POST['hari_kerja']]);
    }

    if (checkEmptyFields(['kd_dokter', 'hari_kerja', 'kd_poli'], $_POST)) {
      $this->notify('failure', 'Isian kosong');
      redirect($location, $_POST);
    }

    if (!$errors) {
      unset($_POST['save']);

      if (!$id) {    // new
        $query = $this->db('jadwal')->save($_POST);
      } else {        // edit
        $query = $this->db('jadwal')->where('kd_dokter', $id)->where('hari_kerja', $hari_kerja)->save($_POST);
      }

      if ($query) {
        $this->notify('success', 'Simpan sukes');
      } else {
        $this->notify('failure', 'Simpan gagal');
      }

      redirect($location);
    }

    redirect($location, $_POST);
  }

  public function postStatusRawat()
  {
    $datetime = date('Y-m-d H:i:s');
    $time = date('H:i:s');
    $cek = $this->db('mutasi_berkas')->where('no_rawat', $_POST['no_rawat'])->oneArray();
    $kodebooking = "";
    //get nomor kodebooking
    $dataRefMobileJKN = $this->db('referensi_mobilejkn_bpjs')->where('no_rawat', $_POST['no_rawat'])->oneArray();
    if (!empty($dataRefMobileJKN)) {
      $kodebooking = $dataRefMobileJKN['nobooking'];
    }

    if ($_POST['stts'] == 'Berkas Dikirim') {
      if (!$this->db('mutasi_berkas')->where('no_rawat', $_POST['no_rawat'])->oneArray()) {
        // $this->core->db()->pdo()->exec("INSERT INTO `mutasi_berkas` (no_rawat, status, dikirim, diterima, kembali, tidakada, ranap) 
        // VALUES ('" . $_POST['no_rawat'] . "','Sudah Diterima','$datetime','0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00')");
        $this->db('mutasi_berkas')->save([
          'no_rawat' => $_POST['no_rawat'],
          'status' => 'Sudah Dikirim',
          'dikirim' => $datetime,
          'diterima' => '0000-00-00 00:00:00',
          'kembali' => '0000-00-00 00:00:00',
          'tidakada' => '0000-00-00 00:00:00',
          'ranap' => '0000-00-00 00:00:00'
        ]);
      }
    } else if ($_POST['stts'] == 'Berkas Diterima') {
      if (!$this->db('mutasi_berkas')->where('no_rawat', $_POST['no_rawat'])->oneArray()) {
        // $this->core->db()->pdo()->exec("INSERT INTO `mutasi_berkas` (no_rawat, status, dikirim, diterima, kembali, tidakada, ranap) 
        // VALUES ('" . $_POST['no_rawat'] . "','Sudah Diterima','$datetime','$datetime','0000-00-00 00:00:00','0000-00-00 00:00:00','0000-00-00 00:00:00')");
        $this->db('mutasi_berkas')->save([
          'no_rawat' => $_POST['no_rawat'],
          'status' => 'Sudah Diterima',
          'dikirim' => $datetime,
          'diterima' => $datetime,
          'kembali' => '0000-00-00 00:00:00',
          'tidakada' => '0000-00-00 00:00:00',
          'ranap' => '0000-00-00 00:00:00'
        ]);
      } else {
        $this->db('mutasi_berkas')->where('no_rawat', $_POST['no_rawat'])->save([
          'status' => 'Sudah Diterima',
          'diterima' => $datetime
        ]);
      }
      //cek jika sudah ada record
      if (empty($this->db('record_waktulayan_bpjs')->where('no_rawat', $_POST['no_rawat'])->oneArray())) {
        // echo $_POST['no_rawat'] . ' tidak ada ' . $time;
        $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save($_POST);

        //add waktu pasien saat mulai layan
        $this->core->db()->pdo()->exec("INSERT INTO `record_waktulayan_bpjs` (no_rawat, tgl_perawatan, taskid4) VALUES ('" . $_POST['no_rawat'] . "','".date('Y-m-d')."','$time')");
        // $this->core->db()->pdo()->exec("INSERT INTO `temporary` (no_rawat, tgl_perawatan, taskid4) VALUES ('" . $_POST['no_rawat'] . "','".date('Y-m-d')."','$time')");
        // $this->db('temporary2')->save([
        //   'temp1' => 'waktupasien',
        //   'temp2' => $_POST['no_rawat'],
        //   'temp3' => $time,
        //   'temp4' => '-'
        // ]);
      }

      if ($kodebooking != '') {
        //send data Update waktu antrean = 4
        $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kodebooking, 4);
        $response = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
        if ($response['metadata']['code'] != '200') {
          $this->db('mlite_settings')->save([
            'module' => 'debug',
            'field' => 'post status rawat Berkas Diterima',
            'value' => $kodebooking . '|' . $response['metadata']['code'] . '|' . $response['metadata']['message']
          ]);
        }
      }

      
      //trigger send whatsapp
      $pasien = $this->db('pasien')->where('no_rkm_medis', $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->oneArray()['no_rkm_medis'])->oneArray();
      // get pasien no_telp
      $no_telp = $pasien['no_tlp'];
      // $data['whatsapp'] = $no_telp;
      // get pasien nama
      $nama = $pasien['nm_pasien'];
      $data['whatsapp'] = $this->postWhatsapp($no_telp, $nama);

    } else if ($_POST['stts'] == 'Sudah') { //pasien sudah selesai dilayani

      //cek jika sudah ada record
      if (empty($this->db('record_waktulayan_bpjs')->where('no_rawat', $_POST['no_rawat'])->oneArray())) {
        $data['status'] = 'error';
        $data['message'] = 'Status pasien tidak runut, silahkan update "Berkas Diterima" terlebih dahulu.';
        echo json_encode($data);
      } else {
        $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save($_POST);
        //update waktu pasien saat selesai layan
        $this->db('record_waktulayan_bpjs')->where('no_rawat', $_POST['no_rawat'])->update('taskid5', $time);
        if ($kodebooking != '') {
          
          //send data Update waktu antrean = 5
          $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kodebooking, 5, $_POST['jenisObat']);
          $response = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);

          //tambah antrean farmasi
          $dataTambahAntreanFarmasi = $this->tambahAntreanFarmasiBPJS($kodebooking,$_POST['no_rawat'],$_POST['jenisObat']);
          $response = $this->sendDataWSBPJS('antrean/farmasi/add', $dataTambahAntreanFarmasi);

          // if ($response['metadata']['code'] != '200') {
          //   $this->db('mlite_settings')->save([
          //     'module' => 'debug',
          //     'field' => 'post status rawat Sudah dilayani',
          //     'value' => $kodebooking . '|' . $response['metadata']['code'] . '|' . $response['metadata']['message']
          //   ]);
          // }
        }
        $data['status'] = 'success';
        $data['message'] = 'Status pasien berhasil diubah.';


        echo json_encode($data);
      }
    } else if ($_POST['stts'] == 'Batal') { //pasien batal dilayani

      $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save($_POST);
      if ($kodebooking != '') {
        //send data Update waktu antrean = 99
        $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kodebooking, 99);
        $dataBatalAntrean = $this->batalAntreanBPJS($kodebooking, 'Pasien tidak hadir di poli.');
        $response1 = $this->sendDataWSBPJS('antrean/batal', $dataBatalAntrean);
        $response2 = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
        $response3 = $this->sendDataWSRS('batalantrean', $dataBatalAntrean);

        if ($response1['metadata']['code'] != '200') {
          $this->db('mlite_settings')->save([
            'module' => 'debug',
            'field' => 'post status rawat Batal1',
            'value' => $kodebooking . '|' . $response1['metadata']['code'] . '|' . $response1['metadata']['message']
          ]);
        }
        if ($response2['metadata']['code'] != '200') {
          $this->db('mlite_settings')->save([
            'module' => 'debug',
            'field' => 'post status rawat Batal2',
            'value' => $kodebooking . '|' . $response2['metadata']['code'] . '|' . $response2['metadata']['message']
          ]);
        }
        if ($response3['metadata']['code'] != '200') {
          $this->db('mlite_settings')->save([
            'module' => 'debug',
            'field' => 'post status rawat Batal3',
            'value' => $kodebooking . '|' . $response3['metadata']['code'] . '|' . $response3['metadata']['message']
          ]);
        }
      }
    } else {
      $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save($_POST);
    }
    exit();
  }

  public function postStatusLanjut()
  {
    $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save([
      'status_lanjut' => 'Ranap'
    ]);
    exit();
  }

  public function anyPasien()
  {
    if (isset($_POST['cari'])) {
      $pasien = $this->db('pasien')
        ->like('no_rkm_medis', '%' . $_POST['cari'] . '%')
        ->orLike('nm_pasien', '%' . $_POST['cari'] . '%')
        ->asc('no_rkm_medis')
        ->limit(5)
        ->toArray();
    }
    echo $this->draw('pasien.html', ['pasien' => $pasien]);
    exit();
  }

  public function getAntrian()
  {
    $settings = $this->settings('settings');
    $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
    $rawat_jalan = $this->db('reg_periksa')
      ->select('reg_periksa.no_reg')
      ->select('referensi_mobilejkn_bpjs.nobooking')
      ->select('reg_periksa.no_rawat')
      ->select('pasien.nm_pasien')
      ->select('reg_periksa.no_rkm_medis')
      ->select('poliklinik.nm_poli')
      ->select('dokter.nm_dokter')
      ->select('penjab.png_jawab')
      ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
      ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
      ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
      ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
      ->leftJoin('referensi_mobilejkn_bpjs', 'referensi_mobilejkn_bpjs.no_rawat=reg_periksa.no_rawat')
      ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
      ->oneArray();
    echo $this->draw('antrian.html', ['rawat_jalan' => $rawat_jalan]);
    exit();
  }

  public function postHapus()
  {
    $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->delete();
    exit();
  }

  public function postSaveDetail()
  {
    if ($_POST['kat'] == 'tindakan') {
      $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->oneArray();
      if ($_POST['provider'] == 'rawat_jl_dr') {
        $this->db('rawat_jl_dr')->save([
          'no_rawat' => $_POST['no_rawat'],
          'kd_jenis_prw' => $_POST['kd_jenis_prw'],
          'kd_dokter' => $_POST['kode_provider'],
          'tgl_perawatan' => $_POST['tgl_perawatan'],
          'jam_rawat' => $_POST['jam_rawat'],
          'material' => $jns_perawatan['material'],
          'bhp' => $jns_perawatan['bhp'],
          'tarif_tindakandr' => $jns_perawatan['tarif_tindakandr'],
          'kso' => $jns_perawatan['kso'],
          'menejemen' => $jns_perawatan['menejemen'],
          'biaya_rawat' => $jns_perawatan['total_byrdr'],
          'stts_bayar' => 'Belum'
        ]);
      }
      if ($_POST['provider'] == 'rawat_jl_pr') {
        $this->db('rawat_jl_pr')->save([
          'no_rawat' => $_POST['no_rawat'],
          'kd_jenis_prw' => $_POST['kd_jenis_prw'],
          'nip' => $_POST['kode_provider2'],
          'tgl_perawatan' => $_POST['tgl_perawatan'],
          'jam_rawat' => $_POST['jam_rawat'],
          'material' => $jns_perawatan['material'],
          'bhp' => $jns_perawatan['bhp'],
          'tarif_tindakanpr' => $jns_perawatan['tarif_tindakanpr'],
          'kso' => $jns_perawatan['kso'],
          'menejemen' => $jns_perawatan['menejemen'],
          'biaya_rawat' => $jns_perawatan['total_byrdr'],
          'stts_bayar' => 'Belum'
        ]);
      }
      if ($_POST['provider'] == 'rawat_jl_drpr') {
        $this->db('rawat_jl_drpr')->save([
          'no_rawat' => $_POST['no_rawat'],
          'kd_jenis_prw' => $_POST['kd_jenis_prw'],
          'kd_dokter' => $_POST['kode_provider'],
          'nip' => $_POST['kode_provider2'],
          'tgl_perawatan' => $_POST['tgl_perawatan'],
          'jam_rawat' => $_POST['jam_rawat'],
          'material' => $jns_perawatan['material'],
          'bhp' => $jns_perawatan['bhp'],
          'tarif_tindakandr' => $jns_perawatan['tarif_tindakandr'],
          'tarif_tindakanpr' => $jns_perawatan['tarif_tindakanpr'],
          'kso' => $jns_perawatan['kso'],
          'menejemen' => $jns_perawatan['menejemen'],
          'biaya_rawat' => $jns_perawatan['total_byrdr'],
          'stts_bayar' => 'Belum'
        ]);
      }
    }
    exit();
  }

  public function postHapusDetail()
  {
    if ($_POST['provider'] == 'rawat_jl_dr') {
      $this->db('rawat_jl_dr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
    }
    if ($_POST['provider'] == 'rawat_jl_pr') {
      $this->db('rawat_jl_pr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
    }
    if ($_POST['provider'] == 'rawat_jl_drpr') {
      $this->db('rawat_jl_drpr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
    }
    exit();
  }

  public function anyRincian()
  {
    $rows_rawat_jl_dr = $this->db('rawat_jl_dr')->where('no_rawat', $_POST['no_rawat'])->toArray();
    $rows_rawat_jl_pr = $this->db('rawat_jl_pr')->where('no_rawat', $_POST['no_rawat'])->toArray();
    $rows_rawat_jl_drpr = $this->db('rawat_jl_drpr')->where('no_rawat', $_POST['no_rawat'])->toArray();

    $jumlah_total = 0;
    $rawat_jl_dr = [];
    $rawat_jl_pr = [];
    $rawat_jl_drpr = [];
    $i = 1;

    if ($rows_rawat_jl_dr) {
      foreach ($rows_rawat_jl_dr as $row) {
        $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
        $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
        $jumlah_total = $jumlah_total + $row['biaya_rawat'];
        $row['provider'] = 'rawat_jl_dr';
        $rawat_jl_dr[] = $row;
      }
    }

    if ($rows_rawat_jl_pr) {
      foreach ($rows_rawat_jl_pr as $row) {
        $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
        $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
        $jumlah_total = $jumlah_total + $row['biaya_rawat'];
        $row['provider'] = 'rawat_jl_pr';
        $rawat_jl_pr[] = $row;
      }
    }

    if ($rows_rawat_jl_drpr) {
      foreach ($rows_rawat_jl_drpr as $row) {
        $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
        $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
        $jumlah_total = $jumlah_total + $row['biaya_rawat'];
        $row['provider'] = 'rawat_jl_drpr';
        $rawat_jl_drpr[] = $row;
      }
    }

    echo $this->draw('rincian.html', ['rawat_jl_dr' => $rawat_jl_dr, 'rawat_jl_pr' => $rawat_jl_pr, 'rawat_jl_drpr' => $rawat_jl_drpr, 'jumlah_total' => $jumlah_total, 'no_rawat' => $_POST['no_rawat']]);
    exit();
  }

  public function anySoap()
  {

    $prosedurs = $this->db('prosedur_pasien')
      ->where('no_rawat', $_POST['no_rawat'])
      ->asc('prioritas')
      ->toArray();
    $prosedur = [];
    foreach ($prosedurs as $row) {
      $icd9 = $this->db('icd9')->where('kode', $row['kode'])->oneArray();
      $row['nama'] = $icd9['deskripsi_panjang'];
      $prosedur[] = $row;
    }
    $diagnosas = $this->db('diagnosa_pasien')
      ->where('no_rawat', $_POST['no_rawat'])
      ->asc('prioritas')
      ->toArray();
    $diagnosa = [];
    foreach ($diagnosas as $row) {
      $icd10 = $this->db('penyakit')->where('kd_penyakit', $row['kd_penyakit'])->oneArray();
      $row['nama'] = $icd10['nm_penyakit'];
      $diagnosa[] = $row;
    }

    $rows = $this->db('pemeriksaan_ralan')
      ->where('no_rawat', $_POST['no_rawat'])
      ->toArray();
    $i = 1;
    $row['nama_petugas'] = '';
    $row['departemen_petugas'] = '';
    $result = [];
    foreach ($rows as $row) {
      $row['nomor'] = $i++;
      $row['nama_petugas'] = $this->core->getPegawaiInfo('nama', $row['nip']);
      $row['departemen_petugas'] = $this->core->getDepartemenInfo($this->core->getPegawaiInfo('departemen', $row['nip']));
      $result[] = $row;
    }

    $result_ranap = [];

    $check_table = $this->db()->pdo()->query("SHOW TABLES LIKE 'pemeriksaan_ranap'");
    $check_table->execute();
    $check_table = $check_table->fetch();
    if ($check_table) {
      $rows_ranap = $this->db('pemeriksaan_ranap')
        ->where('no_rawat', $_POST['no_rawat'])
        ->toArray();
      foreach ($rows_ranap as $row) {
        $row['nomor'] = $i++;
        $row['nama_petugas'] = $this->core->getPegawaiInfo('nama', $row['nip']);
        $row['departemen_petugas'] = $this->core->getDepartemenInfo($this->core->getPegawaiInfo('departemen', $row['nip']));
        $result_ranap[] = $row;
      }
    }

    echo $this->draw('soap.html', ['pemeriksaan' => $result, 'pemeriksaan_ranap' => $result_ranap, 'diagnosa' => $diagnosa, 'prosedur' => $prosedur, 'admin_mode' => $this->settings->get('settings.admin_mode')]);
    exit();
  }

  public function postSaveSOAP()
  {
    $check_db = $this->db()->pdo()->query("SHOW COLUMNS FROM `pemeriksaan_ralan` LIKE 'instruksi'");
    $check_db->execute();
    $check_db = $check_db->fetch();

    if ($check_db) {
      $_POST['nip'] = $this->core->getUserInfo('username', null, true);
    } else {
      unset($_POST['instruksi']);
    }

    if (!$this->db('pemeriksaan_ralan')->where('no_rawat', $_POST['no_rawat'])->where('tgl_perawatan', $_POST['tgl_perawatan'])->where('jam_rawat', $_POST['jam_rawat'])->oneArray()) {
      $this->db('pemeriksaan_ralan')->save($_POST);
    } else {
      $this->db('pemeriksaan_ralan')->where('no_rawat', $_POST['no_rawat'])->where('tgl_perawatan', $_POST['tgl_perawatan'])->where('jam_rawat', $_POST['jam_rawat'])->save($_POST);
    }
    exit();
  }

  public function postHapusSOAP()
  {
    $this->db('pemeriksaan_ralan')->where('no_rawat', $_POST['no_rawat'])->where('tgl_perawatan', $_POST['tgl_perawatan'])->where('jam_rawat', $_POST['jam_rawat'])->delete();
    exit();
  }

  public function anyLayanan()
  {
    $layanan = $this->db('jns_perawatan')
      ->where('status', '1')
      ->like('nm_perawatan', '%' . $_POST['layanan'] . '%')
      ->limit(10)
      ->toArray();
    echo $this->draw('layanan.html', ['layanan' => $layanan]);
    exit();
  }

  public function anyBerkasDigital()
  {
    $berkas_digital = $this->db('berkas_digital_perawatan')->where('no_rawat', $_POST['no_rawat'])->toArray();
    echo $this->draw('berkasdigital.html', ['berkas_digital' => $berkas_digital]);
    exit();
  }

  public function postSaveBerkasDigital()
  {

    $dir    = $this->_uploads;
    $cntr   = 0;

    $image = $_FILES['file']['tmp_name'];
    $img = new \Systems\Lib\Image();
    $id = convertNorawat($_POST['no_rawat']);
    if ($img->load($image)) {
      $imgName = time() . $cntr++;
      $imgPath = $dir . '/' . $id . '_' . $imgName . '.' . $img->getInfos('type');
      $lokasi_file = 'pages/upload/' . $id . '_' . $imgName . '.' . $img->getInfos('type');
      $img->save($imgPath);
      $query = $this->db('berkas_digital_perawatan')->save(['no_rawat' => $_POST['no_rawat'], 'kode' => $_POST['kode'], 'lokasi_file' => $lokasi_file]);
      if ($query) {
        echo '<br><img src="' . WEBAPPS_URL . '/berkasrawat/' . $lokasi_file . '" width="150" />';
      }
    }

    exit();
  }

  public function postProviderList()
  {

    if (isset($_POST["query"])) {
      $output = '';
      $key = "%" . $_POST["query"] . "%";
      $rows = $this->db('dokter')->like('nm_dokter', $key)->where('status', '1')->limit(10)->toArray();
      $output = '';
      if (count($rows)) {
        foreach ($rows as $row) {
          $output .= '<li class="list-group-item link-class">' . $row["kd_dokter"] . ': ' . $row["nm_dokter"] . '</li>';
        }
      }
      echo $output;
    }

    exit();
  }

  public function postProviderList2()
  {

    if (isset($_POST["query"])) {
      $output = '';
      $key = "%" . $_POST["query"] . "%";
      $rows = $this->db('petugas')->like('nama', $key)->limit(10)->toArray();
      $output = '';
      if (count($rows)) {
        foreach ($rows as $row) {
          $output .= '<li class="list-group-item link-class">' . $row["nip"] . ': ' . $row["nama"] . '</li>';
        }
      }
      echo $output;
    }

    exit();
  }

  public function postMaxid()
  {
    $max_id = $this->db('reg_periksa')->select(['no_rawat' => 'ifnull(MAX(CONVERT(RIGHT(no_rawat,6),signed)),0)'])->where('tgl_registrasi', date('Y-m-d'))->oneArray();
    if (empty($max_id['no_rawat'])) {
      $max_id['no_rawat'] = '000000';
    }
    $_next_no_rawat = sprintf('%06s', ($max_id['no_rawat'] + 1));
    $next_no_rawat = date('Y/m/d') . '/' . $_next_no_rawat;
    echo $next_no_rawat;
    exit();
  }

  public function postMaxAntrian()
  {
    $max_id = $this->db('reg_periksa')->select(['no_reg' => 'ifnull(MAX(CONVERT(RIGHT(no_reg,3),signed)),0)'])->where('kd_poli', $_POST['kd_poli'])->where('tgl_registrasi', date('Y-m-d'))->desc('no_reg')->limit(1)->oneArray();
    if ($this->settings->get('settings.dokter_ralan_per_dokter') == 'true') {
      $max_id = $this->db('reg_periksa')->select(['no_reg' => 'ifnull(MAX(CONVERT(RIGHT(no_reg,3),signed)),0)'])->where('kd_poli', $_POST['kd_poli'])->where('kd_dokter', $_POST['kd_dokter'])->where('tgl_registrasi', date('Y-m-d'))->desc('no_reg')->limit(1)->oneArray();
    }
    if (empty($max_id['no_reg'])) {
      $max_id['no_reg'] = '000';
    }
    $_next_no_reg = sprintf('%03s', ($max_id['no_reg'] + 1));

    $date = date('Y-m-d');
    $tentukan_hari = date('D', strtotime(date('Y-m-d')));
    $day = array(
      'Sun' => 'AKHAD',
      'Mon' => 'SENIN',
      'Tue' => 'SELASA',
      'Wed' => 'RABU',
      'Thu' => 'KAMIS',
      'Fri' => 'JUMAT',
      'Sat' => 'SABTU'
    );
    $hari = $day[$tentukan_hari];

    $jadwal_dokter = $this->db('jadwal')->where('kd_poli', $_POST['kd_poli'])->where('kd_dokter', $_POST['kd_dokter'])->where('hari_kerja', $hari)->oneArray();
    $jadwal_poli = $this->db('jadwal')->where('kd_poli', $_POST['kd_poli'])->where('hari_kerja', $hari)->toArray();
    $kuota_poli = 0;
    foreach ($jadwal_poli as $row) {
      $kuota_poli += $row['kuota'];
    }
    if ($this->settings->get('settings.dokter_ralan_per_dokter') == 'true' && $this->settings->get('settings.ceklimit') == 'true' && $_next_no_reg > $jadwal_dokter['kuota']) {
      $_next_no_reg = '888888';
    }
    if ($this->settings->get('settings.dokter_ralan_per_dokter') == 'false' && $this->settings->get('settings.ceklimit') == 'true' && $_next_no_reg > $kuota_poli) {
      $_next_no_reg = '999999';
    }
    echo $_next_no_reg;
    exit();
  }

  public function getPasienInfo($field, $no_rkm_medis)
  {
    $row = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
    return $row[$field];
  }

  public function getRegPeriksaInfo($field, $no_rawat)
  {
    $row = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
    return $row[$field];
  }

  public function setNoRawat()
  {
    $date = date('Y-m-d');
    $last_no_rawat = $this->db()->pdo()->prepare("SELECT ifnull(MAX(CONVERT(RIGHT(no_rawat,6),signed)),0) FROM reg_periksa WHERE tgl_registrasi = '$date'");
    $last_no_rawat->execute();
    $last_no_rawat = $last_no_rawat->fetch();
    if (empty($last_no_rawat[0])) {
      $last_no_rawat[0] = '000000';
    }
    $next_no_rawat = sprintf('%06s', ($last_no_rawat[0] + 1));
    $next_no_rawat = date('Y/m/d') . '/' . $next_no_rawat;

    return $next_no_rawat;
  }

  public function getEnum($table_name, $column_name)
  {
    $result = $this->db()->pdo()->prepare("SHOW COLUMNS FROM $table_name LIKE '$column_name'");
    $result->execute();
    $result = $result->fetch();
    $result = explode("','", preg_replace("/(enum|set)\('(.+?)'\)/", "\\2", $result[1]));
    return $result;
  }

  public function convertNorawat($text)
  {
    setlocale(LC_ALL, 'en_EN');
    $text = str_replace('/', '', trim($text));
    return $text;
  }

  public function postCetak()
  {
    $this->core->db()->pdo()->exec("DELETE FROM `mlite_temporary`");
    $cari = $_POST['cari'];
    $tgl_awal = $_POST['tgl_awal'];
    $tgl_akhir = $_POST['tgl_akhir'];
    $igd = $this->settings->get('settings.igd');
    // $this->core->db()->pdo()->exec("INSERT INTO `mlite_temporary` (
    //     `temp1`,`temp2`,`temp3`,`temp4`,`temp5`,`temp6`,`temp7`,`temp8`,`temp9`,`temp10`,`temp11`,`temp12`,`temp13`,`temp14`,`temp15`,`temp16`,`temp17`,`temp18`,`temp19`
    //   )
    //   SELECT *
    //   FROM `reg_periksa`
    //   WHERE `kd_poli` <> '$igd'
    //   AND `tgl_registrasi` BETWEEN '$tgl_awal' AND '$tgl_akhir'
    //   ");
    exit();
  }

  public function getCetakPdf()
  {
    $tmp = $this->db('mlite_temporary')->toArray();
    $logo = $this->settings->get('settings.logo');

    $pdf = new PDF_MC_Table('L', 'mm', 'Legal');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->SetTopMargin(10);
    $pdf->SetLeftMargin(10);
    $pdf->SetRightMargin(10);

    $pdf->Image('../' . $logo, 10, 8, '18', '18', 'png');
    $pdf->SetFont('Arial', '', 24);
    $pdf->Text(30, 16, $this->settings->get('settings.nama_instansi'));
    $pdf->SetFont('Arial', '', 10);
    $pdf->Text(30, 21, $this->settings->get('settings.alamat') . ' - ' . $this->settings->get('settings.kota'));
    $pdf->Text(30, 25, $this->settings->get('settings.nomor_telepon') . ' - ' . $this->settings->get('settings.email'));
    $pdf->Line(10, 30, 345, 30);
    $pdf->Line(10, 31, 345, 31);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Text(10, 40, 'DATA PENDAFTARAN POLIKLINIK');
    $pdf->Ln(34);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetWidths(array(25, 35, 20, 80, 25, 50, 50, 50));
    $pdf->Row(array('Tanggal', 'No. Rawat', 'No. Reg', 'Nama Pasien', 'No. RM', 'Poliklinik', 'Dokter', 'Penjamin'));
    $pdf->SetFont('Arial', '', 10);
    foreach ($tmp as $hasil) {
      $poliklinik = $this->db('poliklinik')->where('kd_poli', $hasil['temp7'])->oneArray();
      $dokter = $this->db('dokter')->where('kd_dokter', $hasil['temp5'])->oneArray();
      $penjab = $this->db('penjab')->where('kd_pj', $hasil['temp15'])->oneArray();
      $pdf->Row(array($hasil['temp3'], $hasil['temp2'], $hasil['temp1'], $this->core->getPasienInfo('nm_pasien', $hasil['temp6']), $hasil['temp6'], $poliklinik['nm_poli'], $dokter['nm_dokter'], $penjab['png_jawab']));
    }
    $pdf->Output('cetak' . date('Y-m-d') . '.pdf', 'I');
  }

  public function getJavascript()
  {
    header('Content-type: text/javascript');
    echo $this->draw(MODULES . '/rawat_jalan/js/admin/rawat_jalan.js');
    exit();
  }

  private function _addHeaderFiles()
  {
    $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
    $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
    $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
    $this->core->addJS(url('assets/jscripts/lightbox/lightbox.min.js'));
    $this->core->addCSS(url('assets/jscripts/lightbox/lightbox.min.css'));
    $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
    $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
    $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
    $this->core->addJS(url([ADMIN, 'rawat_jalan', 'javascript']), 'footer');
  }

  protected function data_icd($table)
  {
    return new DB_ICD($table);
  }

  //Start WS BPJS
  public function updateWaktuAntreanBPJS($kodebooking, $taskid, $jenisObat = null)
  {
    $waktu = strtotime(date("Y-m-d H:i:s")) * 1000;
    //   "taskid": {
    //     1 (mulai waktu tunggu admisi), 
    //     2 (akhir waktu tunggu admisi/mulai waktu layan admisi), 
    //     3 (akhir waktu layan admisi/mulai waktu tunggu poli), 
    //     4 (akhir waktu tunggu poli/mulai waktu layan poli),  
    //     5 (akhir waktu layan poli/mulai waktu tunggu farmasi), 
    //     6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat), 
    //     7 (akhir waktu obat selesai dibuat),
    //     99 (tidak hadir/batal)
    // },
    if(!empty($jenisObat)){
      $request = array(
        "kodebooking" => $kodebooking,
        "taskid" => $taskid,
        "waktu" => $waktu,
        "jenisresep" => $jenisObat
      );
    }else{
      $request = array(
        "kodebooking" => $kodebooking,
        "taskid" => $taskid,
        "waktu" => $waktu
      );
    }
      
    // $request = '{
    //                 "kodebooking": "' . $kodebooking . '",
    //                 "taskid": ' . $taskid . ',
    //                 "waktu": ' . $waktu . '
    //             }';

    return $request;
  }

  public function tambahAntreanFarmasiBPJS($kodebooking, $no_rawat, $jenisObat)
  {
    $nomorantrean = $this->db('resep_obat')->select(['no_urut' => 'CONVERT(RIGHT(no_resep,4),signed)'])->where('no_rawat', $no_rawat)->limit(1)->oneArray();
    $request = array(
      "kodebooking" => $kodebooking,
      "jenisresep" => $jenisObat,
      "nomorantrean" => $nomorantrean['no_urut'],
      "keterangan" => "-"
    );
    return $request;
  }
  
  public function batalAntreanBPJS($kodebooking, $keterangan)
  {
    $request = array(
      "kodebooking" => $kodebooking,
      "keterangan" => $keterangan
    );
    // $request = '{
    //                 "kodebooking": "' . $kodebooking . '",
    //                 "keterangan": "' . $keterangan . '"
    //             }';

    return $request;
  }

  public function sendDataWSBPJS($path, $data)
  {
    $consid = "31533";
    $secretKey = "2lLD04E61A";
    $user_key = "9089e0d979f93718d2a84e0b16664ef6";

    // Computes the timestamp
    date_default_timezone_set('Asia/Jakarta');
    $tStamp = strval(time() - strtotime('Y/m/d H:i:s'));
    // Computes the signature by hashing the salt with the secret key as the key
    $signature = hash_hmac('sha256', $consid . "&" . $tStamp, $secretKey, true);

    // base64 encode
    $encodedSignature = base64_encode($signature);

    //begin post data
    // API URL

    $url = 'https://apijkn.bpjs-kesehatan.go.id/antreanrs/' . $path;

    $jsonData = json_encode($data);

    $header = array(
      'Content-Type: ' . 'application/json',
      'x-cons-id: ' . $consid,
      'x-timestamp: ' . $tStamp,
      'x-signature: ' . $encodedSignature,
      'user_key: ' . $user_key
    );
    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'sendDataBPJS data json',
    //   'value' => $jsonData
    // ]);
    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'sendDataBPJS consid tstamp signature userkey url',
    //   'value' => $consid . ' | ' . $tStamp . ' | ' . $encodedSignature . ' | ' . $user_key . ' | ' . $url
    // ]);
    //24722 | 1646878330 | bE0Hkkxbf/veONKwXxLO/HKoi0mKtE8uvKvN12B2m+w= | 39625d47ae7fc6c4db8d957ee4958fc5 | https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/antrean/add
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $res = curl_exec($ch);
    //$result = file_get_contents($res);
    $data = json_decode($res, true);
    curl_close($ch);

    return $data;
  }

  //end of WS BPJS

  public function getTokenWSRS()
  {
    // $url = 'http://localhost/webapps/api-bpjsfktl/auth';
    $url = 'https://rssoepraoen.com/webapps/api-bpjsfktl/auth';
    $header = array(
      'Accept: application/json',
      'x-username: bridging_rstds',
      'x-password: RSTSoepraoen0341'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');

    $res = curl_exec($ch);
    // $result = file_get_contents($res);
    $data = json_decode($res, true);
    curl_close($ch);

    return $data['response']['token'];
  }

  public function sendDataWSRS($path, $data)
  {
    $token = $this->getTokenWSRS();
    $username = "bridging_rstds";

    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'sendDataWSRS token RS',
    //   'value' => $token
    // ]);

    //begin post data
    // API URL
    $url = 'https://rssoepraoen.com/webapps/api-bpjsfktl/' . $path;
    // $url = 'http://localhost/webapps/api-bpjsfktl/' . $path;
    // $payload = array(
    //   'test' => 'data'
    // );

    $jsonData = json_encode($data);

    $header = array(
      'Accept: application/json',
      'x-token: ' . $token,
      'x-username: ' . $username
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $res = curl_exec($ch);
    //$result = file_get_contents($res);
    $data = json_decode($res, true);
    curl_close($ch);

    return $data;
  }


  function getDataWSBPJS($url)
  {
    $consid = "31533";
    $secretKey = "2lLD04E61A";
    $user_key = "6129e4009acbd89f089be0aa5350f57d";

    // Computes the timestamp
    date_default_timezone_set('Asia/Jakarta');
    $tStamp = strval(time() - strtotime('Y/m/d H:i:s'));
    // Computes the signature by hashing the salt with the secret key as the key
    $signature = hash_hmac('sha256', $consid . "&" . $tStamp, $secretKey, true);

    // base64 encode
    $encodedSignature = base64_encode($signature);
    $today_date = date('Y-m-d');
    $url = 'https://apijkn.bpjs-kesehatan.go.id/' . $url;
    $header = array(
      'Content-Type: ' . 'application/json',
      'x-cons-id: ' . $consid,
      'x-timestamp: ' . $tStamp,
      'x-signature: ' . $encodedSignature,
      'user_key: ' . $user_key
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');

    $res = curl_exec($ch);
    //$result = file_get_contents($res);
    $obj = json_decode($res, true);

    curl_close($ch);
    if ($obj['metaData']['code'] == '200') {
      $decryptData = $this->Decrypt($consid . $secretKey . $tStamp, $obj['response']);
      $decompressData = $this->decompress($decryptData);
      $data['metaData'] = $obj['metaData'];
      $data['response'] = $decompressData;
    } else {
      $data['metaData'] = $obj['metaData'];
      $data['response'] = '';
    }
    return $data;
  }

  function postWhatsapp($nomor_hp,$nama){
    //The url you wish to send the POST request to
    $url = "http://192.168.10.229:9000/humas/messages/send";
    // return $url;

    if($nomor_hp == '' || $nomor_hp == null || $nomor_hp == '-'){
      return false;
    }

    // check if $nomor_hp start with 0, if yes, replace with 62
    if (substr($nomor_hp, 0, 1) == '0') {
      $nomor_hp = '62'.substr($nomor_hp, 1);
    }
    

    //The data you want to send via POST
    $requested_data = [
        'jid'     => $nomor_hp."@s.whatsapp.net",
        'type'    => 'number',
        'message' => [
          'text' => 
          'SELAMAT DATANG DI RST dr.SOEPRAOEN
Yth.'.$nama.'
Terima kasih atas kepercayaan Anda kepada Rumah Sakit Tk.II dr.Soepraoen untuk memberikan pelayanan kesehatan bagi Anda dan keluarga.
Jika Bapak/Ibu membutuhkan bantuan , Silahkan menghubungi Customer Care RST dr.Soepraoen di:
          Telp : 0341-325111/325112
          WhatsApp  : 0811-3229-9222
Demi peningkatan mutu pelayanan, kami mohon kesediaan Anda untuk memberikan penilaian dan masukan melalui link di bawah ini.
          
https://forms.gle/NkRGZ8Me4EMQ2Nb79
          
Terima kasih'
        ],
    ];

    $jsonData = json_encode($requested_data);

    $header = array(
      'Accept: application/json',
      // form type
      'Content-Type: application/json',
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $res = curl_exec($ch);
    
    $data['response'] = json_decode($res, true);
    $data['requested_data'] = $jsonData;
    curl_close($ch);

    return $data;
  }

  //begin function decrypt
  function Decrypt($key, $string)
  {

    $encrypt_method = 'AES-256-CBC';

    // hash
    $key_hash = hex2bin(hash('sha256', $key));

    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);

    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);

    return $output;
  }

  // function lzstring decompress 
  function decompress($string)
  {
    return \LZCompressor\LZString::decompressFromEncodedURIComponent($string);
  }
}
