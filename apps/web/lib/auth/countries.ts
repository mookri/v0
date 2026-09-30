export interface Country {
    code: string
    name: string
    dialCode: string
}

export const COUNTRIES: Country[] = [
    {
        code: 'IN',
        name: 'India',
        dialCode: '91'
    },
    {
        code: 'US',
        name: 'United States',
        dialCode: '1'
    },
    {
        code: 'GB',
        name: 'United Kingdom',
        dialCode: '44'
    },
    {
        code: 'AE',
        name: 'United Arab Emirates',
        dialCode: '971'
    },
    {
        code: 'SG',
        name: 'Singapore',
        dialCode: '65'
    },
    {
        code: 'AU',
        name: 'Australia',
        dialCode: '61'
    },
    {
        code: 'CA',
        name: 'Canada',
        dialCode: '1'
    },
    {
        code: 'DE',
        name: 'Germany',
        dialCode: '49'
    },
    {
        code: 'FR',
        name: 'France',
        dialCode: '33'
    },
    {
        code: 'JP',
        name: 'Japan',
        dialCode: '81'
    }
]

export const DEFAULT_COUNTRY = 'IN'

export function getCountryByCode(code: string) {
    return (
        COUNTRIES.find(
            country => country.code === code
        ) || COUNTRIES[0]
    )
}

export function searchCountries(query: string) {
    const normalized =
        query.toLowerCase().trim()

    if (!normalized) return COUNTRIES

    return COUNTRIES.filter(
        country =>
            country.name
                .toLowerCase()
                .includes(normalized) ||
            country.code
                .toLowerCase()
                .includes(normalized) ||
            country.dialCode.includes(normalized)
    )
}