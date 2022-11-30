<?php

namespace Plugins\Manajemen;

use Systems\AdminModule;
use Systems\Lib\QueryWrapper;

class Admin extends AdminModule
{

    public function navigation()
    {
        return [
            'Kelola'   => 'dashboard',
            'Pengaturan' => 'settings'
        ];
    }

    public function getDashboard()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));

        $settings = htmlspecialchars_array($this->settings('manajemen'));

        //Bagian Ralan
        $dataKunjungan = $this->countKunjungan();
        $visiteCurrYear = $dataKunjungan['tahunini'];
        $visiteLastYear = $dataKunjungan['tahunlalu'];
        $visiteCurrMonth = $dataKunjungan['bulanini'];
        $visiteLastMonth = $dataKunjungan['bulanlalu'];
        $visiteCurrDays = $dataKunjungan['hariini'];
        $visiteLastDays = $dataKunjungan['harilalu'];
        $stats['getYearVisitiesRalan'] = number_format($visiteCurrYear, 0, '', '.');
        $stats['getMonthVisitiesRalan'] = number_format($visiteCurrMonth, 0, '', '.');
        $stats['getDayVisitiesRalan'] = number_format($visiteCurrDays, 0, '', '.');
        $stats['getLastYearVisities'] = number_format($visiteLastYear, 0, '', '.');
        $stats['getLastMonthVisities'] = number_format($visiteLastMonth, 0, '', '.');
        $stats['getLastCurrentVisities'] = number_format($visiteLastDays, 0, '', '.');
        $stats['percentYearRalan'] = 0;
        if ($stats['getYearVisitiesRalan'] != 0) {
            $stats['percentYearRalan'] = number_format((($visiteCurrYear - $visiteLastYear) / $visiteCurrYear) * 100, 0, '', '.');
        }
        $stats['percentMonthRalan'] = 0;
        if ($stats['getMonthVisitiesRalan'] != 0) {
            $stats['percentMonthRalan'] = number_format((($visiteCurrMonth - $visiteLastMonth) / $visiteCurrMonth) * 100, 0, '', '.');
        }
        $stats['percentDaysRalan'] = 0;
        if ($stats['getDayVisitiesRalan'] != 0) {
            $stats['percentDaysRalan'] = number_format((($visiteCurrDays - $visiteLastDays) /  $visiteCurrDays) * 100, 0, '', '.');
        }

        $stats['poliChartRalan'] = $this->poliChart();


        // $stats['KunjunganTahunChart'] = $this->KunjunganTahunChart();
        // $stats['RanapTahunChart'] = $this->RanapTahunChart();
        // $stats['RujukTahunChart'] = $this->RujukTahunChart();

        //Bagian Rawat Inap

        $stats['getDayVisitiesRanap'] = number_format($this->countTodayRanap(), 0, '', '.');
        //perhitungan bor los toi
        $lamaInapBulan = $this->getLamaInap(date('Y-m', strtotime('-1 month')));
        $jumlahPasien = $this->getJumlahPasienInap(date('Y-m', strtotime('-1 month')));
        $jumlahBed = $this->getJumlahBed();
        $jmlhari = date('t', strtotime('-1 month'));

        $borBulan = ($lamaInapBulan / ($jumlahBed * $jmlhari)) * 100;
        $alos = $lamaInapBulan / $jumlahPasien;
        $toi = (($jumlahBed * $jmlhari) - $lamaInapBulan) / $jumlahPasien;
        $bto = $jumlahPasien / $jumlahBed;
        $stats['getBORBulan'] = number_format($borBulan, 2, ',', '.');
        $stats['getALOS'] = number_format($alos, 2, ',', '.');
        $stats['getTOI'] = number_format($toi, 2, ',', '.');
        $stats['getBTO'] = number_format($bto, 2, ',', '.');

        $stats['poliChartRanap'] = $this->countKamarInap();

        //Diagnosa
        $stats['chartDiagnosaRalanBulan'] = $this->countDx('Ralan', date('Y-m', strtotime('-1 month')));
        $stats['chartDiagnosaRalanHari'] = $this->countDx('Ralan', date('Y-m-d'));
        $stats['chartDiagnosaRanapBulan'] = $this->countDx2(date('Y-m', strtotime('-1 month')));
        $stats['chartDiagnosaRanapHari'] = $this->countDx('Ranap', date('Y-m-d'));

        //Bagian Keuangan
        $revenueBulanIni = $this->getRevenueRIRJ(date('Y-m'));
        $revenueBulanLalu = $this->getRevenueRIRJ(date('Y-m', strtotime('-1 month')));
        $revenueAllBulanIni = $revenueBulanIni['Ralan'] + $revenueBulanIni['Ranap'];
        $revenueAllBulanLalu = $revenueBulanLalu['Ralan'] + $revenueBulanLalu['Ranap'];
        $stats['getTotalRevenueMonth'] = number_format($revenueAllBulanIni, 0, '', '.');
        $stats['percentRevenueMonth'] = 0;
        if ($stats['getTotalRevenueMonth'] != 0) {
            $stats['percentRevenueMonth'] = number_format((($revenueAllBulanIni - $revenueAllBulanLalu) / $revenueAllBulanIni) * 100, 0, '', '.');
        }
        $stats['getRevenueRJMonth'] = number_format($revenueBulanIni['Ralan'], 0, '', '.');
        $stats['percentRevenueRJMonth'] = 0;
        if ($stats['getRevenueRJMonth'] != 0) {
            $stats['percentRevenueRJMonth'] = number_format((($revenueBulanIni['Ralan'] - $revenueBulanLalu['Ralan']) / $revenueBulanIni['Ralan']) * 100, 0, '', '.');
        }
        $stats['getRevenueRIMonth'] = number_format($revenueBulanIni['Ranap'], 0, '', '.');
        $stats['percentRevenueRIMonth'] = 0;
        if ($stats['getRevenueRIMonth'] != 0) {
            $stats['percentRevenueRIMonth'] = number_format((($revenueBulanIni['Ranap'] - $revenueBulanLalu['Ranap'])  / $revenueBulanIni['Ranap']) * 100, 0, '', '.');
        }

        $day = array(
            'Sun' => 'AKHAD',
            'Mon' => 'SENIN',
            'Tue' => 'SELASA',
            'Wed' => 'RABU',
            'Thu' => 'KAMIS',
            'Fri' => 'JUMAT',
            'Sat' => 'SABTU'
        );
        $hari = $day[date('D', strtotime(date('Y-m-d')))];
        // var_dump($stats);
        return $this->draw('dashboard.html', [
            'settings' => $settings,
            'stats' => $stats,
            'pasien' => $this->db('pasien')->join('penjab', 'penjab.kd_pj = pasien.kd_pj')->desc('tgl_daftar')->limit('5')->toArray(),
            'dokter' => $this->db('dokter')->join('spesialis', 'spesialis.kd_sps = dokter.kd_sps')->join('jadwal', 'jadwal.kd_dokter = dokter.kd_dokter')->where('jadwal.hari_kerja', $hari)->where('dokter.status', '1')->group('dokter.kd_dokter')->rand()->limit('6')->toArray()
        ]);
    }


    public function getRawatJalan()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));

        $settings = htmlspecialchars_array($this->settings('manajemen'));
        $dataRalan = $this->getDataPasienRalan();
        $ralanHariIniSemua = $dataRalan[0]['Belum'] + $dataRalan[0]['Sudah'] + $dataRalan[0]['Batal'];
        $ralanKemarinSemua = $dataRalan[1]['Belum'] + $dataRalan[1]['Sudah'] + $dataRalan[1]['Batal'];
        $stats['totalPasienRalan'] = number_format($ralanHariIniSemua, 0, '', '.');
        $stats['percentTotalPasienRalan'] = 0;
        if ($ralanHariIniSemua != 0) {
            $stats['percentTotalPasienRalan'] = number_format((100 + ((($ralanHariIniSemua -  $ralanKemarinSemua) / $ralanHariIniSemua) * 100)), 0, '', '.');
        }

        $dataPasienBaru = $this->getDataPasienBaru();
        $stats['pasienBaru'] = number_format($dataPasienBaru['hariini'], 0, '', '.');
        $stats['percentpasienBaru'] = 0;
        if ($stats['pasienBaru'] != 0) {
            $stats['percentpasienBaru'] = number_format((100 + ((($dataPasienBaru['hariini'] - $dataPasienBaru['kemarin']) / $dataPasienBaru['hariini']) * 100)), 0, '', '.');
        }
        $stats['pasienBatal'] = number_format($dataRalan[0]['Batal'], 0, '', '.');
        $stats['percentpasienBatal'] = 0;
        if ($stats['pasienBatal'] != 0) {
            $stats['percentpasienBatal'] = number_format((100 + ((($dataRalan[0]['Batal'] - $dataRalan[1]['Batal']) / $dataRalan[0]['Batal']) * 100)), 0, '', '.');
        }
        $stats['pasienSudah'] = number_format($dataRalan[0]['Sudah'], 0, '', '.');
        $stats['percentpasienSudah'] = 0;
        if ($stats['pasienSudah'] != 0) {
            $stats['percentpasienSudah'] = number_format((($dataRalan[0]['Sudah'] / $ralanHariIniSemua) * 100), 0, '', '.');
        }

        $stats['poliChartRalan'] = $this->poliChart();
        $stats['chartDiagnosaRalanTahun'] = $this->countDx('Ralan', date('Y'));
        $stats['chartDiagnosaRalanBulan'] = $this->countDx('Ralan', date('Y-m'));
        $stats['chartDiagnosaRalanHari'] = $this->countDx('Ralan', date('Y-m-d'));

        return $this->draw('rawatjalan.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function getRawatInap()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));

        $settings = htmlspecialchars_array($this->settings('manajemen'));
        $dataRanap = $this->countRanap();
        $stats['poliChart'] = $this->countKamarInap();
        $stats['getRanap'] = number_format($this->getRanapNow(), 0, '', '.');
        $stats['getRanapIn'] = number_format($dataRanap[1]['rawat'], 0, '', '.');
        $stats['getRanapOut'] = number_format($dataRanap[0]['rawat'], 0, '', '.');
        $stats['getRanapDead'] = number_format($dataRanap[0]['meninggal'], 0, '', '.');

        // $stats['percentTotal'] = 0;
        // if ($stats['getRanap'] != 0) {
        //     $stats['percentTotal'] = number_format((($stats['getRanap'] - $this->countVisiteNoRM()) / $stats['getRanap']) * 100, 0, '', '.');
        // }

        $stats['percentIn'] = 0;
        if ($stats['getRanapIn'] != 0) {
            $stats['percentIn'] = number_format((($stats['getRanapIn'] - $this->countLastRanap('tgl_masuk', '-')) / $stats['getRanapIn']) * 100, 0, '', '.');
        }

        $stats['percentOut'] = 0;
        if ($stats['getRanapOut'] != 0) {
            $stats['percentOut'] = number_format((($stats['getRanapOut'] - $this->countLastRanap('tgl_keluar', array('APS', 'Membaik'))) / $stats['getRanapOut']) * 100, 0, '', '.');
        }

        $stats['percentDead'] = 0;
        if ($stats['getRanapDead'] != 0) {
            $stats['percentDead'] = number_format((($stats['getRanapDead'] - $this->countLastRanap('tgl_keluar', 'Meninggal')) / $stats['getRanapDead']) * 100, 0, '', '.');
        }

        //perhitungan bor los toi
        $lamaInapHari = $this->getLamaInap(date('Y-m-d'));
        $lamaInapBulan = $this->getLamaInap(date('Y-m', strtotime('-1 month')));
        $jumlahPasien = $this->getJumlahPasienInap(date('Y-m', strtotime('-1 month')));
        $jumlahBed = $this->getJumlahBed();
        $jmlhari = date('t', strtotime('-1 month'));
        $dataDeathRate = $this->getDataDeathRate();

        $borHari = ($lamaInapHari / ($jumlahBed * 1)) * 100;
        $borBulan = ($lamaInapBulan / ($jumlahBed * $jmlhari)) * 100;
        $bto = ($dataDeathRate['rawatout'] + $dataDeathRate['meninggal']) / $jumlahBed;
        $alos = $lamaInapBulan / $jumlahPasien;
        $toi = (($jumlahBed * $jmlhari) - $lamaInapBulan) / $jumlahPasien;
        $ndr = ($dataDeathRate['meninggal48'] / ($dataDeathRate['rawatout'] + $dataDeathRate['meninggal'])) * 1000;
        $gdr = ($dataDeathRate['meninggal'] / ($dataDeathRate['rawatout'] + $dataDeathRate['meninggal'])) * 1000;
        $stats['getBTO'] = number_format($bto, 2, ',', '.');
        $stats['getBORBulan'] = number_format($borBulan, 2, ',', '.');
        $stats['getALOS'] = number_format($alos, 2, ',', '.');
        $stats['getTOI'] = number_format($toi, 2, ',', '.');
        $stats['getNDR'] = number_format($ndr, 2, ',', '.');
        $stats['getGDR'] = number_format($gdr, 2, ',', '.');

        return $this->draw('rawatinap.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function getDokter()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));

        $settings = htmlspecialchars_array($this->settings('manajemen'));
        $stats['poliChart'] = $this->countPxDrRj();
        $stats['ranapChart'] = $this->countPxDrRi();

        return $this->draw('dokter.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function getLaboratorium()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));

        $settings = htmlspecialchars_array($this->settings('manajemen'));
        $stats['getVisities'] = number_format($this->countVisite(), 0, '', '.');
        $stats['getLab'] = number_format($this->countCheck('periksa_lab', 'Lab1'), 0, '', '.');
        $stats['getLabMonthly'] = number_format($this->countMonth('periksa_lab', 'Lab1'), 0, '', '.');
        $stats['getLabYearly'] = number_format($this->countYear('periksa_lab', 'Lab1'), 0, '', '.');
        $stats['getDrRujuk'] = $this->countDrPerujukLab();
        $stats['percentTotal'] = 0;
        if ($this->countVisite() != 0) {
            $stats['percentTotal'] = number_format((($this->countVisite() - $this->countVisiteNoRM()) / $this->countVisite()) * 100, 0, '', '.');
        }
        $stats['percentDays'] = 0;
        if ($this->countCheck('periksa_lab', 'Lab1') != 0) {
            $stats['percentDays'] = number_format((($this->countCheck('periksa_lab', 'Lab1') - $this->countLastCheck('periksa_lab', 'Lab1')) / $this->countCheck('periksa_lab', 'Lab1')) * 100, 0, '', '.');
        }
        $stats['percentMonths'] = 0;
        if ($this->countMonth('periksa_lab', 'Lab1') != 0) {
            $stats['percentMonths'] = number_format((($this->countMonth('periksa_lab', 'Lab1') - $this->countLastMonth('periksa_lab', 'Lab1')) / $this->countMonth('periksa_lab', 'Lab1')) * 100, 0, '', '.');
        }
        $stats['percentYears'] = 0;
        if ($this->countYear('periksa_lab', 'Lab1') != 0) {
            $stats['percentYears'] = number_format((($this->countYear('periksa_lab', 'Lab1') - $this->countLastYear('periksa_lab', 'Lab1')) / $this->countYear('periksa_lab', 'Lab1')) * 100, 0, '', '.');
        }

        return $this->draw('laboratorium.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function getRadiologi()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));

        $settings = htmlspecialchars_array($this->settings('manajemen'));
        $stats['getVisities'] = number_format($this->countVisite(), 0, '', '.');
        $stats['getLab'] = number_format($this->countCheck('periksa_radiologi', 'rad1'), 0, '', '.');
        $stats['getLabMonthly'] = number_format($this->countMonth('periksa_radiologi', 'rad1'), 0, '', '.');
        $stats['getLabYearly'] = number_format($this->countYear('periksa_radiologi', 'rad1'), 0, '', '.');
        $stats['getDrRujuk'] = $this->countDrPerujukRad();
        $stats['percentTotal'] = 0;
        if ($this->countVisite() != 0) {
            $stats['percentTotal'] = number_format((($this->countVisite() - $this->countVisiteNoRM()) / $this->countVisite()) * 100, 0, '', '.');
        }
        $stats['percentDays'] = 0;
        if ($this->countCheck('periksa_radiologi', 'rad1') != 0) {
            $stats['percentDays'] = number_format((($this->countCheck('periksa_radiologi', 'rad1') - $this->countLastCheck('periksa_radiologi', 'rad1')) / $this->countCheck('periksa_radiologi', 'rad1')) * 100, 0, '', '.');
        }
        $stats['percentMonths'] = 0;
        if ($this->countMonth('periksa_radiologi', 'rad1') != 0) {
            $stats['percentMonths'] = number_format((($this->countMonth('periksa_radiologi', 'rad1') - $this->countLastMonth('periksa_radiologi', 'rad1')) / $this->countMonth('periksa_radiologi', 'rad1')) * 100, 0, '', '.');
        }
        $stats['percentYears'] = 0;
        if ($this->countYear('periksa_radiologi', 'rad1') != 0) {
            $stats['percentYears'] = number_format((($this->countYear('periksa_radiologi', 'rad1') - $this->countLastYear('periksa_radiologi', 'rad1')) / $this->countYear('periksa_radiologi', 'rad1')) * 100, 0, '', '.');
        }

        return $this->draw('radiologi.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function getApotek()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));
        $settings = htmlspecialchars_array($this->settings('manajemen'));
        $stats['poliChart'] = $this->countResepDr();
        return $this->draw('apotek.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function getFarmasi()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));
        return $this->draw('farmasi.html');
    }

    public function getKasir()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));
        $settings = htmlspecialchars_array($this->settings('manajemen'));

        $revenueBulanIni = $this->getRevenueRIRJ(date('Y-m'));
        $revenueBulanLalu = $this->getRevenueRIRJ(date('Y-m', strtotime('-1 month')));
        $revenueAllBulanIni = $revenueBulanIni['Ralan'] + $revenueBulanIni['Ranap'];
        $revenueAllBulanLalu = $revenueBulanLalu['Ralan'] + $revenueBulanLalu['Ranap'];
        $stats['getTotalRevenueMonth'] = number_format($revenueAllBulanIni, 0, '', '.');
        $stats['percentRevenueMonth'] = 0;
        if ($stats['getTotalRevenueMonth'] != 0) {
            $stats['percentRevenueMonth'] = number_format((($revenueAllBulanIni - $revenueAllBulanLalu) / $revenueAllBulanIni) * 100, 0, '', '.');
        }
        $stats['getRevenueRJMonth'] = number_format($revenueBulanIni['Ralan'], 0, '', '.');
        $stats['percentRevenueRJMonth'] = 0;
        if ($stats['getRevenueRJMonth'] != 0) {
            $stats['percentRevenueRJMonth'] = number_format((($revenueBulanIni['Ralan'] - $revenueBulanLalu['Ralan']) / $revenueBulanIni['Ralan']) * 100, 0, '', '.');
        }
        $stats['getRevenueRIMonth'] = number_format($revenueBulanIni['Ranap'], 0, '', '.');
        $stats['percentRevenueRIMonth'] = 0;
        if ($stats['getRevenueRIMonth'] != 0) {
            $stats['percentRevenueRIMonth'] = number_format((($revenueBulanIni['Ranap'] - $revenueBulanLalu['Ranap'])  / $revenueBulanIni['Ranap']) * 100, 0, '', '.');
        }
        $stats['getRevenueOthers'] = 0;
        $stats['percentRevenueOthers'] = 0;


        $stats['getCaraBayar'] = $this->getCaraBayar();

        $stats['tunai'] = $this->db('reg_periksa')->select(['count' => 'COUNT(DISTINCT no_rawat)'])->where('kd_pj', 'A09')->like('tgl_registrasi', date('Y') . '%')->oneArray();
        $stats['bpjs'] = $this->db('reg_periksa')->select(['count' => 'COUNT(DISTINCT no_rawat)'])->where('kd_pj', 'BPJ')->orWhere('kd_pj', 'A65')->like('tgl_registrasi', date('Y') . '%')->oneArray();
        $stats['lainnya'] = $this->db('reg_periksa')->select(['count' => 'COUNT(DISTINCT no_rawat)'])->where('kd_pj', '!=', 'A09')->where('kd_pj', '!=', 'BPJ')->like('tgl_registrasi', date('Y') . '%')->oneArray();

        var_dump($stats);
        return $this->draw('kasir.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function getMutu()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));
        $settings = htmlspecialchars_array($this->settings('manajemen'));

        $stats['poliChartKebersihanTangan'] = $this->poliChartKebersihanTangan();
        $stats['poliChartAPD'] = $this->poliChartAPD();
        $stats['poliChartIdentifikasi'] = $this->poliChartIdentifikasi();
        $stats['poliChartOperasiSC'] = $this->poliChartOperasiSC();
        $stats['poliChartWaktuTunggu'] = $this->poliChartWaktuTunggu();
        $stats['poliChartPenundaanOK'] = $this->poliChartPenundaanOK();
        $stats['poliChartVisiteDokter'] = $this->poliChartVisiteDokter();
        //connect to DB laborat
        QueryWrapper::connect("mysql:host=" . DBHOST . ";port=" . DBPORT . ";dbname=laborat", DBUSER, DBPASS);
        $stats['poliChartLab'] = $this->poliChartLab();
        //connect back to DB sik
        QueryWrapper::connect("mysql:host=" . DBHOST . ";port=" . DBPORT . ";dbname=" . DBNAME . "", DBUSER, DBPASS);
        $stats['poliChartKomplain'] = $this->poliChartKomplain();
        $stats['poliChartFornas'] = $this->poliChartFornas();
        $stats['poliChartClinicalPathway'] = $this->poliChartClinicalPathway();
        $stats['poliChartRisikoJatuh'] = $this->poliChartRisikoJatuh();
        $stats['poliChartKepuasanPasien'] = $this->poliChartKepuasanPasien();

        return $this->draw('mutu.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function getPresensi()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));
        $settings = htmlspecialchars_array($this->settings('manajemen'));
        $stats['getVisities'] = number_format($this->getTotalAbsen(), 0, '', '.');
        $stats['getBelumAbsen'] = number_format($this->getBelumAbsen(), 0, '', '.');
        $stats['getHarusAbsen'] = number_format($this->getJadwalJaga(), 0, '', '.');
        $stats['presensiChart'] = $this->presensiChart(15);

        $stats['getIjin'] = number_format($this->getIjin(), 0, '', '.');

        $stats['percentTotal'] = 0;
        if ($this->getTotalAbsen() != 0) {
            $stats['percentTotal'] = number_format((($this->getTotalAbsen() - $this->countVisiteNoRM()) / $this->countVisite()) * 100, 0, '', '.');
        }

        return $this->draw('presensi.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function presensiChart($days = 14, $offset = 0)
    {
        $time = strtotime(date("Y-m-d", strtotime("-" . ($days + $offset) . " days")));
        $date = date("Y-m-d", strtotime("-" . ($days + $offset) . " days"));

        $query = $this->db('rekap_presensi')
            ->select([
                'count' => 'COUNT(photo)',
                'count2' => "COUNT(IF(keterangan = '', 1, NULL))",
                'formatedDate' => 'jam_datang',
            ])
            ->where('jam_datang', '>=', $date . ' 00:00:00')
            ->group(['formatedDate'])
            ->asc('formatedDate');

        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        while ($time < (time() - ($offset * 86400))) {
            $return['labels'][] = '"' . date("Y-m-d", $time) . '"';
            $return['readable'][] = '"' . date("d M Y", $time) . '"';
            $return['visits'][] = 0;
            $return['visits2'][] = 0;

            $time = strtotime('+1 day', $time);
        }

        foreach ($data as $day) {
            $index = array_search('"' . date('Y-m-d', strtotime($day['formatedDate'])) . '"', $return['labels']);
            if ($index === false) {
                continue;
            }

            $return['visits'][$index] = $day['count'];
            $return['visits2'][$index] = $day['count2'];
        }

        return $return;
    }

    public function getCoba($days = 14, $offset = 0)
    {
        $date = date("Y-m-d", strtotime("-" .  ($days + $offset) . " days"));

        $query = $this->db('rekap_presensi')
            ->select([
                'count' => 'COUNT(photo)',
                'count2' => "COUNT(IF(keterangan = '', 1, NULL))",
            ])
            ->where('jam_datang', '>=', $date . ' 00:00:00');


        $data = $query->toArray();
        print_r($data);
        exit();
    }

    public function getSettings()
    {
        $this->assign['penjab'] = $this->core->db('penjab')->toArray();
        $this->assign['manajemen'] = htmlspecialchars_array($this->settings('manajemen'));
        return $this->draw('settings.html', ['settings' => $this->assign]);
    }

    public function countKunjungan()
    {
        $year1 = date('Y');
        $year2 = date('Y', strtotime('-1 year'));
        $bulan1 = date('Y-m');
        $bulan2 = date('Y-m', strtotime('-1 month'));
        $hari1 = date('Y-m-d');
        $hari2 = date('Y-m-d', strtotime('-1 days'));
        $query = $this->db()->pdo()->prepare("SELECT 
                    SUM(CASE WHEN tgl_registrasi LIKE '$year1%' THEN 1 ELSE 0 END) tahunini,
                    SUM(CASE WHEN tgl_registrasi LIKE '$year2%' THEN 1 ELSE 0 END) tahunlalu,
                    SUM(CASE WHEN tgl_registrasi LIKE '$bulan1%' THEN 1 ELSE 0 END) bulanini,
                    SUM(CASE WHEN tgl_registrasi LIKE '$bulan2%' THEN 1 ELSE 0 END) bulanlalu,
                    SUM(CASE WHEN tgl_registrasi = '$hari1' THEN 1 ELSE 0 END) hariini,
                    SUM(CASE WHEN tgl_registrasi = '$hari2' THEN 1 ELSE 0 END) kemarin
                FROM reg_periksa WHERE tgl_registrasi LIKE '$year1%' OR tgl_registrasi LIKE '$year2%';");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0];
    }

    public function countVisite()
    {
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->oneArray();

        return $record['count'];
    }

    public function countVisiteNoRM()
    {
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->group('no_rkm_medis')
            ->oneArray();

        return $record['count'];
    }

    public function countYearVisite()
    {
        $date = date('Y');
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->like('tgl_registrasi', $date . '%')
            ->oneArray();

        return $record['count'];
    }

    public function countLastYearVisite()
    {
        $date = date('Y', strtotime('-1 year'));
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->like('tgl_registrasi', $date . '%')
            ->oneArray();

        return $record['count'];
    }

    public function countMonthVisite()
    {
        $date = date('Y-m');
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->like('tgl_registrasi', $date . '%')
            ->oneArray();

        return $record['count'];
    }

    public function countLastMonthVisite()
    {
        $date = date('Y-m', strtotime('-1 month'));
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->like('tgl_registrasi', $date . '%')
            ->oneArray();

        return $record['count'];
    }


    public function countCurrentVisite()
    {
        $date = date('Y-m-d');
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->where('tgl_registrasi', $date)
            ->oneArray();

        return $record['count'];
    }

    public function countLastCurrentVisite()
    {
        $date = date('Y-m-d', strtotime('-1 days'));
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->where('tgl_registrasi', $date)
            ->oneArray();

        return $record['count'];
    }

    public function countCurrentTempPresensi()
    {
        $tgl_presensi = date('Y-m-d');
        $record = $this->db('temporary_presensi')
            ->select([
                'count' => 'COUNT(DISTINCT id)',
            ])
            ->like('jam_datang', $tgl_presensi . '%')
            ->oneArray();

        return $record['count'];
    }

    public function getTotalAbsen()
    {
        $total = $this->countCurrentTempPresensi() + $this->countRkpPresensi();
        return $total;
    }

    public function getBelumAbsen()
    {
        $total = $this->getJadwalJaga() - $this->getTotalAbsen();
        // echo $total;
        return $total;
    }

    public function countPegawai()
    {
        $status = 'AKTIF';
        $record = $this->db('pegawai')
            ->select([
                'count' => 'COUNT(DISTINCT id)',
            ])
            ->where('stts_aktif', $status)
            ->oneArray();

        return $record['count'];
    }

    public function countRkpPresensi()
    {
        $tgl_presensi = date('Y-m-d');
        $record = $this->db('rekap_presensi')
            ->select([
                'count' => 'COUNT(DISTINCT id)',
            ])
            ->like('jam_datang', $tgl_presensi . '%')
            ->oneArray();

        return $record['count'];
    }

    public function getJadwalJaga()
    {
        $date = date('d');
        $bulan = date('m');
        $tahun = date('y');
        $data = array_column($this->db('jadwal_pegawai')->where('h' . $date, '!=', '')->where('bulan', $bulan)->where('tahun', $tahun)->toArray(), 'h' . $date);
        //   //print_r($data);
        //   print("<pre>".print_r($data,true)."</pre>");
        $hasil = count($data);
        //   echo $hasil;
        //   exit();
        return $hasil;
    }

    public function getIjin()
    {
        $record = $this->db('rekap_presensi')
            ->select([
                'count' => 'COUNT(DISTINCT id)',
            ])
            ->where('keterangan', '!=', '')
            ->where('keterangan', '!=', '-')
            ->where('jam_datang', '>=', date('Y-m-d') . ' 00:00:00')
            ->oneArray();
        // var_dump($record);
        return $record['count'];
    }

    public function countPasien()
    {
        $record = $this->db('pasien')
            ->select([
                'count' => 'COUNT(DISTINCT no_rkm_medis)',
            ])
            ->oneArray();

        return $record['count'];
    }

    public function poliChart()
    {

        $query = $this->db('reg_periksa')
            ->select([
                'count'       => 'COUNT(DISTINCT no_rawat)',
                'nm_poli'     => 'nm_poli',
            ])
            ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->where('tgl_registrasi', '>=', date('Y-m-d'))
            ->group(['reg_periksa.kd_poli'])
            ->desc('nm_poli');


        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_poli'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function KunjunganTahunChart()
    {

        $query = $this->db('reg_periksa')
            ->select([
                'count'       => 'COUNT(DISTINCT no_rawat)',
                'label'       => 'tgl_registrasi'
            ])
            ->like('tgl_registrasi', date('Y') . '%')
            ->group('EXTRACT(MONTH FROM tgl_registrasi)');

        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => []
        ];
        foreach ($data as $value) {
            $return['labels'][] = date("M", strtotime($value['label']));
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function RanapTahunChart()
    {

        $query = $this->db('reg_periksa')
            ->select([
                'count'       => 'COUNT(DISTINCT no_rawat)',
                'label'       => 'tgl_registrasi'
            ])
            ->where('stts', 'Dirawat')
            ->like('tgl_registrasi', date('Y') . '%')
            ->group('EXTRACT(MONTH FROM tgl_registrasi)');

        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => []
        ];
        foreach ($data as $value) {
            $return['labels'][] = date("M", strtotime($value['label']));
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function RujukTahunChart()
    {

        $query = $this->db('reg_periksa')
            ->select([
                'count'       => 'COUNT(DISTINCT no_rawat)',
                'label'       => 'tgl_registrasi'
            ])
            ->where('stts', 'Dirujuk')
            ->like('tgl_registrasi', date('Y') . '%')
            ->group('EXTRACT(MONTH FROM tgl_registrasi)');

        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => []
        ];
        foreach ($data as $value) {
            $return['labels'][] = date("M", strtotime($value['label']));
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function poliChartBatal()
    {

        $query = $this->db('reg_periksa')
            ->select([
                'count'       => 'COUNT(DISTINCT no_rawat)',
                'nm_poli'     => 'nm_poli',
            ])
            ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->where('tgl_registrasi', '>=', date('Y-m-d'))
            ->where('stts', 'Batal')
            ->group(['reg_periksa.kd_poli'])
            ->desc('nm_poli');


        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_poli'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function poliChartBaru()
    {

        $query = $this->db('reg_periksa')
            ->select([
                'count'       => 'COUNT(DISTINCT no_rawat)',
                'nm_poli'     => 'nm_poli',
            ])
            ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->where('tgl_registrasi', '>=', date('Y-m-d'))
            ->where('stts_daftar', 'Baru')
            ->group(['reg_periksa.kd_poli'])
            ->desc('nm_poli');


        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_poli'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function presensiChartHari()
    {
        $return = [
            'labels'  => 'Belum Absen',
            'visits'  => $this->getBelumAbsen(),
        ];


        return $return;
    }

    public function countCurrentVisiteBatal($stts)
    {
        $date = date('Y-m-d');
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->where('tgl_registrasi', $date)
            ->where('stts', $stts)
            ->oneArray();

        return $record['count'];
    }

    public function countLastCurrentVisiteBatal($stts)
    {
        $date = date('Y-m-d', strtotime('-1 days'));
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->where('tgl_registrasi', $date)
            ->where('stts', $stts)
            ->oneArray();

        return $record['count'];
    }

    public function countCurrentVisiteBaru()
    {
        $date = date('Y-m-d');
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->where('tgl_registrasi', $date)
            ->where('stts_daftar', 'Baru')
            ->oneArray();

        return $record['count'];
    }

    public function countLastCurrentVisiteBaru()
    {
        $date = date('Y-m-d', strtotime('-1 days'));
        $record = $this->db('reg_periksa')
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->where('tgl_registrasi', $date)
            ->where('stts_daftar', 'Baru')
            ->oneArray();

        return $record['count'];
    }

    public function countCheck($table, $where)
    {
        $date = date('Y-m-d');
        $record = $this->db($table)
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->where('tgl_periksa', $date)
            ->where('nip', $where)
            ->oneArray();

        return $record['count'];
    }

    public function countLastCheck($table, $where)
    {
        $date = date('Y-m-d', strtotime('-1 days'));
        $record = $this->db($table)
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->where('tgl_periksa', $date)
            ->where('nip', $where)
            ->oneArray();

        return $record['count'];
    }

    public function countYear($table, $where)
    {
        $date = date('Y');
        $record = $this->db($table)
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->like('tgl_periksa', $date . '%')
            ->where('nip', $where)
            ->oneArray();

        return $record['count'];
    }

    public function countLastYear($table, $where)
    {
        $date = date('Y', strtotime('-1 year'));
        $record = $this->db($table)
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->like('tgl_periksa', $date . '%')
            ->where('nip', $where)
            ->oneArray();

        return $record['count'];
    }

    public function countMonth($table, $where)
    {
        $date = date('Y-m');
        $record = $this->db($table)
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->like('tgl_periksa', $date . '%')
            ->where('nip', $where)
            ->oneArray();

        return $record['count'];
    }

    public function countLastMonth($table, $where)
    {
        $date = date('Y-m', strtotime('-1 month'));
        $record = $this->db($table)
            ->select([
                'count' => 'COUNT(DISTINCT no_rawat)',
            ])
            ->like('tgl_periksa', $date . '%')
            ->where('nip', $where)
            ->oneArray();

        return $record['count'];
    }

    public function countDrPerujukLab()
    {
        $date = date('Y-m-d');
        $query = $this->db('periksa_lab')
            ->select([
                'count'       => 'COUNT(DISTINCT periksa_lab.no_rawat)',
                'nm_dokter'     => 'dokter.nm_dokter',
            ])
            ->join('dokter', 'periksa_lab.dokter_perujuk = dokter.kd_dokter')
            ->where('periksa_lab.tgl_periksa', $date)
            ->where('periksa_lab.nip', 'Lab1')
            ->group(['periksa_lab.dokter_perujuk'])
            ->desc('dokter.nm_dokter');


        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_dokter'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function countDrPerujukRad()
    {
        $date = date('Y-m-d');
        $query = $this->db('periksa_radiologi')
            ->select([
                'count'       => 'COUNT(DISTINCT periksa_radiologi.no_rawat)',
                'nm_dokter'     => 'dokter.nm_dokter',
            ])
            ->join('dokter', 'periksa_radiologi.dokter_perujuk = dokter.kd_dokter')
            ->where('periksa_radiologi.tgl_periksa', $date)
            ->where('periksa_radiologi.nip', 'rad1')
            ->group(['periksa_radiologi.dokter_perujuk'])
            ->desc('dokter.nm_dokter');


        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_dokter'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function getRanapNow()
    {
        $year = date('Y');
        $query = $this->db()->pdo()->prepare("SELECT COUNT(no_rawat) AS jml FROM kamar_inap WHERE tgl_masuk LIKE '$year%' and tgl_keluar = '0000-00-00';");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0]['jml'];
    }

    public function countRanap()
    {
        $date = date('Y-m-d');
        $query = $this->db()->pdo()->prepare("SELECT 
                                SUM(CASE WHEN stts_pulang = 'Membaik' OR stts_pulang = 'APS' THEN 1 ELSE 0 END) rawat,
                                SUM(CASE WHEN stts_pulang = 'Meninggal' THEN 1 ELSE 0 END) meninggal
                            FROM kamar_inap WHERE tgl_keluar = '$date'
                            UNION 
                            SELECT 
                                SUM(CASE WHEN stts_pulang = '-' THEN 1 ELSE 0 END) rawat,
                                '-'
                            FROM kamar_inap WHERE tgl_masuk = '$date'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data;

        // $arr = is_array($stts) ? 'Yes' : 'No';
        // if ($arr == 'Yes') {
        //     $poliklinik = implode("','", $stts);
        // } else {
        //     $poliklinik = str_replace(",", "','", $stts);
        // }
        // $query = $this->db()->pdo()->prepare("SELECT COUNT(DISTINCT no_rawat) as count FROM kamar_inap WHERE $tgl = '$date' AND stts_pulang IN ('$poliklinik')");
        // $query->execute();
        // $count = $query->fetchColumn();
        // return $count;
    }

    public function countLastRanap($tgl, $stts)
    {
        $date = date('Y-m-d', strtotime('-1 days'));
        $arr = is_array($stts) ? 'Yes' : 'No';
        if ($arr == 'Yes') {
            $poliklinik = implode("','", $stts);
        } else {
            $poliklinik = str_replace(",", "','", $stts);
        }
        $query = $this->db()->pdo()->prepare("SELECT COUNT(DISTINCT no_rawat) as count FROM kamar_inap WHERE $tgl = '$date' AND stts_pulang IN ('$poliklinik')");
        $query->execute();
        $count = $query->fetchColumn();
        return $count;
    }

    public function countKamarInap()
    {
        $date = date('Y-m-d');
        $query = $this->db('kamar_inap')
            ->select([
                'count'       => 'COUNT(DISTINCT kamar_inap.no_rawat)',
                'nm_bangsal'     => 'bangsal.nm_bangsal',
            ])
            ->join('kamar', 'kamar_inap.kd_kamar = kamar.kd_kamar')
            ->join('bangsal', 'kamar.kd_bangsal = bangsal.kd_bangsal')
            ->where('kamar_inap.stts_pulang', '-')
            ->group(['bangsal.kd_bangsal'])
            ->desc('bangsal.nm_bangsal');

        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_bangsal'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function countDx($type, $date)
    {
        // $date = date('Y-m-d');
        $query = $this->db()->pdo()->prepare("SELECT COUNT(diagnosa_pasien.kd_penyakit) as count ,penyakit.nm_penyakit FROM diagnosa_pasien JOIN reg_periksa ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit WHERE diagnosa_pasien.status ='$type' and reg_periksa.tgl_registrasi like '$date%' GROUP BY diagnosa_pasien.kd_penyakit ORDER BY `count`  DESC Limit 10");
        $query->execute();

        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_penyakit'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function countDx2($date)
    {
        // $date = date('Y-m-d');
        $query = $this->db()->pdo()->prepare("SELECT COUNT(d.kd_penyakit) as count ,p.nm_penyakit  FROM kamar_inap k
                                            INNER JOIN diagnosa_pasien d ON d.no_rawat = k.no_rawat
                                            INNER JOIN penyakit p ON p.kd_penyakit = d.kd_penyakit
                                            WHERE k.tgl_masuk LIKE '$date%'
                                            GROUP BY d.kd_penyakit ORDER BY `count`  DESC Limit 10");
        $query->execute();

        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_penyakit'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function countPxDrRj()
    {
        $date = date('Y-m-d');
        $query = $this->db('reg_periksa')
            ->select([
                'count'       => 'COUNT(DISTINCT reg_periksa.no_rawat)',
                'nm_dokter'     => 'dokter.nm_dokter',
            ])
            ->join('dokter', 'reg_periksa.kd_dokter = dokter.kd_dokter')
            ->where('reg_periksa.tgl_registrasi', $date)
            ->group(['reg_periksa.kd_dokter'])
            ->desc('dokter.nm_dokter');


        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_dokter'];
            $return['visits'][] = $value['count'];
        }
        return $return;
    }

    public function countPxDrRi()
    {
        $date = date('Y-m-d');
        $query = $this->db('kamar_inap')
            ->select([
                'count'       => 'COUNT(DISTINCT kamar_inap.no_rawat)',
                'nm_dokter'     => 'dokter.nm_dokter',
            ])
            ->join('dpjp_ranap', 'dpjp_ranap.no_rawat = kamar_inap.no_rawat')
            ->join('dokter', 'dpjp_ranap.kd_dokter = dokter.kd_dokter')
            ->where('kamar_inap.stts_pulang', '-')
            ->group(['dpjp_ranap.kd_dokter'])
            ->desc('dokter.nm_dokter');


        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_dokter'];
            $return['visits'][] = $value['count'];
        }

        return $return;
    }

    public function countResepDr()
    {
        $date = date('Y-m-d');
        $query = $this->db('resep_obat')
            ->select([
                'count'       => 'COUNT(DISTINCT resep_obat.no_rawat)',
                'nm_dokter'     => 'dokter.nm_dokter',
            ])
            ->join('dokter', 'resep_obat.kd_dokter = dokter.kd_dokter')
            ->where('resep_obat.tgl_peresepan', $date)
            ->group(['resep_obat.kd_dokter'])
            ->desc('dokter.nm_dokter');


        $data = $query->toArray();

        $return = [
            'labels'  => [],
            'visits'  => [],
        ];

        foreach ($data as $value) {
            $return['labels'][] = $value['nm_dokter'];
            $return['visits'][] = $value['count'];
        }
        return $return;
    }

    public function sumPdptLain()
    {
        $date = date('Y-m-d');
        $record = $this->db('pemasukan_lain')
            ->select([
                'sum' => 'SUM(besar)',
            ])
            ->where('tanggal', $date)
            ->oneArray();

        return $record['sum'];
    }

    public function getPendaftaran()
    {
        $this->core->addCSS(url(MODULES . '/manajemen/css/admin/style.css'));
        $this->core->addJS(url(BASE_DIR . '/assets/jscripts/Chart.bundle.min.js'));

        $settings = htmlspecialchars_array($this->settings('manajemen'));
        $stats['poliChart'] = $this->poliChartBatal();
        $stats['poliChartBaru'] = $this->poliChartBaru();
        $stats['getVisities'] = number_format($this->countVisite(), 0, '', '.');
        $stats['getCurrentVisities'] = number_format($this->countCurrentVisite(), 0, '', '.');
        $stats['getCurrentVisitiesBatal'] = number_format($this->countCurrentVisiteBatal('Batal'), 0, '', '.');
        $stats['getCurrentVisitiesBaru'] = number_format($this->countCurrentVisiteBaru(), 0, '', '.');
        $stats['percentTotal'] = 0;
        if ($this->countVisite() != 0) {
            $stats['percentTotal'] = number_format((($this->countVisite() - $this->countVisiteNoRM()) / $this->countVisite()) * 100, 0, '', '.');
        }
        $stats['percentDays'] = 0;
        if ($this->countCurrentVisite() != 0) {
            $stats['percentDays'] = number_format((($this->countCurrentVisite() - $this->countLastCurrentVisite()) / $this->countCurrentVisite()) * 100, 0, '', '.');
        }
        $stats['percentDaysBatal'] = 0;
        if ($this->countCurrentVisiteBatal('Batal') != 0) {
            $stats['percentDaysBatal'] = number_format((($this->countCurrentVisiteBatal('Batal') - $this->countLastCurrentVisiteBatal('Batal')) / $this->countCurrentVisiteBatal('Batal')) * 100, 0, '', '.');
        }
        $stats['percentDaysBaru'] = 0;
        if ($this->countCurrentVisiteBaru() != 0) {
            $stats['percentDaysBaru'] = number_format((($this->countCurrentVisiteBaru() - $this->countLastCurrentVisiteBaru()) / $this->countCurrentVisiteBaru()) * 100, 0, '', '.');
        }

        return $this->draw('pendaftaran.html', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function postSaveSettings()
    {
        foreach ($_POST['manajemen'] as $key => $val) {
            $this->settings('manajemen', $key, $val);
        }
        $this->notify('success', 'Pengaturan manajemen telah disimpan');
        redirect(url([ADMIN, 'manajemen', 'settings']));
    }

    public function getDataPasienBaru()
    {
        $hariini = date('Y-m-d');
        $kemarin = date('Y-m-d', strtotime('-1 days'));
        $query = $this->db()->pdo()->prepare("SELECT 
                    SUM(CASE WHEN tgl_registrasi = '$kemarin' THEN 1 ELSE 0 END) kemarin,
                    SUM(CASE WHEN tgl_registrasi = '$hariini' THEN 1 ELSE 0 END) hariini
                FROM reg_periksa WHERE stts_daftar = 'Baru'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0];
    }

    public function getDataPasienRalan()
    {
        $hariini = date('Y-m-d');
        $kemarin = date('Y-m-d', strtotime('-1 days'));
        $query = $this->db()->pdo()->prepare("SELECT 
                    SUM(CASE WHEN stts <> 'Belum' OR stts <> 'Batal' THEN 1 ELSE 0 END) Belum,
                    SUM(CASE WHEN stts = 'Sudah' THEN 1 ELSE 0 END) Sudah,
                    SUM(CASE WHEN stts = 'Batal' THEN 1 ELSE 0 END) Batal
                FROM reg_periksa WHERE tgl_registrasi = '$hariini'
                UNION 
                SELECT 
                    SUM(CASE WHEN stts <> 'Belum' OR stts <> 'Batal' THEN 1 ELSE 0 END) Belum,
                    SUM(CASE WHEN stts = 'Sudah' THEN 1 ELSE 0 END) Sudah,
                    SUM(CASE WHEN stts = 'Batal' THEN 1 ELSE 0 END) Batal
                FROM reg_periksa WHERE tgl_registrasi = '$kemarin'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data;

        // if ($stts == false) {
        //     $record = $this->db('reg_periksa')
        //         ->select([
        //             'count' => 'COUNT(DISTINCT no_rawat)',
        //         ])
        //         ->where('tgl_registrasi', $date)
        //         ->oneArray();
        // } else {
        //     // echo 'here';
        //     $record = $this->db('reg_periksa')
        //         ->select([
        //             'count' => 'COUNT(DISTINCT no_rawat)',
        //         ])
        //         ->where('tgl_registrasi', $date)
        //         ->where('stts', $stts)
        //         ->oneArray();
        // }
        // return $record['count'];
    }

    public function countTodayRanap()
    {
        $year = date('Y');
        $query = $this->db()->pdo()->prepare("SELECT COUNT(*) AS jml FROM kamar_inap WHERE tgl_masuk LIKE '$year%' AND tgl_keluar = '0000-00-00'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0]['jml'];
    }

    public function getRevenueTotal($date)
    {
        $query = $this->db()->pdo()->prepare("SELECT sum(totalbiaya) AS total FROM billing where tgl_byr LIKE '$date%'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0]['total'];
    }

    public function getRevenueRIRJ($date)
    {
        $query = $this->db()->pdo()->prepare("SELECT 
            SUM(CASE WHEN  r.status_lanjut = 'Ralan' THEN b.totalbiaya ELSE 0 END) Ralan,
            SUM(CASE WHEN  r.status_lanjut = 'Ranap' THEN b.totalbiaya ELSE 0 END) Ranap
            FROM billing b INNER JOIN reg_periksa r ON r.no_rawat = b.no_rawat  where b.tgl_byr LIKE '$date%'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0];
    }

    public function getRevenueRJ($date)
    {
        // $record = $this->db('billing')
        //     ->select([
        //         'total' => 'sum(billing.totalbiaya)',
        //     ])
        //     ->join('reg_periksa', 'reg_periksa.no_rawat = billing.no_rawat')
        //     ->where('billing.tgl_byr', 'LIKE', $date)
        //     ->where('reg_periksa.status_lanjut', 'Ralan')
        //     ->oneArray();

        // return $record['total'];

        $query = $this->db()->pdo()->prepare("SELECT sum(b.totalbiaya) AS total FROM billing b INNER JOIN reg_periksa r ON r.no_rawat = b.no_rawat  where b.tgl_byr LIKE '$date%' AND r.status_lanjut = 'Ralan' ");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0]['total'];
    }

    public function getRevenueRI($date)
    {
        // $record = $this->db('billing')
        //     ->select([
        //         'total' => 'sum(billing.totalbiaya)',
        //     ])
        //     ->join('reg_periksa', 'reg_periksa.no_rawat = billing.no_rawat')
        //     ->where('billing.tgl_byr', 'LIKE', $date)
        //     ->where('reg_periksa.status_lanjut', 'Ranap')
        //     ->oneArray();

        // return $record['total'];
        $query = $this->db()->pdo()->prepare("SELECT sum(b.totalbiaya) AS total FROM billing b INNER JOIN reg_periksa r ON r.no_rawat = b.no_rawat  where b.tgl_byr LIKE '$date%' AND r.status_lanjut = 'Ranap' ");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0]['total'];
    }

    public function getLamaInap($date)
    {
        $date = date('Y-m', strtotime('-1 month'));
        $query = $this->db()->pdo()->prepare("SELECT SUM(lama) AS lama FROM kamar_inap where tgl_masuk LIKE '$date%'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0]['lama'];
    }

    public function getJumlahBed()
    {
        $query = $this->db()->pdo()->prepare("select count(*) as jmlbed from kamar where statusdata='1'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0]['jmlbed'];
    }

    public function getJumlahPasienInap($date)
    {
        $query = $this->db()->pdo()->prepare("SELECT SUM(jml) as jml FROM (select count(no_rawat) AS jml from kamar_inap where tgl_masuk LIKE '$date%' group by no_rawat) A");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0]['jml'];
    }

    public function getDataDeathRate()
    {
        $month = date('Y-m');
        $query = $this->db()->pdo()->prepare("SELECT 
                SUM(CASE WHEN stts_pulang <> 'Meninggal' THEN 1 ELSE 0 END) rawatout,
                SUM(CASE WHEN stts_pulang = 'Meninggal' THEN 1 ELSE 0 END) meninggal,
                SUM(CASE WHEN stts_pulang = 'Meninggal'  AND TIMESTAMPDIFF(HOUR, concat(tgl_masuk, ' ', jam_masuk), concat(tgl_keluar, ' ', jam_keluar)) > 48 THEN 1 ELSE 0 END) meninggal48
            FROM kamar_inap WHERE tgl_keluar LIKE '$month%'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data[0];
    }


    public function getBORMonth()
    {
        $date = date('Y-m', strtotime('-1 month'));
        $query = $this->db()->pdo()->prepare("SELECT SUM(lama) AS lama FROM kamar_inap where tgl_masuk LIKE '$date%'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        $lama = $data[0]['lama'];

        $query = $this->db()->pdo()->prepare("select count(*) as jmlbed from kamar where statusdata='1'");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        $jmlbed = $data[0]['jmlbed'];

        $jmlhari = date('t', strtotime('-1 month'));
        $bor = ($lama / ($jmlbed * $jmlhari)) * 100;

        return $bor;
    }

    // public function getBORDays()
    // {
    //     $date = date('Y-m-d');
    //     $query = $this->db()->pdo()->prepare("SELECT SUM(lama) AS lama FROM kamar_inap where tgl_masuk = '$date'");
    //     $query->execute();
    //     $data = $query->fetchAll(\PDO::FETCH_ASSOC);
    //     $lama = $data[0]['lama'];

    //     $query = $this->db()->pdo()->prepare("select count(*) as jmlbed from kamar where statusdata='1'");
    //     $query->execute();
    //     $data = $query->fetchAll(\PDO::FETCH_ASSOC);
    //     $jmlbed = $data[0]['jmlbed'];

    //     $jmlhari = 1;
    //     $bor = ($lama / ($jmlbed * $jmlhari)) * 100;

    //     return $bor;
    // }

    // public function getALOSMonth()
    // {
    //     $date = date('Y-m', strtotime('-1 month'));
    //     $query = $this->db()->pdo()->prepare("SELECT SUM(lama) AS lama FROM kamar_inap where tgl_masuk LIKE '$date%'");
    //     $query->execute();
    //     $data = $query->fetchAll(\PDO::FETCH_ASSOC);
    //     $lama = $data[0]['lama'];

    //     $query = $this->db()->pdo()->prepare("SELECT SUM(jml) as jml FROM (select count(no_rawat) AS jml from kamar_inap where tgl_masuk LIKE '$date%' group by no_rawat) A");
    //     $query->execute();
    //     $data = $query->fetchAll(\PDO::FETCH_ASSOC);
    //     $jmlpasien = $data[0]['jml'];
    //     $alos = $lama / $jmlpasien;

    //     return $alos;
    // }

    // public function getTOIMonth()
    // {
    //     $date = date('Y-m', strtotime('-1 month'));
    //     $query = $this->db()->pdo()->prepare("SELECT SUM(lama) AS lama FROM kamar_inap where tgl_masuk LIKE '$date%'");
    //     $query->execute();
    //     $data = $query->fetchAll(\PDO::FETCH_ASSOC);
    //     $lama = $data[0]['lama'];

    //     $query = $this->db()->pdo()->prepare("SELECT SUM(jml) as jml FROM (select count(no_rawat) AS jml from kamar_inap where tgl_masuk LIKE '$date%' group by no_rawat) A");
    //     $query->execute();
    //     $data = $query->fetchAll(\PDO::FETCH_ASSOC);
    //     $jmlpasien = $data[0]['jml'];

    //     $query = $this->db()->pdo()->prepare("select count(*) as jmlbed from kamar where statusdata='1'");
    //     $query->execute();
    //     $data = $query->fetchAll(\PDO::FETCH_ASSOC);
    //     $jmlbed = $data[0]['jmlbed'];

    //     $jmlhari = date('t', strtotime('-1 month'));

    //     $toi = (($jmlbed * $jmlhari) - $lama) / $jmlpasien;
    //     // echo $jmlbed . ' * ' . $jmlhari . '-' . $lama . ' / ' . $jmlpasien;
    //     return $toi;
    // }

    public function poliChartKebersihanTangan()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(tanggal, 7) AS bulan, ruangan,
                        SUM(CASE WHEN sebelum_kontak_pasien = 'HR' OR sebelum_kontak_pasien = 'HW' THEN 1 ELSE 0 END) kol_1,
                        SUM(CASE WHEN sebelum_tindakan_aseptik = 'HR' OR sebelum_tindakan_aseptik = 'HW' THEN 1 ELSE 0 END) kol_2,
                        SUM(CASE WHEN setelah_kontak_pasien = 'HR' OR setelah_kontak_pasien = 'HW' THEN 1 ELSE 0 END) kol_3,
                        SUM(CASE WHEN setelah_kontak_cairan = 'HR' OR setelah_kontak_cairan = 'HW' THEN 1 ELSE 0 END) kol_4,
                        SUM(CASE WHEN setelah_kontak_alat = 'HR' OR setelah_kontak_alat = 'HW' THEN 1 ELSE 0 END) kol_5,
                        SUM(CASE WHEN cucitangan <> '' THEN 5 ELSE 0 END) opp
                        FROM mutu_kebersihantangan
                        WHERE tanggal LIKE '$year%'
                        GROUP BY LEFT(tanggal, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;
        foreach ($data as $value) {
            $prosentase = round((($value['kol_1'] + $value['kol_2'] + $value['kol_3'] + $value['kol_4'] + $value['kol_5']) / $value['opp']) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartAPD()
    {

        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(tanggal, 7) AS bulan,
                                    SUM(CASE WHEN level_apd = '1' AND masker_bedah = 'IYA' THEN 1 ELSE 0 END) level1_1,
                                    SUM(CASE WHEN level_apd = '1' AND gaun = 'IYA' THEN 1 ELSE 0 END) level1_2,
                                    SUM(CASE WHEN level_apd = '1' AND handscoon = 'IYA' THEN 1 ELSE 0 END) level1_3,
                                    SUM(CASE WHEN level_apd = '2' AND masker_bedah = 'IYA' THEN 1 ELSE 0 END) level2_1,
                                    SUM(CASE WHEN level_apd = '2' AND gaun = 'IYA' THEN 1 ELSE 0 END) level2_2,
                                    SUM(CASE WHEN level_apd = '2' AND faceshield = 'IYA' THEN 1 ELSE 0 END) level2_3,
                                    SUM(CASE WHEN level_apd = '2' AND nursecap = 'IYA' THEN 1 ELSE 0 END) level2_4,
                                    SUM(CASE WHEN level_apd = '2' AND handscoon = 'IYA' THEN 1 ELSE 0 END) level2_5,
                                    SUM(CASE WHEN level_apd = '3' AND masker_n95 = 'IYA' THEN 1 ELSE 0 END) level3_1,
                                    SUM(CASE WHEN level_apd = '3' AND hazmat = 'IYA' THEN 1 ELSE 0 END) level3_2,
                                    SUM(CASE WHEN level_apd = '3' AND handscoon = 'IYA' THEN 1 ELSE 0 END) level3_3,
                                    SUM(CASE WHEN level_apd = '3' AND nursecap = 'IYA' THEN 1 ELSE 0 END) level3_4,
                                    SUM(CASE WHEN level_apd = '3' AND faceshield = 'IYA' THEN 1 ELSE 0 END) level3_5,
                                    SUM(CASE WHEN level_apd = '3' AND goggle = 'IYA' THEN 1 ELSE 0 END) level3_6,
                                    SUM(CASE WHEN level_apd = '3' AND sepatuboot = 'IYA' THEN 1 ELSE 0 END) level3_7,
                                    SUM(CASE WHEN level_apd = '1' THEN 1 ELSE 0 END) n_1,
                                    SUM(CASE WHEN level_apd = '2' THEN 1 ELSE 0 END) n_2,
                                    SUM(CASE WHEN level_apd = '3' THEN 1 ELSE 0 END) n_3
                                    FROM mutu_kepatuhanapd
                                    WHERE tanggal LIKE '$year%'
                                    GROUP BY LEFT(tanggal, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            if ($value['n_1'] != 0)
                $nilai_1 = round((($value['level1_1'] + $value['level1_2'] + $value['level1_3']) / ($value['n_1'] * 3)) * 100);
            if ($value['n_2'] != 0)
                $nilai_2 = round((($value['level2_1'] + $value['level2_2'] + $value['level2_3'] + $value['level2_4'] + $value['level2_5']) / ($value['n_2'] * 5)) * 100);
            if ($value['n_3'] != 0)
                $nilai_3 = round((($value['level3_1'] + $value['level3_2'] + $value['level3_3'] + $value['level3_4'] + $value['level3_5'] + $value['level3_6'] + $value['level3_7']) / ($value['n_3'] * 7)) * 100);
            $prosentase = round(($nilai_1 + $nilai_2 + $nilai_3) / 3);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartIdentifikasi()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(tanggal, 7) AS bulan,
                                    SUM(CASE WHEN pemberian_obat = 'IYA' AND pemberian_nutrisi = 'IYA' AND pemberian_darah = 'IYA' AND pengambilan_specimen = 'IYA' AND sebelum_diagnostik = 'IYA' THEN 1 ELSE 0 END) kol_1,
                                    COUNT(tanggal) AS jml
                                    FROM mutu_identifikasipasien
                                    WHERE tanggal LIKE '$year%'
                                    GROUP BY LEFT(tanggal, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['kol_1'] / $value['jml']) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartOperasiSC()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(tgl_registrasi, 7) AS bulan, COUNT(tgl_registrasi) AS jml,
                                    SUM(CASE WHEN skor = '1' THEN 1 ELSE 0 END) kol_d
                                    FROM mutu_ketepatansccito
                                    WHERE tgl_registrasi LIKE '$year%' 
                                    GROUP BY LEFT(tgl_registrasi, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['kol_d'] / $value['jml']) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartWaktuTunggu()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(r.tgl_registrasi, 7) AS bulan, COUNT(r.tgl_registrasi) AS jml,
            SUM(CASE WHEN ROUND(((TIME_TO_SEC(t.temp4) - TIME_TO_SEC(r.jam_reg))/60) ,0) > 180 THEN 1 ELSE 0 END) kol_d,
            SUM(CASE WHEN ROUND(((TIME_TO_SEC(t.temp4) - TIME_TO_SEC(r.jam_reg))/60) ,0) <= 180 THEN 1 ELSE 0 END) kol_n
            FROM reg_periksa r 
            INNER JOIN temporary2 t ON t.temp2 = r.no_rawat
            WHERE r.tgl_registrasi LIKE '$year%' 
            GROUP BY LEFT(r.tgl_registrasi, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['kol_n'] / ($value['kol_n'] + $value['kol_d'])) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }
    public function poliChartPenundaanOK()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(bo.tanggal, 7) AS bulan, COUNT(bo.tanggal) AS jml,
                                    SUM(CASE WHEN DATEDIFF(LEFT(o.tgl_operasi, 10),LEFT(bo.tanggal, 10)) > 1 THEN 1 ELSE 0 END) kol_d
                                    FROM booking_operasi bo
                                    INNER JOIN operasi o ON o.no_rawat = bo.no_rawat
                                    WHERE bo.tanggal LIKE '$year%'
                                    GROUP BY LEFT(bo.tanggal, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = ($value['kol_d'] / $value['jml']) * 100;
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartVisiteDokter()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(tgl_perawatan, 7) AS bulan, COUNT(tgl_perawatan) AS jml,
                                        SUM(CASE WHEN jam_rawat >= '06:00:00' AND jam_rawat <= '14:00:00' THEN 1 ELSE 0 END) kol_d
                                        FROM rawat_inap_dr
                                        WHERE tgl_perawatan LIKE '$year%' 
                                        GROUP BY LEFT(tgl_perawatan, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['kol_d'] / $value['jml']) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartLab()
    {
        $year = date("Y");

        $check_db = $this->db()->pdo()->query("SELECT LEFT(tanggal, 7) AS bulan, COUNT(tanggal) AS jml,
                                SUM(CASE WHEN ROUND(((TIME_TO_SEC(jam_keluar) - TIME_TO_SEC(jam_terima))/60) ,0) <= 240 THEN 1 ELSE 0 END) kol_d
                                FROM data_penderita
                                WHERE tanggal LIKE '$year%' 
                                GROUP BY LEFT(tanggal, 7)");
        $check_db->execute();

        // $query = $this->db()->pdo()->prepare("SELECT LEFT(tgl_perawatan, 7) AS bulan, COUNT(tgl_perawatan) AS jml,
        //                                 SUM(CASE WHEN jam_rawat >= '06:00:00' AND jam_rawat <= '14:00:00' THEN 1 ELSE 0 END) kol_d
        //                                 FROM rawat_inap_dr
        //                                 WHERE tgl_perawatan LIKE '$year%' 
        //                                 GROUP BY LEFT(tgl_perawatan, 7)");
        // $query->execute();
        $data = $check_db->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['kol_d'] / $value['jml']) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartKomplain()
    {
        $year = date("Y");

        $query = $this->db()->pdo()->prepare("SELECT LEFT(tgl_komplain, 7) AS bulan, COUNT(tgl_komplain) AS jml,
                                        SUM(CASE WHEN ROUND(((TIME_TO_SEC(jam_tanggap) - TIME_TO_SEC(jam_komplain))/60) ,0) <= 60 AND DATEDIFF(tgl_tanggap,tgl_komplain) < 1 THEN 1 ELSE 0 END) kol_d
                                        FROM mutu_tanggapkomplain
                                        WHERE tgl_komplain LIKE '$year%' 
                                        GROUP BY LEFT(tgl_komplain, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['kol_d'] / $value['jml']) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartFornas()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(d.tgl_perawatan, 7) AS bulan,
                            SUM(CASE WHEN db.kode_kategori = 'K01' THEN 1 ELSE 0 END) fornas,
                            SUM(CASE WHEN db.kode_kategori = 'K02' THEN 1 ELSE 0 END) nonfornas
                            FROM detail_pemberian_obat d
                            INNER JOIN reg_periksa rp ON rp.no_rawat = d.no_rawat
                            INNER JOIN databarang db ON db.kode_brng = d.kode_brng
                            INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
                            WHERE d.tgl_perawatan LIKE '$year%' AND rp.no_rkm_medis NOT IN (SELECT no_rkm_medis FROM pasien_tni) AND db.kode_kategori <> '-'
                            GROUP BY LEFT(d.tgl_perawatan, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['fornas'] / ($value['fornas'] + $value['nonfornas'])) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartClinicalPathway()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(tgl_data, 7) AS bulan, COUNT(tgl_data) AS jml,
                                        SUM(CASE WHEN patuh = 'IYA' THEN 1 ELSE 0 END) kol_d
                                        FROM mutu_kepatuhanclinicalpathway
                                        WHERE tgl_data LIKE '$year%' 
                                        GROUP BY LEFT(tgl_data, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['kol_d'] / $value['jml']) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartRisikoJatuh()
    {
        $year = date("Y");
        $query = $this->db()->pdo()->prepare("SELECT LEFT(tgl_data, 7) AS bulan, COUNT(tgl_data) AS jml,
                                        SUM(CASE WHEN patuh = 'IYA' THEN 1 ELSE 0 END) kol_d
                                        FROM mutu_pencegahanpasienjatuh
                                        WHERE tgl_data LIKE '$year%' 
                                        GROUP BY LEFT(tgl_data, 7)");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round(($value['kol_d'] / $value['jml']) * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function poliChartKepuasanPasien()
    {
        $year = date("Y");
        $_str = "SELECT LEFT(TIMESTAMP, 7) AS bulan, (SUM(CONVERT(REGEXP_SUBSTR(tersedia_alur_layanan,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_alur_layanan)) +SUM(CONVERT(REGEXP_SUBSTR(pelaks_alur_sesuai,\"[0-9]+\"),SIGNED)/4)/(COUNT(pelaks_alur_sesuai)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_persyaratan_layan,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_persyaratan_layan)) +SUM(CONVERT(REGEXP_SUBSTR(pelaks_persyaratan_sesuai,\"[0-9]+\"),SIGNED)/4)/(COUNT(pelaks_persyaratan_sesuai)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_informasi_biaya,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_informasi_biaya)) +SUM(CONVERT(REGEXP_SUBSTR(biaya_layan_terjangkau,\"[0-9]+\"),SIGNED)/4)/(COUNT(biaya_layan_terjangkau)) +SUM(CONVERT(REGEXP_SUBSTR(biaya_sesuai,\"[0-9]+\"),SIGNED)/4)/(COUNT(biaya_sesuai)) +SUM(CONVERT(REGEXP_SUBSTR(informasi_biaya_dimengerti,\"[0-9]+\"),SIGNED)/4)/(COUNT(informasi_biaya_dimengerti)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_informasi_waktu_layan,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_informasi_waktu_layan)) +SUM(CONVERT(REGEXP_SUBSTR(informasi_waktu_layan_terlihat,\"[0-9]+\"),SIGNED)/4)/(COUNT(informasi_waktu_layan_terlihat)) +SUM(CONVERT(REGEXP_SUBSTR(waktu_layan_wajar,\"[0-9]+\"),SIGNED)/4)/(COUNT(waktu_layan_wajar)) +SUM(CONVERT(REGEXP_SUBSTR(waktu_layan_berjalan_terus,\"[0-9]+\"),SIGNED)/4)/(COUNT(waktu_layan_berjalan_terus)) +SUM(CONVERT(REGEXP_SUBSTR(waktu_layan_sesuai_informasi,\"[0-9]+\"),SIGNED)/4)/(COUNT(waktu_layan_sesuai_informasi)) +SUM(CONVERT(REGEXP_SUBSTR(waktu_layan_sesuai_ketentuan,\"[0-9]+\"),SIGNED)/4)/(COUNT(waktu_layan_sesuai_ketentuan)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_meja_layan_unit,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_meja_layan_unit)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_tempat_parkir,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_tempat_parkir)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_ruang_tunggu_toilet,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_ruang_tunggu_toilet)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_sarana_khusus,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_sarana_khusus)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_kotak_pengaduan,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_kotak_pengaduan)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_app_mobile,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_app_mobile)) +SUM(CONVERT(REGEXP_SUBSTR(app_mobile_diakses,\"[0-9]+\"),SIGNED)/4)/(COUNT(app_mobile_diakses)) +SUM(CONVERT(REGEXP_SUBSTR(app_mobile_mudah_digunakan,\"[0-9]+\"),SIGNED)/4)/(COUNT(app_mobile_mudah_digunakan)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_informasi_di_app_mobile,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_informasi_di_app_mobile)) +SUM(CONVERT(REGEXP_SUBSTR(respon_cepat_app_mobile,\"[0-9]+\"),SIGNED)/4)/(COUNT(respon_cepat_app_mobile)) +SUM(CONVERT(REGEXP_SUBSTR(kepuasan_layan_app_mobile,\"[0-9]+\"),SIGNED)/4)/(COUNT(kepuasan_layan_app_mobile)) +SUM(CONVERT(REGEXP_SUBSTR(terdapat_petugas_standby,\"[0-9]+\"),SIGNED)/4)/(COUNT(terdapat_petugas_standby)) +SUM(CONVERT(REGEXP_SUBSTR(petugas_berseragam,\"[0-9]+\"),SIGNED)/4)/(COUNT(petugas_berseragam)) +SUM(CONVERT(REGEXP_SUBSTR(petugas_respon_cepat,\"[0-9]+\"),SIGNED)/4)/(COUNT(petugas_respon_cepat)) +SUM(CONVERT(REGEXP_SUBSTR(petugas_berpengalaman,\"[0-9]+\"),SIGNED)/4)/(COUNT(petugas_berpengalaman)) +SUM(CONVERT(REGEXP_SUBSTR(petugas_perilaku_sesuai,\"[0-9]+\"),SIGNED)/4)/(COUNT(petugas_perilaku_sesuai)) +SUM(CONVERT(REGEXP_SUBSTR(petugas_melayani_dengan_serius,\"[0-9]+\"),SIGNED)/4)/(COUNT(petugas_melayani_dengan_serius)) +SUM(CONVERT(REGEXP_SUBSTR(petugas_sabar,\"[0-9]+\"),SIGNED)/4)/(COUNT(petugas_sabar)) +SUM(CONVERT(REGEXP_SUBSTR(petugas_jawab_pertanyaan,\"[0-9]+\"),SIGNED)/4)/(COUNT(petugas_jawab_pertanyaan)) +SUM(CONVERT(REGEXP_SUBSTR(unit_layan_selalu_bersih,\"[0-9]+\"),SIGNED)/4)/(COUNT(unit_layan_selalu_bersih)) +SUM(CONVERT(REGEXP_SUBSTR(tersedia_tempat_sampah,\"[0-9]+\"),SIGNED)/4)/(COUNT(tersedia_tempat_sampah)) +SUM(CONVERT(REGEXP_SUBSTR(rutin_pembersihan_lingk,\"[0-9]+\"),SIGNED)/4)/(COUNT(rutin_pembersihan_lingk)) +SUM(CONVERT(REGEXP_SUBSTR(unit_tidak_meminta_uang_tambahan,\"[0-9]+\"),SIGNED)/4)/(COUNT(unit_tidak_meminta_uang_tambahan)) +SUM(CONVERT(REGEXP_SUBSTR(unit_layan_tidak_membedabedakan,\"[0-9]+\"),SIGNED)/4)/(COUNT(unit_layan_tidak_membedabedakan)) +SUM(CONVERT(REGEXP_SUBSTR(tidak_ada_calo,\"[0-9]+\"),SIGNED)/4)/(COUNT(tidak_ada_calo)) +SUM(CONVERT(REGEXP_SUBSTR(tidak_bayar_dokter_bidan,\"[0-9]+\"),SIGNED)/4)/(COUNT(tidak_bayar_dokter_bidan)))/40 AS kepuasan
                    FROM mutu_kepuasan_2022
                    WHERE TIMESTAMP LIKE '$year%' GROUP BY LEFT(TIMESTAMP, 7)";
        $query = $this->db()->pdo()->prepare($_str);
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        $data_1 = 0;
        $data_2 = 0;
        $data_3 = 0;
        $data_4 = 0;
        $data_5 = 0;
        $data_6 = 0;
        $data_7 = 0;
        $data_8 = 0;
        $data_9 = 0;
        $data_10 = 0;
        $data_11 = 0;
        $data_12 = 0;

        foreach ($data as $value) {
            $prosentase = round($value['kepuasan'] * 100);
            switch ($value['bulan']) {
                case $year . '-01':
                    $data_1 = $prosentase;
                    break;
                case $year . '-02':
                    $data_2 = $prosentase;
                    break;
                case $year . '-03':
                    $data_3 = $prosentase;
                    break;
                case $year . '-04':
                    $data_4 = $prosentase;
                    break;
                case $year . '-05':
                    $data_5 = $prosentase;
                    break;
                case $year . '-06':
                    $data_6 = $prosentase;
                    break;
                case $year . '-07':
                    $data_7 = $prosentase;
                    break;
                case $year . '-08':
                    $data_8 = $prosentase;
                    break;
                case $year . '-09':
                    $data_9 = $prosentase;
                    break;
                case $year . '-10':
                    $data_10 = $prosentase;
                    break;
                case $year . '-11':
                    $data_11 = $prosentase;
                    break;
                case $year . '-12':
                    $data_12 = $prosentase;
                    break;
            }
        }
        $return = [
            'labels'  => ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember'],
            'visits'  => [$data_1, $data_2, $data_3, $data_4, $data_5, $data_6, $data_7, $data_8, $data_9, $data_10, $data_11, $data_12],
        ];
        return $return;
    }

    public function getCaraBayar()
    {
        $year = date("Y");
        $month = date("Y-m");
        $day = date("Y-m-d");
        $query = $this->db()->pdo()->prepare("SELECT 'year' AS jns, 
                                SUM(CASE WHEN kd_pj IN ('A09') AND LEFT(tgl_registrasi, 4) = '$year' THEN 1 ELSE 0 END) umum,
                                SUM(CASE WHEN kd_pj IN ('A65', 'BPJ') AND LEFT(tgl_registrasi, 4) = '$year' THEN 1 ELSE 0 END) bpjs,
                                SUM(CASE WHEN kd_pj NOT IN ('A65', 'BPJ', 'A09') AND LEFT(tgl_registrasi, 4) = '$year' THEN 1 ELSE 0 END) lain
                                FROM reg_periksa
                                WHERE tgl_registrasi LIKE '$year%'
                                GROUP BY LEFT(tgl_registrasi, 4)
                                UNION 
                                SELECT 'month' AS jns, 
                                SUM(CASE WHEN kd_pj IN ('A09') AND LEFT(tgl_registrasi, 7) = '$month' THEN 1 ELSE 0 END) umum,
                                SUM(CASE WHEN kd_pj IN ('A65', 'BPJ') AND LEFT(tgl_registrasi, 7) = '$month' THEN 1 ELSE 0 END) bpjs,
                                SUM(CASE WHEN kd_pj NOT IN ('A65', 'BPJ', 'A09') AND LEFT(tgl_registrasi, 7) = '$month' THEN 1 ELSE 0 END) lain
                                FROM reg_periksa
                                WHERE tgl_registrasi LIKE '$month%'
                                GROUP BY LEFT(tgl_registrasi,7)
                                UNION 
                                SELECT 'day' AS jns, 
                                SUM(CASE WHEN kd_pj IN ('A09') AND tgl_registrasi = '$day' THEN 1 ELSE 0 END) umum,
                                SUM(CASE WHEN kd_pj IN ('A65', 'BPJ') AND tgl_registrasi = '$day' THEN 1 ELSE 0 END) bpjs,
                                SUM(CASE WHEN kd_pj NOT IN ('A65', 'BPJ', 'A09') AND tgl_registrasi = '$day' THEN 1 ELSE 0 END) lain
                                FROM reg_periksa
                                WHERE tgl_registrasi LIKE '$day'
                                GROUP BY tgl_registrasi");
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        $return = [];
        foreach ($data as $value) {
            // array_push(
            //     $return,
            //     [$value['umum'], $value['bpjs'], $value['lain']]
            // );
            $return[$value['jns']] = [$value['umum'], $value['bpjs'], $value['lain']];
            // switch ($value['jns']) {
            //     case 'year':
            //         $return = [
            //             'year'  => [$value['umum'], $value['bpjs'], $value['lain']],
            //         ];
            //         break;
            //     case 'month':
            //         $return = [
            //             'month'  => [$value['umum'], $value['bpjs'], $value['lain']],
            //         ];
            //         break;
            //     case 'day':
            //         $return = [
            //             'day'  => [$value['umum'], $value['bpjs'], $value['lain']],
            //         ];
            //         break;
            // }
        }
        return $return;
    }
}
