export const IST_TIMEZONE = 'Asia/Kolkata';

function parseDateInput(input: Date | string | number): Date {
  const date = input instanceof Date ? input : new Date(input);
  if (Number.isNaN(date.getTime())) {
    throw new RangeError(`Invalid date/time input: ${String(input)}`);
  }
  return date;
}

export function formatIstDate(input: Date | string | number, timeZone: string = IST_TIMEZONE): string {
  const date = parseDateInput(input);
  return new Intl.DateTimeFormat('en-IN', {
    timeZone,
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date);
}

export function formatIstTime(input: Date | string | number, timeZone: string = IST_TIMEZONE): string {
  const date = parseDateInput(input);
  const formatted = new Intl.DateTimeFormat('en-IN', {
    timeZone,
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  }).format(date);
  return timeZone === IST_TIMEZONE ? `${formatted} IST` : `${formatted} (${timeZone})`;
}

export function formatIstDateTime(input: Date | string | number, timeZone: string = IST_TIMEZONE): string {
  return `${formatIstDate(input, timeZone)}, ${formatIstTime(input, timeZone)}`;
}

export function toIstIsoDate(input: Date | string | number): string {
  const date = parseDateInput(input);
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: IST_TIMEZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(date);
  const year = parts.find((p) => p.type === 'year')?.value ?? '1970';
  const month = parts.find((p) => p.type === 'month')?.value ?? '01';
  const day = parts.find((p) => p.type === 'day')?.value ?? '01';
  return `${year}-${month}-${day}`;
}

export function formatMuhuratWindow(
  start: Date | string | number,
  end: Date | string | number,
  timeZone: string = IST_TIMEZONE,
): string {
  const startDate = parseDateInput(start);
  const endDate = parseDateInput(end);
  if (endDate.getTime() < startDate.getTime()) {
    throw new RangeError('Muhurat window end must not precede start.');
  }
  const suffix = timeZone === IST_TIMEZONE ? 'IST' : timeZone;
  const fmt = new Intl.DateTimeFormat('en-IN', {
    timeZone,
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  });
  return `${formatIstDate(startDate, timeZone)} · ${fmt.format(startDate)} – ${fmt.format(endDate)} ${suffix}`;
}

