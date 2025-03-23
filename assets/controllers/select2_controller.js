import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["input", "list", "spinner"];
  static values = { mechanismId: Number };

  initialize() {
    // Pririšam handlerius
    this.nustatytiReiksmeHandler = this.nustatytiReiksme.bind(this);
    this.outsideClickHandler = this.outsideClick.bind(this);
  }

  connect() {
    console.log("✅ SimpleAutocomplete prijungtas");

    // Input įvedimo listeneris
    this.inputTarget.addEventListener('input', this.onSearch.bind(this));

    // Custom event klausytojas reikšmei nustatyti
    this.inputTarget.addEventListener('nustatytiReiksme', this.nustatytiReiksmeHandler);

    // Click'ai už lauko ribų
    document.addEventListener('click', this.outsideClickHandler);
  }

  disconnect() {
    // Atlaisvinam event'us
    this.inputTarget.removeEventListener('nustatytiReiksme', this.nustatytiReiksmeHandler);
    document.removeEventListener('click', this.outsideClickHandler);
  }

  async onSearch(event) {
    const query = event.target.value.trim();
    const url = this.inputTarget.dataset.url;
    const mechanismId = this.mechanismIdValue;
    console.log("🔍 Vartotojo įvestis:", query);
  console.log("🛠️ Perdavimo mechanismId:", mechanismId);

    // Jei mažiau nei 2 simboliai – slepiam dropdown
    if (query.length < 2) {
      this.clearList();
      return;
    }

    // Rodom loaderį
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
          li.addEventListener('click', () => this.setValue(item.id, item.text));
          this.listTarget.appendChild(li);
        });
      }

      // Rodyti dropdown
      this.listTarget.classList.remove('d-none');

    } catch (error) {
      console.error("❌ Klaida:", error);
    } finally {
      // Paslėpti loaderį
      this.spinnerTarget.classList.add('d-none');
    }
  }

  setValue(value, text) {
    // Nustatom reikšmę
    this.inputTarget.value = text;
    this.clearList();
    console.log(`✅ Pasirinkta reikšmė: ${value} - ${text}`);
  }

  // Gaunam reikšmę iš kito kontrolerio
  nustatytiReiksme(event) {
    const { value, text } = event.detail;
    this.setValue(value, text);
  }

  // Kai paspaudžia kitur – išvalom dropdown ir input, jei nieko nepasirinko
  outsideClick(event) {
    if (!this.element.contains(event.target)) {
      this.clearList();
      this.inputTarget.value = '';
    }
  }

  // Paprastas metodas dropdown'o išvalymui
  clearList() {
    this.listTarget.classList.add('d-none');
    this.listTarget.innerHTML = '';
  }
}
