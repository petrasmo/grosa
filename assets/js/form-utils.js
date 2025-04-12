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

    const mechanismSelect = document.getElementById('mechanism_id');
    const widthErrorMessage = document.getElementById('width-error-message');
    const medzwidthErrorMessage = document.getElementById('medzwidth-error-message');
    const widthWarning = document.getElementById('widthWarning');
    const agreeRadio = document.getElementById('agree');
    const disagreeRadio = document.getElementById('disagree');

    const medzwidthField = document.getElementById('medzwidth');
    const medzwidthValue = medzwidthField ? parseInt(medzwidthField.value) : null;

    // 🔴 1. Validacija: ar pasirinktas gaminio tipas
    if (!mechanismSelect.value) {
        input.classList.add('is-invalid');
        showErrorMessage(widthErrorMessage, 'Pradžioje pasirinkite gaminio tipą');
        return;
    }

    // 🧼 2. Išvalyti buvusias klaidas
    input.classList.remove('is-invalid', 'is-valid', 'border-warning');
    input.setCustomValidity('');
    hideErrorMessage(widthErrorMessage);
    widthWarning.classList.add('d-none');

    // 🔍 3. Patikrinam medžiagos pločio skirtumą
    if (medzwidthValue !== null && !isNaN(value)) {
        const minDiff = 35;
        const maxDiff = 54;
        const diff = Math.abs(value - medzwidthValue);

        if (diff < minDiff) {
            showErrorMessage(medzwidthErrorMessage, `Per mažas skirtumas tarp gaminio ir medžiagos pločių. Mažiausias leistinas skirtumas ${minDiff} mm.`);
        } else if (diff > maxDiff) {
            showErrorMessage(medzwidthErrorMessage, `Per didelis skirtumas tarp gaminio ir medžiagos pločių. Didžiausias leistinas skirtumas ${maxDiff} mm.`);
        } else {
            clearErrorMessage(medzwidthErrorMessage);
        }
    }

    // ❌ 4. Netinkamas skaičius arba per mažas
    if (isNaN(value)) {
        input.classList.add('is-invalid');
        showErrorMessage(widthErrorMessage, 'Įveskite teisingą skaičių');
        return;
    }

    if (value < min) {
        input.classList.add('is-invalid');
        showErrorMessage(widthErrorMessage, `Reikšmė negali būti mažesnė nei ${min} mm.`);
        input.setCustomValidity(`Mažiausias leistinas: ${min} mm.`);
        return;
    }

    // ⚠️ 5. Didesnis nei leidžiamas
    if (value > max) {
        widthWarning.classList.remove('d-none');
        input.classList.add('border-warning');

        agreeRadio.setAttribute('required', 'required');
        disagreeRadio.setAttribute('required', 'required');

        // Tikrinam ar pasirinktas sutikimas
        if (!agreeRadio.checked && !disagreeRadio.checked) {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            showErrorMessage(widthErrorMessage, 'Turite pasirinkti, ar sutinkate su didesniu pločiu.');
            return;
        }

        // Jei pasirinkta – laikom validu
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return;
    }

    // ✅ 6. Viskas gerai
    input.classList.add('is-valid');
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
        const value = parseInt(input.value); // Medžiagos plotis
        const widthInput = document.getElementById('width'); // Gaminio plotis
        const widthValue = parseInt(widthInput.value); // Gaminio pločio reikšmė
        const medzwidthErrorMessage = document.getElementById('medzwidth-error-message');
    
        const minDifference = 35;
        const maxDifference = 54;
    
        // Išvalom klases ir žinutę
        input.classList.remove('is-invalid', 'is-valid');
        hideErrorMessage(medzwidthErrorMessage);
    
        // Tikrinam ar abu reikšmės įvestos
        if (isNaN(value) || isNaN(widthValue)) {
            if (isNaN(value)) {
                input.classList.add('is-invalid');
                showErrorMessage(medzwidthErrorMessage, 'Įveskite teisingą skaičių');
            }
            return;
        }
    
        // Apskaičiuojam skirtumą
        const widthDifference = widthValue - value;
    
        if (widthDifference < minDifference) {
            input.classList.add('is-invalid');
            showErrorMessage(medzwidthErrorMessage, 'Per mažas skirtumas tarp gaminio ir medžiagos pločių. Mažiausias leistinas skirtumas ' + minDifference + ' mm.');
        } else if (widthDifference > maxDifference) {
            input.classList.add('is-invalid');
            showErrorMessage(medzwidthErrorMessage, 'Per didelis skirtumas tarp gaminio ir medžiagos pločių. Didžiausias leistinas skirtumas ' + maxDifference + ' mm.');
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

function pasleptiIrIsvalytiLaukus(rodomiLaukai = [], parentDivId = null) {
    // Surandam tėvinį div'ą, jei nurodytas
   // alert('a');
    let parentDiv = null;
    if (parentDivId) {
        parentDiv = document.getElementById(parentDivId);
    }

    // Surandam visus toggle-field laukus TIK toje srityje (arba visur, jei parentDiv nenustatytas)
    const fields = parentDiv
        ? parentDiv.querySelectorAll('.toggle-field')
        : document.querySelectorAll('.toggle-field');

    fields.forEach(el => {
        const input = el.querySelector('input, select, textarea');
        if (input) {            
            // Išvalom reikšmes
            if (input.tagName === 'SELECT' && input.tomselect) {
                input.tomselect.clear();
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        }

        // Paslepiam
        el.style.display = 'none';
    });

    // Atvaizduojam tuos, kurių ID perduoti masyve
    rodomiLaukai.forEach(id => {
        const el = parentDiv
            ? parentDiv.querySelector(`#${id}`)
            : document.getElementById(id);

        if (el) {
            el.closest('.toggle-field').style.display = 'block';
        }
    });
}

