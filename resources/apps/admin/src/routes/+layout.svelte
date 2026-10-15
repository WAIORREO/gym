<script>
	import { createActivitiesStoreContext } from '$lib/stores/activities.store.svelte';
	import { createAttendanceStoreContext } from '$lib/stores/attendance.store.svelte';
	import { createBranchStoreContext } from '$lib/stores/branches.store.svelte';
	import { createDashboardStoreContext } from '$lib/stores/dashboard.store.svelte';
	import { createMemberStoreContext } from '$lib/stores/members.store.svelte';

	// @ts-nocheck

	import './../app.pcss';

	import { initializeStores, Toast, Modal } from '@skeletonlabs/skeleton';

	import { onMount } from 'svelte';
	import { currentLang, dir } from '$lib/i18n.js';

	initializeStores();

	createDashboardStoreContext();
	createActivitiesStoreContext();
	createAttendanceStoreContext();
	createBranchStoreContext();
	createMemberStoreContext();

	onMount(() => {
		const unsub = currentLang.subscribe((lang) => {
			if (typeof document !== 'undefined') {
				document.documentElement.setAttribute('lang', lang);
				document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
			}
		});
		return unsub;
	});

	const { children } = $props();
</script>

<Toast />
<Modal />

{@render children()}
