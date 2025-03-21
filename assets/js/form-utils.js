window.clearForm = function(formId, titleId, defaultTitle) {
    let form = document.getElementById(formId);
    if (!form) return;

    // Nustatome pradinį formos pavadinimą
    if (titleId) {
        let titleElement = document.getElementById(titleId);
        if (titleElement) {
            titleElement.textContent = defaultTitle;
        }
    }

    // Išvalome visus input laukus (išskyrus submit/reset)
    form.querySelectorAll("input").forEach(input => {
        if (input.type !== "submit" && input.type !== "reset" && input.type !== "button") {
            input.value = "";
        }
    });

    // Išvalome visus textarea laukus
    form.querySelectorAll("textarea").forEach(textarea => {
        textarea.value = "";
    });

    // Atstatome pasirinkimą select laukams
    form.querySelectorAll("select").forEach(select => {
        select.selectedIndex = 0;
    });

    // Atstatome select2 laukus
    $(form).find("select").each(function () {
        if ($(this).hasClass("select2")) {
            $(this).val(null).trigger("change"); // Išvalome select2 ir atnaujiname
        }
    });
};

window.showMessage = function(message, type) {
    let alertBox = document.getElementById("alert-message");
    
    if (!alertBox) return;

    // Priskiriame klasę pagal tipą (žalias - success, raudonas - danger)
    alertBox.className = `alert alert-${type} alert-dismissible fade show`;
    alertBox.textContent = message;

    // Rodome pranešimą
    alertBox.classList.remove("d-none");

    // Po 3 sek. pranešimas išnyksta
    setTimeout(() => {
        alertBox.classList.add("d-none");
    }, 3000);
};


window.updateTableRow = function(data) {
    let table = $('#data-table').DataTable();
    let rows = table.rows().nodes();

    $(rows).each(function () {
        let row = $(this);
        let idCell = row.find("td:first");

        if (idCell.text().trim() === data.kai_id) {
            let cells = row.find("td");
            let keys = Object.keys(data);

            for (let i = 1; i < cells.length - 1; i++) { // Paskutinis stulpelis yra "Veiksmai"
                let key = keys[i];
                if (key) {
                    cells.eq(i).text(data[key]);
                }
            }
        }
    });
};

function validateWidth(input) {

    const value = parseInt(input.value);
    const min = parseInt(input.getAttribute('data-min-width'));
    const max = parseInt(input.getAttribute('data-max-width')) || Infinity;

    // Paimti gaminio tipą
    const mechanismSelect = document.getElementById('mechanism_id');
    const widthErrorMessage = document.getElementById('width-error-message');
    const medzwidthErrorMessage = document.getElementById('medzwidth-error-message');
    const widthWarning = document.getElementById('widthWarning');
    const agreeRadio = document.getElementById('agree');
    const disagreeRadio = document.getElementById('disagree');

    const medzwidthField = document.getElementById('medzwidth');
    const medzwidthValue = medzwidthField ? parseInt(medzwidthField.value) : null;

    // Patikrinti, ar pasirinktas gaminio tipas
    if (!mechanismSelect.value) {
        input.classList.add('is-invalid');
        showErrorMessage(widthErrorMessage, 'Pradžioje pasirinkite gaminio tipą');
        return; // Nutraukia funkciją, jei gaminio tipas nepasirinktas
    }

    // Pašalinti visas klaidas
    input.classList.remove('is-invalid', 'is-valid', 'border-warning');
    input.setCustomValidity("");
    hideErrorMessage(widthErrorMessage);
    widthWarning.classList.add('d-none'); // Paslėpti įspėjimą
    agreeRadio.removeAttribute('required');
    disagreeRadio.removeAttribute('required');

    if (medzwidthValue !== null) {
        const minDifference = 35;
        const maxDifference = 54;
        const widthDifference = Math.abs(value - medzwidthValue);  // Naudojame 'value', o ne 'widthValue'

        if (widthDifference < minDifference) {
            showErrorMessage(medzwidthErrorMessage, `Per mažas skirtumas tarp gaminio ir medžiagos pločių. Mažiausias leistinas skirtumas ${minDifference} mm.`);
        } else if (widthDifference > maxDifference) {
            showErrorMessage(medzwidthErrorMessage, `Per didelis skirtumas tarp gaminio ir medžiagos pločių. Didžiausias leistinas skirtumas ${maxDifference} mm.`);
        } else {
            clearErrorMessage(medzwidthErrorMessage);
        }
    }

    // Patikrinti, ar įvestas teisingas plotis
    if (isNaN(value)) {
        input.classList.add('is-invalid');
        showErrorMessage(widthErrorMessage, 'Įveskite teisingą skaičių');
    } else if (value < min) {
        input.classList.add('is-invalid');
        showErrorMessage(widthErrorMessage, `Reikšmė negali būti mažesnė nei ${min} mm`);
        input.setCustomValidity(`Reikšmė turi būti tarp ${min} ir ${max}`);
    } else if (value > max) {
        widthWarning.classList.remove('d-none'); // Parodome įspėjimą    
        input.classList.add("border-warning");
        agreeRadio.setAttribute('required', 'required');
        disagreeRadio.setAttribute('required', 'required');
    } else {
        widthWarning.classList.add('d-none');
        input.classList.add('is-valid');
    }

    // Patikrinti, ar pasirinktas sutikimas su įspėjimu
    agreeRadio.addEventListener('change', validateWidthAgreement);
    disagreeRadio.addEventListener('change', validateWidthAgreement);
}
function showErrorMessage(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
       
        // Surasti arba sukurti klaidos pranešimo elementą
        let errorElement = input.parentElement.querySelector('.error-tooltip');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-tooltip';
            errorElement.style.color = 'red';
            errorElement.style.fontSize = '0.875rem';
            errorElement.style.marginTop = '0.25rem';
            input.parentElement.appendChild(errorElement);
        }
       
        errorElement.textContent = message;
    }

    function clearErrorMessage(element) {
        element.classList.add('d-none'); // Paslėpiame klaidos pranešimą
    }
   
    function hideErrorMessage(input) {
        input.classList.remove('is-invalid');
       
        const errorElement = input.parentElement.querySelector('.error-tooltip');
        if (errorElement) {
            errorElement.textContent = '';
        }
    }

    function validateWidthAgreement() {
        const widthWarningError = document.getElementById('widthWarningError');
        const agreeRadio = document.getElementById('agree');
        const disagreeRadio = document.getElementById('disagree');

        // Jei neišrinktas nei "Sutinku", nei "Nesutinku"
        if (!agreeRadio.checked && !disagreeRadio.checked) {
            widthWarningError.classList.remove('d-none');
            return false;
        } else {
            widthWarningError.classList.add('d-none');
            return true;
        }
    }

function validateMedzwidth(input) {
    const value = parseInt(input.value); // Paimti įvestą reikšmę
    const widthInput = document.getElementById('width');  // Gaminio plotis
    const widthValue = parseInt(widthInput.value);  // Gaminio pločio reikšmė
    //const widthErrorMessage = document.getElementById('width-error-message');
    const widthWarning = document.getElementById('widthWarning');
    const medzwidthErrorMessage = document.getElementById('medzwidth-error-message');
    const minDifference = 35;
    const maxDifference = 54;
    input.classList.remove('is-invalid', 'is-valid');
    // Patikrinti, ar medžiagos plotis yra teisingas
    if (isNaN(value)) {
        input.classList.add('is-invalid');
        showErrorMessage(medzwidthErrorMessage, 'Įveskite teisingą skaičių');
        return;
    } 

    // Patikrinti skirtumą tarp gaminio ir medžiagos pločių
    
    const widthDifference = widthValue - value; // Apskaičiuoti skirtumą

    if ( widthDifference < minDifference ){
        showErrorMessage(medzwidthErrorMessage, 'Per mažas skirtumas tarp gaminio ir medžiagos pločių. Mažiausias leistinas skirtumas '+minDifference+' mm.');
        input.classList.add('is-invalid');
    } else if (widthDifference > maxDifference) {
        showErrorMessage(medzwidthErrorMessage, 'Per didelis skirtumas tarp gaminio ir medžiagos pločių. Didžiausias leistinas skirtumas '+maxDifference+' mm.');
        input.classList.add('is-invalid');
    } else {       
        input.classList.add('is-valid');
        hideErrorMessage(medzwidthErrorMessage);        
    }
}

function validateinput(input) {  
    // Jei laukas tuščias, nereikia rodyti klaidos
    if (input.value === '') {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        
    }
    else{
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }
    return;
    
}

