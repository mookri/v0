import Link from 'next/link'

export default function TestNavigationPage() {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4">
      <h1 className="text-2xl font-bold mb-4">Navigation Test Page</h1>
      <p className="text-muted-foreground mb-6">Click the link below to test navigation loading</p>
      
      <div className="flex flex-col gap-4">
        <Link 
          href="/" 
          className="px-4 py-2 bg-primary text-primary-foreground rounded hover:opacity-90 text-center"
        >
          Go to Home Page
        </Link>
        
        <Link 
          href="/pujas" 
          className="px-4 py-2 bg-secondary text-secondary-foreground rounded hover:opacity-90 text-center"
        >
          Go to Pujas Page
        </Link>
        
        <Link 
          href="/contact" 
          className="px-4 py-2 bg-accent text-accent-foreground rounded hover:opacity-90 text-center"
        >
          Go to Contact Page
        </Link>
      </div>
    </div>
  )
}