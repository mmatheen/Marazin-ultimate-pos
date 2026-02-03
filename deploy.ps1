# Deploy script - run this before uploading to production
# Save as: deploy.ps1

Write-Host "`n🔨 Building production assets..." -ForegroundColor Cyan
npm run build

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Build successful!`n" -ForegroundColor Green
    Write-Host "📦 Now upload these folders to production:" -ForegroundColor Yellow
    Write-Host "   • public/build/" -ForegroundColor White
    Write-Host "   • All PHP files`n" -ForegroundColor White
    Write-Host "⚠️  Do NOT upload:" -ForegroundColor Red
    Write-Host "   • node_modules/" -ForegroundColor Gray
    Write-Host "   • resources/js/ (source files)" -ForegroundColor Gray
} else {
    Write-Host "❌ Build failed! Fix errors first.`n" -ForegroundColor Red
}
