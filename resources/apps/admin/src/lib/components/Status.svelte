<script>
 // @ts-nocheck
 import { t } from "$lib/i18n.js";
		let { status } = $props();

	let badgeStyle = $derived.by(() => {
		return (
			{
				active: 'variant-filled-success text-white',
				inactive: 'variant-filled-warning',
				suspended: 'variant-filled-secondary text-white',
				expired: 'variant-filled-tertiary text-white'
			}[status] || 'variant-filled-primary text-white'
		);
	});
</script>

<span class={`badge ${badgeStyle}`}>
	{$t('sub_status_' + status, status)}
</span>
