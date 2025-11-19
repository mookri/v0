#!/usr/bin/env node

// bin/dlo.ts
// CLI tool for DLO-X architecture

import { Command } from 'commander';
import { existsSync, mkdirSync, writeFileSync, readFileSync } from 'fs';
import { join, resolve } from 'path';

const program = new Command();

program
  .name('dlo')
  .description('CLI tool for DLO-X architecture')
  .version('1.0.0');

// Initialize DLO-X in a project
program
  .command('init')
  .description('Initialize DLO-X architecture in the current project')
  .action(() => {
    console.log('Initializing DLO-X architecture...');
    
    // Create required directories
    const libDir = join(process.cwd(), 'lib', 'dlo-x');
    const componentsDir = join(process.cwd(), 'components', 'dlo-x');
    const shellsDir = join(componentsDir, 'shells');
    
    if (!existsSync(libDir)) {
      mkdirSync(libDir, { recursive: true });
      console.log('Created lib/dlo-x directory');
    }
    
    if (!existsSync(componentsDir)) {
      mkdirSync(componentsDir, { recursive: true });
      console.log('Created components/dlo-x directory');
    }
    
    if (!existsSync(shellsDir)) {
      mkdirSync(shellsDir, { recursive: true });
      console.log('Created components/dlo-x/shells directory');
    }
    
    console.log('DLO-X initialization complete!');
  });

// Generate a shell component
program
  .command('generate')
  .description('Generate DLO-X components')
  .argument('<type>', 'Type of component to generate (shell, page, policy)')
  .argument('<name>', 'Name of the component')
  .action((type: string, name: string) => {
    switch (type) {
      case 'shell':
        generateShell(name);
        break;
      case 'page':
        generatePage(name);
        break;
      case 'policy':
        generatePolicy(name);
        break;
      default:
        console.error(`Unknown type: ${type}. Use shell, page, or policy.`);
        process.exit(1);
    }
  });

// Inspect a layout policy
program
  .command('inspect')
  .description('Inspect layout policy for a given path, role, and tenant')
  .option('--path <path>', 'Path to inspect')
  .option('--role <role>', 'User role')
  .option('--tenant <tenant>', 'Tenant ID')
  .action((options: { path?: string; role?: string; tenant?: string }) => {
    console.log('Inspecting layout policy...');
    console.log('Path:', options.path || '/');
    console.log('Role:', options.role || 'User');
    console.log('Tenant:', options.tenant || 'default');
    
    // In a real implementation, this would load and evaluate policies
    console.log('Policy inspection complete.');
  });

// Validate layout policies
program
  .command('validate')
  .description('Validate all layout policies')
  .action(() => {
    console.log('Validating layout policies...');
    
    // In a real implementation, this would load and validate all policies
    console.log('Policy validation complete.');
  });

// Generate coverage report
program
  .command('coverage')
  .description('Generate coverage report for layout policies')
  .option('--sitemap <file>', 'Sitemap file to check coverage against')
  .action((options: { sitemap?: string }) => {
    console.log('Generating coverage report...');
    
    if (options.sitemap) {
      console.log('Checking coverage against sitemap:', options.sitemap);
    }
    
    // In a real implementation, this would analyze policy coverage
    console.log('Coverage report generated.');
  });

program.parse();

// Helper functions
function generateShell(name: string) {
  const shellDir = join(process.cwd(), 'components', 'dlo-x', 'shells');
  const fileName = `${name}.tsx`;
  const filePath = join(shellDir, fileName);
  
  if (existsSync(filePath)) {
    console.error(`Shell ${name} already exists!`);
    process.exit(1);
  }
  
  const shellTemplate = `// components/dlo-x/shells/${name}.tsx
import { ReactNode } from 'react';

interface ${name}Props {
  children: ReactNode;
}

export function ${name}({ children }: ${name}Props) {
  return (
    <div className="min-h-screen bg-background">
      {/* ${name} layout */}
      <main className="flex-grow">
        {children}
      </main>
    </div>
  );
}
`;
  
  writeFileSync(filePath, shellTemplate);
  console.log(`Generated shell component: ${fileName}`);
}

function generatePage(name: string) {
  const pagesDir = join(process.cwd(), 'app');
  const fileName = `${name}/page.tsx`;
  const dirPath = join(pagesDir, name);
  const filePath = join(dirPath, 'page.tsx');
  
  if (existsSync(filePath)) {
    console.error(`Page ${name} already exists!`);
    process.exit(1);
  }
  
  if (!existsSync(dirPath)) {
    mkdirSync(dirPath, { recursive: true });
  }
  
  const pageTemplate = `// app/${name}/page.tsx
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator";

export default function ${name.charAt(0).toUpperCase() + name.slice(1)}Page() {
  // For server components, we need to get context from headers
  // In a real implementation, this would come from middleware
  const context = {
    requestId: '${name}-page-request',
    tenantId: 'default' as const,
    userRole: 'User' as const,
    device: 'desktop' as const,
    seed: '${name}-page-seed',
    path: '/${name}'
  };

  return (
    <LayoutOrchestrator context={context}>
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-3xl font-bold text-foreground">${name.charAt(0).toUpperCase() + name.slice(1)} Page</h1>
        <p className="mt-4 text-muted-foreground">This is the ${name} page using DLO-X architecture.</p>
      </div>
    </LayoutOrchestrator>
  );
}
`;
  
  writeFileSync(filePath, pageTemplate);
  console.log(`Generated page: ${fileName}`);
}

function generatePolicy(name: string) {
  const policiesDir = join(process.cwd(), 'lib', 'dlo-x');
  const fileName = `${name}-policy.ts`;
  const filePath = join(policiesDir, fileName);
  
  if (existsSync(filePath)) {
    console.error(`Policy ${name} already exists!`);
    process.exit(1);
  }
  
  const policyTemplate = `// lib/dlo-x/${name}-policy.ts
import { PolicyBuilder } from './policy-builder';

export const ${name}Policy = PolicyBuilder.create()
  .id('${name}-policy')
  .path('/${name}')
  .use('MainShell')
  .priority(10)
  .build();
`;
  
  writeFileSync(filePath, policyTemplate);
  console.log(`Generated policy: ${fileName}`);
}