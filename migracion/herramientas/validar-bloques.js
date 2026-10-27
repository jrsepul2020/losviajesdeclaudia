const { JSDOM } = require('jsdom');
const dom = new JSDOM('<!doctype html><html><body></body></html>', { url: 'http://localhost/' });
for (const k of ['window','document','navigator','Node','HTMLElement','Element','DOMParser','MutationObserver','getComputedStyle','requestAnimationFrame','CustomEvent','Event','KeyboardEvent','Range','Text']) {
  try { global[k] = dom.window[k] ?? global[k]; } catch (e) {}
}
global.window.matchMedia = global.window.matchMedia || (() => ({ matches: false, addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {} }));
global.matchMedia = global.window.matchMedia;
global.requestAnimationFrame = global.requestAnimationFrame || ((cb) => setTimeout(cb, 0));
const origWarn = console.warn, origErr = console.error, origInfo = console.info, origLog = console.log;
console.warn = console.error = console.info = console.log = () => {};
const { registerCoreBlocks } = require('@wordpress/block-library');
const { parse } = require('@wordpress/blocks');
registerCoreBlocks();
const items = JSON.parse(require('fs').readFileSync(process.argv[2], 'utf8'));
const bad = {}; let total = 0, invalid = 0;
const walk = (blocks, it) => blocks.forEach((b) => {
  if (b.name) { total++; if (b.isValid === false) { invalid++; (bad[it.post_type + ' ' + it.post_name] ||= []).push(b.name); } }
  walk(b.innerBlocks || [], it);
});
for (const it of items) { if (it.post_content.includes('<!-- wp:')) walk(parse(it.post_content), it); }
console.log = origLog;
const byType = {}; for (const [k, v] of Object.entries(bad)) for (const n of v) byType[(k.split(' ')[0]) + ':' + n] = (byType[k.split(' ')[0] + ':' + n] || 0) + 1;
console.log(JSON.stringify({ documentos: items.length, bloques: total, invalidos: invalid, porTipo: byType, ejemplos: Object.entries(bad).slice(0, 8) }, null, 1));
