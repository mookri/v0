export default function TestColorsPage() {
  return (
    <div className="min-h-screen bg-background p-4">
      <h1 className="text-2xl font-bold mb-4">Color Test</h1>
      
      <div className="space-y-4">
        <div>
          <h2 className="text-lg font-semibold mb-2">Text Colors</h2>
          <div className="flex flex-wrap gap-2">
            <div className="p-2 bg-card border border-border">
              <span className="text-foreground">Foreground</span>
            </div>
            <div className="p-2 bg-card border border-border">
              <span className="text-primary">Primary</span>
            </div>
            <div className="p-2 bg-card border border-border">
              <span className="text-muted-foreground">Muted Foreground</span>
            </div>
          </div>
        </div>
        
        <div>
          <h2 className="text-lg font-semibold mb-2">Background Colors</h2>
          <div className="flex flex-wrap gap-2">
            <div className="p-2 border border-border">
              <span className="text-foreground">Default Background</span>
            </div>
            <div className="p-2 bg-card border border-border">
              <span className="text-foreground">Card Background</span>
            </div>
            <div className="p-2 bg-muted border border-border">
              <span className="text-foreground">Muted Background</span>
            </div>
          </div>
        </div>
        
        <div>
          <h2 className="text-lg font-semibold mb-2">Preloader Test</h2>
          <div className="p-4 bg-card border border-border rounded-lg">
            <div className="flex items-center justify-center">
              <div className="w-6 h-6 border-3 rounded-full border-t-primary border-r-primary border-b-primary border-l-transparent animate-spin"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}