import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['reference', 'details', 'title', 'author', 'loading', 'tags', 'ingredients'];
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

                this.loadingTarget.classList.add('hidden');
                this.showDetails();

                // Tom Select is initialised by the autocomplete Stimulus controller when the
                // details div becomes visible. Wait for the next animation frame so all
                // controllers have had a chance to connect before we populate.
                await new Promise(resolve => requestAnimationFrame(resolve));

                if (Array.isArray(data.tags) && this.hasTagsTarget) {
                    this.#populateTomSelect(this.tagsTarget, data.tags);
                }
                if (Array.isArray(data.ingredients) && this.hasIngredientsTarget) {
                    this.#populateTomSelect(this.ingredientsTarget, data.ingredients);
                }

                return;
            }
        } catch (error) {
            console.error('Error fetching metadata:', error);
        }

        this.loadingTarget.classList.add('hidden');
        this.showDetails();
    }

    /**
     * Pre-populate a Tom Select field with suggested names.
     *
     * @param {HTMLElement} element
     * @param {string[]} names
     */
    #populateTomSelect(element, names) {
        const tomSelect = element.tomselect;
        if (!tomSelect) {
            return;
        }
        for (const name of names) {
            if (!tomSelect.options[name]) {
                tomSelect.addOption({ value: name, text: name });
            }
            tomSelect.addItem(name, true);
        }
    }

    showDetails() {
        this.detailsTarget.classList.remove('hidden');
    }
}
