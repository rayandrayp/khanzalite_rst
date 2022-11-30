<?php

namespace Plugins\Anjungan;

use Systems\SiteModule;
use Systems\Lib\BpjsService;
use Systems\Lib\QRCode;

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

require_once __DIR__ . '/../../vendor/autoload.php';
class Site extends SiteModule
{

  public function init()
  {
    $this->consid = $this->settings->get('settings.BpjsConsID');
    $this->secretkey = $this->settings->get('settings.BpjsSecretKey');
    $this->user_key = $this->settings->get('settings.BpjsUserKey');
    $this->api_url = $this->settings->get('settings.BpjsApiUrl');
    $this->vclaim_version = $this->settings->get('settings.vClaimVersion');
  }

  public function routes()
  {
    $this->route('anjungan', 'getIndex');
    $this->route('anjungan/pasien', 'getDisplayAPM');
    $this->route('anjungan/loket', 'getDisplayAntrianLoket');
    $this->route('anjungan/poli', 'getDisplayAntrianPoli');
    $this->route('anjungan/poli/(:str)', 'getDisplayAntrianPoliKode');
    $this->route('anjungan/poli/(:str)/(:str)', 'getDisplayAntrianPoliKode');
    $this->route('anjungan/display/poli/(:str)', 'getDisplayAntrianPoliDisplay');
    $this->route('anjungan/display/poli/(:str)/(:str)', 'getDisplayAntrianPoliDisplay');
    $this->route('anjungan/laboratorium', 'getDisplayAntrianLaboratorium');
    $this->route('anjungan/farmasi', 'getDisplayAntrianFarmasi');
    $this->route('anjungan/farmasi-console', 'getDisplayConsoleFarmasi');
    $this->route('anjungan/apotek', 'getDisplayAntrianApotek');
    $this->route('anjungan/ajax', 'getAjax');
    $this->route('anjungan/panggilantrianfarmasi', 'getPanggilAntrianFarmasi');
    $this->route('anjungan/panggilantrian', 'getPanggilAntrian');
    $this->route('anjungan/panggilselesai', 'getPanggilSelesai');
    $this->route('anjungan/setpanggil', 'getSetPanggil');
    $this->route('anjungan/presensi', 'getPresensi');
    $this->route('anjungan/presensi/upload', 'getUpload');
    $this->route('anjungan/bed', 'getDisplayBed');
    $this->route('anjungan/sep', 'getSepMandiri');
    $this->route('anjungan/sep/cek', 'getSepMandiriCek');
    $this->route('anjungan/sep/(:int)/(:int)', 'getSepMandiriNokaNorm');
    $this->route('anjungan/sep/bikin/(:str)/(:int)', 'getSepMandiriBikin');
    $this->route('anjungan/sep/savesep', 'postSaveSep');
    $this->route('anjungan/sep/cetaksep/(:str)', 'getCetakSEP');
  }

  public function getIndex()
  {
    echo $this->draw('index.html', ['test' => 'Opo iki']);
    exit();
  }

  public function getDisplayAPM()
  {
    $title = 'Display Antrian Poliklinik';
    $logo  = $this->settings->get('settings.logo');
    $poliklinik = $this->db('poliklinik')->toArray();
    $penjab = $this->db('penjab')->where('status', '1')->toArray();

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('display.antrian.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_anjungan'),
      'poliklinik' => $poliklinik,
      'penjab' => $penjab
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function getDisplayAntrianPoli()
  {
    $title = 'Display Antrian Poliklinik';
    $logo  = $this->settings->get('settings.logo');
    $display = $this->_resultDisplayAntrianPoli();

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('display.antrian.poli.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_poli'),
      'display' => $display
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function _resultDisplayAntrianPoli()
  {
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

    $poliklinik = str_replace(",", "','", $this->settings->get('anjungan.display_poli'));
    // $strQuery = "SELECT a.kd_dokter, a.kd_poli, b.nm_poli, c.nm_dokter, a.jam_mulai, a.jam_selesai FROM jadwal a, poliklinik b, dokter c WHERE a.kd_poli = b.kd_poli AND a.kd_dokter = c.kd_dokter AND a.hari_kerja = '$hari'  AND a.kd_poli IN ('$poliklinik')";
    $strQuery = "SELECT DISTINCT a.kd_dokter, a.kd_poli, b.nm_poli, c.nm_dokter FROM jadwal a, poliklinik b, dokter c WHERE a.kd_poli = b.kd_poli AND a.kd_dokter = c.kd_dokter AND a.hari_kerja = '$hari'  AND a.kd_poli IN ('$poliklinik') GROUP BY kd_dokter, kd_poli";
    $query = $this->db()->pdo()->prepare($strQuery);
    $query->execute();
    $rows = $query->fetchAll(\PDO::FETCH_ASSOC);

    $result = [];
    if (count($rows)) {
      foreach ($rows as $row) {
        $row['dalam_pemeriksaan'] = $this->db('reg_periksa')
          ->select('no_reg')
          ->select('nm_pasien')
          ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
          ->where('tgl_registrasi', $date)
          ->where('stts', 'Berkas Diterima')
          ->where('kd_poli', $row['kd_poli'])
          ->where('kd_dokter', $row['kd_dokter'])
          ->limit(1)
          ->oneArray();
        $row['dalam_antrian'] = $this->db('reg_periksa')
          ->select(['jumlah' => 'COUNT(DISTINCT reg_periksa.no_rawat)'])
          ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
          ->where('reg_periksa.tgl_registrasi', date('Y-m-d'))
          ->where('reg_periksa.kd_poli', $row['kd_poli'])
          ->where('reg_periksa.kd_dokter', $row['kd_dokter'])
          ->oneArray();
        $row['sudah_dilayani'] = $this->db('reg_periksa')
          ->select(['count' => 'COUNT(DISTINCT reg_periksa.no_rawat)'])
          ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
          ->where('reg_periksa.tgl_registrasi', date('Y-m-d'))
          ->where('reg_periksa.kd_poli', $row['kd_poli'])
          ->where('reg_periksa.kd_dokter', $row['kd_dokter'])
          ->where('reg_periksa.stts', 'Sudah')
          ->oneArray();
        $row['sudah_dilayani']['jumlah'] = 0;
        if (!empty($row['sudah_dilayani'])) {
          $row['sudah_dilayani']['jumlah'] = $row['sudah_dilayani']['count'];
        }
        $row['selanjutnya'] = $this->db('reg_periksa')
          ->select('reg_periksa.no_reg')
          //->select(['no_urut_reg' => 'ifnull(MAX(CONVERT(RIGHT(reg_periksa.no_reg,3),signed)),0)'])
          ->select('pasien.nm_pasien')
          ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
          ->where('reg_periksa.tgl_registrasi', $date)
          ->where('reg_periksa.stts', 'Belum')
          ->where('reg_periksa.kd_poli', $row['kd_poli'])
          ->where('reg_periksa.kd_dokter', $row['kd_dokter'])
          ->asc('reg_periksa.no_reg')
          ->toArray();
        $row['get_no_reg'] = $this->db('reg_periksa')
          ->select(['max' => 'ifnull(MAX(CONVERT(RIGHT(no_reg,3),signed)),0)'])
          ->where('tgl_registrasi', $date)
          ->where('kd_poli', $row['kd_poli'])
          ->where('kd_dokter', $row['kd_dokter'])
          ->oneArray();
        // $row['diff'] = (strtotime($row['jam_selesai']) - strtotime($row['jam_mulai'])) / 60;
        $row['interval'] = 0;
        // if ($row['diff'] == 0) {
        //   $row['interval'] = round($row['diff'] / $row['get_no_reg']['max']);
        // }
        if ($row['interval'] > 10) {
          $interval = 10;
        } else {
          $interval = $row['interval'];
        }
        foreach ($row['selanjutnya'] as $value) {
          // $minutes = $value['no_reg'] * $interval;
          // $row['jam_mulai'] = date('H:i', strtotime('+10 minutes', strtotime($row['jam_mulai'])));
        }

        $result[] = $row;
      }
    }

    return $result;
  }

  public function getDisplayAntrianPoliKode()
  {
    $title = 'Display Antrian Poliklinik';
    $logo  = $this->settings->get('settings.logo');
    $slug = parseURL();
    $vidio = $this->settings->get('anjungan.vidio');
    $_GET['vid'] = '';
    if (isset($_GET['vid']) && $_GET['vid'] != '') {
      $vidio = $_GET['vid'];
    }

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

    $running_text = $this->settings->get('anjungan.text_poli');
    $jadwal = $this->db('jadwal')->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli')->where('hari_kerja', $hari)->toArray();
    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('display.antrian.poli.kode.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'vidio' => $vidio,
      'running_text' => $running_text,
      'jadwal' => $jadwal,
      'slug' => $slug
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function getDisplayAntrianPoliDisplay()
  {
    $title = 'Display Antrian Poliklinik';
    $logo  = $this->settings->get('settings.logo');
    $display = $this->_resultDisplayAntrianPoliKodeDisplay();
    $slug = parseURL();

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('display.antrian.poli.display.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'vidio' => $this->settings->get('anjungan.vidio'),
      'running_text' => $this->settings->get('anjungan.text_poli'),
      'slug' => $slug,
      'display' => $display
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function _resultDisplayAntrianPoliKodeDisplay()
  {
    $slug = parseURL();

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

    $poliklinik = $slug[3];
    $query = $this->db()->pdo()->prepare("SELECT a.kd_dokter, a.kd_poli, b.nm_poli, c.nm_dokter, a.jam_mulai, a.jam_selesai FROM jadwal a, poliklinik b, dokter c WHERE a.kd_poli = b.kd_poli AND a.kd_dokter = c.kd_dokter AND a.hari_kerja = '$hari' AND a.kd_poli = '$poliklinik'");
    if (!isset($slug[4]) && $slug[3] == 'all') {
      $query = $this->db()->pdo()->prepare("SELECT a.kd_dokter, a.kd_poli, b.nm_poli, c.nm_dokter, a.jam_mulai, a.jam_selesai FROM jadwal a, poliklinik b, dokter c WHERE a.kd_poli = b.kd_poli AND a.kd_dokter = c.kd_dokter AND a.hari_kerja = '$hari'  ORDER BY nm_poli");
    }
    if (isset($slug[4]) && $slug[4] != '') {
      $dokter = $slug[4];
      $query = $this->db()->pdo()->prepare("SELECT a.kd_dokter, a.kd_poli, b.nm_poli, c.nm_dokter, a.jam_mulai, a.jam_selesai FROM jadwal a, poliklinik b, dokter c WHERE a.kd_poli = b.kd_poli AND a.kd_dokter = c.kd_dokter AND a.hari_kerja = '$hari' AND a.kd_poli = '$poliklinik' AND a.kd_dokter = '$dokter'");
    }
    $query->execute();
    $rows = $query->fetchAll(\PDO::FETCH_ASSOC);;

    $result = [];
    if (count($rows)) {
      foreach ($rows as $row) {
        $row['dalam_pemeriksaan'] = $this->db('reg_periksa')
          ->select('no_reg')
          ->select('nm_pasien')
          ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
          ->where('tgl_registrasi', $date)
          ->where('stts', 'Berkas Diterima')
          ->where('kd_poli', $row['kd_poli'])
          ->where('kd_dokter', $row['kd_dokter'])
          ->limit(1)
          ->oneArray();
        $row['dalam_antrian'] = $this->db('reg_periksa')
          ->select(['jumlah' => 'COUNT(DISTINCT reg_periksa.no_rawat)'])
          ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
          ->where('reg_periksa.tgl_registrasi', date('Y-m-d'))
          ->where('reg_periksa.kd_poli', $row['kd_poli'])
          ->where('reg_periksa.kd_dokter', $row['kd_dokter'])
          ->oneArray();
        $row['sudah_dilayani'] = $this->db('reg_periksa')
          ->select(['count' => 'COUNT(DISTINCT reg_periksa.no_rawat)'])
          ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
          ->where('reg_periksa.tgl_registrasi', date('Y-m-d'))
          ->where('reg_periksa.kd_poli', $row['kd_poli'])
          ->where('reg_periksa.kd_dokter', $row['kd_dokter'])
          ->where('reg_periksa.stts', 'Sudah')
          ->oneArray();
        $row['sudah_dilayani']['jumlah'] = 0;
        if (!empty($row['sudah_dilayani'])) {
          $row['sudah_dilayani']['jumlah'] = $row['sudah_dilayani']['count'];
        }
        $row['selanjutnya'] = $this->db('reg_periksa')
          ->select('reg_periksa.no_reg')
          //->select(['no_urut_reg' => 'ifnull(MAX(CONVERT(RIGHT(reg_periksa.no_reg,3),signed)),0)'])
          ->select('pasien.nm_pasien')
          ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
          ->where('reg_periksa.tgl_registrasi', $date)
          ->where('reg_periksa.stts', 'Belum')
          ->where('reg_periksa.kd_poli', $row['kd_poli'])
          ->where('reg_periksa.kd_dokter', $row['kd_dokter'])
          ->asc('reg_periksa.no_reg')
          ->toArray();
        $row['get_no_reg'] = $this->db('reg_periksa')
          ->select(['max' => 'ifnull(MAX(CONVERT(RIGHT(no_reg,3),signed)),0)'])
          ->where('tgl_registrasi', $date)
          ->where('kd_poli', $row['kd_poli'])
          ->where('kd_dokter', $row['kd_dokter'])
          ->oneArray();
        $row['diff'] = (strtotime($row['jam_selesai']) - strtotime($row['jam_mulai'])) / 60;
        $row['interval'] = 0;
        if ($row['diff'] == 0) {
          $row['interval'] = round($row['diff'] / $row['get_no_reg']['max']);
        }
        if ($row['interval'] > 10) {
          $interval = 10;
        } else {
          $interval = $row['interval'];
        }
        foreach ($row['selanjutnya'] as $value) {
          //$minutes = $value['no_reg'] * $interval;
          //$row['jam_mulai'] = date('H:i',strtotime('+10 minutes',strtotime($row['jam_mulai'])));
        }

        $result[] = $row;
      }
    }

    return $result;
  }

  public function getDisplayAntrianLoket()
  {
    $title = 'Display Antrian Loket';
    $logo  = $this->settings->get('settings.logo');
    $display = '';

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');
    $loket = explode(",", $this->settings->get('anjungan.antrian_loket'));
    $data_loket = array();

    foreach ($loket as $l) {
      $loket_antrian_query = $this->db('mlite_antrian_loket')->select('noantrian')->where('loket', $l)->where('postdate', date('Y-m-d'))->oneArray();
      if (!empty($loket_antrian_query['noantrian'])) {
        $loket_antrian = $loket_antrian_query['noantrian'];
      } else {
        $loket_antrian = '-';
      }
      $data_loket[] = array('loket' => $l, 'noantrian' => $loket_antrian);
    }

    $show = isset($_GET['show']) ? $_GET['show'] : "";
    switch ($show) {
      default:
        $display = 'Depan';
        $content = $this->draw('display.antrian.loket.html', [
          'title' => $title,
          'logo' => $logo,
          'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
          'username' => $username,
          'tanggal' => $tanggal,
          'show' => $show,
          'loket' => $data_loket,
          'vidio' => $this->settings->get('anjungan.vidio'),
          'running_text' => $this->settings->get('anjungan.text_loket'),
          'display' => $display
        ]);
        break;

      case "panggil_loket":
        $display = 'Panggil Loket';

        $_username = $this->core->getUserInfo('fullname', null, true);
        $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
        $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

        $setting_antrian_loket = str_replace(",", "','", $this->settings->get('anjungan.antrian_loket'));
        $get_antrian = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
        $noantrian = 0;
        if (!empty($get_antrian['noantrian'])) {
          $noantrian = $get_antrian['noantrian'];
        } else {
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => 1]);
        }
        //$antriloket = $this->db('antriloket')->oneArray();
        //$tcounter = $antriloket['antrian'];
        $antriloket = $this->settings->get('anjungan.panggil_loket_nomor');
        $tcounter = $antriloket;
        $_tcounter = 1;
        if (!empty($tcounter)) {
          $_tcounter = $tcounter + 1;
        }
        if (isset($_GET['loket'])) {
          //update current nomor menjadi 3 (sudah selesai dilayani)
          $curr_loket = $_GET['loket'];
          $this->db('mlite_antrian_loket')
            ->where('type', 'Loket')
            ->where('noantrian', $tcounter)
            ->where('postdate', date('Y-m-d'))
            ->save([
              'end_time' => date('H:i:s'),
              'loket' => $curr_loket,
              'status' => 3
            ]);
          /*$this->db()->pdo()->exec("DELETE FROM `antriloket`");
              $this->db('antriloket')->save([
                'loket' => $_GET['loket'],
                'antrian' => $_tcounter
              ]);*/

          $nextNomor = $this->db('mlite_antrian_loket')->select('status')->where('type', 'Loket')->where('noantrian', $tcounter + 1)->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
          if ($nextNomor) {
            //update next nomor menjadi 1 (sedang dilayani)
            $this->db('mlite_antrian_loket')
              ->where('type', 'Loket')
              ->where('noantrian', $tcounter + 1)
              ->where('postdate', date('Y-m-d'))
              ->save([
                'loket' => $curr_loket,
                'status' => 1
              ]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket')->save(['value' => $_GET['loket']]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => $_tcounter]);
          }
          redirect(url('anjungan/loket?show=panggil_loket&no_loket=' . $curr_loket));
        }
        if (isset($_GET['antrian'])) {
          /*$this->db()->pdo()->exec("DELETE FROM `antriloket`");
              $this->db('antriloket')->save([
                'loket' => $_GET['reset'],
                'antrian' => $_GET['antrian']
              ]);*/
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket')->save(['value' => $_GET['reset']]);
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => $_GET['antrian']]);
          redirect(url('anjungan/loket?show=panggil_loket&no_loket=' . $_GET['no_loket']));
        }
        if (isset($_GET['no_loket'])) {
          $no_loket = $_GET['no_loket'];
        } else {
          $no_loket = $_GET['loket'];
        }
        $hitung_antrian = $this->db('mlite_antrian_loket')
          ->where('type', 'Loket')
          ->like('postdate', date('Y-m-d'))
          ->toArray();
        $counter = strlen($tcounter);
        $xcounter = [];
        for ($i = 0; $i < $counter; $i++) {
          $xcounter[] = '<audio id="suarabel' . $i . '" src="{?=url()?}/plugins/anjungan/suara/' . substr($tcounter, $i, 1) . '.wav" ></audio>';
        };

        $content = $this->draw('display.antrian.loket.html', [
          'title' => $title,
          'logo' => $logo,
          'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
          'username' => $username,
          'tanggal' => $tanggal,
          'show' => $show,
          'loket' => $loket,
          'no_loket' => $no_loket,
          'namaloket' => 'a',
          'panggil_loket' => 'panggil_loket',
          'antrian' => $tcounter,
          'hitung_antrian' => $hitung_antrian,
          'xcounter' => $xcounter,
          'noantrian' => $noantrian,
          'display' => $display
        ]);
        break;

      case "panggil_cs":
        $display = 'Panggil CS';
        $loket = explode(",", $this->settings->get('anjungan.antrian_cs'));
        $get_antrian = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'CS')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
        $noantrian = 0;
        if (!empty($get_antrian['noantrian'])) {
          $noantrian = $get_antrian['noantrian'];
        }

        //$antriloket = $this->db('antrics')->oneArray();
        //$tcounter = $antriloket['antrian'];
        $antriloket = $this->settings->get('anjungan.panggil_cs_nomor');
        $tcounter = $antriloket;
        $_tcounter = 1;
        if (!empty($tcounter)) {
          $_tcounter = $tcounter + 1;
        }
        if (isset($_GET['loket'])) {
          $this->db('mlite_antrian_loket')
            ->where('type', 'CS')
            ->where('noantrian', $tcounter)
            ->where('postdate', date('Y-m-d'))
            ->save(['end_time' => date('H:i:s')]);
          /*$this->db()->pdo()->exec("DELETE FROM `antrics`");
              $this->db('antrics')->save([
                'loket' => $_GET['loket'],
                'antrian' => $_tcounter
              ]);*/
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs')->save(['value' => $_GET['loket']]);
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs_nomor')->save(['value' => $_tcounter]);
        }
        if (isset($_GET['antrian'])) {
          /*$this->db()->pdo()->exec("DELETE FROM `antrics`");
              $this->db('antrics')->save([
                'loket' => $_GET['reset'],
                'antrian' => $_GET['antrian']
              ]);*/
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs')->save(['value' => $_GET['reset']]);
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs_nomor')->save(['value' => $_GET['antrian']]);
        }
        $hitung_antrian = $this->db('mlite_antrian_loket')
          ->where('type', 'CS')
          ->like('postdate', date('Y-m-d'))
          ->toArray();
        $counter = strlen($tcounter);
        $xcounter = [];
        for ($i = 0; $i < $counter; $i++) {
          $xcounter[] = '<audio id="suarabel' . $i . '" src="{?=url()?}/plugins/anjungan/suara/' . substr($tcounter, $i, 1) . '.wav" ></audio>';
        };

        $content = $this->draw('display.antrian.loket.html', [
          'title' => $title,
          'logo' => $logo,
          'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
          'username' => $username,
          'tanggal' => $tanggal,
          'show' => $show,
          'loket' => $loket,
          'namaloket' => 'b',
          'panggil_loket' => 'panggil_cs',
          'antrian' => $tcounter,
          'hitung_antrian' => $hitung_antrian,
          'xcounter' => $xcounter,
          'noantrian' => $noantrian,
          'display' => $display
        ]);
        break;
    }

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);

    //exit();
  }

  public function getDisplayAntrianLaboratorium()
  {
    $logo  = $this->settings->get('settings.logo');
    $title = 'Display Antrian Laboratorium';
    $display = $this->_resultDisplayAntrianLaboratorium();

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('display.antrian.laboratorium.html', [
      'logo' => $logo,
      'title' => $title,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_laboratorium'),
      'display' => $display
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);

    //exit();
  }

  public function _resultDisplayAntrianLaboratorium()
  {
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

    $poliklinik = $this->settings('settings', 'laboratorium');
    $rows = $this->db('reg_periksa')
      ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
      ->where('tgl_registrasi', date('Y-m-d'))
      ->where('kd_poli', $poliklinik)
      ->asc('no_reg')
      ->toArray();

    return $rows;
  }
  public function getDisplayConsoleFarmasi()
  {
    $title = 'Display Antrian Poliklinik';
    $logo  = $this->settings->get('settings.logo');
    $poliklinik = $this->db('poliklinik')->toArray();
    $penjab = $this->db('penjab')->where('status', '1')->toArray();

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('display.antrian.farmasi.console.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_anjungan'),
      'poliklinik' => $poliklinik,
      'penjab' => $penjab
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function getDisplayAntrianFarmasi()
  {
    $title = 'Display Antrian Farmasi';
    $logo  = $this->settings->get('settings.logo');
    $display = '';

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $show = isset($_GET['show']) ? $_GET['show'] : "";
    switch ($show) {
      default:
        $display = 'Depan';
        $content = $this->draw('display.antrian.farmasi.html', [
          'title' => $title,
          'logo' => $logo,
          'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
          'username' => $username,
          'tanggal' => $tanggal,
          'show' => $show,
          'vidio' => $this->settings->get('anjungan.vidio'),
          'running_text' => $this->settings->get('anjungan.text_loket'),
          'display' => $display
        ]);
        break;

      case "panggil_obat":
        $display = 'Panggil Obat';

        $_username = $this->core->getUserInfo('fullname', null, true);
        $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
        $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

        $setting_antrian_obat = str_replace(",", "','", $this->settings->get('anjungan.antrian_obat'));
        $get_antrian = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Obat%')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
        $noantrian = 0;

        if (!empty($get_antrian['noantrian'])) {
          $noantrian = $get_antrian['noantrian'];
        } else {
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_obat_nomor')->save(['value' => 1]);
        }
        //$antriloket = $this->db('antriloket')->oneArray();
        //$tcounter = $antriloket['antrian'];
        $antriloket = $this->settings->get('anjungan.panggil_obat_nomor');
        $tcounter = $antriloket;
        $_tcounter = 1;
        if (!empty($tcounter)) {
          $_tcounter = $tcounter + 1;
        }
        if (isset($_GET['loket'])) {
          $curr_loket = $_GET['loket'];
          if (isset($_GET['batal'])) { //batal
            $kdbooking = $_GET['batal'];
            $this->db('mlite_antrian_loket')
              ->where('type', 'LIKE', 'Obat%')
              ->where('noantrian', $tcounter)
              ->where('postdate', date('Y-m-d'))
              ->save([
                'end_time' => date('H:i:s'),
                'loket' => $curr_loket,
                'status' => 99
              ]);
            $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kdbooking, 99);
            $dataBatalAntrean = $this->batalAntreanBPJS($kdbooking, 'Pasien tidak hadir.');
            $response1 = $this->sendDataWSBPJS('antrean/batal', $dataBatalAntrean);
            $response2 = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
            if ($response1['metadata']['code'] != '200') {
              $this->db('mlite_settings')->save([
                'module' => 'debug',
                'field' => 'farmasi obat Batal1',
                'value' => $kdbooking . '|' . $response1['metadata']['code'] . '|' . $response1['metadata']['message']
              ]);
            }
            if ($response2['metadata']['code'] != '200') {
              $this->db('mlite_settings')->save([
                'module' => 'debug',
                'field' => 'farmasi obat Batal2',
                'value' => $kdbooking . '|' . $response2['metadata']['code'] . '|' . $response2['metadata']['message']
              ]);
            }
          } elseif (isset($_GET['lewati'])) { //lewati
            $this->db('mlite_antrian_loket')
              ->where('type', 'LIKE', 'Obat%')
              ->where('noantrian', $_GET['lewati'])
              ->where('postdate', date('Y-m-d'))
              ->save([
                'loket' => $curr_loket,
                'status' => 4
              ]);
          } else { //selesai
            //update current nomor menjadi 3 (sudah selesai dilayani)
            $this->db('mlite_antrian_loket')
              ->where('type', 'LIKE', 'Obat%')
              ->where('noantrian', $tcounter)
              ->where('postdate', date('Y-m-d'))
              ->save([
                'end_time' => date('H:i:s'),
                'loket' => $curr_loket,
                'status' => 3
              ]);
            $kdbooking = $_GET['kdbooking'];
            $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kdbooking, 7);
            $response = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
            if ($response['metadata']['code'] != '200') {
              $this->db('mlite_settings')->save([
                'module' => 'debug',
                'field' => 'farmasi obat 7',
                'value' => $kdbooking . '|' . $response['metadata']['code'] . '|' . $response['metadata']['message']
              ]);
            }
            /*$this->db()->pdo()->exec("DELETE FROM `antriloket`");
              $this->db('antriloket')->save([
                'loket' => $_GET['loket'],
                'antrian' => $_tcounter
              ]);*/
          }
          $get_antrian_akhir = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Obat%')->where('status', 0)->where('postdate', date('Y-m-d'))->asc('start_time')->oneArray();
          // $nextNomor = $this->db('mlite_antrian_loket')->select('status')->where('type', 'LIKE', 'Obat%')->where('noantrian', $tcounter + 1)->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
          $nextNomor = $get_antrian_akhir['noantrian'];
          if ($nextNomor) {
            //update next nomor menjadi 1 (sedang dilayani)
            $this->db('mlite_antrian_loket')
              ->where('type', 'LIKE', 'Obat%')
              ->where('noantrian', $nextNomor)
              ->where('postdate', date('Y-m-d'))
              ->save([
                'loket' => $curr_loket
              ]);
            //,'status' => 1
            // remarked karena saat selesai tidak otomatis memanggil nomor antrian berikutnya 

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_obat')->save(['value' => $_GET['loket']]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_obat_nomor')->save(['value' => $nextNomor]);
          }


          redirect(url('anjungan/farmasi?show=panggil_obat'));
        }

        if (isset($_GET['antrian'])) {
          /*$this->db()->pdo()->exec("DELETE FROM `antriloket`");
              $this->db('antriloket')->save([
                'loket' => $_GET['reset'],
                'antrian' => $_GET['antrian']
              ]);*/
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_obat')->save(['value' => $_GET['reset']]);
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_obat_nomor')->save(['value' => $_GET['antrian']]);
          redirect(url('anjungan/farmasi?show=panggil_obat'));
        }

        $hitung_antrian = $this->db('mlite_antrian_loket')
          ->where('type', 'LIKE', 'Obat%')
          ->like('postdate', date('Y-m-d'))
          ->toArray();
        $counter = strlen($tcounter);
        $xcounter = [];
        for ($i = 0; $i < $counter; $i++) {
          $xcounter[] = '<audio id="suarabel' . $i . '" src="{?=url()?}/plugins/anjungan/suara/' . substr($tcounter, $i, 1) . '.wav" ></audio>';
        };

        //get kode booking
        $get_kdbooking = $this->db('mlite_antrian_loket')->select('type')->where('type', 'LIKE', 'Obat%')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
        if ($get_kdbooking) {
          $kodebookingArr = explode('#', $get_kdbooking['type']);
          $kodebooking = $kodebookingArr[1];
        } else {
          $kodebooking = "-";
        }

        //get antrian terlewati
        $antrianTerlewati = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Obat%')->where('status', 4)->where('postdate', date('Y-m-d'))->desc('start_time')->toArray();
        if (!$antrianTerlewati) {
          $antrianTerlewati = ['0' => array("noantrian" => "-")];
        }

        $content = $this->draw('display.antrian.farmasi.html', [
          'title' => $title,
          'logo' => $logo,
          'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
          'username' => $username,
          'tanggal' => $tanggal,
          'show' => $show,
          'namaloket' => 'Obat',
          'kodeloket' => 'A',
          'panggil_loket' => 'panggil_obat',
          'antrian' => $tcounter,
          'hitung_antrian' => $hitung_antrian,
          'xcounter' => $xcounter,
          'noantrian' => $noantrian,
          'kodebooking' => $kodebooking,
          'antrianterlewati' => $antrianTerlewati,
          'display' => $display
        ]);
        break;

      case "panggil_racikan":
        $display = 'Panggil Racikan';
        $loket = explode(",", $this->settings->get('anjungan.antrian_racikan'));
        $get_antrian = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Racikan%')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
        $noantrian = 0;
        if (!empty($get_antrian['noantrian'])) {
          $noantrian = $get_antrian['noantrian'];
        }

        //$antriloket = $this->db('antrics')->oneArray();
        //$tcounter = $antriloket['antrian'];
        $antriloket = $this->settings->get('anjungan.panggil_racikan_nomor');
        $tcounter = $antriloket;
        $_tcounter = 1;
        if (!empty($tcounter)) {
          $_tcounter = $tcounter + 1;
        }
        if (isset($_GET['loket'])) {
          $curr_loket = $_GET['loket'];
          if (isset($_GET['batal'])) { //batal
            $kdbooking = $_GET['batal'];
            $this->db('mlite_antrian_loket')
              ->where('type', 'LIKE', 'Racikan%')
              ->where('noantrian', $tcounter)
              ->where('postdate', date('Y-m-d'))
              ->save([
                'end_time' => date('H:i:s'),
                'loket' => $curr_loket,
                'status' => 99
              ]);
            $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kdbooking, 99);
            $dataBatalAntrean = $this->batalAntreanBPJS($kdbooking, 'Pasien tidak hadir.');
            $response1 = $this->sendDataWSBPJS('antrean/batal', $dataBatalAntrean);
            $response2 = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
            if ($response1['metadata']['code'] != '200') {
              $this->db('mlite_settings')->save([
                'module' => 'debug',
                'field' => 'farmasi obat Batal1',
                'value' => $kdbooking . '|' . $response1['metadata']['code'] . '|' . $response1['metadata']['message']
              ]);
            }
            if ($response2['metadata']['code'] != '200') {
              $this->db('mlite_settings')->save([
                'module' => 'debug',
                'field' => 'farmasi obat Batal2',
                'value' => $kdbooking . '|' . $response2['metadata']['code'] . '|' . $response2['metadata']['message']
              ]);
            }
          } elseif (isset($_GET['lewati'])) { //lewati
            $this->db('mlite_antrian_loket')
              ->where('type', 'LIKE', 'Racikan%')
              ->where('noantrian', $_GET['lewati'])
              ->where('postdate', date('Y-m-d'))
              ->save([
                'loket' => $curr_loket,
                'status' => 4
              ]);
          } else { //selesai
            $this->db('mlite_antrian_loket')
              ->where('type', 'LIKE', 'Racikan%')
              ->where('noantrian', $tcounter)
              ->where('postdate', date('Y-m-d'))
              ->save([
                'end_time' => date('H:i:s'),
                'loket' => $curr_loket,
                'status' => 3
              ]);
            $kdbooking = $_GET['kdbooking'];
            $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kdbooking, 7);
            $response = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
            if ($response['metadata']['code'] != '200') {
              $this->db('mlite_settings')->save([
                'module' => 'debug',
                'field' => 'farmasi racikan 7',
                'value' => $kdbooking . '|' . $response['metadata']['code'] . '|' . $response['metadata']['message']
              ]);
            }
          }
          $get_antrian_akhir = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Racikan%')->where('status', 0)->where('postdate', date('Y-m-d'))->asc('start_time')->oneArray();
          // $nextNomor = $this->db('mlite_antrian_loket')->select('status')->where('type', 'LIKE', 'Racikan%')->where('noantrian', $tcounter + 1)->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
          $nextNomor = $get_antrian_akhir['noantrian'];
          if ($nextNomor) {
            //update next nomor menjadi 1 (sedang dilayani)
            $this->db('mlite_antrian_loket')
              ->where('type', 'LIKE', 'Racikan%')
              ->where('noantrian', $nextNomor)
              ->where('postdate', date('Y-m-d'))
              ->save([
                'loket' => $curr_loket
              ]);
            //,'status' => 1
            // remarked karena saat selesai tidak otomatis memanggil nomor antrian berikutnya 

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_racikan')->save(['value' => $_GET['loket']]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_racikan_nomor')->save(['value' => $nextNomor]);
          }

          redirect(url('anjungan/farmasi?show=panggil_racikan'));
        }
        if (isset($_GET['antrian'])) {
          /*$this->db()->pdo()->exec("DELETE FROM `antrics`");
              $this->db('antrics')->save([
                'loket' => $_GET['reset'],
                'antrian' => $_GET['antrian']
              ]);*/
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_racikan')->save(['value' => $_GET['reset']]);
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_racikan_nomor')->save(['value' => $_GET['antrian']]);
          redirect(url('anjungan/farmasi?show=panggil_racikan'));
        }
        $hitung_antrian = $this->db('mlite_antrian_loket')
          ->where('type', 'LIKE', 'Racikan%')
          ->like('postdate', date('Y-m-d'))
          ->toArray();
        $counter = strlen($tcounter);
        $xcounter = [];
        for ($i = 0; $i < $counter; $i++) {
          $xcounter[] = '<audio id="suarabel' . $i . '" src="{?=url()?}/plugins/anjungan/suara/' . substr($tcounter, $i, 1) . '.wav" ></audio>';
        };

        //get kode booking
        $get_kdbooking = $this->db('mlite_antrian_loket')->select('type')->where('type', 'LIKE', 'Racikan%')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
        if ($get_kdbooking) {
          $kodebookingArr = explode('#', $get_kdbooking['type']);
          $kodebooking = $kodebookingArr[1];
        } else {
          $kodebooking = "-";
        }
        //get antrian terlewati
        $antrianTerlewati = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Racikan%')->where('status', 4)->where('postdate', date('Y-m-d'))->desc('start_time')->toArray();
        if (!$antrianTerlewati) {
          $antrianTerlewati = ['0' => array("noantrian" => "-")];
        }

        $content = $this->draw('display.antrian.farmasi.html', [
          'title' => $title,
          'logo' => $logo,
          'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
          'username' => $username,
          'tanggal' => $tanggal,
          'show' => $show,
          'namaloket' => 'Racikan',
          'kodeloket' => 'B',
          'panggil_loket' => 'panggil_racikan',
          'antrian' => $tcounter,
          'hitung_antrian' => $hitung_antrian,
          'xcounter' => $xcounter,
          'noantrian' => $noantrian,
          'kodebooking' => $kodebooking,
          'antrianterlewati' => $antrianTerlewati,
          'display' => $display
        ]);
        break;
    }

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);

    //exit();
  }

  public function getDisplayAntrianApotek()
  {
    $logo  = $this->settings->get('settings.logo');
    $title = 'Display Antrian Laboratorium';
    $display = $this->_resultDisplayAntrianApotek();

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

    $jadwal = $this->db('jadwal')->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli')->where('hari_kerja', $hari)->toArray();

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('display.antrian.apotek.html', [
      'logo' => $logo,
      'title' => $title,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $jadwal,
      'display' => $display
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function _resultDisplayAntrianApotek()
  {
    $query = $this->db('reg_periksa')
      ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
      ->join('resep_obat', 'resep_obat.no_rawat=reg_periksa.no_rawat')
      ->where('tgl_registrasi', date('Y-m-d'))
      ->where('stts', 'Sudah')
      ->asc('resep_obat.jam_peresepan')
      ->toArray();
    $rows = [];
    foreach ($query as $row) {
      $row['status_resep'] = 'Sudah';
      if ($row['jam'] == $row['jam_peresepan']) {
        $row['status_resep'] = 'Belum';
      }
      $rows[] = $row;
    }

    return $rows;
  }

  public function getPanggilAntrian()
  {
    $res = [];

    $date = date('Y-m-d');
    $sql = $this->db()->pdo()->prepare("SELECT * FROM mlite_antrian_loket WHERE type in ('Loket', 'CS') AND  status = 1 AND postdate = '$date' ORDER BY CAST(noantrian as int) ASC");
    $sql->execute();
    $data = $sql->fetchAll(\PDO::FETCH_OBJ);
    if ($data) {
      //$data  = $query->fetch_object();
      $type = explode('#', $data[0]->type);
      //print_r($data);
      // code...
      switch (strtolower($type[0])) {
        case 'loket':
          $kode = 'a';
          break;
        case 'cs':
          $kode = 'b';
          break;
        case 'obat':
          $kode = 'a';
          break;
        case 'racikan':
          $kode = 'b';
          break;
        default:
          $kode = 'ahhay';
          break;
      }

      //$terbilang = Terbilang::convert($data->noantrian);
      $terbilang = strtolower(terbilang($data[0]->noantrian));
      // $loket = strtolower(terbilang($data[0]->loket));

      switch (strtolower($data[0]->loket)) {
        case 'obat':
          $loket = '1';
          break;
        case 'racikan':
          $loket = '2';
          break;
        default:
          $loket = $data[0]->loket;
          break;
      }
      $text = "antrian $kode $terbilang counter $loket";
      $res = [
        'status' => true,
        'data_loket' => $data,
        'panggil' => explode(" ", $text)
      ];
      // $res = [
      //   'id' => $data[0]->kd,
      //   'status' => true,
      //   'type' => $data[0]->type,
      //   'kode' => $kode,
      //   'noantrian' => $data[0]->noantrian,
      //   'loket' => $data[0]->loket,
      //   'no_loket' => $data[0]->loket,
      //   'panggil' => explode(" ", $text)
      // ];
    } else {
      $res = [
        'status' => false
      ];
    }

    die(json_encode($res));

    exit();
  }
  public function getPanggilAntrianFarmasi()
  {
    $res = [];

    $date = date('Y-m-d');
    $sql = $this->db()->pdo()->prepare("SELECT * FROM mlite_antrian_loket WHERE type not in ('Loket', 'CS') AND status = 1 AND postdate = '$date' ORDER BY CAST(noantrian as int) ASC");
    $sql->execute();
    $data = $sql->fetchAll(\PDO::FETCH_OBJ);
    if ($data) {
      //$data  = $query->fetch_object();
      $type = explode('#', $data[0]->type);
      //print_r($data);
      // code...
      switch (strtolower($type[0])) {
        case 'obat':
          $kode = 'a';
          break;
        case 'racikan':
          $kode = 'b';
          break;
        default:
          $kode = 'ahhay';
          break;
      }

      //$terbilang = Terbilang::convert($data->noantrian);
      $terbilang = strtolower(terbilang($data[0]->noantrian));
      // $loket = strtolower(terbilang($data[0]->loket));

      switch (strtolower($data[0]->loket)) {
        case 'obat':
          $loket = '1';
          break;
        case 'racikan':
          $loket = '2';
          break;
        default:
          $loket = $data[0]->loket;
          break;
      }
      $text = "antrian $kode $terbilang counter $loket";
      $res = [
        'status' => true,
        'data_loket' => $data,
        'panggil' => explode(" ", $text)
      ];
      // $res = [
      //   'id' => $data[0]->kd,
      //   'status' => true,
      //   'type' => $data[0]->type,
      //   'kode' => $kode,
      //   'noantrian' => $data[0]->noantrian,
      //   'loket' => $data[0]->loket,
      //   'no_loket' => $data[0]->loket,
      //   'panggil' => explode(" ", $text)
      // ];
    } else {
      $res = [
        'status' => false
      ];
    }

    die(json_encode($res));

    exit();
  }
  public function getPanggilSelesai()
  {
    if (!isset($_GET['id']) || $_GET['id'] == '') die(json_encode(array('status' => false)));
    $kode  = $_GET['id'];
    $query = $this->db('mlite_antrian_loket')->where('kd', $kode)->update('status', 2);
    if ($query) {
      $res = [
        'status' => true,
        'message' => 'Berhasil update',
      ];
    } else {
      $res = [
        'status' => false,
        'message' => 'Gagal update',
      ];
    }

    die(json_encode($res));
    exit();
  }

  public function getSetPanggil()
  {
    if (!isset($_GET['type']) || $_GET['type'] == '') die(json_encode(array('status' => false, 'message' => 'Gagal Type')));
    $type = 'CS';
    if ($_GET['type'] == 'loket') {
      $type = 'Loket';
    } else {
      $type = $_GET['type'];
    }
    $noantrian  = $_GET['noantrian'];
    $loket  = $_GET['loket'];
    $date = date('Y-m-d');
    $query = $this->db('mlite_antrian_loket')->where('type', 'LIKE',  $type . '%')->where('noantrian', $noantrian)->where('postdate', $date)->update(['status' => 1]);
    if ($query) {
      $res = [
        'status' => true,
        'message' => 'Berhasil update ' . $type . ' loket ' . $loket,
      ];
    } else {
      $res = [
        'status' => false,
        'message' => 'Gagal update',
      ];
    }

    die(json_encode($res));
    exit();
  }

  public function getAjax()
  {
    $show = isset($_GET['show']) ? $_GET['show'] : "";
    switch ($show) {
      default:
        break;

      case "tampilloket":
        $result = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();

        if ($result) {
          $noantrian = $result['noantrian'];
        } else {
          $noantrian = 0;
        }

        if ($noantrian > 0) {
          $next_antrian = $noantrian + 1;
        } else {
          $next_antrian = 1;
        }

        echo '<div id="nomernya" align="center">';
        echo '<h1 class="display-1">';
        echo 'A' . $next_antrian;
        echo '</h1>';
        echo '[' . date('Y-m-d') . ']';
        echo '</div>';
        echo '<br>';
        break;

      case "printloket":
        $result = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();

        if ($result) {
          $noantrian = $result['noantrian'];
        } else {
          $noantrian = 0;
        }

        if ($noantrian > 0) {
          $next_antrian = $noantrian + 1;
        } else {
          $next_antrian = 1;
        }
        echo '<div id="nomernya" align="center">';
        echo '<h1 class="display-1">';
        echo 'A' . $next_antrian;
        echo '</h1>';
        echo '[' . date('Y-m-d') . ']';
        echo '</div>';
        echo '<br>';
?>
        <script>
          $(document).ready(function() {
            $("#btnKRM").on('click', function() {
              $("#formloket").submit(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $.ajax({
                  url: "<?php echo url() . '/anjungan/ajax?show=simpanloket&noantrian=' . $next_antrian; ?>",
                  type: "POST",
                  data: $(this).serialize(),
                  success: function(data) {
                    setTimeout('$("#loading").hide()', 1000);
                    //window.location.href = "{?=url('anjungan/pasien')?}";
                  }
                });
                return false;
              });
            });
          })
        </script>

      <?php
        break;

      case "simpanloket":
        $this->core->db()->pdo()->exec("INSERT INTO `mlite_antrian_loket` (type, noantrian, postdate, start_time, end_time, loket) 
          VALUES ('Loket', '" . $_GET['noantrian'] . "','" . date('Y-m-d') . "','" . date('H:i:s') . "','00:00:00','Admisi')
        ");
        // $this->db('mlite_antrian_loket')
        //   ->save([
        //     'kd' => NULL,
        //     'type' => 'Loket',
        //     'noantrian' => $_GET['noantrian'],
        //     'postdate' => date('Y-m-d'),
        //     'start_time' => date('H:i:s'),
        //     'end_time' => '00:00:00'
        //   ]);
        //redirect(url('anjungan/pasien'));
        break;

      case "tampilcs":
        $result = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'CS')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
        if ($result) {
          $noantrian = $result['noantrian'];
        } else {
          $noantrian = 0;
        }
        if ($noantrian > 0) {
          $next_antrian = $noantrian + 1;
        } else {
          $next_antrian = 1;
        }
        echo '<div id="nomernya" align="center">';
        echo '<h1 class="display-1">';
        echo 'B' . $next_antrian;
        echo '</h1>';
        echo '[' . date('Y-m-d') . ']';
        echo '</div>';
        echo '<br>';
        break;

      case "printcs":
        $result = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'CS')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();

        if ($result) {
          $noantrian = $result['noantrian'];
        } else {
          $noantrian = 0;
        }

        if ($noantrian > 0) {
          $next_antrian = $noantrian + 1;
        } else {
          $next_antrian = 1;
        }
        echo '<div id="nomernya" align="center">';
        echo '<h1 class="display-1">';
        echo 'B' . $next_antrian;
        echo '</h1>';
        echo '[' . date('Y-m-d') . ']';
        echo '</div>';
        echo '<br>';
      ?>
        <script>
          $(document).ready(function() {
            $("#btnKRMCS").on('click', function() {
              $("#formcs").submit(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $.ajax({
                  url: "<?php echo url() . '/anjungan/ajax?show=simpancs&noantrian=' . $next_antrian; ?>",
                  type: "POST",
                  data: $(this).serialize(),
                  success: function(data) {
                    setTimeout('$("#loading").hide()', 1000);
                    //window.location.href = "{?=url('anjungan/pasien')?}";
                  }
                });
                return false;
              });
            });
          })
        </script>
      <?php
        break;

      case "simpancs":
        $this->db('mlite_antrian_loket')
          ->save([
            'kd' => NULL,
            'type' => 'CS',
            'noantrian' => $_GET['noantrian'],
            'postdate' => date('Y-m-d'),
            'start_time' => date('H:i:s'),
            'end_time' => '00:00:00'
          ]);
        //redirect(url('anjungan/pasien'));
        break;

      case "tampilobat":
        $date = date('Y-m-d');
        $strQuery = "SELECT noantrian FROM mlite_antrian_loket 
                      WHERE type LIKE 'Obat%' AND postdate = '$date' ORDER BY start_time DESC";
        // echo $strQuery;
        $query = $this->db()->pdo()->prepare($strQuery);
        $query->execute();
        $result = $query->fetchAll(\PDO::FETCH_ASSOC);
        // $result = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Obat%')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();

        if ($result) {
          $noantrian = $result[0]['noantrian'];
        } else {
          $noantrian = 0;
        }

        if ($noantrian > 0) {
          $next_antrian = $noantrian + 1;
        } else {
          $next_antrian = 1;
        }

        echo '<div id="nomernya" align="center">';
        echo '<h1 class="display-1">';
        echo 'A' . $next_antrian;
        echo '</h1>';
        echo '[' . date('Y-m-d') . ']';
        echo '</div>';
        echo '<br>';
        break;

      case "printobat":
        $date = date('Y-m-d');
        $strQuery = "SELECT noantrian FROM mlite_antrian_loket 
                      WHERE type LIKE 'Obat%' AND postdate = '$date' ORDER BY start_time DESC";
        $query = $this->db()->pdo()->prepare($strQuery);
        $query->execute();
        $result = $query->fetchAll(\PDO::FETCH_ASSOC);
        // $result = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Obat%')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();

        if ($result) {
          $noantrian = $result[0]['noantrian'];
        } else {
          $noantrian = 0;
        }

        if ($noantrian > 0) {
          $next_antrian = $noantrian + 1;
        } else {
          $next_antrian = 1;
        }
        echo '<div id="nomernya" align="center">';
        echo '<h1 class="display-1">';
        echo 'A' . $next_antrian;
        echo '</h1>';
        echo '[' . date('Y-m-d') . ']';
        echo '</div>';
        echo '<br>';
      ?>
        <script>
          $(document).ready(function() {
            $("#btnKRMObat").on('click', function() {
              var kdbooking = $('#kdbooking').val();
              $("#formobat").submit(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $.ajax({
                  url: "<?php echo url() . '/anjungan/ajax?show=simpanobat&noantrian=' . $next_antrian . '&kdbooking='; ?>" + kdbooking,
                  type: "POST",
                  data: $(this).serialize(),
                  success: function(data) {
                    setTimeout('$("#loading").hide()', 1000);
                    //window.location.href = "{?=url('anjungan/pasien')?}";
                  }
                });
                return false;
              });
            });
          })
        </script>
      <?php
        break;

      case "simpanobat":
        $kdbooking = $_GET['kdbooking'];
        $this->core->db()->pdo()->exec("INSERT INTO `mlite_antrian_loket` (type, noantrian, postdate, start_time, end_time, loket) 
        VALUES ('Obat#$kdbooking', '" . $_GET['noantrian'] . "','" . date('Y-m-d') . "','" . date('H:i:s') . "','00:00:00','Obat')
        ");

        // $this->db('mlite_antrian_loket')
        //   ->save([
        //     'kd' => NULL,
        //     'type' => 'Obat#' . $kdbooking,
        //     'noantrian' => $_GET['noantrian'],
        //     'postdate' => date('Y-m-d'),
        //     'start_time' => date('H:i:s'),
        //     'end_time' => '00:00:00',
        //     'loket' => 'Obat'
        //   ]);
        //redirect(url('anjungan/pasien'));
        $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kdbooking, 6);
        $response = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
        if ($response['metadata']['code'] != '200') {
          $this->db('mlite_settings')->save([
            'module' => 'debug',
            'field' => 'farmasi obat 6',
            'value' => $kdbooking . '|' . $response['metadata']['code'] . '|' . $response['metadata']['message']
          ]);
        }
        break;

      case "tampilracikan":
        $result = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Racikan%')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();

        if ($result) {
          $noantrian = $result['noantrian'];
        } else {
          $noantrian = 0;
        }

        if ($noantrian > 0) {
          $next_antrian = $noantrian + 1;
        } else {
          $next_antrian = 1;
        }

        echo '<div id="nomernya" align="center">';
        echo '<h1 class="display-1">';
        echo 'B' . $next_antrian;
        echo '</h1>';
        echo '[' . date('Y-m-d') . ']';
        echo '</div>';
        echo '<br>';
        break;

      case "printracikan":
        $result = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'LIKE', 'Racikan%')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();

        if ($result) {
          $noantrian = $result['noantrian'];
        } else {
          $noantrian = 0;
        }

        if ($noantrian > 0) {
          $next_antrian = $noantrian + 1;
        } else {
          $next_antrian = 1;
        }
        echo '<div id="nomernya" align="center">';
        echo '<h1 class="display-1">';
        echo 'B' . $next_antrian;
        echo '</h1>';
        echo '[' . date('Y-m-d') . ']';
        echo '</div>';
        echo '<br>';
      ?>
        <script>
          $(document).ready(function() {
            $("#btnKRMRacikan").on('click', function() {
              var kdbooking = $('#kdbooking').val();
              $("#formracikan").submit(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $.ajax({
                  url: "<?php echo url() . '/anjungan/ajax?show=simpanracikan&noantrian=' . $next_antrian . '&kdbooking='; ?>" + kdbooking,
                  type: "POST",
                  data: $(this).serialize(),
                  success: function(data) {
                    setTimeout('$("#loading").hide()', 1000);
                    //window.location.href = "{?=url('anjungan/pasien')?}";
                  }
                });
                return false;
              });
            });
          })
        </script>
<?php
        break;

      case "simpanracikan":
        $kdbooking = $_GET['kdbooking'];
        $this->core->db()->pdo()->exec("INSERT INTO `mlite_antrian_loket` (type, noantrian, postdate, start_time, end_time, loket) 
        VALUES ('Racikan#$kdbooking', '" . $_GET['noantrian'] . "','" . date('Y-m-d') . "','" . date('H:i:s') . "','00:00:00','Racikan')
        ");
        // $this->db('mlite_antrian_loket')
        //   ->save([
        //     'kd' => NULL,
        //     'type' => 'Racikan#' . $kdbooking,
        //     'noantrian' => $_GET['noantrian'],
        //     'postdate' => date('Y-m-d'),
        //     'start_time' => date('H:i:s'),
        //     'end_time' => '00:00:00',
        //     'loket' => 'Racikan'
        //   ]);
        $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kdbooking, 6);
        $response = $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
        if ($response['metadata']['code'] != '200') {
          $this->db('mlite_settings')->save([
            'module' => 'debug',
            'field' => 'farmasi racikan 6',
            'value' => $kdbooking . '|' . $response['metadata']['code'] . '|' . $response['metadata']['message']
          ]);
        }
        //redirect(url('anjungan/pasien'));
        break;

      case "obat":
        //$antrian = $this->db('antriloket')->oneArray();
        //echo $antrian['loket'];
        echo $this->settings->get('anjungan.panggil_obat');
        break;

      case "antriobat":
        //$antrian = $this->db('antriloket')->oneArray();
        //$antrian = $antrian['antrian'] - 1;
        $antrian = $this->settings->get('anjungan.panggil_obat_nomor') - 1;
        if ($antrian == '-1') {
          echo '0';
        } else {
          echo $antrian;
        }
        break;

      case "racikan":
        //$antrian = $this->db('antrics')->oneArray();
        //echo $antrian['loket'];
        echo $this->settings->get('anjungan.panggil_racikan');
        break;

      case "antriracikan":
        //$antrian = $this->db('antrics')->oneArray();
        //$antrian = $antrian['antrian'] - 1;
        $antrian = $this->settings->get('anjungan.panggil_racikan_nomor') - 1;
        if ($antrian == '-1') {
          echo '0';
        } else {
          echo $antrian;
        }
        break;

      case "loket":
        //$antrian = $this->db('antriloket')->oneArray();
        //echo $antrian['loket'];
        echo $this->settings->get('anjungan.panggil_loket');
        break;

      case "antriloket":
        //$antrian = $this->db('antriloket')->oneArray();
        //$antrian = $antrian['antrian'] - 1;
        $antrian = $this->settings->get('anjungan.panggil_loket_nomor') - 1;
        if ($antrian == '-1') {
          echo '0';
        } else {
          echo $antrian;
        }
        break;

      case "cs":
        //$antrian = $this->db('antrics')->oneArray();
        //echo $antrian['loket'];
        echo $this->settings->get('anjungan.panggil_cs');
        break;

      case "antrics":
        //$antrian = $this->db('antrics')->oneArray();
        //$antrian = $antrian['antrian'] - 1;
        $antrian = $this->settings->get('anjungan.panggil_cs_nomor') - 1;
        if ($antrian == '-1') {
          echo '0';
        } else {
          echo $antrian;
        }
        break;

      case "get-skdp":
        if (!empty($_POST['no_rkm_medis'])) {
          $data = array();
          $query = $this->db('skdp_bpjs')
            ->join('dokter', 'dokter.kd_dokter = skdp_bpjs.kd_dokter')
            ->join('booking_registrasi', 'booking_registrasi.tanggal_periksa = skdp_bpjs.tanggal_datang')
            ->join('poliklinik', 'poliklinik.kd_poli = booking_registrasi.kd_poli')
            ->join('pasien', 'pasien.no_rkm_medis = skdp_bpjs.no_rkm_medis')
            ->where('skdp_bpjs.no_rkm_medis', $_POST['no_rkm_medis'])
            ->where('booking_registrasi.kd_poli', $_POST['kd_poli'])
            ->desc('skdp_bpjs.tanggal_datang')
            ->oneArray();
          if (!empty($query)) {
            $data['status'] = 'ok';
            $data['result'] = $query;
          } else {
            $data['status'] = 'err';
            $data['result'] = '';
          }
          echo json_encode($data);
        }
        break;

      case "get-daftar":
        if (!empty($_POST['no_peserta'])) {
          $data = array();
          $query = $this->db('pasien')
            ->where('no_peserta', $_POST['no_peserta'])
            ->oneArray();
          if (!empty($query)) {
            $data['status'] = 'ok';
            $data['result'] = $query;
          } else {
            $data['status'] = 'err';
            $data['result'] = '';
          }
          echo json_encode($data);
        }
        break;

      case "get-poli":
        if (!empty($_POST['no_rkm_medis'])) {
          $data = array();
          $tanggal = $_POST['tgl_registrasi'];
          $tentukan_hari = date('D', strtotime($tanggal));
          $day = array('Sun' => 'AKHAD', 'Mon' => 'SENIN', 'Tue' => 'SELASA', 'Wed' => 'RABU', 'Thu' => 'KAMIS', 'Fri' => 'JUMAT', 'Sat' => 'SABTU');
          $hari = $day[$tentukan_hari];

          // $strQuery = "SELECT DISTINCT p.kd_poli, p.nm_poli, j.jam_mulai, j.jam_selesai FROM poliklinik p INNER JOIN jadwal j ON p.kd_poli = j.kd_poli WHERE j.hari_kerja LIKE '$hari' GROUP BY j.kd_poli";
          $strQuery = "SELECT DISTINCT p.kd_poli, p.nm_poli, LEFT(j.jam_mulai,5) as jam_mulai, LEFT(j.jam_selesai,5) as jam_selesai 
                        FROM poliklinik p INNER JOIN jadwal j ON p.kd_poli = j.kd_poli 
                        WHERE j.hari_kerja LIKE '$hari' AND j.kd_dokter in (select kd_dokter from maping_dokter_dpjpvclaim)
                        GROUP BY j.kd_poli;";
          $query = $this->db()->pdo()->prepare($strQuery);
          $query->execute();
          $rows = $query->fetchAll(\PDO::FETCH_ASSOC);

          if (!empty($rows)) {
            if ($this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tgl_registrasi', $_POST['tgl_registrasi'])->where('stts', '<>', 'Batal')->oneArray()) {
              $data['status'] = 'exist';
              $data['result'] = $rows;
            } else {
              $data['status'] = 'ok';
              $data['result'] = $rows;
            }
          } else {
            $data['status'] = 'err';
            $data['result'] = '';
          }
          echo json_encode($data);
        }
        break;

      case "get-dokter":
        if (!empty($_POST['kd_poli'])) {
          $tanggal = $_POST['tgl_registrasi'];
          $kd_poli = $_POST['kd_poli'];
          $path = 'jadwaldokter/kodepoli/' . $kd_poli . '/tanggal/' . $tanggal;
          $data = array();
          $responseJadwalDokter = $this->getDataWSBPJS($path);
          // $this->db('mlite_settings')->save([
          //   'module' => 'debug',
          //   'field' => 'get-dokter',
          //   'value' => 'get-dokter' . json_encode($responseJadwalDokter)
          // ]);
          if ($responseJadwalDokter['metadata']['code'] == '200') {
            if ($this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tgl_registrasi', $_POST['tgl_registrasi'])->where('kd_poli', $kd_poli)->oneArray()) {
              $data['status'] = 'exist';
              $data['result'] = '';
            } else {
              $data = $responseJadwalDokter;
              $data['status'] = 'ok';
            }
          } else {
            $data['status'] = 'empty';
            $data['result'] = '';
          }
          echo json_encode($data);


          // $tentukan_hari = date('D', strtotime($tanggal));
          // $day = array('Sun' => 'AKHAD', 'Mon' => 'SENIN', 'Tue' => 'SELASA', 'Wed' => 'RABU', 'Thu' => 'KAMIS', 'Fri' => 'JUMAT', 'Sat' => 'SABTU');
          // $hari = $day[$tentukan_hari];

          // $result = $this->db('jadwal')
          //   ->select(['kd_dokter' => 'jadwal.kd_dokter'])
          //   ->select(['nm_dokter' => 'dokter.nm_dokter'])
          //   ->select(['kuota' => 'jadwal.kuota'])
          //   ->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli')
          //   ->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')
          //   ->where('jadwal.kd_poli', $_POST['kd_poli'])
          //   ->like('jadwal.hari_kerja', $hari)
          //   ->oneArray();
          // $check_kuota = $this->db('reg_periksa')
          //   ->select(['count' => 'COUNT(DISTINCT no_rawat)'])
          //   ->where('kd_poli', $_POST['kd_poli'])
          //   ->where('tgl_registrasi', $_POST['tgl_registrasi'])
          //   ->oneArray();
          // $curr_count = $check_kuota['count'];
          // $curr_kuota = $result['kuota'];
          // $online = $curr_kuota / 2;
          // if ($curr_count > $online) {
          //   $data['status'] = 'limit';
          // } else {
          //   $query = $this->db('jadwal')
          //     ->select(['kd_dokter' => 'jadwal.kd_dokter'])
          //     ->select(['nm_dokter' => 'dokter.nm_dokter'])
          //     ->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli')
          //     ->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')
          //     ->join('maping_dokter_dpjpvclaim', 'maping_dokter_dpjpvclaim.kd_dokter = jadwal.kd_dokter')
          //     ->where('jadwal.kd_poli', $_POST['kd_poli'])
          //     ->like('jadwal.hari_kerja', $hari)
          //     ->toArray();
          //   if (!empty($query)) {
          //     $data['status'] = 'ok';
          //     $data['result'] = $query;
          //   } else {
          //     $data['status'] = 'err';
          //     $data['result'] = '';
          //   }
          //   echo json_encode($data);
          // }
        }
        break;

      case "get-namapoli":
        //$_POST['kd_poli'] = 'INT';
        if (!empty($_POST['kd_poli'])) {
          $data = array();
          $result = $this->db('poliklinik')->where('kd_poli', $_POST['kd_poli'])->oneArray();
          if (!empty($result)) {
            $data['status'] = 'ok';
            $data['result'] = $result;
          } else {
            $data['status'] = 'err';
            $data['result'] = '';
          }
          echo json_encode($data);
        }
        break;

      case "get-namadokter":
        //$_POST['kd_dokter'] = 'DR001';
        if (!empty($_POST['kd_dokter'])) {
          $data = array();
          $result = $this->db('dokter')->where('kd_dokter', $_POST['kd_dokter'])->oneArray();
          if (!empty($result)) {
            $data['status'] = 'ok';
            $data['result'] = $result;
          } else {
            $data['status'] = 'err';
            $data['result'] = '';
          }
          echo json_encode($data);
        }
        break;

      case "get-penjab":
        if (!empty($_POST['kd_pj'])) {
          $data = array();
          $result = $this->db('penjab')->where('kd_pj', $_POST['kd_pj'])->oneArray();
          if (!empty($result)) {
            $data['status'] = 'ok';
            $data['result'] = $result;
          } else {
            $data['status'] = 'err';
            $data['result'] = '';
          }
          echo json_encode($data);
        }
        break;
      case "post-registrasi":
        $data = array();
        if (!empty($_POST['no_rkm_medis_daftar'])) {

          // $this->db('mlite_settings')->save([
          //   'module' => 'debug',
          //   'field' => 'post-registrasi',
          //   'value' => 'begin post registrasi'
          // ]);
          // $result = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $_POST['kd_dokter_daftar'])->oneArray();


          $no_rkm_medis = $_POST['no_rkm_medis_daftar'];
          $nomorreferensi = $_POST['no_rujukan_daftar']; //nomor rujukan
          $nomorkartu = $_POST['no_peserta']; //$this->core->getPasienInfo('no_peserta', $no_rkm_medis);
          $nik = $_POST['nik'];
          $nohp = $this->core->getPasienInfo('no_tlp', $no_rkm_medis);;
          $kodepoli = $_POST['kd_poli_daftar'];
          $kodesubspesialis = $_POST['kodesubspesialis'];
          $norm = $no_rkm_medis;
          $tanggalperiksa = $_POST['tgl_registrasi_daftar'];
          $kodedokter = $_POST['kd_dokter_daftar'];
          $jampraktek = $_POST['jam_praktek'];
          $jeniskunjungan = $_POST['jeniskunjungan'];
          $jenispasien = 'JKN';

          //melengkapi data pasien
          $this->cekDataPasien($no_rkm_medis, $nomorkartu, $nik);

          $dataAmbilAntrean = $this->ambilAntreanRS($nomorkartu, $nik, $nohp, $kodepoli, $norm, $tanggalperiksa, $kodedokter, $jampraktek, $jeniskunjungan, $nomorreferensi);
          $responseAmbilAntrean = $this->sendDataWSRS('ambilantrean', $dataAmbilAntrean);
          // var_dump($dataAmbilAntrean);
          // // {
          // //   "response": {
          // //       "nomorantrean": "X-XXX",
          // //       "angkaantrean": "XXX",
          // //       "kodebooking": "XXXXXXXXXXXXX",
          // //       "pasienbaru": X,
          // //       "norm": "XXXXXX",
          // //       "namapoli": "XXXXXXXXXXXXXXX",
          // //       "namadokter": "XXXXXXXXXXXXXXX",
          // //       "estimasidilayani": XXXXXXX,
          // //       "sisakuotajkn": "XX",
          // //       "kuotajkn": "XX",
          // //       "sisakuotanonjkn": "XXX",
          // //       "kuotanonjkn": "XXX",
          // //       "keterangan": "XXXXXXXXXXXXXXXX"
          // //   },
          // //   "metadata": {
          // //       "message": "Ok",
          // //       "code": 200
          // //   }

          // $this->db('mlite_settings')->save([
          //   'module' => 'debug',
          //   'field' => 'post-registrasi responseAmbilAntrean-code',
          //   'value' => $responseAmbilAntrean['metadata']['message'] . ' | ' . $kodedokter . ' | ' . $jampraktek
          // ]);
          // //Ok | 35039 | 
          if ($responseAmbilAntrean) {

            if ($responseAmbilAntrean['metadata']['code'] == '200') {

              $kodebooking = $responseAmbilAntrean['response']['kodebooking'];
              $nomorkartu = $nomorkartu;
              $nik = $nik;
              $nohp = $nohp;
              $kodepoli = $kodesubspesialis;
              $namapoli = $responseAmbilAntrean['response']['namapoli'];
              $pasienbaru = $responseAmbilAntrean['response']['pasienbaru'];
              $norm = $responseAmbilAntrean['response']['norm'];
              $tanggalperiksa = $tanggalperiksa;
              $kodedokter = $kodedokter;
              $namadokter = $responseAmbilAntrean['response']['namadokter'];
              $jampraktek = $jampraktek;
              $jeniskunjungan = $jeniskunjungan; //{1 (Rujukan FKTP), 2 (Rujukan Internal), 3 (Kontrol), 4 (Rujukan Antar RS)},
              $nomorreferensi = $nomorreferensi;
              $nomorantrean = $responseAmbilAntrean['response']['nomorantrean'];
              $angkaantrean = $responseAmbilAntrean['response']['angkaantrean'];
              $estimasidilayani = $responseAmbilAntrean['response']['estimasidilayani'];
              $sisakuotajkn = $responseAmbilAntrean['response']['sisakuotajkn'];
              $kuotajkn = $responseAmbilAntrean['response']['kuotajkn'];
              $sisakuotanonjkn = $responseAmbilAntrean['response']['sisakuotanonjkn'];
              $kuotanonjkn = $responseAmbilAntrean['response']['kuotanonjkn'];
              $keterangan = $responseAmbilAntrean['response']['keterangan'];

              if ($_POST['jenis'] != 'BPJS') { //jika non BPJS
                $jenispasien = 'NON JKN';
                $nomorkartu = '';
                $nomorreferensi = '';
              }

              $dataTambahAntrean = $this->tambahAntreanBPJS(
                $kodebooking,
                $jenispasien,
                $nomorkartu,
                $nik,
                $nohp,
                $kodepoli,
                $namapoli,
                $pasienbaru,
                $norm,
                $tanggalperiksa,
                $kodedokter,
                $namadokter,
                $jampraktek,
                $jeniskunjungan,
                $nomorreferensi,
                $nomorantrean,
                $angkaantrean,
                $estimasidilayani,
                $sisakuotajkn,
                $kuotajkn,
                $sisakuotanonjkn,
                $kuotanonjkn,
                $keterangan
              );
              $responseTambahAntrean = $this->sendDataWSBPJS('antrean/add', $dataTambahAntrean);
              $datajs = json_encode($dataTambahAntrean);
              // $this->db('mlite_settings')->save([
              //   'module' => 'debug',
              //   'field' => 'post-registrasi datajs',
              //   'value' => $datajs
              // ]);
              if ($responseTambahAntrean['metadata']['code'] == '200') {

                //check in antrean
                date_default_timezone_set('Asia/Jakarta');
                $sekarang  = date("Y-m-d");
                $jamsekarang = date("H:i:s");
                $query = $this->db()->pdo()->prepare("UPDATE referensi_mobilejkn_bpjs set status='Checkin',validasi='$sekarang $jamsekarang' where nobooking='$kodebooking'");
                $query->execute();
                //prepare data WS 
                // $dataWS = $this->checkinAntreanRS($kodebooking);
                //send data WS
                // $responseCheckin = $this->sendDataWSRS('checkinantrean', $dataWS);

                //get data registrasi untuk cetak
                $result = $this->db('reg_periksa')
                  ->select('reg_periksa.no_rkm_medis')
                  ->select('referensi_mobilejkn_bpjs.nobooking')
                  ->select('pasien.nm_pasien')
                  ->select('pasien.alamat')
                  ->select('reg_periksa.tgl_registrasi')
                  ->select('reg_periksa.jam_reg')
                  ->select('reg_periksa.no_rawat')
                  ->select('reg_periksa.no_reg')
                  ->select('poliklinik.nm_poli')
                  ->select('dokter.nm_dokter')
                  ->select('reg_periksa.status_lanjut')
                  ->select('penjab.png_jawab')
                  ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
                  ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                  ->join('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
                  ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                  ->join('referensi_mobilejkn_bpjs', 'referensi_mobilejkn_bpjs.no_rawat = reg_periksa.no_rawat')
                  ->where('reg_periksa.tgl_registrasi', $tanggalperiksa)
                  ->where('reg_periksa.no_rkm_medis', $no_rkm_medis)
                  ->desc('referensi_mobilejkn_bpjs.nobooking')
                  ->oneArray();

                if (!empty($result)) {
                  $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kodebooking, 3);
                  $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);

                  $data['status'] = 'ok';
                  $data['result'] = $result;
                } else {
                  $data['status'] = 'err';
                  $data['result'] = 'Registrasi WS Berhasil. Tetapi Data Registrasi Tidak Tercatat.';
                }
              } else {
                //delete record 
                $data['status'] = 'err';
                $data['result'] = 'Tambah Antrean BPJS gagal. ' . $responseTambahAntrean['metadata']['message'];

                $result = $this->db('referensi_mobilejkn_bpjs')->select('no_rawat')->where('nobooking', $kodebooking)->oneArray();
                $no_rawat = $result['no_rawat'];
                // $this->db('mlite_settings')->save([
                //   'module' => 'debug',
                //   'field' => 'post-registrasi responseTambahAntrean',
                //   'value' => 'delete records ' . $no_rawat . ' | ' . $data['result']
                // ]);
                $this->db('reg_periksa')->where('no_rawat', $no_rawat)->delete();
                $this->db('referensi_mobilejkn_bpjs')->where('nobooking', $kodebooking)->delete();
              }
            } else {
              $data['status'] = 'err';
              $data['result'] = 'Tambah Antrean RS gagal. ' . $responseAmbilAntrean['metadata']['message'];
            }
          } else {
            $data['status'] = 'err';
            $data['result'] = 'Tambah Antrean RS gagal. Terjadi kesalahan pada server RS. ';
          }
          // $data['status'] = 'ok';
          // $data['result'] = $responseAmbilAntrean;
          echo json_encode($data);
        } else {
          $data['status'] = 'err';
          $data['result'] = 'error bet error';
          echo json_encode($data);
        }
        break;

      case "check-rujukan":
        if (!empty($_POST['no_bpjs'])) {
          $data = array();
          $no_bpjs = $_POST['no_bpjs'];

          $tglAkhir = date('Y-m-d');
          $tglMulai = date('Y-m-d', strtotime('-90 days'));

          $url = 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/monitoring/HistoriPelayanan/NoKartu/' . $no_bpjs . '/tglMulai/' . $tglMulai . '/tglAkhir/' . $tglAkhir;
          $responsePCARE = $this->getRujukanWSBPJS($url);

          if ($responsePCARE['metaData']['code'] == '200') {
            $arr_str = '';
            $dataResponse = json_decode($responsePCARE['response'], true);
            // var_dump($dataResponse['histori']);
            foreach ($dataResponse['histori'] as $a) {
              // echo $a['noSep'] . ' ' . $this->checkNoSEP($a['noSep']);
              if ($this->checkNoSEP($a['noSep']) && $a['jnsPelayanan'] == '1') {
                // echo '{"noRujukan":"' . $a['noRujukan'] . '" , "noSep":"'  . $a['noSep'] . '", "tglSep":"'  . $a['tglSep'] . '", "poli":"'  . $a['poli'] . '"},';
                $poli = "";
                if ($a['poli'] == "") {
                  $poli = "-";
                } else {
                  $poli = $a['poli'];
                }
                $arr_str = $arr_str . '{"noRujukan":"' . $a['noRujukan'] . '" , "noSep":"'  . $a['noSep'] . '", "tglSep":"'  . $a['tglSep'] . '", "poli":"'  . $poli . '"},';
              }
              // echo $arr_str;
            }
            $arr_str = substr($arr_str, 0, strlen($arr_str) - 1);
            $data['result'] = '[' . $arr_str . ']';
            $data['status'] = 'ok';
          } else {
            $data['status'] = 'err';
            $data['result'] = $responsePCARE['metaData']['message'];
            // }
          }
          echo json_encode($data);
        }
        break;

      case "check-rujukan2":
        if (!empty($_POST['no_bpjs'])) {
          $data = array();
          $no_bpjs = $_POST['no_bpjs'];
          $url = 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/Rujukan/List/Peserta/' . $no_bpjs;
          $responsePCARE = $this->getRujukanWSBPJS($url);
          $url = 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/Rujukan/RS/List/Peserta/' . $no_bpjs;
          $responseRS = $this->getRujukanWSBPJS($url);
          // $this->db('mlite_settings')->save([
          //   'module' => 'debug',
          //   'field' => 'check-rujukan Pcare',
          //   'value' => 'PCare berhasil' . $responsePCARE['metaData']['code']
          // ]);
          // if ($responsePCARE['metaData']['code'] == '200') {
          //   $data['pcare'] = $responsePCARE;
          //   $data['statuspcare'] = 'ok';
          // } else {
          //   $data['statuspcare'] = 'err';
          //   $data['resultpcare'] = $responsePCARE['metaData']['message'];
          // }
          // $this->db('mlite_settings')->save([
          //   'module' => 'debug',
          //   'field' => 'check-rujukan RS',
          //   'value' => 'RS berhasil ' . $responseRS['metaData']['code']
          // ]);
          // if ($responseRS['metaData']['code'] == '200') {
          //   $data['rs'] = $responseRS;
          //   $data['statusrs'] = 'ok';
          // } else {
          //   // $this->db('mlite_settings')->save([
          //   //   'module' => 'debug',
          //   //   'field' => 'check-rujukan RS',
          //   //   'value' => 'RS gagal ' . $responsePCARE['metaData']['code'] . ' msg: ' . $responseRS['metaData']['message']
          //   // ]);
          //   $data['statusrs'] = 'err';
          //   $data['resultrs'] = $responsePCARE['metaData']['message'];
          // }
          if ($responsePCARE['metaData']['code'] != '200' && $responseRS['metaData']['code'] != '200') {
            $data['status'] = 'err';
          } else {
            $data['status'] = 'ok';
            $data['pcare'] = $responsePCARE;
            $data['rs'] = $responseRS;
          }

          echo json_encode($data);
        }
        break;

      case "check-nik":
        if (!empty($_POST['no_ktp'])) {
          $data = array();
          $query = $this->db('pasien')
            ->where('no_ktp', $_POST['no_ktp'])
            ->oneArray();
          if (!empty($query)) {
            $data['status'] = 'ok';
            $data['result'] = $query;
          } else {
            $data['status'] = 'err';
            $data['result'] = '';
          }
          echo json_encode($data);
        }
        break;

      case "checkin":
        if (!empty($_POST['no_kodebooking'])) {
          $data = array();
          $no_kodebooking = $_POST['no_kodebooking'];

          //prepare data WS 
          $dataWS = $this->checkinAntreanRS($no_kodebooking);
          //send data WS
          $responseCheckin = $this->sendDataWSRS('checkinantrean', $dataWS);

          if ($responseCheckin['metadata']['code'] == '200') {
            $recordAntrianMobileJKN = $this->db('record_antrian_mobilejkn')->where('kodebooking', $no_kodebooking)->oneArray();
            $dataRefMobileJKN = $this->db('referensi_mobilejkn_bpjs')->where('nobooking', $no_kodebooking)->oneArray();


            $kodebooking = $recordAntrianMobileJKN['kodebooking'];
            $nomorkartu = $dataRefMobileJKN['nomorkartu'];
            $nik = $dataRefMobileJKN['nik'];
            $nohp = $dataRefMobileJKN['nohp'];
            $kodepoli = $dataRefMobileJKN['kodepoli'];
            $namapoli = $recordAntrianMobileJKN['namapoli'];
            $pasienbaru = (int)$recordAntrianMobileJKN['pasienbaru'];
            $norm = $recordAntrianMobileJKN['norm'];
            $tanggalperiksa = $dataRefMobileJKN['tanggalperiksa'];
            $kodedokter = $dataRefMobileJKN['kodedokter'];
            $namadokter = $recordAntrianMobileJKN['namadokter'];
            $jampraktek = $dataRefMobileJKN['jampraktek'];
            $jeniskunjungan = (int)substr($dataRefMobileJKN['jeniskunjungan'], 0, 1);
            $nomorreferensi = $dataRefMobileJKN['nomorreferensi'];
            $nomorantrean = $recordAntrianMobileJKN['nomorantrean'];
            $angkaantrean = $recordAntrianMobileJKN['angkaantrean'];
            $dilayani        = $angkaantrean * 10;
            $jammulai   = substr($jampraktek, 0, 5);
            $estimasidilayani = strtotime($tanggalperiksa . " " . $jammulai . '+' . $dilayani . ' minute') * 1000;
            $sisakuotajkn = (int)$recordAntrianMobileJKN['sisakuotajkn'];
            $kuotajkn = $recordAntrianMobileJKN['kuotajkn'];
            $sisakuotanonjkn = (int)$recordAntrianMobileJKN['sisakuotanonjkn'];
            $kuotanonjkn = $recordAntrianMobileJKN['kuotanonjkn'];
            $keterangan = $recordAntrianMobileJKN['keterangan'];
            $jenispasien = 'JKN';

            $dataTambahAntrean = $this->tambahAntreanBPJS(
              $kodebooking,
              $jenispasien,
              $nomorkartu,
              $nik,
              $nohp,
              $kodepoli,
              $namapoli,
              $pasienbaru,
              $norm,
              $tanggalperiksa,
              $kodedokter,
              $namadokter,
              $jampraktek,
              $jeniskunjungan,
              $nomorreferensi,
              $nomorantrean,
              $angkaantrean,
              $estimasidilayani,
              $sisakuotajkn,
              $kuotajkn,
              $sisakuotanonjkn,
              $kuotanonjkn,
              $keterangan
            );
            $responseTambahAntrean = $this->sendDataWSBPJS('antrean/add', $dataTambahAntrean);
            if ($responseTambahAntrean['metadata']['code'] == '200') {
              $result = $this->db('reg_periksa')
                ->select('reg_periksa.no_rkm_medis')
                ->select('pasien.nm_pasien')
                ->select('pasien.alamat')
                ->select('reg_periksa.tgl_registrasi')
                ->select('reg_periksa.jam_reg')
                ->select('reg_periksa.no_rawat')
                ->select('reg_periksa.no_reg')
                ->select('poliklinik.nm_poli')
                ->select('dokter.nm_dokter')
                ->select('reg_periksa.status_lanjut')
                ->select('penjab.png_jawab')
                ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
                ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                ->join('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->join('referensi_mobilejkn_bpjs', 'referensi_mobilejkn_bpjs.no_rawat = reg_periksa.no_rawat')
                ->where('referensi_mobilejkn_bpjs.nobooking', $no_kodebooking)
                ->oneArray();

              if (!empty($result)) {
                $dataUpdateWaktuAntrean = $this->updateWaktuAntreanBPJS($kodebooking, 3);
                $this->sendDataWSBPJS('antrean/updatewaktu', $dataUpdateWaktuAntrean);
                $data['status'] = 'ok';
                $data['result'] = $result;
                $data['no_rujukan'] = $nomorreferensi;
              } else {
                $data['status'] = 'err';
                $data['result'] = 'Data Registrasi Tidak Ditemukan.';
              }
            } else {
              $jsonData = json_encode($dataTambahAntrean);
              $this->db('mlite_settings')->save([
                'module' => 'debug',
                'field' => 'Checkin responseTambahAntrean',
                'value' => $jsonData . '|' . $responseTambahAntrean['metadata']['code'] . ' ' . $responseTambahAntrean['metadata']['message']
              ]);
              $data['status'] = 'err';
              $data['result'] = 'Checkin gagal. Tidak dapat tambah antrean BPJS ' . $responseTambahAntrean['metadata']['message'];
            }
          } else {
            $data['status'] = 'err';
            $data['result'] = 'Checkin gagal. ' . $responseCheckin['metadata']['message'];
          }
          echo json_encode($data);
        }
        break;
    }
    exit();
  }

  public function getPresensi()
  {

    $title = 'Presensi Pegawai';
    $logo  = $this->settings->get('settings.logo');

    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));

    $content = $this->draw('presensi.html', [
      'title' => $title,
      'notify' => $this->core->getNotify(),
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_poli'),
      'jam_jaga' => $this->db('jam_jaga')->group('jam_masuk')->toArray()
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function getGeolocation()
  {

    $idpeg = $this->db('barcode')->where('barcode', $this->core->getUserInfo('username', null, true))->oneArray();

    if (isset($_GET['lat'], $_GET['lng'])) {
      if (!$this->db('mlite_geolocation_presensi')->where('id', $idpeg['id'])->where('tanggal', date('Y-m-d'))->oneArray()) {
        $this->db('mlite_geolocation_presensi')
          ->save([
            'id' => $idpeg['id'],
            'tanggal' => date('Y-m-d'),
            'latitude' => $_GET['lat'],
            'longitude' => $_GET['lng']
          ]);
      }
    }

    exit();
  }

  public function getUpload()
  {
    if ($photo = isset_or($_FILES['webcam']['tmp_name'], false)) {
      $img = new \Systems\Lib\Image;
      if ($img->load($photo)) {
        if ($img->getInfos('width') < $img->getInfos('height')) {
          $img->crop(0, 0, $img->getInfos('width'), $img->getInfos('width'));
        } else {
          $img->crop(0, 0, $img->getInfos('height'), $img->getInfos('height'));
        }

        if ($img->getInfos('width') > 512) {
          $img->resize(512, 512);
        }
        $gambar = uniqid('photo') . "." . $img->getInfos('type');
      }

      if (isset($img) && $img->getInfos('width')) {

        $img->save(WEBAPPS_PATH . "/presensi/" . $gambar);

        $urlnya         = WEBAPPS_URL . '/presensi/' . $gambar;
        $barcode        = $_GET['barcode'];

        $idpeg          = $this->db('barcode')->where('barcode', $barcode)->oneArray();
        $jam_jaga       = $this->db('jam_jaga')->join('pegawai', 'pegawai.departemen = jam_jaga.dep_id')->where('pegawai.id', $idpeg['id'])->where('jam_jaga.shift', $_GET['shift'])->oneArray();
        $jadwal_pegawai = $this->db('jadwal_pegawai')->where('id', $idpeg['id'])->where('h' . date('j'), $_GET['shift'])->oneArray();

        $set_keterlambatan  = $this->db('set_keterlambatan')->toArray();
        $toleransi      = $set_keterlambatan['toleransi'];
        $terlambat1     = $set_keterlambatan['terlambat1'];
        $terlambat2     = $set_keterlambatan['terlambat2'];

        $valid = $this->db('rekap_presensi')->where('id', $idpeg['id'])->where('shift', $jam_jaga['shift'])->like('jam_datang', '%' . date('Y-m-d') . '%')->oneArray();

        if ($valid) {
          $this->notify('failure', 'Anda sudah presensi untuk tanggal ' . date('Y-m-d'));
          //}elseif((!empty($idpeg['id']))&&(!empty($jam_jaga['shift']))&&($jadwal_pegawai)&&(!$valid)) {
        } elseif ((!empty($idpeg['id']))) {
          $cek = $this->db('temporary_presensi')->where('id', $idpeg['id'])->oneArray();

          if (!$cek) {
            if (empty($urlnya)) {
              $this->notify('failure', 'Pilih shift dulu...!!!!');
            } else {

              $status = 'Tepat Waktu';

              if ((strtotime(date('Y-m-d H:i:s')) - strtotime(date('Y-m-d') . ' ' . $jam_jaga['jam_masuk'])) > ($toleransi * 60)) {
                $status = 'Terlambat Toleransi';
              }
              if ((strtotime(date('Y-m-d H:i:s')) - strtotime(date('Y-m-d') . ' ' . $jam_jaga['jam_masuk'])) > ($terlambat1 * 60)) {
                $status = 'Terlambat I';
              }
              if ((strtotime(date('Y-m-d H:i:s')) - strtotime(date('Y-m-d') . ' ' . $jam_jaga['jam_masuk'])) > ($terlambat2 * 60)) {
                $status = 'Terlambat II';
              }

              if (strtotime(date('Y-m-d H:i:s')) - (date('Y-m-d') . ' ' . $jam_jaga['jam_masuk']) > ($toleransi * 60)) {
                $awal  = new \DateTime(date('Y-m-d') . ' ' . $jam_jaga['jam_masuk']);
                $akhir = new \DateTime();
                $diff = $akhir->diff($awal, true); // to make the difference to be always positive.
                $keterlambatan = $diff->format('%H:%I:%S');
              }

              $insert = $this->db('temporary_presensi')
                ->save([
                  'id' => $idpeg['id'],
                  'shift' => $jam_jaga['shift'],
                  'jam_datang' => date('Y-m-d H:i:s'),
                  'jam_pulang' => NULL,
                  'status' => $status,
                  'keterlambatan' => $keterlambatan,
                  'durasi' => '',
                  'photo' => $urlnya
                ]);

              if ($insert) {
                $this->notify('success', 'Presensi Masuk jam ' . $jam_jaga['jam_masuk'] . ' ' . $status . ' ' . $keterlambatan);
              }
            }
          } elseif ($cek) {

            $status = $cek['status'];
            if ((strtotime(date('Y-m-d H:i:s')) - strtotime(date('Y-m-d') . ' ' . $jam_jaga['jam_pulang'])) < 0) {
              $status = $cek['status'] . ' & PSW';
            }

            $awal  = new \DateTime($cek['jam_datang']);
            $akhir = new \DateTime();
            $diff = $akhir->diff($awal, true); // to make the difference to be always positive.
            $durasi = $diff->format('%H:%I:%S');

            $ubah = $this->db('temporary_presensi')
              ->where('id', $idpeg['id'])
              ->save([
                'jam_pulang' => date('Y-m-d H:i:s'),
                'status' => $status,
                'durasi' => $durasi
              ]);

            if ($ubah) {
              $presensi = $this->db('temporary_presensi')->where('id', $cek['id'])->oneArray();
              $insert = $this->db('rekap_presensi')
                ->save([
                  'id' => $presensi['id'],
                  'shift' => $presensi['shift'],
                  'jam_datang' => $presensi['jam_datang'],
                  'jam_pulang' => $presensi['jam_pulang'],
                  'status' => $presensi['status'],
                  'keterlambatan' => $presensi['keterlambatan'],
                  'durasi' => $presensi['durasi'],
                  'keterangan' => '-',
                  'photo' => $presensi['photo']
                ]);
              if ($insert) {
                $this->notify('success', 'Presensi pulang telah disimpan');
                $this->db('temporary_presensi')->where('id', $cek['id'])->delete();
              }
            }
          }
        } else {
          $this->notify('failure', 'ID Pegawai atau jadwal shift tidak sesuai. Silahkan pilih berdasarkan shift departemen anda!');
        }
      }
    }
    //echo 'Upload';
    exit();
  }

  public function getDisplayBed()
  {
    $title = 'Display Bed Management';
    $logo  = $this->settings->get('settings.logo');
    $display = $this->_resultDisplayBed();

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('display.bed.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_poli'),
      'display' => $display
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function _resultDisplayBed()
  {
    $query = $this->db()->pdo()->prepare("SELECT a.nm_bangsal, b.kelas , a.kd_bangsal FROM bangsal a, kamar b WHERE a.kd_bangsal = b.kd_bangsal AND b.statusdata = '1' GROUP BY b.kd_bangsal , b.kelas");
    $query->execute();
    $rows = $query->fetchAll(\PDO::FETCH_ASSOC);;

    $result = [];
    if (count($rows)) {
      foreach ($rows as $row) {
        $row['kosong'] = $this->db('kamar')
          ->select(['jumlah' => 'COUNT(kamar.status)'])
          ->join('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
          ->where('bangsal.kd_bangsal', $row['kd_bangsal'])
          ->where('kamar.kelas', $row['kelas'])
          ->where('kamar.status', 'KOSONG')
          ->where('kamar.statusdata', '1')
          ->group(array('kamar.kd_bangsal', 'kamar.kelas'))
          ->oneArray();
        $row['isi'] = $this->db('kamar')
          ->select(['jumlah' => 'COUNT(kamar.status)'])
          ->join('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
          ->where('bangsal.kd_bangsal', $row['kd_bangsal'])
          ->where('kamar.kelas', $row['kelas'])
          ->where('kamar.status', 'ISI')
          ->where('kamar.statusdata', '1')
          ->group(array('kamar.kd_bangsal', 'kamar.kelas'))
          ->oneArray();
        $result[] = $row;
      }
    }

    return $result;
  }

  public function getSepMandiri()
  {
    $title = 'Display SEP Mandiri';
    $logo  = $this->settings->get('settings.logo');

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('sep.mandiri.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_anjungan'),
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function getSepMandiriCek()
  {
    if (isset($_POST['cekrm']) && isset($_POST['no_rkm_medis']) && $_POST['no_rkm_medis'] != '') {
      $pasien = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();
      redirect(url('anjungan/sep/' . $pasien['no_peserta'] . '/' . $_POST['no_rkm_medis']));
    } else {
      redirect(url('anjungan/sep'));
    }
    exit();
  }

  public function getSepMandiriNokaNorm()
  {
    $slug = parseURL();
    $sep_response = '';
    if (count($slug) == 4 && $slug[0] == 'anjungan' && $slug[1] == 'sep') {

      $url = "Rujukan/List/Peserta/" . $slug[2];

      $url = $this->api_url . '' . $url;
      $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key);
      $json = json_decode($output, true);
      //var_dump($json);
      $code = $json['metaData']['code'];
      $message = $json['metaData']['message'];
      $stringDecrypt = stringDecrypt($this->settings->get('settings.BpjsConsID'), $this->settings->get('settings.BpjsSecretKey'), $json['response']);
      $decompress = '""';

      if (!empty($json)) :
        if ($code != "200") {
          $sep_response = $message;
        } else {
          if (!empty($stringDecrypt)) {
            $decompress = decompress($stringDecrypt);
            $sep_response = json_decode($decompress, true);
          } else {
            $sep_response = "Sambungan ke server BPJS sedang ada gangguan. Silahkan ulangi lagi.";
          }
        }
      else :
        $sep_response = "Sambungan ke server BPJS sedang ada gangguan. Silahkan ulangi lagi.";
      endif;
    }

    $title = 'Display SEP Mandiri';
    $logo  = $this->settings->get('settings.logo');

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $content = $this->draw('sep.mandiri.noka.norm.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_anjungan'),
      'no_rkm_medis' => $slug[3],
      'sep_response' => $sep_response
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function getSepMandiriBikin()
  {
    $slug = parseURL();

    $title = 'Display SEP Mandiri';
    $logo  = $this->settings->get('settings.logo');
    $kode_ppk  = $this->settings->get('settings.ppk_bpjs');
    $nama_ppk  = $this->settings->get('settings.nama_instansi');

    $_username = $this->core->getUserInfo('fullname', null, true);
    $tanggal       = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
    $username      = !empty($_username) ? $_username : $this->core->getUserInfo('username');

    $date = date('Y-m-d');

    //if ($searchBy == 'RS') {
    //    $url = 'Rujukan/RS/'.$slug[3];
    //} else {
    $url = 'Rujukan/' . $slug[3];
    //}
    $url = $this->api_url . '' . $url;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key);
    $json = json_decode($output, true);
    //var_dump($json);
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($this->settings->get('settings.BpjsConsID'), $this->settings->get('settings.BpjsSecretKey'), $json['response']);
    $decompress = '""';
    //$rujukan = [];
    if ($code == "200") {
      $decompress = decompress($stringDecrypt);
      $rujukan = json_decode($decompress, true);
    }

    $reg_periksa = $this->db('reg_periksa')
      ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
      ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
      ->where('reg_periksa.tgl_registrasi', $date)
      ->where('reg_periksa.no_rkm_medis', $slug[4])
      ->oneArray();

    $skdp_bpjs = $this->db('skdp_bpjs')->where('no_rkm_medis', $slug[4])->where('tanggal_datang', $date)->oneArray();

    $content = $this->draw('sep.mandiri.bikin.html', [
      'title' => $title,
      'logo' => $logo,
      'powered' => 'Powered by <a href="https://basoro.org/">KhanzaLITE</a>',
      'username' => $username,
      'tanggal' => $tanggal,
      'running_text' => $this->settings->get('anjungan.text_anjungan'),
      'kode_ppk' => $kode_ppk,
      'nama_ppk' => $nama_ppk,
      'reg_periksa' => $reg_periksa,
      'skdp_bpjs' => $skdp_bpjs,
      'rujukan' => $rujukan
    ]);

    $assign = [
      'title' => $this->settings->get('settings.nama_instansi'),
      'desc' => $this->settings->get('settings.alamat'),
      'content' => $content
    ];

    $this->setTemplate("canvas.html");

    $this->tpl->set('page', ['title' => $assign['title'], 'desc' => $assign['desc'], 'content' => $assign['content']]);
  }

  public function postSaveSEP()
  {
    $_POST['kdppkpelayanan'] = $this->settings->get('settings.ppk_bpjs');
    $_POST['nmppkpelayanan'] = $this->settings->get('settings.nama_instansi');
    $_POST['sep_user']  = 'SEP Mandiri';

    $data = [
      'request' => [
        't_sep' => [
          'noKartu' => $_POST['no_kartu'],
          'tglSep' => $_POST['tglsep'],
          'ppkPelayanan' => $_POST['kdppkpelayanan'],
          'jnsPelayanan' => $_POST['jnspelayanan'],
          'klsRawat' => $_POST['klsrawat'],
          'noMR' => $_POST['nomr'],
          'rujukan' => [
            'asalRujukan' => $_POST['asal_rujukan'],
            'tglRujukan' => $_POST['tglrujukan'],
            'noRujukan' => $_POST['norujukan'],
            'ppkRujukan' => $_POST['kdppkrujukan']
          ],
          'catatan' => $_POST['catatan'],
          'diagAwal' => $_POST['diagawal'],
          'poli' => [
            'tujuan' => $_POST['kdpolitujuan'],
            'eksekutif' => $_POST['eksekutif']
          ],
          'cob' => [
            'cob' => $_POST['cob']
          ],
          'katarak' => [
            'katarak' => $_POST['katarak']
          ],
          'jaminan' => [
            'lakaLantas' => $_POST['lakalantas'],
            'penjamin' => [
              'penjamin' => $_POST['penjamin'],
              'tglKejadian' => $_POST['tglkkl'],
              'keterangan' => $_POST['keterangankkl'],
              'suplesi' => [
                'suplesi' => $_POST['suplesi'],
                'noSepSuplesi' => $_POST['no_sep_suplesi'],
                'lokasiLaka' => [
                  'kdPropinsi' => $_POST['kdprop'],
                  'kdKabupaten' => $_POST['kdkab'],
                  'kdKecamatan' => $_POST['kdkec']
                ]
              ]
            ]
          ],
          'skdp' => [
            'noSurat' => $_POST['noskdp'],
            'kodeDPJP' => $_POST['kddpjp']
          ],
          'noTelp' => $_POST['notelep'],
          'user' => $_POST['sep_user']
        ]
      ]
    ];

    $data = json_encode($data);

    $url = $this->api_url . 'SEP/1.1/insert';
    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key);
    $data = json_decode($output, true);

    if ($data == NULL) {

      echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';
    } else if ($data['metaData']['code'] == 200) {

      $_POST['sep_no_sep'] = $data['response']['sep']['noSep'];

      $simpan_sep = $this->db('bridging_sep')->save([
        'no_sep' => $_POST['sep_no_sep'],
        'no_rawat' => $_POST['no_rawat'],
        'tglsep' => $_POST['tglsep'],
        'tglrujukan' => $_POST['tglrujukan'],
        'no_rujukan' => $_POST['norujukan'],
        'kdppkrujukan' => $_POST['kdppkrujukan'],
        'nmppkrujukan' => $_POST['nmppkrujukan'],
        'kdppkpelayanan' => $_POST['kdppkpelayanan'],
        'nmppkpelayanan' => $_POST['nmppkpelayanan'],
        'jnspelayanan' => $_POST['jnspelayanan'],
        'catatan' => $_POST['catatan'],
        'diagawal' => $_POST['diagawal'],
        'nmdiagnosaawal' => $_POST['nmdiagnosaawal'],
        'kdpolitujuan' => $_POST['kdpolitujuan'],
        'nmpolitujuan' => $_POST['nmpolitujuan'],
        'klsrawat' => $_POST['klsrawat'],
        'lakalantas' => $_POST['lakalantas'],
        'user' => $_POST['sep_user'],
        'nomr' => $_POST['nomr'],
        'nama_pasien' => $_POST['nama_pasien'],
        'tanggal_lahir' => $_POST['tanggal_lahir'],
        'peserta' => $_POST['peserta'],
        'jkel' => $_POST['jenis_kelamin'],
        'no_kartu' => $_POST['no_kartu'],
        'tglpulang' => $_POST['tglpulang'],
        'asal_rujukan' => $_POST['asal_rujukan'],
        'eksekutif' => $_POST['eksekutif'],
        'cob' => $_POST['cob'],
        'penjamin' => $_POST['penjamin'],
        'notelep' => $_POST['notelep'],
        'katarak' => $_POST['katarak'],
        'tglkkl' => $_POST['tglkkl'],
        'keterangankkl' => $_POST['keterangankkl'],
        'suplesi' => $_POST['suplesi'],
        'no_sep_suplesi' => $_POST['no_sep_suplesi'],
        'kdprop' => $_POST['kdprop'],
        'nmprop' => $_POST['nmprop'],
        'kdkab' => $_POST['kdkab'],
        'nmkab' => $_POST['nmkab'],
        'kdkec' => $_POST['kdkec'],
        'nmkec' => $_POST['nmkec'],
        'noskdp' => $_POST['noskdp'],
        'kddpjp' => $_POST['kddpjp'],
        'nmdpdjp' => $_POST['nmdpdjp']
      ]);
      $simpan_prb = $this->db('bpjs_prb')->save([
        'no_sep' => $_POST['sep_no_sep'],
        'prb' => $_POST['prolanis_prb']
      ]);

      if ($simpan_sep) {
        echo $_POST['sep_no_sep'];
      }
    } else {

      echo $data['metaData']['message'];
    }

    exit();
  }

  public function getCetakSEP()
  {
    $slug = parseURL();
    $no_sep = $slug[3];
    $settings = $this->settings('settings');
    $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
    $data_sep = $this->db('bridging_sep')->where('no_sep', $no_sep)->oneArray();
    $batas_rujukan = strtotime('+87 days', strtotime($data_sep['tglrujukan']));

    $qr = QRCode::getMinimumQRCode($data_sep['no_sep'], QR_ERROR_CORRECT_LEVEL_L);
    //$qr=QRCode::getMinimumQRCode('Petugas: '.$this->core->getUserInfo('fullname', null, true).'; Lokasi: '.UPLOADS.'/invoices/'.$result['kd_billing'].'.pdf',QR_ERROR_CORRECT_LEVEL_L);
    $im = $qr->createImage(4, 4);
    imagepng($im, BASE_DIR . '/tmp/qrcode.png');
    imagedestroy($im);

    $image = "/tmp/qrcode.png";

    $data_sep['qrCode'] = url($image);
    $data_sep['batas_rujukan'] = date('Y-m-d', $batas_rujukan);
    $potensi_prb = $this->db('bpjs_prb')->where('no_sep', $no_sep)->oneArray();
    $data_sep['potensi_prb'] = $potensi_prb['prb'];

    echo $this->draw('cetak.sep.html', ['data_sep' => $data_sep]);
    exit();
  }

  public function cekDataPasien($norm, $nobpjs, $nik)
  {
    $get_data = $this->db('pasien')->select('no_peserta')->select('no_ktp')->where('no_rkm_medis', $norm)->oneArray();
    if ($get_data['no_ktp'] != $nik) {
      $this->db('pasien')->where('no_rkm_medis', $norm)->save(['no_ktp' => $nik]);
    }
    if ($get_data['no_peserta'] != $nobpjs) {
      $this->db('pasien')->where('no_rkm_medis', $norm)->save(['no_peserta' => $nobpjs]);
    }
    return true;
  }

  public function checkNoSEP($nosep)
  {
    $result = $this->db('bridging_sep')->where('no_rujukan', $nosep)->oneArray();
    if (!empty($result)) {
      return false;
    } else {
      return true;
    }
  }


  //Begin code post data ke WS BPJS
  public function updateJadwalDokterBPJS($kodepoli, $kodesubspesialis, $kodedokter, $hari, $buka, $tutup)
  {
    $request = array(
      "kodepoli" => $kodepoli,
      "kodesubspesialis" => $kodesubspesialis,
      "kodedokter" => $kodedokter,
      "waktu" => array(
        "hari" => $hari,
        "buka" => $buka,
        "tutup" => $tutup
      )
    );
    // $request = '{
    //               "kodepoli": "' . $kodepoli . '",
    //               "kodesubspesialis": "' . $kodesubspesialis . '",
    //               "kodedokter": "' . $kodedokter . '",
    //               "jadwal": {
    //                   "hari": "' . $hari . '",
    //                   "buka": "' . $buka . '",
    //                   "tutup": "' . $tutup . '"
    //                 }
    //             }';

    return $request;
  }

  public function tambahAntreanBPJS(
    $kodebooking,
    $jenispasien,
    $nomorkartu,
    $nik,
    $nohp,
    $kodepoli,
    $namapoli,
    $pasienbaru,
    $norm,
    $tanggalperiksa,
    $kodedokter,
    $namadokter,
    $jampraktek,
    $jeniskunjungan,
    $nomorreferensi,
    $nomorantrean,
    $angkaantrean,
    $estimasidilayani,
    $sisakuotajkn,
    $kuotajkn,
    $sisakuotanonjkn,
    $kuotanonjkn,
    $keterangan
  ) {
    $request = array(
      "kodebooking" => $kodebooking,
      "jenispasien" => $jenispasien,
      "nomorkartu" => $nomorkartu,
      "nik" => $nik,
      "nohp" => $nohp,
      "kodepoli" => $kodepoli,
      "namapoli" => $namapoli,
      "pasienbaru" => $pasienbaru,
      "norm" => $norm,
      "tanggalperiksa" => $tanggalperiksa,
      "kodedokter" => $kodedokter,
      "namadokter" => $namadokter,
      "jampraktek" => $jampraktek,
      "jeniskunjungan" => $jeniskunjungan,
      "nomorreferensi" => $nomorreferensi,
      "nomorantrean" => $nomorantrean,
      "angkaantrean" => $angkaantrean,
      "estimasidilayani" => $estimasidilayani,
      "sisakuotajkn" => $sisakuotajkn,
      "kuotajkn" => $kuotajkn,
      "sisakuotanonjkn" => $sisakuotanonjkn,
      "kuotanonjkn" => $kuotanonjkn,
      "keterangan" => $keterangan
    );
    // $request = '{
    //                 "kodebooking": "' . $kodebooking . '",
    //                 "jenispasien": "' . $jenispasien . '",
    //                 "nomorkartu": "' . $nomorkartu . '",
    //                 "nik": "' . $nik . '",
    //                 "nohp": "' . $nohp . '",
    //                 "kodepoli": "' . $kodepoli . '",
    //                 "namapoli": "' . $namapoli . '",
    //                 "pasienbaru": ' . $pasienbaru . ',
    //                 "norm": "' . $norm . '",
    //                 "tanggalperiksa": "' . $tanggalperiksa . '",
    //                 "kodedokter": ' . $kodedokter . ',
    //                 "namadokter": "' . $namadokter . '",
    //                 "jampraktek": "' . $jampraktek . '",
    //                 "jeniskunjungan": ' . $jeniskunjungan . ',
    //                 "nomorreferensi": "' . $nomorreferensi . '",
    //                 "nomorantrean": "' . $nomorantrean . '",
    //                 "angkaantrean": ' . $angkaantrean . ',
    //                 "estimasidilayani": ' . $estimasidilayani . ',
    //                 "sisakuotajkn": ' . $sisakuotajkn . ',
    //                 "kuotajkn": ' . $kuotajkn . ',
    //                 "sisakuotanonjkn": ' . $sisakuotanonjkn . ',
    //                 "kuotanonjkn": ' . $kuotanonjkn . ',
    //                 "keterangan": "' . $keterangan . '"
    //             }';

    return $request;
  }

  public function updateWaktuAntreanBPJS($kodebooking, $taskid)
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
    $request = array(
      "kodebooking" => $kodebooking,
      "taskid" => $taskid,
      "waktu" => $waktu
    );
    // $request = '{
    //                 "kodebooking": "' . $kodebooking . '",
    //                 "taskid": ' . $taskid . ',
    //                 "waktu": ' . $waktu . '
    //             }';

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

  public function getDataWSBPJS($path)
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

    $url = 'https://apijkn.bpjs-kesehatan.go.id/antreanrs/' . $path;
    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'getDataWSBPJS',
    //   'value' => 'X-Cons-ID ' . $consid . ' ;X-Timestamp ' . $tStamp . ' ;X-Signature ' . $encodedSignature . ' ;user_key ' . $user_key
    // ]);
    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'getDataWSBPJS',
    //   'value' => $url
    // ]);
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
    $decryptData = $this->stringDecrypt($consid . $secretKey . $tStamp, $obj['response']);
    $decompressData = $this->decompress($decryptData);
    $data['metadata'] = $obj['metadata'];
    $data['response'] = $decompressData;

    curl_close($ch);
    return $data;
  }

  public function getRujukanWSBPJS($url)
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
    // $tglAkhir = date('Y-m-d');
    // $tglMulai = date('Y-m-d', strtotime('-90 days'));

    // $url = 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/Rujukan/' . $path . 'List/Peserta/' . $no_peserta;
    // $url = 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/monitoring/HistoriPelayanan/NoKartu/' . $no_peserta . '/tglMulai/' . $tglMulai . '/tglAkhir/' . $tglAkhir;
    // echo $url;

    $header = array(
      'Accept: application/json',
      'X-Cons-ID: ' . $consid,
      'X-Timestamp: ' . $tStamp,
      'X-Signature: ' . $encodedSignature,
      'user_key: ' . $user_key
    );
    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'getRujukanWSBPJS_url',
    //   'value' => $url
    // ]);
    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'getRujukanWSBPJS_header',
    //   'value' => 'X-Cons-ID ' . $consid . ' ;X-Timestamp ' . $tStamp . ' ;X-Signature ' . $encodedSignature . ' ;user_key ' . $user_key
    // ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');

    $res = curl_exec($ch);
    //$result = file_get_contents($res);
    $obj = json_decode($res, true);
    $decryptData = $this->stringDecrypt($consid . $secretKey . $tStamp, $obj['response']);
    $decompressData = $this->decompress($decryptData);
    $data['metaData'] = $obj['metaData'];
    $data['response'] = $decompressData;

    curl_close($ch);

    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'check-getRujukanWSBPJS data',
    //   'value' => $res
    // ]);
    return $data;
  }

  public function getRujukanWSVClaim($path, $no_peserta)
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
    $tglAkhir = date('Y-m-d');
    $tglMulai = date('Y-m-d', strtotime('-90 days'));

    $url = 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/Rujukan/' . $path . 'List/Peserta/' . $no_peserta;
    // $url = 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/monitoring/HistoriPelayanan/NoKartu/' . $no_peserta . '/tglMulai/' . $tglMulai . '/tglAkhir/' . $tglAkhir;
    // echo $url;
    $header = array(
      'Accept: application/json',
      'X-Cons-ID: ' . $consid,
      'X-Timestamp: ' . $tStamp,
      'X-Signature: ' . $encodedSignature,
      'user_key: ' . $user_key
    );
    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'getRujukanWSBPJS_url',
    //   'value' => $url
    // ]);
    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'getRujukanWSBPJS_header',
    //   'value' => 'X-Cons-ID ' . $consid . ' ;X-Timestamp ' . $tStamp . ' ;X-Signature ' . $encodedSignature . ' ;user_key ' . $user_key
    // ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');

    $res = curl_exec($ch);
    //$result = file_get_contents($res);
    $obj = json_decode($res, true);
    $decryptData = $this->stringDecrypt($consid . $secretKey . $tStamp, $obj['response']);
    $decompressData = $this->decompress($decryptData);
    $data['metaData'] = $obj['metaData'];
    $data['response'] = $decompressData;

    curl_close($ch);

    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'check-getRujukanWSBPJS data',
    //   'value' => $res
    // ]);
    return $data;
  }
  //end of WS BPJS

  //start WS RS
  public function ambilAntreanRS($nomorkartu, $nik, $nohp, $kodepoli, $norm, $tanggalperiksa, $kodedokter, $jampraktek, $jeniskunjungan, $nomorreferensi)
  {
    $request = array(
      "nomorkartu" => $nomorkartu,
      "nik" => $nik,
      "nohp" => $nohp,
      "kodepoli" => $kodepoli,
      "norm" => $norm,
      "tanggalperiksa" => $tanggalperiksa,
      "kodedokter" => $kodedokter,
      "jampraktek" => $jampraktek,
      "jeniskunjungan" => $jeniskunjungan,
      "nomorreferensi" => $nomorreferensi
    );
    // $request = '{
    //               "nomorkartu": "' . $nomorkartu . '",
    //               "nik": "' . $nik . '",
    //               "nohp": "' . $nohp . '",
    //               "kodepoli": "' . $kodepoli . '",
    //               "norm": "' . $norm . '",
    //               "tanggalperiksa": "' . $tanggalperiksa . '",
    //               "kodedokter": "' . $kodedokter . '",
    //               "jampraktek": "' . $jampraktek . '",
    //               "jeniskunjungan": "' . $jeniskunjungan . '",
    //               "nomorreferensi": "' . $nomorreferensi . '"
    //           }';

    return $request;
  }

  public function checkinAntreanRS($kodebooking)
  {
    date_default_timezone_set('Asia/Jakarta');
    $waktu = strtotime(date("Y-m-d H:i:s")) * 1000;
    $request = array(
      "kodebooking" => $kodebooking,
      "waktu" => $waktu
    );

    // $request = '{
    //               "kodebooking": "' . $kodebooking . '",
    //               "waktu": ' . $waktu . '
    //           }';

    return $request;
  }

  public function batalAntreanRS($kodebooking, $keterangan)
  {
    $request = array(
      "kodebooking" => $kodebooking,
      "keterangan" => $keterangan
    );

    // '{
    //             "kodebooking": "' . $kodebooking . '",
    //             "keterangan": ' . $keterangan . '
    //         }';

    return $request;
  }

  public function getTokenWSRS()
  {
    // $url = 'http://192.168.14.27/webapps/api-bpjsfktl/auth';
    $url = 'https://rssoepraoen.com/webapps/api-bpjsfktl/auth';
    // $url = 'https://rssoepraoen.simkeskhanza.com/webapps/api-bpjsfktl/auth';
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
    if ($data) {
      return $data['response']['token'];
    }
  }

  public function sendDataWSRS($path, $data)
  {
    date_default_timezone_set('Asia/Jakarta');
    $token = $this->getTokenWSRS();
    $username = "bridging_rstds";

    // $this->db('mlite_settings')->save([
    //   'module' => 'debug',
    //   'field' => 'sendDataWSRS token RS',
    //   'value' => $username . '  |  ' . $token
    // ]);

    // $this->core->db()->pdo()->exec("INSERT INTO `mlite_settings` (module, field, value) 
    //       VALUES ('debug', 'sendDataWSRS token RS','" . $username . "  |  " . $token . "')");

    //begin post data
    // API URL
    // $url = 'https://rssoepraoen.simkeskhanza.com/webapps/api-bpjsfktl/' . $path;
    $url = 'https://rssoepraoen.com/webapps/api-bpjsfktl/' . $path;
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

  //begin function decrypt
  function stringDecrypt($key, $string)
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
