# CDN to Local Assets Migration - README

## Overview
All CDN (Content Delivery Network) links have been replaced with local vendor-based assets to ensure your Laravel application works offline without internet connection.

## What Was Changed

### Files Modified
1. `resources/views/layout/layout.blade.php` - Main layout file
2. `resources/views/sell/pos.blade.php` - POS page
3. `resources/views/sell/sale.blade.php` - Sales page
4. `resources/views/sales_rep_module/vehicle_tracking/tracking.blade.php` - Tracking page
5. `resources/views/includes/dashboards/dashboard.blade.php` - Dashboard

### Assets Downloaded
All files are located in `public/vendor/` directory:

#### JavaScript Libraries
- **Select2** v4.0.13 - Advanced select boxes
  - `public/vendor/select2/dist/css/select2.min.css`
  - `public/vendor/select2/dist/js/select2.min.js`

- **DateRangePicker** - Date range picker
  - `public/vendor/daterangepicker/daterangepicker.css`
  - `public/vendor/daterangepicker/daterangepicker.min.js`

- **Tom Select** v2.4.3 - Lightweight select library
  - `public/vendor/tom-select/dist/css/tom-select.css`
  - `public/vendor/tom-select/dist/js/tom-select.complete.min.js`

- **SweetAlert** v1.1.3 - Beautiful alert dialogs
  - `public/vendor/sweetalert/css/sweetalert.min.css`
  - `public/vendor/sweetalert/js/sweetalert.min.js`

- **InputMask** v5.0.7 - Input masking library
  - `public/vendor/inputmask/inputmask.min.js`

- **Moment.js** - Date/time library
  - `public/vendor/moment/moment.min.js`

- **Chart.js** v3.6.0 - Charts and graphs
  - `public/vendor/chartjs/chart.min.js`

- **Date-fns** - Date utility library
  - `public/vendor/date-fns/date-fns.min.js`

- **ChartJS Adapter Date-fns** - Chart.js date adapter
  - `public/vendor/chartjs-adapter-date-fns/chartjs-adapter-date-fns.bundle.min.js`

- **jQuery UI** v1.14.1
  - `public/vendor/jquery-ui/js/jquery-ui.js`
  - `public/vendor/jquery-ui/themes/base/jquery-ui.css`

- **jQuery Validate** v1.19.5
  - `public/vendor/jquery-validate/jquery.validate.min.js`

#### DataTables Extensions
- **DataTables Buttons** v2.4.2
  - `public/vendor/datatables-buttons/css/buttons.dataTables.min.css`
  - `public/vendor/datatables-buttons/js/dataTables.buttons.min.js`
  - `public/vendor/datatables-buttons/js/buttons.bootstrap5.min.js`
  - `public/vendor/datatables-buttons/js/buttons.html5.min.js`
  - `public/vendor/datatables-buttons/js/buttons.print.min.js`
  - `public/vendor/datatables-buttons/js/buttons.colVis.min.js`

#### Export Libraries
- **JSZip** v3.10.1 - ZIP file creation (for Excel export)
  - `public/vendor/jszip/jszip.min.js`

- **PDFMake** v0.2.7 - PDF generation
  - `public/vendor/pdfmake/pdfmake.min.js`
  - `public/vendor/pdfmake/vfs_fonts.js`

#### UI/Icon Libraries
- **Font Awesome** v6.0.0 & v6.5.0 - Icon library
  - `public/vendor/font-awesome/css/all.min.css`
  - `public/vendor/font-awesome/css/all-6.5.0.min.css`
  - `public/vendor/font-awesome/webfonts/` (font files)

- **Leaflet** v1.9.4 - Interactive maps
  - `public/vendor/leaflet/dist/css/leaflet.css`
  - `public/vendor/leaflet/dist/js/leaflet.js`
  - `public/vendor/leaflet/dist/images/` (marker images)

- **Bootstrap** v5.3.2
  - `public/vendor/bootstrap/dist/css/bootstrap.min.css`
  - `public/vendor/bootstrap/dist/js/bootstrap.bundle.min.js`

## CDN Links Still Using External Sources

### Google Fonts
Google Fonts are still loaded from CDN because they require multiple font files and variants. To make them fully offline:

1. Download the font files from Google Fonts
2. Place them in `public/fonts/`
3. Update CSS to reference local fonts

**Current CDN Link:**
```html
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
```

**To Download Fonts:**
- Visit: https://fonts.google.com/specimen/Roboto
- Download the font family
- Use a tool like `google-webfonts-helper` or manually create @font-face rules

## How to Re-download Assets

If you need to download the assets again, run:

```powershell
.\download-cdn-assets.ps1
```

This script will:
1. Create necessary directories in `public/vendor/`
2. Download all required files from CDNs
3. Report success/failure for each download

## Verifying Installation

1. **Check if files exist:**
   ```powershell
   Test-Path "public/vendor/select2/dist/js/select2.min.js"
   ```

2. **Test offline:**
   - Disconnect from internet
   - Access your application
   - Check browser console for any 404 errors on assets

3. **Clear Laravel cache:**
   ```bash
   php artisan cache:clear
   php artisan view:clear
   php artisan config:clear
   ```

## Benefits

✅ **Works Offline** - No internet connection required
✅ **Faster Loading** - Assets served from local server
✅ **No External Dependencies** - Complete control over versions
✅ **Better Privacy** - No external requests to CDNs
✅ **Consistent Performance** - Not affected by CDN downtime

## Troubleshooting

### Assets Not Loading
1. Check file permissions: `public/vendor/` should be readable by web server
2. Clear browser cache: Ctrl+Shift+R (hard refresh)
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify asset paths in browser DevTools Network tab

### Missing Files
Run the download script again:
```powershell
.\download-cdn-assets.ps1
```

### Version Conflicts
If you need specific versions, edit `download-cdn-assets.ps1` and update the URLs before running.

## Maintenance

### Updating Libraries
1. Edit `download-cdn-assets.ps1`
2. Update the version numbers in URLs
3. Run the script to download new versions
4. Test thoroughly before deploying

### Backup
Always backup your `public/vendor/` directory before updating.

## File Size
Total size of downloaded assets: ~15-20 MB

## Notes
- Google Fonts are still loaded from CDN (can be downloaded separately if needed)
- All other CDN dependencies have been localized
- Font files for Font Awesome are included
- Leaflet marker images are included

## Support
If you encounter issues:
1. Check browser console for errors
2. Verify file paths match in blade templates
3. Ensure `public/vendor/` directory is accessible
4. Check server permissions

Last Updated: January 6, 2026
