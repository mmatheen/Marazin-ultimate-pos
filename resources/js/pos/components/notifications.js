/**
 * UI Components - Notifications
 * Handles toastr notifications
 */

export class NotificationManager {
    constructor() {
        this.configureToastr();
    }

    configureToastr() {
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: 3000,
                extendedTimeOut: 1000
            };
        }
    }

    success(message, title = 'Success') {
        toastr.success(message, title);
    }

    error(message, title = 'Error') {
        toastr.error(message, title);
    }

    warning(message, title = 'Warning') {
        toastr.warning(message, title);
    }

    info(message, title = 'Info') {
        toastr.info(message, title);
    }

    clear() {
        toastr.clear();
    }
}

export const notificationManager = new NotificationManager();
export default notificationManager;
