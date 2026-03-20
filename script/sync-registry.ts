import { readFileSync, writeFileSync, readdirSync } from 'fs';
import { join } from 'path';

const CONTENT_DIR = join(process.cwd(), 'content');
const PAGES_DIR = join(CONTENT_DIR, 'pages');
const REGISTRY_PATH = join(CONTENT_DIR, 'registry.json');

function sync() {
    const registry = JSON.parse(readFileSync(REGISTRY_PATH, 'utf-8'));
    const files = readdirSync(PAGES_DIR).filter(f => f.endsWith('.json'));

    const updatedPages = { ...registry.pages };

    for (const file of files) {
        const filePath = join(PAGES_DIR, file);
        const content = JSON.parse(readFileSync(filePath, 'utf-8'));

        if (content.slug && content.cluster) {
            updatedPages[content.slug] = {
                file: file,
                cluster: content.cluster
            };
            console.log(`Synced: ${content.slug} -> ${file} (${content.cluster})`);
        } else {
            console.warn(`Skipping ${file}: missing slug or cluster`);
        }
    }

    registry.pages = updatedPages;
    writeFileSync(REGISTRY_PATH, JSON.stringify(registry, null, 2), 'utf-8');
    console.log('Registry updated successfully!');
}

sync();
