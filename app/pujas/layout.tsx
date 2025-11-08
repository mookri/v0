export default function PujasLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <>
      <main className="min-h-screen">
        {children}
      </main>
    </>
  )
}