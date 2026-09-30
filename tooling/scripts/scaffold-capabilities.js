const fs = require('fs');
const path = require('path');

const capabilities = ['user', 'puja', 'pandit', 'availability', 'pricing', 'booking', 'search', 'recommendations'];
const subdirs = ['domain', 'application', 'contracts', 'ports', 'adapters'];

capabilities.forEach(cap => {
  const root = path.join('packages', cap, 'src');
  subdirs.forEach(sub => {
    fs.mkdirSync(path.join(root, sub), { recursive: true });
  });
  if (cap === 'booking') {
    fs.mkdirSync(path.join(root, 'events'), { recursive: true });
  }
  
  // Create index.ts
  fs.writeFileSync(path.join(root, 'index.ts'), '// Public API for ' + cap + '\n');
  
  // Create package.json
  const pkg = {
    name: `@navdhya/${cap}`,
    version: "0.0.0",
    private: true,
    exports: {
      ".": "./src/index.ts"
    },
    scripts: {
      "typecheck": "tsc --noEmit",
      "test": "vitest run"
    },
    dependencies: {},
    devDependencies: {
      "typescript": "^5.7.3"
    }
  };
  fs.writeFileSync(path.join('packages', cap, 'package.json'), JSON.stringify(pkg, null, 2));
  
  // Create tsconfig.json
  const tsconfig = {
    "extends": "../../tsconfig.json",
    "compilerOptions": {
      "outDir": "./dist",
      "rootDir": "./src"
    },
    "include": ["src/**/*"]
  };
  fs.writeFileSync(path.join('packages', cap, 'tsconfig.json'), JSON.stringify(tsconfig, null, 2));
  
  // Create README.md
  fs.writeFileSync(path.join('packages', cap, 'README.md'), `# ${cap} Capability\n\nOwner: ${cap} domain\n`);
});

console.log('Capabilities scaffolded');
