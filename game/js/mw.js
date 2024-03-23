/**
 * mw.js - JavaScript include that is used by mw_header for MEGA World
 * JS that is used across multiple pages, like generic utility functions should be included here.
 */

// Run this code when the DOM is loaded
document.addEventListener("DOMContentLoaded", function() {
    applyValidation();
    // Ensure dark mode is correctly configured, body class is set correctly
    if (getCookie('darkmode')===null) {
        if (darkMedia.matches) switchToDarkMode();
    }

    // This hack allows iOS Safari to react to "active" events
    document.querySelector('body').addEventListener('touchstart', function() {});

    // Popup elements
    const modalClose = document.getElementById('modalClose');
    const modalBackground = document.getElementById('modalBackground');
    const modalContainer = document.getElementById('modalContainer');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    const menuButton = document.getElementById('menu');
    // Prep menu for display on this screen
    if (modalContainer) {
        modalContainer.style.transform=`translate(0, -${window.innerHeight}px)`;
    }

    if (modalClose) {
        modalClose.addEventListener('click', closeModal);

        modalClose.addEventListener('mouseover', function() {
            document.getElementById('closeKbd').style.opacity='1';
        });
        modalClose.addEventListener('mouseout', function() {
            document.getElementById('closeKbd').style.opacity='0';
        });
    }

    if (modalBackground) {
        modalBackground.addEventListener('click', function() {
            // trigger click on closeButton when background is clicked
            modalClose.dispatchEvent(new Event('click'));
        });
    }

    if (menuButton) {
        menuButton.addEventListener('click', function(){
            showMenu();
        });
    }
}); // end DOMContentLoaded

// Check for WebP support. Sets a cookie if webP is supported
function testWebPSupport(callback) {
    const img = new Image();
    img.onload = function() {
        callback(img.width > 0 && img.height > 0);
    };

    img.onerror = function() {
        callback(false);
    };

    img.src = "data:image/webp;base64,UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA==";
}

// We can use higher-quality webP graphics
let useWebP = false;
useWebP = getCookie('useWebP') === 'true';

if (getCookie('useWebP')==null) {
    testWebPSupport(function(isSupported) {
        useWebP = isSupported;
        setCookie('useWebP', isSupported);
    });
}

function closeModal() {
    // Don't do anything if the modal has no content (and thus should already be closed)
    if (modalContent.innerHTML.length === 0) return;
    modalBackground.style.opacity='0';
    modalContainer.style.transform='translate(0, '+window.innerHeight+'px)';
    // After the transition is complete, set up the style for showing it again later
    setTimeout(function(){
        modalContainer.style.display=modalBackground.style.display='none';
        modalContent.innerHTML='';
        modalContainer.style.transform='translate(0, -'+window.innerHeight+'px)';
        modalContainer.style.transitionTimingFunction='ease-out';
        // Not sure about this, can tweak transition to make it look smoother
        // modalContainer.style.transitionTimingFunction='ease-out';
    },300);
}

// Shows a modal dialog with the specified title and content
function showModal(title, content) {
    modalTitle.innerHTML=title;
    modalContent.innerHTML=content;
    // Typeset math, if there is any. I could make this more specific to the scenarios that need it later.
    // console.log('Typesetting Math');
    if (!!MathJax) MathJax.typeset();

    // Position so the window is off screen so it can animate in.
    modalContainer.style.display=modalBackground.style.display='block';
    // Short timeout to allow transitions to work after displaying element
    setTimeout(function() {
        modalContainer.style.transform='none';
        modalBackground.style.opacity='1';
    },10);
    // Set up transitions for close
    setTimeout(function(){
        modalContainer.style.transitionTimingFunction='ease-in';
    }, 300);
}

// show a message banner at the top of the screen for notifications and errors.
// set hideAfter to 0 to make a message persist indefinitely.
function showToast(message, toastType="", hideAfter = 5) {
    let classes = `toast ${toastType}
		absolute z-50 top-8 inset-x-0 text-center m-auto p-2 max-w-2xl text-black rounded-lg
		shadow-lg-dark dark:text-white`;

    let icon = '';

    // Set the background color based on the toast type
    switch (toastType) {
        case 'error':
            classes+=' bg-red-300/90 dark:bg-red-800/90';
            icon = '<span class="float-left inline-block mr-2"><i class="fas fa-exclamation-triangle"></i></span>';
            break;
        case 'success':
            classes+=' bg-green-300/90 dark:bg-green-800/90';
            break;
        default:
            classes+=' bg-gray-300/90 dark:bg-gray-900/90';
            break;
    }

    const tst = document.createElement("DIV");
    tst.className = classes;

    tst.innerHTML=`<span class="float-right w-5 h-5 text-xl flex justify-center items-center opacity-60 hover:opacity-100"><i class="fas fa-times"></i></span>${icon}<span class="toastMessage">${message}</span>`;

    // Position so the toast is off screen and can animate in.
    // Short timeout to allow transitions to work after displaying element
    document.body.appendChild(tst);
    tst.style.transform='translate(0, -'+(tst.offsetHeight+32)+'px)';

    // TODO: These should be refactored to use a transitionend event
    setTimeout(function() {
        tst.style.transform='none';
    },5);

    // Set up transitions for close
    setTimeout(function(){
        tst.style.transitionTimingFunction='ease-in';
    }, 200);

    // Remove when clicked
    tst.addEventListener('click', clearToast);
    if (parseInt(hideAfter)!==0) setTimeout(function(){clearToast.call(tst);}, hideAfter*1000);
}

// Causes a toast message to immediately fade away. Used when clicking it or its close button.
function clearToast() {
    this.style.opacity='0';
    setTimeout(() => {
        this.remove();
    },1000);
}

// Input validation for email fields
function isEmailValid(el) {
    // trim string to remove any spaces
    el.value = el.value.trim();
    if (document.querySelector('#ftEmailError'+el.id)) {
        document.querySelector('#ftEmailError'+el.id).remove();
    }
    const emailRegex=new RegExp(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/);
    if ( emailRegex.test(el.value) === false ) {
        let div = document.createElement('DIV');
        div.id = 'ftEmailError'+el.id;
        div.className = 'error ftError ftEmailError';

        // This is the only string of localized text in this file.
        div.innerText = typeof invalid_email === 'undefined' ? 'Invalid email address.' : invalid_email;
        el.insertAdjacentElement('afterend', div);
        return false;
    } else return true;
}

function formValidate(debug) {
    // Default debug setting to false
    if (typeof debug === 'undefined') debug = false;
    let valid = true;

    // Hide any errors that are shown
    const errors = document.querySelectorAll('.validate .error');
    errors.forEach((el) => {el.style.display = 'none';})

    // Form validation
    // Check that any required fields have a value. :input catches textarea, select, etc
    const inputs = document.querySelectorAll('.validate input.required, .validate textarea.required, .validate select.required');

    for (let i = 0; i < inputs.length; i++) {
        // Handle checkbox
        if (inputs[i].type === 'checkbox') {
            if (inputs[i].checked === false ) {
                valid = false;
                if (debug) console.log(`invalid: ${inputs[i].id}`);
                showError(inputs[i]);
            }
        }
        // Handle radio buttons
        if (inputs[i].type === 'radio') {
            const radioButtons = document.querySelectorAll(`input[name="${inputs[i].name}"]`);
            Array.from(radioButtons).every(function(element) {
                if (element.checked !== true) {
                    valid = false;
                } else {
                    valid = true;
                    return false;
                }
            });

            showError(inputs[i]);
            if (debug && valid === false) console.log('invalid: '+inputs[i].id);
        }

        // Handle other kinds of fields
        else if (inputs[i].value.length === 0) {
            valid = false;
            if (debug) console.log('invalid: '+inputs[i].id);
            showError(inputs[i]);
        }
    }

    document.querySelectorAll('form .email').forEach(function(el){
        if (el.value.length > 0 && isEmailValid(el) === false) {
            valid = false;
            if (debug) console.log('invalid: '+this.id);
        }
    });

    return valid;
}

// This function can be used to refresh form validation listeners if content is reloaded via AJAX
function applyValidation() {
    // Validate entire submission when the submit button is clicked
    //   unless the NoAutoValidation class is set.
    const forms = document.querySelectorAll('.validate:not(.noAutoValidation)');
    forms.forEach((el) => {
        el.addEventListener('submit', (e) => {
            e.preventDefault();
            if (formValidate()) this.submit();
        });
    });
}

function showError(el) {
    let errorElId = el.id+'Error';
    //Errors for radio groups are a bit different, based on name
    if (el.type === 'radio') {
        errorElId = el.name+'Error';
    }

    const errorEl = document.getElementById(errorElId);
    if (errorEl) {
        errorEl.style.display = '';
        // Have to remove the hidden element if it was present
        errorEl.classList.remove('hidden');
    }
} // End showError()


/*************************************************************************
 Utility functions for Cookies

 Parameters - name=key and value=value
 Coookie expires in 10 years
 Applies to the root of apps (apply to every page.)
 *************************************************************************/
function setCookie(key, value) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (10 * 365 * 24 * 60 * 60 * 1000));
    document.cookie = key + '=' + value + ';path=/;expires=' + expires.toUTCString();
}

// getCookie function return the value of the Cookie named 'key'
function getCookie(key) {
    const keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    return keyValue ? keyValue[2] : null;
}
// Expires/clears the cookie
function deleteCookie(key) {
    if(getCookie(key)) document.cookie=key + '=;path=/;expires=Thu, 01 Jan 1970 00:00:01 GMT';
}

/**
 * Utility functions for Dark Mode
 * Sets up environment appropriately for dark or light modes
 */
let darkMedia = window.matchMedia('(prefers-color-scheme: dark)');

// Handle switching OS theme with no preference explicitly set via cookie
darkMedia.addEventListener('change', function() {
    if (typeof getCookie('darkmode') !== "string") {
        if (darkMedia.matches) switchToDarkMode();
        else switchToLightMode();
    }
});

function switchToDarkMode() {
    if (typeof getCookie('darkmode') === 'string') setCookie('darkmode', "true");

    document.documentElement.classList.remove('light');
    document.documentElement.classList.add('dark');
}

function switchToLightMode() {
    if (typeof getCookie('darkmode')==='string') setCookie('darkmode', "false");

    document.documentElement.classList.remove('dark');
    document.documentElement.classList.add('light');
}

function toggleDarkMode() {
    if (getCookie('darkmode') === 'true') switchToLightMode();
    else if (getCookie('darkmode') === 'false') switchToDarkMode();
    else {
        setCookie('darkmode', '?');
        if (darkMedia.matches) switchToLightMode();
        else switchToDarkMode();
    }
}