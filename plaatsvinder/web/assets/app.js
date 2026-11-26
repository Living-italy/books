/* Plaatsvinder Italië: wizard, ranglijst, gemeentepagina en vergelijken. Vanilla JS.
 * Stappen: profiel, zoekgebied, ligging, grootte, eigenschappen (noodzakelijk, meewegen of onbelangrijk, met niveau),
 * gewichten. De teller filtert in de browser op comuni-lite.json met dezelfde regels als voldoet() in lib/model.php;
 * de scores rekent score.php uit. */
(function () {
  "use strict";

  var T = window.PV.taal, C = window.PV.cfg;
  var app = document.getElementById("app");
  var LITE = null;
  var OPSLAG = "plaatsvinder.v3";
  var STAPPEN = ["profiel", "gebied", "ligging", "grootte", "eigenschappen", "gewichten"];
  var EC = C.eigenschappen, EIG = EC.eigenschappen, GW = EC.gewicht, BRONNEN = C.bronnen.bronnen;
  var EIDX = {}; EIG.forEach(function (e, i) { EIDX[e.id] = i; });
  var MAX_VERGELIJK = 3;
  var LEEG = T.gemeente.geen_data;

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
  function getal(n, dec) { return Number(n).toLocaleString("nl-NL", { maximumFractionDigits: dec == null ? 1 : dec }); }
  function toggle(l, v) { var i = l.indexOf(v); if (i >= 0) l.splice(i, 1); else l.push(v); }
  function haal(url, opties) { return fetch(url, opties).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); }); }
  function profiel(id) { return C.profielen.profielen.filter(function (p) { return p.id === id; })[0]; }

  // ---------------------------------------------------------------- startkeuzes (gelijk aan startkeuzes() in model.php)
  function criteriumgewichten(pid, verblijf) {
    var p = profiel(pid), g = Object.assign({}, p.gewichten);
    (p.vragen || []).forEach(function (v) { v.opties.forEach(function (o) { if (o.id === verblijf && o.effect) Object.assign(g, o.effect); }); });
    return g;
  }
  function startkeuzes(pid, verblijf) {
    var g = criteriumgewichten(pid, verblijf), uitz = EC.profielen[pid] || {}, k = {};
    EIG.forEach(function (e) {
      var w = g[e.criterium] || 0;
      k[e.id] = w > 0 ? { status: "meewegen", niveau: e.standaard_niveau, gewicht: Math.max(GW.min, Math.min(GW.max, Math.round(w / 2))) }
                      : { status: "onbelangrijk", niveau: e.standaard_niveau, gewicht: GW.standaard };
      if (uitz[e.id]) Object.assign(k[e.id], uitz[e.id]);
    });
    return k;
  }
  function telgewicht(k) { return k.status === "noodzakelijk" ? GW.noodzakelijk : k.status === "onbelangrijk" ? GW.onbelangrijk : k.gewicht; }

  // ---------------------------------------------------------------- toestand
  function leeg() {
    return { stap: -1, profiel: "", verblijf: "permanent", eigenschappen: {}, ligging: [], grootte: [],
             gebied: "overal", alleen_regios: [], alleen_provs: [], vergelijk: [] };
  }
  function schoon(x) {                                    // keuzes uit een gedeelde link of opslag controleren
    var basis = x.profiel ? startkeuzes(x.profiel, x.verblijf) : {}, k = {};
    EIG.forEach(function (e) {
      var v = (x.eigenschappen || {})[e.id] || {}, b = basis[e.id] || { status: "meewegen", niveau: e.standaard_niveau, gewicht: GW.standaard };
      k[e.id] = {
        status: ["noodzakelijk", "meewegen", "onbelangrijk"].indexOf(v.status) >= 0 ? v.status : b.status,
        niveau: v.niveau >= 0 && v.niveau < e.niveaus.length ? Number(v.niveau) : b.niveau,
        gewicht: v.gewicht >= GW.min && v.gewicht <= GW.max ? Number(v.gewicht) : b.gewicht
      };
    });
    x.eigenschappen = k;
    if (["overal", "regios", "provincies"].indexOf(x.gebied) < 0) x.gebied = "overal";
    return x;
  }
  var S = leeg(), HERVAT = false, NAAR_UITSLAG = false;
  try {
    var b = JSON.parse(localStorage.getItem(OPSLAG) || "null");
    if (b) { S = schoon(Object.assign(leeg(), b)); HERVAT = !!S.profiel && !location.hash; }
  } catch (e) { /* geen opslag */ }
  try {                                                   // gedeelde link: ?keuzes=...
    var q = new URLSearchParams(location.search).get("keuzes");
    if (q) {
      var k = JSON.parse(decodeURIComponent(escape(atob(q.replace(/-/g, "+").replace(/_/g, "/")))));
      S = schoon(Object.assign(leeg(), { profiel: k.p || "gepensioneerde", verblijf: k.v || "permanent", eigenschappen: k.e || {},
        ligging: k.l || [], grootte: k.g || [], gebied: k.z || "overal", alleen_regios: k.r || [], alleen_provs: k.pr || [] }));
      S.stap = STAPPEN.length; HERVAT = false;
      history.replaceState(null, "", location.pathname);
    }
  } catch (e) { /* ongeldige link: gewoon beginnen */ }
  function bewaar() { try { localStorage.setItem(OPSLAG, JSON.stringify(S)); } catch (e) { /* geen opslag */ } }

  // ---------------------------------------------------------------- teller (gelijk aan voldoet() in model.php)
  function behaald(i, j) { var ch = LITE.e.charAt(i * EIG.length + j); return ch === "-" ? null : Number(ch); }
  function telOver(zonderGebied) {
    if (!LITE) return null;
    var L = LITE, n = 0, masker = 0, nood = [];
    S.ligging.forEach(function (x) { masker |= 1 << L.ligging.indexOf(x); });
    EIG.forEach(function (e, j) { var k = S.eigenschappen[e.id]; if (k && k.status === "noodzakelijk") nood.push([j, k.niveau + 1]); });
    var gr = S.grootte.map(Number);
    var regios = !zonderGebied && S.gebied === "regios" && S.alleen_regios.length ? S.alleen_regios : null;
    var provs = !zonderGebied && S.gebied === "provincies" && S.alleen_provs.length ? S.alleen_provs : null;
    for (var i = 0; i < L.n; i++) {
      var pv = L.provincies[L.pv[i]];
      if (masker && !(L.lig[i] & masker)) continue;
      if (gr.length && gr.indexOf(L.gr[i]) < 0) continue;
      if (regios && regios.indexOf(pv[2]) < 0) continue;
      if (provs && provs.indexOf(pv[0]) < 0) continue;
      if (((L.f[i] >> 2) & 3) === 2) continue;          // rode zone, geheel
      var ok = true;
      for (var x = 0; x < nood.length && ok; x++) { var a = behaald(i, nood[x][0]); if (a !== null && a < nood[x][1]) ok = false; }
      if (ok) n++;
    }
    return n;
  }
  function haaltNiveau(j, niveau) {                       // hoeveel gemeenten (heel Italië) dit niveau halen
    if (!LITE) return null;
    var n = 0;
    for (var i = 0; i < LITE.n; i++) { var a = behaald(i, j); if (a === null || a >= niveau) n++; }
    return n;
  }

  // ---------------------------------------------------------------- onderdelen
  function kaart(titel, uitleg, aan, klik) {
    return el("button", { type: "button", class: "pv-kaart" + (aan ? " aan" : ""), "aria-pressed": aan ? "true" : "false", onclick: klik },
      [el("span", { class: "pv-kaart-titel", text: titel }), uitleg ? el("span", { class: "pv-kaart-uitleg", text: uitleg }) : null]);
  }
  function segment(label, waarde, opties, kies) {
    return el("div", { class: "pv-segment", role: "radiogroup", "aria-label": label }, opties.map(function (o) {
      var aan = waarde === o[0];
      return el("button", { type: "button", role: "radio", "aria-checked": aan ? "true" : "false", class: aan ? "aan" : "", text: o[1],
        onclick: function () { kies(o[0]); } });
    }));
  }
  function balk(score) { return el("span", { class: "pv-balk", "aria-hidden": "true" }, [el("span", { style: "width:" + (score == null ? 0 : score) + "%" })]); }
  function eLabel(id) { return EIG[EIDX[id]].label; }
  function waardeE(e, v) {
    if (v == null) return LEEG;
    var rang = (T.rang || {})[e.kolom];
    if (rang) return rang[String(v)] || String(v);
    return fmt(e.eenheid, { v: typeof v === "number" ? getal(v, Math.abs(v) < 10 ? 2 : 0) : v });
  }

  // ---------------------------------------------------------------- stap 1: profiel
  function kiesProfiel(id) {
    S.profiel = id;
    S.eigenschappen = startkeuzes(id, S.verblijf);
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
        S.verblijf = id; S.eigenschappen = startkeuzes(S.profiel, id); teken();
      }));
      v.opties.forEach(function (o) { if (o.id === S.verblijf && o.uitleg) delen.push(el("p", { class: "pv-uitleg", text: o.uitleg })); });
    });
    delen.push(el("p", { class: "pv-noot", text: st.startkeuzes }));
    return el("div", {}, delen);
  }

  // ---------------------------------------------------------------- stap 2: zoekgebied
  var ZOEK = "";
  function schermGebied() {
    var R = T.regios, st = T.stappen.gebied;
    var delen = [segment(st.titel, S.gebied, [["overal", st.overal], ["regios", st.regios], ["provincies", st.provincies]],
      function (v) { S.gebied = v; teken(); })];
    if (S.gebied === "overal") { delen.push(el("p", { class: "pv-tip", text: st.tip })); return el("div", {}, delen); }
    var gesorteerd = Object.keys(R).sort(function (a, b) { return R[a].localeCompare(R[b], "it"); });
    if (S.gebied === "regios") {
      delen.push(el("p", { class: "pv-uitleg", text: S.alleen_regios.length ? fmt(st.regios_gekozen, { n: S.alleen_regios.length }) : st.regios_leeg }));
      delen.push(el("div", { class: "pv-raster pv-raster-klein" }, gesorteerd.map(function (k) {
        var code = Number(k);
        return kaart(R[k], null, S.alleen_regios.indexOf(code) >= 0, function () { toggle(S.alleen_regios, code); teken(); });
      })));
      return el("div", {}, delen);
    }
    delen.push(el("p", { class: "pv-uitleg", text: S.alleen_provs.length ? fmt(st.provs_gekozen, { n: S.alleen_provs.length }) : st.provs_leeg }));
    var zoek = el("input", { type: "search", id: "pv-zoek-prov", value: ZOEK, placeholder: st.zoek_plaats, autocomplete: "off" });
    var lijst = el("div", { class: "pv-provlijst" });
    var vul = function () {
      var q = ZOEK.trim().toLowerCase();
      lijst.innerHTML = "";
      gesorteerd.forEach(function (k) {
        var provs = (LITE ? LITE.provincies : []).filter(function (p) {
          return p[2] === Number(k) && (!q || p[1].toLowerCase().indexOf(q) >= 0 || R[k].toLowerCase().indexOf(q) >= 0);
        });
        if (!provs.length) return;
        lijst.appendChild(el("h3", { class: "pv-sub-kop", text: R[k] }));
        lijst.appendChild(el("div", { class: "pv-raster pv-raster-klein" }, provs.map(function (p) {
          return kaart(p[1], null, S.alleen_provs.indexOf(p[0]) >= 0, function () { toggle(S.alleen_provs, p[0]); teken(); });
        })));
      });
      if (!lijst.childNodes.length) lijst.appendChild(el("p", { class: "pv-uitleg", text: st.niets_gevonden }));
    };
    zoek.addEventListener("input", function () { ZOEK = zoek.value; vul(); });
    vul();
    delen.push(el("div", { class: "pv-veld" }, [el("label", { for: "pv-zoek-prov", text: st.zoek }), zoek]), lijst);
    return el("div", {}, delen);
  }

  // ---------------------------------------------------------------- stap 3 en 4: ligging en grootte
  function schermKeuzes(sleutel) {
    var opties = T.stappen[sleutel].opties;
    return el("div", { class: "pv-raster" }, Object.keys(opties).map(function (x) {
      return kaart(opties[x][0], opties[x][1], S[sleutel].indexOf(x) >= 0, function () { toggle(S[sleutel], x); teken(); });
    }));
  }

  // ---------------------------------------------------------------- stap 5: eigenschappen
  function eigenschapRij(e) {
    var st = T.stappen.eigenschappen, k = S.eigenschappen[e.id], j = EIDX[e.id];
    var info = el("p", { class: "pv-eig-uitleg", id: "pv-ei-" + e.id, hidden: true, text: e.uitleg });
    var kop = el("div", { class: "pv-eig-kop" }, [el("h4", { text: e.label }),
      el("button", { type: "button", class: "pv-info", "aria-label": fmt(st.info, { item: e.label }), "aria-expanded": "false",
        "aria-controls": info.id, text: "i", onclick: function () { info.hidden = !info.hidden; this.setAttribute("aria-expanded", info.hidden ? "false" : "true"); } })]);
    var delen = [kop, info,
      segment(e.label, k.status, [["noodzakelijk", EC.statussen.noodzakelijk], ["meewegen", EC.statussen.meewegen], ["onbelangrijk", EC.statussen.onbelangrijk]],
        function (v) { k.status = v; teken(); })];
    if (k.status !== "onbelangrijk") {
      var id = "pv-niv-" + e.id;
      var sel = el("select", { id: id, onchange: function () { k.niveau = Number(this.value); teken(); } },
        e.niveaus.map(function (n, i) { return el("option", { value: String(i), selected: i === k.niveau, text: (i + 1) + ". " + n.label }); }));
      var n = haaltNiveau(j, k.niveau + 1);
      delen.push(el("div", { class: "pv-veld pv-niveau" }, [el("label", { for: id, text: k.status === "noodzakelijk" ? st.niveau_nood : st.niveau_mee }), sel]),
        el("p", { class: "pv-noot" }, [e.niveaus[k.niveau].uitleg,
          n == null ? "" : " " + fmt(k.status === "noodzakelijk" ? st.haalt_nood : st.haalt_mee, { n: getal(n, 0), totaal: getal(LITE.n, 0) })]));
    }
    return el("li", { class: "pv-eig pv-eig-" + k.status }, delen);
  }
  function schermEigenschappen() {
    var st = T.stappen.eigenschappen, tel = { noodzakelijk: 0, meewegen: 0, onbelangrijk: 0 };
    EIG.forEach(function (e) { tel[S.eigenschappen[e.id].status]++; });
    var delen = [el("p", { class: "pv-noot", text: fmt(st.telling, tel) })];
    Object.keys(EC.groepen).forEach(function (gid) {
      var lijst = EIG.filter(function (e) { return e.groep === gid; });
      if (!lijst.length) return;
      delen.push(el("fieldset", { class: "pv-groep" }, [el("legend", { text: EC.groepen[gid] }), el("ul", { class: "pv-eiglijst" }, lijst.map(eigenschapRij))]));
    });
    return el("div", {}, delen);
  }

  // ---------------------------------------------------------------- stap 6: gewichten
  function schermGewichten() {
    var st = T.stappen.gewichten;
    var mee = EIG.filter(function (e) { return S.eigenschappen[e.id].status === "meewegen"; });
    var nood = EIG.filter(function (e) { return S.eigenschappen[e.id].status === "noodzakelijk"; });
    var onb = EIG.filter(function (e) { return S.eigenschappen[e.id].status === "onbelangrijk"; });
    var delen = [];
    if (nood.length) delen.push(el("p", { class: "pv-noot" }, [el("strong", { text: fmt(st.vast_nood, { n: GW.noodzakelijk }) + " " }), nood.map(function (e) { return e.label; }).join(", ")]));
    if (onb.length) delen.push(el("p", { class: "pv-noot" }, [el("strong", { text: fmt(st.vast_onb, { n: GW.onbelangrijk }) + " " }), onb.map(function (e) { return e.label; }).join(", ")]));
    if (!mee.length) { delen.push(el("p", { class: "pv-uitleg", text: st.geen_meewegen })); return el("div", {}, delen); }
    delen.push(el("div", { class: "pv-schuifjes" }, mee.map(function (e) {
      var k = S.eigenschappen[e.id], id = "pv-g-" + e.id;
      var schuif = el("input", { type: "range", id: id, min: String(GW.min), max: String(GW.max), step: "1", value: String(k.gewicht) });
      var waarde = el("output", { for: id, class: "pv-gewicht", text: String(k.gewicht) });
      schuif.addEventListener("input", function () { waarde.textContent = schuif.value; });
      schuif.addEventListener("change", function () { k.gewicht = Number(schuif.value); bewaar(); });
      return el("div", { class: "pv-schuif" }, [el("div", { class: "pv-schuif-kop" }, [el("label", { for: id, text: e.label }), waarde]), schuif,
        el("p", { class: "pv-schuif-uitleg", text: fmt(st.niveau, { n: e.niveaus[k.niveau].label }) })]);
    })));
    delen.push(el("button", { type: "button", class: "pv-link", text: T.knoppen.startwaarden, onclick: function () {
      var s = startkeuzes(S.profiel || "gepensioneerde", S.verblijf);
      mee.forEach(function (e) { S.eigenschappen[e.id].gewicht = s[e.id].gewicht; }); teken();
    } }));
    return el("div", {}, delen);
  }

  var SCHERMEN = {
    profiel: schermProfiel, gebied: schermGebied, ligging: function () { return schermKeuzes("ligging"); },
    grootte: function () { return schermKeuzes("grootte"); }, eigenschappen: schermEigenschappen, gewichten: schermGewichten
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

  // ---------------------------------------------------------------- server
  function antwoorden(extra) {
    return Object.assign({ lang: window.PV.lang, profiel: S.profiel || "gepensioneerde", verblijf: S.verblijf, eigenschappen: S.eigenschappen,
      ligging: S.ligging, grootte: S.grootte,
      alleen_regios: S.gebied === "regios" ? S.alleen_regios : [], alleen_provs: S.gebied === "provincies" ? S.alleen_provs : [] }, extra || {});
  }
  function scoreVraag(extra) {
    return haal(window.PV.score, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(antwoorden(extra)) });
  }

  // ---------------------------------------------------------------- ranglijst
  function eigTabel(c) {                                  // uitklap: alle eigenschappen van één gemeente
    var U = T.uitslag;
    return el("div", { class: "pv-tabelrol" }, [el("table", { class: "pv-eigtabel" }, [
      el("thead", {}, [el("tr", {}, [el("th", { text: U.kol_eig }), el("th", { text: U.kol_waarde }), el("th", { text: U.kol_niveau }), el("th", { text: U.kol_score })])]),
      el("tbody", {}, c.eigenschappen.map(function (x) {
        var e = EIG[EIDX[x.id]], ok = x.gevraagd == null || x.behaald == null ? null : x.behaald >= x.gevraagd;
        var niv = x.behaald == null ? LEEG : fmt(U.niveau_van, { b: x.behaald, n: e.niveaus.length })
          + (x.gevraagd == null ? "" : ", " + fmt(ok ? U.niveau_ok : U.niveau_niet, { g: x.gevraagd }));
        return el("tr", { class: "pv-eig-" + x.status + (ok === false ? " pv-niet" : "") }, [
          el("th", { scope: "row" }, [e.label, el("br"), el("span", { class: "pv-noot", text: EC.statussen[x.status] })]),
          el("td", { text: waardeE(e, x.waarde) }), el("td", { text: niv }),
          el("td", { text: x.deel == null ? "" : String(x.deel) })]);
      }))])]);
  }
  function plaatsKaart(c, rang) {
    var U = T.uitslag, gekozen = S.vergelijk.indexOf(c.istat) >= 0;
    var info = [c.prov, c.regio, c.inwoners != null ? fmt(U.inwoners, { n: getal(c.inwoners, 0) }) : null,
      c.hoogte_m != null ? fmt(U.hoogte, { n: getal(c.hoogte_m, 0) }) : null].filter(Boolean).join(" · ");
    var punten = function (titel, ids, cls) {
      if (!ids.length) return null;
      var d = {}; c.eigenschappen.forEach(function (x) { d[x.id] = x.deel; });
      return el("div", { class: "pv-punten " + cls }, [el("h4", { text: titel }), el("ul", {}, ids.map(function (id) {
        return el("li", {}, [el("span", { text: eLabel(id) }), balk(d[id]), el("span", { class: "pv-getal", text: String(d[id]) })]);
      }))]);
    };
    return el("li", { class: "pv-plaats" }, [
      el("div", { class: "pv-plaats-kop" }, [
        rang ? el("span", { class: "pv-rang", text: String(rang) }) : null,
        el("div", { class: "pv-plaats-naam" }, [el("h3", {}, [el("a", { href: "#/gemeente/" + c.istat, text: c.naam })]), el("p", { text: info }),
          c.trend ? el("span", { class: "pv-badge-trend", title: C.trends.uitleg, text: U.trend }) : null,
          c.buiten_gebied ? el("span", { class: "pv-badge-buiten", text: U.buiten_gebied }) : null]),
        el("div", { class: "pv-score" }, [el("strong", { text: c.score == null ? LEEG : String(c.score) }), el("span", { text: "/100" })])]),
      el("div", { class: "pv-tests" }, [
        el("span", { class: "pv-chip" }, [U.winter + " ", el("strong", { text: c.winter == null ? LEEG : String(c.winter) })]),
        el("span", { class: "pv-chip" + (c.tachtig != null && c.tachtig < C.aandachtspunten.tachtig_waarschuwing.drempel ? " laag" : "") },
          [U.tachtig + " ", el("strong", { text: c.tachtig == null ? LEEG : String(c.tachtig) })])]),
      punten(U.sterk, c.sterk, "sterk"), punten(U.zwak, c.zwak, "zwak"),
      el("details", { class: "pv-uitklap" }, [el("summary", { text: U.alle_eigenschappen }), eigTabel(c)]),
      el("div", { class: "pv-knoprij" }, [
        el("a", { class: "pv-knop pv-knop-klein", href: "#/gemeente/" + c.istat, text: T.knoppen.bekijk }),
        rang ? vinkjeVergelijk(c) : null])
    ]);
    function vinkjeVergelijk(c) {
      var id = "pv-vg-" + c.istat;
      return el("div", { class: "pv-vinkje" }, [el("input", { type: "checkbox", id: id, checked: gekozen, onchange: function () {
        if (this.checked && S.vergelijk.length >= MAX_VERGELIJK) { alert(U.vergelijk_max); this.checked = false; return; }
        toggle(S.vergelijk, c.istat); bewaar(); tekenVergelijkBalk();
      } }), el("label", { for: id, text: T.knoppen.vergelijk })]);
    }
  }
  function tekenVergelijkBalk() {
    var oud = document.getElementById("pv-vergelijkbalk"); if (oud) oud.remove();
    if (!S.vergelijk.length) return;
    app.appendChild(el("nav", { class: "pv-nav", id: "pv-vergelijkbalk" }, [el("div", { class: "pv-nav-knoppen" }, [
      el("button", { type: "button", class: "pv-knop pv-knop-terug", text: T.knoppen.wis_vergelijk, onclick: function () { S.vergelijk = []; bewaar(); teken(); } }),
      el("a", { class: "pv-knop pv-knop-hoofd", href: "#/vergelijk", text: fmt(T.knoppen.vergelijk_n, { n: S.vergelijk.length }) })])]));
  }
  function overzicht() {
    var O = T.overzicht, p = S.profiel && profiel(S.profiel), tel = { noodzakelijk: 0, meewegen: 0, onbelangrijk: 0 };
    EIG.forEach(function (e) { tel[S.eigenschappen[e.id].status]++; });
    var opties = function (sl) { return S[sl].length ? S[sl].map(function (x) { return T.stappen[sl].opties[x][0]; }).join(", ") : O.alles; };
    var gebied = S.gebied === "regios" && S.alleen_regios.length ? S.alleen_regios.map(function (r) { return T.regios[r]; }).join(", ")
      : S.gebied === "provincies" && S.alleen_provs.length ? S.alleen_provs.join(", ") : O.overal;
    var rijen = [["profiel", p ? p.label : O.geen], ["gebied", gebied], ["ligging", opties("ligging")], ["grootte", opties("grootte")],
      ["eigenschappen", fmt(T.stappen.eigenschappen.telling, tel)],
      ["gewichten", EIG.filter(function (e) { return S.eigenschappen[e.id].status === "meewegen"; })
        .sort(function (a, b) { return S.eigenschappen[b.id].gewicht - S.eigenschappen[a.id].gewicht; }).slice(0, 3)
        .map(function (e) { return e.label + " " + S.eigenschappen[e.id].gewicht; }).join(", ") || O.geen]];
    return el("details", { class: "pv-overzicht" }, [el("summary", { text: O.titel }), el("ul", {}, rijen.map(function (r) {
      return el("li", {}, [el("span", {}, [el("strong", { text: T.stappen[r[0]].titel }), el("br"), r[1]]),
        el("button", { type: "button", class: "pv-link", text: T.knoppen.wijzigen, onclick: function () { NAAR_UITSLAG = true; S.stap = STAPPEN.indexOf(r[0]); teken(); } })]);
    }))]);
  }
  function weinigBlok(res) {                              // te weinig plaatsen: wat elke noodzakelijke eigenschap wegstreept
    var U = T.uitslag;
    return el("section", { class: "pv-blok pv-weinig" }, [el("h3", { text: fmt(U.weinig_titel, { n: res.aantal }) }),
      el("p", { class: "pv-uitleg", text: U.weinig_uitleg }),
      el("ul", { class: "pv-weinig-lijst" }, res.wegstrepen.map(function (x) {
        var k = S.eigenschappen[x.id], e = EIG[EIDX[x.id]];
        return el("li", {}, [el("span", {}, [el("strong", { text: e.label }), " (" + e.niveaus[k.niveau].label + ") " + fmt(U.weinig_extra, { n: getal(x.weg, 0), van: getal(x.van, 0) })]),
          el("span", { class: "pv-knoprij" }, [
            k.niveau > 0 ? el("button", { type: "button", class: "pv-link", text: U.soepeler, onclick: function () { k.niveau--; teken(); } }) : null,
            el("button", { type: "button", class: "pv-link", text: U.naar_mee, onclick: function () { k.status = "meewegen"; teken(); } })])]);
      }))]);
  }
  function deelLink() {
    var k = { p: S.profiel, v: S.verblijf, e: S.eigenschappen, l: S.ligging, g: S.grootte, z: S.gebied, r: S.alleen_regios, pr: S.alleen_provs };
    var t = btoa(unescape(encodeURIComponent(JSON.stringify(k)))).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
    return location.origin + location.pathname + "?keuzes=" + t;
  }
  function deelBlok() {
    var U = T.uitslag, link = deelLink();
    var inp = el("input", { type: "text", readonly: true, value: link, id: "pv-deellink" });
    return el("div", { class: "pv-deel" }, [el("label", { for: "pv-deellink", text: U.deel_label }), el("div", { class: "pv-deel-rij" }, [inp,
      el("button", { type: "button", class: "pv-knop pv-knop-klein", text: U.kopieer, onclick: function () {
        var knop = this;
        (navigator.clipboard ? navigator.clipboard.writeText(link) : Promise.reject()).then(function () { knop.textContent = U.gekopieerd; })
          .catch(function () { inp.select(); });
      } })])]);
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
      sectie.appendChild(el("details", { class: "pv-berekening" }, [el("summary", { text: U.berekening_titel }), el("p", { class: "pv-noot", text: U.berekening })]));
      if (res.wegstrepen && res.wegstrepen.length) sectie.appendChild(weinigBlok(res));
      sectie.appendChild(overzicht());
      sectie.appendChild(deelBlok());
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
    if (typeof v === "boolean") return v ? G.waarde_ja : G.waarde_nee;
    return fmt(T.waarden[kolom] || "{v}", { v: typeof v === "number" ? getal(v, kolom.indexOf("pct") >= 0 ? 2 : 1) : v });
  }
  function waardeRij(label, kolom, g) {
    return el("li", { class: "pv-waarde" }, [el("span", { class: "pv-waarde-label", text: label }), el("strong", { text: waardeTekst(kolom, g.w[kolom]) }), bronRegel(kolom, g)]);
  }
  function testKaart(titel, uitleg, score, delen, labels, waarschuwing) {
    return el("section", { class: "pv-testkaart" }, [el("h3", { text: titel }), el("p", { class: "pv-uitleg", text: uitleg }),
      el("p", { class: "pv-testscore" }, [el("strong", { text: score == null ? LEEG : String(score) }), " / 100"]),
      waarschuwing || null,
      el("ul", { class: "pv-delen" }, Object.keys(labels).map(function (x) {
        return el("li", {}, [el("span", { text: labels[x] }), balk(delen[x]), el("span", { class: "pv-getal", text: delen[x] == null ? T.gemeente.geen_data : String(delen[x]) })]);
      }))]);
  }
  function extraRegels(g) {                               // gidsprijs, vluchten, stations, klimaatschatting
    var G = T.gemeente, w = g.w, regels = [];
    var gp = (C.gids_prijzen || {}).regios || {}, v = gp[g.regio];
    if (v != null) regels.push(fmt(G.gidsprijs, { v: getal(v, 0), regio: g.regio, toelichting: ((C.gids_prijzen.toelichting || {})[g.regio]) || C.gids_prijzen.peildatum }));
    var lv = C.luchthavens, m = String(w.luchthaven_jaarrond_naam || "").match(/\(([A-Z]{3})\)$/), vb = m && lv && lv.verbindingen && lv.verbindingen[m[1]];
    if (vb) {
      var lijst = [];
      if (vb.nl.length) lijst.push(fmt(G.vluchten_nl, { l: vb.nl.join(", ") }));
      if (vb.be.length) lijst.push(fmt(G.vluchten_be, { l: vb.be.join(", ") }));
      regels.push(fmt(G.vluchten, { lh: w.luchthaven_jaarrond_naam.replace(/ \([A-Z]{3}\)$/, ""), lijst: lijst.join("; ") }));
    }
    if (w.station_hs_naam) regels.push(fmt(G.station_hs, { naam: w.station_hs_naam }));
    if (w.station_nacht_naam) regels.push(fmt(G.station_nacht, { naam: w.station_nacht_naam }));
    if (w.klimaat_geschat) regels.push(G.klimaat_geschat);
    return regels.length ? el("ul", { class: "pv-noot pv-extra" }, regels.map(function (r) { return el("li", { text: r }); })) : null;
  }
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
    var G = T.gemeente, U = T.uitslag;
    app.innerHTML = "";
    app.appendChild(kop());
    var sectie = el("section", { class: "pv-gemeente" }, [el("p", { text: U.bezig })]);
    app.appendChild(sectie);
    window.scrollTo(0, 0);
    Promise.all([haal(window.PV.gemeente + "?istat=" + encodeURIComponent(istat)), scoreVraag({ alternatieven_voor: istat, n: 1 }).catch(function () { return {}; })])
      .then(function (res) {
        var g = res[0], r = res[1], s = (r.gemeenten || {})[istat], alt = r.alternatieven || [], w = g.w;
        sectie.innerHTML = "";
        sectie.appendChild(el("a", { href: "#", class: "pv-link", text: "← " + T.knoppen.terug_lijst, onclick: function (e) { e.preventDefault(); history.back(); } }));
        sectie.appendChild(el("div", { class: "pv-plaats-kop" }, [
          el("div", { class: "pv-plaats-naam" }, [el("h2", { text: g.naam, tabindex: "-1", id: "pv-titel" }),
            el("p", { text: [g.prov, g.provincie, g.regio, fmt(U.inwoners, { n: getal(g.inwoners, 0) }), g.hoogte_m != null ? fmt(U.hoogte, { n: g.hoogte_m }) : null].filter(Boolean).join(" · ") }),
            g.trend ? el("p", {}, [el("span", { class: "pv-badge-trend", text: C.trends.label }), " " + C.trends.uitleg]) : null]),
          el("div", { class: "pv-score", title: G.totaal }, [el("strong", { text: !s || s.score == null ? LEEG : String(s.score) }), el("span", { text: "/100" })])]));
        if (s && !s.voldoet) sectie.appendChild(el("p", { class: "pv-let-op", text: G.voldoet_niet }));
        if (g.hoogte_m != null) sectie.appendChild(el("p", { class: "pv-noot", text: fmt(G.hoogte_uitleg, { n: getal(g.hoogte_m, 0) }) }));
        if (w.sciolto_mafia_jaar) {
          var sm = C.sciolti_mafia, smg = sm.gemeenten.filter(function (x) { return x.istat === g.istat; })[0] || {};
          sectie.appendChild(el("section", { class: "pv-blok pv-label-maffia" }, [el("h3", { text: G.maffia_titel }),
            el("p", {}, [el("strong", { text: fmt(G.maffia, { jaar: w.sciolto_mafia_jaar, org: smg.organisatie || "", keer: smg.keer > 1 ? fmt(G.maffia_keer, { n: smg.keer }) : "" }) }), " " + sm.tekst]),
            smg.bron ? el("p", { class: "pv-noot", text: fmt(G.maffia_bron, { bron: smg.bron }) }) : null]));
        }
        // alle eigenschappen met waarde, niveau en bron
        if (s) {
          sectie.appendChild(el("h3", { class: "pv-sub-kop", text: G.eigenschappen_titel }));
          sectie.appendChild(eigTabel(s));
          sectie.appendChild(el("p", { class: "pv-noot", text: G.bronnen_eig }));
          sectie.appendChild(el("ul", { class: "pv-waarden pv-bronnenlijst" }, EIG.map(function (e) {
            return el("li", { class: "pv-waarde" }, [el("span", { class: "pv-waarde-label", text: e.label }), bronRegel(e.kolom, g)]);
          })));
        }
        sectie.appendChild(extraRegels(g));
        // risicoblok (altijd)
        var zone = w.seismische_zone;
        var vulk = !w.vulkaan_deel ? (w.vulkaan_zone === "geel" ? G.vulkaan_geel : G.vulkaan_geen) : fmt(w.vulkaan_deel === "geheel" ? G.vulkaan_geheel : G.vulkaan_deels, { v: w.vulkaan });
        sectie.appendChild(el("section", { class: "pv-risico" }, [el("h3", { text: G.risico_titel }), el("p", { class: "pv-uitleg", text: G.risico_uitleg }),
          el("ul", { class: "pv-waarden" }, [
            el("li", { class: "pv-waarde" }, [el("span", { class: "pv-waarde-label", text: G.seismisch }),
              el("strong", { text: zone ? fmt(G.seismisch_zone, { z: w.seismische_zone_detail || zone, tekst: G.zones[zone] }) : G.geen_data }), bronRegel("seismische_zone", g)]),
            waardeRij(G.frane, "frane_pop_pct", g), waardeRij(G.alluvioni, "alluvioni_pop_pct", g), waardeRij(G.bosbrand, "bosbrand_pct", g),
            el("li", { class: "pv-waarde" }, [el("span", { class: "pv-waarde-label", text: G.vulkaan }), el("strong", { text: vulk }), bronRegel("vulkaan", g)])]),
          el("a", { href: "https://idrogeo.isprambiente.it/app/iffi?@=" + g.lat + "," + g.lon + ",14", target: "_blank", rel: "noopener", text: G.idrogeo_link })]));
        var tw = C.aandachtspunten.tachtig_waarschuwing;
        sectie.appendChild(el("div", { class: "pv-tests-groot" }, [
          testKaart(G.winter_titel, G.winter_uitleg, g.t.winter, g.t.winter_delen, G.winter_delen),
          testKaart(G.tachtig_titel, G.tachtig_uitleg, g.t.tachtig, g.t.tachtig_delen, G.tachtig_delen,
            g.t.tachtig != null && g.t.tachtig < tw.drempel ? el("p", { class: "pv-let-op" }, [el("strong", { text: tw.tekst }), " " + tw.onderbouwing]) : null)]));
        var r7 = w.regime7_status || "nee", rg = C.regime7;
        var r7delen = [el("h3", { text: G.regime7_titel }), el("p", {}, [el("strong", { text: G.regime7[r7] }), w.regime7_reden && r7 !== "te_verifieren" ? " (" + w.regime7_reden + ")" : ""])];
        if (r7 === "ja" && w.lea != null && w.lea < rg.waarschuwing_lea.drempel) r7delen.push(el("p", { class: "pv-let-op", text: rg.waarschuwing_lea.tekst }));
        r7delen.push(el("p", { class: "pv-noot", text: rg.disclaimer }), bronRegel("regime7_status", g));
        sectie.appendChild(el("section", { class: "pv-blok" }, r7delen));
        var nt = w.ntn_per_100, hw = C.aandachtspunten.herverkoop_waarschuwing;
        sectie.appendChild(el("section", { class: "pv-blok" }, [el("h3", { text: G.herverkoop_titel }),
          el("p", { text: nt == null ? G.herverkoop_geen : fmt(G.herverkoop, { n: getal(nt, 1) }) }),
          nt != null && nt < hw.drempel ? el("p", { class: "pv-let-op", text: hw.tekst }) : null]));
        sectie.appendChild(el("section", { class: "pv-blok" }, [el("h3", { text: G.checklist_titel }),
          el("ul", { class: "pv-checklist" }, C.aandachtspunten.checklist.map(function (p) { return el("li", { text: p }); })),
          el("p", { class: "pv-noot", text: fmt(G.niet_in_score, { lijst: C.aandachtspunten.niet_in_score.join(", ").toLowerCase() }) })]));
        sectie.appendChild(gidsBlok());
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
    scoreVraag({ gemeenten: S.vergelijk, n: 1 }).then(function (r) {
      var gs = S.vergelijk.map(function (id) { return (r.gemeenten || {})[id]; }).filter(Boolean);
      var rij = function (label, cellen, cls) { return el("tr", { class: cls || "" }, [el("th", { scope: "row", text: label })].concat(cellen.map(function (c) { return el("td", {}, [c]); }))); };
      var kop2 = el("tr", {}, [el("th", { text: V.criterium })].concat(gs.map(function (g) { return el("th", { scope: "col" }, [el("a", { href: "#/gemeente/" + g.istat, text: g.naam }), el("br"), g.prov]); })));
      var rijen = [rij(G.totaal, gs.map(function (g) { return el("strong", { text: g.score == null ? LEEG : String(g.score) }); }), "pv-totaalrij")];
      EIG.forEach(function (e, j) {
        rijen.push(rij(e.label, gs.map(function (g) {
          var x = g.eigenschappen[j], ok = x.gevraagd == null || x.behaald == null ? "" : x.behaald >= x.gevraagd ? " ✓" : " ✗";
          return waardeE(e, x.waarde) + ok;
        })));
      });
      rijen.push(rij(T.uitslag.winter, gs.map(function (g) { return g.winter == null ? LEEG : String(g.winter); })));
      rijen.push(rij(T.uitslag.tachtig, gs.map(function (g) { return g.tachtig == null ? LEEG : String(g.tachtig); })));
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
    var y = window.scrollY;
    app.innerHTML = "";
    inhoud.forEach(function (n) { app.appendChild(n); });
    if (S.stap !== vorigeStap) {
      vorigeStap = S.stap; window.scrollTo(0, 0);
      var t = document.getElementById("pv-titel"); if (t) t.focus({ preventScroll: true });
    } else {
      window.scrollTo(0, y);
      if (actief && document.getElementById(actief)) document.getElementById(actief).focus({ preventScroll: true });
    }
  }
  window.addEventListener("hashchange", function () { vorigeStap = null; teken(); });

  haal(window.PV.lite).then(function (d) { LITE = d; teken(); })
    .catch(function () { app.innerHTML = ""; app.appendChild(kop()); app.appendChild(el("p", { class: "pv-fout", text: T.meta.fout_laden })); });
  teken();
})();
