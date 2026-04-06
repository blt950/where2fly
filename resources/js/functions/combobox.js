// Dynamically adjust placeholder for destinations
document.querySelectorAll('u-combobox').forEach(element => {
    const input = element.querySelector('input[placeholder="Anywhere"]');

    if(input){
        element.addEventListener('comboboxafterselect', (event) => {
            setTimeout(() => {
                const element = event.target;
                let count = element.querySelectorAll('data').length;
    
                if(count == 0){
                    input.placeholder = "Anywhere";
                } else {
                    input.placeholder = "Choose";
                }
    
            });
        });

        // Check if there's already data and apply the correct placeholder on page load
        let count = element.querySelectorAll('data').length;
        if(count > 0){
            input.placeholder = "Choose";
        }
    }
})

// @TODO Cleanup this hack. The issue is that the u-combobox component doesn't update the underlying select element's options 
// to reflect the current chips, which causes issues when the form is submitted. 
// This code ensures that the select element's options are always in sync with the visible chips before the form is submitted.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if(!(form instanceof HTMLFormElement)){
        return;
    }

    Array.from(form.querySelectorAll('u-combobox')).forEach((combobox) => {
        const select = combobox.querySelector('select[name]');
        if(!select){
            return;
        }

        const chips = Array.from(combobox.querySelectorAll('data'));
        select.replaceChildren();

        chips.forEach((chip) => {
            const value = (chip.getAttribute('value') || chip.textContent || '').trim();
            const label = (chip.textContent || value).trim();

            if(value){
                select.appendChild(new Option(label, value, true, true));
            }
        });

        if(combobox.hasAttribute('data-multiple')){
            select.setAttribute('multiple', '');
        }
    });
}, true);

// Clean input field and close the box upon selection
document.querySelectorAll('u-combobox').forEach(element => {
    const input = element.querySelector('input');
    const datalist = element.querySelector('u-datalist');

    if(input){
        element.addEventListener('comboboxafterselect', (event) => {
            input.value = null;
            input.focus();
            datalist.hidden = true;
        });
    }
})