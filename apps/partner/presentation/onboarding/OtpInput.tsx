'use client';

import React, { useRef } from 'react';

interface OtpInputProps {
  value: string;
  onChange: (value: string) => void;
  length?: number;
  disabled?: boolean;
}

export function OtpInput({ value, onChange, length = 6, disabled = false }: OtpInputProps) {
  const inputsRef = useRef<(HTMLInputElement | null)[]>([]);

  const digits = value.split('').slice(0, length);
  while (digits.length < length) {
    digits.push('');
  }

  const handleChange = (index: number, e: React.ChangeEvent<HTMLInputElement>) => {
    const val = e.target.value.replace(/\D/g, '');
    if (!val) {
      const next = [...digits];
      next[index] = '';
      onChange(next.join(''));
      return;
    }

    if (val.length === 1) {
      const next = [...digits];
      next[index] = val;
      onChange(next.join(''));
      if (index < length - 1) {
        inputsRef.current[index + 1]?.focus();
      }
    } else {
      // Pasted multiple digits
      const pastedDigits = val.slice(0, length).split('');
      onChange(pastedDigits.join(''));
      const nextIndex = Math.min(pastedDigits.length, length - 1);
      inputsRef.current[nextIndex]?.focus();
    }
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace' && !digits[index] && index > 0) {
      inputsRef.current[index - 1]?.focus();
    }
  };

  return (
    <div style={{ display: 'flex', gap: '8px', justifyContent: 'center' }}>
      {Array.from({ length }, (_, i) => (
        <input
          key={i}
          ref={(el) => {
            inputsRef.current[i] = el;
          }}
          type="text"
          inputMode="numeric"
          pattern="[0-9]*"
          maxLength={1}
          value={digits[i] ?? ''}
          onChange={(e) => handleChange(i, e)}
          onKeyDown={(e) => handleKeyDown(i, e)}
          disabled={disabled}
          autoComplete={i === 0 ? 'one-time-code' : 'off'}
          style={{
            width: '44px',
            height: '52px',
            fontSize: '22px',
            fontWeight: '600',
            textAlign: 'center',
            borderRadius: '8px',
            border: '1px solid #ccc',
            background: disabled ? '#f5f5f5' : '#fff',
            outline: 'none',
          }}
        />
      ))}
    </div>
  );
}
