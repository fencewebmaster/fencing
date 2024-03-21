
FENCES = FENCES || {};

FENCES.cartItems = {
   
    item: {
        gateKit1: {
            slug: 'gate+kit',
            qty: 1
        },
        gateKit2: {
            slug: 'gate+converter',
            qty: 1
        },
        gateKit3: {
            slug: 'gate+hinges',
            qty: 1
        },        
        gateKit4: {
            slug: 'gate+latch',
            qty: 1
        },
        swivelBrackets: {
            slug: 'swivel_brackets',
            qty: 2,
            className: 'no-post-swivel-bracket'
        }
    },

    init: function(i) {

        var tabInfo    = JSON.parse(localStorage.getItem('custom_fence-'+i));
        window.tabInfo = tabInfo;

        if( tabInfo ) {
            var slug = tabInfo[0].fence;
            var fenceInfo    = JSON.parse(localStorage.getItem('custom_fence-'+i+'-'+slug));
            window.fenceInfo = fenceInfo;            
        }



        var i = i + 1;

        $(`.fc-section-${i}`).click();

        // Sample data (object or multi-dimensional array)
        const multiArray = FENCES.cartItems.process();

        // Convert the array to a string using JSON.stringify
        const multiArrayString = JSON.stringify(multiArray);

        try {
            // Save the string in local storage
            localStorage.setItem('cart_items-'+i+'-'+slug, multiArrayString);

            // Retrieve data from local storage
            const storedMultiArrayString = localStorage.getItem('cart_items-'+i+'-'+slug);

            // Convert the string back to an array using JSON.parse
            const storedMultiArray = JSON.parse(storedMultiArrayString);

            // Output the retrieved data
           // console.log('Retrieved cart_items:', storedMultiArray);

        } catch (error) {
            // Handle errors
            //console.error('Error occurred while getting cart_items:', error);
        }

    },

    process: function() {   
        //Get all cart items
        // Added data-cart-key to identify the items that will appear in cart
        let getItemsByCartKey = document.querySelectorAll('[data-cart-key]');

        //Object that holds the color and cart items
        //This is the data that will be stored in localstorage and also sent to the server when form is submitted 
        var newCartItems = [];

        //Iterate all items found with [data-cart-key] attribute
        for( let i = 0; i < getItemsByCartKey.length; i++ ){

            //Get element object
            let el = getItemsByCartKey[i];
            
            //A cart item slug consist of two parts, the key and the value
            //example: `panel_options+even` = `{key}+{value}`

            //Get cart key
            let cartKey = el.getAttribute('data-cart-key');

            //Get cart value
            let cartValue = el.getAttribute('data-cart-value');
            
            //Merge the two strings to create an cart item slug
            //example: `panel_options+even` = `{cartKey}+{cartValue}`
            let modifiedCartKey = cartValue ? `${cartKey}+${cartValue}` : cartKey;

            //Init an empty object
            //This will contain the cart item data
            let entry = {};

            //This variable is used to add a check before adding new object to the `newCartItems` array
            //If an object is found in the array, update it. if not, push the new object to array
            let found = false;
            let qty = 1;


            //additional condition for some cart items
            if( cartKey === "gate" ){
             //   modifiedCartKey = cartKey;

                //Since `gate_kit` shares the same value with `gate`
                //create the entry for `gate_kit` manually
                newCartItems.push(FENCES.cartItems.item.gateKit1);

            }

            //additional condition for some cart items
            //for raked_panel, we removed extra characters from the slug
            if( cartKey === "left_raked_panel" ){
                //Remove H and W chars
                modifiedCartKey = modifiedCartKey.replace("H", '').replace("W", '');
            }
            if( cartKey === "right_raked_panel" ){
                //Remove H and W chars
                modifiedCartKey = modifiedCartKey.replace("H", '').replace("W", '');
            }

            //additional condition for panel_post to exclude el with class `post-left` OR `post-right`
            if( cartKey === "panel_post" || cartKey === "raked_post" ){   
                if(el.classList.contains('panel-no-post') ) {
                    qty = 0;
                }                

                if( el.classList.contains(FENCES.cartItems.item.swivelBrackets.className) ){
                    modifiedCartKey = FENCES.cartItems.item.swivelBrackets.slug;
                    qty = FENCES.cartItems.item.swivelBrackets.qty;
                    cartValue = true;
                }
            }

            //Update the object `slug` and `qty` property before pushing to the array
            entry.slug = modifiedCartKey;
            entry.qty = qty;


            //Iterate though existing cart items array
            //This condition will handle the check if the cart item slug already exists in the array
            if( newCartItems.length > 0 ){
                for (let i = 0; i < newCartItems.length; i++) {
                    //We are using the `slug` property to check if the cart item already exists in the array
                    if (newCartItems[i].slug === modifiedCartKey) {
                        //If it exists, increase the quantity by 1
                        newCartItems[i].qty += qty;
                        found = true;
                        break;
                    }
                }
            }

            //If the cart item slug does not exists in array, push/add it into the array
            if( !found && cartValue !== null && entry.qty != 0 ){
                newCartItems.push(entry);
            }

        }

        newCartItems = FENCES.cartItems.apply_conditions(newCartItems);

        return newCartItems;
    },

    apply_conditions: function(newCartItems) {

        //Apply condition for panel_options+even
        newCartItems = FENCES.cartItems.apply_panel_options_even(newCartItems);

        //Apply condition for panel_options+full
        newCartItems = FENCES.cartItems.apply_panel_options_full(newCartItems);

        //Apply condition for post_options+opt-1 
        newCartItems = FENCES.cartItems.apply_post_options_opt1(newCartItems);

        //Apply condition for panel_post
        newCartItems = FENCES.cartItems.apply_panel_post(newCartItems);
        
        newCartItems = FENCES.cartItems.cart_conditions(newCartItems);

        return newCartItems;

    },

    cart_conditions: function(array) {

        var tabInfo   = window.tabInfo,
            fenceInfo = window.fenceInfo;


        if( tabInfo[0]?.fence == 'barr' ) {

            /*
                1200H & 1800H Gates: 
                - if Standard Size Gate is selected = add 1x 975W Gate & 1x Hinge & Latch Kit Only
                - if Custom Size Gate is selected = add 1x Panel & 1x Gate Converter & 1x Hinge & Latch Kit
            */

            var tabInfo_filtered_data = tabInfo[0].fields.filter(function(item) {
                if( item.name == 'fence_height' ) {
                    return item;
                }
            });

            const foundGate = array.find(obj => obj['slug'].includes('gate') );

            if( foundGate ) {
         
                var gate_data = fenceInfo.filter(function(item) {
                   return item.control_key == 'gate';
                });

                isSTDGate = false;

                if( fenceInfo ) {
                    isSTDGate = gate_data[0]?.settings?.fields?.find(obj => obj['key'] === "use_std" && obj['val']);
                }

                fenceHeight = parseInt(tabInfo_filtered_data[0].value);

                if( [1200, 1800].includes(fenceHeight) ) {

                    if( isSTDGate ) {
                        /*
                        array.push(FENCES.cartItems.item.gateKit3);
                        array.push(FENCES.cartItems.item.gateKit4);
                        */
                    } else {

                        // Converter
                        FENCES.cartItems.item.gateKit2.slug = `${FENCES.cartItems.item.gateKit2.slug}+${fenceHeight}`;
                        array.push(FENCES.cartItems.item.gateKit2);
                        
                        /*
                        array.push(FENCES.cartItems.item.gateKit3);
                        array.push(FENCES.cartItems.item.gateKit4);
                        */
                    }

                } else {
                    // Converter
                    FENCES.cartItems.item.gateKit2.slug = `${FENCES.cartItems.item.gateKit2.slug}+${fenceHeight}`;
                    array.push(FENCES.cartItems.item.gateKit2);                
                }

            }

        }


        return array;

    },
    
    /**
     * IF gate is found
     * @param {*} array 
     * @returns 
     */
    apply_panel_post: function(array) {

        const foundGate = array.find(obj => obj['slug'] === "gate");

        array.forEach(function(v, k){

            if( foundGate &&  v.slug.includes('panel_post') ) {
                v.qty = v.qty; //  - 1
                array[k] = v;
            }
     
        });

       return array;
    },    

    /**
     * IF panel options = "Evenly Spaced Posts" = (Number of Fence Panels) + (short panel offcut panel length)
     * @param {*} array 
     * @returns 
     */
    apply_panel_options_even: function(array) {

        //Get offcut size
        let getOffCutValue = document.querySelector('.fencing-offcut')?.getAttribute('data-cart-value');
        let getPanelItems  = document.querySelectorAll('.panel-item:not(.fencing-raked-panel)').length;

        //Find the existing object
        const foundObject = array.find(obj => obj['slug'] === "panel_options+even");

        let qty = getPanelItems;

        if( qty ) {
            FENCES.cartItems.apply_panel_options_bracket(array, qty);
        }

        return array;

    },

    /**  1 + 3
     * IF panel options = "Full Panels - 3000W" = (Number of full length Panels) + (Number of short length panels)
     * @param {*} array 
     * @returns 
     */
    apply_panel_options_full: function(array) {

        //Get all short panel item
        let noOfShortPanel = document.querySelectorAll('.short-panel-item').length;
        let getPanelItems  = document.querySelectorAll('.panel-item:not(.fencing-raked-panel)').length;
        let getRakedPanelItems = document.querySelectorAll('.panel-item.fencing-raked-panel').length;

        //Find the existing object
        const foundObject = array.find(obj => obj['slug'] === "panel_options+full");


        var qty = getPanelItems + getRakedPanelItems;

        if( qty ) {
            FENCES.cartItems.apply_panel_options_bracket(array, qty);
        }

        return array;

    },

    
    /**
     * Get qty of either slug selected for panel_options{even/full}
     * @param {*} array 
     * @param {*} total 
     * @returns 
     */
    apply_panel_options_bracket: function(array, total){

        let slug = "panel_options+bracket";

        //Find if the slug already exists
        const foundObject = array.find(obj => obj['slug'] === slug );

        //Exists
        if (foundObject) {
            //then update the qty value
            foundObject['qty'] = total;

        //Doesnt exists
        } else {
            //add new object for the slug
            array.push({
                "slug": slug,
                "qty": total,
            });

        }


    },

    /**
     * Apply Condition for Base Plated Posts | Row 16
     * Condition: Object with slug `panel_post+opt-1` AND `raked_post+opt-1` must be in array
     * 
     * @param {*} array 
     * @returns 
     */
    apply_raked_panel_post_opt1: function(array) {

        //Find the two objects with slug `panel_post+opt-1` and `raked_post+opt-1` in the array
        //If it exists means user selected it
        const foundPanelPostOpt1 = array.find(obj => obj['slug'] === "panel_post+opt-1");
        const foundRakedPostOpt1 = array.find(obj => obj['slug'] === "raked_post+opt-1");

        //If any of the slug returns undefined, do nothing
        if( typeof foundPanelPostOpt1 === "undefined" || typeof foundRakedPostOpt1 === "undefined" ){
            return array;
        }

        //Remove `post_options+opt-2` from array
        array = FENCES.cartItems.remove_post_options_opt2(array);

        let total = foundPanelPostOpt1.qty + foundRakedPostOpt1.qty;

        array.push({
            "slug": "raked_panel_post+opt-1",
            "qty": total,
        });

        return array;

    },

    /**
     * Apply Condition for Cemented Post | Row 20
     * Condition: Object with slug `panel_post+opt-2` AND `raked_post+opt-2` must be in array
     * 
     * @param {*} array 
     * @returns 
     */
    apply_raked_panel_post_opt2: function(array) {

        const foundRakedPostOpt2 = array.find(obj => obj['slug'] === "raked_post+opt-2");

        //If any of the slug returns undefined, do nothing
        if( typeof foundRakedPostOpt2 === "undefined" ){
            return array;
        }

        //Find the two objects with slug `panel_post+opt-2` and `raked_post+opt-2` in the array
        //If it exists means user selected it
       for (var i = 0; i < array.length; i++) {
            if (array[i].slug == 'raked_post+opt-2') {
                array[i].qty = array[i].qty + 1; 
                break;
            }
        }

        return array;

    },

    /**
     * Apply Condition for Cemented Post
     * Condition: Object with slug `panel_post+opt-1` AND `raked_post+opt-1` must be in array
     * 
     * @param {*} array 
     * @returns 
     */
    apply_post_options_opt1: function(array) {

        var total = 0;

        //Find the two objects with slug `panel_post+opt-1` and `raked_post+opt-1` in the array
        //If it exists means user selected it
        const foundPanelPostOpt1 = array.find(obj => obj['slug'].includes("panel_post+opt-1") );
        const foundRakedPostOpt1 = array.find(obj => obj['slug'].includes("raked_post+opt-1") );
        const foundGate = array.find(obj => obj['slug'] === "gate");

        //If any of the slug returns undefined, do nothing
        if( typeof foundPanelPostOpt1 !== "undefined" ) {
            total += foundPanelPostOpt1.qty;
        } 

        if( typeof foundRakedPostOpt1 !== "undefined" ){
            total += foundRakedPostOpt1.qty;
        }

        if( total ) {

            array.push({
                "slug": "base_plate+dynabolts",
                "qty": total,
            });

            array.push({
                "slug": "base_plate+post_cover",
                "qty": total,
            });

            return array;
        }

        return array;
    },

    remove_item: function(array, slug) {

        //To removed
        const foundPostOptionsOpt2 = array.find(obj => obj['slug'] === slug);
        if( typeof foundPostOptionsOpt2 !== "undefined" ){
            array = array.filter(obj => obj.slug !== slug);
        }
        return array;
    },

    remove_post_options_opt2: function(array) {
        let slug = "post_options+opt-2";
        //To removed
        const foundPostOptionsOpt2 = array.find(obj => obj['slug'] === slug);
        if( typeof foundPostOptionsOpt2 !== "undefined" ){
            array = array.filter(obj => obj.slug !== slug);
        }

        return array;
    }
    

}
