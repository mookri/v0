// Applicant-facing strings, en + hi. Every en key must exist in hi
// (guarded by test). Reviewer console stays English v1.
export type OnboardingLocale = 'en' | 'hi';

const en = {
  'steps.identity.title': 'Who are you?',
  'steps.identity.name': 'Full name',
  'steps.identity.phone': 'Mobile number',
  'steps.credentials.title': 'Your qualifications',
  'steps.credentials.certifications': 'Certifications (comma separated)',
  'steps.services.title': 'Services you offer',
  'steps.services.list': 'Service IDs (comma separated)',
  'steps.areas.title': 'Where do you serve?',
  'steps.areas.list': 'Areas (comma separated)',
  'steps.availability.title': 'Availability',
  'steps.availability.set': 'My availability is set',
  'steps.business.title': 'Business details',
  'steps.business.gstin': 'GSTIN',
  'steps.business.bankAccount': 'Bank account number',
  'steps.capacity.title': 'Capacity',
  'steps.capacity.weeklySlots': 'Slots per week',
  'steps.documents.title': 'Documents',
  'steps.documents.hint': 'Upload each required document below.',
  'steps.agreement.title': 'Agreement',
  'steps.agreement.accepted': 'I accept the terms',
  'steps.review.title': 'Review and submit',
  'common.save': 'Save',
  'common.saved': 'Saved.',
  'common.continue': 'Continue',
  'common.needs': 'Still needed',
  'common.back': 'Back to overview',
  'common.unknownStep': 'Unknown step.',
  'common.invalidStep': 'Invalid step.',
  'common.saveFailed': 'Save failed.',
};

export type OnboardingStringKey = keyof typeof en;

const hi: Record<OnboardingStringKey, string> = {
  'steps.identity.title': 'आप कौन हैं?',
  'steps.identity.name': 'पूरा नाम',
  'steps.identity.phone': 'मोबाइल नंबर',
  'steps.credentials.title': 'आपकी योग्यताएँ',
  'steps.credentials.certifications': 'प्रमाणपत्र (अल्पविराम से अलग करें)',
  'steps.services.title': 'आप कौन-सी सेवाएँ देते हैं?',
  'steps.services.list': 'सेवा ID (अल्पविराम से अलग करें)',
  'steps.areas.title': 'आप कहाँ सेवा देते हैं?',
  'steps.areas.list': 'क्षेत्र (अल्पविराम से अलग करें)',
  'steps.availability.title': 'उपलब्धता',
  'steps.availability.set': 'मेरी उपलब्धता तय है',
  'steps.business.title': 'व्यवसाय विवरण',
  'steps.business.gstin': 'GSTIN',
  'steps.business.bankAccount': 'बैंक खाता संख्या',
  'steps.capacity.title': 'क्षमता',
  'steps.capacity.weeklySlots': 'प्रति सप्ताह स्लॉट',
  'steps.documents.title': 'दस्तावेज़',
  'steps.documents.hint': 'नीचे प्रत्येक आवश्यक दस्तावेज़ अपलोड करें।',
  'steps.agreement.title': 'सहमति',
  'steps.agreement.accepted': 'मैं शर्तों को स्वीकार करता हूँ',
  'steps.review.title': 'जाँचें और जमा करें',
  'common.save': 'सहेजें',
  'common.saved': 'सहेजा गया।',
  'common.continue': 'आगे बढ़ें',
  'common.needs': 'अभी चाहिए',
  'common.back': 'अवलोकन पर वापस जाएँ',
  'common.unknownStep': 'अज्ञात चरण।',
  'common.invalidStep': 'अमान्य चरण।',
  'common.saveFailed': 'सहेजना विफल रहा।',
};

export const onboardingStrings: Record<OnboardingLocale, Record<OnboardingStringKey, string>> = { en, hi };

export function onboardingString(locale: string, key: OnboardingStringKey): string {
  const table = (onboardingStrings as Record<string, Record<OnboardingStringKey, string>>)[locale] ?? en;
  return table[key] ?? en[key]!;
}

export function resolveOnboardingLocale(input: string | undefined): OnboardingLocale {
  return input === 'hi' ? 'hi' : 'en';
}
