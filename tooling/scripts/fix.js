const fs = require('fs');
let code = fs.readFileSync('apps/web/lib/content/registry.ts', 'utf8');

const start = code.indexOf('let _cacheTime = 0;\n');
const end = code.indexOf('return _cache;\r\n}');

if (start === -1 || end === -1) {
  // If it's already using \n, try that
  const end2 = code.indexOf('return _cache;\n}');
  if (start !== -1 && end2 !== -1) {
    const replacement = `let _cacheTime = 0;
const CACHE_TTL = process.env.NODE_ENV === 'production' ? Infinity : 2000;

// Use process.uptime() to avoid Next.js intercepting Date.now() during prerendering
function getSafeTime() {
  if (typeof process !== 'undefined' && process.uptime) return process.uptime() * 1000;
  return 0;
}

function loadRegistry(): ContentRegistry {
  if (_cache && process.env.NODE_ENV === 'production') {
    return _cache;
  }

  const now = getSafeTime();
  if (_cache && (now - _cacheTime < CACHE_TTL)) {
    return _cache;
  }

  const signature = getDiskSignature();
  if (_cache && _cacheSignature === signature) {
    _cacheTime = now;
    return _cache;
  }

  _cache = {
    clusters: loadClusterMetaFromDisk(),
    pages: buildPageIndexFromDisk(),
  };
  _cacheSignature = signature;
  _cacheTime = now;

  return _cache;
}`;
    code = code.substring(0, start) + replacement + code.substring(end2 + 16);
    fs.writeFileSync('apps/web/lib/content/registry.ts', code);
    console.log('Fixed');
  } else {
    console.log('Could not find bounds to fix');
  }
} else {
  console.log('Found with crlf');
}
