<?php
// generate_skl.php

// Memasukkan file koneksi database
require_once "config/db.php";

// Memasukkan library TCPDF
// Pastikan path ini benar sesuai dengan lokasi folder TCPDF Anda
require_once('tcpdf/tcpdf.php');

// Cek apakah NISN diberikan melalui parameter GET
if (!isset($_GET['nisn']) || empty($_GET['nisn'])) {
    die("NISN siswa tidak ditemukan.");
}

$nisn = $_GET['nisn'];

// --- Ambil Data Pengaturan (Nama Sekolah, Logo, TTD Kepala Sekolah) ---
$school_name = 'SMK NURUL ISLAM';
$school_address = 'Jl. Pangklengan. Rt 15/03 Geneng Batealit Jepara Pos 59461';
$school_phone = '0822 2129 3036';
$school_email = 'smknurulislamgeneng@gmail.com';
$school_npsn = '69916826';
$school_logo_path = ''; // Path ke logo sekolah
$principal_name = 'Ahmad Syarif Hidayat, S.Pd.I';
$principal_nip = '-';
$principal_signature_path = ''; // Path ke tanda tangan kepala sekolah
$skl_number = '400.3.14.5/045.2/363/SMK.NI/V/2025'; // Default, bisa diatur di admin

$sql_settings = "SELECT setting_name, setting_value FROM settings";
$result_settings = mysqli_query($conn, $sql_settings);

if ($result_settings) {
    while ($row = mysqli_fetch_assoc($result_settings)) {
        switch ($row['setting_name']) {
            case 'school_name':
                $school_name = $row['setting_value'];
                break;
            case 'school_address':
                $school_address = $row['setting_value'];
                break;
            case 'school_phone':
                $school_phone = $row['setting_value'];
                break;
            case 'school_email':
                $school_email = $row['setting_value'];
                break;
            case 'school_npsn':
                $school_npsn = $row['setting_value'];
                break;
            case 'school_logo_path':
                $school_logo_path = $row['setting_value'];
                break;
            case 'principal_name':
                $principal_name = $row['setting_value'];
                break;
            case 'principal_nip':
                $principal_nip = $row['setting_value'];
                break;
            case 'principal_signature_path':
                $principal_signature_path = $row['setting_value'];
                break;
            case 'skl_number':
                $skl_number = $row['setting_value'];
                break;
        }
    }
}

// --- Ambil Data Siswa ---
$student_data = null;
$sql_student = "SELECT * FROM students WHERE nisn = ?";
if ($stmt = mysqli_prepare($conn, $sql_student)) {
    mysqli_stmt_bind_param($stmt, "s", $param_nisn);
    $param_nisn = $nisn;
    if (mysqli_stmt_execute($stmt)) {
        $result_student = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result_student) == 1) {
            $student_data = mysqli_fetch_assoc($result_student);
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$student_data) {
    die("Data siswa dengan NISN " . htmlspecialchars($nisn) . " tidak ditemukan.");
}

// --- Ambil Nilai Mata Pelajaran Siswa ---
$grades_data = [];
$sql_grades = "SELECT s.subject_name, s.category, g.grade_value
               FROM grades g
               JOIN subjects s ON g.subject_id = s.id
               WHERE g.student_id = ?
               ORDER BY s.category, s.subject_name"; // Urutkan berdasarkan kategori dan nama mapel

if ($stmt_grades = mysqli_prepare($conn, $sql_grades)) {
    mysqli_stmt_bind_param($stmt_grades, "i", $student_data['id']);
    if (mysqli_stmt_execute($stmt_grades)) {
        $result_grades = mysqli_stmt_get_result($stmt_grades);
        while ($row_grade = mysqli_fetch_assoc($result_grades)) {
            $grades_data[] = $row_grade;
        }
    }
    mysqli_stmt_close($stmt_grades);
}

mysqli_close($conn);

// --- Membuat PDF menggunakan TCPDF ---

class MYPDF extends TCPDF {
    // Page header
    public function Header() {
        global $school_name, $school_address, $school_phone, $school_email, $school_npsn, $school_logo_path;

        // Logo
        if (!empty($school_logo_path) && file_exists($school_logo_path)) {
            $this->Image($school_logo_path, 15, 10, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
             // Placeholder if logo not found
             // $this->Image('https://placehold.co/25x25/cccccc/000000?text=LOGO', 15, 10, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }


        // Set font
        $this->SetFont('helvetica', 'B', 14);
        // Title
        $this->SetY(10);
        $this->Cell(0, 5, 'YAYASAN ZHILALUL QUR\'AN ASSALAM', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 5, 'SMK ' . strtoupper($school_name), 0, 1, 'C');
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 5, 'NPSN NO. ' . $school_npsn, 0, 1, 'C');
        $this->Cell(0, 5, 'Alamat: ' . $school_address, 0, 1, 'C');
        $this->Cell(0, 5, 'Phone: ' . $school_phone . ' Email: ' . $school_email, 0, 1, 'C');
        // Line break
        $this->Ln(2);
        $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY()); // Garis pembatas
        $this->Ln(5);
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Aplikasi Kelulusan ' . $school_name);
$pdf->SetTitle('Surat Keterangan Lulus - ' . $student_data['full_name']);
$pdf->SetSubject('SKL');
$pdf->SetKeywords('SKL, Kelulusan, SMK');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP + 30, PDF_MARGIN_RIGHT); // Sesuaikan margin atas karena header kustom
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('times', '', 11);

// add a page
$pdf->AddPage();

// Content
$html = '
<h3 style="text-align: center; font-weight: bold; text-decoration: underline;">SURAT KETERANGAN LULUS</h3>
<p style="text-align: center;">Nomor: ' . htmlspecialchars($skl_number) . '</p>
<br><br>
<p>Yang bertanda tangan di bawah ini, Kepala Sekolah Menengah Kejuruan ' . htmlspecialchars($school_name) . ' Kabupaten Jepara, Provinsi Jawa Tengah menerangkan bahwa :</p>
<br>
<table cellspacing="0" cellpadding="1" border="0">
    <tr>
        <td width="30%">Satuan pendidikan</td>
        <td width="2%">:</td>
        <td width="68%">SMK ' . htmlspecialchars($school_name) . '</td>
    </tr>
    <tr>
        <td>Nomor Pokok Sekolah Nasional</td>
        <td>:</td>
        <td>' . htmlspecialchars($school_npsn) . '</td>
    </tr>
    <tr>
        <td>Nama Lengkap</td>
        <td>:</td>
        <td><b>' . htmlspecialchars($student_data['full_name']) . '</b></td>
    </tr>
    <tr>
        <td>Tempat, Tanggal Lahir</td>
        <td>:</td>
        <td>' . htmlspecialchars($student_data['place_of_birth']) . ', ' . date('d F Y', strtotime($student_data['date_of_birth'])) . '</td>
    </tr>
    <tr>
        <td>Nomor Induk Siswa Nasional</td>
        <td>:</td>
        <td>' . htmlspecialchars($student_data['nisn']) . '</td>
    </tr>
    <tr>
        <td>Nomor Ijazah</td>
        <td>:</td>
        <td>-</td> </tr>
    <tr>
        <td>Tanggal Kelulusan</td>
        <td>:</td>
        <td>' . date('d F Y', strtotime($student_data['graduation_date'])) . '</td>
    </tr>
    <tr>
        <td>Program Keahlian</td>
        <td>:</td>
        <td>' . htmlspecialchars($student_data['program_keahlian']) . '</td>
    </tr>
    <tr>
        <td>Konsentrasi Keahlian</td>
        <td>:</td>
        <td>' . htmlspecialchars($student_data['konsentrasi_keahlian']) . '</td>
    </tr>
</table>
<br><br>
<p>Dinyatakan LULUS dari satuan pendidikan berdasarkan kriteria kelulusan Sekolah Menengah Kejuruan ' . htmlspecialchars($school_name) . ' Tahun Ajaran ' . date('Y', strtotime($student_data['graduation_date'])) . '/' . (date('Y', strtotime($student_data['graduation_date'])) + 1) . ', dengan nilai sebagai berikut:</p>
<br>
';

$pdf->writeHTML($html, true, false, true, false, '');

// --- Tabel Nilai Mata Pelajaran ---
$html_grades = '<table border="1" cellpadding="5" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #f2f2f2;">
            <th style="width: 10%; text-align: center;">No</th>
            <th style="width: 60%; text-align: left;">Mata Pelajaran</th>
            <th style="width: 30%; text-align: center;">Nilai</th>
        </tr>
    </thead>
    <tbody>';

$no = 1;
$current_category = '';
$total_grade = 0;
$grade_count = 0;

foreach ($grades_data as $grade) {
    if ($current_category != $grade['category']) {
        $current_category = $grade['category'];
        $html_grades .= '<tr>
                            <td colspan="3" style="font-weight: bold;">' . htmlspecialchars($current_category) . '</td>
                         </tr>';
        $no = 1; // Reset nomor untuk setiap kategori
    }
    $html_grades .= '<tr>
                        <td style="text-align: center;">' . $no++ . '</td>
                        <td>' . htmlspecialchars($grade['subject_name']) . '</td>
                        <td style="text-align: center;">' . number_format($grade['grade_value'], 2) . '</td>
                     </tr>';
    $total_grade += $grade['grade_value'];
    $grade_count++;
}

$average_grade = ($grade_count > 0) ? $total_grade / $grade_count : 0;

$html_grades .= '<tr>
                    <td colspan="2" style="text-align: right; font-weight: bold;">Rata - Rata</td>
                    <td style="text-align: center; font-weight: bold;">' . number_format($average_grade, 2) . '</td>
                 </tr>';

$html_grades .= '</tbody></table><br>';

$pdf->writeHTML($html_grades, true, false, true, false, '');

$html_closing = '
<p>Surat Keterangan Lulus ini berlaku sementara sampai dengan diterbitkannya Ijazah Tahun Ajaran ' . date('Y', strtotime($student_data['graduation_date'])) . '/' . (date('Y', strtotime($student_data['graduation_date'])) + 1) . ', untuk menjadikan maklum bagi yang berkepentingan.</p>
<br><br>
<table cellspacing="0" cellpadding="1" border="0" style="width: 100%;">
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%; text-align: center;">Jepara, ' . date('d F Y', strtotime($student_data['graduation_date'])) . '</td>
    </tr>
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%; text-align: center;">Kepala SMK ' . htmlspecialchars($school_name) . '</td>
    </tr>
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%; text-align: center; height: 50px;">
            ';
if (!empty($principal_signature_path) && file_exists($principal_signature_path)) {
    // Menyesuaikan ukuran tanda tangan agar tidak terlalu besar
    $html_closing .= '<img src="' . $principal_signature_path . '" width="100" height="50" alt="Tanda Tangan Kepala Sekolah">';
} else {
    // Placeholder jika tanda tangan tidak ditemukan
    // $html_closing .= '<img src="https://placehold.co/100x50/cccccc/000000?text=TTD" alt="Tanda Tangan Kepala Sekolah">';
}
$html_closing .= '
        </td>
    </tr>
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%; text-align: center; text-decoration: underline; font-weight: bold;">' . htmlspecialchars($principal_name) . '</td>
    </tr>
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%; text-align: center;">NIP. ' . htmlspecialchars($principal_nip) . '</td>
    </tr>
</table>
';

$pdf->writeHTML($html_closing, true, false, true, false, '');

// ---------------------------------------------------------

// Close and output PDF document
$pdf->Output('SKL_' . $student_data['nisn'] . '.pdf', 'I');

exit;
?>