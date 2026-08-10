// Dynamically adjust placeholder for destinations
const placeholderConfigs = [
    { selector: 'input[placeholder="Anywhere"]', default: "Anywhere" },
    { selector: 'input[placeholder="None"]', default: "None" }
];

placeholderConfigs.forEach(config => {
    document.querySelectorAll('u-combobox').forEach(element => {
        const input = element.querySelector(config.selector);

        if(input){
            element.addEventListener('comboboxafterselect', (event) => {
                setTimeout(() => {
                    const count = event.target.querySelectorAll('data').length;
                    input.placeholder = count === 0 ? config.default : "Choose";
                });
            });

            // Check if there's already data and apply the correct placeholder on page load
            const count = element.querySelectorAll('data').length;
            if(count > 0){
                input.placeholder = "Choose";
            }
        }
    });
});

// "Anywhere" and "Domestic Only" override any other area filter server side, so they stand alone
const exclusiveDestinations = ['Anywhere', 'Domestic'];
const destination = document.getElementById('destination');
const destinationList = document.getElementById('destination-list');

if(destination && destinationList){
    const syncDestinationExclusivity = () => {
        const selected = Array.from(destination.querySelectorAll('data')).map(item => item.value);
        const exclusiveSelected = selected.some(value => exclusiveDestinations.includes(value));
        const otherSelected = selected.some(value => !exclusiveDestinations.includes(value));

        destinationList.querySelectorAll('u-option').forEach(option => {
            option.disabled = exclusiveSelected
                ? !selected.includes(option.value)
                : otherSelected && exclusiveDestinations.includes(option.value);
        });

        // A disabled option is hidden by u-datalist, so its group heading would be left dangling
        destinationList.querySelectorAll('.divider').forEach(divider => {
            divider.hidden = exclusiveSelected;
        });
    };

    syncDestinationExclusivity();
    destination.addEventListener('comboboxafterselect', syncDestinationExclusivity);
}

document.querySelectorAll('u-combobox').forEach(element => {
    const input = element.querySelector('input');
    if (!input) return;

    // Open the combobox when clicking on the element itself (not just the input)
    element.addEventListener('click', (event) => {
        if (event.target === element) input.click();
    });

    // Clear input and close the combobox when an option is selected
    element.addEventListener('comboboxafterselect', (event) => {
        input.value = null;
        input.focus();
        setTimeout(() => {
            input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        });
    });
});