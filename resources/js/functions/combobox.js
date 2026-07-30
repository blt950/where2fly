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