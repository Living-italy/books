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


def download(parallel: int = 1) -> None:
    """De CDS staat maar een beperkt aantal aanvragen per dataset tegelijk toe: standaard na elkaar."""
    import cdsapi
    MAP.mkdir(parents=True, exist_ok=True)
    taken = [a for a in _aanvragen() if not a[0].exists()]
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
    return all(p.exists() for p, _, _ in _aanvragen())


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
    """Klimaatindicatoren op het middelpunt van elke comune."""
    lat, lon = t["lat"].values, t["lon"].values

    def dagen(naam, jaren, cond):
        tellingen = []
        for j in jaren:
            da = _open(MAP / f"{naam}_{j}.nc") - 273.15
            v = _punten(da, lat, lon)
            tellingen.append(cond(v).sum("tijd").values.astype(float))
        return np.nanmean(np.vstack(tellingen), axis=0)

    heet_recent = dagen("tmax", RECENT, lambda v: v > HEET)
    heet_vroeg = dagen("tmax", VROEG, lambda v: v > HEET)
    vorst = dagen("tmin", RECENT, lambda v: v < VORST)

    p = _open(MAP / "neerslag_maand.nc")                       # m per dag, maandgemiddelde
    dagen_in_maand = p["tijd"].dt.days_in_month
    jaar = (_punten(p, lat, lon) * dagen_in_maand * 1000).groupby("tijd.year").sum("tijd")
    recent = jaar.sel(year=RECENT).mean("year").values
    vroeg = jaar.sel(year=VROEG).mean("year").values
    out = pd.DataFrame({
        "istat": t["istat"].values,
        "dagen_boven_32": np.round(heet_recent, 1),
        "vorstdagen": np.round(vorst, 1),
        "trend_hete_dagen": np.round(heet_recent - heet_vroeg, 1),
        "neerslag_mm": np.round(recent, 0),
        "neerslag_trend_pct": np.round((recent - vroeg) / vroeg * 100, 1),
    })
    # cellen zonder landdata (zee) geven NaN; die blijven leeg
    return out


if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] == "download":
        download()
