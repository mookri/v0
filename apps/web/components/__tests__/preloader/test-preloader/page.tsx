import Preloader from '@/components/ui/preloader'

export default function TestPreloaderPage() {
  return (
    <div className="min-h-screen bg-background flex flex-col items-center justify-center p-4">
      <h1 className="text-2xl font-bold mb-8">Preloader Test</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div className="flex flex-col items-center">
          <h2 className="text-lg font-semibold mb-4">Small</h2>
          <Preloader size="sm" className="text-primary" />
        </div>
        
        <div className="flex flex-col items-center">
          <h2 className="text-lg font-semibold mb-4">Medium</h2>
          <Preloader size="md" className="text-primary" />
        </div>
        
        <div className="flex flex-col items-center">
          <h2 className="text-lg font-semibold mb-4">Large</h2>
          <Preloader size="lg" className="text-primary" />
        </div>
      </div>
      
      <div className="mt-8 p-4 bg-card rounded-lg border border-border">
        <p className="text-muted-foreground">
          If you can see the preloaders spinning above, they are working correctly.
        </p>
      </div>
    </div>
  )
}