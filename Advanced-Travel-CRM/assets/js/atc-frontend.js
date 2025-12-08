document.addEventListener('DOMContentLoaded', () => {
    initializeTabs();
    initializeForms();
});

function initializeTabs() {
    const tabs = document.querySelectorAll('.atc-tab');
    const contents = document.querySelectorAll('.atc-tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            tab.classList.add('active');
            const target = tab.getAttribute('data-target');
            const targetContent = document.querySelector('#' + target);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
}

async function initializeForms() {
    // Load all service configs
    try {
        const config = typeof atcVars !== 'undefined' ? atcVars : (typeof atc_vars !== 'undefined' ? atc_vars : null);
        if (!config) {
            console.error('atcVars not available');
            return;
        }
        const response = await fetch(config.restUrl + 'services', {
            headers: {
                'X-WP-Nonce': config.restNonce
            }
        });
        const configs = await response.json();
        
        // Initialize search forms
        document.querySelectorAll('.atc-search-form').forEach(form => {
            const service = form.getAttribute('data-service');
            if (configs[service]) {
                buildFormFields(form, configs[service].search_fields, service);
            }
            addHiddenField(form, 'service', service);
            
            form.addEventListener('submit', e => {
                e.preventDefault();
                submitSearch(form);
            });
        });
        
        // Initialize booking forms
        document.querySelectorAll('.atc-booking-form').forEach(form => {
            const service = form.getAttribute('data-service');
            if (configs[service]) {
                buildFormFields(form, configs[service].booking_fields, service);
                
                // Add hidden fields
                addHiddenField(form, 'service', service);
                addHiddenField(form, 'price_total', '0');
                
                // Initialize calculator
                new AtcCalculator(form, configs[service]);
            }
            
            form.addEventListener('submit', e => {
                e.preventDefault();
                submitBooking(form);
            });
        });
        
    } catch(err) {
        console.error('Failed to load service configs:', err);
    }
}

function buildFormFields(form, fields, service) {
    const container = form.querySelector('.atc-form-fields');
    if (!container) return;
    
    container.innerHTML = '';
    
    fields.forEach(field => {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'atc-form-field';
        
        const label = document.createElement('label');
        label.textContent = field.label;
        if (field.required) label.textContent += ' *';
        fieldDiv.appendChild(label);
        
        let input;
        if (field.type === 'select') {
            input = document.createElement('select');
            field.options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.textContent = opt;
                if (field.default === opt) option.selected = true;
                input.appendChild(option);
            });
        } else {
            input = document.createElement('input');
            input.type = field.type;
            if (field.placeholder) input.placeholder = field.placeholder;
            if (field.min !== undefined) input.min = field.min;
            if (field.default !== undefined) input.value = field.default;
        }
        
        input.name = field.id;
        if (field.required) input.required = true;
        
        fieldDiv.appendChild(input);
        container.appendChild(fieldDiv);
    });
}

function addHiddenField(form, name, value) {
    let hidden = form.querySelector(`[name="${name}"]`);
    if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = name;
        form.appendChild(hidden);
    }
    hidden.value = value;
}