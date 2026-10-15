<script setup>
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Errors from '@/Shared/Form/Errors.vue';
import ContactSelector from '@/Shared/Form/ContactSelector.vue';
import Dropdown from '@/Shared/Form/Dropdown.vue';
import ContactCard from '@/Shared/ContactCard.vue';
import { Gift } from 'lucide-vue-next';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const nameInput = useTemplateRef('nameInput');
const loadingState = ref('');
const createGiftFormShown = ref(false);
const editedGiftId = ref(0);
const localOpenGifts = ref(props.data.open_gifts);
const localGivenGifts = ref(props.data.given_gifts);
const localCurrencies = ref([]);
const fieldErrors = ref({});
const generalErrors = ref([]);

const form = useForm({
  name: '',
  recipients: [],
  gift_occasion_id: '',
  gift_state_id: '',
  estimated_price: '',
  currency_id: '',
  url: '',
  given_at: '',
  description: '',
});

const canEdit = computed(() => props.layoutData.vault.permission.at_least_editor);

const occasionOptions = computed(() => [{ id: '', name: trans('No occasion') }, ...props.data.gift_occasions]);
const stateOptions = computed(() => [{ id: '', name: trans('No gift state') }, ...props.data.gift_states]);
const currencyOptions = computed(() => [{ id: '', name: trans('Choose a value') }, ...localCurrencies.value]);

const resetErrors = () => {
  fieldErrors.value = {};
  generalErrors.value = [];
};

const getCurrencies = () => {
  if (localCurrencies.value.length === 0) {
    axios.get(props.data.url.currencies).then((response) => {
      localCurrencies.value = response.data.data;
    });
  }
};

const showCreateGiftForm = () => {
  getCurrencies();
  resetErrors();
  editedGiftId.value = 0;
  form.name = '';
  form.recipients = [props.data.contact];
  form.gift_occasion_id = '';
  form.gift_state_id = props.data.default_gift_state_id ?? '';
  form.estimated_price = '';
  form.currency_id = '';
  form.url = '';
  form.given_at = '';
  form.description = '';
  createGiftFormShown.value = true;

  nextTick().then(() => nameInput.value?.focus());
};

const showEditGiftForm = (gift) => {
  getCurrencies();
  resetErrors();
  createGiftFormShown.value = false;
  form.name = gift.name;
  form.recipients = [...gift.recipients];
  form.gift_occasion_id = gift.gift_occasion?.id ?? '';
  form.gift_state_id = gift.gift_state?.id ?? '';
  form.estimated_price = gift.estimated_price_input ?? '';
  form.currency_id = gift.currency_id ?? '';
  form.url = gift.link ?? '';
  form.given_at = gift.given_at ?? '';
  form.description = gift.description ?? '';
  editedGiftId.value = gift.id;
};

const cancel = () => {
  createGiftFormShown.value = false;
  editedGiftId.value = 0;
  resetErrors();
};

const payload = () => ({
  name: form.name,
  recipients: form.recipients.map((recipient) => ({ id: recipient.id })),
  gift_occasion_id: form.gift_occasion_id === '' ? null : form.gift_occasion_id,
  gift_state_id: form.gift_state_id === '' ? null : form.gift_state_id,
  estimated_price: form.estimated_price === '' ? null : form.estimated_price,
  currency_id: form.currency_id === '' ? null : form.currency_id,
  url: form.url === '' ? null : form.url,
  given_at: form.given_at === '' ? null : form.given_at,
  description: form.description === '' ? null : form.description,
});

const handleError = (error) => {
  loadingState.value = '';
  if (error.response?.status === 422 && error.response.data.errors) {
    fieldErrors.value = error.response.data.errors;
  } else {
    generalErrors.value = error.response?.data ?? [];
  }
};

const removeFromLists = (giftId) => {
  localOpenGifts.value = localOpenGifts.value.filter((gift) => gift.id !== giftId);
  localGivenGifts.value = localGivenGifts.value.filter((gift) => gift.id !== giftId);
};

const placeInLists = (gift) => {
  removeFromLists(gift.id);

  // the gift only stays in this section if this contact is still a recipient
  if (!gift.recipients.some((recipient) => recipient.id === props.data.contact.id)) {
    return;
  }

  if (gift.is_given) {
    localGivenGifts.value.unshift(gift);
  } else {
    localOpenGifts.value.unshift(gift);
  }
};

const validateRecipients = () => {
  if (form.recipients.length === 0) {
    fieldErrors.value = { recipient_ids: [trans('Please choose at least one recipient.')] };
    return false;
  }

  return true;
};

const submit = () => {
  resetErrors();
  if (!validateRecipients()) {
    return;
  }

  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, payload())
    .then((response) => {
      flash(trans('The gift has been added'), 'success');
      placeInLists(response.data.data);
      loadingState.value = '';
      createGiftFormShown.value = false;
    })
    .catch(handleError);
};

const update = (gift) => {
  resetErrors();
  if (!validateRecipients()) {
    return;
  }

  loadingState.value = 'loading';

  axios
    .put(gift.url.update, payload())
    .then((response) => {
      flash(trans('The gift has been updated'), 'success');
      placeInLists(response.data.data);
      loadingState.value = '';
      editedGiftId.value = 0;
    })
    .catch(handleError);
};

const save = () => {
  if (createGiftFormShown.value) {
    submit();
    return;
  }

  const gift = [...localOpenGifts.value, ...localGivenGifts.value].find((g) => g.id === editedGiftId.value);
  update(gift);
};

const destroy = (gift) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(gift.url.destroy)
      .then(() => {
        flash(trans('The gift has been deleted'), 'success');
        removeFromLists(gift.id);
      })
      .catch(handleError);
  }
};
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
        @click="showCreateGiftForm" />
    </div>

    <!-- add or edit a gift -->
    <form
      v-if="createGiftFormShown || editedGiftId !== 0"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="save">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <!-- name -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <text-input
            ref="nameInput"
            v-model="form.name"
            :label="$t('What is the gift?')"
            :type="'text'"
            :input-class="'block w-full'"
            :required="true"
            :autocomplete="false"
            :maxlength="255"
            @esc-key-pressed="cancel" />
          <p v-if="fieldErrors.name" class="mt-1 text-sm text-red-600">{{ fieldErrors.name[0] }}</p>
        </div>

        <!-- recipients -->
        <div class="border-b border-gray-200 dark:border-gray-700">
          <contact-selector
            v-model="form.recipients"
            :search-url="layoutData.vault.url.search_contacts_only"
            :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
            :display-most-consulted-contacts="true"
            :label="$t('Who is the gift for?')"
            :add-multiple-contacts="true"
            :required="true"
            :class="'p-5'" />
          <p v-if="fieldErrors.recipient_ids" class="px-5 pb-5 text-sm text-red-600">
            {{ fieldErrors.recipient_ids[0] }}
          </p>
        </div>

        <!-- occasion + state -->
        <div class="flex border-b border-gray-200 p-5 dark:border-gray-700">
          <div class="me-3">
            <dropdown
              v-model="form.gift_occasion_id"
              :data="occasionOptions"
              :required="false"
              :dropdown-class="'block'"
              :label="$t('Gift occasion')" />
            <p v-if="fieldErrors.gift_occasion_id" class="mt-1 text-sm text-red-600">
              {{ fieldErrors.gift_occasion_id[0] }}
            </p>
          </div>

          <div>
            <dropdown
              v-model="form.gift_state_id"
              :data="stateOptions"
              :required="false"
              :dropdown-class="'block'"
              :label="$t('Gift state')" />
            <p v-if="fieldErrors.gift_state_id" class="mt-1 text-sm text-red-600">
              {{ fieldErrors.gift_state_id[0] }}
            </p>
          </div>
        </div>

        <!-- rough price + currency -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <div class="flex">
            <text-input
              v-model="form.estimated_price"
              :label="$t('Rough price')"
              :help="$t('Write the amount with a dot if you need decimals, like 100.50')"
              :type="'number'"
              :input-class="'w-full'"
              :required="false"
              :min="0"
              :autocomplete="false"
              @esc-key-pressed="cancel" />

            <dropdown
              v-model="form.currency_id"
              :data="currencyOptions"
              :required="false"
              :class="'mb-5 ms-3'"
              :dropdown-class="'block'"
              :label="$t('Currency')" />
          </div>
          <p v-if="fieldErrors.estimated_price" class="mt-1 text-sm text-red-600">
            {{ fieldErrors.estimated_price[0] }}
          </p>
          <p v-if="fieldErrors.currency_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.currency_id[0] }}</p>
        </div>

        <!-- link -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <text-input
            v-model="form.url"
            :label="$t('Link')"
            :help="$t('Monica never opens or checks this link.')"
            :type="'url'"
            :input-class="'block w-full'"
            :required="false"
            :autocomplete="false"
            :maxlength="2048"
            @esc-key-pressed="cancel" />
          <p v-if="fieldErrors.url" class="mt-1 text-sm text-red-600">{{ fieldErrors.url[0] }}</p>
        </div>

        <!-- date given -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <label for="gift-given-at" class="mb-2 block text-sm">
            {{ $t('Date given') }}
            <span class="optional-badge rounded-xs px-[3px] py-px text-xs">{{ $t('optional') }}</span>
          </label>
          <input
            id="gift-given-at"
            v-model="form.given_at"
            type="date"
            class="rounded-md border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-900" />
          <p v-if="fieldErrors.given_at" class="mt-1 text-sm text-red-600">{{ fieldErrors.given_at[0] }}</p>
        </div>

        <!-- note -->
        <div class="p-5">
          <text-area
            v-model="form.description"
            :label="$t('Note')"
            :maxlength="65535"
            :textarea-class="'block w-full'" />
          <p v-if="fieldErrors.description" class="mt-1 text-sm text-red-600">{{ fieldErrors.description[0] }}</p>
        </div>
      </div>

      <div v-if="generalErrors.length > 0 || generalErrors.message" class="p-5">
        <errors :errors="generalErrors" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="cancel" />
        <pretty-button
          :text="createGiftFormShown ? $t('Add gift') : $t('Save')"
          :state="loadingState"
          :icon="'check'"
          :class="'save'" />
      </div>
    </form>

    <!-- open gifts -->
    <div v-if="localOpenGifts.length > 0" class="mb-5">
      <p class="mb-2 text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $t('Open gifts') }}</p>

      <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="gift in localOpenGifts"
          :key="gift.id"
          class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:hover:bg-slate-800">
          <div class="flex items-start justify-between">
            <div>
              <span class="block">
                {{ gift.name }}
                <span v-if="gift.estimated_price" class="ms-2 text-sm text-gray-500">{{ gift.estimated_price }}</span>
              </span>

              <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                <span v-if="gift.gift_occasion" class="rounded-full bg-sky-400/10 px-2 py-0.5 text-sky-600">
                  {{ gift.gift_occasion.name }}
                </span>
                <span v-if="gift.gift_state" class="rounded-full bg-gray-400/10 px-2 py-0.5 text-gray-600">
                  {{ gift.gift_state.name }}
                </span>
              </div>

              <p v-if="gift.description" class="mt-1 text-sm">{{ gift.description }}</p>

              <a
                v-if="gift.link"
                :href="gift.link"
                target="_blank"
                rel="noopener noreferrer nofollow"
                class="mt-1 block break-all text-sm text-blue-500 hover:underline">
                {{ gift.link }}
              </a>
            </div>

            <div class="flex -space-x-2 overflow-hidden">
              <div v-for="recipient in gift.recipients" :key="recipient.id">
                <contact-card
                  :contact="recipient"
                  :avatar-classes="'h-7 w-7 rounded-full me-2'"
                  :display-name="false" />
              </div>
            </div>
          </div>

          <ul v-if="canEdit" class="mt-2 text-sm">
            <li class="me-4 inline cursor-pointer text-blue-500 hover:underline" @click="showEditGiftForm(gift)">
              {{ $t('Edit') }}
            </li>
            <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(gift)">
              {{ $t('Delete') }}
            </li>
          </ul>
        </li>
      </ul>
    </div>

    <!-- given gifts -->
    <div v-if="localGivenGifts.length > 0" class="mb-5">
      <p class="mb-2 text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $t('Given gifts') }}</p>

      <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="gift in localGivenGifts"
          :key="gift.id"
          class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:hover:bg-slate-800">
          <div class="flex items-start justify-between">
            <div>
              <span class="block">
                {{ gift.name }}
                <span v-if="gift.estimated_price" class="ms-2 text-sm text-gray-500">{{ gift.estimated_price }}</span>
              </span>

              <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                <span v-if="gift.given_at_human_format" class="text-gray-500">{{ gift.given_at_human_format }}</span>
                <span v-if="gift.gift_occasion" class="rounded-full bg-sky-400/10 px-2 py-0.5 text-sky-600">
                  {{ gift.gift_occasion.name }}
                </span>
              </div>

              <p v-if="gift.description" class="mt-1 text-sm">{{ gift.description }}</p>

              <a
                v-if="gift.link"
                :href="gift.link"
                target="_blank"
                rel="noopener noreferrer nofollow"
                class="mt-1 block break-all text-sm text-blue-500 hover:underline">
                {{ gift.link }}
              </a>
            </div>

            <div class="flex -space-x-2 overflow-hidden">
              <div v-for="recipient in gift.recipients" :key="recipient.id">
                <contact-card
                  :contact="recipient"
                  :avatar-classes="'h-7 w-7 rounded-full me-2'"
                  :display-name="false" />
              </div>
            </div>
          </div>

          <ul v-if="canEdit" class="mt-2 text-sm">
            <li class="me-4 inline cursor-pointer text-blue-500 hover:underline" @click="showEditGiftForm(gift)">
              {{ $t('Edit') }}
            </li>
            <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(gift)">
              {{ $t('Delete') }}
            </li>
          </ul>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="localOpenGifts.length === 0 && localGivenGifts.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">
        {{ $t('There are no gifts yet. Use “Add a gift” to save a gift idea for this contact.') }}
      </p>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.item-list {
  &:last-child {
    border-bottom: 0;
  }
}

.optional-badge {
  color: #283e59;
  background-color: #edf2f9;
}
</style>
