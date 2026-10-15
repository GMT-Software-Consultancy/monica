<script setup>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import { Gift } from 'lucide-vue-next';

defineProps({
  layoutData: Object,
  data: Object,
});
</script>

<template>
  <Layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title -->
        <div class="mb-5 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
          <div class="mb-2 sm:mb-0 flex items-center gap-2">
            <Gift class="h-4 w-4 text-gray-600" />

            {{ $t('Open gifts in this vault') }}
          </div>
        </div>

        <!-- groups of gifts, by occasion -->
        <div v-for="group in data.groups" :key="group.id ?? 'none'" class="mb-6">
          <p class="mb-2 font-semibold">{{ group.name ?? $t('No occasion') }}</p>

          <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="gift in group.gifts"
              :key="gift.id"
              class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-slate-900">
              <div>
                <span class="block">{{ gift.name }}</span>

                <span class="text-sm text-gray-500">
                  <template v-for="(recipient, index) in gift.recipients" :key="recipient.id">
                    <Link :href="recipient.url" class="text-blue-500 hover:underline">{{ recipient.name }}</Link
                    ><span v-if="index < gift.recipients.length - 1">, </span>
                  </template>
                </span>
              </div>

              <span
                v-if="gift.gift_state"
                class="rounded-full bg-gray-400/10 px-2 py-0.5 text-xs text-gray-600 dark:text-gray-300">
                {{ gift.gift_state.name }}
              </span>
            </li>
          </ul>
        </div>

        <!-- blank state -->
        <div
          v-if="data.groups.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('There are no open gifts in this vault.') }}
          </p>
          <p class="px-5 pb-5 text-center text-sm text-gray-500">
            {{
              $t(
                'To add a gift, open the page of the person the gift is for, and select “Add a gift” in the Gifts section.',
              )
            }}
          </p>
        </div>
      </div>
    </main>
  </Layout>
</template>

<style lang="scss" scoped>
.item-list {
  &:last-child {
    border-bottom: 0;
  }
}
</style>
