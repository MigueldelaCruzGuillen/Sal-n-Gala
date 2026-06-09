const menuButton = document.querySelector(".menu-toggle");
const navPanel = document.querySelector(".nav-panel");
const bookingForm = document.querySelector("#bookingForm");

if (window.lucide) {
  window.lucide.createIcons();
}

menuButton.addEventListener("click", () => {
  const isOpen = navPanel.classList.toggle("is-open");
  document.body.classList.toggle("menu-open", isOpen);
  menuButton.setAttribute("aria-expanded", String(isOpen));
});

navPanel.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    navPanel.classList.remove("is-open");
    document.body.classList.remove("menu-open");
    menuButton.setAttribute("aria-expanded", "false");
  });
});

const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        revealObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.16 }
);

document.querySelectorAll(".reveal").forEach((element) => {
  revealObserver.observe(element);
});

bookingForm.addEventListener("submit", (event) => {
  event.preventDefault();

  const data = new FormData(bookingForm);
  const nombre = data.get("nombre");
  const telefono = data.get("telefono");
  const servicio = data.get("servicio");
  const fecha = data.get("fecha");
  const mensaje = data.get("mensaje") || "Sin mensaje adicional";

  const text = encodeURIComponent(
    `Hola Galá, quiero reservar una cita.

Nombre: ${nombre}
Teléfono: ${telefono}
Servicio: ${servicio}
Fecha: ${fecha}
Mensaje: ${mensaje}`
  );

  const whatsapp = bookingForm.dataset.whatsapp || "18098666064";
  window.open(`https://wa.me/${whatsapp}?text=${text}`, "_blank", "noopener,noreferrer");
});
