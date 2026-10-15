<script>
	// @ts-nocheck
	import FaClock from 'svelte-icons/fa/FaClock.svelte';
	import Status from '$lib/components/Status.svelte';
	import FaTrash from 'svelte-icons/fa/FaTrash.svelte';
	import FaCog from 'svelte-icons/fa/FaCog.svelte';
	import moment from 'moment';
 import { getBearerToken, getErrorMessage, useApi } from '$lib/api';
 import { useToast } from '$lib/toast';
	import { t, dir } from '$lib/i18n.js';

	let { item = {}, action = true, onEdit, onDelete, onRenew } = $props();

	// Calculate days remaining
	let daysRemaining = $derived.by(() => {
		if (!item.expires_at) return null;
		const diff = moment(item.expires_at).diff(moment(), 'days');
		return diff;
	});

	// Calculate session usage percentage
	let sessionProgress = $derived.by(() => {
		if (item.total_sessions === null || item.total_sessions === undefined || item.total_sessions <= 0) {
			return 100;
		}
		const remaining = item.remaining_sessions !== null && item.remaining_sessions !== undefined
			? item.remaining_sessions
			: (item.total_sessions - (item.sessions_used || 0));
		return Math.min(100, Math.max(0, Math.round((remaining / item.total_sessions) * 100)));
	});
 let busy = $state(false);
 const toast = useToast();
 async function transition() {
  const frozen = item.status === 'suspended';
  if (!window.confirm($t(frozen ? 'sub_resume_confirm' : 'sub_freeze_confirm'))) return;
  busy = true;
  try {
   const { data } = await useApi({Authorization:getBearerToken()}).post(`/subscriptions/${item.id}/transition`,{action:frozen ? 'resume' : 'freeze'});
   item = data;
   window.dispatchEvent(new CustomEvent('user_subscription'));
  } catch(error) { toast.trigger({message:getErrorMessage(error),background:'variant-filled-error'}); }
  finally { busy=false; }
 }
</script>

<div class="card w-full overflow-hidden bg-white shadow-md border border-surface-200 dark:border-surface-700 dark:bg-surface-800 transition hover:shadow-lg" dir={$dir}>
	<header class="card-header flex items-center justify-between border-b border-surface-200/60 dark:border-surface-700/60 pb-3">
		<div>
			<h4 class="text-base font-bold text-gray-900 dark:text-white">
				{item.package?.name || 'Abonnement / اشتراك'}
			</h4>
			{#if item.interval && item.interval > 1}
				<span class="text-xs text-gray-500">
					({item.interval}x {$t('sub_interval', 'Cycles')})
				</span>
			{/if}
		</div>
		<Status status={item.effective_status || item.status} />
	</header>

	<section class="p-4 space-y-3.5">
		<!-- Dual Expiration: 1. Sessions Badge & Progress -->
		{#if item.total_sessions !== null && item.total_sessions !== undefined}
			<div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-2.5">
				<div class="flex items-center justify-between text-xs font-semibold mb-1.5">
					<span class="text-emerald-800 dark:text-emerald-300 flex items-center gap-1">
						🎟️ {$t('gate_sessions_left', 'Remaining Sessions')}:
					</span>
					<span class="text-sm font-bold {item.remaining_sessions <= 0 ? 'text-red-600' : 'text-emerald-700 dark:text-emerald-300'}">
						{item.remaining_sessions ?? 0} / {item.total_sessions}
					</span>
				</div>
				<!-- Progress bar -->
				<div class="w-full bg-emerald-200/50 dark:bg-surface-700 rounded-full h-2 overflow-hidden">
					<div
						class="h-full rounded-full transition-all duration-500 {item.remaining_sessions <= 2 ? 'bg-red-500' : 'bg-emerald-500'}"
						style="width: {sessionProgress}%"
					></div>
				</div>
				<div class="flex justify-between items-center text-[10px] text-gray-500 mt-1">
					<span>{item.sessions_used || (item.total_sessions - (item.remaining_sessions || 0))} {$t('gate_used', 'used')}</span>
					{#if item.remaining_sessions <= 0}
						<span class="text-red-600 font-bold">⛔ {$t('sub_status_completed', 'Completed')}</span>
					{/if}
				</div>
			</div>
		{:else}
			<div class="rounded-lg bg-primary-500/10 border border-primary-500/20 p-2 text-xs font-medium text-primary-700 dark:text-primary-300 flex items-center gap-1.5">
				<span>♾️</span>
				<span>{$t('gate_unlimited_sessions', 'Unlimited Sessions')}</span>
			</div>
		{/if}

		<!-- Dual Expiration: 2. Start Date & Expiration Date with Countdown -->
		<div class="rounded-lg bg-surface-500/5 p-2.5 space-y-1.5 border border-surface-200/50">
			<div class="flex items-center justify-between text-xs text-gray-700 dark:text-gray-300">
				<span class="flex items-center gap-1.5 font-medium">
					<span class="h-3.5 w-3.5 text-gray-500"><FaClock /></span>
					<span>{$t('sub_valid_until', 'Valid Until')}:</span>
				</span>
				<strong class="text-gray-900 dark:text-white">
					{item.expires_at ? moment(item.expires_at).format('LL') : '-'}
				</strong>
			</div>

			<div class="flex items-center justify-between text-[11px] text-gray-500 pt-1 border-t border-surface-200/40">
				<span>📅 {moment(item.starts_at || item.created_at).format('LL')}</span>
				{#if daysRemaining !== null}
					<span class="{daysRemaining <= 0 ? 'text-red-600 font-bold' : daysRemaining <= 5 ? 'text-amber-600 font-semibold' : 'text-emerald-600 font-medium'}">
						{#if daysRemaining < 0}
							⌛ {Math.abs(daysRemaining)} {$t('gate_days', 'days')} {$t('gate_expired', 'ago')}
						{:else if daysRemaining === 0}
							⌛ {$t('gate_expires_at', 'Expires today')}!
						{:else}
							⏳ {daysRemaining} {$t('gate_days_left', 'days left')}
						{/if}
					</span>
				{/if}
			</div>
		</div>

		<!-- Services list -->
		{#if item.package && item.package.services && item.package.services.length > 0}
			<div class="border-t border-surface-200/50 pt-2">
				<div class="text-[11px] font-semibold text-gray-500 mb-1">
					{$t('nav_services', 'Services')}:
				</div>
				<div class="flex flex-wrap gap-1">
					{#each item.package.services as s}
						<span class="badge variant-soft text-[10px] py-0.5 px-1.5">
							{s.name}
						</span>
					{/each}
				</div>
			</div>
		{/if}

		{#if item.status === 'suspended'}
			<div class="rounded bg-amber-500/10 p-2 text-xs text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
				<span class="h-3.5 w-3.5"><FaClock /></span>
				<span>{$t('sub_status_suspended', 'Suspended')}: {moment(item.frozen_at || item.suspended_at).format('LL')}</span>
			</div>
		{/if}
	</section>

	{#if action}
		<footer class="card-footer flex flex-wrap items-center justify-between gap-2 border-t border-surface-200/60 dark:border-surface-700/60 py-2 px-4 bg-surface-500/5">
			<div class="flex items-center gap-2">
				{#if (item.effective_status || item.status) === 'active' || item.status === 'suspended'}
<button type="button" class="btn btn-sm variant-soft-primary" disabled={busy} onclick={transition}>{$t(item.status === 'suspended' ? 'sub_resume' : 'sub_freeze')}</button>
{/if}
{#if onRenew}
					<button
						type="button"
						class="btn btn-sm variant-filled-success text-white flex items-center gap-1 font-bold shadow-sm"
						onclick={() => onRenew(item)}
						title={$t('sub_renew', 'Renew')}
					>
						<span>🔄</span>
						<span>{$t('sub_renew', 'Renew')}</span>
					</button>
				{/if}

				<button
					type="button"
					class="btn btn-sm variant-soft-primary flex items-center gap-1 font-semibold"
					onclick={() => onEdit(item)}
				>
					<span class="h-3.5 w-3.5"><FaCog /></span>
					<span>{$t('common_edit', 'Modify')}</span>
				</button>
			</div>

			<button
				type="button"
				class="btn-icon btn-icon-sm variant-soft-error"
				onclick={() => onDelete(item)}
				title={$t('common_delete', 'Delete')}
			>
				<span class="h-3.5 w-3.5"><FaTrash /></span>
			</button>
		</footer>
	{/if}
</div>
