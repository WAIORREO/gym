<script>
	// @ts-nocheck

	import { goto } from '$app/navigation';
	import { page } from '$app/stores';
	import { getBearerToken, getErrorMessage, useApi } from '$lib/api';
	import PackageSelect from '$lib/components/PackageSelect.svelte';
	import UserSelect from '$lib/components/UserSelect.svelte';
	import { prevent } from '$lib/prevent';
	import { useToast } from '$lib/toast';
	import { onMount } from 'svelte';
	import { t, dir } from '$lib/i18n.js';
	import moment from 'moment';

	const toast = useToast();

	const api = useApi({
		Authorization: getBearerToken()
	});

	let loading = $state(false);

	const formatDateForInput = (d) => (d ? moment(d).format('YYYY-MM-DD') : '');

	let fields = $state({
		package: null,
		user: null,
		interval: 1,
		status: 'active',
		isLimitedSessions: true,
		total_sessions: 12,
		remaining_sessions: 12,
		sessions_used: 0,
		starts_at: '',
		expires_at: ''
	});

	const adjustRemainingSessions = (delta) => {
		fields.isLimitedSessions = true;
		const current = Number(fields.remaining_sessions) || 0;
		const updated = Math.max(0, current + delta);
		fields.remaining_sessions = updated;
		if (updated > Number(fields.total_sessions)) {
			fields.total_sessions = updated;
		}
		if (updated > 0 && fields.status === 'completed') {
			fields.status = 'active';
		}
	};

	const addDaysToExpiry = (days) => {
		const base = fields.expires_at ? moment(fields.expires_at) : moment();
		fields.expires_at = formatDateForInput(base.add(days, 'days'));
		if (fields.status === 'expired') {
			fields.status = 'active';
		}
	};

	// Renewal: Extends end date, resets sessions, activates status
	const triggerRenewalCalculation = () => {
		const now = moment();
		const currentEnd = fields.expires_at ? moment(fields.expires_at) : now;
		const startBase = currentEnd.isAfter(now) ? currentEnd : now;
		const cycleDays = fields.package?.cycle?.num_days || 30;
		const days = cycleDays * (Number(fields.interval) || 1);
		
		fields.expires_at = formatDateForInput(startBase.clone().add(days, 'days'));
		fields.remaining_sessions = fields.total_sessions;
		fields.sessions_used = 0;
		fields.status = 'active';

		toast.trigger({
			message: `✨ ${fields.expires_at} (${days} j) تم تمديد التاريخ وتعبئة الحصص`,
			background: 'variant-filled-success'
		});
	};

	const onDirectRenewApi = () => {
		loading = true;
		api.post(`/subscriptions/${$page.params.id}/renew`, {
			package_id: fields.package?.id,
			interval: Number(fields.interval) || 1,
			total_sessions: fields.isLimitedSessions ? Number(fields.total_sessions) : null
		})
			.then(() => {
				toast.trigger({
					message: $t('sub_renew_success', 'Subscription renewed successfully!'),
					background: 'variant-filled-success'
				});
				loadItem();
			})
			.catch((error) => {
				toast.trigger({
					message: getErrorMessage(error),
					background: 'variant-filled-error'
				});
			})
			.finally(() => (loading = false));
	};

	const onSubmit = () => {
		loading = true;

		const payload = {
			package_id: fields.package?.id,
			user_id: fields.user?.id,
			status: fields.status,
			interval: Number(fields.interval) || 1,
			total_sessions: fields.isLimitedSessions ? (Number(fields.total_sessions) || 0) : null,
			remaining_sessions: fields.isLimitedSessions ? (Number(fields.remaining_sessions) || 0) : null,
			sessions_used: Number(fields.sessions_used) || 0
		};

		if (fields.starts_at) {
			payload.starts_at = moment(fields.starts_at).startOf('day').toISOString();
		}

		if (fields.expires_at) {
			payload.expires_at = moment(fields.expires_at).endOf('day').toISOString();
		}

		api.put(`/subscriptions/${$page.params.id}`, payload)
			.then(() => {
				toast.trigger({
					message: $t('common_success', 'Successfully updated'),
					background: 'variant-filled-success'
				});
				goto('/subscriptions');
			})
			.catch((error) => {
				toast.trigger({
					message: getErrorMessage(error),
					background: 'variant-filled-error'
				});
			})
			.finally(() => (loading = false));
	};

	const loadItem = () => {
		loading = true;
		api.get(`/subscriptions/${$page.params.id}`)
			.then((response) => {
				const value = response.data;
				fields = {
					package: value.package,
					user: value.user,
					interval: value.interval || 1,
					status: value.status || 'active',
					isLimitedSessions: value.total_sessions !== null && value.total_sessions !== undefined,
					total_sessions: value.total_sessions !== null ? value.total_sessions : 12,
					remaining_sessions: value.remaining_sessions !== null ? value.remaining_sessions : 12,
					sessions_used: value.sessions_used || 0,
					starts_at: formatDateForInput(value.starts_at || value.created_at || moment()),
					expires_at: formatDateForInput(value.expires_at)
				};
			})
			.catch((error) => {
				toast.trigger({
					message: getErrorMessage(error),
					background: 'variant-filled-error'
				});
				goto('/subscriptions');
			})
			.finally(() => (loading = false));
	};

	onMount(() => {
		loadItem();
	});
</script>

<svelte:head>
	<title>{$t('sub_title', 'Edit Subscription')} #{$page.params.id}</title>
</svelte:head>

<div class="lg:max-w-1200 p-4 lg:p-6" dir={$dir}>
	<div class="card bg-white p-4 lg:p-6 shadow-sm">
		<header class="card-header mb-6 flex items-center justify-between border-b pb-4">
			<h3 class="h3 font-bold flex items-center gap-2">
				<span>✏️</span>
				<span>{$t('sub_title', 'Edit Subscription')} #{$page.params.id}</span>
			</h3>
		</header>

		<!-- Instant Renewal Action Banner -->
		<div class="mb-6 rounded-xl bg-gradient-to-r from-primary-500/15 via-emerald-500/15 to-primary-500/15 border border-primary-500/30 p-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
			<div>
				<div class="font-bold text-base text-primary-800 dark:text-primary-200 flex items-center gap-1.5">
					<span>🔄</span>
					<span>{$t('sub_renew', 'Renew Subscription')}</span>
				</div>
				<div class="text-xs text-gray-600 dark:text-gray-300">
					تجديد تاريخ النهاية آلياً بحسب دورة الباقة وإعادة تعبئة الحصص
				</div>
			</div>
			<div class="flex gap-2">
				<button
					type="button"
					class="btn btn-sm variant-soft-secondary font-semibold"
					onclick={triggerRenewalCalculation}
				>
					⚡ حساب التاريخ الجديد
				</button>
				<button
					type="button"
					class="btn btn-sm variant-filled-success font-bold text-white shadow"
					onclick={onDirectRenewApi}
					disabled={loading}
				>
					✓ تجديد فوري وحفظ
				</button>
			</div>
		</div>

		<form onsubmit={prevent(onSubmit)}>
			<!-- User Select -->
			<div class="mb-4">
				<!-- svelte-ignore a11y_label_has_associated_control -->
				<label class="label">
					<span class="font-semibold">{$t('gate_member_name', 'Member')}</span>
					<UserSelect bind:value={fields.user} />
				</label>
			</div>

			<!-- Package Select -->
			<div class="mb-4">
				<!-- svelte-ignore a11y_label_has_associated_control -->
				<label class="label">
					<span class="font-semibold">{$t('sub_package', 'Package')}</span>
					<PackageSelect bind:value={fields.package} />
				</label>
			</div>

			<div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
				<!-- Status -->
				<div>
					<label class="label">
						<span class="font-semibold">{$t('sub_status', 'Status')}</span>
						<select
							class="select"
							bind:value={fields.status}
							required
							disabled={loading}
						>
							<option value="active">✅ {$t('sub_status_active', 'Active')}</option>
							<option value="completed">🏁 {$t('sub_status_completed', 'Completed')}</option>
							<option value="suspended">⏸️ {$t('sub_status_suspended', 'Suspended')}</option>
							<option value="expired">⌛ {$t('sub_status_expired', 'Expired')}</option>
							<option value="cancelled">❌ {$t('sub_status_cancelled', 'Cancelled')}</option>
							<option value="inactive">⭕ {$t('sub_status_inactive', 'Inactive')}</option>
						</select>
					</label>
				</div>

				<!-- Interval -->
				<div>
					<label class="label">
						<span class="font-semibold">{$t('sub_interval', 'Interval')}</span>
						<input
							class="input"
							bind:value={fields.interval}
							name="interval"
							type="number"
							min="1"
							required
							disabled={loading}
						/>
					</label>
				</div>
			</div>

			<!-- Dynamic Session Settings & Compensation -->
			<div class="mb-4 rounded-xl border border-primary-500/20 bg-primary-500/5 p-4">
				<div class="mb-3 flex items-center justify-between">
					<span class="text-sm font-bold text-primary-700 dark:text-primary-300">
						🎟️ {$t('sub_sessions_type', 'Session Type & Adjustments')}
					</span>
					<div class="flex gap-2">
						<button
							type="button"
							class="btn btn-sm {fields.isLimitedSessions ? 'variant-filled-primary' : 'variant-soft'}"
							onclick={() => (fields.isLimitedSessions = true)}
						>
							{$t('sub_sessions_limited', 'Limited')}
						</button>
						<button
							type="button"
							class="btn btn-sm {!fields.isLimitedSessions ? 'variant-filled-primary' : 'variant-soft'}"
							onclick={() => (fields.isLimitedSessions = false)}
						>
							{$t('sub_sessions_unlimited', 'Unlimited ♾️')}
						</button>
					</div>
				</div>

				{#if fields.isLimitedSessions}
					<div class="grid grid-cols-2 gap-3 mb-3">
						<label class="label">
							<span class="text-xs text-gray-500 dark:text-gray-400">
								{$t('sub_remaining_sessions', 'Remaining Sessions')}
							</span>
							<input
								class="input font-bold text-lg text-emerald-600 dark:text-emerald-400"
								type="number"
								min="0"
								max="1000"
								bind:value={fields.remaining_sessions}
								disabled={loading}
							/>
						</label>

						<label class="label">
							<span class="text-xs text-gray-500 dark:text-gray-400">
								{$t('sub_total_sessions', 'Total Sessions')}
							</span>
							<input
								class="input font-bold"
								type="number"
								min="0"
								max="1000"
								bind:value={fields.total_sessions}
								disabled={loading}
							/>
						</label>
					</div>

					<!-- Quick Compensation Buttons -->
					<div class="flex flex-wrap items-center gap-1.5 pt-1">
						<span class="text-xs text-gray-500 font-semibold">
							{$t('sub_add_sessions', 'Adjust / Compensate')}:
						</span>
						<button
							type="button"
							class="btn btn-xs variant-filled-success text-white"
							onclick={() => adjustRemainingSessions(1)}
						>
							+1 حصة / séance
						</button>
						<button
							type="button"
							class="btn btn-xs variant-filled-success text-white"
							onclick={() => adjustRemainingSessions(2)}
						>
							+2
						</button>
						<button
							type="button"
							class="btn btn-xs variant-filled-success text-white"
							onclick={() => adjustRemainingSessions(5)}
						>
							+5
						</button>
						<button
							type="button"
							class="btn btn-xs variant-soft-error"
							onclick={() => adjustRemainingSessions(-1)}
						>
							-1
						</button>
						<button
							type="button"
							class="btn btn-xs variant-soft"
							onclick={() => { fields.remaining_sessions = fields.total_sessions; }}
						>
							إعادة ضبط / Reset
						</button>
					</div>
				{:else}
					<div class="text-xs text-primary-600 dark:text-primary-300">
						✨ {$t('gate_unlimited_sessions', 'Unlimited sessions throughout the subscription duration')}
					</div>
				{/if}
			</div>

			<!-- Dates Section (Start Date & Expiration Date) -->
			<div class="mb-4 rounded-xl border border-surface-400/20 bg-surface-500/5 p-4">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
					<div>
						<label class="label">
							<span class="text-xs font-semibold text-gray-600 dark:text-gray-300">
								📅 {$t('sub_starts_at', 'Start Date')}
							</span>
							<input
								class="input font-medium"
								type="date"
								bind:value={fields.starts_at}
								disabled={loading}
							/>
						</label>
					</div>

					<div>
						<label class="label">
							<span class="text-xs font-semibold text-gray-600 dark:text-gray-300">
								🏁 {$t('gate_expires_at', 'Expiration Date')}
							</span>
							<input
								class="input font-semibold"
								type="date"
								bind:value={fields.expires_at}
								disabled={loading}
							/>
						</label>
					</div>
				</div>

				<div class="flex flex-wrap items-center gap-1.5 pt-1">
					<span class="text-xs text-gray-500 font-semibold">
						{$t('sub_quick_add_days', 'Extend validity')}:
					</span>
					<button
						type="button"
						class="btn btn-xs variant-filled-primary"
						onclick={() => addDaysToExpiry(7)}
					>
						{$t('sub_days_plus_7', '+7 days')}
					</button>
					<button
						type="button"
						class="btn btn-xs variant-filled-primary"
						onclick={() => addDaysToExpiry(15)}
					>
						{$t('sub_days_plus_15', '+15 days')}
					</button>
					<button
						type="button"
						class="btn btn-xs variant-filled-primary"
						onclick={() => addDaysToExpiry(30)}
					>
						{$t('sub_days_plus_30', '+30 days')}
					</button>
					<button
						type="button"
						class="btn btn-xs variant-filled-secondary"
						onclick={triggerRenewalCalculation}
					>
						🔄 احتساب التجديد التلقائي
					</button>
				</div>
			</div>

			<div class="flex items-center justify-between border-t pt-4">
				<button
					type="button"
					onclick={() => goto('/subscriptions')}
					class="btn variant-ghost-error"
					disabled={loading}
				>
					{$t('common_cancel', 'Cancel')}
				</button>
				<button
					type="submit"
					class="btn variant-filled-primary text-white font-bold"
					disabled={loading}
				>
					{loading ? $t('common_loading', 'Saving...') : $t('common_save', 'Submit')}
				</button>
			</div>
		</form>
	</div>
</div>
