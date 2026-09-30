'use client'

import {
  useEffect,
  useRef,
  useState,
  useId,
  useMemo
} from 'react'
import clsx from 'clsx'
import { ChevronDown, Search } from 'lucide-react'
import { COUNTRIES } from '@/lib/auth/countries'

function getFlagEmoji(countryCode: string) {
  const codePoints = countryCode
    .toUpperCase()
    .split('')
    .map(char => 127397 + char.charCodeAt(0))
  return String.fromCodePoint(...codePoints)
}

interface PhoneInputProps {
  value: string
  onChange: (value: string) => void
  countryCode: string
  onCountryChange: (code: string) => void
  error?: string
  disabled?: boolean
}

export function PhoneInput({
  value,
  onChange,
  countryCode,
  onCountryChange,
  error,
  disabled = false
}: PhoneInputProps) {
  const [open, setOpen] = useState(false)
  const [search, setSearch] = useState('')

  const dropdownRef = useRef<HTMLDivElement>(null)
  const searchRef = useRef<HTMLInputElement>(null)

  const id = useId()
  const errorId = `${id}-error`

  const selected =
    COUNTRIES.find(c => c.code === countryCode) ||
    COUNTRIES[0]

  const filtered = useMemo(() => {
    return COUNTRIES.filter(
      c =>
        c.name.toLowerCase().includes(search.toLowerCase()) ||
        c.dialCode.includes(search)
    )
  }, [search])

  useEffect(() => {
    const close = (e: MouseEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(e.target as Node)
      ) {
        setOpen(false)
        setSearch('')
      }
    }

    document.addEventListener('mousedown', close)
    return () =>
      document.removeEventListener('mousedown', close)
  }, [])

  useEffect(() => {
    if (open) searchRef.current?.focus()
  }, [open])

  return (
    <div ref={dropdownRef} className="w-full relative">
      <div
        className={clsx(
          `
          relative
          flex
          h-[42px]
          sm:h-[44px]
          rounded-[12px]
          bg-[#FAFAFA]
          border
          transition-all
          duration-150
          `,
          error
            ? 'border-red-300 bg-white'
            : `
              border-[#E2D4C0]
              focus-within:bg-white
              focus-within:border-[#91713B]
              focus-within:ring-[4px]
              focus-within:ring-[#91713B]/[0.03]
            `
        )}
      >
        {/* country prefix trigger button */}
        <button
          type="button"
          onClick={() => setOpen(!open)}
          disabled={disabled}
          className="
            h-full
            px-3.5
            flex
            items-center
            gap-1
            border-r
            border-[#E2D4C0]
            text-[#111]
            font-bold
            text-[12.5px]
            sm:text-[13px]
            rounded-l-[12px]
            hover:bg-[#EAEAEA]/30
            transition-colors
          "
        >
          <span className="text-[15px] sm:text-[16px] mr-0.5" aria-hidden="true">
            {getFlagEmoji(selected.code)}
          </span>
          <span>+{selected.dialCode}</span>

          <ChevronDown
            className={clsx(
              'w-3 h-3 text-[#888] transition-transform',
              open && 'rotate-180'
            )}
          />
        </button>

        {/* input */}
        <input
          type="tel"
          value={value}
          disabled={disabled}
          onChange={(e) =>
            onChange(e.target.value.replace(/\D/g, ''))
          }
          placeholder="Phone number"
          className="
            flex-1
            px-3.5
            text-[13px]
            sm:text-[13.5px]
            font-bold
            text-[#111]
            placeholder:text-[#A0A0A0]
            outline-none
            bg-transparent
            rounded-r-[12px]
          "
        />
      </div>

      {/* absolute dropdown floated relative to the entire outer container, spanning 100% width with no clipping */}
      {open && (
        <div className="
          absolute
          top-full
          left-0
          right-0
          w-full
          mt-2
          rounded-[12px]
          bg-white
          border
          border-[#E2D4C0]
          shadow-[0_12px_32px_rgba(0,0,0,0.04)]
          overflow-hidden
          z-50
        ">
          <div className="p-2.5 border-b border-[#E2D4C0]">
            <div className="relative">
              <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#AAA]" />

              <input
                ref={searchRef}
                value={search}
                onChange={(e) =>
                  setSearch(e.target.value)
                }
                placeholder="Search country"
                className="
                  w-full
                  h-9
                  rounded-lg
                  bg-[#FAFAFA]
                  px-8
                  text-[12.5px]
                  outline-none
                  font-bold
                  text-[#111]
                "
              />
            </div>
          </div>

          <div className="max-h-[220px] overflow-y-auto">
            {filtered.map(country => {
              const isSelected = country.code === countryCode
              return (
                <button
                  key={country.code}
                  type="button"
                  onClick={() => {
                    onCountryChange(country.code)
                    setOpen(false)
                    setSearch('')
                  }}
                  className={clsx(
                    `
                    w-full
                    px-4 py-2.5
                    flex
                    justify-between
                    items-center
                    text-[13.5px]
                    font-semibold
                    transition-all
                    `,
                    isSelected
                      ? 'bg-[#91713B]/[0.03] text-[#91713B]'
                      : 'text-[#333] hover:bg-[#F5F5F5]'
                  )}
                >
                  <span className="flex items-center gap-2">
                    <span className="text-[17px]">{getFlagEmoji(country.code)}</span>
                    <span className={clsx(isSelected && 'font-bold')}>{country.name}</span>
                  </span>
                  <span className="flex items-center gap-2.5">
                    <span className={clsx('text-[13.5px] font-bold', isSelected ? 'text-[#91713B]' : 'text-[#888]')}>
                      +{country.dialCode}
                    </span>
                    {isSelected && (
                      <span className="w-1.5 h-1.5 rounded-full bg-[#91713B] shadow-[0_0_8px_rgba(145,113,59,0.5)] animate-pulse" />
                    )}
                  </span>
                </button>
              )
            })}
          </div>
        </div>
      )}

      {error && (
        <p
          id={errorId}
          className="mt-1.5 pl-1 text-[11.5px] font-semibold text-red-500"
        >
          {error}
        </p>
      )}
    </div>
  )
}