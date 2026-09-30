import fs from 'fs';
import path from 'path';

const REQUIRED_DIRS = ['apps', 'packages', 'services'];
let hasError = false;

for (const dir of REQUIRED_DIRS) {
  if (!fs.existsSync(path.join(process.cwd(), dir))) {
    console.error(`Missing directory: ${dir}`);
    hasError = true;
  }
}

if (hasError) {
  process.exit(1);
} else {
  console.log('Workspace structural check passed.');
}
