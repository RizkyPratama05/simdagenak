// DataPilah.js — Logika modul DataPilah terpadu
(function () {
    MyApp.renderMainTpl();

    var $me = MyApp.$me;
    var curKode = null; // kode_data_pilah yang sedang di-detail
    var curHeaderBaris = 'Kecamatan/Wilayah';

    // ====================================================================
    // UTILITAS: Tampilkan/sembunyikan section
    // ====================================================================
    function showSection(id) {
        $me('.dp-section').removeClass('active');
        $me('#' + id).addClass('active');
    }

    // ====================================================================
    // SECTION 1: DAFTAR DATA PILAH (DataTables)
    // ====================================================================
    $me.nomor = 0;
    $me.index = 0;
    $me.dataRow = [];
    $me.draw = 0;
    $me.aksi = 'add';

    // PENTING: Gunakan $() global untuk form di dalam modal,
    // karena Bootstrap memindahkan modal ke <body> (di luar scope $me).
    var idForm = $('#form_input');

    var oTable = $me('#datatable_fixed_column').DataTable({
        serverSide: true,
        processing: true,
        destroy: true,
        bInfo: true,
        ajax: {
            url: "service.php",
            method: "POST",
            data: {
                Module: MyApp.curMod,
                option: "ACTION",
                action: "list",
                draw: function () { $me.draw++; return $me.draw; }
            }
        },
        columns: [
            {
                data: "id_data_pilah", width: "5%",
                render: function (d, t, r, meta) { return meta.row + meta.settings._iDisplayStart + 1; }
            },
            {
                data: function (val) {
                    $me.dataRow[$me.index] = val;
                    var btn = '<a href="#" class="btKelolaMatriks btn btn-xs btn-info" data-index="' + $me.index + '">' +
                        '<i class="fa fa-th"></i> Kelola Matriks</a> ';
                    btn += '<a href="#" class="btUpdate btn btn-xs btn-primary" data-index="' + $me.index + '">' +
                        '<i class="fa fa-pencil"></i></a> ';
                    btn += '<a href="#" class="btHapus btn btn-xs btn-danger" data-index="' + $me.index + '">' +
                        '<i class="fa fa-trash"></i></a>';
                    $me.index++;
                    return btn;
                },
                width: '22%', orderable: false
            },
            { data: "judul_data_pilah" },
            { data: "kode_data_pilah", width: "10%" },
            { data: "instansi" },
            {
                data: "aktif", width: "8%",
                render: function (d) {
                    return d == 1 ? '<span class="label label-success">Ya</span>' : '<span class="label label-danger">Tidak</span>';
                }
            }
        ],
        dom: "<'dt-toolbar'<'col-xs-12 col-sm-6'f><'col-sm-6 col-xs-12 hidden-xs'<'toolbar'>>r>t" +
            "<'dt-toolbar-footer'<'col-sm-6 col-xs-12 hidden-xs'i><'col-xs-12 col-sm-6'p>>",
        fnRowCallback: function (nRow, aData, iDisplayIndex) {
            var info = $(this).DataTable().page.info();
            $("td:nth-child(1)", nRow).html(info.start + iDisplayIndex + 1);
            return nRow;
        },
        initComplete: function () {
            var buttonHeader = '<button data-aksi="add" class="btn btn-success btTopAdd" style="margin:5px 5px 0 0">' +
                '<i class="fa fa-plus"></i> Tambah Data</button>' +
                '<button data-aksi="refresh" class="btn btn-danger btTopRefresh" style="margin:5px 5px 0 0">' +
                '<i class="fa fa-refresh"></i> Refresh</button>';
            $me("div.toolbar").html(buttonHeader);

            // Bind di sini karena tombol baru saja dimasukkan ke DOM
            $me('.btTopAdd').click(function () {
                $me.aksi = 'add';
                $('#myModal').find('form')[0].reset();
                $('#myModal .modal-title').html("Tambah Data Pilah");
                $('#myModal').modal('show');
            });
            $me('.btTopRefresh').click(function () {
                $me.index = 0; $me.dataRow = [];
                oTable.ajax.reload();
            });
        }
    });

    // Event delegasi untuk tabel daftar — gunakan $('table') global
    // agar tetap bekerja setelah DataTables re-render
    var selectortable = $('table');

    selectortable.on('click', '.btKelolaMatriks', function (e) {
        e.preventDefault();
        var idx = $(this).data('index');
        var row = $me.dataRow[idx];
        curKode = row.kode_data_pilah;
        curHeaderBaris = row.header_baris || 'Kecamatan/Wilayah';
        showDetail(row);
    });

    selectortable.on('click', '.btUpdate', function (e) {
        e.preventDefault();
        $me.aksi = 'update';
        var idx = $(this).data('index');
        var row = $me.dataRow[idx];
        $('#myModal .modal-title').html("Update Data Pilah");
        MyApp.setFormValues(idForm, row);
        $('#myModal').modal('show');
    });

    selectortable.on('click', '.btHapus', function (e) {
        e.preventDefault();
        var idx = $(this).data('index');
        var row = $me.dataRow[idx];
        if (confirm('Yakin ingin menghapus "' + row.judul_data_pilah + '"?')) {
            hapusDataPilah(row);
        }
    });

    // Simpan form data pilah (header) — gunakan $() global karena modal ada di <body>
    $('.btSimpan').on('click', function () {
        var dataForm = MyApp.getFormValues(idForm);
        if (!dataForm.kode_data_pilah || dataForm.kode_data_pilah.trim() === '') {
            alert("Kode data pilah harus diisi!"); return;
        }
        MyApp.ajax({
            option: 'ACTION', action: $me.aksi, data: dataForm
        }, function (resp) {
            if (resp.success) {
                $('#myModal').modal('hide');
                alert(resp.msg);
                $me.index = 0; $me.dataRow = [];
                oTable.ajax.reload();
            } else { alert(resp.msg); }
        });
    });

    function hapusDataPilah(dataForm) {
        MyApp.ajax({
            option: 'ACTION', action: 'delete', data: dataForm
        }, function (resp) {
            if (resp.success) {
                alert(resp.msg);
                $me.index = 0; $me.dataRow = [];
                oTable.ajax.reload();
            } else { alert(resp.msg); }
        });
    }

    // ====================================================================
    // LOAD DROPDOWNS: Tahun & Unit
    // ====================================================================
    function loadDropdowns() {
        // Load Tahun
        MyApp.ajax({ option: 'ACTION', action: 'listTahun' }, function (resp) {
            if (resp.success) {
                var html = '';
                $.each(resp.result, function (i, v) {
                    html += '<option value="' + v.tahun + '">' + v.tahun + '</option>';
                });
                $me('#cbTahunMatriks').html(html);

                // Set to current year if possible
                var curYear = new Date().getFullYear();
                $me('#cbTahunMatriks').val(curYear);
            }
        });
    }

    loadDropdowns();

    // ====================================================================
    // SECTION 2: DETAIL MATRIKS
    // ====================================================================
    function showDetail(row) {
        $me('#detailJudul').text(row.judul_data_pilah);
        $me('#detailKode').text('Kode: ' + row.kode_data_pilah + ' | Instansi: ' + (row.instansi || '-'));
        // Set hidden fields di modal baris/kolom (global karena modal pindah ke body)
        $('.input-kode-dp').val(row.kode_data_pilah);

        // ROLE CHECK: Sembunyikan manajemen baris/kolom jika bukan admin
        var userData = MyApp.userData;
        if (userData.isadmin != 1) {
            $me('.btTambahBaris, .btTambahKolom').hide();
            $me('#panelBaris, #panelKolom').hide();
            $me('#panelMatriks').removeClass('col-md-8').addClass('col-md-12');
        } else {
            $me('.btTambahBaris, .btTambahKolom').show();
            $me('#panelBaris, #panelKolom').show();
            $me('#panelMatriks').removeClass('col-md-12').addClass('col-md-8');
        }

        showSection('sectionDetail');
        loadBaris();
        loadKolom();
        loadMatriks();
    }

    // ====================================================================
    // KECAMATAN REFERENCE MODAL
    // ====================================================================
    var kecamatanSleman = [
        "Moyudan", "Minggir", "Seyegan", "Godean", "Gamping", "Mlati", "Depok", "Berbah",
        "Prambanan", "Kalasan", "Ngemplak", "Ngaglik", "Sleman", "Tempel", "Turi", "Pakem", "Cangkringan"
    ];

    $('.btRefBaris').on('click', function () {
        renderRefBaris('');
        $('#modalRefBaris').modal('show');
    });

    $('#searchRefBaris').on('keyup', function () {
        renderRefBaris($(this).val());
    });

    function renderRefBaris(search) {
        var html = '';
        var filtered = kecamatanSleman.filter(function (v) {
            return v.toLowerCase().indexOf(search.toLowerCase()) > -1;
        });
        $.each(filtered, function (i, v) {
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + v + '</td>' +
                '<td><button class="btn btn-xs btn-success btPilihKec" data-nama="' + v + '">Pilih</button></td>' +
                '</tr>';
        });
        if (filtered.length == 0) html = '<tr><td colspan="3" class="text-center">Tidak ditemukan</td></tr>';
        $('#tableRefBaris tbody').html(html);
    }

    $('#tableRefBaris').on('click', '.btPilihKec', function () {
        var nama = $(this).data('nama');
        $('#nama_baris_input').val(nama);
        $('#modalRefBaris').modal('hide');
    });

    // Kembali ke daftar
    $me('.btKembali').on('click', function () {
        curKode = null;
        showSection('sectionDaftar');
        $me.index = 0; $me.dataRow = [];
        oTable.ajax.reload();
    });

    // ====================================================================
    // PANEL BARIS & AUTO CODE
    // ====================================================================

    $('.btAutoCode').on('click', function () {
        MyApp.ajax({
            option: 'ACTION', action: 'generateCodeBaris',
            kode_data_pilah: curKode
        }, function (resp) {
            if (resp.success) {
                $('#kode_baris_input').val(resp.code);
            }
        });
    });

    $('.btAutoCodeKolom').on('click', function () {
        MyApp.ajax({
            option: 'ACTION', action: 'generateCodeKolom',
            kode_data_pilah: curKode
        }, function (resp) {
            if (resp.success) {
                $('#kode_kolom_input').val(resp.code);
            }
        });
    });


    function loadBaris() {
        MyApp.ajax({
            option: 'ACTION', action: 'listBaris', kode_data_pilah: curKode
        }, function (resp) {
            if (!resp.success) return;
            var tbody = '';
            $.each(resp.data, function (i, b) {
                tbody += '<tr>' +
                    '<td>' + (b.no_urut || (i + 1)) + '</td>' +
                    '<td>' + b.kode_baris + '</td>' +
                    '<td>' + b.nama_baris + '</td>' +
                    '<td><button class="btn-action-delete btHapusBaris" data-id="' + b.id_data_pilah_baris + '" data-nama="' + b.nama_baris + '" title="Hapus">' +
                    '<i class="fa fa-trash-o"></i></button></td>' +
                    '</tr>';
            });
            
            // Toggle Gunakan 17 Kecamatan BPS button based on row count
            if (resp.data.length === 0) {
                tbody = '<tr><td colspan="4" class="text-center text-muted">Belum ada baris</td></tr>';
                $me('.btTambahKecamatanBps').prop('disabled', false);
            } else {
                $me('.btTambahKecamatanBps').prop('disabled', true);
            }
            
            $me('#tabelBaris tbody').html(tbody);
        });
    }

    // Tombol Gunakan 17 Kecamatan BPS
    $me('.btTambahKecamatanBps').off('click').on('click', function () {
        if(confirm("Apakah Anda yakin ingin mengisi daftar baris secara otomatis dengan 17 Kecamatan Sleman BPS?")) {
            $me('.overlay').show();
            MyApp.ajax({
                option: 'ACTION', action: 'addKecamatanBps',
                kode_data_pilah: curKode
            }, function (resp) {
                $me('.overlay').hide();
                alert(resp.msg);
                if (resp.success) {
                    loadBaris();
                    loadMatriks();
                }
            });
        }
    });

    // Tombol Tambah Baris — gunakan $() global karena akan membuka modal
    $('.btTambahBaris').on('click', function () {
        $('#form_baris')[0].reset();
        $('#form_baris .input-kode-dp').val(curKode);
        $('#modalBaris').modal('show');

        // Langsung trigger auto code saat modal dibuka
        $('.btAutoCode').trigger('click');
    });

    // Simpan baris — gunakan $() global karena tombol ada di modal
    $('.btSimpanBaris').on('click', function () {
        var dataForm = MyApp.getFormValues($('#form_baris'));
        if (!dataForm.kode_baris || !dataForm.nama_baris) {
            alert("Kode dan Nama baris harus diisi!"); return;
        }
        MyApp.ajax({
            option: 'ACTION', action: 'addBaris', data: dataForm
        }, function (resp) {
            if (resp.success) {
                $('#modalBaris').modal('hide');
                loadBaris();
                loadMatriks();
            } else { alert(resp.msg); }
        });
    });

    $me('#tabelBaris').on('click', '.btHapusBaris', function () {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        if (confirm('Hapus baris "' + nama + '"? Data cell terkait juga akan dihapus.')) {
            MyApp.ajax({
                option: 'ACTION', action: 'deleteBaris', data: { id_data_pilah_baris: id }
            }, function (resp) {
                if (resp.success) { loadBaris(); loadMatriks(); }
                else { alert(resp.msg); }
            });
        }
    });

    // ====================================================================
    // PANEL KOLOM
    // ====================================================================
    function loadKolom() {
        MyApp.ajax({
            option: 'ACTION', action: 'listKolom', kode_data_pilah: curKode
        }, function (resp) {
            if (!resp.success) return;
            var tbody = '';
            $.each(resp.data, function (i, k) {
                tbody += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + k.kode_kolom + '</td>' +
                    '<td>' + k.nama_kolom + '</td>' +
                    '<td>' + (k.header_kolom || '-') + '</td>' +
                    '<td><button class="btn-action-delete btHapusKolom" data-id="' + k.id_data_pilah_kolom + '" data-nama="' + k.nama_kolom + '" title="Hapus">' +
                    '<i class="fa fa-trash-o"></i></button></td>' +
                    '</tr>';
            });
            if (resp.data.length === 0) {
                tbody = '<tr><td colspan="5" class="text-center text-muted">Belum ada kolom</td></tr>';
            }
            $me('#tabelKolom tbody').html(tbody);
        });
    }

    // Tombol Tambah Kolom — gunakan $() global karena akan membuka modal
    $('.btTambahKolom').on('click', function () {
        $('#form_kolom')[0].reset();
        $('#form_kolom .input-kode-dp').val(curKode);
        $('#modalKolom').modal('show');

        // Langsung trigger auto code kolom saat modal dibuka
        $('.btAutoCodeKolom').trigger('click');
    });

    // Simpan kolom — gunakan $() global karena tombol ada di modal
    $('.btSimpanKolom').on('click', function () {
        var dataForm = MyApp.getFormValues($('#form_kolom'));
        if (!dataForm.kode_kolom || !dataForm.nama_kolom) {
            alert("Kode dan Nama kolom harus diisi!"); return;
        }
        MyApp.ajax({
            option: 'ACTION', action: 'addKolom', data: dataForm
        }, function (resp) {
            if (resp.success) {
                $('#modalKolom').modal('hide');
                loadKolom();
                loadMatriks();
            } else { alert(resp.msg); }
        });
    });

    $me('#tabelKolom').on('click', '.btHapusKolom', function () {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        if (confirm('Hapus kolom "' + nama + '"? Data cell terkait juga akan dihapus.')) {
            MyApp.ajax({
                option: 'ACTION', action: 'deleteKolom', data: { id_data_pilah_kolom: id }
            }, function (resp) {
                if (resp.success) { loadKolom(); loadMatriks(); }
                else { alert(resp.msg); }
            });
        }
    });

    // ====================================================================
    // PREVIEW MATRIKS (live render + inline edit)
    // ====================================================================
    function loadMatriks() {
        var tahun = $me('#cbTahunMatriks').val();

        MyApp.ajax({
            option: 'ACTION', action: 'getMatriks',
            kode_data_pilah: curKode, tahun: tahun
        }, function (resp) {
            if (!resp.success) return;

            var kolom = resp.kolom;
            var baris = resp.baris;
            var id_instansi_res = resp.id_instansi;

            if (kolom.length === 0 || baris.length === 0) {
                $me('#matriksTable').hide();
                $me('#matriksEmpty').show().text(
                    'Tambahkan baris dan kolom terlebih dahulu untuk melihat matriks. ' +
                    '(Baris: ' + baris.length + ', Kolom: ' + kolom.length + ')'
                );
                return;
            }

            $me('#matriksEmpty').hide();
            $me('#matriksTable').show();

            // Build header
            var html = '<thead><tr>';
            html += '<th style="width:40px;">No</th>';
            html += '<th style="min-width:140px;">' + curHeaderBaris + '</th>';
            $.each(kolom, function (i, k) {
                html += '<th>' + (k.header_kolom || k.nama_kolom) + '</th>';
            });
            html += '</tr></thead>';

            // Build body
            html += '<tbody>';
            $.each(baris, function (bi, b) {
                html += '<tr>';
                html += '<td class="text-center">' + (b.no_urut || (bi + 1)) + '</td>';
                html += '<td>' + b.nama_baris + '</td>';
                $.each(b.cells, function (ci, c) {
                    html += '<td class="text-right">' + (c.val !== null && c.val !== '' ? c.val : '-') + '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody>';

            $me('#matriksTable').html(html);
        });
    }

    // Refresh matriks on tahun change
    $me('#cbTahunMatriks').on('change', function () { loadMatriks(); });
    $me('.btRefreshMatriks').on('click', function () { loadMatriks(); });

    // ====================================================================
    // INIT: sembunyikan overlay loading
    // ====================================================================
    $('.modal-backdrop').addClass('hide');
    setTimeout(function () {
        $me('.overlay').hide();
    }, 500);

})();

//# sourceURL=DataPilah.js
