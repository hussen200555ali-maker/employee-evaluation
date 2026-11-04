#!/usr/bin/env python3
"""Produces mPDF-safe Cairo builds.

mPDF 8.3.1 aborts on fonts that carry a GDEF MarkGlyphSets table
("This font contains MarkGlyphSets - Not tested yet").  Cairo ships
mark/mkmk GPOS lookups which trigger that path.  We keep the kerning
lookup and Arabic shaping (GSUB) intact and drop only the mark
attachment lookups, which mPDF cannot process anyway.
"""
from __future__ import annotations

import sys
from pathlib import Path

from fontTools.ttLib import TTFont


def sanitize(source: Path, target: Path) -> None:
    font = TTFont(str(source))

    if "GDEF" in font:
        gdef = font["GDEF"].table
        gdef.Version = 0x00010000
        gdef.MarkGlyphSetsDef = None

    # mPDF's OTL parser also mis-reads Cairo's GPOS lookups, so the table is
    # removed entirely.  Arabic shaping comes from GSUB; only Latin kerning
    # is lost, which does not affect the Arabic report layout.
    if "GPOS" in font:
        del font["GPOS"]

    font.save(str(target))


if __name__ == "__main__":
    root = Path(__file__).resolve().parent
    pairs = [
        ("Cairo-Regular.ttf", "Cairo-Regular-mpdf.ttf"),
        ("Cairo-Bold.ttf", "Cairo-Bold-mpdf.ttf"),
    ]
    for src_name, dst_name in pairs:
        source = root / src_name
        target = root / dst_name
        if not source.is_file():
            sys.exit(f"missing source font: {source}")
        sanitize(source, target)
        print(f"generated {target.name}")
