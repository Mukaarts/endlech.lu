import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/*
 * Stimulus-Controller für Drag & Drop Bildsortierung.
 * Sendet die neue Reihenfolge per POST an das Backend.
 */
export default class extends Controller {
    static targets = ['list'];
    static values = { url: String, token: String };

    declare readonly listTarget: HTMLElement;
    declare urlValue: string;
    declare tokenValue: string;

    connect() {
        Sortable.create(this.listTarget, {
            handle: '.drag-handle',
            ghostClass: 'opacity-30',
            animation: 150,
            onEnd: () => this.#persist(),
        });
    }

    async #persist() {
        const items = this.listTarget.querySelectorAll<HTMLElement>('[data-image-id]');
        const imageIds = Array.from(items).map((el) => Number(el.dataset.imageId));

        // Cover-Badge aktualisieren: nur beim ersten Element anzeigen
        items.forEach((el, index) => {
            const badge = el.querySelector('[data-cover-badge]');
            if (badge) {
                (badge as HTMLElement).style.display = index === 0 ? '' : 'none';
            }
        });

        await fetch(this.urlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _token: this.tokenValue, imageIds }),
        });
    }
}
