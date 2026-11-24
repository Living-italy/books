/* Plaatsvinder Italië – wizard. Vanilla JS, geen afhankelijkheden.
 * De teller filtert in de browser op comuni-lite.json; score.php berekent de top 25.
 * De filterregels hieronder zijn dezelfde als voldoet() in lib/model.php. */
(function () {
  "use strict";

  var T = window.PV.taal;
  var app = document.getElementById("app");
  var LITE = null;
  var OPSLAG = "plaatsvinder.v1";
  var STAPPEN = ["ligging", "grootte", "profiel", "top", "extra", "bereik", "budget", "regio"];
  var MINUTEN = [15, 30, 45, 60, 90, 120];
  var STRALEN = [10, 20, 30, 50, 75, 100];

  var leeg = function () {
    return {
      stap: -1, ligging: [], grootte: [], profiel: "", top: [], extra: [], scholen: [],
      max_luchthaven: null, max_station: null, werk: null,
      budget: { soort: "", max: null, m2: 80 }, regios_uit: [],
      gebied: "overal", alleen_regios: [], alleen_provs: []
    };
  };
  var S = leeg();
  try { var bewaard = JSON.parse(localStorage.getItem(OPSLAG) || "null"); if (bewaard) S = Object.assign(leeg(), bewaard); } catch (e) { /* geen opslag */ }
  function bewaar() { try { localStorage.setItem(OPSLAG, JSON.stringify(S)); } catch (e) { /* geen opslag */ } }

  // ---------------------------------------------------------------- hulp
  function el(tag, attrs, kids) {
    var n = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === "class") n.className = attrs[k];
      else if (k === "text") n.textContent = attrs[k];
      else if (k.slice(0, 2) === "on") n.addEventListener(k.slice(2), attrs[k]);
      else if (attrs[k] === true) n.setAttribute(k, "");
      else if (attrs[k] !== false && attrs[k] != null) n.setAttribute(k, attrs[k]);
    });
    (kids || []).forEach(function (k) { if (k != null) n.appendChild(typeof k === "string" ? document.createTextNode(k) : k); });
    return n;
  }
  function fmt(tpl, v) { return String(tpl).replace(/\{(\w+)\}/g, function (_, k) { return v[k] != null ? v[k] : ""; }); }
  function getal(n) { return Number(n).toLocaleString("nl-NL"); }
  function toggle(lijst, v) { var i = lijst.indexOf(v); if (i >= 0) lijst.splice(i, 1); else lijst.push(v); }
  function km(la1, lo1, la2, lo2) {
    var r = Math.PI / 180, a = Math.sin((la2 - la1) * r / 2), b = Math.sin((lo2 - lo1) * r / 2);
    var h = a * a + Math.cos(la1 * r) * Math.cos(la2 * r) * b * b;
    return 2 * 6371 * Math.asin(Math.min(1, Math.sqrt(h)));
  }

  // ---------------------------------------------------------------- filters (gelijk aan model.php)
  function telOver() {
    if (!LITE) return null;
    _rmp = null;
    var L = LITE, n = 0, masker = 0;
    S.ligging.forEach(function (k) { masker |= 1 << L.ligging.indexOf(k); });
    var gr = S.grootte.map(Number), uit = S.gebied === "alleen" ? [] : S.regios_uit.map(Number);
    var b = S.budget, metBudget = b.soort && b.max > 0 && b.m2 > 0;
    var prijzen = b.soort === "huren" ? L.huur : L.koop;
    var w = S.werk;
    var alleen = gebiedActief();
    for (var i = 0; i < L.n; i++) {
      if (masker && !(L.lig[i] & masker)) continue;
      if (gr.length && gr.indexOf(L.gr[i]) < 0) continue;
      if (S.max_luchthaven && L.lh[i] != null && L.lh[i] > S.max_luchthaven) continue;
      if (S.max_station && L.st[i] != null && L.st[i] > S.max_station) continue;
      if (w && w.km && km(L.lat[i] / 1000, L.lon[i] / 1000, w.lat, w.lon) > w.km) continue;
      if (metBudget && prijzen[i] != null && prijzen[i] * b.m2 > b.max) continue;
      if (uit.length && uit.indexOf(L.regio[i]) >= 0) continue;
      if (alleen && !binnenGebied(L.regio[i], L.provincies[L.pv[i]][0])) continue;
      n++;
    }
    return n;
  }

  // "Alleen in": plaats telt als haar provincie gekozen is, of als haar regio gekozen is en er
  // in die regio geen afzonderlijke provincies gekozen zijn (zelfde regel als binnen_gebied() in model.php)
  function gebiedActief() { return S.gebied === "alleen" && (S.alleen_regios.length > 0 || S.alleen_provs.length > 0); }
  function regiosMetProv() {
    var r = {};
    if (LITE) LITE.provincies.forEach(function (p) { if (S.alleen_provs.indexOf(p[0]) >= 0) r[p[2]] = true; });
    return r;
  }
  var _rmp = null;
  function binnenGebied(regio, prov) {
    if (S.alleen_provs.indexOf(prov) >= 0) return true;
    if (_rmp === null) _rmp = regiosMetProv();
    return S.alleen_regios.indexOf(regio) >= 0 && !_rmp[regio];
  }

  // ---------------------------------------------------------------- schermen
  function kaart(titel, uitleg, aan, klik, extra) {
    return el("button", { type: "button", class: "pv-kaart" + (aan ? " aan" : ""), "aria-pressed": aan ? "true" : "false", onclick: klik },
      [el("span", { class: "pv-kaart-titel", text: titel }), uitleg ? el("span", { class: "pv-kaart-uitleg", text: uitleg }) : null, extra || null]);
  }

  function schermKeuzes(sleutel, lijst, enkel) {
    var opties = T.stappen[sleutel].opties;
    return el("div", { class: "pv-raster" }, Object.keys(opties).map(function (k) {
      var aan = enkel ? S[lijst] === k : S[lijst].indexOf(k) >= 0;
      return kaart(opties[k][0], opties[k][1], aan, function () {
        if (enkel) S[lijst] = S[lijst] === k ? "" : k; else toggle(S[lijst], k);
        teken();
      });
    }));
  }

  function schermTop() {
    var P = T.prioriteiten;
    return el("div", { class: "pv-raster" }, Object.keys(P).map(function (k) {
      var pos = S.top.indexOf(k);
      var badge = pos >= 0 ? el("span", { class: "pv-badge", text: String(pos + 1) }) : null;
      return kaart(P[k][0], P[k][1], pos >= 0, function () {
        if (pos >= 0) S.top.splice(pos, 1);
        else if (S.top.length < 3) S.top.push(k);
        S.extra = S.extra.filter(function (x) { return S.top.indexOf(x) < 0; });
        teken();
      }, badge);
    }));
  }

  function schermExtra() {
    var P = T.prioriteiten, st = T.stappen.extra;
    var raster = el("div", { class: "pv-raster" }, Object.keys(P).filter(function (k) { return S.top.indexOf(k) < 0; }).map(function (k) {
      return kaart(P[k][0], P[k][1], S.extra.indexOf(k) >= 0, function () { toggle(S.extra, k); teken(); });
    }));
    var delen = [raster];
    if (S.top.indexOf("scholen") >= 0 || S.extra.indexOf("scholen") >= 0) {
      delen.push(el("h3", { class: "pv-sub-kop", text: st.scholen_titel }), el("p", { class: "pv-uitleg", text: st.scholen_uitleg }),
        el("div", { class: "pv-raster" }, Object.keys(st.scholen).map(function (k) {
          return kaart(st.scholen[k], null, S.scholen.indexOf(k) >= 0, function () { toggle(S.scholen, k); teken(); });
        })));
    }
    return el("div", {}, delen);
  }

  function keuzelijst(label, waarde, opties, veranderd) {
    var id = "pv-" + Math.random().toString(36).slice(2, 8);
    var sel = el("select", { id: id, onchange: function () { veranderd(this.value ? Number(this.value) : null); } },
      opties.map(function (o) { return el("option", { value: o[0] == null ? "" : String(o[0]), selected: o[0] === waarde, text: o[1] }); }));
    return el("div", { class: "pv-veld" }, [el("label", { for: id, text: label }), sel]);
  }

  function schermBereik() {
    var b = T.stappen.bereik;
    var min = [[null, b.geen_limiet]].concat(MINUTEN.map(function (m) { return [m, fmt(b.minuten, { n: m })]; }));
    var delen = [
      keuzelijst(b.luchthaven, S.max_luchthaven, min, function (v) { S.max_luchthaven = v; teken(); }),
      keuzelijst(b.station, S.max_station, min, function (v) { S.max_station = v; teken(); })
    ];
    // werkplek: zoeken via zoek.php
    var lijst = el("ul", { class: "pv-suggesties", role: "listbox" });
    var invoer = el("input", { type: "search", id: "pv-werk", autocomplete: "off", placeholder: b.werk_zoek,
      value: S.werk ? S.werk.naam + " (" + S.werk.prov + ")" : "" });
    var timer = null;
    invoer.addEventListener("input", function () {
      clearTimeout(timer);
      var q = invoer.value.trim();
      if (!q) { S.werk = null; lijst.innerHTML = ""; teken(true); return; }
      timer = setTimeout(function () {
        fetch(window.PV.zoek + "?q=" + encodeURIComponent(q)).then(function (r) { return r.json(); }).then(function (res) {
          lijst.innerHTML = "";
          if (!res.length) lijst.appendChild(el("li", { class: "pv-geen", text: b.werk_geen }));
          res.forEach(function (c) {
            lijst.appendChild(el("li", {}, [el("button", { type: "button", text: c.naam + " (" + c.prov + ")", onclick: function () {
              S.werk = { istat: c.istat, naam: c.naam, prov: c.prov, lat: c.lat, lon: c.lon, km: (S.werk && S.werk.km) || 30 };
              teken();
            } })]));
          });
        }).catch(function () { lijst.innerHTML = ""; });
      }, 200);
    });
    var werkDelen = [el("label", { for: "pv-werk", text: b.werk_plaats }), invoer, lijst];
    var blok = el("fieldset", { class: "pv-werk" }, [el("legend", { text: b.werk })].concat(werkDelen));
    if (S.werk) {
      blok.appendChild(keuzelijst(b.werk_straal, S.werk.km, STRALEN.map(function (k) { return [k, fmt(b.km, { n: k })]; }),
        function (v) { S.werk.km = v; teken(); }));
      blok.appendChild(el("button", { type: "button", class: "pv-link", text: T.knoppen.wissen, onclick: function () { S.werk = null; teken(); } }));
    }
    delen.push(blok);
    return el("div", {}, delen);
  }

  function schermBudget() {
    var b = T.stappen.budget, bu = S.budget;
    var soort = el("div", { class: "pv-segment", role: "radiogroup", "aria-label": b.soort }, [["", b.geen], ["huren", b.huren], ["kopen", b.kopen]].map(function (o) {
      return el("button", { type: "button", role: "radio", "aria-checked": bu.soort === o[0] ? "true" : "false", class: bu.soort === o[0] ? "aan" : "",
        text: o[1], onclick: function () { bu.soort = o[0]; teken(); } });
    }));
    var delen = [el("p", { class: "pv-label", text: b.soort }), soort];
    if (bu.soort) {
      var getalVeld = function (label, sleutel, stap) {
        var id = "pv-" + sleutel;
        var inp = el("input", { id: id, type: "number", inputmode: "numeric", min: "0", step: stap, value: bu[sleutel] != null ? bu[sleutel] : "" });
        inp.addEventListener("change", function () { bu[sleutel] = inp.value ? Number(inp.value) : null; teken(); });
        return el("div", { class: "pv-veld" }, [el("label", { for: id, text: label }), inp]);
      };
      delen.push(getalVeld(bu.soort === "kopen" ? b.max_koop : b.max_huur, "max", bu.soort === "kopen" ? "5000" : "50"));
      delen.push(getalVeld(b.m2, "m2", "5"));
      if (LITE && LITE.koop.every(function (v) { return v == null; })) delen.push(el("p", { class: "pv-let-op", text: b.geen_omi }));
    }
    return el("div", {}, delen);
  }

  function schermRegio() {
    var R = T.regios, st = T.stappen.regio;
    var modus = el("div", { class: "pv-segment", role: "radiogroup", "aria-label": st.titel }, [["overal", st.modus_overal], ["alleen", st.modus_alleen]].map(function (o) {
      return el("button", { type: "button", role: "radio", "aria-checked": S.gebied === o[0] ? "true" : "false", class: S.gebied === o[0] ? "aan" : "",
        text: o[1], onclick: function () { S.gebied = o[0]; teken(); } });
    }));
    var delen = [modus];
    if (S.gebied !== "alleen") {
      delen.push(el("p", { class: "pv-uitleg", text: st.overal_uitleg }));
      delen.push(el("div", { class: "pv-knoprij" }, [
        el("button", { type: "button", class: "pv-link", text: T.knoppen.alles_aan, onclick: function () { S.regios_uit = []; teken(); } }),
        el("button", { type: "button", class: "pv-link", text: T.knoppen.alles_uit, onclick: function () { S.regios_uit = Object.keys(R).map(Number); teken(); } })
      ]));
      delen.push(el("div", { class: "pv-raster pv-raster-klein" }, Object.keys(R).map(function (k) {
        var aan = S.regios_uit.indexOf(Number(k)) < 0;
        return kaart(R[k], null, aan, function () { toggle(S.regios_uit, Number(k)); teken(); });
      })));
      return el("div", {}, delen);
    }
    delen.push(el("p", { class: "pv-uitleg", text: S.alleen_regios.length ? st.alleen_uitleg : st.alleen_leeg }));
    delen.push(el("div", { class: "pv-raster pv-raster-klein" }, Object.keys(R).map(function (k) {
      var code = Number(k), aan = S.alleen_regios.indexOf(code) >= 0;
      return kaart(R[k], null, aan, function () {
        toggle(S.alleen_regios, code);
        if (!aan) return teken();
        // regio uit: haar provincies ook uit
        S.alleen_provs = S.alleen_provs.filter(function (p) {
          return !LITE.provincies.some(function (q) { return q[0] === p && q[2] === code; });
        });
        teken();
      });
    })));
    S.alleen_regios.forEach(function (code) {
      var provs = LITE ? LITE.provincies.filter(function (p) { return p[2] === code; }) : [];
      if (provs.length < 2) return;
      delen.push(el("h3", { class: "pv-sub-kop", text: fmt(st.provs_titel, { regio: R[code] }) }));
      delen.push(el("p", { class: "pv-uitleg", text: st.provs_uitleg }));
      delen.push(el("div", { class: "pv-raster pv-raster-klein" }, provs.map(function (p) {
        return kaart(p[1], null, S.alleen_provs.indexOf(p[0]) >= 0, function () { toggle(S.alleen_provs, p[0]); teken(); });
      })));
    });
    return el("div", {}, delen);
  }

  var SCHERMEN = {
    ligging: function () { return schermKeuzes("ligging", "ligging"); },
    grootte: function () { return schermKeuzes("grootte", "grootte"); },
    profiel: function () { return schermKeuzes("profiel", "profiel", true); },
    top: schermTop, extra: schermExtra, bereik: schermBereik, budget: schermBudget, regio: schermRegio
  };

  // ---------------------------------------------------------------- opbouw
  function teller() {
    var n = telOver();
    if (n == null) return el("p", { class: "pv-teller", text: T.meta.laden });
    var tekst = n === 0 ? T.teller.geen : n === 1 ? T.teller.een : fmt(T.teller.meer, { n: getal(n) });
    return el("p", { class: "pv-teller" + (n === 0 ? " nul" : ""), role: "status" }, [
      el("strong", { text: tekst }), n > 0 ? el("span", { text: " " + fmt(T.teller.van, { totaal: getal(LITE.n) }) }) : null
    ]);
  }

  function kop() {
    return el("header", { class: "pv-kop" }, [el("h1", { text: T.meta.titel })]);
  }

  function intro() {
    return [kop(), el("section", { class: "pv-intro" }, [
      el("p", { class: "pv-sub", text: T.meta.ondertitel }),
      el("button", { type: "button", class: "pv-knop pv-knop-hoofd", text: T.knoppen.start, onclick: function () { S.stap = 0; teken(); } })
    ])];
  }

  function stapScherm() {
    var sleutel = STAPPEN[S.stap], st = T.stappen[sleutel];
    var laatste = S.stap === STAPPEN.length - 1;
    var voortgang = el("div", { class: "pv-voortgang", role: "progressbar", "aria-valuemin": "1", "aria-valuemax": String(STAPPEN.length), "aria-valuenow": String(S.stap + 1) },
      [el("span", { style: "width:" + ((S.stap + 1) / STAPPEN.length * 100) + "%" })]);
    var magVerder = sleutel !== "top" || S.top.length > 0;
    var nav = el("nav", { class: "pv-nav" }, [
      teller(),
      el("div", { class: "pv-nav-knoppen" }, [
        el("button", { type: "button", class: "pv-knop pv-knop-terug", text: T.knoppen.vorige, onclick: function () { S.stap--; teken(); } }),
        el("button", { type: "button", class: "pv-knop pv-knop-hoofd", disabled: !magVerder, text: laatste ? T.knoppen.resultaat : T.knoppen.volgende,
          onclick: function () { if (laatste) { S.stap = STAPPEN.length; uitslag(); } else { S.stap++; teken(); } } })
      ])
    ]);
    return [kop(), el("section", { class: "pv-stap" }, [
      el("p", { class: "pv-stapnr", text: fmt(T.stap, { n: S.stap + 1, totaal: STAPPEN.length }) }), voortgang,
      el("h2", { text: st.titel, tabindex: "-1", id: "pv-titel" }), el("p", { class: "pv-uitleg", text: st.uitleg }),
      SCHERMEN[sleutel]()
    ]), nav];
  }

  var vorigeStap = null;
  function teken(behoudFocus) {
    bewaar();
    var actief = document.activeElement && document.activeElement.id;
    var inhoud = S.stap < 0 ? intro() : S.stap < STAPPEN.length ? stapScherm() : null;
    if (!inhoud) return;
    app.innerHTML = "";
    inhoud.forEach(function (n) { app.appendChild(n); });
    if (S.stap !== vorigeStap) {
      vorigeStap = S.stap;
      window.scrollTo(0, 0);
      var h = document.getElementById("pv-titel"); if (h) h.focus({ preventScroll: true });
    } else if (actief && document.getElementById(actief)) {
      var a = document.getElementById(actief); a.focus();
      if (behoudFocus && a.setSelectionRange && a.type === "search") a.setSelectionRange(a.value.length, a.value.length);
    }
  }

  // ---------------------------------------------------------------- uitslag
  function antwoorden() {
    var b = S.budget;
    return {
      lang: window.PV.lang, ligging: S.ligging, grootte: S.grootte, profiel: S.profiel || "anders",
      top: S.top, extra: S.extra, scholen: S.scholen,
      max_luchthaven: S.max_luchthaven, max_station: S.max_station,
      werk: S.werk ? { istat: S.werk.istat, km: S.werk.km } : null,
      budget: b.soort && b.max > 0 && b.m2 > 0 ? { soort: b.soort, max: b.max, m2: b.m2 } : null,
      regios_uit: S.gebied === "alleen" ? [] : S.regios_uit,
      alleen_regios: gebiedActief() ? S.alleen_regios : [],
      alleen_provs: gebiedActief() ? S.alleen_provs : []
    };
  }

  function uitslag() {
    bewaar();
    var U = T.uitslag;
    app.innerHTML = "";
    app.appendChild(kop());
    var sectie = el("section", { class: "pv-uitslag" }, [el("h2", { text: U.titel, tabindex: "-1", id: "pv-titel" }), el("p", { class: "pv-uitleg", text: U.bezig })]);
    app.appendChild(sectie);
    window.scrollTo(0, 0);
    fetch(window.PV.score, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(antwoorden()) })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (res) { toonUitslag(sectie, res); })
      .catch(function () { sectie.lastChild.textContent = U.fout; });
  }

  function woonlastRegel(wl) {
    var U = T.uitslag;
    if (!wl) return null;
    if (wl.prijs == null) return el("p", { class: "pv-woonlast" }, [U.woonlast_geen]);
    var m2p = wl.soort === "huren" ? String(wl.m2_prijs).replace(".", ",") : getal(wl.m2_prijs);
    var hoofd = fmt(wl.soort === "huren" ? U.woonlast_huren : U.woonlast_kopen, { prijs: getal(wl.prijs), m2: wl.m2, m2_prijs: m2p });
    var marge = wl.onder_pct >= 5 ? fmt(U.woonlast_onder, { pct: wl.onder_pct }) : U.woonlast_rond;
    var noot = U.woonlast_bron + (wl.provinciecijfer ? ", " + U.provinciecijfer : "");
    return el("p", { class: "pv-woonlast" }, [el("strong", { text: hoofd }), " · " + marge, el("br"), el("small", { text: noot })]);
  }

  function toonUitslag(sectie, res) {
    var U = T.uitslag, P = T.prioriteiten;
    sectie.innerHTML = "";
    sectie.appendChild(el("h2", { text: U.titel, tabindex: "-1", id: "pv-titel" }));
    sectie.appendChild(el("p", { class: "pv-uitleg", text: res.aantal ? fmt(U.aantal, { n: getal(res.aantal) }) + " " + U.uitleg : U.leeg }));
    var lijst = el("ol", { class: "pv-lijst" });
    res.top.forEach(function (c, i) {
      var punten = function (titel, items, cls) {
        if (!items.length) return null;
        return el("div", { class: "pv-punten " + cls }, [el("h4", { text: titel }), el("ul", {}, items.map(function (p) {
          return el("li", {}, [el("strong", { text: P[p.punt] ? P[p.punt][0] : p.punt }), el("span", { text: ": " + p.tekst })]);
        }))]);
      };
      var info = [c.prov, c.regio, c.inwoners != null ? fmt(U.inwoners, { n: getal(c.inwoners) }) : null,
        c.hoogte_m != null ? fmt(U.hoogte, { n: getal(c.hoogte_m) }) : null].filter(Boolean).join(" · ");
      var kaartLink = "https://www.openstreetmap.org/?mlat=" + c.lat + "&mlon=" + c.lon + "#map=13/" + c.lat + "/" + c.lon;
      lijst.appendChild(el("li", { class: "pv-plaats" }, [
        el("div", { class: "pv-plaats-kop" }, [
          el("span", { class: "pv-rang", text: String(i + 1) }),
          el("div", { class: "pv-plaats-naam" }, [el("h3", {}, [el("a", { href: kaartLink, target: "_blank", rel: "noopener", text: c.naam })]), el("p", { text: info })]),
          el("div", { class: "pv-score", "aria-label": U.score + " " + c.score }, [el("strong", { text: String(c.score) }), el("span", { text: "/100" })])
        ]),
        woonlastRegel(c.woonlast),
        punten(U.sterk, c.sterk, "sterk"), punten(U.zwak, c.zwak, "zwak"),
        c.geen_gegevens.length ? el("p", { class: "pv-geen-data", text: fmt(U.geen_data, { lijst: c.geen_gegevens.map(function (p) { return P[p] ? P[p][0].toLowerCase() : p; }).join(", ") }) }) : null
      ]));
    });
    sectie.appendChild(lijst);
    sectie.appendChild(el("div", { class: "pv-knoprij" }, [
      el("button", { type: "button", class: "pv-knop pv-knop-terug", text: T.knoppen.aanpassen, onclick: function () { S.stap = STAPPEN.length - 1; teken(); } }),
      el("button", { type: "button", class: "pv-knop", text: T.knoppen.opnieuw, onclick: function () { S = leeg(); S.stap = 0; teken(); } })
    ]));
    sectie.appendChild(el("p", { class: "pv-bronnen" }, [U.bronnen + " ", el("a", { href: U.osm_link, target: "_blank", rel: "noopener", text: U.osm })]));
    document.getElementById("pv-titel").focus({ preventScroll: true });
  }

  // ---------------------------------------------------------------- start
  fetch(window.PV.lite).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(function (d) { LITE = d; if (S.stap >= STAPPEN.length) uitslag(); else teken(); })
    .catch(function () { app.innerHTML = ""; app.appendChild(kop()); app.appendChild(el("p", { class: "pv-fout", text: T.meta.fout_laden })); });
  if (S.stap < STAPPEN.length) teken();
})();
