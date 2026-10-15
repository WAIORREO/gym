<script>
	// @ts-nocheck
	import { getModalStore } from '@skeletonlabs/skeleton';
	import ModalClose from './ModalClose.svelte';
	import ModalHeader from './ModalHeader.svelte';
	import ModalBody from './ModalBody.svelte';
	import Modal from './Modal.svelte';
	import ModalFooter from './ModalFooter.svelte';
	import { getBearerToken, getErrorMessage, useApi } from '$lib/api';
	import PackageSelect from '$lib/components/PackageSelect.svelte';
	import { useToast } from '$lib/toast';
	import { page } from '$app/stores';
	import { t, dir } from '$lib/i18n.js';
	import moment from 'moment';

	let { user_id } = $props();

	const toast = useToast();
	const api = useApi({
		Authorization: getBearerToken()
	});
	const modalStore = getModalStore();

	let fields = $state({
		package: null,
		interval: 1,
		isLimitedSessions: true,
		total_sessions: 12,
		customExpiry: false,
		expires_at: ''
	});

	let loading = $state(false);

	const formatDateForInput = (d) => moment(d).format('YYYY-MM-DD');

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
			if (fields.package.cycle && fields.package.cycle.num_days && !fields.customExpiry) {
				const days = fields.package.cycle.num_days * (fields.interval || 1);
				fields.expires_at = formatDateForInput(moment().add(days, 'days'));
			}
		}
	});

	const onSubmit = (event) => {
		event.preventDefault();

		if (!fields.package || !fields.package.id) {
			toast.trigger({
				message: $t('sub_package', 'Please select a package'),
				background: 'variant-filled-warning'
			});
			return;
		}

		loading = true;

		const payload = {
			package_id: fields.package.id,
			interval: Number(fields.interval) || 1,
			user_id: Number(user_id || $page.params.id),
			total_sessions: fields.isLimitedSessions ? (Number(fields.total_sessions) || 0) : null,
			remaining_sessions: fields.isLimitedSessions ? (Number(fields.total_sessions) || 0) : null
		};

		if (fields.customExpiry && fields.expires_at) {
			payload.expires_at = moment(fields.expires_at).endOf('day').toISOString();
		}

		api.post(`/subscriptions`, payload)
			.then(() => {
				toast.trigger({
					message: $t('common_success', 'Successfully created'),
					background: 'variant-filled-success'
				});
				window.dispatchEvent(new CustomEvent('user_subscription'));
				modalStore.close();
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

<Modal>
	<form onsubmit={onSubmit} class="flex flex-1 flex-col" dir={$dir}>
		<ModalHeader>
			<div class="flex-1 text-lg font-bold">
				⚡ {$t('sub_title', 'Member Subscription')}
			</div>
			<ModalClose onClose={() => modalStore.close()} />
		</ModalHeader>

		<ModalBody>
			<!-- Package Selection -->
			<div class="mb-4">
				<!-- svelte-ignore a11y_label_has_associated_control -->
				<label class="label">
					<span class="font-semibold">{$t('sub_package', 'Package')} *</span>
					<PackageSelect bind:value={fields.package} />
				</label>
			</div>

			<!-- Interval -->
			<div class="mb-4">
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

			<!-- Dynamic Expiration Settings Section -->
			<div class="mb-4 rounded-xl border border-surface-400/20 bg-surface-500/5 p-4">
				<div class="mb-3 flex items-center justify-between">
					<span class="text-sm font-bold">
						📅 {$t('sub_custom_expiry', 'Custom Expiration')}
					</span>
					<label class="flex items-center gap-2 cursor-pointer text-xs">
						<input
							type="checkbox"
							class="checkbox checkbox-sm"
							bind:checked={fields.customExpiry}
						/>
						<span>{$t('sub_use_custom_expiry', 'Custom')}</span>
					</label>
				</div>

				{#if fields.customExpiry}
					<div class="space-y-3">
						<label class="label">
							<span class="text-xs text-gray-500 dark:text-gray-400">
								{$t('gate_expires_at', 'Expiration Date')}
							</span>
							<input
								class="input"
								type="date"
								bind:value={fields.expires_at}
								disabled={loading}
							/>
						</label>

						<!-- Quick extension buttons -->
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
								onclick={() => addDaysToExpiry(90)}
							>
								+3 mois
							</button>
						</div>
					</div>
				{:else}
					<div class="text-xs text-gray-500 dark:text-gray-400">
						ℹ️ {$t('sub_calculated_expiry', 'Calculated automatically from cycle duration')}
						{#if fields.expires_at}
							: <strong class="text-primary-600 dark:text-primary-400">{fields.expires_at}</strong>
						{/if}
					</div>
				{/if}
			</div>
		</ModalBody>

		<ModalFooter>
			<div class="flex w-full items-center justify-between">
				<button
					type="button"
					onclick={() => modalStore.close()}
					class="btn variant-ghost-error"
					disabled={loading}
				>
					{$t('common_cancel', 'Cancel')}
				</button>

				<button
					type="submit"
					class="btn variant-filled-primary font-bold shadow-md"
					disabled={loading}
				>
					{loading ? $t('common_loading', 'Saving...') : $t('common_save', 'Submit')}
				</button>
			</div>
		</ModalFooter>
	</form>
</Modal>
