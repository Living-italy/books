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
  var PER_KEER = 10;                                      // de ranglijst toont telkens 10 gemeenten meer
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
  // per eigenschap: naam met vakjes Noodzakelijk en Onbelangrijk (geen van beide = meewegen),
  // daaronder de uitleg en de niveaus als direct zichtbare keuzes (niet bij onbelangrijk)
  function eigenschapRij(e) {
    var st = T.stappen.eigenschappen, k = S.eigenschappen[e.id], j = EIDX[e.id];
    var vakje = function (status) {
      var id = "pv-st-" + status + "-" + e.id;
      return el("label", { class: "pv-eig-vakje", for: id }, [el("input", { type: "checkbox", id: id, checked: k.status === status,
        onchange: function () { k.status = this.checked ? status : "meewegen"; teken(); } }), EC.statussen[status]]);
    };
    var delen = [el("div", { class: "pv-eig-kop" }, [el("h4", { text: e.label }),
        el("div", { class: "pv-eig-vakjes" }, [vakje("noodzakelijk"), vakje("onbelangrijk")])]),
      el("p", { class: "pv-eig-uitleg", text: e.uitleg })];
    if (k.status !== "onbelangrijk") {
      var naam = "pv-niv-" + e.id;
      delen.push(el("fieldset", { class: "pv-niveaus" }, [el("legend", { class: "pv-verborgen", text: fmt(st.niveau_legend, { item: e.label }) })]
        .concat(e.niveaus.map(function (n, i) {
          var id = naam + "-" + i, aan = i === k.niveau;
          return el("label", { class: "pv-niv" + (aan ? " aan" : ""), for: id }, [
            el("input", { type: "radio", name: naam, id: id, value: String(i), checked: aan, onchange: function () { k.niveau = i; teken(); } }),
            el("span", {}, [el("span", { class: "pv-niv-label", text: n.label }), el("span", { class: "pv-niv-uitleg", text: n.uitleg })])]);
        }))));
      var n = haaltNiveau(j, k.niveau + 1);
      if (n != null) delen.push(el("p", { class: "pv-noot pv-niv-telling", text: fmt(k.status === "noodzakelijk" ? st.haalt_nood : st.haalt_mee, { n: getal(n, 0), totaal: getal(LITE.n, 0) }) }));
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
  function naarTestsUitleg(ev) {
    ev.preventDefault();
    var d = document.getElementById("pv-tests-uitleg");
    if (d) { d.open = true; d.scrollIntoView({ behavior: "smooth", block: "start" }); }
    else location.hash = "";
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
    return el("li", { class: "pv-plaats", id: rang ? "pv-plaats-" + c.istat : null }, [
      el("div", { class: "pv-plaats-kop" }, [
        rang ? el("span", { class: "pv-rang", text: String(rang) }) : null,
        el("div", { class: "pv-plaats-naam" }, [el("h3", {}, [el("a", { href: "#/gemeente/" + c.istat, text: c.naam })]), el("p", { text: info }),
          c.trend ? el("span", { class: "pv-badge-trend", title: C.trends.uitleg, text: U.trend }) : null,
          c.buiten_gebied ? el("span", { class: "pv-badge-buiten", text: U.buiten_gebied }) : null]),
        el("div", { class: "pv-score" }, [el("strong", { text: c.score == null ? LEEG : String(c.score) }), el("span", { text: "/100" })])]),
      el("div", { class: "pv-tests" }, [
        el("a", { class: "pv-chip", href: "#", title: T.tests.winter.titel, onclick: naarTestsUitleg }, [U.winter + " ", el("strong", { text: c.winter == null ? LEEG : String(c.winter) })]),
        el("a", { class: "pv-chip" + (c.tachtig != null && c.tachtig < C.aandachtspunten.tachtig_waarschuwing.drempel ? " laag" : ""), href: "#", title: T.tests.tachtig.titel, onclick: naarTestsUitleg },
          [U.tachtig + " ", el("strong", { text: c.tachtig == null ? LEEG : String(c.tachtig) })]),
        el("span", { class: "pv-noot pv-tests-hint", text: U.tests_hint })]),
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
  // kaart van Italië: elke gemeente gekleurd naar haar score (groen hoog, rood laag), grijs als ze afvalt
  var KAART = null, SVGNS = "http://www.w3.org/2000/svg";
  function kleur(s, min, max) {
    var t = max > min ? (s - min) / (max - min) : 1;
    return "hsl(" + Math.round(120 * t) + ",70%," + Math.round(42 + 8 * (1 - Math.abs(t - 0.5) * 2)) + "%)";
  }
  function svg(tag, attrs) { var n = document.createElementNS(SVGNS, tag); Object.keys(attrs).forEach(function (k) { n.setAttribute(k, attrs[k]); }); return n; }
  function kaartBlok(top) {
    var U = T.uitslag, blok = el("section", { class: "pv-kaartblok" });
    var knop = el("button", { type: "button", class: "pv-knop", text: U.kaart_toon, onclick: function () {
      if (blok.querySelector(".pv-kaart-svg")) { blok.innerHTML = ""; blok.appendChild(knop); knop.textContent = U.kaart_toon; return; }
      knop.disabled = true; knop.textContent = U.kaart_laden;
      Promise.all([KAART ? Promise.resolve(KAART) : haal(window.PV.kaart), scoreVraag({ kaart: true, n: 1 })]).then(function (r) {
        KAART = r[0]; knop.disabled = false; knop.textContent = U.kaart_verberg;
        tekenKaart(blok, r[1].kaart || [], top);
      }).catch(function () { knop.disabled = false; knop.textContent = U.fout; });
    } });
    blok.appendChild(knop);
    return blok;
  }
  function tekenKaart(blok, lijst, top) {
    var U = T.uitslag, K = KAART, scores = {}, positie = {}, waarden = lijst.map(function (x) { return x[1]; });
    lijst.forEach(function (x, i) { scores[x[0]] = x[1]; positie[x[0]] = i + 1; });
    var min = Math.min.apply(null, waarden), max = Math.max.apply(null, waarden);
    var rang = {}; top.forEach(function (c, i) { rang[c.istat] = i + 1; });
    var vb = [0, 0, K.breedte, K.hoogte];
    var kaart = svg("svg", { viewBox: vb.join(" "), class: "pv-kaart-svg", role: "img", "aria-label": U.kaart_label });
    var laag = svg("g", {}), topLaag = svg("g", {});
    Object.keys(K.gemeenten).forEach(function (ist) {
      var g = K.gemeenten[ist], s = scores[ist];
      var p = svg("path", { d: g[2], fill: s == null ? "var(--pv-kaart-uit)" : kleur(s, min, max), "data-i": ist });
      (rang[ist] ? topLaag : laag).appendChild(p);
      if (rang[ist]) p.setAttribute("class", "pv-kaart-top");
    });
    kaart.appendChild(laag);
    kaart.appendChild(svg("path", { d: K.regios, class: "pv-kaart-regio" }));
    kaart.appendChild(topLaag);
    var info = el("p", { class: "pv-kaart-info", role: "status", text: U.kaart_tik });
    kaart.addEventListener("click", function (e) {
      var ist = e.target.getAttribute && e.target.getAttribute("data-i");
      if (!ist) return;
      var g = K.gemeenten[ist], s = scores[ist];
      info.innerHTML = "";
      info.appendChild(el("strong", { text: g[0] + " (" + g[1] + ")" }));
      info.appendChild(document.createTextNode(" " + (s == null ? U.kaart_valt_af : fmt(U.kaart_score, { s: s }) + (positie[ist] ? " · " + fmt(U.kaart_rang, { n: getal(positie[ist], 0), van: getal(lijst.length, 0) }) : "")) + " "));
      info.appendChild(el("a", { href: "#/gemeente/" + ist, text: T.knoppen.bekijk }));
      // op een telefoon valt het infovak vaak buiten beeld: spring naar het resultaat
      var kaartje = rang[ist] && document.getElementById("pv-plaats-" + ist);
      var doel = kaartje || info;
      doel.scrollIntoView({ behavior: "smooth", block: kaartje ? "start" : "center" });
      if (kaartje) { kaartje.classList.remove("pv-flits"); void kaartje.offsetWidth; kaartje.classList.add("pv-flits"); }
    });
    // inzoomen en verschuiven
    var zet = function () { kaart.setAttribute("viewBox", vb.join(" ")); };
    var zoom = function (f, cx, cy) {
      var nb = Math.min(K.breedte, Math.max(K.breedte / 40, vb[2] * f)), nh = nb * K.hoogte / K.breedte;
      cx = cx == null ? vb[0] + vb[2] / 2 : cx; cy = cy == null ? vb[1] + vb[3] / 2 : cy;
      vb = [cx - nb / 2, cy - nh / 2, nb, nh]; zet();
    };
    var punt = function (e) { var r = kaart.getBoundingClientRect(); return [vb[0] + (e.clientX - r.left) / r.width * vb[2], vb[1] + (e.clientY - r.top) / r.height * vb[3]]; };
    kaart.addEventListener("wheel", function (e) { e.preventDefault(); var q = punt(e); zoom(e.deltaY < 0 ? 0.8 : 1.25, q[0], q[1]); }, { passive: false });
    var sleep = null;
    kaart.addEventListener("pointerdown", function (e) { sleep = { x: e.clientX, y: e.clientY, vb: vb.slice(), bewogen: false }; });
    kaart.addEventListener("pointermove", function (e) {
      if (!sleep) return;
      var r = kaart.getBoundingClientRect(), dx = (e.clientX - sleep.x) / r.width * vb[2], dy = (e.clientY - sleep.y) / r.height * vb[3];
      if (Math.abs(e.clientX - sleep.x) + Math.abs(e.clientY - sleep.y) > 4) sleep.bewogen = true;
      if (sleep.bewogen) { vb = [sleep.vb[0] - dx, sleep.vb[1] - dy, vb[2], vb[3]]; zet(); }
    });
    var los = function () { if (sleep && sleep.bewogen) kaart.addEventListener("click", function stop(ev) { ev.stopPropagation(); kaart.removeEventListener("click", stop, true); }, true); sleep = null; };
    kaart.addEventListener("pointerup", los); kaart.addEventListener("pointerleave", function () { sleep = null; });
    var knoppen = el("div", { class: "pv-kaart-knoppen" }, [
      el("button", { type: "button", class: "pv-knop pv-knop-klein", "aria-label": U.kaart_in, text: "+", onclick: function () { zoom(0.6); } }),
      el("button", { type: "button", class: "pv-knop pv-knop-klein", "aria-label": U.kaart_uit, text: "−", onclick: function () { zoom(1 / 0.6); } }),
      el("button", { type: "button", class: "pv-knop pv-knop-klein", text: U.kaart_heel, onclick: function () { vb = [0, 0, K.breedte, K.hoogte]; zet(); } })]);
    var legenda = el("div", { class: "pv-kaart-legenda" }, [
      el("span", { text: fmt(U.kaart_laag, { s: min }) }), el("span", { class: "pv-kaart-schaal", "aria-hidden": "true" }), el("span", { text: fmt(U.kaart_hoog, { s: max }) }),
      el("span", { class: "pv-kaart-grijs" }, [el("i", { "aria-hidden": "true" }), U.kaart_grijs]),
      el("span", { class: "pv-kaart-grijs" }, [el("i", { class: "top", "aria-hidden": "true" }), U.kaart_top])]);
    blok.appendChild(el("div", { class: "pv-kaart-kader" }, [knoppen, kaart]));
    blok.appendChild(legenda);
    blok.appendChild(info);
    blok.appendChild(el("p", { class: "pv-noot", text: fmt(U.kaart_uitleg, { n: getal(waarden.length, 0) }) + " " + K.bron + "." }));
  }
  // ---------------------------------------------------------------- pdf van de uitslag (jsPDF, op eigen server, pas geladen bij gebruik)
  function laadScript(src) {
    return new Promise(function (ok, nee) { var x = document.createElement("script"); x.src = src; x.onload = ok; x.onerror = nee; document.head.appendChild(x); });
  }
  function pdfTekst(t) {                                  // standaardlettertype van jsPDF kent alleen Windows-1252
    return String(t == null ? "" : t).replace(/[≥]/g, ">=").replace(/[≤]/g, "<=").replace(/[−]/g, "-").replace(/[✓]/g, "ja").replace(/[✗]/g, "nee")
      .replace(/[^\u0000-\u00ff\u20ac\u2018\u2019\u201c\u201d\u2013\u2014\u2022\u2026]/g, "?");
  }
  function maakPdf(res, knop) {
    var U = T.uitslag, P = T.pdf, oud = knop.textContent;
    knop.disabled = true; knop.textContent = P.bezig;
    var lib = "assets/lib/";
    (window.jspdf ? Promise.resolve() : laadScript(lib + "jspdf-2.5.1.umd.min.js").then(function () { return laadScript(lib + "jspdf-autotable-3.8.2.min.js"); }))
      .then(function () {
        var doc = new window.jspdf.jsPDF({ unit: "mm", format: "a4" }), y = 16, groen = [31, 111, 92];
        var tekst = function (t, grootte, stijl, kleur) {
          doc.setFont("helvetica", stijl || "normal"); doc.setFontSize(grootte || 10); doc.setTextColor.apply(doc, kleur || [31, 35, 40]);
          var regels = doc.splitTextToSize(pdfTekst(t), 182);
          if (y + regels.length * grootte * 0.42 > 285) { doc.addPage(); y = 16; }
          doc.text(regels, 14, y); y += regels.length * (grootte || 10) * 0.42 + 2;
        };
        var p = S.profiel && profiel(S.profiel), datum = new Date().toLocaleDateString("nl-NL", { day: "numeric", month: "long", year: "numeric" });
        tekst(fmt(P.titel, { n: res.top.length }), 18, "bold", groen);
        tekst(fmt(P.ondertitel, { datum: datum, n: getal(res.aantal, 0) }), 10, "normal", [91, 100, 112]);
        y += 2;
        tekst(P.keuzes, 12, "bold");
        var gebied = S.gebied === "regios" && S.alleen_regios.length ? S.alleen_regios.map(function (x) { return T.regios[x]; }).join(", ")
          : S.gebied === "provincies" && S.alleen_provs.length ? S.alleen_provs.join(", ") : T.overzicht.overal;
        var opties = function (sl, metUitleg) {
          return S[sl].length ? S[sl].map(function (x) { var o = T.stappen[sl].opties[x]; return metUitleg ? o[0] + " (" + o[1].toLowerCase() + ")" : o[0].toLowerCase(); }).join(", ") : P.geen_voorkeur;
        };
        var per = function (st) { return EIG.filter(function (e) { return S.eigenschappen[e.id].status === st; }); };
        tekst(fmt(P.profiel, { p: p ? p.label : "-" }) + "\n" + fmt(P.gebied, { g: gebied }) + "\n" + fmt(P.ligging, { l: opties("ligging") }) + "\n" + fmt(P.grootte, { g: opties("grootte", true) }), 10);
        var nood = per("noodzakelijk"), mee = per("meewegen"), onb = per("onbelangrijk");
        if (nood.length) tekst(P.noodzakelijk + " " + nood.map(function (e) { return e.label + " (" + e.niveaus[S.eigenschappen[e.id].niveau].label + ")"; }).join("; "), 10);
        if (mee.length) tekst(P.meewegen + " " + mee.map(function (e) { var k = S.eigenschappen[e.id]; return e.label + " (" + e.niveaus[k.niveau].label + ", " + fmt(P.gewicht, { n: k.gewicht }) + ")"; }).join("; "), 10);
        if (onb.length) tekst(P.onbelangrijk + " " + onb.map(function (e) { return e.label; }).join(", "), 10);
        tekst(P.berekening_titel, 10, "bold");
        tekst(U.berekening, 9, "normal", [91, 100, 112]);
        [T.tests.winter, T.tests.tachtig].forEach(function (x) {
          tekst(x.titel, 10, "bold");
          tekst(x.uitleg + " " + T.tests.onderdelen + " " + x.delen.join("; ") + ".", 9, "normal", [91, 100, 112]);
        });
        tekst(T.tests.slot, 9, "normal", [91, 100, 112]);
        y += 2;
        tekst(P.ranglijst, 12, "bold");
        doc.autoTable({ startY: y, head: [P.kol_rang.map(pdfTekst)], theme: "striped", headStyles: { fillColor: groen }, styles: { fontSize: 9, cellPadding: 1.6 },
          body: res.top.map(function (c, i) { return [i + 1, pdfTekst(c.naam), c.prov, pdfTekst(c.regio), c.score, c.winter == null ? "-" : c.winter, c.tachtig == null ? "-" : c.tachtig]; }),
          margin: { left: 14, right: 14 } });
        res.top.forEach(function (c, i) {
          doc.addPage(); y = 16;
          tekst((i + 1) + ". " + c.naam + " (" + c.prov + ", " + c.regio + ")", 14, "bold", groen);
          tekst(fmt(P.kop_gemeente, { s: c.score, inw: c.inwoners == null ? "-" : getal(c.inwoners, 0), h: c.hoogte_m == null ? "-" : getal(c.hoogte_m, 0),
            w: c.winter == null ? "-" : c.winter, t: c.tachtig == null ? "-" : c.tachtig }), 10, "normal", [91, 100, 112]);
          var rijen = c.eigenschappen.filter(function (x) { return x.status !== "onbelangrijk"; });
          doc.autoTable({ startY: y, head: [P.kol_eig.map(pdfTekst)], theme: "grid", headStyles: { fillColor: groen }, styles: { fontSize: 8.5, cellPadding: 1.5 },
            columnStyles: { 0: { cellWidth: 80 }, 2: { halign: "right", cellWidth: 20 } },
            body: rijen.map(function (x) {
              var e = EIG[EIDX[x.id]];
              return [pdfTekst(e.label + (x.status === "noodzakelijk" ? " *" : "")), pdfTekst(waardeE(e, x.waarde)), x.deel == null ? "-" : x.deel];
            }),
            didParseCell: function (d) {
              var x = d.section === "body" && rijen[d.row.index];
              if (x && x.gevraagd != null && x.behaald != null && x.behaald < x.gevraagd) d.cell.styles.textColor = [163, 84, 27];
            },
            margin: { left: 14, right: 14 } });
          y = doc.lastAutoTable.finalY + 4;
          tekst(P.legenda_tabel, 8, "normal", [91, 100, 112]);
          tekst(fmt(P.link, { url: location.origin + location.pathname + "#/gemeente/" + c.istat }), 8, "normal", [91, 100, 112]);
        });
        var n = doc.getNumberOfPages();
        for (var pg = 1; pg <= n; pg++) {
          doc.setPage(pg); doc.setFont("helvetica", "normal"); doc.setFontSize(7.5); doc.setTextColor(120, 120, 120);
          doc.text(doc.splitTextToSize(pdfTekst(P.voet), 160), 14, 289); doc.text(pg + " / " + n, 196, 289, { align: "right" });
        }
        doc.save("plaatsvinder-beste-" + res.top.length + "-" + new Date().toISOString().slice(0, 10) + ".pdf");
        knop.disabled = false; knop.textContent = oud;
      }).catch(function () { knop.disabled = false; knop.textContent = P.fout; });
  }
  // uitleg van de wintertest en de 80-jarentest
  function testsUitleg() {
    var X = T.tests, blok = function (x) {
      return el("div", { class: "pv-test-uitleg" }, [el("h4", { text: x.titel }), el("p", { text: x.uitleg }), el("p", { class: "pv-noot", text: X.onderdelen }),
        el("ul", {}, x.delen.map(function (d) { return el("li", { text: d }); }))]);
    };
    return el("details", { class: "pv-berekening pv-tests-uitleg", id: "pv-tests-uitleg" }, [el("summary", { text: X.titel }),
      blok(X.winter), blok(X.tachtig), el("p", { class: "pv-noot", text: X.slot })]);
  }
  function uitslag() {
    var U = T.uitslag;
    app.innerHTML = "";
    app.appendChild(kop());
    var sectie = el("section", { class: "pv-uitslag" }, [el("h2", { text: U.titel, tabindex: "-1", id: "pv-titel" }), el("p", { class: "pv-uitleg", text: U.bezig })]);
    app.appendChild(sectie);
    scoreVraag({ n: PER_KEER }).then(function (res) {
      sectie.innerHTML = "";
      sectie.appendChild(el("h2", { text: U.titel, tabindex: "-1", id: "pv-titel" }));
      sectie.appendChild(el("p", { class: "pv-uitleg", text: res.aantal ? fmt(U.aantal, { n: getal(res.aantal, 0) }) + " " + U.uitleg : U.leeg }));
      sectie.appendChild(el("details", { class: "pv-berekening" }, [el("summary", { text: U.berekening_titel }), el("p", { class: "pv-noot", text: U.berekening })]));
      sectie.appendChild(testsUitleg());
      if (res.wegstrepen && res.wegstrepen.length) sectie.appendChild(weinigBlok(res));
      sectie.appendChild(overzicht());
      sectie.appendChild(deelBlok());
      if (res.aantal) sectie.appendChild(el("div", { class: "pv-knoprij" }, [el("button", { type: "button", class: "pv-knop", text: T.pdf.knop,
        onclick: function () { maakPdf(res, this); } })]));
      if (res.aantal) sectie.appendChild(kaartBlok(res.top));
      var lijst = el("ol", { class: "pv-lijst" }, res.top.map(function (c, i) { return plaatsKaart(c, i + 1); }));
      sectie.appendChild(lijst);
      if (res.aantal > res.top.length) {
        var meer = el("button", { type: "button", class: "pv-knop pv-knop-meer" });
        var zetTekst = function () {
          var n = Math.min(PER_KEER, res.aantal - res.top.length);
          meer.textContent = fmt(n === 1 ? U.meer_een : U.meer, { n: n, van: getal(res.top.length + 1, 0), tot: getal(res.top.length + n, 0) });
        };
        zetTekst();
        meer.addEventListener("click", function () {
          meer.disabled = true;
          scoreVraag({ van: res.top.length, n: PER_KEER }).then(function (r) {
            var eerste = null;
            r.top.forEach(function (c) { res.top.push(c); var li = plaatsKaart(c, res.top.length); eerste = eerste || li; lijst.appendChild(li); });
            meer.disabled = false;
            if (res.top.length >= res.aantal || !r.top.length) meer.remove(); else zetTekst();
            if (eerste) { eerste.scrollIntoView({ behavior: "smooth", block: "start" }); var a = eerste.querySelector("h3 a"); if (a) a.focus({ preventScroll: true }); }
          }).catch(function () { meer.disabled = false; meer.textContent = U.fout; });
        });
        sectie.appendChild(el("div", { class: "pv-knoprij pv-meer-rij" }, [meer]));
      }
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
