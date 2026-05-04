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
      `Name: ${name}\nEmail: ${email}\nProject type: ${data.get("budget") || ""}\n\n${message}`
    );
    note.textContent = "Opening your email app with the enquiry ready to send.";
    window.location.href = `mailto:hello@sccwebdesign.co.uk?subject=${subject}&body=${body}`;
  });
}

function initSiteBuilderForm() {
  const form = document.querySelector("[data-site-builder-form]");
  if (!form) return;

  const output = document.querySelector("[data-builder-output]");
  const emailLink = document.querySelector("[data-builder-email]");
  const note = document.querySelector("[data-builder-note]");

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const data = new FormData(form);
    const pages = data.getAll("pages");
    const assets = data.getAll("assets");
    const request = {
      job: "generate_static_website",
      business_name: String(data.get("business_name") || "").trim(),
      email: String(data.get("email") || "").trim(),
      location: String(data.get("location") || "").trim(),
      business_type: data.get("business_type"),
      style: data.get("style"),
      colour: data.get("colour"),
      pages,
      assets,
      output: data.get("output"),
      notes: String(data.get("notes") || "").trim(),
      generated_at: new Date().toISOString()
    };

    if (!request.business_name || !request.email || !request.location || !request.business_type) {
      note.textContent = "Please complete the business name, email, location and business type.";
      return;
    }

    const json = JSON.stringify(request, null, 2);
    output.textContent = json;
    const subject = encodeURIComponent(`Website factory request: ${request.business_name}`);
    const body = encodeURIComponent(`Please generate a website from this structured request:\n\n${json}`);
    emailLink.href = `mailto:hello@sccwebdesign.co.uk?subject=${subject}&body=${body}`;
    note.textContent = "Your structured request is ready. Use the email button to send it to SCC.";
  });
}

document.addEventListener("DOMContentLoaded", () => {
  const year = document.getElementById("year");
  if (year) year.textContent = String(new Date().getFullYear());
  initMenu();
  initCookies();
  initContactForm();
  initSiteBuilderForm();
});
