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

// --- Ambil Data Pengaturan (Nama Sekolah, Logo, TTD Kepala Sekolah, Stempel) ---
$school_name = 'NURUL ISLAM'; // Default name, as seen in PDF header
$school_address = 'Jl. Pangklengan. Rt 15/03 Geneng Batealit Jepara Pos 59461'; // From PDF
$school_phone = '0822 2129 3036'; // From PDF
$school_email = 'smknurulislamgeneng@gmail.com'; // From PDF
$school_npsn = '69916826'; // From PDF
$school_logo_path = ''; // Path ke logo sekolah
$principal_name = 'Ahmad Syarif Hidayat, S.Pd.I'; // From PDF
$principal_nip = '-'; // From PDF
$principal_signature_path = ''; // Path ke tanda tangan kepala sekolah
$school_stamp_path = ''; // Path ke stempel sekolah
$skl_number = '400.3.14.5/045.2/363/SMK.NI/V/2025'; // From PDF

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
            case 'school_stamp_path': // Ambil path stempel
                $school_stamp_path = $row['setting_value'];
                break;
            case 'skl_number':
                $skl_number = $row['setting_value'];
                break;
        }
    }
}

// --- Ambil Data Siswa (termasuk photo_path) ---
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
// Urutkan berdasarkan kategori dan nama mapel
$grades_data = [];
$sql_grades = "SELECT s.subject_name, s.category, g.grade_value
               FROM grades g
               JOIN subjects s ON g.subject_id = s.id
               WHERE g.student_id = ?
               ORDER BY FIELD(s.category, 'Umum', 'Kejuruan', 'Muatan Lokal', 'Pilihan'), s.subject_name"; // Urutan kategori sesuai PDF

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
        // Path logo harus relatif terhadap file ini (generate_skl.php)
        $logo_full_path = $school_logo_path;
        if (!empty($logo_full_path) && file_exists($logo_full_path)) {
            // Posisi logo: 15mm dari kiri (sesuai contoh), 10mm dari atas, lebar 25mm (sesuai contoh)
            $this->Image($logo_full_path, 15, 10, 25, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        // Set font dan informasi header
        $this->SetFont('helvetica', 'B', 14);
        // Menyesuaikan posisi X untuk teks header agar lebih dekat dengan logo
        // Posisi X awal teks header (setelah logo)
        $text_start_x = 15 + 25 + 5; // Posisi X logo + lebar logo + spasi (5mm)
        $this->SetX($text_start_x);
        $this->SetY(10); // Sesuaikan posisi Y untuk teks header
        $this->Cell(0, 5, '                 YAYASAN ZHILALUL QUR\'AN ASSALAM', 0, 1, 'C'); // Cell ini akan di-center relatif terhadap sisa lebar halaman
        
        // Pindahkan kursor ke posisi X yang sama untuk baris berikutnya
        $this->SetX($text_start_x);
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 5, '' . strtoupper($school_name), 0, 1, 'C'); // Nama SMK diulang "SMK SMK"
        
        // Baris NPSN (bold)
        $this->SetX($text_start_x);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(0, 5, 'NPSN NO. ' . $school_npsn, 0, 1, 'C');

        // Baris Alamat & Kontak (normal)
        $this->SetFont('helvetica', '', 9); // Kembali ke font normal
        $this->SetX($text_start_x);
        $this->Cell(0, 5, 'Alamat: ' . $school_address, 0, 1, 'C');

        $this->SetX($text_start_x);
        $this->Cell(0, 5, 'Phone: ' . $school_phone . ' Email: ' . $school_email, 0, 1, 'C');
        
        // Garis pembatas header: garis ganda (atas tebal, bawah tipis)
        $this->Ln(2);

        // Garis pertama (atas) - tebal
        $this->SetLineWidth(1.2);
        $this->SetDrawColor(0, 0, 0); // warna hitam
        $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());

        // Geser ke bawah sedikit
        $this->Ln(1.2);

        // Garis kedua (bawah) - tipis
        $this->SetLineWidth(0.3);
        $this->SetDrawColor(100, 100, 100); // abu-abu gelap agar tampak seperti shadow
        $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());

    }

    // Page footer
    public function Footer() {
        // Kosongkan footer agar tidak ada "Page 1/1"
        // $this->SetY(-15);
        // $this->SetFont('helvetica', 'I', 8);
        // $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// create new PDF document with F4 (Folio) paper size
// F4 dimensions: 215mm x 330mm
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, array(215, 330), true, 'UTF-8', false);

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
$left_margin_value = 15;
$top_margin_value = 40;
$right_margin_value = 15;
$pdf->SetMargins($left_margin_value, $top_margin_value, $right_margin_value);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 20); // Margin bawah 20mm

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('times', '', 11); // Font utama dokumen (11pt)

// add a page
$pdf->AddPage();

// Posisi judul "SURAT KETERANGAN LULUS" setelah header kustom
$pdf->SetY($top_margin_value + 3);
$pdf->SetFont('times', 'B', 13);
$title_text = 'SURAT KETERANGAN LULUS';
$pdf->Cell(0, 5, $title_text, 0, 1, 'C');

// Hitung posisi dan lebar garis secara dinamis
$line_width = $pdf->GetStringWidth($title_text);
$page_width = $pdf->getPageWidth();
$line_start_x = ($page_width - $line_width) / 2;

$pdf->Line($line_start_x, $pdf->GetY(), $line_start_x + $line_width, $pdf->GetY());
$pdf->SetFont('times', '', 11); // Font normal kembali (11pt)
$pdf->Cell(0, 4, 'Nomor: ' . htmlspecialchars($skl_number), 0, 1, 'C');
$pdf->Ln(3);

// Content
$html = '
<p style="margin-bottom: 0; font-size: 11pt;">Yang bertanda tangan di bawah ini, Kepala Sekolah Menengah Kejuruan ' . htmlspecialchars($school_name) . ' Kabupaten Jepara, Provinsi Jawa Tengah menerangkan bahwa :</p>
<table cellspacing="0" cellpadding="0.5" border="0" style="font-size: 11pt;"> <tr>
        <td width="30%">Satuan pendidikan</td>
        <td width="2%">:</td>
        <td width="68%">' . htmlspecialchars($school_name) . '</td> </tr>
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
<p style="margin-top: 5px; margin-bottom: 0; font-size: 11pt;">Dinyatakan LULUS dari satuan pendidikan berdasarkan kriteria kelulusan Sekolah Menengah Kejuruan ' . htmlspecialchars($school_name) . ' Tahun Ajaran ' . date('Y', strtotime($student_data['graduation_date'])) . '/' . (date('Y', strtotime($student_data['graduation_date'])) + 1) . ', dengan nilai sebagai berikut:</p>
';

$pdf->writeHTML($html, true, false, true, false, '');

// --- Tabel Nilai Mata Pelajaran ---
// Mengatur font tabel nilai ke 10pt agar muat.
$html_grades = '<table border="1" cellpadding="1" style="width: 100%; border-collapse: collapse; font-size: 10pt;">
    <thead>
        <tr style="background-color: #f2f2f2;">
            <th style="width: 10%; text-align: center;">No</th>
            <th style="width: 60%; text-align: left;"> Mata Pelajaran</th>
            <th style="width: 30%; text-align: center;">Nilai</th>
        </tr>
    </thead>
    <tbody>';

$no_umum = 1;
$no_kejuruan = 1;
$no_mulok = 1;
$no_pilihan = 1;

// Group grades by category
$grouped_grades = [];
foreach ($grades_data as $grade) {
    $grouped_grades[$grade['category']][] = $grade;
}

// Define the order of categories
$category_order = ['Umum', 'Kejuruan', 'Muatan Lokal', 'Pilihan'];

// Initialize total_grade and grade_count before the loop
$total_grade = 0;
$grade_count = 0;

foreach ($category_order as $category) {
    if (isset($grouped_grades[$category])) {
        $html_grades .= '<tr>';
        // Add category header row
        if ($category == 'Umum') {
            $html_grades .= '<td colspan="3" style="font-weight: bold;"> A. Kelompok Mata Pelajaran Umum :</td>';
        } elseif ($category == 'Kejuruan') {
            $html_grades .= '<td colspan="3" style="font-weight: bold;"> B. Kelompok Mata Pelajaran Kejuruan :</td>';
        } else {
            $html_grades .= '<td colspan="3" style="font-weight: bold;">' . htmlspecialchars($category) . '</td>';
        }
        $html_grades .= '</tr>';

        foreach ($grouped_grades[$category] as $grade) {
            $current_no = '';
            if ($category == 'Umum') {
                if ($grade['subject_name'] == 'Muatan Lokal') {
                    $current_no = '7'; // Hardcode "7" for Muatan Lokal
                } else {
                    $current_no = $no_umum++;
                }
            } elseif ($category == 'Kejuruan') {
                $current_no = $no_kejuruan++;
            } elseif ($category == 'Muatan Lokal') { // For sub-item 'a. Bahasa Jawa'
                 $current_no = 'a.';
            } elseif ($category == 'Pilihan') {
                $current_no = $no_pilihan++;
            }

            $html_grades .= '<tr>
                                <td style="width: 10%; text-align: center;">' . $current_no . '</td>
                                <td style="width: 60%; text-align: left;">&nbsp;' . htmlspecialchars($grade['subject_name']) . '</td>
                                <td style="width: 30%; text-align: center;">' . number_format($grade['grade_value'], 2) . '</td>
                             </tr>';
            // Accumulate total_grade and grade_count inside the inner loop
            $total_grade += $grade['grade_value'];
            $grade_count++;
        }
    }
}

// Calculate average_grade after all grades have been processed
$average_grade = ($grade_count > 0) ? $total_grade / $grade_count : 0;

$html_grades .= '<tr>
                    <td colspan="2" style="text-align: center; font-weight: bold;">Rata - Rata</td>
                    <td style="text-align: center; font-weight: bold;">' . number_format($average_grade, 2) . '</td>
                 </tr>';

$html_grades .= '</tbody></table><br>';

$pdf->writeHTML($html_grades, true, false, true, false, '');

// --- Bagian Penutup dengan Foto Siswa, Tanda Tangan, dan Stempel ---
// Mengurangi spasi di sini
$html_closing_start = '
<p style="margin-bottom: 0; margin-top: 5px; font-size: 11pt;">Surat Keterangan Lulus ini berlaku sementara sampai dengan diterbitkannya Ijazah Tahun Ajaran ' . date('Y', strtotime($student_data['graduation_date'])) . '/' . (date('Y', strtotime($student_data['graduation_date'])) + 1) . ', untuk menjadikan maklum bagi yang berkepentingan.</p>
<br> '; // Mengurangi dari <br><br> menjadi <br>
$pdf->writeHTML($html_closing_start, true, false, true, false, '');

// Posisi untuk foto siswa (kiri)
$x_photo = 25;
$y_photo = $pdf->GetY() + 0; // Tetap di posisi Y saat ini

// Ukuran foto siswa (sesuai contoh PDF, sekitar 3x4 atau 4x6 cm)
$w_photo = 35;
$h_photo = 45;

// Tampilkan foto siswa jika ada
$student_photo_full_path = $student_data['photo_path'];
if (!empty($student_photo_full_path) && file_exists($student_photo_full_path)) {
    $pdf->Image($student_photo_full_path, $x_photo, $y_photo, $w_photo, $h_photo, '', '', 'T', false, 300, '', false, false, 0, true, false, false);
} else {
    // Tampilkan placeholder "Foto" jika tidak ada foto
    $pdf->Rect($x_photo, $y_photo, $w_photo, $h_photo, 'D'); // Gambar kotak border untuk foto
    $pdf->SetFont('times', 'B', 12);
    $pdf->SetTextColor(150, 150, 150); // Warna abu-abu
    $pdf->Text($x_photo + ($w_photo / 2) - 8, $y_photo + ($h_photo / 2) - 3, 'FOTO');
    $pdf->SetTextColor(0, 0, 0); // Kembali ke warna hitam
    $pdf->SetFont('times', '', 11); // Font sesuai utama (11pt)
}

// --- Bagian Tanda Tangan dan Stempel ---
$y_signature_block_start = $y_photo;
$x_signature_block = 115;

$pdf->SetY($y_signature_block_start);
$pdf->SetX($x_signature_block);

// Header tanggal dan nama kepala sekolah (sebelum gambar)
$html_signature_header = '
<table cellspacing="0" cellpadding="0" border="0" style="width: 100%; font-size: 11pt;"> <tr>
        <td style="width: 100%; text-align: center;">Jepara, ' . date('d F Y', strtotime($student_data['graduation_date'])) . '</td>
    </tr>
    <tr>
        <td style="width: 100%; text-align: center;">Kepala SMK ' . htmlspecialchars($school_name) . '</td>
    </tr>
</table>
';
// Tinggi cell diperkirakan 45mm untuk menampung tanda tangan dan stempel
$pdf->writeHTMLCell(80, 45, $x_signature_block, $pdf->GetY(), $html_signature_header, 0, 0, false, true, 'C', true);

// Dapatkan posisi Y saat ini setelah HTMLCell terakhir
$current_y_after_header_sig = $pdf->GetY();

// Tentukan area tengah untuk tanda tangan dan stempel
$middle_x_sig_area = $x_signature_block + (80 / 2);

// Posisi stempel (di atas tanda tangan, sedikit tumpang tindih)
$stamp_full_path = $school_stamp_path;
$stamp_original_width = 50; // Asumsi lebar asli stempel (bisa disesuaikan jika tahu dimensi asli)
$stamp_original_height = 50; // Asumsi tinggi asli stempel (bisa disesuaikan jika tahu dimensi asli)
$scale_factor = 0.8; // 80% dari ukuran asli

$stamp_width_scaled = $stamp_original_width * $scale_factor;
$stamp_height_scaled = $stamp_original_height * $scale_factor;


if (!empty($stamp_full_path) && file_exists($stamp_full_path)) {
    // Pusat stempel di X area signature block, lalu geser 5mm ke kiri
    $stamp_x_pos = $middle_x_sig_area - ($stamp_width_scaled / 2) - 5; // Pindah 5mm ke kiri
    $stamp_y_pos = $current_y_after_header_sig + 0; // Posisikan stempel lebih ke atas
    
    $pdf->Image($stamp_full_path, $stamp_x_pos, $stamp_y_pos, $stamp_width_scaled, $stamp_height_scaled, '', '', 'B', false, 300, '', false, false, 0, true, false, false);
}

// Posisi tanda tangan (di atas nama kepala sekolah)
$signature_full_path = $principal_signature_path;
$signature_width = 40;
$signature_height = 25;

if (!empty($signature_full_path) && file_exists($signature_full_path)) {
    $sig_x_pos = $middle_x_sig_area - ($signature_width / 2);
    $sig_y_pos = $current_y_after_header_sig + 10; // Posisikan tanda tangan lebih ke atas
    
    $pdf->Image($signature_full_path, $sig_x_pos, $sig_y_pos, $signature_width, $signature_height, '', '', 'T', false, 300, '', false, false, 0, true, false, false);
}


// Lanjutkan dengan nama kepala sekolah dan NIP
$pdf->SetY($current_y_after_header_sig + 35); // Lompat Y ke bawah perkiraan tinggi gambar + spasi
$pdf->SetX($x_signature_block);

$html_closing_end = '
<table cellspacing="0" cellpadding="0" border="0" style="width: 100%; font-size: 11pt;"> <tr>
        <td style="width: 100%; text-align: center; text-decoration: underline; font-weight: bold;">' . htmlspecialchars($principal_name) . '</td>
    </tr>
    <tr>
        <td style="width: 100%; text-align: center;">NIP. ' . htmlspecialchars($principal_nip) . '</td>
    </tr>
</table>
';
$pdf->writeHTMLCell(80, '', $x_signature_block, $pdf->GetY(), $html_closing_end, 0, 1, false, true, 'C', true);

// ---------------------------------------------------------

// Close and output PDF document
$pdf->Output('SKL_' . $student_data['nisn'] . '.pdf', 'I');

exit;
?>