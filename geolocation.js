let allIngredients = [];

async function loadIngredients() {
    try {
        const response = await fetch('get_ingredients.php');
        allIngredients = await response.json();
        setupIngredientSearch();
    } catch (error) {
        console.error("Error loading ingredients:", error);
    }
}

function setupIngredientSearch() {
    const searchInput = document.getElementById('ingredient-search');
    const suggestionsDropdown = document.getElementById('ingredient-suggestions');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        suggestionsDropdown.innerHTML = '';
        
        if (searchTerm.length < 2) {
            suggestionsDropdown.style.display = 'none';
            return;
        }
        
        const filtered = allIngredients.filter(ingredient => 
            ingredient.toLowerCase().includes(searchTerm)
        ).slice(0, 10); // Limit to 10 suggestions
        
        if (filtered.length > 0) {
            filtered.forEach(ingredient => {
                const suggestion = document.createElement('div');
                suggestion.className = 'suggestion-item';
                suggestion.textContent = ingredient;
                suggestion.addEventListener('click', function() {
                    addIngredient(ingredient);
                    searchInput.value = '';
                    suggestionsDropdown.style.display = 'none';
                });
                suggestionsDropdown.appendChild(suggestion);
            });
            suggestionsDropdown.style.display = 'block';
        } else {
            suggestionsDropdown.style.display = 'none';
        }
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsDropdown.contains(e.target)) {
            suggestionsDropdown.style.display = 'none';
        }
    });
}
function getLocation() {
    return new Promise((resolve, reject) => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                position => resolve(position.coords),
                error => reject(error)
            )
            } else {
            reject(new Error("Geolocation is not supported by this browser."));
        }
    });
}

async function loadNearbyStores() {
    try {
        const coords = await getLocation();
        const response = await fetch(`get_stores.php?lat=${coords.latitude}&lng=${coords.longitude}`);
        const stores = await response.json();
        
        const storesContainer = document.getElementById('nearby-stores');
        storesContainer.innerHTML = '<h3>Nearby Stores</h3>';
        
        stores.forEach(store => {
            const storeElement = document.createElement('div');
            storeElement.className = 'store';
            storeElement.innerHTML = `
                <h4>${store.name}</h4>
                <p>${store.vicinity}</p>
                <p>${store.distance.toFixed(2)} km away</p>
            `;
            storesContainer.appendChild(storeElement);
        });
    } catch (error) {
        console.error("Error loading stores:", error);
    }
}

async function checkIngredientAvailability(ingredient) {
    try {
        const coords = await getLocation();
        const response = await fetch(`check_ingredient.php?ingredient=${encodeURIComponent(ingredient)}&lat=${coords.latitude}&lng=${coords.longitude}`);
        const availability = await response.json();
        
        showIngredientAvailability(ingredient, availability);
    } catch (error) {
        console.error("Error checking ingredient:", error);
    }
}

function initAutocomplete() {
    const input = document.getElementById('ingredient-search');
    const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['establishment'],
        componentRestrictions: {country: ['us']},
        fields: ['name', 'geometry']
    });
    
    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        
        // Add ingredient to the list
        addIngredient(place.name);
    });
}

function addIngredient(ingredient) {
    const ingredientsList = document.getElementById('selected-ingredients');
    
    // Check if ingredient is already added
    const existingIngredients = Array.from(ingredientsList.querySelectorAll('.ingredient-tag span'))
        .map(span => span.textContent);
    
    if (existingIngredients.includes(ingredient)) {
        return; // Don't add duplicate ingredients
    }
    
    const ingredientElement = document.createElement('div');
    ingredientElement.className = 'ingredient-tag';
    ingredientElement.innerHTML = `
        <span>${ingredient}</span>
        <button class="remove-ingredient" data-ingredient="${ingredient}">&times;</button>
    `;
    ingredientsList.appendChild(ingredientElement);
    
    // Add event listener for ingredient click (to show availability)
    ingredientElement.addEventListener('click', async function(e) {
        if (e.target.classList.contains('remove-ingredient')) return;
        await checkIngredientAvailability(ingredient);
    });
    
    // Add remove functionality
    ingredientElement.querySelector('.remove-ingredient').addEventListener('click', function(e) {
        e.stopPropagation();
        ingredientsList.removeChild(ingredientElement);
    });
}

function showIngredientAvailability(ingredient, data) {
    const modal = document.getElementById('ingredient-modal');
    const modalContent = document.getElementById('ingredient-modal-content');
    
    modalContent.innerHTML = `
        <h3>${ingredient} Availability</h3>
        <div class="stores-list">
            ${data.stores.map(store => `
                <div class="store">
                    <h4>${store.name}</h4>
                    <p>${store.address}</p>
                    <p>Distance: ${store.distance.toFixed(2)} km</p>
                </div>
            `).join('')}
        </div>
        <div class="products-list">
            <h4>Available Products:</h4>
            ${data.products.map(product => `
                <div class="product">
                    <h5>${product.title}</h5>
                    <img src="${product.image}" alt="${product.title}">
                </div>
            `).join('')}
        </div>
    `;
    
    modal.style.display = 'block';
}

// Close modal
document.querySelector('.close-modal').addEventListener('click', function() {
    document.getElementById('ingredient-modal').style.display = 'none';
});

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    loadIngredients();
    loadNearbyStores();
});