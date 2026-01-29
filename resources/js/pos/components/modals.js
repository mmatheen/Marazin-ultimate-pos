/**
 * UI Components - Modals
 * Handles all modal dialogs
 */

export class ModalManager {
    constructor() {
        this.activeModal = null;
    }

    showCustomerModal() {
        const modal = $('#customer-modal');
        if (modal.length) {
            modal.modal('show');
            this.activeModal = 'customer';
        }
    }

    showPaymentModal() {
        const modal = $('#payment-modal');
        if (modal.length) {
            modal.modal('show');
            this.activeModal = 'payment';
        }
    }

    showIMEIModal() {
        const modal = $('#imei-modal');
        if (modal.length) {
            modal.modal('show');
            this.activeModal = 'imei';
        }
    }

    showBatchModal() {
        const modal = $('#batch-modal');
        if (modal.length) {
            modal.modal('show');
            this.activeModal = 'batch';
        }
    }

    showQuickAddModal() {
        const modal = $('#quick-add-modal');
        if (modal.length) {
            modal.modal('show');
            this.activeModal = 'quick-add';
        }
    }

    showShippingModal() {
        const modal = $('#shipping-modal');
        if (modal.length) {
            modal.modal('show');
            this.activeModal = 'shipping';
        }
    }

    hideActiveModal() {
        if (this.activeModal) {
            $(`#${this.activeModal}-modal`).modal('hide');
            this.activeModal = null;
        }
    }

    hideAllModals() {
        $('.modal').modal('hide');
        this.activeModal = null;
    }
}

export const modalManager = new ModalManager();
export default modalManager;
