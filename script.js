const CONSENT_KEY = "scc_cookie_consent";
const GA_ID = "G-71ZPZ7YT0S";

function loadAnalytics() {
  if (window.sccAnalyticsLoaded) return;
  window.sccAnalyticsLoaded = true;

  const script = document.createElement("script");
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
  document.head.appendChild(script);

  window.dataLayer = window.dataLayer || [];
  window.gtag = function gtag() {
    window.dataLayer.push(arguments);
  };
  window.gtag("js", new Date());
  window.gtag("config", GA_ID, { anonymize_ip: true });
}

function setConsent(value) {
  localStorage.setItem(CONSENT_KEY, value);
  document.querySelector("[data-cookie-banner]")?.classList.remove("is-visible");
  if (value === "accepted") loadAnalytics();
}

function initCookies() {
  const banner = document.querySelector("[data-cookie-banner]");
  const stored = localStorage.getItem(CONSENT_KEY);

  if (stored === "accepted") {
    loadAnalytics();
  } else if (!stored && banner) {
    banner.classList.add("is-visible");
  }

  document.querySelectorAll("[data-cookie-choice]").forEach((button) => {
    button.addEventListener("click", () => setConsent(button.dataset.cookieChoice));
  });
}

function initMenu() {
  const toggle = document.querySelector("[data-menu-toggle]");
  const links = document.querySelector("[data-nav-links]");
  if (!toggle || !links) return;

  toggle.addEventListener("click", () => {
    const isOpen = links.classList.toggle("is-open");
    document.body.classList.toggle("menu-open", isOpen);
    toggle.setAttribute("aria-expanded", String(isOpen));
  });
}

function initContactForm() {
  const form = document.querySelector("[data-contact-form]");
  if (!form) return;

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const name = String(data.get("name") || "").trim();
    const email = String(data.get("email") || "").trim();
    const message = String(data.get("message") || "").trim();
    const note = form.querySelector("[data-form-note]");

    if (!name || !email || !message) {
      note.textContent = "Please complete your name, email and project message.";
      return;
    }

    const subject = encodeURIComponent(`Website enquiry from ${name}`);
    const body = encodeURIComponent(
      `Name: ${name}\nEmail: ${email}\nPhone: ${data.get("phone") || ""}\nBudget: ${data.get("budget") || ""}\n\n${message}`
    );
    note.textContent = "Opening your email app with the enquiry ready to send.";
    window.location.href = `mailto:hello@sccwebdesign.co.uk?subject=${subject}&body=${body}`;
  });
}

document.addEventListener("DOMContentLoaded", () => {
  const year = document.getElementById("year");
  if (year) year.textContent = String(new Date().getFullYear());
  initMenu();
  initCookies();
  initContactForm();
});
