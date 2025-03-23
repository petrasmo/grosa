import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["select"];

  connect() {
    this.initTomSelect();
    this.selectTarget.addEventListener('nustatytiReiksme', (e) => {
        const { value, text } = e.detail;
        this.setValue(value, text);
    });
  }

  initTomSelect() {
    if (this.selectTarget.tomselect) {
        return;
    }

    const url = this.selectTarget.dataset.url;

    this.selectTarget.tomselect = new TomSelect(this.selectTarget, {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        create: false,
        maxOptions: 20,
        load: async (query, callback) => {
            if (query.length < 2) return callback();
            try {
                const response = await fetch(`${url}?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                callback(data);
            } catch (error) {
                console.error("❌ Klaida kraunant duomenis:", error);
                callback();
            }
        }
    });
    }


    setValue(value, text) {
        const instance = this.selectTarget.tomselect;
        if (!instance) {
            console.error("❌ TomSelect nėra inicializuotas");
            return;
        }
    
        // Patikrinam ar option jau yra DOM'e
        let existingOption = Array.from(this.selectTarget.options).find(opt => opt.value == value);
        if (!existingOption) {
            const option = document.createElement("option");
            option.value = value;
            option.text = text;
            this.selectTarget.appendChild(option);
        }
    
        // Pridedam į TomSelect instanciją
        instance.addOption({ value: value, text: text });
        instance.refreshOptions(false);
    
        // Nustatom reikšmę
        instance.setValue(value);
    
        // PRIVALOMA: užtikrinam, kad vizualiai būtų parodytas pasirinkimas
        instance.updateOriginalInput();
        instance.control_input.value = text;
    
        // Vizualiai aktyvuojam
        instance.wrapper.classList.add('has-items');
    
        console.log(`✅ Nustatyta: ${value} - ${text}`);
    }


  triggerSetValue(selector, value, text) {
    const event = new CustomEvent('nustatytiReiksme', {
      detail: { value: value, text: text },
      bubbles: true
    });
    document.querySelector(selector).dispatchEvent(event);
  }
  
}