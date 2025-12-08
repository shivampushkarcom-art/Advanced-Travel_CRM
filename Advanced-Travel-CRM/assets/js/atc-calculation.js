class AtcCalculator {
    constructor(formEl, config) {
        this.formEl = formEl;
        this.config = config;
        this.bindEvents();
        this.update();
    }

    bindEvents() {
        this.formEl.addEventListener('input', e => {
            if (e.target.closest('.atc-form-field')) {
                this.update();
            }
        });
    }

    getVal(fieldId) {
        const el = this.formEl.querySelector(`[name="${fieldId}"]`);
        return el ? parseFloat(el.value || 0) : 0;
    }

    update() {
        let total = 0;
        
        if(this.config && this.config.pricing) {
            if(this.config.pricing.adult_rate) {
                // Hotels, Flights, Tours, Trains, Safari
                const adults = this.getVal('adults') || 1;
                const children = this.getVal('children') || 0;
                const adultRate = this.config.pricing.adult_rate;
                const childDiscount = this.config.pricing.child_discount || 0;
                total = (adults * adultRate) + (children * adultRate * (1 - childDiscount));
            } 
            else if(this.config.pricing.base_fare) {
                // Car rentals
                const km = this.getVal('km') || 0;
                total = this.config.pricing.base_fare + (km * (this.config.pricing.per_km_rate || 0));
            } 
            else if(this.config.pricing.conversion_fee_percent) {
                // Forex
                const amount = this.getVal('amount') || 0;
                total = amount * (1 + this.config.pricing.conversion_fee_percent / 100);
            }
        }

        const totalEl = this.formEl.querySelector('.atc-total');
        if(totalEl) {
            const currency = this.config?.pricing?.currency || 'INR';
            totalEl.textContent = `Total: ${currency} ${total.toFixed(2)}`;
        }

        const hidden = this.formEl.querySelector('[name="price_total"]');
        if(hidden) {
            hidden.value = total.toFixed(2);
        }
    }
}