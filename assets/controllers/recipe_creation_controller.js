import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['reference', 'details', 'title', 'author', 'loading'];
    static values = {
        metadataUrl: String
    };

    connect() {
        this.checkReference();
    }

    checkReference() {
        const value = this.referenceTarget.value.trim();
        if (!value) {
            this.detailsTarget.classList.add('hidden');
            return;
        }

        if (this.isUrl(value)) {
            this.fetchMetadata(value);
        } else {
            this.showDetails();
        }
    }

    isUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    async fetchMetadata(url) {
        this.loadingTarget.classList.remove('hidden');
        this.detailsTarget.classList.add('hidden');

        try {
            const response = await fetch(`${this.metadataUrlValue}?url=${encodeURIComponent(url)}`);
            if (response.ok) {
                const data = await response.json();
                if (data.title && !this.titleTarget.value) {
                    this.titleTarget.value = data.title;
                }
                if (data.author && !this.authorTarget.value) {
                    this.authorTarget.value = data.author;
                }
            }
        } catch (error) {
            console.error('Error fetching metadata:', error);
        } finally {
            this.loadingTarget.classList.add('hidden');
            this.showDetails();
        }
    }

    showDetails() {
        this.detailsTarget.classList.remove('hidden');
    }
}
