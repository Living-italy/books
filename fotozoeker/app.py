#!/usr/bin/env python3
"""Fotozoeker als app met een venster. Start via het icoon op je bureaublad."""

import json
import os
import queue
import subprocess
import sys
import threading
import tkinter as tk
from pathlib import Path
from tkinter import filedialog, messagebox, ttk

try:
    from PIL import ImageTk

    import fotozoeker as fz
except ImportError as e:
    # Zonder terminalvenster zou je anders niets zien.
    tk.Tk().withdraw()
    messagebox.showerror(
        "Fotozoeker",
        f"Niet alle onderdelen zijn geïnstalleerd ({e}).\n\n"
        "Dubbelklik nog een keer op het installatiebestand.",
    )
    sys.exit(1)

INSTELLINGEN = fz.MAP / "instellingen.json"
TEGEL = 200  # breedte van een miniatuur in pixels


def lees_instellingen():
    try:
        return json.loads(INSTELLINGEN.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return {"mappen": []}


def bewaar_instellingen(inst):
    INSTELLINGEN.parent.mkdir(parents=True, exist_ok=True)
    INSTELLINGEN.write_text(json.dumps(inst, indent=2, ensure_ascii=False), encoding="utf-8")


def open_bestand(pad):
    if sys.platform == "win32":
        os.startfile(pad)
    elif sys.platform == "darwin":
        subprocess.Popen(["open", pad])
    else:
        subprocess.Popen(["xdg-open", pad])


def toon_in_map(pad):
    if sys.platform == "win32":
        subprocess.Popen(["explorer", "/select,", pad])
    elif sys.platform == "darwin":
        subprocess.Popen(["open", "-R", pad])
    else:
        subprocess.Popen(["xdg-open", str(Path(pad).parent)])


class App:
    def __init__(self, root):
        self.root = root
        self.inst = lees_instellingen()
        self.berichten = queue.Queue()
        self.bezig = False
        self.stop_scannen = False
        self.resultaten = []
        self.tegels = []
        self.plaatjes = {}
        self.leeg = tk.PhotoImage(width=1, height=1)

        root.title("Fotozoeker")
        root.geometry("1100x760")
        root.minsize(700, 500)
        self.bouw_venster()
        self.toon_mappen()
        self.toon_uitleg()
        self.tel_index()
        root.after(100, self.verwerk_berichten)

    # ------------------------------------------------------------ opbouw

    def bouw_venster(self):
        groot = ("TkDefaultFont", 14)

        # Fotomappen
        boven = ttk.LabelFrame(self.root, text="Fotomappen", padding=8)
        boven.pack(fill="x", padx=12, pady=(12, 6))
        self.lijst = tk.Listbox(boven, height=3, activestyle="none")
        self.lijst.pack(side="left", fill="x", expand=True)
        knoppen = ttk.Frame(boven)
        knoppen.pack(side="left", padx=(8, 0))
        self.knop_toevoegen = ttk.Button(knoppen, text="Map toevoegen...", command=self.map_toevoegen)
        self.knop_toevoegen.pack(fill="x")
        self.knop_weg = ttk.Button(knoppen, text="Map weghalen", command=self.map_weghalen)
        self.knop_weg.pack(fill="x", pady=4)
        self.knop_scan = ttk.Button(knoppen, text="Foto's scannen", command=self.scannen)
        self.knop_scan.pack(fill="x")

        # Zoekbalk
        zoek = ttk.Frame(self.root, padding=(12, 6))
        zoek.pack(fill="x")
        ttk.Label(zoek, text="Zoek naar:", font=groot).pack(side="left")
        self.vraag = ttk.Entry(zoek, font=groot)
        self.vraag.pack(side="left", fill="x", expand=True, padx=8)
        self.vraag.bind("<Return>", lambda e: self.zoeken())
        ttk.Label(zoek, text="Liever niet:").pack(side="left")
        self.niet = ttk.Entry(zoek, width=16)
        self.niet.pack(side="left", padx=(4, 8))
        self.niet.bind("<Return>", lambda e: self.zoeken())
        ttk.Label(zoek, text="Aantal:").pack(side="left")
        self.aantal = ttk.Spinbox(zoek, from_=10, to=500, increment=10, width=5)
        self.aantal.set(40)
        self.aantal.pack(side="left", padx=(4, 8))
        self.knop_zoek = ttk.Button(zoek, text="Zoeken", command=self.zoeken)
        self.knop_zoek.pack(side="left")

        # Statusregel onderaan (eerst packen zodat hij altijd zichtbaar blijft)
        onder = ttk.Frame(self.root, padding=(12, 6))
        onder.pack(side="bottom", fill="x")
        self.status = ttk.Label(onder, text="")
        self.status.pack(side="left", fill="x", expand=True)
        self.balk = ttk.Progressbar(onder, length=220, mode="determinate")
        self.balk.pack(side="left", padx=8)
        self.knop_kopieer = ttk.Button(
            onder, text="Treffers kopiëren naar map...", command=self.kopieren, state="disabled"
        )
        self.knop_kopieer.pack(side="left")

        # Resultaten: een scrollbaar raster van miniaturen
        midden = ttk.Frame(self.root)
        midden.pack(fill="both", expand=True, padx=12)
        self.canvas = tk.Canvas(midden, highlightthickness=0, background="#f4f4f2")
        scroll = ttk.Scrollbar(midden, orient="vertical", command=self.canvas.yview)
        self.canvas.configure(yscrollcommand=scroll.set)
        scroll.pack(side="right", fill="y")
        self.canvas.pack(side="left", fill="both", expand=True)
        self.raster = tk.Frame(self.canvas, background="#f4f4f2")
        self.raster_id = self.canvas.create_window((0, 0), window=self.raster, anchor="nw")
        self.raster.bind(
            "<Configure>", lambda e: self.canvas.configure(scrollregion=self.canvas.bbox("all"))
        )
        self.canvas.bind("<Configure>", self.herschik)
        self.root.bind_all("<MouseWheel>", self.scroll_wiel)
        self.root.bind_all("<Button-4>", lambda e: self.canvas.yview_scroll(-3, "units"))
        self.root.bind_all("<Button-5>", lambda e: self.canvas.yview_scroll(3, "units"))

        self.vraag.focus_set()

    def scroll_wiel(self, e):
        stap = -e.delta if sys.platform == "darwin" else -e.delta // 120 * 3
        self.canvas.yview_scroll(stap, "units")

    def toon_mappen(self):
        self.lijst.delete(0, "end")
        for m in self.inst["mappen"]:
            self.lijst.insert("end", m)

    def toon_uitleg(self):
        self.wis_raster()
        tekst = (
            "Zo werkt het:\n\n"
            "1. Klik op 'Map toevoegen...' en kies de map(pen) met je foto's.\n"
            "2. Klik op 'Foto's scannen'. De eerste keer duurt dit even\n"
            "    (ongeveer een kwartier per 10.000 foto's). Daarna gaat het snel.\n"
            "3. Typ wat je zoekt, bijvoorbeeld 'mensen die aan tafel eten', en druk op Enter.\n\n"
            "Klik op een foto om hem te openen. Rechtsklik om hem in de map te tonen."
        )
        tk.Label(
            self.raster, text=tekst, justify="left", background="#f4f4f2",
            font=("TkDefaultFont", 13), padx=24, pady=24,
        ).grid(row=0, column=0, sticky="w")

    def zet_status(self, tekst):
        self.status.configure(text=tekst)

    def zet_bezig(self, bezig):
        self.bezig = bezig
        staat = "disabled" if bezig else "normal"
        for k in (self.knop_toevoegen, self.knop_weg, self.knop_zoek):
            k.configure(state=staat)
        # Tijdens het scannen wordt de scanknop een stopknop.
        self.knop_scan.configure(state="normal")
        if not bezig:
            self.knop_scan.configure(text="Foto's scannen", command=self.scannen)

    # ------------------------------------------------------------ acties

    def map_toevoegen(self):
        m = filedialog.askdirectory(title="Kies een fotomap", mustexist=True)
        if m and m not in self.inst["mappen"]:
            self.inst["mappen"].append(m)
            bewaar_instellingen(self.inst)
            self.toon_mappen()
            self.zet_status("Map toegevoegd. Klik op 'Foto's scannen' om de foto's te analyseren.")

    def map_weghalen(self):
        keuze = self.lijst.curselection()
        if not keuze:
            self.zet_status("Selecteer eerst een map in de lijst.")
            return
        del self.inst["mappen"][keuze[0]]
        bewaar_instellingen(self.inst)
        self.toon_mappen()

    def tel_index(self):
        def taak():
            n = fz.aantal_in_index(fz.open_db())
            self.berichten.put(("status", f"{n} foto's in de index." if n else
                                "Nog geen foto's gescand."))

        threading.Thread(target=taak, daemon=True).start()

    def scannen(self):
        if self.bezig:
            return
        if not self.inst["mappen"]:
            messagebox.showinfo("Fotozoeker", "Voeg eerst een fotomap toe.")
            return
        self.zet_bezig(True)
        self.stop_scannen = False
        self.knop_scan.configure(text="Stoppen", command=self.stoppen)
        self.balk.configure(value=0)
        mappen = list(self.inst["mappen"])

        def voortgang(klaar, totaal, tempo):
            rest = (totaal - klaar) / max(tempo, 1e-6) / 60
            self.berichten.put(("voortgang", klaar / totaal * 100))
            self.berichten.put(("status", f"Scannen: {klaar} van {totaal} "
                                          f"({tempo:.1f} per seconde, nog ongeveer {rest:.0f} min)"))

        def taak():
            try:
                db = fz.open_db()
                fz.opruimen(db)
                n = fz.indexeer(db, mappen, voortgang, self.melding,
                                stoppen=lambda: self.stop_scannen)
                totaal = fz.aantal_in_index(db)
                afloop = "Gestopt" if self.stop_scannen else "Klaar"
                self.berichten.put(("status", f"{afloop}. {n} foto's verwerkt, "
                                              f"{totaal} foto's in de index. Je kunt nu zoeken."))
            except Exception as e:
                self.berichten.put(("fout", e))
            self.berichten.put(("klaar", None))

        threading.Thread(target=taak, daemon=True).start()

    def stoppen(self):
        self.stop_scannen = True
        self.zet_status("Stoppen na deze stap...")

    def melding(self, tekst):
        self.berichten.put(("status", tekst))

    def zoeken(self):
        vraag = self.vraag.get().strip()
        if self.bezig or not vraag:
            return
        try:
            top = max(1, int(self.aantal.get()))
        except ValueError:
            top = 40
        niet = self.niet.get().strip() or None
        self.zet_bezig(True)
        self.zet_status("Zoeken...")

        def taak():
            try:
                db = fz.open_db()
                if not fz.aantal_in_index(db):
                    self.berichten.put(("status", "Nog geen foto's gescand. "
                                                  "Voeg een map toe en klik op 'Foto's scannen'."))
                else:
                    res = fz.zoek(db, vraag, top, niet, melding=self.melding)
                    self.berichten.put(("resultaten", res))
                    for i, (_, pad) in enumerate(res):
                        try:
                            self.berichten.put(("plaatje", (i, fz.open_foto(pad, TEGEL))))
                        except Exception:
                            pass
                    self.berichten.put(("status", f"{len(res)} treffers voor “{vraag}”. "
                                                  "Klik op een foto om hem te openen."))
            except Exception as e:
                self.berichten.put(("fout", e))
            self.berichten.put(("klaar", None))

        threading.Thread(target=taak, daemon=True).start()

    def kopieren(self):
        if not self.resultaten:
            return
        doel = filedialog.askdirectory(title="Kopieer de treffers naar deze map")
        if doel:
            fz.kopieer(self.resultaten, doel)
            self.zet_status(f"{len(self.resultaten)} foto's gekopieerd naar {doel}")
            open_bestand(doel)

    # ------------------------------------------------------------ resultaten

    def wis_raster(self):
        for w in self.raster.winfo_children():
            w.destroy()
        self.tegels = []
        self.plaatjes = {}
        self.canvas.yview_moveto(0)

    def toon_resultaten(self, res):
        self.wis_raster()
        self.resultaten = res
        self.knop_kopieer.configure(state="normal" if res else "disabled")
        if not res:
            tk.Label(self.raster, text="Niets gevonden.", background="#f4f4f2",
                     padx=24, pady=24).grid(row=0, column=0)
            return
        for score, pad in res:
            tegel = tk.Frame(self.raster, background="white", padx=6, pady=6, cursor="hand2")
            beeld = tk.Label(tegel, image=self.leeg, background="#e4e4e0",
                             width=TEGEL, height=TEGEL * 3 // 4)
            beeld.pack()
            naam = Path(pad).name
            if len(naam) > 28:
                naam = naam[:25] + "..."
            tk.Label(tegel, text=f"{naam}\n{score:.3f}", background="white",
                     font=("TkDefaultFont", 9), justify="center").pack(fill="x")
            for w in (tegel, beeld) + tuple(tegel.winfo_children()):
                w.bind("<Button-1>", lambda e, p=pad: open_bestand(p))
                w.bind("<Button-3>", lambda e, p=pad: toon_in_map(p))
                w.bind("<Button-2>", lambda e, p=pad: toon_in_map(p))  # Mac rechtsklik
            self.tegels.append((tegel, beeld))
        self.herschik()

    def zet_plaatje(self, i, img):
        if i >= len(self.tegels):
            return
        img.thumbnail((TEGEL, TEGEL * 3 // 4))
        foto = ImageTk.PhotoImage(img)
        self.plaatjes[i] = foto  # referentie bewaren, anders verdwijnt het plaatje
        self.tegels[i][1].configure(image=foto)

    def herschik(self, event=None):
        breedte = self.canvas.winfo_width()
        self.canvas.itemconfigure(self.raster_id, width=breedte)
        if not self.tegels:
            return
        kolommen = max(1, breedte // (TEGEL + 24))
        for n, (tegel, _) in enumerate(self.tegels):
            tegel.grid(row=n // kolommen, column=n % kolommen, padx=6, pady=6, sticky="n")

    # ------------------------------------------------------------ berichten

    def verwerk_berichten(self):
        try:
            for _ in range(50):
                soort, inhoud = self.berichten.get_nowait()
                if soort == "status":
                    self.zet_status(inhoud)
                elif soort == "voortgang":
                    self.balk.configure(value=inhoud)
                elif soort == "resultaten":
                    self.toon_resultaten(inhoud)
                elif soort == "plaatje":
                    self.zet_plaatje(*inhoud)
                elif soort == "fout":
                    self.zet_status("Er ging iets mis.")
                    messagebox.showerror("Fotozoeker", f"Er ging iets mis:\n\n{inhoud}")
                elif soort == "klaar":
                    self.zet_bezig(False)
        except queue.Empty:
            pass
        self.root.after(50, self.verwerk_berichten)


def main():
    root = tk.Tk()
    try:
        ttk.Style().theme_use("clam" if sys.platform.startswith("linux") else None)
    except tk.TclError:
        pass
    icoon = Path(__file__).with_name("icoon.png")
    if icoon.exists():
        root.iconphoto(True, tk.PhotoImage(file=str(icoon)))
    App(root)
    root.mainloop()


if __name__ == "__main__":
    main()
