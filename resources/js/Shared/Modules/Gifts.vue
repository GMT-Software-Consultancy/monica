<script setup>
import { computed, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { flash } from '@/methods';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import GiftForm from '@/Shared/Modules/GiftItems/GiftForm.vue';
import { Gift } from 'lucide-vue-next';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const createGiftModalShown = ref(false);
const editedGiftId = ref(0);
const localGifts = ref([...props.data.ideas, ...props.data.given]);

const canEdit = computed(() => props.layoutData.vault.permission.at_least_editor);

// "Ideas": open gifts, newest created first.
const ideas = computed(() =>
  localGifts.value.filter((gift) => !gift.given_at).sort((a, b) => b.created_at - a.created_at || b.id - a.id),
);

// "Given": given gifts, most recent given date first.
const given = computed(() =>
  localGifts.value.filter((gift) => gift.given_at).sort((a, b) => b.given_at.localeCompare(a.given_at) || b.id - a.id),
);

const showCreateGiftModal = () => {
  editedGiftId.value = 0;
  createGiftModalShown.value = true;
};

const showEditGiftModal = (gift) => {
  createGiftModalShown.value = false;
  editedGiftId.value = gift.id;
};

const created = (gift) => {
  if (gift.is_recipient) {
    localGifts.value.push(gift);
  }
  createGiftModalShown.value = false;
};

const updated = (gift) => {
  const index = localGifts.value.findIndex((x) => x.id === gift.id);
  if (gift.is_recipient) {
    localGifts.value[index] = gift;
  } else {
    localGifts.value.splice(index, 1);
  }
  editedGiftId.value = 0;
};

const destroy = (gift) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios.delete(gift.url.destroy).then(() => {
      flash(trans('The gift has been deleted'), 'success');
      localGifts.value.splice(
        localGifts.value.findIndex((x) => x.id === gift.id),
        1,
      );
    });
  }
};

const sections = computed(() => [
  { key: 'ideas', title: trans('Ideas'), gifts: ideas.value, empty: trans('There are no gift ideas yet.') },
  { key: 'given', title: trans('Given'), gifts: given.value, empty: trans('No gift has been given yet.') },
]);
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <Gift class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Gifts') }} </span>
      </div>
      <pretty-button
        v-if="canEdit"
        :text="$t('Add a gift')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateGiftModal" />
    </div>

    <!-- add a gift -->
    <gift-form
      v-if="createGiftModalShown"
      :data="data"
      :layout-data="layoutData"
      @saved="created"
      @cancel="createGiftModalShown = false" />

    <div v-for="section in sections" :key="section.key" class="mb-6">
      <p class="mb-2 text-sm font-semibold text-gray-600 dark:text-gray-400">{{ section.title }}</p>

      <div v-for="gift in section.gifts" :key="gift.id" class="mb-3">
        <div
          v-if="editedGiftId !== gift.id"
          class="item-list w-full rounded-lg border border-gray-200 bg-white hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800">
          <div class="border-b border-gray-200 px-3 py-2 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <span class="me-2 block">
                  <a
                    v-if="gift.link"
                    :href="gift.link"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-blue-500 hover:underline">
                    {{ gift.name }}
                  </a>
                  <span v-else>{{ gift.name }}</span>
                </span>

                <span v-if="gift.other_recipients.length > 0" class="block text-sm text-gray-500">
                  {{ $t('Also for: :names', { names: gift.other_recipients.join(', ') }) }}
                </span>

                <span v-if="gift.description" class="block text-sm">{{ gift.description }}</span>
              </div>

              <span v-if="gift.given_at_human_format" class="me-2 text-sm text-gray-500">
                {{ gift.given_at_human_format }}
              </span>
            </div>

            <div class="mt-1 flex flex-wrap gap-2 text-xs">
              <span
                v-if="gift.gift_occasion"
                class="rounded-full bg-sky-400/10 px-2 py-0.5 text-sky-600 dark:text-sky-400">
                {{ gift.gift_occasion.name }}
              </span>
              <span
                v-if="gift.gift_state"
                class="rounded-full bg-green-400/10 px-2 py-0.5 text-green-600 dark:text-green-400">
                {{ gift.gift_state.name }}
              </span>
              <span
                v-if="gift.estimated_price"
                class="rounded-full bg-gray-400/10 px-2 py-0.5 text-gray-600 dark:text-gray-400">
                {{ gift.estimated_price }}
              </span>
            </div>
          </div>

          <!-- actions -->
          <div v-if="canEdit" class="px-3 py-2">
            <ul class="text-sm">
              <li class="me-4 inline cursor-pointer text-blue-500 hover:underline" @click="showEditGiftModal(gift)">
                {{ $t('Edit') }}
              </li>
              <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(gift)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>
        </div>

        <!-- edit a gift -->
        <gift-form
          v-else
          :data="data"
          :layout-data="layoutData"
          :gift="gift"
          @saved="updated"
          @cancel="editedGiftId = 0" />
      </div>

      <!-- blank state -->
      <div
        v-if="section.gifts.length === 0"
        class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <p class="p-5 text-center text-sm text-gray-500">{{ section.empty }}</p>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.item-list {
  &:hover {
    border-radius: 8px;
  }
}
</style>
