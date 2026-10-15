<script>
	// @ts-nocheck
	import StatisticsCard from '$lib/components/StatisticsCard.svelte';
	import SubscriptionIcon from 'svelte-icons/fa/FaCreditCard.svelte';
	import ServicesIcon from 'svelte-icons/fa/FaServer.svelte';
	import PackagesIcon from 'svelte-icons/fa/FaBox.svelte';
	import MembersIcon from 'svelte-icons/fa/FaUsers.svelte';
	import { useToast } from '$lib/toast';
	import { onMount } from 'svelte';
	import LatestActivities from '$lib/components/LatestActivities.svelte';
	import LatestAttendance from '$lib/components/LatestAttendance.svelte';
	import MemberLogin from '$lib/components/MemberLogin.svelte';
	import { getDashboardStoreContext } from '$lib/stores/dashboard.store.svelte';
	import { getErrorMessage } from '$lib/api';
	import { t } from '$lib/i18n.js';

	const { loadInitialData, loading, statistics } = getDashboardStoreContext();

	let attendance;

	const toast = useToast();

	const loadItems = async () => {
		try {
			await loadInitialData();
		} catch (error) {
			toast.trigger({
				// @ts-ignore
				message: getErrorMessage(error),
				background: 'variant-filled-error'
			});
		}
	};

	const onReloadAttenance = () => attendance.reloadItems();

	onMount(() => {
		loadItems();
	});
</script>

<div class="flex flex-col gap-6 p-6">
	<div class="statistics">
		<StatisticsCard
			href="/subscriptions"
			icon={SubscriptionIcon}
			value={statistics.subscriptions}
			title={$t('nav_subscriptions', 'إجمالي الاشتراكات')}
		/>
		<StatisticsCard
			href="/services"
			icon={ServicesIcon}
			value={statistics.services}
			title={$t('nav_services', 'إجمالي الخدمات')}
		/>
		<StatisticsCard
			href="/packages"
			icon={PackagesIcon}
			value={statistics.packages}
			title={$t('nav_packages', 'إجمالي الباقات')}
		/>
		<StatisticsCard
			href="/members"
			icon={MembersIcon}
			value={statistics.members}
			title={$t('nav_members', 'إجمالي الأعضاء')}
		/>
	</div>
	<div class="flex flex-col gap-6 lg:flex-row">
		<LatestActivities />
		<div class="flex flex-1 flex-col gap-6">
			<MemberLogin load={onReloadAttenance} />
			<LatestAttendance bind:this={attendance} />
		</div>
	</div>
</div>

<style lang="postcss">
	.statistics {
		@apply grid grid-cols-1 gap-6 lg:grid-cols-4;
	}
</style>
