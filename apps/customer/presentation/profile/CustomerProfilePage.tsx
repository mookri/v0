import { CustomerProfileViewModel } from '@/application/profile/customer-profile';

export function CustomerProfilePage({ profile, error }: { profile: CustomerProfileViewModel | null; error?: string }) {
  if (error) {
    return (
      <main>
        <h1>Profile</h1>
        <p role="alert">{error}</p>
        <a href="/">Go home</a>
      </main>
    );
  }
  if (!profile) {
    return (
      <main>
        <h1>Profile not found</h1>
      </main>
    );
  }
  return (
    <main>
      <h1>{profile.name}</h1>
      <p>{profile.email}</p>
    </main>
  );
}
