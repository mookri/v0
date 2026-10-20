const fs = require('fs');
const path = require('path');

const dir = 'packages/pricing/src';

const files = {
  'domain/Money.ts': `export interface Money {\n  amount: number;\n  currency: string;\n}\n`,
  'domain/Tax.ts': `import { Money } from './Money';\n\nexport interface Tax {\n  name: string;\n  percentage: number;\n  amount: Money;\n}\n`,
  'domain/Discount.ts': `import { Money } from './Money';\n\nexport interface Discount {\n  code: string;\n  amount: Money;\n}\n`,
  'domain/PriceRule.ts': `export interface PriceRule {\n  id: string;\n  name: string;\n  apply(basePrice: number): number;\n}\n`,
  'domain/Price.ts': `import { Money } from './Money';\nimport { Tax } from './Tax';\nimport { Discount } from './Discount';\n\nexport interface Price {\n  baseAmount: Money;\n  discounts: Discount[];\n  taxes: Tax[];\n  finalAmount: Money;\n}\n`,
  'contracts/PricingContext.ts': `export interface PricingContext {\n  pujaId: string;\n  panditId?: string;\n  bookingDate: Date;\n  discountCodes: string[];\n}\n`,
  'contracts/PriceQuote.ts': `import { Price } from '../domain/Price';\n\nexport interface PriceQuote {\n  quoteId: string;\n  price: Price;\n  validUntil: Date;\n}\n`,
  'ports/PricingRepository.ts': `import { PriceRule } from '../domain/PriceRule';\n\nexport interface PricingRepository {\n  getBasePrice(pujaId: string): Promise<number>;\n  getActiveRules(): Promise<PriceRule[]>;\n  validateDiscount(code: string): Promise<boolean>;\n}\n`,
  'application/calculate-quote.ts': `import { PricingRepository } from '../ports/PricingRepository';\nimport { PricingContext } from '../contracts/PricingContext';\nimport { PriceQuote } from '../contracts/PriceQuote';\n\nexport async function calculateQuote(repo: PricingRepository, ctx: PricingContext): Promise<PriceQuote> {\n  const base = await repo.getBasePrice(ctx.pujaId);\n  // Placeholder logic\n  return {\n    quoteId: \`quote_\${Date.now()}\`,\n    price: {\n      baseAmount: { amount: base, currency: 'INR' },\n      discounts: [],\n      taxes: [],\n      finalAmount: { amount: base, currency: 'INR' },\n    },\n    validUntil: new Date(Date.now() + 15 * 60000),\n  };\n}\n`,
  'application/validate-price.ts': `import { PriceQuote } from '../contracts/PriceQuote';\n\nexport function validatePrice(quote: PriceQuote): boolean {\n  return quote.validUntil > new Date();\n}\n`,
  'index.ts': `export * from './domain/Money';\nexport * from './domain/Price';\nexport * from './domain/PriceRule';\nexport * from './domain/Discount';\nexport * from './domain/Tax';\n\nexport * from './contracts/PriceQuote';\nexport * from './contracts/PricingContext';\n\nexport * from './ports/PricingRepository';\n\nexport * from './application/calculate-quote';\nexport * from './application/validate-price';\n`
};

for (const [file, content] of Object.entries(files)) {
  const fullPath = path.join(dir, file);
  fs.writeFileSync(fullPath, content);
}
console.log('Pricing capability files generated');
