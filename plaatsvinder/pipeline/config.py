"""Paden, bron-URL's en instellingen voor de Plaatsvinder-pipeline."""
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DATA = ROOT / "data"
RAW = DATA / "raw"
WORK = DATA / "work"
OUT_FULL = DATA / "comuni.json"
OUT_LITE = DATA / "comuni-lite.json"
OUT_CSV = DATA / "comuni.csv"          # zelfde tabel als csv, handig om te controleren

OMI_DIR = RAW / "omi"                   # handmatig geplaatst (login nodig), wordt niet gedownload
OSM_PBF = RAW / "osm" / "italy-latest.osm.pbf"

# Geofabrik is de bron uit PLAN.md. Vanuit sommige cloudomgevingen verbreekt Geofabrik
# de verbinding; dan valt de pipeline terug op de dagelijkse spiegel van OSM France.
OSM_URLS = [
    "https://download.geofabrik.de/europe/italy-latest.osm.pbf",
    "https://download.openstreetmap.fr/extracts/europe/italy-latest.osm.pbf",
]

# Elke bron: bestandsnaam in data/raw -> url
SOURCES = {
    "elenco.xlsx": "https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.xlsx",
    "limiti.zip": "https://www.istat.it/storage/cartografia/confini_amministrativi/generalizzati/2026/Limiti01012026_g.zip",
    "p2.zip": "https://demo.istat.it/data/p2/P2_2025_it_Comuni.zip",
    "altimetria.xlsx": "https://www.istat.it/wp-content/uploads/2026/02/Altimetria_Comuni-al-31_12_2021.xlsx",
    "strasa.zip": "https://demo.istat.it/data/strasa/STRASA_2026_it_Comuni.zip",
    "rifiuti.csv": "https://www.catasto-rifiuti.isprambiente.it/get/getDettaglioComunale.csv.php?&aa=2024",
    "scuole.csv": "https://dati.istruzione.it/opendata/opendata/catalogo/elements1/leaf/SCUANAGRAFESTAT20262720260901.csv",
    "irpef.zip": "https://www1.finanze.gov.it/finanze/analisi_stat/public/v_4_0_0/contenuti/Redditi_e_principali_variabili_IRPEF_su_base_comunale_CSV_2024.zip?d=1615465800",
    "eea/bwd.zip": "https://sdi.eea.europa.eu/datashare/s/mBEEM7LmkgwEFKR/download",
    "eea/pm25.zip": "https://sdi.eea.europa.eu/datashare/s/DdCEJKXn6zqgrpA/download",
    "agcom/comuni.csv": "https://geo.agcom.it/arcgis/sharing/rest/content/items/e78bb4e1d6c64123a241d522d845daab/data",
}
# Ziekenhuizen: 'Strutture di ricovero attive' (xlsx) van het Ministero della Salute handmatig in
# data/raw/salute/ zetten; salute.gov.it blokkeert geautomatiseerde downloads.
# Het apothekenbestand heeft de datum van vandaag in de naam; download.py zoekt de actuele link op.
FARMACIE_PAGE = "https://www.dati.salute.gov.it/it/dataset/farmacie"

# OSRM in Docker
OSRM_IMAGE = "osrm/osrm-backend"
OSRM_DIR = WORK / "osrm"
OSRM_PORT = 5005
OSRM_CANDIDATES = 5
MAX_RIJTIJD = 240           # langer telt als "meer dan 4 uur" (o.a. kleine eilanden met veerboot)          # per comune alleen de 5 hemelsbreed dichtstbijzijnde kandidaten

# Drempels voor ligging (stap 1, definities van Magic Towns)
KUST_MIN = 30
MEER_MIN = 30
SKI_MIN = 20
HEUVEL = (300, 600)
# Heuvelland op lage hoogte (zoals de Oltrepò Pavese): centrum minstens HEUVEL_LAAG_CENTRUM m
# en minstens HEUVEL_LAAG_VERSCHIL m hoogteverschil binnen de comune telt ook als heuvel.
HEUVEL_LAAG_CENTRUM = 150
HEUVEL_LAAG_VERSCHIL = 200

SCHOOL_RADIUS_KM = 5
WALK_RADIUS_M = 800

# Fusies die nog niet in alle bronnen verwerkt zijn: oude ISTAT-code -> huidige code.
# De Sardijnse hercodering (provincies 112-119) leest de pipeline zelf uit de ISTAT-lijst.
# Komt er een nieuwe fusie bij, dan meldt de pipeline welke codes niet te koppelen zijn.
FUSIES = {
    "024027": "024129",   # Castegnero  -> Castegnero Nanto (2026)
    "024071": "024129",   # Nanto       -> Castegnero Nanto (2026)
    "018082": "018094",   # Lirio       -> Montalto Pavese (31-01-2026)
}

# OSM-voorzieningen voor de maat 'voorzieningen in het centrum' (binnen 800 m)
OSM_AMENITY = ["restaurant", "cafe", "bar", "pub", "fast_food", "ice_cream", "pharmacy", "bank", "post_office",
               "library", "doctors", "dentist", "marketplace", "cinema", "theatre", "townhall"]
