interface AuthHeaderProps {
  eyebrow?: string
  title: string
  subtitle: string
  align?: 'left' | 'center'
}

export function AuthHeader({
  eyebrow,
  title,
  subtitle,
  align = 'left'
}: AuthHeaderProps) {
  const alignment =
    align === 'center'
      ? 'text-center items-center'
      : 'text-left items-start'

  return (
    <div
      className={`
        flex
        flex-col
        gap-1.5
        ${alignment}
      `}
    >
      {eyebrow && (
        <p className="text-[9.5px] sm:text-[10px] font-bold tracking-[0.16em] uppercase text-[#91713B] animate-fade-in">
          {eyebrow}
        </p>
      )}

      <h1
        className="
          text-[21px]
          sm:text-[23px]
          font-bold
          tracking-[-0.04em]
          leading-[1.15]
          text-[#111111]
          text-balance
        "
      >
        {title}
      </h1>

      <p
        className="
          max-w-[34ch]
          text-[12px]
          sm:text-[12.5px]
          leading-[1.45]
          font-medium
          text-[#6B6B6B]
          text-pretty
        "
      >
        {subtitle}
      </p>

      {/* subtle luxury accent */}
      <div
        className="
          mt-1
          h-px
          w-8
          bg-gradient-to-r
          from-[#91713B]/40
          to-transparent
        "
      />
    </div>
  )
}