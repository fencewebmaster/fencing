
FENCES = FENCES || {};

FENCES.cartItems = {
    
    item: {
        gateKit: {
            slug: 'gate_kit',
            qty: 1
        }
    },

    init: function() {

        FENCES.cartItems.process();

        // Sample data (object or multi-dimensional array)
        const multiArray = [];

        // Convert the array to a string using JSON.stringify
        const multiArrayString = JSON.stringify(multiArray);

        try {
            // Save the string in local storage
            localStorage.setItem('cart_items', multiArrayString);

            // Retrieve data from local storage
            const storedMultiArrayString = localStorage.getItem('cart_items');

            // Convert the string back to an array using JSON.parse
            const storedMultiArray = JSON.parse(storedMultiArrayString);

            // Output the retrieved data
            console.log('Retrieved cart_items:', storedMultiArray);
        } catch (error) {
            // Handle errors
            console.error('Error occurred while getting cart_items:', error);
        }

    },

    process: function() {   
        //Get all cart items
        // Added data-cart-key to identify the items that will appear in cart
        let getItemsByCartKey = document.querySelectorAll('[data-cart-key]');
        let storedCartItems = JSON.parse(localStorage.getItem('cart_items'));
        let newCartItems = {
            color: '',
            items: []
        };
        
        for( let i = 0; i < getItemsByCartKey.length; i++ ){

            let el = getItemsByCartKey[i];
            let cartKey = el.getAttribute('data-cart-key');
            let cartValue = el.getAttribute('data-cart-value');
            let modifiedCartKey = `${cartKey}+${cartValue}`;
            let entry = {};
            let found = false;
            let qty = 1;

            if( cartKey === "gate" ){
                modifiedCartKey = cartKey;

                //Since `gate_kit` shares the same value with `gate`
                //create the entry for `gate_kit` manually
                newCartItems.items.push(FENCES.cartItems.item.gateKit);

            }

            if( cartKey === "raked_panel" ){
                //Remove H and W chars
                modifiedCartKey = modifiedCartKey.replace("H", '').replace("W", '');
            }

            entry.slug = modifiedCartKey;
            entry.qty = qty;

            
            if( newCartItems.items.length > 0 ){
                for (let i = 0; i < newCartItems.items.length; i++) {
                    if (newCartItems.items[i].slug === modifiedCartKey) {
                        newCartItems.items[i].qty += 1;
                        found = true;
                        console.log('found', found);
                        break;
                    }
                }
            }
            
            if( !found && cartValue !== null ){
                newCartItems.items.push(entry);
            }
        }

        newCartItems = FENCES.cartItems.apply_conditions(newCartItems);

        console.log('newCartItems', newCartItems);

    },

    apply_conditions: function(newCartItems) {

        //Apply condition for panel_options+even
        newCartItems.items = FENCES.cartItems.apply_panel_options_even(newCartItems.items);

        //Apply condition for panel_options+full
        newCartItems.items = FENCES.cartItems.apply_panel_options_full(newCartItems.items);

        return newCartItems;

    },

    //Condition
    // IF panel options = "Evenly Spaced Posts" = (Number of Fence Panels) + (short panel offcut panel length)
    apply_panel_options_even: function(array) {

        //Get offcut size
        let getOffCutValue = document.querySelector('.fencing-offcut')?.getAttribute('data-cart-value');
        
        //Find the existing object
        const foundObject = array.find(obj => obj['slug'] === "panel_options+even");

        if (foundObject) {
            //then update the qty value
            foundObject['qty'] = foundObject['qty'] + parseInt(getOffCutValue);
        }

        return array;

    },

    //Condition
    // IF panel options = "Full Panels - 3000W" = (Number of full length Panels) + (Number of short length panels)
    apply_panel_options_full: function(array) {

        //Get all short panel item
        let noOfShortPanel = document.querySelectorAll('.short-panel-item').length;

        //Find the existing object
        const foundObject = array.find(obj => obj['slug'] === "panel_options+full");

        if (foundObject) {
            //then update the qty value
            foundObject['qty'] = foundObject['qty'] + parseInt(noOfShortPanel);
        }

        return array;

    }
    

}

FENCES.cartItems.init();
