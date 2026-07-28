interface AuthDividerProps {
  text?: string
}

export function AuthDivider({
  text = 'OR'
}: AuthDividerProps) {
  return (
    <div className="flex items-center gap-5 py-3">
      <div className="flex-1 h-px bg-gradient-to-r from-transparent via-[rgba(17,17,17,0.06)] to-[rgba(17,17,17,0.06)]" />

      <span
        className="
          text-[10px]
          font-bold
          tracking-[0.22em]
          uppercase
          text-[#A0A0A0]
        "
      >
        {text}
      </span>

      <div className="flex-1 h-px bg-gradient-to-l from-transparent via-[rgba(17,17,17,0.06)] to-[rgba(17,17,17,0.06)]" />
    </div>
  )
}