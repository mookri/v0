'use client'

import React, {
  forwardRef,
  useId
} from 'react'
import clsx from 'clsx'

interface FloatingInputProps
  extends React.InputHTMLAttributes<HTMLInputElement> {
  label: string
  error?: string
}

export const FloatingInput = forwardRef<
  HTMLInputElement,
  FloatingInputProps
>(
  (
    {
      label,
      error,
      className,
      id,
      value,
      defaultValue,
      ...props
    },
    ref
  ) => {
    const generatedId = useId()
    const inputId = id || generatedId
    const errorId = `${inputId}-error`

    return (
      <div className="w-full flex flex-col">
        {/* Human-designed static Label-above-Input */}
        <label
          htmlFor={inputId}
          className={clsx(
            "text-[11px] font-bold tracking-[0.06em] uppercase mb-1.5 transition-colors",
            error ? "text-red-500" : "text-[#6B6B6B]"
          )}
        >
          {label}
        </label>

        <div className="relative">
          <input
            ref={ref}
            id={inputId}
            value={value}
            defaultValue={defaultValue}
            aria-invalid={!!error}
            aria-describedby={
              error ? errorId : undefined
            }
            className={clsx(
              `
              w-full
              h-[42px]
              sm:h-[44px]
              rounded-[12px]
              px-4
              bg-[#FAFAFA]
              border
              text-[13px]
              sm:text-[13.5px]
              font-bold
              text-[#111111]
              placeholder:text-[#A0A0A0]
              outline-none
              transition-all
              duration-150
              `,
              error
                ? `
                  border-red-400
                  bg-white
                `
                : `
                  border-[#E2D4C0]
                  focus:border-[#91713B]
                  focus:bg-white
                  focus:ring-[4px]
                  focus:ring-[#91713B]/[0.03]
                `,
              className
            )}
            {...props}
          />
        </div>

        {error && (
          <p
            id={errorId}
            role="alert"
            className="
              mt-1.5
              pl-1
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
)

FloatingInput.displayName = 'FloatingInput'