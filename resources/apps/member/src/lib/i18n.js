import { writable, derived } from 'svelte/store';
import { gymCopy } from './gym-copy.js';
const saved = typeof window === 'undefined' ? 'ar' : (localStorage.getItem('laragym_lang') || 'ar');
export const currentLang = writable(['ar','fr','en'].includes(saved) ? saved : 'ar');
export const dir = derived(currentLang, lang => lang === 'ar' ? 'rtl' : 'ltr');
export const t = derived(currentLang, lang => (/** @type {string} */ key) => gymCopy[lang][key] ?? gymCopy.en[key] ?? key);
/** @param {string} lang */
export function setLanguage(lang) {
 if (!['ar','fr','en'].includes(lang)) return;
 currentLang.set(lang);
 if (typeof window !== 'undefined') {
  localStorage.setItem('laragym_lang',lang);
  document.documentElement.lang=lang;
  document.documentElement.dir=lang === 'ar' ? 'rtl' : 'ltr';
 }
}
