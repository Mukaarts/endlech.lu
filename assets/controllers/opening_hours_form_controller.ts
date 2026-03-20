import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['row'];

    declare readonly rowTargets: HTMLElement[];

    connect(): void {
        this.rowTargets.forEach((row) => this.syncRow(row));
    }

    toggle(event: Event): void {
        const checkbox = event.target as HTMLInputElement;
        const row = checkbox.closest('[data-opening-hours-form-target="row"]') as HTMLElement;
        if (row) {
            this.syncRow(row);
        }
    }

    private syncRow(row: HTMLElement): void {
        const checkbox = row.querySelector<HTMLInputElement>('input[type="checkbox"]');
        const timeInputs = row.querySelectorAll<HTMLInputElement>('input[type="time"]');

        if (checkbox && timeInputs.length > 0) {
            const isClosed = checkbox.checked;
            timeInputs.forEach((input) => {
                input.disabled = isClosed;
                if (isClosed) {
                    input.classList.add('opacity-40');
                } else {
                    input.classList.remove('opacity-40');
                }
            });
        }
    }
}
