<?php require __DIR__ . '/app/bootstrap.php'; ?>
<?php
$services = items('service', 6);
$products = items('product', 8);
$testimonials = items('testimonial', 6);
$gallery = items('gallery', 8);
$whatsapp = preg_replace('/\D+/', '', setting('whatsapp', '18098666064'));
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= h(setting('legal_name')) ?></title>
    <meta name="description" content="<?= h(setting('intro')) ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/styles.css?v=20260515-1" />
  </head>
  <body>
    <header class="site-header" id="inicio">
      <nav class="navbar" aria-label="Navegación principal">
        <a class="brand" href="#inicio" aria-label="Inicio Galá">
          <span class="brand-mark">G</span>
          <span><strong><?= h(setting('brand')) ?></strong><small>Salud Capilar Integrativa</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false"><i data-lucide="menu"></i></button>
        <div class="nav-panel">
          <a href="#inicio">Inicio</a>
          <a href="#servicios">Servicios</a>
          <a href="#tratamientos">Tratamientos</a>
          <a href="#productos">Productos</a>
          <a href="#testimonios">Testimonios</a>
          <a href="#contacto">Contacto</a>
          <a class="nav-cta" href="#reservas">Agendar</a>
        </div>
      </nav>

      <section class="hero">
        <div class="hero-media" style="background-image: linear-gradient(90deg, rgba(14,11,10,.78), rgba(14,11,10,.42), rgba(14,11,10,.18)), url('<?= h(setting('hero_image')) ?>')"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content reveal">
          <p class="eyebrow">Santo Domingo · Tricología aplicada a la cosmética capilar</p>
          <h1><?= h(setting('tagline')) ?></h1>
          <p><?= h(setting('intro')) ?> Cuidado personalizado, acabado premium y una experiencia diseñada para mujeres que invierten en verse y sentirse impecables.</p>
          <div class="hero-actions">
            <a class="btn btn-primary" href="#reservas">Reservar cita</a>
            <a class="btn btn-secondary" href="#tratamientos">Ver tratamientos</a>
          </div>
        </div>
        <aside class="hero-card glass reveal">
          <span><?= h(setting('google_rating')) ?></span>
          <strong><?= h(setting('google_reviews')) ?></strong>
          <p><?= h(setting('address')) ?></p>
        </aside>
      </section>
    </header>

    <main>
      <section class="intro-band reveal">
        <div><span class="section-kicker">Centro capilar</span><h2>Belleza clínica, trato cálido y resultados visibles.</h2></div>
        <p>En Galá cuidamos el cabello desde la raíz hasta el acabado final con protocolos para hidratación, color, alisados, styling y salud capilar.</p>
      </section>

      <section class="section services" id="servicios">
        <div class="section-heading reveal"><span class="section-kicker">Servicios</span><h2>Rituales diseñados para cabello sano, brillante y memorable.</h2></div>
        <div class="service-grid">
          <?php foreach ($services as $service): ?>
            <article class="service-card reveal">
              <img src="<?= h(image_url($service['image'])) ?>" alt="<?= h($service['title']) ?>" loading="lazy" />
              <div>
                <i data-lucide="sparkles"></i>
                <h3><?= h($service['title']) ?></h3>
                <p><?= h($service['body']) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="section treatments" id="tratamientos">
        <div class="treatment-copy reveal">
          <span class="section-kicker">Tratamientos</span>
          <h2>El lujo está en sentir tu cabello vivo otra vez.</h2>
          <p>Rutinas capilares pensadas para recuperar elasticidad, luminosidad y fuerza, respetando la identidad de cada clienta.</p>
          <ul>
            <li><i data-lucide="check"></i> Reparación intensiva y reconstrucción</li>
            <li><i data-lucide="check"></i> Detox de cuero cabelludo</li>
            <li><i data-lucide="check"></i> Brillo espejo y sellado de puntas</li>
          </ul>
        </div>
        <div class="treatment-image reveal"><img src="https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?auto=format&fit=crop&w=1200&q=88" alt="Mujer con cabello saludable y brillante" loading="lazy" /></div>
      </section>

      <section class="section gallery">
        <div class="section-heading reveal"><span class="section-kicker">Galería premium</span><h2>Fotos reales del salón, transformaciones y momentos Galá.</h2></div>
        <div class="masonry">
          <?php foreach ($gallery as $index => $photo): ?>
            <figure class="tile <?= $index % 3 === 0 ? 'tall' : '' ?> <?= $index % 4 === 2 ? 'wide' : '' ?> reveal">
              <img src="<?= h(image_url($photo['image'])) ?>" alt="<?= h($photo['title']) ?>" loading="lazy" />
              <figcaption><?= h($photo['title']) ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="section products" id="productos">
        <div class="section-heading reveal"><span class="section-kicker">Productos</span><h2>Rutina boutique para prolongar el resultado en casa.</h2></div>
        <div class="product-grid">
          <?php foreach ($products as $product): ?>
            <article class="product-card reveal">
              <img src="<?= h(image_url($product['image'])) ?>" alt="<?= h($product['title']) ?>" loading="lazy" />
              <div><span><?= h($product['subtitle']) ?></span><h3><?= h($product['title']) ?></h3><p><?= h($product['body']) ?></p><strong><?= h($product['price']) ?></strong></div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="section testimonials" id="testimonios">
        <div class="section-heading reveal"><span class="section-kicker">Opiniones Google</span><h2><?= h(setting('google_rating')) ?> estrellas en Google · <?= h(setting('google_reviews')) ?></h2></div>
        <div class="testimonial-grid">
          <?php foreach ($testimonials as $review): ?>
            <article class="testimonial-card reveal">
              <img src="<?= h(image_url($review['image'])) ?>" alt="<?= h($review['title']) ?>" loading="lazy" />
              <div class="stars" aria-label="<?= (int) $review['rating'] ?> estrellas"><?= h(stars((int) $review['rating'])) ?></div>
              <p>“<?= h($review['body']) ?>”</p>
              <strong><?= h($review['title']) ?></strong>
              <span><?= h($review['subtitle']) ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="section booking" id="reservas">
        <div class="booking-panel reveal">
          <div class="booking-copy">
            <span class="section-kicker">Reservas</span>
            <h2>Agenda una experiencia capilar personalizada.</h2>
            <p>Completa tus datos y te llevamos directo a WhatsApp para confirmar disponibilidad.</p>
            <div class="contact-line"><i data-lucide="phone"></i><span><?= h(setting('phone')) ?></span></div>
          </div>
          <form class="booking-form" id="bookingForm" data-whatsapp="<?= h($whatsapp) ?>">
            <label>Nombre<input type="text" name="nombre" placeholder="Tu nombre" required maxlength="80" /></label>
            <label>Teléfono<input type="tel" name="telefono" placeholder="Tu teléfono" required maxlength="30" /></label>
            <label>Servicio<select name="servicio" required><option value="">Selecciona un servicio</option><?php foreach ($services as $service): ?><option><?= h($service['title']) ?></option><?php endforeach; ?></select></label>
            <label>Fecha<input type="date" name="fecha" required /></label>
            <label class="full">Mensaje<textarea name="mensaje" rows="4" placeholder="Cuéntanos qué deseas lograr" maxlength="500"></textarea></label>
            <button class="btn btn-primary full" type="submit">Reservar por WhatsApp</button>
          </form>
        </div>
      </section>

      <section class="final-cta reveal">
        <div><span class="section-kicker">Galá Beauty Ritual</span><h2>Tu cabello merece cuidado profesional.</h2><p>Reserva hoy y vive una atención capilar elevada, personalizada y profundamente femenina.</p><a class="btn btn-primary" href="#reservas">Agenda tu cita hoy</a></div>
      </section>
    </main>

    <footer class="footer" id="contacto">
      <div><a class="brand footer-brand" href="#inicio"><span class="brand-mark">G</span><span><strong><?= h(setting('brand')) ?></strong><small>Centro de Salud Capilar Integrativa</small></span></a><p><?= h(setting('intro')) ?></p></div>
      <div><h3>Contacto</h3><p><?= h(setting('address')) ?></p><p>WhatsApp: <a href="https://wa.me/<?= h($whatsapp) ?>" target="_blank" rel="noreferrer"><?= h(setting('phone')) ?></a></p><p>Horario: <?= h(setting('hours')) ?></p></div>
      <div><h3>Redes sociales</h3><div class="socials"><a href="<?= h(setting('instagram')) ?>" aria-label="Instagram"><i data-lucide="camera"></i></a><a href="<?= h(setting('tiktok')) ?>" aria-label="TikTok"><i data-lucide="music-2"></i></a><a href="https://wa.me/<?= h($whatsapp) ?>" target="_blank" rel="noreferrer" aria-label="WhatsApp"><i data-lucide="message-circle"></i></a></div></div>
    </footer>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="assets/js/main.js"></script>
  </body>
</html>
