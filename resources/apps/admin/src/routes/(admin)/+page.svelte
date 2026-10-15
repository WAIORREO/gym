<script>
 // @ts-nocheck
 import { onMount } from 'svelte';
 import { getBearerToken, useApi } from '$lib/api';
 import { t, dir, currentLang } from '$lib/i18n.js';
 import Status from '$lib/components/Status.svelte';
 let data = $state(null);
 let loading = $state(true);
 let failed = $state(false);
 async function load() {
  loading = true; failed = false;
  try { data = (await useApi({Authorization:getBearerToken()}).get('/stats/overview')).data; }
  catch { failed = true; }
  finally { loading = false; }
 }
 const date = value => new Intl.DateTimeFormat($currentLang, {day:'numeric',month:'short'}).format(new Date(value));
 const time = value => new Intl.DateTimeFormat($currentLang,{hour:'2-digit',minute:'2-digit'}).format(new Date(value));
 onMount(load);
</script>
<svelte:head><title>{$t('ops_title')} · LaraGym</title></svelte:head>
<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8" dir={$dir} aria-busy={loading}>
 <header class="flex flex-wrap items-center justify-between gap-4">
  <div><p class="mb-1 text-xs font-semibold uppercase tracking-widest text-primary-500">LaraGym</p><h1 class="text-2xl font-bold">{$t('ops_title')}</h1><p class="mt-2 text-sm text-surface-500 dark:text-surface-300">{$t('ops_intro')}</p></div>
  <div class="flex flex-wrap gap-2"><button class="btn variant-soft" onclick={load} disabled={loading}>{$t('ops_refresh')}</button><a class="btn variant-filled-primary" href="/gate">{$t('nav_gate')}</a></div>
 </header>
 {#if failed}
  <div role="alert" class="rounded-xl border border-red-300 bg-red-50 p-5 text-red-900">{$t('ops_error')} <button class="btn btn-sm variant-soft-error" onclick={load}>{$t('ops_retry')}</button></div>
 {:else if !data}
  <p role="status" class="py-16 text-center">{$t('common_loading')}</p>
 {:else}
  <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label={$t('ops_title')}>
   {#each [['active_members','ops_active'],['today_visits','ops_visits'],['expiring_soon','ops_expiring'],['low_sessions','ops_low']] as [key,label]}
    <div class="rounded-2xl border border-surface-300/40 bg-white p-4 dark:bg-surface-800 md:p-6"><p class="text-sm text-surface-600 dark:text-surface-300">{$t(label)}</p><p class="mt-3 text-3xl font-bold tabular-nums">{new Intl.NumberFormat($currentLang).format(data[key])}</p></div>
   {/each}
  </section>
  <div class="grid gap-6 xl:grid-cols-3">
   <section class="overflow-hidden rounded-2xl border border-surface-300/40 bg-white dark:bg-surface-800 xl:col-span-2">
    <header class="border-b border-surface-300/30 p-5"><h2 class="text-lg font-bold">{$t('ops_attention')}</h2><p class="mt-1 text-sm text-surface-500 dark:text-surface-300">{$t('ops_attention_hint')}</p></header>
    {#if data.attention.length === 0}<p class="p-8 text-sm text-surface-500">{$t('ops_clear')}</p>{/if}
    <div class="divide-y divide-surface-300/30">
     {#each data.attention as membership}
      <a href={'/members/' + membership.user_id} class="flex flex-wrap items-center justify-between gap-3 p-5 transition-colors hover:bg-primary-500/5">
       <div><p class="font-semibold">{membership.user?.name || '—'}</p><p class="mt-1 text-sm text-surface-500">{membership.package?.name || '—'}</p></div>
       <div class="flex flex-wrap items-center gap-3 text-sm">
        {#if membership.total_sessions !== null}<span class="rounded-lg bg-amber-500/10 px-3 py-2 text-amber-700 dark:text-amber-300">{$t('gate_sessions_left')}: {membership.remaining_sessions}</span>{/if}
        <span>{$t('sub_valid_until')}: {date(membership.expires_at)}</span>
       </div>
      </a>
     {/each}
    </div>
   </section>
   <section class="rounded-2xl border border-surface-300/40 bg-white p-5 dark:bg-surface-800">
    <h2 class="mb-5 text-lg font-bold">{$t('ops_recent')}</h2>
    {#if data.recent.length === 0}<p class="text-sm text-surface-500">{$t('ops_empty')}</p>{/if}
    <div class="space-y-5">{#each data.recent as visit}<div class="flex items-start justify-between gap-3"><div><p class="font-semibold">{visit.user?.name || '—'}</p><p class="mt-1 text-xs text-surface-500">{visit.branch?.name || '—'} · {date(visit.checked_in_at)}</p></div><time class="whitespace-nowrap text-sm tabular-nums">{time(visit.checked_in_at)}</time></div>{/each}</div>
   </section>
  </div>
 {/if}
 <nav class="flex flex-wrap gap-3" aria-label={$t('common_actions')}>
  {#each [['/members','nav_members'],['/packages','nav_packages'],['/subscriptions','nav_subscriptions'],['/branches','nav_branches']] as [href,label]}<a class="btn variant-soft-primary" {href}>{$t(label)}</a>{/each}
 </nav>
</div>
