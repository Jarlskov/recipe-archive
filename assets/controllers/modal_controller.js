import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog'];

    connect() {
        this.dialogTarget.close();
    }

    open(event) {
        if (event) {
            event.preventDefault();
            const url = event.currentTarget.href;
            if (url) {
                const frame = this.dialogTarget.querySelector('turbo-frame');
                frame.src = url;
                
                // Wait for the frame to load before showing the modal
                // to avoid showing an empty dialog
                frame.addEventListener('turbo:frame-load', () => {
                    this.dialogTarget.showModal();
                }, { once: true });
            } else {
                this.dialogTarget.showModal();
            }
        }
    }

    close(event) {
        if (event) event.preventDefault();
        this.dialogTarget.close();
    }

    clickOutside(event) {
        if (event.target === this.dialogTarget) {
            this.dialogTarget.close();
        }
    }

    submitEnd(event) {
        if (event.detail.success) {
            this.close();
            // Refresh the dashboard to show new content
            import('@hotwired/turbo').then(Turbo => {
                Turbo.visit(window.location.pathname, { action: 'replace' });
            });
        }
    }
}