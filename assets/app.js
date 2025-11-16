import './bootstrap.js';
import './styles/app.css';

import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

document.addEventListener("DOMContentLoaded", () => {
  // Petit délai pour s'assurer que le DOM Symfony est chargé
  setTimeout(() => {
    const pickers = document.querySelectorAll('.datetimepicker');
    if (!pickers.length) {
      console.warn("⚠️ Aucun champ .datetimepicker trouvé !");
      return;
    }

    

    flatpickr(pickers, {
      enableTime: true,
      dateFormat: "d/m/Y H:i",
      time_24hr: true,
      minDate: "today",
      minuteIncrement: 15,
      disableMobile: true,
      locale: {
        firstDayOfWeek: 1,
        weekdays: {
          shorthand: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
          longhand: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
        },
        months: {
          shorthand: ['Janv', 'Févr', 'Mars', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'],
          longhand: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
        },
      },
    });

    console.log("✅ Flatpickr attaché sur", pickers.length, "champ(s).");
  }, 300); // léger délai pour être sûr que le formulaire est injecté
});
