"""ERA5-Land (Copernicus CDS): hete dagen, vorstdagen en neerslagtrend per comune.

Download vraagt een CDS-account: zet de API-sleutel in ~/.cdsapirc (url: https://cds.climate.copernicus.eu/api,
key: <sleutel>) en accepteer de licentie van beide datasets op de CDS-site.

  python pipeline/era5.py download   # aanvragen na elkaar, ongeveer 10 minuten per jaar (enkele uren)
"""
import sys
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

import numpy as np
import pandas as pd

from config import RAW

MAP = RAW / "era5"
GEBIED = [47.1, 6.6, 35.5, 18.6]                   # noord, west, zuid, oost
RECENT = list(range(2016, 2026))                   # 10 complete jaren
VROEG = list(range(1991, 2001))
NEERSLAG_JAREN = list(range(1991, 2026))
HEET = 32.0                                        # °C, daggrootste temperatuur
VORST = 0.0


def _aanvragen():
    for jaar in sorted(set(RECENT + VROEG)):
        for stat, naam in [("daily_maximum", "tmax"), ("daily_minimum", "tmin")]:
            if naam == "tmin" and jaar not in RECENT:
                continue                            # vorstdagen alleen voor de recente periode
            yield (MAP / f"{naam}_{jaar}.nc", "derived-era5-land-daily-statistics", {
                "variable": ["2m_temperature"], "year": str(jaar),
                "month": [f"{m:02d}" for m in range(1, 13)], "day": [f"{d:02d}" for d in range(1, 32)],
                "daily_statistic": stat, "time_zone": "utc+01:00", "frequency": "1_hourly", "area": GEBIED})
    yield (MAP / "neerslag_maand.nc", "reanalysis-era5-land-monthly-means", {
        "product_type": ["monthly_averaged_reanalysis"], "variable": ["total_precipitation"],
        "year": [str(j) for j in NEERSLAG_JAREN], "month": [f"{m:02d}" for m in range(1, 13)],
        "time": ["00:00"], "area": GEBIED, "data_format": "netcdf", "download_format": "unarchived"})


MAAND_KLIMAAT = MAP / "maand_klimaat.nc"


def _aanvragen_maand():
    yield (MAAND_KLIMAAT, "reanalysis-era5-land-monthly-means", {
        "product_type": ["monthly_averaged_reanalysis"],
        "variable": ["2m_temperature", "surface_solar_radiation_downwards"],
        "year": [str(j) for j in RECENT], "month": [f"{m:02d}" for m in range(1, 13)],
        "time": ["00:00"], "area": GEBIED, "data_format": "netcdf", "download_format": "unarchived"})


def download(parallel: int = 1) -> None:
    """De CDS staat maar een beperkt aantal aanvragen per dataset tegelijk toe: standaard na elkaar."""
    import cdsapi
    MAP.mkdir(parents=True, exist_ok=True)
    taken = [a for a in list(_aanvragen_maand()) + list(_aanvragen()) if not a[0].exists()]
    print(f"ERA5: {len(taken)} aanvragen")

    def een(taak):
        doel, dataset, verzoek = taak
        c = cdsapi.Client(quiet=True, retry_max=20, sleep_max=60)
        tmp = doel.with_suffix(".part")
        c.retrieve(dataset, verzoek, str(tmp))
        tmp.rename(doel)
        print(f"  klaar: {doel.name}", flush=True)

    with ThreadPoolExecutor(parallel) as ex:
        for f in [ex.submit(een, t) for t in taken]:
            try:
                f.result()
            except Exception as e:      # noqa: BLE001
                print(f"  ! mislukt: {e}", flush=True)


def beschikbaar() -> bool:
    """Minstens één deel van de klimaatdata is binnen; per_comune rekent wat er is."""
    return any(p.exists() for p, _, _ in list(_aanvragen()) + list(_aanvragen_maand()))


def _open(pad: Path):
    import xarray as xr
    ds = xr.open_dataset(pad)
    var = list(ds.data_vars)[0]
    da = ds[var]
    tijd = "valid_time" if "valid_time" in da.dims else "time"
    return da.rename({tijd: "tijd"}) if tijd != "tijd" else da


def _punten(da, lat, lon):
    """Waarde van de dichtstbijzijnde rastercel met data (land) voor elk punt."""
    import xarray as xr
    la = xr.DataArray(lat, dims="p")
    lo = xr.DataArray(lon, dims="p")
    return da.sel(latitude=la, longitude=lo, method="nearest")


def per_comune(t: pd.DataFrame) -> pd.DataFrame:
    """Klimaatindicatoren op het middelpunt van elke comune; onderdelen zonder bestand blijven leeg."""
    import xarray as xr
    lat, lon = t["lat"].values, t["lon"].values
    out = pd.DataFrame({"istat": t["istat"].values})
    leeg = np.full(len(t), np.nan)

    def dagen(naam, jaren, cond):
        paden = [MAP / f"{naam}_{j}.nc" for j in jaren]
        if not all(p.exists() for p in paden):
            return None
        tellingen = []
        for p in paden:
            v = _punten(_open(p) - 273.15, lat, lon)
            tellingen.append(cond(v).sum("tijd").where(v.notnull().any("tijd")).values.astype(float))
        return np.nanmean(np.vstack(tellingen), axis=0)

    heet_recent = dagen("tmax", RECENT, lambda v: v > HEET)
    heet_vroeg = dagen("tmax", VROEG, lambda v: v > HEET)
    vorst = dagen("tmin", RECENT, lambda v: v < VORST)
    out["dagen_boven_32"] = np.round(heet_recent, 1) if heet_recent is not None else leeg
    out["vorstdagen"] = np.round(vorst, 1) if vorst is not None else leeg
    out["trend_hete_dagen"] = np.round(heet_recent - heet_vroeg, 1) if heet_recent is not None and heet_vroeg is not None else leeg

    if (MAP / "neerslag_maand.nc").exists():
        p = _open(MAP / "neerslag_maand.nc")                   # m per dag, maandgemiddelde
        jaar = (_punten(p, lat, lon) * p["tijd"].dt.days_in_month * 1000).groupby("tijd.year").sum("tijd")
        recent, vroeg = jaar.sel(year=RECENT).mean("year").values, jaar.sel(year=VROEG).mean("year").values
        out["neerslag_mm"] = np.round(recent, 0)
        out["neerslag_trend_pct"] = np.round((recent - vroeg) / vroeg * 100, 1)
    else:
        out["neerslag_mm"] = leeg
        out["neerslag_trend_pct"] = leeg

    if MAAND_KLIMAAT.exists():
        ds = xr.open_dataset(MAAND_KLIMAAT)
        tijd = "valid_time" if "valid_time" in ds.dims else "time"
        ds = ds.rename({tijd: "tijd"})
        tm = _punten(ds["t2m"] - 273.15, lat, lon)
        maand = tm.groupby("tijd.month").mean("tijd")
        out["temp_jan"] = np.round(maand.sel(month=1).values, 1)
        out["temp_jul"] = np.round(maand.sel(month=7).values, 1)
        zon = _punten(ds["ssrd"], lat, lon).mean("tijd") / 3.6e6       # J/m² per dag -> kWh/m² per dag
        out["zon_kwh"] = np.round(zon.values, 2)
    else:
        for c in ["temp_jan", "temp_jul", "zon_kwh"]:
            out[c] = leeg
    return out


if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] == "download":
        download()
