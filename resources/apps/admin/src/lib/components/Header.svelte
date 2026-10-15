<script>
	import { AppBar, getDrawerStore } from '@skeletonlabs/skeleton';
	import LanguageSwitcher from '$lib/components/LanguageSwitcher.svelte';
	import { t } from '$lib/i18n.js';

	const drawerStore = getDrawerStore();

	const drawerOpen = () => drawerStore.open({});
</script>

<AppBar background="bg-blue-950">
	<svelte:fragment slot="lead">
		<div class="flex items-center to-blue-950 gap-3">
			<button
				class="btn btn-sm mr-2 lg:hidden"
				aria-label="Toggle Drawer"
				onclick={drawerOpen}
			>
				<span>
					<svg viewBox="0 0 100 80" class="h-4 w-4 fill-white">
						<rect width="100" height="20" />
						<rect y="30" width="100" height="20" />
						<rect y="60" width="100" height="20" />
					</svg>
				</span>
			</button>
			<div class="flex items-center gap-2">
				<span class="inline-block h-3 w-3 rounded-full bg-emerald-400 animate-pulse"></span>
				<strong class="text-xl font-bold tracking-tight text-white">{$t('app_name', 'LaraGym')}</strong>
			</div>
		</div>
	</svelte:fragment>

	<svelte:fragment slot="trail">
		<div class="flex items-center gap-3">
			<a
				href="/gate"
				class="hidden sm:inline-flex items-center gap-1.5 rounded-lg bg-emerald-600/30 border border-emerald-500/40 px-3 py-1.5 text-xs font-semibold text-emerald-200 hover:bg-emerald-600/50 transition-colors"
			>
				<span>⚡</span>
				<span>{$t('nav_gate', 'بوابات الدخول')}</span>
			</a>
			<LanguageSwitcher />
		</div>
	</svelte:fragment>
</AppBar>
