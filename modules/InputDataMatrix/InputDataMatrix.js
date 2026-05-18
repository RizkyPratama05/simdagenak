(function() {
    MyApp.renderMainTpl();
    var $me = MyApp.$me;
    var curKode = null;

    function showSection(id) {
        $me('.dp-section').removeClass('active');
        $me('#' + id).addClass('active');
    }

    // Load Tahun
    MyApp.ajax({ Module: 'DataPilah', option: 'ACTION', action: 'listTahun' }, function(resp) {
        if (resp.success) {
            var html = '';
            $.each(resp.result, function(i, v) {
                html += '<option value="' + v.tahun + '">' + v.tahun + '</option>';
            });
            $me('#cbTahunMatriks').html(html);
            $me('#cbTahunMatriks').val(new Date().getFullYear());
        }
    });

    function loadAssigned() {
        MyApp.ajax({ option: 'ACTION', action: 'listAssigned' }, function(resp) {
            if (resp.success) {
                var html = '';
                $.each(resp.data, function(i, v) {
                    html += '<div class="col-md-4">' +
                        '<div class="matrix-card jarviswidget jarviswidget-color-blue" data-kode="' + v.kode_data_pilah + '" data-judul="' + v.judul_data_pilah + '">' +
                        '<div style="padding: 15px; border: 1px solid #ddd; background:#fff; border-radius:4px; height: 120px; overflow: hidden;">' +
                        '<h3 style="margin:0 0 10px 0; font-size:16px; color:#2196F3;">' + v.judul_data_pilah + '</h3>' +
                        '<p class="text-muted" style="font-size:12px;">Kode: ' + v.kode_data_pilah + '</p>' +
                        '<div class="text-right"><i class="fa fa-arrow-circle-right fa-2x" style="color:#eee;"></i></div>' +
                        '</div></div></div>';
                });
                if (resp.data.length == 0) html = '<div class="col-md-12 text-center text-muted" style="padding:50px;">Belum ada matriks yang ditugaskan kepada instansi Anda.</div>';
                $me('#listMatriksAssigned').html(html);
            }
        });
    }

    loadAssigned();

    $me('#listMatriksAssigned').on('click', '.matrix-card', function() {
        curKode = $(this).data('kode');
        var judul = $(this).data('judul');
        $me('#detailJudul').text(judul);
        showSection('sectionEntry');
        loadMatriks();
    });

    $me('.btKembali').on('click', function() {
        showSection('sectionDaftar');
    });

    function loadMatriks() {
        var tahun = $me('#cbTahunMatriks').val();
        MyApp.ajax({
            Module: 'DataPilah', // Reuse logic from DataPilah
            option: 'ACTION', action: 'getMatriks',
            kode_data_pilah: curKode, tahun: tahun
        }, function (resp) {
            if (!resp.success) return;

            var kolom = resp.kolom;
            var baris = resp.baris;
            var id_instansi_res = resp.id_instansi;

            if (kolom.length === 0 || baris.length === 0) {
                $me('#matriksTable').hide();
                $me('#matriksEmpty').show();
                return;
            }

            $me('#matriksEmpty').hide();
            $me('#matriksTable').show();

            // Build header
            var html = '<thead><tr>';
            html += '<th style="width:40px;">No</th>';
            html += '<th style="min-width:140px;">Uraian</th>';
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
                    var k = kolom[ci];
                    var isJml = false;
                    if (k) {
                        var name = (k.nama_kolom || '').toLowerCase().trim();
                        var header = (k.header_kolom || '').toLowerCase().trim();
                        if (name === 'jumlah' || name === 'total' || name === 'l+p' || name === 'jml' || name === 'l + p' ||
                            header === 'jumlah' || header === 'total' || header === 'l+p' || header === 'jml' || header === 'l + p') {
                            isJml = true;
                        }
                    }

                    var inputAttrs = isJml 
                        ? ' readonly="readonly" style="background-color: #f5f5f5; cursor: not-allowed; font-weight: bold;" class="cell-input cell-jumlah" ' 
                        : ' class="cell-input" ';

                    html += '<td><input type="text" ' + inputAttrs +
                        'data-kode-baris="' + b.kode_baris + '" ' +
                        'data-kode-kolom="' + c.kode_kolom + '" ' +
                        'data-id-instansi="' + id_instansi_res + '" ' +
                        'data-header-kolom="' + (k ? k.header_kolom || '' : '') + '" ' +
                        'data-nama-kolom="' + (k ? k.nama_kolom || '' : '') + '" ' +
                        'value="' + (c.val !== null && c.val !== '' ? c.val : '') + '"></td>';
                });
                html += '</tr>';
            });
            html += '</tbody>';

            $me('#matriksTable').html(html);
            recalculateSums();
        });
    }

    function recalculateSums() {
        $me('#matriksTable tbody tr').each(function () {
            var $row = $(this);
            var groups = {};
            var jumlahInputs = [];
            
            // First pass: collect regular inputs and group them by header, collect sum inputs
            $row.find('.cell-input').each(function () {
                var $input = $(this);
                var header = ($input.data('header-kolom') || '').toString().toLowerCase().trim();
                var name = ($input.data('nama-kolom') || '').toString().toLowerCase().trim();
                
                var isJml = (name === 'jumlah' || name === 'total' || name === 'l+p' || name === 'jml' || name === 'l + p' ||
                             header === 'jumlah' || header === 'total' || header === 'l+p' || header === 'jml' || header === 'l + p');
                
                if (isJml) {
                    jumlahInputs.push($input);
                } else {
                    if (!groups[header]) {
                        groups[header] = [];
                    }
                    groups[header].push($input);
                }
            });
            
            // Second pass: compute and update sums
            $.each(jumlahInputs, function (i, $jInput) {
                var jHeader = ($jInput.data('header-kolom') || '').toString().toLowerCase().trim();
                var sum = 0;
                var groupInputs = groups[jHeader] || [];
                
                $.each(groupInputs, function (j, $input) {
                    var valStr = $input.val().replace(',', '.');
                    var val = parseFloat(valStr) || 0;
                    sum += val;
                });
                
                var formattedSum = sum % 1 === 0 ? sum : parseFloat(sum.toFixed(4));
                $jInput.val(formattedSum);
            });
        });
    }

    $me('#matriksContainer').on('input', '.cell-input:not([readonly])', function () {
        recalculateSums();
    });

    $me('#matriksContainer').on('blur', '.cell-input', function () {
        var $input = $(this);
        if ($input.prop('readonly')) return; // Don't save readonly cells from frontend
        var val = $input.val();
        var kodeBaris = $input.data('kode-baris');
        var kodeKolom = $input.data('kode-kolom');
        var idInstansi = $input.data('id-instansi');
        var tahun = $me('#cbTahunMatriks').val();

        MyApp.ajax({
            Module: 'DataPilah',
            option: 'ACTION', action: 'saveCell',
            data: {
                kode_data_pilah: curKode,
                kode_baris: kodeBaris,
                kode_kolom: kodeKolom,
                tahun: tahun,
                id_instansi: idInstansi,
                val: val
            }
        }, function (resp) {
            if (resp.success) {
                $input.css('background', '#d4edda');
                setTimeout(function () { $input.css('background', ''); }, 600);
            }
        });
    });

    $me('#cbTahunMatriks').on('change', function () { loadMatriks(); });
    $me('.btRefreshMatriks').on('click', function () { loadMatriks(); });

})();
