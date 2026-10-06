<script>
	// @ts-nocheck
	import { onMount, onDestroy } from 'svelte';
	import { getBearerToken, useApi, getErrorMessage } from '$lib/api';
	import { getAvatarUrl } from '$lib/avatar';
	import { playAccessGrantedSound, playAccessDeniedSound } from '$lib/audio';
	import moment from 'moment';

	import FaCheck from 'svelte-icons/fa/FaCheck.svelte';
	import FaTimes from 'svelte-icons/fa/FaTimes.svelte';
	import FaIdCard from 'svelte-icons/fa/FaIdCard.svelte';
	import FaVolumeUp from 'svelte-icons/fa/FaVolumeUp.svelte';
	import FaVolumeMute from 'svelte-icons/fa/FaVolumeMute.svelte';
	import FaExpand from 'svelte-icons/fa/FaExpand.svelte';
	import FaCompress from 'svelte-icons/fa/FaCompress.svelte';
	import FaSyncAlt from 'svelte-icons/fa/FaSyncAlt.svelte';
	import FaSearch from 'svelte-icons/fa/FaSearch.svelte';
	import FaClock from 'svelte-icons/fa/FaClock.svelte';
	import FaBuilding from 'svelte-icons/fa/FaBuilding.svelte';
	import FaUserCheck from 'svelte-icons/fa/FaUserCheck.svelte';

	const api = useApi({
		Authorization: getBearerToken()
	});

	// State
	let status = $state('idle'); // 'idle' | 'success' | 'error' | 'loading'
	let memberData = $state(null);
	let errorData = $state(null);
	let soundEnabled = $state(true);
	let isFullscreen = $state(false);

	// Time & Date
	let currentTime = $state('');
	let currentDate = $state('');
	let clockInterval = null;

	// Branches
	let branches = $state([]);
	let selectedBranchId = $state(null);

	// Today's stats & recent checkins
	let recentCheckins = $state([]);
	let todayCount = $state(0);
	let loadingRecent = $state(false);

	// Inputs
	let manualInput = $state('');
	let rfidBuffer = '';
	let lastKeyTime = 0;
	let countdownSeconds = $state(0);
	let countdownInterval = null;
	let hiddenInputRef = null;

	// Update Clock
	const updateClock = () => {
		const now = moment();
		currentTime = now.format('HH:mm:ss');
		currentDate = now.format('dddd, D MMMM YYYY');
	};

	// Reset to idle
	const resetToIdle = () => {
		if (countdownInterval) clearInterval(countdownInterval);
		status = 'idle';
		memberData = null;
		errorData = null;
		countdownSeconds = 0;
		if (hiddenInputRef) {
			hiddenInputRef.focus();
		}
	};

	// Start auto-reset countdown
	const startResetCountdown = (seconds = 5) => {
		if (countdownInterval) clearInterval(countdownInterval);
		countdownSeconds = seconds;
		countdownInterval = setInterval(() => {
			countdownSeconds -= 1;
			if (countdownSeconds <= 0) {
				clearInterval(countdownInterval);
				resetToIdle();
			}
		}, 1000);
	};

	// Process RFID / Card check-in
	const processCheckIn = async (cardCode) => {
		if (!cardCode || status === 'loading') return;
		cardCode = cardCode.trim();
		if (!cardCode) return;

		status = 'loading';
		if (countdownInterval) clearInterval(countdownInterval);

		try {
			const response = await api.post('/gate/check-in', {
				rfid_card_id: cardCode,
				branch_id: selectedBranchId
			});

			memberData = response.data;
			status = 'success';

			if (soundEnabled) {
				playAccessGrantedSound();
			}

			// Refresh recent feed
			loadRecentCheckins();
			loadStats();

			// Auto reset after 4.5 seconds
			startResetCountdown(5);
		} catch (error) {
			const res = error.response ? error.response.data : null;
			errorData = {
				statusCode: res?.status_code || 'ERROR',
				message: res?.message || getErrorMessage(error) || 'حدث خطأ أثناء معالجة البطاقة',
				messageEn: res?.message_en || 'Error processing card',
				scannedCode: cardCode,
				member: res?.member || null,
				lastSubscription: res?.last_subscription || null
			};

			status = 'error';

			if (soundEnabled) {
				playAccessDeniedSound();
			}

			startResetCountdown(5);
		}
	};

	// Handle manual submit
	const handleManualSubmit = (e) => {
		e.preventDefault();
		if (!manualInput) return;
		processCheckIn(manualInput);
		manualInput = '';
	};

	// Global Keyboard listener for USB RFID Readers (Keyboard Emulation / HID)
	const handleKeyDown = (e) => {
		// Don't capture when typing in the manual search input box
		if (e.target && e.target.id === 'manual-search-input') {
			return;
		}

		const now = Date.now();
		const char = e.key;

		// USB RFID readers type very fast (under 60ms between keys)
		if (now - lastKeyTime > 250) {
			rfidBuffer = '';
		}
		lastKeyTime = now;

		if (e.key === 'Enter') {
			if (rfidBuffer.length >= 3) {
				const scanned = rfidBuffer;
				rfidBuffer = '';
				processCheckIn(scanned);
			}
		} else if (char.length === 1) {
			rfidBuffer += char;
		}
	};

	// Load branches
	const loadBranches = async () => {
		try {
			const res = await api.get('/branches');
			branches = res.data.data || res.data || [];
			if (branches.length && !selectedBranchId) {
				selectedBranchId = branches[0].id;
			}
		} catch (e) {
			console.error('Failed to load branches', e);
		}
	};

	// Load recent check-ins
	const loadRecentCheckins = async () => {
		loadingRecent = true;
		try {
			const res = await api.get('/gate/recent', {
				params: { branch_id: selectedBranchId, limit: 12 }
			});
			recentCheckins = res.data.data || [];
		} catch (e) {
			console.error('Failed to load recent checkins', e);
		} finally {
			loadingRecent = false;
		}
	};

	// Load gate statistics
	const loadStats = async () => {
		try {
			const res = await api.get('/gate/stats');
			todayCount = res.data.today_checkins || 0;
		} catch (e) {
			console.error('Failed to load stats', e);
		}
	};

	// Toggle Fullscreen (Kiosk Mode)
	const toggleFullscreen = () => {
		if (!document.fullscreenElement) {
			document.documentElement.requestFullscreen?.().catch(() => {});
			isFullscreen = true;
		} else {
			document.exitFullscreen?.().catch(() => {});
			isFullscreen = false;
		}
	};

	onMount(() => {
		updateClock();
		clockInterval = setInterval(updateClock, 1000);
		window.addEventListener('keydown', handleKeyDown);
		loadBranches();
		loadRecentCheckins();
		loadStats();

		// Auto refresh feed every 30 seconds
		const feedInterval = setInterval(() => {
			loadRecentCheckins();
			loadStats();
		}, 30000);

		return () => {
			if (clockInterval) clearInterval(clockInterval);
			if (countdownInterval) clearInterval(countdownInterval);
			clearInterval(feedInterval);
			window.removeEventListener('keydown', handleKeyDown);
		};
	});
</script>

<svelte:head>
	<title>شاشة بوابة الدخول RFID — LaraGym Gate</title>
</svelte:head>

<!-- Hidden auto-focus input for RFID scanner -->
<input
	bind:this={hiddenInputRef}
	class="absolute -left-[9999px] opacity-0"
	type="text"
	aria-hidden="true"
/>

<div
	class="gate-container flex min-h-[calc(100vh-65px)] flex-col bg-slate-950 text-slate-100 selection:bg-primary-500"
	dir="rtl"
>
	<!-- Top Bar / Kiosk Header -->
	<header
		class="flex flex-wrap items-center justify-between border-b border-slate-800 bg-slate-900/80 px-6 py-3.5 backdrop-blur-md"
	>
		<!-- Left: Gate Status & Branch -->
		<div class="flex items-center gap-4">
			<div class="flex items-center gap-2">
				<span class="relative flex h-3.5 w-3.5">
					<span
						class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
					></span>
					<span class="relative inline-flex h-3.5 w-3.5 rounded-full bg-emerald-500"></span>
				</span>
				<span class="text-sm font-semibold tracking-wide text-emerald-400">بوابة الدخول متصلة</span>
			</div>

			<div class="h-4 w-px bg-slate-700"></div>

			<!-- Branch Selector -->
			<div class="flex items-center gap-2 text-sm text-slate-300">
				<div class="h-4 w-4 text-primary-400">
					<FaBuilding />
				</div>
				<select
					bind:value={selectedBranchId}
					onchange={() => {
						loadRecentCheckins();
					}}
					class="select select-sm rounded-lg border-slate-700 bg-slate-800 py-1 text-sm font-medium text-slate-100 hover:border-primary-500 focus:border-primary-500 focus:ring-0"
				>
					{#each branches as branch}
						<option value={branch.id}>{branch.name}</option>
					{/each}
				</select>
			</div>
		</div>

		<!-- Center: Live Clock & Date -->
		<div class="flex flex-col items-center justify-center">
			<div class="font-mono text-2xl font-black tracking-widest text-slate-100">
				{currentTime}
			</div>
			<div class="text-xs font-medium text-slate-400">
				{currentDate}
			</div>
		</div>

		<!-- Right: Tools, Mute, Fullscreen, Counter -->
		<div class="flex items-center gap-3">
			<div
				class="flex items-center gap-2 rounded-full border border-slate-700/80 bg-slate-800/80 px-3.5 py-1 text-sm font-semibold text-slate-200"
			>
				<div class="h-3.5 w-3.5 text-emerald-400">
					<FaUserCheck />
				</div>
				<span>دخول اليوم:</span>
				<span class="font-mono font-bold text-emerald-400">{todayCount}</span>
			</div>

			<!-- Sound Button -->
			<button
				type="button"
				class="btn-icon btn-icon-sm rounded-lg border border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700"
				title={soundEnabled ? 'كتم الصوت' : 'تفعيل الصوت'}
				onclick={() => (soundEnabled = !soundEnabled)}
			>
				<div class="h-4 w-4">
					{#if soundEnabled}
						<FaVolumeUp />
					{:else}
						<FaVolumeMute />
					{/if}
				</div>
			</button>

			<!-- Fullscreen Button -->
			<button
				type="button"
				class="btn-icon btn-icon-sm rounded-lg border border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700"
				title="وضع ملء الشاشة (Kiosk)"
				onclick={toggleFullscreen}
			>
				<div class="h-4 w-4">
					{#if isFullscreen}
						<FaCompress />
					{:else}
						<FaExpand />
					{/if}
				</div>
			</button>
		</div>
	</header>

	<!-- Main Gate Display Grid -->
	<div class="grid flex-1 grid-cols-1 lg:grid-cols-12">
		<!-- Main Center Stage: RFID Interactive Kiosk -->
		<div
			class="relative flex flex-col items-center justify-center p-6 lg:col-span-8 lg:p-12"
		>
			<!-- STATE 1: IDLE / WAITING FOR RFID TAP -->
			{#if status === 'idle'}
				<div class="flex w-full max-w-xl flex-col items-center text-center">
					<!-- Animated Contactless Wave Pulse -->
					<div class="relative my-8 flex items-center justify-center">
						<div
							class="absolute h-56 w-56 animate-ping rounded-full bg-primary-500/10 [animation-duration:3s]"
						></div>
						<div
							class="absolute h-44 w-44 animate-pulse rounded-full bg-primary-500/20 [animation-duration:2s]"
						></div>
						<div
							class="relative flex h-32 w-32 items-center justify-center rounded-full border-2 border-primary-400/40 bg-gradient-to-br from-primary-600 to-indigo-700 shadow-2xl shadow-primary-500/40"
						>
							<div class="h-16 w-16 text-white drop-shadow">
								<FaIdCard />
							</div>
						</div>
					</div>

					<h1 class="text-3xl font-black tracking-tight text-white lg:text-4xl">
						مرر بطاقة العضوية أو السوار الذكي
					</h1>
					<p class="mt-2 text-base font-medium text-slate-400">
						Please tap your RFID card, wristband or key fob to unlock gate
					</p>

					<!-- Manual Search Form (for receptionist or forgotten card) -->
					<form
						onsubmit={handleManualSubmit}
						class="mt-10 flex w-full max-w-md items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900/90 p-2 shadow-xl"
					>
						<div class="pr-3 text-slate-400">
							<div class="h-4 w-4">
								<FaSearch />
							</div>
						</div>
						<input
							id="manual-search-input"
							type="text"
							bind:value={manualInput}
							placeholder="أو أدخل رقم البطاقة / رقم حساب العضو..."
							class="w-full bg-transparent px-2 py-1 text-sm text-white placeholder-slate-500 focus:outline-none"
						/>
						<button
							type="submit"
							class="btn btn-sm rounded-xl bg-primary-600 font-semibold text-white shadow-md hover:bg-primary-500"
						>
							تحقق
						</button>
					</form>

					<!-- Quick Test Helper Pills -->
					<div class="mt-8 flex flex-wrap items-center justify-center gap-2">
						<span class="text-xs text-slate-500">تجربة سريعة:</span>
						<button
							type="button"
							class="badge border border-emerald-500/30 bg-emerald-500/10 text-xs font-mono text-emerald-300 hover:bg-emerald-500/20"
							onclick={() => processCheckIn('RFID123456')}
						>
							بطاقة صالحة (RFID123456)
						</button>
						<button
							type="button"
							class="badge border border-rose-500/30 bg-rose-500/10 text-xs font-mono text-rose-300 hover:bg-rose-500/20"
							onclick={() => processCheckIn('UNKNOWN_999')}
						>
							بطاقة غير مسجلة
						</button>
					</div>
				</div>

			<!-- STATE 2: LOADING -->
			{:else if status === 'loading'}
				<div class="flex flex-col items-center justify-center py-12">
					<div class="h-16 w-16 animate-spin text-primary-400">
						<FaSyncAlt />
					</div>
					<h2 class="mt-6 text-2xl font-bold text-white">جاري التحقق من صلاحية العضوية...</h2>
				</div>

			<!-- STATE 3: ACCESS GRANTED (SUCCESS) -->
			{:else if status === 'success' && memberData}
				<div
					class="relative flex w-full max-w-2xl flex-col items-center rounded-3xl border-2 border-emerald-500/50 bg-gradient-to-b from-emerald-950/40 via-slate-900 to-slate-900 p-8 text-center shadow-2xl shadow-emerald-500/20"
				>
					<!-- Top Success Badge -->
					<div
						class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-500/40 bg-emerald-500/20 px-5 py-2 font-bold text-emerald-300"
					>
						<div class="h-5 w-5">
							<FaCheck />
						</div>
						<span class="text-lg">تم السماح بالدخول — البوابة مفتوحة</span>
					</div>

					<!-- Member Avatar -->
					<div class="relative mb-4">
						<div
							class="h-28 w-28 overflow-hidden rounded-full border-4 border-emerald-400 bg-slate-800 shadow-xl"
						>
							{#if memberData.member.avatar}
								<img
									src={getAvatarUrl(memberData.member.avatar)}
									alt={memberData.member.name}
									class="h-full w-full object-cover"
								/>
							{:else}
								<div
									class="flex h-full w-full items-center justify-center bg-emerald-800 text-3xl font-black text-white"
								>
									{memberData.member.name.charAt(0)}
								</div>
							{/if}
						</div>
						<div
							class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-white shadow"
						>
							<div class="h-4 w-4">
								<FaCheck />
							</div>
						</div>
					</div>

					<!-- Member Name & Account -->
					<h2 class="text-3xl font-black text-white lg:text-4xl">
						{memberData.member.name}
					</h2>
					<div class="mt-1 flex items-center gap-3 font-mono text-sm text-slate-400">
						<span>رقم العضو: {memberData.member.account_number}</span>
						<span>•</span>
						<span class="text-emerald-400">بطاقة: {memberData.member.rfid_card_id || 'RFID'}</span>
					</div>

					<!-- Subscription Info Cards -->
					<div class="mt-6 grid w-full grid-cols-1 gap-4 sm:grid-cols-3">
						<div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-4">
							<div class="text-xs text-slate-400">الباقة الحالية</div>
							<div class="mt-1 text-base font-bold text-white">
								{memberData.subscription.package_name}
							</div>
						</div>
						<div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-4">
							<div class="text-xs text-slate-400">تاريخ الانتهاء</div>
							<div class="mt-1 font-mono text-base font-bold text-white">
								{memberData.subscription.expires_at}
							</div>
						</div>
						<div
							class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-300"
						>
							<div class="text-xs text-emerald-400">الأيام المتبقية</div>
							<div class="mt-1 font-mono text-xl font-black">
								{memberData.subscription.days_left !== null
									? `${memberData.subscription.days_left} يوم`
									: 'مفتوح'}
							</div>
						</div>
					</div>

					<!-- Message -->
					<div class="mt-6 text-base font-semibold text-emerald-400">
						{memberData.message}
					</div>

					<!-- Auto Reset Bar -->
					<div class="mt-8 flex w-full items-center justify-between text-xs text-slate-500">
						<span>إعادة تعيين الشاشة خلال {countdownSeconds} ثوانٍ...</span>
						<button
							type="button"
							class="text-xs text-slate-400 underline hover:text-white"
							onclick={resetToIdle}
						>
							عودة الآن
						</button>
					</div>
				</div>

			<!-- STATE 4: ACCESS DENIED (ERROR) -->
			{:else if status === 'error' && errorData}
				<div
					class="relative flex w-full max-w-xl flex-col items-center rounded-3xl border-2 border-rose-500/60 bg-gradient-to-b from-rose-950/40 via-slate-900 to-slate-900 p-8 text-center shadow-2xl shadow-rose-500/20"
				>
					<!-- Denied Icon -->
					<div
						class="mb-6 flex h-20 w-20 items-center justify-center rounded-full border-2 border-rose-500 bg-rose-500/20 text-rose-400 shadow-lg shadow-rose-500/30"
					>
						<div class="h-10 w-10">
							<FaTimes />
						</div>
					</div>

					<h2 class="text-3xl font-black text-rose-400">تم رفض الدخول — البوابة مغلقة</h2>

					<!-- Reason Banner -->
					<div
						class="mt-4 w-full rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-center font-bold text-rose-200"
					>
						{errorData.message}
					</div>

					<!-- Member info if recognized -->
					{#if errorData.member}
						<div class="mt-6 flex w-full items-center gap-4 rounded-2xl border border-slate-800 bg-slate-900 p-4 text-right">
							<div
								class="h-14 w-14 overflow-hidden rounded-full border-2 border-slate-700 bg-slate-800"
							>
								{#if errorData.member.avatar}
									<img
										src={getAvatarUrl(errorData.member.avatar)}
										alt={errorData.member.name}
										class="h-full w-full object-cover"
									/>
								{:else}
									<div
										class="flex h-full w-full items-center justify-center bg-slate-700 text-xl font-bold text-white"
									>
										{errorData.member.name.charAt(0)}
									</div>
								{/if}
							</div>
							<div class="flex-1">
								<div class="text-lg font-bold text-white">{errorData.member.name}</div>
								<div class="text-xs text-slate-400">رقم الحساب: {errorData.member.account_number}</div>
							</div>
							{#if errorData.lastSubscription}
								<div class="rounded-lg bg-rose-950/60 px-3 py-1 text-xs text-rose-300">
									انتهى في: {errorData.lastSubscription.expires_at}
								</div>
							{/if}
						</div>
					{:else}
						<div class="mt-4 font-mono text-sm text-slate-400">
							رمز البطاقة المقروءة: <span class="text-white">{errorData.scannedCode}</span>
						</div>
					{/if}

					<!-- Auto Reset Bar -->
					<div class="mt-8 flex w-full items-center justify-between text-xs text-slate-500">
						<span>إعادة تعيين الشاشة خلال {countdownSeconds} ثوانٍ...</span>
						<button
							type="button"
							class="text-xs text-slate-400 underline hover:text-white"
							onclick={resetToIdle}
						>
							عودة الآن
						</button>
					</div>
				</div>
			{/if}
		</div>

		<!-- Right Side / Feed: Today's Live Attendance Stream -->
		<div
			class="flex flex-col border-t border-slate-800 bg-slate-900/50 p-6 lg:col-span-4 lg:border-r lg:border-t-0"
		>
			<div class="mb-4 flex items-center justify-between">
				<div class="flex items-center gap-2">
					<div class="h-4 w-4 text-emerald-400">
						<FaClock />
					</div>
					<h3 class="text-base font-bold text-white">سجل الدخول اللحظي</h3>
				</div>
				<button
					type="button"
					class="btn-icon btn-icon-xs text-slate-400 hover:text-white"
					title="تحديث السجل"
					onclick={() => {
						loadRecentCheckins();
						loadStats();
					}}
				>
					<div class="h-3.5 w-3.5 {loadingRecent ? 'animate-spin' : ''}">
						<FaSyncAlt />
					</div>
				</button>
			</div>

			<div class="flex-1 space-y-2.5 overflow-y-auto pr-1">
				{#if recentCheckins.length === 0}
					<div class="flex flex-col items-center justify-center py-16 text-center text-slate-500">
						<div class="h-8 w-8 text-slate-600">
							<FaIdCard />
						</div>
						<p class="mt-2 text-sm">لا توجد عمليات دخول مسجلة اليوم حتى الآن</p>
					</div>
				{:else}
					{#each recentCheckins as item (item.id)}
						<div
							class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-800/60 p-3 transition hover:border-slate-700"
						>
							<div class="flex items-center gap-3">
								<div
									class="h-10 w-10 overflow-hidden rounded-full border border-slate-700 bg-slate-700"
								>
									{#if item.user?.avatar}
										<img
											src={getAvatarUrl(item.user.avatar)}
											alt={item.user?.name}
											class="h-full w-full object-cover"
										/>
									{:else}
										<div
											class="flex h-full w-full items-center justify-center bg-slate-600 text-sm font-bold text-white"
										>
											{item.user?.name ? item.user.name.charAt(0) : '?'}
										</div>
									{/if}
								</div>
								<div>
									<div class="text-sm font-semibold text-white">
										{item.user?.name || 'عضو غير معروف'}
									</div>
									<div class="text-xs text-slate-400">
										{item.branch?.name || 'الفرع الرئيسي'}
									</div>
								</div>
							</div>
							<div class="text-left font-mono text-xs text-emerald-400">
								{moment(item.checked_in_at).format('hh:mm A')}
							</div>
						</div>
					{/each}
				{/if}
			</div>
		</div>
	</div>
</div>

<style>
	.gate-container {
		font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
	}
</style>
