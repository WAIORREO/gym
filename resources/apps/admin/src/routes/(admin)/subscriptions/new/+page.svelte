<script>
	// @ts-nocheck

	import { goto } from '$app/navigation';
	import { getBearerToken, getErrorMessage, useApi } from '$lib/api';
	import PackageSelect from '$lib/components/PackageSelect.svelte';
	import UserSelect from '$lib/components/UserSelect.svelte';
	import { prevent } from '$lib/prevent';
	import { useToast } from '$lib/toast';
	import { t, dir } from '$lib/i18n.js';
	import moment from 'moment';

	const toast = useToast();

	const api = useApi({
		Authorization: getBearerToken()
	});

	let loading = $state(false);

	const formatDateForInput = (d) => moment(d).format('YYYY-MM-DD');

	let fields = $state({
		package: null,
		user: null,
		interval: 1,
		isLimitedSessions: true,
		total_sessions: 12,
		starts_at: formatDateForInput(moment()), // Today by default!
		customExpiry: false,
		expires_at: ''
	});

	const recalculateEndDate = () => {
		const base = fields.starts_at ? moment(fields.starts_at) : moment();
		const days = (fields.package?.cycle?.num_days || 30) * (Number(fields.interval) || 1);
		fields.expires_at = formatDateForInput(base.add(days, 'days'));
	};

	const addDaysToExpiry = (days) => {
		fields.customExpiry = true;
		const base = fields.expires_at ? moment(fields.expires_at) : moment();
		fields.expires_at = formatDateForInput(base.add(days, 'days'));
	};

	const setPresetSessions = (count) => {
		fields.isLimitedSessions = true;
		fields.total_sessions = count;
	};

	$effect(() => {
		if (fields.package) {
			if (fields.package.sessions !== undefined && fields.package.sessions !== null) {
				fields.isLimitedSessions = true;
				fields.total_sessions = fields.package.sessions * (fields.interval || 1);
			}
			if (!fields.customExpiry) {
				recalculateEndDate();
			}
		}
	});

	const onStartDateChange = () => {
		if (!fields.customExpiry) {
			recalculateEndDate();
		}
	};

	const onIntervalChange = () => {
		if (!fields.customExpiry) {
			recalculateEndDate();
		}
		if (fields.package?.sessions && fields.isLimitedSessions) {
			fields.total_sessions = fields.package.sessions * (Number(fields.interval) || 1);
		}
	};

	const onSubmit = () => {
		if (!fields.package || !fields.package.id) {
			toast.trigger({
				message: $t('sub_package', 'Please select a package'),
				background: 'variant-filled-warning'
			});
			return;
		}

		if (!fields.user || !fields.user.id) {
			toast.trigger({
				message: $t('gate_member_name', 'Please select a member'),
				background: 'variant-filled-warning'
			});
			return;
		}

		loading = true;

		const payload = {
			package_id: fields.package.id,
			user_id: fields.user.id,
			interval: Number(fields.interval) || 1,
			starts_at: moment(fields.starts_at || moment()).startOf('day').toISOString(),
			total_sessions: fields.isLimitedSessions ? (Number(fields.total_sessions) || 0) : null,
			remaining_sessions: fields.isLimitedSessions ? (Number(fields.total_sessions) || 0) : null
		};

		if (fields.expires_at) {
			payload.expires_at = moment(fields.expires_at).endOf('day').toISOString();
		}

		api.post(`/subscriptions`, payload)
			.then(() => {
				goto('/subscriptions');
				toast.trigger({
					message: $t('common_success', 'Successfully created'),
					background: 'variant-filled-success'
				});
			})
			.catch((error) => {
				toast.trigger({
					message: getErrorMessage(error),
					background: 'variant-filled-error'
				});
			})
			.finally(() => (loading = false));
	};
</script>

<svelte:head>
	<title>{$t('sub_title', 'New Subscription')}</title>
</svelte:head>

<div class="lg:max-w-1200 p-4 lg:p-6" dir={$dir}>
	<div class="card bg-white p-4 lg:p-6 shadow-sm">
		<header class="card-header mb-6 flex items-center justify-between border-b pb-4">
			<h3 class="h3 font-bold flex items-center gap-2">
				<span>⚡</span>
				<span>{$t('sub_title', 'New Subscription')}</span>
			</h3>
		</header>

		<form onsubmit={prevent(onSubmit)}>
			<!-- User Select -->
			<div class="mb-4">
				<!-- svelte-ignore a11y_label_has_associated_control -->
				<label class="label">
					<span class="font-semibold">{$t('gate_member_name', 'Member')} *</span>
					<UserSelect bind:value={fields.user} />
				</label>
			</div>

			<!-- Package Select -->
			<div class="mb-4">
				<!-- svelte-ignore a11y_label_has_associated_control -->
				<label class="label">
					<span class="font-semibold">{$t('sub_package', 'Package')} *</span>
					<PackageSelect bind:value={fields.package} />
				</label>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
				<!-- Interval -->
				<div>
					<label class="label">
						<span class="font-semibold">{$t('sub_interval', 'Interval')}</span>
						<input
							class="input"
							bind:value={fields.interval}
							oninput={onIntervalChange}
							name="interval"
							type="number"
							min="1"
							required
							disabled={loading}
						/>
					</label>
				</div>

				<!-- Start Date (Starts Today Automatically) -->
				<div>
					<label class="label">
						<span class="font-semibold">📅 {$t('sub_starts_at', 'Start Date')} *</span>
						<input
							class="input font-semibold"
							type="date"
							bind:value={fields.starts_at}
							onchange={onStartDateChange}
							required
							disabled={loading}
						/>
					</label>
				</div>
			</div>

			<!-- Dynamic Session Settings Section -->
			<div class="mb-4 rounded-xl border border-primary-500/20 bg-primary-500/5 p-4">
				<div class="mb-3 flex items-center justify-between">
					<span class="text-sm font-bold text-primary-700 dark:text-primary-300">
						🎟️ {$t('sub_sessions_type', 'Session Type')}
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
					<div class="space-y-3">
						<label class="label">
							<span class="text-xs text-gray-500 dark:text-gray-400">
								{$t('sub_total_sessions', 'Total Sessions')}
							</span>
							<input
								class="input font-bold"
								type="number"
								min="1"
								max="1000"
								bind:value={fields.total_sessions}
								disabled={loading}
							/>
						</label>

						<!-- Quick Presets -->
						<div class="flex flex-wrap items-center gap-1.5 pt-1">
							<span class="text-xs text-gray-500">سريع / Rapide:</span>
							{#each [8, 10, 12, 16, 20, 24, 30] as count}
								<button
									type="button"
									class="btn btn-xs {fields.total_sessions === count ? 'variant-filled-secondary' : 'variant-soft-surface'}"
									onclick={() => setPresetSessions(count)}
								>
									{count}
								</button>
							{/each}
						</div>
					</div>
				{:else}
					<div class="text-xs text-primary-600 dark:text-primary-300">
						✨ {$t('gate_unlimited_sessions', 'Unlimited sessions throughout the subscription duration')}
					</div>
				{/if}
			</div>

			<!-- Dynamic Expiration Settings Section (Auto-generated from starts_at + cycle) -->
			<div class="mb-4 rounded-xl border border-surface-400/20 bg-surface-500/5 p-4">
				<div class="mb-3 flex items-center justify-between">
					<span class="text-sm font-bold">
						📅 {$t('gate_expires_at', 'Expiration Date')}
					</span>
					<label class="flex items-center gap-2 cursor-pointer text-xs">
						<input
							type="checkbox"
							class="checkbox checkbox-sm"
							bind:checked={fields.customExpiry}
							onchange={() => { if (!fields.customExpiry) recalculateEndDate(); }}
						/>
						<span>{$t('sub_use_custom_expiry', 'Custom')}</span>
					</label>
				</div>

				<div class="space-y-3">
					<input
						class="input font-semibold {fields.customExpiry ? 'border-primary-500' : ''}"
						type="date"
						bind:value={fields.expires_at}
						disabled={loading}
					/>

					{#if fields.customExpiry}
						<div class="flex flex-wrap items-center gap-1.5 pt-1">
							<span class="text-xs text-gray-500">تمديد / Prolonger:</span>
							<button
								type="button"
								class="btn btn-xs variant-soft"
								onclick={() => addDaysToExpiry(7)}
							>
								+7 j
							</button>
							<button
								type="button"
								class="btn btn-xs variant-soft"
								onclick={() => addDaysToExpiry(15)}
							>
								+15 j
							</button>
							<button
								type="button"
								class="btn btn-xs variant-soft"
								onclick={() => addDaysToExpiry(30)}
							>
								+30 j
							</button>
							<button
								type="button"
								class="btn btn-xs variant-soft"
								onclick={recalculateEndDate}
							>
								🔄 حساب تلقائي / Auto
							</button>
						</div>
					{:else}
						<div class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
							<span>✓</span>
							<span>{$t('sub_auto_calculated', 'Calculated automatically from start date and duration')}</span>
						</div>
					{/if}
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
