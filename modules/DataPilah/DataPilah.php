<?php

class DataPilah extends Database
{

    function __construct()
    {
        parent::__construct();
    }

    private function get_userId()
    {
        $user = new os;
        $userData = $user->getUserData();
        $userDataArr = json_decode($userData);
        $userId = $userDataArr->user_id;
        return $userId;
    }

    private function findField() {
        $findField = [
            "judul_data_pilah","kode_data_pilah","aktif","kode_instansi","instansi","header_baris"
        ];
        return $findField;
    }

    private function buildSqlSearchingCriteria($keywords, $findField) {
        $arrayKata = explode(' ', $keywords);
        $queryCriteria = array();
        foreach ($arrayKata as $hasil) {
            $criteria = array();
            foreach ($findField as $fieldName) {
                $criteria[] = "LOWER($fieldName) like '%$hasil%'";
            }
            $queryCriteria[] = implode(" OR ", $criteria);
        }
        $resultCriteria = implode(" OR ", $queryCriteria);
        return $resultCriteria;
    }

    // =====================================================================
    // DAFTAR DATA PILAH (header matriks)
    // =====================================================================

    public function ACTION_list($return=false)
    {
        $params = isset($_GET) ? $_GET : $_POST;
        $userId = $this->get_userId();
        $sql = 'SELECT * FROM data_pilah';
        if(isset($_POST['search']['value']) && $_POST['search']['value'] !=''){
            $keywords = strtolower($_POST['search']['value']);
            $findField = $this->findField();
            $criteria = $this->buildSqlSearchingCriteria($keywords, $findField);
            $sql .= " where ".$criteria;
        }
        if (isset($_POST['start'])) {
            $start = $_POST['start'];
            $limit = $_POST['length'];
            $sql .= " limit $start,$limit ";
        }

        $arrayData = $this->dbDataSelectAndReturnAll($sql, $params, true);
        if($return){
            return $arrayData;
        }
        $array = array();
        $sqlCount = "SELECT count(*) FROM data_pilah ";
        $countData = $this->dbDataGetValue($sqlCount);
        $array['recordsTotal'] = $countData;
        $array['recordsFiltered'] = $countData;
        $array['draw'] = $_POST['draw'];
        $array['data'] = (array)$arrayData;
        echo json_encode($array);
    }

    public function ACTION_add(){
        $params = isset($_GET) ? $_GET : $_POST;
        $kode_data_pilah = $params['data']['kode_data_pilah'];
        $sql_k = "SELECT * FROM data_pilah WHERE kode_data_pilah='$kode_data_pilah'";
        $sql = "insert into data_pilah (judul_data_pilah,kode_data_pilah,aktif,kode_instansi,instansi,header_baris) VALUES 
                                          (:judul_data_pilah,:kode_data_pilah,:aktif,:kode_instansi,:instansi,:header_baris)";
        if($this->dbDataRowsCount($sql_k,$params['data']) > 0) {
            echo '{"success" : false,"msg": "Kode Data Pilah Sudah Ada"}';
        }else {
            echo $this->dbDataExecute($sql,$params['data']);
        }
    }

    public function ACTION_update(){
        $params = isset($_GET) ? $_GET : $_POST;
        $sql = "update data_pilah set judul_data_pilah=:judul_data_pilah,kode_data_pilah=:kode_data_pilah,aktif=:aktif,kode_instansi=:kode_instansi,instansi=:instansi,header_baris=:header_baris
                 WHERE id_data_pilah=:id_data_pilah";
        echo $this->dbDataExecute($sql,$params['data']);
    }

    public function ACTION_delete(){
        $params = isset($_GET) ? $_GET : $_POST;
        $sql = "delete from data_pilah WHERE id_data_pilah=:id_data_pilah";
        echo $this->dbDataExecute($sql,$params['data']);
    }

    // =====================================================================
    // DETAIL: Ambil detail satu data pilah berdasarkan kode
    // =====================================================================

    public function ACTION_getDetail(){
        $params = isset($_GET) ? $_GET : $_POST;
        $kode = $params['kode_data_pilah'];
        $sql = "SELECT * FROM data_pilah WHERE kode_data_pilah = '$kode'";
        $data = $this->dbDataSelectAndReturnAll($sql, $params, true);
        $result = new stdClass();
        if(count($data) > 0){
            $result->success = true;
            $result->data = $data[0];
        } else {
            $result->success = false;
            $result->msg = "Data tidak ditemukan";
        }
        echo json_encode($result);
    }

    // =====================================================================
    // MANAJEMEN BARIS
    // =====================================================================

    public function ACTION_listBaris(){
        $params = isset($_GET) ? $_GET : $_POST;
        $kode = $params['kode_data_pilah'];
        $sql = "SELECT * FROM data_pilah_baris WHERE kode_data_pilah = '$kode' ORDER BY no_urut ASC";
        $data = $this->dbDataSelectAndReturnAll($sql, $params, true);
        $result = new stdClass();
        $result->success = true;
        $result->data = $data;
        echo json_encode($result);
    }

    public function ACTION_addBaris(){
        $params = isset($_GET) ? $_GET : $_POST;
        $d = $params['data'];

        // Cek duplikat kode_baris
        $kode_baris = $d['kode_baris'];
        $sqlCek = "SELECT count(*) FROM data_pilah_baris WHERE kode_baris = '$kode_baris'";
        $count = $this->dbDataGetValue($sqlCek);
        if($count > 0){
            echo '{"success":false,"msg":"Kode baris sudah ada"}';
            return;
        }

        // Auto no_urut
        $kode_dp = $d['kode_data_pilah'];
        $sqlMax = "SELECT IFNULL(MAX(no_urut),0)+1 FROM data_pilah_baris WHERE kode_data_pilah = '$kode_dp'";
        $noUrut = $this->dbDataGetValue($sqlMax);

        $sql = "INSERT INTO data_pilah_baris (kode_data_pilah, no_urut, kode_baris, nama_baris, aktif) 
                VALUES (:kode_data_pilah, $noUrut, :kode_baris, :nama_baris, :aktif)";
        echo $this->dbDataExecute($sql, $d);
    }

    public function ACTION_addKecamatanBps(){
        $params = isset($_GET) ? $_GET : $_POST;
        if(isset($_GET['kode_data_pilah'])) {
            $params = $_GET;
        }
        $kode_dp = $params['kode_data_pilah'];

        // Cek apakah sudah ada baris
        $sqlCek = "SELECT count(*) FROM data_pilah_baris WHERE kode_data_pilah = '$kode_dp'";
        $count = $this->dbDataGetValue($sqlCek);
        if ($count > 0) {
            echo json_encode(array("success" => false, "msg" => "Daftar baris harus kosong untuk menggunakan 17 kecamatan BPS."));
            return;
        }

        $kecamatanSleman = array(
            "Moyudan", "Minggir", "Seyegan", "Godean", "Gamping", "Mlati", "Depok", "Berbah",
            "Prambanan", "Kalasan", "Ngemplak", "Ngaglik", "Sleman", "Tempel", "Turi", "Pakem", "Cangkringan"
        );

        $successCount = 0;
        foreach ($kecamatanSleman as $index => $nama) {
            $seq = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $kode_baris = $kode_dp . "." . $seq;
            $noUrut = $index + 1;

            $sql = "INSERT INTO data_pilah_baris (kode_data_pilah, no_urut, kode_baris, nama_baris, aktif) 
                    VALUES ('$kode_dp', $noUrut, '$kode_baris', '$nama', 1)";
            $this->dbDataExecute($sql);
            $successCount++;
        }

        echo json_encode(array("success" => true, "msg" => "Berhasil menambahkan 17 kecamatan Sleman BPS."));
    }

    public function ACTION_deleteBaris(){
        $params = isset($_GET) ? $_GET : $_POST;
        $id = $params['data']['id_data_pilah_baris'];
        $sql = "DELETE FROM data_pilah_baris WHERE id_data_pilah_baris = $id";
        echo $this->dbDataExecute($sql);
    }

    // =====================================================================
    // MANAJEMEN KOLOM
    // =====================================================================

    public function ACTION_listKolom(){
        $params = isset($_GET) ? $_GET : $_POST;
        $kode = $params['kode_data_pilah'];
        $sql = "SELECT * FROM data_pilah_kolom WHERE kode_data_pilah = '$kode' ORDER BY id_data_pilah_kolom ASC";
        $data = $this->dbDataSelectAndReturnAll($sql, $params, true);
        $result = new stdClass();
        $result->success = true;
        $result->data = $data;
        echo json_encode($result);
    }

    public function ACTION_addKolom(){
        $params = isset($_GET) ? $_GET : $_POST;
        $d = $params['data'];

        // Cek duplikat kode_kolom
        $kode_kolom = $d['kode_kolom'];
        $sqlCek = "SELECT count(*) FROM data_pilah_kolom WHERE kode_kolom = '$kode_kolom'";
        $count = $this->dbDataGetValue($sqlCek);
        if($count > 0){
            echo '{"success":false,"msg":"Kode kolom sudah ada"}';
            return;
        }

        $sql = "INSERT INTO data_pilah_kolom (kode_data_pilah, header_kolom, nama_kolom, kode_kolom, tipe_kolom, aktif) 
                VALUES (:kode_data_pilah, :header_kolom, :nama_kolom, :kode_kolom, :tipe_kolom, :aktif)";
        echo $this->dbDataExecute($sql, $d);
    }

    public function ACTION_deleteKolom(){
        $params = isset($_GET) ? $_GET : $_POST;
        $id = $params['data']['id_data_pilah_kolom'];
        $sql = "DELETE FROM data_pilah_kolom WHERE id_data_pilah_kolom = $id";
        echo $this->dbDataExecute($sql);
    }

    // =====================================================================
    // MATRIKS: Render data matriks (baris x kolom) + cell values
    // =====================================================================

    // =====================================================================
    // MATRIKS: Render data matriks (baris x kolom) + cell values
    // =====================================================================

    public function ACTION_getMatriks(){
        $params = isset($_GET) ? $_GET : $_POST;
        $kode = $params['kode_data_pilah'];
        $tahun = isset($params['tahun']) ? (int)$params['tahun'] : date('Y');
        
        // Get unit id from session or params
        $os = new Os();
        $user_id = $os->getUserLogin();
        $userData = json_decode($os->getUserData());
        
        $id_instansi = isset($params['id_instansi']) ? (int)$params['id_instansi'] : 0;
        
        // If not admin, force to user's unit
        if ($userData->isadmin == 0) {
            $id_instansi = $os->getUserUnit();
        }

        // Ambil baris
        $sqlBaris = "SELECT * FROM data_pilah_baris WHERE kode_data_pilah = '$kode' ORDER BY no_urut ASC";
        $baris = $this->dbDataSelectAndReturnAll($sqlBaris, $params, true);

        // Ambil kolom
        $sqlKolom = "SELECT * FROM data_pilah_kolom WHERE kode_data_pilah = '$kode' ORDER BY id_data_pilah_kolom ASC";
        $kolom = $this->dbDataSelectAndReturnAll($sqlKolom, $params, true);

        // Ambil semua cell untuk kode + tahun + unit ini
        $sqlCell = "SELECT * FROM data_pilah_cell WHERE kode_data_pilah = '$kode' AND tahun = $tahun AND id_instansi = $id_instansi";
        $cells = $this->dbDataSelectAndReturnAll($sqlCell, $params, true);

        // Bangun lookup cell: key = "kode_baris|kode_kolom" => val
        $cellMap = array();
        foreach($cells as $c){
            $key = $c->kode_baris . '|' . $c->kode_kolom;
            $cellMap[$key] = $c->val;
        }

        // Bangun data matriks
        $matriksRows = array();
        foreach($baris as $b){
            $row = new stdClass();
            $row->kode_baris = $b->kode_baris;
            $row->nama_baris = $b->nama_baris;
            $row->no_urut = $b->no_urut;
            $row->cells = array();
            foreach($kolom as $k){
                $cell = new stdClass();
                $cell->kode_kolom = $k->kode_kolom;
                $key = $b->kode_baris . '|' . $k->kode_kolom;
                $cell->val = isset($cellMap[$key]) ? $cellMap[$key] : '';
                $row->cells[] = $cell;
            }
            $matriksRows[] = $row;
        }

        $result = new stdClass();
        $result->success = true;
        $result->kolom = $kolom;
        $result->baris = $matriksRows;
        $result->tahun = $tahun;
        $result->id_instansi = $id_instansi;
        echo json_encode($result);
    }

    // =====================================================================
    // SAVE CELL: Simpan/Update satu nilai cell
    // =====================================================================

    public function ACTION_saveCell(){
        $params = isset($_GET) ? $_GET : $_POST;
        $d = $params['data'];
        $kode_dp = $d['kode_data_pilah'];
        $kode_baris = $d['kode_baris'];
        $kode_kolom = $d['kode_kolom'];
        $tahun = (int)$d['tahun'];
        $val = $d['val'];
        
        $os = new Os();
        $userData = json_decode($os->getUserData());
        
        $id_instansi = isset($d['id_instansi']) ? (int)$d['id_instansi'] : 0;
        
        // If not admin, force to user's unit
        if ($userData->isadmin == 0) {
            $id_instansi = $os->getUserUnit();
        }
        
        $d['id_instansi'] = $id_instansi;

        // Cek Verifikasi
        $sqlCekVerif = "SELECT count(*) FROM data_pilah_verifikasi WHERE kode_data_pilah='$kode_dp' AND tahun=$tahun AND is_verified=1";
        $isVerif = $this->dbDataGetValue($sqlCekVerif);
        if ($isVerif > 0) {
            echo '{"success":false, "msg":"Data sudah diverifikasi dan tidak dapat diubah."}';
            exit();
        }

        // Cek apakah cell sudah ada
        $sqlCek = "SELECT count(*) FROM data_pilah_cell 
                   WHERE kode_data_pilah='$kode_dp' AND kode_baris='$kode_baris' 
                   AND kode_kolom='$kode_kolom' AND tahun=$tahun AND id_instansi=$id_instansi";
        $count = $this->dbDataGetValue($sqlCek);

        if($count > 0){
            $sql = "UPDATE data_pilah_cell SET val = :val 
                    WHERE kode_data_pilah = :kode_data_pilah 
                    AND kode_baris = :kode_baris 
                    AND kode_kolom = :kode_kolom 
                    AND tahun = :tahun
                    AND id_instansi = :id_instansi";
        } else {
            $sql = "INSERT INTO data_pilah_cell (kode_data_pilah, kode_baris, kode_kolom, tahun, val, id_instansi) 
                    VALUES (:kode_data_pilah, :kode_baris, :kode_kolom, :tahun, :val, :id_instansi)";
        }
        $res = $this->dbDataExecute($sql, $d);

        // --- AUTO SUM RECALCULATION & DB UPDATE ---
        // 1. Get all columns for this matrix
        $sqlKolom = "SELECT * FROM data_pilah_kolom WHERE kode_data_pilah = '$kode_dp' ORDER BY id_data_pilah_kolom ASC";
        $koloms = $this->dbDataSelectAndReturnAll($sqlKolom, null, true);
        
        // 2. Identify sum columns and group columns by header_kolom
        $groups = array();
        foreach ($koloms as $k) {
            $header = strtolower(trim($k->header_kolom ?? ''));
            if (!isset($groups[$header])) {
                $groups[$header] = array(
                    'jumlah_cols' => array(),
                    'regular_cols' => array()
                );
            }
            
            $name = strtolower(trim($k->nama_kolom ?? ''));
            if ($name === 'jumlah' || $name === 'total' || $name === 'l+p' || $name === 'jml' || $name === 'l + p' ||
                $header === 'jumlah' || $header === 'total' || $header === 'l+p' || $header === 'jml' || $header === 'l + p') {
                $groups[$header]['jumlah_cols'][] = $k;
            } else {
                $groups[$header]['regular_cols'][] = $k;
            }
        }
        
        // 3. Recalculate sums and update the database for each group with a "jumlah" column
        foreach ($groups as $header => $groupData) {
            if (empty($groupData['jumlah_cols'])) {
                continue;
            }
            
            $sum = 0.0;
            if (!empty($groupData['regular_cols'])) {
                foreach ($groupData['regular_cols'] as $col) {
                    $cKode = $col->kode_kolom;
                    $sqlVal = "SELECT val FROM data_pilah_cell 
                               WHERE kode_data_pilah = '$kode_dp' 
                               AND kode_baris = '$kode_baris' 
                               AND tahun = $tahun 
                               AND id_instansi = $id_instansi
                               AND kode_kolom = '$cKode'";
                    $valStr = $this->dbDataGetValue($sqlVal);
                    if ($valStr !== null && $valStr !== '') {
                        $valStr = str_replace(',', '.', $valStr);
                        $sum += (float)$valStr;
                    }
                }
            }
            
            // Format sum nicely (e.g. if integer, don't show decimal places)
            $formattedSum = ($sum == (int)$sum) ? (int)$sum : $sum;
            
            // Save the sum to each "jumlah" column in this group
            foreach ($groupData['jumlah_cols'] as $jCol) {
                $jKode = $jCol->kode_kolom;
                
                $sqlCekJ = "SELECT count(*) FROM data_pilah_cell 
                            WHERE kode_data_pilah='$kode_dp' AND kode_baris='$kode_baris' 
                            AND kode_kolom='$jKode' AND tahun=$tahun AND id_instansi=$id_instansi";
                $countJ = $this->dbDataGetValue($sqlCekJ);
                
                $jParams = array(
                    'kode_data_pilah' => $kode_dp,
                    'kode_baris' => $kode_baris,
                    'kode_kolom' => $jKode,
                    'tahun' => $tahun,
                    'id_instansi' => $id_instansi,
                    'val' => $formattedSum
                );
                
                if ($countJ > 0) {
                    $sqlSaveJ = "UPDATE data_pilah_cell SET val = :val 
                                 WHERE kode_data_pilah = :kode_data_pilah 
                                 AND kode_baris = :kode_baris 
                                 AND kode_kolom = :kode_kolom 
                                 AND tahun = :tahun
                                 AND id_instansi = :id_instansi";
                } else {
                    $sqlSaveJ = "INSERT INTO data_pilah_cell (kode_data_pilah, kode_baris, kode_kolom, tahun, val, id_instansi) 
                                 VALUES (:kode_data_pilah, :kode_baris, :kode_kolom, :tahun, :val, :id_instansi)";
                }
                $this->dbDataExecute($sqlSaveJ, $jParams);
            }
        }
        
        echo $res;
    }

    // =====================================================================
    // TAHUN & UNIT: Daftar untuk dropdown
    // =====================================================================

    public function ACTION_listTahun() {
        $sql = "SELECT * FROM ref_tahun WHERE aktif = 1 ORDER BY tahun DESC";
        echo $this->dbDataSelectAndReturnAll($sql);
    }

    public function ACTION_listUnit() {
        $sql = "SELECT id, nama_instansi as text FROM reff_unit_kerja ORDER BY nama_instansi ASC";
        echo $this->dbDataSelectAndReturnAll($sql);
    }

    // =====================================================================
    // AUTO CODE: Generate kode baris (0201, 0202, dst)
    // =====================================================================

    public function ACTION_generateCodeBaris() {
        $params = isset($_GET) ? $_GET : $_POST;
        $kode_dp = $params['kode_data_pilah'];
        
        // Cari kode baris terakhir untuk matrix ini
        $sqlMax = "SELECT kode_baris FROM data_pilah_baris WHERE kode_data_pilah = '$kode_dp' ORDER BY id_data_pilah_baris DESC LIMIT 1";
        $lastCode = $this->dbDataGetValue($sqlMax);
        
        if (!$lastCode) {
            // Jika belum ada data, mulai dari [kode_dp].01
            $nextCode = $kode_dp . ".01";
        } else {
            // Jika ada titik, ambil angka setelah titik terakhir
            if (strpos($lastCode, '.') !== false) {
                $parts = explode('.', $lastCode);
                $lastPart = end($parts);
                $prefix = implode('.', array_slice($parts, 0, -1));
                
                $nextSequence = (int)$lastPart + 1;
                $nextCode = $prefix . "." . str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
            } else {
                // Fallback jika tidak ada titik (format lama)
                $sequence = substr($lastCode, strlen($kode_dp));
                $nextSequence = (int)$sequence + 1;
                $nextCode = $kode_dp . "." . str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
            }
        }
        
        echo json_encode(array("success" => true, "code" => $nextCode));
    }

    // =====================================================================
    // AUTO CODE KOLOM: Generate kode kolom (K01, K02, dst)
    // =====================================================================

    public function ACTION_generateCodeKolom() {
        $params = isset($_GET) ? $_GET : $_POST;
        $kode_dp = $params['kode_data_pilah'];
        
        $sqlMax = "SELECT kode_kolom FROM data_pilah_kolom WHERE kode_data_pilah = '$kode_dp' ORDER BY id_data_pilah_kolom DESC LIMIT 1";
        $lastCode = $this->dbDataGetValue($sqlMax);
        
        if (!$lastCode) {
            $nextCode = $kode_dp . ".01";
        } else {
            if (strpos($lastCode, '.') !== false) {
                $parts = explode('.', $lastCode);
                $lastPart = end($parts);
                $prefix = implode('.', array_slice($parts, 0, -1));
                
                $nextSequence = (int)$lastPart + 1;
                $nextCode = $prefix . "." . str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
            } else {
                $sequence = substr($lastCode, strlen($kode_dp));
                $nextSequence = (int)$sequence + 1;
                $nextCode = $kode_dp . "." . str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
            }
        }
        
        echo json_encode(array("success" => true, "code" => $nextCode));
    }

    // =====================================================================
    // PDF EXPORT (legacy — dipertahankan)
    // =====================================================================

    public function ACTION_listPrint($return = false)
    {
        $params = isset($_GET) ? $_GET : $_POST;
        $userId = $this->get_userId();
        $sql = 'SELECT * FROM data_pilah';
        if(isset($_POST['search']['value']) && $_POST['search']['value'] !=''){
            $keywords = strtolower($_POST['search']['value']);
            $findField = $this->findField();
            $criteria = $this->buildSqlSearchingCriteria($keywords, $findField);
            $sql .= " where ".$criteria;
        }
        if (isset($_POST['start'])) {
            $start = $_POST['start'];
            $limit = $_POST['length'];
            $sql .= " limit $start,$limit ";
        }
        echo $this->dbDataSelectAndReturnAll($sql, $params);
    }

    public function ACTION_exportPdf()
    {
        $params = isset($_GET) ? $_GET : $_POST;
        $kode = $params['kode_data_pilah'];
        $tahun = isset($params['tahun']) ? (int)$params['tahun'] : date('Y');
        $id_instansi = isset($params['id_instansi']) ? (int)$params['id_instansi'] : 0;

        // Re-use logic to get data
        $sqlM = "SELECT * FROM data_pilah WHERE kode_data_pilah = '$kode'";
        $matrixInfo = $this->dbDataSelectAndReturnAll($sqlM, null, true)[0];

        $sqlU = "SELECT nama_instansi FROM reff_unit_kerja WHERE id = $id_instansi";
        $dinas = $this->dbDataGetValue($sqlU) ?: 'Semua Dinas';

        // Get baris, kolom, cells
        $sqlBaris = "SELECT * FROM data_pilah_baris WHERE kode_data_pilah = '$kode' ORDER BY no_urut ASC";
        $baris = $this->dbDataSelectAndReturnAll($sqlBaris, null, true);

        $sqlKolom = "SELECT * FROM data_pilah_kolom WHERE kode_data_pilah = '$kode' ORDER BY id_data_pilah_kolom ASC";
        $kolom = $this->dbDataSelectAndReturnAll($sqlKolom, null, true);

        $sqlCell = "SELECT * FROM data_pilah_cell WHERE kode_data_pilah = '$kode' AND tahun = $tahun AND id_instansi = $id_instansi";
        $cells = $this->dbDataSelectAndReturnAll($sqlCell, null, true);

        $cellMap = array();
        foreach($cells as $c){ $cellMap[$c->kode_baris . '|' . $c->kode_kolom] = $c->val; }

        $matriksRows = array();
        foreach($baris as $bi => $b){
            $row = array();
            $row['no_urut'] = $b->no_urut ?: ($bi+1);
            $row['nama_baris'] = $b->nama_baris;
            $row['cells'] = array();
            foreach($kolom as $k){
                $key = $b->kode_baris . '|' . $k->kode_kolom;
                $row['cells'][] = array('val' => isset($cellMap[$key]) ? $cellMap[$key] : '');
            }
            $matriksRows[] = $row;
        }

        $data = array(
            'judul' => $matrixInfo->judul_data_pilah,
            'tahun' => $tahun,
            'dinas' => $dinas,
            'header_baris' => $matrixInfo->header_baris,
            'kolom' => json_decode(json_encode($kolom), true),
            'baris' => $matriksRows
        );

        $pdf = $this->createHtml2Pdf('DataPilah', $data, 'tpl_matrix.html');
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($matrixInfo->judul_data_pilah);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->AddPage();
        $pdf->writeHTML($pdf->content, true, 0, true, 0);
        $pdf->Output('matrix_' . $kode . '_' . $tahun . '.pdf', 'D');
    }

    public function ACTION_exportExcel()
    {
        $params = isset($_GET) ? $_GET : $_POST;
        $kode = $params['kode_data_pilah'];
        $tahun = isset($params['tahun']) ? (int)$params['tahun'] : date('Y');
        $id_instansi = isset($params['id_instansi']) ? (int)$params['id_instansi'] : 0;

        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=matrix_" . $kode . "_" . $tahun . ".xls");

        $sqlM = "SELECT * FROM data_pilah WHERE kode_data_pilah = '$kode'";
        $matrixInfo = $this->dbDataSelectAndReturnAll($sqlM, null, true)[0];

        $sqlU = "SELECT nama_instansi FROM reff_unit_kerja WHERE id = $id_instansi";
        $dinas = $this->dbDataGetValue($sqlU) ?: 'Semua Dinas';

        $sqlBaris = "SELECT * FROM data_pilah_baris WHERE kode_data_pilah = '$kode' ORDER BY no_urut ASC";
        $baris = $this->dbDataSelectAndReturnAll($sqlBaris, null, true);

        $sqlKolom = "SELECT * FROM data_pilah_kolom WHERE kode_data_pilah = '$kode' ORDER BY id_data_pilah_kolom ASC";
        $kolom = $this->dbDataSelectAndReturnAll($sqlKolom, null, true);

        $sqlCell = "SELECT * FROM data_pilah_cell WHERE kode_data_pilah = '$kode' AND tahun = $tahun AND id_instansi = $id_instansi";
        $cells = $this->dbDataSelectAndReturnAll($sqlCell, null, true);

        $cellMap = array();
        foreach($cells as $c){ $cellMap[$c->kode_baris . '|' . $c->kode_kolom] = $c->val; }

        echo "<h3>" . $matrixInfo->judul_data_pilah . "</h3>";
        echo "<p>Tahun: $tahun | Dinas: $dinas</p>";
        echo "<table border='1'>";
        echo "<thead><tr><th>No</th><th>" . $matrixInfo->header_baris . "</th>";
        foreach($kolom as $k) { echo "<th>" . ($k->header_kolom ?: $k->nama_kolom) . "</th>"; }
        echo "</tr></thead><tbody>";

        foreach($baris as $bi => $b) {
            echo "<tr><td>" . ($b->no_urut ?: ($bi+1)) . "</td><td>" . $b->nama_baris . "</td>";
            foreach($kolom as $k) {
                $key = $b->kode_baris . '|' . $k->kode_kolom;
                echo "<td>" . (isset($cellMap[$key]) ? $cellMap[$key] : '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table>";
    }

    public function ACTION_pdf()
    {
        $this->ACTION_exportPdf();
    }
}
