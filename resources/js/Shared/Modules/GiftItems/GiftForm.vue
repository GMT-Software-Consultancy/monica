<script setup>
import { onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import { flash, isDark } from '@/methods';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Errors from '@/Shared/Form/Errors.vue';
import ContactSelector from '@/Shared/Form/ContactSelector.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
  // the gift being edited, or null when adding a gift
  gift: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['saved', 'cancel']);

const loadingState = ref('');
const warning = ref('');
const currencies = ref([]);
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});

const emptyOption = { id: '', name: trans('None') };

const form = useForm({
  name: props.gift?.name ?? '',
  recipients: props.gift ? [...props.gift.recipients] : [props.data.contact],
  gift_occasion_id: props.gift?.gift_occasion?.id ?? '',
  gift_state_id: props.gift?.gift_state?.id ?? '',
  estimated_price: props.gift?.estimated_price_input ?? '',
  currency_id: props.gift?.currency_id ?? '',
  link: props.gift?.link ?? '',
  description: props.gift?.description ?? '',
  given_at: props.gift?.given_at ?? '',
  errors: [],
});

// Picking the last gift state (by position) fills in today's date as the
// given date, unless a given date is already set.
watch(
  () => form.gift_state_id,
  (value) => {
    const lastId = props.data.last_gift_state_id;
    if (lastId !== null && String(value) === String(lastId) && !form.given_at) {
      form.given_at = props.data.current_date;
    }
  },
);

onMounted(() => {
  axios.get(props.data.url.currencies).then((response) => {
    currencies.value = [emptyOption, ...response.data.data];
  });
});

const clearGivenDate = () => {
  form.given_at = '';
};

const submit = () => {
  warning.value = '';
  if (form.recipients.length === 0) {
    warning.value = trans('The gift needs at least one recipient.');
    return;
  }

  loadingState.value = 'loading';

  const request = props.gift ? axios.put(props.gift.url.update, form) : axios.post(props.data.url.store, form);

  request
    .then((response) => {
      loadingState.value = '';
      flash(props.gift ? trans('The gift has been updated') : trans('The gift has been added'), 'success');
      emit('saved', response.data.data);
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};
</script>

<template>
  <form
    class="mb-6 w-full rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
    @submit.prevent="submit()">
    <div class="border-b border-gray-200 dark:border-gray-700">
      <!-- name -->
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <text-input
          v-model="form.name"
          :label="$t('What is the gift?')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="emit('cancel')" />
      </div>

      <!-- recipients -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <contact-selector
          v-model="form.recipients"
          :search-url="layoutData.vault.url.search_contacts_only"
          :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
          :display-most-consulted-contacts="false"
          :label="$t('Who is the gift for?')"
          :add-multiple-contacts="true"
          :required="true"
          :class="'p-5'" />
      </div>

      <!-- occasion + state -->
      <div class="flex border-b border-gray-200 p-5 dark:border-gray-700">
        <dropdown
          v-model="form.gift_occasion_id"
          :data="[emptyOption, ...data.gift_occasions]"
          :required="false"
          :class="'me-3'"
          :dropdown-class="'block'"
          :label="$t('Gift occasion')" />

        <dropdown
          v-model="form.gift_state_id"
          :data="[emptyOption, ...data.gift_states]"
          :required="false"
          :dropdown-class="'block'"
          :label="$t('Gift state')" />
      </div>

      <!-- estimated price + currency -->
      <div class="flex border-b border-gray-200 p-5 dark:border-gray-700">
        <text-input
          v-model="form.estimated_price"
          :label="$t('Estimated price')"
          :help="$t('Write the amount with a dot if you need decimals, like 100.50')"
          :type="'number'"
          :input-class="'w-full'"
          :required="false"
          :min="0"
          :max="10000000"
          :autocomplete="false"
          @esc-key-pressed="emit('cancel')" />

        <dropdown
          v-model="form.currency_id"
          :data="currencies"
          :required="false"
          :class="'mb-5 ms-3'"
          :dropdown-class="'block'"
          :label="$t('Currency')" />
      </div>

      <!-- link -->
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <text-input
          v-model="form.link"
          :label="$t('Link')"
          :type="'text'"
          :input-class="'block w-full'"
          :required="false"
          :autocomplete="false"
          :maxlength="2048"
          :placeholder="'https://'"
          @esc-key-pressed="emit('cancel')" />
      </div>

      <!-- given at -->
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <p class="mb-2 block text-sm">
          {{ $t('When was the gift given?') }}
          <span class="optional-badge rounded-xs px-[3px] py-px text-xs">{{ $t('optional') }}</span>
        </p>

        <div class="flex items-center">
          <DatePicker
            v-model.string="form.given_at"
            class="inline-block h-full"
            :masks="masks"
            :locale="$page.props.auth.user?.locale_ietf"
            :is-dark="isDark()">
            <template #default="{ inputValue, inputEvents }">
              <input
                class="rounded-xs border bg-white px-2 py-1 dark:bg-gray-900"
                :value="inputValue"
                v-on="inputEvents" />
            </template>
          </DatePicker>

          <span
            v-if="form.given_at"
            class="ms-3 cursor-pointer text-sm text-blue-500 hover:underline"
            @click="clearGivenDate">
            {{ $t('Clear') }}
          </span>
        </div>
      </div>

      <!-- note -->
      <div class="p-5">
        <text-area v-model="form.description" :label="$t('Note')" :maxlength="65535" :textarea-class="'block w-full'" />
      </div>
    </div>

    <div v-if="form.errors.length > 0 || form.errors.errors" class="p-5">
      <errors :errors="form.errors" />
    </div>

    <div v-if="warning !== ''" class="border-b p-3">⚠️ {{ warning }}</div>

    <div class="flex justify-between p-5">
      <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="emit('cancel')" />
      <pretty-button
        :text="gift ? $t('Save') : $t('Add gift')"
        :state="loadingState"
        :icon="gift ? 'check' : 'plus'"
        :class="'save'" />
    </div>
  </form>
</template>

<style lang="scss" scoped>
.optional-badge {
  color: #283e59;
  background-color: #edf2f9;
}

.dark .optional-badge {
  color: #d4d8dd !important;
  background-color: #2f3031 !important;
}
</style>
