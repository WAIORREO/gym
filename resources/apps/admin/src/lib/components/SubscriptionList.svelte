<script>
	let { children } = $props();
</script>

<div class="subscriptions">
	{@render children()}
</div>

<style lang="postcss">
	.subscriptions {
		@apply grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4;
	}
</style>
