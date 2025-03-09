import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["form"];

    connect() {
        console.log("OrderFormController connected"); // Debug: patikrina, ar valdiklis įkeltas
        this.formTarget.classList.add("d-none"); // Užtikriname, kad forma pradžioje būtų paslėpta
    }

    toggle(event) {
        event.preventDefault();
        console.log("Toggle function called"); // Debug: patikrina, ar funkcija iškviečiama

        let button = event.currentTarget;
        let rect = button.getBoundingClientRect();

        this.formTarget.classList.toggle("d-none");

        // Nustatome formelės poziciją
        this.formTarget.style.position = "absolute";
        this.formTarget.style.top = `${rect.bottom + window.scrollY}px`;
        this.formTarget.style.left = `${rect.left + window.scrollX}px`;

        console.log("Form visibility toggled"); // Debug: patikrina, ar forma pasirodė
    }
}
