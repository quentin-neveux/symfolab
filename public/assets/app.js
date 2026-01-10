/********************************************************************
 *  ECORIDE — APP.JS (reconstruit & stabilisé)
 ********************************************************************/

document.addEventListener("DOMContentLoaded", () => {

/* ============================================================
   SEARCHBAR — PASSAGERS (UX type BlaBlaCar)
============================================================ */
(() => {
  const field = document.getElementById('passengers-field');
  if (!field) return;

  const toggle = document.getElementById('passengers-toggle');
  const dropdown = document.getElementById('passengers-dropdown');
  const label = document.getElementById('passengers-label');
  const countEl = document.getElementById('passengers-count');
  const input = document.getElementById('nb-passagers-input');
  const plus = document.getElementById('passengers-plus');
  const minus = document.getElementById('passengers-minus');

  if (!toggle || !dropdown || !label || !countEl || !input || !plus || !minus) return;

  let count = 1;
  const MIN = 1;
  const MAX = 5;

  const update = () => {
    countEl.textContent = count;
    label.textContent = count === 1
      ? '1 passager'
      : `${count} passagers`;
    input.value = count;
  };

  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    dropdown.classList.toggle('d-none');
  });

  plus.addEventListener('click', () => {
    if (count < MAX) {
      count++;
      update();
    }
  });

  minus.addEventListener('click', () => {
    if (count > MIN) {
      count--;
      update();
    }
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#passengers-field')) {
      dropdown.classList.add('d-none');
    }
  });

  // fermeture au submit (optionnel mais clean)
  const form = field.closest('form');
  form?.addEventListener('submit', () => {
    dropdown.classList.add('d-none');
  });

  update();
})();
});

document.addEventListener("DOMContentLoaded", () => {
  /* ============================================================
     0) OUTILS (petits helpers)
  ============================================================ */
  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const on = (eventName, selector, handler, root = document) => {
    root.addEventListener(eventName, (e) => {
      const target = e.target.closest(selector);
      if (!target) return;
      handler(e, target);
    });
  };

  const debounce = (fn, delay = 180) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  };

  /* ============================================================
     1) MENU MOBILE / BURGER (EcoRide)
     - supporte #burgerButton, #mobileMenu, #closeMenu
  ============================================================ */
  (() => {
    const burger = $("#burgerButton");
    const menu = $("#mobileMenu");
    const closeBtn = $("#closeMenu");

    if (!burger || !menu) return;

    const openMenu = () => {
      menu.classList.add("active");
      document.body.classList.add("no-scroll");
    };
    const closeMenu = () => {
      menu.classList.remove("active");
      document.body.classList.remove("no-scroll");
    };

    burger.addEventListener("click", openMenu);
    closeBtn?.addEventListener("click", closeMenu);

    // clic hors du panneau
    menu.addEventListener("click", (e) => {
      if (e.target === menu) closeMenu();
    });

    // ESC
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && menu.classList.contains("active")) {
        closeMenu();
      }
    });
  })();

  /* ============================================================
     2) LIGNES DE TABLE CLIQUABLES (Historique / Listes)
     - .trajet-clickable[data-href]
     - protège .no-row-click
  ============================================================ */
  (() => {
    document.addEventListener(
      "click",
      (e) => {
        const row = e.target.closest(".trajet-clickable[data-href]");
        if (!row) return;

        // Si clic sur un bouton / lien / action → on laisse faire
        if (e.target.closest(".no-row-click, a, button, form")) return;

        const url = row.dataset.href;
        if (url) {
          globalThis.location.href = url;
        }
      },
      true // capture pour être robuste face à Bootstrap
    );
  })();

  /* ============================================================
     2bis) ONGLET HISTORIQUE (À venir / En cours / Passés) ✅ AJOUT
     - Boutons: [data-histo-tab="avenir|encours|passes"]
     - Sections: #histo-avenir, #histo-encours, #histo-passes
     - Affichage via classe Bootstrap: .d-none
  ============================================================ */
  (() => {
    const tabButtons = $$("[data-histo-tab]");
    if (!tabButtons.length) return;

    const sections = {
      avenir: $("#histo-avenir"),
      encours: $("#histo-encours"),
      passes: $("#histo-passes"),
    };

    // si les sections n'existent pas, inutile
    if (!sections.avenir && !sections.encours && !sections.passes) return;

    const setActive = (key) => {
      // active bouton
      tabButtons.forEach((btn) => {
        btn.classList.toggle("active", btn.dataset.histoTab === key);
      });

      // toggle sections
      Object.entries(sections).forEach(([k, el]) => {
        if (!el) return;
        el.classList.toggle("d-none", k !== key);
      });
    };

    tabButtons.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        setActive(btn.dataset.histoTab);
      });
    });

    // init: si un bouton est déjà .active, on le respecte, sinon "avenir"
    const initial =
      tabButtons.find((b) => b.classList.contains("active"))?.dataset.histoTab || "avenir";
    setActive(initial);
  })();

  /* ============================================================
     3) MASQUAGE / AFFICHAGE DU TÉLÉPHONE
     - .phone-mask__btn[data-phone]
  ============================================================ */
  (() => {
    on("click", ".phone-mask__btn", (e, btn) => {
      const phone = btn.dataset.phone;
      if (!phone) return;

      btn.classList.toggle("revealed");
      btn.textContent = btn.classList.contains("revealed")
        ? phone
        : "Afficher le numéro";
    });
  })();

  /* ============================================================
     4) FILTRE VÉHICULE (desktop)
     - #vehicleFilterToggle, #vehicleFilterMenu, #vehicleFilterArrow
  ============================================================ */
  (() => {
    const toggle = $("#vehicleFilterToggle");
    const menu = $("#vehicleFilterMenu");
    const arrow = $("#vehicleFilterArrow");

    if (!toggle || !menu) return;

    const close = () => {
      menu.style.display = "none";
      if (arrow) arrow.style.transform = "rotate(0deg)";
    };

    toggle.addEventListener("click", (e) => {
      e.stopPropagation();
      const open = menu.style.display === "block";
      menu.style.display = open ? "none" : "block";
      if (arrow) arrow.style.transform = open ? "rotate(0deg)" : "rotate(180deg)";
    });

    document.addEventListener("click", (e) => {
      if (!menu.contains(e.target) && !toggle.contains(e.target)) close();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") close();
    });
  })();

  /* ============================================================
     5) TOGGLE PASSWORD (connexion / inscription / reset)
     - bouton .toggle-password[data-target="idInput"]
     - icône <i> (bootstrap icons)
  ============================================================ */
  (() => {
    $$(".toggle-password").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.dataset.target;
        if (!id) return;

        const input = document.getElementById(id);
        if (!input) return;

        input.type = input.type === "password" ? "text" : "password";

        const icon = btn.querySelector("i");
        if (icon) {
          icon.classList.toggle("bi-eye");
          icon.classList.toggle("bi-eye-slash");
        }
      });
    });
  })();

  /* ============================================================
     6) FLASH MESSAGES (auto-disparition)
     - .flash-message
  ============================================================ */
  (() => {
    const flashes = $$(".flash-message");
    if (!flashes.length) return;

    const onFlashTransitionEnd = (e) => {
      e.target.remove();
    };

    flashes.forEach((el) => {
      setTimeout(() => {
        el.style.transition = "opacity 0.6s";
        el.style.opacity = "0";
        el.addEventListener("transitionend", onFlashTransitionEnd, { once: true });
      }, 4200);
    });
  })();

  /* ============================================================
     7) COOKIE BANNER
     - #cookie-banner, #accept-cookies, #reject-cookies
     - localStorage: cookies_accepted = "true" | "false"
  ============================================================ */
  (() => {
    const banner = $("#cookie-banner");
    if (!banner) return;

    const accept = $("#accept-cookies");
    const reject = $("#reject-cookies");

    const stored = localStorage.getItem("cookies_accepted");
    if (stored === null) banner.classList.add("show");

    const hide = () => banner.classList.remove("show");

    accept?.addEventListener("click", () => {
      localStorage.setItem("cookies_accepted", "true");
      hide();
    });

    reject?.addEventListener("click", () => {
      localStorage.setItem("cookies_accepted", "false");
      hide();
    });
  })();

  /* ============================================================
     8) AIMLAB POPUP SECRET (footer leaf)
     - #eco-secret-logo, #aimlab-popup, #close-popup
     - iframe à l'intérieur du popup
     - desktop only (>=768)
  ============================================================ */
  (() => {
    const leaf = $("#eco-secret-logo");
    const popup = $("#aimlab-popup");
    const close = $("#close-popup");

    if (!leaf || !popup || !close) return;

    const iframe = popup.querySelector("iframe");

    const open = () => {
      if (globalThis.innerWidth < 768) return;
      if (iframe) iframe.src = "/aimlab.html?v=" + Date.now();
      popup.classList.add("active");
    };

    const hide = () => {
      popup.classList.remove("active");
      if (iframe) iframe.src = "about:blank";
    };

    leaf.addEventListener("click", (e) => {
      e.preventDefault();
      open();
    });

    close.addEventListener("click", hide);

    popup.addEventListener("click", (e) => {
      if (e.target === popup) hide();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && popup.classList.contains("active")) hide();
    });
  })();

  /* ============================================================
     9) AUTOCOMPLETE VÉHICULE (marque/modèle) + AUTO-ÉNERGIE
     - Inputs:
       [data-vehicle-brand], [data-vehicle-model], [data-vehicle-energy]
     - Containers (optionnels):
       [data-brand-results], [data-model-results]
     - API:
       /api/vehicles-eu/makes
       /api/vehicles-eu/models/{make}  -> attendu: [{name, energy}] OU ["208", ...]
  ============================================================ */
  (() => {
    const brandInput = $('[data-vehicle-brand]');
    const modelInput = $('[data-vehicle-model]');
    const energyField = $('[data-vehicle-energy]');

    if (!brandInput || !modelInput) return;

    const brandBox = $('[data-brand-results]') || null;
    const modelBox = $('[data-model-results]') || null;

    // fallback si pas de box custom (on créera un dropdown simple)
    const ensureBox = (input, existingBox) => {
      if (existingBox) return existingBox;

      let box = input.parentElement.querySelector(".autocomplete-list");
      if (!box) {
        box = document.createElement("div");
        box.className = "autocomplete-list";
        input.parentElement.style.position = "relative";
        input.parentElement.appendChild(box);
      }
      return box;
    };

    const brandResults = ensureBox(brandInput, brandBox);
    const modelResults = ensureBox(modelInput, modelBox);

    const modelsCache = new Map(); // brandLower -> array
    let makesCache = null;

    const clearList = (box) => { if (box) box.innerHTML = ""; };

    const showList = (box) => { if (box) box.style.display = "block"; };
    const hideList = (box) => { if (box) box.style.display = "none"; };

    const normalizeModels = (raw) => {
      // supporte:
      // - ["208", "308"]
      // - [{name:"208", energy:"Essence"}]
      if (!Array.isArray(raw)) return [];
      if (raw.length === 0) return [];
      if (typeof raw[0] === "string") return raw.map(n => ({ name: n, energy: null }));
      return raw.map(o => ({ name: o.name ?? "", energy: o.energy ?? null })).filter(x => x.name);
    };

    const setEnergy = (energy) => {
      if (!energyField || !energy) return;
      energyField.value = energy;
      energyField.classList.add("border-success");
      setTimeout(() => energyField.classList.remove("border-success"), 650);
    };

    const fetchMakes = async () => {
      if (makesCache) return makesCache;
      const res = await fetch("/api/vehicles-eu/makes", { headers: { "Accept": "application/json" } });
      makesCache = await res.json();
      return makesCache;
    };

    const fetchModels = async (brand) => {
      const key = brand.toLowerCase();
      if (modelsCache.has(key)) return modelsCache.get(key);

      const res = await fetch(`/api/vehicles-eu/models/${encodeURIComponent(brand)}`, {
        headers: { "Accept": "application/json" }
      });
      const raw = await res.json();
      const models = normalizeModels(raw);
      modelsCache.set(key, models);
      return models;
    };

    const createAutocompleteItem = (item, onPick) => {
      const div = document.createElement("div");
      div.className = "autocomplete-item";
      div.textContent = item.label ?? item.name ?? item;
      div.addEventListener("mousedown", function (e) {
        // mousedown pour éviter blur avant click
        e.preventDefault();
        onPick(item);
      });
      return div;
    };

    const renderItems = (box, items, onPick) => {
      clearList(box);
      if (!items.length) {
        hideList(box);
        return;
      }

      const frag = document.createDocumentFragment();

      items.forEach((item) => {
        const div = createAutocompleteItem(item, onPick);
        frag.appendChild(div);
      });

      box.appendChild(frag);
      showList(box);
    };

    const filterIncludes = (arr, value, getLabel) => {
      const v = value.trim().toLowerCase();
      if (!v) return [];
      return arr
        .filter((x) => getLabel(x).toLowerCase().includes(v))
        .slice(0, 10);
    };

    const closeAll = () => {
      clearList(brandResults); hideList(brandResults);
      clearList(modelResults); hideList(modelResults);
    };

    // --- Marques ---
    const onBrandInput = debounce(async () => {
      const value = brandInput.value;
      const makes = await fetchMakes();
      const items = filterIncludes(makes, value, (x) => x);
      renderItems(
        brandResults,
        items.map(m => ({ label: m, value: m })),
        (picked) => {
          brandInput.value = picked.value;
          modelInput.value = "";
          clearList(brandResults); hideList(brandResults);
          clearList(modelResults); hideList(modelResults);
        }
      );
    }, 120);

    brandInput.addEventListener("input", onBrandInput);
    brandInput.addEventListener("focus", onBrandInput);

    // --- Modèles ---
    const onModelInput = debounce(async () => {
      const brand = brandInput.value.trim();
      const value = modelInput.value;
      if (!brand) { clearList(modelResults); hideList(modelResults); return; }

      const models = await fetchModels(brand);
      const items = filterIncludes(models, value, (x) => x.name);

      renderItems(
        modelResults,
        items.map(m => ({ label: m.name, value: m.name, energy: m.energy })),
        (picked) => {
          modelInput.value = picked.value;
          if (picked.energy) setEnergy(picked.energy);
          clearList(modelResults); hideList(modelResults);
        }
      );
    }, 120);

    modelInput.addEventListener("input", onModelInput);
    modelInput.addEventListener("focus", onModelInput);

    // auto-énergie aussi au "change" (quand user tape et valide)
    modelInput.addEventListener("change", async () => {
      const brand = brandInput.value.trim();
      const modelName = modelInput.value.trim();
      if (!brand || !modelName) return;

      const models = await fetchModels(brand);
      const found = models.find(m => m.name.toLowerCase() === modelName.toLowerCase());
      if (found?.energy) setEnergy(found.energy);
    });

    // fermeture
    document.addEventListener("click", (e) => {
      if (
        e.target !== brandInput &&
        e.target !== modelInput &&
        !brandResults.contains(e.target) &&
        !modelResults.contains(e.target)
      ) {
        closeAll();
      }
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeAll();
    });
  })();

  /* ============================================================
     10) CONFIRMATION OPTIONNELLE (liens sensibles)
     - Ajoute la classe .js-confirm et data-confirm="message"
  ============================================================ */
  (() => {
    on("click", ".js-confirm", (e, el) => {
      const msg = el.dataset.confirm || "Confirmer cette action ?";
      if (!confirm(msg)) e.preventDefault();
    });
  })();

  /* ============================================================
     11) SCROLL TO TOP (optionnel)
     - #scrollTopBtn
  ============================================================ */
  (() => {
    const btn = $("#scrollTopBtn");
    if (!btn) return;

    const toggle = () => {
      if (globalThis.scrollY > 500) btn.classList.add("show");
      else btn.classList.remove("show");
    };

    window.addEventListener("scroll", toggle, { passive: true });
    toggle();

    btn.addEventListener("click", () => {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  })();

  /* ============================================================
     12) PETITE SÉCURITÉ: empêche double-submit
     - form[data-prevent-double-submit]
  ============================================================ */
  (() => {
    $$("form[data-prevent-double-submit]").forEach((form) => {
      form.addEventListener("submit", () => {
        const btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!btn) return;
        btn.disabled = true;
        btn.classList.add("disabled");
      });
    });
  })();

  /* ============================================================
     FILTRES MOBILE (OUVERTURE / FERMETURE)
     - #open-filters
     - #filters-panel-mobile
     - #close-filters
  ============================================================ */
  (() => {
    const openBtn  = document.getElementById("open-filters");
    const panel    = document.getElementById("filters-panel-mobile");
    const closeBtn = document.getElementById("close-filters");

    if (!openBtn || !panel) return;

    const open = () => {
      panel.classList.add("active");
      document.body.classList.add("no-scroll");
    };

    const close = () => {
      panel.classList.remove("active");
      document.body.classList.remove("no-scroll");
    };

    openBtn.addEventListener("click", (e) => {
      e.preventDefault();
      open();
    });

    closeBtn?.addEventListener("click", (e) => {
      e.preventDefault();
      close();
    });

    // clic hors panneau
    panel.addEventListener("click", (e) => {
      if (e.target === panel) close();
    });

    // ESC
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && panel.classList.contains("active")) {
        close();
      }
    });
  })();

(() => {
  const applyBtn = document.querySelector('#filters-panel-mobile #apply-filters');
  if (!applyBtn) return;

  applyBtn.addEventListener("click", (e) => {
    e.preventDefault();

    const params = new URLSearchParams(globalThis.location.search);

    // --- TRI ---
    const sort = document.querySelector('input[name="sortMobile"]:checked');
    if (sort) {
      params.set("sort", sort.value);
    }

    // --- ÉNERGIES ---
    params.delete("energie[]");

    document
      .querySelectorAll('#filters-panel-mobile input[type="checkbox"]:checked')
      .forEach(cb => {
      params.append("energie[]", cb.value);
      });

    globalThis.location.href = globalThis.location.pathname + "?" + params.toString();
    });
  })();

  document.addEventListener('click', function (e) {
    // Si tu as des boutons/liens dans la ligne, mets-leur la classe "no-row-click"
    if (e.target.closest('.no-row-click')) return;

    const row = e.target.closest('.clickable-row');
    if (!row) return;

    const href = row.dataset.href;
    if (href) globalThis.location.href = href;
  });
});
