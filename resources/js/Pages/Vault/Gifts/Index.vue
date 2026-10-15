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
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title -->
        <div class="mb-5 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
          <div class="mb-2 sm:mb-0 flex items-center gap-2">
            <Gift class="h-4 w-4 text-gray-600" />

            {{ $t('Gift ideas') }}
          </div>
        </div>

        <!-- groups, by gift occasion -->
        <div v-for="group in data.groups" :key="group.id" class="mb-6">
          <p class="mb-2 font-semibold">{{ group.label }}</p>

          <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="gift in group.gifts"
              :key="gift.id"
              class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-slate-900">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <span class="block text-gray-900 dark:text-gray-100">{{ gift.name }}</span>

                  <span class="text-sm text-gray-500">
                    {{ $t('For:') }}
                    <template v-for="(recipient, index) in gift.recipients" :key="recipient.id">
                      <Link :href="recipient.url" class="text-blue-500 hover:underline">{{ recipient.name }}</Link
                      ><span v-if="index < gift.recipients.length - 1">, </span>
                    </template>
                  </span>
                </div>

                <div class="flex gap-2 text-xs">
                  <span
                    v-if="gift.gift_state"
                    class="rounded-full bg-green-400/10 px-2 py-0.5 text-green-600 dark:text-green-400">
                    {{ gift.gift_state }}
                  </span>
                  <span
                    v-if="gift.estimated_price"
                    class="rounded-full bg-gray-400/10 px-2 py-0.5 text-gray-600 dark:text-gray-400">
                    {{ gift.estimated_price }}
                  </span>
                </div>
              </div>
            </li>
          </ul>
        </div>

        <!-- blank state -->
        <div
          v-if="data.groups.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('There are no gift ideas yet. To add a gift, open the page of a contact and use the Gifts module.') }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
