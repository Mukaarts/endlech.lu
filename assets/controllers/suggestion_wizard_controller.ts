import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['step', 'indicator', 'prevButton', 'nextButton', 'submitButton'];
    static values = {
        current: { type: Number, default: 1 },
        total: Number,
    };

    declare currentValue: number;
    declare totalValue: number;
    declare readonly stepTargets: HTMLElement[];
    declare readonly indicatorTargets: HTMLElement[];
    declare readonly prevButtonTarget: HTMLElement;
    declare readonly nextButtonTarget: HTMLElement;
    declare readonly submitButtonTarget: HTMLElement;

    connect(): void {
        this.updateView();
    }

    next(): void {
        if (this.currentValue < this.totalValue) {
            this.currentValue++;
            this.updateView();
        }
    }

    prev(): void {
        if (this.currentValue > 1) {
            this.currentValue--;
            this.updateView();
        }
    }

    goTo(event: Event): void {
        const target = event.currentTarget as HTMLElement;
        const step = parseInt(target.dataset.step || '1', 10);
        if (step >= 1 && step <= this.totalValue) {
            this.currentValue = step;
            this.updateView();
        }
    }

    private updateView(): void {
        // Steps ein-/ausblenden
        this.stepTargets.forEach((el, index) => {
            el.classList.toggle('hidden', index + 1 !== this.currentValue);
        });

        // Step-Indikatoren aktualisieren
        this.indicatorTargets.forEach((el, index) => {
            const stepNum = index + 1;
            const circle = el.querySelector('[data-circle]') as HTMLElement;
            const label = el.querySelector('[data-label]') as HTMLElement;
            const line = el.querySelector('[data-line]') as HTMLElement;

            if (circle) {
                circle.classList.remove('bg-cyan-600', 'text-white', 'bg-green-500', 'bg-gray-200', 'text-gray-500');
                if (stepNum === this.currentValue) {
                    circle.classList.add('bg-cyan-600', 'text-white');
                } else if (stepNum < this.currentValue) {
                    circle.classList.add('bg-green-500', 'text-white');
                } else {
                    circle.classList.add('bg-gray-200', 'text-gray-500');
                }
            }

            if (label) {
                label.classList.remove('text-cyan-700', 'font-semibold', 'text-green-700', 'text-gray-400');
                if (stepNum === this.currentValue) {
                    label.classList.add('text-cyan-700', 'font-semibold');
                } else if (stepNum < this.currentValue) {
                    label.classList.add('text-green-700');
                } else {
                    label.classList.add('text-gray-400');
                }
            }

            if (line) {
                line.classList.remove('bg-green-500', 'bg-gray-200');
                line.classList.add(stepNum < this.currentValue ? 'bg-green-500' : 'bg-gray-200');
            }
        });

        // Buttons
        this.prevButtonTarget.classList.toggle('hidden', this.currentValue === 1);
        this.nextButtonTarget.classList.toggle('hidden', this.currentValue === this.totalValue);
        this.submitButtonTarget.classList.toggle('hidden', this.currentValue !== this.totalValue);
    }
}
