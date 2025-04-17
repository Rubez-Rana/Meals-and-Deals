<?php
include 'connect.php';

$commonUKIngredients = [
    // Vegetables
    'Potatoes', 'Carrots', 'Onions', 'Tomatoes', 'Broccoli', 'Cauliflower', 
    'Peas', 'Sweetcorn', 'Mushrooms', 'Peppers', 'Cucumber', 'Lettuce',
    'Spinach', 'Green Beans', 'Cabbage', 'Leeks', 'Courgettes', 'Aubergine',
    
    // Fruits
    'Apples', 'Bananas', 'Oranges', 'Grapes', 'Strawberries', 'Blueberries',
    'Raspberries', 'Pears', 'Peaches', 'Plums', 'Lemons', 'Limes',
    
    // Dairy
    'Milk', 'Cheese', 'Butter', 'Yogurt', 'Cream', 'Eggs',
    
    // Meat & Poultry
    'Chicken Breast', 'Beef Mince', 'Pork Chops', 'Bacon', 'Sausages', 'Turkey',
    'Lamb Chops', 'Duck', 'Ham', 'Chicken Thighs', 'Steak',
    
    // Fish & Seafood
    'Salmon', 'Cod', 'Haddock', 'Prawns', 'Tuna', 'Mussels',
    'Scallops', 'Trout', 'Sardines', 'Mackerel',
    
    // Dry Goods
    'Pasta', 'Rice', 'Flour', 'Sugar', 'Salt', 'Pepper',
    'Bread', 'Cereal', 'Oats', 'Biscuits', 'Crackers',
    
    // Canned Goods
    'Baked Beans', 'Tomato Soup', 'Tinned Tomatoes', 'Tuna in Water',
    'Sweetcorn', 'Peas', 'Chickpeas', 'Kidney Beans',
    
    // Frozen
    'Frozen Peas', 'Frozen Mixed Vegetables', 'Ice Cream', 'Frozen Pizza',
    'Frozen Fish Fingers', 'Frozen Berries',
    
    // Baking
    'Baking Powder', 'Baking Soda', 'Vanilla Extract', 'Cocoa Powder',
    'Chocolate Chips', 'Icing Sugar', 'Brown Sugar',
    
    // Herbs & Spices
    'Basil', 'Oregano', 'Thyme', 'Rosemary', 'Cumin', 'Coriander',
    'Paprika', 'Chilli Powder', 'Garlic Powder', 'Ginger', 'Turmeric',
    
    // Other
    'Olive Oil', 'Vegetable Oil', 'Vinegar', 'Soy Sauce', 'Ketchup',
    'Mayonnaise', 'Mustard', 'Honey', 'Jam', 'Peanut Butter'
];

foreach ($commonUKIngredients as $ingredient) {
    $name = mysqli_real_escape_string($conn, $ingredient);
    $category = mysqli_real_escape_string($conn, getCategory($ingredient));
    
    $query = "INSERT INTO inventory (name, category) VALUES ('$name', '$category')";
    mysqli_query($conn, $query);
}

function getCategory($ingredient) {
    // This is a simplified categorization - expand as needed
    $vegetables = ['Potatoes', 'Carrots', 'Onions', 'Tomatoes', 'Broccoli', 'Cauliflower', 'Peas', 'Sweetcorn', 'Mushrooms', 'Peppers', 'Cucumber', 'Lettuce', 'Spinach', 'Green Beans', 'Cabbage', 'Leeks', 'Courgettes', 'Aubergine'];
    $fruits = ['Apples', 'Bananas', 'Oranges', 'Grapes', 'Strawberries', 'Blueberries', 'Raspberries', 'Pears', 'Peaches', 'Plums', 'Lemons', 'Limes'];
    $dairy = ['Milk', 'Cheese', 'Butter', 'Yogurt', 'Cream', 'Eggs'];
    $meat = ['Chicken Breast', 'Beef Mince', 'Pork Chops', 'Bacon', 'Sausages', 'Turkey', 'Lamb Chops', 'Duck', 'Ham', 'Chicken Thighs', 'Steak'];
    $fish = ['Salmon', 'Cod', 'Haddock', 'Prawns', 'Tuna', 'Mussels', 'Scallops', 'Trout', 'Sardines', 'Mackerel'];
    
    if (in_array($ingredient, $vegetables)) return 'Vegetables';
    if (in_array($ingredient, $fruits)) return 'Fruits';
    if (in_array($ingredient, $dairy)) return 'Dairy';
    if (in_array($ingredient, $meat)) return 'Meat';
    if (in_array($ingredient, $fish)) return 'Fish';
    return 'Other';
}

echo "Inventory populated successfully!";
?>