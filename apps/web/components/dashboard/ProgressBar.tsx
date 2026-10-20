'use client'

interface ProgressBarProps {
  label: string
  value: number
  maxValue?: number
  color?: string
}

export function ProgressBar({ label, value, maxValue = 100, color = 'bg-primary' }: ProgressBarProps) {
  const percentage = Math.min((value / maxValue) * 100, 100)

  return (
    <div className="space-y-1.5">
      <div className="flex items-center justify-between">
        <span className="text-sm text-foreground">{label}</span>
        <span className="text-sm font-medium text-muted-foreground">{value}%</span>
      </div>
      <div className="h-2 rounded-full bg-muted overflow-hidden">
        <div
          className={`h-full rounded-full transition-all duration-500 ${color}`}
          style={{ width: `${percentage}%` }}
        />
      </div>
    </div>
  )
}
