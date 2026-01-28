<?php
// Demo Teklif Tasarımı - Modern UI
// Bu sayfa page.tsx'teki tasarımın PHP versiyonudur

// Demo veriler
$organizationName = "GEMAS Mermer";
$offerNumber = "TKL-2026-001";
$customerName = "Örnek Müşteri A.Ş.";
$customerEmail = "musteri@example.com";
$customerPhone = "+90 555 123 4567";
$offerDate = date('d-m-Y');
$validityDays = 30;

// Döviz kurları (demo)
$exchangeRates = [
    'USD' => ['buying' => 34.5678, 'selling' => 34.6789],
    'EUR' => ['buying' => 37.1234, 'selling' => 37.2345]
];

// Demo ürünler
$products = [
    [
        'id' => 1,
        'code' => 'MRM-001',
        'name' => 'Beyaz Mermer 60x60',
        'quantity' => 100,
        'unit' => 'M²',
        'unitPrice' => 450.00,
        'taxRate' => 20
    ],
    [
        'id' => 2,
        'code' => 'MRM-002',
        'name' => 'Siyah Granit 80x80',
        'quantity' => 50,
        'unit' => 'M²',
        'unitPrice' => 850.00,
        'taxRate' => 20
    ],
    [
        'id' => 3,
        'code' => 'MRM-003',
        'name' => 'Traverten Klasik',
        'quantity' => 75,
        'unit' => 'M²',
        'unitPrice' => 320.00,
        'taxRate' => 20
    ]
];

// Ödeme yöntemleri
$paymentMethods = [
    'cash' => 'Peşin',
    'credit_card' => 'Kredi Kartı',
    'installment_7' => 'Vadeli 7',
    'installment_10' => 'Vadeli 10',
    'installment_15' => 'Vadeli 15',
    'installment_30' => 'Vadeli 30',
    'installment_45' => 'Vadeli 45',
    'installment_60' => 'Vadeli 60',
    'installment_90' => 'Vadeli 90',
    'installment_120' => 'Vadeli 120',
    'installment_150' => 'Vadeli 150'
];

// Toplam hesaplama
function calculateTotals($products, $currency = 'TRY') {
    $subtotal = 0;
    $taxAmount = 0;
    
    foreach ($products as $product) {
        $itemSubtotal = $product['unitPrice'] * $product['quantity'];
        $itemTax = $itemSubtotal * ($product['taxRate'] / 100);
        
        $subtotal += $itemSubtotal;
        $taxAmount += $itemTax;
    }
    
    $total = $subtotal + $taxAmount;
    
    return [
        'subtotal' => $subtotal,
        'taxAmount' => $taxAmount,
        'total' => $total
    ];
}

$totals = calculateTotals($products);

function getCurrencySymbol($currency) {
    switch ($currency) {
        case 'USD': return '$';
        case 'EUR': return '€';
        case 'TRY': return '₺';
        default: return $currency;
    }
}

function getCompanyInitials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Teklif Formu - <?php echo $offerNumber; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f9fafb;
            color: #1f2937;
            line-height: 1.6;
        }
        
        /* Top Brand Bar */
        .brand-bar {
            height: 8px;
            background: linear-gradient(90deg, #f6b900 0%, #ffd700 100%);
            box-shadow: 0 2px 4px rgba(246, 185, 0, 0.2);
        }
        
        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1.5rem 0;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f6b900 0%, #ffd700 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
            color: #1f2937;
            box-shadow: 0 4px 6px rgba(246, 185, 0, 0.2);
        }
        
        .header-title {
            font-size: 2.5rem;
            font-weight: 300;
            color: #9ca3af;
            letter-spacing: 2px;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .icon-btn {
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .icon-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn-whatsapp { background: #25D366; color: white; }
        .btn-print { background: #3b82f6; color: white; }
        .btn-download { background: #f59e0b; color: white; }
        
        /* Exchange Rates Widget */
        .exchange-widget {
            background: linear-gradient(135deg, #eff6ff 0%, #d1fae5 100%);
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }
        
        .exchange-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .exchange-rates {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .exchange-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .rate-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .rate-currency {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .rate-usd { color: #2563eb; }
        .rate-eur { color: #059669; }
        
        .rate-value {
            color: #374151;
            font-size: 0.9rem;
        }
        
        .rate-note {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        /* Toolbar */
        .toolbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }
        
        .toolbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        .toolbar-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .toolbar-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        
        .btn-green {
            background: #10b981;
            color: white;
        }
        
        .btn-green:hover {
            background: #059669;
        }
        
        .btn-red {
            background: #ef4444;
            color: white;
        }
        
        .btn-red:hover {
            background: #dc2626;
        }
        
        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .info-card h2 {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .info-item {
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        
        .info-item strong {
            color: #374151;
            font-weight: 600;
        }
        
        .info-item-icon {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .offer-detail-card {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        }
        
        .highlight-value {
            color: #2563eb;
            font-weight: 600;
        }
        
        /* Payment Methods */
        .payment-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .payment-section h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border: 2px solid #10b981;
            background: #f0fdf4;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .payment-option:hover {
            background: #dcfce7;
        }
        
        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #10b981;
            border-radius: 50%;
            background: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .radio-custom::after {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: white;
        }
        
        .add-payment-btn {
            padding: 0.5rem 1rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .add-payment-btn:hover {
            background: #059669;
        }
        
        /* Products Table */
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        }
        
        th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        th.text-center {
            text-align: center;
        }
        
        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s ease;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        tbody tr:nth-child(even) {
            background: #fafafa;
        }
        
        tbody tr:nth-child(even):hover {
            background: #f3f4f6;
        }
        
        td {
            padding: 1rem;
            font-size: 0.9rem;
        }
        
        td.text-center {
            text-align: center;
        }
        
        .product-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .product-code {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .price-input {
            width: 100px;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-align: center;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .price-input:focus {
            outline: none;
            border-color: #f6b900;
            box-shadow: 0 0 0 3px rgba(246, 185, 0, 0.1);
        }
        
        .price-hint {
            font-size: 0.7rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .kdv-price {
            color: #10b981;
            font-weight: 600;
        }
        
        .tax-select {
            width: 80px;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        .total-amount {
            font-weight: 600;
            color: #1f2937;
        }
        
        /* Totals Section */
        .totals-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2rem;
        }
        
        .totals-box {
            width: 400px;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            font-size: 0.9rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .total-row.tax {
            color: #ef4444;
        }
        
        .total-row.grand {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-weight: 700;
            font-size: 1.125rem;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            border: none;
        }
        
        /* Payment Details Form */
        .form-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-label .required {
            color: #ef4444;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            padding: 0.625rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #f6b900;
            box-shadow: 0 0 0 3px rgba(246, 185, 0, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        /* Submit Button */
        .submit-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .submit-btn {
            padding: 1rem 3rem;
            background: linear-gradient(135deg, #f6b900 0%, #ffd700 100%);
            color: #1f2937;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(246, 185, 0, 0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(246, 185, 0, 0.4);
        }
        
        /* Icons */
        .icon {
            width: 16px;
            height: 16px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .info-grid,
            .payment-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .totals-box {
                width: 100%;
            }
            
            table {
                font-size: 0.8rem;
            }
            
            .toolbar-content {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Top Brand Bar -->
    <div class="brand-bar"></div>
    
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <!-- Logo -->
            <div class="logo-container">
                <div class="logo">
                    <?php echo getCompanyInitials($organizationName); ?>
                </div>
            </div>
            
            <!-- Title -->
            <h1 class="header-title">TEKLİF FORMU</h1>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="icon-btn btn-whatsapp" title="WhatsApp ile Gönder">📱</button>
                <button class="icon-btn btn-print" title="Yazdır" onclick="window.print()">🖨️</button>
                <button class="icon-btn btn-download" title="PDF İndir">📥</button>
            </div>
        </div>
    </div>
    
    <!-- Exchange Rates Widget -->
    <div class="exchange-widget">
        <div class="exchange-content">
            <div class="exchange-rates">
                <span class="exchange-label">Güncel Kurlar (TCMB):</span>
                <div class="rate-item">
                    <span class="rate-currency rate-usd">$ USD</span>
                    <span class="rate-value"><?php echo number_format($exchangeRates['USD']['selling'], 4); ?> ₺</span>
                </div>
                <div class="rate-item">
                    <span class="rate-currency rate-eur">€ EUR</span>
                    <span class="rate-value"><?php echo number_format($exchangeRates['EUR']['selling'], 4); ?> ₺</span>
                </div>
            </div>
            <span class="rate-note">Satış Kuru</span>
        </div>
    </div>
    
    <!-- Toolbar -->
    <div class="toolbar">
        <div class="toolbar-content">
            <button class="toolbar-btn btn-green">
                📤 Belge Yükle
            </button>
            <button class="toolbar-btn btn-green">
                💬 Mesaj Gönder
            </button>
            <button class="toolbar-btn btn-red">
                💰 Toplu Fiyat Ver
            </button>
            <button class="toolbar-btn btn-green">
                📊 Excel ile Fiyat Ver
            </button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Customer Info & Offer Details -->
        <div class="info-grid">
            <!-- Customer Info -->
            <div class="info-card">
                <h2>Teklif Talep Edilen Bilgileri:</h2>
                <div class="info-item">
                    <strong>Firma Adı:</strong> <?php echo $organizationName; ?>
                </div>
                <div class="info-item info-item-icon">
                    <span>✉️</span>
                    <span><?php echo $customerEmail; ?></span>
                </div>
                <div class="info-item info-item-icon">
                    <span>📞</span>
                    <span><?php echo $customerPhone; ?></span>
                </div>
            </div>
            
            <!-- Offer Details -->
            <div class="info-card offer-detail-card">
                <div class="info-item">
                    <strong>Teklif No:</strong> <span class="highlight-value"><?php echo $offerNumber; ?></span>
                </div>
                <div class="info-item">
                    <strong>Teklif Tarihi:</strong> <span class="highlight-value"><?php echo $offerDate; ?></span>
                </div>
                <div class="info-item">
                    <strong>Teklif Geçerlilik:</strong> <span class="highlight-value"><?php echo $validityDays; ?> Gün</span>
                </div>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <div class="payment-card">
            <div class="payment-grid">
                <div class="payment-section">
                    <h3>Firmanın Tercihi</h3>
                    <div class="payment-option">
                        <div class="radio-custom"></div>
                        <span style="font-weight: 600;">Peşin</span>
                    </div>
                </div>
                
                <div class="payment-section">
                    <h3>Alternatif Ödeme Yöntemleri</h3>
                    <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">
                        Henüz alternatif ödeme yöntemi eklenmedi
                    </p>
                    <button class="add-payment-btn">
                        ➕ Alternatif Fiyat Ekle
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="text-center">Fiyat</th>
                        <th class="text-center">KDV'li Fiyat</th>
                        <th class="text-center">Adet</th>
                        <th class="text-center">KDV Oranı</th>
                        <th class="text-center">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): 
                        $itemSubtotal = $product['unitPrice'] * $product['quantity'];
                        $itemTax = $itemSubtotal * ($product['taxRate'] / 100);
                        $itemTotal = $itemSubtotal + $itemTax;
                        $kdvliPrice = $product['unitPrice'] * (1 + $product['taxRate'] / 100);
                    ?>
                    <tr>
                        <td>
                            <div class="product-name"><?php echo $product['name']; ?></div>
                            <div class="product-code">Kod: <?php echo $product['code']; ?></div>
                        </td>
                        <td class="text-center">
                            <input type="number" 
                                   class="price-input" 
                                   value="<?php echo number_format($product['unitPrice'], 2, '.', ''); ?>" 
                                   step="0.01"
                                   placeholder="0">
                            <div class="price-hint">KDV'siz birim fiyat</div>
                        </td>
                        <td class="text-center">
                            <span class="kdv-price">
                                <?php echo number_format($kdvliPrice, 2); ?> ₺
                            </span>
                        </td>
                        <td class="text-center"><?php echo $product['quantity']; ?></td>
                        <td class="text-center">
                            <select class="tax-select">
                                <option value="1">%1</option>
                                <option value="10">%10</option>
                                <option value="20" <?php echo $product['taxRate'] == 20 ? 'selected' : ''; ?>>%20</option>
                            </select>
                        </td>
                        <td class="text-center total-amount">
                            <?php echo number_format($itemTotal, 2); ?> ₺
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Totals -->
        <div class="totals-container">
            <div class="totals-box">
                <div class="total-row">
                    <span>Ara Toplam (KDV'siz)</span>
                    <span><?php echo number_format($totals['subtotal'], 2); ?> ₺</span>
                </div>
                <div class="total-row tax">
                    <span>Toplam KDV</span>
                    <span><?php echo number_format($totals['taxAmount'], 2); ?> ₺</span>
                </div>
                <div class="total-row grand">
                    <span>Genel Toplam (KDV'li)</span>
                    <span><?php echo number_format($totals['total'], 2); ?> ₺</span>
                </div>
            </div>
        </div>
        
        <!-- Payment Details Form -->
        <div class="form-card">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Para Birimi <span class="required">*</span></label>
                    <select class="form-select">
                        <option value="TRY" selected>TRY - Türk Lirası</option>
                        <option value="USD">USD - Dolar</option>
                        <option value="EUR">EUR - Euro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">KDV <span class="required">*</span></label>
                    <select class="form-select">
                        <option value="1">%1</option>
                        <option value="10">%10</option>
                        <option value="20" selected>%20</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ekstra Ücret</label>
                    <input type="number" class="form-input" placeholder="0" step="0.01" value="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Termin <span class="required">*</span></label>
                    <select class="form-select">
                        <option value="">Seçiniz</option>
                        <option value="7">7 Gün</option>
                        <option value="15">15 Gün</option>
                        <option value="30">30 Gün</option>
                        <option value="45">45 Gün</option>
                        <option value="60">60 Gün</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Geçerlilik Tarihi <span class="required">*</span></label>
                    <input type="date" class="form-input" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Not</label>
                    <textarea class="form-textarea" placeholder="Teklif ile ilgili notlarınız..."></textarea>
                </div>
            </div>
        </div>
        
        <!-- Submit Button -->
        <div class="submit-container">
            <button class="submit-btn">
                <span>📨</span>
                <span>TEKLİFİ GÖNDER</span>
            </button>
        </div>
    </div>
    
    <script>
        // Basit interaktif özellikler
        document.querySelectorAll('.price-input').forEach(input => {
            input.addEventListener('input', function() {
                // Fiyat değiştiğinde KDV'li fiyatı güncelle
                const row = this.closest('tr');
                const taxSelect = row.querySelector('.tax-select');
                const kdvPriceSpan = row.querySelector('.kdv-price');
                const totalSpan = row.querySelector('.total-amount');
                const quantityCell = row.querySelectorAll('td')[3];
                
                const price = parseFloat(this.value) || 0;
                const taxRate = parseFloat(taxSelect.value) || 20;
                const quantity = parseInt(quantityCell.textContent) || 0;
                
                const kdvliPrice = price * (1 + taxRate / 100);
                const itemSubtotal = price * quantity;
                const itemTax = itemSubtotal * (taxRate / 100);
                const itemTotal = itemSubtotal + itemTax;
                
                kdvPriceSpan.textContent = kdvliPrice.toFixed(2) + ' ₺';
                totalSpan.textContent = itemTotal.toFixed(2) + ' ₺';
                
                updateTotals();
            });
        });
        
        document.querySelectorAll('.tax-select').forEach(select => {
            select.addEventListener('change', function() {
                const row = this.closest('tr');
                const priceInput = row.querySelector('.price-input');
                priceInput.dispatchEvent(new Event('input'));
            });
        });
        
        function updateTotals() {
            let subtotal = 0;
            let taxAmount = 0;
            
            document.querySelectorAll('tbody tr').forEach(row => {
                const priceInput = row.querySelector('.price-input');
                const taxSelect = row.querySelector('.tax-select');
                const quantityCell = row.querySelectorAll('td')[3];
                
                const price = parseFloat(priceInput.value) || 0;
                const taxRate = parseFloat(taxSelect.value) || 20;
                const quantity = parseInt(quantityCell.textContent) || 0;
                
                const itemSubtotal = price * quantity;
                const itemTax = itemSubtotal * (taxRate / 100);
                
                subtotal += itemSubtotal;
                taxAmount += itemTax;
            });
            
            const total = subtotal + taxAmount;
            
            const totalsBox = document.querySelector('.totals-box');
            const rows = totalsBox.querySelectorAll('.total-row');
            
            rows[0].querySelector('span:last-child').textContent = subtotal.toFixed(2) + ' ₺';
            rows[1].querySelector('span:last-child').textContent = taxAmount.toFixed(2) + ' ₺';
            rows[2].querySelector('span:last-child').textContent = total.toFixed(2) + ' ₺';
        }
        
        // Yazdırma için stil
        window.addEventListener('beforeprint', function() {
            document.querySelector('.toolbar').style.display = 'none';
            document.querySelector('.action-buttons').style.display = 'none';
        });
        
        window.addEventListener('afterprint', function() {
            document.querySelector('.toolbar').style.display = 'block';
            document.querySelector('.action-buttons').style.display = 'flex';
        });
    </script>
</body>
</html>
