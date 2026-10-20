const fs = require('fs');
const path = require('path');

const scriptsDir = path.join(process.cwd(), 'scripts');
if (fs.existsSync(scriptsDir)) {
  const files = fs.readdirSync(scriptsDir).filter(f => f.endsWith('.ts'));
  for (const file of files) {
    const p = path.join(scriptsDir, file);
    let content = fs.readFileSync(p, 'utf8');
    
    // Quick replacements
    content = content.replace(/const root = process\.cwd\(\);/g, "const root = path.join(process.cwd(), 'apps/web');");
    content = content.replace(/const ROOT = process\.cwd\(\);/g, "const ROOT = path.join(process.cwd(), 'apps/web');");
    content = content.replace(/const PAGES_DIR = join\(process\.cwd\(\),\s*"content",\s*"pages"\);/g, 'const PAGES_DIR = join(process.cwd(), "apps/web", "content", "pages");');
    content = content.replace(/const MESSAGES_DIR = join\(process\.cwd\(\),\s*"messages"\);/g, 'const MESSAGES_DIR = join(process.cwd(), "apps/web", "messages");');
    
    // Also fix imports since we moved things!
    content = content.replace(/from "\.\.\/lib/g, 'from "../apps/web/lib');
    content = content.replace(/from "\.\/lib/g, 'from "../apps/web/lib');
    
    fs.writeFileSync(p, content, 'utf8');
  }
}
