// Renders the SDF glyph PBFs MapLibre needs for the map's ICAO labels and cluster counts.
// CARTO's glyph server only carries Open Sans / Roboto / Noto, so Work Sans has to be ours.
//
// One-off — the output is committed under public/fonts/. Only re-run when the typeface
// changes. fontnik is native and deliberately not a devDependency, so it cannot break CI:
//
//   npm install --no-save fontnik
//   node scripts/build-glyphs.mjs
import fontnik from 'fontnik';
import fs from 'node:fs';
import path from 'node:path';

// ICAO codes and cluster counts are ASCII, so one range covers the whole requirement.
const RANGES = [[0, 255]];
const FACES = [['resources/fonts/WorkSans-Regular.ttf', 'Work Sans Regular']];

for (const [ttf, fontstack] of FACES) {
    const font = fs.readFileSync(ttf);
    const outDir = path.join('public/fonts', fontstack);
    fs.mkdirSync(outDir, { recursive: true });

    for (const [start, end] of RANGES) {
        const pbf = await new Promise((resolve, reject) => {
            fontnik.range({ font, start, end }, (err, res) => (err ? reject(err) : resolve(res)));
        });

        const out = path.join(outDir, `${start}-${end}.pbf`);
        fs.writeFileSync(out, pbf);
        console.log(`wrote ${out} (${pbf.length} bytes)`);
    }
}
