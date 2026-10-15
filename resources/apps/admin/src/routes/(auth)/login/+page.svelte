<script>
	// @ts-nocheck
	import { useToast } from '$lib/toast';
	import axios from 'axios';
	import LanguageSwitcher from '$lib/components/LanguageSwitcher.svelte';
	import { t, currentLang } from '$lib/i18n.js';

	const toast = useToast();

	let fields = $state({ email: '', password: '' });
	let loading = $state(false);

	const onSubmit = async (event) => {
		event.preventDefault();
		if (loading) return;
		loading = true;

		try {
			const response = await axios.post('/login', fields);

			if (response.data && response.data.success) {
				if (response.data.access_token) {
					try {
						localStorage.setItem('access_token', response.data.access_token);
					} catch (e) {}
				}

				toast.trigger({
					message: $t('login_success', 'تم تسجيل الدخول بنجاح! جاري التحويل...'),
					background: 'variant-filled-success'
				});

				setTimeout(() => {
					window.location.href = '/gate';
				}, 600);
			} else {
				toast.trigger({
					message: response.data?.message || $t('login_failed', 'فشل تسجيل الدخول، تأكد من صحة البيانات'),
					background: 'variant-filled-error'
				});
			}
		} catch (error) {
			const msg =
				error.response?.data?.message ||
				error.message ||
				$t('login_failed', 'تعذر تسجيل الدخول، تأكد من تشغيل سيرفر الـ API');

			toast.trigger({
				message: msg,
				background: 'variant-filled-error'
			});
		} finally {
			loading = false;
		}
	};
</script>

<div class="mb-4 flex items-center justify-between">
	<div class="flex items-center gap-2">
		<span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
		<span class="text-xs font-semibold uppercase tracking-wider text-blue-400">{$t('app_name', 'LaraGym')}</span>
	</div>
	<LanguageSwitcher />
</div>

<div class="mb-6">
	<h3 class="h3 font-bold text-white">{$t('login_title', 'تسجيل الدخول')}</h3>
	<p class="text-sm text-surface-400">{$t('login_subtitle', 'يرجى إدخال بياناتك للمتابعة إلى النظام')}</p>
</div>

<form action="" onsubmit={onSubmit} class="space-y-4">
	<div>
		<label class="label">
			<span class="text-sm font-medium">{$t('login_email', 'البريد الإلكتروني')}</span>
			<input
				class="input mt-1"
				bind:value={fields.email}
				name="email"
				type="email"
				disabled={loading}
				placeholder="admin@admin.com"
				required
			/>
		</label>
	</div>

	<div>
		<label class="label">
			<span class="text-sm font-medium">{$t('login_password', 'كلمة المرور')}</span>
			<input
				class="input mt-1"
				bind:value={fields.password}
				name="password"
				type="password"
				placeholder="password"
				disabled={loading}
				required
			/>
		</label>
	</div>

	<button
		type="submit"
		disabled={loading}
		class="variant-filled-primary btn w-full font-bold text-white shadow-lg shadow-blue-900/30 transition-all hover:brightness-110 active:scale-[0.99]"
	>
		{#if loading}
			{$t('login_btn_loading', 'جاري تسجيل الدخول...')}
		{:else}
			{$t('login_btn', 'تسجيل الدخول')}
		{/if}
	</button>

	<a href="/forgot" class="block pt-2 text-center text-xs text-surface-400 hover:text-white transition-colors">
		{$t('login_forgot', 'نسيت كلمة المرور؟')}
	</a>
</form>
