<script>
 // @ts-nocheck
 import { onMount } from 'svelte';
 import { getMe, getSubscriptions } from '$lib/api/me.js';
 import { t, currentLang, dir } from '$lib/i18n.js';
 let { home = false } = $props();
 let member = $state(null), memberships = $state([]), loading = $state(true), failed = $state(false), page = $state(1), last = $state(1);
 async function load(target = 1) {
  loading = true; failed = false;
  try {
   const [me, result] = await Promise.all([getMe(),getSubscriptions({page:String(target)})]);
   member=me; memberships=result.data ?? result; page=result.current_page ?? 1; last=result.last_page ?? 1;
  } catch { failed=true; }
  finally { loading=false; }
 }
 const date = value => value ? new Intl.DateTimeFormat($currentLang,{day:'numeric',month:'short',year:'numeric'}).format(new Date(value)) : '—';
 onMount(() => load());
</script>
<svelte:head><title>{$t(home ? 'member_home' : 'member_plans')} · LaraGym</title></svelte:head>
<div class="mx-auto max-w-4xl space-y-6 px-4 py-6 md:px-8" dir={$dir} aria-busy={loading}>
 <header><p class="mb-2 text-sm text-primary-300">{$t(home ? 'member_welcome' : 'member_history')}</p><h1 class="text-2xl font-bold">{home ? (member?.name || 'LaraGym') : $t('member_plans')}</h1><p class="mt-2 text-sm text-surface-300">{$t('member_intro')}</p>{#if member}<p class="mt-3 text-xs text-surface-400">{$t('member_number')} <span dir="ltr">{member.account_number}</span></p>{/if}</header>
 {#if failed}<div role="alert" class="rounded-xl border border-red-500/30 p-5"><p>{$t('ops_error')}</p><button class="btn variant-soft mt-3" onclick={() => load(page)}>{$t('ops_retry')}</button></div>
 {:else if loading}<p role="status" class="py-12 text-center">{$t('member_loading')}</p>
 {:else if !memberships.length}<p class="rounded-2xl border border-surface-600 p-6 text-surface-300">{$t('member_no_plan')}</p>
 {:else}
  <div class="grid gap-4 md:grid-cols-2">
   {#each memberships as plan}
    {@const state = plan.effective_status || plan.status}
    <article class="rounded-2xl border border-surface-600 bg-surface-800 p-5">
     <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-bold">{plan.package?.name || $t('member_plans')}</h2><span class="rounded-full px-3 py-1 text-xs {state === 'active' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-surface-700 text-surface-200'}">{$t('sub_status_' + state)}</span></div>
     {#if plan.total_sessions !== null}
      <p class="mt-6 text-sm text-surface-300">{$t('member_remaining')}</p><p class="mt-1 text-3xl font-bold tabular-nums">{plan.remaining_sessions ?? 0}<span class="text-base font-normal text-surface-400"> / {plan.total_sessions}</span></p>
      <progress class="mt-3 h-2 w-full accent-emerald-400" max={Math.max(1,plan.total_sessions)} value={plan.remaining_sessions ?? 0} aria-label={$t('member_remaining')}></progress>
      <p class="mt-2 text-xs text-surface-400">{$t('member_used')}: {plan.sessions_used || 0}</p>
     {:else}<p class="my-6 text-lg font-semibold text-primary-300">{$t('member_unlimited')}</p>{/if}
     <dl class="mt-5 space-y-2 border-t border-surface-600 pt-4 text-sm"><div class="flex justify-between gap-3"><dt class="text-surface-400">{$t('member_start')}</dt><dd>{date(plan.starts_at || plan.created_at)}</dd></div><div class="flex justify-between gap-3"><dt class="text-surface-400">{$t('member_end')}</dt><dd>{date(plan.expires_at)}</dd></div></dl>
    </article>
   {/each}
  </div>
  {#if last > 1}<nav class="flex items-center justify-between gap-3"><button class="btn variant-soft" disabled={page <= 1 || loading} onclick={() => load(page - 1)}>{$t('member_previous')}</button><span>{page} / {last}</span><button class="btn variant-soft" disabled={page >= last || loading} onclick={() => load(page + 1)}>{$t('member_next')}</button></nav>{/if}
 {/if}
 {#if home}<nav class="grid grid-cols-2 gap-3"><a class="btn variant-filled-primary" href="/attendance">{$t('member_attendance')}</a><a class="btn variant-soft" href="/branches">{$t('member_branches')}</a></nav>{/if}
</div>
