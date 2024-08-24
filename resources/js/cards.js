/*
    ***
    Card functions for Where2Fly
    ***
*/

window.cardsInitEvents = cardsInitEvents;
window.cardOpen = cardOpen;
window.cardClose = cardClose;
window.cardCloseAll = cardCloseAll;

/*
* Function to open a card
*/
function cardsInitEvents(){

    // Fetch all elements with data-card-event="open" attribute and add click event
    document.querySelectorAll('[data-card-event="open"]').forEach(function(element){
        var cardId = element.getAttribute('data-card-for')
        var type = element.getAttribute('data-card-type')
        var card = document.querySelector('[data-card-id="'+cardId+'"]')
        element.addEventListener('click', function(){
            cardOpen(card, type);
        });
    });

    // Fetch all elements with data-card-event="close" attribute and add click event
    document.querySelectorAll('[data-card-event="close"]').forEach(function(element){
        var cardId = element.getAttribute('data-card-for')
        var type = element.getAttribute('data-card-type')
        var card = document.querySelector('[data-card-id="'+cardId+'"]')
        element.addEventListener('click', function(){
            cardClose(card, type);
        });
    });

}

/*
* Function to open a card
*/
var openCards = []
function cardOpen(element, type){
    cardCloseAll(type)
    
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
function cardClose(element, type){
    element.classList.remove('show')

    // Remove from openCards array
    openCards[type] = openCards[type].filter(function(card){
        return card != element
    });
}

/*
* Function to close all open cards
*/
function cardCloseAll(type){
    if(openCards[type] === undefined){ openCards[type] = [] }

    openCards[type].forEach(function(card){
        cardClose(card, type);
    });

    openCards[type] = [];
}