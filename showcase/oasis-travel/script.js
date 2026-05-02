const key = "oasis_cookie_choice";
document.addEventListener("DOMContentLoaded", () => {
  const nav = document.querySelector("[data-nav]");
  document.querySelector("[data-menu-toggle]")?.addEventListener("click", () => nav?.classList.toggle("open"));
  const banner = document.querySelector("[data-cookie-banner]");
  if (!localStorage.getItem(key)) banner?.classList.add("show");
  document.querySelectorAll("[data-cookie-choice]").forEach((button) => button.addEventListener("click", () => {
    localStorage.setItem(key, button.dataset.cookieChoice || "accepted");
    banner?.classList.remove("show");
  }));
  document.querySelector("[data-contact-form]")?.addEventListener("submit", (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);
    const body = encodeURIComponent(`Name: ${data.get("name")}\nEmail: ${data.get("email")}\nTrip style: ${data.get("subject")}\n\n${data.get("message")}`);
    form.querySelector("[data-form-note]").textContent = "Opening your email app.";
    location.href = `mailto:${form.dataset.email}?subject=Travel enquiry&body=${body}`;
  });
});
