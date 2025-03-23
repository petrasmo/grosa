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

    this.selectTarget.tomselect = new TomSelect(this.selectTarget, {
      valueField: 'id',
      labelField: 'text',
      searchField: 'text',
      create: false, // kol kas nenaudojam kūrimo
      maxOptions: 20
    });
    
}

  setValue(value, text) {
    const instance = this.selectTarget.tomselect;
    if (!instance) {
      console.error("❌ TomSelect nėra inicializuotas");
      return;
    }
  
    // Pridedam naują pasirinkimą į TomSelect (vidinį)
    instance.addOption({ value: value, text: text });
    console.log(`➕ Pridėta option: { value: ${value}, text: ${text} }`);
  
    // Refreshinam options dropdowną (būtina po addOption)
    instance.refreshOptions(false);
  
    // Nustatom reikšmę
    instance.setValue(value);
  
    // Papildomai, jei nori, kad į textbox vizualiai įrašytų tekstą
    instance.setTextboxValue(text);
  
    console.log(`✅ Reikšmė nustatyta: ${value} - ${text}`);
  }

  triggerSetValue(selector, value, text) {
    const event = new CustomEvent('nustatytiReiksme', {
      detail: { value: value, text: text },
      bubbles: true
    });
    document.querySelector(selector).dispatchEvent(event);
  }
  
}