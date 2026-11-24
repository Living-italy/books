#!/usr/bin/env bash
# Bereidt het OSRM-wegennet voor uit italy-latest.osm.pbf (auto-profiel, MLD).
# Gebruik: pipeline/osrm_prepare.sh <pad naar pbf> <werkmap>
set -euo pipefail
PBF="$1"; DIR="$2"; IMAGE="${OSRM_IMAGE:-osrm/osrm-backend}"
mkdir -p "$DIR"
# Alleen wat de auto nodig heeft: wegen, veerverbindingen en afslagverboden. Scheelt veel geheugen.
osmium tags-filter -O "$PBF" \
  w/highway=motorway,motorway_link,trunk,trunk_link,primary,primary_link,secondary,secondary_link,tertiary,tertiary_link,unclassified,residential,living_street,service,road,track \
  w/route=ferry r/type=restriction -o "$DIR/italy-roads.osm.pbf"
docker run --rm -v "$DIR":/data "$IMAGE" osrm-extract -p /opt/car.lua /data/italy-roads.osm.pbf
docker run --rm -v "$DIR":/data "$IMAGE" osrm-partition /data/italy-roads.osrm
docker run --rm -v "$DIR":/data "$IMAGE" osrm-customize /data/italy-roads.osrm
date -u +%FT%TZ > "$DIR/KLAAR"
