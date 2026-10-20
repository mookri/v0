'use client'

import {
  useRef,
  useEffect,
  KeyboardEvent,
  ClipboardEvent
} from 'react'
import clsx from 'clsx'

interface OtpInputProps {
  length?: number
  value: string
  onChange: (value: string) => void
  onComplete?: (value: string) => void
  error?: string
  disabled?: boolean
}

export function OtpInput({
  length = 6,
  value,
  onChange,
  onComplete,
  error,
  disabled = false
}: OtpInputProps) {
  const refs = useRef<
    (HTMLInputElement | null)[]
  >([])
  /**
   * Tracks the last value for which we already fired `onComplete`.
   * The original implementation called `onComplete` on every change
   * once `value.length === length`, which caused:
   *   - Double-fire when a parent also calls onChange from its own
   *     submit handler (each keystroke triggered a fresh onComplete)
   *   - Re-fire when the parent re-rendered with a new `onComplete`
   *     callback identity (effect deps included `onComplete`)
   *   - Re-fire on every re-render while the OTP was full
   * Now we only fire once per distinct value, and depend only on
   * the length, not on the callback identity.
   */
  const lastCompletedRef = useRef<string>("")

  const normalized = value
    .padEnd(length, '')
    .slice(0, length)

  useEffect(() => {
    if (
      onComplete &&
      value.length === length &&
      value.length > 0 &&
      lastCompletedRef.current !== value
    ) {
      lastCompletedRef.current = value
      onComplete(value)
    }
  }, [value, length, onComplete])

  const focus = (index: number) => {
    refs.current[index]?.focus()
    refs.current[index]?.select()
  }

  const updateValue = (
    index: number,
    digit: string
  ) => {
    const clean = digit
      .replace(/\D/g, '')
      .slice(-1)

    const chars = normalized.split('')
    chars[index] = clean

    const next = chars.join('').trimEnd()

    onChange(next)

    if (clean && index < length - 1) {
      focus(index + 1)
    }
  }

  const handleKeyDown = (
    index: number,
    e: KeyboardEvent<HTMLInputElement>
  ) => {
    if (disabled) return

    if (e.key === 'Backspace') {
      e.preventDefault()

      const chars = normalized.split('')

      if (chars[index]) {
        chars[index] = ''
        onChange(chars.join('').trimEnd())
      } else if (index > 0) {
        chars[index - 1] = ''
        onChange(chars.join('').trimEnd())
        focus(index - 1)
      }
    }

    if (e.key === 'ArrowLeft' && index > 0) {
      focus(index - 1)
    }

    if (
      e.key === 'ArrowRight' &&
      index < length - 1
    ) {
      focus(index + 1)
    }
  }

  const handlePaste = (
    e: ClipboardEvent<HTMLInputElement>
  ) => {
    e.preventDefault()

    if (disabled) return

    const pasted = e.clipboardData
      .getData('text')
      .replace(/\D/g, '')
      .slice(0, length)

    onChange(pasted)

    focus(
      Math.min(
        pasted.length,
        length - 1
      )
    )
  }

  return (
    <div className="space-y-4">
      <div
        role="group"
        aria-label="Verification code"
        className="
          flex
          items-center
          justify-center
          gap-2
        "
      >
        {Array.from({ length }).map(
          (_, index) => {
            const filled =
              !!normalized[index]

            return (
              <input
                key={index}
                ref={el => {
                  refs.current[index] = el
                }}
                type="text"
                inputMode="numeric"
                autoComplete={
                  index === 0
                    ? 'one-time-code'
                    : 'off'
                }
                maxLength={1}
                value={normalized[index]}
                disabled={disabled}
                aria-label={`Digit ${index + 1
                  }`}
                aria-invalid={!!error}
                onChange={e =>
                  updateValue(
                    index,
                    e.target.value
                  )
                }
                onKeyDown={e =>
                  handleKeyDown(index, e)
                }
                onPaste={handlePaste}
                className={clsx(
                  `
                  w-[36px]
                  h-[42px]
                  sm:w-[40px]
                  sm:h-[46px]
                  rounded-[10px]
                  border
                  text-center
                  text-[16px]
                  sm:text-[18px]
                  font-bold
                  bg-[#FAFAFA]
                  outline-none
                  transition-all
                  duration-200
                  `,
                  disabled &&
                  'opacity-40 cursor-not-allowed',
                  error
                    ? `
                      border-red-400
                      bg-white
                      text-red-500
                    `
                    : filled
                      ? `
                      border-[rgba(17,17,17,0.06)]
                      text-[#111111]
                      bg-[#FAFAFA]
                    `
                      : `
                      border-[rgba(17,17,17,0.06)]
                      text-[#111111]
                      focus:border-[#91713B]
                      focus:bg-white
                      focus:ring-[6px]
                      focus:ring-[#91713B]/[0.04]
                      focus:scale-[1.04]
                      focus:shadow-[0_4px_16px_rgba(145,113,59,0.05)]
                    `
                )}
              />
            )
          }
        )}
      </div>

      {error && (
        <p
          role="alert"
          className="
            text-center
            text-[11.5px]
            font-semibold
            text-red-500
          "
        >
          {error}
        </p>
      )}
    </div>
  )
}