import { Controller } from '@hotwired/stimulus';

/*
 * Stimulus-Controller für dynamische Symfony CollectionType-Felder.
 * Ermöglicht das Hinzufügen und Entfernen von Einträgen.
 */
export default class extends Controller {
    static targets = ['entries', 'entry'];
    static values = { prototype: String };

    declare readonly entriesTarget: HTMLElement;
    declare readonly entryTargets: HTMLElement[];
    declare prototypeValue: string;

    #index!: number;

    connect() {
        this.#index = this.entryTargets.length;
    }

    addEntry() {
        const html = this.prototypeValue.replace(/__name__/g, String(this.#index));
        this.#index++;

        const wrapper = document.createElement('div');
        wrapper.classList.add('flex', 'items-center', 'gap-2');
        wrapper.setAttribute('data-collection-form-target', 'entry');
        wrapper.innerHTML = html +
            '<button type="button" data-action="collection-form#removeEntry" ' +
            'class="text-red-500 hover:text-red-700 text-sm font-bold px-2 py-1 shrink-0 transition">' +
            '\u2715</button>';

        this.entriesTarget.appendChild(wrapper);
    }

    removeEntry(event: Event) {
        const target = event.target as HTMLElement;
        const entry = target.closest('[data-collection-form-target="entry"]');
        if (entry) {
            entry.remove();
        }
    }
}
