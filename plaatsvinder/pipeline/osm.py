"""Punten uit OpenStreetMap: plaatscentra, stations, luchthavens, skigebieden, ziekenhuizen, voorzieningen."""
import json
import subprocess

import pandas as pd
from shapely.geometry import shape

from config import OSM_PBF, WORK, OSM_AMENITY

POI_PBF = WORK / "osm-poi.osm.pbf"
POI_JSON = WORK / "osm-poi.geojsonseq"
POI_CSV = WORK / "osm-poi.csv"
NATUUR_GPKG = WORK / "osm-natuur.gpkg"


def extract(force: bool = False) -> pd.DataFrame:
    if POI_CSV.exists() and not force and POI_CSV.stat().st_mtime > OSM_PBF.stat().st_mtime:
        return pd.read_csv(POI_CSV, dtype={"id": str, "iata": str, "emergency": str, "ele": str, "sub": str}, keep_default_na=False, na_values=[""])
    WORK.mkdir(parents=True, exist_ok=True)
    filters = [
        "n/place=city,town,village",
        "nwr/railway=station,halt",
        "nwr/aeroway=aerodrome",
        "nwr/landuse=winter_sports",
        "nwr/amenity=hospital," + ",".join(OSM_AMENITY),
        "nwr/shop",
        "nwr/historic=castle,archaeological_site,ruins,fort,monastery,church,city_gate,tower,palace,manor,citywalls,aqueduct,abbey",
        "nwr/tourism=museum",
        "nwr/boundary=protected_area,national_park",
        "nwr/leisure=nature_reserve",
    ]
    print("  osmium tags-filter (voorzieningen)")
    subprocess.run(["osmium", "tags-filter", "-O", str(OSM_PBF), *filters, "-o", str(POI_PBF)], check=True)
    subprocess.run(["osmium", "export", "-O", "-f", "geojsonseq", "--geometry-types=point,polygon",
                    "-a", "type,id", str(POI_PBF), "-o", str(POI_JSON)], check=True)
    rows, natuur = [], []
    with open(POI_JSON, encoding="utf-8") as f:
        for line in f:
            line = line.lstrip("\x1e").strip()
            if not line:
                continue
            ft = json.loads(line)
            p = ft["properties"]
            cat = categorie(p)
            if is_natuur(p):
                if ft["geometry"]["type"] in ("Polygon", "MultiPolygon"):
                    natuur.append({"naam": p.get("name", ""), "geometry": shape(ft["geometry"])})
                if not cat:
                    continue
            if not cat:
                continue
            geom = shape(ft["geometry"])
            pt = geom if geom.geom_type == "Point" else geom.representative_point()
            area = 0.0 if geom.geom_type == "Point" else geom.area * 111_000 * 78_000 / 1e6  # ruwe km² op 45°N
            rows.append({
                "id": f'{p.get("@type", "")[:1]}{p.get("@id", "")}', "cat": cat,
                "naam": p.get("name", ""), "lat": round(pt.y, 6), "lon": round(pt.x, 6),
                "km2": round(area, 3), "iata": p.get("iata", ""), "emergency": p.get("emergency", ""),
                "place": p.get("place", ""), "ele": p.get("ele", ""),
                "sub": ("museum" if p.get("tourism") == "museum" else p.get("historic", "")) if cat == "cultuur" else
                       p.get("shop") if p.get("shop") in ("supermarket", "convenience", "greengrocer", "bakery") else p.get("amenity", ""),
            })
    d = pd.DataFrame(rows)
    d.to_csv(POI_CSV, index=False)
    import geopandas as gpd
    n = gpd.GeoDataFrame(natuur, geometry="geometry", crs=4326)
    n["geometry"] = n.geometry.buffer(0)
    n.to_file(NATUUR_GPKG, driver="GPKG")
    return d


SNELWEG_JSON = WORK / "osm-snelwegen.geojsonseq"


def snelwegen(force: bool = False):
    """Autosnelwegen (highway=motorway) als lijnen, voor de dealbreaker 'Lawaai van een snelweg'."""
    import geopandas as gpd
    from shapely.geometry import shape as _shape
    if force or not SNELWEG_JSON.exists() or SNELWEG_JSON.stat().st_mtime < OSM_PBF.stat().st_mtime:
        pbf = WORK / "osm-snelwegen.osm.pbf"
        subprocess.run(["osmium", "tags-filter", "-O", str(OSM_PBF), "w/highway=motorway", "-o", str(pbf)], check=True)
        subprocess.run(["osmium", "export", "-O", "-f", "geojsonseq", "--geometry-types=linestring", str(pbf),
                        "-o", str(SNELWEG_JSON)], check=True)
    geoms = []
    with open(SNELWEG_JSON, encoding="utf-8") as f:
        for line in f:
            line = line.lstrip("\x1e").strip()
            if line:
                geoms.append(_shape(json.loads(line)["geometry"]))
    return gpd.GeoDataFrame(geometry=geoms, crs=4326)


NATUUR_KLASSEN = {"1", "1a", "1b", "2", "3", "4", "6"}
NATUUR_TITELS = ("sito di importanza comunitaria", "zona speciale di conservazione", "zona di protezione speciale",
                 "natura 2000", "92/43", "riserva naturale", "parco nazionale")


def is_natuur(p: dict) -> bool:
    """Echte natuurbescherming; geen beschermd landschap (klasse 5) zoals regionale parken met steden erin."""
    if p.get("boundary") == "national_park" or p.get("leisure") == "nature_reserve":
        return True
    if p.get("boundary") != "protected_area":
        return False
    titel = (p.get("protection_title") or "").lower()
    return p.get("protect_class") in NATUUR_KLASSEN or any(t in titel for t in NATUUR_TITELS)


def categorie(p: dict) -> str:
    if p.get("place") in ("city", "town", "village") and "@type" in p and p["@type"] == "node":
        return "plaats"
    rw = p.get("railway")
    if rw in ("station", "halt"):
        if p.get("station") in ("subway", "light_rail", "monorail", "funicular", "tram") or \
           p.get("usage") == "tourism" or p.get("railway:preserved") == "yes" or p.get("tourism"):
            return ""
        return "station"
    if p.get("aeroway") == "aerodrome":
        if p.get("iata") and p.get("military") is None and p.get("aerodrome:type") != "military":
            return "luchthaven"
        return ""
    if p.get("landuse") == "winter_sports":
        return "ski"
    am = p.get("amenity")
    if am == "hospital":
        return "ziekenhuis"
    if am == "townhall":
        return "gemeentehuis"
    if am in OSM_AMENITY or p.get("shop"):
        return "voorziening"
    if p.get("tourism") == "museum" or p.get("historic"):
        return "cultuur"
    return ""
