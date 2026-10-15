<script>
	// @ts-nocheck
 import { t } from "$lib/i18n.js";
	import { goto } from '$app/navigation';
	import { getBearerToken, useApi } from '$lib/api';
	import { Paginator } from '@skeletonlabs/skeleton';
	import { onMount } from 'svelte';

	import Status from '$lib/components/Status.svelte';
	import EditIcon from 'svelte-icons/fa/FaEdit.svelte';
	import DeleteIcon from 'svelte-icons/fa/FaTrash.svelte';

	const api = useApi({
		Authorization: getBearerToken()
	});

	let items = $state([]);
	let loading = $state(false);

	let paginationSettings = $state({
		page: 0,
		limit: 15,
		size: 0,
		amounts: [5, 10, 15, 20, 40, 60, 100]
	});

	const title = $derived($t('nav_packages'));

	const onDelete = (id) => {
		const confirm = window.confirm($t('ui_delete_confirm'));
		if (confirm) {
			items = items.filter((v) => v.id != id);
			paginationSettings.size = paginationSettings.size - 1;
			api.delete(`/packages/${id}`);
		}
	};

	const onEdit = (id) => goto(`/packages/${id}`);

	const loadItems = () => {
		if (loading) return;
		loading = true;
		api.get('/packages', {
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

<div class="p-4 lg:p-6">
	<div class="card rounded-2xl border border-surface-300/40 bg-white p-4 dark:bg-surface-800 lg:p-6">
		<header class="card-header mb-6 flex flex-wrap items-center gap-3">
			<h3 class="h3">{title}</h3>
			<div class="flex-1"></div>
			<button
				type="submit"
				class="btn variant-filled-primary text-white"
				onclick={() => goto('/packages/new')}
			>
				{$t('ui_add')}
			</button>
		</header>
		<!-- Responsive Container (recommended) -->
		<div class="table-container">
			<!-- Native Table Element -->
			<table class="table-hover table bg-white">
				<thead>
					<tr>
						<th>{$t('ui_id')}</th>
						<th>{$t('ui_name')}</th>
						<th>{$t('ui_cycle')}</th>
						<th>{$t('ui_services')}</th>
						<th>{$t('ui_price')}</th>
						<th>{$t('ui_status')}</th>
						<th>{$t('ui_action')}</th>
					</tr>
				</thead>
				<tbody>
					{#each items as item}
						<tr>
							<td>{item.id}</td>
							<td>
								<a href={`/packages/${item.id}`} class="font-bold">{item.name}</a>
							</td>
							<td>
								{item.cycle.name}
							</td>
							<td>
								{item.services.length}
							</td>
							<td>
								{parseFloat(item.amount).toFixed(2)}
							</td>
							<td>
								<Status status={item.status} />
							</td>
							<td>
								<button
									type="button"
									class="btn-icon variant-filled-primary"
									aria-label={$t("common_edit")} onclick={() => onEdit(item.id)}
								>
									<span class="h-4 w-4 text-white">
										<EditIcon />
									</span>
								</button>
								<button
									type="button"
									class="btn-icon variant-filled-error"
									aria-label={$t("common_delete")} onclick={() => onDelete(item.id)}
								>
									<span class="h-4 w-4 text-white">
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
