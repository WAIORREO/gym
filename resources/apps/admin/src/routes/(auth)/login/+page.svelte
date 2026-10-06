<script>
	// @ts-nocheck
	import { useToast } from '$lib/toast';
	import axios from 'axios';

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
				// Store in localStorage as backup
				if (response.data.access_token) {
					try {
						localStorage.setItem('access_token', response.data.access_token);
					} catch (e) {}
				}

				toast.trigger({
					message: 'تم تسجيل الدخول بنجاح! جاري التحويل...',
					background: 'variant-filled-success'
				});

				setTimeout(() => {
					window.location.href = '/gate';
				}, 600);
			} else {
				toast.trigger({
					message: response.data?.message || 'فشل تسجيل الدخول، تأكد من صحة البيانات',
					background: 'variant-filled-error'
				});
			}
		} catch (error) {
			const msg =
				error.response?.data?.message ||
				error.message ||
				'تعذر تسجيل الدخول، تأكد من تشغيل سيرفر الـ API';

			toast.trigger({
				message: msg,
				background: 'variant-filled-error'
			});
		} finally {
			loading = false;
		}
	};
</script>

<div class="mb-6">
	<h3 class="h3">Account Login</h3>
	<p>Please login to get started</p>
</div>

<form action="" onsubmit={onSubmit}>
	<div class="mb-4">
		<label class="label">
			<span>Email</span>
			<input
				class="input"
				bind:value={fields.email}
				name="email"
				type="email"
				disabled={loading}
				placeholder="admin@admin.com"
				required
			/>
		</label>
	</div>

	<div class="mb-6">
		<label class="label">
			<span>Password</span>
			<input
				class="input"
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
		class="variant-filled-primary btn w-full font-bold text-white"
	>
		{#if loading}
			جاري تسجيل الدخول...
		{:else}
			Login
		{/if}
	</button>

	<a href="/forgot" class="block pt-2 text-center">Forgot password</a>
</form>
