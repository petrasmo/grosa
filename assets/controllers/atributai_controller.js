import { Controller } from "@hotwired/stimulus";




export default class extends Controller {
    static targets = ["select", "tableBody", "tableContainer", "gamtipas", "gamtipasSelect", 
        "colorSelect","materialSelect", "fields","uzsId","uzeId", "UzsakymaiLines"];
       /* static values = {
            initialValue: String,
            initialText: String,
            options: Object
        };*/
    connect() {
        if (this.element.dataset.initialized) {
           // console.warn("⚠️ Atributai valdiklis jau buvo prijungtas!");
            return;
        }
        this.element.dataset.initialized = "true";
        
          
        if (this.hasUzsIdTarget) {
            const uzsId = this.uzsIdTarget.value;
    
            if (uzsId) {
                this.loadTable(uzsId);
            }
        }

        if (this.hasGamtipasSelectTarget) {
            this.gamtipasSelectTarget.addEventListener('change', (e) => {
                const mechanismId = e.target.value;
                const autocompleteElement = document.querySelector('[data-controller="select2"]');
        
                if (autocompleteElement) {
                    const autocompleteController = this.application.getControllerForElementAndIdentifier(
                        autocompleteElement,
                        'select2'
                    );
        
                    console.log('MechanismID:', mechanismId);
                    console.log('autocompleteController:', autocompleteController);
        
                    if (autocompleteController) {
                        autocompleteController.mechanismIdValue = mechanismId;
                        console.log(`✅ Mechanism ID nustatytas: ${mechanismId}`);
                    } else {
                        console.warn('⚠️ Nerastas select2 controlleris!');
                    }
                }
            });
        }

    }

    async loadAttributes() {
        const gaminysId = this.selectTarget.value;

        if (!gaminysId) {
            this.tableContainerTarget.classList.add("d-none");
            return;
        }

        try {
            const response = await fetch(`/formos/atributai?gaminys_id=${gaminysId}`);
            const data = await response.json();
            this.populateTable(data);
        } catch (error) {
            console.error("❌ Klaida gaunant duomenis:", error);
            this.showMessage("Klaida gaunant duomenis", "danger");
        }
    }

    async updateFields(event) {
        const mechanismId = event.target.value;
        if (!mechanismId) return;
        
        try {
            const response = await fetch(`/gaminio-laukai/${mechanismId}`);
            const data = await response.json();
    
            const laukupavadinimai = Object.values(data.fieldNames);
            pasleptiIrIsvalytiLaukus(laukupavadinimai);
    
            // Jei yra spalvų – užkraunam
            if (laukupavadinimai.includes('productColor')) {
                const spalvos = data.spalvos;
                const colorSelect = document.getElementById('productColor');
                colorSelect.innerHTML = '<option value="" selected disabled>Pasirinkite...</option>';
                spalvos.forEach(spalva => {
                    const option = document.createElement('option');
                    option.value = spalva.id;
                    option.textContent = spalva.name;
                    colorSelect.appendChild(option);
                });
            }

            
    
            // ✅ ČIA KVIETI CUSTOM EVENT!
            const laukaiLoadedEvent = new CustomEvent('laukaiLoaded', {
                bubbles: true
            });
            this.element.dispatchEvent(laukaiLoadedEvent); // Iššauni event'ą
    
        } catch (error) {
            console.error("❌ Klaida kraunant laukus:", error);
        }
    }

    async updateTypes(event) {
        const gamId = event.target.value;
        console.log("🔄 Pasirinktas gaminys ID:", gamId);
        if (!gamId) return;

        this.clearFields();
    
        try {
            const response = await fetch(`/gaminio-tipai/${gamId}`);
            console.log("📡 Užklausa išsiųsta į:", `/gaminio-tipai/${gamId}`);
    
            const data = await response.json();
            console.log("📥 Gauti duomenys:", data);
    
            // Patikriname, ar target tikrai egzistuoja
            if (!this.hasGamtipasSelectTarget) {
                console.error("❌ Klaida: Target gamtipasSelect nerastas!");
                return;
            }
    
            // Išvalome esamus pasirinkimus
            this.gamtipasSelectTarget.innerHTML = '<option value="" selected disabled>Pasirinkite...</option>';
    
            // Pridedame naujus pasirinkimus
            data.forEach(item => {
                const option = document.createElement("option");
                option.value = item.id;
                option.textContent = item.text;
                this.gamtipasSelectTarget.appendChild(option);
            });

          
    
        } catch (error) {
            console.error("❌ Klaida kraunant gaminio tipus:", error);
        }
    }

    async updateColors(event) {
        const mechanismId = event.target.value;
        console.log("🔄 Pasirinktas mechanizmas ID:", mechanismId);
        if (!mechanismId) return;

        this.clearFields(); // Išvalome spalvą ir medžiagą
    
        try {
            const response = await fetch(`/gaminio-spalvos/${mechanismId}`);
            console.log("📡 Užklausa išsiųsta į:", `/gaminio-spalvos/${mechanismId}`);
    
            const data = await response.json();
            console.log("📥 Gauti duomenys:", data);
    
            // Patikriname, ar target tikrai egzistuoja
            if (!this.hasColorSelectTarget) {
                console.error("❌ Klaida: Target colorSelect nerastas!");
                return;
            }
    
            // Išvalome esamus pasirinkimus
            this.colorSelectTarget.innerHTML = '<option value="" selected disabled>Pasirinkite...</option>';
    
            // Pridedame naujas spalvas
            data.forEach(item => {
                const option = document.createElement("option");
                option.value = item.id;
                option.textContent = item.name;
                this.colorSelectTarget.appendChild(option);
            });
    
        } catch (error) {
            console.error("❌ Klaida kraunant spalvas:", error);
        }
    }

    loadTable(uzsId) {
        const tableContainer = document.getElementById('my-table-container');
    
        const table = new Tabulator(this.UzsakymaiLinesTarget, {          
          layout: "fitColumns",
          columns: [
            { title: "Užsakymo Nr.", field: "uzs_nr" },
            { title: "Gaminys", field: "gaminys" },
            { title: "Gaminio tipas", field: "gaminio_tipas" },
            { title: "Būsena", field: "uzs_busena" },
            { title: "Pristatymas", field: "uzs_pristatymas" },
            { title: "Plotis", field: "uze_gaminio_plotis" },
            { title: "Aukštis", field: "uze_gaminio_aukstis" },
            {
                title: "Veiksmai",
                formatter: (cell) => {
                  const rowData = cell.getRow().getData();
                  return `
                    <button class="btn btn-sm btn-outline-secondary koreguoti-eilute" data-id="${rowData.uze_id}">
                      ✏️
                    </button>
                  `;
                },
                headerSort: false,
                hozAlign: "center",
                cellClick: (e, cell) => {
                  if (e.target.classList.contains('koreguoti-eilute')) {
                    const uzeId = e.target.getAttribute('data-id');
                    this.loadEilute(uzeId); // Kviesim metodą
                  }
                },
              }
          ],
        });
    
        fetch(`/uzsakymai/uzsakymo-eilutes/${uzsId}`)
          .then(response => response.json())
          .then(data => {
            if (data.length > 0) {
              table.setData(data);
              tableContainer.style.display = 'block';
            }
          })
          .catch(error => {
            console.error('Klaida kraunant lentelę:', error);
          });
      }

      /*initMaterialSelect() {
        if (this.materialTomSelect) {
            this.materialTomSelect.destroy();
        }
    
        this.materialTomSelect = new TomSelect(this.materialSelectTarget, {
            valueField: "id",
            labelField: "text",
            searchField: "text",
            load: async (query, callback) => {
                if (query.length < 2) return callback();
                try {
                    const response = await fetch(`/uzsakymai/medziagos-paieska?q=${query}`);
                    const data = await response.json();
                    callback(data);
                } catch (error) {
                    callback();
                }
            },
            placeholder: "Įveskite bent 2 simbolius...",
            maxOptions: 20
        });
    
        // Blur validacija – perrašyk naudodamas saugomą instance
        this.materialTomSelect.on('blur', () => {
            const wrapper = this.materialSelectTarget.parentElement.querySelector('.ts-wrapper');
    
            if (this.materialTomSelect.getValue()) {
                this.materialSelectTarget.classList.remove('is-invalid');
                wrapper?.classList.remove('is-invalid');
    
                this.materialSelectTarget.classList.add('is-valid');
                wrapper?.classList.add('is-valid');
            } else {
                this.materialSelectTarget.classList.remove('is-valid');
                wrapper?.classList.remove('is-valid');
    
                this.materialSelectTarget.classList.add('is-invalid');
                wrapper?.classList.add('is-invalid');
            }
        });
    }*/

    populateTable(data) {
        this.tableBodyTarget.innerHTML = ""; // Išvalome esamus duomenis

        if (data.length === 0) {
            this.tableContainerTarget.classList.add("d-none"); // Paslepia lentelę, jei nėra duomenų
            return;
        }

        this.tableContainerTarget.classList.remove("d-none");

        data.forEach(row => {
            const checked = row.gfa_id !== null ? "checked" : "";
            const tr = document.createElement("tr");

            tr.innerHTML = `
                <td>${row.gfk_atributas}</td>
                <td>${row.gfk_tipas}</td>
                <td>
                    <input type="hidden" name="gfk_id" value="${row.gfk_id}">
                    <input type="hidden" name="gfa_id" value="${row.gfa_id !== null ? row.gfa_id : ''}">
                    <input type="checkbox" class="form-check-input" data-id="${row.gfk_id}" ${checked}>
                </td>
            `;

            // Jei checkbox pažymėtas, dažome eilutę
            if (checked) {
                tr.classList.add("table-primary");
            }

            // Pridėti įvykį kiekvienam checkbox
            tr.querySelector("input[type='checkbox']").addEventListener("change", (event) => {
                this.toggleRowColor(event.target);
            });

            this.tableBodyTarget.appendChild(tr);
        });
    }

    toggleRowColor(checkbox) {
        const row = checkbox.closest("tr");
        if (checkbox.checked) {
            row.classList.add("table-primary"); // Prideda mėlyną spalvą, jei pažymėta
        } else {
            row.classList.remove("table-primary"); // Nuima spalvą, jei atžymėta
        }
    }

    async saveAttributes(event) {
        event.preventDefault(); // Apsauga nuo dvigubo vykdymo

        const gaminysId = this.selectTarget.value;
        const rows = this.tableBodyTarget.querySelectorAll("tr");

        let formData = {
            gaminys_id: gaminysId,
            atributai: []
        };

        rows.forEach(row => {
            const gfkId = row.querySelector("input[name='gfk_id']").value;
            const gfaIdInput = row.querySelector("input[name='gfa_id']");
            const isChecked = row.querySelector("input[type='checkbox']").checked;
            const gfaId = gfaIdInput.value !== "" ? gfaIdInput.value : null;

            formData.atributai.push({
                gfk_id: gfkId,
                gfa_id: gfaId,
                checked: isChecked
            });
        });

        console.log("✅ Siunčiama tik viena užklausa į serverį:", formData);

        try {
           
            const response = await fetch("/formos/atributai/issaugoti", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Nežinoma klaida.");
            }

            this.showMessage(data.message, "success");

            
            this.loadAttributes(); // Perkrauna lentelę

        } catch (error) {
            console.error("❌ Klaida išsaugant duomenis:", error);
            this.showMessage(error.message, "danger");
        }
    }

    setupWidthField() {
        const widthField = this.element.querySelector('#width');
        if (!widthField) return;
        widthField.addEventListener('mouseenter', async () => {
            const gaminioTipasId = this.gamtipasSelectTarget.value;
            if (!gaminioTipasId) return;
            try {
                const response = await fetch(`/gaminio-plotis/${gaminioTipasId}`);
                const data = await response.json();
                
                if (data && data.minWidth && data.maxWidth) {
                    // Gauti minWidth ir maxWidth
                    this.minWidth = data.minWidth;
                    this.maxWidth = data.maxWidth;            

                    widthField.setAttribute('data-min-width', this.minWidth);
                    widthField.setAttribute('data-max-width', this.maxWidth);
                    
                    console.log('Gauti minWidth ir maxWidth:', this.minWidth, this.maxWidth);
                }
            } catch (error) {
                console.error('Klaida užkraunant duomenis:', error);
            }
        });
    }
    
    

    

    showMessage(message, type) {
        let alertContainer = document.getElementById("alert-container");
        if (!alertContainer) {
            alertContainer = document.createElement("div");
            alertContainer.id = "alert-container";
            document.body.prepend(alertContainer);
        }

        alertContainer.textContent = message;
        alertContainer.className = `alert alert-${type} mt-3`;
        alertContainer.classList.remove("d-none");

        // Paslepia pranešimą po 3 sekundžių
        setTimeout(() => {
            alertContainer.classList.add("d-none");
        }, 3000);
    }

    clearFields() {
        console.log("🧹 Valome laukus...");
        
           
        // Išvalome gaminio spalvą
        if (this.hasColorSelectTarget) {
            this.colorSelectTarget.innerHTML = '<option value="" selected disabled>Pasirinkite...</option>';
        }
    
        // Išvalome medžiagą ir atstatome TomSelect
       /* if (this.hasMaterialSelectTarget) {
            console.log("🧹 Valome medžiagos lauką...");
    
            if (this.materialSelectTarget.tomselect) {
                this.materialSelectTarget.tomselect.clear();
                this.materialSelectTarget.tomselect.clearOptions();
            }
    
            // Nustatome pradinę būseną
            this.materialSelectTarget.innerHTML = '<option value="" selected disabled>Pasirinkite...</option>';
            this.initMaterialSelect();
        }*/
    }

    issaugoti(event) {
        let valid = true;

    // Surandam visus matomus input, select, textarea laukus
    const inputs = this.element.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        if (input.offsetParent === null || !input.required) 
            return;

        // Paleidžiam jau egzistuojančią tavo funkciją
       
        if (input.name === 'medzwidth') {
            validateMedzwidth(input);
        } else if (input.name === 'width') {
            validateWidth(input);
        } else {
            validateinput(input);
        }

        // Jei kažkuris laukas turi klasę is-invalid – validacija bloga
        if (input.classList.contains('is-invalid')) {
            valid = false;
        }
    });

    // Papildomai tikrinam specialius atvejus (pvz. medžiagos input)
    const materialInput = document.getElementById('materialInput');
    if (materialInput && materialInput.offsetParent !== null) {
        validateinput(materialInput);
        if (materialInput.classList.contains('is-invalid')) {
            valid = false;
        }
    }

    if (!valid) {
        event.preventDefault();
        event.stopPropagation();
        alert('Forma turi klaidų. Patikrinkite laukus.');
        return;
    }







    
    
        const gam_id = document.getElementById('gam_id')?.value;
        const mechanism_id = document.getElementById('mechanism_id')?.value;
    
        let papildomiDuomenys = {}
        /*this.fieldsTarget.querySelectorAll('input, select, textarea').forEach(el => {
            papildomiDuomenys[el.name] = el.value
        });*/

        this.fieldsTarget.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.type === 'radio') {
                // Tik jei pirmą kartą matom tokį name, priskiriam reikšmę
                if (!(el.name in papildomiDuomenys)) {
                    const checked = this.element.querySelector(`input[name="${el.name}"]:checked`);
                    papildomiDuomenys[el.name] = checked ? checked.value : '';
                }
            } else {
                papildomiDuomenys[el.name] = el.value;
            }
        });

    
        const uzsIdInput = this.element.querySelector('#uzs_id');
        const uzeIdInput = this.element.querySelector('#uze_id');
    
        const data = {
            gam_id: gam_id,
            mechanism_id: mechanism_id,
            uzs_id: this.uzsIdTarget.value,
            uze_id: this.uzeIdTarget.value,
            ...papildomiDuomenys
        }
    
        fetch('/uzsakymai/issaugoti', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) throw new Error("Tinklo klaida");
            return response.json();
        })
        .then(json => {
            if (uzsIdInput && json.uzs_Id) uzsIdInput.value = json.uzs_Id;
            if (uzeIdInput && json.uze_Id) uzeIdInput.value = json.uze_Id;
    
            if (json.uzs_Id) {   
                this.loadTable(json.uzs_Id);
            }
            alert(json.message || 'Užsakymas sėkmingai pateiktas!');
        })
        .catch(error => {
            console.error('Klaida:', error);
            alert('Įvyko klaida pateikiant užsakymą.');
        });
    }
    
    

    pasalinti(event) {
        event.preventDefault();
    
        const uzsId = this.uzsIdTarget.value;
        const uzeId = this.uzeIdTarget.value;
    
        if (!uzeId || !uzsId) {
            alert("Trūksta ID reikšmių!");
            return;
        }
    
        if (!confirm("Ar tikrai norite pašalinti šią eilutę?")) {
            return;
        }
    
        fetch('/uzsakymai/uzsakymo-eilutes/salinti', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                uze_id: uzeId,
                uzs_id: uzsId
            })
        })
        .then(response => {
            if (!response.ok) throw new Error("Tinklo klaida");
            return response.json();
        })
        .then(json => {
            if (json.success) {
                alert(json.message || "Eilutė pašalinta!");
    
                // Perkraunam lentelę
                if (json.uzs_Id) {
                    this.loadTable(json.uzs_Id);
                }
    
                // Išvalom formos laukus
                this.uzeIdTarget.value = "";
    
                // Papildomai gali išvalyti kitus laukus jei reikia
            } else {
                alert(json.message || "Nepavyko pašalinti įrašo!");
            }
        })
        .catch(error => {
            console.error("❌ Klaida šalinant įrašą:", error);
            alert("Įvyko klaida šalinant įrašą.");
        });
    }

    loadEilute(uzeId) {
        fetch(`/uzsakymai/uzsakymo-eilutes/redaguoti/${uzeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const eilute = data.data;
    
                    // Užpildom ID laukus
                    this.element.querySelector('#uze_id').value = eilute.uze_id;
                    this.element.querySelector('#gam_id').value = eilute.gaminys_id;
    
                    // Užkraunam gaminio tipus
                    this.updateTypes({ target: { value: eilute.gaminys_id } });
    
                    // Užkraunam mechanizmą su delay
                    setTimeout(() => {
                        this.gamtipasSelectTarget.value = eilute.mechanism_id;
                        const event = new Event('change', { bubbles: true });
                        this.gamtipasSelectTarget.dispatchEvent(event);
                    }, 200);
    
                    // Užklausome laukų
                    const laukaiHandler = (e) => {
                        const atitraukimas = this.element.querySelector('#atitraukimas');
                        if (atitraukimas) atitraukimas.value = eilute.uze_atitraukimo_kaladele;
    
                        const vyriai = this.element.querySelector('#vyriai');
                        if (vyriai) vyriai.value = eilute.uze_vyriai;
    
                        const productColor = this.element.querySelector('#productColor');
                        if (productColor) productColor.value = eilute.uze_gaminio_spalva_id;
    
                        const materialInput = document.querySelector('#materialInput');
                        if (materialInput) {
                            materialInput.dispatchEvent(new CustomEvent('nustatytiReiksme', {
                                detail: {
                                    value: eilute.uze_lameliu_spalva_id,
                                    text: eilute.uze_lameles_spalva
                                },
                                bubbles: true
                            }));
                        }
    
                        const width = this.element.querySelector('#width');
                        if (width) {
                            width.value = eilute.uze_gaminio_plotis;
    
                            if (eilute.min_width) {
                                width.setAttribute('data-min-width', parseInt(eilute.min_width));
                            }
                            if (eilute.max_warranty_width) {
                                width.setAttribute('data-max-width', parseInt(eilute.max_warranty_width));
                            }
                        }
    
                        const heigth = this.element.querySelector('#heigth');
                        if (heigth) heigth.value = eilute.uze_gaminio_aukstis;
    
                        const medzwidth = this.element.querySelector('#medzwidth');
                        if (medzwidth) medzwidth.value = eilute.uze_medziagos_plotis;
    
                        const medzheigth = this.element.querySelector('#medzheigth');
                        if (medzheigth) medzheigth.value = eilute.uze_medziagos_aukstis;
    
                        const valdymas = this.element.querySelector('#valdymas');
                        if (valdymas) valdymas.value = eilute.uze_valdymas_puse;
    
                        const stabdymas = this.element.querySelector('#stabdymas');
                        if (stabdymas) stabdymas.value = eilute.uze_stabdymo_mechanizmas;
    
                        const comments = this.element.querySelector('#comments');
                        if (comments) comments.value = eilute.uze_komentarai_gamybai ?? '';
    
                        // ✅ Gam. pločio sutikimo checkbox pažymėjimas
                        const agree = document.getElementById('agree');
                        const disagree = document.getElementById('disagree');
                        const widthWarning = document.getElementById('widthWarning');
    
                        if (eilute.uze_gam_plocio_sutikimas === 'T') {
                            if (agree) agree.checked = true;
                            if (widthWarning) widthWarning.classList.remove('d-none');
                        } else if (eilute.uze_gam_plocio_sutikimas === 'N') {
                            if (disagree) disagree.checked = true;
                            if (widthWarning) widthWarning.classList.remove('d-none');
                        }
    
                        this.element.removeEventListener('laukaiLoaded', laukaiHandler);
                    };
    
                    this.element.addEventListener('laukaiLoaded', laukaiHandler);
    
                } else {
                    alert('Nepavyko gauti duomenų.');
                }
            })
            .catch(error => {
                console.error('Klaida gaunant eilutę:', error);
            });
    }
    

   
}
