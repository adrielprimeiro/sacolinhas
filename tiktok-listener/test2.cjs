const lib = require('tiktok-live-connector');
console.log('WebcastPushConnection in lib:', 'WebcastPushConnection' in lib);
console.log('Keys:', Object.keys(lib).filter(k => k.includes('Connection') || k.includes('TikTok') || k.includes('Live')));
console.log('typeof lib:', typeof lib);
if (typeof lib === 'function') {
  console.log('lib is a function (probably the class itself)');
}
