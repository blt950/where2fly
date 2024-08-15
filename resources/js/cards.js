/*
    ***
    Card functions for Where2Fly
    ***
*/

window.initCardEvents = initCardEvents;
window.openCard = openCard;
window.closeCard = closeCard;
window.closeAllCards = closeAllCards;

/*
* Function to open a card
*/
function initCardEvents(){

    // Fetch all elements with data-card-event="open" attribute and add click event

    document.querySelectorAll('[data-card-event="open"]').forEach(function(element){
        var cardId = element.getAttribute('data-card-for')
        var type = element.getAttribute('data-card-type')
        var card = document.querySelector('[data-card-id="'+cardId+'"]')
        element.addEventListener('click', function(){
            if(type == 'airport'){
                openCard(card, type, true);
            } else {
                openCard(card, type);
            }
        });
    });

}

/*
* Function to open a card
*/
var openCards = []
function openCard(element, type){
    closeAllCards(type)
    
    // Show the new card
    element.classList.add('show')

    // Add to openCards array
    if(openCards[type] === undefined){ openCards[type] = [] }
    openCards[type].push(element)

    // Trigger an event
    document.dispatchEvent(new CustomEvent('cardOpened', {
        detail: {
            type: type,
            card: element,
            cardId: element.getAttribute('data-card-id')
        }
    }))
}

/*
* Function to close a card
*/
function closeCard(element, type){
    element.classList.remove('show')

    // Remove from openCards array
    openCards[type] = openCards[type].filter(function(card){
        return card != element
    });
}

/*
* Function to close all open cards
*/
function closeAllCards(type){
    if(openCards[type] === undefined){ openCards[type] = [] }

    openCards[type].forEach(function(card){
        closeCard(card, type);
    });

    openCards[type] = [];
}