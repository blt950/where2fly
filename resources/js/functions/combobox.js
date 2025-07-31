// Dynamically adjust placeholder for destinations
document.querySelectorAll('u-combobox').forEach(element => {
    const input = element.querySelector('input[placeholder="Anywhere"]');

    if(input){
        element.addEventListener('afterchange', (event) => {
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
    }
})

// Clean input field and close the box upon selection
document.querySelectorAll('u-combobox').forEach(element => {
    const input = element.querySelector('input');
    const datalist = element.querySelector('u-datalist');

    if(input){
        element.addEventListener('afterchange', (event) => {
            input.value = null;
            input.focus();
            datalist.hidden = true;
        });
    }
})