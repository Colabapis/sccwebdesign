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

  form.addEventListener("submit", async (event) => {
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

    note.textContent = "Sending your enquiry...";

    try {
      const response = await fetch(form.action || "contact-submit.php", {
        method: "POST",
        body: data,
        headers: { Accept: "application/json" }
      });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || "Send failed");
      note.textContent = result.message || "Thanks, your enquiry has been sent.";
      form.reset();
    } catch (error) {
      const subject = encodeURIComponent(`Website enquiry from ${name}`);
      const body = encodeURIComponent(
        `Name: ${name}\nEmail: ${email}\nProject type: ${data.get("budget") || ""}\n\n${message}`
      );
      note.textContent = "The website could not send automatically. Opening your email app as a fallback.";
      window.location.href = `mailto:scottchowen@gmail.com?subject=${subject}&body=${body}`;
    }
  });
}

function initSiteBuilderForm() {
  const form = document.querySelector("[data-site-builder-form]");
  if (!form) return;

  const output = document.querySelector("[data-builder-output]");
  const emailLink = document.querySelector("[data-builder-email]");
  const note = document.querySelector("[data-builder-note]");
  const status = document.querySelector("[data-builder-status]");
  const copyButton = document.querySelector("[data-builder-copy]");
  const emailNote = document.querySelector("[data-builder-email-note]");

  function setFieldFromQuery(data) {
    for (const [key, value] of data.entries()) {
      const controls = Array.from(form.elements).filter((control) => control.name === key);
      controls.forEach((control) => {
        if (control.type === "checkbox") {
          control.checked = data.getAll(key).includes(control.value);
        } else {
          control.value = value;
        }
      });
    }
  }

  function buildRequest() {
    const data = new FormData(form);
    const logoFile = data.get("logo_file");
    const imageFiles = data.getAll("client_images[]").filter((file) => file && file.name);
    return {
      job: "generate_static_website",
      business_name: String(data.get("business_name") || "").trim(),
      email: String(data.get("email") || "").trim(),
      location: String(data.get("location") || "").trim(),
      live_domain: String(data.get("live_domain") || "").trim(),
      domain_extension: data.get("domain_extension"),
      business_type: data.get("business_type"),
      style: data.get("style"),
      tone: data.get("tone"),
      colour: data.get("colour"),
      banner_style: data.get("banner_style"),
      banner_image_count: Number(data.get("banner_image_count") || 3),
      pages: data.getAll("pages"),
      assets: data.getAll("assets"),
      logo_url: String(data.get("logo_url") || "").trim(),
      logo_upload: logoFile && logoFile.name ? logoFile.name : "",
      client_image_urls: String(data.get("client_image_urls") || "")
        .split(/\r?\n/)
        .map((url) => url.trim())
        .filter(Boolean)
        .slice(0, 20),
      client_image_uploads: imageFiles.map((file) => file.name).slice(0, 20),
      analytics_measurement_id: String(data.get("analytics_measurement_id") || "").trim(),
      output: data.get("output"),
      preview_ttl_minutes: Number(data.get("preview_ttl_minutes") || 15),
      social_links: String(data.get("social_links") || "")
        .split(/\r?\n/)
        .map((url) => url.trim())
        .filter(Boolean),
      notes: String(data.get("notes") || "").trim(),
      generated_at: new Date().toISOString()
    };
  }

  function applyBusinessDefaults() {
    const businessType = form.elements.business_type?.value;
    const pages = new Set(["tree_surgeon", "plumber", "electrician", "landscaping", "roofing", "pest_control", "car_garage"]);
    if (!pages.has(businessType)) return;
    ["gallery", "reviews"].forEach((page) => {
      const checkbox = form.querySelector(`input[name="pages"][value="${page}"]`);
      if (checkbox) checkbox.checked = true;
    });
    const tone = form.elements.tone;
    if (tone && businessType === "tree_surgeon") tone.value = "rugged_industrial";
  }

  function renderRequest(request, message) {
    const json = JSON.stringify(request, null, 2);
    output.textContent = json;
    if (emailLink.tagName === "A") {
      const subject = encodeURIComponent(`Website factory request: ${request.business_name || "New website"}`);
      const body = encodeURIComponent(`Please generate a website from this structured request:\n\n${json}`);
      emailLink.href = `mailto:scottchowen@gmail.com?subject=${subject}&body=${body}`;
    }
    if (status) status.textContent = "Request prepared";
    if (note) note.textContent = message;
    if (emailNote) {
      emailNote.textContent = `When SCC runs this request, the preview can be available shortly and expire after ${request.preview_ttl_minutes || 15} minutes.`;
    }
  }

  const query = new URLSearchParams(window.location.search);
  if ([...query.keys()].length) {
    setFieldFromQuery(query);
    const request = buildRequest();
    renderRequest(request, "Loaded your link and prepared the request. It has not been sent or published yet.");
  }

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    applyBusinessDefaults();
    const request = buildRequest();

    if (!request.business_name || !request.email || !request.location || !request.business_type) {
      note.textContent = "Please complete the business name, email, location and business type.";
      if (status) status.textContent = "Needs details";
      return;
    }

    renderRequest(request, "Your structured request is ready. Use the email button to send it to SCC. SCC can then create the temporary preview safely.");
  });

  copyButton?.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(output.textContent);
      if (note) note.textContent = "Request copied to your clipboard.";
    } catch {
      if (note) note.textContent = "Copy failed. You can still select the request text manually.";
    }
  });

  emailLink?.addEventListener("click", async (event) => {
    event.preventDefault();
    applyBusinessDefaults();
    const request = buildRequest();
    if (!request.business_name || !request.email || !request.location || !request.business_type) {
      if (emailNote) emailNote.textContent = "Complete the required brief details before sending the request.";
      if (status) status.textContent = "Needs details";
      return;
    }

    renderRequest(
      request,
      `Sending request to SCC. Once SCC receives and runs it, the preview can be available shortly and expire after ${request.preview_ttl_minutes || 15} minutes.`
    );

    try {
      const payload = new FormData(form);
      payload.set("request_json", JSON.stringify(request));
      const response = await fetch("builder-request.php", {
        method: "POST",
        headers: {
          Accept: "application/json"
        },
        body: payload
      });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || "Send failed");
      if (result.request) renderRequest(result.request, result.message);
      if (status) status.textContent = "Request sent";
      if (note) note.textContent = result.message;
      if (emailNote) emailNote.textContent = "SCC has received the request. The preview link is generated by the private Site Factory workflow after review.";
    } catch (error) {
      const json = JSON.stringify(request, null, 2);
      const subject = encodeURIComponent(`Website factory request: ${request.business_name || "New website"}`);
      const body = encodeURIComponent(`Please generate a website from this structured request:\n\n${json}`);
      if (emailNote) {
        emailNote.textContent = "The website could not send automatically. Opening your email app as a fallback.";
      }
      window.location.href = `mailto:scottchowen@gmail.com?subject=${subject}&body=${body}`;
    }
  });

  form.elements.business_type?.addEventListener("change", applyBusinessDefaults);
}

document.addEventListener("DOMContentLoaded", () => {
  const year = document.getElementById("year");
  if (year) year.textContent = String(new Date().getFullYear());
  initMenu();
  initCookies();
  initContactForm();
  initSiteBuilderForm();
});
