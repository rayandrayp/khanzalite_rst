
// ketika tombol acc ditekan
$("#farmasi_pengajuan").on("click", ".acc_pengajuan", function(event) {
    var baseURL = mlite.url + '/' + mlite.admin;
    event.preventDefault();
    var url = baseURL + '/manajemen/accpengajuan?t=' + mlite.token;
    var nopengajuan = $(this).attr("data-nopengajuan");
    console.log('nopengajuan '+nopengajuan);

    // tampilkan dialog konfirmasi
    bootbox.confirm("Apakah Anda yakin ingin menyetujui pengajuan ini?", function(result) {
        // ketika ditekan tombol ok
        if (result) {
            // mengirimkan perintah penghapusan
            $.post(url, {
                no_pengajuan: nopengajuan
            }, function(data) {
                alert("Pengajuan berhasil disetujui.");
                location.reload()
            });
        }
    });
});

// ketika tombol tolak ditekan
$("#farmasi_pengajuan").on("click", ".tolak_pengajuan", function(event) {
    var baseURL = mlite.url + '/' + mlite.admin;
    event.preventDefault();
    var url = baseURL + '/manajemen/tolakpengajuan?t=' + mlite.token;
    var nopengajuan = $(this).attr("data-nopengajuan");
    console.log('nopengajuan '+nopengajuan);
    // tampilkan dialog konfirmasi
    let msg = prompt("Silahkan masukkan alasan pengajuan ditoak");
    if (person != null) {
        $.post(url, {
            no_pengajuan: nopengajuan,
            msg: msg
        }, function(data) {
            alert("Pengajuan berhasil ditolak.");
            location.reload()
        });
    }
    // bootbox.confirm("Apakah Anda yakin ingin menolak pengajuan ini?", function(result) {
    //     // ketika ditekan tombol ok
    //     if (result) {
    //         // mengirimkan perintah penghapusan
    //         $.post(url, {
    //             no_pengajuan: nopengajuan
    //         }, function(data) {
    //             alert("Pengajuan berhasil ditolak.");
    //             location.reload()
    //         });
    //     }
    // });
});