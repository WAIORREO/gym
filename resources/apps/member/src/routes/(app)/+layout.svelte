<script>
	// @ts-nocheck
	import { authStore } from '$lib/stores/auth.js';
	import { goto } from '$app/navigation';
	import { onMount } from 'svelte';
	import LanguageSwitcher from '$lib/components/LanguageSwitcher.svelte';
 import { currentLang, setLanguage, dir } from '$lib/i18n.js';
	import BottomNav from '$lib/components/BottomNav.svelte';

	const { children } = $props();

	onMount(() => {
 setLanguage($currentLang);
		const unsubscribe = authStore.subscribe(($auth) => {
			if (!$auth.isAuthenticated) {
				goto('/login');
			}
		});
		return unsubscribe;
	});
</script>

<div class="flex min-h-screen flex-col bg-surface-900 text-white" dir={$dir}>
 <header class="flex items-center justify-between border-b border-surface-700 px-4 py-3"><a href="/" class="font-bold tracking-wide">LaraGym</a><LanguageSwitcher /></header>
	<!-- Page content with bottom nav clearance -->
	<main class="flex-1 overflow-y-auto with-bottom-nav">
		{@render children()}
	</main>

	<BottomNav />
</div>
