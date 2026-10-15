<script>
 import { currentLang, setLanguage } from '$lib/i18n.js';
</script>
<label class="inline-flex items-center gap-2 text-sm">
 <span class="sr-only">Language / اللغة / Langue</span>
 <select class="rounded-lg border border-slate-500 bg-slate-900 px-3 py-2 text-white" value={$currentLang} onchange={(event) => setLanguage(event.currentTarget.value)} aria-label="Language / اللغة / Langue">
  <option value="ar">العربية</option><option value="fr">Français</option><option value="en">English</option>
 </select>
</label>
