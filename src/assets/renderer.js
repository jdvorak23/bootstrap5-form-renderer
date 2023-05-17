/**
 * JavaScript for rounding corners of responsive input group elements
 * TWO IMPORTANT SELECTORS:
 * You must add class 'responsive-input-group' to wrappers['controls']['inputGroup'] as a selector for this script
 * It also counts with fact, that you have 'rounded-0' class on every element in input group, as is default in wrappers['inputGroup']['.item']
 * You can change these, and toggle classes for rounding:
 */
window.addEventListener('DOMContentLoaded', () => {
    const inputGroupSelector = ".responsive-input-group";
    const inputGroupItemSelector = ".rounded-0";
    const classToRoundLeft = "rounded-start";
    const classToRoundRight = "rounded-end";

    const roundCorners = function(){
        const getItems = function (element) {
            const items = [];
            [...element.querySelectorAll(inputGroupItemSelector)].forEach(child => {
                if(window.getComputedStyle(child).getPropertyValue("display") !== 'none'
                    && window.getComputedStyle(child).getPropertyValue("visibility") !== 'hidden'){
                    items.push(child);
                }
            });
            return items;
        };
        [...document.querySelectorAll(inputGroupSelector)].forEach(inputGroup => {
            for (let inputGroupItem of inputGroup.children) {
                const items = getItems(inputGroupItem);
                if(!items.length)
                    continue;

                items.forEach(item =>{
                    item.classList.remove(classToRoundLeft, classToRoundRight);
                });

                if (inputGroupItem.offsetLeft <= 0)
                    items[0].classList.add(classToRoundLeft);

                if((inputGroupItem.offsetWidth + inputGroupItem.offsetLeft) >= inputGroup.clientWidth)
                    items[items.length - 1].classList.add(classToRoundRight);
            }
        });
    }
    roundCorners();
   /* If you have some dynamic content hiding / displaying form, events should be added
      Like Bootstrap5 pills
    const tabs = document.querySelector("#pills-tab");
    if(tabs)
        tabs.addEventListener('click', (event) => roundCorners());
        */
    /**
     If you have tons of it, or other dynamic content, consider using also 'DOMSubtreeModified'
     */
    window.addEventListener('resize', () => roundCorners());
});
