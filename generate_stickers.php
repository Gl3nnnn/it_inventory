<?php
// Start output buffering to prevent HTML output when generating PDFs
ob_start();

// Include required libraries
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

// Use the libraries
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;


// Function to generate QR code as base64
function generateQRCode($url) {
    $qrCode = QrCode::create($url)
        ->setSize(100)
        ->setMargin(0)
        ->setBackgroundColor(new Color(255, 255, 255, 0)); // transparent background

    $writer = new PngWriter();
    $result = $writer->write($qrCode);

    // Return raw PNG string instead of base64 for direct file output
    return $result->getString();
}

function checkImageFile($filename) {
    $path = __DIR__ . '/' . $filename;
    if (!file_exists($path)) {
        error_log("Image file not found: " . $path);
        return false;
    }
    if (!is_readable($path)) {
        error_log("Image file not readable: " . $path);
        return false;
    }
    return true;
}



// Function to generate asset stickers PDF
function generateAssetStickers($conn, $assetIds = null) {
    // Create new PDF document
     $logoExists = checkImageFile('1.png');
    $cornerLogoExists = checkImageFile('4_enhanced.png');
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('IT Asset Inventory');
    $pdf->SetAuthor('IT Department');
    $pdf->SetTitle('Asset Stickers');
    $pdf->SetSubject('Asset Identification Stickers');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set margins
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(false);

    // Add a page
    $pdf->AddPage();

    // Sticker dimensions (32mm x 28mm) - increased size to fill more of the page
    $stickerWidth = 30;
    $stickerHeight = 26;

    // Spacing between stickers
    $horizontalSpacing = 2;
    $verticalSpacing = 2;

    // Calculate stickers per row and column
    $pageWidth = 297 - 20; // A4 landscape width minus margins
    $pageHeight = 210 - 20; // A4 landscape height minus margins

    // Set fixed 7x7 grid as requested
    $stickersPerRow = 8;
    $stickersPerColumn = 7;

    // Calculate actual spacing to center the grid
    $totalWidth = $stickersPerRow * $stickerWidth + ($stickersPerRow - 1) * $horizontalSpacing;
    $totalHeight = $stickersPerColumn * $stickerHeight + ($stickersPerColumn - 1) * $verticalSpacing;

    $startX = (297 - $totalWidth) / 2;
    $startY = (210 - $totalHeight) / 2;

    // Build query
    $query = "SELECT id, asset_tag, asset_name, category, location FROM assets";
    $params = [];
    $types = "";

    if ($assetIds && is_array($assetIds)) {
        $placeholders = str_repeat('?,', count($assetIds) - 1) . '?';
        $query .= " WHERE id IN ($placeholders)";
        $params = $assetIds;
        $types = str_repeat('i', count($assetIds));
    }

    $query .= " ORDER BY asset_tag ASC";

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $currentSticker = 0;
    $currentPage = 1;

    while ($asset = $result->fetch_assoc()) {
        // Calculate position
        $row = floor($currentSticker / $stickersPerRow);
        $col = $currentSticker % $stickersPerRow;

        $x = $startX + $col * ($stickerWidth + $horizontalSpacing);
        $y = $startY + $row * ($stickerHeight + $verticalSpacing);

        // Check if we need a new page
        if ($row >= $stickersPerColumn) {
            $pdf->AddPage();
            $currentPage++;
            $currentSticker = 0;
            $row = 0;
            $col = 0;
            $x = $startX;
            $y = $startY;
        }

        // Add light background
        $pdf->SetFillColor(245, 245, 245); // light gray background
        $pdf->Rect($x, $y, $stickerWidth, $stickerHeight, 'F');

        // Draw light border (cutting guide)
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.1);
        $pdf->Rect($x, $y, $stickerWidth, $stickerHeight);

        // Add logo (top center, smaller size)
        $pdf->Image('1.png', $x + ($stickerWidth - 10) / 2, $y + 1, 10, 5, 'PNG');

        // Generate QR code with asset information directly
        $qrText = "Asset Tag: " . $asset['asset_tag'] . "\n" .
                  "Asset Name: " . $asset['asset_name'] . "\n" .
                  "Category: " . $asset['category'] . "\n" .
                  "Location: " . $asset['location'];
        $qrCodeData = generateQRCode($qrText);

        // Save QR code image to temp file
        $tempPng = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        file_put_contents($tempPng, $qrCodeData);

        // Add QR code (left side, smaller)
        $pdf->Image($tempPng, $x + 1, $y + 7, 12, 12, 'PNG');

        // Remove temp file
        unlink($tempPng);

        // Add text (right side, smaller font)
        $pdf->SetFont('helvetica', '', 3.5); // 4pt font for better readability
        $pdf->SetTextColor(0, 0, 0);

        $textX = $x + 14;  // adjusted margin
        $textY = $y + 7;

        // TAG line
        $pdf->SetXY($textX, $textY);
        $pdf->Cell(0, 3, 'TAG: ' . $asset['asset_tag'], 0, 1, 'L');

        // LOC line
        $pdf->SetXY($textX, $textY + 3);
        $pdf->Cell(0, 3, 'LOC: ' . $asset['location'], 0, 1, 'L');

        // ITEM line
        $pdf->SetXY($textX, $textY + 6);
        $pdf->Cell(0, 3, 'ITEM: ' . $asset['category'], 0, 1, 'L');

        // Draw background image (bottom right corner, fully visible)
   // Draw background image (bottom right corner, fully visible) if file exists
        if ($cornerLogoExists) {
            try {
                $pdf->SetAlpha(1.0); // fully visible
                $pdf->Image(
                    '4_enhanced.png',
                    $x + $stickerWidth - 8,   // bottom right with 2mm margin
                    $y + $stickerHeight - 8,
                    8,                        // width
                    8,                        // height
                    'PNG',
                    '',                       // link
                    '',                       // align
                    false,                    // resize
                    10000,                 // dpi
                    '',                       // palign
                    false,                    // ismask
                    false,                    // imgmask
                    0,                        // border
                    false,                    // fitbox
                    false,                    // hidden
                    true                      // fitonpage
                );
                $pdf->SetAlpha(1); // reset transparency
            } catch (Exception $e) {
                error_log("Error adding corner logo: " . $e->getMessage());
                
                // Fallback: Draw a placeholder if image can't be loaded
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Rect($x + $stickerWidth - 8, $y + $stickerHeight - 8, 8, 8, 'F');
                $pdf->SetFont('helvetica', 'B', 4);
                $pdf->SetXY($x + $stickerWidth - 8, $y + $stickerHeight - 8);
                $pdf->Cell(8, 8, 'LOGO', 0, 0, 'C');
            }
        }

        $currentSticker++;
    }

    $stmt->close();

    // Output PDF
    $pdf->Output('asset_stickers.pdf', 'D');
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if specific asset IDs are provided
    $assetIds = null;
    if (isset($_GET['ids']) && !empty($_GET['ids'])) {
        if (is_array($_GET['ids'])) {
            $assetIds = array_map('intval', $_GET['ids']);
        } else {
            $assetIds = array_map('intval', explode(',', $_GET['ids']));
        }
    }

    generateAssetStickers($conn, $assetIds);
    exit;
}

// Handle POST request for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assetIds = null;
    if (isset($_POST['asset_ids']) && is_array($_POST['asset_ids'])) {
        $assetIds = array_map('intval', $_POST['asset_ids']);
    }

    generateAssetStickers($conn, $assetIds);
    exit;
}

// Only show HTML if not generating PDF
$showHtml = true;

if ($showHtml):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Asset Stickers - IT Asset Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php require __DIR__ . '/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <!-- Main content -->
        <main class="main-content col-md-9 col-lg-10">
            <div class="page-header">
                <div class="page-icon">
                    <i class="bi bi-upc-scan"></i>
                </div>
                <div>
                    <h2 class="mb-0">Generate Asset Stickers</h2>
                    <p class="text-muted">Create PDF stickers with QR codes for asset identification</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                <i class="bi bi-gear me-2"></i>Select Assets for Sticker Generation
                            </h5>

                            <form method="post" action="">
                                <div class="mb-3">
                                    <label class="form-label">Choose Assets:</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="selection_type" id="all_assets" value="all" checked>
                                                <label class="form-check-label" for="all_assets">
                                                    Generate stickers for ALL assets
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="selection_type" id="selected_assets" value="selected">
                                                <label class="form-check-label" for="selected_assets">
                                                    Select specific assets
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="asset_selector" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Select Assets:</label>
                                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                            <?php
                                            global $conn;
                                            $assets = $conn->query("SELECT id, asset_tag, asset_name FROM assets ORDER BY asset_tag ASC");
                                            while ($asset = $assets->fetch_assoc()):
                                            ?>
                                            <div class="form-check">
                                                <input class="form-check-input asset-checkbox" type="checkbox" name="asset_ids[]" value="<?= $asset['id'] ?>" id="asset_<?= $asset['id'] ?>">
                                                <label class="form-check-label" for="asset_<?= $asset['id'] ?>">
                                                    <strong><?= htmlspecialchars($asset['asset_tag']) ?></strong> - <?= htmlspecialchars($asset['asset_name']) ?>
                                                </label>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="select_all">Select All</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect_all">Deselect All</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-file-earmark-pdf me-2"></i>Generate PDF Stickers
                                    </button>
                                    <a href="assets.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Assets
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-eye me-2"></i>Sticker Preview
                            </h5>
                            <p class="text-muted small">Each sticker measures 25mm × 20mm</p>

                            <div class="sticker-preview">
                                <div class="sticker-sample">
                                    <div class="qr-sample">
                                        <small class="text-muted">QR Code</small>
                                    </div>
                                    <div class="text-sample">
                                        <div><strong>CODE:</strong> ABC123</div>
                                        <div><strong>LOC:</strong> Office A</div>
                                        <div><strong>ITEM:</strong> Laptop</div>
                                    </div>
                                    <div style="clear: both;"></div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Sticker Details:</h6>
                                <ul class="mb-0 small">
                                    <li>Size: 25mm × 20mm (landscape)</li>
                                    <li>QR code contains asset information directly</li>
                                    <li>Font size: 5-6pt for text</li>
                                    <li>Light borders for cutting guides</li>
                                    <li>A4 landscape layout</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

<script>
// Toggle asset selector visibility
document.querySelectorAll('input[name="selection_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const selector = document.getElementById('asset_selector');
        if (this.value === 'selected') {
            selector.style.display = 'block';
        } else {
            selector.style.display = 'none';
        }
    });
});

// Select/Deselect all checkboxes
document.getElementById('select_all').addEventListener('click', function() {
    document.querySelectorAll('.asset-checkbox').forEach(cb => cb.checked = true);
});

document.getElementById('deselect_all').addEventListener('click', function() {
    document.querySelectorAll('.asset-checkbox').forEach(cb => cb.checked = false);
});
</script>
</body>
</html>
<?php endif; ?>
