const currency = "RD$";

const formatMoney = (value) =>
  `${currency} ${Number(value || 0).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;

const invoiceForm = document.querySelector("[data-invoice-form]");

if (invoiceForm) {
  const linesContainer = invoiceForm.querySelector("[data-lines]");
  const addButton = invoiceForm.querySelector("[data-add-line]");

  const recalc = () => {
    let subtotal = 0;
    invoiceForm.querySelectorAll("[data-line]").forEach((line) => {
      const qty = Number(line.querySelector("[data-qty]").value || 0);
      const price = Number(line.querySelector("[data-price]").value || 0);
      const total = qty * price;
      subtotal += total;
      line.querySelector("[data-line-total]").textContent = formatMoney(total);
    });

    const discount = Number(invoiceForm.querySelector("[data-discount]").value || 0);
    const taxRate = Number(invoiceForm.querySelector("[data-tax]").value || 0);
    const taxable = Math.max(0, subtotal - discount);
    const taxTotal = taxable * (taxRate / 100);
    const total = taxable + taxTotal;

    invoiceForm.querySelector("[data-subtotal]").textContent = formatMoney(subtotal);
    invoiceForm.querySelector("[data-tax-total]").textContent = formatMoney(taxTotal);
    invoiceForm.querySelector("[data-total]").textContent = formatMoney(total);
  };

  addButton.addEventListener("click", () => {
    const template = invoiceForm.querySelector("[data-line]").cloneNode(true);
    template.querySelectorAll("input").forEach((input) => {
      input.value =
        input.name === "quantity[]"
          ? "1"
          : input.name === "unit_price[]" || input.name === "duration_days[]"
          ? "0"
          : "";
    });
    template.querySelector("[data-line-total]").textContent = formatMoney(0);
    linesContainer.appendChild(template);
  });

  invoiceForm.addEventListener("input", recalc);
  invoiceForm.addEventListener("click", (event) => {
    const remove = event.target.closest("[data-remove-line]");
    if (!remove) return;
    const lines = invoiceForm.querySelectorAll("[data-line]");
    if (lines.length > 1) {
      remove.closest("[data-line]").remove();
      recalc();
    }
  });
  recalc();
}

const chart = document.querySelector("#salesChart");
if (chart) {
  const rows = JSON.parse(chart.dataset.series || "[]");
  const ctx = chart.getContext("2d");
  const rect = chart.getBoundingClientRect();
  const dpr = window.devicePixelRatio || 1;
  chart.width = rect.width * dpr;
  chart.height = Number(chart.getAttribute("height")) * dpr;
  ctx.scale(dpr, dpr);

  const width = rect.width;
  const height = Number(chart.getAttribute("height"));
  const pad = 34;
  const values = rows.map((row) => Number(row.total || 0));
  const max = Math.max(...values, 1);

  ctx.clearRect(0, 0, width, height);
  ctx.strokeStyle = "rgba(16, 13, 13, 0.12)";
  ctx.lineWidth = 1;
  for (let i = 0; i < 4; i += 1) {
    const y = pad + ((height - pad * 2) / 3) * i;
    ctx.beginPath();
    ctx.moveTo(pad, y);
    ctx.lineTo(width - pad, y);
    ctx.stroke();
  }

  if (!rows.length) {
    ctx.fillStyle = "#7c706a";
    ctx.font = "14px Inter, Arial";
    ctx.fillText("Aún no hay ventas para graficar.", pad, height / 2);
  } else {
    const step = rows.length > 1 ? (width - pad * 2) / (rows.length - 1) : 0;
    ctx.strokeStyle = "#c5a563";
    ctx.lineWidth = 3;
    ctx.beginPath();
    rows.forEach((row, index) => {
      const x = pad + step * index;
      const y = height - pad - (Number(row.total || 0) / max) * (height - pad * 2);
      if (index === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    });
    ctx.stroke();

    rows.forEach((row, index) => {
      const x = pad + step * index;
      const y = height - pad - (Number(row.total || 0) / max) * (height - pad * 2);
      ctx.fillStyle = "#100d0d";
      ctx.beginPath();
      ctx.arc(x, y, 4, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = "#7c706a";
      ctx.font = "11px Inter, Arial";
      ctx.fillText(String(row.invoice_date || "").slice(5), x - 14, height - 9);
    });
  }
}
