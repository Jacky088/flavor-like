const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log('--- Starting Asset Build & Minification ---');

// 1. Minify JS using terser
try {
  console.log('Minifying assets/js/flavor-like.js -> assets/js/flavor-like.min.js ...');
  execSync('npx terser assets/js/flavor-like.js -c -m -o assets/js/flavor-like.min.js', { stdio: 'inherit' });
  console.log('JS minification complete.');
} catch (err) {
  console.error('Terser error:', err);
  process.exit(1);
}

// 2. Minify CSS
console.log('Minifying assets/css/flavor-like.css -> assets/css/flavor-like.min.css ...');
function minifyCss(content) {
  return content
    .replace(/\/\*[\s\S]*?\*\//g, '') // remove comments
    .replace(/\r?\n/g, ' ')           // replace newlines with space
    .replace(/\s+/g, ' ')              // collapse multi-spaces
    .replace(/\s*([{;}])\s*/g, '$1')   // trim around { ; }
    .replace(/\s*,\s*/g, ',')          // trim around commas
    .replace(/:\s+/g, ':')             // trim after colon
    .replace(/\s+!important/g, '!important')
    .replace(/;}/g, '}')               // remove trailing semicolon
    .trim();
}

const cssSrc = fs.readFileSync(path.join(__dirname, 'assets/css/flavor-like.css'), 'utf8');
const minCss = minifyCss(cssSrc);
fs.writeFileSync(path.join(__dirname, 'assets/css/flavor-like.min.css'), minCss, 'utf8');
console.log('CSS minification complete. Output size:', minCss.length, 'bytes');

console.log('--- All assets compiled successfully ---');
