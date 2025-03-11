import { Controller } from "@hotwired/stimulus";
import TomSelect from "tom-select";

export default class extends Controller {
    static targets = ["select", "tableBody", "tableContainer", "gamtipas", "gamtipasSelect", 
        "colorSelect","materialSelect"];
   
    connect() {
        if (this.element.dataset.initialized) {
           // console.warn("⚠️ Atributai valdiklis jau buvo prijungtas!");
            return;
        }
        this.element.dataset.initialized = "true";
        this.initMaterialSelect();
        //console.log("✅ Atributai valdiklis prijungtas!");
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

    async updateTypes(event) {
        const gamId = event.target.value;
        console.log("🔄 Pasirinktas gaminys ID:", gamId);
        if (!gamId) return;
    
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

    initMaterialSelect() {
        if (this.materialSelectTarget.tomselect) {
            this.materialSelectTarget.tomselect.destroy();
        }

        new TomSelect(this.materialSelectTarget, {
            valueField: "id",
            labelField: "text",
            searchField: "text",
            load: async (query, callback) => {
                if (query.length < 2) {
                    return callback();
                }

                try {
                    const response = await fetch(`/medziagos-paieska?q=${query}`);
                    const data = await response.json();
                    callback(data);
                } catch (error) {
                    console.error("Klaida kraunant medžiagas:", error);
                    callback();
                }
            },
            placeholder: "Įveskite bent 2 simbolius...",
            maxOptions: 20
        });
    }

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
}
