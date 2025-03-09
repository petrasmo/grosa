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

