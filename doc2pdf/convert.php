<?php
// === Enable PHP error reporting ===
error_reporting(E_ALL);
ini_set('display_errors', 0); // hide from user
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/protected/debug-error.log');

// === Logging function ===
function log_debug($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/protected/debug-error.log');
}

// === File paths ===
$sharedDir = __DIR__ . '/protected';
$uploadedFile = $sharedDir . '/input.docx';
$convertedFile = $sharedDir . '/input.pdf';

// === Main logic ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    
    try {
        $file = curl_file_create($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name']);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/convert");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $file]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
    
        if ($info['http_code'] === 200) {
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename=converted.pdf");
            echo $response;
            log_debug('Uploaded file saved to: ' . $response);
        } else {
            throw new Exception('Upload failed with error: ' . $response);
        }
    } catch (Exception $e) {
        log_debug('Error: ' . $e->getMessage());
        http_response_code(500);
        echo 'Internal server error. See php-error.log for details.';
    }
} else {
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file" accept=".doc,.docx" required>
    <button type="submit">Convert to PDF</button>
</form>
<?php
}
?>