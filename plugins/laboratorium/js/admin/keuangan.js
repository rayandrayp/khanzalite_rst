$("#notif").hide();
// tombol tambah diklik
$("#index").on('click', '#search', function() {
    console.log("cari button pressed ");
    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/laboratorium/keuangansearch?t=' + mlite.token;
    var mulai = $('input[name=mulai]').val();
    var sampai = $('input[name=sampai]').val();
    var status = $('select[name=status]').val();
    var tiperawat = $('select[name=tiperawat]').val();
    console.log(mulai + ' | ' + sampai + ' | ' + status + ' | ' + tiperawat);
    $.post(url, {
        mulai: mulai,
        sampai: sampai,
        status: status,
        tiperawat: tiperawat,
    }, function(data) {
        // tampilkan data yang sudah di perbaharui
        console.log(data);
        $("#display").show().load(baseURL + '/laboratorium/keuangandisplay?t=' + mlite.token);
        $("#display").html(data).show();
    });
});

$("#index").on('click', '#tutupform', function() {
    event.preventDefault();
    $("#form").hide();
    $("#tutupform").val("Buka Form");
    $("#tutupform").attr("id", "bukaform");
});

// tombol batal diklik
$("#form").on("click", "#batal", function(event) {
    bersih();
});

// tombol simpan diklik
$("#form").on("click", "#simpan", function(event) {
    var baseURL = mlite.url + '/' + mlite.admin;
    event.preventDefault();
    var kode_brng = $('input:text[name=kode_brng]').val();
    var nama_brng = $('input:text[name=nama_brng]').val();
    var kode_sat = $('select[name=kode_sat]').val();
    var kode_satbesar = $('select[name=kode_satbesar]').val();
    var letak_barang = $('input:text[name=letak_barang]').val();
    var dasar = $('input:text[name=dasar]').val();
    var h_beli = $('input:text[name=h_beli]').val();
    var ralan = $('input:text[name=ralan]').val();
    var kelas1 = $('input:text[name=kelas1]').val();
    var kelas2 = $('input:text[name=kelas2]').val();
    var kelas3 = $('input:text[name=kelas3]').val();
    var utama = $('input:text[name=utama]').val();
    var vip = $('input:text[name=vip]').val();
    var vvip = $('input:text[name=vvip]').val();
    var beliluar = $('input:text[name=beliluar]').val();
    var jualbebas = $('input:text[name=jualbebas]').val();
    var karyawan = $('input:text[name=karyawan]').val();
    var stokminimal = $('input:text[name=stokminimal]').val();
    var kdjns = $('select[name=kdjns]').val();
    var isi = $('input:text[name=isi]').val();
    var kapasitas = $('input:text[name=kapasitas]').val();
    var expire = $('input:text[name=expire]').val();
    var status = $('select[name=status]').val();
    var kode_industri = $('select[name=kode_industri]').val();
    var kode_kategori = $('select[name=kode_kategori]').val();
    var kode_golongan = $('select[name=kode_golongan]').val();

    var url = baseURL + '/master/databarangsave?t=' + mlite.token;

    $.post(url, {
        kode_brng: kode_brng,
        nama_brng: nama_brng,
        kode_satbesar: kode_satbesar,
        kode_sat: kode_sat,
        letak_barang: letak_barang,
        dasar: dasar,
        h_beli: h_beli,
        ralan: ralan,
        kelas1: kelas1,
        kelas2: kelas2,
        kelas3: kelas3,
        utama: utama,
        vip: vip,
        vvip: vvip,
        beliluar: beliluar,
        jualbebas: jualbebas,
        karyawan: karyawan,
        stokminimal: stokminimal,
        kdjns: kdjns,
        isi: isi,
        kapasitas: kapasitas,
        expire: expire,
        status: status,
        kode_industri: kode_industri,
        kode_kategori: kode_kategori,
        kode_golongan: kode_golongan
    }, function(data) {
        $("#display").show().load(baseURL + '/master/databarangdisplay?t=' + mlite.token);
        $("#form").hide();
        $("#tutupform").val("Buka Form");
        $("#tutupform").attr("id", "bukaform");
        $('#notif').html("<div class=\"alert alert-success alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
            "Data barang telah disimpan!" +
            "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
            "</div>").show();
    });
});

// ketika baris data diklik
$("#display").on("click", ".edit", function(event) {
    var baseURL = mlite.url + '/' + mlite.admin;
    event.preventDefault();
    var url = baseURL + '/master/databarangform?t=' + mlite.token;
    var kode_brng = $(this).attr("data-kode_brng");

    $.post(url, { kode_brng: kode_brng }, function(data) {
        // tampilkan data
        $("#form").html(data).show();
        $("#bukaform").val("Tutup Form");
        $("#bukaform").attr("id", "tutupform");
    });
});

// ketika tombol hapus ditekan
$("#form").on("click", "#hapus", function(event) {
    var baseURL = mlite.url + '/' + mlite.admin;
    event.preventDefault();
    var url = baseURL + '/master/databaranghapus?t=' + mlite.token;
    var kode_brng = $('input:text[name=ikode_brngd]').val();

    // tampilkan dialog konfirmasi
    bootbox.confirm("Apakah Anda yakin ingin menghapus data ini?", function(result) {
        // ketika ditekan tombol ok
        if (result) {
            // mengirimkan perintah penghapusan
            $.post(url, {
                kode_brng: kode_brng
            }, function(data) {
                // sembunyikan form, tampilkan data yang sudah di perbaharui, tampilkan notif
                $("#form").hide();
                $("#tutupform").val("Buka Form");
                $("#tutupform").attr("id", "bukaform");
                $("#display").load(baseURL + '/master/databarangdisplay?t=' + mlite.token);
                $('#notif').html("<div class=\"alert alert-danger alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
                    "Data barang telah dihapus!" +
                    "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
                    "</div>").show();
            });
        }
    });
});

// ketika inputbox pencarian diisi
$('input:text[name=cari]').on('input', function(e) {
    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/master/databarangdisplay?t=' + mlite.token;
    var cari = $('input:text[name=cari]').val();

    if (cari != "") {
        $.post(url, { cari: cari }, function(data) {
            // tampilkan data yang sudah di perbaharui
            $("#display").html(data).show();
        });
    } else {
        $("#display").load(baseURL + '/master/databarangdisplay?t=' + mlite.token);
    }

});
// end pencarian