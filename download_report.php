<?php
require_once 'db.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$db = new Database();
$conn = $db->connect();

// Get all document requests
$stmt = $conn->prepare("SELECT * FROM document_requests ORDER BY date_requested DESC");
$stmt->execute();
$result = $stmt->get_result();

// Create Excel file
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'Request No.');
$sheet->setCellValue('B1', 'Student Number');
$sheet->setCellValue('C1', 'Student Name');
$sheet->setCellValue('D1', 'Course');
$sheet->setCellValue('E1', 'Contact #');
$sheet->setCellValue('F1', 'Email');
$sheet->setCellValue('G1', 'Date Requested');
$sheet->setCellValue('H1', 'Scheduled Pick-Up');
$sheet->setCellValue('I1', 'Rescheduled Pick-Up');
$sheet->setCellValue('J1', 'Status');

// Add data
$row = 2;
while ($request = $result->fetch_assoc()) {
    $sheet->setCellValue('A'.$row, $request['request_number']);
    $sheet->setCellValue('B'.$row, $request['student_number']);
    $sheet->setCellValue('C'.$row, $request['student_name']);
    $sheet->setCellValue('D'.$row, $request['course']);
    $sheet->setCellValue('E'.$row, $request['contact_number']);
    $sheet->setCellValue('F'.$row, $request['email']);
    $sheet->setCellValue('G'.$row, $request['date_requested']);
    $sheet->setCellValue('H'.$row, $request['scheduled_pickup']);
    $sheet->setCellValue('I'.$row, $request['rescheduled_pickup']);
    $sheet->setCellValue('J'.$row, $request['status']);
    $row++;
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="document_requests_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$stmt->close();
$conn->close();
exit;
?>