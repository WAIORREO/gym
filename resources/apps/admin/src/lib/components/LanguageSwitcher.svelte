<script>
	// @ts-nocheck
	import { currentLang, toggleLanguage } from '$lib/i18n.js';

	export let size = 'sm'; // 'sm' | 'md'
	export let variant = 'outline'; // 'outline' | 'filled'
</script>

<button
	type="button"
	class="inline-flex items-center gap-2 rounded-full border border-blue-400/40 bg-blue-950/60 px-3 py-1.5 text-xs font-semibold text-white shadow-sm backdrop-blur transition-all hover:border-blue-400 hover:bg-blue-900/80 active:scale-95"
	onclick={() => toggleLanguage()}
	title={$currentLang === 'ar' ? 'Changer en Français' : 'التحويل إلى العربية'}
>
	{#if $currentLang === 'ar'}
		<span class="text-sm">🇫🇷</span>
		<span class="tracking-wide">Français</span>
	{:else}
		<span class="text-sm">🇩🇿</span>
		<span class="tracking-wide">العربية</span>
	{/if}
</button>
