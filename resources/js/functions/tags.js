// Focus input when clicking on u-tags whitespace
document.querySelectorAll('u-datalist').forEach(datalist => {
    const parent = datalist.parentElement;
    if (parent.tagName === 'U-TAGS') {
        parent.addEventListener('click', event => {
            if (event.target === parent) {
                parent.querySelector('input').focus();
            }
        });
    }
});

// Prevent adding tags that don't exist in u-option's and add hidden input to form
document.querySelectorAll('u-tags').forEach(element => {
    element.addEventListener('tags', (event) => {
        const element = event.target;
        const value = event.detail.item.value;
        const options = Array.from(document.querySelectorAll('u-option')).map(option => option.value);

        if (!options.includes(value)) {
            event.preventDefault();
        } else {
            if (event.detail.action === 'add') {

                // Create a hidden input element with the data-input-name and value equal to 'value'
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = element.dataset.inputName;
                input.value = value;
                input.dataset.tagValue = value; // For easy identification later

                // Append the hidden input the element
                element.appendChild(input);
            } else if (event.detail.action === 'remove') {
                // Find the hidden input element with the corresponding value and remove it
                const inputToRemove = element.querySelector(`input[type="hidden"][name="${element.dataset.inputName}"][value="${value}"]`);
                if (inputToRemove) {
                    inputToRemove.remove();
                }
            }
        }
    });
})

// Dynamically adjust placeholder for destinations
document.querySelectorAll('u-tags').forEach(element => {
    const input = element.querySelector('input[placeholder="Anywhere"]');

    element.addEventListener('tags', (event) => {
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
    
})