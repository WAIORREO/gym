<script>
	// @ts-nocheck
	import { goto } from '$app/navigation';
	import { getBearerToken, useApi } from '$lib/api';
	import { Paginator } from '@skeletonlabs/skeleton';
	import { onMount } from 'svelte';

	import Status from '$lib/components/Status.svelte';
	import moment from 'moment';
	import EditIcon from 'svelte-icons/fa/FaEdit.svelte';
	import DeleteIcon from 'svelte-icons/fa/FaTrash.svelte';
	import { t, dir } from '$lib/i18n.js';

	const api = useApi({
		Authorization: getBearerToken()
	});

	const title = $derived($t('nav_subscriptions'));

	let items = $state([]);
	let loading = $state(false);

	let paginationSettings = $state({
		page: 0,
		limit: 15,
		size: 0,
		amounts: [5, 10, 15, 20, 40, 60, 100]
	});

	const onDelete = (id) => {
		const confirm = window.confirm($t('ui_delete_confirm'));
		if (confirm) {
			items = items.filter((v) => v.id != id);
			paginationSettings.size = paginationSettings.size - 1;
			api.delete(`/subscriptions/${id}`);
		}
	};

	const onEdit = (id) => goto(`/subscriptions/${id}`);

	const loadItems = () => {
		if (loading) return;
		loading = true;
		api.get('/subscriptions', {
			params: {
				page: paginationSettings.page + 1,
				per_page: paginationSettings.limit
			}
		})
			.then((response) => {
				items = response.data.data;
				paginationSettings.size = response.data.total;
			})
			.finally(() => (loading = false));
	};

	onMount(() => loadItems());
</script>

<svelte:head>
	<title>{title}</title>
</svelte:head>

<div class="p-4 lg:p-6" dir={$dir}>
	<div class="card bg-white p-4 lg:p-6 shadow-sm">
		<header class="card-header mb-6 flex items-center justify-between">
			<h3 class="h3 font-bold flex items-center gap-2">
				<span>🎟️</span>
				<span>{$t('nav_subscriptions', 'Manage Subscriptions')}</span>
			</h3>
			<button
				type="button"
				class="btn variant-filled-primary text-white font-semibold"
				onclick={() => goto('/subscriptions/new')}
			>
				+ {$t('common_add', 'Add Item')}
			</button>
		</header>
		<!-- Responsive Container (recommended) -->
		<div class="table-container">
			<!-- Native Table Element -->
			<table class="table-hover table bg-white">
				<thead>
					<tr>
						<th>{$t('ui_id')}</th>
						<th>{$t('gate_member_name', 'Name')}</th>
						<th>{$t('sub_sessions_badge', 'Sessions')}</th>
						<th>{$t('gate_expires_at', 'Expires At')}</th>
						<th>{$t('sub_package', 'Billing Cycle')}</th>
						<th>{$t('nav_services', 'Services')}</th>
						<th>{$t('common_total', 'Amount Paid')}</th>
						<th>{$t('gate_status', 'Status')}</th>
						<th>{$t('common_actions', 'Action')}</th>
					</tr>
				</thead>
				<tbody>
					{#each items as item}
						<tr>
							<td>{item.id}</td>
							<td>
								<a href={`/subscriptions/${item.id}`} class="font-bold text-primary-600 hover:underline"
									>{item.user.name}</a
								>
							</td>
							<td>
								{#if item.total_sessions !== null && item.total_sessions !== undefined}
									<span class="badge {item.remaining_sessions <= 0 ? 'variant-filled-error' : item.remaining_sessions <= 3 ? 'variant-filled-warning' : 'variant-filled-success'} font-bold">
										🎟️ {item.remaining_sessions ?? 0} / {item.total_sessions}
									</span>
								{:else}
									<span class="badge variant-soft-primary">
										♾️ {$t('gate_unlimited_sessions', 'Unlimited')}
									</span>
								{/if}
							</td>
							<td>
								{moment(item.expires_at).format('LL')}
							</td>
							<td>
								{item.package?.cycle?.name || '-'}
							</td>
							<td>
								{item.package?.services?.length || 0}
							</td>
							<td>
								{parseFloat(item.package?.amount || 0).toFixed(2)}
							</td>
							<td>
								<Status status={item.status} />
							</td>
							<td>
								<button
									type="button"
									class="btn-icon btn-icon-sm variant-filled-primary mr-1"
									aria-label={$t("common_edit")} onclick={() => onEdit(item.id)}
									title={$t('common_edit', 'Edit')}
								>
									<span class="h-3.5 w-3.5 text-white">
										<EditIcon />
									</span>
								</button>
								<button
									type="button"
									class="btn-icon btn-icon-sm variant-filled-error"
									aria-label={$t("common_delete")} onclick={() => onDelete(item.id)}
									title={$t('common_delete', 'Delete')}
								>
									<span class="h-3.5 w-3.5 text-white">
										<DeleteIcon />
									</span>
								</button>
							</td>
						</tr>
					{/each}
				</tbody>
				<tfoot>
					<tr>
						<th colspan="3" class="bg-white">{$t('ui_results')} {paginationSettings.size}</th
						>
						<td class="bg-white"></td>
					</tr>
				</tfoot>
			</table>
			<div class="pt-6">
				<Paginator
					bind:settings={paginationSettings}
					showNumerals
					maxNumerals={1}
					on:amount={(event) => {
						paginationSettings.limit = event.detail;
						loadItems();
					}}
					on:page={(event) => {
						paginationSettings.page = event.detail;
						loadItems();
					}}
				/>
			</div>
		</div>
	</div>
</div>
