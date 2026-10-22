<?php
function certificate_pdf_text($value) {
    $value = (string)$value;
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }
    $value = preg_replace('/[^\x20-\x7E]/', '?', $value);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
}

function send_certificate_pdf($username, $vulnerabilityName, $completedAt) {
    $username = certificate_pdf_text($username);
    $vulnerabilityName = certificate_pdf_text($vulnerabilityName);
    $completedAt = certificate_pdf_text($completedAt);

    $stream = "0.18 0.25 0.55 RG 2 w 8 8 272 128 re S\n";
    $stream .= "BT /F1 14 Tf 24 111 Td (SHOPEASE LAB CERTIFICATE) Tj ET\n";
    $stream .= "BT /F1 8 Tf 24 88 Td (Awarded to) Tj ET\n";
    $stream .= "BT /F1 12 Tf 24 70 Td ($username) Tj ET\n";
    $stream .= "BT /F1 8 Tf 24 51 Td (For discovering:) Tj ET\n";
    $stream .= "BT /F1 10 Tf 24 34 Td ($vulnerabilityName) Tj ET\n";
    $stream .= "BT /F1 7 Tf 24 18 Td ($completedAt) Tj ET\n";

    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 288 144] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach (array_slice($offsets, 1) as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n$xrefOffset\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="shopease-achievement.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}
