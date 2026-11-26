"""Kaart van Italië met alle gemeentegrenzen (ISTAT, grenzen 1 januari 2026, gegeneraliseerd).

  python pipeline/kaart.py     # schrijft kaarten/italie-gemeenten.png en .pdf
"""
import geopandas as gpd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt

from config import RAW, ROOT

L = RAW / "x" / "limiti"
UIT = ROOT / "kaarten"


def maak() -> None:
    com = gpd.read_file(L / "Com01012026_g/Com01012026_g_WGS84.shp")
    prov = gpd.read_file(L / "ProvCM01012026_g/ProvCM01012026_g_WGS84.shp")
    reg = gpd.read_file(L / "Reg01012026_g/Reg01012026_g_WGS84.shp")
    UIT.mkdir(exist_ok=True)
    fig, ax = plt.subplots(figsize=(16, 19))
    com.plot(ax=ax, facecolor="#f4f1ea", edgecolor="#9a9a9a", linewidth=0.08)
    prov.boundary.plot(ax=ax, color="#5b6470", linewidth=0.35)
    reg.boundary.plot(ax=ax, color="#1f2328", linewidth=0.8)
    ax.set_axis_off()
    ax.set_title(f"Italië: alle {len(com):,} gemeenten".replace(",", "."), fontsize=22, loc="left", pad=12)
    fig.text(0.06, 0.03, "Dunne lijnen: gemeenten. Middel: provincies en grootstedelijke steden. Dik: regio's.\n"
             "Bron: ISTAT, Confini delle unità amministrative a fini statistici, 1 januari 2026 (gegeneraliseerd), CC BY 4.0.",
             fontsize=11, color="#5b6470")
    fig.savefig(UIT / "italie-gemeenten.png", dpi=300, bbox_inches="tight", facecolor="white")
    fig.savefig(UIT / "italie-gemeenten.pdf", bbox_inches="tight", facecolor="white")
    print(len(com), "gemeenten,", len(prov), "provincies,", len(reg), "regio's")


if __name__ == "__main__":
    maak()
    web_kaart()


def _pad(geom, x0, y1, schaal) -> str:
    """SVG-pad met relatieve stappen in hele eenheden (1 eenheid = 1/schaal km)."""
    delen = []
    polys = [geom] if geom.geom_type == "Polygon" else list(geom.geoms)
    for p in polys:
        for ring in [p.exterior]:                      # gaten (enclaves) zijn eigen gemeenten
            pts = [(round((x - x0) / 1000 * schaal), round((y1 - y) / 1000 * schaal)) for x, y in ring.coords]
            uniek = [pts[0]] + [q for a, q in zip(pts, pts[1:]) if q != a]
            if len(uniek) < 4:
                continue
            stappen = [f"M{uniek[0][0]} {uniek[0][1]}"]
            stappen += [f"{b[0] - a[0]} {b[1] - a[1]}" for a, b in zip(uniek, uniek[1:])]
            delen.append(stappen[0] + "l" + " ".join(stappen[1:]) + "z")
    return "".join(delen)


def web_kaart(tolerantie_m: float = 350, schaal: int = 2) -> None:
    """web/data/kaart.json: vereenvoudigde gemeentegrenzen als SVG-paden voor de kaart bij de uitslag."""
    import json
    from config import FUSIES
    com = gpd.read_file(L / "Com01012026_g/Com01012026_g_WGS84.shp")[["PRO_COM_T", "geometry"]]
    com["istat"] = com["PRO_COM_T"].map(lambda c: FUSIES.get(c, c))
    com = com.dissolve("istat").reset_index()
    reg = gpd.read_file(L / "Reg01012026_g/Reg01012026_g_WGS84.shp")
    x0, y0, x1, y1 = com.total_bounds
    com["geometry"] = com.geometry.simplify(tolerantie_m, preserve_topology=True)
    reg["geometry"] = reg.geometry.simplify(tolerantie_m * 2, preserve_topology=True)
    namen = {c["istat"]: [c["naam"], c["prov"]] for c in json.loads((ROOT / "web/data/score.json").read_text(encoding="utf-8"))["comuni"]}
    paden = {r.istat: namen.get(r.istat, ["", ""]) + [_pad(r.geometry, x0, y1, schaal)] for r in com.itertuples()}
    uit = {"bron": "ISTAT, grenzen 1 januari 2026 (gegeneraliseerd), CC BY 4.0",
           "breedte": round((x1 - x0) / 1000 * schaal), "hoogte": round((y1 - y0) / 1000 * schaal),
           "gemeenten": paden,  # istat: [naam, provincie, pad]
           "regios": "".join(_pad(g, x0, y1, schaal) for g in reg.geometry)}
    p = ROOT / "web" / "data" / "kaart.json"
    p.write_text(json.dumps(uit, separators=(",", ":")), encoding="utf-8")
    print(f"  kaart.json: {p.stat().st_size / 1e6:.2f} MB, {len(paden)} gemeenten")
