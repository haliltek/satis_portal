<?php
/**
 * SQL Dosyası Import Öncesi Collation Düzeltme Scripti
 * 
 * Kullanım:
 * 1. Bu dosyayı tarayıcıda açın: http://localhost/b2b-gemas-project-main/fix_collation_before_import.php
 * 2. SQL dosyasını seçin ve "Düzelt ve İndir" butonuna tıklayın
 * 3. Düzeltilmiş dosyayı indirin ve phpMyAdmin'de import edin
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die('Dosya yükleme hatası: ' . $file['error']);
    }
    
    if ($file['type'] !== 'application/sql' && $file['type'] !== 'text/plain' && 
        pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
        die('Lütfen geçerli bir SQL dosyası seçin.');
    }
    
    $content = file_get_contents($file['tmp_name']);
    
    // Collation değiştir
    $originalCount = substr_count($content, 'utf8mb4_0900_ai_ci');
    $content = str_replace('utf8mb4_0900_ai_ci', 'utf8mb4_general_ci', $content);
    $replacedCount = substr_count($content, 'utf8mb4_general_ci') - substr_count($content, 'utf8mb4_0900_ai_ci');
    
    // Düzeltilmiş dosyayı indir
    $newFileName = 'fixed_' . $file['name'];
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $newFileName . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Collation Düzeltme Aracı</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            background: #fafafa;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background: #45a049;
        }
        .info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 SQL Collation Düzeltme Aracı</h1>
        
        <div class="info">
            <strong>Bu araç ne yapar?</strong><br>
            SQL dosyanızdaki <code>utf8mb4_0900_ai_ci</code> collation'larını 
            <code>utf8mb4_general_ci</code> ile değiştirir ve düzeltilmiş dosyayı indirmenizi sağlar.
        </div>
        
        <div class="warning">
            <strong>⚠️ Önemli:</strong><br>
            Bu araç sadece collation'ı değiştirir. Verileriniz ve tablo yapılarınız aynı kalır.
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="sql_file">SQL Dosyası Seçin:</label>
                <input type="file" id="sql_file" name="sql_file" accept=".sql" required>
            </div>
            
            <button type="submit">✅ Düzelt ve İndir</button>
        </form>
        
        <div class="info" style="margin-top: 30px;">
            <strong>Kullanım Adımları:</strong>
            <ol>
                <li>Yukarıdaki formdan SQL dosyanızı seçin</li>
                <li>"Düzelt ve İndir" butonuna tıklayın</li>
                <li>Düzeltilmiş dosyayı indirin</li>
                <li>phpMyAdmin'de düzeltilmiş dosyayı import edin</li>
            </ol>
        </div>
    </div>
</body>
</html>



