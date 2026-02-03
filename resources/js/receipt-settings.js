/**
 * Receipt Settings Manager
 * Handles receipt configuration with live preview
 */
class ReceiptSettingsManager {
    constructor() {
        this.locationId = null;
        this.previewTimeout = null;
        this.currentConfig = {};
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Initialize receipt settings for a location
     */
    init(locationId) {
        this.locationId = locationId;
        this.loadSettings();
    }

    /**
     * Load current settings from server
     */
    async loadSettings() {
        try {
            const response = await fetch(`/receipt-settings/${this.locationId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                this.currentConfig = data.config;
                this.populateForm(data.config);
                this.populatePresets(data.presets);
                this.updatePreview();
            } else {
                toastr.error('Failed to load receipt settings');
            }
        } catch (error) {
            console.error('Error loading settings:', error);
            toastr.error('Error loading receipt settings');
        }
    }

    /**
     * Populate form with current settings
     */
    populateForm(config) {
        // Checkbox fields
        const checkboxFields = [
            'show_logo', 'show_customer_phone', 'show_mrp_strikethrough',
            'show_imei', 'show_discount_breakdown', 'show_payment_method',
            'show_outstanding_due', 'show_stats_section', 'show_footer_note'
        ];

        checkboxFields.forEach(field => {
            const checkbox = document.getElementById(`receipt_${field}`);
            if (checkbox) {
                checkbox.checked = config[field] || false;
            }
        });

        // Radio buttons for spacing mode
        const spacingRadio = document.querySelector(`input[name="spacing_mode"][value="${config.spacing_mode}"]`);
        if (spacingRadio) {
            spacingRadio.checked = true;
        }

        // Number inputs
        const fontSizeInput = document.getElementById('receipt_font_size_base');
        if (fontSizeInput) {
            fontSizeInput.value = config.font_size_base || 11;
        }

        const lineSpacingInput = document.getElementById('receipt_line_spacing');
        const lineSpacingValue = document.getElementById('line_spacing_value');
        if (lineSpacingInput) {
            lineSpacingInput.value = config.line_spacing || 5;
            if (lineSpacingValue) {
                lineSpacingValue.textContent = config.line_spacing || 5;
            }
        }
    }

    /**
     * Populate preset dropdown
     */
    populatePresets(presets) {
        const presetSelect = document.getElementById('receipt_preset');
        if (!presetSelect) return;

        presetSelect.innerHTML = '<option value="">-- Select Preset --</option>';

        Object.keys(presets).forEach(key => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = presets[key].name;
            presetSelect.appendChild(option);
        });
    }

    /**
     * Bind event listeners to form inputs
     */
    bindEventListeners() {
        const form = document.getElementById('receiptSettingsForm');
        if (!form) return;

        // All input changes trigger preview update
        form.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('change', () => this.updatePreview());
        });

        // Range slider live update
        const lineSpacingInput = document.getElementById('receipt_line_spacing');
        const lineSpacingValue = document.getElementById('line_spacing_value');
        if (lineSpacingInput && lineSpacingValue) {
            lineSpacingInput.addEventListener('input', (e) => {
                lineSpacingValue.textContent = e.target.value;
                this.updatePreview();
            });
        }

        // Save button
        const saveBtn = document.getElementById('saveReceiptSettings');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveSettings());
        }

        // Reset button
        const resetBtn = document.getElementById('resetReceiptSettings');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetSettings());
        }

        // Apply preset
        const presetSelect = document.getElementById('receipt_preset');
        if (presetSelect) {
            presetSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    this.applyPreset(e.target.value);
                }
            });
        }
    }

    /**
     * Update live preview (debounced)
     */
    updatePreview() {
        clearTimeout(this.previewTimeout);

        this.previewTimeout = setTimeout(() => {
            this.generatePreview();
        }, 300);
    }

    /**
     * Generate preview HTML
     */
    async generatePreview() {
        const iframe = document.getElementById('receiptPreviewFrame');
        if (!iframe) return;

        const formData = this.getFormData();
        formData.location_id = this.locationId;

        try {
            const response = await fetch('/receipt-settings/preview', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'text/html',
                },
                body: JSON.stringify(formData)
            });

            const html = await response.text();

            // Update iframe content
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(html);
            iframeDoc.close();
        } catch (error) {
            console.error('Error generating preview:', error);
        }
    }

    /**
     * Get form data as object
     */
    getFormData() {
        const formData = {
            show_logo: document.getElementById('receipt_show_logo')?.checked || false,
            show_customer_phone: document.getElementById('receipt_show_customer_phone')?.checked || false,
            show_mrp_strikethrough: document.getElementById('receipt_show_mrp_strikethrough')?.checked || false,
            show_imei: document.getElementById('receipt_show_imei')?.checked || false,
            show_discount_breakdown: document.getElementById('receipt_show_discount_breakdown')?.checked || false,
            show_payment_method: document.getElementById('receipt_show_payment_method')?.checked || false,
            show_outstanding_due: document.getElementById('receipt_show_outstanding_due')?.checked || false,
            show_stats_section: document.getElementById('receipt_show_stats_section')?.checked || false,
            show_footer_note: document.getElementById('receipt_show_footer_note')?.checked || false,
            spacing_mode: document.querySelector('input[name="spacing_mode"]:checked')?.value || 'compact',
            font_size_base: parseInt(document.getElementById('receipt_font_size_base')?.value) || 11,
            line_spacing: parseInt(document.getElementById('receipt_line_spacing')?.value) || 5,
        };

        return formData;
    }

    /**
     * Save settings to server
     */
    async saveSettings() {
        const formData = this.getFormData();
        const saveBtn = document.getElementById('saveReceiptSettings');

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        try {
            const response = await fetch(`/receipt-settings/update/${this.locationId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                toastr.success(data.message || 'Receipt settings saved successfully');
                this.currentConfig = data.config;
            } else {
                toastr.error(data.message || 'Failed to save settings');
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            toastr.error('Error saving receipt settings');
        } finally {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Settings';
            }
        }
    }

    /**
     * Reset settings to defaults
     */
    async resetSettings() {
        if (!confirm('Are you sure you want to reset to default settings?')) {
            return;
        }

        try {
            const response = await fetch(`/receipt-settings/reset/${this.locationId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                toastr.success(data.message || 'Settings reset to defaults');
                this.currentConfig = data.config;
                this.populateForm(data.config);
                this.updatePreview();
            } else {
                toastr.error(data.message || 'Failed to reset settings');
            }
        } catch (error) {
            console.error('Error resetting settings:', error);
            toastr.error('Error resetting settings');
        }
    }

    /**
     * Apply a preset configuration
     */
    async applyPreset(presetKey) {
        try {
            const response = await fetch(`/receipt-settings/apply-preset/${this.locationId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ preset: presetKey })
            });

            const data = await response.json();

            if (data.success) {
                toastr.success(data.message || 'Preset applied successfully');
                this.currentConfig = data.config;
                this.populateForm(data.config);
                this.updatePreview();

                // Reset preset dropdown
                document.getElementById('receipt_preset').value = '';
            } else {
                toastr.error(data.message || 'Failed to apply preset');
            }
        } catch (error) {
            console.error('Error applying preset:', error);
            toastr.error('Error applying preset');
        }
    }
}

// Global instance
let receiptSettingsManager = null;

/**
 * Open receipt settings modal for a location
 */
function openReceiptSettings(locationId) {
    const modal = new bootstrap.Modal(document.getElementById('receiptSettingsModal'));

    if (!receiptSettingsManager) {
        receiptSettingsManager = new ReceiptSettingsManager();
        receiptSettingsManager.bindEventListeners();
    }

    receiptSettingsManager.init(locationId);
    modal.show();
}

// Expose function globally for inline onclick handlers
window.openReceiptSettings = openReceiptSettings;
