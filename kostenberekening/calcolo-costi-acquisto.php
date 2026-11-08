<!DOCTYPE html>
<html lang="it">
<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-GPH47E08WK"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-GPH47E08WK');
</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calcolo dei costi per comprare casa in Italia | Stef Smulders</title>
<meta name="description" content="Calcola tutti i costi accessori dell'acquisto di una casa in Italia: imposte di trasferimento per ciascun bene (abitazione, pertinenze, terreno edificabile, terreno agricolo), notaio, agenzia immobiliare e altro.">

<!-- Canvas Theme CSS (voor header/menu) -->
<link href="https://fonts.googleapis.com/css?family=Lato:300,400,400i,700|Poppins:300,400,500,600,700|PT+Serif:400,400i&display=swap" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="css/bootstrap.css" type="text/css" />
<link rel="stylesheet" href="style.css" type="text/css" />
<link rel="stylesheet" href="css/dark.css" type="text/css" />
<link rel="stylesheet" href="css/font-icons.css" type="text/css" />
<link rel="stylesheet" href="css/animate.css" type="text/css" />
<link rel="stylesheet" href="css/magnific-popup.css" type="text/css" />
<link rel="stylesheet" href="css/custom.css" type="text/css" />

<!-- Calculator eigen fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
  --sand: #FDF6EC;
  --sand-dark: #F5E6CC;
  --terracotta: #C6864B;
  --terracotta-dark: #A06830;
  --brown: #8B4513;
  --brown-dark: #5C2E0D;
  --cream: #FFFDF8;
  --olive: #6B7B3A;
  --olive-light: #8FA04F;
  --text: #2C2017;
  --text-light: #6B5B4D;
  --border: #E0D0BC;
  --white: #FFFFFF;
  --red: #C0392B;
  --blue-info: #2980B9;
  --shadow: 0 2px 12px rgba(139,69,19,0.08);
  --shadow-lg: 0 8px 32px rgba(139,69,19,0.12);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: 'Source Sans 3', sans-serif;
  background: var(--sand);
  color: var(--text);
  line-height: 1.6;
  min-height: 100vh;
}

.hidden { display: none !important; }

/* Page title banner */
.page-title-banner {
  background: linear-gradient(135deg, var(--brown-dark) 0%, var(--brown) 40%, var(--terracotta-dark) 100%);
  color: white;
  padding: 2.5rem 1.5rem 2rem;
  text-align: center;
  position: relative;
  overflow: hidden;
  margin-top: 0;
}
.page-title-banner::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 0L60 30L30 60L0 30z' fill='none' stroke='rgba(255,255,255,0.05)' stroke-width='1'/%3E%3C/svg%3E");
  opacity: 0.5;
}
.page-title-banner h1 {
  font-family: 'Playfair Display', serif;
  font-size: clamp(1.6rem, 4vw, 2.4rem);
  font-weight: 700;
  position: relative;
  margin-bottom: 0.3rem;
}
.page-title-banner p {
  font-size: 0.95rem;
  opacity: 0.85;
  position: relative;
  font-weight: 300;
}

.container {
  max-width: 960px;
  margin: 0 auto;
  padding: 1.5rem 1rem 3rem;
}

/* Sections */
.section {
  background: var(--white);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.25rem;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}
.section-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.25rem;
  color: var(--brown);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid var(--sand-dark);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.section-title .num {
  background: var(--terracotta);
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Source Sans 3', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  flex-shrink: 0;
}

/* Form elements */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 600px) {
  .form-grid { grid-template-columns: 1fr; }
}
.form-group { position: relative; }
.form-group.full { grid-column: 1 / -1; }

label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 600;
  font-size: 0.88rem;
  color: var(--text);
  margin-bottom: 0.3rem;
}
label.mini {
  font-size: 0.75rem;
  color: var(--text-light);
  margin-bottom: 0.15rem;
}

.info-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--blue-info);
  color: white;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  line-height: 1;
  flex-shrink: 0;
  transition: transform 0.15s;
  font-family: serif;
}
.info-btn:hover { transform: scale(1.15); }

input[type="number"], input[type="text"], select {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  font-size: 0.95rem;
  font-family: 'Source Sans 3', sans-serif;
  background: var(--cream);
  color: var(--text);
  transition: border-color 0.2s, box-shadow 0.2s;
}
input:focus, select:focus {
  outline: none;
  border-color: var(--terracotta);
  box-shadow: 0 0 0 3px rgba(198,134,75,0.15);
}
input[type="number"] { -moz-appearance: textfield; }
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button { -webkit-appearance: none; }

.input-with-unit { position: relative; }
.input-with-unit input { padding-right: 2.5rem; }
.input-unit {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-light);
  font-size: 0.85rem;
  font-weight: 600;
  pointer-events: none;
}

.range-info {
  font-size: 0.78rem;
  color: var(--text-light);
  margin-top: 0.2rem;
  font-style: italic;
}

/* Radio/toggle groups */
.toggle-group {
  display: flex;
  gap: 0;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  overflow: hidden;
  background: var(--cream);
}
.toggle-group label {
  flex: 1;
  padding: 0.55rem 0.5rem;
  text-align: center;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.85rem;
  margin: 0;
  transition: all 0.2s;
  border-right: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1.25;
}
.toggle-group label:last-child { border-right: none; }
.toggle-group input { display: none; }
.toggle-group input:checked + label {
  background: var(--terracotta);
  color: white;
  font-weight: 700;
}

/* Checkbox styled */
.checkbox-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0;
}
.checkbox-row input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--terracotta);
  cursor: pointer;
  flex-shrink: 0;
}
.checkbox-row label {
  margin: 0;
  cursor: pointer;
  font-weight: 500;
}

.conditional {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: var(--sand);
  border-radius: 8px;
  border-left: 3px solid var(--terracotta);
}

/* Onderdelen van de koop */
.component {
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0.85rem 1rem;
  margin-bottom: 0.85rem;
  background: var(--cream);
}
.component .checkbox-row label,
.component-title {
  font-weight: 700;
  color: var(--brown);
  font-size: 0.95rem;
}
.component-title {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.5rem;
}
.component.accent {
  border: 2px solid var(--terracotta);
  background: var(--white);
}
.bg-row {
  display: grid;
  grid-template-columns: 28px 1.6fr 1fr 1fr 34px;
  gap: 0.5rem;
  align-items: end;
  margin-bottom: 0.6rem;
}
.bg-num {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--sand-dark);
  color: var(--brown);
  font-weight: 700;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.4rem;
}
.bg-remove {
  width: 34px;
  height: 40px;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  background: var(--white);
  color: var(--red);
  font-size: 1.2rem;
  cursor: pointer;
}
@media (max-width: 600px) {
  .bg-row {
    grid-template-columns: 1fr 1fr;
    padding: 0.6rem;
    border: 1px dashed var(--border);
    border-radius: 8px;
    position: relative;
  }
  .bg-row .bg-num { display: none; }
  .bg-row .bg-cat { grid-column: 1 / -1; padding-right: 2.5rem; }
  .bg-row .bg-remove { position: absolute; top: 0.5rem; right: 0.5rem; height: 34px; }
}
.add-btn {
  background: var(--white);
  border: 1.5px dashed var(--terracotta);
  color: var(--terracotta-dark);
  font-weight: 600;
  font-family: 'Source Sans 3', sans-serif;
  font-size: 0.88rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
}
.add-btn:hover { background: var(--sand); }

/* Results */
.results-section {
  background: linear-gradient(135deg, var(--brown-dark) 0%, var(--brown) 60%, var(--terracotta-dark) 100%);
  color: white;
  border-radius: 12px;
  padding: 2rem 1.5rem;
  margin-bottom: 1.25rem;
  box-shadow: var(--shadow-lg);
}
.results-section .section-title {
  color: white;
  border-bottom-color: rgba(255,255,255,0.2);
}
.results-section .section-title .num { background: rgba(255,255,255,0.2); }

.result-total { text-align: center; padding: 1.5rem 0 1rem; }
.result-total .label {
  font-size: 0.9rem;
  opacity: 0.8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.result-total .amount {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2rem, 5vw, 3rem);
  font-weight: 700;
  margin: 0.25rem 0;
}
.result-total .percentage { font-size: 1.1rem; opacity: 0.85; font-weight: 300; }

.result-breakdown { margin-top: 1rem; }
.result-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.6rem 0;
  border-bottom: 1px solid rgba(255,255,255,0.12);
  font-size: 0.92rem;
}
.result-row:last-child { border-bottom: none; }
.result-row .rlabel {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  opacity: 0.9;
  flex: 1;
  padding-right: 1rem;
}
.result-row .ramount { font-weight: 700; font-size: 1rem; white-space: nowrap; }
.result-row.subtotal {
  border-top: 2px solid rgba(255,255,255,0.3);
  border-bottom: 2px solid rgba(255,255,255,0.3);
  font-weight: 700;
  padding: 0.75rem 0;
  margin-top: 0.25rem;
}
.result-row.subtotal .rlabel { opacity: 1; }
.result-row.dim { opacity: 0.5; }
.result-row.minus .ramount { color: #D7EDB4; }

.result-divider { height: 1px; background: rgba(255,255,255,0.2); margin: 0.75rem 0; }

.split-banner {
  background: rgba(255,255,255,0.14);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: 8px;
  padding: 0.85rem 1rem;
  margin-top: 1rem;
  font-size: 0.92rem;
  line-height: 1.5;
}
.warn-box {
  background: rgba(0,0,0,0.2);
  border-radius: 8px;
  padding: 0.85rem 1rem;
  margin-top: 1rem;
  font-size: 0.86rem;
  line-height: 1.5;
}
.warn-box ul { margin: 0.4rem 0 0 1.1rem; padding: 0; }
.warn-box li { margin-bottom: 0.35rem; }

/* Annual costs */
.annual-section {
  background: var(--white);
  border: 2px solid var(--olive);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.25rem;
}
.annual-section .section-title { color: var(--olive); border-bottom-color: rgba(107,123,58,0.2); }
.annual-section .section-title .num { background: var(--olive); }
.annual-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--sand-dark);
  font-size: 0.92rem;
}
.annual-row:last-child { border-bottom: none; }
.annual-row .rlabel { flex: 1; padding-right: 1rem; }
.annual-row .ramount { font-weight: 700; color: var(--olive); white-space: nowrap; }
.annual-row.dim { color: var(--text-light); font-style: italic; }
.annual-row.subtotal {
  border-top: 2px solid var(--olive);
  font-weight: 700;
  padding-top: 0.75rem;
  margin-top: 0.25rem;
  font-size: 1.05rem;
}
.annual-row.subtotal .ramount { font-size: 1.1rem; }

/* Popup / Modal */
.info-overlay {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(44,32,23,0.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.info-overlay.active { display: flex; }
.info-modal {
  background: var(--white);
  border-radius: 12px;
  max-width: 520px;
  width: 100%;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: var(--shadow-lg);
  animation: infoModalIn 0.25s ease;
}
@keyframes infoModalIn {
  from { opacity: 0; transform: translateY(20px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.info-modal-header {
  background: var(--terracotta);
  color: white;
  padding: 1rem 1.25rem;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.info-modal-header h3 { font-family: 'Playfair Display', serif; font-size: 1.1rem; }
.info-modal-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0 0.25rem;
  opacity: 0.8;
  transition: opacity 0.15s;
}
.info-modal-close:hover { opacity: 1; }
.info-modal-body {
  padding: 1.25rem;
  font-size: 0.9rem;
  line-height: 1.7;
  color: var(--text);
}
.info-modal-body p { margin-bottom: 0.75rem; }
.info-modal-body strong { color: var(--brown); }
.info-modal-body .highlight {
  background: #F0E6D6;
  border-left: 3px solid var(--terracotta);
  padding: 0.6rem 0.8rem;
  margin: 0.75rem 0;
  border-radius: 0 6px 6px 0;
  font-size: 0.85rem;
  color: #3A2410;
}
.info-modal-body .highlight strong { color: #6B2E0A; }

/* Note bar */
.note-bar {
  background: var(--sand);
  border: 1px solid var(--border);
  border-left: 4px solid var(--terracotta);
  padding: 0.75rem 1rem;
  border-radius: 0 8px 8px 0;
  margin: 1rem 0;
  font-size: 0.85rem;
  color: var(--text-light);
  line-height: 1.5;
}
.note-bar.warn { border-left-color: var(--red); color: var(--red); }

/* Footer */
.calc-footer {
  text-align: center;
  padding: 1.5rem;
  font-size: 0.8rem;
  color: var(--text-light);
  border-top: 1px solid var(--border);
}
.calc-footer a { color: var(--terracotta); text-decoration: none; }

/* CTA Banner */
.cta-banner {
  background: linear-gradient(135deg, var(--olive) 0%, #4A6B2A 100%);
  color: white;
  border-radius: 12px;
  padding: 2rem 1.75rem;
  margin-bottom: 1.25rem;
  text-align: center;
  box-shadow: var(--shadow-lg);
  position: relative;
  overflow: hidden;
}
.cta-banner::before {
  content: '';
  position: absolute;
  top: -50%; left: -50%; width: 200%; height: 200%;
  background: radial-gradient(ellipse at 30% 50%, rgba(255,255,255,0.08) 0%, transparent 60%);
  pointer-events: none;
}
.cta-banner h3 {
  font-family: 'Playfair Display', serif;
  font-size: 1.35rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  position: relative;
  line-height: 1.3;
}
.cta-banner p {
  font-size: 0.95rem;
  opacity: 0.9;
  margin-bottom: 1.25rem;
  position: relative;
  line-height: 1.6;
  max-width: 520px;
  margin-left: auto;
  margin-right: auto;
}
.cta-banner img {
  width: 220px;
  height: 300px;
  object-fit: cover;
  border-radius: 8px;
  margin: 1rem auto;
  display: block;
}
.cta-button {
  display: inline-block;
  padding: 0.85rem 2.25rem;
  background: var(--white);
  color: var(--olive);
  font-family: 'Source Sans 3', sans-serif;
  font-size: 1.05rem;
  font-weight: 700;
  border-radius: 10px;
  text-decoration: none;
  transition: transform 0.15s, box-shadow 0.2s;
  position: relative;
  letter-spacing: 0.02em;
}
.cta-button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }
.cta-button:active { transform: translateY(0); }
.cta-sub { font-size: 0.8rem; opacity: 0.7; margin-top: 0.75rem; position: relative; }
</style>
</head>
<body class="stretched">

<!-- Document Wrapper -->
<div id="wrapper" class="clearfix">

	<!-- Header -->
	<header id="header" class="full-header">
		<div id="header-wrap">
			<div class="container">
				<div class="header-row">
					<!-- Logo -->
					<div id="logo">
						<a href="index.php" class="retina-logo" data-dark-logo="Afbeeldingen/logo@2x.png"><img src="Afbeeldingen/logo@2x.png" alt="Stef Smulders"></a>
					</div><!-- #logo end -->

					<div class="primary-menu-trigger">
						<button class="cnvs-hamburger" type="button" title="Apri il menu">
							<span class="cnvs-hamburger-box"><span class="cnvs-hamburger-inner"></span></span>
						</button>
					</div>
					<!-- Primary Navigation -->
					<?php require_once 'Code-snippets/Menu.php';?>

					<form class="top-search-form" action="search.html" method="get">
						<input type="text" name="q" class="form-control" value="" placeholder="Scrivi e premi Invio..." autocomplete="off">
					</form>
				</div>
			</div>
		</div>
		<div class="header-wrap-clone"></div>
	</header><!-- #header end -->

	<!-- Page Title Banner -->
	<div class="page-title-banner">
		<h1 style="color:white;">Costi per comprare casa in Italia</h1>
		<h3 style="color:white;">Calcola tutti i costi accessori dell'acquisto, anche per una casa con pertinenze e terreni.<br>Sulla base della guida "Een Huis kopen in Italië".</h3>
		<p>(aggiornato al 04-11-2026, verificato su fonti italiane)</p>
	</div>

	<!-- Calculator Content -->
	<div id="calculator-wrapper">
	<div class="container">

		<!-- SECTION 1: Basis -->
		<div class="section">
			<div class="section-title"><span class="num">1</span> Dati di base</div>
			<div class="form-grid">
				<div class="form-group">
					<label>Prezzo di acquisto totale <button type="button" class="info-btn" onclick="showInfo('aankoopprijs', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="aankoopprijs" value="200000" min="0" step="1000" oninput="calculate()">
						<span class="input-unit">€</span>
					</div>
				</div>
				<div class="form-group">
					<label>Rendita catastale dell'abitazione <button type="button" class="info-btn" onclick="showInfo('rendita', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="rendita" value="635" min="0" step="5" oninput="calculate()">
						<span class="input-unit">€</span>
					</div>
					<div class="range-info" id="rendita_info">Si trova nella visura catastale</div>
				</div>
			</div>
		</div>

		<!-- SECTION 2: Verkoper, koper, bestemming -->
		<div class="section">
			<div class="section-title"><span class="num">2</span> Venditore, acquirente e destinazione</div>
			<div class="form-grid">
				<div class="form-group full">
					<label>Chi vende? <button type="button" class="info-btn" onclick="showInfo('verkoper', event)">i</button></label>
					<div class="toggle-group">
						<input type="radio" name="verkoper" id="vk_part" value="particulier" checked onchange="calculate()">
						<label for="vk_part">Privato</label>
						<input type="radio" name="verkoper" id="vk_vrij" value="bedrijf_vrij" onchange="calculate()">
						<label for="vk_vrij">Impresa, senza IVA</label>
						<input type="radio" name="verkoper" id="vk_iva" value="bedrijf_iva" onchange="calculate()">
						<label for="vk_iva">Impresa costruttrice, con IVA</label>
					</div>
				</div>
				<div class="form-group">
					<label>Chi acquista? <button type="button" class="info-btn" onclick="showInfo('koper', event)">i</button></label>
					<div class="toggle-group">
						<input type="radio" name="koper" id="kp_persoon" value="persoon" checked onchange="calculate()">
						<label for="kp_persoon">Persona fisica</label>
						<input type="radio" name="koper" id="kp_rp" value="rechtspersoon" onchange="calculate()">
						<label for="kp_rp">Società (srl o BV)</label>
					</div>
				</div>
				<div class="form-group" id="bestemming_container">
					<label>Destinazione <button type="button" class="info-btn" onclick="showInfo('bestemming', event)">i</button></label>
					<div class="toggle-group">
						<input type="radio" name="bestemming" id="prima_casa" value="prima" onchange="calculate()">
						<label for="prima_casa">Prima casa</label>
						<input type="radio" name="bestemming" id="seconda_casa" value="seconda" checked onchange="calculate()">
						<label for="seconda_casa">Seconda casa</label>
					</div>
				</div>
				<div class="form-group hidden" id="rechtspersoon_note">
					<div class="note-bar" style="margin:1.55rem 0 0;">Una persona giuridica non ha diritto né alle agevolazioni prima casa né al prezzo-valore. Il calcolatore applica il 9% sul prezzo.</div>
				</div>
				<div class="form-group full">
					<div class="checkbox-row">
						<input type="checkbox" id="luxe" onchange="calculate()">
						<label for="luxe">Abitazione di lusso (categoria A/1, A/8 o A/9) <button type="button" class="info-btn" onclick="showInfo('luxe', event)">i</button></label>
					</div>
				</div>
				<div class="form-group full" id="prezzo_valore_container">
					<div class="checkbox-row">
						<input type="checkbox" id="prezzo_valore" checked onchange="calculate()">
						<label for="prezzo_valore">Applica il prezzo-valore <button type="button" class="info-btn" onclick="showInfo('prezzo_valore', event)">i</button></label>
					</div>
				</div>
				<div class="form-group full hidden" id="credito_container">
					<div class="checkbox-row">
						<input type="checkbox" id="credito_aan" onchange="calculate()">
						<label for="credito_aan">Vendo (o ho venduto) in Italia una precedente prima casa <button type="button" class="info-btn" onclick="showInfo('credito', event)">i</button></label>
					</div>
					<div class="conditional hidden" id="credito_details">
						<label>Imposta di registro o IVA pagata all'epoca (credito d'imposta)</label>
						<div class="input-with-unit">
							<input type="number" id="credito_bedrag" value="2000" min="0" step="100" oninput="calculate()">
							<span class="input-unit">€</span>
						</div>
						<div class="range-info">Mai superiore all'imposta dovuta su questo acquisto; nell'atto si scomputa solo dall'imposta di registro</div>
					</div>
				</div>
			</div>
		</div>

		<!-- SECTION 3: Onderdelen van de koop -->
		<div class="section">
			<div class="section-title"><span class="num">3</span> Che cosa acquisti esattamente?</div>
			<div class="checkbox-row">
				<input type="checkbox" id="onderdelen_aan" onchange="calculate()">
				<label for="onderdelen_aan">L'acquisto comprende più della sola abitazione (pertinenze, giardino, terreni, rudere) <button type="button" class="info-btn" onclick="showInfo('onderdelen', event)">i</button></label>
			</div>
			<div class="range-info">Lascia disattivato per una normale abitazione. La rendita del passo 1 vale allora per l'intero immobile.</div>

			<div id="onderdelen_details" class="hidden" style="margin-top:1rem;">
				<div class="note-bar" id="woning_rest_bar">Quota di prezzo dell'abitazione (prezzo di acquisto meno i beni qui sotto): <strong id="woning_rest">€0</strong></div>

				<!-- Bijgebouwen -->
				<div class="component">
					<div class="component-title">Pertinenze <button type="button" class="info-btn" onclick="showInfo('pertinenze', event)">i</button></div>
					<div id="bijgebouwen_list"></div>
					<button type="button" class="add-btn" onclick="addBijgebouw()">+ Aggiungi pertinenza</button>
				</div>

				<!-- Tuin -->
				<div class="component">
					<div class="checkbox-row">
						<input type="checkbox" id="tuin_aan" onchange="calculate()">
						<label for="tuin_aan">Giardino o corte, censito a parte al catasto terreni <button type="button" class="info-btn" onclick="showInfo('tuin', event)">i</button></label>
					</div>
					<div class="range-info">Il giardino è censito al catasto fabbricati insieme alla casa (graffato)? Allora è già compreso nella rendita e qui non inserisci nulla.</div>
					<div class="conditional hidden" id="tuin_details">
						<div class="form-grid">
							<div class="form-group">
								<label>Reddito dominicale</label>
								<div class="input-with-unit">
									<input type="number" id="tuin_rd" value="12" min="0" step="1" oninput="calculate()">
									<span class="input-unit">€</span>
								</div>
							</div>
							<div class="form-group">
								<label>Quota di prezzo nell'atto</label>
								<div class="input-with-unit">
									<input type="number" id="tuin_prijs" value="3000" min="0" step="500" oninput="calculate()">
									<span class="input-unit">€</span>
								</div>
							</div>
						</div>
						<div class="checkbox-row">
							<input type="checkbox" id="tuin_pert" checked onchange="calculate()">
							<label for="tuin_pert">Il giardino è dimostrabilmente al servizio dell'abitazione (pertinenza indicata nell'atto)</label>
						</div>
						<div class="checkbox-row hidden" id="tuin_cass_container">
							<input type="checkbox" id="tuin_cass" onchange="calculate()">
							<label for="tuin_cass">Calcola con l'aliquota prima casa del 2% (orientamento della Cassazione, non certo)</label>
						</div>
					</div>
				</div>

				<!-- Bouwgrond -->
				<div class="component">
					<div class="checkbox-row">
						<input type="checkbox" id="bouw_aan" onchange="calculate()">
						<label for="bouw_aan">Terreno edificabile <button type="button" class="info-btn" onclick="showInfo('bouwgrond', event)">i</button></label>
					</div>
					<div class="conditional hidden" id="bouw_details">
						<div class="form-grid">
							<div class="form-group">
								<label>Superficie</label>
								<div class="input-with-unit">
									<input type="number" id="bouw_m2" value="2000" min="0" step="100" oninput="calculate()">
									<span class="input-unit">m²</span>
								</div>
							</div>
							<div class="form-group">
								<label>Quota di prezzo nell'atto</label>
								<div class="input-with-unit">
									<input type="number" id="bouw_prijs" value="50000" min="0" step="1000" oninput="calculate()">
									<span class="input-unit">€</span>
								</div>
								<div class="range-info" id="bouw_hint"></div>
							</div>
						</div>
					</div>
				</div>

				<!-- Landbouwgrond -->
				<div class="component">
					<div class="checkbox-row">
						<input type="checkbox" id="landb_aan" onchange="calculate()">
						<label for="landb_aan">Terreno agricolo <button type="button" class="info-btn" onclick="showInfo('landbouwgrond', event)">i</button></label>
					</div>
					<div class="conditional hidden" id="landb_details">
						<div class="form-grid">
							<div class="form-group">
								<label>Superficie</label>
								<div class="input-with-unit">
									<input type="number" id="landb_ha" value="3" min="0" step="0.1" oninput="calculate()">
									<span class="input-unit">ha</span>
								</div>
							</div>
							<div class="form-group">
								<label>Quota di prezzo nell'atto</label>
								<div class="input-with-unit">
									<input type="number" id="landb_prijs" value="30000" min="0" step="1000" oninput="calculate()">
									<span class="input-unit">€</span>
								</div>
								<div class="range-info" id="landb_hint"></div>
							</div>
							<div class="form-group">
								<label>Reddito dominicale (per l'IMU)</label>
								<div class="input-with-unit">
									<input type="number" id="landb_rd" value="60" min="0" step="5" oninput="calculate()">
									<span class="input-unit">€</span>
								</div>
							</div>
							<div class="form-group">
								<label>Regime <button type="button" class="info-btn" onclick="showInfo('landbouw_regime', event)">i</button></label>
								<select id="landb_regime" onchange="calculate()">
									<option value="standaard" selected>Acquirente non agricoltore (15%)</option>
									<option value="agrarier">Coltivatore diretto o IAP (€200 + €200 + 1%)</option>
									<option value="cd9">Coltivatore diretto o IAP, senza vincolo quinquennale (9%)</option>
									<option value="berg">Territori montani, coltivazione diretta per 5 anni (€200 + €200)</option>
								</select>
							</div>
						</div>
					</div>
				</div>

				<!-- Ruïne F/2 -->
				<div class="component">
					<div class="checkbox-row">
						<input type="checkbox" id="f2_aan" onchange="calculate()">
						<label for="f2_aan">Rudere (unità collabente, F/2) <button type="button" class="info-btn" onclick="showInfo('ruine', event)">i</button></label>
					</div>
					<div class="conditional hidden" id="f2_details">
						<div class="form-grid">
							<div class="form-group">
								<label>Quota di prezzo nell'atto</label>
								<div class="input-with-unit">
									<input type="number" id="f2_prijs" value="20000" min="0" step="1000" oninput="calculate()">
									<span class="input-unit">€</span>
								</div>
							</div>
						</div>
						<div class="checkbox-row hidden" id="f2_prima_container">
							<input type="checkbox" id="f2_prima" onchange="calculate()">
							<label for="f2_prima">Il rudere diventa la mia prima casa (abitabile entro 3 anni)</label>
						</div>
					</div>
				</div>

				<!-- D/10 -->
				<div class="component">
					<div class="checkbox-row">
						<input type="checkbox" id="d10_aan" onchange="calculate()">
						<label for="d10_aan">Fabbricato rurale strumentale (D/10) <button type="button" class="info-btn" onclick="showInfo('d10', event)">i</button></label>
					</div>
					<div class="conditional hidden" id="d10_details">
						<div class="form-grid">
							<div class="form-group">
								<label>Quota di prezzo nell'atto</label>
								<div class="input-with-unit">
									<input type="number" id="d10_prijs" value="10000" min="0" step="1000" oninput="calculate()">
									<span class="input-unit">€</span>
								</div>
								<div class="range-info" id="d10_hint"></div>
							</div>
							<div class="form-group">
								<label>Rendita (per l'IMU)</label>
								<div class="input-with-unit">
									<input type="number" id="d10_rendita" value="0" min="0" step="10" oninput="calculate()">
									<span class="input-unit">€</span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Uitsplitsing -->
				<div class="component accent hidden" id="uitsplitsing_container">
					<label>Nell'atto il prezzo è indicato separatamente per ciascun bene? <button type="button" class="info-btn" onclick="showInfo('uitsplitsing', event)">i</button></label>
					<div class="toggle-group">
						<input type="radio" name="uitsplitsing" id="split_ja" value="ja" checked onchange="calculate()">
						<label for="split_ja">Sì, prezzi distinti</label>
						<input type="radio" name="uitsplitsing" id="split_nee" value="nee" onchange="calculate()">
						<label for="split_nee">No, un unico prezzo</label>
					</div>
					<div class="range-info">Senza prezzi distinti si applica l'aliquota più elevata all'intero prezzo.</div>
				</div>
			</div>
		</div>

		<!-- SECTION 4: Makelaar & Notaris -->
		<div class="section">
			<div class="section-title"><span class="num">4</span> Agenzia immobiliare e notaio</div>
			<div class="form-grid">
				<div class="form-group">
					<div class="checkbox-row">
						<input type="checkbox" id="makelaar_aan" checked onchange="calculate()">
						<label for="makelaar_aan">Acquisto tramite agenzia immobiliare <button type="button" class="info-btn" onclick="showInfo('makelaar', event)">i</button></label>
					</div>
					<div class="conditional" id="makelaar_details">
						<label>Provvigione (%)</label>
						<div class="input-with-unit">
							<input type="number" id="makelaar_pct" value="3" min="0" max="10" step="0.1" oninput="calculate()">
							<span class="input-unit">%</span>
						</div>
						<div class="range-info">Di solito: 3% – 4% + IVA 22%, a carico dell'acquirente</div>
					</div>
				</div>
				<div class="form-group">
					<label>Spese notarili <button type="button" class="info-btn" onclick="showInfo('notaris', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="notaris_pct" value="1.5" min="0" max="5" step="0.1" oninput="calculate()">
						<span class="input-unit">%</span>
					</div>
					<div class="range-info">Indicativamente 1% – 2% del prezzo (spese e IVA incluse)</div>
					<div class="checkbox-row hidden" id="notaris_pv_container" style="margin-top:0.5rem;">
						<input type="checkbox" id="notaris_pv" onchange="calculate()">
						<label for="notaris_pv">Il notaio applica la riduzione del 30% prevista per il prezzo-valore (non obbligatoria, chiedila)</label>
					</div>
				</div>
			</div>
		</div>

		<!-- SECTION 5: Hypotheek -->
		<div class="section">
			<div class="section-title"><span class="num">5</span> Mutuo (facoltativo)</div>
			<div class="form-group">
				<div class="checkbox-row">
					<input type="checkbox" id="hypotheek_aan" onchange="calculate()">
					<label for="hypotheek_aan">Mutuo ipotecario italiano <button type="button" class="info-btn" onclick="showInfo('hypotheek', event)">i</button></label>
				</div>
				<div class="conditional hidden" id="hypotheek_details">
					<div class="form-grid">
						<div class="form-group">
							<label>Importo del mutuo</label>
							<div class="input-with-unit">
								<input type="number" id="hypotheek_bedrag" value="120000" min="0" step="5000" oninput="calculate()">
								<span class="input-unit">€</span>
							</div>
						</div>
						<div class="form-group">
							<label>Imposta sostitutiva <button type="button" class="info-btn" onclick="showInfo('imposta_sostitutiva', event)">i</button></label>
							<select id="hypotheek_belasting" onchange="calculate()">
								<option value="auto" selected>Automatica (segue la destinazione)</option>
								<option value="0.25">0,25% (prima casa)</option>
								<option value="2">2% (abitazione senza agevolazioni prima casa)</option>
							</select>
							<div class="range-info" id="hypotheek_belasting_info"></div>
						</div>
						<div class="form-group">
							<label>Spese di perizia</label>
							<div class="input-with-unit">
								<input type="number" id="taxatie" value="400" min="0" step="50" oninput="calculate()">
								<span class="input-unit">€</span>
							</div>
							<div class="range-info">Spesso €300 – €400</div>
						</div>
						<div class="form-group">
							<label>Spese bancarie (istruttoria)</label>
							<div class="input-with-unit">
								<input type="number" id="bankkosten" value="1000" min="0" step="100" oninput="calculate()">
								<span class="input-unit">€</span>
							</div>
							<div class="range-info">Spesso 0,5% – 1% del finanziamento</div>
						</div>
						<div class="form-group">
							<label>Notaio per l'atto di mutuo</label>
							<div class="input-with-unit">
								<input type="number" id="hypo_notaris" value="1500" min="0" step="100" oninput="calculate()">
								<span class="input-unit">€</span>
							</div>
							<div class="range-info">Atto separato dalla compravendita; spesso €1.000 – €2.000</div>
						</div>
						<div class="form-group">
							<label>Assicurazione incendio e scoppio</label>
							<div class="input-with-unit">
								<input type="number" id="hypo_verzekering" value="300" min="0" step="50" oninput="calculate()">
								<span class="input-unit">€</span>
							</div>
							<div class="range-info">Obbligatoria con il mutuo; il premio varia da banca a banca</div>
						</div>
					</div>
					<div class="highlight" style="margin-top:0.75rem; background:#FFF3E0; border-left:4px solid #E65100; padding:0.75rem 1rem; border-radius:0 8px 8px 0; font-size:0.88rem; line-height:1.5;">
						<strong>⚠ Importante:</strong> al <em>rogito</em> la banca versa l'importo del mutuo direttamente al venditore. Tutti i costi accessori (imposte di trasferimento, notaio per la compravendita e per il mutuo, agenzia, geometra, interprete) vanno però pagati con <strong>mezzi propri</strong>. Anche la caparra al <em>compromesso</em> (settimane o mesi prima) la paghi interamente tu. In fondo il calcolatore mostra un riepilogo per fase.
						<br><span style="font-size:0.82rem; color:#6B5B4D;">📖 <strong>Guida:</strong> Sezione 2.1.1 (Opzioni di mutuo) · Sezione 5.3 (Rogito)</span>
					</div>
				</div>
			</div>
		</div>

		<!-- SECTION 6: Extras -->
		<div class="section">
			<div class="section-title"><span class="num">6</span> Costi facoltativi e aggiuntivi</div>
			<div class="form-grid">
				<div class="form-group">
					<div class="checkbox-row">
						<input type="checkbox" id="vertaler_aan" checked onchange="calculate()">
						<label for="vertaler_aan">Traduttore/interprete <button type="button" class="info-btn" onclick="showInfo('vertaler', event)">i</button></label>
					</div>
					<div class="conditional" id="vertaler_details">
						<div class="input-with-unit">
							<input type="number" id="vertaler_kosten" value="650" min="0" step="50" oninput="calculate()">
							<span class="input-unit">€</span>
						</div>
						<div class="range-info">€500 – €800</div>
					</div>
				</div>
				<div class="form-group">
					<div class="checkbox-row">
						<input type="checkbox" id="geometra_aan" checked onchange="calculate()">
						<label for="geometra_aan">Perizia tecnica (geometra) <button type="button" class="info-btn" onclick="showInfo('geometra', event)">i</button></label>
					</div>
					<div class="conditional" id="geometra_details">
						<div class="input-with-unit">
							<input type="number" id="geometra_kosten" value="1000" min="0" step="50" oninput="calculate()">
							<span class="input-unit">€</span>
						</div>
						<div class="range-info">Da circa €750 IVA inclusa</div>
					</div>
				</div>
				<div class="form-group">
					<div class="checkbox-row">
						<input type="checkbox" id="advocaat_aan" onchange="calculate()">
						<label for="advocaat_aan">Avvocato / consulente per l'acquisto <button type="button" class="info-btn" onclick="showInfo('advocaat', event)">i</button></label>
					</div>
					<div class="conditional hidden" id="advocaat_details">
						<div class="input-with-unit">
							<input type="number" id="advocaat_kosten" value="1500" min="0" step="100" oninput="calculate()">
							<span class="input-unit">€</span>
						</div>
						<div class="range-info">Variabile, spesso €500 – €2.500+</div>
					</div>
				</div>
				<div class="form-group">
					<div class="checkbox-row">
						<input type="checkbox" id="volmacht_aan" onchange="calculate()">
						<label for="volmacht_aan">Procura notarile <button type="button" class="info-btn" onclick="showInfo('volmacht', event)">i</button></label>
					</div>
					<div class="conditional hidden" id="volmacht_details">
						<div class="input-with-unit">
							<input type="number" id="volmacht_kosten" value="400" min="0" step="50" oninput="calculate()">
							<span class="input-unit">€</span>
						</div>
						<div class="range-info">€200 – €600 (apostille inclusa se firmata presso un notaio olandese)</div>
					</div>
				</div>
			</div>
		</div>

		<!-- SECTION 7: Compromesso -->
		<div class="section">
			<div class="section-title"><span class="num">7</span> Contratto preliminare (compromesso)</div>
			<div class="form-grid">
				<div class="form-group">
					<label>Caparra confirmatoria <button type="button" class="info-btn" onclick="showInfo('caparra', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="caparra_pct" value="10" min="0" max="30" step="1" oninput="calculate()">
						<span class="input-unit">%</span>
					</div>
					<div class="range-info">Di solito: 10% – 20% del prezzo di acquisto</div>
				</div>
				<div class="form-group">
					<label>Acconto (anticipo sul prezzo) <button type="button" class="info-btn" onclick="showInfo('caparra', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="acconto" value="0" min="0" step="1000" oninput="calculate()">
						<span class="input-unit">€</span>
					</div>
					<div class="range-info">Da compilare solo se oltre alla caparra si versa un acconto</div>
				</div>
				<div class="form-group">
					<label>Imposta di registro fissa <button type="button" class="info-btn" onclick="showInfo('registratie_compromesso', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="registratie_compromesso" value="200" min="0" step="50" oninput="calculate()">
						<span class="input-unit">€</span>
					</div>
					<div class="range-info">€200, non scomputabile</div>
				</div>
				<div class="form-group">
					<label>Imposta di bollo <button type="button" class="info-btn" onclick="showInfo('zegelrecht', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="zegelrecht_compromesso" value="32" min="0" step="16" oninput="calculate()">
						<span class="input-unit">€</span>
					</div>
					<div class="range-info">€16 ogni 4 facciate (o 100 righe)</div>
				</div>
			</div>
		</div>

		<!-- RESULTS -->
		<div class="results-section" id="results">
			<div class="section-title"><span class="num">✓</span> Risultato: costi una tantum dell'acquisto</div>
			<div id="results-content"></div>
		</div>

		<!-- ANNUAL COSTS -->
		<div class="annual-section">
			<div class="section-title"><span class="num">↻</span> Stima dei costi fissi annuali</div>
			<div class="form-grid" style="margin-bottom:1rem;">
				<div class="form-group">
					<label>Aliquota IMU del Comune <button type="button" class="info-btn" onclick="showInfo('imu', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="imu_pct" value="0.86" min="0" max="1.14" step="0.01" oninput="calculate()">
						<span class="input-unit">%</span>
					</div>
					<div class="range-info">A seconda del Comune 0% – 1,06% (aliquota di base 0,86%, in via eccezionale 1,14%)</div>
				</div>
				<div class="form-group hidden" id="imu_luxe_container">
					<label>Aliquota IMU abitazione principale di lusso <button type="button" class="info-btn" onclick="showInfo('imu', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="imu_luxe_pct" value="0.5" min="0" max="0.6" step="0.01" oninput="calculate()">
						<span class="input-unit">%</span>
					</div>
					<div class="range-info">Base 0,5%, il Comune può applicare dallo 0% allo 0,6%; detrazione di €200</div>
				</div>
				<div class="form-group hidden" id="imu_grond_container">
					<label>Aliquota IMU terreni agricoli <button type="button" class="info-btn" onclick="showInfo('imu', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="imu_grond_pct" value="0.76" min="0" max="1.06" step="0.01" oninput="calculate()">
						<span class="input-unit">%</span>
					</div>
					<div class="range-info">Aliquota di base 0,76%, il Comune può applicare dallo 0% all'1,06%</div>
				</div>
				<div class="form-group">
					<label>TARI (tassa sui rifiuti) <button type="button" class="info-btn" onclick="showInfo('tari', event)">i</button></label>
					<div class="input-with-unit">
						<input type="number" id="tari" value="350" min="0" step="50" oninput="calculate()">
						<span class="input-unit">€</span>
					</div>
					<div class="range-info">Dipende da superficie e Comune; €150 – €600 all'anno</div>
				</div>
				<div class="form-group full hidden" id="imu_berg_container">
					<div class="checkbox-row">
						<input type="checkbox" id="imu_berg" onchange="calculate()">
						<label for="imu_berg">Il terreno agricolo si trova in un Comune esente da IMU per i terreni agricoli (Comune montano) <button type="button" class="info-btn" onclick="showInfo('imu_berg', event)">i</button></label>
					</div>
				</div>
			</div>
			<div id="annual-results"></div>
			<div class="note-bar" style="margin-top:1rem;">
				<strong>Attenzione:</strong> sull'abitazione principale non paghi l'IMU, ma solo dal momento in cui ci abiti e vi hai la residenza anagrafica. Fino ad allora paghi l'IMU come per una seconda casa. Le abitazioni di lusso (A/1, A/8, A/9) pagano anche come abitazione principale: 0,5% con una detrazione di €200. Per i terreni agricoli e per un giardino censito al catasto terreni vale un'aliquota propria (base 0,76%); le aree fabbricabili seguono l'aliquota comunale indicata sopra. Considera anche le utenze (luce, gas, acqua: €2.000 – €3.000 all'anno) ed eventuali spese condominiali (€50 – €300 al mese).
			</div>
		</div>

		<div class="note-bar">
			<strong>Avvertenza:</strong> questo calcolo è indicativo e si basa sulla guida "Een Huis kopen in Italië" (2026) e sulle norme in vigore a novembre 2026. Gli importi esatti dipendono da regione, Comune, notaio e circostanze individuali. Per un calcolo definitivo rivolgiti sempre a un <em>commercialista</em> o a un notaio del posto. Dal 1° gennaio 2027 entrano in vigore i nuovi Testi Unici, tra cui quello sull'imposta di registro (D.Lgs. 123/2025) e quello sulle sanzioni tributarie (D.Lgs. 173/2024), dopo il rinvio disposto dall'art. 4 D.L. 200/2025. Gli articoli di legge cambieranno quindi numerazione e alcune sanzioni potrebbero cambiare.
		</div>

		<!-- CTA BANNER -->
		<div class="cta-banner">
			<h3 style="color:white;">Vuoi capire esattamente che cosa significano tutti questi importi,<br>e come risparmiare migliaia di euro?</h3>
			<p>Il calcolo qui sopra è solo l'inizio. Nella guida &ldquo;Een Huis kopen in Itali&euml;&rdquo; leggi come funziona esattamente il sistema del <em>prezzo-valore</em>, quando hai diritto e quando no alle agevolazioni <em>prima casa</em>, quanto capitale proprio devi mettere a disposizione e molto altro.</p>
			<img src="Afbeeldingen/3d-boeken/3dhuiskopen.png" alt="Een Huis kopen in Italië">
			<p style="color:white;"><a href="https://stefsmulders.nl/Huis-kopen-Italie.php" class="cta-button">&#x1F4D6; Ordina la guida</a></p>
			<div class="cta-sub"></div>
		</div>

		<div class="calc-footer">
			© 2026 Stef Smulders · <a href="https://stefsmulders.nl">stefsmulders.nl</a> · Sulla base della guida "Een Huis kopen in Italië"
		</div>
	</div>
	</div><!-- /calculator-wrapper -->

</div><!-- #wrapper end -->

<!-- Go To Top -->
<div id="gotoTop" class="icon-angle-up"></div>

<!-- INFO MODAL -->
<div class="info-overlay" id="modal" onclick="if(event.target===this)closeInfo()">
	<div class="info-modal">
		<div class="info-modal-header">
			<h3 id="modal-title"></h3>
			<button class="info-modal-close" onclick="closeInfo()" aria-label="Chiudi">&times;</button>
		</div>
		<div class="info-modal-body" id="modal-body"></div>
	</div>
</div>

<script>
const INFO = {
  aankoopprijs: {
    title: 'Prezzo di acquisto totale',
    body: `<p>Il prezzo totale così come sarà indicato nell'atto di compravendita (<em>rogito</em>), comprese pertinenze e terreni.</p>
    <p>Se compri un'abitazione da un privato, di solito le imposte di trasferimento <strong>non</strong> si pagano su questo prezzo, ma sul valore catastale, molto più basso (<em>prezzo-valore</em>). Se acquisti anche terreno edificabile, terreno agricolo o un rudere, per questi beni l'imposta si paga invece sul prezzo.</p>
    <p>Nell'atto deve sempre figurare il prezzo reale. Chi ne occulta una parte perde il prezzo-valore: l'imposta si applica allora sull'intero prezzo, con una sanzione dal 50% al 100% della differenza d'imposta (regola specifica per il prezzo-valore, art. 1 comma 498 L. 266/2005). La sanzione generale per l'occultazione del corrispettivo (art. 72 DPR 131/1986, dal 2027 art. 43 D.Lgs. 173/2024) è pari al 120% da settembre 2024.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 5.4 (Panoramica dei costi del trasferimento) · Appendice H (Esempi di calcolo) · Appendice J, fatto 8 (Prezzo-valore)</div>`
  },
  rendita: {
    title: 'Rendita catastale',
    body: `<p>La rendita catastale dell'abitazione. Si trova nella <em>visura catastale</em>, che l'agenzia, il notaio o il geometra possono richiedere per te.</p>
    <p>Con questo dato il calcolatore ricava da solo il valore catastale:</p>
    <div class="highlight">
    • Imposte di trasferimento prima casa: rendita × 1,05 × 110 = <strong>rendita × 115,5</strong><br>
    • Imposte di trasferimento seconda casa: rendita × 1,05 × 120 = <strong>rendita × 126</strong><br>
    • IMU: rendita × 1,05 × 160 = <strong>rendita × 168</strong>
    </div>
    <p>Acquisti anche pertinenze con una rendita propria (garage, magazzino, stalla)? Inseriscile a parte al passo 3 e indica qui solo la rendita dell'abitazione.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 4.2.2 (Controllo dei dati catastali / <em>visura catastale</em>) · Sezione 5.4 (Panoramica dei costi del trasferimento)</div>`
  },
  verkoper: {
    title: 'Chi vende?',
    body: `<p><strong>Privato:</strong> paghi l'<em>imposta di registro</em>. Per l'abitazione puoi usare il prezzo-valore.</p>
    <p><strong>Impresa, senza IVA:</strong> la cessione di un'abitazione da parte di un'impresa è di regola esente da IVA. Paghi allora l'imposta di registro, come con un privato, ed è possibile il prezzo-valore.</p>
    <p><strong>Impresa costruttrice, con IVA:</strong> un'impresa di costruzione o di ristrutturazione che vende entro 5 anni dall'ultimazione dei lavori, o che nell'atto opta per l'IVA. Paghi l'IVA sul prezzo, più 3 × €200 di imposte fisse e €320 di bollo e tributi catastali (imposta di bollo €230, tassa ipotecaria €35, voltura €55). Niente prezzo-valore.</p>
    <div class="highlight">
    <strong>Aliquote IVA abitazione:</strong><br>
    • 4%: prima casa (non A/1, A/8, A/9)<br>
    • 10%: altre abitazioni<br>
    • 22%: abitazioni di lusso (A/1, A/8, A/9)
    </div>
    <div class="highlight"><strong>Attenzione:</strong> il terreno edificabile ceduto da un'impresa è soggetto a IVA al 22%. Per un rudere (F/2) venduto da un'impresa il trattamento IVA non è univoco; il calcolatore applica il 22%, fattelo confermare dal notaio. Il terreno agricolo è fuori campo IVA e sconta sempre l'imposta di registro.</div>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 5.4 (Panoramica dei costi del trasferimento) · Appendice H (Esempi di calcolo)</div>`
  },
  koper: {
    title: 'Chi acquista?',
    body: `<p>Se acquisti come <strong>persona fisica</strong>, puoi ottenere le agevolazioni prima casa e il prezzo-valore.</p>
    <p>Se acquisti tramite una <strong>BV, una srl o un'altra persona giuridica</strong>, decadono entrambi. Paghi il 9% sul prezzo e il Fisco può rettificare il valore. Il terreno agricolo sconta il 15%, a meno che la società sia una società agricola riconosciuta.</p>
    <div class="highlight">Se ci vai ad abitare tu, acquistare tramite una società è fiscalmente quasi sempre più caro. La cittadinanza non conta: un olandese o un belga paga quanto un italiano.</div>`
  },
  bestemming: {
    title: 'Prima casa o seconda casa',
    body: `<p><strong>Prima casa</strong> (abitazione principale): imposte molto più basse. <strong>Seconda casa</strong> (casa per le vacanze o seconda abitazione): imposte di trasferimento più alte e IMU annuale.</p>
    <div class="highlight">
    <strong>Aliquote con venditore privato:</strong><br>
    • Prima casa: 2% sul valore catastale<br>
    • Seconda casa: 9% sul valore catastale<br>
    • Minimo: €1.000 di imposta di registro per atto
    </div>
    <p><strong>Requisiti per la prima casa:</strong></p>
    <p>• L'abitazione non rientra nelle categorie A/1, A/8 o A/9<br>
    • Risiedi o lavori nel Comune, oppure vi trasferisci la <em>residenza</em> entro 18 mesi. Va dichiarato nell'atto.<br>
    • Non possiedi un'altra abitazione nello stesso Comune, nemmeno in comunione con il coniuge<br>
    • Se possiedi già altrove in Italia un'abitazione acquistata con le agevolazioni prima casa, la vendi entro 2 anni</p>
    <div class="highlight"><strong>Decadenza:</strong> se non ci riesci, paghi la differenza fino al 9% di imposta di registro (in caso di acquisto con IVA: la differenza rispetto all'IVA al 10%), più interessi e una sanzione del 30% su tale differenza. Se rinunci al trasferimento prima della scadenza dei 18 mesi, paghi solo la differenza e gli interessi (Ris. 105/E/2011). Ciò vale solo per l'impegno a trasferire la residenza, non se i requisiti mancavano già al momento dell'acquisto. La locazione non comporta decadenza. Se vendi l'abitazione entro 5 anni, decadi ugualmente dall'agevolazione, a meno che entro un anno non acquisti una nuova prima casa.</div>
    <div class="highlight"><strong>Proposta, non ancora legge:</strong> il disegno di legge di bilancio 2027 (art. 41, A.C. 3174, presentato alla Camera il 30 ottobre 2026) intende portare a 2 anni sia i 18 mesi per il trasferimento della residenza sia l'anno per il riacquisto dopo la vendita. Finché la legge non è pubblicata in Gazzetta Ufficiale, valgono 18 mesi e 1 anno. Secondo la norma transitoria i termini più lunghi si applicano anche agli acquisti e alle vendite precedenti il cui termine è ancora pendente alla data di entrata in vigore della legge (di norma il 1° gennaio 2027). Un termine già scaduto non si riapre. Testo e numero dell'articolo possono ancora cambiare in Parlamento.</div>
    <p>Le pertinenze e i terreni seguono la propria aliquota (vedi il passo 3).</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 2.1.1 (Opzioni di mutuo, requisito della residenza) · Sezione 5.4 (Panoramica dei costi del trasferimento) · Capitolo 7 (Imposte)</div>`
  },
  luxe: {
    title: 'Abitazioni di lusso (A/1, A/8, A/9)',
    body: `<p>Le abitazioni di categoria catastale <strong>A/1</strong> (signorili), <strong>A/8</strong> (ville) o <strong>A/9</strong> (castelli e palazzi di pregio) sono <strong>sempre escluse</strong> dalle agevolazioni prima casa. Paghi il 9% di imposta di registro; il prezzo-valore resta però possibile.</p>
    <p>Se acquisti con IVA da un'impresa costruttrice, paghi sempre l'<strong>IVA al 22%</strong>, anche se ci vai ad abitare.</p>
    <p>Come abitazione principale paghi comunque l'IMU (0,5%, che il Comune può portare tra lo 0% e lo 0,6%, con una detrazione di €200), mentre per le abitazioni ordinarie è esente. L'aliquota ridotta vale anche per una pertinenza per ciascuna categoria C/2, C/6 e C/7. Neanche il mutuo gode dell'aliquota prima casa: l'imposta sostitutiva è del 2%.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 5.4 (Panoramica dei costi del trasferimento) · Sezioni 7.1 / 7.2 (Imposte sulla seconda casa / sull'abitazione principale)</div>`
  },
  prezzo_valore: {
    title: 'Il sistema del prezzo-valore',
    body: `<p>Il sistema del <em>prezzo-valore</em> è un <strong>grande vantaggio fiscale</strong>: le imposte di trasferimento si pagano sul valore catastale (basso) invece che sul prezzo effettivamente pagato (alto).</p>
    <div class="highlight"><strong>Esempio:</strong> prezzo di acquisto €200.000, rendita €635. Valore catastale seconda casa: €635 × 126 = €80.010. Al 9% paghi €7.201 invece di €18.000.</div>
    <p><strong>Requisiti:</strong></p>
    <p>• L'acquirente è una persona fisica che non agisce nell'esercizio di un'attività d'impresa<br>
    • La cessione è soggetta a imposta di registro, non a IVA<br>
    • Lo chiedi espressamente nell'atto; dopo non è più possibile<br>
    • Con più beni, il prezzo di ciascuno è indicato nell'atto</p>
    <p><strong>Si applica a:</strong> l'abitazione e le pertinenze con rendita propria, e un giardino che è pertinenza dell'abitazione (in tal caso sul reddito dominicale × 112,5).</p>
    <p><strong>Non si applica a:</strong> terreni edificabili, terreni agricoli autonomi, ruderi (F/2, senza rendita), cessioni soggette a IVA e persone giuridiche.</p>
    <div class="highlight"><strong>Altri vantaggi:</strong> il Fisco non può rettificare il valore. La legge prevede anche una riduzione del 30% degli onorari notarili, ma da quando le tariffe notarili sono state abolite nel 2012 non è esigibile. Chiedila quando ricevi il preventivo.<br><strong>Attenzione:</strong> chi occulta una parte del prezzo perde il prezzo-valore, paga l'imposta sull'intero prezzo e una sanzione dal 50% al 100% della differenza d'imposta (art. 1 comma 498 L. 266/2005). La sanzione generale per l'occultazione del corrispettivo (art. 72 DPR 131/1986, dal 2027 art. 43 D.Lgs. 173/2024) è pari al 120% da settembre 2024.</div>
    <div class="highlight">📖 <strong>Guida:</strong> Appendice J, fatto 8 (Il trucco dello sconto fiscale) · Sezione 5.4 (Panoramica dei costi del trasferimento)</div>`
  },
  credito: {
    title: "Credito d'imposta (precedente prima casa)",
    body: `<p>Vendi in Italia un'abitazione acquistata con le agevolazioni prima casa e ne acquisti una nuova come prima casa entro un anno? Allora ottieni come credito d'imposta l'imposta di registro o l'IVA pagata all'epoca.</p>
    <p>Se acquisti prima la nuova abitazione e vendi la vecchia dopo, hai due anni per la vendita (Risposta 197 del 30 luglio 2025).</p>
    <p>Se prima vendi e poi acquisti, il termine resta di un anno (Risposta 297 del 26 novembre 2025). Il disegno di legge di bilancio 2027 (art. 41, A.C. 3174) intende portare anche questo termine a due anni, anche per le vendite il cui anno è ancora in corso alla data di entrata in vigore della legge (di norma il 1° gennaio 2027). Non è ancora legge.</p>
    <div class="highlight">Il credito non supera mai l'imposta di registro o l'IVA dovuta sul nuovo acquisto; l'eccedenza si perde (art. 7 L. 448/1998). Nell'atto puoi scomputarlo solo dall'imposta di registro, non dall'IVA che paghi al venditore. Ciò che non utilizzi nell'atto non viene rimborsato, ma puoi usarlo in seguito in diminuzione dell'imposta di registro, ipotecaria, catastale o di successione su un atto successivo, oppure dell'IRPEF in dichiarazione.</div>`
  },
  onderdelen: {
    title: 'Che cosa acquisti esattamente?',
    body: `<p>In Italia l'immobile non viene tassato in un unico blocco. <strong>Ogni bene ha la propria aliquota e la propria base imponibile</strong>: l'abitazione, ciascuna pertinenza, il giardino, il terreno edificabile e il terreno agricolo.</p>
    <p>Inserisci per ogni bene la quota di prezzo così come figurerà nell'atto. Quanto resta del prezzo totale il calcolatore lo attribuisce all'abitazione.</p>
    <div class="highlight">
    <strong>Aliquote con venditore privato:</strong><br>
    • Abitazione: 2% o 9% sul valore catastale<br>
    • Pertinenze C/2, C/6, C/7: una per categoria al 2% (prima casa), le altre al 9%<br>
    • Terreno edificabile: 9% sul prezzo<br>
    • Terreno agricolo: 15% sul prezzo (agricoltore senza vincolo 9%; con vincolo o in territorio montano: importi fissi)<br>
    • Rudere F/2: 9% sul prezzo (prima casa 2%)<br>
    • Minimo: €1.000 di imposta di registro per l'intero atto
    </div>`
  },
  pertinenze: {
    title: 'Pertinenze',
    body: `<p>Con le agevolazioni prima casa ottieni l'aliquota del 2% per <strong>al massimo una pertinenza per ciascuna categoria</strong>: C/2 (magazzino, ripostiglio, cantina), C/6 (autorimessa, stalla) e C/7 (tettoia). La pertinenza deve essere al servizio dell'abitazione.</p>
    <p>Una seconda pertinenza della stessa categoria sconta il 9%. Può comunque rientrare nel prezzo-valore, se ha una rendita propria e il vincolo pertinenziale con l'abitazione è indicato nell'atto.</p>
    <p>Un fabbricato accessorio a destinazione abitativa (categoria A, per esempio un rustico) è fiscalmente una seconda abitazione: 9%.</p>
    <div class="highlight"><strong>Conta la categoria alla data dell'atto.</strong> Un fabbricato rurale strumentale (D/10) non ottiene mai le agevolazioni prima casa, neppure se in seguito lo fai variare. Fai modificare la categoria dal venditore prima dell'atto, quando è giuridicamente corretto. Un D/10 si inserisce nel bene apposito più in basso (Risposta 566/2021).</div>
    <p><strong>Quota di prezzo:</strong> conta solo se non usi il prezzo-valore o se non indichi prezzi distinti. Compilala comunque, perché determina la quota di prezzo dell'abitazione.</p>`
  },
  tuin: {
    title: 'Giardino o corte',
    body: `<p><strong>Graffato:</strong> se il giardino è censito al catasto fabbricati insieme alla casa, è già compreso nella rendita. Non paghi nulla in più e qui non inserisci nulla.</p>
    <p><strong>Censito a parte al catasto terreni:</strong> il giardino è allora una particella autonoma con un proprio <em>reddito dominicale</em>. Se è dimostrabilmente al servizio dell'abitazione e il vincolo è indicato nell'atto, rientra nel prezzo-valore (Ris. 149/E/2008): 9% sul valore catastale, reddito dominicale × 1,25 × 90 = × 112,5 (art. 52 comma 4 DPR 131/1986, con le successive rivalutazioni di legge).</p>
    <p><strong>Prima casa anche per il giardino?</strong> L'Agenzia delle Entrate dice di no, salvo graffatura al catasto fabbricati. La Cassazione dice di sì, purché il giardino sia civilisticamente e dimostrabilmente al servizio dell'abitazione (art. 817 c.c.; Cass. 2351/2024). La sola contiguità non basta: in Cass. 6160/2025 è rimasto fermo il recupero dell'imposta, perché l'atto non qualificava il terreno come pertinenza e il vincolo con l'abitazione non era provato. Per l'imposta di registro ordinaria (e quindi per il prezzo-valore), Cass. 29065/2025 ha stabilito che i dati catastali non sono decisivi; contano invece fatti come una recinzione comune o una piscina sul terreno. Nemmeno la sola distanza esclude il vincolo (Cass. ord. 13553/2026): decisivo è se il terreno serve in modo dimostrabile e durevole l'abitazione. L'ufficio non sempre segue la giurisprudenza; mettiti in conto un possibile avviso di liquidazione da impugnare.</p>
    <div class="highlight"><strong>Vuoi la certezza?</strong> Fai graffare il giardino all'abitazione dal venditore prima dell'atto (graffatura tramite dichiarazione Docfa). Un appezzamento grande, o un terreno coltivato, non viene accettato come giardino. Inseriscilo come terreno agricolo. Un unico prezzo per casa e terreno senza prova del vincolo pertinenziale porta all'aliquota più alta sull'intero prezzo (art. 23 DPR 131/1986).</div>`
  },
  bouwgrond: {
    title: 'Terreno edificabile',
    body: `<p>Il terreno edificabile sconta <strong>il 9% di imposta di registro sul valore venale</strong>, o sul prezzo se superiore. Niente prezzo-valore. Se a vendere il terreno è un'impresa, si applica l'IVA al 22%.</p>
    <p>Un terreno è fiscalmente edificabile non appena lo strumento urbanistico adottato dal Comune (PGT in Lombardia, PRG altrove) ne consente l'edificazione, anche se il piano non è ancora definitivamente approvato.</p>
    <div class="highlight"><strong>Prima del compromesso richiedi il <em>certificato di destinazione urbanistica</em> (CDU).</strong> Indica, particella per particella, che cosa è edificabile e che cosa è agricolo.</div>
    <div class="highlight">Il Fisco può sempre rettificare il valore di un terreno edificabile. Confronta il tuo prezzo con i valori di riferimento IMU al m² del Comune. Valore dichiarato troppo basso: sanzione del 70% sulla maggiore imposta, se il valore accertato, diminuito di un quarto, supera quello dichiarato (art. 71 DPR 131/1986, dal 2027 art. 42 D.Lgs. 173/2024).</div>`
  },
  landbouwgrond: {
    title: 'Terreno agricolo',
    body: `<p>Chi non è un agricoltore iscritto paga <strong>il 15% di imposta di registro sul prezzo</strong>, con un minimo di €1.000. La cittadinanza non conta. Niente prezzo-valore, e il Fisco può rettificare il valore.</p>
    <p>Alla voce "Regime" verifica se per te vale una delle eccezioni.</p>
    <p>Il <em>reddito dominicale</em> si trova nella visura del catasto terreni. Il calcolatore lo usa solo per l'IMU annuale.</p>`
  },
  landbouw_regime: {
    title: 'Regime del terreno agricolo',
    body: `<p><strong>Acquirente non agricoltore:</strong> 15% di imposta di registro sul prezzo.</p>
    <p><strong>Coltivatore diretto o IAP</strong> iscritto nella relativa gestione previdenziale (INPS): agevolazione <em>piccola proprietà contadina</em>. €200 di imposta di registro, €200 di ipotecaria e 1% di catastale. Decadi dall'agevolazione se entro 5 anni vendi il terreno, lo concedi in affitto o smetti di coltivarlo direttamente. Se hai meno di 40 anni, puoi ottenerla anche dichiarando nell'atto che ti iscriverai nella gestione previdenziale agricola entro 24 mesi (art. 2 comma 4-ter DL 194/2009, dal 2023). Se non ci riesci, l'agevolazione decade e il Fisco recupera l'imposta ordinaria con gli interessi. Chiedi al notaio se si aggiunge anche una sanzione.</p>
    <p><strong>Coltivatore diretto o IAP senza vincolo:</strong> se non vuoi impegnarti per 5 anni, come agricoltore riconosciuto paghi il 9% di imposta di registro invece del 15%.</p>
    <p><strong>Giovani agricoltori</strong> (CD o IAP fino a 41 anni, iscritti nella gestione previdenziale agricola) pagano dal 2024 solo il 60% delle imposte di registro, ipotecaria e catastale (art. 7 L. 36/2024). Il calcolatore non ne tiene conto.</p>
    <p><strong>Territori montani</strong> (art. 9 DPR 601/1973, ripristinato dalla L. 232/2016, dalla L. 197/2022 anche per i non agricoltori): fondi rustici situati ad almeno 700 m di altitudine, inclusi nell'elenco della Commissione censuaria centrale o in un comprensorio di bonifica montana. €200 di imposta di registro e €200 di ipotecaria, esenzione dall'imposta catastale e dal bollo. Vale anche per i non agricoltori, se nell'atto ti impegni a coltivare direttamente il fondo per 5 anni. Se vendi o smetti entro 5 anni, l'agevolazione decade.</p>
    <div class="highlight">Fai verificare dal notaio, particella per particella, se il terreno ha i requisiti. Sottoscrivi il vincolo quinquennale solo se intendi rispettarlo.</div>`
  },
  ruine: {
    title: 'Rudere (F/2, unità collabente)',
    body: `<p>Un rudere non ha rendita. Quindi niente prezzo-valore: <strong>9% di imposta di registro sul prezzo</strong>.</p>
    <p>Con la <strong>Risposta 108/2026</strong> (26 maggio 2026) l'Agenzia delle Entrate ammette le agevolazioni prima casa (2%) per un F/2, a condizione che il fabbricato sia ristrutturato, accatastato come abitazione (non di lusso) e utilizzato come abitazione entro tre anni. Nell'atto devono figurare le consuete dichiarazioni prima casa. L'Agenzia supera così la propria posizione precedente (interpello 357/2019) e si allinea alla Cassazione (ord. 3913/2025). Si tratta della risposta a un singolo interpello, ma mostra l'orientamento che l'Agenzia segue oggi.</p>
    <p>Puoi avere una sola prima casa. Se scegli le agevolazioni prima casa per il rudere e acquisti anche un'abitazione, il calcolatore tratta l'abitazione come seconda casa.</p>
    <div class="highlight">Se il rudere è venduto da un'impresa, il calcolatore applica l'IVA al 22% più imposta di registro, ipotecaria e catastale di €200 ciascuna. È la posizione dell'Agenzia (Risp. 554/2022). Il Notariato (Studio 36-2026/T del 17 luglio 2026) considera il rudere un fabbricato e ritiene la cessione esente da IVA, con imposta di registro proporzionale (9%) e €50 + €50. Solo se vende un'impresa di costruzione o di ristrutturazione entro cinque anni dall'ultimazione dei lavori, o se nell'atto si opta per l'IVA, la cessione è per il Notariato imponibile IVA. In caso di controllo l'ufficio segue l'Agenzia. Fattelo confermare dal notaio. L'aliquota IVA prima casa del 4% per un rudere non è una questione risolta; il calcolatore non la applica.</div>`
  },
  d10: {
    title: 'Fabbricato rurale strumentale (D/10)',
    body: `<p>Un fabbricato rurale strumentale non ottiene mai le agevolazioni prima casa.</p>
    <p>• <strong>Insieme a un terreno agricolo nello stesso atto</strong> e al servizio di quel terreno: il fabbricato segue l'aliquota del terreno (15%, oppure il regime per agricoltori o per i territori montani).<br>
    • <strong>Da solo, o come pertinenza dell'abitazione:</strong> 9% sul prezzo.</p>
    <p>Se il fabbricato è dimostrabilmente al servizio dell'abitazione e il vincolo è indicato nell'atto, il prezzo-valore potrebbe applicarsi (9% sul valore catastale). Il calcolatore applica prudenzialmente il 9% sul prezzo; chiedi al notaio.</p>
    <div class="highlight">Vuoi usare il fabbricato come garage o magazzino dell'abitazione? Fallo variare in C/2 o C/6 prima dell'atto. Alla data dell'atto conta solo la categoria risultante in catasto.</div>
    <div class="highlight">Se il fabbricato è venduto da un'impresa, fiscalmente è un immobile strumentale (art. 10 comma 1 n. 8-ter DPR 633/1972): imposta di registro €200 fissa, ipotecaria 3% e catastale 1% sul prezzo. Se vende l'impresa costruttrice entro cinque anni, o se il venditore opta per l'IVA (alla voce "Impresa costruttrice, con IVA"), si aggiunge l'IVA al 22%. Altrimenti la cessione è esente da IVA.</div>`
  },
  uitsplitsing: {
    title: 'Prezzo distinto per ciascun bene',
    body: `<p>Se un atto riguarda beni soggetti ad aliquote diverse, si applica <strong>l'aliquota più elevata all'intero prezzo</strong>, salvo che per ciascun bene sia stato pattuito un corrispettivo distinto (art. 23 DPR 131/1986).</p>
    <p>La Cassazione applica la regola in modo rigoroso: con un unico prezzo per due abitazioni con terreno ha dato ragione all'ufficio, con l'aliquota del terreno sull'intero importo. Senza un prezzo distinto per l'abitazione non si può applicare neppure il prezzo-valore. Con un terreno agricolo nello stesso atto paghi allora il 15% sull'intero prezzo, anche sull'abitazione.</p>
    <div class="highlight"><strong>Come evitarlo:</strong> indica la ripartizione già nel compromesso e riportala testualmente nell'atto. Usa importi difendibili. Un prezzo del terreno estremamente basso accanto a un prezzo alto dell'abitazione invita il Fisco alla rettifica.</div>`
  },
  makelaar: {
    title: 'Provvigione dell\'agenzia immobiliare',
    body: `<p>In Italia l'agenzia è pagata <strong>sia dall'acquirente sia dal venditore</strong>, ciascuno con la propria provvigione. È una differenza sostanziale rispetto ai Paesi Bassi.</p>
    <p>Di solito la provvigione è del <strong>3% – 4%</strong> del prezzo, maggiorata dell'<strong>IVA al 22%</strong>. Una provvigione del 3% diventa quindi in pratica il 3,66%. L'importo va indicato nell'atto. Dal 12 gennaio 2025 (art. 22 L. 203/2024) al posto dell'importo si può indicare il numero della fattura dell'agenzia con la dichiarazione che corrisponde a quanto effettivamente pagato. Le modalità di pagamento vanno sempre indicate nell'atto.</p>
    <div class="highlight"><strong>Attenzione:</strong> la provvigione è negoziabile, soprattutto per gli immobili più costosi. Concorda sempre la percentuale in anticipo e per iscritto.</div>
    <p><strong>Detrazione:</strong> se diventi fiscalmente residente in Italia e l'immobile diventa la tua abitazione principale (anche se A/1, A/8 o A/9), puoi detrarre dall'IRPEF il 19% della provvigione fino a €1.000. Al massimo €190, e solo con pagamento tracciabile. I non residenti non hanno diritto alla detrazione (art. 24 comma 3 TUIR), salvo che producano in Italia almeno il 75% del loro reddito. Con un reddito superiore a €75.000 la detrazione rientra nel tetto generale degli oneri detraibili (art. 16-ter TUIR).</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 3.4 (Acquistare tramite agenzia immobiliare o intermediario) · Sezione 5.4 (Panoramica dei costi del trasferimento) · Appendice E (Le figure coinvolte)</div>`
  },
  notaris: {
    title: 'Spese notarili',
    body: `<p>Il <em>notaio</em> è obbligatorio per ogni trasferimento immobiliare in Italia. Il notaio:</p>
    <p>• Redige l'atto di compravendita (<em>rogito</em>)<br>
    • Verifica la proprietà, le ipoteche e i pignoramenti<br>
    • Riscuote e versa tutte le imposte<br>
    • Cura la trascrizione e la voltura catastale</p>
    <div class="highlight"><strong>Costi:</strong> indicativamente 1% – 2% del prezzo, spese e IVA incluse. Per le abitazioni più economiche la percentuale può risultare più alta a causa dei costi minimi fissi.</div>
    <p><strong>Prezzo-valore:</strong> per il prezzo-valore la legge prevede una riduzione del 30% degli onorari notarili. Da quando le tariffe notarili sono state abolite nel 2012, la riduzione non è esigibile: alcuni notai la applicano, altri no. Il calcolatore di default non la considera. Spunta l'opzione solo se il tuo notaio conferma la riduzione; viene calcolata sull'importo al netto dell'IVA.</p>
    <p><strong>Attenzione:</strong> il notaio <strong>non</strong> verifica se la casa è stata costruita regolarmente (<em>conformità urbanistica</em>). Va controllato a parte da un <em>geometra</em>.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 5.3 (Il contratto definitivo / <em>rogito</em>) · Sezione 5.4 (Panoramica dei costi) · Appendice J, fatto 1 (L'illusione del notaio) · Appendice E (Le figure coinvolte)</div>`
  },
  hypotheek: {
    title: 'Mutuo ipotecario italiano',
    body: `<p>Un mutuo in Italia è possibile ma <strong>non semplice</strong> per gli stranieri. Punti da considerare:</p>
    <p>• Di norma dal 50 al 70% del valore di perizia per i non residenti, a seconda della banca (la banca ragiona sul valore periziato, non sul prezzo di acquisto)<br>
    • Serve un elevato capitale proprio: 30-50%<br>
    • Molte banche richiedono la <em>conformità urbanistica</em><br>
    • Ti serve un conto corrente italiano<br>
    • La procedura dura mesi; parti per tempo</p>
    <div class="highlight"><strong>Costi del mutuo:</strong><br>
    • Imposta sostitutiva: 0,25% (prima casa o finanziamento a una persona giuridica) o 2% (persona fisica, abitazione senza agevolazioni prima casa)<br>
    • Perizia: €300 – €400<br>
    • Spese di istruttoria: 0,5% – 1% del finanziamento<br>
    • Notaio per l'atto di mutuo separato: €1.000 – €2.000<br>
    • Assicurazione obbligatoria incendio e scoppio, eventualmente polizza vita</div>
    <p><strong>Suggerimento:</strong> chiedi un <em>voucher mutuo</em> o una <em>pre-delibera reddituale</em> prima di fare un'offerta.</p>
    <div class="highlight"><strong>⚠ Attenzione:</strong> al <em>rogito</em> la banca versa l'importo del mutuo direttamente al venditore. Tutti i costi accessori (imposte di trasferimento, notaio per la compravendita e per il mutuo, agenzia, geometra, interprete) e la caparra al compromesso vanno però pagati con <strong>mezzi propri</strong>. In fondo il calcolatore mostra una stima del capitale proprio necessario per ciascuna fase.</div>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 2.1.1 (Opzioni di mutuo, NL, BE e IT) · Sezione 4.2.8 (Titoli edilizi / <em>conformità urbanistica</em>)</div>`
  },
  imposta_sostitutiva: {
    title: 'Imposta sostitutiva sul mutuo',
    body: `<p>Un'imposta una tantum sull'atto di mutuo che sostituisce diversi tributi minori (imposte di registro, di bollo, ipotecaria e catastale).</p>
    <div class="highlight">
    <strong>Aliquota:</strong><br>
    • <strong>0,25%</strong> dell'importo del mutuo per la prima casa (dichiarata nell'atto di mutuo)<br>
    • <strong>2%</strong> per un finanziamento a una persona fisica per l'acquisto o la ristrutturazione di un'abitazione senza agevolazioni prima casa, anche per le abitazioni di lusso A/1, A/8 e A/9<br>
    • <strong>0,25%</strong> per gli altri finanziamenti, anche a una BV, una srl o un'altra impresa (circ. 19/E/2005)
    </div>
    <p>L'imposta si applica solo ai finanziamenti di durata superiore a 18 mesi. Con "Automatica" il calcolatore segue la tua scelta tra prima e seconda casa; un'abitazione di lusso è calcolata al 2%, una persona giuridica allo 0,25%.</p>
    <p>Di solito la banca trattiene l'importo all'erogazione del mutuo.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 2.1.1 (L'imposta sull'atto di mutuo / <em>imposta sostitutiva</em>)</div>`
  },
  vertaler: {
    title: 'Traduttore / interprete',
    body: `<p>Se all'atto (<em>rogito</em>) dichiari di non conoscere l'italiano, la legge notarile prevede due strade (art. 54 e 55 legge notarile):</p>
    <p>• <strong>Atto nella tua lingua:</strong> possibile solo se anche il notaio e i testimoni conoscono quella lingua. Accanto si riporta la traduzione italiana. L'interprete allora non serve, ma il notaio non è tenuto a scegliere questa strada.<br>
    • <strong>Atto in italiano con un interprete</strong>, la strada più comune. L'interprete è scelto dalle parti, deve avere i requisiti richiesti per i testimoni (maggiorenne, non parente, senza interesse nell'atto) e presta giuramento davanti al notaio prima della stipula.</p>
    <p>Non serve un "traduttore giurato" nel senso olandese o belga. Se acquisti con un mutuo, l'interprete deve essere presente anche all'atto di mutuo.</p>
    <div class="highlight"><strong>Costi:</strong> €500 – €800 per interprete e traduzione scritta. Non esiste una tariffa di legge.</div>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 5.3 (Il contratto definitivo / <em>rogito</em>) · Sezione 5.4 (Extra obbligatori o consigliati)</div>`
  },
  geometra: {
    title: 'Perizia tecnica (geometra)',
    body: `<p>Il <em>geometra</em> è un tecnico italiano che esegue controlli essenziali che il notaio <strong>non</strong> fa:</p>
    <p>• <strong>Conformità urbanistica:</strong> verifica che la casa sia stata costruita regolarmente<br>
    • <strong>Conformità catastale:</strong> la planimetria corrisponde allo stato di fatto?<br>
    • <strong>Stato tecnico:</strong> tetto, fondazioni, impianti, umidità<br>
    • <strong>Strumento urbanistico:</strong> puoi ristrutturare o ampliare? Quale terreno è edificabile (CDU)?</p>
    <div class="highlight"><strong>Fortemente consigliato!</strong> Senza il controllo di un <em>geometra</em> puoi comprare una casa con ampliamenti abusivi, di cui il Comune può ordinare la demolizione. Come nuovo proprietario ne rispondi tu.</div>
    <p><strong>Costi:</strong> da circa €750 IVA inclusa, a seconda della complessità. Una dichiarazione Docfa (per esempio per graffare un giardino all'abitazione o variare la categoria di una pertinenza) costa a parte.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 4.2 (Controlli legali e amministrativi) · Sezione 4.2.8 (Titoli edilizi / <em>conformità urbanistica</em>) · Sezione 4.2.10 (Perizia tecnica) · Appendice J, fatto 1 (L'illusione del notaio) · Appendice E (Le figure coinvolte)</div>`
  },
  advocaat: {
    title: 'Avvocato / consulente per l\'acquisto',
    body: `<p>Non obbligatorio, ma può essere utile nelle situazioni complesse:</p>
    <p>• Verifica di contratti e clausole<br>
    • Negoziazione delle condizioni<br>
    • Tutela dei tuoi interessi (il notaio è imparziale, non è il tuo consulente)<br>
    • Questioni successorie o di donazione (rischio da <em>donazione</em>)<br>
    • Acquisti all'asta</p>
    <p><strong>Costi:</strong> variabili, spesso €500 – €2.500+ a seconda della complessità.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 5.4 (Extra obbligatori o consigliati) · Appendice J, fatto 2 (La <em>donazione</em>) · Appendice E (Le figure coinvolte)</div>`
  },
  volmacht: {
    title: 'Procura speciale notarile',
    body: `<p>Se non puoi essere presente di persona alla firma del <em>rogito</em>, puoi delegare una persona di fiducia con una <em>procura speciale</em>.</p>
    <p>Fai redigere o approvare il testo dal notaio italiano prima di firmare. Se firmi presso un notaio olandese, la procura richiede l'apostille, che sostituisce la legalizzazione. Una procura di un notaio belga non richiede l'apostille (Convenzione di Bruxelles del 25 maggio 1987). Se firmi presso un notaio italiano o un consolato italiano, si tratta di un atto italiano e l'apostille non serve. Una procura non redatta in italiano deve essere tradotta.</p>
    <div class="highlight"><strong>Costi:</strong> €200 – €600 (notaio, eventuali apostille e traduzione)</div>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 5.3 (Il contratto definitivo / <em>rogito</em>) · Appendice I, domanda 10 (Comprare senza essere presenti)</div>`
  },
  caparra: {
    title: 'Caparra e acconto',
    body: `<p>Alla firma del contratto preliminare (<em>compromesso</em>) di solito versi un anticipo dal <strong>10% al 20%</strong> del prezzo.</p>
    <div class="highlight">
    <strong>Caparra confirmatoria, effetti giuridici:</strong><br>
    • Se recede l'<strong>acquirente</strong> → perde l'intera caparra<br>
    • Se recede il <strong>venditore</strong> → deve restituire il doppio
    </div>
    <p>L'<strong>acconto</strong> è un semplice anticipo sul prezzo, senza quegli effetti.</p>
    <p>Dal 2025, alla registrazione del compromesso paghi <strong>lo 0,5% sulla somma di caparra e acconto</strong>, oppure l'imposta dell'atto definitivo se inferiore. L'importo viene scomputato al <em>rogito</em> dall'imposta di registro. Se acquisti con IVA da un'impresa costruttrice, l'acconto è soggetto a IVA: lo 0,5% si applica solo alla caparra con un tetto di €200, e l'Agenzia chiede ancora €200 di imposta fissa sull'acconto (il Notariato lo contesta). Lo 0,5% sulla caparra viene allora scomputato dai €200 fissi di imposta di registro dell'atto (circ. 2/E/2025). Il sistema di registrazione non sempre applica il tetto di €200; controlla il calcolo e chiedi, se serve, la compensazione.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 5.2 (Contratto preliminare / <em>compromesso</em>) · Appendice G (Modello di compromesso tradotto) · Appendice I, domanda 11 (Caparra)</div>`
  },
  registratie_compromesso: {
    title: 'Spese di registrazione del compromesso',
    body: `<p>Il contratto preliminare (<em>compromesso</em>) va registrato entro 30 giorni presso l'<em>Agenzia delle Entrate</em>. Paghi:</p>
    <p>• <strong>Imposta fissa:</strong> €200 di imposta di registro, non scomputabile<br>
    • <strong>Imposta di bollo:</strong> €16 ogni 4 facciate (o 100 righe); €155 per un compromesso notarile<br>
    • <strong>0,5% su caparra e acconto</strong> come anticipo sulle imposte di trasferimento, mai più dell'imposta dovuta sull'atto definitivo<br>
    • <strong>Con acquisto soggetto a IVA:</strong> 0,5% solo sulla caparra, con un massimo di €200, più €200 di imposta fissa sull'acconto</p>
    <p>L'anticipo dello 0,5% viene scomputato, al trasferimento definitivo, dall'<em>imposta di registro</em> dovuta. Il calcolatore mostra questa compensazione tra le imposte.</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 2.1 (Preparazione finanziaria) · Sezione 5.2 (Contratto preliminare / <em>compromesso</em>)</div>`
  },
  zegelrecht: {
    title: 'Imposta di bollo',
    body: `<p>Sul compromesso registrato paghi l'imposta di bollo: €16 ogni 4 facciate (o ogni 100 righe). Un compromesso di 8 facciate costa quindi €32.</p>
    <p>Se acquisti da un privato o con imposta di registro proporzionale, l'atto definitivo è esente da bollo. Se acquisti con IVA, all'atto paghi €230 di imposta di bollo, più €35 di tassa ipotecaria e €55 di voltura. Il calcolatore li considera tra le imposte.</p>`
  },
  imu: {
    title: 'IMU (Imposta Municipale Unica)',
    body: `<p>L'imposta comunale sugli immobili. L'aliquota di base è dello <strong>0,86%</strong>. I Comuni possono aumentarla fino all'1,06% o ridurla fino allo 0%. Solo i Comuni che hanno mantenuto la vecchia maggiorazione TASI possono arrivare all'1,14%.</p>
    <div class="highlight">
    <strong>Base imponibile:</strong><br>
    • Abitazione e pertinenze (A, C/2, C/6, C/7): rendita × 1,05 × 160 = <strong>rendita × 168</strong><br>
    • Area fabbricabile: il valore venale al 1° gennaio (il calcolatore usa il prezzo di acquisto)<br>
    • Terreno agricolo (anche un giardino censito al catasto terreni): reddito dominicale × 1,25 × 135 = <strong>reddito dominicale × 168,75</strong>, aliquota di base propria dello 0,76% (il Comune può applicare dallo 0% all'1,06%), da inserire a parte<br>
    • Fabbricato rurale strumentale (D/10): rendita × 1,05 × 65 = <strong>rendita × 68,25</strong>, aliquota 0,1% (il Comune può azzerarla)
    </div>
    <div class="highlight">
    <strong>Esenzione:</strong> per l'abitazione principale non paghi l'IMU sull'abitazione e su una pertinenza per categoria. Occorre che tu vi dimori abitualmente e vi abbia la residenza anagrafica (art. 1 comma 741 L. 160/2019). Le agevolazioni prima casa all'acquisto non bastano: finché non ti sei trasferito e non hai la residenza, paghi l'IMU come per una seconda casa. L'esenzione non vale per le abitazioni di lusso (A/1, A/8, A/9), che pagano lo 0,5% (il Comune può applicare dallo 0% allo 0,6%) con una detrazione di €200, anche su una pertinenza per categoria. I terreni agricoli sono esenti se posseduti e condotti da un coltivatore diretto o IAP (indipendentemente dal regime applicato all'acquisto), e nei Comuni montani.
    </div>
    <p><strong>Attenzione:</strong> il Comune <strong>non</strong> invia alcun avviso di pagamento! L'IMU va calcolata e versata da te in due rate (giugno e dicembre) con il modello <em>F24</em>. Molti stranieri aspettano invano la posta; intanto matura una sanzione del 25% più interessi (ridotta se regolarizzi spontaneamente con il <em>ravvedimento operoso</em>).</p>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 6.2 (Registrazione, tributi locali) · Sezione 7.1 (Seconda casa, IMU) · Sezione 7.2 (Abitazione principale) · Appendice J, fatto 5 (Le imposte si pagano in autoliquidazione)</div>`
  },
  imu_berg: {
    title: 'Esenzione IMU per i terreni agricoli',
    body: `<p>I terreni agricoli situati nei Comuni compresi nell'elenco dei Comuni montani (o parzialmente montani) sono esenti da IMU. Per alcuni Comuni l'esenzione vale solo per una parte del territorio.</p>
    <p>È un regime diverso da quello dei territori montani per le imposte di trasferimento. Chiedi al Comune (ufficio tributi) se l'esenzione vale per le tue particelle.</p>`
  },
  tari: {
    title: 'TARI (Tassa sui Rifiuti)',
    body: `<p>La tassa comunale sui rifiuti, dovuta per ogni abitazione, anche per una seconda casa, persino se ci stai solo una parte dell'anno.</p>
    <p>L'importo dipende dalla <strong>superficie</strong> dell'abitazione e dal <strong>numero di occupanti</strong>.</p>
    <p>Alcuni Comuni prevedono riduzioni se l'abitazione è dimostrabilmente vuota e priva di utenze attive, ma non è un diritto automatico.</p>
    <div class="highlight"><strong>Attenzione in caso di locazione:</strong> appena presenti una SCIA (per B&amp;B, affittacamere, ecc.), la tariffa TARI può passare da domestica a non domestica, fino a 3-4 volte più cara al m².</div>
    <div class="highlight">📖 <strong>Guida:</strong> Sezione 6.2 (Registrazione, tributi locali) · Sezione 7.1 (Seconda casa, TARI) · Capitolo 10 (Affittare, effetti della SCIA sulla TARI)</div>`
  }
};

// Voorkomt dat een klik op de info-knop doorschiet naar de omringende <label>
function showInfo(key, event) {
  if (event) { event.stopPropagation(); event.preventDefault(); }
  const info = INFO[key];
  if (!info) return;
  document.getElementById('modal-title').textContent = info.title;
  document.getElementById('modal-body').innerHTML = info.body;
  document.getElementById('modal').classList.add('active');
}
function closeInfo() {
  document.getElementById('modal').classList.remove('active');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeInfo(); });

// ── HULPFUNCTIES ───────────────────────────────────────────────
function getVal(id) { const el = document.getElementById(id); return el ? (parseFloat(el.value) || 0) : 0; }
function isChecked(id) { const el = document.getElementById(id); return !!(el && el.checked); }
function getRadio(name) { const el = document.querySelector(`input[name="${name}"]:checked`); return el ? el.value : undefined; }
// Italiaanse notatie; useGrouping 'always' zodat ook €1.000 een punt krijgt
function fmt(n) { const r = Math.round(n); return (r < 0 ? '−€' : '€') + Math.abs(r).toLocaleString('it-IT', { useGrouping: 'always' }); }
function fmt2(n) { return '€' + n.toLocaleString('it-IT', { minimumFractionDigits: 0, maximumFractionDigits: 2, useGrouping: 'always' }); }
function nl(x) { return String(x).replace('.', ','); }
function show(id, on) { const el = document.getElementById(id); if (el) el.classList.toggle('hidden', !on); }
function setText(id, txt) { const el = document.getElementById(id); if (el) el.textContent = txt; }

const COEF_PRIMA = 115.5;    // 1,05 × 110
const COEF_SECONDA = 126;    // 1,05 × 120
const COEF_IMU = 168;        // 1,05 × 160
const COEF_TERRENO = 112.5;  // 1,25 × 90 (prezzo-valore tuin)
const COEF_IMU_TERRENO = 168.75; // 1,25 × 135
const COEF_IMU_D = 68.25;        // 1,05 × 65 (groep D, behalve D/5)
const PERT_CATS = ['C/2', 'C/6', 'C/7'];

// ── BIJGEBOUWEN ────────────────────────────────────────────────
const CATS = [
  { v: 'C/2', t: 'C/2 magazzino, ripostiglio, cantina' },
  { v: 'C/6', t: 'C/6 autorimessa, stalla' },
  { v: 'C/7', t: 'C/7 tettoia' },
  { v: 'A',   t: 'A rustico a destinazione abitativa' }
];
const bijgebouwen = [];
let bgSeq = 0;

function addBijgebouw(cat) {
  bijgebouwen.push({ id: ++bgSeq, cat: cat || 'C/2', rendita: 50, prijs: 5000 });
  renderBijgebouwen();
  calculate();
}
function removeBijgebouw(id) {
  const i = bijgebouwen.findIndex(b => b.id === id);
  if (i > -1) bijgebouwen.splice(i, 1);
  renderBijgebouwen();
  calculate();
}
function setBijgebouw(id, field, value) {
  const b = bijgebouwen.find(x => x.id === id);
  if (!b) return;
  b[field] = field === 'cat' ? value : (parseFloat(value) || 0);
  calculate();
}
function renderBijgebouwen() {
  const box = document.getElementById('bijgebouwen_list');
  if (!bijgebouwen.length) {
    box.innerHTML = '<div class="range-info" style="margin-bottom:0.6rem;">Nessuna pertinenza aggiunta.</div>';
    return;
  }
  box.innerHTML = bijgebouwen.map((b, i) => `
    <div class="bg-row">
      <div class="bg-num">${i + 1}</div>
      <div class="bg-cat">
        <label class="mini">Categoria</label>
        <select onchange="setBijgebouw(${b.id},'cat',this.value)">
          ${CATS.map(c => `<option value="${c.v}"${c.v === b.cat ? ' selected' : ''}>${c.t}</option>`).join('')}
        </select>
      </div>
      <div>
        <label class="mini">Rendita</label>
        <div class="input-with-unit"><input type="number" min="0" step="5" value="${b.rendita}" oninput="setBijgebouw(${b.id},'rendita',this.value)"><span class="input-unit">€</span></div>
      </div>
      <div>
        <label class="mini">Quota di prezzo</label>
        <div class="input-with-unit"><input type="number" min="0" step="500" value="${b.prijs}" oninput="setBijgebouw(${b.id},'prijs',this.value)"><span class="input-unit">€</span></div>
      </div>
      <button type="button" class="bg-remove" onclick="removeBijgebouw(${b.id})" title="Rimuovi" aria-label="Rimuovi pertinenza">&times;</button>
    </div>`).join('');
}

// ── INVOER INLEZEN ─────────────────────────────────────────────
function readInputs() {
  const v = {};
  v.prijs = getVal('aankoopprijs');
  v.rendita = getVal('rendita');
  v.verkoper = getRadio('verkoper') || 'particulier';
  v.iva = v.verkoper === 'bedrijf_iva';
  v.onderneming = v.verkoper !== 'particulier';
  v.rechtspersoon = getRadio('koper') === 'rechtspersoon';
  v.prima = getRadio('bestemming') === 'prima' && !v.rechtspersoon;
  v.luxe = isChecked('luxe');
  v.pvAllowed = !v.iva && !v.rechtspersoon;
  v.pvWanted = isChecked('prezzo_valore');
  v.credito = (v.prima && isChecked('credito_aan')) ? getVal('credito_bedrag') : 0;

  v.onderdelen = isChecked('onderdelen_aan');
  const on = id => v.onderdelen && isChecked(id);
  v.bijgebouwen = v.onderdelen ? bijgebouwen.slice() : [];
  v.tuin = on('tuin_aan') ? { rd: getVal('tuin_rd'), prijs: getVal('tuin_prijs'), pert: isChecked('tuin_pert'), cass: isChecked('tuin_cass') } : null;
  v.bouw = on('bouw_aan') ? { m2: getVal('bouw_m2'), prijs: getVal('bouw_prijs') } : null;
  v.landb = on('landb_aan') ? { ha: getVal('landb_ha'), prijs: getVal('landb_prijs'), rd: getVal('landb_rd'), regime: document.getElementById('landb_regime').value } : null;
  v.f2 = on('f2_aan') ? { prijs: getVal('f2_prijs'), prima: isChecked('f2_prima') && v.prima } : null;
  v.d10 = on('d10_aan') ? { prijs: getVal('d10_prijs'), rendita: getVal('d10_rendita') } : null;

  v.overige = v.bijgebouwen.reduce((s, b) => s + b.prijs, 0)
    + (v.tuin ? v.tuin.prijs : 0) + (v.bouw ? v.bouw.prijs : 0) + (v.landb ? v.landb.prijs : 0)
    + (v.f2 ? v.f2.prijs : 0) + (v.d10 ? v.d10.prijs : 0);
  v.woningPrijs = Math.max(0, v.prijs - v.overige);
  v.overschot = v.overige > v.prijs;
  v.woningExists = !v.onderdelen || v.rendita > 0 || v.woningPrijs > 0;
  v.f2Conflict = !!(v.f2 && v.f2.prima && v.woningExists);
  v.primaWoning = v.prima && !v.luxe && !v.f2Conflict;
  v.hasExtra = v.onderdelen && !!(v.bijgebouwen.length || v.tuin || v.bouw || v.landb || v.f2 || v.d10);
  v.split = !v.hasExtra || v.iva || getRadio('uitsplitsing') !== 'nee';
  return v;
}

// ── OVERDRACHTSBELASTING PER ONDERDEEL ─────────────────────────
function computeTaxes(v, split) {
  const L = [], W = [];
  let regProp = 0, woningTax = 0, agriCat = 0;
  let prop = false, fixIva = false, agri = false, berg = false, pvUsed = false, d10Strum = false;
  let fixedReg = 0;
  const usePV = v.pvAllowed && v.pvWanted && split;

  const addReg = (label, amt, isWoning) => { L.push({ label, amount: amt }); regProp += amt; prop = true; if (isWoning) woningTax += amt; };
  const addIva = (label, amt, isWoning) => { L.push({ label, amount: amt }); fixIva = true; if (isWoning) woningTax += amt; };
  const ivaRate = primaLike => v.luxe ? 22 : (primaLike ? 4 : 10);
  const seen = {};
  const bgPrima = b => {
    const isPert = PERT_CATS.includes(b.cat);
    const ok = v.primaWoning && isPert && !seen[b.cat];
    if (isPert) seen[b.cat] = true;
    return ok;
  };

  // Grond die geen pertinenza is: 15% voor wie geen CD of IAP is; een CD of IAP buiten de bijzondere regelingen betaalt 9%
  const agriKoper = !!(v.landb && (v.landb.regime === 'agrarier' || v.landb.regime === 'cd9'));
  const grondRate = agriKoper ? 9 : 15;

  // Onderdelen die een onderneming verkoopt met IVA of in het regime voor bedrijfsgebouwen (los van art. 23)
  const ondernemingDelen = () => {
    let deel = 0;
    if (v.bouw && v.onderneming) {
      addIva(`Terreno edificabile: IVA 22% su ${fmt(v.bouw.prijs)}`, v.bouw.prijs * 0.22, false);
      deel += v.bouw.prijs;
    }
    if (v.f2 && v.onderneming) {
      addIva(`Rudere (F/2): IVA 22% su ${fmt(v.f2.prijs)}`, v.f2.prijs * 0.22, false);
      W.push('Il trattamento IVA di un rudere (F/2) venduto da un\'impresa è controverso. Il calcolatore segue l\'Agenzia (Risp. 554/2022): IVA al 22% più €200 di imposta di registro, ipotecaria e catastale. Il Notariato (Studio 36-2026/T) ritiene la cessione esente (in tal caso 9% di imposta di registro e €50 + €50). Fattelo confermare dal notaio.');
      deel += v.f2.prijs;
    }
    if (v.d10 && v.onderneming) {
      // Bedrijfsgebouw van een onderneming: registro €200, ipotecaria 3%, catastale 1% (+22% IVA bij optie of bouwer binnen 5 jaar)
      const p = v.d10.prijs;
      d10Strum = true;
      if (v.iva) { L.push({ label: `Fabbricato strumentale D/10: IVA 22% su ${fmt(p)}`, amount: p * 0.22 }); }
      L.push({ label: `Fabbricato strumentale D/10 venduto da un'impresa: imposta di registro fissa €200`, amount: 200 });
      L.push({ label: `Fabbricato strumentale D/10: ipotecaria 3% + catastale 1% su ${fmt(p)}`, amount: p * 0.04 });
      fixedReg += 200;
      W.push(v.iva
        ? 'Il fabbricato strumentale (D/10) è ceduto con IVA al 22%. Si aggiungono imposta di registro €200, ipotecaria 3% e catastale 1% (regime degli immobili strumentali).'
        : 'Il fabbricato strumentale (D/10) venduto da un\'impresa è esente da IVA, ma paghi imposta di registro €200, ipotecaria 3% e catastale 1% (regime degli immobili strumentali). Se il venditore opta comunque per l\'IVA, si aggiunge il 22%.');
      deel += p;
    }
    return deel;
  };

  if (!split) {
    // Art. 23 DPR 131/1986: hoogste tarief over de hele prijs, geen prezzo-valore.
    // Delen met IVA of met het regime voor bedrijfsgebouwen vallen daarbuiten en worden apart belast.
    const apart = ondernemingDelen();
    const basis = Math.max(0, v.prijs - apart);
    const rates = [];
    if (v.woningExists) rates.push(v.primaWoning ? 2 : 9);
    v.bijgebouwen.forEach(b => rates.push(bgPrima(b) ? 2 : 9));
    if (v.tuin) rates.push(v.tuin.pert ? ((v.primaWoning && v.tuin.cass) ? 2 : 9) : grondRate);
    if (v.bouw && !v.onderneming) rates.push(9);
    if (v.landb) rates.push(grondRate);
    if (v.f2 && !v.onderneming) rates.push(v.f2.prima ? 2 : 9);
    if (v.d10 && !v.onderneming) rates.push(v.landb ? grondRate : 9);
    const r = Math.max(0, ...rates);
    const wat = apart > 0 ? `sul resto del prezzo, ${fmt(basis)}` : `sull'intero prezzo di ${fmt(v.prijs)}`;
    addReg(`Imposta di registro ${r}% ${wat} (aliquota più alta, perché il prezzo non è ripartito)`, basis * r / 100, false);
    W.push('Il prezzo non è ripartito per ciascun bene. Si applica quindi l\'aliquota più alta all\'intero prezzo e non spetta il prezzo-valore.');
    if (v.landb && (v.landb.regime === 'agrarier' || v.landb.regime === 'berg')) W.push(`Senza prezzi distinti non puoi applicare al terreno agricolo il regime per agricoltori o per i territori montani. ${agriKoper ? 'Come coltivatore diretto o IAP paghi allora il 9% sul terreno, non il 15%.' : ''}`.trim());
    if (apart > 0) W.push('I beni che un\'impresa vende con IVA o come immobili strumentali hanno in pratica sempre un prezzo proprio (la fattura). Il calcolatore li tassa a parte.');
  } else {
    // Woonhuis
    if (v.woningExists) {
      const naam = 'Abitazione';
      const pd = v.onderdelen ? `sulla quota di prezzo ${fmt(v.woningPrijs)}` : `sul prezzo di acquisto ${fmt(v.woningPrijs)}`;
      const tag = v.primaWoning ? ', prima casa' : (v.f2Conflict ? ', seconda casa' : '');
      if (v.iva) {
        const r = ivaRate(v.primaWoning);
        addIva(`${naam}${tag}: IVA ${r}% ${pd}`, v.woningPrijs * r / 100, true);
      } else {
        const r = v.primaWoning ? 2 : 9;
        if (usePV && v.rendita > 0) {
          const c = v.primaWoning ? COEF_PRIMA : COEF_SECONDA;
          const base = v.rendita * c;
          pvUsed = true;
          addReg(`${naam}${tag}: imposta di registro ${r}% sul valore catastale ${fmt(base)} (rendita × ${nl(c)})`, base * r / 100, true);
        } else {
          if (usePV) W.push('Inserisci la rendita dell\'abitazione per applicare il prezzo-valore.');
          addReg(`${naam}${tag}: imposta di registro ${r}% ${pd}`, v.woningPrijs * r / 100, true);
        }
      }
    }

    // Bijgebouwen
    v.bijgebouwen.forEach((b, i) => {
      const p = bgPrima(b);
      const isPert = PERT_CATS.includes(b.cat);
      const extra = (isPert && v.primaWoning && !p) ? ', seconda della stessa categoria' : '';
      const naam = `Pertinenza ${i + 1} (${b.cat === 'A' ? 'rustico, cat. A' : b.cat}${p ? ', prima casa' : ''}${extra})`;
      if (v.iva) {
        const r = ivaRate(p);
        addIva(`${naam}: IVA ${r}% sulla quota di prezzo ${fmt(b.prijs)}`, b.prijs * r / 100, p);
      } else {
        const r = p ? 2 : 9;
        if (usePV && b.rendita > 0) {
          const c = p ? COEF_PRIMA : COEF_SECONDA;
          const base = b.rendita * c;
          pvUsed = true;
          addReg(`${naam}: imposta di registro ${r}% su ${fmt(base)} (rendita × ${nl(c)})`, base * r / 100, p);
        } else {
          addReg(`${naam}: imposta di registro ${r}% sulla quota di prezzo ${fmt(b.prijs)}`, b.prijs * r / 100, p);
        }
      }
    });

    // Tuin apart in het landbouwkadaster
    if (v.tuin) {
      const t = v.tuin;
      if (t.pert) {
        const prima2 = v.primaWoning && t.cass;
        const r = prima2 ? 2 : 9;
        if (v.iva) {
          const ri = ivaRate(prima2);
          addIva(`Giardino (pertinenza): IVA ${ri}% sulla quota di prezzo ${fmt(t.prijs)}`, t.prijs * ri / 100, prima2);
        } else if (usePV && t.rd > 0) {
          const base = t.rd * COEF_TERRENO;
          pvUsed = true;
          addReg(`Giardino (pertinenza): imposta di registro ${r}% su ${fmt(base)} (reddito dominicale × 112,5)`, base * r / 100, prima2);
        } else {
          addReg(`Giardino (pertinenza): imposta di registro ${r}% sulla quota di prezzo ${fmt(t.prijs)}`, t.prijs * r / 100, prima2);
        }
        if (prima2) W.push('L\'aliquota prima casa per un giardino censito a parte al catasto terreni segue l\'orientamento della Cassazione. L\'Agenzia delle Entrate non sempre lo accetta. Per prudenza, calcola anche con il 9%.');
      } else {
        addReg(`Giardino (non pertinenza, quindi terreno agricolo): imposta di registro ${grondRate}% sulla quota di prezzo ${fmt(t.prijs)}`, t.prijs * grondRate / 100, false);
      }
    }

    // Bouwgrond, ruïne en D/10 van een onderneming (IVA of regime bedrijfsgebouwen)
    ondernemingDelen();

    // Bouwgrond
    if (v.bouw) {
      if (!v.onderneming) addReg(`Terreno edificabile: imposta di registro 9% su ${fmt(v.bouw.prijs)}`, v.bouw.prijs * 0.09, false);
      if (v.bouw.m2 > 0 && v.bouw.prijs > 0 && v.bouw.prijs / v.bouw.m2 < 10) {
        W.push(`Il terreno edificabile risulta a ${fmt2(v.bouw.prijs / v.bouw.m2)} al m². È molto basso. Il Fisco può rettificare il valore (sanzione del 70% sulla maggiore imposta se il valore accertato, diminuito di un quarto, supera quello dichiarato). Confronta con i valori di riferimento IMU del Comune.`);
      }
    }

    // Landbouwgrond
    if (v.landb) {
      const l = v.landb;
      if (l.regime === 'agrarier') { agri = true; agriCat += l.prijs * 0.01; }
      else if (l.regime === 'berg') { berg = true; }
      else if (l.regime === 'cd9') addReg(`Terreno agricolo, acquirente CD o IAP senza vincolo quinquennale: imposta di registro 9% su ${fmt(l.prijs)}`, l.prijs * 0.09, false);
      else addReg(`Terreno agricolo: imposta di registro 15% su ${fmt(l.prijs)}`, l.prijs * 0.15, false);
      if (l.ha > 0 && l.prijs > 0 && l.prijs / l.ha < 3000 && (l.regime === 'standaard' || l.regime === 'cd9')) {
        W.push(`Il terreno agricolo risulta a ${fmt(l.prijs / l.ha)} per ettaro. Verifica che sia realistico; il Fisco può rettificare il valore di un terreno agricolo ceduto da solo.`);
      }
    }

    // Ruïne F/2
    if (v.f2) {
      if (!v.onderneming) {
        const r = v.f2.prima ? 2 : 9;
        addReg(`Rudere (F/2)${v.f2.prima ? ', prima casa' : ''}: imposta di registro ${r}% su ${fmt(v.f2.prijs)}`, v.f2.prijs * r / 100, v.f2.prima);
        if (v.f2.prima) W.push('Le agevolazioni prima casa per il rudere spettano solo se entro tre anni il fabbricato è ristrutturato, accatastato come abitazione e utilizzato come tale (Risposta 108/2026). Altrimenti segue il recupero dell\'imposta fino al 9%, con interessi e sanzione.');
      }
      if (v.f2Conflict) W.push('Puoi avere una sola prima casa. Poiché scegli le agevolazioni prima casa per il rudere, il calcolatore tratta l\'abitazione come seconda casa. Eccezione: se il rudere e l\'abitazione vengono uniti in un\'unica abitazione, la prima casa può valere per entrambi. Chiedi al notaio.');
    }

    // Agrarisch bedrijfsgebouw D/10
    if (v.d10) {
      const p = v.d10.prijs;
      if (v.onderneming) {
        // al belast in ondernemingDelen()
      } else if (v.landb) {
        if (v.landb.regime === 'agrarier') agriCat += p * 0.01;
        else if (v.landb.regime === 'cd9') addReg(`Fabbricato strumentale D/10 (con il terreno agricolo): imposta di registro 9% su ${fmt(p)}`, p * 0.09, false);
        else if (v.landb.regime !== 'berg') addReg(`Fabbricato strumentale D/10 (con il terreno agricolo): imposta di registro 15% su ${fmt(p)}`, p * 0.15, false);
      } else {
        addReg(`Fabbricato strumentale D/10: imposta di registro 9% su ${fmt(p)}`, p * 0.09, false);
      }

    }
  }

  // Minimum en vaste bedragen
  if (prop && regProp < 1000) {
    const d = 1000 - regProp;
    L.push({ label: 'Integrazione fino al minimo di €1.000 di imposta di registro per atto', amount: d });
    regProp = 1000;
  }
  const metD10 = (v.d10 && v.landb && !v.onderneming) ? ' e D/10' : '';
  if (prop) L.push({ label: 'Imposte ipotecaria e catastale (€50 + €50, fisse)', amount: 100 });
  if (fixIva) { L.push({ label: 'Imposte fisse con IVA (registro, ipotecaria e catastale, €200 ciascuna)', amount: 600 }); fixedReg += 200; }
  // Bij een akte met IVA of met vaste registro geldt de vrijstelling van art. 10 lid 3 D.Lgs. 23/2011 niet
  if (fixIva || d10Strum) L.push({ label: 'Imposta di bollo €230, tassa ipotecaria €35 e voltura catastale €55 (solo con IVA o imposta di registro fissa)', amount: 320 });
  if (agri) {
    L.push({ label: `Terreno agricolo${metD10} come agricoltore: imposta di registro €200 + ipotecaria €200`, amount: 400 });
    L.push({ label: `Terreno agricolo${metD10} come agricoltore: imposta catastale 1%`, amount: agriCat });
    fixedReg += 200;
  }
  if (berg) { L.push({ label: `Terreno agricolo${metD10} in territorio montano: imposta di registro €200 + ipotecaria €200`, amount: 400 }); fixedReg += 200; }
  if ([prop, fixIva, agri, berg, d10Strum].filter(Boolean).length > 1) {
    W.push('Questo atto combina regimi fiscali diversi. Quante volte si applicano le imposte fisse varia da notaio a notaio. Considera gli importi fissi come indicativi.');
  }

  const total = L.reduce((s, x) => s + x.amount, 0);
  return { L, W, total, registroDue: regProp + fixedReg, woningTax, pvUsed };
}

// ── ZICHTBAARHEID VAN VELDEN ───────────────────────────────────
function updateVisibility(v) {
  show('makelaar_details', isChecked('makelaar_aan'));
  show('hypotheek_details', isChecked('hypotheek_aan'));
  show('vertaler_details', isChecked('vertaler_aan'));
  show('geometra_details', isChecked('geometra_aan'));
  show('advocaat_details', isChecked('advocaat_aan'));
  show('volmacht_details', isChecked('volmacht_aan'));

  show('prezzo_valore_container', v.pvAllowed);
  show('notaris_pv_container', v.pvAllowed && v.pvWanted);
  show('imu_luxe_container', v.prima && v.luxe);
  show('bestemming_container', !v.rechtspersoon);
  show('rechtspersoon_note', v.rechtspersoon);
  show('credito_container', v.prima);
  show('credito_details', isChecked('credito_aan'));

  show('onderdelen_details', v.onderdelen);
  show('tuin_details', isChecked('tuin_aan'));
  show('tuin_cass_container', isChecked('tuin_pert') && v.prima && !v.luxe);
  show('bouw_details', isChecked('bouw_aan'));
  show('landb_details', isChecked('landb_aan'));
  show('f2_details', isChecked('f2_aan'));
  show('f2_prima_container', v.prima);
  show('d10_details', isChecked('d10_aan'));
  show('uitsplitsing_container', v.hasExtra && !v.iva);
  show('imu_berg_container', !!(v.landb || v.tuin));
  show('imu_grond_container', !!(v.landb || v.tuin));

  setText('woning_rest', fmt(v.woningPrijs));
  const bar = document.getElementById('woning_rest_bar');
  if (bar) {
    bar.classList.toggle('warn', v.overschot);
    if (v.overschot) bar.innerHTML = `<strong>Attenzione:</strong> i beni insieme (${fmt(v.overige)}) costano più del prezzo di acquisto totale. Correggi gli importi.`;
    else bar.innerHTML = `Quota di prezzo dell'abitazione (prezzo di acquisto meno i beni qui sotto): <strong id="woning_rest">${fmt(v.woningPrijs)}</strong>`;
  }

  const c = v.primaWoning ? COEF_PRIMA : COEF_SECONDA;
  setText('rendita_info', v.rendita > 0
    ? `Valore catastale per il trasferimento: ${fmt(v.rendita * c)} (× ${nl(c)}) · Base imponibile IMU: ${fmt(v.rendita * COEF_IMU)} (× 168)`
    : 'Si trova nella visura catastale');

  setText('bouw_hint', (v.bouw && v.bouw.m2 > 0) ? `${fmt2(v.bouw.prijs / v.bouw.m2)} al m²` : '');
  setText('landb_hint', (v.landb && v.landb.ha > 0) ? `${fmt(v.landb.prijs / v.landb.ha)} per ettaro` : '');
  setText('d10_hint', v.onderneming
    ? 'Il venditore è un\'impresa: imposta di registro €200, ipotecaria 3% e catastale 1%' + (v.iva ? ', più IVA 22%.' : '.')
    : (isChecked('landb_aan')
      ? 'Segue l\'aliquota del terreno agricolo compreso in questo atto.'
      : '9% sul prezzo (nessun terreno agricolo in questo atto).'));

  const hsel = document.getElementById('hypotheek_belasting').value;
  setText('hypotheek_belasting_info', hsel === 'auto' ? `Ora: ${v.rechtspersoon ? '0,25% (finanziamento a una persona giuridica)' : ((v.prima && !v.luxe) ? '0,25% (prima casa)' : (v.prima && v.luxe ? '2% (abitazione di lusso, senza agevolazioni prima casa)' : '2%'))}` : '');
}

// ── HOOFDBEREKENING ────────────────────────────────────────────
function calculate() {
  const v = readInputs();
  updateVisibility(v);

  const prijs = v.prijs;
  const tax = computeTaxes(v, v.split);
  const alt = (v.hasExtra && !v.iva) ? computeTaxes(v, !v.split) : null;

  // ── COMPROMESSO ──────────────────────────────────────────────
  const caparra = prijs * getVal('caparra_pct') / 100;
  const acconto = getVal('acconto');
  const voorschotBasis = v.iva ? caparra : caparra + acconto;
  // 0,5% of, als dat lager is, de belasting op de definitieve akte (bij btw: de vaste €200)
  const voorschotBruto = voorschotBasis * 0.005;
  const voorschot = Math.min(voorschotBruto, tax.registroDue);
  const voorschotBegrensd = voorschot < voorschotBruto;
  const registratieVast = getVal('registratie_compromesso');
  const zegelrecht = getVal('zegelrecht_compromesso');
  // Btw-koop: de Agenzia rekent €200 vast recht over een btw-plichtig acconto (door het notariaat betwist)
  const accontoIvaVast = (v.iva && acconto > 0) ? 200 : 0;
  const totaalCompr = registratieVast + zegelrecht + voorschot + accontoIvaVast;

  // Verrekening voorschot en credito d'imposta
  const verrekend = Math.min(voorschot, tax.registroDue);
  // Art. 7 L. 448/1998: tegoed nooit hoger dan de registro of IVA op de nieuwe woning; het meerdere vervalt.
  // In de akte alleen af te trekken van de registro (niet van de IVA); de rest later verrekenen.
  const creditoCap = Math.min(v.credito, tax.woningTax);
  const creditoUsed = Math.min(creditoCap, Math.max(0, tax.registroDue - verrekend));
  const creditoLater = creditoCap - creditoUsed;
  const creditoVervalt = v.credito - creditoCap;
  const totaalBelasting = tax.total - verrekend - creditoUsed;

  // ── MAKELAAR ─────────────────────────────────────────────────
  let makelaar = 0, makelaarBtw = 0;
  if (isChecked('makelaar_aan')) {
    makelaar = prijs * getVal('makelaar_pct') / 100;
    makelaarBtw = makelaar * 0.22;
  }
  const totaalMakelaar = makelaar + makelaarBtw;

  // ── NOTARIS ──────────────────────────────────────────────────
  const notarisBruto = prijs * getVal('notaris_pct') / 100;
  const notarisKorting = (tax.pvUsed && isChecked('notaris_pv')) ? (notarisBruto / 1.22) * 0.3 : 0;
  const notaris = notarisBruto - notarisKorting;

  // ── HYPOTHEEK ────────────────────────────────────────────────
  let hypoImposta = 0, hypoTaxatie = 0, hypoBankkosten = 0, hypoNotaris = 0, hypoVerzekering = 0, hypPct = 0;
  if (isChecked('hypotheek_aan')) {
    const sel = document.getElementById('hypotheek_belasting').value;
    hypPct = sel === 'auto' ? ((v.rechtspersoon || (v.prima && !v.luxe)) ? 0.25 : 2) : parseFloat(sel);
    hypoImposta = getVal('hypotheek_bedrag') * hypPct / 100;
    hypoTaxatie = getVal('taxatie');
    hypoBankkosten = getVal('bankkosten');
    hypoNotaris = getVal('hypo_notaris');
    hypoVerzekering = getVal('hypo_verzekering');
  }
  const totaalHypotheek = hypoImposta + hypoTaxatie + hypoBankkosten + hypoNotaris + hypoVerzekering;

  // ── EXTRAS ───────────────────────────────────────────────────
  const vertaler = isChecked('vertaler_aan') ? getVal('vertaler_kosten') : 0;
  const geometra = isChecked('geometra_aan') ? getVal('geometra_kosten') : 0;
  const advocaat = isChecked('advocaat_aan') ? getVal('advocaat_kosten') : 0;
  const volmacht = isChecked('volmacht_aan') ? getVal('volmacht_kosten') : 0;
  const totaalExtras = vertaler + geometra + advocaat + volmacht;

  // ── TOTAAL ───────────────────────────────────────────────────
  const totaal = totaalBelasting + totaalMakelaar + notaris + totaalHypotheek + totaalExtras + totaalCompr;
  const pctVanPrijs = prijs > 0 ? (totaal / prijs * 100).toFixed(1).replace('.', ',') : 0;

  // ── RENDER ───────────────────────────────────────────────────
  let html = `
    <div class="result-total">
      <div class="label">Costi accessori totali</div>
      <div class="amount">${fmt(totaal)}</div>
      <div class="percentage">${pctVanPrijs}% del prezzo di acquisto di ${fmt(prijs)}</div>
    </div>
    <div class="result-breakdown">
      <div class="result-row"><span class="rlabel"><strong>Imposte di trasferimento</strong></span><span class="ramount"></span></div>`;

  tax.L.forEach(r => {
    html += `<div class="result-row"><span class="rlabel">${r.label}</span><span class="ramount">${fmt(r.amount)}</span></div>`;
  });
  if (verrekend > 0) {
    html += `<div class="result-row minus"><span class="rlabel">Scomputo dell'anticipo dello 0,5% versato al compromesso</span><span class="ramount">${fmt(-verrekend)}</span></div>`;
  }
  if (creditoUsed > 0) {
    html += `<div class="result-row minus"><span class="rlabel">Credito d'imposta della precedente prima casa, scomputato dall'imposta di registro nell'atto</span><span class="ramount">${fmt(-creditoUsed)}</span></div>`;
  }
  if (creditoLater > 0) {
    html += `<div class="result-row dim"><span class="rlabel">Credito d'imposta non utilizzato in questo atto${v.iva ? ' (non scomputabile dall\'IVA)' : ''}; utilizzabile in seguito in diminuzione dell'IRPEF o delle imposte di registro, ipotecaria, catastale o di successione successive (non incluso nel totale)</span><span class="ramount">${fmt(creditoLater)}</span></div>`;
  }
  if (creditoVervalt > 0) {
    html += `<div class="result-row dim"><span class="rlabel">Parte del credito d'imposta che si perde, perché superiore all'imposta sulla nuova abitazione</span><span class="ramount">${fmt(creditoVervalt)}</span></div>`;
  }
  html += `<div class="result-row subtotal"><span class="rlabel">Subtotale imposte al rogito</span><span class="ramount">${fmt(totaalBelasting)}</span></div>`;

  // Effect van uitsplitsing
  if (alt) {
    const diff = alt.total - tax.total;
    if (v.split && diff > 0) {
      html += `<div class="split-banner">✓ <strong>Ripartire il prezzo conviene:</strong> senza un prezzo distinto per ciascun bene pagheresti ${fmt(diff)} di imposte in più (${fmt(alt.total)} invece di ${fmt(tax.total)}).</div>`;
    } else if (!v.split && diff < 0) {
      html += `<div class="split-banner">⚠ <strong>Paghi ${fmt(-diff)} di troppo.</strong> Con un prezzo distinto per ciascun bene nell'atto le imposte sarebbero ${fmt(alt.total)} invece di ${fmt(tax.total)}.</div>`;
    }
  }

  // Waarschuwingen
  const warnings = tax.W.slice();
  if (v.prima && !v.luxe) warnings.push('Prima casa: trasferisci la residenza nel Comune entro 18 mesi e vendi entro 2 anni un\'eventuale precedente prima casa in Italia. Altrimenti ' + (v.iva
    ? 'paghi la differenza rispetto all\'IVA al 10%, più interessi e una sanzione del 30% su tale differenza.'
    : 'segue il recupero dell\'imposta fino al 9%, con interessi e una sanzione del 30%.') + ' Lo stesso accade se vendi l\'abitazione entro 5 anni senza acquistare entro un anno una nuova prima casa.');
  if (v.prima && v.luxe) warnings.push('Un\'abitazione di lusso (A/1, A/8, A/9) non ha diritto alle agevolazioni prima casa. Il calcolatore applica l\'aliquota della seconda casa, anche per l\'imposta sostitutiva sul mutuo (2%).');
  if (warnings.length) {
    html += `<div class="warn-box"><strong>Attenzione</strong><ul>${warnings.map(w => `<li>${w}</li>`).join('')}</ul></div>`;
  }

  html += `
      <div class="result-divider"></div>
      <div class="result-row"><span class="rlabel"><strong>Professionisti</strong></span><span class="ramount"></span></div>`;
  if (isChecked('makelaar_aan')) {
    html += `
      <div class="result-row"><span class="rlabel">Provvigione dell'agenzia ${nl(getVal('makelaar_pct'))}% (sul prezzo di acquisto)</span><span class="ramount">${fmt(makelaar)}</span></div>
      <div class="result-row"><span class="rlabel">IVA 22% sulla provvigione</span><span class="ramount">${fmt(makelaarBtw)}</span></div>`;
    if (v.prima) {
      html += `<div class="result-row dim"><span class="rlabel">Detraibile in seguito dall'IRPEF, solo se diventi fiscalmente residente in Italia (non residenti: nessuna detrazione; non scomputata)</span><span class="ramount">max ${fmt(Math.min(190, totaalMakelaar * 0.19))}</span></div>`;
    }
  } else {
    html += `<div class="result-row dim"><span class="rlabel">Agenzia immobiliare</span><span class="ramount">-</span></div>`;
  }
  html += `<div class="result-row"><span class="rlabel">Notaio ${nl(getVal('notaris_pct'))}% (sul prezzo di acquisto)</span><span class="ramount">${fmt(notarisBruto)}</span></div>`;
  if (notarisKorting > 0) {
    html += `<div class="result-row minus"><span class="rlabel">Riduzione del 30% per il prezzo-valore, sull'importo al netto dell'IVA (solo se il notaio la applica)</span><span class="ramount">${fmt(-notarisKorting)}</span></div>`;
  }
  html += `<div class="result-row subtotal"><span class="rlabel">Subtotale professionisti</span><span class="ramount">${fmt(totaalMakelaar + notaris)}</span></div>`;

  if (isChecked('hypotheek_aan')) {
    html += `
      <div class="result-divider"></div>
      <div class="result-row"><span class="rlabel"><strong>Mutuo</strong></span><span class="ramount"></span></div>
      <div class="result-row"><span class="rlabel">Imposta sostitutiva ${nl(hypPct)}% (sull'importo del mutuo)</span><span class="ramount">${fmt(hypoImposta)}</span></div>
      <div class="result-row"><span class="rlabel">Perizia</span><span class="ramount">${fmt(hypoTaxatie)}</span></div>
      <div class="result-row"><span class="rlabel">Spese bancarie</span><span class="ramount">${fmt(hypoBankkosten)}</span></div>
      <div class="result-row"><span class="rlabel">Notaio per l'atto di mutuo</span><span class="ramount">${fmt(hypoNotaris)}</span></div>
      <div class="result-row"><span class="rlabel">Assicurazione incendio e scoppio</span><span class="ramount">${fmt(hypoVerzekering)}</span></div>
      <div class="result-row subtotal"><span class="rlabel">Subtotale mutuo</span><span class="ramount">${fmt(totaalHypotheek)}</span></div>`;
  }

  if (totaalExtras > 0) {
    html += `
      <div class="result-divider"></div>
      <div class="result-row"><span class="rlabel"><strong>Costi aggiuntivi</strong></span><span class="ramount"></span></div>`;
    if (vertaler) html += `<div class="result-row"><span class="rlabel">Traduttore/interprete</span><span class="ramount">${fmt(vertaler)}</span></div>`;
    if (geometra) html += `<div class="result-row"><span class="rlabel">Perizia tecnica (geometra)</span><span class="ramount">${fmt(geometra)}</span></div>`;
    if (advocaat) html += `<div class="result-row"><span class="rlabel">Avvocato/consulente</span><span class="ramount">${fmt(advocaat)}</span></div>`;
    if (volmacht) html += `<div class="result-row"><span class="rlabel">Procura</span><span class="ramount">${fmt(volmacht)}</span></div>`;
    html += `<div class="result-row subtotal"><span class="rlabel">Subtotale costi aggiuntivi</span><span class="ramount">${fmt(totaalExtras)}</span></div>`;
  }

  const basisTxt = v.iva ? `caparra ${fmt(caparra)}` : (acconto > 0 ? `caparra ${fmt(caparra)} + acconto ${fmt(acconto)}` : `caparra ${fmt(caparra)}`);
  html += `
      <div class="result-divider"></div>
      <div class="result-row"><span class="rlabel"><strong>Compromesso</strong></span><span class="ramount"></span></div>
      <div class="result-row"><span class="rlabel">Imposta di registro fissa</span><span class="ramount">${fmt(registratieVast)}</span></div>
      <div class="result-row"><span class="rlabel">Imposta di bollo (€16 ogni 4 facciate)</span><span class="ramount">${fmt(zegelrecht)}</span></div>
      <div class="result-row"><span class="rlabel">Anticipo dello 0,5% su ${basisTxt}${voorschotBegrensd ? ', limitato all\'imposta dell\'atto definitivo' : ''}${voorschot > 0 ? ', scomputato al rogito' : ''}</span><span class="ramount">${fmt(voorschot)}</span></div>${accontoIvaVast ? `
      <div class="result-row"><span class="rlabel">Imposta fissa sull'acconto soggetto a IVA (prassi dell'Agenzia, contestata dal Notariato)</span><span class="ramount">${fmt(accontoIvaVast)}</span></div>` : ''}
      <div class="result-row subtotal"><span class="rlabel">Subtotale compromesso</span><span class="ramount">${fmt(totaalCompr)}</span></div>
    </div>`;

  // ── EIGEN GELD (bij hypotheek) ───────────────────────────────
  if (isChecked('hypotheek_aan')) {
    const hypBedrag = getVal('hypotheek_bedrag');
    const fase1Caparra = caparra + acconto;
    const fase1Registratie = totaalCompr;
    const fase1Totaal = fase1Caparra + fase1Registratie;
    const fase2Taxatie = hypoTaxatie + hypoBankkosten;
    const nettoHypotheek = hypBedrag - hypoImposta;
    const restantKoopsom = Math.max(0, prijs - caparra - acconto - nettoHypotheek);
    const bijkomendeKosten = totaalBelasting + totaalMakelaar + notaris + totaalExtras;
    const hypoAkteKosten = hypoNotaris + hypoVerzekering;
    const fase3Totaal = restantKoopsom + bijkomendeKosten + hypoAkteKosten;
    const totaalEigenGeld = fase1Totaal + fase2Taxatie + fase3Totaal;

    html += `<div style="margin-top:1.25rem; background:linear-gradient(135deg, #E65100 0%, #BF360C 100%); border-radius:10px; padding:1.25rem; color:white;">
      <div style="font-family:'Playfair Display',serif; font-size:1.15rem; font-weight:700; margin-bottom:0.75rem;">⚠ Capitale proprio necessario con il mutuo</div>
      <div style="font-size:0.85rem; opacity:0.9; margin-bottom:0.75rem; line-height:1.5;">
        Al <em>rogito</em> la banca versa l'importo del mutuo (${fmt(hypBedrag)}) direttamente al venditore, ma trattiene l'imposta sostitutiva (${fmt(hypoImposta)}). Al venditore arrivano netti ${fmt(nettoHypotheek)}. Tutti gli altri importi vanno pagati con mezzi propri:
      </div>

      <div style="font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; opacity:0.7; margin:0.75rem 0 0.25rem;">Fase 1: al compromesso</div>
      <div class="result-row" style="color:white;"><span class="rlabel">${acconto > 0 ? 'Caparra e acconto' : 'Caparra'} (anticipo sul prezzo)</span><span class="ramount">${fmt(fase1Caparra)}</span></div>
      <div class="result-row" style="color:white;"><span class="rlabel">Spese di registrazione del compromesso (incl. anticipo dello 0,5%)</span><span class="ramount">${fmt(fase1Registratie)}</span></div>
      <div class="result-row" style="color:white; opacity:0.7; border-top:1px solid rgba(255,255,255,0.2); padding-top:0.25rem;"><span class="rlabel"><em>Subtotale fase 1</em></span><span class="ramount"><em>${fmt(fase1Totaal)}</em></span></div>

      <div style="font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; opacity:0.7; margin:0.75rem 0 0.25rem;">Fase 2: prima del rogito (richiesta di mutuo)</div>
      <div class="result-row" style="color:white;"><span class="rlabel">Perizia + spese bancarie</span><span class="ramount">${fmt(fase2Taxatie)}</span></div>

      <div style="font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; opacity:0.7; margin:0.75rem 0 0.25rem;">Fase 3: al rogito</div>
      <div class="result-row" style="color:white; opacity:0.5;"><span class="rlabel">La banca eroga il mutuo lordo e trattiene l'imposta sostitutiva</span><span class="ramount">${fmt(hypBedrag)}</span></div>
      <div class="result-row" style="color:white; opacity:0.5;"><span class="rlabel">Importo netto ricevuto dal venditore tramite la banca</span><span class="ramount">${fmt(nettoHypotheek)}</span></div>
      <div class="result-row" style="color:white;"><span class="rlabel">Saldo del prezzo (${fmt(prijs)} meno anticipi meno mutuo netto)</span><span class="ramount">${fmt(restantKoopsom)}</span></div>
      <div class="result-row" style="color:white;"><span class="rlabel">Costi accessori (imposte dopo lo scomputo, notaio, agenzia, ecc.)</span><span class="ramount">${fmt(bijkomendeKosten)}</span></div>
      <div class="result-row" style="color:white;"><span class="rlabel">Notaio per l'atto di mutuo e assicurazione</span><span class="ramount">${fmt(hypoAkteKosten)}</span></div>
      <div class="result-row" style="color:white; opacity:0.7; border-top:1px solid rgba(255,255,255,0.2); padding-top:0.25rem;"><span class="rlabel"><em>Subtotale fase 3 (con mezzi propri)</em></span><span class="ramount"><em>${fmt(fase3Totaal)}</em></span></div>

      <div style="border-top:2px solid rgba(255,255,255,0.4); margin-top:0.75rem; padding-top:0.75rem;">
        <div class="result-row" style="color:white; font-size:1.1rem;"><span class="rlabel"><strong>Capitale proprio necessario in totale</strong></span><span class="ramount"><strong>${fmt(totaalEigenGeld)}</strong></span></div>
        <div style="font-size:0.82rem; opacity:0.8; margin-top:0.25rem;">${prijs > 0 ? ((totaalEigenGeld / prijs * 100).toFixed(1).replace('.', ',') + '% del prezzo di acquisto') : ''}</div>
      </div>
      <div style="font-size:0.78rem; opacity:0.7; margin-top:0.75rem; line-height:1.4;">
        * L'imposta sostitutiva (${fmt(hypoImposta)}) è trattenuta dalla banca sull'importo lordo del mutuo prima del versamento al venditore. Riduce quindi l'importo netto ricevuto e aumenta il saldo da pagare con mezzi propri.
      </div>
    </div>`;
  }

  document.getElementById('results-content').innerHTML = html;

  // ── JAARLIJKSE LASTEN ────────────────────────────────────────
  const imuPct = getVal('imu_pct');
  const imuF = imuPct / 100;
  // Landbouwgrond heeft een eigen basistarief van 0,76% (art. 1 lid 752 L. 160/2019)
  const imuGrondPct = getVal('imu_grond_pct');
  const imuGrondF = imuGrondPct / 100;
  const tari = getVal('tari');
  const rows = [];
  let imuTot = 0;
  const addImu = (label, amt) => { rows.push(`<div class="annual-row"><span class="rlabel">${label}</span><span class="ramount">${fmt(amt)}</span></div>`); imuTot += amt; };
  const addVrij = label => rows.push(`<div class="annual-row"><span class="rlabel">${label}</span><span class="ramount">Esente</span></div>`);
  const addNote = label => rows.push(`<div class="annual-row dim"><span class="rlabel">${label}</span><span class="ramount">-</span></div>`);
  const addIndicatie = (label, amt) => rows.push(`<div class="annual-row dim"><span class="rlabel">${label}</span><span class="ramount">${fmt(amt)}</span></div>`);
  let vrijBase = 0; // IMU-grondslag die vrijgesteld is als hoofdverblijf, voor de indicatie tot de verhuizing

  // Luxe hoofdverblijf (A/1, A/8, A/9): 0,5% en €200 aftrek over woning plus één pertinenza per categorie (L. 160/2019 c. 748-749)
  const luxeHoofd = v.prima && v.luxe && v.woningExists && v.rendita > 0;
  let luxeBase = 0, luxePert = 0;
  if (v.woningExists && v.rendita > 0) {
    const base = v.rendita * COEF_IMU;
    const naam = 'abitazione';
    if (v.primaWoning) { addVrij(`IMU ${naam} (abitazione principale, da quando ci abiti e vi hai la residenza)`); vrijBase += base; }
    else if (luxeHoofd) luxeBase += base;
    else addImu(`IMU ${naam} ${nl(imuPct)}% su ${fmt(base)} (rendita × 168)`, base * imuF);
  }
  const imuSeen = {};
  v.bijgebouwen.forEach((b, i) => {
    if (b.rendita <= 0) return;
    const isPert = PERT_CATS.includes(b.cat);
    const eerste = isPert && !imuSeen[b.cat];
    if (isPert) imuSeen[b.cat] = true;
    const base = b.rendita * COEF_IMU;
    if (v.primaWoning && eerste) { addVrij(`IMU pertinenza ${i + 1} (${b.cat}, pertinenza dell'abitazione principale)`); vrijBase += base; }
    else if (luxeHoofd && eerste) { luxeBase += base; luxePert++; }
    else addImu(`IMU pertinenza ${i + 1} (${b.cat === 'A' ? 'rustico' : b.cat}) ${nl(imuPct)}% su ${fmt(base)}`, base * imuF);
  });
  if (luxeHoofd) {
    const lp = getVal('imu_luxe_pct');
    const naam = 'abitazione';
    const extra = luxePert ? ` + ${luxePert} ${luxePert === 1 ? 'pertinenza' : 'pertinenze'}` : '';
    addImu(`IMU ${naam}${extra}, abitazione principale di lusso: ${nl(lp)}% su ${fmt(luxeBase)} meno €200 di detrazione`, Math.max(0, luxeBase * lp / 100 - 200));
  }
  if (vrijBase > 0) {
    addIndicatie(`Finché non ci abiti e non vi hai la residenza: IMU come seconda casa, ${nl(imuPct)}% su ${fmt(vrijBase)} all'anno, in proporzione ai mesi (non incluso nel totale)`, vrijBase * imuF);
  }
  const grondVrij = (v.landb &&(v.landb.regime === 'agrarier' || v.landb.regime === 'cd9')) ? 'coltivatore diretto o IAP' : (isChecked('imu_berg') ? 'Comune montano' : '');
  if (v.tuin && v.tuin.rd > 0) {
    const base = v.tuin.rd * COEF_IMU_TERRENO;
    if (grondVrij) addVrij(`IMU giardino (${grondVrij})`);
    else addImu(`IMU giardino ${nl(imuGrondPct)}% su ${fmt(base)} (reddito dominicale × 168,75)`, base * imuGrondF);
  }
  if (v.bouw && v.bouw.prijs > 0) {
    addImu(`IMU area fabbricabile ${nl(imuPct)}% sul valore venale (qui il prezzo di acquisto ${fmt(v.bouw.prijs)})`, v.bouw.prijs * imuF);
  }
  if (v.landb) {
    if (grondVrij) addVrij(`IMU terreno agricolo (${grondVrij})`);
    else if (v.landb.rd > 0) {
      const base = v.landb.rd * COEF_IMU_TERRENO;
      addImu(`IMU terreno agricolo ${nl(imuGrondPct)}% su ${fmt(base)} (reddito dominicale × 168,75)`, base * imuGrondF);
    } else addNote('IMU terreno agricolo: inserisci il reddito dominicale');
  }
  if (v.f2) addNote('IMU rudere (F/2): nessuna IMU finché l\'immobile è censito come F/2; dopo la demolizione o durante lavori di ristrutturazione importanti, IMU sul valore dell\'area');
  if (v.d10) {
    // Agrarisch bedrijfsgebouw: 0,1% (art. 1 lid 750 L. 160/2019), grondslag rendita × 1,05 × 65
    if (v.d10.rendita > 0) {
      const base = v.d10.rendita * COEF_IMU_D;
      addImu(`IMU fabbricato strumentale D/10 0,1% su ${fmt(base)} (rendita × 68,25; il Comune può azzerarla)`, base * 0.001);
    } else addNote('IMU fabbricato strumentale (D/10): inserisci la rendita; aliquota 0,1%, il Comune può azzerarla');
  }

  const jaarTotaal = imuTot + tari;
  document.getElementById('annual-results').innerHTML = rows.join('') + `
    <div class="annual-row"><span class="rlabel">TARI (tassa sui rifiuti)</span><span class="ramount">${fmt(tari)}</span></div>
    <div class="annual-row subtotal"><span class="rlabel">Costi fissi annuali (utenze escluse)</span><span class="ramount">${fmt(jaarTotaal)}</span></div>`;
}

// Start
renderBijgebouwen();
calculate();
</script>

<!-- JavaScripts (voor Canvas theme header/menu) -->
<script src="js/jquery.js"></script>
<script src="js/plugins.min.js"></script>
<script src="js/functions.js"></script>
	<!-- Default Statcounter code for StefSmulders
http://www.stefsmulders.nl -->
<script type="text/javascript">
var sc_project=12895700;
var sc_invisible=1;
var sc_security="4b7432a4";
</script>
<script type="text/javascript"
src="https://www.statcounter.com/counter/counter.js"
async></script>
<noscript><div class="statcounter"><a title="Web Analytics
Made Easy - Statcounter" href="https://statcounter.com/"
target="_blank"><img class="statcounter"
src="https://c.statcounter.com/12895700/0/4b7432a4/1/"
alt="Web Analytics Made Easy - Statcounter"
referrerPolicy="no-referrer-when-downgrade"></a></div></noscript>
<!-- End of Statcounter Code -->
</body>
</html>
