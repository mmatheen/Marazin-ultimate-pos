/**
 * IMEI Module
 * Handles IMEI selection and tracking
 */

import { productsAPI } from '../api/products.js';
import { posState } from '../state/index.js';

export class IMEIManager {
    constructor() {
        this.imeiModal = null;
        this.selectedIMEIs = [];
    }

    initialize() {
        this.imeiModal = document.getElementById('imei-modal');
        this.setupEventListeners();
    }

    setupEventListeners() {
        // IMEI selection checkboxes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('imei-checkbox')) {
                this.handleIMEISelection(e.target);
            }
        });

        // Confirm IMEI button
        const confirmBtn = document.getElementById('confirm-imei-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.confirmIMEISelection());
        }
    }

    async showIMEIModal(productId, locationId, batchId = null) {
        try {
            const imeis = await productsAPI.getIMEINumbers(productId, locationId, batchId);
            this.renderIMEIList(imeis);
            $(this.imeiModal).modal('show');
        } catch (error) {
            toastr.error('Failed to load IMEI numbers', 'Error');
        }
    }

    renderIMEIList(imeis) {
        const container = document.getElementById('imei-list-container');
        if (!container) return;

        container.innerHTML = imeis.map(imei => `
            <div class="form-check">
                <input class="form-check-input imei-checkbox"
                    type="checkbox"
                    value="${imei.imei_number}"
                    id="imei-${imei.id}"
                    data-id="${imei.id}">
                <label class="form-check-label" for="imei-${imei.id}">
                    ${imei.imei_number}
                </label>
            </div>
        `).join('');
    }

    handleIMEISelection(checkbox) {
        if (checkbox.checked) {
            this.selectedIMEIs.push(checkbox.value);
        } else {
            this.selectedIMEIs = this.selectedIMEIs.filter(imei => imei !== checkbox.value);
        }
    }

    confirmIMEISelection() {
        if (this.selectedIMEIs.length === 0) {
            toastr.warning('Please select at least one IMEI', 'Warning');
            return;
        }

        // Emit event with selected IMEIs
        window.dispatchEvent(new CustomEvent('imei-selected', {
            detail: { imeis: this.selectedIMEIs }
        }));

        $(this.imeiModal).modal('hide');
        this.selectedIMEIs = [];
    }
}

export const imeiManager = new IMEIManager();
export default imeiManager;
