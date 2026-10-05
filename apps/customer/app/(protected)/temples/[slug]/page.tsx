import { TempleDetailPageView } from '@/presentation/temples/TempleDetailPage';
import { customerApi } from '@/adapters/backend/customer-api-client';

interface TempleDetailPageProps {
  params: Promise<{ slug: string }>;
}

export default async function TempleDetailPage({ params }: TempleDetailPageProps) {
  const { slug } = await params;

  try {
    const data = await customerApi.getTemple(slug);
    const temple = data as {
      id: string;
      slug: string;
      name: string;
      trust_name: string;
      type: string;
      address: string;
      city: string;
      state: string;
      lat: number | null;
      lng: number | null;
      images: string[];
      amenities: string[];
      description: string | null;
      verification_status: string;
      status: string;
      created_at: string;
    };
    return <TempleDetailPageView temple={temple} />;
  } catch {
    return <div className="p-6 max-w-4xl mx-auto text-center">
      <h1 className="text-2xl font-bold mb-4">Temple not found</h1>
      <p className="text-gray-500 mb-4">The temple you're looking for doesn't exist or has been removed.</p>
      <a href="/temples" className="text-indigo-600 hover:underline">Back to temples</a>
    </div>;
  }
}