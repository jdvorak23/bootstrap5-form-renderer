
/**
 * Handles client side validation for forms created by Nette and Bootstrap5FormRenderer
 * This script is part of the Bootstrap5FormRenderer
 * https://github.com/jdvorak23/bootstrap5-form-renderer
 * This script works only with https://github.com/nette/forms/blob/master/src/assets/netteForms.js predeclared
 */

(function(Nette){
    if(typeof Nette !== 'object')
        return;
    /**
     * Classes used for validation.
     */
    Nette.validationClasses = {
        formErrorContainer: 'form-errors',
        errorContainer : 'invalid-feedback',
        listContainer: 'list-element',
        valid: 'is-valid',
        invalid: 'is-invalid'
    }
    /**
     * Finds elements essential for validation.
     * @param controlElement
     * @param form
     * @returns {{container: null, parent: null, error: null, element}}
     */
    Nette.getValidationElements = function(controlElement, form) {
        const elements = {
            element : controlElement,
            parent : null,
            error : null,
            container : null
        };
        // Tries to find element with errorContainer class
        // If not in siblings, try parent siblings etc. Also saves 'parent' element (the real sibling)
        let sibling = controlElement;
        while(!elements.error && sibling !== form) {
            if(sibling.classList.contains(Nette.validationClasses.errorContainer)) {
                elements.error = sibling;
                continue;
            }
            if(!sibling.nextElementSibling){
                elements.parent = sibling.parentElement;
                sibling = sibling.parentElement;
            }else
                sibling = sibling.nextElementSibling;
        }

        if(!(controlElement.type in {radio: 1, checkbox: 1}))
            return elements;

        // If radio or checkbox, tries to find deep parent with class listContainer
        let parent = controlElement.parentElement;
        while(parent !== form) {
            if(parent.classList.contains(Nette.validationClasses.listContainer)){
                elements.container = parent;
                return elements;
            }
            parent = parent.parentElement;
        }
        return elements;
    }
    /**
     * Adds / removes classes based on parameter isValid
     */
    Nette.setValidationClasses = function (element, isValid){
        element.classList.add(isValid ? Nette.validationClasses.valid : Nette.validationClasses.invalid)
        element.classList.remove(isValid ? Nette.validationClasses.invalid : Nette.validationClasses.valid);
    }
    /**
     * Sets elements essential for validation and error message
     * @param elements
     * @param isValid
     * @param message
     */
    Nette.setValidationElements = function(elements, isValid, message = ''){
        if(elements.error){
            if(elements.error.firstElementChild) {
                // If there is more error elements, all but not first are removed
                for(let i= 1; i < elements.error.children.length; i++)
                    elements.error.children[i].remove();
                elements.error.firstElementChild.innerText = message;
            }
            else
                elements.error.innerText = message;
            // Sets parent element, if error container exists
            if(elements.parent)
                Nette.setValidationClasses(elements.parent, isValid);
        }
        // Sets control element
        Nette.setValidationClasses(elements.element, isValid);
        // Sets radios or checkbox container, if any
        if(elements.container)
            Nette.setValidationClasses(elements.container, isValid);
    }
    /**
     * Sets all validable controls in form as valid
     * @param form
     */
    Nette.setAllControlsValid = function(form){
        for (let i = 0; i < form.elements.length; i++){
            const element = form.elements[i];
            if (!(element.tagName.toLowerCase() in {input: 1, select: 1, textarea: 1})
                || element.type in {hidden: 1, button: 1, image: 1, submit: 1, reset: 1})
                continue;
            const elements = Nette.getValidationElements(element, form);
            Nette.setValidationElements(elements, true);
        }
    }
    /**
     * Resets validation for all controls in form
     * @param form
     */
    Nette.resetValidation = function (form) {
        // Remove all is-valid
        [...form.querySelectorAll('.' + Nette.validationClasses.valid)].forEach(element => {
            element.classList.remove(Nette.validationClasses.valid);
        });
        // Remove all is-invalid
        [...form.querySelectorAll('.' + Nette.validationClasses.invalid)].forEach(element => {
            element.classList.remove(Nette.validationClasses.invalid);
        });
        // Remove errors on whole form
        form.querySelector('.' + Nette.validationClasses.formErrorContainer)?.remove();
    }
    /**
     * Rewritten to set Bootstrap 5 validation classes and append error message
     * @param form
     * @param errors
     */
    Nette.showFormErrors = function(form, errors) {
        Nette.setAllControlsValid(form);
        let focusElem;
        for (let i = 0; i < errors.length; i++) {
            const element = errors[i].element;
            const message = errors[i].message;
            const elements = Nette.getValidationElements(element, form);
            Nette.setValidationElements(elements, false, message);

            if (!focusElem && element.focus)
                focusElem = element;
        }
        if (focusElem)
            focusElem.focus();
    };
    /**
     * Helps properly toggle for Nette if reset event occurs - it doesn't count with reset event
     * @param form
     */
    Nette.resetToggles = function(form){
        let names = [];
        for (let i = 0; i < form.elements.length; i++){
            const element = form.elements[i];
            if (!(element.tagName.toLowerCase() in {input: 1, select: 1, textarea: 1})
                || element.type in {hidden: 1, button: 1, image: 1, submit: 1, reset: 1}
                || names.indexOf(element.name) !== -1)
                continue;
            window.setTimeout(function (){
                element.dispatchEvent(new window.Event('change', { bubbles: true }))
            });
            names.push(element.name);
        }
    };

    /**
     * Set reset event of forms to reset validation
     */
    window.addEventListener('DOMContentLoaded', () => {
        [...document.querySelectorAll('form')].forEach(form => {
            form.addEventListener('reset', () => {
                Nette.resetValidation(form);
                Nette.resetToggles(form);
            });
        });
    });

    /**
     * Just needed comment a few lines in the middle to solve radios. Yes I am lazy.
     */
    Nette.validateForm = function(sender, onlyCheck) {
        var form = sender.form || sender,
            scope = false;

        Nette.formErrors = [];

        if (form['nette-submittedBy'] && form['nette-submittedBy'].getAttribute('formnovalidate') !== null) {
            var scopeArr = JSON.parse(form['nette-submittedBy'].getAttribute('data-nette-validation-scope') || '[]');
            if (scopeArr.length) {
                scope = new RegExp('^(' + scopeArr.join('-|') + '-)');
            } else {
                Nette.showFormErrors(form, []);
                return true;
            }
        }

        var radios = {}, i, elem;

        for (i = 0; i < form.elements.length; i++) {
            elem = form.elements[i];

            if (elem.tagName && !(elem.tagName.toLowerCase() in {input: 1, select: 1, textarea: 1, button: 1})) {
                continue;

            }
            /*  else if (elem.type === 'radio') {
                  if (radios[elem.name]) {
                      continue;
                  }
                  radios[elem.name] = true;
              }*/
            if ((scope && !elem.name.replace(/]\[|\[|]|$/g, '-').match(scope)) || Nette.isDisabled(elem)) {
                continue;
            }

            if (!Nette.validateControl(elem, null, onlyCheck) && !Nette.formErrors.length) {
                return false;
            }
        }
        var success = !Nette.formErrors.length;
        Nette.showFormErrors(form, Nette.formErrors);
        return success;
    };
})(typeof Nette === 'undefined' ? null : Nette);

