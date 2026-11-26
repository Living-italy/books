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
