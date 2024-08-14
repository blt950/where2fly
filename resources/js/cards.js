window.initCardEvents = initCardEvents;
window.openCard = openCard;

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
            openCard(card, type);
        });
    });

}

/*
* Function to open a card
*/
var openCards = []
function openCard(element, type){
    // Close all cards of this type
    closeAllCards(type)
    
    // Show the new card
    element.classList.add('show')

    // Add to openCards array
    if(openCards[type] === undefined){ openCards[type] = [] }
    openCards[type].push(element)
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

    openCards = [];
}