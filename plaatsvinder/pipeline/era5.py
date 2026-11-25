"""Klimaat per comune uit Copernicus (CDS).

Twee bronnen, elk één aanvraag:
  - Europese klimaatindicatoren (sis-ecde-climate-indicators, ERA5, raster 0,25°): per jaar het aantal
    hete dagen (daggrootste temperatuur boven 30 °C) en vorstdagen (dagminimum onder 0 °C), 1940-2025.
  - ERA5-Land maandgemiddelden (raster 0,1°): temperatuur, inkomende zonnestraling en neerslag, 1991-2025.
    Temperatuur gecorrigeerd voor het hoogteverschil tussen rastervak en centrum (0,65 °C per 100 m).

De CDS-sleutel staat in ~/.cdsapirc (url: https://cds.climate.copernicus.eu/api, key: <sleutel>).

  python pipeline/era5.py download
"""
import sys
import zipfile
from pathlib import Path

import numpy as np
import pandas as pd

from config import RAW

MAP = RAW / "era5"
ECDE = RAW / "ecde"
GEBIED = [47.1, 6.6, 35.5, 18.6]                   # noord, west, zuid, oost
RECENT = list(range(2016, 2026))                   # 10 complete jaren
VROEG = list(range(1991, 2001))
HEET = 30                                          # °C, drempel van de indicator "hot days"

MAAND = MAP / "maand_alles.nc"
MAX_HOOGTEVERSCHIL = 150                           # m; daarboven worden hete dagen en vorstdagen geschat (zie _herschat)
HOOGTE = MAP / "hoogte.nc"                         # ERA5-Land geopotentiaal (vaste velden), voor de hoogtecorrectie
VERVAL = 0.0065                                    # °C per meter (0,65 °C per 100 m)
INDICATOREN = {                                    # naam: (variabele, extra parameter)
    "hete_dagen": ("hot_days", "30_c"),
    "vorstdagen": ("frost_days", None),
}


def _ecde_bestand(naam: str) -> Path | None:
    nc = sorted((ECDE / naam).glob("*.nc"))
    return nc[0] if nc else None


def download() -> None:
    import cdsapi
    c = cdsapi.Client(quiet=True, retry_max=20, sleep_max=60)
    ECDE.mkdir(parents=True, exist_ok=True)
    MAP.mkdir(parents=True, exist_ok=True)
    for naam, (var, par) in INDICATOREN.items():
        if _ecde_bestand(naam):
            continue
        verzoek = {"variable": [var], "origin": "reanalysis", "temporal_aggregation": ["yearly"],
                   "spatial_aggregation": "gridded", "version": ["v2_0"]}
        if par:
            verzoek["other_parameters"] = [par]
        z = ECDE / f"{naam}.zip"
        c.retrieve("sis-ecde-climate-indicators", verzoek, str(z))
        with zipfile.ZipFile(z) as zf:
            zf.extractall(ECDE / naam)
        print(f"  klaar: {naam}", flush=True)
    if not MAAND.exists():
        tmp = MAAND.with_suffix(".part")
        c.retrieve("reanalysis-era5-land-monthly-means", {
            "product_type": ["monthly_averaged_reanalysis"],
            "variable": ["2m_temperature", "surface_solar_radiation_downwards", "total_precipitation"],
            "year": [str(j) for j in range(VROEG[0], RECENT[-1] + 1)], "month": [f"{m:02d}" for m in range(1, 13)],
            "time": ["00:00"], "area": GEBIED, "data_format": "netcdf", "download_format": "unarchived"}, str(tmp))
        tmp.rename(MAAND)
        print(f"  klaar: {MAAND.name}", flush=True)
    if not HOOGTE.exists():
        c.retrieve("reanalysis-era5-land-monthly-means", {
            "product_type": ["monthly_averaged_reanalysis"], "variable": ["geopotential"], "year": ["2020"],
            "month": ["01"], "time": ["00:00"], "data_format": "netcdf", "download_format": "unarchived"}, str(HOOGTE))


def beschikbaar() -> bool:
    """Minstens één deel van de klimaatdata is binnen; per_comune rekent wat er is."""
    return MAAND.exists() or any(_ecde_bestand(n) for n in INDICATOREN)


def _punten(da, lat, lon, la="latitude", lo="longitude"):
    """Waarde van de dichtstbijzijnde rastercel voor elk punt."""
    import xarray as xr
    return da.sel({la: xr.DataArray(lat, dims="p"), lo: xr.DataArray(lon, dims="p")}, method="nearest")


def per_comune(t: pd.DataFrame) -> pd.DataFrame:
    """Klimaatindicatoren op het middelpunt van elke comune; onderdelen zonder bestand blijven leeg."""
    import xarray as xr
    lat, lon = t["lat"].values, t["lon"].values
    out = pd.DataFrame({"istat": t["istat"].values})
    leeg = np.full(len(t), np.nan)

    def jaargemiddelde(naam, jaren):
        f = _ecde_bestand(naam)
        if f is None:
            return None
        ds = xr.open_dataset(f)
        da = ds[INDICATOREN[naam][0]]
        da = da.sel(time=da["time"].dt.year.isin(jaren)).mean("time")
        return _punten(da, lat, lon, "lat", "lon").values.astype("float64")

    heet_recent, heet_vroeg = jaargemiddelde("hete_dagen", RECENT), jaargemiddelde("hete_dagen", VROEG)
    vorst = jaargemiddelde("vorstdagen", RECENT)
    out["dagen_boven_30"] = np.round(heet_recent, 1) if heet_recent is not None else leeg
    out["vorstdagen"] = np.round(vorst, 1) if vorst is not None else leeg
    out["trend_hete_dagen"] = np.round(heet_recent - heet_vroeg, 1) if heet_recent is not None else leeg

    if MAAND.exists():
        from scipy.spatial import cKDTree
        ds = xr.open_dataset(MAAND)
        tijd = "valid_time" if "valid_time" in ds.dims else "time"
        ds = ds.rename({tijd: "tijd"})
        # ERA5-Land heeft alleen landcellen: neem voor elk middelpunt de dichtstbijzijnde cel met data
        geldig = ds["t2m"].isel(tijd=0).notnull()
        la2, lo2 = np.meshgrid(ds.latitude.values, ds.longitude.values, indexing="ij")
        boom = cKDTree(np.c_[la2[geldig.values], lo2[geldig.values]])
        _, idx = boom.query(np.c_[lat, lon])
        plat, plon = la2[geldig.values][idx], lo2[geldig.values][idx]

        def pt(da):
            return _punten(da, plat, plon).astype("float64")
        jaar = ds["tijd"].dt.year
        rec, vro = ds.sel(tijd=jaar.isin(RECENT)), ds.sel(tijd=jaar.isin(VROEG))
        # hoogtecorrectie: het rastervak middelt dal en berg; reken om naar de hoogte van het centrum
        corr = np.zeros(len(t))
        if HOOGTE.exists() and "hoogte_m" in t:
            z = xr.open_dataset(HOOGTE)["z"].isel(time=0) / 9.80665
            celhoogte = _punten(z, plat, plon).values
            corr = np.nan_to_num((celhoogte - t["hoogte_m"].values.astype(float)) * VERVAL)
        maand = pt(rec["t2m"] - 273.15).groupby("tijd.month").mean("tijd")
        jul_vroeg = pt(vro["t2m"] - 273.15).groupby("tijd.month").mean("tijd").sel(month=7).values + corr
        out["temp_jan"] = np.round(maand.sel(month=1).values + corr, 1)
        out["temp_jul"] = np.round(maand.sel(month=7).values + corr, 1)
        # maandgemiddelden zijn per dag: J/m² per dag -> kWh/m² per dag; neerslag m per dag -> mm per jaar
        out["zon_kwh"] = np.round((pt(rec["ssrd"]).mean("tijd") / 3.6e6).values, 2)

        def neerslag(d):
            p = pt(d["tp"]) * d["tijd"].dt.days_in_month * 1000
            return p.groupby("tijd.year").sum("tijd", min_count=1).mean("year").values
        nr, nv = neerslag(rec), neerslag(vro)
        out["neerslag_mm"] = np.round(nr, 0)
        out["neerslag_trend_pct"] = np.round((nr - nv) / nv * 100, 1)
    else:
        for c in ["temp_jan", "temp_jul", "zon_kwh", "neerslag_mm", "neerslag_trend_pct"]:
            out[c] = leeg
    if MAAND.exists() and HOOGTE.exists() and heet_recent is not None and vorst is not None and "hoogte_m" in t:
        out = _herschat(out, t, lat, lon, jul_vroeg)
    return out


def _monotoon(x, y, x_nieuw, stijgend=True):
    """Mediaan per klasse van 0,5 °C, monotoon gemaakt, dan lineair geïnterpoleerd."""
    k = np.round(x * 2) / 2
    df = pd.DataFrame({"k": k, "y": y}).dropna().groupby("k")["y"].median()
    kx, ky = df.index.values, df.values
    ky = np.maximum.accumulate(ky) if stijgend else np.minimum.accumulate(ky)
    return np.clip(np.interp(x_nieuw, kx, ky), 0, None)


def _herschat(out, t, lat, lon, jul_vroeg):
    """Hete dagen en vorstdagen komen van een raster van 25 km. In dalen en op bergen wijkt de hoogte van die
    cel sterk af van het centrum (Aosta, Bolzano). Daar schatten we ze uit de hoogtegecorrigeerde temperatuur
    van juli en januari (0,1°), met het verband dat geldt voor gemeenten waar de hoogte wel klopt."""
    import xarray as xr
    # hoogte van de cel van 0,25° = gemiddelde van de fijne ERA5-Land-hoogte (0,1°) binnen die cel
    z = xr.open_dataset(HOOGTE)["z"].isel(time=0) / 9.80665
    z = z.sel(latitude=slice(48, 35), longitude=slice(6, 19))
    cel = xr.open_dataset(_ecde_bestand("hete_dagen"))
    clat = cel["lat"].sel(lat=xr.DataArray(lat, dims="p"), method="nearest").values
    clon = cel["lon"].sel(lon=xr.DataArray(lon, dims="p"), method="nearest").values
    zl, zo, zv = z.latitude.values, z.longitude.values, z.values
    celhoogte = np.array([np.nanmean(zv[np.ix_(np.abs(zl - a) <= 0.125, np.abs(zo - o) <= 0.125)]) for a, o in zip(clat, clon)])
    verschil = np.abs(celhoogte - t["hoogte_m"].values.astype(float))
    goed = verschil <= MAX_HOOGTEVERSCHIL
    afwijkend = ~goed & np.isfinite(verschil)
    tj, tl = out["temp_jan"].values, out["temp_jul"].values
    heet = _monotoon(tl[goed], out["dagen_boven_30"].values[goed], tl)
    vorst = _monotoon(tj[goed], out["vorstdagen"].values[goed], tj, stijgend=False)
    heet_vroeg = _monotoon(tl[goed], out["dagen_boven_30"].values[goed], jul_vroeg)
    out.loc[afwijkend, "dagen_boven_30"] = np.round(heet[afwijkend], 1)
    out.loc[afwijkend, "vorstdagen"] = np.round(vorst[afwijkend], 1)
    out.loc[afwijkend, "trend_hete_dagen"] = np.round(heet[afwijkend] - heet_vroeg[afwijkend], 1)
    out["klimaat_geschat"] = afwijkend
    print(f"  hete dagen en vorstdagen geschat uit de temperatuur voor {afwijkend.sum()} gemeenten "
          f"(hoogte van de rastercel wijkt meer dan {MAX_HOOGTEVERSCHIL} m af)")
    return out


if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] == "download":
        download()
