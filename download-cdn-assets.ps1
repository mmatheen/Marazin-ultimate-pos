# PowerShell script to download all CDN assets locally

$vendorPath = "public/vendor"

# Create directories
$directories = @(
    "$vendorPath/select2/dist/css",
    "$vendorPath/select2/dist/js",
    "$vendorPath/daterangepicker",
    "$vendorPath/tom-select/dist/css",
    "$vendorPath/tom-select/dist/js",
    "$vendorPath/sweetalert/css",
    "$vendorPath/sweetalert/js",
    "$vendorPath/inputmask",
    "$vendorPath/moment",
    "$vendorPath/chartjs",
    "$vendorPath/date-fns",
    "$vendorPath/chartjs-adapter-date-fns",
    "$vendorPath/datatables-buttons/css",
    "$vendorPath/datatables-buttons/js",
    "$vendorPath/jszip",
    "$vendorPath/pdfmake",
    "$vendorPath/jquery-ui/css",
    "$vendorPath/jquery-ui/js",
    "$vendorPath/jquery-ui/themes/base",
    "$vendorPath/font-awesome/css",
    "$vendorPath/font-awesome/webfonts",
    "$vendorPath/leaflet/dist/css",
    "$vendorPath/leaflet/dist/js",
    "$vendorPath/bootstrap/dist/css",
    "$vendorPath/bootstrap/dist/js"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "Created: $dir" -ForegroundColor Green
    }
}

# Download files
$downloads = @{
    "https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" = "$vendorPath/select2/dist/css/select2.min.css"
    "https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js" = "$vendorPath/select2/dist/js/select2.min.js"
    "https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" = "$vendorPath/daterangepicker/daterangepicker.css"
    "https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" = "$vendorPath/daterangepicker/daterangepicker.min.js"
    "https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css" = "$vendorPath/tom-select/dist/css/tom-select.css"
    "https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js" = "$vendorPath/tom-select/dist/js/tom-select.complete.min.js"
    "https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" = "$vendorPath/sweetalert/css/sweetalert.min.css"
    "https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js" = "$vendorPath/sweetalert/js/sweetalert.min.js"
    "https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.7/inputmask.min.js" = "$vendorPath/inputmask/inputmask.min.js"
    "https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.css" = "$vendorPath/inputmask/jquery.inputmask.min.css"
    "https://cdn.jsdelivr.net/momentjs/latest/moment.min.js" = "$vendorPath/moment/moment.min.js"
    "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.6.0/chart.min.js" = "$vendorPath/chartjs/chart.min.js"
    "https://cdnjs.cloudflare.com/ajax/libs/date-fns/2.21.3/date-fns.min.js" = "$vendorPath/date-fns/date-fns.min.js"
    "https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@1.0.0/dist/chartjs-adapter-date-fns.bundle.min.js" = "$vendorPath/chartjs-adapter-date-fns/chartjs-adapter-date-fns.bundle.min.js"
    "https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" = "$vendorPath/datatables-buttons/css/buttons.dataTables.min.css"
    "https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js" = "$vendorPath/datatables-buttons/js/dataTables.buttons.min.js"
    "https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js" = "$vendorPath/datatables-buttons/js/buttons.bootstrap5.min.js"
    "https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js" = "$vendorPath/datatables-buttons/js/buttons.html5.min.js"
    "https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js" = "$vendorPath/datatables-buttons/js/buttons.print.min.js"
    "https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js" = "$vendorPath/datatables-buttons/js/buttons.colVis.min.js"
    "https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" = "$vendorPath/jszip/jszip.min.js"
    "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js" = "$vendorPath/pdfmake/pdfmake.min.js"
    "https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js" = "$vendorPath/pdfmake/vfs_fonts.js"
    "https://code.jquery.com/ui/1.14.1/jquery-ui.js" = "$vendorPath/jquery-ui/js/jquery-ui.js"
    "https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css" = "$vendorPath/jquery-ui/themes/base/jquery-ui.css"
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" = "$vendorPath/font-awesome/css/all.min.css"
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" = "$vendorPath/font-awesome/css/all-6.5.0.min.css"
    "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" = "$vendorPath/leaflet/dist/css/leaflet.css"
    "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" = "$vendorPath/leaflet/dist/js/leaflet.js"
    "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" = "$vendorPath/bootstrap/dist/css/bootstrap.min.css"
    "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" = "$vendorPath/bootstrap/dist/js/bootstrap.bundle.min.js"
}

Write-Host "Downloading CDN assets..." -ForegroundColor Cyan
$successful = 0
$failed = 0

foreach ($url in $downloads.Keys) {
    $destination = $downloads[$url]
    try {
        Write-Host "Downloading: $url" -ForegroundColor Yellow
        Invoke-WebRequest -Uri $url -OutFile $destination -ErrorAction Stop
        Write-Host "  Saved to: $destination" -ForegroundColor Green
        $successful++
    }
    catch {
        Write-Host "  Failed: $_" -ForegroundColor Red
        $failed++
    }
}

Write-Host "Download Complete!" -ForegroundColor Green
Write-Host "Successful: $successful" -ForegroundColor Green
Write-Host "Failed: $failed" -ForegroundColor Red
