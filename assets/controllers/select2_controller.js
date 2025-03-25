import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["input", "list", "spinner"];
  static values = { mechanismId: Number };

  connect() {
    console.log("✅ SimpleAutocomplete prijungtas");

    this.inputTarget.addEventListener('input', this.onSearch.bind(this));
    this.inputTarget.addEventListener('nustatytiReiksme', this.nustatytiReiksme.bind(this));
    document.addEventListener('click', this.outsideClick.bind(this));
  }

  disconnect() {
    document.removeEventListener('click', this.outsideClick.bind(this));
  }

  async onSearch(event) {
    const query = event.target.value.trim();
    const url = this.inputTarget.dataset.url;
    const mechanismId = this.mechanismIdValue;

    if (query.length < 2) {
      this.clearList();
      return;
    }

    this.spinnerTarget.classList.remove('d-none');

    try {
      const response = await fetch(`${url}?q=${encodeURIComponent(query)}&mechanism_id=${mechanismId}`);
      const data = await response.json();

      this.listTarget.innerHTML = '';

      if (data.length === 0) {
        const li = document.createElement('li');
        li.classList.add('list-group-item', 'disabled');
        li.textContent = 'Nerasta';
        this.listTarget.appendChild(li);
      } else {
        data.forEach(item => {
          const li = document.createElement('li');
          li.classList.add('list-group-item', 'list-group-item-action');
          li.textContent = item.text;
          li.dataset.value = item.id;

          li.addEventListener('click', (event) => {
            event.stopPropagation();
            this.setValue(item.id, item.text);
          });

          this.listTarget.appendChild(li);
        });
      }

      this.listTarget.classList.remove('d-none');

    } catch (error) {
      console.error("❌ Klaida:", error);
    } finally {
      this.spinnerTarget.classList.add('d-none');
    }
  }

  setValue(value, text) {
    this.inputTarget.value = text;
    // Užpildom hidden lauką
    const hiddenInput = document.getElementById('materialId');
    if (hiddenInput) {
      hiddenInput.value = value;
    }
    this.clearList();
    console.log(`✅ Pasirinkta reikšmė: ${value} - ${text}`);
  }

  nustatytiReiksme(event) {
    const { value, text } = event.detail;
    this.setValue(value, text);
  }

  outsideClick(event) {
    if (!this.element.contains(event.target)) {
      this.clearList();
    }
  }

  clearList() {
    this.listTarget.classList.add('d-none');
    this.listTarget.innerHTML = '';
  }
}
