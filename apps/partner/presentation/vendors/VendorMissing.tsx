import Link from 'next/link';

// Shared not-found / load-failed block for /vendors/:vendorId/* pages
// (mirrors the temple detail fallback posture).

export function VendorMissing() {
  return (
    <main className="p-6 max-w-4xl mx-auto text-center">
      <h1 className="text-2xl font-bold mb-4">Vendor not found</h1>
      <p className="text-gray-500 mb-4">The vendor you&apos;re looking for doesn&apos;t exist or has been removed.</p>
      <Link href="/vendors" className="text-indigo-600 hover:underline">
        Back to vendors
      </Link>
    </main>
  );
}
