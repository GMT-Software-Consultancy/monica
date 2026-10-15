<script setup>
import { ref } from 'vue';
import { Link as InertiaLink } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import Avatar from '@/Shared/Avatar.vue';
import { Gift } from 'lucide-vue-next';

defineProps({
  layoutData: Object,
  data: Object,
});

const selectedGiftId = ref(0);

const toggle = (gift) => {
  selectedGiftId.value = selectedGiftId.value === gift.id ? 0 : gift.id;
};
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title -->
        <div class="mb-5 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
          <div class="mb-2 sm:mb-0 flex items-center gap-2">
            <Gift class="h-4 w-4 text-gray-600" />

            {{ $t('Open gift ideas') }}
          </div>
        </div>

        <!-- one group per occasion -->
        <div v-for="group in data.groups" :key="group.id" class="mb-6">
          <p class="mb-2 font-semibold">{{ group.name }}</p>

          <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="gift in group.gifts"
              :key="gift.id"
              class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-slate-900">
              <div class="flex cursor-pointer items-center justify-between" @click="toggle(gift)">
                <span>
                  {{ gift.name }}
                  <span
                    v-if="gift.state"
                    class="ms-2 rounded-full bg-sky-400/10 px-2 py-0.5 text-xs text-sky-600 dark:bg-sky-600/10 dark:text-sky-400">
                    {{ gift.state.name }}
                  </span>
                </span>

                <span class="flex flex-wrap items-center gap-2 text-sm">
                  <span v-for="recipient in gift.recipients" :key="recipient.id" class="flex items-center">
                    <avatar :data="recipient.avatar" :class="'me-1 h-5 w-5 rounded-full'" />
                    {{ recipient.name }}
                  </span>
                </span>
              </div>

              <!-- details of the selected gift -->
              <div
                v-if="selectedGiftId === gift.id"
                class="mt-2 border-t border-gray-200 pt-2 text-sm dark:border-gray-700">
                <p v-if="gift.price_formatted" class="mb-1">
                  <span class="text-gray-500">{{ $t('Rough price') }}:</span> {{ gift.price_formatted }}
                </p>
                <p v-if="gift.link" class="mb-1 break-all">
                  <span class="text-gray-500">{{ $t('Link') }}:</span>
                  <a
                    :href="gift.link"
                    target="_blank"
                    rel="noopener noreferrer nofollow"
                    class="text-blue-500 hover:underline">
                    {{ gift.link }}
                  </a>
                </p>
                <p v-if="gift.description" class="mb-1 whitespace-pre-line">
                  <span class="text-gray-500">{{ $t('Note') }}:</span> {{ gift.description }}
                </p>
                <p v-if="gift.given_at_human_format" class="mb-1">
                  <span class="text-gray-500">{{ $t('Date given') }}:</span> {{ gift.given_at_human_format }}
                </p>
                <p class="mt-2">
                  <span v-if="layoutData.vault.permission.at_least_editor" class="text-gray-500">
                    {{ $t('To change this gift, open it from the page of:') }}
                  </span>
                  <span v-else class="text-gray-500">{{ $t('For:') }}</span>
                  <InertiaLink
                    v-for="recipient in gift.recipients"
                    :key="recipient.id"
                    :href="recipient.url.show"
                    class="ms-2 text-blue-500 hover:underline">
                    {{ recipient.name }}
                  </InertiaLink>
                </p>
              </div>
            </li>
          </ul>
        </div>

        <!-- blank state -->
        <div
          v-if="data.groups.length <= 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('There are no open gift ideas.') }}
            {{ $t('You can add a gift from any contact’s page.') }}
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
