const fs = require('fs');
const path = require('path');

const srcDir = path.join(process.cwd(), 'packages/design-system/src');

function walk(dir) {
  const files = fs.readdirSync(dir);
  for (const file of files) {
    const fullPath = path.join(dir, file);
    if (fs.statSync(fullPath).isDirectory()) {
      walk(fullPath);
    } else if (file.endsWith('.ts') || file.endsWith('.tsx')) {
      let content = fs.readFileSync(fullPath, 'utf8');
      if (content.includes('@/lib/utils')) {
        const relativePath = path.relative(path.dirname(fullPath), path.join(srcDir, 'lib/utils'));
        // Convert Windows backslashes to forward slashes for imports
        const posixPath = relativePath.split(path.sep).join(path.posix.sep);
        const importPath = posixPath.startsWith('.') ? posixPath : `./${posixPath}`;
        content = content.replace(/['"]@\/lib\/utils['"]/g, `"${importPath}"`);
        fs.writeFileSync(fullPath, content);
      }
    }
  }
}

walk(srcDir);
