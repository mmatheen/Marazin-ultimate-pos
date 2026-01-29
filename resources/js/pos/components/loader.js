/**
 * UI Components - Loader
 * Handles loading indicators
 */

export class LoaderManager {
    constructor() {
        this.loaderElement = null;
        this.loadingCount = 0;
    }

    initialize() {
        this.loaderElement = document.getElementById('pos-loader');
        if (!this.loaderElement) {
            this.createLoader();
        }
    }

    createLoader() {
        const loader = document.createElement('div');
        loader.id = 'pos-loader';
        loader.className = 'pos-loader';
        loader.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        `;
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        document.body.appendChild(loader);
        this.loaderElement = loader;
    }

    show() {
        this.loadingCount++;
        if (this.loaderElement) {
            this.loaderElement.style.display = 'flex';
        }
    }

    hide() {
        this.loadingCount = Math.max(0, this.loadingCount - 1);
        if (this.loadingCount === 0 && this.loaderElement) {
            this.loaderElement.style.display = 'none';
        }
    }

    forceHide() {
        this.loadingCount = 0;
        if (this.loaderElement) {
            this.loaderElement.style.display = 'none';
        }
    }
}

export const loaderManager = new LoaderManager();
export default loaderManager;
