<!-- Location Invoice Layout Selection Component -->
<!-- Add this to your location create/edit form -->

<div class="form-group">
    <label for="invoice_layout_pos">Receipt Layout for POS <span class="text-danger">*</span></label>
    <select class="form-control" id="invoice_layout_pos" name="invoice_layout_pos" required>
        <option value="">Select Receipt Layout</option>
        @foreach(\App\Models\Location::getLayoutOptions() as $key => $label)
            <option value="{{ $key }}" 
                {{ (old('invoice_layout_pos') ?? (isset($location) ? $location->invoice_layout_pos : '')) == $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <small class="form-text text-muted">
        <strong>80mm Thermal:</strong> Standard thermal receipt printer (small format)<br>
        <strong>A4 Size:</strong> Standard A4 printer (detailed invoice)<br>
        <strong>Dot Matrix:</strong> Old-style dot matrix printer (carbon copy compatible)
    </small>
</div>

<!-- Layout Preview (Optional) -->
<div class="form-group">
    <label>Layout Preview</label>
    <div id="layoutPreview" class="border p-3 bg-light" style="min-height: 100px;">
        <div id="preview80mm" class="preview-item" style="display: none;">
            <strong>80mm Thermal Receipt</strong>
            <ul>
                <li>Compact design for thermal printers</li>
                <li>Perfect for quick transactions</li>
                <li>Optimized for small paper width</li>
                <li>Mobile-friendly layout</li>
            </ul>
        </div>
        <div id="previewA4" class="preview-item" style="display: none;">
            <strong>A4 Size Invoice</strong>
            <ul>
                <li>Professional detailed invoice</li>
                <li>Customer and business details</li>
                <li>Comprehensive product listing</li>
                <li>Perfect for formal documentation</li>
            </ul>
        </div>
        <div id="previewDotMatrix" class="preview-item" style="display: none;">
            <strong>Dot Matrix Receipt</strong>
            <ul>
                <li>Monospace font for dot matrix printers</li>
                <li>Compatible with carbon copy paper</li>
                <li>Classic business receipt format</li>
                <li>Durable printing for records</li>
            </ul>
        </div>
        <div id="previewDefault">
            <span class="text-muted">Select a layout to see preview</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const layoutSelect = document.getElementById('invoice_layout_pos');
    const previewItems = document.querySelectorAll('.preview-item');
    const previewDefault = document.getElementById('previewDefault');
    
    layoutSelect.addEventListener('change', function() {
        // Hide all previews
        previewItems.forEach(item => item.style.display = 'none');
        previewDefault.style.display = 'block';
        
        // Show selected preview
        const selectedLayout = this.value;
        if (selectedLayout) {
            previewDefault.style.display = 'none';
            const previewElement = document.getElementById('preview' + selectedLayout.charAt(0).toUpperCase() + selectedLayout.slice(1).replace('_', '').replace('mm', 'mm'));
            if (previewElement) {
                previewElement.style.display = 'block';
            }
        }
    });
    
    // Trigger change event on page load to show current selection
    layoutSelect.dispatchEvent(new Event('change'));
});
</script>