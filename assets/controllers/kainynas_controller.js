import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["materialInput", "productInput", "typeInput", "colorInput", "table"];

    connect() {
        console.log("✅ Kainyno paieškos valdiklis prijungtas");
    
        this.dataType = this.element.dataset.type; // <-- Pridedam data-type tikrinimą
    
        if (this.dataType === 'kainynas') {
            this.setupKainynas();    // Sukuria lentelę kainynui
        } else if (this.dataType === 'kainuTaisykles') {
            this.setupKainuTaisykles();  // Sukuria lentelę kainų taisyklėms
        } else {
            console.error('❌ Nežinomas data-type:', this.dataType);
        }
    
        this.search(); // Iš karto paleidžia paiešką pagal tai kas nustatyta
        this.setupInputListeners(); // Paleidžia stebėjimą, kai keičiasi input'ai
    }

    setupInputListeners() {
        this.tableTarget.addEventListener('blur', (event) => {
            if (event.target.classList.contains('kaina-input')) {
                const originalValue = parseFloat(event.target.defaultValue).toFixed(2); // Pradinė reikšmė
                let currentValue = event.target.value.trim();
    
                if (currentValue !== '') {
                    currentValue = parseFloat(currentValue).toFixed(2); // Dabartinė reikšmė su 2 skaičiais
    
                    if (!isNaN(currentValue)) {
                        event.target.value = currentValue; // Sutvarkom vaizdą
    
                        if (currentValue !== originalValue) {
                            // 🔵 Tik jei reikšmė pasikeitė – pridedam klasę
                            event.target.classList.add('kaina-modified');
                        } else {
                            // 🔵 Jei reikšmė nepasikeitė – pašalinam mėlyną
                            event.target.classList.remove('kaina-modified');
                        }
                    }
                }
            }
        }, true);
    }
    async search() {
        const gaminys = this.productInputTarget?.value.trim() || '';
        const tipas = this.typeInputTarget?.value.trim() || '';
        const spalva = this.colorInputTarget?.value.trim() || '';
        const medziaga = this.materialInputTarget?.value.trim() || '';
    
        const params = new URLSearchParams({
            gaminys: gaminys,
            tipas: tipas,
            spalva: spalva,
            medziaga: medziaga
        });
    
        // Pasirenkam URL pagal this.dataType
        const url = (this.dataType === 'kainynas')
            ? `/kainynas/ieskoti`
            : `/kainynas/kainu-taisykles/ieskoti`;
    
        try {
            const response = await fetch(`${url}?${params.toString()}`);
            const data = await response.json();
    
            if (this.dataTable) {
                this.dataTable.clear();
                this.dataTable.rows.add(data);
                this.dataTable.draw();
            }
        } catch (error) {
            console.error('❌ Klaida ieškant:', error);
        }
    }

    async issaugotiKainas() {
        const pakeistosKainos = [];
    
        this.tableTarget.querySelectorAll('tbody input.kaina-input').forEach(input => {
            const kaiId = input.getAttribute('data-id');
            const kaina = input.value.trim();
    
            if (input.classList.contains('kaina-modified')) {
                pakeistosKainos.push({
                    kai_id: kaiId,
                    kaina: kaina
                });
            }
        });

        //console.log('Pakeistos kainos:', pakeistosKainos);
    
        if (pakeistosKainos.length === 0) {
            window.showMessage('⚠️ Nėra pakeistų kainų.', 'warning');
            return;
        }
    
        try {
            const response = await fetch('/kainynas/issaugoti', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ kainos: pakeistosKainos })
            });
    
            const result = await response.json();
    
            if (result.success) {
                window.showMessage(result.message, 'success'); // 🛠️ Naudojam iš serverio!
                
                // Išvalom pažymėjimą
                this.tableTarget.querySelectorAll('input.kaina-input').forEach(input => {
                    input.defaultValue = input.value;
                    input.classList.remove('kaina-modified');
                });                
            } else {
                window.showMessage(result.message || '❌ Klaida išsaugant.', 'danger');
            }
        } catch (error) {
            console.error('❌ Klaida siunčiant duomenis:', error);
            window.showMessage('❌ Klaida siunčiant duomenis.', 'danger');
        }
    }

    setupKainynas() {
        this.dataTable = new DataTable(this.tableTarget, {
            paging: true,
            searching: false,
            ordering: true,
            pageLength: 25,
            lengthChange: false,
            data: [],
            columns: [
                { data: 'gaminys' },
                { data: 'gaminio_tipas' },
                { data: 'spalva' },
                { data: 'medziaga' },
                { data: 'kai_roller_width' },
                {
                    data: 'kai_kaina_su_pvm',
                    render: function(data, type, row) {
                        return `
                            <input 
                                type="text" 
                                class="form-control form-control-sm kaina-input" 
                                value="${parseFloat(data).toFixed(2)}" 
                                data-id="${row.kai_id}" 
                                style="width: 100px; font-weight: bold; color: black;" 
                                inputmode="decimal"
                            />
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <a href="/kainynas/${data.kai_id}" class="btn btn-sm btn-outline-secondary">✏️</a>
                            <button class="btn btn-sm btn-outline-danger salinti-uzsakyma" data-id="${data.kai_id}">🗑️</button>
                        `;
                    }
                }
            ]
        });
    }
    setupKainuTaisykles() {
        this.dataTable = new DataTable(this.tableTarget, {
            paging: true,
            searching: false,
            ordering: true,
            pageLength: 10,
            lengthChange: false,
            data: [],
            columns: [
                { data: 'gaminys' },
                { data: 'gaminio_tipas' },
                { data: 'spalva' },
                { data: 'medziaga' },
                { data: 'kat_kaina' },
                { data: 'kat_matavimo_vienetas' },
                { data: 'kat_aprasymas' },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-outline-secondary" disabled>✏️</button>
                            <button class="btn btn-sm btn-outline-danger" disabled>🗑️</button>
                        `;
                    }
                }
            ]
        });
    }

}
