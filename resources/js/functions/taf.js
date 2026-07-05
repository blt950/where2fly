/*
    ***
    Reveal server-rendered TAF for Where2Fly
    ***
*/

var tafButtons = document.querySelectorAll('[data-taf-button="true"]');
tafButtons.forEach(element => {
    element.addEventListener('click', function() {
        revealTAF(element)
    });
})

function revealTAF(element){

    if(window.umami){
        umami.track('Interactions', {interaction: 'Fetch TAF'});
    }

    var text = element.parentElement.querySelector('[data-taf-text="true"]');
    if (text) {
        text.classList.remove('d-none');
    }
    element.classList.add('d-none');

}
