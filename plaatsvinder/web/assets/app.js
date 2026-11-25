/* Plaatsvinder Italië: wizard, resultaten, gemeentepagina en vergelijken. Vanilla JS.
 * De teller filtert in de browser op comuni-lite.json met dezelfde regels als voldoet() in lib/model.php.
 * Scores rekent score.php uit; de gemeentepagina haalt de details op bij gemeente.php. */
(function () {
  "use strict";

  var T = window.PV.taal, C = window.PV.cfg;
  var app = document.getElementById("app");
  var LITE = null;
  var OPSLAG = "plaatsvinder.v2";
  var STAPPEN = ["profiel", "wensen", "ligging", "grootte", "gewichten", "filters", "regio"];
  var CRIT = C.criteria.criteria.slice().sort(function (a, b) { return a.rang - b.rang; });
  var BRONNEN = C.bronnen.bronnen;
  var ZONDER_DATA = C.zonder_data || [];
  var MAX_VERGELIJK = 3;
  var LEEG = T.gemeente.geen_data;

  function zonderData(c) {
    return !c.indicatoren.length || ZONDER_DATA.indexOf(c.id) >= 0;
  }
  function profiel(id) { return C.profielen.profielen.filter(function (p) { return p.id === id; })[0]; }

  function basisgewichten(pid, verblijf) {
    var p = profiel(pid), g = Object.assign({}, p.gewichten);
    (p.vragen || []).forEach(function (v) {
      v.opties.forEach(function (o) { if (o.id === verblijf && o.effect) Object.assign(g, o.effect); });
    });
    return g;
  }
  // naar precies 100 met de grootste-rest-methode (gelijk aan normaliseer() in model.php)
  function normaliseer(g) {
    var k = Object.keys(g), t = som(g);
    if (t <= 0) return g;
    var ex = k.map(function (x) { return g[x] * 100 / t; }), af = ex.map(Math.floor);
    var tekort = 100 - af.reduce(function (a, b) { return a + b; }, 0);
    ex.map(function (v, i) { return [v - af[i], i]; }).sort(function (a, b) { return b[0] - a[0]; })
      .slice(0, tekort).forEach(function (x) { af[x[1]]++; });
    var uit = {}; k.forEach(function (x, i) { uit[x] = af[i]; });
    return uit;
  }
  // wensenlijst: must-haves x1,5, nice-to-haves x1,2 (gelijk aan pas_wensen_toe() in model.php)
  var WL = C.criteria.wensenlijst;
  function startgewichten(pid, verblijf) {
    var g = basisgewichten(pid, verblijf), w = S.wensen || {};
    (w.must || []).forEach(function (c) { if (g[c] != null) g[c] *= WL.factor_must; });
    (w.nice || []).forEach(function (c) { if (g[c] != null) g[c] *= WL.factor_nice; });
    return normaliseer(g);
  }
  // dealbreakers als harde eisen; een strengere eigen eis blijft staan (gelijk aan streng() in model.php)
  function effFilters() {
    var f = Object.assign({}, S.filters);
    CRIT.forEach(function (c) {
      if (!c.dealbreaker || (S.wensen.dealbreakers || []).indexOf(c.id) < 0) return;
      var k = c.dealbreaker.filter, v = c.dealbreaker.waarde, eigen = f[k];
      if (typeof v === "boolean") f[k] = v || !!eigen;
      else if (!(eigen > 0)) f[k] = v;
      else f[k] = k.slice(0, 4) === "min_" ? Math.max(eigen, v) : Math.min(eigen, v);
      if (c.id === "risico") f.rode_zone_uit = true;
    });
    return f;
  }

  var leeg = function () {
    return {
      stap: -1, profiel: "", verblijf: "permanent", expats: "maakt_niet_uit", klimaat: "zacht", gewichten: {},
      wensen: { dealbreakers: [], must: [], nice: [] },
      ligging: [], grootte: [], filters: { rode_zone_uit: true }, budget: { soort: "", max: null, m2: 80 },
      gebied: "overal", regios_uit: [], alleen_regios: [], alleen_provs: [], vergelijk: []
    };
  };
  var S = leeg();
  var HERVAT = false;   // eerder begonnen: eerst vragen of de bezoeker verder wil (zoals de wensenlijst van Un Sogno di Casa)
  try {
    var b = JSON.parse(localStorage.getItem(OPSLAG) || "null");
    if (b) { S = Object.assign(leeg(), b); HERVAT = !!S.profiel && !location.hash; }
  } catch (e) { /* geen opslag */ }
  var NAAR_UITSLAG = false;   // via "Wijzigen" in het overzicht: na de stap terug naar de uitslag
  function bewaar() { try { localStorage.setItem(OPSLAG, JSON.stringify(S)); } catch (e) { /* geen opslag */ } }

  // ---------------------------------------------------------------- hulp
  function el(tag, attrs, kids) {
    var n = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      var v = attrs[k];
      if (k === "class") n.className = v;
      else if (k === "text") n.textContent = v;
      else if (k.slice(0, 2) === "on") n.addEventListener(k.slice(2), v);
      else if (v === true) n.setAttribute(k, "");
      else if (v !== false && v != null) n.setAttribute(k, v);
    });
    (kids || []).forEach(function (k) { if (k != null) n.appendChild(typeof k === "string" ? document.createTextNode(k) : k); });
    return n;
  }
  function fmt(tpl, v) { return String(tpl).replace(/\{(\w+)\}/g, function (_, k) { return v[k] != null ? v[k] : ""; }); }
  function getal(n, dec) {
    return Number(n).toLocaleString("nl-NL", { maximumFractionDigits: dec == null ? 1 : dec });
  }
  function toggle(l, v) { var i = l.indexOf(v); if (i >= 0) l.splice(i, 1); else l.push(v); }
  function som(g) { return Object.keys(g).reduce(function (s, k) { return s + (g[k] || 0); }, 0); }
  function haal(url, opties) {
    return fetch(url, opties).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); });
  }

  // ---------------------------------------------------------------- filters (gelijk aan voldoet() in model.php)
  var STAD_KOL = { utrecht: "uu", brussel: "ub", keulen: "uk", munchen: "um" };
  function telOver() {
    if (!LITE) return null;
    var L = LITE, n = 0, masker = 0, f = effFilters(), bu = S.budget;
    S.ligging.forEach(function (k) { masker |= 1 << L.ligging.indexOf(k); });
    var gr = S.grootte.map(Number), uit = S.gebied === "alleen" ? [] : S.regios_uit;
    var alleen = S.gebied === "alleen" && (S.alleen_regios.length || S.alleen_provs.length);
    var rmp = {};
    L.provincies.forEach(function (p) { if (S.alleen_provs.indexOf(p[0]) >= 0) rmp[p[2]] = 1; });
    var metBudget = bu.soort && bu.max > 0 && bu.m2 > 0;
    var uur = f.max_uur && f.max_uur.stad && f.max_uur.uren ? L[STAD_KOL[f.max_uur.stad]] : null;
    for (var i = 0; i < L.n; i++) {
      var pv = L.provincies[L.pv[i]], regio = pv[2], fl = L.f[i];
      if (masker && !(L.lig[i] & masker)) continue;
      if (gr.length && gr.indexOf(L.gr[i]) < 0) continue;
      if (uit.indexOf(regio) >= 0) continue;
      if (alleen && !(S.alleen_provs.indexOf(pv[0]) >= 0 || (S.alleen_regios.indexOf(regio) >= 0 && !rmp[regio]))) continue;
      if (f.max_prijs_m2 && L.koop[i] != null && L.koop[i] * 10 > f.max_prijs_m2) continue;
      if (f.max_luchthaven && L.lh[i] != null && L.lh[i] > f.max_luchthaven) continue;
      if (f.max_ziekenhuis && L.zh[i] != null && L.zh[i] > f.max_ziekenhuis) continue;
      if (uur && uur[i] != null && uur[i] / 2 > f.max_uur.uren) continue;
      if (f.zone1_uit && (fl & 3) === 0) continue;
      if (f.rode_zone_uit && ((fl >> 2) & 3) === 2) continue;
      if (f.min_glasvezel && L.gv[i] != null && L.gv[i] / 10 < f.min_glasvezel) continue;
      if (f.alleen_regime7 && ((fl >> 4) & 3) !== 1) continue;
      if (f.max_hoogte && L.hm[i] != null && L.hm[i] > f.max_hoogte) continue;
      if (metBudget) {
        var m2p = bu.soort === "huren" ? (L.huur[i] == null ? null : L.huur[i] / 10) : (L.koop[i] == null ? null : L.koop[i] * 10);
        if (m2p != null && m2p * bu.m2 > bu.max) continue;
      }
      n++;
    }
    return n;
  }

  // ---------------------------------------------------------------- onderdelen
  function kaart(titel, uitleg, aan, klik, extra) {
    return el("button", { type: "button", class: "pv-kaart" + (aan ? " aan" : ""), "aria-pressed": aan ? "true" : "false", onclick: klik },
      [el("span", { class: "pv-kaart-titel", text: titel }), uitleg ? el("span", { class: "pv-kaart-uitleg", text: uitleg }) : null, extra || null]);
  }
  function segment(label, waarde, opties, kies) {
    return el("div", { class: "pv-segment", role: "radiogroup", "aria-label": label }, opties.map(function (o) {
      var aan = waarde === o[0];
      return el("button", { type: "button", role: "radio", "aria-checked": aan ? "true" : "false", class: aan ? "aan" : "", text: o[1],
        onclick: function () { kies(o[0]); } });
    }));
  }
  function keuzelijst(label, waarde, opties, veranderd, id) {
    id = id || "pv-" + label.replace(/\W+/g, "").slice(0, 20);
    var sel = el("select", { id: id, onchange: veranderd ? function () { veranderd(this.value === "" ? null : Number(this.value)); } : null },
      opties.map(function (o) { return el("option", { value: o[0] == null ? "" : String(o[0]), selected: o[0] === waarde, text: o[1] }); }));
    return el("div", { class: "pv-veld" }, [el("label", { for: id, text: label }), sel]);
  }
  function vinkje(label, aan, veranderd) {
    var id = "pv-v-" + label.replace(/\W+/g, "").slice(0, 24);
    var inp = el("input", { type: "checkbox", id: id, checked: !!aan, onchange: function () { veranderd(this.checked); } });
    return el("div", { class: "pv-vinkje" }, [inp, el("label", { for: id, text: label })]);
  }
  function basisLabel(c) { return el("span", { class: "pv-basis pv-basis-" + c.basis, text: T.basis[c.basis] }); }

  // ---------------------------------------------------------------- stap 1: profiel
  function kiesProfiel(id) {
    S.profiel = id;
    S.gewichten = startgewichten(id, S.verblijf);
    var std = profiel(id).filters_standaard || {};
    S.filters = { rode_zone_uit: true };
    if (std.max_ziekenhuis) S.filters.max_ziekenhuis = std.max_ziekenhuis;
    if (std.min_glasvezel_pct) S.filters.min_glasvezel = std.min_glasvezel_pct;
    teken();
  }
  function schermProfiel() {
    var st = T.stappen.profiel;
    var delen = [el("div", { class: "pv-raster" }, C.profielen.profielen.map(function (p) {
      return kaart(p.label, st.uitleg_per_profiel[p.id], S.profiel === p.id, function () { kiesProfiel(p.id); });
    }))];
    var p = S.profiel && profiel(S.profiel);
    (p && p.vragen || []).forEach(function (v) {
      delen.push(el("h3", { class: "pv-sub-kop", text: v.vraag }));
      delen.push(segment(v.vraag, S.verblijf, v.opties.map(function (o) { return [o.id, o.label]; }), function (id) {
        S.verblijf = id; S.gewichten = startgewichten(S.profiel, id); teken();
      }));
      v.opties.forEach(function (o) { if (o.id === S.verblijf && o.uitleg) delen.push(el("p", { class: "pv-uitleg", text: o.uitleg })); });
    });
    return el("div", {}, delen);
  }

  // ---------------------------------------------------------------- stap 2: wensenlijst (gids 1.3)
  function schermWensen() {
    var st = T.stappen.wensen, w = S.wensen, max = WL.aantal;
    var groep = function (soort) {
      var kandidaten = CRIT.filter(function (c) { return !zonderData(c) && (soort !== "dealbreakers" || c.dealbreaker); });
      var gekozen = w[soort];
      return el("fieldset", { class: "pv-groep" }, [
        el("legend", { text: st[soort] }),
        el("p", { class: "pv-uitleg", text: st[soort + "_uitleg"] + " " + fmt(st.gekozen, { n: gekozen.length, max: max }) }),
        el("div", { class: "pv-raster pv-raster-klein" }, kandidaten.map(function (c) {
          var aan = gekozen.indexOf(c.id) >= 0;
          return kaart(c.label, soort === "dealbreakers" ? c.dealbreaker.tekst : null, aan, function () {
            if (!aan && gekozen.length >= max) { alert(fmt(st.vol, { max: max })); return; }
            ["dealbreakers", "must", "nice"].forEach(function (x) { if (x !== soort) w[x] = w[x].filter(function (id) { return id !== c.id; }); });
            toggle(gekozen, c.id);
            S.gewichten = startgewichten(S.profiel || "gepensioneerde", S.verblijf);
            teken();
          });
        }))]);
    };
    return el("div", {}, [groep("dealbreakers"), groep("must"), groep("nice")]);
  }

  function schermKeuzes(sleutel) {
    var opties = T.stappen[sleutel].opties;
    return el("div", { class: "pv-raster" }, Object.keys(opties).map(function (k) {
      return kaart(opties[k][0], opties[k][1], S[sleutel].indexOf(k) >= 0, function () { toggle(S[sleutel], k); teken(); });
    }));
  }

  // ---------------------------------------------------------------- stap 4: gewichten (som 100)
  function zetGewicht(id, nieuw) {
    var g = S.gewichten, actief = CRIT.filter(function (c) { return !zonderData(c) && c.id !== id; }).map(function (c) { return c.id; });
    nieuw = Math.max(0, Math.min(100, Math.round(nieuw)));
    var rest = 100 - nieuw, oud = actief.reduce(function (s, k) { return s + (g[k] || 0); }, 0);
    var basis = actief.map(function (k) { return oud > 0 ? (g[k] || 0) / oud * rest : rest / actief.length; });
    var afgerond = basis.map(Math.floor), tekort = rest - afgerond.reduce(function (a, b) { return a + b; }, 0);
    basis.map(function (v, i) { return [v - afgerond[i], i]; }).sort(function (a, b) { return b[0] - a[0]; })
      .slice(0, tekort).forEach(function (x) { afgerond[x[1]]++; });
    actief.forEach(function (k, i) { g[k] = afgerond[i]; });
    CRIT.forEach(function (c) { if (zonderData(c)) g[c.id] = 0; });
    g[id] = nieuw;
  }
  function schermGewichten() {
    var st = T.stappen.gewichten;
    if (!S.profiel) S.gewichten = startgewichten("gepensioneerde", S.verblijf);
    var lijst = el("div", { class: "pv-schuifjes" }, CRIT.map(function (c) {
      var geen = zonderData(c), id = "pv-g-" + c.id, w = S.gewichten[c.id] || 0;
      var schuif = el("input", { type: "range", id: id, min: "0", max: "100", step: "1", value: String(w), disabled: geen,
        "aria-describedby": id + "-u" });
      var waarde = el("output", { for: id, class: "pv-gewicht", text: String(w) });
      schuif.addEventListener("input", function () { waarde.textContent = schuif.value; });
      schuif.addEventListener("change", function () { zetGewicht(c.id, Number(schuif.value)); teken(); });
      var rij = [
        el("div", { class: "pv-schuif-kop" }, [el("label", { for: id, text: c.label }), waarde]),
        schuif,
        el("p", { class: "pv-schuif-uitleg", id: id + "-u" }, [geen ? el("strong", { text: st.geen_data + ". " }) : null,
          geen ? st.geen_data_uitleg : c.uitleg, " ", basisLabel(c)])
      ];
      if (c.id === "klimaat" && c.voorkeur) {
        rij.push(segment(c.voorkeur.vraag, S.klimaat, c.voorkeur.opties.map(function (o) { return [o.id, o.label]; }), function (v) { S.klimaat = v; teken(); }));
        rij.push(el("p", { class: "pv-noot", text: c.voorkeur.uitleg }));
      }
      if (c.id === "expats") {
        var q = C.profielen.expats_vraag;
        rij.push(segment(q.vraag, S.expats, q.opties.map(function (o) { return [o.id, o.label]; }), function (v) { S.expats = v; teken(); }));
      }
      return el("div", { class: "pv-schuif" + (geen ? " uit" : "") }, rij);
    }));
    return el("div", {}, [
      el("p", { class: "pv-let-op", text: st.expertoordeel }),
      el("p", { class: "pv-som", text: fmt(st.som, { n: som(S.gewichten) }) }),
      lijst,
      el("button", { type: "button", class: "pv-link", text: T.knoppen.startwaarden, onclick: function () {
        S.gewichten = startgewichten(S.profiel || "gepensioneerde", S.verblijf); teken();
      } })
    ]);
  }

  // ---------------------------------------------------------------- stap 5: harde filters
  function schermFilters() {
    var st = T.stappen.filters, f = S.filters, bu = S.budget;
    var min = function (lijst) { return [[null, st.geen_limiet]].concat(lijst.map(function (m) { return [m, fmt(st.minuten, { n: m })]; })); };
    var uur = f.max_uur || { stad: "utrecht", uren: null };
    var delen = [
      keuzelijst(st.max_prijs, f.max_prijs_m2 || null, [[null, st.geen_limiet]].concat([500, 750, 1000, 1500, 2000, 3000].map(function (e) { return [e, fmt(st.euro, { n: getal(e) })]; })),
        function (v) { f.max_prijs_m2 = v; teken(); }, "pv-f-prijs"),
      keuzelijst(st.max_luchthaven, f.max_luchthaven || null, min([30, 60, 90]), function (v) { f.max_luchthaven = v; teken(); }, "pv-f-lh"),
      el("p", { class: "pv-noot", text: st.max_luchthaven_noot }),
      keuzelijst(st.max_ziekenhuis, f.max_ziekenhuis || null, min([20, 30, 45, 60]), function (v) { f.max_ziekenhuis = v; teken(); }, "pv-f-zh"),
      el("fieldset", { class: "pv-groep" }, [el("legend", { text: st.max_uur }),
        keuzelijst(st.stad, uur.stad, Object.keys(T.steden).map(function (k) { return [k, T.steden[k]]; }), null, "pv-f-stad"),
        keuzelijst(st.max_uur, uur.uren, [[null, st.geen_limiet]].concat([6, 8, 10, 12, 14].map(function (u) { return [u, fmt(st.uren, { n: u })]; })), null, "pv-f-uren")]),
      keuzelijst(st.min_glasvezel, f.min_glasvezel || null, [[null, st.geen_limiet]].concat([25, 50, 75, 90].map(function (p) { return [p, fmt(st.pct, { n: p })]; })),
        function (v) { f.min_glasvezel = v; teken(); }, "pv-f-gv"),
      keuzelijst(st.max_hoogte, f.max_hoogte || null, [[null, st.geen_limiet]].concat([300, 500, 800, 1000].map(function (m) { return [m, fmt(st.meter, { n: getal(m, 0) })]; })),
        function (v) { f.max_hoogte = v; teken(); }, "pv-f-hoogte"),
      el("p", { class: "pv-noot", text: st.max_hoogte_noot }),
      vinkje(st.zone1, f.zone1_uit, function (v) { f.zone1_uit = v; teken(); }),
      vinkje(st.rode_zone, f.rode_zone_uit !== false, function (v) { f.rode_zone_uit = v; teken(); }),
      vinkje(st.regime7, f.alleen_regime7, function (v) { f.alleen_regime7 = v; teken(); }),
      el("h3", { class: "pv-sub-kop", text: st.budget_titel }),
      segment(st.soort, bu.soort, [["", st.geen], ["huren", st.huren], ["kopen", st.kopen]], function (v) { bu.soort = v; teken(); })
    ];
    // stad en uren horen bij elkaar
    var stadSel = delen[4].querySelector("#pv-f-stad"), urenSel = delen[4].querySelector("#pv-f-uren");
    stadSel.value = uur.stad;
    var zetUur = function () { var u = urenSel.value ? Number(urenSel.value) : null; f.max_uur = u ? { stad: stadSel.value, uren: u } : null; teken(); };
    stadSel.onchange = zetUur; urenSel.onchange = zetUur;
    if (bu.soort) {
      var veld = function (label, sleutel, stap) {
        var id = "pv-b-" + sleutel, inp = el("input", { id: id, type: "number", inputmode: "numeric", min: "0", step: stap, value: bu[sleutel] != null ? bu[sleutel] : "" });
        inp.addEventListener("change", function () { bu[sleutel] = inp.value ? Number(inp.value) : null; teken(); });
        return el("div", { class: "pv-veld" }, [el("label", { for: id, text: label }), inp]);
      };
      delen.push(veld(bu.soort === "kopen" ? st.max_koop : st.max_huur, "max", bu.soort === "kopen" ? "5000" : "50"));
      delen.push(veld(st.m2, "m2", "5"));
    }
    var db = (S.wensen.dealbreakers || []).map(function (id) { var c = CRIT.filter(function (x) { return x.id === id; })[0]; return c && c.dealbreaker ? c.dealbreaker.tekst : null; }).filter(Boolean);
    if (db.length) delen.unshift(el("p", { class: "pv-let-op", text: fmt(st.dealbreakers_actief, { lijst: db.join("; ") }) }));
    return el("div", {}, delen);
  }

  // ---------------------------------------------------------------- stap 7: waar in Italië
  function schermRegio() {
    var R = T.regios, st = T.stappen.regio;
    var delen = [segment(st.titel, S.gebied, [["overal", st.modus_overal], ["alleen", st.modus_alleen]], function (v) { S.gebied = v; teken(); })];
    if (S.gebied !== "alleen") {
      delen.push(el("p", { class: "pv-uitleg", text: st.overal_uitleg }));
      delen.push(el("div", { class: "pv-knoprij" }, [
        el("button", { type: "button", class: "pv-link", text: T.knoppen.alles_aan, onclick: function () { S.regios_uit = []; teken(); } }),
        el("button", { type: "button", class: "pv-link", text: T.knoppen.alles_uit, onclick: function () { S.regios_uit = Object.keys(R).map(Number); teken(); } })]));
      delen.push(el("div", { class: "pv-raster pv-raster-klein" }, Object.keys(R).map(function (k) {
        return kaart(R[k], null, S.regios_uit.indexOf(Number(k)) < 0, function () { toggle(S.regios_uit, Number(k)); teken(); });
      })));
      return el("div", {}, delen);
    }
    delen.push(el("p", { class: "pv-uitleg", text: S.alleen_regios.length ? st.alleen_uitleg : st.alleen_leeg }));
    delen.push(el("div", { class: "pv-raster pv-raster-klein" }, Object.keys(R).map(function (k) {
      var code = Number(k), aan = S.alleen_regios.indexOf(code) >= 0;
      return kaart(R[k], null, aan, function () {
        toggle(S.alleen_regios, code);
        if (aan) S.alleen_provs = S.alleen_provs.filter(function (p) { return !LITE.provincies.some(function (q) { return q[0] === p && q[2] === code; }); });
        teken();
      });
    })));
    S.alleen_regios.forEach(function (code) {
      var provs = LITE ? LITE.provincies.filter(function (p) { return p[2] === code; }) : [];
      if (provs.length < 2) return;
      delen.push(el("h3", { class: "pv-sub-kop", text: fmt(st.provs_titel, { regio: R[code] }) }), el("p", { class: "pv-uitleg", text: st.provs_uitleg }));
      delen.push(el("div", { class: "pv-raster pv-raster-klein" }, provs.map(function (p) {
        return kaart(p[1], null, S.alleen_provs.indexOf(p[0]) >= 0, function () { toggle(S.alleen_provs, p[0]); teken(); });
      })));
    });
    return el("div", {}, delen);
  }

  var SCHERMEN = {
    profiel: schermProfiel, wensen: schermWensen, ligging: function () { return schermKeuzes("ligging"); }, grootte: function () { return schermKeuzes("grootte"); },
    gewichten: schermGewichten, filters: schermFilters, regio: schermRegio
  };

  // ---------------------------------------------------------------- opbouw wizard
  function kop() { return el("header", { class: "pv-kop" }, [el("h1", {}, [el("a", { href: "#", text: T.meta.titel, onclick: function (e) { e.preventDefault(); location.hash = ""; } })])]); }
  function teller() {
    var n = telOver();
    if (n == null) return el("p", { class: "pv-teller", text: T.meta.laden });
    var tekst = n === 0 ? T.teller.geen : n === 1 ? T.teller.een : fmt(T.teller.meer, { n: getal(n, 0) });
    return el("p", { class: "pv-teller" + (n === 0 ? " nul" : ""), role: "status" }, [el("strong", { text: tekst }),
      n > 0 ? el("span", { text: " " + fmt(T.teller.van, { totaal: getal(LITE.n, 0) }) }) : null]);
  }
  function hervatScherm() {
    var H = T.hervat;
    return [kop(), el("section", { class: "pv-intro" }, [el("h2", { text: H.titel, tabindex: "-1", id: "pv-titel" }), el("p", { class: "pv-sub", text: H.tekst }),
      el("div", { class: "pv-knoprij" }, [
        el("button", { type: "button", class: "pv-knop pv-knop-hoofd", text: T.knoppen.verder, onclick: function () { HERVAT = false; teken(); } }),
        el("button", { type: "button", class: "pv-knop pv-knop-terug", text: T.knoppen.opnieuw, onclick: function () { HERVAT = false; S = leeg(); S.stap = 0; teken(); } })])])];
  }
  function intro() {
    return [kop(), el("section", { class: "pv-intro" }, [el("p", { class: "pv-sub", text: T.meta.ondertitel }),
      el("button", { type: "button", class: "pv-knop pv-knop-hoofd", text: T.knoppen.start, onclick: function () { S.stap = 0; teken(); } }),
      el("p", { class: "pv-noot", text: T.disclaimer })])];
  }
  function stapScherm() {
    var sleutel = STAPPEN[S.stap], st = T.stappen[sleutel], laatste = S.stap === STAPPEN.length - 1;
    var magVerder = sleutel !== "profiel" || !!S.profiel;
    var nav = el("nav", { class: "pv-nav" }, [teller(), el("div", { class: "pv-nav-knoppen" }, [
      el("button", { type: "button", class: "pv-knop pv-knop-terug", text: T.knoppen.vorige, onclick: function () { S.stap--; teken(); } }),
      el("button", { type: "button", class: "pv-knop pv-knop-hoofd", disabled: !magVerder,
        text: NAAR_UITSLAG ? T.knoppen.terug_uitslag : laatste ? T.knoppen.resultaat : T.knoppen.volgende,
        onclick: function () { if (laatste || NAAR_UITSLAG) { NAAR_UITSLAG = false; S.stap = STAPPEN.length; } else S.stap++; teken(); } })])]);
    return [kop(), el("section", { class: "pv-stap" }, [
      el("p", { class: "pv-stapnr", text: fmt(T.stap, { n: S.stap + 1, totaal: STAPPEN.length }) }),
      el("div", { class: "pv-voortgang" }, [el("span", { style: "width:" + ((S.stap + 1) / STAPPEN.length * 100) + "%" })]),
      el("h2", { text: st.titel, tabindex: "-1", id: "pv-titel" }), el("p", { class: "pv-uitleg", text: st.uitleg }),
      SCHERMEN[sleutel]()]), nav];
  }

  // ---------------------------------------------------------------- antwoorden naar de server
  function antwoorden(extra) {
    var bu = S.budget;
    return Object.assign({
      lang: window.PV.lang, profiel: S.profiel || "gepensioneerde", verblijf: S.verblijf, expats: S.expats, klimaat: S.klimaat,
      wensen: S.wensen,
      gewichten: S.gewichten, ligging: S.ligging, grootte: S.grootte, filters: S.filters,
      budget: bu.soort && bu.max > 0 && bu.m2 > 0 ? { soort: bu.soort, max: bu.max, m2: bu.m2 } : null,
      regios_uit: S.gebied === "alleen" ? [] : S.regios_uit,
      alleen_regios: S.gebied === "alleen" ? S.alleen_regios : [], alleen_provs: S.gebied === "alleen" ? S.alleen_provs : []
    }, extra || {});
  }
  function scoreVraag(extra) {
    return haal(window.PV.score, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(antwoorden(extra)) });
  }

  // ---------------------------------------------------------------- uitslag
  function critLabel(id) { var c = CRIT.filter(function (x) { return x.id === id; })[0]; return c ? c.label : id; }
  function balk(score) {
    return el("span", { class: "pv-balk", "aria-hidden": "true" }, [el("span", { style: "width:" + (score == null ? 0 : score) + "%" })]);
  }
  function woonlast(wl) {
    var U = T.uitslag;
    if (!wl) return null;
    if (wl.prijs == null) return el("p", { class: "pv-woonlast", text: U.woonlast_geen });
    return el("p", { class: "pv-woonlast" }, [el("strong", { text: fmt(wl.soort === "huren" ? U.woonlast_huren : U.woonlast_kopen, { prijs: getal(wl.prijs, 0), m2: wl.m2 }) }),
      " · " + (wl.onder_pct >= 5 ? fmt(U.woonlast_onder, { pct: wl.onder_pct }) : U.woonlast_rond)]);
  }
  function plaatsKaart(c, rang) {
    var U = T.uitslag, gekozen = S.vergelijk.indexOf(c.istat) >= 0;
    var info = [c.prov, c.regio, c.inwoners != null ? fmt(U.inwoners, { n: getal(c.inwoners, 0) }) : null,
      c.hoogte_m != null ? fmt(U.hoogte, { n: getal(c.hoogte_m, 0) }) : null].filter(Boolean).join(" · ");
    var deelLijst = function (titel, ids, cls) {
      if (!ids.length) return null;
      return el("div", { class: "pv-punten " + cls }, [el("h4", { text: titel }), el("ul", {}, ids.map(function (id) {
        return el("li", {}, [el("span", { text: critLabel(id) }), balk(c.deel[id]), el("span", { class: "pv-getal", text: String(c.deel[id]) })]);
      }))]);
    };
    return el("li", { class: "pv-plaats" }, [
      el("div", { class: "pv-plaats-kop" }, [
        rang ? el("span", { class: "pv-rang", text: String(rang) }) : null,
        el("div", { class: "pv-plaats-naam" }, [el("h3", {}, [el("a", { href: "#/gemeente/" + c.istat, text: c.naam })]), el("p", { text: info }),
          c.trend ? el("span", { class: "pv-badge-trend", title: C.trends.uitleg, text: U.trend }) : null]),
        el("div", { class: "pv-score" }, [el("strong", { text: String(c.score) }), el("span", { text: "/100" })])]),
      woonlast(c.woonlast),
      el("div", { class: "pv-tests" }, [
        el("span", { class: "pv-chip" }, [U.winter + " ", el("strong", { text: c.winter == null ? LEEG : String(c.winter) })]),
        el("span", { class: "pv-chip" + (c.tachtig != null && c.tachtig < C.aandachtspunten.tachtig_waarschuwing.drempel ? " laag" : "") },
          [U.tachtig + " ", el("strong", { text: c.tachtig == null ? LEEG : String(c.tachtig) })])]),
      deelLijst(U.sterk, c.sterk, "sterk"), deelLijst(U.zwak, c.zwak, "zwak"),
      c.geen_data.length ? el("p", { class: "pv-geen-data", text: fmt(U.geen_data, { lijst: c.geen_data.map(critLabel).join(", ") }) }) : null,
      el("div", { class: "pv-knoprij" }, [
        el("a", { class: "pv-knop pv-knop-klein", href: "#/gemeente/" + c.istat, text: T.knoppen.bekijk }),
        vinkje(T.knoppen.vergelijk, gekozen, function (v) {
          if (v && S.vergelijk.length >= MAX_VERGELIJK) { alert(U.vergelijk_max); teken(); return; }
          toggle(S.vergelijk, c.istat); bewaar(); tekenVergelijkBalk();
        })])
    ]);
  }
  function tekenVergelijkBalk() {
    var oud = document.getElementById("pv-vergelijkbalk"); if (oud) oud.remove();
    if (!S.vergelijk.length) return;
    app.appendChild(el("nav", { class: "pv-nav", id: "pv-vergelijkbalk" }, [el("div", { class: "pv-nav-knoppen" }, [
      el("button", { type: "button", class: "pv-knop pv-knop-terug", text: T.knoppen.wis_vergelijk, onclick: function () { S.vergelijk = []; bewaar(); teken(); } }),
      el("a", { class: "pv-knop pv-knop-hoofd", href: "#/vergelijk", text: fmt(T.knoppen.vergelijk_n, { n: S.vergelijk.length }) })])]));
  }
  // overzicht van de keuzes met "Wijzigen" per stap
  function overzicht() {
    var O = T.overzicht, w = S.wensen, f = effFilters();
    var opties = function (sl) { return S[sl].length ? S[sl].map(function (k) { return T.stappen[sl].opties[k][0]; }).join(", ") : O.alles; };
    var eisen = Object.keys(f).filter(function (k) { return f[k] && !(k === "rode_zone_uit" && f[k] === true); }).length;
    var gebied = S.gebied === "alleen" && S.alleen_regios.length
      ? fmt(O.alleen, { lijst: S.alleen_regios.map(function (r) { return T.regios[r]; }).join(", ") })
      : S.regios_uit.length ? fmt(O.regios_uit, { n: S.regios_uit.length }) : O.overal;
    var p = S.profiel && profiel(S.profiel);
    var rijen = [
      ["profiel", p ? p.label : O.geen],
      ["wensen", fmt(O.wensen, { d: w.dealbreakers.length, m: w.must.length, n: w.nice.length })],
      ["ligging", opties("ligging")], ["grootte", opties("grootte")],
      ["gewichten", CRIT.slice().sort(function (a, b) { return (S.gewichten[b.id] || 0) - (S.gewichten[a.id] || 0); }).slice(0, 3)
        .map(function (c) { return c.label + " " + (S.gewichten[c.id] || 0); }).join(", ")],
      ["filters", eisen === 0 ? O.filters_geen : eisen === 1 ? O.filters_een : fmt(O.filters, { n: eisen })], ["regio", gebied]];
    return el("details", { class: "pv-overzicht" }, [el("summary", { text: O.titel }), el("ul", {}, rijen.map(function (r) {
      return el("li", {}, [el("span", {}, [el("strong", { text: T.stappen[r[0]].titel }), el("br"), r[1]]),
        el("button", { type: "button", class: "pv-link", text: T.knoppen.wijzigen, onclick: function () { NAAR_UITSLAG = true; S.stap = STAPPEN.indexOf(r[0]); teken(); } })]);
    }))]);
  }
  function uitslag() {
    var U = T.uitslag;
    app.innerHTML = "";
    app.appendChild(kop());
    var sectie = el("section", { class: "pv-uitslag" }, [el("h2", { text: U.titel, tabindex: "-1", id: "pv-titel" }), el("p", { class: "pv-uitleg", text: U.bezig })]);
    app.appendChild(sectie);
    scoreVraag().then(function (res) {
      sectie.innerHTML = "";
      sectie.appendChild(el("h2", { text: U.titel, tabindex: "-1", id: "pv-titel" }));
      sectie.appendChild(el("p", { class: "pv-uitleg", text: res.aantal ? fmt(U.aantal, { n: getal(res.aantal, 0) }) + " " + U.uitleg : U.leeg }));
      if ((res.dealbreakers || []).length) sectie.appendChild(el("p", { class: "pv-noot", text: fmt(U.dealbreakers, { lijst: res.dealbreakers.map(critLabel).join(", ") }) }));
      sectie.appendChild(overzicht());
      sectie.appendChild(el("ol", { class: "pv-lijst" }, res.top.map(function (c, i) { return plaatsKaart(c, i + 1); })));
      sectie.appendChild(el("div", { class: "pv-knoprij" }, [
        el("button", { type: "button", class: "pv-knop pv-knop-terug", text: T.knoppen.aanpassen, onclick: function () { S.stap = STAPPEN.length - 1; teken(); } }),
        el("button", { type: "button", class: "pv-knop", text: T.knoppen.opnieuw, onclick: function () { S = leeg(); S.stap = 0; teken(); } })]));
      sectie.appendChild(voet());
      tekenVergelijkBalk();
    }).catch(function () { sectie.lastChild.textContent = U.fout; });
  }
  function voet() {
    return el("footer", { class: "pv-bronnen" }, [T.bronnen_voet + " ", el("a", { href: T.osm_link, target: "_blank", rel: "noopener", text: T.osm }),
      el("br"), T.disclaimer]);
  }

  // ---------------------------------------------------------------- gemeentepagina
  function bronRegel(kolom, g) {
    var G = T.gemeente, id = (C.bron_van || {})[kolom], b = id && BRONNEN[id];
    if (!b) return null;
    var delen = [fmt(G.bron, { bron: b.bron }), fmt(G.peildatum, { p: b.peildatum }), fmt(G.schaal, { s: b.schaalniveau })];
    if ((g.provinciecijfer || []).length && ["koop_m2", "huur_m2", "glasvezel_pct"].indexOf(kolom) >= 0) delen.push(G.provinciecijfer);
    return el("span", { class: "pv-bronregel", text: delen.join(" · ") });
  }
  function waardeTekst(kolom, v) {
    var G = T.gemeente;
    if (v == null) return G.geen_data;
    if (kolom === "regime7") return v ? G.waarde_ja : G.waarde_nee;
    if (typeof v === "boolean") return v ? G.waarde_ja : G.waarde_nee;
    var tpl = T.waarden[kolom] || "{v}";
    return fmt(tpl, { v: typeof v === "number" ? getal(v, kolom.indexOf("pct") >= 0 || kolom === "bedden_per_inw" ? 2 : 1) : v });
  }
  function waardeRij(label, kolom, g) {
    return el("li", { class: "pv-waarde" }, [el("span", { class: "pv-waarde-label", text: label }),
      el("strong", { text: waardeTekst(kolom, g.w[kolom]) }), bronRegel(kolom, g)]);
  }
  function totaalJS(s) {
    var sw = 0, sg = 0;
    Object.keys(S.gewichten).forEach(function (k) {
      var w = S.gewichten[k] || 0, v = k === "klimaat" && S.klimaat === "seizoenen" && s.klimaat_seizoenen !== undefined ? s.klimaat_seizoenen : s[k];
      if (k === "expats") { if (S.expats === "maakt_niet_uit") w = 0; else if (S.expats === "weinig" && v != null) v = 100 - v; }
      if (w > 0 && v != null) { sw += w * v; sg += w; }
    });
    return sg ? Math.round(sw / sg) : null;
  }
  function testKaart(titel, uitleg, score, delen, labels, waarschuwing) {
    return el("section", { class: "pv-testkaart" }, [el("h3", { text: titel }), el("p", { class: "pv-uitleg", text: uitleg }),
      el("p", { class: "pv-testscore" }, [el("strong", { text: score == null ? LEEG : String(score) }), " / 100"]),
      waarschuwing || null,
      el("ul", { class: "pv-delen" }, Object.keys(labels).map(function (k) {
        return el("li", {}, [el("span", { text: labels[k] }), balk(delen[k]), el("span", { class: "pv-getal", text: delen[k] == null ? T.gemeente.geen_data : String(delen[k]) })]);
      }))]);
  }
  // extra regels onder een criterium: gidsprijs bij prijs, stationsnamen bij bereikbaarheid
  function extraBijCriterium(id, g) {
    var G = T.gemeente, w = g.w, regels = [];
    if (id === "prijs") {
      var gp = (C.gids_prijzen || {}).regios || {}, v = gp[g.regio];
      if (v != null) regels.push(fmt(G.gidsprijs, { v: getal(v, 0), regio: g.regio, toelichting: ((C.gids_prijzen.toelichting || {})[g.regio]) || C.gids_prijzen.peildatum }));
    }
    if (id === "klimaat" && w.klimaat_geschat) regels.push(G.klimaat_geschat);
    if (id === "bereikbaarheid") {
      var lv = C.luchthavens, m = String(w.luchthaven_jaarrond_naam || "").match(/\(([A-Z]{3})\)$/), vb = m && lv && lv.verbindingen && lv.verbindingen[m[1]];
      if (vb) {
        var lijst = [];
        if (vb.nl.length) lijst.push(fmt(G.vluchten_nl, { l: vb.nl.join(", ") }));
        if (vb.be.length) lijst.push(fmt(G.vluchten_be, { l: vb.be.join(", ") }));
        regels.push(fmt(G.vluchten, { lh: w.luchthaven_jaarrond_naam.replace(/ \([A-Z]{3}\)$/, ""), lijst: lijst.join("; ") }));
      }
      if (w.station_hs_naam) regels.push(fmt(G.station_hs, { naam: w.station_hs_naam }));
      if (w.station_nacht_naam) regels.push(fmt(G.station_nacht, { naam: w.station_nacht_naam }));
    }
    return regels.length ? el("p", { class: "pv-noot" }, regels.map(function (r, i) { return i ? [el("br"), r] : [r]; }).reduce(function (a, b) { return a.concat(b); }, [])) : null;
  }
  // blok "Meer hierover in de gids": met url een link, anders de verwijzing als tekst
  function gidsBlok() {
    var G = T.gemeente, gl = C.gids_links || { verwijzingen: [] };
    if (!gl.verwijzingen.length) return null;
    return el("section", { class: "pv-blok pv-gids" }, [el("h3", { text: G.gids_titel }), el("p", { class: "pv-noot", text: G.gids_uitleg }),
      el("ul", {}, gl.verwijzingen.map(function (v) {
        var p = " (" + fmt(G.gids_paragraaf, { p: v.paragraaf, pagina: v.pagina || "" }).replace(/, \)?$/, "") + ")";
        return el("li", {}, [v.url ? el("a", { href: v.url, target: "_blank", rel: "noopener", text: v.label }) : v.label, p]);
      }))]);
  }
  function gemeentePagina(istat) {
    var G = T.gemeente;
    app.innerHTML = "";
    app.appendChild(kop());
    var sectie = el("section", { class: "pv-gemeente" }, [el("p", { text: T.uitslag.bezig })]);
    app.appendChild(sectie);
    window.scrollTo(0, 0);
    Promise.all([haal(window.PV.gemeente + "?istat=" + encodeURIComponent(istat)), scoreVraag({ alternatieven_voor: istat, n: 1 }).catch(function () { return {}; })])
      .then(function (res) {
        var g = res[0], alt = res[1].alternatieven || [], w = g.w, U = T.uitslag;
        sectie.innerHTML = "";
        var tot = totaalJS(g.s);
        sectie.appendChild(el("a", { href: "#", class: "pv-link", text: "← " + T.knoppen.terug_lijst, onclick: function (e) { e.preventDefault(); history.back(); } }));
        sectie.appendChild(el("div", { class: "pv-plaats-kop" }, [
          el("div", { class: "pv-plaats-naam" }, [el("h2", { text: g.naam, tabindex: "-1", id: "pv-titel" }),
            el("p", { text: [g.prov, g.provincie, g.regio, fmt(U.inwoners, { n: getal(g.inwoners, 0) }), g.hoogte_m != null ? fmt(U.hoogte, { n: g.hoogte_m }) : null].filter(Boolean).join(" · ") }),
            g.trend ? el("p", {}, [el("span", { class: "pv-badge-trend", text: C.trends.label }), " " + C.trends.uitleg]) : null]),
          el("div", { class: "pv-score", title: G.totaal }, [el("strong", { text: tot == null ? LEEG : String(tot) }), el("span", { text: "/100" })])]));

        if (g.hoogte_m != null) sectie.appendChild(el("p", { class: "pv-noot", text: fmt(G.hoogte_uitleg, { n: getal(g.hoogte_m, 0) }) }));
        if (w.sciolto_mafia_jaar) {
          var sm = C.sciolti_mafia, smg = sm.gemeenten.filter(function (x) { return x.istat === g.istat; })[0] || {};
          sectie.appendChild(el("section", { class: "pv-blok pv-label-maffia" }, [el("h3", { text: G.maffia_titel }),
            el("p", {}, [el("strong", { text: fmt(G.maffia, { jaar: w.sciolto_mafia_jaar, org: smg.organisatie || "", keer: smg.keer > 1 ? fmt(G.maffia_keer, { n: smg.keer }) : "" }) }), " " + sm.tekst]),
            smg.bron ? el("p", { class: "pv-noot", text: fmt(G.maffia_bron, { bron: smg.bron }) }) : null,
            sm.url && !smg.bron ? el("a", { href: sm.url, target: "_blank", rel: "noopener", text: G.maffia_link }) : null]));
        }

        // deelscores met indicatoren en bronnen
        sectie.appendChild(el("h3", { class: "pv-sub-kop", text: G.deelscores }));
        sectie.appendChild(el("ul", { class: "pv-criteria" }, CRIT.map(function (c) {
          var s = c.id === "klimaat" && S.klimaat === "seizoenen" && g.s.klimaat_seizoenen !== undefined ? g.s.klimaat_seizoenen : g.s[c.id], gw = S.gewichten[c.id] || 0;
          return el("li", { class: "pv-criterium" + (s == null ? " leeg" : "") }, [
            el("div", { class: "pv-criterium-kop" }, [el("strong", { text: c.label }), balk(s),
              el("span", { class: "pv-getal", text: s == null ? G.geen_data : String(s) })]),
            el("p", { class: "pv-noot" }, [fmt(G.gewicht, { n: gw }) + " · ", basisLabel(c)]),
            c.indicatoren.length ? el("ul", { class: "pv-waarden" }, c.indicatoren.map(function (i) { return waardeRij(i.label, i.kolom, g); })) : null,
            extraBijCriterium(c.id, g)]);
        })));

        // risicoblok (altijd)
        var zone = w.seismische_zone;
        var vulk = !w.vulkaan_deel ? G.vulkaan_geen : fmt(w.vulkaan_deel === "geheel" ? G.vulkaan_geheel : G.vulkaan_deels, { v: w.vulkaan });
        sectie.appendChild(el("section", { class: "pv-risico" }, [el("h3", { text: G.risico_titel }), el("p", { class: "pv-uitleg", text: G.risico_uitleg }),
          el("ul", { class: "pv-waarden" }, [
            el("li", { class: "pv-waarde" }, [el("span", { class: "pv-waarde-label", text: G.seismisch }),
              el("strong", { text: zone ? fmt(G.seismisch_zone, { z: w.seismische_zone_detail || zone, tekst: G.zones[zone] }) : G.geen_data }), bronRegel("seismische_zone", g)]),
            waardeRij(G.frane, "frane_pop_pct", g), waardeRij(G.alluvioni, "alluvioni_pop_pct", g), waardeRij(G.bosbrand, "bosbrand_pct", g),
            el("li", { class: "pv-waarde" }, [el("span", { class: "pv-waarde-label", text: G.vulkaan }), el("strong", { text: vulk }), bronRegel("vulkaan", g)])]),
          el("a", { href: "https://idrogeo.isprambiente.it/app/iffi?@=" + g.lat + "," + g.lon + ",14", target: "_blank", rel: "noopener", text: G.idrogeo_link })]));

        // tests
        var tw = C.aandachtspunten.tachtig_waarschuwing;
        sectie.appendChild(el("div", { class: "pv-tests-groot" }, [
          testKaart(G.winter_titel, G.winter_uitleg, g.t.winter, g.t.winter_delen, G.winter_delen),
          testKaart(G.tachtig_titel, G.tachtig_uitleg, g.t.tachtig, g.t.tachtig_delen, G.tachtig_delen,
            g.t.tachtig != null && g.t.tachtig < tw.drempel ? el("p", { class: "pv-let-op" }, [el("strong", { text: tw.tekst }), " " + tw.onderbouwing]) : null)]));

        // 7%-regime
        var r7 = w.regime7_status || "nee", rg = C.regime7;
        var r7delen = [el("h3", { text: G.regime7_titel }), el("p", {}, [el("strong", { text: G.regime7[r7] }), w.regime7_reden && r7 !== "te_verifieren" ? " (" + w.regime7_reden + ")" : ""])];
        if (r7 === "ja" && w.lea != null && w.lea < rg.waarschuwing_lea.drempel) r7delen.push(el("p", { class: "pv-let-op", text: rg.waarschuwing_lea.tekst }));
        r7delen.push(el("p", { class: "pv-noot", text: rg.disclaimer }), bronRegel("regime7_status", g));
        sectie.appendChild(el("section", { class: "pv-blok" }, r7delen));

        // herverkoop
        var nt = w.ntn_per_100, hw = C.aandachtspunten.herverkoop_waarschuwing;
        sectie.appendChild(el("section", { class: "pv-blok" }, [el("h3", { text: G.herverkoop_titel }),
          el("p", { text: nt == null ? G.herverkoop_geen : fmt(G.herverkoop, { n: getal(nt, 1) }) }),
          nt != null && nt < hw.drempel ? el("p", { class: "pv-let-op", text: hw.tekst }) : null]));

        // checklist
        sectie.appendChild(el("section", { class: "pv-blok" }, [el("h3", { text: G.checklist_titel }),
          el("ul", { class: "pv-checklist" }, C.aandachtspunten.checklist.map(function (p) { return el("li", { text: p }); })),
          el("p", { class: "pv-noot", text: fmt(G.niet_in_score, { lijst: C.aandachtspunten.niet_in_score.join(", ").toLowerCase() }) })]));

        sectie.appendChild(gidsBlok());

        // alternatieven
        if (alt.length) {
          sectie.appendChild(el("section", {}, [el("h3", { class: "pv-sub-kop", text: G.alternatieven_titel }), el("p", { class: "pv-uitleg", text: G.alternatieven_uitleg }),
            el("ol", { class: "pv-lijst" }, alt.map(function (c) { return plaatsKaart(c, null); }))]));
        }
        sectie.appendChild(voet());
        document.getElementById("pv-titel").focus({ preventScroll: true });
      }).catch(function () { sectie.innerHTML = ""; sectie.appendChild(el("p", { class: "pv-fout", text: G.onbekend })); });
  }

  // ---------------------------------------------------------------- vergelijken
  function vergelijkPagina() {
    var V = T.vergelijk, G = T.gemeente;
    app.innerHTML = "";
    app.appendChild(kop());
    var sectie = el("section", { class: "pv-vergelijk" }, [el("h2", { text: V.titel, tabindex: "-1", id: "pv-titel" })]);
    app.appendChild(sectie);
    if (!S.vergelijk.length) { sectie.appendChild(el("p", { text: V.leeg })); return; }
    Promise.all(S.vergelijk.map(function (id) { return haal(window.PV.gemeente + "?istat=" + id); })).then(function (gs) {
      var rij = function (label, cellen, cls) { return el("tr", { class: cls || "" }, [el("th", { scope: "row", text: label })].concat(cellen.map(function (c) { return el("td", {}, [c]); }))); };
      var kop2 = el("tr", {}, [el("th", { text: V.criterium })].concat(gs.map(function (g) { return el("th", { scope: "col" }, [el("a", { href: "#/gemeente/" + g.istat, text: g.naam }), el("br"), g.prov]); })));
      var rijen = [rij(G.totaal, gs.map(function (g) { var t = totaalJS(g.s); return el("strong", { text: t == null ? LEEG : String(t) }); }), "pv-totaalrij")];
      CRIT.forEach(function (c) { rijen.push(rij(c.label, gs.map(function (g) { return g.s[c.id] == null ? G.geen_data : String(g.s[c.id]); }))); });
      rijen.push(rij(T.uitslag.winter, gs.map(function (g) { return g.t.winter == null ? LEEG : String(g.t.winter); })));
      rijen.push(rij(T.uitslag.tachtig, gs.map(function (g) { return g.t.tachtig == null ? LEEG : String(g.t.tachtig); })));
      [["koop_m2", "Koopprijs"], ["min_ziekenhuis", G.tachtig_delen.ziekenhuis], ["min_luchthaven_jaarrond", "Luchthaven (hele jaar)"],
       ["uur_naar_huis", "Naar huis"], ["seismische_zone", G.seismisch]].forEach(function (x) {
        rijen.push(rij(x[1], gs.map(function (g) { return waardeTekst(x[0], g.w[x[0]]); })));
      });
      sectie.appendChild(el("div", { class: "pv-tabelrol" }, [el("table", { class: "pv-tabel" }, [el("thead", {}, [kop2]), el("tbody", {}, rijen)])]));
      sectie.appendChild(el("button", { type: "button", class: "pv-knop pv-knop-terug", text: T.knoppen.terug_lijst, onclick: function () { history.back(); } }));
      sectie.appendChild(voet());
    });
  }

  // ---------------------------------------------------------------- router
  var vorigeStap = null;
  function teken() {
    bewaar();
    var h = location.hash, m = h.match(/^#\/gemeente\/(\d{6})$/);
    if (m) return gemeentePagina(m[1]);
    if (h === "#/vergelijk") return vergelijkPagina();
    if (S.stap >= STAPPEN.length && !HERVAT) return uitslag();
    var actief = document.activeElement && document.activeElement.id;
    var inhoud = HERVAT ? hervatScherm() : S.stap < 0 ? intro() : stapScherm();
    app.innerHTML = "";
    inhoud.forEach(function (n) { app.appendChild(n); });
    if (S.stap !== vorigeStap) {
      vorigeStap = S.stap; window.scrollTo(0, 0);
      var t = document.getElementById("pv-titel"); if (t) t.focus({ preventScroll: true });
    } else if (actief && document.getElementById(actief)) document.getElementById(actief).focus();
  }
  window.addEventListener("hashchange", function () { vorigeStap = null; teken(); });

  haal(window.PV.lite).then(function (d) { LITE = d; teken(); })
    .catch(function () { app.innerHTML = ""; app.appendChild(kop()); app.appendChild(el("p", { class: "pv-fout", text: T.meta.fout_laden })); });
  teken();
})();
