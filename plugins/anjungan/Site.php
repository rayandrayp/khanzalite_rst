<?php

namespace Plugins\Anjungan;

use Systems\SiteModule;
use Systems\Lib\BpjsService;
use Systems\Lib\QRCode;

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
    $this->route('anjungan/apotek', 'getDisplayAntrianApotek');
    $this->route('anjungan/ajax', 'getAjax');
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
    $strQuery = "SELECT a.kd_dokter, a.kd_poli, b.nm_poli, c.nm_dokter, a.jam_mulai, a.jam_selesai FROM jadwal a, poliklinik b, dokter c WHERE a.kd_poli = b.kd_poli AND a.kd_dokter = c.kd_dokter AND a.hari_kerja = '$hari'  AND a.kd_poli IN ('$poliklinik')";
    // echo "<script>console.log($strQuery)</script>";
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
      $query = $this->db()->pdo()->prepare("SELECT a.kd_dokter, a.kd_poli, b.nm_poli, c.nm_dokter, a.jam_mulai, a.jam_selesai FROM jadwal a, poliklinik b, dokter c WHERE a.kd_poli = b.kd_poli AND a.kd_dokter = c.kd_dokter AND a.hari_kerja = '$hari'");
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
        $loket = explode(",", $this->settings->get('anjungan.antrian_loket'));
        $get_antrian = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('postdate', date('Y-m-d'))->desc('start_time')->oneArray();
        $noantrian = 0;
        if (!empty($get_antrian['noantrian'])) {
          $noantrian = $get_antrian['noantrian'];
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
          $this->db('mlite_antrian_loket')
            ->where('type', 'Loket')
            ->where('noantrian', $tcounter)
            ->where('postdate', date('Y-m-d'))
            ->save(['end_time' => date('H:i:s')]);
          /*$this->db()->pdo()->exec("DELETE FROM `antriloket`");
              $this->db('antriloket')->save([
                'loket' => $_GET['loket'],
                'antrian' => $_tcounter
              ]);*/
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket')->save(['value' => $_GET['loket']]);
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => $_tcounter]);
        }
        if (isset($_GET['antrian'])) {
          /*$this->db()->pdo()->exec("DELETE FROM `antriloket`");
              $this->db('antriloket')->save([
                'loket' => $_GET['reset'],
                'antrian' => $_GET['antrian']
              ]);*/
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket')->save(['value' => $_GET['reset']]);
          $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => $_GET['antrian']]);
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
    $sql = $this->db()->pdo()->prepare("SELECT * FROM mlite_antrian_loket WHERE status = 1 AND postdate = '$date' ORDER BY noantrian ASC");
    $sql->execute();
    $data = $sql->fetchAll(\PDO::FETCH_OBJ);
    if ($data) {
      //$data  = $query->fetch_object();

      //print_r($data);
      // code...
      switch (strtolower($data[0]->type)) {
        case 'loket':
          $kode = 'a';
          break;
        case 'cs':
          $kode = 'b';
          break;
        default:
          $kode = 'ahhay';
          break;
      }

      //$terbilang = Terbilang::convert($data->noantrian);
      $terbilang = strtolower(terbilang($data[0]->noantrian));
      $loket = strtolower(terbilang($data[0]->loket));
      $text = "antrian $kode $terbilang counter $loket";

      $res = [
        'id' => $data[0]->kd,
        'status' => true,
        'type' => $data[0]->type,
        'kode' => $kode,
        'noantrian' => $data[0]->noantrian,
        'loket' => $data[0]->loket,
        'panggil' => explode(" ", $text)
      ];
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
    }
    $noantrian  = $_GET['noantrian'];
    $loket  = $_GET['loket'];
    $date = date('Y-m-d');
    $query = $this->db('mlite_antrian_loket')->where('type', $type)->where('noantrian', $noantrian)->where('postdate', $date)->update(['status' => 1, 'loket' => $loket]);
    if ($query) {
      $res = [
        'status' => true,
        'message' => 'Berhasil update' . $date,
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
        $this->db('mlite_antrian_loket')
          ->save([
            'kd' => NULL,
            'type' => 'Loket',
            'noantrian' => $_GET['noantrian'],
            'postdate' => date('Y-m-d'),
            'start_time' => date('H:i:s'),
            'end_time' => '00:00:00'
          ]);
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
        if (!empty($_POST['no_rkm_medis_daftar'])) {
          $data = array();
          $query = $this->db('pasien')
            ->where('no_rkm_medis', $_POST['no_rkm_medis_daftar'])
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
          if ($this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tgl_registrasi', $_POST['tgl_registrasi'])->oneArray()) {
            $data['status'] = 'exist';
            $data['result'] = '';
            echo json_encode($data);
          } else {
            $tanggal = $_POST['tgl_registrasi'];
            $tentukan_hari = date('D', strtotime($tanggal));
            $day = array('Sun' => 'AKHAD', 'Mon' => 'SENIN', 'Tue' => 'SELASA', 'Wed' => 'RABU', 'Thu' => 'KAMIS', 'Fri' => 'JUMAT', 'Sat' => 'SABTU');
            $hari = $day[$tentukan_hari];

            $strQuery = "SELECT * FROM poliklinik WHERE kd_poli IN (SELECT DISTINCT kd_poli FROM jadwal WHERE hari_kerja LIKE '$hari')";
            $query = $this->db()->pdo()->prepare($strQuery);
            $query->execute();
            $rows = $query->fetchAll(\PDO::FETCH_ASSOC);
            // $query = $this->db('jadwal')
            //   ->select(['kd_poli' => 'jadwal.kd_poli'])
            //   ->select(['nm_poli' => 'poliklinik.nm_poli'])
            //   ->select(['jam_mulai' => 'jadwal.jam_mulai'])
            //   ->select(['jam_selesai' => 'jadwal.jam_selesai'])
            //   ->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli')
            //   ->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')
            //   ->like('jadwal.hari_kerja', $hari)
            //   ->toArray();
            if (!empty($rows)) {
              $data['status'] = 'ok';
              $data['result'] = $rows;
            } else {
              $data['status'] = 'err';
              $data['result'] = '';
            }
            echo json_encode($data);
          }
        }
        break;

      case "get-dokter":
        if (!empty($_POST['kd_poli'])) {
          $tanggal = $_POST['tgl_registrasi'];
          $tentukan_hari = date('D', strtotime($tanggal));
          $day = array('Sun' => 'AKHAD', 'Mon' => 'SENIN', 'Tue' => 'SELASA', 'Wed' => 'RABU', 'Thu' => 'KAMIS', 'Fri' => 'JUMAT', 'Sat' => 'SABTU');
          $hari = $day[$tentukan_hari];
          $data = array();
          $result = $this->db('jadwal')
            ->select(['kd_dokter' => 'jadwal.kd_dokter'])
            ->select(['nm_dokter' => 'dokter.nm_dokter'])
            ->select(['kuota' => 'jadwal.kuota'])
            ->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli')
            ->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')
            ->where('jadwal.kd_poli', $_POST['kd_poli'])
            ->like('jadwal.hari_kerja', $hari)
            ->oneArray();
          $check_kuota = $this->db('reg_periksa')
            ->select(['count' => 'COUNT(DISTINCT no_rawat)'])
            ->where('kd_poli', $_POST['kd_poli'])
            ->where('tgl_registrasi', $_POST['tgl_registrasi'])
            ->oneArray();
          $curr_count = $check_kuota['count'];
          $curr_kuota = $result['kuota'];
          $online = $curr_kuota / 2;
          if ($curr_count > $online) {
            $data['status'] = 'limit';
          } else {
            $query = $this->db('jadwal')
              ->select(['kd_dokter' => 'jadwal.kd_dokter'])
              ->select(['nm_dokter' => 'dokter.nm_dokter'])
              ->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli')
              ->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')
              ->where('jadwal.kd_poli', $_POST['kd_poli'])
              ->like('jadwal.hari_kerja', $hari)
              ->toArray();
            if (!empty($query)) {
              $data['status'] = 'ok';
              $data['result'] = $query;
            } else {
              $data['status'] = 'err';
              $data['result'] = '';
            }
            echo json_encode($data);
          }
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

      case "post-registrasi":
        if (!empty($_POST['no_rkm_medis'])) {

          $data = array();
          $date = date('Y-m-d');

          $_POST['no_reg']     = $this->core->setNoReg($_POST['kd_dokter'], $_POST['kd_poli']);
          $_POST['hubunganpj'] = $this->core->getPasienInfo('keluarga', $_POST['no_rkm_medis']);
          $_POST['almt_pj']    = $this->core->getPasienInfo('alamat', $_POST['no_rkm_medis']);
          $_POST['p_jawab']    = $this->core->getPasienInfo('namakeluarga', $_POST['no_rkm_medis']);
          $_POST['stts']       = 'Belum';

          $cek_stts_daftar = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->count();
          $_POST['stts_daftar'] = 'Baru';
          if ($cek_stts_daftar > 0) {
            $_POST['stts_daftar'] = 'Lama';
          }

          $biaya_reg = $this->db('poliklinik')->where('kd_poli', $_POST['kd_poli'])->oneArray();
          $_POST['biaya_reg'] = $biaya_reg['registrasi'];
          if ($_POST['stts_daftar'] == 'Lama') {
            $_POST['biaya_reg'] = $biaya_reg['registrasilama'];
          }

          $cek_status_poli = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('kd_poli', $_POST['kd_poli'])->count();
          $_POST['status_poli'] = 'Baru';
          if ($cek_status_poli > 0) {
            $_POST['status_poli'] = 'Lama';
          }

          $tanggal = new \DateTime($this->core->getPasienInfo('tgl_lahir', $_POST['no_rkm_medis']));
          $today = new \DateTime($date);
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

          $_POST['umurdaftar'] = $umur;
          $_POST['sttsumur'] = $sttsumur;
          $_POST['status_lanjut']   = 'Ralan';
          // $_POST['kd_pj']           = $this->settings->get('anjungan.carabayar_umum');
          $_POST['status_bayar']    = 'Belum Bayar';
          $_POST['no_rawat'] = $this->core->setNoRawat($date);
          $_POST['jam_reg'] = date('H:i:s');

          $query = $this->db('reg_periksa')->save($_POST);

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
            ->where('reg_periksa.tgl_registrasi', $_POST['tgl_registrasi'])
            ->where('reg_periksa.no_rkm_medis', $_POST['no_rkm_medis'])
            ->oneArray();

          if (!empty($result)) {
            $data['status'] = 'ok';
            $data['result'] = $result;
          } else {
            $data['status'] = 'err';
            $data['result'] = 'Registrasi gagal.';
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
}
